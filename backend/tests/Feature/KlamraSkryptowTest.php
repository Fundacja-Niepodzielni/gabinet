<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

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
