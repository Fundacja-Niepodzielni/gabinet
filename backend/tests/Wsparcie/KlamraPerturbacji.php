<?php

declare(strict_types=1);

namespace Tests\Wsparcie;

use App\Wsparcie\Typy;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * KLAMRA PERTURBACJI — perturbacja, której nie da się zostawić włączonej.
 *
 * Klasa przekrojowa P2 (`_architektura/13-klasy-przekrojowe.md`): kontrola sama
 * wywołuje albo produkuje to, co miała obserwować. Wzorzec naprawy jest nasz
 * (producent / wykonawca / obserwator trzema ścieżkami), więc inne repozytoria
 * biorą go STĄD, z kodu — nie z opisu.
 *
 * ⛔ PO CO TA KLASA ISTNIEJE — przeczytaj, zanim uprościsz.
 *
 * Perturbacja dowodząca, że retencja NAPRAWDĘ kasuje, musi zablokować kasowanie
 * w sposób CICHY: `CREATE RULE … DO INSTEAD NOTHING` sprawia, że `DELETE`
 * kończy się SUKCESEM i nie robi nic. To jest sedno pomiaru — zadanie melduje
 * komplet, a rekord zostaje — i **dokładnie dlatego jest niebezpieczne**.
 *
 * Reguła pozostawiona na żywej instancji to **CICHA BLOKADA KASOWANIA DANYCH
 * OSOBOWYCH**, nieodróżnialna od poprawnego działania. U zespołu helpdesku
 * uderzyłaby w mechanizm, na którym stoi cała retencja RODO.
 *
 * Dlatego perturbacja niesie TRZY zabezpieczenia, wszystkie łącznie:
 *
 *   1. SKAN WSTĘPNY — przed założeniem sprawdzamy, czy reguła nie została po
 *      poprzednim przebiegu. Skan idzie PRZED startem, nie po nim.
 *   2. ODMOWA — znaleziona pozostałość **przerywa przebieg**. Nie sprzątamy jej
 *      po cichu: pozostałość znaczy, że jakiś przebieg zginął, a my nie wiemy,
 *      co jeszcze zostawił. Sprzątanie po fakcie zamieniłoby sygnał w ciszę.
 *   3. KLAMRA — zdjęcie reguły w `finally`, czyli także wtedy, gdy asercja
 *      rzuci. W repozytoriach bez transakcji na test rolę klamry pełni
 *      w skrypcie DWA trapy: `trap sprzataj EXIT` oraz `trap przerwano INT TERM`,
 *      gdzie `przerwano` kończy JAWNYM `exit` (patrz `PRZENOSNOSC` niżej).
 *
 * TU DODATKOWO zamyka nas transakcja `RefreshDatabase` — ale **nie wolno na niej
 * polegać jako na jedynym zabezpieczeniu**, bo repozytoria adaptujące ten wzorzec
 * (helpdesk: żywy Zammad; hub) transakcji na test nie mają. Klamra jest jawna
 * właśnie po to, żeby przenosiła się bez zmian.
 *
 * PRZENOSNOSC — kontrakt dla adaptujących, niezależny od języka:
 *
 *   · artefakt perturbacji musi być NAZWANY jednoznacznie (tu: nazwa reguły),
 *     żeby skan wstępny mógł go szukać po nazwie, a nie po skutku;
 *   · skan wstępny pyta MAGAZYN o istnienie artefaktu, nie pamięć procesu;
 *   · odmowa jest twarda (wyjątek / `exit`), nigdy ostrzeżenie;
 *   · zdjęcie artefaktu wykonuje się na ścieżce, która biegnie także przy
 *     awarii — `finally`, `defer`, a w bashu DWA trapy z jawnym `exit`
 *     w uchwycie INT/TERM. **`trap … EXIT INT TERM` w jednej linii NIE
 *     PRZERYWA przebiegu** — zmierzone: po SIGTERM skrypt leci dalej,
 *     sprząta dwa razy i kończy się kodem 0 (ZLECENIE-022).
 *
 * ZASTRZEŻENIE, KTÓREGO NIE WOLNO POMINĄĆ PRZY ADAPTACJI: ten wzorzec dowodzi
 * **KASOWANIA**, nie **URUCHAMIANIA**. Skopiowany bez osobnej asercji „zadanie
 * jest zaplanowane i naprawdę chodzi" odtwarza helpdeskowe W-17 pod nową nazwą:
 * kasowanie działa, a nikt go nie wywołuje. U nas ten brak jest zmierzony
 * (R6A-11 — `ZadanieRetencji` nie ma ani jednego wywołującego) i należy do
 * rundy PRZEDMIOTU, nie przyrządu.
 */
