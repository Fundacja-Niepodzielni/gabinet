<?php

declare(strict_types=1);

use App\Tozsamosc\KontaOidc;
use App\Tozsamosc\RoszczeniaZweryfikowane;
use App\Tozsamosc\SesjaKonta;
use App\Tozsamosc\TozsamoscSesji;
use App\Wsparcie\Typy;
use Illuminate\Http\Request;
use Tests\Wsparcie\FabrykaTokenow;
use Tests\Wsparcie\Kod;

/*
 * ⛔ TOŻSAMOŚĆ JAKO TYP, NIE JAKO WYNIK SKANOWANIA KODU.
 *
 * Cztery rundy z rzędu znajdowały tę samą klasę wady o piętro wyżej:
 *
 *     R8-1   nazwa pola                → dopisaliśmy nazwy
 *     R9-1   sposób dostarczenia       → dopisaliśmy kształty ładunku
 *     R10-1  składnia odczytu          → usunęliśmy listę metod
 *     R11-1  pole KONTRAKTOWE użyte jako tożsamość  ← warstwa 3 milczy SŁUSZNIE
 *     R11-2  inna metoda fasady (`zaktualizuj`)     ← warstwy 2 i 4 jej nie znały
 *
 * Wniosek architekta (`ZLECENIE-078` §0), z którym się zgadzam po tym, jak sam
 * wystawiłem piąte piętro do obalenia i zostało obalone: **kontrola oparta na
 * analizie KSZTAŁTU KODU zawsze ma brzeg, a brzeg zawsze da się przekroczyć.**
 * Piąta warstwa dałaby szóste piętro.
 *
 * Dlatego ten plik NIE jest piątą warstwą. Warstwy 1–4
 * (`WaskieGardloZapisuTozsamosciTest`) zostają jako DRUGA LINIA — obrona
 * w głąb — ale przestają być jedyną. Pierwszą linią jest odtąd TYP:
 *
 *     · `RoszczeniaZweryfikowane` powstaje WYŁĄCZNIE po sprawdzeniu podpisu;
 *     · `SesjaKonta::zaloz()` nie przyjmuje ani tablicy, ani napisu;
 *     · `TozsamoscSesji` nie ma już metody zdolnej podmienić `sub`.
 *
 * RÓŻNICA, O KTÓRĄ TU CHODZI. Kontrola strukturalna mówi „widzę, że napisałeś
 * coś złego". Typ mówi „tego nie da się napisać". Pierwsze zależy od tego, czy
 * skaner zna dany kształt; drugie nie zależy od niczego, co autor kodu wymyśli.
 *
 * DLACZEGO TU W OGÓLE ZOSTAJE KONTROLA STRUKTURALNA. Zostaje jedna i dotyczy
 * JEDNEJ klasy: pilnuje, żeby obiekt wartości nie stał się podrabialny (żeby
 * nikt nie dodał publicznego konstruktora ani fabryki z tablicy). To jest
 * dozwolone przez `ZLECENIE-078` §1.2 i różni się od poprzednich warstw
 * zasięgiem: przedmiotem jest jedna klasa, a nie cały kod wykonywalny.
 *
 * CZEGO TEN PLIK NIE TWIERDZI. Nie twierdzi, że instancji nie da się wytworzyć
 * `Reflection`-em ani `unserialize`-em — w PHP nie da się tego zamknąć typem
 * (to samo ograniczenie co przy R6A-3). Warunek utrzymujący jest ten sam
 * i EGZEKWOWANY osobno w `WaskieGardloTozsamosciTest`: kod produkcyjny nie
 * używa `Reflection` ani `unserialize`.
 */

