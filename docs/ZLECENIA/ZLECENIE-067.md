# ZLECENIE-067 · 18.08.2026 · OD architekta DO sesji WERYFIKATOR — RUNDA 9

## Przedmiot pomiaru

- **SHA KODU: `d79dc0c9cd1ba65bce944b53c404fb5dc6386e7d`** (gałąź `faza-1-retencja`,
  krótko `d79dc0c`; osiągalne z origin, klonuj z origin albo lokalnie).
  *(Uzupełnienie 18.08 po `ZLECENIE-068` — definicja zamrożenia SPRAWDZALNA zamiast
  „czubka gałęzi", bo commity dokumentacyjne są dozwolone i zgłaszane:)*
  **Warunek zamrożenia:** `git diff --stat d79dc0c..HEAD -- backend/ skrypty/
  .gitleaks.toml` → **musi być PUSTO**. Naruszenie tego warunku = znalezisko;
  commity dotykające wyłącznie `docs/` są dozwolone i zgłoszone (znane:
  `b5cd83f` — meldunek + raport rundy 8 do repozytorium; architekt zmierzył:
  diff kodu pusty). Pomiar wykonuj na `d79dc0c`.
- Stawka: **F1/F0 zamyka wyłącznie runda z zerem znalezisk** (D-2026-08-07-16).
  Zbieżność dotychczas: 11 → 15 → 12 → 29 → 9 → 2.

## Procedura

Wzorzec rund 5–8: czysty klon → własne izolowane stosy (NIE `gabinet-perturbacje` —
montuje drzewo dewelopera; efemeryczne klony, po rundzie `down -v`) → pełna bramka
od zera (deklaracja autora: 22/22, **290 testów / 2130 asercji, podłogi RÓWNO
290/2130**, perturbacje **49 kontroli / 32 scenariusze / 0 pominiętych**, znacznik
zdjęty — zmierz, nie cytuj) → weryfikacja zamknięć → własne polowanie.
Pomiar rozstrzygający — świeży subagent bez Twojego kontekstu.

## Zamknięcia do zweryfikowania (`ODPOWIEDZ-066`; każde z kontrolą pozytywną i negatywną instrumentu)

1. **R8-1**: siatka D-1b mierzy skutek niezależnie od nazwy pola — ładunek z pól,
   które kod czyta (`nazwyPolWejsciowych()`), + pole niczytane przez nikogo;
   perturbacja `d1b_zaklecie` (nazwa spoza baterii) zapala siatkę z właściwej
   przyczyny; twierdzenie z `ODPOWIEDZ-062` §8 sprostowane.
2. **R8-2**: `BlokadaWysylkiTest` pyta realnej aplikacji (bootstrap jądra w osobnym
   procesie ze sterownikiem wysyłającym); pomiar różnicowy: pusty
   `bootstrap/providers.php` → czerwień; sonda z wyłączoną blokadą → `mail=smtp`
   (kontrola przyrządu); pusty wynik/`exec` wyłączony zatrzymuje test.
   Test R7-3 zostaje (rozróżnianie piętra przyczyny).
3. **Wada własna §4**: `WaskieGardloTozsamosciTest` rozszerzony o `routes/` —
   mutacja w `routes/web.php` → 1 failed z nazwą pliku; stan czysty → zielone.

## Znane długi (znaleziskiem jest coś SPOZA listy albo rozjazd opisu ze stanem)

**D-3**: `TwierdzeniaKomentarzyTest` poza bramką (14 obejść na 15) — bez zmian ·
**D-4**: wyjątek gitleaks zawężony i zmierzony, usunięcie przy scalaniu (O-1) ·
**Granice siatki D-1b** wypisane w `ODPOWIEDZ-066` §2 („krok dalej", 3 pozycje) —
nazwane granice przyrządu, wpisane w nagłówek kontroli. **Jeśli którąś da się
wykorzystać w tym środowisku — to znalezisko, nie znana granica.**

## Mapa autora, gdzie uderzyć (`ODPOWIEDZ-066` §7 — mapa, nie ograniczenie)

`nazwyPolWejsciowych()` to nowy parser — rozjazd ze źródłami cicho cofa ładunek do
baterii (bronią 2 asercje; naturalne miejsce trzeciego kroku klasy) · sonda R8-2
zależna od `exec()` i `php` w kontenerze (limit zmierzony) · **zasięg WSZYSTKICH
kontroli skanujących katalogi** — wada §4 wyszła z jednego skanera; sprawdź
pozostałe pod kątem „czy widzą oba katalogi wykonywalne". Plus klasy historyczne
(wynik zgodny z >1 światem · znacznik zamiast skutku · instrument pytający o co
innego · kontrola zaspokojona komentarzem/napisem · wspólny klucz · klasa o krok
dalej).

## Raport i kanał

`docs/rundy/RUNDA-9-RAPORT.md` (sekcja „czego NIE sprawdziłem" obowiązkowa;
zero znalezisk = wprost, z zakresem pokrycia). Odpowiedź: **ODPOWIEDZ-067.md**.
Zakaz commitowania; po rundzie zgaś swoje stosy (`down -v`).
