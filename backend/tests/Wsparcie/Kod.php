<?php

declare(strict_types=1);

namespace Tests\Wsparcie;

use Illuminate\Support\Facades\File;

/**
 * Czytanie STRUKTURY kodu — funkcje, wywołania, zapisy do sesji.
 *
 * ================== PO CO OSOBNA KLASA ==================
 *
 * Rundy 7, 8 i 9 zamknęły po jednej instancji tej samej klasy wady: kontrola
 * tożsamości była LISTĄ (plików, nazw pól, metod czytających), a nie pomiarem
 * niezmiennika. Za każdym razem istniał „krok dalej", bo wyliczanka z definicji
 * ma brzeg, a atakujący wybiera, po której stronie brzegu stanie.
 *
 * `Zrodlo` odpowiada na pytanie „co jest KODEM, a co tekstem". Ta klasa
 * odpowiada na pytanie o piętro wyżej: „GDZIE w kodzie dzieje się rzecz X" —
 * w którym pliku i w której FUNKCJI. Bez tego drugiego pytania allowlista może
 * być tylko listą plików, a plik z jedną legalną linią dostaje zgodę na całą
 * swoją treść (to jest sedno R9-1: mechanizm w `LogowanieController` był
 * niewidzialny, bo ten plik ma zgodę).
 *
 * ================== DLACZEGO NIE WYRAŻENIA REGULARNE ==================
 *
 * Bo pytanie „w jakiej funkcji stoi ta linia" wymaga liczenia klamer, a to
 * jest dokładnie to, czego regex nie umie. Trzy razy w tym repozytorium
 * kontrola tekstowa zapaliła się na własnym komentarzu albo na literale
 * w komunikacie (R6A-6, R7-2, egzekutor N-3) — lekser PHP nie ma tej wady.
 */
final class Kod
{
    /**
     * Katalogi z kodem WYKONYWALNYM — jedno miejsce dla wszystkich skanerów.
     *
     * R9-4: naprawa zasięgu po rundzie 8 objęła JEDNĄ asercję, a dwa inne
     * skanery dalej widziały tylko `app/`. To nie było przeoczenie w tamtej
     * naprawie — to skutek tego, że KAŻDY skaner trzymał własną listę katalogów.
     * Póki listy są rozproszone, „rozszerz zasięg" trzeba wykonać tyle razy,
     * ile jest skanerów, a pominięcie jednego nie daje żadnego sygnału.
     *
     * Odtąd lista jest JEDNA. Dopisanie katalogu wykonywalnego (np. gdyby
     * pojawiły się domknięcia w `config/`) zmienia zasięg WSZYSTKICH kontroli
     * naraz, a nie tej, którą akurat ktoś pamiętał.
     *
     * `bootstrap/` i `config/` są tu, bo oba wykonują PHP: `bootstrap/app.php`
     * składa aplikację i middleware, a pliki `config/` bywają domknięciami.
     *
     * @return list<string>
     */
    public static function katalogiWykonywalne(): array
    {
        $katalogi = [];

        foreach (['app', 'routes', 'bootstrap', 'config'] as $nazwa) {
            $sciezka = base_path($nazwa);

            if (is_dir($sciezka)) {
                $katalogi[] = $sciezka;
            }
        }

        return $katalogi;
    }

    /**
     * Pliki PHP we wszystkich katalogach wykonywalnych — REKURENCYJNIE.
     *
     * `File::files()` nie schodzi w podkatalogi; `allFiles()` schodzi. Runda 9
     * odnotowała nierekurencyjny skan `routes/` jako rzecz niesprawdzoną —
     * zamykamy to od razu, bo różnica kosztuje jedno słowo, a jej brak
     * kosztowałby kolejny „krok dalej".
     *
     * @return list<string>
     */
    public static function plikiWykonywalne(): array
    {
        $pliki = [];

        foreach (self::katalogiWykonywalne() as $katalog) {
            foreach (File::allFiles($katalog) as $plik) {
                if ($plik->getExtension() === 'php') {
                    $pliki[] = $plik->getPathname();
                }
            }
        }

        sort($pliki);

        return $pliki;
    }

