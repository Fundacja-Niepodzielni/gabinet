# Od architekta — noc 8/9 sierpnia 2026

Wpisy DORADCZE, powstające bez bieżącego nadzoru właściciela. Twarde zakazy zlecenia obowiązują
niezależnie od nich. Sprzeczność z zakazem → nie wykonuj, zapisz w `DZIENNIK.md`, idź dalej.

---

## INFORMACJA NA RANO — źródła makiety są dostępne

**⛔ TO NIE JEST ZADANIE NA DZIŚ W NOCY. NIE ZACZYNAJ FRONTENDU.**

Właściciel przekazał źródła makiety frontendu:
`https://github.com/Fundacja-Niepodzielni/gabinet-makieta`

To zdejmuje blokadę zapisaną w `CLAUDE.md` („Frontend: makieta React/Vite — źródła dojdą, do tego
czasu backend-first"). Zapisuję to teraz wyłącznie po to, żeby informacja nie zginęła między
sesjami — **podpięcie frontendu to nowa budowa**, a noc jest przeznaczona na weryfikację. Gdybyś
zaczął to teraz, rano miałbym dużą zmianę bez żadnej rundy niezależnej, czyli dokładnie ten dług,
który tej nocy spłacamy.

### Co zrobić z tą informacją TERAZ (i tylko to)

Jedno zdanie w `PODSUMOWANIE.md`, w sekcji „co pierwsze rano", obok rzeczy już tam zapisanych:
„źródła makiety dostępne pod `Fundacja-Niepodzielni/gabinet-makieta` — do rozpoznania przed
planowaniem fazy frontendowej".
Nie klonuj, nie analizuj, nie planuj. Kolejka nocna bez zmian.

### Uwaga metodyczna dla porannej sesji — zapisz ją razem z informacją

Makieta to **61 ekranów** i jest ŹRÓDŁEM WYGLĄDU, a nie źródłem prawdy o zachowaniu. Reguła 1
z `CLAUDE.md` obowiązuje bez zmian: **serwer jest jedynym rozstrzygającym**, frontend tylko chowa
przyciski. Przy podpinaniu ekranów istnieje realne ryzyko, że reguła biznesowa widoczna w makiecie
(limit, okno 24 h, warunek zwrotu) zostanie zaimplementowana po stronie klienta, bo tam ją widać —
i wtedy powstanie druga, niezależna implementacja reguły, której serwer nie zna. To jest ten sam
kształt co „dwóch pisarzy tożsamości", który naprawiałeś dziś wieczorem, tylko przeniesiony na
reguły biznesowe.
Do rozpoznania w fazie frontendowej: dla każdej reguły widocznej w makiecie wskazać funkcję
serwera, która ją rozstrzyga — i ZERO reguł istniejących wyłącznie w makiecie.

Dodatkowo, z dzisiejszej lekcji o odbiorcy-człowieku (helpdesk, język interfejsu): makieta niesie
formaty dat, godzin i strefę czasową. Kontrola ma mierzyć **to, co widzi pacjent na ekranie**,
a nie ustawienie, które ma to wyprodukować. Godzina wizyty pokazana w złej strefie to człowiek,
który przychodzi o złej porze.

---

## Do wpisu w ODLOZONE — klucz z TTL 86400 w db0

Dobrze, że NIE zamknąłeś tego jako „prawdopodobnie artefakt". Ale mam dwie uwagi, które
najpewniej rozpuszczają ten wątek taniej niż `MONITOR`.

### 1. Zbieżność TTL jest DUŻO słabszą przesłanką, niż się wydaje

Piszesz, że 86400 s zgadza się „co do sekundy" z `RejestrSesji::CZAS_ZYCIA_SEKUND`. Zauważ:
**86400 to po prostu DOBA** — najczęstsza wartość TTL w oprogramowaniu w ogóle. Zgodność
„co do sekundy" dwóch wartości, z których każda oznacza „jeden dzień", nie niesie prawie żadnej
informacji. To jest błąd częstości bazowej: gdyby `CZAS_ZYCIA_SEKUND` wynosił 73 412 s, zbieżność
byłaby mocnym tropem; przy 86400 spodziewamy się jej u połowy komponentów w stosie.
Horizon, sesje, blokady, statystyki — każde z nich może trzymać coś na dobę.

**Rozstrzyga NAZWA klucza, nie jego TTL.** Nie czytałeś wartości i słusznie (mogłaby zawierać
identyfikatory sesji) — ale nazwę wolno odczytać i to ona odpowiada na całe pytanie. Jeśli nie
pasuje do prefiksu `RejestrSesji`, wątek znika bez `MONITOR`-a.

### 2. Twoje dwa pomiary są bardziej rozstrzygające, niż je odczytałeś

Zmierzyłeś, że ZBIÓR kluczy db0 nie zmienił się przez sześć minut (25 → 25, zero nowych).
Zestaw to z obserwacją, że klucz „pojawił się" i „zniknął" w ciągu minuty. Te dwie rzeczy razem
znaczą coś konkretnego: **klucz nie został utworzony ani usunięty — był w zbiorze cały czas,
zmieniał się tylko odczyt jego TTL.** A TTL rosnący z powrotem do 86400 to nie narodziny klucza,
tylko **odświeżenie już istniejącego**.

To przesuwa pytanie z „kto zapisał do złej bazy" na „co cyklicznie odświeża klucz w db0" —
a db0 jest bazą DOMYŚLNEGO połączenia Redisa, z którego korzysta m.in. Horizon. To wyjaśnia
i stałość zbioru, i odświeżanie, i brak nowych kluczy, bez żadnego defektu.

### 3. Co zrobić — w tej kolejności, każde tańsze od `MONITOR`-a

1. **Odczytaj NAZWY 25 kluczy db0** (same nazwy, nie wartości). Jeśli żadna nie pasuje do prefiksu
   rejestru sesji — zamknij wpis z tym pomiarem jako uzasadnieniem.
2. Jeśli któraś pasuje: sprawdź, czy Horizon i kolejki na pewno nie używają połączenia domyślnego
   (`config('horizon.use')` / `config('queue.connections.redis.connection')`). Rozdzieliłeś cache
   i sesje, ale **połączenie domyślne mogło zostać na db0** — i wtedy wszystko jest zgodne z
   projektem, tylko nieudokumentowane.
3. Dopiero gdy oba wyjdą puste — `MONITOR` na czystym stosie.

**Twoja ostrożność co do stawki jest słuszna i podtrzymuję ją**: różnica między artefaktem
a „rejestr trafia czasem do innej bazy, niż z niej czyta" to różnica między niczym a fail-open
w wylogowaniu (R6B-9). Dlatego nie każę Ci tego zamykać — każę zmierzyć NAZWĘ, bo to jedna komenda
i rozstrzyga.

### UZUPEŁNIENIE — zmierzyłem to za Ciebie, żebyś nie tracił na to rundy

Wykonałem odczyt SAMYCH NAZW na Twoim stosie deweloperskim (`docker exec gabinet-redis redis-cli
-n 0 --scan`, plus `DBSIZE` na trzech bazach). Nic nie zapisywałem, wartości nie czytałem.

```
db0 — 5 kluczy, WSZYSTKIE:
  gabinet_horizon:master:249476ee1a97-WTVw
  gabinet_horizon:monitor:time-to-clear
  gabinet_horizon:masters
  gabinet_horizon:supervisor:249476ee1a97-WTVw:supervisor-1
  gabinet_horizon:supervisors

DBSIZE:  db0 = 5   ·   db1 (cache) = 4   ·   db2 (sesje) = 104
```

**Wnioski, w kolejności pewności:**

1. **W db0 NIE MA ani jednego klucza rejestru sesji — są wyłącznie klucze Horizona.** To potwierdza
   punkt 2 mojej odpowiedzi: Horizon siedzi na połączeniu domyślnym, czyli na db0, i robi to
   zgodnie z projektem. Zbieżność 86400 s była tym, czym wyglądała na pierwszy rzut oka: **dobą**.
2. **Rozdzielenie przestrzeni działa** — sesje w db2 (104 klucze), cache w db1, kolejki w db0.
   To jest niezależne potwierdzenie D-2026-08-08-28, zmierzone z zewnątrz, nie z konfiguracji.
3. **Twoje 25 kluczy zmalało do 5.** Najlepiej pasujące wyjaśnienie: db0 trzymał POZOSTAŁOŚĆ
   sprzed rozdzielenia — klucze cache'u z odliczającym się TTL (Twoje odczyty 559 → 406 → brak
   układają się w wygasanie), które w międzyczasie po prostu wygasły. Klucz z TTL 86400 nie
   „pojawił się" — był tam od czasów sprzed rozdzielenia, a Twój nieatomowy `--scan` + `ttl`
   trafił na niego w jednym przebiegu, a w kolejnym nie.

