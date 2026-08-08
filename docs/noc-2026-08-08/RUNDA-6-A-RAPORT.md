# Runda 6, część A („stos") — raport niezależnego weryfikatora

## 1. Metryki przebiegu — surowo

| pozycja | wartość |
|---|---|
| SHA (`git rev-parse HEAD` w klonie) | `49131d8d0bbe73991ea4283b7bd631fc17b0b751` |
| klon | `d:/tmp/gabinet-r6a` (świeży `git clone` z `d:/KOD/Niepodzielni/gabinet`) |
| projekt compose / prefiks | `gabinet-r6a` |
| porty | HTTP `8107`, Postgres `55461`, Redis `56407` |
| **kroków bramki** | **22** |
| **nieudanych kroków** | **1** |
| **lista nieudanych kroków** | **`[19] testy (Pest)`** — i tylko ten |
| **testów wykonanych** | **181** (180 zielonych, 1 czerwony), pominiętych **0** |
| **asercji** | **640** |
| test czerwony | `Tests\Feature\OdebranieRoliTest > it NOGA 1 […]` — `Expected response status code [401] but received 200.` (`tests/Feature/OdebranieRoliTest.php:637`) |
| Pint | `PASS 74 files` |
| Larastan (level max) | `[OK] No errors` |
| gitleaks | `49 commits scanned`, `no leaks found` |
| stopka | `BRAMKA CZERWONA — 1 nieudanych kroków z 22` |

**Twierdzenie wykonawcy „bramka jest CZERWONA dokładnie z JEDNEGO powodu — test `NOGA 1` — i tylko z niego": POTWIERDZONE.** Jeden czerwony krok, jeden czerwony test, ten wskazany. Tego nie udało się obalić.

Ale patrz **R6A-2**: zmierzyłem, że ten jedyny czerwony **nie jest defektem systemu**.

---

## 2. Znaleziska

### R6A-1 — test „POZYTYWNY: żądanie PO wylogowaniu dostaje 401 — logout REALNIE zabija sesję" przechodzi, gdy back-channel logout NIE kasuje żadnej sesji

**Dowód.** Mutacja kodu produkcyjnego `backend/app/Tozsamosc/RejestrSesji.php`, `zakoncz()`:

```php
        foreach ($identyfikatory as $id) {
-            if ($uchwyt->destroy($id)) {
-                $skasowane++;
-            }
+            // MUTACJA R6A-M1: NIE kasujemy sesji, ale liczymy tak, jakbyśmy kasowali.
+            $skasowane++;
        }
```

`./vendor/bin/pest tests/Feature/OdebranieRoliTest.php`:

```
  ⨯ it zabija sesję NATYCHMIAST po back-channel logout — bez czekania n… 0.30s
  ⨯ it kończy sesję przy niedostępnym IdP, gdy klucze są w cache — BEZ…  0.34s
  ✓ it POZYTYWNY: żądanie PO wylogowaniu dostaje 401 — logout REALNIE z… 0.23s
  Tests:    3 failed, 10 passed (70 assertions)
```

