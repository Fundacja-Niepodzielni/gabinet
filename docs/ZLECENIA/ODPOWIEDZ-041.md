# ODPOWIEDZ-041 — STUDIUM WYKONALNOŚCI: SMSAPI

**09.08.2026, noc** · pozycja z `PODJETO-041` · **czytanie dokumentacji, nic nie zbudowane**
· konta nie założyłem, SMS-a nie wysłałem, żadnych danych dostępowych nie zapisałem

**Data odczytu wszystkich źródeł: 09.08.2026.** Dokumentacja SMSAPI **nie jest wersjonowana
numerem** — nie ma na stronach ani numeru wydania, ani daty ostatniej zmiany. **To sam w sobie
jest problem** i wpisuję go do sekcji „czego nie znalazłem": nie umiem powiedzieć, czy za pół
roku czytam to samo. Jedyne, co mogę podać, to data odczytu.

---

## 0 · Trzy rzeczy, dla których warto przeczytać całą resztę

1. **Nazwa „Niepodzielni" (12 znaków) NIE PRZEJDZIE.** Limit 11 znaków potwierdzony w trzech
   niezależnych miejscach, w tym w regulaminie. **To blokuje wniosek właściciela** — trzeba
   wybrać wariant PRZED złożeniem.
2. **`test=1` nie testuje tego, co najbardziej wymaga testu.** Tryb testowy w ogóle nie wysyła
   wiadomości, więc **nie generuje raportu doręczenia** — a raport doręczenia to jedyne źródło
   odpowiedzi na pytanie „SMS nie dotarł czy człowiek nie wpisał". **Rzecz najważniejsza dla
   przepływu logowania jest jedyną, której tryb testowy nie obejmuje.**
3. **SMS Authenticator faktycznie istnieje i faktycznie łamie `D-EKO-001`** — potwierdzone
   cytatem ze strony produktu, nie domysłem. Szczegóły w §2.

---

## 1 · A · Rejestracja nadawcy

| wymaganie | werdykt | źródło (odczyt 09.08.2026) |
| --- | --- | --- |
| pole nadawcy „Niepodzielni" (12 znaków) | **NIE DA SIĘ** | limit 11 znaków — [regulamin](https://www.smsapi.pl/regulamin), [FAQ](https://www.smsapi.pl/faq), [poradnik brandingu](https://www.smsapi.pl/blog/podstawy/branding-sms-skojarz-swoja-marke-z-wysylka-sms/) |
| polskie znaki diakrytyczne | **NIE DA SIĘ** | „bez polskich znaków diakrytycznych" — poradnik brandingu |
| dozwolone znaki | DA SIĘ: `a-z A-Z 0-9 . & @ - + _ ! % #` i spacja | regulamin, poradnik brandingu |
| co najmniej jedna litera | wymóg | poradnik brandingu |
| trzy cyfry pod rząd | **zabronione** (np. `TEST1234` odpada) | poradnik brandingu |
| nazwa wyglądająca jak numer telefonu | **zabronione** | regulamin |
| czas weryfikacji | **do 1 dnia roboczego** | „Poczekaj na weryfikację (maksymalnie jeden dzień roboczy)" — poradnik brandingu |
| kryteria odrzucenia | **WYMAGA UZNANIA OPERATORA** | regulamin, klauzula 1.4(24) |
| dokumenty przy nazwie firmowej / znaku towarowym | **WYMAGA — może zażądać oświadczeń i dokumentów** | regulamin 1.4(24) |

### ⚠ To jest twarda blokada wniosku właściciela

> **„Akceptacja Pola Nadawcy wybranego przez Klienta zależy w całości od swobodnego uznania LINK,
> który może odrzucić wybrane Pole Nadawcy"** — regulamin, klauzula 1.4(24).

Czyli: **1 dzień roboczy to czas na odpowiedź, nie obietnica zgody.** Warto złożyć wniosek
z wariantem podstawowym i **mieć zapasowy przygotowany od razu**, bo drugie podejście to drugi
dzień roboczy.

