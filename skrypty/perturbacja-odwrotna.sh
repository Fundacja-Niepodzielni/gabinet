#!/usr/bin/env bash
# ===========================================================================
# PERTURBACJA ODWROTNA + TOWARZYSZ POKRYCIA
#
# „POPRAW zachowanie i sprawdź, czy któryś ZIELONY test się zaczerwieni."
#
# Wzorzec pochodzi z `hub/scripts/perturbacja-odwrotna.sh`. To jest ADAPTACJA,
# a adaptacja JEST weryfikacją — więc niżej opisane są DWIE rzeczy, które przy
# przenoszeniu okazały się w oryginale niewystarczające, i jedna, która
# okazała się słuszna i została przejęta bez zmian.
#
# =================== CO PRZEJĄŁEM BEZ ZMIAN (i dlaczego) ===================
#
# Trzy wymogi klamry — `trap … EXIT INT TERM`, ODMOWA przy brudnym drzewie
# (nigdy sprzątanie cudzych zmian) i ODCZYT Z POWROTEM po cofnięciu. Sprawdziłem
# je u siebie i nie mam do nich zastrzeżeń. Przebieg BAZOWY też: bez niego
# „zielony → czerwony" jest nieodróżnialne od „czerwony od początku", a u mnie
# jest to szczególnie dotkliwe, bo suita ma JEDEN trwale czerwony test (noga 1).
#
# =================== CO ZMIENIŁEM (1): DOWÓD MUTACJI =======================
#
# Hub dowodzi mutacji przez `git diff --stat` (ich linie 279–286). To mówi
# „plik na HOŚCIE różni się od HEAD". Nie mówi dwóch rzeczy:
#
#   (a) że zniknął DOKŁADNIE ten fragment, o który chodziło — plik mógł się
#       różnić z innego powodu (inna deklaracja dotknęła tego samego pliku
#       wcześniej w tym samym przebiegu; u hubu CZTERY z pięciu deklaracji
#       celują w ten sam `Uniewaznienia.php`);
#   (b) że zmiana dotarła do programu, KTÓRY NAPRAWDĘ BIEGNIE — testy biegną
#       w kontenerze, a `git diff` pyta hosta.
#
# Tutaj dowód mutacji ODCZYTUJE PLIK Z WNĘTRZA KONTENERA i sprawdza trzy rzeczy:
# fragment SZUKAJ zniknął, fragment ZASTAP jest obecny, treść różni się od
# ODCZYTU BAZOWEGO zrobionego przed mutacją. To ta sama dyscyplina, co
# `dowod_zniknieciem` w `skrypty/perturbacje.sh`: negatywna forma („nie ma
# starego tekstu") ma gałąź zdegenerowaną, więc wymaga odczytu bazowego.
#
# =================== CO DODAŁEM (2): TOWARZYSZ =============================
#
# Hub sam nazwał brak: „zero kandydatów" jest zgodne z DWOMA światami —
# (1) żaden test nie przypina zdegradowanego zachowania (zdrowie),
# (2) żaden test w ogóle nie wykonuje tej ścieżki (brak pokrycia).
# Ich narzędzie tych światów nie rozdziela, więc ich zero nie jest zdrowiem.
#
# BOMBA rozdziela je jednym dodatkowym przebiegiem: w to samo miejsce wstawiamy
# `throw`. Jeśli po wysadzeniu NIC nie pada — linii nie wykonuje żaden test,
# więc wynik poprawy jest NIEROZSTRZYGNIĘTY, a nie „zdrowy".
#
#   bomba zabija testy  +  poprawa zabija testy   → KANDYDAT (test przypina defekt)
#   bomba zabija testy  +  poprawa nie zabija     → ZDROWE (ścieżka pokryta, nic nie pinuje)
#   bomba NIE zabija nikogo                       → NIEROZSTRZYGNIĘTE (zero pokrycia)
#
# =================== CO WYMUSIŁEM KONSTRUKCJĄ (3) ==========================
#
# Hub zapisał w komentarzu: „co najmniej połowa deklaracji musi ODMAWIAĆ czegoś,
# co dziś przechodzi". Komentarz nie ma jak zadziałać. Tutaj rodzaj poprawy jest
# DANĄ w deklaracji, a narzędzie ODMAWIA STARTU przy zestawie dobranym w słabą
# stronę. Warunek przed strażnikiem: zestaw nie ma jak być za słaby.
#
# UŻYCIE:  bash skrypty/perturbacja-odwrotna.sh [--tylko R-1]
# KODY:    0 = pomiar wykonany · 2 = ODMOWA · 1 = pomiar nierozstrzygnięty
# ===========================================================================
set -uo pipefail

