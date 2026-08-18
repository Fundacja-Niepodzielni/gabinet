# ZLECENIE-045 · 12.08.2026 · OD sesji TESTY DO architekta

**Kierunek odwrotny niż zwykle** (jak `ZLECENIE-044` sesji SPEC-UMOWA): meldunek
ukończenia etapu A + pozycje wymagające Twojego rozstrzygnięcia. Plik zamyka pozycję
(S-3); nie stoję (S-2).

**Produkt:** `docs/testy/PLAN-TESTOW-F2.md` · **gałąź:** `testy-plan-f2` (baza `71cd8a5`).

> **⚠ Gdzie leży plan.** Na gałęzi `testy-plan-f2`, w **osobnym drzewie roboczym**
> `D:\tmp\gabinet-testy-plan-f2`. W `D:\KOD\Niepodzielni\gabinet` go **nie zobaczysz**.
> Odczyt bez przełączania gałęzi:
> `git -C d:/KOD/Niepodzielni/gabinet show testy-plan-f2:docs/testy/PLAN-TESTOW-F2.md`
> Powód osobnego drzewa: §4.1. **Ten** plik celowo leży w drzewie głównym — §4.2.

---

## 1 · Meldunek gotowości — etap A

| kryterium z `PROMPT-TESTY.md` | stan |
|---|---|
| plan pokrywa wszystkie reguły F2 z zakresu specyfikacji | **43 reguły, 0 bez przypadku** (macierz §6 planu) |
| każdy przypadek ma wynik **liczbowy** | **74 przypadki**, każdy kończy się liczbą; zero „element widoczny" |
| każdy przypadek ma kontrolę pozytywną **i** negatywną | **74 / 74** |
| test współbieżności 100 żądań = 1 rezerwacja | `F2-I-01` + `I-02` (dowód, że żądania były **naprawdę** równoczesne) |
| zmiana czasu: doba 23- i 25-godzinna | `H-02`…`H-05`, `H-07`, `J-03`, `J-04`, `D-09` |
| przypadki brzegowe z decyzji | `D-06` (płatność po wygaśnięciu) · `J-02` (psycholog umawia bez konta) · `J-03`/`J-04` (rezygnacja w dzień zmiany czasu) |
| zero zmian w kodzie produkcyjnym i w `tests/` | **potwierdzone** — jedyna zmiana to `docs/testy/` |

Rozbicie per grupa (suma bez rozbicia nie jest dowodem):
`A 10 · B 4 · C 5 · D 9 · E 4 · F 10 · G 5 · H 7 · I 6 · J 7 · K 4 · L 3`, plus jeden
odsyłacz (`J-01` → `D-06`), który **nie liczy się** do 74.

**Przypadków bez perturbacji: 4** — `K-01`…`K-04`, kontrole nad kontrolami, z powodem
i warunkiem znoszącym w nagłówku grupy. **Nie piszę „zero"**, bo cztery to nie zero:
perturbacja kontroli-nad-kontrolą jest sama kontrolą wymagającą perturbacji, więc
rekurencję ucinam na pierwszym poziomie — jawnie, nie po cichu.

**Czego NIE zrobiłem:** planu nie weryfikowała sesja, która go nie pisała. Zgodnie
z `WYTYCZNE-PRACY.md` §2 jest **napisany**, nie **zrobiony**.

---

## 2 · DZIESIĘĆ PYTAŃ BLOKUJĄCYCH — bez nich przypadek nie ma wartości oczekiwanej

Każde ma **dwie konkurencyjne liczby** i moją rekomendację. Uzasadnienia stoją w planie
§8.1 — tutaj tylko to, na co odpowiadasz, żeby jedna rzecz nie była opisana dwa razy (`P3`).

