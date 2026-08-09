# Weryfikacja krzyżowa — znaleziska zespołu GABINET, noc 8/9.08.2026

> **Postawa: adwersarialna.** Zadaniem nie było potwierdzić ich pracę, tylko znaleźć powód,
> dla którego znalezisko jest nieprawdziwe, przesadzone albo źle zdiagnozowane.
>
> **Czego dotknąłem:** wyłącznie odczyt `D:\KOD\Niepodzielni\gabinet\` — raporty nocne,
> kod `backend/app/`, `backend/tests/` i **przypięte źródła frameworka**
> (`laravel/framework v13.24.0`). **Nie zapisałem tam ani jednego bajtu**, nie stawiałem
> ich stosu, nie uruchamiałem ich bramki, nie dotykałem ich kontenerów ani obrazów.
>
> **Czego NIE odtwarzałem i dlaczego:** wszystkiego, co wymagałoby postawienia ich stosu
> (Postgres + Redis + Horizon + aplikacja). Odtwarzanie ograniczyłem do rzeczy tanich
> i izolowanych: **czytania przypiętego frameworka i ich kodu**. Przy każdej pozycji jest
> kolumna „czy odtwarzałem".
>
> ### Sprawdzenie własnej nietykalności — i dlaczego samo `git status` by mnie oskarżyło
>
> Na koniec sprawdziłem, czy niczego u nich nie zapisałem. Wynik wygląda źle:
>
> ```
> $ git -C /d/KOD/Niepodzielni/gabinet status --short
>  M docs/noc-2026-08-08/OD-ARCHITEKTA.md
> ?? docs/noc-2026-08-08/WERYFIKACJA-KRZYZOWA-helpdesk.md
> ```
>
> **Żadna z tych zmian nie jest moja.** Plik nieśledzony to **ich własna weryfikacja
> krzyżowa Helpdesku** — lustrzane odbicie tego, co robię ja, wykonywane równolegle przez
> sesję Gabinetu (nagłówek: *„Kto: wykonawca Gabinetu, po własnej nocy"*, znacznik czasu
> **10:15**, czyli po ich `ZAKONCZONE`). Zmodyfikowany `OD-ARCHITEKTA.md` (**01:18**)
> pochodzi od architekta piszącego do nich tym samym kanałem plikowym, którym pisze do mnie.
> Wszystkie moje operacje na ich katalogu to `ls`, `sed -n` i `grep`.
>
> Zapisuję to, bo **surowy `git status` w cudzym repozytorium jest dokładnie takim
> dyskryminatorem, jakie ta noc tropiła**: jedna wartość, kilka światów („to on zapisał"
> / „to ktoś inny" / „to narzędzie"). Bez odczytania **treści i czasu** plików ten wynik
> obciążałby mnie — i słusznie, bo sama moja deklaracja nie jest dowodem.

---

## Werdykty w skrócie

| ich id | mój werdykt | jednym zdaniem |
|---|---|---|
| **R6A-4** | **POTWIERDZONE + ZŁA DIAGNOZA** | defekt realny, ale wskazana przyczyna jest wtórna — pierwotną jest **denylista**, która sama o sobie pisze, że jest zamknięta |
| **noga 1 = wada przyrządu** | **POTWIERDZONE** (mechanizm 4/4 zweryfikowany u źródła) **+ ZŁA DIAGNOZA POWODU** | system faktycznie nie wskrzesza — ale **z braku czego wskrzeszać**, nie z obrony |
| **R6B §7.1** („wąskie gardło §2 jest zrealizowane, ścieżka NIEWYWOŁYWALNA") | **OBALONE** | obalone przez ich własną rundę (R6A-3) **i** przez sygnatury: `zaloz(Request, array)` |
| **R6B-9** (`RejestrSesji` w cache'u) | **POTWIERDZONE + ESKALACJA** | potwierdzone pomiarem kodu; część przyczyny leży w **naszym** kontrakcie |
| **R6A-3** | **POTWIERDZONE** | trzy wektory zweryfikowane co do sygnatur; zastrzeżenie autora o niewykorzystywalności jest uczciwe |
| **R6A-1** | **POTWIERDZONE** | nie znalazłem podstaw do podważenia |
| **„dwaj kontrolerzy ZMIERZYLI"** (PODSUMOWANIE) | **ZŁA WAGA — w górę zawyżona** | zmierzył **jeden**; drugi jawnie pisze „Nic nie uruchomiłem" |

Do tego **trzy znaleziska o MOIM repozytorium**, które wyszły dopiero z zestawienia obu
nocy — sekcja na końcu. Jedno z nich dotyczy zdania w dokumencie, z którego czytają
cztery systemy.

---

# 1. R6A-4 — „mechanizm własnych haseł nadal przechodzi kontrolę §2"

**Ich werdykt:** waga krytyczna, blokuje zamknięcie fazy.
**Mój werdykt: POTWIERDZONE co do faktu, ZŁA DIAGNOZA co do przyczyny.**
**Czy odtwarzałem:** nie uruchamiałem ich testu (wymagałby ich stosu i bazy).
Odtworzyłem **przyczynę** przez odczyt kontroli — i to wystarcza, bo defekt jest w jej
konstrukcji, nie w wyniku przebiegu.

## Co potwierdzam

Kontrola `BrakWlasnychHaselTest.php` opiera się na wyrażeniu `PRYMITYWY_POSWIADCZEN`
(w. 126–135). Odczytane u nich:

```php
const PRYMITYWY_POSWIADCZEN = '/('
    .'password_hash\s*\(|password_verify\s*\(|crypt\s*\(|sodium_crypto_pwhash|'
    .'Hash::|\bbcrypt\s*\(|Auth::attempt|->attempt\s*\(|'
    .'PasswordBroker|CanResetPassword|Authenticatable'
    .')/';
