<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Tozsamosc\Bramki;
use App\Tozsamosc\KontaOidc;
use App\Tozsamosc\RejestrSesji;
use App\Tozsamosc\WalidatorTokenu;
use App\Wsparcie\Typy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function __construct(private readonly KontaOidc $oidc) {}

    public function zaloguj(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $nonce = Str::random(40);
        $weryfikator = Str::random(64);

        $request->session()->put('konta.state', $state);
        $request->session()->put('konta.nonce', $nonce);
        $request->session()->put('konta.pkce', $weryfikator);

        return redirect()->away($this->oidc->adresLogowania($state, $nonce, $weryfikator));
    }

    public function powrot(Request $request): RedirectResponse|JsonResponse
    {
        // Wejście bez `code` to normalna sytuacja: tak wraca RP-initiated logout,
        // bo `post_logout_redirect_uri` musi być jednym z `redirectUris` klienta.
        if (! $request->filled('code')) {
            return redirect('/');
        }

        $state = $request->session()->pull('konta.state');
        $nonce = $request->session()->pull('konta.nonce');
        $weryfikator = $request->session()->pull('konta.pkce');

        if (! is_string($state) || ! hash_equals($state, Typy::napis($request->query('state')))) {
            return response()->json(['ok' => false, 'blad' => 'state'], 400);
        }

        $tokeny = $this->oidc->wymienKod(Typy::napis($request->query('code')), Typy::napis($weryfikator));

        if ($tokeny['status'] !== 200) {
            return response()->json(['ok' => false, 'blad' => 'wymiana_kodu', 'status' => $tokeny['status']], 401);
        }

        $jwks = $this->oidc->jwks();

        // ID token: dowód TOŻSAMOŚCI. `aud` = client_id, `nonce` z naszej sesji.
        $idToken = Typy::napis($tokeny['body']['id_token'] ?? null);
        $wynikId = WalidatorTokenu::sprawdz($idToken, [
            'issuer' => $this->oidc->issuerPubliczny(),
            'jwks' => $jwks,
            'audience' => $this->oidc->clientId(),
            'typ' => 'ID',
            'nonce' => $nonce,
            'tolerancja' => $this->oidc->tolerancjaZegara(),
        ]);

        if (! $wynikId['ok']) {
            return response()->json([
                'ok' => false, 'blad' => 'id_token', 'nieudane' => $wynikId['nieudane'],
                'kontrole' => $wynikId['kontrole'],
            ], 401);
        }

        // ACCESS token: dowód UPRAWNIEŃ. Role są WYŁĄCZNIE tutaj (kontrakt §2b).
        $accessToken = Typy::napis($tokeny['body']['access_token'] ?? null);
        $wynikAccess = WalidatorTokenu::sprawdz($accessToken, [
            'issuer' => $this->oidc->issuerPubliczny(),
            'jwks' => $jwks,
            'audience' => $this->oidc->wymaganaAudiencja(),
            'typ' => 'Bearer',
            'tolerancja' => $this->oidc->tolerancjaZegara(),
        ]);

        if (! $wynikAccess['ok']) {
            return response()->json([
                'ok' => false, 'blad' => 'access_token', 'nieudane' => $wynikAccess['nieudane'],
                'kontrole' => $wynikAccess['kontrole'],
            ], 401);
        }

        $claimsId = $wynikId['claims'];
        $sid = isset($claimsId['sid']) ? Typy::napis($claimsId['sid']) : null;

        // Wiązanie konta lokalnego po `sub`, NIGDY po e-mailu (CLAUDE.md §2).
        $request->session()->regenerate();
        $request->session()->put('konta', [
            'sub' => Typy::napis($claimsId['sub'] ?? null),
            'sid' => $sid,
            'login' => isset($claimsId['preferred_username']) ? Typy::napis($claimsId['preferred_username']) : null,
            'email' => isset($claimsId['email']) ? Typy::napis($claimsId['email']) : null,
            'email_potwierdzony' => Typy::prawda($claimsId['email_verified'] ?? null),
            'role' => Bramki::roleZAccessTokenu($wynikAccess['claims']),
            'id_token' => $idToken,
        ]);

        if ($sid !== null) {
            RejestrSesji::zapamietaj($sid, $request->session()->getId());
        }

        return redirect('/');
    }

    public function wyloguj(Request $request): RedirectResponse
    {
        $konta = Typy::mapa($request->session()->get('konta', []));
        $idToken = Typy::napis($konta['id_token'] ?? null);

        $request->session()->flush();
        $request->session()->invalidate();

        if ($idToken === '') {
            return redirect('/');
        }

        // Adres powrotu MUSI być jednym z `redirectUris` klienta (kontrakt §3),
        // dlatego wskazujemy własny callback — obsługuje wejście bez `code`.
        return redirect()->away($this->oidc->adresWylogowania($idToken, $this->oidc->redirectUri()));
    }

    /** Kim jestem i co mi wolno — jedyne źródło prawdy dla frontendu. */
    public function ja(Request $request): JsonResponse
    {
        $konta = $request->session()->get('konta');

        if (! is_array($konta)) {
            return response()->json(['zalogowany' => false], 401);
        }

        $role = Typy::listaNapisow($konta['role'] ?? null);

        return response()->json([
            'zalogowany' => true,
            'sub' => $konta['sub'] ?? null,
            'login' => $konta['login'] ?? null,
            'email' => $konta['email'] ?? null,
            // Pusta lista ról to POPRAWNY stan konta, nie błąd (kontrakt §2).
            'role' => $role,
            'bramki' => Bramki::dlaRol($role),
        ]);
    }
}
