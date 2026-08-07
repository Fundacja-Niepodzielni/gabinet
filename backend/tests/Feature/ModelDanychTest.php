<?php

declare(strict_types=1);

use App\Reguly\RejestrRegul;
use App\Wsparcie\Typy;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Model danych F1 — testy sprawdzają BAZĘ, nie deklaracje w migracji.
 *
 * Trzy reguły z `CLAUDE.md` są regułami bazy danych i tylko baza może
 * udowodnić, że działają: unikalne ograniczenie na termin (§6), zamrożenie
 * kwoty i reguły (§4), ślad audytowy tylko do dopisywania (§9, tu w wersji
 * dla `zdarzenia_rezerwacji`).
 */
function specjalista(string $sub = 'sub-spec-1'): int
{
    return (int) DB::table('specjalisci')->insertGetId([
        'keycloak_sub' => $sub,
        'imie' => 'Anna',
        'nazwisko' => 'Testowa',
        'stawka_pelna_gr' => 14500,
        'przyjmuje_pacjentow' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function usluga(string $kod = 'pelnoplatna', string $konto = 'komercja', int $prowizjaBp = 2000): int
{
    return (int) DB::table('uslugi')->insertGetId([
        'kod' => $kod,
        'nazwa' => 'Konsultacja',
        'minuty' => 50,
        'model_ceny' => 'widelki',
        'widelki_gr' => json_encode([11500, 12500, 13500, 14500]),
        'konto_stripe' => $konto,
        'prowizja_bp' => $prowizjaBp,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function pacjent(?string $sub = null): int
{
    return (int) DB::table('pacjenci')->insertGetId([
        'keycloak_sub' => $sub,
        'imie' => Crypt::encryptString('Maria'),
        'email' => Crypt::encryptString('maria@przyklad.test'),
        'telefon' => Crypt::encryptString('+48111222333'),
        'email_skrot' => hash('sha256', 'maria@przyklad.test'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

/**
 * Uruchamia próbę w ZAGNIEŻDŻONEJ transakcji.
 *
 * PostgreSQL po naruszeniu ograniczenia unieważnia całą transakcję: każde
 * kolejne zapytanie pada na „current transaction is aborted". Savepoint
 * pozwala sprawdzić, że baza odrzuciła zapis, i pracować dalej.
 */
function probaWTransakcji(Closure $proba): void
{
    DB::beginTransaction();

    try {
        $proba();
        DB::commit();
    } catch (Throwable $e) {
        DB::rollBack();
        throw $e;
    }
}

/**
 * @param  array<string, mixed>  $nadpisania
 */
function rezerwacja(array $nadpisania = []): string
{
    $reguly = app(RejestrRegul::class)->obowiazujace();
    $numer = (string) Str::uuid();

    DB::table('rezerwacje')->insert(array_merge([
        'numer' => $numer,
        'pacjent_id' => pacjent(),
        'specjalista_id' => specjalista('sub-'.Str::random(8)),
        'usluga_id' => usluga('kod-'.Str::random(8)),
        'termin' => now()->addDays(3),
        'czas_trwania_minut' => 50,
        'status' => 'oplacona',
        'kwota_zamrozona_gr' => 14500,
        'regula_anulacji_zamrozona' => json_encode($reguly->doTablicy()),
        'wersja_regul' => $reguly->wersja,
        'wersja_regulaminu' => '2026-08-01',
        'konto_stripe' => 'komercja',
        'prowizja_bp_zamrozona' => 2000,
        'created_at' => now(),
        'updated_at' => now(),
    ], $nadpisania));

    return $numer;
}

// ---------------------------------------------------------------------------
// WSPÓŁBIEŻNOŚĆ — reguła bazy, nie reguła kodu
// ---------------------------------------------------------------------------

it('nie pozwala BAZIE zapisać dwóch rezerwacji na ten sam termin u tego samego specjalisty', function (): void {
    // CLAUDE.md §6. Warunek w kodzie nie wystarcza: przy PHP-FPM dwa żądania
    // sprawdzają wolny termin jednocześnie i oba widzą, że jest wolny.
    $specjalistaId = specjalista('sub-kolizja');
    $uslugaId = usluga('kolizja');
    $termin = now()->addDays(5);

    rezerwacja(['specjalista_id' => $specjalistaId, 'usluga_id' => $uslugaId, 'termin' => $termin]);

    // PostgreSQL unieważnia CAŁĄ transakcję po naruszeniu ograniczenia, więc
    // próbę izolujemy w zagnieżdżonej transakcji (savepoint). Bez tego kolejne
    // zapytanie pada na „current transaction is aborted" i test raportuje
    // niewłaściwą przyczynę.
    expect(fn () => probaWTransakcji(fn () => rezerwacja([
        'specjalista_id' => $specjalistaId,
        'usluga_id' => $uslugaId,
        'termin' => $termin,
    ])))->toThrow(QueryException::class);

    expect(DB::table('rezerwacje')->where('specjalista_id', $specjalistaId)->count())->toBe(1);
});

it('pozwala na ten sam termin u RÓŻNYCH specjalistów', function (): void {
    // Ograniczenie ma dotyczyć pary (specjalista, termin), nie samego terminu —
    // inaczej 111 specjalistów dzieliłoby jeden kalendarz.
    $termin = now()->addDays(6);

    rezerwacja(['specjalista_id' => specjalista('sub-a'), 'termin' => $termin]);
    rezerwacja(['specjalista_id' => specjalista('sub-b'), 'termin' => $termin]);

    expect(DB::table('rezerwacje')->where('termin', $termin)->count())->toBe(2);
});

// ---------------------------------------------------------------------------
// ZAMRAŻANIE — zmiana cennika nie dotyka wizyt już opłaconych
// ---------------------------------------------------------------------------

it('zachowuje kwotę i regułę z dnia zakupu mimo zmiany cennika i reguł', function (): void {
    $uslugaId = usluga('zamrozenie');
    $numer = rezerwacja(['usluga_id' => $uslugaId, 'kwota_zamrozona_gr' => 14500]);

    // Fundacja podnosi cennik i wydłuża okno odwołania.
    DB::table('uslugi')->where('id', $uslugaId)->update(['widelki_gr' => json_encode([15500])]);
    app(RejestrRegul::class)->dodajWersje(
        ['okno_bezplatnego_odwolania_godzin' => 48],
        CarbonImmutable::now()->addDay(),
        autor: 'koordynator',
        uzasadnienie: 'Test wstecznego działania.',
    );

    $wiersz = Typy::mapa((array) DB::table('rezerwacje')->where('numer', $numer)->first());
    $zamrozona = Typy::mapa(json_decode(Typy::napis($wiersz['regula_anulacji_zamrozona'] ?? null), true));

    expect(Typy::liczba($wiersz['kwota_zamrozona_gr'] ?? null))->toBe(14500)
        ->and(Typy::liczba($zamrozona['okno_bezplatnego_odwolania_godzin'] ?? null))->toBe(24)
        ->and(Typy::liczba($wiersz['wersja_regul'] ?? null))->toBe(1);
});

it('zamraża pełny ZRZUT reguł, nie samą referencję do wersji', function (): void {
    // CLAUDE.md §4. Zrzut musi być samowystarczalny: skasowanie wiersza
    // konfiguracji nie może pozbawić rezerwacji jej reguł.
    $numer = rezerwacja();

    DB::table('konfiguracja_regul')->delete();

    $wiersz = Typy::mapa((array) DB::table('rezerwacje')->where('numer', $numer)->first());
    $zamrozona = Typy::mapa(json_decode(Typy::napis($wiersz['regula_anulacji_zamrozona'] ?? null), true));

    expect($zamrozona)->toHaveKeys([
        'okno_bezplatnego_odwolania_godzin',
        'limit_przelozen',
        'macierz_odwolan',
        'limit_niskoplatnych_wizyt',
    ]);
});

it('zamraża flagę konta Stripe i prowizję razem z kwotą', function (): void {
    // Usługa może zmienić przypisanie do konta; rozliczenie starej rezerwacji
    // nie może (CLAUDE.md §3 — osobna rekoncyliacja per konto).
    $uslugaId = usluga('fundacyjna', 'fundacja', 0);
    $numer = rezerwacja(['usluga_id' => $uslugaId, 'konto_stripe' => 'fundacja', 'prowizja_bp_zamrozona' => 0]);

    DB::table('uslugi')->where('id', $uslugaId)->update(['konto_stripe' => 'komercja', 'prowizja_bp' => 2000]);

    $wiersz = Typy::mapa((array) DB::table('rezerwacje')->where('numer', $numer)->first());

    expect(Typy::napis($wiersz['konto_stripe'] ?? null))->toBe('fundacja')
        ->and(Typy::liczba($wiersz['prowizja_bp_zamrozona'] ?? null))->toBe(0);
});

// ---------------------------------------------------------------------------
// DANE WRAŻLIWE — szyfrowanie realne, nie deklarowane
// ---------------------------------------------------------------------------

it('trzyma dane kontaktowe pacjenta zaszyfrowane w SUROWYM wierszu', function (): void {
    // W-1 z DPIA. Czytamy wiersz z pominięciem warstwy modelu — gdyby kolumna
    // trzymała tekst jawny, ten test by tego nie zauważył przy odczycie przez
    // Eloquenta z castem `encrypted`.
    pacjent();

    $wiersz = Typy::mapa((array) DB::table('pacjenci')->first());

    foreach (['imie', 'email', 'telefon'] as $kolumna) {
        $surowa = Typy::napis($wiersz[$kolumna] ?? null);

        expect($surowa)->not->toContain('Maria')
            ->and($surowa)->not->toContain('maria@przyklad.test')
            ->and($surowa)->not->toContain('+48111222333')
            // …i musi dać się odszyfrować, żeby test nie przechodził
            // przy kolumnie wypełnionej śmieciem.
            ->and(Crypt::decryptString($surowa))->toBeString();
    }
});

it('pozwala odnaleźć pacjenta-gościa po skrócie e-maila, bez odszyfrowywania', function (): void {
    pacjent();

    $skrot = hash('sha256', 'maria@przyklad.test');

    expect(DB::table('pacjenci')->where('email_skrot', $skrot)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// ŚLAD AUDYTOWY — tylko do dopisywania, egzekwowane przez BAZĘ
// ---------------------------------------------------------------------------

it('odrzuca UPDATE i DELETE na śladzie audytowym NA POZIOMIE BAZY', function (string $operacja): void {
    // W-5 z DPIA i wzorzec dla dziennika decyzji z F5 (CLAUDE.md §9).
    // Reguła egzekwowana wyzwalaczem, nie pamięcią autora kontrolera.
    $numer = rezerwacja();
    $rezerwacjaId = Typy::liczba(Typy::mapa((array) DB::table('rezerwacje')->where('numer', $numer)->first())['id'] ?? null);

    DB::table('zdarzenia_rezerwacji')->insert([
        'rezerwacja_id' => $rezerwacjaId,
        'aktor_rodzaj' => 'system',
        'typ' => 'utworzona',
        'created_at' => now(),
    ]);

    $proba = match ($operacja) {
        'update' => fn () => DB::table('zdarzenia_rezerwacji')->update(['typ' => 'podmienione']),
        default => fn () => DB::table('zdarzenia_rezerwacji')->delete(),
    };

    expect(fn () => probaWTransakcji($proba))->toThrow(QueryException::class);
    expect(DB::table('zdarzenia_rezerwacji')->count())->toBe(1);
})->with(['update', 'delete']);

it('pozwala dopisywać kolejne zdarzenia', function (): void {
    // NEGATYW poprzedniego: wyzwalacz ma blokować zmiany, a NIE zapisy.
    $numer = rezerwacja();
    $rezerwacjaId = Typy::liczba(Typy::mapa((array) DB::table('rezerwacje')->where('numer', $numer)->first())['id'] ?? null);

    foreach (['utworzona', 'oplacona', 'przypomnienie_wyslane'] as $typ) {
        DB::table('zdarzenia_rezerwacji')->insert([
            'rezerwacja_id' => $rezerwacjaId,
            'aktor_rodzaj' => 'system',
            'typ' => $typ,
            'created_at' => now(),
        ]);
    }

    expect(DB::table('zdarzenia_rezerwacji')->count())->toBe(3);
});

// ---------------------------------------------------------------------------
// IDENTYFIKATORY I CZAS
// ---------------------------------------------------------------------------

it('nadaje rezerwacjom numery NIEODGADYWALNE, nie kolejne', function (): void {
    // W-11 z DPIA: stare `NP-` + numer arytmetyczny pozwalał czytać cudze
    // rezerwacje przez podmianę cyfry (spec M6/5).
    $numery = [];

    for ($i = 0; $i < 5; $i++) {
        $numery[] = rezerwacja(['termin' => now()->addDays(10 + $i)]);
    }

    expect($numery)->toHaveCount(5)
        ->and(array_unique($numery))->toHaveCount(5);

    foreach ($numery as $numer) {
        expect($numer)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
    }
});

it('trzyma WSZYSTKIE kolumny czasowe jako timestamptz', function (): void {
    // CLAUDE.md §5. `timestamp without time zone` przy dobach 23/25-godzinnych
    // daje ciche przesunięcia — a od nich zależy granica bezpłatnego odwołania.
    // Wykluczamy tabele techniczne frameworka (`failed_jobs` itd.) — ich
    // schematu nie projektujemy i nie niosą danych domenowych. Reguła dotyczy
    // TABEL GABINETU, więc lista jest jawna: nowa tabela domenowa wchodzi
    // do sprawdzenia automatycznie, bo nie ma jej w wyjątkach.
    $techniczne = ['migrations', 'failed_jobs', 'jobs', 'job_batches', 'cache', 'cache_locks', 'sessions'];

    $bezStrefy = DB::select(<<<'SQL'
        select table_name, column_name
        from information_schema.columns
        where table_schema = 'public'
          and data_type = 'timestamp without time zone'
        order by table_name, column_name
    SQL);

    $lista = [];

    foreach ($bezStrefy as $wiersz) {
        $dane = Typy::mapa((array) $wiersz);
        $tabela = Typy::napis($dane['table_name'] ?? null);

        if (in_array($tabela, $techniczne, true)) {
            continue;
        }

        $lista[] = $tabela.'.'.Typy::napis($dane['column_name'] ?? null);
    }

    expect($lista)->toBe([], 'Kolumny bez strefy czasowej: '.implode(', ', $lista));
});

it('sprawdza schemat, który realnie istnieje', function (): void {
    // Asercja „miałem czego szukać" — bez niej test wyżej przechodzi
    // także przy pustej bazie.
    $tabele = ['uslugi', 'specjalisci', 'pacjenci', 'zgody', 'rezerwacje', 'zdarzenia_rezerwacji'];

    foreach ($tabele as $tabela) {
        expect(Schema::hasTable($tabela))->toBeTrue("Brak tabeli {$tabela}");
    }
});
