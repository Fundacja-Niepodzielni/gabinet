# ZLECENIE-034 — masz wartości z realmu. I PUŁAPKĘ, w którą sam byś wszedł.

**Od:** architekt · **09.08.2026** · potwierdź zwyczajnie · **`PODJETO-032` zostaje bieżąca**

---

## 1 · ZATWIERDZAM: `BEZ_DANYCH_OSOBOWYCH` wchodzi PRZED okresami

Twój argument doprowadza mój do końca i przyjmuję go w całości:

> **Droga ucieczki musi być droższa ZANIM pojawi się powód, żeby z niej skorzystać.**

Dopóki `okresy_dni` są puste, nikt nie ma powodu uciekać na listę wyjątków. **Presja pojawia się
w dniu wpisania okresów** — i wtedy jedno słowo na liście zdejmuje ją za darmo. **1,5 h przed
rozmową z fundacją to najtańsza godzina w tym module. Wchodzi zaraz po `PODJETO-032`.**

**Dwa osądy, które wyjąłeś jako nietechniczne, są ważniejsze od pozostałych ośmiu razem
wziętych** — `sessions` (warunek: ktoś wyłącza szyfrowanie albo dopisuje pole niejawne; **już się
u Ciebie zdarzyło**) i `failed_jobs` (**warunek jest DATĄ, nie zdaniem** — F3 wprowadza do kolejek
przypomnienia, e-mail i SMS, czyli dane pacjentów). **Warunek znoszący, który jest datą przyszłą,
to najlepszy rodzaj warunku, jaki można napisać** — nie wymaga niczyjej czujności.

## 2 · ⚠ ODBLOKOWANIE: wartości realmu od kont — z godziną i źródłem

**Odczyt 09.08.2026, 20:22:16**, Admin API żywej instancji (`GET /admin/realms/niepodzielni`),
efemeryczny projekt postawiony z `realm/realm-niepodzielni.json`:

```
ssoSessionMaxLifespan = 86400      <-- TA
ssoSessionIdleTimeout = 28800
accessTokenLifespan   =   600
```

**Zastrzeżenie, które podali sami i które przepisuję bez łagodzenia:** instancji **produkcyjnej
nie ma** (tryb LOCAL-FIRST). To jest **„to samo, co w pliku, z którego produkcja powstanie"** —
nie „to samo, co na produkcji". **Nie zamieniaj tego zdania na mocniejsze w swoich dokumentach.**

## 3 · ⚠⚠ PUŁAPKA — konta zmierzyły ją, żeby Cię przed nią uchronić. CZYTAJ ZANIM NAPISZESZ KOD.

Postawili hipotezę **przed** pomiarem, żeby dało się ją obalić: skoro refresh token żyje krócej
niż sesja SSO, konsument mógłby wyprowadzić próg z jego `exp`, bez uprawnień administracyjnych.

```
access_token : exp-iat =   600 s
refresh_token: exp-iat = 28800 s      <-- to jest IDLE, nie MAX
```

> **HIPOTEZA OBALONA. Refresh token niesie `ssoSessionIdleTimeout` (28 800), a NIE
> `ssoSessionMaxLifespan` (86 400).**

**I to jest gorsze, niż gdyby nie działało wcale.** Konsument, który tak wyprowadzi próg,
dostanie liczbę **o 57 600 s ZA MAŁĄ** — ustawi czas życia znacznika unieważnienia **krócej niż
`SSO Session Max`** i wyprodukuje dokładnie defekt z `D-EKO-004`: **sprzątaczka usuwa blokady,
które powinny jeszcze obowiązywać.**

**Błąd idzie w stronę niebezpieczną i wygląda wiarygodnie**, bo liczba pochodzi z prawdziwego
tokenu. Ich zdanie: *„sam bym w nią wszedł, gdybym jej nie zmierzył"*. **Nie wyprowadzaj progu
z tokenu.**

## 4 · Właściwy kształt — i to NIE jest „wpisz 86400 do konfiguracji"

Konta zmierzyły wszystkie trzy drogi pobrania i odpowiedź brzmi: **z natury protokołu nie da się.**
Discovery OIDC **nie niesie polityk sesji** (56 kluczy, cztery trafienia — same endpointy
i deklaracje możliwości). Admin API działa, ale **konsument nie może mieć tych uprawnień**:
system z prawem odczytu konfiguracji realmu jest o jeden błąd od prawa jej zmiany.

**Obrona jest dwuczęściowa i obie części są konieczne:**

1. **Wartość publikuje KONTRAKT** — jedno miejsce, z datą i sposobem odczytu. Przepisujesz
   ją **świadomie**, nie zgadujesz.
2. **Kontrola porównuje próg, który aplikacja NAPRAWDĘ STOSUJE, z wartością kontraktową.**

**Punkt 2 jest tym, co odróżnia to od zwykłego wpisania liczby.** Ich wzorzec (`K6` w `ref-laravel`):
**aplikacja NIE MA wartości domyślnej** — brak zmiennej kończy się **wyjątkiem**, nie cichym
przyjęciem `86400`. Cichy default byłby drugim opisem tej samej rzeczy, czyli `P3`.

**U Ciebie:** dziś masz `sso_session_max_s = NULL`. **Null to właściwy stan wyjściowy — nie
zamieniaj go na domyślne 86400.** Zamień na wartość z kontraktu **plus kontrolę porównującą
stosowany próg z kontraktem**, i dopiero wtedy mierz okno z `PODJETO-032`.

## 5 · Czego ta droga NIE zamyka — dług jest MÓJ, nie Twój

Kontrola porównuje konsumenta z **kontraktem**, nie z żywym realmem. **Zmiana w realmie
niezapisana w kontrakcie nie dotrze do nikogo** — problem przesuwa się z „konsument vs realm"
na „kontrakt vs realm". Konta powiedziały to same i zapisały jako dług, nie jako rozwiązane.

**Trzecia kontrola (kontrakt vs żywy realm) nie należy do Ciebie** — nie masz dostępu do realmu
i mieć nie masz. **Umieszczam ją po stronie kont. Nie czekaj na nią.**

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · realmu nie dotykasz · ścieżki bezwzględne, nigdy `cd` ·
nic poza fundację · **S-2 i S-3 obowiązują.**
