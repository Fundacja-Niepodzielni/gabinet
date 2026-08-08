# Odłożone — noc z 8 na 9 sierpnia 2026

Problemy techniczne, których nie umiem rozwiązać w rozsądnym czasie. Zapisuję
STAN i OBJAW, idę dalej.

**Nie improwizuję obejść o czwartej nad ranem** — obejście wymyślone bez świadka
jest dokładnie tym, czego rano nikt nie zweryfikuje. Lepszy jawny wpis tutaj
niż cicha łatka w kodzie.

Format wpisu: co próbowałem · dokładny objaw (komenda + wynik) · co wykluczyłem ·
co zostaje do sprawdzenia · czy blokuje coś innego.

---

## O-N1 — klucz o TTL 86400 s pojawił się w Redis db0, choć nie ma prawa

**Stan.** Po rozdzieleniu przestrzeni kluczy (D-2026-08-08-28) cache ma mieszkać
w bazie 1, sesje w bazie 2, a baza 0 zostaje dla kolejek/Horizona.

**Objaw (zmierzony, stos deweloperski `gabinet`):**

```
00:17:12  najwyższy TTL wśród kluczy `…-cache-…` w db0 = 559 s
00:18:59  najwyższy TTL = 86400 s        ← klucz zapisany chwilę wcześniej
00:19:46  najwyższy TTL = 406 s          ← klucza już nie ma
```

86400 s to co do sekundy `RejestrSesji::CZAS_ZYCIA_SEKUND`
(`backend/app/Tozsamosc/RejestrSesji.php:23`) — czyli mapa `sid → sesje lokalne`,
od której zależy back-channel logout.

**Co wykluczyłem.** `RejestrSesji` zapisuje przez `Cache::put()` (`:33`), a magazyn
cache'u wskazuje bazę 1 (zmierzone: `config('cache.stores.redis.connection')`
= `cache`, `database.redis.cache.database` = `1`). Świeżo uruchomione procesy
w kontenerach `gabinet-app` i `gabinet-horizon` piszą do db1 (sonda `Cache::put`
w obu — klucz wylądował w db1, w db0 i db2 zero).

**Czego nie wiem.** Który proces zapisał ten klucz do db0 i dlaczego zniknął.
Nie czytałem wartości — mogłaby zawierać identyfikatory sesji.

**Dlaczego nie improwizuję naprawy.** Hipotezy są co najmniej trzy (wyścig w moim
przyrządzie pomiarowym: `--scan` i `ttl` to osobne wywołania `redis-cli`; jakiś
kod sięgający po połączenie `default` z pominięciem magazynu cache'u; pozostałość
procesu w trakcie zamykania po restarcie Horizona). Rozstrzygnięcie wymaga
`MONITOR` albo `CLIENT LIST` w chwili zapisu, czyli obserwacji na żywo — a nie
zgadywania po fakcie.

**Czy blokuje.** Nie blokuje bramki ani rundy. **Ale dotyka `RejestrSesji`**,
który weryfikator niezależnie wskazał jako fail-open (R6B-9: utrata rejestru
daje `skasowane_sesje = 0` po cichu). Jeśli rejestr trafia czasem do innej bazy,
niż z niej czyta, to jest to ta sama klasa awarii, tylko innym wejściem.

**Do zrobienia rano (kolejność):** `redis-cli -n 0 MONITOR` w tle podczas
logowania i wylogowania · sprawdzić, czy którykolwiek kod używa `Redis::`
zamiast `Cache::` · powtórzyć na CZYSTYM stosie, gdzie nie ma historii
sprzed rozdzielenia.

**Uzupełnienie 00:26 — dwa dalsze pomiary zawężają, ale nie zamykają.**

```
$ grep -rn "Redis::" backend/app backend/config backend/routes
backend/app/Console/Commands/Zdrowie.php:57:  Redis::connection()->ping();
  → jedyne bezpośrednie użycie Redisa z pominięciem magazynu cache'u to PING.
    PING niczego nie zapisuje, więc ścieżka „kod pisze wprost do db0" — ODPADA.

$ porównanie ZBIORU kluczy db0 w odstępie ~6 minut (00:20:27 → 00:26)
  t1 = 25 kluczy, t2 = 25 kluczy, nowych: ZERO
  → przez sześć minut db0 nie dostał ANI JEDNEGO nowego klucza.
```

Wiodąca hipoteza przesuwa się więc na **artefakt mojego przyrządu pomiarowego**:
migawka składa się z `redis-cli --scan` i osobnego `redis-cli ttl` na każdy klucz,
czyli z dziesiątek niezależnych wywołań rozłożonych w czasie — to nie jest odczyt
atomowy i nie mam dowodu, że wszystkie dotyczyły tej samej chwili.

**Zostawiam wpis OTWARTY mimo to.** Różnica między „to był artefakt" a „coś
naprawdę zapisało klucz rejestru sesji do złej bazy" jest różnicą między niczym
a fail-open w back-channel logout (R6B-9). Przy takiej stawce „prawdopodobnie
artefakt" nie jest rozstrzygnięciem — jest hipotezą do sprawdzenia `MONITOR`-em
na czystym stosie.
