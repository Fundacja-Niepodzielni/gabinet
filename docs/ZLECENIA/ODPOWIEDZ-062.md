# ODPOWIEDZ-062 — cykl naprawczy rundy 7 zamknięty

**Od:** sesja KOD-F1 · **Do:** architekt · **Data:** 18.08.2026
**W odpowiedzi na:** `ZLECENIE-062` (naprawy R7-1…R7-9 + O-6b + D-1b)
**SHA zamrożone:** `179c05c696f6535ed4d4c9d839e623d4a9ea5e56` (gałąź `faza-1-retencja`, „179c05c")

---

## 0. Jednym zdaniem

Komplet dziewięciu znalezisk naprawiony z pomiarem, O-6b i D-1b wdrożone, dług
allowlist `--przyczyna` **spłacony do zera** — a przy naprawianiu wskazanych wad
wyszło **siedem kolejnych**, których raport nie wskazywał, w tym dwie w samym
przyrządzie pomiarowym. Wszystkie naprawione i zamknięte kontrolami.

---

## 1. Pomiary rozstrzygające

```
BRAMKA OK — 22 kroków, 0 nieudanych              (przebieg OD ZERA, kod wyjścia 0)
289 testów, 2119 asercji, 2 pominięte
podłogi 289 / 2119                               (RÓWNO — bez zapasu)
PERTURBACJE OK — 48 kontroli udowodniło, że umie zaświecić czerwono (pominięte: 0)
znacznik `.przebieg-pomiarowy` po przebiegu bramki:  ZDJĘTY
```

Podłogi podniesione trzema krokami, każdy z powodem wpisanym do `podlogi.sh`:
`265/2024 → 284/2098 → 286/2108 → 287/2112 → 289/2119`.

**Pominięte dwa** to nadal `TwierdzeniaKomentarzyTest` (dług D-3, bez zmian).

---

## 2. R7-1…R7-9 — co zmieniono i gdzie klasa mogła stać o krok dalej

Pytanie obowiązkowe ze zlecenia zadane przy każdej pozycji; odpowiedź w drugiej
kolumnie, także gdy brzmi „nigdzie, sprawdziłem X".

### R7-1 — allowlista tożsamości nie widziała fasady
Wzorzec obejmuje teraz `TozsamoscSesji::`, **`SesjaKonta::`** i literał klucza.
Allowlista 2 → 5 plików, każdy z uzasadnieniem; kontrola negatywna obejmuje
wszystkie wpisy, nie dwa pierwsze.

**Krok dalej:** sprawdziłem trzy pozostałe drogi do sesji w Laravelu — `session()`,
`$request->session()`, fasadę `Session::`. Żadna nie sięga po tożsamość inaczej
niż przez KLUCZ, a klucz jest we wzorcu. Czwartej drogi nie znalazłem.

### R7-2 — literał w napisie zamiast w komentarzu
Kontrola tekstowa zastąpiona **POMIAREM**: usuwamy `SESSION_ENCRYPT` ze
środowiska, wykonujemy `config/session.php`, czytamy wartość domyślną.

**Krok dalej:** nigdzie — i to jest ciekawsza część. Pierwsza próba (filtr napisów)
**obaliła się sama**: zaślepienie literałów niszczy szukany napis, bo on sam jest
literałem. To był sygnał, że narzędzie jest złe, a nie że potrzebuje lepszego
filtra. Pomiar nie czyta tekstu, więc żadna postać tekstu — komentarz, napis,
heredoc, konkatenacja — nie ma jak wejść.

### R7-3 — blokada wysyłki bez egzekutora wpięcia
Nowy `tests/Feature/BlokadaWysylkiTest.php`, obok istniejącego `tests/Unit/…`
(tamten bada czystą funkcję i robi to dobrze). Woła `AppServiceProvider::boot()`
i sprawdza, że sterownik poczty realnie staje się `log`; kierunek odwrotny
pilnuje, żeby przy WYŁĄCZONEJ blokadzie sterownik został.

**Pomiar różnicowy:** `return;` na wejściu mechanizmu → nowy egzekutor CZERWONY
z komunikatem o adresach pacjentów, a stary `SzkieletTest` **8 passed**.

**Krok dalej:** „mechanizm wpięty, ale ktoś nadpisuje sterownik po nim" — łapie to
kierunek odwrotny, bo mierzy stan PO `boot()`, nie fakt wywołania.

### R7-4 — druga warstwa base64
Usunięty wczesny `return`, dodany JEDEN poziom rekurencji w `zdekodowaneLadunki()`.

**Krok dalej:** trzeci poziom zagnieżdżenia. Świadomie NIE zamykam go
nieograniczoną rekurencją — granica jest jawna i opisana. **Zysk uboczny okazał
się większy od naprawy:** po niej perturbacja `id_token_sesja` zaczęła zapalać
WŁAŚCIWĄ asercję, więc dług `:1208` dało się w ogóle spłacić (patrz §4).

### R7-5 — wyjątek gitleaks bez zawężenia
`condition = "AND"` — i to **zaczerwieniło bramkę**, odsłaniając drugą wadę w tym
samym wpisie: gitleaks porównuje SHA commita DOKŁADNIE, a we wpisie stały skróty
(`31727fb215`). Kryterium `commits` nie pasowało więc do niczego; przy łączeniu
przez OR nie było tego widać, bo wpis przechodził na samym `regexes` — czyli
dokładnie tak szeroko, jak zarzucił weryfikator. Dwie wady, obie tej samej
rodziny: opis mówił o zakresie, którego implementacja nie miała.

Naprawa: pełne SHA. `git log -S` pokazał przy tym **czwarty** commit z przynętą,
o którym opis wyjątku nie wiedział.

```
repo po naprawie                                 → no leaks found
klon + przynęta w NOWYM commicie (poza czwórką)   → leaks found: 2
```

**Krok dalej:** wyjątek ma zniknąć przy scalaniu — pozycja O-1 listy scaleniowej.

### R7-6 — plik stanu niósł trzy twierdzenia nieprawdziwe
`CURRENT WORK` przepisany ze stanu ZMIERZONEGO. Egzekutor rozszerzony o WNĘTRZE
sekcji, w trzech sprawdzalnych rodzajach: (a) podłogi zgodne z `podlogi.sh`,
(b) liczba zielonych nie niższa od podłogi (sprzeczność wykrywalna BEZ
uruchamiania suity), (c) zdania „NIE POWSTAŁ / NIE ISTNIEJE" o cytowanych
ścieżkach muszą być prawdziwe.

**Krok dalej:** trzecie twierdzenie leżało w PODSEKCJI `###`, poza blokiem stanu —
czyli wąska kontrola przepuściłaby je ponownie. Zasięg (c) rozszerzyłem na CAŁY
plik, z tą samą regułą kotwicy: zdanie z datą albo skrótem commita nazywa
ZDARZENIE i się nie starzeje.

### R7-7 — dwa narzędzia mieliły stos i drzewo dewelopera
`perturbacja-odwrotna.sh`: własny projekt `gabinet-odwrotna`, własne porty,
`--env-file` + `GABINET_PLIK_ENV`, odmowa na projekcie dewelopera, znacznik
przebiegu, cofanie przez `cp` z kopii zamiast `git checkout --`.
`odczyt-przyczyn.py`: korzeń ze ścieżki własnej, prefiks `dc` z `-p`/`--env-file`,
odmowa, interpreter powłoki wybierany **testem funkcjonalnym** (zmierzone:
`subprocess.run(['bash', …])` trafia tu w bash WSL-a, który nie widzi `D:/…`,
a `shutil.which()` wskazuje Git Bash — sama nazwa nie rozstrzyga).

Kontrola R6B-16 zastąpiona **rejestrem ZUPEŁNYM**: każdy plik w `skrypty/`
wspominający `docker compose` musi być sklasyfikowany jako IZOLOWANY albo
WYJATEK-* z powodem sprawdzalnym asercją. Kształt jest ważniejszy od dwóch
dopisanych wpisów — lista plików do sprawdzenia przepuściłaby trzeci skrypt.

Kontrola odmowy przeszła z odczytu kodu na **uruchomienie narzędzia**: pierwsza
wersja szukała napisu `PROJEKTY_ZABRONIONE` i fałszywie oskarżyła `bramka.sh`,
która odmawia od zawsze, tylko przez zmienną `PROJEKT_DEWELOPERA`. Test pisowni
udający test własności.

**Krok dalej:** `odczyt-przyczyn.py` ma WŁASNY odczyt statyczny — z dokładnie tą
ślepotą, którą R7-8 naprawiło w zapadce, bo powstał z niej przez przepisanie.
Naprawiony razem; inaczej dwa przyrządy mierzyłyby tę samą własność różnie,
a rozbieżność jest tam **z definicji znaleziskiem**, więc generowałaby fałszywe.

### R7-8 — zapadka liczyła 2 z 5
Słownik zielonego przebiegu (nazwy testów + **nazwy klas** + stałe raportera)
i rozbijanie **alternatywy ERE** na gałęzie, bo `grep -qiE` spełnia się przy
dowolnej. Twierdzenie weryfikatora o nagłówku klasy było dedukcyjne — zmierzyłem
je wydrukiem:

```
pest tests/Feature/BrakWlasnychHaselTest.php  →  PASS  Tests\Feature\BrakWlasnychHaselTest
pest --filter="marker"                        →  „marker" 3×, PASS … BramkiTest 1×
```

Czyli obie gałęzie `"Bramki|marker"` były zdegenerowane, nie jedna.
**Dług 5 → 0**, sufit zapadki 0 — zapadka jest odtąd zwykłą bramką.

Cztery wzorce przepisane na komunikaty asercji **skopiowane z czerwieni**
(nie z pamięci), dwóm testom dopisano brakujące komunikaty. Piątego spłaciła
naprawa gdzie indziej — R7-4.

Drugi, niezależny instrument potwierdził wynik: `odczyt-przyczyn.py` na stosie
perturbacyjnym → **14 wywołań ZGODNE-ROZROZNIA, 0 rozbieżności**.

**Krok dalej:** komunikat połknięty przez matcher wariadyczny — §4.1.

### R7-9 — kontrola N-14 na surowej treści
Filtr komentarzy powłoki przed `str_contains` w `TrwaloscMagazynowTest`.

**Krok dalej:** ta sama klasa siedziała w odczycie podłóg — §4.2.

---

## 3. O-6b i D-1b

**O-6b.** Strażnik w katalogu wspólnym, tożsamość z `--git-common-dir`, kontrole
K-1/K-2 wykonywane w każdym aktywnym worktree.

**D-1b — postać docelowa z raportu §3b, wcielona.**
`SiatkaPomiarowaTozsamosciTest`: szpieg na zapisie klucza sesji `konta`
z atrybucją do trasy. Jedyną trasą, której wolno ustanowić tożsamość, jest
`/auth/callback`; wyjątek ma własną kontrolę, żeby nie stał się martwy.
Pyta o SKUTEK, nie o sposób — więc obejmuje `===`, `hash()`, `sodium_*`
i cokolwiek jeszcze, bo każdy mechanizm logowania MUSI zakończyć się zapisem
tożsamości.

Nowa perturbacja `d1b` odtwarza atak weryfikatora (porównanie `===` w kolumnie
i na trasie JUŻ zadeklarowanych). Pomiar rozstrzygający:

```
siatka POMIAROWA      → CZERWONA z właściwej przyczyny
siatki DEKLARATYWNE   → nadal ZIELONE  (dokładnie dlatego trzecia siatka istnieje)
```

---

## 4. Siedem wad WŁASNYCH, wykrytych przy naprawach

Żadnej z nich nie ma w raporcie rundy 7. Wszystkie wyszły z naprawiania
wskazanych — wspólny mianownik „klasa przeniosła się o krok" działa też w tę stronę.

### 4.1 Komunikat asercji połknięty przez matcher WARIADYCZNY
`->not->toContain('redaktor', 'Role czytane z ID TOKENU.')` — drugi argument
`toContain()` to KOLEJNA IGŁA, nie komunikat. Dwa „komunikaty" stały tak
w `OdebranieRoliTest` **od rundy 2** i nigdy nie trafiały do wyjścia.

To unieważnia opis długu D-2 po raz drugi: perturbacja `:1130` nie miała czego
szukać nie dlatego, że komunikatu nie napisano, tylko dlatego, że sygnatura go
połykała. Zamknięte kontrolą **parsującą** (`token_get_all`) — wersja na
wyrażeniu regularnym zapalała się na własnych komentarzach i na własnej kontroli
negatywnej, a zaślepienie napisów było tu niemożliwe, bo szukany argument SAM
JEST napisem.

### 4.2 Odczyt podłóg brał wartość z KOMENTARZA
`podlogiZeZrodla()` czytało `GABINET_MINIMUM_TESTOW=0` z komentarza
`podlogi.sh:27` → podłoga **0** → „każda liczba jest nie niższa od podłogi".
R6A-6 trafione w kontroli pisanej PRZECIWKO nieprawdziwym twierdzeniom pliku
stanu, w tej samej godzinie, w której zamykałem tę klasę gdzie indziej.
Naprawione filtrem komentarzy i kotwicą `^`, z osobną kontrolą przyrządu.

### 4.3 Siatka D-1b nie zapalała się na ataku
Sondowała wartościami z powietrza (`'haslo' => 'Test'`), a mechanizm poświadczeń
porównuje z sekretem W BAZIE — pustej w teście. „Zero trafień" było zgodne
z dwoma światami: „nikt nie loguje poza OIDC" i „nie ma z czym porównać".
Rozdzieliła je własna perturbacja w pierwszym przebiegu. Siatka zakłada teraz
konto o znanych wartościach i sonduje DOKŁADNIE nimi.

### 4.4 `odczyt-przyczyn.py` fałszywie oskarżał sprawną perturbację
Zakładał, że mutacja mieszka zawsze w DRZEWIE. Dla `p_sesja` siedzi w samym
poleceniu (`-e SESSION_ENCRYPT=false`), więc czytnik uruchamiał świat JUŻ
zmutowany i meldował „GAŁĄŹ BAZOWA — ta perturbacja nie może niczego dowieść".
Naprawa: zdejmowanie mutacji z polecenia przed odczytem bazowym.

### 4.5 KOLEJNOŚĆ W UCHWYTACH SPRZĄTANIA
`sprzataj_po_przebiegu() { znacznik_zdejmij; przywroc_wszystko; }` — znacznik
padał PRZED przywróceniem drzewa. Przy sekwencji SIGTERM→SIGKILL, którą wysyła
każdy nadzorca procesów, uchwyt zdąża zdjąć znacznik i nie zdąża przywrócić
plików.

**Zmierzone na własnym drzewie:** przerwany pełny zestaw zostawił w
`backend/routes/web.php` **żywą trasę `/wejscie/zaloz` z `Hash::make`**,
a znacznika już nie było. Czysty SIGTERM kończy się poprawnie (kod 130, mutacja
cofnięta) — więc wada mieszka wyłącznie w tym oknie. Znacznik pada odtąd OSTATNI.

Tę samą kolejność wpisałem tego dnia sam do `perturbacja-odwrotna.sh` przy
naprawie R7-7 — godzinę po tym, jak w tym samym pliku napisałem, że wiedza
w komentarzu obok nie propaguje się sama.

### 4.6 `bramka.sh` ustawiała DWA uchwyty dla tego samego sygnału
```
trap znacznik_zdejmij EXIT     (wiersz 167)
trap zwolnij_zamek    EXIT     (wiersz 201)
```
`trap` PODMIENIA uchwyt, nie dokłada. Drugie wywołanie po cichu wyrzucało
pierwsze, więc bramka **nigdy** nie zdejmowała własnego znacznika. To samo dla
`INT TERM` — `przerwano_bramke` był martwy od początku.

Groźne jest nie śmiecenie: osierocony znacznik sprawia, że strażnik commita
widzi „trwa przebieg pomiarowy" i ODMAWIA **po każdym przebiegu bramki**.
Odmowa, która pada zawsze, uczy odruchu `rm -rf .przebieg-pomiarowy` — kasowania
ochrony bez patrzenia. Rodzina U-5: tam jeden `trap` obsługiwał za dużo
sygnałów, tu dwa walczyły o ten sam; oba razy różnicę widać wyłącznie przy
wyjściu ze skryptu, dlatego przeszło przez wszystkie poprzednie rundy.

Sprzątanie idzie teraz JEDNYM uchwytem, ustawionym raz i PRZED założeniem zamka
(żeby przerwanie w oknie między znacznikiem a zamkiem też było obsłużone).
Zamek pada pierwszy, znacznik ostatni. Pomiar „był → zniknął": przed naprawą
znacznik zostawał po zielonym przebiegu, po naprawie jest ZDJĘTY.

### 4.7 `p_puls` celowała w magazyn, którego nie ma
Kasowała wpis z tabeli `sygnaly_zdrowia`. Puls przeszedł **trzy** adresy —
cache → tabela (R6B-10) → plik `storage/puls-harmonogramu` (bo tabela kazała
sondzie zdrowia czekać na schemat tworzony później i krok [5] stawał na
`scheduler=starting`) — a scenariusz za każdym razem zostawał przy poprzednim.

Za pierwsze dwa razy świecił **ZIELONO**: mutacja szła w magazyn, którego nie ma,
puls zostawał, `--sprawdz` słusznie przechodziło, scenariusz nie mierzył niczego.
Trzeci raz wyszedł głośno tylko przez przypadek — tabela zniknęła, więc poleciał
wyjątek. Gdyby została pusta, byłoby zielono dalej. To N-3 w trzeciej odsłonie.

Naprawa nie polega na wpisaniu nowej ścieżki — to przesunęłoby ten sam błąd
o krok. Adres podaje odtąd **sam mechanizm** (`gabinet:puls --gdzie`), więc
czwartą przenosinę perturbacja przeżyje bez zmian.

Przy tym egzekutorze złapałem się na tej samej klasie **trzeci raz w tej sesji**:
pierwszy warunek sprawdzał obecność frazy `gabinet:puls --gdzie` i kontrola
pozytywna go NIE OBALIŁA — ta sama fraza stoi w komunikacie `printf` obok.
Filtrowanie komentarzy nie pomaga, bo `printf` komentarzem nie jest. Warunek
pyta teraz o STRUKTURĘ: ścieżka musi powstać z podstawienia polecenia
(`plik_pulsu="$( … --gdzie … )"`), czego żaden komunikat nie spełni.

**Dodatkowo, ten sam pełny zestaw:** wzorzec przyczyny `p_sesja` i komunikat
asercji były dwoma różnymi napisami (`Wartość` vs `Wartosc`) — `grep -qiE` nie
zwija wielkości liter poza ASCII. Klasa opisana w nagłówku `oczekuj_czerwone`,
tylko tym razem rozjechał się komunikat, nie wzorzec.

---

## 5. Stan długów

| dług | stan |
|---|---|
| **D-2** — allowlisty `--przyczyna` | **SPŁACONY**, sufit zapadki 0 |
| **D-3** — `TwierdzeniaKomentarzyTest` zdjęty z bramki (14 obejść na 15) | BEZ ZMIAN |
| **D-4** — wyjątek gitleaks | zawężony i ZMIERZONY, ale ISTNIEJE → O-1 listy scaleniowej |

**D-2, opis prawdziwy:** poprzedni („dług 2, blokuje brak komunikatów asercji
w dwóch testach") był nieprawdziwy w liczbie i w przyczynie. Prawdziwa liczba to
**5**, a przyczyny były **trzy**: ślepota parsera zapadki (nazwy klas, gałęzie
ERE), komunikaty połknięte przez matcher wariadyczny i wada
`zdekodowaneLadunki()`, przez którą właściwa asercja nie zapalała się wcale.

---

## 6. Czego NIE zrobiłem — i dlaczego

- **`docs/DECYZJE.md` nie tknięty.** Leży w nim niezacommitowany wpis innej sesji
  (`D-2026-08-12-04`), a plik jest poza zakresem tej sesji (`.zakres-sesji`).
  Decyzje z tego cyklu są udokumentowane w komentarzach przy kodzie i w tym
  meldunku; wpisy D do dopisania w oknie scaleniowym, razem z konsolidacją.
- **Historii gitleaks nie przepisałem.** Warunek znoszący wyjątku stoi
  i jest terminowy: O-1 listy scaleniowej, gdy drzewo będzie ciche.
- **Fazy nie zamykam.** Zamyka ją runda.

---

## 7. Dla sesji TESTY (jedno zdanie do kanału)

`.zakres-sesji` w worktree `gabinet-testy-plan-f2` ma wpisane
`gabinet-testy-plan-f2` jako obejście S-02. **Po naprawie O-6b ten wiersz może
wrócić na `gabinet`** — zmiana jednej linii u siebie plus
`bash skrypty/straznik-w-worktree.sh` na potwierdzenie. Nie ruszam pliku
w cudzym drzewie.

---

## 8. Gotowość do rundy 8

Zbieżność: 11 → 15 → 12 → 29 → 9 → **teraz**. Zgłaszam gotowość na SHA
`179c05c`. Dla weryfikatora, żeby nie szukał po omacku:

- **przyrząd pomiarowy zmieniałem w tym cyklu** (`bramka.sh`, `perturbacje.sh`,
  `perturbacja-odwrotna.sh`, `odczyt-przyczyn.py`) — to jest naturalne miejsce
  ataku i sam bym tam patrzył pierwszy;
- **dwie kontrole opierają się na uruchomieniu skryptów z testu** (`exec()`
  w `KlamraSkryptowTest`) — jeśli `exec` będzie w danym środowisku wyłączony,
  proszę o rozstrzygnięcie, bo dziś limit jest zmierzony, nie założony;
- **kontrola żywej odmowy nie obejmuje `odczyt-przyczyn.py`**, bo w kontenerze
  nie ma Pythona; to ograniczenie jest ZMIERZONE osobnym testem, który zapali
  się, gdy Python się pojawi;
- **siatka D-1b sonduje baterią nazw parametrów** — bateria podnosi czułość,
  ale nie jest dowodem pokrycia i mówię to wprost w kodzie. Mechanizm
  przyjmujący sekret pod nazwą spoza baterii łapie perturbacja `d1b`.
