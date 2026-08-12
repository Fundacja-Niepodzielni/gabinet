<?php

declare(strict_types=1);

use App\Models\User;
use App\Wsparcie\Typy;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Regresja na CLAUDE.md §2: „ŻADNYCH własnych haseł w tym systemie".
 *
 * WERSJA TRZECIA — i zmiana podejścia, nie tylko wzorców.
 *
 * Wersja pierwsza sprawdzała LITERALNE nazwy. Obalona: wystarczyło nazwać
 * kolumny po polsku (`haslo_hash`, `zetony_resetu`).
 *
 * Wersja druga sprawdzała WZORCE nazw (`hasl|password|reset`). Też obalona:
 * weryfikator użył `sekret_logowania`, `odcisk_urzadzenia`,
 * `poswiadczenia_wejsciowe`, `pin_dostepu`, `sodium_crypto_pwhash_str()`
 * i tras `/wejscie` — nic z tego nie pasuje do wzorca, a mechanizm DZIAŁAŁ
 * (`GET /wejscie/sprawdz?sekret=tajne123` → `{"zalogowany":true}`).
 *
 * Wniosek po dwóch obaleniach: **lista zakazanych nazw nigdy nie będzie pełna**,
 * bo przeciwnik wybiera nazwy. Odwracamy więc ciężar dowodu:
 *
 *   1. **Schemat bazy jest ZADEKLAROWANY.** Zbiór tabel i kolumn musi być
 *      DOKŁADNIE taki, jak w `OCZEKIWANY_SCHEMAT`. Nowa kolumna — obojętne
 *      jak nazwana — zapala test, dopóki człowiek świadomie nie dopisze jej
 *      do listy. Wtedy pytanie „po co nam kolumna `sekret_logowania`" pada
 *      przy przeglądzie, a nie nigdy.
 *   2. **Trasy są ZADEKLAROWANE.** Ta sama zasada.
 *   3. **Prymitywy kryptograficzne** skanujemy w CAŁYM `backend/` (bez
 *      `vendor/`), nie tylko w `app/` — weryfikator schował `Hash::check`
 *      w `routes/web.php`, którego poprzedni skan w ogóle nie widział.
 *      Lista prymitywów jest zamknięta i krótka, bo w PHP nie da się
 *      sprawdzić hasła inaczej niż jednym z nich.
 */

/**
 * Pełny, zadeklarowany schemat domenowy.
 *
 * Kolumny posortowane alfabetycznie — porównanie jest zbiorami, nie kolejnością.
 */
const OCZEKIWANY_SCHEMAT = [
    'users' => [
        'created_at', 'email', 'email_potwierdzony', 'id', 'keycloak_sub',
        'nazwa_wyswietlana', 'ostatnie_logowanie_at', 'updated_at',
    ],
    'konfiguracja_regul' => [
        'autor', 'created_at', 'id', 'obowiazuje_od', 'reguly', 'uzasadnienie', 'wersja',
    ],
    'uslugi' => [
        'cena_gr', 'created_at', 'id', 'kod', 'konto_stripe', 'minuty', 'model_ceny',
        'nazwa', 'prowizja_bp', 'updated_at', 'widelki_gr', 'widoczna_publicznie',
        'wymaga_uprawnienia',
    ],
    'specjalisci' => [
        'created_at', 'id', 'imie', 'keycloak_sub', 'nazwisko', 'przyjmuje_pacjentow',
        'stawka_pelna_gr', 'updated_at',
    ],
    'specjalista_usluga' => [
        'created_at', 'id', 'specjalista_id', 'updated_at', 'usluga_id', 'wlaczona',
    ],
    'pacjenci' => [
        'created_at', 'email', 'email_skrot', 'id', 'imie', 'keycloak_sub',
        'limit_niskoplatnych_indywidualny', 'nazwisko', 'prowadzacy_specjalista_id',
        'strefa_czasowa', 'telefon', 'updated_at', 'zanonimizowany_at',
    ],
    // Znaczniki unieważnionych sesji SSO (BLK-22). Trzyma WYŁĄCZNIE skrót
    // `sid` — żadnych poświadczeń, żadnych danych osobowych.
    'uniewaznione_sesje' => ['powod', 'sid_skrot', 'uniewazniona_at', 'wygasa_at'],
    // Mapa `sid -> sesje lokalne` wyprowadzona z cache'u do bazy (R6B-9).
    // ZERO kolumn nadajacych sie na poswiadczenie: skrot `sid`, identyfikator
    // sesji frameworka i dwie daty.
    'sesje_sso' => ['id', 'id_sesji', 'sid_skrot', 'wygasa_at', 'zapamietana_at'],
    'zgody' => [
        'created_at', 'id', 'ip', 'pacjent_id', 'rodzaj', 'rozstrzygnieta_at',
        'udzielona', 'wersja_dokumentu',
    ],
    'rezerwacje' => [
        'created_at', 'czas_trwania_minut', 'forma', 'id', 'konto_stripe',
        'kwota_zamrozona_gr', 'liczba_przelozen', 'link_spotkania', 'numer',
        'pacjent_id', 'prowizja_bp_zamrozona', 'regula_anulacji_zamrozona',
        'specjalista_id', 'status', 'stripe_payment_intent', 'termin', 'updated_at',
        'usluga_id', 'wersja_regul', 'wersja_regulaminu',
    ],
    'zdarzenia_rezerwacji' => [
        'aktor_identyfikator', 'aktor_rodzaj', 'created_at', 'id', 'rezerwacja_id',
        'szczegoly', 'typ',
    ],
];

