<?php

declare(strict_types=1);

/**
 * Regresja na zasadę „sekrety nigdy w plikach" (WYTYCZNE-PRACY.md §7).
 *
 * Gitleaks w CI łapie sekret o rozpoznawalnym kształcie. Ten test łapie coś,
 * czego gitleaks nie widzi: wpisanie wartości do `.env.example`, czyli do
 * pliku, który JEST w repozytorium i który każdy kopiuje jako punkt wyjścia.
 */

/** Korzeń repozytorium: backend/tests/Feature → backend/tests → backend → repo. */
function korzenRepozytorium(): string
{
    return dirname(__DIR__, 3);
}

/** @return array<string, string> */
function wczytajWzorzecSrodowiska(): array
{
    $tresc = file_get_contents(korzenRepozytorium().'/.env.example');

    if ($tresc === false) {
        throw new RuntimeException('Nie da się odczytać .env.example');
    }

    $pary = [];

    foreach (preg_split('/\R/', $tresc) ?: [] as $linia) {
        $linia = trim($linia);

        if ($linia === '' || str_starts_with($linia, '#') || ! str_contains($linia, '=')) {
            continue;
        }

        [$klucz, $wartosc] = explode('=', $linia, 2);
        $pary[trim($klucz)] = trim($wartosc);
    }

    return $pary;
}

it('trzyma .env.example bez ani jednej wartości sekretu', function (): void {
    $wzorzec = wczytajWzorzecSrodowiska();

    $sekrety = [
        'APP_KEY',
        'DB_PASSWORD',
        'KEYCLOAK_CLIENT_SECRET',
        'KEYCLOAK_ADMIN_CLIENT_ID',
        'KEYCLOAK_ADMIN_CLIENT_SECRET',
        'STRIPE_FUNDACJA_KEY',
        'STRIPE_FUNDACJA_SECRET',
        'STRIPE_FUNDACJA_WEBHOOK_SECRET',
        'STRIPE_KOMERCJA_KEY',
        'STRIPE_KOMERCJA_SECRET',
        'STRIPE_KOMERCJA_WEBHOOK_SECRET',
        'SMSAPI_TOKEN',
        'MAIL_PASSWORD',
        'WIDEO_API_KLUCZ',
    ];

    foreach ($sekrety as $klucz) {
        // Klucz MUSI istnieć — inaczej test milczy o brakującej zmiennej...
        expect($wzorzec)->toHaveKey($klucz);
        // ...i MUSI być pusty.
        expect($wzorzec[$klucz])->toBe('', "Zmienna {$klucz} ma wartość w .env.example");
    }
});

it('wymienia oba konta Stripe osobno', function (): void {
    // CLAUDE.md §3: fundacyjne i komercyjne to dwa niezależne konta —
    // osobne klucze, osobne webhooki, osobna rekoncyliacja.
    $wzorzec = wczytajWzorzecSrodowiska();

    $webhooki = ['STRIPE_FUNDACJA_WEBHOOK_SECRET', 'STRIPE_KOMERCJA_WEBHOOK_SECRET'];

    expect(array_intersect_key($wzorzec, array_flip($webhooki)))->toHaveCount(2);
});

it('ignoruje .env w gitignore', function (): void {
    $gitignore = file_get_contents(korzenRepozytorium().'/.gitignore');

    expect($gitignore)->not->toBeFalse();

    $linie = array_map(trim(...), preg_split('/\R/', (string) $gitignore) ?: []);

    expect($linie)->toContain('.env');
});
