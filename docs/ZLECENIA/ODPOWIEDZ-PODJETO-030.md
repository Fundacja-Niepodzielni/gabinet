# ODPOWIEDŹ do `PODJETO-030` — `R6B-2` / `R6A-1` ZAMKNIĘTE

Pozycja wzięta z własnego zaległościnika (S-2), źródło (2) i (3) naraz.

## Wada potwierdzona pomiarem, nie odczytem

Test nazywa się **„POZYTYWNY: żądanie PO wylogowaniu dostaje 401 — logout REALNIE zabija
sesję"**. Perturbacja izolująca pytanie — kasowanie wyłączone, **licznik nienaruszony**:

```php
if (true) {          // sesja NIE kasowana
    $skasowane++;    // ale `skasowane_sesje` nadal = 1
}
```

```
PRZED:  ✓ 1 passed (6 assertions)     ← test PRZESZEDŁ przy NIESKASOWANEJ sesji
```

**401 przychodziło ze ZNACZNIKA unieważnienia po `sid`, nie z kasowania sesji.** Jedna wartość
zgodna z dwoma światami: „sesja skasowana" i „sesja żyje, ale znacznik blokuje". Nazwa testu
obiecywała pierwszy, a dowodziła co najwyżej drugiego.

**Perturbacja musiała być dobrana precyzyjnie:** samo usunięcie `destroy()` psuje asercję
licznika w linii 555 i test padłby **z niewłaściwego powodu**, maskując badane pytanie.
Dlatego mutacja zostawia licznik i zabiera wyłącznie skutek.

**Waga wzrosła dziś:** naprawiając `RejestrSesji` (`ZLECENIE-024`) zmieniłem znacznik na
rozstrzyganie po OBECNOŚCI — czyli **wzmocniłem dokładnie tę ścieżkę, która maskowała brak
kasowania**. W `ODPOWIEDZ-020-klasa-3` zapisałem, że wpływu tej naprawy na świat tego testu
nie zmierzyłem. Teraz zmierzyłem.

## Naprawa: dwa twierdzenia, dwa NIEZALEŻNE sygnały

Dołożona asercja pyta **MAGAZYN**, czyli inną drogą niż mechanizm, który bada (reguła C1):

```php
expect(sesjaWMagazynie($idSesji))->toBe('', 'Sesja PRZEŻYŁA wylogowanie w magazynie…');
```

plus asercja stanu wyjściowego („sesji nie ma w magazynie PRZED wylogowaniem" → test nie ma
czego zabijać), żeby zieleń nie brała się z pustki.

## Para — ta sama perturbacja, dwa wyniki

```
PRZED naprawą testu:  ✓ 1 passed (6 assertions)      ← przepuszczała
PO naprawie testu:    ⨯ „Sesja PRZEŻYŁA wylogowanie w magazynie…”  (6 assertions)
kod zdrowy:           ✓ 1 passed (8 assertions)
```

Czerwień z **badanej przyczyny** i z nowej asercji, nie z licznika.

## Stan

```
220 zielonych · 2 pominięte · 1 CZERWONY (noga 1) · 1912 asercji · pint PASS, 88 plików
```

## Klasa 3 — rozliczenie po tej pozycji

| zamknięte | otwarte |
|---|---|
| R6B-13, R6B-15 (opanowany zapadką), **R6B-11**, **R6B-7**, **R6B-2/R6A-1**, + członek z komentarzami | **R6B-6**, **R6B-8**, R6B-1, N-12 |

R6B-1 i N-12 zależą od nogi 1 — ściganie ich przed jej naprawą to praca na ruchomym gruncie.
**Następna pozycja wg iloczynu: `R6B-8`**, potem `R6B-6`.

## Czego NIE zrobiłem

- **Nie sprawdziłem, czy 401 padłoby bez znacznika, ale z kasowaniem** — czyli czy samo
  kasowanie wystarcza. To jest kierunek odwrotny tej pary i **nazwany dług**.
- Pełnej bramki nie przebiegłem.
