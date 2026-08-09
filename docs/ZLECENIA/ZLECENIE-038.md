# ZLECENIE-038 — właściciel rozstrzygnął weryfikację i trzy ścieżki umawiania. Blokada slotu do przeprojektowania.

**Od:** architekt · **09.08.2026, noc** · potwierdź zwyczajnie · **kolejki NIE zmieniam** ·
**to jest ZAPIS WYMAGAŃ dla F2, nie pozycja do wykonania teraz**

---

## 1 · Decyzje właściciela — dosłownie

- **Weryfikacja numeru telefonu kodem jest KONIECZNA** — **przy każdej rezerwacji** *oraz*
  przy zakładaniu konta. Nie tylko przy kojarzeniu historii.
- **Licznik wisi na PACJENCIE** — potwierdzone wprost.
- **Historia obejmuje TRZY ścieżki umawiania, wszystkie równoważne:**
  1. pacjent umawia się przez `niepodzielni.com`;
  2. pacjent umawia się przez panel pacjenta;
  3. **psycholog umawia pacjenta.**
- **Dotyczy to zarówno wizyt niskopłatnych, jak i pełnopłatnych.**
- **Gdy umawia psycholog:** pacjent **z kontem** widzi u siebie informację, że musi opłacić
  wizytę w ciągu **24 albo 48 h** (właściciel nie rozstrzygnął którego), i dostaje
  **SMS + e-mail z linkiem do płatności**. Pacjent **bez konta** — samo SMS + e-mail z linkiem.
- **Blokada slotu przy samodzielnym umawianiu: właściciel proponuje ~godzinę**, po czym termin
  wraca jako dostępny. **Przy umawianiu przez psychologa — dłużej.**

## 2 · ⚠ ROZRÓŻNIENIE, które musi być w kodzie od pierwszej linii

> **HISTORIA obejmuje wszystkie wizyty. LIMIT liczy tylko NISKOPŁATNE.**

Właściciel powiedział „dotyczy to zarówno niskopłatnych, jak i pełnopłatnych" **o historii
i o ścieżkach umawiania** — nie o limicie. Limit `10` pozostaje limitem wizyt niskopłatnych.
**Jeśli licznik zacznie liczyć wszystko, odetnie od pomocy ludzi, którzy płacą pełną stawkę.**
To jest ten rodzaj pomyłki, który wychodzi po pół roku i na żywych ludziach.

## 3 · ⚠ CO JUŻ ZDECYDOWANO O BLOKADZIE SLOTU — i co z tego wynika

**Właściciel pytał, czy mamy politykę. MAMY, i on jej nie pamiętał — więc podaję źródło:**

| reguła | wartość | stan kodu |
|---|---|---|
| blokada koszyka | **10 min** | **`ODPOWIEDZ-019`: kod decyzyjny NIE ISTNIEJE** (`blokadaKoszyka` poza `ZestawRegul` → 0 trafień) |
| ważność linku płatności | **2 dni** | **to samo — 0 trafień poza `ZestawRegul`** |

**Czyli tak jak z limitem: wartości w konfiguracji, zero kodu.** Dobrze — nic nie trzeba cofać.

**⚠ ALE W TYCH DWÓCH LICZBACH SIEDZI SPRZECZNOŚĆ, której nikt nie zauważył:**

> **Slot puszczany po 10 minutach, a link do płatności żyjący 2 dni, to obietnica, której nie da
> się dotrzymać.** Kto zapłaci po pół godzinie, zapłaci za termin, którego już nie ma.

**Rekomendacja, którą przekazuję właścicielowi: ŻYWOTNOŚĆ LINKU = ŻYWOTNOŚĆ BLOKADY. Jedna
liczba na ścieżkę, nie dwie.** Płatność po wygaśnięciu **nie tworzy wizyty** — tworzy zwrot
albo zadanie dla koordynatora, i to musi być rozstrzygnięte, zanim ktoś napisze webhook.

## 4 · Dlaczego dłuższa blokada JEST dziś bezpieczna, choć wcześniej nie była

W `DECYZJE-DO-PODJECIA` §D5 stoi moje znalezisko: **publiczny punkt rezerwacji plus krótka
blokada pozwala zająć cały grafik i odnawiać go w nieskończoność.** Wydłużenie blokady
z 10 minut do godziny **sześciokrotnie potaniałoby ten atak**.

**Decyzja właściciela o weryfikacji kodem przy KAŻDEJ rezerwacji to znosi** — bo każdy zajęty
slot zaczyna kosztować **działający numer i odebrany kod**. **On rozwiązał ten problem, nie
wiedząc, że go rozwiązuje** — i to jest powód, dla którego godzina jest dziś do przyjęcia,
a przed jego decyzją nie byłaby.

**Kształt, który z tego wynika — blokada DWUSTOPNIOWA:**

1. **krótka blokada wstępna** (rząd 10–15 min) w chwili kliknięcia — zanim cokolwiek wyślemy;
2. **przedłużenie do pełnego okna** (godzina / 24 h / 48 h) **dopiero po potwierdzeniu kodem**.

Inaczej niepotwierdzony klikacz trzyma slot pełną godzinę za darmo i wracamy do punktu wyjścia.

## 5 · Przypadek brzegowy, o który nikt nie zapytał, a wywróci wdrożenie

**Blokada nie może być dłuższa niż czas do wizyty.** Wizyta jutro o 9:00, umówiona przez
psychologa dziś o 20:00, z oknem 48 h → **okno płatności kończy się po wizycie**.

**Reguła: okno = min(okno_ścieżki, czas_do_wizyty − margines).** Margines rozstrzyga się razem
z oknem 24 h na odwołanie, bo to ta sama oś czasu. **Zapisz jako wymaganie, nie licz teraz.**

## 6 · Co jeszcze musi być, a nie padło

- **Wygaśnięcie blokady MUSI dać znać pacjentowi.** Cisza znaczy „nie wiem, czy mam wizytę".
- **Psycholog musi widzieć, ile SWOICH slotów trzyma nieopłaconych** — przy oknie 48 h dziesięciu
  pacjentów to dziesięć zamrożonych terminów na dwie doby.
- **Weryfikacja przy każdej rezerwacji to KOSZT OPERACYJNY** — jeden SMS na rezerwację plus
  przypomnienie przed wizytą. Nie oponuję; **odnotowuję, żeby właściciel wiedział, za co płaci.**

## 7 · Czego NIE robisz

**Nie budujesz nic z tego teraz.** `PODJETO-032`, potem `BEZ_DANYCH_OSOBOWYCH`. To jest **zapis
wymagań do `docs/DECYZJE.md`**, żeby nie zginęły — bo połowa z nich powstała w rozmowie, a nie
w dokumencie. **Jeśli którykolwiek punkt kłóci się z Twoim pomiarem — powiedz, nie dopasowuj.**

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · realmu nie dotykasz · ścieżki bezwzględne · nic poza fundację ·
**S-2 i S-3 obowiązują.**
