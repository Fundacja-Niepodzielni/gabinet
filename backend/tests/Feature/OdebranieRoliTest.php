<?php

declare(strict_types=1);

use App\Tozsamosc\RejestrSesji;
use App\Tozsamosc\SladWylogowania;
use App\Wsparcie\Typy;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Tests\Wsparcie\FabrykaTokenow;

/**
 * Odebranie roli w Keycloaku MUSI zadziałać w Gabinecie.
 *
 * Znalezisko B8 z kontroli krzyżowej weryfikacji F1 huba: role były zapisywane
 * do sesji przy logowaniu i nie zmieniały się przez 120 minut, a sesja
 * odnawiała się ruchem. Dla aktywnego użytkownika odebranie roli **nie
 * działało nigdy**. W systemie, w którym rola otwiera dostęp do kartotek
 * pacjentów, to wada bezpieczeństwa.
 *
 * Decyzja przekrojowa B8 (standard ekosystemu):
 *   · ZMIANA ROLI  → przeliczenie najpóźniej przy odświeżeniu access tokenu,
 *                    czyli w oknie kontraktu (600 s), bez dodatkowych żądań;
 *   · BLOKADA      → back-channel logout po `sid`, natychmiast;
 *   · sub-minuta   → tylko dla konkretnej wrażliwej roli, nigdy globalnie.
 *
 * Testy liczą UPRAWNIENIA (CLAUDE.md §15) — konkretną bramkę, która była
 * otwarta i ma się zamknąć — a nie obecność pola w odpowiedzi.
 */
/**
 * Odpowiedź punktu tokenów, którą test może podmienić w trakcie.
 *
 * `Http::fake()` SCALA atrapy i przy tym samym wzorcu URL wygrywa ta
 * zarejestrowana PIERWSZA — więc ponowne `Http::fake()` w środku testu nie
 * podmieniało odpowiedzi i „odświeżenie" zwracało wciąż stary token z rolą.
 * Test przechodził obok zjawiska, które miał mierzyć. Dlatego atrapa jest
 * jedna, a zmienia się jej ZAWARTOŚĆ.
 */
$GLOBALS['odpowiedz_tokenu'] = [];

/** Czy atrapa IdP ma udawać awarię (500) na WSZYSTKICH punktach. */
$GLOBALS['awaria_idp'] = false;

beforeEach(function (): void {
    // ŚWIADOMIE bez `udawajIdp()`: ta funkcja sama rejestruje atrapy discovery
    // i certs, a `Http::fake()` SCALA atrapy — przy tym samym wzorcu URL
    // wygrywa zarejestrowana PIERWSZA. Nasze sterowane atrapy nigdy by nie
    // weszły w życie i test awarii IdP mierzyłby zdrowy IdP.
    config([
        'konta.issuer_publiczny' => FabrykaTokenow::ADRES,
        'konta.issuer_wewnetrzny' => FabrykaTokenow::ADRES,
        'konta.client_id' => 'gabinet',
        'konta.wymagana_audiencja' => 'gabinet',
        'konta.redirect_uri' => 'http://localhost/auth/callback',
        'konta.tolerancja_zegara' => 30,
    ]);

    Http::fake([
        'idp.test/*/protocol/openid-connect/token' => function () {
            /** @var array{body?: array<string, mixed>, status?: int} $ustawione */
            $ustawione = $GLOBALS['odpowiedz_tokenu'];

            return Http::response($ustawione['body'] ?? [], $ustawione['status'] ?? 200);
        },
        'idp.test/*/.well-known/openid-configuration' => function () {
            return $GLOBALS['awaria_idp']
                ? Http::response('IdP nieosiągalny', 500)
                : Http::response(discoveryAtrapy());
        },
        'idp.test/*/protocol/openid-connect/certs' => function () {
            return $GLOBALS['awaria_idp']
                ? Http::response('IdP nieosiągalny', 500)
                : Http::response(FabrykaTokenow::jwks());
        },
    ]);
});

/**
 * @param  array<string, mixed>  $body
 */
function punktTokenowZwraca(array $body, int $status = 200): void
{
    $GLOBALS['odpowiedz_tokenu'] = ['body' => $body, 'status' => $status];
}

// Flaga awarii MUSI wracać do stanu wyjściowego po KAŻDYM teście, także po
// tym, który padł w połowie. Bez tego padający test zostawia zepsutą atrapę
// IdP, a następny melduje zupełnie inną przyczynę niż rzeczywista — zmierzone
// dwa razy w tej samej sesji.
afterEach(function (): void {
    $GLOBALS['awaria_idp'] = false;
});

/**
 * Poprawnie podpisany logout token dla wskazanego `sid`.
 *
 * Zwracamy TOKEN, nie odpowiedź: `TestResponse` jest klasą generyczną, a jej
 * typowanie w sygnaturze funkcji pomocniczej kosztuje więcej, niż daje.
 * Żądanie wysyła sam test — widać wtedy wprost, co jest badane.
 */
function logoutTokenDla(string $sid): string
{
    $logoutToken = FabrykaTokenow::podpisz([
        'iss' => FabrykaTokenow::ADRES,
        'aud' => 'gabinet',
        'sub' => 'sub-abc-123',
        'sid' => $sid,
        'iat' => time(),
        'exp' => time() + 120,
        'jti' => 'jti-'.bin2hex(random_bytes(6)),
        'events' => ['http://schemas.openid.net/event/backchannel-logout' => (object) []],
    ]);

    return $logoutToken;
}

