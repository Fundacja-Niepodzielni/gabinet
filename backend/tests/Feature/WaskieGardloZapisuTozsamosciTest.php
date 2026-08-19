<?php

declare(strict_types=1);

use App\Tozsamosc\TozsamoscSesji;
use Tests\Wsparcie\Kod;

/**
 * WĄSKIE GARDŁO ZAPISU TOŻSAMOŚCI — niezmiennik, nie sonda.
 *
 * ================== PO CO TO ISTNIEJE ==================
 *
 * `CLAUDE.md` §2: „Logowanie wyłącznie przez Konta Niepodzielni (Keycloak).
 * ŻADNYCH własnych haseł w tym systemie." Zdanie ma dokładnie jedno maszynowe
 * znaczenie: **tożsamość może pojawić się w sesji tylko przez callback OIDC.**
 *
 * Trzy rundy z rzędu pytały o to SONDĄ — wysyłały sekret do tras i patrzyły,
 * czy zapis nastąpi. Sonda musi zgadnąć, JAK sekret jedzie, więc za każdym
 * razem istniał krok dalej:
 *
 *   runda 7 → sonda w ogóle powstała (siatka D-1b, pomiar skutku);
 *   runda 8 → nazwa pola spoza baterii (`zaklecie`)          — sonda ślepa;
 *   runda 9 → dwa pola o różnych wartościach (`email`+`hasło`) — sonda ślepa;
 *             sekret w NAGŁÓWKU `X-Zaklecie`                  — sonda ślepa;
 *             `$request->all()['zaklecie']`                   — sonda ślepa.
 *
 * Za każdym razem zamykaliśmy INSTANCJĘ. Ta kontrola zamyka KLASĘ, bo pyta
 * o coś, czego atakujący nie wybiera: **GDZIE stoi zapis tożsamości.**
 * Sposób dostarczenia sekretu — jedno pole, dwa pola, nagłówek, ciasteczko,
 * `all()`, cokolwiek — jest dla niej bez znaczenia, bo każdy z nich musi
 * skończyć się TYM SAMYM zapisem.
 *
 * ================== TRZY WARSTWY, TRZY RÓŻNE UCIECZKI ==================
 *
 *   WARSTWA 1 — surowy zapis klucza tożsamości do sesji istnieje WYŁĄCZNIE
 *               w fasadzie (`SesjaKonta`), i to w dwóch nazwanych metodach.
 *               Zamyka: mechanizm piszący sesję gdziekolwiek indziej —
 *               w `routes/`, w nowym pliku, w innej metodzie tego samego pliku.
 *
 *   WARSTWA 2 — `SesjaKonta::zaloz()` (USTANOWIENIE tożsamości) jest wołane
 *               wyłącznie z metody callbacku OIDC.
 *               Zamyka: mechanizm, który zamiast pisać sesję sam, woła fasadę.
 *
 *   WARSTWA 3 — metoda callbacku czyta z żądania WYŁĄCZNIE parametry kontraktu
 *               OIDC (`code`, `state`).
 *               Zamyka: mechanizm wstawiony do JEDYNEGO legalnego miejsca —
 *               tam warstwy 1 i 2 są z definicji bezradne.
 *
 * Każda warstwa ma tu kontrolę pozytywną (czysty kod przechodzi) i negatywną
 * (materiał zbudowany pod rękę zapala). Trzy wektory rundy 9 są dodatkowo
 * odtworzone jako perturbacje `d1b_para`, `d1b_naglowek`, `d1b_all`.
 *
 * ================== CZEGO TA KONTROLA NIE ZOBACZY ==================
 *
 * Zapisu pod kluczem zbudowanym w czasie działania (`$k = 'ko'.'nta'`).
 * Nazywam to wprost, bo alternatywą byłoby udawanie, że analiza statyczna
 * jest wykonaniem. Ten wektor pokrywa sonda D-1b — mechanizm musi bowiem
 * dać się WYWOŁAĆ, żeby cokolwiek zalogować, a wtedy zapis widać w sesji.
 * Dwa przyrządy o różnych ślepych plamach, nie jeden „na wszystko".
 */

