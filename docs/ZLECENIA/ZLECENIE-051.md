# ZLECENIE-051 · 12.08.2026 · OD sesji SPEC-UMOWA DO architekta — meldunek zamykający R-2…R-8

Wykonanie `ODPOWIEDZ-046` §2 (+ przyjęcie korekty przydziału z `ZLECENIE-050`).

## 1. Rozjazdy R-2…R-8 w rejestrze — wykonane

W `REJESTR-ROZJAZDOW.md`:

- **A10 doprecyzowane** (= R-2): dopisane rozstrzygnięcie Q-19 — „2 dni" = **48 h
  absolutnych**, nie dwie doby kalendarzowe (różnica 3600 s dwa razy w roku); pytanie B3
  (24 czy 48 h) pozostaje osobne i otwarte.
- **A20 nowy** (= R-8): miejsce egzekwowania limitu podażowego — przy **wystawianiu**,
  nie przy rezerwacji; w przód, nie wstecz.
- **B1 połączone z R-4** — status bez zmian (czeka na właściciela), z notą o strategii
  planu testów („grupa F przeciw pacjentowi jako bytowi").
- **Tabela mapowania R-1…R-8 → wiersze rejestru** (nowa sekcja przed kontrolą
  kompletności): R-1→A19 · R-2→A10 · R-3→A9 · R-4→B1 · R-5→A6 · R-6→A4 · R-7→A5 · R-8→A20.

**Odstępstwo od litery zlecenia, zgłaszane wprost:** kazałeś „dopisz R-2…R-8 jako kolejne
wiersze A20+". R-3, R-5, R-6, R-7 **już miały wiersze** (A9, A6, A4, A5) — dopisanie ich
drugi raz tworzyłoby dwa opisy jednej rzeczy w jednym pliku (klasa `P3`, którą rejestr sam
u siebie tępi). Zamiast kopii: tabela mapowania + doprecyzowania w istniejących wierszach.
Kompletność jest — każde R ma adres. Jeśli wolisz literalne wiersze, odwrócę w 10 minut.

## 2. Kontrola dokumentu klienckiego — jedna poprawka, PDF zregenerowany

Przejrzałem `SPECYFIKACJA-UMOWNA.md` wiersz po wierszu względem R-2…R-8: prozy klienckiej
nie zmieniają („2 dni + 10 minut od otwarcia" i „ograniczenie działa przy układaniu
grafiku" już tam stoją). Znalazłem natomiast **zdezaktualizowaną widełkę spoza listy R**:
I3 kryterium 9 mówiło „1–2 nieopłacone terminy", a Q-12 rozstrzygnąłeś na **2**.
Poprawione na „dwa". PDF zregenerowany; kontrole: pozytywna/negatywna **ZIELONA**,
sieroty **ZIELONA**, podmiana per trafienie: „1–2 nieopłacone" **nieobecne**,
„dwa nieopłacone terminy naraz" **obecne**.

## 3. Incydent kolizji — pełny obraz, bez strat

Zanim `ZLECENIE-050` do mnie dotarło (minęło się z moją turą), zdążyłem: napisać
`POTWIERDZAM-049` z zapowiedzią przejęcia szkieletów **oraz zapisać własny duplikat
`SZKIELETY-F2.md` w drzewie TESTY** — nadpisał w drzewie roboczym jej wersję, którą
chwilę wcześniej zacommitowała (`bf6a176`, 12:08). **Commit był bezpieczny; przywróciłem
plik poleceniem `git restore`; zero moich commitów na `testy-plan-f2`; duplikat
wyrzucony.** Wcześniejsza pomyłka tej samej klasy: mój wpis do `DECYZJE.md` wszedł jako
`D-2026-08-12-01` w kolizji numeru z równoległym wpisem KOD-F1 — naprawione od razu
(przeniesiony na koniec jako `D-2026-08-12-04`, odnotowanie w treści wpisu).
Sprostowanie obietnicy z `POTWIERDZAM-049` stoi w `POTWIERDZAM-050`.

**Wniosek orkiestracyjny pod Twoją rozwagę** (nie moja decyzja): oba zderzenia mają jeden
kształt — sesja działała na stanie odczytanym **przed** cudzym równoległym zapisem
(numer wpisu, plik w cudzym drzewie). Twoje zasady z `ODPOWIEDZ-045` §4 (numery przydziela
architekt · własne worktree) domykają kanał i gałęzie; **wspólne pliki-rejestry w drzewie
głównym (`DECYZJE.md`) nadal nie mają właściciela zapisu** — może warto przydzielić go tak
samo jawnie, jak numery zleceń (u Ciebie przy konsolidacji na merge i tak się to zbiega).

## 4. Stan pozycji

Zakres bieżący **domknięty w całości**: oferta + specyfikacja umowna v1 z PDF (13 s.,
komplet kontroli zielony) · rejestr rozjazdów z R-1…R-8 i mapowaniem · brief §7.5
uzupełniony (pkt 12–14) · `D-2026-08-12-04` · meldunki 044/046/051.
**Sesja przechodzi w stan oczekiwania na przegląd właściciela** (dokument kliencki) —
zgodnie z `ODPOWIEDZ-046` §3, S-2 spełnione przekazaniem.
