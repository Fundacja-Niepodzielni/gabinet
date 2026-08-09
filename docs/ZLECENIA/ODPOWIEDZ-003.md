# ODPOWIEDŹ-003 — decyzja o kredycie zapisana

## Pomiar kanału

| pomiar | wartość |
|---|---|
| plik powstał (`stat`) | **11:20:13.527** |
| obserwator mnie obudził | **11:21:29.724** |
| **różnica** | **76,2 s** |

**Trzeci pomiar i pierwszy, który wychodzi poza dotychczasowy zakres:** 28,9 s · 33,4 s ·
**76,2 s**. Nie robię z tego średniej — trzy punkty to nie rozkład. Podaję za to **wyjaśnienie
mechanizmu**, bo tym razem je znam: `ZLECENIE-003` powstało o **11:20:13**, czyli **w trakcie**
gdy kończyłem `ZLECENIE-002`. Obserwator **wykrył plik w swoim cyklu**, ale sygnał trafił do
mnie dopiero, gdy zamykałem poprzednią pozycję — zgodnie z Twoją własną instrukcją („jesteś
w trakcie pracy? DOKOŃCZ bieżącą pozycję").

**Wniosek dla kanału, ważniejszy niż sama liczba:** opóźnienie, które mierzę, to **nie jest
opóźnienie kanału** — to suma opóźnienia kanału i **czasu, jaki zajmuje mi domknięcie
poprzedniego zlecenia**. Przy zleceniach przychodzących seriami ta druga składowa będzie
dominować i będzie rosła z długością zadania. **Nie da się z tych liczb odczytać wydajności
kanału**, dopóki nie przyjdą do bezczynnej sesji — a dwie pierwsze (29 s, 33 s) właśnie takie
były i to one są miarodajne.

## Co zapisałem

**1. Decyzja: `docs/DECYZJE.md` → D-2026-08-09-01.** Kredyt za odsprzedany termin **poza
zakresem pierwszego wdrożenia**, decyzja właściciela 09.08. Uzasadnienie zapisane tak, jak
prosiłeś — **saldo kredytu jest formą finansowej historii pacjenta**, a `CLAUDE.md` zamyka
zakres słowami „brak pakietów wizyt, brak historii finansowej pacjenta"; bez tej decyzji oba
zapisy stały w cichej sprzeczności, bo osobno brzmiały sensownie.

**2. Znaczniki w streszczeniach — jest ich DZIESIĘĆ, nie trzy.** I to jest jedyna rzecz,
w której odstąpiłem od litery zlecenia, więc mówię wprost dlaczego: wskazałeś trzy miejsca,
a **pomiar dał dziesięć**. Oznaczyłem wszystkie, bo znacznik przy trzech z dziesięciu byłby
gorszy niż brak znaczników — czytelnik trafiający na nieoznaczone wystąpienie uznałby, że
akurat *to* jest w zakresie.

| plik | miejsca |
|---|---|
| `03-streszczenie-jak-dziala.md` | **4** — definicja kredytu (s.154, znacznik pełny), lista rezerwowa (41), ekran reguł (224), skrót (246) |
| `04-streszczenie-zakres.md` | **5** — reguła `REGULY.kredytZaOdsprzedany` (391, znacznik pełny), pozycja 14 zakresu (57), ekran reguł (156), zwroty (172), tabela ryzyk (627) |
| `05-DECYZJE-makiety.md` | **1** — decyzja o kredycie (91) |

**Niczego nie skasowałem.** Wszystkie zapisy zostają w brzmieniu specyfikacji; znacznik mówi
tylko, że tego nie budujemy teraz — dokładnie z powodu, który podałeś: streszczenie ma zgadzać
się ze źródłem, inaczej ktoś „naprawi" je z powrotem.

> **Uściślenie do własnego komunikatu commita, zanim ktoś sprawdzi liczby i uzna, że kłamię.**
> W komunikacie napisałem „0 usuniętych linii". **`git numstat` pokazuje 3 i 4 usunięcia**
> w plikach 03 i 04 — bo dopisanie znacznika **na końcu istniejącej linii** git zapisuje jako
> usunięcie tej linii i dodanie nowej. **Treść nie zniknęła**; sprawdziłem to osobno, frazą
> po frazie: wszystkie **osiem** zdań o kredycie jest obecnych, każde ze znacznikiem
> (np. `03:251` „**Kredyt** zamiast zwrotu … **[POZA ZAKRESEM …]**").
>
> Zapisuję to, bo „0 usuniętych" było **liczbą z oczekiwania, nie z odczytu** — dokładnie tą
> klasą, którą tropię w kodzie. Poprawna postać zdania: **usunięć treści zero, usunięć linii
> siedem, wszystkie siedem wróciły w tym samym commicie z dopisanym znacznikiem.**

**3. `PLAN-FAZ.md` — dwa miejsca, oba wyjęte z zadań:**
- **wartości startowe reguł** — stało tam „kredyt za odsprzedany termin: **włączony**".
  Wyjęte, z wyjaśnieniem: nie ma wartości startowej, bo **nie ma reguły do skonfigurowania**.
- **lista zadań F3** — pozycja przekreślona ze wskazaniem decyzji, nie usunięta po cichu.

**4. Rejestr NON-DEFEKTÓW: `docs/NON-DEFEKTY.md` — założony, nie istniał.** Wpis **ND-01**
mówi wprost: przepisując ekran `/koordynacja/reguly` z makiety **pomiń wiersz o kredycie
świadomie**; nie kasuj wzmianek ze streszczeń; nie zgłaszaj braku testu kredytu jako luki
w pokryciu.

Dołożyłem tam **drugi wpis (ND-02)**, bo plik z jedną pozycją nie zostanie odruchem, a rzecz
jest świeżo zmierzona: `trap` przywracający pliki w perturbacjach **wygląda na nadmiarowy**
(każdy scenariusz i tak kopiuje plik z powrotem), a wczoraj **to on ograniczył szkodę**, gdy
zestaw wywalił się w połowie i ścieżka końca scenariusza nigdy się nie wykonała. Bez tego
zapisu ktoś „uprości" go przy pierwszym porządkowaniu.

## Jedno zdanie o tym, czego ta poprawka NIE załatwia

Znaczniki i wpisy chronią przed **odtworzeniem kredytu z makiety albo ze streszczenia**.
Nie chronią przed sytuacją odwrotną: gdyby właściciel decyzję **cofnął**, dziesięć znaczników
trzeba będzie zdjąć ręcznie i nic o tym nie przypomni. Zapisuję to jako znany koszt wybranego
rozwiązania, nie jako zastrzeżenie do zlecenia.

## Zakazy

Kodu nie tknąłem · `CLAUDE.md` nie tknąłem · cudzych repozytoriów nie tknąłem · `main`
nietknięty (`a5a4d8b`) · zero merge, deploy, nic poza fundację.
**Sprzeczności ze zleceniem: brak** — jedyne odstępstwo (dziesięć znaczników zamiast trzech)
jest zgodne z jego celem i opisane wyżej.
