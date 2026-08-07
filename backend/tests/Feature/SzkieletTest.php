<?php

declare(strict_types=1);

use App\Wsparcie\BlokadaWysylki;
use App\Wsparcie\Typy;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

/**
 * Bramka F0 — asercje pytają o STAN, nie o deklarację.
 *
 * Pierwsza wersja tego pliku sprawdzała wartości z `config()`. Niezależny
 * weryfikator pokazał, że cała suita przechodziła przy `DB_HOST` wskazującym
 * nieistniejący host i przy nieistniejącym Redisie — bo nic nigdy nie
 * otwierało połączenia. To dokładnie klasa błędów z rozdz. 15 dziennika
 * makiety: „ekran działał, tylko nic nie znaczył".
 */
it('odpowiada 200 na sondę zdrowia /up', function (): void {
    $this->get('/up')->assertOk();
});

it('przedstawia się WŁASNYM znacznikiem, nie samym kodem 200', function (): void {
    // Lekcja z zespołu helpdesku: HTTP 200 to nie tożsamość — cudza usługa
    // na zajętym porcie potrafi dać bramce fałszywe zielone.
    $this->getJson('/api/wersja')
        ->assertOk()
        ->assertJsonPath('znacznik', 'gabinet-api-v1')
        ->assertJsonPath('aplikacja', 'Gabinet')
        ->assertJsonPath('srodowisko', 'testing');
});

it('trzyma czas aplikacji w UTC, a prezentację w Europe/Warsaw', function (): void {
    // CLAUDE.md §5. Gdyby APP_TIMEZONE zsunęło się na Europe/Warsaw, testy
    // dób 23/25-godzinnych z F1 zaczęłyby przechodzić z błędnego powodu.
    expect(config('app.timezone'))->toBe('UTC')
        ->and(config('gabinet.strefa_prezentacji'))->toBe('Europe/Warsaw')
        // STAN, nie konfiguracja: sesja bazy też musi liczyć w UTC, inaczej
        // `now()` w SQL i w PHP rozjadą się o dwie godziny.
        ->and(Typy::napis(Typy::mapa((array) DB::selectOne('show timezone'))['TimeZone'] ?? null))->toBe('UTC');
});

it('rozmawia z ŻYWYM PostgreSQL, a nie z konfiguracją', function (): void {
    // CLAUDE.md §6: współbieżność egzekwuje baza (unikalne ograniczenie
    // + blokada wiersza). Na SQLite test 100 równoczesnych żądań jest fikcją.
    $wersja = Typy::napis(Typy::mapa((array) DB::selectOne('select version() as wersja'))['wersja'] ?? null);

    expect($wersja)->toContain('PostgreSQL')
        ->and(DB::connection()->getDriverName())->toBe('pgsql');
});

it('jest podłączony do bazy TESTOWEJ, nie deweloperskiej', function (): void {
    // Nazwę czytamy z SERWERA (`current_database()`), a nie z `.env`.
    // Wersja czytająca konfigurację przechodziła przy niedostępnej bazie.
    $baza = Typy::napis(Typy::mapa((array) DB::selectOne('select current_database() as baza'))['baza'] ?? null);

    expect($baza)->toEndWith('_test')
        ->and(config('queue.default'))->toBe('sync');
});

it('rozmawia z ŻYWYM Redisem — PING i zapis do cache', function (): void {
    // Poprzednia wersja asertowała literał `'redis'` z `config/queue.php`,
    // czyli stałą, której nie da się zepsuć konfiguracją. Przechodziła przy
    // `REDIS_HOST=nie-ma-redisa-999`.
    expect(Redis::connection()->ping())->not->toBeNull();

    $klucz = 'test:puls:'.bin2hex(random_bytes(6));
    Cache::store('redis')->put($klucz, 'wartosc', 30);

    expect(Cache::store('redis')->pull($klucz))->toBe('wartosc')
        ->and(config('queue.connections.redis.driver'))->toBe('redis');
});

it('ma harmonogram z zadaniem pulsu', function (): void {
    // Bez pulsu nie da się odróżnić „harmonogram pracuje" od „proces stoi,
    // ale pętla umarła" — a to jest cichy tryb awarii zadań okresowych.
    $zadania = collect(app(Schedule::class)->events())
        ->map(fn ($zdarzenie): string => (string) $zdarzenie->command);

    expect($zadania->filter(fn (string $polecenie): bool => str_contains($polecenie, 'gabinet:puls')))
        ->not->toBeEmpty();
});

it('nie pozwala poczcie wyjść na zewnątrz przy włączonej blokadzie', function (): void {
    // Test sprawdza SKUTEK po starcie aplikacji, nie samą obecność ustawienia:
    // pytamy o klasę realnie zbudowanego transportu.
    expect(config('gabinet.blokada_wysylki'))->toBeTrue()
        ->and(config('mail.default'))->toBeIn(BlokadaWysylki::STEROWNIKI_NIEWYSYLAJACE);

    $transport = Mail::mailer()->getSymfonyTransport();

    expect($transport::class)->not->toBe(EsmtpTransport::class);
});
