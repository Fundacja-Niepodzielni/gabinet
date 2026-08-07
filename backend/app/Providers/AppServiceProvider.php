<?php

declare(strict_types=1);

namespace App\Providers;

use App\Wsparcie\BlokadaWysylki;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->zablokujWysylkePozaProdukcja();
    }

    /**
     * Podmienia sterownik poczty na `log`, dopóki blokada jest włączona.
     *
     * Robimy to w kodzie, a nie samą konfiguracją `.env`, bo konfigurację da
     * się skopiować z produkcji jednym `scp` — a wtedy staging wysyła
     * przypomnienia na prawdziwe adresy pacjentów (CLAUDE.md §10).
     */
    private function zablokujWysylkePozaProdukcja(): void
    {
        $blokada = (bool) config('gabinet.blokada_wysylki');

        $skonfigurowany = config('mail.default');

        if (! is_string($skonfigurowany)) {
            return;
        }

        $dozwolony = BlokadaWysylki::sterownikPoczty($skonfigurowany, $blokada);

        if ($dozwolony === $skonfigurowany) {
            return;
        }

        config(['mail.default' => $dozwolony]);

        Log::warning('Blokada wysyłki: sterownik poczty podmieniony.', [
            'skonfigurowany' => $skonfigurowany,
            'uzyty' => $dozwolony,
            'srodowisko' => $this->app->environment(),
        ]);
    }
}