export MSYS_NO_PATHCONV=1
export MSYS2_ARG_CONV_EXCL='*'

KATALOG_REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$KATALOG_REPO" || exit 2

# Klasa MSYS_NO_PATHCONV — przejęta z hubu jako OSTRZEŻENIE, nie jako ozdoba:
# u nich pierwszy przebieg odmówił, bo Windows Python dostawał ścieżkę POSIX
# i nie umiał otworzyć istniejącego pliku. Guard zachował się poprawnie, ale
# przyczyną był przyrząd. Nie powtarzam tego błędu — konwertuję z góry.
hp() {
	if command -v cygpath >/dev/null 2>&1; then cygpath -w "$1"; else printf '%s' "$1"; fi
}

PY="${PY:-python}"
command -v "$PY" >/dev/null 2>&1 || PY=python3

# Nadpisywalne WYŁĄCZNIE po to, żeby dało się uruchomić kontrole negatywne
# samego narzędzia (pusty plik, zestaw dobrany w słabą stronę). Odmowa, której
# nikt nigdy nie wywołał, jest deklaracją, nie zabezpieczeniem.
DEKLARACJE="${DEKLARACJE:-$KATALOG_REPO/skrypty/perturbacje-odwrotne/POPRAWKI.txt}"
WYNIKI="$KATALOG_REPO/dowody/odwrotna"
MONTAZ="/srv/gabinet"          # gdzie repo widać W KONTENERZE
TYLKO=""
[ "${1:-}" = "--tylko" ] && TYLKO="${2:-}"

odmowa() { printf '\nODMOWA: %s\n' "$1" >&2; exit 2; }

dc() { docker compose "$@"; }

# --- ADAPTACJA (A): suita z wynikiem PER TEST ------------------------------
# `exec`, nie `run --rm`: mierzę TEN kontener, który stoi, w tym samym
# środowisku PHP co runtime.
uruchom_suite() {                       # $1 = ścieżka XML na hoście (w repo)
	local wzgledna="${1#"$KATALOG_REPO/"}"
	dc exec -T app ./vendor/bin/pest --log-junit "$MONTAZ/$wzgledna"
}

# --- ADAPTACJA (B): parser wyniku na `nazwa<TAB>status` --------------------
parsuj_wynik() {                        # $1 = XML, $2 = plik docelowy
	"$PY" - "$(hp "$1")" "$(hp "$2")" <<'PY'
import sys, xml.etree.ElementTree as ET
zrodlo, cel = sys.argv[1], sys.argv[2]
try:
    drzewo = ET.parse(zrodlo)
except Exception as e:
    sys.stderr.write("NIE UMIEM SPARSOWAC %s: %s\n" % (zrodlo, e)); sys.exit(3)
wiersze = []
for tc in drzewo.iter('testcase'):
    nazwa = "%s::%s" % (tc.get('classname') or '?', tc.get('name') or '?')
    zly = any(tc.find(t) is not None for t in ('failure', 'error'))
    pominiety = tc.find('skipped') is not None
    wiersze.append("%s\t%s" % (nazwa, 'POMINIETY' if pominiety else ('CZERWONY' if zly else 'ZIELONY')))
if not wiersze:
    sys.stderr.write("ZERO TESTOW w %s — pomiar nierozstrzygniety\n" % zrodlo); sys.exit(4)
open(cel, 'w', encoding='utf-8').write("\n".join(sorted(wiersze)) + "\n")
print(len(wiersze))
PY
}

# ===========================================================================
# ODMOWA PRZED CZYMKOLWIEK, CO DOTYKA DRZEWA
# ===========================================================================
[ -f "$DEKLARACJE" ] || odmowa "brak pliku deklaracji: $DEKLARACJE"
[ -s "$DEKLARACJE" ] || odmowa "plik deklaracji jest PUSTY (pustka to błąd, nie zero poprawek)"

BRUDNE="$(git status --porcelain -- backend/app backend/tests backend/config backend/routes 2>/dev/null)"
if [ -n "$BRUDNE" ]; then
	printf '%s\n' "$BRUDNE" >&2
	odmowa "drzewo robocze ma niezacommitowane zmiany w mierzonym zakresie — nie umiałbym ich odróżnić od własnych mutacji"
