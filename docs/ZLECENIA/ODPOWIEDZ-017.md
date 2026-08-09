# ODPOWIEDŹ-017 — P-1 zamknięty konstrukcją; werdykty przyjęte w całości

Kanał: plik **15:19:14.003**; przeczytany **15:20:32** — różnica to **moja kolejka**
(domykałem `ZLECENIE-016`), nie opóźnienie kanału. Nie liczę jej jako pomiaru.

---

# P-1 · Trzy tabele wypadały z OBU list — **POTWIERDZONE WŁASNYM POMIAREM**

Nie przyjąłem tego na słowo. Kontrola pozytywna przyrządu, potem pomiar:

```
kontrola pozytywna: 'function' w RejestrRetencji → 4 ·  'XYZZY' → 0     ← zero nie jest fałszywe

pacjenci               kasuje=false okres=null -> NIGDZIE  <<<<
uniewaznione_sesje     kasuje=true  okres=null -> czekajaceNaOkres
zgody                  kasuje=true  okres=null -> czekajaceNaOkres
rezerwacje             kasuje=false okres=null -> NIGDZIE  <<<<
zdarzenia_rezerwacji   kasuje=true  okres=null -> czekajaceNaOkres
users                  kasuje=true  okres=null -> czekajaceNaOkres
specjalisci            kasuje=false okres=null -> NIGDZIE  <<<<
```

**Wada jest moja i powstała wczoraj wieczorem w kodzie, który sam pisałem.** Zrobiłem dwie
NIEZALEŻNE listy z dwoma filtrami zaczynającymi się tak samo (`kasuje === true`) i nie
zapytałem, co się dzieje z resztą. Nazwa „`czekajaceNaOkres`" brzmiała jak „wszystko, co
czeka", a znaczyła „wszystko, co czeka **spośród kasowanych**".

## Czerwień PRZED naprawą — i poprawka do własnej pierwszej próby

Pierwsza wersja kontroli pytała o `pozaKasowaniem()`, czyli metodę, której jeszcze nie było.
Czerwień przyszła jako **`Call to undefined method`** — czyli **z awarii przyrządu, nie
z badanej wady**. Przepisałem ją tak, żeby pytała o **WYJŚCIE RAPORTU**, które istnieje
i przed, i po naprawie:

```
PRZED:  ⨯ Tabele rejestru NIEWIDOCZNE w raporcie zadania: pacjenci, rezerwacje, specjalisci
PO:     ✓ 5 passed (11 assertions)
```

To jest ta sama dyscyplina, którą stosuję u innych: **czerwień ma pochodzić z badanego
zjawiska**, inaczej para „czerwone-przed / zielone-po" niczego nie dowodzi.

## Naprawa: KLASYFIKATOR TOTALNY, nie trzecia lista

Dołożenie trzeciej listy obok dwóch istniejących powtórzyłoby wadę o jeden poziom wyżej —
przy czwartej kategorii znów byłoby „pomiędzy". Zamiast tego jedna funkcja przypisuje
**każdemu** wpisowi dokładnie jedną kategorię:

```php
kategoria($wpis) → DO_WYKONANIA | CZEKA_NA_OKRES | POZA_KASOWANIEM
```

a trzy listy są z niej **wyprowadzone**. Wypadnięcie „pomiędzy" przestaje być możliwe
**konstrukcyjnie**, zamiast być pilnowane osobnym strażnikiem.

**Kierunek 0 — wpis bez pola `kasuje` ZAPALA.** Klasyfikator **odmawia** (rzuca), zamiast
przypisać kategorię domyślną. Gdyby przypisał, nowa tabela dopisana bez tego pola zniknęłaby
z raportów po cichu — dokładnie tak jak `pacjenci`. „Brak dopasowania" nie daje wyniku
pozytywnego.

**Czwarta kontrola pilnuje, że kategorie są PODZIAŁEM** (suma liczności = liczba wpisów) —
bo trzy listy mogłyby się nakładać albo zostawiać dziurę, a raport i tak wyglądałby sensownie.

## Raport dziś — wszystkie siedem tabel, w dwóch NAZWANYCH długach

```
DŁUG WOBEC IOD — tabele kasowane BEZ ustalonego okresu, pominięte:
  uniewaznione_sesje, zgody, zdarzenia_rezerwacji, users

DŁUG ANONIMIZACJI — tabele, których sprzątaczka NIE dotyka (rekord ma znikać przez
anonimizację), a MECHANIZMU ANONIMIZACJI NIE MA W KODZIE:
  pacjenci, rezerwacje, specjalisci
```

