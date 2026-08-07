# Rejestr decyzji — Gabinet

Zasada (WYTYCZNE-PRACY.md §6): **każda decyzja projektowa z datą i uzasadnieniem.
Podjętych nie relitygujemy.** Nowa wiedza = nowy wpis z odwołaniem do starego, nie
edycja starego wpisu. Sprostowanie błędu też jest nowym wpisem.

Format: `D-RRRR-MM-DD-NN` · decyzja · dlaczego · co z tego wynika · gdzie się to
odbija w kodzie.

---

## Decyzje odziedziczone (przed startem prac)

Podjęte 07.08.2026 przez właściciela w trakcie analizy architektonicznej.
Źródło i pełne uzasadnienia: [`docs/specyfikacja/00-analiza-architekta-i-decyzje.md`](specyfikacja/00-analiza-architekta-i-decyzje.md).
Skrót obowiązujący na co dzień: `CLAUDE.md`. **Tych wpisów nie otwieramy ponownie.**

| Decyzja | Skrót |
|---|---|
| Gabinet = **osobna aplikacja** i **źródło prawdy** o specjalistach; WordPress tylko czyta po API | wariant B w wersji mocnej |
| Technologia: **Laravel** (PHP) | spójność ekosystemu, gotowe klocki (SSO `ref-laravel`, kolejki, harmonogram) |
| Logowanie **wyłącznie przez Konta Niepodzielni** — personel i pacjenci; guest checkout bez konta | żadnych własnych haseł |
| **Dwa konta Stripe**: fundacyjne i komercyjne, flaga per usługa | osobne webhooki i rekoncyliacja |
| **Zamrażanie kwoty i reguły anulacji w chwili zakupu** (pełny zrzut, nie referencja) | zmiana cennika nie działa wstecz |
| Wideo: **pokoje generowane per wizyta**; dostawca do wyboru | patrz D-2026-08-07-06 |
| SMS: **SMSAPI**, nadawca „Niepodzielni" | rejestracja nadawcy trwa tygodnie — patrz Z-01 |
| Rozliczenia ze specjalistami: **przelewy miesięczne** (Stripe Connect odrzucony na start) | decyzja fundacji w toku, budujemy pod przelewy |
| Cutover Bookero: **przełom miesiąca** | wariant obsługi rezerwacji w toku |
| Poza pierwszym wdrożeniem: notatki z sesji, pakiety wizyt, historia finansowa pacjenta | — |
| Stare repo `System-rezerwacji` → **archiwum**, nie wzorzec | 4 luki krytyczne, obcy stack |

---

## D-2026-08-07-01 — Układ repozytorium: `backend/` osobno, frontend dojdzie obok

**Decyzja.** Kod aplikacji Laravel mieszka w `backend/`. Makieta React wejdzie
później jako `frontend/`. W korzeniu repozytorium zostają wyłącznie: dokumenty
projektowe, `docker-compose.yml`, `docker/`, `.github/` i `.env`.

**Dlaczego.** Frontend to osobna aplikacja Vite z własnym `package.json`
i własnym cyklem budowania (61 ekranów, bramka `sprawdz-ekrany`), a nie warstwa
widoków Laravela. Wsadzenie jej w `resources/js` zmusiłoby nas do sklejenia
dwóch niezależnych procesów budowania w jeden. Ten sam podział ma repo `chat`.

**Skutek.** Wszystkie polecenia PHP uruchamiamy z `backend/` — w praktyce
przez `docker compose exec app`, bo katalog roboczy kontenera to już
`/srv/gabinet/backend`. CI nie potrzebuje więc `working-directory`: woła
`skrypty/bramka.sh` z korzenia, a ten wchodzi do kontenera.

---

## D-2026-08-07-02 — Testy jadą na PostgreSQL, nie na SQLite

**Decyzja.** Suita testowa łączy się z realnym PostgreSQL (baza `gabinet_test`),
zakładaną przy inicjalizacji wolumenu (`docker/postgres/init/`). SQLite nie
występuje nigdzie w projekcie.

**Dlaczego.** Trzy twarde reguły z `CLAUDE.md` są regułami **bazy danych**, nie
aplikacji: unikalne ograniczenie `(specjalista_id, termin)` plus blokada wiersza
(§6), dziennik decyzji z **odebranym** UPDATE/DELETE roli bazodanowej (§9),
oraz test 100 równoczesnych żądań o jeden termin. Na SQLite każdy z tych testów
albo nie da się napisać, albo przechodzi z niewłaściwego powodu — czyli daje
zieloną bramkę bez dowodu. Cena (test wymaga stojącej bazy) jest zapłacona
i tak, bo `docker compose up` stawia bazę razem z resztą.

**Skutek.** `phpunit.xml` wymusza `DB_CONNECTION=pgsql`. Test
`SzkieletTest` sprawdza sterownik połączenia — cichy powrót do SQLite zapala
bramkę.

---

## D-2026-08-07-03 — Jeden plik `.env`, w korzeniu repozytorium

**Decyzja.** Całe repozytorium ma **jeden** plik `.env` (i jeden `.env.example`),
w korzeniu. Laravel czyta go, bo `backend/bootstrap/app.php` woła
`useEnvironmentPath(dirname(__DIR__, 2))`. Kontenery montują całe repozytorium
pod `/srv/gabinet`, a nie samo `backend/`.

**Dlaczego.** Ten sam zestaw wartości (hasło do bazy, porty, nazwa bazy) jest
potrzebny aplikacji i `docker-compose.yml`. Przy dwóch plikach pierwsza zmiana
hasła rozjeżdża je po cichu, a objaw — „Postgres odrzuca połączenie" — wygląda
jak awaria Dockera i kosztuje godzinę szukania nie tam, gdzie trzeba.

**Koszt, który świadomie płacimy.** Odstępstwo od konwencji Laravela; osoba
przyzwyczajona do `backend/.env` musi to raz przeczytać. Dlatego uzasadnienie
stoi w komentarzu w `bootstrap/app.php`, a nie tylko tutaj.

