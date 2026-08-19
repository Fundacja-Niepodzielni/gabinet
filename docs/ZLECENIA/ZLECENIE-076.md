# ZLECENIE-076 · 19.08.2026 · OD sesji KOD-F1 DO architekta — PILNE, ŁAMIE CISZĘ

> Łamię ciszę tak, jak zapowiedziałem w `ODPOWIEDZ-074` §7: bramka po commicie
> dokumentacyjnym wypadła inaczej niż zielono, więc zgłaszam natychmiast.
> **Numer 076 wzięty dla tego zgłoszenia** (`ODPOWIEDZ-075` należy do rundy 11).

## 0. Jednym zdaniem

Krok [21] na czubku gałęzi jest **CZERWONY** — mój commit dokumentacyjny `661e8a6`
wniósł do historii pełną wartość sekretu **po raz drugi**, tym razem w pliku,
w którym opisywałem jej usuwanie. **Runda 11 NIE jest zablokowana** (uzasadnienie
pomiarowe w §3). Kod zamrożony `bbc8167` jest zielony.

## 1. Pomiar, surowe wyjście

Bramka OD ZERA po commicie dokumentacyjnym:

```
    WYKONANO 304 testów (podłoga: 304)
    sprawdzono 2211 asercji (podłoga: 2211)
6:28AM INF 158 commits scanned.
6:28AM WRN leaks found: 1
    ^ KROK NIEUDANY
BRAMKA CZERWONA — 1 nieudanych kroków z 22
KOD=1
```

```
Finding:  - GOOGLE_CALENDAR_CLIENT_SECRET=REDACTED
RuleID:   generic-api-key
File:     docs/ZLECENIA/ZLECENIE-072.md
Line:     69
Commit:   661e8a66b4980d70f93421f688110f20382734dd
```

Ta sama bramka **przed** commitem dokumentacyjnym (na `bbc8167`): `BRAMKA OK —
22 kroków, 0 nieudanych`, `no leaks found`, 157 commitów, kod 0.

## 2. Co się stało — wart odnotowania kształt wady

W `ZLECENIE-072.md` opisywałem naprawę D-5 i pokazałem ją **diffem**. Linia „przed"
tego diffu niosła pełną wartość sekretu.

**Dokument opisujący redakcję odtworzył zredagowany sekret.** Usunąłem wartość
z raportu rundy 9 i w tym samym ruchu wpisałem ją do pliku o tym, że ją usuwam.

Cytat sekretu nie przestaje być cytatem sekretu przez to, że stoi w zdaniu o jego
usuwaniu — to zdanie dopisałem do `ZLECENIE-072` w miejscu, w którym stał diff.

Skala w drzewie była większa, niż pokazał skaner: pełna wartość stała w **trzech**
miejscach (`RUNDA-10-RAPORT.md` ×2, `ZLECENIE-072.md` ×1). Gitleaks zgłosił jedno,
bo pozostałe wystąpienia nie mają kształtu przypisania. **Naprawiłem wszystkie trzy** —
naprawa jednego zgłoszonego byłaby naprawą instancji, nie przyczyny.

## 3. Dlaczego runda 11 NIE jest zablokowana — pomiar, nie pociecha

`ZLECENIE-075` każe weryfikatorowi **przyciąć refy potomne do `bbc8167`** przed
skanem sekretów. Commit `661e8a6` jest potomkiem zamrożenia, więc po przycięciu
nie wchodzi w zakres skanu — tak samo jak `527f1b7`, o którym zlecenie mówi wprost.

Sprawdzalne jednym poleceniem:

```
git merge-base --is-ancestor 661e8a6 bbc8167   →  FAŁSZ (661e8a6 jest POTOMKIEM)
```

Runda mierzy `bbc8167`, a tam bramka jest zielona. **Nie wstrzymuj rundy z mojego
powodu.**

## 4. Naprawa PRZYCZYNY — gotowa, niezacommitowana

Trzy wystąpienia skrócone; w `ZLECENIE-072.md` diff zastąpiony opisem słownym,
żeby dokument nie odtwarzał wartości. W drzewie roboczym pełnej wartości **nie ma**:

```
grep -rc "<pełna wartość>" docs/   →  0
```

Nie commituję — cisza rundy 11 obowiązuje, a rozstrzygnięcie o historii jest Twoje.

## 5. Historia — rekomendacja bez zmian, ale z jedną uwagą

`661e8a6` jest na `origin/faza-1-retencja`, więc przepisanie historii to wymuszony
push. Nie wykonuję takich operacji sam.

**Rekomenduję rozszerzyć istniejący wpis D-5** o ten commit — nie tworzyć D-6.
Powód: to ta sama wartość, ta sama reguła, ten sam termin zniesienia (O-2b) i to
samo jedno przepisanie historii ją usunie. Dwa wpisy na jedną wartość rozjechałyby
się przy zdejmowaniu, a `O-2b` mówi wprost, że usunięcie tylko jednego z dwóch
wpisów jest znaleziskiem — trzy wpisy zwiększają tylko szansę na taki rozjazd.

Konkretnie: do `commits` przy D-5 dopisać `661e8a66b4980d70f93421f688110f20382734dd`,
resztę wpisu zostawić bez zmian (`condition = "AND"`, jedna wartość, jedna reguła).

## 6. Wniosek procedurny — moja reguła zadziałała, ale za późno o jeden krok

Reguła „po KAŻDYM commicie bramka biegnie jeszcze raz" (`ODPOWIEDZ-073` §4)
**zadziałała**: złapała to natychmiast, zanim zdążyłem zameldować zielone.
Poprzednim razem dowiedziałem się tego samego od Ciebie.

Brakuje jednak kroku wcześniej — kontroli, która zapala **przed** commitem, w suicie.
Propozycja, **nie wykonuję jej bez Twojej zgody i nie w trakcie rundy**:

> rozszerzyć `SekretyTest` o skan `docs/` pod kątem **kształtu** wartości
> (ten sam predykat, którego użyłem dla `.env.example`: długość ≥ 24, ≥ 3 klasy
> znaków, nie URL, bez spacji), z jawną listą znanych cytatów historycznych.

Wtedy „sekret w dokumencie" pada w `pest`, czyli przed commitem, a nie w kroku [21]
po nim. Dziś jedyną kontrolą tej klasy jest gitleaks — a jego przedmiotem jest
**historia**, więc z natury odzywa się dopiero, gdy jest już za późno.

## 7. Stan do decyzji

- kod zamrożony `bbc8167` — bramka 22/22, `no leaks`, kod 0;
- czubek `661e8a6` — krok [21] czerwony, przyczyna usunięta w drzewie;
- runda 11 może biec (§3);
- czekam na: zgodę na commit mimo ciszy + rozstrzygnięcie o D-5 (§5) + stanowisko
  wobec propozycji z §6.
