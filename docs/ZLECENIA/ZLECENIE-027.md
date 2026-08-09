# ZLECENIE-027 — `R6B-7`: sześć `sed -i` bez odczytu zwrotnego

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-027`, odpowiedz `ODPOWIEDZ-027.md`

---

## Odbiór `R6B-11` — trzy rzeczy, i druga jest ważniejsza od zleconej

**1. Pomiar rozstrzygający, w całości na loopbacku.** Sonda HTTP **nie wykryła** Postgresa
na `127.0.0.1:55442`, próba TCP **wykryła**, kontrola pozytywna na martwym porcie nie połączyła.
**Kontrola „nic nie wystawione publicznie" była zielona przy wystawionej bazie danych.**

**2. Znalazłeś drugą wadę w tym samym bloku, której nie było w zleceniu — i jest tej samej klasy.**
Przy braku adresu spoza loopbacku kontrola wypisywała „pominięte" i szła dalej, więc **brak
pomiaru wyglądał identycznie jak pomiar udany**. Teraz `NIEROZSTRZYGNIĘTE` i czerwone,
**z podpowiedzią, jak odblokować** — czyli strażnik, który da się otworzyć zamiast wyciszyć.
**To jest dokładnie wymaganie 5 wykonane w duchu, nie w literze.**

**3. Trzy światy zamiast dwóch, każdy zmierzony osobno:** `OSIAGALNY`, `ODMOWA`, `NIEZNANY` —
i **nieznane traktowane jak wystawione**, bo inaczej zapora udaje bezpieczeństwo.

**Podałeś też granicę zamiast ją przemilczeć:** gałąź bazowa pokrywa **13 z 29** wywołań,
bo parser klucza się na `--przyczyna`; **16 zostaje nieobjętych i to nie jest zamknięcie
`R6B-13` dla całego zestawu.** Odnotowuję jako dług nazwany, nie jako wadę.

---

## POZYCJA · `R6B-7` — `sed` bez trafienia kończy się SUKCESEM

**Kolejność Twoja, przyjmuję ją.** Sześć wywołań `sed -i` bez odczytu zwrotnego.

**Mechanizm:** `sed -i` na wzorcu, którego w pliku nie ma, **kończy się kodem 0 i nie zmienia
nic**. Czyli „podmiana wykonana" i „podmiana nie trafiła" **dają identyczny sygnał** — to ta sama
klasa co Twoja dzisiejsza sonda i co `unlink` u kont.

**Waga:** te wywołania służą do **przygotowania stanu przed pomiarem**. Nietrafiona podmiana
znaczy, że **perturbacja nigdy nie weszła, a przebieg i tak zamelduje wynik** — czyli cała
para czerwień/zieleń staje się zdaniem o nieznanej wartości.

**Wymagania:**
1. **Kontrola CZERWONA przed naprawą:** `sed -i` na wzorcu nieobecnym **musi** zapalić.
   Dziś przechodzi.
2. **Odczyt zwrotny, nie kod wyjścia** — sprawdzasz **treść pliku po**, nie to, czy polecenie
   się wykonało. Twoje własne zdanie z dzisiaj: *„polecenie się wykonało" nie znaczy „plik ma
   treść"*.
3. **Kierunek 0:** wzorzec trafiający **więcej niż raz** — czy podmiana zmieniła to, co miała,
   czy przy okazji coś jeszcze. **`sed` bez kotwicy jest tu drugą połową tej samej wady.**
4. **Filtruj komentarze** przy sprawdzaniu, czy naprawa weszła — Twoja lekcja, dziś
   zastosowana przy sondzie, obowiązuje tu tak samo.
5. **Jeśli któreś z sześciu okaże się bezpieczne** (wzorzec gwarantowany konstrukcją) —
   powiedz i nie naprawiaj na siłę. **Uczciwy negatyw jest wynikiem.**

## Kolejność dalej — Twoja: `R6B-2` / `R6A-1`. Nie przestawiam.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · ścieżki bezwzględne, nigdy `cd` · nic poza fundację ·
**nasłuch testowy wyłącznie na interfejsie lokalnym**.
