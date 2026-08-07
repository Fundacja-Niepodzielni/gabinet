# Rejestr blokerów — Gabinet

Zasada z `WYTYCZNE-PRACY.md` §4: **czerwona bramka to informacja, nie przeszkoda.**
Nie obchodzimy jej i nie wyłączamy testów — wpisujemy tutaj, z uzasadnieniem
i planem powrotu. Zamknięcie blokera = nowy wpis w kolumnie „Stan", nigdy
skasowanie wiersza.

| # | Bloker | Faza | Stan |
|---|---|---|---|
| BLK-01 | Klient `gabinet` nie istnieje w realmie Keycloaka | F0 | **OTWARTY** |

---

## BLK-01 — klient `gabinet` nie istnieje w realmie Keycloaka

**Data zgłoszenia:** 2026-08-07 (sesja 1).

**Co jest zablokowane.** Punkt bramki F0 „logowanie testowym kontem z lokalnego
Keycloaka działa". Pełny przepływ authorization code nie da się przejść, bo IdP
nie zna klienta `gabinet` — żądanie autoryzacyjne skończy się błędem klienta,
zanim ktokolwiek zobaczy ekran logowania.

**Dlaczego tak jest — i dlaczego to NIE jest usterka.** Realm `niepodzielni`
świadomie nie zawiera tego klienta: `realm/README.md` §4.4 mówi wprost, że
`gabinet` i `mindscape-app` to **rezerwacja nazw** (etapy 3 i 4), a
`realm/assert.sh` ma **test negatywny**, który zapala się na czerwono, gdyby
klient powstał przedwcześnie. Czyli dzisiejszy stan realmu jest zgodny
z kontraktem, a to my przyszliśmy o jeden etap za wcześnie.

**Czego NIE zrobiliśmy i dlaczego.** Nie utworzyliśmy klienta lokalnie „na
skróty" — zabrania tego `CLAUDE.md` („Czego NIE wolno"), a `realm/README.md` §11
wymaga dla każdej zmiany realmu: uzasadnienia, **zielonego `scripts/ci-local.sh`
przed i po**, niezależnej weryfikacji oraz **jednego piszącego naraz**. Warunek
„jeden piszący" nie był spełniony: repozytorium `konta` ma w chwili pisania
niezacommitowane zmiany (`docs/briefy/BLOCKERY.md`, `tests/theme/…`), czyli ktoś
tam pracuje. Wejście z równoległą zmianą do `realm-niepodzielni.json` złamałoby
regułę, której to repozytorium pilnuje najmocniej.

**Co zostało udowodnione mimo blokera** (`skrypty/keycloak-sprawdz.sh`,
przebieg 2026-08-07, żywy stos `niepodzielni-konta`):

| Sprawdzenie | Wynik |
|---|---|
| discovery trasą wewnętrzną, `issuer` == adres publiczny | **OK** |
| JWKS pobrane, 2 klucze RS256 | **OK** |
| PRAWDZIWY token z realmu (konto `test-pacjent`, klient `test-cli`) | pobrany |
| walidacja: `format`, `alg`, `kid`, `signature`, `iss`, `exp`, `iat`, `typ` | **OK** |
| walidacja: `aud` przy wymaganej audiencji `gabinet` | **FAIL** — i to jest wynik POPRAWNY: token wystawiono dla innego klienta (§4.4 kontraktu) |
| ta sama walidacja przy audiencji, którą token faktycznie ma (`account`) | **token przyjęty** |
| role odczytane z `realm_access.roles` prawdziwego access tokenu | `pacjent offline_access default-roles-niepodzielni uma_authorization` |
| mapowanie ról na bramki Gabinetu | otwarta `panel.pacjenta`, panele personelu zamknięte |

Czyli: cały łańcuch walidacji działa na prawdziwych kluczach prawdziwego IdP.
Brakuje **wyłącznie** rejestracji klienta — czyli tego jednego kroku, który
z definicji należy do repozytorium `konta`.

**Plan powrotu — ROZSTRZYGNIĘTY 2026-08-07.** Zgłoszenie przekazuje **właściciel
albo sesja pracująca w repo `konta`**. Sesja Gabinetu **nie dotyka tamtego
repozytorium** — to jest ostateczne, nie „na razie".

1. Właściciel (albo sesja pracująca w repo `konta`) wykonuje zgłoszenie
   [`docs/zgloszenia/klient-gabinet-w-realmie.md`](zgloszenia/klient-gabinet-w-realmie.md)
   — gotowa definicja klienta, lista wymaganych zmiennych i wskazanie asercji
   do przestawienia.
2. Po merge: `KEYCLOAK_CLIENT_SECRET` wpisuje **człowiek** do `.env`
   (nigdy przez repozytorium, nigdy przez czat).
3. Sesja Gabinetu uruchamia `skrypty/keycloak-sprawdz.sh`, dokłada krok pełnego
   przepływu authorization code i zamyka punkt bramki F0.

**Czego bloker NIE zatrzymuje.** Fazy F1 i F2 (model danych, reguły jako
konfiguracja, silnik dostępności) nie dotykają logowania. Decyzją właściciela
z 2026-08-07 **F1 rusza równolegle, nie czekając na domknięcie BLK-01**.
