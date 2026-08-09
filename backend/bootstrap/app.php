<?php

declare(strict_types=1);

use App\Http\Middleware\SprawdzUniewaznienie;
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
        // ZASIĘG KONTROLI UNIEWAŻNIENIA WYZNACZA KONSTRUKCJA, NIE PAMIĘĆ AUTORA TRASY.
        //
        // Do 09.08 ten blok był PUSTY, a kontrola unieważnienia była usługą
        // wstrzykniętą do jednego kontrolera. Zmierzone (ODPOWIEDZ-031):
        // sprawdzała ją JEDNA TRASA Z 34. Każda nowa trasa chroniona, dopisana
        // poza tym kontrolerem, nie miała kontroli i nic tego nie zgłaszało.
        //
        // Odstępstwa są DANYMI (`App\Tozsamosc\WyjatkiUniewaznienia`), nie
        // nieobecnością — bo świadomy wyjątek i przeoczenie mają w kodzie ten
        // sam kształt, a nieobecność nie niesie intencji (klasa D6).
        $middleware->appendToGroup('web', SprawdzUniewaznienie::class);
        $middleware->appendToGroup('api', SprawdzUniewaznienie::class);
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
