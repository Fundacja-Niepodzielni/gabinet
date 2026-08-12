<?php

declare(strict_types=1);

namespace Tests\Wsparcie;

/**
 * Czytanie KODU, a nie tekstu pliku.
 *
 * Znalezisko R6A-6: kontrola „szyfrowanie domyślnie, czytane z TREŚCI pliku"
 * przechodziła, gdy szukany literał wystąpił w KOMENTARZU — `str_contains()`
 * nie odróżnia kodu od prozy. Ta sama wada trafiła mnie 12.08 w pierwszej
 * minucie życia `WaskieGardloTozsamosciTest`: skaner zabraniający `unserialize`
 * w kodzie produkcyjnym zapalił się na WŁASNYM docbloku, który to słowo
 * wymienia po to, żeby wyjaśnić, dlaczego go tam nie ma.
 *
 * Dwa razy ta sama klasa w dwóch miejscach czyni z niej wzorzec, nie wypadek —
 * dlatego lekarstwem jest jedna funkcja, a nie ostrożniejszy regex u każdego.
 *
 * PARSUJEMY, nie dopasowujemy. `token_get_all()` używa leksera PHP, więc wie,
 * czym jest komentarz, czym docblock, a czym napis zawierający `//`.
 * Wyrażenie regularne tego nie wie i nigdy nie będzie wiedziało: `'// nie'`
 * wewnątrz literału napisowego wygląda dla niego identycznie jak komentarz.
 */
final class Zrodlo
{
    /**
     * Treść pliku PHP z USUNIĘTYMI komentarzami i docblokami.
     *
     * Długość jest zachowywana z dokładnością do liczby wierszy (komentarz
     * zamieniamy na tyle samo znaków nowej linii), żeby numery wierszy
     * w komunikatach kontroli nadal wskazywały to samo miejsce w pliku.
     */
    public static function bezKomentarzy(string $tresc): string
    {
        $wynik = '';

        foreach (token_get_all($tresc) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                $wynik .= str_repeat("\n", substr_count($token[1], "\n"));

                continue;
            }

            $wynik .= is_array($token) ? $token[1] : $token;
        }

        return $wynik;
    }

    /** Kod pliku spod ścieżki, bez komentarzy. */
    public static function kod(string $sciezka): string
    {
        return self::bezKomentarzy((string) file_get_contents($sciezka));
    }
}
