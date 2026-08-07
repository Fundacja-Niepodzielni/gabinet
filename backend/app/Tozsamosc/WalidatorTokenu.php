<?php

declare(strict_types=1);

namespace App\Tozsamosc;

use App\Wsparcie\Typy;

/**
 * Weryfikacja JWT podpisem RS256 na podstawie JWKS.
 *
 * Port aplikacji referencyjnej `konta/tests/ref-laravel/app/src/Jwt.php`
 * (kontrakt §4.4). Zachowana jest jej najważniejsza cecha: metoda zwraca
 * MAPĘ KONTROLI, a nie samo tak/nie.
 *
 * To nie jest ozdobnik. Test negatywny, który sprawdza tylko „nie 200",
 * przechodzi także wtedy, gdy walidacja jest kompletnie zepsuta. Mapa kontroli
 * pozwala udowodnić, że token odrzucono Z WŁAŚCIWEGO POWODU — w kontrakcie
 * usunięcie mappera audiencji z żywego realmu zapala 28 asercji właśnie dzięki
 * temu, że każda kontrola raportuje się osobno.
 *
 * Klucz publiczny budujemy z JWK (n, e) samodzielnie, kodując
 * SubjectPublicKeyInfo w DER — bez ani jednej zewnętrznej biblioteki.
 */
final class WalidatorTokenu
{
    /**
     * @param  array<string, mixed>  $opcje  klucze: issuer, jwks, audience, azp,
     *                                       typ, nonce, tolerancja
     * @return array{ok: bool, kontrole: array<string, string>, claims: array<string, mixed>, nieudane: list<string>}
     */
    public static function sprawdz(string $jwt, array $opcje): array
    {
        $rozlozony = self::rozloz($jwt);

        if ($rozlozony === null) {
            return ['ok' => false, 'kontrole' => ['format' => 'fail'], 'claims' => [], 'nieudane' => ['format']];
        }

        $kontrole = ['format' => 'ok'];
        $claims = $rozlozony['payload'];

        $alg = Typy::napis($rozlozony['naglowek']['alg'] ?? null);
        // Żadnego `none`, żadnego HS256 — inaczej podpis da się podrobić kluczem publicznym.
        $kontrole['alg'] = $alg === 'RS256' ? 'ok' : 'fail';

        $kid = Typy::napis($rozlozony['naglowek']['kid'] ?? null);
        $jwk = self::znajdzKlucz(Typy::mapa($opcje['jwks'] ?? null), $kid);
        $kontrole['kid'] = $jwk !== null ? 'ok' : 'fail';

        $kontrole['signature'] = 'fail';

        if ($jwk !== null && $kontrole['alg'] === 'ok') {
            $pem = self::jwkNaPem($jwk);

            if ($pem !== null && openssl_verify($rozlozony['podpisywane'], $rozlozony['podpis'], $pem, OPENSSL_ALGO_SHA256) === 1) {
                $kontrole['signature'] = 'ok';
            }
        }

        // Porównanie DOKŁADNE, nie „zaczyna się od".
        $issOczekiwany = rtrim(Typy::napis($opcje['issuer'] ?? null), '/');
        $kontrole['iss'] = rtrim(Typy::napis($claims['iss'] ?? null), '/') === $issOczekiwany ? 'ok' : 'fail';

        $tolerancja = Typy::liczba($opcje['tolerancja'] ?? null, 30);
        $teraz = time();
        $kontrole['exp'] = isset($claims['exp']) && $teraz < (Typy::liczba($claims['exp']) + $tolerancja) ? 'ok' : 'fail';
        $kontrole['iat'] = isset($claims['iat']) && (Typy::liczba($claims['iat']) - $tolerancja) <= $teraz ? 'ok' : 'fail';

        if (! empty($opcje['typ'])) {
            // `typ=Bearer` odrzuca podstawiony ID token w miejsce access tokenu.
            $kontrole['typ'] = Typy::napis($claims['typ'] ?? null) === Typy::napis($opcje['typ']) ? 'ok' : 'fail';
        }

        if (! empty($opcje['audience'])) {
            // `aud` bywa stringiem, nie tablicą — normalizujemy przed sprawdzeniem.
            $aud = $claims['aud'] ?? [];
            $aud = is_array($aud) ? $aud : [$aud];
            $kontrole['aud'] = in_array(Typy::napis($opcje['audience']), Typy::listaNapisow($aud), true) ? 'ok' : 'fail';
        }

        if (! empty($opcje['azp'])) {
            $kontrole['azp'] = Typy::napis($claims['azp'] ?? null) === Typy::napis($opcje['azp']) ? 'ok' : 'fail';
        }

        if (array_key_exists('nonce', $opcje)) {
            // FAIL-CLOSED. Wersja pierwsza pomijała tę kontrolę, gdy oczekiwany
            // `nonce` był `null` — a kontroler przekazywał `$przeplyw['nonce']
            // ?? null`. Sesja bez `nonce` sprawiała więc, że token z DOWOLNYM
            // nonce przechodził i kończył się stanem „zalogowany".
            // Znalazł to niezależny weryfikator; kontrola bezpieczeństwa nie
            // może MILCZEĆ, kiedy nie ma z czym porównać — ma paść.
            $oczekiwany = $opcje['nonce'];

            $kontrole['nonce'] = is_string($oczekiwany) && $oczekiwany !== ''
                && Typy::napis($claims['nonce'] ?? null) === $oczekiwany
                    ? 'ok' : 'fail';
        }

        $nieudane = array_keys(array_filter($kontrole, static fn (string $v): bool => $v !== 'ok'));

        return ['ok' => $nieudane === [], 'kontrole' => $kontrole, 'claims' => $claims, 'nieudane' => $nieudane];
    }

