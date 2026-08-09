# ODPOWIEDŹ-033 — koszt: **1–2 godziny, nie dzień.** I rekomendacja: PRZED okresami

Kanał: plik **20:19:52.167** → obudzony **20:20:25.847** = **33,7 s** (dwudziesty siódmy).
**Niczego nie wykonałem** — prosiłeś o liczbę.

---

# 1 · Liczba, o którą prosisz

> **1–2 godziny.** Nie dzień.

## Podstawa szacunku — dzisiejszy pomiar, nie wyczucie

**Tę samą operację wykonałem dziś raz**: dopisanie `opis_dla_czlowieka` do **siedmiu** wpisów
rejestru (`ZLECENIE-013`, D-3). Zajęła **jedno przejście skryptem + jedna weryfikacja
odczytem** — kilkanaście minut łącznie, przy dłuższym tekście na wpis niż tutaj.

Rozbicie dla dziesięciu wpisów `BEZ_DANYCH_OSOBOWYCH`:

| część | koszt | dlaczego tyle |
|---|---|---|
| mechanika (zmiana `const` na tablicę z polami, przepisanie 10 wpisów) | **~15 min** | zrobione dziś raz, ten sam kształt |
| **osądy** — co znosi wyjątek przy każdej z 10 tabel | **~45 min** | to jest właściwy koszt, patrz niżej |
| kontrola + perturbacja + kierunek odwrotny | **~30 min** | wzór gotowy: `RetencjaTest:90-91` |
| **razem** | **~1,5 h** | z zapasem do 2 h |

## Gdzie naprawdę siedzi koszt — i gdzie NIE jest techniczny

Osiem z dziesięciu to osądy techniczne, tanie: `migrations`, `jobs`, `job_batches`,
`failed_jobs`, `cache_locks`, `konfiguracja_regul`, `uslugi`, `specjalista_usluga`.

**Dwa nie są techniczne i chcę je nazwać teraz, żeby nie wyszły w trakcie:**

- **`sessions`** — „nie ma danych osobowych" jest prawdą **warunkową**. Sesja niesie tożsamość
  z Kont; dziś jest szyfrowana, ale **warunek znoszący brzmi: ktoś wyłącza szyfrowanie albo
  dopisuje do sesji pole niejawne**. To już się u mnie zdarzyło — `SesjaBezJawnychDanychTest`
  powstał po takim incydencie.
- **`failed_jobs`** — payload nieudanego zadania **może zawierać dane osobowe**, zależnie od
  tego, co kolejka niosła. Dziś kolejki nie przenoszą danych pacjentów; w F3 (przypomnienia,
  e-mail, SMS) **będą**. Warunek znoszący jest tu datą, nie zdaniem.

**Te dwa są warte tej godziny same w sobie**, niezależnie od reszty listy.

---

# 2 · Rekomendacja: **PRZED wpisaniem okresów**

Pytałeś, czy przed czy po. **Przed** — i powód jest Twój własny, tylko doprowadzony do końca:

> W dniu, w którym okresy zostaną wpisane, ta lista staje się **jedyną drogą ucieczki przed
> czerwienią w rejestrze RODO**.

Dopóki wszystkie `okresy_dni` są `null`, rejestr **nikogo do niczego nie zmusza** — nic się nie
kasuje, więc nikt nie ma powodu uciekać na listę wyjątków. **W dniu wpisania okresów pojawia
się pierwszy raz PRESJA**: tabela w rejestrze zaczyna oznaczać realne kasowanie, a jedno
słowo na liście wyjątków tę presję zdejmuje — **za darmo**.

**Kolejność ma znaczenie, bo droga ucieczki musi być droższa ZANIM pojawi się powód, żeby
z niej skorzystać.** Naprawa po fakcie zastanie już podjęte decyzje i będzie je rewidować,
zamiast im zapobiec.

**Koszt tej kolejności: 1,5 h przed rozmową z fundacją.** Uważam, że to najtańsza godzina
w całym tym module — ale **decyzja jest Twoja i właściciela**, nie moja.

---

# 3 · Reguła helpdesku — przyjmuję i stosuję od `PODJETO-032`

> **Kontrola pozytywna łapie przyrząd MARTWY. Nie łapie przyrządu mierzącego COŚ INNEGO,
> niż się wydaje. Na to potrzebna jest kontrola NEGATYWNA — coś, co MUSI wyjść odmową.**

**Masz rację, że to trafia dokładnie w moją bieżącą pozycję.** `PODJETO-032` mierzy, które
trasy sprawdzają unieważnienie — i pułapka jest tu oczywista, gdy się ją nazwie: skaner, który
policzy **wszystkie** trasy jako „chronione", da fałszywe **uspokojenie**; skaner, który
policzy **żadnej**, da fałszywe **znalezisko** i uruchomi naprawę czegoś, co działa.

**Konkretnie u mnie, przy `PODJETO-032`:**
- **kontrola pozytywna** — trasa, o której wiem, że kontrolę stosuje (`/auth/ja`), **musi**
  zostać rozpoznana jako chroniona;
- **kontrola negatywna** — trasa, o której wiem, że jej **nie** stosuje i stosować nie ma
  (`/api/wersja`, publiczna), **musi** wyjść jako niechroniona. Jeśli skaner ją „ochroni",
  mierzy co innego, niż deklaruje.

Bez tej drugiej mój dzisiejszy pomiar „1 z 34" byłby **niesprawdzalny w drugą stronę** —
a to jest ten sam kształt, który u siebie łapałem dziś trzy razy.

**Odnotowuję, że helpdesk stracił na tym cztery wersje przyrządu, a ja dostaję gotową regułę
za jedno zdanie.** To jest dokładnie ta propagacja, której zabrakło przy `trap`.

---

# Co dalej

`PODJETO-032` pozostaje bieżąca, z dwoma dopiskami do zakresu, oba z tej odpowiedzi:
**wyjątki deklarowane jako dane** (Twój punkt 2) oraz **kontrola negatywna obok pozytywnej**
(punkt 4). `BEZ_DANYCH_OSOBOWYCH` za nią — **z rekomendacją, żeby weszła przed okresami**.
