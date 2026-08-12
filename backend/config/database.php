<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Pdo\Mysql;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        /*
         * SESJE MAJĄ WŁASNĄ BAZĘ REDISA — rozdzielenie przestrzeni kluczy.
         *
         * Sterownik `redis` sesji idzie przez magazyn CACHE'U, więc bez tego
         * klucze sesji lądowały w tej samej przestrzeni co cache. Skutki,
         * których nikt nie wybrał:
         *   · `Cache::flush()` wykonuje `FLUSHDB` — czyli czyszczenie cache'u
         *     „dla porządku" WYLOGOWYWAŁO WSZYSTKICH. Kierunek bezpieczny
         *     (fail-closed), ale zachowanie nieprzewidziane;
         *   · przy polityce eksmisji `allkeys-*` żywe sesje byłyby eksmitowane
         *     pod presją pamięci — użytkownicy wylatują losowo, bez śladu
         *     w logach aplikacji.
         *
         * ⛔ SPROSTOWANIE WYZWALACZA (R6A-8, 12.08). Ten komentarz wiązał kiedyś
         * rozdzielenie baz z OCHRONĄ PRZED EKSMISJĄ, a `D-2026-08-08-28` mówi
         * wprost coś przeciwnego — i decyzji NIE CYTOWAŁ, więc czytelnik nie miał
         * jak sprawdzić rozbieżności.
         *
         * Zmierzone (N-6, na żywym kontenerze): `maxmemory` = 0,
         * `maxmemory-policy` = `noeviction`. Przy tych wartościach EKSMISJA LRU
         * NIE MOŻE ZAJŚĆ W OGÓLE — Redis przy wyczerpaniu pamięci zaczyna
         * ODRZUCAĆ ZAPISY błędem OOM, a odczyty działają dalej. Nazwany wyzwalacz
         * po prostu nie zachodzi w tej konfiguracji.
         *
         * Do tego eksmisja jest własnością INSTANCJI, nie bazy: `maxmemory-policy`
         * nie ma wariantu per-baza, więc rozdzielenie `cache=1` / `sesje=2` NIE
         * DAJE sesjom żadnej ochrony przed eksmisją, gdyby limit kiedykolwiek
         * ustawiono.
         *
         * Rozdzielenie zostaje, bo jest słuszne z INNEGO powodu — `Cache::flush()`
         * i `FLUSHDB` działają per baza. Wyzwalacz podany błędnie jest gorszy niż
         * brak wyzwalacza, bo wygląda na zmierzony.
         *
         * WARUNEK UTRZYMUJĄCY: `maxmemory` pozostaje 0. Ustawienie limitu WŁĄCZA
         * klasę awarii, której dziś nie ma — i wtedy to rozdzielenie przestanie
         * cokolwiek dawać.
         *
         * To ta sama przyczyna, dla której znacznik unieważnienia musiał wyjść
         * z cache'u do PostgreSQL (D-2026-08-08-26 i okolice): brak segmentacji
         * przestrzeni kluczy. Tam naprawiono objaw, tu przyczynę.
         *
         * Skutek uboczny, celowy: klucze sesji są wyodrębnione STRUKTURALNIE,
         * więc migawki magazynu tożsamości nie muszą ich zgadywać wzorcem.
         */
        'sesje' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_SESSION_DB', '2'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

    ],

];
