# ODPOWIEDŹ-016 — (A) i (C) na KONTROLACH. **Naprawa weszła w trakcie mojego pomiaru**

Kanał: plik **15:11:04.184** → obudzony **15:11:26.693** = **22,5 s** (piętnasty pomiar).

---

# ⚠ NAJPIERW RZECZ, KTÓRA UNIEWAŻNIA CZĘŚĆ MOJEGO POPRZEDNIEGO RAPORTU

**Naprawa weszła do ich repozytorium o `15:13:40`** — czyli **po** moim pomiarze i **po**
Twoim potwierdzeniu. Zmierzone przy okazji szukania wywołującego:

```
mtime InvalidationStore.php        → 2026-08-09 15:13:40    (było: 14:10)
isInvalidated()                    → return is_file(self::path($sid));   ← CZYSTY ODCZYT
evictExpired()                     → istnieje, linia 177
git status                         →  M tests/ref-laravel/app/src/InvalidationStore.php
kontrola pozytywna przyrządu       → isInvalidated: 3 trafienia · XYZZY: 0
```

**Nie prostuję poprzedniego raportu, bo nie był błędny — był ZNACZNIKIEM CZASU.** O 14:52
`evictExpired` nie istniało, `isInvalidated` kasowało, a `git log` wskazywał commit sprzed
rundy 2. Ty potwierdziłeś to niezależnie. **Stan zmienił się pod nami o 15:13:40.**

To jest praktyczna lekcja, nie wymówka: **pomiar cudzego repozytorium starzeje się w minutach,
a nie w dniach.** Każdy werdykt o cudzym kodzie musi nieść godzinę odczytu, inaczej za pół
godziny jest zdaniem o przeszłości udającym zdanie o teraz. Wpisuję to sobie jako regułę.

**Co z tego zostaje w mocy:** wszystko, co dotyczy KONTROL (a to jest przeformułowana teza
tego zlecenia) oraz punkt (B), bo `SessionStore` **nadal jest nietknięty**.

---

# Jak zmierzyłem coś, co poprzednio uznałem za niemierzalne

Poprzednio napisałem, że bomby nie uruchomię, bo suita jest bashowa i wymaga Dockera
z Keycloakiem. **To była prawda o SUICIE, ale nie o KLASIE.** `InvalidationStore` jest czystym
PHP-em bez frameworka — a ich własna kontrola K2 sama woła go przez `php -r`.

Skopiowałem więc ich pliki źródłowe do **katalogu ignorowanego przez gita w moim repo**
(odczyt u nich, zapis wyłącznie u siebie) i uruchomiłem na **własnym, już stojącym kontenerze**.
Niczego nie postawiłem na współdzielonym demonie, niczego nie zapisałem u nich.

**To jest pomiar na KOPII ich kodu, w MOIM środowisku** — i tak go liczę. Nie zastępuje
przebiegu ich suity przez HTTP.

---

# (A) Czy kontrole mają POKRYCIE — **POTWIERDZONE BOMBĄ**

Trzy przebiegi tej samej sondy: kod sprzed naprawy, kod po naprawie, kod po naprawie
**z wysadzoną ścieżką rozstrzygającą** (`isInvalidated` → `return false`).

| pomiar | PRZED naprawą | PO naprawie | **BOMBA** |
|---|---|---|---|
| K1 `isInvalidated` (znacznik ze starym `ts`) | **false** ✗ | **true** ✓ | **false** ✗ |
| K1 SKUTEK 2 — plik przeżył odczyt | **false** ✗ | **true** ✓ | true |
| K3 znacznik PUSTY blokuje | true | true | **false** ✗ |
| K3b znacznik USZKODZONY blokuje | true | true | **false** ✗ |
| K3c JSON bez pola `ts` blokuje | true | true | **false** ✗ |
| `evictExpired` istnieje | **false** ✗ | true | true |
| kontrola sondy: świeży znacznik blokuje | true | true | **false** ✗ |

**Bomba zabija K1, K3, K3b, K3c i kontrolę zdrowia sondy.** Zatem ścieżka, o którą pytasz,
**JEST pokryta** — przyszła zieleń tych kontroli **nie będzie pusta**. To jest odpowiedź na
przeformułowane pytanie.

**Kolumna „PRZED naprawą" odtwarza ich pomiar czerwieni co do znaku** (K1 false, plik
skasowany przez odczyt, brak `evictExpired`) — czyli sonda mierzy to samo, co ich kontrole,
mimo innej drogi. To jest kontrola samej sondy.

## Kierunek 0 — sprawdzony w TRZECH wariantach, jeden ponad ich opis

Ich opis mówi o znaczniku „z uszkodzoną albo pustą treścią". Dołożyłem trzeci:
**poprawny JSON bez pola `ts`**. Wszystkie trzy blokują — i **przed**, i **po** naprawie.

To potwierdza to, co konta same napisały: **K3 jest kontrolą jałowości, nie dowodem naprawy.**
Przewidziały to przed pomiarem i podały powód (`ts=0` omijało gałąź TTL). Przewidywanie się
sprawdziło. Nie mam do tego zastrzeżeń, a sposób postawienia sprawy uważam za wzorcowy.

## ⚠ Znalazłem gałąź ZDEGENEROWANĄ — we WŁASNEJ sondzie

`SONDA_brak_znacznika_nie_blokuje` zwraca **false we wszystkich trzech światach**: w kodzie
zdrowym, naprawionym i **wysadzonym**. Asercja „brak znacznika nie blokuje" jest więc
spełniona także przez implementację, która **nie działa w ogóle** — bo `return false` spełnia
ją idealnie.

