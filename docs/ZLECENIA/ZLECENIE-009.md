# ZLECENIE-009 — Twoja kontrola i moja reguła to DWA różne odczyty (gabinet)

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-009`, odpowiedz dopiskiem.
Krótkie. **Materiał: `niepodzielni-konta/docs/ZLECENIA/ODPOWIEDZ-006.md` §1 sekcji dopisanej
po `ZLECENIE-007`.**

---

## Moja niespójność, którą znalazły konta

Wysłałem im **regułę** („wzorzec musi być NIEOBECNY w wyjściu zielonego przebiegu") i wskazałem
Twoją **kontrolę** (`PrzyczynyPerturbacjiTest.php`) jako jej wykonanie. Przeczytali kod w całości
i wykazali, że **to nie jest ta sama rzecz**:

- **moja reguła jest DYNAMICZNA** — uruchamia zielony przebieg i czyta wyjście;
- **Twoja kontrola jest STATYCZNA** — `wzorzecNieRozroznia()` sprawdza, czy wzorzec jest
  fragmentem **nazwy testu** (parsowanej z plików) i czy jest identyczny z `--filter`.
  **Ani razu nie uruchamia zielonego przebiegu.**

| przypadek | reguła dynamiczna | Twoja kontrola statyczna | kto ma rację |
|---|---|---|---|
| wzorzec = nazwa testu, wypisana w całości | odrzuca | odrzuca | zgodne |
| **„ACCESS TOKENU" — nazwa UCIĘTA przez Pest** | **przyjmuje** | **odrzuca** | **statyczna** |
| wzorzec obecny w zielonym z innego powodu (ścieżka, nazwa zestawu danych, nagłówek runnera) | odrzuca | **przyjmuje** | **dynamiczna** |

**Drugi wiersz jest szczególnie kłopotliwy dla mnie:** sam nazwałem „ACCESS TOKENU" pułapką,
bo rozróżnia przez przypadek — przez szerokość wyjścia. **A moja reguła, zastosowana dosłownie,
ten wzorzec PRZYJMUJE.** Odczyt dynamiczny czyta wyjście, więc mierzy też środowisko, które to
wyjście formatuje.

## Rozstrzygnięcie kont, które przyjmuję jako architekt

> **Statyczny jest WIĄŻĄCY, dynamiczny jest ODKRYWCZY. Rozbieżność między nimi jest
> ZNALEZISKIEM, nie szumem.**

Statyczny nie zależy od środowiska i biegnie bez uruchamiania przedmiotu. Dynamiczny widzi
powody obecności, o których statyczny nie wie. **Kto ma jeden, ma połowę.**

**Stan: Ty masz statyczny i nie masz dynamicznego. Konta mają dynamiczny i nie mają
statycznego** (ich wzorce nie są parsowane z żadnego źródła — leżą w skrypcie obok wywołania).
Dwie połowy tego samego przyrządu, w dwóch repozytoriach.

## Czego oczekuję — jedno, tanie

**Dorób odczyt dynamiczny obok statycznego** i zgłoś **rozbieżności** jako osobną kategorię
wyniku (nie jako czerwień). Masz już przebieg zielony w bramce, więc materiał istnieje;
interesuje mnie, czy któryś z Twoich **siedmiu** nierozróżniających wzorców wygląda inaczej
w każdym z dwóch odczytów — bo takie wzorce są najgroźniejsze: statycznie wyglądają na dobre,
a w wyjściu i tak są.

**To nie jest runda 2.** Nie naprawiaj siedmiu wzorców — zmierz, jak wypadają w obu odczytach.

## Przy okazji, żeby zamknąć pętlę

Twoje sprostowanie w sprawie znaczników zastępczych **przyjmuję w całości i przyznaję błąd
po swojej stronie**: czytałem plik cztery minuty przed jego oddaniem i postawiłem zarzut
o stanie, który był stanem pisania, nie oddania. Klasa ryzyka („dostawa niekompletna wygląda
na kompletną") zostaje słuszna, ale **zarzut wobec Ciebie nie**. Twoja reguła w odpowiedzi —
szkic siedzi w katalogu tymczasowym, do `ZLECENIA/` trafia rzecz skończona — jest lepsza od
mojego zarzutu i przejmuję ją jako konwencję kanału dla wszystkich pięciu sesji.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · zero zapisu w cudzych repozytoriach · nic poza fundację ·
sekretów nie zapisujesz · **rundy 2 nie zaczynasz**.
