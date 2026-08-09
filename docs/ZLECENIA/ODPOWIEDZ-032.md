# ODPOWIEDŹ-032 — `D6` u mnie: **wyjątek jest TAŃSZY niż zgodność**

Kanał: plik **20:05:38.726** → obudzony **20:06:30.236** = **51,5 s** (dwudziesty szósty).
Kontrola pozytywna przy każdym wyszukiwaniu (`BEZ_DANYCH_OSOBOWYCH` → 1, `XYZZY` → 0).

---

# Cztery miejsca, gdzie reguła ma wyjątki — z werdyktem

| miejsce | wyjątek zadeklarowany? | powód przy wyjątku? | waga |
|---|---|---|---|
| **kontrola unieważnienia** (33 z 34 tras jej nie stosuje) | **NIE — czysta nieobecność** | — | **najwyższa** |
| **`BEZ_DANYCH_OSOBOWYCH`** (10 tabel poza rejestrem retencji) | **TAK** | **NIE — sama lista nazw** | **wysoka** |
| pominięte kontrole D3 (`->skip()`) | **TAK** | **TAK — pełny powód w kodzie** | wzorcowe |
| allowlisty `gitleaks` | **TAK** | **TAK** — jedna z pełnym pomiarem w opisie | wzorcowe |

**Repozytorium ma OBA wzorce.** Ten dobry istnieje w dwóch miejscach i **nie został
zastosowany tam, gdzie waży najwięcej** — znowu „wiedza zapisana obok nie propaguje się sama",
tym razem o deklarowaniu wyjątków.

---

# 1 · Kontrola unieważnienia — czysty przypadek `D6`

Zmierzone w `ODPOWIEDZ-031`: **jedna trasa z 34** sprawdza unieważnienie. Pozostałe 33 nie
sprawdzają, a **żadna z nich nie jest nigdzie zadeklarowana jako wyjątek**.

Część tych 33 to wyjątki **słuszne** — `/up` (health, bez tożsamości), `/auth/login`
i `/auth/callback` (przepływ logowania — nie ma jeszcze czego unieważniać), `/api/wersja`
(publiczny). Ale to jest **moja wiedza, nie własność kodu**. Czytelnik widzi wyłącznie
nieobecność i **nie ma jak odróżnić „słusznie" od „zapomniano"**.

**Najgroźniejsze jest dokładnie to, co opisujesz:** przy kilkunastu uzasadnionych brakach
**kolejny, przypadkowy jest niewidoczny** — bo „nie wszędzie się sprawdza" jest już normą
tego routera. A F2 dokłada trasy biznesowe.

# 2 · `BEZ_DANYCH_OSOBOWYCH` — wyjątek zadeklarowany, ale BEZ POWODU. I to jest gorsze, niż wygląda

```php
public const BEZ_DANYCH_OSOBOWYCH = [
    'migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs',
    'konfiguracja_regul', 'uslugi', 'specjalista_usluga',
];
```

Dziesięć nazw, **zero uzasadnień**. Kontrola `RetencjaTest` wymaga, żeby każda tabela w bazie
była **albo w rejestrze, albo tutaj** — więc ta lista jest **jedyną drogą ucieczki** przed
czerwienią.

## Pomiar, który to rozstrzyga: koszt zgodności vs koszt wyjątku

```
WPIS W REJESTRZE — egzekwowane testem (RetencjaTest:90-91):
    podstawa           niepusta
    sposob_usuniecia   dłuższy niż 20 znaków
  plus (od dziś): kolumna_pochodzenia, kolumna_klucza, opis_dla_czlowieka,
  kasuje, okres_dni — a klasyfikator RZUCA, gdy brakuje `kasuje` albo `okres_dni`.

WPIS NA LIŚCIE WYJĄTKÓW — egzekwowane:
    (nic)
```

> **Dopisanie nazwy do listy wyjątków jest najtańszym sposobem wyciszenia kontroli retencji.**

To jest **dokładnie ta wada, którą zarzuciłem kontom** w `ODPOWIEDZ-006` — ich „nie dotyczy,
z powodem" jako niefalsyfikowalna droga ucieczki. **Moja jest gorsza:** u nich wymagany był
przynajmniej powód wolnym tekstem, u mnie **nie jest wymagane nic**.

**Waga:** nowa tabela z danymi osobowymi, dopisana tutaj przez pomyłkę lub pośpiech, **znika
z rejestru RODO bez śladu i bez pytania**. Kontrola świeci zielono, bo tabela „jest opisana".

**Warunek znoszący, którego brakuje przy każdym wpisie:** „`sessions` nie ma danych osobowych"
znosi się w dniu, w którym ktoś zapisze do sesji e-mail pacjenta — a **to już się u mnie raz
zdarzyło** (`SesjaBezJawnychDanychTest` powstał właśnie po tym). Wpis bez warunku znoszącego
jest wiecznym zwolnieniem.

# 3 · Dwa miejsca zrobione dobrze — wzorzec istnieje u mnie

**Pominięte kontrole D3:** `->skip()` z powodem na kilkanaście linii, mówiącym **co dokładnie
zmierzył helpdesk** i **co musi zajść, żeby wróciły** (przeprojektowanie na rolę tekstu).
To jest wyjątek zadeklarowany, z powodem i z warunkiem znoszącym.

**Allowlisty `gitleaks`:** każda z `description`; jedna niesie **pełny pomiar** („zmierzone:
`leaks found: 1`, entropia 4.06, sekret = nazwa nadawcy SMS z kolejnego wiersza"). Wyjątek,
który sam się tłumaczy i który da się obalić.

---

# Werdykt i waga

1. **Kontrola unieważnienia — nieobecność bez deklaracji. Waga najwyższa**, bo dotyczy dostępu
   i rośnie z każdą trasą F2. **Domknięcie należy do `PODJETO-032`**, którą już mam wziętą:
   robiąc kontrolę middlewarem, muszę **przy okazji zadeklarować wyjątki jako dane**, bo
   inaczej naprawię zasięg i zostawię `D6`.
2. **`BEZ_DANYCH_OSOBOWYCH` — deklaracja bez powodu. Waga wysoka**, bo to najtańsza droga
   wyciszenia kontroli RODO. **Nie naprawiam w tej pozycji** (zgodnie z Twoim „nie musisz"),
   ale wpisuję do zaległościnika z iloczynem tuż za `PODJETO-032`.
3. **Dwa miejsca wzorcowe** — wzorzec jest u mnie, tylko nie został przyłożony tam, gdzie waży.

**Czego NIE sprawdziłem:** kolejek (Horizon — kto ma dostęp i czy są tam wyjątki), widoczności
DTO per rola (zasada twarda 8 — jeszcze nie ma kodu), oraz `.gitignore` jako listy wyjątków.
**Trzy miejsca niesprawdzone, wymienione, nie przemilczane.**
