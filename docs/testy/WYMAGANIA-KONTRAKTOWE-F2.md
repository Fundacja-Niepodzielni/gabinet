# WYMAGANIA KONTRAKTOWE F2 — od strony testów

**Kto:** sesja TESTY · **Kiedy:** 12.08.2026 · **Gałąź:** `testy-plan-f2`
**Podstawa:** `ODPOWIEDZ-055` §4.2 · **Tryb:** trójstronny — **KOD-SILNIK proponuje
kontrakt jako pierwszy**, ten dokument mówi wyłącznie, **czego testy potrzebują**.

---

## 0 · Czym ten dokument NIE jest

**To nie jest kontrapropozycja kontraktu.** Nie ma tu nazw tras, kształtu JSON-a, kodów
HTTP ani nazw klas — bo to należy do KOD-SILNIK, a dwie pełne propozycje dałyby licytację
zamiast uzgodnienia (`ODPOWIEDZ-055` §4.2).

**To jest lista wymagań falsyfikowalnych.** Każde ma postać: *bez tego następujące
szkielety nie dają się napisać przeciw kontraktowi* — z wskazaniem, które to szkielety.
Wymaganie bez wskazanego szkieletu byłoby życzeniem i takich tu nie ma.

**Czego świadomie nie wymagam:** niczego o wnętrzu (jak liczone są sloty, czy jest cache,
jakie indeksy). Testy etapu B mają mierzyć **kontrakt**, nie implementację —
inaczej zamienią się w testy-z-kodu i stracą powód, dla którego cały ten plan powstał.

---

## 1 · Operacje, których grupy potrzebują

Nazwy robocze z `PLAN-TESTOW-F2.md` §4 — **do zastąpienia nazwami z kontraktu**.
Kolumna „bez tego nie da się napisać" jest tu wymogiem, nie ilustracją.

| operacja | potrzebuje | bez tego nie da się napisać |
|---|---|---|
| `SLOTY` | specjalista · **usługa** · zakres dat · **rola/konsument** · zegar | całe `A`, `B`, `C`, `H`; **usługa w kluczu** rozstrzyga `A-01` vs `B-02` (ten sam zakres, 4 albo 2 sloty) |
| `RYTM.zapisz` | dzień tygodnia · zakres · kategoria | `A-01`, `B-05`, `G-05`, `fixtureH` |
| `POPRAWKA.zapisz` | data · godzina · typ (`wyłącz`/`dodaj`) · kategoria | `A-02`, `A-03`, `A-05`, `A-06`, `C-03`, `F-06`, `F-07` |
| `URLOP.zapisz` | zakres dat | `A-04`, `A-05`, `A-07`, `A-08` |
| `BLOKADA.zaloz` | termin · usługa · **ścieżka** (`własna`/`psycholog`/`koordynator`) · pacjent | cała `D`, `I-06`, `G-01` (po `P-08`) |
| `BLOKADA.potwierdz` | identyfikator blokady | `D-02` — **cały mechanizm dwustopniowy** |
| `LINK.otworz` | token | `D-04` |
| `REZERWACJA.utworz` | blokada + potwierdzenie płatności | `B`, `E`, `G`, `I`, `J` |
| `REZERWACJA.odwolaj` | rezerwacja · moment | `E`, `G`, `J-03`…`J-06` |
| `REZERWACJA.przeloz` | rezerwacja · nowy termin | `J-05`, `J-06` |
| `WNIOSEK.zloz` | termin · uzasadnienie | `J-07` |
| `LIMIT.pacjent` | pacjent | `F-01`…`F-05`, `F-10`, `J-02` |
| `LIMIT.specjalista` | specjalista · **tydzień ISO** | `F-06`…`F-10` |
| `LIMIT.podnies` | pacjent · delta · uzasadnienie | `F-05` |

---

## 2 · Wymagana semantyka — czternaście pozycji

### W-01 · Zegar jest WEJŚCIEM operacji, nie otoczeniem procesu
Każda operacja rozstrzygająca o czasie musi dać się wywołać z **jawnie podanym „teraz"**.
**Bez tego:** `C-01` (trzy wartości na granicy 2 h), cała `E`, `J-03`/`J-04`, `D-01`…`D-09`
— czyli **wszystkie granice** — nie dają się zmierzyć inaczej niż przez czekanie.
*Testy nie mają prawa czytać zegara maszyny (`K-6`).*

### W-02 · Slot niesie **start UTC** i **etykietę lokalną** jako dwa osobne pola
**Bez tego:** `H-01`…`H-05`, `A-09`. W dobie 25-godzinnej dwa różne sloty mają tę samą
etykietę `02:00`; porównanie po etykiecie **gubi jeden i pokazuje zgodność, której nie ma**.

