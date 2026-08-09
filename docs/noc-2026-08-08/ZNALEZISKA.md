# Znaleziska nocy z 8 na 9 sierpnia 2026

Jedno znalezisko = jeden wpis. Każdy ma: co, DOWÓD (komenda + wynik), wagę,
czy blokuje. Bez dowodu wpis nie powstaje.

**Reguła nocy: NAPRAWIAM PRZYRZĄD, NIGDY PRZEDMIOT.** Znaleziska weryfikatorów
są tu ZAPISYWANE, nie naprawiane — naprawa autorem tej samej nocy nie miałaby
rundy. Wyjątek: zepsuty sam przyrząd (bramka, perturbacja, skrypt weryfikatora),
bo bez niego kolejne rundy są bezwartościowe; taka naprawa jest w dzienniku
oznaczona jako NAPRAWA PRZYRZĄDU.

Numeracja: `N-1`, `N-2`, … dla znalezisk własnych; `R6A-*` / `R6B-*` zachowują
numerację nadaną przez weryfikatorów.

---

## N-1 — trzy komentarze w KODZIE PRODUKCYJNYM nadal niosły wniosek OBALONY

**Skutek po ludzku:** w kodzie stały zdania mówiące „zmierzyliśmy, że odświeżanie
tokenu wskrzeszało tożsamość" — a ten wniosek został wieczorem obalony pomiarem
kontrolnym. Następny czytelnik (człowiek albo agent) odziedziczyłby nieprawdziwą
diagnozę ubraną w słowo „zmierzone", i ścigałby przyczynę, której nie ma.

**Dowód:**

```
$ grep -rn "wskrzesz|świat 2|odtwarzał|potwierdzona pomiarem|POTWIERDZONY" (bez vendor)
backend/app/Tozsamosc/TozsamoscSesji.php:12:  Powód istnienia (noga 1 …, potwierdzona pomiarem)
backend/app/Tozsamosc/SesjaKonta.php:28:      odtwarzało ją z refresh tokenu (noga 1, świat 2)
backend/app/Tozsamosc/OdswiezanieSesji.php:64: bo odświeżanie odtwarzało ją z refresh tokenu
```

Dla kontrastu — w TYCH SAMYCH godzinach sprostowanie zostało wykonane w trzech
innych miejscach: `PLAN-FAZ.md:14`, `WYTYCZNE-PRACY.md:402,426` oraz w komentarzu
testu `OdebranieRoliTest.php:592`.

