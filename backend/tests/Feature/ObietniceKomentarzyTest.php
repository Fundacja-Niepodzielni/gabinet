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

        // ⛔ ZASIĘG ZNACZNIKÓW (R6A-7). Poprzedni wzorzec `[UWO]-\d+` obejmował
        // SZEŚĆ rodzin znaczników i pomijał SIEDEM — w tym `B7`, `B8`, `BLK-22`
        // i `D-2026-08-08-24`, czyli WSZYSTKIE, na które powołuje się warstwa
        // `Tozsamosc`. Zdanie z `WYTYCZNE-PRACY.md` o „każdym znalezisku powołanym
        // w kodzie produkcyjnym" było wobec tego wzorca nieprawdziwe.
        //
        // Egzekutor obejmuje dziś rodziny faktycznie używane w tym repozytorium:
        //   · jednoliterowe rejestry rund   — U-7, W-5, O-2, V-8, N-14, E-3, S-1
        //   · znaleziska rund weryfikacji   — R6A-3, R6B-16
        //   · standardy ekosystemu          — B7, B8
        //   · blokery                       — BLK-22
        //   · decyzje z rejestru            — D-2026-08-08-24, D-EKO-012
        $wzorzec = '/\b('
            .'[UWOVNES]-\d+|'
            .'R6[AB]-\d+|'
            .'BLK-\d+|'
            .'D-EKO-\d+|'
            .'D-\d{4}-\d{2}-\d{2}-\d+|'
            .'B\d'
            .')\b/';

        if (preg_match_all($wzorzec, $tresc, $trafienia) === 0) {
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

it('każdy `@dowod:` wskazuje klasę testową, która ISTNIEJE', function (): void {
    // ⛔ ZNALEZISKO NIEZALEŻNEGO WERYFIKATORA (12.08).
    //
    // Adnotacja `@dowod: NazwaTestu` jest w tym repozytorium obietnicą pokrycia:
    // mówi czytelnikowi „ten mechanizm ma świadka i oto on". Dwie z jedenastu
    // wskazywały klasy, KTÓRE NIE ISTNIEJĄ — `SladNieKlamieTest`
    // i `TrwaloscRejestruSesjiTest`. Mechanizmy były przetestowane (w innym,
    // realnym pliku), więc nie było luki w pokryciu — była luka w PRAWDZIWOŚCI
    // dokumentacji, czyli dokładnie ta klasa, którą ten plik egzekwuje.
    //
    // Nic tego nie łapało: kontrola wyżej pyta o ZNACZNIKI znalezisk (`R6B-9`),
    // a nie o istnienie wskazanej klasy. Luka leżała w cieniu drugiej kontroli,
    // zdjętej z bramki (D3) — dwie ślepe plamy nałożone na siebie.
    $brakujace = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(base_path('app'), FilesystemIterator::SKIP_DOTS)
    );

    /** @var SplFileInfo $plik */
    foreach ($iterator as $plik) {
        if ($plik->getExtension() !== 'php') {
            continue;
        }

        $tresc = (string) file_get_contents($plik->getPathname());

        if (preg_match_all('/@dowod:\s*([A-Z][A-Za-z0-9]*Test)\b/', $tresc, $trafienia) === 0) {
            continue;
        }

        foreach (array_unique($trafienia[1]) as $klasa) {
            $sciezki = glob(base_path('tests/**/'.$klasa.'.php')) ?: [];
            $sciezki = array_merge($sciezki, glob(base_path('tests/*/'.$klasa.'.php')) ?: []);

            if ($sciezki === []) {
                $brakujace[] = $klasa.' (obiecany w: '.$plik->getFilename().')';
            }
        }
    }

    sort($brakujace);

    expect($brakujace)->toBe(
        [],
        "Adnotacja `@dowod:` wskazuje klasę testową, której NIE MA:\n  ".
        implode("\n  ", $brakujace)."\n".
        'Obietnica pokrycia wskazująca w próżnię jest gorsza od jej braku: czytelnik '.
        'przestaje szukać świadka, bo wierzy, że go znalazł.'
    );
});

it('KONTROLA NEGATYWNA: skaner `@dowod:` widzi nazwę wskazującą w próżnię', function (): void {
    // Bez tego „zero brakujących" przechodzi także wtedy, gdy wyrażenie nie
    // dopasowuje NICZEGO — czyli gdy kontrola jest ślepa na badane zjawisko.
    $sztuczny = '/** @dowod: KlasaKtorejNieMaTest — obietnica bez pokrycia. */';

    expect(preg_match_all('/@dowod:\s*([A-Z][A-Za-z0-9]*Test)\b/', $sztuczny, $t))->toBe(1);
    expect($t[1][0])->toBe('KlasaKtorejNieMaTest');
    expect(glob(base_path('tests/*/'.$t[1][0].'.php')) ?: [])->toBe([]);
});
