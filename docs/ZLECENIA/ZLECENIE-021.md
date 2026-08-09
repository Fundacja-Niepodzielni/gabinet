# ZLECENIE-021 — `D-EKO-012` U CIEBIE: CZWARTE MIEJSCE, którego nie wymieniłem

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-021`
**Pozycja pilniejsza niż `ZLECENIE-020`** — klasa 3 poczeka, to nie.

---

## Znalazłeś to sam, poza moją listą — i to jest ważniejsze niż trzy, o które pytałem

Wymieniłem trzy miejsca (zamrożona reguła anulacji, okno linku płatności, blokada slotu).
**Ty znalazłeś czwarte, i jest to jedyne, które faktycznie ma wadę:**

> `RejestrSesji.php:99-102` — **znacznik unieważnienia przestaje blokować po `wygasa_at`,
> choć wiersz nadal jest w bazie.** Zmierzone, nie odczytane. Test czerwony, nazwany, zamierzony.

**To jest `D-EKO-012` w drugim systemie** — dokładnie ta sama wada, którą konta miały
w `isInvalidated`, u Ciebie w konsumencie. **Reguła przekrojowa dowiodła się w praktyce
w dobę po powstaniu:** powstała z cudzego defektu i znalazła bliźniaka tam, gdzie nikt go
nie szukał. Odnotowuję to jako argument za rejestrem, nie za moją listą trzech miejsc —
**moja lista była węższa niż klasa.**

## Dlaczego to jest pozycja PIERWSZA

Waga: **sesja osoby wylogowanej wraca do życia po upływie czasu**, mimo że wiersz unieważnienia
nadal istnieje. Osiągalność: **stan bieżący, z czerwonym testem na dowód.**
`D-EKO-004` w nowym brzmieniu mówi to wprost: **czas życia jest progiem sprzątania i nigdy nie
rozstrzyga o dostępie.**

## Zrobiłeś dobrze, że NIE naprawiłeś tego w środku cudzej weryfikacji

Twoje uzasadnienie („dotyka kontraktu BLK-22 z kontami") jest właściwe. **Teraz masz na to
własną pozycję i zgodę na dotknięcie tej ścieżki.**

**Wymagania:**
1. **Para czerwone-przed / zielone-po**, czerwień z badanej przyczyny — masz już czerwień,
   więc pilnuj tylko, żeby zniknęła **z tego samego powodu**, a nie przez obejście ścieżki.
2. **Rozstrzyganie na OBECNOŚCI wiersza**, nie na jego wieku. Wiek zostaje **wyłącznie progiem
   sprzątania** na ścieżce mutującej.
3. **Kierunek 0:** wiersz ze stemplem pustym, nieczytelnym albo **z przyszłości** → **blokuje**.
   Przy zabezpieczeniu niepewność ma jedną dopuszczalną odpowiedź.
4. **⛔ Nie zrób z wieku „prawa wstępu na odwrót"** — wiek ma **odbierać** dostęp, nigdy go
   **przyznawać**. Konta wpadły dziś dokładnie w tę pułapkę po drugiej stronie.
5. **Sprzątanie wygasłych wierszy** — jeśli je dokładasz, wynik kasowania **odbierany
   i sprawdzany odczytem** („polecenie się wykonało" ≠ „wiersz zniknął").

## Strona kontraktowa — biorę ją na siebie, Ty nie wchodzisz do kont

Konta są w trakcie własnego pomiaru na tej samej klasie (druga strona ich defektu:
`SessionStore` bez sprawdzania wieku). **Przekażę im Twoje znalezisko** — jeśli po obu stronach
wyjdzie, że kontrakt BLK-22 dopuszcza dwie różne interpretacje `wygasa_at`, **to jest wada
kontraktu, nie dwóch implementacji**, i wtedy poprawka idzie do erraty.

**Napisz w odpowiedzi jedno zdanie:** czy `wygasa_at` w kontrakcie jest opisany jako **próg
sprzątania**, czy jako **termin ważności unieważnienia** — bo jeśli to drugie, obie
implementacje były zgodne z dokumentem, a dokument był zły.

## Reszta bez zmian

`ZLECENIE-020` (klasa 3) zostaje **następną** pozycją. Bramki po własnych zmianach nadal
nie przebiegasz jako weryfikacji — słusznie to zapisałeś.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · ścieżki bezwzględne, nigdy `cd` · nic poza fundację ·
kontrola pozytywna przy każdym wyszukiwaniu · godzina odczytu w werdyktach o cudzym kodzie.
