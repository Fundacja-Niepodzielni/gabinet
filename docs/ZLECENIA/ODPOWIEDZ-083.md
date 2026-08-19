# ODPOWIEDZ-083 — RUNDA 13 wykonana

**Od:** sesja WERYFIKATOR · **Do:** architekt · **Data:** 19.08.2026
**W odpowiedzi na:** `ZLECENIE-083` (runda 13, SHA `b60c53a`)
**Raport w całości:** `docs/rundy/RUNDA-13-RAPORT.md`

---

## 0. Jednym zdaniem

**JEDNO ZNALEZISKO (WYSOKA) — faza F1/F0 pozostaje OTWARTA.** Bramka na `b60c53a`
zielona 22/22 i zgodna z Twoją deklaracją co do KAŻDEJ liczby (320/2261, podłogi RÓWNO
320/2261, 66 kontroli / 49 scenariuszy / 0 pominiętych); pięć perturbacji R12 zapala z badanej
przyczyny; allowlista wyjątków PUSTA (zmierzone); obie połowy przyrządu działają.
**Ale naprawa R12-1 ma BRZEG — ósme piętro tej samej klasy: skaner lekserowy odtwarza nazwę
narzędzia tylko ze SKLEJENIA BEZPOŚREDNIEGO (`'unse'.'rialize'`). Sklejenie tych samych
literałów PRZEZ ZMIENNE (`$a='unse'; $b='rialize'; $f=$a.$b;`) omija skaner, forguje tożsamość,
a cała bramka milczy. To NIE jest granica „nazwa z żądania" — wartość nie pochodzi z żądania,
więc warstwa 3 jej nie widzi.**

---

## 1. Pomiary — liczby, nie „przeszło"

```
BRAMKA OK — 22 kroków, 0 nieudanych           (czysty klon b60c53a, OD ZERA, kod 0 WPROST)
Tests: 2 skipped, 320 passed (2261 assertions)
WYKONANO 320 (podłoga 320) · sprawdzono 2261 (podłoga 2261)   RÓWNO
Pint PASS 106 files · Larastan [OK] No errors · gitleaks: no leaks (156 commitów)
2 pominięte: oba TwierdzeniaKomentarzyTest (dług D-3)

PERTURBACJE OK — 66 kontroli (49 scenariuszy, pominiętych: 0), kod 0
   niezależnie policzone: 49 nagłówków „=== PERTURBACJA", 66 × ✓, 0 × ✗

drugi, niezależny przebieg bramki na drugim klonie/projekcie/portach: 320 passed (2261)
```

**Twoja deklaracja z `ODPOWIEDZ-082` — POTWIERDZONA co do każdej liczby**
(22/22, 320/2261, podłogi RÓWNO 320/2261, 66 kontroli / 49 scenariuszy / 0 pominiętych).
Podłogi odczytane z `podlogi.sh` (`MINIMUM_TESTOW=320`, `MINIMUM_ASERCJI=2261`), nie zacytowane.
Warunek zamrożenia SPEŁNIONY: `b60c53a` JEST czubkiem, `git diff` poza `docs/` PUSTY.

---

## 2. Weryfikacja zamknięcia R12-1 — §3 zlecenia, każda kontrola

| kontrola | wynik |
|---|---|
| **5 perturbacji negatywnych** (`r12_sklejenie`, `r12_zmienna`, `r12_backslash`, `r12_refleksja_property`, `r12_wektor_calosc`) | KAŻDA zapala z badanej przyczyny — `--przyczyna "narzędzie omijające konstruktory"` (komunikat asercji) + dowód mutacji. ✓ |
| **pozytywna — allowlista PUSTA** | skan produkcji (`app/routes/bootstrap/config`) skanerem → **0 trafień**. Zmierzone, nie założone. ✓ |
| **przyrządu (obie połowy)** | 8 pisowni wzorcowych wykrytych; 4 formy niewinne (`json_decode`, `var_export`, `$next($request)`, sklejenie komunikatu) milczą. Ani ślepy, ani nadgorliwy — **w zakresie, w którym patrzy**. ✓ |

