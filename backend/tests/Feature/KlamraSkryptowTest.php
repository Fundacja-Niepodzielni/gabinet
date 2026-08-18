<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Tests\Wsparcie\Zrodlo;

/**
 * `trap … EXIT INT TERM` W JEDNEJ LINII **NIE PRZERYWA PRZEBIEGU**.
 *
 * Zmierzone u siebie (ZLECENIE-022, powtórzenie pomiaru hubu), dwie formy obok
 * siebie, `SIGTERM` w trakcie `sleep`:
 *
 *   forma jednolinijkowa → CLEANUP · „PO SLEEPIE — WYKONALO SIE DALEJ" · CLEANUP · kod 0
 *   forma dwóch trapów   → CLEANUP · kod 130
 *
 * Po zabiciu procesu forma jednolinijkowa **poleciała dalej i zameldowała
 * SUKCES**, a sprzątanie wykonało się dwa razy. Bash po powrocie z uchwytu
 * `INT`/`TERM` wznawia wykonanie — przerwanie wymaga JAWNEGO `exit`.
 *
 * Dlaczego to poważne akurat tutaj: klamra chroni przed zostawieniem na żywej
 * instancji reguły, przez którą kasowanie danych osobowych kończy się sukcesem
 * i nie robi nic. Skrypt biegnący dalej z częściowo zdjętym zabezpieczeniem to
 * dokładnie scenariusz, na który klamra była projektowana.
 *
 * ⚠ Wiedza o tym BYŁA w tym repozytorium (`bramka.sh:206`, `perturbacje.sh:201`,
 * znalezisko U-5) i mimo to napisałem formę wadliwą w nowym skrypcie tego samego
 * dnia — oraz wpisałem ją do KONTRAKTU PRZENOŚNOŚCI klamry, który poszedł do
 * trzech repozytoriów. Wiedza w komentarzu nie propaguje się sama.
 */
it('ŻADEN skrypt nie używa formy `trap … EXIT INT TERM`, która NIE przerywa przebiegu', function (): void {
    $katalog = base_path('../skrypty');

    expect(is_dir($katalog))->toBeTrue('Nie widzę katalogu skryptów — kontrola mierzyłaby pustkę.');

    $wadliwe = [];
    $zbadane = 0;

    foreach (File::files($katalog) as $plik) {
        if ($plik->getExtension() !== 'sh') {
            continue;
        }

        $zbadane++;

        foreach (explode("\n", (string) file_get_contents($plik->getPathname())) as $nr => $linia) {
            if (preg_match('/^\s*#/', $linia) === 1) {
                continue;   // komentarz opisujący idiom, nie jego użycie
            }

            // Wadliwe: JEDEN `trap` obsługujący EXIT razem z INT albo TERM.
            if (preg_match('/^\s*trap\s+\S+.*\bEXIT\b.*\b(INT|TERM)\b/', $linia) === 1) {
                $wadliwe[] = $plik->getFilename().':'.($nr + 1).'  '.trim($linia);
            }
        }
    }

    // Pustka to błąd, nie zero: bez tego kontrola przechodzi, gdy nie widzi plików.
    expect($zbadane)->toBeGreaterThan(3, 'Zbadano za mało skryptów — parser się rozjechał.');

    expect($wadliwe)->toBe(
        [],
        "Forma `trap … EXIT INT TERM` w jednej linii NIE PRZERYWA przebiegu.\n".
        "Po SIGTERM skrypt biegnie DALEJ i kończy się kodem 0, a sprzątanie idzie DWA RAZY.\n".
        "Popraw na dwa trapy:\n".
        "    przerwano() { sprzataj; trap - EXIT; exit 130; }\n".
        "    trap sprzataj EXIT\n".
        "    trap przerwano INT TERM\n".
        'Znalezione: '.implode("\n  ", $wadliwe)
    );
});

it('KIERUNEK ODWROTNY: skaner widzi formę wadliwą — na materiale zbudowanym pod rękę', function (): void {
    // Bez tego „zero znalezionych" przechodzi także wtedy, gdy wzorzec się
    // rozjechał i nie łapie niczego.
    $wadliwa = "\ttrap cofnij_wszystko EXIT INT TERM";
    $poprawna1 = "\ttrap sprzataj EXIT";
    $poprawna2 = "\ttrap przerwano INT TERM";
    $komentarz = '# trap sprzataj EXIT INT TERM — tak NIE wolno';

    $wzorzec = '/^\s*trap\s+\S+.*\bEXIT\b.*\b(INT|TERM)\b/';

    expect(preg_match($wzorzec, $wadliwa))->toBe(1, 'Skaner NIE widzi formy wadliwej — kontrola wyżej jest pusta.');
    expect(preg_match($wzorzec, $poprawna1))->toBe(0, 'Skaner oskarża poprawny `trap … EXIT`.');
    expect(preg_match($wzorzec, $poprawna2))->toBe(0, 'Skaner oskarża poprawny `trap … INT TERM`.');
    expect(preg_match('/^\s*#/', $komentarz))->toBe(1, 'Komentarz nie jest rozpoznawany jako komentarz.');
});

