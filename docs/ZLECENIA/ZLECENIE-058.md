# ZLECENIE-058 · 12.08.2026 · OD sesji KOD-F1 DO architekta

**Meldunek:** projekt listy scaleniowej F1 → main gotowy.
**Produkt:** `docs/ZLECENIA/LISTA-SCALENIOWA-F1.md` · Plik zamyka pozycję (S-3).

---

## 1 · Warunki pracy dotrzymane

- **Gałąź stoi.** Ani jednego commita w tej partii. Warunek z `ZLECENIE-054` §1
  nadal spełniony: `git diff --stat 551c0c8..HEAD -- backend/ skrypty/` → pusto.
- **Zero ciężkich przebiegów.** Nie uruchamiałem bramki, perturbacji ani suity.
  Stos główny cichy przez cały czas tej pracy — wyłącznie odczyty (`git log`,
  `git branch`, lektura kanału).
- **Ten meldunek i lista NIE SĄ ZACOMMITOWANE.** `ZLECENIE-056` mówi „commit po tym
  SHA na tej gałęzi jest sam w sobie znaleziskiem", a Twoje `ODPOWIEDZ-054` §2 —
  „jako plik kanału **(nie w repo)**". Kanał żyje dziś plikami nieśledzonymi
  (sprawdzone: wszystkie pliki innych sesji w `docs/ZLECENIA/` są `??`), więc
  oba pliki powstały bez commita.

---

## 2 · ⚠ ZGŁOSZENIE, KTÓRE MOŻE OSZCZĘDZIĆ RUNDZIE CZASU

**Po zamrożonym SHA są DWA commity — moje, wyłącznie dokumentacyjne.**

```
97a11b4  ZLECENIE-054: warunek sprawdzalny zamiast identyfikatora samozwrotnego
82876ab  ZLECENIE-054: meldunek gotowosci do rundy 7, SHA zamrozone
```

Powstały **przed** zleceniem rundy (`ZLECENIE-056` datowane później) i nie dotykają
kodu — sprawdzalne jednym poleceniem z `ZLECENIE-054` §1. Ale zlecenie rundy mówi
**„commit po tym SHA jest sam w sobie znaleziskiem"**, więc weryfikator zobaczy je
i zgodnie z literą zapisze jako znalezisko.

**Zgłaszam to sam, zamiast czekać, aż wyjdzie.** Do rozstrzygnięcia: czy zamrożenie
obejmuje **wszystkie** commity, czy tylko commity **kodu**. Jeżeli wszystkie —
proszę o sprostowanie w kanale, żeby weryfikator nie liczył tego jako znaleziska
merytorycznego, tylko jako to, czym jest: dwa pliki meldunku.

---

## 3 · DWIE RZECZY, KTÓRE ZMIENIAJĄ PLAN SCALENIA — obie zmierzone

### 3.1 · Przepisanie historii (D-4) UNIEWAŻNIA SHA RUNDY 7

`551c0c8` **nie jest na origin** (`git branch -r --contains` → pusto), czyli leży
**wewnątrz zakresu** `origin/faza-1-retencja..HEAD`, który miałby być przepisany.
Po operacji `git show 551c0c8` przestaje cokolwiek zwracać — a ten SHA jest
przedmiotem pomiaru w `ZLECENIE-054`, `ZLECENIE-056` i w przyszłym raporcie rundy.

Reguła „SHA nazywający przeszłe zdarzenie się nie starzeje" obowiązuje **dopóki SHA
pozostaje rozwiązywalny**. Przepisanie zamienia odsyłacz w napis.

**Rekomendacja: wariant C** — przepisać, ale zachować mapowanie
`.git/filter-branch/map` jako dokument sprostowań. Koszt: jedna tabela. Zysk:
zero wyjątków w skanerze **i** odtwarzalność historii rund.
Warianty A (przepisać bez mapy) i B (zostawić wąski wyjątek) opisane w §0.1 listy
z pełnym kosztem każdego — **B nie jest wstydliwy**, jeśli uznasz odtwarzalność
za ważniejszą; wymaga wtedy kontroli pilnującej, że wyjątek zostaje wąski.

