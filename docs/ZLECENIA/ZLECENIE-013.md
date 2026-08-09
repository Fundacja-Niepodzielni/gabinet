# ZLECENIE-013 — DECYZJE WŁAŚCICIELA, 09.08 (gabinet)

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-013`
**To nie jest nowa runda.** Rozstrzygnięcia do wpisania w `docs/DECYZJE.md` i uwzględnienia
w bieżącej pracy. Gdzie zmieniają zakres — powiedz, zamiast cicho przeprojektować.

---

## D-1 · LIMIT NISKOPŁATNYCH: **10 wizyt NA OSOBĘ, ŁĄCZNIE** — nie tygodniowo

Właściciel, dosłownie: *„co do limitu 10 — to nie limit tygodniowy, ale ogólne, że jedna osoba
może skorzystać z max 10 wizyt niskopłatnych ogólnie"*.

**Wiążące:** limit **10** jest **per pacjent, sumarycznie** — nie odnawia się co tydzień,
miesiąc ani rok.

**⚠ To rozstrzyga liczbę 10, ale NIE rozstrzyga liczby 4.** W Twoim `PLAN-FAZ.md:240` (faza F2)
stoi: *„limit **4** niskopłatnych/tydzień (ISO, reset poniedziałek)"*. Skoro 10 nie jest
tygodniowe, **czym jest 4 na tydzień?** Trzy możliwości i **nie wybieram za Ciebie**:

- limitem po stronie **specjalisty** (ile niskopłatnych przyjmuje w tygodniu) — wtedy zostaje,
  ale musi mieć **inną nazwę w konfiguracji**, żeby nikt nie pomylił go z limitem pacjenta;
- limitem **puli fundacyjnej** (ile niskopłatnych wydajemy tygodniowo łącznie);
- **pomyłką**, która weszła do planu i którą trzeba usunąć, zanim ktoś zaimplementuje obie.

**Czego oczekuję:** ustal z kodu i planu, do czego odnosi się 4, **i powiedz mi**, zamiast
zgadywać. Jeśli nie da się ustalić z materiału — powiedz i to, wtedy wraca do właściciela
jako osobne pytanie. **Nie implementuj obu limitów, dopóki nie wiadomo, że są dwa.**

**Konsekwencja dla modelu:** limit sumaryczny per osoba wymaga **trwałego licznika związanego
z pacjentem**, nie okna czasowego. To dotyka zasady twardej 4 (zamrażanie w chwili zakupu) —
licznik musi być odporny na to, że pacjent rezerwuje jako gość, a konto zakłada później.
**Jeśli to zmienia model danych, zatrzymaj się i powiedz** — nie wprowadzaj przy okazji.

## D-2 · POLITYKA SCALANIA — Twoje brzmienie zostało przyjęte jako obowiązujące wszędzie

Właściciel potwierdził **`D-2026-08-07-09`**: push zawsze · merge do `main` po zielonej
NIEZALEŻNEJ weryfikacji · deploy i wystawienie publiczne wyłącznie za zgodą.
**Twoje sformułowanie było jedynym z pełną atrybucją i wygrało jako wzorzec dla czterech repo.**
Nic u Ciebie do zmiany; odnotowuję, bo zmienia się `konta` (mieli „merge po zielonej bramce
autora") i `helpdesk` (nie mieli atrybucji).

## D-3 · OKRESY RETENCJI — nadal NIEUSTALONE, i tak zostaje

Właściciel odniósł się do retencji **helpdesku** (kolejka Granty: bezterminowo), ale
**nie do sześciu tabel Gabinetu**. Twoje `null` = NIEUSTALONY zostają, zadanie nadal
**odmawia i wypisuje dług**. **Nie wpisuj żadnej wartości domyślnej** — Twoje uzasadnienie
weszło do rejestru przekrojowego jako **`D-EKO-009`** i obowiązuje trzy systemy.

**Jedna rzecz do przygotowania, bo przyspieszy decyzję:** przy każdej z sześciu tabel dopisz
**jedno zdanie po polsku, dla nietechnicznego czytelnika: CO tam leży i CZYJE to dane.**
Właściciel ma to omówić z fundacją, a „`uniewaznione_sesje`" nic mu nie mówi. To nie jest
wymyślanie okresu — to opisanie przedmiotu decyzji.

---

**Kolejność bez zmian:** `ZLECENIE-012` (klucz jako parametr w `uniewaznione_sesje`) zostaje
pozycją bieżącą. D-1 i D-3 są **do odnotowania i przygotowania**, nie do wdrożenia teraz.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · zero zapisu w cudzych repozytoriach, ścieżki bezwzględne ·
nic poza fundację · sekretów nie zapisujesz · starego repo `System-rezerwacji` nie dotykasz.