it('bramka NIE podmienia kluczy środowiska gołym `sed -i` — bez odczytu zwrotnego (R6B-7)', function (): void {
    // `sed -i` na wzorcu nieobecnym kończy się KODEM 0 i nie zmienia nic.
    // Zmierzone: plik bez klucza, `sed -i "s|^GABINET_PORT_HTTP=.*|…|"` → kod 0,
    // klucza w pliku 0. „Podmiana wykonana" i „podmiana nie trafiła" dawały
    // identyczny sygnał — a `docker-compose.yml` bierze wtedy wartość domyślną,
    // czyli PORT DEWELOPERA.
    //
    // KOMENTARZE ODFILTROWANE — nagłówek pomocnika cytuje starą postać wywołania.
    $tresc = (string) file_get_contents(base_path('../skrypty/bramka.sh'));
    $kod = (string) preg_replace('~^\s*#.*$~m', '', $tresc);

    // Próg wynosi ZERO, nie jeden. Pierwsza wersja dopuszczała jedno wystąpienie
    // „na wnętrze pomocnika" — a pomocnik używa ZMIENNEJ (`^${klucz}=`), więc
    // wzorzec `\^\w+=` go NIE ŁAPIE. Zmierzone perturbacją: przy progu 1
    // przywrócenie gołego `sed -i` NIE zapaliło kontroli. Trzeci raz dziś mój
    // własny próg był ustawiony na podstawie założenia, nie pomiaru.
    $gole = [];

    foreach (explode("\n", $kod) as $nr => $linia) {
        if (preg_match('/sed -i .*\^\w+=/', $linia) !== 1) {
            continue;
        }
        $gole[] = ($nr + 1).': '.trim($linia);
    }

    expect(count($gole))->toBe(
        0,
        "Gołe `sed -i` na kluczu środowiska poza `ustaw_w_env`:\n  ".implode("\n  ", $gole)."\n".
        'Podmiana bez odczytu zwrotnego nie odróżnia „trafiła" od „nie trafiła".'
    );

    expect(str_contains($kod, 'ustaw_w_env'))->toBeTrue(
        'Brak pomocnika `ustaw_w_env` — podmiany wróciły do gołego `sed -i`.'
    );

    // Pomocnik ma ODMAWIAĆ w obu kierunkach: brak klucza i klucz zwielokrotniony.
    expect(str_contains($kod, 'NIE MA klucza'))->toBeTrue('Pomocnik nie odmawia przy braku klucza.');
    expect(str_contains($kod, 'występuje'))->toBeTrue('Pomocnik nie odmawia przy kluczu zwielokrotnionym.');
});

// ---------------------------------------------------------------------------
// R6A-10 i R6B-16 — środowisko pomiaru jest częścią pomiaru
// ---------------------------------------------------------------------------

it('R6A-10: nazwa pliku środowiska liczona PO sparsowaniu --projekt', function (): void {
    // Znalezisko: `bramka.sh` składała `PLIK_ENV` w linii 73, a `--projekt`
    // parsowała w 98 — więc nazwa pliku IGNOROWAŁA projekt. Dwa równoległe
    // przebiegi o różnych projektach dzieliły JEDEN plik z wygenerowanym
    // `APP_KEY` i `DB_PASSWORD`, a zamek (liczony per projekt) ich nie rozdzielał.
    //
    // Naprawę wykonano 09.08, ale bez kontroli — `KLASY-I-NAPRAWY.md` mówiło
    // „NAPRAWIONE, z dowodem", nie nazywając dowodu, a w tym pliku nie było
    // ani jednej asercji o `PLIK_ENV`. Kolejność w skrypcie da się odwrócić
    // jednym przeniesieniem linii i nic by tego nie złapało.
    $linie = explode("\n", (string) file_get_contents(base_path('../skrypty/bramka.sh')));

    $petla = null;
    $plikEnv = null;

    foreach ($linie as $nr => $linia) {
        if ($petla === null && str_contains($linia, 'while [ $# -gt 0 ]')) {
            $petla = $nr;
        }

        if ($plikEnv === null && preg_match('/^\s*PLIK_ENV=/', $linia) === 1) {
            $plikEnv = $nr;
        }
    }

    expect($petla)->not->toBeNull('Nie znalazłem pętli parsującej argumenty — kontrola mierzy pustkę.');
    expect($plikEnv)->not->toBeNull('Nie znalazłem przypisania `PLIK_ENV` — kontrola mierzy pustkę.');

    expect($plikEnv)->toBeGreaterThan(
        (int) $petla,
        'Nazwa pliku środowiska jest liczona PRZED sparsowaniem `--projekt`. Dwa przebiegi '.
        'o różnych projektach dzielą wtedy jeden plik z `APP_KEY` i `DB_PASSWORD`, '.
        'a zamek liczony per projekt ich nie rozdziela (R6A-10).'
    );
});

/**
 * REJESTR NARZĘDZI SIĘGAJĄCYCH PO `docker compose` — allowlista, nie denylista.
 *
 * R7-7: kontrola R6B-16 sprawdzała `--env-file` WYŁĄCZNIE w `perturbacje.sh`.
 * Powstała po to, żeby zamknąć klasę „narzędzie pomiaru mieli stos dewelopera",
 * i zamknęła ją w jednym pliku. Zmierzone w rundzie 7: `perturbacja-odwrotna.sh`
 * (napisany PO tamtej naprawie) wołał gołe `docker compose`, a `odczyt-przyczyn.py`
 * sklejał polecenie jako `'docker compose ' + reszta`, gubiąc `-p` i `--env-file`.
 * Oba trafiały na projekt domyślny `gabinet` — ten sam, którego `perturbacje.sh`
 * ODMAWIA obsługiwać od rundy 4.
 *
 * Kształt naprawy jest ważniejszy od dwóch dopisanych wpisów: gdyby rejestr był
 * listą plików do sprawdzenia, TRZECI skrypt znów wjechałby niezauważony. Dlatego
 * niżej rejestr jest ZUPEŁNY — osobna asercja żąda, żeby każdy plik w `skrypty/`
 * wspominający `docker compose` był w nim sklasyfikowany. Nowe narzędzie zapala
 * czerwień, dopóki ktoś świadomie nie powie, do której grupy należy.
 *
 * Wyjątki wymagają POWODU SPRAWDZALNEGO, nie zdania w komentarzu — każdy jest
 * niżej zmierzony osobną asercją.
 *
 * @return array<string, string>
 */