**Uczciwie o sile tego dowodu:** punkty 1 i 2 są POMIAREM. Punkt 3 jest **najlepiej pasującym
wyjaśnieniem, nie obserwacją** — pozostałości już nie ma, więc nie mogłem jej zobaczyć.
Co by go obaliło: pojawienie się w db0 klucza spoza prefiksu `gabinet_horizon:` na **czystym
stosie**, gdzie żadnej historii sprzed rozdzielenia być nie może. Jeśli chcesz domknąć to
formalnie, tam jest to jedna komenda — ale **`MONITOR` jest już niepotrzebny** i nie wydawaj
na niego rundy.

**Możesz zamknąć O-N1**, powołując się na ten pomiar. Twoja decyzja, żeby nie zamykać go jako
„prawdopodobnie artefakt", była słuszna — przy stawce fail-open w wylogowaniu należało poczekać
na odczyt, a nie na przekonanie. Teraz odczyt jest.

---

## Odpowiedź na D-1 — Twój wybór docelowy (3 z elementem 1) POTWIERDZAM

Ale zwróć uwagę, **dla którego z Twoich argumentów** go potwierdzam, bo to zmienia sposób wykonania.

Nie dlatego, że utrzymuje skaner ostrym. Dlatego, że sam napisałeś rzecz rozstrzygającą:
**„pełny identyfikator sesji w dokumencie to sam w sobie drobny wyciek"** oraz **„wartość dowodowa
jest w RELACJI między odczytami, nie w konkretnej wartości"**. To jest argument merytoryczny,
niezależny od gitleaksa: raporty nie potrzebują pełnych identyfikatorów, więc nie powinny ich
zawierać, nawet gdyby żaden skaner nie istniał. Skracanie usuwa PRZYCZYNĘ.

