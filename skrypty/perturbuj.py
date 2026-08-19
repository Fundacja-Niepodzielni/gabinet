#!/usr/bin/env python3
"""Mutacje plików dla `skrypty/perturbacje.sh`.

Po co osobny plik zamiast `sed` w skrypcie: perturbacje podkładają całe
fragmenty kodu PHP — z ukośnikami wstecznymi w przestrzeniach nazw, znakami
dolara i cudzysłowami. W zagnieżdżonych heredokach basha każdy z tych znaków
wymaga innego poziomu ucieczki i pomyłka jest cicha: `sed` nic nie znajduje,
perturbacja „przechodzi", a my dowiadujemy się, że kontrola nic nie sprawdza.
Zdarzyło się to dwa razy, zanim ten plik powstał.

Użycie:
    python3 skrypty/perturbuj.py hasla-podloz
    python3 skrypty/perturbuj.py nonce-fail-open
"""

from __future__ import annotations

import io
import sys
from pathlib import Path

KORZEN = Path(__file__).resolve().parent.parent

MIGRACJA = KORZEN / "backend/database/migrations/0001_01_01_000000_create_users_table.php"
TRASY = KORZEN / "backend/routes/web.php"
KONTROLER_LOGOWANIA = KORZEN / "backend/app/Http/Controllers/LogowanieController.php"
MODEL = KORZEN / "backend/app/Models/Personel.php"
WALIDATOR = KORZEN / "backend/app/Tozsamosc/WalidatorTokenu.php"
OIDC = KORZEN / "backend/app/Tozsamosc/KontaOidc.php"
MIDDLEWARE_UNIEWAZNIENIE = KORZEN / "backend/app/Http/Middleware/SprawdzUniewaznienie.php"
PEST = KORZEN / "backend/tests/Pest.php"
OCENA = KORZEN / "backend/app/Reguly/OcenaAnulacji.php"
SESJA = KORZEN / "backend/config/session.php"
ODSWIEZANIE = KORZEN / "backend/app/Tozsamosc/OdswiezanieSesji.php"
REJESTR = KORZEN / "backend/app/Tozsamosc/RejestrSesji.php"
WYLOGOWANIE = KORZEN / "backend/app/Http/Controllers/BackchannelLogoutController.php"
KONTROLER = KORZEN / "backend/app/Http/Controllers/LogowanieController.php"
WEJSCIE = KORZEN / "backend/app/Wejscie/Poswiadczenia.php"
ZADANIE = KORZEN / "backend/app/Retencja/ZadanieRetencji.php"
TYPY = KORZEN / "backend/app/Wsparcie/Typy.php"
BRAMKI = KORZEN / "backend/app/Tozsamosc/Bramki.php"
GABINET = KORZEN / "backend/config/gabinet.php"
PRZYKLAD_ENV = KORZEN / ".env.example"


def czytaj(sciezka: Path) -> str:
    return io.open(sciezka, encoding="utf-8").read()


def pisz(sciezka: Path, tresc: str) -> None:
    io.open(sciezka, "w", encoding="utf-8", newline="\n").write(tresc)


def podmien(sciezka: Path, stare: str, nowe: str) -> None:
    """Podmiana, która KRZYCZY, gdy wzorca nie ma.

    Cicha podmiana bez trafienia to najgorszy możliwy wynik: perturbacja
    zgłasza sukces, nie zmieniwszy niczego.
    """
    tresc = czytaj(sciezka)

    if stare not in tresc:
        raise SystemExit(f"PERTURBACJA NIEUDANA: nie znaleziono wzorca w {sciezka.name}:\n{stare[:120]}")

    pisz(sciezka, tresc.replace(stare, nowe, 1))


def podmien_jedyne(sciezka: Path, stare: str, nowe: str) -> None:
    """Podmiana, ktora krzyczy w OBIE strony: przy zerze i przy zwielokrotnieniu.

    `podmien()` broni tylko przed brakiem trafienia. Wzorzec trafiajacy WIECEJ
    niz raz jest osobna wada: zmieniamy wtedy cos innego, niz zamierzono, i tez
    po cichu (to jest „kierunek 0" z naprawy R6B-7 w `bramka.sh`).

    Uzywaja tego mutacje przeniesione tutaj z surowego `sed -i` w
    `perturbacje.sh` (R6B-17 / N-9): `sed`, ktory nie trafil, konczy sie
    SUKCESEM, wiec przez nieznany czas `p_statyka` mutowala widmo — jej wzorzec
    szukal `string $domyslny = .."..): string`, a sygnatura brzmi
    `string $domyslny = ''): string`.
    """
    tresc = czytaj(sciezka)
    ile = tresc.count(stare)

    if ile == 0:
        raise SystemExit(f"PERTURBACJA NIEUDANA: nie znaleziono wzorca w {sciezka.name}:\n{stare[:120]}")

    if ile > 1:
        raise SystemExit(
            f"PERTURBACJA NIEUDANA: wzorzec wystepuje {ile} razy w {sciezka.name} — "
            f"podmiana zmienilaby WIECEJ, niz zamierzono:\n{stare[:120]}"
        )

    pisz(sciezka, tresc.replace(stare, nowe, 1))


