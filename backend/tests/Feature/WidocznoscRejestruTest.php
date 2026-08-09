<?php

declare(strict_types=1);

use App\Retencja\RejestrRetencji;
use Illuminate\Support\Facades\Artisan;

/**
 * KAŻDY WPIS REJESTRU MUSI BYĆ GDZIEŚ WIDOCZNY.
 *
 * Znalezione przez helpdesk, potwierdzone własnym pomiarem: obie listy raportu
 * (`doWykonania`, `czekajaceNaOkres`) filtrowały po `kasuje === true`, więc wpis
 * z `kasuje=false` **i** okresem `null` wypadał z OBU. Komunikat „DŁUG WOBEC IOD"
 * wymieniał 4 z 7 tabel, a `pacjenci`, `rezerwacje` i `specjalisci` nie pojawiały
 * się NIGDZIE.
 *
 * Dotyczyło to `pacjenci`, których własny opis w rejestrze brzmi „DANE PACJENTÓW,
 * najwrażliwsze w całym systemie", a podstawa „RODO art. 9 — dane o zdrowiu".
 *
 * **Cisza jest mocniejsza niż dług, bo dług przynajmniej ma listę.**
 *
 * To jest R6A-11 o piętro wyżej: tam zadanie nie miało wywołującego, tu cała
 * kategoria danych nie miała pozycji w rejestrze braków.
 */
it('CZERWONA PRZED NAPRAWĄ: KAŻDA tabela rejestru pojawia się w raporcie, który czyta człowiek', function (): void {
    // Kontrola celowo pyta o WYJŚCIE ZADANIA, a nie o wewnętrzne listy klasy.
    // Powód: gdyby pytała o metodę, której jeszcze nie ma, jej czerwień brałaby
    // się z „Call to undefined method" — czyli z awarii przyrządu, nie z badanej
    // wady. Wyjście raportu istnieje dziś i istnieje po naprawie, więc ta sama
    // asercja mierzy jedno i drugie.
    Artisan::call('gabinet:retencja');
    $wyjscie = Artisan::output();

    $niewidoczne = [];

    foreach (array_keys(RejestrRetencji::wpisy()) as $tabela) {
        if (! str_contains($wyjscie, $tabela)) {
            $niewidoczne[] = $tabela;
        }
    }

    expect($niewidoczne)->toBe(
        [],
        'Tabele rejestru NIEWIDOCZNE w raporcie zadania: '.implode(', ', $niewidoczne)."\n".
        "Wpis, którego nie widać ani jako zadania, ani jako długu, jest CISZĄ — a cisza\n".
        "o tabeli z danymi RODO art. 9 jest gorsza niż jawny dług.\n".
        "Raport, który to pominął:\n".$wyjscie
    );
});

it('KIERUNEK 0: wpis BEZ pola `kasuje` ZAPALA, zamiast wypaść po cichu', function (): void {
    // „Brak dopasowania" nie ma prawa dawać wyniku pozytywnego. Klasyfikator
    // musi być TOTALNY: każdy wpis dostaje kategorię albo wywala się głośno.
    $ulomny = ['kolumna_pochodzenia' => 'created_at', 'okres_dni' => null];

    $blad = null;

    try {
        RejestrRetencji::kategoria($ulomny);
    } catch (Throwable $e) {
        $blad = $e->getMessage();
    }

    expect($blad)->not->toBeNull(
        'Wpis bez pola `kasuje` został zaklasyfikowany BEZ SŁOWA — nowa tabela wypadnie '.
        'z raportów przez przeoczenie, dokładnie tak jak `pacjenci`.'
    );
    expect((string) $blad)->toContain('kasuje');
});

it('KIERUNEK ODWROTNY: poprawny wpis dostaje kategorię — klasyfikator nie odmawia wszystkiego', function (): void {
    $poprawny = ['kasuje' => true, 'okres_dni' => 365];

    expect(RejestrRetencji::kategoria($poprawny))->toBe(RejestrRetencji::DO_WYKONANIA);
    expect(RejestrRetencji::kategoria(['kasuje' => true, 'okres_dni' => null]))->toBe(RejestrRetencji::CZEKA_NA_OKRES);
    expect(RejestrRetencji::kategoria(['kasuje' => false, 'okres_dni' => null]))->toBe(RejestrRetencji::POZA_KASOWANIEM);
});

it('kategorie są ROZŁĄCZNE i pokrywają rejestr w CAŁOŚCI', function (): void {
    $wpisy = RejestrRetencji::wpisy();
    $suma = count(RejestrRetencji::doWykonania())
        + count(RejestrRetencji::czekajaceNaOkres())
        + count(RejestrRetencji::pozaKasowaniem());

    // Bez tego trzy listy mogłyby się nakładać albo zostawiać dziurę, a suma
    // i tak by się „zgadzała" w oczach czytającego raport.
    expect($suma)->toBe(
        count($wpisy),
        sprintf('Suma kategorii (%d) != liczba wpisów (%d) — kategorie nie są podziałem.', $suma, count($wpisy))
    );
});

it('RAPORT ZADANIA wymienia tabele poza kasowaniem — nie tylko dług okresów', function (): void {
    $kod = Artisan::call('gabinet:retencja');
    $wyjscie = Artisan::output();

    expect($kod)->toBe(0);

    foreach (RejestrRetencji::pozaKasowaniem() as $tabela) {
        expect(str_contains($wyjscie, $tabela))->toBeTrue(
            "Tabela `{$tabela}` nie pojawia się w raporcie zadania. Jest anonimizowana, ".
            'mechanizmu anonimizacji NIE MA, a raport o niej milczy.'
        );
    }
});
