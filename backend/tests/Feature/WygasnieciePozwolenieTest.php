<?php

declare(strict_types=1);

use App\Tozsamosc\RejestrSesji;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * D-EKO-012 / kontrakt §4.5a: **TTL NIE JEST PRAWEM WSTĘPU.**
 *
 * > O dostępie rozstrzyga OBECNOŚĆ znacznika; czas życia jest progiem
 * > SPRZĄTANIA, nie pozwoleniem na wejście.
 *
 * Wada zmierzona 09.08: `RejestrSesji::uniewazniona()` — pytanie zadawane przy
 * KAŻDYM żądaniu — rozstrzygało przez `->where('wygasa_at', '>', now())`.
 * Znacznik **wciąż obecny w bazie** przestawał blokować po progu, więc upływ
 * czasu PRZYZNAWAŁ dostęp osobie wylogowanej.
 *
 * Klauzula, która to porządkuje, weszła do kontraktu dopiero 09.08 (§4.5a) —
 * wcześniej kontrakt MILCZAŁ o czasie życia znacznika (zmierzone: `wygasa_at`
 * i `86400` po zero trafień w 874 liniach). Ta naprawa jest więc wykonaniem
 * reguły, której nie było, a nie łataniem odstępstwa od niej.
 */
function znacznik(string $sid, string $uniewazniona, string $wygasa): void
{
    DB::table('uniewaznione_sesje')->insert([
        'sid_skrot' => hash('sha256', $sid),
        'uniewazniona_at' => $uniewazniona,
        'wygasa_at' => $wygasa,
        'powod' => 'backchannel-logout',
    ]);
}

it('znacznik PO TERMINIE, który wciąż istnieje, NADAL BLOKUJE', function (): void {
    $sid = 'sid-osoby-wylogowanej';
    znacznik($sid, CarbonImmutable::now()->subDays(3)->toDateTimeString(), CarbonImmutable::now()->subMinute()->toDateTimeString());

    expect(DB::table('uniewaznione_sesje')->where('sid_skrot', hash('sha256', $sid))->exists())
        ->toBeTrue('Test nie ma czego chronić — znacznika nie ma w bazie.');

    expect(RejestrSesji::uniewazniona($sid))->toBeTrue(
        'TTL POTRAKTOWANY JAKO PRAWO WSTĘPU: znacznik NADAL ISTNIEJE, a system '.
        'przepuszcza, bo minął `wygasa_at`. O dostępie ma rozstrzygać OBECNOŚĆ.'
    );
});

it('KIERUNEK 0: stempel PUSTY, NIECZYTELNY albo Z PRZYSZŁOŚCI też blokuje', function (): void {
    // Przy zabezpieczeniu niepewność ma JEDNĄ dopuszczalną odpowiedź.
    // „Z przyszłości" jest tu istotny: gdyby gdziekolwiek został warunek na
    // wiek, stempel w przód mógłby zostać uznany za „jeszcze nieważny".
    znacznik('sid-przyszlosc', CarbonImmutable::now()->addYears(5)->toDateTimeString(), CarbonImmutable::now()->addYears(6)->toDateTimeString());
    expect(RejestrSesji::uniewazniona('sid-przyszlosc'))->toBeTrue('Stempel z przyszłości przestał blokować.');

    znacznik('sid-rowne-teraz', CarbonImmutable::now()->toDateTimeString(), CarbonImmutable::now()->toDateTimeString());
    expect(RejestrSesji::uniewazniona('sid-rowne-teraz'))->toBeTrue('Stempel równy „teraz" przestał blokować.');

    // Stempel odległy w przeszłości — wiersz jest, więc blokuje.
    znacznik('sid-prehistoria', '1970-01-01 00:00:00', '1970-01-02 00:00:00');
    expect(RejestrSesji::uniewazniona('sid-prehistoria'))->toBeTrue('Bardzo stary znacznik przestał blokować.');
});

it('KIERUNEK ODWROTNY: BRAK znacznika nie blokuje — kontrola nie jest tautologią', function (): void {
    // Bez tej asercji wszystkie „blokuje" wyżej byłyby spełnione także przez
    // implementację zwracającą `true` zawsze.
    expect(RejestrSesji::uniewazniona('sid-nigdy-nie-wylogowany'))->toBeFalse(
        'System uznaje za unieważnioną sesję, dla której NIE MA znacznika — to blokada wszystkich.'
    );
    expect(RejestrSesji::uniewazniona(''))->toBeFalse('Pusty `sid` uznany za unieważniony.');
});

