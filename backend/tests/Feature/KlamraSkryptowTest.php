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