/** Tabele frameworka — nie projektujemy ich schematu. */
const TABELE_TECHNICZNE = [
    'migrations', 'sessions', 'cache', 'cache_locks',
    'jobs', 'job_batches', 'failed_jobs',
];

/**
 * Zadeklarowane trasy aplikacji.
 *
 * `/auth/login` NIE jest własnym logowaniem — to przekierowanie do IdP,
 * bez pola na hasło (CLAUDE.md §2).
 */
const OCZEKIWANE_TRASY = [
    'GET /',
    'GET /api/wersja',
    'GET /auth/callback',
    'GET /auth/ja',
    'GET /auth/login',
    'GET /auth/wyloguj',
    'POST /oidc/backchannel-logout',
];

/*
 * `/up` NIE jest na liście powyżej, bo jego domknięcie żyje w `vendor/` —
 * trasę włącza `withRouting(health: '/up')` w `bootstrap/app.php`, a lista
 * dotyczy tras zdefiniowanych w NASZYCH plikach. Żeby nie stracić pokrycia,
 * jej istnienie sprawdza osobna asercja niżej.
 */

/**
 * ALLOWLISTA PRYMITYWOW KRYPTOGRAFICZNYCH — zbior DOPUSZCZONY, nie zakazany.
 *
 * Znalezisko R6A-4, waga KRYTYCZNA (CLAUDE.md §2). Poprzednia wersja tej
 * kontroli WYLICZALA ZAKAZANE prymitywy — `password_hash`, `crypt`,
 * `sodium_crypto_pwhash`, `Hash::`, `bcrypt`, `Auth::attempt`. Weryfikator
 * rundy 6 zbudowal KOMPLETNY mechanizm wlasnych hasel na `hash(sha256, ...)`,
 * czyli prymitywie SPOZA listy, i cala kontrola przeszla: 7 passed.
 *
 * Nad tamta lista stalo zdanie: „Lista jest ZAMKNIETA — nie da sie zweryfikowac
 * hasla bez jednego z nich". Zdanie bylo NIEPRAWDZIWE, obalone jedna linijka,
 * i samo przewidywalo dziure („albo bez wlasnej kryptografii"), po czym oddawalo
 * ja czlowiekowi („czerwona flaga przy przegladzie"). Weryfikator krzyzowy Kont
 * nazwal to cizszym niz sama luka: kontrola zawierala PISEMNE ZAPEWNIENIE,
 * ZE DZIURY NIE MA — czyli uczyla czytelnika przestac szukac.
 *
 * DLACZEGO TA WERSJA NIE PRZEGRA TAK SAMO
 *
 * Zbior ZAKAZANY nie jest tu wypisany recznie. Pochodzi z RUNTIMEU PHP:
 * `get_defined_functions()` daje wszystkie funkcje, jakie ta instalacja zna,
 * a my bierzemy z nich rodziny kryptograficzne po PREFIKSIE. Nowa funkcja
 * w rozszerzeniu `hash`, `openssl` czy `sodium` wchodzi w siec SAMA, bez
 * niczyjej pamieci — bo to PHP ja zglasza, nie ja ja przewiduje.
 *
 * Reczna jest wylacznie ALLOWLISTA, czyli zbior funkcji, ktorym w tym
 * repozytorium wolno wystapic w kodzie produkcyjnym. Dopisanie do niej wymaga
 * swiadomej decyzji i zostawia slad w przegladzie — koszt wyjatku rowny
 * kosztowi zgodnosci.
 *
 * CZEGO TA KONTROLA NIE LAPIE (mowie wprost)
 *
 * Mechanizmu hasel da sie teoretycznie napisac BEZ funkcji kryptograficznej —
 * porownaniem `===` zapisanego sekretu. Tego ta kontrola nie widzi. Lapie to
 * DRUGA, niezalezna siec: `OCZEKIWANY_SCHEMAT` jest allowlista tabel i kolumn,
 * wiec kolumna na sekret nie ma gdzie powstac bez zapalenia bramki. Dwa
 * niezalezne mechanizmy, nie jeden z dwoma nazwami.
 */