### W-03 · Etykieta prezentacyjna **rozróżnia** powtórzoną godzinę zmiany czasu
Warunek jest częścią rozstrzygnięcia `Q-3`, nie dodatkiem.
**Bez tego:** `H-04` (`PERT-2`) — pacjent nie wie, na którą z dwóch „02:00" się umawia.

### W-04 · Odmowa niesie **PRZYCZYNĘ** w postaci nadającej się do asercji
Kod odmowy nie wystarczy: `422` jest spełnione przez „limit", „brak slotu", „poza
horyzontem" i „kolizja" jednocześnie.
**Bez tego:** `F-01`, `F-04`, `F-05`, `F-08`, `F-10`, `A-03`, `C-03`, `C-05`, `J-06` —
czyli **wszystkie testy odmowy** przechodziłyby z niewłaściwej przyczyny (znalezisko `P-14`).

### W-05 · Odmowa limitu podażowego **wskazuje konkretny termin**, który nie przeszedł
Wprost ze specyfikacji (M2/4). **Bez tego:** `F-06`.

### W-06 · `trzymane_do` jest **odczytywalne** i wyrażone w czasie absolutnym
**Bez tego:** `D-02`…`D-05`, `D-09`. Mierzenie blokady wyłącznie przez „czy slot zniknął
z listy" nie odróżnia **blokady wygasłej logicznie** od **usuniętej przez sprzątaczkę** —
a to dwa różne stany (`OBS` w `D-01`).

### W-07 · Kwota i pełny zrzut reguły zamrażają się w chwili **ZAŁOŻENIA BLOKADY**
Rozstrzygnięcie architekta (`ODPOWIEDZ-055` §2, znalezisko `P-08`). Na ścieżce własnej
momenty się zbiegają; **na ścieżce psychologa wiąże chwila wysłania linku** — bo to link
niesie kwotę, a zwrot ma się równać temu, co pacjent naprawdę zapłacił (`CLAUDE.md` §4).
**Bez tego:** `G-01`, `D-06` (skąd `kwota(zadanie) == 14500`, skoro pacjent `A` nigdy nie
doszedł do rezerwacji?).

### W-08 · Zamrożony zrzut jest **samowystarczalny**; niekompletny = **błąd**, nie dobieranie
Kontrakt zrzutu jest ścisły z premedytacją (`U-10`, `D-2026-08-07-18`); brak pola musi dać
**błąd nazywający pole**, nigdy cichego uzupełnienia z konfiguracji.
**Bez tego:** `G-03`, `G-04`.

### W-09 · Zrzut wyraża **obie ścieżki blokady jednym kształtem**
`blokada_koszyka_minut` jako pojedynczy skalar **nie umie** wyrazić dwóch wartości
(`D-2026-08-09-09`). Razem z tym: pole `waznoscLinkuPlatnosciDni` po `Q-19` opisuje
**godziny pod nazwą mówiącą „dni"** — zmiana niesie **nazwę i jednostkę razem z wartością**.
**Bez tego:** `G-04`. **Okno na tę zmianę zamyka się w dniu pierwszej rezerwacji.**

### W-10 · `kwota_zamrozona` i `kwota_zaplacona` to **dwa osobne pola**
Rozstrzygnięcie `Q-22` (wariant A): przy wizycie zwolnionej z opłaty `kwota_zamrozona`
= cena usługi z cennika dnia wizyty, `kwota_zaplacona` = 0.
**Bez tego:** `J-07`, `J-08` — a wizyty zwolnione z opłaty **znikają ze sprawozdania
z dotacji**, czyli z kategorii, którą fundacja finansuje.

### W-11 · Usługa niesie **dwie osie osobno**: rozliczeniową i dostępową
`fundacja/komercja` (które konto płatnicze, `CLAUDE.md` §3) **nie jest** tym samym co
„zużywa pulę 10 dofinansowanych konsultacji" (`Q-23`). Asystent zdrowienia jest
fundacyjny **i** nie zużywa puli.
**Bez tego:** grupa `F` i `J-08` — **bezpłatna usługa odbierałaby dostęp do dofinansowanej
terapii**. *To był mój własny błąd modelowania w fixture; wymaganie zapisuję, żeby nie
wrócił po drugiej stronie.*

### W-12 · Licznik limitu pacjenta jest **wyliczany z historii**, nie przechowywany jako stan
**Bez tego:** `F-01`…`F-04`, `J-02` — test ustawiający licznik wprost **mierzyłby sam
siebie** (kształt `C1`(c), stan własnej produkcji). Fixture podaje **historię wizyt**;
licznik ma się z niej wziąć.

