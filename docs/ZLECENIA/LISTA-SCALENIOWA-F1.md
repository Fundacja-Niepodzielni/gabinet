# PROJEKT LISTY SCALENIOWEJ F1 → main

**Autor:** sesja KOD-F1 · **Data:** 12.08.2026 · **Status: PROJEKT DO ZATWIERDZENIA**
**Zlecone:** `ODPOWIEDZ-054` §2 · **Meldunek:** `ZLECENIE-058`

> **Ten plik NIE JEST ZACOMMITOWANY** — i to jest świadome. `ZLECENIE-056` mówi:
> *„commit po tym SHA na tej gałęzi jest sam w sobie znaleziskiem"*. Kanał żyje
> plikami nieśledzonymi (tak pisze dziś każda inna sesja), więc projekt powstaje
> jako plik, a nie jako commit. Do repozytorium wejdzie dopiero przy scaleniu,
> jeżeli architekt go zatwierdzi.

---

## 0 · ⚠ NAJPIERW: DWIE RZECZY, KTÓRE ZMIENIAJĄ PLAN

### 0.1 · Przepisanie historii (D-4) **UNIEWAŻNIA SHA RUNDY 7**

To nie jest przypuszczenie — wynika wprost z zakresu operacji.

`git filter-branch … origin/faza-1-retencja..HEAD` przepisuje **każdy commit
w zakresie**, nadając mu nowy SHA. Zmierzone: `551c0c8` **nie jest na origin**
(`git branch -r --contains 551c0c8` → pusto), czyli **leży wewnątrz zakresu
przepisywania**.

Po operacji `git show 551c0c8` przestaje cokolwiek zwracać. A ten SHA jest cytowany
jako przedmiot pomiaru w `ZLECENIE-054`, `ZLECENIE-056` i będzie w `RUNDA-7-RAPORT.md`.

Reguła z `WYTYCZNE-PRACY.md` mówi, że SHA nazywający **przeszłe zdarzenie** się nie
starzeje. To prawda — ale tylko dopóki pozostaje **rozwiązywalny**. Przepisanie
historii zamienia „runda 7 na `551c0c8`" z odsyłacza w napis.

**Trzy warianty, z kosztami:**

| wariant | co dostajemy | co tracimy |
| --- | --- | --- |
| **A. Przepisać historię** (plan pierwotny) | zero wyjątków w skanerze | SHA rund 6–7 i wszystkie odwołania przestają być rozwiązywalne |
| **B. Zostawić wyjątek zawężony** | każdy SHA nadal rozwiązywalny | czteroliniowy wyjątek na znaną fałszywkę zostaje na stałe |
| **C. Przepisać + ZAPISAĆ MAPĘ stare→nowe** | zero wyjątków **i** odtwarzalność | jedna tabela do utrzymania + dyscyplina przy jej czytaniu |

**Rekomendacja: C.** `filter-branch` sam wypisuje mapowanie do
`.git/filter-branch/map` — wystarczy je **zachować jako dokument sprostowań**,
zanim katalog zniknie. Koszt to jedna tabela; zysk to jedno i drugie naraz.

**Wariant B zostaje na stole** i nie jest wstydliwy: wartość jest zmyśloną
fałszywką (base64 napisu `hello-world-this-is-a-secret`), a wyjątek jest zawężony
do trzech commitów **oraz** jednej reguły **oraz** jednej wartości. Jeżeli architekt
uzna odtwarzalność historii rund za ważniejszą niż czystość konfiguracji skanera —
B jest decyzją, nie zaniechaniem. **Wtedy jednak wymaga kontroli pilnującej,
że wyjątek pozostaje wąski** (dziś nic tego nie pilnuje).

### 0.2 · Zamrożony SHA **NIE JEST WYPCHNIĘTY**

`git branch -r --contains 551c0c8` → pusto. Weryfikator rundy 7 dostał polecenie
„czysty klon wskazanego SHA" — **z origin go nie sklonuje**. Musi klonować
ze ścieżki lokalnej (tak robiły rundy 5–6) albo ktoś musi wypchnąć gałąź.