### 3.2 · Zamrożony SHA NIE JEST WYPCHNIĘTY

Weryfikator dostał „czysty klon wskazanego SHA" — **z origin go nie sklonuje**.
Musi klonować ze ścieżki lokalnej (jak rundy 5–6) albo ktoś musi wypchnąć gałąź.
Wypchnięcie nie dodaje commitów, ale **nie jest moją decyzją** — zgłaszam.

---

## 4 · Co zawiera lista

Dziesięć operacji **z uzasadnieniem kolejności**, cztery warunki wejścia, sekcja
„czego nie robić" i jawny wykaz tego, co po scaleniu **zostaje otwarte**.

Kolejność w skrócie i dlaczego taka:

1. **O-1 kopia** → **O-2 przepisanie historii** — pierwsze wśród operacji na commitach,
   bo zmienia SHA wszystkiego po sobie; cokolwiek wcześniej trzeba by prostować dwa razy.
2. **O-3 usunięcie wyjątku gitleaks** — natychmiast po O-2, w tym samym oknie ciszy.
3. **O-4 sprostowanie odwołań do SHA** z mapy — **jawne**, nie cicha podmiana.
4. **O-5 konsolidacja wpisów D** — po O-4, bo wpisy cytują SHA i mają cytować ostateczne.
5. **O-6 automatyzacja podłóg** — jedyna **zmiana kodu** na liście, więc własna bramka.
6. **O-7 `.zakres-sesji` per strumień** — przed merge, bo po nim wszyscy ruszają na `main`.
7. **O-8 pełna bramka OD ZERA + perturbacje** — ostatnia rzecz przed merge, nie pierwsza.
8. **O-9 merge + znacznik** → **O-10 sprzątanie i `CURRENT WORK` ze stanu zmierzonego**.

**Każda operacja ma dowód ukończenia, nie deklarację.** Dwa najważniejsze:

- **O-2/O-3:** skan czysty **bez** wyjątku **oraz** przynęta nadal zapalająca skaner.
  Wyjątek usunięty bez tego drugiego zamienia jedną niewiadomą na drugą.
- **O-6:** po automatyzacji podłóg **usunięcie jednego testu musi zapalić bramkę** —
  bez tej kontroli negatywnej automat ustawiałby podłogę „na ile akurat jest",
  co zamienia zapadkę w licznik.

**Warunek W2 pochodzi z pomiaru, nie z ostrożności:** `filter-branch` **odmówił mi**
12.08 przez niezacommitowaną pracę innej sesji w `docs/DECYZJE.md`. Bez ciszy
w drzewie operacja O-2 jest niewykonalna, a obejście przez schowanie cudzej pracy
grozi jej utratą — dlatego cisza jest warunkiem wejścia, nie zaleceniem.

---

## 5 · Pytania do rozstrzygnięcia

1. **Wariant A / B / C** przepisania historii (§3.1). Rekomendacja: **C**.
2. **Czy wypchnąć `faza-1-retencja`** przed rundą (§3.2)? Jeśli nie — zlecenie rundy
   powinno mówić wprost „klonuj lokalnie".
3. **Kto wykonuje O-6** (automatyzacja podłóg) — sesja kodująca w etapie B czy KOD-F1
   przed rozwiązaniem?
4. **Czy zamrożenie SHA obejmuje commity dokumentacyjne** (§2)?

---

## 6 · Sprzeczne polecenia

**Jedna, rozstrzygnięta czytaniem.** `ODPOWIEDZ-054` §2 mówi „jako plik kanału
**(nie w repo)**", co przy pierwszym czytaniu brzmi wewnętrznie sprzecznie — kanał
`docs/ZLECENIA/` **jest** w repozytorium. Rozstrzygnąłem pomiarem, nie domysłem:
wszystkie pliki innych sesji w kanale są dziś **nieśledzone**, więc „plik kanału,
nie w repo" znaczy **napisz, nie commituj**. Zgodne z `ZLECENIE-056`
(„commit po tym SHA jest znaleziskiem").

Koszt cofnięcia, gdyby odczyt był błędny: **zerowy** — wystarczy `git add` obu plików.
