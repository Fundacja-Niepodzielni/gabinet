#!/usr/bin/env bash
# ===========================================================================
# Czy skrypty bramki DAJĄ SIĘ URUCHOMIĆ — nie tylko sparsować.
#
# Reguła: „każda bramka musi URUCHOMIĆ, nie tylko sprawdzić składnię"
# (WYTYCZNE-PRACY, „Podejrzewaj najpierw własny przyrząd", instancja szósta).
#
# `bash -n` przechodzi, choć wołana podkomenda nie istnieje. Literówka
# w `perturbuj hasla-sprzataj` albo w nazwie perturbacji jest dla parsera
# niewidzialna, a w praktyce oznacza, że sprzątanie nigdy się nie wykonuje
# albo że perturbacja cicho nie startuje. Ten sam gatunek co drugi biały ekran
# makiety: wyrażenie regularne z bajtami NUL przeszło WSZYSTKIE kontrole
# statyczne i padło dopiero przy uruchomieniu.
#
# Dlatego tutaj wszystko jest URUCHAMIANE:
#   · `perturbuj.py` bez argumentów — wypisuje listę poleceń (kod 1),
#   · `perturbacje.sh --lista` — wykonuje skrypt do miejsca rozdzielania nazw,
#   · każda nazwa z listy musi mieć zdefiniowaną procedurę,
#   · każde `perturbuj <polecenie>` z `perturbacje.sh` musi być na liście
#     poleceń, którą `perturbuj.py` wypisuje SAM O SOBIE w czasie działania.
# ===========================================================================
set -uo pipefail

KORZEN="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$KORZEN"

BLEDY=0
zle_() { printf '    ✗ %s\n' "$*"; BLEDY=$((BLEDY + 1)); }
ok_()  { printf '    ✓ %s\n' "$*"; }

sciezka_hosta() {
	if command -v cygpath >/dev/null 2>&1; then cygpath -w "$KORZEN/$1"; else echo "$KORZEN/$1"; fi
}

# --- 1. perturbuj.py realnie startuje i wypisuje swoje polecenia -----------
UZYCIE="$(python3 "$(sciezka_hosta skrypty/perturbuj.py)" 2>&1)"

# ŚWIADOMIE wzorzec bez polskich znaków: `użycie:` przychodzi z windowsowego
# Pythona w innej stronie kodowej i grep go nie trafia. Przyrząd, nie system —
# ale przyrząd, który meldował awarię sprawnego programu.
if printf '%s' "$UZYCIE" | grep -q 'perturbuj.py \['; then
	ok_ "perturbuj.py startuje i wypisuje listę poleceń"
else
	zle_ "perturbuj.py nie wystartował albo nie wypisał listy poleceń: ${UZYCIE:0:200}"
fi

# --- 2. każde wywołanie `perturbuj X` wskazuje na istniejące polecenie -----
# To jest sedno: literówki w nazwie polecenia `bash -n` przepuszcza.
WOLANE="$(grep -oE '\bperturbuj [a-z0-9-]+' skrypty/perturbacje.sh | awk '{print $2}' | sort -u)"

for POLECENIE in $WOLANE; do
	if printf '%s' "$UZYCIE" | grep -q -- "$POLECENIE"; then
		ok_ "perturbuj.py zna polecenie '$POLECENIE'"
	else
		zle_ "perturbacje.sh woła 'perturbuj $POLECENIE', a perturbuj.py takiego polecenia NIE MA"
	fi
done

# --- 3. perturbacje.sh startuje i wypisuje swoją listę ---------------------
LISTA="$(bash skrypty/perturbacje.sh --lista 2>&1)"

if printf '%s' "$LISTA" | grep -q '^Perturbacje: '; then
	ok_ "perturbacje.sh startuje i wypisuje listę scenariuszy"
else
	zle_ "perturbacje.sh nie wypisał listy scenariuszy: ${LISTA:0:200}"
fi

# --- 4. każdy scenariusz z listy ma zdefiniowaną procedurę -----------------
NAZWY="$(printf '%s' "$LISTA" | sed 's/^Perturbacje: //')"

for NAZWA in $NAZWY; do
	# Nazwa scenariusza nie musi równać się nazwie procedury (jest tabela
	# rozdzielająca), więc pytamy o WPIS W ROZDZIELACZU i o samą procedurę.
	PROCEDURA="$(grep -oE "^\s*${NAZWA}\) p_[a-z0-9_]+" skrypty/perturbacje.sh | grep -oE 'p_[a-z0-9_]+' | head -1)"

	if [ -z "$PROCEDURA" ]; then
		zle_ "scenariusz '$NAZWA' jest na liście, ale nie ma wpisu w rozdzielaczu — nigdy się nie uruchomi"

		continue
	fi

	if grep -qE "^${PROCEDURA}\(\) \{" skrypty/perturbacje.sh; then
		ok_ "scenariusz '$NAZWA' → ${PROCEDURA}()"
	else
		zle_ "scenariusz '$NAZWA' wskazuje na ${PROCEDURA}(), której nie ma"
	fi
done

# --- 5. nieznana nazwa NIE MOŻE przejść po cichu --------------------------
KOD=0
bash skrypty/perturbacje.sh nazwa-ktorej-nigdy-nie-bylo >/dev/null 2>&1 || KOD=$?

if [ "$KOD" -eq 0 ]; then
	zle_ "perturbacje.sh przyjmuje nieznaną nazwę scenariusza bez protestu"
else
	ok_ "perturbacje.sh odrzuca nieznaną nazwę scenariusza (kod $KOD)"
fi

printf '\n'
if [ "$BLEDY" -eq 0 ]; then
	printf 'SKRYPTY URUCHAMIALNE — wszystkie wywołania rozwiązują się do istniejących celów\n'
	exit 0
fi

printf 'SKRYPTY NIESPRAWNE — %s wywołań wskazuje w próżnię\n' "$BLEDY"
exit 1
