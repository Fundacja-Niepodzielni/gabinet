# Wytyczne pracy — kultura, weryfikacja, zespół agentów

Wspólny standard ekosystemu Fundacji Niepodzielni (ten sam co w repo `konta`, `chat`, dawnym System-rezerwacji). Obowiązuje każdą sesję Claude'a i każdego subagenta w tym repo.

## Język i forma

- Dokumentacja, commity, komunikaty dla ludzi: **polski**. Kod, identyfikatory, klucze konfiguracji: **angielski**.
- Commity małe i opisowe; treść mówi CO i DLACZEGO. Bez `--no-verify`, bez pomijania hooków.
- Wersje zależności przypięte (lockfile commitowany). Podbicia wersji = osobny, świadomy commit.

## Kultura pracy (twarde reguły)

1. **Jedna ścieżka, jeden piszący.** Nad jednym plikiem/modułem pracuje w danym momencie jedna sesja/agent. Fan-out tylko na rozłączne obszary.
2. **„Zrobione" = zweryfikowane niezależnie.** Weryfikuje sesja/agent, który zmiany NIE pisał: czysty checkout, uruchomienie pełnej bramki od zera, porównanie wyniku z kryterium akceptacji fazy. Bez tego zadanie jest „napisane", nie „zrobione".
3. **Test pozytywny I negatywny dla każdego zachowania.** Reguła bez testu na złamanie jej nie istnieje. Testy liczą wartości, nie obecność elementów na ekranie.
4. **Czerwona bramka to informacja, nie przeszkoda.** Nie obchodzimy, nie wyłączamy testów, nie oznaczamy skip bez wpisu w rejestrze blokerów z uzasadnieniem i planem powrotu.
5. **Uczciwe raportowanie pomiarem.** Twierdzenia o stanie („naprawione", „działa") wyłącznie z dowodem: wynik komendy, log, test. Sprostowania błędnych wpisów robimy nowym wpisem, nie edycją historii.
6. **Rejestr decyzji** (`docs/DECYZJE.md`): każda decyzja projektowa z datą i uzasadnieniem. Podjętych nie relitygujemy — nowa wiedza = nowy wpis z odwołaniem do starego.
7. **Sekrety nigdy w plikach ani w historii.** `.env.example` z nazwami, bez wartości. Wyciek = natychmiastowa rotacja + wpis.
8. **Deploy:** środowiska dev — pełna swoboda; produkcja/publiczna ekspozycja — WYŁĄCZNIE za wyraźną zgodą właściciela (Jakub). Gałąź `main` chroniona konwencją: wchodzi na nią tylko zweryfikowana praca.

## Zarządzanie zespołem agentów i subagentów

- **Orkiestrator (sesja główna) nie pisze kodu równolegle z subagentami** na tej samej ścieżce — deleguje, zbiera, weryfikuje.
- **Kiedy subagenci:** research/przeszukiwanie (zawsze można), niezależne moduły (rozłączne pliki), masowy boilerplate/testy. **Kiedy NIE:** drobiazgi (koszt zimnego startu > zysk), praca na wspólnych plikach, decyzje architektoniczne.
- **Weryfikator to osobny agent/sesja** z promptem: „nie pisałeś tej zmiany; sklonuj czysto, uruchom bramkę, spróbuj OBALIĆ twierdzenia z raportu wykonawcy". Domyślna postawa: sceptyczna.
- **Stan przekazujemy przez pliki, nie przez pamięć rozmowy:** `PLAN-FAZ.md` sekcja `CURRENT WORK` (co w toku, co zablokowane, następny krok) aktualizowana na koniec każdej sesji. Nowa sesja zaczyna od jej przeczytania.
- **Jeden agent = jedno zlecenie z kryterium końca.** Prompt subagenta zawiera: zakres plików, czego nie wolno dotykać, definicję „zrobione", format raportu.
- Wyniki subagentów traktuj jak dane do sprawdzenia, nie jak fakty.

## Rytm pracy w fazie

1. Przeczytaj `CLAUDE.md` + `PLAN-FAZ.md` (bieżąca faza + `CURRENT WORK`).
2. Rozpisz fazę na zadania z kryteriami; zapisz w `CURRENT WORK`.
3. Implementuj (sam lub delegując wg zasad wyżej); commituj przyrostowo.
4. Uruchom pełną bramkę fazy (testy + statyka + kryteria akceptacji).
5. Zleć niezależną weryfikację. Czerwone → napraw albo zarejestruj bloker.
6. Zaktualizuj `CURRENT WORK` + `docs/DECYZJE.md`; raport dla właściciela: co zrobione (z dowodami), co czerwone, co dalej, czego potrzebujesz od człowieka.

## Czego agentom nie wolno nigdy

Wdrażać na produkcję bez zgody właściciela · zapisywać sekretów · wyłączać/obchodzić bramek · relitygować decyzji z `CLAUDE.md` i `docs/DECYZJE.md` · pracować dalej mimo niezrozumienia wymagania (wtedy: pytanie do właściciela w raporcie, praca na innym froncie).