fi

command -v docker >/dev/null 2>&1 || odmowa "brak docker"
dc exec -T app true >/dev/null 2>&1 || odmowa "kontener app nie odpowiada — pomiar biegłby w innym programie niż runtime"
mkdir -p "$WYNIKI" || odmowa "nie umiem utworzyć $WYNIKI"
: > "$WYNIKI/.probny" 2>/dev/null || odmowa "katalog wyników nie jest zapisywalny: $WYNIKI"
rm -f "$WYNIKI/.probny"

# ===========================================================================
# ROZBICIE DEKLARACJI + WARUNEK SIŁY ZESTAWU
# ===========================================================================
FRAG="$WYNIKI/fragmenty"
rm -rf "$FRAG"; mkdir -p "$FRAG"
LICZBY="$("$PY" - "$(hp "$DEKLARACJE")" "$(hp "$FRAG")" <<'PY'
import sys, io, os, re
zrodlo, katalog = sys.argv[1], sys.argv[2]
tekst = io.open(zrodlo, encoding='utf-8').read().split("\n")
i, n, zaciesnia = 0, 0, 0
while i < len(tekst):
    m = re.match(r'^###\s*(\S+)\s*\|\s*([^|]+?)\s*\|\s*(\S+)\s*\|\s*(.*)$', tekst[i])
    if not m:
        i += 1; continue
    ident, plik, rodzaj, opis = m.group(1), m.group(2).strip(), m.group(3).strip().upper(), m.group(4).strip()
    if rodzaj not in ('ZACIESNIA', 'DODAJE'):
        sys.stderr.write("DEKLARACJA %s: rodzaj '%s' nie jest ZACIESNIA ani DODAJE\n" % (ident, rodzaj)); sys.exit(7)
    if rodzaj == 'ZACIESNIA':
        zaciesnia += 1
    i += 1
    bloki = {}
    biezacy = None
    while i < len(tekst):
        w = tekst[i]
        if w.startswith('--- KONIEC'):
            i += 1; break
        m2 = re.match(r'^---\s+(SZUKAJ|ZASTAP|BOMBA)\s*$', w)
        if m2:
            biezacy = m2.group(1); bloki[biezacy] = []; i += 1; continue
        if biezacy:
            bloki[biezacy].append(w)
        i += 1
    for wymagany in ('SZUKAJ', 'ZASTAP', 'BOMBA'):
        if wymagany not in bloki or not "\n".join(bloki[wymagany]).strip():
            sys.stderr.write("DEKLARACJA %s: brak albo pusty blok %s\n" % (ident, wymagany)); sys.exit(8)
    n += 1
    baza = os.path.join(katalog, "%03d" % n)
    io.open(baza + '.meta', 'w', encoding='utf-8').write("%s\n%s\n%s\n%s\n" % (ident, plik, rodzaj, opis))
    for nazwa in ('SZUKAJ', 'ZASTAP', 'BOMBA'):
        io.open(baza + '.' + nazwa.lower(), 'w', encoding='utf-8').write("\n".join(bloki[nazwa]))
print("%d %d" % (n, zaciesnia))
PY
)" || odmowa "nie umiem rozłożyć deklaracji na fragmenty"

LICZBA_DEKL="$(printf '%s' "$LICZBY" | awk '{print $1}')"
LICZBA_ZAC="$(printf '%s' "$LICZBY" | awk '{print $2}')"
[ "${LICZBA_DEKL:-0}" -gt 0 ] || odmowa "zero deklaracji po rozłożeniu (pustka to błąd, nie zero poprawek)"

# WARUNEK SIŁY ZESTAWU — lekcja hubu, wymuszona konstrukcją, nie komentarzem.
if [ $((LICZBA_ZAC * 2)) -lt "$LICZBA_DEKL" ]; then
	odmowa "zestaw dobrany w NAJMNIEJ CZUŁĄ STRONĘ: $LICZBA_ZAC z $LICZBA_DEKL deklaracji ZACIEŚNIA, potrzeba co najmniej połowy. Test przypinający defekt pada niemal wyłącznie przy poprawie ZACIEŚNIAJĄCEJ."
fi

