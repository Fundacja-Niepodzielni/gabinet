# ODPOWIEDZ-032 — kontrola unieważnienia jako MIDDLEWARE + pomiar okna na ścieżce najdłuższej

**09.08.2026, noc** · pozycja z `PODJETO-032` (źródło: własne znalezisko z `ODPOWIEDZ-031`)
· zakres poszerzony o wymagania z `ZLECENIE-032` i `ZLECENIE-033`

---

## 1 · Co było zepsute — jednym zdaniem

Kontrola unieważnienia **istniała i działała**, ale była **usługą wołaną przez kontroler**,
a nie własnością trasy. Blok `withMiddleware` był **pusty**. Zmierzone w `ODPOWIEDZ-031`:
kontrolę stosowała **jedna trasa z 34**.

> **Zasięg kontroli bezpieczeństwa wyznaczała pamięć autora trasy.** Każda następna trasa
> chroniona, dopisana poza tym jednym kontrolerem, nie miała kontroli — i **nic tego nie zgłaszało**.

To ta sama rodzina, co `R6A-11`: mechanizm sprawny, wywołania brak.

## 2 · Co zrobione

**a) Kontrola przeniesiona do middleware'u** (`App\Http\Middleware\SprawdzUniewaznienie`),
zarejestrowanego w grupach `web` i `api`. **Zasięg wyznacza teraz konstrukcja.**

**b) Wyjątki zadeklarowane jako DANE** (`App\Tozsamosc\WyjatkiUniewaznienia`) — 8 wpisów,
każdy z **powodem** i **warunkiem znoszącym**. To wymóg z `ZLECENIE-032`: bez tego naprawiłbym
zasięg i zostawił `D6`, bo **świadomy wyjątek i przeoczenie mają w kodzie ten sam kształt —
nieobecność, a nieobecność nie niesie intencji.**

**c) Koszt wyjątku zrównany z kosztem zgodności.** Zmierzone u siebie tego samego dnia: w rejestrze
retencji wpis kosztował podstawę i opis >20 znaków, a wpis na liście wyjątków **nie kosztował nic** —
czyli dopisanie się do wyjątków było najtańszą drogą wyciszenia kontroli. Tutaj wpis bez powodu
albo bez warunku znoszącego jest **czerwony**.

**Warunek znoszący mówi, CO PRZESTANIE CZYNIĆ TEN WYJĄTEK SŁUSZNYM.** Powód bez warunku
znoszącego jest wiecznym zwolnieniem.

## 3 · ⚠ KONTROLA POZYTYWNA ZŁAPAŁA BŁĄD PRZYRZĄDU — to jest najważniejsza rzecz w tej pozycji

Pierwsze uruchomienie: **kontrola główna zielona po dopisaniu wszystkiego do wyjątków, kontrola
pozytywna CZERWONA.** Zmierzona przyczyna:

> Grupy middleware wpisuje do routera **jądro HTTP**, a nie jądro konsoli. Bez jego dotknięcia
> `getMiddlewareGroups()` zwraca **0 członków**, a `gatherRouteMiddleware()` oddaje
> **nierozwinięty napis `web`**.

Mój skaner meldowałby więc **ZEROWE pokrycie przy pełnym** — i wyglądałoby to identycznie jak
prawdziwy brak. Bez `auth/ja` jako kontroli pozytywnej **naprawiłbym to, dopisując 8 tras do
wyjątków, i zameldował zielone**.

**To dokładnie ten przypadek, o którym pisałeś w `ZLECENIE-033`.** Kontrola negatywna
(`api/wersja` musi wyjść jako NIEchroniona) pilnuje drugiego kierunku: skanera, który zwraca
`true` bez patrzenia.

## 4 · Pomiar okna — ścieżka najdłuższa

Zlecenie kazało zmierzyć okno **na ścieżce najdłuższej**. Zbudowałem trasę, która powstaje
**w teście** i o kontroli nie wie nic — tak wygląda trasa dopisana przez człowieka realizującego
swoje zadanie. Bez tego łamałbym regułę `C1`: dotąd **jedyna trasa wołająca kontrolę była też
jedyną testowaną — kontrola sprawdzała samą siebie.**

