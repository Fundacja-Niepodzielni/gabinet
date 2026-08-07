#!/usr/bin/env bash
# ===========================================================================
# skrypty/bramka.sh — WIĄŻĄCY runner bramki Gabinetu.
#
#   ./skrypty/bramka.sh              # pełny przebieg na czystym stosie
#   ./skrypty/bramka.sh --zostaw     # nie sprzątaj po sobie (debugowanie)
#   ./skrypty/bramka.sh --tylko-kod  # bez stawiania stosu (stos już stoi)
#
# CI (.github/workflows/ci.yml) woła DOKŁADNIE ten skrypt — dzięki temu bramka
# lokalna i bramka na GitHubie nie mogą się rozjechać. Reguła przejęta z repo
# `konta` (scripts/ci-local.sh), gdzie się sprawdziła.
#
# ---------------------------------------------------------------------------
# ZASADY KONTROLI (lekcja zespołu helpdesku + dziennik makiety, rozdz. 15 i 23):
#
#  1. Kontrola pyta o STAN, nie o deklarację. Wersję bazy czytamy z żywego
#     zapytania, nie z `.env`; port z `docker inspect`, nie z `compose config`.
#  2. HTTP 200 to NIE tożsamość. Sprawdzamy znacznik WŁASNEJ aplikacji —
#     cudza usługa na zajętym porcie potrafi dać fałszywe zielone.
#  3. `docker compose port` przy wielu przypisaniach zwraca LOSOWE jedno.
#     Nie używamy go w kontrolach.
#  4. „Nic nie wystawione publicznie" sprawdzamy dwutorowo: deklaratywnie
#     (`docker inspect`) ORAZ aktywnie — każdy port × adres spoza loopbacku.
#  5. Bramkę testujemy na CZYSTYM KLONIE. Dlatego gotowość rozstrzygają SONDY
#     kontenerów, a nie kod wyjścia polecenia instalującego.
# ---------------------------------------------------------------------------
#
# BEZPIECZEŃSTWO: przebieg dostaje WŁASNY projekt compose (domyślnie
# `gabinet-bramka`), własny PREFIKS nazw (kontenery, sieć, wolumeny) i własne
# porty. Sprzątanie to wyłącznie `docker compose -p <ten projekt> down -v`.
# Skrypt ODMAWIA startu, gdyby ktoś wskazał projekt `gabinet` — to stos
# dewelopera i jego danych nie kasujemy.
# NIGDY nie wołamy globalnych `docker system/volume prune`.
# ===========================================================================
set -uo pipefail

export MSYS_NO_PATHCONV=1
export MSYS2_ARG_CONV_EXCL='*'

KORZEN="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$KORZEN"

PROJEKT_DEWELOPERA="gabinet"
PROJEKT="${GABINET_BRAMKA_PROJEKT:-gabinet-bramka}"
PORT_HTTP="${GABINET_BRAMKA_PORT_HTTP:-8099}"
PORT_PG="${GABINET_BRAMKA_PORT_POSTGRES:-55443}"
PORT_REDIS="${GABINET_BRAMKA_PORT_REDIS:-56390}"
ZNACZNIK_APLIKACJI="gabinet-api-v1"
# Podłoga liczby testów. Rośnie razem z suitą — obniżenie MUSI być świadomą
# zmianą w repozytorium (zasada D-0013: pusta suita to zielone CI bez testów).
MINIMUM_TESTOW="${GABINET_MINIMUM_TESTOW:-100}"
ZOSTAW=0
TYLKO_KOD=0

while [ $# -gt 0 ]; do
	case "$1" in
		--zostaw) ZOSTAW=1; shift ;;
		--tylko-kod) TYLKO_KOD=1; ZOSTAW=1; shift ;;
		--projekt) PROJEKT="$2"; shift 2 ;;
		-h|--help) sed -n '2,33p' "$0"; exit 0 ;;
		*) echo "nieznany argument: $1" >&2; exit 2 ;;
	esac
done

