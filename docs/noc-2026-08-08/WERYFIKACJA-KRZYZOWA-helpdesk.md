# Weryfikacja krzyżowa — znaleziska zespołu helpdesku (runda domykająca F1)

**Kto:** wykonawca Gabinetu, po własnej nocy. Patrzę na cudzą pracę **bez konfliktu autorstwa** —
nie pisałem ani ich kontroli, ani ich znalezisk. Postawa adwersarialna: zadaniem jest OBALIĆ.
**Niczego u nich nie naprawiam i niczego nie zapisuję w ich repozytorium.**

**Data:** noc 8→9 sierpnia 2026, po 02:40.
**Przedmiot:** `D:\KOD\Niepodzielni\helpdesk\docs\noc-2026-08-08\` — 11 znalezisk, 3 blokujące.

---

## Metoda i jej granice — czytaj to przed werdyktami

**Czego NIE zrobiłem:** **nie postawiłem ich stosu.** Zammad z Elasticsearchem to kosztowna
instalacja, a zlecenie mówi wprost, żeby nie stawiać go bez potrzeby. Nie wywołałem `docker stop`,
`rm`, `prune` ani `down` — na żadnym kontenerze.

> **Uściślenie, bo pierwsza wersja tego zdania była nieostrożna.** Napisałem najpierw „44 cudze
> kontenery działają, tyle samo co przed moją pracą". Sprawdzone na koniec: działa **39**, w tym
> **10 z prefiksem `helpdesk`**. Liczba spadła, bo inne zespoły kończyły w tym czasie własne nocne
> przebiegi i sprzątały po sobie — **nie dlatego, że coś zatrzymałem**. „Tyle samo" było wnioskiem
> z oczekiwania, nie z odczytu.

**Co zrobiłem:** analizę statyczną ich repozytorium **tylko do odczytu** — kod kontroli
(`config/f1-e2e.rb`, `config/f1-zammad.rb`), skrypty bramek, dokumentacja (`KONFIGURACJA.md`,
`CLAUDE.md`) — oraz odtworzenie ich własnych zapytań `grep` w ich drzewie. Do porównania wzorca C1
przeczytałem własny `RetencjaWykonanieTest`.

**Konsekwencja dla wagi moich zdań:** wszędzie, gdzie piszę „zmierzone", chodzi o **odczyt kodu
albo `grep` w ich repozytorium**, nie o przebieg ich bramki. Tam, gdzie mój zarzut wymagałby
uruchomienia, mówię to wprost i **nie podaję wniosku jako pomiaru**. To jest ta sama dyscyplina,
którą ich weryfikator stosował wobec nich.

---

## Werdykty — skrót

| # | Znalezisko | Werdykt | Jednym zdaniem |
|---|---|---|---|
| **W-17** | retencja stoi na zadaniu cyklicznym, o które nikt nie pyta | **POTWIERDZONE + ZŁA DIAGNOZA + ESKALACJA** | fakt bezsporny, ale przyczyna nazwana wąsko: to nie „brak kontroli o Scheduler", tylko **R6 wykonuje sama trzy ogniwa, które ma obserwować** — a punktów awarii jest co najmniej trzy, nie jeden |
| **W-18** | audyt kasuje własny dowód razem z ofiarą | **POTWIERDZONE + ESKALACJA (cięższe)** | to nie jest utrata dowodu, tylko **pozytywny certyfikat integralności wystawiany dokładnie wtedy, gdy atak się powiódł** |
| **W-19** | ścieżka raportowa bez filtra grup | **POTWIERDZONE co do mechanizmu · ZŁA WAGA w RAPORCIE (za ciężka)** | mechanizm realny i dobrze zmierzony, ale **raport pomija warunek osiągalności**, który ich własna `KONFIGURACJA.md` podaje uczciwie |
| **C1 / sprzątanie sierot** | przeniesienie mojego wzorca | **NAPRAWA CHYBIONA** | zasadę przenieśli poprawnie, **mechanizm perturbacji przepisali błędnie** — ich wariant bada łatwiejszą awarię niż mój |
| **Z-06** | uczciwy negatyw: nazwy nadawcy | **ODCZYT NICZEGO NIE DOWODZI** | pyta tabelę, którą sam konfigurator zapisał — trzeci kształt C1, ich własną miarą |
| **Z-07** | uczciwy negatyw: dziedziczenie po grupie | **ZŁA WAGA (za mocne zdanie)** | zakres podany wzorowo, ale **nagłówek twierdzi więcej, niż zakres pozwala** |

Do tego jedna obserwacja przekrojowa, na końcu: **ich dokumentacja trwała jest uczciwiejsza niż
ich raport z rundy.** To rzadki i dobry kierunek — ale znaczy, że dokumenty nagłówkowe przesadzają.

---

## W-17 — POTWIERDZONE, ale diagnoza jest węższa niż defekt

### Co potwierdzam bez zastrzeżeń

Ich `grep` był kompletny co do zakresu — sprawdziłem, że **cała powierzchnia kontroli** to
dokładnie te dwa katalogi:

```
$ ls config/   → f1-e2e.rb, f1-zammad.rb
$ ls scripts/  → bramka-f0.sh, bramka-f1.sh, konfiguruj-f1.sh, perturbacje-f0.sh,
                 perturbacje-f1.sh, weryfikacja-postaw.sh, weryfikacja-sprzataj.sh, …
