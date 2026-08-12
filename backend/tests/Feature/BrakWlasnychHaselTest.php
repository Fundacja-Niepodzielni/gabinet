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
    // Sygnaly zdrowia (puls harmonogramu) — R6B-10. Klucz techniczny i liczba.
    'sygnaly_zdrowia' => ['klucz', 'wartosc', 'zapisany_at'],
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
 * Prymitywy, którymi w PHP da się utworzyć albo sprawdzić sekret
 * uwierzytelniający. Lista jest ZAMKNIĘTA — nie da się zweryfikować hasła
 * bez jednego z nich (albo bez własnej kryptografii, co samo w sobie byłoby
 * czerwoną flagą przy przeglądzie).
 */
const PRYMITYWY_POSWIADCZEN = '/('
    .'password_hash\s*\(|password_verify\s*\(|'
    .'crypt\s*\(|'
    .'sodium_crypto_pwhash|'
    .'Hash::|'
    .'\bbcrypt\s*\(|'
    .'Auth::attempt|->attempt\s*\(|'
    .'PasswordBroker|CanResetPassword|'
    .'Authenticatable'
    .')/';

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

it('nie używa ANI JEDNEGO prymitywu tworzącego lub sprawdzającego hasło', function (): void {
    // Weryfikator schował `Hash::make`/`Hash::check` w `routes/web.php`,
    // a `sodium_crypto_pwhash_str()` w `app/Wejscie/` — poprzedni skan
    // obejmował wyłącznie `app/` i wyłącznie cztery funkcje.
    $podejrzane = [];

    foreach (plikiPhpProjektu() as $plik) {
        $tresc = bezKomentarzy((string) file_get_contents($plik));

        if (preg_match(PRYMITYWY_POSWIADCZEN, $tresc) === 1) {
            $podejrzane[] = str_replace(base_path().'/', '', $plik);
        }
    }

    expect($podejrzane)->toBe([], 'Pliki z prymitywami poświadczeń: '.implode(', ', $podejrzane));
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
