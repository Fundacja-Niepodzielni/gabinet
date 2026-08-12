<?php

declare(strict_types=1);

use App\Tozsamosc\RejestrSesji;
use App\Tozsamosc\SladWylogowania;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * KLASA 4 — magazyn niosący asercję bezpieczeństwa musi przeżyć zdarzenia PROZAICZNE.
 *
 * Wspólny mechanizm trzech znalezisk rundy 6: ścieżka bezpieczeństwa wykonuje
 * zapis, którego NIEPOWODZENIE NIE MA ZDEFINIOWANEGO SKUTKU. Nie rzuca, nie
 * asertuje, nie zostawia śladu — a negatywna asercja („ten `sid` jest martwy",
 * „harmonogram żyje", „handler wystartował") znika po cichu i objawem jest
 * BRAK OBJAWU.
 *
 * Cztery wymagania trwałości zastosowaliśmy 09.08 do ZNACZNIKA unieważnienia
 * i NIE zastosowaliśmy ich do trzech rzeczy obok:
 *
 *   · R6B-9  — mapa `sid → sesje`, bez której back-channel logout nie znajdzie
 *              ŻADNEJ sesji. Utrata = `skasowane_sesje = 0` po cichu. FAIL-OPEN.
 *   · R6B-10 — puls harmonogramu, czyli sygnał zdrowia. FAIL-CLOSED, więc lżejsze:
 *              kosztem jest fałszywy alarm, nie dziura. Fałszywy alarm uczy
 *              jednak ignorować alarm, więc też ma zniknąć.
 *   · N-14   — ślad wylogowania: zapis cicho nie dochodził w procesie `www-data`,
 *              a ODCZYT SIĘ UDAWAŁ i oddawał nieświeżą liczbę z innego procesu.
 *
 * Wyzwalaczem jest we wszystkich trzech `Cache::flush()` — czyli `cache:clear`,
 * deploy albo restart Redisa. Nic egzotycznego.
 *
 * ⚠ TEN TEST MIERZY W ŚRODOWISKU, KTÓRE MA `CACHE_STORE=array`. Nie osłabia to
 * wyniku, bo pytanie brzmi „czy dane przeżywają wyczyszczenie CACHE'U", a nie
 * „czy Redis działa": magazyn docelowy (Postgres) jest w suicie PRAWDZIWY,
 * a cache — ten, który ma zostać wyczyszczony — jest atrapą tylko w tym sensie,
 * że czyści się szybciej. Gdyby dane nadal mieszkały w cache'u, `Cache::flush()`
 * zabrałby je tak samo w obu sterownikach; to jest właśnie mierzone.
 */
it('R6B-9: mapa sid → sesje PRZEŻYWA wyczyszczenie cache', function (): void {
    $sid = 'sid-trwalosc-'.bin2hex(random_bytes(4));

    RejestrSesji::zapamietaj($sid, 'sesja-lokalna-1');
    RejestrSesji::zapamietaj($sid, 'sesja-lokalna-2');

    // ODCZYT BAZOWY. Bez niego „po flushu widzę 2" byłoby zgodne także ze światem,
    // w którym zapis nigdy nie zadziałał, a liczba 2 pochodzi skądinąd.
    expect(RejestrSesji::odczytaj($sid))->toBe(['sesja-lokalna-1', 'sesja-lokalna-2']);

    // NAJPROZAICZNIEJSZE ZDARZENIE OPERACYJNE, JAKIE MOŻNA SOBIE WYOBRAZIĆ.
    Cache::flush();

    expect(RejestrSesji::odczytaj($sid))->toBe(
        ['sesja-lokalna-1', 'sesja-lokalna-2'],
        'Mapa `sid → sesje` zniknęła razem z cache\'em. Back-channel logout nie znajdzie wtedy '.
        'ŻADNEJ sesji i zwróci `skasowane_sesje = 0` — po cichu, bez jednego komunikatu. '.
        'To jest fail-open w wylogowaniu: wylogowany zostaje zalogowany.'
    );
});

it('R6B-9 KONTROLA NEGATYWNA: zapomniany sid oddaje PUSTO, a nie cudze sesje', function (): void {
    // Bez tego „mapa przeżywa" przechodzi także wtedy, gdy odczyt zwraca
    // wszystko, co jest w tabeli, niezależnie od `sid`.
    RejestrSesji::zapamietaj('sid-A-'.bin2hex(random_bytes(4)), 'sesja-A');

    expect(RejestrSesji::odczytaj('sid-B-nieznany'))->toBe([]);
});

