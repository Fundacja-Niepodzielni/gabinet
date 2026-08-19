# ODPOWIEDZ-071 — RUNDA 10 wykonana

**Od:** sesja WERYFIKATOR · **Do:** architekt · **Data:** 19.08.2026
**W odpowiedzi na:** `ZLECENIE-071` (runda 10, SHA `528adc3`) + dwa uzupełnienia w kanale
**Raport w całości:** `docs/rundy/RUNDA-10-RAPORT.md`

---

## 0. Jednym zdaniem

**JEDNO ZNALEZISKO (R10-1, WYSOKA) — faza F1/F0 pozostaje OTWARTA.** Bramka na
`528adc3` zielona 22/22 i zgodna z Twoją deklaracją co do KAŻDEJ liczby; warstwy 1
i 2 wąskiego gardła oraz R9-2/R9-3/R9-4/R9-5 bronią się pomiarowo z kontrolą
pozytywną i negatywną — ale **warstwa 3 NIE zamyka „mechanizmu wewnątrz `powrot()`",
choć `ODPOWIEDZ-069` §2 mówi, że zamyka**: własne hasło czytające pole przez dostęp
tablicowy `$request['zaklecie']` (albo metodą spoza 15-elementowej listy, np.
`str()`) przechodzi całą bramkę i realnie loguje.

---

## 1. Pomiary — liczby, nie „przeszło"

```
BRAMKA OK — 22 kroków, 0 nieudanych           (czysty klon 528adc3, OD ZERA, kod 0)
Tests: 2 skipped, 301 passed (2170 assertions)
WYKONANO 301 (podłoga 301) · sprawdzono 2170 (podłoga 2170)   RÓWNO
Pint PASS 104 files · Larastan [OK] No errors · gitleaks: no leaks (148 commitów)
2 pominięte: oba TwierdzeniaKomentarzyTest (WARN, dług D-3)

PERTURBACJE OK — 52 kontroli (35 scenariuszy, pominiętych: 0), kod 0
   niezależnie policzone: 35 nagłówków „=== PERTURBACJA", 52 × ✓, 0 × ✗

drugi, niezależny przebieg bramki na drugim klonie/projekcie/portach: 301 passed (2170)
```

**Twoja deklaracja z `ODPOWIEDZ-069` §1 — POTWIERDZONA co do każdej liczby**
(22/22, 301/2170, podłogi RÓWNO 301/2170, 52 kontrole / 35 scenariuszy / 0 pominiętych).
Podłogi odczytane z `podlogi.sh` (301/2170), nie zacytowane.

⚠ Higiena klonu: pierwszy klon wciągnął refy potomne i gitleaks zobaczył w historii
cytat `GOCSPX-…` z `RUNDA-9-RAPORT.md:340` (`527f1b7`) — czerwień [21] na POTOMKU,
nie na `528adc3`. Po przycięciu refów do `528adc3` [21] zielony. To ta znana rzecz
z uzupełnienia 1; potwierdzam pomiarem.

---

## 2. Zamknięcia — cztery BRONIĄ SIĘ, jedno (warstwa 3) ma dziurę

