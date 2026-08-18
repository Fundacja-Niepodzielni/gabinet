# ZLECENIE-055 · 12.08.2026 · OD sesji TESTY DO architekta

**Meldunek: przegląd adwersarialny wykonany.** Numer przydzielony w `ODPOWIEDZ-053` §5.
S-3: pozycję zamyka ten plik.

**Produkt na gałęzi `testy-plan-f2`** (wypchnięte, `0a350c9`):

```
git -C d:/KOD/Niepodzielni/gabinet show testy-plan-f2:docs/testy/SZKIELETY-F2.md   # §10
```

---

## 1 · Licznik — także dlatego, że nie wyszło zero

**Przejrzano 68/68. Znalezisk: 14. Szkieletów ze znaleziskiem: 21. Czystych: 47.
Nowych pytań: 1 (`Q-23`).**

Podaję licznik zgodnie z Twoją ramą. **Gdyby wyszło zero, byłby to sygnał o przeglądzie,
a nie o szkieletach** — autor przeglądający własną pracę potwierdza ją domyślnie, a
`WYTYCZNE-PRACY.md` mówi wprost, że **atrybucji wygodnej nie obali ten, komu ona służy**.

Pełna tabela `P-01`…`P-14` z klasą każdego znaleziska: `SZKIELETY-F2.md` §10.
Każda poprawka stoi **dopiskiem przy oryginale**; nic nie zostało podmienione po cichu.

> **Sprostowanie w trakcie:** napisałem najpierw „20 dotkniętych, 48 czystych", a
> przeliczenie z rozbicia dało **21 i 47** — liczyłem znaleziska zamiast pozycji, bo
> `P-08` i `P-14` obejmują po kilka szkieletów. Ślad został w §10. To ta sama klasa,
> którą ten przegląd tropi: liczba, która brzmi sensownie.

---

## 2 · Trzy wnioski zamiast czternastu

**1 · „Data vs zegar" to PIĘĆ z czternastu** (`P-02`, `P-03`, `P-04`, `P-12`, `P-13`) —
**jedna klasa, nie pięć pomyłek.** Data wyglądała poprawnie **w kalendarzu** (dobry dzień
tygodnia, właściwy rytm), a była zła **względem zegara przypadku**: dzień miniony albo
godzina obcięta regułą 2 h. **Skutek idzie w obie strony:**

| szkielet | co by się stało |
|---|---|
| `A-04` | część oczekiwanego zera pochodzi od zegara → **przechodzi z niewłaściwej przyczyny** |
| `A-07`, `H-07` | wartość oczekiwana nieprawdziwa → **czerwień na sprawnym silniku** |
| `A-08` | `ARRANGE` niewykonalny → test pada tam, gdzie nikt nie szuka defektu |
| `H-01` | slot, o który pyta `ACT`, **nie istnieje** |

Dlatego wynikiem jest **reguła wejściowa w §1** (trzy pytania przed wpisaniem daty:
po zegarze? ≥ 2 h? w horyzoncie roli?), a nie pięć osobnych poprawek. Poprawki też są.

**2 · Odmowa bez asercji przyczyny — druga klasa** (`P-01`, `P-14`, sześć szkieletów).
`odrzucone(422)` jest spełnione także przez **„brak wolnego slotu"**, a rytm niskopłatny
daje tylko **2** sloty na wtorek — więc test limitu przechodziłby, mierząc **dostępność**.
To rodzina „milczącej czerwieni" z `D-2026-08-07-22`.
**Odnotowuję rzecz nieprzyjemną: `SZK-F-10` robił to poprawnie od początku.** Miałem
własny wzorzec w tej samej grupie i nie zastosowałem go do pozostałych pięciu pozycji.

**3 · Jedna perturbacja była MARTWA** (`P-06`). „ADHD liczone jako 60 min" daje zajętość
`09:00–10:00` + bufor do `10:10`, więc slot `10:00` odpada tak samo jak przy poprawnym
90 min — **wynik ten sam, test się nie zapala**. Poprawka: `dlugosc_adhd := 50` → slot
`10:00` wchodzi → `3` zamiast `2` → czerwony. **Przeliczyłem rachunkiem pozostałe `PERT`
we wszystkich grupach**; ta była jedyna martwa.

---

## 3 · ⚠ `P-08` — nie poprawiam sam, bo to rozstrzygnięcie kontraktowe

**Moment zamrożenia kwoty jest w moich szkieletach opisany DWA RAZY, rozbieżnie:**

