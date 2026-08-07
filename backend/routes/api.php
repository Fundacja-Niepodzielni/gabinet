<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|---------------------------------------------------------------------------
| API Gabinetu
|---------------------------------------------------------------------------
| Kontrakty publiczne (WordPress, aplikacja mobilna, hub) dochodzą w F8.
| Na razie stoi tu wyłącznie sonda wersji — używa jej bramka i monitoring.
|
| `gabinet.znacznik` odpowiada na pytanie „czyja to usługa", a nie „czy
| cokolwiek odpowiada". Lekcja z zespołu helpdesku: HTTP 200 to nie tożsamość
| — cudza usługa na zajętym porcie potrafi dać bramce fałszywe zielone.
| Bramka sprawdza ten ciąg, nie sam kod odpowiedzi.
*/

Route::get('/wersja', fn () => [
    'znacznik' => config('gabinet.znacznik'),
    'aplikacja' => config('app.name'),
    'srodowisko' => config('app.env'),
]);
