# Wytyczne pracy — kultura, weryfikacja, zespół agentów

Wspólny standard ekosystemu Fundacji Niepodzielni (ten sam co w repo `konta`, `chat`, dawnym System-rezerwacji). Obowiązuje każdą sesję Claude'a i każdego subagenta w tym repo.

## Język i forma

- Dokumentacja, commity, komunikaty dla ludzi: **polski**. Kod, identyfikatory, klucze konfiguracji: **angielski**.
- Commity małe i opisowe; treść mówi CO i DLACZEGO. Bez `--no-verify`, bez pomijania hooków.
- Wersje zależności przypięte (lockfile commitowany). Podbicia wersji = osobny, świadomy commit.

## Kultura pracy (twarde reguły)

> **Sekcja wspólna ekosystemu** — rdzeń identyczny we wszystkich repo, zmiany
> propaguje architekt. Przykłady i instancje lokalne dopisujemy u siebie.

1. **Jedna ścieżka, jeden piszący.** Nad jednym plikiem/modułem pracuje w danym momencie jedna sesja/agent. Fan-out tylko na rozłączne obszary.
2. **„Zrobione" = zweryfikowane niezależnie.** Weryfikuje sesja/agent, który zmiany NIE pisał: czysty checkout, uruchomienie pełnej bramki od zera, porównanie wyniku z kryterium akceptacji fazy. Bez tego zadanie jest „napisane", nie „zrobione".
3. **Test pozytywny I negatywny dla każdego zachowania.** Reguła bez testu na złamanie jej nie istnieje. Testy liczą wartości, nie obecność elementów na ekranie.
4. **Czerwona bramka to informacja, nie przeszkoda.** Nie obchodzimy, nie wyłączamy testów, nie oznaczamy skip bez wpisu w rejestrze blokerów z uzasadnieniem i planem powrotu.
5. **Uczciwe raportowanie pomiarem.** Twierdzenia o stanie („naprawione", „działa") wyłącznie z dowodem: wynik komendy, log, test. Sprostowania błędnych wpisów robimy nowym wpisem, nie edycją historii.
6. **Rejestr decyzji** (`docs/DECYZJE.md`): każda decyzja projektowa z datą i uzasadnieniem. Podjętych nie relitygujemy — nowa wiedza = nowy wpis z odwołaniem do starego.
7. **Sekrety nigdy w plikach ani w historii.** `.env.example` z nazwami, bez wartości. Wyciek = natychmiastowa rotacja + wpis.
8. **Deploy:** środowiska dev — pełna swoboda; produkcja/publiczna ekspozycja — WYŁĄCZNIE za wyraźną zgodą właściciela (Jakub). Gałąź `main` chroniona konwencją: wchodzi na nią tylko zweryfikowana praca.

## Zarządzanie zespołem agentów i subagentów

> **Sekcja wspólna ekosystemu** — rdzeń identyczny we wszystkich repo, zmiany
> propaguje architekt. Przykłady i instancje lokalne dopisujemy u siebie.

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
6. Zaktualizuj `CURRENT WORK` + `docs/DECYZJE.md`; raport dla właściciela: co zrobione (z dowodami), co czerwone, co dalej, **jakie polecenia były sprzeczne i ile kosztuje cofnięcie**, czego potrzebujesz od człowieka.

## Sprzeczne polecenia architekta

> **Sekcja wspólna ekosystemu** — rdzeń identyczny we wszystkich repo, zmiany
> propaguje architekt. Przykłady i instancje lokalne dopisujemy u siebie.

Wersja kanoniczna, uzgodniona z zespołem helpdesku (07.08.2026):

> Gdy nowe polecenie architekta koliduje z **wcześniejszym poleceniem** —
> wykonaj nowsze, ale **zgłoś rozbieżność w raporcie**, podając trzy rzeczy:
> (a) **co dokładnie się kłóci** — obie wersje wprost, nie „poprzednia
> decyzja"; (b) **co już zostało wykonane** i w jakim stanie jest system;
> (c) **koszt cofnięcia**. Nigdy nie wybieraj po cichu.
>
> **WYJĄTEK:** sprzeczność z `CLAUDE.md` albo z zamkniętą decyzją w
> `docs/DECYZJE.md` to **inny przypadek** — tam **pytasz przed wykonaniem**,
> nie wykonujesz z adnotacją. „Wykonaj nowsze" nie jest furtką do obchodzenia
> zasad twardych.

Uzasadnienie: orkiestrator bywa niespójny, a sesja jest **ostatnim miejscem**,
w którym niespójność da się jeszcze wyłapać. Ciche wybranie jednej wersji
kasuje tę szansę i po miesiącu nikt nie odtworzy, dlaczego system robi to,
co robi.