/** Surowa zawartość sesji o danym identyfikatorze — wprost z magazynu. */
function sesjaWMagazynie(string $id): string
{
    /** @var SessionHandlerInterface $uchwyt */
    $uchwyt = Session::getHandler();

    return (string) $uchwyt->read($id);
}

/**
 * Odtwarza GRANICĘ PROCESU — pełną, nie połowiczną.
 *
 * Produkcja obsługuje każde żądanie w osobnym procesie PHP, więc sesja jest
 * czytana z magazynu od nowa. W suicie trzy obiekty przeżywają żądanie i każdy
 * z nich osobno potrafi przenieść tożsamość:
 *
 *   · `session`        — menedżer sesji (singleton kontenera);
 *   · `session.store`  — wczytany `Store` z atrybutami w PAMIĘCI;
 *   · `StartSession`   — middleware, KTÓRY SAM JEST SINGLETONEM i trzyma
 *                        referencję do menedżera SPRZED `forgetInstance`.
 *
 * Trzeciego brakowało do 12.08 i to jest cała przyczyna czerwonej nogi 1:
 * `forgetInstance('session')` tworzył nowy menedżer, a middleware podawał
 * dalej stary, z wczytaną tożsamością. Zmierzone niezależnie trzema metodami
 * (R6A-2 identyfikatorami obiektów na żywym stosie, R6B-1 lekturą źródeł
 * `laravel/framework v13.24.0`, weryfikacja krzyżowa Kont u źródła):
 * `SessionServiceProvider.php:22-26` rejestruje `StartSession` jako singleton,
 * `StartSession.php:157-160` czyta z własnego `$manager`, `Container.php:1731-1734`
 * pokazuje, że `forgetInstance` nie sięga do już wstrzykniętej referencji.
 *
 * ⚠ SAMA granica procesu NIE WYSTARCZA. Klient testowy Pesta nie odsyła
 * ciasteczka sesji (`MakesHttpRequests.php:730-737`), więc po zresetowaniu
 * singletonów każde żądanie dostaje NOWY, losowy identyfikator — i 401 byłoby
 * wtedy zgodne z dwoma światami („tożsamość zniknęła" ORAZ „to po prostu inna,
 * pusta sesja"). Dlatego ciasteczko niesiemy JAWNIE, a każdy test korzystający
 * z tej funkcji ma ODCZYT BAZOWY: przy nietkniętej tożsamości ma wyjść 200.
 */
function granicaProcesu(string $idSesji): void
{
    app()->forgetInstance('session');
    app()->forgetInstance('session.store');
    app()->forgetInstance(StartSession::class);

    test()->withCookie(Typy::napis(config('session.cookie')), $idSesji);
}

/** Ile razy poszliśmy do punktu tokenów IdP — licznik prób odświeżenia. */
function zadanDoTokenu(): int
{
    $ile = 0;

    foreach (Http::recorded() as [$zadanie]) {
        if (str_contains($zadanie->url(), '/protocol/openid-connect/token')) {
            $ile++;
        }
    }

    return $ile;
}

/**
 * Loguje koordynatora i zwraca `sid` jego sesji w IdP.
 */
function zalogujKoordynatora(int $waznoscTokenuS = 600): string
{
    // Logowanie ZAWSZE na zdrowym IdP. Bez tego test, który wcześniej ustawił
    // awarię i padł przed sprzątaniem, psuje logowanie następnemu — a ten
    // meldowałby wtedy zupełnie inną przyczynę niż rzeczywista.
    $GLOBALS['awaria_idp'] = false;

    $sid = 'sid-'.bin2hex(random_bytes(6));
    $nonce = 'nonce-testowy';

    $idToken = FabrykaTokenow::podpisz(FabrykaTokenow::claimsId([
        'nonce' => $nonce,
        'sid' => $sid,
    ]));

    $accessToken = FabrykaTokenow::podpisz(FabrykaTokenow::claimsAccess([
        'realm_access' => ['roles' => ['koordynator', 'wymaga-2fa']],
        'exp' => time() + $waznoscTokenuS,
    ]));

    punktTokenowZwraca([
        'id_token' => $idToken,
        'access_token' => $accessToken,
        'refresh_token' => 'refresh-poczatkowy',
        'token_type' => 'Bearer',
    ]);

    test()->withSession(['oidc_przeplyw' => [
        'state' => 'state-testowy',
        'nonce' => $nonce,
        'pkce' => 'weryfikator-testowy-o-odpowiedniej-dlugosci-123456',
    ]])->get('/auth/callback?code=kod-testowy&state=state-testowy');

    return $sid;
}

it('odbiera dostęp, gdy Keycloak odbierze rolę — najpóźniej w oknie access tokenu', function (): void {
    // Token ważny 1 s: symulujemy koniec okna, nie czekając 10 minut.
    zalogujKoordynatora(waznoscTokenuS: 1);

    // Stan wyjściowy: bramka koordynatora OTWARTA.
    //
    // ŚWIADOMIE bez `assertJsonPath('bramki.panel.koordynacji', …)`: nazwa
    // bramki SAMA ZAWIERA KROPKĘ, więc ścieżka schodzi w zagnieżdżenie
    // `bramki → panel → koordynacji`, którego nie ma, i asercja porównuje
    // `null` zamiast trafić w klucz. Dokładnie ta pułapka wywróciła U-4.
    $odpowiedz = test()->get('/auth/ja')->assertOk();

    expect($odpowiedz->json('role'))->toBe(['koordynator'])
        ->and($odpowiedz->json('bramki')['panel.koordynacji'])->toBeTrue();

    // W Keycloaku odebrano rolę. Kolejne odświeżenie zwraca token BEZ niej.
    $bezRoli = FabrykaTokenow::podpisz(FabrykaTokenow::claimsAccess([
        'realm_access' => ['roles' => ['wymaga-2fa']],
        'exp' => time() + 600,
    ]));

    punktTokenowZwraca([
        'access_token' => $bezRoli,
        'refresh_token' => 'refresh-kolejny',
        'token_type' => 'Bearer',
    ]);

    sleep(2);

    // Ta sama sesja, to samo żądanie — uprawnienie ma zniknąć.
    $po = test()->get('/auth/ja')->assertOk();

    expect($po->json('role'))->toBe([])
        ->and($po->json('bramki')['panel.koordynacji'])->toBeFalse();
});

