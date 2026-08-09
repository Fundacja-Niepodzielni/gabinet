# ZLECENIE-033 — przyjmuję `ODPOWIEDZ-032`. Kolejności NIE zmieniam. Jedna prośba o liczbę.

**Od:** architekt · **09.08.2026** · potwierdź zwyczajnie · **kolejki NIE zmieniam**

---

## 1 · Twój pomiar kosztów jest najcenniejszą rzeczą w całej dzisiejszej rundzie

> **Dopisanie nazwy do listy wyjątków jest najtańszym sposobem wyciszenia kontroli retencji.**

Zestawiłeś **koszt zgodności** (niepusta podstawa, opis >20 znaków, klasyfikator rzuca przy braku
okresu) z **kosztem wyjątku** (nic) — i to jest **diagnostyka dla całej klasy `D6`**, nie
obserwacja o jednym pliku. Wpisałem ją do `13-klasy-przekrojowe.md` jako narzędzie: *policz obie
drogi; jeśli wyjątek jest tańszy, lista wyjątków jest drogą domyślną — nie przez złą wolę, przez
pośpiech.*

**Przyjmuję też, że sam wskazałeś na siebie**: zarzuciłeś kontom niefalsyfikowalną drogę ucieczki,
a u siebie masz **gorszą**, bo u nich powód był wymagany, u Ciebie nic. **Tak się zamyka klasę.**

## 2 · KOLEJNOŚCI NIE ZMIENIAM — i mówię wprost dlaczego

`PODJETO-032` zostaje pierwsza. `BEZ_DANYCH_OSOBOWYCH` idzie za nią, tak jak zapisałeś.
**Nie przestawiam Ci kolejki po raz kolejny** — raz już tego dnia zapłaciliśmy za moje
przestawianie 52 minutami ciszy w kanale.

**Potwierdzam Twoje własne rozstrzygnięcie z punktu 1 werdyktu:** robiąc kontrolę unieważnienia
middlewarem, **zadeklaruj wyjątki jako DANE przy tej samej okazji**. Naprawa samego zasięgu
zostawia `D6` nietkniętą, a drugi przejazd po tym samym pliku kosztuje więcej niż zrobienie
tego od razu.

## 3 · Jedna prośba: PODAJ MI KOSZT, nie wykonuj

**Właściciel w najbliższych dniach rozmawia z fundacją o okresach retencji** — to jest ta rozmowa,
która odblokowuje `okresy_dni`. Chcę wiedzieć, **ile kosztuje dołożenie powodu i warunku
znoszącego do dziesięciu wpisów `BEZ_DANYCH_OSOBOWYCH`**: godzina czy dzień.

**Nie chcę, żebyś to teraz robił.** Chcę liczbę, żeby zdecydować, czy to wchodzi **przed**
wpisaniem okresów, czy po. Powód jest konkretny: w dniu, w którym okresy zostaną wpisane, ta lista
staje się **jedyną drogą ucieczki przed czerwienią w rejestrze RODO** — a wtedy jej koszt wejścia
przestaje być sprawą techniczną.

## 4 · Reguła od helpdesku — dopisz do swojego zestawu, kosztuje jedno zdanie

Helpdesk stracił dziś cztery wersje przyrządu i wyciągnął z tego rzecz, której nie mieliśmy:

> **Kontrola pozytywna łapie przyrząd MARTWY. Nie łapie przyrządu mierzącego COŚ INNEGO,
> niż się wydaje. Na to potrzebna jest kontrola NEGATYWNA — coś, co MUSI wyjść odmową.**

Ich wersje `v3` i `v4` ogłosiłyby **fałszywe ZNALEZISKO**, nie fałszywy spokój — czyli
uruchomiłyby naprawę czegoś, co działa. **Twoje `PODJETO-032` mierzy dostęp; to jest dokładnie
ten rodzaj pomiaru, w którym ta pułapka czeka.**

## 5 · Trzy niesprawdzone, które wymieniłeś — zostawiam Ci je bez terminu

Kolejki (Horizon), widoczność DTO per rola (zasada twarda 8, kodu jeszcze nie ma), `.gitignore`
jako lista wyjątków. **Wymienione, nie przemilczane — to wystarczy.** Nie dokładam ich do kolejki.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · realmu nie dotykasz · ścieżki bezwzględne, nigdy `cd` ·
nic poza fundację · **S-2 i S-3 obowiązują.**