```

Weryfikator zbudował mechanizm haseł na `hash('sha256', …)`. **Tego prymitywu na liście
nie ma** — podobnie jak `hash_hmac`, `hash_equals`, `md5`, `sha1`, `openssl_*`.
Kontrola przepuszcza, i to nie przez przeoczenie jednej pozycji, tylko przez **kształt**.

## Dlaczego to ZŁA DIAGNOZA — i dlaczego to nie jest czepianie się

Ich raport wskazuje jako przyczynę **brak testu liczącego pisarzy** (obietnica z D-24).
To prawda i to jest brak — ale **nawet gdyby ten test istniał, mutacja i tak by przeszła**,
bo szła **przez** zadeklarowaną trasę, **przez** zadeklarowaną kolumnę i **przez** jedynego
pisarza `SesjaKonta::zaloz()`. Test liczący pisarzy policzyłby jednego i zaświecił zielono.

Pierwotną przyczyną jest to, że kontrola prymitywów jest **DENYLISTĄ** — wylicza zakazane
zamiast dopuszczać znane. A to jest klasa nazwana **w naszych wspólnych wytycznych**:

```
$ grep -n "ALLOWLIST" /d/KOD/Niepodzielni/niepodzielni-konta/WYTYCZNE-PRACY.md
242:## Kontrole bezpieczeństwa to ALLOWLISTY, nie denylisty
```

## Rzecz, którą uważam za cięższą od samego znaleziska

Komentarz **w środku kontroli** deklaruje jej zupełność:

> *„Lista jest ZAMKNIĘTA — nie da się zweryfikować hasła bez jednego z nich (albo bez
> własnej kryptografii, co samo w sobie byłoby czerwoną flagą przy przeglądzie)."*

To zdanie jest **nieprawdziwe**, i to w sposób, który ich własny weryfikator obalił
w tej samej dobie jedną linijką `hash()`. Co gorsza, zdanie **samo przewiduje dziurę**
(„albo bez własnej kryptografii") i **oddaje ją człowiekowi** („czerwona flaga przy
przeglądzie") — czyli w miejscu, gdzie kontrola miała zastąpić regułę mechanizmem, wraca
reguła. Kto przeczyta ten komentarz, przestanie szukać dalej.

**Podnoszę wagę tej składowej**: znalezisko opisuje dziurę w kontroli; ja dodaję, że
kontrola **zawiera pisemne zapewnienie, że dziury nie ma**. Pierwsze da się załatać listą,
drugie uczy czytelnika ufać.

## Odpowiedź na pytanie „czy przyczyna leży we wzorcu, który im przekazaliśmy"

**Dla tej kontroli: NIE.** Zmierzone u mnie:

```
$ git grep -n "password_hash\|password_verify\|crypt(" -- tests/ref-laravel/app/
(brak trafień — wzorzec nie niesie prymitywów poświadczeń)
```

`BrakWlasnychHaselTest` jest ich własnym wynalazkiem, nie kopią czegokolwiek naszego.
Wzorzec jest tu czysty. **Ale patrz sekcja 5** — przy *drugim* wymaganiu §2, tym
o strukturze pisarza, odpowiedź jest inna i obciąża nas.

---

# 2. „Noga 1 to wada PRZYRZĄDU, nie systemu"

**Ich werdykt:** najmocniejsze twierdzenie nocy; zdejmuje podejrzenie dziury
w bezpieczeństwie.
**Mój werdykt: POTWIERDZONE co do mechanizmu i co do wyniku — ale ZŁA DIAGNOZA POWODU,
dla którego system przechodzi.**
**Czy odtwarzałem:** tak, w części tanie i rozstrzygającej — **przeczytałem przypięte
źródła frameworka u nich na dysku**. Nie uruchamiałem ich suity.

## Atak, który przygotowałem — i który się nie powiódł

Szedłem po najostrzejszy zarzut, jaki dawało się postawić: że wniosek „system jest
w porządku" pochodzi z **odczytu kodu**, a odczyt dowodzi istnienia defektu, nigdy jego
braku. Raport B sam podaje amunicję: §3.3 projektuje pomiar rozstrzygający i kończy się
zdaniem **„NIE WDROŻYŁEM tego"**, a §8 otwiera się słowami **„Nic nie uruchomiłem."**

**Zarzut upada**, bo pomiar wykonał ktoś inny. Weryfikator A (R6A-2, Dowód 3) zbudował
przyrząd z **odczytem bazowym w obie strony** i zmierzył:

```
[R6A-P] BAZA (tozsamosc NIETKNIETA): 200 {"zalogowany":true,…,"role":["koordynator"]}
[R6A-P] po destroy dlugosc: 0
[R6A-P] PO USUNIECIU: 401 {"zalogowany":false}
[R6A-P] zadan do punktu tokenow: 1
```

Przyrząd świeci w obie strony, a licznik żądań do punktu tokenów **odcina świat
„wskrzeszenie z refresh tokenu"** (byłoby ≥ 2). To jest pomiar, nie lektura.
**Zapisuję to jako nieudany atak**, bo raport przemilczający własne nieudane próby jest
raportem nieprawdziwym.

## Mechanizm — zweryfikowany przeze mnie u źródła, 4 twierdzenia z 4

Ich wyjaśnienie stoi na czterech faktach o frameworku. Sprawdziłem **wszystkie cztery
w przypiętej wersji `v13.24.0` na ich dysku**, nie w dokumentacji i nie z pamięci:

| twierdzenie B | plik | co realnie jest | werdykt |
|---|---|---|---|
| `StartSession` jest singletonem z menedżerem z konstruktora | `Session/SessionServiceProvider.php:22` | `$this->app->singleton(StartSession::class, fn ($app) => new StartSession($app->make(SessionManager::class), …))` | **prawda** |
| `getSession()` używa menedżera z konstruktora, nie z kontenera | `Session/Middleware/StartSession.php:157` | `return tap($this->manager->driver(), …)` | **prawda** |
| `forgetInstance` nie ma jak go dosięgnąć | `Container/Container.php:1731` | `unset($this->instances[$abstract]);` | **prawda** |
| atrybuty w pamięci przeżywają pusty odczyt magazynu | `Session/Store.php:116` | `array_replace($this->attributes, $this->readFromHandler())` | **prawda** |
| klient testowy nie odsyła ciasteczka sesji | `Foundation/Testing/Concerns/MakesHttpRequests.php:730-740` | składa wyłącznie `defaultCookies` + `unencryptedCookies` | **prawda** |

Piąte twierdzenie sprawdziłem przy okazji i też się broni. **Mechanizmu nie podważam.**
Najciekawszy jest `Store.php:116`: `array_replace` **zachowuje** atrybuty już wczytane
do pamięci, gdy handler zwróci pustkę — to jest dokładnie ten nośnik, przez który
tożsamość przeżywa wyczyszczenie magazynu.

## Gdzie ich diagnoza jest ZŁA — powód, nie wynik

Napisali: *„system NIE wskrzesza tożsamości z refresh tokenu. Wymóg nogi 1 standardu B8
jest SPEŁNIONY."* Pierwsze zdanie sugeruje **obronę**. Zmierzyłem, skąd bierze się ta
własność:

```
$ grep -rn "refresh_token" backend/app/Tozsamosc/TozsamoscSesji.php
71:    public function refreshToken(): string
73:        return Typy::napis($this->dane['refresh_token'] ?? null);