Wypchnięcie nie dodaje commitów i nie łamie „gałąź stoi", ale **nie jest moją
decyzją** — zgłaszam, nie wykonuję.

---

## 1 · WARUNKI WEJŚCIA — bez nich lista nie startuje

| # | warunek | jak sprawdzić | dlaczego |
| --- | --- | --- | --- |
| **W1** | runda 7 z **zerem** znalezisk | `ODPOWIEDZ-056` + `RUNDA-7-RAPORT.md` | reguła zbieżności `D-2026-08-07-16`; F0 i F1 zamyka wyłącznie runda zerowa |
| **W2** | **cisza w drzewie** — wszystkie strumienie wstrzymane, ich praca zacommitowana albo odłożona | `git status --short` → pusto | **ZMIERZONE 12.08:** `filter-branch` **odmówił** przez niezacommitowaną pracę innej sesji w `docs/DECYZJE.md`. Bez ciszy operacja O-2 jest niewykonalna, a schowanie cudzej pracy grozi jej utratą |
| **W3** | świeża kopia bezpieczeństwa gałęzi | `git branch kopia-przed-merge-<data>` | `kopia-przed-filtrem-12-08` istnieje, ale opisuje stan sprzed dnia pracy |
| **W4** | bramka zielona na stanie wejściowym | pełny przebieg **od zera** | „była zielona wczoraj" nie jest stanem |

---

## 2 · KOLEJNOŚĆ OPERACJI — i uzasadnienie każdego „dlaczego tu"

### O-1 · Zamrożenie i kopia
**Kto:** architekt · **Wejście:** W1–W4
**Robi:** `git branch kopia-przed-merge-<data>`; zapis SHA stanu wejściowego.
**Dowód:** gałąź kopii istnieje i wskazuje ten sam SHA co HEAD.
**Wycofanie całej listy:** `git reset --hard <kopia>` w dowolnym momencie do O-8.

### O-2 · Przepisanie historii (D-4) — **PIERWSZE WŚRÓD OPERACJI NA COMMITACH**
**Kto:** architekt · **Wejście:** O-1 + decyzja A/B/C z §0.1
**Dlaczego TU, a nie później:** przepisanie zmienia SHA **wszystkiego, co po nim**.
Każda operacja wykonana wcześniej dostałaby nowy SHA, więc wszystkie odwołania
trzeba by prostować **dwa razy** — raz po niej, raz po przepisaniu.
**Robi:**
```
git filter-branch --tree-filter '<usunięcie literału z perturbuj.py>' \
    origin/faza-1-retencja..HEAD
cp .git/filter-branch/map  docs/rundy/MAPA-SHA-<data>.txt     # wariant C
```
**Dowód ukończenia — DWUSTRONNY, nie jednostronny:**
1. `gitleaks detect` **bez** wpisu w `.gitleaks.toml` → `no leaks found`;
2. **przynęta** o prawdziwym kształcie sekretu w `skrypty/perturbuj.py` → nadal zapala
   (bez tego nie wiadomo, czy skaner widzi cokolwiek).
**Ryzyko:** przy wariancie C mapa musi powstać **przed** zniknięciem katalogu
`.git/filter-branch` — to jedyny moment, w którym istnieje.

### O-2b · D-5 — cytat sekretu w raporcie rundy 9 (TEN SAM TERMIN CO D-4)
**Kto:** architekt · **Wejście:** O-2
**Co:** commit `527f1b7` wniósł do historii pełną wartość cytowaną w dowodzie R9-3
(`docs/rundy/RUNDA-9-RAPORT.md`). Przyczyna usunięta w drzewie 19.08 (wartość
skrócona); w `.gitleaks.toml` stoi wąski wyjątek na ten jeden commit.
**Dlaczego razem z D-4:** obie wartości są zmyślone, obie siedzą wyłącznie
w historii i obie znikają tym samym przepisaniem. Rozdzielenie ich znaczyłoby
dwa przepisania historii zamiast jednego.
**Dowód ukończenia — DWUSTRONNY, jak przy D-4:**
1. `gitleaks detect` **bez** obu wpisów w `.gitleaks.toml` → `no leaks found`;
2. przynęta o kształcie sekretu w dokumencie → nadal zapala.

