# Podsumowanie nocy z 8 na 9 sierpnia 2026

Dla właściciela — najpierw po ludzku, potem dowody. Dla architekta — numery
znalezisk i pliki. Wszystko, co niżej nazwane „zmierzone", ma komendę i wynik
w `DZIENNIK.md` albo `ZNALEZISKA.md`.

---

## Najkrócej: co się stało tej nocy

**Znaleźliśmy przyczynę jedynego czerwonego testu — i okazało się, że system
działa poprawnie, a zepsuty był przyrząd, którym go badaliśmy.**

Od kilku dni jeden test świecił na czerwono i wyglądało to na poważną dziurę
w bezpieczeństwie: podejrzenie, że po usunięciu danych logowania użytkownika
system i tak wpuszcza go dalej. Wieczorem 8 sierpnia sam obaliłem swoją pierwszą
diagnozę, ale nowej nie miałem. **Tej nocy dwaj niezależni kontrolerzy ustalili
przyczynę i zmierzyli, że system zachowuje się PRAWIDŁOWO** — po usunięciu
tożsamości odmawia dostępu (kod 401), tak jak powinien. Czerwony był test,
nie produkt: test próbował udawać „nowe żądanie przeglądarki" w sposób, który
nie działa w tym frameworku.

**Czerwony zostawiłem czerwonym.** Naprawa testu bezpieczeństwa przez tę samą
osobę, która go pisała, w nocy i bez niezależnej kontroli, dałaby rano zielone
światło bez żadnego pokrycia. Naprawa jest opisana co do linijki i czeka na rano.

**Druga rzecz, ważniejsza w skutkach:** narzędzie, które ma udowadniać, że nasze
kontrole umieją wykryć błąd, samo przestało działać — i mimo to meldowało sukces.
Naprawiłem je i sprawdziłem pomiarem, że wykrywa tę awarię.

---

## Co jest ZROBIONE

| # | Rzecz | Dowód |
|---|---|---|
| 1 | **Runda 6 zlecona i odebrana** — dwaj niezależni weryfikatorzy, osobne czyste klony, w pełni izolowane projekty, sekrety testowe z repo | `RUNDA-6-A-RAPORT.md`, `RUNDA-6-B-RAPORT.md`, zlecenie w `ZLECENIE-RUNDA-6.md` |
| 2 | **Przyczyna nogi 1 ustalona pomiarem** — wada przyrządu, system spełnia wymóg B8 | `ZNALEZISKA.md` R6A-2, R6B-1 |
| 3 | **Naprawa przyrządu: perturbacje znowu psują** (2 martwe mutacje) | N-3, `DZIENNIK.md` 00:35 |
| 4 | **Naprawa przyrządu: dowód mutacji z odczytem bazowym** — rozróżnia 3 światy | `DZIENNIK.md` 00:50, pomiar na 3 spreparowanych sytuacjach |
| 5 | **Naprawa przyrządu: podłogi bramki** 170/590 → 180/635 | N-2 |
| 6 | **Naprawa przyrządu: `bramka.sh` respektuje `--projekt`** przy pliku środowiska | R6A-10, `DZIENNIK.md` 00:45 |
| 7 | **`PLAN-FAZ.md` ma znów JEDNĄ sekcję stanu** (były dwie, sprzeczne) | R6A-9, `grep -c` → 1 |
| 8 | **Trzy komentarze w kodzie przestały nieść obalony wniosek** | N-1 |
| 9 | **Przegląd ustawień domyślnych** PostgreSQL i Redis (Z3) | N-5, N-6 |
| 10 | **`CURRENT WORK` zaktualizowane Z ODCZYTU**, z ostrzeżeniem o pokryciu perturbacji | `PLAN-FAZ.md` |

**Znaleziska łącznie: 29 z rundy 6** (12 + 17) **i 7 własnych** (N-1…N-7).

---

## Co jest CZERWONE

1. **Bramka: CZERWONA — 1 nieudany krok z 22.** Zamierzone i tak ma zostać.
   Przyczyna znana (wada testu, nie systemu), naprawa opisana, czeka na rundę.
2. **Runda 6 NIE jest zerowa → F0 i F1 pozostają OTWARTE.** Reguła zbieżności
   (D-2026-08-07-16) mówi, że fazę zamyka dopiero runda z zerem znalezisk.
   Merge do `main` nadal zablokowany — i słusznie.