$ grep -n "put(" backend/app/Tozsamosc/SesjaKonta.php
45:        $request->session()->put(self::KLUCZ, $dane);
62:        $request->session()->put(self::KLUCZ, $nowa->dane);
```

**Refresh token mieszka WEWNĄTRZ tożsamości, a tożsamość wewnątrz sesji.** Skasowanie
sesji zabiera refresh token razem z nią. Odświeżenie nie ma z czego wskrzesić tożsamości
**nie dlatego, że system odmawia, tylko dlatego, że nic nie zostało.**

To jest ta sama różnica, którą **nasz własny kontrakt** nazywa przy naszym wzorcu
(`INTEGRACJA-KONTRAKT.md` §2c.5 A):

> *„Stan wzorca `ref-laravel` (ZMIERZONE): defekt **nie występuje**, ale **z braku
> mechanizmu, nie z obrony** […] Nie traktuj tego jako dowodu, że wzorzec jest odporny."*

**Dlaczego to nie jest kosmetyka.** Własność „nie da się wskrzesić" jest tu **skutkiem
ubocznym miejsca przechowywania**, a nie decyzją. Pierwszy człowiek, który przeniesie
refresh token poza sesję — a jest do tego naturalny powód, choćby odświeżanie w tle albo
odświeżanie po stronie kolejki — **skasuje tę własność, nie tknąwszy ani jednej linii
w kodzie logowania**. I nic tego nie złapie, bo jedyny test tej własności to noga 1,
która jest dziś czerwona z powodu przyrządu.

**Sugerowana zmiana zapisu (ich decyzja, nie moja):** zamiast „system NIE wskrzesza"
zapisać „wskrzeszenie jest dziś **niemożliwe konstrukcyjnie**, bo refresh token nie
przeżywa skasowania sesji — własność zniknie, gdy token zamieszka gdziekolwiek indziej".
To jest zdanie prawdziwe w obu światach i niosące warunek, pod którym przestaje obowiązywać.

---

# 3. To, co uważam za najpoważniejsze: ich runda zawiera SPRZECZNOŚĆ, której nikt nie uzgodnił

**Ich id:** R6B §7 poz. 1 („Twierdzenia, których NIE UDAŁO SIĘ obalić").
**Mój werdykt: OBALONE.**
**Czy odtwarzałem:** tak — odczytem sygnatur w ich kodzie.

Weryfikator B umieścił w sekcji **„twierdzeń nieobalonych"** takie zdanie:

> *„**Wąskie gardło §2 jest zrealizowane.** `SesjaKonta::KLUCZ = 'konta'` ma dokładnie
> jednego pisarza […] Ścieżka »brak rekordu → utwórz« jest **niewywoływalna**, nie
> »zabroniona warunkiem«. Sprawdzone wprost: `grep -rn "session()->put('konta'\|SesjaKonta::"
> backend/app` daje jednego pisarza."*

W tej samej rundzie weryfikator A (R6A-3) **obalił to pomiarem na żywym stosie**, trzema
wektorami, pokazując tożsamość koordynatora wytworzoną bez logowania.

**Nie jest to spór interpretacyjny — sprawdziłem sygnatury i rację ma A:**

```php
// backend/app/Tozsamosc/TozsamoscSesji.php
public static function zMagazynu(array $zMagazynu): ?self   // PUBLICZNA, DOWOLNA tablica
    if (Typy::napis($zMagazynu['sub'] ?? null) === '') { return null; }

