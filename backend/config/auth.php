<?php

declare(strict_types=1);

/*
|---------------------------------------------------------------------------
| Uwierzytelnianie
|---------------------------------------------------------------------------
| CLAUDE.md §2: logowanie WYŁĄCZNIE przez Konta Niepodzielni (Keycloak).
| W tym systemie NIE MA własnych haseł, resetu hasła ani ekranu logowania.
|
| Domyślny plik Laravela definiował brokera `passwords` (tabela
| `password_reset_tokens`), strażnika sesyjnego nad providerem Eloquent
| i `password_timeout`. Wszystko to zostało usunięte ŚWIADOMIE — pusta
| sekcja `passwords` jest tu deklaracją, którą sprawdza test regresyjny
| `tests/Feature/BrakWlasnychHaselTest.php`.
|
| Tożsamość zalogowanego trzyma sesja (`konta` w sesji, `App\Tozsamosc\*`),
| a nie strażnik Laravela. Gdyby kiedyś potrzebny był strażnik (np. dla
| tokenów API w F8), wchodzi TYLKO jako provider czytający `keycloak_sub`,
| nigdy jako provider haseł.
*/

return [

    'defaults' => [
        'guard' => 'web',
    ],

    'guards' => [],

    'providers' => [],

    // Pusta i taka ma zostać. Broker resetu haseł nie ma tu czego resetować.
    'passwords' => [],

];
