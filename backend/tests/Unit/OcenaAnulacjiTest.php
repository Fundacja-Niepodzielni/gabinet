<?php

declare(strict_types=1);

use App\Reguly\OcenaAnulacji;
use App\Reguly\OdmowaPrzelozenia;
use App\Reguly\Sytuacja;
use App\Reguly\ZestawRegul;
use Carbon\CarbonImmutable;

/**
 * Macierz odwołań to „serce systemu" (dziennik makiety, rozdz. 3), a granica
 * 24 h to jedyne miejsce, w którym pomyłka o minutę zmienia odpowiedź systemu
 * na pytanie o pieniądze. Stąd testy tabelaryczne na wartościach granicznych
 * — wymóg bramki F1 wprost: 23:59 / 24:00 / 24:01.
 *
 * Wszystkie kwoty w GROSZACH. Zwrot musi zgadzać się ze Stripe co do grosza.
 */
const TERMIN = '2026-03-10 14:00:00';

function reguly(): ZestawRegul
{
    return ZestawRegul::wersjaZerowa();
}

function termin(): CarbonImmutable
{
    return CarbonImmutable::parse(TERMIN, 'Europe/Warsaw');
}

// ---------------------------------------------------------------------------
// GRANICA OKNA — trzy wartości, o które prosi bramka F1
// ---------------------------------------------------------------------------

it('rozstrzyga granicę okna bezpłatnego odwołania co do minuty', function (
    string $ileZostalo,
    bool $oczekiwanyZwrot,
    string $oczekiwanaSytuacja,
): void {
    $chwila = termin()->sub($ileZostalo);

    $werdykt = OcenaAnulacji::oceń(
        reguly(),
        Sytuacja::PacjentOdwolujeWczesniej,
        termin(),
        $chwila,
        kwotaZamrozonaGr: 14500,
    );

    // KOMUNIKAT JEST CZĘŚCIĄ PRZYRZĄDU, nie ozdobnikiem (R6B-15).
    // Perturbacja `p_testy` psuje granicę okna i musi umieć wskazać,
    // ŻE CZERWIEŃ POCHODZI STĄD — a allowlista `--przyczyna` może być
    // wyłącznie komunikatem asercji: nazwę testu Pest wypisuje w KAŻDYM
    // przebiegu, także zielonym, więc jako zawężenie nie znaczy nic.
    expect($werdykt->sytuacja->value)->toBe(
        $oczekiwanaSytuacja,
        "GRANICA OKNA ROZSTRZYGNIĘTA ŹLE dla {$ileZostalo} przed wizytą."
    );

    expect($werdykt->kwotaZwrotuGr)->toBe(
        $oczekiwanyZwrot ? 14500 : 0,
        "GRANICA OKNA ROZSTRZYGNIĘTA ŹLE: kwota zwrotu nie zgadza się dla {$ileZostalo}."
    );

    expect($werdykt->wOknieBezplatnym)->toBe(
        $oczekiwanyZwrot,
        "GRANICA OKNA ROZSTRZYGNIĘTA ŹLE: flaga okna bezpłatnego dla {$ileZostalo}."
    );
})->with([
    // Zostało DUŻO ponad okno — bezspornie bezpłatnie.
    '48 h przed' => ['48 hours', true, 'pacjent_odwoluje_wczesniej'],
    // GRANICA. Dokładnie 24:00:00 to JESZCZE bezpłatne odwołanie: pacjent widzi
    // datę graniczną co do minuty i musi móc w nią trafić bez ryzyka.
    '24:01 przed' => ['24 hours 1 minute', true, 'pacjent_odwoluje_wczesniej'],
    '24:00 przed (granica)' => ['24 hours', true, 'pacjent_odwoluje_wczesniej'],
    // Minuta po granicy — już płatne.
    '23:59 przed' => ['23 hours 59 minutes', false, 'pacjent_odwoluje_pozniej'],
    '2 h przed' => ['2 hours', false, 'pacjent_odwoluje_pozniej'],
    // Sekunda po granicy — dowód, że liczymy w sekundach, nie w pełnych godzinach.
    '23:59:59 przed' => ['23 hours 59 minutes 59 seconds', false, 'pacjent_odwoluje_pozniej'],
]);

// ---------------------------------------------------------------------------
// MACIERZ — wszystkie osiem sytuacji, wartość po wartości
// ---------------------------------------------------------------------------

