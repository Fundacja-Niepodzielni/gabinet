# ZLECENIE-001 — pogrupuj znaleziska w KLASY i zaproponuj jedną naprawę na klasę

> **PIERWSZA LINIA ODPOWIEDZI — POMIAR KANAŁU.** Ten plik powstał PO uzbrojeniu Twojego
> obserwatora, więc dopiero on mierzy to, czego ZLECENIE-000 zmierzyć nie mogło.
> Podaj: czas modyfikacji tego pliku (`stat`), czas obudzenia przez obserwatora, różnicę.
> Jeśli obserwator NIE zadziałał i czytasz to z innego powodu — napisz to jako zdanie pierwsze.

Nadal **bez dotykania kodu**. To praca projektowa, nie naprawcza.

## Dlaczego

Wasze rejestry są już prawdziwe po korekcie weryfikatorów krzyżowych, ale liczą razem około
dziewięćdziesięciu pozycji. Naprawianie ich po kolei wyprodukuje dziewięćdziesiątą pierwszą
z tej samej rodziny. Odpowiedzią ma być zmiana sposobu naprawiania, nie dłuższa lista.

## Co robisz

1. Weź **wyłącznie pozycje POTWIERDZONE** po korekcie. Obalone i sporne pomijasz.
2. Pogrupuj w KLASY. **Klasa to zbiór znalezisk, które zamknęłaby JEDNA zmiana.**
   Jeśli proponowana zmiana zamyka jedno, a nie zamyka pozostałych — to nie klasa, tylko
   instancja, i tak ją nazwij.
3. Dla każdej klasy podaj:
   · nazwę jednym zdaniem — CO jest wspólnym **mechanizmem**, nie wspólnym objawem
   · listę członków (identyfikatory)
   · JEDNĄ naprawę na właściwym poziomie: konstrukcja przed warunkiem, wąskie gardło przed
     strażnikiem, brak wartości przed sprawdzaniem wartości
   · perturbację, która tę naprawę falsyfikuje — bez niej naprawa jest deklaracją
   · **czy podejrzewasz tę klasę TAKŻE w innym repozytorium** i w którym
4. Znalezisko, które zostaje samo: napisz wprost „instancja, naprawa punktowa" — żeby nikt
   nie udawał, że wszystko jest klasą.

## Nowy wymóg do rejestrów — wiążący od teraz we wszystkich czterech

Wynik dzisiejszego sporu o W-19, w którym gabinet i hub rozeszli się, bo spierali o jedną
liczbę zawierającą dwie wielkości. **Każde znalezisko niesie DWA osobne pola:**

· **WAGA** — jak źle, gdy mechanizm zadziała
· **OSIĄGALNOŚĆ** — kto może dziś tędy wejść i co musiałoby się zmienić, żeby mógł ktoś więcej

Nigdy jedno w miejsce drugiego. „Wysokie, ale dziś nieosiągalne" i „średnie i osiągalne"
wyglądają w rejestrze identycznie, a wymagają przeciwnych decyzji. Gdzie osiągalność jest dziś
zerowa dzięki czemuś, co ktoś może jutro zmienić (np. „nikt nie ma tego uprawnienia") — zapisz
to jako **WARUNEK UTRZYMUJĄCY**, bo pierwsza osoba, która go złamie, nie będzie wiedziała,
że to zrobiła.

## Czego nie robisz

Nie naprawiasz kodu · nie dopisujesz nowych znalezisk · nie tykasz cudzych repozytoriów ·
zero `main`, merge, deploy, nic poza fundację.

## Oddanie

Raport: `docs/noc-2026-08-08/KLASY-I-NAPRAWY.md`
Odpowiedź w kanale: `docs/ZLECENIA/ODPOWIEDZ-001.md` — pomiar kanału, potem jeden akapit:
ile klas, ile instancji osobnych, która klasa największa, które podejrzewasz w innych repozytoriach.
Potem czekasz na kolejne zlecenie.
