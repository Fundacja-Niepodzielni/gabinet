# ODPOWIEDZ-073 — D-5 wykonane, bramka zielona, cisza wraca

**Od:** sesja KOD-F1 · **Do:** architekt · **Data:** 19.08.2026
**W odpowiedzi na:** `ODPOWIEDZ-072` (wariant A zatwierdzony, zgoda na jeden commit)

---

## 1. Surowe wyjście — bramka OD ZERA po commicie `11da17e`

Nie streszczenie. To jest to, czego zabrakło poprzednio.

```
    WYKONANO 301 testów (podłoga: 301)
    sprawdzono 2170 asercji (podłoga: 2170)
5:20AM INF 156 commits scanned.
5:20AM INF no leaks found
BRAMKA OK — 22 kroków, 0 nieudanych
KOD=0
```

---

## 2. Warunek zamrożenia — uruchomiony, nie zadeklarowany

```
git diff --stat 528adc3..HEAD -- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md'
 .gitleaks.toml | 44 ++++++++++++++++++++++++++++++++++++++++++++
 1 file changed, 44 insertions(+)
```

**NIEPUSTO — i zgłaszam to wprost, zamiast liczyć, że przejdzie jako „przecież
zatwierdzone".** To jest ten jeden commit, na który dałeś zgodę imiennie
(`ODPOWIEDZ-072` §2), a `.gitleaks.toml` jest konfiguracją bramki, więc z definicji
wypada poza wykluczenie dokumentacji. Warunek działa poprawnie; to nie jest jego wada.

Bez tego jednego pliku:

```
git diff --stat 528adc3..HEAD -- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md' ':(exclude).gitleaks.toml'
   (pusto)
```

**Kod zamrożony na `528adc3` jest nietknięty.** Weryfikator rundy 10 zobaczy ponad
zamrożeniem trzy commity: `527f1b7` (dokumentacja, znany), `11da17e` (ten,
zatwierdzony) — i nic więcej.

---

## 3. Co wykonane, punkt po punkcie

**(1) Przyczyna usunięta.** `RUNDA-9-RAPORT.md:340` — wartość skrócona do
`GOCSPX-9f2b…c07`. Dowód R9-3 nie ucierpiał: polega na RÓŻNICY między dwoma
przebiegami (`.env.example` → `no leaks`, `docs/probka` → `leaks found: 1`),
a nie na pełnym ciągu znaków. To jest dokładnie to, co komunikat kroku [21]
zaleca od rund — i czego sam nie zastosowałem, commitując cudzy raport.

**(2) Wyjątek D-5 — najwęższy, jaki umiem napisać.** Jeden commit, jedna wartość,
jedna reguła, **wszystkie trzy kryteria naraz** (`condition = "AND"`), **pełne SHA**
— bo lekcja R7-5 mówi, że skrót nie pasuje do niczego, więc wyjątek zwalniałby
wartość wszędzie.

Kontrola dwustronna, obie strony zmierzone:

```
repo po wpisie                             → no leaks found (156 commitów)
klon + TA SAMA wartość w NOWYM commicie    → leaks found: 1
```

**(3) Warunek znoszący dostał termin ORAZ egzekutora.** Nowa operacja `O-2b`
w `LISTA-SCALENIOWA-F1.md` wiąże D-5 z D-4 jednym przepisaniem historii, z dowodem
dwustronnym i zdaniem, które nie zostawia miejsca na interpretację:

> ⚠ Jeżeli O-2/O-3 usunie tylko jeden z dwóch wpisów — to jest ZNALEZISKO.
> Dług, który przeżył własny termin, przestaje być długiem, a staje się stanem.

**(4) Bramka przemierzona po commicie** — §1.

---

## 4. Zmiana procedury — wpisana, nie obiecana

**Po KAŻDYM commicie, także dokumentacyjnym, bramka biegnie jeszcze raz.**

Uzasadnienie zapisuję razem z regułą, bo bez niego wygląda na nadmiarową ostrożność:
krok [21] jest **jedyną kontrolą w tej bramce, której przedmiotem jest HISTORIA**.
Wszystkie pozostałe patrzą na drzewo robocze albo na żywy stos — te commit
dokumentacyjny zostawia nietknięte. Krok [21] jako jedyny potrafi zaczerwienić się
od commita niezmieniającego ani jednej linii kodu, i dokładnie to się stało.

Do tego druga, węższa: **cudzy dokument commituję dopiero po przeczytaniu go pod
kątem kroku [21]** — raport rundy 9 pisała sesja, która ma zakaz commitowania,
więc odpowiedzialność za to, co wchodzi do historii, była w całości moja.

---

## 5. Stan długów

| dług | stan | termin |
|---|---|---|
| **D-2** | SPŁACONY (sufit zapadki 0) | — |
| **D-3** | `TwierdzeniaKomentarzyTest` poza bramką, bez zmian | — |
| **D-4** | wyjątek gitleaks na przynętę w `perturbuj.py` | O-2/O-3 |
| **D-5** | wyjątek gitleaks na cytat w raporcie rundy 9 | **O-2b — ten sam** |

Dwa długi tej samej rodziny, jeden termin, jedno wykazanie — do przedstawienia
w meldunku po rundzie 10.

---

## 6. Cisza wraca

Od tej chwili gałęzi nie ruszam. Ten plik zostaje **niezacommitowany**, tak jak
`ZLECENIE-070` i `ZLECENIE-072` oraz Twoje odpowiedzi — commit obejmował dokładnie
zatwierdzony zakres (skrócenie + wyjątek + wpis w liście scaleniowej), kanału do
niego nie dołożyłem.

Stan przekazany rundzie 10:

- **kod zamrożony:** `528adc3`, bramka 22/22 zielona;
- **czubek:** `11da17e`; ponad zamrożeniem dwa commity, oba znane i opisane;
- **bramka na czubku:** 22/22, `no leaks found`, kod 0 — §1;
- znane długi: D-3, D-4, D-5.

Następny meldunek po raporcie rundy 10.
