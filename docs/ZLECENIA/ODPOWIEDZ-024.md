# ODPOWIEDŹ-024 — `RejestrSesji`: rozstrzyga OBECNOŚĆ. **ZROBIONE**

Kanał: plik **17:14:39.525** → obudzony **17:15:20.086** = **40,6 s** (dwudziesty pomiar).

## Para czerwone-przed / zielone-po — czerwień znikła Z TEGO SAMEGO POWODU

```
PRZED (stan z ZLECENIE-015):
  ⨯ znacznik po terminie, który wciąż istnieje, przestaje blokować
    „TTL POTRAKTOWANY JAKO PRAWO WSTĘPU: znacznik NADAL ISTNIEJE, a system przepuszcza…”

PO:
  ✓ 6 passed (16 assertions)

PERTURBACJA — przywracam STARĄ WADĘ (`->where('wygasa_at', '>', now())` z powrotem):
  ⨯ znacznik PO TERMINIE, który wciąż istnieje, NADAL BLOKUJE
  ⨯ KIERUNEK 0: stempel PUSTY, NIECZYTELNY albo Z PRZYSZŁOŚCI też blokuje
    ten sam komunikat co wyżej, co do znaku
```

Czerwień wraca **z tej samej przyczyny i tym samym zdaniem** — nie zniknęła przez obejście
ścieżki, tylko przez usunięcie warunku, który ją powodował.

## Co zrobiłem — punkt po punkcie z Twoich wymagań

**1 · Rozstrzyganie na OBECNOŚCI.** Z zapytania decydującego zniknął warunek na wiek:

```php
return DB::table('uniewaznione_sesje')
    ->where('sid_skrot', hash('sha256', $sid))
    ->exists();
```

**2 · Wiek zredukowany do progu SPRZĄTANIA**, na ścieżce mutującej (`sprzataj()`), z wynikiem
**odbieranym i sprawdzanym odczytem** — nie ufam liczbie z `delete()`, tylko pytam bazę drugą
drogą, ile wierszy NAPRAWDĘ zniknęło.

**3 · Kierunek 0 — trzy warianty, wszystkie blokują:** stempel **z przyszłości** (5 lat
w przód), stempel **równy „teraz"**, stempel **prehistoryczny** (1970). Wariant „z przyszłości"
jest tu istotny: gdyby gdziekolwiek został warunek na wiek, taki stempel mógłby zostać uznany
za „jeszcze nieważny".

**4 · Kontrola progu — to jest część, której nie było w wymaganiach, a wynika z Twojej liczby:**

```php
próg sprzątania (86400 s)  ≥  najdłuższa możliwa sesja lokalna (7200 s)
```

Zapisałem margines **jako kontrolę, nie jako założenie**. Gdyby próg był krótszy niż życie
sesji, sesja przeżywałaby własny znacznik i **sprzątaczka sama odblokowywałaby wylogowanego** —
czyli wiek przyznawałby dostęp okrężną drogą. **To jest dokładnie ta strona, której kontom
brakuje** (`SessionStore` nie sprawdza wieku rekordu wcale, `ODPOWIEDZ-018`). U mnie warunek
jest spełniony z zapasem 12× i od teraz pilnuje go test, a nie komentarz.

**5 · Kierunek odwrotny:** brak znacznika **nie** blokuje, pusty `sid` **nie** blokuje — bez
tego wszystkie „blokuje" byłyby spełnione także przez implementację zwracającą `true` zawsze.

## Rzecz, którą złapałem u siebie w trakcie: `sprzataj()` NIE MIAŁA WYWOŁUJĄCEGO

Napisałem funkcję sprzątającą i **zero wywołań w kodzie produkcyjnym** — tylko testy.
To jest `R6A-11` od nowa, w pozycji naprawiającej inny defekt, kilka godzin po tym, jak
zamykałem tamten. Zmierzone `grep`em z kontrolą pozytywną, nie zauważone z głowy.

Podłączona do `zakoncz()` — jedynej ścieżki, która i tak mutuje tę tabelę, i rzadkiej, więc
koszt jest znikomy. Wynik jest **odbierany**, nie porzucany jako `sprzataj();`.

## Dwa zdegenerowane odczyty — MOJE, w ciągu pięciu minut

Sprawdzałem po perturbacji, czy przywróciłem wersję naprawioną:

```
grep -c "wygasa_at"          w metodzie → 1   ← wyglądało na WADLIWĄ
grep -c "->where('wygasa_at'" w metodzie → 1   ← nadal wyglądało na wadliwą
grep, z odfiltrowanymi komentarzami      → 0   ← naprawiona
```

**Przyczyna: komentarz w naprawionej wersji CYTUJE DOSŁOWNIE kod, który zastąpił** („Do 09.08
stało tu `->where('wygasa_at', '>', now())`"). Każde sprawdzenie po treści łapało cytat.

Lekcja szersza niż ten przypadek: **komentarz cytujący usunięty kod psuje wszystkie kontrole
tekstowe nad nowym kodem** — a takich kontroli mam kilka. Zapisuję jako rzecz do sprawdzenia
przy klasie 3, bo to dokładnie „jedna wartość, dwa światy".

## Stan zmierzony

```
216 zielonych · 2 pominięte · 1 CZERWONY · 1896 asercji
  jedyny czerwony: NOGA 1 — przyrząd, znany.  Czerwień TTL ZNIKŁA.
podłogi bramki: 212/1877 → 216/1891 · pint: PASS, 87 plików
```

**Po raz pierwszy dziś suita ma tylko jeden czerwony** — i jest nim ten, który ma zostać.

## Czego NIE zrobiłem

- **Nie tknąłem klasy 3** — następna pozycja, zaczynam od decyzji o `werdykt()`.
- `perturbacje-powtarzalne.sh:39` zostaje nazwanym długiem, zgodnie z Twoim odbiorem.
- **Pełnej bramki nie przebiegłem** — zmieniałem `bramka.sh`. Własnej pracy nie zamykam.

## Zakazy

Zero `main`, merge, deploy · ścieżki bezwzględne, nigdy `cd` · nic poza fundację ·
perturbacja cofnięta kopią, nie `git checkout`. **Sprzeczności: brak.**
