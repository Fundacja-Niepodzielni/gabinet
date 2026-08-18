# RUNDA 8 — RAPORT NIEZALEŻNEGO WERYFIKATORA

**SHA mierzone:** `179c05c696f6535ed4d4c9d839e623d4a9ea5e56` (gałąź `faza-1-retencja`, „179c05c").
**Zlecenie:** `docs/ZLECENIA/ZLECENIE-064.md`. **Data pomiaru:** 18.08.2026.
**Stawka:** F1 i F0 zamykają się WYŁĄCZNIE rundą z zerem znalezisk (D-2026-08-07-16).

## Werdykt jednym zdaniem

**Faza NIE zamyka się w tej rundzie. Dwa znaleziska** — oba zmierzone z kontrolą
pozytywną i negatywną instrumentu, oba klasy „kontrola świeci zielono nad luką"
i oba dokładnie tego rodzaju „klasa o krok dalej", którego szukać kazało zlecenie:
naprawa rundy 7 domknęła INSTANCJĘ, a klasa przeniosła się o poziom wyżej.
Bramka jest zielona i zgodna z deklaracją autora co do wszystkich liczb; zamknięcia
R7-1…R7-9, O-6b, D-2, D-4 bronią się pomiarowo.

---

## 0. Środowisko pomiaru — własne izolowane klony, nie `gabinet-perturbacje`

Zgodnie z lekcją rundy 7 (`gabinet-perturbacje` montuje DRZEWO dewelopera) NIE użyłem
tego stosu. Postawiłem własne, efemeryczne klony i stosy, każdy `git checkout 179c05c`,
drzewo po każdym pomiarze wracało do `0 zmian` (`git status --porcelain`):

| klon | katalog | projekt compose | porty (HTTP/PG/Redis) | rola |
|---|---|---|---|---|
| r8  | `d:/tmp/gabinet-r8`  | `gabinet-r8`  | 8118 / 55463 / 56410 | bramka OD ZERA |
| r8b | `d:/tmp/gabinet-r8b` | `gabinet-r8b` | 8116 / 55461 / 56408 | stos żywy do sond |
| r8p | `d:/tmp/gabinet-r8`  | `gabinet-r8p` | 8117 / 55462 / 56409 | pełne perturbacje |
| r8v | `d:/tmp/gabinet-r8v` | `gabinet-r8v` | 8123 / 55471 / 56421 | pomiar rozstrzygający (świeży subagent) |

Pomiar rozstrzygający obu znalezisk wykonał świeży subagent bez mojego kontekstu,
na własnym klonie r8v (sekcja 4). Po rundzie wszystkie stosy zgaszone `down -v`.
Stos dewelopera `gabinet` nietknięty. **Zakaz commitowania utrzymany** — jedyne
zapisy to ten raport i `ODPOWIEDZ-064.md`.

---

## 1. Pełna bramka — wynik LICZBOWY

Przebieg OD ZERA na czystym klonie r8 (`skrypty/bramka.sh --projekt gabinet-r8`):

```
BRAMKA OK — 22 kroków, 0 nieudanych              (kod wyjścia 0)
Tests: 2 skipped, 289 passed (2119 assertions)
WYKONANO 289 testów (podłoga: 289)               (RÓWNO — bez zapasu)
sprawdzono 2119 asercji (podłoga: 2119)          (RÓWNO — bez zapasu)
pominięte (nie liczą się do podłogi): 2          (oba: TwierdzeniaKomentarzyTest, dług D-3)
Pint: PASS 102 files
Larastan (level max): [OK] No errors
gitleaks: no leaks found (151 commits, 3.64 MB, 2.08 s)
czas testów: 44 s
```

Deklaracja autora (`ODPOWIEDZ-062` §1: 22/22, 289/2119, podłogi RÓWNO 289/2119,
znacznik zdjęty) — **POTWIERDZONA co do każdej liczby**. Podłogi to JEDNO źródło
`skrypty/podlogi.sh` (`MINIMUM_TESTOW=289` w. 80, `MINIMUM_ASERCJI=2119` w. 87),
zgodne z sekcją stanu `PLAN-FAZ.md`.

**Znacznik `.przebieg-pomiarowy`:** po zielonym przebiegu ZDJĘTY (zmierzone: `ls`
→ „No such file"). Naprawa 4.6 (dwa `trap … EXIT` walczące o siebie) trzyma się —
pojedynczy uchwyt sprzątający zostawia drzewo czyste, znacznik pada ostatni.

**Perturbacje:** pełny zestaw 31 scenariuszy na własnym stosie r8p →
`PERTURBACJE OK — 48 kontroli udowodniło, że umie zaświecić czerwono (pominięte: 0)`,
kod 0. Zmierzone: 48× `✓`, 0× `✗`. Zgodne z deklaracją (48 kontroli).

**Zamrożenie SHA.** `git diff --stat 179c05c..HEAD -- backend/ skrypty/ docker/
docker-compose.yml .gitleaks.toml .env.example` → **puste**. Czubek gałęzi `7f4c65f`;
po nim commitów NIE MA. `179c05c..7f4c65f` to jeden plik `PLAN-FAZ.md` (10/5), diff
kodu pusty. Dwa znane commity dokumentacyjne — zgodne z opisem architekta, nie
znalezisko.

---

## 2. Weryfikacja zamknięć — co się broni (każde z kontrolą pozytywną i/lub negatywną)

Zmierzone pomiarowo na żywych stosach; „krok dalej" ze zlecenia sprawdzony osobno.

- **R7-1 (fasada w allowliście tożsamości)** — BRONI SIĘ. Wzorzec obejmuje teraz
  `SesjaKonta::`. Kontrola pozytywna: dopisany SZÓSTY plik produkcyjny
  `App\Nowy6Test` z `SesjaKonta::odczytaj($r)` → `WaskieGardloTozsamosciTest`
  **1 failed** (instrument żyje). Bez pliku: 5 passed. Krok dalej (inne drogi do
  sesji: `session()->get('konta')`, `session('konta')`, `$request->session()`) —
  wszystkie niosą literał `'konta'` w kontekście `)`/`;`/`,`, który wzorzec łapie;
  jedyne obejście to sklejanie klucza (`'kon'.'ta'`) — contrived, odnotowuję jako
  granicę, nie znalezisko.
- **R7-2 (domyślne szyfrowanie sesji z TREŚCI, nie literału)** — BRONI SIĘ.
  Kontrola pozytywna: `config/session.php` domyślna `env('SESSION_ENCRYPT', false)`
  → `SesjaBezJawnychDanychTest` (filtr „DOMYŚLNIE") **1 failed**. Test wykonuje plik
  z USUNIĘTĄ zmienną i czyta wynik — żaden literał (komentarz/napis/heredoc) tego
  nie zmieni. Klasa zamknięta, nie instancja.
- **R7-3 (blokada wysyłki — egzekutor WPIĘCIA)** — BRONI SIĘ dla wektora `return;`.
  Kontrola pozytywna: usunięcie wywołania `zablokujWysylkePozaProdukcja()` z `boot()`
  → `BlokadaWysylkiTest` **1 failed**. ⚠ ale jest KROK DALEJ — znalezisko R8-2.
- **R7-4 (druga warstwa base64 nie jest martwa)** — BRONI SIĘ. Obie warstwy
  `zdekodowaneLadunki()` wykonują się zawsze; jeden poziom rekurencji odzyskuje
  e-mail z `base64_encode($idToken)`. Perturbacja `id_token_sesja` noga „ZAKODOWANY"
  w pełnym zestawie: czerwona z badanej przyczyny. `SesjaBezJawnychDanychTest`
  „SKANER DEKODUJE base64url" → 5 passed.
- **R7-5 / D-4 (wyjątek gitleaks ZAWĘŻONY `condition="AND"` + pełne SHA)** — BRONI
  SIĘ dwustronnie. Kontrola: zwolniona wartość `aGVsbG8…` umieszczona w NOWYM
  commicie POZA czwórką zwolnionych (`api_key = "aGVsbG8…"`, commit `f0d9836`) →
  **`leaks found: 1`** (kod 1). Czyli zwolnienie NIE działa poza deklarowanym
  zakresem — dokładnie odwrotnie niż w rundzie 7. (Uwaga metodyczna: `SEKRET_TEST=`
  po polsku NIE odpala reguły `generic-api-key` w ogóle — trzeba angielskiego
  słowa-klucza, inaczej pomiar jest nierozstrzygający; stąd forma `api_key =`.)
- **R7-6 (plik stanu — trzy twierdzenia)** — BRONI SIĘ. `CURRENT WORK` na SHA rundy
  niesie podłogi 289/2119 zgodne z `podlogi.sh`, liczbę zielonych ≥ podłogi, i nie
  cytuje ścieżek jako „NIE POWSTAŁ" wbrew stanowi. `JednoZrodloStanuTest` obejmuje
  wnętrze sekcji i CAŁY plik. Pełna suita zielona.
- **R7-7 (izolacja narzędzi + żywa odmowa)** — BRONI SIĘ. Uruchomione na projekcie
  `gabinet`: `bramka.sh`, `perturbacje.sh`, `perturbacja-odwrotna.sh` — każde
  `[kod 2] ODMOWA`. `odczyt-przyczyn.py` (brak Pythona w kontenerze, sprawdzany
  statycznie) URUCHOMIONY NA HOŚCIE (`python 3.14`) z `GABINET_PERTURBACJE_PROJEKT=gabinet`
  → `[kod 1] ODMOWA: odczyt dynamiczny nie dziala na projekcie 'gabinet'` — ścieżka
  odmowy realnie działa, nie jest samym napisem. Rejestr `docker compose` ZUPEŁNY
  dla plików najwyższego poziomu (patrz §5 — skan nierekurencyjny).
- **R7-8 / D-2 (zapadka `--przyczyna`, sufit 0)** — BRONI SIĘ. Pełna suita zawiera
  obie asercje zapadki (`SUFIT_NIEROZROZNIAJACYCH=0`, `toBe(0)`); zielone → zero
  zdegenerowanych wzorców. Drugi instrument (`odczyt-przyczyn.py`) nie odpalany
  dynamicznie (§5).
- **R7-9 (N-14 chown z filtrem komentarzy)** — BRONI SIĘ. Kontrola pozytywna:
  ZAKOMENTOWANY `chown -R www-data:www-data storage` w `entrypoint.sh` →
  `TrwaloscMagazynowTest` „SRODOWISKO" **1 failed**. Filtr `#` działa.
- **O-6b (strażnik, tożsamość z `--git-common-dir`)** — BRONI SIĘ jako mechanizm.
  Hook `skrypty/git-hooks/pre-commit` (205 w.) ustala tożsamość przez
  `git rev-parse --path-format=absolute --git-common-dir`, więc jedna dla wszystkich
  worktree; ścieżki odmowy (a)(b)(c) są behawioralne, nie napisowe.
  `StraznikCommitaTest` (7 kontroli) w pełnej suicie zielony. K-1/K-2 żyją
  w `skrypty/straznik-w-worktree.sh` (nie krok bramki — §5).

---

## 3. ZNALEZISKA — każde zmierzone

Waga jak w rundach 6–7: KRYTYCZNA (luka §2/§10 eksploatowalna DZIŚ) · WYSOKA ·
ŚREDNIA · NISKA. Żadne z poniższych nie jest eksploatowalne z zewnątrz na SHA rundy —
oba są defektami KONTROLI (zielone nad luką). Reguła zbieżności tego nie łagodzi:
„kontrola świecąca zielono nad usuniętym/omijalnym mechanizmem" jest w tym
repozytorium klasą, która już kosztowała (R6A-4, R6A-11, R7-3).

---

### R8-1 (WYSOKA) — siatka POMIAROWA D-1b jest ślepa na atak z nazwą parametru SPOZA baterii; deklarowana „sieć bezpieczeństwa" (perturbacja `d1b`) tej luki NIE pokrywa

**Kontekst.** Runda 7 §3b wprowadziła `SiatkaPomiarowaTozsamosciTest` jako odpowiedź
na to, że dwie siatki DEKLARATYWNE (`BrakWlasnychHaselTest`) pytają o SPOSÓB
(krypto / schemat / trasy) i przepuszczają atak `===` na istniejącej kolumnie.
Siatka pomiarowa reklamuje się, że „pyta o SKUTEK — zapis tożsamości do sesji —
niezależnie od sposobu" (`SiatkaPomiarowaTozsamosciTest.php:31-46`).

**Co zmierzone.** Skutek jest wykrywany TYLKO dla nazw parametrów z baterii
(`SiatkaPomiarowaTozsamosciTest.php:138`):
`['haslo','password','pin','sekret','token','kod','nazwa_wyswietlana']` + `email` + `sub`.
Atak, który czyta sekret pod nazwą SPOZA tej listy, produkuje ten sam skutek
(zapis `konta` do sesji), ale siatka nigdy nie wysyła tego parametru, więc go nie
wyzwala. Pomiar na żywym stosie r8b (mechanizm identyczny z perturbacją `d1b`,
zmieniona wyłącznie nazwa czytanego pola `nazwa_wyswietlana` → `zaklecie`):

```
KONTROLA POZYTYWNA (nazwa Z baterii, `nazwa_wyswietlana`):
   SiatkaPomiarowaTozsamosciTest → 1 failed          (siatka zapala się — działa)

MUTACJA (nazwa SPOZA baterii, `zaklecie`; dowód mutacji: input('zaklecie')=1),
wersja BRAMKOWO-CZYSTA (z osłoną `is_string()` — Larastan i Pint zielone):
   SiatkaPomiarowaTozsamosciTest → 3 passed           (siatka ŚLEPA)
   BrakWlasnychHaselTest         → 9 passed           (obie deklaratywne ślepe)
   PEŁNA SUITA                   → 289 passed          (nic nie łapie)
   Larastan (level max)          → No errors
   Pint                          → PASS 102 files
```

**Mechanizm nadal loguje** — potwierdzone niezależnie na klonie r8v (subagent):
konto o znanym sekrecie w bazie, żądanie `GET /?zaklecie=<sekret>` puszczone przez
jądro HTTP → `session()->has('konta')` = **TAK** (zapis tożsamości poza OIDC,
złamana `CLAUDE.md` §2).

**Uwaga uczciwa o Larastanie.** Najkrótsza forma mutacji (`(string) request()->input`)
odpala Larastana na `cast mixed to string` — ale to NIE jest detektor mechanizmu
logowania, tylko incydentalna typizacja, i znika po dodaniu `is_string()`. Wersja
bramkowo-czysta (zmierzona wyżej) omija siatkę, suitę, Larastana i Pinta razem.
Detektor §2 (siatka) jest ślepy niezależnie od Larastana.

**Dlaczego to znalezisko, a nie znana granica.** Autor deklaruje ograniczenie
baterii — ale w tym samym miejscu podaje SIEĆ BEZPIECZEŃSTWA, która ma je znieść.
`SiatkaPomiarowaTozsamosciTest.php:131-135` i `ODPOWIEDZ-062` §8:
> „mechanizm przyjmujący sekret pod nazwą spoza baterii zostanie złapany przez
>  perturbację `d1b`, która wywołuje go dokładnie tak, jak zrobiłby to atakujący."

To twierdzenie jest **nieprawdziwe wobec własnej implementacji**: perturbacja
`d1b` (`skrypty/perturbuj.py:235`) wstrzykuje atak czytający `nazwa_wyswietlana` —
nazwę Z BATERII. Nie wywołuje więc żadnej nazwy spoza baterii i nie może dowieść
pokrycia, które reklamuje. Zmierzona luka (nazwa `zaklecie`) NIE jest łapana ani
przez siatkę, ani przez perturbację, ani przez nic w suicie. To dokładnie klasa
„instrument pytający o co innego, niż twierdzi" (R7-1) i „twierdzenie »sprawdziłem
X« podlegające obaleniu" — przeniesiona o krok: siatka miała być pomiarem SKUTKU,
a jest pomiarem skutku WARUNKOWANYM zgadnięciem nazwy wejścia, czyli po cichu
wróciła do pytania o SPOSÓB (nazwę parametru).

**Waga WYSOKA, nie KRYTYCZNA:** na SHA rundy takiego mechanizmu w kodzie NIE MA
(perturbacja przywracana), więc nic nie jest dziś eksploatowalne. Groźny jest
FAŁSZYWY SPOKÓJ: trzy siatki i perturbacja meldują pokrycie §2, którego pod nazwą
spoza baterii nie ma.

**Odtworzenie.** Na żywym stosie: `python skrypty/perturbuj.py d1b-podloz`, potem
w `backend/routes/web.php` zamień `request()->input('nazwa_wyswietlana', '')` na
odczyt pola `zaklecie` (mechanizm bez zmian, opcjonalnie z `is_string()` dla
czystości bramki) → uruchom `pest tests/Feature/SiatkaPomiarowaTozsamosciTest.php`
→ ZIELONE mimo żywego mechanizmu logowania poza OIDC. Kontrola pozytywna: bez
zmiany nazwy → CZERWONE.

**Kierunek naprawy (measurement pokazuje wektor, nie zalecenie na papierze).**
Siatka, żeby naprawdę pytać o SKUTEK, nie może zależeć od nazwy wejścia. Dwa
kierunki: (1) ładunek generowany z RZECZYWISTYCH pól wejściowych każdej trasy
(nie z zamkniętej baterii) przy szpiegu na zapisie klucza `konta`; (2) perturbacja
`d1b` parametryzująca nazwę pola spoza baterii, żeby dowodziła tego, co deklaruje.
Dopóki tego nie ma, opis w `SiatkaPomiarowaTozsamosciTest` i `ODPOWIEDZ-062` §8
należy sprostować: bateria JEST dziś jedynym nośnikiem czułości, a perturbacja
`d1b` pokrycia spoza baterii NIE dowodzi.

---

### R8-2 (WYSOKA) — egzekutor blokady wysyłki (§10) jest ślepy na WYREJESTROWANIE providera; buduje `AppServiceProvider` ręcznie, więc nie widzi cyklu życia frameworka

**Kontekst.** Runda 7 (R7-3) zamknęła „mechanizm §10 napisany, ale NIE WPIĘTY":
`BlokadaWysylkiTest` sprawdza teraz, że `AppServiceProvider::boot()` realnie
podmienia sterownik poczty na `log`. Zamknięcie zadeklarowane jako „mechanizm
WPIĘTY" (`ODPOWIEDZ-062` §2, R7-3).

**Co zmierzone.** Egzekutor buduje providera RĘCZNIE
(`BlokadaWysylkiTest.php:44`: `(new AppServiceProvider($this->app))->boot()`).
Sprawdza więc, że METODA wpina sterownik i że `boot()` woła metodę — ale NIE, że
framework w ogóle ładuje TEN provider. Provider jest zarejestrowany w JEDNYM
miejscu: `backend/bootstrap/providers.php`. Pomiar (r8b oraz niezależnie r8v) —
wyrejestrowanie providera (lista pusta):

```
REALNY APP (config blokady=true, mail.default=smtp, po boot):
   provider zaladowany: NIE
   mail.default: smtp                    ← BLOKADA §10 MARTWA (poczta wyszłaby na zewnątrz)

BlokadaWysylkiTest → 2 passed            (buduje providera ręcznie — NIE widzi wyrejestrowania)
PEŁNA SUITA        → 289 passed          (nic nie łapie)
```

**Kontrast, który to potwierdza (asymetria).** Ta sama klasa „czy mechanizm jest
WPIĘTY w cykl życia" jest gdzie indziej rozwiązana POPRAWNIE: `SprawdzUniewaznienie`
middleware sprawdza `ZasiegUniewaznieniaTest` przez `Route::gatherRouteMiddleware()`
— czyli czyta REALNIE zebrane middleware z jądra. Kontrola pozytywna: usunięcie
`appendToGroup('web', SprawdzUniewaznienie::class)` z `bootstrap/app.php` →
`ZasiegUniewaznieniaTest` **2 failed**. Middleware egzekwuje wpięcie przez skutek;
blokada wysyłki — nie, bo konstruuje jednostkę pod ręką.

**Dlaczego to znalezisko „o krok dalej".** R7-3 przesunęło pytanie z „czy metoda
istnieje" na „czy metoda jest wołana z `boot()`". Została NASTĘPNA warstwa tej samej
klasy: „czy `boot()` jest wołany przez framework" (czy provider jest zarejestrowany).
Egzekutor konstruujący providera ręcznie jest STRUKTURALNIE niezdolny to zobaczyć —
to R6A-11 (mechanizm bez pokrycia URUCHOMIENIA) dla §10, jedno piętro wyżej niż
R7-3. Konsekwencja identyczna z tą, którą R7-3 nazywa: staging z konfiguracją
z produkcji wysyła przypomnienia na PRAWDZIWE adresy pacjentów (`CLAUDE.md` §10).

**Waga WYSOKA (jak R7-3), z uczciwą uwagą o wyzwalaczu:** złamanie wymaga edycji
`bootstrap/providers.php` (zmiana konspicytna, nie ukryty `return;`), a `AppServiceProvider`
robi też inne rzeczy, więc wyrejestrowanie miałoby skutki uboczne. Ale KONTROLA §10
nie jest tu strażnikiem — jest ślepa z konstrukcji, a §10 to twarda zasada RODO.

**Odtworzenie.** Na żywym stosie: opróżnij `backend/bootstrap/providers.php`
(`return [];`) → w realnym app `config(['gabinet.blokada_wysylki'=>true,
'mail.default'=>'smtp'])`, po `boot()` `mail.default` = `smtp` (blokada martwa),
a `BlokadaWysylkiTest` i cała suita = ZIELONE. Kontrola pozytywna asymetrii:
usuń wpis middleware z `bootstrap/app.php` → `ZasiegUniewaznieniaTest` czerwone.

**Kierunek naprawy.** Kontrola, która pyta o SKUTEK w realnym cyklu życia: asercja,
że `app()->getProvider(AppServiceProvider::class)` NIE jest `null` (provider
faktycznie załadowany), albo test wykonujący pełny bootstrap jądra i czytający
`config('mail.default')` po nim — jak `ZasiegUniewaznieniaTest` czyta zebrane
middleware, a nie konstruuje je pod ręką.

**Zbadany, ale ODRZUCONY jako znalezisko wariant siostrzany:** domyślna wartość
`gabinet.blokada_wysylki`. `config/gabinet.php:39` bierze
`env('GABINET_BLOKADA_WYSYLKI', true)` — domyślnie `true` (bezpiecznie), a
`.env.example:136` PRZYPINA `GABINET_BLOKADA_WYSYLKI=true`. Zmierzone: podmiana
domyślnej na `false` NIE zmienia nic, bo zmienna jest ustawiona w środowisku.
To NIE jest nawrót R7-2 (tam `SESSION_ENCRYPT` w `.env` NIE było) — dlatego nie
podnoszę tego do rangi znaleziska.

---

## 4. Pomiar rozstrzygający — świeży subagent, klon r8v, bez mojego kontekstu

Świeży subagent postawił WŁASNY klon `d:/tmp/gabinet-r8v` na `179c05c` (status
początkowy pusty), własny stos (`BRAMKA OK — 21 kroków, 0 nieudanych`), wykonał
oba pomiary od zera i zgasił stos (`down -v`, klon usunięty). Surowe wyjścia:

| # | pomiar | surowe wyjście |
|---|---|---|
| A | siatka bazowa (atak `d1b` wstrzyknięty, nazwa Z baterii) | `Tests: 1 failed, 2 passed` |
| A | dowód mutacji `session()->put('konta'` | linia 15 obecna |
| A | siatka, nazwa `zaklecie` (SPOZA baterii) | `Tests: 3 passed` |
| A | siatki deklaratywne `BrakWlasnychHaselTest` | `Tests: 9 passed` |
| A | pełna suita, nazwa `zaklecie` | `Tests: 2 skipped, 289 passed` |
| A | Larastan (surowa forma bez `is_string`) | `[ERROR] Found 1 error` (cast mixed→string; nie detektor §2) |
| A | **dowód HTTP: `session()->has('konta')`** | **`tozsamosc_w_sesji=TAK`** |
| A | git status po przywróceniu | (pusty) |
| B | realny app z pustą listą providerów | `provider_zaladowany=NIE mail_default=smtp` |
| B | egzekutor `BlokadaWysylkiTest` | `Tests: 2 passed` |
| B | pełna suita z pustą listą providerów | `Tests: 2 skipped, 289 passed` |
| B | git status po przywróceniu | (pusty) |

Oba znaleziska odtworzone niezależnie, bez mojego kontekstu. Rozbieżność Larastana
(A) to artefakt najkrótszej mutacji subagenta (`(string) mixed`), nie detektor
mechanizmu logowania — moja wersja bramkowo-czysta (§3, R8-1) omija Larastana,
Pinta, siatkę i suitę razem, a dowód HTTP `tozsamosc_w_sesji=TAK` potwierdza, że
mechanizm loguje pod nazwą spoza baterii.

---

## 5. CZEGO NIE SPRAWDZIŁEM (sekcja obowiązkowa)

1. **`odczyt-przyczyn.py` w trybie DYNAMICZNYM przeciw żywemu stosowi** — sprawdziłem
   tylko ścieżkę ODMOWY (uruchomieniem na hoście) i zapadkę statyczną. Deklaracja
   autora „14 wywołań ZGODNE-ROZROZNIA, 0 rozbieżności" (`ODPOWIEDZ-062` §2 R7-8)
   przyjęta bez własnego przebiegu dynamicznego (mieli pełne suity per wzorzec).
2. **O-6b K-1/K-2 na REALNYM drugim worktree** — `skrypty/straznik-w-worktree.sh`
   nie jest krokiem bramki; sprawdziłem jego kod i kod hooka (tożsamość z
   `--git-common-dir` poprawna), ale nie postawiłem drugiego worktree i nie
   uruchomiłem checkera end-to-end (checker ma zaszyty literał tożsamości `gabinet`,
   więc w klonie meldowałby rozjazd nazwy — artefakt klonu, nie defekt).
3. **Rejestr `docker compose` — skan NIEREKURENCYJNY.** `KlamraSkryptowTest`
   „rejestr ZUPEŁNY" używa `File::files(skrypty)` (bez podkatalogów). Dziś nic nie
   ucieka (jedyne pliki w `skrypty/git-hooks/`, `skrypty/perturbacje-odwrotne/`
   nie wołają `docker compose`), więc to LATENTNA wąskość, nie znalezisko — narzędzie
   dodane w podkatalogu wymknęłoby się kontroli. Odnotowuję jako dług konstrukcyjny.
4. **Wyścig SIGTERM→SIGKILL na żywej bramce/perturbacjach** (4.5/4.6) — sprawdziłem
   statyczną formę (`KlamraSkryptowTest`: jeden uchwyt, kolejność zamek→znacznik)
   i skutek na zielonym przebiegu (znacznik zdjęty), ale NIE odtworzyłem realnego
   przerwania sekwencją sygnałów w oknie między znacznikiem a zamkiem.
5. **Współbieżność** (`CLAUDE.md` §6, 100 równoczesnych żądań) — poza suitą, zakres
   F3 (jawnie odroczone).
6. **Tezy kontraktowe wobec `konta/`, `hub/`, `helpdesk/`** — nie mam tych repo
   w zasięgu tej rundy; cytaty B7/B8/BLK-22/§4.x przyjęte za wierne.
7. **Treść `docs/specyfikacja/`, `WYTYCZNE-PRACY.md`, ~60 plików `docs/ZLECENIA/`**
   poza cytatami do konkretnych zamknięć.
8. **Migracje/schemat** poza tym, co mierzy bramka (`OCZEKIWANY_SCHEMAT` nie
   porównywany z żywym schematem inaczej niż przez zielony `ModelDanychTest`).

---

## 6. Zakres pokryty — dla jawności

Zmierzone: pełna bramka OD ZERA (22 kroki, 289/2119, RÓWNO z podłogami) na czystym
klonie; pełny zestaw 31 perturbacji (48 kontroli, 0 pominiętych); zamrożenie SHA
dwustronnie; zamknięcia R7-1…R7-9 + O-6b + D-2 + D-4 z kontrolą pozytywną i/lub
negatywną instrumentu (R7-1, R7-2, R7-3, R7-5, R7-9 z żywą kontrolą pozytywną;
R7-4, R7-6, R7-8, O-6b przez suitę/perturbację/kod); dwa własne pomiary rozstrzygające
na dwóch izolowanych klonach + niezależny subagent na trzecim.

**Zbieżność rund:** 11 → 15 → 12 → 29 → 9 → **2**. Malejąca, ale NIE zerowa.

**Faza F1/F0 pozostaje OTWARTA — dwa znaleziska. Runda nie kończy się zerem.**
Fazę zamyka wyłącznie runda z zerem znalezisk (D-2026-08-07-16) — kryterium
nie łagodzę.
