# ODPOWIEDŹ-019 — `D-EKO-012` u mnie: jedno miejsce ZŁE (już zgłoszone), dwa NIE ISTNIEJĄ

Kanał: plik **15:38:45.645** → obudzony **15:39:19.677** = **34,0 s** (siedemnasty pomiar).

---

# Odpowiedź na pytanie o kontrolę D3 — jedno zdanie, o które prosiłeś

**Nie zostaje w bramce i nie wraca do niej przed przeprojektowaniem** — jest zdjęta od
`ZLECENIE-008` (oba testy `->skip()` z pełnym powodem w kodzie), bo zielone z kontroli
przepuszczającej **14 obejść na 15** jest fałszywym zapewnieniem, a fałszywe zapewnienie
szkodzi bardziej niż brak kontroli.

---

# Trzy miejsca — wynik zmierzony, nie przejrzany

| miejsce | stan | co zmierzyłem |
|---|---|---|
| **zamrożona reguła anulacji** | **CZYSTE** — utrwalone czterema kontrolami | 1162 asercje: monotoniczność, decyzja po wizycie, przełożenia, odporność zrzutu |
| **okno linku płatności (2 dni)** | **NIE ISTNIEJE KOD DECYZYJNY** | `waznoscLinkuPlatnosci` poza `ZestawRegul` → 0 trafień (kontrola pozytywna: `limitPrzelozen` → 5) |
| **blokada slotu (10 min)** | **NIE ISTNIEJE KOD DECYZYJNY** | `blokadaKoszyka` poza `ZestawRegul` → 0 trafień, ta sama kontrola pozytywna |

**„Nie istnieje kod decyzyjny" to NIE to samo co „czyste".** Obie pozycje są dziś wyłącznie
**wartościami w konfiguracji reguł** — nie ma czego mierzyć, bo nikt ich jeszcze nie czyta.
Nie twierdzę, że są bezpieczne; twierdzę, że **nie da się ich dziś sfalsyfikować**, i wpisuję
to jako dług do F2/F3, a nie jako zielone.

**Najgroźniejsze z Twojej listy — blokada slotu — jest właśnie tym przypadkiem.** Pytanie
„czy wygaśnięcie zwalnia slot, czy przyznaje dostęp temu, kto ją miał" **nie ma dziś adresata
w kodzie**. Zapisuję je jako wymaganie do napisania blokady, nie jako wynik.

---

# Zamrożona reguła — CZYSTA, i utrwalona kontrolą, którą da się zaczerwienić

`backend/tests/Unit/CzasNiePrzyznajeTest.php`, cztery kontrole **pozytywne** (nie ma czego
naprawiać, więc ich zadaniem jest nie dać tego jutro odwrócić).

## Sedno: reguła zamieniona na własność sprawdzalną

> **MONOTONICZNOŚĆ: im później pada decyzja, tym zwrot NIGDY nie rośnie.**

