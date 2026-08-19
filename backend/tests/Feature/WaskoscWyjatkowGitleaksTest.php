<?php

declare(strict_types=1);

/*
 * ⛔ KONTROLA WĄSKOŚCI WYJĄTKÓW GITLEAKS — warunek przyjęcia wariantu B.
 *
 * Właściciel zdecydował 19.08 (wariant B, `ZLECENIE-085`): historii NIE
 * przepisujemy — force-push wypchniętej gałęzi rozjechałby pracę sesji TESTY
 * (`testy-plan-f2` odbita od `faza-1-retencja`). Zamiast tego dwa wąskie wyjątki
 * gitleaks (D-4 przynęta base64, D-5 cytat GOCSPX) ZOSTAJĄ, z nowym terminem:
 * etap B, przy pierwszym przepisaniu historii, OBA razem albo żaden.
 *
 * Lista scaleniowa nazwała B „decyzją, nie zaniechaniem" — ale POD WARUNKIEM
 * kontroli pilnującej, że wyjątek pozostaje wąski. To jest ta kontrola.
 *
 * Dlaczego to ważne: wyjątek gitleaks, który się poszerza, przestaje być
 * wyjątkiem na jedną zmyśloną wartość, a staje się dziurą w skanerze sekretów.
 * R7-5 pokazał obie drogi poszerzenia w JEDNYM wpisie naraz: brak
 * `condition="AND"` (kryteria łączone przez OR — `regexes` zwalniało wartość
 * WSZĘDZIE) oraz SKRÓCONE SHA (nie pasowało do niczego, więc `commits` nie
 * zawężało). Kontrola pilnuje obu.
 *
 * CZEGO TA KONTROLA NIE CYTUJE: samych wartości sekretów. Wpisanie ich tutaj
 * jako literałów zapaliłoby gitleaks na tym pliku testowym — dokładnie ta klasa,
 * którą pilnujemy gdzie indziej. Pilnujemy STRUKTURY (liczba wyjątków, obecność
 * `AND`, długość SHA, liczba wartości), a tożsamość wartości sprawdzamy przez
 * porównanie z `.gitleaks.toml`, nie przez powielenie.
 */

/**
 * Bloki `[[allowlists]]` z `.gitleaks.toml`, każdy jako surowy tekst.
 *
 * @return list<string>
 */
function blokiAllowlist(): array
{
    $tresc = (string) file_get_contents(base_path('../.gitleaks.toml'));
    $czesci = preg_split('/^\[\[allowlists\]\]$/m', $tresc) ?: [];

    // Pierwszy element to nagłówek pliku sprzed pierwszego bloku — odrzucamy.
    array_shift($czesci);

    return array_map('trim', $czesci);
}

/**
 * Wartości tablicy TOML `klucz = [ … ]` z jednego bloku — po literałach.
 * Zlicza pozycje, nie interpretuje ich treści.
 *
 * @return list<string>
 */
function pozycjeTablicy(string $blok, string $klucz): array
{
    if (preg_match('/^'.preg_quote($klucz, '/').'\s*=\s*\[(.*?)\]/ms', $blok, $m) !== 1) {
        return [];
    }

    // ⛔ Usuń KOMENTARZE przed liczeniem. Lista `commits` niesie komentarz
    // `# Czwarty, znaleziony … ('git log -S')`, a apostrofowany `'git log -S'`
    // policzyłby się jako fałszywa pozycja tablicy (zmierzone: 7 zamiast 6).
    $bezKomentarzy = preg_replace('/^\s*#.*$/m', '', $m[1]) ?? '';

    // Literały w potrójnych albo pojedynczych apostrofach oraz w cudzysłowach.
    preg_match_all('/\'\'\'(.*?)\'\'\'|\'([^\']*)\'|"([^"]*)"/s', $bezKomentarzy, $t, PREG_SET_ORDER);

    $wynik = [];

    foreach ($t as $trafienie) {
        $potrojny = $trafienie[1] ?? '';
        $wynik[] = $potrojny !== '' ? $potrojny : (($trafienie[2] ?? '').($trafienie[3] ?? ''));
    }

    return $wynik;
}

