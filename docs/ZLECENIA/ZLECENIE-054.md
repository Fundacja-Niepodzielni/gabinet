# ZLECENIE-054 · 12.08.2026 · OD sesji KOD-F1 DO architekta

**Meldunek gotowości do rundy 7.** Sekwencja z `ODPOWIEDZ-048` §2 wykonana:
strażnik (R-B) → pełna bramka OD ZERA → ten meldunek. Plik zamyka pozycję (S-3).

**Fazy nie zamykam — zamyka ją runda.**

---

## 1 · SHA ZAMROŻONE DO RUNDY

```
551c0c8c1e425e469a7f9f3b2189ba0bdd337877
```
Gałąź `faza-1-retencja`, krótko `551c0c8`. **To jest SHA KODU** — ostatni commit
dotykający `backend/` i `skrypty/`.

⚠ **HEAD jest DALEJ niż to SHA i tak ma być.** Sam ten meldunek jest commitem,
więc zapisanie w nim „HEAD = X" byłoby identyfikatorem SAMOZWROTNYM: nieaktualnym
od chwili zapisu (`WYTYCZNE-PRACY.md`, reguła o plikach stanu). Zamiast wartości,
która się starzeje, zapisuję **warunek sprawdzalny**:

```
git diff --stat 551c0c8..HEAD -- backend/ skrypty/     # MUSI być puste
```

Dopóki to polecenie nic nie wypisuje, wszystko po `551c0c8` jest **wyłącznie
dokumentacją**, a runda może mierzyć HEAD albo `551c0c8` — kod jest ten sam.
Gdyby kiedykolwiek coś wypisało, zgłoszę to osobnym zleceniem z nowym SHA kodu.

**Wynik bramki na tym SHA, przebieg OD ZERA:**

```
BRAMKA OK — 22 kroków, 0 nieudanych          (kod wyjścia 0)
Tests: 2 skipped, 267 passed (2026 assertions)
WYKONANO 267 testów (podłoga: 265)
Larastan (level max): [OK] No errors
Pint: PASS (99 plików)
gitleaks: no leaks found
```

W drzewie roboczym jest jedna niezacommitowana zmiana — `docs/DECYZJE.md`,
**praca innej sesji**, której świadomie nie dotykam.

---

## 2 · ⚠ ZNANE DŁUGI — co weryfikator ma wiedzieć, zanim zacznie

Poniższe są **opisane i otwarte**. Nie są znaleziskami rundy 7; znalezieniem
byłoby coś, czego tu nie ma.

### D-1 · Luka §2 przez PONOWNE UŻYCIE istniejącej kolumny — POZYCJA JAWNA ATAKU

Zgodnie z `ODPOWIEDZ-048` R-A. **Wektor, w całości:** mechanizm własnych haseł
**bez żadnej funkcji kryptograficznej** — porównanie `===` wartości z żądania
z sekretem zapisanym w kolumnie **już obecnej** w `OCZEKIWANY_SCHEMAT`
(zmierzone na `users.nazwa_wyswietlana`). Przechodzi obie siatki:

- allowlista prymitywów (`BrakWlasnychHaselTest`) nie widzi go, bo nie ma tam
  żadnego prymitywu;
- allowlista schematu nie widzi go, bo **kolumna nie jest nowa**.

Zmierzone przez niezależnego weryfikatora 12.08: `9 passed`, wszystkie kontrole
zielone. **Zalecenia świadomie nie wpisałem** — dwa razy w tym repozytorium
zalecenie bez pokrycia dało *zieloną kontrolę nad otwartą dziurą*.

Prośba do rundy zgodnie z Twoim brzmieniem: (a) odtworzyć atak na czystym klonie,
(b) ocenić, czy istnieje trzecia siatka **o charakterze pomiarowym, nie
deklaratywnym**, która go łapie.

### D-2 · Dwie allowlisty `--przyczyna` nadal nie rozróżniają

Dług zjechany 7 → **2**, pilnowany zapadką `PrzyczynyPerturbacjiTest` (sufit 2,
kontrola zabrania zostawiania zapasu). Blokuje je brak komunikatów asercji
w dwóch testach. Wzorzec równy nazwie testu spełnia się w **każdym** przebiegu,
także zielonym — więc te dwa scenariusze mają dziś zawężenie pozorne.

### D-3 · `TwierdzeniaKomentarzyTest` zdjęty z bramki

Kontrola D3, zdjęta 09.08 po weryfikacji helpdesku: **14 obejść na 15 prób**.
To jedno z dwóch pominięć w suicie. **W jego cieniu leżało moje znalezisko
z 12.08**: dwie adnotacje `@dowod:` wskazywały klasy, które nie istnieją —
nic tego nie pilnowało. Domknąłem to osobnym egzekutorem, ale sama kontrola D3
czeka na przeprojektowanie (wiązanie świadka z ROLĄ tekstu, nie ze słowami).

