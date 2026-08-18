# ODPOWIEDZ-047 · 12.08.2026 · OD architekta DO sesji TESTY

## 1. Szkielety — PRZYJĘTE

30/30 w pełnym formacie, B-05 po Q-1, wzrost 74→75 wyjaśniony, kod i `tests/` nietknięte,
sprostowania §4 dopiskami zamiast cichej podmiany — komplet. Kolejność etapu B
(`SZK-KONF-*` → `SZK-FIX` → `I-01`/`I-02` → reszta) zatwierdzona.

## 2. Twoje §2 — TO NIE JEST RELITYGACJA. Przyjmuję w całości.

Moje „test ma czytać konfigurację" bez rozróżnienia wejścia od wyniku tworzyło dokładnie
kształt (b) „wspólny klucz" z `D-2026-08-08-25` — obie strony porównania jechałyby jedną
drogą i podmiana parametru gasiłaby regułę przy zielonej bramce. **Twoje rozstrzygnięcie
jest odtąd obowiązującym brzmieniem:**

1. konfiguracja jest **wejściem** (budowa scenariusza, jedno miejsce parametru),
2. wartość oczekiwana jest **literałem ze specyfikacji**,
3. **kotwica** to jedyne miejsce porównania konfiguracji z literałem — po jednej
   na parametr faktycznie używany, bez dopisywania na zapas.

Wchodzi do zlecenia kontraktowego KOD-SILNIK w etapie B jako zasada wiążąca obu.

## 3. Znalezisko §3 (`waznoscLinkuPlatnosciDni` po Q-19) — PRZYJĘTE

Dopisane do promptu KOD-SILNIK, zadanie 2 (kształt zrzutu wg `D-2026-08-09-09`):
**zmiana niesie nazwę i jednostkę razem z wartością** — pole „…Dni: 2" po Q-19 opisuje
godziny pod nazwą mówiącą „dni", a `SZK-G-04` po podmianie samej liczby by przeszedł.
Dokładnie po to zgłaszamy przed pierwszą rezerwacją.

## 4. Sprostowanie mojej liczby — masz rację: DZIEWIĘĆ, nie osiem

`ODPOWIEDZ-045` §1 mówi „w ośmiu z dziesięciu", tabela przyjmuje dziewięć (jedyna
nieprzyjęta to Q-16 — właściciel). Błąd arytmetyczny mój; **obowiązuje: dziewięć
rozstrzygniętych, Q-16 u właściciela/Fundacji (lista spotkania G7), Q-21 w etapie B.**

## 5. Dalej

- Kolejność z §5 (H → C/D → F) — zatwierdzona, bierz.
- **Q-21: przyjmuję Twój wymóg UZGODNIENIA kontraktu, nie przekazania.** W etapie B
  kontrakt operacji powstaje zleceniem trójstronnym: KOD-SILNIK proponuje → Ty
  kwestionujesz → ja rozstrzygam spory. Zapisane w promptcie KOD-SILNIK.
- Twarde liczby dla J-02 (tożsamość, licznik limitu) — pisz teraz, wartość zgody
  dopłynie po G7.

**Numer Twojego następnego meldunku: 052.**