### Warianty mieszczące się w regułach — do wyboru przez właściciela

| wariant | znaków | uwaga |
| --- | --- | --- |
| `NIEPODZIELN` | 11 | ucięte, ale całe wersalikami czyta się jak skrót — najbliżej pełnej nazwy |
| `Niepodzieln` | 11 | ucięcie widoczne, wygląda na literówkę |
| `Niepodziel` | 10 | ucięcie mniej rzucające się w oczy |
| `Fundacja NP` | 11 | ze spacją; poprawne, ale zmienia markę na skrót |
| `NIEPODZIELNI.COM` | — | **odpada, 16 znaków** |

**Nie rekomenduję** — to decyzja o marce, nie techniczna. Podaję tylko, które przejdą przez filtr.
Zwracam uwagę na jedno: **odbiorca zobaczy tę nazwę zamiast numeru i to ona ma go upewnić, że SMS
jest od nas, a nie od oszusta.** Przy kodach jednorazowych to nie jest kwestia estetyki.

---

## 2 · B · Kody jednorazowe — ⚠ PUŁAPKA POTWIERDZONA

**SMS Authenticator istnieje** — [strona produktu](https://www.smsapi.pl/sms-authenticator),
sekcja 16 w [dokumentacji](https://www.smsapi.pl/docs/) („Wysyłka kodu", „Sprawdzenie
poprawności kodu").

### Co dokładnie robi — cytat, nie parafraza

> **„Wystarczy, że wyślesz do naszego API prośbę o sprawdzenie podanego numeru, a my zajmiemy się
> resztą — wyślemy użytkownikowi SMS-a z kodem bezpieczeństwa"**

> Usługa zwalnia z **„zarządzania wysyłką SMS i przechowywaniem kodów bezpieczeństwa"**

**Kod generuje SMSAPI. Kod przechowuje SMSAPI. Poprawność kodu rozstrzyga SMSAPI.**

### Konflikt — wskazuję wprost, jak prosiłeś, i NIE rekomenduję

`D-2026-08-09-11` mówi, że krok „numer + kod" **to jest logowanie**, a `D-EKO-001`, że dowód
tożsamości żyje w jednym miejscu — w Kontach Niepodzielni.

> **Przy SMS Authenticatorze zdanie „czy ta osoba jest tym numerem" wypowiada dostawca SMS-ów.
> Nasz system pyta go o werdykt i przyjmuje odpowiedź.** Dowód tożsamości pacjenta przenosi się
> poza nasz system logowania — **tak samo jak przy WordPressie, tylko przez inne drzwi.**

Trzy skutki, o których warto wiedzieć przy rozstrzyganiu:

1. **Awaria albo wygaśnięcie środków u dostawcy = pacjenci nie mogą się zalogować** i nie mamy
   żadnej drogi obejścia, bo nie mamy kodów u siebie.
2. **Zmiana dostawcy przestaje być wymianą modułu wysyłkowego**, a staje się wymianą fragmentu
   mechanizmu logowania.
3. **Dowód logowania pacjenta korzystającego z pomocy psychologicznej** żyje u podmiotu
   trzeciego. To wychodzi poza samą architekturę — dotyka `RODO art. 9`.

### Droga bez tego konfliktu — istnieje i jest udokumentowana

Zwykła wysyłka SMS (`sms.do`) wystarczy: **kod generują i sprawdzają Konta**, SMSAPI tylko
przewozi tekst i **nie wie, że to kod**. Wtedy SMSAPI jest przewoźnikiem, nie świadkiem.

**Rozstrzygnięcie zostawiam Tobie.** Odnotowuję tylko, że wariant bez konfliktu **nie kosztuje
więcej ani nie wymaga innego produktu** — kosztuje napisanie generowania i sprawdzania kodu po
stronie Kont, czyli tam, gdzie `D-2026-08-09-11` i tak to umieściło.

---

## 3 · C · Wysyłka, raporty doręczeń, błędy

| wymaganie | werdykt | źródło |
| --- | --- | --- |
| raport doręczenia (callback) | DA SIĘ | dokumentacja, sekcja 9 „Raporty Callback" |
| pola raportu | `MsgId`, `status`, `status_name`, `idx`, `sent_at`, `donedate`, `to`, `from`, `mcc`/`mnc`, `points` | dokumentacja, sekcja 9 |
| potwierdzenie odbioru raportu | **skrypt MUSI zwrócić dosłowne `OK`**, inaczej ponawiane cyklicznie | dokumentacja, sekcja 9 |
| ponawianie raportów | „cyklicznie aż do odbioru bądź archiwizacji wiadomości" | dokumentacja, sekcja 9 |
| źródłowe adresy IP raportów | 5 adresów: `89.174.81.98`, `91.185.187.219`, `213.189.53.211`, `31.186.83.18`, `212.91.26.253` | dokumentacja, sekcja 9 |
| statusy doręczenia | sekcja 18 istnieje | dokumentacja, sekcja 18 |
| „nie dotarł" i **po jakim czasie** | **NIE POTWIERDZONE — patrz §7** | — |
| numer nieprawidłowy | `ERROR:13` — „brak poprawnych numerów", **synchronicznie** | dokumentacja, sekcja 19 |
| brak środków | `ERROR:103`, **wiadomości NIE wysłane** | dokumentacja, sekcja 19 |
| IP spoza listy | `ERROR:105` | dokumentacja, sekcja 19 |
| powtórzony `idx` w ciągu 24 h | `ERROR:53` | dokumentacja, sekcja 19 |

### ⚠ `ERROR:53` — idempotencja za darmo, ale z ostrzem

Powtórzenie tego samego `idx` w ciągu 24 h jest odrzucane. **To dobra wiadomość dla naszej
idempotencji** — powtórzone żądanie nie wyśle drugiego SMS-a.

> **Ale to znaczy też, że PONOWNA WYSYŁKA KODU dla tego samego pacjenta musi mieć NOWY `idx`.**
> Jeśli ktoś zbuduje `idx` z identyfikatora rezerwacji albo z numeru telefonu, **przycisk
> „wyślij kod ponownie" przestanie działać** — i to cicho, bo błąd wróci do serwera, a pacjent
> zobaczy tylko, że SMS nie przychodzi. Dokładnie ten objaw, którego §2 zlecenia kazał unikać.

`idx` **musi** nieść coś jednorazowego (identyfikator próby), nie tożsamość rezerwacji.

### Co z tego wynika dla przepływu logowania

`ERROR:13` i `ERROR:103` wracają **od razu, w odpowiedzi na żądanie** — więc „nie udało się
wysłać" umiemy odróżnić od „wysłane" **natychmiast**. To dobra wiadomość.

**Czego NIE umiemy:** odróżnić „SMS wysłany, ale nie dotarł" od „dotarł, człowiek nie wpisał" —
bo to wymaga statusu z raportu, a **czasu, po jakim status staje się rozstrzygający, nie
znalazłem w dokumentacji SMSAPI** (§7). Bez tej liczby nie da się napisać komunikatu, który
podpowie człowiekowi, co zrobić — a to było jawnym wymaganiem punktu C.

---

## 4 · D · Limity, koszty, zabezpieczenia

| wymaganie | werdykt | źródło |
| --- | --- | --- |
| SMS krajowy, przedpłata | **0,17 → 0,11 zł** zależnie od progu doładowania (49 zł → 10 000 zł) | [cennik](https://www.smsapi.pl/cennik) |
| SMS krajowy, abonament | **0,14 → 0,08 zł** + **49 zł netto/mies.** | cennik |
| SMS zagraniczny | **0,35 zł** — **2–3× drożej niż krajowy** | cennik |
| minimalne doładowanie | 49 zł | cennik |
| osobna cena za 2FA | **NIE MA** — stawki jak za zwykły SMS | cennik |
| ograniczenie do numerów polskich | DA SIĘ — `&skip_foreign=1` | dokumentacja, sekcja 2 |
| **lista dozwolonych krajów** (nie „tylko PL") | **NIE POTWIERDZONE — patrz §7** | — |
| limit tempa po stronie operatora | **100 żądań/s z jednego IP** | dokumentacja (wersja .com) |
| filtrowanie po IP | DA SIĘ — panel „ip-filters" | dokumentacja, `ERROR:105` |
| po wyczerpaniu środków | **`ERROR:103`, wiadomości NIE wysłane** — **NIE po cichu** | dokumentacja, sekcja 19 |
| powiadomienie o niskim stanie konta | DA SIĘ — ustawiane w panelu | pomoc SMSAPI |

### ⚠ Odpowiedź na pytanie, które zadałeś jako krytyczne

> **„Co się dzieje po wyczerpaniu środków — czy wysyłka cicho przestaje działać?"**

**Nie po cichu.** `ERROR:103` wraca **w odpowiedzi na żądanie**, a wiadomości nie są wysyłane
wcale (nie ma wysyłki częściowej — dokumentacja mówi, że gdy koszt całości przekracza saldo,
zwracany jest błąd i **żaden** SMS nie idzie).

**Ale to przenosi cały ciężar na nas.** Operator **nie zadzwoni**. Jeśli nasz kod potraktuje
`ERROR:103` jak zwykłe niepowodzenie wysyłki i pokaże pacjentowi „spróbuj ponownie", to:

> **logowanie pacjentów przestanie działać, a my dowiemy się z reklamacji** — dokładnie ten
> scenariusz, który nazwałeś awarią do wykrycia NATYCHMIAST.

`ERROR:103` **musi mieć własną, głośną ścieżkę alarmową**, odrębną od zwykłych błędów wysyłki.
To nie jest błąd pacjenta ani błąd numeru — **to jest awaria fundacji.** Powiadomienie o niskim
stanie konta po stronie SMSAPI to zabezpieczenie drugie, nie pierwsze: **nie wolno budować
alarmu wyłącznie na nim**, bo dzieli źródło z tym, co ma pilnować (reguła `C1` — konto SMSAPI
pilnowałoby konta SMSAPI).

### Koszt ograniczenia tempa — liczba, nie wrażenie

Publiczny przycisk „wyślij kod" bez ograniczenia to przy stawce **0,17 zł** i **100 żądań/s**
limitu operatora **do 17 zł na sekundę**. Minimalne doładowanie 49 zł znika w **trzy sekundy**.
To potwierdza wymaganie 2 z `D-2026-08-09-11` liczbą, nie przeczuciem.

**`skip_foreign=1` jest przełącznikiem, nie listą** — „tylko polskie" albo „wszystkie".
Przy stawce zagranicznej 0,35 zł ten przełącznik jest tańszym zabezpieczeniem niż limity tempa,
**ale odcina też pacjentów z numerami zagranicznymi.** Decyzja fundacji, nie techniczna —
odnotowuję, bo brzmi jak ustawienie, a jest wykluczeniem.

---

## 5 · E · Kwalifikacja wiadomości transakcyjnych

| wymaganie | werdykt | źródło |
| --- | --- | --- |
| operator sam kwalifikuje wiadomość jako transakcyjną | **NIE — nie znalazłem takiego rozróżnienia** | regulamin |
| zakaz wiadomości niezamówionych | jest | regulamin 6.2.5(a) |
| **kto odpowiada za zgody** | **KLIENT, wyłącznie** | regulamin 6.1.2, 6.7 |

> **Klauzula 6.1.2:** klient zapewnia przed wysłaniem „wszelkie wymagane prawa, autoryzacje,
> licencje, **zgody** i pozwolenia".
> **Klauzula 6.7:** „**wyłączna odpowiedzialność**" klienta za dobór odbiorców i poprawność danych.

### ⚠ Odpowiedź jest inna, niż zakładało zlecenie

Zlecenie mówiło: *„zgody marketingowe nas nie dotyczą (to wiadomości transakcyjne) — ale sprawdź,
czy operator tak to kwalifikuje"*.

> **Operator TEGO NIE KWALIFIKUJE — ani tak, ani inaczej. On przerzuca ocenę na nas w całości.**

To nie jest zielone „nie dotyczy nas". To jest: **nikt nam nie potwierdzi, że kod logowania jest
wiadomością transakcyjną — my to twierdzimy i my za to odpowiadamy.** Ocena wygląda na oczywistą
(SMS wysłany na wyraźne żądanie użytkownika, w odpowiedzi na jego działanie, bez treści
handlowej), ale **jej podstawa jest nasza**, a nie operatora.

**Zostawiam jako pytanie do IOD**, nie jako rzecz zamkniętą — to samo miejsce, gdzie już czeka
`DŁUG WOBEC IOD` z retencji.

---

## 6 · F · Środowisko testowe — ⚠ odpowiedź jest DWUCZĘŚCIOWA

| wymaganie | werdykt | źródło |
| --- | --- | --- |
| test bez wysyłania prawdziwych SMS-ów | **DA SIĘ** — `&test=1` | dokumentacja, sekcja 2 |
| test **bez zarejestrowanego nadawcy** | **DA SIĘ** — puste pole → domyślne, **„Test"** | dokumentacja, sekcja 2 |
| SMS-y na start | 50 sztuk przy rejestracji | [FAQ](https://www.smsapi.pl/faq) |
| **test ścieżki raportu doręczenia** | **NIE DA SIĘ przez `test=1`** | wynika z definicji — patrz niżej |

**Twoje twierdzenie potwierdzone dokumentacją:** budowa **nie czeka** na wniosek właściciela.
Cytat: *„Wiadomość nie jest wysyłana, wyświetlana jest jedynie odpowiedź (w celach testowych)"*
oraz *„Pozostawienie pola pustego powoduje wysłanie domyślnego rodzaju wiadomości z domyślnym
polem nadawcy"* — domyślne to **„Test"**.

### ⚠ Ale twierdzenie jest prawdziwe WĘŻEJ, niż brzmi

> **`test=1` w ogóle nie wysyła wiadomości — więc nie powstaje raport doręczenia.**
> A raport doręczenia to jedyne źródło odpowiedzi na pytanie „nie dotarł czy nie wpisał",
> które w §C nazwałeś **krytycznym dla przepływu logowania**.

Czyli: **kształt żądania, obsługę `ERROR:13`, `ERROR:53`, `ERROR:103` i całą logikę limitów
zbudujemy i przetestujemy bez konta produkcyjnego i bez nadawcy.** Natomiast **obsługi statusów
doręczenia nie da się zamknąć trybem testowym** — wymaga prawdziwej wysyłki (choćby z nadawcą
„Test", na własny numer, z darmowej puli 50 SMS-ów).

**Wniosek praktyczny:** budowa nie czeka na wniosek właściciela, ale **domknięcie ścieżki raportów
czeka na pierwszą prawdziwą wysyłkę** — co jest tańsze i szybsze niż rejestracja nadawcy, ale
nie jest zerem. To rozróżnienie warto mieć w planie, żeby nie odkryć go w F2.

---

## 7 · CZEGO NIE ZNALAZŁEM W DOKUMENTACJI

**Brak informacji to nie jest informacja o braku.** Poniżej rzeczy, których **nie potwierdziłem**
— nie twierdzę, że ich nie ma.

| czego brak | dlaczego to boli | co z tym zrobić |
| --- | --- | --- |
| **domyślny czas ważności wiadomości** i po jakim czasie status staje się `EXPIRED` | **bez tej liczby nie napiszemy komunikatu „SMS nie dotarł"** — a to jawne wymaganie §C | pytanie do BOK **przed** F2 |
| **pełna lista statusów doręczenia SMSAPI, słowo w słowo** | sekcja 18 istnieje, ale nie udało mi się jej odczytać — strona ucina treść; **statusy, które podałem, pochodzą ze źródeł OGÓLNYCH o SMS, nie od SMSAPI** | odczytać sekcję 18 u źródła; **nie kodować statusów z mojej listy** |
| **endpointy SMS Authenticatora, czas ważności kodu, limit prób** | bez tego nie wiadomo, czy wariant operatorski w ogóle spełnia nasze wymagania | odpada, jeśli rozstrzygniesz §2 na naszą korzyść |
| **lista dozwolonych krajów** (nie tylko przełącznik `skip_foreign`) | zlecenie mówiło „zmierzone, że istnieje" — **nie potwierdziłem tego w dokumentacji**; znalazłem tylko przełącznik „pomiń nie-polskie" | zweryfikować w panelu; **do tego czasu nie zakładać, że da się** |
| **limit tempa na numer po stronie operatora** | znalazłem tylko 100 żądań/s **na IP** — to limit techniczny, nie ochrona przed zasypaniem jednego numeru | **nasze ograniczenie tempa jest jedyne, jakie mamy** |
| **czy `test=1` zużywa punkty** | wpływa na to, ile testów zmieści się w darmowej puli | drobne, ale sprawdzić |
| **domyślny okres ważności punktów** | regulamin 4.5 mówi, że ważność „może być przypisana", bez wartości | pytanie handlowe do BOK |
| **wersja i data zmiany dokumentacji** | **żadna ze stron nie podaje numeru wersji ani daty ostatniej aktualizacji** — nie umiem stwierdzić, czy to, co przeczytałem dziś, obowiązywało wczoraj | przy każdym wykorzystaniu podawać **datę odczytu**, tak jak w tym dokumencie |

### ⚠ Jedna sprzeczność w źródłach — zgłaszam, nie zamiatam

Strona produktu twierdzi, że **oficjalne biblioteki klienckie wspierają SMS Authenticator**.
W `README` biblioteki PHP (`smsapi/smsapi-php-client`) **nie ma o tym ani słowa** — wymienia
wysyłkę wiadomości, skracanie odnośników i administrowanie kontem.

**Nie rozstrzygam, które źródło jest aktualne.** Odnotowuję, że **materiał marketingowy i
`README` biblioteki mówią co innego** — a to typowa `P3`: jedna rzecz opisana w dwóch miejscach,
bez niczego, co wymusza zgodność.

---

## 8 · Podsumowanie: co zmienia plan

**Czerwone — wymaga decyzji lub działania przed F2:**

1. **Nazwa nadawcy — wybór wariantu przed złożeniem wniosku** (blokuje właściciela).
2. **`ERROR:103` musi mieć osobną, głośną ścieżkę alarmową** — inaczej awaria logowania
   pacjentów dotrze do nas reklamacją.
3. **`idx` nie może nieść tożsamości rezerwacji** — inaczej „wyślij ponownie" umiera cicho.
4. **Czas ważności wiadomości nieznany** — bez niego komunikat „SMS nie dotarł" nie powstanie.
5. **Kwalifikacja transakcyjna jest NASZA, nie operatora** — do IOD.

**Zielone — nic nie zmieniają, wymieniam dla porządku:** wysyłka, raporty callback, ograniczenie
do numerów polskich, filtrowanie IP, tryb testowy bez nadawcy, brak dopłaty za 2FA.

**Do rozstrzygnięcia przez Ciebie:** §2 — SMS Authenticator kontra `D-EKO-001`. Opisałem konflikt,
wskazałem drogę bez niego, **nie rekomenduję.**

---

**Stan kanału:** pozycję zamyka ten plik (`S-3`). Po nim commit, push i `PODJETO` na
`BEZ_DANYCH_OSOBOWYCH` — zgodnie z Twoją sekwencją.
