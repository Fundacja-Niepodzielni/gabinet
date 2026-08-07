#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Wspólny entrypoint kontenerów `app`, `horizon` i `scheduler`.
#
# NAJWAŻNIEJSZA REGUŁA: zależności instaluje DOKŁADNIE JEDNA rola — `app`.
#
# Poprzednia wersja pozwalała instalować wszystkim trzem naraz. Ponieważ dzielą
# jeden bind-mount, `composer install` biegł potrójnie w tym samym katalogu,
# `restart: unless-stopped` przerywał go w połowie, a `vendor/autoload.php`
# (zapisywany na samym końcu) nigdy nie powstawał. Kolejne podejście mówiło
# „Nothing to install" i stanu już nie naprawiało. Objaw: bramka czerwona na
# czystym klonie, zielona przy drugim uruchomieniu. Znalezione przez
# niezależnego weryfikatora, nie przez autora.
#
# Świadomie NIE uruchamiamy tu migracji: kolejność migracji jest decyzją
# człowieka, a trzy kontenery odpaliłyby ją równolegle.
# ---------------------------------------------------------------------------
set -euo pipefail

KATALOG=/srv/gabinet/backend
cd "$KATALOG"

# `app` startuje php-fpm; pozostałe role wołają `php artisan ...`.
if [ "${1:-}" = "php-fpm" ]; then
	ROLA_INSTALUJACA=1
else
	ROLA_INSTALUJACA=0
fi

if [ ! -f vendor/autoload.php ]; then
	if [ "$ROLA_INSTALUJACA" = "1" ]; then
		echo "[entrypoint] brak vendor/ — instaluję zależności (rola: app)"
		composer install --no-interaction --prefer-dist --no-progress
	else
		echo "[entrypoint] czekam, aż rola `app` zainstaluje zależności"
		for _ in $(seq 1 180); do
			[ -f vendor/autoload.php ] && break
			sleep 2
		done

		if [ ! -f vendor/autoload.php ]; then
			echo "[entrypoint] BŁĄD: vendor/autoload.php nie pojawił się w 360 s." >&2
			echo "[entrypoint] Uruchom: docker compose run --rm --no-deps app composer install" >&2
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