| stan | przed przebudową | po |
| --- | --- | --- |
| okno dla trasy **objętej** kontrolą | jedno żądanie | jedno żądanie |
| okno dla trasy **nieobjętej** | **nieograniczone** — do wygaśnięcia ciasteczka | jedno żądanie |
| ile tras było objętych | **1 z 34** | wszystkie oprócz **8 zadeklarowanych** |

**Okno nie skróciło się dla trasy chronionej — ono przestało być nieskończone dla reszty.**
Prawdziwą zmianą nie jest długość okna, tylko to, **że przestało zależeć od czyjejś pamięci.**

## 5 · Dowody

| co | dowód |
| --- | --- |
| każda trasa: kontrola albo deklaracja | `ZasiegUniewaznieniaTest` — porównanie 3 zbiorów |
| martwy wpis w wyjątkach (trasa nie istnieje) | tamże, kierunek odwrotny |
| wyjątek bez powodu / bez warunku znoszącego | tamże |
| przyrząd żywy / przyrząd mierzący co innego | tamże, kontrola **pozytywna** i **NEGATYWNA** |
| nowa trasa chroniona bez wiedzy autora | `NowaTrasaJestChronionaTest` — 401 |
| 401 pochodzi z unieważnienia, nie skądinąd | tamże, kontrola negatywna — ta sama trasa, 200 |
| brak sesji ≠ unieważnienie | tamże |
| okno = najbliższe żądanie | tamże, bez przesuwania zegara |

**Perturbacja** (kopia przez `cp`, nigdy `git checkout` — po stracie pracy 09.08):
usunięcie rejestracji w grupie `web` → **4 czerwone**; przywrócenie → **9 zielonych**,
obie rejestracje na miejscu.

**Suita: 232 zielone (było 223), 2 pominięte, 1930 asercji, jeden czerwony — noga 1, zamierzony.**
Podłogi bramki podniesione 223/1912 → **232/1930**. Pint: naprawił 2 drobiazgi, czysto.

## 6 · Czego ta pozycja NIE rozstrzyga — mówię wprost

1. **`up` nie da się objąć grupą.** Trasa zdrowia frameworka rejestruje się **poza** `web` i `api` —
   zmierzone: **zero** middleware'ów. Jest zadeklarowana jako wyjątek, ale to wyjątek **wymuszony
   konstrukcją frameworka**, nie wybrany. Gdyby kiedyś zaczęła cokolwiek ujawniać, potrzebuje
   własnego strażnika — grupa jej nie obejmie.
2. **`storage/{path}` tak samo** — poza grupami. Dziś nie serwuje danych osobowych. Warunek
   znoszący zapisany w kodzie.
3. **Horizon i `docs/api` są NIEZMIERZONE, nie czyste.** Niosą własnych strażników i własny cykl
   życia; wyłączyłem je ze skanu i **odnotowuję to jako dług**, tak samo jak w `ODPOWIEDZ-031`.
4. **Nie zmierzyłem, czy `web` i `api` to jedyne grupy.** Gdyby ktoś dodał trzecią, kontrola
   złapie to dopiero przez trasy bez deklaracji — czyli **po fakcie, nie przy dodawaniu grupy**.

## 7 · Stan kanału

Pozycję zamyka ten plik, nie meldunek w oknie (`S-3`). Po nim: commit, push, `PODJETO-041`.
Kolejna pozycja zgodnie z Twoją sekwencją — **studium SMSAPI**, potem `BEZ_DANYCH_OSOBOWYCH`.

**W `POTWIERDZAM-041` zgłosiłem jedną rzecz do rozstrzygnięcia przed startem:** studium wymaga
czytania dokumentacji zewnętrznej. Czytam zakaz „nic poza fundację" jako dotyczący **zgłoszeń
i publikacji**, nie czytania publicznej dokumentacji. Zaczynam z tym założeniem — jeśli jest za
szerokie, powiedz, a przerwę.
