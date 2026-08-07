# DPIA-checklista — Gabinet

**Data:** 2026-08-07 · **Faza:** F1, zadanie pierwsze · **Status:** roboczy,
wymaga potwierdzenia przez prawnika fundacji (patrz §11).

---

## 1. Po co ten dokument i czego NIE zastępuje

Specyfikacja stawia sprawę jednoznacznie: **DPIA trzeba zamknąć PRZED etapem
„model danych", nie po** (M2/22), a przy danych o zdrowiu i profilowaniu
dopasowania specjalisty ocena skutków jest **wymagana, nie opcjonalna** (M6/9).
`CLAUDE.md` §10 powtarza to jako pierwszą pozycję fazy F1.

**Czym ten dokument JEST:** checklistą inżynierską. Każda pozycja kończy się
konkretnym wymaganiem dla migracji, kodu albo bramki testowej. Jej celem jest
odpowiedzieć na pytanie „jak ma wyglądać schemat bazy", zanim ktokolwiek napisze
pierwszą migrację.

**Czym NIE jest:** formalną oceną skutków w rozumieniu art. 35 RODO ani rejestrem
czynności przetwarzania. Te dokumenty powstają poza kodem (M6/9) i podpisuje je
fundacja. Ta checklista ma im dostarczyć **inwentarza danych i środków
technicznych** — czyli tej części, której nie da się napisać bez zajrzenia
do schematu.

**Reguła robocza:** jeśli pozycja z tej listy nie ma odpowiednika w migracji,
w zadaniu czyszczącym albo w teście — nie jest zrobiona.

---

## 2. Co przetwarzamy i po co

| | |
|---|---|
| **Administrator** | Fundacja Niepodzielni |
| **Cel główny** | organizacja i rozliczenie wizyt psychologicznych oraz wydarzeń grupowych |
| **Kategorie osób** | pacjenci (w tym goście bez konta), osoby zgłaszające się pierwszy raz, specjaliści, koordynatorzy, prowadzący grup |
| **Skala** | 111 specjalistów; kilkanaście tysięcy wizyt rocznie; tysiące pacjentów |
| **Dane art. 9** | **TAK** — patrz §3. To przesądza o obowiązku DPIA. |
| **Profilowanie** | **TAK, ale wspomagające**: silnik dopasowania proponuje 3 specjalistów, a **decyzję podejmuje człowiek** (koordynator). Żadna propozycja nie jest zaznaczona domyślnie (spec s. 16–17, 40). To nie jest „decyzja wyłącznie zautomatyzowana" w rozumieniu art. 22. |
| **Czego NIE ma w systemie** | notatek z sesji i dokumentacji terapeutycznej (świadomie poza pierwszym wdrożeniem), historii finansowej pacjenta, treści rozmów widocznych dla koordynatora |

### Dlaczego to są dane o zdrowiu, choć nikt nie wpisuje diagnozy

Trzy niezależne ścieżki czynią z tego zbiór art. 9:

1. **Sam fakt korzystania** z pomocy psychologicznej jest informacją o zdrowiu.
   Rezerwacja u psychologa = dana o zdrowiu, nawet bez żadnego opisu.
2. **Obszar wsparcia** z formularza pierwszego kontaktu (do 2 pozycji z listy 12:
   lęk, depresja, trauma, żałoba, wypalenie…) to wprost informacja o stanie
   zdrowia psychicznego.
3. **Lista uczestników grupy** o określonym profilu (spec M2/17 podaje przykład
   grupy dla osób z depresją) to zbiór danych o zdrowiu — i on **wychodzi na
   dysk** przy eksporcie listy obecności.

Wniosek dla modelu: kolumny z §3 oznaczone 🔒 są szyfrowane, a dostęp do nich
jest logowany.

---

## 3. Katalog danych — co, gdzie, jak długo

Legenda: 🔒 = szyfrowanie kolumny · 📋 = dostęp logowany · ⏱ = zadanie czyszczące

