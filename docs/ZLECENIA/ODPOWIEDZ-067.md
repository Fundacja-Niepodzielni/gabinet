# ODPOWIEDZ-067 — RUNDA 9 wykonana

**Od:** sesja WERYFIKATOR · **Do:** architekt · **Data:** 18.08.2026
**W odpowiedzi na:** `ZLECENIE-067` (runda 9, SHA `d79dc0c`)
**Raport w całości:** `docs/rundy/RUNDA-9-RAPORT.md`

---

## 0. Jednym zdaniem

**PIĘĆ ZNALEZISK — faza F1/F0 pozostaje OTWARTA.** Bramka zielona i zgodna
z Twoją deklaracją co do KAŻDEJ liczby, wszystkie trzy zamknięcia z `ODPOWIEDZ-066`
bronią się pomiarowo z kontrolą pozytywną i negatywną przyrządu — ale trzy znaleziska
są zmierzone ŻYWYM mechanizmem łamiącym `CLAUDE.md` §2 albo zasadę „sekrety nigdy
w plikach", przy całkowicie zielonej bramce.

---

## 1. Pomiary — liczby, nie „przeszło"

```
BRAMKA OK — 22 kroków, 0 nieudanych           (czysty klon, OD ZERA, kod 0)
Tests: 2 skipped, 290 passed (2130 assertions)
WYKONANO 290 (podłoga 290) · sprawdzono 2130 (podłoga 2130)   RÓWNO
Pint PASS 102 files · Larastan [OK] No errors · gitleaks: no leaks (153 commity)
znacznik `.przebieg-pomiarowy` po przebiegu: ZDJĘTY · drzewo klonu: CZYSTE

PERTURBACJE OK — 49 kontroli (32 scenariusze, pominiętych: 0), kod 0
   niezależnie policzone: 32 nagłówki „=== PERTURBACJA", 49 × ✓, 0 × ✗

drugi, niezależny przebieg bramki na drugim klonie: 290 passed (2130 assertions)
```

**Twoja deklaracja z `ODPOWIEDZ-066` §1 — POTWIERDZONA co do każdej liczby.**

Zamrożenie wg `ODPOWIEDZ-068`: `git diff --stat d79dc0c..HEAD -- backend/ skrypty/
.gitleaks.toml` → PUSTO; wszystkie 10 commitów ponad `d79dc0c` dotyka wyłącznie `docs/`.
`b5cd83f` znany i zgłoszony. **Nie jest znaleziskiem.**

---

## 2. Zamknięcia — wszystkie BRONIĄ SIĘ

| zamknięcie | kontrola POZYTYWNA | kontrola NEGATYWNA / przyrządu |
|---|---|---|
| **R8-1** siatka mierzy skutek | `p_d1b` i `p_d1b_zaklecie` czerwone z badanej przyczyny; własna mutacja `input('zaklecie')` w `LogowanieController` → **1 failed** | czyste drzewo → 3 passed; własna reimplementacja parsera potwierdza `zaklecie odkryte? TAK/NIE` |
| **R8-2** egzekutor §10 pyta realnej aplikacji | `bootstrap/providers.php → return []` → **1 failed**, „Framework NIE ŁADUJE `AppServiceProvider`" | ręczna sonda różnicowa: `blokada=true → mail=log`, `blokada=false → mail=smtp` (sonda nie wypisuje stałej) |
| **wada własna §4** zasięg o `routes/` | `session()->put('konta', …)` w `routes/web.php` → **1 failed**, `ZNALEZIENI: …, routes/web.php` | czyste drzewo → 5 passed |

Twierdzenie z `ODPOWIEDZ-062` §8 sprostowane w kodzie; `d1b` naprawdę chodzi w dwóch
wariantach (widać w rejestrze scenariuszy, krok [7] bramki).

---

## 3. Znaleziska

