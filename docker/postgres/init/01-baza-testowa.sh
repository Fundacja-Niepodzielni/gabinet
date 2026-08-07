#!/bin/sh
# ---------------------------------------------------------------------------
# Obraz postgresa tworzy jedną bazę (POSTGRES_DB). Testy jadą na PostgreSQL
# (phpunit.xml, uzasadnienie tam), więc potrzebna jest druga — osobna, żeby
# `RefreshDatabase` nie kasował danych deweloperskich przy każdym uruchomieniu.
#
# Skrypt odpala się WYŁĄCZNIE przy inicjalizacji pustego wolumenu.
# ---------------------------------------------------------------------------
set -eu

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
	CREATE DATABASE gabinet_test OWNER "$POSTGRES_USER";
EOSQL

echo "[init] baza gabinet_test utworzona"
