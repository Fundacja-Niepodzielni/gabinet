<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Wsparcie\Typy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

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
/*
 * MAGAZYNEM JEST PLIK, NIE CACHE ANI BAZA (R6B-10, zamkniete 12.08).
 *
 * Puls siedzial w cache'u — magazynie, ktory `cache:clear`, deploy i restart
 * Redisa czyszcza rutynowo. Waga jest NIZSZA niz przy mapie `sid -> sesje`
 * i mowie to wprost: utrata pulsu daje healthcheck CZERWONY, czyli
 * fail-CLOSED. Kosztem nie jest dziura, tylko FALSZYWY ALARM przy kazdym
 * czyszczeniu cache'u — a falszywy alarm uczy ludzi ignorowac alarm.
 *
 * Baza byla PIERWSZYM wyborem i BYLA ZLYM WYBOREM — obalone wlasna bramka:
 * krok [5] meldowal `scheduler=starting`, bo sonda kontenera wola
 * `gabinet:puls --sprawdz`, a migracje ida dopiero w kroku [13]. Sonda
 * pytala wiec o tabele, ktorej jeszcze nie ma, i harmonogram nie mial jak
 * zglosic zdrowia. Sygnal zdrowia NIE MOZE zalezec od schematu, ktorego
 * powstanie sam poprzedza.
 *
 * PLIK spelnia caly model zagrozen R6B-10 (`cache:clear`, restart Redisa,
 * eksmisja), nie wymaga schematu, a jego utrata razem z kontenerem jest
 * POPRAWNA: nowy harmonogram ma sie wykazac od nowa. To ten sam mechanizm,
 * ktorego uzywa `SladWylogowania`, i z tego samego powodu — sygnal nie
 * moze dzielic magazynu ze swoim przedmiotem (regula C1).
 *
 * @dowod: TrwaloscMagazynowTest — puls PRZEZYWA `Cache::flush()`.
 */
final class Puls extends Command
{
    protected $signature = 'gabinet:puls
        {--sprawdz : Zamiast zapisywać puls, sprawdź czy jest świeży (do healthchecku)}';

    protected $description = 'Zapisuje puls harmonogramu albo sprawdza jego świeżość';

    /** Sciezka pliku pulsu. Jedno miejsce, zeby zapis i odczyt nie rozjechaly sie. */
    private static function plik(): string
    {
        return storage_path('puls-harmonogramu');
    }

    /** Ile sekund puls jest uznawany za świeży. Zadanie chodzi co minutę. */
    private const SWIEZOSC_SEKUND = 180;

    public function handle(): int
    {
        if ($this->option('sprawdz')) {
            return $this->sprawdz();
        }

        File::ensureDirectoryExists(dirname(self::plik()));
        File::put(self::plik(), (string) time());

        // Prawa jak przy sladzie wylogowania i z tej samej przyczyny (N-14):
        // zapisuje harmonogram, a czytac musi takze sonda kontenera.
        @chmod(self::plik(), 0666);

        return self::SUCCESS;
    }

    private function sprawdz(): int
    {
        $zapisany = File::exists(self::plik())
            ? Typy::liczba(trim(File::get(self::plik())))
            : 0;

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
