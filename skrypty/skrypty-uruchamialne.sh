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
#
# DOPASOWANIE DOKŁADNE, NIE PODCIĄGIEM (R6B-8). Poprzednia wersja robiła
# `grep -q -- "$POLECENIE"` na całym wierszu użycia, więc wywołanie
# `perturbuj hasla` przechodziło dzięki obecności `hasla-podloz`, a
# `perturbuj sesja` dzięki `sesja-jawna`. Kontrola literówek przepuszczała
# każdą literówkę będącą PREFIKSEM istniejącej nazwy — czyli najczęstszą.
#
# Lista poleceń pochodzi z tego, co `perturbuj.py` wypisuje SAM O SOBIE:
# `użycie: perturbuj.py [a | b | c]`. Wyłuskujemy wnętrze nawiasu i rozbijamy
# po ` | `, zamiast szukać podciągu w całym wierszu.
ZNANE_POLECENIA="$(printf '%s' "$UZYCIE" | sed -n 's/.*\[\(.*\)\].*/\1/p' | tr '|' '\n' | tr -d ' ')"

if [ -z "$ZNANE_POLECENIA" ]; then
	zle_ "nie udało się wyłuskać listy poleceń z wyjścia perturbuj.py — kontrola mierzyłaby pustkę"
fi

WOLANE="$(grep -oE '\bperturbuj [a-z0-9-]+' skrypty/perturbacje.sh | awk '{print $2}' | sort -u)"

if [ -z "$WOLANE" ]; then
	zle_ "nie znaleziono ANI JEDNEGO wywołania 'perturbuj X' w perturbacje.sh — parser się rozjechał"
fi

for POLECENIE in $WOLANE; do
	ZNANE=0

	for KANDYDAT in $ZNANE_POLECENIA; do
		[ "$POLECENIE" = "$KANDYDAT" ] && ZNANE=1
	done

	if [ "$ZNANE" -eq 1 ]; then
		ok_ "perturbuj.py zna polecenie '$POLECENIE'"
	else
		zle_ "perturbacje.sh woła 'perturbuj $POLECENIE', a perturbuj.py takiego polecenia NIE MA"
	fi
done

# KIERUNEK ODWROTNY dla samego dopasowania: bez niego „wszystko się zgadza"
# przechodzi także wtedy, gdy porównanie nigdy nikogo nie odrzuca.
NIEISTNIEJACE="polecenie-ktorego-nigdy-nie-bylo"
ODRZUCONE=1

for KANDYDAT in $ZNANE_POLECENIA; do
	[ "$NIEISTNIEJACE" = "$KANDYDAT" ] && ODRZUCONE=0
done

if [ "$ODRZUCONE" -eq 1 ]; then
	ok_ "porównanie odrzuca nazwę spoza listy (kontrola pozytywna dopasowania)"
else
	zle_ "porównanie przyjmuje nazwę, której nie ma — dopasowanie nie rozstrzyga niczego"
fi

# --- 3. perturbacje.sh startuje i wypisuje swoją listę ---------------------
# KOD WYJSCIA LISTOWANIA JEST CZESCIA POMIARU (12.08).
#
# Bez tego kontrola brala TEKST ODMOWY za liste scenariuszy: gdy w indeksie
# gita czekaly przygotowane zmiany, `perturbacje.sh` odmawialo, a ten skrypt
# rozbijal komunikat odmowy na slowa i meldowal 29 wywolan wskazujacych
# w prozne. Diagnoza byla nieprawdziwa co do jednego slowa, a czerwien
# prawdziwa — czyli najgorszy uklad: alarm bez adresu.
#
# To ta sama wada, ktora naprawiono przy nieznanej nazwie (R6B-8), tylko
# o jedno wywolanie dalej: wynik rozstrzygany CUDZYM sygnalem.
KOD_WYPISU=0
LISTA="$(bash skrypty/perturbacje.sh --lista 2>&1)" || KOD_WYPISU=$?

if [ "$KOD_WYPISU" -ne 0 ]; then
	zle_ "perturbacje.sh --lista zakonczylo sie kodem $KOD_WYPISU — listy NIE MA,"
	zle_ "wiec wszystko ponizej mierzyloby tekst bledu. Wyjscie: $LISTA"
	LISTA=""
fi

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
#
# ROZSTRZYGNIĘCIE WŁASNE, NIE CUDZY KOD WYJŚCIA (R6B-8). Poprzednia wersja
# przyjmowała „cokolwiek niezerowego" jako dowód odrzucenia — a `perturbacje.sh`
# wraca niezerowo także wtedy, gdy nie ma Dockera, gdy stos nie wstaje albo gdy
# w indeksie gita czekają zmiany. Kontrola świeciła zielono na czerwieni, która
# z nią nie miała nic wspólnego: jedna wartość, wiele światów.
#
# Teraz wymagamy DOKŁADNIE tego kodu, który `perturbacje.sh` rezerwuje dla
# nieznanej nazwy, ORAZ jego diagnozy w wyjściu. Oba sygnały pochodzą z TEJ
# kontroli, nie z otoczenia.
KOD_NIEZNANA_NAZWA=4
KOD=0
WYJSCIE_NIEZNANEJ="$(bash skrypty/perturbacje.sh nazwa-ktorej-nigdy-nie-bylo 2>&1)" || KOD=$?

if [ "$KOD" -ne "$KOD_NIEZNANA_NAZWA" ]; then
	zle_ "perturbacje.sh na nieznaną nazwę zwrócił kod $KOD zamiast $KOD_NIEZNANA_NAZWA (kod może być CUDZY: brak Dockera, niezdrowy stos)"
elif ! printf '%s' "$WYJSCIE_NIEZNANEJ" | grep -q 'NIEZNANA PERTURBACJA'; then
	zle_ "kod wyjścia się zgadza, ale nie ma diagnozy 'NIEZNANA PERTURBACJA' — nie wiadomo, co go wywołało"
else
	ok_ "perturbacje.sh odrzuca nieznaną nazwę WŁASNYM kodem $KOD_NIEZNANA_NAZWA i mówi dlaczego"
fi

# KIERUNEK ODWROTNY: nazwa ZNANA nie może dostać kodu „nieznana nazwa".
# Bez tego „kod 4 przy literówce" przechodzi także dla skryptu, który zwraca
# 4 zawsze. `--lista` nie dotyka Dockera, więc kontrola nie mierzy otoczenia.
KOD_LISTY=0
bash skrypty/perturbacje.sh --lista >/dev/null 2>&1 || KOD_LISTY=$?

if [ "$KOD_LISTY" -eq "$KOD_NIEZNANA_NAZWA" ]; then
	zle_ "perturbacje.sh zwraca kod nieznanej nazwy także dla poprawnego wywołania — kod nie rozstrzyga niczego"
else
	ok_ "poprawne wywołanie NIE dostaje kodu nieznanej nazwy (kod $KOD_LISTY)"
fi

printf '\n'
if [ "$BLEDY" -eq 0 ]; then
	printf 'SKRYPTY URUCHAMIALNE — wszystkie wywołania rozwiązują się do istniejących celów\n'
	exit 0
fi

printf 'SKRYPTY NIESPRAWNE — %s wywołań wskazuje w próżnię\n' "$BLEDY"
exit 1
