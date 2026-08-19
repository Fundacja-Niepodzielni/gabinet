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

/*
 * Pola żądania czytane w danej funkcji — NIEZALEŻNIE OD SKŁADNI ODCZYTU.
 *
 * TO JEST NAPRAWA R10-1, A RÓŻNICA SIEDZI W PYTANIU, NIE W DŁUGOŚCI LISTY.
 *
 * Poprzednia wersja pytała „czy wywołano metodę Z LISTY 15 nazw" i przez to
 * przepuszczała dwa idiomatyczne odczyty, oba zmierzone przez rundę 10:
 *
 *   · dostęp tablicowy — `Request` implementuje `ArrayAccess`, więc nie ma
 *     ani tokenu metody, ani strzałki; pętla nie mogła go zobaczyć NIGDY;
 *   · metoda spoza listy — `str`, `boolean`, `enum`, `date`, a także
 *     właściwość dynamiczna obsługiwana magicznym `__get`.
 *
 * Przy obu mechanizm własnego hasła w `powrot()` przechodził CAŁĄ bramkę
 * (301 passed, Larastan i Pint zielone) i realnie logował: tożsamość w sesji
 * pojawiała się dla poprawnego sekretu, a nie pojawiała dla błędnego ani przy
 * jego braku.
 *
 * Trzeci raz z rzędu ta sama klasa, za każdym razem o piętro wyżej:
 * nazwa pola (R8-1) → sposób dostarczenia (R9-1) → SKŁADNIA ODCZYTU (R10-1).
 * Dlatego nie dopisuję nazw do listy — usuwam listę z miejsca, w którym stała.
 *
 * PYTANIE BRZMI ODTĄD: jakie POLE zostało odczytane. Lista dozwolonych zostaje
 * wyłącznie po stronie WEJŚCIA (`code`, `state`) — tam uzasadnia ją kontrakt
 * OIDC i ma dokładnie dwa elementy. Po stronie WYKRYWANIA listy nie ma: formy
 * składniowe są zamknięte przez gramatykę PHP, nie przez czyjąś pamięć.
 *
 * Odczyt pola pod nazwą NIELITERALNĄ jest zgłaszany, bo nie da się dowieść,
 * że mieści się w kontrakcie — a milczenie w takiej sytuacji byłoby gałęzią
 * zdegenerowaną.
 *
 * ⚠ Ta proza stoi w komentarzu ZWYKŁYM, nie dokumentacyjnym, i to jest
 * świadome: gdy była w `/**`, PHPStan przestawał widzieć znaczniki `@param`
 * (40 błędów zamiast 14 — zmierzone bisekcją). Opis w środku docbloku potrafi
 * unieważnić typy, a wtedy statyka cicho przestaje sprawdzać tę funkcję.
 */
/**
 * @param  list<array{0: int, 1: string, 2: int}|string>  $tokeny
 * @param  array{nazwa: string, od: int, do: int, linia: int}  $funkcja
 * @return list<string>
 */
