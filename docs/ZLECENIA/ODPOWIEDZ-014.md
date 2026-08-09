# ODPOWIEDŹ-014 — wariant A wprowadzony; czerwień znikła z tego samego powodu

Kanał: plik **14:38:19.048** → obudzony **14:38:46.063** = **27,0 s** (trzynasty pomiar).

## Najpierw rzecz, którą przegapiłem, a Ty nazwałeś

Odrzuciłeś wariant B mocniejszym argumentem, niż miałem: **normalizacja do napisów czyni `42`
i `"42"` nieodróżnialnymi**, czyli produkuje **wartość zdegenerowaną** w polu, którego jedynym
zadaniem jest wskazywać konkretny obiekt. W mojej analizie widziałem koszt wariantu B (zepsuty
test) — **nie widziałem, że należy do klasy, na którą polujemy od dwóch dni**. Zapisuję to
przeciw sobie, bo trzy godziny wcześniej sam tę klasę opisywałem kontom.

## Co zrobiłem

**Wariant A.** `Wynik::pozostale` ma adnotację `list<int|string>`; zbiór pól, ich znaczenie
i `kompletny()` **nietknięte**.

**Reprezentacja rozstrzygana RAZ, po typie KOLUMNY**, nie po wyglądzie wartości:

```php
$kluczCalkowity = $this->kluczJestCalkowity($tabela, $kolumnaKlucza);  // information_schema
$naKlucz = static fn (mixed $k): int|string => $kluczCalkowity ? Typy::liczba($k) : Typy::napis($k);
```

Gdybym rozstrzygał po wartości, **klucz tekstowy złożony z samych cyfr wyglądałby jak liczba**
i po cichu zmieniłby reprezentację — czyli popełniłbym dokładnie ten błąd, przed którym
wariant A ma chronić. To jest ta sama figura co „pytaj o własność, nie o nazwę".

**Klucz przestał być zakładany — pochodzi z rejestru** (`kolumna_klucza`), więc kontrola
pilnuje teraz czegoś mocniejszego niż wcześniej: że **zadeklarowany** klucz istnieje i ma
wartości.

## Czerwień znikła Z TEGO SAMEGO POWODU — sprawdzone perturbacją, nie założone

Prosiłeś wprost, żeby nie zniknęła przez obejście ścieżki. Wpisałem do rejestru **kłamstwo**
(`uniewaznione_sesje` deklaruje klucz `id`) i czerwień wróciła z komunikatem **identycznym**
co do znaku:

```
tabela `uniewaznione_sesje` NIE MA kolumny `id`, której wymaga ZadanieRetencji
(pluck/whereIn). Kolumny tej tabeli: sid_skrot, uniewazniona_at, wygasa_at, powod
```

Ścieżka jest ta sama; zmieniła się wyłącznie **prawdziwość deklaracji**.

## Dowód właściwy — zadanie DZIAŁA na kluczu tekstowym

Zielona kontrola klucza mówi tylko, że kolumna istnieje. Dołożyłem dwa testy, które mierzą
zachowanie:

- **kasuje i weryfikuje** na `uniewaznione_sesje` (rekord po terminie znika, świeży zostaje);
- **reprezentacja nie jest ujednolicana** — przy zablokowanym kasowaniu `pozostale` zawiera
  `sid` jako **napis** (`toBeString`), a dla `zgody` klucze pozostają `int`, więc test
  z linii 108 przechodzi bez zmiany. Sprawdziłem to przed propozycją i to się potwierdziło.

## Reguła dopisana

`docs/DECYZJE.md`: **D-2026-08-09-03** (typ identyfikatora należy do dziedziny) oraz
**D-2026-08-09-04** (obie strony jednego porównania w tym samym kodowaniu; identyfikatorów
z różnych dziedzin nie zestawia się nigdy) — z Twoim uzasadnieniem o pustej części wspólnej,
która wygląda jak brak wycieku.

## Błąd własny, który zapisuję, bo dotyczy przyrządu

Sprzątając perturbację użyłem `git checkout -- <plik>` — i **skasowałem tym własną,
niezacommitowaną pracę w tym samym pliku** (deklaracje `kolumna_klucza` dla siedmiu wpisów).
Objawiło się jako `Undefined array key "kolumna_klucza"`; zdiagnozowane pomiarem (`grep -c`
→ **0**), nie zgadywaniem. To ta sama rodzina co nocne `git add -A` w trakcie perturbacji.

**Wniosek operacyjny:** do cofania perturbacji `git checkout` wolno używać **wyłącznie** na
pliku, w którym nie mam niezacommitowanych zmian. Bezpieczny wzorzec, którego używam gdzie
indziej, to kopia przed i `cp` z powrotem — i on ma być domyślny.

## Stan zmierzony

```
199 zielonych · 2 POMINIĘTE (kontrola D3) · 1 CZERWONY (noga 1) · 700 asercji
  czerwień KLUCZA RETENCJI — ZNIKNĘŁA po naprawie
podłogi bramki: 196/688 → 199/695 · pint: PASS
```

**Pełnej bramki nie przebiegłem** — zmieniałem `bramka.sh`. Własnej pracy nie zamykam.
