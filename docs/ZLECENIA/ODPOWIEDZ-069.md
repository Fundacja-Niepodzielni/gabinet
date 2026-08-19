# ODPOWIEDZ-069 — cykl naprawczy rundy 9 zamknięty

**Od:** sesja KOD-F1 · **Do:** architekt · **Data:** 18.08.2026
**W odpowiedzi na:** `ZLECENIE-069` (przeprojektowanie klasy, nie łatanie instancji)
**SHA zamrożone (KOD):** `528adc365040808b9abc653cfddc2c8b3d08f94c` (gałąź `faza-1-retencja`, „528adc3")

**Warunek zamrożenia w formie poprawionej 18.08:**

```
git diff --stat 528adc3..HEAD -- . ':(exclude)docs/'   →  PUSTO
```

Ponad `528adc3` stoi jeden commit dokumentacyjny (meldunek + kanał + kotwica
w `PLAN-FAZ.md`) — zgłaszam go w §7.

---

## 1. Pomiary rozstrzygające

```
BRAMKA OK — 22 kroków, 0 nieudanych              (przebieg OD ZERA, kod wyjścia 0)
301 testów, 2170 asercji, 2 pominięte
podłogi 301 / 2170                               (RÓWNO — bez zapasu)
PERTURBACJE OK — 52 kontroli (35 scenariuszy, pominiętych: 0), kod 0
```

Podłogi podniesione `290/2130 → 301/2170`, powód wpisany do `podlogi.sh`.

---

## 2. R9-1 + R9-2 + R9-4 — jedna klasa, jedno rozwiązanie

Przyjmuję diagnozę bez zastrzeżeń: to nie były trzy wady, tylko jedna —
**kontrola tożsamości była LISTĄ, a nie pomiarem niezmiennika.** Trzy rundy
zamknęły po jednej instancji; czwarty cykl łatania dałby czwarty krok.

### Co powstało: wąskie gardło zapisu tożsamości

`WaskieGardloZapisuTozsamosciTest` pyta o coś, **czego atakujący nie wybiera**:
gdzie stoi zapis tożsamości. Sposób dostarczenia sekretu — jedno pole, dwa pola,
nagłówek, `all()`, cokolwiek — przestaje mieć znaczenie, bo każdy z nich musi
skończyć się tym samym zapisem.

| warstwa | niezmiennik | zamyka ucieczkę |
|---|---|---|
| **1** | surowy zapis klucza `konta` istnieje wyłącznie w dwóch metodach fasady | zapis w `routes/`, w nowym pliku, w innej metodzie tego samego pliku |
| **2** | `SesjaKonta::zaloz()` wołane wyłącznie z metody callbacku OIDC | mechanizm, który zamiast pisać sesję sam, woła fasadę |
| **3** | callback czyta z żądania wyłącznie `code` i `state` | mechanizm w JEDYNYM legalnym miejscu — tam warstwy 1–2 są bezradne z definicji |

**Atrybucja jest do METODY, nie do pliku** — to była cała treść R9-1.
`LogowanieController` ma zgodę na dotykanie tożsamości, więc lista PLIKÓW była
wobec niego bezradna: mechanizm w nim dawał `WaskieGardlo + BrakWlasnychHasel`
= 14 passed. Nowa kontrola ma osobny test na samą atrybucję, bo bez niej
allowlista po cichu wraca do poziomu pliku.

### Trzy wektory rundy 9 jako kontrole negatywne w suicie

Perturbacje `gardlo_para`, `gardlo_naglowek`, `gardlo_all` — mechanizm
**identyczny co do bajtu poza sposobem odczytu**, wstawiony tam, gdzie postawił
go weryfikator (`LogowanieController::zaloguj`):

```
gardlo_para      (email + hasło, dwa pola o różnych wartościach)  → CZERWIEŃ z badanej przyczyny
gardlo_naglowek  (sekret w nagłówku X-Zaklecie)                   → CZERWIEŃ z badanej przyczyny
gardlo_all       ($request->all()['zaklecie'])                    → CZERWIEŃ z badanej przyczyny
```

Do tego kontrola negatywna na plikach budowanych pod rękę: trzy wektory + formy
składniowe zapisu (`self::KLUCZ`, `session([…])`, `merge([…])`) muszą być
widziane, a odczyt tożsamości, zapis pod innym kluczem, komentarz i literał
napisowy — **nie**, bo każde z nich byłoby fałszywym oskarżeniem.

### Siatka D-1b — zostaje jako WZMOCNIENIE, z granicami mówiącymi prawdę

- parser stracił **listę metod czytających** (nie znał `all`, `only`, `validate`,
  dostępu tablicowego — 4 nazwy z całej aplikacji). Bierze teraz każdy literał
  o kształcie nazwy pola: żadnej listy do utrzymania;
- próg porównuje się z **drugim, niezależnym odczytem**, nie ze stałą — dotąd
  „4" i „40" były dla asercji broniącej tym samym;
- doszedł ładunek **rozdzielający wartości** (kanoniczny `email` + `hasło`)
  i sondowanie nagłówkami.

**Nagłówków NIE zaliczam jako zdjętej granicy.** Zmierzone: nagłówek dochodzi
do mechanizmu z właściwą wartością (zrzut z sondy), a mimo to siatka nie wykrywa
zapisu. Przyczyny nie ustaliłem i nie twierdzę, że działa — sondowanie zostaje,
bo nic nie kosztuje, ale liczyć na nie nie wolno. Ten wektor zamyka warstwa 1,
co jest zmierzone perturbacją.

### R9-4 — jedna lista katalogów zamiast rozszerzania każdego skanera

Naprawa po rundzie 8 objęła jedną asercję nie przez niedbalstwo, tylko dlatego,
że **każdy skaner trzymał własną listę katalogów**. Powstało `Tests\Wsparcie\Kod`
z jedną listą (`app`, `routes`, `bootstrap`, `config` — rekurencyjnie, bo
nierekurencyjny skan `routes/` był na Twojej liście rzeczy niesprawdzonych).

Dopisanie katalogu zmienia odtąd zasięg **wszystkich** kontroli naraz.

### Krok dalej — czym jeszcze da się ustanowić tożsamość

1. **Klucz sesji zbudowany w czasie działania** (`$k = 'ko'.'nta'`). Analiza
   statyczna tego nie zobaczy i mówię to wprost w nagłówku kontroli, zamiast
   udawać, że jest wykonaniem. Pokrywa to sonda D-1b: mechanizm musi dać się
   WYWOŁAĆ, żeby cokolwiek zalogować, a wtedy zapis widać w sesji. Dwa przyrządy
   o **różnych** ślepych plamach, nie jeden „na wszystko".
2. **Zapis wprost do magazynu sesji z pominięciem fasady** — warstwa 1 łapie go,
   bo szuka wywołania zapisującego z kluczem tożsamości niezależnie od odbiorcy
   (`session()`, `$request->session()`, `Session::`). Kontrola negatywna
   obejmuje wszystkie trzy formy.
3. ~~**Mechanizm wewnątrz `powrot()`** — zamyka warstwa 3 (kontrakt OIDC to `code`
   i `state`; cokolwiek innego czytane z żądania jest tam obce).~~

   > ⛔ **SPROSTOWANIE 19.08 (R10-1) — to twierdzenie było NIEPRAWDZIWE.**
   >
   > Warstwa 3 zamykała ten wektor **wyłącznie dla odczytu metodą z 15-elementowej
   > listy**. Dostęp tablicowy (`$request['zaklecie']`) i metody spoza listy
   > (`str`, `boolean`, `enum`, właściwość dynamiczna) były dla niej niewidzialne.
   > Runda 10 zmierzyła: mechanizm własnego hasła w `powrot()` przechodził CAŁĄ
   > bramkę (301 passed, Larastan i Pint zielone) i realnie logował.
   >
   > Zostawiam oryginał przekreślony, nie podmieniam go po cichu: zdanie „krok
   > dalej: sprawdziłem X" podlega obaleniu tak samo jak każde inne, a ślad po
   > obaleniu jest wart więcej niż gładki tekst.
   >
   > **Brzmienie po naprawie (`ODPOWIEDZ-074`):** warstwa 3 pyta o ODCZYTANE POLE,
   > nie o składnię odczytu — dostęp tablicowy, dowolna metoda, właściwość
   > dynamiczna, pomocnik `request()`, superglobale i `php://input` są objęte;
   > lista dozwolonych została wyłącznie po stronie WEJŚCIA (`code`, `state`),
   > gdzie uzasadnia ją kontrakt OIDC.
4. **Ciasteczko ustawione na trasie A, hydratacja na trasie B** — hydratacja
   musi dotknąć klucza albo fasady, więc widzą ją warstwy 1–2.

---

## 3. R9-3 — sekret w `.env.example`

**Ciężar dowodu odwrócony**, tak jak przy schemacie bazy i prymitywach krypto:
`SekretyTest` nie pyta już „czy te 14 nazw jest pustych", tylko **czy KAŻDE
przypisanie jest puste albo jawnie stoi na liście wartości nietajnych** (46
pozycji, każda z wartością). Druga linia: wartość o **kształcie sekretu**
(≥ 24 znaki, ≥ 3 klasy znaków, nie URL, bez spacji) zapala kontrolę nawet wtedy,
gdy ktoś dopisał ją do listy bezmyślnie.

```
.env.example + GOOGLE_CALENDAR_CLIENT_SECRET=GOCSPX-…  → CZERWIEŃ
   „wiersz 141: GOOGLE_CALENDAR_CLIENT_SECRET ma WARTOŚĆ, a nie stoi na liście nietajnych"
plik czysty                                            → 4 passed
```

**Sprzeczność w `.gitleaks.toml` usunięta.** Zdanie „nie potrzebuje wyjątku
— i celowo go nie dostaje" było nieprawdziwe wobec tego samego pliku. Zastąpione
sprostowaniem, które mówi stan faktyczny: wyjątek ISTNIEJE i jest potrzebny
(heurystyka zapala się na pustych przypisaniach przez `\s` obejmujące nową
linię), cztery reguły własne są przypięte do nazw, a ochroną **niezależną od
nazwy** jest odtąd `SekretyTest`.

---

## 4. R9-5 — plik stanu

`CURRENT WORK` przepisany ze stanu zmierzonego. Do tego **domknięcie klasy**,
odłożone kiedyś „na okno scaleniowe" — wchodzi teraz, bo runda pokazała jego brak:

**Konwencja kotwic.** Liczby pomiaru zapisujemy jako „**zmierzone na `<SHA>`**",
nigdy z datą. Powód jest sprawdzalnościowy: daty nie da się zweryfikować
z wnętrza repozytorium bez wpuszczenia zegara do kontroli, a kontrola zależna
od zegara zaczyna padać sama z siebie. SHA weryfikuje się jednym
`git cat-file -e`.

Dwa nowe egzekutory w `JednoZrodloStanuTest`:

- sekcja stanu musi mieć kotwicę, a **każda kotwica musi wskazywać commit, który
  ISTNIEJE** (kotwica do SHA zmyślonego byłaby gorsza od daty — wygląda na sprawdzalną);
- **liczba scenariuszy** perturbacji musi zgadzać się z listą w `perturbacje.sh`.
  Liczby KONTROLI nie da się sprawdzić bez uruchomienia, więc wymaga kotwicy —
  sprawdzamy to, co sprawdzalne, i kotwiczymy resztę.

Ta sekcja kłamała o własnym pomiarze **trzy razy** (R7-6, moje „zmierzone 12.08"
o pomiarze z 18.08, R9-5). Za każdym razem wychodziło to dopiero w rundzie.

---

## 5. Wada własna, znaleziona własnym przebiegiem

**`toHaveKey($klucz, $komunikat)` — ta sama pułapka co `toContain`, przeniesiona
na inną rodzinę matcherów.** Napisałem komunikat jako drugi argument, dostałem
czerwień o porównaniu dwóch napisów: Pest porównywał wartość klucza z treścią
mojego zdania.

Mój własny skaner połkniętych komunikatów (z rundy 7) tego **nie widział**, bo
znał wyłącznie rodzinę `toContain*`. Wspólna cecha nie siedzi w nazwie, tylko
w **sygnaturze**: drugi argument niesie wartość, więc zdanie ginie bez śladu.
Skaner poszerzony o `toHaveKey`, `toHaveProperty`, `toHaveKeys`.

Osobno: rozszerzenie zasięgu (R9-4) odsłoniło **siedem powołań** na znaleziska
w `bootstrap/` i `config/` bez egzekutorów. Cztery egzekutory napisałem (jeden
`.env` w korzeniu — pytany aplikacji przez `environmentPath()`; polityka pamięci
Redisa pytana ŻYWEJ instancji, bo to jej własność, nie pliku; rozdział baz
cache/sesje), trzy nazwałem w kontrolach, które już je egzekwowały.

---

## 6. Stan długów

| dług | stan |
|---|---|
| **D-2** — allowlisty `--przyczyna` | SPŁACONY (sufit 0), bez zmian |
| **D-3** — `TwierdzeniaKomentarzyTest` poza bramką | BEZ ZMIAN |
| **D-4** — wyjątek gitleaks | zawężony i zmierzony; opis **sprostowany** (R9-3); usunięcie przy scalaniu (O-1) |

Nowy dług: **żaden**. Granice wąskiego gardła i siatki są nazwanymi granicami
przyrządu, wpisanymi w nagłówki kontroli — nie wpisami w rejestrze.

---

## 7. Commit dokumentacyjny ponad zamrożeniem — zgłaszam

Zgodnie z poprawionym warunkiem: ponad `528adc3` stoi **jeden** commit dotykający
wyłącznie `docs/` (ten meldunek, kanał, kotwica w `PLAN-FAZ.md`).

```
git diff --stat 528adc3..HEAD -- . ':(exclude)docs/'   →  PUSTO
```

Zgłaszam go przed zapisaniem zlecenia rundy 10, żeby nie powtórzył się wyścig
z `ZLECENIE-065` i `ZLECENIE-068`.

---

## 8. Gotowość do rundy 10

Zbieżność: 29 → 9 → 2 → 5 → **teraz**. Gdzie sam bym uderzył:

- **Warstwa 3 jest listą dwóch nazw** (`code`, `state`) — najwęższą, jaką umiałem
  uzasadnić kontraktem OIDC, ale nadal listą. Jeśli kontrakt callbacku kiedyś
  urośnie, urośnie i ona. To jest miejsce, w którym klasa „lista zamiast pomiaru"
  ma jeszcze oddech.
- **Sondowanie nagłówkami nie działa i nie wiem dlaczego** — opisane wprost
  w nagłówku siatki. Jeśli ktoś ustali przyczynę, może się okazać, że sonda ma
  szerszą ślepą plamę, niż zdążyłem zmierzyć.
- **`Kod::funkcje()` to nowy parser** — atrybucja do metody stoi na liczeniu
  klamer. Rozjazd z kodem cofnąłby allowlistę do poziomu pliku, czyli do stanu
  sprzed naprawy. Broni tego osobny test atrybucji, ale to naturalne miejsce
  kolejnego kroku.
- **Lista wartości nietajnych w `SekretyTest` ma 46 pozycji** — im dłuższa,
  tym większa pokusa dopisywania bez pytania. Kształt wartości jest tam drugą
  linią właśnie z tego powodu, ale pierwszą pozostaje czyjaś uwaga.
