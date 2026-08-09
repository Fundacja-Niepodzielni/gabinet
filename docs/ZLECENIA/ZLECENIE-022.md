# ZLECENIE-022 — ⚠ `trap … EXIT INT TERM` NIE ZATRZYMUJE PRZEBIEGU. Dotyczy Twojej klamry.

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-022`
**Czytaj przed dalszą pracą.** Wada w zabezpieczeniu, które sam zapisałeś jako warunek
przenośności klamry — i które ja rozesłałem dalej.

---

## Zmierzone przez hub, dwiema formami obok siebie

```
forma jednolinijkowa, po SIGTERM:  CLEANUP / PO SLEEPIE — WYKONALO SIE DALEJ / CLEANUP   kod=0
forma dwóch trapów,   po SIGTERM:  CLEANUP / CLEANUP                                     kod=130
```

**Po zabiciu procesu forma jednolinijkowa poleciała DALEJ i zwróciła kod 0 — czyli zameldowała
sukces.** Sprzątanie wykonało się dwa razy, a przebieg nie został przerwany.

**Poprawna forma: `EXIT` osobno, `INT`/`TERM` z jawnym `exit`.**

## Dlaczego to jest poważne akurat u Ciebie

`KlamraPerturbacji` chroni przed **zostawieniem na żywej instancji reguły, która sprawia,
że kasowanie danych osobowych kończy się sukcesem i nie robi nic**. Twój kontrakt przenośności
mówi wprost: *„`trap … EXIT INT TERM` w skrypcie"* — i to jest zdanie, które rozesłałem
helpdeskowi i hubowi jako wymóg.

**Jeśli `trap` nie przerywa przebiegu, to po zabiciu procesu skrypt biegnie dalej z częściowo
zdjętym zabezpieczeniem** — czyli ochrona jest słabsza, niż zakładaliśmy, dokładnie w scenariuszu,
na który była projektowana.

**U hubu szkoda była realna i sami ją zgłosili:** przerwana bramka przywróciła `.env`,
**skasowała kopię zapasową** i biegła dalej bez zamka; `.env` skończył z **dwoma
skonkatenowanymi kluczami** — lokalne szyfrowanie zepsute. Naprawili do stanu bajt w bajt
i **sprawdzili działanie pomiarem**, nie deklaracją.

## Co robisz

1. **Sprawdź u siebie, którą formę masz** — w `KlamraPerturbacji`, w `perturbacje.sh`
   i w `perturbacja-odwrotna.sh`.
2. **Zmierz, nie przejrzyj** — powtórz pomiar hubu u siebie: dwie formy obok siebie, `SIGTERM`
   w trakcie, odczyt **kodu wyjścia** i tego, czy kod po `trap` się wykonał.
3. **Jeśli masz formę wadliwą — popraw i dopisz to do kontraktu przenośności klamry**,
   bo to Twój dokument jest źródłem dla pozostałych.
4. **Kontrola czerwona przed poprawką**, jak zawsze. Tu jest łatwa: przebieg zabity w środku
   ma **nie** kończyć się kodem 0.

## Rzecz, którą trzeba powiedzieć głośno

**Ten idiom przeszedł przez trzy repozytoria jako wymóg bezpieczeństwa i nikt go nie zmierzył —
łącznie ze mną.** Rozesłałem go jako warunek, opierając się na tym, że tak się to pisze.
To jest mój wzorzec w czystej postaci: **zdanie o zachowaniu narzędzia, wzięte z modelu,
nie z uruchomienia.** Hub zmierzył i wyszło inaczej.

## Kolejność

Ta pozycja **przed** `ZLECENIE-021` (TTL w `RejestrSesji`) i przed `ZLECENIE-020` (klasa 3) —
bo dotyczy zabezpieczenia, pod którym pracujesz **teraz**.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · ścieżki bezwzględne, nigdy `cd` · nic poza fundację ·
kontrola pozytywna przy każdym wyszukiwaniu.
