<?php

declare(strict_types=1);

use App\Retencja\RejestrRetencji;
use App\Wsparcie\Typy;
use Illuminate\Support\Facades\Schema;

/**
 * ZWOLNIENIA Z RETENCJI — koszt zwolnienia równy kosztowi wpisu (`D6`).
 *
 * Do 09.08 `BEZ_DANYCH_OSOBOWYCH` było **płaską listą nazw**. Wpis do rejestru retencji
 * kosztował podstawę i opis dla człowieka, a **dopisanie tabeli do zwolnień nie kosztowało nic** —
 * czyli **zwolnienie było najtańszą drogą wyjścia z rejestru**. Kiedy droga wyjścia jest tańsza
 * od zgodności, staje się drogą domyślną. Nie przez złą wolę — przez pośpiech.
 */
it('KAŻDE zwolnienie niesie powód i warunek znoszący', function (): void {
    $ubogie = [];

    foreach (RejestrRetencji::bezDanychOsobowych() as $tabela => $wpis) {
        if (mb_strlen($wpis['powod'] ?? '') < 20 || mb_strlen($wpis['warunek_znoszacy'] ?? '') < 20) {
            $ubogie[] = $tabela;
        }
    }

    expect($ubogie)->toBe([], 'Zwolnienia bez powodu albo bez warunku znoszącego: '.implode(', ', $ubogie));
});

it('WARUNEK ZNOSZĄCY `sessions` JEST EGZEKWOWANY, nie opisany', function (): void {
    // To jest sedno tej pozycji. Tabela `sessions` ma `ip_address` i `user_agent`,
    // a ADRES IP JEST DANĄ OSOBOWĄ. Zwolnienie broni się WYŁĄCZNIE tym, że
    // sterownik sesji to `redis`, więc tabela stoi pusta.
    //
    // Dopóki to była linijka komentarza, przestawienie `session.driver` na
    // `database` zamieniało zwolnienie w nieprawdę — CICHO. Tabela zaczynałaby
    // zbierać adresy IP, a rejestr retencji nadal twierdziłby, że nie ma tam
    // danych osobowych. Warunek znoszący zapisany prozą niczego nie znosi.
    $zwolnione = RejestrRetencji::nazwyBezDanychOsobowych();

    if (! in_array('sessions', $zwolnione, true)) {
        // `sessions` wypadla ze zwolnien — warunek nie ma czego pilnowac. MILCZACE
        // przejscie byloby jednak zielenia BEZ PRZEDMIOTU, wiec zapisujemy stan,
        // ktory faktycznie zachodzi, asercja o wartosci, a nie o stalej `true`.
        expect($zwolnione)->not->toContain('sessions');

        return;
    }

    // ⚠ `config('session.driver')` W TEŚCIE ZWRACA `array`, bo `phpunit.xml` wymusza
    // to na sztywno. Pierwsza wersja tej kontroli czytała właśnie tę wartość — czyli
    // mierzyła ŚWIAT TESTOWY, a nie ten, którego dotyczy zwolnienie. Klasa 3: odczyt
    // zgodny z więcej niż jednym stanem świata. Zielone i czerwone znaczyłoby tam
    // dokładnie tyle samo.
    //
    // Mierzymy więc DOMYŚLKĘ z pliku konfiguracyjnego — bo to ona rozstrzyga, co się
    // stanie, gdy `SESSION_DRIVER` zniknie z `.env`. To był realny stan: domyślką było
    // `database`, czyli brak jednej linijki w `.env` włączał zapisywanie adresów IP
    // do tabeli zwolnionej z retencji.
    $konfiguracja = file_get_contents(config_path('session.php'));

    expect($konfiguracja)->not->toBeFalse('Nie udało się odczytać `config/session.php`.');

    $trafil = preg_match("/'driver'\s*=>\s*env\('SESSION_DRIVER',\s*'([a-z]+)'\)/", (string) $konfiguracja, $t);

    expect($trafil)->toBe(1, 'Nie znalazłem domyślki `SESSION_DRIVER` — kontrola oślepła.');

    // `?? null` zamiast golego `$t[1]`: statyka nie wie, ze `preg_match() === 1`
    // gwarantuje grupe pierwsza, a asercja o wartosci nieistniejacej bylaby
    // czerwienia o przyrzadzie, nie o przedmiocie.
    expect($t[1] ?? null)->not->toBe(
        'database',
        'Domyślką sterownika sesji jest `database`, a `sessions` figuruje jako tabela BEZ danych '.
        'osobowych. Tabela ma `ip_address` i `user_agent`. Brak `SESSION_DRIVER` w `.env` zaczyna '.
        'wtedy CICHO zbierać adresy IP tam, gdzie rejestr retencji twierdzi, że danych nie ma.'
    );

    // Gałąź dynamiczna: poza testami, gdyby sterownik jednak wskazywał tabelę,
    // zwolnienie przestaje być prawdziwe niezależnie od tego, co mówi domyślka.
    // Asercja WPROST o wartosci, nie `expect(false)->toBeTrue()` wewnatrz `if`:
    // tamta forma jest dla statyki „asercja niemozliwa”, a dla czytelnika ukrywa,
    // co wlasciwie jest sprawdzane.
    expect(Typy::napis(config('session.driver')))->not->toBe(
        'database',
        'Sterownik sesji to `database`, a `sessions` nadal jest zwolniona z retencji. '.
        'Albo wróć na `redis`, albo przenieś `sessions` do rejestru z okresem.'
    );
});

