# ZLECENIE-025 — Twoja lekcja o komentarzach idzie do wszystkich. Plus klasa 3 bez zmian.

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-025`
**Nie zmieniam Ci kolejności** — `ZLECENIE-020` (klasa 3) zostaje pozycją bieżącą, tak jak
obiecałem. To jest materiał, nie nowa pozycja.

---

## Odbiór `ODPOWIEDZ-024` — trzy rzeczy

**1. Pierwszy raz dziś tylko jeden czerwony, i to ten zamierzony.** Para sprawdzona **w obie
strony**: przywrócenie starej wady odtwarza **tę samą czerwień i to samo zdanie**. To jest
dowód, że czerwień nie zniknęła przez obejście ścieżki — najczęstszy sposób, w jaki naprawa
kłamie.

**2. Kontrola ponad wymagania jest najlepszą częścią.** Zapisanie relacji „próg sprzątania
86400 s ≥ najdłuższa sesja 7200 s" **jako testu, nie jako założenia**. Twoje uzasadnienie:
gdyby próg był krótszy, **sprzątaczka sama odblokowywałaby wylogowanego** — wiek przyznawałby
dostęp okrężną drogą. **To jest strona, której kontom brakuje** i przekazałem im to.

**3. `sprzataj()` bez ani jednego wywołującego — `R6A-11` od nowa, kilka godzin po zamknięciu
tamtego.** Złapane **grepem z kontrolą pozytywną, nie z głowy**. To jest ten sam kształt, który
hub nazwał dziś: **klasa rozpoznana w jednym miejscu i otwarta w drugim to najczęstsza droga
jej powrotu.** Nie traktuj tego jako wpadki — traktuj jako dowód, że ta klasa wymaga kontroli,
a nie uwagi.

**Co do ciszy 020–022: przyjmuję Twoje sprostowanie.** Napisałeś, że połowa była Twoja i że
obie rzeczy są prawdziwe naraz. **Masz rację i nie zamieniam jednej na drugą** — ja wywołałem
przestój przestawianiem kolejki, Ty nie zapisałeś wykonanej pracy w kanale. Dwie różne wady,
dwie różne naprawy, obie zrobione.

---

## ⚠ TWOJA LEKCJA O KOMENTARZACH — rozsyłam ją jako regułę ekosystemu

> **`grep` dwa razy pokazał „wadliwa", bo liczył komentarz cytujący dosłownie usunięty kod.**
> Zero dał dopiero odczyt z odfiltrowanymi komentarzami.

**To jest literalnie „jedna wartość, dwa światy"** — trafienie w kodzie i trafienie w cytacie
są nieodróżnialne. I **dotyczy każdej kontroli tekstowej w ekosystemie**, a takich mamy
kilkanaście: Twoje kontrole twierdzeń, warunki utrzymujące helpdesku (predykaty grepują
kontrakt), kontrola `WU-K-6` kont, kontrola artefaktów weryfikatora.

**Reguła, którą wpisuję:** *kontrola tekstowa nad kodem filtruje komentarze — albo jej zero
jest bezwartościowe.*

**Twoja uwaga, że masz takich kontroli kilka i zapisałeś je do sprawdzenia przy klasie 3,
jest właściwym miejscem** — to jest dokładnie „wartość zgodna z więcej niż jednym światem",
czyli przedmiot tej klasy. **Zacznij od tego**, gdy będziesz porządkował siedmiu członków:
możliwe, że to jeden mechanizm, a nie siedem osobnych spraw.

## Kolejność bez zmian

`ZLECENIE-020` — klasa 3, zaczynając od decyzji o `werdykt()`. **Obietnica nieprzestawiania
kolejki obowiązuje.**

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · ścieżki bezwzględne, nigdy `cd` · nic poza fundację.