# ===========================================================================
# KLAMRA
# ===========================================================================
PLIKI_DOTKNIETE=""
cofnij_wszystko() {
	local p
	for p in $PLIKI_DOTKNIETE; do git checkout -- "$p" 2>/dev/null; done
	local reszta
	reszta="$(git status --porcelain -- backend/app backend/tests backend/config backend/routes 2>/dev/null)"
	if [ -n "$reszta" ]; then
		printf '\n!!! DRZEWO NIE WRÓCIŁO DO STANU WYJŚCIOWEGO — cofnij RĘCZNIE:\n' >&2
		printf '%s\n' "$reszta" >&2
	fi
	return 0
}
trap cofnij_wszystko EXIT INT TERM

printf '\n== PERTURBACJA ODWROTNA (gabinet) — poprawiam zachowanie, szukam ZIELONYCH, które padną ==\n'
printf '   deklaracji: %s, w tym ZACIEŚNIAJĄCYCH: %s\n' "$LICZBA_DEKL" "$LICZBA_ZAC"

# --- odczyt bazowy Z KONTENERA, per plik ----------------------------------
zapisz_baze_pliku() {                   # $1 = ścieżka względna od korzenia repo
	local cel="$WYNIKI/baza-plik-$(printf '%s' "$1" | tr '/' '_')"
	[ -f "$cel" ] && return 0
	dc exec -T app cat "$MONTAZ/$1" > "$cel" 2>/dev/null || return 1
	[ -s "$cel" ] || return 1
	return 0
}

# --- DOWÓD MUTACJI: odczyt z KONTENERA + porównanie z bazą ----------------
dowod_mutacji() {                       # $1=plik $2=plik_szukaj $3=plik_oczekiwany $4=etykieta
	local plik="$1" fszukaj="$2" foczek="$3" etykieta="$4"
	local teraz="$WYNIKI/.teraz"
	local baza="$WYNIKI/baza-plik-$(printf '%s' "$plik" | tr '/' '_')"

	dc exec -T app cat "$MONTAZ/$plik" > "$teraz" 2>/dev/null
	if [ ! -s "$teraz" ]; then
		printf '   DOWÓD MUTACJI ZAWIÓDŁ: kontener oddał PUSTY plik %s\n' "$plik"; return 1
	fi

	"$PY" - "$(hp "$teraz")" "$(hp "$baza")" "$(hp "$fszukaj")" "$(hp "$foczek")" "$etykieta" <<'PY'
import sys, io
teraz = io.open(sys.argv[1], encoding='utf-8', errors='replace').read()
baza  = io.open(sys.argv[2], encoding='utf-8', errors='replace').read()
szukaj = io.open(sys.argv[3], encoding='utf-8').read().strip("\n")
oczek  = io.open(sys.argv[4], encoding='utf-8').read().strip("\n")
etyk = sys.argv[5]

def norm(s):
    return s.replace("\r\n", "\n")

teraz, baza, szukaj, oczek = map(norm, (teraz, baza, szukaj, oczek))

bledy = []
# 1. odczyt bazowy MUSI zawierac fragment — inaczej perturbacja rozjechala sie z kodem
if szukaj not in baza:
    bledy.append("PERTURBACJA ROZJECHALA SIE Z KODEM: fragmentu SZUKAJ nie bylo w odczycie bazowym")

# 2. po mutacji fragment ma ZNIKNAC — ale TYLKO gdy mutacja jest ZASTEPUJACA.
#
#    NAPRAWA PRZYRZADU, zmierzona na wlasnym pierwszym przebiegu: mutacja
#    DODAJACA (zostawia oryginalna linie, dokladajac cos obok) sprawia, ze
#    fragment SZUKAJ nadal jest obecny — bo zawiera go sam fragment docelowy.
#    Pierwsza wersja tego sprawdzenia ODMOWILA wtedy mutacji, ktora WESZLA
#    poprawnie. Odmowa byla falszywa, choc w bezpieczna strone.
#
#    Ma to znaczenie ponad moim repozytorium: u hubu CZTERY z pieciu deklaracji
#    sa DODAJACE, wiec naiwny przeszczep tego sprawdzenia odmowilby wiekszosci
#    ich zestawu i wygladaloby to jak awaria narzedzia, a nie jak jego wada.
if szukaj not in oczek and szukaj in teraz:
    bledy.append("fragment SZUKAJ NADAL JEST w pliku widzianym przez kontener")
# 3. fragment docelowy ma BYC
if oczek not in teraz:
    bledy.append("fragmentu docelowego NIE MA w pliku widzianym przez kontener")
# 4. tresc ma sie roznic od bazy
if teraz == baza:
    bledy.append("kontener widzi DOKLADNIE te sama tresc co przed mutacja")

if bledy:
    sys.stderr.write("   DOWOD MUTACJI ZAWIODL (%s):\n" % etyk)
    for b in bledy:
        sys.stderr.write("      - %s\n" % b)
    sys.exit(1)
print("   dowod mutacji: fragment zniknal, docelowy obecny, tresc rozna od bazowej (odczyt Z KONTENERA)")
PY
	return $?
}

