# Weryfikacja krzyżowa — znaleziska zespołu huba (runda niezależna, 30 znalezisk)

**Kto:** wykonawca Gabinetu. Nie pisałem ich kodu ani ich znalezisk — brak konfliktu autorstwa.
Postawa adwersarialna: zadaniem jest OBALIĆ, nie przyklasnąć.
**Niczego u nich nie naprawiam i niczego nie zapisuję w ich repozytorium.**

**Para jest naturalna:** oba repozytoria to Laravel, oba są konsumentami tego samego logowania
(Konta Niepodzielni / Keycloak), obaj naprawialiśmy **ten sam defekt BLK-22**. Znam ten mechanizm
od środka, bo zamykałem go u siebie — i to jest jedyny powód, dla którego mam prawo mówić
o ich Z-A-1 stanowczo.

---

## Metoda i jej granice

**Czego NIE zrobiłem:** nie stawiałem ich stosu, nie uruchomiłem ich bramki ani ich suity.
Nie dotknąłem żadnego kontenera z prefiksem `hub`. Nie wykonałem `docker stop/rm/prune/down`.

**Co zrobiłem:** czytanie ich repozytorium **tylko do odczytu** (dokumenty nocy, `config/`,
`scripts/`, `tests/`) plus **pomiar na MOIM stosie** tam, gdzie pytanie brzmiało „czy ja mam
to samo" — bo to jedyna część, której nie da się zrobić czytaniem.

**Jak czytać moje zdania:** „zmierzone" bez dopisku = pomiar na moim stosie. „Z lektury" =
odczyt ich kodu. Nigdzie nie podaję lektury jako pomiaru.

---

## Werdykty

| # | Twierdzenie huba | Werdykt |
|---|---|---|
| **Z-A-1** | wylogowanie nie unieważnia drugiej sesji przy niezapisywalnym logu | **POTWIERDZONE — dowód rozstrzygający, nie zbieżność** |
| **Meta-twierdzenie** | „obaj weryfikatorzy **niezależnie** nazwali ten sam wzorzec", zmierzony **6×** | **ZŁA DIAGNOZA co do MOCY DOWODU** — wzorzec prawdopodobnie realny, ale **niezależności nie było**: pytanie o niego stało w zleceniu, które obaj dostali. Do tego liczba niespójna (4 vs 6) i brak mianownika |
| **L-1…L-7** (braki kontroli) | 7 pozycji | **POTWIERDZONE w sprawdzonej próbce (3 z 7)**, w tym jedna **CIĘŻSZA, niż opisali** |
| **„czy masz to samo u siebie"** | — | **NIE mam Z-A-1** (zmierzone) — ale ten sam pomiar odsłonił u mnie **inny defekt tej samej rodziny** (N-14) |

---

## 1. Z-A-1 — POTWIERDZONE. Dowód jest rozstrzygający

Pytanie brzmiało: czy ich dowód jest rozstrzygający, czy tylko zbieżny. **Jest rozstrzygający**
i mówię to jako ktoś, kto zna tę pułapkę z własnej naprawy.

Ich dyskryminator: owinęli **jedno** wywołanie `Log::info` w `sprzatnij()` w `rescue(…, report: false)`,
**bez żadnej innej zmiany**, i zmierzyli:

```
przed:  ⨯ SONDA 1 (znacznik nie powstał)   ⨯ SONDA 1b (200 na /panel po wylogowaniu)
po:     ✓ SONDA 1                          ✓ SONDA 1b        ⨯ SONDA 2 (pozostałe defekty ZOSTAJĄ czerwone)
```

**To jest poprawnie skonstruowany dyskryminator, nie korelacja**, z trzech powodów:

1. **Zmiana jest minimalna i celowana** — jedno wywołanie, nie przebudowa ścieżki.
2. **Ma kierunek odwrotny wbudowany**: SONDA 2 **zostaje czerwona**. Gdyby poprawka „naprawiała
   wszystko", byłby to sygnał, że mierzy coś szerszego niż deklaruje. Zostawienie innych defektów
   czerwonymi jest dowodem, że trafili w tę jedną przyczynę.
