#!/usr/bin/env bash
# ===========================================================================
# Jedna procedura licząca testy — dla bramki I dla perturbacji.
#
# Po co osobny plik: dwa razy zdarzyło się, że perturbacja mierzyła co innego
# niż kontrola. Raz porównywała napis ułożony w skrypcie zamiast wyjścia pesta
# (CI zapaliło się przy 107 zielonych testach, perturbacja nie). Drugi raz
# (U-6, runda 3) parser poprawiono w `bramka.sh`, a kopię w `perturbacje.sh`
# zostawiono starą. Dopóki procedura istniała w dwóch egzemplarzach, „ta sama
# procedura" była deklaracją, nie faktem. Teraz jest jedna.
#
# ---------------------------------------------------------------------------
# W-4 z RUNDY 4 — NAPRAWA U-6 OTWORZYŁA TĘ SAMĄ AWARIĘ Z DRUGIEJ STRONY.
#
# U-6 kazało sumować WSZYSTKIE stany, bo parser gubił się na „1 failed,
# 135 passed" i zwracał zero. Zsumowałem także `skipped`, `todo`
# i `incomplete` — a te testy SIĘ NIE WYKONAŁY. Weryfikator zmierzył:
# jedno `beforeEach` rzucające wyjątkiem pominięcia dawało
# „Tests: 151 skipped (0 assertions)" i `BRAMKA OK — 0 nieudanych`.
# Zero wykonanych testów, zero asercji, bramka zielona — czyli DOKŁADNIE
# awaria, przed którą ten krok deklaruje ochronę.
#
# Dlatego rozdzielamy dwa różne pytania:
#   `policz_testy`   — ile testów NAPRAWDĘ SIĘ WYKONAŁO (passed + failed).
#                      Pominięty test nie jest wykonanym testem.
#   `policz_asercje` — ile asercji sprawdzono. Niezależny sygnał tej samej
#                      rzeczy: suita bez asercji niczego nie dowiodła,
#                      choćby liczba testów wyglądała dobrze.
#
# Dwa sygnały zamiast jednego, bo każdy z nich da się osobno oszukać.
# ===========================================================================

# Wiersz podsumowania pesta, oczyszczony z sekwencji ANSI. Pest koloruje wynik
# nawet przy NO_COLOR, a `[32;1m` zawiera cyfry — bez czyszczenia parser
# wyłuskuje „39" albo zero.
_wiersz_testow() {
	printf '%s' "$1" \
		| sed -e 's/\x1b\[[0-9;]*[A-Za-z]//g' -e 's/\[[0-9;]*[A-Za-z]//g' \
		| sed -n 's/.*Tests:  *//p' | tail -1
}

# Liczba testów, które SIĘ WYKONAŁY. `skipped`, `todo` i `incomplete` NIE
# wchodzą — nie wykonały się, więc nie dowodzą niczego.
policz_testy() {
	_wiersz_testow "$1" \
		| grep -oE '[0-9]+ (passed|failed|risky|warned)' \
		| grep -oE '^[0-9]+' \
		| awk '{ suma += $1 } END { print suma + 0 }'
}

# Liczba testów POMINIĘTYCH — do raportu, żeby nagły skok był widoczny.
policz_pominiete() {
	_wiersz_testow "$1" \
		| grep -oE '[0-9]+ (skipped|todo|incomplete)' \
		| grep -oE '^[0-9]+' \
		| awk '{ suma += $1 } END { print suma + 0 }'
}

# Liczba sprawdzonych asercji — drugi, niezależny sygnał.
policz_asercje() {
	printf '%s' "$1" \
		| sed -e 's/\x1b\[[0-9;]*[A-Za-z]//g' -e 's/\[[0-9;]*[A-Za-z]//g' \
		| grep -oE '\(([0-9]+) assertions?\)' | tail -1 \
		| grep -oE '[0-9]+' \
		| awk '{ n = $1 } END { print n + 0 }'
}
