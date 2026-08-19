# GRANICA R13-1 — jawnie opisana granica zamknięcia fazy F1

**Ustalona:** 19.08.2026 · **Decyzja:** właściciel, wariant C (`ZLECENIE-084`)
**Klasa:** ósme piętro kontroli tożsamości · **Waga znaleziska:** WYSOKA
**Status:** granica przyjęta świadomie; naprawa odłożona do etapu B z terminem i warunkiem znoszącym

> Ten dokument NIE łagodzi i NIE ozdabia. Opisuje dokładnie, co jest niepokryte,
> jaki byłby skutek, dlaczego mimo to zamykamy fazę i co tę granicę znosi.

---

## 1. Wektor — dosłownie

Kontrola `Kod::wywolaniaOmijajaceKonstruktor` (egzekwowana testem „WARUNEK
UTRZYMUJĄCY" w `WaskieGardloTozsamosciTest`) pyta o NAZWĘ narzędzia omijającego
konstruktor (`unserialize`, klasy `Reflection*`, metody wytwarzające/mutujące
z pominięciem API). Odtwarza tę nazwę z trzech postaci: gołego identyfikatora,
pojedynczego literału i **sklejenia SĄSIEDNICH literałów** (`'unse'.'rialize'`).

**Nie odtwarza jej, gdy nazwa jest zbudowana przez ZMIENNE POMOCNICZE.** Zmierzone
na `b60c53a`:

```
$f = 'unse'.'rialize'; $f($x)                 → skaner: ['unserialize']   (łapane)
$a='unse'; $b='rialize'; $f=$a.$b; $f($x)      → skaner: []                (ŚLEPE)
$k='Reflection'.'Cla'; $k.='ss'; new $k()      → skaner: []                (ŚLEPE)
$f=implode('',['unse','rialize']); $f($x)      → skaner: []                (ŚLEPE)
```

Do tej samej klasy należą: `.=`, `implode`, `strrev`, `sprintf`, heredoc
z interpolacją, `new $zmienna` z nazwą złożoną inaczej niż literałem, stała
klasowa niosąca nazwę. Wszystkie zwracają `[]`.

To NIE jest granica „nazwa z żądania" (którą pokrywałaby warstwa 3 wąskiego
gardła). Wartość nie pochodzi z żądania — jest zbudowana ze STAŁYCH literałów,
więc żadna warstwa czytająca żądanie jej nie widzi.

---

## 2. Skutek, gdyby ktoś tak napisał

Plik produkcyjny na allowliście zapisu tożsamości (np. `LogowanieController::powrot`),
czytający pole KONTRAKTOWE `code` (więc warstwa 3 milczy słusznie), odtwarza
`TozsamoscSesji` deserializacją o nazwie sklejonej przez zmienne i woła
`SesjaKonta::zaktualizuj`:

```
sub  = wartość podana przez piszącego (np. ATAK-KOORDYNATOR)
role = ["koordynator", "admin-fundacja"]   ← eskalacja z wyboru piszącego
access_exp = w przyszłości                  ← przeżywa odświeżanie
```

Tożsamość i role z wyboru piszącego, z pominięciem konstruktora i weryfikacji
podpisu — **przy całkowicie zielonej bramce** (zmierzone w rundzie 12 i 13:
15/15 kontroli wąskiego gardła, Larastan „No errors", Pint PASS, pełna suita
bez zmiany liczb).

---

## 3. Dlaczego to NIE jest luka osiągalna z zewnątrz — twierdzenie SPRAWDZALNE

**Twierdzenie:** żaden użytkownik (pacjent, personel, gość) ani żadne żądanie HTTP
nie może uruchomić tego wektora. Wymaga on **dopisania kodu do repozytorium** —
mechanizm jest instrukcjami PHP w pliku produkcyjnym, nie danymi wejściowymi.

**Co trzeba by zmierzyć, żeby to twierdzenie OBALIĆ** (to jest jego treść, nie
zapewnienie):

1. **Ścieżka „dane wejściowe → wykonany kod"** — czy istnieje w systemie miejsce,
   które wykonuje treść pochodzącą z żądania: `eval` na wejściu, `unserialize`
   na danych z żądania, szablon renderujący z parametru, wtyczka ładowana ze
   ścieżki z żądania, konfiguracja wykonywalna zależna od wejścia. **Zmierzone
   na `b60c53a`: takiej ścieżki NIE MA** (`SekretyTest`, `BrakWlasnychHaselTest`
   i skaner narzędzi omijających konstruktor pokrywają `eval`/`unserialize`
   w kodzie produkcyjnym; żadne nie działa na danych z żądania). Gdyby powstała —
   patrz warunek znoszący (§8).
2. **Dostęp do zapisu w repozytorium** — kto może dopisać kod. To jest kontrola
   procesu (przegląd, uprawnienia git), nie kontrola aplikacji. Poza zakresem
   tego dokumentu, ale nazwana.

Dopóki (1) daje „brak ścieżki", wektor jest osiągalny wyłącznie dla osoby piszącej
kod — a przed nieuwagą piszącego kod broni druga linia (§6).

---

