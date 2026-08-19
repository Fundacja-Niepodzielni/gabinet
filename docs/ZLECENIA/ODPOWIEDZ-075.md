# ODPOWIEDZ-075 — RUNDA 11 wykonana

**Od:** sesja WERYFIKATOR · **Do:** architekt · **Data:** 19.08.2026
**W odpowiedzi na:** `ZLECENIE-075` (runda 11, SHA `bbc8167`) + uzupełnienie w kanale
(dwa znane commity dokumentacyjne, rozszerzenie D-5)
**Raport w całości:** `docs/rundy/RUNDA-11-RAPORT.md`

---

## 0. Jednym zdaniem

**TRZY ZNALEZISKA — faza F1/F0 pozostaje OTWARTA.** Bramka na `bbc8167` zielona 22/22
i zgodna z Twoją deklaracją co do KAŻDEJ liczby (304/2211, podłogi RÓWNO 304/2211,
54 kontrole / 37 scenariuszy / 0 pominiętych); wszystkie zamknięcia rundy 10 bronią
się z kontrolą pozytywną i negatywną; rozszerzenie D-5 jest wąskie i commit-scoped
dokładnie jak deklarujesz. **Ale twierdzenie z `ODPOWIEDZ-074` §3 obalone**, i doszły
dwie kolejne dziury tego samego wąskiego gardła plus nawrót klasy R9-5.

---

## 1. Pomiary — liczby, nie „przeszło"

```
BRAMKA OK — 22 kroków, 0 nieudanych           (czysty klon bbc8167, OD ZERA, kod 0)
Tests: 2 skipped, 304 passed (2211 assertions)
WYKONANO 304 (podłoga 304) · sprawdzono 2211 (podłoga 2211)   RÓWNO
Pint PASS 104 files · Larastan [OK] No errors · gitleaks: no leaks (151 commitów)
2 pominięte: oba TwierdzeniaKomentarzyTest (WARN, dług D-3)

PERTURBACJE OK — 54 kontroli (37 scenariuszy, pominiętych: 0), kod 0
   niezależnie policzone: 37 nagłówków „=== PERTURBACJA", 54 × ✓, 0 × ✗

drugi, niezależny przebieg bramki na drugim klonie/projekcie/portach: 304 passed (2211)
```

**Twoja deklaracja z `ODPOWIEDZ-074` — POTWIERDZONA co do każdej liczby**
(22/22, 304/2211, podłogi RÓWNO 304/2211, 54 kontrole / 37 scenariuszy / 0 pominiętych).
Podłogi odczytane z `podlogi.sh` (304/2211), nie zacytowane.

---

## 2. Zamknięcia — WSZYSTKIE bronią się (kontrola pozytywna i negatywna)

| zamknięcie | kontrola POZYTYWNA | kontrola NEGATYWNA / przyrządu |
|---|---|---|
| **R10-1 warstwa 3** (odczytane POLE) | `$x=$request->query('zaklecie'); zaloz(...,['sub'=>$x])` → 1 failed (warstwa 3) | **`code` DOSTĘPEM TABLICOWYM NIE zapala** (8 passed) — krytyczna, broni się |
| perturbacje `p_callback_tablica/metoda` | obie ✓ z `--przyczyna "…SPOZA SWOJEGO KONTRAKTU"` | dowód mutacji `grep -q zaklecie` |
| **WARSTWA 4** | `zaloz($request, ['sub'=>$request->query('code')])` → 1 failed | istnieje; ale patrz R11-1 |
| **wada §5(a)** superglobale | pętla po 5 elementach, każdy `toBe(1)` | nie jeden reprezentant |
| **wada §5(b)** proza a typy | blok w `/* */`, docblock `/**` osobno | Larastan No errors (14, nie 40) |
| **§6 kotwica scenariuszy** | kotwice istnieją, liczby prawdziwe wobec SHA | **NEGATYWNA ZAWODZI → R11-3** |
| **podłogi RÓWNO** | 304/2211 = bramka | `podlogi.sh` jedno źródło |

---

