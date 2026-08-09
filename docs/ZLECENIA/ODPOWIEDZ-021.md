# ODPOWIEDŹ-021 — kontrakt MILCZY. Naprawa `RejestrSesji`: **NIEZROBIONA**

**Stan: CZĘŚCIOWO.** Pytanie o kontrakt — **odpowiedziane**. Naprawa kodu — **NIEZROBIONA**.
Kanał: plik **16:07:06.260** → obudzony **16:07:35.567** = **29,3 s**.

> **Uwaga procesowa:** odpowiedź na pytanie o kontrakt poszła **oknem sesji**, nie kanałem.
> Z katalogu pozycja wyglądała na nietkniętą. Moja wada procesu — `ODPOWIEDZ-023`.

---

## Odpowiedź na Twoje pytanie — jedno zdanie, o które prosiłeś

**Godzina odczytu cudzego kodu: 16:09:43.** Plik `niepodzielni-konta/docs/INTEGRACJA-KONTRAKT.md`,
874 linie. Kontrola pozytywna przyrządu: `sid` → **12** trafień, `XYZZY` → **0**.

```
wygasa_at   → 0 trafień
86400       → 0 trafień
"czas życia znacznika" / "Session Max" / "TTL"  → 0 trafień
```

> **Kontrakt nie opisuje `wygasa_at` ANI jako progu sprzątania, ANI jako terminu ważności
> unieważnienia — MILCZY o czasie życia znacznika w całości.**

Jedyne miejsce ocierające się o temat to w. 223-225, i mówi o czymś innym: że znacznik
**nie zakrywa** ścieżek, w których tożsamość znika **bez** znacznika (sprzątanie magazynu,
eksmisja, ręczne usunięcie).

**Wniosek: obie implementacje były zgodne z dokumentem, bo dokument nie mówił nic.**
To wada kontraktu **przez przemilczenie** — cisza dopuściła dwa rozbieżne odczytania i żadna
strona nie mogła się na niej złapać. Odnotowuję, że konta wkleiły dziś klauzulę jako **§4.5a**.

## Pomiar, który przyda się przy naprawie

```
session.lifetime            → 120 min = 7200 s
okno unieważnienia          → 86400 s  (CZAS_ZYCIA_SEKUND; konta.sso_session_max_s = NULL)
margines                    → 12×
```

**U mnie sprzątanie po progu jest bezpieczne** — sesja nie może przeżyć znacznika. To jest
dokładnie ta własność, której u kont **brakuje** (`SessionStore` nie sprawdza wieku, sesja nie
wygasa nigdy — `ODPOWIEDZ-018`). Przy naprawie zamierzam **zapisać ją jako kontrolę**, a nie
jako założenie: próg sprzątania musi być ≥ maksymalnego czasu życia sesji.

---

## ⛔ NAPRAWA: NIEZROBIONA. Powód, nie wymówka

Kolejność zmieniłeś sam w `ZLECENIE-022` („ta pozycja **przed** `ZLECENIE-021`"), bo dotyczyła
zabezpieczenia, pod którym pracuję. Wykonałem 022 i **nie wróciłem do 021** — a powinienem był
przynajmniej zapisać w kanale, że pozycja stoi.

**Stan faktyczny defektu — bez zmian, zmierzony i czerwony w suicie:**

`RejestrSesji::uniewazniona()` rozstrzyga o dostępie przez
`->where('wygasa_at', '>', now())->exists()`, więc znacznik **wciąż obecny w bazie** przestaje
blokować po progu. Kontrola `WygasnieciePozwolenieTest` jest **czerwona, nazwana i zamierzona**;
oba kierunki kontrolne zielone (świeży znacznik blokuje, brak znacznika nie blokuje), więc
czerwień dotyczy **wygaśnięcia**, a nie zepsutego mechanizmu.

**Plan naprawy, na który mam Twoją zgodę — do wykonania jako następna pozycja:**

1. rozstrzyganie na **OBECNOŚCI** wiersza, wiek znika z zapytania decydującego;
2. wiek zostaje **wyłącznie progiem sprzątania** na ścieżce mutującej, z wynikiem kasowania
   **odbieranym i sprawdzanym odczytem**;
3. **kierunek 0**: stempel pusty, nieczytelny albo **z przyszłości** → **blokuje**;
4. **kontrola progu**: próg sprzątania ≥ maksymalny czas życia sesji — żeby wiek nie stał się
   „prawem wstępu na odwrót", w co konta wpadły po drugiej stronie;
5. czerwień ma zniknąć **z tego samego powodu**, nie przez obejście ścieżki.

---

## ⬆ AKTUALIZACJA — naprawa WYKONANA

Pozycja domknięta w `ZLECENIE-024`. **Stan tego pliku w części „naprawa" jest nieaktualny;
wynik i pomiary są w `ODPOWIEDZ-024.md`.** Zostawiam pierwotną treść bez podmiany, bo ktoś
mógł ją przeczytać, a cicha korekta do niego nie dotrze.
