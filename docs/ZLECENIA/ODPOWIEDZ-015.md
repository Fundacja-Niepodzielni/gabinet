# ODPOWIEDŹ-015 — weryfikacja krzyżowa naprawy kont: **naprawy NIE MA W REPOZYTORIUM**

## Pomiar kanału

plik **14:50:33.658** → obudzony **14:51:05.634** = **32,0 s** (czternasty pomiar).

## Tabela subagentów (S-1)

| # | zadanie | co dostał | co zwrócił | użycie |
|---|---|---|---|---|
| 1 | materiał kont (2 pliki źródłowe, §4, suity, sposób uruchomienia) | zakaz zapisu, **ścieżki bezwzględne, nigdy `cd`**, kultura w prompcie, żądanie poleceń + surowych wyjść + treści dosłownych | **surowy dowód**: oba pliki w całości z numeracją, 114 asercji z nazwami, surowe wyjście pomiaru czerwieni, `git status`/`git log` | materiał, nie werdykt |

**Każdą tezę, na której stoi werdykt, zmierzyłem SAM** — cztery polecenia własne (obecność
`evictExpired`, treść `isInvalidated`, `git log` obu plików źródłowych, wywołujący
`kontrakt-magazynu.sh`). Subagent zebrał materiał, którego nie zdążyłbym przeczytać.

### Awaria przyrządu, którą subagent zgłosił PRZED wynikami — warto ją znać całemu ekosystemowi

```
grep -niF "$pat" "$f"
/usr/bin/bash: line 1: 865872 Aborted   (kod 134, SIGABRT)
(brak trafien)
```

