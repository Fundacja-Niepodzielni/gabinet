# SZKIELETY WYKONAWCZE F2 — grupy A, B, E, G, I

**Kto:** sesja TESTY · **Kiedy:** 12.08.2026 · **Gałąź:** `testy-plan-f2`
**Podstawa:** [`PLAN-TESTOW-F2.md`](PLAN-TESTOW-F2.md) · `ODPOWIEDZ-045` §5 (S-2: nie stoję)
**Status:** nadal **etap A** — zero plików w `backend/`, zero w `tests/`.

---

## 0 · Czym to jest

Plan mówi **co** ma być zmierzone i **jaką liczbą**. Ten dokument mówi **w jakiej
kolejności i na jakim stanie**, tak żeby etap B był **przepisaniem**, nie projektowaniem.

**Zakres:** grupy **A** (10), **B** (5), **E** (4), **G** (5), **I** (6) = **30 szkieletów**
— te, których wartości nie zależą od pytań otwartych. `ODPOWIEDZ-045` §5 wymienia dodatkowo
`H-01`…`H-03` i `H-06`; **nie ma ich tutaj**, bo zlecenie do tej rundy enumeruje pięć grup.
Są gotowe do wzięcia i nic ich nie blokuje.

**Jeden punkt podstawienia w etapie B.** Operacje (`SLOTY`, `RYTM.zapisz`, …) to nazwy
kontraktowe z planu §4. Gdy KOD-SILNIK poda kontrakt API (`Q-21`, pierwsze zadanie F2),
podstawiamy je **w jednym miejscu** — w pomocnikach z §1, nie w trzydziestu testach.

**Notacja:**

| skrót | znaczenie |
|---|---|
| `ARRANGE` | stan przygotowany **jawnie**, nigdy dziedziczony po innym teście |
| `ACT` | jedna operacja badana |
| `ASSERT` | asercje **liczbowe**; `==` znaczy równość dokładną, nie „zawiera" |
| `NEG` | kontrola negatywna — musi dać **inną liczbę** |
| `ŚWIADEK` | asercja „miałem czego szukać" (K-4); obowiązkowa wszędzie, gdzie oczekujemy `0` |
| `PERT` | perturbacja: co złamać, żeby ten test zaświecił czerwono |
| `OBS` | ścieżka obserwacji — **inna niż mechanizm** (K-3) |
| `KOTWICE` | parametry konfiguracji, na których stoi ten przypadek (§2) |

---

## 1 · Wspólne przygotowanie

**Zegar jest wejściem, nie otoczeniem** (K-6). Żaden szkielet nie czyta zegara maszyny.

```
T0            := 2026-09-15 08:00:00 Europe/Warsaw      # wtorek; 06:00:00Z
STREFA_SYS    := Europe/Warsaw
```

**Budowniczy `fixtureS1()`** — jedno miejsce, w którym powstaje stan bazowy planu §3.2:

```
fixtureS1():
    S1 := specjalista(strefa: Europe/Warsaw)
    usluga(KONS_PELNA,  dlugosc: 50, cena_gr: 14500, kategoria: PELNOPLATNE)
    usluga(KONS_NISKA,  dlugosc: 50, cena_gr:  5500, kategoria: NISKOPLATNE)
    usluga(ADHD,        dlugosc: 90, cena_gr: 35000, kategoria: PELNOPLATNE, uprawnienie: nadane)
    usluga(ASYSTENT,    dlugosc: 50, cena_gr:     0, kategoria: NISKOPLATNE)
    RYTM.zapisz(S1, PELNOPLATNE, pon–pt, 09:00–13:00)
    RYTM.zapisz(S1, NISKOPLATNE, wt,     15:00–17:00)
    # bez urlopów, bez poprawek, bez rezerwacji
```

**Liczby pochodne — kontrola samego fixture'u.** Uruchamiana **przed** grupami A i B;
jej czerwień znaczy „zepsuty przyrząd", nie „zepsuty silnik":

```
SZK-FIX  ASSERT  SLOTY(S1, KONS_PELNA,  2026-09-22) == 4
                 SLOTY(S1, ADHD,        2026-09-22) == 2
                 SLOTY(S1, KONS_NISKA,  2026-09-22) == 2
                 SLOTY(S1, ASYSTENT,    2026-09-22) == 2
                 SLOTY(S1, KONS_PELNA,  2026-09-19) == 0      # sobota
                 suma(SLOTY(S1, KONS_PELNA, 2026-09-21 .. 09-25)) == 20
```

> **Ten sam zakres `09:00–13:00` daje 4 albo 2 sloty, zależnie od długości usługi.**
> Implementacja materializująca „sloty dnia" bez usługi w kluczu nie umie oddać obu liczb
> naraz — i `SZK-FIX` wywala się, zanim ktokolwiek zacznie szukać w grupie A.

---

## 2 · Kotwice konfiguracji

**Po co.** `ODPOWIEDZ-045` §1 mówi, że parametry idą do konfiguracji wersjonowanej
i **test ma czytać konfigurację**. Czytać — **na wejściu**. Gdyby test **wyliczał
z konfiguracji wartość oczekiwaną**, obie strony porównania jechałyby tą samą drogą
i zmiana parametru byłaby niewykrywalna (plan §8.3, kształt `C1`(b)).

**Podział ról, obowiązujący w każdym szkielecie:**

- wartość oczekiwana w `ASSERT` to **literał** (`== 4`), wyprowadzony ze specyfikacji;
- konfiguracja jest **wejściem** scenariusza, nigdy prawą stroną asercji;
- **kotwica** jest jedynym miejscem, gdzie parametr porównuje się z literałem — i jedynym,
  które nazywa **przyczynę** czerwieni, gdy parametr się rozjedzie.

**Bez kotwic** zmiana `bufor_min` na 20 daje kilkanaście czerwonych testów i **żadnej
informacji, co się stało**. Kotwica zamienia to w jedno zdanie.

