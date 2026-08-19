<?php

declare(strict_types=1);

namespace App\Tozsamosc;

use App\Wsparcie\Typy;

/*
 * ROSZCZENIA, KTÓRE PRZESZŁY WERYFIKACJĘ PODPISU — obiekt wartości.
 *
 * Powód istnienia jest jeden i wart zapisania, bo bez niego ta klasa wygląda
 * na opakowanie tablicy bez zysku.
 *
 * Rundy 8–11 znajdowały ZA KAŻDYM RAZEM tę samą klasę wady, o piętro wyżej:
 *
 *     R8-1   nazwa pola                → dopisaliśmy nazwy
 *     R9-1   sposób dostarczenia       → dopisaliśmy kształty ładunku
 *     R10-1  składnia odczytu          → usunęliśmy listę metod
 *     R11-1  pole KONTRAKTOWE (`code`) użyte jako tożsamość
 *     R11-2  inna metoda fasady (`zaktualizuj`), której skaner nie znał
 *
 * Wspólny mianownik: obrona polegała na ROZPOZNAWANIU ZŁEGO KSZTAŁTU KODU,
 * a każdy skaner kształtu ma brzeg — i brzeg zawsze dawał się przekroczyć.
 * Piąta warstwa dałaby szóste piętro.
 *
 * Dlatego zmienia się RODZAJ obrony: z wykrywania złego kształtu na
 * UNIEMOŻLIWIENIE ZŁEGO STANU. Tożsamości nie da się dziś ustanowić napisem
 * z żądania nie dlatego, że kontrola to zauważy, tylko dlatego, że `zaloz()`
 * nie przyjmuje napisu ani tablicy — przyjmuje wartość, której jedyna droga
 * powstania prowadzi przez sprawdzony podpis.
 *
 * `$request->query('code')` jest napisem. Napis nie jest tym typem i nie da
 * się go nim stać — niezależnie od tego, czy pole jest w kontrakcie OIDC.
 *
 * CZEGO TA KLASA NIE TWIERDZI. Nie twierdzi, że nikt nie wytworzy instancji
 * `Reflection`-em ani `unserialize`-em — w PHP nie da się tego zamknąć typem
 * (to samo ograniczenie co przy `TozsamoscSesji`, znalezisko R6A-3). Warunek
 * utrzymujący jest ten sam i jest EGZEKWOWANY kontrolą: kod produkcyjny nie
 * używa `Reflection` ani `unserialize`. Twierdzę wyłącznie tyle: ZWYKŁYM
 * kodem PHP instancji bez weryfikacji podpisu nie da się zrobić.
 */

/**
 * Roszczenia tokenu, który przeszedł `WalidatorTokenu::sprawdz()`.
 *
 * @dowod: TypTozsamosciTest — „konstruktor prywatny, jedyna droga przez
 *         weryfikację, brak fabryki z tablicy".
 */
