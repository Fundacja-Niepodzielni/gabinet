<?php

declare(strict_types=1);

namespace Tests\Wsparcie;

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
 *      `trap … EXIT INT TERM` w skrypcie (patrz `PRZENOSNOSC` niżej).
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
 *     awarii — `finally`, `trap EXIT INT TERM`, `defer`.
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

    public static function nazwaReguly(string $tabela): string
    {
        return "perturbacja_bez_kasowania_{$tabela}";
    }
}