function polaZadaniaCzytaneW(array $tokeny, array $funkcja): array
{
    $odczyty = [];

    for ($i = $funkcja['od']; $i <= $funkcja['do']; $i++) {
        $t = $tokeny[$i];

        // (A) SUPERGLOBALE i wejście surowe — wartość z zewnątrz BEZ `$request`.
        //     Odpowiedź na pytanie „krok dalej" ze zlecenia: pokrywamy, nie nazywamy.
        if (is_array($t) && $t[0] === T_VARIABLE
            && in_array($t[1], ['$_POST', '$_GET', '$_REQUEST', '$_COOKIE', '$_FILES', '$_SERVER'], true)) {
            $odczyty[] = sprintf('wiersz %d: superglobal %s', $t[2], $t[1]);

            continue;
        }

        if (is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING && str_contains($t[1], 'php://')) {
            $odczyty[] = sprintf('wiersz %d: strumień wejścia %s', $t[2], trim($t[1], '\'"'));

            continue;
        }

        // (B) Wszystko, co JEST żądaniem: zmienna, pomocnik `request()`, fasada.
        $toZadanie = (is_array($t) && $t[0] === T_VARIABLE && $t[1] === '$request')
            || (is_array($t) && $t[0] === T_STRING && $t[1] === 'request' && ($tokeny[$i + 1] ?? null) === '(')
            || (is_array($t) && $t[0] === T_STRING && $t[1] === 'Request'
                && is_array($tokeny[$i + 1] ?? null) && $tokeny[$i + 1][0] === T_DOUBLE_COLON);

        if (! $toZadanie) {
            continue;
        }

        // Przeskakujemy nawias pomocnika `request()`, żeby stanąć na tym,
        // co następuje PO obiekcie żądania.
        $k = $i + 1;

        if (($tokeny[$k] ?? null) === '(') {
            $glebokosc = 0;

            for (; $k < count($tokeny); $k++) {
                if ($tokeny[$k] === '(') {
                    $glebokosc++;
                } elseif ($tokeny[$k] === ')') {
                    $glebokosc--;

                    if ($glebokosc === 0) {
                        $k++;

                        break;
                    }
                }
            }
        }

        while (is_array($tokeny[$k] ?? null) && $tokeny[$k][0] === T_WHITESPACE) {
            $k++;
        }

        $nastepny = $tokeny[$k] ?? null;

        // (B1) DOSTĘP TABLICOWY — wektor A rundy 10.
        if ($nastepny === '[') {
            $m = $k + 1;

            while (is_array($tokeny[$m] ?? null) && $tokeny[$m][0] === T_WHITESPACE) {
                $m++;
            }

            $arg = $tokeny[$m] ?? null;
            $pole = is_array($arg) && $arg[0] === T_CONSTANT_ENCAPSED_STRING
                ? trim($arg[1], '\'"')
                : '(nieliterałowa nazwa pola)';

            $odczyty[] = sprintf('wiersz %d: dostęp tablicowy → %s', $t[2], $pole);

            continue;
        }

        // (B2) `->coś`
        if (is_array($nastepny) && in_array($nastepny[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true)) {
            $nazwa = $tokeny[$k + 1] ?? null;

            if (! is_array($nazwa) || $nazwa[0] !== T_STRING) {
                $odczyty[] = sprintf('wiersz %d: dostęp dynamiczny', $t[2]);

                continue;
            }

            // `session()` NIE jest odczytem pola wejściowego — to magazyn sesji.
            if ($nazwa[1] === 'session') {
                continue;
            }

            // Właściwość (bez nawiasu) — magiczne `__get`, wektor rundy 10.
            if (($tokeny[$k + 2] ?? null) !== '(') {
                $odczyty[] = sprintf('wiersz %d: właściwość dynamiczna → %s', $t[2], $nazwa[1]);

                continue;
            }

            // DOWOLNA metoda — nie pytamy, jak się nazywa, tylko CO CZYTA.
            $m = $k + 3;

            while (is_array($tokeny[$m] ?? null) && $tokeny[$m][0] === T_WHITESPACE) {
                $m++;
            }

            $arg = $tokeny[$m] ?? null;

            // Metoda bez argumentów (`->all()`, `->keys()`) czyta WSZYSTKO,
            // więc z definicji wykracza poza kontrakt dwóch pól.
            if ($arg === ')') {
                $odczyty[] = sprintf('wiersz %d: ->%s() → CAŁE wejście', $t[2], $nazwa[1]);

                continue;
            }

            $pole = is_array($arg) && $arg[0] === T_CONSTANT_ENCAPSED_STRING
                ? trim($arg[1], '\'"')
                : '(nieliterałowa nazwa pola)';

            $odczyty[] = sprintf('wiersz %d: ->%s() → %s', $t[2], $nazwa[1], $pole);

            continue;
        }

        // (B3) `$request` przekazany dalej / przypisany — NIE jest odczytem pola.
        //      Gdyby współpracownik czytał z niego sekret i ustanawiał tożsamość,
        //      zapaliłaby WARSTWA 2 (`zaloz` spoza callbacku) albo WARSTWA 1
        //      (zapis klucza poza fasadą). Warstwy nie są niezależne i to jest ich sens.
    }

    return $odczyty;
}

it('WARSTWA 3: callback czyta z żądania WYŁĄCZNIE pola kontraktu OIDC — niezależnie od składni', function (): void {
    // Ta warstwa istnieje dla wektora, wobec którego dwie poprzednie są bezradne
    // z definicji: mechanizmu wstawionego do JEDYNEGO miejsca, które ma prawo
    // pisać tożsamość. Kontrakt OIDC mówi, że callback konsumuje `code` i `state`.
    $sciezka = base_path('app/Http/Controllers/LogowanieController.php');

    expect(file_exists($sciezka))->toBeTrue('Nie widzę kontrolera logowania — kontrola mierzy pustkę.');

    $tokeny = token_get_all((string) file_get_contents($sciezka));
    $funkcje = Kod::funkcje($tokeny);
    $callback = null;

    foreach ($funkcje as $f) {
        if ($f['nazwa'] === 'powrot') {
            $callback = $f;
        }
    }

    expect($callback)->not->toBeNull(
        'Nie znalazłem metody `powrot()` w kontrolerze — kontrola mierzyłaby pustkę.'
    );

    /** @var array{nazwa: string, od: int, do: int, linia: int} $callback */
    $odczyty = polaZadaniaCzytaneW($tokeny, $callback);

    // ⛔ PUSTKA TO BŁĄD, NIE ZERO. Callback MUSI czytać `code` i `state` —
    // gdyby skaner nie widział niczego, jego zielone znaczyłoby „nie umiem
    // czytać", a nie „nic obcego tam nie ma".
    expect(count($odczyty))->toBeGreaterThan(1, sprintf(
        'Skaner widzi %d odczytów żądania w `powrot()`, a kontrakt OIDC wymaga co'.
        ' najmniej dwóch (`code`, `state`). Parser rozjechał się z kodem (R10-1).',
        count($odczyty)
    ));

    $obce = [];

    foreach ($odczyty as $odczyt) {
        $pole = trim((string) (explode('→', $odczyt)[1] ?? ''));

        if (! in_array($pole, PARAMETRY_CALLBACKU, true)) {
            $obce[] = $odczyt;
        }
    }

    expect($obce)->toBe([], sprintf(
        'CALLBACK OIDC CZYTA Z ŻĄDANIA COŚ SPOZA SWOJEGO KONTRAKTU:%s  %s%s%s'.
        'To jedyne miejsce, w którym wolno ustanowić tożsamość — więc jedyne, w którym%s'.
        'mechanizm własnych haseł byłby niewidzialny dla warstw 1 i 2.%s'.
        'Kontrakt OIDC konsumuje wyłącznie: %s.',
        PHP_EOL,
        implode(PHP_EOL.'  ', $obce),
        PHP_EOL,
        PHP_EOL,
        PHP_EOL,
        PHP_EOL,
        implode(', ', PARAMETRY_CALLBACKU)
    ));
});

/**
 * Tokeny DRUGIEGO argumentu `SesjaKonta::zaloz(…)` — czyli DANYCH tożsamości.
 *
 * @param  list<array{0: int, 1: string, 2: int}|string>  $tokeny
 * @return list<array{0: int, 1: string, 2: int}|string>
 */
function daneTozsamosciWZaloz(array $tokeny): array
{
    $ile = count($tokeny);

    for ($i = 2; $i < $ile; $i++) {
        $t = $tokeny[$i];

        if (! is_array($t) || $t[0] !== T_STRING || $t[1] !== 'zaloz') {
            continue;
        }
        if (($tokeny[$i + 1] ?? null) !== '(') {
            continue;
        }

        $glebokosc = 0;
        $przecinki = 0;
        $drugi = [];

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
                $przecinki++;

                continue;
            }

            if ($przecinki >= 1) {
                $drugi[] = $x;
            }
        }

        return $drugi;
    }

    return [];
}

