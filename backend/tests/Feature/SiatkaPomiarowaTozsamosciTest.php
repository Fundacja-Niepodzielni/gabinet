<?php

declare(strict_types=1);

use App\Tozsamosc\TozsamoscSesji;
use App\Wsparcie\Typy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\Wsparcie\Kod;
use Tests\Wsparcie\Trasy;

/**
 * TRZECIA SIATKA — POMIAROWA, nie deklaratywna. (D-1b, kierunek z rundy 7 §3b)
 *
 * ================== DLACZEGO DWIE POPRZEDNIE NIE WYSTARCZAJĄ ==================
 *
 * `BrakWlasnychHaselTest` trzyma dwie siatki DEKLARATYWNE i obie pytają o SPOSÓB:
 *
 *   · allowlista prymitywów — „czy użyto funkcji kryptograficznej spoza listy";
 *   · allowlista schematu i tras — „czy powstała nowa kolumna albo nowa trasa".
 *
 * Weryfikator rundy 7 przeszedł przez OBIE naraz, i to bez żadnej egzotyki:
 * porównanie `===` wartości z żądania z sekretem w kolumnie JUŻ ZADEKLAROWANEJ
 * (`users.nazwa_wyswietlana`), na trasie JUŻ ZADEKLAROWANEJ (`GET /`).
 *
 *     DOWÓD MUTACJI: trasa GET / niesie mechanizm hasła = True
 *     BrakWlasnychHaselTest → 9 passed        (obie siatki ŚLEPE)
 *     Larastan (level max)  → No errors
 *
 * Żadna funkcja krypto (więc pierwsza siatka nie ma czego zobaczyć), żadna nowa
 * kolumna ani trasa (więc druga też nie). Mechanizm własnych haseł działał przy
 * zielonej bramce — czyli dokładnie to, czego zakazuje `CLAUDE.md` §2.
 *
 * ================== CZYM TA SIATKA SIĘ RÓŻNI ==================
 *
 * Pyta o SKUTEK, nie o sposób: **czy jakakolwiek trasa poza `/auth/callback`
 * ustanawia tożsamość w sesji.** Skutek jest pierwotny wobec sposobu — obejmuje
 * `===`, `hash()`, `sodium_*`, porównanie w bazie, cokolwiek jeszcze ktoś wymyśli,
 * bo każdy taki mechanizm MUSI zakończyć się zapisem tożsamości. Inaczej nikogo
 * nie zaloguje i nie jest mechanizmem logowania.
 *
 * GRANICE TEJ SIATKI, ZMIERZONE — nie deklarowane (18.08):
 *
 *   1. WERDYKT to zapis pod klucz sesji `konta` PO zadaniu. Mechanizm, ktory
 *      ustanawia tozsamosc na trasie A dopiero przez ciasteczko odczytane
 *      na trasie B, nie zostanie tu zlapany — siatka nie przenosi ciasteczek
 *      miedzy zadaniami. Lapie go natomiast `WaskieGardloTozsamosciTest`,
 *      bo hydratacja tozsamosci MUSI dotknac klucza albo fasady. Zasieg tamtej
 *      kontroli rozszerzono 18.08 o `routes/` dokladnie z tego powodu:
 *      zmierzone, ze zapis `konta` w `routes/web.php` dawal jej `5 passed`.
 *   2. Sterownik sesji w testach to `array` (`phpunit.xml:56`), wiec zapis
 *      wprost do magazynu trafia w TEN SAM obiekt, ktory czyta szpieg.
 *      Mechanizm piszacy do INNEGO magazynu (np. wprost do tabeli sesji)
 *      nie zalogowalby nikogo w tym srodowisku — to nie jest przeoczenie
 *      siatki, tylko rzecz niemierzalna ta droga.
 *   3. Naglowki HTTP sa od 18.08 WYSYLANE, ale ta droga NIE JEST
 *      potwierdzona pomiarem. Zmierzone: przy mechanizmie czytajacym
 *      `header('X-Zaklecie')` naglowek DOCHODZI z wlasciwa wartoscia
 *      (zweryfikowane zrzutem), a mimo to sonda nie wykrywa zapisu.
 *      Przyczyny nie ustalilem i NIE UDAJE, ze granica zostala zdjeta —
 *      sondowanie zostaje, bo nic nie kosztuje, ale liczyc na nie nie wolno.
 *      Ten wektor zamyka STRUKTURALNIE `WaskieGardloZapisuTozsamosciTest`
 *      (perturbacja `gardlo_naglowek` — czerwien z wlasciwej przyczyny).
 *   4. Parowanie pol: ladunek „dopasowany" dobiera wartosc KSZTALTEM nazwy
 *      (mail/sub/reszta). Mechanizm wymagajacy pary nazw o nieprzewidzianym
 *      ksztalcie nadal sie wymknie — i to jest heurystyka, nie dowod.
 *
 * ⚠ NAJWAZNIEJSZE O TYCH GRANICACH: od 18.08 siatka NIE JEST juz jedynym
 * detektorem §2. Zapis tozsamosci poza fasada lapie STRUKTURALNIE
 * `WaskieGardloZapisuTozsamosciTest` — niezaleznie od tego, ilu i jakimi
 * polami sekret przyjechal. Siatka jest odtad WZMOCNIENIEM, ktore pokrywa
 * wektor niedostepny analizie statycznej (klucz sesji budowany w czasie
 * dzialania), a nie jedyna linia obrony.
 *
 * `CLAUDE.md` §2 mówi: „Logowanie wyłącznie przez Konta Niepodzielni". Zdanie
 * to ma dokładnie jedno maszynowe znaczenie: JEDYNĄ trasą, po której w sesji
 * pojawia się klucz `konta`, jest callback OIDC.
 *
 * ================== DLACZEGO SZPIEG, A NIE ODCZYT ODPOWIEDZI ==================
 *
 * Prototyp weryfikatora czytał treść odpowiedzi (`{"zalogowany":true}`). Sam
 * nazwał jego granicę: mechanizm, który mintuje sesję BEZ echa w odpowiedzi,
 * wymyka się takiej postaci. Dlatego tutaj patrzymy nie na to, co trasa ODPOWIADA,
 * tylko co ZAPISUJE — na magazyn sesji po żądaniu. Odpowiedź może kłamać albo
 * milczeć; zapisana tożsamość nie ma jak.
 */

