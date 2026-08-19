#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Wspólny entrypoint kontenerów `app`, `horizon` i `scheduler`.
#
# Zależności są JUŻ w obrazie (patrz Dockerfile) i montują się jako nazwany
# wolumen `vendor`. Tutaj zostaje tylko wygenerowanie autoloadera — mapa klas
# wymaga plików aplikacji, których w czasie budowania obrazu jeszcze nie ma.
# To sekundy, nie minuty.
#
# Dlaczego to nie jest znowu wyścig: autoloader generuje WYŁĄCZNIE rola `app`,
# a `horizon` i `scheduler` startują dopiero po jej sondzie zdrowia
# (`depends_on: app: service_healthy` w docker-compose.yml). Pętla oczekiwania
# niżej jest drugim zamkiem na wypadek uruchomienia usług pojedynczo.
#
# Świadomie NIE uruchamiamy tu migracji: kolejność migracji jest decyzją
# człowieka, a trzy kontenery odpaliłyby ją równolegle.
# ---------------------------------------------------------------------------
set -euo pipefail

KATALOG=/srv/gabinet/backend

# --- REPOZYTORIUM ZAMONTOWANE Z HOSTA JEST „CUDZE" DLA GITA ----------------
#
# ZNALEZIONE 19.08 przez CI (A-4 — druga noga pomiaru), na `main` po scaleniu F1.
# W przebiegu chmurowym właściciel plików repozytorium różni się od użytkownika
# procesu w kontenerze, więc git odmawia:
#
#     fatal: detected dubious ownership in repository at '/srv/gabinet'
#
# Skutek był gorszy niż sama odmowa: kontrola kotwic (`JednoZrodloStanuTest`,
# R9-5) woła `git cat-file` i czytała kod ≠ 0 jako „ten commit nie istnieje" —
# czyli oskarżała TRZY PRAWDZIWE kotwice o bycie zmyślonymi. Kontrola myliła
# „nie mogę sprawdzić" ze „sprawdziłem i nie ma".
#
# Naprawa jest dwustronna i obie strony są potrzebne:
#   · TUTAJ — git przestaje odmawiać, więc kontrola może realnie pytać;
#   · w samej kontroli — pyta najpierw o HEAD (commit istniejący NA PEWNO)
#     i przy jego niepowodzeniu melduje AWARIĘ PRZYRZĄDU, a nie znalezisko.
#
# Zakres jest wąski: dokładnie ten jeden katalog, nie `*`. Zwolnienie „wszystko
# jest bezpieczne" wyłączyłoby ochronę, którą git wprowadził celowo.
git config --global --add safe.directory /srv/gabinet 2>/dev/null || true

cd "$KATALOG"

# `app` startuje php-fpm; pozostałe role wołają `php artisan ...`.
if [ "${1:-}" = "php-fpm" ]; then
	ROLA_INSTALUJACA=1
else
	ROLA_INSTALUJACA=0
fi

if [ ! -f vendor/autoload.php ]; then
	if [ "$ROLA_INSTALUJACA" = "1" ]; then
		if [ -d vendor/composer ]; then
			echo "[entrypoint] generuję autoloader (zależności są z obrazu)"
			composer dump-autoload --optimize --no-interaction
		else
			# Wolumen `vendor` bez zawartości obrazu — np. gdy ktoś podmontował
			# własny katalog. Wtedy trzeba pełnej instalacji.
			echo "[entrypoint] brak zależności w wolumenie — pełna instalacja"
			composer install --no-interaction --prefer-dist --no-progress
		fi
	else
		echo "[entrypoint] czekam, aż rola app wygeneruje autoloader"
		for _ in $(seq 1 300); do
			[ -f vendor/autoload.php ] && break
			sleep 2
		done

		if [ ! -f vendor/autoload.php ]; then
			echo "[entrypoint] BŁĄD: vendor/autoload.php nie pojawił się w 600 s." >&2
			exit 1
		fi
	fi
fi

mkdir -p \
	storage/app/private \
	storage/app/public \
	storage/framework/cache/data \
	storage/framework/sessions \
	storage/framework/views \
	storage/logs \
	storage/slad-wylogowania \
	bootstrap/cache

# Kontener działa jako root (dev). Prawa ustawiamy dla użytkownika php-fpm,
# żeby przejście na nie-roota w F9 nie wymagało zmiany tych ścieżek.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

if [ "${GABINET_WAIT_FOR_DB:-1}" = "1" ] && [ -n "${DB_HOST:-}" ]; then
	echo "[entrypoint] czekam na bazę ${DB_HOST}:${DB_PORT:-5432}"
	for _ in $(seq 1 60); do
		if pg_isready -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-gabinet}" >/dev/null 2>&1; then
			echo "[entrypoint] baza odpowiada"
			break
		fi
		sleep 1
	done
fi

exec "$@"
