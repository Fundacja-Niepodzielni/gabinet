<?php

declare(strict_types=1);

namespace App\Tozsamosc;

use App\Wsparcie\Typy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

/**
 * Mapa `sid` (identyfikator sesji SSO w IdP) → identyfikatory sesji lokalnych.
 *
 * Bez niej nie da się obsłużyć back-channel logout: żądanie od IdP przychodzi
 * BEZ ciasteczka użytkownika, więc `sid` jest jedynym kluczem, po którym
 * potrafimy znaleźć sesję do skasowania (kontrakt §4.1 pkt 1 i §4.5).
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
        Cache::put(self::kluczUniewaznienia($sid), CarbonImmutable::now()->getTimestamp(), self::CZAS_ZYCIA_SEKUND);

        Cache::forget(self::klucz($sid));

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
        return $sid !== '' && Cache::has(self::kluczUniewaznienia($sid));
    }

    private static function klucz(string $sid): string
    {
        return 'konta:sid:'.hash('sha256', $sid);
    }

    private static function kluczUniewaznienia(string $sid): string
    {
        return 'konta:uniewazniony:'.hash('sha256', $sid);
    }
}
