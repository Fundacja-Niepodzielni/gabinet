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
# ---------------------------------------------------------------------------
#
# WYMAGA stojącego stosu bramki albo deweloperskiego. Domyślnie używa
# deweloperskiego (`gabinet`), bo perturbacje są krótkie i nie ruszają danych:
# każda zmiana dotyczy plików w drzewie roboczym albo stanu kontenera,
# i każda jest cofana.
# ===========================================================================
set -uo pipefail

export MSYS_NO_PATHCONV=1
export MSYS2_ARG_CONV_EXCL='*'

KORZEN="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$KORZEN"

PROJEKT="${GABINET_PERTURBACJE_PROJEKT:-gabinet}"
KOPIE="$(mktemp -d)"
UDANE=0
NIEUDANE=0
POMINIETE=0

sciezka_hosta() {
	if command -v cygpath >/dev/null 2>&1; then cygpath -w "$KORZEN/$1"; else echo "$KORZEN/$1"; fi
}

dc() { docker compose -p "$PROJEKT" -f "$(sciezka_hosta docker-compose.yml)" "$@"; }

# Mutacje plików trzymamy w `perturbuj.py`, nie w heredokach basha —
# uzasadnienie w nagłówku tamtego pliku (dwa ciche błędy ucieczki znaków
# sprawiły, że perturbacja „przechodziła", nie zmieniwszy niczego).
# `sciezka_hosta`, nie "$KORZEN/..." — przy MSYS_NO_PATHCONV=1 ścieżka POSIX
# dociera do windowsowego Pythona dosłownie i staje się `D:\d\KOD\...`.
perturbuj() { python3 "$(sciezka_hosta skrypty/perturbuj.py)" "$@"; }

# Liczba testów z wyjścia pesta — DOKŁADNIE ta sama procedura co w bramce.
# Pest koloruje wynik nawet przy NO_COLOR, a `[32;1m` zawiera cyfry, więc
# bez usunięcia pełnych sekwencji ANSI parser wyłuskuje „39" albo zero.
policz_testy() {
	local liczba
	liczba="$(printf '%s' "$1" \
		| sed -e 's/\x1b\[[0-9;]*[A-Za-z]//g' \
		| sed -n 's/.*Tests:[^0-9]*\([0-9][0-9]*\) passed.*/\1/p' | tail -1)"
	printf '%s' "${liczba:-0}"
}

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
	rm -rf "$KOPIE"
}
trap przywroc_wszystko EXIT INT TERM

# --- raportowanie ----------------------------------------------------------
naglowek() { printf '\n=== PERTURBACJA: %s\n' "$*"; }

# Kontrola MUSI paść. Zielony wynik po złamaniu reguły = kontrola nic nie znaczy.
oczekuj_czerwone() {
	local opis="$1"; shift

	if "$@" >/dev/null 2>&1; then
		printf '    ✗ %s — kontrola PRZESZŁA mimo złamanej reguły (nic nie sprawdza)\n' "$opis"
		NIEUDANE=$((NIEUDANE + 1))
	else
		printf '    ✓ %s — kontrola zapaliła się na czerwono\n' "$opis"
		UDANE=$((UDANE + 1))
	fi
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
	sed -i 's/\$sekundDoWizyty >= \$sekundOkna/\$sekundDoWizyty > \$sekundOkna/' "$plik"
	oczekuj_czerwone "Pest wykrywa przesunięcie granicy o 1 sekundę" \
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

	if [ "$liczba" -lt 100 ]; then
		printf '    ✓ podłoga 100 testów odrzuca pusty przebieg (policzono: %s)\n' "$liczba"
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ podłoga nie odrzuca pustego wyniku (policzono: %s)\n' "$liczba"
		NIEUDANE=$((NIEUDANE + 1))
	fi

	# Kierunek odwrotny — bez niego „0 przy pustce" przechodzi także wtedy,
	# gdy parser ZAWSZE zwraca zero. Dokładnie ten błąd popełniliśmy.
	local pelny pelna_liczba
	pelny="$(dc exec -T app ./vendor/bin/pest 2>&1)"
	pelna_liczba="$(policz_testy "$pelny")"

	if [ "$pelna_liczba" -ge 100 ]; then
		printf '    ✓ parser widzi pełny przebieg (%s testów) — nie zwraca zawsze zera\n' "$pelna_liczba"
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ parser NIE widzi pełnego przebiegu (policzył: %s)\n' "$pelna_liczba"
		NIEUDANE=$((NIEUDANE + 1))
	fi
}

