# ZLECENIE-060 · 12.08.2026 · OD sesji KOD-F1 DO architekta

**Meldunek:** projekt naprawy strażnika (S-01, S-02) gotowy.
**Produkt:** `docs/ZLECENIA/PROJEKT-NAPRAWY-STRAZNIKA.md` · Plik zamyka pozycję (S-3).

---

## 1 · Przyjmuję znalezisko TESTY w całości — i jedna rzecz jest gorsza, niż napisali

Sprawdziłem obie usterki niezależnie, na repozytorium jednorazowym poza projektem.
**Diagnoza broni się co do joty.** Do tego mój własny zapis w `ZLECENIE-054` §3 był
nieprawdziwy:

> *„`core.hooksPath` jest wspólny dla wszystkich worktree, więc pozostałe trzy
> strumienie trafią na odmowę przy pierwszym commicie"*

**Wspólna jest WARTOŚĆ konfiguracji, nie SKUTECZNOŚĆ mechanizmu.** Ścieżka względna
rozwija się w każdym drzewie osobno i w większości celuje w pustkę. Ostrzegłem
strumienie przed odmową, **która nigdy nie miała nadejść** — i architekt rozesłał
to ostrzeżenie dalej jako `ZLECENIE-057`.

To jest ta sama klasa, którą ta sesja zamykała u siebie trzy razy: **pisemne
zapewnienie o mechanizmie, którego nikt nie zmierzył.** Tym razem autorem byłem ja,
a zmierzył je ktoś inny — i tak właśnie ma działać runda.

---

## 2 · ⚠ JEDNA RZECZ NIE MOŻE CZEKAĆ NA OKNO SCALENIOWE

`ZLECENIE-057` mówi strumieniom: *„strażnik aktywny we wszystkich drzewach roboczych
gabinetu"*. **To zdanie jest dziś nieprawdziwe.** Dopóki stoi bez sprostowania, sesja
może uznać, że jest chroniona, i przestać uważać na `git add`.

**Fałszywe zapewnienie jest gorsze niż znana dziura** — precedens R6A-4, gdzie zdanie
„lista jest ZAMKNIĘTA" uczyło czytelnika przestać szukać.

**Proszę o jedno zdanie sprostowania w kanale:** strażnik działa dziś **wyłącznie
w drzewie głównym**; w worktree jest nieaktywny do wykonania O-6b.

Samego ryzyka nie ma sensu wyolbrzymiać: jest **równe stanowi sprzed strażnika**,
więc to brak ochrony, nie regresja. Ale musi być znany.

---

## 3 · Pomiary własne — trzy, wszystkie poza projektem, posprzątane

**M-1 · Hook w katalogu WSPÓLNYM odpala się w OBU drzewach** (to rozstrzyga wybór wariantu):
```
git rev-parse --git-path hooks  (główne)  → .git/hooks
git rev-parse --git-path hooks  (worktree)→ …/glowne/.git/hooks
commit w głównym  → HOOK ODPALIL SIE
commit w worktree → HOOK ODPALIL SIE
```

**M-2 · `hooksPath` w pustkę MILCZY** (potwierdzenie mechanizmu S-01):
```
core.hooksPath = skrypty/git-hooks   (katalog NIE ISTNIEJE)
commit → kod 0, ZERO ostrzeżeń — w obu drzewach
```
Git nie mówi nic. Obecność i nieobecność kontroli mają **ten sam objaw**.

**M-3 · Tożsamość** (potwierdzenie S-02 i rekomendacji TESTY):
```
w worktree TESTY:
  basename(dirname(--git-common-dir)) → gabinet                 ✔
  basename(--show-toplevel)           → gabinet-testy-plan-f2   ✘ (dziś)
```

---

## 4 · Patch — skrót, całość w projekcie

**P-1 · Strażnik do KATALOGU WSPÓLNEGO, `core.hooksPath` USUNIĘTY.**
Z trzech wariantów TESTY wybrałem ten, nie „ścieżkę bezwzględną": bezwzględna
działa, ale wpisuje ścieżkę **tej maszyny** do konfiguracji repozytorium — psuje się
przy przeniesieniu katalogu i u każdego, kto ma repo gdzie indziej. Katalog wspólny
rozwiązuje S-01 bez wiązania konfiguracji z maszyną (M-1).
Instalator ma **odczyt zwrotny po sumie kontrolnej** — „skrypt się wykonał" ≠ „plik
ma treść".

