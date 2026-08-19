# ZLECENIE-068 · 18.08.2026 · OD sesji KOD-F1 DO architekta

## Rzecz do rozstrzygnięcia, ZANIM runda 9 ruszy

`ZLECENIE-067` mówi:

> **SHA: `d79dc0c…`** … **jest czubkiem gałęzi** — jakikolwiek commit ponad nim
> = znalezisko.

**Czubkiem jest dziś `b5cd83f`**, mój commit dokumentacyjny. Zgłaszam go sam,
zanim weryfikator znajdzie go jako niespodziankę.

## Pomiar

```
b5cd83f  Meldunek ODPOWIEDZ-066 + raport rundy 8 i kanal do repozytorium
         10 plików, wszystkie w docs/ (ZLECENIA/ + rundy/)

git diff --stat d79dc0c..HEAD -- backend/ skrypty/ .gitleaks.toml   →  PUSTO
```

Kod zamrożony na `d79dc0c` jest nietknięty.

## Kolejność zdarzeń, bo ona tu rozstrzyga

```
21:23:53   commit b5cd83f
21:24:03   powstaje ZLECENIE-067.md
```

Dziesięć sekund. Zdanie „jest czubkiem gałęzi" było nieaktualne już w chwili
zapisu — nie z Twojej winy i nie przez moje przeoczenie: `ZLECENIE-066` §3 mówi
wprost **„commity dokumentacyjne dozwolone i zgłaszane; potem cisza całkowita"**,
więc ten commit był w zakresie zgody, a to jest właśnie jego zgłoszenie.

Wyścig, nie błąd — ale weryfikator zmierzy czubek i zobaczy rozjazd z treścią
własnego zlecenia. Dlatego piszę teraz, a nie po rundzie.

## Dlaczego nie cofam tego commita sam

`b5cd83f` wnosi do repozytorium `ODPOWIEDZ-066` i `docs/rundy/RUNDA-8-RAPORT.md`.
`ZLECENIE-067` §„Zamknięcia do zweryfikowania" każe weryfikatorowi sprawdzać
zamknięcia **wg `ODPOWIEDZ-066`** — cofnięcie zabrałoby mu z klonu dokument,
na którym ma pracować. To byłoby gorsze niż jawny commit ponad zamrożeniem.

Rozstrzygnięcie i tak nie jest moje, więc go nie podejmuję.

## Trzy warianty — rekomendacja pierwsza

1. **Dopisz `b5cd83f` do znanych commitów dokumentacyjnych** w `ZLECENIE-067`
   (czubek = `b5cd83f`, diff kodu wobec `d79dc0c` pusty — zmierzone wyżej).
   Tak samo rozstrzygnąłeś `ODPOWIEDZ-065`; koszt: jedno zdanie.
2. Cofnę `b5cd83f`. Koszt: weryfikator nie ma w klonie meldunku ani raportu
   rundy 8, a zlecenie każe mu z nich korzystać.
3. Przeniesiesz zamrożenie na `b5cd83f`. Koszt: przemierzanie identycznego kodu.

**Rekomenduję 1.** Do decyzji nie ruszam ani kodu, ani gałęzi.

## Uwaga o procedurze na przyszłość

Ten wyścig zdarzył się DRUGI RAZ w tej samej postaci (poprzednio `ZLECENIE-065`).
Za każdym razem wychodzi tak samo: zamrożenie ogłaszasz na SHA, ja dokładam
dozwolony commit dokumentacyjny, a zlecenie rundy zdążyło już powiedzieć
„to jest czubek".

Propozycja, żeby trzeciego razu nie było — do Twojej decyzji, nie robię tego sam:
**zamrożenie definiuj jako „SHA kodu + dowolne commity dotykające wyłącznie
`docs/`"**, zamiast jako czubek gałęzi. Wtedy warunek jest sprawdzalny jednym
poleceniem, którego wynik nie zależy od tego, kto pisał ostatni:

```
git diff --stat <SHA>..HEAD -- backend/ skrypty/ .gitleaks.toml   →  musi być PUSTO
```

To ta sama zamiana co przy `ZLECENIE-054`: warunek sprawdzalny zamiast
identyfikatora, który starzeje się sam.

## Uwaga o tym pliku

Zapisany, **NIE zacommitowany** — zgodnie z „potem cisza całkowita"
z `ZLECENIE-066` §3 i tak samo jak Twoje pliki kanału, które leżą w drzewie
jako nieśledzone.
