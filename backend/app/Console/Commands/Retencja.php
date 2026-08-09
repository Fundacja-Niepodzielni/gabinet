<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Retencja\RejestrRetencji;
use App\Retencja\ZadanieRetencji;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * WYWOŁUJĄCY zadania retencji — powód istnienia tej klasy to `R6A-11`.
 *
 * Weryfikator rundy 6 zmierzył, że `ZadanieRetencji` **nie ma ani jednego
 * wywołującego**: klasa istniała, testy kasowania świeciły zielono, a na żywym
 * systemie **nic się nie kasowało**. Wszystkie kontrole retencji badały
 * KASOWANIE, żadna nie badała URUCHAMIANIA — a to są dwa różne twierdzenia.
 *
 * Zasada twarda 10 mówi „retencje jako zadania czyszczące w kodzie". Zadanie
 * bez wywołującego nie jest zadaniem czyszczącym, tylko klasą.
 *
 * ================== DLACZEGO NIE KASUJE WSZYSTKIEGO ==================
 *
 * Sprzątaczka dotyka wyłącznie wpisów, które mają JEDNOCZEŚNIE `kasuje = true`
 * i USTALONY `okres_dni`. Tabele anonimizowane (`pacjenci`, `rezerwacje`) nie są
 * jej sprawą — skasowanie rezerwacji zniszczyłoby rekord finansowy.
 *
 * Tabele o NIEUSTALONYM okresie są pomijane **głośno**: idą do logu i na wyjście
 * jako dług wobec IOD. „Pominięte po cichu" i „pominięte, bo nikt nie ustalił
 * okresu" wyglądają w logu identycznie, a znaczą co innego.
 *
 * @dowod: HarmonogramRetencjiTest — kontrola URUCHOMIENIA, osobna od kontroli kasowania.
 */
final class Retencja extends Command
{
    protected $signature = 'gabinet:retencja
        {--na-sucho : Pokaż, co zostałoby usunięte, niczego nie usuwając}';

    protected $description = 'Wykonuje retencję dla tabel z ustalonym okresem (rejestr retencji)';

    public function handle(): int
    {
        $teraz = CarbonImmutable::now();
        $naSucho = (bool) $this->option('na-sucho');
        $doWykonania = RejestrRetencji::doWykonania();
        $czekajace = RejestrRetencji::czekajaceNaOkres();

        $niekompletne = [];
        $razemUsuniete = 0;

        foreach ($doWykonania as $tabela => $wpis) {
            $prog = $wpis['okres_dni'];

            if ($prog === null) {
                continue;  // nieosiągalne — `doWykonania()` już to odfiltrowało
            }

            if ($naSucho) {
                $this->line("  {$tabela}: przebieg na sucho, próg {$prog} dni");

                continue;
            }

            $wynik = (new ZadanieRetencji($teraz))->wykonaj(
                $tabela,
                $wpis['kolumna_pochodzenia'],
                $prog
            );

            $razemUsuniete += $wynik->usuniete;
            $this->line("  {$tabela}: wybrane {$wynik->wybrane}, usunięte {$wynik->usuniete}");

            // NIEPUSTA lista pozostałych to NARUSZENIE retencji, nie ostrzeżenie:
            // rekord po terminie przeżył własne zadanie czyszczące.
            if (! $wynik->kompletny()) {
                $niekompletne[] = $tabela.' (pozostało '.count($wynik->pozostale).')';
                $this->error("  [BŁĄD] {$tabela}: rekordy po terminie PRZEŻYŁY zadanie retencyjne");
            }
        }

        // Ślad wykonania zapisujemy ZAWSZE, gdy przebieg doszedł do końca —
        // także gdy nie było czego usuwać. „Nic nie usunięto" i „nie biegło"
        // to dwa różne stany i kontrola musi je rozróżniać.
        if (! $naSucho) {
            Log::info('retencja.przebieg', [
                'usuniete' => $razemUsuniete,
                'tabel' => count($doWykonania),
                'czekajace_na_okres' => $czekajace,
            ]);
        }

        if ($czekajace !== []) {
            $this->warn(
                '  DŁUG WOBEC IOD — tabele kasowane BEZ ustalonego okresu, pominięte: '
                .implode(', ', $czekajace)
            );
        }

        if ($niekompletne !== []) {
            $this->error('RETENCJA NIEKOMPLETNA: '.implode('; ', $niekompletne));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
