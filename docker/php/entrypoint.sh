#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Wspólny entrypoint kontenerów `app`, `horizon` i `scheduler`.
#
# Zadania:
#   1. dociągnąć zależności, jeśli katalog `vendor/` nie przyjechał z hosta
#      (na maszynie dewelopera bind-mount przykrywa warstwę obrazu),
#   2. upewnić się, że katalogi zapisu istnieją,
#   3. poczekać na bazę — bez tego `horizon`/`scheduler` wpadają w restart-loop
#      przy pierwszym `docker compose up`.
#
# Świadomie NIE uruchamiamy tu migracji: kolejność migracji jest decyzją
# człowieka (`make migracje` / `php artisan migrate`), a trzy kontenery na tym
# samym obrazie odpaliłyby ją równolegle.
# ---------------------------------------------------------------------------
set -euo pipefail

cd /srv/gabinet/backend

if [ ! -f vendor/autoload.php ]; then
	echo "[entrypoint] brak vendor/ — uruchamiam composer install"
	composer install --no-interaction --prefer-dist --no-progress
fi

mkdir -p \
	storage/app/private \
	storage/app/public \
	storage/framework/cache/data \
	storage/framework/sessions \
	storage/framework/views \
	storage/logs \
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
