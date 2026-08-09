# ZLECENIE-012 — kolejna pozycja przedmiotu (gabinet)

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-012`, odpowiedz `ODPOWIEDZ-012.md`

---

## Odbiór `ODPOWIEDZ-010` — przyjmuję w całości, z trzema rzeczami do odnotowania

**1. „Zamknąłem MECHANIZM, nie POKRYCIE" jest właściwym zdaniem i tak to zapisuję.**
Nie pozwoliłeś, żeby sześć zielonych kontroli sugerowało działającą retencję, choć zadanie kasuje
dziś zero tabel. To jest dokładnie ta różnica, której brak stworzył `R6A-11`.

**2. Odmowa wpisania okresów była decyzją, nie unikiem.** *„Wpisanie wartości domyślnej byłoby
podjęciem decyzji prawnej przez przeoczenie — i to takiej, która KASUJE"* — zdanie idzie do
`DECYZJE-PRZEKROJOWE.md` jako uzasadnienie reguły „nieznane → odmowa" w kasowaniu danych
osobowych. **Wpisałem to właścicielowi jako pozycję B7**, powiązaną z progami retencji helpdesku,
bo to jedna rozmowa z fundacją, nie dwie. Dodałem pytanie, czy IOD i DPIA w ogóle istnieją —
jeśli nie, zaczynamy od tego, a nie od tabeli.

**3. Rejestr w pliku testu — najlepsze znalezisko tej rundy** i takie, którego **nie było widać
z odczytu kodu**, tylko z podłączania. Gdybyś dał sprzątaczce własną listę tabel, powstałyby
dwa źródła prawdy rozjeżdżające się po cichu, **obie strony zielone**. To jest klasa **D5**
złapana zanim powstała.

---

## POZYCJA · `uniewaznione_sesje` — klucz jako parametr

Sam ją wydzieliłeś i sam wycofałeś się z wciskania jej do poprzedniej rundy, bo **rozlewała się
na kontrakt `Wynik::pozostale`**. To była właściwa decyzja i dlatego dostajesz ją osobno.

**Stan zmierzony przez Ciebie:** `ZadanieRetencji` selekcjonuje i weryfikuje przez `pluck('id')`,
a `uniewaznione_sesje` ma klucz `sid_skrot` (string) i **kolumny `id` nie ma wcale** — zadanie
wywróciłoby się na nieistniejącej kolumnie.

**Rzecz warta powiedzenia głośno:** założenie „każda tabela ma całkowite `id`" **nie miało jak
się ujawnić, dopóki zadania nikt nie wołał**. To jest drugi, niezależny skutek tego samego braku
wywołującego — i argument, że `R6A-11` był cięższy, niż wyglądał.

**Wymagania:**
1. **Kontrola CZERWONA przed naprawą** — na `uniewaznione_sesje`, z komunikatem wskazującym
   **brakującą kolumnę**, nie ogólną awarię zapytania. Czerwień z badanej przyczyny, sprawdzona
   obydwoma odczytami.
2. **Kierunek 0:** tabela z kluczem, ale o wartościach pustych/`null` — czy kontrola bada
   **wartość klucza**, czy samą jego obecność w schemacie.
3. **Kontrakt `Wynik`**: skoro klucz przestaje być całkowity, `Wynik::pozostale` i pola niosące
   identyfikatory muszą to znieść. **Jeśli to wymusza zmianę kształtu `Wynik` — zatrzymaj się
   i powiedz, zanim ją wprowadzisz**: `Wynik` jest bliski krataki dwóch osi, którą konta wnoszą
   do kontraktu SSO, i nie chcę dwóch rozjeżdżających się kształtów rozstrzygnięcia w jednym
   ekosystemie.
4. **Klamra obowiązuje** w wersji po `ZLECENIE-011` — skan pyta o **własność**, nie o nazwę.

**Czego NIE robisz:** nie wpisujesz okresów retencji (czeka na właściciela) · nie naprawiasz
siedmiu statycznych ani trzech dynamicznych wzorców `--przyczyna` · nie ruszasz `main` ·
**nie zamykasz własnej pracy** — runda 2 idzie do weryfikacji krzyżowej, gdy pozostałe sesje oddadzą.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · **zero zapisu w cudzych repozytoriach, ścieżki bezwzględne,
nigdy `cd`** · nic poza fundację · sekretów nie zapisujesz · **S-1 obowiązuje.**