**P-2 · Tożsamość z `--git-common-dir`**, nie z `basename` drzewa (M-3). Brak
tożsamości = odmowa, nie cicha zgoda — inaczej fail-open trafiałby dokładnie tam,
gdzie zabolało N-13.

**Skutek do cofnięcia:** `.zakres-sesji` sesji TESTY wraca z `gabinet-testy-plan-f2`
na `gabinet`. Wpisali to **jawnie jako obejście, z komentarzem** — dokładnie tak,
żeby dało się cofnąć zamiast uznać za konwencję.

---

## 5 · Kontrola negatywna — świadome odstępstwo, proszę o rozstrzygnięcie

Dosłowna „kontrola negatywna w każdym aktywnym worktree" znaczy **podłożenie
naruszenia w cudzym drzewie** — zapis `.zakres-sesji` albo `.przebieg-pomiarowy`
u pracującej sesji. To łamie **jedna ścieżka, jeden piszący** i może nadpisać
czyjąś deklarację.

**Rozbijam na trzy części, które razem dają to samo, nie pisząc u nikogo:**

| | gdzie | co dowodzi |
| --- | --- | --- |
| **K-1** | każde aktywne drzewo, **tylko odczyty** | mechanizm osiągalny i **nieprzesłonięty** (to jest cała treść S-01) |
| **K-2** | worktree **jednorazowy**, tworzony i usuwany przez kontrolę | strażnik **naprawdę odmawia** w worktree, **z właściwej przyczyny** |
| **K-3** | każda sesja u siebie, raz, wpis w kanale | odmowa w KONKRETNYM drzewie tej sesji |

K-3 jest dosłownym spełnieniem wymogu, tylko rękami właściciela drzewa — jak O-7.
**Jeśli wolisz wariant centralny i dosłowny, wykonam go** — ale potrzebuję zgody
każdej sesji na zapis w jej drzewie.

K-2 sprawdza **dwa sygnały, nie jeden**: kod wyjścia ORAZ przyczynę odmowy. Sam kod
nie odróżnia „odmówił" od „padł".

---

## 6 · Miejsce w liście scaleniowej: **O-6b**, między O-6 a O-7

- **po O-6**, bo obie są zmianami kodu i dzielą jedną zieloną bramkę;
- **przed O-7 — i to jest warunek konieczny, nie preferencja.** O-7 weryfikuje
  deklaracje `.zakres-sesji` per strumień. **Weryfikacja deklaracji dla strażnika,
  który w tym drzewie nie działa, jest pustym rytuałem** — czyli dokładnie tym,
  co TESTY zmierzyły: deklaracja była, mechanizmu nie było.

Dowód ukończenia O-6b: K-1 zielone dla każdego drzewa · K-2 zielone z właściwą
przyczyną · `StraznikCommitaTest` nadal zielony · `.zakres-sesji` TESTY cofnięty.

---

## 7 · Warunki pracy dotrzymane

- **Kod i drzewo stoją.** Zero commitów, zero zmian w `backend/` i `skrypty/`.
  Warunek z `ZLECENIE-054` §1 nadal spełniony (sprawdzalny jednym poleceniem).
- **Stos główny cichy.** Żadnej bramki, perturbacji ani suity. Trzy pomiary poszły
  na repozytorium jednorazowym w katalogu tymczasowym, **usuniętym po pomiarze**.
- **Oba pliki nieśledzone**, zgodnie z `ODPOWIEDZ-058` (odczyt „napisz, nie commituj"
  potwierdzony).

---

## 8 · Czego NIE zmierzyłem

- Zachowania przy **trzecim i czwartym** drzewie roboczym — dziś są dwa. K-1 iteruje
  po `git worktree list`, więc obejmie kolejne, ale to **nie jest zmierzone**.
- Przypadku sesji pracującej w **klonie**, nie w worktree (weryfikator rundy 7).
  Tam strażnik nie działa **i to jest poprawne** — `ZLECENIE-056` daje mu zakaz
  commitowania, więc mechanizm jest zbędny.
- **Nie rozstrzygam**, czy `.zakres-sesji` ma być plikiem śledzonym per gałąź zamiast
  lokalnym per drzewo. Dziś lokalny; zmiana byłaby osobną decyzją.

---

## 9 · Sprzeczne polecenia

**Brak.** Rozstrzygnięcia z `ODPOWIEDZ-058` (wariant C, push wykonany przez Ciebie,
O-6 moje, zamrożenie = kod) przyjęte bez zastrzeżeń i wpisane do listy scaleniowej.
