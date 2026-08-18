# ODPOWIEDZ-066 — cykl naprawczy rundy 8 zamknięty

**Od:** sesja KOD-F1 · **Do:** architekt · **Data:** 18.08.2026
**W odpowiedzi na:** `ZLECENIE-066` (naprawy R8-1, R8-2)
**SHA zamrożone:** `d79dc0c9cd1ba65bce944b53c404fb5dc6386e7d` (gałąź `faza-1-retencja`, „d79dc0c")

---

## 0. Jednym zdaniem

Oba znaleziska naprawione i zmierzone różnicowo na dokładnie tych mutacjach,
które je ujawniły; pytania „krok dalej" wyprodukowały **trzecią wadę**, której
runda nie wskazała — zgłaszam ją osobnym wpisem (§4).

---

## 1. Pomiary rozstrzygające

```
BRAMKA OK — 22 kroków, 0 nieudanych              (przebieg OD ZERA, kod wyjścia 0)
290 testów, 2130 asercji, 2 pominięte
podłogi 290 / 2130                               (RÓWNO — bez zapasu)
PERTURBACJE OK — 49 kontroli (32 scenariusze, pominiętych: 0)
znacznik `.przebieg-pomiarowy` po przebiegu bramki:  ZDJĘTY
```

---

## 2. R8-1 — siatka mierzy SKUTEK niezależnie od nazwy pola

### Co zmieniono

Ładunek pochodzi teraz z **pól, które kod naprawdę czyta**. `nazwyPolWejsciowych()`
tokenizuje `backend/app` i `backend/routes` i zbiera:

- literały przekazywane do metod czytających żądanie (`input`, `query`, `post`,
  `string`, `has`, `filled`, `header`, `cookie`, `old`, `request`, …);
- odczyty właściwości `$request->nazwa`.

Do tego dwa składniki jawnie nazwane w kodzie jako **wzmocnienie**, nie nośnik
czułości: dotychczasowa bateria oraz **jedno pole, którego nikt nie czyta** —
bo mechanizm skanujący `$request->all()` w poszukiwaniu wartości pasującej do
sekretu nie potrzebuje żadnej konkretnej nazwy, a dowolne pole niosące sekret
go wyzwoli.

Sekret idzie pod KAŻDĄ nazwą, w trzech wariantach wartości (nazwa wyświetlana,
e-mail, `sub`), bo mechanizm może porównywać z dowolną kolumną założonego konta.

### Dlaczego odczyt źródeł nie jest nawrotem do pytania o SPOSÓB

Czytam kod tylko po to, żeby zbudować **ŁADUNEK**. Werdykt nadal wydaje SKUTEK:
zapis tożsamości do sesji. To różnica między „sprawdzam, czy w kodzie jest
krypto" (siatka deklaratywna, obalona w rundzie 7) a „pytam kod, gdzie ma usta,
i wkładam tam sekret". Mechanizm musi skądś wziąć sekret — jeśli czyta go pod
nazwą `zaklecie`, to `zaklecie` STOI W ŹRÓDLE, inaczej nie miałby jak.

### Perturbacja z nazwą spoza baterii

Mutacja `d1b` przyjmuje nazwę pola jako PARAMETR i chodzi w dwóch wariantach:
`d1b` (nazwa z baterii) i **`d1b_zaklecie`** (nazwa dokładnie z raportu). Mechanizm
jest identyczny co do bajtu poza jedną nazwą — jeśli siatka zapala się na jednym,
a na drugim nie, to nie mierzy skutku, tylko zgaduje nazwę wejścia.

**Pomiar różnicowy — ta sama perturbacja, dwie wersje siatki:**

```
siatka SPRZED naprawy + zaklecie → ✗ „kontrola PRZESZŁA mimo złamanej reguły (nic nie sprawdza)"
siatka PO naprawie    + zaklecie → ✓ czerwień z właściwej przyczyny
siatka PO naprawie    + d1b      → ✓ czerwień z właściwej przyczyny
```

Sprostowane zostało też twierdzenie, które lukę „znosiło": `ODPOWIEDZ-062` §8
powoływała się na perturbację `d1b` jako na sieć bezpieczeństwa dla nazw spoza
baterii, a `d1b` czytała `nazwa_wyswietlana` — nazwę Z BATERII. **Twierdzenie było
nieprawdziwe wobec własnej implementacji**; dziś ma pokrycie w kodzie.

### Krok dalej — jakie drogi omijają punkt, w którym słucha szpieg

Odpowiedź jest zmierzona, nie wyrozumowana, i wpisana do nagłówka siatki:

1. **Tożsamość ustanowiona przez ciasteczko odczytane na INNEJ trasie.** Szpieg
   patrzy na sesję po pojedynczym żądaniu i nie przenosi ciasteczek między nimi.
   **Łapie to jednak inna kontrola**: hydratacja tożsamości musi dotknąć klucza
   albo fasady, więc widzi ją `WaskieGardloTozsamosciTest` — i właśnie przy tym
   pytaniu wyszło, że jej zasięg był za wąski (§4).
2. **Zapis wprost do magazynu sesji.** Sterownik sesji w testach to `array`
   (`phpunit.xml:56`), więc taki zapis trafia w TEN SAM obiekt, który czyta
   szpieg. Mechanizm piszący do INNEGO magazynu (np. wprost do tabeli sesji)
   nie zalogowałby nikogo w tym środowisku — to nie przeoczenie siatki, tylko
   rzecz niemierzalna tą drogą. Nazywam ją, nie zamiatam.
3. **Nagłówki HTTP** nie są sondowane; ładunek idzie parametrami.

---

## 3. R8-2 — egzekutor §10 pyta REALNEJ aplikacji

### Co zmieniono

`BlokadaWysylkiTest` nie buduje już providera pod ręką. Uruchamia **pełny
bootstrap jądra w OSOBNYM PROCESIE** ze sterownikiem WYSYŁAJĄCYM w środowisku
i czyta dwie rzeczy: czy framework załadował `AppServiceProvider` oraz co
realnie stoi w `mail.default` po starcie.

Osobny proces jest konieczny, nie elegancki: `phpunit.xml` wymusza
`MAIL_MAILER=array` (wiersz 54), a `array` nie wysyła — więc gałąź podmieniająca
w mechanizmie nigdy nie wchodzi i **w aplikacji testowej nie ma czego mierzyć**.
Podniesienie `mail.default` przez `config([...])` po starcie i ręczne zawołanie
`boot()` to dokładnie ta konstrukcja pod ręką, którą R8-2 nazywa wadą.

To ten sam ruch, który `ZasiegUniewaznieniaTest` wykonuje dla middleware przez
`gatherRouteMiddleware()` — czytamy stan, który złożył framework.

### Pomiar różnicowy (dokładnie ten z raportu)

```
bootstrap/providers.php → return []:
   realny start:            provider=NIE  mail=smtp     (BLOKADA §10 MARTWA)
   egzekutor PRZED naprawą: 2 passed
   egzekutor PO naprawie:   1 failed — „Framework NIE ŁADUJE AppServiceProvider"

stan przywrócony:
   realny start:            provider=TAK  mail=log
   egzekutor:               3 passed
```

Kontrola przyrządu jest osobna i obowiązkowa: sonda z **wyłączoną** blokadą musi
dać `mail=smtp`. Bez tej różnicy „zielone" mogłoby znaczyć „sonda wypisuje stałą".
Dodatkowo pusty wynik sondy albo niezerowy kod wyjścia zatrzymuje test wprost —
`exec` wyłączony nie ma prawa wyglądać jak spełniona reguła.

### Dlaczego test R7-3 ZOSTAJE

Nowy egzekutor zapala się na obu sposobach zabicia mechanizmu naraz —
wyrejestrowaniu providera ORAZ `return;` w metodzie — więc sam nie rozróżnia
PRZYCZYNY. Test R7-3 rozróżnia: gdy oba czerwone, wina jest w metodzie; gdy
czerwony tylko nowy — w rejestracji. Dwie wartości zamiast jednej to różnica
między „coś jest zepsute" a „wiem co".

### Krok dalej — które jeszcze kontrole budują badany obiekt ręcznie

Przejrzałem suitę pod tym jednym kątem. Konstrukcje ręczne dzielą się na trzy grupy:

| grupa | przykłady | ocena |
|---|---|---|
| przyrządy, nie przedmiot pomiaru | `ReflectionClass`, iteratory katalogów, `FabrykaTokenow` | bez wady |
| obiekt domenowy, ale WPIĘCIE pilnowane osobno | `new ZadanieRetencji(…)` w `KluczRetencjiTest` i `RetencjaWykonanieTest` | **bez wady** — `HarmonogramRetencjiTest` pyta `app(Schedule::class)` i ma kierunek 0 na pustym harmonogramie |
| `new AppServiceProvider(…)` | `BlokadaWysylkiTest` | świadomie zostawione jako rozróżnianie piętra (wyżej) |

**Odpowiedź: żadna inna kontrola nie ma wady R8-2.** Jedyny obiekt domenowy
budowany ręcznie ma osobny egzekutor wpięcia, i to zbudowany właściwym wzorcem.

---

## 4. Wada WŁASNA znaleziona przez pytanie „krok dalej" (nie z raportu rundy 8)

**Allowlista tożsamości skanowała wyłącznie `app/`.**

Przy odpowiadaniu na pytanie 2.3 (drogi omijające szpiega) sprawdziłem, czy
`WaskieGardloTozsamosciTest` naprawdę wyłapie hydratację tożsamości poza siatką.
Zmierzone wprost, z mutacją `d1b` w drzewie:

```
session()->put('konta', …) żywe w backend/routes/web.php
WaskieGardloTozsamosciTest → 5 passed          ← NIE WIDZI
```

`routes/` to **dokładnie to miejsce, w którym mieszkał atak rundy 7**. Docblock
tej kontroli obiecywał, że „każdy nowy plik sięgający po tożsamość zapala bramkę",
a jeden z dwóch katalogów wykonywalnych stał poza skanem — ta sama klasa co samo
R7-1: kontrola o zasięgu węższym, niż deklaruje.

Zasięg rozszerzony o `routes/`. Allowlista nie wymagała nowego wpisu, bo nazwy
tras (`'konta.callback'`) są INNYMI literałami niż `'konta'`.

```
PO rozszerzeniu, ta sama mutacja → 1 failed
ZNALEZIENI: …, routes/web.php
stan czysty                      → 5 passed
```

---

## 5. Stan długów

| dług | stan |
|---|---|
| **D-2** — allowlisty `--przyczyna` | SPŁACONY (sufit zapadki 0), bez zmian |
| **D-3** — `TwierdzeniaKomentarzyTest` poza bramką (14 obejść na 15) | BEZ ZMIAN |
| **D-4** — wyjątek gitleaks | zawężony i zmierzony; usunięcie przy scalaniu (O-1) |

Nowych długów nie zaciągam. Granice siatki D-1b (§2, krok dalej) nie są długiem,
tylko nazwanymi granicami przyrządu — wpisane do nagłówka kontroli, nie do rejestru.

---

## 6. Czego NIE ruszałem

- **`docs/DECYZJE.md`** — nadal leży w nim niezacommitowany wpis innej sesji
  (`D-2026-08-12-04`), a plik jest poza zakresem tej sesji.
- **Niczego poza R8-1, R8-2 i wadą z §4** — zgodnie z `ZLECENIE-066` §3.
- **Konwencji daty w pliku stanu** (kotwica w commicie zamiast daty dziennej) —
  przyjęty kierunek na okno scaleniowe, nie łatka pod zamrożeniem.

---

## 7. Gotowość do rundy 9

Zbieżność: 29 → 9 → 2 → **teraz**. Zgłaszam gotowość na SHA `d79dc0c`.
Gdzie sam bym uderzył, żeby weryfikator nie szukał po omacku:

- **`nazwyPolWejsciowych()` to nowy parser** — jeśli rozjedzie się ze źródłami,
  ładunek cicho wraca do samej baterii, czyli do stanu obalonego w rundzie 8.
  Bronią tego dwie asercje (liczba nazw > 2 oraz obecność `code` i `state`),
  ale to jest dokładnie miejsce, w którym warto szukać trzeciego kroku tej klasy.
- **Sonda R8-2 zależy od `exec()` i od obecności `php` w kontenerze.** Limit jest
  zmierzony (pusty wynik albo niezerowy kod zatrzymuje test), nie założony —
  ale to trzecia kontrola w suicie opierająca się na uruchamianiu podprocesu.
- **Granice siatki wypisane w §2** — każda z nich to zaproszenie: jeśli któraś
  da się wykorzystać w tym środowisku, jest znaleziskiem, a nie znaną granicą.
- **Zasięg kontroli, które skanują katalogi** — właśnie tam wyszła wada z §4.
  Warto sprawdzić pozostałe skanery pod kątem „czy widzą oba katalogi
  wykonywalne", nie tylko ten jeden.
