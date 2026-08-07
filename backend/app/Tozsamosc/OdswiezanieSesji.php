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
        $konta = Typy::mapa($request->session()->get('konta'));

        if (! isset($konta['sub']) || Typy::napis($konta['sub']) === '') {
            return null;
        }

        if (! $this->wymagaOdswiezenia($konta)) {
            return $konta;
        }

        $refresh = Typy::napis($konta['refresh_token'] ?? null);

        if ($refresh === '') {
            // Sesja sprzed wprowadzenia odświeżania albo IdP nie wydał
            // refresh tokenu. Nie ma jak potwierdzić ról, więc nie udajemy,
            // że są aktualne.
            $this->zakoncz($request);

            return null;
        }

        $odpowiedz = $this->oidc->odswiezTokeny($refresh);

        if ($odpowiedz['status'] !== 200) {
            $this->zakoncz($request);

            return null;
        }

        return $this->przelicz($request, $konta, $odpowiedz['body']);
    }

    /**
     * @param  array<string, mixed>  $konta
     */
    private function wymagaOdswiezenia(array $konta): bool
    {
        $wygasa = Typy::liczba($konta['access_exp'] ?? null);

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
     * @param  array<string, mixed>  $konta
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>|null
     */
    private function przelicz(Request $request, array $konta, array $body): ?array
    {
        $accessToken = Typy::napis($body['access_token'] ?? null);

        $wynik = WalidatorTokenu::sprawdz($accessToken, [
            'issuer' => $this->oidc->issuerPubliczny(),
            'jwks' => $this->oidc->jwksDlaKid(WalidatorTokenu::kidNiezweryfikowany($accessToken)),
            'audience' => $this->oidc->wymaganaAudiencja(),
            'tolerancja' => $this->oidc->tolerancjaZegara(),
        ]);

        if (! $wynik['ok']) {
            $this->zakoncz($request);

            return null;
        }

        $claims = $wynik['claims'];

        // Wiązanie po `sub` (CLAUDE.md §2). Gdyby IdP zwrócił token innego
        // podmiotu, kończymy sesję zamiast po cichu podmienić tożsamość.
        if (Typy::napis($claims['sub'] ?? null) !== Typy::napis($konta['sub'])) {
            $this->zakoncz($request);

            return null;
        }

        $surowe = Bramki::roleZAccessTokenu($claims);

        $konta['role_surowe'] = $surowe;
        $konta['role'] = Bramki::roleAutoryzujace($surowe);
        $konta['markery'] = Bramki::markery($surowe);
        $konta['access_exp'] = Typy::liczba($claims['exp'] ?? null);

        if (isset($body['refresh_token'])) {
            $konta['refresh_token'] = Typy::napis($body['refresh_token']);
        }

        $request->session()->put('konta', $konta);

        return $konta;
    }

    private function zakoncz(Request $request): void
    {
        $request->session()->flush();
        $request->session()->regenerate();
    }
}