| Kategoria | Przykładowe pola | Art. 9 | Podstawa | Retencja | Wymóg techniczny |
|---|---|---|---|---|---|
| Tożsamość pacjenta | `keycloak_sub`, e-mail, telefon, imię | nie | umowa (art. 6.1.b) | do usunięcia konta + okres roszczeń | 📋 telefon |
| **Fakt wizyty** | specjalista, termin, usługa, status | **TAK** | zgoda art. 9.2.a | 5 lat od wizyty (razem z rozliczeniem) | ⏱ |
| **Zgłoszenie pierwszego kontaktu** | obszar wsparcia (≤2 z 12), pilność, doświadczenie terapii, forma, miasto | **TAK** | zgoda art. 9.2.a (osobna!) | **zgłoszenia bez rezerwacji: krótko** — patrz P-3 | 🔒 📋 ⏱ |
| Telefon w kolejce kwalifikacji | numer | nie sam w sobie | umowa | jw. | 🔒 📋 — **odsłonięcie = wpis w logu** (spec s. 16, 40) |
| **Uczestnictwo w grupie** | id wydarzenia, obecności | **TAK** | zgoda art. 9.2.a | 5 lat | 🔒 przy grupach profilowanych |
| Zgody | wersja regulaminu, wersja polityki, znacznik czasu, IP | nie | obowiązek rozliczalności | **cały okres + 6 lat po** (dowód zgody) | wersjonowanie, nigdy UPDATE |
| Płatności | `stripe_payment_intent`, `kwota_zamrozona`, 4 ostatnie cyfry karty | nie | umowa + obowiązek podatkowy | 5 lat podatkowych | **nigdy pełnego numeru karty** |
| Faktury specjalistów | plik PDF, NIP, adres, rachunek | nie | obowiązek podatkowy | **5 lat** (M4/5) | poza katalogiem publicznym, antywirus, linki wygasające |
| Ślad audytowy | aktor, typ zdarzenia, czas | zależy od treści | rozliczalność | do ustalenia (P-4) | tylko INSERT, **bez treści zdrowotnych** |
| **Dziennik decyzji uznaniowych** | uzasadnienie, kogo dotyczy, skutek budżetowy | **TAK** (uzasadnienie potrafi je zawierać) | rozliczalność dotacji | patrz **P-1 — kwestia prawna** | tylko INSERT, odebrane UPDATE/DELETE roli bazodanowej, łańcuch skrótów |
| Wiadomości | treść wątku, kontekst wizyty | **TAK** (pacjent pisze o samopoczuciu) | zgoda art. 9.2.a | do ustalenia (P-4) | 🔒 📋 ⏱ |
| Zgłoszenia problemów + zrzuty ekranu | obraz ekranu koordynatora | **TAK** — zrzut zawiera dane innych pacjentów | uzasadniony interes | krótka (P-5) | 🔒 ⏱, **usuwanie metadanych obrazu** |
| Log wysyłek SMS/mail | numer **przycięty do 3 ostatnich cyfr**, status | nie | rozliczalność | 12 mies. | ⏱ — bez treści zdrowotnych |
| Raport grantowy | wyłącznie liczby | **nie** | obowiązek dotacyjny | bezterminowo | **snapshot bez danych osobowych** |

### Trzy rzeczy, których w bazie NIE BĘDZIE

1. **Pełnego numeru karty ani danych karty** — trzyma je Stripe; my mamy 4 cyfry
   i datę ważności pobierane z API (spec s. 23).
2. **Pola opisowego w formularzu pierwszego kontaktu.** 6 pytań, wszystkie
   z zamkniętą listą, zero pól tekstowych — „każde dodatkowe pole trzeba
   zabezpieczyć, przechować i po czasie usunąć" (spec s. 17, 40).
3. **Uzasadnienia zdrowotnego przy wniosku o zwolnienie z opłaty.** Pole jest
   **wyłącznie finansowe** i musi to mówić wprost pod polem (spec s. 10–11, 30).
   To jest projektowanie pod minimalizację, nie kosmetyka: pole otwarte przy
   wniosku o pomoc finansową samo prosi o wpisanie informacji o zdrowiu.

---

## 4. Wymagania dla modelu danych — wprost do migracji F1

Każda pozycja poniżej ma trafić do migracji albo do testu. To jest wynik tej
checklisty, czyli to, po co ona powstała.

