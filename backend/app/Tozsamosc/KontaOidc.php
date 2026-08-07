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

    /** Znacznik „JWKS odświeżano niedawno" — patrz `jwksDlaKid()`. */
    private const KLUCZ_ODSWIEZANIE = 'konta:jwks-odswiezone';

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
     * JWKS zawierający wskazany `kid` — z OGRANICZONYM odświeżaniem.
     *
     * Problem, którego ta metoda pilnuje (lekcja zespołu hubu, perturbacje
     * JWKS): `kid` pochodzi z NAGŁÓWKA tokenu, czyli z danych wejściowych,
     * których jeszcze nie zweryfikowaliśmy — kontroluje go ten, kto wysyła
     * żądanie. Naiwna obsługa rotacji kluczy („nie znam kid → dociągnij
     * JWKS") zamienia więc Gabinet we WZMACNIACZ ŻĄDAŃ: strumień tokenów
     * z losowym `kid` staje się strumieniem żądań do Kont Niepodzielni,
     * przy czym atakujący nie musi mieć konta ani ważnego podpisu.
     *
     * Dlatego odświeżenie stoi za bramką częstotliwości. `Cache::add` jest
     * atomowe — ustawia klucz tylko wtedy, gdy go nie ma — więc nawet przy
     * równoległych żądaniach do IdP idzie DOKŁADNIE JEDNO zapytanie na okno.
     * Kto znacznika nie zdobędzie, dostaje JWKS z cache'u i po prostu odrzuci
     * token: nieznany `kid` to i tak najczęściej token podrobiony.
     *
     * Reguła ogólna: każda ścieżka „cache-miss → żądanie w górę" wymaga
     * pytania, kto kontroluje wejście decydujące o tym missie. Jeśli
     * atakujący — potrzebna jest bramka.
     *
     * @return array<string, mixed>
     */
    public function jwksDlaKid(string $kid): array
    {
        $jwks = $this->jwks();

        if ($kid === '' || self::maKid($jwks, $kid)) {
            return $jwks;
        }

        if (! Cache::add(self::KLUCZ_ODSWIEZANIE, 1, $this->odstepOdswiezaniaSekundy())) {
            return $jwks;
        }

        Cache::forget(self::KLUCZ_JWKS);

        return $this->jwks();
    }

    /**
     * @param  array<string, mixed>  $jwks
     */
    private static function maKid(array $jwks, string $kid): bool
    {
        foreach (Typy::mapa($jwks['keys'] ?? null) as $klucz) {
            if (Typy::napis(Typy::mapa($klucz)['kid'] ?? null) === $kid) {
                return true;
            }
        }

        return false;
    }

    /**
     * Najkrótszy odstęp między odświeżeniami JWKS wywołanymi nieznanym `kid`.
     *
     * 60 s to kompromis: rotacja klucza w IdP zaczyna działać najpóźniej po
     * minucie, a koszt zalewu żądań spada do jednego zapytania na minutę
     * niezależnie od tego, ile ich przyjdzie.
     */
    private function odstepOdswiezaniaSekundy(): int
    {
        return max(1, Typy::liczba(config('konta.jwks_odstep_odswiezania_s'), 60));
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
     * Odświeżenie tokenów refresh tokenem.
     *
     * Decyzja B8: to jest MOMENT, w którym przeliczamy role. Odświeżenie i tak
     * podtrzymuje sesję, więc przeliczenie kosztuje zero dodatkowych żądań —
     * a odebranie roli w Keycloaku zaczyna działać najpóźniej w oknie tokenu
     * (kontrakt: 600 s), zamiast nie działać wcale przez 120 minut sesji.
     *
     * Odmowa IdP (konto zablokowane, refresh token cofnięty, sesja
     * unieważniona) jest sygnałem do ZAKOŃCZENIA sesji, nie do ponowienia.
     *
     * @return array{status: int, body: array<string, mixed>}
     */
    public function odswiezTokeny(string $refreshToken): array
    {
        $metadane = $this->metadane();

        $odpowiedz = $this->klient()->asForm()->post(Typy::napis($metadane['token_endpoint']), [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
        ]);

        /** @var array<string, mixed> $body */
        $body = is_array($odpowiedz->json()) ? $odpowiedz->json() : [];

        return ['status' => $odpowiedz->status(), 'body' => $body];
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
