# ODPOWIEDZ-086 — okno scaleniowe F1 zamknięte, stan gotowy do przeglądu

**Od:** sesja KOD-F1 · **Do:** architekt · **Data:** 19.08.2026
**W odpowiedzi na:** `ZLECENIE-084` (wariant C) + `ZLECENIE-085` (wariant B dla historii) + `ZLECENIE-087` (granica O-6c)
**SHA do scalenia:** `75b84e2` · **Kopia bezpieczeństwa:** `kopia-przed-merge-19-08` (= `b60c53a`)

---

## 1. O-8 — surowe wyjście, bramka OD ZERA bez potoku

```
KOD_BRAMKI=0
    WYKONANO 326 testów (podłoga: 326)
    sprawdzono 2282 asercji (podłoga: 2282)
11:39AM INF 166 commits scanned.
11:39AM INF no leaks found
BRAMKA OK — 22 kroków, 0 nieudanych
```

Pełny zestaw perturbacji:

```
KOD_PERTURBACJI=0
PERTURBACJE OK — 66 kontroli udowodniło, że umie zaświecić czerwono (pominięte: 0)
zmierzone niezależnie: 49 nagłówków „=== PERTURBACJA", 66 znaków ✓, 0 znaków ✗
```

Podłogi **326 / 2282 — RÓWNO**, bez zapasu. Kod wyjścia z pliku, nie z potoku.

---

## 2. Warunki wejścia W1–W4

| # | wynik |
|---|---|
| **W1** | **ZASTĄPIONE decyzją właściciela z 19.08** (wariant C). Zapisane w `D-2026-08-19-04` jako świadome nadpisanie reguły zbieżności `D-2026-08-07-16`, nie pominięcie. |
| **W2** | SPEŁNIONE — `git status` poza `docs/` pusty; brak niezacommitowanej pracy w kodzie. |
| **W3** | SPEŁNIONE — `kopia-przed-merge-19-08` = `b60c53a`. |
| **W4** | SPEŁNIONE — bramka zielona na stanie wejściowym i po każdym commicie. |

---

## 3. Wykonane operacje

### §1 zlecenia — zapis granicy (najwyższa waga)

`docs/GRANICA-R13-1.md` — osiem elementów, bez łagodzenia: wektor dosłownie
(sklejenie przez zmienne, `.=`, `implode`, `strrev`, `sprintf`, heredoc,
`new $zmienna`), skutek (`sub` i role z wyboru piszącego przy zielonej bramce),
**twierdzenie SPRAWDZALNE** o niedosięgalności z zewnątrz wraz z tym, co trzeba
zmierzyć, żeby je obalić, powód niepogłębiania, tabela pokryte/niepokryte,
druga linia obrony, termin (etap B — kontrola skutku), warunek znoszący.
Wpis `D-2026-08-19-04`.

### O-1 — kopia

`kopia-przed-merge-19-08` = `b60c53a`. Wycofanie całej listy: `git reset --hard`
na tę gałąź.

### O-2/O-2b/O-3 — WARIANT B (zamiast przepisania historii)

Zgodnie z Twoim rozstrzygnięciem: historii **nie przepisujemy**. Zamiast tego
**kontrola wąskości** — warunek przyjęcia wariantu B, którego wymagała lista:

`WaskoscWyjatkowGitleaksTest` (4 kontrole) pilnuje, że:
- wyjątków historycznych są **dokładnie dwa** (nie rosną),
- każdy ma `condition="AND"` (bez niej kryteria łączą się przez OR — R7-5),
- każdy zwalnia **jedną** wartość i **jedną** regułę,
- każde SHA jest **pełne 40-hex** (skrót nie pasuje do niczego — R7-5),
- suma zwolnionych commitów = **6** (D-4: 4, D-5: 2).

**Kontrola negatywna wbudowana** (nie na pliku, lecz na bloku psutym w pamięci —
manipulacja pliku przez indeksy okazała się zawodna przy polskich znakach):
cztery sposoby poszerzenia, każdy odrzucany, plus kontrola przyrządu
rozgraniczająca predykat bloku od licznika sumy.

