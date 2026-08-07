<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|---------------------------------------------------------------------------
| API Gabinetu
|---------------------------------------------------------------------------
| Kontrakty publiczne (WordPress, aplikacja mobilna, hub) dochodzą w F8.
| Na razie stoi tu wyłącznie sonda wersji — używa jej bramka F0 i monitoring.
*/

Route::get('/wersja', function () {
    return [
        'aplikacja' => config('app.name'),
        'srodowisko' => config('app.env'),
    ];
});