Raport kończący fazę ma stałą pozycję: **„jakie polecenia były sprzeczne i ile
kosztuje cofnięcie"**. Brak sprzeczności zapisujemy wprost („brak"), żeby
milczenie nie było dwuznaczne.

## Zapis pliku to nie dowód, że plik ma treść

Lekcja zespołu helpdesku (defekt platformy Windows), potwierdzona w tej sesji
dwa razy:

1. **Ścieżki POSIX na Windows.** Git Bash i Python interpretują `/tmp` (i inne
   ścieżki POSIX) **różnie**. Skrypt melduje „zapisałem", a plik jest gdzie
   indziej albo go nie ma. Ta sama klasa błędu co `MSYS_NO_PATHCONV`, która
   wywróciła `--czysty-klon` u hubu. W tej sesji: heredoc basha zapisał
   `/tmp/parser.txt`, a Python z tego samego polecenia zgłosił
   `FileNotFoundError`.
   **Reguła:** w skryptach mieszających bash i Pythona operacje plikowe
   robimy **wewnątrz repozytorium albo katalogu roboczego**, nigdy w `/tmp`;
   albo wszystkie operacje na plikach w **jednym języku**. `/tmp` wewnątrz
   kontenera linuksowego jest bezpieczne — to prawdziwy POSIX.
2. **Meta-reguła.** Po każdym zapisie, na którym opiera się kontrola albo
   decyzja, **przeczytaj plik z powrotem**. „Skrypt się wykonał" ≠ „plik ma
   treść". U helpdesku commit deklarujący zapis przeszedł, bo reszta zmian
   była poprawna — a plik był pusty.

To samo dotyczy podmian w kodzie: `sed`/`str.replace`, które nic nie znalazły,
kończą się **sukcesem**. Dlatego `skrypty/perturbuj.py` ma `podmien()`
podnoszące błąd przy braku trafienia, a każda perturbacja ma **dowód mutacji**.

## Podejrzewaj najpierw własny przyrząd

> **Sekcja wspólna ekosystemu** — rdzeń identyczny we wszystkich repo, zmiany
> propaguje architekt. Tabela instancji i przykłady są **lokalne** i mają
> u nas rosnąć.

**Zasada.** Przyrząd pomiarowy jest częścią badanego systemu i **zawodzi
ciszej** niż przedmiot badania. Gdy kontrola, bramka albo test wygląda na
zepsuty — **sprawdź instrument przed systemem**.

**To jest KOLEJNOŚĆ SPRAWDZANIA, nie WERDYKT.** Korekta architekta z 08.08,
po trzech przypadkach w ekosystemie (hub B7, Gabinet V-8, helpdesk E6):
„przyrząd" bywa w obie strony i część przypadków to **realny defekt systemu
albo kontroli**. Sprawdzamy instrument najpierw, bo tak jest taniej — ale
wynik trzeba **zmierzyć**, a nie założyć.

Zakaz wprost: nie wolno zaklejać etykietą „to był przyrząd" ani błędu
proceduralnego, ani prawdziwej dziury. U nas kosztowało to dwukrotnie —
przy `SESSION_ENCRYPT` (V-2/V-3) nie było nawet atrybucji, tylko ciche
„u mnie nie występuje", a przy B7 (V-8) przypisałem kontroli mechanizm,
którego perturbacja nigdy nie uruchamia.

Synteza zespołu helpdesku, potwierdzona czterokrotnie w trzech repozytoriach.
We wszystkich instancjach mierzony system działał bez zarzutu — **kłamał
przyrząd**.

| przyrząd | jak skłamał |
|---|---|
| perturbacja bez mutacji | „kontrola nie zareagowała" na naruszenie, którego **nie było** |
| filtr `grep` wyjścia | ukrył linię diagnozy, dla której uruchomiono przebieg |
| edycja skryptu bash **w locie** | `syntax error` wskazujący na POPRAWNY kod (bash czyta plik przyrostowo; `bash -n` przechodzi!) |
| ścieżki `/tmp` POSIX na Windows | „zapisano plik", a plik jest gdzie indziej (klasa `MSYS_NO_PATHCONV`) |
| perturbacja kłamiąca o kontroli | sprawna kontrola uznana za wadliwą (U5) |
| **kontrola statyczna zamiast uruchomienia** | `bash -n` przechodzi, choć wołana podkomenda nie istnieje; typy i lint przechodzą, a kod pada przy starcie |

**Reguły operacyjne.**