3. **Skutek zmierzony u KLIENTA, nie w magazynie** — `200` na `/panel` w drugiej karcie. To jest
   dokładnie ta granica, o którą sam potykałem się przy BLK-22: mierzenie stanu magazynu zamiast
   kolejnego żądania mierzy węższe zjawisko, niż się deklaruje. **Oni zmierzyli szersze.**

**Nie znalazłem sposobu, żeby to obalić.** Sprawdziłem jedną hipotezę alternatywną — czy `200`
nie bierze się z tego, że drugie żądanie w ogóle nie dotyka magazynu unieważnień (np. czyta sesję
z pamięci) — ale ich pomiar idzie osobnym żądaniem HTTP, więc ta droga odpada.

### Jedno zastrzeżenie do ZAKRESU, nie do dowodu

Warunek („plik logu należy do roota") w produkcji **łamie także inne ścieżki, które logują**.
Ich sformułowanie *„normalny stan po pierwszym dniu pracy"* jest trafne co do WYZWALACZA, ale
sugeruje, że defekt jest cichy dla operatora — a przy niezapisywalnym logu operator zobaczy
awarie również gdzie indziej. **To nie osłabia znaleziska** (użytkownik i tak zostaje zalogowany
w drugiej karcie, a jego własna karta pokazuje sukces), ale warto to nazwać, żeby nikt nie
zbudował na tym fałszywego wniosku „awaria jest całkowicie niewidoczna".

---

## 2. Meta-twierdzenie o zasięgu napraw — najmocniejsza rzecz do podważenia w całej rundzie

`PODSUMOWANIE.md` podnosi to zdanie ponad listę trzydziestu pozycji:

> **Obaj weryfikatorzy, niezależnie i w rozłącznych zakresach, nazwali ten sam wzorzec** […]
> **Zmierzone sześć razy w jednej rundzie.**

To jest twierdzenie o METODZIE, więc trzeba je mierzyć jak twierdzenie, a nie cytować jak morał.
**Trzy zastrzeżenia, każde sprawdzalne w ich własnych plikach.**

### (a) Niezależności NIE BYŁO — pytanie o wzorzec stało w zleceniu

`ZLECENIE-WERYFIKACJA.md`, sekcja „Siedem pytań nazwanych wprost", pytanie pierwsze:

```
- **(a) INSTANCJA zamiast KLASY.** Czy któraś naprawa zamyka pokazany przypadek,
  a nie rodzinę? To udokumentowany wzorzec autora (B-3).
```

**Obaj weryfikatorzy dostali to samo zlecenie.** Nie tylko poproszono ich o szukanie tego
wzorca — **powiedziano im, że wzorzec u tego autora już istnieje i jest udokumentowany.**

Zbieżność dwóch agentów, którzy dostali tę samą instrukcję i tę samą sugestię co do wyniku,
**nie jest niezależnym potwierdzeniem.** To jest ta sama klasa błędu, którą hub sam poprawnie
diagnozuje gdzie indziej: **pomiar odpowiadający na pytanie, które sam zawiera odpowiedź.**
Gdyby żaden z weryfikatorów nie znalazł ani jednej instancji, byłby to mocny wynik — znalezienie
ich po takim zleceniu jest wynikiem oczekiwanym.

**Nie twierdzę, że wzorzec jest fałszywy.** Twierdzę, że **zdanie „nazwali go niezależnie" jest
nieprawdziwe**, a to ono niesie całą moc retoryczną tego akapitu.

### (b) Liczba jest niespójna wewnątrz tej samej nocy: 4 czy 6?

W `ZNALEZISKA.md`, w komentarzu autora tuż pod cytatem weryfikatora:

> …a tu został zmierzony u mnie, **czterokrotnie**, w jednej rundzie.

W `PODSUMOWANIE.md`, dokumencie czytanym pierwszym:

> **Zmierzone sześć razy w jednej rundzie.**

Sam weryfikator B wylicza **cztery** („Wzorzec, plik, jedna wartość progu, jedno narzędzie
z pięciu"). Do sześciu brakuje dwóch, których nikt nie nazywa z osobna — najpewniej dołożono
Z-A-1 („kontroler zamiast całej ścieżki") i jeszcze jedną pozycję przy agregacji.
**Liczba urosła między plikiem znalezisk a podsumowaniem, bez nowego pomiaru.** Przy twierdzeniu
o wzorcu liczba instancji JEST dowodem, więc jej nieostrożne podbicie osłabia całość.

### (c) Brakuje mianownika — a oni sami go mają

Zlecenie wymienia **dziewięć** pozycji do zweryfikowania (K1, B-1, B-2, B-3, D-6, BLK-22, czwarty
wektor, zamek przebiegu, B-4), choć `PODSUMOWANIE` mówi o „ośmiu naprawach" — drobna
niezgodność, ale istotna, bo to jest mianownik.

Twierdzenie „wzorzec" wymaga **licznika i mianownika**. Oni podają licznik (6) w nagłówku,
a mianownik chowają w zastrzeżeniach: *„osiemnaście mutacji trafiło w mur (8 u A, 4 potwierdzone
twierdzenia u B, 6 pozycji »nie udało się obalić«)"*. **To jest osiemnaście przypadków, w których
naprawa NIE była za wąska** — i ta liczba nie trafiła do zdania o wzorcu.

**Postać falsyfikowalna, którą powinni napisać zamiast obecnej:** *„z dziewięciu napraw
poddanych rundzie N okazało się zamykać pokazany przypadek, a nie rodzinę"*. Taka liczba jest
sprawdzalna i nie rośnie przy przepisywaniu.

### (d) A czy wzorzec jest REALNY? Prawdopodobnie tak — ale nie jest ich własnością

Tu przechodzę na ich stronę, bo część zadania brzmiała „a jeśli wzorzec jest realny, sprawdź,
czy Ty go nie masz".

**Mam go.** Moja własna runda 6 z tej samej nocy dała dwie instancje tej samej rodziny:

- **R6A-3** — napisałem o swoim wąskim gardle §2, że ścieżka „brak rekordu → utwórz" jest
  **NIEWYWOŁYWALNA**. Weryfikator wytworzył tożsamość trzema drogami (dane z żądania,
  `Reflection`, `unserialize`). Naprawa zamknęła pokazany przypadek; warunek przeniósł się
  o poziom wyżej. **To jest dokładnie „zasięg tego, co pokazał weryfikator".**
- **N-3** — naprawiłem perturbacje tak, że dowód mutacji ma odczyt bazowy, i **przeoczyłem, że
  osiem innych podmian robionych surowym `sed`-em** nie ma żadnego zabezpieczenia. Zamknąłem
  klasę w jednym mechanizmie z dwóch.

**Wniosek, który jest dla nich lepszą wiadomością niż ich własny:** wzorzec najprawdopodobniej
**nie jest właściwością tego repozytorium ani tego autora** — jest właściwością *naprawiania
z raportu weryfikatora*. Raport pokazuje instancję; instancja jest tym, co ma się przestać
dziać; naprawa celuje w to, co widać. Ich zdanie *„naprawy W TYM REPOZYTORIUM"* przypisuje sobie
wadę metody jako wadę własną — co brzmi samokrytycznie, a jest **diagnozą w złym miejscu**
i prowadzi do złego lekarstwa (więcej ostrożności u autora zamiast zmiany w procedurze
zamykania znaleziska).

Ich własna rekomendacja jest natomiast **trafna i wartościowa niezależnie od powyższego**:
*„każda naprawa musi mieć perturbację sięgającą po wariant, którego weryfikator NIE pokazał"*.
To zdanie zabieram do siebie.

---

## 3. Siedem pozycji „braku kontroli" — sprawdziłem trzy, wszystkie prawdziwe, jedna cięższa

Nie sprawdzałem wszystkich siedmiu; wybrałem te, które da się rozstrzygnąć odczytem ich repo.

| pozycja | mój pomiar (odczyt ich repo) | werdykt |
|---|---|---|
| **L-4** — sesja bez kontroli flag ciasteczka | `config/session.php:180` → `'secure' => env('SESSION_SECURE_COOKIE')` — **bez wartości domyślnej**; `grep` po `tests/` za `same_site\|http_only\|secure` → **zero trafień** | **POTWIERDZONE i CIĘŻSZE, niż opisali** |
| **L-6** — brak CSP | `grep -rln "Content-Security-Policy" app/ config/ tests/` → **zero** | **POTWIERDZONE** |
| **L-7** — próg „więcej niż zero" | `scripts/bramka*.sh:588` → `[ "$LICZBA_TESTOW" -gt 0 ]` | **POTWIERDZONE** |

**Dlaczego L-4 jest cięższe, niż napisali.** Opisali brak KONTROLI. Zmierzyłem brak WARTOŚCI:
`'secure' => env('SESSION_SECURE_COOKIE')` bez drugiego argumentu daje `null`, gdy zmiennej nie
ma w środowisku — czyli **ciasteczko sesji nie dostaje flagi `Secure`**, dopóki ktoś świadomie
nie ustawi zmiennej. Przy produkcji za Cloudflare (ich N-5 mówi, że taka jest) to nie jest brak
kontroli nad poprawnym stanem, tylko **brak kontroli nad stanem niepoprawnym**. Ich `http_only`
ma domyślne `true` — więc różnica jest widoczna w tym samym pliku, dwa wiersze obok.

**Czego NIE sprawdziłem:** L-1, L-2, L-3, L-5. Nie wypowiadam się o nich w żadną stronę.

---

## 4. „Czy nie masz tego samego u siebie" — NIE mam Z-A-1, ale mam jego rodzeństwo

To jedyna część, którą trzeba było zmierzyć, a nie przeczytać. **Zmierzyłem na własnym stosie.**

**Punkt wyjścia — mam identyczny kształt ryzyka.** Mój handler `BackchannelLogoutController`
woła `SladWylogowania::wejscie()` w **linii 40**, czyli **przed** blokiem `try` (linia 42)
i długo przed `RejestrSesji::zakoncz($sid)` (linia 115). A `SladWylogowania` — dokładnie jak
u nich — **pisze do pliku**. Gdyby ten zapis rzucał, mój handler przewracałby się przed
unieważnieniem: **Z-A-1 co do joty.**

**Zmierzone — NIE rzuca:**

```
$ docker exec -u www-data gabinet-app php artisan tinker --execute='
    try { SladWylogowania::wejscie(); echo "BEZ WYJATKU"; } catch (Throwable $e) { echo "RZUCA"; }'
WARNING  file_put_contents(storage/slad-wylogowania/wejscia): Failed to open stream: Permission denied
BEZ WYJATKU
```

`Illuminate\Filesystem::put()` **ostrzega i zwraca, nie rzuca**. Mój handler idzie dalej,
`RejestrSesji::zakoncz()` się wykonuje, unieważnienie powstaje. **Defektu Z-A-1 u siebie nie mam
i mówię to z pomiaru, nie z lektury.**

### Ale ten sam pomiar odsłonił u mnie coś innego — i to nie jest drobiazg

Katalog śladu **w stanie zastanym, bez żadnej mojej perturbacji**:

```
$ docker exec gabinet-app sh -c 'ls -ld storage/slad-wylogowania'
drwxr-xr-x  root root  storage/slad-wylogowania          ← root, 755

$ docker exec gabinet-app sh -c 'ps -o user,args | grep php-fpm'
root      php-fpm: master process
www-data  php-fpm: pool www                              ← ŻĄDANIA obsługuje www-data
```

Skutek, zmierzony w obie strony:

```
jako www-data (czyli tak, jak biegnie ŻĄDANIE):   WARNING … Permission denied  → licznik NIE rośnie
jako root     (czyli tak, jak biegną TESTY):      licznik rośnie
```

**`SladWylogowania` jest w prawdziwym procesie aplikacji CICHO BEZCZYNNY.** To jest przyrząd,
który zbudowałem specjalnie po to, żeby odróżnić „handler nie wystartował" od „wystartował
i padł" — i w produkcji nie odróżnia niczego. Gorzej: licznik **daje się ODCZYTAĆ** (pliki
zapisane kiedyś przez roota), więc zwraca **liczbę pochodzącą z innego procesu i innej chwili**.
Diagnostyka zwracająca nieświeżą liczbę jest gorsza od zwracającej zero, bo wygląda jak pomiar.

**Dlaczego żaden mój test tego nie widzi:** testy biegną przez `docker compose exec`, czyli
**jako root**, a żądania obsługuje **www-data**. Kontrola jest walidowana w kontekście
użytkownika, który w produkcji nie występuje. **To jest moja własna lekcja V-2 („42 kontrole
zmierzone w jedynym środowisku, w którym wychodzą") w nowym przebraniu** — tym razem różnicą
nie jest plik środowiska, tylko UŻYTKOWNIK PROCESU.

Zapisuję to u siebie jako **N-14**, nie naprawiam tej nocy (to defekt przedmiotu, nie przyrządu
mierzącego rundę, a naprawa autorem bez rundy nie miałaby pokrycia).

**Dla huba płynie stąd konkret:** ich Z-A-1 i mój N-14 to **ta sama rodzina — „zapis w ścieżce
wylogowania może po cichu nie dojść"** — rozdzielona wyłącznie tym, czy użyta funkcja rzuca, czy
ostrzega. U nich rzuca i zabija unieważnienie; u mnie ostrzega i zabija diagnostykę. **Ich L-3
(„niezapisywalny magazyn unieważnień wyłącza wzorzec BLK-22 w ciszy") to trzeci wariant tej samej
rzeczy.** Rekomendacja, którą traktuję jako wspólną: w ścieżce wylogowania **każdy** zapis musi
mieć jawnie rozstrzygnięty los — osłonę albo asercję — a kontrola musi biec **jako ten sam
użytkownik, co proces obsługujący żądanie**.

---

## 5. Czego NIE sprawdziłem — jawnie

- **Nie uruchomiłem niczego u nich.** Wszystkie zdania o ich kodzie to odczyt. Ich pomiarów
  (99/99, `200` na `/panel`, EXIT-y) **nie odtwarzałem** — przepisywanie cudzych liczb nie jest
  weryfikacją.
- **Z 30 znalezisk dotknąłem: Z-A-1, meta-twierdzenia, L-4, L-6, L-7.** O pozostałych —
  w tym o wszystkich Z-B-* i o blokerach Z-B-5, Z-B-4, Z-B-7 — **nie wypowiadam się**.
- **Nie sprawdziłem ich raportów weryfikatorów A i B w całości** (398 + 442 wiersze); czytałem
  fragmenty przez `ZNALEZISKA.md`.
- **Nie mam dostępu do ich `main` ani CI** i nie próbowałem go uzyskać.

## 6. Zakazy — stan na koniec

| zakaz | stan |
|---|---|
| zero zapisu w cudzym repozytorium | ✔ **zero** — sprawdzone `git status` w ich drzewie po zakończeniu |

**Uwaga do powyższego, bo wygląda niepokojąco, a nie jest.** `git status` w ich drzewie pokazuje
jeden plik nieśledzony o nazwie **identycznej z moim raportem o helpdesku**
(`WERYFIKACJA-KRZYZOWA-helpdesk.md`). Sprawdziłem treścią, zanim czegokolwiek dotknąłem:
**to jest ICH plik** — nagłówek brzmi „*Kto: sesja hubu (`f1/naprawy`)*", a mój „*Kto: wykonawca
Gabinetu*". Hub weryfikował helpdesk niezależnie ode mnie i nazwaliśmy pliki tak samo.
**Nie usunąłem go i nie ruszyłem.** Zbieżność nazw jest tu przypadkiem, a nie moim śladem —
i właśnie dlatego sprawdziłem ją odczytem, zamiast założyć jedno albo drugie.
| zero `main`, zero deploy, nic na zewnątrz | ✔ |
| nie kasować cudzych kontenerów ani obrazów | ✔ żadnego `stop/rm/prune/down`; kontenery `hub-*` nietknięte |
| ich stos stawiany tylko w razie potrzeby | ✔ **nie stawiany ani razu** |
