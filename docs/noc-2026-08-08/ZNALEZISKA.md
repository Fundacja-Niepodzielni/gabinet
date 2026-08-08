# Znaleziska nocy z 8 na 9 sierpnia 2026

Jedno znalezisko = jeden wpis. Każdy ma: co, DOWÓD (komenda + wynik), wagę,
czy blokuje. Bez dowodu wpis nie powstaje.

**Reguła nocy: NAPRAWIAM PRZYRZĄD, NIGDY PRZEDMIOT.** Znaleziska weryfikatorów
są tu ZAPISYWANE, nie naprawiane — naprawa autorem tej samej nocy nie miałaby
rundy. Wyjątek: zepsuty sam przyrząd (bramka, perturbacja, skrypt weryfikatora),
bo bez niego kolejne rundy są bezwartościowe; taka naprawa jest w dzienniku
oznaczona jako NAPRAWA PRZYRZĄDU.

Numeracja: `N-1`, `N-2`, … dla znalezisk własnych; `R6A-*` / `R6B-*` zachowują
numerację nadaną przez weryfikatorów.

---

## N-1 — trzy komentarze w KODZIE PRODUKCYJNYM nadal niosły wniosek OBALONY

**Skutek po ludzku:** w kodzie stały zdania mówiące „zmierzyliśmy, że odświeżanie
tokenu wskrzeszało tożsamość" — a ten wniosek został wieczorem obalony pomiarem
kontrolnym. Następny czytelnik (człowiek albo agent) odziedziczyłby nieprawdziwą
diagnozę ubraną w słowo „zmierzone", i ścigałby przyczynę, której nie ma.

**Dowód:**

```
$ grep -rn "wskrzesz|świat 2|odtwarzał|potwierdzona pomiarem|POTWIERDZONY" (bez vendor)
backend/app/Tozsamosc/TozsamoscSesji.php:12:  Powód istnienia (noga 1 …, potwierdzona pomiarem)
backend/app/Tozsamosc/SesjaKonta.php:28:      odtwarzało ją z refresh tokenu (noga 1, świat 2)
backend/app/Tozsamosc/OdswiezanieSesji.php:64: bo odświeżanie odtwarzało ją z refresh tokenu
```

Dla kontrastu — w TYCH SAMYCH godzinach sprostowanie zostało wykonane w trzech
innych miejscach: `PLAN-FAZ.md:14`, `WYTYCZNE-PRACY.md:402,426` oraz w komentarzu
testu `OdebranieRoliTest.php:592`.

