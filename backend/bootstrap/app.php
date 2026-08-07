<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create()
    // Jeden plik `.env` na całe repozytorium — leży w KORZENIU, nie w `backend/`.
    // Powód: ten sam plik konfiguruje aplikację i `docker-compose.yml` (hasło do
    // bazy, porty, klucze). Dwa pliki rozjeżdżają się po pierwszej zmianie hasła
    // i objawia się to błędem, który wygląda jak awaria Dockera.
    // Uzasadnienie i skutki: docs/DECYZJE.md, decyzja D-2026-08-07-03.
    ->useEnvironmentPath(dirname(__DIR__, 2));
