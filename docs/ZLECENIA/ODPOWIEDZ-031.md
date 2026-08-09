# ODPOWIEDŹ-031 — mierzyłem ŚCIEŻKĘ NAJKRÓTSZĄ. I jest gorzej: kontrola nie jest globalna

Kanał: plik **19:32:32.793** → obudzony **19:32:59.309** = **26,5 s** (dwudziesty piąty).
Kontrola pozytywna przy każdym wyszukiwaniu (np. `Route::` w `web.php` → 6, `XYZZY` → 0).

## Odpowiedź w jednym zdaniu

> **Mierzyłem ścieżkę NAJKRÓTSZĄ — tokenem ważnym 1 sekundę — i nie wiem, ile trwa najdłuższa,
> bo jej nie zmierzyłem.**

---

# 1 · Wszystkie ścieżki dostępu — ALLOWLISTA z routera, nie z pamięci

Źródłem jest `php artisan route:list --json`, czyli **router**, a nie moja lista. **34 trasy.**

| ścieżka | strażnik | czy sprawdza unieważnienie |
|---|---|---|
| `/` | brak | **nie** — publiczny JSON, bez tożsamości |
| `/api/wersja` | brak | **nie** — publiczny |
| `/auth/login`, `/auth/callback`, `/auth/wyloguj` | przepływ logowania | nie dotyczy |
| **`/auth/ja`** | `LogowanieController::ja` → `OdswiezanieSesji::stanKonta()` | **TAK — jedyna** |
| `/oidc/backchannel-logout` | walidacja logout tokenu | przyjmuje unieważnienie |
| `/docs/api`, `/docs/api.json` | `Scramble\RestrictedDocsAccess` | **nie** — cudzy strażnik |
| `horizon/*` (26 tras) | `Laravel\Horizon\...\Authenticate` | **nie** — cudzy strażnik |
| `/up` | brak | nie dotyczy |

## ⛔ Znalezisko ważniejsze od odpowiedzi na pytanie

**`OdswiezanieSesji` NIE JEST MIDDLEWARE'em.** W `bootstrap/app.php` blok `withMiddleware`
jest **pusty** — zawiera wyłącznie komentarz. Kontrola unieważnienia jest **usługą
wstrzykniętą do kontrolera** i wykonuje się tam, gdzie kontroler ją zawoła:
**w jednym miejscu, `LogowanieController:236`**.

**Skutek:** każda nowa trasa chroniona, dopisana poza tym kontrolerem, **nie ma kontroli
unieważnienia i nic tego nie zgłosi**. Dziś nie boli, bo tras biznesowych jeszcze nie ma —
ale F2 dokłada rezerwacje, kalendarz i panel koordynatora. **To jest wada konstrukcyjna
czekająca na moment, w którym stanie się luką.**

To ta sama rodzina co `R6A-11`: mechanizm istnieje, ale **jego zasięg wyznacza czyjaś pamięć,
a nie konstrukcja**.

---

# 2 · Na której ścieżce mierzyłem — i dlaczego to najkrótsza

Test „odbiera dostęp, gdy Keycloak odbierze rolę — **najpóźniej w oknie access tokenu**"
zaczyna się od założenia tokenu ważnego **jedną sekundę** (`waznoscTokenuS: 1`), a dopiero
dalej pojawia się w danych `exp` oddalone o 600 s.

**Mierzę odświeżenie przy tokenie ważnym 1 s.** To dowodzi, że mechanizm **przeliczy role
po wygaśnięciu tokenu** — ale **nie dowodzi górnej granicy**. Nazwa testu obiecuje „najpóźniej
w oknie access tokenu", a pomiar dotyczy okna **sztucznie skróconego do sekundy**.

**Jedna wartość, dwa światy:** „mechanizm działa w granicy 600 s" oraz „mechanizm działa,
a granicy nikt nie sprawdził" dają ten sam zielony.

# 3 · Najdłuższa ścieżka i jej czas — **NIE ZMIERZONE, i tak to zapisuję**

Najdłuższa droga dostępu po odebraniu uprawnień w Keycloaku, **bez wylogowania**:

```
rola odebrana w IdP  →  sesja lokalna ŻYJE  →  role przeliczane dopiero przy odświeżeniu
                     →  granica = czas życia access tokenu z realmu
```

**Nie mierzyłem jej przy realnej wartości realmu.** Podanie tu „600 s" byłoby powtórzeniem
liczby z komentarza, a nie pomiarem — tym bardziej że `konta.sso_session_max_s` jest u mnie
**`NULL`** (zmierzone dziś), więc wartości realmu **nie mam nawet skonfigurowanej**.

**„Nie mierzyłem" jest moją odpowiedzią.** „Prawdopodobnie mieści się" — nie jest.

## Czego jeszcze nie zmierzyłem, a jest ścieżką dostępu

- **`horizon/*`** — 26 tras z **własnym** strażnikiem. Nie sprawdziłem, kogo wpuszcza ani czy
  reaguje na unieważnienie. Nie niesie ról mojego systemu, ale **jest wejściem do panelu
  kolejek** i nie objęła go żadna moja kontrola.
- **`/docs/api`** — cudzy strażnik, ta sama uwaga.
- **Zadania w tle** — `gabinet:retencja` i `gabinet:puls` biegną **bez tożsamości
  użytkownika**, więc nie są ścieżką dostępu w tym sensie. To jest odczyt kodu, nie pomiar.

---

# 4 · Werdykt

**To nie jest wpadka, tylko skutek tego, że decyzja tego nie rozstrzygała** — i dokładnie
dlatego zapisuję jako **znalezisko**, zgodnie z Twoim poleceniem:

1. **Mierzyłem najkrótszą ścieżkę** (token 1 s). Górna granica niezmierzona.
2. **Kontrola unieważnienia nie jest globalna** — jedno wywołanie w jednym kontrolerze,
   blok `withMiddleware` pusty. **To jest cięższe niż punkt 1.**
3. **Dwie ścieżki (`horizon/*`, `/docs/api`) mają cudzych strażników** i nie są objęte
   żadną moją kontrolą.

**Nie naprawiam tego w tej pozycji** — pytanie brzmiało „na której ścieżce mierzyłeś i skąd
wiesz", i na nie odpowiadam. Naprawa (kontrola unieważnienia jako middleware oraz pomiar przy
realnej wartości realmu) to **osobna pozycja z parą czerwone-przed / zielone-po**; biorę ją
jako `PODJETO-032`.
