<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Tozsamosc\Bramki;
use App\Tozsamosc\KontaOidc;
use App\Tozsamosc\OdswiezanieSesji;
use App\Tozsamosc\RejestrSesji;
use App\Tozsamosc\RoszczeniaZweryfikowane;
use App\Tozsamosc\SesjaKonta;
use App\Tozsamosc\WalidatorTokenu;
use App\Wsparcie\Typy;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Logowanie przez Konta Niepodzielni (OIDC, klient poufny `gabinet`).
 *
 * CLAUDE.md §2: w tym systemie NIE MA własnego ekranu logowania ani własnych
 * haseł. Ten kontroler wyłącznie przekierowuje do IdP i przyjmuje powrót.
 *
 * Cztery rzeczy, których żadna biblioteka nie zrobi za nas (kontrakt §4.1):
 *   1. zapisanie `sid` z ID tokenu — bez niego nie ma back-channel logout,
 *   2. walidacja `aud` ACCESS tokenu — `azp` jej nie zastępuje,
 *   3. endpoint back-channel logout bez CSRF i bez sesji,
 *   4. RP-initiated logout z `id_token_hint`.
 */
final class LogowanieController extends Controller
{
    public function __construct(
        private readonly KontaOidc $oidc,
        private readonly OdswiezanieSesji $odswiezanie,
    ) {}

    public function zaloguj(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $nonce = Str::random(40);
        $weryfikator = Str::random(64);

        // UWAGA: klucze przepływu MUSZĄ być rozłączne z kluczem tożsamości.
        // Wersja pierwotna zapisywała je jako `konta.state` / `konta.nonce`,
        // a Laravel składa zapis z kropką w TABLICĘ pod kluczem `konta` — czyli
        // pod tym samym, który znaczy „zalogowany". Skutek: samo wejście na
        // /auth/login sprawiało, że /auth/ja odpowiadał „zalogowany: true"
        // (z pustą listą ról). Znalazł to test pozytywny pełnej ścieżki.
        $request->session()->put('oidc_przeplyw', [
            'state' => $state,
            'nonce' => $nonce,
            'pkce' => $weryfikator,
        ]);

        return redirect()->away($this->oidc->adresLogowania($state, $nonce, $weryfikator));
    }

    public function powrot(Request $request): RedirectResponse|JsonResponse
    {
        // Wejście bez `code` to normalna sytuacja: tak wraca RP-initiated logout,
        // bo `post_logout_redirect_uri` musi być jednym z `redirectUris` klienta.
        if (! $request->filled('code')) {
            return redirect('/');
        }

        $przeplyw = Typy::mapa($request->session()->pull('oidc_przeplyw'));
        $state = $przeplyw['state'] ?? null;
        $nonce = $przeplyw['nonce'] ?? null;
        $weryfikator = $przeplyw['pkce'] ?? null;

        if (! is_string($state) || ! hash_equals($state, Typy::napis($request->query('state')))) {
            return response()->json(['ok' => false, 'blad' => 'state'], 400);
        }

        // Komplet danych przepływu MUSI być w sesji. Brak któregokolwiek pola
        // znaczy, że nie ma z czym porównać tokenu — i wtedy jedyną poprawną
        // odpowiedzią jest odmowa, a nie pominięcie kontroli.
        if (! is_string($nonce) || $nonce === '' || ! is_string($weryfikator) || $weryfikator === '') {
            return response()->json(['ok' => false, 'blad' => 'niekompletny_przeplyw'], 400);
        }

        $tokeny = $this->oidc->wymienKod(Typy::napis($request->query('code')), $weryfikator);

        if ($tokeny['status'] !== 200) {
            return response()->json(['ok' => false, 'blad' => 'wymiana_kodu', 'status' => $tokeny['status']], 401);
        }

        $jwks = $this->oidc->jwksDlaKid(
            WalidatorTokenu::kidNiezweryfikowany(Typy::napis($tokeny['body']['id_token'] ?? null))
        );

        // ID token: dowód TOŻSAMOŚCI. `aud` = client_id, `nonce` z naszej sesji.
        $idToken = Typy::napis($tokeny['body']['id_token'] ?? null);
        $wynikId = RoszczeniaZweryfikowane::zTokenu($idToken, [
            'issuer' => $this->oidc->issuerPubliczny(),
            'jwks' => $jwks,
            'audience' => $this->oidc->clientId(),
            'typ' => 'ID',
            'nonce' => $nonce,
            'tolerancja' => $this->oidc->tolerancjaZegara(),
        ]);

        if (! $wynikId['ok']) {
            return $this->odmowa('id_token', $wynikId);
        }

        // ACCESS token: dowód UPRAWNIEŃ. Role są WYŁĄCZNIE tutaj (kontrakt §2b).
        $accessToken = Typy::napis($tokeny['body']['access_token'] ?? null);
        $wynikAccess = RoszczeniaZweryfikowane::zTokenu($accessToken, [
            'issuer' => $this->oidc->issuerPubliczny(),
            'jwks' => $jwks,
            'audience' => $this->oidc->wymaganaAudiencja(),
            'typ' => 'Bearer',
            'tolerancja' => $this->oidc->tolerancjaZegara(),
        ]);

        if (! $wynikAccess['ok']) {
            return $this->odmowa('access_token', $wynikAccess);
        }

        // ⛔ TOŻSAMOŚĆ SKŁADA SIĘ WYŁĄCZNIE ZE ZWERYFIKOWANYCH ROSZCZEŃ.
        //
        // Do 19.08 stała tu TABLICA budowana w kontrolerze — i to była
        // przyczyna R11-1: tablicę wolno wypełnić czymkolwiek, więc
        // `['sub' => $sub]` z `$sub = $request->query('code')` przechodziło
        // całą bramkę. Warstwa 3 milczała SŁUSZNIE, bo `code` jest w kontrakcie
        // OIDC; warstwa 4 nie śledziła zmiennej pośredniej.
        //
        // Teraz mapowanie roszczeń na sesję mieszka w `SesjaKonta` — u jedynego
        // pisarza — a tu zostaje wyłącznie przekazanie dwóch zweryfikowanych
        // obiektów.
        // Kontrakt uzasadnia CZYTANIE `code` (do wymiany na tokeny) — nigdy
        // użycie go jako tożsamości.
        $roszczeniaId = $wynikId['roszczenia'];
        $sid = $roszczeniaId->napisAlbo('sid');

        $request->session()->regenerate();
        SesjaKonta::zaloz(
            $request,
            $roszczeniaId,
            $wynikAccess['roszczenia'],
            isset($tokeny['body']['refresh_token'])
                ? Typy::napis($tokeny['body']['refresh_token'])
                : null,
        );

        if ($sid !== null) {
            RejestrSesji::zapamietaj($sid, $request->session()->getId());
        }

        return redirect('/');
    }

