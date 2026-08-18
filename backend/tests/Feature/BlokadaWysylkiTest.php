<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;

/**
 * Blokada wysyłki poczty poza produkcją (CLAUDE.md §10) — EGZEKUTOR WPIĘCIA.
 *
 * Osobny plik od `tests/Unit/BlokadaWysylkiTest.php`, i to jest cała rzecz:
 * tamten bada CZYSTĄ FUNKCJĘ `BlokadaWysylki::sterownikPoczty()` i robi to
 * dobrze. Ten pyta, czy mechanizm jest W OGÓLE URUCHAMIANY.
 *
 * ================== TRZY PIĘTRA TEGO SAMEGO PYTANIA ==================
 *
 * Historia jest tu wartościowsza od kodu, bo pokazuje, jak jedna klasa wędruje
 * o piętro w górę za każdym razem, gdy zamknie się piętro niższe:
 *
 *   1. „czy funkcja podmieniająca sterownik istnieje i liczy dobrze"
 *      → `tests/Unit/BlokadaWysylkiTest.php`, zamknięte dawno;
 *   2. „czy `boot()` tę funkcję WOŁA" — runda 7 (R7-3) zmierzyła, że nikt
 *      nie pytał: `return;` na wejściu mechanizmu zostawiał suitę ZIELONĄ
 *      (267 passed). Zamknięte testem wołającym `boot()`;
 *   3. **„czy framework WOŁA `boot()`"** — runda 8 (R8-2) zmierzyła, że i tego
 *      nikt nie pytał, bo test BUDOWAŁ providera ręcznie
 *      (`new AppServiceProvider($this->app)`). Konstrukcja pod ręką jest
 *      STRUKTURALNIE niezdolna zobaczyć, czy framework ładuje ten provider.
 *
 * Pomiar R8-2 (odtworzony u siebie 18.08, `bootstrap/providers.php` → `return []`):
 *
 *     realny start:  provider=NIE  mail=smtp     ← BLOKADA §10 MARTWA
 *     BlokadaWysylkiTest → 2 passed
 *     pełna suita        → 289 passed
 *
 * ================== DLACZEGO OSOBNY PROCES, A NIE `$this->app` ==================
 *
 * Bo `phpunit.xml` wymusza `MAIL_MAILER=array` (wiersz 54), a `array` jest
 * sterownikiem NIEWYSYŁAJĄCYM — więc gałąź podmieniająca w mechanizmie nigdy
 * nie wchodzi i w aplikacji testowej nie ma czego mierzyć. Podniesienie
 * `mail.default` przez `config([...])` po starcie i ręczne zawołanie `boot()`
 * to dokładnie ta konstrukcja pod ręką, którą R8-2 nazywa wadą.
 *
 * Pytamy więc REALNEJ aplikacji: pełny bootstrap jądra w OSOBNYM PROCESIE,
 * ze sterownikiem WYSYŁAJĄCYM w środowisku. To jest ten sam ruch, który
 * `ZasiegUniewaznieniaTest` wykonuje dla middleware przez `gatherRouteMiddleware()`
 * — czytamy stan, który złożył framework, zamiast składać go samemu.
 */

/**
 * Stan REALNEJ aplikacji po pełnym bootstrapie frameworka, zmierzony w osobnym procesie.
 *
 * @param  array<string, string>  $srodowisko
 * @return array{provider: string, mail: string, blokada: string, surowe: string}
 */
function stanRealnegoStartu(array $srodowisko): array
{
    $korzen = base_path();

    // Sonda pisana ZA KAŻDYM RAZEM, nie trzymana w repozytorium: gdyby leżała
    // obok jako plik, mogłaby rozjechać się z tym, co asercje niżej twierdzą,
    // że mierzy — i nikt by tego nie zauważył.
    $sonda = <<<PHP
    <?php
    require '{$korzen}/vendor/autoload.php';
    \$app = require '{$korzen}/bootstrap/app.php';
    \$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
    printf(
        'provider=%s mail=%s blokada=%s',
        \$app->getProvider(App\\Providers\\AppServiceProvider::class) !== null ? 'TAK' : 'NIE',
        (string) config('mail.default'),
        var_export(config('gabinet.blokada_wysylki'), true)
    );
    PHP;

    $plik = (string) tempnam(sys_get_temp_dir(), 'blokada-sonda').'.php';
    file_put_contents($plik, $sonda);

    $przedrostek = '';

    foreach ($srodowisko as $klucz => $wartosc) {
        $przedrostek .= $klucz.'='.escapeshellarg($wartosc).' ';
    }

    $wyjscie = [];
    $kod = 0;
    exec($przedrostek.'php '.escapeshellarg($plik).' 2>&1', $wyjscie, $kod);
    @unlink($plik);

    $surowe = implode("\n", $wyjscie);

    // ⛔ PUSTKA TO BŁĄD, NIE ZERO. Gdyby `exec` był wyłączony albo sonda nie
    // wystartowała, wszystkie asercje niżej dostałyby ten sam pusty napis
    // i mogłyby przejść „bo nic nie zaprzecza". Zatrzymujemy się TUTAJ.
    expect($kod)->toBe(0, sprintf(
        "Sonda realnego startu nie wykonała się (kod %d). Wyjście:\n%s\n".
        'Bez niej ten plik NIE MIERZY NICZEGO — a jego zielone znaczyłoby '.
        '„nie umiem zapytać", nie „blokada działa".',
        $kod,
        $surowe
    ));

    expect(preg_match('/provider=(TAK|NIE) mail=(\S+) blokada=(\S+)/', $surowe, $t))->toBe(1, sprintf(
        "Sonda odpowiedziała w nierozpoznanym kształcie:\n%s",
        $surowe
    ));

    return [
        'provider' => $t[1] ?? '?',
        'mail' => $t[2] ?? '?',
        'blokada' => $t[3] ?? '?',
        'surowe' => $surowe,
    ];
}