/*
 * ⛔ POMOCNIKI PRZEPISANE 19.08 — i to jest CECHA, nie koszt.
 *
 * Do naprawy „szóstego piętra" stały tu funkcje `wymaganiaAccess()`
 * i `wymaganiaId()`, które SKŁADAŁY wymagania walidacji ręcznie — razem
 * z `jwks`. Dokładnie ta możliwość była znaleziskiem: kto podaje materiał
 * klucza, ten decyduje, wobec czego token jest „zweryfikowany".
 *
 * Po zamknięciu tej drogi obiektu roszczeń NIE DA SIĘ zdobyć inaczej niż
 * przez konfigurację wskazującą nasze IdP. W teście znaczy to podstawione
 * IdP (`udawajIdp()` + atrapa JWKS) — czyli tę samą drogę, którą idzie
 * produkcja. Test stał się trudniejszy i to jest miara tego, że typ trzyma:
 * gdyby dało się go łatwo obejść w teście, dałoby się i poza nim.
 */

/** Konfiguracja OIDC wskazująca PODSTAWIONE IdP — jedyne źródło wymagań. */
function oidcAtrapy(): KontaOidc
{
    udawajIdp();

    return app(KontaOidc::class);
}

/**
 * Roszczenia ACCESS — jedyną drogą, jaka istnieje: przez sprawdzony podpis.
 *
 * @param  array<string, mixed>  $nadpisaniaClaims
 */
function roszczeniaAccess(array $nadpisaniaClaims = []): RoszczeniaZweryfikowane
{
    $wynik = RoszczeniaZweryfikowane::zAccessTokenu(
        FabrykaTokenow::podpisz(FabrykaTokenow::claimsAccess($nadpisaniaClaims)),
        oidcAtrapy()
    );

    // Wyjątek, nie `expect()`: sprawdzenie ma ZWĘZIĆ TYP, żeby statyka
    // wiedziała, że poniżej obiekt JEST. Pustka to błąd, nie zero — bez
    // obiektu wszystkie pomiary niżej mierzyłyby `null` i milczały o tym.
    if ($wynik['ok'] !== true) {
        throw new RuntimeException(
            'Fabryka roszczeń ACCESS przestała wystawiać obiekt — dalsze pomiary byłyby o niczym.'
        );
    }

    return $wynik['roszczenia'];
}

/**
 * @param  array<string, mixed>  $nadpisaniaClaims
 */
function roszczeniaId(array $nadpisaniaClaims = []): RoszczeniaZweryfikowane
{
    $wynik = RoszczeniaZweryfikowane::zIdTokenu(
        FabrykaTokenow::podpisz(FabrykaTokenow::claimsId($nadpisaniaClaims)),
        oidcAtrapy(),
        'nonce-testowy'
    );

    if ($wynik['ok'] !== true) {
        throw new RuntimeException(
            'Fabryka roszczeń ID przestała wystawiać obiekt — dalsze pomiary byłyby o niczym.'
        );
    }

    return $wynik['roszczenia'];
}

/** Żądanie z PRAWDZIWYM magazynem sesji — jak w `WaskieGardloTozsamosciTest`. */
function zadanieZSesja(): Request
{
    $zadanie = Request::create('/');
    $zadanie->setLaravelSession(app('session.store'));

    return $zadanie;
}

// ---------------------------------------------------------------------------
// NIEPODRABIALNOŚĆ OBIEKTU WARTOŚCI — jedyna kontrola strukturalna, jedna klasa
// ---------------------------------------------------------------------------

it('obiekt roszczeń ma konstruktor PRYWATNY i żadnej fabryki z tablicy', function (): void {
    $klasa = new ReflectionClass(RoszczeniaZweryfikowane::class);

    expect($klasa->getConstructor()?->isPrivate())->toBeTrue(
        'Konstruktor `RoszczeniaZweryfikowane` NIE jest prywatny. Publiczny konstruktor '.
        'zamienia obiekt wartości z powrotem w tablicę pod inną nazwą: dowolny kod może '.
        'wtedy wytworzyć „zweryfikowane" roszczenia, których nikt nie weryfikował.'
    );

    // Fabryka przyjmująca tablicę byłaby dokładnie tym, czym była
    // `TozsamoscSesji::zMagazynu(array)` przed R6A-3 — publicznym wejściem
    // dla wymyślonych danych.
    $zTablicy = [];

    foreach ($klasa->getMethods(ReflectionMethod::IS_PUBLIC) as $metoda) {
        $zwrot = (string) $metoda->getReturnType();

        if (! str_contains($zwrot, 'self') && ! str_contains($zwrot, RoszczeniaZweryfikowane::class)) {
            continue;
        }

        foreach ($metoda->getParameters() as $parametr) {
            if ((string) $parametr->getType() === 'array') {
                $zTablicy[] = sprintf('%s(array $%s)', $metoda->getName(), $parametr->getName());
            }
        }
    }

    expect($zTablicy)->toBe([],
        'Publiczna metoda oddaje `RoszczeniaZweryfikowane` i przyjmuje TABLICĘ: '.
        implode(', ', $zTablicy).'. Tablicę można wymyślić — podpisu nie.');
});

