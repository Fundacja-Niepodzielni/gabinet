<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * ZAPADKA POKRYCIA dla allowlist przyczyny czerwieni.
 *
 * Adaptacja projektu P1 kont (`niepodzielni-konta/docs/ZLECENIA/ODPOWIEDZ-005.md` §3.3):
 * rejestr + ZAPADKA, czyli „dług widoczny, tolerowany, malejący", zamiast bramki
 * czerwonej przez miesiące, którą pierwsza osoba wycisza.
 *
 * ================== CO TA KONTROLA MIERZY ==================
 *
 * `oczekuj_czerwone --przyczyna "X"` ma odpowiadać na pytanie „czy czerwień
 * przyszła z BADANEGO powodu". Wzorzec `X` jest dopasowywany do CAŁEGO wyjścia
 * przebiegu (`grep -qiE`). Jeżeli `X` jest NAZWĄ TESTU, to Pest wypisuje go
 * w każdym przebiegu — także zielonym — więc warunek spełnia się przez sam fakt
 * URUCHOMIENIA testu, a nie przez zapalenie badanej asercji. To jest gałąź
 * zdegenerowana: jedna zmierzona wartość zgodna z więcej niż jednym światem.
 *
 * ZMIERZONE 09.08.2026 — wzorzec obecny w wyjściu ZIELONEGO przebiegu:
 *   „odbiera dostęp"    → OBECNY   (nazwa testu, OdebranieRoliTest:167)
 *   „niedostępnym IdP"  → OBECNY   (nazwa testu, OdebranieRoliTest:286)
 *   „ZASZYFROWANY"      → OBECNY   (nazwa testu, OdebranieRoliTest:480)
 *   „granicę okna"      → OBECNY   (nazwa testu, OcenaAnulacjiTest:35)
 *   „ZAMROŻONĄ"         → OBECNY   (nazwa testu, GranicePienidzyTest:148)
 *   „Logout nie trafił…"→ nieobecny (KOMUNIKAT ASERCJI, OdebranieRoliTest:555)
 *
 * ================== DLACZEGO ZAPADKA, A NIE CZERWIEŃ ==================
 *
 * Zapadka powstała, bo długu nie dawało się spłacić w jednym ruchu, a runda 1
 * (commit bcf6fa5) twierdziła, że tę klasę zamyka, wprowadzając przy tym pięć
 * nowych wystąpień. Pilnowała więc jednej rzeczy: **liczba nierozróżniających
 * wzorców NIE MOŻE UROSNĄĆ.**
 *
 * ================== STAN NA 12.08.2026: DŁUG SPŁACONY ==================
 *
 * Sufit stoi na ZERZE i zapadka jest odtąd zwykłą bramką — każdy nowy wzorzec
 * zdegenerowany zapala czerwień od razu, bez okresu tolerancji.
 *
 * Droga tutaj jest warta zapisania, bo dwa razy wyglądała na zakończoną:
 *
 *   7 → 2   (rundy 1–6) czterem asercjom dopisano komunikaty;
 *   2 → 5   (R7-8) — NIE regres, tylko PRZELICZENIE tym samym zdarzeniem:
 *           zapadka nie widziała nazw KLAS ani gałęzi alternatywy ERE, więc
 *           trzy wystąpienia nigdy nie były policzone. Liczba „2" opisywała
 *           zasięg parsera, nie stan repozytorium;
 *   5 → 0   (12.08) — cztery wzorce przepisano na KOMUNIKATY ASERCJI
 *           skopiowane z czerwieni, dwóm testom dopisano brakujące komunikaty.
 *
 * Piąty (`perturbacje.sh:1208`) spłaciła NAPRAWA GDZIE INDZIEJ, nie zmiana
 * wzorca: dopóki `zdekodowaneLadunki()` gubiła drugą warstwę base64 (R7-4),
 * właściwa asercja nie zapalała się wcale, a czerwień przychodziła z wyjątku
 * deszyfrowania — z innego ogniwa niż badane. Wzorzec nie miał w co celować.
 * To jest rzecz do zapamiętania: „wzorzec przyczyny jest zły" bywa OBJAWEM,
 * a nie chorobą — czasem naprawą jest test, a nie napis w skrypcie.
 *
 * ================== CZYM SIĘ RÓŻNI OD PIERWOWZORU ==================
 *
 * W projekcie kont rejestr jest zarazem SPISEM kontrolowanych asercji i ich
 * audytem, więc asercja nieobecna w rejestrze jest dla zapadki niewidzialna
 * (reguła C1: kontrola dzieląca źródło prawdy z przedmiotem nie ma jak go
 * przyłapać). Tutaj spis pochodzi z PARSOWANIA `perturbacje.sh`, czyli z innego
 * źródła niż lista długu — nowe wywołanie `--przyczyna` liczy się samo, bez
 * niczyjego wpisu.
 */