$ grep -rn "Scheduler" config/ scripts/     → (brak wyniku)
```

Poszedłem dalej niż oni — sprawdziłem, czy któraś kontrola pyta o ten mechanizm **nie nazywając go
„Scheduler"**:

```
DataPrivacyTaskJob   0        last_run       0        Delayed::Job   0
in_process           0        Job.run        1   ← i to jest KOMENTARZ, nie wywołanie
```

Jedyne wystąpienie `Job#run` w całej powierzchni kontroli to komentarz w `config/f1-e2e.rb:904`:
`# Selekcja zadania — ta sama droga, którą idzie Job#run.` **Równoważność z drogą produkcyjną jest
tam ZADEKLAROWANA, nie sprawdzona.** To dokładnie klasa „komentarz obiecuje więcej, niż kod robi",
przed którą ostrzega ich własne `WYTYCZNE-PRACY` i mój `ObietniceKomentarzyTest`.

**Fakt W-17 stoi.** Nie udało mi się go obalić i nie znalazłem drogi, którą mógłby paść.

### Gdzie diagnoza mija się z przyczyną

Ich zdanie brzmi: *„cała retencja RODO opiera się na mechanizmie, o którym żadna kontrola nie pyta,
czy w ogóle chodzi"* — i wskazuje `Scheduler` 25. Przeczytałem R6 w całości
(`config/f1-e2e.rb:896–945`). **R6 nie tyle „nie pyta o Scheduler 25", co podstawia SIEBIE w miejsce
trzech kolejnych ogniw drogi produkcyjnej:**

