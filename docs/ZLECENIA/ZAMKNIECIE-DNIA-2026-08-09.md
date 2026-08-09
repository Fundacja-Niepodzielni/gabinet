# ZAMKNIĘCIE DNIA — 09.08.2026

**Ten plik czytasz jako pierwszy.** Nie trzyma stanu — mówi, **gdzie stan zmierzyć**
i **czego nie powtarzać**.

---

## 0 · Zanim cokolwiek zrobisz

```
git -C d:/KOD/Niepodzielni/gabinet log --oneline -10
git -C d:/KOD/Niepodzielni/gabinet status
docker compose exec -T app ./vendor/bin/pest
```

**Każda liczba w tym pliku ma datę i jest zapisem z godziny, nie stałą.**
09.08 liczba testów zmieniła się **trzy razy w ciągu jednego wieczoru** (223 → 232 → 236).

## 1 · Stan na koniec dnia (09.08, ~23:20)

| co | wartość | zmierzone |
| --- | --- | --- |
| gałąź | `faza-1-retencja`, `HEAD == origin` | 09.08 wieczór |
| testy | **236 zielonych · 1 czerwony (noga 1, ZAMIERZONY) · 2 pominięte · 1936 asercji** | 09.08 ~23:0x |
| podłogi bramki | **236 / 1936** | 09.08 wieczór |
| Pint | czysto | 09.08 wieczór |
| bramka pełna | **CZERWONA — 1 krok z 22** (krok `[19] testy`, przez nogę 1) | runda 6, starsze |

**Jedyny czerwony test jest zamierzony** — noga 1, wada **przyrządu**, nie systemu
(przyczyna ustalona, patrz `PLAN-FAZ.md`). **Nie ścigaj go jako nowego defektu.**

## 2 · Zamknięte dziś w nocy — NIE ROBIĆ DRUGI RAZ

| pozycja | co zamknięte | dowód |
| --- | --- | --- |
| `ODPOWIEDZ-032` | kontrola unieważnienia **jest middlewarem**; wyjątki jako dane | `ZasiegUniewaznieniaTest`, `NowaTrasaJestChronionaTest` |
| `ODPOWIEDZ-041` | **studium SMSAPI** — dokumentacja przeczytana, nic nie zbudowane | `docs/ZLECENIA/ODPOWIEDZ-041.md` |
| `ODPOWIEDZ-042` | zwolnienia z retencji: powód + warunek znoszący; **4 z 10 obalone** | `ZwolnieniaRetencjiTest` |

**`PODJETO-043` (trap bez `INT`/`TERM`) zamknięte jako NIEZROBIONA** — wzięte kilka minut
przed końcem dnia, **zero pracy włożonej, zero do odzyskania**. Wolne do wzięcia od zera.

## 3 · ⚠ CZEKA NA CZŁOWIEKA — nie na sesję

**Nie da się tego ruszyć samodzielnie. Nie zaczynaj od tego, ale przypomnij o tym wcześnie.**

| co | na kogo | dlaczego to blokuje |
| --- | --- | --- |
| **wariant nazwy nadawcy SMS** | **WŁAŚCICIEL** | „Niepodzielni" ma 12 znaków, limit **11** — **wniosek nie może być złożony bez wyboru** |
| **SMS Authenticator kontra `D-EKO-001`** | **ARCHITEKT** | kod po stronie operatora = dowód tożsamości pacjenta u dostawcy SMS |
| **`failed_jobs` → rejestr retencji** | **WŁAŚCICIEL** (zmiana modelu) | `payload` + `exception` **bezterminowo**; przy SMS/e-mailach to kwestia czasu |
| `jobs`, `konfiguracja_regul.autor` | **WŁAŚCICIEL** | to samo, mniej pilne; `autor` gryzie się z niezmiennością dziennika |
| **okresy retencji** | **IOD** (`D-EKO-009`) | mechanizm gotowy, **kasuje ZERO tabel** |
| limit niskopłatnych — wartość startowa | **ZARZĄD** | `CLAUDE.md` pkt 14 |
| kontrakt `KONTRAKT-HUB-SUMMARY.md` | **hub** | **nie istnieje**; nie implementować wg domysłu |

## 4 · Zaległościnik — wg ILOCZYNU WAGI I OSIĄGALNOŚCI

**Kolejność jest wnioskiem, nie kolejnością wpisywania.**

### Wysoki iloczyn — bierz stąd

1. **`failed_jobs` jako archiwum danych osobowych** — waga wysoka (RODO), osiągalność wysoka
   **po decyzji właściciela**. Do tego czasu: **przygotuj wpis rejestru**, nie zmieniaj modelu.
2. **`trap` bez `INT`/`TERM`** w `skrypty/perturbacje-powtarzalne.sh:39` — waga średnia,
   **osiągalność natychmiastowa**. Ctrl-C zostawia zmutowany kod w drzewie; 09.08 **straciłem
   już pracę** przez sprzątanie perturbacji.
3. **Naprawa 7 wzorców allowlist, które nie rozróżniają** (runda 2) — waga wysoka (kontrola
   przyczyny czerwieni jest dziś częściowo pozorna), osiągalność średnia. Zapadka pilnuje sufitu.
4. **Gałąź bazowa `odczyt-przyczyn.py` pokrywa 13 z 29 wywołań** — waga średnia, osiągalność wysoka.

### Średni iloczyn

5. **`horizon` i `docs/api` są NIEZMIERZONE, nie czyste** — wyłączone ze skanu zasięgu
   unieważnienia. Waga zależy od tego, co tam wystawiamy.
