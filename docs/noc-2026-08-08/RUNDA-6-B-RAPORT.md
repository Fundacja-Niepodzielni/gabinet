# Runda 6, część B („analiza") — raport niezależnego weryfikatora

**Postawa:** sceptyczna. Zadaniem było OBALIĆ twierdzenia wykonawcy, nie potwierdzić.
**Bez Dockera.** Żaden stos nie był stawiany, żaden kontener/wolumen/obraz nie był
kasowany. Narzędzia: czytanie, `grep`, rozumowanie na źródłach — w tym na
**źródłach frameworka** (`vendor/laravel/framework` w drzewie dewelopera, czytane
wyłącznie do odczytu).

---

## 1. SHA, na którym pracowałem

```
klon:  d:/KOD/Niepodzielni/gabinet  →  /d/tmp/gabinet-r6b
HEAD:  49131d8d0bbe73991ea4283b7bd631fc17b0b751
```

Drzewo robocze wykonawcy (`d:\KOD\Niepodzielni\gabinet`) nie zostało zmienione.
**Uwaga:** w trakcie mojej pracy `HEAD` wykonawcy przesunął się na `c3a11c0`
(zmierzone `git rev-parse HEAD` po zakończeniu analizy). Zgodnie z regułą
zbieżności rund (D-2026-08-07-16) **cały ten raport odnosi się do `49131d8`**;
znaleziska mogą być już częściowo nieaktualne wobec `c3a11c0` i wymagają
przełożenia na ten SHA przez wykonawcę.
Pliki `vendor/laravel/framework/**` czytane w drzewie dewelopera (klon nie ma
`vendor/`) — wyłącznie odczyt, wersja przypięta `laravel/framework v13.24.0`
z `backend/composer.lock`.

---

## 2. ZADANIE A — dyskryminatory i ich gałęzie

Odruch stosowany przy każdym wierszu: **„jakie światy dają tę wartość"**.

### 2.1 `backend/tests/Feature/OdebranieRoliTest.php`

| pomiar (plik:linia) | co mierzy | jakie ŚWIATY dają tę wartość | odczyt bazowy? | werdykt |
|---|---|---|---|---|
| `OdebranieRoliTest.php:177-180` | 200 + `role==['koordynator']` + `bramki['panel.koordynacji']===true` | tożsamość istnieje i token świeży | to JEST odczyt bazowy dla kolejnego pomiaru | **ROZSTRZYGAJĄCY** |
| `:197-200` | po `sleep(2)`: 200 + `role==[]` + bramka `false` | tylko: odświeżenie zwróciło token bez roli (brak odświeżenia ⇒ stare role z sesji; brak tożsamości ⇒ 401 i `assertOk` pada) | tak (:177) | **ROZSTRZYGAJĄCY** |
| `:209` | 3× bramka `true` przy ważnym tokenie | kierunek odwrotny: sesja nie wygasa co żądanie | — | **ROZSTRZYGAJĄCY** |
| `:216-223` | `$doTokenu === 1` z `Http::recorded()` | wyłącznie: jedna wymiana kodu, zero odświeżeń (przy zdanym :209) | tak (:209) | **ROZSTRZYGAJĄCY** |
| `:233` | 401 + `zalogowany:false` po odmowie IdP | (a) odświeżenie odrzucone → `zakoncz()`; (b) **logowanie w `zalogujKoordynatora()` w ogóle się nie powiodło**; (c) tożsamości nie było z innego powodu | **NIE — jedyny test w pliku bez asercji stanu wyjściowego** | **ZDEGENEROWANY** (R6B-6) |
| `:241` | bramka `true` przed logoutem | odczyt bazowy | — | **ROZSTRZYGAJĄCY** |
| `:263` | `RejestrSesji::odczytaj($sid)` ma 1 wpis | w suicie: sterownik cache `array`, więc 1 ⇔ „zapamiętano". **W produkcji ta sama 1 jest zgodna także z „wpis jeszcze nie wyeksmitowany / nie wyczyszczony"** — magazyn jest cache'em Redisa | tak | **ROZSTRZYGAJĄCY w suicie / BEZ POKRYCIA produkcji** (R6B-9) |
| `:267` | `sesjaWMagazynie($idSesji) !== ''` | odczyt bazowy; magazyn to `ArraySessionHandler` (ten test NIE przełącza sterownika) | tak | **ROZSTRZYGAJĄCY w suicie** |
| `:273` | `skasowane_sesje === 1` | logout trafił w zarejestrowany identyfikator | tak | **ROZSTRZYGAJĄCY w suicie** |
| `:283` | `sesjaWMagazynie($idSesji) === ''` | klucz zarejestrowany przy logowaniu zniknął. **Nie mierzy** klucza utworzonego przez żądanie z `:241` — ten żyje dalej | tak | **NIEPEWNY** (mierzy 1 z ≥2 żywych kluczy) |
| `:297` | `sesjaWMagazynie !== ''` | odczyt bazowy | tak | **ROZSTRZYGAJĄCY** |
| `:307-311` | 200 + `wejscia()==1` + `awarie()==0` + `skasowane==1` + magazyn pusty | ślad idzie do PLIKU (`SladWylogowania`), czyli ścieżką niezależną od cache'u; `wyczysc()` na :301 | tak | **ROZSTRZYGAJĄCY** (wzorcowe zamknięcie C1) |
| `:332-335` | 503 + `skasowane==0` + `odmowy()==1` + sesja żyje | jw., `wyczysc()` na :325 | tak | **ROZSTRZYGAJĄCY** |
| `:350-354` | rejestr ma 1 wpis + sesja w magazynie | odczyt bazowy ataku | tak | **ROZSTRZYGAJĄCY** |
| `:401-402` | `wejscia()==1` **i** `awarie()==1` | dowód, że atak DOSZEDŁ do ścieżki awaryjnej — bez tego 200/sesja-żyje byłoby zgodne ze „zwykłą odmową walidacji" | tak | **ROZSTRZYGAJĄCY** (najlepszy pomiar w pliku) |
| `:407-414` | sesja żyje + `ok!==true` + `skasowane==0` + status ∈ {400,503} | dwuelementowy zbiór statusów jest ZADEKLAROWANY, a własność bezpieczeństwa asertowana osobno | tak | **ROZSTRZYGAJĄCY** |
| `:468-477` | role z access tokenu przy TRZECH rozbieżnych źródłach | tylko odczyt z access tokenu daje ten wynik | konstrukcja fixtury zastępuje bazowy | **ROZSTRZYGAJĄCY** |
| `:515-527` | ID token ≠ oryginał, e-mail niedekodowalny, `Crypt::decryptString` odwraca | wyklucza „tylko zakodowany" i „zepsuty zapis" | tak | **ROZSTRZYGAJĄCY** |
| `:550` | bramka `true` (BLK-22, stan wyjściowy) | odczyt bazowy | — | **ROZSTRZYGAJĄCY** |
| `:555` | `skasowane_sesje === 1` | logout trafił w sesję | tak | **ROZSTRZYGAJĄCY** |
| `:568-575` | po `forgetInstance('session'/'session.store')`: 401 | (a) magazyn pusty — **świat deklarowany w komentarzu**; (b) **znacznik `uniewaznione_sesje` w bazie → `SesjaKonta::zakoncz()` → `null` → 401** — i to jest świat, który realnie zachodzi, bo `forgetInstance` NIE odcina `Store` niosącego atrybuty (dowód w §3) | NIE (brak odczytu odróżniającego oba mechanizmy) | **ZDEGENEROWANY** (R6B-2) |
| `:622` | bramka `true` (NOGA 1, stan wyjściowy) | odczyt bazowy | — | **ROZSTRZYGAJĄCY** |
| `:636-638` | **NOGA 1**: oczekiwane 401, zmierzone 200 | ≥5 światów — patrz §3 | NIE | **ZDEGENEROWANY** (R6B-1) |
| `:652` / `:662-664` | bramka `true`, potem po `Cache::flush()` → 401 | znacznik w BAZIE przeżył; `Cache::flush()` czyści `array` (a nie Redis), więc mierzy „znacznik nie mieszka w cache'u aplikacji" | tak | **ROZSTRZYGAJĄCY** (mechanizm ten sam co :568 — tam jednak przypisany innemu zjawisku) |
| `:694-697` | status ≠ 200 i ∈ {401,500,503} po `Schema::drop` | (a) świadoma odmowa; (b) **niekontrolowany wyjątek → 500**. Test akceptuje oba, choć doktryna projektu wymaga „odmowa GŁOŚNA", a 500 jest awarią, nie odmową | tak (:678) | **NIEPEWNY** |
| `zalogujKoordynatora()` `:158-162` | brak jakiejkolwiek asercji na odpowiedź `/auth/callback` | „logowanie się udało" NIGDY nie jest sprawdzane wprost | — | **NIEPEWNY** — źródło degeneracji `:233` |

### 2.2 `skrypty/bramka.sh` (22 kroki)

