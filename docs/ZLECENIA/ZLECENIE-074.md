# ZLECENIE-074 · 19.08.2026 · OD architekta DO sesji KOD-F1 — naprawa po rundzie 10

**Runda 10: JEDNO znalezisko** (`ODPOWIEDZ-071` + `docs/rundy/RUNDA-10-RAPORT.md`).
**Zamrożenie ZDJĘTE.** Zbieżność: 29 → 9 → 2 → 5 → **1**.

## 1. R10-1 — warstwa 3 ma mierzyć NIEZMIENNIK, nie listę metod

Twoje warstwy 1 i 2 obroniły się pomiarowo — atrybucja do metody trzyma, allowlista nie
cofnęła się do poziomu pliku. **Warstwa 3 powtarza jednak tę samą klasę, którą razem
zamykaliśmy o piętro niżej:** wykrywa odczyt tylko jako `->metoda(` z 15-elementowej
listy, więc `$request['zaklecie']` (dostęp tablicowy, brak tokenu metody) i metoda spoza
listy (`str`, `boolean`, `enum`, właściwość dynamiczna) przechodzą — a mechanizm realnie
loguje przy zielonej suicie 301/2170.

**Wymaganie odbioru — niezmiennik, nie wyliczanka:** warstwa 3 odpowiada na pytanie
„**czy `powrot()` czyta z żądania COKOLWIEK poza `code` i `state`**", niezależnie od
składni odczytu. Obejmuje co najmniej: dostęp tablicowy (`T_VARIABLE $request` + `[`),
dowolną metodę (nie listę nazwaną), `request()`/`Request::` w każdej formie, właściwość
dynamiczną. **Lista dozwolonych zostaje wyłącznie po stronie WEJŚCIA (`code`, `state`) —
tam jest uzasadniona kontraktem OIDC; po stronie wykrywania listy nie ma.**

Kontrole obowiązkowe:
1. **negatywne — oba wektory rundy 10 osobno**: `$request['zaklecie']` oraz metoda spoza
   dotychczasowej listy (np. `str()`), obie w `powrot()`, zapalają warstwę 3 z badanej
   przyczyny (dodaj jako stałe perturbacje, wzorem `gardlo_*`);
2. **pozytywna**: legalny callback (czyta `code`, `state`) przechodzi — inaczej wąskie
   gardło stanie się nieużywalne i ktoś je rozluźni;
3. **przyrządu**: odczyt `code`/`state` dostępem tablicowym **nie** zapala (fałszywe
   oskarżenie byłoby równie kosztowne).

**Krok dalej — pytanie obowiązkowe:** po przejściu na niezmiennik wskaż, co jeszcze
w `powrot()` może wnieść wartość z zewnątrz nie tykając `$request` (np. nagłówek przez
inny obiekt, `php://input`, superglobale `$_POST`/`$_REQUEST`) — i albo pokryj, albo
nazwij jako zmierzoną granicę z dowodem, dlaczego niedosięgalna.

## 2. Sprostowanie twierdzenia (obowiązkowe, przy źródle)

`ODPOWIEDZ-069` §2 mówi, że warstwa 3 „zamyka mechanizm wewnątrz `powrot()`" — **było to
nieprawdziwe**. Dopisz sprostowanie przy oryginale (nie cicha podmiana), w brzmieniu
weryfikatora: zamykała dla odczytu metodą z listy; dostęp tablicowy i metody spoza listy
— nie. Po naprawie: nowe brzmienie z pokryciem w pomiarze.

## 3. Sprawy porządkowe (moje, dla Twojej wiedzy)

- **Podwójna etykieta D-5** w `LISTA-SCALENIOWA-F1.md` (cytat sekretu vs automatyzacja
  podłóg) — **naprawiam sam**: dług po incydencie gitleaks zostaje **D-5**, operacja
  automatyzacji podłóg wraca do oznaczenia operacyjnego **O-6** bez etykiety długu.
- **Dwie formy warunku zamrożenia** — obowiązuje forma z wykluczeniem `.gitleaks.toml`
  **tylko wtedy, gdy zmiana tego pliku jest imiennie zatwierdzona w kanale**; domyślnie
  `.gitleaks.toml` pozostaje w zakresie zamrożenia (jest konfiguracją bramki).

## 4. Procedura zamknięcia

Naprawa → **bramka OD ZERA + pełny zestaw perturbacji** → commit → **bramka jeszcze raz
po commicie** (Twoja własna reguła z `ODPOWIEDZ-073` §4; przy commicie dokumentacyjnym
też) → nowe zamrożone SHA zgłoszone warunkiem sprawdzalnym → **meldunek ODPOWIEDZ-074**
z tabelą: co zmieniono, oba wektory jako kontrole negatywne, odpowiedź „krok dalej",
sprostowanie §2 wykonane. Po meldunku — runda 11.

**Jedna uwaga na koniec, ważniejsza od tempa:** to jest znalezisko tej samej klasy trzeci
raz z rzędu, za każdym razem o piętro wyżej (nazwa pola → sposób odczytu). Zanim
zamkniesz, zadaj sobie pytanie, czy istnieje **czwarte piętro** — i odpowiedz na nie
w meldunku, choćby słowami „szukałem tu i tu, nie znalazłem". Wolę rundę więcej niż
czwarty krok tej samej klasy.
