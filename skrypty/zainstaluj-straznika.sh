#!/usr/bin/env bash
# ===========================================================================
# INSTALACJA STRAŻNIKA W KATALOGU WSPÓLNYM REPOZYTORIUM (S-01).
#
# Znalezisko sesji TESTY (`ZLECENIE-059` §2): `core.hooksPath` był ścieżką
# WZGLĘDNĄ i rozwijał się w KAŻDYM drzewie roboczym osobno. Plik strażnika żyje
# w plikach śledzonych na `faza-1-retencja`, więc w drzewie odbitym od
# wcześniejszego commita tej ścieżki NIE MA.
#
# ⚠ GIT NIE OSTRZEGA, gdy `core.hooksPath` wskazuje nieistniejący katalog —
# po prostu nie uruchamia niczego. Zmierzone przeze mnie niezależnie na
# repozytorium jednorazowym: commit przechodzi z kodem 0, zero komunikatów,
# w obu drzewach. Obecność i NIEOBECNOŚĆ kontroli mają ten sam objaw.
#
# Katalog WSPÓLNY rozwiązuje to bez wiązania konfiguracji z maszyną — zmierzone:
# hook w `.git/hooks` odpala się zarówno w drzewie głównym, jak i w worktree.
# Wariant „ścieżka bezwzględna w core.hooksPath" też by działał, ale wpisywałby
# ścieżkę TEJ maszyny do konfiguracji repozytorium.
# ===========================================================================
set -euo pipefail

WSPOLNY="$(git rev-parse --path-format=absolute --git-common-dir)"
ZRODLO="$(git rev-parse --show-toplevel)/skrypty/git-hooks/pre-commit"
CEL="$WSPOLNY/hooks/pre-commit"

[ -f "$ZRODLO" ] || { echo "ODMOWA: brak źródła strażnika: $ZRODLO" >&2; exit 1; }

mkdir -p "$WSPOLNY/hooks"
cp "$ZRODLO" "$CEL"
chmod +x "$CEL"

# `hooksPath` MUSI zniknąć — dopóki jest ustawiony, git IGNORUJE katalog wspólny.
git config --unset-all core.hooksPath 2>/dev/null || true

# ODCZYT ZWROTNY. „Skrypt się wykonał" ≠ „plik ma treść" — reguła tego
# repozytorium, złamana u nas dwa razy. Porównujemy SUMY, nie fakt wykonania `cp`.
if [ "$(sha256sum < "$ZRODLO")" != "$(sha256sum < "$CEL")" ]; then
	echo "ODMOWA: zainstalowany strażnik RÓŻNI SIĘ od źródła." >&2
	exit 1
fi

[ -x "$CEL" ] || { echo "ODMOWA: strażnik nie jest wykonywalny." >&2; exit 1; }

echo "Strażnik zainstalowany: $CEL"
echo "Aktywny we WSZYSTKICH drzewach roboczych tego repozytorium."
