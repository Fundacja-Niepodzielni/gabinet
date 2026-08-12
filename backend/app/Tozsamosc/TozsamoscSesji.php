<?php

declare(strict_types=1);

namespace App\Tozsamosc;

use App\Wsparcie\Typy;
use Illuminate\Http\Request;

/**
 * ISTNIEJĄCA tożsamość w sesji — typ, który UTRUDNIA wytworzenie z niczego.
 *
 * @dowod: WaskieGardloTozsamosciTest — R6A-3 zamknięte 12.08 STRUKTURALNIE.
 *
 * Historia, bo bez niej naprawa wygląda na kosmetykę: weryfikator rundy 6 wytworzył
 * tożsamość koordynatora BEZ LOGOWANIA trzema wektorami — danymi z żądania,
 * `Reflection` i `unserialize` — i uzyskał przez HTTP pełne uprawnienia. Pierwszy
 * z tych wektorów prowadził przez `zMagazynu()`: PUBLICZNĄ fabrykę przyjmującą
 * DOWOLNĄ tablicę. Moje wcześniejsze zdanie „NIEWYWOŁYWALNE" było za mocne —
 * warunek przeniósł się o poziom wyżej, zamiast zniknąć.
 *
 * ZAMKNIĘTE JEST: wytworzenie tożsamości z surowej tablicy. `zMagazynu()` jest
 * prywatna, a jedyne publiczne wejście — `zZadania(Request)` — czyta PRAWDZIWY
 * magazyn sesji. Podanie mu wymyślonych ról wymaga uprzedniego zapisania ich
 * do sesji, czyli przejścia drogą logowania.
 *
 * NIE JEST ZAMKNIĘTE i mówię to wprost, żeby nikt nie odziedziczył fałszywego
 * poczucia domknięcia: `Reflection::newInstanceWithoutConstructor()` oraz
 * `unserialize()` omijają KAŻDY konstruktor w PHP i tego nie da się zamknąć
 * typem. WARUNEK UTRZYMUJĄCY: w kodzie produkcyjnym nie ma ani `unserialize()`
 * na danych z żądania, ani wywołań `Reflection` na tej warstwie — i to jest
 * EGZEKWOWANE kontrolą, nie pamięcią.
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

    /** Klucz w magazynie sesji. JEDNO miejsce, żeby dało się policzyć czytających. */
    public const KLUCZ = 'konta';

    /**
     * JEDYNE publiczne wejście: odczyt z PRAWDZIWEGO magazynu sesji.
     *
     * Argumentem jest żądanie, nie tablica — i to jest cała naprawa R6A-3.
     * Tablicę można wymyślić; sesji nie można, bo żeby coś w niej było,
     * ktoś musiał to tam wcześniej zapisać drogą logowania.
     *
     * @dowod: WaskieGardloTozsamosciTest — „jedyna publiczna fabryka przyjmuje
     *         Request, nigdy tablicy" (kontrola pozytywna i negatywna).
     */
    public static function zZadania(Request $request): ?self
    {
        return self::zMagazynu(Typy::mapa($request->session()->get(self::KLUCZ)));
    }

    /**
     * Tożsamość z surowej zawartości magazynu — albo `null`, gdy jej TAM NIE MA.
     *
     * PRYWATNA od 12.08. Dopóki była publiczna, była fabryką tożsamości
     * z dowolnej tablicy — czyli §2 stało na nazwie metody, nie na typie.
     *
     * @param  array<string, mixed>  $zMagazynu
     */
    private static function zMagazynu(array $zMagazynu): ?self
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