def hasla_podloz() -> None:
    """Odtworzenie ataku niezależnego weryfikatora: pełny mechanizm haseł.

    Pierwsza wersja `BrakWlasnychHaselTest` sprawdzała literalne nazwy
    (`password`, `remember_token`, `password_reset_tokens`, model `User`)
    i przepuściła KOMPLET poniżej — cała bramka była wtedy zielona.
    """
    # 1. kolumny hasła i „zapamiętaj mnie" pod polskimi nazwami
    podmien(
        MIGRACJA,
        "$table->string('email')->nullable();",
        "$table->string('email')->nullable();\n"
        "            $table->string('haslo_hash');\n"
        "            $table->string('token_zapamietania')->nullable();",
    )

    # 2. osobna tabela kont lokalnych i tabela resetu
    podmien(
        MIGRACJA,
        "        Schema::create('sessions', function (Blueprint $table): void {",
        "        Schema::create('konta_lokalne', function (Blueprint $table): void {\n"
        "            $table->id();\n"
        "            $table->string('hash_hasla');\n"
        "        });\n\n"
        "        Schema::create('zetony_resetu', function (Blueprint $table): void {\n"
        "            $table->string('email')->primary();\n"
        "            $table->string('zeton');\n"
        "        });\n\n"
        "        Schema::create('sessions', function (Blueprint $table): void {",
    )

    # 3. drugi model, zdolny do uwierzytelniania hasłem
    pisz(
        MODEL,
        "<?php\n\ndeclare(strict_types=1);\n\n"
        "namespace App" + chr(92) + "Models;\n\n"
        "use Illuminate" + chr(92) + "Foundation" + chr(92) + "Auth" + chr(92) + "User as Authenticatable;\n\n"
        "class Personel extends Authenticatable\n{\n"
        "    protected $table = 'konta_lokalne';\n}\n",
    )

    # 4. trasy w polskiej odmianie — `haslo` ich nie łapie, `hasl` tak
    pisz(
        TRASY,
        czytaj(TRASY)
        + "\nRoute::get('/reset-hasla', fn () => 'perturbacja');\n"
        + "Route::post('/zaloguj-haslem', fn () => 'perturbacja');\n",
    )


def hasla_sprzataj() -> None:
    MODEL.unlink(missing_ok=True)
    WEJSCIE.unlink(missing_ok=True)

    if WEJSCIE.parent.exists() and not any(WEJSCIE.parent.iterdir()):
        WEJSCIE.parent.rmdir()


def hasla_podloz_v2() -> None:
    """Atak z RUNDY 3 — nazwy, ktorych zaden wzorzec nie przewidzi.

    Weryfikator obalil wersje druga testu, uzywajac `sekret_logowania`,
    `poswiadczenia_wejsciowe`, `pin_dostepu`, `sodium_crypto_pwhash_str()`
    i tras `/wejscie` — plus schowal `Hash::make` w `routes/web.php`,
    ktorego skan w ogole nie obejmowal. Mechanizm DZIALAL.
    """
    podmien(
        MIGRACJA,
        "$table->string('email')->nullable();",
        "$table->string('email')->nullable();\n"
        "            $table->string('sekret_logowania')->nullable();\n"
        "            $table->string('odcisk_urzadzenia')->nullable();",
    )

    podmien(
        MIGRACJA,
        "        Schema::create('sessions', function (Blueprint $table): void {",
        "        Schema::create('poswiadczenia_wejsciowe', function (Blueprint $table): void {\n"
        "            $table->id();\n"
        "            $table->string('odcisk_uwierzytelnienia');\n"
        "            $table->string('pin_dostepu');\n"
        "        });\n\n"
        "        Schema::create('zetony_odnowienia_dostepu', function (Blueprint $table): void {\n"
        "            $table->string('zeton')->primary();\n"
        "        });\n\n"
        "        Schema::create('sessions', function (Blueprint $table): void {",
    )

    # Prymityw POZA `app/` — w `routes/`, ktorego stary skan nie widzial.
    pisz(
        TRASY,
        czytaj(TRASY)
        + "\nRoute::get('/wejscie/zaloz', fn () => \\Illuminate\\Support\\Facades\\Hash::make('tajne123'));\n",
    )

    # Prymityw spoza starej listy czterech funkcji.
    WEJSCIE.parent.mkdir(parents=True, exist_ok=True)
    pisz(
        WEJSCIE,
        "<?php\n\ndeclare(strict_types=1);\n\n"
        "namespace App\\Wejscie;\n\n"
        "final class Poswiadczenia\n{\n"
        "    public static function utworz(string $sekret): string\n"
        "    {\n        return sodium_crypto_pwhash_str(\n"
        "            $sekret,\n"
        "            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,\n"
        "            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE\n        );\n    }\n\n"
        "    public static function sprawdz(string $skrot, string $sekret): bool\n"
        "    {\n        return sodium_crypto_pwhash_str_verify($skrot, $sekret);\n    }\n}\n",
    )


