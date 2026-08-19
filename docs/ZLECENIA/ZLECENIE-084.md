# ZLECENIE-084 · 19.08.2026 · OD architekta DO sesji KOD-F1 — WARIANT C: domykamy F1

**Decyzja właściciela: wariant C.** Zamykamy fazę fundamentową z jawnie opisaną granicą;
kontrola skutkowa wchodzi jako pierwsze zadanie etapu B. **Zamrożenie ZDJĘTE.**

## 1. Zapis granicy — to jest zadanie o najwyższej wadze w tym zleceniu

Napisz **`docs/GRANICA-R13-1.md`** i wpis `D-2026-08-19-03` w `docs/DECYZJE.md`.
Wymagana treść (bez łagodzenia, bez ozdabiania):

1. **Wektor dosłownie**: nazwa narzędzia omijającego konstruktor sklejona przez zmienne
   pomocnicze (`$a='unse'; $b='rialize'; $f=$a.$b;`) omija `Kod::wywolaniaOmijajaceKonstruktor`;
   wraz z wariantami `.=`, `implode`, `strrev`, `sprintf`, heredoc, `new $zmienna`.
2. **Skutek, gdyby ktoś tak napisał**: odtworzenie `TozsamoscSesji` z pominięciem
   konstruktora → `zaktualizuj` → tożsamość i role z wyboru piszącego, przy zielonej bramce.
3. **Dlaczego to NIE jest luka osiągalna z zewnątrz**: wymaga dopisania kodu do
   repozytorium; żaden użytkownik (pacjent, personel, gość) nie ma tej drogi. **Podaj to
   jako twierdzenie sprawdzalne**, nie jako zapewnienie — wskaż, co trzeba by zmierzyć,
   żeby je obalić.
4. **Dlaczego nie naprawiamy dalej w tej fazie**: rozstrzygnięcie klasy z rundy 13 —
   „kolejne rozszerzanie skanera to ta sama denylista o piętro wyżej, brzeg będzie zawsze".
   Kontrola czytająca kod nie rozpozna wszystkich zapisów tej samej czynności.
5. **Co pokryte, a co nie** — tabelą: formy naturalne (łapane) vs formy wymagające
   celowego obejścia (nie łapane).
6. **Druga linia obrony**: niezależne rundy weryfikacyjne (13 wykonanych; ta klasa
   wychwycona w każdej), przegląd kodu przy scaleniu.
7. **Termin naprawy**: etap B, pierwsze zadanie — **kontrola skutkowa** (każda tożsamość
   w sesji ma odpowiadający dowód weryfikacji z tego samego żądania; nie pyta o kod,
   więc nie ma tego brzegu).
8. **WARUNEK ZNOSZĄCY**: gdyby powstała ścieżka pozwalająca uruchomić kod spoza
   repozytorium (wykonywanie treści z żądania, wtyczki, konfiguracja wykonywalna),
   granica **przestaje obowiązywać i wraca jako blokada** — do sprawdzenia przy każdym
   nowym module przyjmującym treść z zewnątrz.

## 2. Okno scaleniowe — wykonanie wg `LISTA-SCALENIOWA-F1.md`

Warunki wejścia **W1–W4** sprawdź i wypisz wynikami, zanim ruszysz. W1 („runda z zerem")
zastępuje **decyzja właściciela z 19.08 o zamknięciu z granicą** — zapisz to wprost
w meldunku, żeby nikt później nie odczytał tego jako pominięcia reguły.

Operacje: **O-1/O-2/O-2b** (przepisanie historii, wariant C z **mapą SHA** zachowaną jako
`docs/rundy/MAPA-SHA-<data>.txt`; **usuwa D-4 ORAZ D-5 — usunięcie tylko jednego jest
znaleziskiem**) → **O-3** (usunięcie wpisów z `.gitleaks.toml`, dowód dwustronny: skan
czysty + przynęta zapala) → **O-4** (jawne sprostowanie odwołań do przepisanych SHA) →
**O-5** (konsolidacja wpisów D: rozstrzygnięcia Q-1…Q-19, Q-22, P-08, Q-23, R-1=10 min,
zasady z lekcji F1) → **O-6** (automatyzacja podłóg, kontrola negatywna: usunięcie testu
zapala) → **O-6b** wykonane wcześniej → **O-6c** (kontrola kształtu wartości w `docs/`,
wymagania z `ODPOWIEDZ-076` §2; lista znanych cytatów ma się kurczyć po O-2b) → **O-7**
(`.zakres-sesji` wersjonowane, `.gitignore` poprawiony; kontrola negatywna w każdym
aktywnym drzewie) → **O-8** (pełna bramka OD ZERA **bez potoku** + pełny zestaw
perturbacji).

**O-9 (merge do `main`) WYKONUJE ARCHITEKT po zgodzie właściciela — Ty go nie robisz.**

## 3. Meldunek

**ODPOWIEDZ-084**: wyniki W1–W4, wynik każdej operacji z dowodem, surowe wyjście bramki
i perturbacji po O-8, nowe SHA do scalenia, stan długów (D-4 i D-5 mają zniknąć; D-3
zostaje z terminem do etapu B), oraz „czego nie zrobiłem". Po meldunku ja przeprowadzam
przegląd i pytam właściciela o zgodę na merge.

Cisza nie obowiązuje — to jest okno scaleniowe, commity są jego treścią. Obowiązuje za to
Twoja własna reguła: **bramka po każdym commicie**.
