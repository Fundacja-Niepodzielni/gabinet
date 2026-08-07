<?php

declare(strict_types=1);

namespace App\Reguly;

use App\Wsparcie\Typy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Dostęp do wersjonowanej konfiguracji reguł.
 *
 * Dwie operacje i ani jednej więcej:
 *  - `obowiazujace()` — która wersja obowiązuje TERAZ (do nowej rezerwacji),
 *  - `obowiazujaceW()` — która obowiązywała w danej chwili (do odtworzenia historii).
 *
 * Czego tu NIE ma i nie będzie: metody `aktualizuj()`. Zmiana reguł to nowy
 * wiersz z nową datą obowiązywania (`dodajWersje()`), nigdy UPDATE — inaczej
 * „jaka reguła obowiązywała 3 marca" przestaje mieć odpowiedź, a zamrożone
 * zrzuty w rezerwacjach tracą punkt odniesienia.
 */
final class RejestrRegul
{
    /** Reguły obowiązujące w tej chwili — do zamrożenia w nowej rezerwacji. */
    public function obowiazujace(): ZestawRegul
    {
        return $this->obowiazujaceW(CarbonImmutable::now());
    }

    /**
     * Reguły obowiązujące w podanej chwili.
     *
     * Zwraca wersję o NAJPÓŹNIEJSZEJ dacie obowiązywania nie późniejszej niż
     * podana chwila — czyli tę, na którą pacjent faktycznie się zgodził.
     */
    public function obowiazujaceW(CarbonImmutable $chwila): ZestawRegul
    {
        $wiersz = DB::table('konfiguracja_regul')
            ->where('obowiazuje_od', '<=', $chwila)
            ->orderByDesc('obowiazuje_od')
            ->orderByDesc('wersja')
            ->first();

        if ($wiersz === null) {
            // Wersja zerowa wchodzi migracją, więc brak wiersza znaczy, że ktoś
            // ją skasował albo baza nie jest zmigrowana. Cicha podmiana na
            // wartości domyślne byłaby tu najgorszym wyjściem: rezerwacje
            // zamroziłyby reguły, których nikt nie zatwierdził.
            throw new RuntimeException(
                'Brak konfiguracji reguł obowiązującej w '.$chwila->toIso8601String().
                ' — czy migracje zostały wykonane?'
            );
        }

        // `DB::table()->first()` oddaje stdClass, nie tablicę — bez rzutowania
        // `Typy::mapa()` zwróciłoby pustą tablicę i wersja wyszłaby 0.
        $dane = Typy::mapa((array) $wiersz);

        return ZestawRegul::zTablicy(array_merge(
            Typy::mapa(json_decode(Typy::napis($dane['reguly'] ?? null), true)),
            ['wersja' => Typy::liczba($dane['wersja'] ?? null)],
        ));
    }

    /**
     * Dopisuje nową wersję reguł. Nie modyfikuje żadnej istniejącej.
     *
     * @param  array<string, mixed>  $zmiany  pola do nadpisania w stosunku do wersji obowiązującej
     */
    public function dodajWersje(
        array $zmiany,
        CarbonImmutable $obowiazujeOd,
        string $autor,
        string $uzasadnienie,
    ): ZestawRegul {
        // U-9 z rundy 3: data wsteczna PRZEPISYWAŁA historię — zapytanie
        // „jaka reguła obowiązywała 3 marca" zaczynało zwracać co innego niż
        // przed zmianą. Formalnie to był INSERT, ale skutek dokładnie taki,
        // przed jakim ostrzega docblock tej klasy.
        $ostatnia = DB::table('konfiguracja_regul')->max('obowiazuje_od');

        // W-3 z rundy 4: porównanie było OSTRE, więc data RÓWNA ostatniej
        // przechodziła. Wersja zerowa wchodzi migracją z `obowiazuje_od`
        // = 2020-01-01, więc jedno wywołanie tej publicznej metody — bez SQL-a,
        // bez podniesienia uprawnień — przepisywało CAŁĄ historię reguł od 2020
        // roku: zapytanie „co obowiązywało 3 marca" zaczynało zwracać nową
        // wersję. Test z rundy 3 sprawdzał wyłącznie datę ściśle wcześniejszą,
        // czyli dokładnie tę instancję, którą pokazał weryfikator.
        //
        // Przy dacie równej obie wersje mają ten sam moment wejścia w życie
        // i o wyniku decyduje kolejność wierszy — czyli nic.
        if ($ostatnia !== null && CarbonImmutable::parse(Typy::napis($ostatnia))->greaterThanOrEqualTo($obowiazujeOd)) {
            throw new InvalidArgumentException(
                'Nowa wersja reguł musi obowiązywać PÓŹNIEJ niż poprzednia ('
                .Typy::napis($ostatnia).'), podano '.$obowiazujeOd->toDateTimeString().'.'
            );
        }

        $nastepnaWersja = Typy::liczba(DB::table('konfiguracja_regul')->max('wersja')) + 1;

        $nowy = ZestawRegul::zTablicy(array_merge(
            $this->obowiazujace()->doTablicy(),
            $zmiany,
            ['wersja' => $nastepnaWersja],
        ));

        DB::table('konfiguracja_regul')->insert([
            'wersja' => $nastepnaWersja,
            'obowiazuje_od' => $obowiazujeOd,
            'reguly' => json_encode($nowy->doTablicy(), JSON_UNESCAPED_UNICODE),
            'autor' => $autor,
            'uzasadnienie' => $uzasadnienie,
            'created_at' => CarbonImmutable::now(),
        ]);

        return $nowy;
    }
}