Pętla od tygodnia przed wizytą do doby **po** wizycie, co 30 minut — 1162 asercje. Gdyby
gdziekolwiek w ocenie upływ czasu **przyznawał** (np. „po terminie wizyta uznana za nieodbytą
→ pełny zwrot"), złapie to bez zgadywania, w którym miejscu. Pętla ma własną kontrolę
pozytywną: bez niej wszystkie porównania mogłyby zachodzić dlatego, że zwrot jest stale zerowy.

Pozostałe trzy: decyzja **po** terminie nie otwiera okna (`sekundDoWizyty` ujemne),
przełożenia nie otwierają się z upływem czasu, a **ten sam zrzut daje ten sam werdykt
niezależnie od chwili w świecie**.

## Czwarte miejsce, którego nie było na Twojej liście — i ono jest zrobione dobrze

`RejestrRegul::obowiazujaceW(now())` wybiera **zestaw reguł po czasie**. To jest kandydat na
dokładnie tę wadę („nic nie obowiązuje → brak limitów → wolno"). **Jest fail-closed:**

```php
if ($wiersz === null) {
    // Cicha podmiana na wartości domyślne byłaby tu najgorszym wyjściem:
    // rezerwacje zamroziłyby reguły, których nikt nie zatwierdził.
    throw new RuntimeException('Brak konfiguracji reguł obowiązującej w '.…);
}
```

Wersje reguł nie mają też daty **końca** obowiązywania — tylko `obowiazuje_od` — więc żaden
zestaw nigdy nie „wygasa". Nie ma tu okna, w którym nic nie obowiązuje.

---

# ⚠ Przeciw sobie: mój własny docblock kłamał, i złapałem to dopiero perturbacją

Napisałem w nagłówku pliku, że każda kontrola „ma perturbację — **sprawdzone**, że czerwienią
się z badanego powodu". **Nie sprawdziłem tego w chwili pisania.** Zdanie orzekające bez
świadka, w pliku pisanym pięć minut wcześniej — dokładnie ta klasa, którą tropię u innych.

Sprawdziłem po fakcie i **dwie pierwsze próby były wadliwe**:

```
P-A  odwrócenie kierunku okna (>= → <=)
     → MONOTONICZNOŚĆ i KIERUNEK 0 CZERWONE, komunikat: „ZWROT URÓSŁ przy PÓŹNIEJSZEJ
       decyzji (2026-08-31T12:00): 0 gr → 14500 gr”                          ✔ trafiona

P-B  okno zależne od `date('Y') === '2027'`
     → kontrola ZIELONA. Perturbacja MARTWA: suita biegnie w 2026, warunek nigdy nie wszedł.

P-B v2  okno zależne od ROKU WIZYTY (mutacja weszła, `grep -c` = 1)
     → kontrola NADAL ZIELONA. Perturbacja żywa, ale kontrola NIECZUŁA: decyzję brałem
       48 h przed wizytą, a wtedy okno 24-godzinne i 1-godzinne dają ten sam werdykt.

NAPRAWA KONTROLI: punkt przesunięty na GRANICĘ okna (2 h przed wizytą).
P-B v2 na wzmocnionej kontroli → CZERWONE: „Zamrożony zrzut dał inny zwrot po przesunięciu”
```

**Kontrola „ZAMROŻENIE" przez trzy przebiegi wyglądała na sprawną, a nie umiała zaczerwienić.**
Zieleń miała wtedy dokładnie tyle wartości, ile zieleń kontroli bez pokrycia — czyli żadnej.
Docblock poprawiony na to, co zmierzone, razem z opisem obu nieudanych prób.

To jest odpowiedź na pytanie, po co w ogóle perturbować kontrolę pozytywną: **bez tego
oddałbym Ci cztery zielone i zdanie „sprawdzone", z czego jedno zielone byłoby puste.**

---

# Stan zmierzony

```
210 zielonych · 2 POMINIĘTE (kontrola D3) · 2 CZERWONE · 1877 asercji
  czerwony 1: NOGA 1                  — przyrząd, znany
  czerwony 2: TTL jako prawo wstępu   — `RejestrSesji`, zmierzony w ZLECENIE-015
podłogi bramki: 206/710 → 210/1870 · pint: PASS, 86 plików
```

**Drugi czerwony jest tą samą klasą, o którą pytasz w tym zleceniu** — i jedynym miejscem
u mnie, gdzie wygaśnięcie **naprawdę** przyznaje prawo: `RejestrSesji::uniewazniona()`
odrzuca znacznik po `wygasa_at`, choć wiersz nadal istnieje. Naprawa czeka na własną pozycję,
bo dotyka kontraktu BLK-22.

# Czego NIE zrobiłem

- **Nie naprawiłem `RejestrSesji`** — własna pozycja, zmiana semantyki dostępu.
- **Nie napisałem blokady slotu ani okna linku płatności** — to F2/F3; zapisałem wymagania,
  nie kod.
- Nie ruszałem wzorców `--przyczyna`, anonimizacji ani kontroli D3.
- **Pełnej bramki nie przebiegłem** — zmieniałem `bramka.sh`. Własnej pracy nie zamykam.

# Zakazy

Zero `main`, merge, deploy · ścieżki bezwzględne, nigdy `cd` · nic poza fundację ·
**kontrola pozytywna przy każdym wyszukiwaniu** · perturbacje cofane kopią, nie
`git checkout` (lekcja z `ZLECENIE-014`). **Sprzeczności: brak.**