| kotwica | parametr | wartość | źródło | używają |
|---|---|---|---|---|
| `KONF-BUFOR` | `bufor_min` | **10** | spec s. 25/35/50; `ZLECENIE-043` §4 | A, B, I |
| `KONF-DL-KONS` | `dlugosc_konsultacji_min` | **50** | spec s. 13 (55 zł / 50 min) | A, B, I |
| `KONF-DL-ADHD` | `dlugosc_adhd_min` | **90** | spec s. 13 (350 zł / 90 min) | A, B, I |
| `KONF-OKNO-24H` | `okno_bezplatnego_odwolania_s` | **86 400** | `D-2026-08-09-06` + `Q-4` (`ODPOWIEDZ-045`) | E, G |
| `KONF-STREFA` | `strefa_systemu` | **`Europe/Warsaw`** | spec s. 13/17 (`REGULY.strefa`) | wszystkie |
| `KONF-CENY` | cennik | **14500 · 5500 · 35000 · 0** gr | spec s. 13/36 | G |

```
SZK-KONF-*   ACT     wartosc := konfiguracja(<parametr>)
             ASSERT  wartosc == <literał z tabeli>
             NEG     podmiana parametru na inną wartość → TEN test czerwony,
                     i jest pierwszym czerwonym w raporcie (przyczyna, nie objaw)
             OBS     odczyt konfiguracji z bazy, nie przez operację, która jej używa
```

