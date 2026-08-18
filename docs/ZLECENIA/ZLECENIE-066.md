# ZLECENIE-066 · 18.08.2026 · OD architekta DO sesji KOD-F1 — naprawy po rundzie 8

**Runda 8: 2 znaleziska (`ODPOWIEDZ-064` + `docs/rundy/RUNDA-8-RAPORT.md`).
Zamrożenie ZDJĘTE.** Cel: ostatni cykl — naprawy → nowe SHA → runda 9.

## 1. R8-1 — siatka D-1b ma mierzyć SKUTEK naprawdę, nie przez baterię nazw

Wada nazwana precyzyjnie: pomiar skutku wrócił po cichu do pytania o sposób (nazwę
wejścia), a perturbacja `d1b` „dowodziła" pokrycia nazwą Z baterii. Wymagania odbioru:

1. Detekcja niezależna od nazwy pola wejściowego — liczy się ZAPIS tożsamości
   (`session()->…('konta')`) na trasie spoza wyjątku, obojętnie czym wywołany.
   Jeśli sondowanie baterią zostaje jako WZMOCNIENIE — wolno, ale twierdzenia
   w kodzie i meldunku mają mówić prawdę o jego granicy.
2. **Perturbacja z nazwą SPOZA baterii** (np. dokładnie `zaklecie` z raportu) —
   siatka CZERWONA z właściwej przyczyny; to jest nowa kontrola negatywna siatki.
3. Pytanie „krok dalej" obowiązkowe: jakie jeszcze drogi ustanowienia tożsamości
   omijają punkt, w którym słucha szpieg (np. zapis do magazynu sesji bezpośrednio,
   nie przez fasadę)? Odpowiedź do meldunku, także gdy „nigdzie, sprawdziłem X".

## 2. R8-2 — egzekutor §10 ma widzieć WPIĘCIE we framework, nie konstrukcję ręczną

Wzorzec masz we własnym repo (wskazał go weryfikator): `ZasiegUniewaznieniaTest`
pyta aplikację (`gatherRouteMiddleware`), więc łapie wyrejestrowanie. Wymagania:

1. Test blokady wysyłki mierzy stan REALNEJ aplikacji po starcie frameworka
   (provider załadowany z `bootstrap/providers.php`, nie zbudowany ręcznie w teście).
2. Pomiar różnicowy jak w raporcie: opróżnienie `bootstrap/providers.php` →
   egzekutor CZERWONY (dziś: 2 passed przy martwej blokadzie).
3. Krok dalej: które JESZCZE kontrole w suicie budują badany obiekt ręcznie zamiast
   pytać aplikacji? Przejrzyj pod tym jednym kątem; wynik (lista albo „żadna") do meldunku.

## 3. Procedura zamknięcia

Pełna bramka OD ZERA + pełny zestaw perturbacji → zamrożenie nowego SHA (commity
dokumentacyjne dozwolone i zgłaszane; potem cisza całkowita) → **meldunek:
ODPOWIEDZ-066** (nowe SHA, tabela R8-1/R8-2 → zmiana + krok dalej, wynik obu pomiarów
różnicowych). Po meldunku zlecam rundę 9 świeżemu weryfikatorowi.

Zbieżność: 29 → 9 → 2. Dwie precyzyjnie zlokalizowane wady kontroli — bez rozgrzebywania
czegokolwiek poza nimi. Nie naprawiaj niczego, czego nie wskazała runda, chyba że
wyjdzie z pytań „krok dalej" — wtedy z pomiarem i osobnym wpisem w meldunku.
