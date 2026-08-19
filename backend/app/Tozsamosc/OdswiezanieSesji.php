<?php

declare(strict_types=1);

namespace App\Tozsamosc;

use App\Wsparcie\Typy;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Przeliczanie ról przy odświeżeniu access tokenu.
 *
 * Decyzja przekrojowa B8 (standard ekosystemu, 07.08.2026):
 *
 *   Ról NIE WOLNO zamrażać na całą sesję. Przeliczamy je najpóźniej przy
 *   odświeżeniu access tokenu — czyli w oknie kontraktu (600 s). Kosztuje ZERO
 *   dodatkowych żądań, bo odświeżenie i tak podtrzymuje sesję.
 *
 * Stan przed tą zmianą (znalezisko B8 z kontroli krzyżowej huba): role były
 * zapisywane do sesji przy logowaniu i nie zmieniały się przez 120 minut,
 * a sesja odnawiała się ruchem. Dla aktywnego użytkownika odebranie roli
 * w Keycloaku **nie działało nigdy**. W systemie, w którym rola otwiera dostęp
 * do kartotek pacjentów, to jest wada bezpieczeństwa, nie niedogodność.
 *
 * Podział ścieżek jest celowy:
 *   · ZMIANA ROLI  → tędy, najpóźniej w oknie tokenu;
 *   · BLOKADA KONTA → back-channel logout po `sid`, natychmiast (`RejestrSesji`);
 *   · sub-minuta    → wyłącznie dla konkretnej wrażliwej roli, gdyby zaszła
 *                     potrzeba: cache 60 s TEJ roli, nigdy globalnie.
 *
 * Świadomie NIE odpytujemy IdP przy każdym żądaniu. To byłby dokładnie ten
 * ruch, który zamknęliśmy bramką częstotliwości w `KontaOidc::jwksDlaKid()`.
 */
final class OdswiezanieSesji
{
    /** Ile sekund przed wygaśnięciem access tokenu sięgamy po nowy. */
    private const MARGINES_S = 30;

    public function __construct(private readonly KontaOidc $oidc) {}

    /**
     * Aktualny stan konta — z odświeżeniem, jeśli access token dobiegł końca.
     *
     * Zwraca `null`, gdy sesji nie ma albo gdy IdP odmówił odświeżenia
     * (konto zablokowane, sesja unieważniona, refresh token cofnięty).
     * Odmowa MUSI kończyć sesję: dalsza praca na zamrożonych rolach byłaby
     * dokładnie tym, czego ta klasa zabrania.
     *
     * @return array<string, mixed>|null
     */
    public function stanKonta(Request $request): ?array
    {
        // WEJŚCIEM JEST ISTNIEJĄCA TOŻSAMOŚĆ, albo nic.
        //
        // `odczytaj()` zwraca `null`, gdy w magazynie nie ma tożsamości —
        // i wtedy nie ma czym wywołać `zaktualizuj()`, bo ta wymaga wartości
        // typu `TozsamoscSesji`. Ścieżka „brak rekordu → utwórz" jest tu
        // TRUDNIEJSZA, a nie „zabroniona warunkiem".
        // @dowod: R6A-3 — „NIEWYWOŁYWALNA" obalone pomiarem; obejście prowadzi
        //         przez publiczną fabrykę `TozsamoscSesji::zMagazynu()`.
        //
        // Poprzednia wersja czytała tablicę i sprawdzała `if` — czyli miała
        // dostęp do zapisu niezależnie od wyniku sprawdzenia. To wystarcza
        // za uzasadnienie: wymóg §2 mówi o STRUKTURZE, nie o tym, czy dziurę
        // ktoś już wykorzystał.
        //
        // NIE jest natomiast zmierzone, że odświeżanie tożsamość odtwarzało.
        // Taki wniosek postawiono z migawek i OBALONO pomiarem kontrolnym
        // (po tej zmianie liczby identyczne). Noga 1 — NIEROZSTRZYGNIĘTA.
        $tozsamosc = SesjaKonta::odczytaj($request);

        if ($tozsamosc === null) {
            return null;
        }

        // BLK-22: sesja SSO mogła zostać unieważniona back-channel logoutem
        // PO tym, jak identyfikator sesji frameworka zrotował. Pytamy o `sid`,
        // czyli tożsamość, którą zna IdP i która NIE rotuje.
        if (RejestrSesji::uniewazniona($tozsamosc->sid())) {
            SesjaKonta::zakoncz($request);

            return null;
        }

        if (! $this->wymagaOdswiezenia($tozsamosc)) {
            return $tozsamosc->dane;
        }

        if ($tozsamosc->refreshToken() === '') {
            SesjaKonta::zakoncz($request);

            return null;
        }

        $odpowiedz = $this->oidc->odswiezTokeny($tozsamosc->refreshToken());

        if ($odpowiedz['status'] !== 200) {
            SesjaKonta::zakoncz($request);

            return null;
        }

        return $this->przelicz($request, $tozsamosc, $odpowiedz['body']);
    }