/**
 * Trasy zdefiniowane w NASZYM kodzie — nie z `vendor/` (Horizon, Scramble).
 *
 * @return list<array{metoda: string, uri: string}>
 */
function trasyDoObejrzenia(): array
{
    $trasy = [];

    foreach (Route::getRoutes()->getRoutes() as $trasa) {
        if (! Trasy::zNaszegoKodu($trasa)) {
            continue;
        }

        foreach ($trasa->methods() as $metoda) {
            if (! is_string($metoda) || in_array($metoda, ['HEAD', 'OPTIONS'], true)) {
                continue;
            }

            $trasy[] = ['metoda' => $metoda, 'uri' => '/'.ltrim($trasa->uri(), '/')];
        }
    }

    return $trasy;
}

/**
 * Nazwy pól wejściowych, które NASZ KOD gdziekolwiek czyta z żądania.
 *
 * ⛔ TO JEST NAPRAWA R8-1 I WARTO ROZUMIEĆ, CO DOKŁADNIE NAPRAWIA.
 *
 * Siatka reklamowała pomiar SKUTKU „niezależnie od sposobu", a sondowała
 * ZAMKNIĘTĄ baterią dziewięciu nazw. Mechanizm czytający sekret pod nazwą
 * spoza listy produkuje ten sam skutek — zapis tożsamości do sesji — ale
 * siatka nigdy nie wysyłała takiego pola, więc go nie wyzwalała.
 * Zmierzone przez rundę 8 na nazwie `zaklecie`:
 *
 *     siatka POMIAROWA  → 3 passed        (ŚLEPA)
 *     siatki deklaratywne → 9 passed
 *     pełna suita       → 289 passed
 *     a `session()->has('konta')` → TAK   (logowanie poza OIDC)
 *
 * Pomiar skutku wrócił więc po cichu do pytania o SPOSÓB — o nazwę wejścia.
 * Gorsze od samej luki było zdanie, które ją „znosiło": `ODPOWIEDZ-062` §8
 * twierdziła, że nazwę spoza baterii łapie perturbacja `d1b` — a `d1b`
 * czytała `nazwa_wyswietlana`, czyli nazwę Z BATERII. Sieć bezpieczeństwa
 * nie istniała.
 *
 * ================== DLACZEGO ODCZYT ŹRÓDEŁ NIE JEST NAWROTEM ==================
 *
 * Czytamy kod, żeby dowiedzieć się, JAKIE POLA ten kod w ogóle czyta — i tylko
 * po to, żeby zbudować ŁADUNEK. WERDYKT nadal wydaje SKUTEK: zapis tożsamości
 * do sesji. To jest różnica między „sprawdzam, czy w kodzie jest krypto"
 * (siatka deklaratywna, obalona w rundzie 7) a „pytam kod, gdzie ma usta,
 * i wkładam tam sekret".
 *
 * Mechanizm musi skądś wziąć sekret. Jeśli czyta go pod nazwą `zaklecie`,
 * to `zaklecie` STOI W ŹRÓDLE — inaczej nie miałby jak go odczytać.
 *
 * @return list<string>
 */
