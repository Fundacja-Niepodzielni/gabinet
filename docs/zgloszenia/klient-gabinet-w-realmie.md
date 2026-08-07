# Zgłoszenie do repo `konta`: rejestracja klienta `gabinet` w realmie

**Od:** sesja wykonawcza Gabinetu · **Data:** 2026-08-07 · **Dotyczy:** BLK-01
**Adresat:** sesja/osoba pracująca w repozytorium `Fundacja-Niepodzielni/konta`
(realm ma **jednego autora naraz** — `realm/README.md` §11 pkt 4).

> Ten dokument jest **prośbą z gotową treścią**, a nie zmianą. Sesja Gabinetu
> świadomie nie dotyka repozytorium `konta` — decyzja właściciela z 2026-08-07:
> zgłoszenie przekazuje właściciel albo sesja pracująca w tamtym repozytorium.
> Wszystkie wartości poniżej są gotowe do przepisania; nazwa domeny produkcyjnej
> jest już zatwierdzona.

---

## 1. O co prosimy

Utworzenie w realmie `niepodzielni` klienta **`gabinet`** — poufnego, na wzór
`psychon-api` — oraz przestawienie asercji negatywnej, która dziś pilnuje, żeby
ten klient NIE istniał.

## 2. Uzasadnienie (do opisu PR-a, §11 pkt 1)

- Gabinet wchodzi w etap 3 ekosystemu — dokładnie ten, dla którego nazwa
  `gabinet` była zarezerwowana (`PLAN-ETAPU-0.md` §3, `realm/README.md` §4.4).
- `CLAUDE.md` Gabinetu §2: logowanie **wyłącznie** przez Konta Niepodzielni,
  bez własnych haseł. Bez klienta w realmie personel nie ma jak się zalogować,
  a to blokuje panele z faz F4 i F5.
- Warstwa OIDC po stronie Gabinetu jest **gotowa i sprawdzona na żywym IdP**:
  discovery, JWKS, walidacja podpisu, `iss`, `exp`, `typ`, `aud` i odczyt ról
  z `realm_access.roles` działają na prawdziwych tokenach z tego realmu
  (dowód: `gabinet/docs/BLOKERY.md`, BLK-01). Brakuje wyłącznie rejestracji.

**Co ta zmiana psuje w istniejących asercjach — i dlaczego to zamierzone:**
`realm/assert.sh` (ok. wiersz 336) ma pętlę `for c in gabinet mindscape-app`,
która **zapala się na czerwono, jeśli klient `gabinet` istnieje**. Ta asercja
była poprawna dla etapu 0. Po tej zmianie `gabinet` przechodzi z listy
„nie może istnieć" na listę „musi istnieć" (pętla z klientami kontraktowymi
kilka wierszy wyżej). `mindscape-app` **zostaje** w asercji negatywnej — etap 4
jeszcze nie nadszedł. Zmienia się też stała `NK_CLIENTS_KONTRAKT` (zbiór
klientów realmu sprawdzany co do jednego).

## 3. Proponowana definicja klienta

Wzorzec: `psychon-api` (klient poufny z audience mapperem). Różnice: inne
zmienne środowiskowe i inna audiencja.

```json
{
  "clientId": "gabinet",
  "name": "Gabinet - system rezerwacji (Laravel)",
  "description": "Klient poufny systemu rezerwacji wizyt. Tokeny niosa aud=gabinet. Etap 3.",
  "enabled": true,
  "protocol": "openid-connect",
  "publicClient": false,
  "clientAuthenticatorType": "client-secret",
  "secret": "${NK_GABINET_SECRET}",
  "standardFlowEnabled": true,
  "implicitFlowEnabled": false,
  "directAccessGrantsEnabled": false,
  "serviceAccountsEnabled": false,
  "frontchannelLogout": false,
  "consentRequired": false,
  "fullScopeAllowed": true,
  "redirectUris": [
    "${NK_GABINET_DEV_ORIGIN}/auth/callback",
    "${NK_GABINET_PROD_ORIGIN}/auth/callback"
  ],
  "webOrigins": [],
  "attributes": {
    "backchannel.logout.url": "${NK_GABINET_BACKCHANNEL_LOGOUT_URL}",
    "backchannel.logout.session.required": "true",
    "backchannel.logout.revoke.offline.tokens": "false",
    "post.logout.redirect.uris": "+",
    "oauth2.device.authorization.grant.enabled": "false",
    "oidc.ciba.grant.enabled": "false",
    "require.pushed.authorization.requests": "false",
    "use.refresh.tokens": "true",
    "pkce.code.challenge.method": "S256"
  },
  "protocolMappers": [
    {
      "name": "audience-gabinet",
      "protocol": "openid-connect",
      "protocolMapper": "oidc-audience-mapper",
      "consentRequired": false,
      "config": {
        "included.client.audience": "gabinet",
        "id.token.claim": "false",
        "access.token.claim": "true",
        "introspection.token.claim": "true",
        "userinfo.token.claim": "false",
        "lightweight.claim": "false"
      }
    }
  ]
}
```

