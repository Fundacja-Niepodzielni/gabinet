<?php

declare(strict_types=1);

namespace App\Tozsamosc;

/**
 * WYJĄTKI OD KONTROLI UNIEWAŻNIENIA — **ZADEKLAROWANE JAKO DANE, nie jako nieobecność**.
 *
 * Klasa `D6`: **świadomy wyjątek i zapomnienie o regule mają w kodzie TEN SAM KSZTAŁT —
 * nieobecność. Nieobecność nie niesie intencji, więc nie da się jej zweryfikować ani
 * odróżnić od błędu.**
 *
 * Zmierzone 09.08 (`ODPOWIEDZ-031`): kontrolę unieważnienia stosowała **jedna trasa z 34**,
 * a pozostałe 33 nie były **nigdzie** zadeklarowane jako wyjątki. Część z nich to wyjątki
 * słuszne — ale to była **moja wiedza, nie własność kodu**. Przy kilkunastu uzasadnionych
 * brakach **kolejny, przypadkowy, jest niewidoczny**.
 *
 * ================== KOSZT WYJĄTKU MUSI RÓWNAĆ SIĘ KOSZTOWI ZGODNOŚCI ==================
 *
 * Zmierzone u siebie tego samego dnia (`ODPOWIEDZ-032`): w rejestrze retencji wpis wymagał
 * podstawy i opisu dłuższego niż 20 znaków, a wpis na liście wyjątków **nie wymagał niczego** —
 * czyli **dopisanie nazwy do wyjątków było najtańszym sposobem wyciszenia kontroli**.
 * Dlatego tutaj każdy wyjątek niesie **powód** i **warunek znoszący**, oba wymagane kontrolą.
 *
 * **Warunek znoszący mówi, CO PRZESTAJE CZYNIĆ TEN WYJĄTEK SŁUSZNYM.** Powód bez warunku
 * znoszącego jest wiecznym zwolnieniem.
 *
 * @dowod: ZasiegUniewaznieniaTest — „trasa bez kontroli i bez deklaracji jest CZERWONA”
 */
final class WyjatkiUniewaznienia
{
    /**
     * Trasy, które kontroli unieważnienia **celowo** nie stosują.
     *
     * Klucz to `uri` trasy z routera — nie nazwa, bo nazwy bywają puste.
     *
     * @return array<string, array{powod: string, warunek_znoszacy: string}>
     */
    public static function wpisy(): array
    {
        return [
            '/' => [
                'powod' => 'Publiczny JSON o aplikacji, bez tożsamości i bez danych osobowych.',
                'warunek_znoszacy' => 'Gdy zacznie zwracać cokolwiek zależnego od zalogowanego użytkownika.',
            ],
            'api/wersja' => [
                'powod' => 'Publiczny numer wersji, celowo dostępny bez logowania (używa go bramka).',
                'warunek_znoszacy' => 'Gdy zacznie ujawniać stan wewnętrzny albo dane instancji.',
            ],
            'up' => [
                'powod' => 'Sonda zdrowia frameworka. Nie ma tożsamości i nie może jej wymagać, bo bada żywotność procesu.',
                'warunek_znoszacy' => 'Gdy zacznie raportować cokolwiek poza „żyję/nie żyję".',
            ],
            'storage/{path}' => [
                'powod' => 'Trasa plików statycznych frameworka, rejestrowana POZA grupami `web` i `api` — nie da się jej objąć grupą, a nie serwuje danych osobowych.',
                'warunek_znoszacy' => 'Gdy zaczniemy trzymać w `storage` cokolwiek z kartotek albo załączników pacjenta — wtedy potrzebuje własnego strażnika, nie tego.',
            ],
            'auth/login' => [
                'powod' => 'Początek przepływu logowania — w tym momencie sesji jeszcze NIE MA, więc nie ma czego unieważniać.',
                'warunek_znoszacy' => 'Gdy zacznie przyjmować istniejącą sesję zamiast ją tworzyć.',
            ],
            'auth/callback' => [
                'powod' => 'Powrót z IdP zakładający tożsamość. Sprawdzanie unieważnienia PRZED jej założeniem odrzucałoby każde logowanie.',
                'warunek_znoszacy' => 'Gdy zacznie obsługiwać sesje już istniejące (np. dowiązanie drugiego konta).',
            ],
            'auth/wyloguj' => [
                'powod' => 'Wylogowanie ma zadziałać także dla sesji już unieważnionej — inaczej nie da się posprzątać po sobie.',
                'warunek_znoszacy' => 'Gdyby zaczęło zwracać dane użytkownika zamiast go wylogowywać.',
            ],
            'oidc/backchannel-logout' => [
                'powod' => 'Żądanie od IdP, BEZ ciasteczka użytkownika. To ono TWORZY unieważnienie; własna tożsamość go nie dotyczy.',
                'warunek_znoszacy' => 'Gdyby zaczęło działać na sesji wywołującego zamiast na `sid` z logout tokenu.',
            ],
        ];
    }

    /** Czy trasa o tym `uri` ma ZADEKLAROWANY wyjątek. */
    public static function zadeklarowany(string $uri): bool
    {
        return array_key_exists(ltrim($uri, '/') === '' ? '/' : $uri, self::wpisy());
    }
}
