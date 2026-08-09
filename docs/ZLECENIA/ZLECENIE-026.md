# ZLECENIE-026 — `R6B-11`: kontrola „nic nie wystawione" pyta HTTP-em o Postgresa

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-026`, odpowiedz `ODPOWIEDZ-026.md`

---

## Decyzja o `werdykt()` — PRZYJMUJĘ Twoją, przeciw mojej sugestii

Proponowałem wspólny pomocnik. **Odrzuciłeś to i powód jest zmierzony, nie estetyczny:**
członkowie żyją w **trzech runtime'ach** (bash, Pest, osobny skrypt), więc wspólna funkcja
**musiałaby istnieć w trzech kopiach i sama stałaby się rzeczą, która się rozjeżdża** — czyli
klasą `D5`, którą złapałeś dziś przy rejestrze retencji.

**Twoje rozwiązanie jest lepsze i przyjmuję je w całości:** dołożyć **gałąź bazową do
istniejącego `oczekuj_czerwone`**, bo on już jest tym pomocnikiem dla członków bashowych
i brakuje mu dokładnie tej jednej nogi. Dla członków w PHP — **reguła plus kontrola nad
kontrolą**, co dziś zadziałało.

**To jest drugi raz dziś, gdy odmawiasz zbudowania czegoś, co bym zaakceptował, i podajesz
mierzalny powód.** Zapisuję jako wzorzec: **wspólny pomocnik ponad granicą runtime'u jest
klasą D5 w zalążku.**

## Odbiór reszty `ODPOWIEDZ-020` — trzy rzeczy

**1. Listę zweryfikowałeś pomiarem, nie przepisałeś.** Cztery z siedmiu potwierdzone jako
aktualne, z konkretami. **Trzech nie zweryfikowałeś i nie cytujesz ich stanu jako aktualnego** —
dokładnie tak, jak wymagałem, i to jest rzadsze niż się wydaje.

**2. Moja hipoteza „może to jeden mechanizm" sprawdziła się CZĘŚCIOWO i powiedziałeś to wprost.**
Jeden mechanizm dla jednej kontroli, nie wspólny mianownik siedmiu; dwie inne już filtrują
komentarze, dwie są denylistami, gdzie komentarz daje **awarię głośną**. **Nie zgrupowałeś ich
na siłę** — a to była pokusa, bo grupowanie ładnie wygląda w raporcie.

**3. Trzeci raz dziś Twoja własna kontrola miała gałąź zdegenerowaną:** skrypt „które kontrole
filtrują komentarze" odpowiedział **NIE dla wszystkich sześciu**, w tym dla dwóch, o których
wiesz, że filtrują. **Wynik dostałeś dopiero czytając asercje.** Zapisuję jako czwarty dziś
egzemplarz „przyrząd jest częścią pomiaru".

---

## POZYCJA · `R6B-11` — i uzasadnienie jest Twoje

> **Kontrola „nic nie wystawione publicznie" jest ZIELONA także wtedy, gdy Postgres słucha
> na adresie LAN — bo pyta HTTP-em, a Postgres nie mówi po HTTP.**

**To jedyny członek, którego fałszywe zielone znaczy WYSTAWIONĄ BAZĘ DANYCH.** Waga najwyższa
w tej klasie, osiągalność do zmierzenia. `bramka.sh:505`.

**Wymagania:**
1. **Kontrola CZERWONA przed naprawą** — Postgres nasłuchujący poza `localhost` **musi**
   zapalić. Dziś nie zapala i to jest przedmiot pomiaru.
2. **Sonda dopasowana do protokołu, nie jedna dla wszystkiego.** HTTP dla usług HTTP,
   **próba połączenia TCP** dla bazy. Cokolwiek wybierzesz — **musi umieć odróżnić „nie słucha"
   od „słucha, ale nie odpowiada po tym protokole"**. To jest sedno tej wady.
3. **Kierunek 0:** adres pusty, `0.0.0.0`, nazwa niesprawdzalna, port zajęty przez coś innego.
   **Nieznane → traktuj jak wystawione**, nie jak bezpieczne.
4. **Gałąź bazowa w `oczekuj_czerwone`** — Twoja decyzja, wykonaj ją **przy tej pozycji**,
   bo to ona jej potrzebuje. **Zamknie mechanizm, nie instancję**, jak sam napisałeś.
5. **Kontrola pozytywna:** poprawna konfiguracja (wszystko na `localhost`) **musi przejść** —
   inaczej dołożysz strażnika, który blokuje pracę, a takiego się wycisza.

**Uwaga o kontroli tekstowej:** przy sprawdzaniu, czy naprawa weszła, **filtruj komentarze** —
Twoja własna lekcja z dziś, i przy tej pozycji szczególnie łatwo o cytat starej sondy w opisie.

## Kolejność

Po `R6B-11` — reszta klasy 3, wg Twojego iloczynu. **Kolejki nie przestawiam.**

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · ścieżki bezwzględne, nigdy `cd` · nic poza fundację ·
**żadnego wystawiania czegokolwiek publicznie w trakcie pomiaru** — nasłuch testowy wyłącznie
na interfejsie lokalnym.
