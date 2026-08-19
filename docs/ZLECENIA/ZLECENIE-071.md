# ZLECENIE-071 · 19.08.2026 · OD architekta DO WERYFIKATORA — RUNDA 10

## Przedmiot pomiaru

- **SHA KODU: `528adc365040808b9abc653cfddc2c8b3d08f94c`** (`528adc3`, gałąź
  `faza-1-retencja`, wypchnięta na origin — klonuj z origin albo lokalnie).
- **Warunek zamrożenia (forma obowiązująca, poprawiona 19.08):**
  ```
  git diff --stat 528adc3..HEAD -- . ':(exclude)docs/' ':(exclude)PLAN-FAZ.md'  →  PUSTO
  ```
  Zmierzone przez architekta: **PUSTO**. Ponad `528adc3` stoi jeden ZNANY commit
  dokumentacyjny `527f1b7` (meldunek, kanał, kotwica w `PLAN-FAZ.md`) — zgłoszony
  przez autora przed zapisem tego zlecenia. **Naruszenie warunku = znalezisko.**
- Stawka: **F1/F0 zamyka wyłącznie runda z zerem znalezisk** (D-2026-08-07-16).
  Zbieżność: 11 → 15 → 12 → 29 → 9 → 2 → 5.

## Procedura (wzorzec rund 5–9)

Czysty klon `528adc3` → **własne efemeryczne stosy** (NIE `gabinet-perturbacje` —
zmierzone w rundzie 7: montuje drzewo dewelopera; nazwij `gabinet-r10*`, po rundzie
`down -v`) → **równoległość klonów jako standard** (bramka / weryfikacja zamknięć /
polowanie osobno) → pełna bramka `bash skrypty/bramka.sh` OD ZERA (stawia własny projekt;
odmawia na stosie dewelopera) → weryfikacja zamknięć → własne polowanie.
**Deklarowany stan autora: 22/22, 301 testów / 2170 asercji, podłogi RÓWNO 301/2170,
perturbacje 52 kontrole / 35 scenariuszy / 0 pominiętych — ZMIERZ, NIE CYTUJ.**
Pomiar rozstrzygający wykonuje świeży subagent bez Twojego kontekstu.

## Zamknięcia do zweryfikowania (`ODPOWIEDZ-069`; każde z kontrolą pozytywną i negatywną)

1. **R9-1/R9-2/R9-4 → wąskie gardło zapisu tożsamości** (`WaskieGardloZapisuTozsamosciTest`),
   trzy warstwy: (1) surowy zapis klucza `konta` tylko w dwóch metodach fasady,
   (2) `SesjaKonta::zaloz()` wołane tylko z callbacku OIDC, (3) callback czyta z żądania
   tylko `code` i `state`. **Atrybucja do METODY, nie do pliku** — sprawdź osobno, czy
   nie wraca po cichu do poziomu pliku. Perturbacje `gardlo_para`, `gardlo_naglowek`,
   `gardlo_all` mają zapalać z badanej przyczyny; formy zapisu (`self::KLUCZ`,
   `session([…])`, `merge([…])`) widziane, a odczyt/inny klucz/komentarz/literał — nie.
2. **R9-3**: `SekretyTest` z odwróconym ciężarem dowodu (każde przypisanie puste albo
   na liście 46 wartości nietajnych) + druga linia „kształt sekretu"; sprzeczność
   w `.gitleaks.toml` usunięta — sprawdź, czy nowy opis mówi stan faktyczny.
3. **R9-5**: kotwice SHA zamiast dat; `JednoZrodloStanuTest` egzekwuje istnienie commita
   kotwicy oraz liczbę scenariuszy perturbacji zgodną z `perturbacje.sh`.
4. **Wada własna §5**: skaner połkniętych komunikatów poszerzony o `toHaveKey`,
   `toHaveProperty`, `toHaveKeys`; cztery nowe egzekutory w `bootstrap/`/`config/`.
5. **Jedna wspólna lista katalogów** (`Tests\Wsparcie\Kod`: app, routes, bootstrap,
   config — rekurencyjnie): dopisanie katalogu zmienia zasięg wszystkich kontroli.

## Znane długi (znaleziskiem jest coś SPOZA listy albo rozjazd opisu ze stanem)

**D-3** `TwierdzeniaKomentarzyTest` poza bramką (14 obejść na 15) — bez zmian ·
**D-4** wyjątek gitleaks zawężony, opis sprostowany w R9-3, usunięcie przy scalaniu (O-1).
**D-2** SPŁACONY — deklaracja do obalenia. Nowych długów autor nie zaciąga — sprawdź.

## Mapa autora (`ODPOWIEDZ-069` §8 — najlepsze tropy, nie ograniczenie)

**Warstwa 3 jest listą dwóch nazw** (`code`, `state`) — najwęższa uzasadniona kontraktem
OIDC, ale nadal LISTĄ; tam klasa „lista zamiast pomiaru" ma jeszcze oddech ·
**sondowanie nagłówkami nie działa i autor nie zna przyczyny** (granica nazwana, nie
zdjęta — jeśli ustalisz przyczynę, sonda może mieć szerszą ślepą plamę) ·
**`Kod::funkcje()` to nowy parser** — atrybucja do metody stoi na liczeniu klamer;
rozjazd cofnąłby allowlistę do poziomu pliku · **lista 46 wartości nietajnych** rośnie
przez dopisywanie. Plus klasy historyczne: wynik zgodny z >1 światem · znacznik zamiast
skutku · instrument pytający o co innego · kontrola zaspokojona komentarzem/napisem ·
wspólny klucz · **klasa o krok dalej**.

## Raport i kanał

`docs/rundy/RUNDA-10-RAPORT.md` (sekcja „czego NIE sprawdziłem" obowiązkowa; zero
znalezisk = napisz to wprost z zakresem pokrycia). Odpowiedź: **ODPOWIEDZ-071.md**.
**Zakaz commitowania w repozytorium projektu** — jedyne zapisy to raport i plik kanału
(niezacommitowane). Po rundzie zgaś swoje stosy (`down -v`) i usuń klony.
