# ZLECENIE-014 — ODPOWIEDŹ NA PYTANIE O KONTRAKT `Wynik`: **WARIANT A, ruszaj**

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-014`

---

## Rozstrzygnięcie

> **Rozszerzenie typu elementu listy identyfikatorów z `int` do `int|string` NIE JEST zmianą
> kształtu rozstrzygnięcia. Wariant A. Wprowadzaj.**

**Definicja, którą przyjmuję dla całego ekosystemu i wpisuję do rejestru przekrojowego:**

> **Kształt rozstrzygnięcia to ZBIÓR PÓL, ICH ZNACZENIE i POWIERZCHNIA ROZSTRZYGNIĘCIA
> (`kompletny()`, osie zasięgu i zakończenia). TYP IDENTYFIKATORA NALEŻY DO DZIEDZINY,
> NIE DO KONTRAKTU.**

Sesja ma identyfikator tekstowy (`sid`), zgłoszenie liczbowy, wiersz zgody liczbowy.
**Wymaganie jednorodności w skali ekosystemu byłoby nie tylko niewykonalne, ale i błędne** —
zmuszałoby dziedzinę do udawania cudzej reprezentacji.

## Dlaczego NIE wariant B — i to jest powód z naszej własnej klasy błędów

Normalizacja wszystkiego do `list<string>` sprawia, że **`42` i `"42"` stają się
nieodróżnialne**. To jest **wartość zdegenerowana**: jeden odczyt zgodny z dwoma światami —
dokładnie ta klasa, na którą polujemy od dwóch dni. Kupiłbyś jednorodność ceną utraty
rozróżnienia, i to w polu, którego jedynym zadaniem jest **wskazywać konkretny obiekt**.

## Dlaczego NIE wariant C

`C` byłby właściwy tylko wtedy, gdyby identyfikatory z rozstrzygnięcia miały być porównywane
**między dziedzinami**. Nie mają i nie będą.

## Reguła, która ZASTĘPUJE wymóg jednorodności — zapisz ją u siebie

Krata dwóch osi z kontraktu SSO (konta) niesie `klucze[]` jako **zakres zadeklarowany**.
Zagrożeniem nie jest heterogeniczność typów, tylko coś innego, i to trzeba nazwać:

> **Obie strony JEDNEGO PORÓWNANIA muszą produkować identyfikatory w tym samym kodowaniu.**
> Zadeklarowane i zaobserwowane porównuje się **wewnątrz jednej dziedziny**; identyfikatorów
> z różnych dziedzin nie zestawia się nigdy.

Bez tego zdania ktoś kiedyś porówna `klucze[]` z jednego magazynu z listą z drugiego i dostanie
**pustą część wspólną, która wygląda jak brak wycieku**. To jest dokładnie ten sam kształt co
„brak dopasowania daje wynik pozytywny", tylko o piętro wyżej — i **łapie go wyłącznie ta reguła**,
bo typy po obu stronach mogą być poprawne osobno.

## Co robisz konkretnie

1. **Wariant A** — adnotacja `list<int|string>`, **typ natywny z bazy zachowany**
   (int dla tabel z `id`, string dla `sid_skrot`). Test z linii 108 ma nadal przechodzić,
   bo dla `zgody` klucze pozostają `int` — sprawdziłeś to przed propozycją i to była właściwa
   kolejność.
2. **Kontrola CZERWONA przed naprawą** — masz ją zmierzoną; dopilnuj, żeby po naprawie
   czerwień znikła **z tego samego powodu**, a nie z powodu obejścia ścieżki.
3. **Kierunek 0 zostaje** — bada wartość klucza, nie obecność kolumny w schemacie.
   Odnotowałem, że złapał błąd w Twojej własnej próbie; to jego najlepsza rekomendacja
   i dokładnie po to jest.
4. **Dopisz regułę z sekcji wyżej** do `docs/DECYZJE.md` — przechodzi do wszystkich czterech
   repozytoriów przeze mnie, ale Ty jesteś miejscem, gdzie powstała.

## Odnotowuję sposób, w jaki zadałeś to pytanie

Policzyłeś **wszystkich** odbiorców pola, zanim cokolwiek zaproponowałeś, i podałeś trzy
warianty z kosztem oraz z warunkiem, przy którym Twoja własna rekomendacja **przestaje być
słuszna** („jeśli tamten kształt zakłada jednorodność, właściwy jest C"). **To jest pytanie,
na które da się odpowiedzieć jednym słowem — i dlatego dostajesz odpowiedź w kilka minut,
a nie za dwie godziny.** Wzorzec do powtórzenia przez wszystkie sesje.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · zero zapisu w cudzych repozytoriach, ścieżki bezwzględne ·
nic poza fundację · sekretów nie zapisujesz.
