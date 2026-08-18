<?php

declare(strict_types=1);

namespace Tests\Wsparcie;

use Closure;
use Illuminate\Routing\Route;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;

/**
 * Rozpoznanie tras WŁASNYCH — jedno źródło dla wszystkich siatek.
 *
 * Funkcja `trasaZNaszegoKodu()` mieszkała w `BrakWlasnychHaselTest.php`. Gdy
 * D-1b potrzebowało tej samej odpowiedzi, dostępne były trzy drogi i dwie z nich
 * są w tym repozytorium nazwane po imieniu jako błąd:
 *
 *   · przepisać ją drugi raz — dwa opisy jednej rzeczy rozjeżdżają się po cichu
 *     (lekcja `licz-testy.sh`, pułapka U-2 przy zamku);
 *   · uzależnić jeden plik testowy od drugiego — Pest nie ładuje ich wzajemnie,
 *     więc byłoby to wiązanie przez przypadek kolejności;
 *   · wyciągnąć do wsparcia, tak jak `Zrodlo` po R6A-6.
 *
 * Trzecia. Obie siatki §2 pytają odtąd o „naszość" trasy TYM SAMYM kodem — więc
 * nie da się ich rozjechać, poprawiając jedną.
 */
final class Trasy
{
    /**
     * Czy trasa jest zdefiniowana w NASZYM kodzie?
     *
     * Rozstrzyga PLIK DEFINICJI, nie adres. Trasy pakietów (Horizon, Scramble)
     * mają kontrolery w `vendor/` i nie podlegają deklaracji — ale trasa schowana
     * pod `/horizon/coś` z naszym kontrolerem albo domknięciem zostanie policzona.
     * Przedrostek adresu jest tu bezużyteczny właśnie dlatego, że atakujący
     * wybiera adres.
     */
    public static function zNaszegoKodu(Route $trasa): bool
    {
        $akcja = $trasa->getAction('uses');

        try {
            $plik = match (true) {
                $akcja instanceof Closure => (new ReflectionFunction($akcja))->getFileName(),
                is_string($akcja) && str_contains($akcja, '@') => (new ReflectionClass(self::klasaZAkcji($akcja)))->getFileName(),
                is_string($akcja) && class_exists($akcja) => (new ReflectionClass($akcja))->getFileName(),
                default => null,
            };
        } catch (ReflectionException) {
            return false;
        }

        if (! is_string($plik)) {
            // Trasa bez rozpoznawalnego pliku (np. `Route::view`) traktowana jest
            // jako NASZA — bezpieczniejszy kierunek: pojawi się na liście różnic.
            return true;
        }

        $plik = str_replace(DIRECTORY_SEPARATOR, '/', $plik);

        return ! str_contains($plik, '/vendor/');
    }

    /**
     * Nazwa klasy z zapisu `Kontroler@metoda`.
     *
     * @return class-string
     */
    private static function klasaZAkcji(string $akcja): string
    {
        /** @var class-string */
        return explode('@', $akcja)[0];
    }
}
