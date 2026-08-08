# Zlecenie rundy 6 — niezależna weryfikacja na SHA `49131d8`

Zapisane, bo **zlecenie jest częścią wyniku**. Runda, której treści zlecenia nie
da się odtworzyć, nie jest powtarzalna, a jej wynik jest nieinterpretowalny.

- **Przedmiot:** SHA `49131d8d0bbe73991ea4283b7bd631fc17b0b751`, gałąź `faza-1-retencja`
- **Data zlecenia:** 2026-08-08, 23:40
- **Wykonawca kodu (czyli ja) NIE weryfikuje.** Weryfikują dwaj świeży agenci.
- **Reguła zbieżności rund (D-2026-08-07-16):** faza jest zamknięta dopiero, gdy
  runda na konkretnym SHA kończy się ZEREM znalezisk.

## Dlaczego dwóch weryfikatorów, a nie jeden

Zakres a–f nie mieści się w jednej sesji bez utraty jakości. Podział biegnie po
linii **„potrzebuje żywego stosu / nie potrzebuje"**, żeby dwa ciężkie stosy
dockerowe nie chodziły równolegle na maszynie, na której stoi już 60 kontenerów.
Współbieżne obciążenie to znany generator **fałszywej czerwieni** (timeouty sond
zdrowia), a runda skażona fałszywą czerwienią jest bezwartościowa.

## Wymagania izolacji (identyczne dla obu)

Weryfikacja USŁUGI STANOWEJ na „czystym klonie" dzielącym cudzą instancję to
fikcja — klon mierzyłby CUDZY stan. Dlatego wymagane są WSZYSTKIE wektory:

| wektor | A | B |
|---|---|---|
| klon | `/d/tmp/gabinet-r6a` | `/d/tmp/gabinet-r6b` |
| projekt compose | `gabinet-r6a` | — (bez dockera) |
| prefiks nazw (kontenery/sieć/wolumeny) | `gabinet-r6a` | — |
| tag obrazu | `gabinet-r6a-app:local` | — |
| port HTTP / Postgres / Redis | 8107 / 55461 / 56407 | — |
| sekrety | budowane przez bramkę z `.env.example` | `.env.example` |

**`.env` dewelopera nie jest nawet czytany.** Klon weryfikatora nigdy nie trzyma
prawdziwych sekretów (near-miss zespołu helpdesku: weryfikator skopiował `.env`
z sekretami do katalogu tymczasowego).

## Twarde zakazy w obu zleceniach

- Zero zmian w drzewie roboczym wykonawcy (`d:\KOD\Niepodzielni\gabinet`).
- Zero commitów, pushy, gałęzi w repozytorium źródłowym.
- Zero dotykania cudzych stosów: `helpdesk-*`, `control-plane*`,
  `niepodzielni-hub*`, `np-*`, `trydive*`, `gabinet`, `gabinet-perturbacje`.
- **Zero kasowania obrazów.** Żadnego `image prune`, `rmi`, `system prune`,
  `volume prune`. Obrazy są globalne dla DEMONA — `prune` kasuje po WIEKU i po
  braku referencji, nie po nazwie, więc sprzątaczka projektu efemerycznego
  zabrałaby obraz spod cudzego stosu.
- Sprzątanie WYŁĄCZNIE: kontenery + wolumeny własnego projektu + własny klon,
  zawsze z prefiksem (`GABINET_PREFIX=<projekt> docker compose -p <projekt> down -v`) —
  bo nazwy wolumenów biorą się z `GABINET_PREFIX`, a nie z `-p`. Weryfikator
  poprzedniej rundy wykonał instrukcję bez prefiksu i zaczął kasować
  `gabinet-pg-data`, czyli bazę dewelopera.
- **Niczego nie naprawiają.** Produktem jest raport.
- Rozróżnienie obowiązkowe: **znalezisko ≠ awaria środowiska**. Padnięty port,
  brak pamięci, timeout sondy przy obciążonej maszynie to awaria POMIARU, nie
  defekt systemu — i ma być nazwana wprost oraz powtórzona.

