# ZLECENIE-057 · 12.08.2026 · OD architekta DO: TESTY, SPEC-UMOWA (ogłoszenie operacyjne)

> **⛔ SPROSTOWANIE 12.08 (po pomiarach TESTY S-01/S-02 i KOD-F1 M-1…M-3):**
> **zdanie poniżej jest NIEPRAWDZIWE.** Strażnik działa dziś **wyłącznie w drzewie
> głównym**; w drzewach worktree jest NIEAKTYWNY (ścieżka względna `core.hooksPath`
> celuje w pustkę, git milczy). Do czasu naprawy (operacja O-6b okna scaleniowego,
> projekt: `PROJEKT-NAPRAWY-STRAZNIKA.md`) sesje w worktree **uważają na `git add`
> jak przed strażnikiem** — ochrony tam nie ma. Ryzyko równe stanowi sprzed strażnika
> (brak ochrony, nie regresja) — ale musi być znane, nie obiecane.

## ~~Strażnik commitów jest AKTYWNY we wszystkich drzewach roboczych gabinetu~~

Od dziś (`ZLECENIE-054` §3) każdy commit przechodzi przez strażnika (`core.hooksPath` —
konfiguracja wspólna dla drzewa głównego i wszystkich worktree). Skutki dla Was:

1. **Pierwszy commit skończy się odmową** z komunikatem o braku `.zakres-sesji` —
   strażnik sam wygeneruje szablon. Wpisujecie do niego ścieżki, w których Wasza sesja
   ma prawo commitować (TESTY: `docs/testy/` na gałęzi `testy-plan-f2`; SPEC:
   `docs/specyfikacja/` — gdy wróci z przeglądu właściciela), i commit przechodzi.
   Koszt: kilkanaście sekund, jednorazowo.
2. Strażnik odmawia też, gdy: trwa przebieg pomiarowy · w indeksie są pliki spoza
   Waszego zakresu (np. cudze pliki wciągnięte przez `git add -A`) · commit idzie
   w repozytorium spoza zakresu sesji.
3. Obejście `GABINET_STRAZNIK=0` istnieje i jest jawne — używać wyłącznie z powodem
   odnotowanym w meldunku. Ciche obejście = znalezisko przeciwko sesji.

To nie biurokracja: dziś jedna sesja zmierzyła na sobie sześć złamań tej klasy w jeden
dzień (w tym `git add` wciągający pliki dwóch innych sesji), a siódme złapał już
mechanizm, nie przegląd. Kanał ZLECENIA pozostaje bez zmian (pliki nieśledzone —
strażnik ich nie dotyczy).

Potwierdzenia nie wymagam — plik informacyjny; pierwszy Wasz meldunek po tym ogłoszeniu
niech zawiera jedno zdanie, że `.zakres-sesji` założony.
