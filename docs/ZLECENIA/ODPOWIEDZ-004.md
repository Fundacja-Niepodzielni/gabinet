# ODPOWIEDŹ-004 — czy czat i WordPress to egzemplarze MOICH klas

## Pomiar kanału

| pomiar | wartość |
|---|---|
| plik powstał (`stat`) | **11:29:38.688** |
| obserwator mnie obudził | **11:30:03.841** |
| **różnica** | **25,2 s** |

Czwarty pomiar: 28,9 · 33,4 · 76,2 · **25,2 s**. Ten trafił w sesję bezczynną, więc jest
miarodajny — i **potwierdza wyjaśnienie odstającego trzeciego**: tamte 76 s to była suma
kanału i domykania poprzedniego zlecenia, nie kanał. Trzy pomiary „do bezczynnej sesji"
układają się w **25–33 s**.

**Metoda:** wyłącznie odczyt dwóch raportów rozpoznania. **Do `chat/` ani `Niepodzielni-dev/`
nie wchodziłem**, niczego nie uruchamiałem, nie proponuję tam napraw. Wszystko poniżej to
analiza cudzego pomiaru, nie mój pomiar.

---

## Odpowiedź krótka

**Sześć znalezisk to egzemplarze moich klas. Trzy NIE SĄ moje i mówię to wprost.
A jedno odsłania klasę, której w moim dokumencie NIE MA W OGÓLE** — i to jest
najważniejsza rzecz w tej odpowiedzi.

| znalezisko | moja klasa | czy MOJA naprawa je zamyka |
|---|---|---|
| czat · audyt zapisany bezwarunkowo, wynik `->delete()` odrzucony | **4** | **NIE — tylko w połowie** |
| czat · brak warstwy znacznika unieważnienia | **instancja R6A-11 (klasa ekosystemowa)** | nie dotyczy — nie ma czego naprawiać, jest czego dobudować |
| czat · `RpInitiatedLogout`: brak `id_token_hint` → cichy redirect | **3** | **NIE — moja naprawa dotyczy pomiaru, nie produktu** |
| czat · test certyfikujący wejście, którego kontroler nie obsługuje | **3** (+ 6) | częściowo |
| WP · Z2 retencja: `deleted_count` liczy dopasowane, nie usunięte | **4** | **NIE — tylko w połowie** |
| WP · opakowanie AJAX: `auth_callback` niewywoływalny → przepuść | **5** | **TAK, w całości** |
| czat · kasowanie po `sub` = wszystkie sesje osoby | **ŻADNA — brak takiej klasy u mnie** | — |
| WP · Z3 eksport CSV za `edit_posts` | nie moja — rodzina helpdeskowego W-19 | — |
| WP · Z1 drugie źródło tożsamości | nie moja — stan architektury, nie mechanizm cichej awarii | — |

---

## 1. Dopasowania, przy których mechanizm jest TEN SAM

### 1.1 · Czat: audyt bezwarunkowy + odrzucony wynik `->delete()` → **moja KLASA 4**

Mechanizm jest identyczny co do joty, nie tylko podobny. U nich
(`OidcController.php:106-116`): wartość zwracana przez `->delete()` — czyli **liczba
skasowanych wierszy** — jest odrzucana, a wpis audytowy wykonuje się **bezwarunkowo**.
Skasowanie zera wierszy zostaje odnotowane jako `sso.backchannel_logout`.

U mnie **R6B-9**: utrata mapy `sid → sesje` daje `skasowane_sesje = 0` **bez komunikatu**;
u mnie **N-14**: zapis śladu zwraca ostrzeżenie zamiast rzucić, licznik nie rośnie, a odczyt
**udaje się** i oddaje nieświeżą liczbę. We wszystkich trzech: **wynik operacji bezpieczeństwa
nie wchodzi do werdyktu, a sukces jest raportowany niezależnie od niego.**

**Czy moja naprawa zamknęłaby ich egzemplarz: NIE, i to jest dla mnie ważniejsze niż samo
dopasowanie.** Zapisałem naprawę jako *„każdy magazyn niosący asercję bezpieczeństwa musi
spełniać cztery wymagania trwałości, a odczyt z magazynu, który ich nie spełnia, ma zwracać
«nie wiem», nie liczbę"*. To domyka **magazyn**. Czat i WordPress pokazują **drugą połowę tej
samej klasy: wynik OPERACJI**, który nikt nie odbiera. Ich magazyn jest w porządku — zepsute
jest to, że nikt nie pyta, ile wierszy zniknęło.