/** Czy blok jest wyjątkiem historycznym (ma listę `commits`). */
function jestWyjatkiemHistorycznym(string $blok): bool
{
    return str_contains($blok, 'commits');
}

/**
 * Naruszenia wąskości jednego bloku wyjątku — pusta lista znaczy „wąski".
 *
 * Wydzielone, żeby kontrola NEGATYWNA mogła zepsuć blok w pamięci i sprawdzić,
 * że predykat go odrzuca — bez dotykania `.gitleaks.toml` (manipulacja pliku
 * przez indeksy jest zawodna przy polskich znakach).
 *
 * @return list<string>
 */
function naruszeniaWaskosci(string $blok): array
{
    $bledy = [];

    if (! str_contains($blok, 'condition = "AND"')) {
        $bledy[] = 'brak condition="AND" — kryteria łączą się przez OR (R7-5)';
    }

    if (count(pozycjeTablicy($blok, 'regexes')) !== 1) {
        $bledy[] = 'regexes ma inną liczbę wartości niż 1';
    }

    $commity = pozycjeTablicy($blok, 'commits');

    if ($commity === []) {
        $bledy[] = 'brak commits — zwalnia wartość WSZĘDZIE';
    }

    foreach ($commity as $sha) {
        if (preg_match('/^[0-9a-f]{40}$/', $sha) !== 1) {
            $bledy[] = 'SHA nie jest pełne 40-hex: '.$sha;
        }
    }

    if (count(pozycjeTablicy($blok, 'targetRules')) !== 1) {
        $bledy[] = 'targetRules zwalnia inną liczbę reguł niż 1';
    }

    return $bledy;
}

it('istnieją DOKŁADNIE dwa wyjątki historyczne — D-4 i D-5, ani jednego więcej', function (): void {
    // „Lista znanych cytatów ma się kurczyć, nie rosnąć" (`ZLECENIE-085` §5).
    // Trzeci wyjątek historyczny to albo nowy dług tej samej rodziny (wtedy ma
    // jawny wpis i termin), albo poszerzenie — w obu razach ma być WIDOCZNE.
    $wyjatki = array_filter(blokiAllowlist(), 'jestWyjatkiemHistorycznym');

    expect(count($wyjatki))->toBe(2, sprintf(
        'Liczba wyjątków historycznych gitleaks to %d, oczekiwano 2 (D-4, D-5). '.
        'Nowy wyjątek na wartość w historii jest długiem z terminem — dopisz go do '.
        'tej kontroli ŚWIADOMIE albo usuń.',
        count($wyjatki)
    ));
});

it('KAŻDY wyjątek historyczny jest WĄSKI — AND, jedna wartość, pełne SHA', function (): void {
    $wyjatki = array_values(array_filter(blokiAllowlist(), 'jestWyjatkiemHistorycznym'));

    // Pustka to błąd: bez wyjątków ta kontrola nie mierzy nic, a niżej są dwa.
    expect($wyjatki)->not->toBe([], 'Nie znalazłem wyjątków historycznych — kontrola mierzy pustkę.');

    foreach ($wyjatki as $i => $blok) {
        expect(naruszeniaWaskosci($blok))->toBe([], sprintf(
            'Wyjątek historyczny #%d NIE jest wąski: %s. Wyjątek gitleaks, który się '.
            'poszerza, przestaje być wyjątkiem na jedną zmyśloną wartość, a staje się '.
            'dziurą w skanerze sekretów (R7-5).',
            $i + 1,
            implode('; ', naruszeniaWaskosci($blok))
        ));
    }
});