it('NIE traci dostępu, dopóki access token jest ważny — okno jest oknem, nie losowaniem', function (): void {
    // Kierunek odwrotny. Bez niego „traci dostęp" przechodzi także wtedy, gdy
    // sesja wygasa przy każdym żądaniu — a to byłoby zepsute inaczej.
    zalogujKoordynatora(waznoscTokenuS: 600);

    for ($i = 0; $i < 3; $i++) {
        expect(test()->get('/auth/ja')->assertOk()->json('bramki')['panel.koordynacji'])->toBeTrue();
    }

    // Żadne żądanie do IdP nie poszło — odświeżenie ma się dziać w oknie
    // tokenu, nie przy każdym kliknięciu.
    $doTokenu = 0;

    foreach (Http::recorded() as [$zadanie]) {
        if (str_contains($zadanie->url(), '/protocol/openid-connect/token')) {
            $doTokenu++;
        }
    }

    // Jedno: wymiana kodu przy logowaniu. Ani jednego odświeżenia.
    expect($doTokenu)->toBe(1);
});

it('kończy sesję, gdy IdP odmawia odświeżenia — konto zablokowane', function (): void {
    zalogujKoordynatora(waznoscTokenuS: 1);

    punktTokenowZwraca(['error' => 'invalid_grant', 'error_description' => 'Session not active'], 400);

    sleep(2);

    test()->get('/auth/ja')->assertStatus(401)->assertJsonPath('zalogowany', false);
});

it('zabija sesję NATYCHMIAST po back-channel logout — bez czekania na okno tokenu', function (): void {
    // Ścieżka „teraz" z decyzji B8: blokada konta nie czeka na wygaśnięcie
    // access tokenu. Token jest tu ważny 600 s i mimo to dostęp znika.
    $sid = zalogujKoordynatora(waznoscTokenuS: 600);

    expect(test()->get('/auth/ja')->assertOk()->json('bramki')['panel.koordynacji'])->toBeTrue();

    $logoutToken = FabrykaTokenow::podpisz([
        'iss' => FabrykaTokenow::ADRES,
        'aud' => 'gabinet',
        'sub' => 'sub-abc-123',
        'sid' => $sid,
        'iat' => time(),
        'exp' => time() + 120,
        'jti' => 'jti-'.bin2hex(random_bytes(6)),
        'events' => ['http://schemas.openid.net/event/backchannel-logout' => (object) []],
    ]);

    // Dowód, że logout NAPRAWDĘ trafił w sesję tego użytkownika, a nie
    // odpowiedział grzecznie w próżnię: liczymy SKASOWANE sesje. Bez tego
    // asercja „401 potem" mogłaby przechodzić z zupełnie innego powodu.
    // Bierzemy identyfikator Z REJESTRU `sid → sesje lokalne`, a nie
    // `session()->getId()` klienta testowego: to drugie zwraca sesję bieżącego
    // żądania, która nie musi być tą zarejestrowaną przy logowaniu. Pierwsza
    // wersja tego testu mierzyła właśnie nie ten identyfikator.
    $zarejestrowane = RejestrSesji::odczytaj($sid);

    expect($zarejestrowane)->toHaveCount(1);

    $idSesji = $zarejestrowane[0];

    expect(sesjaWMagazynie($idSesji))->not->toBe('', 'Sesja nie istnieje w magazynie — test nie mierzy tego, co powinien.');

    $odp = test()->postJson('/oidc/backchannel-logout', ['logout_token' => $logoutToken])->assertOk();

    // Dowód, że logout trafił w sesję TEGO użytkownika, a nie odpowiedział
    // grzecznie w próżnię.
    expect($odp->json('skasowane_sesje'))->toBe(1);

    // Mierzymy STAN MAGAZYNU, nie kolejną odpowiedź klienta testowego.
    //
    // Powód: w suicie sterownik sesji to `array`, a menedżer sesji jest
    // singletonem w kontenerze — Store raz wczytany trzyma atrybuty w pamięci
    // procesu i kolejne `get()` w tym samym teście nie sięga po nie do
    // magazynu. Produkcja ma osobny proces na żądanie, więc tam liczy się
    // dokładnie to, co sprawdzamy tutaj: czy w magazynie CZEGOKOLWIEK
    // jeszcze nie ma. Asercja na odpowiedzi mierzyłaby klienta testowego.
    expect(sesjaWMagazynie($idSesji))->toBe('');
});