def nonce_fail_open() -> None:
    """Przywraca zachowanie sprzed naprawy: kontrola MILCZY zamiast paść."""
    podmien(
        WALIDATOR,
        "if (array_key_exists('nonce', $opcje)) {",
        "if (array_key_exists('nonce', $opcje) && $opcje['nonce'] !== null) {",
    )


def _d1b_podloz(pole: str) -> None:
    """Atak D-1 weryfikatora rundy 7: logowanie BEZ krypto, kolumny i trasy.

    NAZWA POLA JEST PARAMETREM — i to jest naprawa R8-1, nie kosmetyka.

    Do rundy 8 mutacja czytala na sztywno `nazwa_wyswietlana`, czyli nazwe
    Z BATERII siatki. Meldunek ODPOWIEDZ-062 §8 powolywal sie na te wlasnie
    perturbacje jako na SIEC BEZPIECZENSTWA znoszaca ograniczenie baterii —
    a ona nie wywolywala ZADNEJ nazwy spoza baterii, wiec nie mogla dowiesc
    pokrycia, ktore reklamowala. Twierdzenie bylo nieprawdziwe wobec wlasnej
    implementacji.

    Odtad ta sama mutacja chodzi w DWOCH wariantach: nazwa z baterii
    (kontrola pozytywna sondy) i nazwa, ktorej zadna lista nie przewiduje
    (`zaklecie` — dokladnie ta z raportu rundy 8).

    Obie siatki DEKLARATYWNE `BrakWlasnychHaselTest` pytaja o SPOSOB:
    o funkcje kryptograficzna i o zadeklarowany schemat/trasy. Ten mechanizm
    nie uzywa zadnej funkcji krypto (gole `===`), nie dodaje kolumny
    (`nazwa_wyswietlana` jest zadeklarowana) ani trasy (`GET /` istnieje).

    Zmierzone przez weryfikatora: `BrakWlasnychHaselTest` -> 9 passed,
    Larastan level max -> No errors. Bramka zielona nad dzialajacym
    mechanizmem wlasnych hasel, czyli nad zlamana zasada 2 z CLAUDE.md.

    Siatka POMIAROWA (`SiatkaPomiarowaTozsamosciTest`) pyta o SKUTEK
    i dlatego ma to zobaczyc.
    """
    podmien_jedyne(
        TRASY,
        "Route::get('/', fn () => response()->json([",
        "Route::get('/', function () {" + chr(10)
        + "    $podane = (string) request()->input('" + pole + "', ''); " + chr(10)
        + "    $konto = DB::table('users')->where('nazwa_wyswietlana', $podane)->first(); " + chr(10)
        + "    if ($podane !== '' && $konto !== null && $podane === $konto->nazwa_wyswietlana) { " + chr(10)
        + "        session()->put('konta', ['sub' => 'sub-lokalne', 'role' => ['pacjent']]); " + chr(10)
        + "    } " + chr(10)
        + "    return response()->json([" + chr(10),
    )

    # Domkniecie domkniecia: oryginal konczy sie `]));`, nowa forma
    # potrzebuje `]); });`. Bez tego plik nie parsuje sie i perturbacja
    # zapalilaby suite bledem skladni — czyli czerwienia z INNEJ przyczyny
    # niz badana (P25).
    podmien_jedyne(
        TRASY,
        "    'informacja' => 'Gabinet — API. Interfejs użytkownika dochodzi w F7.'," + chr(10) + "]));",
        "    'informacja' => 'Gabinet — API. Interfejs użytkownika dochodzi w F7.'," + chr(10) + "    ]); });",
    )

    # `DB` musi byc zaimportowane — inaczej „Class not found" jest awaria
    # poboczna z listy AWARIE_POBOCZNE, a nie zapaleniem siatki.
    podmien_jedyne(
        TRASY,
        "use Illuminate\\Support\\Facades\\Route;",
        "use Illuminate\\Support\\Facades\\DB;" + chr(10) + "use Illuminate\\Support\\Facades\\Route;",
    )