it('JEDYNE `new` obiektu roszczeń stoi w jego własnej fabryce, za sprawdzeniem podpisu', function (): void {
    // Konstruktor prywatny zamyka tworzenie SPOZA klasy. Ta kontrola pilnuje
    // tego, czego typ nie pilnuje: żeby WEWNĄTRZ klasy nie pojawiła się druga
    // droga — `new self(...)` bez uprzedniej weryfikacji.
    $sciezka = base_path('app/Tozsamosc/RoszczeniaZweryfikowane.php');
    $tokeny = token_get_all((string) file_get_contents($sciezka));
    $funkcje = Kod::funkcje($tokeny);

    $miejsca = [];

    foreach ($tokeny as $i => $token) {
        if (! is_array($token) || $token[0] !== T_NEW) {
            continue;
        }

        $miejsca[] = Kod::funkcjaDla($funkcje, $i);
    }

    expect($miejsca)->toBe(['zTokenu'],
        'Konstrukcja obiektu roszczeń stoi POZA `zTokenu()` albo jej tam nie ma: '.
        json_encode($miejsca, JSON_UNESCAPED_UNICODE).'. Jedyne `new self` ma stać '.
        'za sprawdzeniem podpisu — inaczej „zweryfikowane" znaczy tylko tyle, ile nazwa klasy.');

    // I żeby to nie było zaufaniem do nazwy: fabryka MUSI wołać walidator.
    //
    // Forma z jawnym predykatem, nie `->toContain(igła, komunikat)` — ten
    // matcher jest WARIADYCZNY i drugi argument potraktowałby jak kolejną igłę.
    // Napisałem to najpierw źle i złapała mnie własna kontrola z rundy 7
    // (po rozszerzeniu jej o komunikaty SKLEJANE — patrz `PrzyczynyPerturbacjiTest`).
    $kod = (string) file_get_contents($sciezka);

    expect(str_contains($kod, 'WalidatorTokenu::sprawdz'))->toBeTrue(
        'Fabryka roszczeń nie woła `WalidatorTokenu::sprawdz` — obiekt nazywałby się '.
        '„zweryfikowany", nie będąc nim.');
});

it('poza własną klasą NIKT nie konstruuje obiektu roszczeń', function (): void {
    $winowajcy = [];

    foreach (Kod::plikiWykonywalne() as $plik) {
        if (str_ends_with($plik, 'RoszczeniaZweryfikowane.php')) {
            continue;
        }

        $kod = (string) file_get_contents($plik);

        if (preg_match('/new\s+(\\\\App\\\\Tozsamosc\\\\)?RoszczeniaZweryfikowane\b/', $kod) === 1) {
            $winowajcy[] = Kod::wzgledna($plik);
        }
    }

    expect($winowajcy)->toBe([],
        'Kod poza klasą konstruuje `RoszczeniaZweryfikowane`: '.implode(', ', $winowajcy).'. '.
        'Konstruktor jest prywatny, więc to i tak by nie zadziałało — ale gdyby ktoś '.
        'zdjął prywatność, ta kontrola powie o tym GŁOŚNO, zamiast pozwolić przemilczeć.');

    // Kontrola przyrządu: skaner musi mieć czego szukać. Gdyby wzorzec był
    // martwy (jak `'\$_GET'` w apostrofach — wada własna z rundy 10), pusta
    // lista znaczyłaby „nie umiem szukać", a nie „nie ma".
    expect(preg_match('/new\s+(\\\\App\\\\Tozsamosc\\\\)?RoszczeniaZweryfikowane\b/', 'new RoszczeniaZweryfikowane([])'))
        ->toBe(1, 'Wzorzec skanera nie trafia nawet we wzorcowe wywołanie — kontrola mierzyłaby pustkę.');
});

