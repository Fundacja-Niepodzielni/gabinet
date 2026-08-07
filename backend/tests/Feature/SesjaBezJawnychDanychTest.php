<?php

declare(strict_types=1);

use App\Wsparcie\Typy;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Redis;
use Tests\Wsparcie\FabrykaTokenow;

/**
 * Magazyn sesji nie może trzymać danych osobowych JAWNIE.
 *
 * Kontrola krzyżowa z weryfikacji F1 huba (B7), zmierzona u nas i potwierdzona:
 * sesja Gabinetu trzyma `email`, `preferred_username` oraz CAŁY ID TOKEN
 * (potrzebny do `id_token_hint` przy wylogowaniu), sterownik to `redis`
 * utrwalający dane na dysku, a `SESSION_ENCRYPT` miało domyślnie `false`.
 * E-mail pacjenta leżał więc w magazynie sesji jawnie — w systemie
 * przetwarzającym dane o zdrowiu (RODO art. 9).
 *
 * NAJWAŻNIEJSZE W TYM TEŚCIE: szuka WARTOŚCI, nie NAZW.
 *
 * Asercja „w sesji nie ma klucza `email`" jest bezwartościowa — dane wyciekają
 * pod dowolną nazwą, a nazwę wybiera ten, kto pisze kod. Hub uznał kiedyś taką
 * asercję za zamkniętą i to był błąd. Dlatego wstawiamy ZNANY, unikalny ciąg
 * i sprawdzamy, czy da się go znaleźć w surowej zawartości magazynu.
 */
beforeEach(function (): void {
    udawajIdp();
});

/** Surowa zawartość WSZYSTKICH kluczy sesji w Redisie — bajt w bajt. */
function surowaZawartoscSesji(): string
{
    $polaczenie = Redis::connection(Typy::napis(config('session.connection'), 'default'));
    $prefiks = Typy::napis(config('database.redis.options.prefix'));
    $tresc = '';

    foreach (Typy::mapa($polaczenie->keys('*')) as $klucz) {
        // Nazwa klucza wraca z prefiksem połączenia; do odczytu potrzebna jest
        // postać bez prefiksu, bo klient dokłada go ponownie.
        $bezPrefiksu = $prefiks !== '' && str_starts_with(Typy::napis($klucz), $prefiks)
            ? substr(Typy::napis($klucz), strlen($prefiks))
            : Typy::napis($klucz);

        $tresc .= Typy::napis($polaczenie->get($bezPrefiksu));
    }

    return $tresc;
}

it('nie zapisuje e-maila ani ID tokenu JAWNIE w magazynie sesji', function (): void {
    $email = 'unikalny-pacjent-'.bin2hex(random_bytes(6)).'@przyklad.test';
    $login = 'login-'.bin2hex(random_bytes(6));

    $idToken = FabrykaTokenow::podpisz(FabrykaTokenow::claimsId([
        'email' => $email,
        'preferred_username' => $login,
    ]));

    // Suita biegnie na sterowniku `array` (phpunit.xml), więc `session()`
    // nigdy nie dotknęłaby prawdziwego magazynu — test mierzyłby pustkę
    // i przechodził zawsze. Bierzemy więc sterownik PRODUKCYJNY wprost
    // z menedżera sesji; szyfrowanie stosuje się dokładnie tak, jak
    // w działającej aplikacji.
    /** @var Session $magazyn */
    $magazyn = app('session')->driver('redis');
    $magazyn->setId(bin2hex(random_bytes(20)));

    // Zapisujemy DOKŁADNIE to, co zapisuje kontroler logowania.
    $magazyn->put('konta', [
        'sub' => 'sub-testowy',
        'email' => $email,
        'login' => $login,
        'id_token' => $idToken,
    ]);
    $magazyn->save();

    $surowe = surowaZawartoscSesji();

    expect($surowe)->not->toBe('', 'Magazyn sesji jest pusty — test nie mierzy tego, co powinien.');

    // Szukamy WARTOŚCI. Gdyby ktoś przemianował klucz `email` na `kontakt`,
    // ten test nadal łapie wyciek — bo szuka ciągu, nie nazwy.
    expect(str_contains($surowe, $email))->toBeFalse('E-mail pacjenta leży JAWNIE w magazynie sesji.')
        ->and(str_contains($surowe, $login))->toBeFalse('Login pacjenta leży JAWNIE w magazynie sesji.')
        ->and(str_contains($surowe, $idToken))->toBeFalse('ID token leży JAWNIE w magazynie sesji.');
});

it('mierzy realny magazyn — ten sam ciąg BEZ szyfrowania jest znajdowany', function (): void {
    // Kierunek odwrotny i zarazem dowód, że skaner działa. Bez niego pierwszy
    // test przechodzi również wtedy, gdy `surowaZawartoscSesji()` zwraca
    // cokolwiek, w czym nigdy nic nie znajdziemy — a to najczęstszy sposób,
    // w jaki kontrola oparta o skanowanie po cichu przestaje działać.
    $znacznik = 'kontrolny-ciag-'.bin2hex(random_bytes(6));

    $polaczenie = Redis::connection(Typy::napis(config('session.connection'), 'default'));
    $polaczenie->setex('gabinet:test-jawnosci', 60, $znacznik);

    expect(str_contains(surowaZawartoscSesji(), $znacznik))->toBeTrue(
        'Skaner nie widzi zawartości magazynu — pierwszy test niczego nie dowodzi.'
    );

    $polaczenie->del('gabinet:test-jawnosci');
});

it('ma szyfrowanie sesji włączone DOMYŚLNIE, nie tylko w .env', function (): void {
    // Wartość domyślna jest tym, co działa na środowisku, którego nikt nie
    // skonfigurował. Ustawienie „bezpieczne, o ile ktoś pamiętał" nie jest
    // ustawieniem bezpiecznym.
    $domyslna = Typy::mapa(require base_path('config/session.php'));

    expect(config('session.encrypt'))->toBeTrue()
        ->and($domyslna['encrypt'])->toBeTrue();
});