it('kończy sesję przy niedostępnym IdP, gdy klucze są w cache — BEZ wchodzenia w tryb awaryjny', function (): void {
    // Efekt przeprojektowania: `jwksDlaKid()` nie wyrzuca już starych kluczy
    // przed pobraniem świeżych i nie rzuca przy nieudanym odświeżeniu. Dzięki
    // temu niedostępny IdP przestał być awarią — poprawny logout token
    // przechodzi ZWYKŁĄ ścieżką, bo podpis da się sprawdzić kluczami z cache'u.
    //
    // Dlatego asercja brzmi: awarii MA NIE BYĆ. To mocniejszy wynik niż
    // „fail-safe zadziałał": ścieżka awaryjna w ogóle nie jest potrzebna.
    $sid = zalogujKoordynatora(waznoscTokenuS: 600);
    $idSesji = RejestrSesji::odczytaj($sid)[0];

    expect(sesjaWMagazynie($idSesji))->not->toBe('');

    // Klucze ZOSTAJĄ w cache'u. Awarię wymuszamy na discovery, którego
    // `metadane()` potrzebuje przy nieznanym `kid`.
    SladWylogowania::wyczysc();
    Cache::forget('konta:discovery');
    $GLOBALS['awaria_idp'] = true;

    $odp = test()->postJson('/oidc/backchannel-logout', ['logout_token' => logoutTokenDla($sid)]);

    expect($odp->status())->toBe(200)
        ->and(SladWylogowania::wejscia())->toBe(1, 'Handler nie wystartował — mierzymy nie to zjawisko.')
        ->and(SladWylogowania::awarie())->toBe(0, 'Handler wszedł w tryb awaryjny, choć klucze były w cache.')
        ->and($odp->json('skasowane_sesje'))->toBe(1)
        ->and(sesjaWMagazynie($idSesji))->toBe('');
});

it('NIE kasuje sesji, gdy podpisu nie da się sprawdzić — i mówi o tym głośno', function (): void {
    // Druga strona tej samej klauzuli. Bez kluczy w cache'u weryfikacja jest
    // niemożliwa, więc sesji NIE ruszamy — ale nie milczymy: 503 zaprasza IdP
    // do ponowienia, a licznik odmów robi z tego sygnał dla operatora.
    $sid = zalogujKoordynatora(waznoscTokenuS: 600);
    $zarejestrowane = RejestrSesji::odczytaj($sid);

    expect($zarejestrowane)->toHaveCount(1, 'Logowanie nie zarejestrowało sesji — test nie ma czego chronić.');

    $idSesji = $zarejestrowane[0];

    SladWylogowania::wyczysc();
    Cache::forget('konta:discovery');
    Cache::forget('konta:jwks');
    $GLOBALS['awaria_idp'] = true;

    $odp = test()->postJson('/oidc/backchannel-logout', ['logout_token' => logoutTokenDla($sid)]);

    expect($odp->status())->toBe(503)
        ->and($odp->json('skasowane_sesje'))->toBe(0)
        ->and(SladWylogowania::odmowy())->toBe(1, 'Odmowa nie została odnotowana — cisza zamiast sygnału.')
        ->and(sesjaWMagazynie($idSesji))->not->toBe('');
});

it('ADWERSARIALNY: napastnik z cudzym sid i wymuszoną awarią NIE wylogowuje ofiary', function (): void {
    // Pytanie adwersarialne do klauzuli fail-safe: skoro awaryjna ścieżka
    // kończy sesje, to czy nie jest narzędziem WYMUSZONEGO WYLOGOWANIA?
    //
    // Napastnik ma tu WSZYSTKO, co mógłby mieć w rzeczywistości:
    //   · zna `sid` ofiary (zakładamy najgorszy przypadek — wyciek),
    //   · kontroluje `kid`, więc potrafi wymusić sięgnięcie do sieci,
    //   · trafia w moment niedostępności IdP.
    // Nie ma jednego: klucza prywatnego Kont Niepodzielni.
    $sid = zalogujKoordynatora(waznoscTokenuS: 600);
    $zarejestrowane = RejestrSesji::odczytaj($sid);

    expect($zarejestrowane)->toHaveCount(1, 'Logowanie nie zarejestrowało sesji — nie ma czego atakować.');

    $idSesji = $zarejestrowane[0];

    expect(sesjaWMagazynie($idSesji))->not->toBe('');

    // Token napastnika: poprawny kształt, cudzy `sid`, PODPIS WŁASNYM KLUCZEM.
    $obcyKlucz = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);

    expect($obcyKlucz)->not->toBeFalse('Nie udało się wygenerować klucza napastnika.');

    /** @var OpenSSLAsymmetricKey $obcyKlucz */
    $naglowek = ['alg' => 'RS256', 'typ' => 'JWT', 'kid' => 'kid-wymyslony-przez-napastnika'];
    $claims = [
        'iss' => FabrykaTokenow::ADRES,
        'aud' => 'gabinet',
        'sub' => 'sub-abc-123',
        'sid' => $sid,
        'iat' => time(),
        'exp' => time() + 120,
        'jti' => 'jti-napastnika',
        'events' => ['http://schemas.openid.net/event/backchannel-logout' => (object) []],
    ];

    $czesci = [
        rtrim(strtr(base64_encode((string) json_encode($naglowek)), '+/', '-_'), '='),
        rtrim(strtr(base64_encode((string) json_encode($claims)), '+/', '-_'), '='),
    ];

    $podpis = '';
    openssl_sign(implode('.', $czesci), $podpis, $obcyKlucz, OPENSSL_ALGO_SHA256);
    $czesci[] = rtrim(strtr(base64_encode(Typy::napis($podpis)), '+/', '-_'), '=');

    // Napastnik dostaje NAJGORSZY DLA NAS scenariusz: pusty cache kluczy
    // i niedostępny IdP. Dopiero wtedy handler wchodzi w ścieżkę AWARYJNĄ —
    // czyli w tę, w której kiedyś kończyliśmy sesję po niezweryfikowanym
    // `sid`. Bez wyczyszczenia cache'u atak zatrzymywał się na zwykłej odmowie
    // walidacji i test nie dotykał badanej furtki (zmierzone: przechodził
    // także po jej przywróceniu).
    SladWylogowania::wyczysc();
    Cache::forget('konta:discovery');
    Cache::forget('konta:jwks');
    $GLOBALS['awaria_idp'] = true;

    $odp = test()->postJson('/oidc/backchannel-logout', ['logout_token' => implode('.', $czesci)]);

    // Handler WSZEDŁ I NAPRAWDĘ wpadł w ścieżkę awaryjną — inaczej test
    // mierzyłby zwykłą odmowę walidacji, nie odporność tej furtki.
    expect(SladWylogowania::wejscia())->toBe(1, 'Handler nie wystartował.')
        ->and(SladWylogowania::awarie())->toBe(1, 'Atak nie dotarł do ścieżki awaryjnej — test nie bada furtki.');

    // SEDNO: sesja ofiary ŻYJE. Niezależnie od tego, którą ścieżką poszedł
    // handler — awaryjną czy zwykłą odmową walidacji — napastnik bez klucza
    // prywatnego Kont Niepodzielni NIE MOŻE nikogo wylogować.
    expect(sesjaWMagazynie($idSesji))->not->toBe('', 'WYMUSZONE WYLOGOWANIE: napastnik wyrzucił ofiarę z systemu.')
        // Żadna gałąź nie mogła zgłosić sukcesu wylogowania.
        ->and($odp->json('ok'))->not->toBeTrue()
        ->and(Typy::liczba($odp->json('skasowane_sesje')))->toBe(0)
        // Odpowiedź MUSI być odmową — 400 (zły podpis) albo 503 (nie dało się
        // zweryfikować). Nigdy 200, nigdy 500.
        ->and(in_array($odp->status(), [400, 503], true))->toBeTrue(
            'Nieoczekiwany kod odpowiedzi na atak: '.$odp->status()
        );
});

