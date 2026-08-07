<?php

declare(strict_types=1);

use App\Tozsamosc\RejestrSesji;
use App\Tozsamosc\SladWylogowania;
use Illuminate\Support\Facades\Cache;
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

/** Surowa zawartość sesji o danym identyfikatorze — wprost z magazynu. */
function sesjaWMagazynie(string $id): string
{
    /** @var SessionHandlerInterface $uchwyt */
    $uchwyt = Session::getHandler();

    return (string) $uchwyt->read($id);
}

/**
 * Loguje koordynatora i zwraca `sid` jego sesji w IdP.
 */
function zalogujKoordynatora(int $waznoscTokenuS = 600): string
{
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

it('zabija sesję TAKŻE gdy walidacja padnie wyjątkiem — fail-safe logout', function (): void {
    // Klauzula fail-safe logout (standard B8, znalezisko sesji `konta`).
    // Handler pobiera JWKS, więc sięga po sieć. Gdy IdP jest niedostępny,
    // pierwotna wersja kończyła się kodem 500 — a SESJA ŻYŁA DALEJ, w ciszy.
    // To dokładnie ten tryb awarii, który back-channel logout ma eliminować.
    $sid = zalogujKoordynatora(waznoscTokenuS: 600);

    $zarejestrowane = RejestrSesji::odczytaj($sid);
    $idSesji = $zarejestrowane[0];

    expect(sesjaWMagazynie($idSesji))->not->toBe('');

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

    // Wstrzykujemy awarię DOKŁADNIE w operację poprzedzającą zabicie sesji:
    // pobranie JWKS. Cache czyścimy, żeby handler naprawdę poszedł do sieci.
    SladWylogowania::wyczysc();

    // Czyścimy WYBIÓRCZO dwa klucze OIDC, żeby handler naprawdę poszedł do
    // sieci. `Cache::flush()` byłoby tu błędem: rejestr `sid → sesje lokalne`
    // też mieszka w cache'u, więc czyszczenie wszystkiego kasowało CEL
    // perturbacji i sesja „przeżywała" awarię, bo nie było czego zabić.
    // Przyrząd niszczył zjawisko, które miał zmierzyć.
    Cache::forget('konta:jwks');
    Cache::forget('konta:discovery');

    $GLOBALS['awaria_idp'] = true;

    $odp = test()->postJson('/oidc/backchannel-logout', ['logout_token' => $logoutToken]);

    // Handler NIE MOŻE zwrócić 500 z żywą sesją.
    expect($odp->status())->not->toBe(500);

    // DWA NIEZALEŻNE SYGNAŁY — licznik skasowanych sesji sam w sobie myli
    // „token nie dotarł" z „dotarł i handler padł": oba dają zero. Dlatego
    // osobno pytamy, czy handler w ogóle wszedł.
    expect(SladWylogowania::wejscia())->toBe(1, 'Handler w ogóle nie wystartował — mierzymy nie to zjawisko.')
        ->and(SladWylogowania::awarie())->toBe(1, 'Walidacja nie padła — perturbacja nierozstrzygająca.');

    // I najważniejsze: sesja NIE PRZEŻYŁA awarii.
    expect(sesjaWMagazynie($idSesji))->toBe('');

    $GLOBALS['awaria_idp'] = false;
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
