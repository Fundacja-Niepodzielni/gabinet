#!/usr/bin/env bash
# ===========================================================================
# skrypty/bramka.sh — WIĄŻĄCY runner bramki Gabinetu.
#
#   ./skrypty/bramka.sh              # pełny przebieg na czystym stosie
#   ./skrypty/bramka.sh --zostaw     # nie sprzątaj po sobie (debugowanie)
#   ./skrypty/bramka.sh --tylko-kod  # bez stawiania stosu (stos już stoi)
#   ./skrypty/bramka.sh --projekt X --przygotuj-srodowisko
#                                    # zbuduj plik środowiska efemerycznego dla
#                                    # projektu X, wypisz jego ścieżkę i wyjdź
#   ./skrypty/bramka.sh --projekt X --pokaz-srodowisko
#                                    # sama ścieżka tego pliku, nic nie buduje
#
# CI (.github/workflows/ci.yml) woła DOKŁADNIE ten skrypt — dzięki temu bramka
# lokalna i bramka na GitHubie nie mogą się rozjechać. Reguła przejęta z repo
# `konta` (scripts/ci-local.sh), gdzie się sprawdziła.
#
# ---------------------------------------------------------------------------
# ZASADY KONTROLI (lekcja zespołu helpdesku + dziennik makiety, rozdz. 15 i 23):
#
#  1. Kontrola pyta o STAN, nie o deklarację. Wersję bazy czytamy z żywego
#     zapytania, nie z `.env`; port z `docker inspect`, nie z `compose config`.
#  2. HTTP 200 to NIE tożsamość. Sprawdzamy znacznik WŁASNEJ aplikacji —
#     cudza usługa na zajętym porcie potrafi dać fałszywe zielone.
#  3. `docker compose port` przy wielu przypisaniach zwraca LOSOWE jedno.
#     Nie używamy go w kontrolach.
#  4. „Nic nie wystawione publicznie" sprawdzamy dwutorowo: deklaratywnie
#     (`docker inspect`) ORAZ aktywnie — każdy port × adres spoza loopbacku.
#  5. Bramkę testujemy na CZYSTYM KLONIE. Dlatego gotowość rozstrzygają SONDY
#     kontenerów, a nie kod wyjścia polecenia instalującego.
# ---------------------------------------------------------------------------
#
# BEZPIECZEŃSTWO: przebieg dostaje WŁASNY projekt compose (domyślnie
# `gabinet-bramka`), własny PREFIKS nazw (kontenery, sieć, wolumeny) i własne
# porty. Skrypt ODMAWIA startu, gdyby ktoś wskazał projekt `gabinet` — to stos
# dewelopera i jego danych nie kasujemy.
#
# SPRZĄTANIE — V-6 z rundy 5, poprawka instrukcji dla człowieka.
# Poprzedni nagłówek mówił: „sprzątanie to wyłącznie
# `docker compose -p <ten projekt> down -v`". To było NIEBEZPIECZNE: nazwy
# wolumenów i sieci biorą się z `GABINET_PREFIX`, a NIE z `-p`. Weryfikator
# wykonał tę instrukcję literalnie dla własnego projektu i zaczął kasować
# `gabinet-pg-data` — bazę dewelopera. Uratowało go tylko to, że stos akurat
# chodził („Resource is still in use").
#
# Poprawnie sprząta się ZAWSZE z prefiksem:
#   GABINET_PREFIX=<projekt> docker compose -p <projekt> down -v
# Od tej rundy `docker-compose.yml` WYMAGA `GABINET_PREFIX` (bez wartości
# domyślnej), więc pominięcie go kończy się odmową compose, nie kasowaniem.
# ZAKRES SPRZĄTACZKI — decyzja zapisana, nie zwyczaj (przegląd helpdesku):
#   kontenery + wolumeny + klon. NIC POZA TYM.
#
# W szczególności NIGDY `docker image prune`, `docker rmi` ani globalne
# `docker system/volume prune`. Obrazy są globalne dla DEMONA, nie dla
# projektu — sprzątaczka projektu efemerycznego, „sprzątając tylko po sobie",
# zabrałaby obraz spod stosu roboczego. Od tej rundy tag obrazu zawiera nazwę
# stosu (`${GABINET_PREFIX}-app:local`), więc obrazy się nie nadpisują — ale
# to nie znosi zakazu, bo `prune` kasuje po WIEKU i po braku referencji,
# nie po nazwie.
# ===========================================================================
set -uo pipefail

export MSYS_NO_PATHCONV=1
export MSYS2_ARG_CONV_EXCL='*'

KORZEN="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$KORZEN"

# Wspólna procedura licząca testy — ten sam kod, którym mierzą perturbacje.
. "$KORZEN/skrypty/licz-testy.sh"