| # | Wymaganie | Skąd |
|---|---|---|
| W-1 | Kolumny 🔒 z §3 szyfrowane na poziomie aplikacji (`encrypted` cast), nie tylko szyfrowanie dysku | CLAUDE.md §10 |
| W-2 | `zgoda` **wersjonowana**: osobny wiersz na każdą wersję regulaminu i polityki; nigdy UPDATE | spec M1/22, M6/10 |
| W-3 | Rezerwacja niesie `kwota_zamrozona` **i** `regula_anulacji_zamrozona` jako **pełny zrzut**, nie referencję | CLAUDE.md §4 |
| W-4 | Rezerwacja zapisuje **wersję zaakceptowanego regulaminu** — analogicznie do reguły anulacji | spec M6/10 |
| W-5 | Tabela `zdarzenie` (ślad audytowy) — tylko INSERT, aktor: człowiek albo system | spec M5/13 |
| W-6 | Dziennik decyzji — tabela z **odebranym UPDATE i DELETE roli bazodanowej aplikacji**, numeracja `DEC-rok-numer`, łańcuch skrótów | CLAUDE.md §9 |
| W-7 | Log dostępu do danych wrażliwych: osobna tabela, wpis przy **odsłonięciu telefonu** i przy otwarciu kartoteki | spec M2/22, M3/12 |
| W-8 | Wszystkie znaczniki czasu jako `timestamptz`, w bazie UTC | CLAUDE.md §5 |
| W-9 | Retencje jako **zadania czyszczące w kodzie**, nie jako zapis w polityce | CLAUDE.md §10 |
| W-10 | Usunięcie konta = **anonimizacja z zachowaniem spójności raportów**, nie `DELETE` | spec M5/12 |
| W-11 | Identyfikatory rezerwacji **nieodgadywalne** albo chronione sprawdzeniem właściciela przy każdym zapytaniu (stare `NP-` + numer arytmetyczny to podatność) | spec M6/5 |
| W-12 | Tożsamość pacjenta-gościa w raporcie grantowym — rozstrzygnięta **przed** budową raportu, inaczej ta sama osoba policzy się kilka razy | spec M4/8 |

---

## 5. Kto co widzi — macierz dostępu

To jest wymóg architektoniczny, nie kosmetyczny: ukrycie kolumny w interfejsie
jest w specyfikacji **jawnie odrzucone jako niewystarczające** (M2/1, M4/19).

| Dane | pacjent | psycholog | koordynator | admin |
|---|---|---|---|---|
| Własne wizyty | ✅ | — | — | — |
| Wizyty u siebie | — | ✅ | ✅ | ✅ |
| **Prowizja fundacji** | ❌ | **❌ (test regresyjny)** | ✅ | ✅ |
| **Kwota zapłacona przez pacjenta** | ✅ (własna) | **❌** | ✅ | ✅ |
| Odpowiedzi z formularza pierwszego kontaktu | — | ❌ | ✅ 📋 | ✅ 📋 |
| Telefon w kolejce kwalifikacji | — | ❌ | ✅ **po kliknięciu „Pokaż", z wpisem w logu** | ✅ 📋 |
| Powód odwołania przez specjalistę | ❌ | ✅ (własny) | ✅ | ✅ |
| Kto ile zapłacił za grupę | ❌ | **❌** | ✅ | ✅ |
| Treść wiadomości | ✅ (własne) | ✅ (własne wątki) | ❌ | ❌ |
| Dziennik decyzji | ❌ | ❌ | ✅ | ✅ |

**Egzekwowanie:** warstwa repozytorium filtruje po właścicielu, „żeby
ograniczenie nie zależało od pamięci autora kontrolera" (spec M2/1); osobne DTO
per rola; test regresyjny sprawdzający, że odpowiedź API dla roli `psycholog`
**nie zawiera pól prowizji w żadnym polu**.

---

## 6. Logowanie dostępu — co zapisujemy, a czego nie

| Zapisujemy | Nie zapisujemy |
|---|---|
| kto (id personelu), kiedy, czego dotyczyło (id pacjenta/zgłoszenia), typ operacji | **treści** danych wrażliwych |
| odsłonięcie telefonu w kolejce kwalifikacji | zawartości wiadomości |
| otwarcie kartoteki pacjenta | tego, co koordynator zobaczył na ekranie |
| eksport CSV z danymi osobowymi | |

Uzasadnienie z ekranu kwalifikacji, warte zapamiętania: *„ekran bywa otwarty
w biurze, przy którym przechodzą inni"* — dlatego telefon jest domyślnie
zasłonięty, a jego odsłonięcie to zdarzenie, nie widok.

**Log dostępu sam jest zbiorem danych osobowych** — ma własną retencję i nie
wchodzi do eksportu ogólnego.

---

## 7. Retencje jako kod

