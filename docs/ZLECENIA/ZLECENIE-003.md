# ZLECENIE-003 — zapisz decyzję właściciela o kredycie. Drobne, ale zapobiega zbudowaniu czegoś zbędnego.

> Pomiar kanału w pierwszej linii, jak zwykle. Wykonaj PO zakończeniu ZLECENIA-002.

## Co jest nie tak

Weryfikator architekta wykazał, że **decyzja właściciela z 09.08 nie dotarła do Twoich dokumentów**.
Właściciel rozstrzygnął: **„kredyt za odsprzedany termin" PRZECHODZI DO DALSZEJ FAZY**, poza pierwsze
wdrożenie. Tymczasem u Ciebie nadal figuruje jako element zakresu, m.in.:

```
docs/specyfikacja/04-streszczenie-zakres.md:391  REGULY.kredytZaOdsprzedany = true
docs/specyfikacja/04-streszczenie-zakres.md:57   pozycja 14 zakresu (BACKEND)
docs/specyfikacja/03-streszczenie-jak-dziala.md:154
```

## Czego NIE robić — to jest ważniejsze niż sama poprawka

**Nie usuwaj tego ze streszczeń specyfikacji.** Streszczenia 03/04 mają wiernie oddawać, co mówi
specyfikacja od właściciela — a ona kredyt zawiera. Skasowanie sprawiłoby, że streszczenie
przestałoby zgadzać się ze źródłem, i przy następnym porównaniu ktoś „naprawiłby" je z powrotem.

## Co zrobić

1. **Wpis w `docs/DECYZJE.md`** z datą 09.08.2026: kredyt za odsprzedany termin **poza zakresem
   pierwszego wdrożenia**, decyzja właściciela. Z uzasadnieniem: saldo kredytu jest formą
   finansowej historii pacjenta, a `CLAUDE.md` zamyka zakres słowami „brak pakietów wizyt,
   **brak historii finansowej pacjenta**" — bez tej decyzji te dwa zapisy stoją w sprzeczności.
2. **Znacznik przy każdym z trzech miejsc w streszczeniach**: jedno zdanie „poza zakresem
   pierwszego wdrożenia — D-2026-08-09-xx", ze wskazaniem wpisu w DECYZJE.
3. **Sprawdź `PLAN-FAZ.md`** — jeśli kredyt figuruje tam jako zadanie do wykonania, wyjmij go
   z listy i wskaż decyzję.
4. **Wpis do rejestru NON-DEFEKTÓW**: przy przepisywaniu ekranu `/koordynacja/reguly` z makiety
   wiersz o kredycie trzeba **świadomie pominąć**, a nie przenieść odruchowo „bo był w makiecie".
   Bez tego zapisu następna sesja uzna celowy brak za lukę do naprawienia.

## Oddanie

`docs/ZLECENIA/ODPOWIEDZ-003.md` — pomiar kanału i lista miejsc, w których nanieśeś znacznik.
