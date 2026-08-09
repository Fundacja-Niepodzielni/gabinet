# Klasy znalezisk i jedna naprawa na klasę — Gabinet, 09.08.2026

**Po co ten dokument.** Rejestry czterech repozytoriów liczą razem około dziewięćdziesięciu
pozycji. Naprawianie ich po kolei wyprodukuje dziewięćdziesiątą pierwszą z tej samej rodziny.
Odpowiedzią ma być **zmiana sposobu naprawiania**, nie dłuższa lista.

**Zakres.** Wyłącznie pozycje **POTWIERDZONE** po korekcie weryfikatora krzyżowego Kont.
Pominięte: **R6B §7.1** (OBALONE), **S-1** (spór o liczenie pisarzy — **rozstrzygnięty 09.08
przez architekta po mojej stronie, przestał być znaleziskiem**), hipoteza „odświeżanie
wskrzesza tożsamość" (obalona dwukrotnie), **O-N1** (zamknięte jako błąd częstości bazowej),
oraz przyczynowa część **N-7** (obniżona — zmierzyłem zgodność z hipotezą, nie jej wyłączność).

**Kryterium klasy, stosowane ostro:** klasa to zbiór znalezisk, które zamknęłaby **JEDNA
zmiana**. Jeśli proponowana zmiana zamyka jedno, a pozostałych nie — to nie jest klasa, tylko
instancja, i tak jest nazwana. Sekcja instancji na końcu jest długa **celowo**.

---

## Nowy wymóg rejestrowy: WAGA i OSIĄGALNOŚĆ to DWA pola

Wiążące od teraz. Powód jest zmierzony, nie teoretyczny: spór o W-19 między Gabinetem a hubem
wziął się stąd, że **jedna liczba niosła dwie wielkości**. Ich raport mówił „krytyczna,
blokuje", ich własna dokumentacja trwała mówiła „dziś to uprawnienie ma jedno konto" — i obie
były prawdziwe, bo mówiły o czym innym.

- **WAGA** — jak źle, **gdy mechanizm zadziała**. Nie zależy od tego, kto dziś może go uruchomić.
- **OSIĄGALNOŚĆ** — **kto może dziś tędy wejść** i co musiałoby się zmienić, żeby mógł ktoś więcej.

„Wysokie, ale dziś nieosiągalne" i „średnie i osiągalne" wyglądają w rejestrze tak samo,
a **wymagają przeciwnych decyzji**: pierwsze pilnuje się warunkiem, drugie naprawia się teraz.

**WARUNEK UTRZYMUJĄCY** — osobne pole tam, gdzie osiągalność jest dziś zerowa *dzięki czemuś,
co ktoś może jutro zmienić*. Zapisujemy go, bo **pierwsza osoba, która go złamie, nie będzie
wiedziała, że to zrobiła.** Warunek bez zapisu to nie zabezpieczenie, tylko szczęście.

---

# KLASA 1 · Kontrola dowodzi własności, której jej środowisko nie ma

**Wspólny mechanizm (nie objaw):** kontrola biegnie w środowisku, w którym **badane zjawisko nie
zachodzi** — sterownik podmieniony, zmienna wymuszona, transakcja obejmująca cały test, inny
użytkownik procesu. Asercja przechodzi, bo mierzy atrapę, a nie mechanizm. Objawy są różne
(atomowość, trwałość, blokada wysyłki, uprawnienia), mechanizm jeden: **rozjazd środowiska
pomiaru z produkcją, którego nikt nie wypisał ani nie egzekwuje.**