PROJEKT_DEWELOPERA="gabinet"
PROJEKT="${GABINET_BRAMKA_PROJEKT:-gabinet-bramka}"
PORT_HTTP="${GABINET_BRAMKA_PORT_HTTP:-8099}"
PORT_PG="${GABINET_BRAMKA_PORT_POSTGRES:-55443}"
PORT_REDIS="${GABINET_BRAMKA_PORT_REDIS:-56390}"
# Plik środowiska WYŁĄCZNIE dla tego przebiegu. Nigdy `.env` dewelopera.
#
# UWAGA: `PLIK_ENV` NIE MOŻE powstać tutaj — `--projekt` jest parsowany DOPIERO
# niżej, więc nazwa brałaby się z wartości domyślnej i ignorowała argument.
# Zmierzone przez weryfikatora rundy 6 (R6A-10): przebieg `--projekt gabinet-r6a`
# zbudował `.env.bramka.gabinet-bramka`. Skutek jest gorszy niż mylna nazwa —
# dwa przebiegi o RÓŻNYCH projektach dzielą JEDEN plik z wygenerowanym `APP_KEY`
# i `DB_PASSWORD`, a zamek (liczony per projekt) ich nie rozdziela. Dotyczy to
# także `perturbacje.sh`, który woła `bramka.sh --projekt … --tylko-kod`.
#
# Definicja przeniesiona POD pętlę parsującą argumenty.

ZNACZNIK_APLIKACJI="gabinet-api-v1"

# PODŁOGI — JEDNO ŹRÓDŁO dla bramki I dla perturbacji (R6B-12).
#
# Do 12.08 liczby stały tutaj, a `perturbacje.sh` dowodziły podłogi o INNEJ,
# niższej wartości (100 testów / 300 asercji). Perturbacja dowodziła więc
# czegoś o liczbie 100, a nie o kontroli, którą ten skrypt wykonuje. Rozjazd
# urósł po podniesieniu podłóg 09.08 — naprawa jednej kontroli powiększyła
# lukę w drugiej. Wartości i cała historia ich podnoszenia: `podlogi.sh`.
. "$KORZEN/skrypty/podlogi.sh"
ZOSTAW=0
TYLKO_KOD=0
POKAZ_ZAMEK=0
# R6B-16: `perturbacje.sh` montowało `./.env` DEWELOPERA (z prawdziwymi
# sekretami), bo nie budowało własnego pliku środowiska ani nie podawało
# `--env-file`. Zamiast pisać tam DRUGI opis tej samej rzeczy — dwa opisy
# jednej rzeczy rozjeżdżają się po cichu — perturbacje wołają TEN mechanizm.
PRZYGOTUJ_SRODOWISKO=0
POKAZ_SRODOWISKO=0

while [ $# -gt 0 ]; do
	case "$1" in
		--zostaw) ZOSTAW=1; shift ;;
		--tylko-kod) TYLKO_KOD=1; ZOSTAW=1; shift ;;
		--projekt) PROJEKT="$2"; shift 2 ;;
		--pokaz-zamek) POKAZ_ZAMEK=1; shift ;;
		--przygotuj-srodowisko) PRZYGOTUJ_SRODOWISKO=1; shift ;;
		--pokaz-srodowisko) POKAZ_SRODOWISKO=1; shift ;;
		-h|--help) sed -n '2,36p' "$0"; exit 0 ;;
		*) echo "nieznany argument: $1" >&2; exit 2 ;;
	esac
done

if [ "$PROJEKT" = "$PROJEKT_DEWELOPERA" ]; then
	echo "ODMOWA: projekt bramki nie może nazywać się '$PROJEKT_DEWELOPERA' — to stos dewelopera." >&2
	exit 2
fi

# Plik środowiska tego przebiegu — DOPIERO TU, gdy `$PROJEKT` jest już ostateczny
# (R6A-10). Każdy projekt dostaje własny plik, więc dwa równoległe przebiegi
# o różnych nazwach nie mielą jednego zestawu poświadczeń.
PLIK_ENV="$KORZEN/.env.bramka.$PROJEKT"

# O-5: zamek na równoległy przebieg TEGO SAMEGO projektu.
#
# Dwa przebiegi mielą jedną bazę `gabinet_test` i produkują fałszywe czerwone
# („duplicate key ... migrations") — niezależny weryfikator potknął się o to sam
# i musiał unieważnić własny wynik.
#
# `mkdir`, nie `flock`: flock NIE ISTNIEJE w Git Bash na Windows. Pierwsza
# wersja zamka używała flocka i po cichu nie chroniła niczego — zmierzone:
# dwa równoległe przebiegi przeszły przez zamek i oba skończyły się
# `BRAMKA CZERWONA — 2 nieudanych`. `mkdir` jest atomowy w każdym systemie
# plików i nie wymaga żadnego narzędzia poza powłoką.
#
# Nazwa ścieżki: U-2 z rundy 3. Wzorzec `gabinet-bramka-${PROJEKT}` dawał dla
# projektu `gabinet-bramka-perturbacja` ścieżkę z podwojonym prefiksem, więc
# perturbacja zamka tworzyła INNY plik niż ten, o który pytała bramka — i
# „przechodziła" na czerwonym z zupełnie innego powodu. Ścieżkę wypisuje teraz
# sam skrypt (`--pokaz-zamek`); nikt jej drugi raz nie składa ręcznie.
ZAMEK="${TMPDIR:-/tmp}/gabinet-bramka.${PROJEKT}.zamek"

# Osobny kod wyjścia dla zajętego zamka. Bez niego perturbacja stwierdzała
# tylko „bramka czerwona" — a czerwona bywa z kilkunastu powodów naraz.
KOD_ZAMEK_ZAJETY=3

# Ta sama zasada co przy zamku: ścieżkę pliku środowiska wypisuje SAM SKRYPT,
# nikt jej nie składa drugi raz ręcznie. Przy zamku ręczne składanie dało U-2
# (perturbacja zajmowała plik, o który nikt nie pytał).
if [ "$POKAZ_SRODOWISKO" -eq 1 ]; then
	printf '%s\n' "$PLIK_ENV"
	exit 0
