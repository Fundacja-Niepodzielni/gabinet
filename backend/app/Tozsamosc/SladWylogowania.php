<?php

declare(strict_types=1);

namespace App\Tozsamosc;

use App\Wsparcie\Typy;
use Illuminate\Support\Facades\File;

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
 *
 * ŚCIEŻKA NIEZALEŻNA OD PRZEDMIOTU (reguła C1 zespołu helpdesku):
 *
 *   KONTROLA DZIELĄCA MECHANIZM ZE SWOIM PRZEDMIOTEM JEST W TYM MECHANIZMIE
 *   NIEFALSYFIKOWALNA.
 *
 * Pierwsza wersja tego śladu trzymała liczniki w CACHE'U — a `RejestrSesji`,
 * czyli obserwowany przedmiot, też mieszka w cache'u. Awaria albo wyczyszczenie
 * cache'u kasowały JEDNO I DRUGIE naraz, więc kontrola nie odróżniała „nie było
 * czego kasować" od „mechanizm padł". Zdarzyło się to w naszym własnym teście:
 * `Cache::flush()` usunął cel perturbacji, a licznik pokazywał spójne zero.
 *
 * Dlatego ślad idzie do PLIKU. To osobny mechanizm: perturbacja cache'u nie
 * znika tą samą drogą, którą kontrola patrzy.
 */
/*
 * SRODOWISKIEM JEST TEZ UZYTKOWNIK PROCESU (N-14, zamkniete 12.08).
 *
 * Naprawa reguly C1 (slad wyprowadzony z cache'u do pliku) otworzyla inna
 * droge awarii, o poziom nizej. Zmierzone: testy biegna przez
 * `docker compose exec`, czyli jako ROOT, a zadania obsluguje WWW-DATA.
 * Katalog sladu powstawal leniwie — u tego, kto pisal pierwszy — wiec
 * w prawdziwym procesie zapis CICHO nie dochodzil (`File::put()` ostrzega
 * i zwraca, NIE rzuca), a odczyt UDAWAL SIE i oddawal nieswieza liczbe
 * z innego procesu. Przyrzad diagnostyczny zwracajacy stara liczbe jest
 * GORSZY od zwracajacego zero, bo WYGLADA JAK POMIAR.
 *
 * Dwie strony naprawy, bo jedna by nie wystarczyla:
 *   1. SRODOWISKO — katalog powstaje w `entrypoint.sh`, przed pierwszym
 *      zadaniem, wiec obejmuje go `chown www-data`;
 *   2. TYP — odczyt oddaje `null` (NIE WIEM), gdy magazyn nie jest
 *      zapisywalny dla TEGO procesu. Wolajacy musi obsluzyc oba stany,
 *      wiec nie da sie wziac ciszy za pomiar. Ta sama zasada, co
 *      `TozsamoscSesji` oddajace `null` zamiast pustej tozsamosci:
 *      BRAK WARTOSCI PRZED SPRAWDZANIEM WARTOSCI.
 *
 * @dowod: SladNieKlamieTest — katalog bez prawa zapisu daje NIE WIEM,
 *         nigdy liczby (kontrola pozytywna i negatywna).
 */
final class SladWylogowania
{
    private const WEJSCIA = 'wejscia';

    private const AWARIE = 'awarie';

    /** Odmowy zakończenia sesji — awaria, w której NIE dało się zweryfikować tokenu. */
    private const ODMOWY = 'odmowy';

    public static function wejscie(): void
    {
        self::zwieksz(self::WEJSCIA);
    }

    public static function awaria(string $klasaWyjatku): void
    {
        self::zwieksz(self::AWARIE);
        self::zapisz('ostatnia-awaria', $klasaWyjatku);
    }

    public static function odmowa(): void
    {
        self::zwieksz(self::ODMOWY);
    }

    /** @return int|null `null` znaczy NIE WIEM — magazyn nie przyjmuje zapisow tego procesu. */
    public static function odmowy(): ?int
    {
        return self::licznik(self::ODMOWY);
    }

    /** @return int|null `null` znaczy NIE WIEM — magazyn nie przyjmuje zapisow tego procesu. */
    public static function wejscia(): ?int
    {
        return self::licznik(self::WEJSCIA);
    }

    /** @return int|null `null` znaczy NIE WIEM — magazyn nie przyjmuje zapisow tego procesu. */
    public static function awarie(): ?int
    {
        return self::licznik(self::AWARIE);
    }

    public static function wyczysc(): void
    {
        File::deleteDirectory(self::katalog());
    }

    private static function katalog(): string
    {
        return storage_path('slad-wylogowania');
    }

    private static function zwieksz(string $nazwa): void
    {
        self::zapisz($nazwa, (string) (self::licznik($nazwa) + 1));
    }

    private static function zapisz(string $nazwa, string $wartosc): void
    {
        File::ensureDirectoryExists(self::katalog());
        File::put(self::katalog().'/'.$nazwa, $wartosc);
    }

    /**
     * Czy magazyn sladu przyjmuje zapisy PROCESU, ktory o to pyta.
     *
     * Sciezka NIEZALEZNA od samego zapisu: pytamy system plikow o uprawnienia,
     * zamiast wnioskowac z wyniku `File::put()`, ktory przy braku praw
     * ostrzega i zwraca, zamiast rzucic.
     */
    public static function zapisywalny(): bool
    {
        $katalog = self::katalog();

        return is_dir($katalog) ? is_writable($katalog) : is_writable(dirname($katalog));
    }

    private static function licznik(string $nazwa): ?int
    {
        // NIE WIEM przed liczba. Gdy magazyn nie przyjmuje zapisow TEGO procesu,
        // kazda odczytana wartosc pochodzi z innego procesu i innej chwili —
        // a to jest gorsze niz brak odpowiedzi, bo wyglada jak pomiar.
        if (! self::zapisywalny()) {
            return null;
        }

        $plik = self::katalog().'/'.$nazwa;

        return File::exists($plik) ? Typy::liczba(trim(File::get($plik))) : 0;
    }
}
