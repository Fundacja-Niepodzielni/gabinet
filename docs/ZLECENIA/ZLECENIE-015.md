# ZLECENIE-015 — WERYFIKACJA KRZYŻOWA RUNDY 2: naprawa bezpieczeństwa kont

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-015`, odpowiedz `ODPOWIEDZ-015.md`

**Materiał:** `niepodzielni-konta/docs/ZLECENIA/ODPOWIEDZ-009.md` §4 (P-1…P-4) oraz kod
`niepodzielni-konta/tests/ref-laravel/app/src/InvalidationStore.php` i `SessionStore.php`.
**Odczyt dozwolony, zapis zabroniony, ścieżki bezwzględne, nigdy `cd`.**

---

## Dlaczego Ty i dlaczego to

**Runda 2 zaczyna wracać i nikt nie zamyka własnej pracy — także tutaj.** Dostajesz naprawę,
która jest **jedyną w tej rundzie dotykającą wprost bezpieczeństwa dostępu**: sesja osoby
wylogowanej wracała do życia po przeskoku zegara. Konta zmierzyły, że defekt był **podwójny**,
i naprawiły obie strony. **Nikt tego nie sprawdził.**

Masz do tego najlepszy przyrząd w ekosystemie: zmierzone martwe mutacje, dowód mutacji czytany
**z wnętrza kontenera**, blok `BOMBA` rozdzielający „zdrowe" od „niepokryte" oraz dwa odczyty
wzorca przyczyny. **Użyj ich na cudzym kodzie.**

## Co dokładnie masz podważyć

**(A) Czy nowe kontrole UMIEJĄ zaczerwienić — i czy ich zieleń nie jest pusta.**
To jest pytanie numer jeden, bo naprawa dotyczy zabezpieczenia. Konkretnie:
1. **Blok `BOMBA` na ścieżce `isInvalidated`** — wstaw celową awarię w miejsce rozstrzygnięcia
   o dostępie i policz, ile testów pada. **Jeśli zero — ścieżka nie jest pokryta i zieleń
   naprawy nic nie znaczy**, choćby kod wyglądał poprawnie. To jest dokładnie ten pomiar,
   którym rozstrzygnąłeś dwuznaczne zero hubu.
2. **Kierunek 0** — znacznik o treści pustej albo uszkodzonej. Konta twierdzą, że **nadal
   blokuje**. Sprawdź, czy kontrola bada **wartość**, czy samą obecność pliku.
3. **Kierunek 3 (naprawa)** — czy istnieje ZIELONY test, który po tej naprawie powinien był
   paść, a nie padł. Jeśli tak, pinował zdegradowane zachowanie.

**(B) Czy druga strona defektu jest naprawdę zamknięta.**
Konta twierdzą: `SessionStore` **nie sprawdzał wieku rekordu w ogóle**, więc ten sam skok
zegara otwierał dostęp **dwukrotnie**. Twierdzenie oparte na `grep 'time()\|created\|expire'`
→ zero trafień w ścieżce odczytu. **To jest odczyt, nie uruchomienie** — i Ty wiesz lepiej niż
ktokolwiek, że `grep` bez otwartego kontekstu produkował już u nas zdania mocniejsze niż materiał.
**Otwórz kontekst i powiedz, czy wniosek się broni.**

**(C) Czy `evictExpired` zwraca rozstrzygnięcie, którego NIE DA SIĘ zignorować.**
Konta deklarują dwie osie (`zasieg` × `zakonczenie`) zgodne z erratą oraz że **wynik `unlink`
jest odbierany, a skutek sprawdzany odczytem** (`@unlink($p) && !is_file($p)`). Zaatakuj to:
znajdź wywołanie, w którym rozstrzygnięcie da się porzucić bez konsekwencji. Jeśli nie
znajdziesz — powiedz i to, jako nieudaną próbę obalenia.

**(D) `D-EKO-012` — reguła, która z tego wyszła.** *„TTL nie jest prawem wstępu; o dostępie
rozstrzyga OBECNOŚĆ znacznika, czas życia jest progiem sprzątania."* **Czy Ty masz u siebie
miejsce, w którym wygaśnięcie czegoś jest traktowane jako pozwolenie?** Zamrożone reguły
anulacji, okno linku płatności, dziesięciominutowa blokada slotu — **wszystkie trzy to progi
czasowe przy podejmowaniu decyzji**. Zmierz, nie zgaduj. **To jest część (B) weryfikacji:
adaptacja u siebie.**

## Czego NIE robisz

Nie wchodzisz do repozytorium kont zapisem · nie naprawiasz ich kodu, nawet gdy zobaczysz jak ·
nie uruchamiasz ich stosu, jeśli wymagałoby to postawienia czegoś na współdzielonym demonie
(S-1 pkt 2 i 3) — a jeśli uruchamiasz cokolwiek, **szeregowo i z odczytem stanu po**.

## Forma

Pomiar kanału · tabela subagentów · (A)(B)(C)(D) z surowym wyjściem · werdykt z pięciu
(`POTWIERDZONE` · `OBALONE` · `ZŁA WAGA` · `ZŁA DIAGNOZA` · `ZALECENIE SZKODLIWE`) ·
**czego nie sprawdziłeś** · **własne nieudane próby obalenia**.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · zero zapisu w cudzych repozytoriach · nic poza fundację ·
sekretów nie zapisujesz. **S-1: do 10 subagentów, przy Dockerze i bazie szeregowo.**
