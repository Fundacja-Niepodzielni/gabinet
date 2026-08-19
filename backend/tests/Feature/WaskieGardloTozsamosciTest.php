<?php

declare(strict_types=1);

use App\Tozsamosc\SesjaKonta;
use App\Tozsamosc\TozsamoscSesji;
use Illuminate\Http\Request;
use Tests\Wsparcie\Kod;
use Tests\Wsparcie\Zrodlo;

/**
 * Wąskie gardło tożsamości (CLAUDE.md §2) — egzekwowane KONSTRUKCJĄ, nie regułą.
 *
 * Znalezisko R6A-3: weryfikator rundy 6 wytworzył tożsamość koordynatora BEZ
 * LOGOWANIA i uzyskał przez HTTP pełne uprawnienia (`panel.koordynacji`,
 * `rozliczenia.akceptuj`, `dziennik.zapisz`). Jednym z trzech wektorów była
 * `TozsamoscSesji::zMagazynu()` — PUBLICZNA fabryka przyjmująca DOWOLNĄ tablicę.
 * Moje wcześniejsze zdanie „ścieżka brak-rekordu-utwórz jest NIEWYWOŁYWALNA"
 * było za mocne: warunek przeniósł się o poziom wyżej, zamiast zniknąć.
 *
 * ⚠ TEN TEST JEST ALLOWLISTĄ, NIE DENYLISTĄ — i to jest jego treść, nie forma.
 *
 * Denylista („nie wolno wołać `zMagazynu`") przegrałaby z pierwszą nową fabryką,
 * której autor jej nie dopisał. W tym repozytorium ta klasa przegrała już
 * CZTERY RAZY, ostatnio przy `BrakWlasnychHaselTest` (R6A-4). Pytamy więc
 * odwrotnie: **czy wszystko, co tu jest, zostało DOPUSZCZONE?** Nowa publiczna
 * fabryka, nowy czytelnik klucza, nowy plik sięgający po tożsamość — każde
 * z nich zapala się na czerwono, dopóki ktoś świadomie nie dopisze go do listy.
 *
 * CZEGO TEN TEST NIE ZAMYKA — mówię wprost, bo cichy brak pokrycia jest gorszy
 * niż jawny: `Reflection::newInstanceWithoutConstructor()` i `unserialize()`
 * omijają KAŻDY konstruktor w PHP. Tego nie da się zamknąć typem, więc zamiast
 * udawać domknięcie, EGZEKWUJEMY WARUNEK UTRZYMUJĄCY: obu tych narzędzi nie ma
 * w kodzie produkcyjnym. Pierwsza osoba, która je wprowadzi, dowie się o tym
 * od bramki — a nie od weryfikatora pół roku później.
 */

/**
 * Pliki `.php` katalogu, ścieżkami względnymi od `base_path()`.
 *
 * @return list<string>
 */
function plikiPhp(string $katalog): array
{
    $wynik = [];
    $korzen = (string) realpath(base_path());

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($katalog, FilesystemIterator::SKIP_DOTS)
    );

    /** @var SplFileInfo $plik */
    foreach ($iterator as $plik) {
        if ($plik->getExtension() !== 'php') {
            continue;
        }

        $sciezka = (string) realpath($plik->getPathname());
        $wynik[] = str_replace('\\', '/', (string) substr($sciezka, strlen($korzen) + 1));
    }

    sort($wynik);

    return $wynik;
}

/**
 * Pliki katalogu, w których wzorzec występuje W KODZIE — nie w komentarzu.
 *
 * R6A-6: pierwsza wersja tego skanera czytała SUROWĄ treść pliku i zapaliła się
 * na własnym docbloku `TozsamoscSesji`, który wymienia `unserialize` po to,
 * żeby wyjaśnić, dlaczego go tam nie ma. Kontrola oskarżała kod o to, co mówił
 * o nim komentarz.
 *
 * @return list<string>
 */
function plikiZeWzorcem(string $katalog, string $wzorzec): array
{
    $wynik = [];
    $korzen = (string) realpath(base_path());

    foreach (plikiPhp($katalog) as $wzgledna) {
        $kod = Zrodlo::kod($korzen.'/'.$wzgledna);

        if (preg_match($wzorzec, $kod) === 1) {
            $wynik[] = $wzgledna;
        }
    }

    return $wynik;
}