| krok (linia) | co mierzy | światy dające wartość „zielony" | odczyt bazowy | werdykt |
|---|---|---|---|---|
| `:269-270` przygotowanie env | kod wyjścia `przygotuj_env()` | (a) plik zbudowany poprawnie; (b) **któryś z 6 `sed -i` nie trafił w żadną linię — `sed` bez trafienia kończy się sukcesem** (`:249-257`) | NIE (brak odczytu pliku z powrotem) | **ZDEGENEROWANY** (R6B-7) |
| `:272-293` sprzątanie + kontrola wolumenów | `docker volume inspect` po `down -v` | pyta o STAN, nie o kod wyjścia | tak | **ROZSTRZYGAJĄCY** |
| `:295-296` build | kod wyjścia | — | — | **ROZSTRZYGAJĄCY** |
| `:298-300` `up -d` | kod wyjścia `up` | nagłówek sam mówi, że kod `up` jest deklaracją o jednej chwili — a krok nadal `|| zle` | — | **NIEPEWNY** (sprzeczność z własną zasadą 5) |
| `:303-333` zbieżność zdrowia | `healthy` **albo** `running` | dla usług bez healthchecka `running` to jedyna możliwa wartość ⇒ dla nich krok nie może zaświecić | — | **NIEPEWNY** |
| `:335-341` `vendor/autoload.php` | `test -f` w kontenerze | — | — | **ROZSTRZYGAJĄCY** |
| `:343-349` skrypty uruchamialne | kod wyjścia `skrypty-uruchamialne.sh` | patrz §2.4 — jeden z jego 5 pomiarów jest zdegenerowany | — | **ZDEGENEROWANY (przez podpomiar)** (R6B-8) |
| `:351-373` zależności (3 sygnały) | `validate` + `--dry-run` + obecność na dysku | trzy niezależne ścieżki; O-7 udokumentowany | tak (perturbacja `vendor`, obie gałęzie + powrót) | **ROZSTRZYGAJĄCY** |
| `:375-376` migracje | kod wyjścia | — | — | **ROZSTRZYGAJĄCY** |
| `:378-387` migracje odwracalne | kod wyjścia, **wyjście do `/dev/null`** | czerwień zgodna z {brak `down()`, `dc exec` padło, baza znikła} | — | **NIEPEWNY** (ta sama klasa co obalone `oczekuj_czerwone`) |
| `:389-390` `gabinet:zdrowie` | polecenie pytające o stan | — | perturbacja `zdrowie` | **ROZSTRZYGAJĄCY** |
| `:392-399` znacznik aplikacji | treść `/api/wersja` zawiera `gabinet-api-v1` | 200 sam w sobie nie wystarcza — i tak jest zrobione | perturbacja `tozsamosc` | **ROZSTRZYGAJĄCY** |
| `:401-414` Horizon | `grep 'is running'` | — | — | **ROZSTRZYGAJĄCY** |
| `:416-426` puls | `gabinet:puls --sprawdz` | wpis pulsu mieszka w **cache'u** (Redis) — patrz R6B-10 | perturbacja `puls` (obie strony) | **ROZSTRZYGAJĄCY w suicie / kruchy operacyjnie** |
| `:429-440` porty deklaratywnie | `docker inspect` PortBindings | — | — | **ROZSTRZYGAJĄCY** |
| `:442-460` porty aktywnie | `curl http://ADRES_LAN:PORT/` dla 3 portów | „brak osiągalnych" zgodne z {nic nie wystawione} **oraz** z {PG/Redis wystawione, ale nie mówią po HTTP} **oraz** z {krok pominięty, bo `gethostbyname` dał 127.* albo brak `php` na hoście} — ostatni przypadek jest wypisywany, ale **krok i tak przechodzi** | NIE | **ZDEGENEROWANY** (R6B-11) |
| `:463-464` Pint | `--test` | — | perturbacja `format` | **ROZSTRZYGAJĄCY** |
| `:466-467` PHPStan | `analyse` | — | perturbacja `statyka` | **ROZSTRZYGAJĄCY** |
| `:469-480` Pest | kod wyjścia + pełne wyjście wypisane + czas | — | — | **ROZSTRZYGAJĄCY** |
| `:482-513` podłoga testów | `policz_testy >= 170` | — | perturbacje `pusta_suita`, `pominiete`, `licznik` | **ROZSTRZYGAJĄCY**, ale patrz R6B-12 (perturbacje dowodzą podłogi 100, nie 170) |
| `:515-525` podłoga asercji | `policz_asercje >= 590` | jw. (perturbacje dowodzą 300) | jw. | **ROZSTRZYGAJĄCY / niedomierzony** |
| `:527-536` gitleaks | tryb git + `pipefail` | O-3 (drzewo robocze) udokumentowane | perturbacja `sekrety` (tryb `--no-git`) | **ROZSTRZYGAJĄCY** |

### 2.3 `skrypty/perturbacje.sh` + `skrypty/perturbuj.py` — wszystkie 30 scenariuszy

Mechanizm rozstrzygania: `oczekuj_czerwone` (`:205-263`) — kod ≠ 0, wyjście niepuste,
brak trafienia w denylistę `AWARIE_POBOCZNE` (`:198`), opcjonalnie `--przyczyna`.

**Ustalenie przekrojowe nr 1 — allowlisty, które nic nie zawężają.** Pest wypisuje
NAZWĘ każdego testu i nazwę klasy/pliku w KAŻDYM przebiegu (także dla testów zdanych).
Wzorzec `--przyczyna` równy nazwie testu, nazwie pliku albo wartości `--filter`
występuje w wyjściu **bezwarunkowo**, więc nie odróżnia „czerwień z badanej asercji"
od „czerwień z czegokolwiek innego w tym samym zbiorze".

**Ustalenie przekrojowe nr 2 — `OdebranieRoliTest.php` jest TRWALE CZERWONY** (NOGA 1,
`PLAN-FAZ.md:12-16`). Każde `oczekuj_czerwone … pest tests/Feature/OdebranieRoliTest.php`
bez zawężającej przyczyny **musi** zwrócić ✓, niezależnie od tego, czy mutacja
cokolwiek zmieniła. To jest 5 scenariuszy (7 wywołań).

| scenariusz (linia) | mierzy | światy dające „✓" | dowód mutacji | werdykt |
|---|---|---|---|---|
| `p_testy` `:299-312` | `pest --filter="granicę okna"` czerwony | mutacja `sed -i` **bez dowodu mutacji**; przyczyna `"granicę okna"` = wartość `--filter` ⇒ w wyjściu zawsze | brak (raw `sed`) | **NIEPEWNY** (przyczyna pusta, mutacja bez dowodu; wzorzec dziś trafia — `OcenaAnulacji.php:75`) |
| `p_pusta_suita` `:314-359` | 3 pomiary: pusty przebieg rozpoznany, `policz_testy<100`, pełny przebieg `>=100` | kierunek odwrotny obecny | tak (na wyjściu pesta) | **ROZSTRZYGAJĄCY** (ale próg 100 ≠ 170, patrz R6B-12) |
| `p_statyka` `:361-372` | PHPStan czerwony | pierwszy `sed` (`:366`) **nie trafia** — wzorzec `.."..` nie pasuje do `string $domyslny = ''` (`Typy.php:23`); czerwień pochodzi wyłącznie z dopisanej funkcji `:367` | brak | **NIEPEWNY** (cicha mutacja-widmo obok działającej) |
| `p_format` `:374-383` | Pint czerwony | dopisanie działa zawsze | brak | **ROZSTRZYGAJĄCY** |
| `p_sekrety` `:385-404` | gitleaks + `SekretyTest` | dwie niezależne ścieżki | brak jawnego, ale `sed` na `^KEYCLOAK_CLIENT_SECRET=$` | **ROZSTRZYGAJĄCY** |
| `p_hasla` `:406-426` | cały `BrakWlasnychHaselTest.php` | przyczyna `"BrakWlasnychHasel"` = nazwa klasy w nagłówku Pesta ⇒ zawsze obecna; nie odróżnia, KTÓRA z 7 asercji padła ani czy padła przez `migrate:fresh` | `podmien()` z twardym błędem + kod wyjścia SPRAWDZANY (`:417`) | **NIEPEWNY** (mutacja pewna, przyczyna pusta) |
| `p_hasla_v2` `:428-447` | jw. | jw. | jw. (`:438`) | **NIEPEWNY** |
| `p_nonce` `:449-461` | `pest --filter="nonce"` (4 testy) | brak przyczyny; podłoga denylisty | `podmien()` + kod wyjścia sprawdzany (`:457`) | **ROZSTRZYGAJĄCY** |
| `p_lockfile` `:463-473` | `composer validate` | — | `podmien` przez indeks `content-hash`, kod sprawdzany | **ROZSTRZYGAJĄCY** |
| `p_vendor_niekompletny` `:534-580` | 3 pomiary + powrót na zielone | dowód mutacji POZYTYWNY (`[ ! -d … ]`) | tak | **ROZSTRZYGAJĄCY** |
| `p_wzmacniacz` `:582-600` | `WzmacniaczZadanTest` czerwony | dowód to `! grep 'KLUCZ_ODSWIEZANIE, 1'` — wzorzec ISTNIEJE (`KontaOidc.php:126`), więc negacja jest falsyfikowalna | tak | **ROZSTRZYGAJĄCY** (patrz jednak R6B-14: test biegnie na `CACHE_STORE=array`) |
| `p_retencja` `:602-670` (2 gałęzie) | `RetencjaTest` czerwony, dwa kierunki awarii | dowody pozytywne (`test -f`, `grep -q`) | tak | **ROZSTRZYGAJĄCY** |
| `p_suita_pominieta` `:672-733` | 4 pomiary + kierunek odwrotny | dowód: `policz_pominiete >= 100` | tak | **ROZSTRZYGAJĄCY** |
| `p_obietnica` `:735-764` | `ObietniceKomentarzyTest` + powrót | dowód pozytywny `grep W-777` | tak | **ROZSTRZYGAJĄCY** |
| `p_sesja_jawna` `:766-791` | „test znajduje e-mail i ID token JAWNIE w Redisie" | ✓ zgodne z {realny wyciek} **oraz** z {szyfrowanie nadal włączone przez `SESSION_ENCRYPT=true` w środowisku, a czerwień pochodzi z asercji na TREŚCI PLIKU `SesjaBezJawnychDanychTest:143-150`} — a to drugie zachodzi na każdym środowisku zbudowanym z `.env.example` (`SESSION_ENCRYPT=true`) | dowód mutacji pozytywny, ale dotyczy WARTOŚCI DOMYŚLNEJ, nie skutku | **ZDEGENEROWANY** (R6B-4) |
| `p_role_zamrozone` `:793-811` | `OdebranieRoliTest` czerwony | **mutacja MARTWA** (patrz R6B-3), dowód mutacji **pusty**, czerwień z NOGI 1 | `! grep 'wymagaOdswiezenia($konta)'` — wzorca NIE MA w kodzie ⇒ negacja zawsze prawdziwa | **ZDEGENEROWANY — fałszywe zielone** (R6B-3) |
| `p_logout_failsafe` `:813-831` | jw. | mutacja działa, ale dowód mutacji **pusty**: `! grep 'sidNiezweryfikowany'` w `BackchannelLogoutController.php`, gdzie tego symbolu **nie ma wcale** (istnieje tylko w `WalidatorTokenu.php:157`); czerwień i tak z NOGI 1 | pusty | **ZDEGENEROWANY** (R6B-5) |
| `p_zrodlo_rol` `:833-851` | jw. | dowód POZYTYWNY (`grep -c … = 3`) ✔, ale czerwień z NOGI 1 nieodróżnialna od czerwieni z mutacji | tak | **ZDEGENEROWANY po stronie czerwieni** (R6B-13) |
| `p_wymuszone_wylogowanie` `:853-872` | jw. + `--przyczyna "WYMUSZONE WYLOGOWANIE"` | przyczyna to KOMUNIKAT ASERCJI (`OdebranieRoliTest.php:407`), nie nazwa testu ⇒ **zawęża realnie** | dowód pozytywny | **ROZSTRZYGAJĄCY** |
| `p_uniewaznienie_sid` `:946-982` | jw. + `--przyczyna "POZYTYWNY"` | **przyczyna to NAZWA TESTU** (`OdebranieRoliTest.php:530`) ⇒ w wyjściu zawsze; mutacja MARTWA (patrz R6B-3); kierunek odwrotny (`:975`) nie może dziś przejść, bo plik jest trwale czerwony | `! grep 'RejestrSesji::uniewazniona'` — wzorzec ISTNIEJE ⇒ dowód **poprawnie pada** i melduje „MUTACJA NIE WESZŁA" | **ZDEGENEROWANY** (allowlista pusta; ratuje go tylko dowód mutacji) (R6B-3) |
| `p_id_token_w_sesji` `:874-907` (2 gałęzie) | jw. | obie gałęzie: czerwień z NOGI 1 nieodróżnialna | noga 1: `! grep 'Crypt::encryptString($idToken)'` — wzorzec istnieje ⇒ falsyfikowalny ✔; noga 2: pozytywny ✔ | **ZDEGENEROWANY po stronie czerwieni** (R6B-13) |
| `p_retencja_wykonanie` `:909-944` | `RetencjaWykonanieTest` + `--przyczyna "PRZEŻYŁ zadanie retencyjne"` | przyczyna to KOMUNIKAT ASERCJI (`RetencjaWykonanieTest.php:70`) ⇒ zawęża realnie; dodatkowo pomiar odwrotny (rejestr zostaje zielony) | dowód złożony, pozytywny | **ROZSTRZYGAJĄCY** (najlepiej skonstruowany scenariusz) |
| `p_zamek` `:984-1034` | `oczekuj_kodu … 3` + kierunek odwrotny | konkretny kod wyjścia, ścieżka podana przez samą bramkę | dowód pozytywny (`kill -0`) | **ROZSTRZYGAJĄCY** |
| `p_licznik_testow` `:1036-1071` | `policz_testy >= 100` na realnie zepsutej suicie | dowód: `grep 'failed'` | tak | **ROZSTRZYGAJĄCY** |
| `p_sonda_bazy` `:493-532` | CZAS ≤ 20 s + dowód treścią + kierunek odwrotny | mierzy czas, nie samą czerwień | tak | **ROZSTRZYGAJĄCY** |
| `p_zdrowie` `:1073-1088` | `gabinet:zdrowie --cichy` czerwony po `dc stop postgres` | brak dowodu mutacji; czerwień zgodna z {baza stoi} i {`dc exec` padło} — częściowo łapie to denylista (`is not running`) | brak | **NIEPEWNY** |
| `p_tozsamosc` `:1090-1100` | `pest --filter="WŁASNYM znacznikiem"` | raw `sed` bez dowodu; wzorzec dziś trafia (`config/gabinet.php:29`) | brak | **NIEPEWNY** |
| `p_puls` `:1102-1165` | `gabinet:puls --sprawdz` + kierunek odwrotny niezależną ścieżką | dowód mutacji czyta WARTOŚĆ, nie kod wyjścia | tak | **ROZSTRZYGAJĄCY** |
| `p_biala_lista` `:1167-1178` | `pest --filter="marker"` + `--przyczyna "Bramki\|marker"` | przyczyna = wartość `--filter` ⇒ pusta; raw `sed` bez dowodu (wzorzec dziś trafia — `Bramki.php:61`) | brak | **NIEPEWNY** |
| `p_zamrozenie` `:1180-1192` | `pest --filter="ZAMROŻONĄ"` + `--przyczyna "ZAMROŻONĄ"` | **przyczyna identyczna z `--filter`, a „ZAMROŻONĄ" to NAZWA TESTU** (`OcenaAnulacjiTest.php:152`) ⇒ w wyjściu zawsze; raw `sed` bez dowodu | brak | **ZDEGENEROWANY** (allowlista pusta) (R6B-15) |

