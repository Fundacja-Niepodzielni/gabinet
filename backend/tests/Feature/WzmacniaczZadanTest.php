<?php

declare(strict_types=1);

use App\Tozsamosc\KontaOidc;
use Illuminate\Cache\RedisStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Gabinet NIE MOŻE być wzmacniaczem żądań do Kont Niepodzielni.
 *
 * Lekcja zespołu hubu (perturbacje JWKS): `kid` w nagłówku tokenu to dane
 * wejściowe atakującego — niezweryfikowane, bo weryfikacja podpisu dopiero
 * przed nami. Jeśli nieznany `kid` wyzwala pobranie JWKS, to strumień tokenów
 * z losowym `kid` jest strumieniem żądań do IdP, a endpoint back-channel
 * logout jest publiczny i nieuwierzytelniony. Napastnik nie potrzebuje konta.
 *
 * Testy liczą ŻĄDANIA (CLAUDE.md §15), nie sprawdzają obecności bramki.
 */
function policzZadaniaJwks(): int
{
    $ile = 0;

    foreach (Http::recorded() as [$zadanie]) {
        if (str_contains($zadanie->url(), '/protocol/openid-connect/certs')) {
            $ile++;
        }
    }

    return $ile;
}

beforeEach(function (): void {
    Cache::flush();
    // Ta sama atrapa IdP, której używa `LogowanieTest` — atrapowany jest
    // wyłącznie ruch sieciowy, warstwa `KontaOidc` działa naprawdę.
    udawajIdp();
});

it('sto tokenów z nieznanym kid wywołuje DOKŁADNIE JEDNO pobranie JWKS', function (): void {
    $oidc = app(KontaOidc::class);

    // Rozgrzewka napełnia cache. ŚWIADOMIE `jwks()`, a nie `jwksDlaKid()`
    // z wymyślonym kluczem: ten drugi sam zużyłby okno bramki i test mierzyłby
    // rozgrzewkę zamiast ataku. (Pierwsza wersja właśnie tak robiła i pokazała
    // zero odświeżeń — czyli mierzyła nie to zjawisko.)
    $oidc->jwks();
    $poRozgrzewce = policzZadaniaJwks();

    for ($i = 0; $i < 100; $i++) {
        $oidc->jwksDlaKid("nieznany-kid-{$i}");
    }

    $odswiezenia = policzZadaniaJwks() - $poRozgrzewce;

    // JEDNO odświeżenie na okno — nie sto. Gdyby bramki nie było, byłoby 100
    // żądań do Kont Niepodzielni z jednego strumienia podrobionych tokenów.
    expect($odswiezenia)->toBe(1);
});

it('nie odpytuje IdP w ogóle, gdy kid jest znany', function (): void {
    $oidc = app(KontaOidc::class);

    $jwks = $oidc->jwks();
    /** @var array<int, array<string, string>> $klucze */
    $klucze = $jwks['keys'];
    $znany = $klucze[0]['kid'];
    $poRozgrzewce = policzZadaniaJwks();

    for ($i = 0; $i < 20; $i++) {
        $oidc->jwksDlaKid($znany);
    }

    expect(policzZadaniaJwks() - $poRozgrzewce)->toBe(0);
});

it('po wygaśnięciu okna wpuszcza kolejne JEDNO odświeżenie — bramka nie blokuje rotacji kluczy', function (): void {
    // Kierunek odwrotny. Bez niego „jedno żądanie na sto" przechodzi także
    // wtedy, gdy odświeżanie zablokowaliśmy NA ZAWSZE — a wtedy rotacja klucza
    // w IdP wywracałaby logowanie do końca życia cache'u.
    config(['konta.jwks_odstep_odswiezania_s' => 1]);

    $oidc = app(KontaOidc::class);
    $oidc->jwks();
    $poRozgrzewce = policzZadaniaJwks();

    $oidc->jwksDlaKid('nieznany-a');
    expect(policzZadaniaJwks() - $poRozgrzewce)->toBe(1);

    $oidc->jwksDlaKid('nieznany-b');
    expect(policzZadaniaJwks() - $poRozgrzewce)->toBe(1);

    // Znacznik świeżości wygasa — dopiero teraz wolno spróbować ponownie.
    Cache::forget('konta:jwks-odswiezone');

    $oidc->jwksDlaKid('nieznany-c');
    expect(policzZadaniaJwks() - $poRozgrzewce)->toBe(2);
});