fi

if [ "$POKAZ_ZAMEK" -eq 1 ]; then
	printf '%s\n' "$ZAMEK"
	exit 0
fi

zwolnij_zamek() {
	# Tylko właściciel sprząta. Przy przejęciu porzuconego zamka poprzedni
	# właściciel już nie żyje, więc nie ma kto skasować cudzego katalogu.
	if [ -d "$ZAMEK" ] && [ "$(cat "$ZAMEK/pid" 2>/dev/null || echo '')" = "$$" ]; then
		rm -rf "$ZAMEK"
	fi
}

# `trap ... INT TERM` sam z siebie NIE kończy skryptu (U-5): po powrocie
# z procedury bash wraca do przerwanej instrukcji i bramka mieli dalej.
# Dlatego sygnały mają własną procedurę, która jawnie wychodzi.
przerwano() { zwolnij_zamek; trap - EXIT; exit 130; }
trap zwolnij_zamek EXIT
trap przerwano INT TERM

if mkdir "$ZAMEK" 2>/dev/null; then
	echo "$$" > "$ZAMEK/pid"
else
	# Zamek po przerwanym przebiegu nie może blokować w nieskończoność:
	# jeśli proces właściciela już nie żyje, przejmujemy zamek.
	#
	# U-2: sekwencja „odczytaj PID → kill -0 → nadpisz PID" NIE JEST atomowa.
	# Dwa przebiegi startujące jednocześnie po porzuconym zamku obydwa widziały
	# martwego właściciela i obydwa wpisywały swój PID — zamek przepuszczał
	# dokładnie to, przed czym miał chronić. Samo przejęcie jest więc objęte
	# drugim, atomowym `mkdir`: procedurę wykonuje ten, kto go zdobył.
	PRZEJECIE="${ZAMEK}.przejecie"

	if mkdir "$PRZEJECIE" 2>/dev/null; then
		STARY_PID="$(cat "$ZAMEK/pid" 2>/dev/null || echo '')"

		if [ -n "$STARY_PID" ] && kill -0 "$STARY_PID" 2>/dev/null; then
			rmdir "$PRZEJECIE" 2>/dev/null || true
			echo "ODMOWA: bramka dla projektu '$PROJEKT' już biegnie (PID $STARY_PID)." >&2
			echo "Równoległe przebiegi mielą tę samą bazę testową i dają fałszywe wyniki." >&2
			exit "$KOD_ZAMEK_ZAJETY"
		fi

		echo "UWAGA: przejmuję porzucony zamek po PID ${STARY_PID:-nieznanym}." >&2
		echo "$$" > "$ZAMEK/pid"
		rmdir "$PRZEJECIE" 2>/dev/null || true
	else
		# Ktoś inny właśnie przejmuje zamek — to znaczy, że przebieg trwa.
		echo "ODMOWA: bramka dla projektu '$PROJEKT' jest właśnie przejmowana przez inny przebieg." >&2
		exit "$KOD_ZAMEK_ZAJETY"
	fi
fi

KROK=0
NIEUDANE=0

krok() { KROK=$((KROK + 1)); printf '\n=== [%d] %s\n' "$KROK" "$*"; }
zle()  { NIEUDANE=$((NIEUDANE + 1)); printf '    ^ KROK NIEUDANY\n'; }

sciezka_hosta() {
	if command -v cygpath >/dev/null 2>&1; then cygpath -w "$KORZEN/$1"; else echo "$KORZEN/$1"; fi
}

sciezka_hosta_pliku() {
	if command -v cygpath >/dev/null 2>&1; then cygpath -w "$1"; else echo "$1"; fi
}

# Prefiks nazw kontenerów/sieci/wolumenów. Bez niego drugi stos z tego samego
# pliku zderza się z zasobami dewelopera po NAZWIE, mimo innego projektu —
# a alias `postgres` rozwiązuje się wtedy losowo na jeden z dwóch serwerów.
dc() {
	GABINET_PREFIX="$PROJEKT" \
	GABINET_PORT_HTTP="$PORT_HTTP" \
	GABINET_PORT_POSTGRES="$PORT_PG" \
	GABINET_PORT_REDIS="$PORT_REDIS" \
	GABINET_PLIK_ENV="$(sciezka_hosta_pliku "$PLIK_ENV")" \
		docker compose --env-file "$(sciezka_hosta_pliku "$PLIK_ENV")" \
			-p "$PROJEKT" -f "$(sciezka_hosta docker-compose.yml)" "$@"
}

