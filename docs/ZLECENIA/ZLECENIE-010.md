# ZLECENIE-010 — RUNDA 2: PRZEDMIOT (gabinet)

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-010`, odpowiedz `ODPOWIEDZ-010.md`

---

## Sprostowanie rozesłane — dziękuję, że zgłosiłeś własny błąd pomiarowy

Poszło do kont w `ZLECENIE-009` §1, ich słowami i Twoimi: **kategoria „rozróżnialność zależna
od szerokości wyjścia" nie ma ani jednego potwierdzonego przypadku**, a bronić się mają przed
kategorią **zmierzoną trzykrotnie** — wzorzec równy **NAZWIE KLASY**, którą Pest drukuje
w każdym przebiegu (`BrakWlasnychHaselTest`), niewidoczny dla odczytu statycznego.

Przekazałem też, że dług jest **3 z 13, nie 6 z 13**, i że wszystkie trzy rozróżniające to
**komunikaty asercji** — co potwierdza regułę kont mocniej niż pojedynczy przykład.

**Twoja decyzja o sufit zapadki jest słuszna i nie zmieniam jej:** sufit stoi na 7, bo mierzy
odczyt statyczny i w nim jest poprawny; trzy nowe pozycje to dług **innej natury**, którego
statycznie złapać się nie da, więc pilnuje go raport, nie zapadka. Mieszanie dwóch długów pod
jednym licznikiem zrobiłoby z zapadki przyrząd, który kłamie o własnej wartości.

---

# RUNDA 2 — PRZEDMIOT. Zaczynamy.

**Warunek wejścia spełniony:** P2 zweryfikowany przez hub, klamra w kodzie, perturbacja klamry
zmierzona w obie strony, przyrząd zmierzony **dwoma** odczytami. **Od teraz naprawiamy to,
co jest zepsute w SYSTEMIE, nie w narzędziu.**

## Reguła rundy 2 — przy każdej pozycji

> **Naprawa bez kontroli, która była CZERWONA przed nią i jest ZIELONA po niej, nie liczy się
> jako wykonana.** Czerwień z badanej przyczyny, sprawdzona **obydwoma** odczytami.
> Rozbieżność między nimi zgłoś jako znalezisko, nie jako szum.

**Kolejność: iloczyn WAGI i OSIĄGALNOŚCI.**

## P-1 · `R6A-11` — `ZadanieRetencji` NIE MA ANI JEDNEGO WYWOŁUJĄCEGO

**To jest pozycja o najwyższym iloczynie w Twoim repozytorium i Ty ją zmierzyłeś.**

**Waga:** zasada twarda 10 mówi „retencje jako zadania czyszczące w kodzie", a `D-EKO-006`
i RODO art. 17 stoją na tym, że dane znikają po terminie. **Zadanie, którego nikt nie woła,
nie kasuje niczego** — a wszystkie kontrole retencji świecą zielono, bo badają **kasowanie**,
nie **uruchamianie**. Sam zapisałeś to w docblocku klamry: *„wzorzec dowodzi KASOWANIA,
nie URUCHAMIANIA"*.

**Osiągalność: maksymalna, bo to nie jest ryzyko — to stan bieżący.** Dziś nic się nie kasuje.

**Trzy kontrole wymagane:**
1. **zadanie jest zaplanowane i chodzi** — asercja na harmonogramie, nie na kodzie zadania;
2. **kierunek 0:** harmonogram z pustą listą zadań → kontrola musi **zaczerwienić**,
   nie przejść („zero zaplanowanych" to nie to samo co „nic nie wymaga planowania");
3. **para z klamrą:** przy zablokowanym kasowaniu (Twoja reguła `DO INSTEAD NOTHING`) kontrola
   uruchomienia ma nadal być **zielona**, a kontrola kasowania **czerwona** — bo to są dwa
   różne twierdzenia i mają zawodzić osobno.

**⛔ Klamra obowiązuje bezwzględnie**: skan wstępny przed startem, twarda **odmowa** przy
pozostałości, zdjęcie na ścieżce biegnącej przy awarii. Reguła zostawiona na żywej instancji
to cicha blokada kasowania danych osobowych.

## P-2 · Dwa niepodparte twierdzenia w kodzie liczącym pieniądze — **dwie minuty**

Znalezione przez helpdesk Twoim własnym silnikiem, po dodaniu jednej frazy, z otwartym
kontekstem każdego trafienia:

| miejsce | twierdzenie |
|---|---|
| `OcenaAnulacji.php:105` | „zwrot **NIGDY nie** przekroczy tego, co pacjent naprawdę zapłacił" |
| `Typy.php:19` | „Tablica, obiekt i `null` **NIGDY nie** stają się napisem po cichu" |

**Oba orzekają o zachowaniu kodu i żadne nie wskazuje świadka.** Pierwsze dotyczy pieniędzy
pacjentów — jeśli jest prawdziwe, ma mieć test; jeśli nie, ma zostać osłabione. **Trzeciej
drogi nie ma.** Tę samą drogę zastosowałeś już przy siedmiu twierdzeniach obalonych przez
weryfikatora i wtedy zadziałała.

## P-3 · Kontrola D3 — decyzja o bramce, nie przebudowa

Helpdesk wystawił **ZALECENIE SZKODLIWE** dla wiązania `@dowod:` na poziomie **bloku**:
jeden świadek certyfikuje cały docblock, pusty znacznik rozbraja blok, a **sama wzmianka
o `@dowod:` w prozie też go rozbraja** — czyli udokumentowanie konwencji zwalnia z konwencji.

**Decyzja, której oczekuję w tej rundzie: czy `TwierdzeniaKomentarzyTest` zostaje w bramce
w obecnym kształcie.** Moja rekomendacja: **nie**, dopóki blok certyfikuje sam siebie.
Przeprojektowanie (wiązanie wymogu świadka z **rolą tekstu**, nie ze słowami — wzorzec
helpdesku: każdy wpis `W-nn` musi mieć linię `Dowód:`) to osobna pozycja, **nie ta runda**.

## Czego w tej rundzie NIE ROBIMY

Nie naprawiamy siedmiu statycznych ani trzech dynamicznych wzorców `--przyczyna` — zapadka
i raport pilnują, żeby dług nie urósł · nie domykamy pozostałych siedmiu członków klasy 3 ·
nie ruszamy `main` · **nie zamykasz własnej pracy** — runda 2 też idzie do weryfikacji krzyżowej.

## Trzeci odczyt, którego nikt nie ma — odnotowuję jako otwarte dla całego ekosystemu

Twoje zdanie: *odczyt dynamiczny biegnie na przebiegu zielonym, więc mówi tylko „wzorzec jest
obecny, gdy nic nie jest zepsute"; czy przy prawdziwej czerwieni pojawi się z badanego powodu —
na to trzeba przebiegu zmutowanego.* **Przyjmuję i zapisuję jako brak wspólny**, nie Twój.
Nie zaczynaj go w tej rundzie.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · zero zapisu w cudzych repozytoriach · nic poza fundację ·
sekretów nie zapisujesz · starego repo `System-rezerwacji` nie dotykasz ·
**S-1: do 10 subagentów, równoległość tylko na rozłącznym stanie, zero przy Dockerze i bazie.**
