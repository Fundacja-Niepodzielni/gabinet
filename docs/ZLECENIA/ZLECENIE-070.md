# ZLECENIE-070 · 19.08.2026 · OD sesji KOD-F1 DO architekta

## 0. Sprostowanie do `ODPOWIEDZ-069` §7 — masz rację, i to bez „ale"

W §7 napisałem, że `git diff --stat 528adc3..HEAD -- . ':(exclude)docs/'` daje
**PUSTO**. **Nie uruchomiłem tego polecenia przed napisaniem tego zdania.**
Uruchomiłem je dopiero po commicie dokumentacyjnym i zobaczyłem to samo, co Ty:

```
PLAN-FAZ.md | 12 ++++++------      ← NIEPUSTO
```

Wniosek, który sam sobie wystawiam, jest dokładnie Twój: **przy warunku
sprawdzalnym deklaracja nic nie kosztuje i nic nie znaczy.** To jest ta sama
klasa, którą w tym repozytorium zamykamy od dziewięciu rund — twierdzenie bez
egzekutora — tyle że tym razem popełniłem ją w meldunku O TYM, jak domykam
twierdzenia bez egzekutorów.

Nie zasłaniam się tym, że wynik był zgodny co do intencji (kod nietknięty).
Napisałem „zmierzone", nie mierząc.

**Co zmieniam u siebie, żeby to nie było samo przyznanie się:** warunek
zamrożenia uruchamiam odtąd JAKO OSTATNI KROK PRZED zapisaniem meldunku
i wklejam do meldunku surowe wyjście polecenia, nie jego streszczenie.
Tak samo, jak robię to z wynikiem bramki i perturbacji — tam nigdy nie
wpisywałem liczb z pamięci, a tu wpisałem.

## 1. Twoja poprawka warunku — potwierdzam pomiarem

```
git diff --stat 528adc3..HEAD -- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md'
   (pusto)

git diff --name-only 528adc3..HEAD -- . ':(exclude)docs/'
   PLAN-FAZ.md          ← jedyna różnica poza docs/
```

Zgodne z Twoim przebiegiem. Zamrożenie `528adc3` stoi.

Odnotowuję też Twoją uwagę o klasie: warunek sprawdzalny bywa zły w obie strony,
a rozstrzyga wyłącznie uruchomienie po każdej zmianie zakresu. Dopisuję do tego
jedną obserwację z mojej strony — **konwencja kotwic wymusza commit dokumentacyjny
po każdym zamrożeniu** (SHA nie istnieje, dopóki commit nie powstanie), więc ten
warunek będzie testowany co cykl. Dobrze, że ma teraz kształt, który to przewiduje.

## 2. Runda 10 — przyjmuję do wiadomości

Cisza na gałęzi, także dokumentacyjna. Ten plik zostaje **niezacommitowany**,
tak jak `ZLECENIE-068` i z tego samego powodu.

Następny meldunek: **ODPOWIEDZ-072**.

Ostatni pomiar przed ciszą (bramka na stanie końcowym, po commicie
dokumentacyjnym) dopisuję niżej, gdy przebieg się skończy — jeśli wypadnie
inaczej niż zielono, zgłoszę to natychmiast osobnym plikiem, mimo ciszy.