# ---------------------------------------------------------------------------
# .env — bramka NIGDY nie nadpisuje pliku dewelopera.
# ---------------------------------------------------------------------------
# PODMIANA W PLIKU ŚRODOWISKA Z ODCZYTEM ZWROTNYM (R6B-7).
#
# `sed -i` na wzorcu, którego w pliku NIE MA, kończy się KODEM 0 i nie zmienia
# nic. Zmierzone:
#
#   plik: APP_KEY=stare / INNY=x
#   sed -i "s|^GABINET_PORT_HTTP=.*|...|"  → kod 0, klucza w pliku: 0
#
# Czyli „podmiana wykonana" i „podmiana nie trafiła" dawały IDENTYCZNY sygnał —
# ta sama klasa co sonda HTTP nad Postgresem i co `unlink` bez odczytu.
#
# Sprawdzamy TREŚĆ PO, nie kod wyjścia. Odmawiamy też przy DWÓCH trafieniach:
# `sed` podmieniłby oba, a to już jest inna zmiana niż zamierzona (kierunek 0).
ustaw_w_env() {
	local klucz="$1" wartosc="$2" ile po

	ile="$(grep -c "^${klucz}=" "$PLIK_ENV" || true)"

	if [ "$ile" -eq 0 ]; then
		echo "    ODMOWA: w $(basename "$PLIK_ENV") NIE MA klucza ${klucz} — podmiana nie miałaby w co trafić"
		zle
		return 1
	fi
	if [ "$ile" -gt 1 ]; then
		echo "    ODMOWA: klucz ${klucz} występuje ${ile} razy — podmiana zmieniłaby WIĘCEJ, niż zamierzono"
		zle
		return 1
	fi

	sed -i "s|^${klucz}=.*|${klucz}=${wartosc}|" "$PLIK_ENV"

	# ODCZYT ZWROTNY — „polecenie się wykonało" nie znaczy „plik ma treść".
	po="$(sed -n "s|^${klucz}=||p" "$PLIK_ENV")"
	if [ "$po" != "$wartosc" ]; then
		echo "    ODMOWA: po podmianie ${klucz} ma wartość '${po}', a miało mieć '${wartosc}'"
		zle
		return 1
	fi
}

przygotuj_env() {
	# DYSCYPLINA WERYFIKACJI USŁUGI STANOWEJ (korekta architekta, 08.08).
	#
	# Poprzednia wersja robiła: „jeśli `.env` istnieje — używam istniejącego".
	# Skutek był podwójny i oba razy zły:
	#
	#  1. POMIAR. Na mojej maszynie bramka mieliła `.env` DEWELOPERA, a na
	#     czystym klonie — plik wygenerowany z `.env.example`. To dwa RÓŻNE
	#     środowiska pomiarowe, więc „u mnie zielone" nie znaczyło nic o klonie.
	#     Dokładnie tak powstało V-2: perturbacja `sesja-jawna` była bezczynna
	#     na klonie (`SESSION_ENCRYPT=true` z `.env.example`), a u mnie
	#     działała, bo mój `.env` tej linii nie miał. Deklarowałem „42 kontrole"
	#     z jedynego środowiska, w którym wychodzą.
	#
	#  2. SEKRETY. Przebieg weryfikacyjny nie ma prawa dotykać prawdziwych
	#     poświadczeń. U zespołu helpdesku weryfikator skopiował `.env`
	#     z sekretami do katalogu tymczasowego — near-miss.
	#
	# Dlatego: WŁASNY plik środowiska, budowany od zera przy KAŻDYM przebiegu,
	# wyłącznie z `.env.example` (definicja w repozytorium) i z sekretami
	# generowanymi na miejscu. `.env` dewelopera nie jest nawet czytany.
	echo "    buduję środowisko efemeryczne: $(basename "$PLIK_ENV")"
	cp .env.example "$PLIK_ENV"

	local haslo klucz
	haslo="$(openssl rand -hex 16)"
	klucz="base64:$(openssl rand -base64 32)"

	# `|` jako separator: klucz base64 zawiera `/`.
	ustaw_w_env DB_PASSWORD "$haslo"
	ustaw_w_env APP_KEY     "$klucz"

	# Prefiks i porty tego przebiegu — inaczej compose weźmie je z `.env.example`
	# i stos bramki zderzy się z deweloperskim po NAZWIE zasobu.
	ustaw_w_env GABINET_PREFIX        "$PROJEKT"
	ustaw_w_env GABINET_PORT_HTTP     "$PORT_HTTP"
	ustaw_w_env GABINET_PORT_POSTGRES "$PORT_PG"
	ustaw_w_env GABINET_PORT_REDIS    "$PORT_REDIS"
}

# ---------------------------------------------------------------------------
# TRYB „TYLKO ZBUDUJ ŚRODOWISKO" — punkt wejścia dla `perturbacje.sh` (R6B-16).
#
# Wypisuje na STDOUT wyłącznie ŚCIEŻKĘ zbudowanego pliku; cała gadanina idzie
# na STDERR, żeby wołający mógł podstawić wynik wprost do zmiennej.
#
# Werdykt bierzemy z LICZNIKA `NIEUDANE`, nie z kodu wyjścia `przygotuj_env`:
# ten ostatni jest kodem OSTATNIEGO `ustaw_w_env`, więc odmowa przy
# `DB_PASSWORD` byłaby niewidoczna, gdyby pozostałe klucze przeszły.
#
# Zamek bramki jest już w tym miejscu zdobyty i zwolni go `trap` przy wyjściu.
# Świadomie nie omijam zamka: budowa pliku środowiska nadpisuje poświadczenia
# tego projektu, więc równoległy przebieg TEGO SAMEGO projektu ma zostać
# odrzucony tak samo jak przy pełnej bramce.
if [ "$PRZYGOTUJ_SRODOWISKO" -eq 1 ]; then
	przygotuj_env >&2

	if [ "$NIEUDANE" -ne 0 ]; then
		echo "ODMOWA: nie udało się zbudować pliku środowiska ($NIEUDANE odmów)." >&2
		exit 2
	fi

	if [ ! -s "$PLIK_ENV" ]; then
		echo "ODMOWA: $PLIK_ENV nie powstał albo jest pusty." >&2
		exit 2
	fi

	printf '%s\n' "$PLIK_ENV"
	exit 0