it('KONTROLA NEGATYWNA: zwolnienia opisują TABELE, KTÓRE ISTNIEJĄ', function (): void {
    // Bez tego lista gnije: zostaną w niej nazwy tabel dawno usuniętych, każda
    // z ładnym powodem i warunkiem znoszącym. Martwy wpis wygląda na decyzję.
    // Druga rola tej kontroli: gdyby ktoś zwolnił tabelę literówką w nazwie,
    // rejestr retencji przestałby ją widzieć, a nic by nie zapaliło.
    $nieistniejace = [];

    foreach (RejestrRetencji::nazwyBezDanychOsobowych() as $tabela) {
        if (! Schema::hasTable($tabela)) {
            $nieistniejace[] = $tabela;
        }
    }

    expect($nieistniejace)->toBe(
        [],
        'Zwolnione tabele, których NIE MA w bazie: '.implode(', ', $nieistniejace).
        '. Literówka w nazwie zwalnia tabelę, która dalej istnieje i dalej zbiera dane.'
    );
});

it('KOLUMNY ZWOLNIONYCH TABEL NIE NIOSĄ OCZYWISTYCH DANYCH OSOBOWYCH', function (): void {
    // Kontrola NEGATYWNA wobec samego uzasadnienia: powód napisany przez człowieka
    // jest twierdzeniem, a nie pomiarem. Ta kontrola patrzy na KOLUMNY.
    //
    // Cztery tabele przechodzą tylko dlatego, że ich ryzyko siedzi w `payload`
    // i w `autor` — i to jest ZAPISANE w ich warunkach znoszących, nie ukryte.
    // Reszta nie ma prawa mieć niczego z tej listy.
    $podejrzane = ['email', 'e_mail', 'telefon', 'phone', 'imie', 'nazwisko', 'pesel', 'adres'];

    // Te cztery mają w warunku znoszącym wprost napisane, czym ryzykują.
    $zeZnanymRyzykiem = ['sessions', 'jobs', 'failed_jobs', 'konfiguracja_regul'];

    $trafienia = [];

    foreach (RejestrRetencji::nazwyBezDanychOsobowych() as $tabela) {
        if (in_array($tabela, $zeZnanymRyzykiem, true) || ! Schema::hasTable($tabela)) {
            continue;
        }

        foreach (Schema::getColumnListing($tabela) as $surowa) {
            $kolumna = Typy::napis($surowa);

            foreach ($podejrzane as $igla) {
                if (str_contains(strtolower($kolumna), $igla)) {
                    $trafienia[] = "{$tabela}.{$kolumna}";
                }
            }
        }
    }

    expect($trafienia)->toBe(
        [],
        'Tabela zwolniona z retencji ma kolumnę wyglądającą na dane osobowe: '.implode(', ', $trafienia)
    );
});
