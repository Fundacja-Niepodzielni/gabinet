# Dziennik nocy z 8 na 9 sierpnia 2026

Zapis chronologiczny. Godzina, co robiłem, co ZMIERZYŁEM, wynik. Surowe liczby.
Pisany na bieżąco, po każdej ukończonej pozycji — nie zbiorczo na końcu.

Punkt wyjścia: gałąź `faza-1-retencja`, HEAD `49131d8`, drzewo czyste.
Bramka wieczorem: CZERWONA, 1 nieudany krok z 22 (noga 1 — zamierzony).

---

## 23:34 — orientacja w stanie

Przeczytane: `CLAUDE.md`, `PLAN-FAZ.md` (CURRENT WORK), `WYTYCZNE-PRACY.md`
(sekcje o weryfikacji, przyrządach, regule C1, gałęzi zdegenerowanej),
`backend/tests/Feature/OdebranieRoliTest.php`, `backend/app/Tozsamosc/*`,
nagłówek `skrypty/bramka.sh`.

Zmierzone:

- `git rev-parse HEAD` → `49131d8d0bbe73991ea4283b7bd631fc17b0b751`
- `git status --short` → pusto (drzewo czyste)
- `docker info` → serwer 29.3.1, kontenerów 60, obrazów 55
- Chodzące CUDZE stosy (nie dotykam): `helpdesk-weryf-bd60adb-*` (10 kontenerów),
  `helpdesk-zammad-*`, `control-plane*`, `niepodzielni-hub*`, `np-*`, `trydive*`
- Chodzące MOJE stosy: `gabinet-*` (deweloperski, 10 h), `gabinet-perturbacje-*` (9 h)
- Porty planowane dla rundy 6 (`netstat`): 8107, 55461, 56407, 8108 — wszystkie WOLNE

## 23:40 — RUNDA 6 ZLECONA (zadanie główne)

Runda przypięta do SHA `49131d8` — identyfikator nazywa ZDARZENIE, nie stan
bieżący, więc się nie starzeje (reguła z WYTYCZNE-PRACY).

Zlecona DWÓM świeżym agentom-weryfikatorom, każdy na WŁASNYM czystym klonie.
Ja jej nie wykonuję — pisałem ten kod, więc nie mogę go weryfikować.

**Dlaczego dwóch, a nie jeden**: zakres a–f nie mieści się w jednej sesji bez
utraty jakości. Podział przebiega po linii „potrzebuje żywego stosu / nie
potrzebuje", żeby dwa ciężkie stosy dockerowe NIE biegły równolegle na maszynie
z 60 kontenerami. Współbieżne obciążenie to znany generator FAŁSZYWEJ CZERWIENI
(timeouty sond) — a runda skażona fałszywą czerwienią jest bezwartościowa.