it('SPRZĄTANIE usuwa PO progu, zostawia PRZED, i liczy to, co NAPRAWDĘ zniknęło', function (): void {
    znacznik('stary', CarbonImmutable::now()->subDays(3)->toDateTimeString(), CarbonImmutable::now()->subMinute()->toDateTimeString());
    znacznik('swiezy', CarbonImmutable::now()->toDateTimeString(), CarbonImmutable::now()->addDay()->toDateTimeString());

    $usuniete = RejestrSesji::sprzataj();

    expect($usuniete)->toBe(1, 'Sprzątanie zwróciło inną liczbę niż faktycznie usunięta.')
        ->and(DB::table('uniewaznione_sesje')->where('sid_skrot', hash('sha256', 'stary'))->exists())
        ->toBeFalse('Znacznik po progu PRZEŻYŁ sprzątanie.')
        ->and(DB::table('uniewaznione_sesje')->where('sid_skrot', hash('sha256', 'swiezy'))->exists())
        ->toBeTrue('Sprzątanie usunęło znacznik PRZED progiem — sprzątaczka sama odblokowuje.');

    // Dopiero PO sprzątnięciu sid przestaje blokować — i to jest jedyna
    // dopuszczalna droga, którą dostęp wraca.
    expect(RejestrSesji::uniewazniona('stary'))->toBeFalse();
    expect(RejestrSesji::uniewazniona('swiezy'))->toBeTrue();
});

it('sprzątanie przy pustej tabeli zwraca ZERO, nie wywraca się', function (): void {
    expect(RejestrSesji::sprzataj())->toBe(0);
});

it('⛔ PRÓG SPRZĄTANIA nie jest krótszy niż najdłuższa możliwa sesja lokalna', function (): void {
    // To jest warunek, na którym stoi bezpieczeństwo całej eksmisji. Gdyby próg
    // był krótszy niż życie sesji, sesja przeżywałaby własny znacznik i
    // sprzątaczka SAMA ODBLOKOWYWAŁABY wylogowanego — czyli wiek przyznawałby
    // dostęp okrężną drogą. Konta mają dziś tę stronę otwartą: ich `SessionStore`
    // nie sprawdza wieku rekordu w ogóle, więc sesja nie wygasa nigdy.
    $prog = RejestrSesji::progSprzataniaSekund();
    $zycieSesji = (int) config('session.lifetime') * 60;

    expect($zycieSesji)->toBeGreaterThan(0, 'Nie umiem odczytać życia sesji — kontrola mierzyłaby pustkę.');

    expect($prog)->toBeGreaterThanOrEqual(
        $zycieSesji,
        sprintf(
            'Próg sprzątania (%d s) jest KRÓTSZY niż życie sesji (%d s). Sesja przeżyje '.
            'własny znacznik, a sprzątaczka odblokuje wylogowanego.',
            $prog, $zycieSesji
        )
    );
});

it('PRÓG POCHODZI Z KONTRAKTU, nie z wartości domyślnej aplikacji', function (): void {
    // Wartość kontraktowa, przepisana świadomie: `ssoSessionMaxLifespan` realmu
    // `niepodzielni`, odczyt Admin API 09.08.2026 20:22:16 (sesja kont).
    // Zastrzeżenie ich, przepisane bez łagodzenia: instancji PRODUKCYJNEJ nie ma
    // — to „to samo, co w pliku, z którego produkcja powstanie".
    expect(RejestrSesji::progSprzataniaSekund())->toBe(86400);
    expect(config('konta.sso_session_max_s'))->toBe(86400, 'Kontrakt i konfiguracja się rozjechały.');
});

it('KONTROLA NEGATYWNA: brak wartości kontraktowej kończy się WYJĄTKIEM, nie cichym 86400', function (): void {
    // Reguła helpdesku, przyjęta dziś: kontrola POZYTYWNA łapie przyrząd MARTWY,
    // ale nie łapie przyrządu mierzącego COŚ INNEGO. Na to potrzebna jest
    // kontrola NEGATYWNA — coś, co MUSI wyjść odmową.
    //
    // Tu odmową jest wyjątek: gdyby próg miał cichą wartość domyślną, ta asercja
    // przeszłaby na wartości zastępczej i nikt by się nie dowiedział, że
    // konfiguracji nie ma.
    config(['konta.sso_session_max_s' => null]);

    $blad = null;

    try {
        RejestrSesji::progSprzataniaSekund();
    } catch (Throwable $e) {
        $blad = $e->getMessage();
    }

    expect($blad)->not->toBeNull(
        'Brak wartości kontraktowej NIE dał wyjątku — próg ma cichą wartość domyślną, '.
        'czyli drugi opis konfiguracji realmu (klasa P3).'
    );
    expect((string) $blad)->toContain('sso_session_max_s');
});

it('KONTROLA NEGATYWNA: wartość MNIEJSZA jest STOSOWANA, a nie po cichu podnoszona', function (): void {
    // Zmierzone przed naprawą: `max(86400, config)` sprawiał, że config=3600
    // dawał próg 86400 — ustawienie było PRZYJMOWANE I IGNOROWANE, czyli
    // wyglądało na zastosowane. To jest gałąź zdegenerowana w konfiguracji.
    //
    // Ta kontrola pilnuje, żeby wartość mniejsza miała SKUTEK. Nie znaczy to,
    // że wolno ją ustawić — od tego jest kontrola „próg >= życie sesji", która
    // wtedy zapali. Znaczy: ma być WIDAĆ, że ustawiono ją źle.
    config(['konta.sso_session_max_s' => 3600]);

    expect(RejestrSesji::progSprzataniaSekund())->toBe(
        3600,
        'Mniejsza wartość została po cichu podniesiona — konfiguracja jest przyjmowana i ignorowana.'
    );
});
