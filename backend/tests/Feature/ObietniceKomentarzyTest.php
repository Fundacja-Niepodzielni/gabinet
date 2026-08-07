<?php

declare(strict_types=1);

/**
 * Komentarz też jest przyrządem pomiarowym — i też kłamie.
 *
 * Reguła z rdzenia „Podejrzewaj najpierw własny przyrząd": dokumentacja o
 * kodzie (komentarz, nagłówek pliku, wpis w dzienniku decyzji) myli następnego
 * czytelnika i weryfikatora CISZEJ niż sam kod. Nikt nie uruchamia komentarza,
 * więc jego nieprawdziwość nie ma jak się ujawnić.
 *
 * W jednej partii napraw złapano u nas TRZY takie obietnice, wszystkie
 * nieprawdziwe wobec kodu:
 *   · nagłówek `perturbacje.sh` — „każda zmiana jest cofana", podczas gdy
 *     perturbacje haseł wołały `migrate:fresh --force` (W-11);
 *   · komentarz w `OcenaAnulacji` — „PHP_INT_MAX obsłużony", podczas gdy
 *     `intdiv()` rzucało `TypeError` (W-5);
 *   · nagłówek `p_puls` — „najpierw ZATRZYMUJEMY harmonogram", podczas gdy
 *     w kodzie nie było ani jednego zatrzymania (W-15).
 *
 * Tego testu nie da się napisać w pełnej ogólności — nie sprawdzimy maszynowo,
 * czy zdanie po polsku jest prawdziwe. Da się natomiast wymusić RZECZ
 * NAJWAŻNIEJSZĄ: jeżeli komentarz w kodzie produkcyjnym powołuje się na
 * znalezisko („U-7", „W-5"), to musi istnieć test, który to samo znalezisko
 * NAZYWA. Obietnica bez dowodu przestaje być obietnicą i staje się czerwienią.
 *
 * Odwrócenie ciężaru dowodu jest tu takie samo jak w `BrakWlasnychHaselTest`
 * i `RetencjaTest`: to autor komentarza ma pokazać test, a nie czytelnik ma
 * zgadywać, czy komentarz nadal opisuje rzeczywistość.
 */

/**
 * Znaczniki znalezisk (U-7, W-5, O-2…) występujące w plikach danego katalogu.
 *
 * @return array<string, list<string>> znacznik → pliki, w których występuje
 */
function znacznikiZnalezisk(string $katalog): array
{
    $znalezione = [];

    // Skaner MUSI pomijać sam siebie — inaczej znajduje własne przykłady
    // i mierzy siebie zamiast repozytorium. Dokładnie ta pułapka wywróciła
    // pierwszą wersję skanera w `BrakWlasnychHaselTest`.
    $wlasnyPlik = (string) realpath(__FILE__);

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($katalog, FilesystemIterator::SKIP_DOTS)
    );

    /** @var SplFileInfo $plik */
    foreach ($iterator as $plik) {
        if ($plik->getExtension() !== 'php') {
            continue;
        }

        if (realpath($plik->getPathname()) === $wlasnyPlik) {
            continue;
        }

        $tresc = (string) file_get_contents($plik->getPathname());

        if (preg_match_all('/\b([UWO]-\d+)\b/', $tresc, $trafienia) === 0) {
            continue;
        }

        foreach (array_unique($trafienia[1]) as $znacznik) {
            $znalezione[$znacznik][] = $plik->getFilename();
        }
    }

    return $znalezione;
}

it('każde znalezisko powołane w kodzie produkcyjnym ma test, który je NAZYWA', function (): void {
    $wKodzie = znacznikiZnalezisk(base_path('app'));
    $wTestach = znacznikiZnalezisk(base_path('tests'));

    expect($wKodzie)->not->toBe([], 'Kod nie powołuje się na żadne znalezisko — sprawdź skaner.');

    $bezDowodu = [];

    foreach ($wKodzie as $znacznik => $pliki) {
        if (! array_key_exists($znacznik, $wTestach)) {
            $bezDowodu[] = $znacznik.' (obiecane w: '.implode(', ', $pliki).')';
        }
    }

    expect($bezDowodu)->toBe(
        [],
        "Komentarz obiecuje naprawę bez testu, który ją nazywa:\n  ".implode("\n  ", $bezDowodu)
    );
});

it('skaner znaczników naprawdę coś znajduje — inaczej powyższy test jest pusty', function (): void {
    // Kierunek odwrotny. Bez niego „zero znaczników bez dowodu" przechodzi
    // również wtedy, gdy skaner nie widzi NICZEGO — a to najczęstszy sposób,
    // w jaki kontrola oparta o skanowanie po cichu przestaje działać.
    $wKodzie = znacznikiZnalezisk(base_path('app'));

    expect(count($wKodzie))->toBeGreaterThanOrEqual(3)
        ->and(array_keys($wKodzie))->toContain('W-5');

    // I odwrotnie po stronie testów: gdyby skaner testów zwracał wszystko,
    // pierwszy test przechodziłby zawsze.
    $wTestach = znacznikiZnalezisk(base_path('tests'));

    expect($wTestach)->not->toHaveKey('W-999');
});
