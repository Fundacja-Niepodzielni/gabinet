<?php

declare(strict_types=1);

use App\Reguly\OcenaAnulacji;
use App\Reguly\Sytuacja;
use App\Reguly\ZestawRegul;
use Carbon\CarbonImmutable;

/**
 * Znaleziska U-7 i U-10 z rundy 3 niezależnej weryfikacji.
 *
 * Weryfikator nie znalazł błędu w arytmetyce — arytmetyka była poprawna.
 * Znalazł BRAK GRANIC: `zwrot_procent` przyjmował dowolną liczbę, więc zapis
 * `500` w konfiguracji dawał zwrot pięciokrotnie większy od wpłaty. Kod, który
 * wg CLAUDE.md §1 jest jedyną instancją rozstrzygającą o pieniądzach, musi
 * odrzucać niemożliwe dane, a nie liczyć na nich dalej.
 *
 * Testy liczą KWOTY (CLAUDE.md §15), a nie sprawdzają, czy „poleciał wyjątek".
 */
/**
 * @return array<string, array{zwrot_procent: int, termin_wraca: bool, godzina_platna: bool}>
 */
function macierzZeZwrotem(int $procent): array
{
    $macierz = [];

    foreach (Sytuacja::cases() as $sytuacja) {
        $macierz[$sytuacja->value] = [
            'zwrot_procent' => $procent,
            'termin_wraca' => true,
            'godzina_platna' => false,
        ];
    }

    return $macierz;
}

it('odrzuca zwrot ponad 100% — dokładnie zapis, którym weryfikator wypłacił 725 zł z wpłaconych 145 zł', function (): void {
    expect(fn () => ZestawRegul::zTablicy(['macierz_odwolan' => macierzZeZwrotem(500)]))
        ->toThrow(InvalidArgumentException::class, 'musi mieścić się w 0..100');
});

it('odrzuca zwrot ujemny', function (): void {
    expect(fn () => ZestawRegul::zTablicy(['macierz_odwolan' => macierzZeZwrotem(-50)]))
        ->toThrow(InvalidArgumentException::class, 'musi mieścić się w 0..100');
});

it('odrzuca ujemną kwotę zamrożoną, zamiast liczyć z niej ujemny zwrot', function (): void {
    expect(fn () => OcenaAnulacji::oceń(
        ZestawRegul::wersjaZerowa(),
        Sytuacja::PacjentOdwolujeWczesniej,
        CarbonImmutable::parse('2026-10-10 12:00:00', 'UTC'),
        CarbonImmutable::parse('2026-10-01 12:00:00', 'UTC'),
        kwotaZamrozonaGr: -14500,
    ))->toThrow(InvalidArgumentException::class, 'nie może być ujemna');
});

it('nigdy nie zwraca więcej, niż pacjent zapłacił — nawet gdyby oba wcześniejsze zamki zawiodły', function (): void {
    // Trzeci zamek sprawdzamy na obiekcie zbudowanym z pominięciem walidacji
    // wejścia (refleksja), bo inaczej nie da się do niego dojść. To jedyny
    // sposób, by udowodnić, że jest czymś więcej niż martwym kodem.
    $konstruktor = new ReflectionMethod(ZestawRegul::class, '__construct');
    $konstruktor->setAccessible(true);

    $zdrowy = ZestawRegul::wersjaZerowa();
    /** @var ZestawRegul $chory */
    $chory = $konstruktor->getDeclaringClass()->newInstanceWithoutConstructor();

    $konstruktor->invoke(
        $chory,
        $zdrowy->wersja,
        $zdrowy->oknoBezplatnegoOdwolaniaGodzin,
        $zdrowy->limitPrzelozen,
        $zdrowy->najblizszyTerminGodzin,
        $zdrowy->kalendarzPacjentaDni,
        $zdrowy->horyzontWystawianiaDni,
        $zdrowy->przerwaMiedzyWizytamiMinut,
        $zdrowy->blokadaKoszykaMinut,
        $zdrowy->waznoscLinkuPlatnosciDni,
        $zdrowy->limitNiskoplatnychWizyt,
        $zdrowy->limitNiskoplatnychNaTydzien,
        $zdrowy->kredytZaOdsprzedanyTermin,
        $zdrowy->autoDomkniecieGodzin,
        macierzZeZwrotem(500),
    );

    $termin = CarbonImmutable::parse('2026-10-10 12:00:00', 'UTC');

    $werdykt = OcenaAnulacji::oceń(
        $chory,
        Sytuacja::PacjentOdwolujeWczesniej,
        $termin,
        $termin->subHours(48),
        kwotaZamrozonaGr: 14500,
    );

    expect($werdykt->kwotaZwrotuGr)->toBe(14500);
});

it('odrzuca macierz niepełną, zamiast dobierać brakujące sytuacje z kodu', function (): void {
    // Sedno CLAUDE.md §4: zamrożony zrzut ma być SAMOWYSTARCZALNY. Macierz
    // z jedną sytuacją oznaczała, że werdykt dla pozostałych siedmiu zależy
    // od bieżącej treści `Sytuacja::macierzDomyslna()` — czyli zmienia się
    // przy każdym wdrożeniu.
    expect(fn () => ZestawRegul::zTablicy([
        'macierz_odwolan' => [
            Sytuacja::PacjentOdwolujeWczesniej->value => [
                'zwrot_procent' => 100, 'termin_wraca' => true, 'godzina_platna' => false,
            ],
        ],
    ]))->toThrow(InvalidArgumentException::class, 'niepełna');
});

it('przerywa, gdy zamrożona macierz nie zna rozstrzygniętej sytuacji', function (): void {
    $konstruktor = new ReflectionMethod(ZestawRegul::class, '__construct');
    $konstruktor->setAccessible(true);

    $zdrowy = ZestawRegul::wersjaZerowa();
    /** @var ZestawRegul $chory */
    $chory = $konstruktor->getDeclaringClass()->newInstanceWithoutConstructor();

    $bezJednej = macierzZeZwrotem(100);
    unset($bezJednej[Sytuacja::PacjentOdwolujeWczesniej->value]);

    $konstruktor->invoke(
        $chory, $zdrowy->wersja, $zdrowy->oknoBezplatnegoOdwolaniaGodzin, $zdrowy->limitPrzelozen,
        $zdrowy->najblizszyTerminGodzin, $zdrowy->kalendarzPacjentaDni, $zdrowy->horyzontWystawianiaDni,
        $zdrowy->przerwaMiedzyWizytamiMinut, $zdrowy->blokadaKoszykaMinut, $zdrowy->waznoscLinkuPlatnosciDni,
        $zdrowy->limitNiskoplatnychWizyt, $zdrowy->limitNiskoplatnychNaTydzien, $zdrowy->kredytZaOdsprzedanyTermin,
        $zdrowy->autoDomkniecieGodzin, $bezJednej,
    );

    $termin = CarbonImmutable::parse('2026-10-10 12:00:00', 'UTC');

    expect(fn () => OcenaAnulacji::oceń($chory, Sytuacja::PacjentOdwolujeWczesniej, $termin, $termin->subHours(48), 14500))
        ->toThrow(InvalidArgumentException::class, 'nie zna sytuacji');
});