/**
 * Funkcje kryptograficzne DOPUSZCZONE w kodzie produkcyjnym tego repozytorium.
 *
 * Dzis: `hash`, i wylacznie do skrotow NIEODWRACALNYCH pelniacych role klucza
 * technicznego (`sid_skrot`, `email_skrot`) — nie do weryfikacji sekretu.
 * `hash_equals` dopuszczone jako porownanie odporne na atak czasowy: jego BRAK
 * jest defektem, a obecnosc sama w sobie nie tworzy poswiadczenia.
 *
 * Kazda inna funkcja z rodzin kryptograficznych PHP zapala bramke.
 */
/**
 * MAPA: funkcja kryptograficzna -> pliki, w ktorych wolno jej wystapic.
 *
 * ZAKRES PLIKOW JEST TU ISTOTA, NIE OZDOBNIKIEM. Dopuszczenie `hash`
 * globalnie odtwarzaloby DOKLADNIE atak weryfikatora rundy 6: mechanizm
 * wlasnych hasel na `hash(sha256, $haslo)`. Allowlista bez zakresu jest
 * denylista w przebraniu — dopuszcza nie funkcje, tylko jej dowolne uzycie.
 *
 * Kazdy wpis odpowiada na dwa pytania: CO wolno i GDZIE. Nowe uzycie
 * dopuszczonej funkcji w NOWYM pliku zapala bramke tak samo, jak funkcja
 * spoza listy — bo to wlasnie nowy plik bylby nosnikiem nowego mechanizmu.
 *
 * @var array<string, list<string>>
 */
const DOPUSZCZONE_PRYMITYWY = [
    // Skrot NIEODWRACALNY w roli klucza technicznego: `sid_skrot` w rejestrze
    // sesji i mapie SSO, `email_skrot` w modelu pacjenta, wyzwanie PKCE.
    // ZADNE z tych uzyc nie weryfikuje sekretu podanego przez czlowieka.
    'hash' => [
        'app/Tozsamosc/KontaOidc.php',
        'app/Tozsamosc/RejestrSesji.php',
        'tests/Feature/KluczRetencjiTest.php',
        'tests/Feature/ModelDanychTest.php',
        'tests/Feature/NowaTrasaJestChronionaTest.php',
        'tests/Feature/RetencjaWykonanieTest.php',
        'tests/Feature/TrwaloscMagazynowTest.php',
        'tests/Feature/WygasnieciePozwolenieTest.php',
    ],

    // Porownanie odporne na atak czasowy parametru `state` w OIDC. Jego BRAK
    // bylby defektem; sama obecnosc nie tworzy poswiadczenia.
    'hash_equals' => ['app/Http/Controllers/LogowanieController.php'],

    // Sprawdzenie podpisu tokenu WYSTAWIONEGO PRZEZ IdP. To jest przeciwienstwo
    // wlasnych hasel: dowod tozsamosci pochodzi z Kont Niepodzielni, a my go
    // wylacznie WERYFIKUJEMY kluczem publicznym z JWKS.
    'openssl_verify' => ['app/Tozsamosc/WalidatorTokenu.php'],

    // Atrapa IdP w suicie: wytwarza pare kluczy i podpisuje tokeny testowe.
    // Wylacznie kod testowy — w `app/` tych funkcji nie ma i nie ma ich prawa byc.
    'openssl_pkey_new' => ['tests/Wsparcie/FabrykaTokenow.php', 'tests/Feature/OdebranieRoliTest.php'],
    'openssl_pkey_get_details' => ['tests/Wsparcie/FabrykaTokenow.php'],
    'openssl_sign' => ['tests/Wsparcie/FabrykaTokenow.php', 'tests/Feature/OdebranieRoliTest.php'],
];

