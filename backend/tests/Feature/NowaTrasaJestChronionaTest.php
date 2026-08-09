<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * ŚCIEŻKA NAJDŁUŻSZA — trasa dopisana DZIŚ, przez kogoś, kto o kontroli unieważnienia
 * nigdy nie słyszał, ma być chroniona **bez jego pamięci**.
 *
 * To jest właściwy pomiar tej przebudowy. Poprzedni stan (`ODPOWIEDZ-031`) przechodził
 * wszystkie testy, bo jedyna trasa, która kontrolę wołała, była też jedyną testowaną.
 * **Kontrola sprawdzała samą siebie** — reguła `C1`: kontrola dzieląca mechanizm ze swoim
 * przedmiotem nie może go złapać.
 *
 * Tutaj trasa powstaje W TEŚCIE i nie ma z kontrolą nic wspólnego — nie woła jej, nie zna
 * jej nazwy, nie ma jej w podpowiedziach. Jeśli mimo to wyjdzie 401, chroni ją KONSTRUKCJA.
 */
/** Tożsamość w magazynie sesji — minimalny kształt, jakiego wymaga `TozsamoscSesji`. */
function sesjaZ(string $sid): array
{
    return ['konta' => ['sub' => 'ktos-zalogowany', 'sid' => $sid]];
}

function dopiszTraseNieswiadoma(string $uri): void
{
    // Celowo BEZ żadnej wzmianki o unieważnieniu — tak wygląda trasa dopisana
    // przez człowieka, który realizuje swoje zadanie i o cudzej regule nie wie.
    Route::middleware('web')->get($uri, fn () => response()->json(['dotarlem' => true]));
    Route::getRoutes()->refreshNameLookups();
}

it('trasa dopisana DZIŚ, nieświadoma kontroli, odrzuca sesję unieważnioną (401)', function (): void {
    $sid = 'sid-wylogowany-przez-idp';

    DB::table('uniewaznione_sesje')->insert([
        'sid_skrot' => hash('sha256', $sid),
        'uniewazniona_at' => now(),
        'wygasa_at' => now()->addDay(),
    ]);

    dopiszTraseNieswiadoma('/trasa-nieswiadoma-odrzucenie');

    $this->withSession(sesjaZ($sid))
        ->get('/trasa-nieswiadoma-odrzucenie')
        ->assertStatus(401)
        ->assertJsonPath('zalogowany', false);
});

it('KONTROLA NEGATYWNA: ta sama trasa, sesja NIE unieważniona — przepuszcza (200)', function (): void {
    // Bez tego 401 wyżej nie dowodzi niczego o unieważnieniu: mógłby pochodzić
    // z zepsutej trasy, z braku sesji albo z zupełnie innego strażnika.
    // Ta sama para co w `R6B-2`, gdzie „POZYTYWNY" przechodził przy sesji
    // NIEUSUNIĘTEJ — 401 brał się wtedy z czegoś innego, niż głosił test.
    dopiszTraseNieswiadoma('/trasa-nieswiadoma-przepuszczenie');

    $this->withSession(sesjaZ('sid-nikt-go-nie-wylogowal'))
        ->get('/trasa-nieswiadoma-przepuszczenie')
        ->assertOk()
        ->assertJsonPath('dotarlem', true);
});

it('ŻĄDANIE BEZ SESJI nie jest traktowane jak unieważnione', function (): void {
    // Granica, którą łatwo przekroczyć niechcący: gdyby middleware odrzucał
    // także brak tożsamości, przestałby być kontrolą bezpieczeństwa, a stałby
    // się bramką logowania — i zamknąłby każdą trasę publiczną, której ktoś
    // zapomniał zadeklarować. To inna decyzja i należy do innego miejsca.
    dopiszTraseNieswiadoma('/trasa-nieswiadoma-bez-sesji');

    $this->get('/trasa-nieswiadoma-bez-sesji')->assertOk();
});

it('OKNO: unieważnienie działa od NAJBLIŻSZEGO żądania, nie po upływie czasu', function (): void {
    // Tu mierzymy to, co zlecenie nazywa OKNEM. Wcześniej okno wyznaczała
    // pamięć autora trasy i było NIEOGRANICZONE: trasa nieobjęta kontrolą
    // przepuszczała unieważnioną sesję aż do wygaśnięcia ciasteczka.
    // Teraz okno = jedno żądanie.
    $sid = 'sid-wylogowany-w-trakcie-pracy';

    dopiszTraseNieswiadoma('/trasa-nieswiadoma-okno');

    // Żądanie PRZED unieważnieniem — przechodzi.
    $this->withSession(sesjaZ($sid))->get('/trasa-nieswiadoma-okno')->assertOk();

    // IdP wylogowuje kanałem tylnym.
    DB::table('uniewaznione_sesje')->insert([
        'sid_skrot' => hash('sha256', $sid),
        'uniewazniona_at' => now(),
        'wygasa_at' => now()->addDay(),
    ]);

    // NAJBLIŻSZE żądanie — bez przesuwania zegara, bez czekania.
    $this->withSession(sesjaZ($sid))->get('/trasa-nieswiadoma-okno')->assertStatus(401);
});
