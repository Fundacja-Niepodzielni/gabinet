# RUNDA 11 — RAPORT NIEZALEŻNEGO WERYFIKATORA

**SHA mierzone:** `bbc8167d83a281225a5b5a742aeb8b13f5760210` (gałąź `faza-1-retencja`, „bbc8167").
**Zlecenie:** `docs/ZLECENIA/ZLECENIE-075.md` + jedno uzupełnienie architekta w kanale
(dwa znane commity dokumentacyjne ponad `bbc8167`, rozszerzenie D-5).
**Data pomiaru:** 19.08.2026.
**Stawka:** F1 i F0 zamykają się WYŁĄCZNIE rundą z zerem znalezisk (D-2026-08-07-16).

## Werdykt jednym zdaniem

**Faza NIE zamyka się w tej rundzie. TRZY znaleziska.** Bramka na `bbc8167` jest
zielona **22/22**, zgodna z deklaracją autora co do KAŻDEJ liczby (304 testy / 2211
asercji, podłogi RÓWNO 304/2211, perturbacje 54 kontrole / 37 scenariuszy / 0
pominiętych); wszystkie zamknięcia rundy 10 (R10-1 warstwa 3, wady własne §5,
kotwica §6, podłogi) bronią się z kontrolą pozytywną i negatywną; rozszerzenie D-5
jest wąskie i commit-scoped dokładnie tak, jak architekt deklaruje. **Ale:**

- **R11-1 (WYSOKA)** — obalone WPROST twierdzenie autora z `ODPOWIEDZ-074` §3, że
  przepływ tożsamości przez **zmienną pośrednią** pokrywają warstwy 1–3. Zmienna
  pośrednia czytająca pole **KONTRAKTOWE** (`code`) i użyta jako tożsamość
  w `SesjaKonta::zaloz` przechodzi CAŁĄ bramkę i realnie loguje — warstwa 3 milczy
  słusznie (pole w kontrakcie), warstwa 4 milczy (nie śledzi zmiennej), warstwy 1–2
  milczą (legalna fasada z callbacku). To jest piąte piętro, które autor sam wystawił
  do obalenia.
- **R11-2 (WYSOKA)** — druga, niezależna dziura tego samego wąskiego gardła:
  warstwy 2 i 4 skanują **wyłącznie `zaloz`**, a nie `zaktualizuj`. Publiczne
  `TozsamoscSesji::zPodmienionymi(['sub' => …])` na WŁASNEJ (zalogowanej) tożsamości
  + `SesjaKonta::zaktualizuj` PODMIENIA `sub` na dowolną wartość z żądania — całą
  bramkę przechodzi, tożsamość zmieniona (zmierzone runtime: `pacjent` → dowolny
  `sub`). Bez `Reflection`/`unserialize`, więc warunek utrzymujący R6A-3 nietknięty.
- **R11-3 (NISKA)** — kontrola liczby scenariuszy (`JednoZrodloStanuTest`, R9-5 po
  zmianie §6) POMIJA zdania zakotwiczone bez weryfikacji liczby wobec wskazanego SHA.
  Fałszywa liczba obok istniejącego SHA przechodzi — negatywna kontrola zamknięcia §6
  NIE zapala. Ta sama klasa co R9-5, której §6 miało domknąć.

**Zbieżność rund:** 11 → 15 → 12 → 29 → 9 → 2 → 5 → 1 → **3**.

---

## 0. Środowisko pomiaru — własne izolowane klony, nie `gabinet-perturbacje`

Zgodnie z lekcją rundy 7 NIE użyłem stosu `gabinet-perturbacje` (montuje DRZEWO
dewelopera). Cztery efemeryczne klony przypięte do `bbc8167` (refy potomne przycięte
PRZED skanem sekretów — lekcja rundy 10) plus jeden klon z historią do `f8f64c0`
do weryfikacji D-5. Po pomiarach drzewa wracały KOPIĄ pliku, `git status --porcelain`
PUSTE; stosy zgaszone `down -v`.

| klon | katalog | projekt compose | porty HTTP/PG/Redis | rola |
|---|---|---|---|---|
| klon-a | `D:/tmp/gabinet-r11/klon-a` | `gabinet-r11a` | 8170 / 55520 / 56470 | bramka OD ZERA |
| klon-b | `D:/tmp/gabinet-r11/klon-b` | `gabinet-r11b` | 8171 / 55521 / 56471 | drugi przebieg (`--zostaw`) + żywy stos do sond zamknięć i mutacji |
| klon-c | `D:/tmp/gabinet-r11/klon-c` | `gabinet-r11c` | 8172 / 55522 / 56472 | PEŁNE perturbacje |
| klon-head | `D:/tmp/gabinet-r11/klon-head` | — (tylko gitleaks) | — | warunek zamrożenia + wąskość D-5 na czubku `f8f64c0` |
| (subagent) | `D:/tmp/gabinet-r11/klon-a` | `gabinet-r11sub` | 8180 / 55530 / 56480 | pomiar rozstrzygający R11-1 (świeży subagent, po zgaszeniu `r11a`) |

**Higiena klonu (§Przedmiot pomiaru zlecenia — zastosowana OD POCZĄTKU).** Po
sklonowaniu: `git checkout bbc8167`, usunięcie `refs/remotes`, `refs/tags`, gałęzi
`faza-1-retencja`, `reflog expire --expire=now --all` + `gc --prune=now`. Po tym
`git rev-list --all --count` = **151**, historia kończy się na `bbc8167`, krok [21]
gitleaks ZIELONY (`no leaks found`, 151 commitów). Bez przycięcia [21] zapaliłby się
na cytacie `GOCSPX-…` z commita potomnego (`661e8a6`/`f8f64c0`) — to znane, nie
znalezisko.

Stos dewelopera `gabinet` (8098/55442/56389) i `gabinet-perturbacje` (8097)
**NIETKNIĘTE** — zweryfikowane po rundzie. Zakaz commitowania w repozytorium projektu
utrzymany — jedyne zapisy to ten raport i `ODPOWIEDZ-075.md`, oba niezacommitowane.
Commity powstały WYŁĄCZNIE w klonie efemerycznym `klon-head` (weryfikacja D-5 wymaga
skanu historii); klon usunięty w całości. Nic nie wypchnięto.

---

## 1. Pełna bramka — wynik LICZBOWY

Przebieg OD ZERA na czystym klonie `klon-a` przypiętym do `bbc8167`
(`bash skrypty/bramka.sh --projekt gabinet-r11a`), kod wyjścia 0:

```
BRAMKA OK — 22 kroków, 0 nieudanych              (kod wyjścia 0)
Tests: 2 skipped, 304 passed (2211 assertions)
WYKONANO 304 testów (podłoga: 304)               (RÓWNO — bez zapasu)
sprawdzono 2211 asercji (podłoga: 2211)          (RÓWNO — bez zapasu)
pominięte (nie liczą się do podłogi): 2          (oba: TwierdzeniaKomentarzyTest, WARN, dług D-3)
Pint:                 PASS 104 files
Larastan (level max): [OK] No errors
gitleaks:             151 commits scanned, no leaks found
czas testów:          55 s
```

**Deklaracja autora (`ODPOWIEDZ-074` §1) POTWIERDZONA co do każdej liczby:**
22/22 · 304/2211 · podłogi RÓWNO 304/2211 · 2 pominięte.
Podłogi to JEDNO źródło `skrypty/podlogi.sh` (`MINIMUM_TESTOW=304`,
`MINIMUM_ASERCJI=2211`) — **odczytane, nie zacytowane**.
Dwa pominięte to oba testy z `Tests\Feature\TwierdzeniaKomentarzyTest` (WARN
w Pest, dług D-3) — potwierdzone w logu.

**Drugi, niezależny przebieg** na klonie `klon-b` (projekt `gabinet-r11b`, inne porty,
`--zostaw`): `BRAMKA OK — 21 kroków, 0 nieudanych` (21, bo `--zostaw` pomija krok [22]
sprzątania), `304 passed (2211 assertions)`, `151 commits, no leaks found` — te same
liczby na innym klonie, innym projekcie compose i innych portach.

**Perturbacje — PEŁNY zestaw, własny stos `gabinet-r11c`:**

```
PERTURBACJE OK — 54 kontroli udowodniło, że umie zaświecić czerwono (pominięte: 0)
kod wyjścia 0
zmierzone niezależnie: 37 nagłówków „=== PERTURBACJA", 54 wiersze „✓", 0 wierszy „✗"
```

**Zgodne z deklaracją autora (`ODPOWIEDZ-074`: 54 kontrole / 37 scenariuszy /
0 pominiętych) co do każdej liczby.** Lista scenariuszy w `perturbacje.sh`
(`WSZYSTKIE=…`) niesie **37** nazw. Nowe perturbacje `p_callback_tablica`
i `p_callback_metoda` zapalają z BADANEJ przyczyny (patrz §2).

---

## 2. Weryfikacja zamknięć z `ODPOWIEDZ-074` — każde z kontrolą pozytywną I negatywną

Pomiary na żywym stosie `gabinet-r11b`, drzewo przywracane KOPIĄ pliku
(nie `git checkout`); po każdym `git status --porcelain` PUSTE.

### R10-1 — warstwa 3 pyta o ODCZYTANE POLE, nie o składnię: **BRONI SIĘ**

| kontrola | pomiar | wynik |
|---|---|---|
| NEGATYWNA (czysto) | `WaskieGardloZapisuTozsamosciTest` | **8 passed (60 asercji)** |
| POZYTYWNA (odczyt POLA SPOZA kontraktu zmienną pośrednią) | `$x = $request->query('zaklecie'); zaloz(..., ['sub'=>$x])` w `powrot()` | **1 failed** — „CALLBACK OIDC CZYTA Z ŻĄDANIA COŚ SPOZA SWOJEGO KONTRAKTU" (warstwa 3) |
| **PRZYRZĄDU (krytyczna): `code` DOSTĘPEM TABLICOWYM NIE zapala** | `$kod = $request['code'];` w `powrot()` | **8 passed** — warstwa 3 NIE oskarża legalnego odczytu `code` tablicą |
| perturbacja `p_callback_tablica` | `$request['zaklecie']` | **✓** czerwień z `--przyczyna "…SPOZA SWOJEGO KONTRAKTU"` |
| perturbacja `p_callback_metoda` | `$request->str('zaklecie')` | **✓** czerwień z tej samej allowlisty |

Kontrola przyrządu (`code` tablicą → zielone) jest KRYTYCZNA i broni się: naprawa
R10-1 pyta o POLE (kontrakt dotyczy `code`/`state`), nie o SKŁADNIĘ — nie zamieniła
jednej listy na drugą. Obie perturbacje mają `dowod_mutacji` (`grep -q zaklecie`)
i `--przyczyna` będącą KOMUNIKATEM ASERCJI.

### WARSTWA 4 (nowa) — istnieje, ale jej twierdzenie o pokryciu OBALONE (R11-1)

| kontrola | pomiar | wynik |
|---|---|---|
| NEGATYWNA (czysto) | warstwa 4 w suicie | **zielona** (część z 8 passed) |
| POZYTYWNA (bezpośredni request w 2. arg `zaloz`) | `SesjaKonta::zaloz($request, ['sub' => $request->query('code')])` w `powrot()` | **1 failed** — „DANE TOŻSAMOŚCI POCHODZĄ Z ŻĄDANIA, NIE ZE ZWERYFIKOWANEGO TOKENU" |

Warstwa 4 UMIE zapalić na wariancie bezpośrednim. **Ale nie pokrywa zmiennej
pośredniej czytającej pole kontraktowe** — patrz R11-1 (§3).

### Wady własne §5 — obie BRONIĄ SIĘ

- **§5(a) literał superglobali, KAŻDY element osobno:** test `KIERUNEK ODWROTNY W3`
  iteruje pętlą po pięciu przypadkach (`superglobal`, `-get`, `-request`, `-cookie`,
  `-server`), każdy oczekuje `toBe(1)` — nie jednego reprezentanta (w. 801-809).
  Zielony w suicie.
- **§5(b) proza unieważniająca typy:** blok opisu `polaZadaniaCzytaneW` stoi
  w komentarzu ZWYKŁYM `/* … */` (w. 320-355), a docblock `/** @param … */`
  jest ODDZIELNY (w. 356-360). Larastan `[OK] No errors` (14, nie 40 błędów).
  Statyka realnie sprawdza tę funkcję.

### §6 — kontrola liczby scenariuszy respektuje kotwicę: **kontrola pozytywna OK,
NEGATYWNA ZAWODZI (R11-3)**

- POZYTYWNA: wszystkie kotwice w sekcji stanu wskazują ISTNIEJĄCE commity
  (`git cat-file -e`), a zakotwiczone liczby są PRAWDZIWE wobec swoich SHA
  (`528adc3`: 301/2170, 35 scenariuszy; `d79dc0c`: 290/2130). Zmierzone
  `git show <SHA>:skrypty/…`. Zdanie z kotwicą jest dziś wierne.
- NEGATYWNA: patrz R11-3 (§3) — fałszywa liczba obok istniejącego SHA przechodzi.

### Podłogi RÓWNO — BRONI SIĘ

`podlogi.sh`: `MINIMUM_TESTOW=304`, `MINIMUM_ASERCJI=2211`. Bramka: „WYKONANO 304
(podłoga 304), sprawdzono 2211 (podłoga 2211)". RÓWNO, bez zapasu.

### Znane długi

- **D-3** (`TwierdzeniaKomentarzyTest` poza bramką): potwierdzone — `2 skipped`,
  oba z tej klasy (WARN). Bez zmian.
- **D-4** (wyjątek gitleaks, base64 `hello-world-…`): wpis obecny w `.gitleaks.toml`
  na `bbc8167` (`condition="AND"`, cztery pełne SHA). Przynęty w nowym commicie NIE
  odtwarzałem (patrz §8).
- **D-5** (cytat sekretu): zweryfikowany POMIAREM jako wąski i commit-scoped — §5.
  Rozszerzony o commit `661e8a6` zgodnie z deklaracją architekta.

**D-4 i D-5 OBA obecne** — żaden nie usunięty pojedynczo (`grep -c` na `.gitleaks.toml`:
D-4 = 1, D-5 = 1). Nowych długów autor nie zaciąga.

---

## 3. ZNALEZISKA

Waga: KRYTYCZNA (luka eksploatowalna DZIŚ z zewnątrz na czystym drzewie) · WYSOKA ·
ŚREDNIA · NISKA. Żadne z trzech nie jest eksploatowalne na czystym `bbc8167` — na
czystym drzewie tych mechanizmów NIE MA. Groźny jest **fałszywy spokój**: bramka
(i meldunek autora) reklamuje niezmiennik „tożsamość tylko przez callback OIDC"
i pokrycie warstw 1–3, których pomiar nie potwierdza.

---

### R11-1 (WYSOKA) — obalone twierdzenie `ODPOWIEDZ-074` §3: zmienna pośrednia czytająca POLE KONTRAKTOWE (`code`) i użyta jako tożsamość w `zaloz` przechodzi CAŁĄ bramkę i loguje; warstwy 1–3 tego NIE pokrywają

**Co twierdzi autor (do obalenia).** `ODPOWIEDZ-074` §3:

> „Czego warstwa 4 nie widzi (nazwane, nie zamiecione): wartość przeprowadzona przez
>  zmienną pośrednią (`$x = $request['a']; … zaloz($request, ['sub' => $x])`). …
>  Zasięg pokrywają wtedy warstwy 1–3, bo taki odczyt **musi** gdzieś nastąpić
>  i będzie widoczny jako pole **spoza kontraktu**."

Twierdzenie ma dziurę: opiera się na słowie **„spoza kontraktu"**. Jeśli zmienna
pośrednia czyta pole **W kontrakcie** (`code` albo `state`), odczyt jest widoczny,
ale LEGALNY — warstwa 3 milczy SŁUSZNIE, a warstwa 4 nie śledzi zmiennej. Zasięg NIE
jest pokryty.

**Mechanizm (wstawiony na początku ciała `powrot()`, plik z allowlisty, trasa
`/auth/callback`, zapis legalną fasadą, bez nowej kolumny/trasy/krypto):**

```php
$sub = $request->query('code');          // pole KONTRAKTOWE → warstwa 3 milczy
if (is_string($sub) && $sub !== '') {
    $konto = DB::table('users')->where('nazwa_wyswietlana', $sub)->first();
    if ($konto !== null) {
        SesjaKonta::zaloz($request, ['sub' => $sub, 'role' => ['pacjent']]);  // 2. arg = $sub → warstwa 4 milczy
        return redirect('/');
    }
}
```

**Co zmierzone (stos `gabinet-r11b`, drzewo przywracane kopią):**

```
WaskieGardloZapisuTozsamosciTest (4 warstwy) → 8 passed  (WSZYSTKIE WARSTWY ZIELONE)
BrakWlasnychHasel + SiatkaPomiarowa          → 12 passed
PEŁNA SUITA                                  → 2 skipped, 304 passed (2211)  ← identycznie jak bazowo
Larastan (level max)                         → [OK] No errors
Pint --test                                  → PASS 104 files
```

**DOWÓD, że mechanizm realnie loguje** (jądro HTTP w kontenerze, konto o znanej
`nazwa_wyswietlana=OFIARA-R11`, każdy przypadek w OSOBNYM procesie PHP):

```
poprawny (/auth/callback?code=OFIARA-R11)  status=302  has(konta)=TAK  sub="OFIARA-R11"
bledny   (/auth/callback?code=NIE-MA)      status=400  has(konta)=NIE  sub=null
brak     (/auth/callback)                  status=302  has(konta)=NIE  sub=null
```

Trzy różne wyniki dowodzą, że to REALNE poświadczenie (mechanizm sprawdza istnienie
konta), a nie „loguje wszystkich". Tożsamość `sub` = wartość `code` podana przez
żądającego, z pominięciem wymiany kodu i weryfikacji podpisu.

**Kontrole pozytywne przyrządu (że przyrząd UMIE zapalić, tylko nie tu):**

```
DIRECT:      zaloz($request, ['sub' => $request->query('code')])   → WARSTWA 4 CZERWONA
                „DANE TOŻSAMOŚCI POCHODZĄ Z ŻĄDANIA…"
POŚREDNIA + POLE SPOZA KONTRAKTU: $x = $request->query('zaklecie'); zaloz(..., ['sub'=>$x])
                → WARSTWA 3 CZERWONA „…SPOZA SWOJEGO KONTRAKTU"
```

Czyli dziura jest DOKŁADNIE w części wspólnej: zmienna pośrednia (warstwa 4 ślepa)
∧ pole kontraktowe (warstwa 3 słusznie milczy). Zielone NIE bierze się z zepsutego
testu.

**Dlaczego znalezisko, a nie znana granica.** Autor NAZWAŁ granicę warstwy 4
(zmienna pośrednia) — ale ZAŁOŻYŁ jej pokrycie przez warstwy 1–3, uzasadniając to
zdaniem „widoczne jako pole spoza kontraktu". Dla pola KONTRAKTOWEGO zdanie jest
nieprawdziwe. Zgodnie z regułą `ZLECENIE-067` („jeśli nazwaną granicę da się
wykorzystać w tym środowisku — to znalezisko") i kryterium rozjazdu opisu ze stanem
— to znalezisko. Klasa ta sama co rundy 8–10 („lista/odczyt strukturalny zamiast
śledzenia przepływu"), o piętro wyżej: z „sposobu odczytu" na „POCHODZENIE danych
przez zmienną pośrednią z pola dozwolonego".

**Odtworzenie (dokładne polecenia).**
Klon przypięty do `bbc8167` (przyciąć refy potomne), stos przez
`bash skrypty/bramka.sh --projekt <p> --zostaw`. W
`backend/app/Http/Controllers/LogowanieController.php`: dodać
`use Illuminate\Support\Facades\DB;` i wstawić powyższy blok na początku ciała
`powrot()` (końce linii LF). W kontenerze `<p>-app`, w `/srv/gabinet/backend`:
`./vendor/bin/pest tests/Feature/WaskieGardloZapisuTozsamosciTest.php` → **8 passed**;
`./vendor/bin/pest` → **304 passed**; `./vendor/bin/phpstan analyse --no-progress`
→ **No errors**; `./vendor/bin/pint --test` → **PASS**. Dowód HTTP: patrz §4
(subagent — dokładny skrypt).

**Kierunek naprawy (wektor z pomiaru).** Warstwa 4 musi śledzić PRZEPŁYW: jeśli 2.
argument `zaloz` zawiera zmienną, której wartość pochodzi (choćby przez jedno
przypisanie w tej funkcji) z odczytu żądania — zgłosić, NIEZALEŻNIE od tego, czy
odczytane pole jest w kontrakcie. Kontrakt `code`/`state` uzasadnia CZYTANIE pola,
nie UŻYCIE go jako tożsamości: `code` służy do wymiany, nie do bycia `sub`. Do czasu
naprawy twierdzenie §3 powinno brzmieć „warstwy 1–3 pokrywają zmienną pośrednią
TYLKO gdy czyta pole SPOZA kontraktu; dla `code`/`state` — NIE".

---

### R11-2 (WYSOKA) — warstwy 2 i 4 skanują wyłącznie `zaloz`; `zaktualizuj` + publiczne `zPodmienionymi(['sub'=>…])` PODMIENIA tożsamość na dowolną, w pliku z allowlisty, i przechodzi CAŁĄ bramkę

**Kontekst.** `SesjaKonta` ma DWA pisarze klucza tożsamości: `zaloz` (TWORZY)
i `zaktualizuj` (AKTUALIZUJE). Wąskie gardło ogranicza wyłącznie `zaloz`:

- warstwa 2 (`wywolaniaStatyczne('SesjaKonta','zaloz')`) wiąże `zaloz` z `powrot` —
  `zaktualizuj` NIE jest liczone;
- warstwa 4 (`daneTozsamosciWZaloz`) skanuje 2. argument `zaloz` — `zaktualizuj`
  NIE jest skanowane (w. 568: `$t[1] !== 'zaloz'`).

Obie metody są w `LEGALNE_ZAPISY` (warstwa 1 dopuszcza zapis przez fasadę). Domniemane
zabezpieczenie `zaktualizuj`: wymaga `TozsamoscSesji` — „dowodu, że tożsamość
istniała". Ale publiczna `TozsamoscSesji::zPodmienionymi(['sub' => X])` zwraca nową
instancję z **DOWOLNIE podmienionym `sub`** — czyli zmienia, KIM jest tożsamość, nie
tylko ją odświeża. To NIE wymaga `Reflection`/`unserialize` (warunek utrzymujący
R6A-3 nietknięty) — używa wyłącznie sankcjonowanego API na WŁASNEJ, zalogowanej
tożsamości.

**Mechanizm (w pliku z allowlisty `OdswiezanieSesji.php`, ścieżka bez odświeżania
w `stanKonta`):**

```php
$tozsamosc = SesjaKonta::odczytaj($request);          // MOJA tożsamość (muszę być zalogowany)
if ($request->query('code') !== null) {
    $tozsamosc = $tozsamosc->zPodmienionymi(['sub' => Typy::napis($request->query('code'))]);
    SesjaKonta::zaktualizuj($request, $tozsamosc);     // zapis podmienionego sub
}
```

**Co zmierzone (stos `gabinet-r11b`):**

```
WaskieGardloZapisuTozsamosciTest + WaskieGardloTozsamosciTest → 13 passed (73 asercje)
PEŁNA SUITA                                                   → 2 skipped, 304 passed (2211)
Larastan → [OK] No errors    ·    Pint --test → PASS 104 files
```

**DOWÓD RUNTIME podmiany tożsamości** (`stanKonta` wywołane z sesją atakującego
`sub=ATAKUJACY-PACJENT`, żądanie `?code=VICTIM-KOORDYNATOR`):

```
sub PRZED:                              ATAKUJACY-PACJENT
sub PO stanKonta (code=VICTIM-...):     VICTIM-KOORDYNATOR
w sesji zapisane:                       VICTIM-KOORDYNATOR
```

Tożsamość podmieniona na dowolną wartość z żądania i UTRWALONA w sesji — a wąskie
gardło zielone. Można też podać `role` w `zPodmienionymi` (eskalacja pionowa:
`pacjent` → `koordynator`).

**Dlaczego znalezisko, a nie znana granica.** Autor nazwał czterech pięter (nazwa
pola → dostarczenie → składnia odczytu → pochodzenie danych `zaloz`) — `zaktualizuj`
jako drogi zapisu tożsamości **nie nazwał NIGDZIE**. Zabezpieczenie oparte na
„`zaktualizuj` tylko aktualizuje" jest fałszywe, bo `zPodmienionymi` zmienia `sub`.
To jest SPOZA listy znanych długów (kryterium zlecenia) i wykorzystana granica
(reguła `ZLECENIE-067`). Wymaga uprzedniego zalogowania (odczyt zwraca `null` bez
tożsamości) — dlatego to eskalacja uprawnień, nie logowanie z zewnątrz; ale
niezmiennik §2 („tożsamość tylko przez callback OIDC") jest złamany, a bramka
milczy.

**Odtworzenie.** Jak wyżej, w `backend/app/Tozsamosc/OdswiezanieSesji.php` wstawić
blok w `stanKonta()` przed `if (! $this->wymagaOdswiezenia($tozsamosc))`. Uruchomić
`WaskieGardloZapisuTozsamosciTest` + `WaskieGardloTozsamosciTest` → **13 passed**;
pełna suita → **304 passed**. Runtime: w kontenerze zbudować `Request::create('/auth/ja','GET',['code'=>'X'])`
z `setLaravelSession` i `session()->put('konta', ['sub'=>'…','access_exp'=>time()+3600,…])`,
wywołać `app(OdswiezanieSesji::class)->stanKonta($req)` i odczytać `['sub']`.

**Kierunek naprawy.** Warstwa 4 ma skanować OBIE metody (`zaloz` I `zaktualizuj`);
osobna kontrola ma pilnować, że `zPodmienionymi` NIE zmienia `sub` (tożsamość
z ID tokenu jest niezmienna w ramach sesji — odświeżeniu podlegają role/tokeny, nie
`sub`). Alternatywnie warstwa 2 ma wiązać `zaktualizuj` wyłącznie z `OdswiezanieSesji::przelicz`.

---

### R11-3 (NISKA) — kontrola liczby scenariuszy (§6) POMIJA zdanie zakotwiczone bez weryfikacji liczby wobec SHA; fałszywa liczba obok istniejącego SHA przechodzi

**Kontekst.** `ODPOWIEDZ-074` §6 zmieniła kontrolę R9-5 tak, by NIE porównywała
liczby scenariuszy w zdaniu zakotwiczonym z bieżącym skryptem (żeby nie wymuszać
przepisywania historii). Efekt uboczny: dla linii z kotwicą kontrola po prostu
`continue`-uje (`JednoZrodloStanuTest`, w. 389-391) — **nie sprawdza, czy liczba jest
prawdziwa wobec wskazanego SHA**. Autor sam wystawił to do obalenia
(`ODPOWIEDZ-074` §9: „zdanie z SHA obok dowolnej liczby przechodzi bez pytania").

**Co zmierzone (stos `gabinet-r11b`).** Do sekcji `CURRENT WORK` w `PLAN-FAZ.md`
wstawiłem FAŁSZYWĄ zakotwiczoną liczbę:

```
- FALSZYWA PROBA: 999 scenariuszy — zmierzone na `528adc3` (naprawdę 35).
```

Wynik: `JednoZrodloStanuTest` → **10 passed** (bez zmian). Kontrola NIE zapaliła.
Liczba scenariuszy na `528adc3` jest sprawdzalna (`git show 528adc3:skrypty/perturbacje.sh`
→ 35), a mimo to fałszywe „999" obok istniejącego SHA przeszło.

**Dlaczego znalezisko.** Jest to negatywna kontrola zamknięcia §6, która NIE ZAPALA —
czyli zamknięcie nie broni się negatywnie. Klasa jest DOKŁADNIE ta sama co R9-5
(„290/2130 zmierzone na `179c05c`, gdzie jest 289/2119" — pomiar przypisany SHA,
na którym jest niemożliwy). Naprawa R9-5 domknęła „SHA musi istnieć" (`git cat-file -e`),
a zmiana §6 otworzyła „liczba przy SHA jest ufana bez sprawdzenia". Dla liczby
SCENARIUSZY sprawdzenie jest tanie i dostępne (kontrola już woła `git cat-file -e`,
więc ma dostęp do `git show <SHA>:skrypty/perturbacje.sh`). Waga NISKA: to kontrola
integralności pliku stanu, nie ścieżka do pieniędzy pacjenta ani kartotek, i nie ma
DZIŚ żywego rozjazdu (wszystkie zakotwiczone liczby są prawdziwe). Odnotowuję wprost,
że autor tę granicę zadeklarował — ale zadeklarowana granica eksploatowalna jest
znaleziskiem, nie długiem (reguła `ZLECENIE-067`), a kryterium fazy to zero.

**Kierunek naprawy.** Dla zdania „N scenariuszy — zmierzone na `<SHA>`" weryfikować
N przez `git show <SHA>:skrypty/perturbacje.sh` (liczbę nazw w `WSZYSTKIE=`), nie
pomijać. Wtedy kotwica ani nie starzeje się, ani nie jest ufana na słowo.

---

## 4. Pomiar rozstrzygający — świeży subagent, własny stos, bez mojego kontekstu

Świeży subagent (bez mojego kontekstu i bez informacji, jakiego wyniku „się
spodziewam") postawił własny stos na `klon-a` (`gabinet-r11sub`, porty
8180/55530/56480) i wykonał pomiary R11-1 od zera. Odtworzył wariant zmiennej
pośredniej z polem kontraktowym:

```
bramka (--zostaw):           BRAMKA OK — 21 kroków, 0 nieudanych, kod 0
WaskieGardloZapisu (4 warstwy): 8 passed / 0 failed (60 asercji)
pełna suita:                 2 skipped, 304 passed (2211) — IDENTYCZNIE jak bazowo
Larastan:                    [OK] No errors
Pint --test:                 PASS 104 files
DOWÓD HTTP (3 procesy):      poprawny=302 sub="OFIARA-SUB" · bledny=400 NIE · brak=302 NIE
drzewo po przywróceniu:      git status --porcelain PUSTE, HEAD=bbc8167
teardown:                    down -v, zero pozostałości gabinet-r11sub
```

**Rozbieżność między moim pomiarem a pomiarem subagenta co do R11-1: ŻADNA.**
Odtworzone niezależnie, na innym stosie i innych portach. Subagent potwierdził
wprost: parametr `code` jest przyjmowany jako tożsamość i sesja `pacjent` powstaje
dla dowolnego napisu pasującego do `nazwa_wyswietlana`, z pominięciem walidacji tokenu.

---

## 5. Warunek zamrożenia i wąskość D-5 (uzupełnienie architekta) — NIE są znaleziskami

### Warunek zamrożenia — dwie formy, zgłaszam rozbieżność (WYTYCZNE)

Ponad `bbc8167` stoją DWA znane commity dokumentacyjne: `661e8a6` (meldunek + raport
rundy 10 + kotwica) i `f8f64c0` (rozszerzenie D-5). Zmierzone na `klon-head`:

```
git diff --stat bbc8167..HEAD -- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md'
   →  .gitleaks.toml | 6 ++++++     (forma ZLECENIE-075 body: NARUSZONA)
git diff --stat bbc8167..HEAD -- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md' ':(exclude).gitleaks.toml'
   →  PUSTO                          (forma uzupełnienia: SPEŁNIONA)
git diff --name-only bbc8167..HEAD -- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md'
   →  .gitleaks.toml                 (JEDYNY plik; commit f8f64c0, D-5)
```

To ta sama klasa rozbieżności co w rundzie 10. Wykonuję formę nowszą (z wykluczeniem
`.gitleaks.toml`) i zgłaszam różnicę: **ani jednej linii kodu** ponad `bbc8167`
(`backend/`, `skrypty/` nietknięte). Zgodne z deklaracją architekta. **Nie znalezisko.**

### Wąskość rozszerzonego D-5 — zweryfikowana POMIAREM jako commit-scoped

Kryteria wąskości z uzupełnienia architekta, każde zmierzone (klon `klon-head`
na czubku `f8f64c0`):

```
[A] skan HEAD:                                   159 commits scanned, no leaks found   (wartość zwolniona w 527f1b7 + 661e8a6)
[C] DOKŁADNIE zwolniona wartość w NOWYM commicie: leaks found: 1                        (commit-scoped — zapala poza dwoma SHA)
[INSTRUMENT] INNA wysokoentropijna wartość, nowy commit: leaks found: 1                 (skaner żyje)
```

Weryfikacja tekstu wpisu D-5 na `f8f64c0`:

- `targetRules = ["generic-api-key"]` — **jedna reguła**;
- `condition = "AND"` — wszystkie kryteria naraz;
- `regexes` — **jedna wartość**: przedrostek `GOCSPX-` plus 32 znaki
  szesnastkowe (skrócone przy commicie — patrz uwaga niżej);
- `commits = [527f1b7e35585a6e6ffd01570fddf4e939b9eb2d, 661e8a66b4980d70f93421f688110f20382734dd]`
  — **DWA, oba pełne 40-znakowe SHA** (nie skróty; lekcja R7-5). `661e8a6` rozwija się
  do dokładnie tego SHA (`git rev-parse`);
- warunek znoszący z terminem O-2b OBECNY (wiąże D-5 z D-4, „jeśli O-1..O-3 nie usunie
  OBU — to ZNALEZISKO");
- pełna wartość `GOCSPX-…` **NIE występuje w `docs/`** na HEAD (`grep -rc` → 0 —
  potwierdzam twierdzenie architekta); w `docs/` są tylko formy SKRÓCONE
  (`GOCSPX-9f2b…c07`), dozwolone.

> ⛔ **SKRÓCONE PRZY COMMICIE — sesja KOD-F1, 19.08.** Ten raport cytował
> zwolnioną wartość w CAŁOŚCI, weryfikując wąskość wpisu D-5. Cytat sekretu
> nie przestaje być cytatem sekretu przez to, że stoi w zdaniu o jego
> kontroli — ta sama klasa, która zapaliła krok [21] w `527f1b7` i `661e8a6`.
> Wartość dowodowa akapitu nie ucierpiała: dowodem jest RÓŻNICA między
> przebiegami ([A] `no leaks` na HEAD, [C] `leaks found: 1` w nowym commicie),
> a nie pełny ciąg znaków. Nie podmieniam po cichu — stąd ta uwaga.

Świadomie NIE utworzono D-6 (rozszerzenie istniejącego wpisu) — spójne z „jeden wpis
na jedną wartość". **Wyjątek NIE jest szerszy, niż architekt deklaruje. Nie znalezisko.**

### Krok [21] na `bbc8167` — ZIELONY

Na czystym `bbc8167` z przyciętymi refami krok [21] gitleaks jest zielony
(151 commitów, `no leaks found`) — dwa niezależne przebiegi bramki (klon-a, klon-b).
Nie ma czerwieni [21] na samym `bbc8167`. **Nie znalezisko.**

---

## 6. Odrzucone po pomiarze — NIE są znaleziskami

- **`Kod::funkcje()` liczy klamry (trop autora z mapy).** Atrybucja do FUNKCJI
  broni się: test `KIERUNEK ODWROTNY: atrybucja do FUNKCJI` zielony, a przy R11-1/R11-2
  parser poprawnie umiejscawiał mechanizm w `powrot`/`stanKonta`. Nie znalazłem rozjazdu
  parsera z gramatyką w zakresie mierzonym. (Osobno NIE fuzzowałem parsera — §8.)
- **`zaktualizuj` przez `Reflection`/`unserialize` (R6A-3 otwarte).** Warunek
  utrzymujący `WaskieGardloTozsamosciTest` („kod produkcyjny nie używa Reflection ani
  unserialize") broni się — na czystym drzewie zbiór pusty. R11-2 NIE korzysta z tej
  ścieżki (używa sankcjonowanego `zPodmienionymi`), więc to inny, nowy wektor.
- **`SekretyTest` — lista wartości nietajnych rośnie (trop autora).** Przejrzałem;
  ciężar dowodu odwrócony (każde przypisanie puste albo jawnie na liście),
  `wygladaJakSekret` łapie kształt sekretu nawet po dopisaniu do listy. Osobnej mutacji
  „wartość o kształcie sekretu dopisana do listy nietajnych" NIE odtwarzałem poza tym,
  co robi perturbacja `p_sekrety` (§8).
- **Kolizja etykiety „D-5"** w `LISTA-SCALENIOWA-F1.md` (odnotowana w rundzie 10) —
  dokument ponad `bbc8167`, nie mierzyłem ponownie.

---

## 7. Sprzeczne polecenia i koszt cofnięcia (pozycja stała WYTYCZNE)

Sprzeczność: **dwie formy warunku zamrożenia** — `ZLECENIE-075` body (bez wykluczenia
`.gitleaks.toml`) vs uzupełnienie architekta w kanale (z wykluczeniem). Co wykonane:
mierzyłem obiekt `bbc8167` (nietknięty przez obie formy), zgłaszam różnicę (§5).
Koszt cofnięcia: **zero** — D-5 nie istnieje na moim obiekcie `bbc8167`. Poza tym: brak.

---

## 8. CZEGO NIE SPRAWDZIŁEM (sekcja obowiązkowa)

1. **R11-2 przez pełne jądro HTTP z prawdziwym ciasteczkiem sesji** — dowód runtime
   zrobiłem wywołując `OdswiezanieSesji::stanKonta` bezpośrednio z sesją pod rękę;
   pełnej ścieżki `/auth/ja` przez nginx+php-fpm z roundtripem ciasteczka NIE
   odtwarzałem (izolacja sesji przez `setLaravelSession`, nie przez kontener web).
2. **Pozostałe warianty R11-1** (właściwość dynamiczna `$request->code` jako źródło
   zmiennej, `state` zamiast `code`, dwa przypisania pośrednie) — zmierzyłem `code`
   przez `query()`; należą do tej samej klasy, ale ich osobno NIE mierzyłem.
3. **`p_callback_*` w NOWYM commicie / poza suitą** — perturbacje zapaliły się
   w pełnym zestawie z `--przyczyna`; nie wstrzykiwałem ich w nowy commit.
4. **D-4 przez PRZYNĘTĘ w nowym commicie** (base64 `hello-world-…` poza czwórką
   zwolnionych) — sprawdziłem D-4 statycznie (wpis obecny, `AND`, cztery pełne SHA);
   przynęty NIE wstrzykiwałem. (D-5 sprawdziłem dynamicznie — §5.)
5. **Fuzzing parsera `Kod::funkcje()`** (zagnieżdżone domknięcia, `match`, atrybuty,
   heredoc z klamrami) — atrybucję sprawdziłem tylko na mechanizmach R11-1/R11-2
   i teście `atrybucja do FUNKCJI`; nie łamałem parsera osobno.
6. **`TwierdzeniaKomentarzyTest`** (D-3) — poza bramką; potwierdziłem tylko, że to on
   daje `2 skipped`, nie uruchamiałem go osobno.
7. **`odczyt-przyczyn.py` / tryb DYNAMICZNY allowlist** — zapadka statyczna zielona
   w suicie; trybu dynamicznego nie odtwarzałem (ta sama pozycja co rundy 8–10).
8. **Merytoryka retencji F1** — perturbacje `retencja`, `retencja_wykonanie` zapalają
   w pełnym zestawie; poza tym mutacjami nie sondowałem.
9. **Współbieżność** (`CLAUDE.md` §6, 100 równoczesnych żądań) — poza suitą, zakres F3.
10. **CI (GitHub Actions)** — nie uruchamiałem; bramka mierzona wyłącznie lokalnie.
11. **Kontrakty wobec `konta/`, `hub/`, `helpdesk/`** — poza zasięgiem rundy.
12. **`WalidatorTokenu`, `KontaOidc`, `Bramki`** — czytałem strukturę przy R11-1/R11-2,
    ale nie sondowałem samej walidacji tokenu (podpis/issuer/aud/nonce) mutacjami.

---

## 9. Zakres pokryty — dla jawności (żeby dało się go zakwestionować)

Zmierzone: pełna bramka OD ZERA (22 kroki, 304/2211 RÓWNO z podłogami, kod 0,
`no leaks found`, 151 commitów po przycięciu refów) na czystym klonie `bbc8167`;
drugi niezależny przebieg na drugim klonie/projekcie/portach; PEŁNY zestaw
perturbacji (54 kontrole / 37 scenariuszy / 0 pominiętych, kod 0), policzony
niezależnie (37 nagłówków, 54 ✓, 0 ✗); wszystkie zamknięcia rundy 10 (R10-1 warstwa 3
z krytyczną kontrolą przyrządu `code` tablicą, WARSTWA 4 z kontrolą pozytywną, wady
własne §5(a)/(b), kotwica §6, podłogi RÓWNO) z kontrolą pozytywną I negatywną;
warunek zamrożenia sprawdzony dwustronnie; wąskość rozszerzonego D-5 zweryfikowana
pomiarem (commit-scoped, dwa pełne SHA, wartość nieobecna w `docs/`); TRZY znaleziska
(R11-1 z żywym mechanizmem i dowodem skutku przez jądro HTTP w trzech wariantach,
odtworzone niezależnie przez świeżego subagenta bez rozbieżności; R11-2 z dowodem
runtime podmiany tożsamości; R11-3 z negatywną kontrolą, która nie zapala).

**Czego zakres NIE obejmuje** — sekcja §8 powyżej (12 pozycji). W szczególności:
merytoryki walidacji tokenu, fuzzingu parsera, trybu dynamicznego allowlist,
współbieżności F3 i CI.

**Zbieżność rund:** 11 → 15 → 12 → 29 → 9 → 2 → 5 → 1 → **3**.

**Faza F1/F0 pozostaje OTWARTA — trzy znaleziska. Runda nie kończy się zerem.**
Fazę zamyka wyłącznie runda z zerem znalezisk (D-2026-08-07-16) — kryterium nie łagodzę.
Dwa znaleziska (R11-1, R11-2) leżą w tym samym wąskim gardle tożsamości, każde o krok
dalej niż poprzednie rundy i każde z osobną przyczyną oraz osobną naprawą; trzecie
(R11-3) to nawrót klasy R9-5 wpuszczony przez zmianę §6.
