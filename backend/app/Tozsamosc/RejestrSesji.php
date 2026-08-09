<?php

declare(strict_types=1);

namespace App\Tozsamosc;

use App\Wsparcie\Typy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use RuntimeException;

/**
 * Mapa `sid` (identyfikator sesji SSO w IdP) → identyfikatory sesji lokalnych.
 *
 * Bez niej nie da się obsłużyć back-channel logout: żądanie od IdP przychodzi
 * BEZ ciasteczka użytkownika, więc `sid` jest jedynym kluczem, po którym
 * potrafimy znaleźć sesję do skasowania (kontrakt §4.1 pkt 1 i §4.5).
 *
 * @dowod: OdebranieRoliTest — „zabija sesję NATYCHMIAST po back-channel logout"
 *         liczy skasowane sesje po `sid` z tego rejestru.
 */
final class RejestrSesji
{
    /** Rejestr żyje tak długo, jak najdłuższa sesja SSO w IdP (max 24 h). */
    private const CZAS_ZYCIA_SEKUND = 86400;

    public static function zapamietaj(string $sid, string $idSesjiLokalnej): void
    {
        $identyfikatory = self::odczytaj($sid);

        if (! in_array($idSesjiLokalnej, $identyfikatory, true)) {
            $identyfikatory[] = $idSesjiLokalnej;
        }

        Cache::put(self::klucz($sid), $identyfikatory, self::CZAS_ZYCIA_SEKUND);
    }

    /**
     * Kasuje wszystkie sesje lokalne powiązane z danym `sid`.
     *
     * @return int liczba skasowanych sesji
     */
    public static function zakoncz(string $sid): int
    {
        $identyfikatory = self::odczytaj($sid);
        $uchwyt = Session::getHandler();
        $skasowane = 0;

        foreach ($identyfikatory as $id) {
            if ($uchwyt->destroy($id)) {
                $skasowane++;
            }
        }

        // UNIEWAŻNIENIE PO `sid`, NIEZALEŻNE OD IDENTYFIKATORA SESJI.
        //
        // BLK-22, zmierzone: `RejestrSesji` zapamiętuje POPRAWNIE ten
        // identyfikator sesji, który klient dostał przy logowaniu — ale
        // identyfikator ROTUJE przy kolejnych żądaniach (zmierzone przez
        // `Set-Cookie` z dwóch kolejnych odpowiedzi: A ≠ B przy zachowanej
        // tożsamości). Zapamiętany identyfikator starzeje się więc przy
        // pierwszym ruchu użytkownika, a wylogowanie kasuje wpis, którego
        // nikt już nie używa. Żywa sesja zostaje nietknięta i konsument
        // serwuje dalej — dokładnie defekt wzorca BLK-22.
        //
        // Kasowanie po identyfikatorach ZOSTAJE (jest tanie i działa, dopóki
        // identyfikator nie zrotował), ale przestaje być jedynym mechanizmem.
        // Znacznik unieważnienia wiąże się z `sid` — tożsamością, którą IdP
        // faktycznie zna i która NIE rotuje. Sprawdza go `OdswiezanieSesji`
        // przy każdym żądaniu.
        self::zapiszUniewaznienie($sid);

        Cache::forget(self::klucz($sid));

        // SPRZĄTANIE NA ŚCIEŻCE MUTUJĄCEJ — tutaj, nie w odczycie.
        //
        // `sprzataj()` bez wywołującego byłoby R6A-11 od nowa: metoda istnieje,
        // testy zielone, a na żywym systemie znaczniki rosną bez końca. Wołamy
        // ją przy wylogowaniu, bo to jedyna ścieżka, która i tak mutuje tę
        // tabelę — i jest rzadka, więc koszt jest znikomy.
        //
        // Wynik jest ODBIERANY (nie `sprzataj();` samo z siebie), żeby dało się
        // go zalogować, gdy zajdzie potrzeba, i żeby nikt nie wziął ciszy za
        // „nie było czego sprzątać".
        $wysprzatane = self::sprzataj();
        unset($wysprzatane);

        return $skasowane;
    }

    /**
     * @return list<string>
     */
    public static function odczytaj(string $sid): array
    {
        return Typy::listaNapisow(Cache::get(self::klucz($sid), []));
    }

    /**
     * Czy sesja SSO o tym `sid` została unieważniona.
     *
     * Pytanie zadawane przy KAŻDYM żądaniu, bo tylko ono jest odporne na
     * rotację identyfikatora sesji frameworka.
     */
    public static function uniewazniona(string $sid): bool
    {
        if ($sid === '') {
            return false;
        }

        // ROZSTRZYGA OBECNOŚĆ WIERSZA, NIGDY JEGO WIEK (`D-EKO-012`, kontrakt §4.5a).
        //
        // Do 09.08 stało tu `->where('wygasa_at', '>', now())`. Znacznik WCIĄŻ
        // OBECNY w bazie przestawał wtedy blokować po progu — czyli upływ czasu
        // PRZYZNAWAŁ dostęp osobie wylogowanej. Zmierzone, nie wyczytane:
        // wiersz istniał, a metoda zwracała `false`.
        //
        // Czas życia jest progiem SPRZĄTANIA i mieszka wyłącznie w `sprzataj()`.
        // Zapytanie rozstrzygające o dostępie nie ma prawa go widzieć — inaczej
        // wystarczy jedna korekta zegara, żeby dowód wylogowania przestał
        // działać, mimo że nikt go nie usunął.
        //
        // @dowod: WygasnieciePozwolenieTest — „znacznik po terminie NADAL blokuje”
        return DB::table('uniewaznione_sesje')
            ->where('sid_skrot', hash('sha256', $sid))
            ->exists();
    }