it('R6B-9: `zakoncz` mówi ILE REJESTR ZNAŁ, nie tylko ile skasował', function (): void {
    // Sedno fail-openu: `skasowane_sesje = 0` było zgodne z DWOMA światami —
    // „nie było czego kasować" oraz „rejestr zniknął". Drugi sygnał je rozdziela.
    $sid = 'sid-znane-'.bin2hex(random_bytes(4));

    RejestrSesji::zapamietaj($sid, 'sesja-ktora-nie-istnieje-w-magazynie');
    RejestrSesji::zakoncz($sid);

    expect(RejestrSesji::znanychPrzyOstatnimZakonczeniu())->toBe(
        1,
        'Rejestr nie mówi, ile sesji ZNAŁ. Wtedy zero skasowanych jest nieodróżnialne '.
        'od utraty rejestru — czyli dokładnie R6B-9.'
    );

    // KIERUNEK ODWROTNY: dla `sid` bez wpisów licznik ma pokazać ZERO, nie ostatnią wartość.
    RejestrSesji::zakoncz('sid-pusty-'.bin2hex(random_bytes(4)));

    expect(RejestrSesji::znanychPrzyOstatnimZakonczeniu())->toBe(0);
});

it('R6B-10: puls harmonogramu PRZEŻYWA wyczyszczenie cache', function (): void {
    expect(Artisan::call('gabinet:puls'))->toBe(0);

    // ODCZYT BAZOWY: świeży puls jest widziany jako świeży.
    expect(Artisan::call('gabinet:puls', ['--sprawdz' => true]))->toBe(0);

    Cache::flush();

    expect(Artisan::call('gabinet:puls', ['--sprawdz' => true]))->toBe(
        0,
        'Puls przestał być widziany po `Cache::flush()` — czyli nadal mieszka w pamieci podrecznej.'
    );

    expect(DB::table('sygnaly_zdrowia')->where('klucz', 'gabinet:puls-harmonogramu')->exists())->toBeTrue(
        'Puls nie mieszka w magazynie trwałym. Po `cache:clear` healthcheck zapala się '.
        'na czerwono bez żadnej awarii harmonogramu — a fałszywy alarm uczy ignorować alarm.'
    );
});

it('R6B-10 KONTROLA NEGATYWNA: BRAK pulsu nadal daje czerwone', function (): void {
    // Bez tego „puls przeżywa flush" przechodzi także wtedy, gdy `--sprawdz`
    // zwraca zero ZAWSZE — a to jest zdrowie mierzone stałą.
    DB::table('sygnaly_zdrowia')->where('klucz', 'gabinet:puls-harmonogramu')->delete();

    expect(Artisan::call('gabinet:puls', ['--sprawdz' => true]))->toBe(1);
});

/**
 * Uruchamia domknięcie JAKO `www-data` i oddaje jego kod wyjścia.
 *
 * Sedno N-14: kontrola walidowana w kontekście użytkownika, który w produkcji
 * NIE WYSTĘPUJE, nie dowodzi niczego o produkcji. Testy biegną przez
 * `docker compose exec`, czyli jako ROOT — a root pisze wszędzie, więc każda
 * asercja o prawach zapisu przechodziłaby u niego niezależnie od stanu.
 *
 * `posix_setuid()` jest NIEODWRACALNE, więc porzucamy uprawnienia w PROCESIE
 * POTOMNYM (`pcntl_fork`), a wynik odbieramy kodem wyjścia. Suita zostaje
 * rootem i biegnie dalej.
 *
 * Alternatywa odrzucona świadomie: `->skip()` na przebiegu jako root. Dawałaby
 * pominięcie ZAWSZE — czyli zero pokrycia pod etykietą uczciwości.
 */
function jakoWwwData(callable $domkniecie): int
{
    // Wynik wraca PLIKIEM, nie kodem wyjścia — i to nie jest ozdobnik.
    //
    // Zmierzone przy pierwszym przebiegu: `exit()` w potomku uruchamia
    // destruktory PDO, które wysyłają komunikat zakończenia po gnieździe
    // ODZIEDZICZONYM po rodzicu. Postgres zamyka wtedy połączenie RODZICA,
    // a suita pada z `server closed the connection unexpectedly` — czyli
    // przyrząd psuł badany system, zamiast go mierzyć.
    //
    // Potomek kończy więc `SIGKILL`-em: bez destruktorów, bez dotykania
    // cudzego gniazda. Kod wyjścia przestaje być nośnikiem wyniku, więc
    // nośnikiem jest plik w `/tmp` (POSIX wewnątrz kontenera linuksowego,
    // czyli bezpieczny — inaczej niż `/tmp` widziane z Windows).
    $wynikPlik = tempnam(sys_get_temp_dir(), 'n14-');

    if ($wynikPlik === false) {
        throw new RuntimeException('Brak pliku na wynik — kontrola N-14 nie ma jak zmierzyć.');
    }

    chmod($wynikPlik, 0o666);

    $pid = pcntl_fork();

    if ($pid === -1) {
        throw new RuntimeException('Nie udało się rozwidlić procesu — kontrola N-14 nie ma jak zmierzyć.');
    }

    if ($pid === 0) {
        $kod = 1;

        try {
            $kod = posix_setgid(82) && posix_setuid(82) && $domkniecie() ? 0 : 1;
        } catch (Throwable) {
            $kod = 2;
        }

        file_put_contents($wynikPlik, (string) $kod);

        posix_kill(posix_getpid(), SIGKILL);
    }

    pcntl_waitpid($pid, $status);

    $odczyt = (string) file_get_contents($wynikPlik);
    @unlink($wynikPlik);

    // Pusty plik znaczy „potomek nie doszedł do zapisu" — to inny świat niż
    // „zmierzył i wyszło źle", więc ma własną wartość, a nie milczące 1.
    return $odczyt === '' ? 4 : (int) $odczyt;
}

