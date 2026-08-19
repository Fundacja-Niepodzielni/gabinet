# Plan faz — Gabinet

Fazy wykonywane po kolei; bramka fazy musi być zielona i **niezależnie zweryfikowana** przed wejściem w następną. Mapowanie na moduły specyfikacji w nawiasach. Rozmiary: S (dni), M (1–2 tyg.), L (kilka tyg.).

## CURRENT WORK

> (aktualizuj na koniec każdej sesji: bieżąca faza, zadania w toku, blokery, następny krok)

- **Faza: F1 w toku.** F0 i F1 formalnie OTWARTE do rundy z zerem znalezisk.
  **Fazy nie zamyka sesja kodująca — zamyka ją runda.**
- **Gałąź robocza: `faza-1-retencja`** (D-2026-08-08-23: merge do `main` po zielonej rundzie).
  Bez SHA — stan czytaj z `git log`.
- **Bramka: ZIELONA — `BRAMKA OK — 22 kroków, 0 nieudanych`, kod wyjścia 0.**
  Przebieg OD ZERA we własnym środowisku efemerycznym
  (`.env.bramka.gabinet-bramka` budowany z `.env.example` przy każdym przebiegu).
- **Testy: 324 zielonych, 0 czerwonych, 2 POMINIĘTE, 2272 asercji.**
  Podłogi bramki: **324 / 2272** (`skrypty/podlogi.sh` — JEDNO źródło dla bramki
  i perturbacji; liczby tutaj są ODCZYTEM z tego pliku, nie osobnym zapisem).
- **Perturbacje: `PERTURBACJE OK — 66 kontroli`, kod wyjścia 0.**
  PEŁNY zestaw 49 scenariuszy na stosie `gabinet-perturbacje`, zero pominiętych.
  ⚠ Liczba SCENARIUSZY opisuje `skrypty/perturbacje.sh` (stan bieżący, pilnuje
  `JednoZrodloStanuTest`); liczba KONTROLI to wynik przebiegu i wymaga kotwicy.