/**
 * Fasady i typy Laravela, przez ktore przechodzi UWIERZYTELNIANIE HASLEM.
 *
 * Osobno od funkcji, bo to nie sa funkcje — to statyczne wywolania i interfejsy,
 * ktorych `get_defined_functions()` nie zna. Tu lista jest wyliczeniem
 * ZAKAZANYCH i mowie to wprost, zamiast udawac allowliste: powierzchnia
 * frameworka nie jest odpytywalna z runtimeu tak, jak zbior funkcji.
 * Jej slabosc jest ODNOTOWANA, a nie zaklejona — i nie jest jedyna siecia,
 * bo allowlista schematu nie pozwala powstac kolumnie na sekret.
 */
const POWIERZCHNIA_UWIERZYTELNIANIA = '/('
    .'Hash::|'
    .'\bbcrypt\s*\(|'
    .'Auth::attempt|->attempt\s*\(|'
    .'PasswordBroker|CanResetPassword|'
    .'Authenticatable'
    .')/';

/**
 * Rodziny funkcji kryptograficznych ZNANE TEJ INSTALACJI PHP.
 *
 * Zbior pochodzi z `get_defined_functions()`, nie z mojej pamieci — dlatego
 * wariant „spoza listy" nie istnieje: gdy PHP doda funkcje do rozszerzenia
 * `hash`, `openssl` albo `sodium`, wejdzie ona w siec bez zmiany tego pliku.
 *
 * @return list<string>
 */
function funkcjeKryptograficzne(): array
{
    $prefiksy = ['hash', 'crypt', 'password_', 'openssl_', 'sodium_', 'mcrypt_'];
    $wynik = [];

    $wszystkie = get_defined_functions();

    foreach ($wszystkie['internal'] as $nazwa) {
        foreach ($prefiksy as $prefiks) {
            if (str_starts_with($nazwa, $prefiks)) {
                $wynik[] = $nazwa;

                break;
            }
        }
    }

    sort($wynik);

    return $wynik;
}

/**
 * @return array<string, list<string>> tabela => posortowane kolumny
 */
function schematDomenowy(): array
{
    $wiersze = DB::select(<<<'SQL'
        select table_name, column_name
        from information_schema.columns
        where table_schema = 'public'
        order by table_name, column_name
    SQL);

    $schemat = [];

    foreach ($wiersze as $wiersz) {
        $dane = Typy::mapa((array) $wiersz);
        $tabela = Typy::napis($dane['table_name'] ?? null);

        if (in_array($tabela, TABELE_TECHNICZNE, true)) {
            continue;
        }

        $schemat[$tabela][] = Typy::napis($dane['column_name'] ?? null);
    }

    foreach ($schemat as $tabela => $kolumny) {
        sort($kolumny);
        $schemat[$tabela] = $kolumny;
    }

    ksort($schemat);

    return $schemat;
}

/**
 * Wszystkie pliki PHP repozytorium poza `vendor/` — łącznie z `routes/`,
 * `bootstrap/`, `config/` i `database/`.
 *
 * @return list<string>
 */
function plikiPhpProjektu(): array
{
    $katalog = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(base_path(), FilesystemIterator::SKIP_DOTS)
    );

    $pliki = [];

    foreach ($katalog as $plik) {
        if (! $plik instanceof SplFileInfo || $plik->getExtension() !== 'php') {
            continue;
        }

        $sciezka = str_replace('\\', '/', $plik->getPathname());

        if (str_contains($sciezka, '/vendor/') || str_contains($sciezka, '/storage/')) {
            continue;
        }

        // Ten plik jest WYŁĄCZONY z własnego skanu: wzorce poświadczeń są
        // jego treścią, więc zawsze trafiłby sam w siebie. To jedyny wyjątek
        // i jest wąski — nie „katalog tests/", tylko ten jeden plik.
        if (str_ends_with($sciezka, 'tests/Feature/BrakWlasnychHaselTest.php')) {
            continue;
        }

        $pliki[] = $sciezka;
    }

    sort($pliki);

    return $pliki;
}

