<?php

declare(strict_types=1);

use App\Http\Middleware\SprawdzUniewaznienie;
use App\Tozsamosc\WyjatkiUniewaznienia;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Route;

/**
 * ZASIĘG KONTROLI UNIEWAŻNIENIA — porównanie TRZECH ZBIORÓW (klasa `D6`).
 *
 * wszystkie trasy · trasy stosujące regułę · **zadeklarowane wyjątki**
 *
 * **Trasa, która reguły nie stosuje i NIE JEST zadeklarowana → CZERWONE.**
 * To zamienia nieobecność w stan **wykrywalny**: dziś świadomy wyjątek i przeoczenie
 * wyglądają identycznie, bo jedno i drugie to brak wywołania.
 *
 * Zmierzone przed naprawą (`ODPOWIEDZ-031`): kontrolę stosowała **1 trasa z 34**,
 * pozostałe 33 nie były nigdzie zadeklarowane.
 */
/**
 * Czy żądanie do tej trasy BYŁOBY SPRAWDZONE — nie „czy middleware jest wpięty".
 *
 * Sama obecność middleware'u nie wystarcza, bo on POMIJA zadeklarowane wyjątki.
 * Gdyby pokrycie liczyć obecnością, główna asercja stałaby się tautologią:
 * middleware jest w grupach `web` i `api`, więc „ma middleware albo jest wyjątkiem"
 * byłoby prawdą zawsze. Pytamy więc o SKUTEK.
 */
function kontrolaObejmuje(string $uri): bool
{
    // ZMIERZONE 09.08, ta kontrola pozytywna to złapała: grupy middleware wpisuje
    // do routera JĄDRO HTTP, a nie jądro konsoli. Bez tej linii `getMiddlewareGroups()`
    // zwraca 0 członków, `gatherRouteMiddleware()` oddaje NIEROZWINIĘTY napis „web",
    // i skaner melduje ZEROWE pokrycie przy pełnym. Przyrząd mierzyłby co innego,
    // niż deklaruje — dokładnie ten przypadek, którego kontrola pozytywna pilnuje.
    app()->make(Kernel::class);

    // `Route::getRoutes()` oddaje `RouteCollectionInterface`, ktory nie jest ani
    // `iterable` dla statyki, ani nosnikiem typu elementu. `->getRoutes()` oddaje
    // tablice `Route` — i dopiero wtedy `->uri()` ma na czym stac.
    $trasa = null;

    foreach (Route::getRoutes()->getRoutes() as $kandydat) {
        if ($kandydat->uri() === $uri) {
            $trasa = $kandydat;

            break;
        }
    }

    if ($trasa === null) {
        return false;
    }

    $wpiety = in_array(SprawdzUniewaznienie::class, Route::gatherRouteMiddleware($trasa), true);

    return $wpiety && ! WyjatkiUniewaznienia::zadeklarowany($uri);
}

/** @return list<string> uri wszystkich tras aplikacji, bez tras narzędzi zewnętrznych */
function trasyAplikacji(): array
{
    $wynik = [];

    foreach (Route::getRoutes()->getRoutes() as $trasa) {
        $uri = $trasa->uri();

        // Horizon i Scramble niosą WŁASNYCH strażników i własny cykl życia.
        // Nie są wyjątkiem od naszej reguły — są cudzym systemem w naszym routerze.
        // Odnotowane w ODPOWIEDZ-031 jako NIEZMIERZONE, nie jako czyste.
        if (str_starts_with($uri, 'horizon') || str_starts_with($uri, 'docs/api')) {
            continue;
        }

        $wynik[$uri] = $uri;
    }

    return array_values($wynik);
}

