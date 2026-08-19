# RUNDA 9 — RAPORT NIEZALEŻNEGO WERYFIKATORA

**SHA mierzone:** `d79dc0c9cd1ba65bce944b53c404fb5dc6386e7d` (gałąź `faza-1-retencja`, „d79dc0c").
**Zlecenie:** `docs/ZLECENIA/ZLECENIE-067.md`. **Data pomiaru:** 18.08.2026.
**Stawka:** F1 i F0 zamykają się WYŁĄCZNIE rundą z zerem znalezisk (D-2026-08-07-16).

## Werdykt jednym zdaniem

**Faza NIE zamyka się w tej rundzie. Pięć znalezisk** — trzy zmierzone ŻYWYM
mechanizmem łamiącym `CLAUDE.md` §2 albo zasadę „sekrety nigdy w plikach", przy
CAŁKOWICIE ZIELONEJ bramce (290/2130 + Larastan + Pint + gitleaks), dwa
dokumentacyjno-zasięgowe. Bramka jest zielona i zgodna z deklaracją autora co do
KAŻDEJ liczby; zamknięcia R8-1, R8-2 i wady własnej §4 bronią się pomiarowo —
każde z kontrolą pozytywną i negatywną przyrządu. Znaleziska nie podważają tych
napraw: leżą **o krok dalej** w tych samych mechanizmach oraz w miejscu, którego
żadna runda dotąd nie ruszyła (`.env.example` × gitleaks × `SekretyTest`).

---

## 0. Środowisko pomiaru — własne izolowane klony, nie `gabinet-perturbacje`

Zgodnie z lekcją rundy 7 (`gabinet-perturbacje` montuje DRZEWO dewelopera) NIE użyłem
tego stosu. Postawiłem własne, efemeryczne klony i stosy; po każdym pomiarze drzewo
wracało do `0 zmian` (`git status --porcelain`), co jest raportowane przy każdym pomiarze.

| klon | katalog | projekt compose | porty (HTTP/PG/Redis) | rola |
|---|---|---|---|---|
| r9  | `d:/tmp/gabinet-r9`  | `gabinet-r9`  | 8130 / 55480 / 56430 | bramka OD ZERA |
| r9p | `d:/tmp/gabinet-r9`  | `gabinet-r9p` | 8132 / 55482 / 56432 | PEŁNE perturbacje |
| r9b | `d:/tmp/gabinet-r9b` | `gabinet-r9b` | 8131 / 55481 / 56431 | stos żywy do sond i mutacji |
| r9v / r9v2 | `d:/tmp/gabinet-r9v(2)` | `gabinet-r9v(2)` | 8140–8141 / 55490–55491 / 56440–56441 | pomiar rozstrzygający (świeży subagent) |

Stos dewelopera `gabinet` (8098/55442/56389) NIETKNIĘTY. **Zakaz commitowania w repozytorium
projektu utrzymany** — jedyne zapisy to ten raport i `ODPOWIEDZ-067.md`. Dwa commity
powstały WYŁĄCZNIE w klonie efemerycznym `r9b` (znalezisko R9-3 wymaga skanu historii;
gitleaks w bramce chodzi w trybie git — tak samo postąpił weryfikator rundy 7 przy R7-5).
Klon po pomiarze cofnięty `git reset --hard d79dc0c` i zweryfikowany: `HEAD=d79dc0c`,
`status` pusty. Nic nie zostało nigdzie wypchnięte.

### Zamrożenie SHA — warunek sprawdzalny z `ODPOWIEDZ-068`

```
git diff --stat d79dc0c..HEAD -- backend/ skrypty/ .gitleaks.toml   →  PUSTO (kod 0)
git diff --name-only d79dc0c..HEAD  →  10 plików, WSZYSTKIE w docs/
                                       (ZLECENIA/*, rundy/RUNDA-8-RAPORT.md)
```

Warunek **SPEŁNIONY**. Czubek `b5cd83f` jest znanym commitem dokumentacyjnym
(zgłoszonym w `ZLECENIE-068`, zatwierdzonym w `ODPOWIEDZ-068`). **Nie jest znaleziskiem.**

---

## 1. Pełna bramka — wynik LICZBOWY

Przebieg OD ZERA na czystym klonie r9 (`bash skrypty/bramka.sh --projekt gabinet-r9`):

```
BRAMKA OK — 22 kroków, 0 nieudanych              (kod wyjścia 0)
Tests: 2 skipped, 290 passed (2130 assertions)
WYKONANO 290 testów (podłoga: 290)               (RÓWNO — bez zapasu)
sprawdzono 2130 asercji (podłoga: 2130)          (RÓWNO — bez zapasu)
pominięte (nie liczą się do podłogi): 2          (oba: TwierdzeniaKomentarzyTest, dług D-3)
Pint:                 PASS 102 files
Larastan (level max): [OK] No errors
gitleaks:             153 commits scanned, ~3.71 MB, no leaks found
czas testów: 49 s
```

**Deklaracja autora (`ODPOWIEDZ-066` §1) POTWIERDZONA co do każdej liczby:**
22/22 · 290/2130 · podłogi RÓWNO 290/2130 · znacznik zdjęty.
Podłogi to JEDNO źródło `skrypty/podlogi.sh` (`MINIMUM_TESTOW=290` w. 81,
`MINIMUM_ASERCJI=2130` w. 88) — odczytane, nie zacytowane.

**Znacznik `.przebieg-pomiarowy`:** po zielonym przebiegu ZDJĘTY (`ls` → „No such file").
Drzewo klonu po bramce: `git status --porcelain` PUSTE.

**Drugi, niezależny przebieg** na klonie r9b (`--zostaw`, więc bez kroku sprzątania):
`BRAMKA OK — 21 kroków, 0 nieudanych`, `290 passed (2130 assertions)` — te same liczby
na innym klonie, innym projekcie compose i innych portach.

**Perturbacje — PEŁNY zestaw, własny stos r9p:**

```
PERTURBACJE OK — 49 kontroli udowodniło, że umie zaświecić czerwono (pominięte: 0)
kod wyjścia 0
zmierzone niezależnie: 32 nagłówki „=== PERTURBACJA", 49 wierszy „✓", 0 wierszy „✗"
drzewo klonu po przebiegu: git status --porcelain PUSTE
```

Zgodne z deklaracją autora (`ODPOWIEDZ-066` §1: 49 kontroli / 32 scenariusze / 0 pominiętych)
i **NIEZGODNE z sekcją stanu `PLAN-FAZ.md`** na tym samym SHA („48 kontroli … PEŁNY zestaw
31 scenariuszy") — to jest znalezisko **R9-5**.

---

## 2. Weryfikacja zamknięć z `ODPOWIEDZ-066` — każde z kontrolą pozytywną I negatywną

Wszystkie trzy zamknięcia **BRONIĄ SIĘ**. Pomiary na żywym stosie r9b, drzewo przywracane
kopią pliku (nie `git checkout`), po każdym `git status --porcelain` PUSTE.

### R8-1 — siatka D-1b mierzy skutek niezależnie od nazwy pola: **BRONI SIĘ**

| kontrola | pomiar | wynik |
|---|---|---|
| POZYTYWNA (nazwa Z baterii) | `p_d1b` w pełnym zestawie perturbacji | ✓ czerwień z badanej przyczyny |
| POZYTYWNA (nazwa SPOZA baterii) | `p_d1b_zaklecie` w pełnym zestawie | ✓ „siatka POMIAROWA wykrywa logowanie pod nazwą pola spoza baterii — kontrola zapaliła się na czerwono (kod 1)" |
| POZYTYWNA (własna, poza perturbacjami) | mechanizm czytający `request()->input('zaklecie')` wstrzyknięty do `LogowanieController::zaloguj()` | `SiatkaPomiarowaTozsamosciTest` → **1 failed, 2 passed** |
| NEGATYWNA (przyrząd) | drzewo czyste | **3 passed**; własny test „KIERUNEK ODWROTNY" dowodzi, że szpieg widzi zapis pod obserwowanym kluczem |
| PRZYRZĄD parsera | własna, niezależna reimplementacja `nazwyPolWejsciowych()` uruchomiona w kontenerze | z mutacją: `zaklecie odkryte? TAK`; bez mutacji: `NIE` |

Twierdzenie z `ODPOWIEDZ-062` §8 zostało w kodzie sprostowane, a perturbacja `d1b`
naprawdę chodzi w dwóch wariantach — sprawdzone w rejestrze scenariuszy bramki
(krok [7]: `scenariusz 'd1b' → p_d1b()`, `scenariusz 'd1b_zaklecie' → p_d1b_zaklecie()`).

### R8-2 — egzekutor §10 pyta REALNEJ aplikacji: **BRONI SIĘ**

| kontrola | pomiar | wynik |
|---|---|---|
| NEGATYWNA (stan czysty) | `BlokadaWysylkiTest` | **3 passed (11 asercji)** |
| POZYTYWNA (wyrejestrowanie providera) | `backend/bootstrap/providers.php` → `return [];` | **1 failed, 2 passed** — asercja w. 136: „Framework NIE ŁADUJE `AppServiceProvider`" |
| PRZYRZĄD (czy sonda nie wypisuje stałej) | ręczna sonda różnicowa w kontenerze, ten sam bootstrap | `blokada=true → provider=TAK mail=log` · `blokada=false → provider=TAK mail=smtp` |

Różnica `log` ↔ `smtp` na tym samym kodzie dowodzi, że sonda patrzy na SKUTEK, a nie
wypisuje napisu. Zatrzymanie na pustym wyjściu / niezerowym kodzie jest w kodzie testu
(dwie asercje przed odczytem pól) — sprawdziłem kształt, nie odtwarzałem wyłączenia `exec`.

### Wada własna §4 — zasięg `WaskieGardloTozsamosciTest` o `routes/`: **BRONI SIĘ (dla tej jednej asercji)**

| kontrola | pomiar | wynik |
|---|---|---|
| NEGATYWNA (stan czysty) | `WaskieGardloTozsamosciTest` | **5 passed (13 asercji)** |
| POZYTYWNA | `session()->put('konta', …)` żywe w `backend/routes/web.php` | **1 failed, 4 passed**, komunikat: `ZNALEZIENI : …, routes/web.php` |

⚠ Ta sama naprawa **NIE objęła drugiej asercji w tym samym pliku** — patrz **R9-4**.

### Znane długi

- **D-3** (`TwierdzeniaKomentarzyTest` poza bramką): potwierdzone — `2 skipped`,
  oba z `Tests\Feature\TwierdzeniaKomentarzyTest` (`WARN` w wyjściu Pest). Bez zmian.
- **D-4** (wyjątek gitleaks): sprawdzony STATYCZNIE — wpis ma `condition = "AND"`,
  `targetRules`, `regexes` i pełne SHA trzech commitów. Nie odtwarzałem kontroli
  z rundy 7 (przynęta w nowym commicie) — patrz §5. ⚠ Przy tym przeglądzie wyszło
  **R9-3**, dotyczące INNEGO wpisu tego samego pliku.

---

## 3. ZNALEZISKA — każde zmierzone

Waga jak w rundach 6–8: KRYTYCZNA (luka eksploatowalna DZIŚ z zewnątrz) · WYSOKA ·
ŚREDNIA · NISKA. Żadne z poniższych nie jest eksploatowalne z zewnątrz na SHA rundy —
na czystym drzewie żadnego z tych mechanizmów NIE MA. Groźny jest **fałszywy spokój**:
kontrola reklamuje pokrycie, którego nie ma, więc pierwsza osoba, która wprowadzi taki
kod, nie dowie się o tym od bramki.

---

### R9-1 (WYSOKA) — siatka D-1b zapala się WYŁĄCZNIE wtedy, gdy sekret jedzie JEDNYM parametrem żądania o wartości równej jednej z trzech znanych; kanoniczny formularz „login + hasło" oraz sekret w NAGŁÓWKU przechodzą przez CAŁĄ bramkę

**Kontekst.** `SiatkaPomiarowaTozsamosciTest` reklamuje się, że „pyta o SKUTEK, nie
o sposób … obejmuje `===`, `hash()`, `sodium_*`, porównanie w bazie, cokolwiek jeszcze
ktoś wymyśli" (docblock, w. 30–46). Runda 8 obaliła jej pierwszą wersję (zamknięta
bateria nazw); naprawa R8-1 dołożyła nazwy czytane z kodu. **Ładunek pozostał jednak
zbudowany tak, że każde żądanie niesie WSZYSTKIE nazwy z JEDNĄ, wspólną wartością**
(`SiatkaPomiarowaTozsamosciTest.php:299-311`): trzy przebiegi, w każdym wszystkie pola
równe odpowiednio `nazwa_wyswietlana`, `email`, `keycloak_sub` założonego konta.

**Dlaczego to nie jest znana granica.** Nagłówek kontroli wymienia TRZY zmierzone granice
(ciasteczko z innej trasy · zapis do innego magazynu sesji · nagłówki HTTP). **Parowanie
dwóch pól o RÓŻNYCH wartościach nie jest wśród nich wymienione**, a jest najzwyklejszym
kształtem mechanizmu, którego zakazuje `CLAUDE.md` §2 — formularzem logowania.
Granica trzecia (nagłówki) JEST wymieniona, ale `ZLECENIE-067` mówi wprost:
„jeśli którąś da się wykorzystać w tym środowisku — to znalezisko, nie znana granica".
Wykorzystałem ją, więc raportuję.

**Dlaczego siatka jest tu JEDYNYM detektorem.** Zapis tożsamości w NOWYM pliku `app/`
albo w `routes/` łapie `WaskieGardloTozsamosciTest` (zweryfikowane w §2). Ale allowlista
tej kontroli jest **listą PLIKÓW, nie instrukcji** — pięć plików ma zgodę na dotykanie
tożsamości bez żadnego ograniczenia treści. Mechanizm umieszczony w jednym z nich
(najnaturalniej: w `LogowanieController`, czyli w pliku od logowania) jest dla niej
niewidzialny **z konstrukcji**. Zmierzone: przy ŻYWYM mechanizmie własnych haseł
w `LogowanieController` `WaskieGardloTozsamosciTest` + `BrakWlasnychHaselTest` = **14 passed**.

**Co zmierzone (stos r9b, drzewo przywracane po każdym wariancie).**
Wszystkie warianty: mechanizm na ZADEKLAROWANEJ trasie `GET /auth/login`, w ZADEKLAROWANYM
pliku z allowlisty, bez nowej kolumny, bez nowej trasy, bez funkcji kryptograficznej.

```
KONTROLA POZYTYWNA — jedno pole, nazwa czytana przez `input()`:
   SiatkaPomiarowaTozsamosciTest        → 1 failed, 2 passed      (siatka DZIAŁA)

WARIANT A — DWA pola o różnych wartościach (`email` + `haslo`, oba Z BATERII):
   SiatkaPomiarowaTozsamosciTest        → 3 passed                (ŚLEPA)
   WaskieGardloTozsamosciTest + BrakWlasnychHaselTest → 14 passed
   PEŁNA SUITA                          → 2 skipped, 290 passed (2130 assertions)
   Larastan (level max)                 → [OK] No errors
   Pint                                 → PASS 102 files

WARIANT H — sekret w NAGŁÓWKU `X-Zaklecie` (granica 3 z nagłówka kontroli):
   trzy siatki razem                    → 17 passed               (ŚLEPE)
   PEŁNA SUITA                          → 2 skipped, 290 passed (2130 assertions)
   Larastan / Pint                      → [OK] No errors / PASS 102 files
```

**DOWÓD, że mechanizm realnie loguje** (jądro HTTP w kontenerze, konto o znanych
wartościach w bazie, `session()->has('konta')` po żądaniu; wiersz kasowany po pomiarze):

```
WARIANT A:
  /auth/login?email=dowod-r9@example.test&haslo=SekretDowoduR9  tozsamosc_w_sesji=TAK  {"sub":"sub-lokalne","role":["pacjent"]}
  /auth/login?email=dowod-r9@example.test&haslo=ZLE-HASLO       tozsamosc_w_sesji=NIE
  /auth/login                                                   tozsamosc_w_sesji=NIE

WARIANT H:
  naglowek POPRAWNY   tozsamosc_w_sesji=TAK  {"sub":"sub-lokalne","role":["pacjent"]}
  naglowek BLEDNY     tozsamosc_w_sesji=NIE
  bez naglowka        tozsamosc_w_sesji=NIE
```

Trzy wiersze na wariant, nie jeden — bo „TAK" przy każdym wejściu znaczyłoby „mechanizm
loguje wszystkich", a to inny świat niż „mechanizm sprawdza poświadczenie". Różnica
POPRAWNE / BŁĘDNE / BRAK dowodzi, że to jest realne własne hasło (`CLAUDE.md` §2).

*(Uwaga uczciwa: `status=500` w sondzie bierze się stąd, że po zapisie tożsamości
`zaloguj()` próbuje zbudować adres IdP, którego w środowisku efemerycznym nie ma.
Zapis do sesji jest wcześniejszy i kompletny; różnicowanie POPRAWNE/BŁĘDNE dowodzi,
że mierzę mechanizm, a nie awarię.)*

**Odtworzenie.** W `backend/app/Http/Controllers/LogowanieController.php`, na początku
ciała `zaloguj()`, wstaw (plus import `use Illuminate\Support\Facades\DB;`, końce linii LF):

```php
$login = $request->input('email');
$sekret = $request->input('haslo');

if (is_string($login) && is_string($sekret) && $login !== '' && $sekret !== '') {
    $konto = DB::table('users')->where('email', $login)->first();

    if ($konto !== null && $sekret === $konto->nazwa_wyswietlana) {
        $request->session()->put('konta', ['sub' => 'sub-lokalne', 'role' => ['pacjent']]);
    }
}
```

→ `./vendor/bin/pest` = 290 passed. Kontrola pozytywna: zamień oba odczyty na jeden
`$request->input('zaklecie')` porównywany z `nazwa_wyswietlana` → siatka CZERWONA.

**Kierunek naprawy (wektor z pomiaru, nie zalecenie na papierze).** Ładunek musi
rozdzielać wartości między nazwy, a nie zlewać ich w jedną: iloczyn kartezjański
(nazwa × wartość) albo — taniej — dodatkowy przebieg, w którym KAŻDA nazwa dostaje INNĄ
wartość z konta, obok dzisiejszych przebiegów „wszystkie = X". Sondowanie nagłówkami jest
osobnym, tanim krokiem: te same nazwy dodatkowo jako nagłówki (`X-…` i wprost).
Dopóki tego nie ma, nagłówek kontroli powinien wymieniać PAROWANIE PÓL jako czwartą
granicę — dziś nie jest nazwane nigdzie.

---

### R9-2 (WYSOKA) — nowy parser `nazwyPolWejsciowych()` nie zna najzwyklejszych sposobów czytania żądania w Laravelu; atak pod nazwą spoza baterii wraca do stanu, który obaliła runda 8

**Kontekst.** To jest miejsce, które autor sam wskazał jako naturalny „trzeci krok"
(`ODPOWIEDZ-066` §7): „`nazwyPolWejsciowych()` to nowy parser — jeśli rozjedzie się
ze źródłami, ładunek cicho wraca do samej baterii". Rozjeżdża się.

**Co zmierzone.** Lista metod czytających jest ZAMKNIĘTA
(`SiatkaPomiarowaTozsamosciTest.php:135-139`) i **nie zawiera** `all`, `only`, `except`,
`json`, `collect`, `str`, `validate`, ani dostępu tablicowego `$request['pole']`.
Każdy z nich jest idiomatycznym sposobem odczytania pola w Laravelu; `validate()` jest
wręcz kanonicznym sposobem obsługi formularza. Dodatkowo klucz w `validate([...])`
i w `only([...])` stoi jako klucz/element tablicy, więc nie zostałby złapany nawet po
dopisaniu samej nazwy metody do listy.

Skala rozjazdu na CZYSTYM drzewie (własna reimplementacja parsera uruchomiona
w kontenerze na `backend/app` + `backend/routes`):

```
4 nazwy: Cache-Control, code, logout_token, state
```

Cztery nazwy z całej aplikacji. Asercja broniąca („liczba nazw > 2" oraz obecność
`code` i `state`) przechodzi przy TAKIM odczycie — czyli nie odróżnia „parser czyta
źródła" od „parser czyta ich ułamek". Nośnikiem czułości pozostaje bateria, dokładnie
jak przed naprawą R8-1.

**Pomiar różnicowy — TEN SAM mechanizm, różnica WYŁĄCZNIE w sposobie odczytu pola:**

```
`$request->input('zaklecie')`             → parser: zaklecie odkryte? TAK
                                            siatka                        → 1 failed  (CZERWONA)

`$wszystko = $request->all();`
`$podane = $wszystko['zaklecie'] ?? null;` → parser: zaklecie odkryte? NIE
                                            siatka                        → 3 passed
                                            trzy siatki razem             → 17 passed
                                            PEŁNA SUITA                   → 290 passed (2130 assertions)
                                            Larastan / Pint               → No errors / PASS 102 files
```

**DOWÓD, że mechanizm realnie loguje** (jak wyżej, jądro HTTP):

```
/auth/login?zaklecie=SekretDowoduR9   tozsamosc_w_sesji=TAK  {"sub":"sub-lokalne","role":["pacjent"]}
/auth/login?zaklecie=ZLE              tozsamosc_w_sesji=NIE
/auth/login                           tozsamosc_w_sesji=NIE
```

**Dlaczego to znalezisko, a nie znana granica.** Docblock mówi: „ŁADUNEK POCHODZI Z PÓL,
KTÓRE KOD NAPRAWDĘ CZYTA" i uzasadnia to zdaniem „mechanizm musi skądś wziąć sekret —
jeśli czyta go pod nazwą `zaklecie`, to `zaklecie` STOI W ŹRÓDLE". Zdanie jest prawdziwe,
ale **wniosek z niego nie**: nazwa stoi w źródle, a parser jej nie widzi. To jest ta sama
klasa co samo R8-1 — instrument reklamujący pokrycie, którego jego implementacja nie daje
— przeniesiona o jedno piętro: z „nazwy z baterii" na „nazwy widziane przez parser".

**Kierunek naprawy.** Albo domknąć listę czytających o pozostałe API `Request`
(`all`, `only`, `except`, `json`, `collect`, `str`, `validate`) I o klucze tablicowe
w ich argumentach, albo — mocniej i bez listy do utrzymania — zbierać WSZYSTKIE literały
napisowe z `app/` i `routes/` pasujące kształtem do nazwy pola. Nadmiarowa nazwa
w ładunku nic nie kosztuje (autor sam to zapisał przy `get`/`header`), pominięta oznacza
ślepotę. Asercja broniąca powinna porównywać liczbę nazw z odczytem drugą, niezależną
drogą — dziś „4" i „40" są dla niej tym samym.

---

### R9-3 (WYSOKA) — sekret wpisany do `.env.example` pod nazwą spoza czterech reguł gitleaksa i spoza czternastu nazw w `SekretyTest` przechodzi CAŁĄ bramkę; `.gitleaks.toml` twierdzi w tej sprawie dwie rzeczy naraz i jedna z nich jest nieprawdziwa

**Kontekst.** To jest twarda zasada, nie kontrola pomocnicza: `CLAUDE.md` („sekrety
wyłącznie w `.env`; nigdy w repo; `.env.example` bez wartości") i `WYTYCZNE-PRACY.md` §7
(„sekrety nigdy w plikach ani w historii"). Żadna runda 1–8 nie mierzyła tego miejsca.

**Co zmierzone.** Ochrona `.env.example` stoi na dwóch mechanizmach i **oba są listami nazw**:

- `.gitleaks.toml` w. 51–69 wyłącza heurystykę `generic-api-key` dla `.env.example`
  (`targetRules = ["generic-api-key"]`, `paths = ['''(^|/)\.env\.example$''']`). Zostają
  cztery reguły własne, a każda z nich jest **przypięta do konkretnej NAZWY zmiennej**:
  `APP_KEY`, `SMSAPI_TOKEN`, `KEYCLOAK_(ADMIN_)CLIENT_SECRET`, `DB_PASSWORD|POSTGRES_PASSWORD`.
- `SekretyTest` sprawdza pustość **zamkniętej listy 14 nazw** (`SekretyTest.php:47-62`).

Zmienna sekretna o dowolnej innej nazwie nie jest objęta ani jednym, ani drugim.
Pomiar na klonie r9b (wpis zacommitowany, bo gitleaks w bramce skanuje historię):

```
.env.example + GOOGLE_CALENDAR_CLIENT_SECRET=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8c07

  gitleaks (dokładnie polecenie kroku [21] bramki)  → 154 commits scanned, NO LEAKS FOUND (kod 0)
  SekretyTest                                       → 3 passed (34 assertions)
      w tym: „it trzyma .env.example bez ani jednej wartości sekretu" ✓
  PEŁNA SUITA                                       → 2 skipped, 290 passed (2130 assertions)

KONTROLA POZYTYWNA — DOKŁADNIE TA SAMA WARTOŚĆ w ścieżce BEZ zwolnienia (docs/probka-r9.md):
  gitleaks                                          → leaks found: 1 (kod 1)
```

Różnica między tymi dwoma przebiegami to **wyłącznie ścieżka pliku**. Skaner działa,
wartość jest wykrywalna — niewidzialność bierze się ze zwolnienia i z listy nazw.

**Dlaczego to znalezisko, a nie znana granica.** Ten sam plik `.gitleaks.toml` zawiera,
trzydzieści wierszy niżej (w. 99–103), komentarz:

> „`.env.example` ma same puste przypisania (pilnuje tego test `SekretyTest`), więc
>  **nie potrzebuje wyjątku — i celowo go nie dostaje. Gdyby ktoś wpisał tam wartość,
>  MA zapalić skan.**"

Wyjątek **stoi w tym samym pliku, wyżej**. Zdanie jest nieprawdziwe wobec własnej
implementacji — dokładnie klasa, którą to repozytorium zamykało już przy R7-5
(opis „domyka wyłącznie historię" przy wpisie, który zwalniał wszędzie). Druga połowa
tego zdania — „pilnuje tego test `SekretyTest`" — jest prawdziwa tylko dla 14 nazw,
a docblock `SekretyTest` reklamuje się szerzej: „Ten test łapie coś, czego gitleaks nie
widzi: wpisanie wartości do `.env.example`". **Dwa mechanizmy, każdy powołujący się na
drugi, i dziura dokładnie w części wspólnej.** To jest „wspólny klucz" i „dwie ślepe
plamy nałożone na siebie" w jednym.

Zmienna z pomiaru nie jest wymyślona: `GOOGLE_CALENDAR_CLIENT_SECRET` będzie potrzebna
w F4 (Google Calendar freeBusy), tak jak token huba w F8 i token Zammada w F8. Każda
z nich wejdzie do `.env.example` w fazie, w której nikt już nie będzie pamiętał, że
ochrona tego pliku jest listą nazw.

**Odtworzenie.** W efemerycznym klonie: dopisz `GOOGLE_CALENDAR_CLIENT_SECRET=GOCSPX-…`
do `.env.example`, zacommituj (gitleaks skanuje historię), uruchom
`docker run --rm -v <repo>:/repo -w /repo zricethezav/gitleaks:latest detect --source=/repo
--config=/repo/.gitleaks.toml --no-banner --redact` → `no leaks found`; `pest
tests/Feature/SekretyTest.php` → 3 passed. Kontrola pozytywna: ta sama linia w `docs/probka.md`
→ `leaks found: 1`.

**Kierunek naprawy.** Odwrócić ciężar dowodu tak, jak zrobiono to dla schematu bazy
i dla prymitywów krypto (R6A-4): `SekretyTest` ma pytać nie „czy te 14 nazw jest pustych",
tylko „czy KAŻDE przypisanie w `.env.example` jest puste albo stoi na jawnej,
uzasadnionej liście wartości nietajnych" (`APP_NAME=Gabinet`, `DB_PORT=5432`, …).
Wtedy nowa zmienna sekretna kosztuje wpis i pytanie przy przeglądzie, a nie nic.
Przy okazji: sprzeczne zdanie w `.gitleaks.toml` trzeba sprostować, bo dziś uczy
czytelnika czegoś, co jest nieprawdą.

---

### R9-4 (ŚREDNIA) — naprawa zasięgu z wady własnej §4 objęła JEDNĄ asercję; dwa inne skanery katalogów nadal widzą tylko `app/`, choć deklarują „kod produkcyjny"

**Kontekst.** `ZLECENIE-067` każe sprawdzić „zasięg WSZYSTKICH kontroli skanujących
katalogi … czy widzą oba katalogi wykonywalne". Sprawdziłem wszystkie
(`grep` po `File::allFiles|File::files|RecursiveDirectoryIterator|glob(` w `backend/tests`).
Dwie mają zasięg węższy od własnej deklaracji, obie zmierzone żywą mutacją.

**(a) `WaskieGardloTozsamosciTest`, asercja „WARUNEK UTRZYMUJĄCY" (w. 284–288).**
To jest deklarowany warunek podtrzymujący naprawę R6A-3 — dwóch wektorów omijających
KAŻDY konstruktor w PHP. Docblock pliku: „EGZEKWUJEMY WARUNEK UTRZYMUJĄCY: obu tych
narzędzi **nie ma w kodzie produkcyjnym**". Skan idzie po `base_path('app')`.
**Trzy asercje wyżej, w tym samym pliku, ten sam commit dopisał `routes/` — tę zostawił.**

```
unserialize(…) + new ReflectionClass(…) ŻYWE w backend/routes/web.php
(bez żadnego odwołania do tożsamości, żeby nie zapalić sąsiedniej asercji):
   WaskieGardloTozsamosciTest → 5 passed
   PEŁNA SUITA                → 2 skipped, 290 passed (2130 assertions)

KONTROLA POZYTYWNA — te same dwa narzędzia w app/Http/Controllers/Controller.php:
   WaskieGardloTozsamosciTest → 1 failed, 4 passed   (asercja w. 290)
```

**(b) `ObietniceKomentarzyTest` (w. 97, 120, 148).** Skanuje `app/` i `tests/`.
Obietnica pokrycia w `routes/` nie jest sprawdzana:

```
/** @dowod: KlasaKtorejNaPewnoNieMaTest … Naprawa R6A-99 obiecana w komentarzu. */
w backend/routes/web.php:
   ObietniceKomentarzyTest → 4 passed

KONTROLA POZYTYWNA — ten sam docblock w app/Http/Controllers/Controller.php:
   ObietniceKomentarzyTest → 1 failed, 3 passed   (asercja w. 175)
```

**Dlaczego to znalezisko, a nie „wąskość latentna".** Bo kontrole **twierdzą coś o kodzie
produkcyjnym**, a `routes/` jest kodem produkcyjnym — wykonuje się przy każdym żądaniu
i to właśnie tam mieszkał atak rundy 7. Wada §4 była dokładnie tym samym zdaniem
o tym samym katalogu; naprawiono instancję, klasa została w sąsiednich wierszach.
Waga ŚREDNIA, nie WYSOKA: pełny atak przez `unserialize` w `routes/` musiałby jeszcze
zapisać tożsamość, a to (na czystym drzewie) łapie sąsiednia asercja allowlisty —
zmierzone w §2. Groźny jest fałszywy warunek utrzymujący, nie natychmiastowa dziura.

**Odtworzenie.** Wstaw do `backend/routes/web.php` domknięcie wołające `unserialize()`
i `new ReflectionClass(stdClass::class)` (bez `TozsamoscSesji::`, `SesjaKonta::` ani
literału `'konta'`) → `pest tests/Feature/WaskieGardloTozsamosciTest.php` = 5 passed.
Kontrola pozytywna: ten sam kod w dowolnym pliku `app/` → 1 failed.

---

### R9-5 (ŚREDNIA) — sekcja stanu `PLAN-FAZ.md` na ZAMROŻONYM SHA przypisuje własny pomiar SHA, na którym ten pomiar jest niemożliwy, i podaje nieaktualne liczby perturbacji

**Kontekst.** Plik stanu jest, wg `CLAUDE.md`, źródłem stanu między sesjami. Ta klasa
kosztowała już dwa razy: R6A-9 (dwie sekcje `CURRENT WORK`) i R7-6 (trzy nieprawdziwe
twierdzenia w sekcji stanu). Ostatni commit przed zamrożeniem nazywa się wprost
„Plik stanu klamal o dacie WLASNEGO pomiaru" (`7f4c65f`).

**Co zmierzone — trzy twierdzenia z sekcji stanu na `d79dc0c` obok pomiaru:**

| twierdzenie w `PLAN-FAZ.md` @ `d79dc0c` | zmierzone |
|---|---|
| „Bramka … Zmierzone **18.08 na ZAMROŻONYM SHA `179c05c`**" | bramka zielona — ale ta kotwica obejmuje też liczby z wiersza niżej |
| „**290 zielonych** … **2130 asercji** — zmierzone 18.08 **na tym samym przebiegu**" | 290/2130 zgadza się dla `d79dc0c`; **dla `179c05c` jest niemożliwe** |
| „`PERTURBACJE OK — 48 kontroli` … **PEŁNY zestaw 31 scenariuszy**" | **49 kontroli, 32 scenariusze**, 0 pominiętych |

Dowody z repozytorium, nie z pamięci:

```
git show 179c05c:skrypty/podlogi.sh  → MINIMUM_TESTOW=289  MINIMUM_ASERCJI=2119
git show d79dc0c:skrypty/podlogi.sh  → MINIMUM_TESTOW=290  MINIMUM_ASERCJI=2130
   (komentarz w tym samym pliku: „PODNIESIENIE 18.08 (naprawy rundy 8: R8-1 skaner
    pol + R8-2 realny start): 289/2119 -> 290/2130")

git show 179c05c:PLAN-FAZ.md         → „289 zielonych … 2119 asercji — zmierzone 12.08"
git diff --stat 179c05c..d79dc0c -- backend/tests/  → 3 pliki, +381 / -58
liczba deklaracji `it(`/`test(` na początku linii: 179c05c → 249, d79dc0c → 250
```

Czyli: liczby `290 / 2130` powstały **z napraw rundy 8**, których na `179c05c` NIE MA.
Zdanie „zmierzone na tym samym przebiegu" wiąże je z przebiegiem zadeklarowanym jako
wykonany na `179c05c`. Sesja czytająca ten plik dostaje kotwicę, która nie trzyma.
Diff `179c05c..d79dc0c` pokazuje przy tym, że TEN SAM commit, który podniósł liczby
testów, **zostawił w sekcji stanu perturbacje na 48/31**, choć dokładał 32. scenariusz
(`p_d1b_zaklecie`) i 49. kontrolę.

**Dlaczego `JednoZrodloStanuTest` tego nie łapie.** Sprawdza (i robi to dobrze) trzy rzeczy:
zgodność liczb „Podłogi X / Y" ze `skrypty/podlogi.sh`, warunek „zielonych ≥ podłoga"
oraz prawdziwość zdań „NIE POWSTAŁ / NIE ISTNIEJE". **Nie sprawdza ani kotwicy pomiaru,
ani liczb perturbacji** — a to jedyne dwa twierdzenia sekcji stanu, które dziś są
nieprawdziwe. Kontrola pilnuje tego, co się już raz zepsuło, i nie widzi sąsiada.

**Odtworzenie.** `sed -n '13,31p' PLAN-FAZ.md` na `d79dc0c` obok
`git show 179c05c:skrypty/podlogi.sh | grep MINIMUM_` oraz ostatniej linii przebiegu
`bash skrypty/perturbacje.sh`.

**Kierunek naprawy.** Liczby perturbacji zrównać z pomiarem (49/32) i — tak jak dla
podłóg — dać im JEDNO źródło, z którego czyta i sekcja stanu, i kontrola. Kotwicę
pomiaru zapisać jako SHA, na którym pomiar naprawdę wykonano (`d79dc0c`); najtaniej
egzekwowalnie: niech `JednoZrodloStanuTest` wymaga, by cytowana kotwica wskazywała
commit, w którym `podlogi.sh` niesie cytowane liczby.

---

## 4. Pomiar rozstrzygający — świeży subagent, własne klony, bez mojego kontekstu

Świeży subagent, bez mojego kontekstu i bez informacji o tym, jakiego wyniku „się
spodziewam", postawił DWA własne klony (`d:/tmp/gabinet-r9v` na `d79dc0c`,
`d:/tmp/gabinet-r9v2` na `179c05c`), własne stosy (projekty `gabinet-r9v` / `gabinet-r9v2`,
porty 8140–8141 / 55490–55491 / 56440–56441) i wykonał pomiary od zera.

**Jego baseline na czystym `d79dc0c`:** `2 skipped, 290 passed (2130 assertions)`,
`[OK] No errors`, `PASS 102 files`, `BRAMKA OK — 21 kroków, 0 nieudanych` (`--zostaw`).
Kontrola przyrządu na czystym kodzie: `has(konta): NIE`, `konta: null`.

| pomiar | WARIANT 1 (`email`+`haslo`, oba przez `input()`) | WARIANT 2 (`zaklecie` przez `$request->all()`) | WARIANT 3 — KONTROLA (`zaklecie` przez `input()`) |
|---|---|---|---|
| `SiatkaPomiarowaTozsamosciTest` | `3 passed (10 assertions)` | `3 passed (10 assertions)` | **`1 failed, 2 passed`** — trafienie `GET /auth/login (ładunek: 15 pól …)` @ w. 353 |
| `WaskieGardlo` + `BrakWlasnychHasel` | `14 passed (47 assertions)` | `14 passed (47 assertions)` | `14 passed (47 assertions)` |
| PEŁNA SUITA | `2 skipped, 290 passed (2130 assertions)` | `2 skipped, 290 passed (2130 assertions)` | **`1 failed, 2 skipped, 289 passed`** |
| Larastan (level max) | `[OK] No errors` | `[OK] No errors` | `[OK] No errors` |
| Pint | `PASS 102 files` | `PASS 102 files` | `PASS 102 files` |
| DOWÓD przez jądro HTTP | POPRAWNE `has=TAK` `{"sub":"sub-lokalne","role":["pacjent"]}` · BŁĘDNE `has=NIE` · BEZ PARAM `has=NIE` | POPRAWNE `has=TAK` `{"sub":"sub-lokalne","role":["pacjent"]}` · BŁĘDNE `has=NIE` · BEZ PARAM `has=NIE` | POPRAWNE `has=TAK` · BŁĘDNE `has=NIE` · BEZ PARAM `has=NIE` |

Po każdym wariancie `git status --porcelain` puste, `HEAD = d79dc0c`; wiersz kontrolny
z tabeli `users` kasowany (`TEARDOWN: usunieto 1 wierszy`).

**Pomiar 2 — suita na `179c05c`** (drugi klon, pełna bramka od zera):

```
Tests:    2 skipped, 289 passed (2119 assertions)
WYKONANO 289 testów (podłoga: 289)
sprawdzono 2119 asercji (podłoga: 2119)
BRAMKA OK — 22 kroków, 0 nieudanych
```

To jest **rozstrzygnięcie R9-5 pomiarem, nie wnioskowaniem**: na SHA, do którego sekcja
stanu przypina swój pomiar, suita ma **289 / 2119**, a nie 290 / 2130. Twierdzenie
„290 zielonych … 2130 asercji — zmierzone 18.08 na tym samym przebiegu [na ZAMROŻONYM
SHA `179c05c`]" jest nieprawdziwe niezależnie od tego, jak się je czyta.

Oba stosy subagenta zgaszone (`down -v`, kod 0), oba klony i obrazy usunięte,
zero pozostałych kontenerów i wolumenów z prefiksami `r9v`/`r9v2`.

**Rozbieżności między moim pomiarem a pomiarem subagenta: ŻADNE.** R9-1, R9-2 i R9-5
odtworzone niezależnie, na innych klonach, innych stosach i innych portach.

---

## 5. CZEGO NIE SPRAWDZIŁEM (sekcja obowiązkowa)

1. **Kontrola D-4 przez PRZYNĘTĘ w nowym commicie** — sprawdziłem wpis
   `.gitleaks.toml` statycznie (`condition = "AND"`, `targetRules`, `regexes`, pełne SHA
   trzech commitów) i przyjąłem pomiar rund 7/8 za wierny. Własnego commita z wartością
   `aGVsbG8…` poza trójką zwolnionych NIE zrobiłem — mój commit testowy dotyczył
   innego wpisu tego pliku (R9-3).
2. **`odczyt-przyczyn.py` w trybie DYNAMICZNYM** — nie uruchamiałem ani przeciw żywemu
   stosowi, ani na hoście. Zapadka statyczna (`SUFIT_NIEROZROZNIAJACYCH=0`) jest zielona
   w pełnej suicie; deklaracja „14 wywołań ZGODNE-ROZROZNIA" przyjęta bez własnego
   przebiegu. To ta sama pozycja, którą runda 8 zapisała jako niesprawdzoną.
3. **`KlamraSkryptowTest` — skany `File::files(skrypty)` NIEREKURENCYJNE** (rejestr
   `docker compose` w. 39/219 oraz skaner uchwytów `trap` w. 524). Runda 8 zgłosiła to
   jako latentną wąskość; **stan bez zmian**, nie mierzyłem go ponownie i nie podnoszę
   do rangi znaleziska, bo dziś nic z podkatalogów nie ucieka (`skrypty/git-hooks/`,
   `skrypty/perturbacje-odwrotne/`). Odnotowuję, że to **trzecia** kontrola tej samej
   rodziny co R9-4 — jeśli architekt zamyka rodzinę razem, ta pozycja do niej należy.
4. **`TwierdzeniaKomentarzyTest`** (D-3) — poza bramką, nie uruchamiałem go osobno;
   potwierdziłem tylko, że to on daje `2 skipped`.
5. **Granica 1 i 2 siatki D-1b** (ciasteczko z innej trasy, zapis do innego magazynu
   sesji) — nie próbowałem ich wykorzystać. Wykorzystałem granicę 3 (nagłówki) i granicę
   NIENAZWANĄ (parowanie pól). Granica 1 wewnątrz pliku Z ALLOWLISTY jest według mnie
   otwarta z tego samego powodu co R9-1, ale **tego nie zmierzyłem**.
6. **`nazwyPolWejsciowych()` skanuje `routes/` NIEREKURENCYJNIE** (`File::files`),
   podczas gdy `WaskieGardloTozsamosciTest` skanuje ten sam katalog rekurencyjnie.
   Dziś `routes/` nie ma podkatalogów, więc nic nie ucieka — **nie mierzyłem** tego
   i nie liczę jako znaleziska.
7. **Skanery pomijające `bootstrap/`, `config/`, `database/`** — nie badałem, czy
   mechanizm tożsamości da się osadzić w `bootstrap/app.php` (nowa trasa zapaliłaby
   `BrakWlasnychHaselTest`, ale nazw pól z tego pliku parser i tak by nie zobaczył).
8. **Wyścig SIGTERM→SIGKILL na żywej bramce** — jak w rundzie 8, nie odtwarzałem
   przerwania sekwencją sygnałów; sprawdziłem tylko skutek na zielonym przebiegu
   (znacznik zdjęty, drzewo czyste).
9. **Współbieżność** (`CLAUDE.md` §6, 100 równoczesnych żądań) — poza suitą, zakres F3.
10. **Tezy kontraktowe wobec `konta/`, `hub/`, `helpdesk/`** — nie mam tych repo
    w zasięgu rundy; cytaty B7/B8/BLK-22/§4.x przyjęte za wierne.
11. **Treść `docs/specyfikacja/`, `WYTYCZNE-PRACY.md` poza cytowanymi regułami,
    ~60 plików `docs/ZLECENIA/`** poza tymi, które rozstrzygały zamknięcia tej rundy.
12. **Migracje/schemat** poza tym, co mierzy bramka (`OCZEKIWANY_SCHEMAT` nie
    porównywany z żywym schematem inaczej niż przez zielony `ModelDanychTest`).
13. **Merytoryka retencji F1** — przeczytałem `ZadanieRetencji` i `RejestrRetencji`
    i nie znalazłem w nich wady (selekcja i weryfikacja idą OSOBNYMI zapytaniami,
    okres `null` = nieustalony jest filtrem opisanym zgodnie z implementacją), ale
    **nie mierzyłem** ich mutacjami poza tym, co robią perturbacje `retencja`
    i `retencja_wykonanie`.
14. **CI (GitHub Actions)** — nie uruchamiałem; bramka mierzona wyłącznie lokalnie.

---

## 6. Zakres pokryty — dla jawności

Zmierzone: pełna bramka OD ZERA (22 kroki, 290/2130 RÓWNO z podłogami, kod 0) na czystym
klonie; drugi, niezależny przebieg na drugim klonie; PEŁNY zestaw perturbacji
(49 kontroli / 32 scenariusze / 0 pominiętych, kod 0) na trzecim projekcie compose;
warunek zamrożenia z `ODPOWIEDZ-068` sprawdzony dwustronnie; wszystkie trzy zamknięcia
`ODPOWIEDZ-066` z kontrolą pozytywną I negatywną przyrządu; pięć znalezisk, z czego
trzy z żywym mechanizmem i dowodem skutku przez jądro HTTP albo przez skan gitleaksa,
wszystkie z kontrolą pozytywną instrumentu; przegląd zasięgu WSZYSTKICH skanerów
katalogów w suicie; trzy znaleziska (R9-1, R9-2, R9-5) odtworzone niezależnie przez
świeżego subagenta na dwóch WŁASNYCH klonach i stosach — bez rozbieżności.

**Zbieżność rund:** 11 → 15 → 12 → 29 → 9 → 2 → **5**.
Wzrost względem rundy 8 nie jest nawrotem: żadne z pięciu znalezisk nie podważa napraw
rundy 8 — dwa leżą o krok dalej w tym samym mechanizmie (siatka D-1b), jedno o krok dalej
w naprawie wady własnej §4, jedno w miejscu, którego dotąd nie mierzyła żadna runda
(`.env.example`), jedno w pliku stanu.

**Faza F1/F0 pozostaje OTWARTA — pięć znalezisk. Runda nie kończy się zerem.**
Fazę zamyka wyłącznie runda z zerem znalezisk (D-2026-08-07-16) — kryterium nie łagodzę.