// ---------------------------------------------------------------------------
// ZASIĘG — WSZYSCY pisarze tożsamości, nie jedna nazwa (lekcja R11-2)
// ---------------------------------------------------------------------------

it('KAŻDY pisarz klucza tożsamości przyjmuje wyłącznie typy NIEPODRABIALNE', function (): void {
    // R11-2 wzięło się DOKŁADNIE stąd, że skanowaliśmy jedną nazwę (`zaloz`),
    // a pisarzy było dwóch. Dlatego tu nie ma listy nazw: pytamy, KTÓRE metody
    // piszą klucz tożsamości, i dopiero potem o ich typy.
    $sciezka = base_path('app/Tozsamosc/SesjaKonta.php');
    $tokeny = token_get_all((string) file_get_contents($sciezka));
    $funkcje = Kod::funkcje($tokeny);

    $pisarze = [];

    foreach ($tokeny as $i => $token) {
        // `put(` na sesji z kluczem tożsamości — zapis stanu tożsamości.
        if (! is_array($token) || $token[0] !== T_STRING || $token[1] !== 'put') {
            continue;
        }

        $nazwa = Kod::funkcjaDla($funkcje, $i);

        if ($nazwa !== '' && ! in_array($nazwa, $pisarze, true)) {
            $pisarze[] = $nazwa;
        }
    }

    sort($pisarze);

    // Pustka to błąd: gdyby parser się rozjechał, kontrola przeszłaby na zero
    // pisarzy i nie zmierzyła niczego.
    expect(count($pisarze))->toBeGreaterThan(1,
        'Znalazłem mniej niż dwóch pisarzy klucza tożsamości ('.json_encode($pisarze).'). '.
        'Wiadomo, że są co najmniej dwaj (`zaloz`, `zaktualizuj`) — czyli skaner mierzy pustkę.');

    // TYPY NIEPODRABIALNE: `Request` (magazyn sesji), `RoszczeniaZweryfikowane`
    // (podpis sprawdzony), `TozsamoscSesji` (dowód, że tożsamość istniała).
    // `?string` jest dopuszczony ŚWIADOMIE i wyłącznie dla refresh tokenu:
    // to poświadczenie do IdP, nie tożsamość — podanie tam bzdury psuje
    // odświeżanie, ale nie zmienia, KIM jest zalogowany. `array` NIE jest
    // dopuszczona i to jest sedno naprawy R11-1.
    $dopuszczone = ['Illuminate\Http\Request', RoszczeniaZweryfikowane::class, TozsamoscSesji::class, '?string'];
    $klasa = new ReflectionClass(SesjaKonta::class);
    $naruszenia = [];

    foreach ($pisarze as $nazwaMetody) {
        foreach ($klasa->getMethod($nazwaMetody)->getParameters() as $parametr) {
            $typ = (string) $parametr->getType();

            if (! in_array($typ, $dopuszczone, true)) {
                $naruszenia[] = sprintf('%s(%s $%s)', $nazwaMetody, $typ, $parametr->getName());
            }
        }
    }

    expect($naruszenia)->toBe([], sprintf(
        'Pisarz tożsamości przyjmuje typ, który da się WYMYŚLIĆ: %s.%sTak wyglądało R11-1: '.
        '`zaloz(Request, array $dane)` pozwalało napisać `[\'sub\' => $request->query(\'code\')]`, '.
        'a skaner kształtu milczał SŁUSZNIE, bo `code` jest w kontrakcie OIDC.',
        implode(', ', $naruszenia),
        PHP_EOL
    ));

    // Kontrola przyrządu: `array` NIE MOŻE być na liście dopuszczonych —
    // inaczej cała ta kontrola przechodziłaby nad naprawianym znaleziskiem.
    expect($dopuszczone)->not->toContain('array');
});