> ⛔ **KONWENCJA KOTWIC — wchodzi 18.08 po znalezisku R9-5.**
>
> Liczby POMIARU zapisujemy w postaci „**zmierzone na `<SHA>`**", nigdy
> „zmierzone `<data>`". Powód jest sprawdzalnościowy, nie estetyczny: daty
> nie da się zweryfikować z wnętrza repozytorium bez wpuszczenia zegara do
> kontroli, a kontrola zależna od zegara zaczyna padać sama z siebie. SHA
> weryfikuje się jednym `git cat-file -e`.
>
> Ta sekcja trzykrotnie kłamała o własnym pomiarze — R7-6 (trzy twierdzenia
> nieprawdziwe), 18.08 („zmierzone 12.08" o pomiarze z 18.08) i R9-5
> („290/2130 na `179c05c`", gdzie jest 289/2119). Za każdym razem wychodziło
> to dopiero w rundzie.

**Pomiar Z KOTWICĄ:** wpisany po commicie zamrażającym rundy 11 — patrz
`docs/ZLECENIA/ODPOWIEDZ-078.md`. Do czasu tego commitu obowiązuje pomiar
poprzedni.

Poprzednie: 304 / 2211, 54 kontrole (37 scenariuszy) — **zmierzone na `bbc8167`**;
301 / 2170, 52 kontrole (35 scenariuszy) — **zmierzone na `528adc3`**;
290 / 2130, 49 kontroli — **zmierzone na `d79dc0c`**.

Poprzedni: 290 / 2130, 49 kontroli — **zmierzone na `d79dc0c`**.

### Co zamknęła sesja KOD-F1 dnia 12.08

**Wszystkie 29 znalezisk rundy 6** plus N-14 z tego samego rejestru. Każde z dowodem
pomiarowym; naprawy pogrupowane wg klas z `KLASY-I-NAPRAWY.md`:

- **klasa 1** (kontrola bez środowiska): R6B-14 mierzy atomowość na PRAWDZIWYM
  Redisie; N-14 biegnie jako `www-data` (`pcntl_fork` + `posix_setuid`).
- **klasa 2** (dowód na nieobecności napisu): osiem podmian `sed` przeniesionych pod
  `podmien_jedyne()`, które podnosi błąd przy ZERZE i przy ZWIELOKROTNIENIU trafień;
  R6A-6 przez parser (`Tests\Wsparcie\Zrodlo`), nie wyrażenie regularne.
- **klasa 3** (wynik zgodny z wieloma światami): odczyty bazowe i tabele światów
  w nodze 1, teście POZYTYWNYM i teście odmowy IdP; allowlisty `--przyczyna` jako
  KOMUNIKATY ASERCJI (dług 7 → **2**, sufit zapadki zjechany).
- **klasa 4** (zapis o nierozstrzygniętym losie): mapa `sid → sesje` w bazie
  (`sesje_sso`) + drugi sygnał `znanychPrzyOstatnimZakonczeniu()`; ślad wylogowania
  oddaje `null` zamiast nieświeżej liczby.
- **klasa 5** (denylista): **R6A-4** — zbiór zakazany pochodzi z `get_defined_functions()`,
  a allowlista wiąże funkcję z ZAKRESEM PLIKÓW. Perturbacja odtwarzająca dokładnie atak
  weryfikatora (`hash('sha256', $haslo)`) zapala kontrolę; stara dawała `7 passed`.
- **klasa 6** (twierdzenie bez egzekutora): zasięg `ObietniceKomentarzyTest` rozszerzony
  o siedem pominiętych rodzin znaczników; `JednoZrodloStanuTest` pilnuje JEDNEJ sekcji
  `CURRENT WORK` i zakazuje liczb stanu bez kotwicy w przeszłym zdarzeniu.

### ⚠ CZEGO SESJA NIE ZROBIŁA — czytaj przed resztą

- **KLASA 7 — ZAMKNIĘTA, nie otwarta.** Strażnik `skrypty/git-hooks/pre-commit`
  STOI (commit `cc70946`, 188 wierszy) wraz z `StraznikCommitaTest`. Odmawia
  commita w trakcie przebiegu pomiarowego, w cudzym repozytorium i przy plikach
  spoza zakresu sesji; działa we wszystkich worktree (naprawa O-6b, 12.08).
  ⚠ Do 12.08 stało w tym miejscu „KLASA 7 NIETKNIĘTA … NIE POWSTAŁ" — zdanie
  nieprawdziwe już w chwili zapisu (R7-6, znalezisko rundy 7). Sekcja stanu
  wysyłała następną sesję do zbudowania czegoś, co stało obok.
  Powód, dla którego strażnik powstał, zostaje zapisany: ta klasa złamała się
  w jednej sesji **pięć razy** mimo pełnej świadomości reguły — komunikat commita
  przez `-m` z odwrotnymi apostrofami (dwukrotnie), `git add` wciągający pliki
  dwóch innych sesji, `git checkout --` zamiast `cp`, perturbacja zostawiona
  w drzewie przez niepełne zachowanie plików.
- **Allowlisty `--przyczyna`: dług SPŁACONY do zera** (12.08, sufit zapadki 0).
  ⚠ Do 12.08 stało tu „dług 2 … blokuje brak komunikatów asercji w dwóch testach".
  Obie liczby były nieprawdziwe i przyczyna też (R7-8): zapadka nie widziała nazw
  KLAS ani gałęzi alternatywy ERE, więc liczyła 2 z 5. Blokerem nie był wyłącznie
  brak komunikatów — dla `:1208` blokadą była wada `zdekodowaneLadunki()` (R7-4),
  przez którą właściwa asercja nie zapalała się wcale; wzorzec nie miał w co celować.
- **`TwierdzeniaKomentarzyTest` nadal zdjęty z bramki** (kontrola D3, 14 obejść na 15).
- **Odczyt DYNAMICZNY wzorców `--przyczyna` — WYKONANY 12.08** na stosie perturbacyjnym
  (`skrypty/odczyt-przyczyn.py`, po naprawie izolacji z R7-7). Wynik: 14 wywołań
  ZGODNE-ROZROZNIA, zero rozbieżności między odczytem statycznym a dynamicznym,
  jedno NIEROZSTRZYGNIETE z konstrukcji (mutacja siedzi w samym poleceniu).
- **Wyjątek w `.gitleaks.toml` z warunkiem znoszącym**: przy scalaniu F1 do `main`
  historię zakresu należy przepisać i wpis USUNĄĆ. Kopia: `kopia-przed-filtrem-12-08`.

### ⚠ SPROSTOWANIA — poprzednie wersje tego pliku podawały nieprawdę

1. **„Bramka CZERWONA — 1 nieudany krok z 22" było NIEAKTUALNE.** Pomiar 12.08:
   czerwone były DWA kroki — `[19] testy` (noga 1) ORAZ `[20] statyka`
   (**34 błędy Larastana** w plikach powstałych 09.08). Liczba „1" pochodziła z rundy 6
   i była starsza niż cała doba pracy z 09.08. Instancja reguły „trzy zielone narzędzia
   to nie zielona bramka": doba napraw nie puściła pełnej bramki ani razu.
2. **„Noga 1 ma zostać CZERWONA do naprawy z rundą" — wykonane.** Naprawa jest w TEŚCIE:
   granica procesu odtwarzana w pełni (`StartSession` też jest singletonem) plus jawne
   ciasteczko sesji. Falsyfikowalność zmierzona trzema perturbacjami, każda czerwona
   z innej i właściwej przyczyny.
3. **„PERTURBACJE CZERWONE — 1, oczekiwana" — nieaktualne.** Ta czerwień zniknęła sama
   po naprawie nogi 1, dokładnie jak przewidywał R6B-13.

### Do rozstrzygnięcia w następnej sesji

**Runda 7 — DO WYKONANIA.** Meldunek gotowości: `docs/ZLECENIA/ZLECENIE-048.md`.
Historia rund (każda nazywa PRZESZŁE ZDARZENIE, więc się nie starzeje):
runda 3 na `a660753` — 11 znalezisk, runda 4 na `1417ad8` — 15,
runda 5 na `b2084fc` — 12, **runda 6 na `49131d8` — 29 (wszystkie zamknięte 12.08)**.

> **SPROSTOWANIE (09.08, noc).** Ta sekcja kierowała sesję do „ODCZYTU
> ROZSTRZYGAJĄCEGO dla nogi 1", pisząc „przyczyna NIE JEST znana".
> **Przyczyna została ustalona w nocy 08/09.08 pomiarem dwóch niezależnych
> weryfikatorów** — patrz `CURRENT WORK` wyżej i `docs/noc-2026-08-08/`.
> Zostawiam ślad po starej treści, bo ktoś mógł ją przeczytać, a cicha podmiana
> do niego nie dotrze. Wcześniejsze sprostowanie (08.08) dotyczyło rund 4 i 5.

**Runda 6 WYKONANA** w nocy 08/09.08 na SHA `49131d8`, dwoma niezależnymi
weryfikatorami, na osobnych czystych klonach i w pełni izolowanych projektach.
**Wynik: 29 znalezisk — runda NIE jest zerowa, więc F1 i F0 pozostają OTWARTE**
(reguła zbieżności D-2026-08-07-16).

Raporty w całości: `docs/noc-2026-08-08/RUNDA-6-A-RAPORT.md` (bramka na żywym
stosie, 12 znalezisk) i `RUNDA-6-B-RAPORT.md` (analiza dyskryminatorów
i sterowników, 17 znalezisk). Streszczenie z wagami: `ZNALEZISKA.md`.

**Stan tych 29 znalezisk czytaj z `CURRENT WORK` na górze pliku** — wszystkie
zostały zamknięte 12.08. Historii rund NIE POWTARZAM tutaj drugi raz: dwa opisy
jednej rzeczy rozjeżdżają się po cichu (klasa P3), a to jest dokładnie ten plik,
w którym już raz się rozjechały (R6A-9).

### PIERWSZE ZADANIA NASTĘPNEJ SESJI

> **SPROSTOWANIE 12.08 — stała tu lista DZIEWIĘCIU zadań, dziś nieprawdziwa.**
> Kierowała następną sesję do: przeniesienia ośmiu podmian `sed`, naprawy
> allowlist `--przyczyna`, naprawy nogi 1, domknięcia §2, `RejestrSesji`
> w cache'u, podpięcia retencji i `.env` perturbacji. **Wszystkie te pozycje
> zostały wykonane 12.08** — lista wysyłałaby na front, którego już nie ma.
> Zostawiam ślad po starej treści, bo ktoś mógł ją przeczytać, a cicha
> podmiana do niego nie dotrze. Pełna treść: `git show` tego commita.

**Następny krok to RUNDA 7 — niezależna weryfikacja, nie kolejna partia napraw.**
Stan wejściowy dla niej: sekcja `CURRENT WORK` na górze tego pliku
oraz meldunek `docs/ZLECENIA/ZLECENIE-048.md` (co zamknięte, czego NIE zrobiono,
co zostaje otwarte i z jakim warunkiem znoszącym).

**Czego NIE robić:** nie ścigaj hipotezy „odświeżanie wskrzesza tożsamość" —
obalona trzykrotnie, ostatnio 12.08 pomiarem niezależnego weryfikatora, który
pokazał przy okazji, że wartość `(200, licznik +1)` ma DWA światy, dopóki
granica procesu jest odtwarzana połowicznie.

---

### Rozpiska zadań F0 — stan końcowy sesji

| # | Zadanie | Stan | Dowód |
|---|---|---|---|
| F0.1 | Szkielet Laravel 13.24 (PHP 8.4.24) w `backend/`, wersje przypięte digestem + `config.platform` | ✅ | `composer install` z lockfile'a; bramka krok 4 |
| F0.2 | Docker Compose: postgres 18.4, redis 8, php-fpm, nginx, Horizon, scheduler | ✅ | `up -d --wait` → 6 kontenerów `healthy`; sondy pytają o STAN (`gabinet:zdrowie`, `gabinet:puls`) |
| F0.3 | Pest 5 + Larastan `max` + Pint | ✅ | 66 testów / 192 asercje; `[OK] No errors`; 47 plików PASS |
| F0.4 | `skrypty/bramka.sh` + CI wołające ten sam skrypt + gitleaks | ✅ lokalnie / ⚠️ CI nieuruchomione | `BRAMKA OK — 18 kroków` na czystym klonie — **przebieg z 07.08, ówczesna liczba kroków**; dziś jest ich więcej, a stan czytaj z `CURRENT WORK` albo z pomiaru. Gitleaks: tryb git czysty, przynęta zapala skan |
| F0.5 | `.env.example` bez wartości (Keycloak, 2× Stripe, SMSAPI, poczta, wideo) | ✅ | `SekretyTest`; gitleaks |
| F0.6 | `docs/DECYZJE.md` | ✅ | 8 wpisów + rejestr zadań dla człowieka |
| F0.7 | Porównanie Jitsi vs Whereby + rekomendacja | ✅ | `docs/analizy/wideo-jitsi-vs-whereby.md` |
| F0.8 | Warstwa OIDC wg wzorca `ref-laravel` + sonda na żywym IdP | 🟡 częściowo | sonda OK na żywym Keycloaku; **pełne logowanie zablokowane: BLK-01** |
| F0.9 | Niezależna weryfikacja | ✅ wykonana, usterki naprawione | obaliła 5 twierdzeń (T1, T2, T3, T9, T12); naprawy w commitach `5045066`…`a3e7166`. **Powtórna weryfikacja niezależna: DO ZROBIENIA** w kolejnej sesji |
| F0.10 | Przypomnienie o nadawcy SMS | ✅ | `docs/DECYZJE.md`, Z-01 |

### Blokery i oczekiwania

- **BLK-01** (`docs/BLOKERY.md`): klient `gabinet` nie istnieje w realmie Keycloaka. Nie blokuje F1 ani F2. Gotowe zgłoszenie: `docs/zgloszenia/klient-gabinet-w-realmie.md`.
- **CI: `bramka` ZIELONA** (`BRAMKA OK — 17 kroków, 0 nieudanych` — przebieg dla `eadf5c5`, czyli ówczesna liczba kroków). Job `sekrety` był czerwony z powodu braku płatnej licencji `gitleaks-action` — **usunięty**, skan sekretów jest krokiem bramki (D-2026-08-07-12).
- Czeka na człowieka: Z-01 (nadawca SMS), Z-02 (dostawca wideo), Z-04 (przelewy vs Connect), Z-05 (źródła makiety).
- Z-03 (limit niskopłatnych) **przestał blokować** — wartość rozstrzygnięta: 10 wizyt (D-2026-08-07-08).

### Następny krok

> Ta lista dotyczy **domknięcia F0** i jest starsza niż `CURRENT WORK` na górze.
> Kolejność pracy bierz z sekcji „PIERWSZE ZADANIA NASTĘPNEJ SESJI", nie stąd.

1. ✅ Push + CI zielone (`ee85c83`).
2. ⏳ Powtórna niezależna weryfikacja — **to ona domyka F0**. Wykonano rundy 3–6;
   **żadna nie skończyła się zerem znalezisk**, więc F0 nadal nie jest domknięte
   (runda 6 na `49131d8`: 29 znalezisk).
3. Zgłoszenie klienta `gabinet` w repo `konta` (właściciel / sesja `konta`) → domknięcie BLK-01 i F0.8.

---

## F1 — rozpiska zadań fazy (to NIE jest sekcja stanu)

> Nagłówek brzmiał wcześniej „CURRENT WORK — F1", więc w pliku były **DWIE**
> sekcje `CURRENT WORK` (linie 5 i 128) o sprzecznej treści. `CLAUDE.md`
> wskazuje „sekcję `CURRENT WORK`" w liczbie pojedynczej jako stan między
> sesjami — a przy dwóch sekcjach o tej samej nazwie nie da się powiedzieć,
> która to. Zmierzone przez weryfikatora rundy 6 (R6A-9).
>
> **Stan bieżący jest wyłącznie w sekcji `CURRENT WORK` na górze pliku.**
> Poniżej są zadania fazy i kryteria „zrobione" — rzeczy, które się nie
> starzeją z każdym przebiegiem.

Materiał wejściowy: [`docs/rodo/DPIA-checklista.md`](docs/rodo/DPIA-checklista.md) (wymagania W-1…W-12),
`docs/specyfikacja/05-DECYZJE-makiety.md` rozdz. 3 (macierz odwołań) i 4 (szkic modelu danych).

| # | Zadanie | Kryterium „zrobione" | Stan |
|---|---|---|---|
| F1.1 | **DPIA-checklista** — art. 9, retencje, dostępy, podprocesorzy | dokument kończy się wymaganiami dla migracji, nie opisem | ✅ |
| F1.3 | Konfiguracja reguł **z wersjonowaniem i datą obowiązywania** + macierz odwołań **jako dane** | zmiana reguły nie działa wstecz — test na rezerwacji sprzed zmiany | ✅ |
| F1.4 | **Jedna funkcja rozstrzygająca** zwrot / możliwość przełożenia / płatność godziny | czysta funkcja, bez bazy i bez „teraz"; 8 sytuacji macierzy jako dane | ✅ |
| F1.6 | Strefy czasowe: UTC w bazie, okna liczone w Europe/Warsaw | doby **23 h i 25 h** + ten sam werdykt w 4 strefach | ✅ |
| F1.2 | Migracje domenowe: `pacjent`, `specjalista` (klucz `sub`), `usluga` (flaga fundacja/komercja, prowizja per usługa), `rezerwacja`, `zgoda`, `zdarzenie` | migracje w górę **i w dół** (krok bramki); `timestamptz`; kolumny 🔒 szyfrowane sprawdzane na SUROWYM wierszu | ✅ |
| F1.5 | Zamrażanie w rezerwacji: `kwota_zamrozona`, `regula_anulacji_zamrozona`, wersja regulaminu, konto Stripe i prowizja | test: podwyżka cennika i zmiana reguł **nie ruszają** starej rezerwacji; zrzut przeżywa skasowanie konfiguracji | ✅ |
| F1.7 | Seed o **wiarygodnych proporcjach**: 111 specjalistów, kilkanaście wizyt/pacjenta | limit **różnicuje** pacjentów; wizyt na pacjenta < 40 (dziennik makiety, rozdz. 15) | ⬜ |
| F1.8 | **Dług O-2/O-4/O-5** z weryfikacji rundy 2 (timeout bazy, rozjazd lockfile, zamek bramki) | każdy z perturbacją dowodzącą czerwieni | ✅ |

**Bramka F1:** testy tabelaryczne reguł na wartościach granicznych (**23:59 / 24:00 / 24:01**);
seed o wiarygodnych proporcjach; migracje w górę i w dół.

**Stanu bramki NIE MA już w tej sekcji.** Stała tu wcześniej linia
„`BRAMKA OK — 21 kroków, 0 nieudanych`; 151 testów (479 asercji)" oraz
„44 kontrole w 20 scenariuszach" — **nieprawdziwe od kilku dni i sprzeczne
z sekcją `CURRENT WORK` na górze pliku**, która mówiła „CZERWONA". Weryfikator
rundy 6 zmierzył to jako R6A-9: sesja czytająca akurat tę sekcję startowała
z fałszywego „BRAMKA OK".

Stan bramki, liczby testów i perturbacji czytaj **wyłącznie** z sekcji
`CURRENT WORK` na górze pliku — a najlepiej z pomiaru (`bash skrypty/bramka.sh`,
`./vendor/bin/pest`). Dwa miejsca trzymające ten sam stan zawsze rozjadą się
w czasie; to nie jest hipoteza, tylko przyczyna tego wpisu.

Granica okna 23:59 / 24:00 / 24:01 sprawdzona co do sekundy (to zostaje —
opisuje ZAKRES testów fazy, nie ich chwilowy wynik).
**Powtarzalność perturbacji:** 3 przebiegi z rzędu, identyczny wynik, czyste
drzewo robocze — `skrypty/perturbacje-powtarzalne.sh` (reguła 4, D-2026-08-07-21).

**Zostało w F1:** seed o wiarygodnych proporcjach (F1.7) — ostatnie zadanie fazy.

**Weryfikacja niezależna:** runda 1 (`0af30ae`) — 5 twierdzeń obalonych;
runda 2 (`eadf5c5`) — 4 obalone, w tym dwa dotyczące bezpieczeństwa;
rundy 3, 4 i 5 wykonane, runda 6 wykonana w nocy 08/09.08 (29 znalezisk,
raporty w `docs/noc-2026-08-08/`). „Runda 3 — w toku" stało tu jeszcze
09.08 w nocy, trzy rundy po fakcie. Reguła zbieżności rund: D-2026-08-07-16.

**Wartości startowe reguł** (do tabeli konfiguracji, wszystkie wersjonowane):
okno bezpłatnego odwołania 24 h · limit przełożeń 2 · najbliższy termin 2 h ·
kalendarz pacjenta 30 dni · specjalista wystawia 7 dni · przerwa 10 min ·
blokada koszyka 10 min · link płatności 2 dni · **limit niskopłatnych 10 wizyt**
(D-2026-08-07-08) · limit podażowy 4 terminy/tydzień/specjalista.

> **Kredyt za odsprzedany termin — WYJĘTY z tej listy 09.08.** Stała tu wartość startowa
> „włączony". Decyzja właściciela: **poza zakresem pierwszego wdrożenia**
> (`docs/DECYZJE.md`, **D-2026-08-09-01**) — saldo kredytu jest formą historii finansowej
> pacjenta, której `CLAUDE.md` nie przewiduje w tym wdrożeniu. Nie ma dla niego wartości
> startowej, bo nie ma reguły do skonfigurowania.

---

## F0 — Fundament (S)

Szkielet Laravel 13 + PostgreSQL + Redis + Horizon w Docker Compose (dev) · Pest/Larastan/Pint + CI (GitHub Actions: testy, statyka, gitleaks z `GITLEAKS_LICENSE` z sekretów org) · lokalny Keycloak do developmentu: klon repo `Fundacja-Niepodzielni/konta`, uruchomienie wg jego README, import realm · `.env.example` · `docs/DECYZJE.md` założony · **porównanie Jitsi self-host vs Whereby** (dokument z rekomendacją i kosztami — decyzja właściciela przed F4) · przypomnienie właścicielowi: wniosek o nadawcę SMS „Niepodzielni" w SMSAPI.
**Bramka:** `docker compose up` stawia całość; CI zielone na pustym szkielecie; logowanie testowym kontem z lokalnego Keycloaka działa (ref-laravel wzorzec); rekomendacja wideo zapisana.

## F1 — DPIA-checklista, model danych, reguły jako konfiguracja (M) — [spec: M1/1, M4/16, M5/16-17]

NAJPIERW DPIA-checklista (art. 9: co zbieramy, po co, retencje, dostępy) — wynik wpływa na model · migracje: pacjent, specjalista (klucz: `sub` z Keycloak), usługa (flaga fundacja/komercja, prowizja per usługa), rezerwacja (`kwota_zamrozona`, `regula_anulacji_zamrozona` jako pełny zrzut), zgoda (wersjonowana), zdarzenie (append-only), konfiguracja reguł z wersjonowaniem i datą obowiązywania · jedna funkcja rozstrzygająca zwrot/przełożenie/płatność godziny · strefy czasowe (UTC + Europe/Warsaw, testy na dobach 23/25 h).
**Bramka:** testy tabelaryczne reguł na wartościach granicznych (23:59/24:00/24:01); seed o wiarygodnych proporcjach (111 specjalistów, kilkanaście wizyt/pacjenta); migracje w górę i w dół.

## F2 — Silnik dostępności (L) — [spec: M1/2-4, M2/2-5; „najbardziej niedoszacowany element projektu"]

Trzy warstwy (rytm/poprawki/urlopy) · sloty 50+10 i 90+10 (90-min zdejmuje dwa sloty) · horyzonty 2 h / 30 dni / 7 dni · limit podażowy 4 niskopłatnych/tydzień NA SPECJALISTĘ (ISO, reset poniedziałek) — NIE mylić z limitem pacjenta, który wynosi 10 wizyt SUMARYCZNIE (D-2026-08-09-05) · jedna funkcja slotów dla panelu (7 d × 1 os.), wyszukiwarki (30 d × 111) i grafiku (35 d × 111) · materializacja/cache z unieważnianiem per specjalista/dzień · API wyszukiwarki z filtrami (bez N+1).
**Bramka:** testy — zmiana czasu, urlop nachodzący na rytm, poprawka spoza rytmu, kolizja 90-min; wydajność < 300 ms na seedzie 111 osób; **test 100 równoczesnych żądań o ten sam termin = dokładnie jedna rezerwacja**.

## F3 — Rezerwacja + płatności (L) — [spec: M1/6-14, M5/1-4]

Blokada terminu 10 min (atomowo, cron zwalniający) · Stripe Checkout ×2 konta (routing po fladze usługi; karta/BLIK/GPay/APay; 0 zł omija Stripe) · webhooki z weryfikacją podpisu, idempotencją, kolejką ponowień · nocna rekoncyliacja per konto + widok rozjazdów · płatność odroczona (`/oplac/:token`, 2 dni, unieważnianie) · zwroty jako lista zadań (domykanie po `charge.refunded`) · ~~kredyt za odsprzedany termin~~ (**poza zakresem, D-2026-08-09-01**) · odwołanie/przełożenie z egzekwowaniem okna 24 h i limitu 2 zmian · konta pacjentów przez Keycloak Admin API + action-token (konto w tle po płatności).
**Bramka:** E2E w trybie testowym Stripe: rezerwacja→płatność→potwierdzenie; odwołanie przed/po granicy; „zapłacone, termin zajęty" jako osobna ścieżka; webhook zwrotu przed webhookiem płatności nie psuje stanu; token `/oplac` po zwolnieniu terminu.

## F4 — Panel specjalisty: API (M) — [spec: M2]

Endpointy `/panel/*` (DTO bez prowizji — test regresyjny) · dostępność (rytm/siatka/urlopy) · oznaczanie wizyt + auto-domknięcie 48 h + „odpuść tym razem" · odwołanie przez specjalistę (zwrot 100%, licznik 30 dni, alert >10 tylko dla koordynatora) · umawianie pacjenta z linkiem płatności / wnioskiem o zwolnienie z opłaty · rozliczenia (naliczanie wg 5 wariantów, zestawienie PDF serwerowo, obieg faktur z antywirusem i plikami poza katalogiem publicznym) · dwuetapowa zmiana rachunku (link na stary adres) · Google Calendar **tylko freeBusy**.
**Bramka:** test braku prowizji w KAŻDEJ odpowiedzi API dla roli psycholog; naliczanie do grosza na przypadkach z macierzy odwołań; PDF z polskimi znakami.

## F5 — Koordynator: operacje + rozliczenia (L) — [spec: M3, M4]

Grafik zespołu (3 widoki, agregaty, cel < 300 ms) · interwencje (odwołaj/przenieś/zmień specjalistę/„zwróć mimo reguły") · rezerwacja telefoniczna (3 tryby) · kolejka pierwszego kontaktu (6 pytań zamkniętych, art. 9: szyfrowanie + log odsłonięć telefonu) · silnik dopasowania + propozycja z blokadą 2 dni · lista rezerwowa z automatem (okno 4 h, zegar stoi 21:00–8:00) · wydarzenia grupowe (cykle, lista rezerwowa, awanse, obecności) · **dziennik decyzji append-only** (uprawnienia odebrane na poziomie bazy, łańcuch skrótów) · raport grantowy (osoby!, zamknięte kwartały jako snapshot) · katalog usług i cennik z wersjonowaniem · faktury: obieg akceptacji + paczka przelewów.
**Bramka:** testy negatywne uprawnień (para rola × zasób); próba UPDATE na dzienniku = błąd bazy; **suma osób z 4 kwartałów > osoby w roku**; belka trybu płatności zawęża KAŻDE zapytanie panelu.

## F6 — Powiadomienia (M) — [spec: M1/19-20, M5/5-9]

Silnik: tabela zaplanowanych + worker z ponowieniami; **przeplanowanie przy każdej zmianie terminu** (najdroższy element wg spec) · mail: dostawca transakcyjny + SPF/DKIM/DMARC, 7 szablonów w bazie (wersjonowane, biała lista zmiennych), .ics (stały UID, SEQUENCE, METHOD:CANCEL) · SMS przez SMSAPI (E.164, DLR, liczenie segmentów z twardym odcięciem, limit kosztów, bez treści zdrowotnych) · cisza nocna · macierz 7 zdarzeń × mail/SMS jako konfiguracja.
**Bramka:** E2E: zmiana terminu przeplanowuje przypomnienia (stare anulowane — test!); odwołana wizyta NIE dostaje przypomnienia; staging z twardą blokadą wysyłki na prawdziwe adresy.

## F7 — Frontend: podpięcie makiety (L) — [spec: M1/5,8,10-12,16,18,21,25-26,29; WYMAGA źródeł makiety]

Przepięcie ekranów z danych lokalnych na API (fazami: pacjent → specjalista → koordynator) · routing po ścieżkach · usunięcie paska demo i localStorage-ról · stany ładowania/błędów (404/403/wygasły link/utrata połączenia przy płatności) · WCAG 2.1 AA (siatki z klawiatury, kontrasty walidatorem, aria-live) · responsywność paneli · osadzenie wizualne w niepodzielni.com.
**Bramka:** `sprawdz-ekrany` w CI (Chrome headless); scenariusze E2E pełnej ścieżki pacjenta; audyt dostępności 3 przepływów.

**Warunek POKRYCIA, nie treści (D6 przyłożone do kryteriów odbioru):** bramka F7 **nie jest
zielona**, dopóki każde z kryteriów `K1`–`K10` z `_architektura/12-frontend-kryteria-odbioru.md`
(**ŹRÓDŁO, nie kopia** — treści tu nie przepisujemy, bo dwa opisy jednej rzeczy rozjeżdżają się
po cichu, klasa P3) nie ma **albo przypisanej kontroli, albo JAWNEGO wpisu „bez kontroli"
z powodem i warunkiem znoszącym**. Kryterium nieprzypisane i niezadeklarowane → **CZERWONE**.

Wpis „bez kontroli" jest **wynikiem, nie porażką** — `K7` („to, co widzi człowiek, mierzone
u człowieka") prawdopodobnie tam trafi i będzie to poprawne, o ile poda **kto i kiedy patrzył**.

**Deklaracja ma być DANĄ, nie prozą.** Zmierzone u siebie 09.08 (`ODPOWIEDZ-032`): gdy wyjątek
jest tańszy od zgodności, lista wyjątków staje się drogą domyślną — nie przez złą wolę, przez
pośpiech. Wpis „bez kontroli" musi więc kosztować **tyle samo co przypisanie kontroli**:
identyfikator kryterium, powód, warunek znoszący, data.

## F8 — Integracje ekosystemu (M)

API dla WordPressa (profile, terminy — WP tylko wyświetla) · te same 3 endpointy rezerwacyjne co `strona/api/24-app-booking-endpoint.php` (aplikacja mobilna bez zmian) · `GET /api/hub-summary` wg kontraktu z repo `hub` · zgłoszenia problemów → skrzynka Zammad · rejestracja klienta Gabinetu w realm (przez repo `konta`, wraz z rolą `pacjent` w praktyce).
**Bramka:** WP renderuje listing z API Gabinetu na dev; aplikacyjny kontrakt 3 endpointów przechodzi testy kontraktowe.

## F9 — Migracja i produkcja (M) — [spec: M6] — **WYŁĄCZNIE za zgodą właściciela**

Import profili CPT+taksonomie z WP · wariant rezerwacji Bookero (decyzja: przełom miesiąca; szczegół do potwierdzenia) · środowiska prod na VPS-3 (plan: `_architektura/10`) · backupy z ćwiczeniem odtworzeniowym · monitoring + alerty · anonimizacja stagingu · pentest zewnętrzny · pilotaż 10 specjalistów 2 tygodnie · szkolenia · cutover Bookero + eksport archiwum + wypowiedzenie.
**Bramka:** pentest bez krytycznych; ćwiczenie odtworzeniowe z pomiarem czasu; pilotaż zakończony poprawkami; zgoda właściciela na każdy krok publiczny.

## Poza zakresem pierwszego wdrożenia (nie buduj)

Notatki z sesji · pakiety/serie wizyt · historia finansowa pacjenta · kody rabatowe (brak decyzji) · dwukierunkowy sync Google Calendar · automatyczne zwroty przez API Stripe · Stripe Connect.
