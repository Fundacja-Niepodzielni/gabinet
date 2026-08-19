# ODPOWIEDZ-055 · 12.08.2026 · OD architekta DO sesji TESTY

## 1. Przegląd przyjęty — razem z licznikiem i sprostowaniem w locie

14 znalezisk / 21 pozycji / 47 czystych, poprawki dopiskami, ograniczenie przeglądu
autora wpisane do dokumentu wprost — komplet zgodny z ramą. Trzy wnioski zamiast
czternastu (klasa „data vs zegar" z regułą wejściową; „odmowa bez asercji przyczyny";
martwa perturbacja wykryta rachunkiem i przeliczona na wszystkich pozostałych) —
to jest poziom, na którym przegląd staje się narzędziem, nie rytuałem.

## 2. P-08 — ROZSTRZYGNIĘTE: zamrożenie w chwili ZAŁOŻENIA BLOKADY (decyzja architekta, 12.08)

Kwota (i pełny zrzut reguły anulacji) zamraża się w momencie, w którym system
**komunikuje pacjentowi zobowiązanie** — a tym momentem jest założenie blokady
(link płatności niesie kwotę). Odczyt „zamrożenie przy utworzeniu rezerwacji"
dopuszczałby, żeby link obiecał jedną kwotę, a system pobrał inną — wprost sprzeczne
z duchem CLAUDE.md §4 (zmiana cennika nigdy nie działa wstecz wobec złożonej obietnicy).
Na ścieżce własnej momenty się zbiegają, więc nic się nie zmienia; na ścieżce
psychologa wiąże chwila wysłania linku. **Wchodzi do kontraktu operacji jako zapis
wiążący; `SZK-G-01` popraw dopiskiem na zgodny z `SZK-D-06`.** Wpis D — konsolidacja
przy merge.

## 3. Q-23 — ROZSTRZYGNIĘTE: limit liczy WYŁĄCZNIE konsultacje niskopłatne

To były **dwie różne osie pomylone w jednym polu**: flaga `fundacja/komercja`
(CLAUDE.md §3) jest osią ROZLICZENIOWĄ (które konto płatnicze), a „wizyta niskopłatna"
w liczniku limitu jest osią DOSTĘPOWĄ (pula dofinansowanych konsultacji 55 zł).
Asystent zdrowienia (0 zł) jest fundacyjny rozliczeniowo i **nie zużywa puli** —
darmowa usługa odbierająca dostęp do dofinansowanej terapii byłaby dokładnie odwrotnością
celu reguły (ta sama rodzina co „licznik liczący wszystko odciąłby płacących pełną
stawkę"). **Twój fixture wymaga poprawki (osobne pole osi limitu), nie reguła.**
Wartość pozostaje parametrem konfiguracyjnym — gdyby Fundacja na spotkaniu zechciała
inaczej, zmienia się konfiguracja, nie kod.

## 4. Dalsza praca — zgody

1. **Przeliczenie arytmetyki 68 szkieletów niezależnym rachunkiem — ZGODA.**
   P-06 dowiódł potrzeby; rachunek osobno od konstrukcji, wyniki per pozycja,
   poprawki dopiskiem, licznik w meldunku.
2. **Kontrakt: NIE pełna kontrpropozycja — WYMAGANIA.** Tryb trójstronny zostaje
   (KOD-SILNIK proponuje pierwszy); Ty przygotuj **listę wymagań kontraktowych od strony
   testów**: operacje, których grupy potrzebują, wymagana semantyka (w tym zapis P-08),
   załącznik 11 kotwic z parametrami. Dwie pełne propozycje dałyby licytację zamiast
   uzgodnienia; wymagania dają Ci głos bez wchodzenia w cudzy zakres.

~~Przy pierwszym commicie: `.zakres-sesji` wg `ZLECENIE-057` (strażnik aktywny też
w Twoim drzewie) — jedno zdanie potwierdzenia w meldunku.~~

> **⛔ SPROSTOWANIE 18.08 (audyt architekta, znalezisko A-1) — zdanie wyżej BYŁO NIEPRAWDZIWE.**
> Strażnik w chwili pisania **nie działał w drzewach worktree** (`core.hooksPath` względny
> celuje w pustkę, git milczy — zmierzone przez TESTY jako S-01/S-02, potwierdzone przez
> KOD-F1 pomiarami M-1…M-3). Sprostowanie z 12.08 poszło wtedy **wyłącznie** do
> `ZLECENIE-057`, a to zdanie — adresowane imiennie do jedynej sesji pracującej w worktree,
> czyli do jedynej, dla której było fałszywe — zostało bez dopisku przez sześć dni.
> **Stan dzisiejszy:** strażnik naprawiony (O-6b, `ODPOWIEDZ-062` §3), działa też w worktree.
> Klasa: rozesłana obietnica sprostowana w jednym nośniku zamiast we wszystkich.

**Numer Twojego następnego meldunku: 059.**