**Wniosek: klasa jest szersza niż moja naprawa** — i wybieram wersję „jedna klasa, naprawa
niepełna", nie „dwie klasy". Uzasadnienie: w obu połowach zawodzi **ta sama rzecz — brak
rozstrzygnięcia losu operacji** — a objaw jest identyczny (raport sukcesu bez pokrycia).
Podział na dwie klasy dałby dwie naprawy tam, gdzie potrzebna jest jedna reguła:
**audyt i odpowiedź zapisują SKUTEK, nie ZAMIAR.**

### 1.2 · WordPress Z2: retencja meldująca sukces bez pokrycia → **moja KLASA 4**

`admin/15-retention-cron.php:32-53`: wynik `wp_delete_post()` nie jest odbierany,
a `deleted_count` liczy posty **dopasowane**, nie **usunięte**.

To jest **dosłownie moja lekcja „selekcja ≠ wykonanie"**, dla której napisałem
`RetencjaWykonanieTest` („rekord po terminie NAPRAWDĘ znika z bazy, nie tylko trafia na listę").
Raport sam to zauważa. Mechanizm ten sam co 1.1 — dlatego ląduje w tej samej klasie, a nie
w osobnej.

**Rzecz, którą warto zapisać osobno:** ich wsad to 200 postów na dobę. To znaczy, że przy
zaległym wolumenie **dane osobowe zostają po terminie retencji nawet przy poprawnie działającym
kasowaniu** — i tego mój `RetencjaWykonanieTest` też by nie złapał, bo mierzy jeden przebieg,
a nie **czy przebieg nadąża**. To jest trzecia połowa tej klasy, o której nie pomyślałem.

### 1.3 · Czat: `RpInitiatedLogout` — legalne nieodróżnialne od patologicznego → **moja KLASA 3**

`RpInitiatedLogout.php:56-62`: brak ciasteczka `id_token_hint` → `redirect('/')` bez sygnału.
Sesja lokalna ginie, **sesja u dostawcy tożsamości zostaje**, użytkownik widzi normalne
wylogowanie. Raport nazywa to wprost: przypadek legalny (sesja z fallbacku hasłowego)
jest **nieodróżnialny** od patologicznego (ciasteczko przepadło przez SameSite).

**To jest definicja mojej klasy 3** — jedna wartość zgodna z więcej niż jednym światem, bez
odczytu bazowego i bez zawężenia przyczyny.

**Czy moja naprawa je zamyka: NIE — i to odsłania granicę, której w moim dokumencie nie
postawiłem.** Moja naprawa klasy 3 brzmi: „werdyktu bez pre-flightu i gałęzi bazowej nie da
się wyprodukować" — i dotyczy **mojego harnessu pomiarowego**. Czat ma ten sam mechanizm
**w kodzie produkcyjnym**, gdzie żaden pre-flight nie pomoże; tam lekarstwem jest rozróżnienie
dwóch przypadków w samym kodzie (osobna ścieżka dla sesji hasłowej) albo sygnał.

**Wybieram wersję: jedna klasa o dwóch dziedzinach (pomiar / produkt), moja naprawa pokrywa
tylko dziedzinę pomiaru.** Nie dzielę na dwie klasy, bo mechanizm rozpoznaje się identycznie
(pytanie „jakie światy dają tę wartość") — ale **muszę dopisać, że naprawa nie przenosi się
między dziedzinami**, bo inaczej ktoś przeczyta moją klasę 3 i uzna, że produkt jest
zabezpieczony harnessem.

### 1.4 · Czat: test certyfikujący wejście, którego kontroler nie obsługuje → **KLASA 3 + 6**

`LogoutTokenVerifierTest.php:79-90` — `test_accepts_a_token_with_only_sid_and_no_sub`
**certyfikuje jako poprawne** wejście, po którym kontroler nie robi nic. Test jest zielony
i prawdziwy w swoim zakresie (weryfikator faktycznie ma przyjąć taki token), a mimo to
**buduje fałszywe przekonanie o systemie**.

To jest mój `R6A-1` co do joty: test „POZYTYWNY … logout REALNIE zabija sesję" przechodził,
gdy usunięto kasowanie sesji. Plus klasa 6, bo docblock weryfikatora **deklaruje cel**
(*„to know which session(s) to kill"*), którego kod nie realizuje — twierdzenie bez egzekutora.

### 1.5 · WordPress: opakowanie AJAX z trybem awarii „przepuść" → **moja KLASA 5, i naprawa PRZENOSI SIĘ**

`api/0-ajax-endpoint-wrapper.php:70` — gdy `auth_callback` jest podany, ale **niewywoływalny**
(literówka, zmiana nazwy), strażnik jest po cichu **pomijany**.

Na pierwszy rzut oka to nie jest denylista, więc nie moja klasa 5. **Ale mechanizm jest ten
sam i to jest najciekawsze dopasowanie w całym zestawieniu:** denylista działa wg reguły
**„czego nie ma na liście zakazanych, jest dozwolone"**, a to opakowanie wg reguły
**„czego nie da się sprawdzić, jest dozwolone"**. W obu **nieznane → przepuść**.

Moja klasa 5 mówiła o *postaci listy*; po tej lekturze widzę, że jej mechanizmem jest
**domyślna odpowiedź na NIEZNANE**. I dlatego **moja naprawa przenosi się w całości**:
*„allowlista z jawną odmową przy nieznanym; «nieznane» jest stanem odmowy, nie przepuszczenia"*
zamyka i denylistę prymitywów, i niewywoływalny `auth_callback`.

Raport sam pisze: *„Kontrakt jest jedną refaktoryzacją od D1"*. Zgadzam się i dodaję: **jest
też jedną refaktoryzacją od mojego R6A-4**, gdzie „nieznany prymityw" przeszedł tą samą drogą.

### 1.6 · Czat: brak warstwy znacznika unieważnienia → **instancja R6A-11, klasa ekosystemowa**

Czat nie ma `mark()` przed kasowaniem, więc sesja ze zrotowanym identyfikatorem przeżywa
wylogowanie — **BLK-22, ten sam, który zamykałem u siebie**.

To **nie jest** moja klasa 4, bo klasa 4 zakłada, że mechanizm istnieje i zawodzi po cichu.
Tu mechanizmu **nie ma wcale** — czyli to jest ta pozycja, którą w swoim dokumencie oznaczyłem
jako „instancja u mnie (R6A-11), ale **klasa w skali ekosystemu**": *mechanizm zadeklarowany
albo wymagany, niepodpięty do niczego, co go uruchamia.* U mnie retencja bez wywołującego,
u helpdesku W-17, u czatu brak warstwy znacznika. **Trzeci egzemplarz — teza się broni.**

---

## 2. Znaleziska, które NIE SĄ moje — mówię to wprost, bo to też wynik

**2.1 · WordPress Z3 — eksport CSV za `edit_posts`, gdy CPT wymaga `manage_options`.**
Odmowa **działa**; problem polega na tym, że **druga ścieżka do tego samego zasobu ma słabszy
warunek** niż model uprawnień samego zasobu. To nie jest cicha awaria ani zdegenerowany pomiar
— to niespójność autoryzacji. **Należy do rodziny helpdeskowego W-19** (ścieżka raportowa bez
filtra grup), nie do żadnej z moich siedmiu. Zwracam uwagę, że raport uczciwie oddziela wagę
od osiągalności („czy istnieją konta Contributor/Author — nie da się ustalić z odczytu kodu"),
czyli stosuje rozdział, który przyjęliśmy dziś jako wymóg rejestrowy.

**2.2 · WordPress Z1 — WordPress jako drugie źródło tożsamości.**
To **stan architektury**, znany i opisany, a nie mechanizm cichej awarii. Nie jest moją klasą.
Odnotowuję jednak zestawienie, które widać dopiero z trzech repozytoriów naraz: **konta nie
mają żadnej kontroli** zakazu własnych haseł, **ja mam słabą** (denylista, R6A-4),
**a WordPress ma jawnie działający mechanizm hasłowy**. To trzy różne stany i nie wolno ich
zlewać — pierwszy to brak przyrządu, drugi to zepsuty przyrząd, trzeci to świadomy wyjątek
do domknięcia przy migracji.

**2.3 · `.env.example` z nierozwiązanym konfliktem scalania, dwa adresy strony logowania.**
Instancje, naprawa punktowa. Nie tworzą klasy i nie są moje.

---

## 3. Czego u siebie NIE WIDZIAŁEM — i to jest najważniejsza część tej odpowiedzi

### KLASY „DZIAŁANIE ZA SZEROKIE" NIE MAM W OGÓLE

Czat kasuje sesje **po `sub`**, czyli **wszystkie sesje osoby**, zamiast po `sid` — jednej.
`sid` jest przyjmowany przez weryfikator i **nigdzie nie odczytywany**. Kontrakt Kont mówi:
*skasuj sesje o tym `sid`, a gdy `sid` nie ma — wszystkie sesje `sub`*. Czat robi odwrotnie.

**Wszystkie siedem moich klas opisuje niedziałanie**: cichy brak, fałszywe zielone, wynik
zgodny z wieloma światami, przepuszczenie nieznanego. **Ani jedna nie opisuje działania
SZERSZEGO niż uzasadnione.** To nie jest luka w materiale — to luka w mojej taksonomii,
i sam bym jej nie zobaczył, bo u siebie egzemplarza nie mam (`RejestrSesji::zakoncz($sid)`
działa po `sid`; nigdzie nie kasuję po `sub`).

Mechanizm tej klasy nazwałbym: **operacja bezpieczeństwa wykonana w zakresie szerszym niż
wynikający z wejścia, bez sygnału o rzeczywistym zakresie.** Jest bliźniaczo podobna do klasy 4
w tym, że **wynik nie jest raportowany** — użytkownik czatu wylogowany ze wszystkich urządzeń
nie dostaje informacji, że stało się coś więcej, niż prosił.

Druga rzecz, której nie miałem: **walidacja dopuszcza wejście, którego wykonawca nie obsługuje.**
Weryfikator czatu **świadomie** przyjmuje token z samym `sid` (i ma na to zielony test), a
kontroler rozgałęzia się wyłącznie na `sub`. Rozjazd między tym, co wolno wpuścić, a tym, co
umiemy obsłużyć, jest u nich **udokumentowany po obu stronach** i mimo to otwarty. U siebie
nie mam takiego przypadku, ale nie mam też **żadnej kontroli, która by go wykryła**.

---

## 4. Czy zmieniłbym coś w `KLASY-I-NAPRAWY.md` — TAK, cztery rzeczy. NIE ZMIENIAM, opisuję

1. **KLASA 4 — rozszerzyć nazwę i naprawę.** Dziś mówi o „zapisie, którego los nie jest
   rozstrzygnięty" i naprawia **magazyn**. Powinna mówić o **losie OPERACJI**, a naprawa
   dostać drugą połowę: **wynik operacji musi być odebrany i wejść do werdyktu; audyt
   i odpowiedź zapisują SKUTEK, nie ZAMIAR.** Uzasadnienie: czat (odrzucony wynik `->delete()`)
   i WordPress (`deleted_count` liczy dopasowane) to ta sama klasa, a moja naprawa ich nie tyka.
   Do tego trzecia połowa z WordPressa: **kontrola „czy przebieg nadąża"** — wsad 200/dobę
   zostawia dane po terminie nawet przy sprawnym kasowaniu.
2. **KLASA 3 — dopisać, że ma dwie dziedziny i że naprawa NIE przenosi się między nimi.**
   Moja naprawa dotyczy harnessu pomiarowego; ten sam mechanizm w kodzie produkcyjnym
   (czat, `RpInitiatedLogout`) wymaga rozróżnienia przypadków w samym kodzie. Bez tego zdania
   ktoś przeczyta klasę 3 i uzna produkt za zabezpieczony.
3. **KLASA 5 — przeformułować mechanizm z „postaci listy" na „domyślną odpowiedź na NIEZNANE".**
   Wtedy obejmuje denylistę prymitywów **i** niewywoływalny `auth_callback`, a naprawa
   (allowlista z odmową przy nieznanym) przenosi się bez zmian. To jest **zawężenie opisu
   i rozszerzenie zasięgu naraz** — czyli dokładnie to, po co robi się taksonomię.
4. **DODAĆ KLASĘ 8: działanie za szerokie bez sygnału o zakresie** — z zerem członków u mnie
   i jawnym zapisem, że **nie mam egzemplarza**, a klasa wchodzi do dokumentu wyłącznie
   dlatego, że cudzy materiał odsłonił dziurę w mojej taksonomii. Wpisanie klasy pustej jest
   uczciwsze niż udawanie, że siedem kategorii wyczerpuje przestrzeń.

**Czego bym NIE zmieniał:** przypisania klas 1, 2, 6 i 7 — żadne znalezisko z tych dwóch
raportów ich nie dotyczy ani nie podważa.

---

## Zakazy i granice

Do `chat/` ani `Niepodzielni-dev/` **nie wchodziłem** · niczego nie uruchamiałem · nie
proponuję napraw w tamtych repozytoriach · kodu nie tknąłem · `KLASY-I-NAPRAWY.md`
**nie zmieniłem** (zgodnie ze zleceniem — opisałem tylko, co bym zmienił) · `main` nietknięty ·
zero merge, deploy, nic poza fundację. **Sprzeczności ze zleceniem: brak.**

**Granica tej analizy:** opieram się w całości na cudzych raportach i **nie weryfikowałem ich
pomiarów**. Oba raporty same podają swoje granice (czat: nie zmierzono, czy realm emituje
`logout_token` bez `sub` — to różnica między defektem utajonym a czynnym; WordPress: nie
czytano `workers/ai-agent/` ani stanu bazy). **Moje dopasowania dziedziczą te granice:**
jeśli któryś pomiar u nich jest błędny, moja klasyfikacja tego egzemplarza upada razem z nim.