it('czyta role Z ACCESS TOKENU, a nie z userinfo — źródła podają RÓŻNE role', function (): void {
    // WYOSTRZENIE B2 (wzorzec huba). Asercja „role == [koordynator]" przechodzi
    // także wtedy, gdy kod czyta ze ZŁEGO ŹRÓDŁA — o ile fixtura każe obu
    // źródłom mówić to samo. Test odpowiadał więc na pytanie „czy role są",
    // a powinien na „Z KTÓREGO ŹRÓDŁA".
    //
    // Dlatego źródła podają tu ROZBIEŻNE role. Test przechodzi wyłącznie
    // przy czytaniu z access tokenu; odczyt z userinfo albo z ID tokenu
    // natychmiast go wywraca.
    $nonce = 'nonce-testowy';

    // ID token: rola „redaktor" — NIE MA prawa autoryzować (kontrakt §2:
    // uprawnienia wyłącznie z access tokenu).
    $idToken = FabrykaTokenow::podpisz(FabrykaTokenow::claimsId([
        'nonce' => $nonce,
        'sid' => 'sid-rozbiezny',
        'realm_access' => ['roles' => ['redaktor']],
    ]));

    // Access token: rola „koordynator" — TO jest jedyne prawidłowe źródło.
    $accessToken = FabrykaTokenow::podpisz(FabrykaTokenow::claimsAccess([
        'realm_access' => ['roles' => ['koordynator']],
        'exp' => time() + 600,
    ]));

    punktTokenowZwraca([
        'id_token' => $idToken,
        'access_token' => $accessToken,
        'refresh_token' => 'refresh-rozbiezny',
        'token_type' => 'Bearer',
    ]);

    // Atrapa userinfo podaje TRZECIĄ, jeszcze inną rolę. Gdyby kod kiedykolwiek
    // po nią sięgnął, wynik będzie natychmiast widoczny.
    Http::fake([
        'idp.test/*/protocol/openid-connect/userinfo' => Http::response([
            'sub' => 'sub-abc-123',
            'realm_access' => ['roles' => ['psycholog']],
        ]),
    ]);

    test()->withSession(['oidc_przeplyw' => [
        'state' => 'state-testowy',
        'nonce' => $nonce,
        'pkce' => 'weryfikator-testowy-o-odpowiedniej-dlugosci-123456',
    ]])->get('/auth/callback?code=kod-testowy&state=state-testowy');

    $odpowiedz = test()->get('/auth/ja')->assertOk();

    // Rola z ACCESS TOKENU — i żadna inna.
    expect($odpowiedz->json('role'))->toBe(['koordynator'])
        ->and($odpowiedz->json('role'))->not->toContain('redaktor', 'Role czytane z ID TOKENU.')
        ->and($odpowiedz->json('role'))->not->toContain('psycholog', 'Role czytane z USERINFO.');

    // Uprawnienia też muszą pochodzić z tego jednego źródła: bramka redaktora
    // ma być ZAMKNIĘTA, mimo że ID token przypisuje tę rolę.
    $bramki = $odpowiedz->json('bramki');

    expect($bramki['panel.koordynacji'])->toBeTrue()
        ->and($bramki['tresci.edytuj'] ?? false)->toBeFalse();
});