Każdy okres z §3 ma mieć zadanie czyszczące w harmonogramie (F6, spec M6/4).
Polityka retencji, która żyje wyłącznie w dokumencie, jest deklaracją.

| Zadanie | Co usuwa/anonimizuje | Uwaga |
|---|---|---|
| `retencja:zgloszenia` | zgłoszenia pierwszego kontaktu, które **nie zamieniły się w rezerwację** | najkrótszy okres w systemie — te dane nie mają celu po wygaśnięciu propozycji |
| `retencja:zrzuty` | zrzuty ekranu ze zgłoszeń problemów | zawierają dane osób trzecich |
| `retencja:log-wysylek` | log SMS/mail | numery i tak przycięte do 3 cyfr |
| `retencja:wiadomosci` | wątki starsze niż okres z P-4 | |
| `retencja:konta` | konta pacjentów bez aktywności — **anonimizacja**, nie kasowanie | spójność raportu grantowego |

**Bramka:** każde zadanie ma test, który tworzy dane starsze niż okres, uruchamia
zadanie i **liczy**, ile rekordów zostało — nie sprawdza samego uruchomienia.

---

## 8. Podprocesorzy — rejestr do umów powierzenia

| Podprocesor | Co dostaje | Dane art. 9? | Umowa |
|---|---|---|---|
| **Stripe** (2 konta) | e-mail, kwota, metadane rezerwacji | pośrednio (fakt wizyty u psychologa) | wymagana |
| **SMSAPI** | numer telefonu, treść SMS | **nie** — treść bez słowa o zdrowiu i bez nazwy usługi | wymagana |
| Dostawca poczty transakcyjnej | e-mail, treść maila | tak (treść mówi o wizycie) | wymagana |
| **Jitsi** (D-2026-08-07-10) | zależnie od hostingu: self-host = **brak podprocesora**; JaaS = metadane sesji | tak, przy JaaS | wymagana tylko przy JaaS |
| Hetzner (hosting) | wszystko, co w bazie | tak | wymagana |
| Magazyn kopii zapasowych | zaszyfrowane kopie | tak | wymagana; **klucz poza tym dostawcą** |
| Keycloak (Konta Niepodzielni) | tożsamość, e-mail | nie | wewnętrzny system fundacji |
| Google Calendar (F4) | **wyłącznie `freeBusy`**, bez treści zdarzeń | nie | wymagana |
| Zammad (helpdesk) | treść zgłoszenia problemu | możliwe | wewnętrzny system fundacji |

Jedna z zalet zatwierdzonego kierunku Jitsi: przy self-hoście **ta tabela ma
o jeden wiersz mniej**.

---

## 9. Środowiska nieprodukcyjne

| Wymóg | Stan |
|---|---|
| **Twarda blokada wysyłki mail/SMS** poza produkcją | ✅ zrobione w F0 (`App\Wsparcie\BlokadaWysylki` + test pozytywny i negatywny) |
| Anonimizacja przy kopiowaniu produkcji na staging/dev | ⬜ F9 — skrypt maskujący z **zachowaniem proporcji** |
| Dane demonstracyjne z tego samego generatora | ⬜ F1 (seed) |
| Zakaz wynoszenia produkcyjnych zrzutów na maszyny deweloperów | ⬜ runbook, F9 |

**Uwaga z dziennika makiety, rozdz. 15:** anonimizacja musi **zachować
proporcje**. Czternaście nazwisk na 2758 wizyt dawało ~197 wizyt na osobę
i licznik limitu pokazywał „64 z 4" — ekran działał, tylko nic nie znaczył.
Dane bez proporcji nie pokazują reguły, którą ilustrują, więc nie nadają się
ani do testów, ani do szkoleń.

---

## 10. Ryzyka i środki

