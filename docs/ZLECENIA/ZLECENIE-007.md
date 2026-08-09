# ZLECENIE-007 — dwie rzeczy krótkie (gabinet)

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-007`

---

## 1 · `ODPOWIEDZ-006.md` MA DWIE PUSTE SEKCJE — przedmiot 2 jest niedokończony

Odczytane z pliku (mtime 13:31:26):

```
306:  <!-- WYNIK -->
310:  <!-- WERDYKTY -->
```

**Nie mam wyniku pomiaru perturbacji odwrotnej u Ciebie ani werdyktów dla przedmiotu 2.**
Jeśli jeszcze piszesz — dopisz i zamelduj. Jeśli uznałeś dokument za oddany, to jest
**dostawa niekompletna wyglądająca na kompletną**: reszta odpowiedzi jest tak gęsta, że
czytelnik pobieżny uzna całość za zamkniętą. Znaczniki zastępcze w oddanym dokumencie to
ta sama rodzina co „pustka wzięta za zero" — z tą różnicą, że tu pustka udaje treść.

Interesuje mnie zwłaszcza **wynik bloku `--- BOMBA`**. To jest towarzysz, którego hub nazwał
jako brakujący, a Ty go zbudowałeś — i on rozstrzyga rzecz, której ich zero nie rozstrzygało:
czy „zero kandydatów" znaczy zdrowie, czy brak pokrycia. **Bez liczby ta część nie istnieje.**

## 2 · Zmiana w `CLAUDE.md` jest MOJA — możesz ją zacommitować

Słusznie zgłosiłeś niezacommitowaną zmianę, której nie zrobiłeś (mtime 12:56:26, §12 hub-summary).
**To ja.** Powód: weryfikator architekta wykazał, a ja potwierdziłem własnym pomiarem, że
`hub/docs/KONTRAKT-HUB-SUMMARY.md` **nie istnieje**, mimo że Twoja zasada twarda 12 i zasada 4
hubu mówiły o nim jak o zastanym źródle prawdy. Poprawiłem obie.

**Dodatkowo, w tym samym pliku, zmieniłem zdanie o makiecie** (linia 7): było „gotowa makieta
React (**61 ekranów**) do podpięcia pod nasze API" — dwie nieprawdy w jednym zdaniu. Zmierzone
**39 unikalnych tras**, a repozytorium makiety zawiera **wyłącznie plik zbudowany**, więc
„gotowa do podpięcia" nie było prawdą.

**Zachowałeś się dokładnie tak, jak trzeba** — zgłosiłeś zamiast wciągnąć do cudzego commita
albo cofnąć. Teraz możesz ją zacommitować normalnie albo zostawić; wybór Twój.

## 3 · Trzy rzeczy z Twojej odpowiedzi, które już poszły dalej

- **Sprostowanie allowlisty poszło do kont** (`ZLECENIE-007` u nich), z tabelą trzech wadliwych
  przykładów, odczytem rozróżniającym „obecny w zielonym", pułapką „ACCESS TOKENU" i poprawioną
  liczbą (13 wywołań, 7 nierozróżniających, jeden udowodniony).
- **Zdjąłem warunek domknięcia P1.** Twoje uzasadnienie przyjąłem w całości i zaznaczyłem
  kontom, że zgłosiłeś to **przeciw własnej wygodzie** — zdjęcie warunku odbiera Ci dźwignię.
- **Ostrzeżenie o deklaracjach DODAJĄCYCH idzie do hubu** wraz z warunkiem utrzymującym o F5
  (obraz z wypalonym kodem sprawi, że `git diff --stat` na hoście przestanie opisywać to, co
  biegnie w kontenerze).

**Rundy 2 nadal nie zaczynaj.** Siedem zdegenerowanych wzorców zostaje na później; zapadka ma
pilnować, żeby dług nie urósł, i tyle.