**Członkowie:** E-1 (atomowość `Cache::add` mierzona magazynem `array`, jednoprocesowo) ·
E-3 (cztery testy magazynu sesji na `array`, w tym **jedyny test adwersarialny**, wbrew zdaniu
z D-2026-08-08-27) · E-5 (`RefreshDatabase` — jedna transakcja czyni niemierzalnym COMMIT,
blokady wierszy i wymagany przez CLAUDE.md §6 test 100 równoczesnych żądań) · E-6
(`APP_ENV=testing` wymuszone → produkcyjna gałąź `odmowa()` nie wykonana ani razu) ·
E-7 (`MAIL_MAILER=array` już jest na liście sterowników niewysyłających, więc asercja przechodzi
**bez udziału blokady**) · E-8 (`Cache::flush()` czyści `array`, nie Redisa) · R6B-14
(bramka częstotliwości JWKS — jw. co E-1) · **N-14** (ślad wylogowania: testy jako `root`,
żądania jako `www-data` — zapis cicho nie dochodzi) · **R6B-16** (perturbacje montują `.env`
DEWELOPERA, więc mierzą w innym środowisku niż czysty klon).

**JEDNA naprawa, na właściwym poziomie — konstrukcja, nie warunek.**
**Drugi pierścień suity, biegnący na PRAWDZIWYCH sterownikach i jako PRAWDZIWY użytkownik**
(`www-data`, bez `RefreshDatabase`, `APP_ENV=production`, Redis/Postgres/kolejka realne), plus
**manifest różnic**: jeden plik wymieniający każdą własność, którą pierścień szybki podmienia.
Kontrola asertująca własność z manifestu **nie może mieszkać w pierścieniu szybkim** — bramka
odmawia. To jest „brak wartości przed sprawdzaniem wartości": kontrola nie dostaje środowiska,
w którym mogłaby przejść nieprawdziwie.

**Perturbacja falsyfikująca:** przenieś dowolną kontrolę z pierścienia produkcyjnego do szybkiego
(albo podmień jeden sterownik w pierścieniu produkcyjnym) → **bramka musi zaświecić czerwono
i NAZWAĆ kontrolę oraz własność**, na której ta kontrola stoi. Dziś taki ruch jest niewidzialny.

**Czy występuje gdzie indziej: TAK, we wszystkich trzech.**
- **helpdesk** — ich D-2026-08-08-27 jest dokładnym odpowiednikiem mojego przeglądu sterowników,
  a ich E-3 (moje ustalenie z weryfikacji krzyżowej) pokazuje, że **zdanie z tego przeglądu jest
  nieprawdziwe** dla czterech testów. To ta sama klasa, ten sam kształt zdania.
- **hub** — precedens `->stateless()` przechodzący 83/83 i `ProviderAtrapa` nadpisujący `user()`
  są nazwane w ich własnym zleceniu jako **znane** przypadki; ich zlecenie pyta o „KOLEJNE".
- **konta** — ich wzorzec `ref-laravel` skanuje pliki rekordów zamiast realnego magazynu sesji.

---

# KLASA 2 · Dowód oparty na NIEOBECNOŚCI napisu

**Wspólny mechanizm:** kontrola pyta o **TEKST**, a nie o stan ani efekt. „Napisu nie ma" jest
zgodne z dwoma światami — *zmieniono go* oraz *nigdy go tam nie było* — więc każdy refaktor
cicho unieważnia dowód, a kontrola dalej świeci zielono.

**Członkowie:** N-3 (dwie perturbacje martwe po przemianowaniu `$konta` → `$tozsamosc`;
dowód mutacji był negacją wzorca, którego nigdy nie było) · R6A-5 i R6B-3 (te same dwie,
znalezione niezależnie) · R6B-5 (`p_logout_failsafe` — dowód na symbolu `sidNiezweryfikowany`,
którego w pliku nie ma **wcale**) · R6B-17 i N-9 (`p_statyka` — `sed`, który nie trafia od
nieznanego czasu; `sed` bez trafienia **kończy się sukcesem**) · R6A-6 (`str_contains` na treści
pliku dopasowuje literał także w **komentarzu**) · R6A-7 (`ObietniceKomentarzyTest` obejmuje
regexem `[UWO]-\d+` sześć znaczników, a **pomija siedem**, w tym `B7`, `B8`, `BLK-22`,
`D-2026-08-08-24` — czyli wszystkie, na które powołuje się warstwa `Tozsamosc`).

