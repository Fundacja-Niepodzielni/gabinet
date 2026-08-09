# ZLECENIE-002 — PILNE, krótkie: przywróć brakujący rdzeń wytycznych u siebie

> Pomiar kanału jak zwykle w pierwszej linii odpowiedzi.

## Skąd to zlecenie

Weryfikator architekta sprawdził twierdzenie, które powtarzałem od trzech dni: że do
`WYTYCZNE-PRACY.md` **każdego** repozytorium kopiuję IDENTYCZNY rdzeń wspólny, a różnić się mają
tylko lokalne przykłady. **Twierdzenie OBALONE pomiarem.** Rdzeń się rozjechał — pracujecie
według różnych zasad, sądząc, że według tych samych. To mój błąd propagacji, nie wasz.

**Najgroźniejsza rozbieżność, i dlatego to zlecenie jest pilne:** w jednym repozytorium reguła
o sprzecznych poleceniach istnieje **bez klauzuli WYJĄTKU**. Klauzula brzmi: sprzeczność
z `CLAUDE.md` albo z zamkniętą decyzją → **PYTAJ przed wykonaniem**, nie „wykonaj nowsze i zgłoś".
Bez niej sesja wykona polecenie łamiące zasadę twardą i uzna, że postąpiła zgodnie z regułą —
podczas gdy sąsiednie repozytorium w tej samej sytuacji zapyta. To jest różnica między
„zabezpieczenie działa" a „zabezpieczenie istnieje na papierze".

## Co robisz

1. Przeczytaj `D:\KOD\Niepodzielni\_architektura\weryfikacja-architekta\2026-08-09-kryteria-faz.md`,
   sekcję dotyczącą **Zlecenia 2** (porównanie czterech plików wytycznych). Tam jest wypisane,
   czego brakuje KONKRETNIE u Ciebie.
2. **Przywróć brakujące reguły rdzenia** w swoim `WYTYCZNE-PRACY.md`. Rdzeń bierz z repozytorium,
   które daną regułę ma — **nie z mojego opisu**. Moje parafrazy okazały się zawodne: wczoraj
   przekręciłem wzorzec gabinetu w sposób, który unieważniał jego sens, i trafiło to do helpdesku.
3. Przy każdej przywracanej regule **zachowaj instancję zmierzoną** (przykład z pomiarem).
   Reguła bez przykładu czyta się jak ogólna ostrożność i pierwsza wylatuje przy porządkowaniu.
4. Jeśli uważasz, że któraś reguła NIE powinna u Ciebie obowiązywać — nie pomijaj jej po cichu.
   Wpisz ją z adnotacją „nie stosuje się u nas, ponieważ…" i uzasadnij. Cichy brak jest gorszy
   niż jawny wyjątek.

## Czego nie robisz

Nie tykasz kodu · nie zmieniasz `CLAUDE.md` · nie tykasz cudzych repozytoriów ·
zero `main`, merge, deploy, nic poza fundację.

## Oddanie

`docs/ZLECENIA/ODPOWIEDZ-002.md`: pomiar kanału, potem lista reguł przywróconych, lista
oznaczonych jako niestosujące się (z uzasadnieniem) i jedno zdanie, czy po tej naprawie
Twój rdzeń zgadza się z pozostałymi trzema.

**To zlecenie ma pierwszeństwo przed pracą, którą właśnie kończysz** — jest krótkie i dotyczy
zabezpieczenia, które ma działać, zanim ktokolwiek wyda kolejne polecenie.