def d1b_podloz() -> None:
    """Wariant z nazwa Z BATERII — kontrola POZYTYWNA sondy siatki."""
    _d1b_podloz('nazwa_wyswietlana')


def d1b_podloz_zaklecie() -> None:
    """Wariant z nazwa SPOZA baterii — to on obalil siatke w rundzie 8.

    Mechanizm jest IDENTYCZNY co do bajtu poza jedna nazwa. Jesli siatka
    zapala sie na jednym wariancie, a na drugim nie, to nie mierzy skutku,
    tylko zgaduje nazwe wejscia.
    """
    _d1b_podloz('zaklecie')


def _gardlo_podloz(odczyt: str, etykieta: str) -> None:
    """Mechanizm wlasnych hasel W PLIKU Z ALLOWLISTY — trzy wektory rundy 9.

    Weryfikator rundy 9 postawil go dokladnie tutaj i to nie byl przypadek:
    `LogowanieController` ma zgode na dotykanie tozsamosci, wiec allowlista
    PLIKOW jest wobec niego bezradna z konstrukcji. Zmierzone wtedy:
    `WaskieGardlo` + `BrakWlasnychHasel` = 14 passed, pelna suita 290 passed.

    Trzy warianty roznia sie WYLACZNIE sposobem dostarczenia sekretu:

      para      — dwa pola o roznych wartosciach (kanoniczny formularz)
      naglowek  — sekret w naglowku HTTP
      all       — `$request->all()[...]`, czyli odczyt, ktorego parser
                  nazw pol nie widzial

    Kazdy z nich konczy sie TYM SAMYM zapisem tozsamosci — i o to chodzi:
    waskie gardlo mierzy MIEJSCE ZAPISU, wiec ma zapalic na wszystkich trzech
    jednakowo, nie zgadujac sposobu.
    """
    podmien_jedyne(
        KONTROLER_LOGOWANIA,
        "    public function zaloguj(Request $request): RedirectResponse" + chr(10)
        + "    {" + chr(10),
        "    public function zaloguj(Request $request): RedirectResponse" + chr(10)
        + "    {" + chr(10)
        + "        // PERTURBACJA " + etykieta + chr(10)
        + odczyt
        + "        if (is_string($sekretPerturbacji) && $sekretPerturbacji !== '') {" + chr(10)
        + "            $kontoPerturbacji = DB::table('users')->where('nazwa_wyswietlana', $sekretPerturbacji)->first();" + chr(10)
        + "            if ($kontoPerturbacji !== null) {" + chr(10)
        + "                $request->session()->put('konta', ['sub' => 'sub-lokalne', 'role' => ['pacjent']]);" + chr(10)
        + "            }" + chr(10)
        + "        }" + chr(10),
    )

    podmien_jedyne(
        KONTROLER_LOGOWANIA,
        "use Illuminate\\Support\\Facades\\Crypt;",
        "use Illuminate\\Support\\Facades\\Crypt;" + chr(10) + "use Illuminate\\Support\\Facades\\DB;",
    )


def gardlo_para() -> None:
    """WEKTOR A rundy 9: dwa pola o roznych wartosciach — formularz logowania."""
    _gardlo_podloz(
        "        $loginPerturbacji = $request->input('email');" + chr(10)
        + "        $sekretPerturbacji = $request->input('haslo');" + chr(10),
        'para pol email+haslo',
    )


def gardlo_naglowek() -> None:
    """WEKTOR H rundy 9: sekret w NAGLOWKU HTTP."""
    _gardlo_podloz(
        "        $sekretPerturbacji = $request->header('X-Zaklecie');" + chr(10),
        'sekret w naglowku',
    )


def gardlo_all() -> None:
    """WEKTOR ALL rundy 9: odczyt przez `all()`, niewidoczny dla parsera nazw."""
    _gardlo_podloz(
        "        $wszystkoPerturbacji = $request->all();" + chr(10)
        + "        $sekretPerturbacji = $wszystkoPerturbacji['zaklecie'] ?? null;" + chr(10),
        'odczyt przez all()',
    )


def lockfile_rozjazd() -> None:
    """Podbija wersję w composer.lock, nie ruszając zainstalowanego vendora.

    Odtwarza O-4: wolumen `vendor` nie odświeża się z przebudowanego obrazu,
    więc podbicie lockfile'a (np. łatka bezpieczeństwa) bywało ignorowane
    BEZ ŻADNEGO SYGNAŁU.
    """
    lock = KORZEN / "backend/composer.lock"
    tresc = czytaj(lock)

    # `content-hash` wiąże lock z composer.json; jego zmiana jest dokładnie
    # tym, co `composer validate` i `install --dry-run` mają wykryć.
    znacznik = '"content-hash": "'
    poczatek = tresc.index(znacznik) + len(znacznik)
    koniec = tresc.index('"', poczatek)
    stary = tresc[poczatek:koniec]
    nowy = ("0" + stary[1:]) if stary[0] != "0" else ("1" + stary[1:])

    pisz(lock, tresc[:poczatek] + nowy + tresc[koniec:])


