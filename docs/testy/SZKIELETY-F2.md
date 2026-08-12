# SZKIELETY WYKONAWCZE F2 — grupy A, B, E, G, I

**Kto:** sesja TESTY · **Kiedy:** 12.08.2026 · **Gałąź:** `testy-plan-f2`
**Podstawa:** [`PLAN-TESTOW-F2.md`](PLAN-TESTOW-F2.md) · `ODPOWIEDZ-045` §5 (S-2: nie stoję)
**Status:** nadal **etap A** — zero plików w `backend/`, zero w `tests/`.

---

## 0 · Czym to jest

Plan mówi **co** ma być zmierzone i **jaką liczbą**. Ten dokument mówi **w jakiej
kolejności i na jakim stanie**, tak żeby etap B był **przepisaniem**, nie projektowaniem.

**Zakres: 68 szkieletów.**

| runda | grupy | ile |
|---|---|---|
| pierwsza (`ZLECENIE-047`) | **A** 10 · **B** 5 · **E** 4 · **G** 5 · **I** 6 | **30** |
| druga (`ZLECENIE-052`) | **H** 7 · **C** 5 · **D** 9 · **F** 10 · `SZK-J-02` | **32** |
| trzecia (`ODPOWIEDZ-052` §5) | **J** — `J-03`…`J-08` | **6** |

**Bilans wobec planu:** 75 przypadków − 68 szkieletów = **7**, i są to dokładnie
**`K` (4) + `L` (3)**, plus odsyłacz `J-01`, który nie jest przypadkiem. Powody w §8.
`SZK-J-02` powstał w drugiej rundzie **na wyraźny wniosek** `ODPOWIEDZ-047` §5, z częścią
zgodową wstrzymaną na `Q-16`.

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