**Waga:** wysoka (jako wada kontroli, nie systemu).
**Czy blokuje zamknięcie fazy:** tak. To jest test wskazany w `PLAN-FAZ.md` jako *dowód pozytywny* domknięcia BLK-22 („nie »stan magazynu się zmienił«, tylko »kolejne żądanie tej samej przeglądarki dostaje 401«"). Zmierzone: 401 pochodzi WYŁĄCZNIE ze znacznika unieważnienia w PostgreSQL, a nie z kasowania sesji. Gdyby ktoś skasował całą pętlę `destroy()`, ten test — jedyny nazwany „POZYTYWNY" — nie zaświeciłby.
**Świat alternatywny:** można argumentować, że „logout zabija sesję" jest spełnione przez znacznik i test mierzy właściwy SKUTEK. Nie wykluczyłem tej interpretacji — ale nazwa i komentarz testu mówią o kasowaniu sesji, a kasowanie da się usunąć bez czerwieni. Druga interpretacja: mierzą to testy `zabija sesję NATYCHMIAST` i `kończy sesję przy niedostępnym IdP` (oba czerwone pod mutacją), więc luki w pokryciu SUMARYCZNIE nie ma — jest luka w tym konkretnym teście i w jego nazwie.

---

### R6A-2 — jedyny czerwony bramki (`NOGA 1`) jest wadą PRZYRZĄDU, nie systemu; komentarz testu o „granicy procesu" jest nieprawdziwy wobec kodu

Test deklaruje: *„Produkcja obsługuje każde żądanie w osobnym procesie PHP […] Odtwarzamy więc granicę procesu jawnie"* i robi to przez `app()->forgetInstance('session')` + `forgetInstance('session.store')`.

**Dowód 1 — `forgetInstance` NIE sięga do middleware.** Instrumentacja (plik testowy tymczasowy, kod produkcyjny NIETKNIĘTY):

```
[R6A] dlugosc sesji w magazynie po destroy: {"3IUriJ2ta8TamJyNlexrSB3JXV7qj09HIFH6M2Qe":0}
[R6A] StartSession::manager id=5858 vs app("session") id=3801
[R6A] status BEZ forgetInstance(StartSession): 200 tresc={"zalogowany":true,"sub":"sub-abc-123",…,"role":["koordynator"],…}
[R6A] status PO  forgetInstance(StartSession): 401 tresc={"zalogowany":false}
[R6A] zadan do punktu tokenow LACZNIE: 3
```

`Illuminate\Session\Middleware\StartSession` jest w kontenerze singletonem z wstrzykniętym `SessionManager`. Zapomnienie `session` i `session.store` tworzy NOWY menedżer (id 3801), ale middleware trzyma STARY (id 5858) — a z nim wczytany w pamięci `Store` z tożsamością. Magazyn jest pusty (długość 0), a żądanie i tak dostaje 200.

**Dowód 2 — odczyt bazowy obalił mój własny pierwszy wniosek.** Sam `forgetInstance(StartSession::class)` daje 401 także wtedy, gdy tożsamości NIE usuwam:

```
[R6A-BAZA] status przy NIETKNIETEJ tozsamosci: 401 tresc={"zalogowany":false}
```

Czyli mój pierwszy dyskryminator był zgodny z dwoma światami. Zbudowałem przyrząd rozstrzygający: pełny reset singletonów **plus jawne niesienie ciasteczka sesji** (`withCookie(config('session.cookie'), $idSesji)`).

**Dowód 3 — pomiar rozstrzygający, z odczytem bazowym:**

```
[R6A-P] id sesji z rejestru: 55wUqZ3PNcXyRByWbhCvY1qyONlSZzHP75tIWcWQ
[R6A-P] dlugosc w magazynie: 3752
[R6A-P] BAZA (tozsamosc NIETKNIETA): 200 {"zalogowany":true,"sub":"sub-abc-123",…,"role":["koordynator"],…}
[R6A-P] po destroy dlugosc: 0
[R6A-P] PO USUNIECIU: 401 {"zalogowany":false}
[R6A-P] zadan do punktu tokenow: 1
```

Przyrząd działa w obie strony (baza = 200), a po usunięciu tożsamości system oddaje **401**, przy czym do punktu tokenów poszło **jedno** żądanie (wymiana kodu przy logowaniu) — ścieżka odświeżania w ogóle nie ruszyła.

**Wniosek:** system NIE wskrzesza tożsamości z refresh tokenu. Wymóg nogi 1 standardu B8 jest SPEŁNIONY. `NOGA 1` jest czerwony, bo jego symulacja granicy procesu jest niekompletna.

**Waga:** wysoka.
**Czy blokuje zamknięcie fazy:** tak — ale w odwrotną stronę niż zakładano: blokerem jest test, nie kod. Poprawny przyrząd to reset `StartSession::class` **razem** z jawnym ciasteczkiem sesji (albo test HTTP przez `curl` z ciasteczkiem, poza klientem Pest).
**Świat alternatywny:** nie wykluczyłem, że w produkcji istnieje jeszcze inna ścieżka wskrzeszenia, której mój test (fabryka tokenów, atrapa IdP) nie odtwarza — np. przy prawdziwym Keycloaku. Pomiar dotyczy suity i żywego stosu bez IdP.

---

### R6A-3 — „wąskie gardło" §2 NIE jest strukturalne: tożsamość da się wytworzyć z danych z żądania, przez PUBLICZNE API

Twierdzenie: *„Konstruktor `TozsamoscSesji` jest PRYWATNY, jedyna droga do instancji to `zMagazynu()` […] ścieżka »brak rekordu → utwórz« jest NIEWYWOŁYWALNA, a nie »zabroniona warunkiem«."*

`zMagazynu()` jest **publiczną statyczną fabryką przyjmującą DOWOLNĄ tablicę**. Jej nazwa („z magazynu") jest jedynym, co wiąże ją z magazynem — nic w typie ani w kodzie tego nie wymusza.

**Dowód (żywy stos, HTTP, bez żadnego logowania).** Trzy tymczasowe trasy w `routes/web.php`; **kod klas `Tozsamosc` NIETKNIĘTY**:

```
=== WEKTOR 1: fabryka zMagazynu z danymi z ZADANIA ===
{"utworzono":true,"klasa":"App\\Tozsamosc\\TozsamoscSesji"}
HTTP=200
{"zalogowany":true,"sub":"napastnik-1","login":null,"email":null,"role":["koordynator"],
 "markery":[],"wymaga_2fa":false,"bramki":{"panel.specjalisty":true,"panel.koordynacji":true,
 "panel.pacjenta":false,"rozliczenia.akceptuj":true,"dziennik.zapisz":true}}

=== WEKTOR 2: reflection (newInstanceWithoutConstructor + setAccessible) ===
{"utworzono":true,"sub":"napastnik-reflection"}
HTTP=200
{"zalogowany":true,"sub":"napastnik-reflection",…,"role":["koordynator"],
 "bramki":{…"panel.koordynacji":true,"rozliczenia.akceptuj":true,"dziennik.zapisz":true}}
```

Wektor 3 (`unserialize`) — również działa, sprawdzony osobno:

```
SERIALIZED: O:28:"App\Tozsamosc\TozsamoscSesji":1:{s:4:"dane";a:2:{s:3:"sub";s:16:"napastnik-serial";…}}
KLASA: App\Tozsamosc\TozsamoscSesji SUB: napastnik-serial
```

`zPodmienionymi()` jest zwykłym `array_merge` na wartości, więc tożsamość jest w pełni przenośna między sesjami i użytkownikami — nic jej nie wiąże z sesją, z której pochodziła.

**Waga:** wysoka.
**Czy blokuje zamknięcie fazy:** tak, jeśli D-2026-08-08-24 ma być uznane za zrealizowane. Gwarancja nadal jest „zabroniona warunkiem" (wczesny `return null` w `stanKonta()`), tylko warunek przeniósł się o poziom wyżej. Trzy linijki zwykłego kodu, przechodzące Larastan level max, przywracają wskrzeszanie.
**Świat alternatywny (istotny — mówię wprost):** to **nie jest** dziura eksploatowalna przez atakującego z zewnątrz w obecnym kodzie. Żadna z tych tras nie istnieje w repozytorium; musiałem je dopisać. Obalone zostaje twierdzenie o **strukturze** („nie da się"), a nie stan bieżący aplikacji. Poprawka strukturalna byłaby tania: `zMagazynu()` prywatne + jedyne wejście przez `SesjaKonta::odczytaj(Request)`.

**Druga strona (czy wąskie gardło nie jest ZA CIASNE):** sprawdzone. Mutacja `SesjaKonta::odczytaj()` → `return null` (odczyt nigdy nie oddaje tożsamości) zapala 8 testów, w tym `NIE traci dostępu, dopóki access token jest ważny`. Test pilnujący legalnego przypadku **pilnuje**.

---

### R6A-4 — V-1 wciąż otwarte i przechodzi PRZEZ nowe wąskie gardło; kontrola obiecana w D-2026-08-08-24 nie istnieje

**Dowód A — brak kontroli.** Własny pomiar całego repozytorium: **żaden test nie liczy pisarzy klucza sesji ani nie sprawdza, że decyzje autoryzacyjne czytają wyłącznie z ustalonej tożsamości.** `grep -rn "pisarz\|PISARZ"` w `backend/` daje wyłącznie komentarze (`SesjaKonta.php`, `TozsamoscSesji.php`, komentarz w `OdebranieRoliTest.php`). D-2026-08-08-24 pkt 2 obiecuje: *„(a) zbiór miejsc zapisujących TE KONKRETNE klucze sesji ma liczność 1; (b) decyzje autoryzacyjne […] czytają wyłącznie z tej ustalonej tożsamości"*. Taki test nie powstał.

**Dowód B — mechanizm własnych haseł nadal przechodzi.** Mutacja: logowanie hasłem na **ZADEKLAROWANEJ** trasie `GET /`, skrót w **ZADEKLAROWANEJ** kolumnie `users.nazwa_wyswietlana`, prymityw `hash('sha256', …)` spoza zamkniętej listy `PRYMITYWY_POSWIADCZEN`, a zapis tożsamości **przez `SesjaKonta::zaloz()`**, czyli przez samo wąskie gardło.

```
=== BrakWlasnychHaselTest po mutacji ===
  ✓ it ma schemat DOKŁADNIE taki, jak zadeklarowany — ani jednej kolumn… 0.76s
  ✓ it wystawia DOKŁADNIE zadeklarowane trasy                            0.13s
  ✓ it nie używa ANI JEDNEGO prymitywu tworzącego lub sprawdzającego ha… 0.56s
  ✓ it nie ma modelu zdolnego do uwierzytelniania hasłem                 0.11s
  Tests:    7 passed (22 assertions)

=== DOWOD: mechanizm hasla DZIALA przez HTTP ===
{"zalogowany":true}
{"zalogowany":true,"sub":"wlasne-haslo",…,"role":["koordynator"],
 "bramki":{"panel.specjalisty":true,"panel.koordynacji":true,"panel.pacjenta":false,
 "rozliczenia.akceptuj":true,"dziennik.zapisz":true}}
```

**Waga:** krytyczna (CLAUDE.md §2 — „ŻADNYCH własnych haseł").
**Czy blokuje zamknięcie fazy:** tak.
**Świat alternatywny:** wykonawca sam wymienia V-1 jako OTWARTE w `PLAN-FAZ.md`, więc to nie jest obalenie twierdzenia o zamknięciu. Obalam natomiast zdanie z `CURRENT WORK`: *„Przebudowa pisarza zostaje — jest poprawna niezależnie, bo realizuje wymóg §2"*. Zmierzone: przebudowa **nie realizuje** wymogu §2 w postaci zapisanej w D-24 — `zaloz()` jest publiczne i wołalne skądkolwiek, a kontroli liczącej pisarzy nie ma.

---

### R6A-5 — perturbacja `role_zamrozone` NIE MUTUJE NICZEGO i mimo to melduje sukces; dowód mutacji jest pusty; 5 sprawdzeń „czerwone" nie może dziś paść

**Dowód — cały łańcuch, uruchomiony:**

```
=== KROK 1: perturbacja role-zamrozone URUCHOMIONA WPROST ===
PERTURBACJA NIEUDANA: nie znaleziono wzorca w OdswiezanieSesji.php:
        if (! $this->wymagaOdswiezenia($konta)) {
            return $konta;
        }
KOD WYJSCIA=1
=== KROK 2: czy plik sie zmienil ===
(pusto = BEZ MUTACJI)
=== KROK 3: dowod mutacji (dokladnie ta komenda co w skrypcie) ===
DOWOD MUTACJI KOD=0 (0 = 'mutacja potwierdzona')
```

Składowe:

1. `skrypty/perturbuj.py::role_zamrozone` szuka `$this->wymagaOdswiezenia($konta)`; po przebudowie §2 kod ma `$this->wymagaOdswiezenia($tozsamosc)`. `podmien()` rzuca `SystemExit`.
2. `skrypty/perturbacje.sh` ma `set -uo pipefail` (**bez `-e`**, linia 60), a wywołanie `perturbuj role-zamrozone` (linia 802) nie jest sprawdzane — skrypt leci dalej.
3. `dowod_mutacji … bash -c "! grep -q 'wymagaOdswiezenia(\$konta)' …"` — literał `wymagaOdswiezenia($konta)` **nie występuje w pliku także PRZED perturbacją** (`grep -c` → `0`). Dowód mutacji jest tautologią.
4. `oczekuj_czerwone` bez `--przyczyna` przyjmuje dowolną czerwień spoza denylisty `AWARIE_POBOCZNE`. Komunikat `NOGA 1` (`Expected response status code [401] but received 200.`) do denylisty nie wpada. Scenariusz melduje ✓, nie zmieniwszy ani bajtu.

**To samo, węziej, dotyczy `p_logout_failsafe`:** `dowod_mutacji … ! grep -q 'sidNiezweryfikowany'` — ten literał również nie istnieje w pliku przed perturbacją (`grep -c` → `0`). Sama mutacja tam wchodzi (wzorce `try {` / `} catch (Throwable $blad) {` nadal pasują), ale dowód mutacji nie potrafi tego stwierdzić.

**Zasięg.** `oczekuj_czerwone … tests/Feature/OdebranieRoliTest.php` **bez** `--przyczyna` występuje w liniach **807, 827, 847, 893, 903** — pięć scenariuszy, które dopóki `NOGA 1` jest czerwony, **nie mogą paść**. Z `--przyczyną` są tylko dwa (867 „WYMUSZONE WYLOGOWANIE", 968 „POZYTYWNY").

**Waga:** krytyczna dla wiarygodności pomiaru.
**Czy blokuje zamknięcie fazy:** tak. `CURRENT WORK` opiera się na „30 scenariuszy ze strażnikiem przyczyny czerwieni"; co najmniej jeden z nich mierzy zero, a pięć nie ma dziś zdolności zaświecenia. To dokładnie ten tryb awarii, który `WYTYCZNE-PRACY.md` nazywa „najgorszym możliwym wynikiem".
**Świat alternatywny:** nie uruchomiłem pełnego `perturbacje.sh` (wymaga własnego stosu, a nie wolno mi ruszać `gabinet-perturbacje`), więc nie mam wydruku „✓ scenariusz role_zamrozone" z prawdziwego przebiegu. Zmierzyłem natomiast każde ogniwo łańcucha osobno i wszystkie zachowują się jak opisano. Jeśli gdzieś wyżej istnieje przechwycenie kodu wyjścia `perturbuj`, którego nie znalazłem — łańcuch by się urwał; szukałem i nie znalazłem (`perturbuj() { python3 …; }`, wywołanie bez `||`, brak `set -e`).

---

### R6A-6 — kontrola „szyfrowanie sesji włączone DOMYŚLNIE — czytane z TREŚCI pliku" przechodzi przy domyślnej `false`, jeśli literał wystąpi w komentarzu

**Dowód.** Mutacja `backend/config/session.php`:

```php
+    // MUTACJA R6A-M8: literal w KOMENTARZU: 'encrypt' => env('SESSION_ENCRYPT', true)
-    'encrypt' => env('SESSION_ENCRYPT', true),
+    'encrypt' => env('SESSION_ENCRYPT', false),
```

```
  ✓ it nie zapisuje e-maila ani ID tokenu JAWNIE w magazynie sesji       0.80s
  ✓ it ma szyfrowanie sesji włączone DOMYŚLNIE — czytane z TREŚCI pliku… 0.11s
  ✓ it nie ujawnia e-maila UKRYTEGO WYŁĄCZNIE wewnątrz ID tokenu         0.19s
  Tests:    5 passed (11 assertions)
```

Test robi `str_contains($tresc, "'encrypt' => env('SESSION_ENCRYPT', true)")` — dopasowanie tekstu **gdziekolwiek w pliku**, także w komentarzu. Druga asercja (`config('session.encrypt')` === true) przechodzi tylko dlatego, że plik środowiska przebiegu ma `SESSION_ENCRYPT=true` — czyli przez tę samą zależność od środowiska, którą naprawa V-3 miała usunąć.

**Waga:** średnia.
**Czy blokuje zamknięcie fazy:** nie (stan faktyczny domyślnej wartości jest poprawny), ale kontrola jest słabsza, niż deklaruje jej komentarz („ścieżka niezależna od środowiska").
**Świat alternatywny:** można uznać, że komentarz z takim literałem nikt nie napisze przypadkiem. Zgoda — to nie jest scenariusz przypadkowy, tylko obejście. Kontrola tekstowa nie odróżnia kodu od komentarza; wystarczyłoby porównać wartość z `require`-owanej tablicy przy jawnie wyczyszczonym `$_ENV`/`putenv`.

---

### R6A-7 — `ObietniceKomentarzyTest` obejmuje 6 znaczników i pomija 7, w tym wszystkie niosące dzisiejsze obietnice bezpieczeństwa

**Dowód A — kontrola jest falsyfikowalna w swoim zakresie:** wstrzyknięcie do `app/Wsparcie/Typy.php` komentarza z `W-777` → `1 failed`.

**Dowód B — poza zakresem nie widzi nic:** komentarz `„znaleziska BLK-99, B9, V-9, C9, D-2026-01-01-99 naprawione"` → `Tests: 2 passed`.

**Dowód C — pomiar zasięgu na kodzie produkcyjnym:**

```
=== znaczniki [UWO]-\d+ w app/ (objęte kontrolą) ===
U-10 U-7 U-9 W-2 W-3 W-5
=== znaczniki B[0-9], BLK-*, D-2026-*, V-[0-9], C[0-9] w app/ (POZA kontrolą) ===
B7 B8 BLK-22 C1 D-2026-08-07-05 D-2026-08-07-08 D-2026-08-08-24
objete: 6
poza:   7
```

Poza siecią są `B7`, `B8`, `BLK-22`, `D-2026-08-08-24` — czyli dokładnie te, na które powołuje się cała warstwa `Tozsamosc`.

**Waga:** średnia.
**Czy blokuje zamknięcie fazy:** nie, ale unieważnia zdanie z `WYTYCZNE-PRACY.md`: *„Egzekwowane maszynowo […] `ObietniceKomentarzyTest` wymaga, by każde znalezisko powołane w kodzie produkcyjnym […] było NAZWANE w co najmniej jednym teście"* — „każde" jest nieprawdziwe; regex to `/\b([UWO]-\d+)\b/`.
**Świat alternatywny:** kontrola i tak sprawdza tylko WSPÓŁWYSTĘPOWANIE znacznika, nie treść obietnicy — więc nawet w swoim zakresie komentarz w pliku testowym ją zaspokaja. Tego nie liczę jako osobne znalezisko, bo sam plik to przyznaje („nie sprawdzimy maszynowo, czy zdanie po polsku jest prawdziwe").

---

### R6A-8 — komentarz w `config/database.php` sugeruje ochronę przed EKSMISJĄ, której rozdzielenie baz nie daje; `D-2026-08-08-28` mówi wprost coś przeciwnego, a plik jej nie cytuje

**Pomiar żywego Redisa (mój stos):**

```
maxmemory
0
maxmemory-policy
noeviction
```

**Rozdzielenie przestrzeni kluczy — zmierzone, DZIAŁA:**

```
dbsize przed zapisem:      db0=5  db1=1  db2=104
po zapisie sesji + cache:  db0=5  db1=2  db2=105     (sesja → db2, cache → db1)

php artisan cache:clear →  INFO  Application cache cleared successfully.
po cache:clear:            db0=5  db1=0  db2=105     (sesje NIETKNIĘTE)

Cache::flush() PO dotknięciu sterownika sesji w tym samym procesie:
po flush:                  db0=5  db1=0  db2=106     (nowa sesja przeżyła)
```

Sprawdziłem też hipotezę, że `SessionManager` mutuje WSPÓLNY obiekt `RedisStore` (co odwróciłoby skutek `Cache::flush()`):

```
store sesji:  id=6636 polaczenie=sesje
store cache:  id=677  polaczenie=cache
TEN SAM OBIEKT: NIE
```

Hipoteza **obalona** — obiekty są rozdzielne.

**Znalezisko dotyczy dokumentacji.** `backend/config/database.php` pod nagłówkiem *„SESJE MAJĄ WŁASNĄ BAZĘ REDISA […] Skutki, których nikt nie wybrał:"* wymienia dwa punkty, drugi to *„przy polityce eksmisji `allkeys-*` żywe sesje byłyby eksmitowane pod presją pamięci"*. Umieszczenie tego w liście skutków, które rozdzielenie usuwa, jest nieprawdziwe. `D-2026-08-08-28` mówi to wprost: *„To nieprawda: `maxmemory` i polityka eksmisji są własnością INSTANCJI Redisa, nie bazy"*. Plik konfiguracyjny **nie został poprawiony ani nie cytuje D-28** (`grep -c "D-2026-08-08-28" backend/config/database.php` → `0`).

**Waga:** średnia (dokumentacja kłamiąca o kodzie — klasa, dla której powstał `ObietniceKomentarzyTest`, i której ta kontrola nie łapie, bo `D-…` jest poza jej regexem — patrz R6A-7).
**Czy blokuje zamknięcie fazy:** nie.
**Świat alternatywny:** komentarz da się przeczytać jako „oto co grozi w tej okolicy", a nie „oto co ta zmiana usuwa". Nie wykluczyłem tej lektury — ale zdanie wprowadzające brzmi „bez tego […] Skutki, których nikt nie wybrał", co wiąże oba punkty z brakiem rozdzielenia.

---

### R6A-9 — `PLAN-FAZ.md` ma DWIE sekcje `CURRENT WORK` o sprzecznym stanie

`grep -n "^## CURRENT WORK"` → linie **5** i **113**.

| | sekcja z linii 5 | sekcja z linii 113 |
|---|---|---|
| bramka | „**CZERWONA — 1 nieudany krok z 22**" | „`BRAMKA OK — 21 kroków, 0 nieudanych`" |
| testy | „180 zielonych, 1 czerwony, **640 asercji**" | „**151 testów** (479 asercji)" |
| perturbacje | „**30 scenariuszy**" | „44 kontrole […] w **20 scenariuszach**" |

Zmierzony stan rzeczywisty (moja bramka): 22 kroki / 1 nieudany / 181 testów / 640 asercji. Druga sekcja jest nieaktualna i sprzeczna z pierwszą.

**Waga:** średnia.
**Czy blokuje zamknięcie fazy:** nie formalnie, ale `CLAUDE.md` wskazuje *„sekcja `CURRENT WORK` w `PLAN-FAZ.md`"* (liczba pojedyncza) jako stan między sesjami, a `WYTYCZNE-PRACY.md` nazywa plik stanu „przyrządem — i to najgroźniejszym". Sesja czytająca sekcję z linii 113 startuje z fałszywego „BRAMKA OK".
**Świat alternatywny:** druga sekcja może być zamierzoną archiwalną migawką F1. Nie jest tak oznaczona; nagłówek brzmi „CURRENT WORK — F1", a treść mówi w czasie teraźniejszym („Stan bramki **dziś**").

---

### R6A-10 — `bramka.sh` ignoruje `--projekt` przy nazwie pliku środowiska; dwa przebiegi o różnych projektach dzielą JEDEN plik, a zamek ich nie rozdziela

**Dowód.** Uruchomiłem `./skrypty/bramka.sh --projekt gabinet-r6a`. Po przebiegu:

```
-rw-r--r-- 1 Jakub 197609    0 Aug  8 23:39 .env
-rw-r--r-- 1 Jakub 197609 5291 Aug  8 23:39 .env.bramka.gabinet-bramka
-rw-r--r-- 1 Jakub 197609 5204 Aug  8 23:39 .env.example
```

Plik nazywa się `.env.bramka.gabinet-bramka`, nie `.env.bramka.gabinet-r6a`. Przyczyna: `PLIK_ENV="$KORZEN/.env.bramka.$PROJEKT"` stoi w linii **73**, a pętla parsująca `--projekt` zaczyna się w linii **98**. `ZAMEK` liczony jest po parsowaniu, więc jest per-projekt — czyli zamek **nie chroni** przed dwoma przebiegami o różnych `--projekt`, a te dzielą ten sam plik z wygenerowanym `APP_KEY` i `DB_PASSWORD`. `perturbacje.sh` woła `bramka.sh --projekt "$projekt" --tylko-kod` (linie 1015, 1023), więc dotyczy to również przebiegów perturbacji.

Dodatkowo: krok `[22] sprzątanie` **nie kasuje** tego pliku — zostaje na dysku z wygenerowanym kluczem aplikacji i hasłem bazy (objęty `.gitignore`, więc do repo nie trafi).

**Waga:** średnia (przyrząd; ryzyko fałszywego pomiaru, nie wyciek do repo).
**Czy blokuje zamknięcie fazy:** nie.
**Świat alternatywny:** w praktyce nikt nie uruchamia dwóch bramek naraz. Nie wykluczyłem tego — ale `perturbacje-powtarzalne.sh` i CI to dokładnie scenariusze, w których to się zdarza.

---

### R6A-11 — retencja nie jest wykonywana w produkcji: `ZadanieRetencji` nie ma ani jednego wywołującego, rejestr retencji mieszka w pliku testowym

**Dowód:**

```
=== czy ZadanieRetencji jest gdziekolwiek wolane w produkcji ===
backend/app/Retencja/ZadanieRetencji.php:28:final class ZadanieRetencji
```

(jedyne trafienie to definicja klasy — brak wywołań w `app/`, `routes/`, `bootstrap/`)

`backend/routes/console.php` ma dokładnie jedno zadanie: `Schedule::command('gabinet:puls')`. `app/Console/Commands/` zawiera `Puls.php`, `SprawdzKonta.php`, `Zdrowie.php` — żadnego polecenia retencji. `REJESTR_RETENCJI` jest stałą w `backend/tests/Feature/RetencjaTest.php`, nie artefaktem produkcyjnym.

Kontrole SĄ falsyfikowalne — sprawdziłem: usunięcie `DB::table($tabela)->whereIn(...)->delete()` zapala `RetencjaWykonanieTest` (1 failed), a dodanie niezarejestrowanej tabeli z danymi osobowymi zapala `RetencjaTest` (1 failed). Mierzą jednak bibliotekę i rejestr, nie działający mechanizm.

**Waga:** średnia.
**Czy blokuje zamknięcie fazy:** zależy od definicji zakresu F1 — `CLAUDE.md` §10 mówi „retencje jako zadania czyszczące **w kodzie**", a gałąź nazywa się `faza-1-retencja`. Zgłaszam jako lukę do rozstrzygnięcia, nie jako złamanie zapisanego kryterium.
**Świat alternatywny:** możliwe, że podpięcie do harmonogramu jest świadomie odłożone (progi retencji czekają na IOD — pytanie P-3 w DPIA). Komentarz klasy to sugeruje. Nie znalazłem jednak nigdzie zapisu „mechanizm celowo niepodpięty".

---

### R6A-12 — „jeden pisarz klucza `konta`": mój pomiar daje inny obraz niż `CURRENT WORK`

`CURRENT WORK`: *„Zmierzone: jeden pisarz klucza `konta` w całym `backend/app`."*

Mój pomiar (`grep -rnE "Session::|session\(\)|->session\(\)" backend/app`):

| miejsce | operacja | w wąskim gardle? |
|---|---|---|
| `SesjaKonta.php:42` | `put('konta', …)` — `zaloz()` | tak |
| `SesjaKonta.php:59` | `put('konta', …)` — `zaktualizuj()` | tak |
| `SesjaKonta.php:65-66` | `flush()` + `regenerate()` — `zakoncz()` | tak |
| **`LogowanieController.php:174`** | **`session()->get('konta', [])` — literałem, z pominięciem `SesjaKonta::odczytaj()` i stałej `KLUCZ`** | **nie** |
| **`LogowanieController.php:186-187`** | **`flush()` + `invalidate()` — kasowanie tożsamości z pominięciem `SesjaKonta::zakoncz()`** | **nie** |

Dla zapisu (`put`) twierdzenie broni się, jeśli „pisarz" znaczy „klasa". Poza gardłem jest jedna ścieżka **kasowania** tożsamości i jeden **odczyt literałem** — a odczyt literałem to dokładnie ta droga, którą stała `KLUCZ` („jedno miejsce, żeby dało się policzyć piszących") miała zamknąć.

**Waga:** niska.
**Czy blokuje zamknięcie fazy:** nie.
**Świat alternatywny:** `flush()` w `wyloguj()` jest semantycznie równoważne `zakoncz()`. Zgoda — to kwestia policzalności, nie zachowania.

---

## 3. Twierdzenia wykonawcy, których NIE UDAŁO SIĘ OBALIĆ

1. **„Bramka czerwona dokładnie z jednego powodu — `NOGA 1` — i tylko z niego."** Potwierdzone dosłownie: 1 nieudany krok z 22, 1 czerwony test z 181.
2. **D-2026-08-08-28 / rozdzielenie przestrzeni kluczy Redisa.** Zmierzone na żywym Redisie: sesje w db2, cache w db1, `cache:clear` czyści db1 i **nie rusza** db2, `Cache::flush()` (także po wcześniejszym dotknięciu sterownika sesji) nie rusza db2. Moja hipoteza o współdzielonym obiekcie `RedisStore` została **obalona pomiarem** (id 6636 vs 677, różne połączenia).
3. **D-2026-08-08-28 o eksmisji jest merytorycznie POPRAWNE i uczciwe** — sam wpis prostuje wcześniejszy błędny wniosek wykonawcy i podaje zmierzone `maxmemory=0`, `maxmemory-policy=noeviction`. Moje R6A-8 dotyczy wyłącznie nieaktualnego komentarza w `config/database.php`.
4. **Falsyfikowalność większości kontroli w `OdebranieRoliTest`.** Każda z poniższych mutacji kodu produkcyjnego zapaliła właściwy test:
   - znacznik unieważnienia z powrotem do cache → ⨯ `znacznik unieważnienia PRZEŻYWA wyczyszczenie cache`
   - `uniewazniona()` opakowane w `try/catch → false` → ⨯ `brak rozstrzygnięcia = ODMOWA (fail-closed)` (`Expecting 200 not to be 200 'FAIL-OPEN: …'`)
   - przywrócona furtka kasowania po niezweryfikowanym `sid` → ⨯ `ADWERSARIALNY…` (`WYMUSZONE WYLOGOWANIE: napastnik wyrzucił ofiarę z systemu`)
   - role brane ze starej sesji zamiast z nowego tokenu → ⨯ `odbiera dostęp, gdy Keycloak odbierze rolę`
   - role z ID tokenu zamiast z access tokenu → ⨯ `czyta role Z ACCESS TOKENU…`
   - ID token zapisany jawnie → ⨯ `zapisuje ID token do sesji ZASZYFROWANY…`
   - `odczytaj()` → `return null` (gardło za ciasne) → ⨯ `NIE traci dostępu, dopóki access token jest ważny` (+7 innych)
5. **Falsyfikowalność kontroli retencji** (usunięcie `delete()` → ⨯; niezarejestrowana tabela z danymi osobowymi → ⨯).
6. **`SesjaBezJawnychDanychTest` jako dowód**: plik SAM deklaruje (D-2026-08-08-25), że 4 z 5 zapisów to fixture testu i że asercja o kodzie produkcyjnym mieszka w `OdebranieRoliTest`. Zmierzyłem to (przy jawnym ID tokenie cały plik zostaje zielony) — i **nie liczę tego jako znaleziska**, bo dokumentacja pliku jest zgodna z pomiarem.

---

## 4. Zadanie G — czy „wada przyrządu" nie bywa wadą systemu w przebraniu

**Odpowiedź: w zbadanym materiale nie znalazłem przypadku, w którym wykonawca zasłonił defekt systemu etykietą „przyrząd". Znalazłem coś odwrotnego i coś gorszego.**

1. **Odwrotnie — `NOGA 1`.** Wykonawca miał tu pełną wygodę: mógł zamknąć jedyny czerwony bramki zdaniem „to artefakt klienta testowego". Nie zrobił tego — zostawił `NIEROZSTRZYGNIĘTE` i jawnie sprostował własny wcześniejszy wniosek. Zmierzyłem: **to naprawdę był przyrząd** (R6A-2). Atrybucja, której nie postawił, była prawdziwa. To argument przeciw tezie o wygodnej atrybucji w tej rundzie.
2. **`D-2026-08-08-27` (podmiana sterowników w suicie).** Klasyczny kandydat na „to przyrząd". Wykonawca opisał `CACHE_STORE=array` jako wadę przyrządu, ale **nie zatrzymał się na tym** — pod spodem był realny defekt systemu (sesje w tej samej przestrzeni kluczy co cache) i został naprawiony strukturalnie (D-28, `config/database.php`). Zmierzyłem naprawę i działa. Atrybucja nie posłużyła do zamknięcia sprawy.
3. **Gorsze niż wygodna atrybucja — R6A-5.** Ryzyko nie leży w etykietowaniu znalezisk, tylko w tym, że **narzędzie do wykrywania fałszywych zielonych samo produkuje fałszywe zielone**. Scenariusz `role_zamrozone` melduje sukces bez mutacji, jego „dowód mutacji" jest tautologią, a pięć sprawdzeń `oczekuj_czerwone` nie ma dziś zdolności paść, bo suita jest czerwona z innego powodu. `CURRENT WORK` niesie z tego liczbę „30 scenariuszy ze strażnikiem przyczyny czerwieni" jako miarę pokrycia. To jest ten sam mechanizm co „wygodna atrybucja", tylko zautomatyzowany: nie ma tu człowieka, który przypisuje winę — jest skrypt, który zawsze mówi „zdaliśmy".
4. **Klasa pokrewna — R6A-6 i R6A-7.** Dwie kontrole opisane w dokumentach jako naprawione i „niezależne od środowiska" / „egzekwowane maszynowo" są zauważalnie węższe niż ich opis. To nie jest atrybucja znaleziska, ale ta sama rodzina: **opis przyrządu jest mocniejszy niż przyrząd**.

**Konkret do zlecenia następnej rundy:** przed jakąkolwiek dyskusją o atrybucji trzeba przywrócić zdolność perturbacji do świecenia — `--przyczyna` przy każdym `oczekuj_czerwone` celującym w plik, w którym już jest czerwień, oraz kontrola, że `dowod_mutacji` porównuje stan PRZED i PO (dziś sprawdza tylko PO, więc każdy nieaktualny literał czyni go pustym).

---

## 5. Czego NIE ZDĄŻYŁEM sprawdzić — lista jawna

Perturbacji przez mutację kodu produkcyjnego **nie wykonałem** dla:

- `backend/tests/Feature/BramkiTest.php` — **zero mutacji**
- `backend/tests/Feature/ModelDanychTest.php` — **zero mutacji** (uwaga: D-2026-08-08-25 sam oznacza go jako kształt (c), 9 zapisów / 15 asercji, status „OTWARTE — W-9")
- `backend/tests/Feature/RejestrRegulTest.php` — **zero mutacji**
- `backend/tests/Feature/WzmacniaczZadanTest.php` — **zero mutacji**
- `backend/tests/Feature/SekretyTest.php` — **zero mutacji**
- `backend/tests/Feature/SzkieletTest.php` — **zero mutacji**
- `backend/tests/Feature/LogowanieTest.php` — **zero mutacji**

Ponadto nie wykonałem:

- **Pełnego przebiegu `skrypty/perturbacje.sh`** (30 scenariuszy). Wymagałby własnego stosu, a scenariusze operują na projekcie, którego nie wolno mi ruszać. R6A-5 opiera się na uruchomieniu POJEDYNCZYCH ogniw łańcucha, nie na wydruku z pełnego przebiegu.
- **Pomiaru na żywym Keycloaku** — mój stos nie ma IdP; wszystkie ścieżki OIDC mierzone przez `Http::fake` i `FabrykaTokenow`.
- **Sprawdzenia pozostałych scenariuszy `perturbuj.py`** pod kątem nieaktualnych wzorców. Zmierzyłem 2 z 30 (`role_zamrozone` — martwy, `logout_bez_failsafe` — mutuje, ale z pustym dowodem). **Pozostałe 28 nie zostały sprawdzone i mogą mieć tę samą wadę.** To najpilniejsza luka w moim pokryciu.
- **Testów współbieżności** (CLAUDE.md §6, 100 równoczesnych żądań) — poza zakresem tej części.
- **Weryfikacji, czy `.env` (0 bajtów) tworzony w klonie przez bramkę ma jakiekolwiek znaczenie** — zauważony, nieprzebadany.

---

## 6. Sprzątanie

Wykonane:

- Kod klonu przywracany `git checkout -- .` **po każdej mutacji**, z kontrolą `git status --porcelain` (za każdym razem pusto → wypisywane `CZYSTE`). Ostatni stan drzewa klonu przed sprzątaniem: czysty.
- Pliki tymczasowe utworzone przeze mnie i skasowane: `backend/tests/Feature/R6ADyskryminatorTest.php`, `backend/tests/Feature/R6AKontrolaTest.php`, `backend/r6a-pomiar.php`, `backend/r6a-diag2.php`, `backend/r6a-flush-po-sesji.php`, `backend/database/migrations/2026_08_09_000000_mutacja_r6a_m10.php`, trasy eksperymentalne w `backend/routes/web.php`.
- Kontenery + wolumeny + sieć: `GABINET_PREFIX=gabinet-r6a docker compose -p gabinet-r6a down -v` (prefiks podany — patrz nagłówek `bramka.sh`). Krok `[22]` bramki wykonał to samo po swoim przebiegu; drugi raz po moich pomiarach.
- Klon `d:/tmp/gabinet-r6a` usunięty.

**Nie ruszałem** żadnego cudzego stosu. Przez cały przebieg na maszynie stały: `helpdesk-*`, `helpdesk-weryf-*`, `nk-noc-v1-*`, `gabinet-*` (deweloper), `gabinet-perturbacje-*` — wszystkie nietknięte.

**Nie wykonałem** żadnego `docker image prune`, `docker rmi`, `docker system prune`, `docker volume prune`.

**Ślad zamierzony:** obraz `gabinet-r6a-app:local` **zostaje** (zgodnie z instrukcją — obrazy są globalne dla demona).

**Ślad niezamierzony, zgłaszam:** plik `.env.bramka.gabinet-bramka` (5291 B, z wygenerowanym `APP_KEY` i `DB_PASSWORD`) był tworzony w katalogu klonu — zniknął razem z klonem. W drzewie dewelopera go nie tworzyłem i nie czytałem tam żadnego `.env` (patrz R6A-10 co do jego nazwy).

**W drzewie roboczym wykonawcy (`d:\KOD\Niepodzielni\gabinet`) nie zmieniłem ani jednego bajtu.** Nie commitowałem, nie pushowałem, nie tworzyłem gałęzi.

**Uwaga o stanie drzewa dewelopera (nie moja zmiana).** W trakcie mojego przebiegu drzewo wykonawcy poszło do przodu: `git rev-parse HEAD` = `cbfc983987ba921f9ace624c24623eba57077939`, `git status --porcelain` = ` M docs/noc-2026-08-08/ODLOZONE.md`. Na starcie sesji było `49131d8` i czysto. To praca wykonawcy równolegle do weryfikacji — odnotowuję, żeby nikt nie przypisał tego mnie, i żeby było jasne, że **cały raport dotyczy przypiętego `49131d8`, a nie stanu bieżącego gałęzi**.
