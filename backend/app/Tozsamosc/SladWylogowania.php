<?php

declare(strict_types=1);

namespace App\Tozsamosc;

use App\Wsparcie\Typy;
use Illuminate\Support\Facades\Cache;

/**
 * Ślad wejścia do handlera back-channel logout.
 *
 * Po co osobny byt, skoro jest już licznik skasowanych sesji: bo ten licznik
 * MYLI DWA RÓŻNE STANY. „Zero skasowanych sesji" znaczy zarówno „token do nas
 * nie dotarł", jak i „dotarł, ale handler padł, zanim cokolwiek zrobił".
 * Przyrząd, który nie odróżnia tych przypadków, jest słaby dokładnie tam, gdzie
 * potrzebny najbardziej — przy diagnozie awarii wylogowania.
 *
 * Dlatego mamy trzy niezależne sygnały:
 *   · `wejscia`        — ile razy handler w ogóle wystartował,
 *   · `awarie`         — ile razy walidacja rzuciła wyjątkiem,
 *   · skasowane sesje  — zwracane w odpowiedzi handlera.
 *
 * Znacznik zapisujemy PRZED jakąkolwiek operacją mogącą rzucić (sieć, JWKS,
 * discovery). Inaczej nie odróżnimy „nie weszło" od „weszło i padło" — czyli
 * dokładnie tego, po co ten byt istnieje.
 */
final class SladWylogowania
{
    private const KLUCZ_WEJSCIA = 'gabinet:wylogowanie:wejscia';

    private const KLUCZ_AWARIE = 'gabinet:wylogowanie:awarie';

    /** Ślad żyje dobę — tyle, ile najdłuższa sesja SSO. */
    private const CZAS_ZYCIA_S = 86400;

    public static function wejscie(): void
    {
        Cache::put(self::KLUCZ_WEJSCIA, self::wejscia() + 1, self::CZAS_ZYCIA_S);
    }

    public static function awaria(string $klasaWyjatku): void
    {
        Cache::put(self::KLUCZ_AWARIE, self::awarie() + 1, self::CZAS_ZYCIA_S);
        Cache::put(self::KLUCZ_AWARIE.':ostatnia', $klasaWyjatku, self::CZAS_ZYCIA_S);
    }

    public static function wejscia(): int
    {
        return Typy::liczba(Cache::get(self::KLUCZ_WEJSCIA));
    }

    public static function awarie(): int
    {
        return Typy::liczba(Cache::get(self::KLUCZ_AWARIE));
    }

    public static function wyczysc(): void
    {
        Cache::forget(self::KLUCZ_WEJSCIA);
        Cache::forget(self::KLUCZ_AWARIE);
        Cache::forget(self::KLUCZ_AWARIE.':ostatnia');
    }
}
