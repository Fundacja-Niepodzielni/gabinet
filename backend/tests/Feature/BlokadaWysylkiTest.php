<?php

declare(strict_types=1);
use App\Providers\AppServiceProvider;

/**
 * Blokada wysylki poczty poza produkcja (CLAUDE.md zasada 10) — EGZEKUTOR WPIECIA.
 *
 * Osobny plik od , i to jest cala rzecz:
 * tamten bada CZYSTA FUNKCJE  i robi to
 * dobrze. Ten bada, czy funkcja jest W OGOLE WOLANA przy starcie aplikacji.
 *
 * Runda 7 zmierzyla, ze drugiego pytania nikt nie zadawal:  na wejsciu
 * mechanizmu zostawial suite ZIELONA (267 passed).
 */
// ---------------------------------------------------------------------------
// R7-3 — EGZEKUTOR WPIĘCIA, nie samej funkcji
// ---------------------------------------------------------------------------

it('R7-3: mechanizm blokady jest WPIĘTY w start aplikacji, nie tylko napisany', function (): void {
    // ⛔ ZNALEZISKO RUNDY 7. Weryfikator wstawił `return;` na wejściu
    // `AppServiceProvider::zablokujWysylkePozaProdukcja()` — mechanizm martwy —
    // i zmierzył: `SzkieletTest` 8 passed, PEŁNA SUITA 267 passed.
    // Usunięcie ochrony §10 przeszło niezauważone.
    //
    // DLACZEGO STARA KONTROLA BYŁA PUSTA: `phpunit.xml` wymusza `MAIL_MAILER=array`,
    // a `.env.bramka` ma `log`. Oba to sterowniki NIEWYSYŁAJĄCE, więc gałąź
    // podmieniająca w mechanizmie NIGDY NIE WCHODZI. Test potwierdzał wtedy
    // konfigurację, nie działanie podmiany — klasyczna kontrola dowodząca
    // własności, której jej środowisko nie ma.
    //
    // Ten test stawia sterownik WYSYŁAJĄCY i uruchamia ŚCIEŻKĘ STARTOWĄ.
    // Zapala się na obu sposobach zabicia mechanizmu: `return;` w metodzie
    // ORAZ usunięciu jej wywołania z `boot()`.
    config(['gabinet.blokada_wysylki' => true, 'mail.default' => 'smtp']);

    // ODCZYT BAZOWY: sterownik NAPRAWDĘ jest wysyłający, zanim cokolwiek zmierzymy.
    expect(config('mail.default'))->toBe(
        'smtp',
        'Nie udało się ustawić sterownika wysyłającego — test mierzyłby stan, '.
        'w którym blokada i tak nie musi działać.'
    );

    (new AppServiceProvider($this->app))->boot();

    expect(config('mail.default'))->toBe(
        'log',
        'BLOKADA WYSYŁKI NIE JEST WPIĘTA: po starcie aplikacji sterownik poczty pozostał '.
        '`smtp`. Staging z konfiguracją skopiowaną z produkcji wysyła wtedy przypomnienia '.
        'na PRAWDZIWE adresy pacjentów (CLAUDE.md zasada 10).'
    );
});

it('R7-3 KIERUNEK ODWROTNY: przy WYŁĄCZONEJ blokadzie sterownik NIE jest podmieniany', function (): void {
    // Bez tego „po starcie jest log" przechodzi także dla mechanizmu, który
    // podmienia ZAWSZE — a taki uniemożliwiałby wysyłkę na produkcji.
    config(['gabinet.blokada_wysylki' => false, 'mail.default' => 'smtp']);

    (new AppServiceProvider($this->app))->boot();

    expect(config('mail.default'))->toBe(
        'smtp',
        'Blokada podmienia sterownik MIMO wyłączenia — na produkcji poczta nigdy by nie wyszła.'
    );
});
