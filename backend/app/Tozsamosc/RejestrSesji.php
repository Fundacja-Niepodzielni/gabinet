<?php

declare(strict_types=1);

namespace App\Tozsamosc;

use App\Wsparcie\Typy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

/**
 * Mapa `sid` (identyfikator sesji SSO w IdP) → identyfikatory sesji lokalnych.
 *
 * Bez niej nie da się obsłużyć back-channel logout: żądanie od IdP przychodzi
 * BEZ ciasteczka użytkownika, więc `sid` jest jedynym kluczem, po którym
 * potrafimy znaleźć sesję do skasowania (kontrakt §4.1 pkt 1 i §4.5).
 */
final class RejestrSesji
{
    /** Rejestr żyje tak długo, jak najdłuższa sesja SSO w IdP (max 24 h). */
    private const CZAS_ZYCIA_SEKUND = 86400;

    public static function zapamietaj(string $sid, string $idSesjiLokalnej): void
    {
        $identyfikatory = self::odczytaj($sid);

        if (! in_array($idSesjiLokalnej, $identyfikatory, true)) {
            $identyfikatory[] = $idSesjiLokalnej;
        }

        Cache::put(self::klucz($sid), $identyfikatory, self::CZAS_ZYCIA_SEKUND);
    }

    /**
     * Kasuje wszystkie sesje lokalne powiązane z danym `sid`.
     *
     * @return int liczba skasowanych sesji
     */
    public static function zakoncz(string $sid): int
    {
        $identyfikatory = self::odczytaj($sid);
        $uchwyt = Session::getHandler();
        $skasowane = 0;

        foreach ($identyfikatory as $id) {
            if ($uchwyt->destroy($id)) {
                $skasowane++;
            }
        }

        Cache::forget(self::klucz($sid));

        return $skasowane;
    }

    /**
     * @return list<string>
     */
    public static function odczytaj(string $sid): array
    {
        return Typy::listaNapisow(Cache::get(self::klucz($sid), []));
    }

    private static function klucz(string $sid): string
    {
        return 'konta:sid:'.hash('sha256', $sid);
    }
}
