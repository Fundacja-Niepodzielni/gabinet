<?php

declare(strict_types=1);

use App\Wsparcie\Typy;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Crypt;
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
 * ZAKRES TEGO PLIKU — czytaj, zanim uznasz go za dowód (D-2026-08-08-25).
 *
 * Cztery z pięciu zapisów to FIXTURE TESTU: plik wkłada dane do magazynu
 * i sam ich potem szuka. To kształt (c) z przeglądu C1 — kontrola sprawdza
 * stan, który sama wyprodukowała, więc dowodzi głównie, że `Crypt` działa.
 *
 * Asercja o KODZIE PRODUKCYJNYM — czy kontroler naprawdę szyfruje ID token —
 * mieszka w `OdebranieRoliTest` („zapisuje ID token do sesji ZASZYFROWANY")
 * i tam ma perturbację dwunożną. Nie szukaj jej tutaj.
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

/**
 * Surowa zawartość magazynu sesji ROZSZERZONA o zdekodowane ładunki JWT.
 *
 * Refinement B7 z huba — najważniejsza część tego pliku. Skaner szukający
 * e-maila TEKSTEM JAWNYM mija go, choć e-mail w magazynie JEST: claimy w ID
 * tokenie są zakodowane base64url, więc ciąg `pacjent@przyklad.test` nie
 * występuje w treści dosłownie. Test przechodził i deklarował klasę „brak
 * danych osobowych w sesji", a mierzył klasę WĘŻSZĄ: „brak danych osobowych
 * zapisanych wprost".
 *
 * Dlatego doklejamy zdekodowane ładunki wszystkiego, co wygląda na JWT.
 * To ta sama zasada, co przy `RetencjaTest` i kontroli §2: pytanie ma dotyczyć
 * DANYCH, nie ich reprezentacji — bo reprezentację wybiera ten, kto pisze kod.
 */
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

    return $tresc.zdekodowaneLadunki($tresc);
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
    /** @var Session $magazyn */
    $magazyn = app('session')->driver('redis');
    $magazyn->setId(bin2hex(random_bytes(20)));

    // Zapisujemy DOKŁADNIE to, co zapisuje kontroler logowania.
    $magazyn->put('konta', [
        'sub' => 'sub-testowy',
        'email' => $email,
        'login' => $login,
        // Tak, jak robi to kontroler: ID token szyfrowany JAWNIE, niezależnie
        // od `session.encrypt` (refinement B7).
        'id_token' => Crypt::encryptString($idToken),
    ]);
    $magazyn->save();

    $surowe = surowaZawartoscSesji();

    expect($surowe)->not->toBe('', 'Magazyn sesji jest pusty — test nie mierzy tego, co powinien.');

    // Szukamy WARTOŚCI. Gdyby ktoś przemianował klucz `email` na `kontakt`,
    // ten test nadal łapie wyciek — bo szuka ciągu, nie nazwy.
    expect(str_contains($surowe, $email))->toBeFalse('E-mail pacjenta odczytywalny w magazynie sesji.')
        ->and(str_contains($surowe, $login))->toBeFalse('Login pacjenta odczytywalny w magazynie sesji.')
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

it('ma szyfrowanie sesji włączone DOMYŚLNIE — czytane z TREŚCI pliku, nie przez env()', function (): void {
    // V-3 z rundy 5, reguła C1. Poprzednia wersja robiła
    // `require config/session.php` i porównywała z `config('session.encrypt')`.
    // To NIE są dwa sygnały — `require` ponownie wykonuje `env('SESSION_ENCRYPT')`,
    // więc obie strony jadą JEDNYM mechanizmem. Weryfikator zmierzył: przy
    // domyślnej ustawionej na `false` i `SESSION_ENCRYPT=true` w `.env` test
    // przechodził, a na każdym środowisku bez tej zmiennej — w tym na `.env`
    // dewelopera — e-mail pacjenta lądował w Redisie jawnie (RODO art. 9).
    //
    // Teraz czytamy TREŚĆ PLIKU. To ścieżka niezależna od środowiska: żadna
    // wartość w `.env` nie zmieni tego, co jest zapisane w kodzie.
    $tresc = (string) file_get_contents(base_path('config/session.php'));

    // `toContain()` w Pest traktuje kolejne argumenty jako KOLEJNE IGŁY,
    // nie jako komunikat — dlatego jawne `str_contains` z opisem błędu.
    expect(str_contains($tresc, "'encrypt' => env('SESSION_ENCRYPT', true)"))->toBeTrue(
        'Wartość DOMYŚLNA szyfrowania sesji nie jest `true` w kodzie — środowisko bez tej zmiennej zapisuje sesję jawnie.'
    );

    // Drugi, osobny sygnał: bieżąca konfiguracja też ma być włączona. Tu
    // ŚWIADOMIE dopuszczamy zależność od `.env` — to jest inne pytanie
    // („czy TU jest bezpiecznie") niż poprzednie („czy jest bezpiecznie
    // DOMYŚLNIE"). Rozdzielenie tych dwóch pytań jest całym sensem naprawy.
    expect(config('session.encrypt'))->toBeTrue();
});

it('SKANER DEKODUJE base64url — inaczej mierzy węższą klasę, niż deklaruje', function (): void {
    // Refinement B7 z huba, przełożony na dowód. Bez dekodowania ładunków JWT
    // test „w sesji nie ma e-maila" przechodzi także wtedy, gdy e-mail
    // TAM JEST — tylko zakodowany base64url. Deklarowaliśmy klasę „brak danych
    // osobowych", a mierzyliśmy „brak danych zapisanych wprost".
    //
    // Ten test dowodzi, że skaner widzi wnętrze tokenu: podkładamy ID token
    // JAWNIE (tak, jak było przed naprawą) i wymagamy, żeby e-mail z jego
    // wnętrza dał się znaleźć.
    $email = 'wykrywalny-'.bin2hex(random_bytes(6)).'@przyklad.test';

    $idToken = FabrykaTokenow::podpisz(FabrykaTokenow::claimsId(['email' => $email]));

    // Kontrola wstępna: e-mail NIE występuje w tokenie tekstem jawnym.
    expect(str_contains($idToken, $email))->toBeFalse(
        'E-mail jest w tokenie dosłownie — test nie mierzy dekodowania.'
    );

    /** @var Session $magazyn */
    $magazyn = app('session')->driver('redis');
    $magazyn->setId(bin2hex(random_bytes(20)));
    $magazyn->put('konta', ['sub' => 'sub-testowy', 'id_token' => $idToken]);
    $magazyn->save();

    // Przy WŁĄCZONYM szyfrowaniu sesji nawet jawny token jest nieczytelny,
    // więc dla tego dowodu czytamy zawartość z pominięciem tej warstwy.
    $bezSzyfrowania = zdekodowaneLadunki($idToken);

    expect(str_contains($bezSzyfrowania, $email))->toBeTrue(
        'Skaner NIE dekoduje ładunków JWT — mierzy węższą klasę, niż deklaruje.'
    );
});

it('nie ujawnia e-maila UKRYTEGO WYŁĄCZNIE wewnątrz ID tokenu', function (): void {
    // Najostrzejsza postać kontroli B7. W sesji NIE MA pola `email` — jedyne
    // miejsce, gdzie adres pacjenta występuje, to claim wewnątrz ID tokenu,
    // zakodowany base64url.
    //
    // Dzięki temu ten test może zapalić się na czerwono TYLKO przez
    // dekodowanie. Perturbacja przywracająca jawny ID token przy wyłączonym
    // szyfrowaniu sesji trafia dokładnie tutaj — i nigdzie indziej.
    $email = 'ukryty-'.bin2hex(random_bytes(6)).'@przyklad.test';

    $idToken = FabrykaTokenow::podpisz(FabrykaTokenow::claimsId(['email' => $email]));

    expect(str_contains($idToken, $email))->toBeFalse('E-mail jest w tokenie dosłownie — nie mierzymy dekodowania.');

    /** @var Session $magazyn */
    $magazyn = app('session')->driver('redis');
    $magazyn->setId(bin2hex(random_bytes(20)));
    $magazyn->put('konta', [
        'sub' => 'sub-testowy',
        'id_token' => Crypt::encryptString($idToken),
    ]);
    $magazyn->save();

    expect(str_contains(surowaZawartoscSesji(), $email))->toBeFalse(
        'E-mail pacjenta odczytywalny z ID tokenu w magazynie sesji.'
    );
});
