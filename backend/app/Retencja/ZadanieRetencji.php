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
     * @param  string  $kolumnaKlucza  po czym zapamiętujemy i weryfikujemy rekord
     */
    public function wykonaj(
        string $tabela,
        string $kolumnaPochodzenia,
        int $progDni,
        string $kolumnaKlucza = 'id',
    ): Wynik {
        $granica = $this->teraz->subDays($progDni);

        // TYP IDENTYFIKATORA NALEŻY DO DZIEDZINY, NIE DO KONTRAKTU (D-2026-08-09-03).
        // Rozstrzygamy go RAZ, po typie KOLUMNY, a nie po wyglądzie wartości:
        // klucz tekstowy złożony z samych cyfr wyglądałby jak liczba i po cichu
        // zmieniłby reprezentację. To ta sama rodzina co wartość zdegenerowana —
        // dwa światy, jeden odczyt.
        $kluczCalkowity = $this->kluczJestCalkowity($tabela, $kolumnaKlucza);
        $naKlucz = static fn (mixed $k): int|string => $kluczCalkowity ? Typy::liczba($k) : Typy::napis($k);

        // 1. SELEKCJA — zapamiętujemy identyfikatory, żeby móc niezależnie
        //    sprawdzić, czy zniknęły. Sam licznik `delete()` nie wystarcza:
        //    zwraca liczbę wierszy, na których zadziałało polecenie, a nie
        //    stan bazy po nim. Wyzwalacz, reguła albo wycofana transakcja
        //    potrafią tę liczbę uczynić nieprawdziwą.
        /** @var list<int|string> $doUsuniecia */
        $doUsuniecia = array_values(
            DB::table($tabela)
                ->where($kolumnaPochodzenia, '<', $granica)
                ->pluck($kolumnaKlucza)
                ->map($naKlucz)
                ->all()
        );

        if ($doUsuniecia === []) {
            return new Wynik(wybrane: 0, usuniete: 0, pozostale: []);
        }

        // 2. WYKONANIE
        DB::table($tabela)->whereIn($kolumnaKlucza, $doUsuniecia)->delete();

        // 3. WERYFIKACJA NIEZALEŻNYM ZAPYTANIEM — nie ufamy liczbie zwróconej
        //    przez `delete()`. Kontrola musi patrzeć inną drogą niż mechanizm,
        //    który bada (reguła C1): pytamy bazę, co tam ZOSTAŁO.
        /** @var list<int|string> $pozostale */
        $pozostale = array_values(
            DB::table($tabela)
                ->whereIn($kolumnaKlucza, $doUsuniecia)
                ->pluck($kolumnaKlucza)
                ->map($naKlucz)
                ->all()
        );

        return new Wynik(
            wybrane: count($doUsuniecia),
            usuniete: count($doUsuniecia) - count($pozostale),
            pozostale: $pozostale,
        );
    }

    /**
     * Czy kolumna klucza jest typu całkowitego — pytanie do SCHEMATU, nie do wartości.
     *
     * Odpowiedź decyduje o reprezentacji identyfikatora, a nie o tym, czy zadanie
     * zadziała. Brak kolumny NIE jest tu obsługiwany po cichu: pilnuje tego
     * `KluczRetencjiTest`, który zapala się na czerwono, zanim ktokolwiek uruchomi
     * retencję na tabeli bez klucza.
     */
    private function kluczJestCalkowity(string $tabela, string $kolumna): bool
    {
        $typ = DB::selectOne(
            'select data_type from information_schema.columns
              where table_schema = current_schema() and table_name = ? and column_name = ?',
            [$tabela, $kolumna]
        );

        if ($typ === null) {
            return true;   // kolumny nie ma — o tym mówi kontrola, nie ta metoda
        }

        return in_array((string) $typ->data_type, ['smallint', 'integer', 'bigint'], true);
    }
}