if [ "$PROJEKT" = "$PROJEKT_DEWELOPERA" ]; then
	echo "ODMOWA: projekt bramki nie może nazywać się '$PROJEKT_DEWELOPERA' — to stos dewelopera." >&2
	exit 2
fi

# O-5: zamek na równoległy przebieg TEGO SAMEGO projektu.
#
# Dwa przebiegi mielą jedną bazę `gabinet_test` i produkują fałszywe czerwone
# („duplicate key ... migrations") — niezależny weryfikator potknął się o to sam
# i musiał unieważnić własny wynik.
#
# `mkdir`, nie `flock`: flock NIE ISTNIEJE w Git Bash na Windows. Pierwsza
# wersja zamka używała flocka i po cichu nie chroniła niczego — zmierzone:
# dwa równoległe przebiegi przeszły przez zamek i oba skończyły się
# `BRAMKA CZERWONA — 2 nieudanych`. `mkdir` jest atomowy w każdym systemie
# plików i nie wymaga żadnego narzędzia poza powłoką.
ZAMEK="${TMPDIR:-/tmp}/gabinet-bramka-${PROJEKT}.zamek"

zwolnij_zamek() {
	[ -d "$ZAMEK" ] && rm -rf "$ZAMEK"
}

if mkdir "$ZAMEK" 2>/dev/null; then
	echo "$$" > "$ZAMEK/pid"
	trap zwolnij_zamek EXIT INT TERM
else
	# Zamek po przerwanym przebiegu nie może blokować w nieskończoność:
	# jeśli proces właściciela już nie żyje, przejmujemy zamek.
	STARY_PID="$(cat "$ZAMEK/pid" 2>/dev/null || echo '')"

	if [ -n "$STARY_PID" ] && kill -0 "$STARY_PID" 2>/dev/null; then
		echo "ODMOWA: bramka dla projektu '$PROJEKT' już biegnie (PID $STARY_PID)." >&2
		echo "Równoległe przebiegi mielą tę samą bazę testową i dają fałszywe wyniki." >&2
		exit 2
	fi

	echo "UWAGA: przejmuję porzucony zamek po PID ${STARY_PID:-nieznanym}." >&2
	echo "$$" > "$ZAMEK/pid"
	trap zwolnij_zamek EXIT INT TERM
fi

KROK=0
NIEUDANE=0

krok() { KROK=$((KROK + 1)); printf '\n=== [%d] %s\n' "$KROK" "$*"; }
zle()  { NIEUDANE=$((NIEUDANE + 1)); printf '    ^ KROK NIEUDANY\n'; }

sciezka_hosta() {
	if command -v cygpath >/dev/null 2>&1; then cygpath -w "$KORZEN/$1"; else echo "$KORZEN/$1"; fi
}

# Prefiks nazw kontenerów/sieci/wolumenów. Bez niego drugi stos z tego samego
# pliku zderza się z zasobami dewelopera po NAZWIE, mimo innego projektu —
# a alias `postgres` rozwiązuje się wtedy losowo na jeden z dwóch serwerów.
dc() {
	GABINET_PREFIX="$PROJEKT" \
	GABINET_PORT_HTTP="$PORT_HTTP" \
	GABINET_PORT_POSTGRES="$PORT_PG" \
	GABINET_PORT_REDIS="$PORT_REDIS" \
		docker compose -p "$PROJEKT" -f "$(sciezka_hosta docker-compose.yml)" "$@"
}

# ---------------------------------------------------------------------------
# .env — bramka NIGDY nie nadpisuje pliku dewelopera.
# ---------------------------------------------------------------------------
przygotuj_env() {
	if [ -f .env ]; then
		echo "    .env istnieje — używam istniejącego"
		return 0
	fi

	echo "    .env nie istnieje — tworzę z .env.example (wartości efemeryczne)"
	cp .env.example .env

	local haslo klucz
	haslo="$(openssl rand -hex 16)"
	klucz="base64:$(openssl rand -base64 32)"

	# `|` jako separator: klucz base64 zawiera `/`.
	sed -i "s|^DB_PASSWORD=$|DB_PASSWORD=${haslo}|" .env
	sed -i "s|^APP_KEY=$|APP_KEY=${klucz}|" .env
}

