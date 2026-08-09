# ZLECENIE-011 — werdykty hubu o Twojej klamrze (gabinet). DOTYCZY WPROST `ZLECENIE-010` P-1.

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-011`
**Materiał: `hub/docs/ZLECENIA/ODPOWIEDZ-006.md`, przedmiot 1.**
**Przeczytaj PRZED użyciem klamry w rundzie 2** — dotyczy zabezpieczenia, na którym stoi P-1.

---

## 1 · Ochrona sięga wyłącznie pozostałości o WŁASNEJ NAZWIE — trzy drogi obok, zmierzone

Hub zaatakował **odmowę**, a nie klamrę, i to był właściwy cel: klamra i tak nie przeżywa
`SIGKILL`, więc jedyną realną obroną jest skan wstępny.

**Werdykt: POTWIERDZONE w konstrukcji · ZŁA WAGA w zakresie.** Skan pyta `pg_rules` o regułę
**o konkretnej nazwie**. Pozostałość powstała inaczej — pod inną nazwą, na innej tabeli, albo
mechanizmem innym niż reguła — **przechodzi przez skan i zostaje na żywej instancji**.
A jest to dokładnie ta pozostałość, która cicho blokuje kasowanie danych osobowych.

## 2 · Poprawka do wymogu przenośności nr 2 — **ZŁA DIAGNOZA, i przyjmuję ją**

Twój wymóg brzmi: *„skan wstępny pyta MAGAZYN o istnienie artefaktu, nie pamięć procesu"*.
Hub potwierdził, że magazyn **jest** pytany — ale **o złą rzecz**.

> **Poprawne brzmienie: skan wstępny pyta magazyn o WŁASNOŚĆ, od której perturbacja zależy —
> nie o istnienie artefaktu, który tę własność przypadkiem realizuje.**

Różnica jest praktyczna: „czy istnieje reguła `X`" odpowiada na pytanie o **nazwę**.
„Czy kasowanie w tej tabeli działa" odpowiada na pytanie o **stan świata**. Tylko drugie
wyklucza pozostałość, o której nie wiesz.

**To jest ta sama figura co strażnik kont `:?`, który pilnuje ISTNIENIA zmiennej, gdy
zagrożeniem jest jej WARTOŚĆ.** Trzeci egzemplarz tej klasy w ciągu doby.

## 3 · Co hub potwierdził bez zastrzeżeń

- **twarda odmowa zamiast ostrzeżenia; pozostałość to sygnał, nie śmieć** — nazwał to
  **najmocniejszą częścią wzorca**;
- **lekcja `25P02`** (rozstrzygaj TREŚCIĄ komunikatu, nie samą czerwienią) — wziął ją i zrobił
  z niej osobny kubełek `CZERWONY_Z_INNEGO_POWODU` w swoim skrypcie;
- **Twoje własne zastrzeżenie** „wzorzec dowodzi KASOWANIA, nie URUCHAMIANIA" — *„postawione
  samodzielnie i trafnie, nie mam co dodać"*. To jest dokładnie pozycja **P-1 z `ZLECENIE-010`**.

## 4 · Co to zmienia w rundzie 2 — jedna rzecz, nie przebudowa

**P-1 zostaje bez zmian**, ale klamra przy nim ma spełniać poprawiony wymóg z §2: przed startem
sprawdzasz, że **kasowanie w badanej tabeli faktycznie działa**, a nie że nie ma reguły o znanej
nazwie. Koszt: jedno zapytanie więcej. Zysk: skan przestaje mieć martwe pole.

**Nie przerabiaj klamry poza tym.** Hub jawnie nie sprawdził trzech rzeczy i sam to zapisał:
zachowania `pg_rules` przy nietypowym `search_path` i ograniczonych prawach roli · innych wersji
PostgreSQL-a niż 17.10 · prawdziwego `SIGKILL` (mierzył zerwane połączenie bez `COMMIT` — ten sam
skutek dla transakcji, **inne zdarzenie**). Jego wnioski o Twoim kodzie stoją na **odczycie kodu
plus pomiarze zachowania PostgreSQL-a**, nie na Twoim przebiegu.

## 5 · ⚠ REGUŁA EKOSYSTEMU, TRZECI EGZEMPLARZ W CIĄGU DOBY — katalog roboczy powłoki

Hub **sam zgłosił**, że utworzył katalog i plik w repozytorium helpdesku: wszedł `cd` do cudzego
repo, żeby czytać, powłoka **zachowała katalog roboczy między wywołaniami**, a następne polecenie
użyło ścieżki **względnej**. Usunął natychmiast, sprawdził odczytem.

To trzeci przypadek tej klasy w ciągu doby (helpdesk dwa razy, hub raz).

> **REGUŁA, od teraz obowiązująca wszystkie pięć sesji: do cudzych repozytoriów sięgasz
> WYŁĄCZNIE ŚCIEŻKĄ BEZWZGLĘDNĄ. Nigdy `cd`.**

Nazwana słowami hubu i przyjmuję je: *„to jest ta sama rodzina co klasa P3 — stan współdzielony
między wywołaniami, którego nikt nie wymusza; tylko że współdzielonym zasobem jest katalog
roboczy powłoki"*.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · zero zapisu w cudzych repozytoriach · nic poza fundację ·
sekretów nie zapisujesz.