it('zapisuje ID token do sesji ZASZYFROWANY — e-mail pacjenta nie do odzyskania', function (): void {
    // Refinement B7 z huba, druga część: poleganie na `SESSION_ENCRYPT` to
    // JEDNA FLAGA OD WYCIEKU. ID token niesie claim `email`, a dla Gabinetu
    // jest wartością NIEPRZEZROCZYSTĄ (tylko `id_token_hint` przy wylogowaniu).
    // Wartość, do której nie zaglądamy, nie ma powodu leżeć czytelnie.
    //
    // Asercja celuje w KOD PRODUKCYJNY — w to, co zapisał kontroler — a nie
    // w to, co test sam sobie włożył do sesji.
    $email = 'pacjent-'.bin2hex(random_bytes(6)).'@przyklad.test';
    $nonce = 'nonce-testowy';

    $idToken = FabrykaTokenow::podpisz(FabrykaTokenow::claimsId([
        'nonce' => $nonce,
        'sid' => 'sid-szyfrowanie',
        'email' => $email,
    ]));

    punktTokenowZwraca([
        'id_token' => $idToken,
        'access_token' => FabrykaTokenow::podpisz(FabrykaTokenow::claimsAccess([
            'realm_access' => ['roles' => ['koordynator']],
            'exp' => time() + 600,
        ])),
        'refresh_token' => 'refresh-szyfrowanie',
        'token_type' => 'Bearer',
    ]);

    test()->withSession(['oidc_przeplyw' => [
        'state' => 'state-testowy',
        'nonce' => $nonce,
        'pkce' => 'weryfikator-testowy-o-odpowiedniej-dlugosci-123456',
    ]])->get('/auth/callback?code=kod-testowy&state=state-testowy');

    $zapisany = Typy::napis(Typy::mapa(session('konta'))['id_token'] ?? null);

    expect($zapisany)->not->toBe('', 'Kontroler nie zapisał ID tokenu — test nie ma czego sprawdzać.')
        // Nie jest to surowy JWT…
        ->and($zapisany)->not->toBe($idToken, 'ID token zapisany JAWNIE w sesji.')
        // …i nie da się z niego odczytać e-maila po zdekodowaniu base64url.
        // To jest ta asercja, której brakowało: samo „nie równa się" przeszłoby
        // także przy kodowaniu bez szyfrowania.
        ->and(str_contains(zdekodowaneLadunki($zapisany), $email))->toBeFalse(
            'E-mail pacjenta odzyskiwalny z zapisanego ID tokenu.'
        );

    // Kierunek odwrotny: zapis MUSI dać się odszyfrować, inaczej wylogowanie
    // straci `id_token_hint` i „bezpieczeństwo" wyszłoby z zepsucia funkcji.
    expect(Crypt::decryptString($zapisany))->toBe($idToken);
});

it('POZYTYWNY: żądanie PO wylogowaniu dostaje 401 — logout REALNIE zabija sesję', function (): void {
    // BLK-22, potwierdzony jako realny defekt WZORCA: back-channel logout
    // zamyka sesję po stronie IdP, a KONSUMENT DALEJ SERWUJE. Zmierzone
    // w ekosystemie. Nasza praca B8 jest naprawą tego defektu u konsumenta,
    // więc musi mieć dowód POZYTYWNY: nie „stan magazynu się zmienił", tylko
    // „kolejne żądanie tej samej przeglądarki dostaje 401".
    //
    // Poprzedni test mierzył zawartość magazynu, bo suita biegnie na
    // sterowniku `array`, a menedżer sesji jest singletonem w kontenerze —
    // Store raz wczytany trzyma atrybuty w pamięci procesu i kolejne `get()`
    // nie sięga po nie do magazynu. To był uczciwy obchód przyrządu, ale
    // mierzył WĘŻSZE zjawisko niż deklarowane.
    //
    // Tutaj przełączamy się na sterownik PRODUKCYJNY, żeby każde żądanie
    // czytało sesję z prawdziwego magazynu — dokładnie jak w produkcji.
    config(['session.driver' => 'redis']);

    $sid = zalogujKoordynatora(waznoscTokenuS: 600);

    // Identyfikator sesji LOKALNEJ — potrzebny, żeby zapytać magazyn wprost.
    $idSesji = RejestrSesji::odczytaj($sid)[0];

    // Stan wyjściowy: sesja ŻYJE i otwiera bramkę koordynatora.
    expect(test()->get('/auth/ja')->assertOk()->json('bramki')['panel.koordynacji'])->toBeTrue();
    expect(sesjaWMagazynie($idSesji))->not->toBe('', 'Sesji nie ma w magazynie PRZED wylogowaniem — test nie ma czego zabijać.');

    $odp = test()->postJson('/oidc/backchannel-logout', ['logout_token' => logoutTokenDla($sid)])
        ->assertOk();

    expect($odp->json('skasowane_sesje'))->toBe(1, 'Logout nie trafił w sesję tego użytkownika.');

    // ⛔ DWA TWIERDZENIA, DWA NIEZALEŻNE SYGNAŁY (R6B-2).
    //
    // Nazwa tego testu mówi „logout REALNIE zabija sesję". Do 09.08 dowodził
    // czego innego: 401 niżej przychodzi przez ZNACZNIK unieważnienia po `sid`,
    // więc sesja mogła zostać w magazynie nietknięta, a test i tak świecił.
    //
    // Zmierzone perturbacją — kasowanie wyłączone, LICZNIK nienaruszony
    // (`if (true) { $skasowane++; }`): test PRZESZEDŁ (1 passed, 6 assertions).
    // Czyli jedna wartość (401) była zgodna z dwoma światami: „sesja skasowana"
    // i „sesja żyje, ale znacznik blokuje".
    //
    // Ta asercja pyta MAGAZYN, czyli inną drogą niż mechanizm, który bada.
    expect(sesjaWMagazynie($idSesji))->toBe(
        '',
        'Sesja PRZEŻYŁA wylogowanie w magazynie. 401 niżej pochodzi wtedy ze ZNACZNIKA, '.
        'nie z kasowania — a nazwa tego testu obiecuje kasowanie.'
    );

    // GRANICA PROCESU. Produkcja obsługuje każde żądanie w osobnym procesie
    // PHP, więc sesja jest za każdym razem czytana z magazynu od nowa.
    // W suicie menedżer sesji jest singletonem w kontenerze i raz wczytany
    // `Store` trzyma atrybuty w PAMIĘCI — kolejne żądanie widziałoby dane,
    // których w magazynie już nie ma, i test meldowałby sukces konsumenta,
    // który w rzeczywistości serwuje po wylogowaniu (czyli DEFEKT BLK-22).
    //
    // Zmierzone, zanim to napisałem: sterownik sesji w żądaniu to naprawdę
    // `CacheBasedSessionHandler` (redis), a `skasowane_sesje` = 1 — magazyn
    // był pusty, a mimo to żądanie dostawało 200. Nie sterownik, tylko
    // singleton. Odtwarzamy więc granicę procesu jawnie.
    app()->forgetInstance('session');
    app()->forgetInstance('session.store');

    // SEDNO BLK-22: kolejne żądanie MUSI dostać 401. Konsument, który dalej
    // serwuje po zamknięciu sesji w IdP, jest dokładnie tym defektem.
    test()->get('/auth/ja')
        ->assertStatus(401)
        ->assertJsonPath('zalogowany', false);
});