    /** Ścieżka względna wobec korzenia `backend/`, z ukośnikami w jedną stronę. */
    public static function wzgledna(string $sciezka): string
    {
        $korzen = str_replace('\\', '/', base_path()).'/';

        return str_replace($korzen, '', str_replace('\\', '/', $sciezka));
    }

    /**
     * Funkcje i metody w pliku, z zakresem tokenów ich CIAŁA.
     *
     * Domknięcia dostają nazwę `{domkniecie}` — nie mają własnej, a i tak
     * muszą być rozróżnialne, bo `routes/web.php` składa się głównie z nich.
     *
     * @param  list<array{0: int, 1: string, 2: int}|string>  $tokeny
     * @return list<array{nazwa: string, od: int, do: int, linia: int}>
     */
    public static function funkcje(array $tokeny): array
    {
        $funkcje = [];
        $ile = count($tokeny);

        for ($i = 0; $i < $ile; $i++) {
            $t = $tokeny[$i];

            if (! is_array($t) || ! in_array($t[0], [T_FUNCTION, T_FN], true)) {
                continue;
            }

            $nazwa = '{domkniecie}';
            $j = $i + 1;

            while ($j < $ile && is_array($tokeny[$j]) && $tokeny[$j][0] === T_WHITESPACE) {
                $j++;
            }

            if (is_array($tokeny[$j] ?? null) && $tokeny[$j][0] === T_STRING) {
                $nazwa = $tokeny[$j][1];
            }

            // `fn () => …` nie ma klamer: ciałem jest wszystko do przecinka
            // albo średnika na poziomie zero. Traktujemy je jako jedną linię —
            // dla naszych pytań to wystarcza, bo strzałkowe domknięcie nie
            // pomieści mechanizmu logowania bez wywołania czegoś nazwanego.
            if ($t[0] === T_FN) {
                $funkcje[] = ['nazwa' => $nazwa, 'od' => $i, 'do' => $i, 'linia' => $t[2]];

                continue;
            }

            // Szukamy `{` otwierającego ciało, pomijając parametry i typ zwrotny.
            $glebokosc = 0;
            $poczatek = null;

            for ($k = $j; $k < $ile; $k++) {
                $x = $tokeny[$k];

                if ($x === '(') {
                    $glebokosc++;
                } elseif ($x === ')') {
                    $glebokosc--;
                } elseif ($x === '{' && $glebokosc === 0) {
                    $poczatek = $k;

                    break;
                } elseif ($x === ';' && $glebokosc === 0) {
                    break;   // metoda abstrakcyjna albo z interfejsu — bez ciała
                }
            }

            if ($poczatek === null) {
                continue;
            }

            $klamry = 0;

            for ($k = $poczatek; $k < $ile; $k++) {
                $x = $tokeny[$k];

                if ($x === '{' || (is_array($x) && in_array($x[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
                    $klamry++;
                } elseif ($x === '}') {
                    $klamry--;

                    if ($klamry === 0) {
                        $funkcje[] = ['nazwa' => $nazwa, 'od' => $poczatek, 'do' => $k, 'linia' => $t[2]];

                        break;
                    }
                }
            }
        }

        return $funkcje;
    }

    /**
     * Nazwa funkcji obejmującej dany token — NAJWĘŻSZA, gdy jest zagnieżdżenie.
     *
     * @param  list<array{nazwa: string, od: int, do: int, linia: int}>  $funkcje
     */
    public static function funkcjaDla(array $funkcje, int $indeks): string
    {
        $nazwa = '{poziom pliku}';
        $szerokosc = PHP_INT_MAX;

        foreach ($funkcje as $f) {
            if ($indeks < $f['od'] || $indeks > $f['do']) {
                continue;
            }

            $obecna = $f['do'] - $f['od'];

            if ($obecna < $szerokosc) {
                $szerokosc = $obecna;
                $nazwa = $f['nazwa'];
            }
        }

        return $nazwa;
    }
}