it('WARSTWA 4: tożsamość pochodzi ze ZWERYFIKOWANEGO tokenu, nie z pola żądania', function (): void {
    // ⛔ CZWARTE PIĘTRO — pytanie obowiązkowe ze `ZLECENIE-074`.
    //
    // Trzy warstwy zamykają: GDZIE stoi zapis (1), SKĄD go wołano (2)
    // i CO callback czyta z żądania (3). Zostaje wektor, którego żadna
    // z nich nie widzi, bo używa wyłącznie rzeczy DOZWOLONYCH:
    //
    //     SesjaKonta::zaloz($request, ['sub' => $request->query('code')]);
    //
    // Pole `code` JEST w kontrakcie OIDC, więc warstwa 3 milczy słusznie.
    // Zapis stoi w fasadzie i jest wołany z callbacku, więc warstwy 1 i 2
    // milczą słusznie. A tożsamość ustanawia napis podany przez atakującego,
    // z pominięciem wymiany kodu i weryfikacji podpisu.
    //
    // Niezmiennik: **dane tożsamości nie mogą pochodzić z żądania.** Dziś
    // pochodzą z `$claimsId` — ładunku tokenu SPRAWDZONEGO przez
    // `WalidatorTokenu` (podpis, issuer, audiencja, czas). To jest jedyne
    // źródło, które kontrakt §2 dopuszcza.
    $sciezka = base_path('app/Http/Controllers/LogowanieController.php');
    $tokeny = token_get_all((string) file_get_contents($sciezka));
    $dane = daneTozsamosciWZaloz($tokeny);

    // Pustka to błąd, nie zero: bez odczytu argumentu kontrola nie mierzy nic.
    expect(count($dane))->toBeGreaterThan(3,
        'Nie odczytałem drugiego argumentu `SesjaKonta::zaloz()` — parser rozjechał się '.
        'z kodem, a kontrola mierzyłaby pustkę (warstwa 4).');

    $skazone = [];

    foreach ($dane as $t) {
        if (! is_array($t) || $t[0] !== T_VARIABLE) {
            continue;
        }

        if ($t[1] === '$request'
            || in_array($t[1], ['$_POST', '$_GET', '$_REQUEST', '$_COOKIE', '$_SERVER'], true)) {
            $skazone[] = sprintf('wiersz %d: %s', $t[2], $t[1]);
        }
    }

    expect($skazone)->toBe([], sprintf(
        'DANE TOŻSAMOŚCI POCHODZĄ Z ŻĄDANIA, NIE ZE ZWERYFIKOWANEGO TOKENU:%s  %s%s%s'.
        'Warstwy 1–3 milczą tu SŁUSZNIE: zapis jest w fasadzie, wołany z callbacku,%s'.
        'a czytane pole może być w kontrakcie OIDC. Mimo to tożsamość ustanawia%s'.
        'wartość podana przez żądającego, z pominięciem weryfikacji podpisu tokenu.',
        PHP_EOL,
        implode(PHP_EOL.'  ', $skazone),
        PHP_EOL,
        PHP_EOL,
        PHP_EOL,
        PHP_EOL
    ));
});

