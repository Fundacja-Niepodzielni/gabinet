# ODPOWIEDŹ-027 — `R6B-7`: sześć `sed -i`. **Dwa groźne, cztery chronione czym innym**

Kanał: plik **18:59:10.061** → obudzony **18:59:53.596** = **43,5 s** (dwudziesty trzeci).

## Mechanizm potwierdzony pomiarem

```
plik:  APP_KEY=stare / INNY=x
sed -i "s|^GABINET_PORT_HTTP=.*|GABINET_PORT_HTTP=8099|"
  kod wyjścia:              0        ← SUKCES
  klucz w pliku po:         0        ← nie zmieniło NIC
```

„Podmiana wykonana" i „podmiana nie trafiła" dawały **identyczny sygnał** — ta sama klasa co
dzisiejsza sonda HTTP nad Postgresem.

**Skutek jest konkretny:** `docker-compose.yml:180` to `${GABINET_PORT_HTTP:-8088}`, a **8088
to port dewelopera**. Nietrafiona podmiana znaczyłaby, że stos bramki bierze porty stosu
roboczego — cicho, przy zielonym meldunku.

---

# ⚠ WYMAGANIE 5 — cztery z sześciu są chronione, ale NIE tym, czym się wydaje

Zanim cokolwiek naprawiłem, sprawdziłem, czy któreś są bezpieczne. **Są cztery — i powód jest
inny, niż zakładałem:**

`dc()` (w. 263-271) przekazuje do `docker compose` **zmienne powłoki**:

```
GABINET_PREFIX · GABINET_PORT_HTTP · GABINET_PORT_POSTGRES · GABINET_PORT_REDIS
```

**Zmierzone, czy zmienna powłoki bije `--env-file`:**

```
bez zmiennej powłoki:   PROBNA: z_pliku
ZE zmienną powłoki:     PROBNA: ze_srodowiska      ← środowisko WYGRYWA
```

Zatem dla tych **czterech** kluczy compose **nigdy nie zależy od podmiany w pliku** —
nietrafiony `sed` byłby tam nieszkodliwy dla compose.

**Ale to nie jest bezpieczeństwo „gwarantowane konstrukcją" tej podmiany.** To **drugi,
niezależny sygnał**, który akurat istnieje — dokładnie ten sam kształt, który dziś rano
uratował kontrolę szyfrowania sesji i o którym napisałem, że **nie wolno go liczyć jako
zasługi kontroli**. Gdyby ktoś usunął przekazywanie zmiennych z `dc()`, cztery wywołania
wracają do bycia groźnymi, a nic by o tym nie krzyknęło.

**Dwa pozostałe — `DB_PASSWORD` i `APP_KEY` — NIE są przekazywane przez `dc()`.** Czyta je
aplikacja z pliku. Nietrafiona podmiana zostawiłaby tam **wartości z `.env.example`**.
**To są te dwa naprawdę odsłonięte.**

## Czy klucze są gwarantowane konstrukcją? NIE — tylko stanem bieżącym

```
DB_PASSWORD · APP_KEY · GABINET_PREFIX · GABINET_PORT_HTTP
GABINET_PORT_POSTGRES · GABINET_PORT_REDIS      → każdy 1× w .env.example
```

Wszystkie sześć trafia **dziś**. Nic nie wymusza, żeby `.env.example` je zachował — a plik
środowiska bramki powstaje właśnie z niego. **Naprawiłem wszystkie sześć**, bo koszt jest
jednakowy, a rozróżnianie „ten groźny, ten nie" zostawia pułapkę na dzień, w którym `dc()`
się zmieni.

---

# Naprawa: `ustaw_w_env()` z odczytem zwrotnym

Sprawdza **treść po**, nie kod wyjścia, i odmawia w **obu** kierunkach:

```
1. klucz OBECNY raz   → podmienia, zle=0        APP_KEY=nowe
2. klucz NIEOBECNY    → ODMOWA + czerwone       „NIE MA klucza APP_KEY”
3. klucz DWA RAZY     → ODMOWA + czerwone       „występuje 2 razy”
```

**Punkt 3 to kierunek 0, o który prosiłeś:** wzorzec trafiający więcej niż raz. `sed`
podmieniłby **oba** wystąpienia — czyli zrobiłby zmianę inną niż zamierzona, i to też
milcząco.

## Kontrola i jej perturbacja — z poprawką, którą złapałem sam

`KlamraSkryptowTest`, kontrola statyczna z **odfiltrowanymi komentarzami** (nagłówek pomocnika
cytuje starą postać wywołania).

**Pierwsza wersja kontroli była BEZWARTOŚCIOWA i wykryła to dopiero perturbacja:** ustawiłem
próg na `<= 1`, „bo jedno wystąpienie należy do wnętrza pomocnika". Pomocnik używa jednak
**zmiennej** (`^${klucz}=`), więc mój wzorzec `\^\w+=` go **nie łapie** — baseline wynosił 0,
nie 1. Przy progu 1 przywrócenie gołego `sed -i` **nie zapaliło kontroli**.

```
próg 1, perturbacja → Tests: 3 passed        ← kontrola BEZWARTOŚCIOWA
próg 0, kod zdrowy  → ✓ passed
próg 0, perturbacja → ⨯ „Gołe `sed -i` …: 337: sed -i "s|^APP_KEY=.*|…"
```

**Trzeci raz dziś mój własny próg był ustawiony na podstawie założenia, a nie pomiaru.**
Zapisuję to przy kontroli, nie tylko tutaj.

# Stan

```
220 zielonych · 2 pominięte · 1 CZERWONY (noga 1) · 1910 asercji
podłogi 219/1901 → 220/1905 · pint PASS, 88 plików
```

# Czego NIE zrobiłem

- **Nie przebiegłem pełnej bramki** — zmieniałem `bramka.sh` w miejscu, które biegnie tylko
  w pełnym przebiegu (`przygotuj_env`). Pomocnik przetestowałem **w izolacji**, na materiale
  zbudowanym pod rękę; **to nie jest to samo co przebieg bramki** i tak to liczę.
- **Nie dołożyłem kontroli, że `dc()` nadal przekazuje cztery zmienne** — a to jest właśnie
  ten „drugi sygnał", na którym opiera się bezpieczeństwo czterech wywołań. **Nazwany dług.**
- Reszta klasy 3 wg iloczynu: następne `R6B-2` / `R6A-1`.
