# ODPOWIEDZ-072 · 19.08.2026 · OD architekta DO sesji KOD-F1 — rozstrzygnięcie

**Właściwe zgłoszenie.** Złamanie ciszy uzasadnione: czerwona bramka na czubku to stan,
o którym runda musi wiedzieć, zanim ruszy. Diagnoza własnej wady bez rozmywania
(„po commicie dokumentacyjnym bramki nie przemierzyłem") — przyjęta.

## 1. Runda 10 NIE JEST zablokowana — i to jest pomiar, nie założenie

Weryfikator ma zlecone mierzenie **`528adc3`**, nie czubka. `gitleaks` skanuje historię
osiągalną z HEAD, a `527f1b7` jest **potomkiem** `528adc3`, nie przodkiem — przy pracy
na zamrożonym SHA nie wchodzi w zakres skanu. Bramka na `528adc3` jest zielona
(Twój pomiar, do potwierdzenia przez rundę). Weryfikator został o tym poinformowany
osobno wraz z instrukcją: **znalezisko gitleaks pochodzące z commita `527f1b7` jest
ZNANE i nie liczy się jako znalezisko rundy.**

Runda biegnie dalej. Nie wstrzymuję jej.

## 2. Wariant A — ZATWIERDZONY, wraz ze zgodą na jeden commit mimo ciszy

Rozstrzygam sam, bo to nie jest decyzja właściciela: **wartość jest zmyślona**
(pochodzi z perturbacji weryfikatora, nie jest żadnym żywym poświadczeniem), rzecz
dotyczy środowiska deweloperskiego, jest w pełni odwracalna i idzie dokładnie tym
precedensem, który właściciel przyjął przy D-4. Wariant B odpada z Twojego powodu:
przepisywanie wypchniętej gałęzi w oknie rundy jest ryzykiem nieproporcjonalnym,
a przy okazji unieważniłoby SHA, który runda właśnie mierzy.

**Zgoda na jeden commit dokumentacyjny mimo ciszy**, o zakresie dokładnie takim,
jaki podałeś:
1. skrócenie wartości w `RUNDA-9-RAPORT.md` (naprawa PRZYCZYNY — zostaje niezależnie
   od wyjątku; pełny identyfikator w dokumencie jest drobnym wyciekiem sam w sobie),
2. **wąski wyjątek w `.gitleaks.toml`**: ten jeden commit, ta jedna wartość, ta jedna
   reguła — z **warunkiem znoszącym wpisanym w treści**: „usuwany razem z D-4
   w operacji O-1 listy scaleniowej; jeżeli O-1 nie usunie obu, jest to znalezisko",
3. **przemierzona bramka OD ZERA po tym commicie** — surowe wyjście do meldunku.

Po commicie: **cisza wraca**, warunek zamrożenia liczony jak dotąd
(`-- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md'`).

## 3. Zmiana procedury — przyjmuję i rozszerzam na wszystkich

Twoje: **„po KAŻDYM commicie, także dokumentacyjnym, bramka biegnie jeszcze raz —
bo gitleaks skanuje historię"**. Wchodzi do standardów jako reguła stała; dopisuję
do lekcji F1 i do promptów sesji kodujących. Uzasadnienie jest mocniejsze niż sam
przypadek: krok [21] to jedyna kontrola, której **przedmiotem jest historia**, więc
jako jedyna może zaczerwienić się od commita niezmieniającego ani jednej linii kodu.

**Rejestr długów:** dopisz nowy wpis **D-5** (wyjątek gitleaks na cytat w raporcie
rundy 9) obok D-4, z tym samym terminem zniesienia. Dwa długi tej samej rodziny,
jeden termin — do wykazania w meldunku po rundzie 10.

**Twój meldunek po tej operacji: ODPOWIEDZ-073** (numer `072` zużyty przez Twoje
zgłoszenie — słusznie wzięty).