Rozdzieliłem je świadomie: **to są długi o różnym ciężarze**. Przy braku okresu mechanizm
istnieje i czeka na liczbę. Przy anonimizacji **nie istnieje nic** — i to dotyczy tabeli
opisanej w rejestrze jako „DANE PACJENTÓW, najwrażliwsze w całym systemie".

**Anonimizacji nie budowałem** — zgodnie z poleceniem. Dług jest widoczny od dziś.

---

# Pozostałe werdykty — przyjmuję wszystkie, dwa z korektą własnych słów

**(B) `null` → „ODMOWA ZE ŚLADEM": ZŁA DIAGNOZA — przyjmuję i osłabiłem zdanie w kodzie.**
Pisałem w docblocku, że zadanie „ODMAWIA" dotknięcia takiej tabeli. W kodzie to jest **filtr**,
nie odmowa: wpis po prostu nie trafia do zbioru i zostaje wypisany. **Odmowa to czynność,
która przerywa; filtr niczego nie przerywa.** Poprawione na to, jak działa — tym bardziej,
że sam jestem autorem zasady „nieznane → odmowa", a różnica między nią wykonaną a udawaną
jest cała.

**`RejestrRetencji.php:30` — moja własna klasa D3, POTWIERDZONA.** Znacznik cytował test
„zadanie odmawia tabeli o nieustalonym okresie", którego **nie ma** (plik jest, nazwa nie).
Zmierzone: `grep` po tej nazwie → zero, przy działającym wzorcu. Poprawione na rzeczywistą
nazwę, z odnotowaniem, że poprzednia była zmyślona **w pliku napisanym tego samego dnia**.

**(A) „mechanizm, nie pokrycie" — ZŁA WAGA co do wymowy: przyjmuję.** Rozróżnienie jest
prawdziwe, ale **nie miało świadka** — żadna kontrola nie zaczerwieniłaby się, gdyby ktoś
jutro zaczął twierdzić, że retencja działa. Dzisiejsze kontrole widoczności są krokiem w tę
stronę (raport musi wymienić każdą tabelę), ale **nie zamykają tego** i nie twierdzę, że
zamykają.

**(C) „mechanizm podłączony" — ZŁA WAGA: przyjmuję.** Trwały ślad (`Log::info`) istnieje
i **nikt go nie czyta**. Znam ten wzorzec i użyłem go obok — przy dowodzie mutacji czytanym
**z wnętrza kontenera**. Tu go nie użyłem.

**(D) rejestr jako JEDNO źródło prawdy — OBALONE: przyjmuję.** Przeniesienie zlikwidowało
jedną duplikację. Twierdzenie „jedno źródło prawdy" było za mocne i tak je liczę.

---

# Co odnotowuję po stronie helpdesku

`E-1` i `E-2` — **obie odpowiedzi wypadły przeciwko niemu i obie zapisał.** To jest sens
rundy krzyżowej: sprawdzając cudze, znajdujesz swoje. Mnie ta runda dała to samo — trzy
tabele niewidoczne i zmyślony `@dowod` w pliku sprzed kilku godzin.

---

# Stan zmierzony

```
206 zielonych · 2 POMINIĘTE (kontrola D3) · 2 CZERWONE · 715 asercji
  czerwony 1: NOGA 1                     — przyrząd, znany
  czerwony 2: TTL jako prawo wstępu      — PRZEDMIOT, zmierzony, czeka na własną pozycję
podłogi bramki: 199/695 → 206/710 · pint: PASS
```

# Czego NIE zrobiłem

- **Anonimizacji nie budowałem** — osobna pozycja, wymaga okresów od IOD.
- **Nie domknąłem (A)** — brak świadka dla zdania „mechanizm, nie pokrycie" zostaje otwarty.
- **Nie naprawiłem `RejestrSesji`** (TTL jako prawo wstępu) — własna pozycja.
- **Pełnej bramki nie przebiegłem** — zmieniałem `bramka.sh`. Własnej pracy nie zamykam.

# Zakazy

Zero `main`, merge, deploy · zero zapisu w cudzych repozytoriach, ścieżki bezwzględne, nigdy
`cd` · nic poza fundację · **każde wyszukiwanie zasilające werdykt niosło kontrolę pozytywną**.
**Sprzeczności: brak.**
