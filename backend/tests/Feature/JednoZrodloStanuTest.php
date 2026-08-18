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

/**
 * Sama sekcja stanu — czyli obszar, którego kontrole niżej DOTĄD NIE OGLĄDAŁY.
 *
 * R7-6: `stan bramki NIE jest przepisany do sekcji zadań fazy` wycina sekcję
 * `CURRENT WORK` i skanuje RESZTĘ pliku. Kontrola reklamowała się (`PLAN-FAZ.md:44`)
 * jako pilnująca „liczb stanu bez kotwicy" — a jedyne miejsce, w którym stan ma
 * prawo stać, było poza jej zasięgiem. Weryfikator rundy 7 znalazł tam TRZY
 * twierdzenia nieprawdziwe wobec repozytorium, wszystkie przy zielonej bramce:
 *
 *   · „Podłogi bramki: 258 / 2008"      — w `podlogi.sh` stało 265 / 2024;
 *   · „Strażnik `pre-commit` NIE POWSTAŁ" — powstał commit wcześniej (`cc70946`);
 *   · „Bramka: ZIELONA — 22 kroków"      — komunikat `551c0c8` mówił „CZERWONA, 2 z 22".
 *
 * Skaner mierzący WĘŻSZY zakres, niż deklaruje, to ta sama klasa co samo
 * znalezisko R6A-9 — i drugi raz z rzędu trafiła w ten sam plik.
 */
function sekcjaStanu(): string
{
    $tresc = planFaz();

    if (preg_match('/^#{1,6}\s+CURRENT WORK.*?(?=^#{2,6}\s)/ms', $tresc, $trafienie) !== 1) {
        return '';
    }

    return $trafienie[0];
}

/**
 * Podłogi z JEDYNEGO źródła — `skrypty/podlogi.sh`.
 *
 * ⚠ KOMENTARZE ODFILTROWANE, PRZYPISANIE ZAKOTWICZONE DO POCZĄTKU LINII.
 *
 * Pierwsza wersja czytała `/MINIMUM_TESTOW\s*=\s*"?(\d+)/` po całej treści
 * i zwracała ZERO — bo trafiała w komentarz `GABINET_MINIMUM_TESTOW=0`
 * z wiersza 27, opisujący, jak kiedyś dawało się kontrolę wyłączyć.
 *
 * Skutek był gorszy niż zła liczba: podłoga = 0 sprawia, że KAŻDA liczba
 * w sekcji stanu jest „nie niższa od podłogi", więc kontrola świeciła zielono
 * przy stanie, który miała łapać. To R6A-6 („literał w komentarzu") — trafiony
 * w kontroli pisanej PRZECIWKO nieprawdziwym twierdzeniom pliku stanu,
 * w tej samej godzinie, w której zamykałem tę klasę gdzie indziej.
 */
/** @return array{testy: int, asercje: int} */
function podlogiZeZrodla(): array
{
    $tresc = (string) preg_replace(
        '/^\s*#.*$/m',
        '',
        (string) file_get_contents(base_path('../skrypty/podlogi.sh'))
    );

    preg_match('/^MINIMUM_TESTOW\s*=\s*"?(\d+)/m', $tresc, $t);
    preg_match('/^MINIMUM_ASERCJI\s*=\s*"?(\d+)/m', $tresc, $a);

    return [
        'testy' => (int) (is_string($t[1] ?? null) ? $t[1] : 0),
        'asercje' => (int) (is_string($a[1] ?? null) ? $a[1] : 0),
    ];
}