function nazwyPolWejsciowych(): array
{
    // ⛔ ŻADNEJ LISTY METOD — R9-2.
    //
    // Poprzednia wersja miała ZAMKNIĘTĄ listę metod czytających żądanie
    // i nie znała `all`, `only`, `except`, `json`, `collect`, `validate`
    // ani dostępu tablicowego. Runda 9 zmierzyła skalę: na czystym drzewie
    // parser znajdował **cztery nazwy z całej aplikacji**, a asercja
    // broniąca („> 2 nazwy") tego nie odróżniała od zdrowia.
    //
    // Lista metod jest tą samą klasą wady co lista nazw pól, którą
    // naprawiała runda 8 — tylko piętro wyżej. Trzecia lista byłaby
    // trzecim krokiem tej samej klasy, więc listy nie ma wcale:
    // bierzemy KAŻDY literał napisowy o kształcie nazwy pola.
    //
    // Nadmiar niczego nie kosztuje (dodatkowe pole w ładunku jest
    // obojętne), a pominięcie oznacza ślepotę — autor zapisał tę
    // asymetrię już przy `get`/`header` i tu obowiązuje tak samo.
    $nazwy = [];

    foreach (Kod::plikiWykonywalne() as $sciezka) {
        foreach (token_get_all((string) file_get_contents($sciezka)) as $token) {
            if (! is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) {
                continue;
            }

            $nazwy[] = trim($token[1], "'\"");
        }
    }

    // Kształt nazwy pola formularza. Odsiewa ścieżki (`/auth/ja`), zdania
    // z komunikatów i wyrażenia regularne — nie listą wyjątków, tylko
    // kształtem, żeby filtr nie wymagał utrzymania.
    $nazwy = array_values(array_unique(array_filter(
        $nazwy,
        static fn (string $n): bool => preg_match('/^[A-Za-z_][A-Za-z0-9_.-]{0,60}$/', $n) === 1
    )));

    sort($nazwy);

    return $nazwy;
}

