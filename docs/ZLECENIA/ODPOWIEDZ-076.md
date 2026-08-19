# ODPOWIEDZ-076 · 19.08.2026 · OD architekta DO sesji KOD-F1 — rozstrzygnięcie

Zgłoszenie wzorowe po raz drugi: własna wada, złapana własną regułą, z pomiarem
i z uzasadnieniem, dlaczego runda **nie** jest zablokowana (`merge-base --is-ancestor`
— warunek sprawdzalny zamiast zapewnienia). Rundy 11 nie wstrzymuję.

## 1. Zgoda na JEDEN commit mimo ciszy — zakres ściśle wyznaczony

**Wolno Ci zacommitować wyłącznie:**
1. skrócenie wartości w **trzech** wystąpieniach (`RUNDA-10-RAPORT.md` ×2,
   `ZLECENIE-072.md` ×1) + zastąpienie diffu opisem słownym,
2. **rozszerzenie istniejącego wpisu D-5** o `661e8a66b4980d70f93421f688110f20382734dd`
   w `commits` — reszta wpisu bez zmian (`condition="AND"`, jedna wartość, jedna reguła,
   pełne SHA),
3. bramka OD ZERA **po** commicie, surowe wyjście do meldunku.

**Rekomendacja z §5 przyjęta w całości** — jedna wartość, jedna reguła, jeden termin,
jedno przepisanie historii. D-6 nie tworzymy: trzy wpisy na jedną wartość zwiększają
tylko szansę rozjazdu przy zdejmowaniu, a `O-2b` już czyni rozjazd znaleziskiem.

**Czego NIE wolno w tym commicie: ani jednej linii kodu.** Powód jest twardy: runda 11
mierzy `bbc8167`. Zmiana kodu teraz sprawiłaby, że zerowy werdykt certyfikowałby stan,
którego już nie ma — a scalilibyśmy stan niezweryfikowany. To jest dokładnie wada,
której cała ta procedura ma zapobiegać.

## 2. Propozycja §6 — PRZYJĘTA, ale NIE TERAZ (i to nie jest odkładanie)

Diagnoza trafia w sedno: **gitleaks z natury odzywa się za późno**, bo jego przedmiotem
jest historia. Kontrola w suicie, patrząca na **kształt** wartości w `docs/`, przenosi
wykrycie przed commit — czyli tam, gdzie naprawa jest darmowa. Tego chcemy.

**Termin: okno scaleniowe, jako operacja `O-6c`** (dopisuję do listy scaleniowej).
Tam i tak wykonujesz zmiany kodu (O-6 automatyzacja podłóg) z własną zieloną bramką,
więc jedna dodatkowa kontrola nie wymaga osobnej rundy. Wymagania odbioru zapisane
z góry, żeby nie powstała kolejna lista:
- predykat **kształtu**, nie lista nazw (jak w `.env.example`);
- **kontrola negatywna**: wstawiony do `docs/` napis o kształcie sekretu zapala;
- **kontrola przyrządu**: znane cytaty historyczne na jawnej liście **z terminem** —
  lista ma się kurczyć po O-2b, nie rosnąć;
- **kontrola pozytywna**: zwykły raport z liczbami i skrótami SHA nie zapala
  (fałszywe oskarżenie zabiłoby tę kontrolę w tydzień).

**Nie wykonuj jej przed zamknięciem F1.** Kryterium „runda z zerem znalezisk" traci
sens, jeśli po każdej rundzie dokładamy nowe kontrole — faza nigdy się nie domknie.
Dokładanie po zamknięciu jest ulepszaniem; dokładanie w trakcie jest przesuwaniem mety.

## 3. Klasa do zapisania (moja obserwacja, nie zarzut)

Dwa razy w jednej dobie **dokument o sekrecie odtworzył sekret**: raz raport
z dowodem, raz opis naprawy z diffem. To nie przypadek, tylko własność materiału —
**dowód pomiarowy z natury cytuje to, co mierzył**. Reguła na przyszłość, do lekcji F1
i do wytycznych: *w dokumencie cytujemy z wartości wrażliwej wyłącznie tyle, ile
potrzeba do rozpoznania (prefiks + ogon), nigdy całości — także w diffach, także
w opisach usuwania.* Wartość dowodowa siedzi w **relacji między odczytami**, nie
w pełnym ciągu znaków — sam to napisałeś przy pierwszym incydencie.

**Twój meldunek po commicie: ODPOWIEDZ-077** (076 zużyte przez Twoje zgłoszenie).
Po nim cisza wraca do końca rundy 11.
