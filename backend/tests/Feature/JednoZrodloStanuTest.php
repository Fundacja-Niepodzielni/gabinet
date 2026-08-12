<?php

declare(strict_types=1);

/**
 * PLIK STANU JEST PRZYRZĄDEM — i to najgroźniejszym.
 *
 * Następna sesja startuje z jego treści, więc jego nieaktualność propaguje się
 * na WSZYSTKIE jej decyzje. Nie ma tu drugiego mechanizmu, który by ją złapał:
 * kod uruchamia się i pada, dokument brzmi autorytatywnie i milczy.
 *
 * Znalezisko R6A-9: `PLAN-FAZ.md` miał DWIE sekcje `CURRENT WORK` o sprzecznym
 * stanie. Górna mówiła „BRAMKA CZERWONA", dolna — „`BRAMKA OK — 21 kroków,
 * 0 nieudanych`", „151 testów (479 asercji)", „20 scenariuszy". `CLAUDE.md`
 * wskazuje „sekcję `CURRENT WORK`" w liczbie POJEDYNCZEJ jako stan między
 * sesjami — a przy dwóch sekcjach o tej samej nazwie nie da się powiedzieć,
 * która to. Sesja czytająca akurat tę drugą startowała z fałszywego „BRAMKA OK".
 *
 * ⚠ POPRAWKĘ Z 09.08 ZROBIONO REDAKCYJNIE — nagłówek przemianowano i tyle.
 * Nic nie broniło przed ponownym rozdwojeniem sekcji stanu, a to jest defekt
 * podatny na nawrót w najczystszej postaci: wystarczy jedna sesja dopisująca
 * „swoją" sekcję. Reguła egzekwowana WYŁĄCZNIE pamięcią nie jest regułą —
 * to jest ta sama lekcja co N-10 i N-13 (`git add -A` łamał zasady, które
 * autor cytował tego samego dnia).
 *
 * Dlatego kontrola jest tutaj, w suicie, a nie w niczyjej głowie.
 */

/** Treść pliku stanu, wprost z repozytorium (nie z kopii, nie z pamięci). */
function planFaz(): string
{
    $sciezka = base_path('../PLAN-FAZ.md');

    expect(file_exists($sciezka))->toBeTrue(
        'Nie ma `PLAN-FAZ.md` — kontrola mierzyłaby pustkę, a jej zielone nic by nie znaczyło.'
    );

    return (string) file_get_contents($sciezka);
}

it('PLAN-FAZ.md ma DOKŁADNIE JEDNĄ sekcję CURRENT WORK', function (): void {
    $ile = preg_match_all('/^#{1,6}\s+CURRENT WORK/m', planFaz());

    expect($ile)->toBe(
        1,
        sprintf(
            'Sekcji `CURRENT WORK` jest %d, a ma być DOKŁADNIE JEDNA. '.
            'Zero znaczy, że stan przestał mieć źródło; więcej niż jedna — że ma ich kilka, '.
            'a wtedy nie da się powiedzieć, która obowiązuje. Tak powstało R6A-9: sesja '.
            'czytająca drugą sekcję startowała z fałszywego „BRAMKA OK", trzy dni po tym, '.
            'jak górna sekcja mówiła „CZERWONA".',
            $ile
        )
    );
});

it('KONTROLA NEGATYWNA: druga sekcja CURRENT WORK ZOSTAŁABY wykryta', function (): void {
    // Bez tego „jest dokładnie jedna" przechodzi także wtedy, gdy wzorzec
    // nie dopasowuje NICZEGO poza pierwszym wystąpieniem — czyli gdy kontrola
    // jest ślepa na dokładnie to zjawisko, którego pilnuje.
    $zPodwojona = planFaz()."\n\n## CURRENT WORK — sekcja podłożona przez kontrolę negatywną\n";

    expect(preg_match_all('/^#{1,6}\s+CURRENT WORK/m', $zPodwojona))->toBe(
        2,
        'Skaner NIE WIDZI drugiej sekcji podłożonej wprost. Jego zielone w teście wyżej '.
        'nie znaczy więc „jest jedna", tylko „umiem policzyć najwyżej jedną".'
    );
});