it('jedyne PUBLICZNE wejście do tożsamości przyjmuje ŻĄDANIE, nigdy tablicy', function (): void {
    // Sedno R6A-3. Tablicę można wymyślić — sesji nie można: żeby coś w niej
    // było, ktoś musiał to tam zapisać drogą logowania. Dlatego pytamy o TYP
    // parametru, a nie o nazwę metody.
    $klasa = new ReflectionClass(TozsamoscSesji::class);

    $dopuszczoneTypy = [Request::class];
    $fabryki = [];

    foreach ($klasa->getMethods(ReflectionMethod::IS_PUBLIC) as $metoda) {
        if (! $metoda->isStatic()) {
            continue;
        }

        $zwrot = (string) $metoda->getReturnType();

        // Interesują nas WYŁĄCZNIE fabryki — metody oddające tożsamość.
        if (! str_contains($zwrot, 'self') && ! str_contains($zwrot, TozsamoscSesji::class)) {
            continue;
        }

        $fabryki[] = $metoda->getName();

        foreach ($metoda->getParameters() as $parametr) {
            $typ = (string) $parametr->getType();

            expect($typ)->toBeIn(
                $dopuszczoneTypy,
                sprintf(
                    'Publiczna fabryka `%s(%s $%s)` przyjmuje typ SPOZA allowlisty. Tak właśnie '.
                    'wyglądało R6A-3: `zMagazynu(array)` pozwalała wytworzyć tożsamość koordynatora '.
                    'bez logowania. Jeśli ten typ ma być dopuszczony, dopisz go do `$dopuszczoneTypy` '.
                    'ŚWIADOMIE — koszt wyjątku ma być równy kosztowi zgodności.',
                    $metoda->getName(),
                    $typ,
                    $parametr->getName()
                )
            );
        }
    }

    // Pustka to błąd, nie zero: bez tego kontrola przechodzi, gdy Reflection
    // nic nie znalazł, i „zero fabryk" wygląda jak „zero naruszeń".
    expect($fabryki)->not->toBe([], 'Nie znalazłem ŻADNEJ publicznej fabryki — skaner mierzy pustkę.');
});

it('KONTROLA POZYTYWNA: fabryka z surowej tablicy JEST prywatna', function (): void {
    // Kontrola pozytywna do poprzedniej: tamta pilnuje typów tych fabryk, które
    // są publiczne. Ta pilnuje, że konkretna fabryka, którą przeszedł weryfikator,
    // publiczna NIE JEST — inaczej „zero fabryk z tablicą" dałoby się osiągnąć
    // także przez skasowanie metody, co jest innym światem.
    $metoda = new ReflectionMethod(TozsamoscSesji::class, 'zMagazynu');

    expect($metoda->isPrivate())->toBeTrue(
        '`TozsamoscSesji::zMagazynu()` znów jest dostępna z zewnątrz. To jest DOKŁADNIE '.
        'wektor R6A-3: fabryka tożsamości z dowolnej tablicy.'
    );

    // ŻĄDANIE Z PRAWDZIWYM MAGAZYNEM SESJI, tylko pustym. `Request::create()`
    // bez podpiętego magazynu rzuca `RuntimeException` — czyli mierzyłoby brak
    // przyrządu, nie zachowanie fabryki. To jest ta sama pułapka co „kontrola
    // dowodzi własności, której jej środowisko nie ma".
    $zadanie = Request::create('/');
    $zadanie->setLaravelSession(app('session.store'));

    expect(TozsamoscSesji::zZadania($zadanie))->toBeNull(
        'PUSTA sesja dała tożsamość. Wejście przez `Request` przestało cokolwiek znaczyć — '.
        'fabryka oddaje coś, czego w magazynie nie ma.'
    );

    // KIERUNEK ODWROTNY. Bez niego `toBeNull()` przechodzi także wtedy, gdy
    // fabryka zwraca `null` ZAWSZE — a to inny świat niż „odczytuje magazyn".
    $zadanie->session()->put(TozsamoscSesji::KLUCZ, ['sub' => 'ktos-zalogowany', 'sid' => 'sid-x']);

    expect(TozsamoscSesji::zZadania($zadanie)?->sub())->toBe(
        'ktos-zalogowany',
        'Fabryka nie oddaje tożsamości, która JEST w magazynie — czyli `toBeNull()` wyżej '.
        'nie dowodzi niczego o odczycie.'
    );
});

