<?php

declare(strict_types=1);

/*
|---------------------------------------------------------------------------
| Czy zależności z composer.lock SĄ NA DYSKU — U-8 z rundy 3 weryfikacji
|---------------------------------------------------------------------------
| Krok bramki „zależności zgodne z composer.lock" opierał się wyłącznie na
| `composer validate` i `composer install --dry-run`. Obie te komendy czytają
| METADANE (`composer.lock`, `vendor/composer/installed.json`) i nie zaglądają
| na dysk. Weryfikator pokazał, że skasowanie zawartości pakietu w wolumenie
| `vendor` przechodzi przez ten krok bez słowa — krok obiecywał więcej,
| niż sprawdzał.
|
| Ten skrypt pyta o STAN (zasada 1 z nagłówka bramki): dla każdego pakietu
| z locka sprawdza, czy jego katalog istnieje i czy nie jest pusty, oraz czy
| plik wejściowy z autoloadu naprawdę da się wczytać.
|
| CZEGO NIE SPRAWDZA — i nie udaje, że sprawdza: podmiany TREŚCI istniejącego
| pliku w `vendor/`. Composer nie przechowuje sum kontrolnych rozpakowanych
| plików, więc wykrycie tego wymaga podpisanego obrazu i wolumenu tylko do
| odczytu. Zapisane jako dług O-7 (F9, hartowanie obrazu).
*/

$korzen = dirname(__DIR__).'/backend';
$lock = json_decode((string) file_get_contents($korzen.'/composer.lock'), true, 512, JSON_THROW_ON_ERROR);

$braki = [];
$zbadane = 0;

foreach ([...$lock['packages'] ?? [], ...$lock['packages-dev'] ?? []] as $pakiet) {
    $nazwa = (string) $pakiet['name'];
    $katalog = $korzen.'/vendor/'.$nazwa;
    $zbadane++;

    if (! is_dir($katalog)) {
        $braki[] = "{$nazwa}: brak katalogu";

        continue;
    }

    $pliki = glob($katalog.'/*') ?: [];

    if ($pliki === []) {
        $braki[] = "{$nazwa}: katalog pusty";
    }
}

// Autoload musi się realnie wczytać, a nie tylko istnieć.
$mapa = $korzen.'/vendor/composer/autoload_classmap.php';

if (! is_file($mapa)) {
    $braki[] = 'vendor/composer/autoload_classmap.php: brak';
}

if ($braki !== []) {
    fwrite(STDERR, 'BRAKUJĄCE ZALEŻNOŚCI ('.count($braki).' z '.$zbadane." pakietów):\n");

    foreach (array_slice($braki, 0, 20) as $brak) {
        fwrite(STDERR, "  - {$brak}\n");
    }

    fwrite(STDERR, "naprawa: docker compose down -v && docker compose build app && docker compose up -d\n");

    exit(1);
}

echo "wszystkie {$zbadane} pakietów z composer.lock obecne na dysku\n";