/**
 * Czy trasa jest zdefiniowana w NASZYM kodzie?
 *
 * Rozstrzyga plik definicji, nie adres. Trasy pakietów (`Horizon`, `Scramble`)
 * mają kontrolery w `vendor/` i nie podlegają deklaracji — ale trasa schowana
 * pod `/horizon/coś` z naszym kontrolerem albo domknięciem zostanie policzona.
 */
/**
 * Nazwa klasy z zapisu `Kontroler@metoda`.
 *
 * @return class-string
 */
function klasaZAkcji(string $akcja): string
{
    /** @var class-string */
    return explode('@', $akcja)[0];
}

function trasaZNaszegoKodu(Illuminate\Routing\Route $trasa): bool
{
    $akcja = $trasa->getAction('uses');

    try {
        $plik = match (true) {
            $akcja instanceof Closure => (new ReflectionFunction($akcja))->getFileName(),
            is_string($akcja) && str_contains($akcja, '@') => (new ReflectionClass(klasaZAkcji($akcja)))->getFileName(),
            is_string($akcja) && class_exists($akcja) => (new ReflectionClass($akcja))->getFileName(),
            default => null,
        };
    } catch (ReflectionException) {
        return false;
    }

    if (! is_string($plik)) {
        // Trasa bez rozpoznawalnego pliku (np. `Route::view`) traktowana jest
        // jako NASZA — bezpieczniejszy kierunek: pojawi się na liście różnic.
        return true;
    }

    $plik = str_replace(DIRECTORY_SEPARATOR, '/', $plik);

    return ! str_contains($plik, '/vendor/');
}

/** Kod bez komentarzy — kontrola patrzy na to, co się WYKONUJE. */
function bezKomentarzy(string $kod): string
{
    $wynik = '';

    foreach (token_get_all($kod) as $token) {
        if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        $wynik .= is_array($token) ? $token[1] : $token;
    }

    return $wynik;
}

// ---------------------------------------------------------------------------
// SCHEMAT ZADEKLAROWANY — odwrócony ciężar dowodu
// ---------------------------------------------------------------------------

it('ma schemat DOKŁADNIE taki, jak zadeklarowany — ani jednej kolumny więcej', function (): void {
    // To jest właściwa obrona przed CLAUDE.md §2. Kolumna `sekret_logowania`
    // przechodziła przez wzorce; przez zadeklarowany schemat nie przejdzie
    // ŻADNA nowa kolumna, dopóki człowiek jej tu nie dopisze.
    $rzeczywisty = schematDomenowy();
    $oczekiwany = OCZEKIWANY_SCHEMAT;

    ksort($oczekiwany);

    expect(array_keys($rzeczywisty))->toBe(
        array_keys($oczekiwany),
        'Zbiór tabel domenowych różni się od zadeklarowanego.'
    );

    foreach ($oczekiwany as $tabela => $kolumny) {
        sort($kolumny);

        expect($rzeczywisty[$tabela] ?? [])->toBe(
            $kolumny,
            "Kolumny tabeli {$tabela} różnią się od zadeklarowanych."
        );
    }
});

it('sprawdza schemat, który realnie istnieje', function (): void {
    // Asercja „miałem czego szukać": bez niej test wyżej przechodzi także
    // przy pustej bazie (obie listy byłyby puste).
    expect(count(schematDomenowy()))->toBe(count(OCZEKIWANY_SCHEMAT))
        ->and(count(schematDomenowy()))->toBeGreaterThan(5);
});

// ---------------------------------------------------------------------------
// TRASY ZADEKLAROWANE
// ---------------------------------------------------------------------------