⚠ **Jeżeli O-2/O-3 usunie tylko jeden z dwóch wpisów — to jest ZNALEZISKO.**
Dług, który przeżył własny termin, przestaje być długiem, a staje się stanem.

### O-3 · Usunięcie wpisu z `.gitleaks.toml`
**Kto:** architekt · **Wejście:** O-2 zielone
**Dlaczego TU:** natychmiast po przepisaniu, w tym samym oknie ciszy. Stan pośredni,
w którym wyjątek jest już zbędny, a wciąż stoi, jest dokładnie tym, co po tygodniu
nikt nie odróżni od wyjątku potrzebnego.
**Dowód:** `grep -c aGVsbG8 .gitleaks.toml` → 0 **oraz** skan czysty **oraz** przynęta zapala.

### O-4 · Sprostowanie odwołań do SHA
**Kto:** architekt · **Wejście:** O-2 (mapa)
**Dotyczy:** `ZLECENIE-054`, `ZLECENIE-056`, `RUNDA-7-RAPORT.md`, `PLAN-FAZ.md`.
**Forma:** **sprostowanie jawne**, nie cicha podmiana — ktoś mógł już przeczytać
wersję ze starym SHA. Wzór: *„SHA `551c0c8` został przepisany na `X` przy scaleniu
F1 (mapa: `docs/rundy/MAPA-SHA-<data>.txt`). Zdarzenie »runda 7 na 551c0c8« zachowuje
sens jako nazwa; do `git show` używaj `X`."*

### O-5 · Konsolidacja wpisów D
**Kto:** architekt · **Wejście:** O-4 (SHA już ostateczne)
**Dlaczego TU:** wpisy D cytują SHA. Konsolidacja przed O-2 kazałaby je prostować drugi raz.
**Zakres — pozycje zebrane z kanału:**