**Czyli:** sprostowanie objęło 3 z 6 miejsc. Etykieta cofnęła się tam, gdzie
autor patrzył (test, dokumenty stanu), a nie cofnęła się w kodzie produkcyjnym.
To jest dokładnie ta klasa, o której wykonawca napisał tego samego wieczora
lekcję („etykieta nazywa STAN WIEDZY i cofa się, gdy wiedza się cofa") — lekcja
została zapisana, ale nie zastosowana do końca.

**Waga:** średnia. Nie zmienia zachowania systemu ani jednego testu.
**Czy blokuje:** nie blokuje zamknięcia fazy, ale ZATRUWA następną sesję —
a to najdroższy rodzaj taniego błędu.

**Świat alternatywny:** żaden. Zdania są jednoznaczne i sprzeczne z późniejszym
pomiarem kontrolnym opisanym w `WYTYCZNE-PRACY.md:395–404`.

**Zrobione:** NAPRAWA PRZYRZĄDU (dokumentacja o kodzie jest przyrządem).
Trzy komentarze przepisane tak, żeby rozdzielały to, co ZMIERZONE (dwóch
pisarzy klucza `konta` — realne złamanie §2), od tego, co NIE (wskrzeszenie).
Uzasadnienie przebudowy stoi teraz na wymogu §2, który jest niezależny od
diagnozy nogi 1. Kod nietknięty — wyłącznie komentarze.

---

## N-2 — podłoga liczby testów przepuszczała skasowanie 10 z 17 plików kontrolnych

**Skutek po ludzku:** bramka miała pilnować, żeby nikt nie usunął testów po cichu.
Pilnowała słabo: można było skasować CAŁY plik kontrolny — łącznie z tym, który
pilnuje pozostałych kontroli — a bramka nadal świeciłaby zielono.

**Dowód:**

```
$ docker exec gabinet-app ./vendor/bin/pest
  Tests:    1 failed, 180 passed (640 assertions)      → 181 wykonanych

$ skrypty/bramka.sh (przed naprawą)
  MINIMUM_TESTOW=170        → zapas 11 testów
  MINIMUM_ASERCJI=590       → zapas 50 asercji
```

Rozkład wykonanych testów na pliki (zmierzony z pełnego wyjścia pesta):

```
 36 OcenaAnulacjiTest      8 SzkieletTest            4 RetencjaTest
 20 WalidatorTokenuTest    8 RejestrRegulTest        3 SekretyTest
 17 BramkiTest             7 BrakWlasnychHaselTest   3 RetencjaWykonanieTest
 16 LogowanieTest          5 SesjaBezJawnychDanychTest   2 ObietniceKomentarzyTest
 13 OdebranieRoliTest     11 GranicePienidzyTest
 13 ModelDanychTest       11 BlokadaWysylkiTest
```

W zapasie 11 testów mieści się skasowanie w CAŁOŚCI każdego z dziesięciu plików
o liczbie ≤ 11 — w tym `ObietniceKomentarzyTest` (2 testy), czyli **kontroli nad
kontrolami**, oraz `SesjaBezJawnychDanychTest` (5, RODO art. 9). Mieszczą się też
kombinacje, np. 2 + 3 + 3 = 8.

**To jest V-10 z rundy 5, zamknięte tylko pozornie.** Komentarz nad tą stałą
obiecywał „podłoga ma siedzieć TUŻ POD stanem bieżącym" i był NIEPRAWDZIWY wobec
własnej wartości — czyli obietnica bez pokrycia, ta sama klasa, przed którą
ostrzega `ObietniceKomentarzyTest`.

**Waga:** wysoka. Kontrola, którą da się obejść bez śladu w `git diff`, nie jest
kontrolą — a ta zostawiała ślad tak mały, że nikt by go nie zobaczył.
**Czy blokuje:** nie blokuje zamknięcia fazy, ale osłabia KAŻDY przyszły dowód
„bramka zielona".

**Świat alternatywny:** można twierdzić, że drugi sygnał (asercje) złapałby
skasowanie pliku. Sprawdzone: zapas asercji wynosił 50, a pliki o 2–5 testach
mają ich znacznie mniej — więc oba sygnały przepuszczały ten sam ruch. Dwa
sygnały chroniły przed tym samym, nie przed dwoma różnymi rzeczami.

**Zrobione:** NAPRAWA PRZYRZĄDU. Podłogi podniesione do 180 / 635 (jeden test
i pięć asercji pod stanem bieżącym), komentarz mówi teraz prawdę o własnej
wartości i o tym, kiedy ją podnosić.

---

## N-3 — DWIE perturbacje przestały cokolwiek psuć, a mimo to meldowały sukces

**Skutek po ludzku:** perturbacje to nasz dowód, że kontrole umieją zaświecić
czerwono. Dwie z nich nie psuły już NICZEGO — a mimo to raportowały „dowód
mutacji ✓" i zaliczały się jako udane. Czyli dwie kontrole były od dziś
wieczora **niesprawdzone**, a raport twierdził, że sprawdzone.

**Przyczyną jest MOJA WŁASNA zmiana z tego wieczora.** Commit `cdc6fbb`
(wąskie gardło §2) przemianował w `OdswiezanieSesji.php` zmienną `$konta`
na `$tozsamosc`. Perturbacje szukają tekstu do podmiany — i przestały go
znajdować.

**Dowód (wzorce, których szuka `perturbuj.py`, wobec kodu bieżącego):**

```
$ grep -cF 'if (! $this->wymagaOdswiezenia($konta)) {'      backend/app/Tozsamosc/OdswiezanieSesji.php
0
$ grep -cF 'if (RejestrSesji::uniewazniona(Typy::napis($konta'  backend/app/Tozsamosc/OdswiezanieSesji.php
0
$ grep -n "wymagaOdswiezenia(\|RejestrSesji::uniewazniona(" backend/app/Tozsamosc/OdswiezanieSesji.php
78:        if (RejestrSesji::uniewazniona($tozsamosc->sid())) {
84:        if (! $this->wymagaOdswiezenia($tozsamosc)) {
```

**Dlaczego to nie zapaliło się samo — trzy zabezpieczenia po kolei zawiodły:**

1. `podmien()` w `perturbuj.py` KRZYCZY, gdy wzorca nie ma (`SystemExit`) — to
   działa. Ale `perturbuj()` w `perturbacje.sh` **nie sprawdza kodu wyjścia**,
   a skrypt biegnie na `set -uo pipefail` — **bez `set -e`**. Zmierzone:
   `grep -n "^set " skrypty/perturbacje.sh` → `60:set -uo pipefail`.
   Czyli nieudana perturbacja nie przerywa scenariusza.
2. **Dowód mutacji ma GAŁĄŹ ZDEGENEROWANĄ.** Jest w formie NEGATYWNEJ —
   „starego tekstu już nie ma":
   ```
   dowod_mutacji "sprawdzanie wieku access tokenu zniknęło z kodu" \
       bash -c "! grep -q 'wymagaOdswiezenia($konta)' '$plik'"
   ```
   Wartość „prawda" jest zgodna z DWOMA światami: (I) mutacja weszła i usunęła
   tekst, (II) tekstu nigdy tam nie było, bo kod przemianowano. Zmierzone: świat
   II. Dowód mutacji zaświadczał o mutacji, której nie było.
3. `oczekuj_czerwone` zobaczyłoby czerwień i tak — bo `OdebranieRoliTest.php`
   ma dziś JEDEN test czerwony (noga 1). Czyli scenariusz zaliczyłby się
   z przyczyny **niezwiązanej** z badanym zjawiskiem. To jest P25 („perturbacja
   zaliczona z innej przyczyny niż badana") nałożone na gałąź zdegenerowaną.

**Zasięg klasy — przegląd WSZYSTKICH pięciu dowodów w formie negatywnej:**

| perturbacja | szukany tekst | jest w pliku? | werdykt |
|---|---|---|---|
| `role_zamrozone` (805) | `wymagaOdswiezenia($konta)` | **NIE (0)** | **ZDEGENEROWANY — przechodzi bez mutacji** |
| `logout_failsafe` (825) | `sidNiezweryfikowany` | **NIE (0)** | **ZDEGENEROWANY — przechodzi bez mutacji** |
| `wzmacniacz` (594) | `KLUCZ_ODSWIEZANIE, 1` | tak (1) | działa, ale forma nadal krucha |
| `id_token_sesja` (891) | `Crypt::encryptString($idToken)` | tak (1) | działa, ale forma nadal krucha |
| `uniewaznienie_sid` (964) | `RejestrSesji::uniewazniona` | tak (1) | **pada głośno** — tekst przetrwał zmianę nazw, więc negacja daje fałsz i scenariusz melduje „MUTACJA NIE WESZŁA W ŻYCIE" |

Ostatni wiersz jest pouczający: ta sama krucha forma raz zadziałała na naszą
korzyść, a dwa razy przeciwko — czyli o wyniku decydował PRZYPADEK doboru nazw,
a nie konstrukcja dowodu.

**Waga:** krytyczna dla przyrządu. Nie zmienia zachowania systemu, ale unieważnia
dowód „kontrola umie zaświecić czerwono" dla dwóch kontroli bezpieczeństwa
(odebranie roli, fail-safe wylogowania).
**Czy blokuje:** blokuje wiarygodność KAŻDEJ przyszłej rundy perturbacji.

**Świat alternatywny:** rozważony i odrzucony — można było twierdzić, że
`perturbuj.py` i tak przerwie przebieg. Zmierzone, że nie: brak `set -e`
i brak sprawdzenia kodu wyjścia w `perturbuj()`.

**Zrobione:** NAPRAWA PRZYRZĄDU — opisana osobno w `DZIENNIK.md`. Naprawiam,
bo to jawny wyjątek reguły nocy: bez sprawnych perturbacji kolejne rundy są
bezwartościowe.

---

## N-4 — `PLAN-FAZ.md` sam sobie przeczył: lista „OTWARTE, blokujące" niosła stan nieprawdziwy

**Skutek po ludzku:** plik, od którego zaczyna każda następna sesja, mówił
w jednym miejscu „jedyny czerwony to noga 1", a pięćdziesiąt linii niżej —
„test pozytywny BLK-22 jest CZERWONY i ma taki zostać". Sesja czytająca to jako
punkt wejścia zaczęłaby dzień od naprawiania testu, który działa.

**Dowód:**

```
PLAN-FAZ.md:15  „Bramka: CZERWONA — 1 nieudany krok z 22. Powód JEDEN … noga 1"
PLAN-FAZ.md:65  „BLK-22 — test pozytywny »żądanie po wylogowaniu dostaje 401«
                 jest CZERWONY i ma taki zostać do naprawy"

$ docker exec gabinet-app ./vendor/bin/pest
  ✓ it POZYTYWNY: żądanie PO wylogowaniu dostaje 401 — logout REALNIE z…  0.29s
  ⨯ it NOGA 1 [NIEROZSTRZYGNIĘTE — patrz komentarz]: tożsamość usunięta…  2.20s
  Tests:  1 failed, 180 passed (640 assertions)
```

Druga pozycja tej samej listy (**V-1**) mówiła „Projekt naprawy: D-2026-08-08-24",
choć naprawa jest WDROŻONA od commita `cdc6fbb` z tego samego wieczora.

**Waga:** wysoka — nie dla systemu, dla następnej sesji. To ta sama klasa co
`PROMPT-START.md` i nagłówek `CURRENT WORK`, które już raz kazały powtarzać
zamkniętą fazę. Plik stanu jest przyrządem: następna sesja startuje z jego
treści, więc jego nieaktualność propaguje się na wszystkie decyzje tej sesji.
**Czy blokuje:** nie technicznie; kosztuje czas i wiarygodność dokumentu.

**Świat alternatywny:** rozważony — może chodziło o INNY test o podobnej nazwie?
Odrzucony: w pliku jest dokładnie jeden test o tej treści i jest zielony.

**Zrobione:** poprawione ze SPROSTOWANIEM (nie cichą podmianą — ktoś mógł
przeczytać wersję nieprawdziwą). Przy pozycji 3 (V-4, V-8, V-9, W-8) wpisałem
wprost, że stanu **nie zmierzyłem** — „otwarte, bo nikt nie sprawdzał" to inny
stan wiedzy niż „otwarte, bo sprawdzone i czerwone", a zlanie ich w jedno jest
dokładnie tym, co wywróciło pozycję 1.

---

# RUNDA 6, część B — 17 znalezisk niezależnego weryfikatora

Pełny raport: [`RUNDA-6-B-RAPORT.md`](RUNDA-6-B-RAPORT.md) (719 wierszy, z tabelami
wszystkich dyskryminatorów i projektem odczytu rozstrzygającego).
Przypięty do SHA `49131d8`. **Nie naprawiam ich tej nocy** — poza tymi, które
sam zamknąłem wcześniej, zanim raport przyszedł (patrz zbieżność niżej).

## Najcięższe — po ludzku

**Runda NIE jest zerowa. F1 zostaje otwarte.** Weryfikator znalazł rzeczy,
których nie znalazłem, w tym trzy, które podważają twierdzenia uznane u nas
za zamknięte.

1. **R6B-1 — wiemy WRESZCIE, dlaczego noga 1 jest czerwona, i to nie jest defekt
   systemu.** Tożsamość niesie **middleware `StartSession`**, który sam jest
   singletonem kontenera i trzyma referencję do menedżera sesji sprzed
   `forgetInstance` — więc `forgetInstance('session')` nie ma jak go dosięgnąć.
   Dowód z przypiętego frameworka (`laravel/framework v13.24.0`):
   `SessionServiceProvider.php:22-26`, `StartSession.php:157-160`,
   `Store.php:116`, `Container.php:1731-1734`. Do tego klient testowy **nie odsyła
   ciasteczka sesji** (`MakesHttpRequests.php:730-737`), więc każde żądanie dostaje
   nowy losowy identyfikator. **To jest wada PRZYRZĄDU, nie produktu** —
   i weryfikator wskazał jednolinijkową, falsyfikowalną próbę naprawy
   (dołożyć `forgetInstance(StartSession::class)`), której świadomie NIE wdrożył.

2. **R6B-2 — test POZYTYWNY BLK-22 mierzy co innego, niż deklaruje.** Komentarz
   mówi „odtwarzamy granicę procesu jawnie"; z R6B-1 wynika, że jej NIE odtwarza,
   a 401 pochodzi ze znacznika unieważnienia w BAZIE. Czyli test dubluje
   `znacznik unieważnienia PRZEŻYWA Cache::flush()` i **nie dowodzi BLK-22**.
   To jest dokumentacja kłamiąca o kodzie w pliku, który uznawaliśmy za zielony.

3. **R6B-13 — pięć perturbacji tożsamości NIE MOŻE dziś zaświecić „z badanego
   powodu".** Wszystkie uruchamiają CAŁY `OdebranieRoliTest.php`, który jest
   trwale czerwony przez nogę 1 — więc `oczekuj_czerwone` musi zwrócić ✓
   niezależnie od mutacji. Symetrycznie: „kierunek odwrotny" w
   `p_uniewaznienie_sid` nie może dziś przejść i będzie meldował wadę tam,
   gdzie jej nie ma.

4. **R6B-16 / G-1 — perturbacje montują `.env` DEWELOPERA (z prawdziwymi
   sekretami).** V-2 zamknięto tylko po stronie bramki: `bramka.sh` buduje własny
   plik środowiska i podaje `--env-file`, a `perturbacje.sh:124-126` nie robi ani
   jednego, ani drugiego, więc `docker-compose.yml:73` montuje `./.env`.
   Łamie to regułę „klon weryfikatora NIGDY nie trzyma prawdziwych sekretów"
   i unieważnia porównywalność wyników między maszyną wykonawcy a czystym klonem.

5. **E-3 — zdanie z D-2026-08-08-27 jest NIEPRAWDZIWE.** Przegląd sterowników
   twierdzi: „testy badające magazyn sesji sięgają po sterownik produkcyjny
   jawnie". Zmierzone: `config(['session.driver' => 'redis'])` występuje tylko
   w 4 miejscach, a **cztery testy magazynu sesji biegną na `array`** — w tym
   JEDYNY test adwersarialny (wymuszone wylogowanie).

6. **R6B-9 — `RejestrSesji` łamie nasze własne cztery wymagania trwałości.**
   Zastosowaliśmy je do znacznika unieważnienia (baza), a mapa `sid → sesje`,
   bez której back-channel logout nie znajdzie ŻADNEJ sesji, została w cache'u
   z TTL 86400 s — podatna na `cache:clear`, restart i eksmisję. Utrata rejestru
   daje `skasowane_sesje = 0` **po cichu**. Fail-open.

7. **R6B-15 — sześć z ośmiu allowlist `--przyczyna` nic nie zawęża**, bo to nazwy
   testów, nazwy klas albo wartości `--filter`, które Pest wypisuje ZAWSZE.
   Realnie zawężają tylko dwie (komunikaty asercji).

Pozostałe: R6B-4 (`p_sesja_jawna` zalicza się z innej przyczyny — mutuje wartość
DOMYŚLNĄ, gdy środowisko i tak ma `SESSION_ENCRYPT=true`), R6B-6 (test odmowy IdP
bez odczytu bazowego), R6B-7 (`przygotuj_env()` — sześć `sed -i` bez odczytu
zwrotnego; `sed` bez trafienia kończy się SUKCESEM), R6B-8
(`skrypty-uruchamialne.sh` — „nieznana nazwa" przechodzi na cudzym kodzie wyjścia,
dopasowanie podciągiem), R6B-10 (puls harmonogramu też mieszka w cache'u
podlegającym eksmisji — D-28 tego nie zauważa), R6B-11 (aktywna kontrola portów
pyta HTTP-em, więc nie wykryje wystawionego Postgresa ani Redisa; bywa milcząco
pomijana i mimo to przechodzi), R6B-12, R6B-14 (bramka częstotliwości JWKS —
atomowość `Cache::add` mierzona magazynem `array`, czyli w jednym procesie),
R6B-17 (`p_statyka` — jedna z dwóch podmian jest cichym no-opem).

## Zbieżność z moimi znaleziskami — dwa niezależne tory, ten sam wynik

**R6B-3 to jest moje N-3.** Znaleźliśmy to niezależnie i innymi drogami: ja przez
przegląd form dowodu mutacji, weryfikator przez czytanie wzorców `perturbuj.py`
wobec kodu. Zbieżność dwóch niezależnych torów jest tu mocniejszym dowodem niż
którykolwiek z nich osobno.

Weryfikator dodał obserwację, której nie miałem: `p_uniewaznienie_sid` **ratował
się przypadkiem** — jego dowód negował wzorzec, który akurat przetrwał zmianę
nazw, więc poprawnie meldował „MUTACJA NIE WESZŁA". Czyli o wyniku decydował
przypadek doboru nazw, nie konstrukcja dowodu. Dokładnie to zamyka
`dowod_zniknieciem`.

## Stan wobec moich napraw z tej nocy (uczciwe rozliczenie)

| znalezisko B | stan po moich naprawach | uwaga |
|---|---|---|
| R6B-3 (martwe mutacje) | **ZAMKNIĘTE** przed przyjściem raportu | = N-3; wzorce uzgodnione, kod wyjścia sprawdzany, dowód z odczytem bazowym |
| R6B-5 (dowód na symbolu, którego nie ma) | **ZAMKNIĘTE** | `sidNiezweryfikowany` → `} catch (Throwable $blad) {`, wzorzec zweryfikowany (`grep -cF` → 1) |
| R6B-12 (perturbacje dowodzą 100/300, bramka egzekwuje 170/590) | **POGORSZONE MOJĄ RĘKĄ** | podniosłem podłogi do 180/635, więc rozjazd urósł. Naprawa N-2 była słuszna, ale **otworzyła** R6B-12 szerzej — zapisuję to przeciw sobie |
| pozostałe 14 | **OTWARTE** | do rundy 7, nie tej nocy |

Wpis o R6B-12 jest tu celowo, choć mnie obciąża: naprawa jednej kontroli
powiększyła lukę w drugiej, a raport, który to przemilcza, jest raportem
nieprawdziwym.

---

# Z3 — DOMYŚLNE ustawienia produktów, których nikt świadomie nie wybrał

Zadanie zapasowe Z3: przegląd, **bez zmieniania konfiguracji**. Wszystko poniżej
zmierzone na żywych kontenerach stosu deweloperskiego, nie odczytane z plików.
Kolumna „źródło" pochodzi wprost z `pg_settings.source` — czyli baza sama mówi,
czy wartość ktoś wybrał, czy przyszła z pudełka.

## N-5 — PostgreSQL: pięć wartości domyślnych o realnych skutkach

```
$ docker exec gabinet-postgres psql -U gabinet -d gabinet -At -c "select name…from pg_settings…"
TimeZone                            = UTC              [konfiguracja]   ← WYBRANE świadomie (CLAUDE.md §5)
max_connections                     = 100              [konfiguracja]
shared_buffers                      = 16384 8kB        [konfiguracja]   (=128 MB)
statement_timeout                   = 0 ms             [DOMYŚLNE]
idle_in_transaction_session_timeout = 0 ms             [DOMYŚLNE]
lock_timeout                        = 0 ms             [DOMYŚLNE]
deadlock_timeout                    = 1000 ms          [DOMYŚLNE]
log_min_duration_statement          = -1 ms            [DOMYŚLNE]
log_statement                       = none             [DOMYŚLNE]
work_mem                            = 4096 kB          [DOMYŚLNE]
ssl                                 = off              [DOMYŚLNE]
row_security                        = on               [DOMYŚLNE]
password_encryption                 = scram-sha-256    [DOMYŚLNE]  ← domyślne i DOBRE
synchronous_commit / fsync          = on / on          [DOMYŚLNE]  ← domyślne i DOBRE
```

Które z nich naprawdę coś znaczą dla TEGO systemu:

1. **`idle_in_transaction_session_timeout = 0` — najgroźniejsze.** Transakcja
   porzucona w połowie trzyma blokady wiersza **bez końca**. CLAUDE.md §6 opiera
   rezerwację terminu na transakcji z blokadą wiersza — jedno zawieszone żądanie
   blokuje termin na zawsze, a objawem jest „nie da się zarezerwować", bez błędu.
2. **`lock_timeout = 0`** — żądanie czekające na zajęty wiersz czeka bez limitu.
   Przy wymaganym teście „100 równoczesnych żądań o ten sam termin" (§6) to
   znaczy, że 99 żądań stoi w kolejce zamiast szybko odpaść.
3. **`statement_timeout = 0`** — pojedyncze zapytanie może trzymać połączenie
   dowolnie długo. Przy `max_connections = 100` dzielonych między php-fpm,
   Horizona i harmonogram, garść takich zapytań wyczerpuje pulę.
4. **`log_min_duration_statement = -1`** — **zero logowania wolnych zapytań**.
   F2 ma bramkę wydajności „< 300 ms na seedzie 111 osób"; bez tego logu nie ma
   jak zobaczyć regresji poza laboratorium.
5. **`ssl = off`** — akceptowalne w sieci dockerowej, ale do świadomego
   rozstrzygnięcia przed F9 (produkcja), nie do odziedziczenia z pudełka.

**Nie zmieniam żadnej z nich** — Z3 jest przeglądem. Rekomendacja do rozważenia
w F2 (nie dziś): `idle_in_transaction_session_timeout` i `lock_timeout` to dwie
linie, które chronią dokładnie tę regułę, którą CLAUDE.md nazywa krytyczną.

## N-6 — Redis: eksmisja, której NIE MA — i to zmienia treść D-2026-08-08-28

```
$ docker exec gabinet-redis redis-cli config get …
maxmemory                     0            ← DOMYŚLNE (bez limitu)
maxmemory-policy              noeviction   ← DOMYŚLNE
appendonly                    yes          ← WYBRANE (domyślne Redisa to `no`)
save                          3600 1 300 100 60 10000
protected-mode                no
requirepass                   (puste)
databases                     16
```

**Rzecz najważniejsza i sprzeczna z naszym własnym dziennikiem.** D-2026-08-08-28
nazywa wyzwalacz „eksmisja Redisa" i buduje na nim uzasadnienie rozdzielenia baz.
Zmierzone: przy `maxmemory = 0` i `maxmemory-policy = noeviction` **eksmisja LRU
nie może zajść w ogóle**. Zachowanie przy wyczerpaniu pamięci jest inne:
Redis zaczyna **odrzucać ZAPISY błędem OOM**, a odczyty działają dalej.

To nie unieważnia decyzji — rozdzielenie baz jest słuszne z innych powodów
(`cache:clear` i `FLUSHDB` są per-baza). Unieważnia **nazwany wyzwalacz**:
scenariusz opisany w decyzji nie zachodzi w tej konfiguracji, a zachodzi inny,
nieopisany. Wyzwalacz podany błędnie jest gorszy niż brak wyzwalacza, bo wygląda
na zmierzony.

Do tego, co przypomina zlecenie i co potwierdzam pomiarem: **eksmisja jest
własnością INSTANCJI, nie bazy** — `maxmemory-policy` nie ma wariantu per-baza,
więc rozdzielenie `cache=1` / `sesje=2` nie daje sesjom żadnej ochrony przed
eksmisją, gdyby limit kiedykolwiek ustawiono. Weryfikator B doszedł do tego samego
z drugiej strony (R6B-10): w tym samym cache'u siedzi **puls harmonogramu**, czyli
sygnał zdrowia — a D-28 tego nie zauważa.

**`protected-mode no` + pusty `requirepass`**: każdy proces, który dosięgnie portu,
wydaje dowolne polecenia. Port jest związany wyłącznie z `127.0.0.1` (zmierzone:
`docker inspect gabinet-redis` → `127.0.0.1:56389`), więc dziś to ryzyko lokalne,
nie sieciowe. Do świadomego rozstrzygnięcia przed F9.

## N-7 — rozdzielenie przestrzeni kluczy NIE działa dla procesów DŁUGO ŻYJĄCYCH

**Skutek po ludzku:** zmiana konfiguracji Redisa weszła w życie dla stron WWW,
ale nie dla działających w tle procesów Horizona. Przez kilka godzin połowa
systemu pisała do starej bazy, druga do nowej — a nikt by tego nie zobaczył.

**Dowód — eksperyment z odczytem bazowym, na moim własnym stosie deweloperskim:**

```
stan zastany (kontenery wstały 08.08 12:03 UTC, commit rozdzielenia 22:36 CEST):
  db0: 45 kluczy, w tym ~34 z prefiksem cache i ŻYWYMI TTL (35–809 s)
  db1: 4    (cache — deklarowana baza cache'u)
  db2: 104  (sesje)

sonda: gdzie pisze ŚWIEŻO uruchomiony proces?
  $ docker exec gabinet-app     php artisan tinker --execute='Cache::put("proba-nocna-app",1,120);'
  $ docker exec gabinet-horizon php artisan tinker --execute='Cache::put("proba-nocna-hz",1,120);'
  → OBA klucze wylądowały w db1 (exists=1), w db0 i db2 zero.

czyli: świeży proces = db1. A kto pisał do db0? Jedyne procesy PHP starsze
od commita to workery Horizona. Test rozstrzygający — RESTART Horizona:

  00:14:50  przed restartem: 34 klucze cache w db0, najwyższy TTL = 706 s
  00:17:12  po restarcie:    20 kluczy cache w db0, najwyższy TTL = 559 s

  706 − 559 = 147 s, a upłynęło 142 s. TTL opada DOKŁADNIE w tempie zegara,
  liczba kluczy maleje. Czyli po restarcie NIE POWSTAJE ani jeden nowy klucz —
  db0 już tylko wygasa.
```

**Wniosek:** to nie były pozostałości. To był **żywy, równoległy zapis** ze starej
konfiguracji, trwający od 22:36 do 00:14 — bo długo żyjące procesy czytają
konfigurację przy starcie i nigdy więcej.

**Waga:** wysoka dla wdrożenia. **Czy blokuje:** nie dziś, ale **musi trafić do
procedury wdrożeniowej F9**: każda zmiana połączeń Redisa wymaga
`horizon:terminate` (albo restartu), inaczej część systemu pisze do starej
przestrzeni, a `cache:clear` wydany przez proces WWW nie czyści tego, co
zapisali workerzy. Spójność cache'u rozjeżdża się cicho.

**Czego ten pomiar NIE dowodzi:** nie wykazałem, KTÓRY konkretnie kod Horizona
pisał te klucze (nie czytałem wartości — mogłyby zawierać dane osobowe).
Wykazałem, że pisał je proces starszy niż zmiana konfiguracji i że restart
to zatrzymał. Do rozstrzygnięcia, czy któreś z nich były kluczami SESJI sprzed
rozdzielenia — jeśli tak, jest to również pytanie o retencję (RODO), nie tylko
o spójność cache'u.

---

# RUNDA 6, część A — 12 znalezisk (bramka na żywym, izolowanym stosie)

Pełny raport: [`RUNDA-6-A-RAPORT.md`](RUNDA-6-A-RAPORT.md). Przypięty do `49131d8`.

Metryki przebiegu (surowo, czysty klon, projekt `gabinet-r6a`, porty 8107/55461/56407):
**22 kroki, 1 nieudany** (`[19] testy (Pest)`), **181 testów** (180 zielonych,
1 czerwony, 0 pominiętych), **640 asercji**, Pint `PASS 74 files`, Larastan
`[OK] No errors`, gitleaks `49 commits scanned, no leaks found`.
Stopka: `BRAMKA CZERWONA — 1 nieudanych kroków z 22`.

## Rzecz najważniejsza tej nocy: NOGA 1 to wada PRZYRZĄDU. System jest w porządku.

**R6A-2 — zmierzone, nie wywnioskowane.** Weryfikator zbudował przyrząd
rozstrzygający (pełny reset singletonów **plus jawne niesienie ciasteczka sesji**)
i zmierzył OBIE gałęzie:

```
[R6A-P] BAZA (tożsamość NIETKNIĘTA):   200  {"zalogowany":true,…,"role":["koordynator"]}
[R6A-P] po destroy długość w magazynie: 0
[R6A-P] PO USUNIĘCIU:                  401  {"zalogowany":false}
[R6A-P] żądań do punktu tokenów:       1     ← tylko wymiana kodu przy logowaniu
```

**Wniosek: system NIE wskrzesza tożsamości z refresh tokenu. Wymóg nogi 1
standardu B8 jest SPEŁNIONY.** Test jest czerwony, bo jego symulacja granicy
procesu jest niekompletna: `app()->forgetInstance('session')` tworzy NOWY
menedżer, ale middleware `StartSession` — sam będąc singletonem — trzyma STARY,
a z nim wczytany w pamięci `Store` z tożsamością:

```
[R6A] StartSession::manager id=5858  vs  app("session") id=3801
[R6A] długość sesji w magazynie po destroy: 0        ← magazyn PUSTY
[R6A] status BEZ forgetInstance(StartSession): 200   ← a mimo to 200
[R6A] status PO  forgetInstance(StartSession): 401
```

**TRZY niezależne tory, jeden wynik.** Część B doszła do tego samego mechanizmu
CZYTAJĄC źródła frameworka (`SessionServiceProvider.php:22-26`,
`StartSession.php:157-160`, `Container.php:1731-1734`), część A — MIERZĄC
identyfikatory obiektów na żywym stosie. Zbieżność metody analitycznej
i pomiarowej jest mocniejsza niż każda z osobna.

**Weryfikator A złapał się przy tym na własnej gałęzi zdegenerowanej i sam ją
zgłosił:** jego pierwszy dyskryminator (`forgetInstance(StartSession)` → 401)
dawał 401 TAKŻE przy nietkniętej tożsamości — był więc zgodny z dwoma światami.
Dopiero odczyt bazowy to ujawnił i wymusił zbudowanie lepszego przyrządu.

**NIE NAPRAWIAM tego dziś.** Zlecenie nocne mówi wprost: „jeden czerwony (noga 1)
ma zostać czerwony". Naprawa testu bezpieczeństwa autorem, w nocy, bez rundy,
zamieniłaby jedyny uczciwy czerwony na zielony bez pokrycia.

## Znaleziska podważające twierdzenia uznane u nas za zamknięte

**R6A-3 — wąskie gardło §2 NIE jest strukturalne.** `TozsamoscSesji::zMagazynu()`
jest **publiczną statyczną fabryką przyjmującą DOWOLNĄ tablicę** — z magazynem
wiąże ją wyłącznie nazwa. Weryfikator wytworzył tożsamość trzema drogami (dane
z żądania, `Reflection`, `unserialize`) i uzyskał przez HTTP pełne uprawnienia
koordynatora **bez żadnego logowania**:

```
{"zalogowany":true,"sub":"napastnik-1","role":["koordynator"],
 "bramki":{"panel.koordynacji":true,"rozliczenia.akceptuj":true,"dziennik.zapisz":true}}
```

**Uczciwe zastrzeżenie samego weryfikatora:** to NIE jest dziura eksploatowalna
z zewnątrz w obecnym kodzie — trasy musiał dopisać. Obalone zostaje twierdzenie
o STRUKTURZE („nie da się"), nie stan bieżący aplikacji.
**To jest moje twierdzenie i było za mocne.** Pisałem „NIEWYWOŁYWALNE, a nie
zabronione warunkiem" — a warunek tylko przeniósł się o poziom wyżej.
Naprawa jest tania i strukturalna: `zMagazynu()` prywatne, jedyne wejście przez
`SesjaKonta::odczytaj(Request)`. **Nie robię jej dziś** — to zmiana kodu
produkcyjnego w obszarze, który właśnie był weryfikowany.

Druga strona sprawdzona: gardło NIE jest za ciasne (mutacja `odczytaj() → null`
zapala 8 testów, w tym ten pilnujący legalnego odświeżenia).

**R6A-4 — V-1 OTWARTE: mechanizm własnych haseł przechodzi PRZEZ nowe gardło.**
Logowanie hasłem na zadeklarowanej trasie, skrót w zadeklarowanej kolumnie,
prymityw spoza zamkniętej listy, zapis tożsamości przez `SesjaKonta::zaloz()` —
`BrakWlasnychHaselTest`: **7 passed**. Waga: **krytyczna** (CLAUDE.md §2).
Kontrola obiecana w D-2026-08-08-24 (liczność zbioru pisarzy = 1) **nie powstała**.

**R6A-1 — test „POZYTYWNY … logout REALNIE zabija sesję" przechodzi, gdy logout
NIE kasuje żadnej sesji.** Mutacja: `destroy()` usunięte, licznik podbijany mimo
to → ten właśnie test **zielony**, a dwa inne czerwone. Czyli 401 pochodzi ze
znacznika w PostgreSQL, nie z kasowania. Zbieżne z R6B-2, uzyskane inną metodą
(A mutacją, B czytaniem źródeł).

**R6A-5 — potwierdza N-3 po raz TRZECI**, uruchamiając każde ogniwo łańcucha:
`PERTURBACJA NIEUDANA … KOD WYJSCIA=1` → plik niezmieniony → `DOWOD MUTACJI KOD=0`
(„mutacja potwierdzona"). Dodaje zasięg, którego nie policzyłem: `oczekuj_czerwone`
bez `--przyczyna` celujące w `OdebranieRoliTest.php` występuje w liniach
**807, 827, 847, 893, 903** — pięć scenariuszy, które dopóki noga 1 jest czerwona,
**nie mogą paść**.

## Pozostałe

**R6A-9** — `PLAN-FAZ.md` ma **DWIE** sekcje `CURRENT WORK` (linie 5 i 113)
o sprzecznym stanie: druga mówi „`BRAMKA OK — 21 kroków, 0 nieudanych`",
„151 testów (479 asercji)", „20 scenariuszy". Sesja czytająca tę drugą startuje
z fałszywego „BRAMKA OK". **Miałem ten pomiar w ręku o 23:36 i go nie wykorzystałem** —
`grep -n "CURRENT WORK"` zwrócił mi wtedy obie linie, a ja przeczytałem tylko pierwszą.

**R6A-10** — `bramka.sh` liczy `PLIK_ENV` w linii 73, a `--projekt` parsuje w 98,
więc **nazwa pliku środowiska ignoruje `--projekt`**: dwa przebiegi o różnych
projektach dzielą JEDEN plik z wygenerowanym `APP_KEY` i `DB_PASSWORD`, a zamek
(liczony per projekt) ich nie rozdziela. Dotyczy też `perturbacje.sh`, który woła
`bramka.sh --projekt … --tylko-kod`.

**R6A-11** — **retencja nie jest podpięta do harmonogramu**: `ZadanieRetencji` nie
ma ani jednego wywołującego w `app/`, `routes/`, `bootstrap/`; `routes/console.php`
ma jedno zadanie (`gabinet:puls`). Kontrole są falsyfikowalne, ale mierzą
bibliotekę i rejestr, nie działający mechanizm. Na gałęzi `faza-1-retencja`.

**R6A-6** — kontrola „szyfrowanie domyślnie, czytane z TREŚCI pliku" przechodzi,
gdy literał wystąpi w KOMENTARZU (`str_contains` nie odróżnia kodu od komentarza).

**R6A-7** — `ObietniceKomentarzyTest` obejmuje regexem `[UWO]-\d+` sześć znaczników,
a **pomija siedem** — w tym `B7`, `B8`, `BLK-22`, `D-2026-08-08-24`, czyli wszystkie,
na które powołuje się warstwa `Tozsamosc`. Zdanie z `WYTYCZNE-PRACY.md` o „każdym
znalezisku powołanym w kodzie produkcyjnym" jest nieprawdziwe.

**R6A-8** — komentarz w `config/database.php` wiąże rozdzielenie baz z ochroną przed
eksmisją; D-2026-08-08-28 mówi wprost coś przeciwnego, a plik jej nie cytuje.
Zbieżne z moim N-6 i z R6B-10.

**R6A-12** — poza gardłem została jedna ścieżka KASOWANIA tożsamości
(`LogowanieController.php:186-187`) i jeden ODCZYT literałem `'konta'` (`:174`),
z pominięciem stałej `KLUCZ`.

## Czego A NIE zdążył — cytuję, bo cichy brak pokrycia jest gorszy niż jawny

Zero mutacji dla: `BramkiTest`, `ModelDanychTest`, `RejestrRegulTest`,
`WzmacniaczZadanTest`, `SekretyTest`, `SzkieletTest`, `LogowanieTest`.
Nie uruchomił pełnego `perturbacje.sh`. **Sprawdził 2 z 30 wzorców `perturbuj.py`
pod kątem nieaktualności — pozostałe 28 mogą mieć tę samą wadę.** Weryfikator sam
nazywa to najpilniejszą luką swojego pokrycia i zgadzam się z tą oceną.

## Zadanie G — odpowiedź, której się nie spodziewałem

Weryfikator A **nie znalazł** przypadku, w którym zasłoniłem defekt systemu
etykietą „przyrząd". Zwrócił uwagę na coś odwrotnego: przy nodze 1 miałem pełną
wygodę zamknięcia sprawy zdaniem „to artefakt klienta testowego" i tego **nie
zrobiłem** — zostawiłem `NIEROZSTRZYGNIĘTE`. Zmierzył, że atrybucja, której nie
postawiłem, była prawdziwa.

Wskazał natomiast coś gorszego niż wygodna atrybucja: **narzędzie do wykrywania
fałszywych zielonych samo produkuje fałszywe zielone** (R6A-5), a `CURRENT WORK`
niesie z tego liczbę „30 scenariuszy ze strażnikiem przyczyny czerwieni" jako
miarę pokrycia. Cytat z raportu: *„To ten sam mechanizm co wygodna atrybucja,
tylko zautomatyzowany: nie ma tu człowieka, który przypisuje winę — jest skrypt,
który zawsze mówi »zdaliśmy«."*

---

## N-7 — KOREKTA WŁASNEGO ZNALEZISKA (03:00 sesji, po pomiarze nazw kluczy)

Znalezisko N-7 wyżej twierdzi, że **workery Horizona pisały cache do db0 przez
półtorej godziny**, i przypisuje zatrzymanie tego zapisu **mojemu restartowi
Horizona**. Obniżam to twierdzenie do tego, co jest zmierzone.

**Co zostaje zmierzone i pewne:**

```
$ docker exec gabinet-redis redis-cli -n 0 --scan   (odczyt SAMYCH NAZW, 00:29:47)
gabinet_horizon:master:249476ee1a97-WTVw
gabinet_horizon:masters
gabinet_horizon:monitor:time-to-clear
gabinet_horizon:supervisor:249476ee1a97-WTVw:supervisor-1
gabinet_horizon:supervisors

DBSIZE:  db0 = 5   ·   db1 (cache) = 4   ·   db2 (sesje) = 104
klucze db0 SPOZA prefiksu `gabinet_horizon:`  →  ZERO
```

- **Rozdzielenie przestrzeni kluczy DZIAŁA** — potwierdzone z zewnątrz, nie
  z konfiguracji: kolejki w db0, cache w db1, sesje w db2.
- **Wcześniej db0 zawierał ~34–45 kluczy z prefiksem cache'u**, o krótkich TTL,
  które opadały i zniknęły. To jest zmierzone.

**Czego NIE ustaliłem i cofam:** który proces te klucze pisał, ani że to mój
restart Horizona je zatrzymał. Mój „test rozstrzygający" (restart → rozpad TTL
w tempie zegara) jest zgodny z DWOMA światami: (I) restart zatrzymał zapisy,
(II) to były pozostałości, które i tak wygasały, a restart nie miał z tym nic
wspólnego. **Rozpad w tempie zegara zachodzi w obu.** Zmierzyłem zgodność
z hipotezą, nie jej wyłączność — czyli popełniłem TĘ SAMĄ wadę, którą ta noc
bada, po raz trzeci.

**Zastrzeżenie do wyjaśnienia „to były pozostałości":** arytmetyka mu nie
sprzyja. Klucze o TTL ≤ 809 s widziane o 00:14 musiałyby powstać po ~00:00,
czyli **półtorej godziny PO** zmianie konfiguracji (commit `5f1eaf4`, 22:36).
Pozostałość sprzed 22:36 z takim TTL już by nie żyła. Więc ani moja wersja,
ani konkurencyjna nie są dowiedzione.

**Co to rozstrzyga (jedna komenda, rano):** powtórzyć na CZYSTYM stosie, gdzie
żadnej historii sprzed rozdzielenia być nie może. Pojawienie się w db0
jakiegokolwiek klucza spoza prefiksu `gabinet_horizon:` obala wersję
„pozostałości"; brak takiego klucza ją potwierdza.

**Co z N-7 zostaje jako ostrzeżenie mimo obniżenia wagi:** teza „długo żyjące
procesy czytają konfigurację przy starcie i nigdy więcej" jest prawdziwa
niezależnie od tego sporu i **musi trafić do procedury wdrożeniowej F9**
(`horizon:terminate` przy każdej zmianie połączeń Redisa). Nie dowiodłem, że to
zaszło u nas — dowiodłem, że może.

## O-N1 — ZAMKNIĘTE pomiarem (błąd częstości bazowej po mojej stronie)

Zgłosiłem O-N1, bo klucz o TTL 86400 s „co do sekundy" zgadzał się
z `RejestrSesji::CZAS_ZYCIA_SEKUND`. **Ta zbieżność nie niosła prawie żadnej
informacji: 86400 to po prostu DOBA** — najczęstsza wartość TTL w oprogramowaniu
w ogóle. Gdyby stała wynosiła 73 412 s, zgodność byłaby mocnym tropem; przy
86400 spodziewamy się jej u połowy komponentów stosu.

Rozstrzyga **NAZWA klucza, nie jego TTL** — a nazwę wolno odczytać bez zaglądania
w wartość. Odczytałem (wyżej): w db0 nie ma ani jednego klucza rejestru sesji,
są wyłącznie klucze Horizona, który siedzi na połączeniu domyślnym zgodnie
z projektem. **O-N1 zamknięte.**

Lekcja jest ogólniejsza niż ten wpis i dlatego ją zapisuję: *zbieżność liczb
jest tropem tylko wtedy, gdy liczba jest RZADKA.* Szukałem potwierdzenia
w wartości, którą dzieli pół świata, zamiast w nazwie, która identyfikuje
jednoznacznie — i o mało nie wydałem na to rundy z `MONITOR`-em.

---

## N-8 — mój własny DZIENNIK zapalił bramkę: nazwa pliku wzięta za klucz API

**Skutek po ludzku:** pisząc dziennik tej nocy, wprowadziłem DRUGI czerwony krok
w bramce. Skaner sekretów uznał **nazwę pliku** za sekret. Zlecenie mówiło
„jeden czerwony (noga 1) ma zostać czerwony" — a ja przez własną dokumentację
zrobiłem drugi.

**Dowód:**

```
=== [21] sekrety (gitleaks) — ten sam skan co w CI
INF  56 commits scanned.          (weryfikator rundy 6 na 49131d8 skanował 49)
WRN  leaks found: 1
    ^ KROK NIEUDANY
BRAMKA CZERWONA — 2 nieudanych kroków z 22

$ gitleaks … --report-format json
REGUŁA  : generic-api-key
PLIK    : docs/noc-2026-08-08/DZIENNIK.md
LINIA   : 62
COMMIT  : 83775f47fa
SEKRET  : ZLECENIE-RUN…
KONTEKST: zapisana obok: `ZLECENIE-RUNDA-6.md`
```

„Sekretem" jest **nazwa pliku** `ZLECENIE-RUNDA-6.md` w zdaniu „Treść obu zleceń
zapisana obok:". Heurystyka entropii nie odróżnia nazwy pliku od klucza API.

**Dlaczego samo przeredagowanie nie wystarczyło:** gitleaks w trybie bramki
skanuje **HISTORIĘ** (56 commitów). Treść z commita `83775f4` zostaje w skanie
niezależnie od tego, co pokazuje drzewo robocze. Historii nie przepisuję —
przepisywanie historii o pierwszej w nocy, bez świadka, na gałęzi tuż przed
wypchnięciem, to dokładnie ten rodzaj obejścia, którego zlecenie zabrania.

**Zrobione — dwa niezależne posunięcia, nie jedno:**

1. **Zdanie przeredagowane**, żeby drzewo robocze nie zależało od wyjątku.
2. **Wyjątek najwęższy z możliwych**: JEDNA reguła (`generic-api-key`) na JEDNYM
   katalogu (`docs/noc-2026-08-08/`). Reguły WŁASNE projektu obowiązują tam dalej.

**Sprawdzone, że wyjątek NIE oślepił skanera** — bo wyjątek bez dowodu
falsyfikowalności jest dziurą, nie decyzją. Podłożyłem przynętę w tym samym
katalogu, którego dotyczy wyłączenie:

```
$ printf 'DB_PASSWORD=…\nSMSAPI_TOKEN=…\n' > docs/noc-2026-08-08/PRZYNETA.md
$ gitleaks detect --no-git …
WRN  leaks found: 6          ← reguły własne ŁAPIĄ mimo allowlisty
$ rm docs/noc-2026-08-08/PRZYNETA.md   (drzewo sprawdzone: czyste)
```

Po naprawie, tą samą komendą co w bramce: `56 commits scanned` → **`no leaks found`**.

**Waga:** średnia. Nie dotyczy bezpieczeństwa produktu — dotyczy tego, że
**pisanie dokumentacji potrafi zapalić bramkę**, a to nie jest oczywiste
i kosztowało mi drugi czerwony.
**Czy blokuje:** nie; zamknięte tej samej nocy z dowodem w obie strony.

**Lekcja szersza niż ten wpis:** raport z weryfikacji jest tekstem o wysokiej
entropii — pełnym identyfikatorów sesji, skrótów i nazw plików. Katalog raportów
i skaner sekretów będą się o siebie ocierać zawsze, nie ten jeden raz.

---

## N-9 — przegląd WSZYSTKICH wzorców mutacji: 16/16 trafia; ale to nie cała rodzina

Weryfikator A nazwał to najpilniejszą luką swojego pokrycia („sprawdziłem 2 z 30,
pozostałe 28 mogą mieć tę samą wadę"). **Sprawdziłem — bo to POMIAR na przyrządzie,
a nie naprawa przedmiotu.**

**Metoda.** Dla każdego polecenia `perturbuj.py`: uruchom, zapisz kod wyjścia
ORAZ liczbę zmienionych plików (dwa niezależne sygnały — sam kod wyjścia mógłby
paść z innego powodu), przywróć stan, **sprawdź, że wrócił**. Przerwanie przy
pierwszym drzewie, które nie wróciło do stanu wyjściowego.

**Wynik:**

```
NAZWA                        KOD    ZMIAN    WERDYKT
hasla-podloz                 0      3        TRAFIA
hasla-podloz-v2              0      3        TRAFIA
nonce-fail-open              0      1        TRAFIA
lockfile-rozjazd             0      1        TRAFIA
wzmacniacz-zadan             0      1        TRAFIA
suita-pominieta              0      1        TRAFIA
obietnica-bez-dowodu         0      1        TRAFIA
sesja-jawna                  0      1        TRAFIA
id-token-jawny               0      1        TRAFIA
retencja-bez-kasowania       0      1        TRAFIA
id-token-zakodowany          0      1        TRAFIA
role-zamrozone               0      1        TRAFIA   ← naprawione tej nocy (N-3)
uniewaznienie-po-sid         0      1        TRAFIA   ← naprawione tej nocy (N-3)
logout-bez-failsafe          0      1        TRAFIA
role-ze-zlego-zrodla         0      1        TRAFIA
logout-niezweryfikowany-sid  0      1        TRAFIA
(3 procedury *-sprzataj pominięte — sprzątają po innych, nie mutują)

TRAFIA: 16 · MARTWE: 0 · drzewo na koniec: CZYSTE
```

**Ale liczba „30" znaczy co innego, niż wyglądało — i to jest właściwy wynik
tego przeglądu.** „30 scenariuszy" to funkcje `p_*` w `perturbacje.sh`.
`perturbuj.py` ma ich tylko 19 (16 mutacji + 3 sprzątaczki). **Różnicę stanowią
scenariusze mutujące SUROWYM `sed`-em wprost w skrypcie** — a `sed`, który nie
trafił, **kończy się sukcesem**. Ta klasa nie ma ani `podmien()`, które krzyczy,
ani (w większości) dowodu mutacji:

```
$ grep -n "sed -i" skrypty/perturbacje.sh    → 8 miejsc
  383  p_testy            466  p_sekrety (KEYCLOAK)     1171  p_tozsamosc
  442  p_statyka          476  p_sekrety (SMSAPI)       1250  p_biala_lista
  1124 p_puls                                          1263  p_zamrozenie
```

**Potwierdziłem pomiarem znalezisko R6B-17** (weryfikator wyprowadził je
z lektury, ja zmierzyłem):

```
wzorzec w perturbacje.sh:442 szuka:  string $domyslny = .."..): string
rzeczywista sygnatura Typy.php:23:   string $domyslny = ''): string

$ cp Typy.php /tmp/przed; sed -i '<ten sam wzorzec>' Typy.php; diff …
WYNIK: BEZ ZMIAN — sed jest cichym no-opem
```

Czyli `p_statyka` działa **wyłącznie** dzięki drugiej mutacji (dopisanej funkcji
tuż obok); pierwsza jest mutacją-widmem od nieznanego czasu i nikt tego nie
widział, bo scenariusz i tak świecił na zielono.

**Waga:** średnia — po naprawach z N-3 żadna mutacja `perturbuj.py` nie jest
martwa, ale klasa „surowy `sed` bez dowodu mutacji" pozostaje niezabezpieczona
strukturalnie: `sed` nie krzyczy, więc każdy przyszły refaktor może ją wyciszyć
dokładnie tak, jak wyciszył `p_statyka`.
**Czy blokuje:** nie. **Do zrobienia rano:** przenieść te 8 podmian do
`perturbuj.py` (gdzie `podmien()` krzyczy) albo obłożyć je `dowod_zniknieciem`.

**Czego ten przegląd NIE sprawdza, mówię wprost:** że mutacja jest SENSOWNA —
tylko że TRAFIA. „Trafia" znaczy „plik się zmienił", nie „zmiana łamie regułę,
o którą chodzi". Odpowiedź na to drugie pytanie daje wyłącznie pełny przebieg
`perturbacje.sh`, którego tej nocy nie wykonałem.

---

## N-10 — WCISNĄŁEM DO REPOZYTORIUM ŻYWĄ PERTURBACJĘ REGUŁY 24 H

**Najpoważniejsza rzecz, jaką zrobiłem tej nocy. Moja wina, w całości.**

**Skutek po ludzku:** przez kilkanaście minut w repozytorium **lokalnym** leżała
zepsuta reguła decydująca o tym, czy pacjent dostaje bezpłatne odwołanie wizyty.

> **SPROSTOWANIE, zanim ten wpis zdążył się zestarzeć.** Pisząc go po raz
> pierwszy napisałem „i na wypchniętej gałęzi". **To nieprawda i sprawdziłem to
> dopiero po napisaniu.** Zmierzone:
> `git show origin/faza-1-retencja:…/OcenaAnulacji.php` → `>=` (poprawnie);
> `origin/faza-1-retencja` stoi na `d81c00b`, a złamany commit `041e528`
> i jego cofnięcie `534360e` są **wyłącznie lokalne**. Nikt nie mógł tego
> pobrać. Zdarzenie zostaje poważne, ale nie było publiczne — a różnica
> między „w repozytorium" a „na zdalnej" jest dokładnie tą, której nie wolno
> zmyślać w raporcie o własnym błędzie. Pacjent odwołujący **dokładnie 24:00:00 przed wizytą** tracił
to prawo. To jest ta granica, którą ten projekt sprawdza co do sekundy i której
poświęcona jest osobna bramka fazy.

**Dowód — wartość tej linii w kolejnych commitach:**

```
$ for C in …; do git show "$C:backend/app/Reguly/OcenaAnulacji.php" | grep -o 'sekundDoWizyty >=\? \$sekundOkna'; done
49131d8   sekundDoWizyty >= $sekundOkna     ← poprawnie
0304245   sekundDoWizyty >= $sekundOkna
12724ef   sekundDoWizyty >= $sekundOkna
d81c00b   sekundDoWizyty >= $sekundOkna
041e528   sekundDoWizyty >  $sekundOkna     ← ZŁAMANE (mój commit)
drzewo    sekundDoWizyty >= $sekundOkna     ← trap perturbacji przywrócił plik
```

Komentarz tuż nad tą linią mówi wprost, dlaczego `>=` jest jedyną poprawną
postacią: *„Dokładnie 24:00:00 to jeszcze bezpłatne odwołanie — pacjent widzi
datę graniczną co do minuty i musi móc trafić w nią bez ryzyka."*

**Przyczyna: zrobiłem `git add -A` W TRAKCIE biegnącego zestawu perturbacji.**
Perturbacja `p_testy` mutuje właśnie ten plik, a ja w tym samym czasie
commitowałem poprawkę do `PODSUMOWANIE.md`. `git add -A` zgarnął jedno i drugie.

**Reguła, którą złamałem, jest zapisana w tym repozytorium** (`WYTYCZNE-PRACY.md`,
sekcja o przyrządach): *„commit robimy dopiero po zakończeniu perturbacji i po
sprawdzeniu, że drzewo wróciło do stanu sprzed przebiegu"*. Przeczytałem ją tej
nocy, cytowałem ją w dzienniku — i złamałem cztery godziny później.

**Dlaczego nie wyszło od razu:** bramka nie biegła po tym commicie, a suita
testów, którą uruchamiałem wcześniej, mierzyła DRZEWO ROBOCZE (poprawne), nie
HEAD. Wyszło dopiero przy sprzątaniu po awarii zestawu perturbacji — czyli
przypadkiem, przy zupełnie innej czynności.

**Co byłoby, gdyby nie wyszło:** granicę 24 h pilnują testy tabelaryczne
(23:59 / 24:00 / 24:01), więc **następny przebieg bramki zapaliłby się na
czerwono** — kontrola zadziałałaby. Ale zapaliłby się dzień później i wyglądałby
jak nowa regresja bez przyczyny, a nie jak commit z konkretnej minuty.

**Zrobione:**
- Przywrócone i **zmierzone**: `pest tests/Unit/OcenaAnulacjiTest.php` →
  `36 passed (89 assertions)`.
- Cofnięcie osobnym commitem z pełnym opisem — **nie przepisuję historii**;
  `041e528` zostaje w niej wraz z tym, co znaczył.

**Waga:** wysoka. **Czy blokuje:** nie — zamknięte tej samej nocy z pomiarem.

**Wniosek, który jest ważniejszy niż samo zdarzenie.** Rzeczy, które
zapisywaliśmy przez cały dzień o przyrządach, dotyczą też przyrządu, którym jest
`git add -A`. Reguła „nie commituj w trakcie perturbacji" jest zapisana w dwóch
miejscach i nie uchroniła mnie, bo egzekwuje ją WYŁĄCZNIE pamięć. To jest ta
sama klasa co „kontrola, którą można wyłączyć niewidocznie": **zabezpieczenie
istniejące tylko jako zdanie w dokumencie nie jest zabezpieczeniem.**

Do rozważenia rano (nie robię tego w nocy — to zmiana w przyrządzie po
zdarzeniu, bez rundy): `perturbacje.sh` mógłby zakładać **plik-znacznik**
na czas przebiegu, a `pre-commit` odmawiać commita, dopóki znacznik istnieje.
Wtedy reguła przestaje zależeć od pamięci wykonawcy — dokładnie tak, jak
`TozsamoscSesji` zamieniło strażnika na strukturę.

---

## N-11 — moja naprawa przyrządu wywaliła zestaw perturbacji na scenariuszu 26 z 30

**Skutek po ludzku:** naprawa, którą zrobiłem w nocy, żeby perturbacje znów
działały, sama je zatrzymała w połowie — i wyszło to dopiero, gdy uruchomiłem
cały zestaw.

**Dowód:**

```
=== PERTURBACJA: role zamrożone na całą sesję — odebranie roli nie działa
skrypty/perturbacje.sh: line 880: this: unbound variable
KOD=1
(scenariuszy wykonanych: 25 z 30 · kontroli ✓ 38 · kontroli ✗ 0)
```

**Przyczyna:** wzorce, które wstawiłem do `dowod_zniknieciem`, są fragmentami
kodu PHP i zawierają `$this`, `$blad`, `$idToken`. Umieściłem je w **cudzysłowie**,
a bash rozwija w nim zmienne; przy `set -u` nieznana zmienna kończy skrypt.
Trzy z pięciu wzorców miały ten problem. Poprawione na apostrofy;
`bash -n` czysty.

**Co to mówi o poprzedniej weryfikacji:** sprawdziłem wtedy `bash -n` (składnia)
i zachowanie funkcji na spreparowanych danych (trzy światy) — obie kontrole
przeszły i **obie były prawdziwe**. Żadna nie mogła złapać tego błędu, bo
rozwinięcie zmiennej w argumencie zachodzi u WOŁAJĄCEGO, a ja testowałem
WOŁANEGO. Test funkcji nie zastępuje uruchomienia jej w miejscu wywołania.

**Waga:** wysoka dla przyrządu (zestaw przerywał w połowie).
**Czy blokuje:** nie — naprawione i ponownie uruchomione tej samej nocy.

**Dlaczego to jest argument za uruchamianiem pełnego zestawu, a nie za odkładaniem
go do rana:** rozważałem odłożenie („maszyna zajęta, późno, wynik trzeba
interpretować"). Gdybym odłożył, rano zastałbym zestaw, który wywala się
w połowie, i naprawę przyrządu opisaną w dzienniku jako zrobioną i sprawdzoną.

---

## N-12 — pełny zestaw perturbacji przebiegł: 45 kontroli OK, 1 czerwona — PRZEWIDZIANA

Po naprawie cytowania (N-11) zestaw przeszedł **wszystkie 30 scenariuszy**,
bez awarii, i zostawił drzewo czyste.

```
PERTURBACJE CZERWONE — 1 kontroli NIE zareagowało na złamaną regułę (udanych: 45)
KOD_ZESTAWU=1
scenariuszy: 30 · kontroli ✓ 45 · kontroli ✗ 1
drzewo po zestawie: czyste (poza moimi plikami dokumentacji)
```

**Jedyna czerwona kontrola to KIERUNEK ODWROTNY scenariusza BLK-22:**

```
=== PERTURBACJA: BLK-22 — unieważnienie sesji po sid, odporne na rotację identyfikatora
    · dowód mutacji (był → zniknął): sprawdzanie unieważnienia po sid zniknęło z kodu
    ✓ test pozytywny wykrywa konsumenta serwującego po wylogowaniu (kod 1)
    ✗ kontrola pozostaje czerwona mimo przywróconego mechanizmu
```

**To nie jest nowa wada — to DOKŁADNIE to, co weryfikator B przewidział z lektury
kodu (R6B-13), zanim ktokolwiek uruchomił zestaw:** *„Symetrycznie: »kierunek
odwrotny« w `p_uniewaznienie_sid` nie może dziś przejść, bo plik jest trwale
czerwony — będzie meldował wadę tam, gdzie jej nie ma."*

Mechanizm: kierunek odwrotny przywraca zepsuty kod i oczekuje, że
`OdebranieRoliTest.php` wróci na zielono. Nie wróci, bo w tym pliku siedzi
**NOGA 1**, czerwona z zupełnie innego powodu. Kontrola mierzy więc plik,
a nie mechanizm, który przywróciła.

**Wartość tego wyniku jest podwójna:**
1. **Predykcja weryfikatora potwierdzona pomiarem.** B wyprowadził to
   z czytania kodu i nazwał jako R6B-13; zestaw uruchomiony niezależnie
   zaświecił dokładnie tam. Analiza i pomiar zgodne co do jednej pozycji.
2. **`PERTURBACJE CZERWONE — 1` jest dziś stanem OCZEKIWANYM**, nie regresją.
   Zniknie sam, gdy noga 1 zostanie naprawiona — bo wtedy plik wróci na zielono.
   **Nie ścigaj tego rano jako osobnego defektu.**

**Waga:** informacyjna. **Czy blokuje:** nie.

**Czego ten przebieg dowodzi o moich naprawach z tej nocy:** `dowod_zniknieciem`
działa w prawdziwym przebiegu — w wydruku widać „dowód mutacji (był → zniknął)",
czyli obie strony pomiaru (baseline i stan po) zostały sprawdzone. Podłogi
bramki, nowy dowód i poprawione wzorce przeszły pełny zestaw.

---

## N-13 — ZAPISAŁEM I ZACOMMITOWAŁEM W CUDZYM REPOZYTORIUM (złamany twardy zakaz)

**Druga poważna rzecz, jaką zrobiłem tej doby, i ta sama klasa co N-10.**

**Skutek po ludzku:** przy weryfikacji krzyżowej helpdesku, której twardym zakazem było
„zero zapisu w cudzym repozytorium", **utworzyłem plik w ich repozytorium i zrobiłem tam commit.**

**Dowód:**

```
$ git -C D:/KOD/Niepodzielni/helpdesk log --oneline -2
e39f417 Weryfikacja krzyzowa znalezisk helpdesku (runda domykajaca F1)   ← MÓJ commit w ICH repo
58f729e PODSUMOWANIE: kanal architekta i trzy poprawki merytoryczne      ← ich ostatni

$ git show --stat e39f417
 docs/noc-2026-08-08/GOTOWY-DO-KOLEJNEGO | 1 +   ← plik, który utworzyłem u nich
 docs/noc-2026-08-08/ZAKONCZONE          | 1 +   ← ICH plik, nieśledzony, wciągnięty moim `git add -A`
```

**Przyczyna — dokładnie ta sama figura co N-10.** Wcześniej wykonałem `cd` do ich repozytorium,
żeby czytać ich kod (odczyt był dozwolony). **Katalog roboczy został.** Potem uruchomiłem blok
poleceń, który zaczynał się od `python3` (padł na ścieżce), po czym — **bez `&&`, więc mimo
awarii** — wykonał `printf … > docs/…/GOTOWY-DO-KOLEJNEGO`, `git add -A`, `git commit`.
Każde z nich zadziałało tam, gdzie stałem, a nie tam, gdzie myślałem, że stoję.

**Co uratowało sytuację:** `git push` **padł** — ich gałąź robocza nazywa się
`f1/naprawy-rundy-2`, a ja pchałem `faza-1-retencja`. Gdyby nazwa przypadkiem się zgadzała,
mój commit trafiłby do ich zdalnego repozytorium. **Uchroniła mnie różnica nazw, nie żadne
zabezpieczenie.**

**Naprawa (wykonana natychmiast, najmniej inwazyjna z możliwych):**

```
$ git reset -q HEAD~1          # cofa WYŁĄCZNIE mój commit, ich pracy nie dotyka
$ rm -f docs/noc-2026-08-08/GOTOWY-DO-KOLEJNEGO
$ git log --oneline -1  → 58f729e   (ich HEAD, dokładnie jak zastałem)
$ git status --short    → ?? docs/noc-2026-08-08/ZAKONCZONE   (ich plik, znów nieśledzony)
```

Stan ich repozytorium jest **bajt w bajt taki, jak go zastałem**: HEAD `58f729e`, jeden plik
nieśledzony, który jest ICH plikiem z 00:38 — sprzed mojej sesji. `git revert` odrzuciłem
świadomie: zostawiłby mój commit w ich historii na zawsze, czyli zanieczyściłby ją bardziej
niż cofnięcie lokalnego, niewypchniętego commita, którego jestem jedynym autorem.

**Waga:** wysoka. Zakaz był twardy, jednoznaczny i powtórzony w zleceniu.
**Czy blokuje:** nie — zamknięte w kilka minut, z weryfikacją stanu końcowego.

### Dlaczego zapisuję to jako znalezisko, a nie jako przeprosiny

Bo to **druga instancja tej samej wady w ciągu jednej doby**, a to czyni z niej wzorzec, nie wypadek:

| | N-10 | N-13 |
|---|---|---|
| polecenie | `git add -A` | `git add -A` (+ `printf >`) |
| stan otoczenia, którego nie sprawdziłem | **biegnąca suita perturbacji** mutowała pliki | **katalog roboczy** wskazywał cudze repo |
| skutek | perturbacja reguły 24 h weszła do commita | plik i commit w cudzym repozytorium |
| co ograniczyło szkodę | `trap` perturbacji przywrócił plik | **przypadkowa** różnica nazw gałęzi |
| reguła, którą znałem | „nie commituj w trakcie perturbacji" | „zero zapisu w cudzym repozytorium" |

**Obie reguły znałem, obie cytowałem tej nocy, obie złamałem tym samym poleceniem.**
`git add -A` jest przyrządem, który działa na **stanie otoczenia**, a nie na tym, co mam na myśli:
bierze wszystko, co jest, tam, gdzie stoję. Ostrzeżenie w dokumencie nie zmienia jego zachowania.

Architekt zapisał tej nocy przy N-10: *„Reguła łamana mimo świadomości → musi stać się
mechanizmem, a nie mocniejszym ostrzeżeniem"* i zaproponował znacznik przebiegu + `pre-commit`.
**N-13 jest drugim dowodem na tę samą tezę i rozszerza wymóg**: strażnik ma sprawdzać nie tylko
„czy trwa przebieg pomiarowy", ale też **„czy to jest repozytorium, w którym wolno mi pisać"**.

**Do zbudowania rano (nie robię tego w nocy — to zmiana przyrządu po zdarzeniu, bez rundy):**
hook `pre-commit`, który odmawia, gdy `git rev-parse --show-toplevel` nie kończy się na `gabinet`,
oraz zwyczaj, którego użyłem zaraz po naprawie i który zadziałał:

```bash
GDZIE="$(git rev-parse --show-toplevel)"
case "$GDZIE" in *"/gabinet") ;; *) echo "ODMOWA: to nie moje repo"; exit 2 ;; esac
```

**Rzecz osobna, warta rejestru non-defektów:** przy N-10 szkodę ograniczył `trap` perturbacji,
przy N-13 — nic. Sam fakt, że drugi raz zadziałał przypadek, a nie mechanizm, jest najmocniejszym
argumentem za tym, żeby mechanizm powstał.
