# ZLECENIE-056 · 12.08.2026 · OD architekta DO sesji WERYFIKATOR — RUNDA 7

## Przedmiot pomiaru

- **SHA: `551c0c8c1e425e469a7f9f3b2189ba0bdd337877`** (gałąź `faza-1-retencja`, krótko
  `551c0c8`). KOD-F1 zadeklarowała zamrożenie — commit po tym SHA na tej gałęzi jest
  sam w sobie znaleziskiem.
- Stawka rundy: **F1 i F0 zamykają się wyłącznie rundą z zerem znalezisk**
  (reguła zbieżności D-2026-08-07-16). Nie łagodź kryterium.

## Procedura

Wg `PROMPT-WERYFIKATOR` (wzorzec rund 5–6): czysty klon wskazanego SHA do osobnego
katalogu → stos izolowany `gabinet-perturbacje` (po rundzie zgaś) → pełna bramka
(22 kroki, wynik liczbowy; deklarowany stan autora: 267 testów / 2026 asercji /
podłogi 265 — zmierz, nie cytuj) → weryfikacja zamknięć rundy 6 (29 pozycji + N-14,
każde z kontrolą pozytywną i negatywną instrumentu) → własne poszukiwanie.
Pomiar rozstrzygający wykonuje świeży subagent bez Twojego kontekstu.

**Uwaga środowiskowa:** strażnik commitów działa przez `core.hooksPath` w konfiguracji
repozytorium roboczego — Twojego czystego klonu NIE obejmie. Nie potrzebujesz go:
**masz zakaz commitowania gdziekolwiek** poza plikami raportu i kanału.

## Pozycja jawna ataku (D-1) — część zakresu, nie znalezisko

Mechanizm własnych haseł bez funkcji kryptograficznej: porównanie `===` z sekretem
w kolumnie już obecnej w `OCZEKIWANY_SCHEMAT` (zmierzone na `users.nazwa_wyswietlana`;
`9 passed`, obie siatki ślepe). Zadanie: **(a)** odtwórz atak na czystym klonie,
**(b)** oceń, czy istnieje trzecia siatka **pomiarowa, nie deklaratywna**, która go
łapie — propozycja kierunku wraca z dowodem (perturbacją zapalającą kontrolę), nie
jako zalecenie na papierze.

## Znane długi (nie są znaleziskami rundy — znaleziskiem jest coś SPOZA tej listy)

`ZLECENIE-054` §2, w brzmieniu autora: **D-2** dwie allowlisty `--przyczyna` bez
rozróżnienia (zapadka, sufit 2) · **D-3** `TwierdzeniaKomentarzyTest` poza bramką
(14 obejść na 15 prób; przeprojektowanie czeka) · **D-4** wyjątek `.gitleaks.toml`
zawężony do trzech commitów, z warunkiem znoszącym przy merge · **D-5** podłogi
ręczne (spłata przy merge). Sprawdź, czy opis długu zgadza się ze stanem zmierzonym —
rozjazd opisu z rzeczywistością JEST znaleziskiem.

## Klasy do polowania (historia rund 3–6 + wczorajsze)

Wynik zgodny z więcej niż jednym światem · test dowodzący znacznika zamiast skutku ·
instrument pytający o co innego, niż twierdzi · kontrola zaspokojona komentarzem ·
kontrola licząca stan własnej produkcji (C1c) · wspólny klucz po obu stronach porównania
(D-2026-08-08-25b) · fałszywe zapewnienie w opisie kontroli (precedens: ZLECENIE-048 §3
pkt 1 — „wniosek się broni, uzasadnienie zawierało fałsz"). Plus zgodność z zasadami
twardymi CLAUDE.md 1–15.

## ⚠ UZUPEŁNIENIE 12.08 (po ZLECENIE-058) — przeczytaj przed klonowaniem

1. **Gałąź `faza-1-retencja` jest wypchnięta** (wykonał architekt; push nie dodaje
   commitów i nie łamie zamrożenia). Możesz klonować z origin **albo** lokalnie
   (wzorzec rund 5–6) — oba dozwolone.
2. **Czubek gałęzi to `97a11b4`, nie `551c0c8` — i to jest ZNANE, nie znalezisko.**
   Po zamrożonym SHA są dokładnie **dwa commity dokumentacyjne** (`82876ab`, `97a11b4`),
   oba dotykają wyłącznie `docs/ZLECENIA/ZLECENIE-054.md`. Architekt zmierzył:
   `git diff --stat 551c0c8..97a11b4 -- backend/ skrypty/` → **pusto**.
   Doprecyzowanie zakresu zamrożenia (ODPOWIEDZ-058): zamrożenie dotyczy KODU —
   znaleziskiem jest commit dotykający `backend/`, `skrypty/` lub konfiguracji bramki,
   albo JAKIKOLWIEK commit po `97a11b4`. Pomiar wykonuj na `551c0c8`.

## Raport i kanał

`docs/rundy/RUNDA-7-RAPORT.md` — każde znalezisko: co zmierzone, jak odtworzyć, waga;
obowiązkowa sekcja „czego NIE sprawdziłem". Zero znalezisk = napisz to wprost z zakresem
pokrycia. Odpowiedź w kanale: **ODPOWIEDZ-056.md** (S-3). Raport i odpowiedź to jedyne
pliki, które wolno Ci zapisać w drzewie głównym.
