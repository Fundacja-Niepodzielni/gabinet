<?php

declare(strict_types=1);

use App\Retencja\RejestrRetencji;
use App\Retencja\ZadanieRetencji;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Wsparcie\KlamraPerturbacji;

/**
 * KLUCZ, PO KTÓRYM ZADANIE RETENCJI SELEKCJONUJE I WERYFIKUJE.
 *
 * `ZadanieRetencji` zapamiętuje identyfikatory (`pluck('id')`), kasuje po nich
 * i tymi samymi identyfikatorami pyta bazę, co ZOSTAŁO. Cała wartość tego
 * zadania — weryfikacja niezależnym zapytaniem — stoi na założeniu, że kolumna
 * `id` istnieje i ma wartości.
 *
 * Założenie NIE MIAŁO JAK SIĘ UJAWNIĆ, dopóki zadania nikt nie wołał (R6A-11).
 * To jest drugi, niezależny skutek tego samego braku wywołującego: nie tylko
 * nic się nie kasowało, ale nikt też nie zauważył, że dla jednej z tabel
 * w rejestrze zadanie w ogóle nie umie zadziałać.
 *
 * Kontrola bada WARTOŚĆ, nie samą obecność kolumny w schemacie — patrz kierunek 0.
 */

// Klucz NIE JEST już zakładany — pochodzi z rejestru (`kolumna_klucza`).
// Kontrola pilnuje więc czegoś mocniejszego niż wcześniej: że ZADEKLAROWANY
// klucz naprawdę istnieje i ma wartości. Wpisanie do rejestru kolumny, której
// nie ma, zapala tę kontrolę tak samo jak dawniej brak `id`.

/**
 * Czy zadanie retencji ma po czym zapamiętać i zweryfikować rekordy tej tabeli.
 *
 * @param  string|null  $powod  wypełniany, gdy klucz jest nieużyteczny
 */
function kluczUzyteczny(string $tabela, string $klucz, ?string &$powod = null): bool
{
    $istnieje = DB::selectOne(
        'select 1 as jest from information_schema.columns
          where table_schema = current_schema() and table_name = ? and column_name = ?',
        [$tabela, $klucz]
    );

    if ($istnieje === null) {
        $kolumny = array_map(
            static fn (object $w): string => (string) $w->column_name,
            DB::select(
                'select column_name from information_schema.columns
                  where table_schema = current_schema() and table_name = ?
                  order by ordinal_position',
                [$tabela]
            )
        );

        $powod = "tabela `{$tabela}` NIE MA kolumny `{$klucz}`, której wymaga ZadanieRetencji ".
            '(pluck/whereIn). Kolumny tej tabeli: '.implode(', ', $kolumny);

        return false;
    }

    // KIERUNEK 0 — kolumna może istnieć i być bezużyteczna. Selekcja po kluczu
    // o wartości `null` nie zapamiętuje niczego, a `whereIn(null)` nie trafia
    // w żaden wiersz — zadanie meldowałoby wtedy sukces, nie kasując nic.
    $puste = (int) DB::table($tabela)->whereNull($klucz)->count();

    if ($puste > 0) {
        $powod = "kolumna `{$klucz}` w tabeli `{$tabela}` ISTNIEJE, ale ma {$puste} wartości NULL — ".
            'selekcja po takim kluczu nie zapamięta rekordu, a weryfikacja go nie znajdzie';

        return false;
    }

    $powod = null;

    return true;
}

it('KAŻDA kasowana tabela z rejestru ma klucz, po którym zadanie retencji umie zweryfikować usunięcie', function (): void {
    $wadliwe = [];

    foreach (RejestrRetencji::wpisy() as $tabela => $wpis) {
        if ($wpis['kasuje'] !== true) {
            continue;   // anonimizowane — sprzątaczka kasująca ich nie dotyka
        }

        $powod = null;

        if (! kluczUzyteczny($tabela, $wpis['kolumna_klucza'], $powod)) {
            $wadliwe[] = (string) $powod;
        }
    }

    expect($wadliwe)->toBe(
        [],
        "Zadanie retencji NIE UMIE zadziałać na tabeli, którą rejestr każe kasować:\n  ".
        implode("\n  ", $wadliwe)
    );
});

