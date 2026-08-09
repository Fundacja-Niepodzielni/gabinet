<?php

declare(strict_types=1);

use App\Retencja\ZadanieRetencji;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Wsparcie\KlamraPerturbacji;

/**
 * Retencja: czy rekord NAPRAWDĘ ZNIKA, nie czy został WYBRANY.
 *
 * Lekcja przekrojowa zespołu helpdesku. Kontrole retencji sprawdzają zwykle,
 * kogo zadanie wybiera do skasowania — i na tym poprzestają. RODO wymaga
 * jednak WYKONANIA, nie selekcji: poprawnie wybrany rekord, którego nikt nie
 * skasował, to dane pozostawione po terminie. Nic o tym nie krzyczy, bo
 * kontrola patrzy na listę, nie na bazę.
 *
 * `RetencjaTest` (obok) pilnuje rejestru: że każda tabela ma decyzję o
 * retencji i kolumnę, po której da się filtrować. Ten plik pilnuje czegoś
 * innego i mocniejszego — że po przebiegu rekordu FIZYCZNIE NIE MA.
 *
 * Testy liczą WIERSZE W BAZIE (CLAUDE.md §15), nie wartość zwróconą przez
 * `delete()`: ta mówi, na ilu wierszach zadziałało polecenie, a nie jaki jest
 * stan bazy po nim. Wyzwalacz, reguła albo wycofana transakcja potrafią ją
 * uczynić nieprawdziwą — i dokładnie tego szukamy.
 */
function zgodaZDaty(string $data, int $pacjentId = 1): int
{
    return (int) DB::table('zgody')->insertGetId([
        'pacjent_id' => $pacjentId,
        'rodzaj' => 'regulamin',
        'wersja_dokumentu' => '1.0',
        'udzielona' => true,
        'rozstrzygnieta_at' => $data,
        'ip' => '127.0.0.1',
        'created_at' => $data,
    ]);
}

beforeEach(function (): void {
    DB::table('zgody')->delete();

    DB::table('pacjenci')->insertOrIgnore([
        'id' => 1,
        'keycloak_sub' => null,
        'imie' => 'x',
        'nazwisko' => 'x',
        'email' => 'x',
        'telefon' => 'x',
        'email_skrot' => hash('sha256', 'x'),
        'strefa_czasowa' => 'Europe/Warsaw',
        'created_at' => CarbonImmutable::now(),
        'updated_at' => CarbonImmutable::now(),
    ]);
});

it('rekord po terminie NAPRAWDĘ znika z bazy, nie tylko trafia na listę', function (): void {
    $teraz = CarbonImmutable::parse('2026-08-08 12:00:00', 'UTC');

    $stara = zgodaZDaty('2020-01-01 10:00:00');
    $swieza = zgodaZDaty('2026-08-01 10:00:00');

    $wynik = (new ZadanieRetencji($teraz))->wykonaj('zgody', 'created_at', progDni: 365);

    // Selekcja — konieczna, ale niewystarczająca.
    expect($wynik->wybrane)->toBe(1);

    // WYKONANIE — o to chodzi. Pytamy BAZĘ, nie zadanie.
    expect(DB::table('zgody')->where('id', $stara)->exists())->toBeFalse(
        'Rekord po terminie PRZEŻYŁ zadanie retencyjne — dane zostały po terminie.'
    );

    expect($wynik->kompletny())->toBeTrue()
        ->and($wynik->pozostale)->toBe([]);

    // Kierunek odwrotny: świeży rekord ma zostać. Bez tego „wszystko znika"
    // przechodzi także dla zadania kasującego całą tabelę.
    expect(DB::table('zgody')->where('id', $swieza)->exists())->toBeTrue(
        'Zadanie skasowało rekord PRZED terminem.'
    );
});

it('zgłasza rozbieżność, gdy rekord został WYBRANY, ale NIE ZNIKNĄŁ', function (): void {
    // Sedno lekcji. Blokujemy kasowanie na poziomie BAZY — regułą, o której
    // zadanie nic nie wie — i sprawdzamy, że wynik to widzi.
    //
    // Perturbacja celuje w stan, którego system sam nie przywróci (reguła
    // z nagłówka `perturbacje.sh`): reguła zostaje w bazie do końca testu.
    $teraz = CarbonImmutable::parse('2026-08-08 12:00:00', 'UTC');
    $stara = zgodaZDaty('2020-01-01 10:00:00');

    // KLAMRA PERTURBACJI (klasa przekrojowa P2). Blokada jest CICHA — `DELETE`
    // kończy się sukcesem i nie robi nic — więc reguła zostawiona w bazie byłaby
    // cichą blokadą kasowania danych osobowych. `KlamraPerturbacji` robi trzy
    // rzeczy, których wcześniej tu nie było: skan wstępny PRZED startem, ODMOWĘ
    // przy znalezionej pozostałości i zdjęcie w `finally`.
    $zdejmijBlokade = KlamraPerturbacji::zablokujKasowanie('zgody');

    try {
        $wynik = (new ZadanieRetencji($teraz))->wykonaj('zgody', 'created_at', progDni: 365);

        // Zadanie WYBRAŁO poprawnie…
        expect($wynik->wybrane)->toBe(1)
            // …ale rekord został. Kontrola pytająca tylko o selekcję
            // meldowałaby tu sukces.
            ->and($wynik->usuniete)->toBe(0)
            ->and($wynik->pozostale)->toBe([$stara])
            ->and($wynik->kompletny())->toBeFalse(
                'Zadanie melduje komplet, choć rekord po terminie ZOSTAŁ w bazie.'
            );

        expect(DB::table('zgody')->where('id', $stara)->exists())->toBeTrue(
            'Dowód mutacji: kasowanie miało być zablokowane, a rekord zniknął.'
        );
    } finally {
        $zdejmijBlokade();
    }
});