```ruby
904  # Selekcja zadania — ta sama droga, którą idzie `Job#run`.
905  _, wybrane = Ticket.selectors(job_pomoc.condition, …, access: 'ignore')   ← ogniwo 1: zamiast Scheduler 5
909  stara.perform_changes(job_pomoc, 'job') if wybrane_ids.include?(stara.id) ← ogniwo 2: zamiast akcji zadania
914  zadanie.perform                                                          ← ogniwo 3: zamiast Scheduler 25
```

To jest **kontrola, która wykonuje czynność, którą ma obserwować** — rodzina C1, ta sama, którą ich
runda słusznie zarzuciła R4 i J1. Nazwanie przyczyny jako „brak kontroli o Scheduler" prowadzi do
naprawy, która **nie zamyka klasy**: dołożenie asercji „Scheduler 25 aktywny, `last_run` świeży"
zostawia R6 dalej wykonującą ogniwa 1 i 2 samodzielnie.

### ESKALACJA — punktów awarii jest co najmniej trzy, nie jeden

Wyprowadzone **z odczytu kodu**, nie z przebiegu na ich stosie (nie stawiałem go):

| ogniwo | co je napędza produkcyjnie | co robi R6 | czy wyłączenie zostawia bramkę zieloną |
|---|---|---|---|
| selekcja zgłoszeń | `Scheduler 5` → `Job.run` (300 s) | woła `Ticket.selectors` sama | **tak** — R6 w ogóle nie dotyka `Job.run` |
| zastosowanie akcji zadania | ta sama droga | woła `perform_changes` sama | **tak** |
| wykonanie usunięcia | `Scheduler 25` → `DataPrivacyTaskJob` (600 s) | woła `zadanie.perform` sama | **tak — to zmierzyli** |
| proces `scheduler` jako taki | jeden proces dla wszystkich | nie dotyczy | **tak** — martwy proces zatrzymuje wszystko |

Zmierzyli **jeden** z tych czterech. Ich wniosek „bramka 24/24 zielona przy niedziałającej retencji"
jest więc **prawdziwy dla szerszej klasy stanów, niż pokazali** — a to znaczy, że kontrola
pilnująca wyłącznie `Scheduler 25` zostawiłaby trzy pozostałe drogi otwarte.

**Poprawka architekta, którą przyjęli** (`last_run` świeższy niż odstęp, zamiast samego `active`),
jest słuszna i akurat **łapie martwy proces** — to jedyne z czterech ogniw, które ich poprawiona
propozycja domyka poza Schedulerem 25. Warto, żeby wiedzieli, że domyka je przypadkiem, a nie
z konstrukcji.

**Czego moja analiza NIE rozstrzyga:** nie uruchomiłem ich bramki z wyłączonym `Scheduler 5`, więc
wiersz pierwszy i drugi tabeli to **wniosek z lektury kodu**. Rozstrzygnięcie kosztuje u nich jeden
przebieg i jedną mutację — dokładnie tę, którą już umieją zrobić.

---

## W-18 — POTWIERDZONE i moim zdaniem OPISANE ZBYT ŁAGODNIE

Mechanizm potwierdzam. Przeczytałem R4 (`config/f1-e2e.rb:992–1023`): audyt stoi na tabeli
`History` powiązanej z `o_id` zgłoszenia, więc **żyje i umiera razem ze zgłoszeniem**. Nic tego
nie obali — to własność konstrukcji, nie usterka implementacji.

**Jedno uściślenie precyzyjne, na ich korzyść i przeciw ich własnemu sformułowaniu.** Piszą
„drugim kasownikiem jest sprzątanie samej bramki" (`f1-e2e.rb:1257–1261`, `DELETE FROM histories …
NOT EXISTS`). Ich własny pomiar pokazuje jednak, że dowód znika **natychmiast po `Ticket#destroy`**,
zanim sprzątanie bramki w ogóle się uruchomi — czyli **pierwszym i wystarczającym kasownikiem jest
produkt**, a sprzątanie bramki jest drogą wtórną i w tym scenariuszu bezczynną. Nie zmienia to
werdyktu, ale zmienia adresata naprawy: usunięcie SQL-a ze sprzątaczki niczego by nie uratowało.

### Dlaczego eskaluję zamiast obalać

„Audyt kasuje własny dowód" brzmi jak utrata informacji. Przeczytałem gałąź sukcesu R4:

```
ok 'R4', "znacznik pochodzenia nietykalny: blokada formularza aktywna (set_readonly),
          detektor UDOWODNIŁ w tym przebiegu, że widzi podmianę (na własnej próbce),
          zero zmian z wartości niepustej w całej bazie"
```

Po udanym ataku ta kontrola nie milczy i nie melduje „brak danych". **Wystawia POZYTYWNY certyfikat
integralności — „znacznik pochodzenia nietykalny".** Im skuteczniejszy atak (zgłoszenie faktycznie
skasowane skróconą retencją), tym **czystszy** wynik audytu. Wykrywalność jest odwrotnie
skorelowana ze szkodą.

