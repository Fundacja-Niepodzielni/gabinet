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

// ---------------------------------------------------------------------------
// KOMPOZYTY ROZWIJAJĄ SIĘ W TOKENIE — zmierzone na żywym realmie
// ---------------------------------------------------------------------------

it('nie wpuszcza markera technicznego do uprawnień', function (): void {
    // Access token koordynatora niesie `koordynator` ORAZ `wymaga-2fa`,
    // obok ról wbudowanych Keycloaka. Autoryzować ma WYŁĄCZNIE `koordynator`.
    $zTokenu = [
        'koordynator',
        'wymaga-2fa',
        'offline_access',
        'uma_authorization',
        'default-roles-niepodzielni',
    ];

    // Komunikat NIE jest ozdobą: `perturbacje.sh` przypisuje czerwień przyczynie
    // przez `grep` w wyjściu, a bez komunikatu jedyne, co tu pada, to „Failed
    // asserting that two arrays are identical" — zdanie pasujące do dowolnej
    // awarii. Wywołanie musiało więc szukać nazwy testu, czyli napisu obecnego
    // także w przebiegu ZIELONYM (R7-8). Naprawa klasy zaczyna się tutaj.
    expect(Bramki::roleAutoryzujace($zTokenu))->toBe(['koordynator'],
        'Marker techniczny AUTORYZUJE — biala lista rol nie odsiewa markerow ani rol wbudowanych Keycloaka.')
        ->and(Bramki::markery($zTokenu))->toBe(['wymaga-2fa'])
        ->and(Bramki::wymaga2fa($zTokenu))->toBeTrue()
        // Uprawnienia takie same, jak przy samym `koordynator`.
        ->and(Bramki::dlaRol($zTokenu))->toBe(Bramki::dlaRol(['koordynator']));
});

it('nie otwiera NICZEGO kontu, które ma wyłącznie role techniczne', function (): void {
    // Konto założone samodzielnie: bez roli merytorycznej. To POPRAWNY stan
    // (kontrakt §2), a nie błąd logowania — ale zero uprawnień.
    $techniczne = ['offline_access', 'uma_authorization', 'default-roles-niepodzielni', 'wymaga-2fa'];

    expect(Bramki::roleAutoryzujace($techniczne))->toBe([])
        ->and(Bramki::dlaRol($techniczne))->each->toBeFalse();

    foreach (array_keys(Bramki::mapa()) as $bramka) {
        expect(Bramki::pozwala($techniczne, $bramka))->toBeFalse();
    }
});

it('nie autoryzuje markerem, NAWET gdyby ktoś wpisał go do mapy bramek', function (): void {
    // Perturbacja wbudowana w test: podmieniamy konfigurację tak, jakby ktoś
    // przez pomyłkę dopisał marker do listy ról otwierających bramkę.
    // Biała lista ma to zatrzymać KONSTRUKCYJNIE, a nie polegać na tym,
    // że nikt się nie pomyli.
    //
    // U-4 z rundy 3: pierwsza wersja pisała `config(['konta.bramki.panel.koordynacji' => …])`.
    // Nazwa bramki SAMA ZAWIERA KROPKĘ, więc Laravel utworzył NOWY zagnieżdżony
    // klucz `bramki → panel → koordynacji`, a prawdziwy wpis `'panel.koordynacji'`
    // został nietknięty. Test przechodził, nie zmutowawszy niczego — czyli
    // niczego nie dowodził. Podmieniamy więc CAŁĄ mapę i sprawdzamy, że
    // podmiana naprawdę weszła w życie, zanim cokolwiek asertujemy.
    $mapa = Bramki::mapa();
    $mapa['panel.koordynacji'] = ['wymaga-2fa'];
    config(['konta.bramki' => $mapa]);

    // Dowód mutacji — bez niego „biała lista zadziałała" bywa nieodróżnialne
    // od „mutacja nigdy nie weszła".
    expect(Bramki::mapa()['panel.koordynacji'])->toBe(['wymaga-2fa']);

    expect(Bramki::pozwala(['wymaga-2fa'], 'panel.koordynacji'))->toBeFalse()
        ->and(Bramki::dlaRol(['wymaga-2fa'])['panel.koordynacji'])->toBeFalse();

    // Kierunek odwrotny: mapa nadal DZIAŁA dla roli z białej listy. Inaczej
    // „false" powyżej mogłoby wynikać z rozwalonej konfiguracji, nie z reguły.
    $mapa['panel.koordynacji'] = ['koordynator', 'wymaga-2fa'];
    config(['konta.bramki' => $mapa]);

    expect(Bramki::pozwala(['koordynator'], 'panel.koordynacji'))->toBeTrue()
        ->and(Bramki::pozwala(['wymaga-2fa'], 'panel.koordynacji'))->toBeFalse();
});

it('odsiewa role spoza białej listy, także te wymyślone', function (): void {
    // Realm ma dokładnie siedem ról merytorycznych. Cokolwiek innego —
    // nowa rola dodana w IdP, literówka, rola z innego systemu — nie
    // autoryzuje, dopóki nie wejdzie świadomie do białej listy.
    expect(Bramki::roleAutoryzujace(['superadmin', 'psycholog', 'admin']))
        ->toBe(['psycholog']);
});

it('zna wszystkie siedem ról merytorycznych realmu', function (): void {
    // Kontrakt §2. Gdyby lista się rozjechała z realmem, personel z poprawną
    // rolą przestałby mieć dostęp — po cichu.
    $biala = config('konta.role_autoryzujace');

    expect($biala)->toHaveCount(7)
        ->and($biala)->toContain('pacjent', 'psycholog', 'prowadzacy', 'wolontariusz', 'koordynator', 'redaktor', 'admin-fundacja')
        // Marker NIE MOŻE trafić na białą listę.
        ->and($biala)->not->toContain('wymaga-2fa');
});