- **Nie edytuj skryptu, który właśnie się wykonuje.** Zatrzymaj, edytuj,
  uruchom od nowa.
- **Filtr, parser i formatowanie wyjścia są częścią pomiaru.** Zanim uznasz
  „nic nie pokazało" — obejrzyj **surowe wyjście zapisane do pliku**.
- **Perturbacja, która nie trafiła w mutację, MUSI przerwać błędem** (dowód
  mutacji). Cicha podmiana bez trafienia to najgorszy możliwy wynik:
  perturbacja melduje sukces, nie zmieniwszy niczego.
- **Po zapisie, na którym opiera się decyzja — odczytaj plik z powrotem.**
  „Skrypt się wykonał" ≠ „plik ma treść".
- **Każda bramka musi URUCHOMIĆ, nie tylko sprawdzić składnię.** Kontrola
  statyczna nie zastępuje uruchomienia. Wzorcowy przypadek: drugi biały ekran
  makiety (dziennik makiety, rozdz. 23) — wyrażenie regularne z surowymi
  bajtami NUL przeszło **wszystkie** kontrole statyczne i padło przy starcie.
- **Pomiar z numerem linii bije model w głowie — także architekta.** Spór
  o fakt techniczny rozstrzyga log, nie autorytet. Gdy dostajesz twierdzenie
  techniczne sprzeczne z własnym pomiarem — ufaj pomiarowi i **zgłoś
  rozbieżność**. Dotyczy wyłącznie twierdzeń o zachowaniu systemu; polecenia
  wykonujemy.

**Biegnąca suita pomiarowa DESTABILIZUJE środowisko.** Konsolidacja helpdesk
U3a + Gabinet W-11. W trakcie działania bramki albo perturbacji:

- **NIE commituj.** `git add -A` złapie stan sperturbowany. U helpdesku tak
  trafił do repozytorium heap 1 GB — twarda reguła z `CLAUDE.md` była
  **fałszywa w repo przez kilka commitów**, a nikt tego nie zauważył.
- **NIE edytuj plików wejściowych.** Bash czyta skrypt przyrostowo, więc
  edycja w locie daje `syntax error` wskazujący na POPRAWNY kod; edycja
  źródeł miesza pomiar.
- **NIE ufaj stanowi drzewa ani bazy.** U nas perturbacje wykonywały
  `migrate:fresh --force` na bazie DEWELOPERA (W-11), bo domyślnym projektem
  compose był `gabinet`.

Docelowo perturbacje mają działać na **efemerycznym klonie i własnym projekcie
compose**, nie na drzewie roboczym. Do czasu przeniesienia: własny projekt
compose już jest wymuszony, a commit robimy **dopiero po zakończeniu
perturbacji i po sprawdzeniu, że drzewo wróciło do stanu sprzed przebiegu**.

**Dokumentacja o kodzie też jest przyrządem — i też kłamie.** Komentarz,
nagłówek pliku i wpis w dzienniku decyzji („naprawione", „robi X") myli
następnego czytelnika i weryfikatora **ciszej niż sam kod**, bo nikt go nie
uruchamia. W jednej partii napraw złapaliśmy trzy takie obietnice, wszystkie
nieprawdziwe wobec kodu: „każda zmiana jest cofana" (W-11), „PHP_INT_MAX
obsłużony" (W-5), „najpierw zatrzymujemy harmonogram" (W-15).

Przy oznaczaniu czegokolwiek jako „zamknięte" sprawdzaj więc **także, czy
komentarz nie obiecuje więcej, niż kod robi**. Egzekwowane maszynowo w części,
w której da się: `ObietniceKomentarzyTest` wymaga, by każde znalezisko powołane
w kodzie produkcyjnym („U-7", „W-5") było NAZWANE w co najmniej jednym teście.
Obietnica bez dowodu zapala bramkę.