it('tożsamość NIE MA już metody zdolnej podmienić `sub`', function (): void {
    // R11-2: `zPodmienionymi(array)` była publiczna i brała dowolne pola,
    // więc zmieniała, KIM jest tożsamość, a nie tylko ją odświeżała.
    // Pytamy REFLEKSJA, nie `method_exists()`: to drugie PHPStan skleja do
    // stalej i zglasza „will always evaluate to false". Sam ten komunikat
    // jest dowodem — statyka WIE, ze metody nie ma — ale zapadka bramki
    // nie odroznia dowodu od bledu, a kontrola ma dzialac takze wtedy, gdy
    // ktos ta metode PRZYWROCI (i wtedy stala bylaby odwrotna).
    expect((new ReflectionClass(TozsamoscSesji::class))->hasMethod('zPodmienionymi'))->toBeFalse(
        '`TozsamoscSesji::zPodmienionymi()` wróciła. To była droga R11-2: publiczna '.
        'metoda przyjmująca DOWOLNE pola pozwalała podmienić `sub` wartością z żądania.'
    );

    // I szerzej niż jedna nazwa — bo nazwa to znowu lista: ŻADNA publiczna
    // metoda oddająca tożsamość nie ma prawa przyjmować tablicy.
    $klasa = new ReflectionClass(TozsamoscSesji::class);
    $zTablicy = [];

    foreach ($klasa->getMethods(ReflectionMethod::IS_PUBLIC) as $metoda) {
        $zwrot = (string) $metoda->getReturnType();

        if (! str_contains($zwrot, 'self') && ! str_contains($zwrot, TozsamoscSesji::class)) {
            continue;
        }

        foreach ($metoda->getParameters() as $parametr) {
            if ((string) $parametr->getType() === 'array') {
                $zTablicy[] = sprintf('%s(array $%s)', $metoda->getName(), $parametr->getName());
            }
        }
    }

    expect($zTablicy)->toBe([],
        'Publiczna metoda oddaje tożsamość i przyjmuje TABLICĘ: '.implode(', ', $zTablicy).'. '.
        'Dokładnie tak wyglądała `zPodmienionymi` — pod inną nazwą wróciłaby ta sama dziura.');
});

// ---------------------------------------------------------------------------
// KONTROLE NEGATYWNE — oba wektory rundy 11 DOSŁOWNIE, mają RZUCAĆ
// ---------------------------------------------------------------------------

it('R11-1 DOSŁOWNIE: podanie tablicy z `sub` jako tożsamości RZUCA, nie „zapala kontrolę"', function (): void {
    // Wektor weryfikatora: `$sub = $request->query('code')` (pole KONTRAKTOWE),
    // a potem `SesjaKonta::zaloz($request, ['sub' => $sub, 'role' => ['pacjent']])`.
    //
    // Wywołanie budujemy DYNAMICZNIE, bo napisane wprost nie przeszłoby przez
    // Larastana — i to samo w sobie jest częścią dowodu: statyka odrzuca ten
    // kod, zanim ktokolwiek go uruchomi.
    $zadanie = zadanieZSesja();
    $zadanie->merge(['code' => 'OFIARA-R11']);
    $sub = $zadanie->query('code');

    // WYWOLANIE PRZEZ REFLEKSJE — i to nie jest wygoda, tylko konsekwencja
    // naprawy. Zapisane wprost NIE PRZECHODZI STATYKI; zmierzone na tym
    // samym drzewie, Larastan level max:
    //
    //   Parameter #2 $id of callable array{SesjaKonta, 'zaloz'} expects
    //   RoszczeniaZweryfikowane, array<string, (array|string|null)> given.
    //
    // Czyli warunek odbioru ze `ZLECENIE-078` §1.1 („ma przestac przechodzic
    // statyke ALBO rzucac") jest spelniony OBIEMA drogami naraz. Tu mierzymy
    // droge druga: pominiecie statyki konczy sie `TypeError`, a nie zapisem.
    $pisarz = new ReflectionMethod(SesjaKonta::class, 'zaloz');

    expect(fn () => $pisarz->invokeArgs(null, [$zadanie, ['sub' => $sub, 'role' => ['pacjent']], null, null]))
        ->toThrow(TypeError::class);

    // Skutek, nie tylko wyjątek: w sesji NIE MA tożsamości.
    expect($zadanie->session()->get(TozsamoscSesji::KLUCZ))->toBeNull(
        'Mimo rzuconego wyjątku w sesji coś zostało — a wtedy „nie da się" znaczy '.
        'tylko „trudniej".'
    );
});

