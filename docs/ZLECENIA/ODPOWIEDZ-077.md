# ODPOWIEDZ-077 — D-5 rozszerzone, bramka zielona, cisza wraca

**Od:** sesja KOD-F1 · **Do:** architekt · **Data:** 19.08.2026
**W odpowiedzi na:** `ODPOWIEDZ-076` (zgoda na jeden commit, zakres ściśle wyznaczony)

---

## 1. Surowe wyjście — bramka OD ZERA po commicie `f8f64c0`

```
    WYKONANO 304 testów (podłoga: 304)
    sprawdzono 2211 asercji (podłoga: 2211)
6:36AM INF 159 commits scanned.
6:36AM INF no leaks found
BRAMKA OK — 22 kroków, 0 nieudanych
KOD=0
```

---

## 2. Warunek zamrożenia — uruchomiony, wynik NIEPUSTY i zgłaszam to wprost

```
git diff --stat bbc8167..HEAD -- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md'
 .gitleaks.toml | 6 ++++++
 1 file changed, 6 insertions(+)
```

To jest **ten plik, na który dałeś imienną zgodę** (`ODPOWIEDZ-076` §1 pkt 2).
Zgłaszam, zamiast liczyć, że przejdzie jako oczywistość — dokładnie tak, jak przy
poprzednim zatwierdzonym wyjątku. Warunek działa poprawnie; `.gitleaks.toml`
pozostaje w zakresie zamrożenia zgodnie z `ODPOWIEDZ-072` §3.

Bez tego jednego pliku:

```
git diff --stat bbc8167..HEAD -- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md' ':(exclude).gitleaks.toml'
   (pusto)
```

**Ani jednej linii kodu.** Runda 11 mierzy `bbc8167` i mierzy stan, który istnieje.

---

## 3. Co wykonane — punkt po punkcie z Twojego §1

**(1) Skrócenie w trzech wystąpieniach.** `RUNDA-10-RAPORT.md` ×2,
`ZLECENIE-072.md` ×1. Skaner zgłosił **jedno** — pozostałe dwa nie mają kształtu
przypisania, więc reguła ich nie łapie. Naprawa samego zgłoszonego byłaby naprawą
instancji; w drzewie pełnej wartości nie ma nigdzie:

```
grep -rc "<pełna wartość>" docs/   →  0
```

**(2) Diff zastąpiony opisem słownym** w `ZLECENIE-072.md`, z dopiskiem
wyjaśniającym, dlaczego akapit został przepisany. Zostawiam ślad zamiast gładkiego
tekstu — tak samo jak przy sprostowaniu `ODPOWIEDZ-069` §2.

**(3) D-5 rozszerzone, nie zdublowane.** Do `commits` doszedł
`661e8a66b4980d70f93421f688110f20382734dd`; reszta wpisu bez zmian —
`condition = "AND"`, jedna wartość, jedna reguła, pełne SHA. Powód zapisany
w samym wpisie, żeby ten, kto będzie go zdejmował w `O-2b`, wiedział, że jeden
wpis pokrywa dwa commity.

**(4) Bramka po commicie** — §1.

---

## 4. Klasa z Twojego §3 — przyjmuję i dopisuję jedno rozróżnienie

Twoja reguła: *w dokumencie cytujemy z wartości wrażliwej wyłącznie tyle, ile
potrzeba do rozpoznania (prefiks + ogon), nigdy całości — także w diffach, także
w opisach usuwania.*

Dopisuję obserwację, która moim zdaniem tłumaczy, dlaczego złapało mnie to dwa razy
w jednej dobie, a nie raz: **te dwa wystąpienia miały różne przyczyny.**

- Pierwsze (`RUNDA-9-RAPORT.md`) — zacommitowałem CUDZY dokument, nie czytając go
  pod kątem kroku, który sam naprawiałem. Wada uwagi.
- Drugie (`ZLECENIE-072.md`) — napisałem WŁASNY dokument o usuwaniu wartości
  i odtworzyłem ją, bo diff jest najbardziej naturalną formą opisu naprawy.
  Wada odruchu, nie uwagi. Ta jest groźniejsza, bo powtórzy się u każdego, kto
  będzie opisywał podobną naprawę.

Stąd forma reguły, którą uważam za skuteczniejszą od „uważaj": **nie pokazuj
naprawy sekretu diffem.** Diff wymaga linii „przed", a linia „przed" jest z definicji
tym, co usuwamy.

---

## 5. Propozycja §6 — przyjmuję termin i uzasadnienie

Zgadzam się bez zastrzeżeń, i to nie z uprzejmości: **dokładanie kontroli w trakcie
serii rund przesuwa metę.** Kryterium „zero znalezisk" mierzy stan, a nie mój zapał
do ulepszania; gdybym dokładał kontrolę po każdej rundzie, faza nie domknęłaby się
nigdy, a każda kolejna runda mierzyłaby coś innego niż poprzednia.

Wymagania odbioru dla `O-6c` przyjęte tak, jak je zapisałeś — w szczególności to,
że **lista znanych cytatów ma się kurczyć po `O-2b`, nie rosnąć**. Lista, która
rośnie, jest długiem udającym kontrolę.

---

## 6. Stan długów

| dług | stan | termin |
|---|---|---|
| **D-3** | `TwierdzeniaKomentarzyTest` poza bramką | — |
| **D-4** | wyjątek gitleaks na przynętę w `perturbuj.py` | O-2/O-3 |
| **D-5** | wyjątek gitleaks na cytat sekretu — **dwa commity, jeden wpis** | O-2b |
| **O-6c** | kontrola kształtu wartości w `docs/` (przyjęta, niewykonana) | okno scaleniowe |

Nowych długów nie zaciągam. D-6 świadomie nie powstało.

---

## 7. Cisza wraca

Gałęzi nie ruszam do raportu rundy 11. Ten plik zostaje **niezacommitowany** —
i tym razem to nie jest tylko zwyczaj: każdy commit wymaga przemierzenia bramki,
więc commitowanie meldunku o bramce otwierałoby cykl bez końca. Kanał czytasz
z drzewa, tak jak ja czytam Twoje pliki.

Stan przekazany rundzie 11:

- **kod zamrożony:** `bbc8167` — bramka 22/22 zielona, `no leaks`;
- **czubek:** `f8f64c0`; ponad zamrożeniem trzy commity, wszystkie dokumentacyjne,
  wszystkie zgłoszone (`661e8a6`, `f8f64c0` oraz wcześniejszy `661e8a6` opisany
  w `ZLECENIE-076`);
- **bramka na czubku:** 22/22, `no leaks found`, 159 commitów, kod 0;
- znane długi: D-3, D-4, D-5 (+ O-6c jako przyjęta operacja scaleniowa).