zastosuj() {                            # $1=plik $2=plik_szukaj $3=plik_zastap
	"$PY" - "$(hp "$1")" "$(hp "$2")" "$(hp "$3")" <<'PY'
import sys, io
plik, fs, fz = sys.argv[1], sys.argv[2], sys.argv[3]
tresc = io.open(plik, encoding='utf-8', newline='').read()
szukaj = io.open(fs, encoding='utf-8').read().strip("\n")
zastap = io.open(fz, encoding='utf-8').read().strip("\n")
uzyty = szukaj
if szukaj not in tresc:
    # plik moze miec CRLF — probujemy raz, jawnie, i mowimy o tym
    szukaj_crlf = szukaj.replace("\n", "\r\n")
    if szukaj_crlf in tresc:
        uzyty = szukaj_crlf
        zastap = zastap.replace("\n", "\r\n")
    else:
        sys.stderr.write("FRAGMENT NIE WYSTEPUJE w %s\n" % plik); sys.exit(5)
if tresc.count(uzyty) > 1:
    sys.stderr.write("FRAGMENT WYSTEPUJE %d RAZY — niejednoznaczne\n" % tresc.count(uzyty)); sys.exit(6)
io.open(plik, 'w', encoding='utf-8', newline='').write(tresc.replace(uzyty, zastap))
PY
}

# ===========================================================================
# PRZEBIEG BAZOWY
# ===========================================================================
printf '\n-- przebieg BAZOWY (bez żadnej poprawy) --\n'
XML_BAZA="$WYNIKI/baza.xml"; LOG_BAZA="$WYNIKI/baza.log"
uruchom_suite "$XML_BAZA" > "$LOG_BAZA" 2>&1
KOD_BAZA=$?
[ -s "$XML_BAZA" ] || { tail -20 "$LOG_BAZA"; odmowa "przebieg bazowy nie wyprodukował wyniku XML"; }

LISTA_BAZA="$WYNIKI/baza.tsv"
LICZBA_BAZA="$(parsuj_wynik "$XML_BAZA" "$LISTA_BAZA")" || odmowa "nie umiem odczytać wyniku bazowego"
ZIELONE_BAZA="$(grep -c 'ZIELONY$' "$LISTA_BAZA" || true)"
CZERWONE_BAZA="$(grep -c 'CZERWONY$' "$LISTA_BAZA" || true)"
printf '   testów: %s, zielonych: %s, CZERWONYCH JUŻ W BAZIE: %s, kod suity: %s\n' \
	"$LICZBA_BAZA" "$ZIELONE_BAZA" "$CZERWONE_BAZA" "$KOD_BAZA"
printf '   (czerwone w bazie są OCZEKIWANE — noga 1. Porównuję ZBIORY, nie liczby.)\n'

[ "${ZIELONE_BAZA:-0}" -gt 0 ] || odmowa "w przebiegu bazowym ZERO zielonych testów — nie ma czego zaczerwienić"

grep 'ZIELONY$' "$LISTA_BAZA" | cut -f1 | sort > "$WYNIKI/.zielone-bazy"

# ===========================================================================
# PĘTLA: dla każdej deklaracji — POPRAWA, potem BOMBA
# ===========================================================================
RAPORT="$WYNIKI/RAPORT.txt"
: > "$RAPORT"
printf 'PERTURBACJA ODWROTNA + TOWARZYSZ — gabinet\nbaza: %s testów, %s zielonych, %s czerwonych\n\n' \
	"$LICZBA_BAZA" "$ZIELONE_BAZA" "$CZERWONE_BAZA" >> "$RAPORT"

L_POPRAW=0; L_KANDYDATOW=0; L_NIEROZSTRZYGNIETYCH=0; L_ZDROWYCH=0