/** Metody fasady, którym WOLNO pisać klucz tożsamości. */
const LEGALNE_ZAPISY = [
    'app/Tozsamosc/SesjaKonta.php::zaloz',
    'app/Tozsamosc/SesjaKonta.php::zaktualizuj',
];

/** Jedyne miejsce, z którego wolno USTANOWIĆ tożsamość. */
const LEGALNE_USTANOWIENIE = [
    'app/Http/Controllers/LogowanieController.php::powrot',
];

/** Parametry, które kontrakt OIDC pozwala callbackowi czytać z żądania. */
const PARAMETRY_CALLBACKU = ['code', 'state'];

/**
 * Miejsca, w których klucz tożsamości trafia do sesji.
 *
 * Szukamy STRUKTURY, nie napisu: wywołania metody zapisującej sesję, której
 * PIERWSZY argument jest kluczem tożsamości — literałem `'konta'` albo stałą
 * (`self::KLUCZ`, `SesjaKonta::KLUCZ`, `TozsamoscSesji::KLUCZ`).
 *
 * Obejmuje też formę pomocniczą `session(['konta' => …])`, bo to ten sam zapis
 * innym zapisem składniowym.
 *
 * @return list<string> wpisy „plik::funkcja (wiersz N, forma)"
 */
function zapisyTozsamosci(): array
{
    $zapisujace = ['put', 'merge', 'flash', 'push', 'replace', 'now'];
    $stale = ['KLUCZ'];
    $znalezione = [];

    foreach (Kod::plikiWykonywalne() as $sciezka) {
        $tokeny = token_get_all((string) file_get_contents($sciezka));
        $funkcje = Kod::funkcje($tokeny);
        $ile = count($tokeny);

        for ($i = 0; $i < $ile; $i++) {
            $t = $tokeny[$i];

            if (! is_array($t) || $t[0] !== T_STRING) {
                continue;
            }

            $forma = null;

            // (a) `->put('konta', …)` / `Session::put(self::KLUCZ, …)`
            if (in_array($t[1], $zapisujace, true) && ($tokeny[$i + 1] ?? null) === '(') {
                $poprzedni = $tokeny[$i - 1] ?? null;
                $wywolanie = is_array($poprzedni)
                    && in_array($poprzedni[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON], true);

                if ($wywolanie && kluczTozsamosciWArgumencie($tokeny, $i + 1, $stale)) {
                    $forma = $t[1].'()';
                }
            }

            // (b) `session(['konta' => …])` — pomocnik z tablicą
            if ($t[1] === 'session' && ($tokeny[$i + 1] ?? null) === '('
                && kluczTozsamosciWArgumencie($tokeny, $i + 1, $stale)) {
                $forma = 'session([…])';
            }

            if ($forma === null) {
                continue;
            }

            $znalezione[] = sprintf(
                '%s::%s (wiersz %d, %s)',
                Kod::wzgledna($sciezka),
                Kod::funkcjaDla($funkcje, $i),
                $t[2],
                $forma
            );
        }
    }

    sort($znalezione);

    return $znalezione;
}

/**
 * Czy w argumentach wywołania (od nawiasu `$nawias`) stoi klucz tożsamości.
 *
 * Patrzymy na CAŁĄ listę argumentów, nie tylko na pierwszy: forma tablicowa
 * `session(['konta' => …])` trzyma klucz w środku tablicy, a `merge()` bierze
 * tablicę zawsze.
 *
 * @param  list<array{0: int, 1: string, 2: int}|string>  $tokeny
 * @param  list<string>  $stale
 */
function kluczTozsamosciWArgumencie(array $tokeny, int $nawias, array $stale): bool
{
    $klucz = TozsamoscSesji::KLUCZ;
    $glebokosc = 0;
    $ile = count($tokeny);

    for ($i = $nawias; $i < $ile; $i++) {
        $t = $tokeny[$i];

        if ($t === '(') {
            $glebokosc++;

            continue;
        }
        if ($t === ')') {
            $glebokosc--;

            if ($glebokosc === 0) {
                return false;
            }

            continue;
        }

        if (! is_array($t)) {
            continue;
        }

        if ($t[0] === T_CONSTANT_ENCAPSED_STRING && trim($t[1], '\'"') === $klucz) {
            return true;
        }

        // `self::KLUCZ`, `SesjaKonta::KLUCZ`, `TozsamoscSesji::KLUCZ`
        if ($t[0] === T_STRING && in_array($t[1], $stale, true)
            && is_array($tokeny[$i - 1] ?? null) && $tokeny[$i - 1][0] === T_DOUBLE_COLON) {
            return true;
        }
    }

    return false;
}