def wzmacniacz_zadan() -> None:
    """Usuwa bramke czestotliwosci odswiezania JWKS.

    Odtwarza naiwna obsluge rotacji kluczy: „nie znam kid → dociagnij JWKS".
    Wyglada niewinnie i jest czesta — a zamienia usluge we wzmacniacz zadan
    do IdP, bo `kid` kontroluje nadawca zadania, jeszcze przed weryfikacja
    podpisu (lekcja zespolu hubu z perturbacji JWKS).
    """
    podmien(
        OIDC,
        "        if (! Cache::add(self::KLUCZ_ODSWIEZANIE, 1, $this->odstepOdswiezaniaSekundy())) {" + chr(10)
        + "            return $jwks;" + chr(10)
        + "        }" + chr(10) + chr(10),
        "",
    )

def suita_pominieta() -> None:
    """Pomija CALA suite jednym `beforeEach` — atak W-4 z rundy 4.

    Pest konczy sie kodem 0, wiersz podsumowania mowi „151 skipped
    (0 assertions)", a podloga sumujaca wszystkie stany widziala 151
    wykonanych testow. Zero wykonanych testow, bramka zielona.
    """
    pisz(PEST, czytaj(PEST) + ZNACZNIK_POMINIECIA + "\n")


# `pest()->beforeEach(...)`, nie samo `beforeEach(...)` — sprawdzone pomiarem:
# forma bez `pest()` NIE pomija testów (suita szła dalej, 158 passed), więc
# perturbacja mierzyłaby nie to zjawisko. Ta forma daje realne
# „158 skipped (0 assertions)", czyli dokładnie stan z W-4.
ZNACZNIK_POMINIECIA = (
    "\npest()->beforeEach(function (): void { $this->markTestSkipped('perturbacja'); })"
    "->in('Feature', 'Unit');\n"
)


def suita_pominieta_sprzataj() -> None:
    """Sprzatanie musi ODTWORZYC plik co do bajtu.

    Pierwsza wersja zostawiala puste linie na koncu — `git status` pokazywal
    zmiane, a kontrola powtarzalnosci meldowala wyciek z perturbacji, ktorego
    nie bylo. Narzedzie obserwacji nie moze zmieniac obserwowanego stanu.
    """
    tresc = czytaj(PEST)

    if ZNACZNIK_POMINIECIA not in tresc:
        return

    pisz(PEST, tresc.replace(ZNACZNIK_POMINIECIA, "").rstrip(chr(10)) + chr(10))


OBIETNICA = "\n// Naprawa W-777: OBIETNICA bez zadnego testu, ktory ja nazywa.\n"


def obietnica_bez_dowodu() -> None:
    """Dopisuje do kodu produkcyjnego komentarz obiecujacy naprawe bez testu.

    Odtwarza klase bledu z rundy 4: trzy komentarze obiecywaly wiecej, niz kod
    robil („zmiana cofana", „PHP_INT_MAX obsluzony", „zatrzymujemy harmonogram").
    Komentarz myli nastepnego czytelnika ciszej niz kod, bo nikt go nie uruchamia.
    """
    pisz(OCENA, czytaj(OCENA) + OBIETNICA)


def obietnica_sprzataj() -> None:
    tresc = czytaj(OCENA)

    if OBIETNICA in tresc:
        pisz(OCENA, tresc.replace(OBIETNICA, "").rstrip(chr(10)) + chr(10))


def sesja_jawna() -> None:
    """Wylacza szyfrowanie sesji — kontrola krzyzowa B7 z weryfikacji huba.

    Sesja Gabinetu trzyma e-mail, login i CALY ID token; sterownik redis
    utrwala dane na dysku. Bez szyfrowania e-mail pacjenta lezy jawnie.
    """
    podmien(SESJA, "'encrypt' => env('SESSION_ENCRYPT', true),", "'encrypt' => env('SESSION_ENCRYPT', false),")


def role_zamrozone() -> None:
    """Przywraca stan sprzed decyzji B8: role zamrozone na cala sesje.

    Wystarczy, ze `stanKonta()` odda zawartosc sesji bez pytania o wiek
    access tokenu — i odebranie roli w Keycloaku przestaje dzialac przez
    120 minut. Zmiana wyglada niewinnie i jest dokladnie tym, co znalazla
    kontrola krzyzowa B8 u huba.
    """
    podmien(
        ODSWIEZANIE,
        "        if (! $this->wymagaOdswiezenia($tozsamosc)) {\n"
        "            return $tozsamosc->dane;\n"
        "        }",
        "        return $tozsamosc->dane;",
    )


