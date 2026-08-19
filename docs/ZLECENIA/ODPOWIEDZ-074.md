# ODPOWIEDZ-074 — cykl naprawczy rundy 10 zamknięty

**Od:** sesja KOD-F1 · **Do:** architekt · **Data:** 19.08.2026
**W odpowiedzi na:** `ZLECENIE-074` (R10-1 — niezmiennik zamiast listy metod)
**SHA zamrożone (KOD):** `bbc8167d83a281225a5b5a742aeb8b13f5760210` („bbc8167")

---

## 1. Surowe wyjście — bramka OD ZERA, PO commicie zamrażającym

Uruchomiona, nie zadeklarowana; po commicie, bo to jest reguła z `ODPOWIEDZ-073` §4.

```
    WYKONANO 304 testów (podłoga: 304)
    sprawdzono 2211 asercji (podłoga: 2211)
6:23AM INF 157 commits scanned.
6:23AM INF no leaks found
BRAMKA OK — 22 kroków, 0 nieudanych
KOD=0
```

Perturbacje, pełny zestaw:

```
PERTURBACJE OK — 54 kontroli udowodniło, że umie zaświecić czerwono (pominięte: 0)
37 scenariuszy · KOD=0
```

Podłogi **304 / 2211 — RÓWNO**. Pierwszy przebieg pokazał 304 przy podłodze 303;
poprawiłem podłogę i przemierzyłem, zamiast zostawić zapas. Zapas w zapadce jest
atrapą zapadki.

---

## 2. R10-1 — warstwa 3 pyta o ODCZYTANE POLE, nie o składnię odczytu

### Dlaczego nie dłuższa lista

To był trzeci raz ta sama klasa, za każdym razem o piętro wyżej:

```
R8-1  nazwa pola          → dopisaliśmy nazwy
R9-1  sposób dostarczenia → dopisaliśmy kształty ładunku
R10-1 SKŁADNIA ODCZYTU    → …i tu przestajemy dopisywać
```

Lista metod ma brzeg, a atakujący wybiera, po której stronie brzegu stanie.
Dlatego **usunąłem listę z miejsca, w którym stała**, zamiast ją wydłużyć.

### Co jest objęte

Pytanie brzmi odtąd: **jakie POLE zostało odczytane.** Wykrywane niezależnie od
składni: dostęp tablicowy, **dowolna** metoda (także nieistniejąca), właściwość
dynamiczna (`__get`), pomocnik `request()`, `Request::`, metoda bez argumentu
(`->all()` — czyta całe wejście), nazwa pola **ze zmiennej** (nie da się dowieść
przynależności do kontraktu, więc zgłaszamy), superglobale i `php://input`.

**Lista dozwolonych została wyłącznie po stronie WEJŚCIA** (`code`, `state`) —
tam uzasadnia ją kontrakt OIDC i ma dokładnie dwa elementy.

### Kontrole, wszystkie zmierzone

**Negatywne — oba wektory rundy 10 osobno, jako stałe perturbacje:**

```
p_callback_tablica  ($request['zaklecie'])        → CZERWIEŃ z badanej przyczyny
p_callback_metoda   ($request->str('zaklecie'))   → CZERWIEŃ z badanej przyczyny
```

**Dowód różnicowy** — ta sama perturbacja, dwie wersje kontroli:

```
warstwa 3 SPRZED naprawy + oba wektory → ✗ „kontrola PRZESZŁA mimo złamanej reguły"
warstwa 3 PO naprawie    + oba wektory → ✓ czerwień z właściwej przyczyny
```

**Pozytywna:** legalny callback (czyta `code`, `state`) przechodzi — inaczej wąskie
gardło stałoby się nieużywalne i ktoś by je rozluźnił.

**Przyrządu:** `code` **dostępem tablicowym NIE zapala.** Bez tej kontroli naprawa
zamieniłaby jedną listę na drugą — tym razem listę dozwolonych składni.

Do tego 18 przypadków na plikach budowanych pod rękę: osiem, które **muszą**
zapalić, i pięć, których zapalenie byłoby fałszywym oskarżeniem (kontrakt metodą,
kontrakt tablicą, `filled('code')`, praca na sesji, przekazanie obiektu dalej).

---

## 3. Krok dalej — CZWARTE PIĘTRO, odpowiedź warstwą zamiast zdaniem

Pytałeś, czy istnieje. **Istnieje i jest cichszy od poprzednich**, bo używa
wyłącznie rzeczy dozwolonych:

```php
SesjaKonta::zaloz($request, ['sub' => $request->query('code')]);
```

Pole `code` **jest** w kontrakcie, więc warstwa 3 milczy słusznie. Zapis stoi
w fasadzie i jest wołany z callbacku, więc warstwy 1 i 2 milczą słusznie.
A tożsamość ustanawia napis podany przez żądającego — z pominięciem wymiany kodu
i weryfikacji podpisu.

**WARSTWA 4:** dane tożsamości nie mogą pochodzić z żądania ani z superglobali.
Dziś pochodzą z `$claimsId` — ładunku tokenu sprawdzonego przez `WalidatorTokenu`
(podpis, issuer, audiencja, czas). Kontrola negatywna: identyczne wywołanie
z `$request->query('code')` i z `$_GET` zapala; wersja z claimów przechodzi.

**Czego warstwa 4 nie widzi (nazwane, nie zamiecione):** wartość przeprowadzona
przez zmienną pośrednią (`$x = $request['a']; … zaloz($request, ['sub' => $x])`).
To wymagałoby analizy przepływu danych, nie odczytu struktury. Zasięg pokrywają
wtedy warstwy 1–3, bo taki odczyt **musi** gdzieś nastąpić i będzie widoczny jako
pole spoza kontraktu.

---

## 4. Sprostowanie `ODPOWIEDZ-069` §2 — przy oryginale, nie po cichu

Zdanie „Mechanizm wewnątrz `powrot()` — zamyka warstwa 3" zostało **przekreślone**
w miejscu, w którym padło, z dopisanym sprostowaniem: zamykała wyłącznie dla
odczytu metodą z listy. Nie podmieniam po cichu — ślad po obaleniu jest wart
więcej niż gładki tekst, a zdanie „krok dalej: sprawdziłem X" podlega obaleniu
tak samo jak każde inne.

---

## 5. Dwie wady WŁASNE, obie tej samej rodziny co naprawiane znalezisko

**(1) Literał, który nigdy nie trafiał.** W liście superglobali tylko `$_POST` był
zapisany poprawnie; `'\$_GET'` i pozostałe niosą w apostrofach PHP **dosłowny
ukośnik z dolarem**, więc porównanie nie mogło trafić nigdy. Kontrola świeciła
zielono, bo mierzyła jednego reprezentanta.

Lekcja jest ostrzejsza niż sama poprawka: **kontrola sprawdzająca jednego
przedstawiciela listy sprawdza jednego przedstawiciela, a nie listę.** Kontrola
przechodzi teraz przez **każdy** element osobno.

**(2) Proza unieważniająca typy.** Opis w bloku `/**` sprawiał, że PHPStan
przestawał widzieć znaczniki `@param` — 40 błędów zamiast 14, zmierzone bisekcją.
Statyka cicho przestawała sprawdzać tę funkcję. Opis wrócił jako komentarz
zwykły, z zapisaną przyczyną obok.

---

## 6. Sprawa porządkowa — kontrola liczby scenariuszy a kotwice

Kontrola z R9-5 porównywała **każdą** liczbę scenariuszy w sekcji stanu
z bieżącym skryptem — także tę w zdaniu zakotwiczonym („35 scenariuszy —
zmierzone na `528adc3`"). Wymuszałaby więc przepisywanie historii pomiarów przy
każdej nowej perturbacji, czyli kasowanie śladu.

Poprawione: zdanie z kotwicą opisuje ZDARZENIE i nie starzeje się — ta sama
zasada, którą stosuje kontrola liczb bramki poza sekcją stanu.

---

## 7. Warunek zamrożenia — uruchomiony

```
git diff --stat bbc8167..HEAD -- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md'
```

Wynik wklejam poniżej po commicie dokumentacyjnym (ten meldunek + kanał + kotwica
w `PLAN-FAZ.md`), razem z bramką przemierzoną PO nim — bo commit dokumentacyjny
też potrafi zaczerwienić krok [21], czego nauczył mnie poprzedni cykl.

`.gitleaks.toml` **nie był ruszany** w tym cyklu, więc pozostaje w zakresie
zamrożenia zgodnie z `ODPOWIEDZ-072` §3.

---

## 8. Stan długów

| dług | stan | termin |
|---|---|---|
| **D-3** | `TwierdzeniaKomentarzyTest` poza bramką | — |
| **D-4** | wyjątek gitleaks na przynętę w `perturbuj.py` | O-2/O-3 |
| **D-5** | wyjątek gitleaks na cytat w raporcie rundy 9 | O-2b — ten sam |

Nowych długów nie zaciągam.

---

## 9. Gotowość do rundy 11

Zbieżność: 29 → 9 → 2 → 5 → 1 → **teraz**. Gdzie sam bym uderzył:

- **Warstwa 4 nie śledzi przepływu danych** (§3) — wartość przeprowadzona przez
  zmienną pośrednią jest poza jej zasięgiem. Twierdzę, że łapią ją warstwy 1–3,
  bo odczyt musi gdzieś nastąpić; **to twierdzenie podlega obaleniu** i jest
  najbardziej naturalnym miejscem piątego piętra.
- **`Kod::funkcje()` liczy klamry** — rozjazd z gramatyką cofnąłby atrybucję do
  poziomu pliku. Broni tego osobny test, ale parser jest nowy.
- **Kontrola liczby scenariuszy respektuje kotwicę** (§6) — sprawdź, czy kotwica
  nie zwalnia za dużo: zdanie z SHA obok dowolnej liczby przechodzi bez pytania.
- **Lista wartości nietajnych w `SekretyTest`** rośnie z każdą zmienną
  konfiguracyjną; kształt wartości jest drugą linią, ale pierwszą pozostaje uwaga.
