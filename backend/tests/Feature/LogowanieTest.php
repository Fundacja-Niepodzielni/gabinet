<?php

declare(strict_types=1);

use App\Tozsamosc\KontaOidc;
use App\Wsparcie\Typy;
use Illuminate\Support\Facades\Http;
use Tests\Wsparcie\FabrykaTokenow;

const IDP = 'https://idp.test/realms/niepodzielni';

/**
 * Podstawia atrapę IdP: discovery + JWKS z klucza wygenerowanego w teście.
 * Dzięki temu cała warstwa `KontaOidc` działa naprawdę (cache, przepisywanie
 * endpointów, kontrola issuera) — atrapowany jest wyłącznie ruch sieciowy.
 */
/**
 * @param  array<string, mixed>  $nadpisaniaDiscovery
 */
function udawajIdp(array $nadpisaniaDiscovery = []): void
{
    config([
        'konta.issuer_publiczny' => IDP,
        'konta.issuer_wewnetrzny' => IDP,
        'konta.client_id' => 'gabinet',
        'konta.wymagana_audiencja' => 'gabinet',
        'konta.redirect_uri' => 'http://localhost/auth/callback',
        'konta.tolerancja_zegara' => 30,
    ]);

    $discovery = array_merge([
        'issuer' => IDP,
        'authorization_endpoint' => IDP.'/protocol/openid-connect/auth',
        'token_endpoint' => IDP.'/protocol/openid-connect/token',
        'jwks_uri' => IDP.'/protocol/openid-connect/certs',
        'end_session_endpoint' => IDP.'/protocol/openid-connect/logout',
        'userinfo_endpoint' => IDP.'/protocol/openid-connect/userinfo',
    ], $nadpisaniaDiscovery);

    Http::fake([
        'idp.test/*/.well-known/openid-configuration' => Http::response($discovery),
        'idp.test/*/protocol/openid-connect/certs' => Http::response(FabrykaTokenow::jwks()),
    ]);
}

it('odpowiada 401 na /auth/ja bez zalogowania', function (): void {
    $this->getJson('/auth/ja')
        ->assertStatus(401)
        ->assertJsonPath('zalogowany', false);
});

it('przekierowuje /auth/login do IdP z PKCE, state i nonce', function (): void {
    udawajIdp();

    $odpowiedz = $this->get('/auth/login');
    $odpowiedz->assertRedirect();

    $cel = (string) $odpowiedz->headers->get('Location');
    parse_str((string) parse_url($cel, PHP_URL_QUERY), $parametry);

    expect($cel)->toStartWith(IDP.'/protocol/openid-connect/auth')
        ->and($parametry['client_id'])->toBe('gabinet')
        ->and($parametry['response_type'])->toBe('code')
        ->and($parametry['code_challenge_method'])->toBe('S256')
        ->and($parametry['code_challenge'])->not->toBeEmpty()
        // `state` i `nonce` muszą trafić do sesji — inaczej powrót z IdP
        // nie ma się do czego porównać.
        ->and($parametry['state'])->toBe(session('oidc_przeplyw.state'))
        ->and($parametry['nonce'])->toBe(session('oidc_przeplyw.nonce'))
        // Klucze przepływu MUSZĄ być rozłączne z kluczem tożsamości:
        // rozpoczęcie logowania nie może uchodzić za zalogowanie.
        ->and(session('konta'))->toBeNull();
});

it('odrzuca powrót z IdP z niezgodnym state', function (): void {
    udawajIdp();

    $this->withSession(['oidc_przeplyw' => ['state' => 'prawdziwy', 'pkce' => 'x']])
        ->get('/auth/callback?code=abc&state=podstawiony')
        ->assertStatus(400)
        ->assertJsonPath('blad', 'state');
});

it('przepuszcza powrót bez parametru code (tak wraca wylogowanie)', function (): void {
    // Kontrakt §3: `post_logout_redirect_uri` musi być jednym z `redirectUris`,
    // więc IdP odsyła użytkownika na nasz callback — bez `code`.
    udawajIdp();

    $this->get('/auth/callback')->assertRedirect('/');
});

