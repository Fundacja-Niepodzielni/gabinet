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

/**
 * Pełny zrzut reguł z podmienioną macierzą.
 *
 * `zTablicy()` wymaga KOMPLETU pól (W-2 z rundy 4), więc test nie może już
 * podać samej macierzy — i bardzo dobrze: podanie fragmentu było właśnie tym,
 * co pozwalało zamrożonemu zrzutowi dobierać resztę z kodu.
 *
 * @param  array<string, mixed>  $zmiany
 * @return array<string, mixed>
 */
function zrzutZe(array $zmiany): array
{
    return array_merge(ZestawRegul::wersjaZerowa()->doTablicy(), $zmiany);
}

it('odrzuca zwrot ponad 100% — dokładnie zapis, którym weryfikator wypłacił 725 zł z wpłaconych 145 zł', function (): void {
    expect(fn () => ZestawRegul::zTablicy(zrzutZe(['macierz_odwolan' => macierzZeZwrotem(500)])))
        ->toThrow(InvalidArgumentException::class, 'musi mieścić się w 0..100');
});

it('odrzuca zwrot ujemny', function (): void {
    expect(fn () => ZestawRegul::zTablicy(zrzutZe(['macierz_odwolan' => macierzZeZwrotem(-50)])))
        ->toThrow(InvalidArgumentException::class, 'musi mieścić się w 0..100');
});

it('odrzuca zwrot podany jako liczba zmiennoprzecinkowa, zamiast cicho robić z niego ZERO', function (): void {
    // W-5 z rundy 4 — najgroźniejsza postać tego błędu, bo idzie W DÓŁ.
    // `"zwrot_procent": 100.0` to poprawny JSON. `Typy::liczba()` podstawiało
    // wartość domyślną 0, warunek 0..100 przepuszczał ją bez protestu,
    // a pacjentowi należnemu 145 zł system wypłacał 0 zł — bez błędu, bez logu.
    // Zamek `min(zwrot, wpłata)` z definicji tego nie łapie.
    $macierz = macierzZeZwrotem(100);
    $macierz[Sytuacja::PacjentOdwolujeWczesniej->value]['zwrot_procent'] = 100.0;

    expect(fn () => ZestawRegul::zTablicy(zrzutZe(['macierz_odwolan' => $macierz])))
        ->toThrow(InvalidArgumentException::class, 'musi być liczbą całkowitą');
});

it('odrzuca KAŻDE pole skalarne podane jako liczba zmiennoprzecinkowa', function (): void {
    // Ta sama klasa błędu, rozpięta na wszystkich polach — nie na jednym
    // przypadku, który pokazał weryfikator (warunek „zamknięte" nr 1).
    foreach (['okno_bezplatnego_odwolania_godzin', 'limit_przelozen', 'blokada_koszyka_minut',
        'limit_niskoplatnych_wizyt', 'auto_domkniecie_godzin', 'wersja'] as $pole) {
        expect(fn () => ZestawRegul::zTablicy(zrzutZe([$pole => 48.0])))
            ->toThrow(InvalidArgumentException::class, 'musi być liczbą całkowitą');
    }
});

it('odrzuca zrzut, któremu brakuje choćby JEDNEGO pola — zamiast dobrać je z kodu', function (): void {
    // W-2: ten sam plik zrzutu dawał zwrot 14 500 gr albo 0 gr, zależnie
    // wyłącznie od stałej w `wersjaZerowa()`. Zamrożony zrzut przestawał być
    // samowystarczalny, czyli zmiana cennika działała WSTECZ (CLAUDE.md §4).
    $pelny = ZestawRegul::wersjaZerowa()->doTablicy();

    foreach (array_keys($pelny) as $pole) {
        $niepelny = $pelny;
        unset($niepelny[$pole]);

        expect(fn () => ZestawRegul::zTablicy($niepelny))
            ->toThrow(InvalidArgumentException::class, 'niekompletny');
    }
});

it('nie zmienia werdyktu, gdy zmieni się stała w kodzie — zrzut jest samowystarczalny', function (): void {
    // Pomiar weryfikatora, zamieniony w test: ten sam zrzut, dwie różne
    // wartości domyślne. Werdykt MUSI być identyczny.
    $zrzut = ZestawRegul::wersjaZerowa()->doTablicy();
    $zrzut['okno_bezplatnego_odwolania_godzin'] = 24;

    $termin = CarbonImmutable::parse('2026-10-10 12:00:00', 'UTC');

    $werdykt = OcenaAnulacji::oceń(
        ZestawRegul::zTablicy($zrzut),
        Sytuacja::PacjentOdwolujeWczesniej,
        $termin,
        $termin->subHours(30),
        kwotaZamrozonaGr: 14500,
    );

    // 30 h > 24 h zapisanych W ZRZUCIE → pełny zwrot, niezależnie od kodu.
    expect($werdykt->kwotaZwrotuGr)->toBe(14500);
});

it('odrzuca kwotę, która przepełniłaby przeliczenie — zamiast wywrócić się TypeError-em', function (): void {
    // W-5, druga część: komentarz twierdził, że PHP_INT_MAX jest obsłużony.
    // Nie był — `intdiv()` dostawało liczbę zmiennoprzecinkową i rzucało
    // `TypeError` z komunikatem, który nie mówi nic o pieniądzach.
    $termin = CarbonImmutable::parse('2026-10-10 12:00:00', 'UTC');

    foreach ([PHP_INT_MAX, intdiv(PHP_INT_MAX, 100) + 1] as $kwota) {
        expect(fn () => OcenaAnulacji::oceń(
            ZestawRegul::wersjaZerowa(),
            Sytuacja::PacjentOdwolujeWczesniej,
            $termin,
            $termin->subHours(48),
            kwotaZamrozonaGr: $kwota,
        ))->toThrow(InvalidArgumentException::class, 'przekracza granicę');
    }

    // Kierunek odwrotny: kwota tuż POD granicą liczy się normalnie.
    expect(OcenaAnulacji::oceń(
        ZestawRegul::wersjaZerowa(),
        Sytuacja::PacjentOdwolujeWczesniej,
        $termin,
        $termin->subHours(48),
        kwotaZamrozonaGr: intdiv(PHP_INT_MAX, 100),
    )->kwotaZwrotuGr)->toBe(intdiv(PHP_INT_MAX, 100));
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
    expect(fn () => ZestawRegul::zTablicy(zrzutZe([
        'macierz_odwolan' => [
            Sytuacja::PacjentOdwolujeWczesniej->value => [
                'zwrot_procent' => 100, 'termin_wraca' => true, 'godzina_platna' => false,
            ],
        ],
    ])))->toThrow(InvalidArgumentException::class, 'niepełna');
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
