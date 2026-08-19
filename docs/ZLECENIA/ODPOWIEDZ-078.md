# ODPOWIEDZ-078 — cykl naprawczy rundy 11 zamknięty

**Od:** sesja KOD-F1 · **Do:** architekt · **Data:** 19.08.2026
**W odpowiedzi na:** `ZLECENIE-078` (R11-1/R11-2 — stan nielegalny NIEWYRAŻALNY; R11-3 — kotwica sprawdzalna)
**SHA zamrożone (KOD):** `6b5881c375521a8c79f851c846b39cd2c242d11e` („6b5881c")

---

## 1. Surowe wyjście — bramka OD ZERA, PO commicie zamrażającym

```
    WYKONANO 315 testów (podłoga: 315)
    sprawdzono 2241 asercji (podłoga: 2241)
8:23AM INF 160 commits scanned.
8:23AM INF no leaks found
BRAMKA OK — 22 kroków, 0 nieudanych
KOD_BRAMKI=0
```

Perturbacje, pełny zestaw:

```
PERTURBACJE OK — 59 kontroli udowodniło, że umie zaświecić czerwono (pominięte: 0)
KOD_PERTURBACJI=0
zmierzone niezależnie: 42 nagłówki „=== PERTURBACJA", 59 znaków ✓, 0 znaków ✗
```

Podłogi **315 / 2241 — RÓWNO**, bez zapasu.

---

## 2. Zakres, o który pytałeś w §3 — ZMIERZONY PRZED ROBOTĄ, nie po

Kazałeś zgłosić, jeśli wymóg typu ruszy większą powierzchnię, niż zakładasz.
Zmierzyłem ją, zanim zacząłem, i **mieści się w Twoim założeniu** — dlatego nie
przerywałem:

```
pisarze tożsamości:            zaloz  ← 1 wołacz (LogowanieController::powrot)
                               zaktualizuj ← 1 wołacz (OdswiezanieSesji::przelicz)
wołacze WalidatorTokenu::sprawdz: 5, z tego 3 dotyczą tożsamości sesji
                               (2 w powrot, 1 w przelicz); SprawdzKonta
                               i BackchannelLogoutController weryfikują tokeny
                               do INNYCH celów i zostały NIETKNIĘTE
pacjencka ścieżka logowania kodem: NIE ISTNIEJE w kodzie (konta pacjentów
                               i guest checkout to późniejsze fazy)
```

Obawa z Twojego §3 się nie zmaterializowała.

---

## 3. R11-1 i R11-2 — jedna naprawa, bo to była jedna wada

### Dlaczego NIE piąta warstwa

Zgadzam się z diagnozą §0 i mam do niej własny dowód: piąte piętro **sam
wystawiłem do obalenia** w `ODPOWIEDZ-074` §9 i zostało obalone w tej samej
rundzie. Kontrola rozpoznająca KSZTAŁT kodu ma brzeg; brzeg da się przekroczyć.

### Co powstało

**`App\Tozsamosc\RoszczeniaZweryfikowane`** — obiekt wartości, którego jedyna
droga powstania prowadzi przez sprawdzenie podpisu:

- konstruktor **prywatny**;
- **jedyne** `new self` w całym kodzie stoi w `zTokenu()`, ZA sprawdzeniem `ok`;
- `zTokenu()` woła `WalidatorTokenu::sprawdz` i przy `ok === false` zwraca
  `roszczenia => null`, zachowując szczegół odmowy (`kontrole`, `nieudane`),
  bo ścieżka logowania musi umieć powiedzieć, CO odpadło;
- zwracany kształt jest **unią dwóch wariantów** (`ok: true` z obiektem,
  `ok: false` z `null`), więc statyka zwęża typ po sprawdzeniu `ok` — bez
  ręcznych asercji u wołającego. To nie jest ozdoba: dzięki temu PHPStan
  potrafi POWIEDZIEĆ, że wektor R11-1 jest błędem (patrz §4).

**Skutki dla obu znalezisk:**

```
R11-1  SesjaKonta::zaloz(Request, RoszczeniaZweryfikowane $id,
                         RoszczeniaZweryfikowane $access, ?string $refresh)
       → `['sub' => $request->query('code')]` nie jest tym typem.
         Nie ma znaczenia, czy pole jest w kontrakcie OIDC.

R11-2  TozsamoscSesji::zPodmienionymi(array) USUNIĘTE.
       Zastąpione `zOdswiezonymi(RoszczeniaZweryfikowane, ?string)`, które
       NIE MA parametru zdolnego ruszyć tożsamość: `sub`, `sid` i dane osoby
       pochodzą z `$this`, czyli z tożsamości, która JUŻ była w magazynie.
```

Mapowanie roszczeń na zawartość sesji przeniosłem z kontrolera do **fasady** —
w kontrolerze stała tablica, a tablicę wolno wypełnić czymkolwiek.

### Rozstrzygnięcie, które zmieniłem w trakcie — i dlaczego

Pierwsza wersja dawała `TozsamoscSesji` publiczną fabrykę statyczną
`zRoszczen(..., string $idTokenSurowy, ?string $refresh)`. **Zapaliła istniejący
strażnik** `WaskieGardloTozsamosciTest`: publiczna fabryka tożsamości ma
allowlistę przyjmowanych typów i jest w niej dziś JEDEN typ (`Request`).
Dopisanie `string` do tej allowlisty naprawiłoby R11-1 kosztem **trwałego
osłabienia strażnika R6A-3**.

Przeprojektowałem: fabryki statycznej nie ma wcale, mapowanie mieszka
u pisarza, a surowy token niesie **sam obiekt roszczeń** (`tokenSurowy()`).
To okazało się mocniejsze niż pierwotny plan — zapisany w sesji `id_token_hint`
jest teraz **z definicji** tym tokenem, którego podpis sprawdziliśmy; wcześniej
kontroler niósł go osobnym parametrem i nic nie wiązało go z wynikiem walidacji.

**Allowlista typów została NIETKNIĘTA.** Naprawa, która psuje inną kontrolę,
nie jest naprawą.

---

## 4. Kontrole odbioru z Twojego §1 — wszystkie cztery, każda zmierzona

### §1.1 — oba wektory rundy 11 DOSŁOWNIE: mają rzucać, nie „zapalać kontrolę"

Warunek brzmiał „przestać przechodzić statykę **albo** rzucać". **Spełniony
obiema drogami naraz**, i drugą zmierzyłem przypadkiem — pisząc test.

Statyka (Larastan level max, to samo drzewo), gdy wektor R11-1 zapisać WPROST:

```
Parameter #2 $id of callable array{SesjaKonta, 'zaloz'} expects
RoszczeniaZweryfikowane, array<string, (array|string|null)> given.
```

a dla R11-2:

```
Call to function method_exists() with 'TozsamoscSesji' and 'zPodmienionymi'
will always evaluate to false.
```

Statyka **wie**, że metody nie ma. Dlatego w teście oba wektory wołam przez
refleksję — nie dla wygody, tylko dlatego, że zapisane wprost nie przechodzą
typowania, a bramka nie odróżnia dowodu od błędu.

Pomiar drogi drugiej (pominięcie statyki):

```
R11-1  ReflectionMethod(SesjaKonta,'zaloz')->invokeArgs(null,
       [$zadanie, ['sub' => $request->query('code'), 'role' => ['pacjent']], null, null])
       → TypeError   ·   w sesji tożsamości NIE MA
R11-2  $tozsamosc->{'zPodmienionymi'}(['sub' => 'VICTIM-KOORDYNATOR'])
       → Error       ·   `sub` w sesji NIETKNIĘTY (ATAKUJACY-PACJENT)
```

Sprawdzam **skutek, nie sam wyjątek**: gdyby zapis częściowo przeszedł,
„nie da się" znaczyłoby tylko „trudniej".

### §1.2 — konstrukcja obiektu wartości jest niepodrabialna

Jedyna kontrola strukturalna, jaka zostaje, i dotyczy JEDNEJ klasy:

- konstruktor prywatny (refleksja);
- **jedyne `new` w pliku klasy stoi w `zTokenu`** — zmierzone parserem
  (`Kod::funkcje`), nie wyrażeniem regularnym, wynik `['zTokenu']`;
- `zTokenu` **musi** wołać `WalidatorTokenu::sprawdz` — inaczej obiekt
  nazywałby się „zweryfikowany", nie będąc nim;
- żadna publiczna metoda oddająca obiekt nie przyjmuje `array`;
- poza klasą nikt nie konstruuje obiektu — **z kontrolą przyrządu**: wzorzec
  skanera musi trafiać we wzorcowe wywołanie, inaczej pusta lista znaczyłaby
  „nie umiem szukać", a nie „nie ma" (lekcja z martwego literału `'\$_GET'`).

### §1.3 — pozytywna: legalny przepływ działa

```
callback → walidator → obiekt → zaloz  →  sub=sub-abc-123, sid=sid-abc-123,
                                          role zawiera „psycholog"
odświeżenie → nowe role, TEN SAM sub
```

Bez tej kontroli §1.1 spełniałby także system, w którym nikt się nie zaloguje.

### §1.4 — ZASIĘG: wszystkie metody zmieniające tożsamość

R11-2 wzięło się stąd, że skanowaliśmy JEDNĄ NAZWĘ. Dlatego kontrola nie ma
listy nazw: **parsuje `SesjaKonta`, pyta które metody piszą klucz tożsamości**,
i dopiero potem sprawdza ich typy.

| metoda | co robi | wejście |
|---|---|---|
| `zaloz` | USTANAWIA | `Request` + 2× `RoszczeniaZweryfikowane` + `?string` |
| `zaktualizuj` | AKTUALIZUJE | `Request` + `TozsamoscSesji` |
| `zakoncz`, `wyloguj` | KASUJĄ | `Request` — nie ustanawiają niczego |

Typy dopuszczone: `Request`, `RoszczeniaZweryfikowane`, `TozsamoscSesji`,
`?string`. **`array` NIE jest dopuszczona** i jest osobna kontrola przyrządu,
która tego pilnuje — bez niej cała kontrola przechodziłaby nad naprawianym
znaleziskiem.

`?string` dopuszczam ŚWIADOMIE i wyłącznie dla refresh tokenu: to poświadczenie
do IdP, nie tożsamość. Podanie tam bzdury psuje odświeżanie, ale nie zmienia,
KIM jest zalogowany. **To jest nazwana granica, nie przeoczenie.**

### §1.5 — warstwy 1–4 zostają jako druga linia

Zostają i tak są opisane w nagłówkach obu plików. `WaskieGardloZapisuTozsamosciTest`
dalej pilnuje, GDZIE stoi zapis, SKĄD go wołano i CO callback czyta z żądania.
Zmienia się to, że **nie są już jedyną obroną**.

---

## 5. R11-3 — kotwica UMIEJSCAWIA pomiar, nie zwalnia z niego

Moja poprawka z `ODPOWIEDZ-074` §6 rozwiązywała prawdziwy problem (zdanie
o przeszłości nie ma się starzeć), ale rozwiązała go **zwolnieniem** — `continue`
na linii z kotwicą. Weryfikator wpisał fałszywą liczbę i przeszła.

Teraz kontrola wyciąga SHA i czyta `perturbacje.sh` **z tamtego commita**:

```
POZYTYWNA:  wszystkie zakotwiczone liczby prawdziwe wobec swoich SHA → 10 passed
NEGATYWNA:  „999 scenariuszy — zmierzone na `528adc3`" →
            „zdanie mówi 999 scenariuszy „zmierzone na `528adc3`",
             a w tamtym commicie było 35"
PRZYRZĄDU:  brak zakotwiczonych zdań = błąd („kontrola mierzy pustkę")
```

Perturbacja stała `kotwica` odtwarza wektor weryfikatora dosłownie.

---

## 6. Trzy wady WŁASNE, znalezione przy tej naprawie

**(1) Detektor połkniętych komunikatów był ślepy na ich najczęstszy kształt.**
Kontrola z rundy 7 żądała, żeby komunikat był POJEDYNCZYM literałem — a w tym
repozytorium większość dłuższych komunikatów jest **sklejana** z kilku, bo linia
się nie mieści. Napisałem `->toContain(igła, komunikat)` w formie sklejanej
i kontrola milczała.

To ta sama klasa, którą naprawiam w tożsamości: **kontrola rozpoznawała JEDEN
KSZTAŁT przedmiotu zamiast pytać o jego istotę.** Istotą jest „ostatni argument
jest ZDANIEM", nie „ostatni argument jest jednym tokenem".

Po rozszerzeniu przeskanowałem całe `tests/`: trafienie **jedno — moje**. Czyli
innych ukrytych połkniętych komunikatów tego kształtu w repozytorium nie było.
Kontrola odwrotna dostała dwa nowe przypadki: sklejenie z literałów (ma zapalać)
i sklejenie ze ZMIENNĄ (nie ma — to wartość, nie zdanie).

**(2) Kontrola skryptów brała PIERWSZE `p_` w linii.**
`skrypty-uruchamialne.sh` wydobywał nazwę procedury przez `grep -o 'p_[a-z_]+' |
head -1`, więc dla scenariusza `typ_zaloz` znajdował `p_zaloz` **wewnątrz samej
nazwy** (t-y-**p_z**-aloz) i meldował „wskazuje na p_zaloz(), której nie ma" —
oskarżenie prawdziwe co do czerwieni, całkowicie fałszywe co do adresu.

Nazwy scenariusza NIE zmieniłem, choć zmiana uciszyłaby objaw: wadą jest parser.
Zostaje jako trwały przypadek regresyjny.

**(3) Potok przesłonił kod wyjścia bramki.**
Pierwszy przebieg puściłem jako `bramka.sh 2>&1 | tail -60`. Dostałem
`exit code 0` — **kod `tail`, nie bramki**; w wyjściu stało
`BRAMKA CZERWONA — 2 nieudanych kroków z 22`. Gdybym patrzył wyłącznie na kod,
zameldowałbym zieleń nad czerwienią.

Zmiana procedury, nie obietnica: **bramkę uruchamiam bez potoku maskującego
status** — wyjście do pliku, kod odczytany wprost. Tak zmierzone są wszystkie
liczby w §1.

---

## 7. KROK DALEJ — szóste piętro ISTNIEJE. Zmierzone, NIENAPRAWIONE, pytam o zakres

Ściana typu zamyka „skąd pochodzi WARTOŚĆ tożsamości". Nie zamyka **skąd
pochodzą WYMAGANIA WALIDACJI**.

`RoszczeniaZweryfikowane::zTokenu(string $jwt, array $wymagania)` przyjmuje
wymagania TABLICĄ — a w niej `jwks`, czyli materiał klucza. Kod, który poda
własny `jwks`, dostanie obiekt **całkowicie legalnie**: walidator powie `ok`,
bo podpis przeszedł — tylko przeciw kluczowi napastnika.

**Zmierzone** (mechanizm w `powrot()`, podmienia `$jwks` z pliku, z bezpiecznym
odwrotem, żeby nie mierzyć własnej awarii):

```
kontrole tożsamości (3 pliki, 24 testy)  →  24 passed   — MILCZĄ
PEŁNA suita                              →  315 passed (2241) — jak bazowo
Larastan level max                       →  No errors
Pint                                     →  PASS 106 files
```

Typ nie kłamie. Kłamie **źródło wymagań**.

**Czego NIE zrobiłem i dlaczego pytam.** Naprawa ma kształt: wymagania mają
pochodzić z konfiguracji (`KontaOidc`), nie być składane przez wołającego —
czyli `zTokenu` przestaje przyjmować `jwks`, a bierze je samo. To rusza:

- kontrakt `WalidatorTokenu` albo nowe wejście obok niego,
- `LogowanieController::powrot` (2 miejsca) i `OdswiezanieSesji::przelicz`,
- **moje własne pomocniki testowe**, które dziś budują wymagania ręcznie;
  po zamknięciu tej drogi obiekt roszczeń dałoby się zdobyć w teście wyłącznie
  przez pełną ścieżkę HTTP z podstawionym IdP.

To jest dokładnie ta sytuacja, którą opisałeś w §3: powierzchnia większa, niż
zakładaliśmy. Zgodnie z Twoim poleceniem **zgłaszam zamiast zastawiać Cię
faktem** — rozstrzygnij zakres.

Na czystym drzewie **nie jest to eksploatowalne** (takiego kodu tam nie ma) —
ten sam status, jaki miały R11-1 i R11-2 przed naprawą.

---

## 8. Stan długów

| dług | stan | termin |
|---|---|---|
| **D-3** | `TwierdzeniaKomentarzyTest` poza bramką (2 pominięte) | — |
| **D-4** | wyjątek gitleaks na przynętę w `perturbuj.py` | O-2/O-3 |
| **D-5** | wyjątek gitleaks na cytat sekretu (dwa commity, jeden wpis) | O-2b |
| **O-6c** | kontrola kształtu wartości w `docs/` (przyjęta, niewykonana) | okno scaleniowe |

Nowych długów nie zaciągam. Szóste piętro (§7) **nie jest długiem** — jest
znaleziskiem czekającym na Twoje rozstrzygnięcie zakresu.

---

## 9. Warunek zamrożenia

```
git diff --stat 6b5881c..HEAD -- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md'
   (pusto)

git status --porcelain -- . ':(exclude)docs/'
   (pusto)
```

Zamrożenie jest na czubku, więc warunek jest pusty z definicji — wklejam mimo to,
bo dwa razy w tej serii „oczywiste" okazało się nieprawdziwe.

**Niezacommitowane zostaje `docs/DECYZJE.md`** i to jest do Twojego rozstrzygnięcia:
przygotowałem wpis `D-2026-08-19-01` o zmianie rodzaju obrony, ale **strażnik
commita odmówił** — plik jest poza `.zakres-sesji` mojej sesji i niesie oczekujący
wpis sesji SPEC-UMOWA (`D-2026-08-12-04`, termin zwrotu; przeczytałem go w całości).
Nie obszedłem strażnika przez `GABINET_STRAZNIK=0` — powstał dokładnie po to, żeby
`git add` nie wciągał cudzych plików, i 12.08 już raz to złapał. Treść wpisu jest
w drzewie roboczym; rozstrzygnij, czy rozszerzyć zakres, czy ma go zacommitować
inna sesja.

---

## 10. Gotowość do rundy 12 — gdzie sam bym uderzył

- **§7 — źródło wymagań walidacji.** Najkrótsza droga, zmierzona, opisana.
- **`?string $refreshToken` u pisarza tożsamości** (§1.4). Twierdzę, że refresh
  token nie rozstrzyga o tożsamości; **to twierdzenie podlega obaleniu** — jeśli
  da się nim uzyskać sesję innego podmiotu, granica jest źle postawiona.
- **`RoszczeniaZweryfikowane::wszystkie()`** wydaje tablicę roszczeń do
  `Bramki`. Twierdzę, że to nie osłabia typu, bo tablica jest WYNIKIEM
  weryfikacji, a nie drogą do jej ominięcia — też do obalenia.
- **Kontrola „jedyne `new` w klasie"** liczy tokeny `T_NEW` w JEDNYM pliku.
  Klasa potomna albo `new` w innym pliku po zdjęciu prywatności to dwa różne
  wektory; drugi ma osobną kontrolę, pierwszego nie sprawdzałem (klasa jest
  `final`, ale tego nie mierzę osobno).

---

## 11. Jedna rzecz, którą zrobiłem inaczej niż zwykle — i dlaczego warto o niej wiedzieć

Przed commitem **przeskanowałem pliki kanału pod kątem kształtu wartości**, zamiast
zaufać, że skoro nie dotykam sekretów, to ich tam nie ma.

`RUNDA-11-RAPORT.md` cytował zwolnioną wartość **w całości** — weryfikator zrobił to
sprawdzając wąskość wpisu D-5, czyli w najbardziej niewinnym możliwym kontekście.
Zacommitowany bez zmian zapaliłby krok [21] **po raz trzeci**.

Skróciłem, zostawiając w raporcie widoczną uwagę o tym, że skróciłem — nie podmieniam
cudzych dokumentów po cichu.

To jest dokładnie ta kontrola, którą sam zaproponowałeś jako `O-6c` i przesunąłeś na
okno scaleniowe. **Nie wprowadzam jej teraz** — zgodnie z Twoją decyzją i z powodem,
który wtedy podałeś (dokładanie kontroli w trakcie serii rund przesuwa metę). Zrobiłem
ją ręcznie, jako czynność, a nie jako nową bramkę. Odnotowuję, żeby było jasne, że
**tym razem klasa została złapana przed commitem, a nie po** — i że stało się to
dzięki procedurze, nie dzięki szczęściu.
