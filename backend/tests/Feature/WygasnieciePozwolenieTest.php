<?php

declare(strict_types=1);

use App\Tozsamosc\RejestrSesji;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * D-EKO-012: **TTL NIE JEST PRAWEM WSTĘPU.**
 *
 * > O dostępie rozstrzyga OBECNOŚĆ znacznika; czas życia jest progiem
 * > SPRZĄTANIA, nie pozwoleniem na wejście.
 *
 * Reguła przyszła z rundy 2 kont, gdzie sesja osoby wylogowanej wracała do życia
 * po przeskoku zegara. `ZLECENIE-015 (D)` kazał zmierzyć, czy TO SAMO mam u siebie.
 *
 * ================== MAM. ZMIERZONE, NIE ODCZYTANE ==================
 *
 * `RejestrSesji::uniewazniona()` — pytanie zadawane przy KAŻDYM żądaniu —
 * rozstrzyga o dostępie tak:
 *
 *     ->where('sid_skrot', hash('sha256', $sid))
 *     ->where('wygasa_at', '>', CarbonImmutable::now())     // <-- TU
 *     ->exists();
 *
 * Znacznik, który WCIĄŻ LEŻY W BAZIE, przestaje blokować po `wygasa_at`.
 * To jest wygaśnięcie traktowane jako pozwolenie — dokładnie kształt D-EKO-012.
 *
 * Dziś broni nas wyłącznie ZAŁOŻENIE, że próg (SSO Session Max) przeżyje każdą
 * sesję, którą znacznik unieważnia. Założenie o cudzym zegarze i cudzej
 * konfiguracji, nie własność naszego kodu. A retencja tej tabeli **dziś nie
 * biegnie w ogóle** (okres czeka na IOD), więc wiersz zostaje — i mamy okno,
 * w którym znacznik ISTNIEJE i NIE BLOKUJE.
 *
 * NAPRAWY NIE WPROWADZAM W TEJ RUNDZIE: zmiana semantyki decyzji o dostępie
 * dotyka kontraktu BLK-22 z kontami i należy jej się własna pozycja z parą
 * czerwone-przed / zielone-po. Ten plik jest POMIAREM, nie naprawą.
 */
it('CZERWONA: znacznik unieważnienia, który WCIĄŻ ISTNIEJE, przestaje blokować po wygaśnięciu', function (): void {
    $sid = 'sid-osoby-wylogowanej';

    DB::table('uniewaznione_sesje')->insert([
        'sid_skrot' => hash('sha256', $sid),
        'uniewazniona_at' => CarbonImmutable::now()->subDays(3),
        'wygasa_at' => CarbonImmutable::now()->subMinute(),   // próg minął MINUTĘ temu
        'powod' => 'backchannel-logout',
    ]);

    // Znacznik LEŻY W BAZIE — nikt go nie sprzątnął.
    expect(DB::table('uniewaznione_sesje')->where('sid_skrot', hash('sha256', $sid))->exists())
        ->toBeTrue('Test nie ma czego chronić — znacznika nie ma w bazie.');

    // A mimo to system twierdzi, że ta sesja NIE jest unieważniona.
    expect(RejestrSesji::uniewazniona($sid))->toBeTrue(
        'TTL POTRAKTOWANY JAKO PRAWO WSTĘPU (D-EKO-012): znacznik unieważnienia '.
        'NADAL ISTNIEJE w bazie, a `uniewazniona()` zwraca false, bo minął `wygasa_at`. '.
        'O dostępie ma rozstrzygać OBECNOŚĆ znacznika; czas życia jest progiem '.
        'SPRZĄTANIA. Naprawa: zdjąć warunek `wygasa_at > now()` z zapytania decydującego '.
        'i zostawić go WYŁĄCZNIE zadaniu retencyjnemu.'
    );
});

it('kierunek odwrotny: znacznik ŚWIEŻY blokuje — kontrola nie jest tautologią', function (): void {
    $sid = 'sid-swiezo-wylogowany';

    DB::table('uniewaznione_sesje')->insert([
        'sid_skrot' => hash('sha256', $sid),
        'uniewazniona_at' => CarbonImmutable::now(),
        'wygasa_at' => CarbonImmutable::now()->addDay(),
        'powod' => 'backchannel-logout',
    ]);

    expect(RejestrSesji::uniewazniona($sid))->toBeTrue(
        'Świeży znacznik NIE blokuje — wtedy czerwień wyżej nie mówi nic o wygaśnięciu, '.
        'tylko o tym, że mechanizm nie działa w ogóle.'
    );
});

it('kierunek 0: BRAK znacznika nie blokuje — inaczej kontrola blokowałaby wszystkich', function (): void {
    expect(RejestrSesji::uniewazniona('sid-nigdy-nie-wylogowany'))->toBeFalse(
        'System uznaje za unieważnioną sesję, dla której NIE MA znacznika — '.
        'to byłaby blokada wszystkich, a nie kontrola.'
    );
});
