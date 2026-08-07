<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Wsparcie\Typy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sonda zdrowia aplikacji — pyta o STAN, nie o deklarację.
 *
 * Lekcja z zespołu helpdesku i z rozdz. 23 dziennika makiety: kontrola
 * statyczna nie zastępuje uruchomienia. Sprawdzenie „czy rozszerzenie jest
 * załadowane" albo „czy w konfiguracji stoi `pgsql`" przechodzi także wtedy,
 * gdy aplikacja w ogóle się nie startuje. Dlatego każda kontrola poniżej
 * wykonuje PRAWDZIWĄ operację i czyta jej wynik.
 */
final class Zdrowie extends Command
{
    protected $signature = 'gabinet:zdrowie {--cichy : Bez wypisywania szczegółów (do healthchecku)}';

    protected $description = 'Sprawdza, czy aplikacja realnie działa: framework, baza, Redis, cache';

    public function handle(): int
    {
        $cichy = (bool) $this->option('cichy');
        $bledy = [];

        // 1. Framework wystartował — samo dojście tutaj to już dowód, ale
        //    dokładamy odczyt konfiguracji, żeby wykluczyć pusty kontener.
        if (Typy::napis(config('app.name')) === '') {
            $bledy[] = 'konfiguracja aplikacji jest pusta';
        }

        // 2. Baza: prawdziwe zapytanie, nie odczyt configu.
        try {
            $wersja = DB::selectOne('select version() as wersja');
            $tekst = Typy::napis(Typy::mapa((array) $wersja)['wersja'] ?? null);

            if (! str_contains($tekst, 'PostgreSQL')) {
                $bledy[] = 'baza odpowiada, ale to nie PostgreSQL: '.$tekst;
            } elseif (! $cichy) {
                $this->line('[OK] baza: '.Str::before($tekst, ' on '));
            }
        } catch (Throwable $e) {
            $bledy[] = 'baza nie odpowiada: '.$e->getMessage();
        }

        // 3. Redis: PING na żywym połączeniu.
        try {
            Redis::connection()->ping();

            if (! $cichy) {
                $this->line('[OK] Redis odpowiada na PING');
            }
        } catch (Throwable $e) {
            $bledy[] = 'Redis nie odpowiada: '.$e->getMessage();
        }

        // 4. Cache: zapis i odczyt, nie sama nazwa sterownika.
        try {
            $klucz = 'gabinet:zdrowie:'.bin2hex(random_bytes(8));
            Cache::put($klucz, 'puls', 30);
            $odczyt = Cache::pull($klucz);

            if ($odczyt !== 'puls') {
                $bledy[] = 'cache nie oddaje zapisanej wartości';
            } elseif (! $cichy) {
                $this->line('[OK] cache zapisuje i oddaje wartość');
            }
        } catch (Throwable $e) {
            $bledy[] = 'cache nie działa: '.$e->getMessage();
        }

        if ($bledy === []) {
            if (! $cichy) {
                $this->info('ZDROWIE OK');
            }

            return self::SUCCESS;
        }

        foreach ($bledy as $blad) {
            $this->error('[BŁĄD] '.$blad);
        }

        return self::FAILURE;
    }
}
