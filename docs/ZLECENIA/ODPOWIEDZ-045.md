# ODPOWIEDZ-045 · 12.08.2026 · OD architekta DO sesji TESTY

Plan odczytany przez `git show testy-plan-f2:docs/testy/PLAN-TESTOW-F2.md` — meldunek
przyjęty. Dobre: rozbicie per grupa, jawne ucięcie rekurencji kontroli (K-01…K-04),
zgłoszenie R-1 zamiast cichego wyboru, uratowanie pracy KOD-F1 przy kolizji drzew.

## 1. Rozstrzygnięcia pytań blokujących (decyzje architekta, 12.08.2026)

Przyjmuję Twoje rekomendacje w ośmiu z dziesięciu — z uzasadnieniem per pozycja niżej.
Wartości liczbowe, które są parametrami operacyjnymi, wchodzą jako **konfiguracja
wersjonowana w bazie** (CLAUDE.md 14), nie stałe w kodzie — test ma czytać konfigurację.

| # | Decyzja | Uzasadnienie skrótem |
|---|---|---|
| **Q-1** | **bufor wlicza się** w zakres (zakres 09:00–12:59 → 3 sloty) | raster 60 min jako jednostka; „ostatnia wizyta kończy się wraz z buforem w godzinach pracy" jest odczytem bezpieczniejszym dla specjalisty |
| **Q-3** | powtórzona `02:00` oferowana **dwa razy**, etykieta prezentacyjna MUSI je rozróżniać | w bazie to dwa różne UTC — ukrycie jednego to utrata slotu; warunek etykiety jest częścią decyzji, przypadek ma go liczyć |
| **Q-4** | okno 24 h = **86 400 s absolutnych** | reguła nie może zmieniać wartości dwa razy w roku; spójne z Q-19 |
| **Q-8** | krótka blokada wstępna = **10 min** | dolna wartość widełek D-2026-08-09-08; parametr konfiguracyjny |
| **Q-9** | **`max(2 dni, otwarcie+10 min)`** | otwarcie linku nie może SKRACAĆ okna pacjenta; „+10 min po otwarciu" jest dopełnieniem, nie zwolnieniem |
| **Q-10** | margines **M = 2 h**, parametr konfiguracyjny | ta sama oś co „najbliższy możliwy termin"; jedna oś = jedna liczba |
| **Q-12** | limit równoczesnych nieopłaconych blokad = **2** | właściciel dał widełki 1–2; 2 nie karze pary „wizyta dla mnie i dziecka"; parametr konfiguracyjny |
| **Q-14** | „wystawiony" = **otwarty** (niezależnie od rezerwacji) | limit podażowy ma ograniczać podaż; odczyt „otwarty i wolny" czyni go rosnącym z każdą rezerwacją — obalone przez cel reguły |
| **Q-16** | **NIE rozstrzygam — właściciel.** | stoi na liście spotkania z Fundacją (brief §7.5 pkt 7 / lista G7); `J-02` czeka; zgłoszone dziś właścicielowi ponownie |
| **Q-19** | „2 dni" = **48 h absolutnych** | spójnie z Q-4 |

Wpisy D-2026-08-12-… do `docs/DECYZJE.md` **wprowadzę ja przy domknięciu F1/merge** —
celowo nie dotykam teraz śledzonych plików w drzewie głównym, w którym pracuje KOD-F1
(dokładnie klasa z Twojego §4.1). Do tego czasu źródłem jest ten plik.

**Q-21 → etap B:** przyjęte. Do promptu KOD-SILNIK dopisuję: **pierwsze zadanie fazy F2
to kontrakt operacji API** (uzgadniany z Tobą zleceniem), drugie — **przestawienie
kształtu zamrożonego zrzutu reguł (D-2026-08-09-09) ZANIM powstanie pierwsza rezerwacja**
(Twoje „okno zamknie się na zawsze" — przyjęte dosłownie).

## 2. R-1 (blokada ~godzina vs 10 min) — u właściciela

Zgadzam się z Twoją analizą hierarchii: nowsze zdanie właściciela stoi przeciw starszym
zapisom i nikt go nie wycofał. **Pytanie postawione właścicielowi dziś** (z rekomendacją:
~60 min przy samodzielnym umawianiu — obrona przed zamrażaniem grafiku przejęta przez
limit Q-12, a godzina daje pacjentowi realny czas na płatność). Plan trzymaj na 10 min
do odpowiedzi; wartość i tak jest parametrem, więc koszt zmiany = aktualizacja
przypadków, nie kodu.

## 3. Rozjazdy R-2…R-8 → SPEC-UMOWA

Przekazuję do rejestru rozjazdów zleceniem do SPEC-UMOWA (wraz ze wskazaniem odczytu
planu przez `git show`). Nie dopisuj się do cudzego pliku — słusznie.

## 4. Kanał i drzewa — decyzje orkiestracyjne (obowiązują wszystkich od zaraz)

1. **Każda sesja dotykająca repozytorium pracuje we własnym `git worktree`** —
   Twoja rekomendacja §4.1 przyjęta; dopisane do `00-PLAN-ORKIESTRACJI.md`.
   KOD-F1 zostaje w drzewie głównym (zastane status quo, jedyna sesja commitująca tam).
2. **Kanał `docs/ZLECENIA/` żyje WYŁĄCZNIE w drzewie głównym** — sesje w worktree piszą
   po ścieżce bezwzględnej `D:\KOD\Niepodzielni\gabinet\docs\ZLECENIA\`.
3. **Numery zleceń przydziela architekt** (Twoja opcja 3 + 2 łącznie): przy każdej
   odpowiedzi dostajesz numer swojego następnego meldunku. **Przydział bieżący:**
   SPEC-UMOWA → 046 · **TESTY (Ty) → 047** · KOD-F1 (meldunek gotowości do rundy 7) → 048.

## 5. Dalej po Twojej stronie

Kontynuuj szkielety grup niezależnych (A, B, E, G, I, H-01…03, H-06) — zgodnie z S-2.
Po odpowiedzi właściciela na R-1 i rozstrzygnięciach ze spotkania Fundacji (Q-16)
dostaniesz zlecenie aktualizacyjne. Meldunek zamykający szkielety: **ZLECENIE-047**.
