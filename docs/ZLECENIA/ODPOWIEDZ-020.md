# ODPOWIEDŹ-020 — klasa 3: **NIEZROBIONA**. Cisza była moja, powód poniżej

**Stan: NIEZROBIONE. Ani jednego z siedmiu członków nie tknąłem.**
Kanał: plik **16:02:15.463** → obudzony **16:02:51.276** = **35,8 s** (osiemnasty pomiar).

> Ten plik istnieje po to, żeby pozycja **nie wyglądała z kanału na w toku**. Nie mam tu
> wyniku do oddania — mam stan i powód.

## Co się stało

Potwierdziłem `ZLECENIE-020` i zdążyłem przeczytać wymagania. Zanim zacząłem, przyszło
**`ZLECENIE-021`** z jawnym „pozycja pilniejsza niż `ZLECENIE-020` — klasa 3 poczeka",
a zaraz po nim **`ZLECENIE-022`** z „ta pozycja **przed** `021` i przed `020`".

**Obie zmiany kolejności są Twoje i obie wykonałem.** `022` jest zrobione i zacommitowane
(`2e4b69e`, 16:15:30). Klasa 3 nie ruszyła z miejsca — i **nie zapisałem tego w kanale**,
przez co z katalogu wygląda jak praca w toku. To jest wada, którą nazwałeś w `ZLECENIE-023`.

## Czego NIE zrobiłem — punkt po punkcie z Twoich wymagań

1. **Lista siedmiu członków z aktualnym stanem** — nie sporządzona. Zaznaczam, że wymagałeś
   przy niej sprawdzenia, **czy lista sprzed doby nadal opisuje rzeczywistość**; tego
   sprawdzenia też nie ma, więc nie wolno cytować starej listy jako aktualnej.
2. **Uporządkowanie wg iloczynu wagi i osiągalności** — nie zrobione.
3. **Zamknięcie choćby jednego członka** — zero.
4. **Decyzja o wspólnym pomocniku `werdykt()`** — nie podjęta. To jedyna rzecz, na którą mam
   dziś materiał (odczyt statyczny i dynamiczny z `ZLECENIE-009`), więc byłaby pierwsza.
5. Jedyne, co z tej klasy przybyło dziś ubocznie: **odczyt dynamiczny** (`ZLECENIE-009`)
   i **zapadka przyczyn** (`ZLECENIE-006`) — ale to przyrząd do mierzenia klasy 3,
   **nie zamknięcie żadnego z jej członków**.

## Kolejność, jak ją rozumiem teraz

`ZLECENIE-021` (naprawa `RejestrSesji`, mam Twoją zgodę i plan) → potem `ZLECENIE-020`.
Jeśli chcesz odwrotnie — powiedz; sam nie zmieniam kolejności, którą ustaliłeś.
