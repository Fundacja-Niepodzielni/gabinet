# Rejestr NON-DEFEKTÓW — rzeczy, których BRAK jest zamierzony

**Po co ten plik.** Celowy brak wygląda dokładnie tak samo jak luka. Następna sesja — albo
weryfikator — znajduje go, uznaje za niedopatrzenie i **naprawia**, czyli przywraca to, co
świadomie wyłączyliśmy. Bez tego rejestru każda decyzja o zawężeniu zakresu ma termin
ważności równy pamięci osoby, która ją podjęła.

**Zasada wpisu:** co jest nieobecne · **dlaczego to jest decyzja, nie przeoczenie** ·
odsyłacz do decyzji · **czego NIE robić**, gdy się na to natknie.

Symetrycznie: tu trafiają też mechanizmy, które **zadziałały poprawnie** i przy porządkowaniu
wyglądają na zbędne — żeby nikt ich nie uprościł, nie wiedząc, co ograniczyły.

---

## ND-01 · Kredyt za odsprzedany termin — NIE budujemy go w pierwszym wdrożeniu

**Co jest nieobecne.** Mechanizm „kredytu za odsprzedany termin": gdy po późnym odwołaniu ktoś
inny wykupi zwolnioną godzinę, pierwszy pacjent dostaje równowartość jako kredyt na kolejną
wizytę. W makiecie jest to **wiersz na ekranie `/koordynacja/reguly`** (przełącznik on/off),
a w specyfikacji reguła opisana na stronach 7, 13, 24, 49, 51, 58, 62.

**Dlaczego to decyzja, nie przeoczenie.** Właściciel rozstrzygnął 09.08.2026: **przechodzi do
dalszej fazy**. Powód nie jest wyłącznie oszczędnością pracy — **saldo kredytu jest formą
finansowej historii pacjenta**, a `CLAUDE.md` zamyka zakres pierwszego wdrożenia słowami
*„brak pakietów wizyt, **brak historii finansowej pacjenta**"*. Budowanie kredytu wymagałoby
pamiętania, komu ile się należy i za co — czyli dokładnie tego, czego zakres nie przewiduje.

**Odsyłacz:** `docs/DECYZJE.md`, **D-2026-08-09-01**.

**Czego NIE robić, gdy się na to natkniesz:**

1. **Przepisując ekran `/koordynacja/reguly` z makiety — pomiń wiersz o kredycie ŚWIADOMIE.**
   Nie przenoś go odruchowo „bo był w makiecie". Makieta jest źródłem **wyglądu**, nie źródłem
   prawdy o zakresie.
2. **Nie kasuj wzmianek o kredycie ze streszczeń specyfikacji** (`docs/specyfikacja/03-…`,
   `04-…`, `05-…`). One mają wiernie oddawać specyfikację właściciela, a ona kredyt zawiera.
   Skasowanie rozjechałoby streszczenie ze źródłem i przy następnym porównaniu ktoś
   „naprawiłby" je z powrotem. **Każde z tych miejsc ma znacznik** odsyłający do D-2026-08-09-01.
3. **Nie zgłaszaj jako luki w pokryciu**, że żaden test nie sprawdza kredytu. Nie ma czego
   sprawdzać.

**Co NIE jest tą decyzją przesądzone:** że kredyt jest złym pomysłem. Wraca razem z historią
finansową pacjenta, do której należy.

---

## ND-02 · `trap` przywracający pliki w perturbacjach — wygląda na nadmiarowy, NIE upraszczać

**Co wygląda na zbędne.** `skrypty/perturbacje.sh` przywraca zmienione pliki przez `trap`
obejmujący `EXIT INT TERM`, mimo że każdy scenariusz i tak kopiuje plik z powrotem na końcu.
Przy porządkowaniu wygląda to na dwa mechanizmy robiące to samo.

**Dlaczego zostaje.** Zmierzone 09.08: zestaw **wywalił się w połowie** (`this: unbound
variable` na scenariuszu 26 z 30). Kopiowanie na końcu scenariusza **nie wykonało się**, bo
scenariusz nigdy nie doszedł do końca — a `trap` przywrócił pliki i **to on ograniczył szkodę**.
Bez niego w drzewie zostałaby zmutowana reguła.

**Czego NIE robić:** nie zastępować `trap`-a „porządnym sprzątaniem na końcu funkcji". Ścieżka
końca funkcji jest dokładnie tą, która nie wykonuje się przy awarii — czyli wtedy, gdy
sprzątanie jest potrzebne.

**Odsyłacz:** `docs/noc-2026-08-08/ZNALEZISKA.md`, N-10 i N-11.
