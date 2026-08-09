# ODPOWIEDŹ-005 — runda 1, przyrząd: P2, klasa 3, D3-kod

## Pomiar kanału

| pomiar | wartość |
|---|---|
| plik powstał (`stat`) | **12:05:37.087** |
| obserwator mnie obudził | **12:06:16.658** |
| **różnica** | **39,6 s** |

Piąty pomiar: 28,9 · 33,4 · 76,2 · 25,2 · **39,6 s**. Do bezczynnej sesji: **25–40 s**.

---

## 1 · P2 — projekt, którego jestem właścicielem. **KLAMRA jest w kodzie.**

**Kod do wzięcia:** `backend/tests/Wsparcie/KlamraPerturbacji.php` (nowy) oraz jego użycie
w `backend/tests/Feature/RetencjaWykonanieTest.php`. Nie opisuję wzorca — oddaję plik.

### Co konkretnie zrobiłem z Twoim warunkiem bezwzględnym

Wszystkie trzy wymogi są w kodzie, nie w komentarzu:

1. **Skan wstępny PRZED startem** — `regulaIstnieje()` pyta **`pg_rules`**, czyli MAGAZYN,
   a nie pamięć procesu.
2. **ODMOWA przy pozostałości** — `RuntimeException`, twarda, nie ostrzeżenie. Komunikat
   mówi wprost, że **kasowanie w tabeli jest teraz CICHO ZABLOKOWANE**, podaje gotowe
   `DROP RULE …` i **odmawia posprzątania automatycznie**: pozostałość znaczy, że jakiś
   przebieg zginął, a skoro tak, nie wiadomo, co jeszcze zostawił. Sprzątnięcie po cichu
   zamieniłoby sygnał w ciszę.
3. **Klamra** — `zablokujKasowanie()` **zwraca sprzątaczkę**, którą wołający wykonuje
   w `finally`. Celowo nie chowam tego w destruktorze: destruktor biegnie w nieokreślonym
   momencie, a chodzi o to, żeby zdjęcie było **widoczne w kodzie testu**.

**Napisałem też wprost, czego NIE wolno przyjąć za klamrę:** u mnie domyka dodatkowo
transakcja `RefreshDatabase` — i **nie wolno na niej polegać**, bo repozytoria adaptujące
(helpdesk: żywy Zammad; hub) transakcji na test nie mają. Klamra jest jawna właśnie po to,
żeby przenosiła się bez zmian.

### Perturbacja falsyfikująca klamrę — i to ona wykryła moją własną wadę

`RetencjaWykonanieTest`, test „KLAMRA: perturbacja ODMAWIA startu, gdy blokada została po
poprzednim przebiegu". Zakłada regułę ręcznie, dowodzi jej istnienia pytaniem do bazy,
sprawdza odmowę, a potem **kierunek odwrotny**: po sprzątnięciu klamra ma wpuścić i zdjąć
blokadę.

**Zmierzone w obie strony:**

```
sprawny:                    Tests: 4 passed (20 assertions)
odmowa usunięta (if(false)): Tests: 1 failed, 3 passed
przywrócone:                Tests: 4 passed (20 assertions)
```

