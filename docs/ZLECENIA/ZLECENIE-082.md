# ZLECENIE-082 · 19.08.2026 · OD architekta DO sesji KOD-F1 — naprawa R12-1

**Decyzja właściciela: wariant A — dopinamy siódme piętro.** Zamrożenie ZDJĘTE.
Zbieżność: 29 → 9 → 2 → 5 → 1 → 3 → **1**.

## 1. Co naprawiamy — i czym to NIE jest

R12-1: strażnik ściany typu („warunek utrzymujący R6A-3") jest **denylistą trzech
pisowni** nad tekstem: `unserialize(`, `newInstanceWithoutConstructor(`,
`new ReflectionClass(`. Sklejenie nazwy (`$f='unse'.'rialize'; $f(...)`), backslash
w nazwie klasy, dynamiczna nazwa metody i `ReflectionProperty` **omijają go** — a wtedy
deserializacja odtwarza tożsamość z pominięciem konstruktora i cała bramka milczy
(zmierzone: `sub=ATAK-…`, role `koordynator`, `admin-fundacja`).

**To nie jest wada ściany typu** — ona się obroniła przed fabrykami, klonowaniem, klasą
potomną, kontenerem zależności i podmienioną konfiguracją. To wada **jej strażnika**.

## 2. Kierunek — trzeci raz ten sam, sprawdzony wzorzec

**Allowlista przez lekser, nie denylista nad tekstem.** Masz w repozytorium dwa działające
przykłady tego ruchu (`Kod`, `Zrodlo` dla zapisu tożsamości) — zastosuj ten sam.
Pytanie kontroli brzmi: **„jakie wywołania omijające konstruktor występują w kodzie
produkcyjnym"**, a każde takie wywołanie musi być **dopuszczone jawnie, z uzasadnieniem**
— niezależnie od tego, jak zapisano jego nazwę.

Zakres pytania (nie lista do uzupełniania — istota): wszystko, co potrafi **wytworzyć
obiekt bez wywołania konstruktora albo zmienić jego stan z pominięciem metod publicznych**.

## 3. Kontrole odbioru — obowiązkowe

**Negatywne — każdy wariant osobno, jako stałe perturbacje:**
1. sklejenie nazwy funkcji (`'unse'.'rialize'`),
2. nazwa w zmiennej (`$f = 'unserialize'; $f(...)`),
3. `new \ReflectionClass` z backslashem,
4. dynamiczna nazwa metody / `ReflectionProperty` (wariant spoza dotychczasowej trójki),
5. **wektor rundy 12 w całości**: odtworzenie tożsamości deserializacją w pliku
   z allowlisty + wywołanie `zaktualizuj` — **kontrola ma zapalić**, a nie „nie zauważyć".

**Pozytywna:** kod produkcyjny bez takich wywołań przechodzi; **legalne** użycie
(jeśli gdzieś jest) stoi na jawnej liście z uzasadnieniem — koszt wyjątku równy kosztowi
zgodności, jak przy allowliście funkcji kryptograficznych.

**Przyrządu:** wzorcowe wywołanie **musi** być wykrywane (inaczej pusty wynik znaczyłby
„nie umiem szukać", nie „nie ma") — lekcja z martwego literału `'\$_GET'`.

**Krok dalej (obowiązkowe pytanie):** po przejściu na lekser wskaż, co jeszcze potrafi
ominąć konstruktor lub podmienić stan obiektu (rozszerzenia PHP? `__wakeup`/`__unserialize`?
`var_export`+`eval`? własne mechanizmy frameworka?) — pokryj albo nazwij jako zmierzoną
granicę z dowodem.

## 4. Procedura

Naprawa → bramka OD ZERA **bez potoku** + pełny zestaw perturbacji → commit → **bramka po
commicie** → statyka razem z testami po każdej zmianie plików testowych → nowe zamrożone
SHA zgłoszone warunkiem sprawdzalnym → **meldunek ODPOWIEDZ-082**. Po nim runda 13.

**Zakres ściśle ten jeden.** Nie naprawiaj niczego poza R12-1 — chyba że wyjdzie z pytania
„krok dalej", wtedy z pomiarem i osobnym wpisem.

## 5. Kontekst, który powinieneś znać

Właściciel wybrał ten cykl świadomie, znając alternatywę (zamknięcie F1 z opisaną granicą).
Uzasadnienie mojej rekomendacji, które przyjął: naprawa jest tania i przewidywalna, wzorzec
dwukrotnie sprawdzony u nas, a rzecz dotyczy kontroli pilnującej dostępu do danych o zdrowiu.
**Rób to spokojnie i nie skracaj** — jeżeli powierzchnia okaże się większa, niż zakładam,
zgłoś PRZED robotą, tak jak poprzednio.
