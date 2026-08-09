# ZLECENIE-029 — DECYZJA WŁAŚCICIELA: okno 24 h liczone ZEGAROWO

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-029`
**Nie zmieniam kolejki** — to rozstrzygnięcie do wpisania w `docs/DECYZJE.md` i uwzględnienia
w pracy nad regułami, nie nowa pozycja.

---

## Decyzja

> **Okno 24 godzin liczy się ZEGAROWO, nie w dniach roboczych.**
> **Wraz z nią: przypomnienie o wizycie na 48 godzin przed terminem.**

**Uzasadnienie, które właściciel przyjął:** liczenie zegarowe jest prostsze do zrozumienia
dla pacjenta i prostsze w systemie; realny problem — „zapomniałem w weekend" — **rozwiązuje
przypomnienie, a nie wydłużanie okna**. Wizyta w poniedziałek o 9:00 wymaga odwołania
do niedzieli 9:00, a pacjent dostaje sygnał w piątek.

## Co z tego wynika dla Ciebie — i jedna zależność, o której trzeba wiedzieć

1. **Reguła okna zostaje w obecnym kształcie** — zegarowa, bez kalendarza dni roboczych.
   **Nie dokładaj obsługi świąt ani weekendów**; gdyby kiedyś miała wejść, będzie to nowa
   decyzja właściciela, nie rozszerzenie tej.
2. **Przypomnienie 48 h jest CZĘŚCIĄ tej decyzji, nie dodatkiem.** Właściciel zgodził się
   na zegarowe okno **pod warunkiem**, że przypomnienie istnieje. **Bez niego decyzja jest
   niepełna i tak ją zapisz** — inaczej za pół roku ktoś przeczyta „okno zegarowe" i uzna,
   że to całość ustalenia.
3. **⚠ Zależność, którą zgłaszam jako architekt:** przypomnienie SMS-em **nie może ruszyć,
   dopóki nadawca „Niepodzielni" nie jest zarejestrowany w SMSAPI** — to pozycja na liście
   właściciela i trwa kilka dni. **Zaprojektuj przypomnienie tak, żeby kanał był wymienny**
   (e-mail jako droga zastępcza), a nie tak, żeby czekało na rejestrację.
4. **Zamrożenie w chwili zakupu obowiązuje jak dotąd** — pacjent, który kupił przy starej
   regule, ma starą. Zmiana sposobu liczenia **nie działa wstecz**, zgodnie z zasadą twardą 4.

**Kontrola, o którą proszę przy okazji tej reguły** (nie teraz — gdy dojdziesz do okna):
**kierunek 0 na granicy** — dokładnie 24 h, sekundę przed i sekundę po. Trzy wartości, trzy
rozstrzygnięcia, żadne „mniej więcej".

## Odbiór `R6B-7` — jedno zdanie, bo zasługuje

**„Dwa groźne, cztery chronione czym innym"** — nie naprawiłeś na siłę sześciu, tylko
rozdzieliłeś je pomiarem. **Uczciwy negatyw przy czterech jest tu wart tyle samo co naprawa
przy dwóch**, bo pokazuje, że wzorzec nie jest zaklęciem stosowanym wszędzie.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · ścieżki bezwzględne, nigdy `cd` · nic poza fundację ·
**S-2 obowiązuje: nie stoisz.**
