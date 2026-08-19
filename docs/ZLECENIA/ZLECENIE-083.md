# ZLECENIE-083 · 19.08.2026 · OD architekta DO WERYFIKATORA — RUNDA 13

## Przedmiot pomiaru

- **SHA KODU: `b60c53a64219b1b81d5be461ffeb23b3622a9749`** (`b60c53a`, gałąź
  `faza-1-retencja`, wypchnięta; **jest czubkiem** w chwili zapisu).
- **Warunek zamrożenia:** `git diff --stat b60c53a..HEAD -- . ':(exclude)docs/'
  ':(exclude)PLAN-FAZ.md'` → PUSTO (zmierzone przez architekta). Commity dotykające
  wyłącznie `docs/` dozwolone i zgłaszane w kanale; `.gitleaks.toml` i `.gitignore`
  pozostają w zakresie zamrożenia.
- **Higiena klonu:** przytnij refy potomne do `b60c53a` przed skanem sekretów
  (inaczej krok [21] zapali na znanych cytatach z commitów potomnych — D-5).
- **Bramkę uruchamiaj BEZ potoku** maskującego kod wyjścia (wyjście do pliku).
- Zbieżność: 29 → 9 → 2 → 5 → 1 → 3 → 1.

## Zamknięcie do zweryfikowania (`ODPOWIEDZ-082`) — jedno, ale kluczowe

**R12-1: strażnik omijania konstruktora pyta o NAZWĘ przez lekser, nie o pisownię.**
Nowa kontrola (`Kod::wywolaniaOmijajaceKonstruktor`) ma wykrywać niezależnie od zapisu:
goły identyfikator, literał w zmiennej, **sklejenie literałów**, nazwę kwalifikowaną
z backslashem (PHP 8 daje jeden token `T_NAME_FULLY_QUALIFIED` — autor złapał się na tym
sam i naprawił porównaniem OSTATNIEGO SEGMENTU), dowolną klasę rozszerzenia Reflection
(zbiór z **runtime**, nie z listy).

Kontrole do powtórzenia:
1. **pięć perturbacji negatywnych**: `r12_sklejenie`, `r12_zmienna`, `r12_backslash`,
   `r12_refleksja_property`, **`r12_wektor_calosc`** (pełny wektor rundy 12: deserializacja
   `TozsamoscSesji` + `zaktualizuj`) — każda ma zapalać **z badanej przyczyny**;
2. **pozytywna**: allowlista wyjątków jest PUSTA i to ma być zmierzone (autor twierdzi:
   zero wystąpień `unserialize`/`Reflection*` w kodzie produkcyjnym) — sprawdź sam;
3. **przyrządu (obie połowy)**: wzorcowe wywołanie MUSI być wykryte, a kod niewinny
   (`json_decode`, `var_export`, `$next($request)`, sklejenie komunikatu) **NIE** zapala.
   Kontrola nadgorliwa jest równie bezużyteczna jak ślepa.

## Twierdzenia autora WYSTAWIONE DO OBALENIA (`ODPOWIEDZ-082` §9) — priorytet rundy

- **„Granica »nazwa funkcji z żądania« jest pokryta warstwą 3"** — autor wskazuje to jako
  najbardziej naturalne miejsce ósmego piętra. Spróbuj: nazwa funkcji zbudowana z wartości
  z żądania, w miejscu, którego warstwa 3 nie obejmuje.
- **`new $zmienna`** z nazwą klasy zbudowaną inaczej niż literałem.
- **Skaner sam używa refleksji w pliku testowym** — autor twierdzi, że to właściwa granica
  zasięgu; obal, jeśli istnieje droga z testu do produkcji.
- **Lista deserializatorów jest jawna** (igbinary/msgpack/wddx wpisane zawczasu) —
  rozszerzenie spoza listy przeszłoby.

## Znane długi (znaleziskiem jest coś SPOZA listy albo rozjazd opisu ze stanem)

**D-3** (`TwierdzeniaKomentarzyTest` poza bramką, 2 pominięte) · **D-4** i **D-5**
(wyjątki gitleaks, wspólny termin O-2b — usunięcie tylko jednego to znalezisko) ·
**O-6c** (kontrola kształtu wartości w `docs/`) · **O-7** (`.zakres-sesji` wersjonowane) —
oba z terminem: okno scaleniowe. **Zegar systemowy** decyzją architekta POZA zakresem F1
(wymóg infrastrukturalny przy starcie produkcyjnym) — nie jest znaleziskiem.

## Procedura i raport

Wzorzec rund 5–12: czysty klon → własne stosy `gabinet-r13*` (NIE `gabinet-perturbacje`;
po rundzie `down -v`) → równoległość klonów → pełna bramka OD ZERA → weryfikacja zamknięcia
→ własne polowanie. **Deklaracja autora: 22/22, 320 testów / 2261 asercji, podłogi RÓWNO
320/2261, perturbacje 66 kontroli / 49 scenariuszy / 0 pominiętych — ZMIERZ, NIE CYTUJ.**
Pomiar rozstrzygający — świeży subagent bez Twojego kontekstu.

`docs/rundy/RUNDA-13-RAPORT.md` + **ODPOWIEDZ-083.md**; sekcja „czego NIE sprawdziłem"
obowiązkowa; zakaz commitowania w repozytorium projektu.

**To jest runda, która może zamknąć fazę fundamentową.** Jeżeli wychodzi zero — napisz to
wprost wraz z dokładnym zakresem pokrycia (co pokryłeś, czego nie, jakimi metodami), tak
żeby dało się go zakwestionować. Kryterium bez złagodzeń.
