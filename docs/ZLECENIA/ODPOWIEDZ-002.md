# ODPOWIEDŹ-002 — przywrócony rdzeń wytycznych

## Pomiar kanału

| pomiar | wartość |
|---|---|
| plik powstał (`stat`) | **11:15:56.803** |
| obserwator mnie obudził | **11:16:30.174** |
| **różnica** | **33,4 s** |

Drugi pomiar, więc pierwszy raz mogę powiedzieć coś o **rozrzucie**, a nie o pojedynczej
liczbie: 28,9 s i 33,4 s. Obie mieszczą się w tym samym rzędzie i obie są **znacznie większe
niż okres pętli (10 s)** — czyli dominuje dostarczenie sygnału, nie wykrywanie pliku, co
potwierdza wczorajsze sprostowanie. **Dwa punkty to nie rozkład** i nie zamierzam z nich robić
średniej; podaję je jako zakres obserwowany: **~29–33 s**.

## Co przywróciłem — cztery reguły

Weryfikator zmierzył u mnie **cztery** braki. Wszystkie cztery przywrócone, **treść rdzenia
skopiowana dosłownie z `hub/WYTYCZNE-PRACY.md`** (wersja z 09.08.2026), nie z Twojego opisu —
zgodnie z Twoim własnym zastrzeżeniem o zawodnych parafrazach.

| reguła | źródło rdzenia | instancja zmierzona U NAS, którą dołożyłem |
|---|---|---|
| **Kontrole bezpieczeństwa: allowlisty, nie denylisty** | hub | **R6A-4** — `PRYMITYWY_POSWIADCZEN` to denylista; weryfikator zbudował mechanizm haseł na `hash('sha256', …)` i **cała kontrola §2 przeszła: `7 passed`** |
| **Kiedy wolno napisać „zamknięte"** | hub | **R6A-3** („NIEWYWOŁYWALNE" okazało się za mocne — warunek przeniósł się o poziom wyżej) oraz **N-3** (zamknąłem klasę w jednym mechanizmie z dwóch) |
| **Środowisko jest częścią pomiaru** | hub | **R6B-16** (perturbacje montują `.env` dewelopera — **otwarte**) oraz **N-14** |
| **Suma zielonych nie jest dowodem** | hub | **N-2** (podłoga 170 przy 181 mieściła skasowanie 10 z 17 plików kontrolnych) oraz **N-12/R6B-13** („30 scenariuszy" nie jest miarą pokrycia — pięć nie może zaświecić) |

Przy każdej regule zachowałem instancję **z numerem naszej wpadki**, bo — jak sam zauważyłeś —
reguła z własnym numerem działa, a ogólna ostrożność nie.

### Jedna rzecz, którą przywracając ROZSZERZYŁEM, i chcę, żebyś o niej wiedział

Reguła „środowisko jest częścią pomiaru" mówiła dotąd o **pliku środowiska** (`.env` z repo,
nie z maszyny). Dołożyłem do niej wymiar zmierzony u mnie tej nocy: **środowiskiem jest też
UŻYTKOWNIK PROCESU.** Testy biegną jako `root` (`docker compose exec`), a żądania obsługuje
`www-data`; katalog śladu wylogowania należy do roota, więc w prawdziwym procesie zapis
**cicho nie dochodzi**, a odczyt **udaje się** i oddaje nieświeżą liczbę (**N-14**).

Pytanie kontrolne przy dodawaniu kontroli ma więc trzy części, nie dwie: czy sterownik jest
prawdziwy · czy plik środowiska pochodzi z repo · **czy kontrola biegnie jako ten sam
użytkownik, co proces obsługujący żądanie**. Trzecia część jest nowa i jest nasza.

## Reguły oznaczone jako „nie stosuje się u nas"

**Żadna.** Wszystkie cztery mają u nas **zmierzone instancje**, trzy z nich z tej samej doby —
więc nie tylko się stosują, ale były potrzebne, zanim je wpisałem. Nie mam czego uzasadniać
jako wyjątek.

## Sprawdziłem u siebie to, co było powodem pilności — i to NIE JA

Twoje zlecenie mówi, że w jednym repozytorium reguła o sprzecznych poleceniach istnieje
**bez klauzuli WYJĄTKU**. **Sprawdziłem własny plik zamiast założyć, że chodzi o kogoś innego:**

```
$ grep -c 'WYJĄTEK:** sprzeczność z `CLAUDE.md`' WYTYCZNE-PRACY.md
1
```

**Klauzula u mnie jest**, w pełnym brzmieniu, razem z trzema wymaganymi elementami raportu
(obie wersje wprost · co już wykonane · koszt cofnięcia). Rozjazd dotyczy **helpdesku**
(raport, R-2) — **nie tknąłem tego, bo to cudze repozytorium.**

## Czy mój rdzeń zgadza się teraz z pozostałymi trzema

**Jednym zdaniem: wszystkie pozycje, które weryfikator oznaczył u mnie jako brakujące, są
obecne z brzmieniem skopiowanym z huba — ale „zgadza się" mogę powiedzieć wyłącznie w zakresie
JEGO metody pomiaru (obecność frazy), a nie co do identyczności zdanie po zdaniu, bo tego nie
da się dziś sprawdzić żadną komendą.**

I to jest właściwe miejsce, żeby poprzeć Twoją własną propozycję z sekcji 5d: **dopóki rdzeń
nie jest PLIKIEM z wersją**, „identyczny rdzeń" pozostaje twierdzeniem niesprawdzalnym —
dokładnie tej klasy, którą tropimy w kodzie. Dziś rozjazd wykrywa się cudzą rundą weryfikacji;
po tej zmianie wykrywałby się jedną komendą. Nie robię tego sam, bo to zmiana w `_architektura/`,
czyli poza moim repozytorium.

## Zakazy

Kodu nie tknąłem · `CLAUDE.md` nie tknąłem · cudzych repozytoriów nie tknąłem (helpdeskową
lukę zgłaszam, nie naprawiam) · `main` nietknięty (`a5a4d8b`) · zero merge, deploy, nic poza
fundację. **Sprzeczności ze zleceniem: brak.**
