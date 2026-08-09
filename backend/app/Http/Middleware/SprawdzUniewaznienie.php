<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Tozsamosc\RejestrSesji;
use App\Tozsamosc\SesjaKonta;
use App\Tozsamosc\WyjatkiUniewaznienia;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kontrola unieważnienia sesji SSO — **jako MIDDLEWARE, czyli konstrukcyjnie**.
 *
 * Do 09.08 ta kontrola była **usługą wstrzykniętą do jednego kontrolera** i wykonywała się
 * tam, gdzie kontroler ją zawołał. Zmierzone (`ODPOWIEDZ-031`): **jedna trasa z 34**,
 * przy PUSTYM bloku `withMiddleware`.
 *
 * Skutek: każda nowa trasa chroniona, dopisana poza tym kontrolerem, **nie miała kontroli
 * i nic tego nie zgłaszało**. To ta sama rodzina co `R6A-11` — mechanizm istniał, ale
 * **jego zasięg wyznaczała czyjaś pamięć, a nie konstrukcja**.
 *
 * Teraz zasięg wyznacza rejestracja w grupach `web` i `api`, a **odstępstwa są DANYMI**
 * (`WyjatkiUniewaznienia`), nie nieobecnością — klasa `D6`.
 *
 * @dowod: ZasiegUniewaznieniaTest — porównanie trzech zbiorów, z kontrolą pozytywną i NEGATYWNĄ.
 */
final class SprawdzUniewaznienie
{
    public function handle(Request $request, Closure $next): Response
    {
        $trasa = $request->route();
        $uri = $trasa !== null ? $trasa->uri() : '';

        // Wyjątek ZADEKLAROWANY — pomijamy świadomie i widocznie.
        if (WyjatkiUniewaznienia::zadeklarowany($uri)) {
            return $next($request);
        }

        $tozsamosc = SesjaKonta::odczytaj($request);

        // Brak tożsamości to NIE jest unieważnienie — to po prostu brak sesji.
        // Odrzucanie takich żądań tutaj zamieniłoby kontrolę bezpieczeństwa
        // w bramkę logowania, a to jest inna decyzja i inne miejsce.
        if ($tozsamosc === null) {
            return $next($request);
        }

        if (RejestrSesji::uniewazniona($tozsamosc->sid())) {
            SesjaKonta::zakoncz($request);

            return response()->json([
                'zalogowany' => false,
                'powod' => 'sesja unieważniona',
            ], 401);
        }

        return $next($request);
    }
}
