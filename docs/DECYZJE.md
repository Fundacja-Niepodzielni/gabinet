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

**Skutek.** Wszystkie polecenia PHP uruchamiamy z `backend/`; CI ma
`working-directory: backend` dla kroków PHP. `docker compose` uruchamiamy
z korzenia.

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

## Zadania dla człowieka (nie dla agenta)

| # | Zadanie | Dlaczego teraz | Stan |
|---|---|---|---|
| **Z-01** | **Złożyć wniosek o alfanumerycznego nadawcę SMS „Niepodzielni" w SMSAPI.** | Rejestracja pola nadawcy u polskich operatorów trwa **od kilku dni do kilku tygodni** i wymaga dokumentów fundacji (spec M5/6). Wniosek złożony przy pierwszym SMS-ie (F6) opóźni całą fazę. Do czasu rejestracji wiadomości wychodzą z losowego numeru — czego reguła prywatności z „Jak działa system" (s. 59) nie dopuszcza. | ⬜ do zrobienia |
| **Z-02** | **Zatwierdzić dostawcę wideo** (D-2026-08-07-06). | Bez tej decyzji nie da się rzetelnie zacząć zadania „link do spotkania" (spec M5/15). Potrzebne przed F3. | ⬜ czeka na właściciela |
| **Z-03** | **Wskazać wartość startową limitu wizyt niskopłatnych** (zarząd fundacji). | Steruje budżetem dopłat; spec ma sprzeczność 10 vs 4. Potrzebne przed F1 (model danych i konfiguracja reguł). | ⬜ czeka na zarząd |
| **Z-04** | **Potwierdzić „przelewy miesięczne"** po rozmowie fundacji. | Stripe Connect zmienia **model danych** rozliczeń, nie samą integrację (spec M4/1). Potrzebne przed F4. | ⬜ czeka na fundację |
| **Z-05** | **Dostarczyć źródła makiety React** (61 ekranów, `DECYZJE.md` wykonawcy makiety §26). | Bez nich F7 nie ma czego podpinać. F0–F6 są od tego niezależne. | ⬜ czeka na właściciela |
