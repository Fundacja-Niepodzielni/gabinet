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

**Perturbacja zaliczona z INNEJ przyczyny niż badana to fałszywe zielone.**
Odmiana C1 od zespołu helpdesku (P25): u nich nadpisanie tematu wywaliło frazę
próbkującą, kontrola zgłosiła „brak materiału do zbadania", a harness zaliczył
to jako wykrycie, bo identyfikator się zgadzał.

**Dowód mutacji i przyczyna czerwieni to DWIE RÓŻNE RZECZY.** Dowód mutacji
mówi, że naruszenie weszło w życie. Nie mówi nic o tym, skąd wzięła się
czerwień. Traktowanie ich jak jednego kosztowało nas pół sesji.

Dwa mechanizmy, o różnej jakości dowodu:

- **Denylista** (podłoga, działa wszędzie): czerwień pasująca do znanych klas
  awarii pobocznych — brak materiału, niedziałające środowisko, błąd składni
  wprowadzony przez samą mutację — jest **porażką perturbacji**. Mówi „ta
  czerwień nie jest jedną ze **znanych** awarii pobocznych"; nowa klasa awarii
  ją obejdzie.
- **Allowlista** (`--przyczyna`, dowód): czerwień musi zawierać wskazany
  fragment. Mówi „ta czerwień pochodzi **stąd**".

Tam, gdzie fałszywe zielone kosztuje **pieniądze pacjenta albo dostęp do
kartotek**, podłoga nie wystarcza — używamy allowlisty. U nas: granice kwot,
zamrażanie reguł, retencja, tożsamość (§2, białe listy ról, wylogowanie).
Reszta zostaje na podłodze — i mówimy to wprost, zamiast udawać kompletność.

**Wzorca przyczyny NIE PRZEPISUJEMY Z PAMIĘCI.** Kopiujemy fragment dosłownie
z komunikatu kontroli albo dopasowujemy bez wrażliwości na wielkość liter.
Pierwszy wzorzec, jaki tu powstał, brzmiał „zamrożon", a komunikat mówi
„ZAMROŻONĄ" — perturbacja reguły §4 zapaliła się z niezgodną przyczyną
w pierwszym przebiegu. To ta sama klasa co „allowlista wpisana z pamięci
zamiast zmierzona".

**TRZY ZIELONE NARZĘDZIA TO NIE ZIELONA BRAMKA.** Zespół hubu puścił testy
i Pint, wypchnął dwa commity — a pełna bramka pokazała czerwone: Larastan na
poziomie `max` widzi to, czego środowisko uruchomieniowe nie widzi (`define()`
z wartością z wywołania funkcji nie istnieje dla analizy statycznej).

Bramka jest **jednym skryptem** dokładnie po to. Nie wypychamy po częściowej
weryfikacji — także wtedy, gdy zmiana wygląda na niegroźną („to tylko stała",
„to tylko dokumentacja", „to tylko wyzwalacz CI").

U nas natychmiast: commit `f4971ac` poszedł **bez pełnej bramki po zmianach** —
bramka biegła na poprzednim commicie, a potem doszły zmiany w CI, `PLAN-FAZ`
i wytycznych. Wyszło dobrze, ale „wyszło dobrze" nie jest metodą. Przy okazji
znalazła się realna usterka, której częściowa weryfikacja nie mogła złapać:
krok diagnostyczny CI wołał `docker compose logs` **bez** `GABINET_PREFIX`,
który od naprawy V-6 jest wymagany — czyli milczałby dokładnie wtedy, gdy jest
potrzebny.

**Plik stanu jest przyrządem — i to najgroźniejszym.** Następna sesja startuje
z jego treści, więc jego nieaktualność propaguje się na **wszystkie** jej
decyzje. Trzy reguły (wkład Gabinetu i sesji `konta`, obie zmierzone):

