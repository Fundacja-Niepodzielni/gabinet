<?php

declare(strict_types=1);

namespace App\Retencja;

use App\Wsparcie\Typy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Wykonawca retencji — z naciskiem na WYKONANIE.
 *
 * Lekcja przekrojowa zespołu helpdesku: kontrole retencji sprawdzają zwykle,
 * **kogo zadanie WYBIERA** do skasowania, a nie **czy rekord realnie ZNIKA**.
 * To dwie różne rzeczy, a RODO wymaga wykonania, nie selekcji. Poprawna
 * selekcja plus niewykonane kasowanie = dane zostają, i nic o tym nie krzyczy.
 *
 * Dlatego ta klasa zwraca `Wynik` z DWIEMA liczbami: ile rekordów wybrano
 * i ile faktycznie zniknęło. Rozbieżność między nimi jest błędem, nie
 * ciekawostką — bo dokładnie ona jest naruszeniem.
 *
 * Okresy retencji są WARTOŚCIAMI ZASTĘPCZYMI do czasu odpowiedzi IOD
 * (pytanie P-3 w `docs/rodo/DPIA-checklista.md`). Mechanizm powstaje teraz,
 * żeby dało się go SPRAWDZIĆ; liczby wchodzą, gdy przyjdą z zewnątrz.
 * Konfiguracja, nie stała w kodzie — z tego samego powodu co reguły anulacji.
 */
final class ZadanieRetencji
{
    public function __construct(private readonly CarbonImmutable $teraz) {}

    /**
     * Kasuje rekordy starsze niż próg i SPRAWDZA, że zniknęły.
     *
     * @param  string  $tabela  tabela z rejestru retencji
     * @param  string  $kolumnaPochodzenia  pole niezmienne po utworzeniu (nigdy kolumna stanu)
     */
    public function wykonaj(string $tabela, string $kolumnaPochodzenia, int $progDni): Wynik
    {
        $granica = $this->teraz->subDays($progDni);

        // 1. SELEKCJA — zapamiętujemy identyfikatory, żeby móc niezależnie
        //    sprawdzić, czy zniknęły. Sam licznik `delete()` nie wystarcza:
        //    zwraca liczbę wierszy, na których zadziałało polecenie, a nie
        //    stan bazy po nim. Wyzwalacz, reguła albo wycofana transakcja
        //    potrafią tę liczbę uczynić nieprawdziwą.
        /** @var list<int> $doUsuniecia */
        $doUsuniecia = array_values(
            DB::table($tabela)
                ->where($kolumnaPochodzenia, '<', $granica)
                ->pluck('id')
                ->map(static fn (mixed $id): int => Typy::liczba($id))
                ->all()
        );

        if ($doUsuniecia === []) {
            return new Wynik(wybrane: 0, usuniete: 0, pozostale: []);
        }

        // 2. WYKONANIE
        DB::table($tabela)->whereIn('id', $doUsuniecia)->delete();

        // 3. WERYFIKACJA NIEZALEŻNYM ZAPYTANIEM — nie ufamy liczbie zwróconej
        //    przez `delete()`. Kontrola musi patrzeć inną drogą niż mechanizm,
        //    który bada (reguła C1): pytamy bazę, co tam ZOSTAŁO.
        /** @var list<int> $pozostale */
        $pozostale = array_values(
            DB::table($tabela)
                ->whereIn('id', $doUsuniecia)
                ->pluck('id')
                ->map(static fn (mixed $id): int => Typy::liczba($id))
                ->all()
        );

        return new Wynik(
            wybrane: count($doUsuniecia),
            usuniete: count($doUsuniecia) - count($pozostale),
            pozostale: $pozostale,
        );
    }
}