fi

# ---------------------------------------------------------------------------

if [ "$TYLKO_KOD" -eq 0 ]; then
	# KOLEJNOŚĆ MA ZNACZENIE: plik środowiska powstaje PRZED pierwszym
	# wywołaniem `dc`. Odwrotna kolejność sprzątała stos plikiem z POPRZEDNIEGO
	# przebiegu (albo bez pliku w ogóle), więc wolumen postgresa przeżywał
	# z INNYM hasłem niż świeżo wygenerowane — i `app` wisiał jako niezdrowy
	# do końca limitu. Zmierzone: 16 minut w pętli sond, zero komunikatu
	# o przyczynie.
	krok "przygotowanie środowiska efemerycznego"
	przygotuj_env || zle

	krok "sprzątanie po poprzednim przebiegu"
	dc down -v --remove-orphans >/dev/null 2>&1 || true

	# PYTAMY O STAN, NIE O KOD WYJŚCIA SPRZĄTANIA (zasada 1 z nagłówka).
	#
	# `down -v` MILCZY, gdy wolumen jest zajęty — a wtedy postgres startuje na
	# STARYM woluminie i nie reinicjalizuje hasła. Skutek wychodzi dopiero
	# kilkanaście minut później jako nieczytelny timeout sondy zdrowia:
	# zmierzone dwa razy, `app` w pętli „password authentication failed"
	# przy zielonym postgresie. Kod wyjścia sprzątania niczego o tym nie mówił.
	for WOLUMEN in "${PROJEKT}-pg-data" "${PROJEKT}-redis-data"; do
		if docker volume inspect "$WOLUMEN" >/dev/null 2>&1; then
			echo "    wolumen $WOLUMEN przeżył down -v — usuwam wprost"
			docker volume rm -f "$WOLUMEN" >/dev/null 2>&1 || true
		fi

		if docker volume inspect "$WOLUMEN" >/dev/null 2>&1; then
			echo "    NIE UDAŁO SIĘ usunąć $WOLUMEN — postgres wystartuje ze starym hasłem"
			echo "    (zatrzymaj kontenery trzymające ten wolumen i uruchom ponownie)"
			zle
		fi
	done

	krok "budowanie obrazu aplikacji"
	dc build app || zle

	krok "start stosu"
	# Zależności przyjeżdżają z OBRAZU i montują się jako nazwany wolumen
	# (Dockerfile + docker-compose.yml). Entrypoint generuje tylko autoloader.
	dc up -d || zle

	krok "czekam, aż KAŻDA usługa zgłosi zdrowie (stan, nie kod wyjścia \`up\`)"
	# Świadomie BEZ `--wait`. Zmierzone na czystym klonie: `up --wait` wraca
	# z błędem („container ... exited (0)"), gdy którykolwiek kontener wyjdzie
	# przejściowo w trakcie startu — mimo że `restart: unless-stopped` zaraz go
	# podnosi i stos po chwili jest w komplecie zdrowy. Kod wyjścia `up` jest
	# więc DEKLARACJĄ o jednej chwili; my pytamy o STAN aż do zbieżności.
	USLUGI="postgres redis app web horizon scheduler"
	STOS_OK=0

	for _ in $(seq 1 120); do
		NIEZDROWE=""

		for USLUGA in $USLUGI; do
			STAN="$(docker inspect "${PROJEKT}-${USLUGA}" \
				--format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' 2>/dev/null || echo brak)"
			case "$STAN" in
				healthy|running) ;;
				*) NIEZDROWE="$NIEZDROWE ${USLUGA}=${STAN}" ;;
			esac
		done

		if [ -z "$NIEZDROWE" ]; then
			STOS_OK=1
			echo "    wszystkie usługi zdrowe:$(printf ' %s' $USLUGI)"
			break
		fi

		sleep 5
	done

	[ "$STOS_OK" -eq 1 ] || { echo "    nie zbiegło się:$NIEZDROWE"; zle; }

	krok "stan: vendor/autoload.php realnie istnieje"
	if dc exec -T app test -f vendor/autoload.php; then
		echo "    vendor/autoload.php istnieje"
	else
		echo "    vendor/autoload.php NIE powstał — instalacja nie doszła do końca"
		zle
	fi

	krok "skrypty bramki DAJĄ SIĘ URUCHOMIĆ (nie tylko sparsować)"
# V-5 z rundy 5: `skrypty-uruchamialne.sh` powstał dokładnie po to, żeby łapać
# literówki w nazwach scenariuszy i podkomend — i NIE BYŁ WOŁANY PRZEZ NIC.
# Ani przez bramkę, ani przez CI, ani przez dokumentację. Kontrola, której
# nikt nie uruchamia, jest wg reguły tego projektu nieistniejąca — a ta od
# dawna wiedziała o realnej wadzie i nikt tego nie widział.
bash "$KORZEN/skrypty/skrypty-uruchamialne.sh" || zle