def logout_bez_failsafe() -> None:
    """Usuwa obsluge wyjatku z handlera back-channel logout.

    Bez `catch` niedostepny IdP daje 500, a sesja zyje dalej — w ciszy. To ten
    sam tryb awarii, ktory back-channel logout ma eliminowac (znalezisko sesji
    `konta`, standard B8).
    """
    tresc = czytaj(WYLOGOWANIE)
    nl = chr(10)
    otwarcie = "        try {" + nl
    poczatek_catch = "        } catch (Throwable $blad) {"
    koniec_catch = "        }" + nl + nl + "        $claims = $wynik['claims'];"

    if otwarcie not in tresc or poczatek_catch not in tresc or koniec_catch not in tresc:
        raise SystemExit("PERTURBACJA NIEUDANA: nie rozpoznano konstrukcji try/catch w handlerze")

    przed = tresc[: tresc.index(otwarcie)]
    srodek = tresc[tresc.index(otwarcie) + len(otwarcie) : tresc.index(poczatek_catch)]
    po = tresc[tresc.index(koniec_catch) + len(koniec_catch) :]

    pisz(WYLOGOWANIE, przed + srodek + "        $claims = $wynik['claims'];" + po)


def role_ze_zlego_zrodla() -> None:
    """Czyta role z ID TOKENU zamiast z access tokenu.

    Wyostrzenie B2 (wzorzec huba): asercja „role == [koordynator]" przechodzi
    takze przy odczycie ze zlego zrodla, o ile fixtura kaze obu zrodlom mowic
    to samo. Ta mutacja sprawdza, czy test pyta „Z KTOREGO ZRODLA", a nie
    „czy role sa".
    """
    # WSZYSTKIE wystapienia, nie pierwsze. Pierwsza wersja tej mutacji
    # podmieniala tylko `role_surowe`, a autoryzujace `role` szly dalej
    # z access tokenu — perturbacja meldowala, ze test nie rozroznia zrodel,
    # podczas gdy nie rozroznial ich sama mutacja.
    tresc = czytaj(KONTROLER)
    stare = "Bramki::roleZAccessTokenu($wynikAccess['claims'])"

    if tresc.count(stare) < 3:
        raise SystemExit(
            "PERTURBACJA NIEUDANA: oczekiwano co najmniej 3 odczytow rol "
            f"z access tokenu, znaleziono {tresc.count(stare)}"
        )

    pisz(KONTROLER, tresc.replace(stare, "Bramki::roleZAccessTokenu($claimsId)"))


def logout_na_niezweryfikowanym_sid() -> None:
    """Przywraca furtke: konczenie sesji po sid z NIEZWERYFIKOWANEGO tokenu.

    Tak wygladala pierwsza wersja klauzuli fail-safe. Rozumowanie („skutkiem
    jest tylko wylogowanie") bylo za slabe: to furtka na WYMUSZONE
    WYLOGOWANIE, bo napastnik czesciowo kontroluje wyzwalacz wyjatku przez
    `kid` we wlasnym tokenie.
    """
    podmien(
        WYLOGOWANIE,
        "            SladWylogowania::odmowa();",
        "            RejestrSesji::zakoncz(WalidatorTokenu::sidNiezweryfikowany($logoutToken));\n"
        "            SladWylogowania::odmowa();",
    )


def id_token_jawny() -> None:
    """Zapisuje ID token do sesji BEZ szyfrowania — refinement B7.

    W polaczeniu z `sesja-jawna` odtwarza pelna luke: claim `email`
    z wnetrza ID tokenu lezy w Redisie, zakodowany base64url. Skaner
    szukajacy tekstu jawnego go MIJA.
    """
    podmien(KONTROLER, "Crypt::encryptString($idToken)", "$idToken")


def id_token_zakodowany() -> None:
    """Koduje ID token zamiast go szyfrowac — najtrudniejszy przypadek B7.

    Kodowanie WYGLADA jak zabezpieczenie: zapisana wartosc rozni sie od
    oryginalu, wiec asercja „nie rowna sie" przechodzi. Ale claim `email`
    jest w pelni odzyskiwalny. Ta mutacja rozstrzyga, czy kontrola pyta
    o ODZYSKIWALNOSC DANYCH, czy tylko o roznice napisow.
    """
    podmien(KONTROLER, "Crypt::encryptString($idToken)", "base64_encode($idToken)")


