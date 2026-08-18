#!/usr/bin/env bash
# ===========================================================================
# skrypty/perturbacje.sh — DOWÓD, że kontrole bramki umieją zaświecić czerwono.
#
#   ./skrypty/perturbacje.sh            # wszystkie perturbacje
#   ./skrypty/perturbacje.sh --lista    # co jest sprawdzane
#   ./skrypty/perturbacje.sh testy      # jedna, po nazwie
#
# ---------------------------------------------------------------------------
# ZASADA (D-0013, zespół hubu, przyjęta jako reguła ekosystemu):
#
#   „Asercja bez dowodu, że umie zaświecić na czerwono, jest traktowana
#    jak nieistniejąca."
#
# U hubu dwanaście kontroli przechodziło bez takiego dowodu — w tym PUSTA
# SUITA TESTÓW, przez którą CI świeciło zielono przy ZERO wykonanych testach.
# Wykryły to dopiero perturbacje.
#
# Każda perturbacja: sztucznie łamie jedną regułę → sprawdza, że odpowiednia
# kontrola bramki pada → PRZYWRACA stan wyjściowy. Przywrócenie idzie przez
# `trap`, więc działa także przy przerwaniu skryptu.
#
# ---------------------------------------------------------------------------
# DRUGA ZASADA (lekcja zespołu helpdesku, przyjęta do puli ekosystemu):
#
#   PERTURBACJA MUSI CELOWAĆ W STAN, KTÓREGO SYSTEM NIE PRZYWRÓCI SAM —
#   albo w taki, którego przywrócenie nie unieważnia naruszenia.
#
# Inaczej mierzysz szybkość mechanizmu samonaprawczego, nie czujność kontroli.
# U nich Zammad odbudowywał skasowany indeks, zanim suita doszła do kontroli:
# perturbacja „przechodziła" losowo, zależnie od tempa przebiegu.
#
# U NAS trafiło to w `p_puls`: harmonogram zapisuje puls co minutę, więc
# skasowanie wpisu i sprawdzenie kontroli było wyścigiem. Naprawa: najpierw
# ZATRZYMUJEMY harmonogram, dopiero potem psujemy puls — wtedy naruszenie
# przeżywa tak długo, jak trzeba.
#
# Dotyczy każdego mechanizmu samonaprawczego: cache z unieważnianiem
# (materializacja slotów w F2), kolejki z ponowieniami, zadania cron.
# Reguła projektowa: mutacja i jej kontrola stanowią PARĘ ATOMOWĄ
# (mutacja → dowód mutacji → kontrola → sprzątanie), nigdy „zmutuj wszystko,
# potem jedź suitą".
#
# TRZECIA ZASADA (z tej samej rundy): perturbacja potrafi KŁAMAĆ o sprawnej
# kontroli — ich U5 czytała stary znacznik, gdy init jeszcze pracował.
# Dlatego dowód mutacji obowiązuje W OBIE STRONY: zanim uznamy, że kontrola
# zareagowała, sprawdzamy NIEZALEŻNIE, że mutacja naprawdę jest w mocy.
# ---------------------------------------------------------------------------
# ---------------------------------------------------------------------------
#
# STOS: perturbacje mają WŁASNY projekt compose (`gabinet-perturbacje`),
# własny prefiks nazw i własne porty; stawiają go same, jeśli nie stoi.
# Uruchomienie na projekcie `gabinet` jest ZABRONIONE — patrz W-11 niżej.
#
# Wcześniejszy nagłówek twierdził, że perturbacje „nie ruszają danych, bo
# każda zmiana jest cofana". To była NIEPRAWDA: perturbacje haseł wołają
# `migrate:fresh --force`, co kasuje bazę i nie jest cofane. Deklaracja
# w komentarzu nie jest kontrolą.
# ===========================================================================
set -uo pipefail

export MSYS_NO_PATHCONV=1
export MSYS2_ARG_CONV_EXCL='*'

KORZEN="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$KORZEN"

# W-11 z rundy 4 — NAJPOWAŻNIEJSZE ZNALEZISKO OPERACYJNE.
#
# Domyślnym projektem był `gabinet`, czyli stos DEWELOPERA. Perturbacje haseł
# wołają na nim `php artisan migrate:fresh --force` CZTERY RAZY, a `p_zdrowie`
# zatrzymuje bazę. `bramka.sh` odmawia startu na tym projekcie od początku
# („to stos dewelopera i jego danych nie kasujemy") — perturbacje brały go
# jako domyślny. Gołe `bash skrypty/perturbacje.sh` czyściło bazę dewelopera
# i tak były uruchamiane przez cały dzień pracy.
#
# Nagłówek tego pliku twierdził: „każda zmiana dotyczy plików w drzewie
# roboczym albo stanu kontenera, i każda jest cofana". `migrate:fresh` kasuje
# dane i NIE JEST cofane. Deklaracja była nieprawdziwa.
PROJEKTY_ZABRONIONE="gabinet dev"
PROJEKT="${GABINET_PERTURBACJE_PROJEKT:-gabinet-perturbacje}"

for ZABRONIONY in $PROJEKTY_ZABRONIONE; do
	if [ "$PROJEKT" = "$ZABRONIONY" ]; then
		echo "ODMOWA: perturbacje nie mogą działać na projekcie '$ZABRONIONY' — to stos dewelopera." >&2
		echo "Perturbacje wykonują 'migrate:fresh --force' i zatrzymują bazę; jego danych nie kasujemy." >&2
		echo "Użyj: GABINET_PERTURBACJE_PROJEKT=gabinet-perturbacje (domyślny) albo własnej nazwy." >&2
		exit 2
	fi
done


KOPIE="$(mktemp -d)"
UDANE=0
NIEUDANE=0
POMINIETE=0

sciezka_hosta() {
	if command -v cygpath >/dev/null 2>&1; then cygpath -w "$KORZEN/$1"; else echo "$KORZEN/$1"; fi
}

sciezka_hosta_pliku() {
	if command -v cygpath >/dev/null 2>&1; then cygpath -w "$1"; else echo "$1"; fi
}

# Własny PREFIKS nazw i własne porty — bez nich stos perturbacji zderza się
# z zasobami dewelopera PO NAZWIE mimo innego projektu, a alias `postgres`
# rozwiązuje się losowo na jeden z dwóch serwerów (ta sama pułapka, którą
# bramka zamknęła prefiksem `GABINET_PREFIX`).
# V-12 z rundy 5: domyślny port HTTP perturbacji był IDENTYCZNY z portem
# dewelopera (`.env.example: GABINET_PORT_HTTP=8098`), mimo że nagłówek
# deklarował „własne porty". PG i Redis były własne, HTTP nie.
PORT_HTTP="${GABINET_PERTURBACJE_PORT_HTTP:-8097}"
PORT_PG="${GABINET_PERTURBACJE_PORT_POSTGRES:-55444}"
PORT_REDIS="${GABINET_PERTURBACJE_PORT_REDIS:-56391}"

# Ustawiane niżej, tuż przed pierwszym użyciem `dc` — `set -u` nie wybacza.
PLIK_ENV=""

dc() {
	GABINET_PREFIX="$PROJEKT" \
	GABINET_PORT_HTTP="$PORT_HTTP" \
	GABINET_PORT_POSTGRES="$PORT_PG" \
	GABINET_PORT_REDIS="$PORT_REDIS" \
	GABINET_PLIK_ENV="$(sciezka_hosta_pliku "$PLIK_ENV")" \
		docker compose --env-file "$(sciezka_hosta_pliku "$PLIK_ENV")" \
			-p "$PROJEKT" -f "$(sciezka_hosta docker-compose.yml)" "$@"
}

# DOWÓD, że kontener ma NASZ plik — nie deklaracja, tylko porównanie treści.
#
# Sam fakt podania `--env-file` nie wystarcza: gdy stos już stoi z poprzedniego
# przebiegu, compose montuje to, co było w chwili jego utworzenia. „Podałem
# flagę" i „kontener ma mój plik" to dwa różne zdania.
#
# Porównujemy SKRÓTY, nie obecność jakiegoś klucza: skrót ma dokładnie jeden
# świat zgodny z wartością „równe". Gdy skrótu nie da się policzyć po którejś
# stronie, wynik brzmi NIEROZSTRZYGNIĘTE i traktujemy go jak niezgodność —
# kontrola bezpieczeństwa, której NIE WYKONANO, nie jest kontrolą zdaną.
srodowisko_zamontowane() {
	local na_hoscie w_kontenerze

	na_hoscie="$(sha256sum "$PLIK_ENV" 2>/dev/null | cut -d' ' -f1)"
	w_kontenerze="$(dc exec -T app sha256sum /srv/gabinet/.env 2>/dev/null | tr -d '\r' | cut -d' ' -f1)"

	if [ -z "$na_hoscie" ] || [ -z "$w_kontenerze" ]; then
		printf 'NIEROZSTRZYGNIĘTE: nie policzono skrótu pliku środowiska (host: %s, kontener: %s)\n' \
			"${na_hoscie:-brak}" "${w_kontenerze:-brak}" >&2

		return 1
	fi

	[ "$na_hoscie" = "$w_kontenerze" ]
}

# O gotowości rozstrzyga SONDA pytająca o STAN, nie kod wyjścia `up`.
czekaj_na_zdrowie() {
	local _

	for _ in $(seq 1 60); do
		if dc exec -T app php artisan gabinet:zdrowie --cichy >/dev/null 2>&1; then
			return 0
		fi

		sleep 2
	done

	echo "ODMOWA: stos '$PROJEKT' nie zgłosił zdrowia w 120 s." >&2

	return 1
}

# Mutacje plików trzymamy w `perturbuj.py`, nie w heredokach basha —
# uzasadnienie w nagłówku tamtego pliku (dwa ciche błędy ucieczki znaków
# sprawiły, że perturbacja „przechodziła", nie zmieniwszy niczego).
# `sciezka_hosta`, nie "$KORZEN/..." — przy MSYS_NO_PATHCONV=1 ścieżka POSIX
# dociera do windowsowego Pythona dosłownie i staje się `D:\d\KOD\...`.
# Perturbacja, która NIE MOŻE zawieść po cichu.
#
# Zmierzone 09.08 w nocy (N-3): `podmien()` w `perturbuj.py` poprawnie rzuca
# `SystemExit`, gdy wzorca nie ma — ale tutaj nikt nie patrzył na kod wyjścia,
# a skrypt biegnie bez `set -e`. Skutek: przemianowanie zmiennej w kodzie
# produkcyjnym (`$konta` → `$tozsamosc`, commit cdc6fbb) uczyniło DWIE
# perturbacje bezczynnymi, a scenariusze meldowały sukces.
#
# `MUTACJA_ZERWANA` niesie tę wiedzę dalej, do dowodu mutacji — inaczej dowód
# orzekałby o mutacji, której nie było.
MUTACJA_ZERWANA=0

perturbuj() {
	MUTACJA_ZERWANA=0

	if python3 "$(sciezka_hosta skrypty/perturbuj.py)" "$@"; then
		return 0
	fi

	printf '    ✗ PERTURBACJA NIE WESZŁA W ŻYCIE: %s — scenariusz NIEROZSTRZYGAJĄCY\n' "$1"
	printf '      (najczęstsza przyczyna: kod produkcyjny zmienił kształt, a wzorzec podmiany został stary)\n'
	NIEUDANE=$((NIEUDANE + 1))
	MUTACJA_ZERWANA=1

	return 1
}

# Liczba testów — DOKŁADNIE ta sama procedura co w bramce, bo ten sam plik.
. "$KORZEN/skrypty/licz-testy.sh"

# PODŁOGI — DOKŁADNIE te same wartości, które egzekwuje bramka (R6B-12).
#
# Do 12.08 perturbacje miały tu wpisane 100 testów i 300 asercji, a bramka
# egzekwowała 236/1936. Perturbacja dowodziła więc czegoś o liczbie 100
# i NIE dowodziła niczego o kontroli, którą bramka wykonuje. Rozjazd urósł po
# podniesieniu podłóg 09.08 — naprawa jednej kontroli powiększyła lukę
# w drugiej. Teraz obie strony czytają `podlogi.sh`.
. "$KORZEN/skrypty/podlogi.sh"

# REGUŁA 4 (lekcja zespołu hubu, E2E logowania): KONTROLA, KTÓRA ZMIENIA STAN,
# PSUJE GO SWOJEMU NASTĘPNEMU PRZEBIEGOWI. U nich kontrola E2E konfigurowała
# TOTP i drugi przebieg zastawał gotowe poświadczenie — czyli INNY EKRAN i inne
# zjawisko niż przebieg pierwszy. Nasze perturbacje modyfikują pliki, migracje
# i wolumen `vendor`, więc ryzyko jest dokładnie to samo.
#
# Wymóg: perturbacja zostawia repozytorium i stos w stanie ZASTANYM, a zestaw
# uznajemy za sprawny dopiero, gdy TRZY PRZEBIEGI Z RZĘDU dają ten sam wynik.
# Sprawdzenie: skrypty/perturbacje-powtarzalne.sh
#
# --- przywracanie stanu ----------------------------------------------------
# Lista plików, których kopie zrobiliśmy. `trap` przywraca je zawsze:
# przy sukcesie, przy błędzie i przy Ctrl-C.
PLIKI_DO_PRZYWROCENIA=()

zachowaj() {
	local plik="$1"
	local nazwa
	nazwa="$(printf '%s' "$plik" | tr '/' '_')"
	cp "$plik" "$KOPIE/$nazwa"
	PLIKI_DO_PRZYWROCENIA+=("$plik")
}

