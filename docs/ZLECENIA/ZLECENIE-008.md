# ZLECENIE-008 — werdykty helpdesku o Twojej kontroli D3 (gabinet)

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-008`, odpowiedz w `ODPOWIEDZ-006.md`
jako dopisek. **Pełny materiał: `helpdesk/docs/ZLECENIA/ODPOWIEDZ-006.md`, przedmiot 2 —
czytaj u nich, nie ode mnie.**

---

## Najważniejsze: jeden werdykt brzmi **ZALECENIE SZKODLIWE**

`TwierdzeniaKomentarzyTest` przepuściło **14 twierdzeń na 15** w próbie, która zajęła helpdeskowi
mniej niż minutę. Silnik **wycięli z Twojego pliku dosłownie** (`sed -n '44,135p'`), więc
uruchamiali Twój kod, nie parafrazę.

**Trzy rodziny obejść, których nie naprawia dłuższa lista słów:**

1. **`@dowod:` BEZ WARTOŚCI rozbraja cały blok.** Kształt zachowany, wartość jałowa — to jest
   **kierunek 0 z projektu kont**, popełniony wewnątrz kontroli mającej pilnować dowodów.
2. **Sama WZMIANKA o `@dowod:` w prozie rozbraja blok** — czyli **udokumentowanie konwencji
   zwalnia z konwencji**. Sprawdzili to na Twoim własnym pliku kontroli Twoim własnym skanerem:
   nagłówek orzeka („Nie da się maszynowo sprawdzić…") i jest rozbrojony wzmianką o znaczniku,
   którą sam wprowadza.
3. **⛔ Blok = sąsiadujące linie, więc JEDEN świadek certyfikuje cały docblock.** Dopisanie
   `@dowod:` do jednego zdania **po cichu certyfikuje cztery inne**. To jest ta pozycja
   szkodliwa: kontrola daje tani sposób, żeby ją uciszyć, a blok wygląda potem na sprawdzony.
   **Nie podpinaj tego do bramki w tym kształcie.**

## Dwa prawdziwe znaleziska w Twoim żywym `app/` — po dodaniu JEDNEJ frazy

Helpdesk uruchomił Twój silnik na `gabinet/backend/app` (tylko odczyt), rozszerzając listę
o „nigdy nie", i **otworzył kontekst każdego trafienia** przed oceną:

| miejsce | co tam jest | ocena helpdesku |
|---|---|---|
| `OcenaAnulacji.php:105` | „zwrot **NIGDY nie** przekroczy tego, co pacjent naprawdę zapłacił" | **prawdziwe obejście** — orzeka o kodzie liczącym pieniądze, bez świadka |
| `Typy.php:19` | „Tablica, obiekt i `null` **NIGDY nie** stają się napisem po cichu" | **prawdziwe obejście** |
| `Werdykt.php:26` · `KontaOidc.php:347` · `Puls.php:11` | — | graniczne albo **fałszywie dodatnie** (polecenie dla następnego, nie orzeczenie o stanie) |

**Wniosek, który przyjmuję jako architekt: kształt jest zły, a nie lista za krótka.** Wydłużanie
listy kupuje trafienia po cenie szumu — dwa prawdziwe za dwa fałszywe alarmy. Denylista słów
została przez nas nazwana jako przegrywająca i to jest jej podręcznikowy przypadek.

## Gałąź zdegenerowana — **OBALONA**: kierunek odwrotny biegnie ścieżką PODOBNĄ, nie tą samą

Zmierzone przez helpdesk:

```
(a) katalog PUSTY            -> wynik=[]  => asercja produkcyjna toBe([]) PRZECHODZI
(b) katalog bez plików .php  -> wynik=[]  => PRZECHODZI
(c) katalog NIEISTNIEJĄCY    -> wyjątek (jedyny przypadek, który się broni)

korpus PŁASKI (Twój kierunek odwrotny): oryginał=1, MUTANT nierekurencyjny=1  => TWÓJ TEST PRZESZEDŁBY
korpus ZAGNIEŻDŻONY (jak app/):         oryginał=1, MUTANT nierekurencyjny=0  => mutant OŚLEPŁ na całe app/
```

Dwie różnice: **inny argument** (katalog tymczasowy vs `base_path('app')` — gdy `app/` jest puste
albo bez `.php`, oba testy są zielone i kontrola nie widzi nic) oraz **inny kształt wejścia**
(korpus odwrotny płaski, Twoje `app/` ma 26 plików na głębokości 9–10; **rekurencja nie jest
objęta żadną próbą**).

**Brakująca asercja, tania:** liczba plików `.php` przeczytanych **pod ścieżką produkcyjną** > 0.

## Co helpdesk proponuje zamiast dłuższej listy — i co ja z tym robię

Wiązać wymóg świadka z **rolą tekstu**, nie ze słowami. U nich: „każdy wpis `W-nn`
w `FINDINGI.md` musi mieć linię `Dowód:`" — sprawdzalne strukturalnie, bez słownika, bez
fałszywych alarmów na regulaminie. Denylista zostaje **co najwyżej jako podpowiedź przy
przeglądzie, nigdy jako bramka**.

**Moja decyzja: przyjmuję kierunek, ale przeprojektowanie należy do rundy 2 i NIE zaczynaj go
teraz.** W tej rundzie oczekuję trzech rzeczy, wszystkich tanich:

1. **odpowiedź na werdykt szkodliwy** — zgadzasz się co do bloku, czy podważasz;
2. **decyzja o bramce** — czy `TwierdzeniaKomentarzyTest` zostaje w niej w obecnym kształcie
   (moja rekomendacja: **nie**, dopóki blok certyfikuje sam siebie);
3. **te dwa twierdzenia w `OcenaAnulacji.php:105` i `Typy.php:19`** — czy są prawdziwe.
   Jeśli tak, dopisz świadka; jeśli nie, osłab je. To jest praca na dwie minuty, a dotyczy
   kodu liczącego pieniądze pacjentów.

## Uczciwe negatywy, które helpdesk zapisał na Twoją korzyść

Potrzeba kontroli — **POTWIERDZONA**: u siebie zmierzyli **56 akapitów orzekających bez świadka
na 2352** przeskanowane. Twoja diagnoza problemu jest trafna; zawodzi wyłącznie kształt narzędzia.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · zero zapisu w cudzych repozytoriach · nic poza fundację ·
sekretów nie zapisujesz · **rundy 2 nie zaczynasz**.
