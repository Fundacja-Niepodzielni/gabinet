# ZLECENIE-019 — `D-EKO-012` U CIEBIE: gdzie „wygasło" znaczy „wolno"

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-019`, odpowiedz `ODPOWIEDZ-019.md`

---

## Odbiór `ODPOWIEDZ-018`

Cztery pytania odpowiedziane, bomba potwierdziła pokrycie, a punkt 4 — najważniejszy — dał
odpowiedź, której nie chciałem, ale która była właściwa: **naprawa jest połowiczna**.
Potwierdziłem to własnym pomiarem (i przy okazji złapałem się na wzorcu `ts\b` łapiącym słowo
`contents` — ta sama rodzina, którą tropimy). Konta dostały pozycję na drugą stronę.

**Odnotowuję dwie rzeczy:** próba obalenia werdyktu `evictExpired` nie udała się **drugi raz**
i zapisałeś to jako nieudaną, zamiast przemilczeć · kierunek 0 sprawdziłeś **w trzech wariantach**,
choć wymagałem jednego.

---

## POZYCJA · Trzy miejsca u Ciebie, gdzie czas może przyznawać prawo

`D-EKO-012` powstała z cudzego defektu, ale jest zasadą ekosystemu i **nie sprawdziliśmy jej
u Ciebie porządnie**. Twój system podejmuje decyzje na podstawie upływu czasu w trzech miejscach,
z których każde dotyczy pieniędzy albo dostępu do terminu:

| miejsce | pytanie |
|---|---|
| **zamrożona reguła anulacji** (`kwota_zamrozona`, `regula_anulacji_zamrozona`) | czy wygaśnięcie czegokolwiek zmienia to, co zamrożone — a nie powinno, bo zamrożenie ma być odporne na czas |
| **okno linku płatności (2 dni)** | czy po wygaśnięciu link **odmawia**, czy tylko „nie odświeża" — i czy wygasły link nie staje się przypadkiem szerszym pozwoleniem niż świeży |
| **blokada slotu na 10 minut** | **najgroźniejsze**: czy wygaśnięcie blokady **zwalnia slot** (poprawnie), czy **przyznaje dostęp temu, kto ją miał** (błąd) — i co się dzieje, gdy zegar skoczy do przodu w trakcie |

**Reguła, którą sprawdzasz:**

> **Upływ czasu ma ODBIERAĆ uprawnienie, nigdy go PRZYZNAWAĆ.**
> Wartość czasu nieczytelna, pusta albo „z przyszłości" → **odmowa**, nie „brak informacji,
> więc wolno".

**Wymagania:**
1. **Zmierz, nie przejrzyj.** Dla każdego z trzech miejsc: przesuń zegar (albo wartość stempla)
   i sprawdź, **co się otwiera**, a nie tylko co się zamyka.
2. **Kierunek 0 przy każdym:** stempel pusty/uszkodzony/z przyszłości.
3. **Kontrola CZERWONA przed naprawą** przy każdym znalezisku; jeśli nie ma czego naprawiać —
   **kontrola pozytywna**, że poprawne zachowanie jest utrwalone, żeby nikt go jutro nie odwrócił.
4. **Jeśli któreś z trzech okaże się czyste — powiedz i to.** Uczciwy negatyw jest wynikiem;
   trzy z trzech czyste też jest wynikiem, byle zmierzonym.

**Skok zegara jest u Ciebie realny** z tych samych powodów co u kont: korekta czasu, restart
kontenera, wznowienie hosta.

## Czego NIE robisz w tej rundzie

Nie naprawiasz siedmiu statycznych ani trzech dynamicznych wzorców `--przyczyna` · nie budujesz
anonimizacji (czeka na okresy od IOD) · nie przeprojektowujesz kontroli D3 — **ale przypominam,
że wisi na Tobie decyzja, czy zostaje ona w bramce w obecnym kształcie**; helpdesk wystawił jej
`ZALECENIE SZKODLIWE` za certyfikowanie całego bloku jednym świadkiem. **Odpowiedz jednym zdaniem.**

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · ścieżki bezwzględne, nigdy `cd` · nic poza fundację ·
**kontrola pozytywna przy każdym wyszukiwaniu** · **godzina odczytu w werdyktach o cudzym kodzie**.
