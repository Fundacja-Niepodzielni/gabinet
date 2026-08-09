# ZLECENIE-042 — DOMYKANIE DNIA. Skończ to, co masz, uporządkuj pamięć, nie zaczynaj nowego.

**Od:** architekt · **09.08.2026, koniec dnia** · **decyzja właściciela** · potwierdź zwyczajnie

---

## 1 · Co się dzieje

**Właściciel kończy dzień pracy wszystkich sesji.** Jutro zaczynamy od nowa, na czystym oknie
(`/clear`). **To nie jest przerwanie pracy — to jest jej domknięcie.**

## 2 · Kolejność, w tej kolejności

**KROK 1 · DOKOŃCZ to, co masz otwarte — ale bez rozpędzania się.**
Jeśli bieżąca pozycja domyka się w rozsądnym czasie — domknij ją normalnie (`ODPOWIEDZ`, commit,
push). **Jeśli nie — zamknij ją plikiem jako NIEZROBIONA**, z trzema rzeczami: **co zrobione ·
co zostało · gdzie dokładnie stanąłeś**. **Nie zostawiaj pracy przerwanej w połowie bez zapisu** —
to jest dokładnie ten stan, którego `S-3` zabrania.

**KROK 2 · ⚠ NIE ZACZYNAJ NOWEJ POZYCJI.** `S-2` („nigdy nie stoisz") **jest na dziś zawieszone
tym zleceniem.** Nie szukaj kolejnej rzeczy w zaległościniku.

**KROK 3 · UPORZĄDKUJ WŁASNĄ PAMIĘĆ — to jest teraz Twoja praca, nie dodatek.**

Sprawdzian, którym mierzysz, czy skończyłeś:

> **Gdyby Twoje okno zniknęło w tej sekundzie, czy jutrzejsza sesja — czytając WYŁĄCZNIE pliki —
> podjęłaby pracę bez zgadywania?**

Konkretnie:
- **`PLAN-FAZ.md` → `CURRENT WORK`** — stan faktyczny, nie wczorajszy. Co otwarte, co czeka
  na właściciela, co na inną sesję.
- **`docs/DECYZJE.md`** — decyzje z dziś, **z datą i z tym, KTO je podjął** (właściciel, architekt,
  Ty). Dziś zapadło ich dużo i część żyje wyłącznie w kanale.
- **Zaległościnik** — uporządkowany wg **iloczynu wagi i osiągalności**, nie wg kolejności wpisów.
- **Hipotezy OBALONE — z pomiarem, który je obalił.** Bez tego jutrzejsza sesja straci ten sam
  czas na tę samą ślepą uliczkę.
- **Ruchome liczby z datą** („223 zielone na 09.08") — nie jako stałe.
- **Identyfikatory samozwrotne wyrzuć** (SHA commita w pliku, który ten commit tworzy).
- **Dokument wejściowy** ma zaczynać się od kroku **„ustal stan bieżący U ŹRÓDŁA, nie z tego
  pliku i nie z pamięci"** — dopiero potem od zadania.

**KROK 4 · Napisz `ZAMKNIECIE-DNIA-2026-08-09.md`** w `docs/ZLECENIA/`: **stan na koniec dnia,
co otwarte, co czeka i na kogo, oraz jedno zdanie „od czego zacząć jutro".** To ma być plik,
który jutrzejsza sesja przeczyta jako pierwszy.

## 3 · Czego NIE robisz

**Nie refaktoryzujesz. Nie „przy okazji" nie poprawiasz.** Porządkowanie pamięci to **pisanie
o stanie**, nie zmienianie stanu. **Zero `main`, merge, deploy** — bez zmian.

## 4 · Uwaga dotycząca Ciebie konkretnie

Masz otwarte `PODJETO-032` (kontrola unieważnienia middlewarem + pomiar okna) i zatwierdzoną
po niej `BEZ_DANYCH_OSOBOWYCH`. **Obie zostają na jutro** — zapisz, w którym miejscu jesteś
w pierwszej, **łącznie z tym, co już zmierzyłeś, żeby nie mierzyć drugi raz.**

Zapisz też **wymagania z `ZLECENIE-038/039/040`** (blokada slotu, trzy ścieżki umawiania,
przepływ tożsamości) do `docs/DECYZJE.md` — **dziś istnieją głównie w kanale, a kanał nie jest
pamięcią.**

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · realmu nie dotykasz · ścieżki bezwzględne · nic poza fundację ·
**S-3 obowiązuje. S-2 zawieszone do jutra.**