// backend/app/Tozsamosc/SesjaKonta.php
public static function zaloz(Request $request, array $dane): void   // ← przyjmuje TABLICĘ
    $request->session()->put(self::KLUCZ, $dane);                   // ← w. 45
public static function zaktualizuj(Request $request, TozsamoscSesji $nowa): void
    $request->session()->put(self::KLUCZ, $nowa->dane);             // ← w. 62
```

Trzy rzeczy naraz, każda osobno wystarczająca:

1. **`zMagazynu()` jest publiczną statyczną fabryką przyjmującą dowolną tablicę.**
   Jedynym warunkiem jest niepusty `sub` — czyli **warunek**, a nie sygnatura. To jest
   dokładnie to, co B nazywa „zabronione warunkiem" i przypisuje sytuacji, której tu nie ma.
2. **`zaloz()` w ogóle nie ma typowanego wąskiego gardła** — bierze `array $dane` i wkłada
   je do sesji bez pośrednictwa `TozsamoscSesji`. B analizował `zaktualizuj()` (które
   faktycznie przyjmuje obiekt) i **uogólnił na całą klasę**.
3. **Pisarzy klucza jest DWÓCH, nie jeden** (w. 45 i 62). Wymaganie z D-2026-08-08-24
   brzmi „zbiór miejsc zapisujących TE KONKRETNE klucze ma **liczność 1**". Liczność
   wynosi 2.

**Dlaczego to jest cięższe niż zwykła pomyłka.** Sekcja „czego nie udało się obalić" jest
najniebezpieczniejszym miejscem w raporcie weryfikacyjnym: czytelnik traktuje ją jako
**pozytywne potwierdzenie**. Tutaj niesie zdanie obalone w tej samej dobie, przez drugiego
weryfikatora, pomiarem. Ich `PODSUMOWANIE.md` **przyjmuje wersję A** (R6A-3 jest na liście
otwartych) — ale raportu B nikt nie skorygował, a raport B ma 719 linii i wygląda
autorytatywnie. Kto rano sięgnie po niego samego, wyjdzie z przekonaniem, że §2 jest
domknięte strukturalnie.

**Metoda, która do tego doprowadziła, jest nazwana w naszych wspólnych wytycznych.**
B rozstrzygał `grep`-em po **NAZWIE** (`"SesjaKonta::"`) i policzył *ile miejsc woła
pisarza*. Pytanie brzmiało: *czy da się dostarczyć pisarzowi tożsamość, której nikt nie
uwierzytelnił*. To jest pomiar pośrednika zamiast skutku — ta sama klasa, którą złapałem
u siebie tej nocy (moje N-17: asercje szukające nazw pól zamiast wartości; moje N-26:
strażnik mierzący czas katalogu zamiast faktu uruchomienia).

**Zalecenie (ich decyzja):** raport B potrzebuje errat­y przy §7.1, a nie milczącego
nadpisania przez `PODSUMOWANIE`. Zdanie obalone, które zostaje w dokumencie bez adnotacji,
wraca po miesiącach jako „przecież było sprawdzone".

---

# 4. R6B-9 — `RejestrSesji` łamie cztery wymagania trwałości

**Mój werdykt: POTWIERDZONE + ESKALACJA (część przyczyny jest po naszej stronie).**
**Czy odtwarzałem:** tak — odczyt ich kodu, bez uruchamiania.

```
$ grep -n "Cache::\|DB::table\|CZAS_ZYCIA" backend/app/Tozsamosc/RejestrSesji.php
23:    private const CZAS_ZYCIA_SEKUND = 86400;
33:        Cache::put(self::klucz($sid), $identyfikatory, self::CZAS_ZYCIA_SEKUND);
71:        Cache::forget(self::klucz($sid));
81:        return Typy::listaNapisow(Cache::get(self::klucz($sid), []));
96:        return DB::table('uniewaznione_sesje')…
114:        DB::table('uniewaznione_sesje')->upsert([[…
```

Znacznik unieważnienia — **baza**. Mapa `sid → sesje`, bez której wylogowanie nie znajdzie
żadnej sesji — **cache**. Zestawiając to z czterema wymaganiami z naszego kontraktu:
(3) czas życia 86 400 s **jest spełniony** (równy SSO Session Max — dobrze dobrany);
(1) trwałość i (2) współdzielenie **nie są** (cache podlega `cache:clear`, restartowi
i eksmisji); (4) niecicha eksmisja **nie jest** — `Cache::forget` i eksmisja nie zostawiają
śladu, a skutkiem jest `skasowane_sesje = 0` bez komunikatu.

## Eskalacja — dlaczego to nie jest wyłącznie ich niedopatrzenie

Cztery wymagania magazynu **pochodzą z naszego kontraktu**. Sprawdziłem, czy nasz kontrakt
w ogóle **wie o istnieniu** takiej mapy:

```
$ grep -n "sid → sesj\|rejestr sesji\|mapa sid" docs/INTEGRACJA-KONTRAKT.md
(brak trafień)
```

§4.5 mówi konsumentowi: *„skasuj sesje o tym `sid`"* — i **milczy o tym, że żeby je
skasować, trzeba je najpierw UMIEĆ ZNALEŹĆ**. Nasz wzorzec `ref-laravel` nie potrzebuje
mapy, bo skanuje pliki rekordów po `sid`; konsument na prawdziwym magazynie sesji
(Redis) **skanować nie może** i musi zbudować mapę — mechanizm, którego nasz kontrakt nie
nazywa, więc i nie obejmuje wymaganiami.

Czyli: **wymagania nałożyliśmy na znacznik, a mapa — bez której znacznik nie ma czego
unieważnić — została poza kontraktem.** Gabinet zbudował ją sam i umieścił w cache'u.
Trudno mieć o to pretensje do dokumentu, który jej nie wymienia.

To trafia do naszej erraty (`docs/ERRATA-KONTRAKT-2026-08-09.md`) jako **brakujący punkt**;
odnotowuję to jako znalezisko **przeciwko sobie**, nie przeciwko nim.

---

# 5. Ich „uczciwe negatywy" — sprawdzone osobno

Zlecenie kazało sprawdzić deklaracje braku defektu, bo odczyt kodu ich nie unosi.

| deklaracja | mój werdykt |
|---|---|
| B §8: *„Nic nie uruchomiłem"* | **wzorowa uczciwość**. B wymienia siedem rzeczy, których nie sprawdził, łącznie z listą plików, o których „nie twierdzi niczego". Nie znalazłem miejsca, gdzie B przekracza własną deklarację zakresu. |
| B §7.1 („gardło §2 zrealizowane") | **OBALONE** — sekcja 3 wyżej. |
| B §7.2 („wycofanie wniosku o wskrzeszeniu było poprawne") | **POTWIERDZONE**, ale wyłącznie dzięki pomiarowi A. Samo B nie mogło tego ustalić — i samo to przyznaje. |
| B §7.8 (`uniewazniona()` fail-closed) | **nie podważam**; zastrzeżenie B o akceptacji statusu 500 jest trafne i sam bym je podniósł. |
| A R6A-3, „świat alternatywny" | **wzorowy negatyw**: A sam pisze, że to **nie jest** dziura wykorzystywalna z zewnątrz, bo trasy musiał dopisać. Adwersarz nie ma tu czego dodać — autor sam zawęził swoje twierdzenie do struktury. |
| A R6A-2, „świat alternatywny" | trafny: pomiar był bez prawdziwego Keycloaka. Dodaję **drugi niewymieniony** świat — sekcja 2, „refresh token mieszka w sesji". |

## Jedna rzecz w `PODSUMOWANIE.md` jest zawyżona

> *„dwaj niezależni kontrolerzy ustalili przyczynę i **zmierzyli**, że system zachowuje się
> PRAWIDŁOWO"*

**Zmierzył jeden.** B otwiera swoją sekcję ograniczeń zdaniem „Nic nie uruchomiłem" i sam
oznacza cztery swoje znaleziska jako wyprowadzone z lektury. Liczba mnoga zamienia
**jeden pomiar plus jedną zgodną analizę** w „dwa pomiary" — a to jest dokładnie ta
operacja, którą ich własna noc nazywa u kodu: **zgodność dwóch źródeł przedstawiona jako
niezależne potwierdzenie, choć jedno z nich niczego nie mierzyło.**

Waga: niska merytorycznie (wniosek się broni), **średnia dla wiarygodności raportu** —
bo to zdanie czyta właściciel, nie architekt.

---

# 6. Co z tego wynika dla NASZEGO repozytorium — trzy rzeczy

Te trzy pozycje wyszły dopiero z zestawienia obu nocy i **żadnej z nich nie widać
z żadnego z repozytoriów osobno**.

## 6.1 · KRYTYCZNE · Nasz kontrakt cytuje jako „POTWIERDZONY DEFEKT" mechanizm, który ich noc OBALIŁA

`docs/INTEGRACJA-KONTRAKT.md` §2c.5 (A) mówi konsumentom — czterem systemom:

> *„**Potwierdzony defekt w innym repo ekosystemu** (Gabinet, 2026-08-08): po usunięciu
> tożsamości z magazynu następne żądanie wróciło ze statusem 200 i uwierzytelnioną
> tożsamością — **odświeżanie odtworzyło ją z refresh tokenu**."*

Ich noc zmierzyła, że **odświeżanie niczego nie odtwarzało**: żądań do punktu tokenów
było **1** (sama wymiana kodu przy logowaniu), a 200 pochodziło z tożsamości niesionej
w pamięci przez singleton `StartSession`. Objaw był prawdziwy; **przypisany mechanizm
nie**. My zapisaliśmy u siebie mechanizm, nie objaw — i opatrzyliśmy go słowem
„potwierdzony".

**Co z tym zrobić (nie zrobiłem — to przedmiot, nie przyrząd, i wymaga decyzji rano):**
sam **wymóg** zostaje słuszny — „odświeżanie nie może odtwarzać tożsamości" jest dobrą
regułą niezależnie od tego, czy Gabinet ją kiedykolwiek złamał. Poprawić trzeba
**dowód**: zamiast „potwierdzony defekt, odświeżanie odtworzyło" napisać, że objaw
zmierzono, a przypisanie do odświeżania zostało później obalone pomiarem licznika żądań.
Trafia to do `docs/ERRATA-KONTRAKT-2026-08-09.md`.

**Dlaczego to jest krytyczne, a nie kosmetyczne:** to zdanie jest **jedyną motywacją**
dla wymogu strukturalnego, który nakładamy na cztery systemy. Konsument, który sprawdzi
je u źródła i zobaczy, że zostało obalone, ma powód uznać cały wymóg za nieaktualny.
Reguła oparta na obalonym dowodzie broni się gorzej niż reguła bez dowodu.

## 6.2 · WYSOKIE · Nakładamy wymóg strukturalny, którego NASZ WŁASNY wzorzec nie spełnia

Kontrakt §2c.5 (A) żąda od konsumentów:

> *„pisarz stanu tożsamości przyjmuje **istniejący rekord jako wejście** (wymusza to typ)
> […] **Warunek `if` da się ominąć; sygnatura nie**."*

Zmierzone dziś po obu stronach:

| | pisarz tożsamości | czym chroniony |
|---|---|---|
| **Gabinet** | `SesjaKonta::zaloz(Request, array $dane)` + `zaktualizuj(…, TozsamoscSesji)` | **dwa** pisarze; pierwszy przyjmuje surową tablicę |
| **nasz wzorzec** `ref-laravel` (przed moją nocną zmianą) | `SessionStore::create` w callbacku OIDC **oraz** w `/_test/wskrzes-sesje` | **dwa** pisarze; drugi chroniony **warunkiem `if`** |

**Obie strony realizują wymóg w jego słabszej postaci, tej, którą kontrakt wprost nazywa
gorszą.** My w dodatku byliśmy tego świadomi — moje N-6 i N-12 z tej nocy opisują
dokładnie to. Konsument nie miał od kogo skopiować mocnej postaci, bo **wzorzec jej nie
pokazuje**.

To jest odpowiedź na pytanie ze zlecenia „czy przyczyna nie leży we wzorcu": przy kontroli
haseł — nie; **przy wymogu strukturalnym — tak, częściowo.** Wymóg zapisaliśmy; przykładu
nie daliśmy.

## 6.3 · ŚREDNIE · Symetria, która mnie obciąża: u nich kontrola jest słaba, u nas jej NIE MA

Krytykuję ich denylistę. Zmierzyłem u siebie:

```
$ git grep -lni "password_hash\|bcrypt\|Hash::make" -- tests/ realm/ scripts/
tests/broker/run.sh          ← niezwiązane (atrapa OIDC)
tests/theme/lib/kcadmin.js   ← niezwiązane (klient Admin API)
```

Nasze `CLAUDE.md` mówi: *„We do NOT implement authentication logic […] If you find
yourself writing code that handles passwords, sessions, or tokens by hand — STOP"*.
**Żadna z dziesięciu suit tego nie sprawdza.** Gabinet ma kontrolę słabą; my nie mamy
żadnej — a regułę mamy ostrzejszą.

Dopisuję to do klasy z mojego N-15 („pierwsza twarda reguła projektu bez strażnika")
i do gotowego strażnika `PROPOZYCJA-testu-higiena.sh` jako kandydata na piątą kontrolę.
**Nie dopisałem jej dziś** — z tego samego powodu, dla którego nie dopisywałem strażników
w nocy: brakujący przyrząd to nie jest przyrząd zepsuty, a nowa kontrola sama potrzebuje
rundy.

---

# 7. Czego NIE sprawdziłem — jawnie

1. **Nie uruchomiłem ani jednego ich testu, skryptu ani kontenera.** Wszystkie werdykty
   dotyczące *wyniku przebiegu* przyjmuję od nich; podważam wyłącznie **konstrukcje
   i wnioski**, które dają się sprawdzić odczytem.
2. **Nie odtworzyłem R6A-3 ani R6A-4 wykonawczo** — wymagałoby dopisania tras w ich
   repozytorium, czyli zapisu, którego zakazano. Odtworzyłem **sygnatury**, na których
   oba stoją, i te wystarczają do werdyktu o strukturze.
3. **Nie oceniałem 20 z 29 ich znalezisk** — perturbacje, `bramka.sh`, allowlisty
   `--przyczyna`, ustawienia PostgreSQL/Redis (N-5…N-7). Ich konstrukcja wygląda solidnie,
   ale **nie twierdzę o nich niczego**; zakres wybrałem wg zlecenia (dwie pozycje
   priorytetowe) plus to, co dotyka naszego kontraktu.
4. **Nie czytałem ich `.env`**, ich `DECYZJE.md` ani `PLAN-FAZ.md` poza cytatami
   w raportach — więc twierdzeń o wcześniejszych decyzjach D-2026-08-08-24/25/27/28
   nie weryfikowałem u źródła.
5. **Nie sprawdziłem, czy `hash()` występuje legalnie w ich drzewie** (a jeśli tak, byłby
   to możliwy powód, dla którego nie trafił na listę zakazanych prymitywów). Hipoteza
   nieweryfikowana — zapisuję ją jako hipotezę, nie jako ustalenie.

---

# 8. Podsumowanie dla właściciela — bez numerów

**Ich noc się broni.** Najmocniejsze twierdzenie — że system działa poprawnie, a czerwony
był przyrząd — **wytrzymało atak**. Sprawdziłem je najostrzej, jak umiałem: przeczytałem
źródła frameworka, na których stoi, i wszystkie pięć fragmentów zgadza się co do znaku.
Podejrzenie dziury w bezpieczeństwie zostało zdjęte słusznie.

**Dwie rzeczy bym jednak poprawił w ich zapisie.** Po pierwsze, system nie tyle *broni się*
przed wskrzeszeniem tożsamości, ile *nie ma z czego* jej wskrzesić — a to znika w dniu,
w którym ktoś przeniesie jeden token w inne miejsce. Po drugie, jeden z ich raportów
w sekcji „rzeczy potwierdzone" niesie zdanie, które drugi weryfikator obalił pomiarem
tej samej nocy; podsumowanie przyjęło wersję właściwą, ale raportu nikt nie poprawił.

**A najważniejsze wyszło poza ich repozytorium.** Nasz kontrakt integracyjny — dokument,
z którego czytają cztery systemy fundacji — opisuje ich defekt jako „potwierdzony"
i podaje mechanizm, który ta noc obaliła. Reguła, którą na tym opieramy, jest nadal
słuszna; jej uzasadnienie trzeba poprawić, zanim ktoś je sprawdzi u źródła. To jest
naprawa po **naszej** stronie i trafiła do erraty czekającej na rano.