it('KIERUNEK ODWROTNY W4: skaner danych tożsamości widzi skażenie — na PLIKU pod rękę', function (): void {
    $katalog = sys_get_temp_dir().'/gabinet-w4-'.getmypid();
    @mkdir($katalog, 0777, true);
    $plik = $katalog.'/probka.php';

    $material = [
        // skażone — wartość z żądania jako tożsamość
        'z-zadania' => 'SesjaKonta::zaloz($request, [\'sub\' => $request->query(\'code\')]);',
        'superglobal' => 'SesjaKonta::zaloz($request, [\'sub\' => $_GET[\'sub\']]);',
        // czyste — wartość z claimów zweryfikowanego tokenu
        'z-claimow' => 'SesjaKonta::zaloz($request, [\'sub\' => Typy::napis($claimsId[\'sub\'])]);',
    ];

    $wynik = [];

    try {
        foreach ($material as $etykieta => $tresc) {
            file_put_contents($plik, "<?php\n".$tresc."\n");

            $dane = daneTozsamosciWZaloz(token_get_all((string) file_get_contents($plik)));
            $skazone = 0;

            foreach ($dane as $t) {
                if (is_array($t) && $t[0] === T_VARIABLE
                    && ($t[1] === '$request' || str_starts_with($t[1], '$_'))) {
                    $skazone++;
                }
            }

            $wynik[$etykieta] = $skazone;
        }
    } finally {
        @unlink($plik);
        @rmdir($katalog);
    }

    expect($wynik['z-zadania'])->toBeGreaterThan(0,
        'Skaner NIE widzi tożsamości zbudowanej z pola żądania — to czwarte piętro '.
        'tej samej klasy i bez tego warstwa 4 nie istnieje.');

    expect($wynik['superglobal'])->toBeGreaterThan(0, 'Skaner nie widzi superglobala w danych tożsamości.');

    expect($wynik['z-claimow'])->toBe(0,
        'Skaner oskarża tożsamość zbudowaną z CLAIMÓW zweryfikowanego tokenu — '.
        'czyli jedyną poprawną drogę. Fałszywe oskarżenie uczyniłoby warstwę nieużywalną.');
});

