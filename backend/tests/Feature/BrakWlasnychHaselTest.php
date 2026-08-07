<?php

declare(strict_types=1);

use App\Wsparcie\Typy;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Regresja na CLAUDE.md §2: „ŻADNYCH własnych haseł w tym systemie".
 *
 * WERSJA DRUGA. Pierwsza sprawdzała LITERALNE nazwy (`password`,
 * `remember_token`, `password_reset_tokens`, model `User`) i została obalona
 * przez niezależnego weryfikatora: podłożył kolumny `users.haslo_hash`
 * i `users.token_zapamietania`, tabele `konta_lokalne(hash_hasla)`
 * i `zetony_resetu`, model `Personel extends Authenticatable` oraz trasy
 * `/reset-hasla` i `/zaloguj-haslem` — i **cała bramka została zielona**.
 *
 * Wniosek: test na liście zakazanych nazw sprawdza słownik, nie regułę.
 * Ta wersja szuka WZORCÓW w całym schemacie, we wszystkich modelach
 * i we wszystkich trasach — po polsku i po angielsku.
 */

/** Wzorce nazw, które w systemie bez haseł nie mają prawa wystąpić. */
const WZORZEC_HASLA = '/(hasl|password|passwd|\bpwd\b|remember_token|token_zapamietania)/i';

/** Wzorce nazw tabel: hasła i wszystko, co je resetuje. */
const WZORZEC_TABELI = '/(hasl|password|passwd|reset)/i';

/**
 * @return list<string>
 */
function wszystkieTabele(): array
{
    $wiersze = DB::select(
        "select tablename from pg_tables where schemaname = 'public' order by tablename"
    );

    $nazwy = [];

    foreach ($wiersze as $wiersz) {
        $nazwy[] = Typy::napis(Typy::mapa((array) $wiersz)['tablename'] ?? null);
    }

    return $nazwy;
}

/**
 * @return list<string> pozycje w formacie `tabela.kolumna`
 */
function wszystkieKolumny(): array
{
    $wynik = [];

    foreach (wszystkieTabele() as $tabela) {
        foreach (Schema::getColumnListing($tabela) as $kolumna) {
            $wynik[] = $tabela.'.'.Typy::napis($kolumna);
        }
    }

    return $wynik;
}

/**
 * Kod PHP bez komentarzy — żeby kontrola patrzyła na to, co się wykonuje,
 * a nie na to, co ktoś o tym napisał.
 */
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

/**
 * @return list<string> ścieżki plików PHP w `app/`
 */
function plikiAplikacji(): array
{
    $katalog = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(base_path('app'), FilesystemIterator::SKIP_DOTS)
    );

    $pliki = [];

    foreach ($katalog as $plik) {
        if ($plik instanceof SplFileInfo && $plik->getExtension() === 'php') {
            $pliki[] = $plik->getPathname();
        }
    }

    sort($pliki);

    return $pliki;
}

// ---------------------------------------------------------------------------
// SCHEMAT BAZY — wzorce, nie lista zakazanych nazw
// ---------------------------------------------------------------------------

it('nie ma w CAŁYM schemacie ani jednej kolumny wyglądającej na hasło', function (): void {
    $podejrzane = array_values(array_filter(
        wszystkieKolumny(),
        fn (string $kolumna): bool => preg_match(WZORZEC_HASLA, $kolumna) === 1
    ));

    expect($podejrzane)->toBe([], 'Kolumny wyglądające na mechanizm haseł: '.implode(', ', $podejrzane));
});

it('nie ma ani jednej tabeli od haseł albo ich resetu', function (): void {
    $podejrzane = array_values(array_filter(
        wszystkieTabele(),
        fn (string $tabela): bool => preg_match(WZORZEC_TABELI, $tabela) === 1
    ));

    expect($podejrzane)->toBe([], 'Tabele od haseł/resetu: '.implode(', ', $podejrzane));
});

it('sprawdza schemat, który realnie ma tabele — nie pustkę', function (): void {
    // Bez tego dwa testy wyżej przechodzą także wtedy, gdy migracje w ogóle
    // się nie wykonały. Kontrola musi wiedzieć, że miała czego szukać.
    expect(wszystkieTabele())->toContain('users', 'konfiguracja_regul')
        ->and(count(wszystkieKolumny()))->toBeGreaterThan(10);
});

// ---------------------------------------------------------------------------
// MODELE — żaden nie może umieć uwierzytelniać hasłem
// ---------------------------------------------------------------------------

