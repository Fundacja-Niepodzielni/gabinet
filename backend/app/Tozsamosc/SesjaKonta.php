<?php

declare(strict_types=1);

namespace App\Tozsamosc;

use App\Wsparcie\Typy;
use Illuminate\Http\Request;

/**
 * JEDYNY pisarz stanu tożsamości sesji (CLAUDE.md §2, D-2026-08-08-24).
 *
 * Dwie operacje, celowo o różnych wymaganiach wejścia:
 *
 *   · `zaloz()`      — TWORZY tożsamość. Wołana WYŁĄCZNIE z callbacku OIDC,
 *                      po pełnej walidacji ID tokenu i access tokenu.
 *   · `zaktualizuj()` — AKTUALIZUJE. Przyjmuje `TozsamoscSesji`, czyli dowód,
 *                      że tożsamość ISTNIAŁA. Bez takiego dowodu nie da się
 *                      jej wywołać — nie „nie wolno", tylko NIE DA SIĘ.
 *
 * Dlaczego to nie jest ten sam warunek co wcześniej: poprzednio odświeżanie
 * czytało tablicę i sprawdzało `if`, więc miało dostęp do zapisu niezależnie
 * od wyniku sprawdzenia. Teraz zapis aktualizujący wymaga WARTOŚCI, której
 * przy braku tożsamości po prostu nie ma.
 *
 * Zmierzone, dlaczego to było potrzebne: po usunięciu tożsamości z magazynu
 * żądanie wracało z kodem 200 i pełnymi uprawnieniami — odświeżanie
 * odtwarzało ją z refresh tokenu (noga 1, świat 2).
 */
final class SesjaKonta
{
    /** Klucz w sesji. Jedno miejsce, żeby dało się policzyć piszących. */
    public const KLUCZ = 'konta';

    /**
     * Zakłada tożsamość. WYŁĄCZNIE ścieżka logowania.
     *
     * @param  array<string, mixed>  $dane
     */
    public static function zaloz(Request $request, array $dane): void
    {
        $request->session()->put(self::KLUCZ, $dane);
    }

    /** Odczyt — `null`, gdy tożsamości NIE MA w magazynie. */
    public static function odczytaj(Request $request): ?TozsamoscSesji
    {
        return TozsamoscSesji::zMagazynu(Typy::mapa($request->session()->get(self::KLUCZ)));
    }

    /**
     * Aktualizacja ISTNIEJĄCEJ tożsamości.
     *
     * Pierwszy argument to nie „identyfikator, po którym znajdziemy rekord",
     * tylko SAM REKORD. Nie da się go zdobyć, jeśli tożsamości nie było.
     */
    public static function zaktualizuj(Request $request, TozsamoscSesji $nowa): void
    {
        $request->session()->put(self::KLUCZ, $nowa->dane);
    }

    /** Kończy sesję razem z refresh tokenem (standard B8). */
    public static function zakoncz(Request $request): void
    {
        $request->session()->flush();
        $request->session()->regenerate();
    }
}
