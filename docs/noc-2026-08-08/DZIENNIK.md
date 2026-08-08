# Dziennik nocy z 8 na 9 sierpnia 2026

Zapis chronologiczny. Godzina, co robiłem, co ZMIERZYŁEM, wynik. Surowe liczby.
Pisany na bieżąco, po każdej ukończonej pozycji — nie zbiorczo na końcu.

Punkt wyjścia: gałąź `faza-1-retencja`, HEAD `49131d8`, drzewo czyste.
Bramka wieczorem: CZERWONA, 1 nieudany krok z 22 (noga 1 — zamierzony).

---

## 23:34 — orientacja w stanie

Przeczytane: `CLAUDE.md`, `PLAN-FAZ.md` (CURRENT WORK), `WYTYCZNE-PRACY.md`
(sekcje o weryfikacji, przyrządach, regule C1, gałęzi zdegenerowanej),
`backend/tests/Feature/OdebranieRoliTest.php`, `backend/app/Tozsamosc/*`,
nagłówek `skrypty/bramka.sh`.

Zmierzone:

- `git rev-parse HEAD` → `49131d8d0bbe73991ea4283b7bd631fc17b0b751`
- `git status --short` → pusto (drzewo czyste)
- `docker info` → serwer 29.3.1, kontenerów 60, obrazów 55
- Chodzące CUDZE stosy (nie dotykam): `helpdesk-weryf-bd60adb-*` (10 kontenerów),
  `helpdesk-zammad-*`, `control-plane*`, `niepodzielni-hub*`, `np-*`, `trydive*`
- Chodzące MOJE stosy: `gabinet-*` (deweloperski, 10 h), `gabinet-perturbacje-*` (9 h)
- Porty planowane dla rundy 6 (`netstat`): 8107, 55461, 56407, 8108 — wszystkie WOLNE

## 23:40 — RUNDA 6 ZLECONA (zadanie główne)

Runda przypięta do SHA `49131d8` — identyfikator nazywa ZDARZENIE, nie stan
bieżący, więc się nie starzeje (reguła z WYTYCZNE-PRACY).

Zlecona DWÓM świeżym agentom-weryfikatorom, każdy na WŁASNYM czystym klonie.
Ja jej nie wykonuję — pisałem ten kod, więc nie mogę go weryfikować.

**Dlaczego dwóch, a nie jeden**: zakres a–f nie mieści się w jednej sesji bez
utraty jakości. Podział przebiega po linii „potrzebuje żywego stosu / nie
potrzebuje", żeby dwa ciężkie stosy dockerowe NIE biegły równolegle na maszynie
z 60 kontenerami. Współbieżne obciążenie to znany generator FAŁSZYWEJ CZERWIENI
(timeouty sond) — a runda skażona fałszywą czerwienią jest bezwartościowa.