---

## D-2026-08-07-04 — PHP przypięty do 8.4.24, także w rozwiązywaniu zależności

**Decyzja.** Obraz aplikacyjny stoi na `php:8.4-fpm-alpine` przypiętym digestem
(PHP 8.4.24). Ta sama wersja jest wpisana w `backend/composer.json`
w `config.platform`, razem z zadeklarowanymi `ext-pcntl` i `ext-posix`.

**Dlaczego.** Bez `config.platform` lockfile rozwiązywałby się pod wersję PHP
maszyny, która akurat uruchomiła `composer update` (u wykonawcy 8.5.5, w CI 8.4)
— i produkowałby różne zestawy paczek. `ext-pcntl`/`ext-posix` deklarujemy,
bo wymaga ich Horizon, a na Windowsie tych rozszerzeń nie ma; bez deklaracji
`composer install` na maszynie wykonawcy w ogóle nie przechodzi.

**Skutek.** Podbicie PHP to świadoma zmiana **trzech** miejsc naraz: digest
w `docker/php/Dockerfile`, `config.platform` w `composer.json` i wersja w CI.
Rozjazd między nimi jest wykrywalny — patrz test `SzkieletTest`.

---

## D-2026-08-07-05 — Larastan na poziomie `max` od pierwszego dnia

**Decyzja.** `phpstan.neon` ma `level: max` i obejmuje `app`, `config`,
`database`, `routes` oraz `tests`.

**Dlaczego.** Podniesienie poziomu statyki na projekcie, który ma już setki
plików, jest osobnym, kosztownym zadaniem, którego nikt nigdy nie planuje.
Na pustym szkielecie kosztuje zero.

**Skutek.** Nowy kod pisany od razu z pełnymi typami. Wyjątki wpisujemy do
`ignoreErrors` z komentarzem — nigdy przez obniżenie poziomu.

---

## D-2026-08-07-06 — Wideo: rekomendacja Jitsi self-host, decyzja u właściciela

**Decyzja (rekomendacja wykonawcy, do zatwierdzenia przez właściciela przed F3).**
Rekomendujemy **Jitsi Meet self-host** na osobnej maszynie, z pokojami
generowanymi per wizyta i tokenem JWT ważnym w oknie wizyty.

**Dlaczego.** Pełne uzasadnienie, koszty i ryzyka obu wariantów:
[`docs/analizy/wideo-jitsi-vs-whereby.md`](analizy/wideo-jitsi-vs-whereby.md).
W skrócie: przy 111 specjalistach i kilkunastu tysiącach wizyt rocznie abonament
Whereby jest funkcją liczby pokoi i minut, a Jitsi — stałym kosztem serwera;
przy danych o zdrowiu przeważa argument, że nagranie i metadane sesji nie
opuszczają infrastruktury fundacji (jeden podprocesor mniej w rejestrze RODO).

**Status.** **OTWARTE** — decyzja należy do właściciela. Do jej podjęcia
`WIDEO_DOSTAWCA` w `.env.example` zostaje puste, a kod nie zakłada żadnego
dostawcy.

---

## D-2026-08-07-07 — OIDC portujemy z `ref-laravel`, bez Socialite

**Decyzja.** Warstwa logowania (`backend/app/Tozsamosc/`) to port aplikacji
referencyjnej `konta/tests/ref-laravel` na narzędzia Laravela, a nie
`laravel/socialite` + `socialiteproviders/keycloak`.

**Dlaczego.** Kontrakt integracyjny wymienia **cztery** rzeczy, których Socialite
nie robi (zapis `sid`, walidacja `aud` access tokenu, endpoint back-channel
logout, RP-initiated logout) — czyli i tak trzeba dopisać całą część
rozstrzygającą o bezpieczeństwie. Zostaje wtedy biblioteka, która robi to, co
umie każdy klient HTTP, w zamian ukrywając szczegóły, nad którymi musimy mieć
kontrolę.

Rozstrzygający jest kształt wyniku: `WalidatorTokenu::sprawdz()` zwraca **mapę
kontroli**, a nie `true/false`. Bez tego test negatywny „token odrzucony"
przechodzi także wtedy, gdy walidacja jest zepsuta w całości. W kontrakcie
usunięcie jednego mappera z żywego realmu zapala 28 asercji właśnie dzięki
tej mapie. Nasze testy robią to samo: sprawdzają, że padła **dokładnie jedna,
właściwa** kontrola.

**Koszt.** ~200 linii własnego kodu (weryfikacja RS256 z JWKS, JWK→PEM w czystym
PHP) zamiast dwóch zależności. Kod jest portem czegoś, co ma za sobą działającą
bramkę w repo `konta`, a nie własnym pomysłem na kryptografię.

**Skutek.** `firebase/php-jwt` nie jest potrzebny i nie wchodzi do zależności.
Zmiana IdP albo kontraktu = zmiana w jednym katalogu.

---

## D-2026-08-07-08 — dziennik makiety źródłem prawdy o REGUŁACH; limit niskopłatnych = 10 wizyt

**Decyzja.** Do repozytorium wchodzi
[`docs/specyfikacja/05-DECYZJE-makiety.md`](specyfikacja/05-DECYZJE-makiety.md)
(dziennik decyzji wykonawcy makiety, stan 04.08.2026) wraz z
[`05a-UWAGI-ARCHITEKTA-do-DECYZJI.md`](specyfikacja/05a-UWAGI-ARCHITEKTA-do-DECYZJI.md).
Dziennik jest **źródłem prawdy o regułach biznesowych i ich uzasadnieniach**;
przy sprzeczności z CLAUDE.md wygrywa CLAUDE.md.

**Trzy fragmenty dziennika NADPISANE** (za 05a):

1. Konta pacjentów — nie „konto w tle + magic link + hasło w checkoucie",
   tylko konta w Keycloaku (rola `pacjent`, tworzenie przez Admin API,
   action-token zamiast własnego magic linku). Zachowanie widziane przez
   pacjenta zostaje identyczne jak w makiecie.
