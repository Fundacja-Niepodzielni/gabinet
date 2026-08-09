# ODPOWIEDŹ-026 — `R6B-11`: sonda dopasowana do protokołu. **ZROBIONE**

Commit `1b88d7a`. Kanał: plik **18:35:57.752** → obudzony **18:36:41.793** = **44,0 s**.

> **⚠ ZŁAMAŁEM WŁASNĄ REGUŁĘ Z `ZLECENIE-023` — po raz drugi.** Pozycja była zrobiona
> i zacommitowana, a odpowiedź poszła **oknem sesji, nie kanałem**. Zauważyłem to sam,
> sprawdzając przed `ZLECENIE-027`, czy pliki `ODPOWIEDZ-024/025/026` istnieją — nie istniały
> dwa. Reguła, którą przyjąłem sześć zleceń temu („odpowiedź do kanału ZANIM meldunek
> w oknie"), nie zadziałała, bo **oparłem ją na pamiętaniu, a nie na kontroli**. To jest
> dokładnie ta klasa, którą tropię cały dzień, popełniona na własnym procesie.

## Pomiar rozstrzygający — w całości na loopbacku, nic nie wystawione

```
Postgres NASŁUCHUJĄCY na 127.0.0.1:55442
  sonda HTTP (curl)     → NIE WYKRYŁA
  próba połączenia TCP  → WYKRYŁA
  kontrola pozytywna: TCP na martwym porcie 59999 → nie połączyła
```

**Kontrola „nic nie wystawione publicznie" była zielona także przy WYSTAWIONEJ BAZIE DANYCH** —
bo baza nie mówi po HTTP, więc pytanie HTTP-em odpowiadało na inne pytanie, niż zadawaliśmy.

## Druga wada w tym samym bloku — nie było jej w zleceniu

Przy braku adresu spoza loopbacku kontrola wypisywała **„pominięte"** i szła dalej: **brak
pomiaru wyglądał identycznie jak pomiar udany.** Teraz `NIEROZSTRZYGNIĘTE` + czerwone,
z podpowiedzią `GABINET_ADRES_LAN=<adres>` — strażnik, który **da się otworzyć zamiast
wyciszyć** (wymaganie 5).

## Trzy światy zamiast dwóch — każdy zmierzony osobno

```
OSIAGALNY  Postgres na 127.0.0.1:55442        → wystawione, czerwone
ODMOWA     martwy port 127.0.0.1:59999        → nie słucha, w porządku
NIEZNANY   10.255.255.1:5432, cisza do timeoutu → traktowane JAK WYSTAWIONE
```

Nieznane traktuję jak wystawione, bo inaczej **zapora udaje bezpieczeństwo**.

## Gałąź bazowa — dołożona do ISTNIEJĄCEGO odczytu

Zgodnie z własną decyzją o `werdykt()`: rozszerzyłem `skrypty/odczyt-przyczyn.py`, zamiast
budować pomocnika ponad granicą runtime'u. Zmierzone: **0 poleceń jest już czerwonych** na
kodzie niezmutowanym, więc czerwień po mutacji da się przypisać mutacji.

**Granica, nie przemilczana: pokrycie 13 z 29** wywołań `oczekuj_czerwone` (parser klucza się
na `--przyczyna`), **16 nieobjętych**. To **nie jest** zamknięcie `R6B-13` dla całego zestawu.

## Kontrola i jej perturbacja

`SondaPortowTest` — trzy kontrole. Perturbacja (powrót do `curl`) zapala pierwszą z właściwym
komunikatem. Kierunek odwrotny na materiale zbudowanym pod rękę. **Filtruje komentarze**, bo
nagłówek sondy **cytuje starą wersję** — bez filtra kontrola widziałaby cytat.

## Stan

```
219 zielonych · 2 pominięte · 1 CZERWONY (noga 1) · 1906 asercji · podłogi 219/1901 · pint PASS
```
