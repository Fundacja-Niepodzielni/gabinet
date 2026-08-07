<?php

declare(strict_types=1);

use App\Reguly\OcenaAnulacji;
use App\Reguly\RejestrRegul;
use App\Reguly\Sytuacja;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Wersjonowanie reguł: zmiana NIGDY nie działa wstecz (CLAUDE.md §4, spec s. 51).
 *
 * Testy sięgają do bazy, bo dowodzoną własnością jest zachowanie ZAPYTANIA
 * („która wersja obowiązywała w chwili T"), a nie działanie funkcji w pamięci.
 */
it('ma wersję zerową wstawioną migracją', function (): void {
    // Spec M4/16 wymaga jej wprost: bez niej pierwsza rezerwacja nie ma
    // czego zamrozić.
    $reguly = app(RejestrRegul::class)->obowiazujace();

    expect($reguly->wersja)->toBe(1)
        ->and($reguly->oknoBezplatnegoOdwolaniaGodzin)->toBe(24)
        ->and($reguly->limitNiskoplatnychWizyt)->toBe(10);
});

it('dodaje nową wersję bez modyfikowania poprzedniej', function (): void {
    $rejestr = app(RejestrRegul::class);
    $przed = DB::table('konfiguracja_regul')->where('wersja', 1)->first();

    $rejestr->dodajWersje(
        ['okno_bezplatnego_odwolania_godzin' => 48],
        CarbonImmutable::parse('2026-09-01 00:00:00', 'UTC'),
        autor: 'test',
        uzasadnienie: 'Test wersjonowania.',
    );

    $po = DB::table('konfiguracja_regul')->where('wersja', 1)->first();

    // Wiersz wersji 1 jest bajt w bajt ten sam.
    expect($po)->toEqual($przed)
        ->and(DB::table('konfiguracja_regul')->count())->toBe(2);
});

it('zwraca wersję obowiązującą w danej chwili, nie najnowszą', function (): void {
    $rejestr = app(RejestrRegul::class);

    $rejestr->dodajWersje(
        ['okno_bezplatnego_odwolania_godzin' => 48],
        CarbonImmutable::parse('2026-09-01 00:00:00', 'UTC'),
        autor: 'test',
        uzasadnienie: 'Wydłużenie okna od września.',
    );

    $przedZmiana = $rejestr->obowiazujaceW(CarbonImmutable::parse('2026-08-15 12:00:00', 'UTC'));
    $poZmianie = $rejestr->obowiazujaceW(CarbonImmutable::parse('2026-09-15 12:00:00', 'UTC'));

    expect($przedZmiana->oknoBezplatnegoOdwolaniaGodzin)->toBe(24)
        ->and($przedZmiana->wersja)->toBe(1)
        ->and($poZmianie->oknoBezplatnegoOdwolaniaGodzin)->toBe(48)
        ->and($poZmianie->wersja)->toBe(2);
});

it('rozlicza starą rezerwację po STAREJ regule, mimo zmiany konfiguracji', function (): void {
    // To jest cała istota zamrażania, sprawdzona end-to-end: rezerwacja
    // kupiona w sierpniu (okno 24 h), reguła zmieniona we wrześniu (48 h),
    // odwołanie w październiku 30 h przed wizytą.
    $rejestr = app(RejestrRegul::class);

    $zamrozona = $rejestr->obowiazujaceW(CarbonImmutable::parse('2026-08-15 10:00:00', 'UTC'));

    $rejestr->dodajWersje(
        ['okno_bezplatnego_odwolania_godzin' => 48],
        CarbonImmutable::parse('2026-09-01 00:00:00', 'UTC'),
        autor: 'koordynator',
        uzasadnienie: 'Wydłużenie okna.',
    );

    $termin = CarbonImmutable::parse('2026-10-10 14:00:00', 'Europe/Warsaw');

    $werdykt = OcenaAnulacji::oceń(
        $zamrozona,
        Sytuacja::PacjentOdwolujeWczesniej,
        $termin,
        $termin->subHours(30),
        kwotaZamrozonaGr: 14500,
    );

    // 30 h > 24 h (reguła zamrożona) → pełny zwrot, mimo że wg bieżącej
    // konfiguracji (48 h) zwrotu by nie było.
    expect($werdykt->kwotaZwrotuGr)->toBe(14500);

    // NEGATYW tej samej sytuacji: gdyby wołający sięgnął po BIEŻĄCĄ regułę,
    // pacjent straciłby pieniądze wbrew warunkom, na które się zgodził.
    $biezaca = $rejestr->obowiazujaceW(CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'));

    expect(OcenaAnulacji::oceń($biezaca, Sytuacja::PacjentOdwolujeWczesniej, $termin, $termin->subHours(30), 14500)->kwotaZwrotuGr)
        ->toBe(0);
});

it('przerywa, gdy nie ma żadnej obowiązującej wersji', function (): void {
    // Cicha podmiana na wartości domyślne byłaby najgorszym wyjściem:
    // rezerwacje zamroziłyby reguły, których nikt nie zatwierdził.
    DB::table('konfiguracja_regul')->delete();

    expect(fn () => app(RejestrRegul::class)->obowiazujace())
        ->toThrow(RuntimeException::class, 'Brak konfiguracji reguł');
});

it('nie pozwala wpisać wersji obowiązującej WSTECZ', function (): void {
    // U-9 z rundy 3: formalnie to był INSERT, więc dziennik „tylko dopisujemy"
    // wyglądał na nienaruszony. Skutek był jednak dokładnie taki, przed jakim
    // chroni CLAUDE.md §4: odpowiedź na pytanie „co obowiązywało 15 sierpnia"
    // ZMIENIAŁA SIĘ po dopisaniu wersji z datą wsteczną.
    $rejestr = app(RejestrRegul::class);

    $rejestr->dodajWersje(
        ['okno_bezplatnego_odwolania_godzin' => 48],
        CarbonImmutable::parse('2026-09-01 00:00:00', 'UTC'),
        autor: 'koordynator',
        uzasadnienie: 'Wydłużenie okna.',
    );

    $pytanie = CarbonImmutable::parse('2026-09-15 12:00:00', 'UTC');
    $przed = $rejestr->obowiazujaceW($pytanie)->oknoBezplatnegoOdwolaniaGodzin;

    expect(fn () => $rejestr->dodajWersje(
        ['okno_bezplatnego_odwolania_godzin' => 6],
        CarbonImmutable::parse('2026-08-20 00:00:00', 'UTC'),
        autor: 'kto-inny',
        uzasadnienie: 'Próba przepisania historii.',
    ))->toThrow(InvalidArgumentException::class, 'wcześniej niż poprzednia');

    // Kontrola liczy WARTOŚĆ, nie sam fakt wyjątku: odpowiedź na to samo
    // pytanie musi być identyczna jak przed próbą.
    expect($rejestr->obowiazujaceW($pytanie)->oknoBezplatnegoOdwolaniaGodzin)->toBe($przed)
        ->and(DB::table('konfiguracja_regul')->count())->toBe(2);
});