**Nowe terminy D-4/D-5** wpisane w `.gitleaks.toml`: znikają razem przy
**pierwszym przepisaniu historii tej gałęzi, jeśli kiedykolwiek nastąpi** (etap B,
gdy gałęzie odbite będą scalone lub porzucone). Termin powiązany ze **zdarzeniem**,
nie z datą — inaczej wpadamy w moje własne zdanie „dług, który przeżył własny
termin, staje się stanem". Usunięcie tylko jednego pozostaje znaleziskiem.

### O-4 — ODPADA

SHA się nie zmieniają (nie przepisujemy historii), więc nie ma czego prostować.

### O-5 — konsolidacja wpisów D

`D-2026-08-19-05`: Q-1/3/4/8/9/10/12/14/19 z wartościami i uzasadnieniami,
**Q-16 jawnie NIEROZSTRZYGNIĘTE** (właściciel, spotkanie z Fundacją — scalenie
F1 tego nie zamyka), Q-21/22 → etap B, Q-23 + P-08 (dwie osie: rozliczeniowa
`fundacja/komercja` vs dostępowa `pula_niskoplatna`), R-1 = 10 min, oraz dwie
zasady przekrojowe z lekcji F1.

### O-6 — WYKREŚLONE z zakresu F1

Zgodnie z Twoim §2: obecne twarde podłogi równe zmierzonemu **są zapadką**;
automat ustawiający je „na ile akurat jest" zamieniłby ją w licznik. Wraca tylko,
gdy ktoś wykaże, że ręczne podnoszenie realnie zawodzi.

### O-6c — WYKONANE (nie przeniesione do etapu B)

`KsztaltWartosciWDokumentachTest`. Furtka z Twojego §1 nie była potrzebna —
predykat udało się skalibrować do **zera fałszywych alarmów**:

```
bez zawężeń                        → 478 trafień (nazwy klas, ścieżki, kod)
+ tylko [A-Za-z0-9_-], bez hex     →   3 trafienia (1 realne + 2 nazwy)
+ RÓŻNORODNOŚĆ małe/WIELKIE/cyfry  →   0 fałszywych; wzorce dalej zapalają
```

Sednem jest trzecie zawężenie, które podpowiedziałeś: nazwa wielbłądzia ma
**dwie** klasy znaków, token ma **trzy** — to rozróżnia bez żadnej listy nazw.
Kontrola odwrotna sprawdza obie strony: trzy wzorce muszą zapalać, sześć form
z dokumentacji (nazwy klas, metod, gałęzi, SHA, ścieżka) musi milczeć.

**Lista znanych cytatów: PUSTA i to zmierzone** — wszystkie znane cytaty
skrócone u źródła. Termin wpisany na wypadek, gdyby urosła.

### §2 zlecenia — realne wystąpienia naprawione

Dwa identyfikatory sesji w `docs/noc-2026-08-08/RUNDA-6-A-RAPORT.md` **skrócone**,
z widocznym śladem zamiast cichej podmiany. To ta sama klasa co dwa incydenty
cytatu sekretu, tylko bez kształtu przypisania — dlatego gitleaks ich nie widział.
**To właśnie znalazła nowa kontrola O-6c**, zanim ją domknąłem.

### O-7 — moja część wykonana

Deklaracje zakresu **wersjonowane**: `skrypty/zakresy/<STRUMIEN>.zakres`,
strażnik wybiera wg `GABINET_STRUMIEN`. Lokalny `.zakres-sesji` zachowuje
**pierwszeństwo** jako jednorazowe nadpisanie — inaczej wersjonowana deklaracja
unieważniałaby świadome rozszerzenie i wypychała ludzi w `GABINET_STRAZNIK=0`,
czyli w strażnika, który nie chroni.

**Kontrola negatywna zmierzona obustronnie:**
```
brak deklaracji + brak strumienia  → ODMOWA („BRAK DEKLARACJI ZAKRESU SESJI")
brak lokalnej + wersjonowana + GABINET_STRUMIEN=KOD-F1 → commit PRZECHODZI
```

Twoja część (prompty TESTY i SPEC-UMOWA) — przyjąłem do wiadomości, nie blokowałem
się na cudze potwierdzenia.

---

## 4. ⛔ WADA WŁASNA — zniszczyłem własną pracę `git reset --hard`

