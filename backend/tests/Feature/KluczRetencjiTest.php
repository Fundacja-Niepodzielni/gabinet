<?php

declare(strict_types=1);

use App\Retencja\RejestrRetencji;
use Illuminate\Support\Facades\DB;

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

/** Nazwa kolumny, którą `ZadanieRetencji` zakłada. Jedno miejsce, żeby dało się policzyć. */
const KLUCZ_ZAKLADANY = 'id';

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

        if (! kluczUzyteczny($tabela, KLUCZ_ZAKLADANY, $powod)) {
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
