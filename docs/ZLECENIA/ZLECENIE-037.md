# ZLECENIE-037 — mój dług, nie Twój: `K1`–`K10` nie wiążą niczego. Jedno zdanie do bramki F7.

**Od:** architekt · **09.08.2026, 21:50** · potwierdź zwyczajnie · **kolejki NIE zmieniam,
to jest wpis do dokumentu, nie pozycja robocza**

---

## 1 · Co jest nie tak — i to jest wada MOJEGO artefaktu

Napisałem dziesięć kryteriów odbioru frontendu (`_architektura/12-frontend-kryteria-odbioru.md`,
`K1`–`K10`: koszt najczęstszych czynności · widoczność stanu · odróżnienie czynności
nieodwracalnych · **reguła biznesowa nigdy w przeglądarce** · przeciętny sprzęt i łącze ·
obsługa z klawiatury · **mierzenie u człowieka** · **dane osobowe nie wyciekają ścieżką
interfejsu** · stany puste i błędu · jeden system projektowy).

**Bramka F7 w Twoim `PLAN-FAZ.md` ich nie zna.** Wymienia własne pozycje (WCAG, stany błędów,
responsywność), ale **nie ma niczego, co by wymuszało pokrycie moich dziesięciu.**

> **To jest `D3-artefakty-architekta`: twierdzenie w dokumencie bez egzekutora.**
> Klasa, której **właścicielem jest rola weryfikatora, a autorem egzemplarza jestem ja.**
> Dokument istnieje, brzmi wiążąco i **nie wiąże niczego.**

## 2 · ⚠ Czego NIE chcę: przepisania `K1`–`K10` do `PLAN-FAZ.md`

**Skopiowanie treści dziesięciu kryteriów do Twojego repozytorium utworzyłoby `P3`** — jedna rzecz
opisana w dwóch miejscach, bez niczego, co wymusza zgodność. Za pół roku poprawię jedno i nie
dotrze do drugiego. **Nie rób tego, nawet jeśli wyglądałoby porządniej.**

## 3 · Co ma wejść — jedno zdanie, egzekwujące POKRYCIE, nie treść

Do bramki F7 dopisz warunek w tym kształcie (sformułowanie Twoje, sens mój):

> **Bramka F7 nie jest zielona, dopóki każde z kryteriów `K1`–`K10`
> (`_architektura/12-frontend-kryteria-odbioru.md` — ŹRÓDŁO, nie kopia) nie ma albo
> przypisanej kontroli, albo JAWNEGO wpisu „bez kontroli" z powodem i warunkiem znoszącym.**
> **Kryterium nieprzypisane i niezadeklarowane → CZERWONE.**

**To jest domknięcie `D6` przyłożone do kryteriów odbioru:** dziesięć pozycji, do których nikt
nie napisał kontroli, i dziesięć pozycji świadomie zwolnionych **wyglądają dziś identycznie —
jako nieobecność**. Po tej zmianie nie wyglądają.

**Uwaga praktyczna:** `K7` („to, co widzi człowiek, mierzone u człowieka") **prawdopodobnie trafi
do wpisów „bez kontroli automatycznej" — i to będzie poprawne**. Helpdesk zamknął dziś dokładnie
taką pozycję obserwacją właściciela, z podaniem kto i kiedy patrzył. **Wpis z powodem jest
wynikiem, nie porażką.**

## 4 · Kiedy — NIE TERAZ

F7 jest odległa, a Ty masz `PODJETO-032` i `BEZ_DANYCH_OSOBOWYCH` przed sobą.
**Wpisz przy najbliższej okazji, gdy i tak będziesz dotykał `PLAN-FAZ.md`** — nie otwieraj tego
pliku wyłącznie dla tej linijki. **Nie licz tego jako pozycji roboczej.**

**Jeśli uznasz, że kształt z punktu 3 jest zły — powiedz.** To jest moja propozycja na Twój
dokument, a Ty masz w tym repozytorium więcej egzemplarzy działających bramek niż ja.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · realmu nie dotykasz · ścieżki bezwzględne · nic poza fundację ·
**S-2 i S-3 obowiązują.**
