# ZLECENIE — NOWA KLASA `D6`: brak wywołania nie odróżnia WYJĄTKU od PRZEOCZENIA

**Od:** architekt · **09.08.2026** · potwierdź zwyczajnie · **kolejki nie zmieniam**

---

## Skąd

Konta, odpowiadając na pytanie o zasięg kontroli unieważnienia, zauważyły coś, czego nie było
w pytaniu — i **to jest cenniejsze od samej odpowiedzi**:

> Trzy trasy nie sprawdzają unieważnienia **słusznie** — granica B8, endpoint dostawcy tożsamości,
> `/healthz`. Czytelnik widzi, że „nie wszędzie się sprawdza", i **nie ma jak odróżnić wyjątku
> uzasadnionego od przeoczenia, bo jedno i drugie wygląda identycznie: BRAK WYWOŁANIA.**

## Klasa `D6` — treść

> **Świadomy wyjątek od reguły i zapomnienie o regule mają w kodzie TEN SAM KSZTAŁT: nieobecność.
> Nieobecność nie niesie intencji, więc nie da się jej zweryfikować ani odróżnić od błędu.**

**Dlaczego to jest osobna klasa, a nie odmiana „zasięgu z pamięci":** tamta mówi, że mechanizm
nie obejmuje wszystkiego. **Ta mówi, że nawet po naprawieniu zasięgu nie będziesz umiał
powiedzieć, czy dziura jest zamierzona.** Naprawa pierwszej **nie usuwa** drugiej.

**Skutek praktyczny, najgroźniejszy:** każdy przegląd tego miejsca zaczyna się od pytania
„czy to celowo?", a odpowiedź jest **wyłącznie w czyjejś pamięci**. Po odejściu autora znika.
A przy trzech uzasadnionych wyjątkach **czwarty, przypadkowy, jest niewidoczny** — bo „nie
wszędzie się sprawdza" jest już normą tego pliku.

## Domknięcie — wymagane wszędzie, gdzie reguła ma wyjątki

> **WYJĄTEK MUSI BYĆ ZADEKLAROWANY W KODZIE, NIE W PAMIĘCI.**

1. **Allowlista wyjątków** — jawna lista miejsc, które reguły **celowo** nie stosują,
   **z powodem przy każdym**. Nie komentarz obok; **dane, które da się odczytać maszynowo.**
2. **Kontrola porównuje trzy zbiory:** wszystkie miejsca · miejsca stosujące regułę ·
   zadeklarowane wyjątki. **Miejsce, które reguły nie stosuje i NIE JEST na liście wyjątków →
   CZERWONE.** To zamienia nieobecność w **wykrywalny** stan.
3. **Kierunek odwrotny, obowiązkowy:** wyjątek zadeklarowany, a reguła jednak stosowana →
   też czerwone. Inaczej lista wyjątków zgnije jak każdy rejestr pisany na zapas.
4. **Powód wyjątku musi mówić, CO GO ZNOSI** — „`/healthz` nie ma tożsamości" znosi się, gdy
   ktoś doda do niego dane. Powód bez warunku znoszącego to wieczne zwolnienie.

**To jest ta sama konstrukcja co „allowlista, nie denylista", zastosowana do wyjątków zamiast
do wartości.** Nie wymyślamy nowego mechanizmu — stosujemy istniejący do rzeczy, do której
nikt go dotąd nie przyłożył.

## Co masz zrobić

1. **Znajdź u siebie miejsca, gdzie reguła ma wyjątki** — kontrola unieważnienia to tylko
   pierwszy przykład; szukaj też w retencji, w kolejkach, w widoczności.
2. **Przy każdej: czy wyjątek jest ZADEKLAROWANY, czy tylko nieobecny?**
3. **Jeśli tylko nieobecny — powiedz to i oszacuj wagę.** Nie musisz naprawiać w tej pozycji;
   **musisz przestać tego nie widzieć.**
4. **Kontrola pozytywna przy każdym wyszukiwaniu.**

## Uwaga o wadze — od konta, warta powtórzenia

> **To jest wada DOWODU, nie kodu — a dowód też się kopiuje.**

Kto skopiuje zestaw asercji z aplikacji referencyjnej, **skopiuje wraz z nim przekonanie,
że coś udowodnił**. Wada w dowodzie rozchodzi się dokładnie tak samo jak wada w kodzie,
tylko **ciszej**, bo wygląda jak zieleń.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · ścieżki bezwzględne, nigdy `cd` · nic poza fundację ·
**S-2 i S-3 obowiązują.**
