<?php

declare(strict_types=1);

use App\Http\Controllers\BackchannelLogoutController;
use App\Http\Controllers\LogowanieController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'aplikacja' => config('app.name'),
    'informacja' => 'Gabinet — API. Interfejs użytkownika dochodzi w F7.',
]));

/*
|---------------------------------------------------------------------------
| Logowanie przez Konta Niepodzielni
|---------------------------------------------------------------------------
| CLAUDE.md §2: żadnych własnych haseł, żadnego własnego ekranu logowania.
*/
Route::get('/auth/login', [LogowanieController::class, 'zaloguj'])->name('konta.login');
Route::get('/auth/callback', [LogowanieController::class, 'powrot'])->name('konta.callback');
Route::get('/auth/wyloguj', [LogowanieController::class, 'wyloguj'])->name('konta.wyloguj');
Route::get('/auth/ja', [LogowanieController::class, 'ja'])->name('konta.ja');

// Wywołuje SERWER IdP: bez ciasteczka, bez sesji, bez CSRF (kontrakt §4.5).
// Adres musi być rozwiązywalny Z KONTENERA Keycloaka — najczęstsza usterka
// wdrożeniowa objawia się CISZĄ, nie błędem.
Route::post('/oidc/backchannel-logout', [BackchannelLogoutController::class, 'przyjmij'])
    ->name('konta.backchannel-logout')
    ->withoutMiddleware([ValidateCsrfToken::class]);