### D-4 · Wyjątek w `.gitleaks.toml` z warunkiem znoszącym

Zawężony do trzech commitów niosących przynętę perturbacji. Przyczyna usunięta
w drzewie (wartość składana w czasie działania). **Warunek znoszący:** przy
scalaniu F1 do `main`, gdy drzewo będzie ciche, historię zakresu przepisać
i wpis USUNĄĆ. Kopia gałęzi: `kopia-przed-filtrem-12-08`.

### D-5 · Podłogi bramki nadal ręczne

Zgodnie z Twoim R-C: dług ze spłatą przy merge. Jedno źródło (`skrypty/podlogi.sh`)
już jest; automatyczny zjazd z ostatniego zielonego przebiegu — przy konsolidacji.

---

## 3 · Strażnik commita (R-B) — WYKONANY

Trzy warunki odmowy, **każdy z własną kontrolą negatywną**, plus kontrola
pozytywna i rozróżnienie znacznika osieroconego:

| kontrola | co sprawdza |
| --- | --- |
| pozytywna | poprawny stan → **przepuszcza** (inaczej strażnik odmawiający zawsze zostałby wyłączony) |
| (a) | żywy znacznik przebiegu → odmowa |
| (a′) | znacznik **osierocony** → odmowa z instrukcją zdjęcia i ostrzeżeniem, żeby najpierw obejrzeć drzewo |
| (b) | repozytorium poza zakresem sesji → odmowa |
| (c) | plik w indeksie spoza zakresu → odmowa, z wypisaniem plików |
| deny-by-default | brak `.zakres-sesji` → odmowa + **samoczynny szablon** |
| obejście | `GABINET_STRAZNIK=0` działa i jest jawne |

Testy wołają hook **jako program**, w izolowanym repozytorium tymczasowym —
nie sprawdzają jego treści `grep`-em, bo kontrola statyczna nie zastępuje
uruchomienia.

**Zmierzone na sobie:** przy pierwszej próbie po włączeniu strażnik odmówił mi
commita `docs/DECYZJE.md` — pliku innej sesji.

### ⚠ RZECZ OPERACYJNA DO ZAKOMUNIKOWANIA STRUMIENIOM, ZANIM WYJDZIE W PRANIU

Strażnik jest włączony przez `git config core.hooksPath skrypty/git-hooks`,
a **ta konfiguracja jest wspólna dla wszystkich worktree**. Pozostałe trzy
strumienie trafią więc na odmowę przy pierwszym commicie i będą musiały dopisać
własny `.zakres-sesji` (szablon powstaje sam, koszt kilkunastu sekund).

To jest zamierzone — blokowanie cudzych plików w commicie było celem — ale
**nie powinno ich zaskoczyć**. Proszę o przekazanie tego w zleceniu do TESTY,
SPEC-UMOWA i pozostałych.

---

## 4 · Czego ta sesja NIE zrobiła

- **Nie przepisałem historii** dla wyjątku gitleaks (D-4) — `filter-branch`
  odmawia przy niezacommitowanych zmianach, a w drzewie leży praca innej sesji.
  Schowanie jej groziło utratą.
- **Nie zmierzyłem odczytu DYNAMICZNego** nowych wzorców `--przyczyna`
  (`odczyt-przyczyn.py` uruchamia kilkanaście przebiegów na współdzielonym stosie).
  Statycznie rozróżniają.
- **Nie tknąłem szkieletów grup A/B/E/G/I** — zakres sesji TESTY, gałąź
  `testy-plan-f2` nietknięta (`ZLECENIE-048` §5).

---

## 5 · Rzecz, którą uważam za najważniejszą w tej partii

**Bramka po strażniku zapaliła się na MNIE — i to była najlepsza wiadomość dnia.**

`KlamraSkryptowTest` złapał w moim nowym kodzie `trap ... EXIT INT TERM`
w jednej linii, czyli **dokładnie tę formę, którą to repozytorium zmierzyło jako
wadliwą** (nie przerywa przebiegu przy sygnale). Poprawna forma stała dwieście
linii wyżej w `perturbacje.sh` — w pliku, który tego samego dnia edytowałem,
z komentarzem, który tego samego dnia czytałem.

To jest **siódma instancja tej rodziny w jednej sesji** i **pierwsza złapana
przez mechanizm, a nie przez mój przegląd**. Czyli dokładnie to, po co strażnik
powstał — z tą różnicą, że złapała mnie kontrola, która istniała już wcześniej.

Wniosek, który proponuję do zasad przekrojowych: **kontrola warta utrzymania to
ta, która łapie autora znającego regułę.** Sześć wcześniejszych złamań złapałem
przeglądem, czyli przypadkiem; to jedno złapał mechanizm — i tylko o tym jednym
wiem na pewno, że złapałby także następnego.
