<?php

declare(strict_types=1);

use App\Retencja\RejestrRetencji;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Wsparcie\KlamraPerturbacji;

/**
 * R6A-11: `ZadanieRetencji` NIE MIAŁO ANI JEDNEGO WYWOŁUJĄCEGO.
 *
 * Ten plik pilnuje twierdzenia, którego NIE pilnowała żadna dotychczasowa
 * kontrola retencji: **że zadanie jest URUCHAMIANE**. `RetencjaWykonanieTest`
 * dowodzi, że rekord po terminie ZNIKA — ale wołając zadanie sam, ręcznie.
 * Zielone z niego było więc prawdziwe i zarazem bezużyteczne: na żywym systemie
 * nikt tego zadania nie wołał i nic się nie kasowało.
 *
 * KASOWANIE i URUCHAMIANIE to dwa różne twierdzenia i mają zawodzić OSOBNO.
 * Dlatego kontrola „chodzi" celuje w HARMONOGRAM, a nie w kod zadania.
 */
/** @return list<array{polecenie: string, wyrazenie: string}> */
function zaplanowaneZadania(Schedule $harmonogram): array
{
    $wynik = [];

    foreach ($harmonogram->events() as $zdarzenie) {
        $wynik[] = [
            'polecenie' => (string) $zdarzenie->command,
            'wyrazenie' => (string) $zdarzenie->expression,
        ];
    }

    return $wynik;
}

/**
 * Własny wytwórca zgody, celowo NIE współdzielony z `RetencjaWykonanieTest`.
 * Funkcje PHP są globalne, ale kolejność wczytania plików testowych nie jest
 * gwarantowana — kontrola zależna od cudzego pliku bywa zielona przez przypadek.
 */