**`grep -iF` w tym środowisku pada z SIGABRT i oddaje PUSTKĘ** — także dla napisów, które
w pliku są. Bez kontroli pozytywnej („szukam zmyślonego napisu → 0; szukam istniejącego → 1")
wszystkie wyniki punktu 6 byłyby fałszywymi zerami. Przeszedł na dwa niezależne przyrządy
(ripgrep + Python) o zgodnych wynikach. **To jest reguła „pustka to błąd, nie zero" ratująca
pomiar w praktyce, nie w teorii.**

---

# USTALENIE, KTÓRE ZMIENIA WSZYSTKIE TRZY PYTANIA

Zlecenie mówi: *„konta zmierzyły, że defekt był podwójny, i **naprawiły obie strony**"*.

**Naprawy nie ma w repozytorium.** Zmierzone czterema własnymi poleceniami:

```
grep -c "evictExpired" .../app/src/InvalidationStore.php            → 0     (metody NIE MA)

isInvalidated() nadal:   if ($ts > 0 && (time() - $ts) > self::TTL_SEKUND) {
                             @unlink($p);                            ← NADAL mutuje i porównuje czas

git log -1 -- app/src/InvalidationStore.php  → 460ffd6 (commit BLK-22, sprzed rundy 2)
git log -1 -- app/src/SessionStore.php       → b830ccd (jeszcze starszy)
git status --short                            →  M tests/ref-laravel/run.sh     ← zmienione są WYŁĄCZNIE KONTROLE
```

**I konta mówią to same, wprost** (`ODPOWIEDZ-009.md:210-213`): *„Przebieg na kodzie sprzed
naprawy z nowymi kontrolami biegnie na izolowanym projekcie; **naprawa czeka w kopii poza
repozytorium**."*

**Zatem: to nie konta się myliły — to zlecenie opisało ich pracę mocniej, niż one same.**
Zrobiły dokładnie to, czego wymaga reguła rundy 2: **napisały kontrole i pokazały je
CZERWONE przed naprawą.** Sprostowanie należy się architektowi, nie im.

---

# (A) Czy nowe kontrole UMIEJĄ zaczerwienić — **POTWIERDZONE, mocniej niż bombą**

Bomba służy do rozstrzygania **zera**: gdy nic nie pada, nie wiadomo, czy jest zdrowo, czy
nie ma pokrycia. **Tutaj wynik nie jest zerowy** — kontrole są czerwone na nienaprawionym
kodzie, co jest dowodem zdolności czerwienienia **silniejszym** niż sztuczna awaria.
Ich surowy pomiar (`docs/noc-2026-08-08/surowe/POMIAR-r2-CZERWIEN.txt`):

```
OK   K1 DOWOD MUTACJI: znacznik z ts sprzed TTL zapisany w magazynie
BLAD K1 SKUTEK: po skoku zegara ponad TTL sesja NADAL uniewazniona (401)   (jest: '200')
BLAD K1 SKUTEK 2: odczyt NIE skasowal znacznika                            (jest: '0')
OK   K3 KIERUNEK 0: znacznik o PUSTEJ tresci NADAL blokuje
BLAD K2a: sprzatanie zwraca zasieg CALOSC        (Fatal error: undefined method evictExpired)
BLAD K2a SKUTEK: znacznik faktycznie zniknal                               (jest: '1')
BLAD P-3: dziennik eksmisji jest CZYTANY i zawiera wpis o usunieciu
```

`K1 SKUTEK` daje **200 zamiast 401** — czyli defekt jest realny i kontrola go widzi.
`K1 SKUTEK 2` pokazuje drugą połowę: **odczyt skasował znacznik** (`jest: '0'`).

## Kierunek 0 (K3) — zrobiony wzorcowo i chcę to powiedzieć jasno

Konta **przewidziały PRZED pomiarem**, że K3 **nie będzie czerwona**, i podały powód (stary
kod też blokował przy `ts=0`, bo zero omijało gałąź TTL). Nazwały ją **„kontrolą jałowości,
nie dowodem naprawy"** i poprosiły, żeby tak ją liczyć. Pomiar to potwierdził.

**To jest przewidywanie falsyfikowalne postawione przed pomiarem** — dokładnie to, czego
brakowało w tym ekosystemie przez dwa dni. Nie mam do tego zastrzeżeń.

## Kierunek 3 (naprawa) — **NIEROZSTRZYGNIĘTY, i powód jest rozstrzygający**

Pytanie brzmi: czy jest ZIELONY test, który po naprawie powinien był paść. **Nie da się
zmierzyć, bo naprawy nie ma** — nie istnieje stan „po", z którym można porównać. Para
czerwone-przed / zielone-po jest **w połowie**.

## Czego NIE zmierzyłem i dlaczego — nieudana próba

**Bloku BOMBA na `isInvalidated` NIE URUCHOMIŁEM.** Suita nie jest PHPUnitem — jest bashowa
(`run.sh`, 531 linii, własny harness), a **cały jej stan idzie przez `docker compose exec`**
do kontenera z przypiętym obrazem, przy działającym Keycloaku z zaimportowanym realmem
(`command -v docker … || die`). Postawienie tego to stos na **współdzielonym demonie**,
czego zabrania S-1 pkt 2 i 3, a zlecenie powtarza. **Nie udaję pomiaru, którego nie zrobiłem.**

Odnotowuję też, że bomba na `isInvalidated` **i tak nie odpowiedziałaby na pytanie o pokrycie
naprawionej ścieżki** — bo ścieżka naprawiona nie istnieje.

---

# (B) Druga strona defektu — **ich WNIOSEK się broni; słowo „zamknięta" NIE**

## Wniosek z grepa — sprawdziłem z otwartym kontekstem i **POTWIERDZAM**

Prosiłeś, żeby nie brać ich `grep` za dobrą monetę. Otworzyłem kontekst — **cała metoda
`read()`** (`SessionStore.php:37-44`):

```php
public static function read(?string $id): ?array
{
    if ($id === null || $id === '') { return null; }
    try { $p = self::path($id); } catch (\RuntimeException) { return null; }
    if (!is_file($p)) { return null; }
    $d = json_decode((string) file_get_contents($p), true);
    return is_array($d) ? $d : null;
}
```

**Ani jednego** `time(`, `expire`, `created`, `ttl`, `filemtime` — potwierdzone dwoma
niezależnymi przyrządami po awarii trzeciego. Rekord sesji **nie ma pola wieku ani jego
sprawdzenia**. Ich zdanie się broni, a wniosek („jedno przesunięcie zegara otwierało dostęp
dwukrotnie") jest poprawny.

**Drobiazg wart odnotowania:** pole `created` dla przepływu logowania **istnieje**, ale
w wywołującym (`app/public/index.php:129`, `putFlow(… 'created' => time())`) — i `takeFlow()`
**go nie czyta**. Zapisany wiek, którego nikt nie sprawdza, to ta sama rodzina co dziennik,
którego nic nie czytało (ich P-3).

## Ale „zamknięta" jest nieprawdziwe

`SessionStore.php` **nie został tknięty** — ostatni commit `b830ccd`, starszy niż sam BLK-22.
Druga strona defektu jest **ZDIAGNOZOWANA, nie ZAMKNIĘTA**.

**Werdykt: ZŁA WAGA** — i kieruję go do zlecenia, nie do kont, bo ich dokument mówi wprost,
że naprawa czeka poza repozytorium.

---

# (C) `evictExpired` — **NIEROZSTRZYGNIĘTE: nie da się porzucić rozstrzygnięcia, którego nie ma**

Metody **nie ma w kodzie źródłowym** (0 trafień). Jedyne wystąpienia to `run.sh` (kontrola),
dokumenty i **`Fatal error: Call to undefined method`** w ich własnym surowym pomiarze.
Nie mam czego atakować, więc nie udaję, że zaatakowałem.

## Ale znalazłem TO SAMO zjawisko w kodzie ŻYWYM, i to jest właściwa odpowiedź na pytanie (C)

`SessionStore::destroyBy()` — kod dzisiejszy, nienaprawiony, na ścieżce back-channel logout:

```php
if ($matchSid || $matchSub) {
    $killed[] = basename($f, '.json');
    @unlink($f);                      // ← WYNIK NIE JEST ODBIERANY
}
return ['ids' => $killed, 'count' => count($killed)];   // ← liczy DOPASOWANIA, nie SKASOWANIA
```

**To jest dokładnie ta wada, którą `evictExpired` ma naprawiać** — „unlink się wykonał" wzięte
za „plik zniknął" — tyle że **w kodzie, który biegnie dziś**, a nie w metodzie, która ma
powstać. Przy niekasowalnym pliku `count` nadal zwróci 1.

**Na ich korzyść, i to mocno:** ich własna suita nazywa ten odczyt
**`DIAGNOSTYKA (nie dowod): aplikacja raportuje killed=1`** (run.sh:297), a dowodem czyni
osobną asercję skutku (401). **Wiedzą, że ta liczba nie jest dowodem, i napisali to
w nazwie testu.** To jest różnica między raportowaniem liczby a opieraniem się na niej.

---

# (D) Adaptacja u siebie — **MAM TEN SAM KSZTAŁT, W ŚCIEŻCE DOSTĘPU**

`RejestrSesji::uniewazniona()` — pytanie zadawane przy **każdym żądaniu**:

```php
->where('sid_skrot', hash('sha256', $sid))
->where('wygasa_at', '>', CarbonImmutable::now())    // ← TTL JAKO PRAWO WSTĘPU
->exists();
```

**Zmierzone, nie odczytane** (`WygasnieciePozwolenieTest`, czerwony w suicie):

```
⨯ CZERWONA: znacznik unieważnienia, który WCIĄŻ ISTNIEJE, przestaje blokować po wygaśnięciu
✓ kierunek odwrotny: znacznik ŚWIEŻY blokuje
✓ kierunek 0: BRAK znacznika nie blokuje
```

Wiersz **jest w bazie** (asercja to sprawdza), a `uniewazniona()` zwraca `false`. Oba kierunki
kontrolne zielone, więc czerwień dotyczy **wygaśnięcia**, nie zepsutego mechanizmu.

Broni nas dziś wyłącznie **założenie**, że próg (SSO Session Max) przeżyje każdą sesję —
założenie o cudzym zegarze i cudzej konfiguracji. A **retencja tej tabeli dziś nie biegnie**
(okres czeka na IOD), więc wiersz zostaje: **jest okno, w którym znacznik istnieje
i nie blokuje.**

**Naprawy nie wprowadzam w tej rundzie** — zmiana semantyki decyzji o dostępie dotyka
kontraktu BLK-22 i należy jej się własna pozycja z parą czerwone-przed / zielone-po.

**Pozostałe trzy progi z pytania, zmierzone:** `blokada_koszyka_minut`
i `waznosc_linku_platnosci_dni` to na razie **wyłącznie wartości konfiguracji**, bez kodu
decyzyjnego (F2/F3) — nie twierdzę, że są czyste, twierdzę, że nie ma tam jeszcze czego
mierzyć. `OcenaAnulacji` używa progu, ale **w drugą stronę**: minione okno czyni wynik
**surowszym**, nie przepuszczającym. To nie jest fail-open.

---

# Dwa znaleziska PONAD zlecenie

## 1 · Kontrola czterech wymagań magazynu NIE MA WYWOŁUJĄCEGO

`tests/ref-laravel/kontrakt-magazynu.sh` — 9 asercji sprawdzających **trwałość,
współdzielenie, czas życia i widoczność eksmisji**, czyli cztery wymagania z nagłówka
`InvalidationStore`. Zmierzone: **żadnego wywołania** ani w `run.sh`, ani w `ci-local.sh`.

**To jest ich `R6A-11`** — kontrola istnieje, jest dobrze napisana i **nikt jej nie
uruchamia**. U mnie ten sam kształt kosztował to, że retencja nie kasowała niczego przez
całą fazę. Waga jest tu wyższa, bo to jedyna kontrola pilnująca własności, których sam
magazyn plikowy **jawnie nie spełnia** (nagłówek mówi: nie spełnia (1) ani (2)).

## 2 · Twierdzenie o usunięciu, które nie zaszło

`ODPOWIEDZ-009.md:170` deklaruje: **„usunięte:** «nawet skasowanie znaczników nie wskrzesza
sesji… patrz test negatywny »wyczyść magazyn po wylogowaniu → nadal 401«"*.

`InvalidationStore.php:46-49` **nadal to zdanie zawiera**, razem z powołaniem na test o tej
nazwie — a testu o takiej nazwie w `run.sh` **nie ma**. To jest klasa D3 (twierdzenie
w komentarzu bez świadka), i to akurat ta jej odmiana, którą helpdesk wystawił mi jako
ZALECENIE SZKODLIWE: **deklaracja usunięcia jest w dokumencie, a tekst został w kodzie.**

---

# WERDYKTY

| pytanie | werdykt |
|---|---|
| (A) czy nowe kontrole umieją zaczerwienić | **POTWIERDZONE** — czerwone na nienaprawionym kodzie, dowód mocniejszy niż bomba |
| (A) kierunek 0 (K3) | **POTWIERDZONE** — plus przewidywanie falsyfikowalne postawione przed pomiarem |
| (A) kierunek 3 (naprawa) | **NIEROZSTRZYGNIĘTE** — nie istnieje stan „po" |
| (B) wniosek o `SessionStore` | **POTWIERDZONE** — broni się po otwarciu kontekstu |
| (B) „druga strona zamknięta" | **ZŁA WAGA** — zdiagnozowana, nie zamknięta; sprostowanie do zlecenia, nie do kont |
| (C) rozstrzygnięcie `evictExpired` | **NIEROZSTRZYGNIĘTE** — metody nie ma; zamiast tego wskazuję `destroyBy` w kodzie żywym |
| (D) czy mam to samo u siebie | **POTWIERDZONE U MNIE** — zmierzone, czerwone, nienaprawione |
| kontrakt magazynu bez wywołującego | **POTWIERDZONE, nowe** |

# Czego NIE sprawdziłem

- **Nie uruchomiłem suity kont** — wymaga Dockera, Keycloaka i realmu na współdzielonym
  demonie (S-1). Wszystkie liczby o nich pochodzą z ich zapisanych artefaktów i z odczytu kodu.
- **Nie zmierzyłem pokrycia naprawionej ścieżki** — nie istnieje.
- **Nie przeszukałem suit `security`, `mail`, `e2e`, `broker`, `zamkniecie`** pod kątem asercji
  o sesjach: mają trafienia słownikowe, ale ich funkcje asertujące nie pasowały do wzorca
  ekstraktora. **Przyrząd tam nie sięgnął** — nie twierdzę, że nic tam nie ma.
- **Nie porównałem** treści `ERRATA-KONTRAKT-2026-08-09.md` z kontraktem.

# Własne nieudane próby obalenia

1. **„Ich wniosek o `SessionStore` to grep bez kontekstu, więc jest za mocny"** — otworzyłem
   kontekst całej metody `read()`. **Obalenie nieudane: wniosek się broni.**
2. **„Rozstrzygnięcie `evictExpired` da się gdzieś porzucić"** — nie znalazłem, bo **metody
   nie ma**. Próba bezprzedmiotowa, nie udana.
3. **„Uruchomię bombę na `isInvalidated` na kopii poza ich repo"** — porzucone po pomiarze:
   suita jest bashowa i wymaga całego stosu, więc kopia nie wystarczy.

# Zakazy

Zero `main`, merge, deploy · **zero zapisu w cudzych repozytoriach** (wyłącznie `grep`, `sed`,
`git log/status`, ścieżki bezwzględne, **ani jednego `cd`**) · ich kodu nie naprawiałem ·
ich stosu nie stawiałem · nic poza fundację. **Sprzeczności: brak.**
