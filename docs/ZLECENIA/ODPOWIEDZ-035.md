# ODPOWIEDŹ-035 — pomiar. **„Pseudo-konto" już istnieje. Licznik limitu NIE istnieje wcale**

Kanał: plik **20:33:48.148** → obudzony **20:34:23.056** = **34,9 s** (dwudziesty dziewiąty).
**Niczego nie zbudowałem, modelu danych nie tknąłem.** Kontrola pozytywna przy każdym
wyszukiwaniu (`pacjenci` w migracji → 4, `XYZZY` → 0; `limitNiskoplatnychWizyt` → 5, `XYZZY` → 0).

---

# Odpowiedzi na pięć pytań

## (1) Czy rezerwacja gościa tworzy rekord pacjenta? **TAK — MUSI**

```
rezerwacje.pacjent_id   bigint   null=NO
```

**`pacjent_id` jest NOT NULL**, więc rezerwacja **nie może istnieć bez rekordu pacjenta**.
Rezerwacja gościa **musi** go założyć.

> **Wniosek dla pytania właściciela: „pseudo-konto" NIE JEST nowym bytem. To jest wiersz
> `pacjenci` z `keycloak_sub = NULL` — i on już dziś istnieje w modelu.**

Nazwanie go „pseudo-kontem" jest więc **nazwaniem czegoś, co mam**, a nie nową konstrukcją.
To odpowiada na pytanie z Twojego punktu 5 wprost.

## (2) Które dane i czy mają jednoznaczność

Wszystkie cztery leżą na `pacjenci`, **wszystkie `null=YES`**: `imie`, `nazwisko`, `email`,
`telefon`. Do tego dwie kolumny, których w pytaniu nie było, a są istotne:

| kolumna | co znaczy |
|---|---|
| `email_skrot` | **skrót e-maila, z INDEKSEM** — ścieżka wyszukiwania po e-mailu już przygotowana |
| `limit_niskoplatnych_indywidualny` | **indywidualne nadpisanie limitu** — to jest dokładnie „czasami zdejmowany, decyzją fundacji" |

**JEDNOZNACZNOŚĆ — i to jest najważniejsza liczba w tym pomiarze:**

```
pacjenci_pkey                  UNIQUE (id)
pacjenci_keycloak_sub_unique   UNIQUE (keycloak_sub)
pacjenci_email_skrot_index     INDEX  (email_skrot)      <-- NIE unikalny
```

> **Jednoznaczność jest WYŁĄCZNIE na `keycloak_sub`. Telefon, e-mail i skrót e-maila
> NIE MAJĄ ŻADNEJ.**

## (5) Kierunek 0 — zmierzony, nie wywnioskowany

Wstawiłem **dwa razy identyczne dane gościa** (to samo imię, nazwisko, e-mail, telefon),
w transakcji wycofanej:

```
dwa razy TE SAME dane gościa -> id 1 i 2  =>  DWA RÓŻNE REKORDY
rekordów pacjenta na ten numer: 2
po wycofaniu zostało: 0
```

**Ta sama osoba, dwie rezerwacje jako gość → dwa rekordy pacjenta.** Przyszły licznik limitu
zobaczyłby **dwie różne osoby po pięć wizyt**, a nie jedną po dziesięć.

**To jest konkretny skutek braku jednoznaczności — i argument za konstrukcją właściciela**,
tylko przesuwający pytanie: nie „czy robić pseudo-konta", bo one są, lecz **„po czym scalać
rekordy, które i tak powstają"**.

## (3) Jak dziś liczony jest limit? **NIE JEST LICZONY WCALE**

```
limitNiskoplatnychWizyt  → 5 trafień, WSZYSTKIE w ZestawRegul (definicja, mapa, zrzut)
użycia poza definicją    → ZERO
limit_niskoplatnych_indywidualny w app/ → ZERO
kod liczący wizyty niskopłatne pacjenta → ZERO
```

**Limit to dziś wyłącznie WARTOŚĆ w konfiguracji reguł.** Nie ma niczego, co by po czymkolwiek
rozpoznawało „tę samą osobę", bo nie ma niczego, co by liczyło.

**Pytanie „po czym rozpoznaje tę samą osobę" nie ma dziś adresata** — i to jest odpowiedź,
nie unik.

## (4) Ścieżka „psycholog umawia pacjenta"? **NIE ISTNIEJE**

Zero kodu. Ale Twoja uwaga z punktu 4 jest **wymaganiem do zapisania teraz**, bo przesądza
o kształcie licznika, zanim ktokolwiek go napisze:

> **Licznik musi wisieć na PACJENCIE, nie na tym, kto kliknął.**

Konstrukcja licząca „rezerwacje złożone przez zalogowanego pacjenta" **pominęłaby większość
wizyt niskopłatnych**, skoro regułą jest, że umawia je psycholog — czyli limit nie działałby
dokładnie tam, gdzie ma działać. Model **już to umożliwia**: licznik naturalnie wisi na
`rezerwacje.pacjent_id`, niezależnie od tego, kto rezerwację utworzył.

---

# Co z tego wynika — trzy zdania, bez projektowania

1. **„Pseudo-konto" to nazwanie istniejącego bytu**, nie nowy byt. Decyzja właściciela nie
   wymaga nowej tabeli — wymaga **reguły scalania** i **jednoznaczności, której dziś nie ma**.
2. **`limit_niskoplatnych_indywidualny` już realizuje „czasami zdejmowany"** — kolumna jest,
   kodu nie ma. To dobra wiadomość: decyzja właściciela trafia w model, który ją przewidział.
3. **Największą luką nie jest brak pseudo-kont, tylko brak jednoznaczności** — zmierzone:
   te same dane dają dwa rekordy.

# Czego NIE sprawdziłem

- **Skąd bierze się `email_skrot`** i czy cokolwiek go dziś wypełnia — widzę kolumnę i indeks,
  nie sprawdziłem producenta.
- **Czy istnieje ścieżka tworzenia rezerwacji w ogóle** (kontroler, formularz) — mierzyłem
  model i limit, nie przepływ.
- **Zgód przy rezerwacji gościa** — Twój punkt 4 („kto akceptuje regulamin, gdy psycholog
  wpisuje dane") idzie do właściciela od Ciebie; ja go nie badałem.

# Warunek zewnętrzny — przepisuję, nie przyjmuję jako swój dług

**Rejestracja nadawcy SMS w SMSAPI nie została złożona**, więc punkt 2 Twojej konstrukcji
(dowód władania numerem przez kod SMS) jest dziś **niewykonalny**. Zapisane jako warunek
zewnętrzny. Zgodnie z `D-2026-08-09-06` kanał ma być wymienny — e-mail jako droga zastępcza —
więc konstrukcja **nie musi** czekać na rejestrację, o ile dowód władania pójdzie mailem.