it('przerywa, gdy discovery zwraca innego issuera niż publiczny', function (): void {
    // Bez tej kontroli walidacja `iss` w tokenach byłaby fikcją (kontrakt §3a).
    // Pytamy usługę wprost, a nie przez trasę HTTP: chcemy dowodu na TREŚĆ
    // błędu, a nie na kod 500, który potrafi wyprodukować dowolna awaria.
    udawajIdp(['issuer' => 'https://obcy.test/realms/niepodzielni']);

    expect(fn () => app(KontaOidc::class)->metadane())
        ->toThrow(RuntimeException::class, 'discovery: issuer=https://obcy.test/realms/niepodzielni');
});

it('przeprowadza pełne logowanie: kod → tokeny → sesja → role → bramki', function (): void {
    // POZYTYW dla całej ścieżki HTTP. Bez niego mamy komplet testów
    // negatywnych i żadnego dowodu, że szczęśliwa ścieżka w ogóle działa —
    // a to jest odwrotność reguły WYTYCZNE §3.
    udawajIdp();

    $idToken = FabrykaTokenow::podpisz(FabrykaTokenow::claimsId([
        'iss' => IDP,
        'nonce' => 'nonce-z-sesji',
    ]));

    $accessToken = FabrykaTokenow::podpisz(FabrykaTokenow::claimsAccess([
        'iss' => IDP,
        'realm_access' => ['roles' => ['psycholog', 'offline_access']],
    ]));

    Http::fake([
        'idp.test/*/.well-known/openid-configuration' => Http::response([
            'issuer' => IDP,
            'authorization_endpoint' => IDP.'/protocol/openid-connect/auth',
            'token_endpoint' => IDP.'/protocol/openid-connect/token',
            'jwks_uri' => IDP.'/protocol/openid-connect/certs',
            'end_session_endpoint' => IDP.'/protocol/openid-connect/logout',
        ]),
        'idp.test/*/protocol/openid-connect/certs' => Http::response(FabrykaTokenow::jwks()),
        'idp.test/*/protocol/openid-connect/token' => Http::response([
            'access_token' => $accessToken,
            'id_token' => $idToken,
            'token_type' => 'Bearer',
            'expires_in' => 600,
        ]),
    ]);

    $this->withSession(['oidc_przeplyw' => [
        'state' => 'state-z-sesji',
        'nonce' => 'nonce-z-sesji',
        'pkce' => 'weryfikator-pkce',
    ]])->get('/auth/callback?code=kod-autoryzacyjny&state=state-z-sesji')
        ->assertRedirect('/');

    // Dowód liczony na WARTOŚCIACH, nie na obecności ekranu:
    $this->getJson('/auth/ja')
        ->assertOk()
        ->assertJsonPath('zalogowany', true)
        ->assertJsonPath('sub', 'sub-abc-123')
        ->assertJsonPath('login', 'test-psycholog')
        ->assertJsonPath('role', ['psycholog', 'offline_access']);

    // Bramki mają kropki w nazwach, więc `assertJsonPath` (notacja kropkowa)
    // by ich nie znalazł — czytamy mapę wprost.
    $bramki = Typy::mapa($this->getJson('/auth/ja')->json('bramki'));

    expect($bramki['panel.specjalisty'])->toBeTrue()
        ->and($bramki['panel.koordynacji'])->toBeFalse()
        ->and($bramki['rozliczenia.akceptuj'])->toBeFalse()
        ->and($bramki['panel.pacjenta'])->toBeFalse();
});

