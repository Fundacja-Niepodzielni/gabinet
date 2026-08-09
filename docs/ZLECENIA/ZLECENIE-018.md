# ZLECENIE-018 — weryfikacja naprawy kont, TYM RAZEM NA ISTNIEJĄCYM KODZIE

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-018`, odpowiedz `ODPOWIEDZ-018.md`

---

## Odbiór `ODPOWIEDZ-017` — przyjmuję, i mówię, co uważam za najlepsze

**Nie naprawę filtra.** Najlepsze jest to, że **pierwsza wersja Twojej kontroli dała czerwień
z `Call to undefined method`** — czyli z awarii przyrządu, nie z badanej wady — **a Ty to
zauważyłeś i przepisałeś ją tak, żeby pytała o WYJŚCIE RAPORTU**, które istnieje i przed,
i po naprawie.

To jest ta sama dyscyplina, którą stosowałeś dziś wobec trzech cudzych systemów, **zastosowana
do siebie w ciągu tej samej godziny**. Para „czerwone przed / zielone po" jest warta dokładnie
tyle, ile pewność, że czerwień pochodzi z badanego zjawiska.

Doceniam też, że diagnozę postawiłeś bez łagodzenia: **dwie niezależne listy z filtrami
zaczynającymi się tak samo, i nazwa `czekajaceNaOkres`, która brzmiała jak „wszystko, co czeka",
a znaczyła „wszystko, co czeka spośród kasowanych"**. Nazwa szersza niż zakres to osobny,
powtarzalny kształt — zapisuję go sobie.

---

## POZYCJA · Naprawa kont ISTNIEJE od 15:13:40 — zweryfikuj ją

**Zmieniło się to, co unieważniło Twój poprzedni raport.** Zmierzone przeze mnie o **15:20:30**
(godzina odczytu, zgodnie z Twoją własną nową regułą), z kontrolą pozytywną:

```
mtime InvalidationStore.php  → 15:13:40
isInvalidated / XYZZY        → 3 / 0          ← przyrząd działa
function evictExpired        → linia 177
unlink                       → tylko 205, w ścieżce mutującej, z odczytem zwrotnym
                               (171: komentarz „poprzednia wersja miała @unlink($p);")
```

**Masz gotowe stanowisko** — kopia ich kodu w katalogu ignorowanym przez gita, uruchamiana
na Twoim stojącym kontenerze. **Przed pomiarem odśwież kopię** i **zapisz godzinę odczytu**;
przy pięciu sesjach naraz przedmiot zmienia się pod pomiarem, co sam zmierzyłeś.

**Cztery pytania, w kolejności wagi:**

1. **Czy `isInvalidated` jest naprawdę czystym odczytem** — zero mutacji, zero porównań czasu,
   rozstrzyganie na **obecności**. To jest `D-EKO-012` w kodzie.
2. **Czy znacznik PUSTY albo USZKODZONY nadal blokuje** (kierunek 0). Konta tak twierdzą.
   Przy zabezpieczeniu niepewność ma jedną dopuszczalną odpowiedź.
3. **Czy `evictExpired` zwraca rozstrzygnięcie, którego NIE DA SIĘ zignorować** — dwie osie,
   wynik `unlink` odbierany i sprawdzany odczytem. Poszukaj wywołania, w którym da się je
   porzucić bez konsekwencji.
4. **⛔ DRUGA STRONA DEFEKTU — `SessionStore`.** W Twoim raporcie z 14:52 był **nietknięty**.
   Sprawdź ponownie i **powiedz godzinę**. Jeśli nadal nie sprawdza wieku rekordu, to **naprawa
   jest połowiczna**, a defekt był podwójny — czyli skok zegara nadal otwiera dostęp jedną drogą
   z dwóch. **To jest najważniejsze pytanie tej pozycji.**

**Blok `BOMBA` na ścieżce rozstrzygania o dostępie** — jeśli nie zabija testów, kontrola nie
ma pokrycia i jej zieleń nic nie znaczy, choćby kod był poprawny.

## Czego NIE robisz

Nie wchodzisz zapisem do kont · nie naprawiasz ich kodu · nie budujesz anonimizacji (czeka
na okresy od IOD) · **nie zamykasz własnej pracy**.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · ścieżki bezwzględne, nigdy `cd` · nic poza fundację ·
**każde wyszukiwanie zasilające werdykt niesie kontrolę pozytywną** · **werdykt o cudzym kodzie
niesie godzinę odczytu**.
