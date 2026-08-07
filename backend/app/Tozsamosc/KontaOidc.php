<?php

declare(strict_types=1);

namespace App\Tozsamosc;

use App\Wsparcie\Typy;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Warstwa OIDC Kont Niepodzielni: discovery, JWKS, wymiana kodu na tokeny,
 * budowa adresów logowania i wylogowania.
 *
 * Port `konta/tests/ref-laravel/app/src/Oidc.php` na narzędzia Laravela
 * (klient HTTP + cache). Zachowana jest reguła dwóch adresów IdP (kontrakt §3a):
 * discovery pobieramy trasą WEWNĘTRZNĄ, ale endpointy przeglądarkowe i `iss`
 * zostają PUBLICZNE.
 */
final class KontaOidc
{
    private const KLUCZ_DISCOVERY = 'konta:discovery';

    private const KLUCZ_JWKS = 'konta:jwks';

    /**
     * Metadane z discovery, z endpointami back-channel przepisanymi na trasę
     * wewnętrzną.
     *
     * @return array<string, mixed>
     */
    public function metadane(): array
    {
        /** @var array<string, mixed> */
        return Cache::remember(self::KLUCZ_DISCOVERY, $this->cacheSekundy(), function (): array {
            $url = $this->issuerWewnetrzny().'/.well-known/openid-configuration';
            $odpowiedz = $this->klient()->get($url);

            if (! $odpowiedz->successful()) {
                throw new RuntimeException("discovery {$url} => HTTP {$odpowiedz->status()}");
            }

            /** @var array<string, mixed> $metadane */
            $metadane = $odpowiedz->json();

            if (! isset($metadane['issuer'])) {
                throw new RuntimeException('discovery: odpowiedź bez pola issuer');
            }

            // KONTRAKT §3a: `iss` w tokenie to ZAWSZE adres publiczny. Gdyby
            // discovery zwróciło cokolwiek innego, cała walidacja tokenów byłaby
            // fikcją — więc przerywamy start, zamiast walidować względem złego adresu.
            if (rtrim(Typy::napis($metadane['issuer']), '/') !== $this->issuerPubliczny()) {
                throw new RuntimeException(
                    'discovery: issuer='.Typy::napis($metadane['issuer']).
                    ' != KEYCLOAK_PUBLIC_ISSUER='.$this->issuerPubliczny()
                );
            }

            foreach (['token_endpoint', 'jwks_uri', 'userinfo_endpoint', 'introspection_endpoint'] as $pole) {
                if (isset($metadane[$pole]) && is_string($metadane[$pole])) {
                    $metadane[$pole] = $this->naTraseWewnetrzna($metadane[$pole]);
                }
            }

            return $metadane;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function jwks(): array
    {
        /** @var array<string, mixed> */
        return Cache::remember(self::KLUCZ_JWKS, $this->cacheSekundy(), function (): array {
            $metadane = $this->metadane();
            $odpowiedz = $this->klient()->get(Typy::napis($metadane['jwks_uri']));

            if (! $odpowiedz->successful()) {
                throw new RuntimeException("JWKS => HTTP {$odpowiedz->status()}");
            }

            /** @var array<string, mixed> */
            return $odpowiedz->json();
        });
    }

    /**
     * Adres, pod który wysyłamy przeglądarkę użytkownika.
     *
     * Klient poufny (`gabinet`) uwierzytelnia się sekretem, ale PKCE i tak
     * dokładamy — kosztuje nic, a domyka wyciek kodu z logów proxy.
     */
    public function adresLogowania(string $state, string $nonce, string $weryfikatorPkce): string
    {
        $metadane = $this->metadane();

        $wyzwanie = rtrim(strtr(base64_encode(hash('sha256', $weryfikatorPkce, true)), '+/', '-_'), '=');

        return Typy::napis($metadane['authorization_endpoint']).'?'.http_build_query([
            'client_id' => $this->clientId(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'redirect_uri' => $this->redirectUri(),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $wyzwanie,
            'code_challenge_method' => 'S256',
        ]);
    }

    /**
     * Wymiana kodu na tokeny. Klient POUFNY → uwierzytelnienie sekretem.
     *
     * @return array{status: int, body: array<string, mixed>}
     */
    public function wymienKod(string $kod, string $weryfikatorPkce): array
    {
        $metadane = $this->metadane();

        $odpowiedz = $this->klient()->asForm()->post(Typy::napis($metadane['token_endpoint']), [
            'grant_type' => 'authorization_code',
            'code' => $kod,
            'redirect_uri' => $this->redirectUri(),
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'code_verifier' => $weryfikatorPkce,
        ]);

        /** @var array<string, mixed> $body */
        $body = is_array($odpowiedz->json()) ? $odpowiedz->json() : [];

        return ['status' => $odpowiedz->status(), 'body' => $body];
    }

    /**
     * RP-initiated logout. Bez tego użytkownik zostaje zalogowany w IdP
     * i jedno kliknięcie „zaloguj" wpuszcza go z powrotem bez hasła.
     */
    public function adresWylogowania(string $idToken, ?string $poWylogowaniu): string
    {
        $metadane = $this->metadane();

        $parametry = ['id_token_hint' => $idToken, 'client_id' => $this->clientId()];

        // KONTRAKT §3: `post.logout.redirect.uris` = "+", czyli dozwolone są
        // WYŁĄCZNIE adresy z `redirectUris` klienta. Obcy adres = odmowa.
        if ($poWylogowaniu !== null) {
            $parametry['post_logout_redirect_uri'] = $poWylogowaniu;
        }

        return Typy::napis($metadane['end_session_endpoint']).'?'.http_build_query($parametry);
    }

    public function issuerPubliczny(): string
    {
        return rtrim(Typy::napis(config('konta.issuer_publiczny')), '/');
    }

    public function issuerWewnetrzny(): string
    {
        $wewnetrzny = rtrim(Typy::napis(config('konta.issuer_wewnetrzny')), '/');

        return $wewnetrzny !== '' ? $wewnetrzny : $this->issuerPubliczny();
    }

    public function clientId(): string
    {
        return Typy::napis(config('konta.client_id'));
    }

    public function wymaganaAudiencja(): string
    {
        return Typy::napis(config('konta.wymagana_audiencja'));
    }

    public function tolerancjaZegara(): int
    {
        return Typy::liczba(config('konta.tolerancja_zegara'), 30);
    }

    public function redirectUri(): string
    {
        return Typy::napis(config('konta.redirect_uri'));
    }

    private function clientSecret(): string
    {
        return Typy::napis(config('konta.client_secret'));
    }

    private function cacheSekundy(): int
    {
        return Typy::liczba(config('konta.cache_sekundy'), 300);
    }

    private function naTraseWewnetrzna(string $url): string
    {
        $publiczny = $this->issuerPubliczny();
        $wewnetrzny = $this->issuerWewnetrzny();

        return str_starts_with($url, $publiczny)
            ? $wewnetrzny.substr($url, strlen($publiczny))
            : $url;
    }

    private function klient(): PendingRequest
    {
        $klient = Http::timeout(10)->connectTimeout(5);

        $ca = Typy::napis(config('konta.ca_bundle'));

        // Lokalny stos `konta` używa CA Caddy'ego. Wskazujemy plik CA —
        // NIGDY nie wyłączamy weryfikacji certyfikatu.
        if ($ca !== '') {
            $klient = $klient->withOptions(['verify' => $ca]);
        }

        return $klient;
    }
}
