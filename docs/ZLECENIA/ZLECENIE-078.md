# ZLECENIE-078 · 19.08.2026 · OD architekta DO sesji KOD-F1 — po rundzie 11

**Runda 11: trzy znaleziska** (`ODPOWIEDZ-075` + `RUNDA-11-RAPORT.md`).
**Zamrożenie ZDJĘTE.** Zbieżność: 29 → 9 → 2 → 5 → 1 → **3**.

## 0. Diagnoza architekta — dlaczego NIE zlecam kolejnej warstwy

Twoje wąskie gardło ma dziś cztery warstwy i **każda runda znajduje piąte piętro**:

```
R8-1   nazwa pola              → dopisaliśmy nazwy
R9-1   sposób dostarczenia     → dopisaliśmy kształty
R10-1  składnia odczytu        → usunęliśmy listę metod (dobry ruch)
R11-1  pole KONTRAKTOWE użyte jako tożsamość  ← warstwa 3 milczy SŁUSZNIE
R11-2  inna metoda fasady (`zaktualizuj`)     ← warstwy 2 i 4 jej nie znają
```

**Wzorzec jest jednoznaczny: kontrola oparta na ANALIZIE KSZTAŁTU KODU zawsze ma brzeg,
a brzeg zawsze da się przekroczyć.** Piąta warstwa da szóste piętro. Dlatego zmieniamy
rodzaj obrony: **z wykrywania złego kształtu na uniemożliwienie złego stanu.**

## 1. Kierunek: stan nielegalny ma być NIEWYRAŻALNY (typ, nie skaner)

**Wymaganie:** `SesjaKonta::zaloz()` **oraz** `zaktualizuj()` (i każda inna metoda
ustanawiająca lub zmieniająca tożsamość) przyjmują **wyłącznie obiekt wartości**
(np. `RoszczeniaZweryfikowane`), którego **jedynym miejscem powstania** jest
`WalidatorTokenu` po sprawdzeniu podpisu, wystawcy, odbiorcy i czasu.

Wtedy:
- **R11-1 znika z definicji** — `$request->query('code')` jest napisem, nie obiektem
  wartości; nie da się go podać, choćby pole było w kontrakcie;
- **R11-2 znika z definicji** — `zaktualizuj` ma ten sam wymóg typu, więc podmiana
  `sub` wartością z żądania jest niewykonalna, a nie „wykrywalna";
- **szóste piętro nie powstaje** — nie ma listy do obejścia; egzekwuje to statyka
  (Larastan) przy każdym przebiegu, nie skaner tekstu.

**Kontrole odbioru (obowiązkowe):**
1. **Negatywne**: oba wektory rundy 11 dosłownie — `['sub' => $request->query('code')]`
   przez zmienną pośrednią oraz `zPodmienionymi(['sub' => X])` + `zaktualizuj` — muszą
   **przestać się kompilować/przechodzić statykę albo rzucać**, nie „zapalać kontrolę".
   Jeśli któryś nadal przechodzi, kierunek jest niewykonany.
2. **Konstrukcja obiektu wartości jest niepodrabialna**: konstruktor prywatny, brak
   settera, brak `fromArray`, żadnego `new` poza walidatorem — z kontrolą pilnującą
   tego niezmiennika (to jedyne miejsce, gdzie zostaje kontrola strukturalna, bo dotyczy
   JEDNEJ klasy, nie całego kodu).
3. **Pozytywna**: legalny przepływ (callback → walidator → obiekt → `zaloz`) działa.
4. **Zasięg**: wypisz WSZYSTKIE metody zmieniające tożsamość w sesji i wykaż, że każda
   ma ten wymóg — R11-2 wzięło się stąd, że skanowaliśmy jedną nazwę.
5. Dotychczasowe warstwy 1–4 **zostają** jako druga linia (obrona w głąb), ale
   **przestają być jedyną** — i tak je opisz w nagłówkach.

## 2. R11-3 — kotwica ma weryfikować LICZBĘ, nie swoją obecność

Fałszywe „999 scenariuszy — zmierzone na `528adc3`" przeszło. Kotwica ma być
**sprawdzalna**: kontrola wyciąga SHA, czyta `perturbacje.sh` **z tamtego commita**
(`git show <SHA>:skrypty/perturbacje.sh`) i porównuje liczbę. Kontrola negatywna:
fałszywa liczba przy prawdziwym SHA **zapala**. Inaczej kotwica jest ozdobą — a to
nawrót R9-5 wpuszczony naszą własną poprawką.

## 3. Procedura i granica czasowa

Naprawa → bramka OD ZERA + pełny zestaw perturbacji → commit → **bramka po commicie** →
nowe zamrożone SHA → **meldunek ODPOWIEDZ-078**. Po nim runda 12.

**Jedna rzecz, którą mówię wprost, bo dotyczy Twojej pracy, nie tylko kodu:**
to nie jest kolejna łatka — to zmiana rodzaju obrony i może kosztować więcej niż
poprzednie cykle. **Nie skracaj jej.** Jeżeli w trakcie okaże się, że wymóg typu
wymaga ruszenia większej powierzchni, niż zakładam (np. pacjenckiej ścieżki logowania
kodem), zgłoś to ZANIM zaczniesz — rozstrzygnę zakres, zamiast zastawać go po fakcie.

**Nie naprawiaj niczego poza R11-1, R11-2, R11-3** — chyba że wyjdzie z pytania
„krok dalej", wtedy z pomiarem i osobnym wpisem.
