<?php

declare(strict_types=1);

namespace App\Tozsamosc;

use App\Wsparcie\Typy;

/**
 * ISTNIEJĄCA tożsamość w sesji — typ, którego nie da się wytworzyć z niczego.
 *
 * Powód istnienia (noga 1 pary negatywnej BLK-22, potwierdzona pomiarem):
 * odświeżanie tokenu potrafiło TOŻSAMOŚĆ STWORZYĆ. Wymóg standardu B8 mówi,
 * że odświeżanie ma być OPERACJĄ NA ISTNIEJĄCEJ TOŻSAMOŚCI — a u nas był to
 * strażnik: `stanKonta()` czytał tablicę i wychodził przy pustce. Strażnika
 * da się ominąć; pomiar pokazał, że został ominięty.
 *
 * Konstruktor jest PRYWATNY, a jedyna droga do instancji prowadzi przez
 * `zMagazynu()`, które zwraca `null`, gdy w sesji nie ma tożsamości. Dzięki
 * temu ścieżka „brak rekordu → utwórz" jest w odświeżaniu **niewywoływalna**,
 * a nie „zabroniona warunkiem". To jest różnica między strukturą a kontrolą
 * — ta sama, którą CLAUDE.md §2 wymusza na pisarzu tożsamości (D-2026-08-08-24).
 */
final readonly class TozsamoscSesji
{
    /**
     * @param  array<string, mixed>  $dane
     */
    private function __construct(public array $dane) {}

    /**
     * Tożsamość odczytana z magazynu — albo `null`, gdy jej TAM NIE MA.
     *
     * `null` nie jest błędem do obsłużenia. Jest brakiem wejścia, przez który
     * operacje wymagające tożsamości stają się niewywoływalne.
     *
     * @param  array<string, mixed>  $zMagazynu
     */
    public static function zMagazynu(array $zMagazynu): ?self
    {
        // `sub` rozstrzyga o istnieniu tożsamości — to identyfikator z ID
        // tokenu, wiązanie po nim jest wymogiem CLAUDE.md §2. Sam fakt, że
        // pod kluczem `konta` coś leży, nie wystarcza.
        if (Typy::napis($zMagazynu['sub'] ?? null) === '') {
            return null;
        }

        return new self($zMagazynu);
    }

    public function sub(): string
    {
        return Typy::napis($this->dane['sub'] ?? null);
    }

    public function sid(): string
    {
        return Typy::napis($this->dane['sid'] ?? null);
    }

    public function refreshToken(): string
    {
        return Typy::napis($this->dane['refresh_token'] ?? null);
    }

    public function accessExp(): int
    {
        return Typy::liczba($this->dane['access_exp'] ?? null);
    }

    /**
     * Nowa tożsamość z podmienionymi polami — WYŁĄCZNIE aktualizacja.
     *
     * Zwraca instancję tej samej klasy, więc wynik nadal jest dowodem, że
     * tożsamość istniała. Nie da się tą drogą powołać tożsamości do życia.
     *
     * @param  array<string, mixed>  $zmiany
     */
    public function zPodmienionymi(array $zmiany): self
    {
        return new self(array_merge($this->dane, $zmiany));
    }
}
