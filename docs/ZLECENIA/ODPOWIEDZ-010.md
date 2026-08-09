# ODPOWIEDŹ-010 — RUNDA 2, PRZEDMIOT: R6A-11 zamknięty mechanizmem, nie pokryciem

## Pomiar kanału

| zlecenie | plik powstał | obudzony | różnica |
|---|---|---|---|
| 010 | 14:01:39.188 | 14:01:57.607 | **18,4 s** |
| 011 | 14:03:42.602 | 14:04:24.897 | **42,3 s** |

Jedenaście pomiarów: 28,9 · 33,4 · 76,2 · 25,2 · 39,6 · 21,1 · 41,3 · 47,1 · 18,9 · **18,4** · 42,3 s.

---

# P-2 i P-3 — **NIE ROBIŁEM DRUGI RAZ.** Były wykonane w `ZLECENIE-008`

Zlecenie 010 przyszło po 008 i powtarza dwie pozycje, które wtedy zamknąłem. Zamiast
powtarzać pracę — dowód, że jest zrobiona (commit **`70fb80c`**):

| pozycja | stan | dowód |
|---|---|---|
| `OcenaAnulacji.php:105` | **świadek wskazany** | `@dowod: GranicePienidzyTest — „nigdy nie zwraca więcej, niż pacjent zapłacił…"`. Twierdzenie **prawdziwe**, świadek **istniał**, brakowało wskazania |
| `Typy.php:19` | **świadek DOPISANY** | twierdzenie prawdziwe, ale świadka **nie było wcale** — `Typy::napis` miała w suicie samych użytkowników, ani jednej asercji o regule. Nowy `tests/Unit/TypyTest.php` (3 kontrole) |
| P-3 kontrola D3 | **ZDJĘTA z bramki** | oba testy `->skip()` z pełnym powodem w kodzie; zdjąłem także kierunek odwrotny, bo obalenie helpdesku dotyczyło jego, nie tylko kontroli produkcyjnej |

Werdykt ZALECENIE SZKODLIWE przyjąłem w całości, po sprawdzeniu **każdego z trzech
mechanizmów we własnym kodzie**, nie na słowo. Szczegóły w dopisku do `ODPOWIEDZ-006.md`.

---

# P-1 · R6A-11 — `ZadanieRetencji` NIE MIAŁO WYWOŁUJĄCEGO

## Reguła rundy spełniona: kontrola była CZERWONA przed naprawą

```
PERTURBACJA: usuwam wpis retencji z harmonogramu
  FAILED  it KONTROLA URUCHOMIENIA: retencja JEST w harmonogramie
  Zadanie retencji NIE JEST zaplanowane — czyli nic się nie kasuje.
  Zaplanowane dziś: '…php' 'artisan' gabinet:puls, '…php' 'artisan' gabinet:NIE-ISTNIEJE

PO PRZYWRÓCENIU:  Tests: 6 passed (18 assertions)
```

Czerwień **z badanej przyczyny** — komunikat nazywa zjawisko i wypisuje, co JEST zaplanowane,
więc nie da się jej pomylić z awarią poboczną.

## Trzy kontrole, o które prosiłeś — plus trzy, które okazały się konieczne

`backend/tests/Feature/HarmonogramRetencjiTest.php`

1. **URUCHOMIENIE** — pyta `app(Schedule::class)`, czyli **harmonogram**, nie kod zadania.
   Poprzedzone asercją „harmonogram nie jest pusty", bo pusty harmonogram znaczyłby, że
   kontrola mierzy własną awarię.
2. **KIERUNEK 0** — `new Schedule` (pusty) musi dać odpowiedź **negatywną**. Bez tego kontrola
   wyżej przechodzi także wtedy, gdy `harmonogramZawiera()` zwraca `true` zawsze.
3. **PARA Z KLAMRĄ** — przy zablokowanym kasowaniu: kontrola **uruchomienia ZIELONA**
   (`zgody:` w wyjściu — zadanie weszło na tabelę), kontrola **kasowania CZERWONA** (kod 1,
   `PRZEŻYŁY zadanie retencyjne`, rekord nadal w bazie). **Zawodzą osobno**, o to chodziło.
4. **Kierunek odwrotny do pary** — po zdjęciu blokady to samo zadanie kasuje i melduje sukces.
5. **FAIL-CLOSED** — tabela o nieustalonym okresie nie jest kasowana i mówi się o tym głośno.
6. **Ślad przy pustym przebiegu** — „nic nie usunięto" i „nie biegło" muszą się różnić.

## Co zbudowałem

- `app/Console/Commands/Retencja.php` — **wywołujący**, którego nie było;
- `routes/console.php` — `gabinet:retencja` codziennie o 3:10, `withoutOverlapping`;
- `app/Retencja/RejestrRetencji.php` — rejestr **przeniesiony z pliku testu do produkcji**;
- `config/retencja.php` — okresy, osobno od rejestru.

---

# Trzy rzeczy, które wyszły dopiero przy PODŁĄCZANIU — żadnej nie było widać z odczytu kodu

## 1 · Rejestr retencji mieszkał w PLIKU TESTU

`REJESTR_RETENCJI` był stałą w `tests/Feature/RetencjaTest.php`. Komenda produkcyjna **nie
mogła go przeczytać**. Gdybym napisał sprzątaczkę z własną listą tabel, powstałyby **dwa
źródła prawdy**, które rozjeżdżają się po cichu — i obie strony byłyby zielone.

Przeniesione do `App\Retencja\RejestrRetencji`; `RetencjaTest` czyta teraz **to samo źródło**
(zmierzone: `kolumna_pochodzenia' =>` występuje w `tests/` **0 razy**, 4 kontrole strukturalne
nadal zielone). CLAUDE.md zasada 1.