it('nie ma w app/ ANI JEDNEJ klasy zdolnej do uwierzytelniania hasłem', function (): void {
    // Weryfikator podłożył `App\Models\Personel extends Authenticatable`
    // w osobnym pliku — poprzedni test patrzył wyłącznie na `App\Models\User`.
    $podejrzane = [];

    foreach (plikiAplikacji() as $plik) {
        // Usuwamy komentarze: kontrola ma patrzeć na to, co się WYKONUJE,
        // a nie na to, co ktoś o tym napisał. Bez tego zdanie „NIE dziedziczy
        // po Authenticatable" w komentarzu modelu zapala test, który pilnuje
        // czegoś dokładnie odwrotnego.
        $tresc = bezKomentarzy((string) file_get_contents($plik));

        if (preg_match('/\bAuthenticatable\b/', $tresc) === 1) {
            $podejrzane[] = str_replace(base_path().'/', '', $plik);
        }
    }

    expect($podejrzane)->toBe([], 'Pliki odwołujące się do Authenticatable: '.implode(', ', $podejrzane));
});

it('nie miesza w kodzie ani jednego skrótu hasła', function (): void {
    // `Hash::make`, `bcrypt()`, `password_hash()`, `Hash::check` — obecność
    // któregokolwiek znaczy, że gdzieś powstaje albo jest sprawdzane hasło.
    $podejrzane = [];

    foreach (plikiAplikacji() as $plik) {
        $tresc = bezKomentarzy((string) file_get_contents($plik));

        if (preg_match('/(Hash::(make|check)|\bbcrypt\s*\(|\bpassword_hash\s*\(|\bpassword_verify\s*\()/', $tresc) === 1) {
            $podejrzane[] = str_replace(base_path().'/', '', $plik);
        }
    }

    expect($podejrzane)->toBe([], 'Pliki mieszające skróty haseł: '.implode(', ', $podejrzane));
});

it('przegląda realny zbiór plików aplikacji — nie pustą listę', function (): void {
    // Ta sama zasada co przy schemacie: kontrola musi wiedzieć, że miała
    // czego szukać. Bez tego dwa testy wyżej przechodzą przy pustym katalogu.
    expect(count(plikiAplikacji()))->toBeGreaterThan(10);
});

// ---------------------------------------------------------------------------
// TRASY — po polsku i po angielsku
// ---------------------------------------------------------------------------

it('nie wystawia ani jednej trasy hasłowej', function (): void {
    // Weryfikator dołożył `/reset-hasla` i `/zaloguj-haslem`. Poprzedni test
    // szukał `haslo` — a odmiana „hasła"/„hasłem" go omijała.
    $podejrzane = [];

    foreach (Route::getRoutes()->getRoutes() as $trasa) {
        $sciezka = $trasa->uri();
        $nazwa = Typy::napis($trasa->getName());

        if (preg_match(WZORZEC_HASLA, $sciezka.' '.$nazwa) === 1) {
            $podejrzane[] = $sciezka;
        }
    }

    expect($podejrzane)->toBe([], 'Trasy hasłowe: '.implode(', ', $podejrzane));
});

// ---------------------------------------------------------------------------
// KONFIGURACJA
// ---------------------------------------------------------------------------

it('nie ma czego resetować — tabela brokera haseł nie istnieje', function (): void {
    // `config('auth.passwords')` NIE jest dowodem: Laravel 11+ scala
    // konfigurację aplikacji z domyślną konfiguracją frameworka, więc
    // usunięcie klucza przywraca wartość domyślną. Zmierzone.
    $tabelaBrokera = Typy::napis(config('auth.passwords.users.table'), 'password_reset_tokens');

    expect(Schema::hasTable($tabelaBrokera))->toBeFalse();
});

it('nie ma ani jednego strażnika nad providerem zdolnym do haseł', function (): void {
    // Strażnik bez providera nie ma kogo sprawdzić. Gdyby ktoś dołożył
    // providera Eloquent nad modelem `Authenticatable`, złapie to test wyżej.
    $providery = Typy::mapa(config('auth.providers'));

    foreach ($providery as $nazwa => $provider) {
        $model = Typy::napis(Typy::mapa($provider)['model'] ?? null);

        if ($model !== '') {
            expect(is_subclass_of($model, Authenticatable::class))
                ->toBeFalse("Provider {$nazwa} wskazuje model zdolny do uwierzytelniania: {$model}");
        }
    }
});