1. **Nie zapisuj identyfikatorów samozwrotnych.** SHA commita w pliku, który
   ten commit tworzy, jest nieaktualny od chwili zapisu — dopisywanie go to
   praca, która z definicji nie może się udać. Zapisuj to, co się NIE starzeje
   (gałąź, co otwarte, obowiązujące decyzje) plus **skąd czytać stan zmienny**
   (`git rev-parse`, `gh run list`). Struktura zamiast ręcznej kontroli.
   *Rozróżnienie:* SHA nazywający PRZESZŁE ZDARZENIE („runda 5 na `b2084fc`")
   się nie starzeje — nazywa zdarzenie, nie stan bieżący.
2. **Ruchome liczby zapisuj Z DATĄ i ostrzeżeniem, że rosną.** Pinowanie
   ruchomej liczby jako stałej to ta sama pułapka, tylko odroczona.
3. **Sprostowanie oznaczaj JAWNIE** („poprzednia wersja podawała nieprawdę,
   bo X"). Ktoś mógł już przeczytać wersję fałszywą — cicha podmiana do niego
   nie dotrze.

**Audyt obejmuje PUNKTY WEJŚCIA, nie tylko pliki akurat edytowane.** Najgorszy
zmierzony przypadek (sesja `konta`): dokument-punkt-wejścia po przerwie
powtarzał wyjaśnienie blokera **obalone tego samego dnia** — następna sesja
ścigałaby nieistniejącą przyczynę, trzymając dokument brzmiący autorytatywnie.

U nas przy tym audycie: sekcja „do rozstrzygnięcia w następnej sesji" nadal
kierowała do **rundy 4**, wykonanej razem z piątą. Obalona hipoteza BLK-22
(wskrzeszenie sesji przez odświeżenie) do dokumentów nie trafiła — ale wpisałem
ją tam TERAZ, jawnie jako obaloną, żeby następna sesja jej nie odkrywała
od nowa.

**Przed użyciem dyskryminatora wypisz WSZYSTKIE światy zgodne z każdą jego
wartością.** Wartość, z którą zgodny jest więcej niż jeden świat, znaczy że
brakuje **odczytu bazowego** — to ten sam wymóg co dowód mutacji przed/po,
tylko zastosowany do wnioskowania.

Zmierzony przypadek u nas: licznik żądań do punktu tokenów miał rozstrzygnąć
między „odświeżanie wskrzesza tożsamość" (>0) a „kasuję niewłaściwy wpis" (=0).
Zero było jednak zgodne także z **trzecim światem**: ścieżka odświeżania w ogóle
się nie uruchamia (access token wciąż ważny), więc 200 pochodzi ze zwykłej,
nietkniętej sesji, a test nie mierzy tego, co deklaruje. Domknięcie: ten sam
licznik w przebiegu **kontrolnym**, bez mutacji.

**Negatywna asercja bezpieczeństwa musi być TRWAŁA.** Znacznik mówiący „ten
byt jest martwy" (unieważniona sesja, zablokowane konto, cofnięty token) ma tę
własność, że **każdy sposób, w jaki może zniknąć, jest po cichu FAIL-OPEN** —
zablokowany wraca, a objawem jest BRAK OBJAWU.

Cztery wymagania magazynu takiego znacznika:

1. **Trwałość** — przeżywa restart, deploy i `cache:clear`. Baza, nie cache.
2. **Współdzielenie** — wszystkie instancje widzą ten sam znacznik.
3. **Czas życia ≥ najdłuższemu bytowi, który unieważnia.** Dla sesji SSO to
   **SSO Session Max** realmu, nie czas życia access tokenu: refresh token żyje
   dłużej, więc znacznik wygasający przed nim wpuszcza z powrotem. Sprzątanie
   musi używać **tego samego progu** — inaczej sprzątaczka sama odblokowuje.
   Próg zapisujemy W WIERSZU, nie domyślamy z konfiguracji w chwili sprzątania.
4. **Eksmisja nie może być cicha** — w magazynie z LRU „brak znacznika" jest
   nieodróżnialny od „nigdy nie blokowany".

Zmierzone u nas: ze znacznikiem w cache'u `Cache::flush()` po wylogowaniu
dawał **200 zamiast 401** — zablokowany użytkownik wracał. Wyzwalacze
całkowicie prozaiczne: deploy, `cache:clear`, restart, eksmisja LRU.

**PRE-FLIGHT DYSKRYMINATORA — przed uruchomieniem, nie po.** Zanim uruchomisz
pomiar mający rozstrzygnąć między hipotezami, wypisz dla **każdej możliwej
wartości** pełną listę światów z nią zgodnych. Jeśli którakolwiek wartość ma
więcej niż jeden świat — **dyskryminator nie jest gotowy i go nie uruchamiasz**.

Przegląd po fakcie łapie to o rundę za późno. Trzy dowody z jednego dnia:

1. Licznik żądań do punktu tokenów: `0` było zgodne z „kasuję niewłaściwy wpis"
   ORAZ z „ścieżka odświeżania w ogóle się nie uruchamia". Odczyt bazowy
   (przebieg kontrolny bez mutacji) rozstrzygnął — i uratował mnie przed
   zmianą kodu, który działa.
2. Ten sam licznik po naprawie testu: `1` było zgodne z „wskrzeszenie" ORAZ
   z „tożsamość nieusunięta, odświeżenie normalne". Zastosowałem regułę do
   wartości `0` i **nie zastosowałem jej do `1`**.
3. `sid` jako kandydat na odczyt rozstrzygający: bezużyteczny, bo wymiana
   refresh tokenu NIE tworzy nowej sesji w IdP — `sid` jest identyczny w obu
   światach. Złapane **przed** uruchomieniem, po weryfikacji we własnym kodzie
   (`przelicz()` w ogóle go nie dotyka).

To rozszerzenie prerejestracji interpretacji: dochodzi warunek, że **każda
gałąź musi być jednoelementowa**.

**Etykieta w kodzie nie może twierdzić więcej, niż pomiar wykazał.** Moja
etykieta mówiła „świat 2 — wskrzeszenie", czego nie udowodniłem. To
dokumentacja kłamiąca o kodzie: następny czytelnik odziedziczyłby wniosek bez
pomiaru. Poprawna postać nazywa stan wiedzy — „NIEROZSTRZYGNIĘTE: światy 1 i 2".

**P25 i gałąź zdegenerowana to TA SAMA WADA** — raz po stronie czerwieni, raz
po stronie zieleni. „Perturbacja zaliczona z innej przyczyny niż badana"
i „wartość dyskryminatora zgodna z więcej niż jednym światem" to jedno
zjawisko: **wynik, którego nie da się przypisać badanemu zjawisku**.

**Odruch:** zobaczywszy wynik, pytaj **„jakie światy dają tę wartość"**, nie
„czy wynik jest taki, jak chciałem". Dotyczy zieleni tak samo jak czerwieni —
zielony test przechodzący z niewłaściwego powodu jest groźniejszy, bo nikt go
nie bada.

Dwa pomiary usunięte w ciągu godziny właśnie z tego powodu: migawka magazynu
(105 kluczy, bez izolacji sesji) i test „`Cache::flush()` nie wylogowuje"
(przechodził także po cofnięciu zmiany, bo w suicie `CACHE_STORE=array`).

**Cicha podmiana sterownika w suicie jest gorsza od braku kontroli**, bo
wygląda jak pokrycie. Przy dodawaniu kontroli pytamy: **czy sterownik, którego
zachowanie badam, jest w suicie prawdziwy?** Jeśli nie — kontrola trafia na
jawną listę „bez pokrycia" z uzasadnieniem, zamiast udawać dowód.
Przegląd sterowników: `docs/DECYZJE.md`, D-2026-08-08-27.

**Po naprawie ZMIERZ PONOWNIE — czy cokolwiek się zmieniło.** Naprawa może być
poprawna i jednocześnie **nie tłumaczyć objawu**. Pomiar kontrolny po naprawie
jest darmowy (masz już przyrząd) i jest jedynym sposobem, żeby to odróżnić.

*Zmierzona instancja (08.08):* przebudowałem pisarza tożsamości tak, że
odświeżanie NIE MOŻE już utworzyć sesji — i zmierzyłem migawki ponownie.
Liczby **identyczne** (zniknęło 1, pojawiło się 1, status 200). Skoro naprawa
tej ścieżki niczego nie zmieniła, objaw nie pochodził z tej ścieżki, a moje
wcześniejsze „POTWIERDZONY DEFEKT: wskrzeszenie" było przedwczesne. Bez pomiaru
kontrolnego zamknąłbym sprawę jako naprawioną i zostawił prawdziwą przyczynę.

**Naruszenie wąskiego gardła ma DWA kształty — sprawdź który, bo zmienia
naprawę.** Albo (a) pisarzy jest kilku, albo (b) pisarz jest jeden, ale **nie
odróżnia UTWORZENIA od AKTUALIZACJI**. W przypadku (b) asercja „zbiór piszących
= 1" przechodzi **przy otwartej dziurze** — czyli kontrola świeci zielono nad
defektem, który miała łapać.

*Zmierzona instancja (08.08):* miałem przypadek (a) — `LogowanieController`
i `OdswiezanieSesji` pisały klucz `konta` niezależnie. Naprawa: jeden pisarz,
w którym operacja aktualizująca **przyjmuje istniejący rekord jako argument**
(typ z prywatnym konstruktorem, tworzony wyłącznie z niepustego magazynu).
Ścieżka „brak rekordu → utwórz" jest wtedy NIEWYWOŁYWALNA, a nie zabroniona
warunkiem — strażnika da się ominąć, brakującej wartości nie.

**Etykieta nazywa STAN WIEDZY i cofa się, gdy wiedza się cofa.** Zmiana
etykiety w jedną stronę (nierozstrzygnięte → potwierdzone) jest łatwa; w drugą
wymaga przyznania, że wcześniejszy wniosek był przedwczesny — i właśnie dlatego
bywa pomijana. Etykieta, która została „potwierdzona" po obaleniu wniosku, jest
dokumentacją kłamiącą o kodzie, tylko trudniejszą do wykrycia niż zwykły
nieaktualny komentarz, bo brzmi jak wynik pracy.

*Zmierzona instancja (08.08):* cofnąłem etykietę testu nogi 1
z „POTWIERDZONY DEFEKT: wskrzeszenie" z powrotem na „NIEROZSTRZYGNIĘTE"
po tym, jak pomiar kontrolny podważył moją diagnozę.

**Nasze instancje** (rosną — dopisuj):

- **Plik stanu, od którego zaczyna następna sesja, kłamał.** `PLAN-FAZ.md`
  deklarował `HEAD: 1204daa` na `main`, 151 testów i 21 kroków bramki; faktycznie
  `main` był na `a5a4d8b`, gałąź robocza na `1106b34`, testów 178, kroków 22.
  Dokument stanu jest przyrządem: następna sesja startuje z jego treści, więc
  jego nieaktualność propaguje się na wszystkie decyzje tej sesji. Aktualizacja
  należy do zamknięcia partii, nie do „kiedyś".
- **CI nie podążyło za dyscypliną gałęzi.** Wyzwalacz miał `branches: ["main"]`,
  a cała praca żyła na gałęziach roboczych — więc naprawy rundy 5 i BLK-22 nie
  miały ANI JEDNEGO przebiegu CI. Znalazł to architekt własnym pomiarem.
  CI jest **jedynym** miejscem, w którym „czysty klon" jest wymuszony
  MASZYNOWO, a nie deklaratywnie — a właśnie na maszynie wykonawcy powstało
  V-2. Wyzwalacz rozszerzony na `**`.

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

### Lekcje nocy 08/09.08 — przyrząd, który przestał świecić

**Dowód mutacji w formie „starego tekstu już nie ma" MA GAŁĄŹ ZDEGENEROWANĄ.**
Wartość „prawda" jest zgodna z dwoma światami: (I) mutacja weszła i usunęła
tekst, (II) tekstu NIGDY TAM NIE BYŁO, bo kod przemianowano. Poprawna postać
pyta **„czy tekst BYŁ, a potem ZNIKNĄŁ"** — odczyt bazowy bierze z kopii sprzed
mutacji, którą i tak robi `zachowaj`. Wtedy rozjazd perturbacji z kodem melduje
się sam, i to z właściwą przyczyną („PERTURBACJA ROZJECHAŁA SIĘ Z KODEM"),
zamiast udawać sukces.

*Zmierzona instancja (09.08, noc):* przemianowanie zmiennej `$konta` →
`$tozsamosc` w kodzie produkcyjnym (własny commit z tego samego wieczora)
uczyniło DWIE perturbacje bezczynnymi. Zawiodły trzy zabezpieczenia po kolei:
`perturbuj()` nie sprawdzał kodu wyjścia (skrypt bez `set -e`), dowód mutacji
był negacją nieistniejącego wzorca, a oczekiwana czerwień i tak przyszła —
z niepowiązanego, trwale czerwonego testu w tym samym pliku. Znalezione
niezależnie trzy razy: przeze mnie, przez weryfikatora A (uruchomieniem każdego
ogniwa) i przez weryfikatora B (porównaniem wzorców z kodem).

**Refaktor kodu produkcyjnego UNIEWAŻNIA perturbacje, które ten kod cytują.**
Perturbacja podmienia tekst, więc każda zmiana nazw jest dla niej zmianą
zrywającą — cichą, bo nikt nie uruchamia perturbacji przy zmianie nazwy
zmiennej. Po każdym refaktorze w obszarze objętym perturbacjami: uruchom je
albo przynajmniej sprawdź, że wzorce nadal trafiają.

**Perturbacja celująca w plik, który jest JUŻ czerwony z innego powodu, nie może
paść.** `oczekuj_czerwone` bez zawężenia przyjmie tamtą czerwień i zaliczy
scenariusz, choćby mutacja nie zmieniła nic. Gdy w pliku siedzi znany czerwony,
**każde** wymierzone w niego `oczekuj_czerwone` musi mieć `--przyczyna`.
A `--przyczyna` ma być **KOMUNIKATEM ASERCJI**, nie nazwą testu, nazwą klasy
ani wartością `--filter` — te trzy Pest wypisuje w KAŻDYM przebiegu, także
zielonym, więc jako allowlisty nie zawężają niczego.

**Zbieżność liczb jest tropem tylko wtedy, gdy liczba jest RZADKA.**
Błąd częstości bazowej. Zgodność „co do sekundy" dwóch wartości, z których każda
znaczy „jedna doba", nie niesie prawie żadnej informacji — 86400 trzyma połowa
komponentów w stosie. Przy wartości 73 412 s byłby to mocny trop.

*Zmierzona instancja (09.08):* zgłosiłem podejrzenie, że klucz rejestru sesji
trafia do złej bazy Redisa, bo jego TTL wynosił 86400 s — tyle, co
`RejestrSesji::CZAS_ZYCIA_SEKUND`. Rozstrzygała **NAZWA** klucza, nie TTL:
odczyt nazw (wolno je czytać, wartości nie) pokazał w tej bazie wyłącznie klucze
Horizona i wątek zniknął w minutę. Szukałem potwierdzenia w wielkości, którą
dzieli pół świata, zamiast w identyfikatorze, który identyfikuje.

**Kod wyjścia potoku należy do OSTATNIEGO polecenia.** `komenda | tail` zwraca
kod `tail`, czyli prawie zawsze zero. Dwa razy jednej nocy „exit code 0" znaczyło
u mnie „pomiar w ogóle się nie wykonał" — raz przez nieistniejącą flagę
(`docker exec -T`, gdzie `-T` należy do `docker compose exec`), raz przez `tail`.
Wniosek czytaj z TREŚCI wyjścia, nie z kodu; przy potokach `set -o pipefail`.

**Naprawa jednej kontroli potrafi POWIĘKSZYĆ lukę w drugiej.** Podniesienie
podłogi liczby testów w bramce było słuszne — i tym samym powiększyło rozjazd
wobec perturbacji, które dowodzą podłogi o innej, niższej wartości. Raport
z naprawy, który przemilcza jej skutek uboczny, jest raportem nieprawdziwym.

**Dokumentacja potrafi zapalić bramkę.** Raport z weryfikacji to tekst
o wysokiej entropii — identyfikatory sesji, skróty, nazwy plików — a skaner
sekretów nie odróżnia go od klucza API. U nas heurystyka `generic-api-key`
uznała za sekret **nazwę pliku** we własnym dzienniku. Wyjątek w skanerze wolno
dodać tylko wąsko (jedna reguła, jedna ścieżka) i **z dowodem, że nie oślepił
kontroli** — przynęta w tym samym katalogu musi go nadal zapalać.

**Podmiana „od kotwicy do kotwicy" w dokumencie wielosekcyjnym ma zasięg
większy, niż wygląda.** Zamieniając jedną sekcję na nową, wskazałem zakres
„od jej nagłówka do następnego znanego nagłówka" i skasowałem po drodze trzy
tabele zadań. Wyszło natychmiast wyłącznie dlatego, że skrypt wypisywał liczbę
usuwanych znaków, a usuwaną treść zapisywał do pliku przed nadpisaniem.
**Zapisuj to, co nadpisujesz** — kosztuje jedną linijkę.

## RDZEŃ WSPÓLNY PRZYWRÓCONY 09.08.2026 — cztery reguły, których u nas NIE BYŁO

> **Skąd to jest.** Weryfikator architekta zmierzył `grep`-em obecność reguł rdzenia w czterech
> repozytoriach i **obalił twierdzenie**, że rdzeń jest identyczny
> (`_architektura/weryfikacja-architekta/2026-08-09-kryteria-faz.md`, sekcja 5).
> U nas brakowało **czterech** reguł. Poniższe **treści rdzenia skopiowałem dosłownie
> z `hub/WYTYCZNE-PRACY.md`, wersja z 09.08.2026** — nie z opisu architekta, bo jego parafrazy
> raz już przekręciły wzorzec w sposób unieważniający jego sens. Do każdej reguły dołożyłem
> **instancję zmierzoną u NAS**, bo reguła bez własnego numeru wpadki czyta się jak ogólna
> ostrożność i pierwsza wylatuje przy porządkowaniu.
>
> **Żadnej z tych czterech nie oznaczam jako „nie stosuje się u nas".** Wszystkie cztery mają
> u nas zmierzone instancje — trzy z nich z tej samej doby.

### Kontrole bezpieczeństwa: allowlisty, nie denylisty — sekcja wspólna ekosystemu

**ZASADA: „wylicz zakazane" zawsze przegra z wariantem spoza listy.** Denylista broni tylko
tego, co jej autor zdążył sobie wyobrazić. Właściwa postać kontroli bezpieczeństwa to
**zakazane domyślnie, jawna zgoda na każdy wyjątek w rejestrze** (deny by default).

Precedens ekosystemowy: zakaz własnych haseł w Gabinecie przegrał **trzykrotnie tak samo** —
literalne nazwy → wzorce → zakres. Każda z tych trzech napraw była inną denylistą, więc każda
przegrała z wariantem, którego nie objęła.

**Praktycznie.** Kontrola ma odpowiadać na pytanie „czy wszystko, co tu jest, zostało
**dopuszczone**?", a nie „czy jest tu coś **zakazanego**?". Zbiór dopuszczony trzymamy jawnie
w kodzie kontroli; dopisanie do niego wymaga świadomej decyzji, czyli zostawia ślad
w przeglądzie i w rejestrze.

> **Nasza instancja — i to jest wstyd, że reguły tu nie było: przegraliśmy CZWARTY raz.**
> `BrakWlasnychHaselTest` opiera się na wyrażeniu `PRYMITYWY_POSWIADCZEN`, które **wylicza
> zakazane** prymitywy. Weryfikator rundy 6 zbudował kompletny mechanizm własnych haseł na
> `hash('sha256', …)` — prymitywu spoza listy — i **cała kontrola CLAUDE.md §2 przeszła:
> `7 passed`** (znalezisko **R6A-4**). Na liście nie ma też `hash_hmac`, `md5`, `sha1`,
> `openssl_*`.
>
> **Ciężar dokłada zdanie stojące w środku tej kontroli:** *„Lista jest ZAMKNIĘTA — nie da się
> zweryfikować hasła bez jednego z nich (albo bez własnej kryptografii, co samo w sobie byłoby
> czerwoną flagą przy przeglądzie)"*. Zdanie jest nieprawdziwe, **samo przewiduje dziurę**
> i oddaje ją człowiekowi — czyli w miejscu, gdzie kontrola miała zastąpić regułę mechanizmem,
> wraca reguła. Weryfikator krzyżowy Kont nazwał to cięższym niż sama luka: kontrola zawiera
> **pisemne zapewnienie, że dziury nie ma**.
>
> To repozytorium jest w ekosystemie **dowodem** tej reguły i było jedynym, które jej nie nosiło.

### Kiedy wolno napisać „zamknięte" — sekcja wspólna ekosystemu

Dla znaleziska **podatnego na nawrót** samo naprawienie instancji nie wystarcza.
„Zamknięte" wymaga dwóch rzeczy:

1. **perturbacji rozpinającej KLASĘ**, nie jedną instancję — mutacja ma sięgać po wariant,
   którego weryfikator **nie** pokazał;
2. **przetrwania jednej pełnej rundy weryfikacji bez dotykania** naprawy.

Naprawa dokładnie tej instancji, którą pokazał weryfikator, opisana jako „klasa zamknięta",
to schemat, przez który w Gabinecie **nawróciły trzy naprawy naraz**.

> **Nasze instancje, dwie z jednej doby.**
> (a) **R6A-3** — napisałem o wąskim gardle §2, że ścieżka „brak rekordu → utwórz" jest
> **NIEWYWOŁYWALNA**. Weryfikator wytworzył tożsamość koordynatora trzema wektorami
> (dane z żądania, `Reflection`, `unserialize`). Zamknąłem pokazany przypadek; warunek
> przeniósł się o poziom wyżej.
> (b) **N-3** — naprawiając perturbacje dołożyłem dowód z odczytem bazowym i przeoczyłem
> **osiem podmian robionych surowym `sed`-em**, które nie mają żadnego zabezpieczenia.
> Zamknąłem klasę w jednym mechanizmie z dwóch.
>
> **Uwaga o cudzym odwołaniu:** `helpdesk/PLAN-FAZ.md` uzasadnia obniżenie statusu F1
> „kryterium z rundy 4 Gabinetu (`WYTYCZNE-PRACY.md`, «Kiedy wolno napisać zamknięte»)" —
> a sekcji o tej nazwie **u nas nie było**, więc odwołanie prowadziło donikąd (sekcja 5c
> raportu). Od tego wpisu prowadzi tutaj.

### Środowisko jest częścią pomiaru — sekcja wspólna ekosystemu

**ŚRODOWISKO JEST CZĘŚCIĄ POMIARU.** Bramka i perturbacje muszą używać środowiska
**zdefiniowanego w repo** (generowanego z `.env.example`), nigdy `.env` zastanego na maszynie
dewelopera. Inaczej zielone istnieje wyłącznie w jednym środowisku — tym, w którym akurat
mierzono.

Precedens ekosystemowy: „42 perturbacje OK" zmierzone w jedynym środowisku, w którym były
zielone; na czystym klonie **czerwone**, a pod spodem wyciek danych osobowych. Brakowało
jednej linii w repo.

**NAPRAWIAJ INTEGRALNOŚĆ POMIARU PRZED DEFEKTAMI.** Gdy przyrząd — bramka, perturbacja,
skrypt powtarzalności — kłamie o wynikach, naprawianie defektów jest zgadywaniem. Najpierw
prawdą ma być to, co runner mówi o zielonym i czerwonym; **dopiero potem** treść tego,
co mierzy.

> **Nasze instancje — i jedna z nich ROZSZERZA tę regułę o wymiar, którego nie miała.**
> (a) **R6B-16, OTWARTE:** `skrypty/perturbacje.sh` nie podaje `--env-file`, więc
> `docker-compose.yml` montuje **`.env` dewelopera z prawdziwymi sekretami**. Bramkę
> naprawiliśmy (własny plik z `.env.example` przy każdym przebiegu), perturbacji **nie** —
> czyli reguła obowiązuje u nas w połowie narzędzi.
> (b) **N-14 — nowy wymiar: środowiskiem jest też UŻYTKOWNIK PROCESU.** Zmierzone: testy
> biegną przez `docker compose exec` jako **root**, a żądania obsługuje **`www-data`**.
> Katalog `storage/slad-wylogowania` należy do roota, więc w prawdziwym procesie zapis śladu
> **cicho nie dochodzi** (`File::put` ostrzega i zwraca, nie rzuca), a odczyt **udaje się**
> i oddaje nieświeżą liczbę z innego procesu. Kontrola jest walidowana w kontekście
> użytkownika, który w produkcji nie występuje.
>
> **Wniosek do reguły:** przy dodawaniu kontroli pytamy nie tylko „czy sterownik jest
> prawdziwy" i „czy plik środowiska pochodzi z repo", ale też **„czy kontrola biegnie jako
> ten sam użytkownik, co proces obsługujący żądanie"**.

### Suma zielonych nie jest dowodem — sekcja wspólna ekosystemu

**SUMA ASERCJI NIE JEST DOWODEM. DOWODEM JEST ROZBICIE.** „80/80 zielonych" brzmi jak fakt
o systemie, a bywa faktem o maszynie. Gdy liczba kontroli zależy od otoczenia (ile portów
opublikowano, ile adresów ma host, ile plików znalazł skaner), porównanie samych sum między
przebiegami nie dowodzi **niczego** — ani że kontroli przybyło, ani że ubyło. Raport ma
podawać sumę **razem z rozbiciem**.

**STAN WYJŚCIOWY SPRZĄTA SIĘ PRZED POMIAREM.** Bramka usuwa pozostałości własnego projektu,
zanim cokolwiek zmierzy. Inaczej mierzy kontener, którego sama nie postawiła — i nie wie o tym.

Reguła jest silniejsza niż wygląda: **liczba, która rośnie, uspokaja.** Dlatego wzrost sumy
asercji wymaga takiego samego wyjaśnienia co spadek.

> **Nasze instancje, obie z tej samej doby i obie o liczbie, która uspokajała.**
> (a) **N-2:** podłogi bramki stały na 170/590 przy stanie 181/640. W zapasie jedenastu testów
> mieściło się **skasowanie w całości dziesięciu z siedemnastu plików kontrolnych**, w tym
> `ObietniceKomentarzyTest` — kontroli NAD kontrolami. Suma wyglądała dobrze przez cały czas.
> (b) **N-12 / R6B-13:** „30 scenariuszy perturbacji" cytowaliśmy jako miarę pokrycia.
> Zmierzone: **pięć** z nich nie może dziś zaświecić czerwono, bo celują w plik trwale czerwony
> z innego powodu, a sześć z ośmiu allowlist `--przyczyna` nie zawęża niczego. Liczba 30 jest
> prawdziwa i **nie jest** miarą pokrycia — dlatego `CURRENT WORK` niesie dziś zdanie
> „nie cytuj «30 scenariuszy» jako miary pokrycia".

## Czego agentom nie wolno nigdy

Wdrażać na produkcję bez zgody właściciela · zapisywać sekretów · wyłączać/obchodzić bramek · relitygować decyzji z `CLAUDE.md` i `docs/DECYZJE.md` · pracować dalej mimo niezrozumienia wymagania (wtedy: pytanie do właściciela w raporcie, praca na innym froncie).