it('ATOMOWOŚĆ bramki mierzona na PRAWDZIWYM sterowniku, nie na atrapie', function (): void {
    // R6B-14. Bramka częstotliwości JWKS stoi na `Cache::add()`, czyli na
    // operacji ATOMOWEJ. Atomowość ma sens wyłącznie MIĘDZY PROCESAMI —
    // a `phpunit.xml` wymusza `CACHE_STORE=array`, czyli magazyn żyjący
    // w pamięci JEDNEGO procesu. Test „stu tokenów" przechodził więc
    // w środowisku, w którym badane zjawisko NIE ZACHODZI: tam nie ma
    // dwóch procesów, między którymi mógłby zajść wyścig.
    //
    // Kontrola dowodząca własności, której jej środowisko nie ma, jest
    // atrapą dowodu. Przełączamy się więc na sterownik PRODUKCYJNY.
    config(['cache.default' => 'redis']);

    $klucz = 'konta:proba-atomowosci-'.bin2hex(random_bytes(4));

    try {
        // ODCZYT BAZOWY: pierwszy `add` na nieistniejącym kluczu ma się UDAĆ.
        expect(Cache::add($klucz, 1, 60))->toBeTrue(
            'Pierwsze `Cache::add` nie zadziałało — mierzymy zepsuty magazyn, nie atomowość.'
        );

        // SEDNO: drugi `add` na TYM SAMYM kluczu MUSI odmówić. To jest cała
        // treść bramki częstotliwości — jeżeli tu wyjdzie `true`, sto tokenów
        // z nieznanym `kid` da sto żądań do Kont Niepodzielni.
        expect(Cache::add($klucz, 1, 60))->toBeFalse(
            'Drugie `Cache::add` na tym samym kluczu ZADZIAŁAŁO. Bramka częstotliwości '.
            'nie jest wtedy bramką: publiczny, nieuwierzytelniony punkt back-channel '.
            'logout zamienia się w wzmacniacz żądań do IdP.'
        );

        // KONTROLA POZYTYWNA sterownika: sprawdzamy, że NAPRAWDĘ mierzyliśmy
        // Redisa, a nie że `config()` przestawiło etykietę bez skutku.
        expect(Cache::getStore())->toBeInstanceOf(RedisStore::class);
    } finally {
        Cache::forget($klucz);
        config(['cache.default' => 'array']);
    }
});

it('ZATRUTY cache JWKS nie odbudowuje się sam w oknie bramki', function (): void {
    // Wzorzec perturbacji mechanizmów samonaprawczych: NIE kasujemy cache'u,
    // bo wtedy mierzylibyśmy tempo odbudowy, a nie czujność bramki. Podmieniamy
    // TREŚĆ pod tym samym kluczem i sprawdzamy, że samonaprawa się NIE
    // uruchomiła — licznik odświeżeń zostaje na zerze.
    $oidc = app(KontaOidc::class);
    $oidc->jwks();

    Cache::put('konta:jwks', ['keys' => [['kid' => 'podrzucony', 'kty' => 'RSA']]], 300);
    // Znacznik świeżości udaje, że odświeżaliśmy przed chwilą.
    Cache::put('konta:jwks-odswiezone', 1, 60);

    $poZatruciu = policzZadaniaJwks();
    $wynik = $oidc->jwksDlaKid('kid-ktorego-nie-ma-w-zatrutym-zestawie');

    /** @var array<int, array<string, string>> $klucze */
    $klucze = $wynik['keys'];

    expect(policzZadaniaJwks() - $poZatruciu)->toBe(0)
        ->and($klucze[0]['kid'])->toBe('podrzucony');
});