    /**
     * @return array{naglowek: array<string, mixed>, payload: array<string, mixed>, podpisywane: string, podpis: string}|null
     */
    public static function rozloz(string $jwt): ?array
    {
        $czesci = explode('.', $jwt);

        if (count($czesci) !== 3) {
            return null;
        }

        $naglowek = json_decode(self::base64UrlDekoduj($czesci[0]), true);
        $payload = json_decode(self::base64UrlDekoduj($czesci[1]), true);

        if (! is_array($naglowek) || ! is_array($payload)) {
            return null;
        }

        return [
            'naglowek' => Typy::mapa($naglowek),
            'payload' => Typy::mapa($payload),
            'podpisywane' => $czesci[0].'.'.$czesci[1],
            'podpis' => self::base64UrlDekoduj($czesci[2]),
        ];
    }

    public static function base64UrlDekoduj(string $s): string
    {
        $reszta = strlen($s) % 4;

        if ($reszta > 0) {
            $s .= str_repeat('=', 4 - $reszta);
        }

        $wynik = base64_decode(strtr($s, '-_', '+/'), true);

        return $wynik === false ? '' : $wynik;
    }

    /**
     * @param  array<string, mixed>  $jwks
     * @return array<string, mixed>|null
     */
    private static function znajdzKlucz(array $jwks, string $kid): ?array
    {
        $klucze = $jwks['keys'] ?? [];

        if (! is_array($klucze)) {
            return null;
        }

        foreach ($klucze as $klucz) {
            if (! is_array($klucz)) {
                continue;
            }

            if (($klucz['kid'] ?? '') === $kid
                && ($klucz['use'] ?? 'sig') === 'sig'
                && ($klucz['alg'] ?? 'RS256') === 'RS256') {
                /** @var array<string, mixed> $klucz */
                return $klucz;
            }
        }

        return null;
    }

    /**
     * JWK (RSA) → PEM klucza publicznego. Czysty PHP, bez zależności.
     *
     * @param  array<string, mixed>  $jwk
     */
    public static function jwkNaPem(array $jwk): ?string
    {
        if (($jwk['kty'] ?? '') !== 'RSA' || ! isset($jwk['n'], $jwk['e'])) {
            return null;
        }

        $n = self::base64UrlDekoduj(Typy::napis($jwk['n']));
        $e = self::base64UrlDekoduj(Typy::napis($jwk['e']));

        if ($n === '' || $e === '') {
            return null;
        }

        $kluczRsa = self::derSekwencja(self::derLiczba($n).self::derLiczba($e));

        // AlgorithmIdentifier: SEQUENCE { OID 1.2.840.113549.1.1.1 (rsaEncryption), NULL }
        $algorytm = (string) hex2bin('300d06092a864886f70d0101010500');
        $bitString = "\x03".self::derDlugosc(strlen($kluczRsa) + 1)."\x00".$kluczRsa;
        $spki = self::derSekwencja($algorytm.$bitString);

        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($spki), 64, "\n").
            "-----END PUBLIC KEY-----\n";
    }

    private static function derSekwencja(string $tresc): string
    {
        return "\x30".self::derDlugosc(strlen($tresc)).$tresc;
    }

    private static function derDlugosc(int $dlugosc): string
    {
        if ($dlugosc < 0x80) {
            return chr($dlugosc);
        }

        $bajty = ltrim(pack('N', $dlugosc), "\x00");

        return chr(0x80 | strlen($bajty)).$bajty;
    }

    private static function derLiczba(string $bajty): string
    {
        $bajty = ltrim($bajty, "\x00");

        if ($bajty === '') {
            $bajty = "\x00";
        }

        if (ord($bajty[0]) > 0x7F) {
            $bajty = "\x00".$bajty;
        }

        return "\x02".self::derDlugosc(strlen($bajty)).$bajty;
    }
}
