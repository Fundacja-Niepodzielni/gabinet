#!/usr/bin/env bash
# ===========================================================================
# STRAŻNIK MUSI DZIAŁAĆ W KAŻDYM DRZEWIE ROBOCZYM — kontrola K-1 i K-2.
#
# Znalezisko sesji TESTY (S-01/S-02): strażnik był aktywny WYŁĄCZNIE w drzewie
# głównym, a `ZLECENIE-057` zapewniało wszystkie strumienie, że działa u nich.
# Zmierzone: `core.hooksPath` ze ścieżką względną celuje w worktree w pustkę,
# a git przy takim ustawieniu NIE OSTRZEGA — po prostu nic nie uruchamia.
#
# ⚠ DLACZEGO NIE PODKŁADAM NARUSZENIA W CUDZYCH DRZEWACH.
#
# Dosłowna „kontrola negatywna w każdym worktree" wymagałaby zapisu
# `.zakres-sesji` albo `.przebieg-pomiarowy` u pracującej sesji — czyli złamania
# reguły JEDNA ŚCIEŻKA, JEDEN PISZĄCY i ryzyka nadpisania cudzej deklaracji.
# Rozbicie na trzy części daje ten sam dowód, nie pisząc u nikogo:
#
#   K-1  w KAŻDYM aktywnym drzewie — WYŁĄCZNIE ODCZYTY: czy mechanizm jest
#        osiągalny i czy nie jest przesłonięty (to jest cała treść S-01);
#   K-2  na worktree JEDNORAZOWYM, tworzonym i usuwanym przez tę kontrolę:
#        pełne naruszenie → strażnik MUSI odmówić, i to z WŁAŚCIWEJ przyczyny;
#   K-3  potwierdzenie własne każdej sesji u siebie (kanał) — poza tym skryptem.
# ===========================================================================
set -uo pipefail

BLEDY=0
ok_()  { printf '    ✓ %s\n' "$*"; }
zle_() { printf '    ✗ %s\n' "$*"; BLEDY=1; }

WSPOLNY="$(git rev-parse --path-format=absolute --git-common-dir)"
HOOK="$WSPOLNY/hooks/pre-commit"
ZRODLO="$(git rev-parse --show-toplevel)/skrypty/git-hooks/pre-commit"

# --- Zainstalowany strażnik ZGADZA SIĘ ze źródłem ---------------------------
#
# Bez tego mierzylibyśmy cudzy plik: instalacja mogła być stara, ręcznie
# podmieniona albo w ogóle nie wykonana po zmianie źródła.
if [ ! -x "$HOOK" ]; then
	zle_ "brak wykonywalnego strażnika: $HOOK — uruchom skrypty/zainstaluj-straznika.sh"
elif [ "$(sha256sum < "$ZRODLO")" != "$(sha256sum < "$HOOK")" ]; then
	zle_ "zainstalowany strażnik RÓŻNI SIĘ od źródła — reinstaluj"
else
	ok_ "zainstalowany strażnik zgadza się ze źródłem (suma kontrolna)"
fi

# --- K-1: każde aktywne drzewo, tylko odczyty -------------------------------
LISTA="$(git worktree list --porcelain | awk '/^worktree /{print $2}')"

# Pustka to BŁĄD, nie zero — inaczej „zero drzew" wygląda jak „wszystko zdrowe".
if [ -z "$LISTA" ]; then
	zle_ "nie znalazłem ŻADNEGO drzewa roboczego — parser się rozjechał"
else
	ILE=0

	while IFS= read -r WT; do
		[ -n "$WT" ] || continue

		if [ ! -d "$WT" ]; then
			printf '    · pomijam nieistniejące drzewo: %s\n' "$WT"
			continue
		fi

		ILE=$((ILE + 1))

		W="$(git -C "$WT" rev-parse --path-format=absolute --git-common-dir 2>/dev/null || printf '')"

		if [ "$W/hooks/pre-commit" != "$HOOK" ]; then
			zle_ "$WT: inny katalog wspólny ($W) — strażnik stąd nieosiągalny"
		fi

		# TO JEST CAŁA TREŚĆ S-01: ustawiony `core.hooksPath` PRZESŁANIA katalog
		# wspólny, a gdy wskazuje pustkę — git milczy i nie uruchamia niczego.
		NADPISANIE="$(git -C "$WT" config --get core.hooksPath 2>/dev/null || printf '')"

		if [ -n "$NADPISANIE" ]; then
			zle_ "$WT: core.hooksPath='$NADPISANIE' PRZESŁANIA katalog wspólny (S-01)"
		fi

		# S-02: tożsamość ma być REPOZYTORIUM, nie nazwą drzewa.
		T="$(basename "$(dirname "$W")")"

		if [ "$T" != "gabinet" ]; then
			zle_ "$WT: tożsamość '$T' zamiast 'gabinet' (S-02)"
		fi
	done <<< "$LISTA"

	ok_ "K-1: sprawdzono $ILE drzew roboczych (osiągalność, brak przesłonięcia, tożsamość)"
fi

# --- K-2: pełna kontrola negatywna na worktree JEDNORAZOWYM -----------------
if [ -x "$HOOK" ]; then
	TYMCZ="$(mktemp -d)/proba-straznika"

	if git worktree add -q --detach "$TYMCZ" 2>/dev/null; then
		printf 'gabinet\nbackend/\n' > "$TYMCZ/.zakres-sesji"
		mkdir -p "$TYMCZ/docs"
		printf 'praca innej sesji\n' > "$TYMCZ/docs/cudze.md"
		git -C "$TYMCZ" add docs/cudze.md >/dev/null 2>&1

		WYJSCIE="$(cd "$TYMCZ" && bash "$HOOK" 2>&1)" && KOD=0 || KOD=$?

		git worktree remove --force "$TYMCZ" >/dev/null 2>&1

		# DWA SYGNAŁY, NIE JEDEN: sam kod wyjścia nie odróżnia „odmówił"
		# od „padł z innego powodu".
		if [ "$KOD" -eq 0 ]; then
			zle_ "K-2: strażnik PRZEPUŚCIŁ naruszenie w worktree"
		elif ! printf '%s' "$WYJSCIE" | grep -q 'SPOZA ZAKRESU'; then
			zle_ "K-2: odmowa z INNEJ przyczyny niż badana — $(printf '%s' "$WYJSCIE" | head -3)"
		else
			ok_ "K-2: strażnik odmawia w worktree, z właściwej przyczyny"
		fi
	else
		zle_ "K-2: nie udało się utworzyć worktree jednorazowego — kontrola NIE ZOSTAŁA wykonana"
	fi
fi

if [ "$BLEDY" -eq 0 ]; then
	printf '\nSTRAŻNIK DZIAŁA WE WSZYSTKICH DRZEWACH ROBOCZYCH\n'
else
	printf '\nSTRAŻNIK NIE DZIAŁA WSZĘDZIE — commit w niechronionym drzewie wciągnie cudzą pracę\n'
fi

exit "$BLEDY"
