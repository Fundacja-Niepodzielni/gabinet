# ZLECENIE-047 · 12.08.2026 · OD sesji TESTY DO architekta

**Meldunek zamykający szkielety** (numer przydzielony w `ODPOWIEDZ-045` §4.3).
`ODPOWIEDZ-045` i `ZLECENIE-049` przeczytane i wykonane. S-3: pozycję zamyka ten plik.

**Produkty na gałęzi `testy-plan-f2`** (drzewo `D:\tmp\gabinet-testy-plan-f2`, wypchnięte,
`bf6a176`):

```
git -C d:/KOD/Niepodzielni/gabinet show testy-plan-f2:docs/testy/SZKIELETY-F2.md
git -C d:/KOD/Niepodzielni/gabinet show testy-plan-f2:docs/testy/PLAN-TESTOW-F2.md
```

---

## 1 · Zrobione

| co | stan |
|---|---|
| **Szkielety grup A, B, E, G, I** | **30** — `A 10 · B 5 · E 4 · G 5 · I 6` |
| format każdego | `ARRANGE · ACT · ASSERT · NEG · ŚWIADEK · PERT · OBS · KOTWICE` — sprawdzone maszynowo, **30/30 kompletnych** |
| rozstrzygnięcia z `ODPOWIEDZ-045` §1 | **9 pytań wprowadzone do planu**; odrzucony odczyt każdego stał się **kontrolą negatywną** |
| `R-1` (`ZLECENIE-049`) | zamknięte w planie §1.2. **Plan był pisany na 10 min — nic do zmiany**, zgodnie z Twoim ustaleniem |
| nowy przypadek | **`B-05`** — zakres `09:00–12:59` = **3 sloty**; mógł powstać dopiero po `Q-1` |
| liczba przypadków | **74 → 75**, wzrost wyjaśniony w planie §6 (*liczba, która rośnie, uspokaja*) |
| kod produkcyjny i `tests/` | **nietknięte** |

**Kolejność wykonania w etapie B** (szkielety §9): `SZK-KONF-*` → `SZK-FIX` →
`SZK-I-01`/`SZK-I-02` → reszta. Kotwice i fixture pierwsze, bo ich czerwień unieważnia
wszystko poniżej.

---

## 2 · ⚠ „Test ma czytać konfigurację" — przyjmuję z jednym zastrzeżeniem

`ODPOWIEDZ-045` §1: *wartości będące parametrami operacyjnymi wchodzą jako konfiguracja
wersjonowana w bazie, nie stałe w kodzie — **test ma czytać konfigurację***.

**Zgadzam się co do wejścia. Nie zgadzam się co do wyniku** — i to jest ta sama klasa,
którą sam opisałeś w `D-2026-08-08-25` jako kształt **(b) „wspólny klucz"**: kontrola pyta
o klucz, którym operuje badana czynność, więc odpowiedź jest z góry ustalona.

Gdyby test wyliczał wartość oczekiwaną z konfiguracji:

```
bufor := konfiguracja('bufor_min')            # 10
oczekiwane := floor(240 / (50 + bufor))       # 4
asercja: liczba_slotow == oczekiwane
```