3. **Trzy znaleziska o wadze krytycznej/wysokiej, których NIE naprawiałem**
   (bo naprawa autorem tej samej nocy nie miałaby rundy):
   - **R6A-4** — mechanizm własnych haseł nadal przechodzi kontrolę `§2`
     („ŻADNYCH własnych haseł" z `CLAUDE.md`). Waga krytyczna.
   - **R6A-3** — wąskie gardło `§2` nie jest strukturalne: weryfikator wytworzył
     tożsamość koordynatora bez logowania. **Zastrzeżenie: nie jest to dziura
     wykorzystywalna z zewnątrz w obecnym kodzie** — trasy trzeba było dopisać.
     Obalone zostało moje twierdzenie o strukturze, nie bezpieczeństwo aplikacji.
   - **R6B-9** — `RejestrSesji` (bez niego wylogowanie nie znajdzie sesji) mieszka
     w cache'u; jego utrata daje „wylogowano 0 sesji" po cichu. Fail-open.
4. **Pokrycie perturbacji jest mniejsze, niż mówiła liczba „30 scenariuszy".**
   Pięć scenariuszy nie może dziś zaświecić czerwono, bo celują w plik, który
   jest czerwony z innego powodu.

---

## Co PIERWSZE RANO

Kolejność wynika z jednej zasady: **najpierw przywróć zdolność przyrządu do
świecenia czerwono, potem mierz nim cokolwiek.** Pełna lista z uzasadnieniami:
`PLAN-FAZ.md`, sekcja „PIERWSZE ZADANIA NASTĘPNEJ SESJI".

1. **Sprawdź pozostałe 28 z 30 wzorców perturbacji** — w nocy sprawdzono 2 i oba
   były martwe. To najpilniejsza luka, nazwana przez samego weryfikatora.
   Metoda gotowa: `python3 skrypty/perturbuj.py <nazwa>` → `kod=0` i niepusty
   `git diff --stat`.
2. **Uruchom pełny `skrypty/perturbacje.sh`** — nie zdążyłem tego zrobić w nocy
   (maszyna obsługiwała weryfikatora), a zmieniałem ten skrypt.
3. **Napraw test nogi 1** (nie system) i **zmierz ponownie** — ma zzielenieć,
   a test „POZYTYWNY" ma zostać zielony.
4. Znaleziska R6A-3, R6A-4, R6B-9 wg wag.
5. **Źródła makiety są dostępne**: `Fundacja-Niepodzielni/gabinet-makieta` —
   do rozpoznania przed planowaniem fazy frontendowej. Nie klonowałem, nie
   analizowałem: noc była przeznaczona na weryfikację, a podpięcie frontendu to
   nowa budowa, która rano potrzebuje własnej rundy.
   *Uwaga metodyczna od architekta:* makieta jest źródłem WYGLĄDU, nie prawdy
   o zachowaniu — przy podpinaniu ekranów dla każdej widocznej reguły trzeba
   wskazać funkcję serwera, która ją rozstrzyga, i nie dopuścić do reguł
   istniejących wyłącznie w makiecie.

---

## Czego NIE zrobiłem i dlaczego — jawnie

- **Nie naprawiałem znalezisk weryfikatorów** (poza przyrządem). Zlecenie mówi
  wprost: naprawa autorem tej samej nocy nie miałaby rundy i rano byłaby stertą
  zmian bez pokrycia.
- **Nie uruchomiłem pełnego zestawu 30 perturbacji.** Wymaga własnego stosu
  i kilkudziesięciu minut, a maszyna obsługiwała weryfikatora rundy 6 (plus
  równolegle biegły nocne weryfikacje innych zespołów — 42 kontenery).
- **Nie ruszałem `main`**, nie merge'owałem, nie wdrażałem, nie zgłaszałem nic
  na zewnątrz, nie zmieniałem `CLAUDE.md` ani zamkniętych wpisów `DECYZJE.md`.
- **Nie kasowałem obrazów.** Zostają dwa ślady po weryfikacji:
  `gabinet-r6a-app:local` i obraz kontroli nocnej — zamierzone, bo `prune`
  kasuje po wieku i braku referencji, nie po nazwie, i zabrałby obraz spod
  cudzego stosu.
- **Z1 (lista braków pokrycia) wykonana tylko częściowo** — w praktyce zastąpiły
  ją sekcje E-1…E-11 raportu B, które są dokładnie taką listą i powstały
  niezależnie ode mnie. Własnej, osobnej listy nie budowałem drugi raz.

---

## Trzy rzeczy, które tej nocy pomyliłem — i jak wyszły na jaw

Zapisuję je, bo raport bez nich byłby nieprawdziwy, a wszystkie trzy są tą samą
wadą, którą ta noc badała u kodu: **wnioskiem zgodnym z więcej niż jednym światem.**

1. **„Exit code 0" dwa razy z rzędu znaczyło „pomiar się nie wykonał"** — raz
   przez nieistniejącą flagę `docker exec -T`, raz przez `| tail`, które
   przejmuje kod wyjścia. Wyszło, bo przeczytałem TREŚĆ wyjścia, nie kod.
2. **Zbieżność TTL 86400 s uznałem za trop** — a 86400 to po prostu doba,
   najczęstsza wartość TTL w oprogramowaniu. Błąd częstości bazowej. Rozstrzygała
   NAZWA klucza, nie jego TTL; po odczytaniu nazw wątek zniknął w minutę.
3. **Restart Horizona uznałem za test rozstrzygający** — a rozpad TTL w tempie
   zegara zachodzi zarówno gdy zapisy ustały, jak i gdy klucze po prostu
   wygasały. Zmierzyłem zgodność z hipotezą, nie jej wyłączność. Waga N-7
   obniżona, sposób rozstrzygnięcia opisany.

Do tego jedna pomyłka manualna: podmieniając sekcję w `PLAN-FAZ.md` skasowałem
przy okazji trzy tabele zadań (10 831 znaków). Wyszło natychmiast, bo skrypt
wypisywał liczbę usuwanych znaków, a usuwaną treść zapisywałem do pliku przed
nadpisaniem. Przywrócone i sprawdzone.

---

## Stan drzewa na koniec

Gałąź `faza-1-retencja`, wszystko scommitowane i wypchnięte. Bramka czerwona
jednym, zamierzonym krokiem. Żadnej pracy w toku, żadnego pliku w połowie.
