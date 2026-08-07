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