it('KIERUNEK ODWROTNY W3: skaner pól widzi KAŻDĄ składnię odczytu — na PLIKU pod rękę', function (): void {
    // Materiał musi być PLIKIEM: skaner parsuje kod, więc forma podana jako
    // napis byłaby dla niego — słusznie — zwykłym tekstem.
    //
    // Ta kontrola jest odpowiedzią na pytanie „skąd wiadomo, że nowa warstwa 3
    // nie ma po prostu DŁUŻSZEJ listy". Materiał zawiera formy, których żadna
    // lista nazw nie przewidzi: dostęp tablicowy, metodę zmyśloną na potrzeby
    // tego testu, właściwość dynamiczną, superglobal i strumień wejścia.
    $katalog = sys_get_temp_dir().'/gabinet-w3-'.getmypid();
    @mkdir($katalog, 0777, true);
    $plik = $katalog.'/probka.php';

    $material = [
        // --- MUSZĄ być zgłoszone: odczyt pola spoza kontraktu ---
        'tablica' => '$sekret = $request[\'zaklecie\'];',
        'metoda-nieznana' => '$sekret = $request->wymyslonaMetoda(\'zaklecie\');',
        'wlasciwosc' => '$sekret = $request->zaklecie;',
        'pomocnik' => '$sekret = request()->str(\'zaklecie\');',
        'bez-argumentu' => '$wszystko = $request->all();',
        'nieliteralowe' => '$sekret = $request[$nazwaPola];',
        'superglobal' => '$sekret = $_POST[\'zaklecie\'];',
        'superglobal-get' => '$sekret = $_GET[\'zaklecie\'];',
        'superglobal-request' => '$sekret = $_REQUEST[\'zaklecie\'];',
        'superglobal-cookie' => '$sekret = $_COOKIE[\'zaklecie\'];',
        'superglobal-server' => '$sekret = $_SERVER[\'HTTP_X_ZAKLECIE\'];',
        'strumien' => '$sekret = file_get_contents(\'php://input\');',
        // --- NIE WOLNO zgłaszać: kontrakt OIDC i praca na sesji ---
        'kontrakt-metoda' => '$kod = $request->query(\'code\');',
        'kontrakt-tablica' => '$kod = $request[\'code\'];',
        'kontrakt-filled' => 'if (! $request->filled(\'code\')) { return null; }',
        'sesja' => '$request->session()->regenerate();',
        'przekazanie' => 'SesjaKonta::zaloz($request, []);',
    ];

    $wynik = [];

    try {
        foreach ($material as $etykieta => $tresc) {
            file_put_contents($plik, "<?php\nfunction powrot(\$request) {\n".$tresc."\n}\n");

            $tokeny = token_get_all((string) file_get_contents($plik));
            $funkcje = Kod::funkcje($tokeny);

            expect($funkcje)->toHaveCount(1);

            $odczyty = polaZadaniaCzytaneW($tokeny, $funkcje[0]);
            $obce = [];

            foreach ($odczyty as $odczyt) {
                $pole = trim((string) (explode('→', $odczyt)[1] ?? ''));

                if (! in_array($pole, PARAMETRY_CALLBACKU, true)) {
                    $obce[] = $odczyt;
                }
            }

            $wynik[$etykieta] = count($obce);
        }
    } finally {
        @unlink($plik);
        @rmdir($katalog);
    }

    // ---- MUSZĄ zapalić. Dwa pierwsze to dokładnie wektory rundy 10. ----
    expect($wynik['tablica'] ?? -1)->toBe(1,
        'Skaner NIE widzi dostępu tablicowego `$request[…]` — to wektor A rundy 10, '.
        'przez który mechanizm własnych haseł przeszedł całą bramkę.');

    expect($wynik['metoda-nieznana'] ?? -1)->toBe(1,
        'Skaner NIE widzi metody, której nie zna z nazwy. Jeśli tu jest zielono, '.
        'to warstwa 3 znowu jest LISTĄ — tylko dłuższą (wektor B rundy 10).');

    expect($wynik['wlasciwosc'] ?? -1)->toBe(1, 'Skaner nie widzi właściwości dynamicznej (`__get`).');
    expect($wynik['pomocnik'] ?? -1)->toBe(1, 'Skaner nie widzi odczytu przez pomocnik `request()`.');
    expect($wynik['bez-argumentu'] ?? -1)->toBe(1, 'Skaner nie widzi `->all()`, czyli odczytu CAŁEGO wejścia.');
    expect($wynik['nieliteralowe'] ?? -1)->toBe(1,
        'Skaner milczy przy nazwie pola z ZMIENNEJ. Nie da się dowieść, że mieści się '.
        'w kontrakcie, więc milczenie byłoby gałęzią zdegenerowaną.');

    // ⛔ KAŻDY ELEMENT LISTY OSOBNO, NIE JEDEN REPREZENTANT.
    //
    // Sprawdzanie jednego przedstawiciela WŁAŚNIE ZAWIODŁO. Literały
    // `$_GET`, `$_REQUEST`, `$_COOKIE`, `$_FILES`, `$_SERVER` stały w kodzie
    // zapisane z ukośnikiem, który w apostrofach PHP jest ZWYKŁYM ZNAKIEM —
    // więc porównanie nie mogło trafić NIGDY. Kontrola świeciła zielono, bo
    // mierzyła wyłącznie `$_POST`, jedyny zapisany poprawnie.
    //
    // Lista, której kontrola dotyka w jednym miejscu, jest sprawdzona
    // w jednym miejscu; reszta jest deklaracją.
    foreach (['superglobal', 'superglobal-get', 'superglobal-request',
        'superglobal-cookie', 'superglobal-server'] as $przypadek) {
        expect($wynik[$przypadek] ?? -1)->toBe(1, sprintf(
            'Skaner nie widzi superglobala w przypadku `%s`. Wartość z zewnątrz BEZ '.
            'dotykania `\$request` to odpowiedź na pytanie „krok dalej" ze ZLECENIE-074 '.
            '— i musi być sprawdzona dla KAŻDEGO elementu listy, nie dla jednego.',
            $przypadek
        ));
    }

    expect($wynik['superglobal'] ?? -1)->toBe(1,
        'Skaner nie widzi superglobali — a to wartość z zewnątrz BEZ dotykania `$request` '.
        '(odpowiedź na pytanie „krok dalej" ze ZLECENIE-074).');

    expect($wynik['strumien'] ?? -1)->toBe(1, 'Skaner nie widzi odczytu `php://input`.');

    // ---- NIE WOLNO zapalić. Fałszywe oskarżenie jest równie kosztowne: ----
    // uczyniłoby wąskie gardło nieużywalnym, a wtedy ktoś je rozluźni.
    expect($wynik['kontrakt-metoda'] ?? -1)->toBe(0, 'Skaner oskarża legalny odczyt `code` metodą.');
    expect($wynik['kontrakt-tablica'] ?? -1)->toBe(0,
        'Skaner oskarża legalny odczyt `code` DOSTĘPEM TABLICOWYM. Kontrakt dotyczy POLA, '.
        'nie składni — inaczej naprawa R10-1 zamieniłaby jedną listę na drugą.');

    expect($wynik['kontrakt-filled'] ?? -1)->toBe(0, 'Skaner oskarża `filled(\'code\')`, które jest w kontrakcie.');
    expect($wynik['sesja'] ?? -1)->toBe(0, 'Skaner myli pracę na SESJI z odczytem pola wejściowego.');
    expect($wynik['przekazanie'] ?? -1)->toBe(0,
        'Skaner oskarża PRZEKAZANIE obiektu żądania. Gdyby współpracownik czytał z niego '.
        'sekret i ustanawiał tożsamość, zapaliłaby warstwa 1 albo 2.');
});

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