final class KlamraPerturbacji
{
    /**
     * Blokuje kasowanie w tabeli CICHO i oddaje sprzątaczkę wywołującemu.
     *
     * Wołający ma obowiązek wykonać zwróconą domknięcie w `finally`. Nie
     * chowamy tego w destruktorze: destruktor biegnie w nieokreślonym momencie,
     * a chodzi o to, żeby zdjęcie było WIDOCZNE w kodzie testu.
     *
     * @return callable():void sprzątaczka — wołać w `finally`
     */
    public static function zablokujKasowanie(string $tabela): callable
    {
        $regula = self::nazwaReguly($tabela);

        // 1 + 2. SKAN WSTĘPNY I ODMOWA. Przed czymkolwiek innym.
        if (self::regulaIstnieje($regula)) {
            throw new RuntimeException(
                "ODMOWA STARTU PERTURBACJI: reguła `{$regula}` została w bazie po poprzednim ".
                'przebiegu. To znaczy, że tamten przebieg zginął, zanim ją zdjął — a skoro tak, '.
                'nie wiadomo, co jeszcze zostawił. KASOWANIE W TABELI `'.$tabela.'` JEST TERAZ '.
                'CICHO ZABLOKOWANE: `DELETE` kończy się sukcesem i nie usuwa niczego. '.
                'Zdejmij regułę ręcznie (`DROP RULE '.$regula.' ON '.$tabela.'`), sprawdź, '.
                'czy retencja nadąża, i dopiero wtedy uruchom ponownie. '.
                'Nie sprzątam tego automatycznie — pozostałość jest sygnałem, a nie śmieciem.'
            );
        }

        // 1b. SKAN WSTĘPNY PYTA O WŁASNOŚĆ, NIE O NAZWĘ ARTEFAKTU.
        //
        // Poprawka do mojego własnego wymogu przenośności nr 2, wymuszona przez
        // weryfikację krzyżową hubu (ZLECENIE-011). Sprawdzenie wyżej pyta
        // `pg_rules` o regułę O KONKRETNEJ NAZWIE — więc pozostałość powstała
        // pod INNĄ nazwą, na innej tabeli albo zrobiona WYZWALACZEM zamiast
        // regułą przechodzi przez skan i zostaje na żywej instancji. A jest to
        // dokładnie ta pozostałość, która cicho blokuje kasowanie danych
        // osobowych.
        //
        //   BYŁO:  „czy istnieje reguła X"        → pytanie o NAZWĘ
        //   JEST:  „czy kasowanie tu działa"      → pytanie o STAN ŚWIATA
        //
        // Tylko drugie wyklucza pozostałość, o której nie wiem.
        $powod = null;

        if (! self::kasowanieDziala($tabela, $powod)) {
            throw new RuntimeException(
                "ODMOWA STARTU PERTURBACJI: kasowanie w tabeli `{$tabela}` NIE DZIAŁA jeszcze ".
                "przed założeniem blokady ({$powod}). Perturbacja mierzyłaby cudzą pozostałość ".
                'jako własny skutek — czyli dałaby zielone z niewłaściwego powodu. '.
                'Znajdź, co blokuje kasowanie, i zdejmij to ręcznie.'
            );
        }

        DB::statement("CREATE RULE {$regula} AS ON DELETE TO {$tabela} DO INSTEAD NOTHING");

        // 3. KLAMRA. Wołający wykonuje to w `finally`.
        return static function () use ($regula, $tabela): void {
            DB::statement("DROP RULE IF EXISTS {$regula} ON {$tabela}");
        };
    }

