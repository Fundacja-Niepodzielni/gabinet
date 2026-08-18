<?php

declare(strict_types=1);

use App\Tozsamosc\TozsamoscSesji;
use App\Wsparcie\Typy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
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

    // Bateria nazw parametrów: atakujący wybiera nazwę, więc żadna lista nie jest
    // dowodem pokrycia — i mówimy to wprost. Bateria podnosi CZUŁOŚĆ siatki,
    // ale nie na niej opiera się jej moc: mechanizm przyjmujący sekret pod nazwą
    // spoza baterii zostanie złapany przez perturbację `d1b`, która wywołuje go
    // dokładnie tak, jak zrobiłby to atakujący.
    //
    // WARTOŚCI pochodzą z założonego konta, nie z wyobraźni — to jest różnica
    // między sondą a życzeniem.
    $bateria = [];

    foreach (['haslo', 'password', 'pin', 'sekret', 'token', 'kod', 'nazwa_wyswietlana'] as $nazwa) {
        $bateria[$nazwa] = Typy::napis($konto['nazwa_wyswietlana']);
    }

    $bateria['email'] = Typy::napis($konto['email']);
    $bateria['sub'] = Typy::napis($konto['keycloak_sub']);

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

        foreach ([[], $bateria] as $ladunek) {
            session()->flush();

            try {
                test()->call($metoda, $uri, $ladunek);
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
                    $ladunek === [] ? 'pusty' : implode(', ', array_keys($ladunek))
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
