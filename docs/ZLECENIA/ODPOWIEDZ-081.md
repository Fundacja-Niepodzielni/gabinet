# ODPOWIEDZ-081 — RUNDA 12 wykonana

**Od:** sesja WERYFIKATOR · **Do:** architekt · **Data:** 19.08.2026
**W odpowiedzi na:** `ZLECENIE-081` (runda 12, SHA `7a8c44d`)
**Raport w całości:** `docs/rundy/RUNDA-12-RAPORT.md`

---

## 0. Jednym zdaniem

**JEDNO ZNALEZISKO (WYSOKA) — faza F1/F0 pozostaje OTWARTA.** Bramka na `7a8c44d`
zielona 22/22 i zgodna z Twoją deklaracją co do KAŻDEJ liczby (318/2251, podłogi RÓWNO
318/2251, 61 kontroli / 44 scenariusze / 0 pominiętych); zamknięcia §1, §2, §4, §5 bronią
się z kontrolą pozytywną i negatywną; **żadnego z czterech Twoich twierdzeń nie obaliłem.**
**Ale §3 jest obalone: prywatny konstruktor MA obejście — deserializację — a strażnik tej
drogi (`warunek utrzymujący R6A-3`) jest denylistą, którą da się ominąć. To jest siódme
piętro.**

---

## 1. Pomiary — liczby, nie „przeszło"

```
BRAMKA OK — 22 kroków, 0 nieudanych           (czysty klon 7a8c44d, OD ZERA, kod 0 WPROST)
Tests: 2 skipped, 318 passed (2251 assertions)
WYKONANO 318 (podłoga 318) · sprawdzono 2251 (podłoga 2251)   RÓWNO
Pint PASS 106 files · Larastan [OK] No errors · gitleaks: no leaks (155 commitów)
2 pominięte: oba TwierdzeniaKomentarzyTest (dług D-3)

PERTURBACJE OK — 61 kontroli (44 scenariusze, pominiętych: 0), kod 0
   niezależnie policzone: 44 nagłówki „=== PERTURBACJA", 61 × ✓, 0 × ✗

drugi, niezależny przebieg bramki na drugim klonie/projekcie/portach: 318 passed (2251)
```

**Twoja deklaracja z `ODPOWIEDZ-079` — POTWIERDZONA co do każdej liczby**
(22/22, 318/2251, podłogi RÓWNO 318/2251, 61 kontroli / 44 scenariusze / 0 pominiętych).
Podłogi odczytane z `podlogi.sh` (318/2251), nie zacytowane. Warunek zamrożenia
SPEŁNIONY: `7a8c44d` JEST czubkiem, `git diff` poza `docs/` PUSTY.

---

## 2. Zamknięcia — §1/§2/§4/§5 bronią się; §3 obalone (R12-1)

| zamknięcie | POZYTYWNA | NEGATYWNA / przyrządu |
|---|---|---|
| **§1 podstawa zaufania z KONFIGURACJI** | token obcym kluczem → `ok=false`, `kontrole['signature']='fail'` (odmowa z podpisu) | token naszym kluczem → `ok=true`; perturbacja `wymagania_wolajacego` ✓ |
| **§2 kontrola pyta o ISTOTĘ** | dowolna PUBLICZNA metoda przyjmująca `array` = znalezisko | perturbacja `wymagania_wolajacego` czerwień |
| **§3 `final`** | `isFinal()` = true/true | perturbacje `roszczenia_final`, `roszczenia_ctor`, `podmienionymi` ✓ |
| **§3 prywatny konstruktor „bez obejścia"** | — | **OBALONE → R12-1: deserializacja omija konstruktor, strażnik ślepy** |
| **§4 nonce/kid/cache JWKS** | warstwa 3 zapala odczyt spoza kontraktu; obcy klucz → `signature=fail` | perturbacje `nonce`, `wzmacniacz` ✓ |
| **§5 DECYZJE.md** | trzy wpisy obecne; D-12-04 SPEC-UMOWA, przeniesiony wiernie i oznaczony | — |

---

## 3. Znalezisko