def retencja_bez_kasowania() -> None:
    """Zadanie retencyjne WYBIERA rekordy, ale ich NIE KASUJE.

    Lekcja przekrojowa helpdesku: kontrole retencji sprawdzaja zwykle,
    kogo zadanie wybiera — a nie czy rekord realnie znika. RODO wymaga
    WYKONANIA, nie selekcji. Ta mutacja zostawia selekcje nietknieta
    i usuwa samo kasowanie: kontrola pytajaca tylko o liste PRZEJDZIE.
    """
    podmien(
        ZADANIE,
        "        DB::table($tabela)->whereIn($kolumnaKlucza, $doUsuniecia)->delete();",
        "        // perturbacja: kasowanie usuniete, selekcja zostaje",
    )


def uniewaznienie_po_sid() -> None:
    """Usuwa sprawdzanie uniewaznienia po `sid` — odtwarza BLK-22.

    Zapamietany identyfikator sesji ROTUJE (zmierzone: Set-Cookie z dwoch
    kolejnych odpowiedzi daje rozne wartosci przy zachowanej tozsamosci),
    wiec wylogowanie kasuje wpis, ktorego nikt juz nie uzywa. Bez tego
    sprawdzenia konsument serwuje dalej po zamknieciu sesji w IdP.
    """
    # OBIE WARSTWY, nie jedna — zmierzone 12.08.
    #
    # Od 09.08 uniewaznienie sprawdzaja DWA miejsca: middleware w grupach
    # `web` i `api` oraz sciezka odswiezania. Mutacja zdejmujaca tylko jedno
    # zostawiala druga warstwe czynna, wiec test przechodzil, a scenariusz
    # meldowal „kontrola PRZESZLA mimo zlamanej reguly" i obwinial kontrole
    # za obrone, ktora dziala. To jest ta sama wada, co pomiar pojedynczego
    # ogniwa w lancuchu majacym dwa.
    for plik in (ODSWIEZANIE, MIDDLEWARE_UNIEWAZNIENIE):
        podmien_jedyne(
            plik,
            "        if (RejestrSesji::uniewazniona($tozsamosc->sid())) {",
            "        if (false) {",
        )


# ---------------------------------------------------------------------------
# MUTACJE PRZENIESIONE Z SUROWEGO `sed -i` (R6B-17 / N-9).
#
# Do 12.08 osiem podmian robil `sed -i` wprost w `perturbacje.sh`. `sed`, ktory
# nie trafil, konczy sie KODEM 0 i nie zmienia nic — czyli „podmiana weszla"
# i „podmiana nie trafila" dawaly identyczny sygnal. Jedna z nich (`p_statyka`)
# byla udowodnionym cichym no-opem: szukala `string $domyslny = .."..): string`,
# a `Typy.php` ma `string $domyslny = ''): string`. Scenariusz swiecil zielono
# wylacznie dzieki DRUGIEJ mutacji, dopisanej obok.
#
# Tutaj kazda z nich idzie przez `podmien_jedyne()`, ktore podnosi blad przy
# zerze i przy zwielokrotnieniu, a `perturbuj()` w skrypcie sprawdza kod
# wyjscia i ustawia `MUTACJA_ZERWANA`.
# ---------------------------------------------------------------------------

def granica_24h() -> None:
    """Przesuwa granice okna bezplatnego odwolania o JEDNA SEKUNDE.

    Najmniejsza mozliwa zmiana zachowania: `>=` na `>`. Jesli suita jej nie
    lapie, testy granicy 23:59 / 24:00 / 24:01 sa dekoracja.
    """
    podmien_jedyne(
        OCENA,
        "$sekundDoWizyty >= $sekundOkna",
        "$sekundDoWizyty > $sekundOkna",
    )


def zamrozenie_biezace() -> None:
    """Czyta okno z wartosci BIEZACEJ zamiast z reguly ZAMROZONEJ (CLAUDE.md §4)."""
    podmien_jedyne(
        OCENA,
        "$sekundOkna = $reguly->oknoBezplatnegoOdwolaniaGodzin * 3600;",
        "$sekundOkna = 48 * 3600;",
    )


PERTURBACJA_TYPU = '\n// perturbacja\nfunction perturbacja_typu(): int { return "napis"; }\n'


def typ_zerwany() -> None:
    """Wstrzykuje DWA bledy typu: zla wartosc domyslna i zly typ zwracany.

    Pierwsza czesc to wlasnie ta, ktora przez nieznany czas byla widmem
    (patrz naglowek sekcji). Druga — dopisana funkcja — dzialala zawsze
    i maskowala brak pierwszej.
    """
    podmien_jedyne(
        TYPY,
        "public static function napis(mixed $wartosc, string $domyslny = ''): string",
        "public static function napis(mixed $wartosc, string $domyslny = 0): string",
    )
    pisz(TYPY, czytaj(TYPY) + PERTURBACJA_TYPU)