it('zbiór plików produkcyjnych sięgających po TOŻSAMOŚĆ to ALLOWLISTA', function (): void {
    // R6A-12: poza gardłem zostawała ścieżka KASOWANIA tożsamości i odczyt
    // klucza LITERAŁEM. Policzono wtedy pisarzy i uznano gardło za domknięte —
    // a czytelnicy i kasujący nie byli liczeni w ogóle.
    //
    // ⛔ TO JEST EGZEKUTOR DECYZJI D-2026-08-08-24, i dlatego ją tu nazywam.
    //
    // Decyzja mówi, że zbiór KOMPONENTÓW zapisujących klucze tożsamości ma
    // liczność 1 (wykładnia rozstrzygnięta przez architekta 09.08: komponenty,
    // nie instrukcje zapisu — pod wykładnią literalną ŻADEN komponent tworzący
    // i aktualizujący nie mógłby jej spełnić, czyli unieważniałaby decyzję,
    // którą ma egzekwować).
    //
    // Do 12.08 D-24 nie miała ŻADNEGO egzekutora: `ObietniceKomentarzyTest`
    // obejmował regexem tylko sześć rodzin znaczników i decyzje z rejestru
    // pomijał (R6A-7). Dwa pliki produkcyjne powoływały się na nią w komentarzu,
    // a nic tego nie sprawdzało — obietnica bez pokrycia.
    //
    // Ta asercja egzekwuje ją WPROST: pisarzem, czytelnikiem i kasującym jest
    // JEDEN komponent (`SesjaKonta`), a `TozsamoscSesji` jest samym typem.
    // Trzeci plik sięgający po tożsamość zapala bramkę.
    // ⛔ R7-1: TA LISTA NIE WIDZIALA SIEGAJACYCH PRZEZ FASADE.
    //
    // Wzorzec znal `TozsamoscSesji::` i literal klucza, ale NIE `SesjaKonta::`
    // — czyli nie znal ZAMIERZONEJ, jedynej legalnej drogi. Weryfikator rundy 7
    // zmierzyl: trzy pliki produkcyjne siegaly po tozsamosc NIEWIDZIANE, a nowy,
    // czwarty plik z `SesjaKonta::odczytaj()` przeszedl niezauwazony (5 passed).
    // Kontrola pozytywna weryfikatora: ten sam plik przez `TozsamoscSesji::` → czerwien.
    //
    // Test reklamowal sie jako egzekutor D-2026-08-08-24 i NIE egzekwowal
    // twierdzenia, ktore nazywa. To R6A-12 nienaprawione dla sciezki fasady.
    //
    // Lista jest dzis DLUZSZA i to jest POPRAWNY stan, nie rozluznienie:
    // czytelnicy przez fasade sa legalni, ale maja byc POLICZENI. Kazdy nowy
    // plik siegajacy po tozsamosc — takze przez fasade — nadal zapala bramke.
    $dopuszczeni = [
        'app/Http/Controllers/LogowanieController.php',   // callback OIDC — jedyne ZAKLADANIE tozsamosci
        'app/Http/Middleware/SprawdzUniewaznienie.php',   // czyta, by sprawdzic uniewaznienie po `sid`
        'app/Tozsamosc/OdswiezanieSesji.php',             // czyta i AKTUALIZUJE przy odswiezeniu
        'app/Tozsamosc/SesjaKonta.php',                   // fasada — jedyny pisarz i kasujacy
        'app/Tozsamosc/TozsamoscSesji.php',               // sam typ (stala i fabryki)
    ];

    // Wzorzec obejmuje teraz TRZY drogi do tozsamosci: typ, FASADE i literal
    // klucza. Pominiecie fasady bylo cala trescia R7-1 — a fasada jest
    // ZAMIERZONA, jedyna legalna droga, wiec jej brak we wzorcu znaczyl, ze
    // kontrola nie widziala tego, co sama zaleca.
    // ZASIEG: `app/` I `routes/` — do 18.08 stalo tu samo `app/`.
    //
    // Wyszlo z pytania „krok dalej" przy R8-1 i zmierzylem to wprost: mutacja
    // `d1b` wpisuje `session()->put('konta', …)` do `backend/routes/web.php`,
    // a ta kontrola dawala `5 passed` — z ZYWYM zapisem tozsamosci w drzewie.
    //
    // Powod, dla ktorego to bolesne: `routes/` to DOKLADNIE to miejsce, w ktorym
    // mieszkal atak rundy 7. Docblock tego pliku obiecywal, ze „kazdy nowy plik
    // siegajacy po tozsamosc zapala bramke", a jeden z dwoch katalogow
    // wykonywalnych stal poza skanem. To ta sama klasa co samo R7-1:
    // kontrola o zasiegu wezszym, niz deklaruje.
    //
    // `routes/` nie potrzebuje wpisu w allowliscie: nazwy tras (`'konta.callback'`)
    // sa INNYMI literalami niz `'konta'`, wiec na czystym drzewie zbior zostaje pusty.
    $wzorzecTozsamosci = '/TozsamoscSesji::|SesjaKonta::|[\'"]konta[\'"]\s*(?:,|\)|=>|;)/';

    $siegajacy = array_merge(
        plikiZeWzorcem(base_path('app'), $wzorzecTozsamosci),
        plikiZeWzorcem(base_path('routes'), $wzorzecTozsamosci)
    );
    $siegajacy = array_values(array_unique($siegajacy));
    sort($siegajacy);

    expect($siegajacy)->toBe(
        $dopuszczeni,
        sprintf(
            "Zbiór plików produkcyjnych sięgających po tożsamość rozjechał się z allowlistą.\n".
            "DOPUSZCZENI : %s\nZNALEZIENI  : %s\n".
            'Jeśli nowy plik ma tam prawo być, dopisz go ŚWIADOMIE — razem z powodem. '.
            'Cicha rozbudowa tego zbioru jest tym samym, czym było R6A-12.',
            implode(', ', $dopuszczeni),
            implode(', ', $siegajacy)
        )
    );
});

