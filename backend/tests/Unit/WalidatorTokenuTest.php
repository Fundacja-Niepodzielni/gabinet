<?php

declare(strict_types=1);

use App\Tozsamosc\WalidatorTokenu;
use Tests\Wsparcie\FabrykaTokenow;

/**
 * Kontrakt `konta/docs/INTEGRACJA-KONTRAKT.md` §4.4 wymienia SIEDEM kontroli.
 * Każda ma tu test negatywny, który sprawdza nie tylko „token odrzucony",
 * ale też że padła DOKŁADNIE JEDNA, właściwa kontrola. Bez tego drugiego
 * warunku test negatywny przechodzi także wtedy, gdy walidacja jest zepsuta
 * w całości — a wtedy nie dowodzi niczego.
 */
/**
 * @param  array<string, mixed>  $nadpisania
 * @return array<string, mixed>
 */
function opcjeAccess(array $nadpisania = []): array
{
    return array_merge([
        'issuer' => 'https://idp.test/realms/niepodzielni',
        'jwks' => FabrykaTokenow::jwks(),
        'audience' => 'gabinet',
        'typ' => 'Bearer',
        'tolerancja' => 30,
    ], $nadpisania);
}

it('przyjmuje poprawny access token i zalicza wszystkie kontrole', function (): void {
    $token = FabrykaTokenow::podpisz(FabrykaTokenow::claimsAccess());

    $wynik = WalidatorTokenu::sprawdz($token, opcjeAccess());

    expect($wynik['ok'])->toBeTrue()
        ->and($wynik['nieudane'])->toBe([])
        ->and($wynik['kontrole'])->toBe([
            'format' => 'ok', 'alg' => 'ok', 'kid' => 'ok', 'signature' => 'ok',
            'iss' => 'ok', 'exp' => 'ok', 'iat' => 'ok', 'typ' => 'ok', 'aud' => 'ok',
        ]);
});

it('odrzuca token wystawiony dla innego klienta — i to WYŁĄCZNIE z powodu aud', function (): void {
    // Dokładnie ta sytuacja z kontraktu: token frontendu podstawiony do API.
    // `azp` mówi, kto poprosił o token — i NIE zastępuje `aud`.
    $token = FabrykaTokenow::podpisz(FabrykaTokenow::claimsAccess([
        'aud' => ['account'],
        'azp' => 'psychon-web',
    ]));

    $wynik = WalidatorTokenu::sprawdz($token, opcjeAccess());

    expect($wynik['ok'])->toBeFalse()
        ->and($wynik['nieudane'])->toBe(['aud'])
        // Dowód, że odrzucenie nie wynikło z rozsypanej walidacji:
        ->and($wynik['kontrole']['signature'])->toBe('ok')
        ->and($wynik['kontrole']['iss'])->toBe('ok')
        ->and($wynik['kontrole']['exp'])->toBe('ok')
        ->and($wynik['kontrole']['typ'])->toBe('ok');
});

it('odrzuca ID token podstawiony w miejsce access tokenu', function (): void {
    // `aud` ID tokenu to client_id, więc kontrola audiencji by go przepuściła —
    // odsiewa go dopiero `typ`.
    $token = FabrykaTokenow::podpisz(FabrykaTokenow::claimsId());

    $wynik = WalidatorTokenu::sprawdz($token, opcjeAccess());

    expect($wynik['ok'])->toBeFalse()
        ->and($wynik['nieudane'])->toBe(['typ'])
        ->and($wynik['kontrole']['aud'])->toBe('ok');
});

it('odrzuca token podpisany innym algorytmem niż RS256', function (): void {
    $token = FabrykaTokenow::podpisz(FabrykaTokenow::claimsAccess(), ['alg' => 'HS256']);

    $wynik = WalidatorTokenu::sprawdz($token, opcjeAccess());

    expect($wynik['ok'])->toBeFalse()
        ->and($wynik['kontrole']['alg'])->toBe('fail')
        // Bez poprawnego `alg` nie wolno uznać podpisu za sprawdzony.
        ->and($wynik['kontrole']['signature'])->toBe('fail');
});

it('odrzuca token o nieznanym kid', function (): void {
    $token = FabrykaTokenow::podpisz(FabrykaTokenow::claimsAccess(), ['kid' => 'obcy-kid']);

    $wynik = WalidatorTokenu::sprawdz($token, opcjeAccess());

    expect($wynik['ok'])->toBeFalse()
        ->and($wynik['kontrole']['kid'])->toBe('fail')
        ->and($wynik['kontrole']['signature'])->toBe('fail');
});

it('odrzuca token z podmienionym ładunkiem (zepsuty podpis)', function (): void {
    $token = FabrykaTokenow::podpisz(FabrykaTokenow::claimsAccess());
    [$naglowek, , $podpis] = explode('.', $token);

    // Napastnik dopisuje sobie rolę administratora — podpis musi to wychwycić.
    $podmieniony = FabrykaTokenow::claimsAccess([
        'realm_access' => ['roles' => ['psycholog', 'admin-fundacja']],
    ]);

    $nowyPayload = rtrim(strtr(base64_encode((string) json_encode($podmieniony)), '+/', '-_'), '=');

    $wynik = WalidatorTokenu::sprawdz("{$naglowek}.{$nowyPayload}.{$podpis}", opcjeAccess());

    expect($wynik['ok'])->toBeFalse()
        ->and($wynik['nieudane'])->toBe(['signature']);
});