**Czyli:** sprostowanie objęło 3 z 6 miejsc. Etykieta cofnęła się tam, gdzie
autor patrzył (test, dokumenty stanu), a nie cofnęła się w kodzie produkcyjnym.
To jest dokładnie ta klasa, o której wykonawca napisał tego samego wieczora
lekcję („etykieta nazywa STAN WIEDZY i cofa się, gdy wiedza się cofa") — lekcja
została zapisana, ale nie zastosowana do końca.

**Waga:** średnia. Nie zmienia zachowania systemu ani jednego testu.
**Czy blokuje:** nie blokuje zamknięcia fazy, ale ZATRUWA następną sesję —
a to najdroższy rodzaj taniego błędu.

**Świat alternatywny:** żaden. Zdania są jednoznaczne i sprzeczne z późniejszym
pomiarem kontrolnym opisanym w `WYTYCZNE-PRACY.md:395–404`.

**Zrobione:** NAPRAWA PRZYRZĄDU (dokumentacja o kodzie jest przyrządem).
Trzy komentarze przepisane tak, żeby rozdzielały to, co ZMIERZONE (dwóch
pisarzy klucza `konta` — realne złamanie §2), od tego, co NIE (wskrzeszenie).
Uzasadnienie przebudowy stoi teraz na wymogu §2, który jest niezależny od
diagnozy nogi 1. Kod nietknięty — wyłącznie komentarze.

---

## N-2 — podłoga liczby testów przepuszczała skasowanie 10 z 17 plików kontrolnych

**Skutek po ludzku:** bramka miała pilnować, żeby nikt nie usunął testów po cichu.
Pilnowała słabo: można było skasować CAŁY plik kontrolny — łącznie z tym, który
pilnuje pozostałych kontroli — a bramka nadal świeciłaby zielono.

**Dowód:**

```
$ docker exec gabinet-app ./vendor/bin/pest
  Tests:    1 failed, 180 passed (640 assertions)      → 181 wykonanych

$ skrypty/bramka.sh (przed naprawą)
  MINIMUM_TESTOW=170        → zapas 11 testów
  MINIMUM_ASERCJI=590       → zapas 50 asercji
```

Rozkład wykonanych testów na pliki (zmierzony z pełnego wyjścia pesta):

```
 36 OcenaAnulacjiTest      8 SzkieletTest            4 RetencjaTest
 20 WalidatorTokenuTest    8 RejestrRegulTest        3 SekretyTest
 17 BramkiTest             7 BrakWlasnychHaselTest   3 RetencjaWykonanieTest
 16 LogowanieTest          5 SesjaBezJawnychDanychTest   2 ObietniceKomentarzyTest
 13 OdebranieRoliTest     11 GranicePienidzyTest
 13 ModelDanychTest       11 BlokadaWysylkiTest
```

W zapasie 11 testów mieści się skasowanie w CAŁOŚCI każdego z dziesięciu plików
o liczbie ≤ 11 — w tym `ObietniceKomentarzyTest` (2 testy), czyli **kontroli nad
kontrolami**, oraz `SesjaBezJawnychDanychTest` (5, RODO art. 9). Mieszczą się też
kombinacje, np. 2 + 3 + 3 = 8.

**To jest V-10 z rundy 5, zamknięte tylko pozornie.** Komentarz nad tą stałą
obiecywał „podłoga ma siedzieć TUŻ POD stanem bieżącym" i był NIEPRAWDZIWY wobec
własnej wartości — czyli obietnica bez pokrycia, ta sama klasa, przed którą
ostrzega `ObietniceKomentarzyTest`.

**Waga:** wysoka. Kontrola, którą da się obejść bez śladu w `git diff`, nie jest
kontrolą — a ta zostawiała ślad tak mały, że nikt by go nie zobaczył.
**Czy blokuje:** nie blokuje zamknięcia fazy, ale osłabia KAŻDY przyszły dowód
„bramka zielona".

**Świat alternatywny:** można twierdzić, że drugi sygnał (asercje) złapałby
skasowanie pliku. Sprawdzone: zapas asercji wynosił 50, a pliki o 2–5 testach
mają ich znacznie mniej — więc oba sygnały przepuszczały ten sam ruch. Dwa
sygnały chroniły przed tym samym, nie przed dwoma różnymi rzeczami.

**Zrobione:** NAPRAWA PRZYRZĄDU. Podłogi podniesione do 180 / 635 (jeden test
i pięć asercji pod stanem bieżącym), komentarz mówi teraz prawdę o własnej
wartości i o tym, kiedy ją podnosić.

---

## N-3 — DWIE perturbacje przestały cokolwiek psuć, a mimo to meldowały sukces

**Skutek po ludzku:** perturbacje to nasz dowód, że kontrole umieją zaświecić
czerwono. Dwie z nich nie psuły już NICZEGO — a mimo to raportowały „dowód
mutacji ✓" i zaliczały się jako udane. Czyli dwie kontrole były od dziś
wieczora **niesprawdzone**, a raport twierdził, że sprawdzone.

**Przyczyną jest MOJA WŁASNA zmiana z tego wieczora.** Commit `cdc6fbb`
(wąskie gardło §2) przemianował w `OdswiezanieSesji.php` zmienną `$konta`
na `$tozsamosc`. Perturbacje szukają tekstu do podmiany — i przestały go
znajdować.

**Dowód (wzorce, których szuka `perturbuj.py`, wobec kodu bieżącego):**

```
$ grep -cF 'if (! $this->wymagaOdswiezenia($konta)) {'      backend/app/Tozsamosc/OdswiezanieSesji.php
0
$ grep -cF 'if (RejestrSesji::uniewazniona(Typy::napis($konta'  backend/app/Tozsamosc/OdswiezanieSesji.php
0
$ grep -n "wymagaOdswiezenia(\|RejestrSesji::uniewazniona(" backend/app/Tozsamosc/OdswiezanieSesji.php
78:        if (RejestrSesji::uniewazniona($tozsamosc->sid())) {
84:        if (! $this->wymagaOdswiezenia($tozsamosc)) {
```

**Dlaczego to nie zapaliło się samo — trzy zabezpieczenia po kolei zawiodły:**

1. `podmien()` w `perturbuj.py` KRZYCZY, gdy wzorca nie ma (`SystemExit`) — to
   działa. Ale `perturbuj()` w `perturbacje.sh` **nie sprawdza kodu wyjścia**,
   a skrypt biegnie na `set -uo pipefail` — **bez `set -e`**. Zmierzone:
   `grep -n "^set " skrypty/perturbacje.sh` → `60:set -uo pipefail`.
   Czyli nieudana perturbacja nie przerywa scenariusza.
2. **Dowód mutacji ma GAŁĄŹ ZDEGENEROWANĄ.** Jest w formie NEGATYWNEJ —
   „starego tekstu już nie ma":
   ```
   dowod_mutacji "sprawdzanie wieku access tokenu zniknęło z kodu" \
       bash -c "! grep -q 'wymagaOdswiezenia($konta)' '$plik'"
   ```
   Wartość „prawda" jest zgodna z DWOMA światami: (I) mutacja weszła i usunęła
   tekst, (II) tekstu nigdy tam nie było, bo kod przemianowano. Zmierzone: świat
   II. Dowód mutacji zaświadczał o mutacji, której nie było.
3. `oczekuj_czerwone` zobaczyłoby czerwień i tak — bo `OdebranieRoliTest.php`
   ma dziś JEDEN test czerwony (noga 1). Czyli scenariusz zaliczyłby się
   z przyczyny **niezwiązanej** z badanym zjawiskiem. To jest P25 („perturbacja
   zaliczona z innej przyczyny niż badana") nałożone na gałąź zdegenerowaną.

**Zasięg klasy — przegląd WSZYSTKICH pięciu dowodów w formie negatywnej:**

| perturbacja | szukany tekst | jest w pliku? | werdykt |
|---|---|---|---|
| `role_zamrozone` (805) | `wymagaOdswiezenia($konta)` | **NIE (0)** | **ZDEGENEROWANY — przechodzi bez mutacji** |
| `logout_failsafe` (825) | `sidNiezweryfikowany` | **NIE (0)** | **ZDEGENEROWANY — przechodzi bez mutacji** |
| `wzmacniacz` (594) | `KLUCZ_ODSWIEZANIE, 1` | tak (1) | działa, ale forma nadal krucha |
| `id_token_sesja` (891) | `Crypt::encryptString($idToken)` | tak (1) | działa, ale forma nadal krucha |
| `uniewaznienie_sid` (964) | `RejestrSesji::uniewazniona` | tak (1) | **pada głośno** — tekst przetrwał zmianę nazw, więc negacja daje fałsz i scenariusz melduje „MUTACJA NIE WESZŁA W ŻYCIE" |

Ostatni wiersz jest pouczający: ta sama krucha forma raz zadziałała na naszą
korzyść, a dwa razy przeciwko — czyli o wyniku decydował PRZYPADEK doboru nazw,
a nie konstrukcja dowodu.

**Waga:** krytyczna dla przyrządu. Nie zmienia zachowania systemu, ale unieważnia
dowód „kontrola umie zaświecić czerwono" dla dwóch kontroli bezpieczeństwa
(odebranie roli, fail-safe wylogowania).
**Czy blokuje:** blokuje wiarygodność KAŻDEJ przyszłej rundy perturbacji.

**Świat alternatywny:** rozważony i odrzucony — można było twierdzić, że
`perturbuj.py` i tak przerwie przebieg. Zmierzone, że nie: brak `set -e`
i brak sprawdzenia kodu wyjścia w `perturbuj()`.

**Zrobione:** NAPRAWA PRZYRZĄDU — opisana osobno w `DZIENNIK.md`. Naprawiam,
bo to jawny wyjątek reguły nocy: bez sprawnych perturbacji kolejne rundy są
bezwartościowe.

---

## N-4 — `PLAN-FAZ.md` sam sobie przeczył: lista „OTWARTE, blokujące" niosła stan nieprawdziwy

**Skutek po ludzku:** plik, od którego zaczyna każda następna sesja, mówił
w jednym miejscu „jedyny czerwony to noga 1", a pięćdziesiąt linii niżej —
„test pozytywny BLK-22 jest CZERWONY i ma taki zostać". Sesja czytająca to jako
punkt wejścia zaczęłaby dzień od naprawiania testu, który działa.

**Dowód:**

```
PLAN-FAZ.md:15  „Bramka: CZERWONA — 1 nieudany krok z 22. Powód JEDEN … noga 1"
PLAN-FAZ.md:65  „BLK-22 — test pozytywny »żądanie po wylogowaniu dostaje 401«
                 jest CZERWONY i ma taki zostać do naprawy"

$ docker exec gabinet-app ./vendor/bin/pest
  ✓ it POZYTYWNY: żądanie PO wylogowaniu dostaje 401 — logout REALNIE z…  0.29s
  ⨯ it NOGA 1 [NIEROZSTRZYGNIĘTE — patrz komentarz]: tożsamość usunięta…  2.20s
  Tests:  1 failed, 180 passed (640 assertions)
```

Druga pozycja tej samej listy (**V-1**) mówiła „Projekt naprawy: D-2026-08-08-24",
choć naprawa jest WDROŻONA od commita `cdc6fbb` z tego samego wieczora.

**Waga:** wysoka — nie dla systemu, dla następnej sesji. To ta sama klasa co
`PROMPT-START.md` i nagłówek `CURRENT WORK`, które już raz kazały powtarzać
zamkniętą fazę. Plik stanu jest przyrządem: następna sesja startuje z jego
treści, więc jego nieaktualność propaguje się na wszystkie decyzje tej sesji.
**Czy blokuje:** nie technicznie; kosztuje czas i wiarygodność dokumentu.

**Świat alternatywny:** rozważony — może chodziło o INNY test o podobnej nazwie?
Odrzucony: w pliku jest dokładnie jeden test o tej treści i jest zielony.

**Zrobione:** poprawione ze SPROSTOWANIEM (nie cichą podmianą — ktoś mógł
przeczytać wersję nieprawdziwą). Przy pozycji 3 (V-4, V-8, V-9, W-8) wpisałem
wprost, że stanu **nie zmierzyłem** — „otwarte, bo nikt nie sprawdzał" to inny
stan wiedzy niż „otwarte, bo sprawdzone i czerwone", a zlanie ich w jedno jest
dokładnie tym, co wywróciło pozycję 1.