it('KONTROLA NEGATYWNA: allowlista nie gnije — każdy jej wpis wskazuje ISTNIEJĄCY plik', function (): void {
    // Bez tego lista przeżyje pliki, których dotyczy: skasowany plik przestaje
    // być „znaleziony", więc porównanie zbiorów zapala się z niewłaściwej
    // przyczyny, a wtedy pierwszym odruchem jest wykreślenie wpisu.
    foreach ([
        'app/Http/Controllers/LogowanieController.php',
        'app/Http/Middleware/SprawdzUniewaznienie.php',
        'app/Tozsamosc/OdswiezanieSesji.php',
        'app/Tozsamosc/SesjaKonta.php',
        'app/Tozsamosc/TozsamoscSesji.php',
    ] as $wpis) {
        expect(file_exists(base_path($wpis)))->toBeTrue("Allowlista wskazuje nieistniejący plik: {$wpis}");
    }

    expect(SesjaKonta::KLUCZ)->toBe(
        TozsamoscSesji::KLUCZ,
        'Klucz sesji ma DWIE definicje. Dwa opisy jednej rzeczy rozjeżdżają się po cichu.'
    );
});

it('WARUNEK UTRZYMUJĄCY: kod produkcyjny nie używa Reflection ani unserialize', function (): void {
    // Dwa pozostałe wektory weryfikatora. Typem ich nie zamkniemy — PHP na to
    // nie pozwala. Zamiast udawać domknięcie, egzekwujemy warunek, dzięki
    // któremu ich dziś NIE MA. Pierwsza osoba, która je wprowadzi, dowie się
    // o tym od bramki, a nie od weryfikatora za pół roku.
    //
    // To jest jawny zapis granicy naprawy, a nie jej ukrycie: gdyby ktoś
    // potrzebował `unserialize`, ma dopisać wyjątek z powodem — i wtedy
    // R6A-3 wraca do rejestru jako otwarte dla tej ścieżki.
    // ZASIEG: WSZYSTKIE katalogi wykonywalne, nie samo `app/` (R9-4).
    //
    // Naprawa zasiegu po rundzie 8 objela JEDNA asercje w tym pliku —
    // te wyzej. Ta zostala przy `app/`, i runda 9 zmierzyla skutek:
    // `unserialize` oraz `Reflection` zywe w `routes/web.php` dawaly
    // `5 passed`, a kontrola pozytywna w `app/` — czerwien.
    //
    // To nie byl blad tamtej naprawy, tylko skutek tego, ze KAZDY skaner
    // trzymal wlasna liste katalogow. Odtad lista jest JEDNA
    // (`Kod::katalogiWykonywalne()`), wiec dopisanie katalogu zmienia
    // zasieg wszystkich kontroli naraz, a pominiecie jednej nie jest
    // juz mozliwe przez zapomnienie.
    // ⛔ SKANER LEKSEROWY, NIE REGEX NAD TEKSTEM — naprawa R12-1 (runda 12).
    //
    // Do 19.08 stał tu regex trzech pisowni. Weryfikator ominął go
    // deserializacją o nazwie SKLEJONEJ (`$f='unse'.'rialize'; $f($ładunek)`)
    // i odtworzył tożsamość z pominięciem konstruktora — cała bramka milczała.
    // To ta sama klasa co R6A-4: denylista nad tekstem przegrywa z wariantem
    // spoza listy (backslash, sklejenie, zmienna, klasa refleksji spoza trójki).
    //
    // Teraz pytamy o NAZWĘ narzędzia (przez lekser), nie o jej pisownię, a zbiór
    // klas refleksji bierzemy z RUNTIME — `Kod::wywolaniaOmijajaceKonstruktor()`.
    //
    // ALLOWLISTA WYJĄTKÓW jest PUSTA i to jest zmierzone: kod produkcyjny nie
    // używa dziś żadnego z tych narzędzi (json_decode nie tworzy obiektów PHP,
    // var_export to tekst). Gdyby któreś było potrzebne, wpis idzie TUTAJ:
    // klucz `'ścieżka:nazwa'`, wartość to POWÓD — koszt wyjątku równy kosztowi
    // zgodności, jak przy allowliście funkcji kryptograficznych.
    //
    // Typ zadeklarowany jawnie (mapa wyjątków), nie `array{}`: inaczej statyka
    // słusznie mówi „isset na pustej tablicy zawsze fałszem" i blokuje mechanizm,
    // który ma ożyć dopiero, gdy pierwszy wyjątek wejdzie.
    /** @var array<string, string> $dozwolone */
    $dozwolone = [];

    $ryzykowne = [];

    foreach (Kod::katalogiWykonywalne() as $katalogWykonywalny) {
        foreach (plikiPhp($katalogWykonywalny) as $wzgledny) {
            $tokeny = token_get_all((string) file_get_contents(base_path($wzgledny)));

            foreach (Kod::wywolaniaOmijajaceKonstruktor($tokeny) as $trafienie) {
                $wpis = $wzgledny.':'.$trafienie['nazwa'];

                if (! isset($dozwolone[$wpis])) {
                    $ryzykowne[] = sprintf('%s (wiersz %d: %s)', $wzgledny, $trafienie['linia'], $trafienie['nazwa']);
                }
            }
        }
    }

    $ryzykowne = array_values(array_unique($ryzykowne));
    sort($ryzykowne);

    expect($ryzykowne)->toBe(
        [],
        sprintf(
            'W kodzie produkcyjnym pojawiło się narzędzie omijające konstruktory:%s  %s%s'.
            'Wąskie gardło tożsamości stoi na tym, że ich TAM NIE MA — to warunek '.
            'utrzymujący R6A-3, nie zalecenie stylu. Jeśli użycie jest legalne, dopisz '.
            'je do `$dozwolone` z powodem.',
            PHP_EOL,
            implode(PHP_EOL.'  ', $ryzykowne),
            PHP_EOL
        )
    );
});

