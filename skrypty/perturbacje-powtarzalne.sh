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

for i in $(seq 1 "$PRZEBIEGI"); do
	printf '\n=== PRZEBIEG %s z %s\n' "$i" "$PRZEBIEGI"
	PODSUMOWANIE="$(bash "$KORZEN/skrypty/perturbacje.sh" 2>&1 | grep -aE '^PERTURBACJE' | tail -1)"
	printf '    %s\n' "$PODSUMOWANIE"
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
if [ "$ROZNE" -eq 0 ] && [ "$BRUDNE" -eq 0 ]; then
	printf 'PERTURBACJE POWTARZALNE — %s przebiegi, identyczny wynik, czyste drzewo\n' "$PRZEBIEGI"
	exit 0
fi

[ "$ROZNE" -ne 0 ] && printf 'NIEPOWTARZALNE: przebiegi dały RÓŻNE wyniki\n'
[ "$BRUDNE" -ne 0 ] && printf 'NIEPOWTARZALNE: %s przebiegów zostawiło zmiany w drzewie\n' "$BRUDNE"
exit 1
