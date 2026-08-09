# ODPOWIEDŹ-012 — klucz retencji: czerwień udowodniona, **naprawa WSTRZYMANA na Twoim warunku**

## Pomiar kanału

plik **14:25:54.871** → obudzony **14:26:35.459** = **40,6 s** (dwunasty pomiar).

**Punkt 3 potraktowałem jako warunek BLOKUJĄCY, nie jako sugestię.** Zrobiłem wszystko, co od
tej decyzji nie zależy; **naprawy nie wprowadziłem**. Odpowiedź na pytanie o `Wynik` jest niżej
i jest krótsza, niż się obawiałeś.

---

# 1 · Kontrola CZERWONA przed naprawą — zmierzona

`backend/tests/Feature/KluczRetencjiTest.php`. Komunikat nazywa **brakującą kolumnę**
i wypisuje, co ta tabela ma naprawdę — więc czerwieni nie da się pomylić z awarią zapytania:

```
Zadanie retencji NIE UMIE zadziałać na tabeli, którą rejestr każe kasować:
  tabela `uniewaznione_sesje` NIE MA kolumny `id`, której wymaga ZadanieRetencji
  (pluck/whereIn). Kolumny tej tabeli: sid_skrot, uniewazniona_at, wygasa_at, powod
```

Kontrola iteruje **rejestr**, nie listę wpisaną ręcznie — więc każda przyszła tabela
z `kasuje = true` przechodzi ten sam test bez niczyjego wpisu.

**Ta czerwień zostaje w suicie.** Nie oznaczyłem jej `skip`: defekt jest realny, a naprawa
czeka na Twoją decyzję — pominięta kontrola byłaby ukryciem stanu, nie jego opisem.
**Suita ma teraz DWA czerwone i oba są nazwane:** noga 1 (przyrząd) oraz ten klucz (przedmiot).

---

# 2 · Kierunek 0 — kontrola bada WARTOŚĆ, nie obecność w schemacie

Pytałeś wprost, czy kontrola bada wartość klucza, czy samą jego obecność. **Bada wartość** —
kolumna istniejąca, ale z wartościami `NULL`, jest uznawana za nieużyteczną:

```
kolumna `id` w tabeli `X` ISTNIEJE, ale ma N wartości NULL — selekcja po takim
kluczu nie zapamięta rekordu, a weryfikacja go nie znajdzie
```

Powód jest praktyczny, nie estetyczny: `pluck` po kluczu `null` nie zapamiętuje rekordu,
a `whereIn` z `null` nie trafia w żaden wiersz — zadanie **zameldowałoby sukces, nie kasując
nic**. To jest „kształt zachowany, wartość jałowa" wewnątrz samego mechanizmu retencji.

## Kierunek 0 złapał błąd w MOJEJ próbie, i to jest jego najlepsza rekomendacja

Pierwsza wersja probówki używała `create temporary table`. Tabele tymczasowe żyją w `pg_temp`,
więc wyszukiwanie po `table_schema = current_schema()` **ich nie widzi** — kontrola meldowała
wtedy „nie ma kolumny `id`" zamiast „kolumna ma wartości NULL". Czerwień **z niewłaściwej
przyczyny**, wyglądająca na udaną próbę. Złapał to wyłącznie kierunek odwrotny, bo asercja
sprawdzała **treść powodu**, a nie sam fakt czerwieni. Naprawione tabelą zwykłą (wycofuje ją
transakcja `RefreshDatabase`).

---

# 3 · ⛔ KONTRAKT `Wynik` — ZATRZYMUJĘ SIĘ I MÓWIĘ. Pytanie do rozstrzygnięcia

**Odpowiedź krótka: naprawa NIE wymusza zmiany KSZTAŁTU `Wynik`. Wymusza rozszerzenie
JEDNEJ adnotacji typu.** Zmierzone, nie oszacowane — policzyłem wszystkich odbiorców.

## Kto naprawdę czyta `pozostale`

| miejsce | jak czyta | czy zależy od typu elementu |
|---|---|---|
| `Wynik::kompletny()` | `$this->pozostale === []` | **nie** |
| `Retencja.php:78` | `count($wynik->pozostale)` | **nie** |
| `RetencjaWykonanieTest:75` | `->toBe([])` | **nie** |
| `RetencjaWykonanieTest:108` | `->toBe([$stara])`, gdzie `$stara` to `int` | **TAK — tożsamość ścisła** |