it('R7-6 PRZYRZĄD: odczyt podłóg NIE bierze wartości z komentarza', function (): void {
    // Kontrola przyrządu, nie przedmiotu. Bez niej „podłoga = 0" przechodzi
    // niezauważona i unieważnia dwie kontrole niżej, świecąc przy tym zielono.
    $zrodlo = podlogiZeZrodla();

    expect($zrodlo['testy'])->toBeGreaterThan(0,
        'Odczyt podłogi testów dał ZERO — parser trafił w komentarz albo rozjechał się '.
        'ze skryptem. Przy zerze każda liczba w sekcji stanu przechodzi (R7-6).');

    expect($zrodlo['asercje'])->toBeGreaterThan(0,
        'Odczyt podłogi asercji dał ZERO — jak wyżej.');

    // KIERUNEK ODWROTNY: na materiale, w którym JEDYNE wystąpienie stoi
    // w komentarzu, odczyt ma dać zero — czyli filtr naprawdę filtruje.
    $tylkoKomentarz = (string) preg_replace('/^\s*#.*$/m', '', "# GABINET_MINIMUM_TESTOW=0 — dawne obejście\n");

    expect(preg_match('/^MINIMUM_TESTOW\s*=\s*"?(\d+)/m', $tylkoKomentarz))->toBe(0,
        'Filtr komentarzy NIE działa — odczyt nadal widzi wartość w prozie.');

    // …a na materiale z prawdziwym przypisaniem ma je znaleźć.
    expect(preg_match('/^MINIMUM_TESTOW\s*=\s*"?(\d+)/m', "MINIMUM_TESTOW=265\n"))->toBe(1,
        'Filtr zjada TAKŻE prawdziwe przypisanie — odczyt mierzyłby pustkę zawsze.');
});

it('R7-6: liczby PODŁÓG w sekcji stanu zgadzają się z `skrypty/podlogi.sh`', function (): void {
    $sekcja = sekcjaStanu();

    expect($sekcja)->not->toBe('', 'Nie udało się wyciąć sekcji stanu — kontrola mierzyłaby pustkę.');

    $zrodlo = podlogiZeZrodla();

    expect($zrodlo['testy'])->toBeGreaterThan(0, 'Nie odczytałem `MINIMUM_TESTOW` — parser się rozjechał ze skryptem.');
    expect($zrodlo['asercje'])->toBeGreaterThan(0, 'Nie odczytałem `MINIMUM_ASERCJI` — parser się rozjechał ze skryptem.');

    // „Podłogi … 265 / 2024" — w dowolnym zapisie z ukośnikiem.
    $ile = preg_match_all('/Podłogi[^\n]*?\*{0,2}(\d+)\s*\/\s*(\d+)\*{0,2}/u', $sekcja, $trafienia, PREG_SET_ORDER);

    expect($ile)->toBeGreaterThan(0,
        'W sekcji stanu NIE MA linii o podłogach. Zniknięcie twierdzenia nie jest '.
        'jego naprawą — ta kontrola pilnuje zgodności, a nie milczenia (R7-6).');

    $rozjazd = [];

    foreach ($trafienia as $t) {
        $wTescie = (int) $t[1];
        $wAsercjach = (int) $t[2];

        if ($wTescie !== $zrodlo['testy'] || $wAsercjach !== $zrodlo['asercje']) {
            $rozjazd[] = sprintf('sekcja stanu mówi %d / %d', $wTescie, $wAsercjach);
        }
    }

    expect($rozjazd)->toBe([], sprintf(
        "PODŁOGI W SEKCJI STANU ROZJECHAŁY SIĘ ZE ŹRÓDŁEM:\n  %s\n  `skrypty/podlogi.sh` mówi %d / %d\n\n".
        'To NIE jest „pomiar przebiegu, który wolno cytować z datą" — to twierdzenie o zawartości '.
        'pliku leżącego obok. Następna sesja czyta stąd próg, poniżej którego bramka ma odmawiać (R7-6).',
        implode("\n  ", $rozjazd),
        $zrodlo['testy'],
        $zrodlo['asercje']
    ));
});

