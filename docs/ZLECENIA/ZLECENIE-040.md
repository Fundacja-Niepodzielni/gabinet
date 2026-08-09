# ZLECENIE-040 — przepływ rezerwacji: właściciel go zaprojektował, ja dokładam cztery rzeczy. NADAL NIE BUDUJESZ.

**Od:** architekt · **09.08.2026, noc** · potwierdź zwyczajnie · **kolejki NIE zmieniam** ·
**zapis wymagań do `docs/DECYZJE.md`**

---

## 1 · Rozstrzygnięcie, które porządkuje wszystko inne

Właściciel opisał dwa przepływy: „pierwszy raz podaję dane, dostaję kod, mam konto" oraz
„kolejny raz podaję numer, dostaję kod, reszta danych uzupełnia się sama".

> **⚠ TO NIE SĄ DWA MECHANIZMY. Drugi przepływ TO JEST LOGOWANIE — bezhasłowe, kodem
> jednorazowym. Nie ma osobnej „weryfikacji przy rezerwacji" i osobnego „logowania".
> Jest JEDNA rzecz, oglądana z dwóch stron.**

**Konsekwencja praktyczna i architektoniczna:** wszystko, co dotyczy kodu — wysyłka, sprawdzenie,
wygaszanie, limit prób — **należy do warstwy tożsamości, nie do rezerwacji.** Gabinet **nie
implementuje własnego sprawdzania kodów.**

## 2 · ⚠ GDZIE TEN KROK ŻYJE — granica, której nie wolno przekroczyć

Rezerwacja zaczyna się na `niepodzielni.com`, czyli **w WordPressie** — a właściciel zdecydował
wczoraj, że **WordPress zostaje przy własnym logowaniu, bez SSO** (wyjątek zadeklarowany przy
`D-EKO-001`, z warunkiem znoszącym).

> **Gdyby krok „numer + kod" został zaimplementowany w WordPressie, WordPress stałby się
> DRUGIM MIEJSCEM, GDZIE ŻYJE DOWÓD TOŻSAMOŚCI PACJENTA.** To jest złamanie `D-EKO-001`
> tylnymi drzwiami — nie przez hasła, tylko przez kody.

**Wymóg: krok tożsamości obsługuje Konta Niepodzielni** (albo Gabinet **wyłącznie jako klient
SSO**, bez własnego magazynu kodów). **Strona pokazuje kalendarz i przekazuje dalej.**
Kalendarz, wyszukiwarka i dobieranie specjalisty zostają tam, gdzie są — **to tożsamość ma się
nie rozmnożyć, nie interfejs.**

## 3 · Cztery rzeczy, których w opisie właściciela nie było — dokładam je jako WYMAGANIA

### 3.1 · ⚠ ODPOWIEDŹ MUSI BYĆ IDENTYCZNA, ZANIM KOD ZOSTANIE POTWIERDZONY

Jeśli po wpisaniu numeru system pokaże cokolwiek, co różni się dla numeru **znanego**
i **nieznanego** — komunikat, czas odpowiedzi, wypełnione pola — to **wystarczy wpisywać cudze
numery, żeby dowiedzieć się, kto korzysta z pomocy psychologicznej.**

> **Po podaniu numeru: ZAWSZE ten sam komunikat („wysłaliśmy kod"). Dane pojawiają się
> WYŁĄCZNIE po potwierdzeniu kodu.**

To jest ta sama zasada, którą postawiłem przy kojarzeniu historii — **najpierw dowód,
potem informacja** — tylko przeniesiona o krok wcześniej, bo formularz jest publiczny.

### 3.2 · OGRANICZENIE TEMPA NA WYSYŁKĘ KODU — to jest wydatek, nie tylko bezpieczeństwo

Krok „podaj numer" **wysyła SMS-a każdemu, kto go kliknie**. Bez ograniczenia tempa jest to
**publiczny przycisk „wydaj pieniądze fundacji"**. Ograniczenie na numer, na adres sieciowy
i globalne. **Do tego lista krajów w SMSAPI zawężona do potrzebnych** — wysyłka
międzynarodowa jest wielokrotnie droższa.

### 3.3 · DANE UZUPEŁNIONE MUSZĄ BYĆ WIDOCZNE I EDYTOWALNE

„Reszta danych uzupełnia się sama" **nie może znaczyć „nie widać ich"**. Ludzie zmieniają adres,
nazwisko i e-mail, a **te dane idą do rozliczeń**. Pokaż wypełnione pola do **potwierdzenia lub
poprawienia**, jednym spojrzeniem zamiast przepisywania — a poprawka **aktualizuje konto**,
nie tylko tę jedną rezerwację.

### 3.4 · ZEGAR BLOKADY STARTUJE PO POTWIERDZENIU KODU

Gdyby 10 minut liczyło się od wyboru terminu, **krok z kodem zjadłby połowę okna płatności**.

> **Wybór terminu → krótka blokada techniczna na czas kroku z kodem → po potwierdzeniu startuje
> pełne okno (10 min / 48 h).**

To jest ta sama blokada dwustopniowa, którą opisałem w `ZLECENIE-038`, **teraz z konkretnym
uzasadnieniem, a nie tylko z obawy o zamrażanie grafiku.**

## 4 · Uproszczenie, które proponuję ponad to, co powiedział właściciel

**Jeden przepływ zamiast dwóch:** **numer → kod → (uzupełnienie albo puste pola) → dane →
rezerwacja.** Także za pierwszym razem.

**Dwa powody, oba twarde:**

1. **Nie zbieramy strony danych osobowych od kogoś, kto nie potwierdził niczego.** Przy kolejności
   „najpierw wszystkie dane, potem kod" **każde porzucone zgłoszenie zostawia komplet danych
   osoby, której nie umiemy zweryfikować.** To jest zbieranie danych bez potrzeby.
2. **Jedna ścieżka w kodzie zamiast dwóch** — nowy i wracający pacjent idą tą samą drogą,
   różnią się tylko tym, czy pola są puste. **Rozgałęzienie na wejściu to miejsce, w którym
   powstają wady widoczne tylko w jednej gałęzi.**

## 5 · Odpowiedź na pytanie o dwie drogi rezerwacji

**Tak, są dwie: panel pacjenta i strona.** Ale **sesja jest jedna** — po zalogowaniu w jednym
miejscu drugie **nie pyta ponownie**, bo tożsamość jest wspólna. **To jest cały sens SSO
i właśnie dlatego kroku z kodem nie wolno zaimplementować osobno w WordPressie:** dwie
implementacje = dwie sesje = pacjent loguje się dwa razy i nikt nie wie dlaczego.

**Zalogowany pacjent nie dostaje kodu w ogóle** — ma sesję, rezerwuje wprost. Kod pojawia się
**tylko wtedy, gdy sesji nie ma**.

## 6 · Czego NIE robisz

**Nic z powyższego.** `PODJETO-032`, potem `BEZ_DANYCH_OSOBOWYCH`. To jest zapis wymagań, żeby
przetrwały noc. **Decyzja o usunięciu ścieżki gościa nadal NIE ZAPADŁA** — czeka na pomiar kont
(`ZLECENIE-027`: czy Keycloak umie kod jednorazowy i czy umie go wysłać SMS-em).

**Jeśli którykolwiek punkt kłóci się z tym, co masz zmierzone — powiedz. Nie dopasowuj.**

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · **modelu danych nie zmieniasz** · realmu nie dotykasz ·
ścieżki bezwzględne · nic poza fundację · **S-2 i S-3 obowiązują.**
