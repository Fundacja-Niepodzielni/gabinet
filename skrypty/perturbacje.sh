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

KOPIE="$(mktemp -d)"
UDANE=0
NIEUDANE=0
POMINIETE=0

sciezka_hosta() {
	if command -v cygpath >/dev/null 2>&1; then cygpath -w "$KORZEN/$1"; else echo "$KORZEN/$1"; fi
}

# Własny PREFIKS nazw i własne porty — bez nich stos perturbacji zderza się
# z zasobami dewelopera PO NAZWIE mimo innego projektu, a alias `postgres`
# rozwiązuje się losowo na jeden z dwóch serwerów (ta sama pułapka, którą
# bramka zamknęła prefiksem `GABINET_PREFIX`).
PORT_HTTP="${GABINET_PERTURBACJE_PORT_HTTP:-8098}"
PORT_PG="${GABINET_PERTURBACJE_PORT_POSTGRES:-55444}"
PORT_REDIS="${GABINET_PERTURBACJE_PORT_REDIS:-56391}"

dc() {
	GABINET_PREFIX="$PROJEKT" 	GABINET_PORT_HTTP="$PORT_HTTP" 	GABINET_PORT_POSTGRES="$PORT_PG" 	GABINET_PORT_REDIS="$PORT_REDIS" 		docker compose -p "$PROJEKT" -f "$(sciezka_hosta docker-compose.yml)" "$@"
}

# Mutacje plików trzymamy w `perturbuj.py`, nie w heredokach basha —
# uzasadnienie w nagłówku tamtego pliku (dwa ciche błędy ucieczki znaków
# sprawiły, że perturbacja „przechodziła", nie zmieniwszy niczego).
# `sciezka_hosta`, nie "$KORZEN/..." — przy MSYS_NO_PATHCONV=1 ścieżka POSIX
# dociera do windowsowego Pythona dosłownie i staje się `D:\d\KOD\...`.
perturbuj() { python3 "$(sciezka_hosta skrypty/perturbuj.py)" "$@"; }

# Liczba testów — DOKŁADNIE ta sama procedura co w bramce, bo ten sam plik.
. "$KORZEN/skrypty/licz-testy.sh"

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
trap przywroc_wszystko EXIT
trap przerwano_perturbacje INT TERM

# --- raportowanie ----------------------------------------------------------
naglowek() { printf '\n=== PERTURBACJA: %s\n' "$*"; }

