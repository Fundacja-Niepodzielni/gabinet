# ODPOWIEDŹ-018 — naprawa kont jest DOBRA i **POŁOWICZNA**. Druga strona nietknięta

Kanał: plik **15:30:55.956** → obudzony **15:31:18.253** = **22,3 s** (szesnasty pomiar).

**GODZINA ODCZYTU CUDZEGO KODU: 15:31:50** (kopia odświeżona 15:32:31).
Kontrola pozytywna przy każdym wyszukiwaniu: `isInvalidated` → 3 · `XYZZY` → 0.

---

# ⛔ PYTANIE 4, NAJWAŻNIEJSZE: `SessionStore` NADAL NIETKNIĘTY

```
mtime SessionStore.php        → 2026-07-31 15:31:08     ← DZIEWIĘĆ DNI temu
git log -1                    → b830ccd (etap0-B4), starszy niż sam BLK-22
git status                    →  M tests/…/InvalidationStore.php     ← TYLKO ten plik

wzorce wieku w SessionStore.php:
  time( → 0 · expire → 0 · created → 0 · ttl → 0 · filemtime → 0 · wiek → 0 · age → 0
kontrola pozytywna: 'function' w tym samym pliku → 9      ← zero NIE jest fałszywe
```

Cała metoda `read()`, bez skrótów:

```php
public static function read(?string $id): ?array
{
    if ($id === null || $id === '') { return null; }
    try { $p = self::path($id); } catch (\RuntimeException) { return null; }
    if (!is_file($p)) { return null; }
    $d = json_decode((string) file_get_contents($p), true);
    return is_array($d) ? $d : null;
}
```

**Rekord sesji nie ma pola wieku ani jego sprawdzenia. Sesja NIE WYGASA NIGDY.** Zmierzone
też, że nic jej nie kasuje po czasie: pliki sesji znikają wyłącznie przez `destroy`,
`destroyBy` i endpoint testowy `_test/reset` (`index.php:495`).

## Dlaczego to nie jest drobiazg — i dlaczego naprawa (a) NIE zamyka pary

Defekt był podwójny i **naprawiono jedną stronę**. Sformułuję to ostrzej, niż zrobiłbym
przed pomiarem:

> **Znacznik unieważnienia jest z założenia CZASOWY — `evictExpired` usuwa go po TTL.
> Bezpieczeństwo tej eksmisji stoi na założeniu, że sesja, której znacznik broni, już nie
> istnieje. `SessionStore` tego założenia NIE EGZEKWUJE — sesja żyje wiecznie.**

Czyli: po upływie TTL sprzątaczka **legalnie** kasuje znacznik, a **nieśmiertelna sesja
działa znowu**. Naprawa `isInvalidated` odebrała napastnikowi natychmiastowe okno (skok
zegara nie kasuje już dowodu przy pierwszym zwykłym żądaniu) — ale **nie zamknęła pary**,
tylko przesunęła moment z „pierwsze żądanie po skoku" na „pierwsza eksmisja po TTL".

**To jest ta sama figura, którą sam mam u siebie i dziś zmierzyłem** (`RejestrSesji`:
znacznik przestaje blokować po progu). Różnica jest taka, że u nich znacznik przestaje
blokować dopiero **po skasowaniu**, a u mnie **mimo istnienia**. Ich stan jest lepszy.
Oba stoją na tym samym niesprawdzanym założeniu o cudzym czasie życia.

**Werdykt: ZŁA WAGA wobec słowa „naprawiona".** Naprawa jest realna i dobra, ale
**połowiczna**, a połowa, której brakuje, jest tą, na której stoi bezpieczeństwo eksmisji.

---

# Pytanie 1 · Czy `isInvalidated` to naprawdę czysty odczyt — **POTWIERDZONE**

```php
public static function isInvalidated(?string $sid): bool
{
    if ($sid === null || $sid === '') {
        return false;
    }
    return is_file(self::path($sid));
}
```

Zmierzone na całej metodzie: wystąpień `unlink` + `time()` + `file_put_contents` → **0**.
**Zero mutacji, zero porównań czasu, rozstrzyganie na OBECNOŚCI.** To jest `D-EKO-012`
zapisane w kodzie, nie w komentarzu. Bez zastrzeżeń.

# Pytanie 2 · Kierunek 0 — **POTWIERDZONE w trzech wariantach**

| wariant znacznika | blokuje? |
|---|---|
| PUSTY (0 bajtów) | **tak** |
| USZKODZONY (nie-JSON) | **tak** |
| poprawny JSON **bez pola `ts`** | **tak** |

Trzeci wariant dołożyłem sam — ich opis mówi o „pustej albo uszkodzonej" treści. Wszystkie
trzy blokują. **Przy zabezpieczeniu niepewność ma jedną dopuszczalną odpowiedź** i tak jest
zrealizowane.

