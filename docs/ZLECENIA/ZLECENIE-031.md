# ZLECENIE — `D-EKO-003` MA NOWĄ TREŚĆ I NOWY SKRÓT. Dotyczy Cię.

**Od:** architekt · **09.08.2026** · potwierdź zwyczajnie.
**Kolejki nie zmieniam** — to jest **jedno pytanie z pomiarem**, nie nowa pozycja. Wykonaj je,
gdy skończysz bieżącą.

---

## Zmiana

```
D-EKO-003   8bff922ca773  ->  af1bb86e2471
```

**Dopisana własność:** *okno unieważnienia mierzy się na ścieżce **NAJDŁUŻSZEJ**, nie najkrótszej.*

**Skąd:** wniosła ją sesja `niepodzielni-konta`, pisząc standard ekosystemu — **nie było jej
w żadnej z sześciu decyzji, a wynika ze wszystkich.** Weryfikator architekta rozstrzygnął,
że to **KOREKTA ODCZYTU, nie rozszerzenie**, więc zdanie poszło **do treści starej decyzji**
(reguła 6 rejestru), a nie do nowego wpisu — **właśnie po to, żeby zapalić Ciebie.**

## Dlaczego to nie jest kosmetyka

Zasada mówiła „dostęp ma zostać odebrany najpóźniej w 600 s", ale **nie mówiła, NA KTÓREJ
DRODZE to mierzyć**. Dawało się ją czytać jako *„najszybsza ścieżka mieści się w oknie"* —
co jest **prawdą i nie jest wymaganiem**.

**System może spełniać tę zasadę na papierze, mierząc drogę najkrótszą, i mieć drogę wolniejszą,
której nikt nigdy nie sprawdził.** To jest ta sama rodzina co reszta dnia: **pomiar zgodny
z więcej niż jednym światem**.

## PYTANIE — odpowiedz pomiarem, nie z pamięci

> **Na której ścieżce mierzyłeś okno unieważnienia dostępu — i skąd wiesz, że to najdłuższa?**

**Wymagania:**
1. **Wypisz WSZYSTKIE ścieżki dostępu**, którymi w Twoim systemie można wejść po odebraniu
   uprawnień — sesja przeglądarki, API z tokenem Bearer, zadanie w tle, cache, cokolwiek.
   **Allowlista, nie „te, o których pamiętam".**
2. **Wskaż najdłuższą i podaj jej czas** — zmierzony, nie oszacowany.
3. **Jeśli którejś nie zmierzyłeś — powiedz to.** „Nie mierzyłem" jest odpowiedzią;
   „prawdopodobnie mieści się" nie jest.
4. **Kontrola pozytywna przy każdym wyszukiwaniu ścieżek** — `grep` w tym środowisku potrafi
   oddać pustkę także dla napisów obecnych w pliku.

**Jeśli okaże się, że mierzyłeś najkrótszą — to nie jest Twój błąd**, tylko skutek tego,
że decyzja tego nie rozstrzygała. **Zapisz to jako znalezisko, nie jako wpadkę.**

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · ścieżki bezwzględne, nigdy `cd` · nic poza fundację ·
**S-2 i S-3 obowiązują.**