# ---------------------------------------------------------------------------
# Gabinet — obraz aplikacyjny (PHP 8.4 FPM, Alpine).
#
# Ten sam obraz obsługuje trzy role: `app` (php-fpm za nginxem), `horizon`
# (worker kolejek) i `scheduler` (`schedule:work`). Różnica jest wyłącznie
# w poleceniu — dzięki temu worker NIGDY nie ma innej wersji kodu ani innych
# rozszerzeń niż serwer HTTP.
#
# Wersje przypięte digestem (zasada z WYTYCZNE-PRACY.md: bez cichych podbić).
# PHP 8.4.24 — ta sama wartość stoi w `backend/composer.json` w `config.platform`,
# żeby lockfile rozwiązywał się dokładnie pod tę wersję.
# ---------------------------------------------------------------------------
FROM composer:2.9@sha256:b09bccd91a78fe8a9ab4b33d707b862e8fe54fec17782e32683ad2a69c46867d AS composer

FROM php:8.4-fpm-alpine@sha256:5992f8b7433fe7fa96dfbf67746c86d6c41bc91e686eac38fe531c72a02e40e4

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1

# Zależności budowania są usuwane w tym samym RUN — obraz nie nosi kompilatora.
RUN set -eux; \
    apk add --no-cache \
        bash \
        git \
        icu-libs \
        libpq \
        libzip \
        postgresql-client \
        tzdata; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        linux-headers \
        postgresql-dev; \
    docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        pcntl \
        pdo_pgsql \
        sockets \
        zip; \
    pecl install redis-6.3.0; \
    docker-php-ext-enable redis opcache; \
    apk del --no-network .build-deps; \
    rm -rf /tmp/pear

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/gabinet.ini
COPY docker/php/entrypoint.sh /usr/local/bin/gabinet-entrypoint
RUN chmod +x /usr/local/bin/gabinet-entrypoint

# Montujemy CAŁE repozytorium (a nie samo `backend/`), bo `.env` leży w korzeniu
# — jeden plik dla aplikacji i dla compose (decyzja D-2026-08-07-03).
WORKDIR /srv/gabinet/backend

# ---------------------------------------------------------------------------
# Zależności wchodzą do OBRAZU, nie do bind-mountu.
#
# Powód jest zmierzony, nie estetyczny: `composer install` 136 pakietów na
# bind-mouncie Windows/WSL trwa ~10 minut. Instalacja przy starcie kontenera
# dawała wyścig, którego nie dało się zamknąć — trzy kolejne podejścia
# (jeden instalator, `compose run -T`, `depends_on: service_healthy`) kończyły
# się tak samo: bramka na czystym klonie leciała po niekompletnym `vendor/`.
#
# W obrazie instalacja idzie po warstwie kontenera i trwa sekundy. `vendor/`
# jest potem montowany jako NAZWANY WOLUMEN nad bind-mountem (docker-compose.yml),
# więc kod źródłowy dalej edytuje się na hoście, a zależności nie chodzą przez
# wolne przejście plikowe.
#
# `--no-autoloader`: mapa klas wymaga plików aplikacji, których na tym etapie
# jeszcze nie ma. Autoloader generuje entrypoint przy pierwszym starcie —
# to sekundy, nie minuty.
#
# SKUTEK DLA PRACY: zmiana `composer.json` albo `composer.lock` wymaga
# `docker compose build app` ORAZ `docker compose down -v` (żeby wolumen
# `vendor` powstał na nowo). Bez tego kontener wozi stare zależności.
# ---------------------------------------------------------------------------
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts --no-autoloader

ENTRYPOINT ["gabinet-entrypoint"]
CMD ["php-fpm"]
