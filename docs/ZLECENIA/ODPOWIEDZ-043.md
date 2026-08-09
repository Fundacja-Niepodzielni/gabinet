ODPOWIEDZ-043 — POZYCJA NIEZROBIONA (zamkniecie dnia, ZLECENIE-042 krok 1)

09.08.2026, 23:2x

CO ZROBIONE: nic. Pozycje wzialem kilka minut przed ZLECENIE-042 i nie tknalem
jeszcze pliku skryptu.

CO ZOSTALO: calosc. skrypty/perturbacje-powtarzalne.sh:39 ma trap wylacznie na
EXIT. Przerwanie z klawiatury (INT) albo sygnalem (TERM) konczy skrypt Z POMINIECIEM
sprzatania, wiec zmutowany kod zostaje w drzewie i wyglada jak zwykly stan
repozytorium. Naprawa: rozbic na trzy osobne trapy, tak jak w perturbacja-odwrotna.sh
po ZLECENIE-022, i dolozyc kontrole, ze wszystkie trzy sa ustawione.

GDZIE DOKLADNIE STANALEM: przed pierwszym odczytem pliku. Zero pomiarow do
odzyskania, zero kopii roboczych, drzewo czyste. Pozycja jest WOLNA DO WZIECIA
OD ZERA — nie ma stanu posredniego, ktory trzeba by odtwarzac.

DLACZEGO WARTO: 09.08 stracilem juz prace przez sprzatanie perturbacji
(git checkout --). Skrypt zostawiajacy mutacje przy Ctrl-C to ta sama rodzina
bledu, tylko czekajaca.