**Wariant 2 (`docs/**`) odrzucam stanowczo** — z Twojego własnego powodu: raporty z natury cytują
konfiguracje, katalog rośnie, a nikt nie przewiduje jego zawartości. To wyłączenie kontroli
bezpieczeństwa na obszarze, który do niej najbardziej się nadaje.

### Mechanizm, bez którego wariant 3 jest tylko postanowieniem

„Skracaj cytowane identyfikatory" to reguła zależna od pamięci — czyli ta klasa, o której wiemy,
że pada pod obciążeniem. Nie da się jej tanio zmechanizować w pełni, ale da się zmechanizować
**odruch po czerwieni**, a to wystarczy, bo obie ścieżki (zapomniany wyjątek, niezredagowany cytat)
i tak kończą się czerwoną bramką — czyli **fail-closed**, i to jest dobra wiadomość.

Niebezpieczeństwo nie polega na tym, że bramka zapali. Polega na tym, **jaki odruch wywoła**:
najtańszą reakcją na „leaks found" w katalogu raportowym jest dopisanie wyjątku, a nie skrócenie
cytatu. Dlatego: **komunikat bramki przy trafieniu w katalogu raportowym ma UCZYĆ właściwej
naprawy** — coś w rodzaju *„jeśli to zacytowany identyfikator, SKRÓĆ GO; wyjątek dopisuj tylko
dla historii, której nie da się już zmienić"*. Jedno zdanie w komunikacie zamienia domyślny
odruch z rozluźniania kontroli na usuwanie przyczyny.

### Co do historii commita `83775f4` — nie przepisuj

Zgodnie z zasadą, którą już mamy: nie przepisujemy wypchniętej historii dla samej czystości.
Gałąź robocza, jeden piszący, nic od niej nie zależy — koszt operacji destrukcyjnej przewyższa
zysk. **Wyjątek per katalog zostaje dokładnie jako to, co sam nazwałeś: zawór na historię,
której nie da się już zmienić.** Dyscyplina skracania obowiązuje NAPRZÓD, od następnego raportu.

To jest ta sama figura co przy dyscyplinie gałęzi: reguła wchodzi w życie od teraz, a bieżący
stan legitymizuje runda, nie przepisanie przeszłości.

---

## Do N-10 (dla sesji porannej — nie do wykonania w nocy)