**Kod produkcyjny jest całkowicie obojętny na typ elementu** — czyta wyłącznie pustość
i liczność. Jedynym miejscem zależnym od typu jest **jeden test**, i to przez `toBe`, czyli
porównanie ścisłe.

## Co to znaczy dla kontraktu

Zbiór pól (`wybrane`, `usuniete`, `pozostale`), ich znaczenie i powierzchnia rozstrzygnięcia
(`kompletny()`) **zostają bez zmian**. Zmienia się dokładnie jedno:

```php
- * @param  list<int>  $pozostale
+ * @param  list<int|string>  $pozostale
```

Jeśli implementacja **zachowa natywny typ z bazy** (int dla tabel z `id`, string dla
`sid_skrot`), to test z linii 108 **nadal przechodzi** — bo dla `zgody` klucze pozostaną `int`.
Sprawdziłem to celowo, zanim cokolwiek zaproponowałem.

## Trzy warianty — i moja rekomendacja

| | wariant | koszt | co robi z kontraktem |
|---|---|---|---|
| **A** | rozszerzyć adnotację do `list<int|string>`, zachować typ natywny | najmniejszy; produkcja nietknięta, istniejące testy przechodzą | **kształt bez zmian**, rozszerzony typ jednego pola |
| B | znormalizować wszystko do `list<string>` | średni; psuje test z linii 108, trzeba go przepisać | kształt bez zmian, ale **traci rozróżnienie** int/string |
| C | wyjąć identyfikatory z `Wynik`, zostawić same liczby | największy | **TO jest zmiana kształtu** — zmienia powierzchnię rozstrzygnięcia |

**Rekomenduję A** i **nie wprowadzam go bez Twojego słowa**, bo to Ty widzisz kratkę dwóch osi
z kontraktu SSO kont, a ja jej nie widziałem. Jeśli tamten kształt zakłada, że identyfikatory
w rozstrzygnięciu są jednorodne, wariant A go rozjeżdża i wtedy właściwy jest **C** —
z osobnym akcesorem na identyfikatory, poza wspólnym kształtem rozstrzygnięcia.

**Pytanie, na które czekam, brzmi dokładnie tak:** czy rozszerzenie typu elementu listy
`pozostale` z `int` do `int|string` liczy się w Waszym ekosystemie jako zmiana kształtu
rozstrzygnięcia — czy jako rozszerzenie pola wewnątrz niezmienionego kształtu?

---

# 4 · Klamra — bez zmian, w wersji po `ZLECENIE-011`

Skan pyta o **własność** („czy kasowanie w tej tabeli działa"), nie o nazwę artefaktu.
Wdrożony i zmierzony w poprzedniej rundzie, wraz z kontrolą na scenariuszu hubu (pozostałość
pod obcą nazwą). Nic tu nie ruszałem.

---

# Stan zmierzony

```
196 zielonych · 2 POMINIĘTE (kontrola D3) · 2 CZERWONE · 693 asercje
  czerwony 1: NOGA 1            — przyrząd, znany
  czerwony 2: klucz retencji    — PRZEDMIOT, defekt realny, naprawa wstrzymana na pkt 3
podłogi bramki: 195/683 → 196/688
pint: PASS, 83 pliki
```

# Czego NIE zrobiłem

- **Naprawy klucza nie wprowadziłem** — czeka na rozstrzygnięcie o `Wynik`. To jest jedyny
  powód, dla którego ta pozycja nie jest zamknięta.
- **Pełnej bramki nie przebiegłem** — zmieniałem `bramka.sh` (podłogi). Własnej pracy nie
  zamykam.
- Okresów retencji nie wpisywałem · siedmiu statycznych ani trzech dynamicznych wzorców
  `--przyczyna` nie ruszałem · `main` nietknięty.

# Zakazy

Zero `main`, merge, deploy · zero zapisu w cudzych repozytoriach, ścieżki bezwzględne,
nigdy `cd` · nic poza fundację · sekretów nie zapisuję. **Sprzeczności: brak.**
