#!/usr/bin/env bash
# ===========================================================================
# skrypty/podlogi.sh — JEDNO ŹRÓDŁO podłóg liczby testów i asercji.
#
# Ten plik nie robi nic poza nadaniem dwóch wartości. Jest osobnym plikiem
# z jednego powodu: `bramka.sh` te podłogi EGZEKWUJE, a `perturbacje.sh` ma
# DOWODZIĆ, że one umieją zaświecić czerwono. Dopóki liczby stały w dwóch
# miejscach, perturbacja dowodziła innej wartości niż bramka egzekwuje.
#
# ---------------------------------------------------------------------------
# R6B-12 — ROZJAZD, KTÓRY URÓSŁ PO NAPRAWIE INNEJ KONTROLI.
#
# Zmierzone 09.08: perturbacje `pusta_suita`, `pominiete` i `licznik` miały
# w kodzie liczby 100 (testy) i 300 (asercje), a bramka egzekwowała 236/1936.
# Perturbacja mówiła więc „podłoga 100 testów odrzuca pusty przebieg", co jest
# prawdą o liczbie 100 i NIE JEST twierdzeniem o kontroli, którą bramka
# wykonuje. Rozjazd urósł, gdy podłogi podniesiono (N-2) — naprawa jednej
# kontroli powiększyła lukę w drugiej.
#
# Od tej zmiany obie strony czytają te same dwie zmienne stąd.
# ---------------------------------------------------------------------------
#
# Podłoga liczby testów. Rośnie razem z suitą — obniżenie MUSI być świadomą
# zmianą w repozytorium (zasada D-0013: pusta suita to zielone CI bez testów).
#
# STAŁA, nie zmienna środowiskowa (U-2/U-6 z rundy 3): dopóki dało się ustawić
# `GABINET_MINIMUM_TESTOW=0`, kontrolę wyłączało się bez śladu w repozytorium —
# a kontrola, którą można wyłączyć niewidocznie, nie jest kontrolą. Obniżenie
# podłogi ma być widoczne w `git diff` i przejść przez przegląd.
#
# V-10 z rundy 5: komentarz twierdził „rośnie razem z suitą", a nie rósł —
# 100 przy 174 testach. Weryfikator zmierzył, że w tym zapasie mieści się
# SKASOWANIE CAŁEGO pliku kontroli CLAUDE.md §2 (7 testów, 21 asercji) przy
# w pełni zielonej bramce. Podłoga ma siedzieć TUŻ POD stanem bieżącym, żeby
# każde zniknięcie kontroli było widoczne — a jej podniesienie to jedna linia
# w tym pliku, świadoma i w `git diff`.
#
# PODNIESIENIE 08.08 w nocy, NAPRAWA PRZYRZĄDU. V-10 zostało zamknięte tylko
# na chwilę: podłoga 170 przy 181 wykonanych testach dawała 11 testów zapasu,
# a w tym zapasie mieści się SKASOWANIE W CAŁOŚCI dziesięciu z siedemnastu
# plików kontrolnych — w tym `ObietniceKomentarzyTest` (2 testy), czyli
# kontroli NAD KONTROLAMI. Zmierzone: `pest` → 181 wykonanych / 640 asercji;
# rozkład na pliki od 2 do 36. Komentarz obiecywał „TUŻ POD stanem bieżącym"
# i był nieprawdziwy wobec własnej wartości — dokładnie ta klasa, przed którą
# ostrzega `ObietniceKomentarzyTest`.
#
# Zapas zostaje CELOWO minimalny: każde zniknięcie choćby jednego testu ma
# zapalić bramkę. Gdy suita urośnie, ta liczba rośnie razem z nią — w tym samym
# commicie, świadomie i w `git diff`.
#
# PODNIESIENIE 09.08 (runda 1, przyrząd): 180/635 → 183/647. Powód: doszły trzy
# kontrole (klamra perturbacji + dwie kontrole twierdzeń w komentarzach), więc
# stan wynosi 184 testy / 652 asercje.
#
# PODNIESIENIE 09.08 (weryfikacja krzyżowa rundy 1): 183/647 → 186/656. Powód:
# doszła ZAPADKA POKRYCIA dla allowlist przyczyny czerwieni
# (`PrzyczynyPerturbacjiTest`, 3 kontrole).
#
# PODNIESIENIE 09.08 (ZLECENIE-008): 186/656 → 187/659. Doszedł świadek dla
# `Typy::napis` (3 kontrole), a DWIE kontrole twierdzeń w komentarzach zostały
# POMINIĘTE — pominięte nie liczą się do podłogi.
#
# PODNIESIENIE 09.08 (runda 2, R6A-11): 187/659 → 195/683.
# PODNIESIENIE 09.08 (ZLECENIE-012/014): 195/683 → 199/695.
# PODNIESIENIE 09.08 (dalsze zlecenia, stan zastany 12.08): 236/1936.
#
# PODNIESIENIE 12.08 (naprawy rundy 7, ZLECENIE-062): 265/2024 -> 284/2098.
# PODNIESIENIE 12.08 (kolejnosc znacznika w klamrze): 284/2098 -> 286/2108.
# PODNIESIENIE 12.08 (egzekutor N-3 dla magazynu pulsu): 286/2108 -> 287/2112.
# PODNIESIENIE 18.08 (podwojone uchwyty trap w bramce): 287/2112 -> 289/2119.
# PODNIESIENIE 18.08 (naprawy rundy 8: R8-1 skaner pol + R8-2 realny start): 289/2119 -> 290/2130.
# Powod: siatka POMIAROWA tozsamosci (D-1b, 3 kontrole), egzekutor wpiecia
# blokady wysylki (R7-3, 2), kontrole rejestru narzedzi compose i odmowy
# na projekcie dewelopera (R7-7, 4), egzekutor WNETRZA sekcji stanu (R7-6, 4),
# skaner matcherow wariadycznych (2).
#
# UWAGA DLA PODNOSZĄCEGO: te dwie liczby czyta TAKŻE `perturbacje.sh`.
# Podniesienie ich bez uruchomienia perturbacji jest bezpieczne (perturbacje
# porównują z nimi PEŁNY przebieg suity), ale podniesienie POWYŻEJ stanu
# bieżącego wywali i bramkę, i perturbacje — i to jest zamierzone.
MINIMUM_TESTOW=290

# Drugi, niezależny sygnał (W-4): suita bez asercji niczego nie dowiodła,
# choćby liczba testów wyglądała dobrze. Liczbę testów da się nadmuchać
# (pominięte wliczały się do podłogi, aż weryfikator pokazał zieloną bramkę
# przy 151 pominiętych i ZERZE asercji). Asercji nie da się nadmuchać, nie
# wykonując ich.
MINIMUM_ASERCJI=2130
