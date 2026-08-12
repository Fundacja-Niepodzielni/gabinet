#!/usr/bin/env bash
# ===========================================================================
# ZNACZNIK TRWAJĄCEGO PRZEBIEGU POMIAROWEGO — jedno źródło dla wszystkich.
#
# Po co istnieje: `git commit` wykonany w trakcie perturbacji utrwala stan
# SPERTURBOWANY. U zespołu helpdesku tak trafił do repozytorium heap 1 GB;
# u nas 09.08 — ŻYWA PERTURBACJA REGUŁY 24 H (N-10), przez `git add -A`
# w trakcie biegnącego zestawu. Reguła „nie commituj w trakcie przebiegu"
# jest zapisana w dwóch miejscach i **nie uchroniła nikogo**, bo egzekwuje ją
# WYŁĄCZNIE pamięć wykonawcy.
#
# 12.08 ta sama klasa złamała się SZEŚĆ RAZY w jednej sesji. Architekt
# rozstrzygnął (`ODPOWIEDZ-048`, R-B): strażnik jest WYMOGIEM przed rundą 7,
# nie długiem.
#
# Dlaczego katalog, nie plik: `mkdir` jest ATOMOWY w każdym systemie plików
# i nie wymaga narzędzia spoza powłoki. `flock` NIE ISTNIEJE w Git Bash
# na Windows — pierwsza wersja zamka bramki używała go i po cichu nie chroniła
# niczego (zmierzone: dwa równoległe przebiegi przeszły).
#
# Dlaczego w KORZENIU REPOZYTORIUM, a nie w `/tmp`: hook gita i skrypty
# pomiarowe muszą widzieć TEN SAM znacznik. `/tmp` rozwiązuje się różnie
# w Git Bashu, w kontenerze i w Pythonie — to jest udokumentowana u nas klasa
# błędu (`MSYS_NO_PATHCONV`, dwa razy jednego dnia). Ścieżka w repozytorium
# jest jednoznaczna dla każdego z nich.
# ===========================================================================

znacznik_katalog() {
	printf '%s\n' "$(git rev-parse --show-toplevel 2>/dev/null)/.przebieg-pomiarowy"
}

# Zakłada znacznik. NIE jest zamkiem — nie odmawia przy zajętości, bo dwa
# różne przebiegi (bramka i perturbacje) mogą chcieć go założyć, a ich
# wzajemne wykluczanie to osobny mechanizm (zamek per projekt w `bramka.sh`).
znacznik_zaloz() {
	local katalog
	katalog="$(znacznik_katalog)"

	mkdir -p "$katalog" 2>/dev/null || return 0

	printf '%s\n' "$$" > "$katalog/pid"
	printf '%s\n' "${1:-przebieg pomiarowy}" > "$katalog/co"
	date '+%Y-%m-%d %H:%M:%S' > "$katalog/od"
}

# Zdejmuje znacznik WYŁĄCZNIE wtedy, gdy należy do tego procesu.
#
# Bez sprawdzenia PID-u przebieg kończący się jako pierwszy zdejmowałby
# znacznik drugiemu, wciąż biegnącemu — czyli strażnik przestawałby chronić
# dokładnie w chwili, gdy jest najbardziej potrzebny.
znacznik_zdejmij() {
	local katalog
	katalog="$(znacznik_katalog)"

	[ -d "$katalog" ] || return 0

	local wlasciciel
	wlasciciel="$(cat "$katalog/pid" 2>/dev/null || printf '')"

	if [ "$wlasciciel" = "$$" ]; then
		rm -rf "$katalog"
	fi
}