function zgodaHarmonogramu(string $data, int $pacjentId = 1): int
{
    DB::table('pacjenci')->insertOrIgnore([
        'id' => $pacjentId,
        'keycloak_sub' => null,
        'imie' => 'x',
        'nazwisko' => 'x',
        'email' => 'harmonogram@example.test',
        'telefon' => 'x',
        'created_at' => $data,
        'updated_at' => $data,
    ]);

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

function harmonogramZawiera(Schedule $harmonogram, string $sygnatura): bool
{
    foreach (zaplanowaneZadania($harmonogram) as $zadanie) {
        if (str_contains($zadanie['polecenie'], $sygnatura)) {
            return true;
        }
    }

    return false;
}

it('KONTROLA URUCHOMIENIA: retencja JEST w harmonogramie — pytamy harmonogram, nie kod zadania', function (): void {
    $harmonogram = app(Schedule::class);
    $zaplanowane = zaplanowaneZadania($harmonogram);

    // Pustka to błąd, nie zero: harmonogram bez zadań znaczy, że nie doczytał
    // `routes/console.php`, a wtedy kontrola niżej mierzyłaby własną awarię.
    expect($zaplanowane)->not->toBe(
        [],
        'Harmonogram jest PUSTY — kontrola mierzy własną awarię, nie stan systemu.'
    );

    expect(harmonogramZawiera($harmonogram, 'gabinet:retencja'))->toBeTrue(
        "Zadanie retencji NIE JEST zaplanowane — czyli nic się nie kasuje.\n".
        'Zaplanowane dziś: '.implode(', ', array_column($zaplanowane, 'polecenie'))
    );
});

it('KIERUNEK 0: harmonogram z PUSTĄ listą zadań musi dać odpowiedź NEGATYWNĄ', function (): void {
    // Bez tego kontrola wyżej przechodzi także wtedy, gdy `harmonogramZawiera()`
    // zwraca `true` zawsze. „Zero zaplanowanych" to nie to samo co „nic nie
    // wymaga planowania" — i tylko ta asercja odróżnia te dwa światy.
    $pusty = new Schedule;

    expect(zaplanowaneZadania($pusty))->toBe([], 'Świeży harmonogram nie jest pusty — materiał kontroli jest skażony.');

    expect(harmonogramZawiera($pusty, 'gabinet:retencja'))->toBeFalse(
        'Kontrola twierdzi, że zadanie jest zaplanowane W PUSTYM harmonogramie — '.
        'czyli nie bada harmonogramu i jej zielone nic nie znaczy.'
    );
});

it('FAIL-CLOSED: tabela o NIEUSTALONYM okresie nie jest kasowana, i mówi się o tym głośno', function (): void {
    // Domyślnie WSZYSTKIE okresy są `null` (czekają na IOD), więc zbiór do
    // wykonania jest pusty, a dług — niepusty.
    config(['retencja.okresy_dni' => array_fill_keys(array_keys(RejestrRetencji::wpisy()), null)]);

    expect(RejestrRetencji::doWykonania())->toBe([], 'Zadanie chce kasować tabelę o nieustalonym okresie.');
    expect(RejestrRetencji::czekajaceNaOkres())->not->toBe([], 'Dług wobec IOD zniknął po cichu.');

    $kod = Artisan::call('gabinet:retencja');
    $wyjscie = Artisan::output();

    // `toContain` przyjmuje KOLEJNE SZUKANE NAPISY, nie komunikat — pierwsza
    // wersja tej asercji podawała komunikat jako drugą igłę i szukała go
    // w wyjściu. Komunikat niesiemy przez `toBeTrue`, żeby nie zgubić powodu.
    expect($kod)->toBe(0);
    expect(str_contains($wyjscie, 'DŁUG WOBEC IOD'))->toBeTrue(
        'Pominięcie tabel jest CICHE — w logu nie do odróżnienia od „nie było czego usuwać".'
    );
});

it('PARA Z KLAMRĄ: przy zablokowanym kasowaniu URUCHOMIENIE zostaje zielone, a KASOWANIE czerwienieje', function (): void {
    // Dwa twierdzenia, dwie kontrole, dwa niezależne wyniki. Gdyby zawodziły
    // razem, nie dałoby się odróżnić „zadanie nie biegło" od „zadanie biegło
    // i nie skasowało" — a to jest dokładnie różnica, którą R6A-11 obnażył.
    config(['retencja.okresy_dni.zgody' => 365]);

    $stara = zgodaHarmonogramu('2020-01-01 00:00:00');
    expect(DB::table('zgody')->where('id', $stara)->exists())->toBeTrue('Test nie ma czego chronić.');

    $zdejmijBlokade = KlamraPerturbacji::zablokujKasowanie('zgody');

    try {
        $kod = Artisan::call('gabinet:retencja');
        $wyjscie = Artisan::output();

        // (1) KONTROLA URUCHOMIENIA — ZIELONA. Zadanie przebiegło po tabeli.
        expect(str_contains($wyjscie, 'zgody:'))->toBeTrue(
            'Zadanie NIE WESZŁO na tabelę — mierzylibyśmy nieuruchomienie, nie niekasowanie.'
        );

        // (2) KONTROLA KASOWANIA — CZERWONA. Rekord przeżył własne zadanie.
        expect($kod)->toBe(
            1,
            'Zadanie zameldowało SUKCES, choć kasowanie było zablokowane — '.
            'liczba z `delete()` została wzięta za stan bazy.'
        );
        expect($wyjscie)->toContain('PRZEŻYŁY zadanie retencyjne');
        expect(DB::table('zgody')->where('id', $stara)->exists())->toBeTrue(
            'Blokada nie zadziałała — perturbacja nie mierzy tego, co deklaruje.'
        );
    } finally {
        $zdejmijBlokade();
    }
});

it('po zdjęciu blokady to samo zadanie KASUJE i melduje sukces — kierunek odwrotny', function (): void {
    config(['retencja.okresy_dni.zgody' => 365]);

    $stara = zgodaHarmonogramu('2020-01-01 00:00:00');
    $swieza = zgodaHarmonogramu(now()->toDateTimeString());

    $kod = Artisan::call('gabinet:retencja');

    expect($kod)->toBe(0, 'Zadanie melduje porażkę przy sprawnym kasowaniu.')
        ->and(DB::table('zgody')->where('id', $stara)->exists())->toBeFalse('Rekord po terminie PRZEŻYŁ zadanie.')
        ->and(DB::table('zgody')->where('id', $swieza)->exists())->toBeTrue('Zadanie skasowało rekord PRZED terminem.');
});

it('ślad wykonania powstaje TAKŻE gdy nie było czego usuwać', function (): void {
    config(['retencja.okresy_dni.zgody' => 365]);
    DB::table('zgody')->delete();

    $kod = Artisan::call('gabinet:retencja');

    // „Nic nie usunięto" i „nie biegło" to dwa różne stany. Bez śladu
    // wykonania wyglądają w logu identycznie — i to jest cichy tryb awarii
    // zadań okresowych, ten sam, przed którym broni puls harmonogramu.
    expect($kod)->toBe(0)
        ->and(Artisan::output())->toContain('zgody: wybrane 0');
});