def przyneta(ziarno: str) -> str:
    """Wartosc udajaca sekret, SKLADANA W CZASIE DZIALANIA.

    Bramka zlapala to sama: doslowny, wysokoentropijny napis udajacy sekret,
    zapisany w SLEDZONYM pliku, jest dla skanera sekretem — i sluszznie, bo
    taki napis w repozytorium jest drobnym wyciekiem niezaleznie od tego, ze
    wartosc zmyslono. Wczesniej ta podmiana szla `sed`-em i skladala wartosc
    inaczej; przeniesienie jej tutaj (R6B-17) wstawilo literal.

    Skutek mutacji jest IDENTYCZNY — do pliku wzorcowego trafia ta sama
    wartosc — ale w repozytorium nie ma juz czego wziac za sekret. Wyjatek
    w `.gitleaks.toml` byl by tu ROZLUZNIENIEM KONTROLI zamiast naprawy.
    """
    import base64

    return base64.b64encode(ziarno.encode()).decode().rstrip("=")


def sekret_keycloak() -> None:
    """Wpisuje WARTOSC do pliku wzorcowego — sekret w repozytorium."""
    podmien_jedyne(
        PRZYKLAD_ENV,
        "\nKEYCLOAK_CLIENT_SECRET=\n",
        "\nKEYCLOAK_CLIENT_SECRET=" + przyneta("hello-world-this-is-a-secret") + "\n",
    )


def sekret_smsapi() -> None:
    """Drugi kierunek: `SekretyTest` pyta o SEMANTYKE („ta zmienna ma byc PUSTA")."""
    podmien_jedyne(PRZYKLAD_ENV, "\nSMSAPI_TOKEN=\n", "\nSMSAPI_TOKEN=cokolwiek\n")


def znacznik_obcy() -> None:
    """Podszywa usluge pod cudza — HTTP 200 to NIE tozsamosc."""
    podmien_jedyne(GABINET, "'znacznik' => 'gabinet-api-v1',", "'znacznik' => 'cudza-usluga',")


def biala_lista_zdjeta() -> None:
    """Zdejmuje filtr bialej listy rol.

    Dokladnie ten blad wpuszcza marker `wymaga-2fa` i role wbudowane Keycloaka
    do uprawnien. Wzorzec celuje w `$biala`, a nie w `$znane` — druga linia
    w tym samym pliku robi co innego i nie wolno jej ruszyc.
    """
    podmien_jedyne(
        BRAMKI,
        "return array_values(array_intersect($roleZTokenu, $biala));",
        "return $roleZTokenu;",
    )


POLECENIA = {
    "gardlo-para": gardlo_para,
    "gardlo-naglowek": gardlo_naglowek,
    "gardlo-all": gardlo_all,
    "d1b-podloz": d1b_podloz,
    "d1b-podloz-zaklecie": d1b_podloz_zaklecie,
    "hasla-podloz": hasla_podloz,
    "hasla-podloz-v2": hasla_podloz_v2,
    "hasla-sprzataj": hasla_sprzataj,
    "nonce-fail-open": nonce_fail_open,
    "lockfile-rozjazd": lockfile_rozjazd,
    "wzmacniacz-zadan": wzmacniacz_zadan,
    "suita-pominieta": suita_pominieta,
    "obietnica-bez-dowodu": obietnica_bez_dowodu,
    "sesja-jawna": sesja_jawna,
    "id-token-jawny": id_token_jawny,
    "retencja-bez-kasowania": retencja_bez_kasowania,
    "id-token-zakodowany": id_token_zakodowany,
    "role-zamrozone": role_zamrozone,
    "uniewaznienie-po-sid": uniewaznienie_po_sid,
    "logout-bez-failsafe": logout_bez_failsafe,
    "role-ze-zlego-zrodla": role_ze_zlego_zrodla,
    "logout-niezweryfikowany-sid": logout_na_niezweryfikowanym_sid,
    "obietnica-sprzataj": obietnica_sprzataj,
    "suita-pominieta-sprzataj": suita_pominieta_sprzataj,
    "granica-24h": granica_24h,
    "zamrozenie-biezace": zamrozenie_biezace,
    "typ-zerwany": typ_zerwany,
    "sekret-keycloak": sekret_keycloak,
    "sekret-smsapi": sekret_smsapi,
    "znacznik-obcy": znacznik_obcy,
    "biala-lista-zdjeta": biala_lista_zdjeta,
}


if __name__ == "__main__":
    if len(sys.argv) != 2 or sys.argv[1] not in POLECENIA:
        raise SystemExit(f"użycie: perturbuj.py [{' | '.join(POLECENIA)}]")

    POLECENIA[sys.argv[1]]()