it('NOGA 1: tożsamość usunięta + ŻYWY refresh token → 401, i ZERO nowych prób odświeżenia', function (): void {
    // Para negatywna do BLK-22, noga pierwsza. Wymóg standardu B8:
    // odświeżanie ma być OPERACJĄ NA ISTNIEJĄCEJ TOŻSAMOŚCI. Refresh token,
    // który przetrwał usunięcie tożsamości, nie może jej WSKRZESIĆ.
    //
    // ── HISTORIA TEGO TESTU, zostawiona świadomie ────────────────────────
    //
    // Do 12.08 nosił etykietę [NIEROZSTRZYGNIĘTE] i był JEDYNYM czerwonym
    // testem suity. Przyczyna okazała się PRZYRZĄDEM, nie systemem, i została
    // ustalona pomiarem, nie rozumowaniem: granica procesu była odtwarzana
    // POŁOWICZNIE (bez `StartSession`, bez ciasteczka). Szczegóły mechanizmu
    // i dowody z przypiętego frameworka — w docbloku `granicaProcesu()`.
    //
    // Wcześniejsza diagnoza („odświeżanie wskrzesza tożsamość z refresh
    // tokenu") została OBALONA dwukrotnie — raz pomiarem kontrolnym po
    // przebudowie pisarza (liczby identyczne), raz weryfikacją krzyżową Kont.
    // NIE ścigaj jej ponownie.
    //
    // ── CO TEN TEST NAPRAWDĘ DOWODZI, a czego NIE ────────────────────────
    //
    // Dowodzi: po usunięciu tożsamości z magazynu kolejne żądanie TEJ SAMEJ
    // przeglądarki (to samo ciasteczko sesji!) dostaje 401, a system nie
    // podejmuje ŻADNEJ próby odświeżenia.
    //
    // NIE dowodzi, że system BRONI SIĘ przed wskrzeszeniem. Powód jest inny
    // i słabszy: refresh token mieszka WEWNĄTRZ tożsamości, a tożsamość
    // wewnątrz sesji — skasowanie sesji zabiera token razem z nią, więc
    // odświeżanie NIE MA Z CZEGO wskrzesić. Ta własność zniknie w dniu,
    // w którym ktokolwiek przeniesie refresh token poza sesję (odświeżanie
    // w tle, odświeżanie z kolejki) — i wtedy właśnie ma zaświecić druga
    // asercja niżej, ta o liczniku żądań do punktu tokenów.
    //
    // ── TABELA ŚWIATÓW (pre-flight dyskryminatora) ───────────────────────
    //
    //   (200, licznik bez zmian) → tożsamość PRZEŻYŁA skasowanie sesji
    //   (200, licznik +1)        → WSKRZESZENIE z refresh tokenu
    //   (401, licznik +1)        → próba odświeżenia była, IdP/walidacja odmówiły
    //   (401, licznik bez zmian) → OCZEKIWANE: nie ma czego odświeżać
    //
    // Każda wartość ma dokładnie jeden świat — dlatego wolno tym mierzyć.
    //
    // TOKEN WAŻNY 1 s — TO JEST ISTOTA TEGO TESTU, nie szczegół. Przy
    // `OdswiezanieSesji::MARGINES_S = 30` okno odświeżania jest wtedy otwarte
    // przy KAŻDYM żądaniu, więc ścieżka wskrzeszenia ma pełną szansę zadziałać.
    // Gdyby token był ważny 600 s, ścieżka odświeżania nie uruchomiłaby się
    // wcale, a 401 pochodziłoby po prostu z braku sesji — test świeciłby
    // zielono, nie zbadawszy tego, co deklaruje.
    config(['session.driver' => 'redis']);
    $sid = zalogujKoordynatora(waznoscTokenuS: 1);

    // Identyfikator sesji LOKALNEJ — ten, który klient dostał w `Set-Cookie`
    // po `regenerate()` przy logowaniu. Bez niego nie da się odtworzyć
    // „tej samej przeglądarki" po granicy procesu.
    $idSesji = RejestrSesji::odczytaj($sid)[0] ?? '';

    expect($idSesji)->not->toBe(
        '',
        'Rejestr sesji nie zna sesji lokalnej dla tego `sid` — pomiar nie ma PRZEDMIOTU, '.
        'a nie „przeszedł". Bez identyfikatora ciasteczko niesie pustkę i każde 401 niżej '.
        'byłoby 401 nowej, pustej sesji.'
    );

    // ── ODCZYT BAZOWY (kontrola pozytywna) ───────────────────────────────
    // Granica procesu SAMA W SOBIE nie ma prawa zabrać tożsamości. Gdyby
    // zabierała, 401 na końcu byłoby artefaktem przyrządu — dokładnie tym,
    // czym było do 12.08.
    granicaProcesu($idSesji);

    expect(test()->get('/auth/ja')->assertOk()->json('bramki')['panel.koordynacji'])->toBeTrue();

    $licznikBazowy = zadanDoTokenu();

    // Usuwamy TOŻSAMOŚĆ, zostawiając refresh token żywy po stronie IdP —
    // atrapa punktu tokenów nadal zwraca poprawny token, więc wskrzeszenie
    // jest DOSTĘPNE dla systemu, który by je robił.
    foreach (RejestrSesji::odczytaj($sid) as $id) {
        Session::getHandler()->destroy($id);
    }

    // DOWÓD MUTACJI: magazyn pytany INNĄ DROGĄ niż mechanizm badany niżej.
    expect(sesjaWMagazynie($idSesji))->toBe(
        '',
        'Sesja NIE zniknęła z magazynu, więc test nie zaczął się od stanu, który deklaruje.'
    );

    granicaProcesu($idSesji);

    sleep(2);

    // Odświeżenie NIE MA prawa odtworzyć tożsamości, której nie ma.
    test()->get('/auth/ja')
        ->assertStatus(401)
        ->assertJsonPath('zalogowany', false);

    expect(zadanDoTokenu())->toBe(
        $licznikBazowy,
        'Poszło żądanie do punktu tokenów PO usunięciu tożsamości. Znaczy to, że refresh '.
        'token przeżył skasowanie sesji — czyli zamieszkał poza nią. To jest dokładnie ta '.
        'regresja, której ten test pilnuje: wskrzeszenie przestaje być niemożliwe '.
        'konstrukcyjnie i staje się kwestią tego, czy ktoś pamiętał o warunku.'
    );
});

