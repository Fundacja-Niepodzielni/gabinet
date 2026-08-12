<?php

declare(strict_types=1);

namespace App\Wsparcie;

/**
 * Bezpieczne sprowadzanie wartości `mixed` do konkretnego typu.
 *
 * Powód istnienia: claimy JWT, odpowiedzi HTTP i konfiguracja to z punktu
 * widzenia statyki `mixed`. Zwykłe rzutowanie `(string) $cos` jest w PHP
 * ciche i groźne — tablica zamienia się w napis „Array", `null` w pusty napis,
 * obiekt rzuca wyjątkiem dopiero w czasie wykonania. W kodzie, który
 * rozstrzyga o tożsamości i uprawnieniach, chcemy przewidywalnego wyniku
 * i statyki na poziomie `max` (docs/DECYZJE.md, D-2026-08-07-05).
 */
final class Typy
{
    /**
     * Zwraca napis albo wartość domyślną. Tablica, obiekt i `null` NIGDY nie
     * stają się napisem „po cichu" — dostają wartość domyślną.
     *
     * @dowod: TypyTest — „tablica, obiekt, null i bool NIE stają się napisem".
     *         Twierdzenie wskazane przez helpdesk (ZLECENIE-008) jako orzekające
     *         bez świadka. Sprawdzone: twierdzenie było PRAWDZIWE, ale świadka
     *         NIE BYŁO — `Typy::napis` miała wyłącznie użytkowników, ani jednej
     *         asercji o samej regule. Świadek dopisany, nie zdanie osłabione.
     */
    public static function napis(mixed $wartosc, string $domyslny = ''): string
    {
        if (is_string($wartosc)) {
            return $wartosc;
        }

        if (is_int($wartosc) || is_float($wartosc)) {
            return (string) $wartosc;
        }

        return $domyslny;
    }

    public static function liczba(mixed $wartosc, int $domyslna = 0): int
    {
        if (is_int($wartosc)) {
            return $wartosc;
        }

        if (is_string($wartosc) && preg_match('/^-?\d+$/', $wartosc) === 1) {
            return (int) $wartosc;
        }

        return $domyslna;
    }

    public static function prawda(mixed $wartosc, bool $domyslna = false): bool
    {
        if (is_bool($wartosc)) {
            return $wartosc;
        }

        if (is_string($wartosc) || is_int($wartosc)) {
            return filter_var($wartosc, FILTER_VALIDATE_BOOLEAN);
        }

        return $domyslna;
    }

    /**
     * Wartość kolumny z wiersza zwróconego przez `DB::select()` / `DB::selectOne()`.
     *
     * Sterowniki oddają wiersze jako `stdClass` o polach nieznanych statyce, więc
     * `(string) $wiersz->kolumna` jest na poziomie `max` potrójnym błędem
     * (`property.nonObject`, `cast.string`, `argument.type`) — a w czasie działania
     * cichym rzutowaniem: brakująca kolumna daje `null`, czyli pusty napis nie do
     * odróżnienia od kolumny pustej. Tu brak kolumny oddaje WARTOŚĆ DOMYŚLNĄ,
     * którą wołający podaje świadomie.
     *
     * Jedno miejsce zamiast rzutowania w każdym wywołaniu — powstało przy naprawie
     * 34 błędów statyki z 12.08, gdzie ten sam idiom powtarzał się w czterech plikach.
     */
    public static function pole(mixed $wiersz, string $kolumna, string $domyslna = ''): string
    {
        if (! is_object($wiersz)) {
            return $domyslna;
        }

        $wartosci = get_object_vars($wiersz);

        return self::napis($wartosci[$kolumna] ?? null, $domyslna);
    }

    /**
     * @return array<string, mixed>
     */
    public static function mapa(mixed $wartosc): array
    {
        if (! is_array($wartosc)) {
            return [];
        }

        $wynik = [];

        foreach ($wartosc as $klucz => $element) {
            $wynik[(string) $klucz] = $element;
        }

        return $wynik;
    }

    /**
     * Lista napisów — z pominięciem elementów, które napisami nie są.
     *
     * @return list<string>
     */
    public static function listaNapisow(mixed $wartosc): array
    {
        if (! is_array($wartosc)) {
            return [];
        }

        $wynik = [];

        foreach ($wartosc as $element) {
            if (is_string($element)) {
                $wynik[] = $element;
            }
        }

        return $wynik;
    }
}
