<?php

declare(strict_types=1);

namespace App\Retencja;

/**
 * REJESTR RETENCJI — jedno źródło prawdy dla kontroli strukturalnej i dla
 * zadania czyszczącego.
 *
 * Przeniesiony 09.08 z `tests/Feature/RetencjaTest.php` do kodu produkcyjnego.
 * Powód jest twardy, nie porządkowy: rejestr mieszkał w PLIKU TESTU, więc
 * komenda produkcyjna nie mogła go przeczytać. Każde zadanie czyszczące
 * musiałoby nieść **własną** listę tabel — a wtedy rejestr i sprzątaczka
 * rozjeżdżają się po cichu i nikt tego nie łapie, bo obie są „zielone".
 * CLAUDE.md, zasada 1: jedna funkcja rozstrzygająca na regułę, wołana przez
 * wszystkie moduły.
 *
 * ================== OKRES RETENCJI ==================
 *
 * `okres_dni` jest osobnym polem i wolno mu być `null`. `null` znaczy
 * **NIEUSTALONY**, a nie „zero" i nie „bez ograniczeń". Zadanie czyszczące
 * ODMAWIA dotknięcia takiej tabeli.
 *
 * To jest świadome zastosowanie reguły „nieznane → odmowa": okresy retencji dla
 * danych o zdrowiu (RODO art. 9) rozstrzyga IOD w DPIA, a nie programista
 * przy pisaniu sprzątaczki. Wpisanie tu wartości domyślnej byłoby podjęciem
 * decyzji prawnej przez przeoczenie — i to takiej, która KASUJE dane.
 *
 * @dowod: HarmonogramRetencjiTest — „zadanie odmawia tabeli o nieustalonym okresie".
 */
final class RejestrRetencji
{
    /**
     * Tabela → po czym liczymy termin, na jakiej podstawie, jak znika i po ilu dniach.
     *
     * `kolumna_pochodzenia` MUSI wskazywać moment POWSTANIA rekordu albo inne
     * pole niezmienne po utworzeniu. Nigdy kolumnę stanu (`status`, `etap`), bo
     * wtedy zmiana stanu przesuwa termin usunięcia.
     *
     * `kasuje` mówi, czy rekord znika przez DELETE. Tabele anonimizowane mają
     * `false` — sprzątaczka kasująca NIE MA ich dotykać, bo rezerwacje i rekordy
     * finansowe muszą zostać.
     *
     * @return array<string, array{kolumna_pochodzenia: string, kolumna_klucza: string, opis_dla_czlowieka: string, podstawa: string, sposob_usuniecia: string, kasuje: bool, okres_dni: int|null}>
     */
    public static function wpisy(): array
    {
        $wpisy = self::opis();

        // Okres DOKLEJAMY z konfiguracji, zamiast trzymać go w tablicy wyżej.
        // Powód: rejestr opisuje decyzję TECHNICZNĄ (po czym liczymy, jak znika),
        // a okres jest decyzją PRAWNĄ (IOD, DPIA). Trzymane razem, jedno
        // zaczęłoby udawać drugie — a przy okresie oznacza to kasowanie danych
        // osobowych na podstawie wartości, której nikt świadomie nie wybrał.
        /** @var array<string, int|null> $okresy */
        $okresy = (array) config('retencja.okresy_dni', []);

        foreach ($wpisy as $tabela => $wpis) {
            $okres = $okresy[$tabela] ?? null;
            $wpisy[$tabela]['okres_dni'] = is_int($okres) && $okres > 0 ? $okres : null;
        }

        return $wpisy;
    }

