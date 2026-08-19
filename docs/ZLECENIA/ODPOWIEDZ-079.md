# ODPOWIEDZ-079 — szóste piętro zamknięte, zakres pliku decyzji naprawiony

**Od:** sesja KOD-F1 · **Do:** architekt · **Data:** 19.08.2026
**W odpowiedzi na:** `ZLECENIE-079` (§1 podstawa zaufania, §2 `docs/DECYZJE.md`, §3 pomiar `final`)
**SHA zamrożone (KOD):** `7a8c44d8dca055d9ad9af1efcd1e5eaed7140c51` („7a8c44d")

---

## 1. Surowe wyjście — bramka OD ZERA, PO commicie, BEZ POTOKU

```
KOD_BRAMKI=0
    WYKONANO 318 testów (podłoga: 318)
    sprawdzono 2251 asercji (podłoga: 2251)
8:54AM INF 161 commits scanned.
8:54AM INF no leaks found
BRAMKA OK — 22 kroków, 0 nieudanych
```

Perturbacje, pełny zestaw:

```
KOD_PERTURBACJI=0
PERTURBACJE OK — 61 kontroli udowodniło, że umie zaświecić czerwono (pominięte: 0)
zmierzone niezależnie: 44 nagłówki „=== PERTURBACJA", 61 znaków ✓, 0 znaków ✗
```

Podłogi **318 / 2251 — RÓWNO**. Kod wyjścia odczytany wprost, nie z potoku — to
zmiana procedury z `ODPOWIEDZ-078` §6(3), którą podniosłeś do standardu.

---

## 2. §1 — podstawa zaufania pochodzi z konfiguracji

### Co się zmieniło

```
PRZED   RoszczeniaZweryfikowane::zTokenu(string $jwt, array $wymagania)   PUBLICZNA
        → wołający podawał `jwks`, czyli MATERIAŁ KLUCZA

PO      ::zIdTokenu(string $jwt, KontaOidc $oidc, string $nonce)          publiczna
        ::zAccessTokenu(string $jwt, KontaOidc $oidc)                     publiczna
        ::zTokenu(string $jwt, array $wymagania)                          PRYWATNA
```

`issuer`, `jwks`, `audience`, `typ`, `tolerancja` biorą się teraz z `KontaOidc`.
Kontroler przestał w ogóle pobierać JWKS — materiał klucza nie jest już jego
sprawą. `OdswiezanieSesji::przelicz` miał tę samą wadę i został zmieniony tak samo.

### Twoje sformułowanie, które przyjmuję jako właściwe rozpoznanie

> ściana typu, która przyjmuje materiał klucza od wołającego, chroni **kształt,
> nie prawdę**

Dokładnie to pokazał pomiar: obiekt nazywał się „zweryfikowany", nie mówiąc,
**wobec czego**. Nazwa klasy była jedyną treścią tego słowa.

### Kontrole odbioru — wszystkie cztery

**§1.1 NEGATYWNA — mechanizm podający własny materiał klucza.** Droga została
zamknięta **typem dostępu**, nie kontrolą: `zTokenu` jest prywatne, więc
wywołanie spoza klasy nie przechodzi statyki (`Call to private static method
zTokenu()`) i rzuca w czasie działania. Zmierzone dodatkowo perturbacją stałą
`wymagania_wolajacego`, która zdejmuje prywatność:

```
p_wymagania_wolajacego  → CZERWIEŃ z „PUBLICZNA METODA OBIEKTU ROSZCZEŃ
                          PRZYJMUJE TABLICĘ"
```

Kontrola pyta o **istotę, nie nazwę**: dowolna publiczna metoda przyjmująca
tablicę jest znaleziskiem, bo tą drogą wchodzi podstawa zaufania. Poprzednia
wersja pytała tylko o metody **oddające** obiekt — i właśnie dlatego nie
widziała `zTokenu`.

**§1.2 POZYTYWNA — legalna ścieżka działa.** Logowanie i odświeżanie: pełna
suita 318 zielonych, w tym `LogowanieTest`, `OdebranieRoliTest` (przeliczanie
ról po odświeżeniu) i test pozytywny w `TypTozsamosciTest`.

**§1.3 PRZYRZĄDU — podstawione IdP MUSI dawać tożsamość.** Wpisana wprost
w test obcego klucza: po odrzuceniu tokenu napastnika sprawdzam, że token
podpisany NASZYM kluczem **przechodzi**. Bez tego „nie da się zalogować"
udawałoby bezpieczeństwo.

**DOWÓD SKUTKU (najmocniejszy pomiar tej naprawy).** Token o poprawnym
kształcie — właściwy wystawca, właściwa audiencja, świeże czasy, prawidłowy
`alg`, nasz `kid` — podpisany **cudzym kluczem**:

```
zAccessTokenu(token podpisany obcym kluczem)  →  ok = false
                                                 roszczenia = null
                                                 kontrole['signature'] = 'fail'
```

Sprawdzam, że odmowa przychodzi z **kontroli podpisu**, a nie z dowolnego
innego ogniwa — inaczej mierzyłbym coś innego, niż mi się wydaje.

---

## 3. §1.4 „krok dalej" — co jeszcze rozstrzyga o zaufaniu

Cztery rzeczy. Trzy pokryte, jedna nazwana jako granica.

**(a) `nonce` — POKRYTY, zmierzony.** Został parametrem (`string`), bo pochodzi
z NASZEJ sesji. Twierdzenie „wektor nonce-z-żądania łapie druga linia" nie jest
domysłem — uruchomiłem je:

```
$nonce = Typy::napis($request->query('nonce'));  w powrot()
→ WaskieGardloZapisuTozsamosciTest: 1 failed
  „CALLBACK OIDC CZYTA Z ŻĄDANIA COŚ SPOZA SWOJEGO KONTRAKTU"
```

Warstwa 3 zna kontrakt `code`/`state`; `nonce` do niego nie należy. Dlatego
**nie** budowałem trzeciego obiektu wartości: istniejąca warstwa pokrywa ten
wektor, a nowy typ byłby kontrolą bez przedmiotu.

**(b) `kid` z NIEZWERYFIKOWANEGO nagłówka — POKRYTY.** To nadawca tokenu
wybiera, którego klucza szukamy. Ale **zbiór kluczy jest nasz**, więc token
podpisany obcym kluczem odpada na `signature` (dowód wyżej). Osobno broni tego
`WzmacniaczZadanTest` — nieznany `kid` nie zmusza nas do chodzenia do sieci.

**(c) Pamięć podręczna JWKS — POKRYTA cudzą kontrolą.** „ZATRUTY cache JWKS nie
odbudowuje się sam w oknie bramki" (`WzmacniaczZadanTest`). Nie dublowałem.

**(d) ZEGAR SYSTEMOWY — GRANICA NAZWANA, NIEPOKRYTA.** `exp` i `iat`
rozstrzygają o ważności tokenu, a rozstrzyga je zegar hosta. Przesunięty zegar
przedłuża ważność tokenu odwołanego albo odrzuca ważny. **Nie mierzę tego
i mówię to wprost** — kontrola zależna od zegara zaczyna padać sama z siebie,
więc wprowadzenie jej jest decyzją, nie oczywistością. Zgłaszam jako rzecz do
Twojego rozstrzygnięcia, nie zaciągam jako dług po cichu.

---

## 4. §3 — trzecie twierdzenie ZMIERZONE

Kazałeś zmierzyć „`final` bez pomiaru jest deklaracją". Zmierzone:

```
RoszczeniaZweryfikowane  →  isFinal() = true
TozsamoscSesji           →  isFinal() = true
perturbacja `roszczenia_final` (zdjęcie `final`) → CZERWIEŃ
```

Powód, dla którego to ma znaczenie: prywatny konstruktor broni przed `new`, ale
nie przed klasą potomną, która doda własny publiczny i **poda się za ten typ**.
Kontrola „jedyne `new` w pliku klasy" tego nie widziała, bo mierzyła jeden plik.

Dwa pozostałe twierdzenia z §10 (`?string $refreshToken`, `wszystkie()`)
zostawiam rundzie bez zmian — tak jak zapisałeś.

---

## 5. §2 — `docs/DECYZJE.md`: zakres naprawiony, ale jest haczyk

Dopisałem `docs/DECYZJE.md` do `.zakres-sesji` i zacommitowałem oba wpisy:

- `D-2026-08-19-01` — mój, ściana typu;
- `D-2026-08-19-02` — mój, podstawa zaufania (dopisany w tym cyklu);
- `D-2026-08-12-04` — **autorstwa sesji SPEC-UMOWA**, przeniesiony, nie
  utworzony; zaznaczone w komunikacie commita, treść przeczytana w całości.

**HACZYK, który zgłaszam, zamiast przemilczeć:** `.zakres-sesji` jest w
`.gitignore`. Moja poprawka deklaracji **działa lokalnie i nie podróżuje
w repozytorium** — inna sesja (albo ja po odtworzeniu środowiska) zastanie
deklarację sprzed poprawki i strażnik znowu odmówi. Nie zmieniałem `.gitignore`
sam, bo to zmiana o skutkach poza moim cyklem. Do Twojego rozstrzygnięcia:
wersjonować deklaracje zakresu czy zostawić je lokalnymi.

---

## 6. Wada własna tego cyklu — jedna, ta sama co poprzednio

**Uruchomiłem statykę PRZED dopisaniem testów, nie po.** Bramka złapała
`Cannot cast mixed to string` w moim nowym teście — czyli krok [18] zrobił to,
czego nie zrobiłem ja. To druga odsłona tej samej pomyłki w dwóch cyklach:
mierzę narzędziem, a potem dopisuję materiał i już nie mierzę ponownie.

Reguła, którą stosuję od teraz i wpisuję jako zmianę procedury, nie obietnicę:
**po każdej zmianie plików testowych statyka biegnie razem z testami** — tak
samo, jak bramka biegnie po każdym commicie.

Odnotowuję też, że kontrole cudze zadziałały dokładnie tak, jak miały:
allowlista funkcji kryptograficznych (R6A-4) odmówiła mojemu nowemu plikowi
i kazała dopisać go **jawnie, z uzasadnieniem** — koszt wyjątku równy kosztowi
zgodności.

---

## 7. Warunek zamrożenia

```
git diff --stat 7a8c44d..HEAD -- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md'
   (pusto)

git status --porcelain -- . ':(exclude)docs/'
   (pusto)
```

Poza `docs/` zero zmian. Niezacommitowane zostają wyłącznie pliki sesji
SPEC-UMOWA w `docs/specyfikacja/` (w tym PDF, którego nie czytałem — nie
commituję rzeczy, których nie przeczytałem).

---

## 8. Stan długów

| dług | stan | termin |
|---|---|---|
| **D-3** | `TwierdzeniaKomentarzyTest` poza bramką (2 pominięte) | — |
| **D-4** | wyjątek gitleaks na przynętę w `perturbuj.py` | O-2/O-3 |
| **D-5** | wyjątek gitleaks na cytat sekretu (dwa commity, jeden wpis) | O-2b |
| **O-6c** | kontrola kształtu wartości w `docs/` (przyjęta, niewykonana) | okno scaleniowe |

Nowych długów nie zaciągam. Zegar systemowy (§3d) **nie jest długiem** — jest
nazwaną granicą czekającą na Twoją decyzję, czy w ogóle wchodzi w zakres F1.

---

## 9. Gotowość do rundy 12 — gdzie sam bym uderzył

- **ZEGAR** (§3d) — jedyna niepokryta podstawa zaufania, nazwana wprost.
- **`?string $refreshToken`** u pisarza tożsamości — twierdzę, że nie
  rozstrzyga o tożsamości; do obalenia.
- **`wszystkie()`** wydające tablicę roszczeń do `Bramki` — twierdzę, że to
  wynik weryfikacji, nie droga jej ominięcia; do obalenia.
- **`KontaOidc` jako parametr** — fabryki biorą instancję konfiguracji. Kto
  poda inaczej skonfigurowaną instancję, przesunie podstawę zaufania. Twierdzę,
  że jedynym źródłem konfiguracji jest `.env` i kontener zależności, więc
  wektor prowadzi przez konfigurację, a nie przez kod — **to twierdzenie też
  podlega obaleniu** i jest najbardziej naturalnym miejscem siódmego piętra.

Znam Twoje kryterium z §5 zlecenia. Jeżeli runda 12 znajdzie siódme piętro
w tym obszarze, mój własny wniosek brzmiałby tak samo: to już nie jest pytanie
o naprawę, tylko o to, gdzie kończy się rozsądny zakres fazy fundamentowej.