it('nie wpuszcza, gdy access token ma cudzą audiencję', function (): void {
    // Ta sama ścieżka co wyżej, zmieniona DOKŁADNIE w jednym miejscu.
    udawajIdp();

    $idToken = FabrykaTokenow::podpisz(FabrykaTokenow::claimsId(['iss' => IDP, 'nonce' => 'n']));
    $accessToken = FabrykaTokenow::podpisz(FabrykaTokenow::claimsAccess([
        'iss' => IDP,
        'aud' => ['account'],
        'azp' => 'psychon-web',
    ]));

    Http::fake([
        'idp.test/*/.well-known/openid-configuration' => Http::response([
            'issuer' => IDP,
            'authorization_endpoint' => IDP.'/protocol/openid-connect/auth',
            'token_endpoint' => IDP.'/protocol/openid-connect/token',
            'jwks_uri' => IDP.'/protocol/openid-connect/certs',
            'end_session_endpoint' => IDP.'/protocol/openid-connect/logout',
        ]),
        'idp.test/*/protocol/openid-connect/certs' => Http::response(FabrykaTokenow::jwks()),
        'idp.test/*/protocol/openid-connect/token' => Http::response([
            'access_token' => $accessToken,
            'id_token' => $idToken,
        ]),
    ]);

    $this->withSession(['oidc_przeplyw' => ['state' => 's', 'nonce' => 'n', 'pkce' => 'p']])
        ->get('/auth/callback?code=kod&state=s')
        ->assertStatus(401)
        ->assertJsonPath('blad', 'access_token')
        ->assertJsonPath('kontrole.aud', 'fail')
        // Dowód, że odrzucenie nie wynika z rozsypanej walidacji:
        ->assertJsonPath('kontrole.signature', 'ok');

    // Sesja MUSI zostać pusta — odrzucony token nie może zalogować.
    $this->getJson('/auth/ja')->assertStatus(401);
});

it('odrzuca back-channel logout ze śmieciem zamiast tokenu', function (): void {
    udawajIdp();

    $this->postJson('/oidc/backchannel-logout', ['logout_token' => 'nie-jest-jwt'])
        ->assertStatus(400)
        ->assertJsonPath('kontrole.format', 'fail');
});

it('odrzuca ID token podstawiony w miejsce logout tokenu', function (): void {
    // Zapora ze specyfikacji: logout token NIE MOŻE mieć `nonce`, MUSI mieć
    // claim `events`. ID token ma dokładnie odwrotnie.
    udawajIdp();

    $idToken = FabrykaTokenow::podpisz(FabrykaTokenow::claimsId());

    $this->postJson('/oidc/backchannel-logout', ['logout_token' => $idToken])
        ->assertStatus(400)
        ->assertJsonPath('kontrole.no_nonce', 'fail')
        ->assertJsonPath('kontrole.events', 'fail')
        // Dowód, że odrzucenie NIE wynika z rozsypanej walidacji podpisu:
        ->assertJsonPath('kontrole.signature', 'ok')
        ->assertJsonPath('kontrole.iss', 'ok');
});

it('przyjmuje poprawny logout token i odpowiada bez cache', function (): void {
    udawajIdp();

    $logoutToken = FabrykaTokenow::podpisz([
        'iss' => IDP,
        'aud' => 'gabinet',
        'sub' => 'sub-abc-123',
        'sid' => 'sid-abc-123',
        'iat' => time(),
        'exp' => time() + 120,
        'jti' => 'jti-1',
        'events' => ['http://schemas.openid.net/event/backchannel-logout' => (object) []],
    ]);

    $this->postJson('/oidc/backchannel-logout', ['logout_token' => $logoutToken])
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertHeader('Cache-Control', 'no-store, private');
});

it('nie przyjmuje logout tokenu wystawionego dla innego klienta', function (): void {
    udawajIdp();

    $logoutToken = FabrykaTokenow::podpisz([
        'iss' => IDP,
        'aud' => 'psychon-api',
        'sid' => 'sid-abc-123',
        'iat' => time(),
        'exp' => time() + 120,
        'events' => ['http://schemas.openid.net/event/backchannel-logout' => (object) []],
    ]);

    $this->postJson('/oidc/backchannel-logout', ['logout_token' => $logoutToken])
        ->assertStatus(400)
        ->assertJsonPath('kontrole.aud', 'fail');
});