2. **Limit wizyt niskopłatnych: 10 WIZYT na pacjenta** (nie godzin).
   Wiersze mówiące „4 h na osobę" to niedoczyszczony ślad sprzed podniesienia
   limitu na wniosek fundacji — ignorujemy je. Liczymy **wizyty** („3 z 10"),
   nigdy minuty. Wartość wchodzi jako konfiguracja z wersjonowaniem
   (CLAUDE.md §14), wartość startowa: **10**.
3. Backend: rozdz. 26 mówi „PHP"; ekosystem doprecyzował — Laravel 13.

**Co z tego wynika dla bramek** (rozdz. 15 i 23 dziennika — klasy błędów
niewidocznych na ekranie): kontrola statyczna nie zastępuje uruchomienia,
a dane bez wiarygodnych proporcji nie pokazują reguły, którą ilustrują.
Obie lekcje są już wpisane w `skrypty/bramka.sh` i w komentarz seedera.

**Zmiana statusu zadania Z-03:** limit niskopłatnych **przestaje być blokerem
F1** — wartość startowa jest znana (10 wizyt). Zostaje pytanie do zarządu
wyłącznie o to, czy 10 obowiązuje na starcie produkcji.

---

## D-2026-08-07-09 — polityka gałęzi: push zawsze, merge po weryfikacji, deploy za zgodą

**Decyzja właściciela (2026-08-07).** Trzy różne progi, świadomie rozdzielone:

| Operacja | Kiedy wolno | Po co ten próg |
|---|---|---|
| **`git push`** | **zawsze** | kopia zapasowa pracy poza maszyną wykonawcy + uruchomienie CI. Kod w repozytorium prywatnym nie jest ekspozycją. |
| **merge do `main`** | po **zielonej niezależnej weryfikacji** | `main` nadal trzyma wyłącznie pracę zweryfikowaną przez sesję, która jej nie pisała (WYTYCZNE-PRACY §2) |
| **deploy / wystawienie publiczne** | **wyłącznie za wyraźną zgodą właściciela** | bez zmian — `CLAUDE.md`, „Czego NIE wolno" |

**Co to zmienia w praktyce.** Do tej pory wstrzymywałem się z pushem, więc CI
nigdy nie jechało i kryterium „CI zielone" zostawało niepotwierdzone przez całą
sesję. To był zły kompromis: bramka, której się nie uruchamia, nie jest bramką.

**Czego NIE zmienia.** Progu produkcyjnego. Push i deploy to dwie różne rzeczy
i mylenie ich było źródłem mojej wcześniejszej nadostrożności.

---

## D-2026-08-07-10 — wideo: Jitsi ZATWIERDZONY; infrastruktury nie projektujemy teraz

**Decyzja właściciela (2026-08-07), zamyka status OTWARTE z D-2026-08-07-06.**
Kierunek: **Jitsi**. Rekomendacja z [`docs/analizy/wideo-jitsi-vs-whereby.md`](analizy/wideo-jitsi-vs-whereby.md)
przyjęta.

**Świadomie ODŁOŻONE:** rozmiar serwera, wybór self-host vs JaaS i cała
infrastruktura wideo. Czekają na jedną liczbę — **procent wizyt online** — którą
dostarczy właściciel. Bez niej projektowanie infrastruktury byłoby zgadywaniem
skali, a §1 analizy mówi wprost, że od tej liczby zależy, czy rachunek jest
w tysiącach czy w dziesiątkach tysięcy złotych.

**Co robimy teraz:** nic w infrastrukturze. W F3 wchodzi wyłącznie interfejs
`DostawcaPokojuWideo` (trzy operacje), żeby wybór hostingu nie dotykał kodu
rezerwacji. `WIDEO_DOSTAWCA` w `.env.example` zostaje puste do czasu decyzji
o hostingu.

---

## D-2026-08-07-11 — domena produkcyjna: `gabinet.niepodzielni.com`

**Decyzja właściciela (2026-08-07).** Nazwa zatwierdzona. Wchodzi do zgłoszenia
klienta OIDC w repo `konta` jako `NK_GABINET_PROD_ORIGIN` oraz do adresu
back-channel logout (`https://gabinet.niepodzielni.com/oidc/backchannel-logout`).

Adres jest **wyłącznie zapisem w konfiguracji i w zgłoszeniu** — nic nie zostaje
wystawione publicznie. Ekspozycja to osobna decyzja, w F9.

---

## D-2026-08-07-12 — skan sekretów jest krokiem bramki, nie osobnym jobem CI

**Decyzja.** Gitleaks biegnie jako krok `skrypty/bramka.sh` (obraz
`zricethezav/gitleaks`, konfiguracja `.gitleaks.toml`). Osobny job CI z akcją
`gitleaks/gitleaks-action@v2` **usunięty**.

**Dlaczego — trzy powody, każdy wystarczający.**

1. **Rozjazd bramki.** Skan sekretów istniał wyłącznie w CI. Lokalna bramka mogła
   być zielona przy wpisanym sekrecie — czyli dokładnie ta sytuacja, przed którą
   ma chronić reguła „CI woła ten sam skrypt".
2. **Inne narzędzie po obu stronach.** Lokalnie sprawdzaliśmy obrazem Dockera,
   w CI akcją. Dwa różne skanery to dwa różne zestawy wyników.
3. **Licencja.** Dla repozytorium należącego do organizacji akcja wymaga płatnej
   licencji. Zmierzone na pierwszym przebiegu po pushu:
   `[Fundacja-Niepodzielni] is an organization. License key is required.`
   Sam gitleaks jest darmowy — płatny jest wyłącznie wrapper w postaci akcji.

**Skutek dla `PLAN-FAZ.md`.** Punkt F0 mówił „gitleaks z `GITLEAKS_LICENSE`
z sekretów org". Licencja **nie jest potrzebna** i nie prosimy o nią. Skan
działa i jest ostrzejszy niż był: `fetch-depth: 0` sprawia, że skanujemy całą
historię, a nie jeden commit (przy domyślnym płytkim klonie skan meldował
„1 commits scanned" i przechodził, nie zaglądając w przeszłość).

**Dowód skuteczności, nie samego uruchomienia:** wpisanie wartości do
`.env.example` zapala regułę `keycloak-client-secret` i kończy skan kodem 1.
Sprawdzone przez podstawienie przynęty i cofnięcie jej.

---

## D-2026-08-07-13 — perturbacje: kontrola bez dowodu czerwieni jest nieistniejąca

**Zasada ekosystemu przejęta od zespołu hubu (ich D-0013), przyjęta 2026-08-07:**

> „Asercja bez dowodu, że umie zaświecić na czerwono, jest traktowana
> jak nieistniejąca."

**Skąd się wzięła.** U hubu dwanaście kontroli przechodziło bez takiego dowodu
— w tym **pusta suita testów**, przez którą CI świeciło zielono przy ZERO
wykonanych testach. Wykryły to dopiero perturbacje.

**Trafiła w nas natychmiast.** Nasza bramka miała dokładnie tę samą dziurę:
`pest` bez testów kończy się kodem 0, więc `dc exec app ./vendor/bin/pest ||
zle` przechodziło przy pustej suicie. Skasowanie katalogu `tests/` dałoby
zieloną bramkę. Naprawione: bramka **liczy** wykonane testy i porównuje
z podłogą (`MINIMUM_TESTOW`, dziś 100 przy 107 testach). Obniżenie podłogi
musi być świadomą zmianą w repozytorium.

**Co wprowadzamy.** `skrypty/perturbacje.sh` — dla każdej kontroli bramki
sztucznie łamie regułę, sprawdza, że kontrola pada, i **przywraca stan**
(przez `trap`, więc także przy przerwaniu).

| Perturbacja | Co łamie | Która kontrola ma paść |
|---|---|---|
| `testy` | `>=` → `>` w granicy okna 24 h (przesunięcie o 1 sekundę) | testy graniczne 23:59/24:00/24:01 |
| `pusta_suita` | uruchomienie bez ani jednego testu | podłoga liczby testów |
| `statyka` | funkcja `int` zwracająca napis | Larastan `level: max` |
| `format` | dopisane puste linie i spacje | Pint |
| `sekrety` | wartość wpisana do `.env.example` | gitleaks **oraz** `SekretyTest` |
| `hasla` | kolumna `password` dopisana do migracji | `BrakWlasnychHaselTest` (CLAUDE.md §2) |
| `zdrowie` | zatrzymany kontener bazy | `gabinet:zdrowie` |
| `tozsamosc` | podmieniony znacznik aplikacji | kontrola tożsamości usługi |
| `puls` | skasowany wpis pulsu | `gabinet:puls --sprawdz` |
| `zamrozenie` | reguła czytana z bieżącej konfiguracji zamiast zamrożonej | test zamrażania (CLAUDE.md §4) |

**Wynik pierwszego przebiegu (2026-08-07): `PERTURBACJE OK` — 12 kontroli
udowodniło, że umie zaświecić czerwono, 0 nieudanych.**

**Kiedy uruchamiamy.** Przed zamknięciem każdej fazy i po każdej zmianie
w bramce. Świadomie NIE w każdym przebiegu CI: perturbacje mutują pliki
i restartują kontenery, więc ich miejsce jest obok bramki, nie w niej.

**Druga część lekcji: podejrzewaj ZBYT SZYBKIE zielone.** Hub złapał różnicę
51 s vs 10 min i poszedł do logów zamiast uwierzyć wynikowi. Bramka mierzy
teraz czas kroku testów i wypisuje go przy wyniku — nagły spadek jest
sygnałem, że coś się nie wykonało.

---

## D-2026-08-07-14 — autoryzuje wyłącznie rola z białej listy; direct grant nie dowodzi 2FA

**Trzy pomiary zespołu hubu na żywym realmie, przyjęte jako wiążące.**

### 1. Kompozyty rozwijają się w tokenie

Access token koordynatora niesie w `realm_access.roles` **`koordynator` ORAZ
marker `wymaga-2fa`**, obok ról wbudowanych Keycloaka (`offline_access`,
`uma_authorization`, `default-roles-niepodzielni`).

**Decyzja:** autoryzuje **wyłącznie rola z BIAŁEJ LISTY**
(`konta.role_autoryzujace` — siedem ról merytorycznych realmu). Nigdy
„wszystkie role z tokenu".

Filtr siedzi w `Bramki::roleAutoryzujace()` i wykonuje się **wewnątrz**
`pozwala()` i `dlaRol()`, a nie u wywołującego. Skutek: gdyby ktoś przez
pomyłkę wpisał marker do mapy bramek, i tak nic nie otworzy. Pilnuje tego
test, który podmienia konfigurację i sprawdza, że marker dalej nie autoryzuje.

`/auth/ja` rozdziela to jawnie w odpowiedzi: `role` (autoryzujące),
`markery` (techniczne) i `wymaga_2fa`. Konsument API nie ma jak zbudować
logiki na „ma jakąś rolę, więc ma dostęp".

**Co się zmieniło w istniejącym zachowaniu:** `/auth/ja` przestał zwracać
`offline_access` w polu `role`. Test pełnego logowania zaktualizowany.

### 2. Direct grant OMIJA 2FA

`grant_type=password` wolno używać **wyłącznie w testach na dev, do inspekcji
zawartości tokenu**. Żaden przepływ produkcyjny ani żaden test „pełnego
logowania" nie może na nim polegać.

Nasza sonda `skrypty/keycloak-sprawdz.sh` używa direct grantu — i to jest
zgodne z tą regułą, bo sprawdza wyłącznie **zawartość tokenu** (podpis, `iss`,
`aud`, role), a nie „czy logowanie działa". Test odbiorczy po rejestracji
klienta `gabinet` (BLK-01) ma być **przeglądarkowy**, a dla ról z markerem
`wymaga-2fa` — **z TOTP**.

### 3. Z Admin API nie widać obowiązku TOTP

`requiredActions` bywa puste mimo obowiązku drugiego składnika. **Nie
wnioskujemy o stanie 2FA konta z Admin API.** Ma znaczenie w F3, gdzie konta
pacjentów zakładamy właśnie przez Admin API — status 2FA czytamy z tokenu
(marker), nie z konta.

**Perturbacja (D-2026-08-07-13):** zdjęcie filtra białej listy →
`PERTURBACJE OK`, testy zapalają się na czerwono. Kontrola udowodniła,
że umie zaświecić.

---

## D-2026-08-07-15 — sprostowanie po drugiej weryfikacji: testy na wzorcach, nie na słowniku

**Druga niezależna weryfikacja (2026-08-07, kod `eadf5c5`) obaliła cztery
twierdzenia.** Wpis jest sprostowaniem, a nie relitygacją — poprzednie wpisy
zostają, bo opisują stan, w którym je pisano.

### Co zostało obalone i naprawione

**1. `BrakWlasnychHaselTest` NIE egzekwował CLAUDE.md §2.** Weryfikator podłożył
kompletny mechanizm własnych haseł — kolumny `users.haslo_hash`
i `users.token_zapamietania`, tabele `konta_lokalne(hash_hasla)` i `zetony_resetu`,
model `Personel extends Authenticatable`, trasy `/reset-hasla` i `/zaloguj-haslem`
— i **cała bramka została zielona**.

Przyczyna: test sprawdzał **listę zakazanych nazw**, czyli słownik, a nie regułę.
Wystarczyło nazwać rzeczy po polsku.

Wersja druga szuka **wzorców**: `/(hasl|password|passwd|pwd|remember_token)/i`
we WSZYSTKICH kolumnach WSZYSTKICH tabel, `/(hasl|password|reset)/i` w nazwach
tabel, `Authenticatable` i `Hash::make|bcrypt|password_hash` w KAŻDYM pliku
`app/` (po usunięciu komentarzy — inaczej zdanie „NIE dziedziczy po
Authenticatable" w komentarzu zapala test pilnujący czegoś odwrotnego), oraz
ten sam wzorzec w ścieżkach i nazwach tras. Plus dwie asercje „miałem czego
szukać": schemat ma realne tabele, katalog `app/` ma realne pliki — bez nich
testy przechodzą także przy pustej bazie.

**2. Fail-open kontroli `nonce`.** `WalidatorTokenu` POMIJAŁ kontrolę, gdy
oczekiwany `nonce` był `null`, a kontroler przekazywał `$przeplyw['nonce'] ?? null`.
Sesja bez `nonce` sprawiała, że token z **dowolnym** nonce przechodził i kończył
się stanem „zalogowany". Weryfikator to zrobił.

Naprawa dwutorowa: walidator jest **fail-closed** (brak oczekiwanego nonce =
`fail`, nie pominięcie), a kontroler odmawia przy niekompletnym przepływie
(`blad: niekompletny_przeplyw`). Kontrola bezpieczeństwa nie może MILCZEĆ,
kiedy nie ma z czym porównać.

**3. Sonda `app` nie testuje php-fpm.** `gabinet:zdrowie` uruchamia świeży
proces CLI, więc zawieszony php-fpm daje `app` = healthy przez 8 minut, podczas
gdy `web` = unhealthy. Zapisane jako znane ograniczenie — patrz „Co zostaje
otwarte".

**4. CI było czerwone na `eadf5c5`** (brak `GITLEAKS_LICENSE`). Naprawione
wcześniej i niezależnie: D-2026-08-07-12 usunęła ten job. CI zielone od `ee85c83`.

### Nowe perturbacje

Obie usterki mają teraz perturbację (D-2026-08-07-13): `hasla` odtwarza **pełny
atak weryfikatora**, `nonce` przywraca fail-open. Obie zapalają się na czerwono.
Komplet: **15 kontroli** udowodniło, że umie zaświecić.

Mutacje plików wyprowadzone do `skrypty/perturbuj.py` — dwa ciche błędy
ucieczki znaków w heredokach basha sprawiły, że perturbacja „przechodziła",
nie zmieniwszy niczego. Teraz podmiana bez trafienia **przerywa z błędem**.

### Co zostaje otwarte (świadomie, z uzasadnieniem)

| # | Ograniczenie | Dlaczego nie teraz |
|---|---|---|
| O-1 | Sonda `app` nie sprawdza, czy php-fpm obsługuje FastCGI | Stos jako całość ratuje sonda `web` (unhealthy po zawieszeniu php-fpm). Domknięcie wymaga `cgi-fcgi` w obrazie i ścieżki `ping` w php-fpm — wchodzi razem z hartowaniem obrazu w F9. |
| O-2 | Suita **zawiesza się** przy niedostępnej bazie zamiast paść | W CI oznacza timeout joba zamiast czytelnej czerwieni. Do domknięcia: `PGCONNECT_TIMEOUT` w obrazie. |
| O-3 | Skan sekretów widzi tylko treści **zacommitowane** | Tryb git jest właściwy dla CI (historia to też wyciek), ale sekret w drzewie roboczym przechodzi. Perturbacja `sekrety` skanuje `--no-git` i to łapie. |
| O-4 | Brak `composer validate`; wolumen `vendor` nie odświeża się z przebudowanego obrazu | Podbicie lockfile'a bez `down -v` jest ignorowane **bez sygnału**. README ostrzega prozą; nic tego nie egzekwuje. |
| O-5 | `bramka.sh` nie broni się przed równoległym przebiegiem na tym samym projekcie | Dwa przebiegi mielą jedną bazę `gabinet_test` i dają fałszywe czerwone. Weryfikator doświadczył tego sam. |
| O-6 | Awaria po starcie widoczna po ~300 s (`retries: 12`) | To nie jest ślepa plama `start_period` (zmierzone: kończy się na pierwszej udanej sondzie), tylko koszt liczby prób. |

O-2, O-4 i O-5 wchodzą do rozpiski F1 jako zadania bramkowe.

---

## D-2026-08-07-16 — reguła zbieżności rund weryfikacji

**Decyzja architekta (2026-08-07), żeby weryfikacja nie goniła własnego ogona.**

Poprzednia runda pokazała problem procesowy: weryfikator badał `eadf5c5`, a gdy
kończył, HEAD był już pięć commitów dalej. Bez reguły każda runda otwierałaby
fazę na nowo, bo zawsze istnieje nowsza praca.

| Zasada | Treść |
|---|---|
| **Weryfikacja dotyczy KONKRETNEGO SHA** | zapisywanego w raporcie; werdykt odnosi się do tamtego stanu, nie do „repozytorium" |
| **Faza zamknięta przy ZERZE znalezisk** | runda na danym SHA kończąca się bez znalezisk zamyka fazę |
| **Praca po zweryfikowanym SHA nie otwiera fazy ponownie** | commity F1 nie wracają do F0; obejmuje je bramka fazy bieżącej |
| **Runda kolejna: HEAD + delty** | pełna bramka na czystym klonie z HEAD, a adwersarialnie **wyłącznie zmiany od SHA rundy poprzedniej** |

**Runda 1:** `0af30ae` — 5 twierdzeń obalonych.
**Runda 2:** `eadf5c5` — 4 twierdzenia obalone (2 dotyczyły bezpieczeństwa).
**Runda 3:** SHA zapisane w zleceniu, adwersarialnie delty `eadf5c5..HEAD`.

**Rozszerzenie D-2026-08-07-13 (przyjęte przez architekta):** automat perturbacji
z **twardym błędem przy nietrafionej mutacji**. Perturbacja, która nie zmieniła
pliku, musi przerwać z błędem — nie zgłosić sukcesu. Bez tego „kontrola
udowodniła czerwień" znaczy tylko tyle, że skrypt się wykonał.

---

## D-2026-08-07-17 — O-2, O-4 i O-5 domknięte; O-1 odłożone do F9

Trzy z sześciu długów z D-2026-08-07-15 wchodzą do bramki F1 i są zrobione.

| # | Co było źle | Naprawa | Dowód |
|---|---|---|---|
| **O-2** | suita **wisiała** przy niedostępnej bazie zamiast paść (w CI: timeout joba 25 min zamiast czerwieni) | ~~`PGCONNECT_TIMEOUT=5` w obrazie~~ → **sonda TCP w `tests/Pest.php`** | ⚠ **SPROSTOWANE w D-2026-08-07-18 (U-3)**: pomiar 19 s był nieprawdziwy — zmienna nie miała mierzalnego efektu (~169 s z nią i bez niej). Po naprawie: **5 s** |
| **O-4** | podbicie `composer.lock` bez `down -v` było ignorowane **bez sygnału** (wolumen `vendor` nie odświeża się z przebudowanego obrazu) | krok bramki: `composer validate --strict` + `install --dry-run` z wymogiem „Nothing to install" | perturbacja `lockfile` zapala kontrolę |
| **O-5** | dwa równoległe przebiegi bramki mieliły jedną bazę `gabinet_test` i dawały fałszywe czerwone | zamek katalogowy per projekt, z przejmowaniem po martwym PID | perturbacja `zamek` — ⚠ **SPROSTOWANE w D-2026-08-07-18 (U-2)**: mierzyła inny plik niż bramka; kod wyjścia to teraz **3** |

**Uwaga warta zapamiętania:** pierwsza wersja zamka używała `flock` — a **`flock`
nie istnieje w Git Bash na Windows**. Zamek po cichu nie chronił niczego:
zmierzone, dwa równoległe przebiegi przeszły i **oba** skończyły się
`BRAMKA CZERWONA — 2 nieudanych`. `mkdir` jest atomowy w każdym systemie plików
i nie wymaga narzędzia spoza powłoki.

**O-1** (sonda `app` nie sprawdza, czy php-fpm obsługuje FastCGI) — odłożone
do F9, razem z hartowaniem obrazu; wymaga `cgi-fcgi` i ścieżki `ping`.
**O-3** i **O-6** zostają jako udokumentowane ograniczenia bez zadania.

---

## Zadania dla człowieka (nie dla agenta)

| # | Zadanie | Dlaczego teraz | Stan |
|---|---|---|---|
| **Z-01** | **Wniosek o alfanumerycznego nadawcę SMS „Niepodzielni" w SMSAPI.** | Rejestracja pola nadawcy u polskich operatorów trwa **od kilku dni do kilku tygodni** i wymaga dokumentów fundacji (spec M5/6). | 🔵 **właściciel składa wniosek (2026-08-07)** — agent nie przypomina, sprawa wraca dopiero przy konfiguracji bramki SMS w F6 |
| **Z-02** | ~~Zatwierdzić dostawcę wideo~~ → **ZAMKNIĘTE: Jitsi** (D-2026-08-07-10). | — | ✅ zamknięte 2026-08-07 |
| **Z-06** | **Podać procent wizyt odbywanych online** (dane z Bookero). | Od tej jednej liczby zależy rozmiar serwera wideo i wybór self-host vs JaaS. Do czasu odpowiedzi infrastruktury wideo NIE projektujemy (D-2026-08-07-10). | ⬜ czeka na właściciela |
| **Z-03** | **Potwierdzić limit 10 wizyt niskopłatnych na starcie produkcji** (zarząd fundacji). | ROZSTRZYGNIĘTE co do wartości: 10 wizyt (D-2026-08-07-08) — F1 nie jest już zablokowana. Zostaje potwierdzenie, czy 10 wchodzi na produkcję; wartość i tak jest konfiguracją z wersjonowaniem, więc zmiana nie wymaga wdrożenia kodu. | 🟡 nie blokuje |
| **Z-04** | **Potwierdzić „przelewy miesięczne"** po rozmowie fundacji. | Stripe Connect zmienia **model danych** rozliczeń, nie samą integrację (spec M4/1). Potrzebne przed F4. | ⬜ czeka na fundację |
| **Z-05** | **Dostarczyć źródła makiety React** (61 ekranów, `DECYZJE.md` wykonawcy makiety §26). | Bez nich F7 nie ma czego podpinać. F0–F6 są od tego niezależne. | ⬜ czeka na właściciela |

---

## D-2026-08-07-18 — runda 3 weryfikacji: 11 znalezisk, wszystkie naprawione

Niezależny weryfikator obalił na SHA `a660753` jedenaście twierdzeń. Poniżej
komplet, bo dwa z nich to **sprostowania wcześniejszych wpisów tego dziennika**.

| # | Co było źle | Naprawa | Dowód |
|---|---|---|---|
| **U-1** | kontrola CLAUDE.md §2 przepuszczała KOMPLETNY, działający mechanizm haseł pod nazwami spoza jakiegokolwiek słownika (`sekret_logowania`, `pin_dostepu`, `sodium_crypto_pwhash_str`) | wersja TRZECIA testu: **zadeklarowany schemat bazy, zadeklarowane trasy, zamknięta lista prymitywów** — skan całego `backend/` poza `vendor/` i `storage/` | perturbacja `hasla_v2` odtwarza atak weryfikatora co do nazwy |
| **U-2** | perturbacja zamka budowała INNĄ ścieżkę niż bramka i nie odróżniała kodu wyjścia; samo przejęcie zamka miało wyścig | ścieżkę podaje `bramka.sh --pokaz-zamek`; przejęcie objęte drugim, atomowym `mkdir`; osobny kod wyjścia **3** | perturbacja `zamek` sprawdza kod 3 **i** kierunek odwrotny |
| **U-3** | **SPROSTOWANIE D-2026-08-07-17:** teza „test pada po 19 s dzięki `PGCONNECT_TIMEOUT=5`" była **nieprawdziwa** — weryfikator zmierzył ~169 s z tą zmienną i bez niej | sonda TCP w `tests/Pest.php` z własnym limitem, niezależna od sterownika | zmierzone: **5 s** zamiast 169 s; perturbacja `sonda_bazy` mierzy CZAS, nie samą czerwień |
| **U-4** | test białej listy pisał `config(['konta.bramki.panel.koordynacji' => …])` — nazwa bramki zawiera kropkę, więc powstawał NOWY klucz zagnieżdżony, a prawdziwy wpis zostawał nietknięty; test nic nie mutował | podmiana CAŁEJ mapy + **dowód mutacji** przed asercją + kierunek odwrotny | `BramkiTest` |
| **U-5** | `trap … INT` nie kończył skryptu (bash wraca do przerwanej instrukcji); sprzątanie po perturbacji haseł nie było w trapie | osobna procedura sygnałowa z jawnym `exit 130`; `hasla-sprzataj` w trapie | `bramka.sh`, `perturbacje.sh` |
| **U-6** | licznik testów zwracał **0** przy „1 failed, 135 passed" — podłoga meldowała „suita się nie uruchomiła" przy pełnym przebiegu; zmienna środowiskowa pozwalała wyłączyć kontrolę bez śladu w repozytorium | sumowanie WSZYSTKICH stanów; podłoga jako **stała**, nie zmienna środowiskowa; jeden wspólny plik `skrypty/licz-testy.sh` dla bramki i perturbacji | perturbacja `licznik` na PRAWDZIWYM wyniku z zepsutym testem |
| **U-7** | **zwrot mógł przekroczyć wpłatę**: wpis `zwrot_procent = 500` dawał 725 zł z wpłaconych 145 zł; kwota ujemna dawała ujemny zwrot | walidacja 0..100 przy wejściu, odrzucenie kwoty ujemnej, **trzeci zamek**: zwrot nigdy większy od wpłaty | `GranicePienidzyTest` liczy KWOTY |
| **U-8** | krok „zależności zgodne z lockiem" czytał wyłącznie METADANE; brak perturbacji na gałąź `install --dry-run` | dodatkowa kontrola obecności pakietów NA DYSKU (`skrypty/zaleznosci-obecne.php`) | perturbacja `vendor` — obie gałęzie + powrót na zielone |
| **U-9** | `dodajWersje` z datą wsteczną **przepisywało historię**: odpowiedź na „co obowiązywało 15 sierpnia" zmieniała się po dopisaniu wersji | odrzucenie daty wcześniejszej niż ostatnia obowiązująca | `RejestrRegulTest` sprawdza WARTOŚĆ przed i po |
| **U-10** | macierz niepełna była przyjmowana, a `OcenaAnulacji` dobierała brakujące sytuacje **z kodu** — zamrożony zrzut przestawał być samowystarczalny (wbrew §4) | odrzucenie macierzy niepełnej; brak klucza w zamrożonym zrzucie = błąd, nie zgadywanie | `GranicePienidzyTest` |
| **U-11** | **backticki bez ucieczki w cudzysłowach `bramka.sh`** — bash wykonywał `vendor` jako polecenie przy KAŻDYM przebiegu; rozbieżności liczb w dokumentach | apostrofy zamiast cudzysłowów; liczby w `PLAN-FAZ.md` przeliczone | `bash -n` + przebieg bramki bez „command not found" |

**Wniosek do zapamiętania.** Dwa znaleziska (U-2, U-3) to kontrole, które
**wyglądały na udowodnione perturbacją**, a mierzyły nie to zjawisko. Sama
perturbacja nie wystarcza — musi mieć **dowód mutacji** (czy naruszenie
naprawdę weszło w życie) i **kierunek odwrotny** (czy kontrola nie świeci
czerwono zawsze). Oba wymogi są teraz w nagłówku `perturbacje.sh`.

---

## D-2026-08-07-19 — wzmacniacz żądań do Kont Niepodzielni (lekcja zespołu hubu)

**Reguła:** każda ścieżka „cache-miss → żądanie w górę" wymaga pytania, **kto
kontroluje wejście decydujące o tym missie**. Jeśli atakujący — to wzmacniacz.

`kid` z nagłówka tokenu to dane nadawcy żądania, jeszcze **przed** weryfikacją
podpisu. Naiwna obsługa rotacji kluczy („nie znam kid → dociągnij JWKS")
zamienia strumień podrobionych tokenów w strumień żądań do Kont Niepodzielni —
a `POST /oidc/backchannel-logout` jest publiczny i nieuwierzytelniony.

Gabinet w chwili zgłoszenia **nie miał wzmacniacza** (JWKS tylko z TTL), ale nie
obsługiwał też rotacji kluczy — a naturalną łatką na to jest dokładnie ten
wzmacniacz. Wprowadzono więc od razu wersję bezpieczną: `KontaOidc::jwksDlaKid()`
z bramką częstotliwości opartą o **atomowe `Cache::add`** (domyślnie 60 s,
`KEYCLOAK_JWKS_ODSTEP_S`).

Dowód: `WzmacniaczZadanTest` **liczy żądania HTTP** — 100 tokenów z nieznanym
`kid` daje **dokładnie jedno** pobranie JWKS; znany `kid` daje zero; po
wygaśnięciu okna wchodzi kolejne jedno (inaczej zablokowalibyśmy rotację
kluczy na zawsze). Perturbacja `wzmacniacz` usuwa bramkę i test pada.

Czwarty test stosuje **wzorzec perturbacji mechanizmów samonaprawczych**: nie
kasuje cache'u (to mierzyłoby tempo odbudowy), tylko **zatruwa treść** pod tym
samym kluczem i potwierdza, że samonaprawa się NIE uruchomiła — licznik
odświeżeń zostaje na zerze.

---

## D-2026-08-07-20 — retencja RODO działa w obie strony (lekcja zespołu helpdesku)

**Reguła:** pilnujemy „nie trzymaj za długo", ale rekord, którego ŻADNE zadanie
czyszczące nie wybierze — bo brakuje mu pola, po którym retencja filtruje —
zostaje **na zawsze**. To też naruszenie, tylko ciche: nic nie pada, nic nie
alarmuje. Druga zasada: **retencja idzie za POCHODZENIEM rekordu**, nie za jego
bieżącym stanem ani kolejką — inaczej przeniesienie albo eskalacja po cichu
przesuwa okres przechowywania.

Zadania czyszczące powstają w F2, ale kontrola **strukturalna** działa już
teraz i odwraca ciężar dowodu tak samo jak kontrola §2: `RetencjaTest` trzyma
**rejestr retencji** (tabela → kolumna pochodzenia → podstawa → sposób
usunięcia). Każda tabela w bazie musi być albo w rejestrze, albo na jawnej
liście „bez danych osobowych". Nowa tabela zapala test na czerwono, dopóki
człowiek świadomie nie dopisze jej podstawy retencji.

Kontrola pilnuje trzech rzeczy naraz: kolumna pochodzenia **istnieje** (bez niej
zadanie nie wybrałoby ani jednego rekordu), **nie jest kolumną stanu**
(`status`, `updated_at`, `zanonimizowany_at`), i każdy wpis mówi **jak** rekord
znika, nie tylko kiedy. Perturbacja `retencja` dowodzi OBU kierunków awarii:
tabela z danymi osobowymi bez wpisu → czerwone; wpis wskazujący nieistniejącą
kolumnę → czerwone.

Do domknięcia w F2: dla każdej kategorii **test, że rekord PO TERMINIE JEST
wybierany przez swoje zadanie** — nie tylko że świeży nie jest.

---

## D-2026-08-07-21 — E2E logowania przez żywy Keycloak: cztery pułapki spoza kontraktu

Lekcja zespołu hubu. Trzy pierwsze punkty są **do zastosowania w F3** (konta
pacjentów + panel personelu, testy przeglądarkowe ról z markerem `wymaga-2fa`).
Czwarty obowiązuje **od zaraz** — to nowy gatunek błędu bramki.

1. **Akcja wymagana przychodzi PRZEKIEROWANIEM, nie w treści odpowiedzi.**
   `CONFIGURE_TOTP` i pokrewne objawiają się jako redirect na ekran
   required-action. Suita parsująca samo ciało odpowiedzi zobaczy pustkę
   i zgłosi „błąd logowania" przy **poprawnym haśle** — czyli wskaże złą
   przyczynę. Śledzimy przekierowania, nie ciało.
2. **`totpSecret` z Keycloaka jest SUROWY** (20 znaków, małe litery), **nie
   Base32**. Potraktowany jak Base32 daje kod odrzucany bez czytelnej
   przyczyny — objaw wygląda jak „złe hasło".
3. **Formularz konfiguracji TOTP musi ODESŁAĆ `totpSecret`** — Keycloak dopiero
   po nim wiąże kod z ziarnem.
   Wzorzec do przepisania: `konta/tests/theme/lib/totp.js`.
4. **META-LEKCJA — kontrola, która zmienia stan, psuje go swojemu następnemu
   przebiegowi.** Kontrola E2E konfigurująca TOTP zostawia gotowe
   poświadczenie; drugi przebieg zastaje **inny ekran** i mierzy co innego niż
   pierwszy. Wymóg: bramka **zdejmuje poświadczenie OTP przed każdym
   przepływem**, a wynik uznajemy dopiero po **≥3 przebiegach z rzędu**.

**Zastosowanie u nas, natychmiast.** Punkt 4 to czwarta reguła perturbacji,
obok trzech dotychczasowych (dowód mutacji · odporność na samonaprawę · dowód
w obie strony). Brzmi: **perturbacja musi zostawiać repozytorium i stos
dokładnie w stanie zastanym**, a zestaw uznajemy za sprawny dopiero, gdy
**trzy przebiegi z rzędu dają identyczny wynik**. Nasze perturbacje modyfikują
pliki i wolumen `vendor` — to dokładnie ten gatunek ryzyka. Zmierzone:
`skrypty/perturbacje.sh` trzy razy pod rząd, ten sam wynik, `git status`
czysty po każdym przebiegu.