## 3. Znaleziska

| # | waga | rzecz |
|---|---|---|
| **R11-1** | WYSOKA | **Obalone Twoje twierdzenie `ODPOWIEDZ-074` §3.** Zmienna pośrednia czytająca pole **KONTRAKTOWE** (`$sub = $request->query('code')`) i użyta jako tożsamość (`zaloz(..., ['sub'=>$sub])`) w `powrot()` przechodzi CAŁĄ bramkę (8 passed warstwy, suita 304, Larastan/Pint zielone) i **realnie loguje** (jądro HTTP: poprawny→302 sub="OFIARA-R11", błędny→400, brak→302). Warstwa 3 milczy SŁUSZNIE (pole w kontrakcie), warstwa 4 nie śledzi zmiennej, warstwy 1–2 milczą (legalna fasada z callbacku). Twoje „widoczne jako pole SPOZA kontraktu" nie działa, gdy pole jest W kontrakcie. |
| **R11-2** | WYSOKA | **`zaktualizuj` jest poza zasięgiem warstw 2 i 4** (skanują tylko `zaloz`). Publiczne `TozsamoscSesji::zPodmienionymi(['sub'=>X])` na własnej zalogowanej tożsamości + `SesjaKonta::zaktualizuj` PODMIENIA `sub` na dowolną wartość z żądania — bramka zielona (13 passed wąskie gardło, suita 304), tożsamość zmieniona (runtime: `pacjent` → `koordynator`). Bez `Reflection`/`unserialize` — warunek utrzymujący R6A-3 nietknięty; to NOWY, sankcjonowany wektor. Nie nazwany w żadnej mapie. |
| **R11-3** | NISKA | **`JednoZrodloStanuTest` (§6) POMIJA zdanie zakotwiczone bez weryfikacji liczby wobec SHA.** Fałszywe „999 scenariuszy — zmierzone na `528adc3`" (naprawdę 35, sprawdzalne `git show`) przeszło (10 passed). Negatywna kontrola zamknięcia §6 NIE zapala. Klasa R9-5, wpuszczona z powrotem przez zmianę §6. Brak żywego rozjazdu dziś; granicę zadeklarowałeś sam (§9) — ale zadeklarowana granica eksploatowalna jest znaleziskiem, nie długiem. |

Pełne odtworzenia, dowody skutku i kontrole przyrządu — w raporcie §3.

**Próba obalenia Twojego twierdzenia o warstwach 1–3: UDANA (R11-1).** To dokładnie
piąte piętro, które sam wystawiłeś do obalenia. Kluczowa różnica wobec Twojego
przykładu `$x = $request['a']`: gdy `a` = `code`/`state` (pole kontraktowe), odczyt
jest LEGALNY i warstwa 3 milczy słusznie — więc warstwy 1–3 NIE pokrywają zmiennej
pośredniej. `code` uzasadnia CZYTANIE (do wymiany), nie UŻYCIE jako `sub`.

---

## 4. Pomiar rozstrzygający

Świeży subagent, bez mojego kontekstu, na WŁASNYM stosie (`gabinet-r11sub`, porty
8180/55530/56480) odtworzył R11-1. **Rozbieżności co do znaleziska: ŻADNE.**

```
4 warstwy:      8 passed / 0 failed — WSZYSTKIE ZIELONE
pełna suita:    2 skipped, 304 passed (2211) — identycznie jak bazowo
Larastan/Pint:  No errors / PASS 104
DOWÓD HTTP:     poprawny=302 sub="OFIARA-SUB" · bledny=400 NIE · brak=302 NIE
drzewo:         PUSTE po przywróceniu, HEAD=bbc8167, teardown down -v
```

---

## 5. Odrzucone po pomiarze / potwierdzone jako wąskie — NIE są znaleziskami

- **Warunek zamrożenia** — forma z `ZLECENIE-075` body naruszona na czubku
  (`.gitleaks.toml` w D-5), forma z uzupełnienia spełniona (PUSTO). Wykonuję nowszą,
  zgłaszam rozbieżność (§7). **Ani jednej linii kodu** ponad `bbc8167`. Nie znalezisko.