# ---------------------------------------------------------------------------

if [ "$TYLKO_KOD" -eq 0 ]; then
	krok "sprzątanie po poprzednim przebiegu"
	dc down -v --remove-orphans >/dev/null 2>&1 || true

	krok "przygotowanie .env"
	przygotuj_env || zle

	krok "budowanie obrazu aplikacji"
	dc build app || zle

	krok "start stosu"
	# Zależności przyjeżdżają z OBRAZU i montują się jako nazwany wolumen
	# (Dockerfile + docker-compose.yml). Entrypoint generuje tylko autoloader.
	dc up -d || zle

	krok "czekam, aż KAŻDA usługa zgłosi zdrowie (stan, nie kod wyjścia \`up\`)"
	# Świadomie BEZ `--wait`. Zmierzone na czystym klonie: `up --wait` wraca
	# z błędem („container ... exited (0)"), gdy którykolwiek kontener wyjdzie
	# przejściowo w trakcie startu — mimo że `restart: unless-stopped` zaraz go
	# podnosi i stos po chwili jest w komplecie zdrowy. Kod wyjścia `up` jest
	# więc DEKLARACJĄ o jednej chwili; my pytamy o STAN aż do zbieżności.
	USLUGI="postgres redis app web horizon scheduler"
	STOS_OK=0

	for _ in $(seq 1 120); do
		NIEZDROWE=""

		for USLUGA in $USLUGI; do
			STAN="$(docker inspect "${PROJEKT}-${USLUGA}" \
				--format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' 2>/dev/null || echo brak)"
			case "$STAN" in
				healthy|running) ;;
				*) NIEZDROWE="$NIEZDROWE ${USLUGA}=${STAN}" ;;
			esac
		done

		if [ -z "$NIEZDROWE" ]; then
			STOS_OK=1
			echo "    wszystkie usługi zdrowe:$(printf ' %s' $USLUGI)"
			break
		fi

		sleep 5
	done

	[ "$STOS_OK" -eq 1 ] || { echo "    nie zbiegło się:$NIEZDROWE"; zle; }

	krok "stan: vendor/autoload.php realnie istnieje"
	if dc exec -T app test -f vendor/autoload.php; then
		echo "    vendor/autoload.php istnieje"
	else
		echo "    vendor/autoload.php NIE powstał — instalacja nie doszła do końca"
		zle
	fi

	krok "zależności zgodne z composer.lock (rozjazd wolumenu `vendor`)"
	# O-4: wolumen `vendor` NIE odświeża się z przebudowanego obrazu. Podbicie
	# lockfile'a (np. łatka bezpieczeństwa) bez `down -v` było dotąd ignorowane
	# BEZ ŻADNEGO SYGNAŁU — README ostrzegał prozą, nic tego nie egzekwowało.
	dc exec -T app composer validate --strict --no-check-publish || zle

	SUCHY="$(dc exec -T app composer install --dry-run --no-scripts 2>&1)"
	if printf '%s' "$SUCHY" | grep -q 'Nothing to install, update or remove'; then
		echo "    zainstalowane zależności zgadzają się z composer.lock"
	else
		echo "    ROZJAZD: wolumen `vendor` nie odpowiada composer.lock"
		printf '%s
