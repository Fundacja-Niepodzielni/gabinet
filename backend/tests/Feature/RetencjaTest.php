<?php

declare(strict_types=1);

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

/**
 * Rejestr retencji: tabela → po czym liczymy termin i dlaczego.
 *
 * `kolumna_pochodzenia` MUSI wskazywać moment POWSTANIA rekordu albo inne
 * pole niezmienne po utworzeniu. Nigdy kolumnę stanu (`status`, `etap`), bo
 * wtedy zmiana stanu przesuwa termin usunięcia.
 */
const REJESTR_RETENCJI = [
    'pacjenci' => [
        'kolumna_pochodzenia' => 'created_at',
        'podstawa' => 'RODO art. 9 — dane o zdrowiu; okres do ustalenia z IOD (P-3 w DPIA).',
        'sposob_usuniecia' => 'anonimizacja (zanonimizowany_at), nie DELETE — rezerwacje muszą zostać do rozliczeń.',
    ],
    'zgody' => [
        'kolumna_pochodzenia' => 'created_at',
        'podstawa' => 'Dowód zgody przechowywany tak długo, jak długo może być potrzebny do obrony roszczeń.',
        'sposob_usuniecia' => 'DELETE po ustaniu podstawy — wpis dopisywany, nigdy modyfikowany.',
    ],
    'rezerwacje' => [
        'kolumna_pochodzenia' => 'created_at',
        'podstawa' => 'Dokument księgowy — 5 lat od końca roku podatkowego (ustawa o rachunkowości).',
        'sposob_usuniecia' => 'odpięcie od pacjenta (anonimizacja pacjenta), rekord finansowy zostaje.',
    ],
    'zdarzenia_rezerwacji' => [
        'kolumna_pochodzenia' => 'created_at',
        'podstawa' => 'Dziennik tylko-do-dopisywania; retencja równa retencji rezerwacji.',
        'sposob_usuniecia' => 'kasowanie całych partii po wygaśnięciu rezerwacji nadrzędnej.',
    ],
    'users' => [
        'kolumna_pochodzenia' => 'created_at',
        'podstawa' => 'Konto personelu — usuwane po ustaniu współpracy (sygnał z Kont Niepodzielni).',
        'sposob_usuniecia' => 'DELETE po potwierdzeniu, że nie ma powiązanych decyzji uznaniowych.',
    ],
    'specjalisci' => [
        'kolumna_pochodzenia' => 'created_at',
        'podstawa' => 'Dane współpracownika — okres rozliczeniowy + roszczenia.',
        'sposob_usuniecia' => 'oznaczenie nieaktywności; usunięcie danych kontaktowych po okresie roszczeń.',
    ],
];

/** Tabele bez danych osobowych — świadomie POZA rejestrem retencji. */
const BEZ_DANYCH_OSOBOWYCH = [
    'migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs',
    'konfiguracja_regul', 'uslugi', 'specjalista_usluga',
];

it('każda tabela w bazie jest albo w rejestrze retencji, albo jawnie uznana za wolną od danych osobowych', function (): void {
    // Sedno kontroli: nowa tabela nie może POWSTAĆ bez decyzji o retencji.
    // Bez tego rekord bez ścieżki usunięcia powstaje przez przeoczenie, a nie
    // przez decyzję — i nikt się o tym nie dowie.
    $wBazie = array_map(
        static fn (object $w): string => (string) $w->tablename,
        DB::select("select tablename from pg_tables where schemaname = 'public'")
    );

    $opisane = [...array_keys(REJESTR_RETENCJI), ...BEZ_DANYCH_OSOBOWYCH];
    sort($wBazie);

    $nieopisane = array_values(array_diff($wBazie, $opisane));

    expect($nieopisane)->toBe([], 'Tabele bez decyzji o retencji: '.implode(', ', $nieopisane));
});

it('każdy rekord z retencją MA po czym być wybrany — kolumna pochodzenia istnieje', function (): void {
    // Awaria retencji „w drugą stronę": jeśli kolumna, po której filtruje
    // zadanie czyszczące, nie istnieje, zadanie nie wybierze NIGDY ani jednego
    // rekordu. Nic nie padnie — dane po prostu zostaną na zawsze.
    foreach (REJESTR_RETENCJI as $tabela => $wpis) {
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

    foreach (REJESTR_RETENCJI as $tabela => $wpis) {
        expect($kolumnyStanu)->not->toContain(
            $wpis['kolumna_pochodzenia'],
            "Retencja {$tabela} liczona od kolumny zmiennej w czasie życia rekordu."
        );
    }
});

it('każdy wpis rejestru mówi, JAK rekord znika — nie tylko kiedy', function (): void {
    // „Próg bez ścieżki" to dokładnie ten sam błąd co brak kolumny: termin
    // mija, a nikt nie wie, co ma się wykonać.
    foreach (REJESTR_RETENCJI as $tabela => $wpis) {
        expect($wpis['podstawa'])->not->toBe('', "Brak podstawy retencji dla {$tabela}.")
            ->and(mb_strlen($wpis['sposob_usuniecia']))->toBeGreaterThan(20, "Brak opisu usunięcia dla {$tabela}.");
    }
});