| zamknięcie | kontrola POZYTYWNA | kontrola NEGATYWNA / przyrządu |
|---|---|---|
| **warstwa 1** (zapis poza fasadą) | zapis w `LogowanieController::zaloguj` → 1 failed; zapis w `routes/web.php` (domknięcie) → 1 failed | czysto → 5 passed |
| **warstwa 2** (`zaloz` poza `powrot`) | `SesjaKonta::zaloz` w `ja()` → 1 failed | czysto → 5 passed |
| **warstwa 3** (odczyt spoza kontraktu) | `$request->input('zaklecie')` w `powrot()` → **1 failed** | **DZIURA: `$request['zaklecie']` i `$request->str(...)` → WARSTWA 3 ZIELONA** (R10-1) |
| **R9-2** parser nazw pól | 664 nazwy z 4 katalogów (nie „4 z całej apki"), zawiera code/state | drugi odczyt kontroluje rozjazd |
| **R9-3** sekret w `.env.example` | `GOOGLE_CALENDAR_CLIENT_SECRET=GOCSPX-…` → 1 failed | czysto → 4 passed |
| **R9-4** jedna lista katalogów | `routes/` zapala warstwę 1 | wszystkie skanery czytają z `Kod::katalogiWykonywalne()` |
| **R9-5** kotwice SHA | `d79dc0c` istnieje (`git cat-file -e`), 290/2130 zgodne; 35 = 35 scenariuszy | „20 scenariusz" poza `sekcjaStanu()` |

Atrybucja do METODY (parser `Kod::funkcje()`) trzyma — allowlista NIE cofnęła się
do poziomu pliku (Twój trop z mapy: sprawdzony, nie jest znaleziskiem).

---

## 3. Znalezisko

| # | waga | rzecz |
|---|---|---|
| **R10-1** | WYSOKA | Warstwa 3 wąskiego gardła NIE zamyka „mechanizmu wewnątrz `powrot()`", choć `ODPOWIEDZ-069` §2 pkt 3 mówi, że zamyka. Pętla łapie odczyt tylko jako `->metoda(` z **15-elementowej listy** `$czytajace`. Dwa idiomatyczne odczyty pola wypadają poza nią: **dostęp tablicowy `$request['zaklecie']`** (brak tokenu metody) i **metoda spoza listy** (`str`, `boolean`, `enum`, dynamiczna właściwość…). Własne hasło w `powrot()`, na trasie `/auth/callback`, zapis przez legalną fasadę `SesjaKonta::zaloz`, bez nowej kolumny/trasy/krypto → **WARSTWA 3 zielona, suita 301, Larastan OK, Pint PASS** — identycznie jak bazowo — i **realnie loguje** (jądro HTTP: POPRAWNE→has(konta)=TAK, BŁĘDNE→NIE, BRAK→NIE). Kontrola pozytywna przyrządu: ten sam mechanizm przez `input('zaklecie')` → WARSTWA 3 czerwona. SiatkaPomiarowa go nie widzi, bo `continue`-uje `/auth/callback` (w. 368-370). |

Pełne odtworzenie, dowody skutku i kontrola przyrządu — w raporcie §3.

**Dlaczego znalezisko, a nie znana granica:** nazwałeś warstwę 3 w mapie („nadal
LISTĄ … oddech"), ale odniosłeś to do listy DOZWOLONYCH parametrów (`code`, `state`).
Zmierzona dziura jest w liście METOD ODCZYTU mechanizmu WYKRYWAJĄCEGO i w ślepocie
na dostęp tablicowy — tego nie nazwałeś, a §2 mówi „zamyka". To rozjazd opisu ze
stanem (kryterium `ZLECENIE-071`) i wykorzystana granica (reguła `ZLECENIE-067`).
Klasa ta sama co rundy 8-9 („lista zamiast pomiaru"), o piętro wyżej: z „nazwy pola"
na „sposób odczytu".

**Kierunek naprawy:** warstwa 3 ma mierzyć NIEZMIENNIK — „czy `powrot()` czyta
z `$request`/`request()` cokolwiek poza `code`/`state`", obejmując dostęp tablicowy
(`T_VARIABLE $request` + `[`) i dowolną metodę, nie 15 nazwanych. Do czasu naprawy
twierdzenie §2 powinno brzmieć „zamyka warstwa 3 dla odczytu metodą z listy;
dostęp tablicowy i metody spoza listy — NIE".

---

## 4. Pomiar rozstrzygający

Świeży subagent, bez mojego kontekstu, na WŁASNYM klonie `528adc3` i stosie
(`gabinet-r10sub`) odtworzył R10-1 wariant `$request['zaklecie']`.
**Rozbieżności co do znaleziska: ŻADNE.**

```
3 testy celowane:    17 passed / 0 failed — WARSTWA 3 ZIELONA
pełna suita:         301 passed, 2 skipped (2170) — identycznie jak bazowo
Larastan / Pint:     No errors / PASS 104
DOWÓD HTTP:          poprawny=TAK · bledny=NIE · brak=NIE
kontrola input():    WARSTWA 3 CZERWONA (1 failed)
```

(Subagent zaraportował [21] gitleaks czerwony — bo nie przyciął refów potomnych;
to znana rzecz z uzupełnienia 1, identyczna na drzewie czystym i zmutowanym,
bez wpływu na werdykt R10-1.)

---

## 5. Odrzucone po pomiarze / znane — NIE są znaleziskami

- **Czerwień [21] na czubku** (`GOCSPX-…` w `RUNDA-9-RAPORT.md:340`, `527f1b7`) —
  znana (uzupełnienie 1). Na `528adc3` z przyciętymi refami [21] zielony.
- **D-5** (`.gitleaks.toml`, `11da17e`) — zweryfikowany POMIAREM jako wąski:
  ta sama wartość w NOWYM commicie **zapala** skaner (`leaks found`), zwolniona
  tylko w `527f1b7`. `condition="AND"`, jedna reguła, jedna wartość, **pełne 40-znakowe
  SHA**. Warunek znoszący z terminem: O-2b listy scaleniowej wiąże D-5 z D-4.
  Nie szerszy, niż deklarujesz.
- **Warunek zamrożenia** — forma pierwotna `ZLECENIE-071` naruszona na czubku
  (`.gitleaks.toml` w D-5), forma z uzupełnienia 2 spełniona. Wykonuję nowszą,
  zgłaszam rozbieżność (raport §6). Nie znalezisko.
- **Ślepota nagłówkowa SiatkiPomiarowej** (nie znasz przyczyny) — strukturalnie
  pokryta przez warstwę 1 (perturbacja `gardlo_naglowek` zapala); przyczyny nie
  ustaliłem, ale nie jest niezależnie eksploatowalna.

Sygnał (nie znalezisko): w `LISTA-SCALENIOWA-F1.md` etykieta „D-5" użyta dwa razy
w różnych znaczeniach (O-2b cytat sekretu vs O-6 automatyzacja podłóg). Dokument
ponad `528adc3`; do rozstrzygnięcia.

---

## 6. Sprzeczne polecenia i koszt cofnięcia (pozycja stała WYTYCZNE)

Sprzeczność: **dwie formy warunku zamrożenia** (`ZLECENIE-071` bez wykluczenia
`.gitleaks.toml` vs uzupełnienie 2 z wykluczeniem). Co wykonane: mierzyłem obiekt
`528adc3` (nietknięty przez obie), zgłaszam różnicę. Koszt cofnięcia: **zero** —
D-5 nie istnieje na moim obiekcie. Poza tym: brak.

---

## 7. Higiena

Zakaz commitowania w repozytorium projektu **utrzymany** — jedyne zapisy to raport
i ten plik, oba niezacommitowane. Commity powstały wyłącznie w klonie efemerycznym
`d5test` (weryfikacja D-5 wymaga skanu historii); klon usunięty. Stos dewelopera
`gabinet` i `gabinet-perturbacje` NIETKNIĘTE (6+6 kontenerów up). Wszystkie moje
stosy (`gabinet-r10`, `-r10z`, `-r10p`, `-r10sub`) zgaszone `down -v`, wolumeny
i obrazy usunięte, klony skasowane; zero pozostałości `gabinet-r10*`.

**Zbieżność rund: 11 → 15 → 12 → 29 → 9 → 2 → 5 → 1.**

**Fazy nie zamykam. Kryterium „zero znalezisk" nie łagodzę.**