// ZERO. Sufit ma zjezdzac razem z dlugiem; sufit z zapasem jest atrapa zapadki.
const SUFIT_NIEROZROZNIAJACYCH = 0;

/** @return list<array{wzorzec: string, filtr: ?string, linia: int}> */
function wywolaniaZPrzyczyna(string $skrypt): array
{
    $linie = explode("\n", $skrypt);
    $wynik = [];

    foreach ($linie as $i => $linia) {
        // Linie komentarza opisują SKŁADNIĘ, nie są wywołaniem.
        if (preg_match('/^\s*#/', $linia) === 1) {
            continue;
        }
        if (preg_match('/--przyczyna\s+"([^"]+)"/', $linia, $dopasowanie) !== 1) {
            continue;
        }

        // Polecenie bywa w tej samej linii albo w następnych (kontynuacja `\`).
        $okno = implode("\n", array_slice($linie, $i, 3));
        $filtr = preg_match('/--filter="([^"]+)"/', $okno, $f) === 1 ? $f[1] : null;

        $wynik[] = ['wzorzec' => $dopasowanie[1], 'filtr' => $filtr, 'linia' => $i + 1];
    }

    return $wynik;
}

/**
 * SŁOWNIK ZIELONEGO PRZEBIEGU — napisy obecne w wyjściu NIEZALEŻNIE od tego,
 * czy badana asercja zapaliła.
 *
 * R7-8: przez trzy rundy ta funkcja nazywała się `nazwyTestow()` i zbierała
 * WYŁĄCZNIE nazwy `it()/test()`. Zapadka liczyła więc jedną z co najmniej
 * trzech postaci degeneracji, a przedstawiała się jako kontrola całej klasy.
 *
 * ZMIERZONE 12.08.2026 na ZIELONYM przebiegu (nie dedukcja z formatu raportera —
 * weryfikator rundy 7 uczciwie zaznaczył, że jego twierdzenie jest dedukcyjne):
 *
 *   pest tests/Feature/BrakWlasnychHaselTest.php  →  `PASS  Tests\Feature\BrakWlasnychHaselTest`
 *   pest --filter="marker"                        →  `marker` 3×, `PASS  Tests\Feature\BramkiTest` 1×
 *
 * Czyli `--przyczyna "BrakWlasnychHasel"` (perturbacje.sh:604, :625) oraz OBIE
 * gałęzie `--przyczyna "Bramki|marker"` (:1518) spełniały się w przebiegu BEZ
 * perturbacji. Trzy wywołania nie mierzyły nic ponad „testy się uruchomiły".
 *
 * Nazwa klasy pochodzi ze ŚCIEŻKI pliku, nie z deklaracji w środku: Pest
 * wypisuje w nagłówku właśnie ścieżkę przełożoną na przestrzeń nazw.
 *
 * Bierzemy klasy WSZYSTKICH plików testowych, nie tylko tych, które dane
 * polecenie uruchamia: `--filter` wybiera pliki po nazwach testów, więc
 * statycznie nie wiadomo, czyj nagłówek padnie. Nadmiar idzie w stronę
 * SUROWSZĄ — zapadka może zażądać wzorca ostrzejszego, niż trzeba, ale nie
 * przepuści zdegenerowanego. Dla zapadki tylko jeden z tych błędów jest groźny.
 *
 * @return list<string>
 */