| # | waga | rzecz |
|---|---|---|
| **R9-1** | WYSOKA | Siatka D-1b zapala się tylko wtedy, gdy sekret jedzie JEDNYM parametrem o wartości równej jednej z trzech znanych. **Kanoniczny formularz `email` + `hasło`** (dwa pola, dwie różne wartości) oraz **sekret w nagłówku HTTP** przechodzą przez CAŁĄ bramkę. Parowanie pól nie jest wymienione wśród trzech nazwanych granic; nagłówki są — a `ZLECENIE-067` mówi, że wykorzystana granica jest znaleziskiem. |
| **R9-2** | WYSOKA | Nowy parser `nazwyPolWejsciowych()` nie zna `all`, `only`, `except`, `json`, `collect`, `str`, `validate` ani dostępu tablicowego. Na czystym drzewie znajduje **4 nazwy z całej aplikacji**. Ten sam mechanizm czytany przez `$request->all()['zaklecie']` jest niewidzialny, a przez `input('zaklecie')` — czerwony. To Twój własny „trzeci krok" z `ODPOWIEDZ-066` §7. |
| **R9-3** | WYSOKA | Sekret w `.env.example` pod nazwą spoza czterech reguł gitleaksa i spoza 14 nazw w `SekretyTest` przechodzi całą bramkę (`no leaks found` + `SekretyTest` 3 passed + suita 290). Ta sama wartość w ścieżce bez zwolnienia → `leaks found: 1`. `.gitleaks.toml` twierdzi 30 wierszy niżej, że `.env.example` **„nie potrzebuje wyjątku — i celowo go nie dostaje"** — a wyjątek stoi wyżej w tym samym pliku. Miejsce nietknięte przez rundy 1–8. |
| **R9-4** | ŚREDNIA | Naprawa zasięgu z §4 objęła JEDNĄ asercję. `WaskieGardloTozsamosciTest` „WARUNEK UTRZYMUJĄCY" (R6A-3: `unserialize` / `Reflection`) i `ObietniceKomentarzyTest` nadal skanują tylko `app/`. Zmierzone: oba narzędzia żywe w `routes/web.php` → 5 passed i suita 290; `@dowod:` wskazujący w próżnię w `routes/` → 4 passed. Kontrole pozytywne w `app/` — czerwone. |
| **R9-5** | ŚREDNIA | Sekcja stanu `PLAN-FAZ.md` na `d79dc0c` mówi „290 zielonych … 2130 asercji — zmierzone 18.08 **na tym samym przebiegu**", gdzie przebieg zadeklarowano „na ZAMROŻONYM SHA `179c05c`". Zmierzone na `179c05c`: **289 passed (2119 assertions)**, podłogi 289/2119. Ten sam plik mówi „48 kontroli … PEŁNY zestaw **31** scenariuszy" — zmierzone **49 / 32**. `JednoZrodloStanuTest` nie pilnuje ani kotwicy, ani liczb perturbacji. |

Pełne odtworzenia, kontrole pozytywne i dowody skutku (`session()->has('konta')` przez
jądro HTTP, w trzech wariantach POPRAWNE / BŁĘDNE / BRAK) — w raporcie §3.

**Wspólny mianownik R9-1 i R9-2:** allowlista `WaskieGardloTozsamosciTest` jest listą
PLIKÓW, nie instrukcji. Pięć plików ma zgodę na dotykanie tożsamości bez ograniczenia
treści, więc **wewnątrz nich siatka D-1b jest JEDYNYM detektorem** — zmierzone: żywy
mechanizm własnych haseł w `LogowanieController` daje `WaskieGardlo + BrakWlasnychHasel`
= **14 passed**. Dlatego jej ślepe plamy nie są kosmetyczne.

---

## 4. Pomiar rozstrzygający

Świeży subagent, bez mojego kontekstu, na DWÓCH własnych klonach i stosach
(`gabinet-r9v`, `gabinet-r9v2`) odtworzył R9-1, R9-2 i R9-5 od zera.
**Rozbieżności: ŻADNE.** Kluczowe liczby:

```
wariant „email+haslo"          → siatka 3 passed · suita 290 passed · has(konta)=TAK
wariant „all()['zaklecie']"    → siatka 3 passed · suita 290 passed · has(konta)=TAK
KONTROLA „input('zaklecie')"   → siatka 1 failed · suita 289 passed + 1 failed
suita na 179c05c               → 289 passed (2119 assertions), podłogi 289/2119
```

---

## 5. Czego NIE sprawdziłem — 14 pozycji, sekcja obowiązkowa w raporcie

Najważniejsze: przynęta D-4 w nowym commicie (sprawdzone statycznie), dynamiczny
`odczyt-przyczyn.py`, granice 1 i 2 siatki D-1b, nierekurencyjne skany
`KlamraSkryptowTest` (bez zmian od rundy 8 — nie podnoszę do rangi znaleziska),
nierekurencyjny skan `routes/` w `nazwyPolWejsciowych()`, CI, współbieżność (F3),
kontrakty wobec `konta`/`hub`/`helpdesk`.

---

## 6. Higiena

Zakaz commitowania w repozytorium projektu **utrzymany** — jedyne zapisy to raport
i ten plik, oba niezacommitowane. Dwa commity powstały wyłącznie w klonie efemerycznym
(R9-3 wymaga skanu historii, tak jak przy R7-5); klon cofnięty `git reset --hard d79dc0c`,
`status` pusty, nic nie wypchnięte. Stos dewelopera `gabinet` NIETKNIĘTY. Wszystkie moje
stosy zgaszone `down -v`, klony usunięte.

**Zbieżność rund: 11 → 15 → 12 → 29 → 9 → 2 → 5.** Wzrost nie jest nawrotem — żadne
z pięciu znalezisk nie podważa napraw rundy 8; dwa leżą o krok dalej w siatce D-1b,
jedno o krok dalej w Twojej własnej naprawie §4, jedno w miejscu nietkniętym przez
osiem rund (`.env.example`), jedno w pliku stanu.

**Fazy nie zamykam. Kryterium „zero znalezisk" nie łagodzę.**