przywroc_wszystko() {
	local plik nazwa
	for plik in "${PLIKI_DO_PRZYWROCENIA[@]:-}"; do
		[ -n "$plik" ] || continue
		nazwa="$(printf '%s' "$plik" | tr '/' '_')"
		[ -f "$KOPIE/$nazwa" ] && cp "$KOPIE/$nazwa" "$plik"
	done
	# U-5: pliki DODANE przez perturbacje (model `Personel`, `app/Wejscie`)
	# nie mają kopii zapasowej — kopiowanie ich nie przywróci. Kasujemy je
	# tutaj, bo przerwanie skryptu zostawiało je w drzewie roboczym i kolejny
	# przebieg startował na zanieczyszczonym repozytorium.
	perturbuj hasla-sprzataj >/dev/null 2>&1 || true
	rm -rf "$KOPIE"
}

# `trap ... INT TERM` nie kończy skryptu sam z siebie — bez jawnego `exit`
# bash wraca do przerwanej instrukcji i perturbacje mielą dalej po Ctrl-C.
przerwano_perturbacje() { przywroc_wszystko; trap - EXIT; exit 130; }
# ZNACZNIK PRZEBIEGU POMIAROWEGO — dla straznika commita (R-B).
#
# To wlasnie TU 09.08 zabraklo mechanizmu: `git add -A` w trakcie tego
# zestawu wciagnal do repozytorium ZYWA PERTURBACJE reguly 24 h (N-10).
# Regula „nie commituj w trakcie przebiegu" byla zapisana w dwoch miejscach
# i egzekwowana wylacznie pamiecia — dzis egzekwuje ja straznik.
. "$KORZEN/skrypty/znacznik-przebiegu.sh"
znacznik_zaloz "zestaw perturbacji"

# ⛔ KOLEJNOSC: NAJPIERW PRZYWROCENIE, ZNACZNIK NA KONCU (zmierzone 12.08).
#
# Stalo tu `znacznik_zdejmij; przywroc_wszystko`. Roznica jest widoczna
# dopiero przy zabiciu W TRAKCIE SPRZATANIA — sekwencja SIGTERM, a zaraz po
# niej SIGKILL, ktora wysyla kazdy sensowny nadzorca procesow:
#
#   SIGTERM  -> uchwyt startuje -> znacznik ZDJETY -> SIGKILL w polowie
#               przywracania -> drzewo ZOSTAJE ZMUTOWANE, a straznik commita
#               juz NIE WIE, ze trwal przebieg pomiarowy.
#
# Zmierzone na wlasnym drzewie tego samego dnia: przerwany pelny przebieg
# zostawil w `backend/routes/web.php` ZYWA TRASE `/wejscie/zaloz` z `Hash::make`,
# a znacznika juz nie bylo. Czysty SIGTERM konczy sie poprawnie (kod 130,
# mutacja cofnieta) — czyli wada ujawnia sie WYLACZNIE w oknie miedzy
# zdjeciem znacznika a koncem przywracania.
#
# To jest ta sama mysl, ktora stoi juz w docbloku `znacznik_zdejmij`:
# straznik przestaje chronic dokladnie wtedy, gdy jest najbardziej potrzebny.
# Znacznik ma padac OSTATNI — po nim drzewo jest juz czyste.
sprzataj_po_przebiegu() { przywroc_wszystko; znacznik_zdejmij; }
przerwano_z_znacznikiem() { przywroc_wszystko; znacznik_zdejmij; trap - EXIT; exit 130; }

trap sprzataj_po_przebiegu EXIT
trap przerwano_z_znacznikiem INT TERM

# --- raportowanie ----------------------------------------------------------
naglowek() { printf '\n=== PERTURBACJA: %s\n' "$*"; }

# Wzorce awarii POBOCZNYCH — czerwień, która NIE pochodzi z badanego zjawiska.
#
# P25 (zespół helpdesku): PERTURBACJA ZALICZONA Z INNEJ PRZYCZYNY NIŻ BADANA
# TO FAŁSZYWE ZIELONE. U nich nadpisanie tematu wywaliło frazę próbkującą,
# kontrola zgłosiła „brak materiału do zbadania", a harness zaliczył to jako
# wykrycie, bo identyfikator się zgadzał.
#
# Dowód mutacji potwierdza, że mutacja WESZŁA — ale NIE, że czerwień pochodzi
# z naruszenia. To dwie różne rzeczy i przez pół sesji traktowałem je jak jedną.
#
# Poniższa lista to KLASY awarii, przy których perturbacja nie ma prawa
# zaliczyć się bez jawnej zgody: brak materiału do zbadania, niedziałające
# środowisko, błąd składni wprowadzony przez samą mutację.
AWARIE_POBOCZNE='No tests found|No tests executed|PHP Parse error|syntax error, unexpected|Class .* not found|Call to undefined (function|method)|is not running|Cannot connect to the Docker daemon|no configuration file provided|Could not open input file'

# Kontrola MUSI paść — I MUSI PAŚĆ Z BADANEGO POWODU.
#
# Trzeci argument (opcjonalny) to WZORZEC PRZYCZYNY: fragment, który musi
# wystąpić w wyjściu, żeby czerwień dała się przypisać badanemu zjawisku.
# Bez niego przyjmujemy tylko, że czerwień nie jest awarią poboczną.
oczekuj_czerwone() {
	local opis="$1"; shift
	local wzorzec=''

	# Wywołanie z wzorcem przyczyny: oczekuj_czerwone "opis" --przyczyna "wzór" polecenie…
	if [ "${1:-}" = "--przyczyna" ]; then
		wzorzec="$2"
		shift 2
	fi

	local wyjscie kod=0
	wyjscie="$(mktemp)"

	"$@" > "$wyjscie" 2>&1 || kod=$?

	if [ "$kod" -eq 0 ]; then
		printf '    ✗ %s — kontrola PRZESZŁA mimo złamanej reguły (nic nie sprawdza)\n' "$opis"
		NIEUDANE=$((NIEUDANE + 1))
		rm -f "$wyjscie"

		return
	fi

	if [ ! -s "$wyjscie" ]; then
		printf '    ✗ %s — kontrola padła MILCZĄCO (kod %s, zero wierszy) — prawdopodobnie w ogóle się nie wykonała\n' "$opis" "$kod"
		NIEUDANE=$((NIEUDANE + 1))
		rm -f "$wyjscie"

		return
	fi

	# P25: czerwień z awarii pobocznej to fałszywe zielone perturbacji.
	if grep -qE "$AWARIE_POBOCZNE" "$wyjscie"; then
		printf '    ✗ %s — czerwień z AWARII POBOCZNEJ, nie z naruszenia:\n' "$opis"
		grep -oE "$AWARIE_POBOCZNE" "$wyjscie" | head -2 | sed 's/^/        /'
		NIEUDANE=$((NIEUDANE + 1))
		rm -f "$wyjscie"

		return
	fi

	# Dopasowanie BEZ WRAŻLIWOŚCI NA WIELKOŚĆ LITER (`-i`). Powód: pierwszy
	# wzorzec, jaki tu wpisałem, brzmiał „zamrożon", a komunikat kontroli mówi
	# „ZAMROŻONĄ" — perturbacja §4 zapaliła się na czerwono z niezgodną
	# przyczyną w PIERWSZYM przebiegu. Wzorzec przepisany Z PAMIĘCI zamiast
	# skopiowany z komunikatu to ta sama klasa co „allowlista wpisana
	# z pamięci zamiast zmierzona".
	if [ -n "$wzorzec" ] && ! grep -qiE "$wzorzec" "$wyjscie"; then
		printf '    ✗ %s — czerwień NIE zawiera oczekiwanej przyczyny (%s)\n' "$opis" "$wzorzec"
		NIEUDANE=$((NIEUDANE + 1))
		rm -f "$wyjscie"

		return
	fi

	printf '    ✓ %s — kontrola zapaliła się na czerwono (kod %s)\n' "$opis" "$kod"
	UDANE=$((UDANE + 1))
	rm -f "$wyjscie"
}

# Wiek pulsu w sekundach, czytany WPROST z cache'u.
dc_wiek_pulsu() {
	dc exec -T app php artisan tinker --execute="echo time() - (int) Cache::get('gabinet:puls-harmonogramu', 0);" 2>/dev/null | tr -dc '0-9'
}
export -f dc_wiek_pulsu 2>/dev/null || true

# Dowód, że mutacja NAPRAWDĘ jest w mocy — czytany niezależnie od kontroli,
# którą za chwilę sprawdzamy. Bez tego „kontrola zapaliła się na czerwono"
# może znaczyć „coś innego poszło nie tak", a „kontrola przeszła" —
# „mutacja nigdy nie weszła w życie".
# ---------------------------------------------------------------------------
# DOWÓD ZNIKNIĘCIA — dowód mutacji Z ODCZYTEM BAZOWYM.
#
# Zastępuje formę `! grep -q 'stary tekst' plik`, która ma GAŁĄŹ ZDEGENEROWANĄ:
# wartość „prawda" jest zgodna z DWOMA światami — (I) mutacja weszła i usunęła
# tekst, (II) tekstu NIGDY TAM NIE BYŁO, bo kod przemianowano. Zmierzone 09.08:
# dwa z pięciu takich dowodów były w świecie II i zaświadczały o mutacji,
# której nie było (N-3).
#
# Odczyt bazowy bierzemy z kopii sprzed mutacji, którą i tak robi `zachowaj`.
# Pytanie brzmi więc nie „czy tekstu nie ma", tylko **„czy tekst BYŁ, a potem
# ZNIKNĄŁ"** — i to rozróżnia oba światy.
#
# Czwarty argument (opcjonalny) `linia` przełącza dopasowanie na CAŁĄ LINIĘ
# (`grep -x`). Potrzebne dla kluczy `.env.example`: wzorzec `SMSAPI_TOKEN=`
# jest PODCIĄGIEM zmutowanej linii `SMSAPI_TOKEN=cokolwiek`, więc dopasowanie
# fragmentem meldowałoby „wzorzec nadal w pliku" przy poprawnej mutacji.
dowod_zniknieciem() {
	local opis="$1" wzorzec="$2" plik="$3" tryb="${4:-fragment}"
	local kopia="$KOPIE/$(printf '%s' "$plik" | tr '/' '_')"
	local flagi='-qF'

	[ "$tryb" = "linia" ] && flagi='-qxF'

	if [ "${MUTACJA_ZERWANA:-0}" -eq 1 ]; then
		printf '    ✗ DOWÓD MUTACJI POMINIĘTY: perturbacja nie weszła w życie (%s)\n' "$opis"
		return 1
	fi

	if [ ! -f "$kopia" ]; then
		printf '    ✗ BRAK ODCZYTU BAZOWEGO: nie ma kopii %s — dowód nierozstrzygający\n' "$plik"
		NIEUDANE=$((NIEUDANE + 1))
		return 1
	fi

	# ODCZYT BAZOWY: czy wzorzec w ogóle istniał PRZED mutacją.
	if ! grep $flagi -- "$wzorzec" "$kopia"; then
		printf '    ✗ PERTURBACJA ROZJECHAŁA SIĘ Z KODEM: wzorca „%s" NIE BYŁO w %s przed mutacją\n' "$wzorzec" "$plik"
		printf '      (dowód w formie „starego tekstu już nie ma" byłby tu PRAWDZIWY BEZ MUTACJI)\n'
		NIEUDANE=$((NIEUDANE + 1))
		return 1
	fi

	if grep $flagi -- "$wzorzec" "$plik"; then
		printf '    ✗ MUTACJA NIE WESZŁA W ŻYCIE: %s — wzorzec nadal w pliku\n' "$opis"
		NIEUDANE=$((NIEUDANE + 1))
		return 1
	fi

	printf '    · dowód mutacji (był → zniknął): %s\n' "$opis"

	return 0
}

dowod_mutacji() {
	local opis="$1"; shift

	if [ "${MUTACJA_ZERWANA:-0}" -eq 1 ]; then
		printf '    ✗ DOWÓD MUTACJI POMINIĘTY: perturbacja nie weszła w życie (%s)\n' "$opis"
		return 1
	fi

	if "$@" >/dev/null 2>&1; then
		printf '    · dowód mutacji: %s
' "$opis"
		return 0
	fi

	printf '    ✗ MUTACJA NIE WESZŁA W ŻYCIE: %s — perturbacja nierozstrzygająca
' "$opis"
	NIEUDANE=$((NIEUDANE + 1))
	return 1
}

pominieta() {
	printf '    – %s (pominięta: %s)\n' "$1" "$2"
	POMINIETE=$((POMINIETE + 1))
}

# ===========================================================================
# PERTURBACJE
# ===========================================================================

