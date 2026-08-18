# ZLECENIE-062 · 12.08.2026 · OD architekta DO sesji KOD-F1 — naprawy po rundzie 7

**Runda 7: 9 znalezisk (`ODPOWIEDZ-056` + `docs/rundy/RUNDA-7-RAPORT.md`). Zamrożenie
ZDJĘTE** — wraca tryb pracy na gałęzi `faza-1-retencja`. Cel: jeden cykl napraw →
nowe zamrożone SHA → runda 8.

## 1. Zakres napraw (wg raportu; kolejność Twoja, komplet obowiązkowy)

R7-1…R7-9 — wszystkie. Zwróć uwagę na wspólny mianownik nazwany przez weryfikatora:
**„klasa przeniosła się o krok"** (literał komentarz→string; kolumna nowa→istniejąca;
pisarze→czytelnicy przez fasadę; jeden skrypt→drugi). Przy każdej naprawie pytanie
obowiązkowe: **gdzie jeszcze ta klasa może stać o krok dalej?** — odpowiedź (także
„nigdzie, sprawdziłem X") do meldunku. Dodatkowo:

- **R7-5 koryguje D-4:** wyjątek gitleaks faktycznie NIE jest zawężony (brak
  `condition="AND"`) — napraw zawężenie ALBO usuń wyjątek od razu, jeśli tańsze;
  opis długu ma mówić prawdę.
- **R7-6:** `CURRENT WORK` przepisz ZE STANU ZMIERZONEGO po naprawach (podłogi
  265/2024, strażnik POWSTAŁ — commit `cc70946`).
- **D-1b (trzecia siatka pomiarowa weryfikatora):** wciel postać docelową z raportu
  §3b (szpieg na zapisie klucza `konta` z atrybucją do trasy) jako stałą kontrolę
  z perturbacją zapalającą. Kierunek ma dowód — nie zostawiamy go w raporcie.

## 2. Do tego samego cyklu: O-6b (naprawa strażnika)

Projekt zatwierdzony (`ODPOWIEDZ-060`): P-1 katalog wspólny + P-2 tożsamość
z `--git-common-dir` + kontrole K-1/K-2/K-3. Wykonaj TERAZ, nie w oknie scaleniowym —
zamrożenie zdjęte, a strażnik ochroni same naprawy. Po naprawie: cofnięcie
`.zakres-sesji` TESTY (wpis w kanale, wykonasz w jej drzewie TYLKO ten jeden plik,
za jej zgodą — albo zostaw jej jedno zdanie w kanale do samodzielnego cofnięcia).

## 3. Procedura zamknięcia cyklu

1. Naprawy + O-6b + D-1b.
2. **Pełna bramka OD ZERA + pełny zestaw perturbacji** (Twoja własna zasada:
   trzy zielone narzędzia to nie zielona bramka).
3. Zamrożenie nowego SHA (commity dokumentacyjne dozwolone wg `ODPOWIEDZ-058` pkt 4).
4. **Meldunek: ODPOWIEDZ-062** — nowe SHA, wynik bramki, tabela R7-x → co zmieniono
   + odpowiedź na pytanie „gdzie klasa mogła stać o krok dalej", stan długów
   (D-2/D-3 wg R7-8: opis ma podawać prawdziwą liczbę wzorców).
5. Po meldunku piszę zlecenie rundy 8 (nowa sesja weryfikatora, świeży kontekst).

Historia zbieżności: 11 → 15 → 12 → 29 → **9**. Schodzimy — domykamy uczciwie.
