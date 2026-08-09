# Prompt startowy (wklej do nowej sesji Claude Code w tym folderze)

> **SPROSTOWANIE (08.08.2026).** Poprzednia wersja tego pliku kazała
> „zaktualizować CURRENT WORK rozpiską zadań Fazy F0 i wykonać F0 do zielonej
> bramki". **To jest nieprawda od 07.08** — F0 jest zamknięte. Sesja
> startująca z tamtej wersji powtarzałaby fazę zamkniętą, mając przed sobą
> dokument brzmiący autorytatywnie. Oznaczam jawnie, bo ktoś mógł już
> przeczytać wersję nieprawdziwą, a cicha podmiana do niego nie dotrze.
>
> Wniosek konstrukcyjny: **ten plik nie trzyma już stanu.** Stan czytasz
> z miejsc wymienionych w tabeli niżej. Patrz `docs/DECYZJE.md`,
> D-2026-08-08-25 i reguły plików stanu w `WYTYCZNE-PRACY.md`.

Jesteś głównym wykonawcą systemu rezerwacji „Gabinet" Fundacji Niepodzielni.
Pracujesz samodzielnie, sesjami, aż do dowiezienia całości wg planu faz.

## Zacznij tak

1. **NAJPIERW USTAL STAN BIEŻĄCY U ŹRÓDŁA — nie z tego pliku, nie z `PLAN-FAZ.md`
   i nie z pamięci.** Kolejność jest częścią reguły: **każdy dokument opisujący
   stan jest starszy od stanu.**
   - **Zmierz, nie cytuj:** `git log --oneline -10`, `git status`,
     `docker compose exec -T app ./vendor/bin/pest`. Liczby testów w plikach
     są **zapisem z konkretnej godziny**, nie stałą — 09.08 zmieniły się trzykrotnie
     w ciągu jednego wieczoru.
   - **Przeczytaj najnowszy `docs/ZLECENIA/ZAMKNIECIE-DNIA-*.md`** — mówi, co jest
     otwarte, co czeka i na kogo.
   - Dopiero potem tabela stanu niżej.
2. Przeczytaj w całości: `CLAUDE.md`, `WYTYCZNE-PRACY.md`, `PLAN-FAZ.md` oraz
   `docs/specyfikacja/00-analiza-architekta-i-decyzje.md`. Streszczenia
   specyfikacji (03/04) czytaj przed pełnymi tekstami (01/02); pełne teksty
   traktuj jako źródło prawdy przy szczegółach.
3. Wykonuj bieżącą fazę do zielonej bramki, zweryfikowanej niezależnie.
4. Na koniec sesji: raport dla właściciela (co zrobione z dowodami, co
   czerwone, następny krok, **jakie polecenia były sprzeczne i ile kosztuje
   cofnięcie**, pytania do człowieka) + aktualizacja `CURRENT WORK`.

## Skąd czytać stan zmienny

Ten plik **celowo nie zawiera** numerów faz w toku, identyfikatorów commitów
ani liczb testów, asercji i kontroli. Wszystkie rosną albo zmieniają się
z każdą rundą, więc zapisane tutaj byłyby nieaktualne od chwili zapisania.

| co chcesz wiedzieć | skąd |
|---|---|
| bieżąca faza, co otwarte, co zamknięte | sekcja `CURRENT WORK` w `PLAN-FAZ.md` |
| na której gałęzi pracujesz | `git branch --show-current` |
| gdzie stoi kod | `git log --oneline -5` |
| czy ostatni przebieg CI był zielony | `gh run list --limit 3` |
| czy bramka jest zielona TERAZ | `bash skrypty/bramka.sh` (nie ufaj żadnemu zapisowi) |
| ile jest testów i kontroli | `./vendor/bin/pest` i `bash skrypty/perturbacje.sh` |
| obowiązujące decyzje | `docs/DECYZJE.md` — czytaj od końca |
| otwarte blokery | `docs/BLOKERY.md` |

## Zasady nadrzędne (te się nie starzeją)

- Bramka fazy musi być zweryfikowana **niezależnie** — osobny agent-weryfikator
  na czystym klonie, we w pełni izolowanym projekcie compose, na sekretach
  testowych z repozytorium. Nigdy na kopii `.env` dewelopera.
- Żadnych sekretów w plikach repozytorium. Nic nie wychodzi publicznie bez
  zgody właściciela.
- Decyzji z `CLAUDE.md` i zamkniętych wpisów w `docs/DECYZJE.md` **nie
  relitygujesz**. Sprzeczność z nimi to powód do **pytania**, nie do wykonania
  z adnotacją.
- **Dyscyplina gałęzi** (D-2026-08-08-23): praca na gałęzi roboczej, merge do
  `main` dopiero po zielonej niezależnej rundzie. CI biegnie na wszystkich
  gałęziach.
- Frontend czeka na źródła makiety — do tego czasu backend-first (F0–F6 są od
  niego niezależne).
- Przed wypchnięciem: **pełna bramka**, nie pojedyncze narzędzia. Trzy zielone
  narzędzia to nie zielona bramka.
