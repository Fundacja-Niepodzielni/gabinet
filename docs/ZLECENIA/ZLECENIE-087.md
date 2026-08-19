# ZLECENIE-087 · 19.08.2026 · OD architekta DO sesji KOD-F1 — granica dla O-6c

Widzę Twój postęp (granica R13-1 zapisana, wpisy D, kontrola wąskości wyjątków z kontrolą
negatywną na cztery sposoby poszerzenia, bramka 324/2272 zielona). Eskalacja o force-push
była trafna i oszczędziła nam rozjazdu gałęzi TESTY.

## Rozstrzygnięcie: O-6c nie ma prawa zablokować domknięcia fazy

**O-6c jest UTWARDZENIEM, nie naprawą znaleziska.** Nie wchodzi w kryterium zamknięcia F1
i nie ma za nią żadnego otwartego znaleziska rundy. Dlatego:

1. **Zawężaj predykat najwyżej do momentu, w którym kontrola ma ZERO fałszywych alarmów
   na obecnym `docs/`.** Nazwy klas (`WaskieGardloTozsamosciTest`) mają odpaść przez
   wymóg **różnorodności znaków** — sam wielbłądzi zapis liter to nie jest kształt sekretu.
   Jeżeli po rozsądnej próbie predykat nadal daje fałszywe alarmy: **przenieś O-6c do
   etapu B** jako pozycję z terminem i idź dalej. Kontrola nadgorliwa zostanie wyłączona
   przez pierwszą osobę, której zapali bez powodu — a wtedy mamy zero zamiast czegoś.
2. **Znalezione realne wystąpienia napraw niezależnie od losu kontroli**: identyfikatory
   sesji w historycznym raporcie skróć teraz (to jest ta sama klasa co dwa incydenty
   cytatu sekretu, tylko bez kształtu przypisania, więc skaner ich nie widzi).
3. **Lista znanych cytatów ma mieć TERMIN** i kurczyć się, nie rosnąć — bez tego jest
   długiem udającym kontrolę (Twoje sformułowanie).

## Kolejność do końca okna

Realne wystąpienia → O-6c (z furtką wyżej) → O-7 w Twojej części (moją wykonałem:
prompty TESTY i SPEC-UMOWA mają wymóg wersjonowanej deklaracji przy wznowieniu) →
**O-8: pełna bramka OD ZERA bez potoku + pełny zestaw perturbacji, surowe wyjście**.
Meldunek **ODPOWIEDZ-086**, jak przydzielono.

Nie przedłużaj okna dla rzeczy, która nie jest warunkiem zamknięcia. Właściciel czeka
na zgodę do merge, a merge robię ja po własnym przeglądzie.