Sprzątając po próbie kontroli negatywnej O-7 użyłem `git reset --hard HEAD~1`,
żeby cofnąć commit próbny. To **skasowało trzy niezacommitowane zmiany**:
wpis O-5, skrócenie identyfikatorów sesji i zmianę strażnika. Wykryłem to
natychmiast (`grep` po odtworzeniu środowiska), wszystko odtworzyłem i zweryfikowałem.

**To był mój błąd, nie awaria narzędzia.** `reset --hard` odrzuca zmiany
w drzewie roboczym, a ja użyłem go do cofnięcia jednego commita — czyli sięgnąłem
po operację destrukcyjną tam, gdzie wystarczała nieszkodliwa.

**Zmiana procedury, nie obietnica:** sprzątanie po próbach idzie przez
`git restore --staged <plik>` + usunięcie pliku, a cofnięcie commita próbnego
przez `reset --soft`. `reset --hard` wyłącznie przy świadomym wycofywaniu całej
listy na kopię bezpieczeństwa. Powtórzona kontrola negatywna O-7 przebiegła tą
drogą i niczego nie utraciła.

Odnotowuję też drugą odsłonę znanej klasy: Pint zapalił w kroku [17] na moim
nowym pliku, bo uruchomiłem go na jednym pliku **przed** dopisaniem ostatniej
asercji. Ta sama rodzina co „statyka przed dopisaniem testów" z poprzedniego
cyklu — i tym razem złapała to bramka O-8, czyli kontrola, nie ja.

---

## 5. Stan długów

| dług | stan | termin |
|---|---|---|
| **D-3** | `TwierdzeniaKomentarzyTest` poza bramką (2 pominięte) | etap B |
| **D-4** | wyjątek gitleaks na przynętę | **NOWY TERMIN:** pierwsze przepisanie historii tej gałęzi (etap B), razem z D-5 |
| **D-5** | wyjątek gitleaks na cytat sekretu | **ten sam termin**, oba razem albo żaden |
| **R13-1** | granica ósmego piętra | etap B, pierwsze zadanie — kontrola skutku (`GRANICA-R13-1.md` §7) |
| **Q-16** | nierozstrzygnięte — właściciel | spotkanie z Fundacją; **NIE zamknięte scaleniem F1** |

Wąskości D-4/D-5 pilnuje odtąd `WaskoscWyjatkowGitleaksTest` — dług ma teraz
nie tylko termin, ale i strażnika.

---

## 6. Czego NIE zrobiłem

- **O-2/O-2b/O-3 (przepisanie historii)** — wariant B, Twoje rozstrzygnięcie.
- **O-4** — odpada wraz z wariantem B.
- **O-6** — wykreślone z zakresu F1, Twoje rozstrzygnięcie.
- **O-7 w części dotyczącej innych strumieni** — Twoje; nie blokowałem O-8.
- **O-9 (merge do `main`)** — **Twój**, po Twoim przeglądzie i zgodzie właściciela.
  Nie dotykam `main` i nie wykonuję force-push.
- **Wypchnięcie gałęzi** — `75b84e2` jest lokalny; `origin/faza-1-retencja` stoi
  na `b60c53a`. Wypchnięcie to zwykły fast-forward (bez przepisania historii),
  ale zostawiam je Tobie razem z O-9, żeby przegląd odbył się przed publikacją.
- **BLK-01** (klient `gabinet` w realmie Keycloaka) — nadal otwarty, scalenie F1
  go nie zamyka.
- **F0** — ma własne otwarte pozycje; nie zamykam jej po cichu przy F1.

---

## 7. Stan do przeglądu

```
75b84e2  Pint: format nowej kontroli O-6c
9cdc8c8  O-5 konsolidacja, O-6c kształt wartości w docs/, O-7 zakresy wersjonowane
bde6a96  kontrola wąskości wyjątków gitleaks + nowe terminy D-4/D-5
0807fcf  §1 zapis granicy R13-1 + decyzja o zamknięciu z granicą
b60c53a  ← kopia-przed-merge-19-08 (stan wejściowy)
```

Bramka 22/22 · 326/2282 RÓWNO · 166 commitów · `no leaks` · perturbacje 66/49/0.
Czekam na Twój przegląd i decyzję właściciela o merge.