function rejestrNarzedziCompose(): array
{
    return [
        'bramka.sh' => 'IZOLOWANY',
        'perturbacje.sh' => 'IZOLOWANY',
        'perturbacja-odwrotna.sh' => 'IZOLOWANY',
        'odczyt-przyczyn.py' => 'IZOLOWANY',

        // Diagnostyka przeciw stojącemu stosowi dewelopera i stosowi `konta`,
        // świadomie POZA bramką i CI (napisane w jej nagłówku). Nie mutuje
        // drzewa, nie woła `migrate:fresh`, nie stawia niczego. Powód
        // sprawdzalny: nie jest krokiem bramki — asercja niżej.
        'keycloak-sprawdz.sh' => 'WYJATEK-DIAGNOSTYKA',

        // `docker compose` występuje tu WYŁĄCZNIE w treści komunikatu
        // podpowiadającego naprawę. Powód sprawdzalny: po odfiltrowaniu
        // komentarzy I LITERAŁÓW NAPISOWYCH fraza znika — asercja niżej.
        'zaleznosci-obecne.php' => 'WYJATEK-NAPIS',
    ];
}

it('R7-7: rejestr narzędzi wołających docker compose jest ZUPEŁNY', function (): void {
    $katalog = base_path('../skrypty');
    expect(is_dir($katalog))->toBeTrue('Nie widzę katalogu skryptów — kontrola mierzyłaby pustkę.');

    $rejestr = rejestrNarzedziCompose();
    $znalezione = [];

    foreach (File::files($katalog) as $plik) {
        $tresc = (string) file_get_contents($plik->getPathname());

        // Komentarze odpadają: wzmianka o `docker compose` w prozie nagłówka
        // nie jest wywołaniem (R6A-6). Napisy zostają — w skrypcie powłoki
        // polecenie w napisie bywa wykonywane przez `eval`/`bash -c`.
        $kod = (string) preg_replace('/^\s*(#|\/\/).*$/m', '', $tresc);

        if (str_contains($kod, 'docker compose')) {
            $znalezione[] = $plik->getFilename();
        }
    }

    sort($znalezione);

    expect(count($znalezione))->toBeGreaterThan(3,
        'Znalazłem mniej niż cztery narzędzia z `docker compose` — skaner rozjechał się '.
        'z katalogiem i kontrola mierzy pustkę.');

    $nieznane = array_values(array_diff($znalezione, array_keys($rejestr)));

    expect($nieznane)->toBe([], sprintf(
        "NARZĘDZIE SPOZA REJESTRU woła `docker compose`: %s\n".
        "Dopisz je do `rejestrNarzedziCompose()` jako IZOLOWANY (własne `-p` i `--env-file`) \n".
        "albo jako WYJATEK-* z powodem, który da się SPRAWDZIĆ asercją.\n".
        "Bez wpisu narzędzie trafia na projekt domyślny `gabinet` — stos dewelopera, \n".
        'z jego bazą i jego `.env` z prawdziwymi sekretami (R7-7).',
        implode(', ', $nieznane)
    ));
});

it('R7-7: każde narzędzie IZOLOWANE podaje własny projekt i własny plik środowiska', function (): void {
    $wadliwe = [];
    $zbadane = 0;

    foreach (rejestrNarzedziCompose() as $nazwa => $rodzaj) {
        if ($rodzaj !== 'IZOLOWANY') {
            continue;
        }

        $sciezka = base_path('../skrypty/'.$nazwa);
        expect(file_exists($sciezka))->toBeTrue("Rejestr wymienia nieistniejący plik $nazwa.");

        $kod = (string) preg_replace(
            '/^\s*(#|\/\/).*$/m',
            '',
            (string) file_get_contents($sciezka)
        );

        $zbadane++;
        $braki = [];

        // `--env-file` mówi compose, skąd brać podstawienia.
        str_contains($kod, '--env-file') || $braki[] = '--env-file';
        // `GABINET_PLIK_ENV` trafia do `docker-compose.yml` jako ścieżka
        // montowana DO kontenera. Bez niego kontener dostaje `./.env`
        // dewelopera MIMO poprawnego `--env-file` (R6B-16).
        str_contains($kod, 'GABINET_PLIK_ENV') || $braki[] = 'GABINET_PLIK_ENV';
        // Własny projekt — inaczej wszystko idzie na `gabinet`.
        preg_match('/-p\s+"?\$?\{?PROJEKT/', $kod) === 1
            || str_contains($kod, "' -p \"' + PROJEKT")
            || $braki[] = 'własny projekt (-p)';

        if ($braki !== []) {
            $wadliwe[] = sprintf('%s → brak: %s', $nazwa, implode(', ', $braki));
        }
    }

    expect($zbadane)->toBeGreaterThan(2, 'Zbadano mniej niż trzy narzędzia izolowane — rejestr się rozjechał.');

    expect($wadliwe)->toBe([], sprintf(
        "NARZĘDZIE POMIARU MIELI STOS DEWELOPERA:\n%s\n\n".
        "Suita biegnąca na tym samym stosie destabilizuje środowisko, które mierzy, \n".
        "a `docker-compose.yml` bez `GABINET_PLIK_ENV` montuje `./.env` z prawdziwymi \n".
        'sekretami do kontenera pomiarowego (R7-7, R6B-16).',
        implode("\n", $wadliwe)
    ));
});