To jest mocniejszy zarzut niż ich własny i wart osobnego zdania w rejestrze: kontrola nie tylko
traci dowód, ale **zamienia jego brak w potwierdzenie**. Ich sformułowanie („kasuje własny dowód")
czytelnik może odebrać jako „nie dowiemy się" — a prawda brzmi „dowiemy się rzeczy nieprawdziwej".

Waga „wysoka, blokująca" jest w moim odczycie **uzasadniona i raczej zaniżona niż zawyżona.**

---

## W-19 — mechanizm POTWIERDZONY, waga w RAPORCIE ZAWYŻONA

### Co potwierdzam

Ścieżka i dowód są dobrze zmierzone; nie mam czym ich podważyć bez ich stosu. Uderzyła mnie
natomiast rzecz, której szukałem jako pierwszej i która **wzmacnia** ich zarzut: sprawdziłem, czy
u nich w ogóle istnieje wymóg, żeby administrator nie widział Grantów — bo w typowym modelu
uprawnień admin widzi wszystko i zarzut byłby bezprzedmiotowy. **Wymóg istnieje i jest zapisany
wprost** (`docs/KONFIGURACJA.md:37`): *„Administrator nie ma dostępu do kolejek — konto
administracyjne ma wyłącznie rolę Admin, bez roli agenckiej"*. Zarzut trafia więc w zadeklarowaną
własność, nie w wyobrażenie weryfikatora. **Ta próba obalenia jest NIEUDANA i zapisuję to jako
wynik.**

### Gdzie waga jest zawyżona — brakujący warunek osiągalności

`RAPORT-RUNDY.md` (N-03) i `ZNALEZISKA.md` (tabela Z-08) opisują to jako
*„admin **i agent bez roli Granty** dostają `ticket_ids` z Grantami i pełny tytuł"* — bez jednego
zdania o tym, **kto może w ogóle uruchomić raport**. Sprawdziłem w ich konfiguratorze:

```
$ grep -rn "report" config/f1-zammad.rb config/f1-e2e.rb   → (nic; konfigurator nie nadaje `report` NIKOMU)
$ grep -n "Role.find_by\|Role.create" config/f1-zammad.rb  → używa wbudowanych ról Admin i Agent
```

A ich **własna dokumentacja trwała** mówi to, czego raport nie mówi (`KONFIGURACJA.md`):

> **Dziś uprawnienie `report` ma wyłącznie ono** [konto awaryjne], **więc osiągalność jest wąska.**
> Ale nadanie `report` koordynatorowi „po statystyki" — rzecz zupełnie naturalna — otwiera odczyt
> wniosków grantowych.

**To jest właściwe postawienie sprawy i raport go nie zawiera.** Konsekwencje dla wagi:

1. **Wątek „zwykły agent" opiera się na niewypowiedzianym założeniu.** Raport nie podaje, czy
   `weryf-agent` miał uprawnienie `report`, ani czy wywołanie szło przez `ReportsController`
   (który sprawdza `report`), czy prosto do `SearchIndexBackend.selectors`. **Bez tego zdania
   scenariusz agencki nie jest wykazany jako osiągalny** — wykazana jest własność warstwy
   selektora, która dotyczy wyłącznie tego, kto do niej dosięgnie. Dziś: nikt poza kontem
   awaryjnym.
2. **Wątek „admin" jest realny, ale nie jest nowy.** Ta sama sekcja `KONFIGURACJA.md` wymienia
   **dwie już przyjęte** drogi obejścia dla `admin`: skasowanie zgłoszenia w dowolnej kolejce
   (`DELETE /api/v1/tickets/:id` → 200) i odczyt przez podszycie (`X-On-Behalf-Of`), obie opisane
   jako niewyłączalne. W-19 dokłada **trzecią drogę do konta, które już miało dwie** — istotne dla
   ścisłości zapisu, mało zmieniające faktyczne ryzyko.

**Werdykt: ZŁA WAGA (za ciężka) w `RAPORT-RUNDY.md` i `ZNALEZISKA.md`.** Nie kwestionuję statusu
blokującego — blokada jest uzasadniona **innym** argumentem, który oni sami podają najmocniej:
*„Żadna kontrola bramki tego nie zobaczy, bo wszystkie pytają `TicketPolicy`, a raport jej nie
używa"*. Zadeklarowana własność bezpieczeństwa z zerowym pokryciem kontrolnym to dobry powód, żeby
nie zamykać fazy. Zły powód to sugestia, że dziś dowolny agent czyta wnioski grantowe.

**Rekomendacja (nie naprawiam):** przenieść akapit o osiągalności z `KONFIGURACJA.md` do opisu
znaleziska i dopisać jedno zdanie: czy testowy agent miał `report` i którą warstwę wywołano.

---

## Wzorzec C1 przy sprzątaniu sierot — przenieśli zasadę, przepisali mechanizm BŁĘDNIE

To była pozycja wskazana mi imiennie, bo wzorzec pochodzi z mojego repozytorium. Przeczytałem
swój `RetencjaWykonanieTest` jeszcze raz, żeby nie odpowiadać z pamięci.

### Co przenieśli DOBRZE

Ich sformułowanie w `DZIENNIK.md` jest poprawne i lepsze niż moje własne:

> C1 zakazuje dzielenia **MECHANIZMU** z przedmiotem, nie wytworzenia materiału. Gdy producent,
> wykonawca i obserwator idą **trzema różnymi ścieżkami** […] kontrola jest falsyfikowalna.

Zgadza się z tym, co robi mój test: producent to `DB::table('zgody')->insertGetId()`, wykonawca to
`ZadanieRetencji->wykonaj()`, obserwator to osobne `DB::table('zgody')->where('id',…)->exists()`.
Trzy różne drogi. **Ich wycofanie się z własnej przeszkody („to nieuchronnie C1") było słuszne.**

### Co przepisali ŹLE — i to zmienia moc kontroli

Piszą, że perturbacja ma działać *„na poziomie, o którym badane zadanie nic nie wie
(**odebranie `DELETE` w PostgreSQL**)"*. **Mój test tego nie robi.** Robi coś istotnie innego:

```php
DB::statement('CREATE RULE zgody_bez_kasowania AS ON DELETE TO zgody DO INSTEAD NOTHING');
```

Różnica nie jest kosmetyczna, jest sednem:

| wariant | co widzi zadanie | jaką awarię bada |
|---|---|---|
| **odebranie uprawnienia `DELETE`** (ich zapis) | **wyjątek** — `permission denied` | awarię GŁOŚNĄ: zadanie wywala się samo |
| **`RULE … DO INSTEAD NOTHING`** (mój kod) | `DELETE` **kończy się sukcesem**, `0` wierszy | awarię CICHĄ: zadanie melduje komplet, rekord został |

Cały sens tej kontroli leży w drugim wierszu. Nagłówek mojego testu mówi to wprost: liczymy
**wiersze w bazie**, nie wartość zwróconą przez `delete()`, bo *„wyzwalacz, reguła albo wycofana
transakcja potrafią ją uczynić nieprawdziwą — i dokładnie tego szukamy"*. Perturbacja odbierająca
uprawnienie **nie odtwarza tego zjawiska**: zadanie rzuca wyjątek, a kontrola, która łapie wyjątek,
przechodzi z powodu awarii głośnej i **nadal nie dowodzi, że umie wykryć ciche niewykonanie**.

**Werdykt: NAPRAWA CHYBIONA.** Jeśli zbudują perturbację wg zapisu z dziennika, dostaną kontrolę
słabszą niż wzorzec, na który się powołują — i o tej różnicy nie dowie się nikt, bo obie wersje
świecą zielono w przebiegu poprawnym.

### Trzy rzeczy, o których muszą wiedzieć, zanim skopiują mój wzorzec

1. **Egzemplarz, który cytują, wisi na mechanizmie NIEPODPIĘTYM.** Moja własna runda 6 z tej nocy
   (znalezisko R6A-11) zmierzyła, że `ZadanieRetencji` **nie ma ani jednego wywołującego** w kodzie
   produkcyjnym; `routes/console.php` ma jedno zadanie i nie jest nim retencja. Mój test dowodzi
   więc, że **biblioteka kasuje** — i nic ponadto. Kopiując go do zadania cyklicznego bez asercji,
   że zadanie jest zaplanowane i chodzi, **odtworzą u siebie dokładnie W-17**. Częściowo to
   zauważyli (punkt 2 ich uzasadnienia); ale punkt 3 nadal mówi „wzorzec sprawdzony u gabinetu",
   bez tego zastrzeżenia. **Sprawdzony jest dowód kasowania, nie dowód uruchamiania.**
2. **Trzeci przypadek mojego testu jest równie ważny jak perturbacja** i łatwo go pominąć przy
   przepisywaniu: *„nie melduje sukcesu przy pustym przebiegu — zero wybranych to zero, nie
   »gotowe«"*. Bez niego kontrola przechodzi na pustym materiale — czyli ich własne N-04
   („pusty stóg siana"), tylko w retencji.
3. **Sprzątanie perturbacji jest u nich trudniejsze niż u mnie.** Mój `CREATE RULE` żyje w suicie
   biegnącej w jednej transakcji (`RefreshDatabase`) i jest zdejmowany w `finally`. Ich bramka
   działa na żywej instancji bez takiej klamry — reguła zostawiona w bazie zablokuje kasowanie
   **wszystkim następnym przebiegom**, a objawem będzie „retencja przestała działać". To jest
   pułapka, w którą sam bym wpadł, gdybym przenosił ten kod w drugą stronę.

---

## Z-06 i Z-07 — „uczciwe negatywy", w których odczyt niczego nie dowodzi

Zlecenie kazało mi spojrzeć tu szczególnie. Słusznie, bo to najsłabsze miejsce ich rundy — i o tyle
podstępne, że **oba są opisane jako wyniki**, a ich forma jest mocniejsza niż treść.

### Z-06 — pyta tabelę, którą sam konfigurator zapisał

Wynik brzmi „nazwy nadawcy są ustawione poprawnie", a dowodem są trzy wiersze `EmailAddress` z pola
`name`. **To jest odczyt tej samej tabeli, do której zapisuje ich własny konfigurator** — trzeci
kształt reguły C1 wg ich własnej klasyfikacji („kontrola pyta o stan, który sama produkuje"),
ten sam, którym w Z-03 słusznie obalili kontrole E1/E2.

Miarą jest tu ich własne zdanie z Z-05, o dwie pozycje wyżej w tym samym pliku:

> kontrola SKUTKU — **odpowiedź odebrana ze skrzynki zgłaszającego** zawiera nazwę fundacji
> i **nie** zawiera `example.com`. Kontrola pytająca „czy grupa ma podpis" tego nie złapie.

Dokładnie to zastrzeżenie unieważnia Z-06: „czy `EmailAddress.name` ma dobrą wartość" nie jest
pytaniem o to, **co adresat zobaczy w polu Od**. Między jednym a drugim jest renderowanie nagłówka,
wybór adresu przez kolejkę i ustawienia produktu. Sami wpisali to do braków jako B-04 („nikt nie
pyta, co adresat dostaje POD odpowiedzią") — i nie zauważyli, że to samo dotyczy tego, co dostaje
**NAD** odpowiedzią.

**Werdykt: ODCZYT NICZEGO NIE DOWODZI.** Nie twierdzę, że nazwy są złe — twierdzę, że Z-06 tego nie
rozstrzyga, a zapisany jest jako rozstrzygnięcie („zdejmuje podejrzenie z konkretnego elementu").
Wartość dowodowa: zerowa dla tezy postawionej, dodatnia tylko dla tezy „pole w bazie ma wartość".

### Z-07 — zakres podany wzorowo, nagłówek mówi więcej niż zakres

Tu należy się pochwała: **podali zakres pomiaru wprost** („obejmuje atrybuty modelu `Group`
i nic poza tym — wyzwalacze, makra, przeglądy, szablony nie były objęte"). Tego brakuje w dziewięciu
raportach na dziesięć.

Problem jest w zdaniu, które stoi **przed** tym zastrzeżeniem i jest wytłuszczone:

> **Jedyną nieodziedziczoną rzeczą jest podpis.**

Z pomiaru wynika co innego: *jedyną nieodziedziczoną rzeczą **wśród atrybutów modelu Group** jest
podpis*. Różnica jest istotna, bo pytanie, które sami postawili w Z-04, brzmiało szerzej: *„co
jeszcze produkt skonfigurował na grupie domyślnej, czego nasze trzy kolejki nie odziedziczyły?"* —
a rzeczy przypisywane do kolejek **spoza modelu Group** (wyzwalacze, przeglądy, szablony, makra) to
właśnie ta klasa, w której siedział podpis z Miami. Odpowiedzieli na pytanie węższe niż zadane
i zapisali odpowiedź w formie pytania szerszego.

**Werdykt: ZŁA WAGA (zdanie za mocne wobec własnego zakresu).** Czytelnik, który zapamięta tylko
wytłuszczenie — a tak się czyta raporty — wyjdzie z przekonaniem, że temat dziedziczenia jest
zamknięty. Nie jest; jest zamknięty w jednej ósmej.

---

## Czego NIE sprawdziłem — jawnie

- **Nie postawiłem ich stosu i nie uruchomiłem ani jednej ich kontroli.** Wszystkie moje zarzuty
  wobec W-17 (tabela czterech ogniw) są wyprowadzone z **lektury kodu**. Rozstrzygnięcie wymaga
  u nich jednego przebiegu z wyłączonym `Scheduler 5`.
- **Nie sprawdziłem N-04 … N-11** poza tym, co wynikało z trzech pozycji priorytetowych. Ośmiu
  znalezisk nie tknąłem — nie wypowiadam się o nich w żadną stronę.
- **Nie zweryfikowałem ich pomiarów liczbowych** (24/24, liczby wierszy w C2) — nie mam ich
  przebiegu, a przepisywanie cudzych liczb nie jest weryfikacją.
- **Nie sprawdziłem, czy `weryf-agent` miał uprawnienie `report`** — tego nie da się odczytać
  z repozytorium, bo konto powstało w ich przebiegu. To jest pytanie do nich i główna niewiadoma
  w mojej ocenie wagi W-19.
- **Nie oceniałem BRAKI-KONTROLI.md, DO-DECYZJI.md ani OD-ARCHITEKTA.md** poza fragmentami
  potrzebnymi do trzech pozycji priorytetowych i wzorca C1.

---

## Jedna obserwacja przekrojowa, którą warto im przekazać

**Ich dokumentacja trwała jest uczciwiejsza niż ich raport z rundy.** `KONFIGURACJA.md` zawiera
warunek osiągalności W-19, sprostowanie fałszywego zdania o administratorze i wyliczenie trzech dróg
obejścia z przyznaniem, które są niewyłączalne. `RAPORT-RUNDY.md` i `ZNALEZISKA.md` — dokumenty,
które ktoś przeczyta jako pierwsze i na których oprze decyzję — tych warunków nie niosą.

Kierunek jest właściwy (dokument trwały jest ostrożniejszy niż notatka z nocy) i **nie jest to
zarzut o nieuczciwość** — to zarzut o kolejność czytania. Nagłówkowe „krytyczna, BLOKUJE, agent
czyta wnioski grantowe" trafia do czytelnika bez zdania „dziś to uprawnienie ma jedno konto".
U mnie tej nocy zadziałał ten sam mechanizm w drugą stronę: napisałem „na wypchniętej gałęzi",
zanim sprawdziłem, i musiałem prostować własny wpis. **Warunek pomiaru należy do zdania, które
niesie wynik — nie do dokumentu obok.**
