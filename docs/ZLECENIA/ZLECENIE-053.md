# ZLECENIE-053 · 12.08.2026 · OD sesji TESTY DO architekta

**Meldunek: grupa J domknięta.** Numer przydzielony w `ODPOWIEDZ-052` §5.
S-3: pozycję zamyka ten plik.

**Produkt na gałęzi `testy-plan-f2`** (wypchnięte, `5526792`):

```
git -C d:/KOD/Niepodzielni/gabinet show testy-plan-f2:docs/testy/SZKIELETY-F2.md
```

---

## 1 · Zrobione

**Szkieletów: 62 → 68.** Komplet formatu sprawdzony maszynowo (`ARRANGE · ACT · ASSERT ·
NEG · PERT · OBS`, każde dokładnie raz — 68/68).

`A 10 · B 5 · C 5 · D 9 · E 4 · F 10 · G 5 · H 7 · I 6 · J 7`

**Bilans wobec planu — podaję, bo suma bez rozbicia nie jest dowodem:**

```
przypadków w planie:            75   (+1 odsyłacz J-01, nie jest przypadkiem)
szkieletów:                     68
bez szkieletu:                   7   =  K (4) + L (3)
```

**Nic nie zostało po drodze.** Siedem brakujących to dokładnie te dwie grupy, które
świadomie czekają — nie „reszta".

---

## 2 · `J-03` / `J-04` — para, która jest dowodem, nie ilustracją

Oba szkielety badają tę samą regułę w dwóch dobach przestawienia zegarów i **odchylają
się w PRZECIWNE strony**:

| | wizyta | granica 24 h (lokalnie) | co robi odczyt „ta sama godzina dzień wcześniej" |
|---|---|---|---|
| `J-03` | 25.10 **12:00 CET** | sobota **13:00 CEST** | granica o 12:00 → odwołanie o 12:30 **traci 14500 gr**, choć jest w oknie |
| `J-04` | 29.03 **12:00 CEST** | sobota **11:00 CET** | granica o 12:00 → odwołanie o 11:30 **dostaje zwrot**, choć zostało 23,5 h |

**To jest dowód własności, dla której rozstrzygnąłeś `Q-4` na odczyt absolutny:** odczyt
„ta sama godzina" daje raz 25 h, raz 23 h — czyli **reguła po cichu zmienia wartość dwa
razy w roku**, w przeciwne strony. Pojedynczy przypadek tego nie pokazuje; para pokazuje.

**Oba szkielety asertują też PREZENTOWANĄ datę graniczną** — spec wypisuje ją w trzech
miejscach (potwierdzenie, przypomnienie 24 h, karta wizyty), więc test liczy: **liczba
różnych wartości = 1**, i porównuje ją z tym, co egzekwuje serwer. Sam poprawny zwrot nie
wystarcza: **pacjent, któremu ekran obiecał 12:00, a serwer egzekwuje 13:00, dowiaduje się
o różnicy po utracie pieniędzy.**

---

## 3 · ⚠ `Q-22` — NOWE, wyszło przy `SZK-J-07`. Nieblokujące, piszę wg rekomendacji

Plan mówił przy wniosku o zwolnienie z opłaty: *„po zgodzie koordynatora: rezerwacja = 1,
**kwota = 0 gr**"*. **To zdanie jest niejednoznaczne i dopiero szkielet to pokazał** —
`kwota_zaplacona` i `kwota_zamrozona` to dwie różne rzeczy i **właśnie tutaj się
rozjeżdżają**.

| kandydat | `kwota_zamrozona` | co z tego wynika |
|---|---|---|
| **A (rekomendacja)** | **5500** — cena usługi | zwrot przy odwołaniu = 0 (pacjent nic nie wpłacił), a **raport grantowy widzi dopłatę fundacji** |
| B | **0** — tyle, ile pacjent zapłacił | zwrot też 0, ale **fundacja traci w sprawozdaniu ślad po własnym wkładzie** |

Spec M4/8 liczy dopłatę **z cennika z dnia wizyty**, a `CLAUDE.md` §11 stawia raport
grantowy wysoko. Przy odczycie B wizyty zwolnione z opłaty **znikają z budżetu dopłat** —
a to jest dokładnie ta kategoria, którą fundacja finansuje z dotacji.

**Piszę wg rekomendacji A.** Gdyby rozstrzygnięcie poszło na B, zmiana dotyczy jednej
asercji w `SZK-J-07`.

**Skutek uboczny, który już jest w szkieletach:** `J-07` i `J-08` stoją obok siebie
celowo — w `J-08` **cena usługi jest zerem**, w `J-07` zeruje się **przelew**.
Implementacja, która utożsamia „pacjent płaci 0" z `kwota_zamrozona = 0`, **przechodzi
`J-08`** i psuje sprawozdanie. Bez tej pary nic by tego nie złapało.

---

## 4 · Drobiazg z `J-05`, wart zapamiętania

Przy przełożeniu terminu **liczba wolnych slotów się nie zmienia** — przed `3`, po `3`.
Test liczący sloty przechodzi więc także wtedy, gdy przełożenie **nie zrobiło nic**.
Asercja pyta o **zbiór**: `10:00 ∈ wolne` **oraz** `11:00 ∉ wolne`.

To ta sama klasa co `H-02` (liczba slotów identyczna po obu stronach zmiany czasu,
różnią się UTC). **Zapisuję sobie jako wzorzec: gdy operacja przenosi coś z miejsca na
miejsce, licznik jest niewrażliwy z definicji — mierzy się zbiór albo różnicę zbiorów.**

---

## 5 · Co dalej

**Zbiór szkieletów jest domknięty w każdej grupie poza `K` i `L`.**

- **`L` czeka na kontrakt API** (tryb trójstronny, potwierdzony w `ODPOWIEDZ-052` §5) —
  mierzy czas i liczbę zapytań, czyli własności implementacji. Instrumentacji się nie zgaduje.
- **`K` czeka na `L`**, bo mierzy **zbiór szkieletów**. Napisana dziś, rosłaby pod własnymi
  asercjami — a kontrola, która sama wytwarza stan, o który pyta, to kształt `C1`(c).

**Wynika z tego kolejność, której nie da się odwrócić:** kontrakt → `L` → `K` → dopiero
wtedy zbiór jest zamknięty i `K-01` (*„liczba przypadków bez perturbacji = 0 albo jawny
wpis"*) mierzy coś stałego.

**Nie stoję (S-2).** Do czasu kontraktu biorę pracę, która go nie wymaga:

1. **Przegląd adwersarialny własnych szkieletów** — przejście 68 pozycji z jednym
   pytaniem: *czy ten szkielet przechodzi także wtedy, gdy reguła nie działa?*
   Pięć pomyłek, które dotąd znalazłem, wyszło **wyłącznie przy przekładzie**; przegląd
   pod tym jednym kątem jest tańszy niż czekanie, aż znajdzie je etap B.
   **Jeśli wolisz, żebym tego nie robił bez zlecenia — powiedz, wezmę co innego.**
2. Uzupełnienie **11 kotwic** jest świadomie **poza** tą listą — zatwierdziłeś plan spłaty
   „kotwica razem z pierwszym testem grupy", więc dopisanie ich teraz łamałoby własne
   ustalenie.

**Czekam:** `Q-16` (G7) · `Q-22` (Twoje, nieblokujące) · kontrakt operacji API.

**Sprzeczne polecenia w tej rundzie:** brak.

---

**Meldunek kolejny:** proszę o numer przy następnej odpowiedzi.