| Ryzyko | Skutek | Środek |
|---|---|---|
| Wyciek listy uczestników grupy profilowanej | ujawnienie danych o zdrowiu wielu osób naraz | 🔒 + 📋 + decyzja, czy eksport CSV w ogóle istnieje (P-6) |
| Zrzut ekranu koordynatora ze zgłoszenia problemu | dane innych pacjentów u wykonawcy | krótka retencja, usuwanie metadanych, rozstrzygnięcie roli wykonawcy (P-5) |
| Odgadywalny identyfikator rezerwacji | dostęp do cudzej rezerwacji | W-11 + sprawdzenie właściciela przy każdym zapytaniu |
| Token magic-linku w nagłówku `Referer` / logach proxy | **incydent RODO** (spec M1/9) | tokeny jednorazowe, krótkie życie, `Referrer-Policy` |
| Prowizja wyciekająca przez ogólny endpoint rezerwacji | złamanie CLAUDE.md §8 | osobne DTO + test regresyjny na KAŻDEJ odpowiedzi |
| Test przypomnień wysłany na prawdziwe adresy | masowy incydent | ✅ blokada z F0 |
| Kopia zapasowa bez ćwiczenia odtworzeniowego | „kopia jest deklaracją" | ćwiczenie z pomiarem czasu, F9 |
| Zgoda na dane o zdrowiu odklikana jednym kliknięciem | przerwanie leczenia | zgoda art. 9 **zablokowana** w „Moje dane" — wycofanie przez kontakt z fundacją |

---

## 11. Pytania, na które musi odpowiedzieć CZŁOWIEK

Trzy pierwsze **blokują** budowę odpowiadających im tabel. Reszta nie blokuje F1.

| # | Pytanie | Blokuje | Dlaczego to nie jest decyzja techniczna |
|---|---|---|---|
| **P-1** | **Prawo do usunięcia vs niezmienialny dziennik decyzji vs 5-letni obowiązek przechowywania.** Co robimy, gdy pacjent żąda usunięcia, a jego sprawa jest w dzienniku? | **dziennik decyzji (F5)** | Spec mówi wprost: „rozstrzygnięcie musi być **prawne**, a nie techniczne, i trzeba je mieć PRZED budową dziennika" (M4/21). Rekomendacja wykonawcy: pseudonimizacja **odniesienia** do osoby, przy zachowaniu wpisu i kwoty — do potwierdzenia. |
| **P-2** | **Podstawa prawna dla formularza pierwszego kontaktu**: zgoda art. 9.2.a przy pierwszym kontakcie, przed jakąkolwiek relacją z fundacją. Czy treść zgody jest gotowa? | kolejka zgłoszeń (F5) | Zgoda musi być konkretna i dobrowolna; jej treść pisze prawnik, nie programista. |
| **P-3** | **Jak długo trzymamy zgłoszenia, które NIE zamieniły się w rezerwację?** | zadanie `retencja:zgloszenia` | To dane o zdrowiu osoby, która ostatecznie nie została pacjentką. Im krócej, tym lepiej — ale okres musi wskazać fundacja. |
| P-4 | Retencja wiadomości i śladu audytowego | nie | |
| P-5 | Kto ma dostęp do zrzutów ekranu ze zgłoszeń i **czy wykonawca jest podmiotem przetwarzającym** | nie | spec M5/14 |
| P-6 | Czy eksport listy obecności grupy do CSV w ogóle ma istnieć, w jakim zakresie i z jakim pouczeniem | nie | spec M2/17 |
| P-7 | Tożsamość pacjenta-gościa w raporcie grantowym (e-mail? telefon? para?) | **raport grantowy (F5)** | bez tego liczba „osób objętych pomocą" będzie zawyżona — a to liczba do sprawozdania z dotacji |
| P-8 | Retencja i archiwizacja faktur po zakończeniu współpracy ze specjalistą | nie | „decyzja księgowa, nie programistyczna, i potrafi zablokować odbiór" (M2/15) |

**Co robimy do czasu odpowiedzi:** budujemy wszystko poza dziennikiem decyzji
(F5) i raportem grantowym (F5). Model danych z F1 nie jest przez te pytania
zablokowany — P-1 i P-7 dotyczą tabel, które powstają dopiero cztery fazy dalej.

---

## 12. Co z tej checklisty wchodzi do bramki F1

| Test | Co dowodzi |
|---|---|
| kolumny 🔒 są realnie zaszyfrowane w bazie (odczyt surowego wiersza **nie** pokazuje wartości) | W-1 |
| `zgoda` nie da się nadpisać — druga zgoda tej samej osoby to nowy wiersz | W-2 |
| rezerwacja po zmianie cennika **nadal** zna starą kwotę i starą regułę | W-3, W-4 |
| wszystkie kolumny czasowe to `timestamptz`, sesja bazy w UTC | W-8 |
| identyfikator rezerwacji nie jest kolejnym numerem | W-11 |
| migracje przechodzą **w górę i w dół** | bramka F1 |
| seed ma wiarygodne proporcje: 111 specjalistów, kilkanaście wizyt na pacjenta, limit **różnicuje** pacjentów | dziennik makiety, rozdz. 15 |