**JEDNA naprawa, na właściwym poziomie — odczyt bazowy zamiast negacji.**
Częściowo już zrobiona (`dowod_zniknieciem`: *czy tekst BYŁ, a potem ZNIKNĄŁ*, z kopii sprzed
mutacji). **Domknięcie klasy wymaga rozszerzenia na wszystkie trzy postacie:**
żaden dowód nie może być pytaniem o obecność/nieobecność napisu bez **odczytu bazowego**;
gdzie da się — pytamy o **EFEKT** (zachowanie), nie o napis; a dopasowania tekstowe w kodzie
źródłowym muszą odróżniać **kod od komentarza** (parsowanie, nie `str_contains`).
Osiem podmian robionych surowym `sed`-em przenieść pod `podmien()`, które **krzyczy**.

**Perturbacja falsyfikująca:** przemianuj dowolny symbol cytowany przez kontrolę
(np. `$tozsamosc` → `$x`) i uruchom zestaw → **każdy dowód tekstowy musi zaświecić czerwono
z komunikatem „rozjechał się z kodem"**, a nie przejść. To dokładnie zdarzenie z tej nocy,
odtworzone na żądanie.

**Czy występuje gdzie indziej: TAK.**
- **helpdesk** — ich `p_statyka`-odpowiedniki: osiem podmian `sed`-em bez dowodu mutacji;
  ich R5 to denylista trzech nazw metod uruchamiana na pustym stogu siana.
- **hub** — ich weryfikator B rozstrzygał `grep`-em **po nazwie** (`"SesjaKonta::"`) i policzył
  wywołania zamiast sprawdzić, czy da się dostarczyć tożsamość; konta nazwały to wprost jako
  „pomiar pośrednika zamiast skutku".

---

# KLASA 3 · Wynik zgodny z więcej niż jednym światem (gałąź zdegenerowana)

**Wspólny mechanizm:** pomiar rozstrzygający **nie ma odczytu bazowego ani zawężenia przyczyny**,
więc jego wartość pasuje do ≥ 2 światów. Dotyczy zieleni tak samo jak czerwieni: „zielony
z niewłaściwego powodu" i „czerwony z niepowiązanej przyczyny" to **jedno zjawisko**.

**To jest największa klasa w moim rejestrze.**

