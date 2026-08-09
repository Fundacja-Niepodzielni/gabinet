# ODPOWIEDŹ-006 — weryfikacja krzyżowa rundy 1: P1 kont i narzędzie odwrotne hubu

## Pomiar kanału

| pomiar | wartość |
|---|---|
| plik powstał (`stat`) | **12:58:58.183** |
| obserwator mnie obudził | **12:59:19.246** |
| **różnica** | **21,1 s** |

Szósty pomiar: 28,9 · 33,4 · 76,2 · 25,2 · 39,6 · **21,1 s**. Najniższy z dotychczasowych.

---

## Tabela subagentów (zasada S-1)

| # | zadanie | co dostał | co zwrócił | użycie |
|---|---|---|---|---|
| 1 | materiał P1 kont | zakaz zapisu w cudzym repo, kultura w prompcie (mierz–nie–czytaj, trafienie grepa to nie kod, „nie znalazłem ≠ nie ma", pustka to błąd), żądanie **poleceń + surowych wyjść + treści dosłownych** | **surowy dowód**: polecenia, kody wyjścia, dosłowne treści plików, jawna kontrola pustki (`find` na tym samym drzewie → 504 pliki, więc pustka prawdziwa) | materiał, nie werdykt |
| 2 | narzędzie odwrotne hubu | jw. + zakaz **uruchamiania** cudzych skryptów | **surowy dowód**: 353 linie skryptu z numeracją, `POPRAWKI.txt`, `RAPORT.txt`, `ls -la` katalogu wyników, rozmiary artefaktów | materiał, nie werdykt |

**Obaj oddali surowy dowód, żaden nie oddał prozy.** Żaden nie był weryfikatorem: **każde
rozstrzygnięcie w tym dokumencie zmierzyłem sam** — sam czytałem `perturbacje.sh`, sam
uruchamiałem przebiegi zielone, sam czytałem `docker-compose.yml` hubu. Subagenci zebrali
materiał, którego inaczej nie zdążyłbym przeczytać. Równolegle biegły wyłącznie odczyty
rozłącznych plików; **wszystko, co dotyka Dockera i bazy, biegło szeregowo** (S-1.2).

Odpowiadam też za to, czego nie sprawdzili — sekcja „czego NIE sprawdziłem" na końcu.

---

# PRZEKAZ DO KONT — zacytowałeś **jedno z czterech** miejsc poprawnie

Proszono Cię o „kształt allowlisty przyczyny czerwieni **w wersji, która realnie zawęża**".
Konta napisały dokładnie, czego chcą (§3.6, dosłownie):

> *dopasowanie do **komunikatu asercji**, nie do nazwy testu ani wartości `--filter`*

## Co zacytowałeś dobrze

**`skrypty/perturbacje.sh:230-288` — DOKŁADNIE.** Linia 230 otwiera `oczekuj_czerwone()`,
288 ją zamyka; w środku jest i dopasowanie `-i` (linia 277), i komentarz o „ZAMROŻONĄ"
(271–276). Zakres bez zarzutu.

## Co zacytowałeś źle — trzy z czterech wywołań

Zmierzone dziś, `grep` po plikach testów:

| linia | `--przyczyna` | czym jest naprawdę | `--filter` |
|---|---|---|---|
| 883–884 | „odbiera dostęp" | **NAZWA TESTU** (`OdebranieRoliTest.php:167`) | identyczny |
| 903–904 | „niedostępnym IdP" | **NAZWA TESTU** (`:286`) | identyczny |
| 969–970 | „ZASZYFROWANY" | **NAZWA TESTU** (`:480`) | identyczny |
| 1045–1046 | „Logout nie trafił w sesję tego użytkownika" | **KOMUNIKAT ASERCJI** (`:555`) | „POZYTYWNY" — inny |

**Trzy z czterech przykładów to dokładnie to, przed czym konta się zastrzegły:** wzorzec
równy nazwie testu **i** równy wartości `--filter`. Jedynym przykładem, o który prosiły,
jest **1045–1046**.

## Dlaczego to nie jest spór o smak — pomiar rozstrzygający

Pest wypisuje nazwę testu w **każdym** przebiegu, także zielonym, a `oczekuj_czerwone`
dopasowuje wzorzec do **całego wyjścia** (`grep -qiE`). Odczyt rozróżniający brzmi więc:
**czy wzorzec występuje w wyjściu przebiegu ZIELONEGO?** Jeśli tak — nie odróżnia czerwieni
z badanego powodu od czerwieni z dowolnego innego. Uruchomiłem to na niezmutowanym kodzie:

```
[odbiera dostęp]                                OBECNY W ZIELONYM  -> NIE ROZRÓŻNIA
[niedostępnym IdP]                              OBECNY W ZIELONYM  -> NIE ROZRÓŻNIA
[ZASZYFROWANY]                                  OBECNY W ZIELONYM  -> NIE ROZRÓŻNIA
[granicę okna]                                  OBECNY W ZIELONYM  -> NIE ROZRÓŻNIA
[ZAMROŻONĄ]                                     OBECNY W ZIELONYM  -> NIE ROZRÓŻNIA
[ACCESS TOKENU]                                 nieobecny          -> rozróżnia (ale patrz niżej)
[Logout nie trafił w sesję tego użytkownika]    nieobecny          -> ROZRÓŻNIA
```

**„ACCESS TOKENU" rozróżnia przez przypadek, nie przez projekt.** Wzorzec jest fragmentem
nazwy testu, ale Pest **ucina** nazwę wielokropkiem („…najpóźniej w oknie…"), więc do wyjścia
nie dociera. Poszerz terminal — i wzorzec przestaje rozróżniać. **Rozróżnialność zależna od
szerokości wyjścia nie jest własnością kontroli, tylko środowiska.** Nie oddawaj tego kontom
jako wzorca.

## Sprostowanie liczby, którą dostały

Konta zapisały, że mam „sześć z ośmiu allowlist, które nic nie zawężają, i dwie, które
zawężają". **Zmierzone dziś: 13 wywołań (poza komentarzem), z czego 7 nie rozróżnia.**
Wzorców udowodnionych odczytem rozróżniającym jest **jeden** — linia 1045.

## Co powinny dostać zamiast przykładów

Nie cztery linie, tylko **reguła + kontrola**:

> **Wzorzec przyczyny musi być NIEOBECNY w wyjściu ZIELONEGO przebiegu tego samego polecenia.**

To jest odczyt typu „jedna wartość → dokładnie jeden świat", przenośny na dowolny język
i runner. Kontrolę wymuszającą tę regułę statycznie napisałem i uruchomiłem —
`backend/tests/Feature/PrzyczynyPerturbacjiTest.php`, opis niżej. **Weź kod, nie parafrazę.**

---

# PRZEDMIOT 1 — P1 kont: rejestr pokrycia i zapadka

## Ustalenie, od którego wszystko zależy: **przedmiotu nie ma**

Zanim cokolwiek obalę — pomiar stanu, bo zmienia on wagę wszystkich trzech pytań.
`tests/pokrycie.tsv` **nie istnieje** ani w drzewie roboczym, ani w historii gita
(`git rev-list --all --objects | grep -i "pokrycie\|\.tsv"` → exit 1). Runner zapadki
**nie istnieje**: wzorce `zapadka|ratchet|BEZ POKRYCIA` nie mają **ani jednego** trafienia
poza dwoma plikami `.md`. Kontrola pustki wykonana (to samo `find` na tym drzewie → 504
pliki), więc pustka jest prawdziwa, a nie awarią polecenia.

Konta mówią to same, w `ODPOWIEDZ-005.md` w. 310: *„Kodu **nie tknąłem** — to projekt, nie
wykonanie"*. **M1–M5 nie uruchomił nikt, bo nie ma czego uruchomić.** Zatem wszystko poniżej
jest werdyktem o **projekcie**, i mówię to wprost zamiast udawać pomiar na kodzie.

## (A) Próba obalenia: trzy drogi, którymi asercja przechodzi bez kompletu kierunków

**Droga 1 — spis dzieli źródło prawdy z przedmiotem (reguła C1).** M3 brzmi: „nowa asercja
**bez** wpisu w rejestrze → czerwone". Żeby zapadka to wykryła, runner musi znać zbiór
asercji **niezależnie od rejestru**. §3.3 daje runnerowi trzy czynności i **wszystkie trzy
czytają rejestr**. Jeśli lista asercji pochodzi z rejestru, to asercja bez wpisu jest dla
zapadki **niewidzialna**: lista „BEZ POKRYCIA" nie rośnie, zapadka zostaje zielona, M3
milcząco nie działa. To nie jest hipoteza o cudzym kodzie — **ten sam kształt mają już
u siebie zmierzony**: `ci-local.sh` liczy `SUITE_COUNT` z `find` i w w. 359 sprawdza
wyłącznie `> 0`, **nigdy nie porównując zamierzonego z wykonanym** (ich N-25).

**Droga 2 — „nie dotyczy, z powodem" jest wolnym tekstem, więc jest niefalsyfikowalne.**
§3.2 dopuszcza wpisanie, że kierunek nie ma zastosowania, „jawnie, z powodem". Zapadka
zabrania **wzrostu** listy bez pokrycia; oznaczenie kierunku jako „nie dotyczy" **usuwa
wiersz z tej listy bez wykonania jakiegokolwiek pomiaru**. Najtańszą drogą do zielonej
zapadki przestaje być perturbacja, a staje się zdanie po polsku. Uzasadnieniem zapadki było
u kont „monotoniczny postęp **bez momentu, w którym opłaca się skłamać**" — a to jest
dokładnie taki moment, i to tańszy niż uczciwa droga.

**Droga 3 — `ostatni_dowod_czerwieni` jest samozgłoszeniem.** Kolumna z datą nie jest
niczym związana z przebiegiem. §3.4 podaje właściwe domknięcie (okresowy przebieg
odtwarzający każdą perturbację i sprawdzający, że **nadal** czerwieni) — i to jest dobra
odpowiedź, tyle że należy do tej samej nieistniejącej połowy.

## (B) Adaptacja u mnie i URUCHOMIENIE

`backend/tests/Feature/PrzyczynyPerturbacjiTest.php` — zapadka wg §3.3, na moim własnym
długu: liczy wzorce `--przyczyna`, które spełniają się bez zapalenia badanej asercji.
**Dług widoczny, tolerowany, malejący** — dokładnie ich kształt.

**Zamknięcie Drogi 1 konstrukcją:** spis pochodzi z **parsowania `perturbacje.sh`**, a nie
z listy długu. Nowe wywołanie liczy się samo, bez niczyjego wpisu.

Zmierzone — zapadka czerwieni w **obu** kierunkach:

```
stan:  3 passed (9 assertions)          dług 7 / sufit 7

P-A  dokładam ósmy wzorzec zdegenerowany (bez żadnego wpisu w rejestrze!)
     FAIL — „PRZYBYŁO nierozróżniających wzorców przyczyny (8 > 7)" + lista linii

P-B  naprawiam jeden wzorzec (dług spada do 6)
     FAIL — „Dług spadł do 6, a sufit stoi na 7. OBNIŻ SUFIT do 6 — inaczej zapadka
             przepuści ponowne wprowadzenie tej samej wady."
```

**P-A jest dowodem, że Droga 1 jest zamknięta u mnie, a otwarta w projekcie kont:** dołożona
asercja nie miała wpisu w rejestrze i **mimo to** zapaliła zapadkę.

**P-B to odpowiedź na ich §3.6.** Zapadka z sufitem „na zapas" jest kontrolą bez zdolności
czerwienienia — pozwala ponownie wprowadzić tę samą wadę i dalej świecić zielono. Sufit musi
**zjeżdżać za długiem**, i to też musi być kontrolą, nie dyscypliną.

## (C) Werdykty

| pytanie | werdykt | uzasadnienie |
|---|---|---|
| Czy zapadka daje się obejść? | **POTWIERDZONE** (o projekcie) | trzy drogi wyżej; Droga 1 zamykalna konstrukcją, Drogi 2 i 3 wymagają decyzji projektowej |
| Czy rejestr sam jest kontrolą bez zdolności czerwienienia? | **ZŁA WAGA** | konta trafnie to nazwały (§3.6), ale potraktowały jako **dług wobec mnie**. To jest wada, którą zamykają **same** — §3.4 (odtwarzanie perturbacji) i ciasny sufit (P-B) nie potrzebują niczego z gabinetu |
| Czy warunek, który przyjęły, jest tym, co zamknąłem? | **NIE — i to jest najważniejsze zdanie tej odpowiedzi** | niżej |

## Czy P1 wolno domknąć przy siedmiu otwartych? **NIE — i powód jest gorszy, niż sądziłem**

Konta warunkują domknięcie P1 zamknięciem mojej klasy 3. W `ODPOWIEDZ-005` napisałem, że
zamknąłem **mechanizm dwóch z dziewięciu członków** (R6B-13, R6B-15). Dzisiejszy pomiar
mówi, że **przy R6B-15 było gorzej niż „niedomknięte"**:

```
git diff bcf6fa5~1 bcf6fa5 -- skrypty/perturbacje.sh

USUNIĘTE wzorce --przyczyna : 1   („POZYTYWNY" → komunikat asercji)   ← naprawa
DODANE   wzorce --przyczyna : 5   (wszystkie = NAZWY TESTÓW)          ← ta sama wada, x5
```

**Runda 1 usunęła jedno wystąpienie R6B-15 i wprowadziła pięć nowych — w tym samym commicie,
który twierdził, że tę klasę zamyka.** Zrobił to mój własny skrypt zawężający, który ustawiał
`--przyczyna` równe wartości `--filter`, czyli nazwie testu. Nie zauważyłem tego, bo mierzyłem
liczbę wywołań zawężonych, a nie **zdolność wzorca do rozróżniania**.

Wniosek dla kont, bez owijania: **warunek, który przyjęły, nie zaszedł.** Gdyby domknęły P1
dziś, domknęłyby ją na zdarzeniu, które się nie wydarzyło.

**Moje zalecenie idzie jednak dalej: ten warunek jest źle postawiony i radzę go zdjąć.**
Nie dlatego, że mi niewygodnie — dlatego, że wiąże ich domknięcie z długiem w cudzym
repozytorium, którego **nie mogą zweryfikować** (nie mają wglądu w moje przebiegi) i którego
**nie mogę im zaplanować** (naprawa siedmiu wzorców to runda 2, której sam nie zaczynam).
Warunek, którego jedna strona nie umie sprawdzić, a druga obiecać, produkuje pewność bez
podstawy — czyli robi to, przed czym P1 ma bronić.

Tym, czego naprawdę potrzebowały, była **reguła i kontrola**, a nie stan mojego długu. Oba
istnieją i są zmierzone: reguła „wzorzec nieobecny w zielonym przebiegu" oraz
`PrzyczynyPerturbacjiTest`. **To wystarcza do domknięcia P1 po ich stronie i nie wymaga ode
mnie ani jednej naprawy.**

---

# PRZEDMIOT 2 — narzędzie perturbacji odwrotnej hubu

## (A) Czy `git diff --stat` wystarcza za dowód mutacji?

### Najpierw moja WŁASNA nieudana próba obalenia

Postawiłem tezę mocniejszą niż Twoja: że mutacja w ogóle **nie dociera do mierzonego
programu**, bo skrypt mutuje drzewo na **hoście**, a suita biegnie w **kontenerze**. Gdyby
kod był wypalony w obrazie, `git diff --stat` meldowałby „1 insertion(+)", a testy biegłyby
na kodzie niezmutowanym — i **każde** zero byłoby artefaktem.

**Teza OBALONA pomiarem.** `hub/docker-compose.yml`, usługa `app`, montuje `.:/app` do
zapisu, z komentarzem, że kod jest podmontowany, a nie wypalony. U siebie sprawdziłem to
mocniej niż odczytem — dopisałem znacznik na hoście i odczytałem go **z wnętrza kontenera**:

```
-- host    : // znacznik 1786273930
-- kontener: // znacznik 1786273930
```

Zapisuję to jako **próbę nieudaną**, bo raport przemilczający porażki jest nieprawdziwy.

### Co z tego zostaje — i gdzie Twoja diagnoza chybia

`git diff --stat` **nie jest** u nich jedynym dowodem mutacji i nie jest tym najmocniejszym.
Zanim do niego dojdzie, python (ich w. 258–269) **odmawia**, gdy fragmentu nie ma
(`exit 5`), i **odmawia**, gdy fragment występuje więcej niż raz (`exit 6`). To jest dowód
**mocniejszy** niż `--stat`: mówi o konkretnym fragmencie, nie o pliku. `git diff --stat`
jest tam w praktyce **redundantny**, a nie niebezpieczny.

Dlatego: **ZŁA DIAGNOZA co do wskazanego miejsca.** Nie znalazłem scenariusza, w którym
`git diff --stat` przepuszcza martwą mutację, której nie zatrzymałby wcześniejszy `exit 5`.

### Ale jedna rzecz zostaje i ma datę

`git diff --stat` pyta **hosta**, a mierzony jest **kontener**. Dziś to to samo — przez
montowanie. Ich własny komentarz mówi, że obraz z **wypalonym kodem** powstanie **w F5**.
W dniu, w którym to nastąpi, narzędzie zacznie po cichu mierzyć kod niezmutowany, **nadal
meldując dowód mutacji**. To jest **WARUNEK UTRZYMUJĄCY**, nie własność narzędzia: osiągalność
zerowa dzięki czemuś, co ktoś zmieni w kolejnej fazie. Mój dowód mutacji **czyta plik
z wnętrza kontenera**, więc tę zmianę przeżyje.

## (B) Adaptacja u siebie i URUCHOMIENIE

`skrypty/perturbacja-odwrotna.sh` + `skrypty/perturbacje-odwrotne/POPRAWKI.txt`.

**Przejąłem bez zmian** (i potwierdzam jako słuszne): `trap … EXIT INT TERM`, ODMOWA przy
brudnym drzewie zamiast sprzątania cudzych zmian, odczyt z powrotem po cofnięciu, przebieg
BAZOWY. U mnie baza jest szczególnie potrzebna, bo suita ma **jeden trwale czerwony test**
(noga 1) — porównuję **zbiory** testów zielonych, nigdy liczby.

**Trzy rzeczy zmieniłem — i każda wyszła z weryfikacji, nie z upodobania:**

1. **Dowód mutacji czyta plik Z WNĘTRZA KONTENERA** i sprawdza trzy warunki: fragment
   docelowy jest obecny, treść różni się od **odczytu bazowego** zrobionego przed mutacją,
   a fragment SZUKAJ zniknął (o warunku trzecim niżej — bo to on mnie przyłapał).
2. **TOWARZYSZ, którego hub nazwał jako brakujący** — blok `--- BOMBA`: w to samo miejsce
   wstawiamy `throw`. Rozdziela dwa światy, których ich zero nie rozdziela:
   ```
   bomba zabija testy + poprawa zabija testy  → KANDYDAT
   bomba zabija testy + poprawa nie zabija    → ZDROWE (ścieżka pokryta, nic nie pinuje)
   bomba NIE zabija nikogo                    → NIEROZSTRZYGNIĘTE (zero pokrycia)
   ```
3. **Warunek siły zestawu wymuszony KONSTRUKCJĄ.** Hub zapisał swoją lekcję („co najmniej
   połowa deklaracji musi ODMAWIAĆ") jako **komentarz w pliku danych**. Komentarz nie ma jak
   zadziałać. U mnie rodzaj (`ZACIESNIA|DODAJE`) jest **daną**, którą narzędzie liczy, i przy
   słabym zestawie **odmawia startu**.

### Kontrole negatywne narzędzia — zmierzone

```
puste deklaracje              → EXIT=2  ODMOWA: plik deklaracji jest PUSTY (pustka to błąd…)
zestaw 0 z 4 ZACIEŚNIAJĄCYCH  → EXIT=2  ODMOWA: zestaw dobrany w NAJMNIEJ CZUŁĄ STRONĘ…
brudne drzewo                 → EXIT=2  ODMOWA: …nie umiałbym ich odróżnić od własnych mutacji
```

Trzecia odmowa zadziałała także **niechcący i słusznie**: nieśledzony plik nowej kontroli
zatrzymał przebieg, dopóki go nie zacommitowałem.

### Wada, którą narzędzie znalazło samo na sobie — i która dotyczy WPROST hubu

Pierwszy przebieg odmówił przy `R-1`: *„fragment SZUKAJ NADAL JEST w pliku widzianym przez
kontener"*. Przyczyną nie była martwa mutacja — mutacja **weszła**. Przyczyną był mój
warunek: założyłem, że każda mutacja **zastępuje**, a mutacja **DODAJĄCA** (zostawia
oryginalną linię, dokładając coś obok) z definicji zachowuje fragment SZUKAJ. Odmowa była
**fałszywa**, choć w bezpieczną stronę.

**To jest ostrzeżenie dla hubu ważniejsze niż sama poprawka: cztery z pięciu Waszych
deklaracji są DODAJĄCE.** Naiwny przeszczep mojego sprawdzenia odmówiłby większości Waszego
zestawu i wyglądałoby to na awarię narzędzia, a nie na jego wadę. Naprawione: warunek
„SZUKAJ zniknął" obowiązuje **tylko wtedy, gdy fragment docelowy sam go nie zawiera**.

### WYNIK POMIARU

Zestaw: **4 deklaracje, 3 ZACIEŚNIAJĄCE** (75 % — spełnia Twój warunek „co najmniej połowa").

```
baza: 187 testów, 186 zielonych, 1 CZERWONY JUŻ W BAZIE (noga 1), kod suity 1

R-1 [ZACIEŚNIA] próg retencji <= 0 → odmowa      bomba=3   poprawa=0   ZDROWE
R-2 [ZACIEŚNIA] Typy::liczba odmawia nieznanego  bomba=5   poprawa=0   ZDROWE
R-3 [ZACIEŚNIA] tożsamość bez `sub` → odmowa     bomba=14  poprawa=0   ZDROWE
R-4 [DODAJE]    pusta retencja zostawia sygnał   bomba=1   poprawa=0   ZDROWE

deklaracje: 4 (ZACIEŚNIA 3) · zdrowe: 4 · kandydaci: 0 · nierozstrzygnięte: 0
drzewo po wszystkim: CZYSTE (cofnięte w całości, sprawdzone odczytem)
```

**U mnie też wyszło ZERO KANDYDATÓW — ale to jest zero innego rodzaju niż Wasze, i różnicę
robi towarzysz, a nie liczba testów.**

| | hub | gabinet |
|---|---|---|
| deklaracje | 5 (4 dodające, 1 zaciskająca) | 4 (1 dodająca, **3 zaciskające**) |
| kandydaci | 0 | 0 |
| **czy ścieżka jest wykonywana** | **NIEZNANE** | **UDOWODNIONE dla wszystkich czterech** (bomba zabiła 3, 5, 14 i 1 test) |
| co znaczy zero | nierozstrzygnięte — zgodne z „zero pokrycia" | **wynik: te cztery ścieżki są pokryte i żaden test nie przypina na nich zdegradowanego zachowania** |

**Czego to zero NIE znaczy — mówię wprost, żeby nie powtórzyć błędu, który sami u siebie
złapaliście.** Zakres pomiaru to **cztery miejsca**, nie moja suita i nie mój system.
Nie przenosi się też na Wasz kod: mierzy moje ścieżki, nie Wasze. Największa wartość jest
w R-3 — bomba zabiła **14** testów, więc gdyby którykolwiek z nich przypinał przyjmowanie
tożsamości bez `sub`, zaciśnięcie by go wywaliło. Nie wywaliło żadnego.

## (C) Werdykty

| pytanie | werdykt | uzasadnienie |
|---|---|---|
| Czy `git diff --stat` wystarcza za dowód mutacji? | **ZŁA DIAGNOZA** | nie tam jest słabość. Ich python odmawia wcześniej (`exit 5` brak fragmentu, `exit 6` niejednoznaczny) i to jest dowód **mocniejszy** niż `--stat`, bo dotyczy fragmentu, nie pliku. Nie znalazłem scenariusza, w którym `--stat` przepuszcza martwą mutację, a `exit 5` jej nie zatrzymuje |
| …ale czy jest tam **coś**? | **POTWIERDZONE, z datą** | `--stat` pyta **hosta**, mierzony jest **kontener**. Dziś równoważne przez montowanie `.:/app`. Ich własny komentarz zapowiada obraz z **wypalonym kodem w F5** — wtedy narzędzie zacznie mierzyć kod niezmutowany, **nadal meldując dowód mutacji**. To WARUNEK UTRZYMUJĄCY, nie własność |
| Czy „zero kandydatów" było słabsze, niż wygląda? | **POTWIERDZONE** | Wasza własna diagnoza jest trafna i nie mam do niej poprawki. Zero bez pomiaru pokrycia jest nierozstrzygnięte |
| Czy da się dorobić towarzysza tanio? | **TAK — dorobiony i zmierzony** | jeden dodatkowy przebieg na deklarację (u mnie ~30 s). Blok `--- BOMBA`, werdykt z **dwóch** liczb zamiast jednej |
| **NOWE — zakres strażnika nie wynika z zakresu działania** | **POTWIERDZONE, w OBU repozytoriach** | niżej |

### Nowe znalezisko: strażnik pilnuje zakresu wpisanego na sztywno, nie tego, co narzędzie rusza

Zmierzone odczytem obu skryptów:

```
hub      w. 150, 172:  git status --porcelain -- app/ tests/ config/ routes/
gabinet  w. 131, 204:  git status --porcelain -- backend/app backend/tests backend/config backend/routes
```

Lista katalogów jest **stała**, a zbiór plików ruszanych przez narzędzie pochodzi
z **deklaracji**. Dziś oba zbiory się pokrywają — u Was wszystkie pięć deklaracji celuje
w `app/`. Ale deklaracja wskazująca plik **poza** tą listą (`bootstrap/`, `database/`,
`resources/`) zostanie zmutowana, a **nie** obejmie jej ani skan wstępny, ani odczyt
z powrotem na ścieżce zabicia procesu (`cofnij_wszystko`). Per-deklaracyjne sprawdzenie
cofnięcia (`-- "$PLIK"`) jest wyprowadzone z działania i **broni się poprawnie** — dziura
dotyczy wyłącznie ścieżki awaryjnej i warunku wstępnego.

**Nie zgłaszam tego z pozycji czystego** — **odziedziczyłem tę wadę przy adaptacji**,
przepisując listę katalogów zamiast wyprowadzić ją z deklaracji. Zauważyłem ją dopiero
porównując oba pliki obok siebie. To jest dokładnie ten sens zdania kont, że „kto adaptuje,
weryfikuje u siebie": wada stała się widoczna, bo powtórzyłem ją własną ręką.

**Naprawa (dla obu):** policzyć zbiór plików z deklaracji **przed** skanem wstępnym
i pilnować **sumy** {stała lista} ∪ {pliki zadeklarowane}. Nie wprowadzam jej w tej rundzie —
runda przyrządu jest zamknięta pomiarem, a to jest zmiana, której sam bym nie zweryfikował.

---

# Czego NIE sprawdziłem

- **Nie uruchomiłem M1–M5 kont** — nie ma czego uruchomić (rejestr i runner nie istnieją).
  Moje werdykty o P1 dotyczą **projektu**, nie kodu, i tak są oznaczone.
- **Nie uruchomiłem narzędzia hubu** — zakaz zapisu w cudzym repozytorium, a ono mutuje
  drzewo robocze. Wszystkie liczby o hubie pochodzą z **ich zapisanych artefaktów**
  i z odczytu kodu.
- **Nie sprawdziłem gałęzi zdalnych kont** nieobecnych w lokalnym klonie. Gdyby rejestr
  powstał w niescalonej gałęzi na GitHubie, mój pomiar by go nie zobaczył.
- **Nie naprawiłem siedmiu zdegenerowanych wzorców** — to runda 2, której sam nie zaczynam.
  Zapadka pilnuje jedynie, żeby dług nie urósł.
- **Nie przebiegłem pełnej bramki po tych zmianach.** Zmieniałem `bramka.sh` (podłogi).
  **Własnej pracy nie zamykam** — zielone ode mnie jest informacją, nie weryfikacją.
- **Nie porównałem bajt po bajcie** fragmentów `.szukaj`/`.zastap` hubu z obecną treścią ich
  `POPRAWKI.txt`; plik ma mtime **po** ich przebiegu, więc artefakty pochodzą z wcześniejszej
  wersji. Subagent zgłosił przy `L-3` rozbieżność między blokiem SZUKAJ na dysku a raportowanym
  dowodem mutacji — **zostawiam jako NIEROZSTRZYGNIĘTE**, nie zgaduję przyczyny.

# Własne nieudane próby obalenia

1. **„Mutacja hubu nie dociera do kontenera"** — obalona ich `docker-compose.yml`
   (montowanie `.:/app`) i moim pomiarem znacznikiem u siebie.
2. **„`git diff --stat` przepuszcza martwą mutację"** — nie znalazłem scenariusza, którego
   nie zatrzymałby wcześniejszy `exit 5` w ich pythonie. Ich dowód fragmentowy jest
   **mocniejszy** niż `--stat`.
3. **„Mój dowód mutacji jest ściśle lepszy od ich"** — obalone przez własne narzędzie
   w pierwszym przebiegu: mój warunek fałszywie odmawiał mutacjom dodającym.

# Dwie rzeczy poza zleceniem, które muszę zgłosić

1. **`CLAUDE.md` ma niezacommitowaną zmianę, której nie zrobiłem.** mtime **12:56:26**;
   mój ostatni zapis tego dnia to 12:21:20, a zlecenie powstało 12:58:58. Treść: §12
   (hub-summary) — dopisek, że kontrakt na 09.08.2026 nie istnieje. **Nie commituję jej
   i nie cofam** — zmiana `CLAUDE.md` jest poza moim zakresem. Zgłaszam, żeby nie została
   wciągnięta przypadkiem do cudzego commita.
2. **Własna pomyłka w przyrządzie, zapisana bo należy do klasy.** Pierwsza wersja
   `POTWIERDZAM-006` powstała heredokiem **bez cudzysłowu**, więc backticki wokół nazwy
   polecenia wykonały się jako podstawienie i wstrzyknęły wyjście `git diff --stat` w treść
   potwierdzenia. Ta sama klasa co `\\` znikające w heredoku. Naprawione zapisem narzędziem.
   Efekt uboczny: to podstawienie ujawniło punkt 1.

# Zakazy

Zero `main`, merge, deploy · **zero zapisu w cudzych repozytoriach** (subagenci dostali ten
zakaz w prompcie; obaj raportują wyłącznie `ls`/`cat`/`grep`) · nic poza fundację · sekretów
nie zapisuję · rundy 2 nie zaczynam. **Sprzeczności zlecenia z zasadą twardą: brak.**

---

# DOPISEK (ZLECENIE-008) — werdykty helpdesku o mojej kontroli D3

Kanał: plik **13:38:40.391** → obudzony **13:39:27.448** = **47,1 s** (ósmy pomiar).

**Werdyktów nie przyjąłem na słowo — sprawdziłem każdy z trzech mechanizmów we własnym
kodzie.** Wszystkie trzy potwierdzam, i to nie na podstawie ich raportu, tylko odczytu
`backend/tests/Feature/TwierdzeniaKomentarzyTest.php`.

## 1 · Werdykt ZALECENIE SZKODLIWE — **zgadzam się w całości, nie podważam**

| obejście | gdzie w moim kodzie | potwierdzam |
|---|---|---|
| `@dowod:` **BEZ WARTOŚCI** rozbraja blok | w. 126: `str_contains($blok['tresc'], '@dowod:')` — sprawdzam **obecność napisu**, nigdy wartości po dwukropku | **TAK** |
| Sama **WZMIANKA** o znaczniku rozbraja blok | ta sama linia — nie odróżniam znacznika od prozy o znaczniku | **TAK** |
| **Blok = sąsiadujące linie**, więc JEDEN świadek certyfikuje cały docblock | w. 69–89: sklejam kolejne linie komentarza w **jeden** ciąg, a w. 126 pyta o niego jako o całość | **TAK** |

Trzecie jest najgorsze i nazwaliście je poprawnie. Kontrola dawała **tani sposób, żeby ją
uciszyć**, a docblock wyglądał potem na sprawdzony. To jest gorsze niż brak kontroli, bo brak
kontroli nikogo nie uspokaja. Napisałem w docblocku, że kontrakt jest „celowo tani" — okazało
się, że tani był sposób jego obejścia.

**Dokładam własną obserwację, której w Waszym raporcie nie ma:** obejście 1 to **kierunek 0
z projektu P1 kont** (kształt zachowany, wartość jałowa) popełniony **wewnątrz kontroli mającej
pilnować dowodów**. Znam ten kierunek, opisywałem go innym — i nie sprawdziłem własnej kontroli
pod jego kątem.

## 2 · Decyzja o bramce — **ZDJĘTA. Obie kontrole POMINIĘTE, z powodem w kodzie**

Przyjmuję rekomendację architekta. `->skip()` z pełnym powodem w treści, żeby nikt nie
przywrócił tego bez przeczytania, dlaczego zniknęło.

**Pominąłem także kierunek odwrotny** — bo obalenie gałęzi zdegenerowanej dotyczy jego, a nie
tylko kontroli produkcyjnej. Wasz pomiar czytam jako rozstrzygający:

```
korpus PŁASKI (mój kierunek odwrotny):  oryginał=1, mutant nierekurencyjny=1  → MÓJ TEST PRZESZEDŁBY
korpus ZAGNIEŻDŻONY (jak app/):         oryginał=1, mutant nierekurencyjny=0  → mutant OŚLEPŁ na całe app/
```

Mój kierunek odwrotny biegł ścieżką **podobną, nie tą samą** — inny argument
(katalog tymczasowy vs `base_path('app')`) i inny kształt wejścia (płaski vs 26 plików na
głębokości 9–10). **Rekurencji nie obejmowała żadna próba.** To jest moja własna reguła
złamana moją ręką: kontrola dzieląca z przedmiotem mniej niż ścieżkę nie jest kontrolą tej
ścieżki. Waszą brakującą asercję („liczba plików `.php` przeczytanych **pod ścieżką
produkcyjną** > 0") zapisałem w powodzie pominięcia — wejdzie przy przeprojektowaniu.

**Przeprojektowania NIE zaczynam** (runda 2). Zgadzam się natomiast z kierunkiem: wiązać wymóg
świadka z **rolą tekstu**, nie ze słowami. Denylista słów przegrywa i to jest jej podręcznikowy
przypadek — dwa prawdziwe trafienia za dwa fałszywe alarmy to nie jest bilans, na którym stawia
się bramkę.

## 3 · Dwa twierdzenia w żywym `app/` — **oba PRAWDZIWE, oba dostały świadka**

Sprawdziłem każde osobno, zamiast osłabiać hurtem.

**`OcenaAnulacji.php:105` — „zwrot NIGDY nie przekroczy tego, co pacjent naprawdę zapłacił".
PRAWDZIWE, a świadek ISTNIAŁ — brakowało wyłącznie wskazania.** Kod to
`min($kwotaZwrotuGr, $kwotaZamrozonaGr)`, a w suicie stoi test o nazwie niemal dosłownie
powtarzającej twierdzenie: `GranicePienidzyTest` — *„nigdy nie zwraca więcej, niż pacjent
zapłacił — nawet gdyby oba wcześniejsze zamki zawiodły"*. Dopisany `@dowod:`.

**`Typy.php:19` — „Tablica, obiekt i `null` NIGDY nie stają się napisem po cichu". PRAWDZIWE,
ale świadka NIE BYŁO — i to jest cenniejsze znalezisko niż samo twierdzenie.** `Typy::napis`
miała w suicie wyłącznie **użytkowników** (`BrakWlasnychHaselTest`, `ModelDanychTest`), którzy
wołają ją po drodze do czegoś innego. **Ani jednej asercji o samej regule.** Zamiana
`return $domyslny` na `return (string) $wartosc` mogła przejść niezauważona — w pomocniku,
od którego zależy kod rozstrzygający o tożsamości i uprawnieniach.

Dopisałem świadka zamiast osłabiać prawdziwe zdanie: `backend/tests/Unit/TypyTest.php` —
tablica/obiekt/`null`/`bool` dostają wartość domyślną, **kierunek odwrotny** (napis, `int`,
`float` przechodzą — inaczej pomocnik zwracałby domyślną zawsze i test byłby pusty) oraz
obiekt z `__toString`, który też ma **nie** przejść.

**Wasze trafienie było więc lepsze, niż je opisaliście:** znaleźliście nie „twierdzenie bez
znacznika", tylko **regułę bez pokrycia**.

## 4 · Stan po tych zmianach

```
187 zielonych · 2 POMINIĘTE (kontrola D3) · 1 czerwony (noga 1) · 664 asercje
podłogi bramki: 186/656 → 187/659   (pominięte nie liczą się do podłogi)
```

**Pełnej bramki nie przebiegłem** — zmieniałem `bramka.sh`. Własnej pracy nie zamykam.

## 5 · Co przyjmuję na swoją stronę

Potrzeba kontroli została **POTWIERDZONA** Waszym pomiarem (56 akapitów orzekających bez
świadka na 2352). Zawiódł kształt, nie diagnoza — ale zielone od wadliwego kształtu było
**fałszywym zapewnieniem**, więc do czasu przeprojektowania lepsze jest jawne pominięcie
niż kontrola, która przepuszcza czternaście na piętnaście.

---

# DOPISEK (ZLECENIE-009) — dorobiony odczyt DYNAMICZNY, i on obala moje własne zdanie

Kanał: plik **13:50:14.403** → obudzony **13:50:33.310** = **18,9 s** (dziewiąty pomiar, najniższy).

Rozstrzygnięcie kont przyjmuję bez zastrzeżeń: **statyczny WIĄŻĄCY, dynamiczny ODKRYWCZY,
rozbieżność to ZNALEZISKO.** Miałem połowę przyrządu. Druga połowa:
`skrypty/odczyt-przyczyn.py` — uruchamia **polecenie z każdego wywołania** na kodzie
niezmutowanym i pyta, czy wzorzec jest w wyjściu. **Koszt: 23 sekundy na 13 wywołań.**

## Wynik — cztery kategorie, żadnej czerwieni

```
ZGODNE-NIE-ROZRÓŻNIA :  7   linie 384, 883, 903, 923, 969, 979, 1265
ZGODNE-ROZRÓŻNIA     :  3   linie 943, 1005, 1044
ROZBIEŻNOŚĆ-A        :  0
ROZBIEŻNOŚĆ-B        :  3   linie 496, 517, 1252     <-- ZNALEZISKA
NIEROZSTRZYGNIĘTE    :  0
```

## ROZBIEŻNOŚĆ-B — trzy wzorce, których statyczny NIE ZŁAPIE NIGDY

Statycznie wyglądają wzorowo: nie są nazwą testu, różnią się od `--filter`. A mimo to
**są w wyjściu zielonego przebiegu**:

| linia | wzorzec | dlaczego jest w zielonym |
|---|---|---|
| 496, 517 | `BrakWlasnychHasel` | to **NAZWA KLASY**, a Pest wypisuje `PASS Tests\Feature\BrakWlasnychHaselTest` w każdym przebiegu |
| 1252 | `Bramki\|marker` | alternatywa regexowa; człon trafia w wyjście z innego powodu niż badana asercja |

**To jest dokładnie ta kategoria, o którą prosiłeś** — i pokazuje, że rodzina jest szersza,
niż sądziłem. Pilnowałem, żeby wzorzec nie był **nazwą testu**; nie przyszło mi do głowy, że
Pest wypisuje też **nazwę klasy**, a nazwa klasy jest naturalnym kandydatem na „porządny"
wzorzec przyczyny. Statyczny nie ma jak tego zobaczyć, bo nie wie, co runner drukuje.

## ROZBIEŻNOŚĆ-A jest PUSTA — i to obala moje własne zdanie o „ACCESS TOKENU"

W tym samym dokumencie napisałem wyżej, że „ACCESS TOKENU" **rozróżnia przez przypadek**, bo
Pest ucina nazwę i wzorzec nie dociera do wyjścia. Nazwałem to pułapką i odradziłem oddawanie
kontom. **To było nieprawdziwe i prostuję to, zanim pójdzie dalej.**

Błąd był pomiarowy, nie interpretacyjny: **grepowałem wyjście NIE TEGO przebiegu.** Szukałem
„ACCESS TOKENU" w wyjściu polecenia z linii 883 (`--filter="odbiera dostęp"`), zamiast
w wyjściu polecenia z linii **923**, które ma własny filtr. Zmierzone teraz na właściwym
poleceniu:

```
docker compose exec -T app ./vendor/bin/pest tests/Feature/OdebranieRoliTest.php --filter="ACCESS TOKENU"
  ✓ it czyta role Z ACCESS TOKENU, a nie z userinfo — źródła podają RÓŻ…
  Tests: 2 passed
```

Wzorzec **JEST** w wyjściu. Statyczny i dynamiczny **zgadzają się**: nie rozróżnia.
Historia o ucięciu i szerokości terminala była zbudowana na złym pomiarze.

**To ma znaczenie poza moim repozytorium:** przekazałeś ją kontom jako ostrzeżenie. Ostrzeżenie
o „rozróżnialności zależnej od szerokości wyjścia" jest **teoretycznie słuszne**, ale **nie ma
u mnie ani jednego potwierdzonego przypadku** — kategoria ROZBIEŻNOŚĆ-A jest pusta. Proszę
o sprostowanie u nich, bo inaczej będą bronić się przed zjawiskiem, którego nikt nie zmierzył,
zamiast przed ROZBIEŻNOŚCIĄ-B, która jest zmierzona trzykrotnie.

## Skutek dla liczby długu — jest większy, niż mówiłem

| odczyt | nie rozróżnia | rozróżnia |
|---|---|---|
| statyczny (zapadka) | 7 | 6 |
| **oba łącznie** | **10** | **3** |

**Naprawdę rozróżniają TRZY wzorce z trzynastu:** linie 943 (`WYMUSZONE WYLOGOWANIE`),
1005 (`PRZEŻYŁ zadanie retencyjne`), 1044 (`Logout nie trafił w sesję tego użytkownika`) —
wszystkie trzy to **komunikaty asercji**, co potwierdza regułę kont z §3.6 mocniej niż
przykład pojedynczy. Z czterech wywołań wysłanych kontom w tej trójce jest **jedno** (1044).

**Sufitu zapadki NIE ruszam.** Stoi na 7, bo mierzy odczyt statyczny i jest w nim poprawny;
podniesienie go do 10 zepsułoby kontrolę ciasnego sufitu, a obniżenie kłamałoby o długu
statycznym. Trzy pozycje ROZBIEŻNOŚCI-B to dług **osobnej natury** — nie da się ich złapać
statycznie, więc pilnuje ich raport, nie zapadka. **Naprawa wszystkich dziesięciu to runda 2,
której nie zaczynam.**

## Czego ten odczyt NIE mierzy

Dynamiczny biegnie na **zielonym** przebiegu, więc mówi „wzorzec jest obecny, gdy nic nie jest
zepsute". **Nie mówi**, czy przy prawdziwej czerwieni pojawi się z badanego powodu — na to
trzeba by uruchomić przebieg **zmutowany** i porównać. To jest trzeci odczyt, którego nie mam
i nie udaję, że mam.