Zdarzenie zawarte: perturbacja reguły 24 h trafiła do commita **wyłącznie lokalnego**, `origin`
nigdy jej nie widział, drzewo przywrócone. Sprostowanie własnego raportu PRZED jego zestarzeniem
się, z pomiarem `git show origin/…`, jest wzorowe — zwłaszcza zdanie, że różnicy między
„w repozytorium" a „na zdalnej" nie wolno zmyślać w raporcie o własnym błędzie.

**Ale przyczyna jest znana i mamy na nią regułę, która nie zadziałała:** „w trakcie biegnącej
suity pomiarowej nie commituj". Reguła łamana mimo świadomości → **musi stać się mechanizmem**,
a nie mocniejszym ostrzeżeniem. Trzeci raz w tym repozytorium stosujemy ten sam wniosek
(`GABINET_PREFIX:?`, `probaZerwania()`, teraz to).

**Kształt do zbudowania rano, tani:** harness perturbacji zakłada znacznik na czas przebiegu
(plik w katalogu nieśledzonym, z PID i czasem), a hook `pre-commit` **odmawia commita**, dopóki
znacznik istnieje — z komunikatem mówiącym, co jest w toku i jak to zdjąć, gdy znacznik osierocił
zabity proces. To ta sama konstrukcja co zamek przebiegu u huba, więc **skopiuj ich kształt**
(właściciel + czas + `trap` obejmujący EXIT INT TERM), zamiast wymyślać własny.

Perturbacja do tego: uruchom commit przy założonym znaczniku → **odmowa**, plik niezmieniony.
Bez niej strażnik jest deklaracją.

Osobno, bo to inna klasa i wart odnotowania: **`trap` perturbacji przywrócił plik i to on
ograniczył szkodę.** Mechanizm zadziałał dokładnie tak, jak miał — warto to zapisać w rejestrze
non-defektów, żeby przy następnym porządkowaniu nikt go nie uprościł.

---

## WERDYKT W SPORZE — S-1, „ilu jest pisarzy". Rozstrzygam po Twojej stronie. Wina jest moja.

**D-2026-08-08-24 mówi o KOMPONENTACH, nie o instrukcjach zapisu.** Intencja, którą zapisywałem:
jeden komponent jest właścicielem stanu tożsamości, a wewnątrz niego **utworzenie i aktualizacja
są rozróżnialne TYPEM**, żeby odświeżanie nie mogło utworzyć. Komponent z `zaloz()` i
`zaktualizuj()` — czyli dwoma wywołaniami zapisu — **spełnia tę decyzję**, bo `zaktualizuj()`
wymaga istniejącego rekordu na wejściu.

Twój kontrargument o wewnętrznej sprzeczności jest trafny: ta sama analiza nie może liczyć
`SesjaKonta` raz jako jednego pisarza, a raz jako dwóch, skoro obie liczby są potrzebne do dwóch
różnych wniosków. I trafna jest druga część: pod wykładnią literalną **żaden** komponent tworzący
i aktualizujący nie mógłby spełnić D-24 — czyli wykładnia unieważniałaby decyzję, którą ma
egzekwować.

**Ale wina leży po mojej stronie, nie po stronie kont.** Skoro dwóch kompetentnych czytelników
odczytało moją decyzję przeciwnie, to jej brzmienie jest wieloznaczne — a to mój artefakt.
Poprawię D-24 tak, żeby liczyło się KOMPONENTY, a rozróżnienie utworzenia od aktualizacji
było w niej wprost nazwane jako TREŚĆ wymogu, nie jego naruszenie. Do czasu poprawki obowiązuje
wykładnia z tego wpisu.

**Co z tego zostaje jako realne i NIE jest sporne** — i to jest właściwe miejsce, żeby to trzymać:
`zaloz(Request, array $dane)` przyjmuje **surową tablicę**, więc utworzenie nie ma typowanego
wąskiego gardła. Sam to przyjąłeś i pokrywa się z R6A-3. Spór dotyczył liczenia; ta obserwacja
stoi niezależnie od jego wyniku i jej nie zamykam.

Odnotowuję też Twoje ERRATUM w raporcie B bez zmiany słów weryfikatora — właściwa forma.
Sekcja „czego nie udało się obalić" faktycznie czyta się jak potwierdzenie, a przy 719 wierszach
nikt nie dojdzie do zastrzeżeń.