# zwraca liczbę testów ZIELONYCH W BAZIE, które teraz nie są zielone
policz_utracone() {                     # $1 = tsv biezacy, $2 = plik wynikowy
	grep 'ZIELONY$' "$1" | cut -f1 | sort > "$WYNIKI/.zt"
	comm -23 "$WYNIKI/.zielone-bazy" "$WYNIKI/.zt" > "$2"
	grep -c . "$2" || true
}

przebieg_z_mutacja() {                  # $1=id $2=plik $3=frag_szukaj $4=frag_docelowy $5=etykieta $6=przyrostek
	local id="$1" plik="$2" fszukaj="$3" fdocel="$4" etyk="$5" suf="$6"

	if ! zastosuj "$plik" "$fszukaj" "$fdocel"; then
		printf '   NIEROZSTRZYGNIĘTE (%s): mutacja nie dała się zastosować\n' "$etyk"
		return 1
	fi
	PLIKI_DOTKNIETE="$PLIKI_DOTKNIETE $plik"

	if ! dowod_mutacji "$plik" "$fszukaj" "$fdocel" "$etyk"; then
		git checkout -- "$plik" 2>/dev/null
		return 1
	fi

	local xml="$WYNIKI/$id$suf.xml" log="$WYNIKI/$id$suf.log" tsv="$WYNIKI/$id$suf.tsv"
	uruchom_suite "$xml" > "$log" 2>&1
	local kod=$?
	if [ ! -s "$xml" ]; then
		printf '   NIEROZSTRZYGNIĘTE (%s): brak XML, kod %s\n' "$etyk" "$kod"
		git checkout -- "$plik" 2>/dev/null
		return 1
	fi
	parsuj_wynik "$xml" "$tsv" >/dev/null || { git checkout -- "$plik" 2>/dev/null; return 1; }

	UTRACONE_PLIK="$WYNIKI/$id$suf.utracone.txt"
	UTRACONE="$(policz_utracone "$tsv" "$UTRACONE_PLIK")"

	git checkout -- "$plik" 2>/dev/null
	if [ -n "$(git status --porcelain -- "$plik")" ]; then
		odmowa "nie udało się cofnąć $plik — przerywam, żeby nie mierzyć na skażonym drzewie"
	fi
	return 0
}