it('R7-6: liczba testów w sekcji stanu NIE jest niższa od podłogi', function (): void {
    // Sprzeczność WEWNĘTRZNA, wykrywalna bez uruchamiania suity: sekcja stanu
    // twierdząca „260 zielonych" obok podłogi 265 opisuje świat, w którym bramka
    // musiałaby odmawiać. Jedna z tych liczb jest nieaktualna i wiadomo, że coś
    // jest nie tak, ZANIM ktokolwiek odpali przebieg.
    $sekcja = sekcjaStanu();
    $zrodlo = podlogiZeZrodla();

    $jest = preg_match('/\*{0,2}(\d+)\s+zielonych/u', $sekcja, $t) === 1;

    expect($jest)->toBeTrue(
        'W sekcji stanu nie ma liczby zielonych testów — kontrola mierzy pustkę.'
    );

    $zielonych = (int) ($t[1] ?? 0);

    expect($zielonych)->toBeGreaterThanOrEqual($zrodlo['testy'], sprintf(
        'Sekcja stanu mówi „%d zielonych", a podłoga bramki wynosi %d. Przy takim stanie '.
        'bramka MUSIAŁABY odmawiać — więc albo liczba, albo podłoga jest nieaktualna (R7-6).',
        $zielonych,
        $zrodlo['testy']
    ));
});

it('R7-6: twierdzenia „NIE POWSTAŁ / NIE ISTNIEJE" o ścieżkach w repo są PRAWDZIWE', function (): void {
    // Drugie z trzech znalezisk R7-6: sekcja stanu wysyłała następną sesję do
    // zbudowania strażnika, który stał w repozytorium od poprzedniego commita.
    // Twierdzenie o NIEISTNIENIU jest sprawdzalne najtaniej ze wszystkich —
    // wystarczy zajrzeć.
    // ZASIĘG: CAŁY PLIK, nie sama sekcja stanu.
    //
    // Trzecie znalezisko R7-6 („strażnik NIE POWSTAŁ") stało w podsekcji
    // `### ⚠ CZEGO SESJA NIE ZROBIŁA`, czyli poza blokiem, który wycina
    // `sekcjaStanu()`. Wąska kontrola przepuściłaby je ponownie — a to jest
    // dokładnie ta wada, którą R7-6 nazywa: skaner o zasięgu węższym niż
    // problem. Twierdzenie „tego nie ma" jest sprawdzalne WSZĘDZIE i najtaniej
    // ze wszystkich: wystarczy zajrzeć.
    //
    // Wyjątek robimy dla twierdzeń HISTORYCZNYCH, tą samą regułą co wyżej:
    // zdanie z kotwicą (data / skrót commita) nazywa ZDARZENIE i się nie starzeje.
    $wiersze = explode("\n", planFaz());
    $klamstwa = [];
    $zbadane = 0;

    foreach ($wiersze as $nr => $wiersz) {
        if (preg_match('/NIE POWSTAŁ|NIE ISTNIEJE|NIE POWSTAŁA|NIETKNIĘT/u', $wiersz) !== 1) {
            continue;
        }

        $kontekst = $wiersz.($nr > 0 ? ' '.$wiersze[$nr - 1] : '');
        $zakotwiczone = preg_match('/`[0-9a-f]{7,40}`/', $kontekst) === 1
            || preg_match('/\b\d{2}\.\d{2}\b/', $kontekst) === 1
            || str_contains($kontekst, 'SPROSTOWANIE')
            || str_contains($kontekst, 'Stała tu');

        // Ścieżki cytowane w tym samym zdaniu, w grawisach.
        preg_match_all('/`([a-zA-Z0-9_.\-]+\/[a-zA-Z0-9_.\-\/]+)`/', $wiersz, $sciezki);

        foreach ($sciezki[1] as $sciezka) {
            $zbadane++;

            if ($zakotwiczone) {
                continue;
            }

            if (file_exists(base_path('../'.$sciezka))) {
                $klamstwa[] = sprintf(
                    'wiersz %d: „%s" — a `%s` ISTNIEJE',
                    $nr + 1,
                    trim(mb_substr($wiersz, 0, 80)),
                    $sciezka
                );
            }
        }
    }

    expect($klamstwa)->toBe([], sprintf(
        "SEKCJA STANU TWIERDZI, ŻE CZEGOŚ NIE MA — A TO ISTNIEJE:\n  %s\n\n".
        'Następna sesja startuje z tej treści i zbuduje po raz drugi coś, co już stoi, '.
        'albo uzna otwartą klasę za otwartą, gdy jest zamknięta (R7-6).',
        implode("\n  ", $klamstwa)
    ));

    // ⛔ „Zero kłamstw" i „zero zdań do sprawdzenia" to DWA RÓŻNE ŚWIATY, a bez
    // tego rozróżnienia zielone wyżej byłoby zgodne z obydwoma. Nie żądamy, żeby
    // zdania o nieistnieniu w pliku BYŁY — plik ma prawo ich nie mieć. Żądamy,
    // żeby PRZYRZĄD, który ich szuka, umiał cokolwiek znaleźć: gdyby wzorzec
    // ścieżek się rozjechał, `$zbadane` byłoby zerem przy dowolnej treści pliku,
    // a kontrola świeciłaby zielono na zawsze.
    preg_match_all('/`([a-zA-Z0-9_.\-]+\/[a-zA-Z0-9_.\-\/]+)`/', planFaz(), $wszystkie);

    expect(count($wszystkie[1]))->toBeGreaterThan(5, sprintf(
        'Wzorzec ścieżek znalazł w CAŁYM `PLAN-FAZ.md` tylko %d trafień. Przy tak '.
        'ubogim odczycie „zero kłamstw" znaczy „nie umiem czytać ścieżek", a nie '.
        '„twierdzenia są prawdziwe" (zdań o nieistnieniu zbadano: %d).',
        count($wszystkie[1]),
        $zbadane
    ));
});