    public function wyloguj(Request $request): RedirectResponse
    {
        $konta = Typy::mapa($request->session()->get(SesjaKonta::KLUCZ, []));
        // Odszyfrowanie tuż przed użyciem. Sesja sprzed tej zmiany trzyma
        // wartość jawną, więc nieudane odszyfrowanie nie może wywrócić
        // wylogowania — bez `id_token_hint` IdP po prostu zapyta użytkownika.
        $idToken = '';

        try {
            $idToken = Crypt::decryptString(Typy::napis($konta['id_token'] ?? null));
        } catch (DecryptException) {
            $idToken = '';
        }

        // R6A-12: kasowanie tożsamości przez FASADĘ, nie wprost w kontrolerze.
        // Zostawało to poza wąskim gardłem §2 wyłącznie dlatego, że nikt nie
        // policzył ścieżek KASUJĄCYCH — liczono tylko piszące.
        SesjaKonta::wyloguj($request);

        if ($idToken === '') {
            return redirect('/');
        }

        // Adres powrotu MUSI być jednym z `redirectUris` klienta (kontrakt §3),
        // dlatego wskazujemy własny callback — obsługuje wejście bez `code`.
        return redirect()->away($this->oidc->adresWylogowania($idToken, $this->oidc->redirectUri()));
    }

    /**
     * Odmowa uwierzytelnienia.
     *
     * Mapa kontroli jest bezcenna przy diagnozie i JEDNOCZEŚNIE jest gotowym
     * oracle'em dla kogoś, kto stroi podrobiony token: odpowiedź mówi wprost,
     * która kontrola padła, a która przeszła. Dlatego szczegóły wychodzą
     * WYŁĄCZNIE poza produkcją; na produkcji zostaje sam powód ogólny,
     * a komplet idzie do logu.
     *
     * Żąda WYŁĄCZNIE tego, co loguje — `kontrole` i `nieudane`. Wcześniej
     * żądała też `claims`, czyli roszczeń, których przy ODMOWIE z definicji
     * nie ma i których ta metoda nigdy nie czytała. Szerszy wymóg niż użycie
     * zmusza wołającego do niesienia pola bez powodu.
     *
     * @param  array{kontrole: array<string, string>, nieudane: list<string>}  $wynik
     */
    private function odmowa(string $etap, array $wynik): JsonResponse
    {
        Log::warning('Odrzucono token przy logowaniu.', [
            'etap' => $etap,
            'nieudane' => $wynik['nieudane'],
            'kontrole' => $wynik['kontrole'],
        ]);

        $tresc = ['ok' => false, 'blad' => $etap];

        if (app()->environment('local', 'testing')) {
            $tresc['nieudane'] = $wynik['nieudane'];
            $tresc['kontrole'] = $wynik['kontrole'];
        }

        return response()->json($tresc, 401);
    }

    /** Kim jestem i co mi wolno — jedyne źródło prawdy dla frontendu. */
    public function ja(Request $request): JsonResponse
    {
        // B8: role NIE są brane wprost z sesji. `OdswiezanieSesji` przelicza
        // je z NOWEGO access tokenu, jeśli stary dobiegł końca — dzięki temu
        // odebranie roli w Keycloaku działa najpóźniej w oknie tokenu, a nie
        // dopiero przy następnym logowaniu. Odmowa IdP kończy sesję.
        $konta = $this->odswiezanie->stanKonta($request);

        // Sam fakt, że pod kluczem coś leży, nie znaczy „zalogowany".
        // Rozstrzyga obecność `sub` — tożsamości z ID tokenu.
        if ($konta === null || Typy::napis($konta['sub'] ?? null) === '') {
            return response()->json(['zalogowany' => false], 401);
        }

        // `role` to WYŁĄCZNIE role autoryzujące. Markery techniczne idą osobno,
        // żeby żaden konsument tego API nie mógł zbudować logiki na „ma jakąś
        // rolę, więc ma dostęp".
        $role = Typy::listaNapisow($konta['role'] ?? null);
        $markery = Typy::listaNapisow($konta['markery'] ?? null);

        return response()->json([
            'zalogowany' => true,
            'sub' => $konta['sub'] ?? null,
            'login' => $konta['login'] ?? null,
            'email' => $konta['email'] ?? null,
            // Pusta lista ról to POPRAWNY stan konta, nie błąd (kontrakt §2).
            'role' => $role,
            'markery' => $markery,
            'wymaga_2fa' => Bramki::wymaga2fa($markery),
            'bramki' => Bramki::dlaRol($role),
        ]);
    }
}