/**
 * Czy wywołanie metody idzie na ŻĄDANIU (`$request->…` albo `request()->…`).
 *
 * @param  list<array{0: int, 1: string, 2: int}|string>  $tokeny
 */
function odbiorcaToZadanie(array $tokeny, int $indeks): bool
{
    $odbiorca = $tokeny[$indeks] ?? null;

    if (is_array($odbiorca) && $odbiorca[0] === T_VARIABLE && $odbiorca[1] === '$request') {
        return true;
    }

    // `request()->…` — nawias pusty, przed nim nazwa pomocnika.
    if ($odbiorca === ')' && ($tokeny[$indeks - 1] ?? null) === '(') {
        $nazwa = $tokeny[$indeks - 2] ?? null;

        return is_array($nazwa) && $nazwa[0] === T_STRING && $nazwa[1] === 'request';
    }

    return false;
}

/**
 * Miejsca wywołania metody statycznej `Klasa::metoda(`.
 *
 * @return list<string> wpisy „plik::funkcja"
 */
function wywolaniaStatyczne(string $klasa, string $metoda): array
{
    $znalezione = [];

    foreach (Kod::plikiWykonywalne() as $sciezka) {
        $tokeny = token_get_all((string) file_get_contents($sciezka));
        $funkcje = Kod::funkcje($tokeny);
        $ile = count($tokeny);

        for ($i = 2; $i < $ile; $i++) {
            $t = $tokeny[$i];

            if (! is_array($t) || $t[0] !== T_STRING || $t[1] !== $metoda) {
                continue;
            }
            if (($tokeny[$i + 1] ?? null) !== '(') {
                continue;
            }

            $operator = $tokeny[$i - 1] ?? null;
            $nazwaKlasy = $tokeny[$i - 2] ?? null;

            if (! is_array($operator) || $operator[0] !== T_DOUBLE_COLON) {
                continue;
            }
            if (! is_array($nazwaKlasy) || $nazwaKlasy[1] !== $klasa) {
                continue;
            }

            // Definicja metody w samej fasadzie nie jest jej wywołaniem.
            $znalezione[] = Kod::wzgledna($sciezka).'::'.Kod::funkcjaDla($funkcje, $i);
        }
    }

    return array_values(array_unique($znalezione));
}

// ---------------------------------------------------------------------------
// WARSTWA 1 — surowy zapis tożsamości tylko w fasadzie
// ---------------------------------------------------------------------------

it('WARSTWA 1: klucz tożsamości trafia do sesji WYŁĄCZNIE w dwóch metodach fasady', function (): void {
    $zapisy = zapisyTozsamosci();

    // Pustka to błąd, nie zero: gdyby skaner nic nie widział, kontrola
    // świeciłaby zielono nad dowolnym mechanizmem.
    expect(count($zapisy))->toBeGreaterThan(0,
        'Skaner nie znalazł ANI JEDNEGO zapisu tożsamości — a legalne dwa istnieją. '.
        'Parser rozjechał się z kodem i kontrola mierzy pustkę.');

    $miejsca = array_map(static fn (string $w): string => explode(' (', $w)[0], $zapisy);
    $miejsca = array_values(array_unique($miejsca));
    sort($miejsca);

    $legalne = LEGALNE_ZAPISY;
    sort($legalne);

    expect($miejsca)->toBe($legalne, sprintf(
        "ZAPIS TOŻSAMOŚCI POZA WĄSKIM GARDŁEM.\n\nZNALEZIONE:\n  %s\n\nDOZWOLONE:\n  %s\n\n".
        "`CLAUDE.md` §2 pozwala ustanowić tożsamość WYŁĄCZNIE callbackiem OIDC. Zapis\n".
        "w innym miejscu jest mechanizmem logowania obok Kont Niepodzielni — niezależnie\n".
        "od tego, czym sekret przyjechał (jedno pole, dwa pola, nagłówek, `all()`).\n".
        'Jeśli nowe miejsce ma tam prawo być, dopisz je ŚWIADOMIE do `LEGALNE_ZAPISY`.',
        implode("\n  ", $zapisy),
        implode("\n  ", $legalne)
    ));
});

