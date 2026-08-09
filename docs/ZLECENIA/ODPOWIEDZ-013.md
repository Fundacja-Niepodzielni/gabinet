# ODPOWIEDŹ-013 — D-1 rozstrzygnięte Z MATERIAŁU; D-3 przygotowane

## D-1 · Czym jest „4" — **ustalone, nie zgadnięte. Limity są DWA i są rozłączne**

Pytałeś, czy 4 to limit specjalisty, limit puli, czy pomyłka. **Odpowiedź: limit specjalisty —
i ma już inną nazwę w konfiguracji.** Nie musiałem wybierać, bo kod to rozstrzyga.

`backend/app/Reguly/ZestawRegul.php`, dwa osobne pola z osobnymi komentarzami:

```php
/** Limit wizyt niskopłatnych na pacjenta (WIZYT, nie godzin). */
public int $limitNiskoplatnychWizyt,          // = 10   (D-2026-08-07-08)

/** Limit podażowy: ile terminów niskopłatnych tygodniowo na specjalistę. */
public int $limitNiskoplatnychNaTydzien,      // = 4    (dziennik makiety, rozdz. 24)
```

W konfiguracji bazodanowej też są rozdzielone: `limit_niskoplatnych_wizyt`
i `limit_niskoplatnych_na_tydzien`.

**Zatem: pierwsza z Twoich trzech możliwości, warunek już spełniony** — 4 jest limitem po
stronie **specjalisty** i **nosi inną nazwę**, więc nie ma czego usuwać ani zmieniać w kodzie.
**Nie ma pomyłki do skasowania i nie ma ryzyka zaimplementowania obu jako jednego.**

**Co było mylące i co poprawiłem — jedno zdanie w planie.** `PLAN-FAZ.md:223` mówi wprost
„limit podażowy 4 terminy/tydzień/**specjalista**". Ale `PLAN-FAZ.md:245` (faza F2) zapisano:

```
limit 4 niskopłatnych/tydzień (ISO, reset poniedziałek)
```

— **bez powiedzenia, czyj to limit**. To jest jedyne miejsce, które mogło doprowadzić do
zaimplementowania limitu tygodniowego na PACJENTA. Uzupełnione o właściciela limitu
i odsyłacz, żeby dwa różne limity nie zlały się w jeden.

**Trzeci ślad, dla porządku:** `ZestawRegul.php:65-66` odnotowuje, że wiersze mówiące
„4 h na osobę" to **niedoczyszczony ślad sprzed podniesienia limitu** — czyli istniało kiedyś
jeszcze jedno, trzecie odczytanie liczby 4, i zostało już opisane jako nieaktualne.

### Konsekwencja dla modelu — **zatrzymuję się, zgodnie z Twoim warunkiem**

Limit **10 sumarycznie na osobę** nie wymaga zmiany modelu **co do wartości**: siedzi
w konfiguracji reguł i jest wersjonowany. Wymaga jej **mechanizm liczenia**, i to w jednym
konkretnym miejscu:

> **Licznik sumaryczny musi wiedzieć, KTO jest tą samą osobą.** Pacjent rezerwuje jako gość,
> konto zakłada później — a wtedy wizyty sprzed założenia konta albo doliczą się do limitu,
> albo nie, i dziś nic tego nie rozstrzyga.

To dotyka zasady twardej 2 (wiązanie po `sub`, nigdy po e-mailu) — bo gość **nie ma** `sub`.
**Nie wprowadzam niczego przy okazji.** To jest praca F2 i osobna decyzja: po czym łączymy
rezerwację gościa z kontem założonym później, skoro nie wolno po e-mailu.

---

## D-2 · Polityka scalania — przyjęte, nic u mnie do zmiany

Odnotowuję. `D-2026-08-07-09` obowiązuje bez zmian: push zawsze · merge do `main` po zielonej
**niezależnej** weryfikacji · deploy i wystawienie publiczne wyłącznie za zgodą.

---

## D-3 · Okresy retencji — `null` zostają; **opisy dla właściciela GOTOWE**

Żadnej wartości domyślnej nie wpisałem i nie wpiszę (D-EKO-009).

Dopisałem do rejestru pole **`opis_dla_czlowieka`** — jedno zdanie po polsku przy każdej
z **siedmiu** tabel (nie sześciu: dołożyłem też trzy anonimizowane, bo właściciel i tak
zapyta, dlaczego ich nie ma na liście). Mówi **co tam leży** i **czyje to dane**:

| tabela | tryb | co tam leży i czyje to dane |
|---|---|---|
| `pacjenci` | anonimizacja | Osoby korzystające z pomocy: imię, nazwisko, e-mail, telefon oraz to, że zgłosiły się po pomoc psychologiczną. **Dane pacjentów, najwrażliwsze w całym systemie.** |
| `zgody` | **kasowana** | Dowody, że pacjent zgodził się na regulamin i przetwarzanie danych: kiedy, na którą wersję dokumentu i z jakiego adresu. **Dane pacjentów; nasz dowód w razie sporu.** |
| `rezerwacje` | anonimizacja | Umówione wizyty: kto, do kogo, kiedy, za ile i czy zapłacono. **Dane pacjentów połączone z dokumentem księgowym.** |
| `zdarzenia_rezerwacji` | **kasowana** | Historia tego, co działo się z wizytą: umówiona, przełożona, odwołana, opłacona. **Dane pacjentów pośrednio** — pokazują, kto i kiedy korzystał z pomocy. |
| `uniewaznione_sesje` | **kasowana** | Techniczne znaczniki wylogowania: skrót identyfikatora sesji i godzina. **Nie ma tu żadnych danych o osobie** — służy tylko temu, żeby wylogowanie w jednym miejscu działało wszędzie. |
| `users` | **kasowana** | Konta pracowników i współpracowników fundacji (koordynatorzy, administracja). **Dane personelu, nie pacjentów.** |
| `specjalisci` | anonimizacja | Dane psychologów i terapeutów: imię, nazwisko, kontakt, opis do strony. **Dane współpracowników, częściowo publiczne.** |

Opis mieszka **w rejestrze, nie w dokumencie** — inaczej przy dodaniu tabeli powstałby wiersz
bez opisu i nikt by tego nie zauważył. Rejestr jest jednym źródłem prawdy także dla tego pola.

**Rzecz warta powiedzenia właścicielowi przy tej rozmowie:** dwie z tych tabel nie są
w ogóle kasowane, tylko **anonimizowane** (`pacjenci`, `rezerwacje`) — bo rezerwacja jest
dokumentem księgowym i musi zostać. Pytanie do fundacji brzmi więc dla nich inaczej: nie
„po jakim czasie usunąć", tylko **„po jakim czasie odciąć wizytę od osoby"**.

---

## Kolejność

`ZLECENIE-012` było pozycją bieżącą i **jest zamknięte** — rozstrzygnięcie o `Wynik`
przyszło w `ZLECENIE-014`, wariant A wprowadzony, czerwień znikła z tego samego powodu.
Szczegóły w `ODPOWIEDZ-014.md`.

## Zakazy

Zero `main`, merge, deploy · zero zapisu w cudzych repozytoriach, ścieżki bezwzględne ·
nic poza fundację · sekretów nie zapisuję · `System-rezerwacji` nietknięte.
**Sprzeczności: brak.**