krok 'zależności zgodne z composer.lock (rozjazd wolumenu vendor)'
	# O-4: wolumen `vendor` NIE odświeża się z przebudowanego obrazu. Podbicie
	# lockfile'a (np. łatka bezpieczeństwa) bez `down -v` było dotąd ignorowane
	# BEZ ŻADNEGO SYGNAŁU — README ostrzegał prozą, nic tego nie egzekwowało.
	dc exec -T app composer validate --strict --no-check-publish || zle

	SUCHY="$(dc exec -T app composer install --dry-run --no-scripts 2>&1)"
	if printf '%s' "$SUCHY" | grep -q 'Nothing to install, update or remove'; then
		echo "    zainstalowane zależności zgadzają się z composer.lock"
	else
		echo '    ROZJAZD: wolumen vendor nie odpowiada composer.lock'
		printf '%s
' "$SUCHY" | tail -15
		echo "    naprawa: docker compose down -v && docker compose build app && docker compose up -d"
		zle
	fi

	# U-8: powyższe czyta wyłącznie METADANE (`composer.lock` kontra
	# `vendor/composer/installed.json`). Skasowanie zawartości pakietu
	# w wolumenie `vendor` przechodziło przez ten krok bez słowa. Pytamy więc
	# o STAN DYSKU. Podmiana treści istniejącego pliku pozostaje niewykryta —
	# to dług O-7, nie przeoczenie.
	dc exec -T app php /srv/gabinet/skrypty/zaleznosci-obecne.php || zle

	krok "migracje"
	dc exec -T app php artisan migrate --force || zle

	krok "migracje odwracalne (w dół i z powrotem w górę)"
	# Wymóg bramki F1. Migracja bez działającego `down()` to migracja, której
	# nie da się wycofać na produkcji — a wtedy jedyną drogą wstecz jest
	# odtworzenie z kopii.
	if dc exec -T app php artisan migrate:rollback --force >/dev/null 2>&1 		&& dc exec -T app php artisan migrate --force >/dev/null 2>&1; then
		echo "    rollback i ponowna migracja przeszły"
	else
		echo "    migracje NIE są odwracalne"
		zle
	fi

	krok "stan aplikacji: framework + baza + Redis + cache (nie deklaracje)"
	dc exec -T app php artisan gabinet:zdrowie || zle

	krok "tożsamość usługi pod portem $PORT_HTTP (nie sam kod 200)"
	ODPOWIEDZ="$(curl -fsS --max-time 10 "http://127.0.0.1:${PORT_HTTP}/api/wersja" 2>&1)"
	if printf '%s' "$ODPOWIEDZ" | grep -q "$ZNACZNIK_APLIKACJI"; then
		echo "    znacznik '$ZNACZNIK_APLIKACJI' obecny — to NASZA usługa"
	else
		echo "    pod portem odpowiada coś innego niż Gabinet: $ODPOWIEDZ"
		zle
	fi

	krok "Horizon widzi Redis"
	# Horizon melduje się w Redisie dopiero po starcie nadzorcy głównego,
	# a to bywa kilka sekund PO tym, jak kontener zgłosi się jako zdrowy.
	HORIZON_OK=0
	for _ in $(seq 1 20); do
		HORIZON_WYNIK="$(dc exec -T app php artisan horizon:status 2>&1)"
		if printf '%s' "$HORIZON_WYNIK" | grep -q 'is running'; then
			HORIZON_OK=1
			echo "    $(printf '%s' "$HORIZON_WYNIK" | tr -s ' \n' ' ')"
			break
		fi
		sleep 3
	done
	[ "$HORIZON_OK" -eq 1 ] || { printf '%s\n' "$HORIZON_WYNIK"; zle; }

	krok "harmonogram realnie WYKONAŁ zadanie (puls, nie sam proces)"
	PULS_OK=0
	for _ in $(seq 1 40); do
		if dc exec -T app php artisan gabinet:puls --sprawdz >/dev/null 2>&1; then
			PULS_OK=1
			echo "    $(dc exec -T app php artisan gabinet:puls --sprawdz 2>&1 | tr -d '\r')"
			break
		fi
		sleep 5
	done
	[ "$PULS_OK" -eq 1 ] || { echo "    harmonogram nie zapisał pulsu"; zle; }

	# --- „nic nie wystawione publicznie" — dwutorowo ------------------------
	krok "porty: deklaratywnie (docker inspect) — wszystko na 127.0.0.1"
	# ŚWIADOMIE bez `docker compose port`: przy wielu przypisaniach zwraca
	# losowe jedno i potrafi zameldować zieleń o niesprawdzonym porcie.
	ZLE_BINDY="$(docker inspect $(dc ps -q) \
		--format '{{.Name}} {{range $p, $b := .HostConfig.PortBindings}}{{range $b}}{{$p}}->{{.HostIp}} {{end}}{{end}}' 2>/dev/null \
		| grep -vE '(^\S+ *$)|->127\.0\.0\.1' || true)"
	if [ -z "$ZLE_BINDY" ]; then
		echo "    każde przypisanie portu wskazuje 127.0.0.1"
	else
		echo "    porty wystawione poza loopback:"; printf '%s\n' "$ZLE_BINDY"
		zle
	fi

	krok "porty: aktywnie — każdy port × adres hosta spoza loopbacku"
	# SONDA DOPASOWANA DO PROTOKOŁU, NIE JEDNA DLA WSZYSTKIEGO (R6B-11).
	#
	# Do 09.08 stało tu zapytanie HTTP-em na KAŻDY port, w tym na Postgresa
	# i Redisa. Zmierzone na loopbacku, bez wystawiania czegokolwiek:
	#
	#   Postgres NASŁUCHUJĄCY na 127.0.0.1:55442
	#     sonda HTTP (curl)  → NIE WYKRYŁA
	#     próba połączenia TCP → WYKRYŁA
	#     kontrola pozytywna sondy: TCP na martwym porcie 59999 → nie połączyła
	#
	# Baza nie mówi po HTTP, więc pytanie HTTP-em o bazę odpowiada na inne
	# pytanie, niż zadajemy. Kontrola „nic nie wystawione" była zielona także
	# przy wystawionej bazie danych — jedna wartość, dwa światy.
	#
	# Próba połączenia TCP rozstrzyga to, o co naprawdę pytamy: czy KTOKOLWIEK
	# z sieci może nawiązać połączenie z tym portem.
	#
	# Trzy wyniki, nie dwa — „nie wiem" NIE JEST tym samym co „nie słucha":
	#   OSIAGALNY  — połączenie nawiązane          → wystawione, czerwone
	#   ODMOWA     — odrzucone od razu             → nie słucha, w porządku
	#   NIEZNANY   — cisza do wyczerpania czasu    → traktujemy JAK WYSTAWIONE
	port_z_sieci() {
		local adres="$1" port="$2" kod=0

		timeout 3 bash -c "echo > /dev/tcp/${adres}/${port}" 2>/dev/null || kod=$?

		case "$kod" in
			0)   printf 'OSIAGALNY' ;;
			124) printf 'NIEZNANY' ;;   # `timeout` zabił próbę — pakiety mogą być filtrowane
			*)   printf 'ODMOWA' ;;
		esac
	}

	ADRES_LAN="${GABINET_ADRES_LAN:-$(php -r 'echo gethostbyname(gethostname());' 2>/dev/null)}"
	case "$ADRES_LAN" in
		127.*|""|0.0.0.0|*[!0-9.]*)
			# NIEZNANE → NIE „pominięte". Poprzednia wersja wypisywała
			# „pominięte" i szła dalej, więc brak pomiaru wyglądał identycznie
			# jak pomiar udany. To jest ta sama klasa co sonda wyżej.
			echo "    NIEROZSTRZYGNIĘTE: nie ustalono adresu spoza loopbacku (mam: '${ADRES_LAN:-brak}')."
			echo "    Kontrola bezpieczeństwa, której NIE WYKONANO, nie jest kontrolą zdaną."
			echo "    Podaj adres jawnie: GABINET_ADRES_LAN=<adres> ./skrypty/bramka.sh"
			zle
			;;
		*)
			OSIAGALNE=""; NIEROZSTRZYGNIETE_PORTY=""
			for PORT in "$PORT_HTTP" "$PORT_PG" "$PORT_REDIS"; do
				case "$(port_z_sieci "$ADRES_LAN" "$PORT")" in
					OSIAGALNY) OSIAGALNE="$OSIAGALNE $PORT" ;;
					NIEZNANY)  NIEROZSTRZYGNIETE_PORTY="$NIEROZSTRZYGNIETE_PORTY $PORT" ;;
				esac
			done

			if [ -n "$OSIAGALNE" ]; then
				echo "    OSIĄGALNE Z SIECI (połączenie TCP nawiązane):$OSIAGALNE"
				zle
			fi
			if [ -n "$NIEROZSTRZYGNIETE_PORTY" ]; then
				echo "    NIEROZSTRZYGNIĘTE (cisza, możliwe filtrowanie):$NIEROZSTRZYGNIETE_PORTY"
				echo "    Nieznane traktujemy JAK WYSTAWIONE — inaczej zapora udaje bezpieczeństwo."
				zle
			fi
			if [ -z "$OSIAGALNE" ] && [ -z "$NIEROZSTRZYGNIETE_PORTY" ]; then
				echo "    z adresu $ADRES_LAN każdy port ODMAWIA połączenia ($PORT_HTTP, $PORT_PG, $PORT_REDIS)"
			fi
			;;
	esac