## Zakres — weryfikator A („stos")

**0. Bramka.** Pełny przebieg, surowe liczby: kroki / nieudane / testy /
asercje / lista nieudanych kroków. Twierdzenie do sprawdzenia: *„bramka jest
czerwona dokładnie z JEDNEGO powodu — test `NOGA 1` — i tylko z niego"*.

**(b) Czy jakikolwiek ZIELONY test przechodzi z niewłaściwego powodu.**
Metoda **obowiązkowa**, nie do zastąpienia czytaniem kodu: **usuń mechanizm,
który test rzekomo bada** (w kodzie produkcyjnym, w klonie), uruchom test, sprawdź,
czy dalej zielony. Zielony po usunięciu mechanizmu = test nie bada tego, co
deklaruje. Po każdej próbie `git checkout -- .` + sprawdzenie `git status`, żeby
następna próba nie mierzyła pozostałości po poprzedniej.
Objęte co najmniej: wszystkie zielone testy `OdebranieRoliTest.php` (logout →
401, trwałość znacznika unieważnienia wobec `Cache::flush()`, fail-closed przy
braku rozstrzygnięcia, test adwersarialny wymuszonego wylogowania, odebranie
roli, źródło ról = access token, szyfrowanie ID tokenu), `SesjaBezJawnychDanychTest`,
`RetencjaTest`, `RetencjaWykonanieTest`, kontrola „jeden pisarz klucza `konta`",
`ObietniceKomentarzyTest` (kontrola nad kontrolami — czy sama jest falsyfikowalna),
`BrakWlasnychHaselTest`. Czego nie zdążył — ma wypisać JAWNIE.

