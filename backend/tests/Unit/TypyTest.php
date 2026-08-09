<?php

declare(strict_types=1);

use App\Wsparcie\Typy;

/**
 * ŚWIADEK dla twierdzenia z docblocka `Typy::napis`.
 *
 * Powstał w odpowiedzi na ZLECENIE-008: helpdesk wskazał, że `Typy.php` ORZEKA
 * („Tablica, obiekt i `null` NIGDY nie stają się napisem po cichu"), nie
 * wskazując świadka. Sprawdziłem: **twierdzenie było prawdziwe, ale świadka nie
 * było** — `Typy::napis` miała w suicie wyłącznie UŻYTKOWNIKÓW (`BrakWlasnychHasel`,
 * `ModelDanych`), którzy wołają ją po drodze do czegoś innego. Ani jedna asercja
 * nie dotyczyła samej reguły, więc zmiana `return $domyslny` na `return (string) $wartosc`
 * mogła przejść niezauważona.
 *
 * Dlatego to nie jest test „na wszelki wypadek": jego brak był LUKĄ w pokryciu
 * reguły, od której zależy kod rozstrzygający o tożsamości i uprawnieniach
 * (D-2026-08-07-05).
 */
it('tablica, obiekt, null i bool NIE stają się napisem — dostają wartość domyślną', function (): void {
    expect(Typy::napis([1, 2, 3], 'DOMYSLNA'))->toBe('DOMYSLNA', 'Tablica zamieniła się w napis (w PHP dałaby „Array").')
        ->and(Typy::napis(new stdClass, 'DOMYSLNA'))->toBe('DOMYSLNA', 'Obiekt zamienił się w napis.')
        ->and(Typy::napis(null, 'DOMYSLNA'))->toBe('DOMYSLNA', 'null zamienił się w napis (w PHP dałby pusty napis).')
        ->and(Typy::napis(true, 'DOMYSLNA'))->toBe('DOMYSLNA', 'bool zamienił się w napis (w PHP dałby „1").');
});

it('napis, liczba całkowita i zmiennoprzecinkowa PRZECHODZĄ — inaczej pomocnik byłby bezużyteczny', function (): void {
    // Kierunek odwrotny: bez niego test wyżej przechodzi także wtedy, gdy
    // `napis()` zwraca wartość domyślną ZAWSZE, czyli nie działa w ogóle.
    expect(Typy::napis('abc', 'DOMYSLNA'))->toBe('abc')
        ->and(Typy::napis(7, 'DOMYSLNA'))->toBe('7')
        ->and(Typy::napis(1.5, 'DOMYSLNA'))->toBe('1.5');
});

it('obiekt z __toString też NIE przechodzi — reguła jest o typie, nie o wygodzie', function (): void {
    $zToString = new class
    {
        public function __toString(): string
        {
            return 'PRZEMYCONE';
        }
    };

    expect(Typy::napis($zToString, 'DOMYSLNA'))->toBe(
        'DOMYSLNA',
        'Obiekt z __toString przemycił się jako napis — twierdzenie z docblocka jest nieprawdziwe.'
    );
});
