# Gabinet — system rezerwacji Fundacji Niepodzielni

Backend rezerwacji wizyt psychologicznych. **Źródło prawdy** o specjalistach,
terminach, rezerwacjach, rozliczeniach i wydarzeniach; WordPress
(niepodzielni.com) tylko wyświetla te dane po API. Zastępuje SaaS Bookero.

Kontekst obowiązujący każdą sesję pracy: [`CLAUDE.md`](CLAUDE.md) ·
zasady współpracy: [`WYTYCZNE-PRACY.md`](WYTYCZNE-PRACY.md) ·
plan i stan prac: [`PLAN-FAZ.md`](PLAN-FAZ.md) ·
decyzje: [`docs/DECYZJE.md`](docs/DECYZJE.md) ·
blokery: [`docs/BLOKERY.md`](docs/BLOKERY.md).

---

## Uruchomienie (dev)

Wymagania: Docker z `docker compose` v2+. **Nie potrzebujesz PHP na hoście** —
wszystko dzieje się w kontenerach.

```bash
cp .env.example .env
# wpisz DB_PASSWORD (np. `openssl rand -base64 24`)

docker compose up -d --wait
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate

curl -fsS http://localhost:8098/up        # 200
curl -fsS http://localhost:8098/api/wersja
```

Porty na hoście (wyłącznie `127.0.0.1`, konfigurowalne w `.env`):

| Usługa | Port | Zmienna |
|---|---|---|
| nginx (aplikacja) | 8098 | `GABINET_PORT_HTTP` |
| PostgreSQL | 55442 | `GABINET_PORT_POSTGRES` |
| Redis | 56389 | `GABINET_PORT_REDIS` |

Domyślne wartości są dobrane tak, żeby nie kolidować z innymi projektami
fundacji stojącymi na tej samej maszynie (`niepodzielni-konta`, `helpdesk`).

### Jeden plik `.env` w korzeniu

Czyta go i `docker-compose.yml`, i Laravel — `backend/bootstrap/app.php` wskazuje
katalog nadrzędny. Uzasadnienie: [`docs/DECYZJE.md`](docs/DECYZJE.md),
D-2026-08-07-03. **W `.env.example` nie ma ani jednej wartości sekretu** i pilnuje
tego test (`backend/tests/Feature/SekretyTest.php`) oraz gitleaks w CI.

## Bramka

```bash
./skrypty/bramka.sh            # pełny przebieg na czystym, osobnym stosie
./skrypty/bramka.sh --zostaw   # nie sprzątaj po sobie (debugowanie)
./skrypty/bramka.sh --tylko-kod  # sam Pint/Larastan/Pest, stos już stoi
```

Kończy się `BRAMKA OK` albo `BRAMKA CZERWONA — N nieudanych`. CI
(`.github/workflows/ci.yml`) woła **dokładnie ten skrypt**, więc przebieg
lokalny i zdalny nie mogą się rozjechać. Bramka stawia własny stos: własny
projekt compose **i własny prefiks nazw** (`GABINET_PREFIX`), czyli osobne
kontenery, osobną sieć i osobne wolumeny.

Prefiks sieci nie jest kosmetyką: przy nazwie na sztywno oba stosy wpinały się
w jedną sieć i alias `postgres` rozwiązywał się losowo na jeden z dwóch
serwerów — bramka trafiała w bazę dewelopera w ~połowie przebiegów. Znalazła to
niezależna weryfikacja, nie autor.

### Czego pilnują kontrole bramki

| Zasada | Jak jest realizowana |
|---|---|
| Kontrola pyta o **stan**, nie o deklarację | `gabinet:zdrowie` wykonuje zapytanie do bazy, PING do Redisa i zapis do cache; nie czyta `.env` |
| **HTTP 200 to nie tożsamość** | bramka sprawdza znacznik `gabinet-api-v1` pod `/api/wersja`, nie sam kod odpowiedzi |
| Harmonogram **wykonał zadanie**, nie „proces istnieje" | `gabinet:puls --sprawdz` czyta puls zapisywany co minutę przez samą pętlę harmonogramu |
| Nic nie wystawione publicznie | dwutorowo: `docker inspect` (każde przypisanie na `127.0.0.1`) **oraz** aktywna próba połączenia z adresu spoza loopbacku |
| Bramka działa na **czystym klonie** | instalacja zależności jest jawnym krokiem skryptu, a nie efektem ubocznym startu kontenerów |
| `docker compose port` | **nie używany** — przy wielu przypisaniach zwraca losowe jedno |

Pojedyncze narzędzia:

```bash
docker compose exec app ./vendor/bin/pint --test        # format
docker compose exec app ./vendor/bin/phpstan analyse    # statyka (level max)
docker compose exec app ./vendor/bin/pest               # testy
```

## Logowanie (Konta Niepodzielni)

Personel i pacjenci logują się **wyłącznie** przez Konta Niepodzielni (Keycloak).
W tym systemie nie ma własnych haseł ani własnego ekranu logowania
(`CLAUDE.md` §2). Kontrakt: repo `konta`, `docs/INTEGRACJA-KONTRAKT.md`.

Sprawdzenie integracji na żywym IdP:

```bash
# 1. postaw stos Kont
cd ../niepodzielni-konta/infra && docker compose up -d && ./smoke.sh
cd ../realm && ./import.sh && ./import-fixtures.sh

# 2. podłącz Gabinet do sieci IdP i uruchom sondę
cd ../../gabinet
docker compose -f docker-compose.yml -f docker-compose.konta.yml up -d
./skrypty/keycloak-sprawdz.sh
```

> **Stan na dziś:** pełny przepływ logowania jest zablokowany — klient `gabinet`
> nie istnieje jeszcze w realmie. To zamierzony stan tamtego repozytorium, nie
> usterka. Szczegóły, dowody i plan powrotu: [`docs/BLOKERY.md`](docs/BLOKERY.md)
> (BLK-01); gotowe zgłoszenie:
> [`docs/zgloszenia/klient-gabinet-w-realmie.md`](docs/zgloszenia/klient-gabinet-w-realmie.md).

## Układ repozytorium

```
backend/     aplikacja Laravel 13 (PHP 8.4)
docker/      obraz aplikacyjny, nginx, skrypty inicjujące bazę
skrypty/     bramka i sondy (wołane też przez CI)
docs/        decyzje, blokery, analizy, specyfikacja nadrzędna
frontend/    (dojdzie w F7 — makieta React, 61 ekranów)
```

## Czego tu świadomie nie ma

Wdrożenia produkcyjnego, publicznej ekspozycji i danych osobowych. Produkcja
wchodzi dopiero w F9 i **wyłącznie za wyraźną zgodą właściciela**
(`CLAUDE.md`, „Czego NIE wolno").
