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

## Zadania dla człowieka (nie dla agenta)

| # | Zadanie | Dlaczego teraz | Stan |
|---|---|---|---|
| **Z-01** | **Wniosek o alfanumerycznego nadawcę SMS „Niepodzielni" w SMSAPI.** | Rejestracja pola nadawcy u polskich operatorów trwa **od kilku dni do kilku tygodni** i wymaga dokumentów fundacji (spec M5/6). | 🔵 **właściciel składa wniosek (2026-08-07)** — agent nie przypomina, sprawa wraca dopiero przy konfiguracji bramki SMS w F6 |
| **Z-02** | ~~Zatwierdzić dostawcę wideo~~ → **ZAMKNIĘTE: Jitsi** (D-2026-08-07-10). | — | ✅ zamknięte 2026-08-07 |
| **Z-06** | **Podać procent wizyt odbywanych online** (dane z Bookero). | Od tej jednej liczby zależy rozmiar serwera wideo i wybór self-host vs JaaS. Do czasu odpowiedzi infrastruktury wideo NIE projektujemy (D-2026-08-07-10). | ⬜ czeka na właściciela |
| **Z-03** | **Potwierdzić limit 10 wizyt niskopłatnych na starcie produkcji** (zarząd fundacji). | ROZSTRZYGNIĘTE co do wartości: 10 wizyt (D-2026-08-07-08) — F1 nie jest już zablokowana. Zostaje potwierdzenie, czy 10 wchodzi na produkcję; wartość i tak jest konfiguracją z wersjonowaniem, więc zmiana nie wymaga wdrożenia kodu. | 🟡 nie blokuje |
| **Z-04** | **Potwierdzić „przelewy miesięczne"** po rozmowie fundacji. | Stripe Connect zmienia **model danych** rozliczeń, nie samą integrację (spec M4/1). Potrzebne przed F4. | ⬜ czeka na fundację |
| **Z-05** | **Dostarczyć źródła makiety React** (61 ekranów, `DECYZJE.md` wykonawcy makiety §26). | Bez nich F7 nie ma czego podpinać. F0–F6 są od tego niezależne. | ⬜ czeka na właściciela |