- **Weryfikator A („stos")** — klon `/d/tmp/gabinet-r6a`, projekt compose
  `gabinet-r6a`, porty 8107 / 55461 / 56407, sekrety budowane przez bramkę
  z `.env.example` (nigdy `.env` dewelopera).
  Zakres: bramka (surowe liczby) · **(b)** zielony z niewłaściwego powodu —
  metodą USUŃ MECHANIZM I SPRAWDŹ, CZY DALEJ ZIELONY · **(c)** próba OBEJŚCIA
  wąskiego gardła §2 (nie odczyt kodu — atak) · **(f)** rozdzielenie
  przestrzeni kluczy Redisa na ŻYWYM Redisie + pytanie o eksmisję jako
  własność INSTANCJI · **(g)** pytanie obowiązkowe rundy.
- **Weryfikator B („analiza")** — klon `/d/tmp/gabinet-r6b`, BEZ dockera.
  Zakres: **(a)** przegląd WSZYSTKICH dyskryminatorów pod gałąź zdegenerowaną,
  z tabelą „jakie światy dają tę wartość" · **(d)** czy migawki nogi 1 mierzą
  to, co deklarują + PROJEKT odczytu rozstrzygającego, jawnie NIEWDRAŻANY ·
  **(e)** czego jeszcze nie pokrywają podmienione sterowniki, jako RÓŻNICA
  wobec D-2026-08-08-27 · **(g)**.

Oba zlecenia zawierają twarde zakazy: zero zmian w drzewie roboczym wykonawcy,
zero commitów i pushy, zero dotykania cudzych stosów, **zero kasowania obrazów**
(`prune` kasuje po wieku i braku referencji, nie po nazwie — zabrałby obraz spod
cudzego stosu). Zakres sprzątania: kontenery + wolumeny własnego projektu + klon.

Treść obu zleceń jest zapisana w tym samym katalogu, w osobnym pliku
o nazwie zaczynającej się od słowa „zlecenie".

Status: oba biegną. Wynik odbiorę i zapiszę — **nie naprawiam znalezisk tej
samej nocy** (naprawa autorem, bez rundy, byłaby rano stertą zmian bez pokrycia).

## 23:55 — Z2 ruszone równolegle (bo czekanie nie jest pracą)

Weryfikatorzy pracują na własnych klonach, więc moja praca w drzewie roboczym
im nie przeszkadza, a runda jest przypięta do SHA `49131d8` i moje zmiany jej
nie unieważniają. Zamiast czekać — Z2 (uzgodnienie dokumentów z rzeczywistością).

**Pomiar suity** (mój stos deweloperski, baza `gabinet_test`):

```
$ docker exec gabinet-app ./vendor/bin/pest
  Tests:    1 failed, 180 passed (640 assertions)
  Duration: 44.53s
  FAILED  OdebranieRoliTest > NOGA 1 …  Expected 401 but received 200
```

- CURRENT WORK twierdzi „180 zielonych, 1 czerwony, 640 asercji" → **PRAWDA**.
- CURRENT WORK twierdzi „30 scenariuszy perturbacji" → **PRAWDA**
  (`grep -oE "^p_[a-z0-9_]+\(\)" skrypty/perturbacje.sh | wc -l` → 30).
- Jedyny czerwony to noga 1 → **PRAWDA**, pozostałe 12 testów w tym pliku zielone.

**Uwaga o moim własnym przyrządzie — złapane na sobie.** Pierwszy pomiar
uruchomiłem jako `docker exec -T …`; `-T` nie istnieje w `docker exec` (to flaga
`docker compose exec`). Polecenie wypisało pomoc i NIC nie zmierzyło, a harness
zaraportował „exit code 0". Drugi przebieg poszedł przez `| tail -45`, więc kod
wyjścia znowu należał do `tail`, nie do pesta. Dwa razy z rzędu „0" było zgodne
ze światami „wszystko zielone" ORAZ „pomiar w ogóle się nie wykonał". Dokładnie
badana dziś klasa — tym razem u mnie. Wniosek czytam z TREŚCI wyjścia, nie z kodu.

## 00:20 — NAPRAWA PRZYRZĄDU (1): podłogi bramki (znalezisko N-2)

`MINIMUM_TESTOW` 170 → **180**, `MINIMUM_ASERCJI` 590 → **635**, przy stanie
181 / 640. Powód: zapas 11 testów mieścił skasowanie w całości 10 z 17 plików
kontrolnych, w tym `ObietniceKomentarzyTest` (kontrola NAD kontrolami).
Komentarz nad stałą obiecywał „TUŻ POD stanem bieżącym" i był nieprawdziwy
wobec własnej wartości. Szczegóły i rozkład testów na pliki: `ZNALEZISKA.md`, N-2.

## 00:35 — NAPRAWA PRZYRZĄDU (2): perturbacje przestały psuć (znalezisko N-3)

Najpoważniejsze znalezisko własne tej nocy i **spowodowane moją własną zmianą
z wieczora**: commit `cdc6fbb` przemianował `$konta` → `$tozsamosc`, przez co
dwie perturbacje przestały cokolwiek podmieniać — a mimo to meldowały sukces.

Trzy zabezpieczenia zawiodły po kolei (dowody w `ZNALEZISKA.md`, N-3):
brak sprawdzenia kodu wyjścia perturbacji · dowód mutacji w formie negatywnej
(gałąź zdegenerowana) · oczekiwana czerwień dostarczana przez niepowiązaną
czerwień nogi 1.

Naprawa jest STRUKTURALNA, nie jest łataniem dwóch napisów:

1. **`perturbuj()` sprawdza kod wyjścia.** Nieudana mutacja liczy się jako
   `NIEUDANE` i ustawia `MUTACJA_ZERWANA=1`. Jeden punkt, chroni 30 scenariuszy.
2. **`dowod_mutacji()` odmawia orzekania**, gdy `MUTACJA_ZERWANA=1` — dowód nie
   może zaświadczać o mutacji, której nie było.
3. **Nowy `dowod_zniknieciem()` — dowód z ODCZYTEM BAZOWYM.** Pyta nie „czy
   tekstu nie ma", tylko **„czy tekst BYŁ, a potem ZNIKNĄŁ"**; odczyt bazowy
   bierze z kopii sprzed mutacji, którą i tak robi `zachowaj`. To rozróżnia
   świat „mutacja usunęła tekst" od świata „tekstu nigdy tam nie było".
   Gdy wzorca nie było w kopii, komunikat brzmi wprost: „PERTURBACJA ROZJECHAŁA
   SIĘ Z KODEM" — czyli tej nocy zapaliłby się od razu i z właściwą przyczyną.
4. Wszystkie **5 dowodów w formie negatywnej** przepisane na `dowod_zniknieciem`.
   Zmierzone po zmianie: `grep -c 'bash -c "! grep -q'` → **0**;
   `grep -c "dowod_zniknieciem "` → **5**; `bash -n skrypty/perturbacje.sh` → OK.
5. Dwa nieaktualne wzorce podmiany w `perturbuj.py` uzgodnione z kodem bieżącym
   (`$tozsamosc->dane`, `$tozsamosc->sid()`).

Naprawiam to mimo reguły „nie naprawiaj przedmiotu", bo to jawny WYJĄTEK: zepsuty
był sam przyrząd. Bez tego runda 7 mierzyłaby własną bezczynność.

## 00:50 — WERYFIKACJA naprawy przyrządu (nie deklaracja, pomiar)

Naprawa przyrządu, której się nie sprawdziło, jest kolejną obietnicą bez pokrycia.
Trzy pomiary:

**1. Czy mutacje znowu wchodzą w życie** (po każdej `git checkout --`, drzewo czyste):

```
$ python3 skrypty/perturbuj.py role-zamrozone      → EXIT=0, git diff: 1 insertion(+), 3 deletions(-)
$ python3 skrypty/perturbuj.py uniewaznienie-po-sid → EXIT=0, w pliku: 78: if (false) {
$ git status --short                               → 0 zmian (stan przywrócony)
```

**2. Czy nowy dowód ROZRÓŻNIA światy** — wyłuskałem samą funkcję `dowod_zniknieciem`
do osobnego pliku i podałem jej trzy spreparowane sytuacje. To kontrola ścieżką
NIEZALEŻNĄ od perturbacji (reguła C1: kontrola nie może dzielić mechanizmu ze
swoim przedmiotem):

```
świat I   (tekst był w kopii, znikł z pliku = mutacja weszła)   → rc=0 ZALICZONE  ✓
świat II  (tekstu NIGDY nie było w kopii = perturbacja rozjechana) → rc=1 ODRZUCONE ✓
          komunikat: „PERTURBACJA ROZJECHAŁA SIĘ Z KODEM: wzorca … NIE BYŁO … przed mutacją"
świat III (tekst nadal w pliku = mutacja nie usunęła)           → rc=1 ODRZUCONE ✓
NIEUDANE=2 (oczekiwane 2)
```

**Świat II to dokładnie dzisiejsza awaria.** Stary dowód dawał w nim „prawda",
nowy odrzuca i nazywa przyczynę. Czyli przyrząd nie tylko został naprawiony, ale
UMIE ZAŚWIECIĆ CZERWONO na tę konkretną awarię — a to jest jedyny sensowny
dowód naprawy kontroli (D-0013).

**3. Składnia:** `bash -n skrypty/perturbacje.sh` → OK.

Czego ta weryfikacja NIE obejmuje, i mówię to wprost: nie uruchomiłem pełnego
zestawu 30 perturbacji na żywym stosie (wymaga własnego projektu compose i
kilkudziesięciu minut, a maszyna obsługuje teraz weryfikatora rundy 6).
Pełny przebieg zostaje jako pierwsza rzecz do zrobienia rano — wpisane
do `PODSUMOWANIE.md`.

## 00:10 — RUNDA 6 część B ODEBRANA (17 znalezisk)

Raport w całości zapisany do repozytorium: `RUNDA-6-B-RAPORT.md` (719 wierszy).
Weryfikator posprzątał po sobie (klon `/d/tmp/gabinet-r6b` usunięty), drzewo
robocze nietknięte — sprawdzone `git status`.

**Runda NIE jest zerowa, więc F1 zostaje otwarte** (reguła zbieżności rund,
D-2026-08-07-16). Streszczenie i rozliczenie wobec moich napraw: `ZNALEZISKA.md`.

Rzecz, którą warto zapisać osobno, bo dotyczy metody: weryfikator zauważył, że
mój `HEAD` przesunął się w trakcie jego pracy (`49131d8` → `c3a11c0`) i **sam
z siebie przypiął cały raport do `49131d8`**, zamiast po cichu mieszać dwa stany.
Dokładnie tak ma działać identyfikator nazywający zdarzenie, a nie stan bieżący.

**Zbieżność dwóch niezależnych torów:** jego R6B-3 to moje N-3 (martwe
perturbacje). Doszliśmy do tego zupełnie różnymi drogami — ja przez przegląd form
dowodu mutacji, on przez porównanie wzorców `perturbuj.py` z kodem. To mocniejszy
dowód niż każdy z osobna.

**Zapisuję też przeciw sobie:** moja naprawa podłóg (N-2, 170→180) POWIĘKSZYŁA
rozjazd, który weryfikator nazwał R6B-12 — perturbacje dowodzą podłóg 100/300,
a bramka egzekwuje teraz 180/635. Naprawa była słuszna i jednocześnie otworzyła
lukę w innej kontroli. Raport, który to przemilcza, byłby nieprawdziwy.

## 00:17 — Z3: przegląd ustawień domyślnych (PostgreSQL, Redis) + eksperyment

Trzy znaleziska własne, wszystkie zmierzone na żywych kontenerach, żadnej zmiany
konfiguracji (Z3 jest przeglądem): **N-5** (PostgreSQL — pięć wartości domyślnych
o realnych skutkach, w tym `idle_in_transaction_session_timeout = 0`, które przy
CLAUDE.md §6 potrafi zablokować termin na zawsze), **N-6** (Redis — przy
`maxmemory = 0` i `noeviction` eksmisja LRU **nie może zajść**, więc wyzwalacz
nazwany w D-2026-08-08-28 nie zachodzi w tej konfiguracji; zachodzi inny —
odrzucanie ZAPISÓW), **N-7** (patrz niżej).

**N-7 — eksperyment, który wyszedł inaczej, niż zakładałem.** Zobaczyłem klucze
cache'u w bazie 0 i założyłem „pozostałości sprzed rozdzielenia". TTL to obaliły:
były ŻYWE (35–809 s), czyli ktoś je pisał w tej chwili. Sonda świeżym procesem
(`artisan tinker` w obu kontenerach) pokazała zapis do db1 — więc winowajcą mogły
być tylko procesy starsze od zmiany konfiguracji, czyli workery Horizona.

Test rozstrzygający: restart Horizona i obserwacja, czy db0 przestaje rosnąć.

```
00:14:50  przed: 34 klucze cache w db0, najwyższy TTL 706 s
00:17:12  po:    20 kluczy,             najwyższy TTL 559 s
          706 − 559 = 147 s przy 142 s zegara → czysty rozpad, zero nowych zapisów
```

Zamiast „pozostałości" wyszło coś poważniejszego: **przez półtorej godziny połowa
systemu pisała do starej przestrzeni kluczy**, bo długo żyjące procesy czytają
konfigurację wyłącznie przy starcie. To musi trafić do procedury wdrożeniowej F9.

Gdybym poprzestał na pierwszej hipotezie („to pozostałości, wygasną"), zamknąłbym
sprawę wnioskiem wygodnym i nieprawdziwym.

## 00:20 — SPROSTOWANIE WŁASNEGO WNIOSKU sprzed dziesięciu minut

Napisałem wyżej „po restarcie NIE POWSTAJE ani jeden nowy klucz — db0 już tylko
wygasa". **Cofam tę część.** Kolejny odczyt ją podważył:

```
00:17:12  najwyższy TTL w db0 = 559 s
00:18:59  najwyższy TTL w db0 = 86400 s   ← klucz zapisany przed chwilą
00:19:46  najwyższy TTL w db0 = 406 s     ← tamtego klucza już nie ma
```

86400 s to DOKŁADNIE `RejestrSesji::CZAS_ZYCIA_SEKUND`
(`backend/app/Tozsamosc/RejestrSesji.php:23`). Sprawdziłem, czym pisze:
`Cache::put(...)` (`:33`), czyli magazynem cache'u — a ten idzie do db1. Więc
klucz o tym TTL **nie ma prawa** znaleźć się w db0, a jednak się tam znalazł
i po chwili zniknął.

**Co pozostaje zmierzone i prawdziwe:** masa kluczy w db0 opada dokładnie
w tempie zegara (706→559→406 przy 142 s i 154 s przerwy), czyli GŁÓWNY zapis
ustał po restarcie Horizona. To trzyma się dowodowo.

**Czego NIE wolno mi już twierdzić:** że zapisy do db0 ustały CAŁKOWICIE. Jeden
odczyt mówi, że nie. Nie znam mechanizmu, nie zgaduję go o tej porze — idzie do
`ODLOZONE.md` z pełnym stanem i objawem.

To druga tej nocy sytuacja, w której mój własny wniosek nie przeżył kolejnego
pomiaru. Zapisuję ją tak samo jak pierwszą: wniosek postawiony za wcześnie kosztuje
tyle, ile go się zostawi w dokumencie.

## 00:30 — RUNDA 6 część A ODEBRANA (12 znalezisk) + odpowiedź architekta

**Sprzątanie A sprawdzone przeze mnie, nie przyjęte na słowo:** kontenery `r6a` —
BRAK, wolumeny `r6a` — BRAK, klon — BRAK, obraz `gabinet-r6a-app:local` ZOSTAJE
(zamierzone), 42 cudze kontenery nadal działają.

**Wynik główny: przyczyna nogi 1 USTALONA — to wada PRZYRZĄDU, nie systemu.**
Trzy niezależne tory dały ten sam mechanizm (singleton `StartSession`).
Szczegóły i surowe wydruki: `ZNALEZISKA.md`, sekcja rundy 6 A.
**Testu NIE naprawiam** — zlecenie mówi „noga 1 ma zostać czerwona", a naprawa
kontroli bezpieczeństwa przez autora, w nocy, bez rundy, zamieniłaby jedyny
uczciwy czerwony na zielony bez pokrycia.

**Architekt odpowiedział na O-N1** i miał rację w sposób, który warto zapisać:
zbieżność TTL 86400 s z `RejestrSesji::CZAS_ZYCIA_SEKUND` uznałem za trop, a
**86400 to po prostu doba** — najczęstsza wartość TTL w oprogramowaniu w ogóle.
Błąd częstości bazowej. Rozstrzyga NAZWA klucza, nie TTL.
Zmierzyłem sam (nie przyjąłem pomiaru architekta na słowo): db0 zawiera
**wyłącznie 5 kluczy `gabinet_horizon:*`**, zero kluczy rejestru sesji.
**O-N1 zamknięte.** Przy okazji obniżyłem wagę własnego N-7 — mój „test
rozstrzygający" przez restart Horizona był zgodny z dwoma światami, więc
zmierzyłem zgodność z hipotezą, nie jej wyłączność. Trzeci raz tej nocy
ta sama wada u mnie.

## 00:45 — NAPRAWY PRZYRZĄDU (3) i (4) ze znalezisk rundy 6

**(3) R6A-10 — `bramka.sh` ignorował `--projekt` przy nazwie pliku środowiska.**
`PLIK_ENV` liczone w linii 73, `--projekt` parsowane w 98. Skutek gorszy niż
mylna nazwa: dwa przebiegi o RÓŻNYCH projektach dzieliły JEDEN plik
z wygenerowanym `APP_KEY` i `DB_PASSWORD`, a zamek (per projekt) ich nie
rozdzielał. Definicja przeniesiona pod pętlę parsującą (teraz linia 141 vs 126).
Zmierzone po naprawie: odtworzenie samej logiki z `--projekt gabinet-r6x` daje
`PLIK_ENV=/tmp/.env.bramka.gabinet-r6x`; `bash -n` czysty.

**(4) R6A-9 — `PLAN-FAZ.md` miał DWIE sekcje `CURRENT WORK`.** Druga niosła
„`BRAMKA OK — 21 kroków, 0 nieudanych`", „151 testów", „20 scenariuszy" i była
sprzeczna z pierwszą. Przemianowana na „F1 — rozpiska zadań fazy (to NIE jest
sekcja stanu)", nieaktualne liczby usunięte ze SPROSTOWANIEM. Zmierzone:
`grep -c "^## CURRENT WORK"` → **1**.

**Pomyłka przy tej naprawie — zapisuję, bo dotyczy przyrządu, którym jestem ja.**
Podmieniając sekcję „Do rozstrzygnięcia" wskazałem zakres od jej nagłówka do
`## F0 — Fundament` i skasowałem przy okazji **rozpiskę zadań F0, blokery
i rozpiskę F1** — 10 831 znaków. Zauważyłem to natychmiast, bo skrypt wypisał
liczbę usuniętych znaków, i **dlatego** zapisywałem usuwaną treść do pliku,
zanim ją nadpisałem. Przywrócone (6 719 znaków), sprawdzone: jedna sekcja
`CURRENT WORK`, zero duplikatów tabel, `F1.7` na miejscu.
Lekcja: podmiana „od kotwicy do kotwicy" w dokumencie o wielu sekcjach to
operacja o zasięgu większym, niż wygląda. Zapis usuwanego fragmentu kosztował
jedną linijkę i uratował trzy tabele.

## 00:50 — CURRENT WORK zaktualizowane Z ODCZYTU

Nie z pamięci i nie z poprzedniego zapisu: liczby pochodzą z dwóch niezależnych
pomiarów (mój stos i czysty klon weryfikatora — identyczne 180/1/640).
Dopisane wprost ostrzeżenie, którego wcześniej nie było: **„nie cytuj »30
scenariuszy« jako miary pokrycia"**, bo pięć z nich nie może dziś zaświecić.
Sekcja „PIERWSZE ZADANIA NASTĘPNEJ SESJI" ułożona wg jednej zasady: najpierw
przywróć zdolność przyrządu do świecenia czerwono, potem mierz nim cokolwiek.

## 00:55 — KONTROLNY przebieg bramki po moich zmianach w niej

Zmieniałem `bramka.sh` (podłogi, `PLIK_ENV`) i `perturbacje.sh` (dowód mutacji),
więc zostawienie ich bez przebiegu byłoby dokładnie tą obietnicą bez pokrycia,
którą całą noc opisuję. Przebieg na WŁASNYM, izolowanym projekcie:
`--projekt gabinet-noc-kontrola`, porty 8109 / 55471 / 56417.

**Dowód naprawy R6A-10, wprost z systemu plików:**

```
$ ls -la .env.bramka.*
-rw-r--r-- 5175 Aug  8 19:03 .env.bramka.gabinet-bramka        ← ślad SPRZED naprawy
-rw-r--r-- 5300 Aug  9 00:38 .env.bramka.gabinet-noc-kontrola  ← PO naprawie: podąża za --projekt
```

Przed naprawą przebieg z `--projekt gabinet-noc-kontrola` zbudowałby plik
o nazwie `gabinet-bramka` — tak jak zrobił to weryfikator rundy 6, który dostał
`.env.bramka.gabinet-bramka` przy `--projekt gabinet-r6a`. Teraz nazwa jest
zgodna z projektem, więc dwa przebiegi nie mielą jednego zestawu poświadczeń.

Stos kontrolny wstał: 6 kontenerów `healthy`.

## 01:05 — bramka kontrolna: DRUGI czerwony, którego sam narobiłem (N-8)

Pierwszy przebieg kontrolny: **`BRAMKA CZERWONA — 2 nieudanych kroków z 22`**.
Drugim czerwonym był krok `[21] sekrety (gitleaks)`: `leaks found: 1`.
„Sekretem" okazała się **nazwa pliku we własnym dzienniku** — heurystyka
`generic-api-key` nie odróżnia jej od klucza API. Zlecenie mówiło „jeden
czerwony ma zostać czerwony", a ja dokumentowaniem zrobiłem drugi.

Naprawione dwoma niezależnymi posunięciami (zdanie przeredagowane + najwęższy
możliwy wyjątek: jedna reguła, jeden katalog), z **dowodem falsyfikowalności** —
przynęta `DB_PASSWORD` i `SMSAPI_TOKEN` w tym samym katalogu nadal zapala skan
(`leaks found: 6`). Szczegóły: `ZNALEZISKA.md` N-8; zasada na przyszłość
oddana do rozstrzygnięcia: `DO-DECYZJI.md` D-1.

## 01:10 — PRZEBIEG KOŃCOWY BRAMKI (stan, w jakim zostawiam drzewo)

```
=== [19] testy (Pest)      Tests: 1 failed, 180 passed (640 assertions)   ← NOGA 1
=== [20] testy realnie SIĘ WYKONAŁY   WYKONANO 181 testów (podłoga: 180)
                                      sprawdzono 640 asercji (podłoga: 635)
=== [21] sekrety (gitleaks)           56 commits scanned.  no leaks found
=== [22] sprzątanie (down -v, wyłącznie projekt gabinet-noc-kontrola)

BRAMKA CZERWONA — 1 nieudanych kroków z 22
```

**Dokładnie jeden czerwony, ten zamierzony.** Wszystkie 22 kroki przebiegły,
podłogi po podniesieniu przechodzą (181 ≥ 180, 640 ≥ 635), skan sekretów czysty.
Zmiany, które w nocy wprowadziłem do samej bramki i do perturbacji, są tym
przebiegiem sprawdzone od początku do końca — nie deklaracją, tylko przebiegiem.

Sprzątanie kontrolne: kontenery `noc-kontrola` — BRAK, wolumeny — BRAK,
43 cudze kontenery nietknięte.

## 01:15 — lekcje nocy dopisane do WYTYCZNE-PRACY.md

Osiem lekcji, każda z instancją zmierzoną tej nocy: dowód mutacji z odczytem
bazowym · refaktor unieważnia perturbacje cytujące kod · perturbacja celująca
w plik już czerwony nie może paść, a `--przyczyna` musi być komunikatem asercji ·
zbieżność liczb jest tropem tylko przy liczbie RZADKIEJ (86400 to doba) · kod
wyjścia potoku należy do ostatniego polecenia · naprawa jednej kontroli potrafi
powiększyć lukę w drugiej · dokumentacja potrafi zapalić bramkę · podmiana
„od kotwicy do kotwicy" ma zasięg większy, niż wygląda.

## 01:25 — odpowiedź architekta na D-1 ZASTOSOWANA

Architekt potwierdził mój wariant docelowy (3 z elementem 1) i odrzucił wariant 2
(`docs/**`) — z mojego własnego argumentu. Ale **przesunął uzasadnienie**, i to
jest cenniejsze niż sama decyzja: nie chodzi o to, żeby skaner był ostry, tylko
o to, że **raporty nie potrzebują pełnych identyfikatorów**. Wartość dowodowa
jest w RELACJI między odczytami, nie w konkretnej wartości; pełny identyfikator
sesji w dokumencie to sam w sobie drobny wyciek. Skracanie usuwa PRZYCZYNĘ
i obowiązywałoby, **gdyby żaden skaner nie istniał**.

Historii `83775f4` nie przepisuję — nie przepisujemy wypchniętej historii dla
samej czystości. Wyjątek per katalog zostaje jako zawór na historię, której nie
da się już zmienić; dyscyplina skracania obowiązuje NAPRZÓD.

**Wdrożone (naprawa przyrządu, 5):** komunikat kroku `[21] sekrety` w
`skrypty/bramka.sh` **uczy teraz właściwej naprawy**. Rozumowanie architekta,
które warto zapamiętać: obie drogi do czerwieni w katalogu raportowym —
zapomniany wyjątek i niezredagowany cytat — kończą się tak samo, więc kontrola
jest **fail-closed** i to jest dobra wiadomość. Niebezpieczna nie jest czerwień,
tylko **ODRUCH, jaki wywoła**: najtańszą reakcją na „leaks found" jest dopisanie
wyjątku, a nie skrócenie cytatu. Jedno zdanie w komunikacie zamienia domyślny
odruch z rozluźniania kontroli na usuwanie przyczyny.

`bash -n skrypty/bramka.sh` → OK. Komunikat jest w gałęzi `else`, czyli
wykonuje się wyłącznie przy trafieniu — przebieg zielony wygląda jak dotąd.

## 01:35 — sprawdzenie zmienionej gałęzi kroku [21] (i lekcja złapana NA SOBIE po raz czwarty)

Zmieniłem `bramka.sh` po ostatnim pełnym przebiegu, więc gałąź `else` kroku
„sekrety" była niesprawdzona. Pełna bramka dla samych `echo` byłaby
nieproporcjonalna, więc **wyłuskałem ten krok ze skryptu** (`sed`, nie
przepisanie ręczne — przepisany kod to już inny kod) i uruchomiłem go na
podłożonej przynęcie.

**Pierwsza próba wyszła ZIELONO przy 5 znalezionych wyciekach:**

```
WRN  leaks found: 5
    bez wycieków            ← gałąź SUKCESU przy pięciu wyciekach
```

Przyczyna nie leżała w bramce, tylko w moim harnessie: uruchomiłem go przez
`bash -c` **bez `set -o pipefail`**, a krok kończy się `| tail -3`, więc kod
wyjścia należał do `tail`. Prawdziwy skrypt ma `set -uo pipefail` w linii 60
i działa poprawnie.

**To jest ta sama lekcja, którą dwie godziny wcześniej dopisałem do
`WYTYCZNE-PRACY.md`** („kod wyjścia potoku należy do OSTATNIEGO polecenia") —
i złapała mnie po raz czwarty tej nocy, tym razem wewnątrz narzędzia, którym
sprawdzałem narzędzie. Zapisuję, bo to najlepszy dowód, że reguła nie działa
przez samo zapisanie jej w dokumencie.

Powtórzone z `set -uo pipefail`, czyli w warunkach prawdziwego skryptu:

```
=== [21] sekrety (gitleaks) — ten sam skan co w CI
WRN  leaks found: 5
    ──────────────────────────────────────────────────────────
    Trafienie w katalogu raportowym (docs/…)? Zanim dopiszesz wyjątek:
    · jeśli to ZACYTOWANY IDENTYFIKATOR (sesja, skrót, klucz) — SKRÓĆ GO.
    …
    · wyjątek w .gitleaks.toml dopisuj TYLKO dla historii, której nie da
      się już zmienić — wąsko … i z przynętą dowodzącą …
    ──────────────────────────────────────────────────────────
    ^ KROK NIEUDANY
```

Gałąź `else` renderuje się poprawnie, `zle` zlicza krok jako nieudany, przynęta
usunięta, drzewo czyste. **Czego to NIE sprawdza:** nie przebiegłem pełnej
bramki po tej zmianie — zmiana dotyczy wyłącznie treści komunikatu w gałęzi
awaryjnej, a ścieżka sukcesu jest nietknięta i była zielona w przebiegu o 01:10.
Mówię to wprost, żeby nikt nie odczytał „sprawdzone" szerzej, niż sprawdziłem.
