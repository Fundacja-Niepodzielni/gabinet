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