    /**
     * Sprząta znaczniki, których próg minął. **Ścieżka MUTUJĄCA, nie odczyt.**
     *
     * Wiek ma ODBIERAĆ znaczenie znacznikowi, nigdy PRZYZNAWAĆ dostęp — dlatego
     * usuwanie żyje tutaj, a nie w `uniewazniona()`. Bezpieczeństwo tej eksmisji
     * stoi na jednym warunku i jest on **sprawdzany kontrolą**, nie zakładany:
     * próg (`CZAS_ZYCIA_SEKUND` / SSO Session Max) musi być **nie krótszy niż
     * najdłuższa możliwa sesja lokalna**. Inaczej sesja przeżywa własny znacznik
     * i sprzątaczka sama odblokowuje — to jest dokładnie ta strona defektu,
     * której brakuje kontom (`SessionStore` nie sprawdza wieku rekordu wcale).
     *
     * @return int liczba znaczników, które NAPRAWDĘ zniknęły
     *
     * @dowod: WygasnieciePozwolenieTest — „sprzątanie usuwa PO progu i mówi ILE”
     */
    public static function sprzataj(): int
    {
        $doUsuniecia = DB::table('uniewaznione_sesje')
            ->where('wygasa_at', '<=', CarbonImmutable::now())
            ->pluck('sid_skrot')
            ->map(static fn (mixed $s): string => Typy::napis($s))
            ->all();

        if ($doUsuniecia === []) {
            return 0;
        }

        DB::table('uniewaznione_sesje')->whereIn('sid_skrot', $doUsuniecia)->delete();

        // „Polecenie się wykonało" ≠ „wiersz zniknął". Liczymy to, co NAPRAWDĘ
        // zniknęło, pytając bazę drugą drogą — nie ufamy liczbie z `delete()`.
        $pozostale = DB::table('uniewaznione_sesje')->whereIn('sid_skrot', $doUsuniecia)->count();

        return count($doUsuniecia) - $pozostale;
    }

    /** Próg sprzątania w sekundach — jedno miejsce, żeby dało się go porównać z życiem sesji. */
    public static function progSprzataniaSekund(): int
    {
        return self::oknoUniewaznieniaSekund();
    }

    /**
     * Zapis znacznika — TRWALE, z progiem liczonym od SSO Session Max.
     *
     * Próg to NIE czas życia access tokenu (600 s). Refresh token żyje dłużej,
     * więc znacznik wygasający przed nim wpuszczałby zablokowanego z powrotem.
     * Wartość zapisujemy W WIERSZU, żeby sprzątanie używało tego samego progu,
     * co zapis — inaczej sprzątaczka sama odblokowuje.
     */
    private static function zapiszUniewaznienie(string $sid): void
    {
        $teraz = CarbonImmutable::now();

        DB::table('uniewaznione_sesje')->upsert([[
            'sid_skrot' => hash('sha256', $sid),
            'uniewazniona_at' => $teraz,
            'wygasa_at' => $teraz->addSeconds(self::oknoUniewaznieniaSekund()),
            'powod' => 'backchannel-logout',
        ]], ['sid_skrot'], ['uniewazniona_at', 'wygasa_at', 'powod']);
    }

    /**
     * SSO Session Max realmu — najdłuższy byt, który znacznik unieważnia.
     *
     * Domyślne 24 h odpowiada `CZAS_ZYCIA_SEKUND` rejestru; wartość realmu
     * wchodzi konfiguracją, gdy `konta` ją potwierdzi (kontrakt §6).
     */
    private static function oknoUniewaznieniaSekund(): int
    {
        // BEZ WARTOŚCI DOMYŚLNEJ — brak konfiguracji kończy się WYJĄTKIEM.
        //
        // Do 09.08 stało tu `max(self::CZAS_ZYCIA_SEKUND, config(...) ?? 86400)`,
        // czyli dwie wady naraz:
        //
        //  1. CICHY DEFAULT z własnej stałej — drugi opis wartości realmu
        //     (klasa P3). Dwa opisy tej samej rzeczy rozjeżdżają się po cichu.
        //  2. `max()` sprawiał, że konfiguracja MNIEJSZA nie miała ŻADNEGO
        //     skutku. Zmierzone: config=3600 → próg 86400. Ustawienie było
        //     przyjmowane i ignorowane — czyli wyglądało na zastosowane.
        //
        // Wartość jest KONTRAKTOWA (patrz `config/konta.php`), nie domyślna:
        // ma być przepisana świadomie, a jej brak ma krzyczeć.
        $prog = config('konta.sso_session_max_s');

        if (! is_int($prog) || $prog <= 0) {
            throw new RuntimeException(
                'Brak kontraktowej wartości `konta.sso_session_max_s` (jest: '.var_export($prog, true).'). '.
                'Próg sprzątania znaczników unieważnienia NIE MA wartości domyślnej — cicha '.
                'wartość zastępcza byłaby drugim opisem konfiguracji realmu i rozjechałaby się '.
                'z nią bez śladu. Przepisz wartość z kontraktu.'
            );
        }

        return $prog;
    }

    private static function klucz(string $sid): string
    {
        return 'konta:sid:'.hash('sha256', $sid);
    }
}