' "$SUCHY" | tail -15
		echo "    naprawa: docker compose down -v && docker compose build app && docker compose up -d"
		zle
	fi

	krok "migracje"
	dc exec -T app php artisan migrate --force || zle

	krok "stan aplikacji: framework + baza + Redis + cache (nie deklaracje)"
	dc exec -T app php artisan gabinet:zdrowie || zle

	krok "tożsamość usługi pod portem $PORT_HTTP (nie sam kod 200)"
	ODPOWIEDZ="$(curl -fsS --max-time 10 "http://127.0.0.1:${PORT_HTTP}/api/wersja" 2>&1)"
	if printf '%s' "$ODPOWIEDZ" | grep -q "$ZNACZNIK_APLIKACJI"; then
		echo "    znacznik '$ZNACZNIK_APLIKACJI' obecny — to NASZA usługa"
	else
		echo "    pod portem odpowiada coś innego niż Gabinet: $ODPOWIEDZ"
		zle
	fi

	krok "Horizon widzi Redis"
	# Horizon melduje się w Redisie dopiero po starcie nadzorcy głównego,
	# a to bywa kilka sekund PO tym, jak kontener zgłosi się jako zdrowy.
	HORIZON_OK=0
	for _ in $(seq 1 20); do
		HORIZON_WYNIK="$(dc exec -T app php artisan horizon:status 2>&1)"
		if printf '%s' "$HORIZON_WYNIK" | grep -q 'is running'; then
			HORIZON_OK=1
			echo "    $(printf '%s' "$HORIZON_WYNIK" | tr -s ' \n' ' ')"
			break
		fi
		sleep 3
	done
	[ "$HORIZON_OK" -eq 1 ] || { printf '%s\n' "$HORIZON_WYNIK"; zle; }

	krok "harmonogram realnie WYKONAŁ zadanie (puls, nie sam proces)"
	PULS_OK=0
	for _ in $(seq 1 40); do
		if dc exec -T app php artisan gabinet:puls --sprawdz >/dev/null 2>&1; then
			PULS_OK=1
			echo "    $(dc exec -T app php artisan gabinet:puls --sprawdz 2>&1 | tr -d '\r')"
			break
		fi
		sleep 5
	done
	[ "$PULS_OK" -eq 1 ] || { echo "    harmonogram nie zapisał pulsu"; zle; }

	# --- „nic nie wystawione publicznie" — dwutorowo ------------------------
	krok "porty: deklaratywnie (docker inspect) — wszystko na 127.0.0.1"
	# ŚWIADOMIE bez `docker compose port`: przy wielu przypisaniach zwraca
	# losowe jedno i potrafi zameldować zieleń o niesprawdzonym porcie.
	ZLE_BINDY="$(docker inspect $(dc ps -q) \
		--format '{{.Name}} {{range $p, $b := .HostConfig.PortBindings}}{{range $b}}{{$p}}->{{.HostIp}} {{end}}{{end}}' 2>/dev/null \
		| grep -vE '(^\S+ *$)|->127\.0\.0\.1' || true)"
	if [ -z "$ZLE_BINDY" ]; then
		echo "    każde przypisanie portu wskazuje 127.0.0.1"
	else
		echo "    porty wystawione poza loopback:"; printf '%s\n' "$ZLE_BINDY"
		zle
	fi

	krok "porty: aktywnie — każdy port × adres hosta spoza loopbacku"
	ADRES_LAN="$(php -r 'echo gethostbyname(gethostname());' 2>/dev/null)"
	case "$ADRES_LAN" in
		127.*|""|*[!0-9.]*) echo "    pominięte: nie ustalono adresu spoza loopbacku (mam: '${ADRES_LAN:-brak}')" ;;
		*)
			OSIAGALNE=""
			for PORT in "$PORT_HTTP" "$PORT_PG" "$PORT_REDIS"; do
				if curl -s --max-time 3 --connect-timeout 3 -o /dev/null "http://${ADRES_LAN}:${PORT}/" 2>/dev/null; then
					OSIAGALNE="$OSIAGALNE $PORT"
				fi
			done
			if [ -z "$OSIAGALNE" ]; then
				echo "    z adresu $ADRES_LAN nie odpowiada żaden port ($PORT_HTTP, $PORT_PG, $PORT_REDIS)"
			else
				echo "    OSIĄGALNE Z SIECI:$OSIAGALNE"
				zle
			fi
			;;
	esac
fi

