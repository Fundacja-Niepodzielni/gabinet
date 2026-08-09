# ODPOWIEDŹ-020 (właściwa) — klasa 3: lista zweryfikowana, **jeden członek zamknięty, siedem otwartych**

Zastępuje `ODPOWIEDZ-020.md`, w którym zapisałem „NIEZROBIONE". Tamten plik zostaje bez
podmiany — ktoś mógł go przeczytać.

---

# 1 · Lista — ZWERYFIKOWANA POMIAREM, nie przepisana

Wymagałeś sprawdzenia, czy lista sprzed doby nadal opisuje rzeczywistość. Sprawdziłem
**cztery z siedmiu** (kontrola pozytywna przy każdym wyszukiwaniu):

| członek | co miało być | **stan zmierzony 09.08 wieczorem** |
|---|---|---|
| **R6B-11** | kontrola portów pyta HTTP-em, więc nie wykryje wystawionego Postgresa ani Redisa | **AKTUALNE** — `bramka.sh:505` to `curl … http://${ADRES_LAN}:${PORT}/`; baza odpowiadająca protokołem binarnym nie da odpowiedzi HTTP |
| **R6B-7** | sześć `sed -i` bez odczytu zwrotnego | **AKTUALNE** — `grep -c "sed -i" bramka.sh` → **6** |
| **R6B-8** | `skrypty-uruchamialne.sh` rozstrzyga „nieznaną nazwę" cudzym kodem wyjścia | **AKTUALNE** — plik istnieje |
| **R6B-2 / R6A-1** | test „POZYTYWNY": 401 pochodzi ze znacznika, nie z kasowania sesji | **AKTUALNE co do istnienia testu**; wpływu dzisiejszej naprawy `RejestrSesji` na jego świat **NIE zmierzyłem** |
| R6B-1, R6B-6, N-12 | — | **NIE ZWERYFIKOWANE** dziś. Nie cytuję ich stanu jako aktualnego |

**Dwóch członków nie ma już na liście otwartych:** R6B-13 (zawężenie `--filter`, runda 1)
i R6B-15 — przy czym **R6B-15 był przeze mnie ogłoszony zamkniętym i okazał się POGORSZONY**
(runda 1 dołożyła pięć nowych), a dziś trzyma go zapadka plus odczyt dynamiczny.
**Nie liczę go jako zamkniętego, tylko jako opanowanego.**

---

# 2 · Kolejność wg iloczynu WAGI i OSIĄGALNOŚCI

| # | członek | WAGA | OSIĄGALNOŚĆ | uzasadnienie iloczynu |
|---|---|---|---|---|
| **1** | **R6B-11** | **najwyższa** | **stan bieżący** | jedyny członek, którego fałszywe zielone znaczy **wystawioną bazę danych**. Kontrola „nic nie wystawione publicznie" jest zielona także wtedy, gdy Postgres słucha na adresie LAN — bo pyta HTTP-em, a Postgres nie mówi po HTTP |
| 2 | R6B-7 | wysoka | stan bieżący | `sed` bez trafienia **kończy się sukcesem**; sześć cichych no-opów w przygotowaniu środowiska bramki |
| 3 | R6B-2 / R6A-1 | wysoka | stan bieżący | dotyczy tożsamości; test dowodzi czego innego, niż deklaruje |
| 4 | R6B-8 | średnia | stan bieżący | „nieznana nazwa" nieodróżnialna od „polecenie padło" |
| 5 | R6B-6 | średnia | stan bieżący | 401 pasuje do „odmowa zadziałała" i do „logowanie się nie powiodło" |
| 6-7 | R6B-1, N-12 | niska **dziś** | zależne od nogi 1 | znikną albo zmienią kształt po naprawie nogi 1; ściganie ich teraz to praca na ruchomym gruncie |

---

# 3 · ZAMKNIĘTY — jeden, i jest to członek ZNALEZIONY DZIŚ

**Nie zamknąłem żadnego z siedmiu.** Zamknąłem **nowego**, który wyszedł z lekcji
z `ZLECENIE-024` i którego nie było na liście — a `ZLECENIE-025` kazał zacząć właśnie od niego,
bo mógł się okazać jednym mechanizmem, nie siedmioma sprawami.

## Kontrola tekstowa zaspokojona KOMENTARZEM — `SesjaBezJawnychDanychTest`

Kontrola pilnuje, że szyfrowanie sesji jest **domyślnie** włączone **w kodzie**, czytając treść
`config/session.php`. Zmierzone:

```
PERTURBACJA: wartość domyślna zmieniona na FALSE, stara linia zostawiona W KOMENTARZU
  // BYLO: 'encrypt' => env('SESSION_ENCRYPT', true),
  'encrypt' => env('SESSION_ENCRYPT', false),

asercja tekstowa (szuka w CAŁEJ treści)      → PRZESZŁA   ← przy WYŁĄCZONYM szyfrowaniu
ta sama asercja po odfiltrowaniu komentarzy  → padła
```

**Asercja tekstowa przeszła przy wyłączonym szyfrowaniu sesji.** Trafienie w kodzie i trafienie
w cytacie były nieodróżnialne — jedna wartość, dwa światy, w kontroli dotyczącej RODO art. 9.