it('stosuje macierz odwołań: zwrot, powrót terminu i płatność godziny', function (
    Sytuacja $sytuacja,
    int $oczekiwanyZwrotGr,
    bool $terminWraca,
    bool $godzinaPlatna,
): void {
    $werdykt = OcenaAnulacji::oceń(
        reguly(),
        $sytuacja,
        termin(),
        // Godzina przed wizytą — pora bez znaczenia dla sytuacji spoza
        // rozgałęzienia pacjenta, i to też jest tu dowodzone.
        termin()->subHour(),
        kwotaZamrozonaGr: 14500,
    );

    expect($werdykt->kwotaZwrotuGr)->toBe($oczekiwanyZwrotGr)
        ->and($werdykt->terminWracaDoPuli)->toBe($terminWraca)
        ->and($werdykt->godzinaPlatnaDlaSpecjalisty)->toBe($godzinaPlatna);
})->with([
    // Specjalista odwołuje godzinę przed — pacjent i tak dostaje 100%.
    // „Brak jakiegokolwiek progu czasowego" (spec s. 12).
    'specjalista odwołuje' => [Sytuacja::SpecjalistaOdwoluje, 14500, true, false],
    'koordynator odwołuje' => [Sytuacja::KoordynatorOdwoluje, 14500, true, false],
    // Nieobecność: godzina płatna, termin NIE wraca (bo minął) — jedyna taka para.
    'pacjent nie przyszedł' => [Sytuacja::PacjentNiePrzyszedl, 0, false, true],
    'odpuść tym razem' => [Sytuacja::OdpuscTymRazem, 14500, false, false],
    'brak płatności' => [Sytuacja::BrakPlatnosci, 0, true, false],
    'rezygnacja z grupy' => [Sytuacja::RezygnacjaZGrupy, 14500, true, false],
]);

it('przy późnym odwołaniu pacjenta termin WRACA do puli mimo braku zwrotu', function (): void {
    // Asymetria, która wygląda na błąd, a jest celowa: fundacja nie zarabia
    // dwa razy na tej samej godzinie, ale „lepiej, żeby godzina się odbyła,
    // niż stała pusta". Jeśli ktoś ją wykupi, pierwszy pacjent dostaje kredyt.
    $werdykt = OcenaAnulacji::oceń(
        reguly(),
        Sytuacja::PacjentOdwolujePozniej,
        termin(),
        termin()->subHours(3),
        kwotaZamrozonaGr: 14500,
    );

    expect($werdykt->kwotaZwrotuGr)->toBe(0)
        ->and($werdykt->terminWracaDoPuli)->toBeTrue()
        ->and($werdykt->godzinaPlatnaDlaSpecjalisty)->toBeTrue()
        ->and($werdykt->czyPowstajeZadanieZwrotu())->toBeFalse();
});

it('nigdy nie mówi „zwrot wykonany" — generuje zadanie tylko przy kwocie > 0', function (): void {
    // CLAUDE.md §7. Werdykt zna wyłącznie „powstaje zadanie zwrotu";
    // „wykonany" pojawia się dopiero po webhooku `charge.refunded` (F3).
    $zeZwrotem = OcenaAnulacji::oceń(reguly(), Sytuacja::SpecjalistaOdwoluje, termin(), termin()->subHour(), 14500);
    $bezZwrotu = OcenaAnulacji::oceń(reguly(), Sytuacja::PacjentNiePrzyszedl, termin(), termin()->subHour(), 14500);

    expect($zeZwrotem->czyPowstajeZadanieZwrotu())->toBeTrue()
        ->and($bezZwrotu->czyPowstajeZadanieZwrotu())->toBeFalse();
});

// ---------------------------------------------------------------------------
// KWOTY — grosze, nie złotówki
// ---------------------------------------------------------------------------

it('liczy zwrot w groszach dla każdej ceny z cennika', function (int $kwotaGr): void {
    $werdykt = OcenaAnulacji::oceń(reguly(), Sytuacja::SpecjalistaOdwoluje, termin(), termin()->subHour(), $kwotaGr);

    expect($werdykt->kwotaZwrotuGr)->toBe($kwotaGr);
})->with([
    'niskopłatna 55 zł' => [5500],
    'pełnopłatna 115 zł' => [11500],
    'pełnopłatna 125 zł' => [12500],
    'pełnopłatna 135 zł' => [13500],
    'pełnopłatna 145 zł' => [14500],
    'diagnoza ADHD 350 zł' => [35000],
    'asystent zdrowienia 0 zł' => [0],
]);

// ---------------------------------------------------------------------------
// ZAMRAŻANIE — zmiana reguł NIE działa wstecz
// ---------------------------------------------------------------------------