// ---------------------------------------------------------------------------
// WARSTWA 2 — ustanowienie tożsamości tylko z callbacku
// ---------------------------------------------------------------------------

it('WARSTWA 2: `SesjaKonta::zaloz()` wołane WYŁĄCZNIE z metody callbacku OIDC', function (): void {
    $wywolania = wywolaniaStatyczne('SesjaKonta', 'zaloz');

    expect(count($wywolania))->toBeGreaterThan(0,
        'Zero wywołań `SesjaKonta::zaloz()` — skaner rozjechał się z kodem albo callback '.
        'przestał zakładać tożsamość. Jedno i drugie jest błędem.');

    $legalne = LEGALNE_USTANOWIENIE;
    sort($wywolania);
    sort($legalne);

    expect($wywolania)->toBe($legalne, sprintf(
        "USTANOWIENIE TOŻSAMOŚCI POZA CALLBACKIEM OIDC.\n\nZNALEZIONE: %s\nDOZWOLONE : %s\n\n".
        'Warstwa 1 pilnuje, żeby nikt nie pisał sesji samodzielnie; ta warstwa pilnuje, '.
        'żeby nikt nie obszedł tego, WOŁAJĄC fasadę z własnego mechanizmu logowania.',
        implode(', ', $wywolania),
        implode(', ', $legalne)
    ));
});

// ---------------------------------------------------------------------------
// WARSTWA 3 — callback czyta tylko kontrakt OIDC
// ---------------------------------------------------------------------------

it('WARSTWA 3: metoda callbacku czyta z żądania TYLKO parametry kontraktu OIDC', function (): void {
    // Ta warstwa istnieje dla wektora, wobec którego dwie poprzednie są
    // bezradne z definicji: mechanizmu wstawionego do JEDYNEGO miejsca, które
    // ma prawo pisać tożsamość. Kontrakt OIDC mówi, że callback konsumuje
    // `code` i `state` — cokolwiek innego czytane z żądania jest tam obce.
    $sciezka = base_path('app/Http/Controllers/LogowanieController.php');

    expect(file_exists($sciezka))->toBeTrue('Nie widzę kontrolera logowania — kontrola mierzy pustkę.');

    $tokeny = token_get_all((string) file_get_contents($sciezka));
    $funkcje = Kod::funkcje($tokeny);
    $czytajace = ['input', 'query', 'post', 'string', 'get', 'all', 'only', 'except',
        'json', 'collect', 'validate', 'header', 'cookie', 'has', 'filled'];

    $obce = [];
    $zbadane = 0;
    $ile = count($tokeny);

    for ($i = 2; $i < $ile; $i++) {
        $t = $tokeny[$i];

        if (! is_array($t) || $t[0] !== T_STRING || ! in_array($t[1], $czytajace, true)) {
            continue;
        }
        if (($tokeny[$i + 1] ?? null) !== '(') {
            continue;
        }

        $operator = $tokeny[$i - 1] ?? null;

        if (! is_array($operator) || $operator[0] !== T_OBJECT_OPERATOR) {
            continue;
        }

        // ODBIORCĄ musi być ŻĄDANIE. Bez tego `response()->json(...)` wygląda
        // identycznie jak odczyt pola — i tak właśnie pierwszy przebieg tej
        // kontroli oskarżył trzy poprawne odpowiedzi kontrolera. Nazwa metody
        // nie mówi, na czym ją wywołano; mówi to dopiero odbiorca.
        if (! odbiorcaToZadanie($tokeny, $i - 2)) {
            continue;
        }

        // Interesuje nas WYŁĄCZNIE wnętrze callbacku.
        if (Kod::funkcjaDla($funkcje, $i) !== 'powrot') {
            continue;
        }

        $zbadane++;
        $j = $i + 2;

        while (is_array($tokeny[$j] ?? null) && $tokeny[$j][0] === T_WHITESPACE) {
            $j++;
        }

        $arg = $tokeny[$j] ?? null;
        $nazwa = is_array($arg) && $arg[0] === T_CONSTANT_ENCAPSED_STRING
            ? trim($arg[1], '\'"')
            : '(nieliterałowy argument)';

        if (! in_array($nazwa, PARAMETRY_CALLBACKU, true)) {
            $obce[] = sprintf('wiersz %d: ->%s(%s)', $t[2], $t[1], $nazwa);
        }
    }

    expect($zbadane)->toBeGreaterThan(0,
        'W metodzie `powrot()` nie widzę ANI JEDNEGO odczytu żądania, a kontrakt OIDC '.
        'wymaga `code` i `state`. Skaner rozjechał się z kodem.');

    expect($obce)->toBe([], sprintf(
        "CALLBACK OIDC CZYTA Z ŻĄDANIA COŚ SPOZA SWOJEGO KONTRAKTU:\n  %s\n\n".
        "To jedyne miejsce, w którym wolno ustanowić tożsamość — więc jedyne, w którym\n".
        "mechanizm własnych haseł byłby niewidzialny dla warstw 1 i 2. Kontrakt OIDC\n".
        'konsumuje wyłącznie: %s.',
        implode("\n  ", $obce),
        implode(', ', PARAMETRY_CALLBACKU)
    ));
});

