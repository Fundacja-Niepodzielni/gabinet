# ZLECENIE-069 · 18.08.2026 · OD architekta DO sesji KOD-F1 — naprawy po rundzie 9

**Runda 9: 5 znalezisk (`ODPOWIEDZ-067` + `docs/rundy/RUNDA-9-RAPORT.md`). Zamrożenie
ZDJĘTE.** To zlecenie ma inny charakter niż poprzednie: **przy R9-1/R9-2/R9-4 nie łatasz
instancji — przeprojektowujesz klasę kontroli.** Uzasadnienie niżej, bo zmienia sposób.

## 1. Diagnoza architekta: trzy znaleziska to JEDNA klasa

R9-1 (parowanie pól / nagłówek), R9-2 (parser nie zna `all`/`only`/tablicy), R9-4
(zasięg skanerów tylko `app/`) — to nie trzy wady, to **jedna: kontrola tożsamości jest
LISTĄ (plików, nazw pól, reguł), a nie POMIAREM niezmiennym.** Weryfikator nazwał sedno:
*„allowlista to lista PLIKÓW, nie instrukcji; wewnątrz nich siatka D-1b jest jedynym
detektorem"*. Dopóki detektor sonduje wyliczanką, ZAWSZE istnieje „krok dalej" — dowód:
rundy 7, 8, 9 zamknęły po jednej instancji, klasa przechodziła dalej. Czwarty cykl łatania
da czwarty krok. **Zamykamy klasę, nie instancję.**

### R9-1/R9-2 — wąskie gardło STRUKTURALNE zamiast siatki-sondy

Docelowo (CLAUDE.md §2 doprowadzona do końca): **zapis klucza tożsamości do sesji
(`session()->put('konta', …)` i równoważne) jest MOŻLIWY z dokładnie jednego miejsca**
(klasa/metoda callbacku OIDC), a każde inne wystąpienie w `backend/` jest błędem
wykrywanym **niezależnie od nazwy pola wejściowego** — bo mierzy MIEJSCE ZAPISU tożsamości,
nie SPOSÓB dostarczenia sekretu. Siatka D-1b (sonda baterią) zostaje jako WZMOCNIENIE
z jawnie nazwaną granicą, ale przestaje być jedynym detektorem.

Wymagania odbioru:
1. Kontrola pozytywna: legalny callback OIDC przechodzi.
2. Kontrola negatywna — **każdy z trzech wektorów rundy 9 osobno**: formularz `email`+`hasło`
   (dwa pola), sekret w nagłówku HTTP, `$request->all()['zaklecie']` — **każdy zapala
   kontrolę z właściwej przyczyny** (mechanizm ustanawia tożsamość poza callbackiem).
3. „Krok dalej": po przeprojektowaniu wskaż, czym jeszcze DA SIĘ ustanowić tożsamość
   omijając wąskie gardło (np. zapis wprost do magazynu sesji, nie przez fasadę) — i albo
   to pokryj, albo nazwij jako zmierzoną granicę z uzasadnieniem, dlaczego niedosięgalna.

### R9-4 — pozostałe skanery `app/`-only

`WaskieGardloTozsamosciTest` „warunek utrzymujący" (R6A-3) i `ObietniceKomentarzyTest`
skanują tylko `app/`. Rozszerz zasięg o `routes/` (i sprawdź „krok dalej": czy są INNE
katalogi wykonywalne — `bootstrap/`, `config/` z domknięciami). Pomiar różnicowy jak w §4
rundy 8.

## 2. R9-3 — sekret w `.env.example` (osobna klasa: „sekrety nigdy w plikach")

Wyjątek gitleaks na `.env.example` istnieje, a 30 wierszy niżej ten sam plik twierdzi
„`.env.example` nie potrzebuje wyjątku i celowo go nie dostaje" — rozjazd opisu ze stanem
w pliku, który sam jest kontrolą. Napraw: usuń sprzeczność (albo wyjątek, albo zdanie),
a detekcję sekretu w `.env.example` oprzyj na pomiarze niezależnym od nazwy pola
(wzorzec sekretu, nie lista nazw). Dowód dwustronny: przynęta o kształcie sekretu zapala,
plik czysty → `no leaks`.

## 3. R9-5 — plik stanu znów kłamie (D-klasa, trzeci raz)

`PLAN-FAZ.md` mówi „290/2130 zmierzone na `179c05c`", faktycznie na `179c05c` jest
**289/2119**; „48 kontroli / 31 scenariuszy" — faktycznie **49/32**. Przepisz ze stanu
zmierzonego. **I domknij klasę zgodnie z ODPOWIEDZ-065:** data i liczby w sekcji stanu
jako **kotwica do SHA** (`zmierzone na <SHA>`), a `JednoZrodloStanuTest` ma pilnować
kotwicy ORAZ liczb perturbacji (dziś nie pilnuje żadnego). To ta poprawka konwencji,
którą odłożyliśmy „na okno scaleniowe" — wchodzi teraz, bo runda właśnie pokazała jej brak.

## 4. Procedura

Pełna bramka OD ZERA + pełny zestaw perturbacji → nowe zamrożone SHA → **meldunek
ODPOWIEDZ-069**:

> **⚠ WARUNEK ZAMROŻENIA — POPRAWIONY 18.08 (audyt architekta, znalezisko A-4).**
> Dotychczasowe brzmienie (`-- backend/ skrypty/ .gitleaks.toml`) było **węższe niż własna
> definicja słowna** („kod + konfiguracja bramki"): NIE obejmowało `docker-compose.yml`,
> `docker/`, `.env.example` ani `.github/` — plików, które **zmieniają środowisko pomiarowe
> rundy** (bramka uruchamia pomiar przez `docker-compose.yml`, a weryfikator rundy 8 oparł
> werdykt na `.env.example:136`). Commit dotykający ich przechodził warunek bez śladu.
> **Obowiązuje odtąd forma negatywna, obejmująca wszystko poza dokumentacją:**
> ```
> git diff --stat <SHA-KODU>..HEAD -- . ':(exclude)docs/'   →  musi być PUSTO
> ```
> Commity dotykające wyłącznie `docs/` pozostają dozwolone i zgłaszane w kanale.
tabela R9-1…R9-5 → co zmieniono + „krok dalej", z naciskiem na dowód, że wąskie gardło
tożsamości jest STRUKTURALNE (trzy wektory rundy 9 jako kontrole negatywne w suicie).
Po meldunku — runda 10, świeży weryfikator.

To jest cięższy cykl niż poprzednie (przeprojektowanie, nie łatka) — **nie spiesz się
kosztem klasy.** Lepiej jedna runda dłużej i klasa zamknięta, niż trzy krótkie i „krok
dalej” za każdym razem. Zbieżność: 29 → 9 → 2 → 5; celem nie jest malejąca liczba,
tylko zero z zamkniętą klasą pod spodem.
