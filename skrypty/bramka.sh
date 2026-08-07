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
# BEZPIECZEŃSTWO: przebieg dostaje WŁASNY projekt compose (domyślnie
# `gabinet-bramka`), własne wolumeny i własne porty. Sprzątanie to wyłącznie
# `docker compose -p <ten projekt> down -v`. Skrypt ODMAWIA startu, gdyby ktoś
# wskazał projekt `gabinet` — to stos dewelopera i jego danych nie kasujemy.
# NIGDY nie wołamy globalnych `docker system/volume prune`.
# ===========================================================================
set -uo pipefail

# Git Bash zamienia ścieżki kontenerowe na hostowe, zanim dojdą do docker.exe.
export MSYS_NO_PATHCONV=1
export MSYS2_ARG_CONV_EXCL='*'

KORZEN="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$KORZEN"

PROJEKT_DEWELOPERA="gabinet"
PROJEKT="${GABINET_BRAMKA_PROJEKT:-gabinet-bramka}"
PORT_HTTP="${GABINET_BRAMKA_PORT_HTTP:-8099}"
PORT_PG="${GABINET_BRAMKA_PORT_POSTGRES:-55443}"
PORT_REDIS="${GABINET_BRAMKA_PORT_REDIS:-56390}"
ZOSTAW=0
TYLKO_KOD=0

while [ $# -gt 0 ]; do
	case "$1" in
		--zostaw) ZOSTAW=1; shift ;;
		--tylko-kod) TYLKO_KOD=1; ZOSTAW=1; shift ;;
		--projekt) PROJEKT="$2"; shift 2 ;;
		-h|--help) sed -n '2,17p' "$0"; exit 0 ;;
		*) echo "nieznany argument: $1" >&2; exit 2 ;;
	esac
done

if [ "$PROJEKT" = "$PROJEKT_DEWELOPERA" ]; then
	echo "ODMOWA: projekt bramki nie może nazywać się '$PROJEKT_DEWELOPERA' — to stos dewelopera." >&2
	exit 2
fi

KROK=0
NIEUDANE=0

krok()   { KROK=$((KROK + 1)); printf '\n=== [%d] %s\n' "$KROK" "$*"; }
zle()    { NIEUDANE=$((NIEUDANE + 1)); printf '    ^ KROK NIEUDANY\n'; }
# Prefiks nazw kontenerów/sieci/wolumenów. Bez niego drugi stos z tego samego
# pliku zderza się z kontenerami dewelopera po NAZWIE, mimo innego projektu.
dc() {
	GABINET_PREFIX="$PROJEKT" \
	GABINET_PORT_HTTP="$PORT_HTTP" \
	GABINET_PORT_POSTGRES="$PORT_PG" \
	GABINET_PORT_REDIS="$PORT_REDIS" \
		docker compose -p "$PROJEKT" -f "$(sciezka_hosta docker-compose.yml)" "$@"
}

# Pod Git Bash `-f /d/...` nie dochodzi do docker.exe w czytelnej postaci.
sciezka_hosta() {
	if command -v cygpath >/dev/null 2>&1; then cygpath -w "$KORZEN/$1"; else echo "$KORZEN/$1"; fi
}

# ---------------------------------------------------------------------------
# .env — bramka NIGDY nie używa `.env` dewelopera ani nie nadpisuje go.
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

	krok "start stosu (postgres, redis, app, web, horizon, scheduler)"
	dc up -d --wait --wait-timeout 240 || zle

	krok "migracje"
	dc exec -T app php artisan migrate --force || zle

	krok "sonda HTTP /up (przez nginx, port $PORT_HTTP)"
	# Przekierowanie powłoki, a NIE `curl -o /dev/null`: przy MSYS_NO_PATHCONV=1
	# Git Bash nie tłumaczy `/dev/null` w argumencie i curl kończy się kodem 23.
	if curl -fsS "http://127.0.0.1:${PORT_HTTP}/up" >/dev/null; then
		echo "    /up = 200"
	else
		zle
	fi

	krok "Horizon widzi Redis"
	# Horizon melduje się w Redisie dopiero po starcie nadzorcy głównego, a to
	# bywa kilka sekund PO tym, jak kontener zgłosi się jako zdrowy. Bez pętli
	# krok potrafi zapalić się na czerwono z powodu wyścigu, nie usterki.
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
fi

krok "format kodu (Pint)"
dc exec -T app ./vendor/bin/pint --test || zle

krok "statyka (Larastan, level max)"
dc exec -T app ./vendor/bin/phpstan analyse --memory-limit=1G --no-progress || zle

krok "testy (Pest)"
dc exec -T app ./vendor/bin/pest || zle

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