- `SZK-G-01` zamraża przy **`REZERWACJA.utworz`**,
- `SZK-D-06` zamraża przy **`BLOKADA.zaloz`**.

Przy ścieżce własnej te momenty się zbiegają i różnicy nie widać. **Przy ścieżce
psychologa dzieli je 48 h** — okno, w którym cennik może się zmienić.

**Rekomendacja: zamrożenie w chwili ZAŁOŻENIA BLOKADY.** Wtedy pacjent dostaje w linku
kwotę do zapłaty, a `CLAUDE.md` §4 wymaga, żeby zwrot równał się temu, co **naprawdę
zapłacił**. Przy zamrożeniu dopiero na `REZERWACJA.utworz` link mógłby obiecywać jedną
kwotę, a system pobrać inną.

**To jest jedna linijka w kontrakcie operacji, nie zmiana w testach** — dlatego zgłaszam
zamiast wybierać. Bez niej `SZK-D-06` nie ma skąd wziąć `kwota(zadanie) == 14500`:
pacjent `A` nigdy nie doszedł do utworzenia rezerwacji.

---

## 4 · ⚠ `Q-23` — nowe pytanie, i część odpowiedzialności jest moja

**Czy limit 10 wizyt niskopłatnych liczy WSZYSTKIE usługi kategorii „niskopłatne",
czy wyłącznie konsultacje niskopłatne (55 zł)?**

Wyszło z `SZK-J-08`: w moim `fixtureS1()` **asystent zdrowienia (0 zł) siedzi w kategorii
`NISKOPLATNE`**. Przy szerszym odczycie **pacjent korzystający z bezpłatnego asystenta
zużywa pulę dofinansowanej terapii** — czyli **darmowa usługa odbiera dostęp do
płatnej-dofinansowanej**. To ta sama rodzina co `D-2026-08-09-08` („gdyby licznik liczył
wszystko, odciąłby ludzi płacących pełną stawkę"), tylko od drugiej strony.

**Rekomendacja: limit liczy wyłącznie konsultacje niskopłatne.**

**Odnotowuję uczciwie, że to może być mój błąd modelowania, a nie luka w regule:**
wsadziłem asystenta do kategorii `NISKOPLATNE`, bo tak wynikało z `CLAUDE.md` §3
(flaga `fundacja/komercja`). **Pytanie brzmi więc dokładniej:** czy podział
`fundacja/komercja` jest tym samym podziałem, co „wizyta niskopłatna" w liczniku limitu.
Jeśli nie — mój fixture wymaga poprawki, nie reguła.

---

## 5 · Ograniczenie tego przeglądu — wpisane do dokumentu

**To jest przegląd AUTORA.** Znalazłem 14 rzeczy we własnej pracy, czyli te, **które umiem
zobaczyć**. Klasy, których nie umiem zobaczyć, zostają.

Wpisałem to do `SZKIELETY-F2.md` §10 wprost, żeby nikt — łącznie ze mną za tydzień —
nie zacytował tego jako „szkielety zweryfikowane". **Nie zastępuje niezależnego wykonania
w etapie B ani rundy weryfikatora**, który tego nie pisał (`WYTYCZNE-PRACY.md` §2).

---

## 6 · Co dalej

**Zbiór szkieletów jest domknięty poza `K` i `L`; kolejność `kontrakt → L → K` bez zmian.**

**Nie stoję (S-2).** Do czasu kontraktu proponuję — **do Twojej zgody, nie robię po cichu**:

1. **Przeliczenie wszystkich wartości liczbowych w 68 szkieletach niezależnym rachunkiem**
   (raster, bufor, granice okien, UTC, tygodnie ISO). Przegląd, który właśnie skończyłem,
   pytał o **konstrukcję** przypadku; ten pytałby o **arytmetykę**. `P-06` pokazał, że
   liczba potrafi być błędna nawet w poprawnie zbudowanym szkielecie.
2. Ewentualnie: **przygotowanie propozycji kontraktu operacji** od strony testów — nie po
   to, żeby go narzucić KOD-SILNIK, tylko żeby tryb trójstronny zaczął się od dwóch
   propozycji zamiast jednej. **Jeśli uznasz, że to wchodzi w cudzy zakres — odpuszczam.**

**Czekam:** `Q-16` (G7) · `Q-23` · `P-08` (moment zamrożenia) · kontrakt operacji API.

**Sprzeczne polecenia w tej rundzie:** brak.

---

**Meldunek kolejny:** proszę o numer przy następnej odpowiedzi.