**Zero zdolności rozróżniania.** Nie wolno jej liczyć jako dowodu, że mechanizm działa —
ma sens **wyłącznie w parze** z „świeży znacznik blokuje". Zapisuję to przeciw sobie, bo to
moja asercja, i dlatego, że jest to ten sam kształt, którego szukamy u innych: kontrola
negatywna bez kontroli pozytywnej obok jest ozdobą.

---

# (C) Czy rozstrzygnięcie `evictExpired` da się porzucić — **PRÓBA OBALENIA NIEUDANA**

Szukałem wywołania, w którym werdykt ginie. **Nie znalazłem, i mówię to wprost.**

Wywołanie jest **jedno** i leży dokładnie tam, gdzie ich opis obiecywał — na ścieżce
**mutującej**, w obsłudze back-channel logout, tuż po `mark()` (409) i `destroyBy()` (418):

```php
429   $entry['eksmisja'] = InvalidationStore::evictExpired();     // werdykt ODEBRANY
430   @file_put_contents($logFile, json_encode($entry, …), FILE_APPEND);   // i ZAPISANY
```

Werdykt jest przypisany i trafia do dziennika, a dziennik **jest czytany** przez kontrolę P-3
(`"wynik":"USUNIETY"` przez `/_test/state`). Łańcuch jest domknięty: działanie → rozstrzygnięcie
→ dziennik → kontrola czytająca dziennik.

**Zmierzone zachowanie `evictExpired` na ich prawdziwym kodzie:**

```
werdykt: {"zasieg":"CALOSC","zakonczenie":"ZAKONCZONE","usuniete":1}
stary (ts sprzed TTL)      → USUNIĘTY
świeży                     → został
NIECZYTELNY (nie-JSON)     → ZOSTAŁ      ← zgodnie z ich zasadą „nieczytelny nie jest kandydatem"
```

**Jedyne, co zostaje z mojego ataku, i podaję to jako obserwację, nie zarzut:** PHP nie ma
sposobu, by WYMUSIĆ odebranie wartości zwracanej — `InvalidationStore::evictExpired();`
jako samodzielna instrukcja przechodzi bez ostrzeżenia (sprawdziłem). Obroną jest dziś
dyscyplina wywołującego plus kontrola P-3 czytająca dziennik. **To wystarcza, dopóki
wywołanie jest jedno.** Drugie wywołanie, dołożone kiedyś bez dziennika, nie zapali niczego.

---

# (B) i (D) — bez zmian, oddane w `ODPOWIEDZ-015`

- **(B)** twierdzenie o `SessionStore` **POTWIERDZONE** po otwarciu kontekstu całej metody
  `read()`. **Nadal aktualne: `SessionStore.php` jest nietknięty** (sprawdzone ponownie).
  Druga strona defektu pozostaje **zdiagnozowana, nie zamknięta**.
- **(D)** **mam ten sam kształt u siebie**, w ścieżce dostępu: `RejestrSesji::uniewazniona()`
  rozstrzyga przez `where('wygasa_at', '>', now())`. Zmierzone, czerwone w suicie,
  nienaprawione — czeka na własną pozycję.

---

# WERDYKTY

| pytanie | werdykt |
|---|---|
| (A) czy kontrole mają pokrycie ścieżki | **POTWIERDZONE** — bomba zabija K1, K3, K3b, K3c |
| (A) czy ich zieleń będzie pusta po naprawie | **NIE BĘDZIE** — pokrycie udowodnione |
| (A) kierunek 0 | **POTWIERDZONE** w trzech wariantach; K3 jest jałowa **zgodnie z ich własnym opisem** |
| (C) czy werdykt `evictExpired` da się porzucić | **OBALONE — moja próba nieudana.** Werdykt odebrany, zapisany, dziennik czytany przez P-3 |
| (B) `SessionStore` | **POTWIERDZONE**; „zamknięta" nadal nieprawdziwe |
| gałąź zdegenerowana w mojej sondzie | **POTWIERDZONE, przeciw sobie** |

# Czego NIE sprawdziłem

- **Nie uruchomiłem ich suity** — mierzyłem klasę na kopii, nie ich kontrole przez HTTP.
  Nie twierdzę, że ich `run.sh` przejdzie; twierdzę, że **ścieżka, którą bada, jest pokryta**.
- **Nie sprawdziłem `evictExpired` przy kasowaniu NIEUDANYM** (K2b) — wymaga uruchomienia jako
  `nobody` na katalogu bez prawa zapisu, czyli ich harnessu.
- **Nie sprawdziłem, czy naprawa jest zacommitowana** — o `15:16` była **niezacommitowana** (`M`).
- Nie przeszukałem suit `security`, `mail`, `e2e`, `broker`, `zamkniecie` — przyrząd tam nie sięgnął.

# Własne nieudane próby obalenia

1. **„Werdykt `evictExpired` da się porzucić"** — nie znalazłem takiego wywołania; jest jedno
   i odbiera wynik. **Obalenie nieudane.**
2. **„`evictExpired` jest wołane tylko z endpointu testowego"** — podejrzenie po numerze linii
   (429, blisko `_test/state`). **Otworzyłem kontekst: fałsz.** Leży w obsłudze back-channel
   logout. To jest dokładnie ta pomyłka, przed którą sam ostrzegam: trafienie z numerem linii
   wzięte za znajomość miejsca.
3. **„Kontrole nie mają pokrycia, więc ich zieleń będzie pusta"** — bomba obaliła to wprost.

# Zakazy

Zero `main`, merge, deploy · **zero zapisu w cudzych repozytoriach** (kopia poszła do mojego
katalogu ignorowanego przez gita) · ich stosu nie stawiałem — użyłem własnego, już stojącego
kontenera · ścieżki bezwzględne, **ani jednego `cd`** do cudzego repo. **Sprzeczności: brak.**
