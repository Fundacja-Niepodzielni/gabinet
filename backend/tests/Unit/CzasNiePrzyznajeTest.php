<?php

declare(strict_types=1);

use App\Reguly\OcenaAnulacji;
use App\Reguly\OdmowaPrzelozenia;
use App\Reguly\Sytuacja;
use App\Reguly\ZestawRegul;
use Carbon\CarbonImmutable;

/**
 * `D-EKO-012` U SIEBIE: **upływ czasu ma ODBIERAĆ uprawnienie, nigdy PRZYZNAWAĆ.**
 *
 * Reguła powstała z defektu kont (sesja osoby wylogowanej wracała do życia po
 * skoku zegara), ale jest zasadą ekosystemu. `ZLECENIE-019` kazał sprawdzić ją
 * u mnie tam, gdzie czas rozstrzyga o pieniądzach albo o dostępie do terminu.
 *
 * Kontrole niżej są POZYTYWNE, nie naprawcze: nie znalazłem tu wady, więc ich
 * zadaniem jest **utrwalić poprawne zachowanie, żeby nikt go jutro nie odwrócił**.
 *
 * ZDOLNOŚĆ CZERWIENIENIA — ZMIERZONA, nie zadeklarowana:
 *   · odwrócenie kierunku okna (`>=` → `<=`) w `OcenaAnulacji` czerwieni
 *     MONOTONICZNOŚĆ komunikatem „ZWROT URÓSŁ przy PÓŹNIEJSZEJ decyzji”
 *     oraz KIERUNEK 0;
 *   · uzależnienie szerokości okna od roku wizyty czerwieni ZAMROŻENIE.
 *
 * Pierwsza wersja tego docbloku twierdziła, że perturbacje są „sprawdzone” —
 * NIE BYŁY. Sprawdziłem je dopiero po napisaniu tego zdania i pierwsza próba
 * przy ZAMROŻENIU okazała się MARTWA (zależała od `date('Y') === '2027'`,
 * a suita biegnie w 2026), a druga — żywa, ale nietrafiona w czuły punkt.
 * Dopiero trzecia zaczerwieniła kontrolę. To jest ta sama klasa, którą tropię
 * u innych, popełniona w komentarzu pisanym pięć minut wcześniej.
 *
 * Skok zegara jest tu realny z tych samych powodów co u kont: korekta czasu,
 * restart kontenera, wznowienie hosta.
 */
function regulyTestowe(): ZestawRegul
{
    return ZestawRegul::wersjaZerowa();
}

it('MONOTONICZNOŚĆ: im PÓŹNIEJ pada decyzja, tym zwrot NIGDY nie rośnie', function (): void {
    // Sedno reguły w formie sprawdzalnej. Gdyby gdziekolwiek w ocenie upływ
    // czasu PRZYZNAWAŁ (np. „po terminie wizyta uznana za nieodbytą → pełny
    // zwrot"), ta pętla by to złapała — bez zgadywania, w którym miejscu.
    $reguly = regulyTestowe();
    $wizyta = CarbonImmutable::parse('2026-09-01 12:00:00');
    $kwota = 14500;

    $poprzedniZwrot = null;
    $poprzedniOknoBezplatne = null;

    // Od tygodnia przed wizytą do doby PO wizycie, co 30 minut.
    for ($minuty = -10080; $minuty <= 1440; $minuty += 30) {
        $decyzja = $wizyta->addMinutes($minuty);
        $w = OcenaAnulacji::oceń($reguly, Sytuacja::PacjentOdwolujeWczesniej, $wizyta, $decyzja, $kwota);

        if ($poprzedniZwrot !== null) {
            expect($w->kwotaZwrotuGr)->toBeLessThanOrEqual(
                $poprzedniZwrot,
                sprintf(
                    'ZWROT URÓSŁ przy PÓŹNIEJSZEJ decyzji (%s): %d gr → %d gr. '.
                    'Upływ czasu PRZYZNAŁ uprawnienie, zamiast je odebrać (D-EKO-012).',
                    $decyzja->toIso8601String(), $poprzedniZwrot, $w->kwotaZwrotuGr
                )
            );

            expect($poprzedniOknoBezplatne === false && $w->wOknieBezplatnym === true)->toBeFalse(
                'Okno bezpłatne OTWORZYŁO SIĘ PONOWNIE przy późniejszej decyzji — '.
                'czas przyznał uprawnienie.'
            );
        }

        $poprzedniZwrot = $w->kwotaZwrotuGr;
        $poprzedniOknoBezplatne = $w->wOknieBezplatnym;
    }

    // Kontrola pozytywna pętli: bez niej wszystkie porównania mogłyby zachodzić
    // dlatego, że zwrot jest stale zerowy i nic się nie dzieje.
    $wczesnie = OcenaAnulacji::oceń($reguly, Sytuacja::PacjentOdwolujeWczesniej, $wizyta, $wizyta->subDays(7), $kwota);
    expect($wczesnie->kwotaZwrotuGr)->toBeGreaterThan(0, 'Zwrot jest stale zerowy — pętla niczego nie mierzy.');
    expect($wczesnie->wOknieBezplatnym)->toBeTrue();
});