final readonly class RoszczeniaZweryfikowane
{
    /**
     * PRYWATNY. Jedyne `new self` w całym kodzie stoi w `zTokenu()`, za
     * sprawdzeniem `ok`. Kontrola strukturalna pilnuje, że drugiego nie ma.
     *
     * `$tokenSurowy` to DOKŁADNIE ten napis, który przeszedł weryfikację —
     * nie „jakiś token podany obok". Dzięki temu zapisany w sesji
     * `id_token_hint` jest z definicji tym, którego podpis sprawdziliśmy;
     * wcześniej kontroler niósł go osobnym parametrem i nic nie wiązało go
     * z wynikiem walidacji.
     *
     * @param  array<string, mixed>  $roszczenia
     */
    private function __construct(private array $roszczenia, private string $tokenSurowy) {}

    /**
     * ID TOKEN — dowód TOŻSAMOŚCI. Wymagania z KONFIGURACJI, nie od wołającego.
     *
     * @return array{ok: true, roszczenia: self, kontrole: array<string, string>, nieudane: list<string>}|array{ok: false, roszczenia: null, kontrole: array<string, string>, nieudane: list<string>}
     */
    public static function zIdTokenu(string $jwt, KontaOidc $oidc, string $nonce): array
    {
        return self::zTokenu($jwt, [
            'issuer' => $oidc->issuerPubliczny(),
            'jwks' => $oidc->jwksDlaKid(WalidatorTokenu::kidNiezweryfikowany($jwt)),
            'audience' => $oidc->clientId(),
            'typ' => 'ID',
            'nonce' => $nonce,
            'tolerancja' => $oidc->tolerancjaZegara(),
        ]);
    }

    /**
     * ACCESS TOKEN — dowód UPRAWNIEŃ. Inna audiencja, brak `nonce`.
     *
     * @return array{ok: true, roszczenia: self, kontrole: array<string, string>, nieudane: list<string>}|array{ok: false, roszczenia: null, kontrole: array<string, string>, nieudane: list<string>}
     */
    public static function zAccessTokenu(string $jwt, KontaOidc $oidc): array
    {
        return self::zTokenu($jwt, [
            'issuer' => $oidc->issuerPubliczny(),
            'jwks' => $oidc->jwksDlaKid(WalidatorTokenu::kidNiezweryfikowany($jwt)),
            'audience' => $oidc->wymaganaAudiencja(),
            'typ' => 'Bearer',
            'tolerancja' => $oidc->tolerancjaZegara(),
        ]);
    }

    /**
     * Sprawdzenie podpisu, wystawcy, odbiorcy i czasu.
     *
     * ⛔ PRYWATNA OD 19.08 — znalezisko „szóste piętro" (`ODPOWIEDZ-078` §7).
     *
     * Dopóki była publiczna i brała `array $wymagania`, wołający podawał
     * w tej tablicy **`jwks`, czyli materiał klucza**. Kod, który podał własny
     * klucz, dostawał obiekt CAŁKOWICIE LEGALNIE: walidator mówił `ok`, bo
     * podpis przechodził — tylko przeciw kluczowi napastnika. Zmierzone:
     * mechanizm podmieniający `jwks` przechodził PEŁNĄ suitę (315 zielonych),
     * statykę i format, a wszystkie kontrole tożsamości milczały.
     *
     * Ściana typu chroniła wtedy KSZTAŁT, a nie PRAWDĘ: obiekt nazywał się
     * „zweryfikowane", nie mówiąc — wobec czego. Teraz podstawa zaufania
     * pochodzi z konfiguracji (`KontaOidc`), a nie od tego, kto woła.
     *
     * Zwracany kształt zachowuje SZCZEGÓŁ NIEPOWODZENIA (`kontrole`,
     * `nieudane`), bo ścieżka logowania musi umieć powiedzieć, co odpadło —
     * `LogowanieController::odmowa()` to loguje. Gdyby fabryka zwracała samo
     * `null`, diagnostyka odmowy zniknęłaby razem z tablicą.
     *
     * Rozdzielenie na dwa kształty (`ok: true` z obiektem, `ok: false` z `null`)
     * jest po to, żeby STATYKA wiedziała, że po sprawdzeniu `ok` obiekt jest —
     * bez ręcznych asercji u wołającego.
     *
     * @param  array<string, mixed>  $wymagania
     * @return array{ok: true, roszczenia: self, kontrole: array<string, string>, nieudane: list<string>}|array{ok: false, roszczenia: null, kontrole: array<string, string>, nieudane: list<string>}
     */
    private static function zTokenu(string $jwt, array $wymagania): array
    {
        $wynik = WalidatorTokenu::sprawdz($jwt, $wymagania);

        if ($wynik['ok'] !== true) {
            return [
                'ok' => false,
                'roszczenia' => null,
                'kontrole' => $wynik['kontrole'],
                'nieudane' => $wynik['nieudane'],
            ];
        }

        return [
            'ok' => true,
            'roszczenia' => new self($wynik['claims'], $jwt),
            'kontrole' => $wynik['kontrole'],
            'nieudane' => $wynik['nieudane'],
        ];
    }

    /**
     * Napis tokenu, KTÓRY PRZESZEDŁ weryfikację.
     *
     * Dla Gabinetu ID token jest nieprzezroczysty: służy wyłącznie jako
     * `id_token_hint` przy wylogowaniu. Wydajemy go stąd, a nie z osobnego
     * parametru, żeby nie dało się zapisać w sesji INNEGO napisu niż ten,
     * którego podpis sprawdziliśmy.
     */
    public function tokenSurowy(): string
    {
        return $this->tokenSurowy;
    }

    /** Roszczenie jako napis — pusty, gdy go nie ma. */
    public function napis(string $pole): string
    {
        return Typy::napis($this->roszczenia[$pole] ?? null);
    }

    /** Roszczenie jako napis albo `null`, gdy roszczenia NIE MA (nie: gdy puste). */
    public function napisAlbo(string $pole): ?string
    {
        return isset($this->roszczenia[$pole]) ? Typy::napis($this->roszczenia[$pole]) : null;
    }

    public function liczba(string $pole): int
    {
        return Typy::liczba($this->roszczenia[$pole] ?? null);
    }

    public function prawda(string $pole): bool
    {
        return Typy::prawda($this->roszczenia[$pole] ?? null);
    }

    /**
     * Całość roszczeń — do przeliczenia ról przez `Bramki`.
     *
     * Wydanie tablicy NIE osłabia typu: tablica jest WYNIKIEM weryfikacji,
     * a nie drogą do jej ominięcia. Wytworzyć instancji z tablicy nadal się
     * nie da, bo konstruktor jest prywatny i nie ma fabryki, która by ją brała.
     *
     * @return array<string, mixed>
     */
    public function wszystkie(): array
    {
        return $this->roszczenia;
    }
}
