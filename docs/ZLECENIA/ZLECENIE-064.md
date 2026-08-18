# ZLECENIE-064 · 18.08.2026 · OD architekta DO sesji WERYFIKATOR — RUNDA 8

## Przedmiot pomiaru

- **SHA: `179c05c696f6535ed4d4c9d839e623d4a9ea5e56`** (gałąź `faza-1-retencja`,
  krótko `179c05c`; osiągalne z origin — klonuj z origin albo lokalnie).
- **Czubek gałęzi: `7f4c65f`** *(uzupełnienie 18.08 po `ZLECENIE-065`)* — **dwa ZNANE
  commity dokumentacyjne** po zamrożeniu: `d620450` (meldunek + kanał) i `7f4c65f`
  (poprawka prawdziwości pliku stanu: data pomiaru 18.08, perturbacje 48 — zgłoszona
  samodzielnie przez autora, architekt zmierzył: `git show --stat` = 1 plik PLAN-FAZ.md,
  diff kodu `179c05c..7f4c65f` pusty). Zamrożenie dotyczy KODU (`backend/`, `skrypty/`,
  konfiguracja bramki); znaleziskiem jest commit dotykający kodu albo jakikolwiek
  commit po `7f4c65f`. Pomiar wykonuj na `179c05c`.
- Stawka: **F1/F0 zamyka wyłącznie runda z zerem znalezisk** (D-2026-08-07-16).

## Procedura

Wzorzec rund 5–7: czysty klon → **własny izolowany stos** (lekcja rundy 7:
`gabinet-perturbacje` montuje drzewo dewelopera — nie używaj; postaw efemeryczne
klony jak `gabinet-r7`, po rundzie `down -v`) → pełna bramka od zera (deklaracja
autora: 22/22, **289 testów / 2119 asercji, podłogi RÓWNO 289/2119**, perturbacje
48 kontroli, znacznik zdjęty po przebiegu — zmierz, nie cytuj) → weryfikacja
zamknięć → własne polowanie. Pomiar rozstrzygający — świeży subagent.

## Zamknięcia do zweryfikowania (każde z kontrolą pozytywną i negatywną instrumentu)

1. **R7-1…R7-9** wg `ODPOWIEDZ-062` §2 — wraz z odpowiedziami „krok dalej"
   (twierdzenie „nigdzie, sprawdziłem X" też podlega obaleniu).
2. **Siedem wad własnych** §4.1–4.7 — szczególnie uchwyty sygnałów (4.5, 4.6:
   kolejność znacznik/zamek przy SIGTERM→SIGKILL) i adres pulsu z mechanizmu (4.7).
3. **O-6b**: strażnik w katalogu wspólnym, tożsamość z `--git-common-dir`,
   K-1/K-2 w każdym aktywnym worktree.
4. **D-1b**: `SiatkaPomiarowaTozsamosciTest` + perturbacja `d1b` — siatka pomiarowa
   czerwona z właściwej przyczyny, deklaratywne zielone.

## Znane długi (znaleziskiem jest coś SPOZA listy albo rozjazd opisu ze stanem)

- **D-3**: `TwierdzeniaKomentarzyTest` poza bramką (14 obejść na 15) — bez zmian.
- **D-4**: wyjątek gitleaks zawężony (`condition="AND"`, pełne SHA czterech commitów)
  i zmierzony dwustronnie; usunięcie przy scalaniu (O-1). Sprawdź, czy opis = stan.
- **D-2**: SPŁACONY (sufit zapadki 0) — deklaracja do obalenia.

## Wskazania autora, gdzie patrzeć (ZLECENIE-062 §8 — przyjmij jako mapę, nie ograniczenie)

Przyrząd pomiarowy zmieniany w tym cyklu (`bramka.sh`, `perturbacje.sh`,
`perturbacja-odwrotna.sh`, `odczyt-przyczyn.py`) — naturalne miejsce ataku ·
zależność dwóch kontroli od `exec()` (limit zmierzony, nie założony) · brak Pythona
w kontenerze (ograniczenie zmierzone osobnym testem) · bateria nazw w D-1b nie jest
dowodem pokrycia. Plus klasy historyczne: wynik zgodny z >1 światem · znacznik
zamiast skutku · instrument pytający o co innego · kontrola zaspokojona komentarzem/
napisem · wspólny klucz · **„klasa o krok dalej"** (mianownik rundy 7 — sprawdź,
czy nie przeniosła się o KOLEJNY krok).

## Raport i kanał

`docs/rundy/RUNDA-8-RAPORT.md` (format rund 6–7; sekcja „czego NIE sprawdziłem"
obowiązkowa; zero znalezisk = wprost, z zakresem pokrycia). Odpowiedź:
**ODPOWIEDZ-064.md**. Zakaz commitowania; jedyne zapisy: raport + plik kanału.
Po rundzie zgaś swoje stosy (`down -v`).