p_testy() {
	naglowek "testy — wstrzyknięty błąd w regule granicznej 24 h"
	local plik="backend/app/Reguly/OcenaAnulacji.php"
	zachowaj "$plik"

	# Zmiana `>=` na `>` przesuwa granicę o jedną sekundę. To najmniejsza
	# możliwa zmiana zachowania — jeśli suita jej nie łapie, testy granicy
	# 23:59/24:00/24:01 są dekoracją.
	#
	# R6B-17: mutacja idzie przez `perturbuj.py`, nie przez `sed -i`. `sed`,
	# który nie trafił, kończy się SUKCESEM; `podmien_jedyne()` podnosi błąd
	# przy zerze i przy zwielokrotnieniu, a `perturbuj()` czyta jego kod wyjścia.
	perturbuj granica-24h

	dowod_zniknieciem 'porównanie „>=" zniknęło z reguły granicy' \
		'$sekundDoWizyty >= $sekundOkna' "$plik"

	# Allowlista to KOMUNIKAT ASERCJI z `OcenaAnulacjiTest` (dopisany 12.08),
	# a nie nazwa testu — nazwy Pest wypisuje w kazdym przebiegu, takze zielonym.
	oczekuj_czerwone "Pest wykrywa przesunięcie granicy o 1 sekundę" \
		--przyczyna "GRANICA OKNA ROZSTRZYGNIĘTA ŹLE" \
		dc exec -T app ./vendor/bin/pest --filter="granicę okna"
	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

p_pusta_suita() {
	naglowek "pusta suita — kontrola liczby wykonanych testów"
	# Dokładnie przypadek hubu: `pest` bez testów kończy się kodem 0.
	# Dowodzimy DWÓCH rzeczy naraz: że `pest` faktycznie przechodzi na pustce
	# i że nasza podłoga to łapie.
	local wynik liczba
	wynik="$(dc exec -T app ./vendor/bin/pest --filter='nazwa-ktora-nie-istnieje-nigdzie' 2>&1)"

	if printf '%s' "$wynik" | grep -qiE 'No tests found|0 tests'; then
		printf '    ✓ pusty przebieg rozpoznany (pest nie ma czego uruchomić)\n'
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ nie udało się wywołać pustego przebiegu — perturbacja nierozstrzygająca\n'
		NIEUDANE=$((NIEUDANE + 1))
	fi

	# Podłoga liczona na PRAWDZIWYM wyjściu pesta, nie na napisie ułożonym
	# w skrypcie. Pierwsza wersja tej perturbacji porównywała czysty ciąg
	# „Tests: 0 passed" — i przechodziła, podczas gdy realny parser bramki
	# gubił się na kodach ANSI i widział 0 przy 107 zielonych testach.
	# CI zapaliło się na czerwono, perturbacja nie. Perturbacja MUSI
	# obserwować dokładnie to, co obserwuje kontrola.
	liczba="$(policz_testy "$wynik")"

	# PODŁOGA CZYTANA Z `podlogi.sh` — ta sama liczba, którą egzekwuje bramka.
	if [ "$liczba" -lt "$MINIMUM_TESTOW" ]; then
		printf '    ✓ podłoga %s testów odrzuca pusty przebieg (policzono: %s)\n' "$MINIMUM_TESTOW" "$liczba"
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ podłoga nie odrzuca pustego wyniku (policzono: %s przy podłodze %s)\n' "$liczba" "$MINIMUM_TESTOW"
		NIEUDANE=$((NIEUDANE + 1))
	fi

	# Kierunek odwrotny — bez niego „0 przy pustce" przechodzi także wtedy,
	# gdy parser ZAWSZE zwraca zero. Dokładnie ten błąd popełniliśmy.
	local pelny pelna_liczba
	pelny="$(dc exec -T app ./vendor/bin/pest 2>&1)"
	pelna_liczba="$(policz_testy "$pelny")"

	if [ "$pelna_liczba" -ge "$MINIMUM_TESTOW" ]; then
		printf '    ✓ parser widzi pełny przebieg (%s testów ≥ podłoga %s) — nie zwraca zawsze zera\n' \
			"$pelna_liczba" "$MINIMUM_TESTOW"
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ parser NIE widzi pełnego przebiegu (policzył: %s przy podłodze %s)\n' \
			"$pelna_liczba" "$MINIMUM_TESTOW"
		NIEUDANE=$((NIEUDANE + 1))
	fi
}

p_statyka() {
	naglowek "statyka — wstrzyknięty błąd typu"
	local plik="backend/app/Wsparcie/Typy.php"
	zachowaj "$plik"

	# R6B-17 / N-9 — TA PODMIANA BYŁA CICHYM NO-OPEM OD NIEZNANEGO CZASU.
	# Stary `sed` szukał `string $domyslny = .."..): string`, a rzeczywista
	# sygnatura brzmi `string $domyslny = ''): string`. Zmierzone: `sed` nie
	# zmieniał ani jednego bajtu i kończył się KODEM 0. Scenariusz świecił
	# zielono wyłącznie dzięki DRUGIEJ mutacji (dopisana funkcja obok), więc
	# nikt tego nie widział.
	#
	# Obie mutacje robi teraz `perturbuj.py`; pierwsza przechodzi przez
	# `podmien_jedyne()`, które podnosi błąd, gdy wzorzec nie trafi.
	perturbuj typ-zerwany

	dowod_zniknieciem "sygnatura z domyślnym pustym napisem zniknęła z Typy.php" \
		"string \$domyslny = ''): string" "$plik"

	oczekuj_czerwone "Larastan wykrywa zwrot napisu z funkcji zwracającej int" \
		dc exec -T app ./vendor/bin/phpstan analyse --memory-limit=1G --no-progress
	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

p_format() {
	naglowek "format — złamany styl kodu"
	local plik="backend/app/Reguly/Werdykt.php"
	zachowaj "$plik"

	printf '\n\n\n   // perturbacja formatu   \n' >> "$plik"
	oczekuj_czerwone "Pint wykrywa złamany styl" \
		dc exec -T app ./vendor/bin/pint --test
	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

p_sekrety() {
	naglowek "sekrety — wartość wpisana do .env.example"
	local plik=".env.example"
	zachowaj "$plik"

	# R6B-17: mutacja przez `perturbuj.py`, dowód w formie „był → zniknął".
	# Dopasowanie CAŁEJ LINII (`linia`), bo `KEYCLOAK_CLIENT_SECRET=` jest
	# podciągiem linii zmutowanej.
	perturbuj sekret-keycloak

	dowod_zniknieciem "pusta wartość KEYCLOAK_CLIENT_SECRET zniknęła z pliku wzorcowego" \
		"KEYCLOAK_CLIENT_SECRET=" "$plik" linia

	oczekuj_czerwone "gitleaks wykrywa sekret w pliku wzorcowym" \
		docker run --rm -v "$(cygpath -w "$KORZEN" 2>/dev/null || echo "$KORZEN"):/repo" -w /repo \
			zricethezav/gitleaks:latest detect --source=/repo --config=/repo/.gitleaks.toml \
			--no-banner --redact --no-git
	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"

	# Drugi kierunek: test `SekretyTest` też musi to złapać — gitleaks patrzy
	# na kształt, test na semantykę („ta zmienna ma być PUSTA").
	perturbuj sekret-smsapi

	dowod_zniknieciem "pusta wartość SMSAPI_TOKEN zniknęła z pliku wzorcowego" \
		"SMSAPI_TOKEN=" "$plik" linia

	oczekuj_czerwone "SekretyTest wykrywa niepustą zmienną sekretną" \
		dc exec -T app ./vendor/bin/pest --filter="bez ani jednej wartości sekretu"
	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

p_hasla() {
	naglowek "CLAUDE.md §2 — pełny mechanizm haseł pod polskimi nazwami"
	# Odtworzenie ATAKU niezależnego weryfikatora. Pierwsza wersja testu
	# sprawdzała literalne nazwy i przepuściła komplet: kolumny po polsku,
	# osobną tabelę kont, tabelę resetu, drugi model dziedziczący po
	# Authenticatable i trasy w polskiej odmianie. Bramka była wtedy zielona.
	local migracja="backend/database/migrations/0001_01_01_000000_create_users_table.php"
	local trasy="backend/routes/web.php"
	zachowaj "$migracja"
	zachowaj "$trasy"

	perturbuj hasla-podloz || { echo "    nie udało się podłożyć perturbacji"; NIEUDANE=$((NIEUDANE + 1)); return; }
	dc exec -T app php artisan migrate:fresh --force >/dev/null 2>&1 || true

	# R7-8: wzorzec "BrakWlasnychHasel" był NAZWĄ KLASY, którą raporter Pest
	# wypisuje w nagłówku KAŻDEGO przebiegu (`PASS  Tests\Feature\BrakWlasnychHaselTest`
	# — zmierzone 12.08 na przebiegu ZIELONYM, nie zdedukowane z formatu).
	# Napis poniżej skopiowany DOSŁOWNIE z czerwieni tej perturbacji.
	oczekuj_czerwone "BrakWlasnychHaselTest wykrywa PEŁNY mechanizm haseł pod polskimi nazwami" \
		--przyczyna "Zbiór tabel domenowych różni się od zadeklarowanego" \
		dc exec -T app ./vendor/bin/pest tests/Feature/BrakWlasnychHaselTest.php

	perturbuj hasla-sprzataj
	cp "$KOPIE/$(printf '%s' "$migracja" | tr '/' '_')" "$migracja"
	cp "$KOPIE/$(printf '%s' "$trasy" | tr '/' '_')" "$trasy"
	dc exec -T app php artisan migrate:fresh --force >/dev/null 2>&1 || true
}

p_hasla_v2() {
	naglowek "CLAUDE.md §2 — atak RUNDY 3: nazwy spoza jakiegokolwiek wzorca"
	# `sekret_logowania`, `poswiadczenia_wejsciowe`, `pin_dostepu`,
	# `sodium_crypto_pwhash_str()` i prymityw schowany w `routes/`.
	# Wersja druga testu przepuściła to wszystko przy zielonej bramce.
	local migracja="backend/database/migrations/0001_01_01_000000_create_users_table.php"
	local trasy="backend/routes/web.php"
	zachowaj "$migracja"
	zachowaj "$trasy"

	perturbuj hasla-podloz-v2 || { echo "    nie udało się podłożyć perturbacji"; NIEUDANE=$((NIEUDANE + 1)); return; }
	dc exec -T app php artisan migrate:fresh --force >/dev/null 2>&1 || true

	# R7-8, ta sama wada co wyżej. Tu wzorzec celuje w SEDNO wariantu:
	# prymityw pod nazwą spoza jakiegokolwiek wzorca ma zostać złapany
	# allowlistą FUNKCJI, a nie listą zakazanych słów.
	oczekuj_czerwone "test §2 wykrywa mechanizm ukryty pod obcymi nazwami" \
		--przyczyna "Prymityw kryptograficzny POZA dopuszczonym zakresem: sodium_crypto_pwhash_str" \
		dc exec -T app ./vendor/bin/pest tests/Feature/BrakWlasnychHaselTest.php

	perturbuj hasla-sprzataj
	cp "$KOPIE/$(printf '%s' "$migracja" | tr '/' '_')" "$migracja"
	cp "$KOPIE/$(printf '%s' "$trasy" | tr '/' '_')" "$trasy"
	dc exec -T app php artisan migrate:fresh --force >/dev/null 2>&1 || true
}

p_nonce() {
	naglowek "nonce — fail-open kontroli bezpieczeństwa"
	local plik="backend/app/Tozsamosc/WalidatorTokenu.php"
	zachowaj "$plik"

	# Przywracamy zachowanie sprzed naprawy: kontrola MILCZY, gdy nie ma
	# z czym porównać. Weryfikator doszedł tak do stanu „zalogowany"
	# tokenem z cudzym nonce.
	perturbuj nonce-fail-open || { echo "    nie udało się podłożyć perturbacji"; NIEUDANE=$((NIEUDANE + 1)); return; }

	oczekuj_czerwone "testy wykrywają pominiętą kontrolę nonce" 		dc exec -T app ./vendor/bin/pest --filter="nonce"
	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

p_lockfile() {
	naglowek "rozjazd composer.lock — wolumen vendor nie odświeża się z obrazu"
	local plik="backend/composer.lock"
	zachowaj "$plik"

	perturbuj lockfile-rozjazd || { echo "    nie udało się podłożyć perturbacji"; NIEUDANE=$((NIEUDANE + 1)); return; }

	oczekuj_czerwone "composer validate wykrywa rozjazd lock/json" 		dc exec -T app composer validate --strict --no-check-publish

	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

# Kontrola musi paść z KONKRETNYM kodem wyjścia. „Cokolwiek niezerowego"
# przechodzi także wtedy, gdy skrypt padł z zupełnie innego powodu — i
# dokładnie tak perturbacja zamka „przechodziła" w rundzie 3.
oczekuj_kodu() {
	local opis="$1" oczekiwany="$2"; shift 2
	local kod=0

	"$@" >/dev/null 2>&1 || kod=$?

	if [ "$kod" -eq "$oczekiwany" ]; then
		printf '    ✓ %s — kod wyjścia %s, zgodnie z oczekiwaniem\n' "$opis" "$kod"
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ %s — kod wyjścia %s zamiast %s\n' "$opis" "$kod" "$oczekiwany"
		NIEUDANE=$((NIEUDANE + 1))
	fi
}

p_sonda_bazy() {
	naglowek "sonda bazy — suita przy bazie kierującej w próżnię"
	# U-3 z rundy 3: naprawa O-2 (`PGCONNECT_TIMEOUT=5` w obrazie) NIE MIAŁA
	# MIERZALNEGO EFEKTU — weryfikator zmierzył ~169 s zarówno z nią, jak i bez.
	# Perturbacja mierzy CZAS, nie sam fakt czerwieni: czerwień po 169 s w CI
	# oznacza zabity job, a zabity job nie mówi, co jest zepsute.
	#
	# Adres 10.255.255.1 nie odpowiada (czarna dziura), więc mierzymy limit
	# na gnieździe, a nie szybkość odmowy połączenia.
	local start koniec czas wynik
	start="$(date +%s)"
	wynik="$(dc exec -T -e DB_HOST=10.255.255.1 app ./vendor/bin/pest tests/Feature/SzkieletTest.php 2>&1)"
	koniec="$(date +%s)"
	czas=$((koniec - start))

	if printf '%s' "$wynik" | grep -q 'BAZA TESTOWA NIEOSIĄGALNA'; then
		printf '    · dowód mutacji: suita zgłosiła nieosiągalną bazę\n'
	else
		printf '    ✗ suita nie rozpoznała nieosiągalnej bazy — perturbacja nierozstrzygająca\n'
		NIEUDANE=$((NIEUDANE + 1))
		return
	fi

	if [ "$czas" -le 20 ]; then
		printf '    ✓ suita pada po %s s zamiast wisieć (~169 s przed naprawą)\n' "$czas"
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ suita wisiała %s s — limit na gnieździe nie działa\n' "$czas"
		NIEUDANE=$((NIEUDANE + 1))
	fi

	# Kierunek odwrotny: sonda nie może blokować przy ZDROWEJ bazie.
	if dc exec -T app ./vendor/bin/pest tests/Unit/GranicePienidzyTest.php >/dev/null 2>&1; then
		printf '    ✓ przy zdrowej bazie sonda przepuszcza suitę — nie blokuje zawsze\n'
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ sonda blokuje suitę także przy zdrowej bazie\n'
		NIEUDANE=$((NIEUDANE + 1))
	fi
}

p_vendor_niekompletny() {
	naglowek "wolumen vendor — pakiet zniknięty z dysku"
	# U-8 z rundy 3: perturbacja `lockfile` sprawdzała wyłącznie gałąź
	# `composer validate` (rozjazd content-hash). Druga gałąź kroku —
	# `composer install --dry-run` i kontrola obecności pakietów — nie miała
	# ŻADNEJ perturbacji, więc formalnie nie istniała (D-0013).
	#
	# Mutacja celuje w stan, którego system sam nie przywróci: przeniesiony
	# katalog nie wraca ani przez restart kontenera, ani przez cache Composera.
	local pakiet="nesbot/carbon"

	if ! dc exec -T app sh -c "mv /srv/gabinet/backend/vendor/${pakiet} /tmp/perturbacja-vendor" >/dev/null 2>&1; then
		pominieta "wolumen vendor — pakiet zniknięty z dysku" "nie udało się przenieść ${pakiet}"
		return
	fi

	dowod_mutacji "katalog vendor/${pakiet} zniknął z dysku" \
		dc exec -T app sh -c "[ ! -d /srv/gabinet/backend/vendor/${pakiet} ]"

	oczekuj_czerwone "kontrola obecności zależności wykrywa brak ${pakiet}" \
		dc exec -T app php /srv/gabinet/skrypty/zaleznosci-obecne.php

	# Druga gałąź tego samego kroku bramki — `install --dry-run`. Sprawdzamy ją
	# osobno, bo to osobna asercja i osobny sposób, w jaki może przestać działać.
	local suchy
	suchy="$(dc exec -T app composer install --dry-run --no-scripts 2>&1)"

	if printf '%s' "$suchy" | grep -q 'Nothing to install, update or remove'; then
		printf '    ✗ composer install --dry-run melduje „nothing to install" przy brakującym pakiecie\n'
		NIEUDANE=$((NIEUDANE + 1))
	else
		printf '    ✓ composer install --dry-run wykrywa brakujący pakiet\n'
		UDANE=$((UDANE + 1))
	fi

	dc exec -T app sh -c "mv /tmp/perturbacja-vendor /srv/gabinet/backend/vendor/${pakiet}" >/dev/null 2>&1

	# Kierunek odwrotny — po przywróceniu kontrola MUSI wrócić na zielone,
	# inaczej „czerwono przy braku" przechodzi także dla kontroli zawsze czerwonej.
	if dc exec -T app php /srv/gabinet/skrypty/zaleznosci-obecne.php >/dev/null 2>&1; then
		printf '    ✓ po przywróceniu pakietu kontrola wraca na zielone\n'
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ kontrola pozostaje czerwona mimo kompletnego vendora\n'
		NIEUDANE=$((NIEUDANE + 1))
	fi
}

p_wzmacniacz() {
	naglowek "wzmacniacz żądań — JWKS odświeżane na każdy nieznany kid"
	# Lekcja zespołu hubu: `kid` z nagłówka tokenu to dane NADAWCY żądania,
	# jeszcze niezweryfikowane. Bez bramki częstotliwości strumień tokenów
	# z losowym `kid` staje się strumieniem żądań do Kont Niepodzielni — a
	# endpoint back-channel logout jest publiczny i nieuwierzytelniony.
	local plik="backend/app/Tozsamosc/KontaOidc.php"
	zachowaj "$plik"

	perturbuj wzmacniacz-zadan

	dowod_zniknieciem "bramka częstotliwości zniknęła z kodu" \
		"KLUCZ_ODSWIEZANIE, 1" "$plik"

	oczekuj_czerwone "test liczby żądań wykrywa wzmacniacz" \
		dc exec -T app ./vendor/bin/pest tests/Feature/WzmacniaczZadanTest.php

	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

p_retencja() {
	naglowek "retencja — tabela z danymi osobowymi bez ścieżki usunięcia"
	# Lekcja zespołu helpdesku: awaria retencji działa W OBIE STRONY. Rekord,
	# którego żadne zadanie czyszczące nie wybierze, zostaje NA ZAWSZE — i nic
	# o tym nie krzyknie. Sprawdzamy oba kierunki awarii osobno.
	local plik="backend/database/migrations/9999_99_99_999999_perturbacja_retencji.php"

	cat > "$plik" <<'MIGRACJA'
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zgloszenia_pierwszego_kontaktu', function (Blueprint $table): void {
            $table->id();
            $table->string('imie');
            $table->string('telefon');
            $table->text('opis_sytuacji');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zgloszenia_pierwszego_kontaktu');
    }
};
MIGRACJA

	dowod_mutacji "migracja z nową tabelą danych osobowych leży w drzewie" \
		test -f "$plik"

	oczekuj_czerwone "rejestr retencji wykrywa tabelę bez decyzji o retencji" \
		dc exec -T app ./vendor/bin/pest tests/Feature/RetencjaTest.php

	rm -f "$plik"

	# Drugi kierunek: tabela JEST w rejestrze, ale znika kolumna, po której
	# retencja filtruje. Zadanie czyszczące nie wybrałoby wtedy ani jednego
	# rekordu — cisza, nie błąd. To trudniejszy przypadek i dlatego ważniejszy.
	# SCIEZKA POPRAWIONA 12.08 — rejestr przeniosl sie 09.08 z pliku testu
	# do `app/Retencja/RejestrRetencji.php`, a perturbacja dalej mutowala
	# STARE miejsce. Wbudowany `str.replace` konczy sie sukcesem takze przy
	# ZERZE trafien, wiec scenariusz zaliczal sie nie zmieniwszy niczego —
	# ta sama klasa co surowy `sed`, tylko w Pythonie. Ponizej podmiana
	# PRZERYWA BLEDEM, gdy wzorca nie ma albo jest wiecej niz raz.
	local rejestr="backend/app/Retencja/RejestrRetencji.php"
	zachowaj "$rejestr"

	python3 - "$rejestr" <<'PYTON'
import io, sys
p = sys.argv[1]
s = io.open(p, encoding='utf-8').read()
_szukane = "            'pacjenci' => [\n                'kolumna_pochodzenia' => 'created_at',"
_ile = s.count(_szukane)
if _ile != 1:
    sys.exit("PERTURBACJA NIEUDANA: wzorzec rejestru wystepuje %d razy w %s" % (_ile, p))
s = s.replace(
    "            'pacjenci' => [\n                'kolumna_pochodzenia' => 'created_at',",
    "            'pacjenci' => [\n                'kolumna_pochodzenia' => 'kolumna_ktorej_nie_ma',",
    1,
)
io.open(p, 'w', encoding='utf-8', newline='\n').write(s)
PYTON

	dowod_mutacji "rejestr wskazuje nieistniejącą kolumnę pochodzenia" \
		grep -q "kolumna_ktorej_nie_ma" "$rejestr"

	oczekuj_czerwone "kontrola wykrywa rekord bez pola, po którym retencja filtruje" \
		dc exec -T app ./vendor/bin/pest tests/Feature/RetencjaTest.php

	cp "$KOPIE/$(printf '%s' "$rejestr" | tr '/' '_')" "$rejestr"
}

p_suita_pominieta() {
	naglowek "podłoga testów — CAŁA suita pominięta"
	# W-4 z rundy 4: naprawa U-6 („sumuj wszystkie stany") otworzyła tę samą
	# awarię z drugiej strony. Weryfikator jednym `beforeEach` doprowadził do
	# „Tests: 151 skipped (0 assertions)" i `BRAMKA OK — 0 nieudanych`.
	# Zero wykonanych testów, zero asercji, bramka zielona — czyli DOKŁADNIE
	# awaria, przed którą ten krok deklaruje ochronę.
	local plik="backend/tests/Pest.php"
	zachowaj "$plik"

	perturbuj suita-pominieta

	local wynik wykonane pominiete asercje
	wynik="$(dc exec -T app ./vendor/bin/pest 2>&1)"
	wykonane="$(policz_testy "$wynik")"
	pominiete="$(policz_pominiete "$wynik")"
	asercje="$(policz_asercje "$wynik")"

	perturbuj suita-pominieta-sprzataj
	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"

	# Dowód mutacji, nie podłoga: pytamy, czy mutacja NAPRAWDĘ pominęła suitę.
	# Progiem jest ta sama podłoga, bo „pominiętych mniej niż podłoga testów"
	# znaczy, że `beforeEach` nie objął całej suity — czyli mierzymy nie to
	# zjawisko.
	if [ "$pominiete" -lt "$MINIMUM_TESTOW" ]; then
		printf '    ✗ mutacja nie pominęła suity (pominiętych: %s przy podłodze %s) — perturbacja nierozstrzygająca\n' \
			"$pominiete" "$MINIMUM_TESTOW"
		NIEUDANE=$((NIEUDANE + 1))
		return
	fi

	printf '    · dowód mutacji: pest zgłasza %s pominiętych testów\n' "$pominiete"

	# Pierwszy sygnał: pominięty test NIE JEST wykonanym testem.
	if [ "$wykonane" -eq 0 ]; then
		printf '    ✓ podłoga liczy 0 wykonanych testów — pominięte się nie liczą\n'
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ podłoga zaliczyła %s pominiętych testów jako wykonane\n' "$wykonane"
		NIEUDANE=$((NIEUDANE + 1))
	fi

	# Drugi, niezależny sygnał: suita bez asercji niczego nie dowiodła.
	if [ "$asercje" -eq 0 ]; then
		printf '    ✓ podłoga asercji widzi 0 sprawdzeń — drugi sygnał też czerwony\n'
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ licznik asercji pokazuje %s przy pominiętej suicie\n' "$asercje"
		NIEUDANE=$((NIEUDANE + 1))
	fi

	# Kierunek odwrotny: na zdrowej suicie oba liczniki muszą wrócić wysoko,
	# inaczej „zero przy pominięciu" przechodzi także dla licznika zawsze zerowego.
	local zdrowy
	zdrowy="$(dc exec -T app ./vendor/bin/pest 2>&1)"

	# OBIE podłogi z `podlogi.sh` — te same, które egzekwuje bramka (R6B-12).
	if [ "$(policz_testy "$zdrowy")" -ge "$MINIMUM_TESTOW" ] \
		&& [ "$(policz_asercje "$zdrowy")" -ge "$MINIMUM_ASERCJI" ]; then
		printf '    ✓ na zdrowej suicie oba liczniki wracają nad podłogi %s/%s (%s testów, %s asercji)\n' \
			"$MINIMUM_TESTOW" "$MINIMUM_ASERCJI" "$(policz_testy "$zdrowy")" "$(policz_asercje "$zdrowy")"
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ liczniki nie wracają nad podłogi %s/%s na zdrowej suicie (%s testów, %s asercji)\n' \
			"$MINIMUM_TESTOW" "$MINIMUM_ASERCJI" "$(policz_testy "$zdrowy")" "$(policz_asercje "$zdrowy")"
		NIEUDANE=$((NIEUDANE + 1))
	fi
}

p_obietnica() {
	naglowek "obietnica w komentarzu bez testu, który ją nazywa"
	# Rdzeń „podejrzewaj przyrząd", dopełnienie: DOKUMENTACJA O KODZIE TEŻ JEST
	# PRZYRZĄDEM I TEŻ KŁAMIE — ciszej niż kod, bo nikt jej nie uruchamia.
	# W jednej partii napraw złapano u nas trzy takie obietnice, wszystkie
	# nieprawdziwe wobec kodu: „każda zmiana jest cofana" (W-11),
	# „PHP_INT_MAX obsłużony" (W-5), „najpierw zatrzymujemy harmonogram" (W-15).
	local plik="backend/app/Reguly/OcenaAnulacji.php"
	zachowaj "$plik"

	perturbuj obietnica-bez-dowodu

	dowod_mutacji "kod produkcyjny powołuje się na naprawę W-777" \
		grep -q "W-777" "$plik"

	oczekuj_czerwone "kontrola wykrywa obietnicę bez testu, który ją nazywa" \
		dc exec -T app ./vendor/bin/pest tests/Feature/ObietniceKomentarzyTest.php

	perturbuj obietnica-sprzataj
	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"

	# Kierunek odwrotny: bez obietnicy kontrola wraca na zielone.
	if dc exec -T app ./vendor/bin/pest tests/Feature/ObietniceKomentarzyTest.php >/dev/null 2>&1; then
		printf '    ✓ po usunięciu obietnicy kontrola wraca na zielone\n'
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ kontrola pozostaje czerwona mimo braku obietnicy bez dowodu\n'
		NIEUDANE=$((NIEUDANE + 1))
	fi
}

p_sesja_jawna() {
	naglowek "magazyn sesji — dane osobowe bez szyfrowania (dwie nogi)"
	# Kontrola krzyżowa B7 z weryfikacji F1 huba, zmierzona u nas i potwierdzona:
	# sesja trzyma e-mail, login i CAŁY ID token, sterownik `redis` utrwala dane
	# na dysku, a `SESSION_ENCRYPT` miało domyślnie `false`.
	#
	# R6B-4 — SCENARIUSZ ZALICZAŁ SIĘ Z INNEJ PRZYCZYNY, NIŻ DEKLAROWAŁ.
	#
	# Miał JEDNĄ nogę: podmieniał WARTOŚĆ DOMYŚLNĄ w `config/session.php`
	# i oczekiwał czerwieni od „testu, który znajduje e-mail JAWNIE w Redisie".
	# Tyle że o zachowaniu przebiegu rozstrzyga `SESSION_ENCRYPT` z pliku
	# środowiska, a ten ma `true` (`.env.example:59`). Mutacja wartości
	# domyślnej NIE ZMIENIA więc ani jednego bajtu w Redisie — czerwień, jeśli
	# przychodziła, to z kontroli TEKSTOWEJ czytającej treść pliku, czyli
	# z innego zjawiska niż nazwane w opisie.
	#
	# Rozdzielamy więc dwa RÓŻNE pytania i dowodzimy ich osobno:
	#   NOGA 1 — „czy jest bezpiecznie DOMYŚLNIE" (mutacja wartości domyślnej),
	#   NOGA 2 — „czy jest bezpiecznie TUTAJ"     (mutacja środowiska przebiegu).
	local plik="backend/config/session.php"
	zachowaj "$plik"

	# --- NOGA 1: wartość domyślna w kodzie -----------------------------------
	perturbuj sesja-jawna

	dowod_zniknieciem "domyślne szyfrowanie sesji zniknęło z konfiguracji" \
		"'encrypt' => env('SESSION_ENCRYPT', true)," "$plik"

	# ALLOWLISTA = KOMUNIKAT ASERCJI, skopiowany dosłownie z
	# `backend/tests/Feature/SesjaBezJawnychDanychTest.php` („ma szyfrowanie
	# sesji włączone DOMYŚLNIE"). To jest kontrola, która realnie reaguje na tę
	# mutację — i dlatego opis mówi teraz o wartości domyślnej, a nie o Redisie.
	oczekuj_czerwone "kontrola wykrywa wyłączone szyfrowanie DOMYŚLNE (treść pliku, nie env)" \
		--przyczyna "Wartość DOMYŚLNA szyfrowania sesji nie jest" \
		dc exec -T app ./vendor/bin/pest tests/Feature/SesjaBezJawnychDanychTest.php

	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"

	# --- NOGA 2: to, co realnie rozstrzyga w środowisku przebiegu ------------
	#
	# Bez mutacji pliku: wstrzykujemy `SESSION_ENCRYPT=false` do środowiska
	# procesu. Ta sama droga, którą `p_sonda_bazy` podstawia `DB_HOST` —
	# zmienna procesu trafia do `$_SERVER`, a ten adapter ma w Laravelu
	# pierwszeństwo przed `.env`.
	local stan_szyfrowania
	stan_szyfrowania="$(dc exec -T -e SESSION_ENCRYPT=false app \
		php artisan tinker --execute="echo config('session.encrypt') ? 'TAK' : 'NIE';" 2>/dev/null \
		| tr -d '[:space:]')"

	if [ "$stan_szyfrowania" = "NIE" ]; then
		printf '    · dowód mutacji: w środowisku przebiegu szyfrowanie sesji jest WYŁĄCZONE\n'

		# Komunikat asercji z pierwszego testu tego pliku — czerwień pochodzi
		# wtedy z ODCZYTANIA e-maila w magazynie, a nie z treści pliku.
		oczekuj_czerwone "kontrola znajduje e-mail pacjenta w magazynie, gdy środowisko wyłącza szyfrowanie" \
			--przyczyna "E-mail pacjenta odczytywalny w magazynie sesji" \
			dc exec -T -e SESSION_ENCRYPT=false app ./vendor/bin/pest tests/Feature/SesjaBezJawnychDanychTest.php
	else
		printf '    ✗ MUTACJA NIE WESZŁA W ŻYCIE: config(session.encrypt) daje „%s" mimo SESSION_ENCRYPT=false\n' \
			"${stan_szyfrowania:-brak odczytu}"
		printf '      (bez tego odczytu noga 2 mierzyłaby nie to zjawisko — patrz R6B-4)\n'
		NIEUDANE=$((NIEUDANE + 1))
	fi

	# Kierunek odwrotny: bez mutacji i bez wstrzyknięcia kontrola MUSI być
	# zielona, inaczej „czerwono przy mutacji" przechodzi także dla kontroli
	# zawsze czerwonej.
	if dc exec -T app ./vendor/bin/pest tests/Feature/SesjaBezJawnychDanychTest.php >/dev/null 2>&1; then
		printf '    ✓ po przywróceniu szyfrowania kontrola wraca na zielone\n'
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ kontrola pozostaje czerwona mimo włączonego szyfrowania\n'
		NIEUDANE=$((NIEUDANE + 1))
	fi
}

p_role_zamrozone() {
	naglowek "role zamrożone na całą sesję — odebranie roli nie działa"
	# Standard B8: ról NIE WOLNO zamrażać na całą sesję. Przed tą zmianą role
	# szły do sesji przy logowaniu i nie zmieniały się przez 120 minut, a sesja
	# odnawiała się ruchem — dla aktywnego użytkownika odebranie roli
	# w Keycloaku nie działało NIGDY.
	local plik="backend/app/Tozsamosc/OdswiezanieSesji.php"
	zachowaj "$plik"

	perturbuj role-zamrozone

	dowod_zniknieciem "sprawdzanie wieku access tokenu zniknęło z kodu" \
		'if (! $this->wymagaOdswiezenia($tozsamosc)) {' "$plik"

	# Allowlista to KOMUNIKAT ASERCJI z `OdebranieRoliTest` (dopisany 12.08).
	# Ten test nie mial wczesniej ANI JEDNEGO komunikatu, wiec nie bylo z czego
	# zbudowac zawezenia — to bylo sedno dlugu, nie jego objaw.
	oczekuj_czerwone "test wykrywa, że odebranie roli nie dociera do aplikacji" --przyczyna "ROLE ZAMROŻONE NA CAŁĄ SESJĘ" \
		dc exec -T app ./vendor/bin/pest tests/Feature/OdebranieRoliTest.php --filter="odbiera dostęp"

	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

p_logout_failsafe() {
	naglowek "wylogowanie bez klauzuli fail-safe — 500 z żywą sesją"
	# Klauzula fail-safe logout (standard B8, znalezisko sesji `konta`):
	# handler pobiera JWKS, więc sięga po sieć. Bez tej gałęzi niedostępny IdP
	# dawał kod 500, a SESJA ŻYŁA DALEJ — w ciszy. Dokładnie ten tryb awarii,
	# który back-channel logout ma eliminować.
	local plik="backend/app/Http/Controllers/BackchannelLogoutController.php"
	zachowaj "$plik"

	perturbuj logout-bez-failsafe

	dowod_zniknieciem "awaryjne zakończenie sesji zniknęło z handlera" \
		'} catch (Throwable $blad) {' "$plik"

	# ⚠ DWIE WADY NARAZ, obie do naprawy poza tym plikiem:
	#
	# 1. R6B-15: „niedostępnym IdP" to NAZWA TESTU, więc allowlista nie zawęża
	#    niczego, a ten test nie ma komunikatu przy pierwszej asercji
	#    (`expect($odp->status())->toBe(200)`).
	# 2. GROŹNIEJSZE — MUTACJA NIE MA WPŁYWU NA WYBRANY TEST. Zmierzone
	#    czytaniem kodu 12.08: test „kończy sesję przy niedostępnym IdP, gdy
	#    klucze są w cache" ASERUJE `SladWylogowania::awarie() === 0`, czyli
	#    jawnie NIE wchodzi w blok `catch`. Usunięcie `catch` nie zmienia więc
	#    w nim niczego. Ścieżkę awaryjną wykonują testy „NIE kasuje sesji, gdy
	#    podpisu nie da się sprawdzić" oraz „ADWERSARIALNY". Filtra NIE
	#    przestawiam, bo nie mogę tu uruchomić perturbacji, a przestawienie bez
	#    pomiaru byłoby zgadywaniem — propozycja jest w raporcie.
	# PRZECELOWANE 12.08 — poprzedni cel NIE WCHODZIL w mutowany blok.
	#
	# Celowalismy w test o niedostepnym IdP przy kluczach w cache. Ten test
	# JAWNIE asertuje, ze licznik awarii wynosi ZERO — czyli deklaruje, ze
	# w blok ratunkowy NIE WCHODZI: przy znanym kid klucze ida z cache,
	# a discovery nie jest wolane. Usuniecie bloku nie zmienialo w nim NICZEGO,
	# wiec czerwien musiala przyjsc skadinad — perturbacja zaliczala sie
	# z innej przyczyny niz badana (P25).
	#
	# Blok ratunkowy wykonuje test o niesprawdzalnym podpisie (kasuje discovery
	# ORAZ jwks) i to jest wlasciwy cel. Allowlista to komunikat jego asercji.
	oczekuj_czerwone "test wykrywa konsumenta kasujacego sesje bez weryfikacji podpisu" \
		--przyczyna "FAIL-SAFE WYLOGOWANIA ZDJETY" \
		dc exec -T app ./vendor/bin/pest tests/Feature/OdebranieRoliTest.php --filter="podpisu nie da"

	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

p_zrodlo_rol() {
	naglowek "źródło ról — odczyt z ID tokenu zamiast z access tokenu"
	# Wyostrzenie B2 (wzorzec huba): asercja „role == [koordynator]" przechodzi
	# TAKŻE przy odczycie ze złego źródła, o ile fixtura każe obu źródłom mówić
	# to samo. Test ma pytać „Z KTÓREGO ŹRÓDŁA", nie „czy role są" — a tego
	# dowodzi wyłącznie perturbacja podmieniająca źródło.
	local plik="backend/app/Http/Controllers/LogowanieController.php"
	zachowaj "$plik"

	perturbuj role-ze-zlego-zrodla

	dowod_mutacji "wszystkie trzy odczyty ról idą teraz z ID tokenu" \
		bash -c "[ \"\$(grep -c 'roleZAccessTokenu(\$claimsId)' '$plik')\" = '3' ]"

	# DŁUG SPŁACONY 12.08 (był R6B-15, doliczony ponownie w R7-8). Pierwsza
	# asercja testu dostała komunikat, więc wzorzec przestał być nazwą testu.
	oczekuj_czerwone "test wykrywa role czytane ze złego źródła" \
		--przyczyna "Zrodlo rol INNE niz access token" \
		dc exec -T app ./vendor/bin/pest tests/Feature/OdebranieRoliTest.php --filter="ACCESS TOKENU"

	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

p_wymuszone_wylogowanie() {
	naglowek "wymuszone wylogowanie — sesja kończona po NIEZWERYFIKOWANYM sid"
	# Pytanie adwersarialne do klauzuli fail-safe: skoro ścieżka awaryjna kończy
	# sesje, to czy nie jest narzędziem WYMUSZONEGO WYLOGOWANIA? Pierwsza wersja
	# klauzuli działała na `sid` z niezweryfikowanego tokenu, a napastnik
	# CZĘŚCIOWO kontroluje wyzwalacz wyjątku — `kid` pochodzi z jego tokenu.
	local plik="backend/app/Http/Controllers/BackchannelLogoutController.php"
	zachowaj "$plik"

	perturbuj logout-niezweryfikowany-sid

	dowod_mutacji "handler znowu kończy sesję po niezweryfikowanym sid" \
		grep -q "RejestrSesji::zakoncz(WalidatorTokenu::sidNiezweryfikowany" "$plik"

	oczekuj_czerwone "test adwersarialny wykrywa wymuszone wylogowanie ofiary" \
		--przyczyna "WYMUSZONE WYLOGOWANIE" \
		dc exec -T app ./vendor/bin/pest tests/Feature/OdebranieRoliTest.php --filter="ADWERSARIALNY"

	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

p_id_token_w_sesji() {
	naglowek "ID token w sesji — jawny oraz tylko zakodowany"
	# Refinement B7 z huba, obie nogi naraz.
	#
	# NOGA 1: ID token zapisany JAWNIE. Poleganie na `SESSION_ENCRYPT` to jedna
	# flaga od wycieku, a claim `email` jest daną pacjenta (RODO art. 9).
	#
	# NOGA 2 — trudniejsza i dlatego ważniejsza: ID token tylko ZAKODOWANY.
	# Zapisana wartość różni się od oryginału, więc asercja „nie równa się"
	# przechodzi, a e-mail jest w pełni odzyskiwalny. Ta noga rozstrzyga, czy
	# kontrola pyta o ODZYSKIWALNOŚĆ DANYCH, czy tylko o różnicę napisów.
	local plik="backend/app/Http/Controllers/LogowanieController.php"
	zachowaj "$plik"

	perturbuj id-token-jawny

	dowod_zniknieciem "kontroler zapisuje ID token bez szyfrowania" \
		'Crypt::encryptString($idToken)' "$plik"

	# ALLOWLISTA = KOMUNIKAT ASERCJI (R6B-15). „ZASZYFROWANY" było NAZWĄ TESTU
	# i zarazem wartością `--filter`, więc spełniało się przez sam fakt
	# uruchomienia testu — także w przebiegu zielonym. Fragment poniżej
	# skopiowany DOSŁOWNIE z `OdebranieRoliTest`: to asercja, która pada
	# dokładnie przy tej mutacji (ID token zapisany bez szyfrowania).
	oczekuj_czerwone "kontrola wykrywa ID token zapisany JAWNIE" \
		--przyczyna "ID token zapisany JAWNIE w sesji" \
		dc exec -T app ./vendor/bin/pest tests/Feature/OdebranieRoliTest.php --filter="ZASZYFROWANY"

	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"

	perturbuj id-token-zakodowany

	dowod_mutacji "kontroler koduje ID token zamiast go szyfrować" \
		grep -q "base64_encode(\$idToken)" "$plik"

	# DŁUG SPŁACONY 12.08 — i spłaciła go naprawa R7-4, nie zmiana tutaj.
	#
	# Do 12.08 stały tu DWIE wady naraz. Pierwsza: „ZASZYFROWANY" to nazwa
	# testu i zarazem wartość `--filter`, więc jako allowlista nie zawężała
	# niczego (R6B-15). Druga, GORSZA: właściwa asercja NIE ZAPALAŁA SIĘ
	# wcale — `zdekodowaneLadunki()` przerywała po pierwszej warstwie base64,
	# a `base64_encode($idToken)` chowa JWT o warstwę głębiej. Czerwień
	# przychodziła z wyjątku deszyfrowania, czyli z innego ogniwa (P25).
	#
	# Wzorca nie dało się wtedy poprawić: nie miał w co celować. Naprawił to
	# R7-4 (rekurencja w `backend/tests/Pest.php`). ZMIERZONE po naprawie —
	# ta sama mutacja, ta sama komenda:
	#
	#   ⨯ zapisuje ID token do sesji ZASZYFROWANY …
	#   E-mail pacjenta odzyskiwalny z zapisanego ID tokenu.
	#
	# Czerwień pochodzi wreszcie z badanego ogniwa i wzorzec ją nazywa.
	oczekuj_czerwone "kontrola wykrywa ID token TYLKO ZAKODOWANY — po zdekodowaniu, nie po różnicy napisów" \
		--przyczyna "E-mail pacjenta odzyskiwalny z zapisanego ID tokenu" \
		dc exec -T app ./vendor/bin/pest tests/Feature/OdebranieRoliTest.php --filter="ZASZYFROWANY"

	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

p_retencja_wykonanie() {
	naglowek "retencja — zadanie WYBIERA rekordy, ale ich NIE KASUJE"
	# Lekcja przekrojowa zespołu helpdesku: kontrole retencji sprawdzają zwykle,
	# KOGO zadanie wybiera do skasowania — a nie czy rekord realnie ZNIKA.
	# RODO wymaga WYKONANIA, nie selekcji: poprawnie wybrany rekord, którego
	# nikt nie skasował, to dane pozostawione po terminie, w ciszy.
	#
	# Mutacja zostawia selekcję nietkniętą i usuwa samo kasowanie. Zmierzone:
	# strukturalny `RetencjaTest` (rejestr, kolumna pochodzenia, sposób
	# usunięcia) POZOSTAJE W CAŁOŚCI ZIELONY — czerwień zapala wyłącznie
	# kontrola wykonania. To jest dowód, że selekcja i wykonanie to dwie
	# różne rzeczy i że tylko jedna z nich była dotąd pilnowana.
	local plik="backend/app/Retencja/ZadanieRetencji.php"
	zachowaj "$plik"

	perturbuj retencja-bez-kasowania

	dowod_mutacji "kasowanie zniknęło z zadania, selekcja została" \
		bash -c "grep -q 'kasowanie usuniete, selekcja zostaje' '$plik' && grep -q 'pluck(' '$plik'"

	oczekuj_czerwone "kontrola wykrywa rekord, który PRZEŻYŁ zadanie retencyjne" \
		--przyczyna "PRZEŻYŁ zadanie retencyjne" \
		dc exec -T app ./vendor/bin/pest tests/Feature/RetencjaWykonanieTest.php

	# Kontrola strukturalna NIE MOŻE tego złapać — i to też trzeba pokazać,
	# bo inaczej nikt nie uwierzy, że potrzebne są obie.
	if dc exec -T app ./vendor/bin/pest tests/Feature/RetencjaTest.php >/dev/null 2>&1; then
		printf '    ✓ rejestr retencji pozostaje zielony — selekcja to NIE wykonanie\n'
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ rejestr retencji zapalił się z innego powodu — perturbacja nierozstrzygająca\n'
		NIEUDANE=$((NIEUDANE + 1))
	fi

	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

p_uniewaznienie_sid() {
	naglowek "BLK-22 — unieważnienie sesji po sid, odporne na rotację identyfikatora"
	# Defekt WZORCA, potwierdzony w ekosystemie: back-channel logout zamyka
	# sesję po stronie IdP, a KONSUMENT DALEJ SERWUJE.
	#
	# Zmierzone u nas: `RejestrSesji` zapamiętuje POPRAWNIE identyfikator sesji
	# z chwili logowania, ale ten identyfikator ROTUJE przy kolejnych żądaniach
	# (`Set-Cookie` z dwóch kolejnych odpowiedzi: A ≠ B przy zachowanej
	# tożsamości). Wylogowanie kasowało więc wpis, którego nikt już nie używa.
	#
	# Mutacja zdejmuje sprawdzanie unieważnienia po `sid` — czyli jedyny
	# mechanizm odporny na tę rotację.
	local plik="backend/app/Tozsamosc/OdswiezanieSesji.php"
	# ZACHOWUJEMY OBA PLIKI, bo mutacja dotyka OBU (12.08).
	#
	# Rozszerzylem mutacje na middleware i NIE rozszerzylem zachowania —
	# zestaw zostawil ZYWA MUTACJE w drzewie roboczym. To jest mechanizm
	# N-10: zepsuta regula bezpieczenstwa czekajaca na commit. Zlapane
	# przegladem stanu drzewa po przebiegu, a nie kontrola — czyli
	# przypadkiem, dokladnie tak samo jak wtedy.
	local middleware="backend/app/Http/Middleware/SprawdzUniewaznienie.php"
	zachowaj "$plik"
	zachowaj "$middleware"

	perturbuj uniewaznienie-po-sid

	dowod_zniknieciem "sprawdzanie unieważnienia po sid zniknęło z kodu" \
		"RejestrSesji::uniewazniona" "$plik"

	# ALLOWLISTA przyczyny, nie podłoga: to tożsamość, więc fałszywe zielone
	# kosztuje tu najwięcej. Fragment skopiowany DOSŁOWNIE z komunikatu Pesta.
	# PRZECELOWANE 12.08 na SWIADKA MECHANIZMU, nie na test POZYTYWNY.
	#
	# Test POZYTYWNY dostal tego dnia pelna granice procesu i jawne ciasteczko
	# (naprawa R6A-1/R6B-2). Skutek uboczny: jego 401 pochodzi teraz ze
	# SKASOWANEJ SESJI, bo kasowanie po zapamietanym identyfikatorze TRAFIA —
	# klient niesie dokladnie ten identyfikator. Zdjecie znacznika po `sid`
	# przestalo byc w nim widoczne; zmierzone pelnym przebiegiem.
	#
	# Sedno BLK-22 zachodzi dopiero po ROTACJI identyfikatora: rejestr zna
	# stary, kasowanie chybia, ratuje wylacznie znacznik po `sid`.
	oczekuj_czerwone "swiadek BLK-22 wykrywa konsumenta serwujacego po wylogowaniu" \
		--przyczyna "KONSUMENT SERWUJE PO WYLOGOWANIU" \
		dc exec -T app ./vendor/bin/pest tests/Feature/OdebranieRoliTest.php --filter="BLK-22 SEDNO"

	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
	cp "$KOPIE/$(printf '%s' "$middleware" | tr '/' '_')" "$middleware"

	# Kierunek odwrotny: po przywróceniu kontrola wraca na zielone.
	if dc exec -T app ./vendor/bin/pest tests/Feature/OdebranieRoliTest.php >/dev/null 2>&1; then
		printf '    ✓ po przywróceniu unieważnienia po sid kontrola wraca na zielone\n'
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ kontrola pozostaje czerwona mimo przywróconego mechanizmu\n'
		NIEUDANE=$((NIEUDANE + 1))
	fi
}

p_zamek() {
	naglowek "zamek bramki — drugi równoległy przebieg"
	# O-5: dwa przebiegi mielą jedną bazę `gabinet_test`. Sprawdzamy, że drugi
	# ODMAWIA startu, zamiast produkować fałszywe czerwone.
	#
	# U-2 z rundy 3: ta perturbacja składała ścieżkę zamka RĘCZNIE i składała
	# ją inaczej niż bramka (`gabinet-bramka-perturbacja.zamek` wobec
	# `gabinet-bramka-gabinet-bramka-perturbacja.zamek`). Zajmowała więc plik,
	# o który nikt nie pytał, bramka szła dalej i padała z innego powodu —
	# a perturbacja meldowała sukces. Teraz ścieżkę podaje SAMA BRAMKA.
	local projekt="gabinet-bramka-perturbacja"
	local zamek
	zamek="$(bash "$KORZEN/skrypty/bramka.sh" --projekt "$projekt" --pokaz-zamek)"

	if [ -z "$zamek" ]; then
		printf '    ✗ bramka nie potrafi podać ścieżki zamka — perturbacja nierozstrzygająca\n'
		NIEUDANE=$((NIEUDANE + 1))
		return
	fi

	printf '    · zajmuję zamek wskazany przez bramkę: %s\n' "$zamek"
	rm -rf "$zamek" "$zamek.przejecie"
	mkdir -p "$zamek"
	# PID ŻYWEGO procesu (własny) — inaczej bramka słusznie przejmie zamek
	# po nieboszczyku i perturbacja zmierzy nie to zjawisko.
	echo "$$" > "$zamek/pid"

	dowod_mutacji "zamek istnieje i wskazuje żywy proces $$" \
		bash -c "[ -d '$zamek' ] && kill -0 \"\$(cat '$zamek/pid')\"" || { rm -rf "$zamek"; return; }

	oczekuj_kodu "bramka odmawia startu przy zajętym zamku" 3 \
		bash "$KORZEN/skrypty/bramka.sh" --projekt "$projekt" --tylko-kod

	# Kierunek odwrotny: bez niego „kod 3" przechodzi także wtedy, gdyby
	# bramka zwracała 3 zawsze. Po zwolnieniu zamka MUSI ruszyć dalej —
	# a skoro stos tego projektu nie stoi, padnie na czymś innym niż 3.
	rm -rf "$zamek"

	local kod=0
	bash "$KORZEN/skrypty/bramka.sh" --projekt "$projekt" --tylko-kod >/dev/null 2>&1 || kod=$?

	if [ "$kod" -ne 3 ]; then
		printf '    ✓ po zwolnieniu zamka bramka rusza dalej (kod %s ≠ 3) — nie odmawia zawsze\n' "$kod"
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ bramka odmawia startu także przy WOLNYM zamku — kontrola zawsze na czerwono\n'
		NIEUDANE=$((NIEUDANE + 1))
	fi

	rm -rf "$zamek" "$zamek.przejecie"
}

p_licznik_testow() {
	naglowek "licznik testów — przebieg z jednym niezaliczonym testem"
	# U-6 z rundy 3: parser wyłuskiwał liczbę tuż przed słowem „passed"
	# licząc od „Tests:". Przy „Tests: 1 failed, 135 passed" nie trafiał
	# w nic i zwracał ZERO — czyli podłoga meldowała „suita się nie
	# uruchomiła", choć uruchomiła się w komplecie. Diagnoza szła w las.
	#
	# Perturbacja celuje w PRAWDZIWE wyjście pesta z realnie zepsutym testem,
	# nie w napis ułożony w skrypcie — ten błąd popełniliśmy już raz.
	local plik="backend/app/Reguly/OcenaAnulacji.php"
	zachowaj "$plik"

	perturbuj granica-24h

	dowod_zniknieciem 'porównanie „>=" zniknęło z reguły granicy' \
		'$sekundDoWizyty >= $sekundOkna' "$plik"

	local wynik liczba
	wynik="$(dc exec -T app ./vendor/bin/pest 2>&1)"
	liczba="$(policz_testy "$wynik")"

	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"

	if printf '%s' "$wynik" | grep -qE 'failed'; then
		printf '    · dowód mutacji: pest zgłasza niezaliczone testy\n'
	else
		printf '    ✗ mutacja nie wywołała ani jednego niezaliczonego testu — perturbacja nierozstrzygająca\n'
		NIEUDANE=$((NIEUDANE + 1))
		return
	fi

	if [ "$liczba" -ge "$MINIMUM_TESTOW" ]; then
		printf '    ✓ licznik widzi %s wykonanych testów mimo niezaliczonych (podłoga %s) — podłoga nie kłamie\n' \
			"$liczba" "$MINIMUM_TESTOW"
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ licznik zgubił się na wyniku z niezaliczonymi testami (policzył: %s przy podłodze %s)\n' \
			"$liczba" "$MINIMUM_TESTOW"
		NIEUDANE=$((NIEUDANE + 1))
	fi
}

p_zdrowie() {
	naglowek "sonda zdrowia — zatrzymana baza"
	# Kluczowa perturbacja: pierwsza wersja sondy sprawdzała `extension_loaded()`
	# i meldowała „healthy" przy martwej aplikacji.
	dc stop postgres >/dev/null 2>&1

	oczekuj_czerwone "gabinet:zdrowie wykrywa niedostępną bazę" \
		dc exec -T app php artisan gabinet:zdrowie --cichy

	dc start postgres >/dev/null 2>&1
	# Czekamy na powrót bazy, żeby kolejne perturbacje nie startowały w ruinie.
	for _ in $(seq 1 30); do
		dc exec -T app php artisan gabinet:zdrowie --cichy >/dev/null 2>&1 && break
		sleep 2
	done
}

p_tozsamosc() {
	naglowek "tożsamość usługi — zmieniony znacznik"
	local plik="backend/config/gabinet.php"
	zachowaj "$plik"

	perturbuj znacznik-obcy

	dowod_zniknieciem "własny znacznik usługi zniknął z konfiguracji" \
		"'znacznik' => 'gabinet-api-v1'," "$plik"

	oczekuj_czerwone "test tożsamości wykrywa obcy znacznik" \
		dc exec -T app ./vendor/bin/pest --filter="WŁASNYM znacznikiem"
	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

p_puls() {
	naglowek "puls harmonogramu — usunięty wpis"
	# Sonda ma wykrywać BRAK wykonanych zadań, nie brak procesu.
	#
	# W-15 z rundy 4 — KŁAMSTWO DOKUMENTACJI. Nagłówek tego pliku twierdził:
	# „U NAS trafiło to w `p_puls`… Naprawa: najpierw ZATRZYMUJEMY harmonogram,
	# dopiero potem psujemy puls". W kodzie nie było ANI JEDNEGO zatrzymania —
	# było `Cache::forget` i natychmiastowe sprawdzenie. Harmonogram zapisuje
	# puls co 60 s, więc perturbacja była wyścigiem i w kilku procentach
	# przebiegów mierzyła tempo samonaprawy zamiast czujności kontroli.
	# Komentarz opisywał naprawę, której nikt nie napisał.
	#
	# Teraz naprawa naprawdę istnieje: zatrzymujemy harmonogram, więc
	# naruszenie przeżywa tak długo, jak trzeba.
	dc stop scheduler >/dev/null 2>&1

	# MAGAZYNEM PULSU JEST PLIK — `storage/puls-harmonogramu` (12.08).
	#
	# TRZECI ADRES TEGO SAMEGO MECHANIZMU, i za kazdym razem perturbacja
	# zostawala przy poprzednim:
	#
	#   cache  → baza (`sygnaly_zdrowia`, R6B-10)  → PLIK (bo tabela kazala
	#   sondzie zdrowia czekac na schemat tworzony pozniej i krok [5] bramki
	#   stawal na `scheduler=starting`).
	#
	# Skutek za kazdym razem TEN SAM i po cichu: mutacja celowala w magazyn,
	# ktorego nie ma, wiec puls zostawal na miejscu i scenariusz nie mierzyl
	# NICZEGO. Tym razem wyszlo dopiero na pelnym zestawie, jako wyjatek
	# „relation sygnaly_zdrowia does not exist" — czyli glosno tylko dlatego,
	# ze tabela zniknela. Gdyby zostala pusta, perturbacja swiecilaby zielono.
	#
	# To jest N-3 w trzeciej odslonie. Dlatego mutacja NIE cytuje juz sciezki
	# z pamieci: pyta o nia SAM MECHANIZM (`--gdzie`), wiec kolejna przenosine
	# przezyje bez zmian tutaj.
	local plik_pulsu
	plik_pulsu="$(dc exec -T app php artisan gabinet:puls --gdzie 2>/dev/null | tr -d '[:space:]')"

	if [ -z "$plik_pulsu" ]; then
		printf '    ✗ NIE WIEM, GDZIE JEST PULS: `gabinet:puls --gdzie` nic nie oddalo — perturbacja nierozstrzygająca\n'
		NIEUDANE=$((NIEUDANE + 1))
		dc start scheduler >/dev/null 2>&1

		return
	fi

	dc exec -T app rm -f "$plik_pulsu" >/dev/null 2>&1

	# Dowód mutacji: puls NAPRAWDĘ zniknął, a nie „pewnie zniknął".
	#
	# Czytamy WARTOŚĆ, nie kod wyjścia tinkera: `exit()` wewnątrz `--execute`
	# nie propaguje się do powłoki, więc pierwsza wersja tego dowodu meldowała
	# „mutacja nie weszła w życie" przy poprawnie wykonanej mutacji. Znowu
	# przyrząd, nie system — i znowu wykryte dopiero uruchomieniem.
	# Dowod mutacji pyta o TEN SAM plik, ktory skasowalismy — sciezka pochodzi
	# z jednego odczytu, wiec dowod i mutacja nie moga sie rozjechac.
	local stan_pulsu
	if dc exec -T app test -f "$plik_pulsu" >/dev/null 2>&1; then
		stan_pulsu="JEST"
	else
		stan_pulsu="BRAK"
	fi

	if [ "$stan_pulsu" = "BRAK" ]; then
		printf '    · dowód mutacji: plik pulsu %s zniknął\n' "$plik_pulsu"
	else
		printf '    ✗ MUTACJA NIE WESZŁA W ŻYCIE: plik pulsu %s nadal jest — perturbacja nierozstrzygająca\n' "$plik_pulsu"
		NIEUDANE=$((NIEUDANE + 1))
		dc start scheduler >/dev/null 2>&1

		return
	fi

	oczekuj_czerwone "gabinet:puls --sprawdz wykrywa brak pulsu" \
		dc exec -T app php artisan gabinet:puls --sprawdz

	dc start scheduler >/dev/null 2>&1

	# Kierunek odwrotny — bez czekania na naturalny tik harmonogramu.
	#
	# Reguła C1 (zespół helpdesku): KONTROLA DZIELĄCA MECHANIZM ZE SWOIM
	# PRZEDMIOTEM JEST W TYM MECHANIZMIE NIEFALSYFIKOWALNA. Pierwsza wersja tej
	# gałęzi czekała, aż harmonogram sam zapisze puls — czyli mierzyła cadencję
	# harmonogramu, nie czujność kontroli, i losowo padała po 90 s.
	#
	# Puls zapisujemy więc NIEZALEŻNĄ ścieżką: wołamy polecenie wprost.
	dc exec -T app php artisan gabinet:puls >/dev/null 2>&1

	if dc exec -T app php artisan gabinet:puls --sprawdz >/dev/null 2>&1; then
		printf '    ✓ po zapisaniu pulsu kontrola wraca na zielone
'
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ kontrola pozostaje czerwona mimo świeżego pulsu
'
		NIEUDANE=$((NIEUDANE + 1))
	fi
}

p_biala_lista() {
	naglowek "biała lista ról — autoryzacja „wszystkimi rolami z tokenu\""
	local plik="backend/app/Tozsamosc/Bramki.php"
	zachowaj "$plik"

	# Zdejmujemy filtr białej listy — dokładnie ten błąd, który wpuszcza
	# marker `wymaga-2fa` i role wbudowane Keycloaka do uprawnień.
	perturbuj biala-lista-zdjeta

	dowod_zniknieciem "filtr białej listy zniknął z Bramki.php" \
		'return array_values(array_intersect($roleZTokenu, $biala));' "$plik"

	# R7-8: "Bramki|marker" to ALTERNATYWA ERE, a zdegenerowane były OBIE
	# gałęzie — `marker` jako fragment nazw trzech testów, `Bramki` jako
	# nazwa klasy w nagłówku (`PASS  Tests\Feature\BramkiTest`; oba wystąpienia
	# zmierzone na przebiegu zielonym). Zapadka porównywała CAŁY literał
	# przez `str_contains`, więc nie widziała żadnej z gałęzi.
	oczekuj_czerwone "testy wykrywają marker techniczny w uprawnieniach" \
		--przyczyna "Marker techniczny AUTORYZUJE" \
		dc exec -T app ./vendor/bin/pest --filter="marker"
	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

p_d1b() {
	naglowek "D-1b — logowanie bez krypto, bez nowej kolumny, bez nowej trasy"
	# Atak, ktory w rundzie 7 przeszedl przez OBIE siatki deklaratywne przy
	# zielonej bramce (`BrakWlasnychHaselTest` → 9 passed, Larastan → No errors).
	# Siatka POMIAROWA pyta o SKUTEK — zapis tozsamosci do sesji — wiec ma go
	# zobaczyc niezaleznie od tego, jakim prymitywem porownano sekret.
	local plik="backend/routes/web.php"
	zachowaj "$plik"

	perturbuj d1b-podloz || { echo "    nie udało się podłożyć perturbacji"; NIEUDANE=$((NIEUDANE + 1)); return; }

	dowod_mutacji "trasa GET / niesie mechanizm logowania obok OIDC" \
		grep -q "session()->put('konta'" "$plik"

	# DWA POMIARY, bo pojedynczy nie rozdziela swiatow:
	#   1. siatka POMIAROWA ma ZAPALIC — inaczej nie zamyka niczego;
	#   2. siatki DEKLARATYWNE maja pozostac ZIELONE — to nie jest ich wada
	#      do naprawienia, tylko powod, dla ktorego trzecia siatka istnieje.
	#      Gdyby zaczely tu czerwienic, wynik nadal bylby dobry, ale opis
	#      w `SiatkaPomiarowaTozsamosciTest` przestalby byc prawdziwy.
	oczekuj_czerwone "siatka POMIAROWA wykrywa logowanie poza OIDC" \
		--przyczyna "TRASA SPOZA CALLBACKU USTANOWIŁA TOŻSAMOŚĆ W SESJI" \
		dc exec -T app ./vendor/bin/pest tests/Feature/SiatkaPomiarowaTozsamosciTest.php

	if dc exec -T app ./vendor/bin/pest tests/Feature/BrakWlasnychHaselTest.php >/dev/null 2>&1; then
		printf '    · siatki DEKLARATYWNE nadal ZIELONE — dokładnie dlatego trzecia siatka istnieje\n'
	else
		printf '    · uwaga: siatki DEKLARATYWNE też zaczerwieniły — opis D-1b wymaga aktualizacji\n'
	fi

	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

p_d1b_zaklecie() {
	naglowek "D-1b — ten sam atak pod nazwą pola SPOZA baterii siatki (zaklecie)"
	# ZNALEZISKO RUNDY 8 (R8-1). Siatka reklamowała pomiar SKUTKU „niezależnie
	# od sposobu", a wykrywała atak tylko wtedy, gdy pole nazywało się tak, jak
	# zgadła jej bateria. Pod nazwą `zaklecie` mechanizm logował poza OIDC,
	# a siatka, obie siatki deklaratywne, pełna suita, Larastan i Pint —
	# wszystko zielone.
	#
	# Ten scenariusz jest KONTROLĄ NEGATYWNĄ samej siatki: mechanizm identyczny
	# co do bajtu poza jedną nazwą. Jeśli siatka zapala się na `d1b`, a tutaj
	# nie, to nie mierzy skutku — zgaduje nazwę wejścia.
	local plik="backend/routes/web.php"
	zachowaj "$plik"

	perturbuj d1b-podloz-zaklecie || { echo "    nie udało się podłożyć perturbacji"; NIEUDANE=$((NIEUDANE + 1)); return; }

	dowod_mutacji "trasa GET / czyta sekret z pola spoza baterii siatki" \
		grep -q "input('zaklecie'" "$plik"

	oczekuj_czerwone "siatka POMIAROWA wykrywa logowanie pod nazwą pola spoza baterii" \
		--przyczyna "TRASA SPOZA CALLBACKU USTANOWIŁA TOŻSAMOŚĆ W SESJI" \
		dc exec -T app ./vendor/bin/pest tests/Feature/SiatkaPomiarowaTozsamosciTest.php

	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

p_zamrozenie() {
	naglowek "zamrażanie reguł — reguła czytana z bieżącej konfiguracji"
	local plik="backend/app/Reguly/OcenaAnulacji.php"
	zachowaj "$plik"

	# Podmiana zamrożonego okna na wartość „bieżącą" — dokładnie ten błąd,
	# przed którym chroni CLAUDE.md §4.
	perturbuj zamrozenie-biezace

	dowod_zniknieciem "odczyt okna z reguły ZAMROŻONEJ zniknął z kodu" \
		'$sekundOkna = $reguly->oknoBezplatnegoOdwolaniaGodzin * 3600;' "$plik"

	# Allowlista to KOMUNIKAT ASERCJI z `OcenaAnulacjiTest` (dopisany 12.08).
	# Tu falszywe zielone kosztuje PIENIADZE PACJENTA (CLAUDE.md §4), wiec
	# allowlista jest wymagana, a nie zalecana.
	oczekuj_czerwone "test zamrażania wykrywa użycie innej reguły niż zamrożona" \
		--przyczyna "ZASTOSOWANO REGUŁĘ BIEŻĄCĄ ZAMIAST ZAMROŻONEJ" \
		dc exec -T app ./vendor/bin/pest --filter="ZAMROŻONĄ"
	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

# ===========================================================================

WSZYSTKIE="testy pusta_suita licznik pominiete statyka format sekrety hasla hasla_v2 nonce wzmacniacz lockfile vendor zamek sonda_bazy zdrowie tozsamosc puls d1b d1b_zaklecie zamrozenie biala_lista retencja retencja_wykonanie obietnica sesja role_zamrozone logout_failsafe zrodlo_rol wymuszone_wylogowanie uniewaznienie_sid id_token_sesja"

# `--lista` ODPOWIADA PRZED STRAZNIKIEM MUTACJI — bo NICZEGO NIE MUTUJE.
#
# Zlapane krokiem [7] bramki 12.08: straznik indeksu odmawial takze
# LISTOWANIU, wiec odpowiedz na pytanie „jakie sa scenariusze" zalezala od
# stanu, ktory z tym pytaniem nie ma nic wspolnego. `skrypty-uruchamialne.sh`
# bral wtedy TEKST ODMOWY za liste i meldowal 29 wywolan wskazujacych
# w prozne — alarm prawdziwy co do czerwieni, nieprawdziwy co do adresu.
#
# Pierwsza proba naprawy przeniosla `--lista` na sam poczatek pliku, PRZED
# definicje `$WSZYSTKIE` — i listowanie padalo na `unbound variable`.
# Wlasciwe posuniecie jest odwrotne: to STRAZNIK ma stac pozniej, bo chroni
# MUTACJE, a nie odczyt.
if [ "${1:-}" = "--lista" ]; then
	printf 'Perturbacje: %s\n' "$WSZYSTKIE"
	exit 0
fi


WYBRANE="${*:-$WSZYSTKIE}"

# NIEZNANA NAZWA JEST BŁĘDEM WYWOŁANIA I ROZSTRZYGA SIĘ TUTAJ (R6B-8).
#
# Do 12.08 literówka wychodziła dopiero w rozdzielaczu, PO postawieniu stosu —
# czyli po kilku minutach — a `skrypty-uruchamialne.sh` przyjmował „cokolwiek
# niezerowego" jako dowód odrzucenia. Kod wyjścia bywa wtedy CUDZY: brak
# Dockera, zajęty port, niezdrowy stos. Kontrola „nieznana nazwa nie przechodzi
# po cichu" świeciła zielono na czerwieni, która z nią nie miała nic wspólnego.
#
# Od tej zmiany: sprawdzenie nazw idzie PRZED dotknięciem Dockera i ma WŁASNY
# kod wyjścia. „Odrzucono nieznaną nazwę" i „przewróciło się środowisko" to
# odtąd dwie różne wartości, nie jedna.
KOD_NIEZNANA_NAZWA=4
NIEZNANE=""

for NAZWA in $WYBRANE; do
	ZNANA=0

	for KANDYDAT in $WSZYSTKIE; do
		[ "$NAZWA" = "$KANDYDAT" ] && ZNANA=1
	done

	[ "$ZNANA" -eq 1 ] || NIEZNANE="$NIEZNANE $NAZWA"
done

if [ -n "$NIEZNANE" ]; then
	# V-5 z rundy 5: literówka w nazwie dawała „PERTURBACJE OK — 0 kontroli"
	# i kod wyjścia 0. To jest awaria D-0013 („pusta suita = zielone CI")
	# odtworzona w runnerze, który D-0013 egzekwuje.
	printf 'NIEZNANA PERTURBACJA:%s — literówka albo usunięty scenariusz\n' "$NIEZNANE" >&2
	printf 'Znane scenariusze: %s\n' "$WSZYSTKIE" >&2
	exit "$KOD_NIEZNANA_NAZWA"
fi

# STRAZNIK MUTACJI STOI PO WALIDACJI WYWOLANIA (12.08).
#
# Literowka w nazwie i pytanie o liste NICZEGO NIE MUTUJA, wiec ich wynik
# nie moze zalezec od stanu indeksu gita. Przy odwrotnej kolejnosci
# `skrypty-uruchamialne.sh` dostawalo na nieznana nazwe kod 2 (odmowa
# straznika) zamiast 4 (nieznana nazwa) i meldowalo defekt, ktorego nie ma —
# czyli rozstrzygalo CUDZYM sygnalem, dokladnie jak w R6B-8.

# Perturbacje MUTUJĄ drzewo robocze. Gdy w indeksie czekają zmiany
# przygotowane do commitu, ryzyko jest konkretne: `git commit` wykonany
# w trakcie przebiegu utrwala stan SPERTURBOWANY. U zespołu helpdesku tak
# trafił do repozytorium heap 1 GB — twarda reguła była nieprawdziwa w repo
# przez kilka commitów, bo nikt nie sprawdził, co właśnie zostało zapisane.
if [ -n "$(git diff --cached --name-only 2>/dev/null)" ]; then
	echo "ODMOWA: w indeksie git czekają przygotowane zmiany." >&2
	echo "Perturbacje mutują drzewo robocze — commit w trakcie przebiegu utrwaliłby stan sperturbowany." >&2
	echo "Zacommituj albo wycofaj z indeksu, potem uruchom perturbacje." >&2
	exit 2
fi

# ---------------------------------------------------------------------------
# ŚRODOWISKO PRZEBIEGU — R6B-16 / G-1.
#
# Do 12.08 `dc()` nie podawało ani `--env-file`, ani `GABINET_PLIK_ENV`, więc
# `docker-compose.yml` montował do kontenera wartość domyślną — `./.env`, czyli
# plik DEWELOPERA z PRAWDZIWYMI SEKRETAMI. V-2 zamknięto tylko po stronie
# bramki; reguła „klon weryfikatora NIGDY nie trzyma prawdziwych sekretów"
# obowiązywała u nas w połowie narzędzi.
#
# Plik budujemy TYM SAMYM mechanizmem co bramka (`--przygotuj-srodowisko`),
# a jego ŚCIEŻKĘ podaje sama bramka (`--pokaz-srodowisko`). Świadomie nie
# powstaje tu druga kopia `przygotuj_env()` ani drugi wzór na nazwę pliku:
# dwa opisy jednej rzeczy rozjeżdżają się po cichu (to jest lekcja
# `licz-testy.sh` i pułapka U-2 przy zamku).
#
# PLIK JEST TRWAŁY MIĘDZY PRZEBIEGAMI — i to jest decyzja, nie przeoczenie.
# `przygotuj_env()` losuje nowe `DB_PASSWORD` przy każdym wywołaniu, a stos
# perturbacji NIE jest kasowany z wolumenami między przebiegami. Świeże hasło
# przy starym woluminie postgresa daje „password authentication failed"
# i 120 s czekania na zdrowie — dokładnie awaria opisana w nagłówku bramki.
# Dlatego: budujemy, gdy pliku NIE MA; gdy jest — używamy istniejącego.
# Wymuszenie odbudowy: skasuj plik i uruchom perturbacje ponownie.
PLIK_ENV="$(bash "$KORZEN/skrypty/bramka.sh" --projekt "$PROJEKT" --pokaz-srodowisko)"

if [ -z "$PLIK_ENV" ]; then
	echo "ODMOWA: bramka nie potrafi podać ścieżki pliku środowiska." >&2
	exit 2
fi

if [ ! -s "$PLIK_ENV" ]; then
	echo "Buduję plik środowiska przebiegu z .env.example: $(basename "$PLIK_ENV")" >&2

	PLIK_ENV="$(GABINET_BRAMKA_PORT_HTTP="$PORT_HTTP" \
		GABINET_BRAMKA_PORT_POSTGRES="$PORT_PG" \
		GABINET_BRAMKA_PORT_REDIS="$PORT_REDIS" \
		bash "$KORZEN/skrypty/bramka.sh" --projekt "$PROJEKT" --przygotuj-srodowisko)"
fi

if [ -z "$PLIK_ENV" ] || [ ! -s "$PLIK_ENV" ]; then
	echo "ODMOWA: nie ma pliku środowiska dla projektu '$PROJEKT'." >&2
	echo "Bez niego compose zamontowałby './.env' — plik DEWELOPERA z sekretami." >&2
	exit 2
fi

if ! dc ps --status running --services 2>/dev/null | grep -q '^app$'; then
	echo "Stos '$PROJEKT' nie stoi — stawiam go." >&2

	# ŚWIADOMIE bez sprawdzania kodu wyjścia `up` — zasada 1 z nagłówka bramki:
	# pytamy o STAN, nie o kod wyjścia polecenia. Pierwsza wersja tej gałęzi
	# robiła `if ! dc up -d`, więc odmawiała startu, mimo że stos wstawał
	# poprawnie (`up` wraca niezerowo, gdy któryś kontener mignie jako
	# unhealthy w trakcie startu). Zmierzone: ręczne `up -d` → wszystko
	# `Healthy`, skryptowe → „nie udało się postawić stosu".
	dc up -d >/dev/null 2>&1 || true

	czekaj_na_zdrowie || exit 2

	dc exec -T app php artisan migrate --force >/dev/null 2>&1
	echo "Stos '$PROJEKT' gotowy." >&2
fi

# R6B-16 — KONTROLA, NIE DEKLARACJA: czy kontener ma NASZ plik środowiska.
#
# Stos podniesiony przed tą zmianą (albo z inną wartością `GABINET_PLIK_ENV`)
# ma zamontowane to, co było w chwili jego utworzenia — czyli `./.env`
# DEWELOPERA. Samo podanie `--env-file` niczego by tu nie zmieniło.
if ! srodowisko_zamontowane; then
	echo "Stos '$PROJEKT' ma zamontowany INNY plik .env niż plik tego przebiegu — stawiam go od nowa." >&2

	# `down -v`, nie samo `--force-recreate`: nowy plik niesie nowe
	# `DB_PASSWORD`, a postgres ustawia hasło WYŁĄCZNIE przy inicjalizacji
	# klastra. Odtworzenie samego kontenera nad starym woluminem daje
	# „password authentication failed" w pętli aż do wyczerpania limitu —
	# ta sama awaria, którą opisuje nagłówek bramki, tylko bez komunikatu
	# o przyczynie.
	#
	# Wolno tu kasować wolumeny, bo: (a) projekt NIGDY nie jest `gabinet`
	# (odmowa na początku pliku), (b) nazwy wolumenów są scope'owane przez
	# `GABINET_PREFIX=$PROJEKT`, (c) perturbacje haseł i tak wołają
	# `migrate:fresh --force` cztery razy. `down -v` NIE rusza obrazu, więc
	# ponowny start nie wymaga przebudowy.
	dc down -v --remove-orphans >/dev/null 2>&1 || true
	dc up -d >/dev/null 2>&1 || true
	czekaj_na_zdrowie || exit 2
	dc exec -T app php artisan migrate --force >/dev/null 2>&1
fi

# ZDROWIE PO PRZYGOTOWANIU SRODOWISKA — druga, NIEZALEZNA wlasnosc.
#
# Zgodny skrot pliku `.env` dowodzi, ze APLIKACJA ma nasz plik. NIE dowodzi,
# ze stos jako calosc jest z nim spojny: postgres ustawia haslo WYLACZNIE
# przy inicjalizacji klastra, wiec wolumen z poprzedniego zycia stosu niesie
# STARE haslo przy nowym pliku. Wniosek o calym stosie wyciagany z wlasnosci
# jednego kontenera to ta sama wada, ktora ten zestaw bada gdzie indziej.
#
# Zmierzone 12.08: `password authentication failed for user gabinet` przy
# ZGODNYM skrocie pliku. Wszystkie scenariusze dotykajace bazy padaly wtedy
# na polaczeniu, a allowlisty meldowaly „czerwien nie zawiera oczekiwanej
# przyczyny" — takze te, ktore dzien wczesniej rozrozanialy poprawnie.
# Zestaw wyprodukowal 17 czerwieni, z ktorych ZADNA nie dotyczyla badanych regul.
if ! czekaj_na_zdrowie; then
	echo "Stos '$PROJEKT' nie zglasza zdrowia mimo wlasciwego pliku srodowiska —" >&2
	echo "najczestsza przyczyna: wolumen bazy sprzed izolacji, z innym haslem. Stawiam od nowa." >&2

	dc down -v --remove-orphans >/dev/null 2>&1 || true
	dc up -d >/dev/null 2>&1 || true

	if ! czekaj_na_zdrowie; then
		echo "ODMOWA: stos nie zglasza zdrowia takze po odtworzeniu od zera." >&2
		echo "Bez zdrowego stosu KAZDY wynik tego zestawu jest nieprawdziwy:" >&2
		echo "czerwien pochodzi wtedy z polaczenia, a nie z badanej reguly." >&2
		exit 2
	fi

	dc exec -T app php artisan migrate --force >/dev/null 2>&1
fi

if ! srodowisko_zamontowane; then
	echo "ODMOWA: nie potwierdzono, że kontener ma plik środowiska tego przebiegu." >&2
	echo "Bez tego dowodu perturbacje mielą '.env' DEWELOPERA — z prawdziwymi sekretami." >&2
	echo "Naprawa: GABINET_PREFIX=$PROJEKT docker compose -p $PROJEKT down -v, potem uruchom ponownie." >&2
	exit 2
fi

echo "Środowisko przebiegu potwierdzone: $(basename "$PLIK_ENV") (skrót zgodny z plikiem w kontenerze)." >&2

for NAZWA in $WYBRANE; do
	case "$NAZWA" in
		testy) p_testy ;;
		pusta_suita) p_pusta_suita ;;
		statyka) p_statyka ;;
		format) p_format ;;
		sekrety) p_sekrety ;;
		hasla) p_hasla ;;
		hasla_v2) p_hasla_v2 ;;
		nonce) p_nonce ;;
		wzmacniacz) p_wzmacniacz ;;
		lockfile) p_lockfile ;;
		licznik) p_licznik_testow ;;
		pominiete) p_suita_pominieta ;;
		hasla_v2) p_hasla_v2 ;;
		nonce) p_nonce ;;
		lockfile) p_lockfile ;;
		vendor) p_vendor_niekompletny ;;
		retencja) p_retencja ;;
		retencja_wykonanie) p_retencja_wykonanie ;;
		obietnica) p_obietnica ;;
		sesja) p_sesja_jawna ;;
		role_zamrozone) p_role_zamrozone ;;
		logout_failsafe) p_logout_failsafe ;;
		zrodlo_rol) p_zrodlo_rol ;;
		wymuszone_wylogowanie) p_wymuszone_wylogowanie ;;
		uniewaznienie_sid) p_uniewaznienie_sid ;;
		id_token_sesja) p_id_token_w_sesji ;;
		zamek) p_zamek ;;
		sonda_bazy) p_sonda_bazy ;;
		zdrowie) p_zdrowie ;;
		tozsamosc) p_tozsamosc ;;
		puls) p_puls ;;
		d1b) p_d1b ;;
		d1b_zaklecie) p_d1b_zaklecie ;;
		zamrozenie) p_zamrozenie ;;
		biala_lista) p_biala_lista ;;
		*)
			# V-5 z rundy 5: literówka w nazwie dawała „PERTURBACJE OK — 0 kontroli"
			# i kod wyjścia 0. To jest awaria D-0013 („pusta suita = zielone CI")
			# odtworzona w runnerze, który D-0013 egzekwuje. Nieznana nazwa to
			# BŁĄD WYWOŁANIA, nie scenariusz do pominięcia.
			printf '    ✗ NIEZNANA PERTURBACJA: %s — literówka albo usunięty scenariusz
' "$NAZWA"
			NIEUDANE=$((NIEUDANE + 1))
			;;
	esac
done

printf '\n'
# V-5: zestaw, który nie wykonał ANI JEDNEJ kontroli, nie jest „OK" — to ta
# sama awaria co pusta suita testów. Podłoga jest tu jedynką, bo zestaw bywa
# świadomie wołany z jedną nazwą.
if [ "$UDANE" -eq 0 ]; then
	printf '
PERTURBACJE NIEROZSTRZYGNIĘTE — wykonano ZERO kontroli (pominięte: %s)
' "$POMINIETE"
	exit 1
fi

if [ "$NIEUDANE" -eq 0 ]; then
	echo "PERTURBACJE OK — $UDANE kontroli udowodniło, że umie zaświecić czerwono (pominięte: $POMINIETE)"
	exit 0
fi

echo "PERTURBACJE CZERWONE — $NIEUDANE kontroli NIE zareagowało na złamaną regułę (udanych: $UDANE)"
echo "Kontrola, która nie pada przy złamanej regule, jest traktowana jak nieistniejąca (D-0013)."
exit 1