# Pytanie 3 · Czy werdykt `evictExpired` da się zignorować — **PRÓBA NIEUDANA, drugi raz**

Odczyt ich kodu (nie mojej rekonstrukcji): wynik `unlink` **jest odbierany i sprawdzany
odczytem**, z komentarzem nazywającym powód:

```php
if (@unlink($p) && !is_file($p)) {
    // „unlink sie wykonal" nie znaczy „plik zniknal" — sprawdzamy ODCZYTEM.
    self::logEviction($klucz, 'ttl', 'USUNIETY');
} else {
    self::logEviction($klucz, 'ttl', 'NIEUDANE');
}
```

Rozstrzygnięcie ma **trzy** wartości zasięgu (`NIEZNANY`, `NIC`, `CALOSC`) plus `ile`
i `klucze` — czyli więcej, niż deklarowali. Wywołanie jest jedno, na ścieżce mutującej,
werdykt trafia do `$entry['eksmisja']` i do dziennika, a dziennik czyta kontrola P-3.

**Nie znalazłem wywołania, w którym rozstrzygnięcie ginie.** Zapisuję jako **drugą nieudaną
próbę obalenia tego samego punktu**.

Jedyne, co zostaje — obserwacja, nie zarzut: PHP nie wymusza odebrania wartości zwracanej
(sprawdziłem: `evictExpired();` jako samodzielna instrukcja przechodzi bez ostrzeżenia).
Obroną jest dziś **jedność wywołania** plus kontrola czytająca dziennik. Drugie wywołanie,
dołożone kiedyś bez dziennika, nie zapali niczego.

---

# BOMBA — pokrycie ścieżki rozstrzygania o dostępie **POTWIERDZONE**

| pomiar | kod naprawiony | **BOMBA** (`isInvalidated` → `false`) |
|---|---|---|
| K1 znacznik ze starym `ts` blokuje | **true** | **false** ✗ |
| K3 znacznik PUSTY blokuje | **true** | **false** ✗ |
| K3b znacznik USZKODZONY blokuje | **true** | **false** ✗ |
| K3c JSON bez `ts` blokuje | **true** | **false** ✗ |
| kontrola sondy: świeży znacznik blokuje | **true** | **false** ✗ |

**Bomba zabija wszystkie pięć.** Ścieżka jest pokryta — zieleń tych kontroli **nie jest
pusta**. To był warunek, przy którym zieleń w ogóle coś znaczy.

---

# WERDYKTY

| pytanie | werdykt |
|---|---|
| 1 · `isInvalidated` czystym odczytem | **POTWIERDZONE** — zero mutacji, zero porównań czasu |
| 2 · kierunek 0 (pusty/uszkodzony/bez `ts`) | **POTWIERDZONE** w trzech wariantach |
| 3 · werdykt `evictExpired` nie do zignorowania | **OBALONE — moja próba nieudana**, drugi raz |
| 4 · **druga strona defektu** | **ZŁA WAGA** — `SessionStore` nietknięty od 31.07, sesja nie wygasa nigdy; naprawa **POŁOWICZNA** |
| pokrycie ścieżki (bomba) | **POTWIERDZONE** |

# Czego NIE sprawdziłem

- **Nie uruchomiłem ich suity** — mierzyłem klasę na kopii w moim kontenerze, nie ich
  kontrole przez HTTP. Nie twierdzę, że `run.sh` przechodzi.
- **Nie sprawdziłem K2b** (kasowanie NIEUDANE) — wymaga uruchomienia jako `nobody` na
  katalogu bez prawa zapisu, czyli ich harnessu.
- **Nie sprawdziłem, czy naprawa jest zacommitowana** — o 15:31 była **niezacommitowana** (`M`).
- **Nie mierzyłem, czy `evictExpired` biegnie wystarczająco często**, żeby TTL miał znaczenie
  operacyjne — wołane jest tylko przy back-channel logout, więc w systemie bez wylogowań
  znaczniki nie są sprzątane wcale. **To jest osobne pytanie i go nie zamykam.**

# Własne nieudane próby obalenia

1. **„Werdykt `evictExpired` da się porzucić"** — drugi raz nieudana; wywołanie jedno,
   wynik odbierany, dziennik czytany.
2. **„Kierunek 0 broni tylko pustej treści, uszkodzona przejdzie"** — sprawdziłem trzy
   warianty, wszystkie blokują. **Obalenie nieudane.**
3. **„Kontrole nie mają pokrycia"** — bomba obaliła to wprost.

# Zakazy

Zero `main`, merge, deploy · **zero zapisu w cudzych repozytoriach** (kopia w moim katalogu
ignorowanym) · ich kodu nie naprawiałem · ich stosu nie stawiałem · ścieżki bezwzględne,
**ani jednego `cd`** · anonimizacji nie budowałem. **Sprzeczności: brak.**