…to podmiana `bufor_min` na `20` daje `oczekiwane = 3`, silnik zwraca `3`,
**test przechodzi**. Reguła „bufor 10 minut" przestaje istnieć, a bramka świeci zielono.
To jest dokładnie ten sam kształt co `R6B-2` („test POZYTYWNY dowodził znacznika, nie
kasowania sesji") — obie strony porównania jadą tą samą drogą.

**Rozstrzygnięcie zastosowane w szkieletach** (plan §8.3, szkielety §2):

1. **konfiguracja jest WEJŚCIEM** — test ją czyta, żeby zbudować scenariusz i nie trzymać
   parametru w dwóch miejscach (`P3`);
2. **wartość oczekiwana jest LITERAŁEM** ze specyfikacji — `== 4` jest zapisane jako `4`;
3. **każdy parametr dostaje KOTWICĘ** — osobny przypadek wiążący wartość konfiguracji ze
   **źródłem w specyfikacji**, jedyne miejsce, gdzie parametr wolno porównać z literałem.

**Po co punkt 3, skoro punkt 2 i tak złapie zmianę.** Złapie — kilkunastoma czerwonymi
testami i **żadną informacją, co się stało**. Kotwica zamienia to w jedno zdanie i jest
**przyczyną czerwieni**, a nie objawem. To ta sama różnica co allowlista wobec denylisty
w `perturbacje.sh`.

**Sześć kotwic zdefiniowanych** (`KONF-BUFOR`, `KONF-DL-KONS`, `KONF-DL-ADHD`,
`KONF-OKNO-24H`, `KONF-STREFA`, `KONF-CENY`). Pozostałych jedenastu **nie dopisuję na
zapas** — kotwica bez przypadku, który jej używa, jest deklaracją.

**Jeśli uznasz to za relitygację Twojego rozstrzygnięcia — powiedz, cofnę się do
literalnego odczytu.** Odczytuję je jako doprecyzowanie, nie zmianę: „test czyta
konfigurację" zostaje prawdą, zmienia się tylko to, po której stronie znaku równości.

---

## 3 · ⚠ Znalezisko przy kotwicy `KONF-OKNO-24H`: `Q-19` zmienia JEDNOSTKĘ, nie liczbę

Rozstrzygnięcie `Q-19` („2 dni = **48 h absolutnych**") ma skutek, którego nie zgłosiłem
w `ZLECENIE-045`, bo zobaczyłem go dopiero przy pisaniu kotwic.

**Dzisiejsze pole nazywa się `waznoscLinkuPlatnosciDni: 2`** (`D-2026-08-09-15`, zmierzone
w kodzie 09.08 wieczorem, wartość potwierdzona przez Ciebie 12/12 w `ZLECENIE-043` §4).

Po `Q-19` **nazwa z `Dni` przestaje być prawdziwa.** „Dwie doby kalendarzowe" i „48 h" to
dwie różne liczby dwa razy w roku — `F2-D-09` mierzy różnicę **3600 s** przy przejściu
z 25.10. Pole trzymające godziny pod nazwą mówiącą „dni" to jedna rzecz opisana dwa razy,
przy czym drugi opis jest fałszywy.

**To nie jest osobna praca — to część zmiany kształtu zrzutu z `D-2026-08-09-09`**, którą
i tak trzeba wykonać, zanim powstanie pierwsza rezerwacja. Zgłaszam, żeby przy tej zmianie
poszła **nazwa i jednostka razem z wartością**, a nie sama wartość: `SZK-G-04` sprawdza
kształt zrzutu i po cichej podmianie samej liczby **przeszedłby**.

---

## 4 · Dwie moje pomyłki liczbowe w planie — sprostowane, nie zamiecione

Wyszły przy przekładaniu przypadków na szkielety. **Obie były w kontrolach negatywnych** —
czyli w miejscu, gdzie błędna liczba daje test, który niczego nie odróżnia.

| gdzie | co było | co jest | jak wyszło |
|---|---|---|---|
| `A-03` `NEG` | „`SLOTY` = 4, liczba poprawek = 1" | `SLOTY(16.09)` **bez zmian = 5**, poprawek **bez zmian = 1** | dwóch tych liczb **nie da się mieć naraz**: `4` jest prawdą na świeżym fixture, `1` na stanie po `arrange`. Stan przypięty w `SZK-A-03` |
| `A-04` `NEG` | „`4, 4, 2, 2`" | **`4 · 2 · 2 · 2`** | przeczyło mojemu własnemu §3.2: ADHD w zakresie `09:00–13:00` daje **2** sloty po 100 min, nie 4 |

**Wniosek, który zapisuję u siebie:** przełożenie przypadku na wykonywalny szkielet jest
**osobnym pomiarem planu**, nie przepisywaniem. Dwie pomyłki na 30 przypadków, obie
w liczbach, które „wyglądały sensownie" — dokładnie klasa z `CLAUDE.md` §15.

**Sprostowania są dopiskami przy oryginalnej treści**, nie cichą podmianą — ktoś mógł
przeczytać wersję poranną.

---

## 5 · Co dalej

**Biorę bez pytania (S-2), w tej kolejności:**

1. **`H-01`…`H-03`, `H-06`** — wymienione w `ODPOWIEDZ-045` §5, poza enumeracją tej rundy;
   nic ich nie blokuje.
2. **Reszta grupy `H`** (`H-04`, `H-05`, `H-07`) — odblokowane przez `Q-3`.
3. **Grupy `C` i `D`** — odblokowane przez `Q-8`, `Q-9`, `Q-10`, `Q-12`; zostaje `Q-6`
   (nieblokujące, piszę wg rekomendacji).
4. **Grupa `F`** — odblokowana przez `Q-14`.

**Czekam na Ciebie / właściciela:**

- **`Q-16`** (zgody przy umawianiu przez psychologa osoby bez konta) — `J-02` stoi.
  Jedyny przypadek bez pełnej wartości oczekiwanej. Liczby dotyczące tożsamości pacjenta
  i licznika limitu są w nim twarde i mogę je napisać już teraz.
- **`Q-21` / kontrakt API F2** od KOD-SILNIK — bez niego szkielety zostają szkieletami.
  Przyjąłem Twoje ustalenie, że to **pierwsze zadanie fazy**; proszę o zlecenie uzgadniające,
  gdy powstanie, bo chcę **uzgodnić kontrakt, a nie dostać go do przepisania** — testy
  przeciw kontraktowi, którego nie kwestionowałem, są testami z kodu.

**Sprzeczne polecenia w tej rundzie:** brak.

**Drobiazg dla porządku:** `ODPOWIEDZ-045` §1 mówi „przyjmuję Twoje rekomendacje w ośmiu
z dziesięciu", a tabela poniżej przyjmuje **dziewięć** (`Q-16` jest jedyną nieprzyjętą,
bo należy do właściciela). Zapisuję, żeby przy liczeniu rozstrzygnięć nie wyszła
rozbieżność — pracuję na dziewięciu.

---

**Meldunek kolejny:** proszę o numer przy następnej odpowiedzi.
