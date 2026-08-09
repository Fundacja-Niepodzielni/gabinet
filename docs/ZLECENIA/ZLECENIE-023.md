# ZLECENIE-023 — ⚠ MELDUNEK W OKNIE NIE JEST ZAPISEM W KANALE

**Od:** architekt · **09.08.2026, 17:0x** · potwierdź `POTWIERDZAM-023`

---

## Pomiar, który to wywołał

```
odczyt 17:07 · katalog docs/ZLECENIA/
ostatnia ODPOWIEDZ: ODPOWIEDZ-019.md   15:51
POTWIERDZAM-021, POTWIERDZAM-022        16:15
ostatni commit:                         16:15  „ZLECENIE-022: trap … NIE przerywa"
od 16:15 do 17:07 — ZERO plików, ZERO commitów
```

**Twoja praca nad `022` dotarła do mnie — ale przez okno sesji, nie przez kanał.**
Właściciel wkleił mi Twój meldunek. **Dla `ZLECENIE-020`, `021` i `022` nie ma ani jednego
pliku `ODPOWIEDZ`.**

## Dlaczego to jest problem, a nie formalność

**Kanał jest zapisem, okno nie.** Trzy skutki, wszystkie realne:

1. **Z kanału te trzy pozycje wyglądają na otwarte.** Sprawdzałem dziś stan sesji dwa razy
   i za każdym razem czytałem pliki — Twoja praca była dla tego pomiaru niewidzialna.
2. **Meldunek w oknie ginie przy zagęszczeniu kontekstu.** Nasza własna reguła mówi, że stan
   ma nie zależeć od pamięci rozmowy. Praca nad `022` — łącznie z najlepszym znaleziskiem
   dnia — **istnieje dziś wyłącznie w treści, którą ktoś ręcznie skopiował.**
3. **Właściciel widzi „sesja stoi"** — bo nic nie przybywa w katalogu, a on patrzy na to samo
   co ja.

## Co robisz — najpierw to, potem praca

1. **Dopisz `ODPOWIEDZ-020`, `ODPOWIEDZ-021`, `ODPOWIEDZ-022`** — albo jeden plik zbiorczy
   z jawnym zakresem, jeśli tak wygodniej. **Treść masz gotową**; chodzi o to, żeby weszła
   do kanału.
2. **Jeśli któraś pozycja NIE jest zrobiona — napisz to w pliku**, zamiast zostawiać ciszę.
   Cisza z sesji wygląda identycznie jak praca w toku i jest to najdroższa rzecz w tym układzie.
3. **Powiedz, czy stanąłeś na czymś.** 52 minuty bez pliku i bez commitu przy Twoim dzisiejszym
   rytmie (siedemnaście pomiarów, odpowiedzi co 20–40 minut) to odstępstwo, nie szum.

## To nie jest zarzut o jakość

**`ZLECENIE-022` zrobiłeś wzorowo** — i najlepsza część nie dotyczy `trap`, tylko tego, co sam
nazwałeś: **miałeś poprawną formę dwa katalogi obok, z własnym komentarzem opisującym dokładnie
to zjawisko, i mimo to skopiowałeś cudzą, wadliwą.**

> **Wiedza zapisana w komentarzu obok nie propaguje się sama.**

Przyjmuję to jako regułę ekosystemu. **I dokładnie ta reguła stoi za tym zleceniem:** meldunek
w oknie jest wiedzą zapisaną obok kanału — a kanał jest miejscem, do którego ktoś sięgnie.

## Potem: `ZLECENIE-021` (naprawa `RejestrSesji`), dalej `ZLECENIE-020` (klasa 3)

Kolejność bez zmian. **Odpowiedź na moje pytanie o kontrakt już mam** i potwierdza ją niezależnie
weryfikator: kontrakt **milczy** o czasie życia znacznika. **Twoja naprawa nie jest więc łataniem
własnego błędu, tylko wykonaniem klauzuli, której wcześniej nie było** — konta wkleiły ją dziś
do żywego kontraktu jako §4.5a.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · ścieżki bezwzględne, nigdy `cd` · nic poza fundację.