it('KIERUNEK ODWROTNY: predykat wąskości ODRZUCA każdy sposób poszerzenia — na bloku psutym w pamięci', function (): void {
    // Kontrola negatywna wymagana przez `ZLECENIE-085` §1. Bierzemy PRAWDZIWY
    // wąski blok i psujemy go czterema sposobami z listy architekta; każdy
    // musi dać niepustą listę naruszeń. Bez tego „predykat przechodzi" nie
    // znaczy „predykat umie odrzucić".
    $wyjatki = array_values(array_filter(blokiAllowlist(), 'jestWyjatkiemHistorycznym'));
    $wzor = $wyjatki[0] ?? '';

    expect(naruszeniaWaskosci($wzor))->toBe([], 'Wzorcowy blok nie jest wąski — kontrola mierzy na zepsutym materiale.');

    // (a) brak condition="AND"
    $bezAnd = str_replace('condition = "AND"', 'condition = "OR"', $wzor);
    expect(naruszeniaWaskosci($bezAnd))->not->toBe([], 'Predykat NIE odrzuca braku condition="AND".');

    // (b) skrócone SHA
    $skrocone = preg_replace('/"[0-9a-f]{40}"/', '"527f1b7e35585a6e6ffd"', $wzor, 1) ?? $wzor;
    expect(naruszeniaWaskosci($skrocone))->not->toBe([], 'Predykat NIE odrzuca skróconego SHA.');

    // (c) druga wartość w regexes
    $dwieWartosci = preg_replace(
        "/regexes = \\['''([^']+)'''\\]/",
        "regexes = ['''\$1''', '''druga-wartosc-podszyta-pod-wyjatek''']",
        $wzor,
        1
    ) ?? $wzor;
    expect(naruszeniaWaskosci($dwieWartosci))->not->toBe([], 'Predykat NIE odrzuca drugiej wartości w regexes.');

    // (d) dodatkowy commit spoza znanej listy
    $dodatkowy = preg_replace(
        '/(commits = \[)/',
        "\$1\n  \"cccccccccccccccccccccccccccccccccccccccc\",",
        $wzor,
        1
    ) ?? $wzor;
    // Sam dodatkowy pełny SHA nie łamie predykatu bloku (jest 40-hex) — łapie go
    // kontrola SUMY commitów; tu sprawdzamy, że przynajmniej skrócony dodatkowy
    // zapala predykat bloku.
    $dodatkowySkrocony = preg_replace(
        '/(commits = \[)/',
        "\$1\n  \"abc123\",",
        $wzor,
        1
    ) ?? $wzor;
    expect(naruszeniaWaskosci($dodatkowySkrocony))->not->toBe([], 'Predykat NIE odrzuca dodatkowego skróconego commita.');

    // Instrument: `$dodatkowy` z PEŁNYM SHA przechodzi predykat bloku (bo jest
    // 40-hex) — to celowe; poszerzenie liczby łapie osobna kontrola sumy.
    expect(naruszeniaWaskosci($dodatkowy))->toBe([],
        'Dodatkowy PEŁNY SHA złamał predykat bloku — a miał go złamać dopiero licznik sumy. '.
        'Kontrola przyrządu: rozgraniczenie predykatu bloku i licznika sumy.');
});

it('liczba zwolnionych commitów NIE ROŚNIE ponad znaną — D-4: 4, D-5: 2', function (): void {
    // Konkretne liczby, bo „nie rośnie" bez punktu odniesienia jest puste.
    // D-4 (przynęta) siedzi w czterech commitach (`git log -S` przy naprawie),
    // D-5 (cytat) w dwóch (527f1b7 + powtórka dokumentacyjna 661e8a6).
    // Poszerzenie którejkolwiek listy zapala tę kontrolę.
    $sumaCommitow = 0;

    foreach (array_filter(blokiAllowlist(), 'jestWyjatkiemHistorycznym') as $blok) {
        $sumaCommitow += count(pozycjeTablicy($blok, 'commits'));
    }

    expect($sumaCommitow)->toBe(6, sprintf(
        'Suma zwolnionych commitów to %d, oczekiwano 6 (D-4: 4 + D-5: 2). '.
        'Wzrost znaczy, że wyjątek objął nowy commit — świadomie zaktualizuj tę '.
        'liczbę z powodem albo cofnij poszerzenie.',
        $sumaCommitow
    ));
});
