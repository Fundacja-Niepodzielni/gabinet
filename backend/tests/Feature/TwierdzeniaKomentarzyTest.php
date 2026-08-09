<?php

declare(strict_types=1);

/**
 * D3-kod: TWIERDZENIE W KOMENTARZU MUSI WSKAZYWAĆ DOWÓD.
 *
 * Rozszerzenie `ObietniceKomentarzyTest` ze ZNACZNIKÓW na TWIERDZENIA — klasa
 * przekrojowa D3, właściciel projektu: to repozytorium
 * (`_architektura/13-klasy-przekrojowe.md`).
 *
 * Tamten plik pilnuje rzeczy węższej: jeśli komentarz powołuje się na znalezisko
 * („U-7", „W-5"), to musi istnieć test, który je NAZYWA. Sprawdza więc, czy
 * obietnica ma świadka — ale wyłącznie wtedy, gdy autor wpisał identyfikator.
 *
 * Tu pilnujemy klasy szerszej i groźniejszej: **zdania, które ORZEKA O STANIE
 * ŚWIATA** — „nie da się", „niewywoływalne", „gwarantuje", „zamknięte",
 * „potwierdzone". Takie zdanie czyta się jak wynik pracy, a nie jak zamiar,
 * więc następny czytelnik przestaje szukać. Jeśli jest nieprawdziwe, myli
 * ciszej niż kod, bo **nikt go nie uruchamia**.
 *
 * ZMIERZONA INSTANCJA, dla której ta kontrola powstała: docblock
 * `TozsamoscSesji` orzekał, że ścieżka „brak rekordu → utwórz" jest
 * **NIEWYWOŁYWALNA**. Weryfikator rundy 6 wytworzył tożsamość koordynatora
 * trzema wektorami (dane z żądania, `Reflection`, `unserialize`) — twierdzenie
 * **OBALONE** (R6A-3, potwierdzone przez weryfikatora krzyżowego Kont).
 * Zdanie stało w kodzie przez całą rundę i nic nie mogło go zakwestionować.
 *
 * KONTRAKT, którego ta kontrola wymaga — celowo tani:
 *
 *   Komentarz w `app/`, który zawiera SŁOWO ORZEKAJĄCE, musi w tym samym bloku
 *   komentarza nieść znacznik `@dowod:` wskazujący, skąd wiadomo, że to prawda —
 *   nazwę testu, identyfikator znaleziska albo decyzji.
 *
 * Nie da się maszynowo sprawdzić, czy zdanie po polsku jest prawdziwe. Da się
 * wymusić, żeby **autor wskazał świadka** — a wtedy nieprawdziwe twierdzenie
 * przestaje być anonimowe: widać, czego dotyczy dowód i czy jeszcze istnieje.
 *
 * Świadomie NIE wymuszamy, żeby wskazany test istniał: to robi już
 * `ObietniceKomentarzyTest` dla znaczników, a podwójna kontrola tej samej
 * rzeczy dałaby dwa czerwone na jedną przyczynę.
 */

/** Słowa, po których zdanie przestaje być opisem, a staje się ORZECZENIEM o świecie. */
const SLOWA_ORZEKAJACE = [
    'nie da się',
    'niewywoływaln',
    'gwarantuj',
    'zamknięta',
    'zamknięte',
    'naprawion',
    'niemożliw',
];

/**
 * Bloki komentarzy w pliku, każdy jako jeden ciąg.
 *
 * Blok = sąsiadujące linie komentarza. Dzięki temu `@dowod:` może stać
 * w dowolnej linii tego samego docblocka, a nie koniecznie w tej ze słowem.
 *
 * @return list<array{linia:int, tresc:string}>
 */
function blokiKomentarzy(string $sciezka): array
{
    $linie = file($sciezka, FILE_IGNORE_NEW_LINES) ?: [];
    $bloki = [];
    $biezacy = null;

    foreach ($linie as $nr => $linia) {
        $przycieta = ltrim($linia);
        $toKomentarz = str_starts_with($przycieta, '//')
            || str_starts_with($przycieta, '*')
            || str_starts_with($przycieta, '/*');

        if ($toKomentarz) {
            if ($biezacy === null) {
                $biezacy = ['linia' => $nr + 1, 'tresc' => ''];
            }

            $biezacy['tresc'] .= ' '.$przycieta;

            continue;
        }

        if ($biezacy !== null) {
            $bloki[] = $biezacy;
            $biezacy = null;
        }
    }

    if ($biezacy !== null) {
        $bloki[] = $biezacy;
    }

    return $bloki;
}

/** @return list<string> opisy bloków orzekających BEZ wskazanego dowodu */
function twierdzeniaBezDowodu(string $katalog): array
{
    $bez = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($katalog, FilesystemIterator::SKIP_DOTS)
    );

    /** @var SplFileInfo $plik */
    foreach ($iterator as $plik) {
        if ($plik->getExtension() !== 'php') {
            continue;
        }

        foreach (blokiKomentarzy($plik->getPathname()) as $blok) {
            $tresc = mb_strtolower($blok['tresc']);

            $orzeka = false;

            foreach (SLOWA_ORZEKAJACE as $slowo) {
                if (str_contains($tresc, $slowo)) {
                    $orzeka = true;

                    break;
                }
            }

            if (! $orzeka || str_contains($blok['tresc'], '@dowod:')) {
                continue;
            }

            $bez[] = $plik->getFilename().':'.$blok['linia'];
        }
    }

    return $bez;
}

it('każde TWIERDZENIE w komentarzu kodu produkcyjnego wskazuje dowód', function (): void {
    $bezDowodu = twierdzeniaBezDowodu(base_path('app'));

    expect($bezDowodu)->toBe(
        [],
        "Komentarz ORZEKA o stanie świata, nie wskazując, skąd to wiadomo.\n".
        "Dopisz `@dowod: <nazwa testu | znalezisko | decyzja>` w tym samym bloku\n".
        "albo osłab zdanie do opisu zamiaru:\n  ".implode("\n  ", $bezDowodu)
    );
});

it('SKANER NAPRAWDĘ WIDZI twierdzenia — inaczej powyższy test jest pusty', function (): void {
    // Kierunek odwrotny, bez którego „zero bez dowodu" przechodzi także wtedy,
    // gdy skaner nie widzi NICZEGO. To najczęstszy sposób, w jaki kontrola
    // oparta o skanowanie po cichu przestaje działać (nasza lekcja z W-4).
    $katalog = sys_get_temp_dir().'/twierdzenia-'.bin2hex(random_bytes(4));
    mkdir($katalog);

    try {
        // (a) twierdzenie BEZ dowodu — ma zostać znalezione
        file_put_contents($katalog.'/Bez.php', "<?php\n// tej ścieżki nie da się wywołać\nclass Bez {}\n");
        expect(twierdzeniaBezDowodu($katalog))->toHaveCount(1);

        // (b) to samo twierdzenie ZE wskazanym dowodem — ma zostać przepuszczone
        file_put_contents($katalog.'/Ze.php', "<?php\n// tej ścieżki nie da się wywołać\n// @dowod: OdebranieRoliTest\nclass Ze {}\n");
        $wynik = twierdzeniaBezDowodu($katalog);

        expect($wynik)->toHaveCount(1)
            ->and(implode(' ', $wynik))->toContain('Bez.php')
            ->and(implode(' ', $wynik))->not->toContain('Ze.php');
    } finally {
        array_map('unlink', glob($katalog.'/*.php') ?: []);
        rmdir($katalog);
    }
});
