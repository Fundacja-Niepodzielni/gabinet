# PLAN TESTÓW F2 — silnik dostępności i rezerwacji

**Kto:** sesja TESTY (etap A orkiestracji) · **Kiedy:** 12.08.2026
**Gałąź:** `testy-plan-f2` · **Baza:** `71cd8a5` (`faza-1-retencja`)
**Status:** plan, nie kod. **Zero zmian w `backend/`, zero w `tests/`.**

> **Aktualizacja 12.08, po `ODPOWIEDZ-045` i `ZLECENIE-049`.** Rozstrzygnięte **9 z 10**
> pytań blokujących (§8.1) i rozjazd `R-1` (§1.2). Dopisany `B-05` — przypadek, który
> wcześniej nie mógł istnieć. Dopisane §8.3: **„test ma czytać konfigurację" wymaga
> zastrzeżenia**, inaczej wynik jedzie tą samą drogą co wejście.
> Szkielety wykonawcze grup A, B, E, G, I: [`SZKIELETY-F2.md`](SZKIELETY-F2.md).
> **Nic nie kasuję** — pytania zostają widoczne pod swoimi numerami, ze znacznikiem
> rozstrzygnięcia, bo cicha podmiana nie dociera do tego, kto zdążył przeczytać.

---

## 0 · Czym ten plan jest i czym NIE jest

Plan jest wyprowadzony **ze specyfikacji i z rejestru decyzji**, nie z implementacji.
Silnika F2 nie ma — a gdyby był, i tak bym go tu nie czytał. **Test napisany z kodu
potwierdza kod; test napisany ze specyfikacji potwierdza system.** Różnica ujawnia się
dokładnie tam, gdzie kod robi coś sensownego i niezgodnego z umową — czyli w miejscu,
którego test-z-kodu nigdy nie zobaczy.

**Czego tu nie ma i dlaczego:**

- **Nie ma nazw klas, metod ani tras.** Plan wiąże się z **operacjami kontraktowymi**
  (§4), nie z URL-ami. Kontrakt API F2 ustala sesja KOD-SILNIK; do czasu jego powstania
  wiązanie planu z wymyślonym przeze mnie URL-em byłoby zgadywaniem, które w etapie B
  trzeba by przepisać w całości.
- **Nie ma przypadków „element widoczny".** `CLAUDE.md` §15: 3 z 32 błędów makiety
  wyglądały na ekranie poprawnie. **Każdy przypadek kończy się liczbą** — liczbą slotów,
  kwotą w groszach, liczbą rezerwacji, liczbą odrzuceń, liczbą milisekund.