it('nie melduje sukcesu przy pustym przebiegu — zero wybranych to zero, nie „gotowe"', function (): void {
    // Pusty przebieg jest poprawny, ale nie może być nieodróżnialny od
    // udanego kasowania: to ta sama rodzina co pusta suita testów.
    $teraz = CarbonImmutable::parse('2026-08-08 12:00:00', 'UTC');
    zgodaZDaty('2026-08-01 10:00:00');

    $wynik = (new ZadanieRetencji($teraz))->wykonaj('zgody', 'created_at', progDni: 365);

    expect($wynik->wybrane)->toBe(0)
        ->and($wynik->usuniete)->toBe(0)
        ->and(DB::table('zgody')->count())->toBe(1);
});

it('KLAMRA: perturbacja ODMAWIA startu, gdy blokada została po poprzednim przebiegu', function (): void {
    // PERTURBACJA NA PRZYRZĄD, nie na przedmiot. Bez niej klamra jest deklaracją.
    //
    // Odtwarzamy dokładnie sytuację, przed którą klamra broni: poprzedni przebieg
    // zginął, zanim zdjął regułę, więc kasowanie w tabeli jest CICHO zablokowane.
    // Perturbacja, która by tego nie zauważyła, uruchomiłaby się na martwej bazie
    // i zameldowała sukces — a przy okazji zostawiła dane osobowe nieusuwalne.
    $regula = KlamraPerturbacji::nazwaReguly('zgody');

    DB::statement("CREATE RULE {$regula} AS ON DELETE TO zgody DO INSTEAD NOTHING");

    try {
        // Dowód mutacji: pozostałość NAPRAWDĘ jest w bazie, pytamy magazyn.
        expect(KlamraPerturbacji::regulaIstnieje($regula))->toBeTrue(
            'Pozostałość nie powstała — test nie mierzy tego, co deklaruje.'
        );

        $odmowa = null;

        // PUNKT ZAPISU. PostgreSQL unieważnia CAŁĄ transakcję po błędnym
        // zapytaniu, a suita biegnie w jednej transakcji (`RefreshDatabase`).
        // Bez SAVEPOINT-u ten test padał na SPRZĄTANIU (`25P02`), a nie na
        // asercji — czyli mierzył własne zanieczyszczenie zamiast tego, czy
        // klamra odmówiła. Zmierzone: dokładnie ta pomyłka wyszła przy pierwszym
        // przebiegu perturbacji tej kontroli.
        try {
            probaZerwania(static fn () => KlamraPerturbacji::zablokujKasowanie('zgody'));
        } catch (Throwable $e) {
            // `Throwable`, nie `RuntimeException`: gdyby klamra przestała
            // odmawiać, `CREATE RULE` na istniejącej regule rzuci `QueryException`
            // i test też by zczerwieniał — ale Z INNEGO POWODU niż badany.
            // Łapiemy wszystko i rozstrzygamy TREŚCIĄ komunikatu, żeby czerwień
            // pochodziła z braku odmowy, a nie z przypadkowego błędu bazy.
            $odmowa = $e->getMessage();
        }

        expect($odmowa)->not->toBeNull('KLAMRA NIE ODMÓWIŁA: perturbacja ruszyła mimo pozostałości.')
            ->and($odmowa)->toContain('ODMOWA STARTU')
            // Komunikat ma UCZYĆ naprawy, nie tylko odmawiać — inaczej najtańszą
            // reakcją będzie skasowanie reguły bez sprawdzenia, co jeszcze zostało.
            ->and($odmowa)->toContain('DROP RULE')
            ->and($odmowa)->toContain('CICHO ZABLOKOWANE');
    } finally {
        DB::statement("DROP RULE IF EXISTS {$regula} ON zgody");
    }

    // Kierunek odwrotny: po sprzątnięciu pozostałości klamra MA wpuścić.
    $zdejmij = KlamraPerturbacji::zablokujKasowanie('zgody');

    try {
        expect(KlamraPerturbacji::regulaIstnieje($regula))->toBeTrue();
    } finally {
        $zdejmij();
    }

    expect(KlamraPerturbacji::regulaIstnieje($regula))->toBeFalse(
        'Klamra nie zdjęła blokady — dokładnie ta awaria, przed którą broni.'
    );
});