for META in "$FRAG"/*.meta; do
	[ -f "$META" ] || continue
	ID="$(sed -n '1p' "$META")"; PLIK="$(sed -n '2p' "$META")"
	RODZAJ="$(sed -n '3p' "$META")"; OPIS="$(sed -n '4p' "$META")"
	B="${META%.meta}"

	[ -n "$TYLKO" ] && [ "$TYLKO" != "$ID" ] && continue

	printf '\n-- %s [%s] (%s) --\n   %s\n' "$ID" "$RODZAJ" "$PLIK" "$OPIS"
	L_POPRAW=$((L_POPRAW + 1))

	if [ ! -f "$PLIK" ]; then
		printf '   NIEROZSTRZYGNIĘTE: plik nie istnieje\n'
		printf '%s NIEROZSTRZYGNIETE (brak pliku)\n' "$ID" >> "$RAPORT"
		L_NIEROZSTRZYGNIETYCH=$((L_NIEROZSTRZYGNIETYCH + 1)); continue
	fi
	zapisz_baze_pliku "$PLIK" || { printf '   NIEROZSTRZYGNIĘTE: nie umiem odczytać bazy pliku z kontenera\n'; L_NIEROZSTRZYGNIETYCH=$((L_NIEROZSTRZYGNIETYCH+1)); continue; }

	# ---------- 1. TOWARZYSZ: czy ta ścieżka jest w ogóle wykonywana ----------
	printf '   [towarzysz] BOMBA — czy suita w ogóle wykonuje tę linię?\n'
	if ! przebieg_z_mutacja "$ID" "$PLIK" "$B.szukaj" "$B.bomba" "BOMBA" ".bomba"; then
		printf '%s NIEROZSTRZYGNIETE (bomba nie dala sie zmierzyc)\n' "$ID" >> "$RAPORT"
		L_NIEROZSTRZYGNIETYCH=$((L_NIEROZSTRZYGNIETYCH + 1)); continue
	fi
	ZABITE_BOMBA="$UTRACONE"
	printf '   [towarzysz] testów zabitych bombą: %s\n' "$ZABITE_BOMBA"

	# ---------- 2. POPRAWA ----------
	printf '   [poprawa] stosuję POPRAWĘ zachowania\n'
	if ! przebieg_z_mutacja "$ID" "$PLIK" "$B.szukaj" "$B.zastap" "POPRAWA" ""; then
		printf '%s NIEROZSTRZYGNIETE (poprawa nie dala sie zmierzyc)\n' "$ID" >> "$RAPORT"
		L_NIEROZSTRZYGNIETYCH=$((L_NIEROZSTRZYGNIETYCH + 1)); continue
	fi
	ZABITE_POPRAWA="$UTRACONE"
	KANDYDACI_PLIK="$WYNIKI/$ID.utracone.txt"
	printf '   [poprawa] ZIELONE→NIE-ZIELONE: %s\n' "$ZABITE_POPRAWA"

	# ---------- 3. WERDYKT Z DWÓCH LICZB ----------
	if [ "${ZABITE_BOMBA:-0}" -eq 0 ]; then
		printf '   >>> NIEROZSTRZYGNIĘTE: bomba nie zabiła NIKOGO — tej ścieżki nie wykonuje żaden test.\n'
		printf '       „Zero kandydatów" znaczy tu ZERO POKRYCIA, nie zdrowie.\n'
		printf '%s NIEROZSTRZYGNIETE bomba=0 poprawa=%s (zero pokrycia)\n' "$ID" "$ZABITE_POPRAWA" >> "$RAPORT"
		L_NIEROZSTRZYGNIETYCH=$((L_NIEROZSTRZYGNIETYCH + 1))
	elif [ "${ZABITE_POPRAWA:-0}" -gt 0 ]; then
		printf '   >>> KANDYDACI (%s) — testy zielone przed poprawą, niezielone po:\n' "$ZABITE_POPRAWA"
		sed 's/^/       KANDYDAT: /' "$KANDYDACI_PLIK"
		printf '%s KANDYDACI bomba=%s poprawa=%s\n' "$ID" "$ZABITE_BOMBA" "$ZABITE_POPRAWA" >> "$RAPORT"
		sed 's/^/    KANDYDAT /' "$KANDYDACI_PLIK" >> "$RAPORT"
		L_KANDYDATOW=$((L_KANDYDATOW + ZABITE_POPRAWA))
	else
		printf '   >>> ZDROWE: ścieżka JEST pokryta (bomba zabiła %s), a poprawa nie zaczerwieniła nikogo.\n' "$ZABITE_BOMBA"
		printf '%s ZDROWE bomba=%s poprawa=0\n' "$ID" "$ZABITE_BOMBA" >> "$RAPORT"
		L_ZDROWYCH=$((L_ZDROWYCH + 1))
	fi
done

rm -f "$WYNIKI/.zt" "$WYNIKI/.teraz"

# ===========================================================================
# WERDYKT
# ===========================================================================
printf '\n== Podsumowanie ==\n'
printf 'deklaracje: %s (ZACIEŚNIA %s) · zdrowe: %s · z kandydatami: %s · nierozstrzygnięte: %s\n' \
	"$L_POPRAW" "$LICZBA_ZAC" "$L_ZDROWYCH" "$L_KANDYDATOW" "$L_NIEROZSTRZYGNIETYCH"

if [ "$L_POPRAW" -eq 0 ]; then
	printf 'PERTURBACJA ODWROTNA NIEROZSTRZYGNIĘTA — nie wykonano ANI JEDNEJ poprawy\n'
	exit 1
fi

if [ "$L_KANDYDATOW" -eq 0 ]; then
	printf 'ZERO KANDYDATÓW — ale przeczytaj rozbicie wyżej:\n'
	printf '  · pozycje ZDROWE to wynik: ścieżka pokryta, nikt nie przypina zdegradowanego zachowania.\n'
	printf '  · pozycje NIEROZSTRZYGNIĘTE (bomba=0) to BRAK POKRYCIA i NIE są zdrowiem.\n'
else
	printf '%s KANDYDATÓW na testy przypinające defekt.\n' "$L_KANDYDATOW"
	printf '  KANDYDAT ≠ WERDYKT. Test może słusznie czerwienić, gdy poprawa zmienia kontrakt,\n'
	printf '  który on poprawnie opisywał. Rozstrzyga człowiek.\n'
fi
printf 'raport: %s\n' "$RAPORT"
exit 0
