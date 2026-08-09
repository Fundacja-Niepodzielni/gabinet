# ZLECENIE-041 — STUDIUM WYKONALNOŚCI: SMSAPI. Lista rzeczy, KTÓRYCH NIE DA SIĘ, jest ważniejsza.

**Od:** architekt · **09.08.2026, noc** · potwierdź zwyczajnie · **weź po `PODJETO-032`,
przed `BEZ_DANYCH_OSOBOWYCH`** · **to jest czytanie dokumentacji, nie budowa**

---

## 1 · Po co — polecenie właściciela, przytaczam sens

> *„Zanim przejdziemy do realizacji, sprawdź dokumentację […] całość, która jest nam potrzebna,
> żeby nie było żadnych kwiatków, że coś się nie da w trakcie."*

**Standard odbioru tej pozycji:** przy każdym wymaganiu **wskazanie w dokumentacji + wersja +
data odczytu + odnośnik**. **„Da się" bez wskazania miejsca nie jest odpowiedzią.**

> **⚠ NAJWAŻNIEJSZE: lista rzeczy, KTÓRYCH NIE DA SIĘ ALBO WYMAGAJĄ CZEGOŚ NIEOCZYWISTEGO,
> jest cenniejsza od listy tego, co działa.** Zielone pozycje niczego nie zmieniają w planie.
> **Zmieniają go czerwone — i to po to jest ta pozycja.**

## 2 · Co ma być sprawdzone

**A · Rejestracja nadawcy**
Wymagane dokumenty, **czas trwania**, kryteria odrzucenia. **Zmierzone przeze mnie i wymaga
potwierdzenia u źródła: nazwa może mieć maks. 11 znaków bez polskich liter — „Niepodzielni"
ma 12.** Jakie warianty są dopuszczalne (`Niepodziel`, `NIEPODZIELN`)? **To blokuje wniosek,
który właściciel ma złożyć.**

**B · Kody jednorazowe — czy SMSAPI ma do tego OSOBNY produkt**
Wielu operatorów ma gotową usługę weryfikacji numeru (generowanie kodu, sprawdzanie, limity).
**Sprawdź, czy istnieje — ale NIE rekomenduj jej odruchowo.**

> **⚠ Jeśli kody generuje i sprawdza operator, to dowód tożsamości pacjenta zaczyna żyć
> u DOSTAWCY SMS, poza naszym systemem logowania.** To może być złamanie `D-EKO-001` przez
> tylne drzwi — **to samo, co odrzuciłem przy WordPressie.** Opisz możliwość, **wskaż ten
> konflikt wprost i zostaw rozstrzygnięcie mnie.**

**C · Wysyłka: API, raporty doręczeń, obsługa błędów**
Jak wygląda potwierdzenie doręczenia · **czy wiemy, że SMS NIE dotarł, i po jakim czasie** ·
kody błędów (numer nieistniejący, poza zasięgiem, odrzucony przez operatora).
**To jest krytyczne dla przepływu logowania: jeśli nie odróżnimy „nie dotarł" od „nie wpisał",
nie umiemy podpowiedzieć człowiekowi, co ma zrobić.**

**D · Limity, koszty, zabezpieczenia**
Cennik Polska vs zagranica · **zawężanie listy krajów i limity per kraj** (zmierzone, że
istnieje — potwierdź, jak się je ustawia) · limity tempa po stronie operatora ·
**co się dzieje po wyczerpaniu środków** (czy wysyłka cicho przestaje działać — bo wtedy
**logowanie pacjentów przestaje działać razem z nią**, i to jest awaria, o której musimy
wiedzieć NATYCHMIAST, nie z reklamacji).

**E · Wymogi formalne wobec nadawcy**
Zgody marketingowe nas nie dotyczą (to wiadomości transakcyjne) — **ale sprawdź, czy operator
tak to kwalifikuje**, bo od tego zależy, czy potrzebna jest osobna zgoda przy rezerwacji.

**F · Środowisko testowe**
Czy da się testować **bez wysyłania prawdziwych SMS-ów** i bez zarejestrowanego nadawcy.
**To rozstrzyga, czy budowa naprawdę nie czeka na wniosek właściciela** — twierdziłem, że nie
czeka, i chcę to potwierdzone dokumentacją, a nie moim przekonaniem.

## 3 · Czego NIE robisz

**Nie zakładasz konta, nie wysyłasz żadnego SMS-a, nie zapisujesz żadnych danych dostępowych.**
Sekretów nie zapisujemy do plików — bez wyjątków, także „testowych".

## 4 · Forma odpowiedzi

Tabela: **wymaganie · werdykt (DA SIĘ / NIE DA SIĘ / WYMAGA X) · źródło i data**.
Na końcu **osobna sekcja „czego nie znalazłem w dokumentacji"** — bo brak informacji
to nie jest to samo co informacja o braku, i ta różnica już nas dziś kosztowała.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · realmu nie dotykasz · ścieżki bezwzględne · nic poza fundację ·
**S-2 i S-3 obowiązują.**
