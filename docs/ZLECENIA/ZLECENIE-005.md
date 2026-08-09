# ZLECENIE-005 — jesteś właścicielem klasy P2 i D3-kod

> Pomiar kanału w pierwszej linii odpowiedzi.

## Runda 1 — PRZYRZĄD. Dlaczego teraz i tylko to

Zestawienie klas przekrojowych jest gotowe i **przeszło weryfikację po czterech poprawkach**:
`_architektura/13-klasy-przekrojowe.md`. Przeczytaj je w całości — zawiera klasę, której nie miał
NIKT z was (D4, działanie za szerokie) i dwa brakujące kształty perturbacji.

Runda 1 obejmuje **wyłącznie przyrząd**. Powód sformułowały konta i przyjmuję go bez zastrzeżeń:
dopóki nie wiemy, czy nasze kontrole w ogóle potrafią zapalić się na czerwono, **każdy zielony
wynik — łącznie z waszymi raportami — jest twierdzeniem o nieznanej wartości.** Naprawa defektów
przed naprawą przyrządu to mierzenie zepsutą miarką.

Jesteś **właścicielem projektu** swojej klasy. Właściciel projektuje, pozostali adaptują
i weryfikują u siebie. Twój projekt trafi do nich, więc ma być na tyle konkretny, żeby dało się
go wykonać bez dopytywania — i na tyle uczciwy, żeby dało się go odrzucić.

## Twoje zadanie

**P2** — kontrola sama wywołuje albo produkuje to, co miała obserwować. Wzorzec jest Twój
(producent/wykonawca/obserwator trzema ścieżkami) i to z Twojego KODU inni mają go brać.

**⛔ ZANIM cokolwiek wyślesz dalej — przeczytaj w `13-klasy-przekrojowe.md` warunek bezwzględny
przenośności perturbacji.** Mój opis Twojego wzorca był DWUKROTNIE wadliwy: raz badał awarię
głośną zamiast cichej, raz nie miał klamry bezpieczeństwa. Reguła bazodanowa zostawiona na żywej
instancji to CICHA BLOKADA KASOWANIA DANYCH OSOBOWYCH. Twój projekt musi nieść klamrę
(`trap … EXIT INT TERM` albo transakcja), skan na pozostałość PRZED startem i ODMOWĘ przy jej
znalezieniu — inaczej wyślemy helpdeskowi bombę z opóźnionym zapłonem.

**Twoja KLASA 3** („wynik zgodny z więcej niż jednym światem", 9 członków) — warunek domknięcia
P1 u kont. Zrób ją w tej rundzie; bez niej ich praca da fałszywe poczucie domkniętego przyrządu.

**D3-kod** — twierdzenia w komentarzach i docblockach bez egzekutora. Masz gotowy mechanizm
(`ObietniceKomentarzyTest`) do rozszerzenia ze znaczników na TWIERDZENIA.

## Twarde zasady tej rundy

- **Perturbacja przy każdej naprawie.** Bez niej naprawa jest deklaracją.
- **Nie zamykasz własnej pracy.** Zielona bramka od autora to informacja, nie weryfikacja.
- Zero `main`, merge, deploy · nic poza fundację · zero zapisu w cudzych repozytoriach.
- Jeśli w trakcie okaże się, że projekt nie zamyka wszystkich członków klasy — **powiedz to**,
  zamiast zawężać klasę do tego, co umiesz naprawić.

## Oddanie
`docs/ZLECENIA/ODPOWIEDZ-005.md` — pomiar kanału, projekt, perturbacje, i jedno zdanie:
czy Twój projekt przenosi się do pozostałych repozytoriów bez zmian, czy wymaga adaptacji i jakiej.
Potem czekasz.