Naprawa R12-1 broni się w zakresie, w którym patrzy. Jej BRZEG opisuje R13-1.

---

## 3. Znalezisko

| # | waga | rzecz |
|---|---|---|
| **R13-1** | **WYSOKA** | **Skaner lekserowy odtwarza nazwę tylko ze SKLEJENIA BEZPOŚREDNIEGO — sklejenie przez zmienne omija go; tożsamość sforgowana, bramka milczy.** `Kod::wywolaniaOmijajaceKonstruktor` (`sklejLiteraly`) łączy tylko sąsiednie `T_CONSTANT_ENCAPSED_STRING` przez `.`; NIE śledzi wartości zmiennych. `$a='unse'; $b='rialize'; $f=$a.$b; $f($ładunek)` → skaner `[]` (a `'unse'.'rialize'` → `['unserialize']`). Także `.=`, `implode`, `strrev`, `sprintf`, heredoc, `new $zmienna` sklejona — wszystkie `[]`. Plik na allowliście (`LogowanieController::powrot`, czyta pole KONTRAKTOWE `code`) odtwarza `TozsamoscSesji` deserializacją o nazwie sklejonej przez zmienne i woła `SesjaKonta::zaktualizuj` (którego warstwa 2 NIE ogranicza) — **cała bramka zielona** (15/15 wąskie gardło w tym „WARUNEK UTRZYMUJĄCY", Larastan No errors, Pint PASS, pełna suita 320 passed identycznie jak bazowo), a runtime daje `sub=ATAK-…`, `role=["koordynator","admin-fundacja"]`, `access_exp` w przyszłości. Komentarz `Kod.php` („sklejenie … są objęte") i test („dowie się od bramki") NIEPRAWDZIWE dla sklejenia przez zmienne. Klasa R12-1/R6A-4: kontrola KSZTAŁTU ma brzeg. |

Pełne odtworzenie, dowód skutku, kontrola przyrządu i tabela ślepych pisowni — raport §3.

**Kluczowe: to NIE jest przewidziane przez Ciebie ósme piętro.** Wskazałeś „nazwa z żądania,
warstwa 3". Ósme piętro leży gdzie indziej — w samym rozpoznaniu kształtu przez skaner,
dla nazwy budowanej ze STAŁYCH literałów, BEZ udziału żądania, więc bez żadnego backstopu
w warstwie 3.

---

## 4. Pomiar rozstrzygający

Świeży subagent, bez mojego kontekstu, na tym samym stosie (`gabinet-r13b`) odtworzył R13-1
wariantem `$a.$b`. **Rozbieżności co do znaleziska: ŻADNE.**

```
skaner:  $a.$b → []   (direct-concat → ["unserialize"], json_decode → [])
guard:   WaskieGardloTozsamosci + Zapisu → 15 passed (WARUNEK UTRZYMUJĄCY ZIELONY)
statyka: phpstan [OK] No errors · pint PASS
suita:   2 skipped, 320 passed (2261)
runtime: status 302, sub=ATAK-KOORDYNATOR-SUB, role=["koordynator","admin-fundacja"]
drzewo:  PUSTE po przywróceniu
```

---

## 5. CZTERY Twoje twierdzenia — jedno OBALONE (priorytet rundy)

- **„Granica »nazwa funkcji z żądania« pokryta warstwą 3"** → **OBALONE (R13-1).** Ósme piętro
  nie jest granicą „nazwa z żądania". Nazwa ze STAŁYCH literałów przez zmienne omija skaner,
  a warstwa 3 jej nie widzi (nie ma odczytu pola spoza kontraktu). Twój framing „jedyny brzeg
  = nazwa z żądania, pokryty warstwą 3" jest fałszywy: brzeg jest NIEZALEŻNY od żądania.
- **`new $zmienna` z nazwą klasy zbudowaną inaczej niż literałem** → **POTWIERDZONA DZIURA**
  (część R13-1): `$k='Reflection'.'Cla'; $k.='ss'; new $k()` → skaner `[]`.
- **Skaner sam używa refleksji w pliku testowym — właściwa granica zasięgu** → **NIE OBALONE.**
  `composer.json`: `Tests\` WYŁĄCZNIE w `autoload-dev`, poza autoloadem produkcyjnym. Brak
  drogi z testu do produkcji. Granica trzyma.
- **Lista deserializatorów jawna — rozszerzenie spoza listy przeszłoby** → **STOI, ale
  SUBSUMOWANE przez R13-1.** Prawdziwe, lecz drugorzędne: nawet `unserialize` Z listy jest
  omijalny konstrukcją nazwy. Słaby punkt to DOPASOWANIE NAZWY, nie kompletność listy. Nie
  liczę osobno.

---

## 6. Odrzucone po pomiarze — NIE są znaleziskami

- **Granica zasięgu skanera (test → produkcja)** — `Tests\` autoload-dev; broni się.
- **Warstwy 3 i 4** — milczą SŁUSZNIE przy R13-1 (wektor nie czyta pola spoza kontraktu, nie
  dotyka `zaloz`); nie są wadliwe, po prostu nie są backstopem dla nazwy ze stałych.
- **Allowlista wyjątków pusta** (skan produkcji → 0), **warunek zamrożenia** (`b60c53a` czubek,
  `git diff` poza `docs/` PUSTY), **D-3/D-4/D-5 OBA obecne, podłogi RÓWNO** — §2 raportu.
- **Pięć perturbacji R12, kontrola przyrządu (obie połowy)** — bronią się.
- **Lista deserializatorów jawna** — jawna granica, subsumowana przez R13-1; nie liczę osobno.

---

## 7. Sprzeczne polecenia i koszt cofnięcia (pozycja stała WYTYCZNE)

**Brak.** `ZLECENIE-083` nie koliduje z wcześniejszym poleceniem, `CLAUDE.md` ani
`docs/DECYZJE.md`. `b60c53a` JEST czubkiem, więc warunek zamrożenia ma jedną formę. Koszt
cofnięcia po mojej stronie: **zero** — mutacje zakładane i cofane KOPIĄ pliku w klonach
efemerycznych; `git status --porcelain` PUSTE po każdej.

---

## 8. Higiena

Zakaz commitowania w repozytorium projektu **utrzymany** — jedyne zapisy to raport i ten plik,
oba niezacommitowane. Nie dotykałem `docs/specyfikacja/` ani `docs/testy/` (inne sesje).
W klonach efemerycznych nie commitowałem. Stosy dewelopera `gabinet` i `gabinet-perturbacje`
NIETKNIĘTE (sprawdzone `docker compose ls` przed i po). Wszystkie moje stosy (`gabinet-r13a`,
`-r13b`, `-r13c`) zgaszone `down -v`; klony do skasowania; zero pozostałości `gabinet-r13*`.

**Zbieżność rund: 29 → 9 → 2 → 5 → 1 → 3 → 1 → 1.**

**Fazy nie zamykam. Kryterium „zero znalezisk" nie łagodzę.** R13-1 to ósme piętro tej samej
klasy co rundy 6–12: skaner lekserowy zamknął siódme (denylista pisowni), a jego własny sposób
odtwarzania nazwy — tylko ze sklejenia BEZPOŚREDNIEGO — jest kontrolą opartą na rozpoznaniu
KSZTAŁTU, która ma brzeg. Zgodnie z Twoim kryterium (`ODPOWIEDZ-082` §9): ósme piętro w tym
obszarze → **decyzja właściciela, nie kolejna naprawa kształtu.** Kolejne rozszerzanie skanera
(`$a.$b`, `.=`, `implode`, `strrev`, stała klasowa…) to ta sama denylista, o piętro wyżej —
brzeg będzie zawsze. Kontrola patrząca na SKUTEK zamiast na leksykę leży poza zakresem tej
rundy pomiarowej.
