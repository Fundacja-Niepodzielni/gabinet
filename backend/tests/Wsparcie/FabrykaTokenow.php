<?php

declare(strict_types=1);

namespace Tests\Wsparcie;

use OpenSSLAsymmetricKey;
use RuntimeException;

/**
 * Wystawia PRAWDZIWE tokeny RS256 na kluczu wygenerowanym w pamięci testu.
 *
 * Dlaczego nie stałe tokeny wklejone do repozytorium: token ma `exp`, więc
 * po kilku miesiącach test przestałby cokolwiek dowodzić („odrzucony, bo
 * wygasł" wygląda tak samo jak „odrzucony, bo zły podpis"). Klucz w pamięci
 * pozwala też stworzyć token, który różni się od poprawnego DOKŁADNIE JEDNYM
 * polem — a to jest warunek uczciwego testu negatywnego.
 */
final class FabrykaTokenow
{
    /** Adres atrapy IdP — jedno źródło dla wszystkich testów tożsamości. */
    public const ADRES = 'https://idp.test/realms/niepodzielni';

    private static ?OpenSSLAsymmetricKey $klucz = null;

    public const KID = 'test-kid-gabinet';

    public static function klucz(): OpenSSLAsymmetricKey
    {
        if (self::$klucz === null) {
            $klucz = openssl_pkey_new([
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);

            if ($klucz === false) {
                throw new RuntimeException('nie udało się wygenerować klucza RSA');
            }

            self::$klucz = $klucz;
        }

        return self::$klucz;
    }

    /**
     * JWKS w formacie, jaki zwraca Keycloak.
     *
     * @return array{keys: list<array<string, string>>}
     */
    public static function jwks(): array
    {
        $szczegoly = openssl_pkey_get_details(self::klucz());

        if ($szczegoly === false || ! isset($szczegoly['rsa']) || ! is_array($szczegoly['rsa'])) {
            throw new RuntimeException('nie udało się odczytać klucza publicznego');
        }

        $modul = $szczegoly['rsa']['n'] ?? null;
        $wykladnik = $szczegoly['rsa']['e'] ?? null;

        if (! is_string($modul) || ! is_string($wykladnik)) {
            throw new RuntimeException('klucz publiczny nie ma pól n/e');
        }

        return [
            'keys' => [[
                'kty' => 'RSA',
                'use' => 'sig',
                'alg' => 'RS256',
                'kid' => self::KID,
                'n' => self::base64Url($modul),
                'e' => self::base64Url($wykladnik),
            ]],
        ];
    }

    /**
     * @param  array<string, mixed>  $claims
     * @param  array<string, mixed>  $naglowek
     */
    public static function podpisz(array $claims, array $naglowek = []): string
    {
        $naglowek = array_merge(['alg' => 'RS256', 'typ' => 'JWT', 'kid' => self::KID], $naglowek);

        $czesc1 = self::base64Url(self::json($naglowek));
        $czesc2 = self::base64Url(self::json($claims));
        $doPodpisu = $czesc1.'.'.$czesc2;

        $podpis = '';

        if (! openssl_sign($doPodpisu, $podpis, self::klucz(), OPENSSL_ALGO_SHA256) || ! is_string($podpis)) {
            throw new RuntimeException('nie udało się podpisać tokenu');
        }

        return $doPodpisu.'.'.self::base64Url($podpis);
    }

    /**
     * Poprawny ACCESS token dla klienta `gabinet`. Testy negatywne biorą to
     * jako punkt wyjścia i zmieniają dokładnie jedno pole.
     *
     * @param  array<string, mixed>  $nadpisania
     * @return array<string, mixed>
     */
    public static function claimsAccess(array $nadpisania = []): array
    {
        $teraz = time();

        return array_merge([
            'iss' => 'https://idp.test/realms/niepodzielni',
            'aud' => ['gabinet', 'account'],
            'azp' => 'gabinet',
            'typ' => 'Bearer',
            'sub' => 'sub-abc-123',
            'sid' => 'sid-abc-123',
            'iat' => $teraz,
            'exp' => $teraz + 600,
            'preferred_username' => 'test-psycholog',
            'email' => 'test-psycholog@niepodzielni.test',
            'email_verified' => true,
            'realm_access' => ['roles' => ['offline_access', 'psycholog', 'default-roles-niepodzielni']],
        ], $nadpisania);
    }

    /**
     * @param  array<string, mixed>  $nadpisania
     * @return array<string, mixed>
     */
    public static function claimsId(array $nadpisania = []): array
    {
        $teraz = time();

        return array_merge([
            'iss' => 'https://idp.test/realms/niepodzielni',
            'aud' => 'gabinet',
            'azp' => 'gabinet',
            'typ' => 'ID',
            'sub' => 'sub-abc-123',
            'sid' => 'sid-abc-123',
            'iat' => $teraz,
            'exp' => $teraz + 600,
            'nonce' => 'nonce-testowy',
            'preferred_username' => 'test-psycholog',
            'email_verified' => true,
            // Świadomie BEZ `given_name`/`family_name`: kontrakt §2a mówi, że
            // tych claimów zwykle w ogóle nie ma (pseudonimiczność pacjenta).
        ], $nadpisania);
    }

    private static function base64Url(string $dane): string
    {
        return rtrim(strtr(base64_encode($dane), '+/', '-_'), '=');
    }

    /**
     * @param  array<string, mixed>  $dane
     */
    private static function json(array $dane): string
    {
        $json = json_encode($dane, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new RuntimeException('nie udało się zakodować JSON-a tokenu');
        }

        return $json;
    }
}