| źródło | co wchodzi |
| --- | --- |
| `ODPOWIEDZ-045` §1 | rozstrzygnięcia **Q-1, Q-3, Q-4, Q-8, Q-9, Q-10, Q-12, Q-14, Q-19** (Q-16 **NIE** — czeka na właściciela) |
| `ODPOWIEDZ-045` §1 | **Q-21/Q-22** → etap B: kontrakt operacji API jako pierwsze zadanie F2 |
| `ZLECENIE-049` | **R-1 = 10 minut** (decyzja właściciela, wycofuje „~godzinę" z 09.08) |
| `docs/DECYZJE.md` | `D-2026-08-12-01/02/03` (moje, już wpisane) + `D-2026-08-12-04` (architekta, dziś niezacommitowany) |
| `ZLECENIE-048` §3 | zasada: **„wniosek się broni, uzasadnienie zawierało fałsz"** |
| `ZLECENIE-054` §5 | zasada: **„kontrola warta utrzymania to ta, która łapie autora znającego regułę"** |

**Dowód:** każda pozycja z tabeli ma wpis w `docs/DECYZJE.md` z datą i uzasadnieniem;
`ObietniceKomentarzyTest` przechodzi (egzekwuje, że znaczniki powołane w kodzie mają
świadka).

### O-6 · Automatyzacja podłóg (D-5 / R-C)
**Kto:** sesja kodująca · **Wejście:** O-5
**Dlaczego TU, a nie wcześniej:** to **zmiana kodu**, więc wymaga własnej zielonej
bramki. Wciśnięta między operacje na historii kazałaby przepisywać ją razem z nimi.
**Robi:** zjazd podłóg z ostatniego zielonego pełnego przebiegu, jedno źródło
(`skrypty/podlogi.sh`) już istnieje.
**Dowód — z kontrolą negatywną:** po automatyzacji **usunięcie jednego testu** musi
zapalić bramkę. Bez tego automat może ustawiać podłogę na „ile akurat jest",
co zamienia zapadkę w licznik.

### O-7 · Weryfikacja `.zakres-sesji` per strumień
**Kto:** architekt + każdy strumień · **Wejście:** niezależne od O-2…O-6
**Dlaczego przed merge:** po scaleniu wszystkie strumienie ruszają na `main`,
a strażnik commita musi działać u **każdego** — inaczej pierwsza sesja bez deklaracji
albo go wyłączy, albo zablokuje sobie pracę.
**Robi:** każdy strumień potwierdza w kanale, że ma `.zakres-sesji` i że deklaracja
odpowiada jego rzeczywistemu zakresowi.
**Dowód:** `ZLECENIE-057` rozesłane (wykonane przez architekta) + potwierdzenia
w kanale od TESTY i SPEC-UMOWA.

### O-8 · Pełna bramka OD ZERA na stanie scalanym
**Kto:** sesja kodująca · **Wejście:** O-3, O-5, O-6, O-7
**Dlaczego TU:** to jest **ostatnia rzecz przed merge**, nie pierwsza. Trzy zielone
narzędzia to nie zielona bramka — zmierzone 12.08 dwa razy: statyka była czerwona
przez dobę, a krok [5] i [7] zapaliły się dopiero w pełnym przebiegu.
**Dowód:** `BRAMKA OK — 22 kroków, 0 nieudanych`, kod wyjścia 0, **przebieg od zera**.
Plus **pełny zestaw perturbacji** — bo bramka mówi „dziś zielone", a perturbacje
„kontrola umie zaświecić czerwono".

### O-9 · Merge do `main` + znacznik
**Kto:** architekt · **Wejście:** O-8 zielone
**Dowód:** `main` zawiera scalony stan; znacznik nazywający zdarzenie
(np. `f1-zamkniete-<data>`), żeby dało się do niego wrócić bez polegania na SHA.

### O-10 · Po scaleniu
- aktualizacja `CURRENT WORK` **ze stanu zmierzonego**, nie z tego dokumentu;
- usunięcie kopii zabezpieczających **dopiero po potwierdzeniu**, że `main` jest zdrowy;
- `docs/BLOKERY.md`: **BLK-01** (klient `gabinet` w realmie Keycloaka) **nadal otwarty** —
  nie zamyka go scalenie F1.

---

## 3 · CZEGO NIE ROBIĆ

- **Nie scalać przed rundą zerową.** Reguła zbieżności nie ma wyjątku „ale bramka zielona".
- **Nie przepisywać historii bez ciszy w drzewie.** Zmierzone: operacja odmawia,
  a obejście przez schowanie cudzej pracy grozi jej utratą.
- **Nie zdejmować wyjątku gitleaks bez przynęty.** Wyjątek usunięty bez dowodu, że
  skaner nadal widzi prawdziwe sekrety, zamienia jedną niewiadomą na drugą.
- **Nie ustawiać podłóg „na ile akurat jest"** — bez kontroli negatywnej automat
  zamienia zapadkę w licznik.
- **Nie zamykać F0 po cichu przy F1.** F0 ma własne otwarte pozycje (F0.8 / BLK-01).

---

## 4 · CO ZOSTAJE OTWARTE PO SCALENIU — jawnie

| pozycja | dlaczego zostaje |
| --- | --- |
| **D-1** — luka §2 przez ponowne użycie istniejącej kolumny | kierunek wraca z rundy 7 **z dowodem**; scalenie jej nie zamyka |
| **D-2** — dwie allowlisty `--przyczyna` | wymaga komunikatów asercji w dwóch testach |
| **D-3** — `TwierdzeniaKomentarzyTest` poza bramką | przeprojektowanie: świadek wiązany z **rolą tekstu**, nie ze słowami |
| **BLK-01** | klient `gabinet` w realmie — repo `konta`, nie my |
| odczyt **dynamiczny** wzorców `--przyczyna` | niezmierzony; wymaga cichego stosu |

---

## 5 · Pytania do architekta

1. **Wariant A, B czy C** z §0.1? Rekomendacja: **C**.
2. **Czy wypchnąć `faza-1-retencja`** przed rundą, żeby weryfikator mógł klonować
   z origin (§0.2)? Jeżeli nie — zlecenie rundy powinno mówić wprost „klonuj lokalnie".
3. **Kto wykonuje O-6** (automatyzacja podłóg) — sesja kodująca w etapie B czy KOD-F1
   przed rozwiązaniem? To jedyna pozycja listy, która jest **zmianą kodu**.
