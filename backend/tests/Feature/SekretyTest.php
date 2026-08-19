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

/**
 * WARTOSCI JAWNIE UZNANE ZA NIETAJNE — allowlista, nie denylista (R9-3).
 *
 * Do 18.08 ochrona `.env.example` stala na DWOCH listach NAZW: czterech
 * regulach gitleaksa (APP_KEY, SMSAPI_TOKEN, KEYCLOAK_*_SECRET, DB_PASSWORD)
 * i czternastu nazwach w tym tescie. Runda 9 zmierzyla, co z tego wynika:
 *
 *     .env.example + GOOGLE_CALENDAR_CLIENT_SECRET=GOCSPX-…
 *       gitleaks   → no leaks found        (zwolnienie na ten plik)
 *       SekretyTest → 3 passed             (nazwa spoza czternastu)
 *       pelna suita → 290 passed
 *     ta sama wartosc w `docs/probka.md` → leaks found: 1
 *
 * Dwa mechanizmy, kazdy powolujacy sie na drugi, i dziura dokladnie
 * w czesci wspolnej. Nazwa zmiennej nie jest wymyslona: `GOOGLE_CALENDAR_*`
 * bedzie potrzebna w F4, token huba w F8, token Zammada w F8 — kazda wejdzie
 * w fazie, w ktorej nikt nie bedzie pamietal, ze ochrona to lista nazw.
 *
 * Ciezar dowodu jest odtad ODWROCONY, tak jak przy schemacie bazy i przy
 * prymitywach krypto (R6A-4): KAZDE przypisanie musi byc PUSTE albo stac
 * tutaj z wartoscia. Nowa zmienna sekretna kosztuje wpis i pytanie przy
 * przegladzie — a nie nic.
 *
 * @var array<string, string>
 */
const WARTOSCI_NIETAJNE = [
    'APP_NAME' => 'Gabinet',
    'APP_ENV' => 'local',
    'APP_DEBUG' => 'true',
    'APP_URL' => 'http://localhost:8098',
    'APP_TIMEZONE' => 'UTC',
    'APP_LOCALE' => 'pl',
    'APP_FALLBACK_LOCALE' => 'pl',
    'APP_FAKER_LOCALE' => 'pl_PL',
    'GABINET_STREFA_PREZENTACJI' => 'Europe/Warsaw',
    'LOG_CHANNEL' => 'stack',
    'LOG_STACK' => 'single',
    'LOG_LEVEL' => 'debug',
    'DB_CONNECTION' => 'pgsql',
    'DB_HOST' => 'postgres',
    'DB_PORT' => '5432',
    'DB_DATABASE' => 'gabinet',
    'DB_USERNAME' => 'gabinet',
    'GABINET_PORT_POSTGRES' => '55442',
    'REDIS_CLIENT' => 'phpredis',
    'REDIS_HOST' => 'redis',
    'REDIS_PORT' => '6379',
    'REDIS_PASSWORD' => 'null',
    'GABINET_PORT_REDIS' => '56389',
    'QUEUE_CONNECTION' => 'redis',
    'CACHE_STORE' => 'redis',
    'SESSION_DRIVER' => 'redis',
    'SESSION_ENCRYPT' => 'true',
    'SESSION_LIFETIME' => '120',
    'SESSION_SECURE_COOKIE' => 'false',
    'BROADCAST_CONNECTION' => 'log',
    'HORIZON_PREFIX' => 'gabinet_horizon:',
    'GABINET_PREFIX' => 'gabinet',
    'GABINET_PORT_HTTP' => '8098',
    'KEYCLOAK_PUBLIC_ISSUER' => 'https://localhost:8443/realms/niepodzielni',
    'KEYCLOAK_INTERNAL_ISSUER' => 'http://keycloak:8080/realms/niepodzielni',
    'KEYCLOAK_CLIENT_ID' => 'gabinet',
    'KEYCLOAK_REDIRECT_URI' => 'http://localhost:8098/auth/callback',
    'KEYCLOAK_EXPECTED_AUDIENCE' => 'gabinet',
    'STRIPE_WALUTA' => 'PLN',
    'SMSAPI_NADAWCA' => 'Niepodzielni',
    'MAIL_MAILER' => 'log',
    'MAIL_FROM_ADDRESS' => 'kontakt@niepodzielni.com',
    'MAIL_FROM_NAME' => '"Fundacja Niepodzielni"',
    'GABINET_BLOKADA_WYSYLKI' => 'true',
    'REDIS_SESSION_DB' => '2',
    'SESSION_CONNECTION' => 'sesje',
];

/**
 * Czy wartosc WYGLADA jak sekret — ksztaltem, nie nazwa.
 *
 * Druga linia obrony dla wartosci JUZ dopuszczonych: gdyby ktos podmienil
 * `APP_NAME` na klucz API i dopisal go do allowlisty bez zastanowienia,
 * ksztalt nadal krzyknie. Prog jest jawny i tlumaczy sie sam: dlugosc
 * >= 24 znakow i co najmniej trzy rozne klasy znakow.
 */
