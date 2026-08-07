<?php

declare(strict_types=1);

use App\Models\User;
use App\Wsparcie\Typy;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Regresja na CLAUDE.md §2: „ŻADNYCH własnych haseł w tym systemie".
 *
 * Test powstał po niezależnej weryfikacji, która pokazała, że domyślny
 * szkielet Laravela dowiózł kolumnę `password`, `remember_token` i tabelę
 * `password_reset_tokens` — i że migracje zostały już wykonane. Dokumentacja
 * deklarowała zgodność, a schemat bazy woził mechanizm własnych haseł.
 *
 * WYTYCZNE-PRACY §3: „reguła bez testu na złamanie jej nie istnieje".
 * Dlatego asercje idą po SCHEMACIE BAZY i po realnej tablicy tras — czyli po
 * stanie, a nie po deklaracji w komentarzu.
 */
it('nie ma w bazie ani jednej kolumny hasła', function (): void {
    expect(Schema::hasTable('users'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'password'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'remember_token'))->toBeFalse();
});

it('nie ma tabeli resetu haseł', function (): void {
    expect(Schema::hasTable('password_reset_tokens'))->toBeFalse();
});

it('wiąże konto lokalne po sub z Keycloaka, nie po e-mailu', function (): void {
    // Kontrakt §6 pkt 3 i CLAUDE.md §2. E-mail bywa NIEOBECNY w tokenie
    // (kontrakt §2a), więc nie może być kluczem tożsamości.
    expect(Schema::hasColumn('users', 'keycloak_sub'))->toBeTrue();

    $unikalneKolumny = [];

    foreach (Schema::getIndexes('users') as $indeks) {
        $indeks = Typy::mapa($indeks);

        if (($indeks['unique'] ?? false) === true) {
            $unikalneKolumny = array_merge($unikalneKolumny, Typy::listaNapisow($indeks['columns'] ?? null));
        }
    }

    expect($unikalneKolumny)->toContain('keycloak_sub')
        // E-mail NIE MOŻE być unikalny: dwa konta w IdP mogą nie mieć e-maila
        // w ogóle, a ograniczenie unikalności na NULL-ach jest pułapką.
        ->and($unikalneKolumny)->not->toContain('email');
});

it('nie ma czego resetować — tabela brokera haseł nie istnieje', function (): void {
    // UWAGA: samo `config('auth.passwords')` NIE jest dowodem. Laravel 11+
    // scala konfigurację aplikacji z domyślną konfiguracją frameworka, więc
    // usunięcie klucza z `config/auth.php` przywraca wartość domyślną
    // (`table => password_reset_tokens`). Zmierzone, nie założone.
    //
    // Dlatego pytamy o STAN: nawet gdyby broker był skonfigurowany, nie ma
    // tabeli, na której mógłby cokolwiek zrobić — a `Schema::hasTable()`
    // czyta katalog systemowy bazy, nie plik konfiguracyjny.
    $tabelaBrokera = Typy::napis(config('auth.passwords.users.table'), 'password_reset_tokens');

    expect(Schema::hasTable($tabelaBrokera))->toBeFalse();

    // Drugi zamek: model wskazany przez providera (domyślnie App\Models\User)
    // nie umie oddać hasła, bo nie jest `Authenticatable`. Nawet gdyby ktoś
    // odtworzył tabelę, broker nie ma czego porównać.
    $model = Typy::napis(config('auth.providers.users.model'), User::class);

    expect(is_subclass_of($model, Authenticatable::class))->toBeFalse();
});

it('nie wystawia ani jednej trasy hasłowej', function (): void {
    $podejrzane = [];

    foreach (Route::getRoutes()->getRoutes() as $trasa) {
        $sciezka = $trasa->uri();
        $nazwa = Typy::napis($trasa->getName());

        if (str_contains($sciezka, 'password') || str_contains($sciezka, 'haslo')
            || str_starts_with($nazwa, 'password.')) {
            $podejrzane[] = $sciezka;
        }
    }

    expect($podejrzane)->toBe([]);
});

it('nie ma modelu użytkownika zdolnego do uwierzytelniania hasłem', function (): void {
    // `Authenticatable` niesie `getAuthPassword()` i `getRememberToken()`.
    // Dopóki model po nim nie dziedziczy, żaden strażnik Laravela nie ma
    // czego sprawdzić — i to jest zamierzone.
    expect(is_subclass_of(User::class, Authenticatable::class))
        ->toBeFalse();
});