function slownikZielonegoPrzebiegu(string $katalog): array
{
    $slownik = [];

    foreach (File::allFiles($katalog) as $plik) {
        if ($plik->getExtension() !== 'php') {
            continue;
        }

        preg_match_all(
            '/^\s*(?:it|test|describe)\(\s*([\'"])(.*?)\1/m',
            (string) file_get_contents($plik->getPathname()),
            $trafienia
        );
        foreach ($trafienia[2] as $nazwa) {
            $slownik[] = $nazwa;
        }

        // `…/tests/Feature/BramkiTest.php` → `Tests\Feature\BramkiTest`
        $wzgledna = str_replace('\\', '/', substr($plik->getPathname(), strlen($katalog) + 1));
        $slownik[] = 'Tests\\'.str_replace('/', '\\', substr($wzgledna, 0, -4));
    }

    // Stałe raportera. Wzorzec „Tests" albo „passed" spełnia się w każdym
    // przebiegu tak samo pewnie jak nazwa testu.
    foreach (['PASS', 'FAIL', 'Tests:', 'Duration:', 'assertions', 'passed', 'failed', 'WARN', 'SKIPPED'] as $stala) {
        $slownik[] = $stala;
    }

    return $slownik;
}

/**
 * Gałęzie alternatywy ERE — bo `grep -qiE` spełnia się, gdy pasuje DOWOLNA.
 *
 * Dzielimy na KAŻDYM `|` poza klasą znaków i poza ucieczką, także wewnątrz
 * nawiasów grupujących. To dzieli za dużo (`(A|B) C` → `(A`, `B) C`) i taki
 * jest zamiar: nadmiarowy podział czyni zapadkę SUROWSZĄ, a pominięcie
 * gałęzi — ślepą.
 *
 * @return list<string>
 */
function galezieAlternatywy(string $wzorzec): array
{
    $galezie = [];
    $biezaca = '';
    $wKlasie = false;
    $ucieczka = false;

    // Po BAJCIE, nie po znaku: bajty kontynuacji UTF-8 są >= 0x80, więc nigdy
    // nie udają ASCII-owych metaznaków, których tu szukamy.
    foreach (str_split($wzorzec) as $znak) {
        if ($ucieczka) {
            $biezaca .= $znak;
            $ucieczka = false;

            continue;
        }
        if ($znak === '\\') {
            $biezaca .= $znak;
            $ucieczka = true;

            continue;
        }
        if ($wKlasie) {
            $biezaca .= $znak;
            $wKlasie = $znak !== ']';

            continue;
        }
        if ($znak === '[') {
            $wKlasie = true;
            $biezaca .= $znak;

            continue;
        }
        if ($znak === '|') {
            $galezie[] = $biezaca;
            $biezaca = '';

            continue;
        }

        $biezaca .= $znak;
    }

    $galezie[] = $biezaca;

    return $galezie;
}

/**
 * Gałąź pasująca do CZEGOKOLWIEK — pusta albo złożona z samych metaznaków.
 *
 * `--przyczyna "|X"` i `--przyczyna ".*"` są zdegenerowane bez związku
 * z jakąkolwiek nazwą; słownik ich nie złapie, bo nie porównują się z niczym.
 */
function galazPasujeDoWszystkiego(string $galaz): bool
{
    return preg_match('/^[.*+?^$()\s]*$/', $galaz) === 1;
}

/**
 * Wzorzec jest NIEROZRÓŻNIAJĄCY, gdy spełnia się bez zapalenia badanej asercji.
 *
 * Trzy postacie, wszystkie zmierzone na żywym wyjściu (R7-8):
 *
 *   1. gałąź jest fragmentem NAZWY TESTU — Pest wypisuje nazwy zawsze;
 *   2. gałąź jest fragmentem NAZWY KLASY — nagłówek `PASS  Tests\Feature\XTest`;
 *   3. gałąź pasuje do wszystkiego (pusta, `.*`) albo jest kopią `--filter`.
 *
 * Sprawdzamy KAŻDĄ gałąź osobno, bo `grep -qiE` spełnia się przy dowolnej.
 * Jedna zdegenerowana gałąź degeneruje CAŁY wzorzec — dokładnie to przepuściło
 * `"Bramki|marker"`, gdzie zdegenerowane okazały się obie.
 *
 * @param  list<string>  $slownik
 */
