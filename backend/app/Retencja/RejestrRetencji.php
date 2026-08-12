<?php

declare(strict_types=1);

namespace App\Retencja;

use InvalidArgumentException;

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
 * **NIEUSTALONY**, a nie „zero" i nie „bez ograniczeń".
 *
 * OSŁABIONE 09.08 po werdykcie helpdesku (ZŁA DIAGNOZA): pisałem tu, że zadanie
 * „ODMAWIA" dotknięcia takiej tabeli. W kodzie to jest FILTR, nie odmowa —
 * wpis po prostu nie trafia do zbioru wykonywanego i zostaje WYPISANY jako dług.
 * Odmowa to czynność, która przerywa; filtr niczego nie przerywa. Nazywam to
 * teraz tak, jak działa, bo sam jestem autorem zasady „nieznane → odmowa"
 * i różnica między nią wykonaną a udawaną jest cała.
 *
 * To jest świadome zastosowanie reguły „nieznane → odmowa": okresy retencji dla
 * danych o zdrowiu (RODO art. 9) rozstrzyga IOD w DPIA, a nie programista
 * przy pisaniu sprzątaczki. Wpisanie tu wartości domyślnej byłoby podjęciem
 * decyzji prawnej przez przeoczenie — i to takiej, która KASUJE dane.
 *
 * @dowod: HarmonogramRetencjiTest — „FAIL-CLOSED: tabela o NIEUSTALONYM okresie nie jest
 *         kasowana, i mówi się o tym głośno". Nazwa poprawiona 09.08: poprzednia wersja
 *         tego znacznika cytowała test „zadanie odmawia tabeli o nieustalonym okresie",
 *         którego NIE MA — moja własna klasa D3, w pliku napisanym tego samego dnia.
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

    /**
     * Tabele świadomie POZA rejestrem retencji — **z powodem i warunkiem znoszącym**.
     *
     * Do 09.08 była to **płaska lista dziesięciu nazw**. Dokładnie ten sam kształt `D6`,
     * co pusty `withMiddleware`: **zwolnienie przemyślane i zwolnienie z rozpędu wyglądały
     * identycznie**, bo jedno i drugie było samą nazwą w tablicy. Dopisanie tabeli tutaj
     * było **najtańszym sposobem usunięcia jej z rejestru retencji** — tam wpis kosztował
     * podstawę i opis, tutaj nie kosztował nic.
     *
     * **Pisanie powodów NIE było formalnością — pomiar obalił trzy zwolnienia.** Co znalazłem
     * przy 10 tabelach (kolumny odczytane z bazy 09.08):
     *
     * - `sessions` ma `ip_address` i `user_agent`. **Adres IP jest daną osobową.** Zwolnienie
     *   jest prawdziwe **wyłącznie dlatego, że sterownik sesji to `redis`** — czyli zależy od
     *   wartości konfiguracji, której **nic nie pilnowało**. Teraz pilnuje test.
     * - `jobs` i `failed_jobs` mają `payload`, a `failed_jobs` dodatkowo `exception`.
     *   **Zadanie niosące e-mail pacjenta zapisuje go tam jawnie i zostawia bezterminowo.**
     * - `konfiguracja_regul` ma kolumnę `autor` — **to identyfikuje pracownika**. Dane
     *   personelu to nadal dane osobowe, choć nie pacjenta.
     *
     * **Nie przenoszę ich samowolnie do rejestru retencji** — to zmiana modelu i decyzja
     * właściciela. Zwolnienia zostają, ale **z warunkiem znoszącym, który mówi prawdę**,
     * i są zgłoszone jako dług w `ODPOWIEDZ-042`.
     *
     * @return array<string, array{powod: string, warunek_znoszacy: string}>
     */
    public static function bezDanychOsobowych(): array
    {
        return [
            'sesje_sso' => [
                'powod' => 'Mapa `sid -> identyfikatory sesji lokalnych`. Niesie SKROT `sid` (SHA-256) i identyfikator sesji frameworka — oba techniczne, zadnej cechy czlowieka. Wiersze kasuje sprzatanie po tym samym progu, co znaczniki uniewaznienia.',
                'warunek_znoszacy' => 'Gdyby identyfikator sesji zaczal byc budowany z czegokolwiek osobowego, albo gdyby doszla kolumna opisujaca uzytkownika (adres IP, przegladarka) — czyli dokladnie to, co uczynilo `sessions` zwolnieniem WARUNKOWYM.',
            ],
            'migrations' => [
                'powod' => 'Dziennik zastosowanych migracji: nazwa pliku, numer partii. Opisuje KSZTAŁT bazy, nie jej zawartość.',
                'warunek_znoszacy' => 'Gdyby ktoś zaczął umieszczać w nazwach migracji cokolwiek poza opisem zmiany schematu.',
            ],
            'sessions' => [
                'powod' => 'ZWOLNIENIE WARUNKOWE. Tabela ma `ip_address` i `user_agent` — adres IP JEST daną osobową. Pusta wyłącznie dlatego, że sterownik sesji to `redis`.',
                'warunek_znoszacy' => 'Gdy `session.driver` przestanie być `redis` — wtedy tabela natychmiast zaczyna zbierać adresy IP. Pilnuje tego test, nie ten komentarz.',
            ],
            'cache' => [
                'powod' => 'Magazyn pamięci podręcznej. Klucz i wartość są tym, co włoży wywołujący — dziś nic z danych pacjenta tam nie trafia.',
                'warunek_znoszacy' => 'Gdy zaczniemy zapamiętywać cokolwiek wyliczonego z danych pacjenta — wtedy retencja pamięci podręcznej przestaje być kwestią wydajności.',
            ],
            'cache_locks' => [
                'powod' => 'Zamki pamięci podręcznej: klucz, właściciel zamka, czas wygaśnięcia. Właściciel to identyfikator procesu, nie człowieka.',
                'warunek_znoszacy' => 'Gdyby klucz zamka zaczął być budowany z identyfikatora pacjenta zamiast z identyfikatora zasobu.',
            ],
            'jobs' => [
                'powod' => 'ZWOLNIENIE WARUNKOWE. Kolejka ma kolumnę `payload` — zadanie niosące dane pacjenta zapisuje je tam jawnie. Dziś takich zadań nie ma, ale nic tego nie wymusza.',
                'warunek_znoszacy' => 'Gdy powstanie zadanie niosące w ładunku cokolwiek osobowego — a przy wysyłce SMS i e-maili to kwestia czasu, nie możliwości.',
            ],
            'job_batches' => [
                'powod' => 'Metryki partii zadań: nazwa, liczniki, czasy. Sam ładunek zadań leży w `jobs`, nie tutaj.',
                'warunek_znoszacy' => 'Gdyby nazwa partii zaczęła być budowana z danych pacjenta, np. z jego adresu e-mail.',
            ],
            'failed_jobs' => [
                'powod' => 'ZWOLNIENIE WARUNKOWE I NAJSŁABSZE Z CAŁEJ LISTY. Ma `payload` ORAZ `exception` — nieudane zadanie zapisuje jedno i drugie jawnie i zostawia BEZTERMINOWO.',
                'warunek_znoszacy' => 'Gdy jakiekolwiek zadanie zacznie nieść dane osobowe — wtedy ta tabela staje się archiwum danych pacjentów utworzonym przez awarie, którego nikt nie zaplanował.',
            ],
            'konfiguracja_regul' => [
                'powod' => 'ZWOLNIENIE WARUNKOWE. Wersjonowane reguły i limity — ale kolumna `autor` IDENTYFIKUJE PRACOWNIKA. Dane personelu to nadal dane osobowe.',
                'warunek_znoszacy' => 'Warunek już jest spełniony w części: `autor` to człowiek. Zwolnienie broni się tylko tym, że to dane personelu przy dokumencie, który musi być niezmienny.',
            ],
            'uslugi' => [
                'powod' => 'Katalog usług: kod, nazwa, czas, cena, konto Stripe, prowizja. Opisuje OFERTĘ, nie osobę.',
                'warunek_znoszacy' => 'Gdyby usługa zaczęła być definiowana pod konkretnego pacjenta zamiast pod rodzaj pomocy.',
            ],
            'specjalista_usluga' => [
                'powod' => 'Powiązanie: który specjalista prowadzi którą usługę. Niesie WYŁĄCZNIE dwa odnośniki i przełącznik — żadnej cechy człowieka.',
                'warunek_znoszacy' => 'Gdyby powiązanie zaczęło nieść cokolwiek własnego o specjaliście, np. jego stawkę albo notatkę.',
            ],
        ];
    }

    /**
     * Same nazwy tabel — dla miejsc, które pytają „czy ta tabela jest opisana".
     *
     * @return list<string>
     */
    public static function nazwyBezDanychOsobowych(): array
    {
        return array_keys(self::bezDanychOsobowych());
    }

    public const DO_WYKONANIA = 'do-wykonania';

    public const CZEKA_NA_OKRES = 'czeka-na-okres';

    public const POZA_KASOWANIEM = 'poza-kasowaniem';

    /**
     * KLASYFIKATOR TOTALNY — każdy wpis dostaje dokładnie jedną kategorię
     * albo wywala się GŁOŚNO.
     *
     * Powstał, bo poprzednia wersja miała dwie NIEZALEŻNE listy, obie filtrujące
     * po `kasuje === true`. Wpis z `kasuje=false` i okresem `null` wypadał
     * z OBU — i tak wypadły `pacjenci` (RODO art. 9), `rezerwacje`
     * i `specjalisci`. Raport wymieniał 4 z 7 tabel, a o trzech MILCZAŁ.
     * Cisza jest gorsza niż dług, bo dług przynajmniej ma listę.
     *
     * Konstrukcja przed strażnikiem: przy podziale na kategorie wypadnięcie
     * „pomiędzy" przestaje być możliwe, zamiast być pilnowane osobną kontrolą.
     *
     * Wpis niekompletny NIE dostaje kategorii domyślnej — „nieznane → odmowa".
     * Inaczej nowa tabela dopisana bez pola `kasuje` zostałaby po cichu uznana
     * za anonimizowaną i zniknęła z raportu tak samo jak `pacjenci`.
     *
     * @param  array<string, mixed>  $wpis
     *
     * @dowod: WidocznoscRejestruTest — „KIERUNEK 0: wpis BEZ pola `kasuje` ZAPALA”
     */
    public static function kategoria(array $wpis): string
    {
        foreach (['kasuje', 'okres_dni'] as $wymagane) {
            if (! array_key_exists($wymagane, $wpis)) {
                throw new InvalidArgumentException(
                    "Wpis rejestru retencji BEZ pola `{$wymagane}` — odmawiam zaklasyfikowania. ".
                    'Wpis bez kategorii wypadłby ze wszystkich raportów po cichu.'
                );
            }
        }

        if ($wpis['kasuje'] !== true) {
            return self::POZA_KASOWANIEM;
        }

        return $wpis['okres_dni'] === null ? self::CZEKA_NA_OKRES : self::DO_WYKONANIA;
    }

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
            static fn (array $w): bool => self::kategoria($w) === self::DO_WYKONANIA
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
            static fn (array $w): bool => self::kategoria($w) === self::CZEKA_NA_OKRES
        ));
    }

    /**
     * Wpisy, których sprzątaczka KASUJĄCA nie dotyka, bo rekord znika przez
     * ANONIMIZACJĘ — a mechanizmu anonimizacji **NIE MA W KODZIE**.
     *
     * To jest dług cięższy niż brak okresu i musi być widoczny osobno: przy
     * braku okresu istnieje mechanizm i czeka na liczbę, tutaj **nie istnieje
     * nic**. Leży tu `pacjenci` — „DANE PACJENTÓW, najwrażliwsze w całym
     * systemie", podstawa „RODO art. 9".
     *
     * @return list<string>
     */
    public static function pozaKasowaniem(): array
    {
        return array_keys(array_filter(
            self::wpisy(),
            static fn (array $w): bool => self::kategoria($w) === self::POZA_KASOWANIEM
        ));
    }
}