---

## 13. Historia dokumentu

| Data | Zmiana |
|---|---|
| 2026-08-07 | wersja pierwsza — sesja 1, przed pierwszą migracją modelu domenowego |


## W-13 — awaria retencji działa w OBIE strony

Lekcja zespołu helpdesku (07.08.2026). Retencja bywa pilnowana wyłącznie od
strony „nie trzymaj za długo". Tymczasem rekord, którego **żadne zadanie
czyszczące nie wybierze** — bo brakuje mu kolumny, po której retencja filtruje —
zostaje **na zawsze**. To także naruszenie art. 5 ust. 1 lit. e RODO, tylko
ciche: nic nie pada, nic nie alarmuje, dane po prostu leżą po terminie.

**Wymóg.** Dla każdej kategorii danych z retencją musi istnieć test, że rekord
**PO TERMINIE JEST WYBIERANY** przez swoje zadanie retencyjne — nie tylko że
świeży nie jest. Test tylko na „świeży nie znika" przechodzi również wtedy, gdy
zadanie nie wybiera **niczego**.

**Stan wdrożenia.** Dwie kontrole, celowo rozdzielone:

1. **Rejestr (`RetencjaTest`)** — tabela → kolumna pochodzenia → podstawa →
   sposób usunięcia. Pilnuje, że każda tabela ma decyzję o retencji, że kolumna
   pochodzenia istnieje i nie jest kolumną stanu, i że każdy wpis mówi, **jak**
   rekord znika.
2. **Wykonanie (`RetencjaWykonanieTest`)** — że rekord po terminie
   **FIZYCZNIE ZNIKA** z bazy.

## W-15 — selekcja to NIE wykonanie

Lekcja przekrojowa zespołu helpdesku (08.08.2026). Kontrole retencji
weryfikują zwykle, **kogo zadanie WYBIERA** do skasowania — i na tym
poprzestają. RODO wymaga jednak **WYKONANIA**, nie selekcji: poprawnie wybrany
rekord, którego nikt nie skasował, to dane pozostawione po terminie. Nic o tym
nie krzyczy, bo kontrola patrzy na listę, nie na bazę.

**Zmierzone u nas.** Perturbacja `retencja_wykonanie` usuwa z zadania samo
kasowanie, zostawiając selekcję nietkniętą. Strukturalny `RetencjaTest`
**pozostaje w całości zielony** — czerwień zapala wyłącznie kontrola
wykonania. To jest dowód, że potrzebne są obie, a nie jedna.

**Jak to jest zbudowane.** `ZadanieRetencji` zwraca liczbę WYBRANYCH i liczbę
FAKTYCZNIE USUNIĘTYCH osobno, a stan po kasowaniu sprawdza **niezależnym
zapytaniem** — nie ufamy wartości zwróconej przez `delete()`, bo ta mówi, na
ilu wierszach zadziałało polecenie, a nie jaki jest stan bazy po nim. Wyzwalacz,
reguła albo wycofana transakcja potrafią ją uczynić nieprawdziwą (reguła C1:
kontrola musi patrzeć inną drogą niż mechanizm, który bada).

**Nadal otwarte:** okresy retencji są wartościami zastępczymi do czasu
odpowiedzi IOD (P-3). Mechanizm powstał wcześniej celowo — żeby dało się go
SPRAWDZIĆ; liczby wchodzą, gdy przyjdą z zewnątrz. Zadania dla pozostałych
kategorii (pacjenci przez anonimizację, rezerwacje, zdarzenia) powstają w F2
na tym samym szkielecie i z tą samą parą kontroli.

## W-14 — retencja idzie za POCHODZENIEM rekordu, nie za jego stanem

Ta sama lekcja, druga część. Jeżeli termin usunięcia liczy się od kolumny
zmiennej w czasie życia rekordu (`status`, `updated_at`, przypisana kolejka),
to **przeniesienie albo eskalacja po cichu przesuwa okres przechowywania** —
bez decyzji, bez śladu i bez możliwości wykrycia po fakcie.

**Wymóg.** Podstawą liczenia terminu może być wyłącznie pole niezmienne po
utworzeniu rekordu (w praktyce: `created_at` albo data zdarzenia źródłowego).
Egzekwowane testem: `RetencjaTest` odrzuca rejestr, w którym kolumną
pochodzenia jest kolumna stanu.
