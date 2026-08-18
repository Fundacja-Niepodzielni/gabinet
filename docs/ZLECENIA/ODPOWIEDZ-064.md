# ODPOWIEDZ-064 — runda 8 zamknięta: DWA znaleziska

**Od:** sesja WERYFIKATOR (runda 8) · **Do:** architekt · **Data:** 18.08.2026
**W odpowiedzi na:** `ZLECENIE-064` · **SHA mierzone:** `179c05c`
**Raport pełny:** `docs/rundy/RUNDA-8-RAPORT.md`

---

## 0. Jednym zdaniem

Bramka zielona i zgodna z KAŻDĄ liczbą deklaracji (22/22, 289/2119 RÓWNO z podłogami,
48 perturbacji, znacznik zdjęty, SHA zamrożone); zamknięcia R7-1…R7-9 + O-6b + D-2 + D-4
bronią się pomiarowo — ale **znalazłem dwa defekty kontroli klasy „o krok dalej"**,
więc **F1/F0 pozostają OTWARTE**. Runda nie kończy się zerem.

## 1. Pomiary rozstrzygające

```
BRAMKA OK — 22 kroków, 0 nieudanych           (przebieg OD ZERA, klon r8, kod 0)
289 testów, 2119 asercji, 2 pominięte          (podłogi RÓWNO 289 / 2119)
PERTURBACJE OK — 48 kontroli (pominięte: 0)    (klon r8p, kod 0)
znacznik .przebieg-pomiarowy po bramce:  ZDJĘTY
git diff 179c05c..HEAD (kod): PUSTY ; commitów po 7f4c65f: BRAK
```

Pomiar rozstrzygający obu znalezisk: świeży subagent, klon r8v, bez mojego kontekstu.

## 2. Dwa znaleziska (pełny opis + odtworzenie w raporcie §3)

**R8-1 (WYSOKA) — siatka POMIAROWA D-1b ślepa na atak z nazwą pola SPOZA baterii.**
Siatka reklamuje pomiar SKUTKU „niezależnie od sposobu", ale wykrywa zapis tożsamości
tylko dla 9 nazw z baterii (`SiatkaPomiarowaTozsamosciTest.php:138`). Ten sam mechanizm
czytający sekret pod nazwą `zaklecie` (spoza baterii): siatka `3 passed`, deklaratywne
`9 passed`, pełna suita `289 passed`, Larastan/Pint zielone — a `session()->has('konta')`
= **TAK** (logowanie poza OIDC, złamana §2). Deklarowana sieć bezpieczeństwa
(`ODPOWIEDZ-062` §8: „mechanizm pod nazwą spoza baterii łapie perturbacja `d1b`") jest
**nieprawdziwa**: perturbacja `d1b` (`perturbuj.py:235`) czyta `nazwa_wyswietlana` —
nazwę Z BATERII, więc nie dowodzi pokrycia spoza baterii. Klasa „instrument pytający
o co innego, niż twierdzi" (R7-1) przeniesiona o krok: pomiar SKUTKU wrócił po cichu
do pytania o SPOSÓB (nazwę wejścia).

**R8-2 (WYSOKA) — egzekutor blokady wysyłki §10 ślepy na WYREJESTROWANIE providera.**
`BlokadaWysylkiTest` buduje `AppServiceProvider` ręcznie (`:44`), więc widzi, że metoda
wpina sterownik i że `boot()` ją woła — ale NIE, że framework ładuje ten provider.
Opróżnienie `bootstrap/providers.php`: realny app `mail.default=smtp` (blokada §10
MARTWA), `BlokadaWysylkiTest` `2 passed`, suita `289 passed`. Kontrast: to samo pytanie
dla middleware (`ZasiegUniewaznieniaTest` przez `gatherRouteMiddleware`) łapie
wyrejestrowanie (`2 failed`). Klasa R7-3 (mechanizm bez pokrycia wpięcia) o piętro wyżej:
„metoda wpięta w `boot()`, ale `boot()` niewpięty we framework".

## 3. Co odrzuciłem po pomiarze (żeby nie mnożyć fałszywych)

- Domyślna wartość `gabinet.blokada_wysylki` — NIE nawrót R7-2: `.env.example:136`
  przypina `GABINET_BLOKADA_WYSYLKI=true`, więc podmiana domyślnej nic nie zmienia.
- Rejestr `docker compose` nierekurencyjny — dziś nic nie ucieka; latentna wąskość,
  nie znalezisko (raport §5.3).

## 4. Uwaga proceduralna

Zakaz commitowania utrzymany — jedyne zapisy tej sesji: `RUNDA-8-RAPORT.md` i ten plik.
Wszystkie stosy r8/r8b/r8p/r8v zgaszone `down -v`; stos dewelopera `gabinet` nietknięty.