it('R11-2 DOSŁOWNIE: podmiana `sub` na własnej tożsamości RZUCA — metody już nie ma', function (): void {
    $zadanie = zadanieZSesja();
    $zadanie->session()->put(TozsamoscSesji::KLUCZ, ['sub' => 'ATAKUJACY-PACJENT', 'sid' => 'sid-a']);

    $tozsamosc = SesjaKonta::odczytaj($zadanie);

    if ($tozsamosc === null) {
        throw new RuntimeException('Bez odczytanej tożsamości ten pomiar byłby o niczym.');
    }

    // Wektor weryfikatora: `$t->zPodmienionymi(['sub' => $request->query('code')])`.
    // Nazwa przez zmienna, bo zapisana wprost jest dla statyki bledem —
    // co samo w sobie jest czescia dowodu.
    $metoda = 'zPodmienionymi';

    // Jawny typ zwrotu domkniecia: przy dynamicznej nazwie metody statyka nie
    // umie go wywiesc i zglasza „unresolvable type" na `expect()`.
    $podmien = function () use ($tozsamosc, $metoda): void {
        $tozsamosc->{$metoda}(['sub' => 'VICTIM-KOORDYNATOR']);
    };

    expect($podmien)->toThrow(Error::class);

    // Skutek: tożsamość w sesji NIETKNIĘTA.
    expect(SesjaKonta::odczytaj($zadanie)?->sub())->toBe('ATAKUJACY-PACJENT',
        'Tożsamość w sesji zmieniła się mimo rzuconego wyjątku.');
});

// ---------------------------------------------------------------------------
// KONTROLE POZYTYWNE — legalny przepływ MUSI działać
// ---------------------------------------------------------------------------

it('POZYTYWNA: legalny przepływ (walidator → obiekt → `zaloz`) ustanawia tożsamość', function (): void {
    // Bez tej kontroli poprzednie dwie byłyby spełnione także przez kod,
    // który rzuca ZAWSZE — czyli przez system, w którym nikt się nie zaloguje.
    $zadanie = zadanieZSesja();

    SesjaKonta::zaloz($zadanie, roszczeniaId(), roszczeniaAccess(), 'refresh-abc');

    $tozsamosc = SesjaKonta::odczytaj($zadanie);

    expect($tozsamosc?->sub())->toBe('sub-abc-123')
        ->and($tozsamosc?->sid())->toBe('sid-abc-123')
        ->and($tozsamosc?->refreshToken())->toBe('refresh-abc')
        ->and($tozsamosc?->dane['role'])->toContain('psycholog');
});

it('POZYTYWNA: odświeżenie zmienia ROLE, a `sub` zostaje — nawet gdy nowy token mówi inaczej', function (): void {
    // Sedno naprawy R11-2: `zOdswiezonymi` NIE MA parametru zdolnego ruszyć
    // tożsamość. Sprawdzam to najostrzej, jak się da — podaję roszczenia
    // z INNYM `sub` i wymagam, żeby tożsamość została stara.
    $zadanie = zadanieZSesja();
    SesjaKonta::zaloz($zadanie, roszczeniaId(), roszczeniaAccess(), 'refresh-abc');

    $obecna = SesjaKonta::odczytaj($zadanie);

    if ($obecna === null) {
        throw new RuntimeException('Bez tożsamości w sesji nie ma czego odświeżać — pomiar byłby o niczym.');
    }

    $nowa = $obecna->zOdswiezonymi(
        roszczeniaAccess([
            'sub' => 'VICTIM-KOORDYNATOR',
            'realm_access' => ['roles' => ['koordynator']],
        ]),
        'refresh-nowy'
    );

    expect($nowa->sub())->toBe('sub-abc-123',
        'Odświeżenie zmieniło `sub` — czyli zmieniło, KIM jest zalogowany. To jest R11-2.')
        ->and($nowa->dane['role'])->toContain('koordynator')
        ->and($nowa->refreshToken())->toBe('refresh-nowy');
});

