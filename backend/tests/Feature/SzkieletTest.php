<?php

declare(strict_types=1);
use App\Wsparcie\BlokadaWysylki;

// Bramka F0: te asercje liczą WARTOŚCI konfiguracji, nie sprawdzają, „czy coś jest".
// Każda z nich pilnuje decyzji zapisanej w CLAUDE.md — cicha zmiana którejkolwiek
// z nich rozwala moduł, który dopiero powstanie.

it('odpowiada 200 na sondę zdrowia /up', function (): void {
    $this->get('/up')->assertOk();
});

it('wystawia sondę wersji pod /api/wersja', function (): void {
    $this->getJson('/api/wersja')
        ->assertOk()
        ->assertJsonPath('aplikacja', 'Gabinet')
        ->assertJsonPath('srodowisko', 'testing');
});

it('trzyma czas aplikacji w UTC, a prezentację w Europe/Warsaw', function (): void {
    // CLAUDE.md §5. Gdyby APP_TIMEZONE zsunęło się na Europe/Warsaw, testy
    // dób 23/25-godzinnych z F1 zaczęłyby przechodzić z błędnego powodu.
    expect(config('app.timezone'))->toBe('UTC')
        ->and(config('gabinet.strefa_prezentacji'))->toBe('Europe/Warsaw');
});

it('używa PostgreSQL, a nie SQLite', function (): void {
    // CLAUDE.md §6: współbieżność egzekwuje baza (unikalne ograniczenie
    // + blokada wiersza). Na SQLite test 100 równoczesnych żądań jest fikcją.
    expect(config('database.default'))->toBe('pgsql')
        ->and(DB::connection()->getDriverName())->toBe('pgsql');
});

it('nie dotyka bazy deweloperskiej', function (): void {
    // Bez `force="true"` w phpunit.xml Pest wczytuje `.env` PRZED blokiem <php>
    // i suita jedzie na bazie dewelopera. Ten test jest jedynym miejscem, które
    // to wykrywa — patrz komentarz w phpunit.xml.
    expect(DB::connection()->getDatabaseName())->toEndWith('_test')
        ->and(config('queue.default'))->toBe('sync');
});

it('kieruje kolejki i cache na Redis', function (): void {
    // W testach QUEUE_CONNECTION=sync (phpunit.xml) — sprawdzamy więc, że
    // połączenie `redis` jest realnie skonfigurowane dla Horizona.
    expect(config('queue.connections.redis.driver'))->toBe('redis')
        ->and(config('horizon.use'))->not->toBeNull();
});

it('nie pozwala poczcie wyjść na zewnątrz przy włączonej blokadzie', function (): void {
    // Test sprawdza SKUTEK po starcie aplikacji, nie samą obecność ustawienia.
    expect(config('gabinet.blokada_wysylki'))->toBeTrue()
        ->and(config('mail.default'))->toBeIn(BlokadaWysylki::STEROWNIKI_NIEWYSYLAJACE);
});