**Jednorodny ciąg wniosków w jedną stronę jest sam podejrzany.** Gdy każde
kolejne znalezisko wskazuje tę samą przyczynę — a zwłaszcza przyczynę wygodną
dla Ciebie („to wina przyrządu, nie systemu") — to sam wzorzec jest sygnałem.
Zespół helpdesku sformułował to wprost: „osiem instancji pod rząd »winny
przyrząd, nie system« to podejrzanie jednorodny wynik".

**Atrybucji wygodnej nie obali ten, komu ona służy.** Dlatego pytanie zadajemy
weryfikatorowi WPROST, jako osobne zadanie rundy: *czy część znalezisk
zaklasyfikowanych jako wada przyrządu nie jest w rzeczywistości wadą systemu
przebraną za wadę przyrządu*. Pytanie ma trafić do zlecenia rundy, nie do
naszej samooceny — samoocena tu z definicji nie działa.

**Kontrola dzieląca mechanizm ze swoim przedmiotem jest w tym mechanizmie
NIEFALSYFIKOWALNA.** Reguła C1 zespołu helpdesku. U nich kontrola dzieliła
zapytanie ze sprzątaczką, więc podłożona zaległość znikała wspólnym
mechanizmem — kontrola świeciła zielono, a przebiegi zostawiały dane wrażliwe
w 13 obiektach. Wcześniej inna kontrola dzieliła uruchomienie skryptu
konfiguracyjnego z testem i **naprawiała stan, który badała**.

**Lek:** kontrola musi patrzeć ŚCIEŻKĄ NIEZALEŻNĄ od przedmiotu — osobne
zapytanie, skan wstępny, inny mechanizm. Inaczej perturbacja znika tą samą
drogą, którą kontrola patrzy.

Nasze instancje tej klasy:

- **Ślad wylogowania w tym samym cache'u co rejestr sesji.** `SladWylogowania`
  liczył wejścia i awarie w cache'u, a `RejestrSesji` — obserwowany przedmiot —
  też tam mieszkał. Wyczyszczenie cache'u kasowało JEDNO I DRUGIE, więc licznik
  pokazywał spójne zero i nie dało się odróżnić „nie było czego kasować" od
  „mechanizm padł". Trafiło nas we własnym teście: `Cache::flush()` usunął cel
  perturbacji. Ślad idzie teraz do **pliku**.
- **Kontrola odwrotna czekająca na harmonogram.** Perturbacja pulsu czekała, aż
  harmonogram sam zapisze wpis — mierzyła więc jego cadencję, nie czujność
  kontroli, i losowo padała po 90 s. Puls zapisujemy teraz **wprost**,
  niezależnym poleceniem.

**Weryfikacja USŁUGI STANOWEJ: „czysty klon" dzielący działającą instancję to
fikcja.** Klon, który podłącza się do cudzej bazy, kolejki albo poczty, mierzy
CUDZY stan. Wymóg: w pełni **izolowany, efemeryczny projekt** — własna nazwa
projektu compose, własne porty, wolumeny scope'owane projektem — postawiony na
**sekretach testowych zdefiniowanych w repozytorium**, nigdy na kopii `.env`
dewelopera.

**Klon weryfikatora NIGDY nie trzyma prawdziwych sekretów.** U zespołu
helpdesku weryfikator skopiował `.env` z sekretami do katalogu tymczasowego —
near-miss.

U nas dotyczyło to bramki wprost: `przygotuj_env()` robiło „jeśli `.env`
istnieje — używam istniejącego", więc na maszynie dewelopera przebieg mielił
JEGO plik, a na czystym klonie — plik z `.env.example`. Dwa różne środowiska
pomiarowe; stąd wzięło się V-2 („42 kontrole" zmierzone w jedynym środowisku,
w którym wychodzą). Bramka buduje teraz własny plik od zera przy każdym
przebiegu, wyłącznie z definicji w repozytorium.

**Nasze instancje** (rosną — dopisuj):

1. `skrypty/perturbacje-powtarzalne.sh` dawał **fałszywe zielone**: podsumowanie
   wyławiał grepem, więc padnięty zestaw dawał pustkę, a trzy pustki są
   identyczne. Zmierzone: `bash -c 'echo start; exit 1' | grep '^PERTURBACJE'`
   → `ROZNE=0`. (D-2026-08-07-22)
2. Ten sam skrypt **brudził drzewo własnym dziennikiem**, po czym oskarżał
   perturbacje o wyciek.
3. Ten sam skrypt porównywał drzewo z **czystym repozytorium** zamiast ze
   stanem sprzed przebiegu — każda praca w toku wyglądała jak wyciek
   z perturbacji.
4. `oczekuj_czerwone` sądziło **wyłącznie po kodzie wyjścia**, z wyjściem
   w `/dev/null`: „wykryto naruszenie" było nieodróżnialne od „kontrola
   w ogóle się nie wykonała". Tak przepadły U-2 i U-6.
5. Heredoc basha zapisał `/tmp/parser.txt`, Python z tego samego polecenia
   zgłosił `FileNotFoundError` — dwa razy tego samego dnia.

## Czego agentom nie wolno nigdy

Wdrażać na produkcję bez zgody właściciela · zapisywać sekretów · wyłączać/obchodzić bramek · relitygować decyzji z `CLAUDE.md` i `docs/DECYZJE.md` · pracować dalej mimo niezrozumienia wymagania (wtedy: pytanie do właściciela w raporcie, praca na innym froncie).
