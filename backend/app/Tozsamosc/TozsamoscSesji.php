<?php

declare(strict_types=1);

namespace App\Tozsamosc;

use App\Wsparcie\Typy;

/**
 * ISTNIEJĄCA tożsamość w sesji — typ, który UTRUDNIA wytworzenie z niczego.
 *
 * @dowod: R6A-3 — twierdzenie „nie da się" ZOSTAŁO OBALONE pomiarem (weryfikator
 *         wytworzył tożsamość koordynatora bez logowania trzema wektorami:
 *         danymi z żądania, `Reflection` i `unserialize`). Zdanie osłabione do
 *         stanu zgodnego z pomiarem; domknięcie strukturalne czeka na rundę
 *         przedmiotu (`zMagazynu()` prywatne, jedyne wejście przez `SesjaKonta`).
 *
 * Powód istnienia: wymóg CLAUDE.md §2 i standardu B8 — odświeżanie ma być
 * OPERACJĄ NA ISTNIEJĄCEJ TOŻSAMOŚCI. Wcześniej pilnował tego STRAŻNIK:
 * `stanKonta()` czytał tablicę i wychodził przy pustce, więc miał dostęp do
 * zapisu niezależnie od wyniku sprawdzenia. Strażnika da się ominąć —
 * i to samo w sobie wystarcza, żeby go zastąpić strukturą.
 *
 * CZEGO TEN KOMENTARZ NIE TWIERDZI (sprostowanie z 08.08, wieczór): NIE jest
 * zmierzone, że odświeżanie kiedykolwiek tożsamość STWORZYŁO. Taki wniosek
 * postawiono z migawek magazynu i **obalono go pomiarem kontrolnym** — po tej
 * przebudowie liczby były IDENTYCZNE, więc objaw nie pochodził z tej ścieżki.
 * Noga 1 pary negatywnej BLK-22 pozostaje NIEROZSTRZYGNIĘTA; patrz komentarz
 * testu `NOGA 1` w `tests/Feature/OdebranieRoliTest.php`.
 *
 * Przebudowa zostaje mimo obalenia diagnozy, bo jej uzasadnieniem jest wymóg
 * §2, a nie tamten objaw. To była właściwa naprawa — tylko nie tego objawu.
 *
 * Konstruktor jest PRYWATNY, a jedyna droga do instancji prowadzi przez
 * `zMagazynu()`, które zwraca `null`, gdy w sesji nie ma tożsamości. Dzięki
 * temu ścieżka „brak rekordu → utwórz" jest w odświeżaniu **trudniejsza**,
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
     * operacje wymagające tożsamości stają się trudniejsze do wywołania.
     *
     * @dowod: R6A-3 — „niewywoływalne" było za mocne; `zMagazynu()` jest
     *         publiczną fabryką przyjmującą dowolną tablicę.
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
     * tożsamość istniała — o ile sama instancja powstała z magazynu.
     *
     * @dowod: R6A-3 — warunek przeniósł się o poziom wyżej, do `zMagazynu()`.
     *
     * @param  array<string, mixed>  $zmiany
     */
    public function zPodmienionymi(array $zmiany): self
    {
        return new self(array_merge($this->dane, $zmiany));
    }
}