it('odrzuca token od obcego wystawcy', function (): void {
    // Porównanie jest DOKŁADNE, nie „zaczyna się od" — dlatego adres, który
    // ma poprawny przedrostek, też musi wypaść.
    $token = FabrykaTokenow::podpisz(FabrykaTokenow::claimsAccess([
        'iss' => 'https://idp.test/realms/niepodzielni-podrobka',
    ]));

    $wynik = WalidatorTokenu::sprawdz($token, opcjeAccess());

    expect($wynik['ok'])->toBeFalse()
        ->and($wynik['nieudane'])->toBe(['iss']);
});

it('odrzuca token wygasły poza tolerancją zegara i przyjmuje w jej granicach', function (): void {
    $wygasly = FabrykaTokenow::podpisz(FabrykaTokenow::claimsAccess([
        'iat' => time() - 3600,
        'exp' => time() - 3600,
    ]));

    expect(WalidatorTokenu::sprawdz($wygasly, opcjeAccess())['nieudane'])->toBe(['exp']);

    // NEGATYW granicy: token wygasły 10 s temu przy tolerancji 30 s ma przejść.
    $swiezoWygasly = FabrykaTokenow::podpisz(FabrykaTokenow::claimsAccess([
        'exp' => time() - 10,
    ]));

    expect(WalidatorTokenu::sprawdz($swiezoWygasly, opcjeAccess())['ok'])->toBeTrue();
});

it('odrzuca ID token z niezgodnym nonce', function (): void {
    $token = FabrykaTokenow::podpisz(FabrykaTokenow::claimsId(['nonce' => 'cudzy-nonce']));

    $wynik = WalidatorTokenu::sprawdz($token, opcjeAccess([
        'audience' => 'gabinet',
        'typ' => 'ID',
        'nonce' => 'nonce-testowy',
    ]));

    expect($wynik['ok'])->toBeFalse()
        ->and($wynik['nieudane'])->toBe(['nonce']);
});

it('odrzuca śmieć zamiast tokenu, nie wywracając się', function (string $smiec): void {
    $wynik = WalidatorTokenu::sprawdz($smiec, opcjeAccess());

    expect($wynik['ok'])->toBeFalse()
        ->and($wynik['nieudane'])->toBe(['format']);
})->with(['', 'nie-jest-jwt', 'a.b', 'a.b.c.d', '...']);

it('normalizuje aud podane jako pojedynczy napis', function (): void {
    // Kontrakt: „`aud` bywa stringiem, nie tablicą. Znormalizuj przed sprawdzeniem."
    $token = FabrykaTokenow::podpisz(FabrykaTokenow::claimsAccess(['aud' => 'gabinet']));

    expect(WalidatorTokenu::sprawdz($token, opcjeAccess())['ok'])->toBeTrue();
});

// ---------------------------------------------------------------------------
// FAIL-CLOSED — kontrola, która nie ma z czym porównać, MA PAŚĆ
// ---------------------------------------------------------------------------

it('odrzuca token, gdy oczekiwany nonce jest pusty albo nieznany', function (mixed $oczekiwany): void {
    // Znalezione przez niezależnego weryfikatora: wersja pierwsza POMIJAŁA
    // kontrolę nonce przy `null`, więc sesja bez nonce wpuszczała token
    // z dowolnym nonce i kończyła się stanem „zalogowany".
    $token = FabrykaTokenow::podpisz(FabrykaTokenow::claimsId(['nonce' => 'nonce-NAPASTNIKA']));

    $wynik = WalidatorTokenu::sprawdz($token, opcjeAccess([
        'typ' => 'ID',
        'nonce' => $oczekiwany,
    ]));

    expect($wynik['ok'])->toBeFalse()
        ->and($wynik['kontrole']['nonce'])->toBe('fail')
        // Dowód, że padła DOKŁADNIE ta kontrola, a nie cała walidacja.
        ->and($wynik['kontrole']['signature'])->toBe('ok')
        ->and($wynik['kontrole']['iss'])->toBe('ok');
})->with([
    'null' => [null],
    'pusty napis' => [''],
    'liczba zamiast napisu' => [0],
    'tablica' => [[]],
]);

it('przyjmuje token dopiero przy zgodnym, niepustym nonce', function (): void {
    $token = FabrykaTokenow::podpisz(FabrykaTokenow::claimsId(['nonce' => 'nonce-testowy']));

    $wynik = WalidatorTokenu::sprawdz($token, opcjeAccess([
        'typ' => 'ID',
        'nonce' => 'nonce-testowy',
    ]));

    expect($wynik['kontrole']['nonce'])->toBe('ok');
});