it('KONTROLA NEGATYWNA R7-6: skaner sekcji stanu widzi WSZYSTKIE trzy wady', function (): void {
    // Materiał budowany pod rękę — kontrola nie może dzielić źródła z przedmiotem.
    $zrodlo = podlogiZeZrodla();
    $zle = $zrodlo['testy'] - 7;

    expect($zle)->toBeGreaterThan(0,
        'Materiał kontrolny wyszedł ujemny — podłoga jest podejrzanie niska.');

    // (a) rozjazd podłóg
    $sekcja = "## CURRENT WORK\n- Podłogi bramki: **{$zle} / 2008**\n";
    preg_match_all('/Podłogi[^\n]*?\*{0,2}(\d+)\s*\/\s*(\d+)\*{0,2}/u', $sekcja, $t, PREG_SET_ORDER);

    expect($t)->toHaveCount(1, 'Skaner nie widzi linii podłóg — kontrola byłaby pusta.');
    expect((int) ($t[0][1] ?? 0))->not->toBe($zrodlo['testy'],
        'Materiał kontrolny nie różni się od źródła — nic nie dowodzi.');

    // (b) liczba zielonych poniżej podłogi
    expect(preg_match('/\*{0,2}(\d+)\s+zielonych/u', "- **{$zle} zielonych, 0 czerwonych**", $z))->toBe(1,
        'Skaner nie widzi liczby zielonych.');
    expect((int) ($z[1] ?? 0))->toBeLessThan($zrodlo['testy'],
        'Materiał kontrolny nie jest poniżej podłogi.');

    // (c) twierdzenie o nieistnieniu pliku, który istnieje
    $wiersz = '- **KLASA 7 NIETKNIĘTA.** Strażnik `skrypty/git-hooks/pre-commit` NIE POWSTAŁ.';

    expect(preg_match('/NIE POWSTAŁ|NIE ISTNIEJE|NIE POWSTAŁA|NIETKNIĘT/u', $wiersz))->toBe(1,
        'Skaner nie rozpoznaje zdania o nieistnieniu.');

    preg_match_all('/`([a-zA-Z0-9_.\-]+\/[a-zA-Z0-9_.\-\/]+)`/', $wiersz, $sciezki);

    expect($sciezki[1])->toContain('skrypty/git-hooks/pre-commit');
    expect(file_exists(base_path('../skrypty/git-hooks/pre-commit')))->toBeTrue(
        'Strażnik NIE ISTNIEJE — wtedy materiał kontrolny (c) nie dowodzi niczego, '.
        'a kontrola wyżej przechodzi z niewłaściwego powodu.'
    );
});

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