| # | waga | rzecz |
|---|---|---|
| **R12-1** | **WYSOKA** | **Strażnik ściany typu jest DENYLISTĄ do ominięcia — deserializacja forguje tożsamość, bramka milczy.** `WaskieGardloTozsamosciTest` „warunek utrzymujący R6A-3" skanuje kod produkcyjny regexem `\bunserialize\s*\(|\bnewInstanceWithoutConstructor\s*\(|new\s+ReflectionClass\s*\(` — trzy pisownie. Sklejenie `$f='unse'.'rialize'; $f($ładunek)` (a także `new \ReflectionClass` z backslashem, dynamiczna nazwa metody, `ReflectionProperty` spoza listy) **omija skaner**. Plik na allowliście (`LogowanieController::powrot`, czyta pole KONTRAKTOWE `code`) odtwarza `TozsamoscSesji` deserializacją i woła `SesjaKonta::zaktualizuj` — **cała bramka zielona** (318 passed, Larastan No errors, Pint 106 plików, sam `WaskieGardloTozsamosciTest` 5 passed), a runtime daje `sub=ATAK-…`, `role=["koordynator","admin-fundacja"]`. Pisemna obietnica kontroli („dowie się od bramki") NIEPRAWDZIWA; runda 11 §6 („warunek utrzymujący broni się") obalona. Klasa R6A-4: denylista przegrywa z wariantem spoza listy. |

Pełne odtworzenie, dowód skutku i kontrola przyrządu (literał zapala / sklejenie milczy) —
w raporcie §3.

**Próba obalenia §3 zlecenia („czy prywatny konstruktor naprawdę nie ma obejścia — fabryki,
deserializacja, klonowanie"): UDANA drogą DESERIALIZACJI.** Fabryki, klonowanie, klasa
potomna, kontener zależności, podmieniona konfiguracja — sprawdzone i BRONIĄ SIĘ. Obejście
jest jedno: deserializacja (równoważnie refleksja), a jego strażnik jest denylistą z dziurą.

---

## 4. Pomiar rozstrzygający

Świeży subagent, bez mojego kontekstu, na tym samym stosie (`gabinet-r12b`) odtworzył R12-1
wariantem `unserialize`. **Rozbieżności co do znaleziska: ŻADNE.**

```
WaskieGardloTozsamosciTest:  5 passed (warunek utrzymujący ZIELONY)
Larastan / Pint:             No errors / PASS
runtime:                     sub=ATAK-KOORDYNATOR-777  role=["koordynator","admin-fundacja"]  exp_future=TAK
drzewo:                      PUSTE po przywróceniu
```

---

## 5. CZTERY Twoje twierdzenia — WSZYSTKIE OBRONIŁY SIĘ (nie obaliłem żadnego)

- **`?string $refreshToken` „nie rozstrzyga o tożsamości"** → **NIE OBALONE.** Refresh token
  czytany wyłącznie z własnej sesji albo z ciała odpowiedzi IdP; `sub` zawsze ze
  zweryfikowanego access tokenu; `przelicz` KOŃCZY sesję przy rozjeżdżającym się `sub`.
  Sesji innego podmiotu nim nie uzyskałem.
- **`wszystkie()` „wynik weryfikacji, nie droga ominięcia"** → **NIE OBALONE** zwykłym kodem
  (obiektu nie da się wytworzyć bez podpisu). Deserializacją forguje się i ten obiekt — ale
  to R12-1, nie słabość `wszystkie()`.
- **`KontaOidc` jako parametr → wektor przez konfigurację** → **NIE OBALONE.** Brak ścieżki
  żądanie→`config('konta.*')` w kodzie produkcyjnym (jedyny `config([…])` to `mail.default`).
  Wektor prowadzi przez `.env`/kontener, jak deklarujesz.
- **Zegar POZA F1** → **nie znalezisko.** Ważności tokenu nie da się przesunąć bez zegara
  hosta (`tolerancja_zegara` z `.env`, `now()` systemowy). Zgodnie z Twoją decyzją.

R12-1 jest ORTOGONALNE — nie korzysta z żadnego z tych czterech wektorów.

---

## 6. Odrzucone po pomiarze — NIE są znaleziskami

- **Ściana typu przez fabryki / klonowanie / klasę potomną / kontener zależności /
  podmienioną konfigurację** — wszystkie bronią się (zmierzone/wywnioskowane, §5 raportu).
- **Cztery twierdzenia autora** — nie obaliłem żadnego (§5 wyżej).
- **Warunek zamrożenia** (`7a8c44d` czubek, `git diff` poza `docs/` PUSTY), **D-3/D-4/D-5
  OBA obecne, podłogi RÓWNO, DECYZJE.md (trzy wpisy, atrybucja D-12-04)** — §2 raportu.
- **`Bramki`/eskalacja bez forgingu, rejestr sesji, back-channel logout** — czytane, bez
  osobnych sond mutacyjnych (§7 raportu — czego nie sprawdziłem).

---

## 7. Sprzeczne polecenia i koszt cofnięcia (pozycja stała WYTYCZNE)

**Brak.** `ZLECENIE-081` nie koliduje z wcześniejszym poleceniem, `CLAUDE.md` ani
`docs/DECYZJE.md`. Warunek zamrożenia ma w rundzie 12 jedną formę (7a8c44d jest czubkiem),
więc rozbieżność z rundy 11 nie występuje. Koszt cofnięcia po mojej stronie: **zero** —
mutacje zakładane i cofane KOPIĄ pliku w klonach efemerycznych; `git status --porcelain`
PUSTE po każdej.

---

## 8. Higiena

Zakaz commitowania w repozytorium projektu **utrzymany** — jedyne zapisy to raport i ten
plik, oba niezacommitowane. W klonach efemerycznych nie commitowałem. Stos dewelopera
`gabinet` i `gabinet-perturbacje` NIETKNIĘTE. Wszystkie moje stosy (`gabinet-r12a`, `-r12b`,
`-r12c`) do zgaszenia `down -v`, klony do skasowania; zero pozostałości `gabinet-r12*`.

**Zbieżność rund: 29 → 9 → 2 → 5 → 1 → 3 → 1.**

**Fazy nie zamykam. Kryterium „zero znalezisk" nie łagodzę.** R12-1 to siódme piętro tej
samej klasy co rundy 6–11: ściana typu zamknęła szóste, a jej STRAŻNIK — denylista
Reflection/unserialize — jest kontrolą opartą na rozpoznaniu KSZTAŁTU kodu, która ma brzeg,
i brzeg da się przekroczyć. Naprawa: allowlista przez lekser (jak `Kod`/`Zrodlo` dla zapisu
tożsamości), nie regex literałów nad tekstem.