### W-13 · Tożsamość pacjenta jest **jedna** dla trzech ścieżek umawiania
Strona · panel · psycholog. Licznik wisi na pacjencie, nie na tym, kto kliknął
(`D-2026-08-09-08`).
**Bez tego:** `F-04`, `J-02`. Stan zmierzony 09.08 (`D-2026-08-09-07`): *te same dane
gościa dwa razy dają **dwa rekordy***.

### W-14 · Wyścig i idempotencja to **dwa różne wejścia**, nie jedno
100 płatności **różnych** pacjentów o jeden slot ≠ 100 **duplikatów** jednego webhooka
(znalezisko `R-03`). Kontrakt musi pozwolić zbudować oba: pierwsze wymaga różnych
pacjentów, drugie — **powtórzonego identyfikatora zdarzenia**.
**Bez tego:** `I-06a`/`I-06b`; a `CLAUDE.md` §7 wymaga idempotencji **po ID zdarzenia**.

---

## 3 · Załącznik: 17 parametrów konfiguracji (kotwice)

Zasada z `ODPOWIEDZ-047` §2: **konfiguracja jest wejściem, wartość oczekiwana literałem,
kotwica jedynym miejscem porównania**. Kotwica powstaje **razem z pierwszym testem grupy,
która jej używa** — nigdy na zapas (plan spłaty, `ODPOWIEDZ-052` §3).

**Sześć już zdefiniowanych** (`SZKIELETY-F2.md` §2):

| kotwica | parametr | wartość | źródło |
|---|---|---|---|
| `KONF-BUFOR` | `bufor_min` | **10** | spec s. 25/35/50 |
| `KONF-DL-KONS` | `dlugosc_konsultacji_min` | **50** | spec s. 13 |
| `KONF-DL-ADHD` | `dlugosc_adhd_min` | **90** | spec s. 13 |
| `KONF-OKNO-24H` | `okno_bezplatnego_odwolania_s` | **86 400** | `D-2026-08-09-06` + `Q-4` |
| `KONF-STREFA` | `strefa_systemu` | **`Europe/Warsaw`** | spec s. 13/17 |
| `KONF-CENY` | cennik | **14500 · 5500 · 35000 · 0** gr | spec s. 13/36 |

**Jedenaście do spłaty w etapie B** — pełna lista, żeby „kotwice są" nie znaczyło
„kotwice są dla sześciu z siedemnastu":

| parametr | wartość | źródło rozstrzygnięcia | grupa |
|---|---|---|---|
| `min_wyprzedzenie_h` | **2** | spec s. 5/24/35/49 | `C` |
| `horyzont_pacjenta_dni` | **30** | spec s. 5/25/35/49 | `C` |
| `horyzont_wystawiania_dni` | **7** | spec s. 5/11/25/35/49 | `C` |
| `blokada_koszyka_min` | **10** | spec + `ZLECENIE-049` | `D` |
| `blokada_wstepna_min` | **10** | `Q-8` (`ODPOWIEDZ-045`) | `D` |
| `waznosc_linku_platnosci_h` | **48** | `Q-19` — **uwaga na nazwę i jednostkę, `W-09`** | `D` |
| `okno_po_otwarciu_linku_min` | **10** | `Q-9` + spec s. 11 | `D` |
| `margines_przed_wizyta_h` | **2** | `Q-10` | `D` |
| `limit_rownoczesnych_blokad` | **2** | `Q-12` | `D` |
| `limit_niskoplatnych_wizyt` | **10** | `D-2026-08-07-08`, `D-2026-08-09-05` | `F` |
| `limit_niskoplatnych_na_tydzien` | **4** | `D-2026-08-09-05` (**na SPECJALISTĘ**) | `F` |

---

## 4 · Jedno, o co proszę w trybie uzgadniania

Kontrakt dostanę do **kwestionowania**, nie do przepisania (`ODPOWIEDZ-047` §5) —
i z tego korzystam wyłącznie tak: **każde z wymagań `W-01`…`W-14` albo jest w kontrakcie
spełnione, albo dostaje jawny wpis „nie spełniamy, bo…" z warunkiem znoszącym.**

**Nieobecność nie niesie intencji** (`D-2026-08-09-12`, klasa `D6`) — wymaganie
przemilczane i wymaganie świadomie odrzucone wyglądają w kontrakcie identycznie, a przy
`W-04` czy `W-07` różnica kosztuje pieniądze pacjenta.