    /**
     * Część rejestru trzymana w kodzie — bez okresu.
     *
     * @return array<string, array{kolumna_pochodzenia: string, kolumna_klucza: string, opis_dla_czlowieka: string, podstawa: string, sposob_usuniecia: string, kasuje: bool, okres_dni: int|null}>
     */
    private static function opis(): array
    {
        return [
            'pacjenci' => [
                'kolumna_pochodzenia' => 'created_at',
                'opis_dla_czlowieka' => 'Osoby korzystajace z pomocy: imie, nazwisko, e-mail, telefon oraz to, ze zglosily sie po pomoc psychologiczna. DANE PACJENTOW, najwrazliwsze w calym systemie.',
                'kolumna_klucza' => 'id',
                'podstawa' => 'RODO art. 9 — dane o zdrowiu; okres do ustalenia z IOD (P-3 w DPIA).',
                'sposob_usuniecia' => 'anonimizacja (zanonimizowany_at), nie DELETE — rezerwacje muszą zostać do rozliczeń.',
                'kasuje' => false,
                'okres_dni' => null,
            ],
            'uniewaznione_sesje' => [
                'kolumna_pochodzenia' => 'uniewazniona_at',
                'opis_dla_czlowieka' => 'Techniczne znaczniki wylogowania: skrot identyfikatora sesji i godzina. NIE MA tu zadnych danych o osobie — sluzy tylko temu, zeby wylogowanie w jednym miejscu dzialalo wszedzie.',
                'kolumna_klucza' => 'sid_skrot',
                'podstawa' => 'Znacznik unieważnienia sesji SSO — negatywna asercja bezpieczeństwa. Skrót sid, bez danych osobowych.',
                'sposob_usuniecia' => 'DELETE po `wygasa_at` — próg zapisany W WIERSZU, żeby sprzątaczka nie odblokowała wcześniej niż SSO Session Max.',
                'kasuje' => true,
                'okres_dni' => null,
            ],
            'zgody' => [
                'kolumna_pochodzenia' => 'created_at',
                'opis_dla_czlowieka' => 'Dowody, ze pacjent zgodzil sie na regulamin i przetwarzanie danych: kiedy, na ktora wersje dokumentu i z jakiego adresu. DANE PACJENTOW; to nasz dowod w razie sporu.',
                'kolumna_klucza' => 'id',
                'podstawa' => 'Dowód zgody przechowywany tak długo, jak długo może być potrzebny do obrony roszczeń.',
                'sposob_usuniecia' => 'DELETE po ustaniu podstawy — wpis dopisywany, nigdy modyfikowany.',
                'kasuje' => true,
                'okres_dni' => null,
            ],
            'rezerwacje' => [
                'kolumna_pochodzenia' => 'created_at',
                'opis_dla_czlowieka' => 'Umowione wizyty: kto, do kogo, kiedy, za ile i czy zaplacono. DANE PACJENTOW polaczone z dokumentem ksiegowym.',
                'kolumna_klucza' => 'id',
                'podstawa' => 'Dokument księgowy — 5 lat od końca roku podatkowego (ustawa o rachunkowości).',
                'sposob_usuniecia' => 'odpięcie od pacjenta (anonimizacja pacjenta), rekord finansowy zostaje.',
                'kasuje' => false,
                'okres_dni' => null,
            ],
            'zdarzenia_rezerwacji' => [
                'kolumna_pochodzenia' => 'created_at',
                'opis_dla_czlowieka' => 'Historia tego, co dzialo sie z wizyta: umowiona, przelozona, odwolana, oplacona. DANE PACJENTOW posrednio — pokazuja, kto i kiedy korzystal z pomocy.',
                'kolumna_klucza' => 'id',
                'podstawa' => 'Dziennik tylko-do-dopisywania; retencja równa retencji rezerwacji.',
                'sposob_usuniecia' => 'kasowanie całych partii po wygaśnięciu rezerwacji nadrzędnej.',
                'kasuje' => true,
                'okres_dni' => null,
            ],
            'users' => [
                'kolumna_pochodzenia' => 'created_at',
                'opis_dla_czlowieka' => 'Konta pracownikow i wspolpracownikow fundacji (koordynatorzy, administracja). DANE PERSONELU, nie pacjentow.',
                'kolumna_klucza' => 'id',
                'podstawa' => 'Konto personelu — usuwane po ustaniu współpracy (sygnał z Kont Niepodzielni).',
                'sposob_usuniecia' => 'DELETE po potwierdzeniu, że nie ma powiązanych decyzji uznaniowych.',
                'kasuje' => true,
                'okres_dni' => null,
            ],
            'specjalisci' => [
                'kolumna_pochodzenia' => 'created_at',
                'opis_dla_czlowieka' => 'Dane psychologow i terapeutow: imie, nazwisko, kontakt, opis do strony. DANE WSPOLPRACOWNIKOW, czesciowo publiczne.',
                'kolumna_klucza' => 'id',
                'podstawa' => 'Dane współpracownika — okres rozliczeniowy + roszczenia.',
                'sposob_usuniecia' => 'oznaczenie nieaktywności; usunięcie danych kontaktowych po okresie roszczeń.',
                'kasuje' => false,
                'okres_dni' => null,
            ],
        ];
    }

    /** Tabele bez danych osobowych — świadomie POZA rejestrem retencji. */
    public const BEZ_DANYCH_OSOBOWYCH = [
        'migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs',
        'konfiguracja_regul', 'uslugi', 'specjalista_usluga',
    ];

    /**
     * Wpisy, które zadanie czyszczące WOLNO dziś wykonać: kasowane i z USTALONYM
     * okresem. Reszta czeka na IOD i jest raportowana, nie pomijana po cichu.
     *
     * @return array<string, array{kolumna_pochodzenia: string, kolumna_klucza: string, opis_dla_czlowieka: string, podstawa: string, sposob_usuniecia: string, kasuje: bool, okres_dni: int|null}>
     */
    public static function doWykonania(): array
    {
        return array_filter(
            self::wpisy(),
            static fn (array $w): bool => $w['kasuje'] === true && $w['okres_dni'] !== null
        );
    }

    /**
     * Wpisy kasowane, ale o NIEUSTALONYM okresie — dług wobec IOD.
     *
     * Istnieje jako osobna lista, bo „pominięte po cichu" i „pominięte, bo nikt
     * nie ustalił okresu" wyglądają w logu identycznie, a znaczą co innego.
     *
     * @return list<string>
     */
    public static function czekajaceNaOkres(): array
    {
        return array_keys(array_filter(
            self::wpisy(),
            static fn (array $w): bool => $w['kasuje'] === true && $w['okres_dni'] === null
        ));
    }
}
