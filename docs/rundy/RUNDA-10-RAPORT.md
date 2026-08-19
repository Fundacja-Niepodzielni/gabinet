# RUNDA 10 — RAPORT NIEZALEŻNEGO WERYFIKATORA

**SHA mierzone:** `528adc365040808b9abc653cfddc2c8b3d08f94c` (gałąź `faza-1-retencja`, „528adc3").
**Zlecenie:** `docs/ZLECENIA/ZLECENIE-071.md` + dwa uzupełnienia architekta w kanale.
**Data pomiaru:** 19.08.2026.
**Stawka:** F1 i F0 zamykają się WYŁĄCZNIE rundą z zerem znalezisk (D-2026-08-07-16).

## Werdykt jednym zdaniem

**Faza NIE zamyka się w tej rundzie. JEDNO znalezisko (R10-1, WYSOKA):**
mechanizm własnego hasła wstawiony do JEDYNEGO legalnego miejsca — metody
callbacku OIDC `powrot()` — i czytający pole żądania przez **dostęp tablicowy
`$request['zaklecie']`** albo przez **metodę spoza 15-elementowej listy**
(`$request->str(...)`) **przechodzi CAŁĄ bramkę na zielono i realnie loguje
użytkownika**. To falsyfikuje wprost twierdzenie zamknięcia z `ODPOWIEDZ-069`
§2 („Mechanizm wewnątrz `powrot()` — **zamyka warstwa 3**"). Bramka na
`528adc3` jest zielona 22/22 i zgodna z deklaracją autora co do KAŻDEJ liczby;
warstwy 1 i 2 wąskiego gardła oraz zamknięcia R9-3, R9-4, R9-5 bronią się
pomiarowo z kontrolą pozytywną i negatywną. Znalezisko leży dokładnie w miejscu,
które autor sam nazwał w mapie („warstwa 3 … nadal LISTĄ; klasa »lista zamiast
pomiaru« ma jeszcze oddech") — ale twierdzenie §2 mówi „zamknięte", a pomiar
mówi „otwarte".

**Zbieżność rund:** 11 → 15 → 12 → 29 → 9 → 2 → 5 → **1**.

---

## 0. Środowisko pomiaru — własne izolowane klony, nie `gabinet-perturbacje`

Zgodnie z lekcją rundy 7 (`gabinet-perturbacje` montuje DRZEWO dewelopera) NIE
użyłem tego stosu. Postawiłem własne, efemeryczne klony i stosy; po pomiarach
drzewa wracały do `0 zmian` (`git status --porcelain` PUSTE), a stosy zgasiłem
`down -v` i usunąłem obrazy po nazwie.

| klon | katalog | projekt compose | porty (HTTP/PG/Redis) | rola |
|---|---|---|---|---|
| bramka2 | `D:/tmp/gabinet-r10/bramka2` | `gabinet-r10` | 8150 / 55500 / 56450 | bramka OD ZERA |
| zamkniecia | `D:/tmp/gabinet-r10/zamkniecia` | `gabinet-r10z` | 8151 / 55501 / 56451 | stos żywy do sond i mutacji + drugi przebieg bramki |
| polowanie | `D:/tmp/gabinet-r10/polowanie` | `gabinet-r10p` | 8161 / 55511 / 56461 | PEŁNE perturbacje |
| (subagent) | `D:/tmp/gabinet-r10sub/klon` | `gabinet-r10sub` | 8160 / 55510 / 56460 | pomiar rozstrzygający R10-1 (świeży subagent) |
| d5test | `D:/tmp/gabinet-r10/d5test` | — (tylko gitleaks) | — | weryfikacja wąskości wyjątku D-5 na czubku |

Stos dewelopera `gabinet` (8098/55442/56389) i `gabinet-perturbacje` (8097)
**NIETKNIĘTE** — zweryfikowane po rundzie (6 kontenerów `gabinet-*` up).
**Zakaz commitowania w repozytorium projektu utrzymany** — jedyne zapisy to ten
raport i `ODPOWIEDZ-071.md`, oba niezacommitowane. Commity powstały WYŁĄCZNIE
w klonie efemerycznym `d5test` (weryfikacja D-5 wymaga skanu historii); klon
usunięty w całości. Nic nie wypchnięto.

### Higiena klonów — lekcja z pierwszego przebiegu (raportuję, bo zmieniła pomiar)

Pierwszy klon `--no-hardlinks` z lokalnego repo wciągnął refy potomne (`527f1b7`,
`11da17e`), więc gitleaks w bramce zobaczył w HISTORII cytat sekretu z
`docs/rundy/RUNDA-9-RAPORT.md:340` (`GOOGLE_CALENDAR_CLIENT_SECRET=GOCSPX-…`) i
krok [21] zapalił się na czerwono — **na potomku, nie na `528adc3`**. Naprawa:
klon przypięty WYŁĄCZNIE do `528adc3` (usunięte `refs/remotes`, `refs/tags`,
gałąź `faza-1-retencja`, `reflog expire` + `gc --prune=now`). Po tym: `git
rev-list --all --count` = 148 commitów, historia kończy się na `528adc3`, a krok
[21] jest zielony (`no leaks found, 148 commits`). To ta sama klasa środowiskowa,
którą zgłosił architekt w uzupełnieniu 1 — potwierdzam ją pomiarem i odnotowuję,
że **weryfikator, który nie przytnie refów, zmierzy czerwień z cudzego commita**.
Subagent (który refów nie przyciął) trafił dokładnie w to i zaraportował
[21] czerwone — bez wpływu na werdykt R10-1 (patrz §4).

---

## 1. Pełna bramka — wynik LICZBOWY

Przebieg OD ZERA na czystym klonie `bramka2` przypiętym do `528adc3`
(`bash skrypty/bramka.sh --projekt gabinet-r10`), kod wyjścia 0:

```
BRAMKA OK — 22 kroków, 0 nieudanych              (kod wyjścia 0)
Tests: 2 skipped, 301 passed (2170 assertions)
WYKONANO 301 testów (podłoga: 301)               (RÓWNO — bez zapasu)
sprawdzono 2170 asercji (podłoga: 2170)          (RÓWNO — bez zapasu)
pominięte (nie liczą się do podłogi): 2          (oba: TwierdzeniaKomentarzyTest, WARN, dług D-3)
Pint:                 PASS 104 files
Larastan (level max): [OK] No errors
gitleaks:             148 commits scanned, no leaks found
czas testów:          55 s
```

**Deklaracja autora (`ODPOWIEDZ-069` §1) POTWIERDZONA co do każdej liczby:**
22/22 · 301/2170 · podłogi RÓWNO 301/2170 · 2 pominięte.
Podłogi to JEDNO źródło `skrypty/podlogi.sh` (`MINIMUM_TESTOW=301` w. 83,
`MINIMUM_ASERCJI=2170` w. 90) — **odczytane, nie zacytowane**.
Uwaga: liczba 104 plików Pinta (nie 102 z rundy 9) to skutek napraw rundy 9
(nowe `Kod.php`, `WaskieGardloZapisuTozsamosciTest.php` itd.).

**Drugi, niezależny przebieg** na klonie `zamkniecia` (projekt `gabinet-r10z`,
inne porty, `--zostaw`): `BRAMKA OK — 21 kroków, 0 nieudanych`,
`301 passed (2170 assertions)`, `no leaks found` — te same liczby na innym
klonie, innym projekcie compose i innych portach.

**Perturbacje — PEŁNY zestaw, własny stos `gabinet-r10p`:**

```
PERTURBACJE OK — 52 kontroli udowodniło, że umie zaświecić czerwono (pominięte: 0)
kod wyjścia 0
zmierzone niezależnie: 35 nagłówków „=== PERTURBACJA", 52 wiersze „✓", 0 wierszy „✗"
```

**Zgodne z deklaracją autora (`ODPOWIEDZ-069` §1: 52 kontrole / 35 scenariuszy /
0 pominiętych) co do każdej liczby.** Lista scenariuszy w `perturbacje.sh`
(`WSZYSTKIE=…`) niesie **35** nazw — zgodne z sekcją stanu.

### Warunek zamrożenia — sprawdzony dwustronnie

Na moim obiekcie (`528adc3`) kod jest nietknięty. Na obecnym czubku gałęzi
(`11da17e`, dwa commity ponad `528adc3`):

```
git diff --stat 528adc3..11da17e -- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md'
   →  .gitleaks.toml | 44 +++…                       (NIE puste)
git diff --stat 528adc3..11da17e -- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md' ':(exclude).gitleaks.toml'
   →  PUSTO
git diff --name-only 528adc3..11da17e -- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md'
   →  .gitleaks.toml   (jedyny plik)
```

Czyli: jedyną zmianą ponad `528adc3` poza `docs/` i `PLAN-FAZ.md` jest
`.gitleaks.toml` (commit `11da17e`, dług D-5). **To jest ZNANE i zadeklarowane**
w uzupełnieniu 2 architekta, który zaktualizował formę warunku, wykluczając
`.gitleaks.toml`. W tej zaktualizowanej formie warunek jest **SPEŁNIONY**.
Backend (`backend/`, `skrypty/`) nietknięty. **Nie jest znaleziskiem** — patrz
§5 (weryfikacja wąskości D-5) i §6 (rozbieżność form warunku).

---

## 2. Weryfikacja zamknięć z `ODPOWIEDZ-069` — każde z kontrolą pozytywną I negatywną

Pomiary na żywym stosie `gabinet-r10z`, drzewo przywracane KOPIĄ pliku
(nie `git checkout`), po każdym `git status --porcelain` PUSTE.

### R9-1 + R9-2 + R9-4 — wąskie gardło zapisu tożsamości (3 warstwy)

**Warstwy 1 i 2 BRONIĄ SIĘ. Warstwa 3 ma zmierzoną dziurę → R10-1 (§3).**

| warstwa | kontrola | pomiar | wynik |
|---|---|---|---|
| **1** (zapis poza fasadą) | NEGATYWNA (czysto) | `WaskieGardloZapisuTozsamosciTest` | **5 passed (19 asercji)** |
| **1** | POZYTYWNA (zapis w `LogowanieController::zaloguj`) | `session()->put('konta', …)` w innej metodzie pliku z allowlisty | **1 failed** — „ZAPIS TOŻSAMOŚCI POZA WĄSKIM GARDŁEM"; ZNALEZIONE zawiera `LogowanieController.php::zaloguj` |
| **1** | POZYTYWNA (zapis w `routes/`) | `session()->put('konta', …)` w domknięciu `routes/web.php` | **1 failed** — ZNALEZIONE zawiera `routes/web.php::{domkniecie} (wiersz 33, put())` |
| **2** (ustanowienie poza callbackiem) | POZYTYWNA (`zaloz` z `ja()`) | `SesjaKonta::zaloz(...)` wstrzyknięte do metody `ja()` | **1 failed** — „USTANOWIENIE TOŻSAMOŚCI POZA CALLBACKIEM OIDC" |
| **3** (odczyt spoza kontraktu) | POZYTYWNA (odczyt metodą Z listy) | `$request->input('zaklecie')` w `powrot()` | **1 failed** — „CALLBACK OIDC CZYTA Z ŻĄDANIA COŚ SPOZA SWOJEGO KONTRAKTU: wiersz N: ->input(zaklecie)" |
| **3** | **NEGATYWNA-DZIURA (odczyt tablicowy)** | `$request['zaklecie']` w `powrot()` | **3 passed (WARSTWA 3 ZIELONA)** → **R10-1** |
| **3** | **NEGATYWNA-DZIURA (metoda spoza listy)** | `$request->str('zaklecie')` w `powrot()` | **WARSTWA 3 ZIELONA** → **R10-1** |

Atrybucja do METODY (nie do pliku) działa: kontrola „KIERUNEK ODWROTNY: atrybucja
do FUNKCJI" jest zielona, a parser `Kod::funkcje()` poprawnie rozpoznał zapis
w `zaloguj` (nie w `powrot`) i w domknięciu `routes/web.php` — czyli allowlista
NIE cofnęła się po cichu do poziomu pliku. To był trop autora z mapy; **nie jest
znaleziskiem** (parser trzyma atrybucję).

### R9-2 — nowy parser `nazwyPolWejsciowych()`: **BRONI SIĘ**

Parser bierze KAŻDY literał napisowy o kształcie nazwy pola z czterech katalogów
wykonywalnych — bez listy metod. Zmierzone na czystym `528adc3`
(reimplementacja uruchomiona w kontenerze na `app`+`routes`+`bootstrap`+`config`):

```
liczba nazw pól (kształt):  664        (nie „4 z całej aplikacji" jak przed R9-2)
zawiera code i state:       TAK
```

Asercja broniąca porównuje liczbę z drugim, niezależnym odczytem (`preg_match_all`);
`664 > drugi/2` — parser widzi źródła, nie ich ułamek. R9-2 (jako `SiatkaPomiarowa`)
jest dziś **WZMOCNIENIEM**, nie jedyną linią; perturbacje `d1b`, `d1b_zaklecie`
zapalają się w pełnym zestawie (policzone w 52).

### R9-3 — sekret w `.env.example` (odwrócony ciężar dowodu): **BRONI SIĘ**

| kontrola | pomiar | wynik |
|---|---|---|
| NEGATYWNA (czysto) | `SekretyTest` | **4 passed (37 asercji)** |
| POZYTYWNA (sekret pod nazwą SPOZA listy 14 i spoza 4 reguł gitleaks) | `GOOGLE_CALENDAR_CLIENT_SECRET=GOCSPX-…` w `.env.example` | **1 failed** — „wiersz 141: GOOGLE_CALENDAR_CLIENT_SECRET ma WARTOŚĆ, a nie stoi na liście nietajnych" |

Ciężar dowodu jest odwrócony: KAŻDE przypisanie musi być puste albo jawnie stać
na `WARTOSCI_NIETAJNE` (46 pozycji), a druga linia (`wygladaJakSekret`) łapie
wartość o kształcie sekretu nawet po bezmyślnym dopisaniu do listy. Sprzeczne
zdanie w `.gitleaks.toml` (R9-3) sprostowane — obecny opis mówi stan faktyczny
(wyjątek ISTNIEJE i jest potrzebny, ochroną niezależną od nazwy jest `SekretyTest`).

### R9-4 — jedna wspólna lista katalogów: **BRONI SIĘ**

`Tests\Wsparcie\Kod::katalogiWykonywalne()` = `app`, `routes`, `bootstrap`,
`config` (rekurencyjnie). Zmierzone: wszystkie skanery kodu wykonywalnego
(`WaskieGardloZapisuTozsamosci`, `WaskieGardloTozsamosci` warunek utrzymujący,
`ObietniceKomentarzy`, `SiatkaPomiarowa` parser) czytają z tej JEDNEJ listy.
Kontrola pozytywna zasięgu `routes/` zmierzona wyżej (warstwa 1 → `routes/web.php`
zapala). Skaner połkniętych komunikatów (wada własna §5) poszerzony o `toHaveKey`,
`toHaveProperty`, `toHaveKeys` — obecne w `polknieteKomunikaty()`, zielone w suicie.

### R9-5 — plik stanu, kotwice SHA: **BRONI SIĘ**

`JednoZrodloStanuTest` egzekwuje: (a) każda kotwica `zmierzone na <SHA>` wskazuje
commit ISTNIEJĄCY (`git cat-file -e`), (b) liczba scenariuszy perturbacji w sekcji
stanu = liczbie w `perturbacje.sh`. Zmierzone:

```
kotwica sekcji stanu:                zmierzone na `d79dc0c`   (git cat-file -e → istnieje w klonie)
git show d79dc0c:skrypty/podlogi.sh: MINIMUM_TESTOW=290, MINIMUM_ASERCJI=2130   (zgodne z „290/2130 na d79dc0c")
liczba scenariuszy w sekcji stanu:   35   (sekcjaStanu() ogranicza się do „## CURRENT WORK" przed pierwszym „###")
liczba scenariuszy w perturbacje.sh: 35   (WSZYSTKIE=…)
```

Uwaga metodyczna: `PLAN-FAZ.md` zawiera też „20 scenariusz" (w. 224) — ale w sekcji
HISTORYCZNEJ, poza granicą `sekcjaStanu()` (która kończy się przed pierwszym `###`),
i jest tam jawnie oznaczone jako „nieprawdziwe od kilku dni". Egzekutor widzi
wyłącznie „35 scenariuszy" z sekcji bieżącej. Kontrola pilnuje tego, co się już
zepsuło; **broni się**.

### Znane długi

- **D-2** (allowlisty `--przyczyna`, sufit 0): `PrzyczynyPerturbacjiTest`
  (`SUFIT_NIEROZROZNIAJACYCH=0`) zielony w suicie; spis pochodzi z parsowania
  `perturbacje.sh`, nie z listy długu (inne źródło niż przedmiot). Deklaracja
  „SPŁACONY" broni się w zakresie statycznym. ⚠ Trybu DYNAMICZNEGO nie odtwarzałem
  (patrz §7).
- **D-3** (`TwierdzeniaKomentarzyTest` poza bramką): potwierdzone — `2 skipped`,
  **oba** z `Tests\Feature\TwierdzeniaKomentarzyTest` (`WARN` w Pest). Bez zmian.
- **D-4** (wyjątek gitleaks, base64 `hello-world-…`): sprawdzony STATYCZNIE — wpis
  ma `condition = "AND"`, `targetRules`, `regexes` i **pełne SHA czterech** commitów.
  Przynęty w nowym commicie NIE odtwarzałem (patrz §7).
- **D-5** (nowy, cytat sekretu w raporcie): zweryfikowany POMIAREM jako wąski — §5.

**Nowych długów autor NIE zaciąga** poza D-5, który jest jawnie zadeklarowany
z warunkiem znoszącym i terminem (O-2b listy scaleniowej). Granice warstwy 3
(list metod, dostęp tablicowy) NIE są nazwane jako granice przyrządu w kodzie
kontroli — dlatego są znaleziskiem, nie długiem (§3).

---

## 3. ZNALEZISKO

Waga jak w rundach 6–9: KRYTYCZNA (luka eksploatowalna DZIŚ z zewnątrz) · WYSOKA ·
ŚREDNIA · NISKA. Znalezisko nie jest eksploatowalne z zewnątrz na `528adc3` — na
czystym drzewie tego mechanizmu NIE MA. Groźny jest **fałszywy spokój**: kontrola
(i meldunek autora) reklamuje zamknięcie, którego nie ma, więc pierwsza osoba, która
wstawi taki kod do callbacku, nie dowie się o tym od bramki.

---

### R10-1 (WYSOKA) — warstwa 3 wąskiego gardła NIE zamyka „mechanizmu wewnątrz `powrot()`": odczyt pola żądania przez dostęp tablicowy `$request['…']` albo przez metodę spoza 15-elementowej listy jest dla niej NIEWIDZIALNY; własne hasło w callbacku OIDC przechodzi CAŁĄ bramkę i loguje

**Kontekst i dlaczego to falsyfikuje zamknięcie, a nie tylko trop.**
`ODPOWIEDZ-069` §2 („Krok dalej") deklaruje wprost:

> „3. **Mechanizm wewnątrz `powrot()`** — zamyka warstwa 3 (kontrakt OIDC to
>  `code` i `state`; cokolwiek innego czytane z żądania jest tam obce)."

To jest twierdzenie ZAMKNIĘCIA. Warstwa 3 (`WaskieGardloZapisuTozsamosciTest`
w. 320-396) istnieje właśnie dla wektora, wobec którego warstwy 1 i 2 są bezradne
z definicji: mechanizmu w JEDYNYM miejscu, które ma prawo pisać tożsamość
(`LogowanieController::powrot`). Zdanie „cokolwiek innego czytane z żądania jest
tam obce [i wykryte]" jest **nieprawdziwe wobec własnej implementacji**.

**Co dokładnie widzi warstwa 3.** Pętla (w. 338-382) łapie odczyt tylko wtedy, gdy:
(a) token to `T_STRING` z **15-elementowej listy** `$czytajace` = `input, query,
post, string, get, all, only, except, json, collect, validate, header, cookie,
has, filled` (w. 331-332), ORAZ (b) poprzedza go `T_OBJECT_OPERATOR` (`->`), ORAZ
(c) odbiorcą jest żądanie. **Dwa idiomatyczne sposoby odczytu pola w Laravelu
wypadają poza to:**

1. **Dostęp tablicowy** `$request['zaklecie']` — nie ma tokenu metody ani `->`,
   więc pętla go nie zobaczy NIGDY. `Request implements ArrayAccess`, więc to
   w pełni legalny odczyt pola.
2. **Metoda spoza listy** — `str`, `boolean`, `integer`, `float`, `date`, `enum`,
   `whenHas`, `keys`, dynamiczna właściwość `$request->zaklecie` (magiczne `__get`).
   `str('zaklecie')` zwraca `Stringable` z wartością pola — i nie ma go na liście.

**Co zmierzone (stos `gabinet-r10z`, drzewo przywracane kopią po każdym wariancie).**
Mechanizm wstawiony na POCZĄTKU ciała `powrot()`, w ZADEKLAROWANYM pliku
z allowlisty, na ZADEKLAROWANEJ trasie `/auth/callback`, bez nowej kolumny, bez
nowej trasy, bez funkcji krypto (reużyta kolumna `users.nazwa_wyswietlana`, zapis
przez legalną fasadę `SesjaKonta::zaloz`):

```php
$podane = $request['zaklecie'] ?? null;              // WARIANT A: dostęp tablicowy
if (is_string($podane) && $podane !== '') {
    $konto = DB::table('users')->where('nazwa_wyswietlana', $podane)->first();
    if ($konto !== null) {
        SesjaKonta::zaloz($request, ['sub' => $konto->keycloak_sub, 'role' => ['pacjent']]);
        return redirect('/');
    }
}
```

```
WARIANT A — $request['zaklecie'] (dostęp tablicowy):
   WaskieGardloZapisuTozsamosciTest   → 5 passed  (WARSTWA 3 ZIELONA)
   SiatkaPomiarowaTozsamosciTest      → 3 passed
   BrakWlasnychHaselTest              → 9 passed
   PEŁNA SUITA                        → 2 skipped, 301 passed (2170 assertions)  ← identycznie jak bazowo
   Larastan (level max)               → [OK] No errors
   Pint                               → PASS 104 files

WARIANT B — $request->str('zaklecie') (metoda spoza 15-elementowej listy):
   WaskieGardloZapisuTozsamosciTest   → WARSTWA 3 ZIELONA (8 passed w parze z SiatkaPomiarowa)
   Pint                               → PASS 104 files
```

**DOWÓD, że mechanizm realnie loguje** (jądro HTTP w kontenerze, konto o znanych
wartościach w bazie, każdy przypadek w OSOBNYM procesie PHP dla izolacji sesji;
wiersz kasowany po pomiarze):

```
POPRAWNY  (/auth/callback?zaklecie=SekretDowoduR10)  status=302  has(konta)=TAK  {"sub":"sub-dowod-r10","role":["pacjent"]}
BŁĘDNY    (/auth/callback?zaklecie=ZLE-HASLO)         status=302  has(konta)=NIE  null
BRAK      (/auth/callback)                            status=302  has(konta)=NIE  null
```

Trzy wiersze, nie jeden: różnica POPRAWNE / BŁĘDNE / BRAK dowodzi, że to jest
realne własne hasło (mechanizm sprawdza poświadczenie), a nie „loguje wszystkich".
Uwaga o izolacji: pierwszy pomiar w JEDNYM procesie dawał TAK/TAK/TAK — to był
przeciek sesji między żądaniami w tym samym procesie (wada przyrządu, nie systemu);
rozbicie na trzy osobne procesy dało czyste TAK/NIE/NIE.

**KONTROLA POZYTYWNA PRZYRZĄDU — ten sam mechanizm przez metodę Z listy:**

```
$podane = $request->input('zaklecie');   (zamiast $request['zaklecie'])
   WaskieGardloZapisuTozsamosciTest → 1 failed, 4 passed
       „CALLBACK OIDC CZYTA Z ŻĄDANIA COŚ SPOZA SWOJEGO KONTRAKTU: wiersz N: ->input(zaklecie)"
```

Czyli warstwa 3 UMIE zapalić — gdy odczyt jest metodą z listy. Zielone w wariantach
A i B NIE bierze się z zepsutego testu, tylko z tego, że przyrząd nie patrzy na
dostęp tablicowy ani na metody spoza listy.

**Dlaczego żadna inna kontrola tego nie łapie (mechanika, każda zmierzona).**
- **SiatkaPomiarowa** jawnie `continue`-uje trasę `/auth/callback` (w. 368-370) —
  czyli dokładnie tę, w której siedzi `powrot()`. Nie sonduje jej z definicji.
- **Warstwa 1** szuka ZAPISU sesji z kluczem `konta` poza fasadą; mechanizm pisze
  przez legalną `SesjaKonta::zaloz` → niewidoczny.
- **Warstwa 2** wymaga, by `SesjaKonta::zaloz` wołać tylko z `powrot()`; wstawka
  JEST w `powrot()` → nadal legalne.
- **BrakWlasnychHasel**: brak nowej kolumny/trasy/prymitywu krypto → 9 passed.
  (Ten sam plik w komentarzu 12.08 dokumentuje wektor `===` na `nazwa_wyswietlana`
  jako wcześniej obalony — to jego rdzeń.)
- **Perturbacje** `gardlo_para/naglowek/all` wstawiają ZAPIS (`session()->put('konta'`)
  do kontrolera — łapany przez warstwę 1. Żadna perturbacja nie odtwarza „legalny
  zapis przez fasadę + nielegalny ODCZYT przez dostęp tablicowy w `powrot()`".

**Odtworzenie (dokładne polecenia).**
Klon przypięty do `528adc3` (usuń refy potomne), stos przez
`bash skrypty/bramka.sh --projekt <p> --zostaw`. W
`backend/app/Http/Controllers/LogowanieController.php`:
dodaj `use Illuminate\Support\Facades\DB;` i wstaw powyższy blok na początku ciała
`powrot()` (końce linii LF). Następnie w kontenerze `app`, w `/srv/gabinet/backend`:
`./vendor/bin/pest tests/Feature/WaskieGardloZapisuTozsamosciTest.php` →
**5 passed** (WARSTWA 3 zielona); `./vendor/bin/pest` → **301 passed**;
`./vendor/bin/phpstan analyse --no-progress` → **No errors**;
`./vendor/bin/pint --test` → **PASS**.
Kontrola pozytywna: zamień `$request['zaklecie']` na `$request->input('zaklecie')`
→ WARSTWA 3 **1 failed**.

**Dlaczego to znalezisko, a nie znana granica.** Autor nazwał warstwę 3 w mapie
(`ODPOWIEDZ-069` §8: „nadal LISTĄ; klasa »lista zamiast pomiaru« ma jeszcze
oddech") — ale odniósł to do listy DOZWOLONYCH parametrów (`code`, `state`),
czyli do troski „gdy kontrakt OIDC urośnie, urośnie i lista". **Zmierzona dziura
jest inna i głębsza:** to lista METOD ODCZYTU (`$czytajace`) w mechanizmie
WYKRYWAJĄCYM oraz jego ślepota na dostęp tablicowy. Tego autor nie nazwał, a §2
mówi wprost „zamyka warstwa 3". Zgodnie z regułą `ZLECENIE-067` („jeśli którąś
[nazwaną granicę] da się wykorzystać w tym środowisku — to znalezisko, nie znana
granica") i z kryterium `ZLECENIE-071` („znaleziskiem jest … rozjazd opisu ze
stanem") — to jest znalezisko. Klasa jest DOKŁADNIE ta sama, którą runda 8 i 9
zamykały („lista zamiast pomiaru"), przeniesiona o jedno piętro: z „nazwy pola"
na „sposób odczytu".

**Kierunek naprawy (wektor z pomiaru, nie zalecenie na papierze).** Warstwa 3
powinna mierzyć NIEZMIENNIK, nie odpytywać listy: „czy `powrot()` odwołuje się do
`$request` (albo `request()`) w jakikolwiek sposób prowadzący do odczytu pola
INNEGO niż `code`/`state`" — obejmując dostęp tablicowy (`T_VARIABLE $request`
+ `[`) i dowolną metodę na żądaniu, nie tylko 15 nazwanych. Alternatywa tańsza,
ale słabsza: domknąć listę o pozostałe API `Request` I o dostęp tablicowy —
z jawnym zapisem w nagłówku kontroli, że to nadal LISTA (czyli dług, nie
zamknięcie klasy). Dopóki tego nie ma, twierdzenie §2 „zamyka warstwa 3"
powinno brzmieć „zamyka warstwa 3 dla odczytu metodą z listy `$czytajace`;
dostęp tablicowy i metody spoza listy — NIE".

---

## 4. Pomiar rozstrzygający — świeży subagent, własny klon i stos, bez mojego kontekstu

Świeży subagent (bez mojego kontekstu i bez informacji, jakiego wyniku „się
spodziewam") postawił własny klon `D:/tmp/gabinet-r10sub/klon` na `528adc3`,
własny stos (`gabinet-r10sub`, porty 8160/55510/56460) i wykonał pomiary od zera.
Odtworzył R10-1 wariant A (`$request['zaklecie']`):

```
3 testy celowane:            17 passed / 0 failed (64 asercje) — WARSTWA 3 ZIELONA
pełna suita:                 301 passed, 2 skipped (2170 asercji) — IDENTYCZNIE jak bazowo
Larastan:                    No errors
Pint --test:                 PASS 104 files
DOWÓD HTTP (3 procesy):      poprawny=TAK · bledny=NIE · brak=NIE
KONTROLA POZYTYWNA input():  WARSTWA 3 CZERWONA (1 failed), „wiersz 65: ->input(zaklecie)"
drzewo po przywróceniu:      git status --porcelain PUSTE, HEAD=528adc3
```

**Rozbieżność między moim pomiarem a pomiarem subagenta co do R10-1: ŻADNA.**
Odtworzone niezależnie, na innym klonie, innym stosie i innych portach.

**Jedna rozbieżność środowiskowa, wyjaśniona:** subagent zaraportował BRAMKĘ jako
CZERWONĄ 1/21 (krok [21] gitleaks „leaks found: 1", 156 commitów) — bo jego klon,
w przeciwieństwie do mojego, NIE przyciął refów potomnych i skan historii zobaczył
cytat z `RUNDA-9-RAPORT.md:340` w commicie `527f1b7`. To jest **ta sama znana
przyczyna** (uzupełnienie 1 architekta), niezwiązana z mutacją: czerwień [21] jest
identyczna na drzewie czystym i zmutowanym, więc nie dotyka werdyktu R10-1. Na
`528adc3` z przyciętymi refami krok [21] jest zielony (mój przebieg: 148 commitów,
`no leaks found`). Subagent sam to sprostował: „bramka jako całość CZERWONA … ale
także na czystym drzewie i wyłącznie z powodu gitleaks".

---

## 5. Weryfikacja wąskości D-5 (na prośbę architekta, uzupełnienie 2) — NIE jest znaleziskiem

D-5 (`.gitleaks.toml`, commit `11da17e`) zwalnia cytat zmyślonego sekretu
`GOCSPX-9f2b…c07 (SKRÓCONE 19.08 — pełna wartość zapalała krok [21])` z commita `527f1b7`. Sprawdziłem trzy
kryteria wąskości POMIAREM (klon `d5test` na czubku `11da17e`):

```
[A] skan czubka:                        156 commits scanned, no leaks found   (wartość zwolniona)
[INSTRUMENT] INNA wartość w formie przypisania, nowy commit:  leaks found: 1  (skaner żyje)
[C] DOKŁADNIE zwolniona wartość D-5 w NOWYM commicie:         leaks found: 2  (zapala!)
      trafienie: docs/przyneta-d5val.md @ 9d9d3b8 (commit ≠ 527f1b7)
```

Czyli wyjątek jest **commit-scoped**: ta sama wartość w commicie innym niż
`527f1b7` **zapala** skaner. Weryfikacja tekstu wpisu:
`targetRules = ["generic-api-key"]` (jedna reguła), `condition = "AND"` (wszystkie
trzy kryteria naraz), `regexes = ['GOCSPX-9f2b…c07 (SKRÓCONE 19.08 — pełna wartość zapalała krok [21])']`
(jedna wartość), `commits = ["527f1b7…" ]` — **pełne 40-znakowe SHA**, nie skrót
(lekcja R7-5). Warunek znoszący z terminem: `LISTA-SCALENIOWA-F1.md` O-2b wiąże
D-5 z D-4 jednym terminem i mówi wprost „Jeżeli O-2/O-3 usunie tylko jeden z dwóch
wpisów — to jest ZNALEZISKO". **Wyjątek NIE jest szerszy, niż architekt deklaruje.
Nie jest znaleziskiem.**

Uwaga (nie znalezisko, sygnał dla architekta): w `LISTA-SCALENIOWA-F1.md` etykieta
„D-5" występuje DWA razy w różnych znaczeniach — O-2b („D-5 — cytat sekretu")
oraz O-6 („Automatyzacja podłóg (D-5 / R-C)"). Kolizja etykiety w dokumencie
ponad moim obiektem; nie mierzyłem jej głębiej, odnotowuję do rozstrzygnięcia.

---

## 6. Rozbieżność form warunku zamrożenia (zgłaszam wg WYTYCZNE)

Dwie formy warunku zamrożenia w obiegu:
- `ZLECENIE-071` (pierwotna): `528adc3..HEAD -- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md'` → PUSTO.
- Uzupełnienie 2 (nowsza): dokłada `':(exclude).gitleaks.toml'`.

Na czubku `11da17e` **forma pierwotna jest naruszona** (`.gitleaks.toml` zmieniony
w D-5), a **forma nowsza jest spełniona**. Zgodnie z WYTYCZNE („wykonaj nowsze,
zgłoś rozbieżność") wykonuję nowszą i zgłaszam: różnica to JEDEN plik
`.gitleaks.toml` (D-5), jawnie zadeklarowany. Koszt cofnięcia: zero (mój obiekt
to `528adc3`, gdzie D-5 nie istnieje). **Nie jest to znalezisko rundy 10** — jest
to znany, zadeklarowany dług z warunkiem znoszącym.

---

## 7. CZEGO NIE SPRAWDZIŁEM (sekcja obowiązkowa)

1. **Odczyt przez dynamiczną WŁAŚCIWOŚĆ** `$request->zaklecie` (magiczne `__get`)
   oraz przez pozostałe metody spoza listy (`boolean`, `integer`, `enum`, `date`,
   `whenHas`, `keys`) — zmierzyłem `str()` i dostęp tablicowy; pozostałe formy
   NALEŻĄ do tej samej klasy R10-1, ale ich osobno NIE mierzyłem.
2. **Mechanizm wewnątrz `SesjaKonta::zaloz()`** czytający żądanie — rozważałem
   (warstwa 3 patrzy tylko na `powrot()`), ale uznałem za nieosiągalny jako
   alt-login (warstwa 2 wiąże `zaloz` z `powrot`, a `powrot` woła je po walidacji
   OIDC) i **nie mierzyłem**.
3. **`odczyt-przyczyn.py` / D-2 w trybie DYNAMICZNYM** — zapadka statyczna
   (`SUFIT_NIEROZROZNIAJACYCH=0`) zielona w suicie; „14 wywołań ZGODNE-ROZROZNIA"
   przyjęte bez własnego przebiegu dynamicznego. Ta sama pozycja co w rundach 8-9.
4. **D-4 przez PRZYNĘTĘ w nowym commicie** (base64 `hello-world-…` poza czwórką
   zwolnionych) — sprawdziłem D-4 statycznie (`AND`, pełne SHA); przynęty NIE
   wstrzykiwałem. (D-5 sprawdziłem dynamicznie — §5.)
5. **`KlamraSkryptowTest` — skany `File::files(skrypty)` NIEREKURENCYJNE** (w. 39,
   219, 524) — stan bez zmian od rundy 8, nie mierzyłem ponownie; dziś nic z
   podkatalogów nie ucieka.
6. **`TwierdzeniaKomentarzyTest`** (D-3) — poza bramką, nie uruchamiałem osobno;
   potwierdziłem tylko, że to on daje `2 skipped`.
7. **Granice 1 i 2 SiatkiPomiarowej** (ciasteczko z innej trasy, zapis do innego
   magazynu sesji) — nie próbowałem ich wykorzystać; są nazwane w kodzie i strukturalnie
   pokryte przez wąskie gardło.
8. **Wyścig SIGTERM→SIGKILL na żywej bramce** — nie odtwarzałem; sprawdziłem tylko
   skutek na zielonym przebiegu (znacznik zdjęty, drzewo czyste).
9. **Współbieżność** (`CLAUDE.md` §6, 100 równoczesnych żądań) — poza suitą, zakres F3.
10. **CI (GitHub Actions)** — nie uruchamiałem; bramka mierzona wyłącznie lokalnie.
11. **Kontrakty wobec `konta/`, `hub/`, `helpdesk/`** — poza zasięgiem rundy;
    cytaty B7/B8/BLK-22/§ przyjęte za wierne.
12. **Merytoryka retencji F1** — przeczytałem `ZwolnieniaRetencjiTest`,
    `RejestrRetencji`, `ZadanieRetencji`; warunki znoszące egzekwowane (nie prozą),
    selekcja/weryfikacja osobnymi zapytaniami — nie znalazłem wady, ale nie
    mierzyłem mutacjami poza tym, co robią perturbacje `retencja`, `retencja_wykonanie`.
13. **Kolizja etykiety „D-5"** w `LISTA-SCALENIOWA-F1.md` (O-2b vs O-6) — odnotowana,
    nie mierzona; dokument ponad `528adc3`.
14. **Migracje/schemat** poza tym, co mierzy bramka (`ModelDanychTest` zielony).

---

## 8. Zakres pokryty — dla jawności

Zmierzone: pełna bramka OD ZERA (22 kroki, 301/2170 RÓWNO z podłogami, kod 0,
`no leaks found` po przycięciu refów) na czystym klonie `528adc3`; drugi,
niezależny przebieg na drugim klonie/projekcie/portach; PEŁNY zestaw perturbacji
(52 kontrole / 35 scenariuszy / 0 pominiętych, kod 0) na trzecim projekcie compose,
policzony niezależnie (35 nagłówków, 52 ✓, 0 ✗); warunek zamrożenia sprawdzony
dwustronnie (obie formy); wszystkie pięć zamknięć `ODPOWIEDZ-069` (wąskie gardło
3-warstwowe, R9-2, R9-3, R9-4, R9-5) z kontrolą pozytywną I negatywną przyrządu;
JEDNO znalezisko (R10-1) z żywym mechanizmem, dowodem skutku przez jądro HTTP
w trzech wariantach i kontrolą pozytywną przyrządu, odtworzone niezależnie przez
świeżego subagenta bez rozbieżności; wąskość D-5 zweryfikowana pomiarem
(commit-scoped, przynęta w nowym commicie zapala).

**Zbieżność rund:** 11 → 15 → 12 → 29 → 9 → 2 → 5 → **1**.
Znalezisko nie jest nawrotem napraw rundy 9: warstwy 1 i 2 wąskiego gardła bronią
się pomiarowo, a R10-1 leży **o krok dalej** — w warstwie 3, w miejscu, które
autor nazwał jako mające „oddech", ale opisał w §2 jako zamknięte.

**Faza F1/F0 pozostaje OTWARTA — jedno znalezisko. Runda nie kończy się zerem.**
Fazę zamyka wyłącznie runda z zerem znalezisk (D-2026-08-07-16) — kryterium nie łagodzę.