it('stan bramki NIE jest przepisany do sekcji zadań fazy', function (): void {
    // Druga połowa R6A-9, o której łatwo zapomnieć: rozdwojenie nie wymaga
    // drugiego NAGŁÓWKA. Wystarczy, że liczba testów albo wynik bramki zostanie
    // przepisany w drugie miejsce — dwa opisy jednej rzeczy rozjeżdżają się
    // po cichu, bo aktualizuje się zwykle jeden.
    //
    // Zdanie „BRAMKA OK — N kroków" stało w rozpisce zadań F1 przez kilka dni,
    // sprzeczne z sekcją stanu na górze tego samego pliku.
    // Odcinamy WYŁĄCZNIE sekcję stanu — do najbliższego nagłówka DOWOLNEGO
    // poziomu. Pierwsza wersja szukała `^## `, więc połykała też podsekcje
    // i całą rozpiskę F0 — czyli kontrola nie oglądała miejsca, w którym R6A-9
    // faktycznie się zdarzyło. Skaner mierzący węższy zakres, niż deklaruje,
    // to ta sama klasa co samo znalezisko.
    $poSekcjiStanu = (string) preg_replace('/^.*?^#{1,6}\s+CURRENT WORK.*?(?=^#{2,6}\s)/ms', '', planFaz());

    expect($poSekcjiStanu)->not->toBe('', 'Nie udało się odciąć sekcji stanu — kontrola mierzy pustkę.');

    // ⛔ ROZRÓŻNIENIE, BEZ KTÓREGO TA KONTROLA BYŁABY NIEUCZCIWA.
    //
    // `WYTYCZNE-PRACY.md` mówi wprost: identyfikator nazywający PRZESZŁE
    // ZDARZENIE („runda 5 na `b2084fc`") SIĘ NIE STARZEJE — nazywa zdarzenie,
    // nie stan bieżący. Zakaz wszystkich liczb bramki poza sekcją stanu kasowałby
    // historię i sprostowania, czyli dokładnie to, co ma tam zostać.
    //
    // Wymagamy więc KOTWICY: liczba bramki poza sekcją stanu musi stać przy
    // skrócie commita albo dacie. Bez kotwicy jest twierdzeniem o TERAZ —
    // a takich w tym pliku ma być jedno miejsce.
    preg_match_all(
        '/^.*(?:BRAMKA OK|BRAMKA CZERWONA)\s*—\s*\d+.*$/mu',
        $poSekcjiStanu,
        $trafienia
    );

    $bezKotwicy = [];

    $wiersze = explode("\n", $poSekcjiStanu);

    foreach ($trafienia[0] as $wiersz) {
        // Kotwicy szukamy także w wierszu POPRZEDNIM: sprostowanie jest z natury
        // wielowierszowe (zdanie „Stała tu wcześniej linia" plus cytat), więc
        // wymaganie znacznika w tym samym wierszu co cytat kazałoby przepisywać
        // historię pod kształt kontroli.
        $nr = array_search($wiersz, $wiersze, true);
        $kontekst = $wiersz.(is_int($nr) && $nr > 0 ? ' '.$wiersze[$nr - 1] : '');

        $maSha = preg_match('/`[0-9a-f]{7,40}`/', $kontekst) === 1;
        $maDate = preg_match('/\b\d{2}\.\d{2}\b/', $kontekst) === 1;
        $jestSprostowaniem = str_contains($kontekst, 'Stała tu') || str_contains($kontekst, 'SPROSTOWANIE');

        if (! $maSha && ! $maDate && ! $jestSprostowaniem) {
            $bezKotwicy[] = trim(mb_substr($wiersz, 0, 120));
        }
    }

    expect($bezKotwicy)->toBe(
        [],
        sprintf(
            "Wynik bramki przepisany POZA sekcję stanu i BEZ KOTWICY w przeszłym zdarzeniu:\n  %s\n".
            'Taka liczba jest twierdzeniem o TERAZ, a dwa miejsca trzymające ten sam stan zawsze '.
            'rozjadą się w czasie — to nie hipoteza, tylko przyczyna R6A-9. Albo dopisz skrót '.
            'commita/datę (wtedy nazywa ZDARZENIE i się nie starzeje), albo zostaw stan wyłącznie '.
            'w sekcji `CURRENT WORK`.',
            implode("\n  ", $bezKotwicy)
        )
    );
});