it('stosuje regułę ZAMROŻONĄ, a nie bieżącą konfigurację', function (): void {
    // CLAUDE.md §4 i spec s. 51: przesunięcie okna z 24 na 48 h obowiązuje
    // WYŁĄCZNIE nowe rezerwacje. Rezerwacja z zamrożonym oknem 24 h ma być
    // rozliczona po staremu, choćby fundacja zmieniła regułę wczoraj.
    $stara = reguly();                                   // okno 24 h
    $nowa = ZestawRegul::zTablicy(array_merge(
        reguly()->doTablicy(),
        ['wersja' => 2, 'okno_bezplatnego_odwolania_godzin' => 48],
    ));

    $chwila = termin()->subHours(30);                    // 30 h przed wizytą

    $poStaremu = OcenaAnulacji::oceń($stara, Sytuacja::PacjentOdwolujeWczesniej, termin(), $chwila, 14500);
    $poNowemu = OcenaAnulacji::oceń($nowa, Sytuacja::PacjentOdwolujeWczesniej, termin(), $chwila, 14500);

    // 30 h > 24 h → zwrot; 30 h < 48 h → brak zwrotu. Ta sama chwila, dwa wyniki.
    // Komunikat nazywa REGUŁĘ, nie liczbę — bo to jej dotyczy CLAUDE.md §4,
    // i bo perturbacja `p_zamrozenie` musi mieć czym zawęzić swoją czerwień.
    // Tu fałszywe zielone kosztuje PIENIĄDZE PACJENTA, więc allowlista jest
    // wymagana, a nie zalecana (WYTYCZNE-PRACY.md, dowód mutacji).
    expect($poStaremu->kwotaZwrotuGr)->toBe(
        14500,
        'ZASTOSOWANO REGUŁĘ BIEŻĄCĄ ZAMIAST ZAMROŻONEJ: rezerwacja z oknem 24 h '.
        'została rozliczona po nowemu. Zmiana cennika albo reguł NIGDY nie działa wstecz.'
    );

    expect($poNowemu->kwotaZwrotuGr)->toBe(
        0,
        'ZASTOSOWANO REGUŁĘ BIEŻĄCĄ ZAMIAST ZAMROŻONEJ: nowa reguła 48 h nie zadziałała '.
        'na nowej rezerwacji, więc test nie rozróżnia dwóch zestawów reguł.'
    );
});

// ---------------------------------------------------------------------------
// STREFY CZASOWE — doby 23- i 25-godzinne
// ---------------------------------------------------------------------------

it('liczy okno poprawnie przez zmianę czasu na letni (doba 23-godzinna)', function (): void {
    // W Polsce 2026-03-29 doba ma 23 godziny (2:00 → 3:00).
    // Wizyta w niedzielę 29.03 o 12:00 czasu lokalnego. Odwołanie w sobotę
    // 28.03 o 12:00 to wg ZEGARA „24 godziny wcześniej" — ale realnie minęły
    // tylko 23 godziny. Reguła mówi o godzinach, nie o wskazaniach zegara,
    // więc to jest odwołanie PO granicy.
    $terminDst = CarbonImmutable::parse('2026-03-29 12:00:00', 'Europe/Warsaw');
    $dzienWczesniej = CarbonImmutable::parse('2026-03-28 12:00:00', 'Europe/Warsaw');

    expect($terminDst->getTimestamp() - $dzienWczesniej->getTimestamp())->toBe(23 * 3600);

    $werdykt = OcenaAnulacji::oceń(reguly(), Sytuacja::PacjentOdwolujeWczesniej, $terminDst, $dzienWczesniej, 14500);

    expect($werdykt->kwotaZwrotuGr)->toBe(0)
        ->and($werdykt->sytuacja)->toBe(Sytuacja::PacjentOdwolujePozniej);
});

it('liczy okno poprawnie przez zmianę czasu na zimowy (doba 25-godzinna)', function (): void {
    // 2026-10-25 doba ma 25 godzin (3:00 → 2:00). Ten sam wskaźnik zegara
    // dzień wcześniej to realnie 25 godzin — czyli odwołanie PRZED granicą.
    $terminDst = CarbonImmutable::parse('2026-10-25 12:00:00', 'Europe/Warsaw');
    $dzienWczesniej = CarbonImmutable::parse('2026-10-24 12:00:00', 'Europe/Warsaw');

    expect($terminDst->getTimestamp() - $dzienWczesniej->getTimestamp())->toBe(25 * 3600);

    $werdykt = OcenaAnulacji::oceń(reguly(), Sytuacja::PacjentOdwolujeWczesniej, $terminDst, $dzienWczesniej, 14500);

    expect($werdykt->kwotaZwrotuGr)->toBe(14500)
        ->and($werdykt->sytuacja)->toBe(Sytuacja::PacjentOdwolujeWczesniej);
});