it('R7-7: narzędzie URUCHOMIONE na projekcie dewelopera ODMAWIA — pomiar, nie odczyt kodu', function (): void {
    // Pierwsza wersja tej kontroli szukała w kodzie napisu `PROJEKTY_ZABRONIONE`
    // i oskarżyła `bramka.sh`, która odmawia od zawsze — tylko przez zmienną
    // `PROJEKT_DEWELOPERA`. To był test PISOWNI udający test WŁASNOŚCI: gotowy
    // fałszywie oskarżać poprawne narzędzia i przepuścić narzędzie, które nazwie
    // zmienną tak samo, a odmowy nie wykona.
    //
    // Właściwe pytanie brzmi „czy URUCHOMIONE na projekcie dewelopera odmawia",
    // i da się je zadać wprost. Odmowa musi poprzedzać cokolwiek ciężkiego,
    // więc każde z tych wywołań wraca w ułamku sekundy i nie dotyka dockera.
    $korzen = base_path('..');

    $wywolania = [
        'bramka.sh' => 'bash skrypty/bramka.sh --projekt gabinet --pokaz-srodowisko',
        'perturbacje.sh' => 'GABINET_PERTURBACJE_PROJEKT=gabinet bash skrypty/perturbacje.sh --lista',
        'perturbacja-odwrotna.sh' => 'GABINET_ODWROTNA_PROJEKT=gabinet bash skrypty/perturbacja-odwrotna.sh',
    ];

    $wadliwe = [];

    foreach ($wywolania as $nazwa => $polecenie) {
        $wyjscie = [];
        $kod = 0;
        exec(sprintf('cd %s && %s 2>&1', escapeshellarg($korzen), $polecenie), $wyjscie, $kod);
        $tekst = implode("\n", $wyjscie);

        if ($kod === 0) {
            $wadliwe[] = sprintf('%s → kod 0 (PRZYJĘŁO projekt dewelopera)', $nazwa);

            continue;
        }
        if (! str_contains($tekst, 'ODMOWA')) {
            $wadliwe[] = sprintf('%s → kod %d, ale bez słowa ODMOWA: %s', $nazwa, $kod, mb_substr($tekst, 0, 120));
        }
    }

    expect($wadliwe)->toBe([], sprintf(
        "NARZĘDZIE NIE ODMAWIA PROJEKTU DEWELOPERA:\n%s\n\n".
        'Te narzędzia wołają `migrate:fresh`, zatrzymują bazę i mielą pełne suity. '.
        'Na projekcie `gabinet` kasują dane dewelopera (W-11 z rundy 4).',
        implode("\n", $wadliwe)
    ));

    // POZYTYWNA STRONA TEGO SAMEGO PRZYRZĄDU: gdyby `exec()` był tu wyłączony
    // albo skrypty w ogóle się nie uruchamiały, powyższa pętla dałaby same
    // czerwienie „kod != 0, brak ODMOWA" i wyglądałoby to jak wada narzędzi.
    // Dlatego sprawdzamy, że ta sama droga umie zwrócić kod 0.
    $wyjscie = [];
    $kod = 0;
    exec(sprintf('cd %s && bash skrypty/perturbacje.sh --lista 2>&1', escapeshellarg($korzen)), $wyjscie, $kod);

    expect($kod)->toBe(0,
        'Ta sama droga wywołania NIE UMIE zwrócić zera — czerwienie wyżej mówiłyby '.
        'o przyrządzie, nie o narzędziach.');
    // `toContain()` przyjmuje KOLEJNE IGŁY, nie komunikat — patrz kontrola
    // „komunikat asercji NIE MOŻE być połknięty przez matcher wariadyczny".
    expect(str_contains(implode("\n", $wyjscie), 'Perturbacje:'))->toBeTrue(
        'Wywołanie na dozwolonym projekcie nie wypisało listy — przyrząd mierzy pustkę.'
    );
});

it('R7-7: ograniczenie kontroli żywej jest ZMIERZONE, nie założone', function (): void {
    // Kontrola wyżej uruchamia TRZY narzędzia z czterech. `odczyt-przyczyn.py`
    // zostaje przy sprawdzeniu statycznym, bo w tym kontenerze nie ma Pythona —
    // i to jest fakt do ZMIERZENIA, nie do przyjęcia na słowo. Gdy Python się
    // pojawi, ten test zapali się i przypomni, że kontrolę żywą da się rozszerzyć.
    $kod = 0;
    $wyjscie = [];
    exec('command -v python3 2>&1', $wyjscie, $kod);

    expect($kod)->not->toBe(0, sprintf(
        "W kontenerze JEST już python3 (%s) — rozszerz kontrolę żywą o `odczyt-przyczyn.py`:\n".
        "  GABINET_PERTURBACJE_PROJEKT=gabinet python3 skrypty/odczyt-przyczyn.py  →  ma ODMÓWIĆ.\n".
        'Do dziś to jedyne z czterech narzędzi sprawdzane wyłącznie z kodu (R7-7).',
        implode(' ', $wyjscie)
    ));

    // …a skoro sprawdzenie jest statyczne, niech przynajmniej pyta o właściwą
    // rzecz: literał zabronionego projektu ORAZ wyjście z błędem.
    $py = (string) file_get_contents(base_path('../skrypty/odczyt-przyczyn.py'));

    expect(str_contains($py, 'PROJEKTY_ZABRONIONE'))->toBeTrue(
        'Brak listy projektów zabronionych w `odczyt-przyczyn.py` (R7-7).'
    );
    expect(str_contains($py, 'ODMOWA: odczyt dynamiczny nie dziala na projekcie'))->toBeTrue(
        'Brak ścieżki odmowy w `odczyt-przyczyn.py` — lista bez egzekutora to zdanie, nie kontrola.'
    );
});