it('KIERUNEK ODWROTNY: skaner widzi KAŻDĄ pisownię narzędzia omijającego konstruktor — na PLIKU pod rękę', function (): void {
    // ⛔ Sedno naprawy R12-1. Materiał musi być PLIKIEM parsowanym lekserem,
    // nie napisem w tym teście — forma wadliwa jako literał byłaby dla skanera
    // (słusznie) zwykłym tekstem. Każda para: wariant → nazwa, którą skaner
    // MA z niego wydobyć niezależnie od zapisu.
    $przypadki = [
        // §3 zlecenia — cztery warianty, które omijały starą denylistę:
        'sklejenie_funkcji' => ['<?php $f = \'unse\'.\'rialize\'; $f($x);', 'unserialize'],
        'zmienna_z_literalu' => ['<?php $f = \'unserialize\'; $f($x);', 'unserialize'],
        'backslash_klasy' => ['<?php $r = new \ReflectionClass($x);', 'ReflectionClass'],
        'metoda_sklejona' => ['<?php $m = \'newInstance\'.\'WithoutConstructor\'; $r->$m();', 'newInstanceWithoutConstructor'],
        // klasa refleksji spoza starej trójki — łapana, bo zbiór z runtime:
        'reflection_property' => ['<?php $p = new ReflectionProperty($o, \'dane\'); $p->setValue($o, $z);', 'ReflectionProperty'],
        // kanoniczne pisownie — muszą dalej być łapane:
        'unserialize_wprost' => ['<?php unserialize($plod);', 'unserialize'],
        'reflection_wprost' => ['<?php new ReflectionClass($x);', 'ReflectionClass'],
        'eval_wprost' => ['<?php eval($kod);', 'eval'],
    ];

    foreach ($przypadki as $etykieta => [$kod, $oczekiwanaNazwa]) {
        $trafienia = Kod::wywolaniaOmijajaceKonstruktor(token_get_all($kod));
        $nazwy = array_column($trafienia, 'nazwa');

        // Jawny predykat, nie `toContain($igła, $komunikat)` — ten matcher jest
        // WARIADYCZNY i drugi argument potraktowałby jak kolejną igłę, połykając
        // komunikat (klasa z rundy 7). Złapał mnie tu własny strażnik.
        expect(in_array($oczekiwanaNazwa, $nazwy, true))->toBeTrue(
            "Skaner NIE widzi wariantu `$etykieta` (oczekiwano nazwy `$oczekiwanaNazwa`). ".
            'To dokładnie ta pisownia, którą omijano starą denylistę — jeśli skaner jej nie '.
            'wydobywa, R12-1 nie jest naprawione.');
    }

    // KONTROLA PRZYRZĄDU (POZYTYWNA): kod BEZ tych narzędzi NIE zapala.
    // Bez tego skaner „widzący wszystko" spełniałby powyższe, będąc bezużyteczny.
    // `$next($request)` to legalne dynamiczne wywołanie middleware — NIE może zapalać.
    $niewinny = <<<'PHP'
    <?php
    $dane = json_decode($wejscie, true);
    $wynik = var_export($dane, true);
    return $next($request);
    $tekst = 'komunikat: '.$powod.' — koniec';
    PHP;

    $trafienia = Kod::wywolaniaOmijajaceKonstruktor(token_get_all($niewinny));

    expect($trafienia)->toBe([],
        'Skaner oskarża NIEWINNY kod (json_decode/var_export/$next/sklejenie komunikatu). '.
        'Zamieniłby jedną nadgorliwą kontrolę na drugą — a `$next($request)` w middleware '.
        'jest legalny. Znalezione: '.json_encode(array_column($trafienia, 'nazwa')));
});