- **Weryfikator A („stos")** — klon `/d/tmp/gabinet-r6a`, projekt compose
  `gabinet-r6a`, porty 8107 / 55461 / 56407, sekrety budowane przez bramkę
  z `.env.example` (nigdy `.env` dewelopera).
  Zakres: bramka (surowe liczby) · **(b)** zielony z niewłaściwego powodu —
  metodą USUŃ MECHANIZM I SPRAWDŹ, CZY DALEJ ZIELONY · **(c)** próba OBEJŚCIA
  wąskiego gardła §2 (nie odczyt kodu — atak) · **(f)** rozdzielenie
  przestrzeni kluczy Redisa na ŻYWYM Redisie + pytanie o eksmisję jako
  własność INSTANCJI · **(g)** pytanie obowiązkowe rundy.
- **Weryfikator B („analiza")** — klon `/d/tmp/gabinet-r6b`, BEZ dockera.
  Zakres: **(a)** przegląd WSZYSTKICH dyskryminatorów pod gałąź zdegenerowaną,
  z tabelą „jakie światy dają tę wartość" · **(d)** czy migawki nogi 1 mierzą
  to, co deklarują + PROJEKT odczytu rozstrzygającego, jawnie NIEWDRAŻANY ·
  **(e)** czego jeszcze nie pokrywają podmienione sterowniki, jako RÓŻNICA
  wobec D-2026-08-08-27 · **(g)**.

Oba zlecenia zawierają twarde zakazy: zero zmian w drzewie roboczym wykonawcy,
zero commitów i pushy, zero dotykania cudzych stosów, **zero kasowania obrazów**
(`prune` kasuje po wieku i braku referencji, nie po nazwie — zabrałby obraz spod
cudzego stosu). Zakres sprzątania: kontenery + wolumeny własnego projektu + klon.

Treść obu zleceń zapisana obok: `ZLECENIE-RUNDA-6.md`.

Status: oba biegną. Wynik odbiorę i zapiszę — **nie naprawiam znalezisk tej
samej nocy** (naprawa autorem, bez rundy, byłaby rano stertą zmian bez pokrycia).

## 23:55 — Z2 ruszone równolegle (bo czekanie nie jest pracą)

Weryfikatorzy pracują na własnych klonach, więc moja praca w drzewie roboczym
im nie przeszkadza, a runda jest przypięta do SHA `49131d8` i moje zmiany jej
nie unieważniają. Zamiast czekać — Z2 (uzgodnienie dokumentów z rzeczywistością).

**Pomiar suity** (mój stos deweloperski, baza `gabinet_test`):

```
$ docker exec gabinet-app ./vendor/bin/pest
  Tests:    1 failed, 180 passed (640 assertions)
  Duration: 44.53s
  FAILED  OdebranieRoliTest > NOGA 1 …  Expected 401 but received 200
```

- CURRENT WORK twierdzi „180 zielonych, 1 czerwony, 640 asercji" → **PRAWDA**.
- CURRENT WORK twierdzi „30 scenariuszy perturbacji" → **PRAWDA**
  (`grep -oE "^p_[a-z0-9_]+\(\)" skrypty/perturbacje.sh | wc -l` → 30).
- Jedyny czerwony to noga 1 → **PRAWDA**, pozostałe 12 testów w tym pliku zielone.

**Uwaga o moim własnym przyrządzie — złapane na sobie.** Pierwszy pomiar
uruchomiłem jako `docker exec -T …`; `-T` nie istnieje w `docker exec` (to flaga
`docker compose exec`). Polecenie wypisało pomoc i NIC nie zmierzyło, a harness
zaraportował „exit code 0". Drugi przebieg poszedł przez `| tail -45`, więc kod
wyjścia znowu należał do `tail`, nie do pesta. Dwa razy z rzędu „0" było zgodne
ze światami „wszystko zielone" ORAZ „pomiar w ogóle się nie wykonał". Dokładnie
badana dziś klasa — tym razem u mnie. Wniosek czytam z TREŚCI wyjścia, nie z kodu.

## 00:20 — NAPRAWA PRZYRZĄDU (1): podłogi bramki (znalezisko N-2)

`MINIMUM_TESTOW` 170 → **180**, `MINIMUM_ASERCJI` 590 → **635**, przy stanie
181 / 640. Powód: zapas 11 testów mieścił skasowanie w całości 10 z 17 plików
kontrolnych, w tym `ObietniceKomentarzyTest` (kontrola NAD kontrolami).
Komentarz nad stałą obiecywał „TUŻ POD stanem bieżącym" i był nieprawdziwy
wobec własnej wartości. Szczegóły i rozkład testów na pliki: `ZNALEZISKA.md`, N-2.

## 00:35 — NAPRAWA PRZYRZĄDU (2): perturbacje przestały psuć (znalezisko N-3)

Najpoważniejsze znalezisko własne tej nocy i **spowodowane moją własną zmianą
z wieczora**: commit `cdc6fbb` przemianował `$konta` → `$tozsamosc`, przez co
dwie perturbacje przestały cokolwiek podmieniać — a mimo to meldowały sukces.

Trzy zabezpieczenia zawiodły po kolei (dowody w `ZNALEZISKA.md`, N-3):
brak sprawdzenia kodu wyjścia perturbacji · dowód mutacji w formie negatywnej
(gałąź zdegenerowana) · oczekiwana czerwień dostarczana przez niepowiązaną
czerwień nogi 1.

Naprawa jest STRUKTURALNA, nie jest łataniem dwóch napisów:

1. **`perturbuj()` sprawdza kod wyjścia.** Nieudana mutacja liczy się jako
   `NIEUDANE` i ustawia `MUTACJA_ZERWANA=1`. Jeden punkt, chroni 30 scenariuszy.
2. **`dowod_mutacji()` odmawia orzekania**, gdy `MUTACJA_ZERWANA=1` — dowód nie
   może zaświadczać o mutacji, której nie było.
3. **Nowy `dowod_zniknieciem()` — dowód z ODCZYTEM BAZOWYM.** Pyta nie „czy
   tekstu nie ma", tylko **„czy tekst BYŁ, a potem ZNIKNĄŁ"**; odczyt bazowy
   bierze z kopii sprzed mutacji, którą i tak robi `zachowaj`. To rozróżnia
   świat „mutacja usunęła tekst" od świata „tekstu nigdy tam nie było".
   Gdy wzorca nie było w kopii, komunikat brzmi wprost: „PERTURBACJA ROZJECHAŁA
   SIĘ Z KODEM" — czyli tej nocy zapaliłby się od razu i z właściwą przyczyną.
4. Wszystkie **5 dowodów w formie negatywnej** przepisane na `dowod_zniknieciem`.
   Zmierzone po zmianie: `grep -c 'bash -c "! grep -q'` → **0**;
   `grep -c "dowod_zniknieciem "` → **5**; `bash -n skrypty/perturbacje.sh` → OK.
5. Dwa nieaktualne wzorce podmiany w `perturbuj.py` uzgodnione z kodem bieżącym
   (`$tozsamosc->dane`, `$tozsamosc->sid()`).

Naprawiam to mimo reguły „nie naprawiaj przedmiotu", bo to jawny WYJĄTEK: zepsuty
był sam przyrząd. Bez tego runda 7 mierzyłaby własną bezczynność.

## 00:50 — WERYFIKACJA naprawy przyrządu (nie deklaracja, pomiar)

Naprawa przyrządu, której się nie sprawdziło, jest kolejną obietnicą bez pokrycia.
Trzy pomiary:

**1. Czy mutacje znowu wchodzą w życie** (po każdej `git checkout --`, drzewo czyste):

```
$ python3 skrypty/perturbuj.py role-zamrozone      → EXIT=0, git diff: 1 insertion(+), 3 deletions(-)
$ python3 skrypty/perturbuj.py uniewaznienie-po-sid → EXIT=0, w pliku: 78: if (false) {
$ git status --short                               → 0 zmian (stan przywrócony)
```

**2. Czy nowy dowód ROZRÓŻNIA światy** — wyłuskałem samą funkcję `dowod_zniknieciem`
do osobnego pliku i podałem jej trzy spreparowane sytuacje. To kontrola ścieżką
NIEZALEŻNĄ od perturbacji (reguła C1: kontrola nie może dzielić mechanizmu ze
swoim przedmiotem):

```
świat I   (tekst był w kopii, znikł z pliku = mutacja weszła)   → rc=0 ZALICZONE  ✓
świat II  (tekstu NIGDY nie było w kopii = perturbacja rozjechana) → rc=1 ODRZUCONE ✓
          komunikat: „PERTURBACJA ROZJECHAŁA SIĘ Z KODEM: wzorca … NIE BYŁO … przed mutacją"
świat III (tekst nadal w pliku = mutacja nie usunęła)           → rc=1 ODRZUCONE ✓
NIEUDANE=2 (oczekiwane 2)
```

**Świat II to dokładnie dzisiejsza awaria.** Stary dowód dawał w nim „prawda",
nowy odrzuca i nazywa przyczynę. Czyli przyrząd nie tylko został naprawiony, ale
UMIE ZAŚWIECIĆ CZERWONO na tę konkretną awarię — a to jest jedyny sensowny
dowód naprawy kontroli (D-0013).

**3. Składnia:** `bash -n skrypty/perturbacje.sh` → OK.

Czego ta weryfikacja NIE obejmuje, i mówię to wprost: nie uruchomiłem pełnego
zestawu 30 perturbacji na żywym stosie (wymaga własnego projektu compose i
kilkudziesięciu minut, a maszyna obsługuje teraz weryfikatora rundy 6).
Pełny przebieg zostaje jako pierwsza rzecz do zrobienia rano — wpisane
do `PODSUMOWANIE.md`.