it('N-14: kontrola biegnie jako WWW-DATA — ten sam uzytkownik, co proces obslugujacy zadania', function (): void {
    // Zmierzone na zywym stosie 09.08: katalog sladu nalezal do roota, zadania
    // obsluguje `www-data`, wiec zapis CICHO nie dochodzil (`File::put()` ostrzega
    // i zwraca), a ODCZYT SIE UDAWAL i oddawal nieswieza liczbe z innego procesu.
    //
    // Zaden test tego nie lapal, bo wszystkie biegna jako ROOT — a root pisze
    // wszedzie. Kontrola walidowana w kontekscie uzytkownika, ktory w produkcji
    // NIE WYSTEPUJE, nie dowodzi niczego o produkcji.
    $katalog = storage_path('slad-wylogowania');

    File::deleteDirectory($katalog);
    File::ensureDirectoryExists($katalog);

    // ── GALAZ 1: katalog ROOTA, czyli stan ZASTANY przed ta naprawa ──
    chown($katalog, 'root');
    chgrp($katalog, 'root');
    chmod($katalog, 0o755);

    expect(jakoWwwData(static fn (): bool => SladWylogowania::zapisywalny() === false))->toBe(
        0,
        'W procesie `www-data` katalog nalezacy do roota wyszedl jako ZAPISYWALNY. '.
        'Perturbacja nie weszla w zycie, wiec wynik nizej nie znaczylby nic.'
    );

    expect(jakoWwwData(static fn (): bool => SladWylogowania::wejscia() === null))->toBe(
        0,
        'Slad oddal LICZBE z magazynu, do ktorego proces zadania nie moze pisac. To jest N-14: '.
        'przyrzad diagnostyczny zwracajacy nieswieza liczbe jest GORSZY od zwracajacego zero, '.
        'bo WYGLADA JAK POMIAR.'
    );

    // ── GALAZ 2 (kontrola pozytywna): katalog www-data, czyli stan PO naprawie ──
    // Bez niej `null` wyzej bylby zgodny takze ze swiatem „slad oddaje null ZAWSZE”.
    chown($katalog, 'www-data');
    chgrp($katalog, 'www-data');

    expect(jakoWwwData(static fn (): bool => SladWylogowania::zapisywalny() === true))->toBe(
        0,
        'Katalog nalezacy do `www-data` nadal wychodzi jako niezapisywalny — kontrola oslepla.'
    );

    expect(jakoWwwData(static function (): bool {
        SladWylogowania::wejscie();

        return SladWylogowania::wejscia() === 1;
    }))->toBe(
        0,
        'W sprawnym magazynie `www-data` nie umie ani zapisac, ani odczytac wlasnego zapisu.'
    );

    SladWylogowania::wyczysc();
});

it('N-14 SRODOWISKO: katalog sladu nalezy do uzytkownika procesu zadan', function (): void {
    // Druga strona naprawy. Typ mowi „NIE WIEM” zamiast klamac — ale gdyby na tym
    // poprzestac, produkcja mialaby diagnostyke trwale w stanie „nie wiem”.
    // Katalog powstaje w `entrypoint.sh`, PRZED pierwszym zadaniem, wiec obejmuje
    // go `chown www-data` — a nie leniwie u tego, kto pisal pierwszy.
    $entrypoint = (string) file_get_contents(base_path('../docker/php/entrypoint.sh'));

    expect($entrypoint)->toContain(
        'storage/slad-wylogowania',
    );

    expect(str_contains($entrypoint, 'chown -R www-data:www-data storage'))->toBeTrue(
        'Entrypoint nie ustawia wlasciciela katalogu storage. Katalog sladu powstalby wtedy '.
        'leniwie — u tego procesu, ktory pisal pierwszy — i N-14 wrocilby przy pierwszym '.
        'przebiegu testow przed pierwszym zadaniem HTTP.'
    );
});