// ---------------------------------------------------------------------------
// R8-2 — WPIĘCIE WE FRAMEWORK, nie konstrukcja pod ręką
// ---------------------------------------------------------------------------

it('R8-2: blokada §10 działa w REALNEJ aplikacji — provider załadowany przez framework', function (): void {
    // Sterownik WYSYŁAJĄCY w środowisku: bez niego mechanizm nie ma czego
    // podmieniać i test potwierdzałby konfigurację, nie działanie (R7-3).
    $stan = stanRealnegoStartu([
        'MAIL_MAILER' => 'smtp',
        'GABINET_BLOKADA_WYSYLKI' => 'true',
    ]);

    // ODCZYT BAZOWY: blokada naprawdę jest włączona w mierzonym procesie.
    expect($stan['blokada'])->toBe('true', sprintf(
        "W mierzonym procesie blokada NIE jest włączona (%s) — test mierzyłby świat,\n".
        'w którym podmiana i tak nie musi zajść. Wyjście sondy: %s',
        $stan['blokada'],
        $stan['surowe']
    ));

    // (1) Cykl życia: czy framework W OGÓLE ładuje ten provider.
    expect($stan['provider'])->toBe('TAK',
        'Framework NIE ŁADUJE `AppServiceProvider` — `bootstrap/providers.php` go nie '.
        'wymienia. Metoda podmieniająca sterownik może być doskonała; nikt jej nie wywoła (R8-2).'
    );

    // (2) Skutek: co realnie stoi w konfiguracji poczty po starcie.
    expect($stan['mail'])->toBe('log', sprintf(
        "BLOKADA WYSYŁKI MARTWA W REALNEJ APLIKACJI: po pełnym starcie frameworka\n".
        "sterownik poczty to `%s`, mimo włączonej blokady. Staging z konfiguracją\n".
        "skopiowaną z produkcji wysyła wtedy przypomnienia na PRAWDZIWE adresy\n".
        'pacjentów (CLAUDE.md §10). Wyjście sondy: %s',
        $stan['mail'],
        $stan['surowe']
    ));
});

it('R8-2 KIERUNEK ODWROTNY: przy WYŁĄCZONEJ blokadzie sterownik NIE jest podmieniany', function (): void {
    // Dwie rzeczy naraz, i obie są potrzebne:
    //
    //   · MERYTORYCZNIE — mechanizm podmieniający ZAWSZE uniemożliwiałby wysyłkę
    //     na produkcji, więc „po starcie jest log" to za mało;
    //   · JAKO KONTROLA PRZYRZĄDU — gdyby sonda wypisywała stałą albo czytała
    //     nie ten proces, oba przebiegi dałyby ten sam wynik. Różnica między
    //     nimi jest jedynym dowodem, że sonda naprawdę patrzy na skutek.
    $stan = stanRealnegoStartu([
        'MAIL_MAILER' => 'smtp',
        'GABINET_BLOKADA_WYSYLKI' => 'false',
    ]);

    expect($stan['blokada'])->toBe('false',
        'Nie udało się wyłączyć blokady w mierzonym procesie — kontrola przyrządu mierzy pustkę.');

    expect($stan['mail'])->toBe('smtp', sprintf(
        "Blokada podmienia sterownik MIMO WYŁĄCZENIA (`%s`). Na produkcji oznacza to\n".
        'pocztę, która nigdy nie wychodzi. Wyjście sondy: %s',
        $stan['mail'],
        $stan['surowe']
    ));
});

// ---------------------------------------------------------------------------
// R7-3 — piętro niżej: `boot()` woła mechanizm
// ---------------------------------------------------------------------------

it('R7-3: `boot()` woła mechanizm blokady — piętro niżej niż R8-2', function (): void {
    // Ten test ZOSTAJE mimo R8-2, i to jest decyzja, nie zaniedbanie.
    //
    // Test wyżej zapala się na obu sposobach zabicia mechanizmu naraz —
    // wyrejestrowaniu providera ORAZ `return;` w metodzie — więc sam nie
    // rozróżnia PRZYCZYNY. Ten rozróżnia: gdy oba są czerwone, wina jest
    // w metodzie; gdy czerwony jest tylko tamten, wina jest w rejestracji.
    //
    // Dwie wartości zamiast jednej to cała różnica między „coś jest zepsute"
    // a „wiem co" — i dokładnie tego wymaga tu reguła o gałęzi zdegenerowanej.
    config(['gabinet.blokada_wysylki' => true, 'mail.default' => 'smtp']);

    expect(config('mail.default'))->toBe('smtp',
        'Nie udało się ustawić sterownika wysyłającego — test mierzyłby stan, '.
        'w którym blokada i tak nie musi działać.');

    (new AppServiceProvider($this->app))->boot();

    expect(config('mail.default'))->toBe('log',
        '`boot()` NIE WOŁA mechanizmu blokady (albo mechanizm nie podmienia). '.
        'To piętro R7-3 — niższe niż wyrejestrowanie providera (R8-2).');
});
