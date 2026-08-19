# ZLECENIE-080 · 19.08.2026 · OD architekta DO sesji KOD-F1 — dwa rozstrzygnięcia + cisza

Cykl przyjęty. Weryfikacja własna: `7a8c44d` = czubek, warunek zamrożenia pusty, gałąź
wypchnięta. Dowód skutku przy obcym kluczu (odmowa **z kontroli podpisu**, nie z dowolnego
ogniwa) — wzorcowy: mierzy przyczynę odmowy, nie sam fakt odmowy.

## 1. Zegar systemowy (§3d) — POZA ZAKRESEM F1, jako decyzja, nie przeoczenie

**Nie wprowadzamy kontroli zależnej od zegara.** Powody: (a) przesunięty zegar hosta to
zagadnienie eksploatacyjne (synchronizacja czasu), nie logika aplikacji — naprawia się je
w konfiguracji serwera, nie w kodzie; (b) kontrola zależna od zegara zaczyna padać sama
z siebie, co sam trafnie zauważyłeś; (c) w F1 nie ma produkcji, więc nie ma czego mierzyć.

**Ale nie znika po cichu:** wpisuję ją do pozycji uruchomieniowych jako wymóg
infrastrukturalny (synchronizacja czasu na serwerze produkcyjnym + monitorowanie
rozjazdu), do wykazania przy starcie produkcyjnym. To jest nazwana granica z adresem,
a nie dług bez terminu.

## 2. `.zakres-sesji` w `.gitignore` (§5 haczyk) — WERSJONUJEMY, ale w oknie scaleniowym

Masz rację, że deklaracja niepodróżująca z repozytorium jest deklaracją na jedną maszynę.
**Rozstrzygnięcie: deklaracje zakresu mają być wersjonowane** — inaczej strażnik działa
inaczej u każdego, a to ta sama klasa co „mechanizm obecny wyłącznie lokalnie" (S-01).

**Termin: okno scaleniowe, w ramach O-7** (weryfikacja `.zakres-sesji` per strumień),
nie teraz — `.gitignore` leży w zakresie zamrożenia, a runda 12 mierzy `7a8c44d`.
Wymaganie do O-7: pliki zakresu w repozytorium, po jednym na strumień, z kontrolą
negatywną (sesja bez deklaracji → odmowa) wykonywaną **w każdym aktywnym drzewie**.

## 3. Wada własna §6 — przyjęta i podniesiona do standardu

„Mierzę narzędziem, a potem dopisuję materiał i już nie mierzę ponownie" — to ta sama
rodzina co „bramka po każdym commicie". Twoja reguła (**po każdej zmianie plików
testowych statyka biegnie razem z testami**) wchodzi do standardów sesji kodujących.

## 4. Cisza i runda 12

Gałęzi nie ruszasz do raportu rundy 12 (`ZLECENIE-081` — zlecone). Twoje cztery
twierdzenia z §9, w tym najnowsze o `KontaOidc` jako parametrze, przekazuję rundzie
**wprost jako pozycje do obalenia**. Twój następny meldunek: **ODPOWIEDZ-082**.

Znasz moje kryterium: siódme piętro w tym samym obszarze → nie zlecam ósmej naprawy,
tylko przedstawiam właścicielowi wybór. Twoja zgoda z nim wyrażona w §9 jest odnotowana.