fi

krok "format kodu (Pint)"
dc exec -T app ./vendor/bin/pint --test || zle

krok "statyka (Larastan, level max)"
dc exec -T app ./vendor/bin/phpstan analyse --memory-limit=1G --no-progress || zle

krok "testy (Pest)"
# Mierzymy czas: ZBYT SZYBKIE zielone jest równie podejrzane co czerwone
# (lekcja zespołu hubu — u nich 51 s zamiast 10 min oznaczało, że kontrola
# nic nie robiła).
SEKUNDY_PRZED=$(date +%s)
WYNIK_TESTOW="$(dc exec -T app ./vendor/bin/pest 2>&1)"
KOD_TESTOW=$?
CZAS_TESTOW=$(( $(date +%s) - SEKUNDY_PRZED ))
printf '%s
' "$WYNIK_TESTOW"
echo "    czas testów: ${CZAS_TESTOW}s"
[ "$KOD_TESTOW" -eq 0 ] || zle

krok "testy realnie SIĘ WYKONAŁY (podłoga: ${MINIMUM_TESTOW})"
# Zasada D-0013 (zespół hubu): „asercja bez dowodu, że umie zaświecić na
# czerwono, jest traktowana jak nieistniejąca". U nich PUSTA SUITA dawała
# zielone CI przy ZERO wykonanych testach — `pest` bez testów kończy się
# kodem 0, więc sam kod wyjścia niczego nie dowodzi.
#
# Dlatego liczymy testy i porównujemy z podłogą. Podłoga rośnie razem
# z suitą; jej obniżenie ma być świadomą zmianą w repozytorium, a nie
# efektem ubocznym skasowanego pliku.
# Usuwamy PEŁNE sekwencje ANSI, nie sam znak ESC. Pest koloruje wynik nawet
	# przy NO_COLOR, a `[32;1m` zawiera cyfry — parser bez tego czyszczenia
	# wyłuskiwał „39" albo zero zamiast liczby testów. Zmierzone: CI zapaliło
	# się na czerwono przy 107 zielonych testach, bo kontrola widziała 0.
	# Sumujemy KAŻDY stan z wiersza „Tests:", nie tylko `passed` (U-6 z rundy 3):
	# przy „1 failed, 135 passed" wzorzec szukający liczby tuż przed słowem
	# „passed" nie trafiał w nic i podłoga widziała ZERO wykonanych testów —
	# meldowała „suita się nie uruchomiła", choć uruchomiła się w całości,
	# a diagnoza kierowała w zupełnie złe miejsce.
	LICZBA_TESTOW="$(policz_testy "$WYNIK_TESTOW")"
	LICZBA_POMINIETYCH="$(policz_pominiete "$WYNIK_TESTOW")"
	LICZBA_ASERCJI="$(policz_asercje "$WYNIK_TESTOW")"