**Pierwsza wersja tej perturbacji była wadliwa i mówię o tym, bo dotyczy wprost przenośności.**
Czerwień brała się z `QueryException` → `SQLSTATE[25P02] … current transaction is aborted`:
PostgreSQL unieważnia całą transakcję po błędnym zapytaniu, więc test padał **na sprzątaniu**,
nie na asercji — mierzył własne zanieczyszczenie. Naprawione **punktem zapisu**
(`probaZerwania()` → `SAVEPOINT`/`ROLLBACK TO`) i złapaniem `Throwable` zamiast
`RuntimeException`, żeby o czerwieni rozstrzygała **treść komunikatu**, a nie klasa wyjątku.
Po poprawce czerwień pochodzi z mojej asercji („KLAMRA NIE ODMÓWIŁA…").

**To jest ostrzeżenie do adaptacji:** kto przeniesie ten wzorzec do bazy transakcyjnej i nie
doda punktu zapisu, dostanie perturbację, która świeci czerwono **z niewłaściwego powodu**.

### Zastrzeżenie, którego nie wolno zgubić przy adaptacji

Wzorzec dowodzi **KASOWANIA**, nie **URUCHAMIANIA**. Zapisałem to w docblocku klasy.
U mnie ten brak jest zmierzony — **R6A-11: `ZadanieRetencji` nie ma ani jednego wywołującego** —
i należy do rundy **przedmiotu**, nie przyrządu. Świadomie **nie dodałem** asercji „zadanie
jest zaplanowane i chodzi": byłby to nowy czerwony na obiekcie, czyli praca spoza rundy 1.

---

## 2 · KLASA 3 — zrobione **częściowo**, i mówię dokładnie ile

Warunek domknięcia P1 u kont. **Nie zamknąłem całej klasy** — zamknąłem jej najgroźniejszy
mechanizm, ten, przez który perturbacje tożsamości **nie mogły zaświecić**.

**Co zrobiłem:** wszystkie **siedem** wywołań `oczekuj_czerwone` celujących w
`OdebranieRoliTest.php` biegło na CAŁYM pliku, który jest trwale czerwony przez nogę 1 —
więc czerwień przychodziła zawsze, niezależnie od mutacji (R6B-13). Zawęziłem je `--filter`
do badanego testu:

```
przed:  wywołań na całym pliku: 7   ·  zawężonych: 0
po:     wywołań na całym pliku: 0   ·  zawężonych: 7
```

Do tego **`--przyczyna "POZYTYWNY"` wymieniona na komunikat asercji** („Logout nie trafił
w sesję tego użytkownika"). „POZYTYWNY" to **nazwa testu**, a Pest wypisuje nazwy w każdym
przebiegu, także zielonym — więc jako zawężenie nie zawężała niczego (R6B-15).

**Dlaczego `--filter`, a nie samo `--przyczyna`:** przyczyna dopasowuje tekst w wyjściu,
więc broni się dopiero po fakcie. Filtr **w ogóle nie uruchamia** pozostałych testów, więc
noga 1 **nie ma jak** dostarczyć czerwieni. To jest różnica między strażnikiem a konstrukcją.

**Czego NIE zrobiłem, a należy do tej klasy:** wspólnego pomocnika `werdykt()`, który
odmawiałby wydania werdyktu bez (1) tabeli „wartość → dokładnie jeden świat", (2) gałęzi
bazowej z tego samego przebiegu, (3) zawężenia przyczyny. Zawężenie z tej rundy zamyka
**punkt (3)** i część (2) dla siedmiu scenariuszy; **punkty (1) i (2) dla pozostałych
kontroli zostają otwarte.** Klasa ma dziewięciu członków — **zamknąłem mechanizm dwóch
(R6B-13, R6B-15), reszta czeka.** Nie zawężam klasy do tego, co umiałem zrobić w jednej rundzie.

---

## 3 · D3-kod — nowa kontrola, z perturbacją i z kierunkiem odwrotnym

**`backend/tests/Feature/TwierdzeniaKomentarzyTest.php`** (nowy). Rozszerza
`ObietniceKomentarzyTest` ze **znaczników** na **twierdzenia**.

**Kontrakt, celowo tani:** komentarz w `app/`, który zawiera SŁOWO ORZEKAJĄCE
(`nie da się`, `niewywoływaln`, `gwarantuj`, `zamknięta/e`, `naprawion`, `niemożliw`),
musi w **tym samym bloku komentarza** nieść `@dowod:` — nazwę testu, znalezisko albo decyzję.
Nie da się maszynowo sprawdzić, czy zdanie po polsku jest prawdziwe; da się wymusić, żeby
autor **wskazał świadka**, a wtedy nieprawdziwe twierdzenie przestaje być anonimowe.

**Kontrola zapaliła się od razu na dziesięciu miejscach** — i to jest jej najlepsza
rekomendacja, bo **cztery z nich to zdania, które weryfikator OBALIŁ** (R6A-3):
„typ, którego **nie da się** wytworzyć z niczego", „ścieżka … jest **niewywoływalna**",
„**Nie da się** go zdobyć". Stały w kodzie przez całą rundę i **nic nie mogło ich
zakwestionować**.

**Jak je domknąłem — dwiema różnymi drogami, świadomie:**
- **siedem twierdzeń OBALONYCH → OSŁABIONE** do stanu zgodnego z pomiarem
  („niewywoływalna" → „trudniejsza"), każde z `@dowod: R6A-3` wskazującym **obalenie**.
  Nie dopisywałem im dowodu, bo dowodu nie ma — jest kontrdowód.
- **trzy twierdzenia PRAWDZIWE → wskazany świadek** (`@dowod: OdebranieRoliTest …`).
  Przy sondzie `SprawdzKonta` wpisałem jawny wyjątek: jej przedmiotem jest ŻYWY IdP,
  nieosiągalny w suicie — **to jest wyjątek nazwany, nie przeoczenie**.

**Kierunek odwrotny jest w suicie**, bo bez niego „zero bez dowodu" przechodzi także wtedy,
gdy skaner nie widzi niczego: drugi test buduje w katalogu tymczasowym plik z twierdzeniem
bez dowodu (**ma zostać znaleziony**) i z dowodem (**ma zostać przepuszczony**).

---

## 4 · Stan przyrządu po tej rundzie

```
pest:  Tests: 1 failed, 183 passed (652 assertions)     ← czerwony to NOGA 1, zamierzony
podłogi bramki: 180/635 → 183/647   (stan 184/652)
```

Podłogi podniosłem **w tym samym commicie**, w którym doszły kontrole — i poprawiłem
komentarz nad nimi, bo zaczął kłamać o własnej wartości. Byłaby to klasa D3 popełniona
w suicie, którą właśnie tą klasą zamykam.

**Nie zamykam własnej pracy.** Bramki po tych zmianach **nie przebiegłem** — zmieniałem
`bramka.sh` (podłogi, komentarz) i `perturbacje.sh` (siedem zawężeń), a pełnego zestawu
perturbacji też nie uruchomiłem. **To jest praca dla weryfikatora**, nie dla mnie:
zielone od autora jest informacją, nie weryfikacją.

---

## 5 · Czy projekt przenosi się bez zmian — jedno zdanie, o które prosiłeś

**Kontrakt P2 przenosi się bez zmian (nazwany artefakt · skan magazynu · twarda odmowa ·
zdjęcie na ścieżce biegnącej przy awarii), ale KAŻDA z trzech implementacji wymaga adaptacji
językowej i jednej decyzji lokalnej: czym jest „artefakt" i gdzie żyje** — u mnie regułą
PostgreSL w `pg_rules`, u helpdesku najpewniej regułą albo wyzwalaczem w bazie Zammada
(gdzie **klamrą musi być `trap … EXIT INT TERM`**, bo transakcji na test tam nie ma),
u hubu i kont — artefaktem w magazynie sesji.

**Dwa ostrzeżenia do adaptacji, oba zmierzone u mnie, nie przewidziane:**
1. **Baza transakcyjna wymaga punktu zapisu**, inaczej perturbacja świeci czerwono
   z niewłaściwego powodu (`25P02`) — i wygląda jak sukces kontroli.
2. **Wzorzec dowodzi kasowania, nie uruchamiania.** Skopiowany bez osobnej asercji
   „zadanie jest zaplanowane i chodzi" odtwarza W-17 pod nową nazwą — u mnie ten brak
   jest zmierzony (R6A-11) i wciąż otwarty.

## Zakazy

Zero `main`, merge, deploy · nic poza fundację · zero zapisu w cudzych repozytoriach ·
zmiany wyłącznie w przyrządzie (kontrole, harness, podłogi) i w komentarzach, które te
kontrole właśnie zakwestionowały. **Sprzeczności ze zleceniem: brak.**