p_statyka() {
	naglowek "statyka — wstrzyknięty błąd typu"
	local plik="backend/app/Wsparcie/Typy.php"
	zachowaj "$plik"

	sed -i 's/public static function napis(mixed \$wartosc, string \$domyslny = .."..): string/public static function napis(mixed $wartosc, string $domyslny = 0): string/' "$plik"
	printf '\n// perturbacja\nfunction perturbacja_typu(): int { return "napis"; }\n' >> "$plik"

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

	sed -i 's|^KEYCLOAK_CLIENT_SECRET=$|KEYCLOAK_CLIENT_SECRET=aGVsbG8td29ybGQtdGhpcy1pcy1hLXNlY3JldA|' "$plik"

	oczekuj_czerwone "gitleaks wykrywa sekret w pliku wzorcowym" \
		docker run --rm -v "$(cygpath -w "$KORZEN" 2>/dev/null || echo "$KORZEN"):/repo" -w /repo \
			zricethezav/gitleaks:latest detect --source=/repo --config=/repo/.gitleaks.toml \
			--no-banner --redact --no-git
	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"

	# Drugi kierunek: test `SekretyTest` też musi to złapać — gitleaks patrzy
	# na kształt, test na semantykę („ta zmienna ma być PUSTA").
	sed -i 's|^SMSAPI_TOKEN=$|SMSAPI_TOKEN=cokolwiek|' "$plik"
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

	oczekuj_czerwone "BrakWlasnychHaselTest wykrywa PEŁNY mechanizm haseł pod polskimi nazwami" 		dc exec -T app ./vendor/bin/pest tests/Feature/BrakWlasnychHaselTest.php

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

p_zamek() {
	naglowek "zamek bramki — drugi równoległy przebieg"
	# O-5: dwa przebiegi mielą jedną bazę `gabinet_test`. Sprawdzamy, że drugi
	# ODMAWIA startu, zamiast produkować fałszywe czerwone.
	local zamek="${TMPDIR:-/tmp}/gabinet-bramka-perturbacja.zamek"
	rm -rf "$zamek"
	mkdir -p "$zamek"
	echo "$$" > "$zamek/pid"

	oczekuj_czerwone "bramka odmawia startu przy zajętym zamku" 		bash "$KORZEN/skrypty/bramka.sh" --projekt gabinet-bramka-perturbacja --tylko-kod

	rm -rf "$zamek"
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

	sed -i "s|'znacznik' => 'gabinet-api-v1',|'znacznik' => 'cudza-usluga',|" "$plik"

	oczekuj_czerwone "test tożsamości wykrywa obcy znacznik" \
		dc exec -T app ./vendor/bin/pest --filter="WŁASNYM znacznikiem"
	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

p_puls() {
	naglowek "puls harmonogramu — usunięty wpis"
	# Sonda ma wykrywać BRAK wykonanych zadań, nie brak procesu.
	dc exec -T app php artisan tinker --execute="Cache::forget('gabinet:puls-harmonogramu');" >/dev/null 2>&1

	oczekuj_czerwone "gabinet:puls --sprawdz wykrywa brak pulsu" \
		dc exec -T app php artisan gabinet:puls --sprawdz

	# Harmonogram zapisze puls sam, w ciągu minuty.
}

p_biala_lista() {
	naglowek "biała lista ról — autoryzacja „wszystkimi rolami z tokenu\""
	local plik="backend/app/Tozsamosc/Bramki.php"
	zachowaj "$plik"

	# Zdejmujemy filtr białej listy — dokładnie ten błąd, który wpuszcza
	# marker `wymaga-2fa` i role wbudowane Keycloaka do uprawnień.
	sed -i 's/return array_values(array_intersect(\$roleZTokenu, \$biala));/return $roleZTokenu;/' "$plik"

	oczekuj_czerwone "testy wykrywają marker techniczny w uprawnieniach" 		dc exec -T app ./vendor/bin/pest --filter="marker"
	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

p_zamrozenie() {
	naglowek "zamrażanie reguł — reguła czytana z bieżącej konfiguracji"
	local plik="backend/app/Reguly/OcenaAnulacji.php"
	zachowaj "$plik"

	# Podmiana zamrożonego okna na wartość „bieżącą" — dokładnie ten błąd,
	# przed którym chroni CLAUDE.md §4.
	sed -i 's/\$sekundOkna = \$reguly->oknoBezplatnegoOdwolaniaGodzin \* 3600;/\$sekundOkna = 48 * 3600;/' "$plik"

	oczekuj_czerwone "test zamrażania wykrywa użycie innej reguły niż zamrożona" \
		dc exec -T app ./vendor/bin/pest --filter="ZAMROŻONĄ"
	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
}

# ===========================================================================

WSZYSTKIE="testy pusta_suita statyka format sekrety hasla nonce lockfile zamek zdrowie tozsamosc puls zamrozenie biala_lista"

if [ "${1:-}" = "--lista" ]; then
	printf 'Perturbacje: %s\n' "$WSZYSTKIE"
	exit 0
fi

WYBRANE="${*:-$WSZYSTKIE}"

if ! dc ps --status running --services 2>/dev/null | grep -q '^app$'; then
	echo "ODMOWA: stos '$PROJEKT' nie stoi. Uruchom: docker compose up -d --wait" >&2
	exit 2
fi

for NAZWA in $WYBRANE; do
	case "$NAZWA" in
		testy) p_testy ;;
		pusta_suita) p_pusta_suita ;;
		statyka) p_statyka ;;
		format) p_format ;;
		sekrety) p_sekrety ;;
		hasla) p_hasla ;;
		nonce) p_nonce ;;
		lockfile) p_lockfile ;;
		zamek) p_zamek ;;
		nonce) p_nonce ;;
		lockfile) p_lockfile ;;
		zamek) p_zamek ;;
		zdrowie) p_zdrowie ;;
		tozsamosc) p_tozsamosc ;;
		puls) p_puls ;;
		zamrozenie) p_zamrozenie ;;
		biala_lista) p_biala_lista ;;
		*) pominieta "$NAZWA" "nieznana perturbacja" ;;
	esac
done

printf '\n'
if [ "$NIEUDANE" -eq 0 ]; then
	echo "PERTURBACJE OK — $UDANE kontroli udowodniło, że umie zaświecić czerwono (pominięte: $POMINIETE)"
	exit 0
fi

echo "PERTURBACJE CZERWONE — $NIEUDANE kontroli NIE zareagowało na złamaną regułę (udanych: $UDANE)"
echo "Kontrola, która nie pada przy złamanej regule, jest traktowana jak nieistniejąca (D-0013)."
exit 1