LICZBA_TESTOW="${LICZBA_TESTOW:-0}"

echo "    pominięte (nie liczą się do podłogi): $LICZBA_POMINIETYCH"

if [ "$LICZBA_TESTOW" -ge "$MINIMUM_TESTOW" ]; then
	echo "    WYKONANO $LICZBA_TESTOW testów (podłoga: $MINIMUM_TESTOW)"
else
	echo "    wykonano tylko $LICZBA_TESTOW testów przy podłodze $MINIMUM_TESTOW"
	echo "    — suita skurczyła się, została pominięta albo w ogóle się nie uruchomiła"
	zle
fi

# W-4: drugi sygnał, niezależny od pierwszego. Liczbę testów da się nadmuchać
# (pominięte wliczały się do podłogi, aż weryfikator pokazał zieloną bramkę
# przy 151 pominiętych i ZERZE asercji). Asercji nie da się nadmuchać, nie
# wykonując ich.
if [ "$LICZBA_ASERCJI" -ge "$MINIMUM_ASERCJI" ]; then
	echo "    sprawdzono $LICZBA_ASERCJI asercji (podłoga: $MINIMUM_ASERCJI)"
else
	echo "    sprawdzono tylko $LICZBA_ASERCJI asercji przy podłodze $MINIMUM_ASERCJI"
	echo "    — testy mogły się „wykonać\" bez sprawdzenia czegokolwiek"
	zle
fi

krok "sekrety (gitleaks) — ten sam skan co w CI"
# Skanujemy tryb git (śledzone pliki + historia), czyli to, co realnie
# trafia do repozytorium. `.env` dewelopera jest gitignorowany i nie wchodzi.
if docker run --rm -v "$(cygpath -w "$KORZEN" 2>/dev/null || echo "$KORZEN"):/repo" -w /repo \
	zricethezav/gitleaks:latest detect --source=/repo --config=/repo/.gitleaks.toml \
	--no-banner --redact 2>&1 | tail -3; then
	echo "    bez wycieków"
else
	# KOMUNIKAT MA UCZYĆ WŁAŚCIWEJ NAPRAWY (odpowiedź architekta na D-1, 09.08).
	#
	# Obie drogi do czerwieni w katalogu raportowym — zapomniany wyjątek i
	# niezredagowany cytat — kończą się tak samo, więc kontrola jest fail-closed
	# i to jest dobra wiadomość. Niebezpieczny jest ODRUCH: najtańszą reakcją na
	# „leaks found" jest dopisanie wyjątku, a nie usunięcie przyczyny. Jedno
	# zdanie tutaj zamienia domyślny odruch z rozluźniania kontroli na naprawę.
	echo "    ────────────────────────────────────────────────────────────────"
	echo "    Trafienie w katalogu raportowym (docs/…)? Zanim dopiszesz wyjątek:"
	echo "    · jeśli to ZACYTOWANY IDENTYFIKATOR (sesja, skrót, klucz) — SKRÓĆ GO."
	echo "      Wartość dowodowa raportu jest w RELACJI między odczytami, nie"
	echo "      w pełnej wartości; pełny identyfikator w dokumencie to sam w sobie"
	echo "      drobny wyciek. Skrócenie usuwa PRZYCZYNĘ."
	echo "    · wyjątek w .gitleaks.toml dopisuj TYLKO dla historii, której nie da"
	echo "      się już zmienić — wąsko (jedna reguła, jedna ścieżka) i z przynętą"
	echo "      dowodzącą, że skaner dalej łapie prawdziwy sekret."
	echo "    ────────────────────────────────────────────────────────────────"
	zle
fi

if [ "$ZOSTAW" -eq 0 ]; then
	krok "sprzątanie (down -v, wyłącznie projekt $PROJEKT)"
	dc down -v --remove-orphans >/dev/null 2>&1 || true
fi

printf '\n'
if [ "$NIEUDANE" -eq 0 ]; then
	echo "BRAMKA OK — $KROK kroków, 0 nieudanych"
	exit 0
fi

echo "BRAMKA CZERWONA — $NIEUDANE nieudanych kroków z $KROK"
exit 1