function wzorzecNieRozroznia(string $wzorzec, ?string $filtr, array $slownik): bool
{
    if ($filtr !== null && mb_strtolower($filtr) === mb_strtolower($wzorzec)) {
        return true;
    }

    foreach (galezieAlternatywy($wzorzec) as $galaz) {
        if (galazPasujeDoWszystkiego($galaz)) {
            return true;
        }

        // Nawiasy grupujące nie są tekstem do dopasowania — po nadmiarowym
        // podziale zostają na brzegach gałęzi i psułyby porównanie.
        $g = mb_strtolower(trim(str_replace(['(', ')'], '', $galaz)));

        if ($g === '') {
            continue;
        }

        foreach ($slownik as $napis) {
            if (str_contains(mb_strtolower($napis), $g)) {
                return true;
            }
        }
    }

    return false;
}

it('ZAPADKA: liczba nierozróżniających wzorców --przyczyna nie rośnie', function (): void {
    $sciezka = base_path('../skrypty/perturbacje.sh');
    expect(file_exists($sciezka))->toBeTrue('Nie widzę skryptu perturbacji — kontrola mierzyłaby pustkę.');

    $wywolania = wywolaniaZPrzyczyna((string) file_get_contents($sciezka));
    expect(count($wywolania))->toBeGreaterThan(0, 'Zero wywołań --przyczyna — parser się rozjechał ze skryptem.');

    $slownik = slownikZielonegoPrzebiegu(base_path('tests'));
    expect(count($slownik))->toBeGreaterThan(50, 'Pusty słownik zielonego przebiegu — parser się rozjechał, kontrola mierzyłaby pustkę.');

    // Kontrola ŚRODKA słownika, nie samej długości: nazwy klas doszły w R7-8
    // i mają być OBECNE, inaczej cicho wracamy do stanu sprzed naprawy.
    // `toContain()` przyjmuje KOLEJNE SZUKANE WARTOŚCI, nie komunikat — drugi
    // argument byłby cicho traktowany jak następna igła. Stąd `in_array`.
    expect(in_array('Tests\\Feature\\BramkiTest', $slownik, true))->toBeTrue(
        'Słownik nie zawiera nazw KLAS — wraca ślepota R7-8 na nagłówek raportera.'
    );

    $zdegenerowane = [];
    foreach ($wywolania as $w) {
        if (wzorzecNieRozroznia($w['wzorzec'], $w['filtr'], $slownik)) {
            $zdegenerowane[] = sprintf(
                'linia %d: --przyczyna "%s"%s',
                $w['linia'],
                $w['wzorzec'],
                $w['filtr'] === $w['wzorzec'] ? ' (IDENTYCZNY z --filter)' : ''
            );
        }
    }

    expect(count($zdegenerowane))->toBeLessThanOrEqual(
        SUFIT_NIEROZROZNIAJACYCH,
        sprintf(
            "PRZYBYŁO nierozróżniających wzorców przyczyny (%d > %d).\n".
            "Wzorzec równy NAZWIE TESTU spełnia się w każdym przebiegu, także zielonym,\n".
            "więc nie odróżnia czerwieni z badanego powodu od czerwieni z dowolnego innego.\n".
            "Użyj KOMUNIKATU ASERCJI, nie nazwy testu i nie wartości --filter.\n%s",
            count($zdegenerowane),
            SUFIT_NIEROZROZNIAJACYCH,
            implode("\n", $zdegenerowane)
        )
    );
});