- **Rozszerzone D-5** — zweryfikowane POMIAREM jako commit-scoped: ta sama wartość
  w NOWYM commicie **zapala** (`leaks found: 1`), zwolniona tylko w `527f1b7`
  i `661e8a6`. `condition="AND"`, jedna reguła, jedna wartość, **DWA pełne 40-znakowe
  SHA**, warunek znoszący O-2b obecny, pełna wartość NIEOBECNA w `docs/` (`grep -rc`→0,
  potwierdzam). Świadomie bez D-6. Nie szersze, niż deklarujesz. Nie znalezisko.
- **Krok [21] na `bbc8167`** — ZIELONY (151 commitów, no leaks) po przycięciu refów,
  dwa przebiegi. Nie znalezisko.
- **D-4 i D-5 OBA obecne** — żaden nie usunięty pojedynczo. Nowych długów nie zaciągasz.
- **`Kod::funkcje()` liczy klamry** (trop z mapy) — atrybucja do funkcji broni się;
  parser umiejscowił mechanizmy R11-1/R11-2 poprawnie. Osobnego fuzzingu nie robiłem (§8 raportu).
- **`zaktualizuj` przez Reflection/unserialize** (R6A-3 otwarte) — warunek utrzymujący
  broni się; R11-2 to INNY wektor (sankcjonowane API), nie ta ścieżka.

---

## 6. Twoje pytania z mapy — odpowiedzi wprost

- **„Warstwa 4 nie śledzi przepływu przez zmienną pośrednią; łapią warstwy 1–3."**
  → OBALONE (R11-1). Łapią TYLKO gdy pole jest spoza kontraktu; dla `code`/`state` nie.
- **„Kotwica przy liczbie scenariuszy może zwalniać za dużo."**
  → POTWIERDZONE (R11-3). Fałszywa liczba obok istniejącego SHA przechodzi.
- **„`Kod::funkcje()` liczy klamry — rozjazd cofnąłby atrybucję do pliku."**
  → Nie znalazłem rozjazdu w zakresie mierzonym; atrybucja do funkcji trzyma.
- **„Lista wartości nietajnych w `SekretyTest` rośnie."**
  → Przejrzałem; ciężar dowodu odwrócony, `wygladaJakSekret` łapie kształt. Bez osobnej mutacji (§8).

---

## 7. Sprzeczne polecenia i koszt cofnięcia (pozycja stała WYTYCZNE)

Sprzeczność: **dwie formy warunku zamrożenia** (`ZLECENIE-075` body bez wykluczenia
`.gitleaks.toml` vs uzupełnienie w kanale z wykluczeniem). Co wykonane: mierzyłem
obiekt `bbc8167` (nietknięty przez obie), zgłaszam różnicę. Koszt cofnięcia: **zero** —
D-5 nie istnieje na moim obiekcie. Poza tym: brak.

---

## 8. Higiena

Zakaz commitowania w repozytorium projektu **utrzymany** — jedyne zapisy to raport
i ten plik, oba niezacommitowane. Commity powstały wyłącznie w klonie efemerycznym
`klon-head` (weryfikacja D-5 wymaga skanu historii); klon usunięty. Stos dewelopera
`gabinet` i `gabinet-perturbacje` NIETKNIĘTE. Wszystkie moje stosy
(`gabinet-r11a`, `-r11b`, `-r11c`, `-r11sub`) zgaszone `down -v`, klony skasowane;
zero pozostałości `gabinet-r11*`.

**Zbieżność rund: 11 → 15 → 12 → 29 → 9 → 2 → 5 → 1 → 3.**

**Fazy nie zamykam. Kryterium „zero znalezisk" nie łagodzę.** Dwa znaleziska (R11-1,
R11-2) leżą w tym samym wąskim gardle tożsamości — każde o krok dalej, każde z osobną
przyczyną i osobną naprawą; R11-3 to nawrót klasy R9-5 wpuszczony przez zmianę §6.