> **⚠ REGUŁA WEJŚCIOWA — dopisana 12.08 po przeglądzie adwersarialnym (§10).**
> **Datę przypadku dobiera się względem `T0`, nie względem kalendarza.** Pięć z czternastu
> znalezisk przeglądu (`P-02`, `P-03`, `P-04`, `P-12`, `P-13`) to **jedna klasa**: data
> wyglądała sensownie w kalendarzu (właściwy dzień tygodnia, właściwy rytm), a była
> **przeszła albo obcięta regułą 2 h** względem zegara przypadku. Skutek bywa w obie strony
> — raz test przechodzi z niewłaściwej przyczyny, raz świeci czerwono na sprawnym silniku.
>
> **Trzy pytania przed wpisaniem daty do szkieletu:**
> 1. czy ten dzień jest **po** zegarze przypadku (nie „dziś", nie wczoraj)?
> 2. czy godzina jest **co najmniej 2 h** po zegarze (reguła najbliższego terminu)?
> 3. czy mieści się w **horyzoncie roli** (30 dni pacjent / 7 dni wystawianie)?
>
> Bezpieczna domyślna odległość w tym dokumencie: **tydzień od zegara przypadku**
> (`T0` → `2026-09-22`). Wszystkie szkielety pisane po tym wpisie mają ją stosować.

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
>
> **⚠ PRZEGLĄD 12.08 — `P-01`, POPRAWIONE.** `NEG` żąda tylko „odrzucone" — a to jest
> spełnione także przez implementację, która **odrzuca KAŻDĄ drugą poprawkę tego dnia**,
> bez patrzenia na kolizję. Odrzucenie z niewłaściwej przyczyny wygląda identycznie.
> **Dopisz `POZ-2`:** `POPRAWKA.zapisz(dodaj, 2026-09-16 19:00)` (bez kolizji) →
> **przyjęte**, `count(poprawki) == 2`, `count(w16) == 6`. Dopiero para „19:00 wchodzi,
> 12:30 nie" mierzy kolizję, a nie licznik poprawek.

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
>
> **⚠ PRZEGLĄD 12.08 — `P-02`, POPRAWIONE.** Urlop `14–18.09` **zachodzi na przeszłość**
> względem `T0 = 2026-09-15 08:00`: `14.09` jest dniem minionym, a `15.09` obcina reguła
> 2 h. Bez urlopu ten zakres i tak dałby `0 + 3 + 4 + 4 + 4`, więc **część oczekiwanego
> zera pochodzi od zegara, nie od urlopu** — a `ŚWIADEK` mierzy `22.09`, czyli **inny
> dzień**, więc niczego o tym zakresie nie dowodzi.
> **Poprawka:** urlop `2026-09-21 .. 2026-09-25`; `ŚWIADEK` = suma slotów **tego samego
> zakresu bez urlopu** == **20**; kontrola „poza urlopem" przenosi się na
> `2026-09-28 .. 2026-10-02` == **20**.

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
> **⚠ PRZEGLĄD 12.08 — `P-03`, POPRAWIONE. To był BŁĄD WARTOŚCI, nie słaba kontrola.**
> `w == [4, 0, 0, 0, 4]` dla dni `14.09 .. 18.09` jest **nieprawdą wobec poprawnej
> implementacji**: `14.09` to dzień **miniony** względem `T0`, więc `SLOTY` zwróci tam `0`,
> a test świeciłby czerwono na sprawnym silniku (fałszywa czerwień — gorsza od braku testu,
> bo wysyła szukać defektu tam, gdzie go nie ma).
> **Poprawka — całość o tydzień w przód:** urlop `2026-09-22 .. 2026-09-24`,
> mierzone dni `2026-09-21 .. 2026-09-25` → `[4, 0, 0, 0, 4]`.
> `NEG` (urlop jednodniowy) → `2026-09-22 .. 2026-09-22` → `[4, 0, 4, 4, 4]`.

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
> **⚠ PRZEGLĄD 12.08 — `P-04`, POPRAWIONE. `ARRANGE` jest NIEWYKONALNY.**
> Wizyta `15.09 09:00` przy zegarze `T0 = 15.09 08:00` leży **godzinę** przed terminem,
> a najbliższy możliwy termin to **2 h** — poprawna implementacja **odrzuci tę rezerwację**
> i przygotowanie nigdy nie powstanie. Test padłby w `ARRANGE`, czyli w miejscu, w którym
> nikt nie szuka defektu.
> **Poprawka:** wizyty `2026-09-22 09:00`, `2026-09-23 09:00`, `2026-09-24 09:00`;
> urlop `2026-09-22 .. 2026-09-24`. `NEG` (urlop bez wizyt) bez zmian.

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
>
> **⚠ PRZEGLĄD 12.08 — `P-05`, POPRAWIONE.** `NEG` ustawia `bufor_min := 0`, ale ten sam
> parametr **buduje raster**: przy zerze siatka przestaje być 60-minutowa i robi się
> 50-minutowa, więc starty byłyby `[09:00, 09:50, 10:40, 11:30]`, a **nie**
> `[09:00, 10:00, 12:00]`. Kontrola negatywna przeczy własnej przesłance.
> **Poprawka — nie ruszamy parametru, przesuwamy wizytę.** `NEG`: wizyta ręczna
> `11:05–11:55` → `count == 2`, ale **starty `[09:00, 10:00]`**, nie `[09:00, 12:00]`:
> `10:00` wchodzi (`10:50 + 10 == 11:00 ≤ 11:05`), `12:00` odpada (`11:55 + 10 == 12:05`).
> **Ta sama liczba, inny zbiór** — dokładnie ten wzorzec, który zapisaliśmy przy `J-05`:
> gdy operacja przesuwa coś w czasie, licznik jest niewrażliwy, mierzy się zbiór.

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
> **⚠ PRZEGLĄD 12.08 — `P-06`, POPRAWIONE. PERTURBACJA BYŁA MARTWA.**
> „ADHD liczone jako 60 min" daje zajętość `09:00–10:00` + bufor do `10:10`; slot `10:00`
> nadal odpada (`10:00 < 10:10`), więc wynik to **wciąż 2** — **perturbacja nie zapala
> testu**. Kontrola bez dowodu czerwieni jest traktowana jak nieistniejąca
> (`D-2026-08-07-13`), a martwa mutacja to ta sama klasa co `N-3`.
> **Poprawka:** `PERT: dlugosc_adhd := 50` (tyle co konsultacja) → zajętość `09:00–09:50`
> + bufor do `10:00`, slot `10:00` **wchodzi** → `count == 3`, starty
> `[10:00, 11:00, 12:00]` → **czerwony**. Sprawdzone rachunkiem, nie założone.

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
>
> **⚠ PRZEGLĄD 12.08 — `P-07`, POPRAWIONE. Dotyczy też `SZK-E-02` i `SZK-J-04`.**
> Trzy pomiary graniczne odwołują **tę samą rezerwację `R`** trzy razy. Drugie i trzecie
> wywołanie trafia w rezerwację **już odwołaną** — a wtedy `zwrot_gr == 0` może pochodzić
> ze stanu „nie ma czego odwoływać", **nie z okna 24 h**. Trzecia wartość `0` jest wtedy
> prawdziwa z niewłaściwej przyczyny, czyli test na granicy przechodzi przy **całkowicie
> zepsutym oknie**.
> **Poprawka:** każdy z trzech pomiarów na **świeżej kopii `R`** — tak, jak `SZK-E-03`
> i `SZK-J-03` już to mają zapisane. Trzy kopie, trzy zegary, zero dziedziczenia stanu.

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
>
> **⚠ PRZEGLĄD 12.08 — `P-07` dotyczy również tego szkieletu.** `ACT`, `POZ-2` i `NEG`
> odwołują **tę samą** rezerwację `R2` przy trzech różnych zegarach. **Świeża kopia
> na każdy pomiar.**

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
> **⚠ PRZEGLĄD 12.08 — `P-08`, ZGŁOSZONE (nie poprawiam sam).**
> **Ten szkielet zamraża kwotę przy `REZERWACJA.utworz`, a `SZK-D-06` — przy
> `BLOKADA.zaloz`.** To dwa różne momenty i **oba są w moich szkieletach**, czyli jedna
> rzecz opisana dwa razy, rozbieżnie (`P3`). Przy ścieżce psychologa dzieli je **48 h**,
> w których cennik może się zmienić.
> **Rekomendacja: zamrożenie w chwili ZAŁOŻENIA BLOKADY** — bo to wtedy pacjent dostaje
> kwotę do zapłaty w linku, a zwrot ma się równać temu, co naprawdę zapłacił
> (`CLAUDE.md` §4). Przy ścieżce własnej oba momenty i tak się zbiegają.
> **Wymaga jednej linijki w kontrakcie operacji** — `ZLECENIE-055` §3.

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
>
> **⚠ PRZEGLĄD 12.08 — `P-09`, POPRAWIONE. `ARRANGE` był NIEWYKONALNY.**
> „zegar taki, by do **OBU** wizyt zostało 30 h" jest niemożliwy: wizyty dzieli godzina,
> więc jeden zegar nie da 30 h do obu. Napisane tak, jakby liczba `30` była wymagana —
> a wymagane jest tylko, żeby **obie mieściły się w przedziale (24 h, 48 h)**.
> **Poprawka — konkretny zegar zamiast opisu:** `zegar := 2026-09-21 03:00` →
> do `RA` (22.09 09:00) zostaje **30 h**, do `RB` (22.09 10:00) — **31 h**.
> Obie w przedziale, więc werdykty `[14500, 0]` stoją.
> `NEG`: `zegar := 2026-09-20 07:00` → **50 h** i **51 h** → `[14500, 14500]`.

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
>
> **⚠ PRZEGLĄD 12.08 — `P-10`, POPRAWIONE.** `ASSERT` żąda
> `tp == max(start + 48 h, otwarcie_linku + 10 min)`, ale **`ARRANGE` nigdy nie otwiera
> linku** — `otwarcie_linku` jest niezdefiniowane, więc `max()` nie ma drugiego argumentu.
> Asercja odwołująca się do zdarzenia, którego nie było, jest spełnialna dowolnie.
> **Poprawka:** ten szkielet asertuje wyłącznie `tp == start + 48 h` (kształt zrzutu,
> dwie ścieżki, jeden zestaw pól). **Drugi stopień ma własny szkielet — `SZK-D-04`** —
> i tam `LINK.otworz` jest w `ACT`. Jedna rzecz, jedno miejsce.

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
> **⚠ PRZEGLĄD 12.08 — `P-11`, POPRAWIONE.** `NEG` mówi
> `suma_zajetych_minut(22.09) == 300` i **miesza dwie różne wielkości**: 240 min to
> **sloty oferowane** z nowego zakresu, a 60 min to **wizyta zajęta**. Suma `300` nie jest
> żadną z tych rzeczy i przechodzi przy implementacji, która liczy jedno zamiast drugiego.
> **Poprawka — dwie osobne asercje:** `count(SLOTY(22.09)) == 4` (oferta z nowego zakresu)
> **oraz** `suma_zajetych_minut(22.09) == 60` (wyłącznie zarezerwowana wizyta `10:00`).

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

## 7a · Przygotowania dodatkowe (grupy H, C, D, F)

**`fixtureH(zakres)`** — grupa H bada **doby przestawienia zegarów**, a te wypadają
w **niedzielę**. Rytm bazowy `S1` obejmuje pon–pt, więc na nim tych przypadków **nie da
się zbudować** — slotu tam po prostu nie ma:

```
fixtureH(zakres):
    S_H := specjalista(strefa: Europe/Warsaw)
    usluga(KONS_PELNA, dlugosc: 50, cena_gr: 14500)
    RYTM.zapisz(S_H, PELNOPLATNE, pon–nd, zakres)      # siedem dni, celowo
```

**`fixtureF(n_nisko, n_pelno)`** — pacjent `P` z zadaną **historią**, nie z zadanym
licznikiem. Licznik ma się **wyliczyć**; wpisany wprost mierzyłby sam siebie:

```
fixtureF(n_nisko, n_pelno):
    P := pacjent()
    n_nisko × REZERWACJA.utworz(P, KONS_NISKA, <termin przeszły>, status: ODBYTA)
    n_pelno × REZERWACJA.utworz(P, KONS_PELNA, <termin przeszły>, status: ODBYTA)
```

**`fixtureNY()`** — specjalista `S_NY` ze strefą `America/New_York` i **bez cyklicznego
rytmu niskopłatnego**; terminy wystawiane wyłącznie pojedynczo. Rytm cykliczny dokładałby
2 terminy w każdym tygodniu i licznik przestałby być jednoznaczny (`SZK-F-06` `NEG`).

---

## 7b · Grupa H — strefa czasowa i zmiana czasu

#### SZK-H-01 → `F2-H-01` · Ta sama godzina lokalna, dwa różne offsety
```
ARRANGE  zegar := T0 ; fixtureS1()
ACT      a := SLOTY(S1, KONS_PELNA, 2026-09-15)[start 09:00]      # wtorek, CEST
         zegar := 2026-11-10 08:00 Europe/Warsaw                  # wtorek, CET
         b := SLOTY(S1, KONS_PELNA, 2026-11-17)[start 09:00]      # wtorek, CET
ASSERT   start_utc(a) == 2026-09-15 07:00:00Z
         start_utc(b) == 2026-11-17 08:00:00Z
NEG      etykieta_lokalna(a) == etykieta_lokalna(b) == "09:00"
         # sam UTC nie dowodzi prezentacji, sama etykieta nie dowodzi zapisu
PERT     offset zapisany na stałe (+2) → start_utc(b) == 07:00:00Z
OBS      kolumna terminu Z BAZY oraz pole prezentacyjne z API — dwie drogi
KOTWICE  KONF-STREFA
```
> **Data `2026-11-17`, nie `2026-11-15`** — 15 listopada 2026 to **niedziela**, a rytm
> bazowy obejmuje pon–pt. Prostuje plan §5.H-01.
>
> **⚠ PRZEGLĄD 12.08 — `P-12`, POPRAWIONE. Ta sama klasa co `P-03`, druga strona.**
> Poprawiłem dzień tygodnia, a **przeoczyłem zegar**: przy `T0 = 2026-09-15 08:00` reguła
> 2 h odcina wszystko przed `10:00`, więc **slotu `09:00` dnia `2026-09-15` po prostu nie
> ma** (potwierdza to `SZK-C-01`: tego dnia sloty to `10:00, 11:00, 12:00`).
> `a := SLOTY(…)[start 09:00]` byłoby **puste**.
> **Poprawka:** pierwszy pomiar na `2026-09-22` (wtorek, CEST, tydzień od `T0`) →
> `start_utc(a) == 2026-09-22 07:00:00Z`. Drugi bez zmian.

#### SZK-H-02 → `F2-H-02` · Doba 23-godzinna, zakres 15:00–20:00
```
ARRANGE  zegar := 2026-03-25 08:00 Europe/Warsaw ; fixtureH(15:00–20:00)
ACT      w28 := SLOTY(S_H, KONS_PELNA, 2026-03-28)      # sobota, CET
         w29 := SLOTY(S_H, KONS_PELNA, 2026-03-29)      # niedziela, doba 23 h, CEST
ASSERT   count(w28) == 5 ; count(w29) == 5
         starty_lokalne(w28) == starty_lokalne(w29) == [15:00,16:00,17:00,18:00,19:00]
         starty_utc(w28) == [14:00Z, 15:00Z, 16:00Z, 17:00Z, 18:00Z]
         starty_utc(w29) == [13:00Z, 14:00Z, 15:00Z, 16:00Z, 17:00Z]
NEG      starty_utc(w28) ≠ starty_utc(w29)     # SAMA LICZBA NIE ODRÓŻNIA — 5 i 5
PERT     offset brany z pierwszego dnia zakresu → starty_utc(w29) == starty_utc(w28)
OBS      starty UTC z odpowiedzi + niezależne przeliczenie z kolumny bazy
KOTWICE  KONF-STREFA, KONF-BUFOR, KONF-DL-KONS
```
> **To jest przypadek wprost z zakresu wdrożenia** (s. 17: *„zakres 15:00–20:00 w noc
> przestawienia zegarów"*). Liczba slotów jest **identyczna po obu stronach zmiany czasu**
> — test liczący tylko sloty przechodzi przy całkowicie zepsutej strefie.

#### SZK-H-03 → `F2-H-03` · Doba 23-godzinna, zakres obejmujący nieistniejącą godzinę
```
ARRANGE  zegar := 2026-03-25 08:00 ; fixtureH(00:00–06:00)
ACT      w29 := SLOTY(S_H, KONS_PELNA, 2026-03-29)
         w28 := SLOTY(S_H, KONS_PELNA, 2026-03-28)
ASSERT   count(w29) == 5 ; count(w28) == 6
         starty_lokalne(w29) == [00:00, 01:00, 03:00, 04:00, 05:00]     # 02:00 NIE ISTNIEJE
         starty_utc(w29)     == [2026-03-28 23:00Z, 00:00Z, 01:00Z, 02:00Z, 03:00Z]
         odstępy(starty_utc(w29)) == [3600, 3600, 3600, 3600]           # ciągłe co godzinę
ŚWIADEK  count(w28) == 6                                                # zakres daje 6
NEG      count(starty_lokalne(w29) == 02:00) == 0
PERT     sloty generowane przez dodawanie 3600 s do ETYKIETY lokalnej → pojawia się 02:00
OBS      dwie listy: etykiety lokalne i starty UTC
KOTWICE  KONF-STREFA, KONF-BUFOR, KONF-DL-KONS
```

#### SZK-H-04 → `F2-H-04` · Doba 25-godzinna, godzina powtórzona (`Q-3`)
```
ARRANGE  zegar := 2026-10-22 08:00 ; fixtureH(00:00–06:00)
ACT      w25 := SLOTY(S_H, KONS_PELNA, 2026-10-25)      # niedziela, doba 25 h
         w26 := SLOTY(S_H, KONS_PELNA, 2026-10-26)      # poniedziałek, doba 24 h
ASSERT   count(w25) == 7 ; count(w26) == 6
         count(starty_lokalne(w25) == 02:00) == 2
         starty_utc dla tych dwóch == [2026-10-25 00:00Z (CEST), 01:00Z (CET)]
         count(rozróżnialnych etykiet dla tych dwóch) == 2      # warunek z Q-3
ŚWIADEK  count(w26) == 6
NEG      przy odrzuconym odczycie („powtórzona godzina raz") → count(w25) == 6
         i count(etykiet 02:00) == 1 — obie liczby są dziś CZERWIENIĄ, nie wariantem
PERT     deduplikacja po etykiecie lokalnej → count(w25) == 6
PERT-2   etykieta nierozróżnialna (dwa razy „02:00" bez znacznika) → czerwony
         MIMO poprawnych 7 slotów — warunek etykiety jest częścią decyzji Q-3
OBS      starty UTC i etykiety lokalne, dwie listy
KOTWICE  KONF-STREFA, KONF-BUFOR, KONF-DL-KONS
```

#### SZK-H-05 → `F2-H-05` · Niezmiennik doby 25-godzinnej
```
ARRANGE  zegar := 2026-10-22 08:00 ; fixtureH(00:00–06:00)
ACT      w := SLOTY(S_H, KONS_PELNA, 2026-10-25)
ASSERT   count(duplikatów startu UTC)        == 0
         count(duplikatów etykiety lokalnej) == 1        # jedna para: 02:00
ŚWIADEK  count(w) == 7
NEG      ten sam pomiar dnia 2026-10-26 → duplikatów UTC == 0, etykiet == 0
PERT     klucz slotu budowany z etykiety lokalnej → jeden slot ginie
         albo konflikt unikalności (specjalista_id, termin)
OBS      zliczenie duplikatów W BAZIE, po kluczu (specjalista_id, termin UTC)
KOTWICE  KONF-STREFA
```
> Ten klucz to ten sam, na którym stoi `CLAUDE.md` §6 i cała grupa I. **Tutaj rozstrzyga
> się, czy jest odporny na zmianę czasu** — dwa sloty o etykiecie `02:00` muszą być dwoma
> różnymi wierszami, a nie kolizją.

#### SZK-H-06 → `F2-H-06` · Okna reguł niezależne od strefy pacjenta
```
ARRANGE  R_W := rezerwacja pacjenta ze strefą Europe/Warsaw,  wizyta 2026-09-22 09:00
         R_NY:= rezerwacja pacjenta ze strefą America/New_York, wizyta 2026-09-22 09:00
         obie: kwota_zamrozona 14500, zrzut z oknem 86 400 s
ACT      zegar := 2026-09-21 08:59:59 Europe/Warsaw          # JEDEN moment absolutny
         w := [ REZERWACJA.odwolaj(R_W).zwrot_gr, REZERWACJA.odwolaj(R_NY).zwrot_gr ]
ASSERT   w == [14500, 14500]
NEG      data_graniczna_prezentowana(R_W)  == "2026-09-21 09:00"
         data_graniczna_prezentowana(R_NY) == "2026-09-21 03:00"     # EDT = UTC−4
         # wynik reguły identyczny, PREZENTACJA różna — obie asercje w jednym przypadku
PERT     okno liczone w strefie pacjenta → w == [14500, 0]
OBS      zwrot_gr ×2 + pole prezentacyjne daty granicznej ×2
KOTWICE  KONF-OKNO-24H, KONF-STREFA, KONF-CENY
```
> Sama równość zwrotów przechodzi także wtedy, gdy strefa pacjenta jest ignorowana
> **wszędzie, łącznie z prezentacją** — a wtedy pacjent w Nowym Jorku dostaje godzinę,
> której u siebie nie rozpozna. Dlatego `NEG` mierzy **różnicę** tam, gdzie ma być różnica.

#### SZK-H-07 → `F2-H-07` · Doba jako jednostka ma 23 / 24 / 25 godzin
```
ARRANGE  fixtureH(00:00–24:00)
ACT      zegar := 2026-03-26 08:00 ; a := SLOTY(S_H, KONS_PELNA, 2026-03-29)
         zegar := T0               ; b := SLOTY(S_H, KONS_PELNA, 2026-09-15)
         zegar := 2026-10-22 08:00 ; c := SLOTY(S_H, KONS_PELNA, 2026-10-25)
ASSERT   [count(a), count(b), count(c)]                        == [23, 24, 25]
         [suma_minut(a), suma_minut(b), suma_minut(c)]          == [1380, 1440, 1500]
NEG      doba liczona jako 86 400 s → [24, 24, 24]
         oraz ostatni slot 29.03 kończyłby się 01:00 dnia NASTĘPNEGO
PERT     granica doby wyznaczana w UTC → 23 i 25 znikają
OBS      liczba slotów ORAZ suma minut — dwie miary tego samego
KOTWICE  KONF-STREFA, KONF-BUFOR, KONF-DL-KONS
```
> **⚠ PRZEGLĄD 12.08 — `P-13`, POPRAWIONE. Znowu zegar, trzeci raz.**
> Pomiar `b` bierze `zegar := T0` i dzień `2026-09-15` — czyli **ten sam dzień**.
> Przy rytmie całodobowym reguła 2 h odcina wszystko przed `10:00`, więc wyszłoby
> **14 slotów, nie 24**, i `[23, 24, 25]` padłoby na sprawnym silniku.
> **Poprawka:** `zegar := 2026-09-10 08:00` dla pomiaru `b` (dzień mierzony bez zmian:
> `2026-09-15`). Pomiary `a` i `c` mają zegary z wyprzedzeniem 3 dni — są poprawne.
>
> **Wniosek do zasad planu:** trzy z czternastu znalezisk tego przeglądu (`P-02`, `P-03`,
> `P-04`, `P-12`, `P-13` — właściwie pięć) to **jedna klasa: data przypadku ustawiona
> względem kalendarza, a nie względem zegara `T0`**. Dopisuję do §1 regułę wejściową.

---

## 7c · Grupa C — horyzonty

#### SZK-C-01 → `F2-C-01` · Najbliższy termin 2 h: trzy wartości na granicy
```
ARRANGE  fixtureS1()
ACT      dla z ∈ [2026-09-15 07:59:59, 08:00:00, 08:00:01]:
             zegar := z ; w[z] := SLOTY(S1, KONS_PELNA, 2026-09-15)
ASSERT   [count(w[z])] == [3, 3, 2]
         # próg = zegar + 2 h; slot 10:00 wchodzi, dopóki próg ≤ 10:00:00
NEG      zegar := 2026-09-15 05:00:00 → count == 4      # cały dzień dostępny
PERT     '>=' → '>' w progu → [3, 2, 2]
OBS      SLOTY z jawnie podanym zegarem (K-6), nigdy z zegara maszyny
KOTWICE  KONF-DL-KONS
```

#### SZK-C-02 → `F2-C-02` · Kalendarz pacjenta otwarty 30 dni
```
ARRANGE  zegar := T0 ; fixtureS1()
ACT      w := SLOTY(rola: PACJENT, S1, KONS_PELNA, 2026-09-15 .. 2026-11-15)
ASSERT   count(dni z ≥1 slotem) == 23           # dni robocze 15.09 .. 15.10 włącznie
         count(SLOTY(rola: PACJENT, …, 2026-10-15)) == 4     # czwartek, ostatni dzień okna
         count(SLOTY(rola: PACJENT, …, 2026-10-16)) == 0     # piątek, dzień za oknem
NEG      count(SLOTY(rola: GRAFIK, …, 2026-10-16)) == 4
         # horyzont 30 dni to ograniczenie PREZENTACJI dla pacjenta, nie brak slotu
PERT     horyzont liczony w miesiącach → pacjent widzi 2026-10-16
OBS      dwa zapytania w dwóch rolach
KOTWICE  KONF-DL-KONS
```

#### SZK-C-03 → `F2-C-03` · Wystawianie 7 dni w przód — egzekwowane w API
```
ARRANGE  zegar := T0 ; fixtureS1()
ACT      dla d ∈ [2026-09-21, 2026-09-22, 2026-09-23]:        # T0+6, T0+7, T0+8
             wynik[d] := POPRAWKA.zapisz(S1, dodaj, d 18:00)
ASSERT   [wynik[d]] == [przyjęte, przyjęte, odrzucone(422)]
         count(poprawki(S1)) == 2
NEG      ta sama operacja dla T0+8 przez KOORDYNATORA → odrzucone, count == 2   # Q-6
PERT     kontrola przeniesiona do warstwy prezentacji → T0+8 przechodzi przez API
OBS      count(poprawki) Z BAZY, nie z odpowiedzi operacji
KOTWICE  —   (kotwica horyzontu powstaje razem z resztą grupy C w etapie B)
```

#### SZK-C-04 → `F2-C-04` · Termin ręczny spoza grafiku jest dozwolony
```
ARRANGE  zegar := T0 ; fixtureS1()
ACT      r := REZERWACJA.utworz(przez: SPECJALISTA, S1, KONS_PELNA, 2026-09-22 20:00)
ASSERT   count(rezerwacje(S1, 2026-09-22))                    == 1
         count(SLOTY(rola: PACJENT, S1, KONS_PELNA, 2026-09-22)) == 4
         # godzina 20:00 NIE staje się slotem do rezerwacji dla innych
         suma_zajetych_minut(2026-09-22) == 60
NEG      termin ręczny 2026-09-22 12:30 (koliduje z rytmem przez bufor)
         → count(SLOTY(…)) == 3, starty == [09:00, 10:00, 11:00]
         # termin ręczny ZABIERA dostępność, nie dodaje
PERT     termin ręczny publikowany jako slot → count(SLOTY) == 5
OBS      SLOTY w roli pacjenta + count(rezerwacje) z bazy
KOTWICE  KONF-BUFOR, KONF-DL-KONS
```

#### SZK-C-05 → `F2-C-05` · Horyzont 7 dni a termin ręczny — rozdzielone (`Q-6`)
```
ARRANGE  zegar := T0 ; fixtureS1()
ACT      p := POPRAWKA.zapisz(S1, dodaj, 2026-10-05 20:00)              # T0+20d
         r := REZERWACJA.utworz(przez: SPECJALISTA, S1, KONS_PELNA, 2026-10-05 20:00)
ASSERT   p == odrzucone      ; count(poprawki(S1))  == 0
         r == przyjęte       ; count(rezerwacje(S1)) == 1
         # ta sama data: ODRZUCONA jako wystawienie, PRZYJĘTA jako umówienie
NEG      termin ręczny na 2026-09-14 20:00 (przeszłość) → odrzucone, count == 0
         # zniesienie horyzontu W PRZÓD nie znosi kontroli przeszłości
PERT     jedno wspólne sprawdzenie dla obu operacji → obie liczby jednakowe
OBS      dwie tabele, dwa niezależne zapytania
KOTWICE  —
```
> Wartość oczekiwana stoi na **rekomendacji** `Q-6` (nieblokujące, przyjęte w planie §8.2):
> horyzont dotyczy **wystawiania dostępności**, nie umawiania konkretnej wizyty.

---

## 7d · Grupa D — blokada slotu

**Wspólny cel grupy:** blokowany slot to zawsze `(S1, 2026-09-22 10:00)` — tydzień od `T0`,
więc reguła `min(okno, czas_do_wizyty − M)` **nie przycina** okna i nie miesza się do
pomiaru. Dzień ma bazowo **4** sloty; z blokadą — **3**.

#### SZK-D-01 → `F2-D-01` · Rezerwacja własna: 10 min, trzy wartości
```
ARRANGE  zegar := T0 ; fixtureS1()
         t0 := T0 ; BLOKADA.zaloz(ścieżka: WLASNA, S1, 2026-09-22 10:00, pacjent: A)
ACT      dla t ∈ [t0+09:59.999, t0+10:00.000, t0+10:00.001]:
             zegar := t ; w[t] := count(SLOTY(S1, KONS_PELNA, 2026-09-22, pacjent: B))
ASSERT   [w[t]] == [3, 3, 4]                    # konwencja K-1: okno domknięte
NEG      bez blokady wszystkie trzy pomiary == 4
PERT     blokada zwalniana wyłącznie przez zadanie cykliczne, nie przy odczycie
         → w[t0+30 min] == 3
OBS      SLOTY drugiego pacjenta ORAZ trzymane_do z bazy — „logicznie wygasła"
         i „usunięta przez sprzątaczkę" to dwa różne stany
KOTWICE  KONF-DL-KONS
```

#### SZK-D-02 → `F2-D-02` · Blokada dwustopniowa: zegar startuje po potwierdzeniu
```
ARRANGE  zegar := T0 ; fixtureS1() ; t0 := T0
ACT      BLOKADA.zaloz(WLASNA, S1, 2026-09-22 10:00, pacjent: A)
         zegar := t0+02:00 ; BLOKADA.potwierdz(…)
ASSERT   trzymane_do == t0+12:00                # nie t0+10:00
         count(SLOTY(…, pacjent: B)) w t0+11:00        == 3
         count(SLOTY(…, pacjent: B)) w t0+12:00.001    == 4
NEG      BEZ potwierdzenia: slot wraca po blokadzie wstępnej (Q-8 = 10 min)
         count(SLOTY(…, pacjent: B)) w t0+10:00.001    == 4
         # niepotwierdzony klikacz NIE trzyma slotu przez pełne okno
PERT     zegar liczony od wyboru terminu → trzymane_do == t0+10:00
OBS      trzymane_do z bazy + odczyt SLOTY drugim kontem
KOTWICE  —   (blokada_koszyka_min i blokada_wstepna_min: kotwice w etapie B razem z grupą D)
```
> **Dwie liczby różniące się o 2 minuty i to jest cały mechanizm** (`D-2026-08-09-11` §4:
> gdyby 10 minut liczyło się od wyboru terminu, krok z kodem zjadłby połowę okna).

#### SZK-D-03 → `F2-D-03` · Umawianie przez psychologa: 48 h
```
ARRANGE  zegar := 2026-09-15 10:00 ; fixtureS1()
ACT      BLOKADA.zaloz(ścieżka: PSYCHOLOG, S1, 2026-09-22 10:00, pacjent: A)
ASSERT   trzymane_do == 2026-09-17 10:00
         count(SLOTY(…, pacjent: B)) w 2026-09-17 09:59:59 == 3
         count(SLOTY(…, pacjent: B)) w 2026-09-17 10:00:01 == 4
NEG      ta sama operacja ścieżką WLASNA → trzymane_do == 2026-09-15 10:10
         # jedna operacja, dwie ścieżki, dwie różne liczby
PERT     obie ścieżki czytają jedno pole konfiguracji → trzymane_do równe
OBS      trzymane_do z bazy
KOTWICE  —
```

#### SZK-D-04 → `F2-D-04` · Drugi stopień: 10 minut od OTWARCIA linku (`Q-9`)
```
ARRANGE  blokada jak w SZK-D-03 (trzymane_do == 2026-09-17 10:00)
ACT      zegar := 2026-09-17 09:55 ; LINK.otworz(token)
ASSERT   trzymane_do == 2026-09-17 10:05          # max(2 dni, otwarcie + 10 min)
POZ-2    płatność zaksięgowana 2026-09-17 10:03
         → count(rezerwacje) == 1 ; count(zadania_zwrotu) == 0
NEG      LINK.otworz o 2026-09-16 20:00 (dawno przed końcem)
         → trzymane_do NADAL 2026-09-17 10:00, NIE 2026-09-16 20:10
PERT     `min` zamiast `max` → trzymane_do == 2026-09-16 20:10
OBS      trzymane_do przed i po LINK.otworz — dwa odczyty tego samego pola
KOTWICE  —
```
> Uzasadnienie architekta (`ODPOWIEDZ-045`): *otwarcie linku nie może SKRACAĆ okna
> pacjenta*. `NEG` jest tu ważniejszy od `ASSERT`: `min` zamiast `max` daje wynik, który
> na pierwszy rzut oka wygląda jak działający drugi stopień.

#### SZK-D-05 → `F2-D-05` · `okno = min(okno_ścieżki, czas_do_wizyty − M)`
```
ARRANGE  zegar := 2026-09-15 18:00 ; fixtureS1()
ACT      BLOKADA.zaloz(PSYCHOLOG, S1, wizyta: 2026-09-16 09:00)    # wizyta JUTRO
ASSERT   trzymane_do == 2026-09-16 07:00                            # M = 2 h
         termin_wizyty − trzymane_do == 7200 s
         trzymane_do < termin_wizyty                                # NIGDY po wizycie
NEG      ta sama operacja dla wizyty 2026-10-15 09:00 (miesiąc naprzód)
         → trzymane_do == 2026-09-17 18:00        # pełne 48 h, min nie przycina
PERT     `min` usunięty → trzymane_do == 2026-09-17 18:00 dla wizyty JUTRZEJSZEJ,
         czyli termin płatności PO wizycie
OBS      trzymane_do z bazy + różnica do terminu wizyty w sekundach
KOTWICE  —
```

#### SZK-D-06 → `F2-D-06` · Płatność po wygaśnięciu NIE tworzy wizyty
```
ARRANGE  blokada pacjenta A na (S1, 2026-09-22 10:00), kwota_zamrozona 14500
         zegar := po trzymane_do(A)     → blokada wygasła
         REZERWACJA.utworz(pacjent: B, ten sam termin)      # B zajął slot
ACT      webhook „płatność zaksięgowana" pacjenta A
ASSERT   count(rezerwacje WHERE specjalista=S1 AND termin=…) == 1        # to jest B
         count(wizyty(A))         == 0
         count(zadania_zwrotu(A)) == 1
         kwota(zadanie)           == 14500                               # z ZAMROŻONEJ
POZ-2    płatność PRZED wygaśnięciem → count(rezerwacje) == 1 (A), zadania == 0
NEG      blokada wygasła, ale NIKT slotu nie zajął
         → count(wizyty(A)) == 0 ; count(zadania) == 1, typ == KOORDYNATOR   # Q-11
         # automat NIE tworzy wizyty nawet przy wolnym terminie
PERT     ścieżka „zapłacone, więc rezerwuj" → count(rezerwacje) == 2
         ALLOWLISTA (ścieżka pieniędzy)
OBS      liczba wierszy rezerwacji z bazy + liczba zadań z osobnej tabeli
KOTWICE  KONF-CENY
```
> **⚠ PRZEGLĄD 12.08 — `P-08` dotyczy również tego szkieletu.** `ARRANGE` przypisuje
> `kwota_zamrozona` już **blokadzie**, a `SZK-G-01` zamraża dopiero przy
> `REZERWACJA.utworz`. Do czasu rozstrzygnięcia (`ZLECENIE-055` §3) czytaj oba szkielety
> jako **zamrożenie w chwili założenia blokady** — inaczej `kwota(zadanie) == 14500`
> nie ma skąd pochodzić, bo pacjent `A` nigdy nie doszedł do utworzenia rezerwacji.

#### SZK-D-07 → `F2-D-07` · Limit równoczesnych nieopłaconych blokad (`Q-12` = 2)
```
ARRANGE  zegar := T0 ; fixtureS1() ; pacjent A
ACT      w1 := BLOKADA.zaloz(WLASNA, S1, 2026-09-22 10:00, A)
         w2 := BLOKADA.zaloz(WLASNA, S1, 2026-09-22 11:00, A)
         w3 := BLOKADA.zaloz(WLASNA, S1, 2026-09-22 12:00, A)
ASSERT   [w1, w2, w3] == [przyjęte, przyjęte, odrzucone(422)]
         count(aktywne_blokady(A)) == 2
         count(SLOTY(…, pacjent: B)) == 2                  # 4 − 2 zajęte
NEG      po wygaśnięciu w1: BLOKADA.zaloz(…, A) → przyjęte, count(aktywne) == 2 (nie 3)
PERT     limit liczony po SESJI zamiast po PACJENCIE
         → ten sam pacjent z dwóch przeglądarek zakłada 4 blokady
OBS      count(aktywne_blokady) z bazy, klucz = pacjent
KOTWICE  —
```
> Perturbacja odtwarza scenariusz zamrażania grafiku (`D5` z `D-2026-08-09-08`).
> Ten limit **zastąpił** kod przy każdej rezerwacji (`R-3`), więc jest dziś jedyną obroną.

#### SZK-D-08 → `F2-D-08` · Wygaśnięcie blokady zostawia ślad
```
ARRANGE  blokada pacjenta A; zegar przesunięty za trzymane_do
ACT      (wygaśnięcie)
ASSERT   count(zdarzenia typu BLOKADA_WYGASLA dla A) == 1
         count(zaplanowane_powiadomienia do A)       == 1     # kanał: F6
NEG      blokada zakończona PŁATNOŚCIĄ → count(BLOKADA_WYGASLA) == 0
PERT     ciche zwolnienie slotu → count == 0
OBS      tabela zdarzeń (append-only), zapytanie niezależne od mechanizmu blokady
KOTWICE  —
```
> `D-2026-08-09-08`: *cisza znaczy „nie wiem, czy mam wizytę"*. Zdarzenie liczymy tu,
> wysyłkę — w F6; granica faz odnotowana w planie §7.

#### SZK-D-09 → `F2-D-09` · „2 dni" a zmiana czasu (`Q-19` = 48 h absolutnych)
```
ARRANGE  zegar := 2026-10-24 10:00 CEST ; fixtureS1()
ACT      BLOKADA.zaloz(PSYCHOLOG, S1, wizyta: 2026-10-27 09:00)
ASSERT   trzymane_do == 2026-10-26 09:00 CET        # 48 h absolutnych
         trzymane_do − start == 172800 s
NEG      odczyt kalendarzowy dałby 2026-10-26 10:00 CET == 176400 s (49 h)
         — to jest dziś CZERWIEŃ, nie wariant
POZ-2    ta sama operacja w tygodniu bez zmiany czasu (start 2026-09-15 10:00)
         → oba odczyty dają 2026-09-17 10:00; przypadek MUSI wtedy przechodzić
         (kontrola, że w ogóle rozróżnia tylko tam, gdzie jest co rozróżniać)
PERT-2   start 2026-03-27 10:00 CET → 48 h daje 2026-03-29 11:00 CEST (172800 s),
         kalendarzowo 10:00 CEST (169200 s = 47 h) — rozjazd w DRUGĄ stronę
OBS      trzymane_do w UTC ORAZ lokalnie, plus różnica w sekundach
KOTWICE  KONF-STREFA
```
> `POZ-2` jest tu konieczne: bez niego przypadek przechodzi także przy implementacji,
> która **zawsze** liczy kalendarzowo, a różnicę widać tylko dwa razy w roku.

---

## 7e · Grupa F — dwa rozłączne limity

#### SZK-F-01 → `F2-F-01` · Limit pacjenta: 10 niskopłatnych, granica
```
ARRANGE  fixtureF(n_nisko: 9, n_pelno: 0)
ASSERT   LIMIT.pacjent(P).pozostale == 1
ACT      r10 := REZERWACJA.utworz(P, KONS_NISKA, …)
         r11 := REZERWACJA.utworz(P, KONS_NISKA, …)
ASSERT   r10 == przyjęte ; LIMIT.pacjent(P).pozostale == 0
         r11 == odrzucone(422) ; LIMIT.pacjent(P).wykorzystane == 10      # nie 11
NEG      fixtureF(8, 0) → dwie rezerwacje przyjęte, pozostale == 0, trzecia odrzucona
PERT     granica '<' zamiast '<=' → r10 odrzucone, wykorzystane == 9
OBS      LIMIT.pacjent ORAZ niezależne zliczenie wierszy rezerwacji z bazy —
         licznik agregowany i policzone wiersze MUSZĄ się zgadzać
KOTWICE  —   (limit_niskoplatnych_wizyt: kotwica w etapie B razem z grupą F)
```
> **⚠ PRZEGLĄD 12.08 — `P-14`, POPRAWIONE. Dotyczy `F-01`, `F-04`, `F-05`, `F-07`, `F-08`.**
> Terminy rezerwacji zapisałem jako `…`, a odrzucenie jako samo `odrzucone(422)`.
> **Odrzucenie bez wskazanej przyczyny jest spełnione także przez „brak wolnego slotu"** —
> a przy nieprzypiętych terminach to scenariusz całkiem prawdopodobny, bo rytm niskopłatny
> daje tylko **2** sloty na wtorek. Test limitu przechodziłby wtedy, mierząc dostępność.
> To ta sama klasa co **milcząca czerwień** w perturbacjach (`D-2026-08-07-22`): odmowa
> z niewłaściwego powodu wygląda identycznie jak odmowa właściwa.
> **Poprawka, dwie części:**
> 1. **terminy przypięte co do daty i godziny**, z jawnym świadkiem
>    `count(wolne_terminy_niskopłatne) ≥ liczba planowanych rezerwacji`;
> 2. **każda odmowa asertuje PRZYCZYNĘ**: `przyczyna == LIMIT_PACJENTA` —
>    tak, jak `SZK-F-10` już to robi. `F-10` był wzorcem, tylko go nie zastosowałem
>    do reszty grupy.

#### SZK-F-02 → `F2-F-02` · Limit nie odnawia się w czasie
```
ARRANGE  fixtureF(10, 0), wizyty rozłożone na 3 lata (≈3 rocznie) ; zegar := T0
ACT      w := LIMIT.pacjent(P)
ASSERT   w.pozostale == 0
NEG      zegar := T0 + 1 rok → pozostale NADAL == 0        # limit nie jest oknem
POZ-2    fixtureF(9, 0) rozłożone tak samo → pozostale == 1
PERT     liczenie w oknie 12 miesięcy → pozostale == 7
OBS      LIMIT.pacjent przy dwóch różnych zegarach
KOTWICE  —
```

#### SZK-F-03 → `F2-F-03` · Limit liczy WYŁĄCZNIE niskopłatne
```
ARRANGE  fixtureF(n_nisko: 0, n_pelno: 10)
ASSERT   LIMIT.pacjent(P).pozostale == 10
ACT      r := REZERWACJA.utworz(P, KONS_NISKA, …)
ASSERT   r == przyjęte
NEG      fixtureF(10, 0) → pozostale == 0,
         ALE REZERWACJA.utworz(P, KONS_PELNA, …) == przyjęte
         count(rezerwacje(P)) rośnie o 1
PERT     licznik liczy wszystkie wizyty → pozostale == 0 w pierwszym przypadku
         ALLOWLISTA — ten defekt ODCINA OD POMOCY ludzi płacących pełną stawkę
OBS      LIMIT.pacjent + liczba rezerwacji per kategoria z bazy
KOTWICE  —
```
> `D-2026-08-09-08` ⛔: *historia obejmuje wszystkie wizyty, LIMIT liczy tylko niskopłatne*.
> Kontrola negatywna jest tu ważniejsza od pozytywnej — bada, czy wyczerpany limit
> **nie zamyka** drogi pełnopłatnej.

#### SZK-F-04 → `F2-F-04` · Licznik wisi na PACJENCIE, nie na klikającym
```
ARRANGE  P z 10 wizytami niskopłatnymi: 4 umówione przez PSYCHOLOGA,
         3 przez PANEL pacjenta, 3 przez STRONĘ
ASSERT   LIMIT.pacjent(P).wykorzystane == 10 ; pozostale == 0
ACT      trzy próby 11. rezerwacji, po jednej każdą ścieżką
ASSERT   wszystkie trzy == odrzucone(422)      # 3 × 422
NEG      te same 10 wizyt rozdzielone na DWÓCH pacjentów (5 + 5)
         → pozostale == [5, 5]                 # licznik nie skleja ludzi
PERT     licznik po AUTORZE operacji → psycholog wyczerpuje limit swoim pacjentom
OBS      LIMIT.pacjent dla obu pacjentów + trzy próby przez trzy ścieżki
KOTWICE  —
```
> Spec mówi, że regułą jest umawianie niskopłatnych **przez psychologa** — ten defekt
> trafiłby więc w **większość** wizyt niskopłatnych, a nie w przypadek brzegowy.

#### SZK-F-05 → `F2-F-05` · Twarda bramka z jawnym wyjątkiem
```
ARRANGE  fixtureF(10, 0) → pozostale == 0
ACT      p1 := LIMIT.podnies(P, +4, uzasadnienie: <41 znaków>)
         p2 := LIMIT.podnies(P, +4, uzasadnienie: <39 znaków>)
ASSERT   p1 == przyjęte  ; limit == 14 ; pozostale == 4 ; count(wpisy_dziennika) == 1
         p2 == odrzucone ; limit == 14 ;                  count(wpisy_dziennika) == 1
NEG      próba rezerwacji przy pozostale == 0 BEZ podniesienia
         → 422 oraz count(wpisy_dziennika) == 0
         # odmowa nie jest decyzją uznaniową i nie brudzi dziennika
PERT     bramka zamieniona na ostrzeżenie → rezerwacja przechodzi przy pozostale == 0
OBS      LIMIT.pacjent + count wierszy dziennika (tabela append-only, F5 — granica faz)
KOTWICE  —
```

#### SZK-F-06 → `F2-F-06` · Limit podażowy 4/tydzień ISO — przy WYSTAWIANIU
```
ARRANGE  zegar := T0 ; fixtureS1()
         # rytm niskopłatny (wt 15:00–17:00) daje 2 terminy w KAŻDYM tygodniu
ASSERT   LIMIT.specjalista(S1, W39).wystawione == 2
ACT      a := POPRAWKA.zapisz(S1, dodaj, NISKOPLATNE, 2026-09-22 17:00)
         b := POPRAWKA.zapisz(S1, dodaj, NISKOPLATNE, 2026-09-22 18:00)
         c := POPRAWKA.zapisz(S1, dodaj, NISKOPLATNE, 2026-09-22 19:00)
ASSERT   wystawione(W39) po a, b, c == [3, 4, 4]
         c == odrzucone(422) ; count(wskazanych terminów w komunikacie) == 1
         wskazany termin == 2026-09-22 19:00
NEG      ta sama operacja w tygodniu W40 (2026-09-29 17:00) → przyjęte
         wystawione(W40) == 3      # 2 z RYTMU + 1 poprawka
         wystawione(W39) == 4
PERT     tydzień liczony od niedzieli → operacja z poniedziałku wpada do poprzedniej puli
OBS      LIMIT.specjalista dla dwóch tygodni + liczba slotów niskopłatnych z bazy
KOTWICE  —
```
> **`wystawione(W40) == 3`, nie 1** — rytm jest cykliczny i dokłada 2 terminy w każdym
> tygodniu. Prostuje plan §5.F-06.

#### SZK-F-07 → `F2-F-07` · Reset w poniedziałek 00:00 Warsaw, także dla innej strefy
```
ARRANGE  fixtureNY()      # strefa America/New_York, BEZ cyklicznego rytmu niskopłatnego
         4 × POPRAWKA.zapisz(S_NY, dodaj, NISKOPLATNE, <terminy w W38>)
ASSERT   LIMIT.specjalista(S_NY, W38).wystawione == 4
ACT      zegar := 2026-09-20 23:30 America/New_York        # = 2026-09-21 05:30 Warsaw
         w := POPRAWKA.zapisz(S_NY, dodaj, NISKOPLATNE, <termin>)
ASSERT   w == przyjęte
         wystawione(W38) == 4 ; wystawione(W39) == 1
NEG      zegar := 2026-09-20 17:00 America/New_York        # = niedziela 23:00 Warsaw
         → odrzucone ; wystawione(W38) == 4 ; wystawione(W39) == 0
         # DWIE operacje w odstępie 6,5 h, po dwóch stronach granicy tygodnia
PERT     tydzień liczony w strefie specjalisty → pierwsza operacja odrzucona
OBS      LIMIT.specjalista dla obu tygodni
KOTWICE  KONF-STREFA
```

#### SZK-F-08 → `F2-F-08` · Limit podażowy NIE działa przy rezerwacji
```
ARRANGE  wystawione(S1, W39) == 4
         konfiguracja(limit_niskoplatnych_na_tydzien) := 2      # koordynator OBNIŻA
ACT      4 × REZERWACJA.utworz(pacjenci: różni, KONS_NISKA, cztery wystawione terminy)
ASSERT   count(rezerwacje) == 4 ; count(odmów) == 0
NEG      po obniżeniu NOWE wystawienie odrzucone
         → wystawione(W39) == 4 ; count(nowych) == 0        # w przód, nie wstecz (Q-13)
PERT     sprawdzenie limitu przeniesione do ścieżki rezerwacji → count(odmów) == 2
OBS      count(rezerwacje) z bazy + count odpowiedzi 422
KOTWICE  —
```
> **To jest test MIEJSCA egzekwowania, nie wartości.** Spec podaje powód wprost: *pacjent
> nigdy nie powinien zobaczyć wolnego terminu i dostać odmowy przy płatności — w najgorszym
> możliwym momencie, po podjęciu decyzji i wyjęciu karty*.

#### SZK-F-09 → `F2-F-09` · „Wystawiony" = slot OTWARTY (`Q-14`)
```
ARRANGE  wystawione(S1, W39) == 4
ACT      REZERWACJA.utworz(pacjent, KONS_NISKA, <jeden z tych czterech terminów>)
         piaty := POPRAWKA.zapisz(S1, dodaj, NISKOPLATNE, <nowy termin w W39>)
ASSERT   wystawione(W39) == 4          # rezerwacja NIE zwalnia miejsca w puli podaży
         piaty == odrzucone(422)
NEG      odrzucony odczyt („otwarty i wolny") dałby wystawione == 3 i piaty == przyjęte
         → wystawione == 5. Obie liczby są dziś CZERWIENIĄ, nie wariantem
PERT     licznik pomijający terminy zarezerwowane → wystawione == 3
OBS      LIMIT.specjalista + liczba slotów niskopłatnych z bazy, per stan
KOTWICE  —
```

#### SZK-F-10 → `F2-F-10` · Dwa limity są rozłączne
```
ARRANGE  P z pozostale == 0 (10/10) ; S1 z wystawione(W39) == 3
ACT      w := REZERWACJA.utworz(P, KONS_NISKA, <termin S1>)
ASSERT   w == odrzucone ; przyczyna == LIMIT_PACJENTA
         LIMIT.specjalista(S1, W39).wystawione == 3        # NIEZMIENIONE
         LIMIT.pacjent(P).wykorzystane        == 10        # NIEZMIENIONE
         # odmowa niczego nie konsumuje
NEG      P2 z pozostale == 5 ; S1 z wystawione(W39) == 4
         → rezerwacja PRZYJĘTA ; wystawione == 4 (rezerwacja nie podnosi podaży)
           wykorzystane(P2) == 6
         # dwa liczniki, cztery wartości, żadnego przecieku
PERT     jeden wspólny licznik → jedna z czterech liczb się rozjeżdża
OBS      dwa niezależne zapytania o liczniki
KOTWICE  —
```

---

## 7f · `SZK-J-02` — dopisane na wniosek `ODPOWIEDZ-047` §5

Architekt poprosił, żeby **twarde liczby `J-02` napisać teraz**, a wartość zgody dopłynęła
po spotkaniu z Fundacją (`Q-16`). Reszta grupy J czeka.

#### SZK-J-02 → `F2-J-02` · Psycholog umawia osobę bez konta (część twarda)
```
ARRANGE  zegar := T0 ; fixtureS1() ; osoba X bez konta i bez rekordu pacjenta
ACT      REZERWACJA.utworz(przez: PSYCHOLOG, S1, KONS_NISKA, 2026-09-22 15:00,
                           dane: {imię, nazwisko, e-mail})
ASSERT   count(rezerwacje ze statusem CZEKA_NA_PLATNOSC) == 1
         count(rekordy_pacjenta pasujące do X)           == 1
         count(kont utworzonych w tym momencie)          == 0
         (po zaksięgowaniu płatności) LIMIT.pacjent(X).wykorzystane == 1
NEG      ta sama osoba umówiona przez psychologa DRUGI raz
         → count(rekordy_pacjenta) NADAL == 1 ; wykorzystane == 2
PERT     tożsamość pacjenta wyprowadzana z identyfikatora rezerwacji → 2 rekordy
OBS      count(rekordy_pacjenta) z bazy + LIMIT.pacjent z operacji — dwie drogi
KOTWICE  —

⛔ CZĘŚĆ ZGODOWA — WSTRZYMANA (Q-16, właściciel / spotkanie G7):
         count(zapisane_zgody(X)) == ZGODY_Q16
         kandydaci: 0 (zgody zbiera pacjent przy płatności)
                    2 (psycholog potwierdza w imieniu — wymaga osobnej podstawy)
         NIE ZGADUJĘ. Do rozstrzygnięcia ta asercja nie wchodzi do suity.
```
> **Stan zmierzony, który czyni `NEG` istotnym** (`D-2026-08-09-07`): dziś *te same dane
> gościa dwa razy dają **dwa rekordy**, a jednoznaczność istnieje wyłącznie na
> `keycloak_sub`*. Bez tego `NEG` licznik limitu jest fikcją — a na wizytach niskopłatnych
> to **reguła, nie wyjątek**, że umawia psycholog.

---

## 7g · Grupa J — przypadki brzegowe z decyzji

**Wspólne przygotowanie grupy** (`fixtureJ()`): specjalista `S_J` z rytmem **pon–nd
09:00–14:00** (5 slotów: `09:00`…`13:00`). Rytm siedmiodniowy z tego samego powodu co
`fixtureH` — `J-03` i `J-04` badają **niedziele** przestawienia zegarów.

#### SZK-J-03 → `F2-J-03` · Rezygnacja w oknie bezpłatnym, doba 25-godzinna
```
ARRANGE  zegar := 2026-10-20 08:00 ; fixtureJ()
         R := REZERWACJA.utworz(pacjent, KONS_PELNA, 2026-10-25 12:00)   # niedziela, CET
         kwota_zamrozona(R) == 14500 ; zrzut z oknem 86 400 s
         # wizyta 12:00 CET == 11:00Z ; granica = 11:00Z − 24 h = 2026-10-24 11:00Z
         #                                       == 13:00 CEST czasu lokalnego SOBOTY
ACT      dla t ∈ [2026-10-24 12:59:59, 13:00:00, 13:00:01] CEST:
             zegar := t ; w[t] := REZERWACJA.odwolaj(R).zwrot_gr        # świeża kopia R
ASSERT   [w[t]] == [14500, 14500, 0]
         data_graniczna_prezentowana(R) == "24.10.2026, 13:00"
         count(różnych wartości daty granicznej w 3 miejscach prezentacji) == 1
         # spec wypisuje ją w potwierdzeniu, przypomnieniu i na karcie wizyty
NEG      zegar := 2026-10-24 12:30 CEST → zwrot_gr == 14500
         # implementacja „ta sama godzina dzień wcześniej" dałaby TU 0 —
         # granica wypadłaby o 12:00, a nie o 13:00
PERT     okno liczone przez „−1 dzień" na etykiecie lokalnej
         → [14500, 0, 0] oraz cztery różne stringi daty granicznej
         ALLOWLISTA (ścieżka pieniędzy)
OBS      zwrot_gr ×4 + trzy pola prezentacyjne — cztery drogi do jednej liczby
KOTWICE  KONF-OKNO-24H, KONF-STREFA, KONF-CENY
```
> **Serwer i ekran muszą podać TĘ SAMĄ godzinę.** Sam poprawny zwrot nie wystarcza:
> pacjent, któremu ekran obiecał 12:00, a serwer egzekwuje 13:00, dowiaduje się o różnicy
> **po** utracie pieniędzy. Dlatego prezentacja jest tu asercją, nie dopiskiem.

#### SZK-J-04 → `F2-J-04` · Ta sama rezygnacja, doba 23-godzinna
```
ARRANGE  zegar := 2026-03-24 08:00 ; fixtureJ()
         R := REZERWACJA.utworz(pacjent, KONS_PELNA, 2026-03-29 12:00)   # niedziela, CEST
         # wizyta 12:00 CEST == 10:00Z ; granica = 10:00Z − 24 h = 2026-03-28 10:00Z
         #                                       == 11:00 CET czasu lokalnego SOBOTY
ACT      dla t ∈ [2026-03-28 10:59:59, 11:00:00, 11:00:01] CET:
             zegar := t ; w[t] := REZERWACJA.odwolaj(R).zwrot_gr
ASSERT   [w[t]] == [14500, 14500, 0]
         data_graniczna_prezentowana(R) == "28.03.2026, 11:00"
         # GODZINĘ WCZEŚNIEJ niż godzina wizyty — i tak ma być napisane pacjentowi
NEG      zegar := 2026-03-28 11:30 CET → zwrot_gr == 0        # zostało 23,5 h
         # osoba odwołująca „dzień wcześniej o tej samej porze" PŁACI
PERT     jak SZK-J-03 ; ALLOWLISTA
OBS      zwrot_gr ×4 + pole prezentacyjne
KOTWICE  KONF-OKNO-24H, KONF-STREFA, KONF-CENY
```
> **`J-03` i `J-04` odchylają się w PRZECIWNE strony** — raz granica przesuwa się w przód,
> raz w tył. To jest argument, dla którego `Q-4` rozstrzygnięto na odczyt absolutny:
> odczyt „ta sama godzina" daje raz 25 h, raz 23 h, czyli **reguła zmieniałaby wartość
> dwa razy w roku**. Para tych szkieletów jest dowodem tej własności, nie ilustracją.
>
> **⚠ PRZEGLĄD 12.08 — `P-07` dotyczy również `SZK-J-04`.** `SZK-J-03` ma w `ACT`
> dopisek „świeża kopia `R`", `SZK-J-04` go **nie ma** — a odwołuje tę samą rezerwację
> trzy razy. **Świeża kopia na każdy z trzech pomiarów granicznych**, inaczej trzecia
> wartość `0` może pochodzić ze stanu „już odwołana", nie z okna.

#### SZK-J-05 → `F2-J-05` · Przełożenie: limit 2, płatność przechodzi, slot wraca od razu
```
ARRANGE  zegar := T0 ; fixtureS1()
         R := REZERWACJA.utworz(pacjent, KONS_PELNA, 2026-09-22 10:00)   # opłacona
         count(platnosci(R)) == 1
ACT      p1 := REZERWACJA.przeloz(R, 2026-09-22 11:00)
         p2 := REZERWACJA.przeloz(R, 2026-09-22 12:00)
         p3 := REZERWACJA.przeloz(R, 2026-09-23 09:00)
ASSERT   [p1, p2, p3] == [przyjęte, przyjęte, odrzucone(422)]
         licznik_przelozen(R) == 2
         count(platnosci(R)) == 1            # bez zwrotu i bez ponownego pobrania
         count(zadania_zwrotu(R)) == 0
         po p1: 10:00 ∈ wolne(2026-09-22) ORAZ 11:00 ∉ wolne(2026-09-22)
         count(wolne(2026-09-22)) == 3 przez cały czas
NEG      po wyczerpaniu limitu ODWOŁANIE nadal działa:
         REZERWACJA.odwolaj(R).zwrot_gr == 14500        # > 24 h do wizyty
         # limit przełożeń nie zamyka drogi wyjścia
PERT     przełożenie realizowane jako „odwołaj + zarezerwuj"
         → count(platnosci) == 2 ORAZ count(zadania_zwrotu) == 1
         ALLOWLISTA (ścieżka pieniędzy)
OBS      licznik i płatności z bazy + zbiór wolnych slotów przed i po
KOTWICE  KONF-DL-KONS, KONF-OKNO-24H, KONF-CENY
```
> **Sama liczba wolnych slotów nie odróżnia przełożenia od bezczynności** — przed i po
> jest `3`. Dlatego asercja pyta o **zbiór**: stary termin ma wrócić, nowy zniknąć.

#### SZK-J-06 → `F2-J-06` · Przełożenie tylko w oknie 24 h — egzekwowane w API
```
ARRANGE  zegar := 2026-09-21 10:00 ; fixtureS1()
         RA := rezerwacja na 2026-09-22 09:00      # 23 h do wizyty
         RB := rezerwacja na 2026-09-22 11:00      # 25 h do wizyty
ACT      a := REZERWACJA.przeloz(RA, <inny termin>)
         b := REZERWACJA.przeloz(RB, <inny termin>)
ASSERT   a == odrzucone(422) ; licznik_przelozen(RA) == 0 ; termin(RA) niezmieniony
         b == przyjęte       ; licznik_przelozen(RB) == 1
NEG      REZERWACJA.odwolaj(RA).zwrot_gr == 0
         # zamknięte okno to ROZSTRZYGNIĘCIE (zwrot 0), nie błąd operacji
PERT     kontrola wyłącznie w warstwie prezentacji → a == przyjęte
         (spec M1/13: „reguła egzekwowana wyłącznie w interfejsie jest do obejścia
          zapytaniem do API — przy polityce, która decyduje o pieniądzach, to otwarta furtka")
OBS      licznik i termin Z BAZY, nie z odpowiedzi operacji
KOTWICE  KONF-OKNO-24H, KONF-CENY
```

#### SZK-J-07 → `F2-J-07` · Wniosek o zwolnienie z opłaty blokuje termin do decyzji
```
ARRANGE  zegar := T0 ; fixtureS1()
ACT      WNIOSEK.zloz(przez: PSYCHOLOG, S1, KONS_NISKA, 2026-09-22 10:00,
                      uzasadnienie: <finansowe>)
ASSERT   count(wolne(2026-09-22)) == 3                    # termin zablokowany
         trzymane_do == BRAK WARTOŚCI                     # „do czasu decyzji", nie zegar
         count(linki_platnosci wysłane do pacjenta) == 0
         # spec s. 10: „pacjent nie dostaje w tym momencie żadnego linku ani informacji"
POZ-2    po ZGODZIE koordynatora:
         count(rezerwacje) == 1
         kwota_zaplacona_przez_pacjenta == 0
         count(wywołań operatora płatności) == 0
         oznaczenie „pokryta ze środków fundacji" == obecne
         count(wpisy_dziennika_decyzji) == 1
NEG      po ODMOWIE koordynatora (Q-17, rekomendacja):
         count(wolne(2026-09-22)) == 4                    # blokada zwolniona
         count(linki_platnosci) == 0                      # link to OSOBNA decyzja
PERT     wniosek nieblokujący terminu → count(wolne) == 4 od razu po złożeniu
OBS      SLOTY + liczba wysyłek ze śladu + count wpisów dziennika (F5, granica faz)
KOTWICE  KONF-DL-KONS
```
> **⚠ `Q-22` — NOWE, wyszło przy tym szkielecie.** Wizyta zwolniona z opłaty ma
> `kwota_zaplacona == 0`, ale **czym jest wtedy `kwota_zamrozona`?**
> Kandydaci: **`5500`** (cena usługi — raport grantowy liczy dopłatę fundacji z cennika
> z dnia wizyty, spec M4/8) albo **`0`** (tyle, ile pacjent zapłacił).
> Rozstrzyga **dwie** liczby: zwrot przy późniejszym odwołaniu (`0` w obu odczytach, ale
> z różnych powodów) oraz **kwotę dopłaty w sprawozdaniu z dotacji**.
> **Rekomendacja: `5500`** — `kwota_zamrozona` opisuje **wartość usługi**, a nie przelew;
> przy `0` fundacja traci w raporcie ślad po własnym wkładzie. Piszę wg rekomendacji.

#### SZK-J-08 → `F2-J-08` · Usługa 0 zł omija operatora płatności
```
ARRANGE  zegar := T0 ; fixtureS1()
ACT      r := REZERWACJA.utworz(pacjent, ASYSTENT, 2026-09-22 15:00)     # 0 zł
ASSERT   count(rezerwacje potwierdzone natychmiast) == 1
         count(wywołań operatora płatności)          == 0
         count(blokad przejściowych)                  == 0
         kwota_zamrozona(r) == 0        # pole ISTNIEJE i ma wartość 0, nie NULL
NEG      rezerwacja KONS_PELNA → count(wywołań operatora) == 1
                                  count(blokad przejściowych) == 1
PERT     ścieżka 0 zł prowadzona przez operatora → count(wywołań) == 1
OBS      liczba wywołań atrapy operatora + kwota_zamrozona z bazy
KOTWICE  KONF-CENY
```
> **`J-07` i `J-08` różnią się rzeczą, którą łatwo skleić:** tu cena usługi **jest** zerem,
> tam cena jest niezerowa, a **zeruje się przelew**. Implementacja, która utożsamia
> „pacjent płaci 0" z `kwota_zamrozona == 0`, przechodzi `J-08` i psuje sprawozdanie
> z dotacji (`Q-22`). Dlatego obie asercje stoją obok siebie w dwóch szkieletach.
>
> **⚠ PRZEGLĄD 12.08 — `Q-23`, NOWE PYTANIE. Nie zgaduję.**
> W `fixtureS1()` **asystent zdrowienia siedzi w kategorii `NISKOPLATNE`** — a limit
> „10 wizyt niskopłatnych na pacjenta" liczy… właśnie co? Wszystkie usługi tej kategorii,
> czy **wyłącznie konsultacje niskopłatne (55 zł)**?
> **Skutek jest realny:** przy szerszym odczycie pacjent korzystający z **bezpłatnego**
> asystenta zdrowienia **zużywa pulę dofinansowanej terapii** — czyli darmowa usługa
> odbiera dostęp do płatnej-dofinansowanej. To ta sama rodzina co `D-2026-08-09-08`
> („gdyby licznik liczył wszystko, odciąłby ludzi płacących pełną stawkę"), tylko od
> drugiej strony.
> **Rekomendacja: limit liczy wyłącznie konsultacje niskopłatne.** Asystent zdrowienia
> jest osobną usługą, bezpłatną i bezprowizyjną — nie jest wizytą, której pula dotyczy.
> **Do rozstrzygnięcia:** czy kategoria `fundacja/komercja` (`CLAUDE.md` §3) jest tym
> samym podziałem, co „wizyta niskopłatna" w liczniku. Dziś w moim fixture **jest** —
> i to może być mój błąd modelowania, nie tylko pytanie o regułę.

---

## 8 · Czego w tym dokumencie nie ma

| co | dlaczego | warunek znoszący |
|---|---|---|
| część **zgodowa** `SZK-J-02` | `Q-16` u właściciela (spotkanie G7); kandydaci `0` i `2` zapisanych zgód | rozstrzygnięcie właściciela |
| grupa `K` (kontrole nad kontrolami) | mierzą **zbiór szkieletów**, więc mają sens dopiero, gdy zbiór jest zamknięty — dziś rósłby pod nimi. **Zbiór domyka dopiero grupa `L`**, a ta czeka na kontrakt | kontrakt API → `L` → `K` |
| grupa `L` (wydajność) | mierzy **czas i liczbę zapytań**, czyli własności implementacji, nie kontraktu; szkielet bez kontraktu byłby zgadywaniem instrumentacji | kontrakt API + seed `S111` od KOD-SILNIK |
| **kotwice 11 pozostałych parametrów** | kotwica bez przypadku, który jej używa, jest deklaracją. Po tej rundzie brakuje ich do `C`, `D`, `F` — **wypisane niżej**, nie przemilczane | etap B, razem z pierwszym testem grupy |
| kod w `tests/` | nadal **etap A**; `tests/` otwiera się po merge F1 | merge F1 do `main` |
| `fixtureS111()` w szczegółach | proporcje seeda (111 specjalistów, kilkanaście wizyt na pacjenta) należą do F1/F2 po stronie KOD-SILNIK; tu wołam go po nazwie | seed dostarczony razem z kontraktem |

**Kotwice brakujące po tej rundzie** — parametry, których szkielety już używają, a które
nie mają jeszcze wpisu w §2 (dopisuję je w etapie B, razem z pierwszym testem grupy):
`min_wyprzedzenie_h` (2) · `horyzont_pacjenta_dni` (30) · `horyzont_wystawiania_dni` (7) ·
`blokada_koszyka_min` (10) · `blokada_wstepna_min` (10) · `waznosc_linku_platnosci_h` (48) ·
`okno_po_otwarciu_linku_min` (10) · `margines_przed_wizyta_h` (2) ·
`limit_rownoczesnych_blokad` (2) · `limit_niskoplatnych_wizyt` (10) ·
`limit_niskoplatnych_na_tydzien` (4).

**Odnotowuję uczciwie:** to jest **dług**, nie decyzja. Szkielety `C`, `D`, `F` mają dziś
literały w `ASSERT` (jak należy) i **żadnej kotwicy**, która nazwałaby przyczynę czerwieni,
gdyby parametr się rozjechał. Do etapu B zostaje 11 pozycji, wypisanych co do jednej —
żeby „kotwice są" nie znaczyło „kotwice są dla sześciu parametrów z siedemnastu".

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

---

## 10 · Przegląd adwersarialny — wynik

**Zlecony w `ODPOWIEDZ-053` §3.** Rama: **jedno pytanie na szkielet** — *czy ten szkielet
przechodzi także wtedy, gdy reguła nie działa?* Poprawki **dopiskiem przy oryginale**,
nigdy cichą podmianą.

**Przejrzano: 68/68. Znalezisk: 14. Nowych pytań: 1 (`Q-23`).**

**Licznik podaję także po to, żeby zero nie było domyślne.** Gdyby wyszło zero, byłby to
sygnał o przeglądzie, nie o szkieletach — autor przeglądający własną pracę ma tendencję
do potwierdzania jej, a `WYTYCZNE-PRACY.md` mówi wprost, że **atrybucji wygodnej nie obali
ten, komu ona służy**.

| # | szkielety | co było źle | klasa |
|---|---|---|---|
| `P-01` | `A-03` | `NEG` spełniony także przez „odrzuca każdą drugą poprawkę" — odmowa z niewłaściwej przyczyny | odmowa nieatrybuowana |
| `P-02` | `A-04` | zakres urlopu zachodzi na przeszłość; część zera pochodzi od zegara, świadek mierzy inny dzień | data vs zegar |
| `P-03` | `A-07` | **wartość oczekiwana nieprawdziwa** — `4` dla dnia minionego; fałszywa czerwień na sprawnym silniku | data vs zegar |
| `P-04` | `A-08` | `ARRANGE` niewykonalny — wizyta 1 h po zegarze łamie regułę 2 h | data vs zegar |
| `P-05` | `B-01` | `NEG` zmienia parametr, który **buduje raster** — przeczy własnej przesłance | parametr o dwóch rolach |
| `P-06` | `B-02` | **perturbacja martwa** — „ADHD jako 60 min" daje ten sam wynik, test nie zapala się | brak dowodu czerwieni |
| `P-07` | `E-01`, `E-02`, `J-04` | trzy pomiary graniczne na **tej samej** rezerwacji; `0` może pochodzić z „już odwołana" | dziedziczenie stanu |
| `P-08` | `G-01` ↔ `D-06` | **moment zamrożenia kwoty opisany dwa razy, rozbieżnie** (blokada vs utworzenie) | `P3` — dwa opisy jednej rzeczy |
| `P-09` | `G-02` | `ARRANGE` niewykonalny — jeden zegar nie da 30 h do dwóch wizyt odległych o godzinę | opis zamiast wartości |
| `P-10` | `G-04` | `ASSERT` powołuje `otwarcie_linku`, którego `ARRANGE` nie wykonuje | asercja o zdarzeniu, którego nie było |
| `P-11` | `G-05` | `300` miesza **sloty oferowane** z **minutami zajętymi** | dwie wielkości w jednej liczbie |
| `P-12` | `H-01` | slot `09:00` tego dnia nie istnieje — obcięty regułą 2 h | data vs zegar |
| `P-13` | `H-07` | `b == 24` nieprawdziwe z tego samego powodu — wyszłoby 14 | data vs zegar |
| `P-14` | `F-01`, `F-04`, `F-05`, `F-07`, `F-08` | terminy nieprzypięte, odmowa bez asercji **przyczyny** — spełnione przez „brak wolnego slotu" | odmowa nieatrybuowana |

**Szkieletów ze znaleziskiem: 21 z 68. Czystych: 47.** Do tego `SZK-J-08` niesie **nowe
pytanie `Q-23`**, które nie jest znaleziskiem w szkielecie, tylko luką w regule — dlatego
liczę je osobno, a nie doliczam do 21.

**Rozbicie, bo suma bez rozbicia nie jest dowodem:**
`A-03 · A-04 · A-07 · A-08 · B-01 · B-02 · E-01 · E-02 · G-01 · G-02 · G-04 · G-05 ·
H-01 · H-07 · F-01 · F-04 · F-05 · F-07 · F-08 · D-06 · J-04` = **21**.

*(Sprostowanie w trakcie pisania tej sekcji: napisałem najpierw „20 dotkniętych, 48
czystych" — przeliczenie z rozbicia dało **21 i 47**. Pomyliłem się, bo `P-08` i `P-14`
obejmują po kilka szkieletów, a liczyłem znaleziska zamiast pozycji. Zostawiam ślad,
bo to ta sama klasa, którą ten przegląd tropi: liczba, która brzmi sensownie.)*

### Trzy wnioski, nie czternaście

1. **„Data vs zegar" to pięć z czternastu** (`P-02`, `P-03`, `P-04`, `P-12`, `P-13`) —
   jedna klasa, nie pięć pomyłek. Data wyglądała poprawnie **w kalendarzu** (dobry dzień
   tygodnia, właściwy rytm) i była zła **względem zegara przypadku**. Dlatego wynikiem
   przeglądu jest **reguła wejściowa w §1**, a nie pięć poprawek.
2. **Odmowa bez przyczyny to druga klasa** (`P-01`, `P-14`, 6 szkieletów). `SZK-F-10`
   robił to poprawnie od początku — **miałem własny wzorzec i nie zastosowałem go
   do reszty grupy**. To jest dokładnie ta sama rodzina co „milcząca czerwień"
   w perturbacjach (`D-2026-08-07-22`).
3. **Jedna perturbacja była martwa** (`P-06`). Sprawdziłem **rachunkiem** wszystkie
   pozostałe `PERT` w grupach A, B, E, G, I, H, C, D, F, J — reszta zapala.
   Martwa mutacja melduje sukces, nie zmieniwszy niczego; to najgorszy możliwy wynik
   kontroli (`D-2026-08-07-18`, U-2/U-3).

### Ograniczenie tego przeglądu — wpisane, nie przemilczane

**To jest przegląd AUTORA.** Nie zastępuje niezależnego wykonania w etapie B i nie ma
prawa być cytowany jako „szkielety zweryfikowane". Znalazłem 14 rzeczy w **swojej własnej**
pracy, więc znalazłem te, które umiem zobaczyć. Klasy, których nie umiem zobaczyć,
zostają — i to jest powód, dla którego etap B pisze się **przeciw kontraktowi**,
a rundę weryfikacyjną prowadzi ktoś, kto tego nie pisał (`WYTYCZNE-PRACY.md` §2).