    private function wymagaOdswiezenia(TozsamoscSesji $tozsamosc): bool
    {
        $wygasa = $tozsamosc->accessExp();

        // Brak zapisanego `exp` traktujemy jak „już wygasło": lepiej odświeżyć
        // niepotrzebnie niż autoryzować na podstawie nieznanego wieku tokenu.
        if ($wygasa === 0) {
            return true;
        }

        return CarbonImmutable::now()->getTimestamp() + self::MARGINES_S >= $wygasa;
    }

    /**
     * Nowe role z NOWEGO access tokenu — nie z sesji.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>|null
     */
    private function przelicz(Request $request, TozsamoscSesji $obecna, array $body): ?array
    {
        $accessToken = Typy::napis($body['access_token'] ?? null);

        // Wymagania z KONFIGURACJI — patrz komentarz przy `zTokenu`
        // (znalezisko „szóste piętro"). Odświeżanie miało tę samą wadę co
        // logowanie: składało wymagania na miejscu, razem z materiałem klucza.
        $wynik = RoszczeniaZweryfikowane::zAccessTokenu($accessToken, $this->oidc);

        if (! $wynik['ok']) {
            SesjaKonta::zakoncz($request);

            return null;
        }

        $roszczenia = $wynik['roszczenia'];

        // Wiązanie po `sub` (CLAUDE.md §2). Gdyby IdP zwrócił token innego
        // podmiotu, kończymy sesję zamiast po cichu podmienić tożsamość.
        //
        // To sprawdzenie zostaje TUTAJ, mimo że `zOdswiezonymi()` i tak `sub`
        // nie rusza — bo tu chodzi o co innego: token innego podmiotu znaczy,
        // że coś jest nie tak z sesją, i wtedy właściwą odpowiedzią jest jej
        // ZAKOŃCZENIE, a nie ciche zachowanie starej tożsamości.
        if ($roszczenia->napis('sub') !== $obecna->sub()) {
            SesjaKonta::zakoncz($request);

            return null;
        }

        // AKTUALIZACJA istniejącej tożsamości — `$obecna` jest dowodem, że
        // tożsamość istniała.
        //
        // Do 19.08 stało tu `zPodmienionymi($zmiany)` z tablicą budowaną
        // W TYM MIEJSCU — czyli droga R11-2: publiczna metoda przyjmująca
        // dowolne pola pozwalała podmienić `sub` wartością z żądania,
        // a warstwy 2 i 4 wąskiego gardła skanowały wyłącznie `zaloz`.
        // Teraz odświeżenie NIE MA parametru, którym dałoby się ruszyć
        // tożsamość: role i termin liczy `zOdswiezonymi` ze zweryfikowanych
        // roszczeń, reszta pochodzi z `$obecna`.
        // @dowod: R6A-3 — wcześniejsze „bez niej nie da się napisać" obalone.
        $nowa = $obecna->zOdswiezonymi(
            $roszczenia,
            isset($body['refresh_token']) ? Typy::napis($body['refresh_token']) : null,
        );
        SesjaKonta::zaktualizuj($request, $nowa);

        return $nowa->dane;
    }
}