// ---------------------------------------------------------------------------
// KIERUNEK ODWROTNY — trzy wektory rundy 9 na materiale zbudowanym pod rękę
// ---------------------------------------------------------------------------

it('KIERUNEK ODWROTNY: skaner zapisu widzi wszystkie formy — na PLIKACH pod rękę', function (): void {
    // Materiał musi być PLIKAMI: skaner parsuje kod, więc forma podana jako
    // napis byłaby dla niego — słusznie — zwykłym tekstem. Ta sama lekcja,
    // co przy skanerze matcherów wariadycznych.
    $katalog = sys_get_temp_dir().'/gabinet-gardlo-'.getmypid();
    @mkdir($katalog, 0777, true);

    $material = [
        // trzy wektory rundy 9 — RÓŻNE sposoby dostarczenia, TEN SAM zapis
        'para.php' => "<?php\nfunction zaloguj(\$request) {\n\$l=\$request->input('email');\n\$h=\$request->input('haslo');\n\$request->session()->put('konta', ['sub'=>'x']);\n}\n",
        'naglowek.php' => "<?php\nfunction zaloguj(\$request) {\n\$s=\$request->header('X-Zaklecie');\nsession()->put('konta', ['sub'=>'x']);\n}\n",
        'all.php' => "<?php\nfunction zaloguj(\$request) {\n\$w=\$request->all();\n\$request->session()->put('konta', ['sub'=>'x']);\n}\n",
        // formy składniowe, które muszą być widziane tak samo
        'stala.php' => "<?php\nfunction f(\$r) { \$r->session()->put(SesjaKonta::KLUCZ, []); }\n",
        'pomocnik.php' => "<?php\nfunction f() { session(['konta' => ['sub'=>'x']]); }\n",
        'merge.php' => "<?php\nfunction f(\$r) { \$r->session()->merge(['konta' => []]); }\n",
        // NIE zapis tożsamości — nie wolno oskarżać
        'inny-klucz.php' => "<?php\nfunction f(\$r) { \$r->session()->put('oidc_przeplyw', []); }\n",
        'odczyt.php' => "<?php\nfunction f(\$r) { return \$r->session()->get('konta'); }\n",
        'komentarz.php' => "<?php\n// \$r->session()->put('konta', []) — tak NIE wolno\nfunction f() {}\n",
        'napis.php' => "<?php\nfunction f() { \$x = \"session()->put('konta', [])\"; return \$x; }\n",
    ];

    foreach ($material as $nazwa => $tresc) {
        file_put_contents($katalog.'/'.$nazwa, $tresc);
    }

    try {
        $wynik = [];

        foreach (array_keys($material) as $nazwa) {
            $tokeny = token_get_all((string) file_get_contents($katalog.'/'.$nazwa));
            $funkcje = Kod::funkcje($tokeny);
            $trafienia = 0;
            $ile = count($tokeny);

            for ($i = 1; $i < $ile; $i++) {
                $t = $tokeny[$i];

                if (! is_array($t) || $t[0] !== T_STRING || ($tokeny[$i + 1] ?? null) !== '(') {
                    continue;
                }

                $poprzedni = $tokeny[$i - 1] ?? null;
                $metoda = in_array($t[1], ['put', 'merge', 'flash', 'push', 'replace', 'now'], true)
                    && is_array($poprzedni)
                    && in_array($poprzedni[0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON], true);
                $pomocnik = $t[1] === 'session';

                if (($metoda || $pomocnik) && kluczTozsamosciWArgumencie($tokeny, $i + 1, ['KLUCZ'])) {
                    $trafienia++;
                }
            }

            $wynik[$nazwa] = ['trafienia' => $trafienia, 'funkcje' => count($funkcje)];
        }
    } finally {
        foreach (array_keys($material) as $nazwa) {
            @unlink($katalog.'/'.$nazwa);
        }
        @rmdir($katalog);
    }

    // Trzy wektory rundy 9 — każdy MUSI być widziany, i to jest sedno naprawy.
    foreach (['para.php', 'naglowek.php', 'all.php'] as $wektor) {
        expect($wynik[$wektor]['trafienia'])->toBe(1, sprintf(
            'Skaner NIE widzi zapisu tożsamości w wektorze `%s` — a to jeden z trzech '.
            'sposobów, którymi runda 9 przeszła przez całą bramkę.',
            $wektor
        ));
    }

    // Formy składniowe tego samego zapisu.
    expect($wynik['stala.php']['trafienia'])->toBe(1, 'Skaner nie widzi klucza podanego STAŁĄ.');
    expect($wynik['pomocnik.php']['trafienia'])->toBe(1, 'Skaner nie widzi formy `session([…])`.');
    expect($wynik['merge.php']['trafienia'])->toBe(1, 'Skaner nie widzi `merge([…])`.');

    // Fałszywe oskarżenia — każde wypchnęłoby z użycia poprawną konstrukcję.
    expect($wynik['inny-klucz.php']['trafienia'])->toBe(0, 'Skaner oskarża zapis pod INNYM kluczem.');
    expect($wynik['odczyt.php']['trafienia'])->toBe(0, 'Skaner oskarża ODCZYT tożsamości.');
    expect($wynik['komentarz.php']['trafienia'])->toBe(0, 'Skaner oskarża zapis w KOMENTARZU — nawrót R6A-6.');
    expect($wynik['napis.php']['trafienia'])->toBe(0, 'Skaner oskarża zapis w LITERALE NAPISOWYM — nawrót R7-2.');

    // Przyrząd rozpoznawania funkcji też musi żyć.
    expect($wynik['para.php']['funkcje'])->toBe(1, 'Rozpoznawanie funkcji nie widzi funkcji — atrybucja do metody byłaby pusta.');
});