**(c) Czy naprawa §2 jest NAPRAWDĘ niewywoływalna z odświeżania.**
**Próba obejścia, nie odczyt kodu.** Wektory: Reflection
(`newInstanceWithoutConstructor`, `setAccessible`), `unserialize`/`__set_state`,
klonowanie, `Session::put('konta', …)` z pominięciem `SesjaKonta`, pisanie
pośrednie (zmienne, stałe, `Arr::set`, middleware, kolejki), `zPodmienionymi()`
na tożsamości z INNEJ sesji, `zMagazynu()` nakarmione danymi Z ŻĄDANIA,
serializacja sesji/kolejki/cache, oraz pomiar WŁASNY: ilu jest pisarzy klucza
`konta` w całym `backend/app` (wykonawca twierdzi „jeden") i czy `zaloz()`
naprawdę jest osiągalne tylko z callbacku OIDC.
Druga strona: czy wąskie gardło nie jest ZA CIASNE i nie łamie legalnego
odświeżenia działającej sesji.

**(f) Rozdzielenie przestrzeni kluczy Redisa.** Na ŻYWYM Redisie, nie w suicie
(tam sterowniki są `array` i pomiar jest pusty): `dbsize` bazy 1 i 2 przed/po,
`cache:clear` oraz `Cache::flush()`, surowe liczby. Oraz — najważniejsze —
czy dokument gdziekolwiek obiecuje ochronę, której rozdzielenie baz NIE DAJE:
**eksmisja (`maxmemory-policy`) jest własnością INSTANCJI Redisa, nie bazy**,
więc rozdzielenie baz nie chroni sesji przed LRU. Do zmierzenia `config get
maxmemory` i `maxmemory-policy`.

## Zakres — weryfikator B („analiza", bez dockera)

**(a) Czy któryś dyskryminator ma gałąź zdegenerowaną.**
Definicja robocza podana w zleceniu: *dyskryminator ma gałąź zdegenerowaną, gdy
JEDNA wartość pomiaru jest zgodna z WIĘCEJ NIŻ JEDNYM światem* — wynik nie daje
się wtedy przypisać badanemu zjawisku. Wykonawca przyznaje CZTERY takie
instancje jednego dnia i twierdzi, że to jego wzorzec, nie przypadek; zadaniem
B jest znaleźć POZOSTAŁE.
Produkt: tabela `pomiar (plik:linia) | co mierzy | JAKIE ŚWIATY dają tę wartość |
czy jest odczyt bazowy | werdykt (ZDEGENEROWANY / ROZSTRZYGAJĄCY / NIEPEWNY)`,
obejmująca `backend/tests/`, `skrypty/perturbacje.sh`, `skrypty/perturbuj.py`,
`skrypty/bramka.sh`, `perturbacje-powtarzalne.sh`, `licz-testy.sh`,
`skrypty-uruchamialne.sh` — **także pozycje wychodzące poprawnie**, bo bez listy
negatywnej nie wiadomo, co zostało przejrzane.
Nacisk na pomiary będące LICZBĄ (0/1/2) albo STATUSEM HTTP: 200 może znaczyć
„działa" albo „badana ścieżka w ogóle się nie uruchomiła"; 0 może znaczyć „nie
było czego znaleźć" albo „mechanizm szukający padł".

**(d) Czy migawki nogi 1 mierzą to, co deklarują.**
Wykonawca DEKLARUJE ograniczenie własnego przyrządu: migawki nie odróżniają
„tożsamość odtworzona W MAGAZYNIE" od „tożsamość niesiona przez KLIENTA
TESTOWEGO w pamięci procesu". Zadanie: (1) potwierdzić albo OBALIĆ tę samoocenę;
(2) wypisać WSZYSTKIE światy zgodne z pomiarem „zniknęło 1 / pojawiło się 1 /
status 200" — także takie, których wykonawca nie wymienił; (3) **zaprojektować
odczyt rozstrzygający** badający ZAWARTOŚĆ, nie sam fakt pojawienia się klucza,
z odczytem bazowym i bez dzielenia mechanizmu z przedmiotem (reguła C1);
(4) **NIE WDRAŻAĆ GO**. Projekt odczytu jest produktem, jego wdrożenie nie.

**(e) Czego jeszcze nie pokrywają podmienione sterowniki.**
`CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`,
`MAIL_MAILER=array`. Wynikiem ma być **RÓŻNICA wobec przeglądu wykonawcy
(D-2026-08-08-27)** — kontrole, których tamten przegląd NIE wypisał, a które są
puste. Plus ciche podmiany, których nikt nie wymienił: `RefreshDatabase`,
`Storage::fake`, hasher, broadcaster, harmonogram, klucz szyfrowania, strefa
czasowa, `Http::fake`, `Bus::fake`, `Event::fake`, `Notification::fake` — każdy
`fake()` to podmieniony sterownik, a każda kontrola „za" nim jest pusta.

## Zakres wspólny — pytanie obowiązkowe rundy (g)

Wykonawca wielokrotnie klasyfikował znaleziska jako *„wada PRZYRZĄDU, nie
systemu"*. Taka atrybucja jest dla niego wygodna, a **atrybucji wygodnej nie
obali ten, komu ona służy** — dlatego pytanie idzie do zlecenia rundy, nie do
samooceny: *czy część znalezisk zaklasyfikowanych jako wada przyrządu nie jest
w rzeczywistości wadą SYSTEMU przebraną za wadę przyrządu?*

## Format raportu (oba)

Znalezisko = jeden wpis: identyfikator (`R6A-*` / `R6B-*`) · co · **dowód
(komenda + surowy wynik, nie parafraza)** · waga · czy blokuje · **świat
alternatywny** (jakie INNE wyjaśnienie daje ten sam pomiar; jeśli nie
wykluczone — powiedzieć wprost). Do tego: twierdzenia, których **nie udało się
obalić** (to też wynik), i jawna lista **czego nie zdążyli sprawdzić**.