**Precyzyjnie, bo to zmienia wagę:** test **jako całość** wtedy padł — ale z **drugiej,
niezależnej asercji** (`config('session.encrypt')`), nie z tej. Dwa niezależne sygnały zrobiły
robotę, którą miał zrobić jeden. **Gdyby ta kontrola istniała sama, dałaby fałszywe zielone.**

**Naprawa i para:**

```
kod zdrowy                        → ✓ zielone
napis tylko w komentarzu, PO naprawie → ⨯ czerwone NA ASERCJI TEKSTOWEJ (linia 163),
                                        komunikat: „Wartość DOMYŚLNA szyfrowania sesji
                                        nie jest `true` w kodzie…"
```

Przed naprawą czerwień szła z linii 156; po naprawie z linii 163. **Czerwień przeniosła się
na właściwą przyczynę** — to jest dowód, że naprawa trafiła w mechanizm, a nie w objaw.

## Czy to jeden mechanizm dla pozostałych? **NIE — i mówię to wprost**

Sprawdziłem sześć kontroli czytających pliki źródłowe. **`PrzyczynyPerturbacjiTest`
i `KlamraSkryptowTest` już filtrują komentarze** (napisałem je dziś, po lekcji).
`SekretyTest` i `BrakWlasnychHaselTest` działają w kierunku **denylisty** — komentarz
powoduje tam **fałszywy alarm**, czyli awarię głośną, a nie ciche zielone.

**Tak więc podpowiedź z `ZLECENIE-025` sprawdziła się częściowo: to był jeden mechanizm dla
JEDNEJ kontroli, nie wspólny mianownik siedmiu.** Siedmiu członków łączy klasa, ale nie ten
mechanizm — i wolę to powiedzieć, niż zgrupować je na siłę.

**⚠ Odnotowuję też własny zdegenerowany odczyt przy tym pomiarze:** mój pierwszy skrypt
„które kontrole filtrują komentarze" odpowiedział **NIE dla wszystkich sześciu**, w tym dla
dwóch, o których wiem, że filtrują. Wzorzec był zły. Wynik dostałem dopiero, czytając asercje.
**Trzeci raz dziś moja własna kontrola miała gałąź zdegenerowaną.**

---

# 4 · Decyzja o `werdykt()` — **NIE budować go w tym kształcie**

Pytałeś, czy wspólny pomocnik odmawiający werdyktu bez (1) tabeli światów, (2) gałęzi bazowej,
(3) zawężenia przyczyny jest właściwy — a jeśli nie, co jest lepsze.

**Nie jest, i powód jest zmierzony, nie estetyczny: członkowie tej klasy żyją w trzech różnych
runtime'ach.** R6B-11 i R6B-7 są w bashu (`bramka.sh`), R6B-2 i R6B-6 w PHP (Pest), R6B-8
w osobnym skrypcie. Wspólna funkcja musiałaby istnieć w trzech wersjach — a wtedy **sama staje
się rzeczą, która może się rozjechać między kopiami**, czyli klasą D5, którą dziś złapałem
u siebie przy rejestrze retencji.

**Co jest lepsze — i co już działa:**

1. **Rozszerzyć `oczekuj_czerwone`, zamiast pisać nowego pomocnika.** On **już jest** tym
   pomocnikiem dla członków bashowych: ma kod wyjścia, wykrywa milczącą porażkę, ma allowlistę
   przyczyny i awarie poboczne. **Brakuje mu jednej nogi: GAŁĘZI BAZOWEJ** — przebiegu na kodzie
   niezmutowanym w tym samym przebiegu. To jest jedna zmiana w jednym miejscu, obejmująca
   wszystkich członków bashowych naraz.
2. **Dla członków w PHP właściwym kształtem jest REGUŁA + KONTROLA NAD KONTROLĄ**, nie funkcja —
   dokładnie tak, jak dziś zadziałało: „kontrola tekstowa filtruje komentarze" jako reguła plus
   perturbacja sprawdzająca, że kontrola pada z właściwej asercji.
3. **Odczyt dwuwarstwowy** (statyczny wiążący, dynamiczny odkrywczy) **już jest zbudowany**
   i to on złapał trzy członkowie klasy, których statyka nie widzi.

**Rekomendacja: następna pozycja klasy 3 to R6B-11**, bo ma najwyższy iloczyn, a przy okazji
wymusi gałąź bazową w `oczekuj_czerwone` — czyli zamknie mechanizm, nie tylko instancję.

---

# 5 · Stan

```
216 zielonych · 2 pominięte · 1 CZERWONY (noga 1) · 1896 asercji
```

# Czego NIE zrobiłem — jawnie

- **Żadnego z siedmiu nie zamknąłem.** Zamknąłem członka znalezionego dziś.
- **R6B-1, R6B-6 i N-12 nie zweryfikowane** — nie wolno cytować ich stanu jako aktualnego.
- **Wpływu naprawy `RejestrSesji` na świat testu „POZYTYWNY" (R6B-2) nie zmierzyłem**,
  choć dotknąłem dziś dokładnie tej ścieżki. To jest pierwsza rzecz do sprawdzenia przy R6B-2.
- **Gałęzi bazowej w `oczekuj_czerwone` nie dołożyłem** — to jest treść następnej pozycji.
- **Pełnej bramki nie przebiegłem.** Własnej pracy nie zamykam.