it('KAŻDA trasa albo STOSUJE kontrolę unieważnienia, albo ma ZADEKLAROWANY wyjątek', function (): void {
    $trasy = trasyAplikacji();

    // Pustka to błąd, nie zero: bez tego kontrola przechodzi, gdy router jest pusty.
    expect(count($trasy))->toBeGreaterThan(3, 'Za mało tras — parser routera się rozjechał.');

    $bezDeklaracji = [];

    foreach ($trasy as $uri) {
        $stosuje = kontrolaObejmuje($uri);
        $zadeklarowany = WyjatkiUniewaznienia::zadeklarowany($uri);

        if (! $stosuje && ! $zadeklarowany) {
            $bezDeklaracji[] = $uri;
        }
    }

    expect($bezDeklaracji)->toBe(
        [],
        "Trasy, które NIE stosują kontroli unieważnienia i NIE MAJĄ zadeklarowanego wyjątku:\n  ".
        implode("\n  ", $bezDeklaracji)."\n".
        'Nieobecność nie niesie intencji — nie da się odróżnić świadomego wyjątku od przeoczenia. '.
        'Dopisz kontrolę albo wpis do `WyjatkiUniewaznienia` z powodem i warunkiem znoszącym.'
    );
});

it('KIERUNEK ODWROTNY: wyjątek zadeklarowany dla trasy, która NIE ISTNIEJE, jest błędem', function (): void {
    // Bez tego lista wyjątków zgnije jak każdy rejestr pisany na zapas: zostaną
    // w niej wpisy o trasach dawno usuniętych, a wraz z nimi ZŁUDZENIE, że ktoś
    // przemyślał wyjątek, którego już nie ma. Martwy wpis jest gorszy niż brak
    // wpisu, bo wygląda na decyzję.
    $istniejace = trasyAplikacji();
    $martwe = [];

    foreach (array_keys(WyjatkiUniewaznienia::wpisy()) as $uri) {
        if (! in_array($uri, $istniejace, true)) {
            $martwe[] = $uri;
        }
    }

    expect($martwe)->toBe(
        [],
        'Zadeklarowane wyjątki dla tras, których NIE MA w routerze: '.implode(', ', $martwe).
        '. Wpis o nieistniejącej trasie wygląda na przemyślaną decyzję, a jest śmieciem.'
    );
});

it('KAŻDY wyjątek niesie POWÓD i WARUNEK ZNOSZĄCY — koszt wyjątku = koszt zgodności', function (): void {
    // Zmierzone u siebie 09.08 (ODPOWIEDZ-032): gdy wyjątek jest tańszy od
    // zgodności, lista wyjątków staje się drogą domyślną — nie przez złą wolę,
    // przez pośpiech. Dlatego wpis kosztuje tyle, co przypisanie kontroli.
    $ubogie = [];

    foreach (WyjatkiUniewaznienia::wpisy() as $uri => $wpis) {
        $powod = $wpis['powod'] ?? '';
        $warunek = $wpis['warunek_znoszacy'] ?? '';

        if (mb_strlen($powod) < 20 || mb_strlen($warunek) < 20) {
            $ubogie[] = $uri;
        }
    }

    expect($ubogie)->toBe(
        [],
        'Wyjątki bez sensownego powodu albo bez warunku znoszącego: '.implode(', ', $ubogie).
        '. Powód bez warunku znoszącego jest wiecznym zwolnieniem.'
    );
});

it('KONTROLA POZYTYWNA: `auth/ja` MUSI wyjść jako objęta kontrolą', function (): void {
    // Przyrząd MARTWY — gdyby `kontrolaObejmuje()` zwracała zawsze `false`,
    // wszystkie trasy trafiłyby do wyjątków i kontrola wyżej byłaby zielona
    // po dopisaniu ich do listy.
    expect(kontrolaObejmuje('auth/ja'))->toBeTrue(
        'Trasa czytająca tożsamość NIE jest objęta kontrolą — skaner mierzy co innego, niż deklaruje.'
    );
});

it('KONTROLA NEGATYWNA: `api/wersja` MUSI wyjść jako NIEOBJĘTA kontrolą', function (): void {
    // Reguła helpdesku: kontrola pozytywna łapie przyrząd MARTWY, ale nie łapie
    // przyrządu mierzącego COŚ INNEGO. Gdyby `kontrolaObejmuje()` zwracała
    // zawsze `true`, kontrola główna byłaby zielona przy ZEROWYM pokryciu —
    // i wyglądałaby identycznie jak przy pełnym.
    expect(kontrolaObejmuje('api/wersja'))->toBeFalse(
        'Publiczna trasa wersji wychodzi jako objęta kontrolą — skaner zwraca „true" bez patrzenia.'
    );
});