> **⚠ Kotwica `KONF-OKNO-24H` niesie zmianę JEDNOSTKI, nie tylko wartości.**
> Dzisiejsze pole nazywa się `waznoscLinkuPlatnosciDni: 2` (`D-2026-08-09-15`, zmierzone
> w kodzie 09.08). Po rozstrzygnięciu `Q-19` („2 dni = **48 h absolutnych**") nazwa z `Dni`
> **przestaje być prawdziwa** — dwie doby kalendarzowe i 48 h to dwie różne liczby dwa razy
> w roku (`F2-D-09`). To jest część zmiany kształtu zrzutu z `D-2026-08-09-09`, którą trzeba
> zrobić **zanim** powstanie pierwsza rezerwacja. **Zgłoszone w `ZLECENIE-047`.**

**Kotwice pozostałych parametrów** (`min_wyprzedzenie_h`, `horyzont_pacjenta_dni`,
`horyzont_wystawiania_dni`, `blokada_koszyka_min`, `blokada_wstepna_min`,
`okno_po_otwarciu_linku_min`, `margines_przed_wizyta_h`, `limit_rownoczesnych_blokad`,
`limit_niskoplatnych_wizyt`, `limit_niskoplatnych_na_tydzien`, `limit_przelozen`)
powstają **razem z grupami C, D, F, J** — nie dopisuję ich na zapas, bo kotwica bez
przypadku, który jej używa, jest deklaracją.

---

## 3 · Grupa A — trzy warstwy dostępności

#### SZK-A-01 → `F2-A-01` · Sam rytm rozwija się na raster 60 min
```
ARRANGE  zegar := T0 ; fixtureS1()
ACT      w := SLOTY(S1, KONS_PELNA, 2026-09-22)
ASSERT   count(w)          == 4
         starty_lokalne(w) == [09:00, 10:00, 11:00, 12:00]
         koniec_z_buforem(w[3]) == 13:00
NEG      count(SLOTY(S1, KONS_PELNA, 2026-09-19)) == 0        # sobota
ŚWIADEK  count(SLOTY(S1, KONS_PELNA, 2026-09-22)) > 0         # dla NEG (K-4)
PERT     raster := dlugosc bez bufora → starty == [09:00, 09:50, 10:40, 11:30]
OBS      starty z SLOTY; niezależnie: suma_zajetych_minut(2026-09-22) z bazy == 240
KOTWICE  KONF-BUFOR, KONF-DL-KONS
```

#### SZK-A-02 → `F2-A-02` · Poprawka wyłączająca jest jednorazowa
```
ARRANGE  zegar := T0 ; fixtureS1()
         n_rytmow_przed := count(rytmy(S1))                   # == 1
         POPRAWKA.zapisz(S1, wyłącz, 2026-09-22 11:00)
ACT      w22 := SLOTY(S1, KONS_PELNA, 2026-09-22)
         w29 := SLOTY(S1, KONS_PELNA, 2026-09-29)
ASSERT   count(w22)          == 3
         starty_lokalne(w22) == [09:00, 10:00, 12:00]
         count(w29)          == 4                             # rytm nietknięty
NEG      count(rytmy(S1)) == n_rytmow_przed == 1              # poprawka nie rozbiła rytmu
PERT     poprawka zapisywana przez modyfikację rytmu → count(w29) == 3
OBS      count(rytmy) osobnym zapytaniem do bazy, nie przez SLOTY
KOTWICE  KONF-BUFOR, KONF-DL-KONS
```

#### SZK-A-03 → `F2-A-03` · Poprawka dodająca godzinę spoza rytmu
```
ARRANGE  zegar := T0 ; fixtureS1()
         POPRAWKA.zapisz(S1, dodaj, 2026-09-16 18:00)         # środa
ACT      w16 := SLOTY(S1, KONS_PELNA, 2026-09-16)
         w23 := SLOTY(S1, KONS_PELNA, 2026-09-23)
ASSERT   count(w16) == 5 ; 18:00 ∈ starty_lokalne(w16)
         count(w23) == 4
NEG      wynik := POPRAWKA.zapisz(S1, dodaj, 2026-09-16 12:30)   # kolizja przez bufor
         wynik == odrzucone
         count(SLOTY(S1, KONS_PELNA, 2026-09-16)) == 5           # BEZ ZMIAN
         count(poprawki(S1))                      == 1           # BEZ ZMIAN
PERT     kontrola kolizji zdjęta → count(w16) == 6 i dwie wizyty w odstępie 30 min
OBS      SLOTY + count(poprawki) osobnym zapytaniem
KOTWICE  KONF-BUFOR, KONF-DL-KONS
```
> **Stan przypięty:** `NEG` biegnie na stanie **po** `ARRANGE` (poprawka 18:00 już jest).
> Prostuje to plan §5.A-03 — pierwsza wersja podawała `SLOTY = 4` **i** `poprawek = 1`,
> czego nie da się mieć naraz.

#### SZK-A-04 → `F2-A-04` · Urlop wygrywa z rytmem, we wszystkich usługach naraz
```
ARRANGE  zegar := T0 ; fixtureS1()
         bazowe := [ SLOTY(S1, u, 2026-09-22) dla u ∈ (KONS_PELNA, ADHD, KONS_NISKA, ASYSTENT) ]
         URLOP.zapisz(S1, 2026-09-14 .. 2026-09-18)
ACT      w := [ suma(SLOTY(S1, u, 2026-09-14 .. 2026-09-18)) dla tych samych 4 usług ]
ASSERT   w == [0, 0, 0, 0]
         suma(SLOTY(S1, KONS_PELNA, 2026-09-21 .. 2026-09-25)) == 20
ŚWIADEK  counts(bazowe) == [4, 2, 2, 2]                       # było czego szukać (K-4)
NEG      counts(bazowe) == [4, 2, 2, 2]                       # cztery liczby ≠ 0
PERT     urlop stosowany tylko do kategorii, w której go wpisano → jedna z czterech ≠ 0
OBS      cztery osobne zapytania, każde po innej usłudze
KOTWICE  KONF-DL-KONS, KONF-DL-ADHD, KONF-BUFOR
```
> `ŚWIADEK` i `NEG` to tu **ta sama miara** i jest to celowe: cztery zera nie znaczą nic,
> dopóki nie wiadomo, że przed urlopem stały tam cztery liczby dodatnie. *(Prostuje plan
> §5.A-04, gdzie stało `4, 4, 2, 2` — ADHD w `09:00–13:00` daje **2** sloty po 100 min.)*

#### SZK-A-05 → `F2-A-05` · Urlop wygrywa również z poprawką
```
ARRANGE  zegar := T0 ; fixtureS1()
         POPRAWKA.zapisz(S1, dodaj, 2026-09-16 18:00)
         URLOP.zapisz(S1, 2026-09-14 .. 2026-09-18)
ACT      w := SLOTY(S1, KONS_PELNA, 2026-09-16)
ASSERT   count(w) == 0
         count(poprawki(S1)) == 1        # urlop PRZYKRYWA poprawkę, nie kasuje jej
NEG      ten sam zestaw BEZ urlopu → count == 5
POZ-2    kolejność odwrotna (najpierw urlop, potem poprawka) → count == 0
PERT     złożenie warstw zamienione na sumę zbiorów → count == 1
OBS      SLOTY w obu kolejnościach + count(poprawki) z bazy
KOTWICE  KONF-BUFOR, KONF-DL-KONS
```
> `POZ-2` jest tu istotą przypadku: **o wyniku decyduje pierwszeństwo warstwy, nie
> kolejność zapisu.** Test wykonujący tylko jedną kolejność przechodzi także wtedy, gdy
> silnik po prostu bierze „ostatni zapis wygrywa".

#### SZK-A-06 → `F2-A-06` · Wyłączenie godziny, której w rytmie nie ma
```
ARRANGE  zegar := T0 ; fixtureS1()
ACT      wynik := POPRAWKA.zapisz(S1, wyłącz, 2026-09-22 20:00)
ASSERT   wynik ∈ (przyjęte, odrzucone)          # rozstrzygnięcie, NIE wyjątek 500
         count(SLOTY(S1, KONS_PELNA, 2026-09-22)) == 4
NEG      POPRAWKA.zapisz(S1, wyłącz, 2026-09-22 10:00)
         → count(SLOTY(S1, KONS_PELNA, 2026-09-22)) == 3
PERT     wyłączenie nieistniejącej godziny rzuca 500 → czerwony
OBS      kod odpowiedzi + SLOTY
KOTWICE  KONF-DL-KONS
```
> Bez `NEG` ten przypadek przechodzi także wtedy, gdy poprawki wyłączające **nie działają
> w ogóle** — bo „4 bez zmian" jest wtedy prawdą z niewłaściwego powodu.

#### SZK-A-07 → `F2-A-07` · Granice urlopu domknięte po obu stronach
```
ARRANGE  zegar := T0 ; fixtureS1()
         URLOP.zapisz(S1, 2026-09-15 .. 2026-09-17)
ACT      w := [ count(SLOTY(S1, KONS_PELNA, d)) dla d ∈ 09-14 .. 09-18 ]
ASSERT   w == [4, 0, 0, 0, 4]
NEG      urlop jednodniowy 2026-09-15 .. 2026-09-15 → w == [4, 0, 4, 4, 4]
PERT     granica '<' zamiast '<=' → w == [4, 0, 0, 4, 4]
OBS      pięć osobnych zapytań SLOTY
KOTWICE  KONF-DL-KONS
```

#### SZK-A-08 → `F2-A-08` · Urlop pokazuje, ile wizyt przełożyć — i niczego nie kasuje
```
ARRANGE  zegar := T0 ; fixtureS1()
         3 × REZERWACJA.utworz(S1, KONS_PELNA, {15.09 09:00, 16.09 09:00, 17.09 09:00})
         n_wizyt_przed := count(rezerwacje(S1))               # == 3
ACT      wynik := URLOP.zapisz(S1, 2026-09-15 .. 2026-09-17)
ASSERT   wynik.liczba_wizyt_do_przelozenia == 3
         count(rezerwacje(S1)) == n_wizyt_przed == 3          # zero skasowanych
         count(rezerwacje_odwolane(S1))      == 0
NEG      urlop 2026-10-05 .. 2026-10-07 (bez wizyt) → 0 i 0
PERT     urlop kasujący wizyty → count(rezerwacje) < 3
OBS      count(rezerwacje) osobnym zapytaniem do bazy, NIE z odpowiedzi URLOP.zapisz
KOTWICE  KONF-DL-KONS
```

#### SZK-A-09 → `F2-A-09` · Jedna funkcja slotów — trzy konsumenty, jedna odpowiedź
```
ARRANGE  zegar := T0 ; fixtureS111()                          # 111 specjalistów
ACT      p := SLOTY(konsument: PANEL,        S1, KONS_PELNA,  7 dni)
         s := SLOTY(konsument: WYSZUKIWARKA, S1, KONS_PELNA, 30 dni)
         g := SLOTY(konsument: GRAFIK,       S1, KONS_PELNA, 35 dni)
         wspolne := część wspólna zakresów dat (7 dni)
ASSERT   |starty_utc(p) Δ starty_utc(s)| == 0    (na `wspolne`)
         |starty_utc(s) Δ starty_utc(g)| == 0
         |starty_utc(p) Δ starty_utc(g)| == 0
         count(p) == count(s) == count(g)        (na `wspolne`)
ŚWIADEK  count(dni w `wspolne`) == 7 ; count(specjalistów w seedzie) == 111
NEG      POPRAWKA.zapisz(S1, wyłącz, <dzień z `wspolne`>)
         → różnica wobec stanu sprzed zapisu == 1 we WSZYSTKICH TRZECH ujęciach
PERT     grafik liczony osobną ścieżką → któraś różnica symetryczna > 0
OBS      trzy niezależne wywołania; porównanie po starcie UTC, nie po etykiecie lokalnej
KOTWICE  KONF-BUFOR, KONF-DL-KONS, KONF-STREFA
```
> Porównanie po **UTC**, nie po etykiecie: dwa różne sloty w dobie 25-godzinnej mają tę
> samą etykietę `02:00`, więc porównanie po etykietach zgubiłoby jeden i pokazało zgodność,
> której nie ma.

#### SZK-A-10 → `F2-A-10` · Unieważnianie cache: jeden specjalista, jeden dzień
```
ARRANGE  zegar := T0 ; fixtureS111() ; rozgrzej cache pełnym przebiegiem wyszukiwarki
         przed := migawka( count(SLOTY(spec, KONS_PELNA, d)) dla 111 spec × 30 dni )
ACT      POPRAWKA.zapisz(S1, wyłącz, 2026-09-22 11:00)
         po := ta sama migawka, w NASTĘPNYM żądaniu (bez czekania na TTL)
ASSERT   count(SLOTY(S1, KONS_PELNA, 2026-09-22)) == 3
         count(dni, w których po ≠ przed, u S1)      == 1
         count(specjalistów, u których po ≠ przed)   == 1
NEG      bez zapisu poprawki: obie liczby == 0
PERT     unieważnianie całego cache → count(specjalistów ze zmianą) == 111 → czerwony
OBS      migawka liczb dla WSZYSTKICH 111, nie tylko dla S1
KOTWICE  KONF-DL-KONS
```
> Perturbacja celuje w **nadmiarowe** unieważnienie. To jest defekt, nie „bezpieczny
> zapas": przy 111 specjalistach × 30 dni kasowanie całego cache po każdej poprawce
> zamienia `L-01` w test wydajności zimnego startu.

---

## 4 · Grupa B — bufor i dwie długości usług

#### SZK-B-01 → `F2-B-01` · Bufor wobec wizyty spoza rastra
```
ARRANGE  zegar := T0 ; fixtureS1()
         REZERWACJA.utworz(S1, KONS_PELNA, 2026-09-22 10:55)     # 10:55–11:45, spoza rastra
ACT      w := SLOTY(S1, KONS_PELNA, 2026-09-22)
ASSERT   count(w)          == 2
         starty_lokalne(w) == [09:00, 12:00]
         # 10:00 odpada: koniec 10:50, następna 10:55 → przerwa 5 min < 10
         # 11:00 odpada przez nakładkę; 12:00 wchodzi: 11:45 + 10 == 11:55 ≤ 12:00
NEG      ten sam stan przy bufor_min := 0 → count(w) == 3, starty == [09:00, 10:00, 12:00]
PERT     bufor stosowany tylko „w przód" (po wizycie) → count(w) == 3
OBS      SLOTY + niezależne przeliczenie z bazy: min(odstęp między sąsiednimi
         zajętościami dnia) == 10 min
KOTWICE  KONF-BUFOR, KONF-DL-KONS
```
> **2 kontra 3** to jedyna liczba odróżniająca bufor od zwykłej kontroli nakładek.
> Kontrola nakładek daje `3` i wygląda poprawnie na ekranie.

#### SZK-B-02 → `F2-B-02` · ADHD zdejmuje dwa sloty konsultacji i bufor
```
ARRANGE  zegar := T0 ; fixtureS1()
         REZERWACJA.utworz(S1, ADHD, 2026-09-22 09:00)           # 09:00–10:30 (+bufor → 10:40)
ACT      w := SLOTY(S1, KONS_PELNA, 2026-09-22)
ASSERT   count(w)          == 2
         starty_lokalne(w) == [11:00, 12:00]                     # 10:00 < 10:40 → odpada
NEG      bez rezerwacji ADHD → count(w) == 4
PERT     długość ADHD liczona jako 60 min → count(w) == 3
OBS      SLOTY + suma_zajetych_minut(2026-09-22) z bazy == 100
KOTWICE  KONF-BUFOR, KONF-DL-ADHD, KONF-DL-KONS
```

#### SZK-B-03 → `F2-B-03` · Kierunek odwrotny: konsultacja zamyka oba sloty ADHD
```
ARRANGE  zegar := T0 ; fixtureS1()
         REZERWACJA.utworz(S1, KONS_PELNA, 2026-09-22 10:00)     # 10:00–10:50 (+bufor 09:50–11:00)
ACT      w := SLOTY(S1, ADHD, 2026-09-22)
ASSERT   count(w) == 0
         # 09:00–10:30 nakłada się; 10:40 < 11:00 → odpada
ŚWIADEK  count(SLOTY(S1, ADHD, 2026-09-23)) == 2                 # ADHD w ogóle ma sloty
NEG      bez rezerwacji konsultacji → count(w) == 2
PERT     kolizja liczona tylko wewnątrz jednej usługi → count(w) ∈ (1, 2)
OBS      dwa zapytania SLOTY po różnych usługach
KOTWICE  KONF-BUFOR, KONF-DL-ADHD, KONF-DL-KONS
```

#### SZK-B-04 → `F2-B-04` · Niezmiennik: żadne dwa sloty bliżej niż 10 min
```
ARRANGE  zegar := T0 ; fixtureS1() ; rezerwacje z seeda
ACT      pary := wszystkie pary (zajętość, zajętość) tej samej osoby, 30 dni, 4 usługi
ASSERT   count(par o odstępie < 10 min) == 0
ŚWIADEK  count(par) > 0    # WYPISAĆ dokładną liczbę w raporcie, nie samo „> 0"
NEG      wstrzyknięcie wizyty 5 min po innej → count(naruszeń) == 1
PERT     bufor_min := 0 w konfiguracji → count(naruszeń) > 0
OBS      przeliczenie Z BAZY, nie z odpowiedzi API
KOTWICE  KONF-BUFOR
```
> `ŚWIADEK` wypisuje **liczbę sprawdzonych par**, bo „0 naruszeń z 0 par" i „0 naruszeń
> z 4200 par" to ten sam zielony i dwie różne prawdy.

#### SZK-B-05 → `F2-B-05` · Zakres niebędący wielokrotnością rastra (`Q-1`)
```
ARRANGE  zegar := T0 ; fixtureS1()
         RYTM.zapisz(S1, PELNOPLATNE, wt, 09:00–12:59)           # 239 min, nadpisuje bazowy
ACT      w := SLOTY(S1, KONS_PELNA, 2026-09-22)
ASSERT   count(w)          == 3
         starty_lokalne(w) == [09:00, 10:00, 11:00]
NEG      rytm 09:00–13:00 (240 min) → count == 4
         # jedna minuta zakresu zmienia liczbę slotów o jeden
PERT     koniec zakresu sprawdzany wobec `dlugosc` zamiast `dlugosc + bufor` → count == 4
OBS      SLOTY + niezależne przeliczenie floor(239 / 60) == 3
KOTWICE  KONF-BUFOR, KONF-DL-KONS
```

---

## 5 · Grupa E — okno 24 h

**Wspólne przygotowanie grupy** (`fixtureE()`): rezerwacja `R` na `2026-09-22 09:00`,
usługa `KONS_PELNA`, `kwota_zamrozona = 14500`, zrzut reguł z `okno = 86 400 s`.
Dzień `2026-09-22` ma bazowo **4** sloty; po utworzeniu `R` — **3**.

#### SZK-E-01 → `F2-E-01` · Granica 24 h: trzy wartości + termin wraca zawsze
```
ARRANGE  fixtureE()
ACT      dla t ∈ [2026-09-21 08:59:59, 09:00:00, 09:00:01]:
             zegar := t ; wynik[t] := REZERWACJA.odwolaj(R)
ASSERT   [ wynik[t].zwrot_gr ] == [14500, 14500, 0]
NEG      [ wynik[t].termin_wrocil_do_puli ] == [true, true, true]
         count(SLOTY(S1, KONS_PELNA, 2026-09-22)) po każdym odwołaniu == 4
PERT     '>=' → '>' w oknie → [14500, 0, 0]
         ALLOWLISTA: czerwień musi zawierać dosłowny fragment komunikatu asercji zwrotu
OBS      zwrot_gr z odpowiedzi + SLOTY osobnym zapytaniem
KOTWICE  KONF-OKNO-24H, KONF-CENY
```
> `NEG` nie jest tu „innym wejściem", tylko **inną osią**: termin wraca do puli po **obu**
> stronach granicy (spec, tabela odwołań). Test bez tej osi przechodzi także wtedy, gdy
> późne odwołanie blokuje termin na zawsze — a to jest strata godziny, nie tylko reguły.

#### SZK-E-02 → `F2-E-02` · Zegarowo, nie w dniach roboczych
```
ARRANGE  fixtureE() z wizytą R2 := poniedziałek 2026-09-21 09:00
ACT      zegar := 2026-09-19 12:00 (sobota) ; w := REZERWACJA.odwolaj(R2)
ASSERT   w.zwrot_gr == 14500                      # 45 h przed wizytą
POZ-2    zegar := 2026-09-20 08:00 (niedziela, 25 h) → 14500
NEG      zegar := 2026-09-20 10:00 (niedziela, 23 h) → 0
PERT     wprowadzenie kalendarza dni roboczych → sobota daje 0
OBS      zwrot_gr
KOTWICE  KONF-OKNO-24H, KONF-CENY
```
> Odwołanie **sobotnie** jest przypadkiem rozstrzygającym: przy odczycie „dni robocze"
> granica wypadłaby w piątek i sobota dałaby **0**. Granica wypada w niedzielę o 09:00.

#### SZK-E-03 → `F2-E-03` · Brak progów pośrednich — zbiór wartości ma 2 elementy
```
ARRANGE  fixtureE()
ACT      dla t ∈ [-168 h, -48 h, -24:00:01, -24:00:00, -23:59:59, -1 h] względem wizyty:
             wynik[t] := REZERWACJA.odwolaj(R).zwrot_gr        # na świeżej kopii R
ASSERT   wynik == [14500, 14500, 14500, 14500, 0, 0]
         |zbiór(wynik)| == 2
NEG      wprowadzenie progu 50% dla jednej sytuacji macierzy → |zbiór| == 3
PERT     zwrot_procent := 50 w jednym wierszu macierzy → |zbiór| == 3
OBS      sześć wywołań, każde na osobnej kopii rezerwacji (bez dziedziczenia stanu)
KOTWICE  KONF-OKNO-24H, KONF-CENY
```
> **Liczymy zbiór wartości, nie widoczność przycisku.** To jest wprost reguła
> `CLAUDE.md` §15 zastosowana do pieniędzy.

#### SZK-E-04 → `F2-E-04` · Okno liczy się od terminu wizyty, nie od daty zakupu
```
ARRANGE  RA := rezerwacja kupiona 2026-08-01, wizyta 2026-09-22 09:00
         RB := rezerwacja kupiona 2026-09-21 08:00, wizyta 2026-09-22 09:00 (bliźniacza)
ACT      zegar := 2026-09-21 08:59:59
         w := [ REZERWACJA.odwolaj(RA).zwrot_gr, REZERWACJA.odwolaj(RB).zwrot_gr ]
ASSERT   w == [14500, 14500]
NEG      ten sam zestaw przy TERMINIE wizyty przesuniętym na 07:00 → w == [0, 0]
PERT     okno liczone od `utworzono_at` → w == [0, 14500]
OBS      zwrot_gr ×2
KOTWICE  KONF-OKNO-24H, KONF-CENY
```

---

## 6 · Grupa G — zamrażanie

#### SZK-G-01 → `F2-G-01` · Kwota zamrożona w chwili zakupu
```
ARRANGE  cennik(KONS_PELNA) := 14500
         RA := REZERWACJA.utworz(S1, KONS_PELNA, 2026-09-22 09:00)
         cennik(KONS_PELNA) := 16500                            # podwyżka
         RB := REZERWACJA.utworz(S1, KONS_PELNA, 2026-09-22 10:00)
ACT      zegar := 2026-09-20 09:00                              # > 24 h, obie w oknie
         w := [ REZERWACJA.odwolaj(RA).zwrot_gr, REZERWACJA.odwolaj(RB).zwrot_gr ]
ASSERT   kwota_zamrozona(RA) == 14500 ; kwota_zamrozona(RB) == 16500
         w == [14500, 16500]
NEG      obie odwołane W TEJ SAMEJ SEKUNDZIE dają DWIE RÓŻNE liczby — to jest asercja,
         nie komentarz: w[0] ≠ w[1]
PERT     kwota czytana z cennika bieżącego → w == [16500, 16500]
         ALLOWLISTA (ścieżka pieniędzy)
OBS      kwota_zamrozona z bazy + zwrot_gr z operacji — dwa źródła
KOTWICE  KONF-CENY, KONF-OKNO-24H
```

#### SZK-G-02 → `F2-G-02` · Reguła anulacji zamrożona jako pełny zrzut
```
ARRANGE  konfiguracja(okno_bezplatnego_odwolania_s) := 86400
         RA := REZERWACJA.utworz(..., wizyta 2026-09-22 09:00)
         konfiguracja(okno_bezplatnego_odwolania_s) := 172800   # 48 h, nowa wersja
         RB := REZERWACJA.utworz(..., wizyta 2026-09-22 10:00)
ACT      zegar := taki, by do OBU wizyt zostało 30 h
         w := [ REZERWACJA.odwolaj(RA).zwrot_gr, REZERWACJA.odwolaj(RB).zwrot_gr ]
ASSERT   w == [14500, 0]
NEG      zegar dający 50 h do obu → w == [14500, 14500]         # poza oboma oknami
PERT     reguła czytana z konfiguracji bieżącej → w == [0, 0]
         ALLOWLISTA — rozszerzenie istniejącej perturbacji `zamrozenie`
OBS      zwrot_gr ×2 + odczyt `regula_anulacji_zamrozona` z bazy
KOTWICE  KONF-OKNO-24H, KONF-CENY
```
> Dwie rezerwacje, **jedna sekunda**, jedno wywołanie reguły, **dwie różne liczby**.
> Test wykonujący tylko jedną rezerwację nie odróżnia zamrożenia od bieżącej konfiguracji.

#### SZK-G-03 → `F2-G-03` · Niekompletny zrzut jest błędem, nie zgadywaniem
```
ARRANGE  R := rezerwacja ze zrzutem, z którego usunięto DOKŁADNIE JEDNO pole
ACT      wynik := REZERWACJA.odwolaj(R)
ASSERT   count(rozstrzygnięć) == 0
         wynik == błąd ; komunikat zawiera NAZWĘ brakującego pola
         zwrot_gr nie powstaje
NEG      pełny zrzut → count(rozstrzygnięć) == 1 ; zwrot_gr == 14500
POZ-2    zrzut z polem NADMIAROWYM (nieznanym) → count(rozstrzygnięć) == 1   # Q-15
PERT     brakujące pole dobierane z konfiguracji → count(rozstrzygnięć) == 1 zamiast błędu
         ALLOWLISTA: fragment komunikatu SKOPIOWANY dosłownie z kontroli, nie z pamięci
OBS      kod błędu + liczba wyliczonych zwrotów
KOTWICE  —  (przypadek bada kontrakt zrzutu, nie wartość parametru)
```
> **Wzorzec allowlisty przepisany z pamięci już raz nas kosztował** (`zamrożon` wobec
> komunikatu `ZAMROŻONĄ`, `WYTYCZNE-PRACY.md`). Fragment kopiujemy z kodu kontroli.

#### SZK-G-04 → `F2-G-04` · Zrzut wyraża obie ścieżki blokady jednym kształtem
```
ARRANGE  RW := rezerwacja ścieżką WŁASNA
         RP := rezerwacja ścieżką PSYCHOLOG
ACT      tw := trzymane_do(RW) rozstrzygnięte ZE ZRZUTU (bez dostępu do konfiguracji)
         tp := trzymane_do(RP) rozstrzygnięte ZE ZRZUTU
ASSERT   tw == start + 10 min
         tp == max(start + 48 h, otwarcie_linku + 10 min)
         count(pól zrzutu opisujących blokadę, RW) == count(… , RP)   # jeden kształt
NEG      zrzut w STARYM kształcie (skalar `blokada_koszyka_minut`)
         → count(rozstrzygnięć) == 0, komunikat NAZYWA pole
PERT     stary kształt przyjmowany po cichu → count(rozstrzygnięć) == 1
OBS      treść zrzutu z bazy + wynik rozstrzygnięcia
KOTWICE  —  (wartości pochodzą ze zrzutu, nie z konfiguracji: o to właśnie chodzi)
```
> **To jest przypadek, który wymusza kolejność prac.** `D-2026-08-09-09`: kształt zrzutu
> trzeba przestawić **zanim** powstanie pierwsza rezerwacja. Dziś rezerwacji jest zero,
> więc zmiana jest darmowa; po pierwszej — to albo zmiana wstecz (zakazana zasadą 4),
> albo dwa kształty zrzutu na zawsze.

#### SZK-G-05 → `F2-G-05` · Zamrożenie nie dotyczy dostępności
```
ARRANGE  zegar := T0 ; fixtureS1()
         R := REZERWACJA.utworz(S1, KONS_PELNA, 2026-09-22 10:00)
ACT      RYTM.zapisz(S1, PELNOPLATNE, pon–pt, 14:00–18:00)      # nadpisuje 09:00–13:00
ASSERT   count(rezerwacje(S1)) == 1
         termin(R) == 2026-09-22 10:00                          # BEZ ZMIAN
         count(SLOTY(S1, KONS_PELNA, 2026-09-29)) == 4
         starty_lokalne(...2026-09-29) == [14:00, 15:00, 16:00, 17:00]
NEG      SLOTY(S1, KONS_PELNA, 2026-09-22) == 4 z nowego zakresu,
         a wizyta 10:00 NADAL zajmuje 60 min → suma_zajetych_minut(22.09) == 300
PERT     zmiana rytmu przesuwa istniejące wizyty → termin(R) ≠ 10:00
OBS      termin wizyty z bazy + SLOTY
KOTWICE  KONF-BUFOR, KONF-DL-KONS
```

---

## 7 · Grupa I — współbieżność (obowiązkowa, `CLAUDE.md` §6)

**Środowisko jest częścią pomiaru.** Cała grupa biegnie na **PostgreSQL**
(`D-2026-08-07-02`), w izolowanym projekcie compose, na pliku środowiska
**zbudowanym z repozytorium**, nigdy na `.env` dewelopera.

#### SZK-I-01 → `F2-I-01` · 100 równoczesnych żądań = dokładnie 1 rezerwacja
```
ARRANGE  zegar := T0 ; fixtureS1() ; termin := (S1, 2026-09-22 10:00)
ACT      100 równoczesnych REZERWACJA.utworz(termin), różni pacjenci, bariera z SZK-I-02
ASSERT   count(rezerwacje WHERE specjalista=S1 AND termin=…) == 1     # Z BAZY
         count(odpowiedzi przyjęte)  == 1
         count(odpowiedzi konflikt)  == 99
         count(odpowiedzi razem)     == 100
NEG      pojedyncze żądanie → 1 wiersz, odpowiedź „przyjęte"
PERT-a   zdjęte ograniczenie unikalne (specjalista_id, termin) → count(wierszy) > 1
PERT-b   transakcja bez blokady wiersza → count(wierszy) > 1
         DWIE osobne perturbacje: to dwa mechanizmy i jeden NIE zastępuje drugiego
OBS      SELECT count(*) z bazy ORAZ osobno zliczone odpowiedzi.
         Rozjazd między nimi (1 wiersz, 7 odpowiedzi „przyjęte") jest SAM znaleziskiem.
KOTWICE  —
```

#### SZK-I-02 → `F2-I-02` · Kontrola przyrządu: czy żądania były równoczesne
```
ARRANGE  bariera startowa dla 100 procesów; znaczniki czasu zapisywane POZA aplikacją
ACT      te same 100 żądań co w SZK-I-01
ASSERT   count(procesów, które minęły barierę przed pierwszym zapisem) == 100
         rozrzut(momenty startu) < 50 ms
NEG      wymuszone wykonanie sekwencyjne → rozrzut > 1 s,
         a SZK-I-01 NADAL PRZECHODZI  ← to jest dowód, po co ten szkielet istnieje
PERT     bariera usunięta → count < 100
OBS      znaczniki czasu z każdego procesu, zapisane poza aplikacją (nie przez jej log)
KOTWICE  —
```
> **Próg 50 ms jest parametrem PRZYRZĄDU, nie regułą biznesową** — do kalibracji na
> maszynie CI przy pierwszym przebiegu. Podaję liczbę, żeby nie było „mniej więcej
> równocześnie"; jeśli CI okaże się wolniejsze, próg zmieniamy **świadomie i z zapisem**,
> nie po cichu.

#### SZK-I-03 → `F2-I-03` · 100 różnych terminów = 100 rezerwacji
```
ARRANGE  fixtureS111() ; 100 różnych terminów
ACT      100 równoczesnych REZERWACJA.utworz, każde na inny termin
ASSERT   count(rezerwacje) == 100 ; count(konfliktów) == 0
NEG      bez tego szkieletu SZK-I-01 przechodzi także przy implementacji,
         która NIE POZWALA ZAREZERWOWAĆ NICZEGO
PERT     blokada założona na całą tabelę zamiast na wiersz
         → count(konfliktów) > 0 albo czas rośnie liniowo z liczbą żądań
OBS      liczba wierszy + czas całkowity
KOTWICE  —
```

#### SZK-I-04 → `F2-I-04` · Kolizja przez bufor, nie przez klucz
```
ARRANGE  zegar := T0 ; fixtureS1()
ACT      równocześnie: REZERWACJA.utworz(S1, KONS_PELNA, 10:00)
                       REZERWACJA.utworz(S1, KONS_PELNA, 10:30)   # RÓŻNE terminy
ASSERT   count(rezerwacje) == 1 ; count(konfliktów) == 1
NEG      równocześnie 10:00 i 11:00 → count(rezerwacje) == 2 ; count(konfliktów) == 0
PERT     kontrola bufora poza transakcją → count(rezerwacje) == 2
OBS      liczba wierszy + przeliczenie odstępów z bazy
KOTWICE  KONF-BUFOR, KONF-DL-KONS
```
> Klucz unikalny `(specjalista_id, termin)` **tego nie łapie** — terminy są różne.
> Bufor musi mieć własny mechanizm współbieżny; `2 i 0` w przypadku kolidującym to
> dokładnie ten defekt.

#### SZK-I-05 → `F2-I-05` · Kolizja między usługami o różnej długości
```
ARRANGE  zegar := T0 ; fixtureS1()
ACT      równocześnie: REZERWACJA.utworz(S1, ADHD,       09:00)    # 09:00–10:30
                       REZERWACJA.utworz(S1, KONS_PELNA, 10:00)    # 10:00–10:50
ASSERT   count(rezerwacje) == 1 ; count(konfliktów) == 1
         suma_zajetych_minut(2026-09-22) ∈ (100, 60)   # NIGDY 160
NEG      równocześnie ADHD 09:00 i KONS_PELNA 12:00 → 2 rezerwacje, 0 konfliktów
PERT     długość usługi ignorowana przy blokadzie → count(rezerwacje) == 2
OBS      liczba wierszy + suma zajętych minut z bazy
KOTWICE  KONF-DL-ADHD, KONF-DL-KONS, KONF-BUFOR
```

#### SZK-I-06 → `F2-I-06` · Wyścig: wygaśnięcie blokady kontra płatność
```
ARRANGE  blokada B na (S1, 2026-09-22 10:00), trzymane_do := t_w
ACT      100 żądań „płatność zaksięgowana" docierających DOKŁADNIE w t_w
ASSERT   count(rezerwacje na termin) ∈ (0, 1)      # NIGDY 2
         count(rezerwacje) + count(zadania_zwrotu) == 100
NEG      te same 100 żądań w ŚRODKU okna → count(rezerwacje) == 1,
         a pozostałe 99 to KONFLIKTY, nie zwroty:
         count(zadania_zwrotu) == 0 ; count(konfliktów) == 99
PERT     brak idempotencji po identyfikatorze zdarzenia → suma ≠ 100
OBS      trzy zapytania do bazy: rezerwacje · zadania · zdarzenia
KOTWICE  —
```
> `NEG` rozróżnia **konflikt** od **zwrotu**. Bez tego „suma == 100" przechodzi także
> wtedy, gdy 99 pacjentów dostaje zadanie zwrotu za płatność, której nigdy nie wykonali.

---

## 8 · Czego w tym dokumencie nie ma

| co | dlaczego | warunek znoszący |
|---|---|---|
| `H-01`…`H-03`, `H-06` | `ODPOWIEDZ-045` §5 je wymienia, ale zlecenie tej rundy enumeruje pięć grup (A, B, E, G, I). **Nic ich nie blokuje** | następna runda — biorę je bez pytania (S-2) |
| grupy `C`, `D`, `F`, `J`, `K`, `L` | wartości zależą od `Q-16` (grupa J) albo od kontraktu API (`Q-21`); reszta czeka na kolejność z `ODPOWIEDZ-045` | kontrakt API od KOD-SILNIK |
| kotwice pozostałych 11 parametrów | kotwica bez przypadku, który jej używa, jest deklaracją | powstają razem z grupami C, D, F, J |
| kod w `tests/` | nadal **etap A**; `tests/` otwiera się po merge F1 | merge F1 do `main` |
| `fixtureS111()` w szczegółach | proporcje seeda (111 specjalistów, kilkanaście wizyt na pacjenta) należą do F1/F2 po stronie KOD-SILNIK; tu wołam go po nazwie | seed dostarczony razem z kontraktem |

---

## 9 · Co się zmienia w etapie B

1. **Podstawienie nazw kontraktowych** — w `§1`, nie w trzydziestu miejscach.
2. **Kolejność:** `SZK-KONF-*` → `SZK-FIX` → `SZK-I-01`/`SZK-I-02` → reszta.
   Kotwice i fixture pierwsze, bo ich czerwień unieważnia wszystko poniżej;
   współbieżność zaraz potem, bo jest jedynym przypadkiem wymaganym wprost przez
   `CLAUDE.md` i jedynym, którego brak przechodzi niezauważony do dnia wizyty.
3. **Kolejka dostaje prawdziwy sterownik.** `D-2026-08-08-27` §b: `QUEUE_CONNECTION=sync`
   przestaje wystarczać przy materializacji slotów — albo prawdziwy sterownik, albo jawny
   wpis na listę „bez pokrycia".
4. **Każdy szkielet ma `PERT` przed uznaniem za zrobiony** (`K-01`), a dla ścieżek
   pieniężnych (`E`, `G`) — **allowlistę z fragmentem skopiowanym z kontroli**, nie
   z pamięci.
