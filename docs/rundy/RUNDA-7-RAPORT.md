# RUNDA 7 — RAPORT NIEZALEŻNEGO WERYFIKATORA

**SHA mierzone:** `551c0c8c1e425e469a7f9f3b2189ba0bdd337877` (gałąź `faza-1-retencja`, „551c0c8").
**Zlecenie:** `docs/ZLECENIA/ZLECENIE-056.md`. **Data pomiaru:** 12.08.2026.
**Stawka:** F1 i F0 zamykają się WYŁĄCZNIE rundą z zerem znalezisk (D-2026-08-07-16).

## Werdykt jednym zdaniem

**Faza NIE zamyka się w tej rundzie. Dziewięć znalezisk** — wszystkie zmierzone,
każde z kontrolą pozytywną i negatywną instrumentu. Bramka jest zielona i zgodna
z deklaracją autora co do liczb testów; znaleziska dotyczą **kontroli, które świecą
zielono nad luką**, oraz **rozjazdów opisu ze stanem zmierzonym** (te ostatnie są
znaleziskiem wprost z brzmienia zlecenia).

---

## 0. Środowisko pomiaru — dlaczego NIE `gabinet-perturbacje`

Zlecenie wskazuje stos `gabinet-perturbacje`. **Zmierzyłem, że ten stos montuje
DRZEWO ROBOCZE dewelopera**, nie klon:

```
docker inspect gabinet-perturbacje-app --format '{{range .Mounts}}...{{end}}'
  bind /run/desktop/mnt/host/d/KOD/Niepodzielni/gabinet -> /srv/gabinet
```

Weryfikacja usługi stanowej na stosie montującym cudze drzewo to — słowami
`WYTYCZNE-PRACY.md` — „fikcja: klon mierzy CUDZY stan". Postawiłem więc dwa
w pełni izolowane klony i stosy:

| klon | katalog | projekt compose | porty (HTTP/PG/Redis) | mount |
|---|---|---|---|---|
| r7 | `d:/tmp/gabinet-r7` | `gabinet-r7` | 8096 / 55445 / 56392 | `D:\tmp\gabinet-r7` |
| r7a | `d:/tmp/gabinet-r7a` | `gabinet-r7a` | 8095 / 55446 / 56393 | `D:\tmp\gabinet-r7a` |

Oba na `git checkout 551c0c8`, drzewo po każdym pomiarze wracało do `0 zmian`
(sprawdzane `git status --porcelain`). Po rundzie: `down -v` obu + `docker stop`
`gabinet-perturbacje` (był podniesiony przed rundą, zgaszony wg `SRODOWISKO.md`).
Stos `gabinet` (dev) zostawiony — wymaga go bramka.

---

## 1. Pełna bramka — wynik LICZBOWY, nie „przeszło"

Przebieg OD ZERA na czystym klonie r7 (`skrypty/bramka.sh --projekt gabinet-r7`):

```
BRAMKA OK — 22 kroków, 0 nieudanych              (kod wyjścia 0)
Tests: 2 skipped, 267 passed (2026 assertions)
WYKONANO 267 testów (podłoga: 265)
sprawdzono 2026 asercji (podłoga: 2024)
pominięte (nie liczą się do podłogi): 2          (oba: TwierdzeniaKomentarzyTest)
Pint: PASS 99 files
Larastan (level max): [OK] No errors
gitleaks: no leaks found (147 commits, 3.30 MB, 19.8 s)
czas testów: 37 s
```

Deklaracja autora (`ZLECENIE-054` §1: 267 / 2026 / podłogi 265) — **POTWIERDZONA
co do liczby**. Deklarowana podłoga asercji 265 w zleceniu to omyłka pisarska:
zmierzone `MINIMUM_ASERCJI=2024` (`skrypty/podlogi.sh:78`), `MINIMUM_TESTOW=265`
(`:71`).

**CI (jedyne maszynowo wymuszone czyste)** — run `31593549239`, head `97a11b4`,
`success`, 3m56s: `WYKONANO 267 testów`, `2026 asercji`, `BRAMKA OK — 21 kroków`
(21, bo `--zostaw` pomija krok sprzątania). Adres LAN w kroku [16] rozstrzygnięty
(`10.1.0.155 … ODMAWIA`), więc kontrola ekspozycji nie została „milcząco pominięta".

**Warunek z `ZLECENIE-054` §1 spełniony:** `git diff --stat 551c0c8..HEAD --
backend/ skrypty/` → **puste**. Cały delta 551c0c8..97a11b4 to jeden plik
`docs/ZLECENIA/ZLECENIE-054.md` (+165). Commit po zamrożonym SHA istnieje, ale
nie dotyka kodu — nie jest znaleziskiem.

---

## 2. Weryfikacja zamknięć rundy 6 — co się broni

Sprawdzone pomiarowo i **trzyma się** (kontrola pozytywna + negatywna):

- **R6A-4 / klasa 5 (denylista→allowlista prymitywów):** zbiór zakazany pochodzi
  z `get_defined_functions()`, allowlista wiąże funkcję z zakresem plików.
  Perturbacja `hash('sha256',$h)` w nowym pliku zapala kontrolę. **Broni się.**
- **R6B-14 (atomowość JWKS):** `WzmacniaczZadanTest` przełącza `cache.default→redis`
  i mierzy `Cache::add` na PRAWDZIWYM sterowniku. **Broni się.**
- **R6B-9 (mapa `sid→sesje`):** przeniesiona do bazy (`DB::table('sesje_sso')`),
  `Cache::flush()` jej nie rusza — bo już nie mieszka w cache'u. **Broni się**
  (uwaga o hartowaniu w §5).
- **R6A-3 wąskie gardło (typ fabryki + zakaz Reflection/unserialize):** kontrola
  pozytywna (`zMagazynu` prywatna, pusta sesja→null) i negatywna działają.
  **Broni się** dla wektora, który pokazał weryfikator (uwaga o zasięgu: R7-1).
- **N-14 (ślad wylogowania jako `www-data`):** na żywym kontenerze katalog
  `storage/slad-wylogowania` należy do `82:82` (www-data), zapis dochodzi.
  Mechanizm **broni się** — ale jego KONTROLA środowiskowa nie (R7-9).

---

## 3. Pozycja jawna ataku D-1 — odtworzona + trzecia siatka z dowodem

### (a) Atak odtworzony na czystym klonie

Mechanizm własnych haseł **bez funkcji kryptograficznej** — porównanie `===`
wartości z żądania z sekretem w kolumnie **już zadeklarowanej**
(`users.nazwa_wyswietlana`), na trasie **już zadeklarowanej** (`GET /`):

```
DOWÓD MUTACJI (odczyt zwrotny): trasa GET / niesie mechanizm hasła = True
BrakWlasnychHaselTest  → 9 passed        (obie siatki §2 ŚLEPE)
Larastan (level max)   → No errors
```

Potwierdzone: obie siatki deklaratywne (allowlista prymitywów + allowlista
schematu/tras) przepuszczają ten wektor. Zgodne z opisem autora — **znane, część
zakresu, nie znalezisko rundy.**

### (b) Trzecia siatka — POMIAROWA, nie deklaratywna — z perturbacją zapalającą

Zbudowałem prototyp siatki, która nie pyta „czy w kodzie JEST krypto/kolumna",
tylko o **SKUTEK**: czy jakakolwiek trasa, w odpowiedzi na sekret podany po HTTP,
zwraca uwierzytelnioną odpowiedź / ustanawia tożsamość — **bez przejścia przez
OIDC** (§2: „logowanie wyłącznie przez Konta Niepodzielni"). Skutek jest mierzalny
i pierwotny wobec sposobu (obejmuje `===`, `hash`, cokolwiek).

Pomiar rozstrzygający (ta sama logika siatki, klon r7a, żywy stos):

```
KOD CZYSTY (bez ataku):   przegląd wszystkich tras → 0 trafień (siatka zielona)
KONTROLA POZYTYWNA:       stan startowy /auth/ja → 401 (odczyt bazowy)
Z MUTACJĄ D-1 na GET /:   TRAFIENIE: GET /  s1=true  odp=200 {"zalogowany":true}
                          — a BrakWlasnychHaselTest RÓWNOLEGLE: 9 passed
```

**Siatka pomiarowa zapala się dokładnie na ataku, który obie siatki deklaratywne
przepuszczają.** To jest dowód kierunku, o który prosiło zlecenie — perturbacja
zapalająca kontrolę, nie zalecenie na papierze.

**Ograniczenia prototypu (mówię wprost):** (1) atakujący wybiera nazwę parametru
— prototyp próbuje baterii nazw, ale nie jest to dowód pokrycia; (2) mierzy sygnał
`{"zalogowany":true}` w odpowiedzi trasy albo w `/auth/ja` — mechanizm, który
mintuje sesję bez echa w odpowiedzi, wymknąłby się tej postaci. **Postać docelowa,
odporniejsza:** szpieg na zapisie klucza sesji `konta` z atrybucją do trasy —
asercja, że JEDYNĄ trasą kiedykolwiek zapisującą tożsamość jest `/auth/callback`.
Mierzy WHETHER, nie HOW, więc łapie każdy prymityw. Prototyp (plik nie zapisany
do repo) w załączonych pomiarach; wdrożenie należy do KOD-F1.

---

## 4. Znaleziska — każde zmierzone

Waga: KRYTYCZNA (luka §2/§10 eksploatowalna) · WYSOKA · ŚREDNIA · NISKA.
Żadne z poniższych nie jest dziś **eksploatowalne z zewnątrz** — wszystkie są
defektami KONTROLI (zielone nad luką) albo rozjazdami opisu ze stanem. To nie
łagodzi ich: reguła zbieżności zamyka fazę tylko przy ZERZE znalezisk, a „kontrola
świecąca zielono nad usuniętym mechanizmem" jest w tym repozytorium klasą, która
już raz kosztowała (R6A-4, R6A-11).

---

### R7-1 (WYSOKA) — `WaskieGardloTozsamosciTest` NIE liczy plików sięgających po tożsamość przez fasadę; egzekutor D-2026-08-08-24 zawyża własny zasięg

**Co zmierzone.** Test deklaruje (`WaskieGardloTozsamosciTest.php:191-193`):
*„pisarzem, czytelnikiem i kasującym jest JEDEN komponent (`SesjaKonta`) … Trzeci
plik sięgający po tożsamość zapala bramkę."* Wzorzec (`:199`):
`/TozsamoscSesji::|['"]konta['"]\s*(?:,|\)|=>|;)/` — **nie zna `SesjaKonta::`**.

Pomiar (php w kontenerze, klon r7a):
```
A. trafione przez wzorzec:                SesjaKonta.php, TozsamoscSesji.php
B. realnie sięgają po tożsamość (fasada): LogowanieController, SprawdzUniewaznienie, OdswiezanieSesji
C = B \ A:                                3 pliki produkcyjne NIEWIDZIANE przez „allowlistę"
```

**Kontrola negatywna (perturbacja klasy).** Dopisałem CZWARTY plik produkcyjny
`app/Http/Controllers/PanelController.php` z `SesjaKonta::odczytaj($request)`
(dowód mutacji: `grep -c` → 1):
```
przez fasadę SesjaKonta::odczytaj  → WaskieGardloTozsamosciTest: 5 passed   (NIEZAUWAŻONY)
```
**Kontrola pozytywna.** Ten sam plik przez `TozsamoscSesji::zZadania($request)`:
```
                                   → 1 failed   (instrument żyje)
```

**Klasa (ZLECENIE-056):** instrument pytający o co innego, niż twierdzi. To
**R6A-12 nienaprawione dla ścieżki fasady** — a `SesjaKonta::` jest właśnie
zamierzoną, jedyną legalną drogą. Test reklamuje się jako egzekutor D-24
(`:186-190`), a nie egzekwuje twierdzenia, które nazywa.

**Odtworzenie:** dopisz plik `app/**.php` z `SesjaKonta::odczytaj(...)` →
`pest tests/Feature/WaskieGardloTozsamosciTest.php` → zielone.

---

### R7-2 (WYSOKA) — kontrola domyślnego szyfrowania sesji ślepa na literał NAPISOWY (nawrót R6A-6, §10/RODO)

**Co zmierzone.** `SesjaBezJawnychDanychTest` (test „ma szyfrowanie sesji włączone
DOMYŚLNIE — czytane z TREŚCI pliku") używa `Zrodlo::bezKomentarzy()`, który usuwa
`T_COMMENT`/`T_DOC_COMMENT`, ale **nie `T_CONSTANT_ENCAPSED_STRING`**.

Perturbacja (klon r7a, dowód mutacji odczytem zwrotnym): domyślna `false`
+ literał w KLUCZU TABLICY (napis, nie komentarz):
```php
'notatka_migracji' => "BYLO: 'encrypt' => env('SESSION_ENCRYPT', true)",
'encrypt' => env('SESSION_ENCRYPT', false),
```
```
POMIAR:            SesjaBezJawnychDanychTest → 5 passed   (mutacja NIEZŁAPANA)
KONTROLA POZYTYWNA: false BEZ literału w napisie → 1 failed (właściwa asercja)
```

**Klasa:** test dowodzący ZNACZNIKA zamiast skutku; kontrola zaspokojona napisem.
R6A-6 zamknięto dla „literału w komentarzu"; ta sama klasa wraca dla „literału
w tekście". Naprawa dokładnie pokazanej instancji, nie klasy.

---

### R7-3 (WYSOKA) — blokada wysyłki poczty (§10) BEZ egzekutora WPIĘCIA; `SzkieletTest` mierzy `phpunit.xml`, nie mechanizm

**Co zmierzone.** `SzkieletTest` „nie pozwala poczcie wyjść na zewnątrz przy
włączonej blokadzie" (komentarz: *„sprawdza SKUTEK po starcie aplikacji, nie samą
obecność ustawienia"*). Perturbacja: `return;` na wejściu
`AppServiceProvider::zablokujWysylkePozaProdukcja()` — mechanizm martwy (dowód
zwrotny: „MUTACJA R7"):
```
POMIAR:  SzkieletTest → 8 passed ;  PEŁNA SUITA → 267 passed   (WPIĘCIE usunięte NIEZAUWAŻONE)
KONTROLA POZYTYWNA:  psucie CZYSTEJ funkcji sterownikPoczty() → 7 failed (Unit/BlokadaWysylkiTest)
```

**Dlaczego kontrola jest pusta.** `config/mail.php` czyta `env('MAIL_MAILER','log')`;
`phpunit.xml:54` wymusza `MAIL_MAILER=array`, a `.env.bramka` ma `MAIL_MAILER=log`.
Oba to sterowniki niewysyłające, więc gałąź podmieniająca w mechanizmie **nigdy
nie wchodzi** — test potwierdza `array`/`log` z konfiguracji, nie działanie
podmiany. Mechanizm dziś ŻYJE (zmierzone: `MAIL_MAILER=smtp` → `mail.default=log`),
ale nic nie broni przed jego usunięciem.

**Klasa:** twierdzenie (nazwa/komentarz) o skutku bez egzekutora skutku; cicha
podmiana sterownika (teza dotyczy blokady, suita biegnie tam, gdzie blokada
i tak nie musi działać). To R6A-11 (mechanizm bez pokrycia uruchomienia) dla §10.

---

### R7-4 (WYSOKA) — druga warstwa `zdekodowaneLadunki()` to KOD MARTWY dla wejścia, dla którego ją dopisano; perturbacja „ZASZYFROWANY" zielona z niewłaściwej przyczyny

**Co zmierzone.** `backend/tests/Pest.php` — druga warstwa (zwykły base64 nad
całością) stoi PO wczesnym `return ''` z pierwszej warstwy:
```php
if (preg_match_all('/…\.…\.…/', $tresc, ...) === 0) { return ''; }   // wymaga DWÓCH kropek
```
`base64_encode($idToken)` nie ma kropek → wczesny return → warstwa druga **nigdy
się nie wykonuje**. Pomiar (php w kontenerze, dosłowna kopia funkcji):
```
NOGA 1 (surowy JWT):        e-mail odzyskany = true   → asercja czerwona
NOGA 2 (base64_encode(JWT)): e-mail odzyskany = false  → ZIELONA (niezłapana)
sama DRUGA WARSTWA na tym wejściu = false               (kontrola: warstwa jest martwa)
```

Komentarz `Pest.php:158-165` twierdzi wprost, że warstwa druga to łapie —
**nieprawda wobec kodu**. Konsekwencja dla przyrządu: perturbacja
`p_id_token_w_sesji` noga 2 (`--przyczyna "ZASZYFROWANY"`) zalicza się **z innej
przyczyny niż badana** (wyjątkiem `Crypt::decryptString`, nie odzyskiwalnością
e-maila) — P25 po stronie zieleni. Dług nazwany w komentarzu `perturbacje.sh:1196`,
ale przyczyna wskazana tam jest już nieaktualna (twierdzi „funkcja zwraca pusty
napis" jako opis — a to opis, po którym dodano martwą warstwę).

**Skutek dla systemu:** dziś id-token JEST szyfrowany (`Crypt`), więc realnego
wycieku nie ma. Wada jest w KONTROLI: asercja „e-mail nieodzyskiwalny PO
zdekodowaniu" jest dekoracyjna — gdyby ktoś zamienił szyfrowanie na samo
kodowanie, wyłapałby to tylko kierunek odwrotny (`decryptString` rzuca), a nie
asercja RODO art. 9, która się tym reklamuje.

---

### R7-5 (ŚREDNIA) — wyjątek gitleaks NIE jest zawężony do trzech commitów; D-4 opisuje stan, którego nie ma

**Co zmierzone.** `.gitleaks.toml` blok przynęty: `targetRules=["generic-api-key"]`
+ `regexes=['''aGVsbG8…''']` + `commits=["31727fb215","f24dfec","4ad5728"]`,
**bez `condition="AND"`**. gitleaks łączy kryteria allowlisty przez OR, więc
`regexes` zwalnia wartość **wszędzie**, a `commits` niczego nie zawęża (poszerza).

Pomiar rozstrzygający (klon r7, nowy commit `2385c59` POZA trójką zwolnionych,
skan dokładnie jak bramka):
```
TYLKO bait w nowym commicie 2385c59  → no leaks found   (KOD 0)   ← zwolniony mimo że poza trójką
KONTROLA POZYTYWNA: inny sekret w tym samym pliku → leaks found: 1 (KOD 1)
```

Opis D-4 (`ZLECENIE-054` §2: *„zawężony do trzech commitów"*) i komentarz
w pliku (`.gitleaks.toml:118`: *„domyka WYLACZNIE HISTORIE"*) są **nieprawdziwe
wobec własnej implementacji**. To rozjazd opisu z rzeczywistością — znalezisko
wprost z brzmienia zlecenia. Wartość jest zmyślona (`hello-world-this-is-a-secret`),
więc praktyczny nadmiar zwolnienia obejmuje tylko ten jeden fałszywy napis, ale
w drzewie roboczym i wszystkich przyszłych commitach, nie w historii.
**Naprawa: jedna linia `condition = "AND"`** (albo `paths` zamiast `commits`).

---

### R7-6 (WYSOKA) — `PLAN-FAZ.md` `CURRENT WORK` niesie trzy twierdzenia nieprawdziwe wobec repozytorium; enforcer `JednoZrodloStanuTest` nie obejmuje wnętrza sekcji stanu

**Co zmierzone** (wszystko na SHA rundy, sprawdzalne bez uruchomienia):

1. `CURRENT WORK`: *„Podłogi bramki: **258 / 2008** (`skrypty/podlogi.sh` — JEDNO
   źródło)"*. Zmierzone w tym samym repo: `MINIMUM_TESTOW=265`,
   `MINIMUM_ASERCJI=2024`. To **nie jest pomiar przebiegu** (do którego stosuje się
   „zmierz je"), tylko twierdzenie o zawartości pliku — fałszywe.
2. `CURRENT WORK`: *„KLASA 7 NIETKNIĘTA. Strażnik `pre-commit` … **NIE POWSTAŁ**."*
   Powstał commit wcześniej: `cc70946` dodaje `skrypty/git-hooks/pre-commit`
   (188 linii) + `StraznikCommitaTest.php` (196 linii). Sekcja stanu wysyła
   następną sesję do zbudowania czegoś, co już stoi.
3. `CURRENT WORK`: *„Bramka: ZIELONA — 22 kroków"*, a komunikat commita `551c0c8`
   otwiera się od *„Bramka po straznika: CZERWONA, 2 kroki z 22"*.

`JednoZrodloStanuTest` reklamuje się (`PLAN-FAZ.md:44`) jako pilnujący „liczb stanu
bez kotwicy" — ale mierzy je **wyłącznie POZA** sekcją `CURRENT WORK`
(`preg_replace` wycina sekcję stanu, potem skanuje resztę). Trzy powyższe leżą
WEWNĄTRZ sekcji, więc przechodzą przez zieloną bramkę.

**Klasa:** plik stanu jest przyrządem najgroźniejszym (WYTYCZNE); kontrola o zasięgu
węższym, niż deklaruje. Uwaga: te napisy stoją na SHA rundy; commit po nim
(`ZLECENIE-054`) `PLAN-FAZ.md` nie tknął, więc trwają też na HEAD.

---

### R7-7 (WYSOKA) — `perturbacja-odwrotna.sh` i `odczyt-przyczyn.py` mutują/mierzą DRZEWO DEWELOPERA; kontrola R6B-16 obejmuje tylko `perturbacje.sh`

**Co zmierzone** (odczyt kodu na klonie r7):

```
skrypty/perturbacja-odwrotna.sh:93:  dc() { docker compose "$@"; }
  --env-file: 0   -p: 0   GABINET_PREFIX: 0   znacznik-przebiegu: 0
```
Bez `-p`/`--env-file`/prefiksu projekt domyślny to `gabinet` (stos dewelopera).
Skrypt mutuje `backend/app`, cofa przez `git checkout --` (nie `cp` z kopii — to
punkt 4 z listy sześciu złamań w `StraznikCommitaTest.php:16`) i nie zakłada
`.przebieg-pomiarowy`, więc strażnik commita go nie chroni.

```
skrypty/odczyt-przyczyn.py:25:  KORZEN = 'D:/KOD/Niepodzielni/gabinet'   (ścieżka absolutna dewelopera)
skrypty/odczyt-przyczyn.py:76:  polecenie = 'docker compose ' + …        (gubi -p/--env-file/GABINET_*)
```
To **jedyny odczyt DYNAMICZNY** `--przyczyna` (jedyny, który widziałby degeneracje
z R7-8) — i mierzy CUDZE drzewo na zabronionym projekcie (`perturbacje.sh:81`
odmawia projektu `gabinet`).

Kontrola R6B-16 (`KlamraSkryptowTest`) sprawdza `--env-file` **wyłącznie
w `perturbacje.sh`**. Najnowszy skrypt mutujący i jedyny odczyt dynamiczny
wymykają się kontroli powołanej po to, by tę klasę zamknąć.

**Klasa:** kontrola dzieląca mechanizm ze swoim przedmiotem / bezpieczeństwo
pomiaru (WYTYCZNE: „biegnąca suita destabilizuje środowisko", „czysty klon
dzielący instancję to fikcja"). Uwaga: to NARZĘDZIA, nie kroki bramki; nie
psują wyniku bramki, ale unieważniają wynik tych narzędzi i grożą drzewu dewelopera.

---

### R7-8 (ŚREDNIA) — zapadka `--przyczyna` niedolicza degeneracji; D-2 zaniża dług (5 z 15, nie 2) i wskazuje niewłaściwą przyczynę

**Co zmierzone** (odczyt kodu; dwa niezależne audyty statyczne zbieżnie). Zapadka
`PrzyczynyPerturbacjiTest` liczy 2 zdegenerowane wzorce (`:1130 "ACCESS TOKENU"`,
`:1208 "ZASZYFROWANY"`), sufit=2. Poza jej zasięgiem są **co najmniej 3 kolejne**:

- `perturbacje.sh:604`, `:625` — `--przyczyna "BrakWlasnychHasel"` jest **nazwą
  KLASY/PLIKU** uruchamianego (`pest … BrakWlasnychHaselTest.php`), którą domyślny
  raporter Pest wypisuje w nagłówku KAŻDEGO przebiegu. Zapadka porównuje wzorzec
  tylko z nazwami `it()/test()` (`nazwyTestow()`), nigdy z nazwą klasy → nie widzi.
- `perturbacje.sh:1518` — `--przyczyna "Bramki|marker"` to **alternatywa ERE**
  dopasowywana `grep -qiE`; gałąź `marker` jest fragmentem nazw testów
  uruchamianych `--filter="marker"`. Zapadka porównuje CAŁY literał `"bramki|marker"`
  przez `str_contains` → nie rozbija alternatywy → nie widzi.

Opis D-2 (*„dwie allowlisty … blokuje brak komunikatów asercji w dwóch testach"*)
zaniża dług i wskazuje niewłaściwą przyczynę: dla trzech powyższych blokerem nie
jest brak komunikatu, tylko ślepota parsera zapadki.

**Czego tu NIE zmierzyłem uruchomieniem:** nie odpaliłem pełnego `perturbacje.sh`
(dzieli stos, mieli bazę) ani nie zobaczyłem surowego wyjścia Pest dla tych
scenariuszy. Twierdzenie o nagłówku klasy opiera się na braku własnego printera
w `phpunit.xml` i na formacie domyślnego raportera Pest — nie na wydruku. Dlatego
waga ŚREDNIA, nie WYSOKA.

---

### R7-9 (ŚREDNIA) — kontrola środowiskowa N-14 to `str_contains` na surowej treści `entrypoint.sh`, bez filtra komentarzy

**Co zmierzone.** `TrwaloscMagazynowTest` „N-14 SRODOWISKO" robi
`str_contains($entrypoint, 'chown -R www-data:www-data storage')` na
`file_get_contents(entrypoint.sh)` — **surowej treści**, bez `Zrodlo`/filtra `#`.

```
KONTROLA POZYTYWNA:  usunięcie napisu z entrypoint.sh → 1 failed  (instrument żyje)
```
Dedukcyjnie pewne: **zakomentowany** chown (`# chown -R www-data:www-data storage …`)
nadal zawiera szukany podciąg → `str_contains`=true → test zielony przy MARTWYM
chownie (bash nie wykonuje komentarza → katalog śladu powstaje leniwie u pierwszego
piszącego → N-14 wraca). To ta sama klasa R6A-6, którą pięć innych plików filtruje
przez `Zrodlo::bezKomentarzy()`, a ten nie.

**Uczciwie:** kontrolę pozytywną (usunięcie→czerwień) zmierzyłem; wariantu
„zakomentowane→zielone" nie odpaliłem osobno (dowód jest dedukcyjny z kodu
`str_contains` + treści pliku).

---

## 5. Uwagi o hartowaniu (NIE znaleziska — kierunki dla KOD-F1)

- **R6B-9:** dziś mapa `sid→sesje` jest w bazie i to ją broni. Gdyby ją kiedyś
  przenieść do `Cache::store('redis')` (nazwany magazyn), `Cache::flush()`
  w testach jej nie tknie, a `cache:clear` na produkcji tak — dziś NIE dotyczy.
- **Kontrole negatywne na syntetycznym napisie** (`KlamraSkryptowTest`,
  `ObietniceKomentarzyTest`, `SondaPortowTest`, `BrakWlasnychHaselTest`) trzymają
  DRUGĄ kopię wzorca, którego pilnują — dowodzą, że regex działa, nie że łapie
  naruszenie w repo. Wzorzec do jednej stałej wołanej przez oba testy zamknąłby tę
  klasę. (Nie podniosłem do rangi znaleziska — kontrole główne działają; to dług
  konstrukcyjny.)
- **`BramkiTest` „zna 7 ról realmu"** i **`SzkieletTest` timezone/queue** porównują
  konfigurację z drugą kopią tej samej listy / ze stałą wymuszoną przez `phpunit.xml`
  — nie wykryją rozjazdu z realmem ani zmiany domyślki. Niska waga, do rozważenia.

---

## 6. CZEGO NIE SPRAWDZIŁEM (sekcja obowiązkowa)

1. **Nie uruchomiłem pełnego `perturbacje.sh`** (30 scenariuszy) — dzieli stos
   i mieli bazę, a `gabinet-perturbacje` montuje drzewo dewelopera. R7-8 opiera się
   na odczycie kodu i dwóch audytach statycznych, nie na wydruku perturbacji.
2. **Nie uruchomiłem `odczyt-przyczyn.py` ani `perturbacja-odwrotna.sh`** — z tego
   samego powodu (montują/mierzą drzewo dewelopera). R7-7 to odczyt kodu.
3. **R7-8** zależy od formatu domyślnego raportera Pest (nagłówek klasy) — nie
   zobaczyłem surowego wyjścia dla tych scenariuszy.
4. **R7-9** wariant „zakomentowane→zielone" — dowód dedukcyjny, nie odpalony osobno
   (kontrolę pozytywną odpaliłem).
5. **Nie weryfikowałem tez kontraktowych** wobec `konta/`, `hub/` — nie mam tych
   repozytoriów w zasięgu; cytaty „§4.4", „B7", „B8" przyjąłem za wierne.
6. **Nie sprawdzałem współbieżności** (CLAUDE.md §6, test 100 równoczesnych żądań)
   — w suicie go nie ma; `ModelDanychTest` mierzy ograniczenie unikalne dwoma
   zapisami sekwencyjnymi, nie współbieżnością. To zakres F3 (jawnie odroczone).
7. **Nie audytowałem** treści `docs/specyfikacja/`, `WYTYCZNE-PRACY.md`, ~50 plików
   `docs/ZLECENIA/` poza cytatami do konkretnych znalezisk.
8. **Migracje/schemat** poza tym, co mierzy bramka — nie porównywałem
   `OCZEKIWANY_SCHEMAT` z rzeczywistym schematem inaczej niż przez zielony test.
9. **Prototyp trzeciej siatki (§3b)** ma znane ograniczenia (nazwa parametru; sygnał
   po echu) — jest dowodem kierunku, nie gotową kontrolą.

---

## 7. Zakres pokryty — dla jawności

Zmierzone: pełna bramka OD ZERA (22 kroki) na czystym klonie + potwierdzenie w CI;
warunek zamrożenia SHA; 5 zamknięć rundy 6 z kontrolą pozytywną i negatywną;
atak D-1 (a) odtworzony, (b) trzecia siatka z perturbacją zapalającą; własne
poszukiwanie w klasach z rund 3–6 i zgodność z CLAUDE.md §2, §5, §10, §14.
Trzy niezależne audyty statyczne (perturbacje, suita testowa, kod vs CLAUDE.md 1–15)
plus 12 własnych pomiarów rozstrzygających na dwóch izolowanych klonach.

**Faza F1/F0 pozostaje OTWARTA — dziewięć znalezisk. Runda nie kończy się zerem.**