it('obiekt roszczeń NIE POWSTAJE, gdy podpis nie przechodzi', function (): void {
    // Kontrola przyrządu dla całej reszty: gdyby `zTokenu` oddawało obiekt
    // zawsze, „niepodrabialność" byłaby pustym słowem.
    $zepsuty = FabrykaTokenow::podpisz(FabrykaTokenow::claimsAccess()).'-doklejka';

    $wynik = RoszczeniaZweryfikowane::zAccessTokenu($zepsuty, oidcAtrapy());

    expect($wynik['ok'])->toBeFalse()
        ->and($wynik['roszczenia'])->toBeNull()
        ->and($wynik['nieudane'])->not->toBe([]);
});

it('obiekt roszczeń niesie DOKŁADNIE ten token, który zweryfikował', function (): void {
    // Wcześniej kontroler niósł surowy ID token OSOBNYM parametrem — i nic
    // nie wiązało go z wynikiem walidacji. Można było zapisać do sesji
    // `id_token_hint` inny niż sprawdzony.
    $token = FabrykaTokenow::podpisz(FabrykaTokenow::claimsId());
    $wynik = RoszczeniaZweryfikowane::zIdTokenu($token, oidcAtrapy(), 'nonce-testowy');

    if ($wynik['ok'] !== true) {
        throw new RuntimeException('Poprawny token ID nie dał obiektu — pomiar byłby o niczym.');
    }

    expect($wynik['roszczenia']->tokenSurowy())->toBe($token);
});

// ---------------------------------------------------------------------------
// PODSTAWA ZAUFANIA — znalezisko „szóste piętro" (`ZLECENIE-079` §1)
// ---------------------------------------------------------------------------

it('obiekt roszczeń NIE PRZYJMUJE tablicy od wołającego — także wymagań walidacji', function (): void {
    // Poprzednia kontrola pytała tylko o metody ODDAJĄCE obiekt, więc nie
    // widziała `zTokenu(string, array): array` — a to właśnie tamtą tablicą
    // wchodził materiał klucza. Teraz pytamy o KAŻDĄ metodę publiczną.
    //
    // Kontrola pilnuje ISTOTY, nie nazwy: nieważne, jak metoda się nazywa
    // i co zwraca — jeżeli jest publiczna i bierze tablicę, wołający znowu
    // decyduje, wobec czego token jest „zweryfikowany".
    $klasa = new ReflectionClass(RoszczeniaZweryfikowane::class);
    $zTablica = [];

    foreach ($klasa->getMethods(ReflectionMethod::IS_PUBLIC) as $metoda) {
        foreach ($metoda->getParameters() as $parametr) {
            if ((string) $parametr->getType() === 'array') {
                $zTablica[] = sprintf('%s(array $%s)', $metoda->getName(), $parametr->getName());
            }
        }
    }

    expect($zTablica)->toBe([], sprintf(
        'PUBLICZNA METODA OBIEKTU ROSZCZEŃ PRZYJMUJE TABLICĘ: %s.%s'.
        'Tą drogą wchodziły WYMAGANIA WALIDACJI razem z `jwks`. Kto podaje materiał '.
        'klucza, ten rozstrzyga, wobec czego podpis jest poprawny — a wtedy słowo '.
        '„zweryfikowane" nie znaczy nic ponad „przeszło przez walidator".',
        implode(', ', $zTablica),
        PHP_EOL
    ));

    // Kontrola przyrządu: wejście składające wymagania MUSI istnieć i być PRYWATNE.
    // Gdyby zniknęło, ta kontrola przechodziłaby nad pustką.
    expect($klasa->hasMethod('zTokenu'))->toBeTrue('Wejście składające wymagania zniknęło — kontrola mierzy pustkę.')
        ->and($klasa->getMethod('zTokenu')->isPrivate())->toBeTrue(
            '`zTokenu()` znowu jest dostępne z zewnątrz — to jest dokładna droga „szóstego piętra".'
        );
});