# Kontrola MUSI paść. Zielony wynik po złamaniu reguły = kontrola nic nie znaczy.
#
# Wyjście trafia do PLIKU, nie do `/dev/null` (lekcja helpdesku P11: filtr jest
# częścią pomiaru). Pierwsza wersja sądziła wyłącznie po kodzie wyjścia, więc
# „czerwono" z zupełnie innego powodu — brak kontenera, błąd składni, pusty
# przebieg — wyglądało dokładnie tak samo jak wykryte naruszenie. Dwa razy nas
# to kosztowało rundę weryfikacji (U-2, U-6).
#
# Dlatego czerwień musi być UZASADNIONA: narzędzie ma coś powiedzieć. Milcząca
# czerwień (zero wierszy na wyjściu) to sygnał, że kontrola się nie wykonała,
# a nie że zadziałała.
oczekuj_czerwone() {
	local opis="$1"; shift
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
dowod_mutacji() {
	local opis="$1"; shift

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

	oczekuj_czerwone "test §2 wykrywa mechanizm ukryty pod obcymi nazwami" 		dc exec -T app ./vendor/bin/pest tests/Feature/BrakWlasnychHaselTest.php

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

	dowod_mutacji "bramka częstotliwości zniknęła z kodu" \
		bash -c "! grep -q 'KLUCZ_ODSWIEZANIE, 1' '$plik'"

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
	local rejestr="backend/tests/Feature/RetencjaTest.php"
	zachowaj "$rejestr"

	python3 - "$rejestr" <<'PYTON'
import io, sys
p = sys.argv[1]
s = io.open(p, encoding='utf-8').read()
s = s.replace(
    "    'pacjenci' => [\n        'kolumna_pochodzenia' => 'created_at',",
    "    'pacjenci' => [\n        'kolumna_pochodzenia' => 'kolumna_ktorej_nie_ma',",
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

	if [ "$pominiete" -lt 100 ]; then
		printf '    ✗ mutacja nie pominęła suity (pominiętych: %s) — perturbacja nierozstrzygająca\n' "$pominiete"
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

	if [ "$(policz_testy "$zdrowy")" -ge 100 ] && [ "$(policz_asercje "$zdrowy")" -ge 300 ]; then
		printf '    ✓ na zdrowej suicie oba liczniki wracają wysoko (%s testów, %s asercji)\n' \
			"$(policz_testy "$zdrowy")" "$(policz_asercje "$zdrowy")"
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ liczniki nie wracają na zdrowej suicie (%s testów, %s asercji)\n' \
			"$(policz_testy "$zdrowy")" "$(policz_asercje "$zdrowy")"
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
	naglowek "magazyn sesji — dane osobowe bez szyfrowania"
	# Kontrola krzyżowa B7 z weryfikacji F1 huba, zmierzona u nas i potwierdzona:
	# sesja trzyma e-mail, login i CAŁY ID token, sterownik `redis` utrwala dane
	# na dysku, a `SESSION_ENCRYPT` miało domyślnie `false`.
	local plik="backend/config/session.php"
	zachowaj "$plik"

	perturbuj sesja-jawna

	dowod_mutacji "szyfrowanie sesji wyłączone w konfiguracji" \
		grep -q "SESSION_ENCRYPT', false" "$plik"

	oczekuj_czerwone "test znajduje e-mail i ID token JAWNIE w Redisie" \
		dc exec -T app ./vendor/bin/pest tests/Feature/SesjaBezJawnychDanychTest.php

	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"

	if dc exec -T app ./vendor/bin/pest tests/Feature/SesjaBezJawnychDanychTest.php >/dev/null 2>&1; then
		printf '    ✓ po włączeniu szyfrowania kontrola wraca na zielone\n'
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

	dowod_mutacji "sprawdzanie wieku access tokenu zniknęło z kodu" \
		bash -c "! grep -q 'wymagaOdswiezenia(\$konta)' '$plik'"

	oczekuj_czerwone "test wykrywa, że odebranie roli nie dociera do aplikacji" \
		dc exec -T app ./vendor/bin/pest tests/Feature/OdebranieRoliTest.php

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

	dowod_mutacji "awaryjne zakończenie sesji zniknęło z handlera" \
		bash -c "! grep -q 'sidNiezweryfikowany' '$plik'"

	oczekuj_czerwone "test wykrywa sesję, która przeżyła awarię wylogowania" \
		dc exec -T app ./vendor/bin/pest tests/Feature/OdebranieRoliTest.php

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

	oczekuj_czerwone "test wykrywa role czytane ze złego źródła" \
		dc exec -T app ./vendor/bin/pest tests/Feature/OdebranieRoliTest.php

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
		dc exec -T app ./vendor/bin/pest tests/Feature/OdebranieRoliTest.php

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

	dowod_mutacji "kontroler zapisuje ID token bez szyfrowania" \
		bash -c "! grep -q 'Crypt::encryptString(\$idToken)' '$plik'"

	oczekuj_czerwone "kontrola wykrywa ID token zapisany JAWNIE" \
		dc exec -T app ./vendor/bin/pest tests/Feature/OdebranieRoliTest.php

	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"

	perturbuj id-token-zakodowany

	dowod_mutacji "kontroler koduje ID token zamiast go szyfrować" \
		grep -q "base64_encode(\$idToken)" "$plik"

	oczekuj_czerwone "kontrola wykrywa ID token TYLKO ZAKODOWANY — po zdekodowaniu, nie po różnicy napisów" \
		dc exec -T app ./vendor/bin/pest tests/Feature/OdebranieRoliTest.php

	cp "$KOPIE/$(printf '%s' "$plik" | tr '/' '_')" "$plik"
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

	sed -i 's/\$sekundDoWizyty >= \$sekundOkna/\$sekundDoWizyty > \$sekundOkna/' "$plik"

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

	if [ "$liczba" -ge 100 ]; then
		printf '    ✓ licznik widzi %s wykonanych testów mimo niezaliczonych — podłoga nie kłamie\n' "$liczba"
		UDANE=$((UDANE + 1))
	else
		printf '    ✗ licznik zgubił się na wyniku z niezaliczonymi testami (policzył: %s)\n' "$liczba"
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

	sed -i "s|'znacznik' => 'gabinet-api-v1',|'znacznik' => 'cudza-usluga',|" "$plik"

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

	dc exec -T app php artisan tinker --execute="Cache::forget('gabinet:puls-harmonogramu');" >/dev/null 2>&1

	# Dowód mutacji: puls NAPRAWDĘ zniknął, a nie „pewnie zniknął".
	#
	# Czytamy WARTOŚĆ, nie kod wyjścia tinkera: `exit()` wewnątrz `--execute`
	# nie propaguje się do powłoki, więc pierwsza wersja tego dowodu meldowała
	# „mutacja nie weszła w życie" przy poprawnie wykonanej mutacji. Znowu
	# przyrząd, nie system — i znowu wykryte dopiero uruchomieniem.
	local stan_pulsu
	stan_pulsu="$(dc exec -T app php artisan tinker \
		--execute="echo Cache::has('gabinet:puls-harmonogramu') ? 'JEST' : 'BRAK';" 2>/dev/null \
		| tr -d '[:space:]')"

	if [ "$stan_pulsu" = "BRAK" ]; then
		printf '    · dowód mutacji: wpis pulsu zniknął z cache’u\n'
	else
		printf '    ✗ MUTACJA NIE WESZŁA W ŻYCIE: puls nadal w cache’u (%s) — perturbacja nierozstrzygająca\n' "$stan_pulsu"
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

WSZYSTKIE="testy pusta_suita licznik pominiete statyka format sekrety hasla hasla_v2 nonce wzmacniacz lockfile vendor zamek sonda_bazy zdrowie tozsamosc puls zamrozenie biala_lista retencja obietnica sesja role_zamrozone logout_failsafe zrodlo_rol wymuszone_wylogowanie id_token_sesja"

if [ "${1:-}" = "--lista" ]; then
	printf 'Perturbacje: %s\n' "$WSZYSTKIE"
	exit 0
fi

WYBRANE="${*:-$WSZYSTKIE}"

if ! dc ps --status running --services 2>/dev/null | grep -q '^app$'; then
	echo "Stos '$PROJEKT' nie stoi — stawiam go." >&2

	# ŚWIADOMIE bez sprawdzania kodu wyjścia `up` — zasada 1 z nagłówka bramki:
	# pytamy o STAN, nie o kod wyjścia polecenia. Pierwsza wersja tej gałęzi
	# robiła `if ! dc up -d`, więc odmawiała startu, mimo że stos wstawał
	# poprawnie (`up` wraca niezerowo, gdy któryś kontener mignie jako
	# unhealthy w trakcie startu). Zmierzone: ręczne `up -d` → wszystko
	# `Healthy`, skryptowe → „nie udało się postawić stosu".
	dc up -d >/dev/null 2>&1 || true

	# O gotowości rozstrzyga sonda pytająca o STAN.
	GOTOWY=0

	for _ in $(seq 1 60); do
		if dc exec -T app php artisan gabinet:zdrowie --cichy >/dev/null 2>&1; then
			GOTOWY=1
			break
		fi

		sleep 2
	done

	if [ "$GOTOWY" -ne 1 ]; then
		echo "ODMOWA: stos '$PROJEKT' nie zgłosił zdrowia w 120 s." >&2
		exit 2
	fi

	dc exec -T app php artisan migrate --force >/dev/null 2>&1
	echo "Stos '$PROJEKT' gotowy." >&2
fi

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
		obietnica) p_obietnica ;;
		sesja) p_sesja_jawna ;;
		role_zamrozone) p_role_zamrozone ;;
		logout_failsafe) p_logout_failsafe ;;
		zrodlo_rol) p_zrodlo_rol ;;
		wymuszone_wylogowanie) p_wymuszone_wylogowanie ;;
		id_token_sesja) p_id_token_w_sesji ;;
		zamek) p_zamek ;;
		sonda_bazy) p_sonda_bazy ;;
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