function wygladaJakSekret(string $wartosc): bool
{
    // ADRESY nie sa sekretami, a maja dlugosc i mieszane klasy znakow —
    // pierwszy przebieg tej kontroli oskarzyl trzy URL-e Keycloaka.
    // Wyjatek jest KSZTALTOWY (schemat protokolu), nie listowy, wiec nie
    // wymaga utrzymania przy kazdym nowym adresie.
    if (preg_match('#^https?://#', $wartosc) === 1) {
        return false;
    }

    // Wartosc ze spacja to zdanie (np. nazwa nadawcy), nie token.
    if (preg_match('/\s/', $wartosc) === 1) {
        return false;
    }

    if (mb_strlen($wartosc) < 24) {
        return false;
    }

    $klasy = 0;

    foreach (['/[a-z]/', '/[A-Z]/', '/[0-9]/', '/[^A-Za-z0-9]/'] as $wzorzec) {
        if (preg_match($wzorzec, $wartosc) === 1) {
            $klasy++;
        }
    }

    return $klasy >= 3;
}

it('R9-3: KAŻDE przypisanie w .env.example jest puste albo jawnie uznane za nietajne', function (): void {
    $tresc = (string) file_get_contents(base_path('../.env.example'));

    expect($tresc)->not->toBe('', 'Nie da się odczytać `.env.example` — kontrola mierzy pustkę.');

    $obce = [];
    $zbadane = 0;

    foreach (explode("\n", $tresc) as $nr => $wiersz) {
        if (preg_match('/^([A-Z_][A-Z0-9_]*)=(.*)$/', trim($wiersz), $t) !== 1) {
            continue;
        }

        $zbadane++;
        $nazwa = $t[1];
        $wartosc = trim($t[2]);

        if ($wartosc === '') {
            continue;   // pusto = poprawnie
        }

        if (! array_key_exists($nazwa, WARTOSCI_NIETAJNE)) {
            $obce[] = sprintf('wiersz %d: %s ma WARTOŚĆ, a nie stoi na liście nietajnych', $nr + 1, $nazwa);

            continue;
        }

        if (WARTOSCI_NIETAJNE[$nazwa] !== $wartosc) {
            $obce[] = sprintf('wiersz %d: %s ma INNĄ wartość niż uzgodniona', $nr + 1, $nazwa);

            continue;
        }

        if (wygladaJakSekret($wartosc)) {
            $obce[] = sprintf('wiersz %d: %s ma wartość o KSZTAŁCIE sekretu', $nr + 1, $nazwa);
        }
    }

    expect($zbadane)->toBeGreaterThan(40, 'Sparsowałem za mało przypisań — parser rozjechał się z plikiem.');

    expect($obce)->toBe([], sprintf(
        "W `.env.example` STOI WARTOŚĆ, KTÓREJ NIKT ŚWIADOMIE NIE DOPUŚCIŁ:\n  %s\n\n".
        "Ten plik jest wzorcem BEZ WARTOŚCI (CLAUDE.md). Ochrona oparta na LIŚCIE NAZW\n".
        "przepuściła w rundzie 9 sekret pod nazwą spoza listy — przy zielonym gitleaksie\n".
        'i zielonej suicie. Dopisz wartość do `WARTOSCI_NIETAJNE` ŚWIADOMIE albo ją usuń.',
        implode("\n  ", $obce)
    ));
});

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

it('wymienia oba konta Stripe osobno i nie ma zmiennej jednokontowej', function (): void {
    // CLAUDE.md §3: fundacyjne i komercyjne to dwa niezależne konta —
    // osobne klucze, osobne webhooki, osobna rekoncyliacja.
    //
    // Wersja pierwotna liczyła tylko obecność dwóch nazw. Przeszłaby także
    // wtedy, gdyby ktoś dołożył wspólne `STRIPE_SECRET` i oba konta zaczęły
    // wskazywać ten sam Stripe — czyli przy dokładnie tej regresji, przed
    // którą ma chronić.
    $wzorzec = wczytajWzorzecSrodowiska();

    $wymagane = [
        'STRIPE_FUNDACJA_KEY', 'STRIPE_FUNDACJA_SECRET', 'STRIPE_FUNDACJA_WEBHOOK_SECRET',
        'STRIPE_KOMERCJA_KEY', 'STRIPE_KOMERCJA_SECRET', 'STRIPE_KOMERCJA_WEBHOOK_SECRET',
    ];

    expect(array_intersect_key($wzorzec, array_flip($wymagane)))->toHaveCount(6);

    // NEGATYW: żadnej zmiennej „jednokontowej", która skleiłaby oba konta.
    $jednokontowe = ['STRIPE_KEY', 'STRIPE_SECRET', 'STRIPE_WEBHOOK_SECRET'];

    foreach ($jednokontowe as $klucz) {
        expect($wzorzec)->not->toHaveKey($klucz, "Zmienna {$klucz} skleja oba konta Stripe");
    }
});

it('ignoruje .env w gitignore', function (): void {
    $gitignore = file_get_contents(korzenRepozytorium().'/.gitignore');

    expect($gitignore)->not->toBeFalse();

    $linie = array_map(trim(...), preg_split('/\R/', (string) $gitignore) ?: []);

    expect($linie)->toContain('.env');
});