**Członkowie:** R6B-1 (migawki nogi 1 — siedem światów dla jednej wartości) · R6B-2 i R6A-1
(test „POZYTYWNY" — 401 pochodzi ze znacznika w bazie, nie z kasowania sesji; mutacja usuwająca
`destroy()` **nie zapala go**) · R6B-6 (test odmowy IdP bez asercji stanu wyjściowego — 401 pasuje
do „odmowa zadziałała" i do „logowanie się nie powiodło") · R6B-7 (`przygotuj_env()` — sześć
`sed -i` bez odczytu zwrotnego; `sed` bez trafienia kończy się sukcesem) · R6B-11 (aktywna kontrola
portów pyta **HTTP-em**, więc nie wykryje wystawionego Postgresa ani Redisa; bywa milcząco
pomijana i mimo to przechodzi) · R6B-13 i R6A-5 (**pięć** perturbacji tożsamości nie może dziś
paść, bo celują w plik trwale czerwony przez nogę 1) · R6B-15 (sześć z ośmiu allowlist
`--przyczyna` to nazwy testów/klas albo wartości `--filter`, które Pest wypisuje **zawsze**) ·
R6B-8 (`skrypty-uruchamialne.sh` — „nieznana nazwa" rozstrzygana cudzym kodem wyjścia) ·
N-12 (potwierdzone pomiarem: jedyna czerwona kontrola pełnego zestawu to kierunek odwrotny,
niemożliwy do przejścia przy czerwonej nodze 1).

**JEDNA naprawa, na właściwym poziomie — konstrukcja przed warunkiem.**
**Pomiar rozstrzygający nie może zostać ogłoszony bez PRE-FLIGHTU i gałęzi kontrolnej.**
Mechanicznie: wspólny pomocnik, przez który przechodzi **każdy** werdykt, wymagający trzech
rzeczy jako **argumentów** (nie zaleceń): (1) tabeli „wartość → dokładnie jeden świat",
(2) wyniku gałęzi **bazowej** z tego samego przebiegu, (3) zawężenia przyczyny będącego
**komunikatem asercji**, nie nazwą testu ani wartością `--filter`. Bez któregokolwiek —
werdykt brzmi **NIEROZSTRZYGNIĘTE**, nigdy „OK". To jest wąskie gardło zamiast strażnika:
werdyktu bez odczytu bazowego **nie da się wyprodukować**, a nie „nie wolno".

**Perturbacja falsyfikująca:** wywołaj pomocnika bez gałęzi bazowej (albo z allowlistą równą
nazwie testu) → musi zwrócić `NIEROZSTRZYGNIĘTE` i **zaświecić czerwono**, zamiast zaliczyć.
Drugi kierunek: podaj dwa różne światy dające tę samą wartość → pre-flight musi odmówić startu.

**Czy występuje gdzie indziej: TAK, u wszystkich trzech i jest najpowszechniejsza.**
- **helpdesk** — ich R6 jest tego wzorcowym przykładem: „zielone" pochodzi z ogniw, które
  kontrola **wykonuje sama**; ich `p_sesja_jawna` zalicza się z innej przyczyny niż badana.
- **hub** — ich Z-B-4 (próg dzielący źródło z pomiarem), Z-B-3 („żywy/martwy" na jednym kruchym
  sygnale), Z-A-4 (cichy brak dopasowania `sid` w trzech miejscach).
- **konta** — sami zapisali u siebie N-17 (asercje szukające nazw pól zamiast wartości)
  i N-26 (strażnik mierzący czas katalogu zamiast faktu uruchomienia).

---

# KLASA 4 · Zapis w ścieżce bezpieczeństwa, którego los nie jest rozstrzygnięty

**Wspólny mechanizm:** ścieżka bezpieczeństwa wykonuje zapis (plik, cache, log), którego
**niepowodzenie nie ma zdefiniowanego skutku** — nie rzuca, nie asertuje, nie zostawia śladu.
Negatywna asercja bezpieczeństwa („ten `sid` jest martwy", „handler wystartował") znika po cichu,
a objawem jest **brak objawu**.

**Członkowie:** R6B-9 (`RejestrSesji` — mapa `sid → sesje`, bez której back-channel logout nie
znajdzie ŻADNEJ sesji, mieszka w cache'u z TTL 86 400 s; utrata daje `skasowane_sesje = 0`
bez komunikatu) · N-14 (zapis śladu wylogowania **ostrzega i zwraca**, licznik nie rośnie,
a odczyt **udaje się** i oddaje nieświeżą liczbę z innego procesu) · R6B-10 (puls harmonogramu —
sygnał zdrowia — mieszka w tym samym cache'u podlegającym eksmisji).

**JEDNA naprawa, na właściwym poziomie — brak wartości przed sprawdzaniem wartości.**
**Każdy magazyn niosący asercję bezpieczeństwa musi spełniać cztery wymagania trwałości**
(trwałość, współdzielenie między procesami, czas życia ≥ SSO Session Max, **niecicha eksmisja**),
a **odczyt z magazynu, który ich nie spełnia, ma zwracać „nie wiem", nie liczbę.** Typ, nie
konwencja: funkcja odczytu oddaje wartość **albo** stan „nierozstrzygnięte", a wołający musi
obsłużyć oba — tak jak `TozsamoscSesji::zMagazynu()` oddaje `null` zamiast pustej tożsamości.
Zastosowaliśmy te cztery wymagania do znacznika unieważnienia i **nie zastosowaliśmy ich do mapy**.

**Perturbacja falsyfikująca:** odbierz prawo zapisu do magazynu śladu/mapy (albo wyczyść cache
w połowie przebiegu) → kontrola musi **zaświecić czerwono i nazwać brak rozstrzygnięcia**;
dziś zwraca liczbę i przechodzi. Drugi kierunek: przy sprawnym magazynie ma świecić zielono.

**Czy występuje gdzie indziej: TAK, i to jest najgorsza wspólna klasa ekosystemu.**
- **hub** — ich **Z-A-1** to ta sama rodzina po drugiej stronie: u nich zapis **rzuca**
  i zabija unieważnienie sesji; u mnie **ostrzega** i zabija diagnostykę. Ich **L-3**
  („niezapisywalny magazyn unieważnień wyłącza wzorzec BLK-22 w ciszy") to trzeci wariant.
- **helpdesk** — ich **W-18**: audyt żyje w tabeli powiązanej ze zgłoszeniem, więc **umiera
  razem z ofiarą**, po czym wystawia pozytywny certyfikat integralności.
- **konta** — ich kontrakt **nie nazywa** mapy `sid → sesje`, więc nie obejmuje jej wymaganiami;
  sami zapisali to jako znalezisko przeciwko sobie.

---

# KLASA 5 · Kontrola bezpieczeństwa zbudowana jako DENYLISTA

**Wspólny mechanizm:** kontrola **wylicza zakazane** zamiast dopuszczać znane. Każdy wariant
spoza listy przechodzi — nie przez przeoczenie pozycji, tylko przez **konstrukcję**. Lista
zawsze jest krótsza niż przestrzeń możliwości.

**Członkowie:** R6A-4 (`PRYMITYWY_POSWIADCZEN` — `hash('sha256', …)`, `hash_hmac`, `md5`,
`openssl_*` przechodzą; **cały mechanizm własnych haseł zdał kontrolę §2**) · denylista
`AWARIE_POBOCZNE` w strażniku przyczyny czerwieni (podłoga wyliczająca znane klasy awarii
pobocznych — awaria spoza listy zalicza perturbację).

**JEDNA naprawa — allowlista z jawną odmową przy nieznanym.**
Kontrola dopuszcza **wymieniony zbiór** i **odmawia przy wszystkim innym**; „nieznane" jest
stanem odmowy, nie stanem przepuszczenia. Przy prymitywach kryptograficznych znaczy to:
lista funkcji **dozwolonych** w tym repozytorium (dziś: żadnych) i czerwień przy dowolnej innej.

> **Świadomie nie wpisuję gotowego kształtu tej allowlisty.** Weryfikator wykazał, że moje
> poprzednie zalecenie w tym miejscu (test liczący pisarzy) **przepuściłoby tę samą mutację**;
> zastępowanie go drugim zaleceniem bez dowodu byłoby powtórzeniem błędu. Kierunek jest pewny,
> projekt wymaga własnej rundy.

**Perturbacja falsyfikująca:** wprowadź prymityw poświadczeń **spoza wszystkich list**
(np. `sodium_crypto_generichash`, własna pętla PBKDF2) → kontrola musi zaświecić czerwono.
Dziś to jest dokładnie ten ruch, którym weryfikator ją przeszedł.

**Czy występuje gdzie indziej: TAK.**
- **konta** — sami zmierzyli u siebie coś gorszego: regułę mają ostrzejszą („STOP, jeśli piszesz
  kod dotykający haseł"), a **kontroli nie mają żadnej**; zapisali to jako symetrię obciążającą ich.
- **helpdesk** — ich R5 jest denylistą trzech nazw metod; surowy SQL ją omija, a **sama bramka
  używa tego idiomu**.

---

# KLASA 6 · Twierdzenie w dokumencie bez egzekutora

**Wspólny mechanizm:** zdanie o kodzie („robi X", „zamknięte", „potwierdzone", „lista jest
ZAMKNIĘTA") **nie ma nic, co by je uruchamiało**. Myli następnego czytelnika ciszej niż kod, bo
nikt go nie wykonuje — a brzmi jak wynik pracy.

**Członkowie:** N-1 (trzy docbloki w kodzie produkcyjnym niosły wniosek **obalony** tego samego
wieczora) · N-4 (`PLAN-FAZ.md` przeczył sam sobie: „jedyny czerwony to noga 1" i pięćdziesiąt
linii niżej „test BLK-22 jest CZERWONY") · R6A-9 (**dwie** sekcje `CURRENT WORK` o sprzecznym
stanie) · R6A-8 (komentarz w `config/database.php` wiąże rozdzielenie baz z ochroną przed
eksmisją, a D-2026-08-08-28 mówi wprost coś przeciwnego i **nie jest cytowana**) · N-6
(D-28 nazywa wyzwalacz „eksmisja", którego przy `maxmemory=0` i `noeviction` **nie da się
wywołać**) · R6A-7 (zdanie z `WYTYCZNE-PRACY.md` o „każdym znalezisku powołanym w kodzie" jest
nieprawdziwe wobec regexu) · eskalacja Kont przy R6A-4 (kontrola zawiera **pisemne zapewnienie,
że dziury nie ma**, i sama przewiduje wyjątek, oddając go człowiekowi).

**JEDNA naprawa — rozszerzenie egzekutora z ZNACZNIKÓW na TWIERDZENIA.**
`ObietniceKomentarzyTest` egzekwuje dziś jedno: znacznik powołany w kodzie ma być nazwany
w teście. Rozszerzyć na **twierdzenia o stanie**: każde zdanie postaci „robi / zamknięte /
potwierdzone / lista jest zamknięta" w komentarzu kodu produkcyjnego i w dokumentach stanu musi
wskazywać test albo pomiar; **plus jedno miejsce prawdy dla stanu** (dokładnie jedna sekcja
`CURRENT WORK`, egzekwowana testem). Nie da się zmechanizować prawdziwości zdania po polsku —
da się zmechanizować **wymóg wskazania dowodu** i **zakaz dwóch źródeł stanu**.

**Perturbacja falsyfikująca:** (a) dopisz do kodu komentarz „naprawione, patrz B9" bez testu
nazywającego `B9` → czerwone; (b) dodaj drugą sekcję `CURRENT WORK` → czerwone;
(c) rozszerz zakres znaczników i sprawdź, że `B7`, `BLK-22`, `D-2026-…` wchodzą w sieć,
której dziś unikają.

**Czy występuje gdzie indziej: TAK, u wszystkich.**
- **helpdesk** — ich Z-01 (liczby bez warunku pomiaru w dokumencie idącym na zewnątrz) i Z-02
  (pamięć podana jako pomiar); ich komentarz `# Selekcja — ta sama droga, którą idzie Job#run`
  **deklaruje równoważność, której nikt nie sprawdza**.
- **hub** — ich N-2 (docblock opisuje podział adresów, którego klasa nie robi), N-9 (fałszywy
  inwentarz ryzyka), Z-B-12 (skrypt nazywa siebie „elementem bramki", a nikt go nie woła).
- **konta** — ich kontrakt cytuje jako „POTWIERDZONY DEFEKT" mechanizm, który ta noc obaliła;
  sami skierowali to do własnej erraty.

---

# KLASA 7 · Reguła istniejąca wyłącznie jako zdanie w dokumencie

**Wspólny mechanizm:** zabezpieczenie egzekwowane **pamięcią wykonawcy**, nie mechanizmem.
Reguła jest zapisana, znana, cytowana — i łamana, bo `git add -A` działa na **stanie otoczenia**
(co biegnie, gdzie stoję), a nie na moim zamiarze.

**Członkowie:** N-10 (commit w trakcie biegnącej suity perturbacji wciągnął **żywą perturbację
reguły 24 h** do repozytorium) · N-13 (zapis i commit **w cudzym repozytorium**, bo został po
mnie `cd` z fazy czytania).

**JEDNA naprawa — strażnik przed operacją, nie ostrzeżenie po niej.**
Hook `pre-commit`, który **odmawia**, gdy zachodzi którykolwiek z dwóch warunków:
(1) istnieje znacznik trwającego przebiegu pomiarowego (zakładany przez harness perturbacji,
z PID i czasem, zdejmowany `trap`-em na `EXIT INT TERM`), (2) `git rev-parse --show-toplevel`
nie wskazuje repozytorium, w którym wolno mi pisać. Komunikat odmowy ma mówić, **co jest w toku
i jak zdjąć znacznik osierocony przez zabity proces** — inaczej strażnik zamieni się w blokadę
bez wyjścia.

**Perturbacja falsyfikująca:** (a) załóż znacznik i spróbuj commita → **odmowa**, drzewo
nietknięte; (b) spróbuj commita z katalogu cudzego repozytorium → **odmowa**. Bez obu strażnik
jest deklaracją — czyli dokładnie tym, czym była reguła, która zawiodła dwa razy w ciągu doby.

**Czy występuje gdzie indziej: TAK.**
- **hub** — ich Z-B-1 i Z-B-2 (zamek przebiegu pilnował katalogu, a chronił zasobu globalnego
  dla demona; nieudany zapis zamka przechodził w ciszy) to ta sama rodzina: ochrona zależna od
  właściwego stanu otoczenia, bez egzekucji.
- **helpdesk** — ich N-07 (`TRYB_SPRAWDZANIA` domyślnie wyłączony: brak zmiennej **cicho**
  przełącza kontrolę w tryb zapisu).
- **konta** — ich N-15 („pierwsza twarda reguła projektu bez strażnika").

---

# Instancje — naprawa punktowa, NIE klasa

Nazywam je wprost, żeby nikt nie udawał, że wszystko jest klasą. Każda z nich ma własny
mechanizm i **jedna zmiana nie zamyka żadnej innej**.

| # | Znalezisko | Dlaczego to instancja | WAGA | OSIĄGALNOŚĆ |
|---|---|---|---|---|
| **R6A-3** | `zMagazynu()` publiczna, przyjmuje dowolną tablicę | naprawa (`private` + jedyne wejście przez `SesjaKonta::odczytaj`) zamyka **tylko** to; nie dotyka żadnego innego znaleziska | **wysoka** — tożsamość koordynatora bez logowania | **dziś ZEROWA z zewnątrz** — weryfikator musiał dopisać trasy. **WARUNEK UTRZYMUJĄCY: żadna trasa nie przekazuje danych z żądania do `zMagazynu()`.** Pierwsza osoba, która napisze taką trasę, otworzy to nie wiedząc |
| **R6A-11** | `ZadanieRetencji` nie ma **ani jednego wywołującego** | u mnie jeden mechanizm; podpięcie go nie zamyka niczego innego. **Ale to jest KLASA w skali ekosystemu** — patrz niżej | **wysoka** (RODO: retencja nie wykonuje się wcale) | **pełna i bierna** — nie trzeba nic robić, żeby szkoda rosła; wystarczy upływ czasu |
| **R6A-12** | odczyt klucza `konta` literałem, z pominięciem stałej i fasady | punktowe; stała `KLUCZ` istnieje, jedno miejsce jej nie używa | niska | niedotyczy (spójność, nie bezpieczeństwo) |
| **R6A-10** | `bramka.sh` liczyła `PLIK_ENV` przed parsowaniem `--projekt` | **NAPRAWIONE 09.08**, z dowodem | średnia (dwa przebiegi dzieliły poświadczenia) | była: każdy równoległy przebieg |
| **N-2** | podłogi bramki 170/590 przy 181/640 | **NAPRAWIONE** (180/635); pozostaje rozjazd R6B-12 (perturbacje dowodzą 100/300) — to jedna zmiana, ale **tylko tej pozycji** | średnia | pełna: każdy commit usuwający testy |
| **N-5** | domyślne PostgreSQL (`idle_in_transaction_session_timeout=0`, `lock_timeout=0`, `statement_timeout=0`, brak logu wolnych zapytań) | to **decyzje konfiguracyjne**, nie wspólny mechanizm — każda ma inny skutek i inny próg | **wysoka dla §6** (zawieszona transakcja blokuje termin bez końca) | pełna w produkcji; dziś dev |
| **N-6 / N-7** | Redis: `maxmemory=0` + `noeviction`; rozdzielenie kluczy nie działa dla procesów długo żyjących | dwie różne rzeczy o jednym produkcie; część dokumentacyjna poszła do KLASY 6 | średnia | **WARUNEK UTRZYMUJĄCY: `maxmemory` pozostaje `0`.** Ustawienie limitu **włącza** klasę, której dziś nie ma |
| **N-8** | dokumentacja zapaliła skaner sekretów (nazwa pliku jako klucz API) | **ZAMKNIĘTE** tej nocy z dowodem falsyfikowalności i decyzją architekta | niska | — |
| **E-9** | `SladWylogowania` jako stan globalny współdzielony między testami | punktowe (kolejność testów), inny mechanizm niż N-14 | niska | — |
| **R6B-16** | perturbacje montują `.env` dewelopera | mechanizm wspólny z KLASĄ 1 (środowisko pomiaru ≠ zadeklarowane) — **wpisana tam**, tu wymieniona dla kompletności | średnia + higiena sekretów | pełna na maszynie wykonawcy |

## Jedna instancja, która jest klasą u sąsiadów

**R6A-11** (retencja niepodpięta) u mnie ma jednego członka — więc **instancja, naprawa
punktowa**. Ale to **dosłownie to samo, co helpdeskowe W-17** („cała retencja RODO stoi na
zadaniu cyklicznym, o które żadna kontrola nie pyta") i to samo, co ich N-01. Różnica jest
tylko taka, że u nich mechanizm istnieje i nie jest sprawdzany, a u mnie **nie istnieje wcale**.

**Wniosek dla ekosystemu, nie dla mnie:** *„mechanizm zadeklarowany, ale niepodpięty do niczego,
co go uruchamia"* jest klasą **międzyrepozytoryjną**, widoczną wyłącznie z zestawienia dwóch
nocy. Naprawa na właściwym poziomie jest wspólna: **kontrola nie pyta, czy mechanizm istnieje,
tylko czy WYKONAŁ SIĘ** — z odczytem świeżości (`last_run` młodszy niż odstęp), a nie z flagi
`active`. Perturbacja: cofnij znacznik ostatniego wykonania w czasie → musi zaczerwienić.

---

# Czego ten dokument NIE robi

- **Nie naprawia kodu.** Ani jednej linii; to praca projektowa.
- **Nie dopisuje znalezisk.** Wszystkie identyfikatory pochodzą z istniejących rejestrów.
- **S-1 (liczenie pisarzy) — ROZSTRZYGNIĘTE 09.08 przez architekta po mojej stronie**
  (D-24 mówi o komponentach, nie o instrukcjach; wina za wieloznaczność po jego stronie).
  Do żadnej klasy nie wchodzi, bo **nie jest już znaleziskiem**. Część nie-sporna — `zaloz()`
  przyjmuje surową tablicę — była i pozostaje w instancji **R6A-3**.
- **Nie przypisuje klas cudzym repozytoriom autorytatywnie.** Kolumna „czy występuje gdzie
  indziej" to **podejrzenie oparte na ich własnych zapisach**, które czytałem — nie pomiar u nich.
  Każde takie zdanie ma wskazane ich znalezisko, żeby dało się je sprawdzić u źródła.
