#!/usr/bin/env bash
# ===========================================================================
# Jedna procedura licząca wykonane testy — dla bramki I dla perturbacji.
#
# Po co osobny plik: dwa razy zdarzyło się, że perturbacja mierzyła co innego
# niż kontrola. Raz porównywała napis ułożony w skrypcie zamiast wyjścia pesta
# (CI zapaliło się przy 107 zielonych testach, perturbacja nie). Drugi raz
# (U-6, runda 3) poprawiłem parser w `bramka.sh`, a kopię w `perturbacje.sh`
# zostawiłem starą. Dopóki procedura istniała w dwóch egzemplarzach, „ta sama
# procedura" była deklaracją, nie faktem. Teraz jest jedna.
#
# Liczy KAŻDY wykonany test, nie tylko zaliczone: przy „1 failed, 135 passed"
# wzorzec szukający liczby tuż przed słowem „passed" nie trafiał w nic
# i zwracał ZERO — czyli „suita się nie uruchomiła" przy pełnym przebiegu.
# ===========================================================================

policz_testy() {
	printf '%s' "$1" \
		| sed -e 's/\x1b\[[0-9;]*[A-Za-z]//g' -e 's/\[[0-9;]*[A-Za-z]//g' \
		| sed -n 's/.*Tests:  *//p' | tail -1 \
		| grep -oE '[0-9]+ (passed|failed|skipped|todo|incomplete|risky|warned|notified|deprecated)' \
		| grep -oE '^[0-9]+' \
		| awk '{ suma += $1 } END { print suma + 0 }'
}