it('KLASY TOŻSAMOŚCI SĄ `final` — inaczej prywatny konstruktor obchodzi się dziedziczeniem', function (): void {
    // ⛔ `ZLECENIE-079` §3: „`final` bez pomiaru jest deklaracją".
    //
    // Klasa potomna nie sięgnie po PRYWATNY konstruktor rodzica, ale może
    // dodać własny publiczny i przekazać się dalej jako ten typ — o ile typ
    // pozwala się rozszerzyć. `final` to zamyka, tylko dotąd nikt tego
    // nie mierzył.
    foreach ([RoszczeniaZweryfikowane::class, TozsamoscSesji::class] as $nazwa) {
        expect((new ReflectionClass($nazwa))->isFinal())->toBeTrue(sprintf(
            'Klasa %s NIE jest `final`. Prywatny konstruktor broni przed `new`, '.
            'ale nie przed klasą potomną, która poda się za ten typ.',
            $nazwa
        ));
    }
});

it('OBCY KLUCZ: token o poprawnym kształcie, podpisany NIE NASZYM kluczem, NIE daje roszczeń', function (): void {
    // Najmocniejszy pomiar tej naprawy — bo mierzy SKUTEK, nie kształt kodu.
    //
    // Token jest pod każdym względem poprawny: właściwy wystawca, właściwa
    // audiencja, świeże czasy, prawidłowy `alg`. Jedyna różnica to KLUCZ.
    // Dopóki wołający mógł podać `jwks`, taki token dawał obiekt roszczeń.
    $obcyKlucz = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);

    expect($obcyKlucz)->not->toBeFalse('Nie udało się wygenerować klucza napastnika — pomiar byłby o niczym.');

    /** @var OpenSSLAsymmetricKey $obcyKlucz */
    $naglowek = ['alg' => 'RS256', 'typ' => 'JWT', 'kid' => FabrykaTokenow::KID];
    $claims = FabrykaTokenow::claimsAccess();

    $czesci = [
        rtrim(strtr(base64_encode((string) json_encode($naglowek)), '+/', '-_'), '='),
        rtrim(strtr(base64_encode((string) json_encode($claims)), '+/', '-_'), '='),
    ];

    $podpis = '';
    openssl_sign(implode('.', $czesci), $podpis, $obcyKlucz, OPENSSL_ALGO_SHA256);
    $czesci[] = rtrim(strtr(base64_encode(Typy::napis($podpis)), '+/', '-_'), '=');

    $wynik = RoszczeniaZweryfikowane::zAccessTokenu(implode('.', $czesci), oidcAtrapy());

    expect($wynik['ok'])->toBeFalse(
        'Token podpisany CUDZYM kluczem dał roszczenia. Podstawa zaufania nie pochodzi '.
        'z naszej konfiguracji — obiekt nazywa się „zweryfikowany", nie mówiąc, wobec czego.'
    )->and($wynik['roszczenia'])->toBeNull()
        ->and($wynik['kontrole']['signature'] ?? null)->toBe('fail',
            'Odmowa przyszła z INNEGO ogniwa niż podpis — czyli mierzę coś innego,'.
            ' niż mi się wydaje. Nazwa kontroli pochodzi z `WalidatorTokenu`.');

    // KONTROLA PRZYRZĄDU (`ZLECENIE-079` §1.3): podstawione IdP MUSI dawać
    // tożsamość. Bez tego „nie da się zalogować" udawałoby bezpieczeństwo,
    // a powyższa czerwień znaczyłaby tylko tyle, że nic nie działa.
    $nasz = RoszczeniaZweryfikowane::zAccessTokenu(
        FabrykaTokenow::podpisz(FabrykaTokenow::claimsAccess()),
        oidcAtrapy()
    );

    expect($nasz['ok'])->toBeTrue(
        'Token podpisany NASZYM kluczem też nie przechodzi — czyli kontrola wyżej '.
        'mierzy awarię atrapy IdP, a nie odrzucenie obcego klucza.'
    );
});