6. **Brak kontroli, czy `dc()` nadal eksportuje cztery zmienne** — dług nazwany 09.08.
7. **`ZadanieRetencji` — klucz nie-całkowity** — refaktor odłożony świadomie.
8. **Klasa 3 otwarta: `R6B-8`, `R6B-6`.**

### Niski iloczyn / zablokowane

9. **`R6B-1` i `N-12` zależą od nogi 1** — nie ruszaj przed naprawą przyrządu.
10. **Przeprojektowanie `TwierdzeniaKomentarzyTest`** (kontrola D3, zdjęta z bramki: 14 obejść
    na 15 prób) — waga wysoka, ale **osiągalność niska**: wymaga wiązania świadka z ROLĄ tekstu.

## 5 · ⚠ HIPOTEZY OBALONE — z pomiarem, który je obalił

**Ta sekcja istnieje po to, żeby jutro nie stracić czasu na tę samą ślepą uliczkę.**

| co twierdziłem | co obaliło | wniosek |
| --- | --- | --- |
| „system nie wskrzesza tożsamości, bo się broni" | migawki magazynu; **potem pomiar kontrolny po przebudowie dał liczby IDENTYCZNE** | wniosek **OBALONY przeze mnie samego**; refresh token mieszka **wewnątrz** sesji, więc odświeżanie **nie ma z czego** wskrzesić. **Noga 1 pary BLK-22 pozostaje NIEROZSTRZYGNIĘTA** |
| „runda 1 zamyka klasę allowlist" | pomiar: usunęła 1 wzorzec degenerujący, **dodała 5** | sprostowanie w `ZNALEZISKA.md`; nie ufać deklaracji rundy bez pomiaru |
| „30 scenariuszy perturbacji = pokrycie 30" | 2 mutacje MARTWE, 5 scenariuszy **nie może paść** (plik trwale czerwony przez nogę 1) | **nie cytuj „30 scenariuszy" jako miary pokrycia** |
| „test POZYTYWNY dowodzi skasowania sesji" (`R6B-2`) | pomiar: przechodzi przy sesji **NIEUSUNIĘTEJ** — 401 pochodził ze znacznika | kontrola dzieliła mechanizm z przedmiotem (`C1`) |
| „sonda HTTP wykryje nasłuch Postgresa" (`R6B-11`) | pomiar na pętli zwrotnej: HTTP **nie wykrywa**, TCP wykrywa | sonda portowa zamiast HTTP |
| „`sessions` nie ma danych osobowych" | odczyt kolumn: **`ip_address`, `user_agent`** | zwolnienie prawdziwe **wyłącznie** przez sterownik `redis`; warunek teraz **egzekwowany** |
| „`config('session.driver')` mierzy nasz świat" | `phpunit.xml` wymusza `array` — **kontrola mierzyła świat testowy** | **klasa 3**; czytać domyślkę z pliku + gałąź dynamiczna |
| „skaner zasięgu widzi middleware tras" | grupy wpisuje **jądro HTTP**, nie konsoli — `gatherRouteMiddleware` oddawał napis `web` | **zerowe pokrycie wyglądałoby jak pełne**; złapała to kontrola pozytywna |
| „budowa SMS nie czeka na wniosek właściciela" | prawda, **ale węziej**: `test=1` nie wysyła, więc **nie ma raportu doręczenia** | domknięcie ścieżki raportów czeka na pierwszą prawdziwą wysyłkę |
| „zgody marketingowe nas nie dotyczą, bo to transakcyjne" | regulamin: operator **nie kwalifikuje** — przerzuca ocenę na klienta (6.1.2, 6.7) | **to nie jest „nie dotyczy nas"** — pytanie do IOD |

## 6 · Reguły warsztatowe wypracowane 09.08 — stosuj od jutra

1. **Kontrola pozytywna ORAZ negatywna.** Pierwsza łapie przyrząd **martwy**, druga przyrząd
   **mierzący co innego**. Dwa razy tego dnia druga złapała to, czego pierwsza nie mogła.
2. **Koszt wyjątku = koszt zgodności.** Gdy droga wyjścia jest tańsza, staje się drogą domyślną.
3. **Perturbacje cofaj przez `cp` z kopii — NIGDY `git checkout --`.** 09.08 straciłem tak pracę.
4. **Komunikaty commita pisz do pliku i podawaj przez `-F`.** Cudzysłów w `-m` rozwalił commit,
   a `push` zameldował **OK**, bo nie było czego wypychać. **Push dowodź:**
   `git rev-parse HEAD origin/<gałąź>`.
5. **Pozycję zamyka PLIK, nie meldunek w oknie** (`S-3`): `ODPOWIEDZ` → commit/push → `PODJETO`
   → dopiero meldunek.
6. **Brak informacji ≠ informacja o braku.** Każde studium kończ sekcją „czego nie znalazłem".

## 7 · Od czego zacząć jutro — jedno zdanie

> **Zmierz stan (`git log`, `pest`), przypomnij właścicielowi o wyborze nazwy nadawcy SMS
> i decyzji o `failed_jobs`, a sam weź `trap` bez `INT`/`TERM` w
> `skrypty/perturbacje-powtarzalne.sh:39` — najwyższy iloczyn wagi i osiągalności bez cudzej
> decyzji.**

---

**S-2 („nigdy nie stoisz") zawieszone na dziś przez `ZLECENIE-042`. Od jutra obowiązuje znowu.**