| # | pytanie | liczby, które się rozjeżdżają | rekomendacja |
|---|---|---|---|
| **Q-1** | Czy bufor 10 min musi zmieścić się w zakresie **po ostatnim slocie**? | zakres `09:00–12:59` → **3** albo **4** sloty | **wlicza się** (raster 60 min jako jednostka) |
| **Q-3** | Doba 25-godzinna: powtórzona godzina `02:00` oferowana **dwa razy** czy raz? | **7** albo **6** slotów | **dwa razy**, pod warunkiem że etykieta prezentacyjna je rozróżnia |
| **Q-4** | Okno 24 h: **86400 s absolutnych** czy „ta sama godzina dzień wcześniej"? | `J-03`: odwołanie o 12:30 daje **14500 gr** albo **0 gr** | **absolutne** — drugi odczyt daje raz 25 h, raz 23 h, czyli reguła po cichu zmienia wartość |
| **Q-8** | Długość **krótkiej blokady wstępnej** (`D-2026-08-09-08`: „10–15 min") | `t0+10` albo `t0+15` | **10 min** |
| **Q-9** | Otwarcie linku: `max(2 dni, otwarcie+10 min)` czy **zwolnienie** 10 min po otwarciu? | `2026-09-17 10:00` albo `2026-09-16 20:10` | **`max`** — inaczej przypadkowe otwarcie linku kasuje pacjentowi resztę okna |
| **Q-10** | Margines `M` w `okno = min(okno_ścieżki, czas_do_wizyty − M)` | `trzymane_do` = wizyta − M | **2 h** (ta sama oś co „najbliższy możliwy termin") |
| **Q-12** | Limit równoczesnych nieopłaconych blokad na pacjenta | **1** albo **2** | **2** |
| **Q-14** | „Wystawiony termin niskopłatny" = slot **otwarty** czy **otwarty i wolny**? | `wystawione` = **4** albo **5** | **otwarty** — przy drugim odczycie limit podażowy rośnie z każdą rezerwacją, czyli przestaje ograniczać podaż |
| **Q-16** | Kto akceptuje regulamin i zgodę art. 9, gdy **psycholog umawia osobę bez konta**? | zapisanych zgód **0** albo **2** | **właściciela, nie nasze** — patrz niżej |
| **Q-19** | „2 dni" = **48 h absolutnych** czy dwie doby kalendarzowe? | różnica **3600 s**, dwa razy w roku | **48 h absolutnych**, spójnie z `Q-4` |

**Jedenaście pytań nieblokujących** (`Q-2`, `Q-5`, `Q-6`, `Q-7`, `Q-11`, `Q-13`, `Q-15`,
`Q-17`, `Q-18`, `Q-20`, `Q-21`) — plan §8.2. Przy każdym przyjąłem rekomendację i piszę
przeciw niej; potwierdzenie może przyjść później, bez wstrzymywania etapu B.

### Dwa pytania, które są cudze

- **`Q-16` należy do właściciela.** Stoi otwarte w `DECYZJE-DO-PODJECIA.md`
  („OTWARTE PO TEJ RUNDZIE", poz. 3): *„kto akceptuje regulamin i zgodę, gdy PSYCHOLOG
  umawia osobę bez konta"*. Na wizytach niskopłatnych to **reguła, nie wyjątek**
  (*„z reguły psycholog umawia kolejną wizytę"*) — czyli **większość** tej ścieżki
  zbiera dziś dane bez rozstrzygniętej podstawy. Nie zgaduję: `J-02` czeka.
- **`Q-21` należy do KOD-SILNIK.** Plan wiąże się z **operacjami kontraktowymi**
  (§4 planu), nie z trasami — wymyślony przeze mnie URL trzeba by w etapie B przepisać
  w całości. **Etap B nie ruszy bez kontraktu API F2.**

---

## 3 · Rozjazdy specyfikacja ↔ decyzje — do sesji SPEC-UMOWA

Osiem pozycji, pełna treść w planie §1.2. Sesja SPEC-UMOWA prowadzi rejestr
(`docs/specyfikacja/REJESTR-ROZJAZDOW.md` — widzę go w drzewie); **nie dopisuję się do
cudzego pliku**, przekazuję przez Ciebie. **Jedna wymaga uwagi teraz:**

> **`R-1` — blokada slotu przy rezerwacji własnej.** Specyfikacja, `D-2026-08-09-08`
> i Twój prompt mówią **10 min**. Właściciel 09.08 wieczorem powiedział
> **„~godzina przy samodzielnym umawianiu, dłużej przy umawianiu przez psychologa"**
> i dodał wprost, że to **zmienia zapisane wcześniej 10 min**
> (`DECYZJE-DO-PODJECIA.md`, „DOPISANE PÓŹNYM WIECZOREM").
>
> **Tego zdania nikt nie wycofał na piśmie.** Nocna runda tego samego dnia przestawiła
> obronę przed zamrażaniem grafiku z „kod przy każdej rezerwacji" na „limit równoczesnych
> blokad" — ale **nie wróciła do samej długości blokady**. `ZLECENIE-043` prostuje
> „2 dni" po stronie psychologa i potwierdza 12/12 wartości `ZestawRegul` wobec
> specyfikacji; **ścieżki własnej wobec zdania właściciela nie prostuje nic.**
>
> Piszę plan na **10 min** — bo tak mówi Twój prompt i trzy zapisy. **Ale to jest
> nowsza decyzja właściciela stojąca przeciwko starszym zapisom**, czyli układ, w którym
> hierarchia mówi „wygrywa właściciel". Zgłaszam zamiast wybierać po cichu.
>
> **Koszt cofnięcia dziś: zero** — nie ma kodu blokady i nie ma ani jednej rezerwacji.
> Po pierwszej rezerwacji z zamrożonym zrzutem to już zmiana **kształtu** zrzutu
> (`D-2026-08-09-09`), nie wartości.

Pozostałe: `R-2` (2 dni / 48 h / 24 h — trzy wartości, z czego dwie tylko **pozornie**
te same, patrz `Q-19`) · `R-3` (weryfikacja kodem: raz kontra przy każdej rezerwacji) ·
`R-4` (guest checkout jako zasada twarda kontra kierunek „konto bez hasła",
`D-2026-08-09-10` — nierozstrzygnięty) · `R-5`–`R-8` zamknięte, odnotowane dla kompletności.

---

## 4 · Dwa znaleziska operacyjne — orkiestracja, nie treść

### 4.1 Sesje mają własne gałęzie, ale **dzieliły jedno drzewo robocze**

**Zmierzone, nie założone.** `git checkout -b testy-plan-f2` w `D:\KOD\Niepodzielni\gabinet`
**przestawił HEAD sesji KOD-F1**, która pracuje w tym samym katalogu. W drzewie leżała jej
niezacommitowana praca: `backend/tests/Feature/OdebranieRoliTest.php`, **+126 / −35 linii**
(naprawa nogi 1 — `granicaProcesu()` z trzecim singletonem `StartSession`).

**Gdyby KOD-F1 zacommitowała w tym oknie, jej praca wylądowałaby na mojej gałęzi.**
A `git add -A` nie pyta, co się zmieniło — to ta sama klasa, którą sam opisałeś w regule 6
`ZAMKNIECIE-DNIA-2026-08-09`.

**Co zrobiłem:** wróciłem na `faza-1-retencja` (HEAD i zmiana nietknięte — sprawdzone
`git status`), a swoją gałąź wziąłem do osobnego drzewa `git worktree`. Praca KOD-F1
przetrwała i jest dziś zacommitowana (`3b14cf0`, `1b2e20c`).

**Rekomendacja:** `00-PLAN-ORKIESTRACJI.md` §4.2 mówi „każda sesja na własnej gałęzi".
To **nie wystarcza** — gałąź bez własnego drzewa nie izoluje niczego, bo HEAD jest wspólny.
Proponuję dopisać: **każda sesja dotykająca repozytorium dostaje własny `git worktree`**.
Koszt: jedno polecenie na starcie sesji.

### 4.2 …ale kanał `docs/ZLECENIA/` żyje w drzewie GŁÓWNYM — i to się z tym gryzie

**Zmierzone:** `ZLECENIE-044`, `POTWIERDZAM-044` i `ODPOWIEDZ-044` leżą w drzewie głównym
jako pliki **nieśledzone**. Kanał działa dziś dlatego, że wszystkie sesje siedzą w jednym
katalogu — czyli **dokładnie z powodu tego, co §4.1 proponuje usunąć**.

Gdybym napisał ten plik na swojej gałęzi, **nie zobaczyłbyś go**: obserwator pilnuje
katalogu, nie gałęzi. Dlatego ten plik leży w drzewie głównym, a plan — na gałęzi.
**Rozdzielenie jest świadome, nie niekonsekwencją.**

Drugi skutek: numeracja. Wziąłem `044`, zanim zobaczyłem, że należy do SPEC-UMOWA.
**Przy sesjach w osobnych drzewach „sprawdź najwyższy numer" przestaje działać** — każdy
widzi inny najwyższy.

**Trzy wyjścia, w kolejności mojej preferencji:**
1. **kanał w jednym, uzgodnionym miejscu** — osobny worktree na `main` albo katalog obok
   repozytorium, dowiązany do wszystkich drzew; numeracja i obserwator znów mają jedno źródło;
2. kanał zostaje w drzewie głównym, a sesje w worktree **piszą do niego po ścieżce
   bezwzględnej** (tak zrobiłem dziś) — działa, ale wymaga, żeby każdy o tym wiedział;
3. numery **przydzielane przez architekta** przy zlecaniu, zamiast wyszukiwane przez sesję.

**Nie wybieram sam** — to Twoja decyzja o kształcie kanału, nie moja o własnej pracy.

---

## 5 · Co dalej po mojej stronie

- **Etap B rusza po merge F1 do `main`.** Wtedy: testy **wyłącznie w `tests/`**, przeciw
  kontraktom z §4 planu, nigdy przeciw wnętrzu silnika.
- **Kolejność pierwszego dnia etapu B:** `I-01` + `I-02` przed wszystkim innym — to jedyny
  przypadek wymagany wprost przez `CLAUDE.md` §6 i jedyny, którego brak przechodzi
  niezauważony **do dnia wizyty**.
- **Jedna rzecz, która nie poczeka do etapu B:** `D-2026-08-09-09` mówi, że kształt
  zamrożonego zrzutu reguł trzeba przestawić **zanim** powstanie pierwsza rezerwacja.
  Dziś rezerwacji jest zero, więc zmiana jest darmowa. `G-04` zakłada kształt **już
  przestawiony** — jeśli KOD-SILNIK zbuduje ścieżkę rezerwacji przed zmianą kształtu,
  przypadek trzeba będzie przepisać, a okno zamknie się na zawsze.
- **S-2 — nie stoję.** Do czasu odpowiedzi na pytania blokujące przygotowuję szkielety
  przypadków od nich niezależnych: grupy **A, B, E, G, I** oraz `H-01`…`H-03`, `H-06`.

---

**S-3:** tę pozycję zamyka ten plik razem z `docs/testy/PLAN-TESTOW-F2.md` na gałęzi
`testy-plan-f2`, nie meldunek w oknie rozmowy.
