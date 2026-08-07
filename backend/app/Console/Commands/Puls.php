<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Wsparcie\Typy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Puls harmonogramu — dowód, że pętla `schedule:work` realnie WYKONAŁA zadanie.
 *
 * Po co: sonda „czy proces istnieje" jest tautologią (poprzednia wersja
 * healthchecku trafiała we własny PID 1 kontenera i meldowała „healthy",
 * zanim harmonogram w ogóle wystartował). Cichym trybem awarii zadań
 * okresowych jest właśnie to, że nic się nie dzieje — a nic nie krzyczy.
 *
 * W F6 od tej samej pętli zależą przypomnienia 24 h i 2 h, wygaszanie blokad
 * koszyka i awanse z listy rezerwowej. Martwy harmonogram = pacjent bez
 * przypomnienia i termin, który nigdy nie wraca do puli.
 */
final class Puls extends Command
{
    protected $signature = 'gabinet:puls
        {--sprawdz : Zamiast zapisywać puls, sprawdź czy jest świeży (do healthchecku)}';

    protected $description = 'Zapisuje puls harmonogramu albo sprawdza jego świeżość';

    private const KLUCZ = 'gabinet:puls-harmonogramu';

    /** Ile sekund puls jest uznawany za świeży. Zadanie chodzi co minutę. */
    private const SWIEZOSC_SEKUND = 180;

    public function handle(): int
    {
        if ($this->option('sprawdz')) {
            return $this->sprawdz();
        }

        Cache::put(self::KLUCZ, time(), self::SWIEZOSC_SEKUND * 4);

        return self::SUCCESS;
    }

    private function sprawdz(): int
    {
        $zapisany = Typy::liczba(Cache::get(self::KLUCZ), 0);

        if ($zapisany === 0) {
            $this->error('[BŁĄD] harmonogram nie zapisał jeszcze ani jednego pulsu');

            return self::FAILURE;
        }

        $wiek = time() - $zapisany;

        if ($wiek > self::SWIEZOSC_SEKUND) {
            $this->error("[BŁĄD] ostatni puls harmonogramu sprzed {$wiek} s (limit: ".self::SWIEZOSC_SEKUND.' s)');

            return self::FAILURE;
        }

        $this->line("puls harmonogramu sprzed {$wiek} s");

        return self::SUCCESS;
    }
}