### 2.4 pozostałe skrypty

| pomiar | co mierzy | światy | werdykt |
|---|---|---|---|
| `licz-testy.sh:44-49` `policz_testy` | suma `passed\|failed\|risky\|warned` | nie obejmuje `errors` (PHPUnit potrafi raportować „N errors") — wtedy undercount i fałszywa czerwień podłogi | **NIEPEWNY** |
| `licz-testy.sh:52-57` `policz_pominiete` | `skipped\|todo\|incomplete` | — | **ROZSTRZYGAJĄCY** |
| `licz-testy.sh:60-66` `policz_asercje` | ostatnie `(N assertions)` | brak dopasowania ⇒ 0 ⇒ podłoga pada — kierunek bezpieczny | **ROZSTRZYGAJĄCY** |
| `perturbacje-powtarzalne.sh:56-67` | obecność wiersza `^PERTURBACJE` | brak wiersza = osobny, głośny stan ✔ | **ROZSTRZYGAJĄCY** |
| `perturbacje-powtarzalne.sh:99-102` | równość WIERSZY PODSUMOWANIA | identyczne podsumowania są zgodne z {identyczny wynik} **oraz** z {inne scenariusze padły, ale liczniki wyszły takie same} | **NIEPEWNY** |
| `perturbacje-powtarzalne.sh:88-96` | `git status` vs stan wyjściowy | ✔ V-11 naprawione, `STAN_WYJSCIOWY` realnie użyty | **ROZSTRZYGAJĄCY** |
| `skrypty-uruchamialne.sh:41-45` | `perturbuj.py` startuje | — | **ROZSTRZYGAJĄCY** |
| `skrypty-uruchamialne.sh:51-57` | każde `perturbuj X` jest w liście poleceń | dopasowanie **podciągiem**, nie tokenem: `hasla-podloz` „istnieje", dopóki istnieje `hasla-podloz-v2`. **Sprawdza NAZWĘ polecenia, nigdy nie sprawdza, czy WZORZEC mutacji trafia** — dlatego przepuściło R6B-3 | **ZDEGENEROWANY** (R6B-8) |
| `skrypty-uruchamialne.sh:71-87` | scenariusz → procedura | ✔ | **ROZSTRZYGAJĄCY** |
| `skrypty-uruchamialne.sh:90-97` | „nieznana nazwa nie przechodzi po cichu" — kod ≠ 0 | kod ≠ 0 zgodny z {odrzucona nazwa (1)}, {zmiany w indeksie git → `exit 2`, `perturbacje.sh:97-102`}, {projekt zabroniony → `exit 2`, `:83-90`}, {**stos perturbacji nie zgłosił zdrowia w 120 s → `exit 2`**, `:1229-1232`} | **ZDEGENEROWANY** (R6B-8) |

### 2.5 Lista werdyktów `ZDEGENEROWANY`

1. `OdebranieRoliTest.php:636-638` — NOGA 1 (R6B-1)
2. `OdebranieRoliTest.php:568-575` — „granica procesu" w teście POZYTYWNYM (R6B-2)
3. `perturbacje.sh:793-811` — `p_role_zamrozone`, martwa mutacja + pusty dowód (R6B-3)
4. `perturbacje.sh:813-831` — `p_logout_failsafe`, pusty dowód mutacji (R6B-5)
5. `perturbacje.sh:946-982` — `p_uniewaznienie_sid`, martwa mutacja + pusta allowlista (R6B-3)
6. `perturbacje.sh:766-791` — `p_sesja_jawna`, czerwień z innej przyczyny niż badana (R6B-4)
7. `perturbacje.sh:1180-1192` — `p_zamrozenie`, allowlista równa `--filter` (R6B-15)
8. `perturbacje.sh:833-851` i `:874-907` — czerwień nieodróżnialna od NOGI 1 (R6B-13)
9. `bramka.sh:269-270` — `przygotuj_env()` bez odczytu zwrotnego (R6B-7)
10. `bramka.sh:442-460` — aktywna kontrola portów (R6B-11)
11. `skrypty-uruchamialne.sh:90-97` — „nieznana nazwa" (R6B-8)
12. `OdebranieRoliTest.php:233` — 401 bez odczytu bazowego (R6B-6)

---

## 3. ZADANIE D — czy migawki nogi 1 mierzą to, co deklarują

### 3.1 Werdykt: samoocena wykonawcy jest PRAWDZIWA, ale NIEPEŁNA i wskazuje złego winowajcę

Wykonawca napisał (`OdebranieRoliTest.php:600-603`, `PLAN-FAZ.md:58-60`), że migawki nie
odróżniają „tożsamość odtworzona W MAGAZYNIE" od „tożsamość niesiona przez KLIENTA
TESTOWEGO w pamięci procesu", i przypisał to singletonowi menedżera sesji.
**To jest prawda co do wniosku i nieprawda co do mechanizmu.** Nośnikiem nie jest
klient testowy ani menedżer sesji — jest nim **middleware `StartSession`, który sam
jest singletonem kontenera i trzyma REFERENCJĘ do menedżera sprzed `forgetInstance`**.
`app()->forgetInstance('session')` nie ma jak go dosięgnąć. Dowód z przypiętej wersji
frameworka (`laravel/framework v13.24.0`):

```
vendor/laravel/framework/src/Illuminate/Session/SessionServiceProvider.php:22-26
    $this->app->singleton(StartSession::class, function ($app) {
        return new StartSession($app->make(SessionManager::class), …);
    });

vendor/…/Session/Middleware/StartSession.php:157-160
    public function getSession(Request $request) {
        return tap($this->manager->driver(), function ($session) use ($request) {
            $session->setId($request->cookies->get($session->getName()));
        });
    }                      ^^^^^^^^^^^^^^ menedżer Z KONSTRUKTORA, nie z kontenera

vendor/…/Session/Store.php:114-119
    protected function loadSession() {
        $this->attributes = array_replace($this->attributes, $this->readFromHandler());
    }                                     ^^^^^^^^^^^^^^^^^ atrybuty W PAMIĘCI PRZEŻYWAJĄ

vendor/…/Container/Container.php:1731-1734
    public function forgetInstance($abstract) { unset($this->instances[$abstract]); }
    // nie rusza instances[StartSession::class] ani Facade::$resolvedInstance['session']
```

Do tego klient testowy **nie odsyła ciasteczka sesji** — `prepareCookiesForRequest()`
(`vendor/…/Foundation/Testing/Concerns/MakesHttpRequests.php:730-737`) składa wyłącznie
`$defaultCookies` ustawiane ręcznie przez `withCookie()`. Każde `test()->get()` dostaje
więc **nowy losowy identyfikator sesji** i na końcu żądania zapisuje pod nim atrybuty,
które ten sam `Store` niesie w pamięci.

### 3.2 Wszystkie światy zgodne z pomiarem „zniknęło 1 / pojawiło się 1 / status 200"

| # | świat | czy odrzucony przez migawki | czym się różni obserwowalnie |
|---|---|---|---|
| **Ś1** | odświeżanie WSKRZESIŁO tożsamość z refresh tokenu (wniosek pierwotny, już obalony pomiarem kontrolnym) | NIE | nowy klucz zawiera `konta.sub`, a `Http::recorded()` ma ≥1 żądanie do `/token` PO usunięciu |
| **Ś2** | tożsamość niesiona w pamięci przez `Store` middleware'u `StartSession` (singleton), nowy klucz to zapis końca żądania pod świeżo wylosowanym `id` | NIE — **to jest świat, który realnie zachodzi** | nowy klucz zawiera `konta.sub` IDENTYCZNY jak przed usunięciem, a `Http::recorded()` ma ZERO nowych żądań do `/token` |
| **Ś3** | sesja zakładana przez SAMO żądanie: `StartSession` zapisuje sesję nawet bez tożsamości (`_token` CSRF wystarcza, by `Store::save()` coś zapisał) | NIE | nowy klucz **nie zawiera** klucza `konta`; status byłby wtedy 401 |
| **Ś4** | regeneracja identyfikatora: `SesjaKonta::zakoncz()` (`SesjaKonta.php:63-67`) robi `flush()+regenerate()`, więc „zniknął 1 / pojawił się 1" powstaje także na ścieżce POPRAWNEGO zakończenia sesji | NIE | nowy klucz zawiera pustą tablicę atrybutów, status 401 |
| **Ś5** | usunięto NIEWŁAŚCIWY klucz: `RejestrSesji::odczytaj($sid)` zwraca identyfikator z chwili logowania, a żądanie z `:622` utworzyło **drugi, nieujęty w rejestrze** klucz z pełną tożsamością; ten drugi żyje dalej | NIE | po usunięciu w magazynie zostaje klucz z `konta.sub` — mimo „zniknęło 1" |
| **Ś6** | pomyłka przestrzeni kluczy: migawka liczyła klucze cache'u (`RejestrSesji`, `konta:jwks`, `konta:discovery` — `CACHE_STORE=array`) zamiast kluczy sesji (Redis DB 2, prefiks `gabinet-database-gabinet-cache-`) | NIE | zliczanie z podziałem na przestrzenie daje różne liczby |
| **Ś7** | kolejność `forgetInstance` względem `sleep(2)`: `Session::getHandler()` woła FASADĘ, a `Facade::$resolvedInstance['session']` **nie jest czyszczona** przez `forgetInstance` (`Facade.php:232-236`) — więc przyrząd i przedmiot dzielą ten sam obiekt menedżera (reguła C1) | NIE | odczyt handlerem zbudowanym NIEZALEŻNIE od kontenera daje inny wynik niż `Session::getHandler()` |

**Siedem światów, jedna wartość.** Migawka „ile kluczy ubyło / przybyło" jest
dyskryminatorem o gałęzi całkowicie zdegenerowanej. Wykonawca wymienił Ś1, Ś2 i Ś3.
Nie wymienił Ś4 (regeneracja), Ś5 (drugi klucz spoza rejestru — a to on tłumaczy,
dlaczego „usunęliśmy wszystko" jest nieprawdą), Ś6 (przestrzenie kluczy) ani Ś7
(fasada dzieląca obiekt z przedmiotem pomiaru).

**Dodatkowo — obalenie twierdzenia wykonawcy o zakresie problemu.** Wykonawca traktuje
degenerację jako właściwość *migawek w NODZE 1*. W rzeczywistości **ten sam mechanizm
unieważnia deklarację testu POZYTYWNEGO** (`:556-569`): komentarz mówi „Odtwarzamy więc
granicę procesu jawnie", a `forgetInstance('session')` granicy procesu **nie odtwarza**.
Test przechodzi, bo `RejestrSesji::uniewazniona()` znajduje znacznik w BAZIE
(`OdswiezanieSesji.php:74-78`) — czyli mierzy dokładnie to samo, co test
z `:641-665`, a nie to, co deklaruje. To jest dokumentacja kłamiąca o kodzie
w pliku, który wykonawca uznaje za zielony.

### 3.3 PROJEKT ODCZYTU ROZSTRZYGAJĄCEGO (do wdrożenia przez wykonawcę — NIE wdrożony)

Wymagania spełnione: bada ZAWARTOŚĆ, ma przebieg kontrolny, nie dzieli mechanizmu
z przedmiotem.

#### Przyrząd — czytnik NIEZALEŻNY od kontenera Laravela

Nie wolno użyć `Session::getHandler()` ani `app('session')` (Ś7: fasada i kontener
niosą ten sam obiekt, który jest przedmiotem badania). Czytnik ma sięgać do Redisa
**własnym połączeniem** i zwracać ZAWARTOŚĆ, a nie liczbę kluczy:

```
// Tests/Wsparcie/MigawkaSesji.php — SZKIC, nie do wklejenia bez przeczytania
// Ścieżka niezależna: własny klient Redis, własne połączenie, bez SessionManagera.
//  · połączenie: Redis::connection(config('session.connection'))  -> DB 2
//  · prefiks:    config('database.redis.options.prefix') . config('cache.prefix')
//  · dla każdego klucza: odszyfruj (session.encrypt = true → Crypt::decryptString)
//    i zdeserializuj (config('session.serialization') = 'json')
// Zwraca mapę:  id_sesji => ['ma_konta' => bool, 'sub' => ?string,
//                            'access_exp' => ?int, 'refresh' => ?string]
```

#### Cztery odczyty w JEDNYM przebiegu

| moment | co odczytać | nazwa |
|---|---|---|
| M0 — po `zalogujKoordynatora(1)` i po pierwszym `/auth/ja` | migawka zawartości + `Http::recorded()` licznik `/token` | `M0` |
| M1 — bezpośrednio po pętli `destroy()` | migawka zawartości | `M1` |
| M2 — po `sleep(2)`, PRZED ostatnim żądaniem | migawka zawartości | `M2` |
| M3 — po ostatnim `/auth/ja` | migawka zawartości + licznik `/token` + status odpowiedzi | `M3` |

Dodatkowo, **w tym samym żądaniu**: `/auth/ja` ma w środowisku `testing` zwrócić
w odpowiedzi identyfikator sesji obsługującej żądanie (`$request->session()->getId()`),
np. jako pole `_diag_sid_lokalny`. To jest odczyt, którego dziś brakuje: bez niego nie
da się powiedzieć, POD JAKIM kluczem żyje tożsamość, którą serwuje odpowiedź.

#### Odczyt bazowy — przebieg KONTROLNY

Ten sam test uruchomiony **drugi raz z pominiętą pętlą `destroy()`** (parametr
`$usuwacTozsamosc = false`, data provider Pesta z dwoma przypadkami). Wszystkie cztery
migawki i oba liczniki zbierane identycznie. Bez tej gałęzi każdy wynik jest
nieprzypisywalny — dokładnie tak, jak przy liczniku żądań opisanym w
`WYTYCZNE-PRACY.md:319-324`.

#### Pre-flight: każda wartość → dokładnie jeden świat

| odczyt | Ś1 wskrzeszenie | Ś2 pamięć procesu | Ś3 pusta sesja z żądania | Ś4 regeneracja po `zakoncz()` | Ś5 drugi klucz spoza rejestru |
|---|---|---|---|---|---|
| `M1`: liczba kluczy z `ma_konta = true` | 0 | 0 | 0 | 0 | **≥1** |
| `M2 == M1`? | tak | tak | tak | tak | tak |
| `M3`: nowy klucz ma `ma_konta` | **true** | **true** | **false** | **false** | (nie dotyczy — klucz istniał w M1) |
| `M3`: `sub` nowego klucza | równy z `M0` | równy z `M0` | — | — | — |
| `M3 − M0` licznik żądań `/token` | **≥1** | **0** | 0 | 0 | 0 |
| `_diag_sid_lokalny` z M3 obecny w `M1` | nie | nie | nie | nie | **tak** |
| status | 200 | 200 | 401 | 401 | 200 |

**Każda kolumna ma teraz unikalny podpis.** Rozstrzygające są trzy pary:
`(ma_konta w M1)` odcina Ś5; `(ma_konta nowego klucza w M3)` odcina Ś3/Ś4;
`(licznik /token M3−M0)` odcina Ś1 od Ś2.

#### Tabela wyników → wnioski (do wypełnienia po przebiegu)

| przebieg | M1: kluczy z `ma_konta` | M3: nowy klucz `ma_konta` | M3: `sub` == M0 | Δ `/token` | `_diag_sid` ∈ M1 | status | ŚWIAT |
|---|---|---|---|---|---|---|---|
| główny (z `destroy`) | | | | | | | |
| **kontrolny (bez `destroy`)** | ≥1 z definicji | true | tak | 0 lub ≥1 | tak | 200 | odczyt bazowy |

Interpretacja prerejestrowana:
- `M1 = 0` ∧ `M3.ma_konta = true` ∧ `Δ/token = 0` → **Ś2**: wada PRZYRZĄDU.
  Naprawa jednolinijkowa i falsyfikowalna: dołożyć
  `app()->forgetInstance(\Illuminate\Session\Middleware\StartSession::class)`
  obok dwóch istniejących `forgetInstance` (`OdebranieRoliTest.php:630-631` i `:568-569`).
  Po naprawie **zmierzyć ponownie** — jeśli NOGA 1 zzielenieje, a test POZYTYWNY
  zostanie zielony, potwierdzone.
- `M1 = 0` ∧ `M3.ma_konta = true` ∧ `Δ/token ≥ 1` → **Ś1**: realny defekt systemu
  mimo wąskiego gardła §2 — wtedy i tylko wtedy wolno ruszać kod produkcyjny.
- `M1 ≥ 1` → **Ś5**: `RejestrSesji` nie zna wszystkich sesji użytkownika; to jest
  defekt SYSTEMU (back-channel logout kasuje podzbiór sesji), niezależny od nogi 1.
- `M3.ma_konta = false` ∧ status 200 → sprzeczność wewnętrzna: pomiar czyta inną
  przestrzeń kluczy niż aplikacja (Ś6) — przyrząd do przepisania.

#### Zabezpieczenia przyrządu (bez nich pomiar jest znowu nieprzypisywalny)

1. Migawka **musi** asertować, że w M0 widzi co najmniej jeden klucz z `ma_konta=true`
   — inaczej „0 w M1" jest zgodne z „czytnik nic nie widzi" (to jest ta sama asercja
   „miałem czego szukać", którą projekt stosuje w `RetencjaTest` i `SesjaBezJawnychDanychTest`).
2. Migawka nie może wołać `Session::`, `app('session')`, `session()` — reguła C1.
3. `_diag_sid_lokalny` ma być odsłaniany wyłącznie w `local`/`testing`, tym samym
   warunkiem co `LogowanieController::odmowa()` (`LogowanieController.php:219`).
4. Cała para (główny + kontrolny) w JEDNYM `it(...)->with([...])`, żeby nie dało się
   zostawić samej gałęzi głównej.

**NIE WDROŻYŁEM tego.** Żaden plik testu ani kodu produkcyjnego nie został zmieniony.

---

## 4. ZADANIE E — czego D-2026-08-08-27 nie wypisał

D-2026-08-08-27 wymienia 7 zmiennych z `phpunit.xml` i dzieli kontrole na „(a) z pokryciem"
i „(b) bez pokrycia". Poniżej **RÓŻNICA**: kontrole puste albo osłabione, których ten
przegląd nie nazwał.

| # | kontrola / mechanizm | co miała badać | dlaczego jest pusta lub osłabiona | waga |
|---|---|---|---|---|
| **E-1** | `WzmacniaczZadanTest.php` (cały plik, 4 testy) + `KontaOidc::jwksDlaKid()` (`KontaOidc.php:126`) | bramka częstotliwości oparta o **ATOMOWE `Cache::add`** (D-2026-08-07-19) — ochrona Kont Niepodzielni przed wzmacniaczem z publicznego, nieuwierzytelnionego endpointu | `CACHE_STORE=array`: `ArrayStore` żyje w JEDNYM procesie PHP. Test „100 tokenów → 1 pobranie JWKS" mierzy pętlę w jednym procesie. Właściwość, o którą chodzi — atomowość między równoległymi workerami php-fpm — **nie jest mierzona wcale**. D-27 mówi ogólnie „wszystko, co bada zachowanie cache'u", ale nie nazywa tej kontroli, więc czytelnik dziennika uznaje ją za dowiedzioną | **wysoka** — to jedyna kontrola chroniąca cudzy system |
| **E-2** | `RejestrSesji` (`RejestrSesji.php:25-34`, `:41-74`, `:79-82`) i wszystkie asercje `skasowane_sesje === 1` (`OdebranieRoliTest.php:273`, `:310`, `:555`) | mapa `sid → sesje lokalne`, jedyny mechanizm, po którym back-channel logout znajduje sesję do skasowania | mapa mieszka w `Cache::` czyli w suicie w `array`. Cztery wymagania trwałości, które projekt sam sformułował dla znaczników bezpieczeństwa (`WYTYCZNE-PRACY.md:330-341`), zastosowano do `uniewaznione_sesje` (baza) i **nie zastosowano do `RejestrSesji`** (cache z TTL 86400 s, podlegający `cache:clear`, restartowi i eksmisji z D-2026-08-08-28). Utrata rejestru daje `skasowane_sesje = 0` — cicho | **wysoka** — nienazwana ani w D-27, ani w D-28 |
| **E-3** | `OdebranieRoliTest.php:236-284` („zabija sesję NATYCHMIAST"), `:286-312`, `:314-336`, **`:338-416` (ADWERSARIALNY)** | zachowanie MAGAZYNU SESJI: czy sesja ofiary przeżywa atak wymuszonego wylogowania | Te cztery testy **nie przełączają sterownika** — `config(['session.driver'=>'redis'])` występuje tylko w liniach 545, 619, 649, 675. Biegną więc na `ArraySessionHandler`. D-27 §(a) twierdzi wprost: *„Magazyn sesji — testy, które go badają …, sięgają po sterownik produkcyjny jawnie"*. **To zdanie jest nieprawdziwe dla czterech testów**, w tym dla jedynego testu adwersarialnego | **wysoka** — twierdzenie D-27 obalone wprost |
| **E-4** | cała warstwa OIDC: `LogowanieTest`, `OdebranieRoliTest`, `WzmacniaczZadanTest`, `WalidatorTokenuTest` | rozmowa z IdP: TLS, `KEYCLOAK_CA_BUNDLE`, rozdział issuera publicznego i wewnętrznego (§3a kontraktu), limity czasu, przekierowania, obsługa błędów sieci | `Http::fake()` (`Pest.php:76-79`, `OdebranieRoliTest.php:60-77`) to **podmieniony sterownik HTTP**. D-27 nie wymienia go w ogóle, choć jest to najszerzej używana atrapa w projekcie. `KEYCLOAK_CA_BUNDLE` nie jest sprawdzany żadnym testem | **wysoka** |
| **E-5** | `RefreshDatabase` (`Pest.php:24`) | izolacja transakcyjna | Baza jest prawdziwa (D-27 słusznie), ale **cała suita Feature biegnie w JEDNEJ nieskomitowanej transakcji**. Puste stają się: zachowanie przy COMMIT, blokady międzysesyjne (`SELECT … FOR UPDATE`), `advisory locks`, wyzwalacze `DEFERRED`, oraz **wymagany przez CLAUDE.md §6 test 100 równoczesnych żądań** — nie da się go napisać w tej suicie bez wyłączenia `RefreshDatabase`. D-27 pisze „DB_CONNECTION — NIEPODMIENIONE", co jest prawdą o sterowniku i nieprawdą o modelu współbieżności | **średnia dziś, blokująca w F2** |
| **E-6** | `LogowanieController::odmowa()` (`LogowanieController.php:219-222`) | ukrywanie mapy kontroli na produkcji („gotowy oracle dla kogoś, kto stroi podrobiony token") | `APP_ENV=testing` jest **wymuszony** w `phpunit.xml`, więc gałąź produkcyjna (`$tresc` bez `kontrole`/`nieudane`) **nie jest wykonana ANI RAZ**. Kontrola bezpieczeństwa bez jednego przebiegu | **średnia** |
| **E-7** | `SzkieletTest` „nie pozwala poczcie wyjść na zewnątrz" (`SzkieletTest.php:118-127`) + `BlokadaWysylkiTest` | twarda blokada wysyłki poza produkcją (CLAUDE.md §10) | `MAIL_MAILER=array` i `GABINET_BLOKADA_WYSYLKI=true` są **wymuszone** w `phpunit.xml`. `array` jest już na liście `STEROWNIKI_NIEWYSYLAJACE` (`BlokadaWysylki.php:23`), więc asercja `config('mail.default')` ∈ lista przechodzi **bez udziału blokady**. `BlokadaWysylkiTest` testuje czystą funkcję, a nie jej podłączenie. D-27 mówi „blokada sprawdzana osobno flagą" — ale nie ma testu, że przy `MAIL_MAILER=smtp` **i** włączonej fladze zbudowany transport nie jest ESMTP | **średnia** |
| **E-8** | `Cache::flush()` w `OdebranieRoliTest.php:657` („NAJPROZAICZNIEJSZE ZDARZENIE OPERACYJNE") | odporność znacznika unieważnienia na `cache:clear` | `Cache::flush()` czyści magazyn `array`, a nie Redisa. Produkcyjne `cache:clear` czyści Redis DB 1 i **nie dotyka DB 2** — czyli test odtwarza scenariusz OSTRZEJSZY niż produkcyjny, ale nie ten deklarowany. Wniosek jest bezpieczny, deklaracja nie | **niska** (kierunek konserwatywny) |
| **E-9** | `SladWylogowania` (`SladWylogowania.php:97-101`) | ślad wejścia/awarii/odmów niezależny od cache'u | To NIE jest podmiana sterownika (dobrze — C1 zamknięte), ale jest to **stan globalny współdzielony między testami w jednym przebiegu**, sprzątany tylko przez jawne `wyczysc()` w 4 z 12 testów. Asercja `wejscia()===1` zależy od kolejności testów. Nie wymienione nigdzie | **niska** |
| **E-10** | `Crypt` / `APP_KEY` | szyfrowanie kolumn wrażliwych i ID tokenu (RODO art. 9) | klucz pochodzi ze środowiska efemerycznego bramki (`bramka.sh:246-250`) — to prawdziwy sterownik ✔. **Ale** `p_sesja_jawna` nie mierzy tego, co deklaruje (R6B-4), więc realny wyciek do Redisa nie ma dziś kontroli dowodzącej czerwieni na czystym klonie | **średnia** |
| **E-11** | `BROADCAST_CONNECTION=null`, `QUEUE_CONNECTION=sync`, `BCRYPT_ROUNDS=4` | — | **wymienione poprawnie w D-27**; wpisuję dla kompletności listy negatywnej | — |

**Podsumowanie różnicy:** D-27 wymienił 3 pozycje bez pokrycia (cache, kolejki, poczta).
Powyżej jest **10 dalszych**, z czego cztery (E-1…E-4) dotyczą kontroli, które dziś
uchodzą za dowiedzione, a jedna (E-3) **obala zdanie zapisane w samym D-27**.

---

## 5. ZADANIE G — czy „wada przyrządu" nie bywa wadą systemu w przebraniu

**Odpowiedź: tak, w trzech nazwanych przypadkach — i wzorzec jest jednorodny.**
Atrybucja „przyrząd" jest w Gabinecie zwykle *poprawna co do bezpośredniej przyczyny*,
ale **zamyka śledztwo o jeden krok za wcześnie**: znajduje kłamiący instrument i nie
pyta, co ten instrument przez cały czas przykrywał.

### G-1. `SESSION_ENCRYPT` / V-2 — „dwa środowiska pomiarowe" przykryły defekt produkcyjny

`WYTYCZNE-PRACY.md:230-236` opisuje V-2 jako wadę przyrządu: `przygotuj_env()` używało
`.env` dewelopera. Prawda. Ale **treść znaleziska była systemowa**:
`config/session.php` miał `env('SESSION_ENCRYPT', false)` jako wartość DOMYŚLNĄ, czyli
każde środowisko bez tej zmiennej zapisywało e-mail pacjenta do Redisa jawnie
(RODO art. 9). To nie jest wada przyrządu — to wada produktu, którą przyrząd
przypadkiem odsłonił.

**Dowód, że atrybucja kosztowała:** naprawę zastosowano **wyłącznie do `bramka.sh`**
(`bramka.sh:207-215, 220-258` — własny plik środowiska, `--env-file`, `GABINET_PLIK_ENV`).
`perturbacje.sh` do dziś tego nie ma:

```
skrypty/perturbacje.sh:124-126
dc() { GABINET_PREFIX=… GABINET_PORT_HTTP=… docker compose -p "$PROJEKT" -f … "$@"; }
        ^ brak --env-file, brak GABINET_PLIK_ENV
docker-compose.yml:73
    - ${GABINET_PLIK_ENV:-./.env}:/srv/gabinet/.env:ro
```

Skutek: **stos perturbacji montuje `.env` DEWELOPERA** (z prawdziwymi sekretami)
i interpoluje zmienne compose z tego samego pliku. Czyli dokładnie to środowisko
pomiarowe, którego V-2 zakazało — tyle że w narzędziu, które ma dowodzić, że kontrole
umieją zaświecić. To także naruszenie reguły „klon weryfikatora NIGDY nie trzyma
prawdziwych sekretów" (`WYTYCZNE-PRACY.md:228-230`), przeniesione na przebieg
perturbacji.

### G-2. `p_puls` / W-15 — „kłamstwo dokumentacji" przykryło sygnał zdrowia w magazynie ulotnym

`perturbacje.sh:1106-1115` opisuje W-15 jako wadę przyrządu (komentarz obiecywał
zatrzymanie harmonogramu, kodu nie było). Prawda. Ale pod spodem leży własność systemu:
**puls harmonogramu — jedyny sygnał odróżniający „proces stoi" od „pętla umarła" —
mieszka w `Cache`** (`perturbacje.sh:1118`: `Cache::forget('gabinet:puls-harmonogramu')`).
To ten sam magazyn, o którym D-2026-08-08-28 mówi, że przy `maxmemory ≠ 0` będzie
eksmitowany. Znika puls ⇒ bramka i sonda operacyjna meldują awarię harmonogramu,
której nie ma; a przy `cache:clear` w trakcie deployu — tak samo. Ta konsekwencja
nie jest nigdzie zapisana, bo epizod został zaksięgowany jako „przyrząd".

### G-3. NOGA 1 — atrybucja „przyrząd" jest TRAFNA, ale przykrywa zero pokrycia wymagania B8

Tu wykonawca ma rację co do przyrządu (§3 potwierdza: nośnikiem jest singleton
`StartSession`). **Ale ta trafność ma cenę.** Stan faktyczny na `49131d8`:

- wymaganie B8 „odświeżanie jest OPERACJĄ NA ISTNIEJĄCEJ TOŻSAMOŚCI" **nie ma ani
  jednego zielonego testu**;
- jedyny test tego wymagania jest czerwony i oznaczony „NIEROZSTRZYGNIĘTE";
- trzy perturbacje, które miały dowodzić kontroli tożsamości (`p_role_zamrozone`,
  `p_uniewaznienie_sid`, `p_logout_failsafe`), są dziś **martwe albo niefalsyfikowalne**
  (R6B-3, R6B-5) — a dwie z nich zepsuła **ta sama przebudowa §2**, którą wykonano
  na podstawie obalonego wniosku;
- test POZYTYWNY, uznawany za zielony dowód BLK-22, mierzy nie to, co deklaruje (R6B-2).

Innymi słowy: „to wada przyrządu" jest prawdą o NODZE 1 i jednocześnie etykietą,
pod którą schowała się **grupa czterech kontroli tożsamości bez mocy dowodowej**.
Zgodnie z regułą D-0013 przyjętą w tym projekcie („asercja bez dowodu, że umie
zaświecić czerwono, jest traktowana jak nieistniejąca") należy dziś powiedzieć wprost:
**kontrola odbierania roli i kontrola unieważnienia po `sid` nie istnieją.**

### G-4. Kontrprzykłady — gdzie atrybucja „przyrząd" jest w pełni uczciwa

Dla równowagi, bo jednorodny ciąg w drugą stronę byłby tak samo podejrzany:
`U-2`/`U-6` (zamek, licznik testów), `V-11` (`STAN_WYJSCIOWY` nieużyta),
`V-5` (`skrypty-uruchamialne.sh` nie był wołany), przeniesienie `SladWylogowania`
do pliku — to są czyste, poprawnie zaksięgowane wady przyrządu, każda z perturbacją
w obie strony. Wzorzec „winny przyrząd" nie jest więc fałszywy jako całość; jest
**przedwcześnie domykany** dokładnie tam, gdzie pod spodem leży własność produktu.

---

## 6. Znaleziska

### R6B-1 · NOGA 1: dyskryminator z siedmioma światami; przyczyna czerwieni jest w harnessie
**Co.** Test `OdebranieRoliTest.php:578-639` mierzy 401/200 po usunięciu tożsamości.
Wartość 200 jest zgodna z co najmniej siedmioma światami (§3.2). Mechanizmem, który
realnie niesie tożsamość, jest **singleton `StartSession` trzymający referencję do
menedżera sprzed `forgetInstance`**, a nie „klient testowy".
**DOWÓD.** `vendor/laravel/framework/src/Illuminate/Session/SessionServiceProvider.php:22-26`
(`$this->app->singleton(StartSession::class, fn ($app) => new StartSession($app->make(SessionManager::class), …))`),
`…/Session/Middleware/StartSession.php:157-160` (`$this->manager->driver()` — menedżer
z konstruktora), `…/Session/Store.php:116` (`array_replace($this->attributes, $this->readFromHandler())`),
`…/Container/Container.php:1731-1734` (`forgetInstance` = `unset($this->instances[$abstract])`,
nie rusza `instances[StartSession::class]`), `…/Foundation/Testing/Concerns/MakesHttpRequests.php:730-737`
(brak przenoszenia ciasteczek między żądaniami). Test:
`OdebranieRoliTest.php:630-631` forget wyłącznie `'session'` i `'session.store'`.
**Waga:** wysoka. **Blokuje:** tak — to jedyny czerwony krok bramki.

### R6B-2 · Test POZYTYWNY BLK-22 mierzy inne zjawisko, niż deklaruje jego komentarz
**Co.** `OdebranieRoliTest.php:556-575` deklaruje „Odtwarzamy więc granicę procesu jawnie"
i twierdzi, że 401 dowodzi pustego magazynu. Z R6B-1 wynika, że `Store` przeżywa
`forgetInstance`, więc 401 pochodzi ze znacznika w BAZIE (`OdswiezanieSesji.php:74-78`).
Test dubluje `:641-665` i **nie** dowodzi tego, co przypisuje mu `PLAN-FAZ.md`.
**DOWÓD.** jak wyżej + `OdswiezanieSesji.php:74-78`, `RejestrSesji.php:90-100`.
**Waga:** wysoka (dokumentacja kłamiąca o kodzie w pliku uznanym za zielony).
**Blokuje:** tak — to jedna z dwóch nóg pary negatywnej BLK-22.

### R6B-3 · DWIE mutacje w `perturbuj.py` są MARTWE po przebudowie §2; jedna melduje sukces
**Co.** `role_zamrozone()` i `uniewaznienie_po_sid()` szukają wzorców, których w kodzie
już nie ma — zostały zastąpione przy wprowadzeniu `SesjaKonta`/`TozsamoscSesji`.
`podmien()` rzuca `SystemExit`, ale **żaden z dwóch scenariuszy nie sprawdza kodu
wyjścia `perturbuj`**.
**DOWÓD.**
```
perturbuj.py:292-296   szuka: "if (! $this->wymagaOdswiezenia($konta)) {\n            return $konta;\n        }"
OdswiezanieSesji.php:80-82  jest: "if (! $this->wymagaOdswiezenia($tozsamosc)) {  return $tozsamosc->dane;  }"

perturbuj.py:406-410   szuka: "if (RejestrSesji::uniewazniona(Typy::napis($konta['sid'] ?? null))) {"
OdswiezanieSesji.php:74     jest: "if (RejestrSesji::uniewazniona($tozsamosc->sid())) {"

perturbacje.sh:802  `perturbuj role-zamrozone`      — bez `|| { NIEUDANE; return; }`
perturbacje.sh:961  `perturbuj uniewaznienie-po-sid` — bez `|| { NIEUDANE; return; }`
(dla porównania: perturbacje.sh:417, 438, 457, 468 kod wyjścia SPRAWDZAJĄ)
```
Dalej `p_role_zamrozone` (`:804-805`) „dowodzi" mutacji przez
`! grep -q 'wymagaOdswiezenia($konta)'` — czyli przez **brak wzorca, którego nigdy nie
ma** — a `oczekuj_czerwone` (`:807-808`) uruchamia cały `OdebranieRoliTest.php`, który
jest trwale czerwony przez NOGĘ 1. **Wynik: ✓ przy zerowej mutacji.**
`p_uniewaznienie_sid` ratuje się tylko tym, że jego dowód jest negacją wzorca, który
ISTNIEJE (`:963-964`) — więc poprawnie melduje „MUTACJA NIE WESZŁA W ŻYCIE".
**Waga:** wysoka — to jest dokładnie klasa „perturbacja bez mutacji" z tabeli
`WYTYCZNE-PRACY.md:123-130`, uznana w projekcie za zamkniętą.
**Blokuje:** tak.

### R6B-4 · `p_sesja_jawna` zalicza się z INNEJ przyczyny niż badana (P25)
**Co.** Scenariusz deklaruje „test znajduje e-mail i ID token JAWNIE w Redisie"
(`perturbacje.sh:779`). Mutacja zmienia **wartość domyślną** `env('SESSION_ENCRYPT', true)`
→ `false` (`perturbuj.py:281`). Na każdym środowisku, w którym `SESSION_ENCRYPT=true`
stoi w pliku środowiska — a `.env.example:` ma tę linię i bramka buduje env właśnie
z niej (`bramka.sh:242`) — **wartość domyślna nigdy nie wchodzi w życie**, sesja
pozostaje zaszyfrowana, a czerwień pochodzi z asercji statycznej na TREŚCI PLIKU
(`SesjaBezJawnychDanychTest.php:143-150`), nie z wycieku.
**DOWÓD.** `.env.example` (sekcja Redis): `SESSION_ENCRYPT=true`;
`bramka.sh:242` `cp .env.example "$PLIK_ENV"`; brak `--przyczyna` w `perturbacje.sh:779-780`.
**Waga:** średnia-wysoka — jedyna perturbacja chroniąca dane osobowe w magazynie sesji
nie dowodzi dziś czerwieni z badanego powodu.
**Blokuje:** nie, ale wymaga `--przyczyna` skopiowanej z komunikatu
`'E-mail pacjenta odczytywalny w magazynie sesji.'` oraz mutacji zdejmującej zmienną
ze środowiska, a nie tylko wartość domyślną z kodu.

### R6B-5 · `p_logout_failsafe`: dowód mutacji na symbolu, którego w pliku nie ma
**Co.** `perturbacje.sh:824-825` dowodzi mutacji przez
`! grep -q 'sidNiezweryfikowany' backend/app/Http/Controllers/BackchannelLogoutController.php`.
Symbol `sidNiezweryfikowany` **nie występuje w tym pliku wcale** — istnieje wyłącznie
jako `WalidatorTokenu::sidNiezweryfikowany()` (`WalidatorTokenu.php:157`).
Negacja jest więc prawdziwa zawsze; dowód nie może zaświecić.
**DOWÓD.** `grep -rn "sidNiezweryfikowany" backend/` → jedno trafienie:
`backend/app/Tozsamosc/WalidatorTokenu.php:157`. Handler (`BackchannelLogoutController.php:42-89`)
w ogóle nie odwołuje się do `sid` w gałęzi awaryjnej — to skutek naprawy
„wymuszone wylogowanie".
**Waga:** średnia (mutacja dziś działa, więc perturbacja jest przypadkiem skuteczna;
jej dowód nie ma jednak mocy i nie ostrzeże przy następnym dryfie).
**Blokuje:** nie.

### R6B-6 · Test „kończy sesję, gdy IdP odmawia odświeżenia" nie ma odczytu bazowego
**Co.** `OdebranieRoliTest.php:226-234` — jedyny test w pliku bez asercji stanu
wyjściowego. 401 jest zgodne z „odmowa IdP zakończyła sesję" ORAZ z „logowanie się
nie powiodło i sesji nigdy nie było". `zalogujKoordynatora()` (`:158-162`) nie asertuje
odpowiedzi callbacku.
**DOWÓD.** Porównaj z `:241`, `:297`, `:350-354`, `:550`, `:622`, `:652`, `:678` —
wszystkie pozostałe testy mają odczyt bazowy; `:226-234` go nie ma.
**Waga:** średnia. **Blokuje:** nie.

### R6B-7 · `przygotuj_env()` nie odczytuje z powrotem pliku, na którym opiera cały przebieg
**Co.** Sześć `sed -i` (`bramka.sh:249-257`). `sed`, który nie trafił w żadną linię,
kończy się **sukcesem**. Krok jest zielony także wtedy, gdy plik środowiska nie ma
podmienionego hasła, `APP_KEY`, prefiksu ani portów. To jest naruszenie reguły
zapisanej w tym samym repozytorium: „Po zapisie, na którym opiera się decyzja —
odczytaj plik z powrotem" (`WYTYCZNE-PRACY.md:141-142`) oraz „`sed`/`str.replace`,
które nic nie znalazły, kończą się SUKCESEM" (`WYTYCZNE-PRACY.md:93-95`).
**DOWÓD.** `bramka.sh:220-258` — brak jakiejkolwiek asercji po `sed`; kontrastuj
z `perturbuj.py:48-59`, gdzie ta sama klasa błędu jest zamknięta przez `podmien()`.
**Waga:** średnia-wysoka (przy cichym niepowodzeniu środowisko pomiarowe jest inne,
niż deklaruje — R6B-4 pokazuje, że to nie jest ryzyko teoretyczne).
**Blokuje:** nie.

### R6B-8 · `skrypty-uruchamialne.sh`: kontrola „nieznana nazwa" przechodzi na cudzym kodzie wyjścia
**Co.** `skrypty-uruchamialne.sh:90-97` uznaje `kod != 0` za dowód, że `perturbacje.sh`
odrzuca nieznaną nazwę. Ale `perturbacje.sh` kończy się kodem 2 **przed** dojściem do
rozdzielacza w trzech innych sytuacjach: zabroniony projekt (`:83-90`), niepuste
`git diff --cached` (`:97-102`) i **nieudane postawienie stosu perturbacji w 120 s**
(`:1229-1232`). Ostatni przypadek jest w bramce regułą, nie wyjątkiem: krok
`bramka.sh:343-349` uruchamia ten skrypt, a stos `gabinet-perturbacje` w trakcie
przebiegu bramki zwykle nie stoi.
Dodatkowo krok 2 (`:51-57`) dopasowuje nazwę polecenia **podciągiem**, więc
`hasla-podloz` „istnieje" dopóki istnieje `hasla-podloz-v2`.
**Skutek uboczny wart osobnej uwagi:** ten krok bramki potrafi **postawić drugi stos
Dockera** na `.env` dewelopera (patrz G-1), w środku przebiegu, który ma być izolowany.
**DOWÓD.** `bramka.sh:343-349`; `skrypty-uruchamialne.sh:90-97`; `perturbacje.sh:83-90, 97-102, 1206-1236`.
**Waga:** średnia-wysoka. **Blokuje:** nie, ale unieważnia deklarowany zakres kroku 7 bramki.

### R6B-9 · `RejestrSesji` łamie własne cztery wymagania trwałości znacznika
**Co.** Projekt sformułował cztery wymagania magazynu negatywnej asercji bezpieczeństwa
(`WYTYCZNE-PRACY.md:330-341`) i zastosował je do `uniewaznione_sesje` (baza).
`RejestrSesji` — bez którego back-channel logout nie znajdzie ani jednej sesji —
został w cache'u, z TTL 86400 s i bez odporności na `cache:clear`/eksmisję.
W suicie mierzy go magazyn `array`, więc żaden test nie może tego pokazać.
**DOWÓD.** `RejestrSesji.php:22-34` (`Cache::put(..., 86400)`), `:79-82` (`Cache::get`),
`:71` (`Cache::forget`); kontra `:110-120` (znacznik → `DB::table('uniewaznione_sesje')`).
Asercje uzależnione: `OdebranieRoliTest.php:263, 273, 310, 555`.
**Waga:** wysoka (fail-open: `skasowane_sesje = 0` bez żadnego objawu).
**Blokuje:** nie — ale należy do tej samej klasy, którą D-28 domyka dla sesji.

### R6B-10 · Puls harmonogramu — sygnał zdrowia w magazynie podlegającym eksmisji
**Co.** `gabinet:puls --sprawdz` (krok bramki `:416-426`) czyta wpis z cache'u
(`perturbacje.sh:1118, 1127-1129`). D-2026-08-08-28 nazywa wyzwalacz eksmisji dla sesji
i nie zauważa, że ten sam wyzwalacz kasuje sygnał zdrowia harmonogramu.
**DOWÓD.** `perturbacje.sh:1118` `Cache::forget('gabinet:puls-harmonogramu')`;
`docs/DECYZJE.md` D-2026-08-08-28 (brak wzmianki o pulsie).
**Waga:** niska-średnia. **Blokuje:** nie.

### R6B-11 · Aktywna kontrola portów nie może wykryć wystawionego Postgresa ani Redisa
**Co.** `bramka.sh:448-452` sprawdza osiągalność portów **żądaniem HTTP**. Postgres
i Redis nie mówią po HTTP, więc `curl` zwróci błąd niezależnie od tego, czy port jest
wystawiony na 0.0.0.0. Wartość „nie odpowiada żaden port" jest zgodna z „nic nie
wystawione" oraz z „PG/Redis wystawione na świat".
Ponadto cały krok jest **milcząco pomijany**, gdy `gethostbyname(gethostname())` zwraca
`127.*` albo gdy `php` nie jest na PATH hosta (`:444-445`) — i krok **mimo to przechodzi**.
**DOWÓD.** `bramka.sh:442-460`.
**Waga:** średnia (deklarowany „drugi tor" kontroli ekspozycji jest jednotorowy).
**Blokuje:** nie.

### R6B-12 · Perturbacje dowodzą podłóg 100/300, a bramka egzekwuje 170/590
**Co.** `bramka.sh:90, 93` ustawia `MINIMUM_TESTOW=170`, `MINIMUM_ASERCJI=590`.
Perturbacje sprawdzają progi wpisane osobno: `perturbacje.sh:338, 352` (100)
i `:724` (100 / 300). Podłogi realnie egzekwowane przez bramkę **nie mają perturbacji**
— czyli wg D-0013 nie są dowiedzione.
**DOWÓD.** jak wyżej. **Waga:** niska-średnia. **Blokuje:** nie.

### R6B-13 · Pięć perturbacji tożsamości nie może dziś zaświecić na czerwono „z badanego powodu"
**Co.** `p_role_zamrozone`, `p_logout_failsafe`, `p_zrodlo_rol`, `p_id_token_w_sesji`
(×2) i `p_uniewaznienie_sid` uruchamiają **cały** `tests/Feature/OdebranieRoliTest.php`.
Plik jest trwale czerwony (NOGA 1), więc `oczekuj_czerwone` musi zwrócić ✓; jedynym
zawężeniem jest `--przyczyna`, którego cztery z nich nie mają, a piąty ma pusty
(patrz R6B-3). Symetrycznie: „kierunek odwrotny" w `p_uniewaznienie_sid` (`:975-981`)
**nie może dziś przejść** — będzie meldował wadę tam, gdzie jej nie ma.
**DOWÓD.** `perturbacje.sh:807-808, 827-828, 847-848, 893-894, 903-904, 968-970, 975`;
`PLAN-FAZ.md:12-16`. **Waga:** wysoka. **Blokuje:** tak (wraz z R6B-1).

### R6B-14 · Bramka częstotliwości JWKS: kontrola atomowości mierzona magazynem nieatomowym
Patrz E-1. **DOWÓD.** `KontaOidc.php:126` (`Cache::add`), `phpunit.xml` (`CACHE_STORE=array`),
`WzmacniaczZadanTest.php` (cały plik, jeden proces).
**Waga:** wysoka. **Blokuje:** nie (do zamknięcia razem z suitą na realnym cache'u).

### R6B-15 · `--przyczyna` równa `--filter` albo nazwie testu — allowlisty, które nic nie zawężają
**Co.** Sześć z ośmiu allowlist to napisy występujące w wyjściu Pesta **bezwarunkowo**
(nazwa testu, nazwa klasy, wartość `--filter`), więc nie odróżniają czerwieni z badanej
asercji od czerwieni z czegokolwiek innego. Dotyczy miejsc, które
`WYTYCZNE-PRACY.md:257-260` wskazuje jako te, gdzie „podłoga nie wystarcza".
**DOWÓD.**
```
perturbacje.sh:309  --przyczyna "granicę okna"   ↔ --filter="granicę okna" ↔ OcenaAnulacjiTest.php:35 (nazwa testu)
perturbacje.sh:420  --przyczyna "BrakWlasnychHasel" ↔ nazwa klasy w nagłówku Pesta
perturbacje.sh:441  jw.
perturbacje.sh:969  --przyczyna "POZYTYWNY"      ↔ OdebranieRoliTest.php:530 (nazwa testu)
perturbacje.sh:1176 --przyczyna "Bramki|marker"  ↔ --filter="marker"
perturbacje.sh:1190 --przyczyna "ZAMROŻONĄ"      ↔ --filter="ZAMROŻONĄ" ↔ OcenaAnulacjiTest.php:152 (nazwa testu)
```
Zawężają realnie tylko dwie: `:868` „WYMUSZONE WYLOGOWANIE" (komunikat asercji,
`OdebranieRoliTest.php:407`) i `:930` „PRZEŻYŁ zadanie retencyjne" (komunikat asercji,
`RetencjaWykonanieTest.php:70`).
**Waga:** wysoka dla `:969` (tożsamość), średnia dla reszty. **Blokuje:** nie.

### R6B-16 · Perturbacje mielą `.env` dewelopera — V-2 zamknięte tylko po stronie bramki
Patrz G-1. **DOWÓD.** `perturbacje.sh:124-126` vs `bramka.sh:207-215`; `docker-compose.yml:73`.
**Waga:** wysoka (pomiar + higiena sekretów). **Blokuje:** nie, ale unieważnia
porównywalność wyników perturbacji między maszyną wykonawcy a czystym klonem.

### R6B-17 · `p_statyka`: podmiana-widmo obok podmiany działającej
**Co.** `perturbacje.sh:366` — wzorzec `sed` (`… $domyslny = .."..): string`) nie pasuje
do rzeczywistej sygnatury `public static function napis(mixed $wartosc, string $domyslny = ''): string`
(`Typy.php:23`). Ten `sed` jest cichym no-opem; perturbacja działa wyłącznie dzięki
funkcji dopisanej w `:367`.
**Waga:** niska. **Blokuje:** nie.

---

## 7. Twierdzenia, których NIE UDAŁO SIĘ obalić

1. **Wąskie gardło §2 jest zrealizowane.** `SesjaKonta::KLUCZ = 'konta'` ma dokładnie
   jednego pisarza; `zaktualizuj()` przyjmuje `TozsamoscSesji`, którego konstruktor jest
   prywatny, a jedyna droga do instancji (`TozsamoscSesji::zMagazynu()`, `:39-49`) zwraca
   `null` przy pustym `sub`. Ścieżka „brak rekordu → utwórz" w `OdswiezanieSesji`
   jest **niewywoływalna**, nie „zabroniona warunkiem". Sprawdzone wprost:
   `grep -rn "session()->put('konta'\|SesjaKonta::" backend/app` daje jednego pisarza.
2. **Wycofanie wniosku o wskrzeszeniu było poprawne.** Moja analiza (§3) niezależnie
   potwierdza, że 200 nie pochodzi ze ścieżki odświeżania — i wskazuje ten sam kierunek,
   który wykonawca sam sobie zarzucił.
3. **`SladWylogowania` w pliku realnie zamyka C1.** Trzy sygnały (`wejscia`, `awarie`,
   `odmowy`) idą ścieżką niezależną od cache'u i od magazynu sesji; testy `:307-311`
   i `:401-402` używają ich dokładnie tak, jak trzeba (dowód, że handler wszedł
   I wpadł w badaną gałąź).
4. **Test adwersarialny (`:338-416`) jest merytorycznie poprawny** — ma dowód wejścia
   w ścieżkę awaryjną, asercję bezpieczeństwa i zamknięty zbiór dopuszczalnych statusów.
   Jego jedyna wada to sterownik `array` (E-3), nie konstrukcja.
5. **`p_retencja_wykonanie` jest wzorcowo skonstruowaną perturbacją** — allowlista
   z komunikatu asercji, dowód mutacji złożony z dwóch warunków, pomiar odwrotny
   pokazujący, że kontrola strukturalna tego NIE łapie.
6. **`RetencjaWykonanieTest` nie jest kształtem (c)** — uzasadnienie z D-2026-08-08-25
   („producent, wykonawca i obserwator to trzy różne ścieżki") wytrzymuje czytanie kodu.
7. **`perturbacje-powtarzalne.sh` naprawił V-7 i V-11** — kod wyjścia wchodzi do
   werdyktu (`:74-76`), `STAN_WYJSCIOWY` jest realnie użyty (`:88-90`).
8. **`RejestrSesji::uniewazniona()` jest fail-closed w praktyce** — `:667-698` dowodzi
   tego przez zerwanie możliwości rozstrzygnięcia. Zastrzeżenie do formy (akceptacja 500)
   nie podważa wyniku.
9. **`.env.example` nie zawiera sekretów**; `backend/.gitignore:28` ignoruje
   `/storage/slad-wylogowania`, więc ślad wylogowania nie brudzi drzewa i nie
   fałszuje kontroli powtarzalności.

---

## 8. Czego NIE ZDĄŻYŁEM / NIE MOGŁEM sprawdzić — jawnie

1. **Nic nie uruchomiłem.** Część B jest bez Dockera, więc żadne twierdzenie o *wyniku
   przebiegu* nie zostało zmierzone. R6B-3, R6B-4, R6B-8 i R6B-13 są wyprowadzone
   z lektury kodu i z semantyki narzędzi (`sed`, `python`, `bash`, Pest), nie z logu.
   Każde z nich ma jednak jednolinijkowy test rozstrzygający, który wykonawca może
   uruchomić w kilka sekund, np.:
   `python3 skrypty/perturbuj.py role-zamrozone; echo "kod=$?"; git diff --stat` —
   oczekiwane: `kod=1`, zero zmian w drzewie.
2. **Nie zweryfikowałem uruchomieniowo mechanizmu z §3.** Analiza opiera się na
   źródłach `laravel/framework v13.24.0` czytanych z dysku; nie wykonałem testu
   z dołożonym `forgetInstance(StartSession::class)`. To jest właśnie treść projektu
   odczytu z §3.3 — celowo niewdrożonego.
3. **Nie przejrzałem pełnych treści** `BrakWlasnychHaselTest.php`, `ModelDanychTest.php`,
   `RejestrRegulTest.php`, `RetencjaTest.php`, `ObietniceKomentarzyTest.php`,
   `LogowanieTest.php`, `WalidatorTokenu.php`, `KontaOidc.php`, `Bramki.php`,
   migracji ani `docs/specyfikacja/`. Zadanie A było zakresowo ograniczone do
   `OdebranieRoliTest` i skryptów; pozostałe pliki widziałem wyłącznie punktowo
   (nazwy testów, wzorce perturbacji, sygnatury). **Nie twierdzę niczego o ich
   dyskryminatorach.**
4. **Nie sprawdziłem CI** (`.github/workflows/`) ani stanu zdalnego repozytorium.
5. **Nie czytałem `.env` dewelopera** (zakaz w zleceniu), więc twierdzenie z G-1
   o tym, *jakie konkretnie wartości* wchodzą do stosu perturbacji, ograniczam do
   faktu montowania pliku (`docker-compose.yml:73` + brak `--env-file`
   w `perturbacje.sh:124-126`) — bez wnioskowania o zawartości.
6. **Nie mierzyłem rzeczywistych liczb testów/asercji** (180/640 z `PLAN-FAZ.md:17`).
   Progi 170/590 oceniam wyłącznie względem tej deklaracji, którą sam dokument
   oznacza jako ruchomą.
7. **Nie oceniałem `docs/specyfikacja/` ani zgodności biznesowej** — poza zakresem
   tej części rundy.

---

### Sprzeczne polecenia i koszt cofnięcia

**Brak.** Zlecenie części B było wewnętrznie spójne i niesprzeczne z `CLAUDE.md`
ani z zamkniętymi wpisami `docs/DECYZJE.md`. Zgodnie z zakazem z §„NIE WDRAŻAJ GO"
nie zmieniłem ani jednego pliku w repozytorium wykonawcy; mój klon
(`/d/tmp/gabinet-r6b`) usuwam po oddaniu raportu.