**Uwaga do `pkce.code.challenge.method`.** `psychon-api` tego atrybutu nie ma
(klient poufny dowodzi tożsamości sekretem). Prosimy o jego dodanie, bo Gabinet
i tak wysyła `code_challenge` — PKCE nic nie kosztuje, a domyka wyciek kodu
autoryzacyjnego z logów proxy. Jeśli miałoby to złamać którąś asercję kształtu,
**prosimy ten jeden atrybut pominąć**; nasza strona działa w obu wariantach.

## 4. Wartości zmiennych środowiskowych

Do dopisania w `realm/README.md` §9 i do `.env` **wypełniane przez człowieka**:

| Zmienna | Wartość lokalna | Wartość produkcyjna |
|---|---|---|
| `NK_GABINET_SECRET` | dowolna losowa (`openssl rand -base64 32`) | **człowiek przy deployu**, do menedżera haseł fundacji |
| `NK_GABINET_DEV_ORIGIN` | `http://localhost:8098` | — |
| `NK_GABINET_PROD_ORIGIN` | — | `https://gabinet.niepodzielni.com` **(zatwierdzona przez właściciela 2026-08-07, D-2026-08-07-11)** |
| `NK_GABINET_BACKCHANNEL_LOGOUT_URL` | `http://gabinet-app/oidc/backchannel-logout` | `https://gabinet.niepodzielni.com/oidc/backchannel-logout` |

> **Pułapka z kontraktu §3a, warta osobnego zdania:** adres back-channel logout
> wywołuje **serwer Keycloaka**, więc musi być rozwiązywalny **z jego
> kontenera**. `http://localhost:8098/...` jest tam adresem samego Keycloaka
> i nie zadziała — a usterka objawia się **ciszą**, nie błędem. Lokalnie
> działa nazwa kontenera Gabinetu (`gabinet-app`), pod warunkiem że stos
> Gabinetu jest podpięty do sieci `niepodzielni-konta-idp` — robi to
> `gabinet/docker-compose.konta.yml`.

## 5. Zmiany w asercjach

W `realm/assert.sh`:

1. dopisać `gabinet` do listy klientów, które **muszą** istnieć
   (pętla `for c in psychon-web hub psychon-api …`),
2. usunąć `gabinet` z pętli negatywnej `for c in gabinet mindscape-app`
   — **zostawiając w niej `mindscape-app`**,
3. dopisać `gabinet` do stałej `NK_CLIENTS_KONTRAKT`,
4. dodać asercje kształtu, analogicznie do `psychon-api`:
   - `gabinet jest poufny` → `publicClient == false`,
   - `gabinet ma mapper audiencji` → `aud` access tokenu zawiera `gabinet`,
   - `gabinet ma wyłączone directAccessGrants`.

## 6. Bramka (§11 pkt 2 i 3)

- `scripts/ci-local.sh` **zielony przed i po** zmianie, ten sam projekt i te same porty.
- Weryfikacja przez sesję, która tej zmiany **nie pisała**.
- Test odbiorczy po stronie Gabinetu (wykonamy my, po merge):
  `skrypty/keycloak-sprawdz.sh` + pełny przepływ authorization code kontem
  personelu; oczekiwany wynik: `aud` **OK** dla tokenu wystawionego klientowi
  `gabinet` i **FAIL** dla tokenu wystawionego komukolwiek innemu.

## 7. Czego NIE prosimy teraz (żeby nie rozdymać zmiany)

- **Konta usługowego do zakładania kont pacjentów** przez Admin API
  (`CLAUDE.md` §2: rola `pacjent`, tworzenie w tle + action-token). Będzie
  potrzebne dopiero w F3 i zasługuje na osobną decyzję — kontrakt §5 opisuje,
  dlaczego sekret takiego konta ma klasę wrażliwości konta administracyjnego.
- Zmian w rolach realmu. Siedem istniejących ról wystarcza; bramki Gabinetu
  są po naszej stronie (`backend/config/konta.php`).