it('KIERUNEK 0: decyzja PO terminie wizyty nie otwiera okna bezpłatnego', function (): void {
    // Stempel „z przyszłości" względem wizyty: `sekundDoWizyty` jest UJEMNE.
    // Gdyby porównanie robiło `abs()` albo liczyło „pełne godziny", wartość
    // ujemna mogłaby wpaść w okno i przyznać zwrot po fakcie.
    $reguly = regulyTestowe();
    $wizyta = CarbonImmutable::parse('2026-09-01 12:00:00');
    $poWizycie = $wizyta->addDays(3);

    $w = OcenaAnulacji::oceń($reguly, Sytuacja::PacjentOdwolujeWczesniej, $wizyta, $poWizycie, 14500);

    expect($w->wOknieBezplatnym)->toBeFalse('Decyzja PO wizycie wpadła w okno bezpłatnego odwołania.');
    expect($w->sekundDoWizyty)->toBeLessThan(0, 'Czas do wizyty nie jest ujemny — próba mierzy co innego.');
});

it('KIERUNEK 0: przełożenia NIE otwierają się z upływem czasu', function (): void {
    $reguly = regulyTestowe();
    $wizyta = CarbonImmutable::parse('2026-09-01 12:00:00');

    // Kontrakt: `true` gdy wolno, przypadek `OdmowaPrzelozenia` gdy nie.
    // Sprawdziłem sygnaturę zamiast założyć — pierwsza wersja tej kontroli
    // oczekiwała `null` i świeciła czerwono z MOJEGO błędu, nie z wady kodu.

    // W oknie i przy zerowej liczbie przełożeń — wolno.
    expect(OcenaAnulacji::czyMoznaPrzelozyc($reguly, $wizyta, $wizyta->subDays(5), 0))->toBeTrue();

    // Ta sama sytuacja, decyzja PÓŹNIEJ (poza oknem) — MUSI odmówić.
    expect(OcenaAnulacji::czyMoznaPrzelozyc($reguly, $wizyta, $wizyta->subHours(2), 0))
        ->toBe(OdmowaPrzelozenia::PozaOknem, 'Przełożenie poza oknem zostało dopuszczone.');

    // Decyzja PO wizycie — tym bardziej odmowa.
    expect(OcenaAnulacji::czyMoznaPrzelozyc($reguly, $wizyta, $wizyta->addDays(1), 0))
        ->toBe(OdmowaPrzelozenia::PozaOknem, 'Przełożenie PO wizycie zostało dopuszczone — czas przyznał uprawnienie.');
});

it('ZAMROŻENIE jest ODPORNE NA CZAS: ten sam zrzut daje ten sam werdykt niezależnie od chwili odczytu', function (): void {
    // Zamrożenie ma być odporne na upływ czasu — zmiana „teraz" nie może zmienić
    // tego, co zostało zamrożone. Werdykt zależy WYŁĄCZNIE od zrzutu i od
    // odległości decyzji od wizyty, nigdy od zegara ściennego.
    $zrzut = regulyTestowe();
    $odtworzony = ZestawRegul::zTablicy($zrzut->doTablicy());

    $wizyta = CarbonImmutable::parse('2026-09-01 12:00:00');

    // Punkt dobrany PRZY GRANICY okna, nie w jego środku. Pierwsza wersja tej
    // kontroli brała decyzję 48 h przed wizytą — a wtedy i okno 24-godzinne,
    // i okno 1-godzinne dają ten sam werdykt, więc kontrola NIE WYKRYWAŁA
    // uzależnienia okna od zegara. Zmierzone perturbacją: mutacja weszła
    // (`grep -c` = 1), a kontrola została zielona. Dwie godziny przed wizytą
    // werdykt zależy od szerokości okna, więc każda podmiana zrzutu na wartość
    // zależną od czasu zmienia wynik i kontrola pada.
    $decyzja = $wizyta->subHours(2);

    $a = OcenaAnulacji::oceń($zrzut, Sytuacja::PacjentOdwolujeWczesniej, $wizyta, $decyzja, 14500);

    // „Rok później" — ta sama para (wizyta, decyzja), inny moment w świecie.
    $b = OcenaAnulacji::oceń(
        $odtworzony,
        Sytuacja::PacjentOdwolujeWczesniej,
        $wizyta->addYear(),
        $decyzja->addYear(),
        14500
    );

    expect($b->kwotaZwrotuGr)->toBe($a->kwotaZwrotuGr, 'Zamrożony zrzut dał inny zwrot po przesunięciu w czasie.')
        ->and($b->wOknieBezplatnym)->toBe($a->wOknieBezplatnym)
        ->and($b->sytuacja)->toBe($a->sytuacja);
});
