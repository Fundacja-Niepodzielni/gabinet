<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Wsparcie\FabrykaTokenow;

/*
|---------------------------------------------------------------------------
| Konfiguracja Pest
|---------------------------------------------------------------------------
| Zasada z WYTYCZNE-PRACY.md §3: dla każdego zachowania test pozytywny
| I negatywny. Testy liczą WARTOŚCI i pytają o STAN, nie o obecność elementu
| ani o zawartość konfiguracji (CLAUDE.md §15, dziennik makiety rozdz. 15).
|
| `RefreshDatabase` w suicie Feature jest tu ŚWIADOMY, mimo kosztu: bez niego
| testy schematu (np. „w bazie nie ma kolumny hasła") sprawdzałyby bazę
| w nieznanym stanie, a to znaczy: nie sprawdzałyby niczego.
*/

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

/*
|---------------------------------------------------------------------------
| Atrapa IdP — wspólna dla wszystkich testów tożsamości
|---------------------------------------------------------------------------
| Mieszkała w `LogowanieTest.php`, więc widział ją wyłącznie ten jeden plik.
| Drugi test warstwy OIDC (`WzmacniaczZadanTest`) nie mógł jej użyć — a dwie
| kopie atrapy IdP to prosta droga do sytuacji, w której testy sprawdzają
| różne rzeczy, myśląc, że sprawdzają to samo.
*/
/**
 * Podstawia atrapę IdP: discovery + JWKS z klucza wygenerowanego w teście.
 * Dzięki temu cała warstwa `KontaOidc` działa naprawdę (cache, przepisywanie
 * endpointów, kontrola issuera) — atrapowany jest wyłącznie ruch sieciowy.
 */
/**
 * Metadane discovery atrapy IdP — jedno źródło dla wszystkich testów.
 *
 * Wydzielone, bo testy przeliczania ról (B8) muszą podstawiać własną atrapę
 * punktu tokenów, nie tracąc reszty metadanych.
 *
 * @return array<string, mixed>
 */
function discoveryAtrapy(): array
{
    return [
        'issuer' => FabrykaTokenow::ADRES,
        'authorization_endpoint' => FabrykaTokenow::ADRES.'/protocol/openid-connect/auth',
        'token_endpoint' => FabrykaTokenow::ADRES.'/protocol/openid-connect/token',
        'jwks_uri' => FabrykaTokenow::ADRES.'/protocol/openid-connect/certs',
        'end_session_endpoint' => FabrykaTokenow::ADRES.'/protocol/openid-connect/logout',
        'userinfo_endpoint' => FabrykaTokenow::ADRES.'/protocol/openid-connect/userinfo',
    ];
}

/**
 * @param  array<string, mixed>  $nadpisaniaDiscovery
 */
function udawajIdp(array $nadpisaniaDiscovery = []): void
{
    config([
        'konta.issuer_publiczny' => FabrykaTokenow::ADRES,
        'konta.issuer_wewnetrzny' => FabrykaTokenow::ADRES,
        'konta.client_id' => 'gabinet',
        'konta.wymagana_audiencja' => 'gabinet',
        'konta.redirect_uri' => 'http://localhost/auth/callback',
        'konta.tolerancja_zegara' => 30,
    ]);

    $discovery = array_merge(discoveryAtrapy(), $nadpisaniaDiscovery);

    Http::fake([
        'idp.test/*/.well-known/openid-configuration' => Http::response($discovery),
        'idp.test/*/protocol/openid-connect/certs' => Http::response(FabrykaTokenow::jwks()),
    ]);
}

/*
|---------------------------------------------------------------------------
| Wstępna sonda bazy — U-3 z rundy 3 weryfikacji
|---------------------------------------------------------------------------
| Naprawa O-2 polegała na `PGCONNECT_TIMEOUT=5` w obrazie. Niezależny
| weryfikator ZMIERZYŁ, że to nie działa: przy hoście kierującym w próżnię
| (adres bez odpowiedzi) suita wisiała ~169 s zarówno z tą zmienną, jak i bez
| niej. PDO nie przekazuje jej do libpq w sposób, na który liczyliśmy — czyli
| „naprawa" była deklaracją.
|
| Sonda TCP nie zależy od zachowania sterownika: własnoręcznie ustawiony limit
| na gnieździe albo zadziała, albo nie — i jedno od drugiego da się odróżnić.
| W CI liczy się różnica między czerwienią po kilku sekundach a zabiciem joba
| po 25 minutach: pierwsze mówi, co jest zepsute, drugie nie mówi nic.
*/
$sondaBazy = static function (): void {
    // Czytamy WPROST ze środowiska, nie z kontenera Laravela: sonda ma
    // zadziałać ZANIM aplikacja wstanie, bo bez bazy i tak nie wstanie.
    $host = (string) (getenv('DB_HOST') ?: '');
    $port = (int) (getenv('DB_PORT') ?: 5432);

    if ($host === '' || $port === 0) {
        return;
    }

    $limit = (float) (getenv('GABINET_SONDA_BAZY_S') ?: 5);
    $start = microtime(true);
    $gniazdo = @fsockopen($host, $port, $kodBledu, $trescBledu, $limit);

    if ($gniazdo !== false) {
        fclose($gniazdo);

        return;
    }

    $czas = round(microtime(true) - $start, 1);

    fwrite(STDERR, PHP_EOL.str_repeat('=', 74).PHP_EOL);
    fwrite(STDERR, "BAZA TESTOWA NIEOSIĄGALNA: {$host}:{$port} (po {$czas} s)".PHP_EOL);
    fwrite(STDERR, "  {$trescBledu} [{$kodBledu}]".PHP_EOL);
    fwrite(STDERR, 'Suita NIE startuje — bez bazy każdy jej wynik byłby nieprawdziwy.'.PHP_EOL);
    fwrite(STDERR, 'Naprawa: docker compose up -d && docker compose exec app php artisan gabinet:zdrowie'.PHP_EOL);
    fwrite(STDERR, str_repeat('=', 74).PHP_EOL);

    exit(1);
};

$sondaBazy();

/**
 * Zdekodowane ładunki base64url ze wszystkiego, co w treści wygląda na JWT.
 *
 * Świadomie bez sprawdzania podpisu i bez ograniczania się do „naszych"
 * tokenów: interesuje nas KAŻDY ciąg, z którego da się odczytać dane osobowe,
 * niezależnie od tego, kto go wystawił i czy jest ważny.
 */
function zdekodowaneLadunki(string $tresc): string
{
    if (preg_match_all('/[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]+/', $tresc, $trafienia) === 0) {
        return '';
    }

    $zdekodowane = '';

    foreach ($trafienia[0] as $jwt) {
        foreach (array_slice(explode('.', $jwt), 0, 2) as $czesc) {
            $zdekodowane .= (string) base64_decode(strtr($czesc, '-_', '+/'), false);
        }
    }

    return $zdekodowane;
}