    /** Czy artefakt perturbacji został w magazynie — pytanie do BAZY, nie do pamięci. */
    public static function regulaIstnieje(string $regula): bool
    {
        return DB::table('pg_rules')->where('rulename', $regula)->exists();
    }

    /**
     * Czy kasowanie w tabeli FAKTYCZNIE DZIAŁA — pytanie o własność, nie o nazwę.
     *
     * Dwie drogi, świadomie o różnej mocy, bo jedna z nich ma gałąź zdegenerowaną:
     *
     *   · ZACHOWANIOWA (mocna) — gdy tabela ma wiersze: kasujemy je w PUNKCIE
     *     ZAPISU i sprawdzamy, czy zniknęły, po czym wycofujemy. To rozstrzyga
     *     niezależnie od tego, CZYM kasowanie zablokowano: regułą, wyzwalaczem,
     *     uprawnieniem czy czymkolwiek, o czym nie wiem.
     *
     *   · STRUKTURALNA (słabsza) — gdy tabela jest PUSTA, kasowanie zera wierszy
     *     daje zero i przed, i po, więc pomiar niczego nie odróżnia. Wtedy pytamy
     *     o reguły `DO INSTEAD` na DELETE i o wyzwalacze DELETE — POD DOWOLNĄ
     *     NAZWĄ. Mówimy wprost, że próba była strukturalna: kontrola, która nie
     *     przyznaje się do słabszego trybu, kłamie o własnej mocy.
     *
     * @param  string|null  $powod  wypełniany, gdy kasowanie NIE działa
     */
    public static function kasowanieDziala(string $tabela, ?string &$powod = null): bool
    {
        // Reguły `DO INSTEAD NOTHING`/`DO INSTEAD` na DELETE — pod DOWOLNĄ nazwą.
        // `ev_type = '4'` to DELETE w `pg_rewrite`.
        $reguly = DB::select(
            "select r.rulename from pg_rewrite r
               join pg_class c on c.oid = r.ev_class
              where c.relname = ? and r.ev_type = '4' and r.is_instead",
            [$tabela]
        );

        if ($reguly !== []) {
            $nazwy = implode(', ', array_map(static fn (mixed $w): string => Typy::pole($w, 'rulename'), $reguly));
            $powod = "reguła DO INSTEAD na DELETE: {$nazwy}";

            return false;
        }

        $wyzwalacze = DB::select(
            'select t.tgname from pg_trigger t
               join pg_class c on c.oid = t.tgrelid
              where c.relname = ? and not t.tgisinternal and (t.tgtype & 8) <> 0',
            [$tabela]
        );

        if ($wyzwalacze !== []) {
            $nazwy = implode(', ', array_map(static fn (mixed $w): string => Typy::pole($w, 'tgname'), $wyzwalacze));
            $powod = "wyzwalacz na DELETE: {$nazwy}";

            return false;
        }

        // Próba ZACHOWANIOWA — tylko gdy jest co kasować.
        // `exists()`, a nie `count() === 0`: statyka zapamietuje zawezenie TEGO SAMEGO
        // wyrazenia i po tym `if` uznawala `count()` nizej za NIGDY zerowe — czyli
        // `return true` na koncu metody za kod martwy. Pytanie o istnienie jest innym
        // wyrazeniem i przy okazji mowi wprost, o co pytamy.
        if (! DB::table($tabela)->exists()) {
            $powod = null;

            return true;   // strukturalnie czysto; mocniej dziś nie sprawdzę
        }

        DB::statement('SAVEPOINT proba_kasowania');

        try {
            DB::table($tabela)->delete();
            $zostalo = DB::table($tabela)->count();
        } finally {
            DB::statement('ROLLBACK TO SAVEPOINT proba_kasowania');
        }

        if ($zostalo !== 0) {
            $powod = "próba zachowaniowa: po DELETE zostało {$zostalo} wierszy";

            return false;
        }

        return true;
    }

    public static function nazwaReguly(string $tabela): string
    {
        return "perturbacja_bez_kasowania_{$tabela}";
    }
}
