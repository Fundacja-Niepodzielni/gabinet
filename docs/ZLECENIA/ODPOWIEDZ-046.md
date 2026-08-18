# ODPOWIEDZ-046 · 12.08.2026 · OD architekta DO sesji SPEC-UMOWA

## ⚠ 0. NAJPIERW: szkielety NIE są Twoje — STOP (ZLECENIE-050)

Twój §3 zapowiada kontynuację szkieletów. **Nie rób tego.** Sesja TESTY została wznowiona
przez właściciela o 12:00 i robi je we własnym drzewie — szczegóły i uzasadnienie
w `ZLECENIE-050.md` (leżało w kanale przed Twoim meldunkiem, mogło się minąć z Twoją turą).
**Nie dotykaj gałęzi `testy-plan-f2` ani drzewa `D:\tmp\gabinet-testy-plan-f2`.**
ZLECENIE-047 pozostaje numerem TESTY.

## 1. Domknięcie dokumentacyjne — PRZYJĘTE

Poprawka poz. 5 z kontrolą podmiany per trafienie, regeneracja z zielonymi kontrolami,
D-2026-08-12-04, oznaczenie A19 — komplet. Dobrze rozwiązane potknięcie z anchorem
w `DECYZJE.md` (wykryte kontrolą po zapisie, nie po fakcie) — klasę „koniec pliku
z mojego odczytu" znam, konsolidacja przy merge ją domknie.

## 2. Twoje następne zadanie — rozjazdy R-2…R-8 do rejestru

To jest zapowiedziane w `ODPOWIEDZ-045` §3 zlecenie, na które słusznie czekałeś:

- **Źródło:** `ZLECENIE-045` §3 + plan §1.2, odczyt bez przełączania gałęzi:
  `git -C d:/KOD/Niepodzielni/gabinet show testy-plan-f2:docs/testy/PLAN-TESTOW-F2.md`
- Dopisz **R-2…R-8** do `REJESTR-ROZJAZDOW.md` w Twojej konwencji (kolejne wiersze A20+),
  ze statusami: R-2 (2 dni / 48 h / 24 h) — rozstrzygnięte moim Q-19 (48 h absolutne,
  `ODPOWIEDZ-045` §1); R-3 (weryfikacja kodem raz vs przy każdej) — rozstrzygnięte
  nocną rundą 09.08 (raz); **R-4 (guest checkout vs konto bez hasła) połącz z istniejącą
  pozycją B1 — status: czeka na właściciela (D-2026-08-09-10 nierozstrzygnięty)**;
  R-5…R-8 — zamknięte, odnotuj dla kompletności za planem.
- Jeżeli któryś wiersz wymaga zmiany w `SPECYFIKACJA-UMOWNA.md` — zmień i zregeneruj PDF
  z pełnym zestawem kontroli (jak dotąd).

**Meldunek: ZLECENIE-051.** Po nim Twoja sesja przechodzi w stan oczekiwania na przegląd
właściciela (dokument kliencki) — to koniec bieżącego zakresu, S-2 uznaję za spełnione
przekazaniem.
