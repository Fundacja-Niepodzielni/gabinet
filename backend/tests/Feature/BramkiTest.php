<?php

declare(strict_types=1);

use App\Tozsamosc\Bramki;
use App\Wsparcie\Typy;

/**
 * „IdP mówi kim jesteś, aplikacja decyduje co możesz" (kontrakt §2).
 * Tu sprawdzamy drugą część tego zdania.
 */
it('czyta role wyłącznie z realm_access.roles', function (): void {
    $claims = ['realm_access' => ['roles' => ['psycholog', 'offline_access']]];

    expect(Bramki::roleZAccessTokenu($claims))->toBe(['psycholog', 'offline_access']);
});

it('traktuje brak ról jako poprawny stan konta, nie jako błąd', function (array $claims): void {
    // Kontrakt §2: konto założone samodzielnie ma WYŁĄCZNIE role domyślne.
    // „Nie buduj logiki na założeniu »każdy zalogowany to pacjent«".
    expect(Bramki::roleZAccessTokenu(Typy::mapa($claims)))->toBe([])
        ->and(Bramki::dlaRol([]))->each->toBeFalse();
})->with([
    'brak claimu' => [[]],
    'pusta lista' => [['realm_access' => ['roles' => []]]],
    'zły kształt' => [['realm_access' => 'psycholog']],
]);

it('otwiera panel specjalisty psychologowi, a panel koordynacji już nie', function (): void {
    $role = ['psycholog'];

    expect(Bramki::pozwala($role, 'panel.specjalisty'))->toBeTrue()
        ->and(Bramki::pozwala($role, 'panel.koordynacji'))->toBeFalse()
        ->and(Bramki::pozwala($role, 'rozliczenia.akceptuj'))->toBeFalse()
        ->and(Bramki::pozwala($role, 'dziennik.zapisz'))->toBeFalse();
});

it('nie wpuszcza pacjenta do żadnego panelu personelu', function (string $bramka): void {
    expect(Bramki::pozwala(['pacjent'], $bramka))->toBeFalse();
})->with(['panel.specjalisty', 'panel.koordynacji', 'rozliczenia.akceptuj', 'dziennik.zapisz']);

it('otwiera koordynatorowi rozliczenia i dziennik decyzji', function (): void {
    $role = ['koordynator'];

    expect(Bramki::pozwala($role, 'panel.koordynacji'))->toBeTrue()
        ->and(Bramki::pozwala($role, 'rozliczenia.akceptuj'))->toBeTrue()
        ->and(Bramki::pozwala($role, 'dziennik.zapisz'))->toBeTrue();
});

it('odmawia przy nieznanej nazwie bramki', function (): void {
    // Literówka w nazwie bramki nie może przypadkiem otworzyć zasobu.
    expect(Bramki::pozwala(['admin-fundacja'], 'panel.koordynacj'))->toBeFalse()
        ->and(Bramki::pozwala(['admin-fundacja'], ''))->toBeFalse();
});

it('nie zna roli, której nie ma w realmie', function (): void {
    // Realm ma dokładnie 7 ról merytorycznych. Wymyślona rola nie otwiera nic.
    expect(Bramki::pozwala(['superadmin'], 'panel.koordynacji'))->toBeFalse();
});
