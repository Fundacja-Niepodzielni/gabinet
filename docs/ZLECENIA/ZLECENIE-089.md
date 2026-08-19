# ZLECENIE-089 · 19.08.2026 · OD architekta DO sesji KOD-F1 — optymalizacje A-1 i A-4

**F1 zamknięta i scalona** (`main` = `75b84e2`, znacznik `f1-zamkniete-19-08`).
Zgodnie z decyzją właściciela z 18.08 (`LEKCJE-F1-I-OPTYMALIZACJE.md` część A) wykonujemy
teraz dwie optymalizacje, których terminem było „po zamknięciu F1". A-2 i A-3 są już
w promptach — zostają A-1 i A-4.

**Gałąź robocza: `opt/a1-a4`** (nie pracujemy na `main`). Zwykły tryb: bramka po każdym
commicie, perturbacje przyrostowe w trakcie, pełny zestaw przy zgłoszeniu.

## A-1 · Cache budowy dla klonów efemerycznych

**Problem zmierzony:** każda runda weryfikacyjna buduje obrazy i pobiera pakiety od zera.
Cel: **−20–40 minut z rundy** (miara: czas „klon → pierwsza zielona bramka").

**Warunek nadrzędny, ważniejszy od zysku:** niezależność pomiaru dotyczy **stanu kodu**,
nie cache'u pakietów. Cache **nie może** przenosić między przebiegami niczego, co pochodzi
z badanego drzewa.

Wymagania odbioru:
1. Cache **tylko do odczytu** dla przebiegu; przebieg nie zapisuje do wspólnego zasobu
   (inaczej jedna runda zatruwa następną — mieliśmy tę klasę przy pamięci podręcznej JWKS).
2. **Kontrola krytyczna — „cache nie maskuje":** przebieg z cache musi wykryć **podłożoną
   wadę** tak samo jak przebieg bez cache. Zmierz na konkretnej perturbacji: wynik ma być
   identyczny (czerwień z tej samej przyczyny). Bez tej kontroli optymalizacja jest
   przyspieszaniem przyrządu kosztem jego prawdziwości.
3. **Pomiar przed/po** w meldunku: dwa razy „klon → zielona bramka", z liczbami i tym,
   co dokładnie mierzysz.
4. Jeżeli zysk okaże się mniejszy niż ~10 minut albo kontrola z pkt 2 sprawia kłopot —
   **napisz to i odpuść**. Optymalizacja przyrządu pomiarowego nie jest warta ryzyka
   dla samego pomiaru.

## A-4 · CI jako druga noga pomiaru

**Dług znany od 08.08:** przebiegi chmurowe uruchamiają się wyłącznie na `main`, a cała
praca żyła na gałęziach — czyli zero niezależnej weryfikacji tam, gdzie najbardziej
potrzebna.

Wymagania odbioru:
1. Przebieg chmurowy uruchamia się **także na gałęziach roboczych** (co najmniej
   `faza-*`, `f2-*`, `f3-*`, `opt/*`, `testy-*`).
2. **Nie zastępuje** pomiaru lokalnego weryfikatora — jest drugą, niezależną nogą.
   W zleceniu rundy będę odtąd cytował oba przebiegi.
3. Sprawdź, czy przebieg chmurowy wykonuje **ten sam zakres** co bramka lokalna; jeśli
   nie — wypisz różnicę wprost, żeby nikt nie czytał „CI zielone" jako „bramka zielona".
4. Jeżeli koszt czasu przebiegu chmurowego okaże się nieproporcjonalny (długie budowanie),
   zaproponuj zawężenie zakresu **z jawną listą tego, czego tam nie ma**.

## Meldunek

**ODPOWIEDZ-089**: pomiar przed/po dla A-1, dowód kontroli „cache nie maskuje", stan A-4
(gdzie się uruchamia, jaki zakres, różnice wobec bramki lokalnej), oraz „czego nie zrobiłem".
Po meldunku ja przeglądam i scalam do `main` — **bez osobnej rundy weryfikacyjnej**: to jest
narzędziowe, a nie produktowe, i podlega zwykłej bramce. Wyjątek: gdyby kontrola z A-1 pkt 2
wypadła niejednoznacznie — wtedy wracamy z tym do rundy.