it('wystawia DOKŁADNIE zadeklarowane trasy', function (): void {
    // Weryfikator dołożył `/wejscie/sprawdz` — nazwę, której żaden wzorzec
    // nie przewidzi. Lista zadeklarowana przewiduje wszystkie.
    //
    // Liczymy WYŁĄCZNIE trasy zdefiniowane w NASZYCH plikach. Rozstrzyga
    // plik definicji (klasa kontrolera albo domknięcie przez refleksję),
    // a nie przedrostek adresu — inaczej dałoby się schować trasę pod
    // `/horizon/...` i wyjść spod kontroli. Horizon i Scramble rejestrują
    // własne trasy z `vendor/` i te są tu nieistotne.
    $rzeczywiste = [];

    foreach (Route::getRoutes()->getRoutes() as $trasa) {
        if (! trasaZNaszegoKodu($trasa)) {
            continue;
        }

        foreach ($trasa->methods() as $metoda) {
            if (in_array($metoda, ['HEAD', 'OPTIONS'], true)) {
                continue;
            }

            if (! is_string($metoda)) {
                continue;
            }

            $rzeczywiste[] = $metoda.' /'.ltrim($trasa->uri(), '/');
        }
    }

    $rzeczywiste = array_values(array_unique($rzeczywiste));
    sort($rzeczywiste);

    $oczekiwane = OCZEKIWANE_TRASY;
    sort($oczekiwane);

    expect($rzeczywiste)->toBe($oczekiwane, 'Zbiór tras różni się od zadeklarowanego.');
});

// ---------------------------------------------------------------------------
// PRYMITYWY POŚWIADCZEŃ — w CAŁYM repozytorium, nie tylko w app/
// ---------------------------------------------------------------------------

it('KAZDA uzyta funkcja kryptograficzna jest na ALLOWLISCIE — nieznane = odmowa', function (): void {
    // R6A-4. Nie pytamy „czy jest tu cos zakazanego" (to przegrywa z wariantem
    // spoza listy — przegralo u nas CZTERY RAZY), tylko „czy wszystko, co tu
    // jest, zostalo DOPUSZCZONE".
    $znane = funkcjeKryptograficzne();
    $uzyte = [];

    foreach (plikiPhpProjektu() as $plik) {
        $tresc = bezKomentarzy((string) file_get_contents($plik));

        foreach ($znane as $funkcja) {
            // Granica z lewej odsiewa wywolania metod i dluzsze nazwy,
            // nawias z prawej — wystapienia w napisach i komentarzach.
            if (preg_match('/(?<![\\w$>-])'.preg_quote($funkcja, '/').'\\s*\\(/', $tresc) === 1) {
                $uzyte[$funkcja][] = str_replace(base_path().'/', '', $plik);
            }
        }
    }

    // NARUSZENIEM jest jedno i drugie: funkcja spoza listy ORAZ dopuszczona
    // funkcja w pliku, ktorego nikt dla niej nie dopuscil.
    $naruszenia = [];

    foreach ($uzyte as $funkcja => $pliki) {
        $dozwolone = DOPUSZCZONE_PRYMITYWY[$funkcja] ?? null;

        foreach ($pliki as $plik) {
            if ($dozwolone === null || ! in_array($plik, $dozwolone, true)) {
                $naruszenia[] = $funkcja.' w '.$plik;
            }
        }
    }

    sort($naruszenia);

    expect($naruszenia)->toBe(
        [],
        sprintf(
            'Prymityw kryptograficzny POZA dopuszczonym zakresem: %s. '.
            'To jest miejsce, w ktorym R6A-4 przeszlo poprzednia kontrole: weryfikator '.
            'zbudowal kompletny mechanizm wlasnych hasel na skrocie sha256, bo tamta '.
            'lista wyliczala ZAKAZANE. Jesli to uzycie ma prawo istniec, dopisz plik '.
            'do DOPUSZCZONE_PRYMITYWY SWIADOMIE i napisz PO CO — CLAUDE.md §2 mowi '.
            'ZADNYCH wlasnych hasel w tym systemie.',
            implode(', ', $naruszenia)
        )
    );

    // Pustka to blad, nie zero: gdyby skan nie znalazl ANI JEDNEJ funkcji
    // kryptograficznej, znaczyloby to, ze parser albo lista nie dzialaja —
    // a `hash()` w tym repozytorium NA PEWNO jest (skroty sid i e-maila).
    // `toContain()` w Pest traktuje kolejne argumenty jako KOLEJNE IGLY, nie jako
    // komunikat — pulapka opisana wyzej w tym samym pliku, w ktora i tak wpadlem.
    expect(in_array('hash', array_keys($uzyte), true))->toBeTrue(
        'Skaner nie znalazl nawet hash(), ktory w tym repozytorium NA PEWNO jest. '.
        'To znaczy, ze mierzy pustke, a jego zielone nic nie znaczy.'
    );
});

