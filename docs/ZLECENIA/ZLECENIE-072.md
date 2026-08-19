# ZLECENIE-072 · 19.08.2026 · OD sesji KOD-F1 DO architekta — PILNE, ŁAMIE CISZĘ

> Łamię ciszę zgodnie z tym, co zapowiedziałem w `ZLECENIE-070` §2: jeśli ostatni
> pomiar wypadnie inaczej niż zielono, zgłaszam natychmiast osobnym plikiem.
> **Numer 072 wzięty dla tego zgłoszenia; zarezerwowany przez Ciebie `ODPOWIEDZ-072`
> zostaje na meldunek po rundzie 10.**

## 0. BRAMKA NA CZUBKU GAŁĘZI JEST CZERWONA — i to przez MÓJ commit dokumentacyjny

**Runda 10 nie może ruszyć, dopóki tego nie rozstrzygniesz** — weryfikator sklonuje
z origin i dostanie czerwień na kroku [21].

## 1. Pomiar, surowe wyjście

Bramka OD ZERA na czubku (`527f1b7`), po commitach dokumentacyjnych:

```
WYKONANO 301 testów (podłoga: 301)
sprawdzono 2170 asercji (podłoga: 2170)
=== [21] sekrety (gitleaks) — ten sam skan co w CI
    ^ KROK NIEUDANY
BRAMKA CZERWONA — 1 nieudanych kroków z 22
KOD=1
```

Skan z tym samym poleceniem, z `-v`:

```
Finding:  .env.example + GOOGLE_CALENDAR_CLIENT_SECRET=REDACTED
RuleID:   generic-api-key
File:     docs/rundy/RUNDA-9-RAPORT.md
Line:     340
Commit:   527f1b7e35585a6e6ffd01570fddf4e939b9eb2d
```

**Na `528adc3` (zamrożenie KODU) bramka była ZIELONA** — 22/22, kod 0. Czerwień
wnosi wyłącznie commit dokumentacyjny.

## 2. Co się stało i czyja to wada

`RUNDA-9-RAPORT.md:340` cytuje **pełną wartość** sekretu użytego w dowodzie R9-3
(`GOCSPX-` + 32 znaki hex). Wartość jest zmyślona — pochodzi z perturbacji
weryfikatora, nie jest żadnym żywym poświadczeniem. To jednak nie zmienia dwóch rzeczy:

1. `generic-api-key` widzi ją jako sekret i **słusznie** — po to ta reguła jest;
2. komunikat kroku [21] mówi wprost, co robić w tej sytuacji, i mówi to od rund:
   *„jeśli to ZACYTOWANY IDENTYFIKATOR — SKRÓĆ GO. Wartość dowodowa raportu jest
   w RELACJI między odczytami, nie w pełnej wartości; pełny identyfikator
   w dokumencie to sam w sobie drobny wyciek. Skrócenie usuwa PRZYCZYNĘ."*

**Wada jest moja, nie weryfikatora.** On pisał raport i nie commitował niczego
(zakaz commitowania). Ja ten plik zacommitowałem — i nie przeczytałem go pod kątem
kroku, który sam przez cztery cykle naprawiałem. Zamroziłem kod, zmierzyłem bramkę
na `528adc3`, a **po commicie dokumentacyjnym bramki nie przemierzyłem przed
napisaniem meldunku** — dokładnie ta sama luka procedury, którą godzinę wcześniej
sam sobie wypisałem w `ZLECENIE-070` §0.

Poprawiłem tam procedurę o warunek zamrożenia. Powinienem był poprawić ją szerzej:
**po KAŻDYM commicie, także dokumentacyjnym, bramka biegnie jeszcze raz** — bo
gitleaks skanuje historię, więc commit dokumentacyjny potrafi ją zaczerwienić.
Tak od tej chwili robię i wpisuję to do meldunku jako zmianę procedury, nie jako
obietnicę.

## 3. Naprawa PRZYCZYNY — gotowa, niezacommitowana

W drzewie roboczym skróciłem wartość w raporcie:

```
- GOOGLE_CALENDAR_CLIENT_SECRET=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8c07
+ GOOGLE_CALENDAR_CLIENT_SECRET=GOCSPX-9f2b…c07  (wartość SKRÓCONA 19.08 …)
```

Wartość dowodowa raportu nie ucierpiała: dowód R9-3 polega na RÓŻNICY między dwoma
przebiegami (`.env.example` → `no leaks`, `docs/probka` → `leaks found: 1`), a nie
na pełnym ciągu znaków. Nie commituję — cisza obowiązuje, a rozstrzygnięcie
o historii i tak jest Twoje.

## 4. Historia — dwie drogi, obie Twoje

`527f1b7` **jest już na `origin/faza-1-retencja`** (sprawdzone
`git branch -r --contains`), więc przepisanie historii to **wymuszony push**.
Nie wykonuję takich operacji sam i nie zamierzam.

| wariant | koszt | uwaga |
|---|---|---|
| **A. Wąski wyjątek w `.gitleaks.toml`** na ten JEDEN commit i tę JEDNĄ wartość, z warunkiem znoszącym przy scalaniu | jeden wpis, znany kształt (dokładnie jak D-4) | dokłada drugi dług tej samej rodziny; D-4 i tak wymaga przepisania historii w O-1, więc oba zniknęłyby razem |
| **B. Przepisanie historii teraz** (`filter-repo`/`filter-branch` + force-push) | operacja na wypchniętej gałęzi | usuwa przyczynę do końca, ale w trakcie rundy 10 i na gałęzi, którą właśnie klonuje weryfikator |

**Rekomenduję A**, dokładnie z powodu, dla którego przyjąłeś A przy D-4: wartość
nie jest żywym poświadczeniem, dług ma termin (O-1 listy scaleniowej), a przepisywanie
wypchniętej gałęzi w oknie rundy jest ryzykiem nieproporcjonalnym do zysku.

Jeśli wybierzesz A, potrzebuję jednego zdania zgody na commit dokumentacyjny mimo
ciszy — wtedy w jednym commicie: skrócenie wartości + wpis w `.gitleaks.toml`
z warunkiem znoszącym + przemierzona bramka, której surowe wyjście wkleję.

## 5. Czego NIE zrobiłem

- **Nie dopisałem wyjątku sam.** Dwa razy w tej sesji zgłaszałem Ci rzeczy do
  rozstrzygnięcia zamiast rozstrzygać je jednostronnie i oba razy potwierdziłeś,
  że to właściwa kolejność. Wyjątek w skanerze sekretów jest dokładnie taką rzeczą.
- **Nie ruszyłem historii.**
- **Nie zacommitowałem skrócenia** — cisza.

Stan do decyzji: kod zamrożony na `528adc3` (bramka 22/22 zielona), czubek
`527f1b7` z czerwonym krokiem [21], naprawa przyczyny gotowa w drzewie.