## 4. Dlaczego nie naprawiamy dalej w tej fazie

Rozstrzygnięcie klasy z rundy 13, przyjęte przez architekta i właściciela:

> „Kolejne rozszerzanie skanera (`$a.$b`, `.=`, `implode`, `strrev`, stała
> klasowa…) to ta sama denylista, o piętro wyżej — brzeg będzie zawsze."

To jest własność **analizy kodu w ogóle**, nie wada tej implementacji: żadna
kontrola czytająca kod nie rozpozna wszystkich sposobów zapisania tej samej
czynności. Dziewiąte piętro istnieje z definicji. Domknięcie tej klasy wymaga
kontroli, która **nie pyta o kod** — patrz §7.

---

## 5. Co pokryte, a co nie

| forma zapisu nazwy narzędzia | kontrola łapie | powstaje przez nieuwagę |
|---|---|---|
| zwykłe wywołanie (`unserialize(...)`) | **tak** | tak — normalny sposób pisania |
| nazwa kwalifikowana z backslashem (`new \ReflectionClass`) | **tak** | tak |
| dowolna klasa refleksji (z runtime, także `ReflectionEnum`) | **tak** | tak |
| nazwa w pojedynczym literale (`$f='unserialize'`) | **tak** | rzadko |
| sklejenie SĄSIEDNICH literałów (`'unse'.'rialize'`) | **tak** | rzadko |
| **sklejenie przez ZMIENNE** (`$a='unse'; $b='rialize'`) | **nie** | **nie — nikt tak nie pisze przypadkiem** |
| `.=`, `implode`, `strrev`, `sprintf`, heredoc, `new $zmienna` | **nie** | **nie — wymaga celowego obejścia** |

Sedno: **kontrola pokrywa wszystkie formy NATURALNE. Omijają ją wyłącznie zapisy,
które trzeba napisać CELOWO, żeby ją ominąć.** Realne ryzyko (nieuwaga piszącego)
jest pokryte; niepokryte jest wyłącznie obejście świadome, przed którym analiza
kodu i tak nie obroni (§4).

---

## 6. Druga linia obrony — istnieje i działa

1. **Niezależne rundy weryfikacyjne.** Wykonano 13. Ta klasa (odtworzenie
   tożsamości z pominięciem konstrukcji) była wychwytywana w KAŻDEJ rundzie,
   w której się pojawiła — R6A-3, R11-2, R12-1, R13-1. Weryfikator pracuje na
   czystym klonie i sonduje mutacjami, więc widzi mechanizm niezależnie od tego,
   czy skaner statyczny go zna.
2. **Przegląd kodu przy scaleniu.** Zmiana w pliku tożsamości przechodzi przegląd;
   deserializacja o nazwie sklejonej przez zmienne jest wizualnie jaskrawa —
   nikt nie pisze `$a='unse'; $b='rialize'` bez intencji.

Obie linie są kontrolą PROCESU, nie aplikacji — i to jest właściwe miejsce dla
obrony przed nieuwagą piszącego kod.

---

## 7. Termin naprawy — etap B, pierwsze zadanie

**Kontrola SKUTKU zamiast kontroli kodu.** Kierunek wskazany przez weryfikatora
rundy 13: nie pytać „czy w kodzie jest podejrzane wywołanie", lecz sprawdzać
w działaniu, że **każda tożsamość w sesji ma odpowiadający jej dowód weryfikacji
z tego samego żądania** (np. znacznik dowiązany przy `zaloz` do wyniku sprawdzenia
podpisu, niemożliwy do wytworzenia deserializacją, bo nie zależy od treści obiektu,
tylko od sekretu serwera i przebiegu żądania).

Taka kontrola **nie zależy od zapisu kodu**, więc nie ma brzegu tej klasy: nieważne,
jak zapisano nazwę narzędzia — sforgowany obiekt nie będzie miał ważnego dowodu.

**Kiedy:** pierwsze zadanie etapu B, przy silniku rezerwacji, gdzie warstwa żądań
i tak powstaje (taniej niż budować ją teraz osobno).

---

## 8. WARUNEK ZNOSZĄCY — kiedy granica przestaje obowiązywać

Ta granica opiera się na twierdzeniu z §3: wektor wymaga dopisania kodu do
repozytorium i nie jest osiągalny z zewnątrz. **Gdyby powstała ścieżka pozwalająca
uruchomić kod (albo odtworzyć obiekt) z treści pochodzącej spoza repozytorium —
granica przestaje obowiązywać i wraca jako BLOKADA (naprawa przed scaleniem tego
modułu, nie po).**

Do sprawdzenia **przy każdym nowym module przyjmującym treść z zewnątrz**, w
szczególności:

- endpoint wykonujący albo deserializujący dane z żądania;
- mechanizm wtyczek / rozszerzeń ładowanych ze ścieżki zależnej od wejścia;
- konfiguracja wykonywalna (np. reguły jako kod) pochodząca od użytkownika;
- import/upload przetwarzany deserializacją.

Kontrola pilnująca tego warunku należy do przeglądu każdego takiego modułu —
i jest wpisana jako pozycja odbioru etapu B.
