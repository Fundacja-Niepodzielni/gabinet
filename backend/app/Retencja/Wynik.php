<?php

declare(strict_types=1);

namespace App\Retencja;

/**
 * Wynik przebiegu retencji — SELEKCJA i WYKONANIE osobno.
 *
 * Rozdzielenie tych dwóch liczb jest całym sensem tej klasy. Kontrola pytająca
 * wyłącznie „czy wybrano właściwe rekordy" przechodzi także wtedy, gdy nic nie
 * zostało skasowane — a wtedy dane pacjenta zostają po terminie i nic o tym
 * nie krzyczy (lekcja przekrojowa zespołu helpdesku).
 *
 * `$pozostale` to lista identyfikatorów, które PRZEŻYŁY własne kasowanie.
 * Niepusta lista jest naruszeniem retencji, nie ostrzeżeniem.
 */
final readonly class Wynik
{
    /**
     * Typ identyfikatora należy do DZIEDZINY, nie do kontraktu (D-2026-08-09-03):
     * sesja ma identyfikator tekstowy, wiersz zgody liczbowy. Kształtem
     * rozstrzygnięcia jest zbiór pól, ich znaczenie i `kompletny()` — nie typ klucza.
     *
     * Reprezentacja jest ZACHOWYWANA, nie ujednolicana: normalizacja do napisów
     * uczyniłaby `42` i `"42"` nieodróżnialnymi, czyli zrobiłaby wartość
     * zdegenerowaną z pola, którego jedynym zadaniem jest wskazywać konkretny obiekt.
     *
     * @param  list<int|string>  $pozostale
     */
    public function __construct(
        public int $wybrane,
        public int $usuniete,
        public array $pozostale,
    ) {}

    /** Czy wykonanie pokryło się z selekcją. */
    public function kompletny(): bool
    {
        return $this->pozostale === [] && $this->usuniete === $this->wybrane;
    }
}