it('KONTROLA NEGATYWNA: allowlista widzi prymityw, ktorego weryfikator NIE pokazal', function (): void {
    // Perturbacja rozpinajaca KLASE, nie instancje: podkladamy prymityw spoza
    // KAZDEJ listy w tym pliku i spoza tego, co pokazal weryfikator
    // (`sodium_crypto_generichash`, `hash_hmac`) — czyli dokladnie wariant,
    // na ktorym przegrywala kazda poprzednia denylista.
    $sztuczny = '<?php sodium_crypto_generichash($x); hash_hmac($a, $b, $c);';
    $trafione = [];

    foreach (funkcjeKryptograficzne() as $funkcja) {
        if (preg_match('/(?<![\\w$>-])'.preg_quote($funkcja, '/').'\\s*\\(/', $sztuczny) === 1) {
            $trafione[] = $funkcja;
        }
    }

    expect($trafione)->toContain('sodium_crypto_generichash')
        ->and($trafione)->toContain('hash_hmac');

    $spozaListy = array_values(array_diff($trafione, array_keys(DOPUSZCZONE_PRYMITYWY)));

    expect($spozaListy)->not->toBe(
        [],
        'Podlozony prymityw spoza allowlisty NIE zapalil kontroli — czyli allowlista '.
        'przepuszcza to samo, co przepuszczala denylista.'
    );

    // DRUGI KIERUNEK: dopuszczenie ma ZAKRES, a nie jest zgoda globalna. Bez tego
    // `hash` bylby wolny wszedzie — czyli dokladnie ta droga, ktora weszedl
    // weryfikator. Sprawdzamy KSZTALT wpisu, nie konkretna nazwe pliku: nazwa
    // podana z palca jest zawsze nieobecna, wiec taka asercja nic by nie znaczyla.
    foreach (DOPUSZCZONE_PRYMITYWY as $funkcja => $zakres) {
        expect($zakres)->not->toBe(
            [],
            'Wpis allowlisty dla '.$funkcja.' ma PUSTY zakres plikow. Wpis bez zakresu jest '.
            'zgoda globalna na prymityw kryptograficzny — a to odtwarza R6A-4: mechanizm '.
            'wlasnych hasel wchodzi wtedy dowolnym plikiem.'
        );
    }
});

it('POWIERZCHNIA UWIERZYTELNIANIA frameworka: zadnego Hash::, bcrypt, Auth::attempt', function (): void {
    // Druga siec, o SLABSZEJ konstrukcji i mowie to wprost: fasad i interfejsow
    // Laravela nie da sie odpytac z runtimeu tak jak funkcji, wiec TA lista jest
    // wyliczeniem zakazanych. Jej slabosc jest odnotowana, a nie zaklejona.
    $podejrzane = [];

    foreach (plikiPhpProjektu() as $plik) {
        $tresc = bezKomentarzy((string) file_get_contents($plik));

        if (preg_match(POWIERZCHNIA_UWIERZYTELNIANIA, $tresc) === 1) {
            $podejrzane[] = str_replace(base_path().'/', '', $plik);
        }
    }

    expect($podejrzane)->toBe([], 'Pliki z powierzchnia uwierzytelniania: '.implode(', ', $podejrzane));
});

it('przeszukuje realny zbiór plików, w tym routes/ i bootstrap/', function (): void {
    // Asercja „miałem czego szukać" — plus dowód, że skan sięga POZA `app/`,
    // bo dokładnie tam weryfikator schował mechanizm.
    $pliki = plikiPhpProjektu();
    $sklejone = implode(' ', $pliki);

    expect(count($pliki))->toBeGreaterThan(30)
        ->and($sklejone)->toContain('/routes/')
        ->and($sklejone)->toContain('/bootstrap/')
        ->and($sklejone)->toContain('/database/');
});

// ---------------------------------------------------------------------------
// KONFIGURACJA
// ---------------------------------------------------------------------------

it('wystawia sondę zdrowia /up włączoną konfiguracją', function (): void {
    // Trasa pochodzi z frameworka (`withRouting(health:)`), więc nie ma jej
    // na liście zadeklarowanej — ale jej zniknięcie ma zapalić bramkę.
    $this->get('/up')->assertOk();
});

it('nie ma modelu zdolnego do uwierzytelniania hasłem', function (): void {
    $model = Typy::napis(config('auth.providers.users.model'), User::class);

    expect(is_subclass_of($model, Authenticatable::class))->toBeFalse()
        ->and(is_subclass_of(User::class, Authenticatable::class))->toBeFalse();
});
