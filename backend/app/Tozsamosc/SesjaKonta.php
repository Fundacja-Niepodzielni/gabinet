<?php

declare(strict_types=1);

namespace App\Tozsamosc;

use Illuminate\Http\Request;

/**
 * JEDYNY pisarz stanu tożsamości sesji (CLAUDE.md §2, D-2026-08-08-24).
 *
 * Dwie operacje, celowo o różnych wymaganiach wejścia:
 *
 *   · `zaloz()`      — TWORZY tożsamość. Wołana WYŁĄCZNIE z callbacku OIDC,
 *                      po pełnej walidacji ID tokenu i access tokenu.
 *   · `zaktualizuj()` — AKTUALIZUJE. Przyjmuje `TozsamoscSesji`, czyli dowód,
 *                      że tożsamość ISTNIAŁA. Bez takiego dowodu wywołanie
 *                      wymaga obejścia typu.
 *
 * @dowod: R6A-3 — pierwotne „NIE DA SIĘ" obalone; obejście istnieje
 *         (`Reflection`, `unserialize`, publiczna fabryka `zMagazynu()`).
 *
 * Dlaczego to nie jest ten sam warunek co wcześniej: poprzednio odświeżanie
 * czytało tablicę i sprawdzało `if`, więc miało dostęp do zapisu niezależnie
 * od wyniku sprawdzenia. Teraz zapis aktualizujący wymaga WARTOŚCI, której
 * przy braku tożsamości po prostu nie ma.
 *
 * Co jest ZMIERZONE, a co NIE: zmierzone jest to, że pisarzy klucza `konta`
 * było DWÓCH (`LogowanieController` i `OdswiezanieSesji` pisały niezależnie),
 * co samo w sobie łamie §2. NIE jest zmierzone, że odświeżanie kiedykolwiek
 * tożsamość odtworzyło — ten wniosek postawiono z migawek magazynu i OBALONO
 * pomiarem kontrolnym po tej przebudowie (liczby identyczne). Noga 1 pary
 * negatywnej BLK-22 pozostaje NIEROZSTRZYGNIĘTA.
 */
final class SesjaKonta
{
    /**
     * Klucz w sesji — ODWOŁANIE, nie druga definicja.
     *
     * Dwa opisy tej samej rzeczy rozjeżdżają się po cichu (klasa P3), więc
     * wartość mieszka w `TozsamoscSesji`, a tutaj stoi wyłącznie odsyłacz.
     */
    public const KLUCZ = TozsamoscSesji::KLUCZ;

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
        return TozsamoscSesji::zZadania($request);
    }

    /**
     * Aktualizacja ISTNIEJĄCEJ tożsamości.
     *
     * Pierwszy argument to nie „identyfikator, po którym znajdziemy rekord",
     * tylko SAM REKORD — a ten pochodzi z magazynu.
     *
     * @dowod: R6A-3 — „nie da się zdobyć" było za mocne.
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

    /**
     * Kasowanie tożsamości na ścieżce WYLOGOWANIA użytkownika.
     *
     * Różni się od `zakoncz()` świadomie: tam sesja ma żyć dalej (użytkownik
     * został tylko pozbawiony tożsamości), tutaj ma zniknąć razem z ciasteczkiem.
     * Do 12.08 ta ścieżka stała POZA fasadą, wprost w kontrolerze — znalezisko
     * R6A-12. Kasowanie tożsamości jest operacją na tożsamości, więc mieszka
     * tam, gdzie reszta.
     *
     * @dowod: WaskieGardloTozsamosciTest — „zbiór plików sięgających po klucz
     *         tożsamości to ALLOWLISTA".
     */
    public static function wyloguj(Request $request): void
    {
        $request->session()->flush();
        $request->session()->invalidate();
    }
}
