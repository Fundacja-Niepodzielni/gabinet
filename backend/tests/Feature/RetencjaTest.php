<?php

declare(strict_types=1);

use App\Retencja\RejestrRetencji;
use Illuminate\Support\Facades\Schema;

/**
 * Retencja danych osobowych — kontrola OBU KIERUNKÓW awarii.
 *
 * Lekcja zespołu helpdesku: pilnujemy „nie trzymaj za długo", ale awaria
 * retencji działa też w drugą stronę. Rekord, którego ŻADNE zadanie czyszczące
 * nie wybierze — bo brakuje mu pola, po którym retencja filtruje — zostaje
 * NA ZAWSZE. To również naruszenie, tylko ciche: nic nie pada, nic nie
 * alarmuje, a dane leżą po terminie.
 *
 * Druga zasada z tej samej lekcji: retencja idzie za POCHODZENIEM rekordu,
 * nie za jego bieżącym stanem ani kolejką. Inaczej przeniesienie albo
 * eskalacja po cichu zmienia okres przechowywania — a nikt tego nie zauważy,
 * bo nigdzie nie ma zapisu, że termin się przesunął.
 *
 * Ten test jest kontrolą STRUKTURALNĄ i działa ZANIM powstaną same zadania
 * czyszczące (F2). Odwraca ciężar dowodu tak samo jak `BrakWlasnychHaselTest`:
 * każda tabela z danymi osobowymi MUSI być wymieniona w rejestrze poniżej wraz
 * z kolumną, po której retencja filtruje. Nowa tabela z danymi osobowymi
 * zapala test na czerwono, dopóki człowiek świadomie nie dopisze jej podstawy
 * retencji — a nie odwrotnie.
 */

/*
 * Rejestr NIE MIESZKA JUZ W TYM PLIKU.
 *
 * Do 09.08 lista tabel i podstaw retencji byla stala w PLIKU TESTU. Skutek
 * zmierzony w rundzie 2: komenda produkcyjna nie mogla jej przeczytac, wiec
 * zadanie czyszczace musialoby niesc WLASNA liste — a wtedy rejestr
 * i sprzataczka rozjezdzaja sie po cichu i obie sa zielone.
 *
 * Zrodlo prawdy: App\Retencja\RejestrRetencji (CLAUDE.md, zasada 1).
 */

it('każda tabela w bazie jest albo w rejestrze retencji, albo jawnie uznana za wolną od danych osobowych', function (): void {
    // EGZEKUTOR D-2026-08-07-20. `config/retencja.php` mowi, ze wpisanie
    // wartosci domyslnej byloby podjeciem decyzji prawnej przez przeoczenie —
    // i to takiej, ktora KASUJE dane osobowe (CLAUDE.md zasada 10, RODO art. 9).
    // Ta kontrola jest tego egzekutorem: kazda tabela musi byc SKLASYFIKOWANA,
    // wiec nowa tabela bez decyzji zapala bramke zamiast dostac cichy default.
    // Do 18.08 decyzja nie byla NAZWANA w zadnym tescie (odslonelo R9-4).
    // Sedno kontroli: nowa tabela nie może POWSTAĆ bez decyzji o retencji.
    // Bez tego rekord bez ścieżki usunięcia powstaje przez przeoczenie, a nie
    // przez decyzję — i nikt się o tym nie dowie.
    $wBazie = [];

    foreach (DB::select("select tablename from pg_tables where schemaname = 'public'") as $wiersz) {
        /** @var object{tablename: string} $wiersz */
        $wBazie[] = $wiersz->tablename;
    }

    $opisane = [...array_keys(RejestrRetencji::wpisy()), ...RejestrRetencji::nazwyBezDanychOsobowych()];
    sort($wBazie);

    $nieopisane = array_values(array_diff($wBazie, $opisane));

    expect($nieopisane)->toBe([], 'Tabele bez decyzji o retencji: '.implode(', ', $nieopisane));
});

it('każdy rekord z retencją MA po czym być wybrany — kolumna pochodzenia istnieje', function (): void {
    // Awaria retencji „w drugą stronę": jeśli kolumna, po której filtruje
    // zadanie czyszczące, nie istnieje, zadanie nie wybierze NIGDY ani jednego
    // rekordu. Nic nie padnie — dane po prostu zostaną na zawsze.
    foreach (RejestrRetencji::wpisy() as $tabela => $wpis) {
        $kolumna = $wpis['kolumna_pochodzenia'];

        expect(Schema::hasTable($tabela))->toBeTrue("Rejestr retencji wymienia nieistniejącą tabelę {$tabela}.")
            ->and(Schema::hasColumn($tabela, $kolumna))
            ->toBeTrue("Tabela {$tabela} nie ma kolumny {$kolumna} — retencja nie miałaby czego wybrać.");
    }
});

it('retencja liczy się od POCHODZENIA rekordu, nie od jego bieżącego stanu', function (): void {
    // Druga zasada z lekcji helpdesku. Kolumna stanu przesuwałaby termin
    // usunięcia przy każdej zmianie statusu — po cichu i bez śladu.
    $kolumnyStanu = ['status', 'etap', 'kolejka', 'przypisany_do', 'updated_at', 'zanonimizowany_at'];

    foreach (RejestrRetencji::wpisy() as $tabela => $wpis) {
        expect($kolumnyStanu)->not->toContain(
            $wpis['kolumna_pochodzenia'],
            "Retencja {$tabela} liczona od kolumny zmiennej w czasie życia rekordu."
        );
    }
});

it('każdy wpis rejestru mówi, JAK rekord znika — nie tylko kiedy', function (): void {
    // „Próg bez ścieżki" to dokładnie ten sam błąd co brak kolumny: termin
    // mija, a nikt nie wie, co ma się wykonać.
    foreach (RejestrRetencji::wpisy() as $tabela => $wpis) {
        expect($wpis['podstawa'])->not->toBe('', "Brak podstawy retencji dla {$tabela}.")
            ->and(mb_strlen($wpis['sposob_usuniecia']))->toBeGreaterThan(20, "Brak opisu usunięcia dla {$tabela}.");
    }
});