it('R7-7: powody obu WYJĄTKÓW są sprawdzalne, nie deklarowane', function (): void {
    // WYJATEK-NAPIS: po odfiltrowaniu komentarzy I napisów fraza ma ZNIKNĄĆ.
    // Gdyby `zaleznosci-obecne.php` kiedykolwiek zaczął compose WOŁAĆ, fraza
    // zostanie w kodzie wykonywalnym i wyjątek przestanie się bronić.
    $kod = Zrodlo::bezKomentarzyINapisow(
        (string) file_get_contents(base_path('../skrypty/zaleznosci-obecne.php'))
    );

    expect(str_contains($kod, 'docker compose'))->toBeFalse(
        'WYJĄTEK-NAPIS przestał się bronić: `zaleznosci-obecne.php` ma `docker compose` '.
        'w kodzie WYKONYWALNYM, nie tylko w treści komunikatu. Przeklasyfikuj go na '.
        'IZOLOWANY albo cofnij zmianę (R7-7).'
    );

    // WYJATEK-DIAGNOSTYKA: powodem jest „poza bramką i poza CI". To da się
    // sprawdzić: bramka nie ma prawa go wołać.
    $bramka = (string) file_get_contents(base_path('../skrypty/bramka.sh'));
    $bezKomentarzy = (string) preg_replace('/^\s*#.*$/m', '', $bramka);

    expect(str_contains($bezKomentarzy, 'keycloak-sprawdz'))->toBeFalse(
        'WYJĄTEK-DIAGNOSTYKA przestał się bronić: bramka woła `keycloak-sprawdz.sh`, '.
        'więc narzędzie NIE jest już poza bramką i musi mieć własny stos (R7-7).'
    );
});

/**
 * Kolejność w uchwycie sprzątania: PRZYWRÓCENIE, potem znacznik.
 *
 * @return list<string> wpisy „nazwa:linia  treść" dla uchwytów w złej kolejności
 */
