#!/usr/bin/env bash
# ===========================================================================
# Czy zestaw perturbacji jest POWTARZALNY (reguła 4).
#
# Kontrola, która zmienia stan, psuje go swojemu następnemu przebiegowi —
# i wtedy drugi przebieg mierzy co innego niż pierwszy, nie mówiąc o tym ani
# słowa. Uruchamiamy więc cały zestaw trzy razy pod rząd i wymagamy:
#   · identycznego podsumowania za każdym razem,
#   · czystego `git status` po każdym przebiegu.
#
# Trzy, nie dwa: pierwszy przebieg bywa czysty przypadkiem, bo startuje na
# stanie ustawionym ręcznie.
# ===========================================================================
set -uo pipefail

KORZEN="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$KORZEN"

PRZEBIEGI="${1:-3}"
WYNIKI=()
BRUDNE=0
POPSUTE=0

# Stan drzewa PRZED pierwszym przebiegiem. Porównujemy z NIM, nie z czystym
# repozytorium (lekcja „podejrzewaj najpierw własny przyrząd"): pierwsza wersja
# wołała `git status --porcelain` i uznawała KAŻDĄ zmianę za wyciek
# z perturbacji. Efekt: przy jakiejkolwiek pracy w toku skrypt meldował
# „3 przebiegów zostawiło zmiany w drzewie", choć perturbacje sprzątały
# bez zarzutu. Przyrząd oskarżał badany system o własne zanieczyszczenie.
STAN_WYJSCIOWY="$(git status --porcelain)"
# Dziennik POZA repozytorium: gdy leżał w `skrypty/`, kontrola „czyste drzewo
# robocze" wywracała się o WŁASNY artefakt tego skryptu. Narzędzie obserwacji
# nie może zmieniać obserwowanego stanu. Katalog tworzy `mktemp` — tu bezpiecznie,
# bo cała obsługa plików jest w bashu (żadnego mieszania z Pythonem).
KATALOG_DZIENNIKA="$(mktemp -d)"
DZIENNIK="$KATALOG_DZIENNIKA/przebieg"

sprzataj_dzienniki() { rm -rf "$KATALOG_DZIENNIKA"; }
trap sprzataj_dzienniki EXIT

for i in $(seq 1 "$PRZEBIEGI"); do
	printf '\n=== PRZEBIEG %s z %s\n' "$i" "$PRZEBIEGI"

	# Wyjście zestawu trafia do PLIKU, nie prosto do grepa. Powód: filtr jest
	# CZĘŚCIĄ POMIARU, nie kosmetyką (lekcja helpdesku P11). Pierwsza wersja
	# tego skryptu robiła `… | grep '^PERTURBACJE' | tail -1`. Gdyby zestaw
	# PADŁ przed podsumowaniem, grep zwracał pustkę — a trzy pustki są
	# identyczne, więc skrypt meldował POWTARZALNE, choć perturbacje w ogóle
	# nie wystartowały. Zmierzone na sucho, zanim ten komentarz powstał:
	# `bash -c 'echo start; exit 1' | grep '^PERTURBACJE'` → ROZNE=0.
	#
	# Dlatego: brak podsumowania to osobny, głośny stan (NIEROZSTRZYGNIĘTE),
	# a nie „wynik równy poprzedniemu". Kod wyjścia zestawu też jest widoczny.
	KOD_PRZEBIEGU=0
	bash "$KORZEN/skrypty/perturbacje.sh" > "$DZIENNIK.$i" 2>&1 || KOD_PRZEBIEGU=$?
	PODSUMOWANIE="$(grep -aE '^PERTURBACJE' "$DZIENNIK.$i" | tail -1)"

	if [ -z "$PODSUMOWANIE" ]; then
		printf '    ✗ przebieg %s NIE DOSZEDŁ do podsumowania (kod wyjścia %s)\n' "$i" "$KOD_PRZEBIEGU"
		printf '      ostatnie wiersze przebiegu:\n'
		tail -5 "$DZIENNIK.$i" | sed 's/^/      /'
		POPSUTE=$((POPSUTE + 1))
		PODSUMOWANIE="BRAK PODSUMOWANIA (przebieg $i)"
	else
		printf '    %s\n' "$PODSUMOWANIE"
		printf '    · kod wyjścia zestawu: %s\n' "$KOD_PRZEBIEGU"
	fi

	WYNIKI+=("$PODSUMOWANIE")

	STAN="$(git status --porcelain)"
	if [ -n "$STAN" ]; then
		printf '    ✗ przebieg %s zostawił zmiany w drzewie roboczym:\n' "$i"
		printf '%s\n' "$STAN" | sed 's/^/      /'
		BRUDNE=$((BRUDNE + 1))
	else
		printf '    ✓ drzewo robocze czyste po przebiegu %s\n' "$i"
	fi
done

ROZNE=0
for w in "${WYNIKI[@]}"; do
	[ "$w" = "${WYNIKI[0]}" ] || ROZNE=1
done

printf '\n'
if [ "$ROZNE" -eq 0 ] && [ "$BRUDNE" -eq 0 ] && [ "$POPSUTE" -eq 0 ]; then
	printf 'PERTURBACJE POWTARZALNE — %s przebiegi, identyczny wynik, czyste drzewo\n' "$PRZEBIEGI"
	exit 0
fi

[ "$ROZNE" -ne 0 ] && printf 'NIEPOWTARZALNE: przebiegi dały RÓŻNE wyniki\n'
[ "$BRUDNE" -ne 0 ] && printf 'NIEPOWTARZALNE: %s przebiegów zostawiło zmiany w drzewie\n' "$BRUDNE"
[ "$POPSUTE" -ne 0 ] && printf 'NIEROZSTRZYGNIĘTE: %s przebiegów nie doszło do podsumowania — wynik nie znaczy nic\n' "$POPSUTE"
exit 1