it('KIERUNEK ODWROTNY: WEKTOR RUNDY 12 W CAŁOŚCI zapala kontrolę — na PLIKU zbudowanym pod rękę', function (): void {
    // Nie fragment, lecz cały mechanizm R12-1: plik produkcyjny (na allowliście
    // zapisu tożsamości), który deserializacją odtwarza `TozsamoscSesji`
    // i woła `zaktualizuj`. Zapisujemy go jako PRAWDZIWY plik w tymczasowym
    // „katalogu produkcyjnym" i skanujemy tak, jak kontrola skanuje `app/`.
    $mechanizm = <<<'PHP'
    <?php
    namespace App\Http\Controllers;

    class Wstrzyk
    {
        public function powrot($request): void
        {
            $wstrzyk = $request->query('code');
            $dane = ['sub' => $wstrzyk, 'role' => ['koordynator', 'admin-fundacja']];
            $nazwaKlasy = 'App\Tozsamosc\TozsamoscSesji';
            $plod = 'O:' . strlen($nazwaKlasy) . ':"' . $nazwaKlasy . '":1:{s:4:"dane";' . serialize($dane) . '}';
            $odtworz = 'unse' . 'rialize';
            $fake = $odtworz($plod);
            \App\Tozsamosc\SesjaKonta::zaktualizuj($request, $fake);
        }
    }
    PHP;

    $katalog = sys_get_temp_dir().'/gabinet-r12-'.getmypid();
    @mkdir($katalog, 0777, true);
    $sciezka = $katalog.'/Wstrzyk.php';
    file_put_contents($sciezka, $mechanizm);

    try {
        $trafienia = Kod::wywolaniaOmijajaceKonstruktor(token_get_all((string) file_get_contents($sciezka)));
    } finally {
        @unlink($sciezka);
        @rmdir($katalog);
    }

    expect(in_array('unserialize', array_column($trafienia, 'nazwa'), true))->toBeTrue(
        'Skaner NIE złapał wektora rundy 12 w całości. To jest DOKŁADNIE mechanizm, który '.
        'przeszedł całą bramkę w rundzie 12 — jeśli nowa kontrola go nie zapala, naprawa '.
        'jest pozorna.');
});
