<?php

declare(strict_types=1);

/*
|---------------------------------------------------------------------------
| Konta Niepodzielni — integracja OIDC
|---------------------------------------------------------------------------
| Kontrakt: repo `konta`, docs/INTEGRACJA-KONTRAKT.md (wartości zweryfikowane
| na działającej instancji Keycloaka 26.7.0).
|
| CLAUDE.md §2: logowanie WYŁĄCZNIE przez Konta Niepodzielni. W tym systemie
| nie ma i nie będzie własnych haseł ani własnego ekranu logowania.
*/

return [

    /*
    | DWA ADRESY IdP — kontrakt §3a.
    |
    | `publiczny`  widzi PRZEGLĄDARKA (authorization_endpoint, end_session)
    |              i TYLKO względem niego wolno porównywać `iss` w tokenie.
    | `wewnetrzny` widzi SERWER (token_endpoint, JWKS, userinfo). W Dockerze
    |              to nazwa usługi, nieosiągalna dla przeglądarki.
    |
    | Walidacja `iss` względem adresu wewnętrznego jest fikcją — dlatego
    | `KontaOidc` przerywa start, gdy discovery zwróci inny issuer niż publiczny.
    */
    'issuer_publiczny' => rtrim((string) env('KEYCLOAK_PUBLIC_ISSUER', ''), '/'),
    'issuer_wewnetrzny' => rtrim((string) env('KEYCLOAK_INTERNAL_ISSUER', ''), '/'),

    'client_id' => (string) env('KEYCLOAK_CLIENT_ID', 'gabinet'),
    'client_secret' => (string) env('KEYCLOAK_CLIENT_SECRET', ''),
    'redirect_uri' => (string) env('KEYCLOAK_REDIRECT_URI', ''),

    /*
    | Audiencja wymagana w access tokenie (kontrakt §4.4). Token wystawiony dla
    | innego klienta MUSI dostać 401 — `azp` NIE zastępuje `aud`.
    */
    'wymagana_audiencja' => (string) env('KEYCLOAK_EXPECTED_AUDIENCE', 'gabinet'),

    /*
    | Tolerancja zegara przy exp/iat (sekundy). Kontrakt: ≤ 30 s.
    */
    'tolerancja_zegara' => (int) env('KEYCLOAK_CLOCK_LEEWAY', 30),

    /*
    | Lokalne CA (stos deweloperski `konta` używa CA Caddy'ego bez ACME).
    | Puste = zaufanie systemowe. NIGDY nie wpisujemy tu „wyłącz weryfikację".
    */
    'ca_bundle' => (string) env('KEYCLOAK_CA_BUNDLE', ''),

    /*
    | Ile sekund trzymamy discovery i JWKS w cache. Kontrakt §6 pkt 2: tokeny
    | weryfikujemy podpisem z JWKS, nie introspekcją na każde żądanie.
    */
    'cache_sekundy' => (int) env('KEYCLOAK_CACHE_SEKUNDY', 300),

    /*
    |-----------------------------------------------------------------------
    | Odstęp między odświeżeniami JWKS wywołanymi NIEZNANYM `kid`
    |-----------------------------------------------------------------------
    | `kid` przychodzi w nagłówku tokenu, czyli od nadawcy żądania i PRZED
    | weryfikacją podpisu. Bez tego odstępu strumień tokenów z losowym `kid`
    | zamieniłby Gabinet we wzmacniacz żądań do Kont Niepodzielni — patrz
    | `KontaOidc::jwksDlaKid()` i `WzmacniaczZadanTest`.
    */
    'jwks_odstep_odswiezania_s' => (int) env('KEYCLOAK_JWKS_ODSTEP_S', 60),

    /*
    |-----------------------------------------------------------------------
    | BIAŁA LISTA ról autoryzujących
    |-----------------------------------------------------------------------
    | Siedem ról merytorycznych realmu (kontrakt §2). WYŁĄCZNIE one mogą
    | cokolwiek otworzyć.
    |
    | Powód jest zmierzony na żywym realmie: **kompozyty rozwijają się
    | w tokenie**. Access token koordynatora niesie w `realm_access.roles`
    | zarówno `koordynator`, jak i marker techniczny `wymaga-2fa` — a obok
    | nich role wbudowane Keycloaka (`offline_access`, `uma_authorization`,
    | `default-roles-niepodzielni`).
    |
    | Logika oparta na „wszystkich rolach z tokenu" wpuszcza więc markery
    | techniczne do uprawnień. Biała lista sprawia, że jest to niemożliwe
    | KONSTRUKCYJNIE, a nie tylko dlatego, że nikt nie wpisał markera do
    | mapy bramek.
    */
    'role_autoryzujace' => [
        'pacjent',
        'psycholog',
        'prowadzacy',
        'wolontariusz',
        'koordynator',
        'redaktor',
        'admin-fundacja',
    ],

    /*
    | Markery techniczne — czytamy je, ale NIGDY nie autoryzują.
    | `wymaga-2fa` to kompozyt wymuszający drugi składnik po stronie IdP;
    | dla aplikacji jest informacją o koncie, nie uprawnieniem.
    */
    'markery' => [
        'wymaga-2fa',
    ],

    /*
    |-----------------------------------------------------------------------
    | Bramki aplikacji
    |-----------------------------------------------------------------------
    | „IdP mówi kim jesteś, aplikacja decyduje co możesz" (kontrakt §2).
    | W realmie jest 7 grubych ról; tutaj — uprawnienia Gabinetu.
    |
    | PUSTA lista ról to POPRAWNY stan konta, nie błąd logowania: konto
    | założone samodzielnie ma wyłącznie `default-roles-niepodzielni`.
    */
    'bramki' => [
        'panel.specjalisty' => ['psycholog', 'koordynator', 'admin-fundacja'],
        'panel.koordynacji' => ['koordynator', 'admin-fundacja'],
        'panel.pacjenta' => ['pacjent'],
        'rozliczenia.akceptuj' => ['koordynator', 'admin-fundacja'],
        'dziennik.zapisz' => ['koordynator', 'admin-fundacja'],
    ],

];