function uchwytyZeZlaKolejnoscia(string $sciezka): array
{
    $wadliwe = [];

    foreach (explode('
', (string) file_get_contents($sciezka)) as $nr => $linia) {
        if (preg_match('/^\s*#/', $linia) === 1) {
            continue;   // komentarz opisujący wadę nie jest jej użyciem (R6A-6)
        }

        $zdjecie = mb_strpos($linia, 'znacznik_zdejmij');

        if ($zdjecie === false) {
            continue;
        }

        // Przywrócenie w TEJ SAMEJ linii uchwytu — nazwy obu narzędzi.
        $przywrocenie = false;

        foreach (['przywroc_wszystko', 'cofnij_wszystko'] as $nazwa) {
            $poz = mb_strpos($linia, $nazwa);

            if ($poz !== false && $poz < $zdjecie) {
                $przywrocenie = true;
            }
            if ($poz !== false && $poz > $zdjecie) {
                $wadliwe[] = sprintf('%d  %s', $nr + 1, trim($linia));

                continue 2;
            }
        }

        // Linia zdejmuje znacznik i NIE przywraca niczego wcześniej ani później
        // (np. `przerwano() { znacznik_zdejmij; inna_funkcja; }`) — też podejrzana,
        // bo przywrócenie idzie wtedy przez cudzą funkcję, po zdjęciu znacznika.
        if (! $przywrocenie && preg_match('/\(\)\s*\{/', $linia) === 1) {
            $wadliwe[] = sprintf('%d  %s', $nr + 1, trim($linia));
        }
    }

    return $wadliwe;
}

/**
 * Ile razy skrypt ustawia uchwyt dla danego sygnału.
 *
 * `trap` PODMIENIA uchwyt, nie dokłada go do listy — więc drugie `trap … EXIT`
 * po cichu wyrzuca pierwsze. Sprzątanie zapisane w pierwszym przestaje istnieć
 * i nikt tego nie widzi, bo różnica objawia się wyłącznie przy WYJŚCIU.
 *
 * @return array<string, int> sygnał → liczba ustawień
 */
function ustawieniaTrapow(string $sciezka): array
{
    $ile = [];

    foreach (explode('
', (string) file_get_contents($sciezka)) as $linia) {
        if (preg_match('/^\s*#/', $linia) === 1) {
            continue;   // komentarz opisujący idiom nie jest jego użyciem (R6A-6)
        }

        // `trap - EXIT` ZDEJMUJE uchwyt (używane w procedurach przerwania,
        // żeby sprzątanie nie poszło dwa razy) — to nie jest ustawienie.
        if (preg_match('/^\s*trap\s+-\s+/', $linia) === 1) {
            continue;
        }

        if (preg_match('/^\s*trap\s+\S+\s+(.+)$/', $linia, $t) !== 1) {
            continue;
        }

        foreach (preg_split('/\s+/', trim($t[1])) ?: [] as $sygnal) {
            $sygnal = mb_strtoupper(trim((string) $sygnal));

            if ($sygnal === '') {
                continue;
            }

            $ile[$sygnal] = ($ile[$sygnal] ?? 0) + 1;
        }
    }

    return $ile;
}

it('ŻADEN skrypt nie ustawia dwóch uchwytów dla TEGO SAMEGO sygnału', function (): void {
    // ZMIERZONE 18.08 na `bramka.sh`. Stało tam:
    //
    //   trap znacznik_zdejmij EXIT     (wiersz 167)
    //   trap zwolnij_zamek    EXIT     (wiersz 201)
    //
    // Drugie wywołanie wyrzuciło pierwsze, więc bramka NIGDY nie zdejmowała
    // własnego znacznika przy normalnym wyjściu. Po zielonym, zakończonym
    // poprawnie zestawie perturbacji w korzeniu repozytorium stał
    // `.przebieg-pomiarowy` należący do zagnieżdżonej bramki, z martwym PID-em.
    //
    // Dlaczego to groźne, a nie tylko brzydkie: osierocony znacznik sprawia,
    // że strażnik commita widzi „trwa przebieg pomiarowy" i ODMAWIA — po KAŻDYM
    // przebiegu bramki. Odmowa, która pada zawsze, uczy odruchu
    // `rm -rf .przebieg-pomiarowy`, czyli kasowania ochrony bez patrzenia.
    //
    // Rodzina U-5: tam jeden `trap` obsługiwał za dużo sygnałów, tu dwa `trap`
    // walczyły o ten sam. Oba razy różnica widoczna WYŁĄCZNIE przy wyjściu.
    $wadliwe = [];
    $zbadane = 0;

    foreach (File::files(base_path('../skrypty')) as $plik) {
        if ($plik->getExtension() !== 'sh') {
            continue;
        }

        $ustawienia = ustawieniaTrapow($plik->getPathname());

        if ($ustawienia === []) {
            continue;
        }

        $zbadane++;

        foreach ($ustawienia as $sygnal => $ile) {
            if ($ile > 1) {
                $wadliwe[] = sprintf('%s: %s ustawiony %d×', $plik->getFilename(), $sygnal, $ile);
            }
        }
    }

    expect($zbadane)->toBeGreaterThan(2,
        'Zbadano mniej niż trzy skrypty z `trap` — skaner się rozjechał, kontrola mierzy pustkę.');

    expect($wadliwe)->toBe([], sprintf(
        'DWA UCHWYTY DLA JEDNEGO SYGNAŁU — drugi PO CICHU wyrzuca pierwszy:
  %s

'.
        '`trap` podmienia uchwyt, nie dokłada. Sprzątanie zapisane w pierwszym
'.
        'przestaje istnieć, a różnicę widać wyłącznie przy WYJŚCIU ze skryptu.
'.
        'Złóż oba w jedną procedurę i ustaw ją RAZ.',
        implode('
  ', $wadliwe)
    ));
});

it('KIERUNEK ODWROTNY: skaner uchwytów widzi podwojenie — na PLIKU pod rękę', function (): void {
    $katalog = sys_get_temp_dir().'/gabinet-trapy-'.getmypid();
    @mkdir($katalog, 0777, true);

    $material = [
        'podwojony.sh' => '#!/usr/bin/env bash
trap a EXIT
trap b EXIT
',
        'pojedynczy.sh' => '#!/usr/bin/env bash
trap sprzataj EXIT
trap przerwano INT TERM
',
        'zdjecie.sh' => '#!/usr/bin/env bash
trap sprzataj EXIT
przerwano() { trap - EXIT; exit 130; }
',
        'komentarz.sh' => '#!/usr/bin/env bash
trap sprzataj EXIT
# trap zwolnij EXIT — tak NIE wolno
',
    ];

    foreach ($material as $nazwa => $tresc) {
        file_put_contents($katalog.'/'.$nazwa, $tresc);
    }

    try {
        $wynik = [];

        foreach (array_keys($material) as $nazwa) {
            $wynik[$nazwa] = ustawieniaTrapow($katalog.'/'.$nazwa);
        }
    } finally {
        foreach (array_keys($material) as $nazwa) {
            @unlink($katalog.'/'.$nazwa);
        }
        @rmdir($katalog);
    }

    expect($wynik['podwojony.sh']['EXIT'] ?? 0)->toBe(2,
        'Skaner NIE widzi dwóch uchwytów tego samego sygnału — kontrola wyżej jest pusta.');

    expect($wynik['pojedynczy.sh']['EXIT'] ?? 0)->toBe(1,
        'Skaner nie liczy poprawnego, jednokrotnego uchwytu.');

    expect($wynik['pojedynczy.sh']['TERM'] ?? 0)->toBe(1,
        'Skaner gubi sygnały wymienione jako drugi i dalszy argument.');

    // `trap - EXIT` ZDEJMUJE uchwyt — policzony jako ustawienie dałby fałszywe
    // oskarżenie każdej poprawnej procedury przerwania w tym repozytorium.
    expect($wynik['zdjecie.sh']['EXIT'] ?? 0)->toBe(1,
        'Skaner liczy `trap - EXIT` jako USTAWIENIE — oskarżałby poprawną formę.');

    expect($wynik['komentarz.sh']['EXIT'] ?? 0)->toBe(1,
        'Skaner liczy uchwyt z KOMENTARZA — nawrót R6A-6.');
});

it('N-3: perturbacja pulsu NIE cytuje magazynu z pamięci — pyta o niego mechanizm', function (): void {
    // Klasa N-3: przeniesienie mechanizmu unieważnia perturbacje, które go
    // cytują, i robi to PO CICHU. Puls przeszedł TRZY adresy — cache → tabela
    // `sygnaly_zdrowia` → plik `storage/puls-harmonogramu` — a `p_puls` za
    // każdym razem zostawał przy poprzednim.
    //
    // Za pierwsze dwa razy scenariusz świecił ZIELONO: mutacja szła w magazyn,
    // którego nie ma, więc puls zostawał na miejscu, `--sprawdz` słusznie
    // przechodziło, a perturbacja nie mierzyła NICZEGO. Trzeci raz wyszedł
    // głośno tylko przez przypadek — tabela zniknęła, więc poleciał wyjątek
    // „relation sygnaly_zdrowia does not exist". Gdyby została pusta, nadal
    // byłoby zielono.
    //
    // Egzekutor: mutacja ma pytać o adres SAM MECHANIZM (`gabinet:puls --gdzie`),
    // a nie powtarzać go z pamięci. Wtedy czwarta przenosina nie wymaga zmiany
    // w skrypcie i nie ma jak przejść niezauważona.
    $tresc = (string) preg_replace(
        '/^\s*#.*$/m',
        '',
        (string) file_get_contents(base_path('../skrypty/perturbacje.sh'))
    );

    // Wycinamy scenariusz pulsu — pytamy o JEGO wnętrze, nie o cały plik.
    $ma = preg_match('/^p_puls\(\)\s*\{.*?^\}/ms', $tresc, $trafienie) === 1;

    expect($ma)->toBeTrue('Nie znalazłem scenariusza `p_puls` — kontrola mierzyłaby pustkę (N-3).');

    $scenariusz = $trafienie[0] ?? '';

    // ⛔ SZUKAMY PRZYPISANIA Z PODSTAWIENIA POLECENIA, NIE FRAZY.
    //
    // Pierwsza wersja robiła `str_contains($scenariusz, 'gabinet:puls --gdzie')`
    // i kontrola pozytywna JEJ NIE OBALIŁA: po zamianie prawdziwego wywołania na
    // ścieżkę wpisaną z pamięci test nadal przechodził, bo ta sama fraza stoi
    // w komunikacie `printf` obok — w treści zdania „`gabinet:puls --gdzie` nic
    // nie oddało". Napis wystarczał, żeby warunek był spełniony.
    //
    // To trzeci raz TEGO SAMEGO DNIA: R6A-6 (literał w komentarzu), R7-2 (literał
    // w napisie), a teraz literał w komunikacie — w kontroli pisanej po to, żeby
    // zamknąć klasę „perturbacja cytuje mechanizm z pamięci". Filtrowanie
    // komentarzy nie wystarcza, bo `printf` komentarzem nie jest.
    //
    // Pytamy więc o STRUKTURĘ: ścieżka musi POWSTAĆ z podstawienia polecenia
    // (`plik_pulsu="$( … --gdzie … )"`). Tego żaden komunikat nie spełni, bo
    // komunikat niczego nie przypisuje.
    $pytaMechanizm = preg_match(
        '/plik_pulsu="\$\([^"]*gabinet:puls\s+--gdzie/',
        $scenariusz
    ) === 1;

    expect($pytaMechanizm)->toBeTrue(
        'Perturbacja pulsu nie POBIERA adresu magazynu z `gabinet:puls --gdzie` — '.
        'ścieżka jest wpisana wprost. Po przenosinach mutacja celuje wtedy w pustkę, '.
        'a scenariusz świeci zielono, nie mierząc niczego (N-3).'
    );

    // …i NIE MA prawa cytować żadnego z dwóch poprzednich magazynów.
    $porzucone = [];

    foreach (['sygnaly_zdrowia', 'Cache::forget', 'cache:forget'] as $stary) {
        if (str_contains($scenariusz, $stary)) {
            $porzucone[] = $stary;
        }
    }

    expect($porzucone)->toBe([], sprintf(
        'Perturbacja pulsu cytuje PORZUCONY magazyn: %s
'.
        'Puls mieszka dziś w pliku wskazywanym przez `gabinet:puls --gdzie` (N-3).',
        implode(', ', $porzucone)
    ));

    // Mechanizm MUSI umieć odpowiedzieć — inaczej egzekutor wyżej opisuje
    // wywołanie, które nic nie zwraca, a mutacja znów trafia w pustkę.
    $polecenie = (string) file_get_contents(base_path('app/Console/Commands/Puls.php'));

    expect(str_contains($polecenie, '--gdzie'))->toBeTrue(
        '`gabinet:puls` nie ma opcji `--gdzie`, a perturbacja o nią pyta. '.
        'Wywołanie oddałoby pustkę i mutacja poszłaby w nieokreślone miejsce (N-3).'
    );
});

it('ZNACZNIK przebiegu pada OSTATNI — po przywróceniu drzewa', function (): void {
    // ZMIERZONE 12.08 na własnym drzewie. Pełny przebieg perturbacji został
    // przerwany sekwencją SIGTERM+SIGKILL (tak kończy procesy każdy sensowny
    // nadzorca). Uchwyt zdążył zdjąć znacznik i NIE zdążył przywrócić plików:
    //
    //   backend/routes/web.php  →  ŻYWA trasa `/wejscie/zaloz` z `Hash::make`
    //   .przebieg-pomiarowy     →  BRAK
    //
    // Czyli drzewo z żywą perturbacją i strażnik commita już rozbrojony —
    // dokładnie stan, w którym 09.08 perturbacja wjechała do repozytorium (N-10).
    //
    // Sam SIGTERM kończy się poprawnie (zmierzone: kod 130, mutacja cofnięta),
    // więc wada mieszka WYŁĄCZNIE w oknie między zdjęciem znacznika a końcem
    // przywracania. Zamykamy je kolejnością: znacznik pada ostatni.
    $skrypty = ['perturbacje.sh', 'perturbacja-odwrotna.sh'];
    $wadliwe = [];
    $zbadane = 0;

    foreach ($skrypty as $nazwa) {
        $sciezka = base_path('../skrypty/'.$nazwa);

        expect(file_exists($sciezka))->toBeTrue("Nie widzę `$nazwa` — kontrola mierzyłaby pustkę.");

        // Pustka to błąd: skrypt bez znacznika w ogóle nie chroni drzewa.
        $tresc = (string) preg_replace('/^\s*#.*$/m', '', (string) file_get_contents($sciezka));

        expect(str_contains($tresc, 'znacznik_zdejmij'))->toBeTrue(
            "`$nazwa` nie zdejmuje znacznika przebiegu — albo go nie zakłada, albo zostawia "
            .'na zawsze. Jedno i drugie rozbraja strażnika commita.'
        );

        $zbadane++;

        foreach (uchwytyZeZlaKolejnoscia($sciezka) as $wpis) {
            $wadliwe[] = $nazwa.':'.$wpis;
        }
    }

    expect($zbadane)->toBe(count($skrypty), 'Zbadano mniej skryptów, niż wymieniono.');

    expect($wadliwe)->toBe([], sprintf(
        'ZNACZNIK ZDEJMOWANY PRZED PRZYWRÓCENIEM DRZEWA:
  %s

'.
        'Zabicie w tym oknie zostawia ŻYWĄ PERTURBACJĘ w drzewie, a strażnik commita
'.
        'jest już rozbrojony — commit utrwali stan sperturbowany (N-10).
'.
        'Popraw kolejność: `przywroc_wszystko; znacznik_zdejmij`.',
        implode('
  ', $wadliwe)
    ));
});

it('KIERUNEK ODWROTNY: skaner kolejności widzi formę wadliwą — na PLIKU pod rękę', function (): void {
    $katalog = sys_get_temp_dir().'/gabinet-kolejnosc-'.getmypid();
    @mkdir($katalog, 0777, true);

    $material = [
        'zla.sh' => '#!/usr/bin/env bash
sprzataj() { znacznik_zdejmij; przywroc_wszystko; }
',
        'dobra.sh' => '#!/usr/bin/env bash
sprzataj() { przywroc_wszystko; znacznik_zdejmij; }
',
        'zla-posrednia.sh' => '#!/usr/bin/env bash
przerwano() { znacznik_zdejmij; inna_funkcja; }
',
        'komentarz.sh' => '#!/usr/bin/env bash
# sprzataj() { znacznik_zdejmij; przywroc_wszystko; } — tak NIE wolno
',
    ];

    foreach ($material as $nazwa => $tresc) {
        file_put_contents($katalog.'/'.$nazwa, $tresc);
    }

    try {
        $wynik = [];

        foreach (array_keys($material) as $nazwa) {
            $wynik[$nazwa] = uchwytyZeZlaKolejnoscia($katalog.'/'.$nazwa);
        }
    } finally {
        foreach (array_keys($material) as $nazwa) {
            @unlink($katalog.'/'.$nazwa);
        }
        @rmdir($katalog);
    }

    expect($wynik['zla.sh'])->toHaveCount(1,
        'Skaner NIE widzi znacznika zdejmowanego przed przywróceniem — kontrola wyżej jest pusta.');

    expect($wynik['dobra.sh'])->toBe([],
        'Skaner oskarża POPRAWNĄ kolejność — wypchnąłby z użycia właściwą formę.');

    expect($wynik['zla-posrednia.sh'])->toHaveCount(1,
        'Skaner nie widzi zdjęcia znacznika przed wywołaniem CUDZEJ funkcji sprzątającej.');

    expect($wynik['komentarz.sh'])->toBe([],
        'Skaner oskarża KOMENTARZ opisujący formę wadliwą — nawrót R6A-6.');
});

it('R6B-16: perturbacje NIE MOGĄ wołać docker compose bez własnego pliku środowiska', function (): void {
    // Znalezisko: `bramka.sh` buduje własny plik z `.env.example` i podaje
    // `--env-file`, a `perturbacje.sh` nie robiła ani jednego — więc
    // `docker-compose.yml` montował `./.env` DEWELOPERA, z prawdziwymi sekretami.
    // Łamało to regułę „klon weryfikatora NIGDY nie trzyma prawdziwych sekretów"
    // i unieważniało porównywalność wyników między maszyną wykonawcy a czystym
    // klonem. V-2 zamknięto wtedy tylko po jednej stronie narzędzi.
    //
    // Komentarze odfiltrowane: wzmianka o `--env-file` w prozie nie jest
    // podaniem `--env-file` (R6A-6, ta sama klasa).
    $kod = (string) preg_replace(
        '/^\s*#.*$/m',
        '',
        (string) file_get_contents(base_path('../skrypty/perturbacje.sh'))
    );

    expect(str_contains($kod, '--env-file'))->toBeTrue(
        'Perturbacje wołają `docker compose` bez `--env-file`, więc montują `.env` DEWELOPERA '.
        'z prawdziwymi sekretami (R6B-16).'
    );

    expect(str_contains($kod, 'GABINET_PLIK_ENV'))->toBeTrue(
        'Brak podstawienia `GABINET_PLIK_ENV` — `docker-compose.yml` montuje wtedy domyślne `./.env`.'
    );

    // DOWÓD, NIE DEKLARACJA. Podanie `--env-file` mówi o ZAMIARZE; dopiero
    // porównanie skrótu pliku na hoście ze skrótem w kontenerze mówi, że
    // kontener NAPRAWDĘ dostał nasz plik. Bez tego zostaje nam wiara.
    expect(str_contains($kod, 'srodowisko_zamontowane'))->toBeTrue(
        'Brak sprawdzenia, czy kontener naprawdę ma nasz plik środowiska. `--env-file` bez '.
        'weryfikacji jest deklaracją: stos podniesiony wcześniej wozi stary plik, a skrypt '.
        'o tym nie wie.'
    );
});