it('KIERUNEK ODWROTNY: atrybucja do FUNKCJI działa — inaczej allowlista wraca do poziomu pliku', function (): void {
    // Bez tego cała różnica między „plik ma zgodę" a „metoda ma zgodę" znika,
    // a to jest dokładnie wada, przez którą mechanizm w `LogowanieController`
    // był niewidzialny (R9-1).
    $kod = "<?php\nclass X {\n public function legalna() { \$r->session()->put('konta', []); }\n"
        ." public function obca() { \$r->session()->put('konta', []); }\n}\n";

    $tokeny = token_get_all($kod);
    $funkcje = Kod::funkcje($tokeny);
    $nazwy = [];
    $ile = count($tokeny);

    for ($i = 1; $i < $ile; $i++) {
        $t = $tokeny[$i];

        if (is_array($t) && $t[0] === T_STRING && $t[1] === 'put' && ($tokeny[$i + 1] ?? null) === '(') {
            $nazwy[] = Kod::funkcjaDla($funkcje, $i);
        }
    }

    expect($nazwy)->toBe(['legalna', 'obca'],
        'Atrybucja zapisu do METODY nie działa — allowlista mogłaby wtedy być tylko listą '.
        'plików, a plik z jedną legalną linią dostaje zgodę na całą swoją treść (R9-1).');
});
