# ZLECENIE-085 · 19.08.2026 · OD architekta DO sesji KOD-F1 — rozstrzygnięcia okna scaleniowego

Eskalacja przed robotą — **wzorowa i uzasadniona**. Zakres O-2 faktycznie urósł: lista
scaleniowa powstała 12.08, gdy zamrożony SHA nie był wypchnięty; dziś sekrety leżą
w historii od `69c9e38`, a gałąź jest na origin. To zmienia operację z „przepisz kilka
commitów lokalnie" na **przepisanie całej historii + wymuszony push wypchniętej gałęzi**.

## 1. WARIANT B — ZATWIERDZONY. Twoja rekomendacja przyjęta

**Nie przepisujemy historii.** Powody, w kolejności wagi:

1. **Wymuszony push wypchniętej gałęzi jest operacją nieodwracalną i wychodzącą na
   zewnątrz.** Gałąź `testy-plan-f2` sesji TESTY jest od niej odbita — przepisanie
   rozjechałoby jej pracę, a sesja jest w pauzie i nie może zareagować.
2. **`filter-repo` niedostępny**; `filter-branch` sam ostrzega przed zniekształceniem
   historii. Ryzyko utraty pracy przewyższa zysk.
3. **Zysk jest kosmetyczny**: obie wartości są udowodnienie zmyślone (nie są żadnym
   żywym poświadczeniem), a wyjątki są wąskie i zmierzone — jedna reguła, jedna wartość,
   pełne SHA, `condition="AND"`, sprawdzone przez dwie niezależne rundy.

**Lista scaleniowa sama nazywa B „decyzją, nie zaniechaniem" — i tak to zapisujemy.**

**Wykonaj zamiast O-2/O-2b/O-3:**
- **kontrola pilnująca WĄSKOŚCI obu wyjątków** — to jest warunek przyjęcia wariantu B
  (lista wymagała go wprost). Ma zapalać, gdy: wyjątek obejmuje więcej niż wymienione
  commity, SHA jest skrócone, brakuje `condition="AND"`, albo dochodzi nowa wartość.
  Kontrola negatywna: rozszerz wyjątek w kopii → kontrola zapala.
- **D-4 i D-5 dostają NOWY termin: etap B, przy pierwszym przepisaniu historii, jeżeli
  kiedykolwiek nastąpi** — plus zapis, że **oba znikają razem albo żaden**.
  Bez terminu wpadamy w Twoje własne zdanie: „dług, który przeżył własny termin, staje
  się stanem". Termin ma być powiązany ze zdarzeniem, nie z datą.
- **O-4 odpada** (SHA się nie zmieniają), **O-5 rusza od razu** — bez czekania na mapę.

## 2. O-6 (automatyzacja podłóg) — NIE RUSZAMY, i to jest decyzja

Masz rację: obecne twarde podłogi równe zmierzonemu **są zapadką**, a automat ustawiający
je „na ile akurat jest" zamieniłby ją w licznik — przed czym lista sama ostrzega.
**Operacja O-6 zostaje wykreślona z zakresu F1** z uzasadnieniem w meldunku. Wraca tylko
wtedy, gdy ktoś wykaże, że ręczne podnoszenie podłóg realnie zawodzi.

## 3. O-7 — dzielimy na Twoje i moje

**Twoje (wykonaj):** mechanizm śledzonych deklaracji zakresu (per strumień, wybierany
przez strażnika wg tożsamości strumienia), `.gitignore` poprawiony, **własna deklaracja
KOD-F1 wersjonowana**, kontrola negatywna w Twoim drzewie (brak deklaracji → odmowa).

**Moje (nie czekaj):** potwierdzenia od TESTY i SPEC-UMOWA. Obie są w pauzie; ich
deklaracje powstaną przy wznowieniu — wpisuję to do ich promptów startowych, żeby nie
zastały strażnika bez deklaracji. **Nie blokuj O-8 czekaniem na cudze potwierdzenia.**

## 4. Numeracja wpisów D

Rozjazd zgłoszony poprawnie — `D-2026-08-19-03` był zajęty, granica dostała `-04`.
Obowiązuje Twoja numeracja; moje zlecenie było pisane bez wiedzy o zużyciu numeru.

## 5. Domknięcie okna

Kolejność: kontrola wąskości + nowe terminy D-4/D-5 → **O-5** (konsolidacja wpisów D) →
**O-6c** (kontrola kształtu wartości w `docs/`; lista znanych cytatów **z terminem**,
ma się kurczyć, nie rosnąć) → **O-7 w Twojej części** → **O-8** (pełna bramka OD ZERA
bez potoku + pełny zestaw perturbacji, surowe wyjście do meldunku).

**Meldunek: ODPOWIEDZ-086.** Po nim ja robię przegląd i pytam właściciela o zgodę na
merge (**O-9 jest mój**). Kopia `kopia-przed-merge-19-08` zostaje do czasu potwierdzenia,
że `main` jest zdrowy.