/**
 * Czy po tym żądaniu w sesji stoi tożsamość.
 *
 * Klucz bierzemy ze STAŁEJ typu, nie z literału: gdyby nazwa kiedyś się zmieniła,
 * literał przepisany tutaj z pamięci sprawiłby, że szpieg patrzy w puste miejsce
 * i milczy — czyli świeci zielono nad każdą luką (to jest R6A-11 w czystej postaci).
 */
function tozsamoscWSesji(): bool
{
    return session()->has(TozsamoscSesji::KLUCZ);
}

it('D-1b: ŻADNA trasa poza callbackiem OIDC nie ustanawia tożsamości w sesji', function (): void {
    $trasy = trasyDoObejrzenia();

    // Pustka to błąd, nie zero: siatka bez tras mierzyłaby nic i świeciła zielono.
    expect(count($trasy))->toBeGreaterThan(3,
        'Zobaczyłem mniej niż cztery trasy własne — enumeracja się rozjechała, '.
        'a siatka mierzyłaby pustkę (D-1b).');

    // ⛔ KONTO MUSI ISTNIEĆ — inaczej siatka mierzy pustkę i o tym nie wie.
    //
    // ZMIERZONE 12.08 perturbacją `d1b`: pierwsza wersja sondowała wartościami
    // wziętymi z powietrza (`'haslo' => 'Test'`) i przy WŁOŻONYM ataku dała
    // ZERO TRAFIEŃ. Powód jest oczywisty po fakcie: mechanizm poświadczeń
    // porównuje wartość z żądania z sekretem W BAZIE, a baza testowa jest pusta.
    // Żaden sekret nie pasuje do niczego, więc atak nie loguje nikogo i siatka
    // słusznie nic nie widzi — świecąc przy tym zielono nad żywym mechanizmem.
    //
    // To jest gałąź zdegenerowana w najczystszej postaci: „zero trafień" było
    // zgodne z DWOMA światami — „nikt nie loguje poza OIDC" i „nie ma z czym
    // porównać". Perturbacja rozdzieliła je w pierwszym przebiegu.
    //
    // Zakładamy więc konto o ZNANYCH wartościach i sondujemy DOKŁADNIE NIMI.
    // Każdy mechanizm porównujący wartość z żądania z kolumną tego wiersza —
    // obojętne, czy przez `===`, `hash()`, czy zapytanie do bazy — trafi.
    $konto = [
        'keycloak_sub' => 'sub-siatki-pomiarowej',
        'email' => 'siatka-pomiarowa@example.test',
        'nazwa_wyswietlana' => 'SekretSiatkiPomiarowej',
        'email_potwierdzony' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    DB::table('users')->insert($konto);

    // ⛔ ŁADUNEK POCHODZI Z PÓL, KTÓRE KOD NAPRAWDĘ CZYTA (R8-1).
    //
    // Bateria ZOSTAJE, ale jako WZMOCNIENIE, nie jako nośnik czułości —
    // i to jest cała różnica wobec stanu, który obaliła runda 8. Nośnikiem
    // są `nazwyPolWejsciowych()`: nazwy wyczytane z naszych źródeł.
    //
    // Trzecim składnikiem jest nazwa, której NIKT nie czyta. Nie jest ozdobą:
    // mechanizm skanujący `$request->all()` w poszukiwaniu wartości pasującej
    // do sekretu nie potrzebuje ŻADNEJ konkretnej nazwy, więc żadna lista go
    // nie wyzwoli — a dowolne pole niosące sekret owszem.
    $odkryte = nazwyPolWejsciowych();

    // ⛔ PRÓG PORÓWNYWANY Z DRUGIM ODCZYTEM, NIE ZE STAŁĄ (R9-2).
    //
    // Stara asercja brzmiała „> 2 nazwy" i przechodziła przy CZTERECH
    // nazwach z całej aplikacji — czyli „4" i „40" były dla niej tym samym.
    // Próg wzięty z sufitu nie odróżnia „parser czyta źródła" od „parser
    // czyta ich ułamek", a to jest cała różnica między siatką a atrapą.
    //
    // Drugi odczyt jest CELOWO prymitywny i CELOWO inną drogą: zwykły
    // `preg_match_all` po treści plików, bez leksera. Ma prawo znaleźć
    // więcej (łapie też literały w komentarzach), ale nie ma prawa
    // znaleźć DUŻO więcej — rozjazd rzędu wielkości znaczy, że parser
    // przestał widzieć źródła.
    $drugiOdczyt = [];

    foreach (Kod::plikiWykonywalne() as $sciezka) {
        preg_match_all(
            '/[\'"]([A-Za-z_][A-Za-z0-9_.-]{0,60})[\'"]/',
            (string) file_get_contents($sciezka),
            $trafienia
        );
        foreach ($trafienia[1] as $nazwa) {
            $drugiOdczyt[$nazwa] = true;
        }
    }

    $drugi = count($drugiOdczyt);

    expect($drugi)->toBeGreaterThan(20,
        'Drugi, niezależny odczyt też znalazł prawie nic — rozjechały się OBA, '.
        'więc porównanie ich ze sobą niczego nie dowodzi.');

    expect(count($odkryte))->toBeGreaterThan((int) ($drugi / 2), sprintf(
        "PARSER NAZW PÓL WIDZI UŁAMEK ŹRÓDEŁ: %d nazw, gdy drugi, niezależny\n".
        "odczyt tych samych plików daje %d. Ładunek wraca wtedy po cichu do samej\n".
        "baterii — czyli do stanu, który obaliła runda 8 (R8-1), a rozjazd parsera\n".
        'ze źródłami jest znaleziskiem R9-2.',
        count($odkryte),
        $drugi
    ));

    // Kontrola ŚRODKA, nie samej liczby: `code` i `state` czyta callback OIDC.
    // Gdyby zniknęły, skaner mierzyłby coś innego, niż twierdzi.
    foreach (['code', 'state'] as $spodziewana) {
        expect(in_array($spodziewana, $odkryte, true))->toBeTrue(sprintf(
            'Skaner nie widzi pola `%s`, które czyta `LogowanieController`. '.
            'Parser rozjechał się ze źródłami (R8-1).',
            $spodziewana
        ));
    }

    $bateria = [
        'haslo', 'password', 'pin', 'sekret', 'token', 'kod', 'nazwa_wyswietlana',
        'email', 'sub',
    ];

    $nazwy = array_values(array_unique(array_merge($odkryte, $bateria, ['pole-bez-czytelnika'])));

    // Sekret podajemy pod KAŻDĄ nazwą, w trzech wariantach wartości — bo
    // mechanizm może porównywać z dowolną kolumną założonego konta.
    $wartosci = [
        'nazwa' => Typy::napis($konto['nazwa_wyswietlana']),
        'email' => Typy::napis($konto['email']),
        'sub' => Typy::napis($konto['keycloak_sub']),
    ];

    $ladunki = [[]];

    foreach ($wartosci as $wartosc) {
        $ladunek = [];

        foreach ($nazwy as $nazwa) {
            $ladunek[$nazwa] = $wartosc;
        }

        $ladunki[] = $ladunek;
    }

    // ⛔ ŁADUNEK ROZDZIELAJĄCY WARTOŚCI — R9-1.
    //
    // Powyższe przebiegi dają WSZYSTKIM polom TĘ SAMĄ wartość, i to była
    // ślepa plama: kanoniczny formularz logowania potrzebuje DWÓCH pól
    // o RÓŻNYCH wartościach naraz (`email` = adres, `hasło` = sekret).
    // Runda 9 przeszła tędy przez całą bramkę.
    //
    // Iloczyn kartezjański (nazwa × wartość) byłby poprawny i za drogi:
    // przy kilkuset nazwach to setki żądań na trasę. Zamiast tego jeden
    // przebieg, w którym wartość dobiera KSZTAŁT NAZWY — pola „mailowe"
    // dostają adres, „subowe" identyfikator, reszta sekret.
    //
    // To jest heurystyka i mówię to wprost: mechanizm wymagający pary
    // nazw, których kształtu nie przewidziałem, nadal się tędy wymknie.
    // Nie jest to jednak jedyna linia obrony — od 18.08 zapis tożsamości
    // poza fasadą łapie STRUKTURALNIE `WaskieGardloZapisuTozsamosciTest`,
    // niezależnie od tego, ilu i jakimi polami sekret przyjechał.
    $dopasowany = [];

    foreach ($nazwy as $nazwa) {
        $dopasowany[$nazwa] = match (true) {
            str_contains(mb_strtolower($nazwa), 'mail') => $wartosci['email'],
            str_contains(mb_strtolower($nazwa), 'sub') => $wartosci['sub'],
            str_contains(mb_strtolower($nazwa), 'keycloak') => $wartosci['sub'],
            default => $wartosci['nazwa'],
        };
    }

    $ladunki[] = $dopasowany;

    // Atrapa HTTP: bez niej przeglad wszystkich tras probuje realnie siegnac
    // do IdP (odkrycie OIDC na `/auth/login`) i czeka na limity czasu — 32 s
    // zmierzone. Siatka nie pyta o IdP, tylko o ZAPIS DO SESJI, wiec sieci tu
    // nie potrzebuje; zostawiona kosztowalaby bramke tyle, co cala reszta suity.
    Http::fake();

    $trafienia = [];

    foreach ($trasy as $t) {
        if ($t['uri'] === '/auth/callback') {
            continue;   // JEDYNA trasa, której wolno ustanowić tożsamość
        }

        // Adresy z parametrami wypełniamy wartością obojętną — chodzi o to,
        // żeby trasa w ogóle się wykonała, a nie o poprawność danych.
        $uri = (string) preg_replace('/\{[^}]+\}/', '1', $t['uri']);
        $metoda = $t['metoda'];

        foreach ($ladunki as $ladunek) {
            session()->flush();

            // NAGŁÓWKI obok parametrów — trzecia z nazwanych granic siatki,
            // wykorzystana przez rundę 9 (`X-Zaklecie`). Wysyłamy te same
            // nazwy dwiema drogami: wprost i z przedrostkiem `X-`, bo tak
            // wygląda konwencja nagłówków własnych.
            // ⛔ NAGŁÓWKI PROTOKOŁU WYŁĄCZONE — i to nie jest ostrożność,
            // tylko warunek działania sondy.
            //
            // Nazwy pochodzą ze ŹRÓDEŁ, więc trafiają się wśród nich `Host`,
            // `Cookie`, `Accept` i inne nagłówki, którymi steruje protokół.
            // Ustawienie ich rozbija żądanie ZANIM dojdzie do trasy —
            // zmierzone: sonda z nagłówkami nie wykrywała mechanizmu, który
            // przy jednym nagłówku wykrywa (`X-Zaklecie` → tożsamość w sesji).
            // Nie było to więc „nagłówki nie działają", tylko „sonda strzelała
            // sobie w stopę nagłówkiem `Host`".
            $protokolowe = [
                'host', 'cookie', 'authorization', 'accept', 'accept-encoding',
                'accept-language', 'content-type', 'content-length', 'connection',
                'referer', 'origin', 'user-agent', 'expect', 'range', 'te', 'upgrade',
            ];

            $naglowki = [];

            foreach ($ladunek as $nazwa => $wartosc) {
                if (! in_array(mb_strtolower($nazwa), $protokolowe, true)) {
                    $naglowki[$nazwa] = $wartosc;
                }

                $naglowki['X-'.$nazwa] = $wartosc;
            }

            try {
                test()->withHeaders($naglowki)->call($metoda, $uri, $ladunek);
            } catch (Throwable) {
                // Wyjątek trasy nie jest przedmiotem pomiaru. Przedmiotem jest
                // to, co ZOSTAŁO w sesji — a to sprawdzamy niezależnie od tego,
                // czy żądanie doszło do końca.
            }

            if (tozsamoscWSesji()) {
                $trafienia[] = sprintf(
                    '%s %s (ładunek: %s)',
                    $metoda,
                    $uri,
                    $ladunek === [] ? 'pusty' : count($ladunek).' pól, w tym '.implode(', ', array_slice(array_keys($ladunek), 0, 4)).' …'
                );
            }
        }
    }

    session()->flush();

    expect($trafienia)->toBe([], sprintf(
        "TRASA SPOZA CALLBACKU USTANOWIŁA TOŻSAMOŚĆ W SESJI:\n  %s\n\n".
        "`CLAUDE.md` §2: „Logowanie wyłącznie przez Konta Niepodzielni (Keycloak). \n".
        'ŻADNYCH własnych haseł w tym systemie.'."\n".
        'Ta siatka pyta o SKUTEK, nie o sposób — więc trafienie tutaj znaczy, że '.
        'mechanizm logowania obok OIDC ISTNIEJE, niezależnie od tego, jakim prymitywem '.
        'porównuje sekret i czy powstała pod niego nowa kolumna albo trasa (D-1b).',
        implode("\n  ", $trafienia)
    ));
});

it('D-1b KIERUNEK ODWROTNY: szpieg widzi zapis tożsamości — bez tego zielone nic nie znaczy', function (): void {
    // Kontrola przyrządu. Siatka wyżej mówi „zero trafień"; ta sama wartość
    // powstałaby, gdyby szpieg patrzył w niewłaściwy klucz albo gdyby `session()`
    // w tym teście była innym obiektem niż ta zapisywana przez trasę.
    //
    // Materiał zbudowany pod rękę — zapisujemy tożsamość ręcznie i żądamy,
    // żeby szpieg ją zobaczył.
    session()->flush();

    expect(tozsamoscWSesji())->toBeFalse('Szpieg widzi tożsamość w PUSTEJ sesji — mierzy coś innego.');

    session()->put(TozsamoscSesji::KLUCZ, [
        'sub' => 'sub-kontrolne',
        'role' => ['pacjent'],
    ]);

    expect(tozsamoscWSesji())->toBeTrue(
        'Szpieg NIE WIDZI tożsamości zapisanej wprost pod obserwowanym kluczem. '.
        'Zielone siatki wyżej znaczyłoby wtedy „patrzę w puste miejsce", a nie '.
        '„żadna trasa nie loguje" (D-1b).'
    );

    session()->flush();

    expect(tozsamoscWSesji())->toBeFalse('Szpieg nie zauważa wyczyszczenia sesji — trafienia byłyby lepkie.');
});

it('D-1b: trasa callbacku JEST na liście tras — wyjątek nie może być martwy', function (): void {
    // Wyjątek dla `/auth/callback` opisuje trasę, która MUSI istnieć. Gdyby
    // zniknęła albo zmieniła adres, wyjątek przestałby cokolwiek wyłączać,
    // a siatka wyglądałaby na surowszą, niż jest — i nikt by się nie dowiedział.
    $adresy = array_column(trasyDoObejrzenia(), 'uri');

    expect(in_array('/auth/callback', $adresy, true))->toBeTrue(
        'Na liście tras NIE MA `/auth/callback`, a siatka robi dla niego wyjątek. '.
        'Wyjątek bez przedmiotu jest martwy: albo trasa zmieniła adres (wtedy popraw '.
        'wyjątek), albo callback OIDC zniknął (wtedy problem jest większy niż ta kontrola).'
    );

    expect(Typy::napis(TozsamoscSesji::KLUCZ))->toBe('konta',
        'Klucz tożsamości zmienił nazwę — sprawdź, czy `perturbacje.sh` i pozostałe '.
        'kontrole nadal patrzą w to samo miejsce.');
});