it('znacznik unieważnienia PRZEŻYWA wyczyszczenie cache — brak trybu cichego otwarcia', function (): void {
    // Znacznik unieważnienia to NEGATYWNA ASERCJA BEZPIECZEŃSTWA („ten sid
    // jest martwy"), więc KAŻDY sposób, w jaki może zniknąć, jest po cichu
    // FAIL-OPEN: zablokowany wraca, a objawem jest BRAK OBJAWU.
    //
    // Pierwsza wersja trzymała znacznik w cache'u. Wyzwalacze zniknięcia są
    // całkowicie prozaiczne: `cache:clear`, deploy, restart Redisa, eksmisja
    // LRU. Ten test mierzy dokładnie ten scenariusz.
    config(['session.driver' => 'redis']);
    $sid = zalogujKoordynatora(waznoscTokenuS: 600);

    expect(test()->get('/auth/ja')->assertOk()->json('bramki')['panel.koordynacji'])->toBeTrue();

    test()->postJson('/oidc/backchannel-logout', ['logout_token' => logoutTokenDla($sid)])->assertOk();

    // NAJPROZAICZNIEJSZE ZDARZENIE OPERACYJNE, jakie można sobie wyobrazić.
    Cache::flush();

    app()->forgetInstance('session');
    app()->forgetInstance('session.store');

    test()->get('/auth/ja')
        ->assertStatus(401)
        ->assertJsonPath('zalogowany', false);
});

it('brak rozstrzygnięcia o unieważnieniu = ODMOWA, nigdy 200 (fail-closed)', function (): void {
    // Przeniesienie znacznika do bazy dało kontroli bezpieczeństwa zależność,
    // której wcześniej nie miała. Gdyby zapytanie o znacznik było gdziekolwiek
    // opakowane w przechwycenie „zaloguj i przepuść", odtworzyłbym klasę,
    // którą właśnie zamknąłem — tylko wyzwalaczem byłaby niedostępna baza
    // zamiast `cache:clear`.
    //
    // Wymóg: BRAK ROZSTRZYGNIĘCIA = ODMOWA, głośna. Nigdy 200.
    config(['session.driver' => 'redis']);
    zalogujKoordynatora(waznoscTokenuS: 600);

    expect(test()->get('/auth/ja')->assertOk()->json('bramki')['panel.koordynacji'])->toBeTrue();

    // PUNKT ZAPISU: PostgreSQL unieważnia CAŁĄ transakcję po błędnym
    // zapytaniu, a suita biegnie w transakcji (`RefreshDatabase`). Bez tego
    // test truje sam siebie i pada na sprzątaniu zamiast na asercji —
    // mierzyłby własne zanieczyszczenie, nie zachowanie systemu.
    $status = Typy::liczba(probaZerwania(function (): int {
        // Zrywamy MOŻLIWOŚĆ ROZSTRZYGNIĘCIA: tabela znaczników znika.
        Schema::drop('uniewaznione_sesje');

        app()->forgetInstance('session');
        app()->forgetInstance('session.store');

        return test()->get('/auth/ja')->status();
    }));

    expect($status)->not->toBe(200, 'FAIL-OPEN: brak rozstrzygnięcia o unieważnieniu przepuścił żądanie.')
        ->and(in_array($status, [401, 500, 503], true))->toBeTrue(
            'Nieoczekiwany status przy braku rozstrzygnięcia: '.$status
        );
});