it('ZAPADKA: sufit jest CIASNY — nie wolno go zostawić z zapasem', function (): void {
    $wywolania = wywolaniaZPrzyczyna((string) file_get_contents(base_path('../skrypty/perturbacje.sh')));
    $slownik = slownikZielonegoPrzebiegu(base_path('tests'));

    $ile = 0;
    foreach ($wywolania as $w) {
        if (wzorzecNieRozroznia($w['wzorzec'], $w['filtr'], $slownik)) {
            $ile++;
        }
    }

    // Bez tego zapadka jest atrapą: sufit z zapasem pozwala dołożyć wzorzec
    // zdegenerowany i nadal świecić zielono. Sufit ma ZJEŻDŻAĆ za długiem.
    expect($ile)->toBe(
        SUFIT_NIEROZROZNIAJACYCH,
        sprintf(
            'Dług spadł do %d, a sufit stoi na %d. OBNIŻ SUFIT do %d — inaczej zapadka '.
            'przepuści ponowne wprowadzenie tej samej wady.',
            $ile,
            SUFIT_NIEROZROZNIAJACYCH,
            $ile
        )
    );
});

/**
 * Wywołania matcherów WARIADYCZNYCH, w których ostatni argument jest ZDANIEM.
 *
 * PARSUJEMY, NIE DOPASOWUJEMY — i tym razem nie da się inaczej.
 *
 * Pierwsza wersja skanowała linie wyrażeniem regularnym i zapaliła się na
 * TRZECH własnych komentarzach oraz na literale w kontroli negatywnej. To już
 * znajome: R6A-6 (literał w komentarzu), potem R7-2 (literał w napisie).
 *
 * Wyjście z pułapki jest tu tylko jedno. Zaślepienie napisów przez
 * `Zrodlo::bezKomentarzyINapisow()` NIE ZADZIAŁA, bo szukany argument SAM JEST
 * napisem — zaślepienie zniszczyłoby przedmiot pomiaru dokładnie tak, jak
 * w pierwszej próbie naprawy R7-2. Potrzebna jest STRUKTURA: `->`, nazwa
 * matchera, nawias, przecinki na poziomie zero. Regex po liniach tego nie
 * zobaczy nigdy; lekser PHP wie o tym wszystko.
 *
 * @return list<string> wpisy „numer_linii  ->matcher(…, 'napis')"
 */
