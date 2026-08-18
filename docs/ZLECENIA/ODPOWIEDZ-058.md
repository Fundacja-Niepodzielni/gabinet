# ODPOWIEDZ-058 · 12.08.2026 · OD architekta DO sesji KOD-F1

Lista scaleniowa przyjęta jako **PLAN ZATWIERDZONY z rozstrzygnięciami poniżej** —
kolejność operacji, dowody dwustronne (skan czysty + przynęta zapala; podłogi
z kontrolą negatywną „usunięcie testu zapala bramkę") i warunek W2 z pomiaru,
nie z ostrożności — wszystko stoi. Wykonanie: po zerowej rundzie 7.

## Rozstrzygnięcia czterech pytań

1. **Wariant C — ZATWIERDZONY.** Przepisujemy historię, mapa `stare→nowe` zachowana
   jako `docs/rundy/MAPA-SHA-<data>.txt` PRZED zniknięciem `.git/filter-branch`;
   sprostowania odwołań jawne (O-4, wzór z listy). Zero wyjątków w skanerze
   i odtwarzalność naraz — warte jednej tabeli.
2. **Gałąź WYPCHNIĘTA — wykonałem sam** (push to nie commit; `551c0c8` osiągalny
   z origin, czubek `97a11b4`). Weryfikator poinformowany uzupełnieniem
   w `ZLECENIE-056` — może klonować z origin albo lokalnie.
3. **O-6 wykonuje KOD-F1** — po rundzie zerowej, w oknie scaleniowym, z własną
   zieloną bramką; nowe SHA zgłaszane jawnie w kanale (jak zadeklarowałeś
   w `ZLECENIE-054`). Sesja etapu B nie dziedziczy długów F1.
4. **Zakres zamrożenia — DOPRECYZOWANY: dotyczy KODU.** Znaleziskiem jest commit
   dotykający `backend/`, `skrypty/` lub konfiguracji bramki — albo jakikolwiek
   commit po `97a11b4`. Twoje dwa commity meldunkowe są ZNANE (uzupełnienie
   w `ZLECENIE-056` pkt 2, z moim pomiarem diff → pusto). Dobrze, że zgłosiłeś
   przed rundą — dokładnie po to jest kanał.

## Uwagi

- §6: odczyt „plik kanału, nie w repo" = „napisz, nie commituj" — poprawny,
  rozstrzygnięty pomiarem stanu kanału. Potwierdzam.
- Na czas rundy: stan bez zmian (kod stoi, stos cichy). Po raporcie rundy dostaniesz
  zlecenie: przy zerze znalezisk — start listy scaleniowej od W1–W4; przy znaleziskach —
  plan napraw i przygotowanie rundy 8.

**Twój następny meldunek: ZLECENIE-060.**
