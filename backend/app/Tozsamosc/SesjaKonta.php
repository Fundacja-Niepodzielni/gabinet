<?php

declare(strict_types=1);

namespace App\Tozsamosc;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

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
     * Przyjmuje ZWERYFIKOWANE ROSZCZENIA, nie tablicę — naprawa R11-1.
     * Dopóki brała `array $dane`, wolno było napisać
     * `zaloz($request, ['sub' => $x])` z dowolnym `$x`, a jedyną obroną
     * był skaner kształtu kodu — który milczał SŁUSZNIE, gdy `$x`
     * pochodziło z pola KONTRAKTOWEGO (`code`).
     *
     * Teraz tablica nie jest tym typem: statyka to widzi, a przy pominięciu
     * statyki PHP rzuca `TypeError`. To jest różnica między „kontrola
     * zauważy" a „nie da się wyrazić".
     *
     * Mapowanie roszczeń na zawartość sesji stoi TUTAJ, u jedynego pisarza —
     * wcześniej budował je kontroler, a tablicę w kontrolerze wolno wypełnić
     * czymkolwiek.
     */
    public static function zaloz(
        Request $request,
        RoszczeniaZweryfikowane $id,
        RoszczeniaZweryfikowane $access,
        ?string $refreshToken,
    ): void {
        $surowe = Bramki::roleZAccessTokenu($access->wszystkie());

        $request->session()->put(self::KLUCZ, [
            // Wiązanie konta lokalnego po `sub`, NIGDY po e-mailu (CLAUDE.md §2).
            'sub' => $id->napis('sub'),
            'sid' => $id->napisAlbo('sid'),
            'login' => $id->napisAlbo('preferred_username'),
            'email' => $id->napisAlbo('email'),
            'email_potwierdzony' => $id->prawda('email_verified'),
            // Role WYŁĄCZNIE z access tokenu (kontrakt §2b). Zapisujemy obie
            // listy: surową (do logów i diagnozy) i tę, która realnie
            // autoryzuje — kompozyty rozwijają się w tokenie, a marker
            // `wymaga-2fa` przyjeżdża razem z rolą merytoryczną.
            'role_surowe' => $surowe,
            'role' => Bramki::roleAutoryzujace($surowe),
            'markery' => Bramki::markery($surowe),
            // ID TOKEN SZYFROWANY JAWNIE — niezależnie od `session.encrypt`.
            //
            // Refinement B7 z huba: poleganie na jednej fladze konfiguracji to
            // JEDNA FLAGA OD WYCIEKU. `SESSION_ENCRYPT` może ktoś wyłączyć na
            // stagingu albo przy debugowaniu — i wtedy e-mail pacjenta (claim
            // w ID tokenie) leży w Redisie jawnie, w systemie przetwarzającym
            // dane o zdrowiu (RODO art. 9).
            //
            // Napis bierzemy z OBIEKTU, nie z osobnego parametru: dzięki temu
            // zapisany `id_token_hint` jest z definicji tym tokenem, którego
            // podpis sprawdziliśmy.
            'id_token' => Crypt::encryptString($id->tokenSurowy()),
            // B8: bez refresh tokenu i bez `exp` nie da się przeliczyć ról
            // przed końcem sesji — a zamrożone role to wada bezpieczeństwa.
            // @dowod: OdebranieRoliTest — „odbiera dostęp, gdy Keycloak odbierze
            //         rolę — najpóźniej w oknie access tokenu".
            'refresh_token' => $refreshToken,
            'access_exp' => $access->liczba('exp'),
        ]);
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