krok "format kodu (Pint)"
dc exec -T app ./vendor/bin/pint --test || zle

krok "statyka (Larastan, level max)"
dc exec -T app ./vendor/bin/phpstan analyse --memory-limit=1G --no-progress || zle

krok "testy (Pest)"
# Mierzymy czas: ZBYT SZYBKIE zielone jest równie podejrzane co czerwone
# (lekcja zespołu hubu — u nich 51 s zamiast 10 min oznaczało, że kontrola
# nic nie robiła).
SEKUNDY_PRZED=$(date +%s)
WYNIK_TESTOW="$(dc exec -T app ./vendor/bin/pest 2>&1)"
KOD_TESTOW=$?
CZAS_TESTOW=$(( $(date +%s) - SEKUNDY_PRZED ))
printf '%s
' "$WYNIK_TESTOW"
echo "    czas testów: ${CZAS_TESTOW}s"
[ "$KOD_TESTOW" -eq 0 ] || zle

krok "testy realnie SIĘ WYKONAŁY (podłoga: ${MINIMUM_TESTOW})"
# Zasada D-0013 (zespół hubu): „asercja bez dowodu, że umie zaświecić na
# czerwono, jest traktowana jak nieistniejąca". U nich PUSTA SUITA dawała
# zielone CI przy ZERO wykonanych testach — `pest` bez testów kończy się
# kodem 0, więc sam kod wyjścia niczego nie dowodzi.
#
# Dlatego liczymy testy i porównujemy z podłogą. Podłoga rośnie razem
# z suitą; jej obniżenie ma być świadomą zmianą w repozytorium, a nie
# efektem ubocznym skasowanego pliku.
# Usuwamy PEŁNE sekwencje ANSI, nie sam znak ESC. Pest koloruje wynik nawet
	# przy NO_COLOR, a `[32;1m` zawiera cyfry — parser bez tego czyszczenia
	# wyłuskiwał „39" albo zero zamiast liczby testów. Zmierzone: CI zapaliło
	# się na czerwono przy 107 zielonych testach, bo kontrola widziała 0.
	LICZBA_TESTOW="$(printf '%s' "$WYNIK_TESTOW" | sed -e 's/\[[0-9;]*[A-Za-z]//g' | sed -n 's/.*Tests:[^0-9]*\([0-9][0-9]*\) passed.*/\1/p' | tail -1)"
LICZBA_TESTOW="${LICZBA_TESTOW:-0}"

if [ "$LICZBA_TESTOW" -ge "$MINIMUM_TESTOW" ]; then
	echo "    wykonano $LICZBA_TESTOW testów (podłoga: $MINIMUM_TESTOW)"
else
	echo "    wykonano tylko $LICZBA_TESTOW testów przy podłodze $MINIMUM_TESTOW"
	echo "    — suita skurczyła się albo w ogóle się nie uruchomiła"
	zle
fi

krok "sekrety (gitleaks) — ten sam skan co w CI"
# Skanujemy tryb git (śledzone pliki + historia), czyli to, co realnie
# trafia do repozytorium. `.env` dewelopera jest gitignorowany i nie wchodzi.
if docker run --rm -v "$(cygpath -w "$KORZEN" 2>/dev/null || echo "$KORZEN"):/repo" -w /repo \
	zricethezav/gitleaks:latest detect --source=/repo --config=/repo/.gitleaks.toml \
	--no-banner --redact 2>&1 | tail -3; then
	echo "    bez wycieków"
else
	zle
fi

if [ "$ZOSTAW" -eq 0 ]; then
	krok "sprzątanie (down -v, wyłącznie projekt $PROJEKT)"
	dc down -v --remove-orphans >/dev/null 2>&1 || true
fi

printf '\n'
if [ "$NIEUDANE" -eq 0 ]; then
	echo "BRAMKA OK — $KROK kroków, 0 nieudanych"
	exit 0
fi

echo "BRAMKA CZERWONA — $NIEUDANE nieudanych kroków z $KROK"
exit 1
