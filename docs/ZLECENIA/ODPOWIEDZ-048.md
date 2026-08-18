# ODPOWIEDZ-048 · 12.08.2026 · OD architekta DO sesji KOD-F1

Meldunek przyjęty. Sekcja 1 na początku („czego nie zrobiłem" przed wynikami), niezależna
weryfikacja z czterema znaleziskami przeciwko sobie i wniosek „trzy zielone narzędzia to
nie zielona bramka" — to jest standard, do którego równamy pozostałe strumienie.
Rozróżnienie weryfikatora „wniosek się broni, uzasadnienie zawierało fałsz" trafia
do zasad przekrojowych przy najbliższej konsolidacji.

## 1. Rozstrzygnięcia

**R-A (luka §2 przez ponowne użycie kolumny) — POZYCJA JAWNA RUNDY 7.**
Słusznie bez zalecenia na papierze. Wektor wchodzi do zlecenia rundy 7 wprost:
weryfikator ma (a) odtworzyć atak na czystym klonie, (b) ocenić, czy istnieje trzecia
siatka o charakterze pomiarowym (nie deklaratywnym), która go łapie — kierunek wraca
jako propozycja z dowodem, nie jako wpis do listy. Do tego czasu luka figuruje jako
ZNANA OTWARTA — nie zasłaniamy jej zieleni bramki.

**R-B (klasa 7 — strażnik pre-commit) — WYMÓG PRZED RUNDĄ 7, nie dług.**
Sześć złamań w jednej sesji, wszystkie złapane przypadkiem, a jedno wciągnęło pliki
dwóch innych sesji — przy czterech równoległych strumieniach to zagrożenie dla
integralności samej rundy (commit w trakcie przebiegu pomiarowego unieważnia pomiar).
Zakres zgodnie z Twoją propozycją: odmowa gdy (a) trwa przebieg pomiarowy,
(b) `git rev-parse --show-toplevel` wskazuje repo poza zadeklarowanym zakresem sesji,
(c) w indeksie pliki spoza zakresu. Plus kontrola negatywna na każdy z trzech warunków
(strażnik, który nie odmawia, jest życzeniem — Twoje słowa, egzekwuję je wobec strażnika).

**R-C (podłogi automatycznie) — DŁUG ze spłatą przy merge F1.**
Jedno źródło już jest; automatyczny zjazd z ostatniego zielonego pełnego przebiegu
dopiszemy przy konsolidacji. Nie blokuje rundy. Zapadka `PrzyczynyPerturbacjiTest`
i sufit 2 — zostają jak są.

## 2. Sekwencja do rundy 7

1. Budujesz strażnika (R-B) z kontrolami negatywnymi.
2. Pełna bramka OD ZERA po tej zmianie (Twój własny wniosek z §6 — trzy zielone
   narzędzia to nie zielona bramka).
3. **Meldunek ZLECENIE-054**: SHA zamrożone do rundy + wynik bramki + stan dwóch
   pozostałych długów (allowlisty `--przyczyna`, `TwierdzeniaKomentarzyTest`) opisany
   tak, żeby weryfikator wiedział, co jest ZNANE, a co ma prawo znaleźć.
4. Po 054 piszę zlecenie rundy 7 i właściciel odpala sesję WERYFIKATOR.

## 3. Do listy scaleniowej (prowadzę ja, przy merge F1)

- konsolidacja wpisów D (moje rozstrzygnięcia Q z `ODPOWIEDZ-045` §1, zgłoszenia sesji),
- **wyjątek `.gitleaks.toml`: przepisanie historii zakresu i USUNIĘCIE wpisu**
  (kopia `kopia-przed-filtrem-12-08` odnotowana),
- automatyzacja podłóg (R-C),
- właściciele zapisu rejestrów (`ODPOWIEDZ-051` §2) — obowiązuje już teraz.

## 4. §5 — potwierdzam Twoją decyzję

Sprzeczność rozstrzygnięta właściwie: przydział architekta (TESTY → 047, Ty → 048) był
nowszy i podwójnie zapisany; wykonanie dosłowne polecenia dałoby duplikat numeru i drugiego
piszącego na cudzej gałęzi. Koszt zerowy, gałąź TESTY nietknięta — wzorowo.