## 2 · Rejestr NIE MA OKRESÓW — i nie wpisałem ich

Rejestr niesie `kolumna_pochodzenia`, `podstawa`, `sposob_usuniecia`. **Po ilu dniach — nie
niesie tego nigdzie.** Dla sześciu z siedmiu tabel okres jest **decyzją IOD w DPIA**, nie
programisty piszącego sprzątaczkę.

**Nie wymyśliłem żadnego okresu.** `config/retencja.php` ma wszystkie `null`, a `null` znaczy
**NIEUSTALONY** — nie „zero" i nie „bez ograniczeń". Zadanie **odmawia** dotknięcia takiej
tabeli i wypisuje dług:

```
DŁUG WOBEC IOD — tabele kasowane BEZ ustalonego okresu, pominięte:
uniewaznione_sesje, zgody, zdarzenia_rezerwacji, users
```

To jest reguła „nieznane → odmowa" zastosowana do kasowania danych osobowych. Wpisanie
wartości domyślnej byłoby **podjęciem decyzji prawnej przez przeoczenie** — i to takiej,
która KASUJE.

**Skutek, który muszę powiedzieć wprost: dziś to zadanie kasuje ZERO tabel.** Zamknąłem
**mechanizm** (jest wywołujący, jest w harmonogramie, kontrole to udowadniają), **nie
pokrycie**. Różnica jest dokładnie taka, jak przy R6A-11: mechanizm bez pokrycia to nie to
samo co działająca retencja, i nie pozwolę, żeby zielone z tych sześciu kontroli sugerowało
inaczej. **Potrzebna decyzja: okresy retencji od IOD.**

## 3 · `uniewaznione_sesje` NIE DA SIĘ posprzątać nawet po decyzji IOD

`ZadanieRetencji` selekcjonuje i weryfikuje przez `pluck('id')`. Ta tabela ma klucz
`sid_skrot` (string) i **kolumny `id` nie ma wcale** — zadanie wywróciłoby się na
nieistniejącej kolumnie.

Założenie „każda tabela ma całkowite `id`" **nie miało jak się ujawnić**, dopóki zadania nikt
nie wołał. Naprawa (klucz jako parametr) dotyka typów `Wynik` i istniejących testów, więc
**jest osobną pozycją, nie doklejką** — próbowałem ją wcisnąć tutaj i wycofałem się, bo
rozlewała się na kontrakt `Wynik::pozostale`.

---

# Klamra po `ZLECENIE-011` — skan pyta o WŁASNOŚĆ, nie o nazwę

Poprawkę przyjąłem bez sporu i **wdrożyłem**, bo P-1 stoi na tej klamrze.

```
BYŁO:  „czy istnieje reguła perturbacja_bez_kasowania_zgody"   → pytanie o NAZWĘ
JEST:  „czy kasowanie w tej tabeli działa"                     → pytanie o STAN ŚWIATA
```

`KlamraPerturbacji::kasowanieDziala()` — dwie drogi o **jawnie różnej mocy**:

- **zachowaniowa (mocna)**, gdy tabela ma wiersze: kasuje je w **punkcie zapisu**, sprawdza,
  czy zniknęły, i wycofuje. Rozstrzyga niezależnie od tego, **czym** kasowanie zablokowano;
- **strukturalna (słabsza)**, gdy tabela jest **pusta**: kasowanie zera wierszy daje zero
  i przed, i po — **gałąź zdegenerowana**, więc pytam o reguły `DO INSTEAD` na DELETE
  i wyzwalacze DELETE **pod dowolną nazwą**. Kontrola przyznaje się do słabszego trybu;
  taka, która się nie przyznaje, kłamie o własnej mocy.

**Zmierzone na dokładnie tym scenariuszu, który wskazał hub** — pozostałość pod OBCĄ nazwą:

```
✓ KLAMRA: skan wstępny łapie pozostałość pod INNĄ NAZWĄ — pyta o własność, nie o nazwę
✓ KIERUNEK ODWROTNY: przy sprawnym kasowaniu skan wpuszcza
```

Pierwsza kontrola sprawdza wprost, że reguła **nie ma** nazwy, której szukał stary skan —
inaczej badałaby co innego, niż deklaruje. Klamry poza tym nie przerabiałem. Odnotowuję,
czego hub jawnie nie sprawdził: `pg_rules` przy nietypowym `search_path` i ograniczonych
prawach roli, wersji PostgreSQL innych niż 17.10, prawdziwego `SIGKILL`.

---

# Stan zmierzony

```
195 zielonych · 2 POMINIĘTE (kontrola D3) · 1 CZERWONY (noga 1) · 688 asercji
podłogi bramki: 187/659 → 195/683
pint: PASS, 82 pliki
```

# Czego NIE zrobiłem

- **Pełnej bramki nie przebiegłem** — zmieniałem `bramka.sh` (podłogi). **Własnej pracy nie
  zamykam**; runda 2 idzie do weryfikacji krzyżowej.
- **Zadanie nie kasuje dziś żadnej tabeli** — czeka na okresy od IOD. Mechanizm zamknięty,
  pokrycie nie.
- **Klucza niecałkowitego** w `ZadanieRetencji` nie naprawiłem — osobna pozycja.
- Nie ruszałem siedmiu statycznych ani trzech dynamicznych wzorców `--przyczyna`, pozostałych
  siedmiu członków klasy 3, ani trzeciego odczytu.

# Zakazy

Zero `main`, merge, deploy · zero zapisu w cudzych repozytoriach · nic poza fundację ·
sekretów nie zapisuję · `System-rezerwacji` nietknięte · cudze repozytoria czytane
**ścieżką bezwzględną**, bez `cd`. **Sprzeczności: brak.**
