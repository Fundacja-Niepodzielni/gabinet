# ZLECENIE-081 · 19.08.2026 · OD architekta DO WERYFIKATORA — RUNDA 12

## Przedmiot pomiaru

- **SHA KODU: `7a8c44d8dca055d9ad9af1efcd1e5eaed7140c51`** (`7a8c44d`, gałąź
  `faza-1-retencja`, wypchnięta; **jest czubkiem** w chwili zapisu).
- **Warunek zamrożenia:** `git diff --stat 7a8c44d..HEAD -- . ':(exclude)docs/'
  ':(exclude)PLAN-FAZ.md'` → PUSTO (zmierzone przez architekta). Commity dotykające
  wyłącznie `docs/` są dozwolone i **zgłaszane w kanale** — jeżeli pojawi się jakiś
  po Twoim starcie, dostaniesz uzupełnienie. **`.gitleaks.toml` i `.gitignore`
  pozostają w zakresie zamrożenia.**
- **Higiena klonu:** przytnij refy potomne do `7a8c44d` przed skanem sekretów
  (inaczej krok [21] zapali na znanych cytatach z commitów potomnych — D-5).
- Stawka: **F1/F0 zamyka wyłącznie runda z zerem znalezisk.**
  Zbieżność: 29 → 9 → 2 → 5 → 1 → 3.

## Procedura

Wzorzec rund 5–11: czysty klon → **własne efemeryczne stosy `gabinet-r12*`** (NIE
`gabinet-perturbacje`; po rundzie `down -v` + usuń klony) → równoległość klonów →
pełna bramka `bash skrypty/bramka.sh` OD ZERA, **bez potoku maskującego kod wyjścia**
(zmierzone w poprzednim cyklu: `| tail` oddaje kod `tail`, nie bramki) → weryfikacja
zamknięć → własne polowanie. **Deklaracja autora: 22/22, 318 testów / 2251 asercji,
podłogi RÓWNO 318/2251, perturbacje 61 kontroli / 44 scenariusze / 0 pominiętych —
ZMIERZ, NIE CYTUJ.** Pomiar rozstrzygający — świeży subagent bez Twojego kontekstu.

## Zamknięcia do zweryfikowania (`ODPOWIEDZ-079`)

1. **Podstawa zaufania z konfiguracji**: `zTokenu(string, array)` PRYWATNE; publiczne
   są `zIdTokenu(jwt, KontaOidc, nonce)` i `zAccessTokenu(jwt, KontaOidc)`; `issuer`,
   `jwks`, `audience`, `typ`, `tolerancja` z `KontaOidc`; kontroler nie pobiera JWKS.
   **Dowód skutku do powtórzenia**: token o poprawnym kształcie (nasz wystawca,
   audiencja, świeże czasy, nasz `kid`) podpisany **cudzym kluczem** → `ok=false`
   i odmowa **z kontroli podpisu**, nie z innego ogniwa.
2. **Kontrola pyta o istotę, nie nazwę**: dowolna PUBLICZNA metoda obiektu roszczeń
   przyjmująca `array` = znalezisko (perturbacja `wymagania_wolajacego`).
3. **`final` zmierzone**: `RoszczeniaZweryfikowane` i `TozsamoscSesji` — `isFinal()`,
   perturbacja `roszczenia_final` zapala. Sprawdź też, czy prywatny konstruktor
   naprawdę nie ma obejścia (fabryki, deserializacja, klonowanie).
4. **`nonce` (a)** pokryty warstwą 3 — odczyt `nonce` z żądania w callbacku zapala;
   **`kid` (b)**, **cache JWKS (c)** — pokrycia wskazane przez autora, zweryfikuj.
5. **`docs/DECYZJE.md`**: trzy wpisy zacommitowane, w tym `D-2026-08-12-04` autorstwa
   innej sesji — sprawdź, czy przeniesiony wiernie i oznaczony.

## Twierdzenia autora WYSTAWIONE DO OBALENIA (`ODPOWIEDZ-079` §9) — priorytet rundy

- **`?string $refreshToken`** u pisarza tożsamości: „nie rozstrzyga o tożsamości".
  Spróbuj uzyskać nim sesję innego podmiotu.
- **`wszystkie()`** wydające tablicę roszczeń do `Bramki`: „wynik weryfikacji, nie droga
  jej ominięcia".
- **`KontaOidc` jako parametr fabryk**: „jedynym źródłem konfiguracji jest `.env`
  i kontener zależności, więc wektor prowadzi przez konfigurację, nie przez kod" —
  **autor wskazuje to jako najbardziej naturalne miejsce siódmego piętra.**
- **Zegar systemowy** — decyzją architekta POZA zakresem F1 (wymóg infrastrukturalny
  do wykazania przy starcie produkcyjnym). **Nie jest znaleziskiem**; znaleziskiem
  byłoby, gdyby dało się przesunąć ważność tokenu **bez** ruszania zegara hosta.

## Znane długi

**D-3** (`TwierdzeniaKomentarzyTest` poza bramką, 2 pominięte) · **D-4** i **D-5**
(wyjątki gitleaks, wspólny termin O-2b — usunięcie tylko jednego to znalezisko) ·
**O-6c** (kontrola kształtu wartości w `docs/`, przyjęta, niewykonana — termin: okno
scaleniowe) · **`.zakres-sesji` w `.gitignore`** (decyzja: wersjonować w O-7 okna
scaleniowego). Znaleziskiem jest coś SPOZA listy albo rozjazd opisu ze stanem.

## Raport i kanał

`docs/rundy/RUNDA-12-RAPORT.md` (sekcja „czego NIE sprawdziłem" obowiązkowa).
Odpowiedź: **ODPOWIEDZ-081.md**. Zakaz commitowania w repozytorium projektu.
**Jeżeli wychodzi zero — napisz to wprost wraz z dokładnym zakresem pokrycia; to będzie
werdykt zamykający fazę fundamentową i musi dać się zakwestionować.**
