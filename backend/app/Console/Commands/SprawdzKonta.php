<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Tozsamosc\Bramki;
use App\Tozsamosc\KontaOidc;
use App\Tozsamosc\WalidatorTokenu;
use App\Wsparcie\Typy;
use Illuminate\Console\Command;
use Throwable;

/**
 * Sonda integracji z Kontami Niepodzielni — odpowiednik `smoke.sh` z repo `konta`.
 *
 * Sprawdza to, czego nie da się udowodnić testem jednostkowym: że ŻYWY IdP jest
 * osiągalny trasą wewnętrzną, że zwraca publicznego issuera i że nasza walidacja
 * przyjmuje albo odrzuca PRAWDZIWY token z właściwego powodu.
 *
 * Uruchamiana ręcznie i przez `skrypty/keycloak-sprawdz.sh`. Nie jest częścią
 * bramki CI, bo wymaga stojącego stosu `konta`.
 */
final class SprawdzKonta extends Command
{
    protected $signature = 'konta:sprawdz
        {--token= : Access token do zweryfikowania (opcjonalnie)}
        {--audiencja= : Nadpisanie wymaganej audiencji przy sprawdzaniu tokenu}';

    protected $description = 'Sprawdza połączenie z Kontami Niepodzielni (discovery, JWKS, walidacja tokenu)';

    public function handle(KontaOidc $oidc): int
    {
        $bledy = 0;

        $this->line('Issuer publiczny:   '.$oidc->issuerPubliczny());
        $this->line('Issuer wewnętrzny:  '.$oidc->issuerWewnetrzny());
        $this->line('Klient:             '.$oidc->clientId());
        $this->line('Wymagana audiencja: '.$oidc->wymaganaAudiencja());
        $this->newLine();

        try {
            $metadane = $oidc->metadane();
            $this->info('[OK] discovery — issuer zgodny z adresem publicznym');
            $this->line('     authorization_endpoint: '.Typy::napis($metadane['authorization_endpoint'] ?? null));
            $this->line('     token_endpoint (wewn.): '.Typy::napis($metadane['token_endpoint'] ?? null));
        } catch (Throwable $e) {
            $this->error('[BŁĄD] discovery: '.$e->getMessage());

            return self::FAILURE;
        }

        try {
            $jwks = $oidc->jwks();
            $klucze = Typy::mapa($jwks)['keys'] ?? [];
            $ile = is_array($klucze) ? count($klucze) : 0;

            if ($ile === 0) {
                $this->error('[BŁĄD] JWKS nie zawiera ani jednego klucza');
                $bledy++;
            } else {
                $this->info("[OK] JWKS — kluczy: {$ile}");
            }
        } catch (Throwable $e) {
            $this->error('[BŁĄD] JWKS: '.$e->getMessage());

            return self::FAILURE;
        }

        $token = Typy::napis($this->option('token'));

        if ($token === '') {
            $this->newLine();
            $this->comment('Bez --token sprawdzam wyłącznie osiągalność IdP.');

            return $bledy === 0 ? self::SUCCESS : self::FAILURE;
        }

        $audiencja = Typy::napis($this->option('audiencja')) ?: $oidc->wymaganaAudiencja();

        $wynik = WalidatorTokenu::sprawdz($token, [
            'issuer' => $oidc->issuerPubliczny(),
            'jwks' => $oidc->jwks(),
            'audience' => $audiencja,
            'typ' => 'Bearer',
            'tolerancja' => $oidc->tolerancjaZegara(),
        ]);

        $this->newLine();
        $this->line('Kontrole tokenu (wymagana audiencja: '.$audiencja.'):');

        foreach ($wynik['kontrole'] as $nazwa => $stan) {
            $this->line(sprintf('  %-10s %s', $nazwa, $stan === 'ok' ? '[OK]' : '[FAIL]'));
        }

        $role = Bramki::roleZAccessTokenu($wynik['claims']);

        $this->newLine();
        $this->line('sub:   '.Typy::napis($wynik['claims']['sub'] ?? null, '(brak)'));
        $this->line('azp:   '.Typy::napis($wynik['claims']['azp'] ?? null, '(brak)'));
        $this->line('role:  '.($role === [] ? '(pusta lista — poprawny stan konta)' : implode(' ', $role)));
        $this->line('bramki otwarte: '.(implode(' ', array_keys(array_filter(Bramki::dlaRol($role)))) ?: '(żadna)'));

        $this->newLine();

        if ($wynik['ok']) {
            $this->info('[OK] token przyjęty');

            return $bledy === 0 ? self::SUCCESS : self::FAILURE;
        }

        $this->warn('[ODRZUCONY] nieudane kontrole: '.implode(', ', $wynik['nieudane']));

        // Odrzucenie bywa POPRAWNYM wynikiem (test negatywny) — o tym decyduje
        // skrypt wołający, nie ta komenda. Kod wyjścia mówi tylko „token nie przeszedł".
        return self::FAILURE;
    }
}
