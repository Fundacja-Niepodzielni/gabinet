# ZLECENIE-004 — czy znaleziska z CZATU i WORDPRESSA należą do TWOICH klas?

> Pomiar kanału w pierwszej linii. Zadanie analityczne, **bez dotykania kodu**, bez wchodzenia
> do tamtych repozytoriów.

## Skąd to zadanie

Konta podejrzewały, że nasza główna klasa defektu żyje też poza czwórką — w czacie kryzysowym
i w serwisie WordPress, bo kopiują ten sam wzorzec. **Sprawdziłem to dwoma rozpoznaniami
i podejrzenie się potwierdziło.** Raporty:

  `_architektura/rozpoznanie/chat.md`
  `_architektura/rozpoznanie/wordpress.md`

Czytaj je **tylko do odczytu**. Nie wchodź do `chat/` ani `Niepodzielni-dev/`, nie uruchamiaj
niczego, nie proponuj tam napraw — te repozytoria nie mają dziś sesji ani weryfikacji.

## Pytanie, na które odpowiadasz

**Czy znaleziska opisane w tych dwóch raportach są egzemplarzami TWOICH klas z `KLASY-I-NAPRAWY.md`,
czy osobnymi rzeczami tylko podobnie brzmiącymi?**

Dla każdego znaleziska z tamtych raportów, które uznasz za swoje, podaj:
- **numer Twojej klasy** i dlaczego to ten sam MECHANIZM, nie tylko ten sam objaw
- **czy Twoja proponowana naprawa zamknęłaby TAMTEN egzemplarz** — jeśli nie, to znaczy,
  że klasa jest szersza niż myślałeś albo że to dwie klasy; napisz którą wersję wybierasz
- **czy tamten egzemplarz odsłania coś, czego u siebie nie widziałeś** — np. czat ma zarówno
  cichy brak działania, JAK I działanie za szerokie (kasuje wszystkie sesje osoby zamiast jednej);
  WordPress ma opakowanie punktów wejścia, którego trybem awarii jest „przepuść"

Jeśli uznasz, że **żadne** tamtejsze znalezisko nie jest Twoje — napisz to wprost. To też jest wynik
i jest tak samo cenny jak lista dopasowań.

## Dlaczego to nie jest ćwiczenie akademickie

Składam z waszych czterech raportów jedno zestawienie klas przekrojowych, na którym oprę podział
pracy naprawczej. **Jeśli moje grupowanie jest błędne, cztery zespoły zrobią złą pracę na moje
polecenie.** Weryfikator architekta sprawdza to teraz od strony moich dokumentów; wy sprawdzacie
je od strony materiału. Dwie różne drogi do tego samego pytania.

## Oddanie

`docs/ZLECENIA/ODPOWIEDZ-004.md` — pomiar kanału, potem lista dopasowań i jedno zdanie:
czy po tej lekturze zmieniłbyś COKOLWIEK we własnym `KLASY-I-NAPRAWY.md`. Jeśli tak — czego
dokładnie, ale **nie zmieniaj jeszcze**, tylko opisz.