it('daje ten sam werdykt niezależnie od strefy czasowej pacjenta', function (string $strefa): void {
    // Spec M5/17: okna liczymy w Europe/Warsaw NIEZALEŻNIE od strefy pacjenta.
    // Pacjent w Nowym Jorku odwołujący „24 h przed" ma dostać to samo, co
    // pacjent w Warszawie — bo to ta sama chwila w czasie.
    $chwila = termin()->subHours(25)->setTimezone($strefa);

    $werdykt = OcenaAnulacji::oceń(reguly(), Sytuacja::PacjentOdwolujeWczesniej, termin(), $chwila, 14500);

    expect($werdykt->kwotaZwrotuGr)->toBe(14500);
})->with(['Europe/Warsaw', 'America/New_York', 'Asia/Tokyo', 'UTC']);

// ---------------------------------------------------------------------------
// PRZEŁOŻENIE — okno I limit, dwa różne powody odmowy
// ---------------------------------------------------------------------------

it('pozwala przełożyć w oknie i przy niewyczerpanym limicie', function (int $liczbaPrzelozen): void {
    expect(OcenaAnulacji::czyMoznaPrzelozyc(reguly(), termin(), termin()->subHours(48), $liczbaPrzelozen))
        ->toBeTrue();
})->with([0, 1]);

it('odmawia przełożenia po granicy okna — z powodem „poza oknem"', function (): void {
    expect(OcenaAnulacji::czyMoznaPrzelozyc(reguly(), termin(), termin()->subHours(23), 0))
        ->toBe(OdmowaPrzelozenia::PozaOknem);
});

it('odmawia przełożenia po wyczerpaniu limitu — z INNYM powodem', function (): void {
    // Dwa różne komunikaty dla pacjenta, więc dwa różne powody w kodzie.
    $odmowa = OcenaAnulacji::czyMoznaPrzelozyc(reguly(), termin(), termin()->subHours(48), 2);

    expect($odmowa)->toBe(OdmowaPrzelozenia::WyczerpanyLimit)
        ->and(OdmowaPrzelozenia::WyczerpanyLimit->komunikat())
        ->not->toBe(OdmowaPrzelozenia::PozaOknem->komunikat());
});

it('sprawdza okno PRZED limitem — poza oknem nie ma o czym mówić', function (): void {
    // Pacjent po granicy i z wyczerpanym limitem ma usłyszeć o granicy,
    // bo to ona jest powodem, którego nie da się obejść kontaktem ze specjalistą.
    expect(OcenaAnulacji::czyMoznaPrzelozyc(reguly(), termin(), termin()->subHours(2), 5))
        ->toBe(OdmowaPrzelozenia::PozaOknem);
});

// ---------------------------------------------------------------------------
// WARTOŚCI STARTOWE — regresja na liczby, które wolno zmienić tylko świadomie
// ---------------------------------------------------------------------------

it('trzyma wartości startowe reguł zgodne ze specyfikacją', function (): void {
    $r = ZestawRegul::wersjaZerowa();

    expect($r->oknoBezplatnegoOdwolaniaGodzin)->toBe(24)
        ->and($r->limitPrzelozen)->toBe(2)
        ->and($r->najblizszyTerminGodzin)->toBe(2)
        ->and($r->kalendarzPacjentaDni)->toBe(30)
        ->and($r->horyzontWystawianiaDni)->toBe(7)
        ->and($r->przerwaMiedzyWizytamiMinut)->toBe(10)
        ->and($r->blokadaKoszykaMinut)->toBe(10)
        ->and($r->waznoscLinkuPlatnosciDni)->toBe(2)
        ->and($r->autoDomkniecieGodzin)->toBe(48)
        // D-2026-08-07-08: 10 WIZYT, nie godzin. Wiersze mówiące „4 h na osobę"
        // to niedoczyszczony ślad sprzed podniesienia limitu.
        ->and($r->limitNiskoplatnychWizyt)->toBe(10)
        // Limit PODAŻOWY, egzekwowany przy wystawianiu grafiku (rozdz. 24).
        ->and($r->limitNiskoplatnychNaTydzien)->toBe(4)
        ->and($r->kredytZaOdsprzedanyTermin)->toBeTrue();
});

it('przechodzi przez serializację bez straty ani jednej wartości', function (): void {
    // Zamrożony zrzut idzie do bazy jako JSON i wraca po miesiącach.
    // Gdyby zgubił choć jedno pole, stara rezerwacja rozliczyłaby się
    // po nowemu — czyli dokładnie wbrew §4 CLAUDE.md.
    $oryginal = ZestawRegul::wersjaZerowa();
    $odtworzony = ZestawRegul::zTablicy($oryginal->doTablicy());

    expect($odtworzony->doTablicy())->toBe($oryginal->doTablicy());
});

it('zna wszystkie osiem sytuacji z macierzy', function (): void {
    $macierz = ZestawRegul::wersjaZerowa()->macierzOdwolan;

    expect($macierz)->toHaveCount(8);

    foreach (Sytuacja::cases() as $sytuacja) {
        expect($macierz)->toHaveKey($sytuacja->value);
    }
});