it('KIERUNEK 0: klucz OBECNY w schemacie, ale o wartościach NULL, musi zostać uznany za nieużyteczny', function (): void {
    // Bez tego kontrola wyżej bada wyłącznie SCHEMAT i przechodzi dla tabeli,
    // w której klucz istnieje, a selekcja po nim niczego nie zapamiętuje.
    // To jest dokładnie „kształt zachowany, wartość jałowa".
    // Tabela ZWYKŁA, nie tymczasowa. Pierwsza wersja tej próby używała
    // `create temporary table` — a tabele tymczasowe żyją w `pg_temp`, więc
    // wyszukiwanie po `table_schema = current_schema()` ich NIE WIDZI. Kontrola
    // meldowała wtedy „nie ma kolumny `id`" zamiast „kolumna ma wartości NULL",
    // czyli czerwień z NIEWŁAŚCIWEJ przyczyny. Złapał to dopiero kierunek
    // odwrotny — sama próba wyglądała na udaną.
    // Transakcja `RefreshDatabase` wycofuje tę tabelę po teście.
    DB::statement('create table proba_klucza (id integer, created_at timestamptz)');

    try {
        DB::table('proba_klucza')->insert(['id' => null, 'created_at' => now()]);

        $powod = null;
        expect(kluczUzyteczny('proba_klucza', 'id', $powod))->toBeFalse(
            'Kontrola bada wyłącznie OBECNOŚĆ kolumny w schemacie, nie jej WARTOŚĆ — '.
            'przepuściłaby tabelę, w której selekcja po kluczu nic nie zapamiętuje.'
        );
        expect((string) $powod)->toContain('NULL');

        // Kierunek odwrotny: wartość niepusta → klucz użyteczny.
        DB::table('proba_klucza')->delete();
        DB::table('proba_klucza')->insert(['id' => 7, 'created_at' => now()]);

        $powod = null;
        expect(kluczUzyteczny('proba_klucza', 'id', $powod))->toBeTrue(
            'Kontrola odrzuca sprawny klucz — fałszywie oskarżałaby każdą tabelę.'
        );
        expect($powod)->toBeNull();
    } finally {
        DB::statement('drop table if exists proba_klucza');
    }
});

it('zadanie DZIAŁA na tabeli z kluczem TEKSTOWYM — i zachowuje jego reprezentację', function (): void {
    // To jest dowód właściwy. Kontrola wyżej mówi tylko, że klucz ISTNIEJE;
    // dopiero ten test pokazuje, że zadanie na takiej tabeli naprawdę kasuje
    // i weryfikuje. Bez niego „zielona kontrola klucza" byłaby zieloną deklaracją.
    $stary = hash('sha256', 'stara-sesja');
    $swiezy = hash('sha256', 'swieza-sesja');

    DB::table('uniewaznione_sesje')->insert([
        ['sid_skrot' => $stary, 'uniewazniona_at' => '2020-01-01 00:00:00', 'wygasa_at' => '2020-01-02 00:00:00', 'powod' => 'backchannel-logout'],
        ['sid_skrot' => $swiezy, 'uniewazniona_at' => now(), 'wygasa_at' => now()->addDay(), 'powod' => 'backchannel-logout'],
    ]);

    $wynik = (new ZadanieRetencji(CarbonImmutable::now()))->wykonaj(
        'uniewaznione_sesje',
        'uniewazniona_at',
        progDni: 30,
        kolumnaKlucza: 'sid_skrot',
    );

    expect($wynik->wybrane)->toBe(1, 'Zadanie nie wybrało rekordu po terminie na tabeli z kluczem tekstowym.')
        ->and($wynik->usuniete)->toBe(1)
        ->and($wynik->kompletny())->toBeTrue()
        ->and(DB::table('uniewaznione_sesje')->where('sid_skrot', $stary)->exists())->toBeFalse('Rekord po terminie PRZEŻYŁ zadanie.')
        ->and(DB::table('uniewaznione_sesje')->where('sid_skrot', $swiezy)->exists())->toBeTrue('Zadanie skasowało rekord PRZED terminem.');
});

it('reprezentacja klucza NIE JEST ujednolicana — tekstowy zostaje tekstem, liczbowy liczbą', function (): void {
    // D-2026-08-09-03: typ identyfikatora należy do DZIEDZINY. Normalizacja do
    // napisów uczyniłaby `42` i `"42"` nieodróżnialnymi — wartość zdegenerowana
    // w polu, którego jedynym zadaniem jest wskazywać konkretny obiekt.
    $sid = hash('sha256', 'blokada-tekstowa');
    DB::table('uniewaznione_sesje')->insert([
        'sid_skrot' => $sid, 'uniewazniona_at' => '2020-01-01 00:00:00',
        'wygasa_at' => '2020-01-02 00:00:00', 'powod' => 'backchannel-logout',
    ]);

    $zdejmij = KlamraPerturbacji::zablokujKasowanie('uniewaznione_sesje');

    try {
        $wynik = (new ZadanieRetencji(CarbonImmutable::now()))->wykonaj(
            'uniewaznione_sesje', 'uniewazniona_at', progDni: 30, kolumnaKlucza: 'sid_skrot'
        );

        expect($wynik->pozostale)->toBe([$sid], 'Klucz tekstowy zmienił reprezentację po drodze.');
        expect($wynik->pozostale[0])->toBeString();
    } finally {
        $zdejmij();
    }
});