- **Nie ma przypadków bez kontroli negatywnej.** Kontrola pozytywna łapie przyrząd
  **martwy** („zwrócił 0, bo baza pusta"), negatywna łapie przyrząd **mierzący co innego**
  („zwrócił 0 zawsze"). Dwa razy 09.08 druga złapała to, czego pierwsza nie mogła
  (`ZAMKNIECIE-DNIA-2026-08-09.md` §6.1).

**Definicja gotowości tego dokumentu:** każda reguła F2 wymieniona w zakresie
specyfikacji ma co najmniej jeden przypadek (§6 — macierz pokrycia), każdy przypadek ma
wynik liczbowy i obie kontrole, a to, czego plan nie pokrywa, jest wypisane z powodem (§7).

---

## 1 · Hierarchia rozstrzygania i rejestr rozjazdów

### 1.1 Co wygrywa z czym

| poziom | źródło | uwaga |
|---|---|---|
| 1 | `CLAUDE.md` — zasady twarde | nie relitygujemy |
| 2 | `docs/DECYZJE.md` + rozstrzygnięcia właściciela (`../DECYZJE-DO-PODJECIA.md`, „ROZSTRZYGNIĘTE") | **nowsza decyzja właściciela bije specyfikację PDF** |
| 3 | specyfikacja (`01-jak-dziala-system`, `02-zakres-wdrozenia`) | rozstrzyga wszystko, czego nie rozstrzygają 1–2 |
| 4 | dziennik makiety (`05-DECYZJE-makiety.md`) | źródło prawdy o regułach, przy sprzeczności przegrywa z `CLAUDE.md` |

**Rejestr rozjazdów prowadzi sesja SPEC-UMOWA.** Poniżej wyłącznie te, na które
natknąłem się pisząc ten plan — do przekazania, nie do rozstrzygnięcia u siebie.

### 1.2 Rozjazdy zmierzone przy pisaniu planu

| # | rzecz | wersje, które istnieją | co przyjmuję i dlaczego |
|---|---|---|---|
| **R-1** | blokada slotu przy rezerwacji **własnej** | spec: **10 min** · właściciel 09.08 wieczorem: **„~godzina"** · `D-2026-08-09-08` tabela: **10 min** · prompt architekta 12.08: **10 min** | **✅ ZAMKNIĘTE 12.08 (`ZLECENIE-049`): 10 min.** Właściciel zapytany wprost **wycofał** własne zdanie o „~godzinie". Plan pisany był na 10 min — **nic do zmiany**. Odnotowuję dla porządku, że rozstrzygnięcie przyszło **po** zgłoszeniu rozjazdu, a nie przez cichy wybór jednej z wersji. |
| **R-2** | blokada slotu przy umawianiu przez **psychologa** | spec + `D-2026-08-09-15`: **2 dni + 10 min od otwarcia linku** · `D-2026-08-09-08`: **48 h, drugi stopień usunięty** · makieta `Grafik.tsx`: **24 h** · `DECYZJE-DO-PODJECIA` pyta „24 czy 48" | **2 dni + 10 min od otwarcia linku** (`D-2026-08-09-15` prostuje `D-2026-08-09-08`). „2 dni" i „48 h" to **nie ta sama liczba przy zmianie czasu** — patrz `Q-19`. |
| **R-3** | weryfikacja numeru kodem | właściciel wieczorem: **przy KAŻDEJ rezerwacji** · właściciel w nocy + `D-2026-08-09-08`: **RAZ** | **RAZ.** Nowsza. Obronę przed zamrażaniem grafiku przejmuje limit równoczesnych blokad (`D-07`). |
| **R-4** | rezerwacja jako gość | `CLAUDE.md` §2: **guest checkout bez konta = zasada twarda** · właściciel w nocy: **konto zamiast gościa, bez hasła, kod jednorazowy** (`D-2026-08-09-10`, wykonalność **niepotwierdzona**) | **Nie rozstrzygam.** Wpływa na tożsamość pacjenta w liczniku limitu (grupa F) i na `J-02`. Przypadki grupy F pisane są przeciwko **pacjentowi jako bytowi**, nie przeciwko sposobowi jego uwierzytelnienia — dzięki temu przeżyją obie wersje. |
| **R-5** | limit **10 vs 4** | spec cytuje własne `DECYZJE.md` mówiące 4 | **Zamknięte** przez `D-2026-08-09-05`: to **dwa rozłączne limity** (10 na pacjenta sumarycznie, 4/tydzień na specjalistę). Nie ma tu pomyłki do usunięcia. Grupa F testuje **rozłączność** wprost (`F-10`). |
| **R-6** | kredyt za odsprzedany termin | spec: jest, z przełącznikiem | **Poza zakresem** (`D-2026-08-09-01`, `ND-01`). **Zero przypadków — świadomie.** Nie zgłaszać jako luki w pokryciu. |
| **R-7** | okno 24 h zegarowo vs dni robocze | spec: **„otwarte, decyzja regulaminowa"** | **Zamknięte** przez `D-2026-08-09-06`: zegarowo, **razem z przypomnieniem 48 h**. `E-02` dowodzi zegarowo liczbą, nie deklaracją. |
| **R-8** | miejsce egzekwowania limitu 4/tydzień | spec: **przy układaniu grafiku, NIE przy rezerwacji** · `ZLECENIE-043` §5: do sprawdzenia | Przyjmuję spec. `F-08` jest testem **miejsca**, nie wartości — i pada, gdy ktoś przeniesie sprawdzenie do rezerwacji. |

---

## 2 · Konwencje planu

**K-1 · Okna są domknięte od strony uprawnienia.** „Masz 10 minut" znaczy, że akcja
o `t0+10:00.000` jeszcze przechodzi, a `t0+10:00.001` już nie. „Do 24 h przed" znaczy,
że dokładnie 24 h **to jeszcze okno bezpłatne**. Jedna konwencja dla wszystkich okien,
bo dwie konwencje w jednym systemie rozjadą się po cichu. **To jest moja decyzja, nie
właściciela** — `Q-7`.

**K-2 · Każda granica ma trzy wartości.** Sekunda przed · dokładnie · sekunda po
(`D-2026-08-09-06`: „trzy wartości, trzy rozstrzygnięcia, żadnego »mniej więcej«").
Perturbacja `testy` już dziś zamienia `>=` na `>` w oknie 24 h — testy graniczne muszą
na to reagować.

**K-3 · Kontrola patrzy inną drogą niż mechanizm (anty-`C1`).** Każdy przypadek ma
wypisaną **ścieżkę obserwacji**. Gdy operacja idzie przez API, wynik czytamy z bazy
**osobnym zapytaniem** albo ze śladu zdarzeń — nigdy z wartości zwróconej przez samą
operację, jeśli to ona jest badanym mechanizmem. Trzy pytania przy każdej kontroli
(`D-2026-08-08-25`): czy patrzę inną drogą · czy pytam o inny klucz · czy sam nie
wyprodukowałem stanu, o który pytam.

**K-4 · Zero bez świadka nie liczy się.** Każdy przypadek oczekujący `0` niesie asercję
„miałem czego szukać" — zbiór bazowy jest niepusty i ma znaną liczność. Bez tego
`0` przechodzi także przy pustej bazie (`U-1`, klasa „brak dopasowania daje wynik pozytywny").

**K-5 · Czerwień musi mieć przyczynę, nie tylko kod wyjścia.** Dla grup dotykających
pieniędzy i zamrażania (**D-06, E, G**) perturbacja używa **allowlisty** — czerwień musi
zawierać dosłowny fragment komunikatu kontroli, skopiowany, **nie przepisany z pamięci**
(`WYTYCZNE-PRACY.md`: wzorzec „zamrożon" wobec komunikatu „ZAMROŻONĄ"). Reszta zostaje
na denyliście i **mówimy to wprost**, zamiast udawać kompletność.

**K-6 · Zegar jest wejściem, nie otoczeniem.** Żaden przypadek nie czyta zegara systemu.
Czas jest podawany jawnie; test, który przechodzi tylko we wtorek, nie jest testem.

**K-7 · Testy liczą w groszach.** Kwoty jako liczby całkowite groszy (`14500`), nigdy
jako liczby zmiennoprzecinkowe złotych.

---

## 3 · Ustalone dane wejściowe

### 3.1 Zegar odniesienia i kalendarz

| symbol | wartość | uwaga |
|---|---|---|
| `T0` | **2026-09-15 08:00:00 Europe/Warsaw** (wtorek) | CEST = UTC+2 → `06:00:00Z` |
| tydzień ISO | **2026-09-14 (pon) … 2026-09-20 (nd) = W38**; `2026-09-21` = W39 | zweryfikowane wobec ISO-8601 (1.01.2026 = czwartek) |
| doba **23-godzinna** | **2026-03-29** (ostatnia niedziela marca), 02:00 CET → 03:00 CEST | godzina 02:00 **nie istnieje** |
| doba **25-godzinna** | **2026-10-25** (ostatnia niedziela października), 03:00 CEST → 02:00 CET | godzina 02:00 **występuje dwa razy** |
| horyzont 30 dni od `T0` | ostatni dzień **2026-10-15** (czwartek) | |

### 3.2 Fixture `S1` — jeden specjalista, stan bazowy

| element | wartość |
|---|---|
| strefa specjalisty | Europe/Warsaw |
| rytm „pełnopłatne" | **pon–pt 09:00–13:00** |
| rytm „niskopłatne" | **wt 15:00–17:00** |
| usługi | konsultacja pełnopłatna **50 min / 14500 gr** · konsultacja niskopłatna **50 min / 5500 gr** · diagnoza ADHD **90 min / 35000 gr** (uprawnienie nadane) · asystent zdrowienia **50 min / 0 gr** |
| bufor | **10 min** (raster: 50+10 = **60 min**, 90+10 = **100 min**) |
| urlopy / poprawki | **brak** w stanie bazowym |

**Liczby pochodne stanu bazowego** (to są wartości, wobec których liczą wszystkie kontrole
negatywne grup A–C):

| zapytanie | wynik |
|---|---|
| konsultacja pełnopłatna, wtorek 22.09 | **4 sloty** — `09:00, 10:00, 11:00, 12:00` |
| diagnoza ADHD, wtorek 22.09 (ten sam zakres!) | **2 sloty** — `09:00, 10:40` |
| konsultacja niskopłatna, wtorek 22.09 | **2 sloty** — `15:00, 16:00` |
| konsultacja pełnopłatna, tydzień W39 (pon–pt) | **20 slotów** |
| konsultacja pełnopłatna, sobota / niedziela | **0 slotów** |

> **Ta jedna tabela jest już testem.** Ten sam zakres `09:00–13:00` daje **4** albo **2**
> sloty zależnie od długości usługi. Implementacja, która materializuje „sloty dnia"
> bez usługi w kluczu, nie umie oddać obu liczb naraz.

### 3.3 Fixture `S111` — wydajność

111 specjalistów (`spec: 111 osób`), rytmy o wiarygodnych proporcjach, kilkanaście wizyt
na pacjenta, 30 dni w przód. Seed **musi** mieć realistyczne proporcje — dziennik makiety
rozdz. 15: dane bez wiarygodnych proporcji nie pokazują reguły, którą ilustrują.

---

## 4 · Operacje kontraktowe

Plan wiąże się z **operacjami**, nie z trasami. Nazwy tras poda KOD-SILNIK; wtedy etap B
podstawia je w jednym miejscu.

| operacja | wejście | wyjście, na którym liczą testy |
|---|---|---|
| `SLOTY` | specjalista, usługa, zakres dat, konsument (panel / wyszukiwarka / grafik) | lista slotów: **start UTC**, **start lokalny**, długość, usługa |
| `RYTM.zapisz` | specjalista, kategoria, dzień tygodnia, zakres godzin | przyjęte / odrzucone + **powód** + **wskazanie konkretnego terminu**, który nie przeszedł |
| `POPRAWKA.zapisz` | specjalista, data, godzina, typ (`wyłącz` / `dodaj`) | jw. |
| `URLOP.zapisz` | specjalista, zakres dat | jw. + **liczba wizyt do przełożenia** |
| `BLOKADA.zaloz` | specjalista, termin, usługa, ścieżka (`własna` / `psycholog` / `koordynator`), pacjent | identyfikator blokady + **`trzymane_do` (UTC)** |
| `BLOKADA.potwierdz` | identyfikator | nowe `trzymane_do` |
| `LINK.otworz` | token | nowe `trzymane_do` |
| `REZERWACJA.utworz` | blokada, potwierdzenie płatności | rezerwacja + **`kwota_zamrozona`** + **`regula_anulacji_zamrozona`** (pełny zrzut) |
| `REZERWACJA.odwolaj` | rezerwacja, moment | **`zwrot_gr`**, `termin_wrocil_do_puli`, `godzina_platna_dla_specjalisty` |
| `REZERWACJA.przeloz` | rezerwacja, nowy termin | przyjęte / odrzucone + **licznik przełożeń** |
| `LIMIT.pacjent` | pacjent | `wykorzystane`, `limit`, `pozostale` |
| `LIMIT.specjalista` | specjalista, tydzień ISO | `wystawione`, `limit` |

---

## 5 · Przypadki

Format: **wejście → wynik liczbowy**, kontrola pozytywna, kontrola negatywna,
perturbacja (dowód, że kontrola umie zaświecić), ścieżka obserwacji (anty-`C1`).

---

### A · Trzy warstwy dostępności: rytm → poprawki → urlopy

> `CLAUDE.md` §5 · spec M1/2, M2/2 („**najbardziej niedoszacowywany element całego
> projektu**") · cytat spec, s. 36: *„Wpisane wolne […] wyłącza terminy we wszystkich
> usługach naraz i ma pierwszeństwo zarówno przed tygodniowym rytmem, jak i przed
> pojedynczymi poprawkami w siatce godzin."*

#### F2-A-01 · Sam rytm rozwija się na raster 60 min
- **Wejście:** `S1` bazowy; `SLOTY(konsultacja pełnopłatna, 2026-09-22)`.
- **Wynik:** liczba slotów = **4**; starty lokalne dokładnie `[09:00, 10:00, 11:00, 12:00]`;
  ostatni slot zajmuje `12:00–13:00` (50 wizyta + 10 bufor).
- **Poz.:** zbiór startów **równy** oczekiwanemu (nie „zawiera").
- **Neg.:** `SLOTY(…, 2026-09-19)` (sobota, brak rytmu) = **0**, przy niepustym `S1` (K-4).
- **Pert.:** raster zmieniony na 50 min → starty `[09:00, 09:50, 10:40, 11:30]`, test czerwony.
- **Obserwacja:** odpowiedź operacji `SLOTY`; **kontrola krzyżowa** — suma zajętego czasu
  liczona z bazy (4 × 60 min = 240 min = długość zakresu).

#### F2-A-02 · Poprawka wyłączająca jest JEDNORAZOWA (nie mutuje rytmu)
- **Wejście:** `POPRAWKA.zapisz(wyłącz, 2026-09-22 11:00)`.
- **Wynik:** `SLOTY(2026-09-22)` = **3**, starty `[09:00, 10:00, 12:00]`;
  `SLOTY(2026-09-29)` = **4** — rytm nietknięty.
- **Poz.:** 3 i 4 w jednym przebiegu, obie liczby asertowane.
- **Neg.:** liczba rekordów rytmu przed i po zapisie poprawki = **1 i 1** (poprawka nie
  rozbiła rytmu na rekordy — `CLAUDE.md` §5: „poprawka to osobny byt").
- **Pert.:** poprawka zapisywana przez modyfikację rytmu → `SLOTY(2026-09-29)` = 3, czerwony.
- **Obserwacja:** liczba wierszy w tabeli rytmów **osobnym zapytaniem**, nie przez `SLOTY`.

#### F2-A-03 · Poprawka dodająca godzinę spoza rytmu
- **Wejście:** `POPRAWKA.zapisz(dodaj, 2026-09-16 18:00)` (środa — rytm jest, ale nie o 18:00).
- **Wynik:** `SLOTY(2026-09-16)` = **5** (4 z rytmu + 1 dodany); start `18:00` obecny;
  `SLOTY(2026-09-23)` = **4**.
- **Poz.:** 5 i 4.
- **Neg.:** `POPRAWKA.zapisz(dodaj, 2026-09-16 12:30)` — godzina kolidująca z rytmem
  przez bufor (slot `12:00` zajmuje `12:00–13:00`) → **odrzucona**; na stanie **po**
  powyższym `arrange`: `SLOTY(16.09)` **bez zmian = 5**, liczba poprawek **bez zmian = 1**.
  *(Sprostowanie 12.08: pierwsza wersja mówiła „`SLOTY` = 4, liczba poprawek = 1", co jest
  prawdą tylko na świeżym fixture i nie da się mieć obu naraz. Stan przypięty przy pisaniu
  `SZK-A-03`.)*
- **Pert.:** kontrola kolizji zdjęta → 6 slotów i dwie wizyty w odstępie 30 min, czerwony.
- **Obserwacja:** `SLOTY` + niezależne zapytanie o liczbę poprawek.

#### F2-A-04 · Urlop wygrywa z rytmem, we WSZYSTKICH usługach naraz
- **Wejście:** `URLOP.zapisz(2026-09-14 … 2026-09-18)`.
- **Wynik:** `SLOTY` w oknie 14–18.09 = **0** dla każdej z **4** usług (cztery zapytania,
  cztery zera); `SLOTY(konsultacja pełnopłatna, 21–25.09)` = **20**.
- **Poz.:** 20 (K-4 — świadek, że silnik w ogóle produkuje sloty).
- **Neg.:** te same 4 zapytania **bez urlopu**, wtorek `2026-09-22` =
  **`4 · 2 · 2 · 2`** (konsultacja pełnopłatna · ADHD · konsultacja niskopłatna ·
  asystent zdrowienia) — cztery liczby różne od zera.
  *(Sprostowanie 12.08: pierwsza wersja mówiła `4, 4, 2, 2`, co przeczyło §3.2 — ADHD
  w zakresie `09:00–13:00` daje **2** sloty po 100 min, nie 4. Złapane przy `SZK-A-04`.)*
- **Pert.:** urlop zastosowany tylko do kategorii, w której go wpisano → jedna z czterech
  liczb ≠ 0, czerwony.
- **Obserwacja:** cztery osobne zapytania, każde po innej usłudze.

#### F2-A-05 · Urlop wygrywa również z POPRAWKĄ
- **Wejście:** `POPRAWKA.zapisz(dodaj, 2026-09-16 18:00)`, następnie
  `URLOP.zapisz(2026-09-14 … 2026-09-18)`.
- **Wynik:** `SLOTY(2026-09-16)` = **0** (mimo dodanej ręcznie godziny).
- **Poz.:** ta sama para operacji **w odwrotnej kolejności** (najpierw urlop, potem
  poprawka) → też **0**. Warstwy rozstrzyga **pierwszeństwo**, nie kolejność zapisu.
- **Neg.:** ten sam zestaw **bez urlopu** = **5**.
- **Pert.:** złożenie warstw zamienione na sumę zbiorów → 1, czerwony.
- **Obserwacja:** `SLOTY` w obu kolejnościach + liczba poprawek nadal **1** (urlop nie
  kasuje poprawki, tylko ją przykrywa — po urlopie poprawka wraca do gry).

#### F2-A-06 · Poprawka wyłączająca godzinę, której w rytmie nie ma
- **Wejście:** `POPRAWKA.zapisz(wyłącz, 2026-09-22 20:00)`.
- **Wynik:** operacja **przyjęta lub odrzucona jednoznacznie** (bez cichego pominięcia);
  `SLOTY(2026-09-22)` = **4** — bez zmian.
- **Poz.:** 4.
- **Neg.:** `POPRAWKA.zapisz(wyłącz, 2026-09-22 10:00)` → **3**. Ta sama operacja na
  godzinie istniejącej **musi** zmienić liczbę — inaczej `A-06` przechodzi także wtedy,
  gdy poprawki wyłączające nie działają w ogóle.
- **Pert.:** wyłączanie nieistniejącej godziny rzuca wyjątek 500 → czerwony (błąd nie jest
  rozstrzygnięciem).
- **Obserwacja:** kod odpowiedzi + `SLOTY`.

#### F2-A-07 · Granice urlopu są domknięte po obu stronach
- **Wejście:** `URLOP.zapisz(2026-09-15 … 2026-09-17)`.
- **Wynik:** `SLOTY` per dzień: `14.09 = 4`, `15.09 = 0`, `16.09 = 0`, `17.09 = 0`,
  `18.09 = 4`. **Pięć liczb.**
- **Poz.:** pięcioelementowa lista równa `[4, 0, 0, 0, 4]`.
- **Neg.:** urlop `2026-09-15 … 2026-09-15` (jeden dzień) → `[4, 0, 4, 4, 4]`.
- **Pert.:** granica przesunięta o dobę (`<` zamiast `<=`) → `[4, 0, 0, 4, 4]`, czerwony.
- **Obserwacja:** `SLOTY` per dzień, pięć zapytań.

#### F2-A-08 · Urlop pokazuje, ile wizyt trzeba przełożyć
- **Wejście:** 3 opłacone wizyty w dniach 15–17.09, potem `URLOP.zapisz(15–17.09)`.
- **Wynik:** `liczba_wizyt_do_przelozenia` = **3**; liczba **odwołanych automatycznie** = **0**
  (spec: system *pokazuje*, nie kasuje).
- **Poz.:** 3 i 0.
- **Neg.:** urlop w tygodniu bez wizyt → **0** i **0**.
- **Pert.:** urlop kasujący wizyty → liczba wizyt w bazie spada, czerwony.
- **Obserwacja:** liczba wizyt **osobnym zapytaniem do bazy**, nie z odpowiedzi `URLOP.zapisz`.

#### F2-A-09 · Jedna funkcja slotów — trzy konsumenty, jedna odpowiedź
> Spec M2/2 nazywa to głównym ryzykiem: *ta sama funkcja obsługuje 7 dni × 1 osobę,
> 30 dni × 111 osób i 35 dni × 111 osób*. Trzy implementacje rozjadą się po cichu.
- **Wejście:** `S111`; ten sam specjalista i ten sam dzień odpytany jako **panel**
  (7 d × 1 os.), **wyszukiwarka** (30 d × 111) i **grafik** (35 d × 111).
- **Wynik:** **różnica symetryczna zbiorów slotów = 0** dla wszystkich trzech par;
  liczba slotów identyczna (**3 × ta sama liczba**).
- **Poz.:** liczba porównanych dni = **35** i liczba specjalistów = **111** (K-4 — bez tego
  „różnica 0" przechodzi przy zerowym przecięciu zakresów).
- **Neg.:** poprawka wprowadzona po stronie panelu → różnica symetryczna = **1** we
  wszystkich trzech ujęciach jednocześnie (a nie w jednym).
- **Pert.:** grafik liczony osobną ścieżką → różnica > 0, czerwony.
- **Obserwacja:** trzy niezależne wywołania, porównanie zbiorów startów **UTC** (nie etykiet).

#### F2-A-10 · Unieważnianie cache dotyczy jednego specjalisty i jednego dnia
- **Wejście:** `S111` rozgrzany; `POPRAWKA.zapisz(wyłącz, S1, 2026-09-22 11:00)`.
- **Wynik:** w **następnym** żądaniu `SLOTY(S1, 2026-09-22)` = **3** (nie po TTL);
  liczba dni ze zmienioną liczbą slotów **u S1** = **1**;
  liczba specjalistów ze zmienioną liczbą slotów = **1** (ze 111).
- **Poz.:** 3 · 1 · 1.
- **Neg.:** bez zapisu poprawki liczba zmienionych dni = **0** i specjalistów = **0**.
- **Pert.:** unieważnianie całego cache → liczba zmienionych specjalistów = 111 → czerwony
  (nadmiarowe unieważnienie jest defektem wydajnościowym, nie „bezpiecznym zapasem").
- **Obserwacja:** migawka liczb slotów przed i po, **dla wszystkich 111**.

---

### B · Bufor 10 minut i dwie długości usług

> Spec s. 25/35/50: *przerwa między wizytami 10 minut, zawsze* · zakres M1/2:
> *„godzina 90-minutowa musi zdejmować z puli dwa sloty i bufor"*.

#### F2-B-01 · Bufor działa wobec wizyty spoza rastra
- **Wejście:** `S1`; wizyta ręczna **10:55–11:45** dnia 2026-09-22; `SLOTY(konsultacja, 22.09)`.
- **Wynik:** **2 sloty** — `[09:00, 12:00]`.
  (`10:00` odpada: wizyta kończy się 10:50, następna zaczyna 10:55 → przerwa **5 min < 10**.
  `11:00` odpada przez nakładkę. `12:00` wchodzi: 11:45 + 10 = 11:55 ≤ 12:00.)
- **Poz.:** zbiór startów równy `[09:00, 12:00]`.
- **Neg.:** **ten sam przypadek z buforem 0** → **3 sloty** `[09:00, 10:00, 12:00]`.
  **2 vs 3 — to jest liczba, która odróżnia bufor od zwykłej kontroli nakładek.**
- **Pert.:** bufor stosowany tylko „w przód" (po wizycie) → 3, czerwony.
- **Obserwacja:** `SLOTY` + niezależne przeliczenie odstępów między wszystkimi parami
  sąsiednich zajętości dnia (minimum = **10 min**).

#### F2-B-02 · ADHD 90 min zdejmuje dwa sloty konsultacji **i** bufor
- **Wejście:** rezerwacja ADHD `09:00–10:30` dnia 22.09; `SLOTY(konsultacja, 22.09)`.
- **Wynik:** **2 sloty** — `[11:00, 12:00]` (`10:00` odpada: 10:00 < 10:40 = koniec + bufor).
- **Poz.:** 2, starty `[11:00, 12:00]`.
- **Neg.:** bez rezerwacji ADHD → **4**.
- **Pert.:** długość ADHD liczona jako 60 min → 3 sloty, czerwony.
- **Obserwacja:** `SLOTY` + zapytanie o zajętość w minutach (100 min zajęte).

#### F2-B-03 · Kierunek odwrotny — konsultacja zamyka oba sloty ADHD
- **Wejście:** rezerwacja konsultacji `10:00–10:50` dnia 22.09; `SLOTY(ADHD, 22.09)`.
- **Wynik:** **0 slotów** ADHD.
- **Poz.:** `SLOTY(ADHD, 2026-09-23)` = **2** (K-4 — świadek, że ADHD w ogóle ma sloty).
- **Neg.:** bez rezerwacji konsultacji → **2**.
- **Pert.:** kolizja liczona tylko wewnątrz jednej usługi → 1 albo 2, czerwony.
  (Spec M2/5 wprost: *„zamknięcie tego samego czasu w drugiej usłudze"*.)
- **Obserwacja:** dwa zapytania `SLOTY` po różnych usługach.

#### F2-B-04 · Niezmiennik: żadne dwa sloty tej samej osoby nie są bliżej niż 10 min
- **Wejście:** `S1`, pełne okno 30 dni, wszystkie 4 usługi, wraz z rezerwacjami z seeda.
- **Wynik:** liczba par (slot, slot) tej samej osoby o odstępie **< 10 min** = **0**;
  liczba sprawdzonych par > **0** (K-4, podać dokładną liczbę z seeda).
- **Poz.:** liczba sprawdzonych par wypisana w raporcie — suma bez rozbicia nie jest dowodem.
- **Neg.:** wstrzyknięcie jednej wizyty 5 min po innej → liczba naruszeń = **1**.
- **Pert.:** bufor wyzerowany w konfiguracji → liczba naruszeń > 0, czerwony.
- **Obserwacja:** przeliczenie **z bazy**, nie z odpowiedzi API — inaczej kontrola dzieli
  mechanizm z przedmiotem.

#### F2-B-05 · Zakres niebędący wielokrotnością rastra — bufor wlicza się do zakresu
> Dopisane 12.08 po rozstrzygnięciu `Q-1` (`ODPOWIEDZ-045`): **bufor wlicza się w zakres**.
> Do tego dnia przypadek nie mógł istnieć, bo miał dwie wartości oczekiwane.
- **Wejście:** rytm `09:00–12:59` (239 min); `SLOTY(konsultacja, wtorek)`.
- **Wynik:** **3 sloty** — `[09:00, 10:00, 11:00]`. Czwarty wymagałby `12:00–13:00`,
  a zakres kończy się `12:59`.
- **Poz.:** rytm `09:00–13:00` (240 min) → **4**. **Jedna minuta różnicy w zakresie
  zmienia liczbę slotów o jeden** — to jest cały sens rozstrzygnięcia `Q-1`.
- **Neg.:** przy odczycie odrzuconym („bufor nie musi się zmieścić po ostatnim slocie")
  zakres `09:00–12:59` dałby **4** sloty, bo wizyta `12:00–12:50` mieści się w zakresie.
  Ta liczba jest teraz **czerwienią**, nie wariantem.
- **Pert.:** raster liczony jako `długość_usługi` zamiast `długość + bufor` przy sprawdzaniu
  końca zakresu → 4, czerwony.
- **Obserwacja:** `SLOTY` + niezależne przeliczenie `floor(239 / 60) = 3`.

---

### C · Horyzonty: 2 h / 30 dni / 7 dni

#### F2-C-01 · Najbliższy możliwy termin: 2 h — trzy wartości na granicy
- **Wejście:** zegar kolejno `07:59:59`, `08:00:00`, `08:00:01` (2026-09-15);
  `SLOTY(konsultacja, 2026-09-15)`.
- **Wynik:** **3 · 3 · 2**. Próg = zegar + 2 h; slot `10:00` wchodzi, dopóki próg ≤ 10:00:00.
- **Poz.:** trzy liczby w jednym przebiegu.
- **Neg.:** zegar `2026-09-15 05:00:00` → **4** (cały dzień dostępny).
- **Pert.:** `>=` zamienione na `>` w progu → `3 · 2 · 2`, czerwony (to jest istniejąca
  perturbacja `testy`, przeniesiona na horyzont).
- **Obserwacja:** `SLOTY` z jawnie podanym zegarem (K-6).

#### F2-C-02 · Kalendarz pacjenta otwarty 30 dni
- **Wejście:** zegar `T0`; `SLOTY(konsultacja, 2026-09-15 … 2026-11-15)` w roli **pacjenta**.
- **Wynik:** liczba dni z ≥ 1 slotem = **23** (dni robocze od 15.09 do 15.10 włącznie);
  `SLOTY(2026-10-15)` = **4**; `SLOTY(2026-10-16)` = **0**.
- **Poz.:** 4 dla ostatniego dnia okna.
- **Neg.:** to samo zapytanie w roli **koordynatora/grafiku** (35 dni) → `SLOTY(2026-10-16)`
  = **4**. Horyzont 30 dni jest ograniczeniem **prezentacji dla pacjenta**, nie brakiem slotu.
- **Pert.:** horyzont liczony w miesiącach → `SLOTY(2026-10-16)` = 4 dla pacjenta, czerwony.
- **Obserwacja:** dwa zapytania w dwóch rolach.

#### F2-C-03 · Wystawianie najwyżej 7 dni w przód — **egzekwowane w API**
> Spec M3/2: *„Blokada zapisu terminu dalej niż 7 dni w przód po stronie API, nie tylko
> w panelu specjalisty"*.
- **Wejście:** zegar `T0`; `POPRAWKA.zapisz(dodaj, …)` dla dat `T0+6d`, `T0+7d`, `T0+8d`.
- **Wynik:** **przyjęte · przyjęte · odrzucone (422)**; liczba zapisanych poprawek = **2**.
- **Poz.:** 2.
- **Neg.:** ten sam zapis przez **koordynatora** → `Q-6`; do rozstrzygnięcia liczba
  zapisanych poprawek dla `T0+8d` = **0** (koordynator też podlega) albo **1**.
- **Pert.:** kontrola przeniesiona do warstwy prezentacji → `T0+8d` przechodzi przez API,
  czerwony.
- **Obserwacja:** liczba wierszy poprawek **z bazy**.

#### F2-C-04 · Termin ręczny spoza grafiku jest dozwolony
> Cytat spec, s. 11: *„Termin spoza grafiku jest dozwolony i nie wymaga niczyjej zgody."*
- **Wejście:** specjalista umawia pacjenta na `2026-09-22 20:00` (poza rytmem).
- **Wynik:** rezerwacja utworzona (**1**); `SLOTY(konsultacja, 22.09)` **publicznie** = **4**
  (godzina 20:00 **nie staje się** slotem do rezerwacji dla innych); zajętość dnia rośnie
  o **60 min**.
- **Poz.:** 1 · 4 · 60.
- **Neg.:** ten sam termin ręczny o `12:30` → koliduje z rytmem przez bufor →
  `SLOTY` = **3** (slot 12:00 znika). Termin ręczny **zabiera** dostępność, nie dodaje.
- **Pert.:** termin ręczny publikowany jako slot → 5, czerwony.
- **Obserwacja:** `SLOTY` w roli pacjenta + liczba rezerwacji z bazy.

#### F2-C-05 · Horyzont 7 dni a termin ręczny — **rozdzielone**
- **Wejście:** specjalista umawia pacjenta na `T0+20d 20:00`.
- **Wynik:** przy rozstrzygnięciu `Q-6` = „horyzont dotyczy wyłącznie wystawiania
  dostępności": rezerwacja utworzona = **1**. Przy rozstrzygnięciu przeciwnym: **0** + 422.
- **Poz.:** para z `C-03` — ta sama data odrzucona jako *wystawienie*, przyjęta jako
  *umówienie*: `poprawki = 0`, `rezerwacje = 1`.
- **Neg.:** termin ręczny na `T0−1d` (przeszłość) → **odrzucony**, `rezerwacje = 0`.
  Zniesienie horyzontu w przód nie znosi kontroli przeszłości.
- **Pert.:** jedno wspólne sprawdzenie dla obu operacji → obie liczby jednakowe, czerwony.
- **Obserwacja:** dwie tabele, dwa zapytania.
- **⚠ Wartość oczekiwana zależy od `Q-6`.**

---

### D · Blokada slotu

> `D-2026-08-09-08` (blokada dwustopniowa, `okno = min(okno_ścieżki, czas_do_wizyty − margines)`,
> płatność po wygaśnięciu nie tworzy wizyty, limit równoczesnych blokad) ·
> `D-2026-08-09-11` §4 (zegar startuje **po** potwierdzeniu) ·
> `D-2026-08-09-15` (2 dni + 10 min od otwarcia linku — **drugi stopień nie istnieje w kodzie,
> to wymaganie F2**).

#### F2-D-01 · Rezerwacja własna: 10 min — trzy wartości na granicy
- **Wejście:** `BLOKADA.zaloz(własna)` o `t0`; drugi pacjent pyta o `SLOTY` w
  `t0+09:59.999`, `t0+10:00.000`, `t0+10:00.001`.
- **Wynik:** **3 · 3 · 4** (slot wraca dopiero po przekroczeniu okna — konwencja K-1).
- **Poz.:** 4 w trzecim pomiarze (slot **naprawdę** wraca).
- **Neg.:** bez blokady wszystkie trzy pomiary = **4**.
- **Pert.:** blokada zwalniana tylko przez zadanie cykliczne, nie przy odczycie → drugi
  pacjent widzi 3 także w `t0+30 min`, czerwony.
- **Obserwacja:** `SLOTY` drugiego pacjenta + `trzymane_do` z bazy (dwie drogi, bo blokada
  „logicznie wygasła" i „usunięta przez sprzątaczkę" to dwa różne stany).

#### F2-D-02 · Blokada DWUSTOPNIOWA — zegar startuje po potwierdzeniu
- **Wejście:** `BLOKADA.zaloz(własna)` o `t0`; `BLOKADA.potwierdz` o `t0+2:00`.
- **Wynik:** `trzymane_do` = **`t0+12:00`**, nie `t0+10:00`.
- **Poz.:** slot niedostępny dla drugiego pacjenta w `t0+11:00` (**3**), dostępny
  w `t0+12:00.001` (**4**).
- **Neg.:** **bez potwierdzenia** slot wraca po krótkiej blokadzie wstępnej (`Q-8`):
  dostępny w `t0+X+0.001` — a więc **nie** trzymany przez pełne 10 min. Bez tej kontroli
  niepotwierdzony klikacz trzyma slot za darmo (`D-2026-08-09-08`).
- **Pert.:** zegar liczony od wyboru terminu → `trzymane_do` = `t0+10:00`, czerwony.
- **Obserwacja:** `trzymane_do` z bazy + odczyt `SLOTY` drugim kontem.
- **✅ `Q-8` ROZSTRZYGNIĘTE (`ODPOWIEDZ-045`): blokada wstępna = 10 min**, parametr
  konfiguracyjny. `X = 10 min`, więc bez potwierdzenia slot wraca o `t0+10:00.001`,
  a z potwierdzeniem o `t0+2:00` — o `t0+12:00.001`. **Dwie liczby różniące się o 2 min
  i to jest cały mechanizm.**

#### F2-D-03 · Umawianie przez psychologa: 2 dni
- **Wejście:** `BLOKADA.zaloz(psycholog)` o `2026-09-15 10:00`.
- **Wynik:** `trzymane_do` = **`2026-09-17 10:00`**; `SLOTY` dla innego pacjenta:
  `17.09 09:59:59` → **n−1**, `17.09 10:00:01` → **n**.
- **Poz.:** n−1 i n z podaną wartością n z §3.2.
- **Neg.:** ta sama operacja ścieżką `własna` → `trzymane_do` = `10:10`. **Jedna operacja,
  dwie ścieżki, dwie różne liczby** — to jest test rozdzielenia ścieżek.
- **Pert.:** obie ścieżki czytają jedno pole konfiguracji → `trzymane_do` równe, czerwony.
- **Obserwacja:** `trzymane_do` z bazy.

#### F2-D-04 · Drugi stopień: 10 minut **od otwarcia linku**
- **Wejście:** blokada psychologa z `D-03`; `LINK.otworz` o `2026-09-17 09:55`
  (pięć minut przed końcem okna 2 dni).
- **Wynik:** `trzymane_do` = **`2026-09-17 10:05`** — okno przedłużone poza 2 dni.
- **Poz.:** płatność zaksięgowana `10:03` → rezerwacja = **1**, zadania zwrotu = **0**.
- **Neg.:** `LINK.otworz` o `2026-09-16 20:00` (dawno przed końcem) → `trzymane_do`
  pozostaje **`2026-09-17 10:00`**, **nie** `2026-09-16 20:10`. Reguła to
  **`max(wysłanie + 2 dni, otwarcie + 10 min)`**, nie zastąpienie.
- **Pert.:** `min` zamiast `max` → `trzymane_do` = 20:10, czerwony.
- **Obserwacja:** `trzymane_do` przed i po `LINK.otworz`.
- **✅ `Q-9` ROZSTRZYGNIĘTE (`ODPOWIEDZ-045`): `max(wysłanie + 2 dni, otwarcie + 10 min)`.**
  Uzasadnienie architekta: *otwarcie linku nie może SKRACAĆ okna pacjenta*. Wartość
  oczekiwana kontroli negatywnej jest więc twarda: **`2026-09-17 10:00`**.

#### F2-D-05 · `okno = min(okno_ścieżki, czas_do_wizyty − margines)`
- **Wejście:** wizyta `2026-09-16 09:00`; `BLOKADA.zaloz(psycholog)` o `2026-09-15 18:00`.
- **Wynik:** `trzymane_do` = **`2026-09-16 07:00`** (przy marginesie `M = 2 h`), czyli
  **13 h**, nie 2 dni. Termin płatności **nigdy nie wypada po wizycie**.
- **Poz.:** `trzymane_do` < termin wizyty — asercja na różnicy = **7200 s**.
- **Neg.:** wizyta `2026-10-15 09:00` (miesiąc naprzód) → `trzymane_do` = **`2026-09-17 18:00`**
  (pełne 2 dni). Dwie wizyty, jedna operacja, dwie różne wartości.
- **Pert.:** `min` usunięty → `trzymane_do` po wizycie, czerwony.
- **Obserwacja:** `trzymane_do` z bazy.
- **✅ `Q-10` ROZSTRZYGNIĘTE (`ODPOWIEDZ-045`): `M = 2 h`**, parametr konfiguracyjny,
  **ta sama oś co „najbliższy możliwy termin"**. Wartość `trzymane_do = 2026-09-16 07:00`
  jest twarda; różnica do terminu wizyty = **7200 s**.

#### F2-D-06 · Płatność po wygaśnięciu blokady NIE tworzy wizyty
- **Wejście:** blokada pacjenta A wygasła; slot zajęty przez pacjenta B; webhook płatności
  pacjenta A dociera później.
- **Wynik:** rezerwacje na `(S1, termin)` = **1** (pacjent B); wizyty pacjenta A = **0**;
  zadania zwrotu = **1**; kwota zadania = **`kwota_zamrozona` pacjenta A = 14500 gr**.
- **Poz.:** płatność **przed** wygaśnięciem → rezerwacje = 1 (pacjent A), zadania = **0**.
- **Neg.:** blokada wygasła, ale **nikt slotu nie zajął** → wizyty = **0**, zadania = **1**
  (`D-2026-08-09-08`: *„tworzy zwrot albo zadanie dla koordynatora"*). **Automat nie
  tworzy wizyty nawet przy wolnym terminie.**
- **Pert.:** ścieżka „zapłacone, więc rezerwuj" przywrócona → rezerwacje = 2, czerwony.
  **Perturbacja z allowlistą** (K-5) — to jest ścieżka pieniędzy.
- **Obserwacja:** liczba wierszy rezerwacji z bazy + liczba zadań zwrotu z osobnej tabeli.
- **⚠ Kiedy zwrot, a kiedy zadanie: `Q-11`.**

#### F2-D-07 · Limit równoczesnych nieopłaconych blokad na pacjenta
> `D-2026-08-09-08`: obrona zastępcza po zniesieniu kodu przy każdej rezerwacji (`R-3`).
- **Wejście:** pacjent zakłada blokady `#1`, `#2`, `#3` na różne terminy.
- **Wynik (przy limicie 2):** przyjęte · przyjęte · **odrzucone (422)**;
  aktywnych blokad = **2**; zajętych slotów = **2**.
- **Poz.:** 2.
- **Neg.:** po wygaśnięciu `#1` blokada `#3` przechodzi → aktywnych = **2** (nie 3).
- **Pert.:** limit liczony po **sesji** zamiast po **pacjencie** → pacjent z dwóch
  przeglądarek zakłada 4 blokady, czerwony. (To jest scenariusz zamrażania grafiku `D5`.)
- **Obserwacja:** liczba aktywnych blokad z bazy, klucz = pacjent.
- **✅ `Q-12` ROZSTRZYGNIĘTE (`ODPOWIEDZ-045`): limit = 2**, parametr konfiguracyjny.
  Uzasadnienie architekta: *2 nie karze pary „wizyta dla mnie i dziecka"*. Wartości
  w tym przypadku (przyjęte · przyjęte · odrzucone, aktywnych = 2) są twarde.

#### F2-D-08 · Wygaśnięcie blokady zostawia ślad
- **Wejście:** blokada wygasa bez płatności.
- **Wynik:** zdarzeń typu „blokada wygasła" w śladzie audytowym = **1**;
  zaplanowanych powiadomień do pacjenta = **1** (kanał — F6).
- **Poz.:** 1 i 1.
- **Neg.:** blokada zakończona płatnością → zdarzeń „wygasła" = **0**.
- **Pert.:** ciche zwolnienie slotu → 0 zdarzeń, czerwony (`D-2026-08-09-08`:
  *„cisza znaczy »nie wiem, czy mam wizytę«"*).
- **Obserwacja:** tabela zdarzeń (append-only), osobne zapytanie.

#### F2-D-09 · „2 dni" a zmiana czasu
- **Wejście:** `BLOKADA.zaloz(psycholog)` o `2026-10-24 10:00 CEST`.
- **Wynik przy odczycie „48 h absolutnych":** `trzymane_do` = **`2026-10-26 09:00 CET`**
  (= 48 h). Przy odczycie „2 dni kalendarzowe": **`2026-10-26 10:00 CET`** (= **49 h**).
  **Różnica: 3600 s.**
- **Poz.:** ta sama operacja w tygodniu bez zmiany czasu → obie wartości identyczne
  (kontrola, że przypadek w ogóle rozróżnia).
- **Neg.:** `2026-03-27 10:00 CET` → 48 h daje `2026-03-29 11:00 CEST`, dwa dni
  kalendarzowe dają `10:00 CEST` (= **47 h**). Rozjazd w drugą stronę.
- **Pert.:** doba liczona jako 86400 s przy odczycie kalendarzowym → czerwony.
- **Obserwacja:** `trzymane_do` w UTC **i** lokalnie.
- **✅ `Q-19` ROZSTRZYGNIĘTE (`ODPOWIEDZ-045`): 48 h absolutnych.** Wartość oczekiwana
  twarda: **`2026-10-26 09:00 CET`**; odczyt kalendarzowy (`10:00 CET`) staje się
  **kontrolą negatywną**, nie wariantem.

---

### E · Okno 24 h — zegarowo

> `D-2026-08-09-06`: zegarowo, także przez weekend; **kontrola wymagana: kierunek 0 na
> granicy — dokładnie 24 h, sekundę przed i sekundę po**.

#### F2-E-01 · Granica 24 h — trzy wartości
- **Wejście:** wizyta `2026-09-22 09:00` (`kwota_zamrozona = 14500`); odwołanie o
  `2026-09-21 08:59:59`, `09:00:00`, `09:00:01`.
- **Wynik:** `zwrot_gr` = **14500 · 14500 · 0**.
- **Poz.:** trzy liczby w jednym przebiegu tabelarycznym.
- **Neg.:** **`termin_wrocil_do_puli` = `true` we wszystkich trzech** (spec, tabela s. 143–151:
  późne odwołanie **też** zwalnia termin) → `SLOTY` po odwołaniu = **n** w każdym przypadku.
  Bez tej kontroli test „granica działa" przechodzi także wtedy, gdy późne odwołanie
  po cichu blokuje termin do końca świata.
- **Pert.:** `>=` → `>` w oknie → `14500 · 0 · 0`, czerwony. **Allowlista** (K-5).
- **Obserwacja:** `zwrot_gr` z odpowiedzi + `SLOTY` osobnym zapytaniem.

#### F2-E-02 · Zegarowo, nie w dniach roboczych
- **Wejście:** wizyta **poniedziałek** `2026-09-21 09:00`; odwołanie **sobota**
  `2026-09-19 12:00`.
- **Wynik:** `zwrot_gr` = **14500** (45 h przed wizytą).
- **Poz.:** odwołanie **niedziela `2026-09-20 08:00`** (25 h) → **14500**.
- **Neg.:** odwołanie **niedziela `2026-09-20 10:00`** (23 h) → **0**.
  **Granica wypada w niedzielę o 09:00, nie w piątek.** Przy odczycie „dni robocze"
  sobota 12:00 dałaby **0** — dlatego to sobotnie odwołanie jest przypadkiem rozstrzygającym.
- **Pert.:** wprowadzenie kalendarza dni roboczych → sobota daje 0, czerwony.
- **Obserwacja:** `zwrot_gr`.

#### F2-E-03 · Brak progów pośrednich — zbiór wartości ma dokładnie 2 elementy
> Spec s. 7: *„każdy dodatkowy próg to kolejne pytanie do koordynatora"*.
- **Wejście:** ta sama rezerwacja odwoływana w 6 momentach: 168 h, 48 h, 24:00:01,
  24:00:00, 23:59:59, 1 h przed.
- **Wynik:** zbiór różnych wartości `zwrot_gr` = **{14500, 0}**, liczność = **2**.
- **Poz.:** 6 pomiarów, dwie wartości, w podanej kolejności `[14500 ×4, 0 ×2]`.
- **Neg.:** wprowadzenie progu 50% → liczność = **3**, czerwony.
- **Pert.:** `zwrot_procent` = 50 dla jednej sytuacji macierzy → liczność 3.
- **Obserwacja:** `zwrot_gr` z sześciu wywołań; **liczymy zbiór wartości**, nie „czy
  przycisk jest widoczny".

#### F2-E-04 · Okno liczy się od TERMINU WIZYTY, nie od daty zakupu
- **Wejście:** dwie rezerwacje na tę samą wizytę-bliźniaczkę `2026-09-22 09:00`, kupione
  `2026-08-01` i `2026-09-21 08:00`; odwołanie obu o `2026-09-21 08:59:59`.
- **Wynik:** `zwrot_gr` = **14500 i 14500**.
- **Poz.:** dwie identyczne liczby.
- **Neg.:** przesunięcie **terminu wizyty** o 2 h (na `07:00`) → dla obu **0**.
  Zmienia wynik termin, nie data zakupu.
- **Pert.:** okno liczone od `utworzono_at` → pierwsza rezerwacja daje 0, czerwony.
- **Obserwacja:** `zwrot_gr` ×2.

---

### F · Limity — **to są DWA różne limity**

> `D-2026-08-09-05`: `limit_niskoplatnych_wizyt = 10` → **na PACJENTA, sumarycznie**;
> `limit_niskoplatnych_na_tydzien = 4` → **na SPECJALISTĘ, tygodniowo**.
> `D-2026-08-09-08` ⛔: **historia obejmuje wszystkie wizyty, LIMIT liczy tylko niskopłatne.**

#### F2-F-01 · Limit pacjenta: 10 niskopłatnych, granica
- **Wejście:** pacjent z **9** odbytymi niskopłatnymi; rezerwacja 10., potem 11.
- **Wynik:** `pozostale` przed = **1**; 10. przyjęta; `pozostale` = **0**;
  11. **odrzucona (422)**; `wykorzystane` = **10** (nie 11).
- **Poz.:** 1 · 0 · 10.
- **Neg.:** pacjent z **8** → 11. próba nie występuje, `pozostale` po dwóch = **0**… —
  właściwie: pacjent z 8 → dwie rezerwacje przechodzą, `pozostale` = **0**, trzecia
  odrzucona. Ta sama granica z innego punktu startu.
- **Pert.:** limit czytany z konfiguracji bieżącej zamiast z zamrożonej → patrz `G-02`.
- **Obserwacja:** `LIMIT.pacjent` + niezależne zliczenie rezerwacji z bazy (dwie drogi —
  licznik agregowany i policzenie wierszy **muszą się zgadzać**).

#### F2-F-02 · Limit NIE odnawia się w czasie
- **Wejście:** pacjent z 10 wizytami niskopłatnymi rozłożonymi na **3 lata**
  (po ~3 rocznie), zegar `T0`.
- **Wynik:** `pozostale` = **0**.
- **Poz.:** ten sam pacjent z 9 wizytami → `pozostale` = **1**.
- **Neg.:** przesunięcie zegara o rok → `pozostale` nadal **0**. **Limit nie jest oknem.**
- **Pert.:** liczenie w oknie 12 miesięcy → `pozostale` = 7, czerwony.
- **Obserwacja:** `LIMIT.pacjent` przy dwóch różnych zegarach.

#### F2-F-03 · Limit liczy **wyłącznie niskopłatne**
> `D-2026-08-09-08`: *„Gdyby licznik zaczął liczyć wszystko, odciąłby od pomocy ludzi
> płacących pełną stawkę."*
- **Wejście:** pacjent z **10 pełnopłatnymi** i **0 niskopłatnymi**.
- **Wynik:** `pozostale` = **10**; rezerwacja niskopłatna **przyjęta**.
- **Poz.:** 10.
- **Neg.:** pacjent z **10 niskopłatnymi** → `pozostale` = **0**, ale rezerwacja
  **pełnopłatna przyjęta** (liczba rezerwacji +1). **Wyczerpany limit nie odcina od
  wizyt pełnopłatnych.**
- **Pert.:** licznik liczy wszystkie wizyty → `pozostale` = 0 w pierwszym przypadku,
  czerwony. **Allowlista** (K-5) — to odcina ludzi od pomocy.
- **Obserwacja:** `LIMIT.pacjent` + liczba rezerwacji per kategoria z bazy.

#### F2-F-04 · Licznik wisi na PACJENCIE, nie na tym, kto kliknął
- **Wejście:** 10 wizyt niskopłatnych tego samego pacjenta: **4** umówione przez
  psychologa, **3** przez panel pacjenta, **3** przez stronę.
- **Wynik:** `wykorzystane` = **10**, `pozostale` = **0**; 11. odrzucona **każdą z trzech
  ścieżek** (3 × 422).
- **Poz.:** 10 i trzy odrzucenia.
- **Neg.:** te same 10 wizyt rozdzielone na **dwóch różnych pacjentów** (5 + 5) →
  `pozostale` = **5** i **5**. Licznik nie skleja ludzi.
- **Pert.:** licznik po autorze operacji → psycholog wyczerpuje limit pacjentom, czerwony.
  Spec mówi wprost, że regułą jest umawianie niskopłatnych przez psychologa — ten defekt
  trafiłby w **większość** wizyt niskopłatnych.
- **Obserwacja:** `LIMIT.pacjent` dla obu pacjentów + trzy próby przez trzy ścieżki.

#### F2-F-05 · Limit jest twardą bramką z jawnym wyjątkiem
> Rozstrzygnięcie właściciela 09.08: **twarda bramka**, *„czasami zdejmowana — to należy
> do decyzji fundacji"* → bramka + przycisk wyjątku z powodem i śladem.
- **Wejście:** pacjent `pozostale = 0`; koordynator podnosi limit o **+4** z uzasadnieniem
  o długości **41 znaków**; potem drugi raz z uzasadnieniem **39 znaków**.
- **Wynik:** `limit` = **14**, `pozostale` = **4**; wpisów w dzienniku decyzji = **1**;
  drugie podniesienie **odrzucone**, `limit` nadal **14**, wpisów = **1**.
- **Poz.:** 14 · 4 · 1.
- **Neg.:** próba rezerwacji przy `pozostale = 0` **bez** podniesienia → 422 i wpisów = **0**
  (odmowa nie jest decyzją uznaniową i nie brudzi dziennika).
- **Pert.:** bramka zamieniona na ostrzeżenie → rezerwacja przechodzi przy `pozostale = 0`,
  czerwony.
- **Obserwacja:** `LIMIT.pacjent` + liczba wierszy dziennika (tabela append-only, F5 —
  granica faz odnotowana w §7).

#### F2-F-06 · Limit podażowy 4/tydzień ISO na specjalistę — przy WYSTAWIANIU
- **Wejście:** `S1` (rytm niskopłatny daje **2** w tygodniu W39); kolejno
  `POPRAWKA.zapisz(dodaj)` dla trzeciego, czwartego i piątego terminu niskopłatnego W39.
- **Wynik:** `wystawione` = **3 · 4 · 4**; trzecia operacja **odrzucona (422)** i
  **wskazuje konkretny termin**, który nie przeszedł (spec M2/4) — liczba wskazanych
  terminów = **1**.
- **Poz.:** 4 i jeden wskazany termin.
- **Neg.:** ta sama piąta operacja w tygodniu **W40** → **przyjęta**, `wystawione(W40)` = **3**,
  `wystawione(W39)` = **4**. Pula liczy się per tydzień ISO, nie globalnie.
  *(Sprostowanie 12.08: pierwsza wersja podawała `wystawione(W40)` = **1** — pominęła, że
  **rytm jest cykliczny** i sam z siebie daje 2 terminy niskopłatne w KAŻDYM tygodniu,
  więc po dołożeniu poprawki wychodzi 3, nie 1. Złapane przy `SZK-F-06`.)*
- **Pert.:** tydzień liczony od niedzieli → operacja z poniedziałku wpada do poprzedniej puli,
  czerwony.
- **Obserwacja:** `LIMIT.specjalista` dla dwóch tygodni + liczba slotów niskopłatnych z bazy.

#### F2-F-07 · Reset puli w poniedziałek 00:00 Europe/Warsaw — także dla innej strefy
> Spec M2/4: *„Reset puli w poniedziałek o północy czasu Europe/Warsaw, także dla
> specjalistów pracujących w innej strefie."*
- **Wejście:** specjalista ze strefą **America/New_York**, `wystawione(W38) = 4`;
  operacja wystawienia o **niedzielę 2026-09-20 23:30 czasu NY** (= poniedziałek
  `2026-09-21 05:30` Europe/Warsaw).
- **Wynik:** operacja **przyjęta**; `wystawione(W38)` = **4**, `wystawione(W39)` = **1**.
- **Poz.:** 4 i 1.
- **Neg.:** operacja o **niedzielę 2026-09-20 17:00 NY** (= niedziela 23:00 Warsaw) →
  **odrzucona**, `wystawione(W38)` = **4**, `wystawione(W39)` = **0**.
  **Dwie operacje w odstępie 6,5 h, po dwóch stronach granicy tygodnia.**
- **Pert.:** tydzień liczony w strefie specjalisty → pierwsza operacja odrzucona, czerwony.
- **Obserwacja:** `LIMIT.specjalista` dla obu tygodni.

#### F2-F-08 · Limit podażowy **NIE działa przy rezerwacji**
> Spec, cytat przez `ZLECENIE-043` §5: *„pacjent nigdy nie powinien zobaczyć wolnego
> terminu i dostać odmowy przy płatności — w najgorszym możliwym momencie, po podjęciu
> decyzji i wyjęciu karty"*.
- **Wejście:** `wystawione(W39) = 4`; koordynator **obniża** limit konfiguracyjny do **2**;
  pacjenci rezerwują wszystkie 4 wystawione terminy.
- **Wynik:** rezerwacji = **4**, odmów = **0**.
- **Poz.:** 4 i 0.
- **Neg.:** po obniżeniu limitu **nowe wystawienie** jest odrzucone → `wystawione` = **4**,
  liczba nowych = **0**. Limit działa w przód, nie wstecz (`Q-13`).
- **Pert.:** sprawdzenie limitu przeniesione do ścieżki rezerwacji → odmów = 2, czerwony.
  **To jest test MIEJSCA egzekwowania, nie wartości.**
- **Obserwacja:** liczba rezerwacji z bazy + liczba odpowiedzi 422.

#### F2-F-09 · Definicja „wystawiony termin niskopłatny"
> Spec, s. 17, wprost: *„Ta definicja musi zapaść przed kodowaniem."* Nie zgaduję.
- **Wejście:** `wystawione(W39) = 4`; jeden z tych terminów zostaje **zarezerwowany**;
  próba wystawienia piątego.
- **Wynik przy odczycie „slot otwarty":** `wystawione` = **4**, piąty **odrzucony**.
  Przy odczycie „slot otwarty **i wolny**": `wystawione` = **3**, piąty **przyjęty**,
  `wystawione` = **4**.
- **Poz.:** ta sama para operacji rozróżnia oba odczyty liczbą **4 vs 5** wystawionych
  terminów w tygodniu — przypadek **umie** pokazać różnicę, niezależnie od rozstrzygnięcia.
- **Neg.:** ten sam ciąg **bez rezerwacji** któregokolwiek terminu → `wystawione` = **4**
  i piąty odrzucony **w obu odczytach**. Rezerwacja jest jedyną zmienną, która je rozróżnia.
- **Pert.:** definicja zmieniona po cichu → jedna z liczb się zmienia, czerwony.
- **Obserwacja:** `LIMIT.specjalista` + liczba slotów niskopłatnych z bazy, per stan.
- **✅ `Q-14` ROZSTRZYGNIĘTE (`ODPOWIEDZ-045`): „wystawiony" = slot OTWARTY**, niezależnie
  od rezerwacji. Wartości twarde: `wystawione` = **4**, piąty **odrzucony**.
  Odczyt „otwarty i wolny" (`wystawione` = 3, piąty przyjęty) staje się **kontrolą
  negatywną** — uzasadnienie architekta: *limit podażowy ma ograniczać podaż, a drugi
  odczyt czyni go rosnącym z każdą rezerwacją*.

#### F2-F-10 · Dwa limity są rozłączne
- **Wejście:** pacjent z `pozostale = 0`; specjalista z `wystawione(W39) = 3`;
  pacjent próbuje zarezerwować niskopłatny termin tego specjalisty.
- **Wynik:** odmowa z **przyczyną „limit pacjenta"**; `LIMIT.specjalista.wystawione`
  **niezmienione = 3**; `LIMIT.pacjent.wykorzystane` **niezmienione = 10**.
- **Poz.:** 3 i 10 po odmowie (odmowa niczego nie konsumuje).
- **Neg.:** pacjent z `pozostale = 5` u specjalisty z `wystawione = 4` → rezerwacja
  **przyjęta**, `wystawione` = **4** (rezerwacja nie podnosi licznika podażowego),
  `wykorzystane` = **6**. **Dwa liczniki, cztery wartości, żadnego przecieku.**
- **Pert.:** jeden wspólny licznik → jedna z czterech liczb się rozjeżdża, czerwony.
- **Obserwacja:** dwa niezależne zapytania o liczniki.

---

### G · Zamrażanie kwoty i reguły anulacji

> `CLAUDE.md` §4 · `D-2026-08-07-18` (U-10: **niekompletny zrzut = błąd, nie dobieranie
> z kodu**) · `D-2026-08-09-09` (kształt zrzutu musi się zmienić **zanim** powstanie
> pierwsza rezerwacja).

#### F2-G-01 · Kwota zamrożona w chwili zakupu
- **Wejście:** rezerwacja przy cenie **14500 gr**; cennik podniesiony do **16500 gr**;
  odwołanie > 24 h.
- **Wynik:** `kwota_zamrozona` = **14500**; `zwrot_gr` = **14500**.
- **Poz.:** nowa rezerwacja po podwyżce → `kwota_zamrozona` = **16500**, `zwrot_gr` = **16500**.
- **Neg.:** **obie rezerwacje odwołane w tej samej sekundzie** → `[14500, 16500]`.
  Dwie różne liczby z jednego wywołania reguły.
- **Pert.:** kwota czytana z cennika bieżącego → `[16500, 16500]`, czerwony. **Allowlista.**
- **Obserwacja:** `kwota_zamrozona` z bazy + `zwrot_gr` z operacji odwołania.

#### F2-G-02 · Reguła anulacji zamrożona jako PEŁNY ZRZUT
- **Wejście:** rezerwacja `A` przy oknie **24 h**; konfiguracja zmieniona na **48 h**;
  rezerwacja `B` po zmianie; obie odwołane **30 h** przed wizytą.
- **Wynik:** `zwrot_gr(A)` = **14500**, `zwrot_gr(B)` = **0**.
- **Poz.:** dwie różne liczby w tej samej sekundzie, z tego samego wywołania reguły.
- **Neg.:** obie odwołane **50 h** przed → **14500 i 14500** (poza obu oknami).
- **Pert.:** reguła czytana z konfiguracji bieżącej → `[0, 0]`, czerwony.
  **To jest istniejąca perturbacja `zamrozenie`, rozszerzona o okno.** Allowlista.
- **Obserwacja:** `zwrot_gr` ×2 + odczyt `regula_anulacji_zamrozona` z bazy (dwa źródła).

#### F2-G-03 · Niekompletny zrzut jest błędem, nie zaproszeniem do zgadywania
- **Wejście:** rezerwacja ze zrzutem, któremu brakuje **jednego** pola.
- **Wynik:** liczba rozstrzygnięć = **0**; operacja odwołania kończy się **błędem
  wskazującym brakujące pole po nazwie**; `zwrot_gr` **nie powstaje**.
- **Poz.:** pełny zrzut → 1 rozstrzygnięcie, `zwrot_gr` = 14500.
- **Neg.:** zrzut z polem **nadmiarowym** (nieznanym) → `Q-15`; oczekiwanie domyślne:
  **przyjęty**, bo zrzut ma być czytelny po latach, a nieznane pole nie zmienia
  rozstrzygnięcia. Liczba rozstrzygnięć = **1**.
- **Pert.:** brakujące pole dobierane z konfiguracji → 1 rozstrzygnięcie zamiast błędu,
  czerwony. **Allowlista** z dosłownym fragmentem komunikatu.
- **Obserwacja:** kod błędu + liczba wyliczonych zwrotów.

#### F2-G-04 · Zrzut umie wyrazić **obie** ścieżki blokady
> `D-2026-08-09-09`: `blokada_koszyka_minut` jest dziś **pojedynczym skalarem** i nie umie
> wyrazić dwóch wartości na ścieżkę; `waznosc_linku_platnosci_dni` ma **zniknąć albo
> współistnieć**, ale nie opisywać tej samej rzeczy dwa razy (`P3`).
- **Wejście:** rezerwacja ścieżką `własna` i rezerwacja ścieżką `psycholog`.
- **Wynik:** oba zrzuty rozstrzygają `trzymane_do` **bez sięgania do konfiguracji
  bieżącej**: `10 min` i `2 dni + 10 min od otwarcia`; liczba pól zrzutu opisujących
  blokadę = **taka sama w obu** (jeden kształt, dwie wartości).
- **Poz.:** dwie różne wartości z jednego kształtu.
- **Neg.:** zrzut w **starym kształcie** (skalar) → operacja **odrzucona z nazwaniem pola**,
  liczba rozstrzygnięć = **0**. Nie ciche dobranie drugiej wartości.
- **Pert.:** stary kształt przyjmowany po cichu → 1 rozstrzygnięcie, czerwony.
- **Obserwacja:** treść zrzutu z bazy + wynik rozstrzygnięcia.
- **⚠ `Q-15`: co ze zrzutami sprzed zmiany kształtu. Dziś rezerwacji jest zero, więc
  zmiana kształtu jest DARMOWA — okno zamyka się w dniu pierwszej rezerwacji.**

#### F2-G-05 · Zamrożenie nie dotyczy dostępności
- **Wejście:** rezerwacja na `2026-09-22 10:00`; rytm zmieniony na `14:00–18:00`.
- **Wynik:** liczba wizyt = **1**, jej termin **bez zmian** (`10:00`);
  `SLOTY(2026-09-29)` = **4** ze startami `[14:00, 15:00, 16:00, 17:00]`.
- **Poz.:** termin wizyty niezmieniony i nowe starty.
- **Neg.:** `SLOTY(2026-09-22)` **po zmianie rytmu** — dzień z istniejącą wizytą:
  slot `10:00` **nie wraca do puli** (wizyta go trzyma), liczba slotów = **4**
  z zakresu 14:00–18:00, a wizyta 10:00 nadal zajmuje swoje 60 min.
- **Pert.:** zmiana rytmu przesuwa istniejące wizyty → termin wizyty inny, czerwony.
- **Obserwacja:** termin wizyty z bazy + `SLOTY`.

---

### H · Strefa czasowa: UTC w bazie, Europe/Warsaw w prezentacji

> `CLAUDE.md` §5 · spec M1/24, M2/2, M5/17 · zakres, s. 17, dosłownie:
> *„Obsługa strefy Europe/Warsaw i zmiany czasu — doba 23 i 25 godzin, zakres 15:00–20:00
> w noc przestawienia zegarów."*
> Ryzyko nazwane w specyfikacji: *„zmiana czasu zamienia bezpłatne odwołanie w płatne,
> dwa razy w roku"*.

#### F2-H-01 · Ta sama godzina lokalna, dwa różne offsety
- **Wejście:** slot `09:00` lokalnie dnia `2026-09-15` (wtorek, CEST) i `2026-11-17`
  (wtorek, CET). *(Sprostowanie 12.08: pierwsza wersja podawała `2026-11-15` — to
  **niedziela**, a rytm bazowy obejmuje pon–pt, więc przypadku nie dałoby się zbudować:
  slotu tam po prostu nie ma. Złapane przy `SZK-H-01`.)*
- **Wynik:** w bazie **`07:00:00Z`** i **`08:00:00Z`**.
- **Poz.:** dwie różne wartości UTC dla tej samej etykiety lokalnej.
- **Neg.:** obie prezentowane jako **`09:00`** — etykieta lokalna identyczna.
  Sam UTC nie dowodzi prezentacji, sama etykieta nie dowodzi zapisu.
- **Pert.:** offset zapisany na stałe (+2) → listopadowy slot ląduje `07:00Z`, czerwony.
- **Obserwacja:** kolumna z bazy **i** pole prezentacyjne z API — dwie drogi.

#### F2-H-02 · Doba 23-godzinna, zakres 15:00–20:00 (przypadek z zakresu wdrożenia)
- **Wejście:** rytm `15:00–20:00`; `SLOTY(2026-03-28)` i `SLOTY(2026-03-29)`.
- **Wynik:** **5 i 5** slotów; starty lokalne w obu dniach `[15:00 … 19:00]`;
  starty UTC: **28.03 → `[14:00Z … 18:00Z]`**, **29.03 → `[13:00Z … 17:00Z]`**.
- **Poz.:** liczba slotów **równa** i UTC **różne**.
- **Neg.:** **sama liczba nie odróżnia** — dlatego przypadek asertuje UTC. Kontrola
  negatywna: implementacja z zamrożonym offsetem daje `5 i 5` i **te same UTC** → czerwony.
- **Pert.:** offset brany z pierwszego dnia zakresu → 29.03 daje `14:00Z…`, czerwony.
- **Obserwacja:** starty UTC z odpowiedzi + przeliczenie niezależne z bazy.

#### F2-H-03 · Doba 23-godzinna, zakres obejmujący nieistniejącą godzinę
- **Wejście:** rytm `00:00–06:00`; `SLOTY(2026-03-29)` i `SLOTY(2026-03-28)`.
- **Wynik:** **5 i 6**. 29.03 starty lokalne `[00:00, 01:00, 03:00, 04:00, 05:00]` —
  **`02:00` nie istnieje**; starty UTC **ciągłe co 3600 s**: `[23:00Z(28.), 00:00Z, 01:00Z,
  02:00Z, 03:00Z]`.
- **Poz.:** 6 dnia poprzedniego (świadek, że zakres w ogóle produkuje 6).
- **Neg.:** obecność startu `02:00` lokalnie = **0 wystąpień**.
- **Pert.:** sloty generowane przez dodawanie 3600 s do etykiety lokalnej → pojawia się
  `02:00`, czerwony.
- **Obserwacja:** starty lokalne i UTC, dwie listy.

#### F2-H-04 · Doba 25-godzinna, godzina powtórzona
- **Wejście:** rytm `00:00–06:00`; `SLOTY(2026-10-25)` i `SLOTY(2026-10-26)`.
- **Wynik (rekomendacja, `Q-3`):** **7 i 6**. 25.10 dwa sloty z etykietą lokalną `02:00`,
  różne w UTC: **`00:00Z`** (CEST) i **`01:00Z`** (CET).
- **Poz.:** 6 dnia następnego.
- **Neg.:** przy rozstrzygnięciu przeciwnym (`Q-3` = „powtórzona godzina oferowana raz")
  wynik = **6 i 6**, a liczba etykiet `02:00` = **1**. Test rozróżnia oba światy liczbą.
- **Pert.:** deduplikacja po etykiecie lokalnej → 6, czerwony (przy rekomendacji).
- **Obserwacja:** starty UTC i etykiety lokalne.
- **✅ `Q-3` ROZSTRZYGNIĘTE (`ODPOWIEDZ-045`): powtórzona `02:00` oferowana DWA RAZY,
  a etykieta prezentacyjna MUSI je rozróżniać.** Wartości twarde: **7 i 6**.
  **Warunek etykiety jest częścią decyzji, nie dopiskiem** — dlatego przypadek liczy go
  osobno: liczba **rozróżnialnych** etykiet dla dwóch slotów `02:00` = **2** (np. przez
  offset albo znacznik). Etykieta niejednoznaczna = **czerwony**, mimo poprawnych 7 slotów.

#### F2-H-05 · Niezmiennik doby 25-godzinnej
- **Wejście:** wszystkie sloty `S1` dnia `2026-10-25`.
- **Wynik:** duplikatów **startu UTC** = **0**; duplikatów **etykiety lokalnej** = **1** para
  (przy `Q-3` = 7 slotów).
- **Poz.:** liczba sprawdzonych slotów = **7**.
- **Neg.:** ten sam pomiar dnia `2026-10-26` → duplikatów UTC = 0, duplikatów etykiety = **0**.
- **Pert.:** klucz slotu zbudowany z etykiety lokalnej → jeden slot ginie albo powstaje
  konflikt unikalności, czerwony.
- **Obserwacja:** zliczenie duplikatów **w bazie**, po kluczu `(specjalista_id, termin UTC)`.
  To jest ten sam klucz, na którym stoi `CLAUDE.md` §6 — i tu się rozstrzyga, czy jest
  odporny na zmianę czasu.

#### F2-H-06 · Okna reguł liczą się niezależnie od strefy pacjenta
> Spec M5/17: *„okna 24 h / 2 h liczone w Europe/Warsaw niezależnie od strefy pacjenta;
> testy na pacjencie w Ameryka/Nowy Jork"*.
- **Wejście:** dwaj pacjenci — `Europe/Warsaw` i `America/New_York` — ta sama wizyta
  `2026-09-22 09:00`, odwołanie w **tym samym momencie absolutnym** `2026-09-21 08:59:59 CEST`.
- **Wynik:** `zwrot_gr` = **14500 i 14500** — identyczne.
- **Poz.:** dwie identyczne liczby.
- **Neg.:** **prezentowana data graniczna różni się**: `2026-09-21 09:00` vs
  `2026-09-21 03:00` (NY). Wynik reguły identyczny, prezentacja różna — obie asercje
  w jednym przypadku, bo sama równość zwrotów przechodzi też wtedy, gdy strefa pacjenta
  jest ignorowana **wszędzie**, łącznie z prezentacją.
- **Pert.:** okno liczone w strefie pacjenta → `zwrot_gr` = `[14500, 0]`, czerwony.
- **Obserwacja:** `zwrot_gr` ×2 + pole prezentacyjne daty granicznej ×2.

#### F2-H-07 · Doba jako jednostka raportowa ma 23 / 24 / 25 godzin
- **Wejście:** zapytanie „sloty dnia" dla `2026-03-29`, `2026-09-15`, `2026-10-25`
  przy rytmie **całodobowym** `00:00–24:00`.
- **Wynik:** liczba slotów = **23 · 24 · 25**; suma minut zajętych = **1380 · 1440 · 1500**.
- **Poz.:** trzy liczby.
- **Neg.:** doba liczona jako `86400 s` → `24 · 24 · 24`, czerwony; dodatkowo ostatni slot
  29.03 kończyłby się `01:00` dnia następnego.
- **Pert.:** granica doby liczona w UTC → 23/25 znikają.
- **Obserwacja:** liczba slotów + suma minut, dwie miary.

---

### I · Współbieżność — **obowiązkowe** (`CLAUDE.md` §6)

> Spec M2/5: *„Przy PHP-FPM sam warunek w kodzie nie wystarcza — sprawdzenie i zapis
> muszą być w jednej transakcji z blokadą wiersza"* · M3/2: *„podwójna rezerwacja,
> której nikt nie zauważy do dnia wizyty"*.

#### F2-I-01 · 100 równoczesnych żądań o ten sam termin = **dokładnie 1 rezerwacja**
- **Wejście:** 100 równoczesnych `BLOKADA.zaloz` / `REZERWACJA.utworz` na
  `(S1, 2026-09-22 10:00)`.
- **Wynik:** wierszy rezerwacji na ten klucz = **1**; odpowiedzi „przyjęte" = **1**;
  odpowiedzi „konflikt" = **99**; suma odpowiedzi = **100**.
- **Poz.:** pojedyncze żądanie → **1** i „przyjęte".
- **Neg.:** zdjęcie unikalnego ograniczenia `(specjalista_id, termin)` → liczba wierszy
  **> 1**, test **czerwony**. Bez tego dowodu kontrola nie istnieje (`D-2026-08-07-13`).
- **Pert.:** (a) unikalne ograniczenie zdjęte; (b) transakcja bez blokady wiersza —
  **dwie osobne perturbacje**, bo to dwa różne mechanizmy i jeden nie zastępuje drugiego.
- **Obserwacja:** `SELECT count(*)` **z bazy**, nie suma odpowiedzi API.
  Odpowiedzi liczymy osobno — rozjazd między „1 wiersz" a „7 odpowiedzi przyjęte"
  jest sam w sobie znaleziskiem.
- **Baza:** PostgreSQL, nigdy SQLite (`D-2026-08-07-02`).

#### F2-I-02 · Kontrola PRZYRZĄDU: czy żądania były naprawdę równoczesne
- **Wejście:** te same 100 żądań, z barierą startową.
- **Wynik:** liczba procesów, które przekroczyły barierę **przed** pierwszym zapisem
  = **100**; rozrzut momentów startu < **50 ms**.
- **Poz.:** 100.
- **Neg.:** wymuszone wykonanie sekwencyjne → rozrzut > 1 s, a `I-01` **nadal przechodzi**
  → to jest dowód, że `I-01` bez `I-02` mierzy „100 kolejnych żądań", nie współbieżność.
- **Pert.:** bariera usunięta → liczba < 100, czerwony.
- **Obserwacja:** znaczniki czasu startu z każdego procesu, zapisane **poza** aplikacją.
- **Uzasadnienie:** `WYTYCZNE-PRACY.md` — „podejrzewaj najpierw własny przyrząd".
  Test współbieżności bez dowodu współbieżności jest deklaracją.

#### F2-I-03 · 100 żądań o 100 **różnych** terminów = 100 rezerwacji
- **Wejście:** 100 równoczesnych żądań, każde na inny termin (`S111`).
- **Wynik:** rezerwacji = **100**, konfliktów = **0**.
- **Poz.:** 100.
- **Neg.:** bez tego przypadku `I-01` przechodzi także przy implementacji, która
  **nie pozwala zarezerwować niczego**. To jest kontrola „nie zabetonowaliśmy systemu".
- **Pert.:** blokada założona na całą tabelę zamiast na wiersz → konflikty > 0 albo czas
  wykonania rośnie liniowo, czerwony.
- **Obserwacja:** liczba wierszy + czas całkowity.

#### F2-I-04 · Kolizja **przez bufor**, nie przez klucz
- **Wejście:** dwa równoczesne żądania: `10:00` i `10:30` u tego samego specjalisty
  (różne terminy — **klucz unikalny ich nie łapie**).
- **Wynik:** rezerwacji = **1**, konfliktów = **1**.
- **Poz.:** `10:00` i `11:00` równocześnie → rezerwacji = **2**, konfliktów = **0**.
- **Neg.:** przy samym ograniczeniu unikalnym wynik byłby **2 i 0** — to jest liczba,
  która pokazuje, że bufor musi mieć **własny** mechanizm współbieżny.
- **Pert.:** kontrola bufora poza transakcją → 2 rezerwacje, czerwony.
- **Obserwacja:** liczba wierszy + przeliczenie odstępów z bazy.

#### F2-I-05 · Kolizja **między usługami** o różnej długości
- **Wejście:** równocześnie: ADHD `09:00–10:30` i konsultacja `10:00–10:50`.
- **Wynik:** rezerwacji = **1**, konfliktów = **1**.
- **Poz.:** ADHD `09:00` i konsultacja `12:00` → **2 i 0**.
- **Neg.:** kontrola kolizji ograniczona do jednej usługi → **2 i 0** w przypadku
  kolidującym, czerwony.
- **Pert.:** długość usługi ignorowana przy blokadzie → 2 rezerwacje.
- **Obserwacja:** liczba wierszy + suma zajętych minut dnia (**musi wynieść 100 lub 60,
  nigdy 160**).

#### F2-I-06 · Wyścig: wygaśnięcie blokady kontra zaksięgowanie płatności
> Spec M5/1: *„»zapłacone, termin zajęty« to osobna ścieżka"*.
- **Wejście:** 100 żądań płatności docierających **dokładnie** w momencie `trzymane_do`.
- **Wynik:** rezerwacji na termin = **1 albo 0**, **nigdy 2**;
  `rezerwacje + zadania_zwrotu` = **100**.
- **Poz.:** suma = 100 (żadne żądanie nie ginie).
- **Neg.:** te same 100 żądań **w środku** okna → rezerwacji = 1, zadań = **99**
  (pozostałe to konflikty, nie zwroty) — rozróżnienie „konflikt" od „zwrot" musi być
  widoczne w liczbach.
- **Pert.:** brak idempotencji → suma ≠ 100, czerwony.
- **Obserwacja:** trzy zapytania do bazy: rezerwacje, zadania, zdarzenia.

---

### J · Przypadki brzegowe z decyzji

#### F2-J-01 · Płatność po wygaśnięciu blokady
→ **`F2-D-06`.** Pozycja zostaje w spisie, bo pochodzi wprost z listy wymaganej przez
architekta; treść stoi w grupie D, żeby jedna rzecz nie była opisana dwa razy (`P3`).
**To jest odsyłacz, nie przypadek** — nie ma własnego wyniku, kontroli ani perturbacji
i nie liczy się do sumy 75.

#### F2-J-02 · Psycholog umawia osobę **bez konta**
- **Wejście:** psycholog umawia pacjenta, którego w systemie nie ma; podaje imię,
  nazwisko, e-mail; usługa **niskopłatna** (spec: to jest reguła, nie wyjątek).
- **Wynik:** rezerwacja ze statusem „czeka na płatność" = **1**;
  rekordów pacjenta = **1**; `LIMIT.pacjent.wykorzystane` po opłaceniu = **1**;
  kont utworzonych **w tym momencie** = **0** (konto powstaje po zaksięgowaniu płatności —
  spec s. 3; kierunek `D-2026-08-09-10` tego nie zmienia, dopóki nie jest rozstrzygnięty).
- **Poz.:** 1 · 1 · 1 · 0.
- **Neg.:** ta sama osoba umówiona **drugi raz** przez psychologa → rekordów pacjenta
  **nadal 1** (nie dwa), `wykorzystane` = **2**. Bez tego licznik limitu jest fikcją —
  `D-2026-08-09-07` mierzy stan dzisiejszy: *te same dane gościa dwa razy dają **dwa rekordy***.
- **Pert.:** tożsamość pacjenta po identyfikatorze rezerwacji → 2 rekordy, czerwony.
- **Obserwacja:** liczba rekordów pacjenta z bazy + licznik z operacji `LIMIT.pacjent`.
- **⚠ `Q-16` — BLOKUJĄCE dla części zgodowej:** *kto akceptuje regulamin i zgodę
  art. 9, gdy psycholog umawia osobę bez konta*. Pozycja **otwarta u właściciela**
  (`DECYZJE-DO-PODJECIA`, „OTWARTE PO TEJ RUNDZIE" §3). Kandydaci na wynik:
  **liczba zapisanych zgód = 0** (zgody zbierane dopiero przy płatności przez pacjenta)
  albo **= 2** (psycholog potwierdza w imieniu — wtedy potrzebna osobna podstawa).
  **Nie zgaduję — przypadek czeka na rozstrzygnięcie.**

#### F2-J-03 · Rezygnacja w oknie bezpłatnym w dzień zmiany czasu — doba 25-godzinna
- **Wejście:** wizyta **`2026-10-25 12:00 CET`** (= `11:00Z`); okno 24 h;
  odwołania o `2026-10-24 12:59:59 CEST`, `13:00:00 CEST`, `13:00:01 CEST`.
- **Wynik:** `zwrot_gr` = **14500 · 14500 · 0**. Granica wypada o **13:00 czasu lokalnego
  soboty**, nie o 12:00.
- **Poz.:** trzy liczby.
- **Neg.:** odwołanie o `2026-10-24 12:30 CEST` → **14500**.
  **Implementacja „ta sama godzina dzień wcześniej" dałaby tu 0** — i to jest dokładnie
  ryzyko nazwane w specyfikacji („zamienia bezpłatne odwołanie w płatne").
- **Dodatkowo:** **prezentowana** data graniczna = `24.10.2026, 13:00` — ta sama wartość,
  którą egzekwuje serwer. Spec wypisuje ją w **trzech miejscach** (potwierdzenie,
  przypomnienie, karta wizyty); test porównuje **wszystkie trzy** ze sobą i z serwerem:
  liczba różnych wartości = **1**.
- **Pert.:** okno liczone przez dodanie „−1 dzień" do etykiety lokalnej → granica 12:00,
  wynik `[14500, 0, 0]` i cztery różne stringi daty, czerwony. **Allowlista.**
- **Obserwacja:** `zwrot_gr` ×4 + trzy pola prezentacyjne.
- **✅ `Q-4` ROZSTRZYGNIĘTE (`ODPOWIEDZ-045`): okno 24 h = 86 400 s absolutnych.**
  Uzasadnienie architekta: *reguła nie może zmieniać wartości dwa razy w roku*.
  Odczyt „ta sama godzina dzień wcześniej" staje się **kontrolą negatywną**.

#### F2-J-04 · Ta sama rezygnacja — doba 23-godzinna
- **Wejście:** wizyta **`2026-03-29 12:00 CEST`** (= `10:00Z`); odwołania o
  `2026-03-28 10:59:59 CET`, `11:00:00`, `11:00:01`.
- **Wynik:** `zwrot_gr` = **14500 · 14500 · 0**. Granica o **11:00 lokalnie**, czyli
  **godzinę wcześniej niż godzina wizyty**.
- **Poz.:** trzy liczby.
- **Neg.:** odwołanie o `2026-03-28 11:30` → **0**. Osoba, która „odwołuje dzień wcześniej
  o tej samej porze", **płaci** — to jest konsekwencja odczytu absolutnego i **musi być
  widoczna w prezentowanej dacie granicznej** (`28.03.2026, 11:00`), inaczej system obiecuje
  co innego, niż egzekwuje.
- **Pert.:** jw.
- **Obserwacja:** jw.
- **Uwaga do `Q-4`:** `J-03` i `J-04` **odchylają się w przeciwne strony** — to jest
  argument za jedną konwencją absolutną i przeciw „ta sama godzina dzień wcześniej".

#### F2-J-05 · Przełożenie: limit 2, płatność przechodzi, slot wraca natychmiast
- **Wejście:** rezerwacja `2026-09-22 10:00`; trzy kolejne `REZERWACJA.przeloz`.
- **Wynik:** przyjęte · przyjęte · **odrzucone (422)**; licznik przełożeń = **2**;
  liczba płatności = **1** (bez zwrotu i bez ponownego pobrania — spec s. 8/9/55);
  po każdym przełożeniu: stary slot wraca (`SLOTY` +1), nowy zajęty (−1).
- **Poz.:** 2 · 1.
- **Neg.:** po wyczerpaniu limitu **odwołanie nadal możliwe** → `zwrot_gr` = **14500**
  (przy > 24 h). Limit przełożeń nie zamyka drogi wyjścia.
- **Pert.:** przełożenie jako „odwołaj + zarezerwuj" → liczba płatności = 2 i zadanie
  zwrotu = 1, czerwony.
- **Obserwacja:** licznik z bazy + liczba płatności + `SLOTY` przed/po.

#### F2-J-06 · Przełożenie tylko w oknie 24 h
- **Wejście:** rezerwacja na `T+23 h`; `REZERWACJA.przeloz`.
- **Wynik:** **odrzucone (422)**; licznik przełożeń = **0**; termin **niezmieniony**.
- **Poz.:** rezerwacja na `T+25 h` → przyjęte, licznik = **1**.
- **Neg.:** przy `T+23 h` **odwołanie** daje `zwrot_gr` = **0** (spójne z `E-01`) —
  a nie błąd. Zamknięte okno to rozstrzygnięcie, nie awaria.
- **Pert.:** kontrola tylko w warstwie prezentacji → przełożenie przechodzi przez API,
  czerwony (spec M1/13: *„reguła egzekwowana wyłącznie w interfejsie jest do obejścia
  zapytaniem do API"*).
- **Obserwacja:** licznik i termin z bazy.

#### F2-J-07 · Wniosek o zwolnienie z opłaty blokuje termin do decyzji
- **Wejście:** psycholog składa wniosek na termin `2026-09-22 10:00`.
- **Wynik:** `SLOTY` dla innych = **3** (termin zablokowany); `trzymane_do` = **brak
  wartości / do decyzji**; wysłanych linków płatności do pacjenta = **0** (spec s. 10:
  *„pacjent nie dostaje w tym momencie żadnego linku ani informacji"*).
- **Poz.:** 3 i 0.
- **Neg.:** po **zgodzie** koordynatora: rezerwacja = 1, kwota = **0 gr**, wywołań Stripe
  = **0**. Po **odmowie**: `Q-17` — slot zwolniony (`SLOTY` = 4) albo link wysłany
  (linków = 1). Dwie liczby, jedna decyzja.
- **Pert.:** wniosek nieblokujący terminu → 4, czerwony.
- **Obserwacja:** `SLOTY` + liczba wysyłek ze śladu.

#### F2-J-08 · Usługa 0 zł (asystent zdrowienia) — ścieżka bez płatności
- **Wejście:** rezerwacja asystenta zdrowienia.
- **Wynik (rekomendacja, `Q-18`):** rezerwacja potwierdzona **natychmiast** = **1**;
  wywołań operatora płatności = **0**; blokad przejściowych = **0**;
  `kwota_zamrozona` = **0 gr** (pole **istnieje** i ma wartość 0 — nie `null`).
- **Poz.:** 1 · 0 · 0 · 0.
- **Neg.:** rezerwacja pełnopłatna → wywołań operatora = **1**, blokad = **1**.
- **Pert.:** ścieżka 0 zł prowadzona przez operatora → wywołań = 1, czerwony
  (spec M5/1: *„usługi 0 zł omijają Stripe"*).
- **Obserwacja:** liczba wywołań atrapy operatora + `kwota_zamrozona` z bazy.

---

### K · Kontrole nad kontrolami (przyrząd)

> **Rejestr wyjątków od `K-01` — cztery pozycje, wszystkie w tej grupie.**
> Kontrole `K-01`…`K-04` **nie mają własnej perturbacji** i to jest decyzja, nie przeoczenie.
> **Powód:** perturbacja kontroli-nad-kontrolami jest sama kontrolą, która wymaga
> perturbacji — rekurencja nie ma dna, a każdy jej poziom kosztuje tyle samo, co poziom
> niżej. **Ucinam ją na pierwszym poziomie**, świadomie i jawnie.
> **Zamiast perturbacji każda z tych czterech ma KIERUNEK ODWROTNY** wpisany w kontrolę
> negatywną: dopisanie przypadku bez perturbacji, mutacja nietrafiająca, uruchomienie na
> pustej bazie, obserwacja tą samą drogą co mechanizm. **To jest słabszy dowód niż
> perturbacja i mówię to wprost**, zamiast udawać kompletność.
> **Warunek znoszący:** gdy zestaw perturbacji F2 przestanie być uruchamiany ręcznie
> i wejdzie do bramki, `K-01`…`K-04` dostają perturbacje tak samo jak reszta.

#### F2-K-01 · Każdy przypadek ma perturbację albo jawny wpis „bez perturbacji"
- **Wynik:** liczba przypadków bez przypisanej perturbacji **i bez wpisu w rejestrze
  wyjątków** = **0**.
- **Poz.:** liczba przypadków objętych = liczba przypadków w planie (podać obie liczby).
- **Neg.:** przypadek dopisany bez perturbacji → liczba = 1, czerwony.
- **Obserwacja:** zestawienie **trzech zbiorów** — wszystkie przypadki · objęte
  perturbacją · zadeklarowane wyjątki — liczone z plików, nie z tego spisu
  (`D-2026-08-09-12`: nieobecność nie niesie intencji, więc porównujemy zbiory).
- **Uzasadnienie:** `D-2026-08-07-13` — kontrola bez dowodu czerwieni jest traktowana
  jak nieistniejąca. `D-2026-08-09-12` — **koszt wyjątku musi równać się kosztowi
  zgodności**, więc wpis „bez perturbacji" wymaga powodu i warunku znoszącego.

#### F2-K-02 · Perturbacja, która nie trafiła, przerywa błędem
- **Wynik:** liczba perturbacji bez **dowodu mutacji** = **0**;
  liczba perturbacji zaliczonych przy **milczącej czerwieni** (zero wierszy wyjścia) = **0**.
- **Poz.:** dowód mutacji obecny dla każdej.
- **Neg.:** podstawienie mutacji nietrafiającej → przerwanie z błędem, nie sukces.
- **Obserwacja:** **surowe wyjście zapisane do pliku**, nie strumień przefiltrowany
  `grep`-em (`D-2026-08-07-22`: filtr wyjścia jest częścią pomiaru i już raz ukrył linię
  diagnostyczną dodaną specjalnie po to, żeby rozstrzygnąć wynik).
- **Uzasadnienie:** `D-2026-08-07-18` (U-2, U-3), `D-2026-08-07-22`.

#### F2-K-03 · Świadek niepustego zbioru dla każdego oczekiwanego zera
- **Wynik:** liczba przypadków oczekujących `0` **bez** asercji „miałem czego szukać"
  = **0**. Seed: specjalistów = **111**, slotów w oknie 30 dni > **0** (podać liczbę).
- **Poz.:** liczba przypadków oczekujących `0` **z** asercją świadka = liczba wszystkich
  takich przypadków (podać obie liczby, nie samą różnicę).
- **Neg.:** uruchomienie całej suity na **pustej bazie** musi dać **czerwień** we
  wszystkich przypadkach grupy A oczekujących zera — jeśli daje zieleń, świadka nie ma.
- **Obserwacja:** liczność seeda **osobnym zapytaniem do bazy**, nie przez operację `SLOTY`
  (inaczej kontrola świadka dzieli mechanizm z przedmiotem — kształt `C1`(a)).
- **Uzasadnienie:** `U-1` — dwie asercje „miałem czego szukać" uratowały kontrolę §2.

#### F2-K-04 · Ścieżka obserwacji różna od mechanizmu — zadeklarowana, nie domniemana
- **Wynik:** liczba przypadków bez zadeklarowanej ścieżki obserwacji = **0**;
  liczba przypadków, w których obserwacja idzie **tą samą** drogą co mechanizm i nie ma
  wpisu wyjątku = **0**.
- **Poz.:** liczba przypadków z zadeklarowaną ścieżką = **75** (wszystkie poza odsyłaczem
  `J-01`) — suma podana razem z rozbiciem per grupa, bo sama suma nie jest dowodem.
- **Neg.:** przypadek czytający wynik z tej samej operacji, którą bada → wpis w rejestrze
  albo czerwień.
- **Obserwacja:** przegląd deklaracji **per przypadek**, zestawiony z listą operacji z §4 —
  dwie listy, nie jedna.
- **Uzasadnienie:** `D-2026-08-08-25` — trzy kształty `C1`; kształt (b) „wspólny klucz"
  jest najtrudniejszy do zauważenia, bo drogi bywają poprawnie rozdzielone, a odpowiedź
  i tak jest z góry ustalona.

---

### L · Wydajność — noga bramki F2

#### F2-L-01 · Wyszukiwarka: 30 dni × 111 specjalistów < 300 ms
- **Wejście:** `S111`, rozgrzany cache, 20 przebiegów.
- **Wynik:** **p95 < 300 ms**; liczba zapytań SQL **stała**: przy 1 i przy 111
  specjalistach **ta sama liczba** (bez N+1).
- **Poz.:** p95 podane liczbą, nie „szybko".
- **Neg.:** to samo zapytanie dla **1** specjalisty → p95 wyraźnie niższy, ale
  **liczba zapytań SQL identyczna**. Czas sam nie odróżnia N+1 od wolnej maszyny.
- **Pert.:** usunięcie indeksu „pierwszy wolny termin" **oraz** — osobno — wyłączenie
  materializacji slotów. Dwie mutacje, bo to dwa mechanizmy; p95 rośnie wielokrotnie
  i liczba zapytań rośnie z liczbą specjalistów. Bez tego dowodu nie wiadomo, czy
  próg 300 ms w ogóle cokolwiek mierzy.
- **Obserwacja:** czas z zewnątrz procesu + licznik zapytań z instrumentacji.
- **Uwaga:** spec liczy naiwną implementację na **3330 dni-osób na żądanie** — liczba
  zapytań jest tu ważniejszą miarą niż sam czas, bo czas na maszynie dewelopera kłamie.

#### F2-L-02 · Grafik miesięczny: 35 dni × 111 = 3885 kombinacji < 300 ms
- **Wynik:** **p95 < 300 ms**; liczba kombinacji policzona = **3885** (świadek zakresu).
- **Poz.:** 3885 — bez tej liczby „szybko" znaczy tylko tyle, że policzono mniej.
- **Neg.:** widok miesięczny dla **1** specjalisty → 35 kombinacji, liczba zapytań
  **taka sama** jak przy 111.
- **Pert.:** tabela agregatów dziennych wyłączona → p95 idzie w sekundy (spec liczy
  **3885 kombinacji dzień × specjalista** i mówi wprost, że liczenie na żywo „idzie
  w sekundy").
- **Obserwacja:** jw.

#### F2-L-03 · Zimny cache nie zmienia **wyniku**, tylko czas
- **Wejście:** to samo zapytanie przy cache zimnym i rozgrzanym.
- **Wynik:** różnica symetryczna zbiorów slotów = **0**; czas różny.
- **Poz.:** 0.
- **Neg.:** po zapisie poprawki różnica przy **zimnym** cache = 1, przy **rozgrzanym**
  bez unieważnienia = 0 → to jest para do `A-10` i pokazuje, że cache **maskuje** zmianę.
- **Pert.:** unieważnianie wyłączone → różnica = 1 utrzymuje się także po zapisie,
  czerwony. Cache oddający nieaktualną dostępność kończy się podwójną rezerwacją,
  więc ta perturbacja idzie z **allowlistą** (K-5).
- **Obserwacja:** dwa zbiory slotów, porównanie po UTC.

---

## 6 · Macierz pokrycia — reguły F2 → przypadki

Źródło kolumny „reguła": `PLAN-FAZ.md` §F2, zakres wdrożenia M1/2, M1/4, M1/6, M1/24,
M2/2, M2/4, M2/5, M3/2, M5/16, M5/17 oraz `CLAUDE.md` §§1, 4, 5, 6, 14, 15.

| # | reguła F2 | źródło | przypadki |
|---|---|---|---|
| 1 | złożenie rytmu tygodniowego | M1/2, M2/2 | A-01, A-09 |
| 2 | poprawki jako **osobny byt**, jednorazowe | `CLAUDE.md` §5, M2/2 | A-02, A-03, A-06 |
| 3 | urlop wygrywa z rytmem **i** z poprawkami, we wszystkich usługach | spec s. 36 | A-04, A-05, A-07, A-08 |
| 4 | bufor 10 min | spec s. 25/35/50 | B-01, B-04, **B-05**, A-03(neg), I-04 |
| 5 | sloty 50+10 i 90+10; 90 zdejmuje dwa sloty | M1/2, M2/2 | A-01, B-02, B-03, I-05 |
| 6 | horyzont 2 h | spec s. 5/24/35/49 | C-01 |
| 7 | horyzont 30 dni (pacjent) | jw. | C-02 |
| 8 | horyzont 7 dni (wystawianie), **w API** | M3/2 | C-03 |
| 9 | termin ręczny spoza grafiku | spec s. 11 | C-04, C-05 |
| 10 | blokada 10 min (ścieżka własna) | spec s. 3/25/50/58 | D-01, D-02 |
| 11 | blokada 2 dni (ścieżka psychologa) | spec s. 10/11, `D-2026-08-09-15` | D-03, D-09 |
| 12 | **drugi stopień: 10 min od otwarcia linku** | `D-2026-08-09-15` | D-04 |
| 13 | blokada dwustopniowa, zegar po potwierdzeniu | `D-2026-08-09-08`, `-11` §4 | D-02 |
| 14 | `okno = min(okno_ścieżki, czas_do_wizyty − margines)` | `D-2026-08-09-08` | D-05 |
| 15 | płatność po wygaśnięciu **nie tworzy wizyty** | `D-2026-08-09-08` | D-06, I-06, J-01 |
| 16 | limit równoczesnych nieopłaconych blokad | `D-2026-08-09-08` | D-07 |
| 17 | wygaśnięcie blokady daje znać pacjentowi | `D-2026-08-09-08` | D-08 |
| 18 | okno 24 h **zegarowo** | `D-2026-08-09-06` | E-01, E-02, E-04, J-03, J-04 |
| 19 | brak progów pośrednich | spec s. 7 | E-03 |
| 20 | termin wraca do puli **także** przy późnym odwołaniu | spec, tabela odwołań | E-01(neg) |
| 21 | limit **10 na pacjenta, sumarycznie**, tylko niskopłatne | `D-2026-08-09-05`, `-08` | F-01…F-05, F-10 |
| 22 | limit **4/tydzień ISO na specjalistę**, reset pon. 00:00 Warsaw | M2/4 | F-06, F-07 |
| 23 | limit podażowy egzekwowany **przy wystawianiu** | spec s. 25/35/50/60 | F-08 |
| 24 | definicja „wystawiony termin niskopłatny" | spec s. 17 | F-09 (`Q-14`) |
| 25 | zamrożenie **kwoty** | `CLAUDE.md` §4 | G-01 |
| 26 | zamrożenie **reguły anulacji** (pełny zrzut) | `CLAUDE.md` §4, U-10 | G-02, G-03, G-04 |
| 27 | zamrożenie nie dotyczy dostępności | wnioskowane z §4 | G-05 |
| 28 | UTC w bazie, Europe/Warsaw w prezentacji | `CLAUDE.md` §5, M5/17 | H-01, H-06 |
| 29 | doba 23-godzinna | M2/2, M3/2 | H-02, H-03, H-07, J-04 |
| 30 | doba 25-godzinna | jw. | H-04, H-05, H-07, J-03, D-09 |
| 31 | okna reguł niezależne od strefy pacjenta | M5/17 | H-06 |
| 32 | **100 żądań = 1 rezerwacja** | `CLAUDE.md` §6 | I-01, I-02 |
| 33 | kolizja przez bufor i przez drugą usługę | M2/5 | I-04, I-05 |
| 34 | wyścig wygaśnięcia z płatnością | M5/1 | I-06 |
| 35 | jedna funkcja slotów dla trzech konsumentów | M2/2 | A-09 |
| 36 | materializacja / unieważnianie per specjalista-dzień | M1/4, `PLAN-FAZ` F2 | A-10, L-03 |
| 37 | wydajność < 300 ms, bez N+1 | M1/4, M3/3 | L-01, L-02 |
| 38 | przełożenie: limit 2, płatność przechodzi | spec s. 8/9/55 | J-05, J-06 |
| 39 | psycholog umawia osobę bez konta | `D-2026-08-09-08`, rozstrzygnięcie właściciela | J-02 (`Q-16`) |
| 40 | wniosek o zwolnienie z opłaty blokuje termin | spec s. 10/11 | J-07 |
| 41 | usługa 0 zł omija operatora płatności | M5/1 | J-08 |
| 42 | reguła egzekwowana na serwerze, nie w interfejsie | `CLAUDE.md` §1 | C-03, J-06, F-08 |
| 43 | testy **liczą wartości** | `CLAUDE.md` §15 | wszystkie |

**Reguł: 43 · przypadków: 75 (+1 odsyłacz `J-01`) · reguł bez przypadku: 0.**

**Rozbicie per grupa** — bo suma bez rozbicia nie jest dowodem (`WYTYCZNE-PRACY.md`,
„suma zielonych nie jest dowodem"):
`A 10 · B 5 · C 5 · D 9 · E 4 · F 10 · G 5 · H 7 · I 6 · J 7(+1) · K 4 · L 3`.

**Zmiana wobec wersji z 12.08 rano: +1 przypadek (`B-05`)** — mógł powstać dopiero po
rozstrzygnięciu `Q-1`, bo wcześniej miał dwie wartości oczekiwane. Wzrost liczby wymaga
wyjaśnienia tak samo jak spadek (`WYTYCZNE-PRACY.md`: *liczba, która rośnie, uspokaja*).

**Przypadki bez perturbacji: 4** — wyłącznie `K-01`…`K-04`, z powodem i warunkiem
znoszącym w nagłówku grupy K. **Nie „zero"** — cztery, wypisane.

---

## 7 · Czego plan świadomie NIE pokrywa

Wpis „bez pokrycia" jest **wynikiem, nie porażką** — ale musi mieć powód i warunek
znoszący, inaczej milczenie nie niesie intencji (`D-2026-08-09-12`, klasa `D6`).

| co | dlaczego nie | warunek znoszący |
|---|---|---|
| **kredyt za odsprzedany termin** | poza zakresem pierwszego wdrożenia (`D-2026-08-09-01`, `ND-01`) | wraca razem z historią finansową pacjenta |
| **integracja Stripe, webhooki, rekoncyliacja** | to jest **F3**; tutaj kończymy na „płatność zaksięgowana" jako wejściu | plan testów F3 |
| **naliczanie wynagrodzenia i prowizji** | **F4/F5**; F2 rozstrzyga wyłącznie `godzina_platna_dla_specjalisty` jako flagę | plan testów F4 |
| **dziennik decyzji: niezmienialność na poziomie bazy** | **F5**; `F-05` liczy tylko **wpisy**, nie testuje odebrania UPDATE/DELETE | plan testów F5 |
| **kanał powiadomień (mail/SMS)** | **F6**; `D-08` liczy **zaplanowane** powiadomienie, nie wysyłkę | plan testów F6 |
| **lista rezerwowa (okno 4 h, zegar 21:00–8:00)** | **F5**, choć dzieli mechanizm zwalniania terminu z F2 | plan testów F5; **do skrzyżowania z `I-06`** — spec nazywa to „najbardziej podatnym na błędy miejscem w całym module" |
| **wydarzenia grupowe** | **F5** | plan testów F5 |
| **WCAG / interfejs** | **F7**, i z definicji nie jest liczbą po stronie API | plan testów F7 |
| **odczyt „dni robocze" dla okna 24 h** | odrzucony decyzją właściciela (`D-2026-08-09-06`); testujemy tylko, że **nie** obowiązuje (`E-02`) | nowa decyzja właściciela |

---

## 8 · Pytania do architekta

Zgodnie z zasadą „pytania o intencję specyfikacji → ZLECENIE, nie zgadywanie".
Pełna treść: `docs/ZLECENIA/ZLECENIE-045.md` (kanał żyje w głównym drzewie roboczym,
`D:\KOD\Niepodzielni\gabinet`, nie na tej gałęzi — powód w tamtym pliku §4.2).

### 8.1 Blokujące wartość liczbową — **9 z 10 rozstrzygniętych 12.08**

Rozstrzygnął architekt w `ODPOWIEDZ-045` §1, przyjmując rekomendację w każdej z dziewięciu.
**Wartości będące parametrami operacyjnymi wchodzą jako konfiguracja wersjonowana w bazie**
(`CLAUDE.md` §14), nie jako stałe w kodzie — co ma **konsekwencję dla kształtu testu**,
opisaną w §8.3.

| # | pytanie | rozstrzygnięcie | gdzie działa |
|---|---|---|---|
| **Q-1** | bufor a koniec zakresu | ✅ **wlicza się** → `09:00–12:59` = **3 sloty** | `B-05` (nowy), `A-01` |
| **Q-3** | powtórzona `02:00` przy dobie 25 h | ✅ **dwa razy**, etykieta **musi rozróżniać** | `H-04`, `H-05` |
| **Q-4** | okno 24 h | ✅ **86 400 s absolutnych** | `E-01`, `J-03`, `J-04` |
| **Q-8** | krótka blokada wstępna | ✅ **10 min** (parametr) | `D-02` |
| **Q-9** | otwarcie linku | ✅ **`max(2 dni, otwarcie + 10 min)`** | `D-04` |
| **Q-10** | margines `M` | ✅ **2 h** (parametr) | `D-05` |
| **Q-12** | limit równoczesnych blokad | ✅ **2** (parametr) | `D-07` |
| **Q-14** | „wystawiony termin niskopłatny" | ✅ **slot otwarty**, niezależnie od rezerwacji | `F-09` |
| **Q-16** | zgody przy umawianiu przez psychologa osoby bez konta | 🔴 **OTWARTE — właściciel.** Na liście spotkania z Fundacją | `J-02` **czeka** |
| **Q-19** | „2 dni" | ✅ **48 h absolutnych** | `D-09` |

**Skutek dla planu:** każdy z dziewięciu przypadków ma dziś **jedną** wartość oczekiwaną,
a odrzucony odczyt stał się **kontrolą negatywną** — czyli mocniejszym testem niż przed
rozstrzygnięciem. **`J-02` pozostaje jedynym przypadkiem bez pełnej wartości oczekiwanej**
(część zgodowa; liczby dotyczące tożsamości pacjenta i licznika limitu są twarde).

### 8.2 Nieblokujące (przyjmuję rekomendację, proszę o potwierdzenie)

| # | pytanie | przyjmuję |
|---|---|---|
| **Q-2** | Rytm jest per **kategoria** (pełno/nisko) czy per **usługa**? Gdzie mieszka asystent zdrowienia i ADHD? | per **kategoria**; ADHD w pełnopłatnej, asystent w niskopłatnej — spec pokazuje **dwie** zakładki dostępności |
| **Q-5** | Nakładające się zakresy rytmu w jednym dniu — odrzucać czy scalać? | **odrzucać przy zapisie** z nazwaniem kolizji |
| **Q-6** | Czy **termin ręczny** podlega horyzontowi 7 dni? Czy koordynator też? | **nie** — horyzont dotyczy wystawiania **dostępności**; koordynator **tak** (jedna reguła dla wystawiania) |
| **Q-7** | Konwencja domknięcia okien (K-1) | okna **domknięte od strony uprawnienia** |
| **Q-11** | Płatność po wygaśnięciu: kiedy **zwrot**, a kiedy **zadanie**? | termin zajęty → **zadanie zwrotu**; termin wolny → **zadanie dla koordynatora**; wizyta w obu razach **nie powstaje automatycznie** |
| **Q-13** | Koordynator obniża limit podażowy poniżej liczby wystawionych | wystawione **zostają**, nowe odrzucane |
| **Q-15** | Zrzuty reguł w **starym kształcie** (sprzed zmiany z `D-2026-08-09-09`) | dziś rezerwacji jest **zero** → kształt przestawiamy **teraz**; stary kształt **odrzucany z nazwaniem pola**, nigdy dobierany |
| **Q-17** | Odmowa wniosku o zwolnienie z opłaty: zwolnić blokadę czy wysłać link? | **zwolnić blokadę** + osobna decyzja koordynatora o linku |
| **Q-18** | Usługa 0 zł — czy w ogóle występuje blokada? | **nie** — rezerwacja potwierdzana natychmiast, `kwota_zamrozona = 0` |
| **Q-20** | Kierunek „konto zamiast gościa" (`D-2026-08-09-10`) — czy F2 ma go zakładać? | **nie zakładam**; grupa F pisana przeciw **pacjentowi jako bytowi**, więc przeżyje obie wersje |
| **Q-21** | Kto i kiedy ustala **kontrakt API F2** (§4) — potrzebny na wejście etapu B | KOD-SILNIK, przed pierwszym testem etapu B |

**`Q-21` zamknięte w `ODPOWIEDZ-045` §1:** pierwsze zadanie F2 sesji KOD-SILNIK to
**kontrakt operacji API**, drugie — **przestawienie kształtu zamrożonego zrzutu**
(`D-2026-08-09-09`) zanim powstanie pierwsza rezerwacja.

### 8.3 ⚠ „Test ma czytać konfigurację" — z zastrzeżeniem, bez którego test staje się tautologią

`ODPOWIEDZ-045` §1 mówi: *wartości będące parametrami operacyjnymi wchodzą jako
konfiguracja wersjonowana w bazie, nie stałe w kodzie — **test ma czytać konfigurację***.

**Zgadzam się co do wejścia i nie zgadzam co do wyniku** — a różnica jest tą samą klasą,
którą opisuje `D-2026-08-08-25`, kształt **(b) „wspólny klucz"**: kontrola pyta o klucz,
którym operuje badana czynność, więc odpowiedź jest **z góry ustalona** i kontrola
nie może zaświecić.

Gdyby test **wyliczał wartość oczekiwaną z konfiguracji**:

```
bufor := konfiguracja('bufor_min')            # 10
oczekiwane := floor(240 / (50 + bufor))       # 4
asercja: liczba_slotow == oczekiwane
```

…to zmiana `bufor_min` na `20` daje `oczekiwane = 3`, silnik zwraca `3`, **test przechodzi**.
Reguła „bufor 10 minut" przestałaby istnieć, a bramka nadal świeciłaby zielono.

**Rozstrzygnięcie przyjęte w tym planie — rozdzielenie dwóch ról liczby:**

1. **Konfiguracja jest WEJŚCIEM** — test czyta ją, żeby zbudować scenariusz i żeby nie
   wpisywać parametru w dwóch miejscach (`P3`).
2. **Wartość oczekiwana jest LITERAŁEM** wyprowadzonym ze specyfikacji, nigdy
   z konfiguracji. `4` w `A-01` jest zapisane jako `4`.
3. **Każdy parametr dostaje KOTWICĘ** — osobny, jednozdaniowy przypadek wiążący wartość
   konfiguracji ze **źródłem w specyfikacji**. To jest jedyne miejsce, w którym literał
   parametru wolno porównać z konfiguracją, i jedyne, które pada po jej zmianie.

Bez punktu 3 zmiana konfiguracji jest **niewykrywalna**; bez punktu 2 jest **niewykrywalna
podwójnie**, bo obie strony porównania jadą tą samą drogą. Kotwice: `SZKIELETY-F2.md` §2.

---

## 9 · Etap B — co się zmienia

- Testy powstają **wyłącznie w `tests/`**, przeciw **kontraktom** (§4), nigdy przeciw
  wnętrzu. Test, który sięga po klasę silnika, zamienia się w test-z-kodu i traci powód,
  dla którego ten plan powstał.
- Baza: **PostgreSQL**, nigdy SQLite (`D-2026-08-07-02`) — trzy reguły F2 (unikalność
  `(specjalista_id, termin)`, blokada wiersza, 100 żądań) są regułami **bazy**, nie aplikacji.
- Sterowniki podmieniane w suicie: **kolejka wchodzi do gry w F2** razem z materializacją
  slotów (`D-2026-08-08-27` §b) — `QUEUE_CONNECTION=sync` przestaje wystarczać i musi
  dostać prawdziwy sterownik albo jawny wpis na listę „bez pokrycia".
- Każdy przypadek dostaje perturbację **przed** uznaniem za zrobiony (`K-01`).
- Kolejność: **I-01/I-02 najpierw**. To jedyny przypadek, który jest wprost wymagany przez
  `CLAUDE.md`, i jedyny, którego brak przechodzi niezauważony do dnia wizyty.

---

**Autor:** sesja TESTY · **Kontrola:** ten plan nie był weryfikowany niezależnie.
„Zrobione" = zweryfikowane przez sesję, która go nie pisała (`WYTYCZNE-PRACY.md` §2).
