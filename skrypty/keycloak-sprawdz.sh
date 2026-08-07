#!/usr/bin/env bash
# ===========================================================================
# skrypty/keycloak-sprawdz.sh — sonda integracji z Kontami Niepodzielni.
#
# Sprawdza to, czego NIE da się udowodnić testem jednostkowym: że ŻYWY IdP jest
# osiągalny, zwraca publicznego issuera, a nasza walidacja przyjmuje i odrzuca
# PRAWDZIWY token z właściwego powodu.
#
# Wymaga stojącego stosu `konta`:
#   cd ../niepodzielni-konta/infra && docker compose up -d && ./smoke.sh
#   cd ../realm && ./import.sh && ./import-fixtures.sh
#
# i stosu Gabinetu podłączonego do sieci IdP:
#   docker compose -f docker-compose.yml -f docker-compose.konta.yml up -d
#
# ŚWIADOMIE NIE jest częścią `skrypty/bramka.sh` ani CI: bramka Gabinetu musi
# przechodzić na świeżym klonie, bez drugiego repozytorium obok.
# ===========================================================================
set -uo pipefail

export MSYS_NO_PATHCONV=1
export MSYS2_ARG_CONV_EXCL='*'

KORZEN="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$KORZEN"

ISSUER="${GABINET_IDP_PUBLICZNY:-https://localhost:8443/realms/niepodzielni}"
UZYTKOWNIK="${NK_TEST_USER:-test-pacjent}"
# Hasło fixture'ów z repo `konta` — jawny, opisowy ciąg, NIE sekret
# (`realm/test-fixtures.json`: „DANE WYLACZNIE TESTOWE... to NIE jest sekret").
HASLO="${NK_TEST_PASSWORD:-haslo-tylko-do-testow-1234}"

NIEUDANE=0
krok() { printf '\n=== %s\n' "$*"; }
zle()  { NIEUDANE=$((NIEUDANE + 1)); printf '    ^ NIEUDANE\n'; }

sciezka_hosta() {
	if command -v cygpath >/dev/null 2>&1; then cygpath -w "$KORZEN/$1"; else echo "$KORZEN/$1"; fi
}

dc() {
	docker compose -f "$(sciezka_hosta docker-compose.yml)" -f "$(sciezka_hosta docker-compose.konta.yml)" "$@"
}

krok "1. IdP osiągalny, issuer zgodny, JWKS niepusty"
dc exec -T app php artisan konta:sprawdz || zle

krok "2. token PRAWDZIWEGO konta testowego (${UZYTKOWNIK}, klient test-cli)"
# `-k`: lokalny stos używa CA Caddy'ego, którego host nie musi znać. To jedyne
# miejsce, gdzie pomijamy weryfikację TLS — i wyłącznie po to, żeby POBRAĆ token
# do dalszych asercji. Sama aplikacja sięga do IdP trasą wewnętrzną po HTTP
# w sieci Dockera i nigdy nie wyłącza weryfikacji certyfikatu.
TOKEN="$(curl -sk -X POST "${ISSUER}/protocol/openid-connect/token" \
	-d grant_type=password -d client_id=test-cli -d "username=${UZYTKOWNIK}" \
	--data-urlencode "password=${HASLO}" \
	| php -r '$d = json_decode(stream_get_contents(STDIN), true); echo $d["access_token"] ?? "";')"

if [ -z "$TOKEN" ]; then
	echo "    brak tokenu — czy uruchomiono realm/import-fixtures.sh?"
	zle
	echo; echo "SONDA CZERWONA — $NIEUDANE nieudanych"; exit 1
fi
echo "    token pobrany (${#TOKEN} znaków)"

krok "3. NEGATYW: token wystawiony dla innego klienta MUSI odpaść na audiencji"
WYNIK="$(dc exec -T app php artisan konta:sprawdz --token="$TOKEN" 2>&1)"
echo "$WYNIK" | sed -n '/Kontrole tokenu/,$p'

if echo "$WYNIK" | grep -q 'aud *\[FAIL\]' \
	&& echo "$WYNIK" | grep -q 'signature *\[OK\]' \
	&& echo "$WYNIK" | grep -q 'iss *\[OK\]'; then
	echo "    POZYTYW: odrzucony DOKŁADNIE na audiencji (podpis i issuer przeszły)"
else
	echo "    token nie został odrzucony na audiencji albo odrzuciła go inna kontrola"
	zle
fi

krok "4. POZYTYW: ta sama walidacja przy audiencji, którą token faktycznie ma"
if dc exec -T app php artisan konta:sprawdz --token="$TOKEN" --audiencja=account >/dev/null 2>&1; then
	echo "    token przyjęty — cały łańcuch (JWKS → podpis → iss → exp → typ → aud) działa"
else
	zle
fi

printf '\n'
if [ "$NIEUDANE" -eq 0 ]; then
	echo "SONDA OK — 0 nieudanych"
	exit 0
fi

echo "SONDA CZERWONA — $NIEUDANE nieudanych"
exit 1
