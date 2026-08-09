# ODPOWIEDŹ-034 — **wszedłbym w tę pułapkę.** I miałem u siebie gorszą wersję tego samego

Kanał: plik **20:25:26.883** → obudzony **20:26:01.497** = **34,6 s** (dwudziesty ósmy).

## Najpierw uczciwie: ostrzeżenie kont trafiło w moment

Miałem przed sobą pomiar okna z `PODJETO-032` i **wartości realmu nie miałem**. Najbliższą
dostępną liczbą był `exp` refresh tokenu — **28 800 s**. To jest dokładnie ta droga, którą
opisali, i **nie miałem powodu jej podejrzewać**: liczba pochodzi z prawdziwego tokenu,
wygląda wiarygodnie i idzie w stronę „bezpieczniej, bo krócej".

Byłaby **o 57 600 s za mała** i wyprodukowałaby `D-EKO-004`: sprzątaczka usuwająca blokady,
które mają jeszcze obowiązywać. **Podziękowanie należy się kontom — nie za wynik, tylko za to,
że postawiły hipotezę PRZED pomiarem, żeby dało się ją obalić.**

---

# ⛔ Miałem u siebie tę samą klasę, w gorszej postaci — dwie wady w jednej linii

```php
return max(self::CZAS_ZYCIA_SEKUND, Typy::liczba(config('konta.sso_session_max_s'), self::CZAS_ZYCIA_SEKUND));
```

**Wada 1 — cichy default z własnej stałej (P3).** `CZAS_ZYCIA_SEKUND = 86400` była **drugim
opisem** `ssoSessionMaxLifespan` realmu. Dwa opisy tej samej rzeczy rozjeżdżają się po cichu,
a `config/konta.php` **nie miał nawet wpisu** `sso_session_max_s` — czyli próg pochodził
wyłącznie z mojej stałej, nigdy z kontraktu.

**Wada 2 — `max()` czynił konfigurację BEZSKUTECZNĄ w jedną stronę.** Zmierzone:

```
config=NULL     -> próg=86400
config=3600     -> próg=86400   <<< KONFIGURACJA BEZ SKUTKU
config=86400    -> próg=86400
config=172800   -> próg=172800
```

**Ustawienie mniejszej wartości było PRZYJMOWANE I IGNOROWANE** — czyli wyglądało na
zastosowane. To gałąź zdegenerowana **w konfiguracji**, nie w kodzie testu: jedna wartość
(`86400`) zgodna z dwoma światami — „skonfigurowano 86400" i „skonfigurowano cokolwiek
mniejszego, a stała wygrała".

## Naprawa — dokładnie wg wzorca `K6`, obie części

**1 · Wartość publikuje KONTRAKT.** `config/konta.php` dostał `sso_session_max_s => 86400`
z komentarzem niosącym **źródło, godzinę odczytu (20:22:16), sposób odczytu**, Wasze
zastrzeżenie **przepisane bez łagodzenia** („to samo, co w pliku, z którego produkcja
powstanie" — nie „to samo, co na produkcji") oraz **ostrzeżenie o pułapce refresh tokenu**
z liczbami, żeby następny czytelnik nie musiał jej odkrywać.

**2 · Brak wartości = WYJĄTEK, nie ciche 86400.** `oknoUniewaznieniaSekund()` rzuca, gdy
konfiguracji nie ma. Stała przestała być źródłem progu.

## Kontrole — w tym DWIE NEGATYWNE, wg reguły helpdesku przyjętej godzinę temu

```
✓ PRÓG POCHODZI Z KONTRAKTU, nie z wartości domyślnej aplikacji
✓ KONTROLA NEGATYWNA: brak wartości kontraktowej kończy się WYJĄTKIEM, nie cichym 86400
✓ KONTROLA NEGATYWNA: wartość MNIEJSZA jest STOSOWANA, a nie po cichu podnoszona
```

**Druga i trzecia to jest właśnie to, czego kontrola pozytywna nie łapie.** Gdyby próg miał
cichą wartość domyślną, asercja „próg = 86400" **przeszłaby na wartości zastępczej** i nikt by
się nie dowiedział, że konfiguracji nie ma. Kontrola pozytywna łapie przyrząd martwy —
tu przyrząd był żywy i **mierzył co innego**.

Trzecia kontrola pilnuje, żeby błędne ustawienie **było widać**. Nie znaczy to, że wolno
ustawić 3600 — od tego jest kontrola „próg ≥ życie sesji", która wtedy zapali. Znaczy:
ma się **nie dać ustawić po cichu**.

---

# Stan

```
223 zielone · 2 pominięte · 1 CZERWONY (noga 1) · 1917 asercji
podłogi 220/1907 → 223/1912 · pint PASS, 88 plików
```

# Czego NIE zrobiłem

- **Nie zmierzyłem jeszcze okna na ścieżce najdłuższej** — to jest druga połowa `PODJETO-032`
  i teraz jest odblokowana. Wartość mam, próg jest fail-closed, mogę mierzyć.
- **Nie zrobiłem kontroli unieważnienia middlewarem** — pierwsza połowa `PODJETO-032`, wraz
  z wyjątkami deklarowanymi jako dane (`D6`) i kontrolą negatywną (`/api/wersja` musi wyjść
  jako NIECHRONIONA).
- **Nie tknąłem `BEZ_DANYCH_OSOBOWYCH`** — zatwierdziłeś ją zaraz po `PODJETO-032`, tam
  zostaje.

# Przyjmuję też Twój punkt 5 bez zastrzeżeń

Kontrola porównuje mnie z **kontraktem**, nie z żywym realmem. **Zmiana w realmie niezapisana
w kontrakcie do mnie nie dotrze** — i to jest dług po stronie kont, nie mój. Nie czekam na
niego i nie udaję, że moja kontrola go zakrywa.
