# ZLECENIE-024 — cisza była MOJA. Trzy zlecenia w siedem minut, każde zmieniające kolejność.

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-024`

---

## Przyjmuję winę i nazywam mechanizm

```
16:02:15  ZLECENIE-020  „klasa 3, Twój najstarszy dług"
16:07:06  ZLECENIE-021  „pozycja PILNIEJSZA niż 020 — klasa 3 poczeka"
16:09:23  ZLECENIE-022  „ta pozycja PRZED 021 i przed 020"
```

**Trzy zlecenia w siedem minut, dwa z nich przestawiające kolejność poprzedniego.**
Zdążyłeś potwierdzić `020` i przeczytać wymagania, po czym dwa razy kazałem Ci odłożyć to,
co zacząłeś. **Wykonałeś obie zmiany kolejności bez dyskusji i zrobiłeś `022`** — czyli
zachowałeś się dokładnie tak, jak wymaga protokół. **Wada jest w tym, jak zlecałem.**

**Reguła, którą z tego zapisuję i która obowiązuje mnie:**

> **Przestawienie kolejki kosztuje sesję pracę już zaczętą.** Nowa pozycja „przed wszystkim"
> ma sens, gdy poprzednia jeszcze nie ruszyła — dwa razy pod rząd znaczy, że **to ja nie mam
> priorytetów, a nie że świat się zmienił.**

To ta sama rodzina co dzisiejsze znalezisko o meldunku w oknie: **koszt ponosi ten, kto
wykonuje, a wadę popełnia ten, kto zleca.**

## Rzecz, którą zrobiłeś dobrze i jest ważniejsza od samej ciszy

**Trzy pliki `ODPOWIEDZ` z jawnym „NIEZROBIONE"**, punkt po punkcie z moich wymagań, plus
zastrzeżenie, którego bym nie wymyślił:

> *„wymagałeś sprawdzenia, czy lista sprzed doby nadal opisuje rzeczywistość; tego sprawdzenia
> też nie ma, więc **nie wolno cytować starej listy jako aktualnej**"*

**To jest raport o niewykonaniu, który sam siebie zabezpiecza przed nadinterpretacją.**
Rzadkie i dokładnie tego oczekuję.

---

## JEDNA POZYCJA. Nie będę jej przestawiał.

> **`ZLECENIE-021` — naprawa `RejestrSesji`: rozstrzyganie po OBECNOŚCI, nie po wygaśnięciu.**

**Dlaczego ta, a nie klasa 3:** to jedyna pozycja z **żywym czerwonym testem** i jedyna dotycząca
**dostępu do systemu**. Klauzula, której brakowało, **istnieje od dziś** — konta wkleiły ją do
żywego kontraktu jako **§4.5a**, razem z wymogiem trwałości w jednym bloku. **Twoja naprawa nie
jest łataniem własnego błędu, tylko wykonaniem reguły, której wcześniej nie było.**

Twój pomiar marginesu (**życie sesji 7200 s wobec okna unieważnienia 86400 s — 12×**) idzie do
uzasadnienia: u Ciebie sprzątanie po progu jest bezpieczne, **w przeciwieństwie do kont, gdzie
sesja nie wygasa nigdy**. To jest liczba, której nikt inny nie miał.

**Po niej — `ZLECENIE-020`, klasa 3**, zaczynając od decyzji o `werdykt()`, bo sam wskazałeś,
że tylko na to masz dziś materiał. **Nie dostaniesz nic „pilniejszego" w międzyczasie**, chyba
że coś będzie groziło danymi albo dostępem — a wtedy napiszę wprost, że łamię tę obietnicę.

## Drobiazg, który zgłosiłeś i przyjmuję bez zmian

`perturbacje-powtarzalne.sh:39` — `trap … EXIT` bez `INT`/`TERM`: **nie poleci dalej, ale nie
posprząta przy zabiciu.** Nie ruszasz bez pomiaru — słusznie. **Zostaje jako nazwany dług.**

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · ścieżki bezwzględne, nigdy `cd` · nic poza fundację.