function polknieteKomunikaty(string $sciezka): array
{
    // LISTA POSZERZONA 18.08 — klasa przeniosla sie o krok na `toHaveKey`.
    //
    // Napisalem `toHaveKey(klucz, komunikat)` i dostalem czerwien
    // o porownaniu dwoch napisow, bo drugi argument tego matchera to
    // OCZEKIWANA WARTOSC. Ta kontrola tego nie widziala, choc powstala
    // dokladnie po to: znala trzy matchery z rodziny toContain* i ani
    // jednego z rodziny toHave*.
    //
    // Wspolna cecha nie siedzi w nazwie, tylko w SYGNATURZE: drugi argument
    // niesie wartosc, wiec zdanie w tym miejscu ginie bez sladu.
    $wariadyczne = ['toContain', 'toContainEqual', 'toContainOnlyInstancesOf',
        'toHaveKey', 'toHaveProperty', 'toHaveKeys'];
    $trafienia = [];

    $tokeny = token_get_all((string) file_get_contents($sciezka));
    $ile = count($tokeny);

    for ($i = 0; $i < $ile; $i++) {
        $t = $tokeny[$i];

        if (! is_array($t) || $t[0] !== T_STRING || ! in_array($t[1], $wariadyczne, true)) {
            continue;
        }

        // Musi być wywołaniem metody: `->matcher(`
        $poprzedni = $tokeny[$i - 1] ?? null;

        if (! is_array($poprzedni) || $poprzedni[0] !== T_OBJECT_OPERATOR || ($tokeny[$i + 1] ?? null) !== '(') {
            continue;
        }

        // Argumenty aż do domykającego nawiasu, z liczeniem głębokości.
        $glebokosc = 0;
        $argumenty = [[]];

        for ($j = $i + 1; $j < $ile; $j++) {
            $x = $tokeny[$j];

            if ($x === '(' || $x === '[') {
                $glebokosc++;

                if ($glebokosc === 1) {
                    continue;
                }
            } elseif ($x === ')' || $x === ']') {
                $glebokosc--;

                if ($glebokosc === 0) {
                    break;
                }
            } elseif ($x === ',' && $glebokosc === 1) {
                $argumenty[] = [];

                continue;
            }

            $argumenty[count($argumenty) - 1][] = $x;
        }

        if (count($argumenty) < 2) {
            continue;
        }

        // Ostatni argument: POJEDYNCZY literał napisowy wyglądający na zdanie
        // (ma spację i kończy się kropką albo dwukropkiem). Lista wartości
        // — `'pacjent', 'psycholog'` — tego nie spełnia.
        $ostatni = array_values(array_filter(
            $argumenty[count($argumenty) - 1],
            fn ($x) => ! (is_array($x) && in_array($x[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true))
        ));

        if (count($ostatni) !== 1 || ! is_array($ostatni[0]) || $ostatni[0][0] !== T_CONSTANT_ENCAPSED_STRING) {
            continue;
        }

        $tresc = trim($ostatni[0][1], '\'"');

        if (preg_match('/\s/', $tresc) === 1 && preg_match('/[.:]$/', $tresc) === 1) {
            $trafienia[] = sprintf('%d  ->%s(…, %s)', $ostatni[0][2], $t[1], $ostatni[0][1]);
        }
    }

    return $trafienia;
}

it('KOMUNIKAT ASERCJI nie może zostać połknięty przez matcher WARIADYCZNY', function (): void {
    // ZNALEZIONE 12.08 przy naprawie R7-7, w kodzie stojącym tu od rundy 2:
    //
    //     ->not->toContain('redaktor', 'Role czytane z ID TOKENU.')
    //
    // wygląda jak asercja z komunikatem, a `toContain()` przyjmuje KOLEJNE IGŁY.
    // Drugi napis był więc szukany w tablicy ról — nigdy go tam nie było, więc
    // asercja przechodziła, a „komunikat" NIE ISTNIAŁ w wyjściu.
    //
    // Skutek sięgnął dalej, niż wygląda: `perturbacje.sh` nie miał czego szukać,
    // musiał celować w NAZWĘ TESTU, a zapadka liczyła to jako dług „brak
    // komunikatu asercji". Opis długu był podwójnie nieprawdziwy — komunikat
    // był napisany, tylko połknięty przez sygnaturę.
    //
    // Ta kontrola pilnuje SYGNATUR, nie konkretnego przypadku: matcher
    // wariadyczny + argument wyglądający na zdanie = połknięty komunikat.
    // PARSUJEMY, NIE DOPASOWUJEMY — i tym razem nie da się inaczej.
    //
    // Pierwsza wersja skanowała linie wyrażeniem regularnym i zapaliła się na
    // TRZECH własnych komentarzach oraz na literale w kontroli negatywnej niżej.
    // To znajome: R6A-6 (literał w komentarzu), potem R7-2 (literał w napisie).
    //
    // Tu wyjście z pułapki jest tylko jedno. Zwykłe zaślepienie napisów przez
    // `Zrodlo::bezKomentarzyINapisow()` NIE ZADZIAŁA, bo szukany argument SAM
    // JEST napisem — zaślepienie zniszczyłoby przedmiot pomiaru dokładnie tak,
    // jak w pierwszej próbie naprawy R7-2. Potrzebna jest struktura: `->`,
    // nazwa matchera, nawias, przecinki NA POZIOMIE ZERO. Tego regex po liniach
    // nie zobaczy nigdy, a lekser PHP wie o tym wszystko.
    $podejrzane = [];
    $zbadane = 0;

    foreach (File::allFiles(base_path('tests')) as $plik) {
        if ($plik->getExtension() !== 'php') {
            continue;
        }

        $zbadane++;

        foreach (polknieteKomunikaty($plik->getPathname()) as $trafienie) {
            $podejrzane[] = $plik->getFilename().':'.$trafienie;
        }
    }

    expect($zbadane)->toBeGreaterThan(20, 'Zbadano za mało plików — skaner się rozjechał.');

    expect($podejrzane)->toBe([], sprintf(
        "KOMUNIKAT ASERCJI POŁKNIĘTY PRZEZ MATCHER WARIADYCZNY:\n%s\n\n".
        "Te matchery przyjmują KOLEJNE WARTOŚCI, nie komunikat — napis nigdy nie trafi\n".
        "do wyjścia, a perturbacja nie ma czego szukać jako przyczyny czerwieni.\n".
        "Zamień na formę z jawnym predykatem:\n".
        "    expect(in_array(\$igla, \$tablica, true))->toBeFalse('komunikat');\n".
        '    expect(str_contains($tekst, $igla))->toBeTrue(\'komunikat\');',
        implode("\n", $podejrzane)
    ));
});

it('KIERUNEK ODWROTNY: skaner matcherów wariadycznych widzi — na PLIKU zbudowanym pod rękę', function (): void {
    // Materiał musi być PLIKIEM, nie napisem w tym teście: skaner parsuje kod,
    // więc forma wadliwa przekazana jako literał byłaby dla niego — słusznie —
    // zwykłym tekstem. Zapisujemy więc prawdziwy plik PHP i skanujemy go tą
    // samą funkcją, której używa kontrola wyżej.
    $katalog = sys_get_temp_dir().'/gabinet-wariadyczne-'.getmypid();
    @mkdir($katalog, 0777, true);

    $material = [
        'wadliwy.php' => "<?php\nexpect(\$r)->not->toContain('redaktor', 'Role czytane z ID TOKENU.');\n",
        'lista.php' => "<?php\nexpect(\$r)->toContain('pacjent', 'psycholog', 'koordynator');\n",
        'jedna.php' => "<?php\nexpect(\$r)->toContain('Perturbacje:');\n",
        'komentarz.php' => "<?php\n// ->toContain('redaktor', 'Role czytane z ID TOKENU.') — tak NIE wolno\n",
        'w-napisie.php' => "<?php\n\$x = \"->toContain('redaktor', 'Role czytane z ID TOKENU.')\";\n",
    ];

    foreach ($material as $nazwa => $tresc) {
        file_put_contents($katalog.'/'.$nazwa, $tresc);
    }

    try {
        $trafienia = [];

        foreach ($material as $nazwa => $_) {
            $trafienia[$nazwa] = polknieteKomunikaty($katalog.'/'.$nazwa);
        }
    } finally {
        foreach (array_keys($material) as $nazwa) {
            @unlink($katalog.'/'.$nazwa);
        }
        @rmdir($katalog);
    }

    expect($trafienia['wadliwy.php'])->toHaveCount(1,
        'Skaner NIE widzi połkniętego komunikatu — kontrola wyżej jest pusta.');

    expect($trafienia['lista.php'])->toBe([],
        'Skaner oskarża poprawną listę igieł — wypchnąłby z użycia dobrą konstrukcję.');

    expect($trafienia['jedna.php'])->toBe([],
        'Skaner oskarża wywołanie z jedną igłą.');

    // Dwie postacie, na których poległa pierwsza wersja skanera (regex po liniach).
    expect($trafienia['komentarz.php'])->toBe([],
        'Skaner oskarża KOMENTARZ opisujący formę wadliwą — nawrót R6A-6.');

    expect($trafienia['w-napisie.php'])->toBe([],
        'Skaner oskarża formę wadliwą zapisaną w LITERALE NAPISOWYM — nawrót R7-2.');
});

it('KIERUNEK ODWROTNY: skaner naprawdę widzi — na materiale zbudowanym pod rękę', function (): void {
    // Materiał syntetyczny, nie odczyt z repozytorium: gdyby kontrola czytała
    // te same pliki co zapadka, dzieliłaby z nią mechanizm i nie miała jak jej
    // przyłapać (reguła C1).
    $slownik = [
        'odbiera dostęp, gdy Keycloak odbierze rolę',
        'POZYTYWNY: żądanie po wylogowaniu',
        'Tests\Feature\BramkiTest',
        'PASS',
    ];

    // wzorzec = fragment nazwy testu → MA zostać uznany za nierozróżniający
    expect(wzorzecNieRozroznia('odbiera dostęp', null, $slownik))->toBeTrue(
        'Skaner NIE widzi wzorca będącego nazwą testu — cała kontrola mierzyłaby pustkę.'
    );

    // wzorzec = komunikat asercji, nieobecny w słowniku → rozróżnia
    expect(wzorzecNieRozroznia('Logout nie trafił w sesję tego użytkownika', 'POZYTYWNY', $slownik))->toBeFalse(
        'Skaner uznaje komunikat asercji za nierozróżniający — fałszywie oskarżałby poprawne wywołania.'
    );

    // wzorzec identyczny z filtrem → nierozróżniający, nawet gdy słownik pusty
    expect(wzorzecNieRozroznia('ZASZYFROWANY', 'ZASZYFROWANY', []))->toBeTrue(
        'Skaner nie wykrywa wzorca będącego kopią --filter.'
    );

    // ---- R7-8: trzy postacie, których skaner NIE WIDZIAŁ do 12.08 ----------

    // (a) NAZWA KLASY z nagłówka raportera
    expect(wzorzecNieRozroznia('Bramki', null, $slownik))->toBeTrue(
        'Skaner nie widzi wzorca będącego nazwą KLASY — a Pest wypisuje ją w nagłówku '.
        'każdego przebiegu, także zielonego (R7-8, perturbacje.sh:604/:625).'
    );

    // (b) ALTERNATYWA ERE — degeneruje ją JEDNA gałąź, nie komplet
    expect(wzorzecNieRozroznia('KOMUNIKAT KTÓREGO NIGDZIE NIE MA|Bramki', null, $slownik))->toBeTrue(
        'Skaner ocenia alternatywę ERE jako całość zamiast gałąź po gałęzi. `grep -qiE` '.
        'spełnia się przy DOWOLNEJ gałęzi, więc jedna zdegenerowana wystarczy (R7-8, :1518).'
    );

    // (b′) …ale alternatywa z samych dobrych gałęzi ma PRZEJŚĆ — inaczej
    //      naprawa polegałaby na zakazie alternatywy, a nie na jej zrozumieniu.
    expect(wzorzecNieRozroznia('PIERWSZY KOMUNIKAT ASERCJI|DRUGI KOMUNIKAT ASERCJI', null, $slownik))->toBeFalse(
        'Skaner odrzuca alternatywę, której obie gałęzie są komunikatami asercji — '.
        'fałszywe oskarżenie wypycha z użycia poprawną konstrukcję.'
    );

    // (c) gałąź pasująca do wszystkiego — degeneracja BEZ związku ze słownikiem
    expect(wzorzecNieRozroznia('.*', null, $slownik))->toBeTrue(
        'Skaner przepuszcza wzorzec pasujący do dowolnego wyjścia.'
    );
    expect(wzorzecNieRozroznia('|KOMUNIKAT ASERCJI', null, $slownik))->toBeTrue(
        'Skaner przepuszcza PUSTĄ gałąź alternatywy — `grep -qiE "|X"` pasuje do wszystkiego.'
    );

    // Gałęzie w nawiasie też są gałęziami — dzielimy także wewnątrz grupy.
    expect(galezieAlternatywy('(A|B)'))->toHaveCount(2,
        'Alternatywa wewnątrz nawiasu grupującego nie została rozbita.');

    // …a `|` w klasie znaków i po ucieczce to ZWYKŁY ZNAK, nie separator.
    expect(galezieAlternatywy('[a|b]c'))->toHaveCount(1,
        'Pionowa kreska w klasie znaków potraktowana jako separator alternatywy.');
    expect(galezieAlternatywy('a\|b'))->toHaveCount(1,
        'Pionowa kreska po ucieczce potraktowana jako separator alternatywy.');
});
