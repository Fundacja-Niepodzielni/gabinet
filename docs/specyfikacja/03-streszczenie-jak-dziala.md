Przeczytałem cały plik (1969 linii = 60 stron). Poniżej pełne streszczenie.

---

# „Jak działa system — Rezerwacja wizyt, Fundacja Niepodzielni" (07.08.2026, 60 s.)

**Charakter dokumentu:** opis funkcjonalny sporządzony **na podstawie działającej makiety** (s.1), świadomie bez terminów technicznych — „na to jest osobny dokument" (s.1). Struktura: 7 scenariuszy krok-po-kroku (s.3–17), trzy perspektywy ról z opisem ekranów (s.18–52), obszar powiadomień i płatności (s.53–60). Po każdej sekcji blok „Zasady obowiązujące w tym obszarze" z uzasadnieniem każdej reguły. **Prawa kolumna każdego kroku = „SYSTEM W TLE"** (co dzieje się automatycznie).

---

## 1. Scenariusze / przepływy

### 1.1 Pierwsza rezerwacja przez nową osobę (PACJENT, s.3–4)

| Krok użytkownika | System w tle |
|---|---|
| 1. Lista wolnych terminów — wszyscy specjaliści w jednym widoku, sortowanie „kto przyjmie najszybciej", cena i pierwsze wolne godziny na karcie. Filtry: rodzaj wizyty, online/stacjonarnie, „termin w ciągu 48 h", nazwisko/obszar/nurt | Nic nie wysyła. Kalendarz pokazuje **30 dni w przód**, nie pokazuje godzin wcześniejszych niż **2 h od teraz** |
| 2. Profil specjalisty (biogram, obszary, podejście, opinie, kalendarz 7 dni). Wybór rodzaju konsultacji, cena przelicza się bez przeładowania | Od kliknięcia w godzinę **termin trzymany 10 minut**, nikt inny go nie zarezerwuje |
| 3. Dane: imię i nazwisko, e-mail, telefon, „czy była już w fundacji", opcjonalnie kilka zdań o problemie | Zegar 10 min biegnie i jest widoczny w podsumowaniu po prawej |
| 4. Decyzja o koncie — domyślnie **gość**; opcja „Załóż mi konto przy tej rezerwacji" + hasło | **Konto powstaje dopiero po zaksięgowaniu płatności.** Dane do logowania idą **w tym samym mailu co potwierdzenie**, nie osobno |
| 5. Zgody: 2 wymagane (regulamin+polityka prywatności; przetwarzanie danych o zdrowiu), 1 nieobowiązkowa (warsztaty i grupy wsparcia). Osobno, **na żółtym tle**, potwierdzenie zasad odwołania z konkretną datą i godziną | Nic nie wysyła. Data graniczna wyliczana z terminu wizyty: **dokładnie 24 h wcześniej** |
| 6. Płatność — przycisk z kwotą wprost („Zapłać 145 zł i zarezerwuj"), **Stripe**: karta, BLIK, Google Pay, Apple Pay | Po zaksięgowaniu **jednocześnie**: mail „Potwierdzenie wizyty" (termin, nazwisko specjalisty, forma, miejsce/link, nr rezerwacji, data graniczna bezpłatnego odwołania) **oraz SMS** („termin zarezerwowany, szczegóły wysłaliśmy mailem, nr rezerwacji") — **bez nazwy usługi i bez słowa o zdrowiu** |
| 7. Potwierdzenie: nr rezerwacji, kwota, **link do spotkania od razu** lub adres z piętrem/gabinetem/domofonem, dodanie do kalendarza, propozycje 3 kolejnych terminów (+1, +2, +3 tygodnie) | Nic więcej nie idzie. Każdy kolejny termin = **osobna rezerwacja i osobna płatność** |
| 8. Przypomnienia | **24 h przed**: mail „Jutro o [godzina] — przypomnienie o wizycie" + SMS z terminem i datą graniczną bezpłatnego odwołania. **2 h przed (online)**: SMS z linkiem. **Po wizycie**: propozycja kolejnego terminu u tej samej osoby |

### 1.2 Odwołanie przez pacjenta (s.6–7)

Kluczowe: nad przyciskami stoi jedno z dwóch zdań — „Możesz jeszcze bezpłatnie zmienić lub odwołać — masz na to czas do [data], godz. [godzina]" **albo** „Minął czas na bezpłatną zmianę". „To zdanie decyduje o wszystkim, co dalej" (s.6).

**Rozgałęzienie A (>24 h):**
- Przyciski „Przełóż termin" i „Odwołaj wizytę" widoczne. Okno pokazuje: ile godzin zostało, zapłaconą kwotę, kwotę zwrotu, informację że termin natychmiast zwalnia się dla innych.
- Powód **nieobowiązkowy**, 4 opcje: nie podaję powodu / choroba / kolizja z obowiązkami / nie potrzebuję już wizyty. Pod listą wyjaśnienie, kto to zobaczy.
- **System w tle:** zwrot 100% → **lista zwrotów do wykonania u koordynatora** (nie automat), pieniądze na tę samą kartę, 3–5 dni roboczych. Termin natychmiast wraca do puli. **Godzina nie wchodzi do rozliczenia specjalisty.** Powód idzie do zbiorczych statystyk fundacji; specjalista widzi go tylko przy swojej wizycie.

**Rozgałęzienie B (<24 h):**
- Przyciski **znikają z ekranu — nie są wyszarzone** (s.6, s.7, s.22, s.49, s.55, s.58). Uzasadnienie powtarzane 5×: „wyszarzony przycisk kusi, żeby go klikać i szukać sposobu obejścia, a potem generuje zgłoszenia »nie działa mi«".
- Zostaje wyjaśnienie + jeden przycisk „**Napisz do specjalisty**". Na ekranie: „decyzję o wyjątku podejmuje osoba, nie system".
- **System w tle:** nic nie wysyła, nic nie zwraca. Godzina zostaje w rozliczeniu specjalisty.
- Jeśli specjalista zdecyduje o wyjątku — ma osobne pole przy oznaczaniu nieobecności („odpuść tym razem"), wtedy zwrot trafia na listę do wykonania.
- Jeśli pacjentka po prostu nie przyjdzie: specjalista zgłasza nieobecność, zapis w historii pacjentki, widoczne dla koordynatora, brak zwrotu, godzina wchodzi do rozliczenia, termin nie wraca do puli (bo minął).
- **Zwolniony termin idzie na listę rezerwową** — propozycja mailem **i** SMS-em jednocześnie, kolejnym osobom, aż ktoś przyjmie. Jeśli uda się obsadzić — **pierwsza pacjentka dostaje kredyt** na kolejną wizytę, mimo że jej wizyta była płatna („Fundacja nie zarabia dwa razy na tej samej godzinie", s.7).

### 1.3 Przełożenie terminu przez pacjenta (s.8–9)

- Przycisk widoczny tylko gdy **>24 h** i limit zmian niewyczerpany; stoi **przed** czerwonym przyciskiem odwołania.
- Okno: 7 najbliższych dni z liczbą wolnych godzin, dni bez terminów nieaktywne. Na górze: „zmienia tylko godzinę, specjalista, cena i forma zostają bez zmian".
- Na dole licznik: „**Zmiana 1 z 2 dozwolonych dla tej rezerwacji**" — widoczny **przed** kliknięciem (s.9: „ta sama informacja po wyczerpaniu limitu jest już tylko odmową").
- **System w tle po potwierdzeniu:** stary termin natychmiast wraca do puli; **płatność przechodzi na nowy termin — bez ponownego pobrania i bez zwrotu**; zaktualizowane potwierdzenie mailem; przypomnienia (24 h i 2 h) przeliczają się na nowy termin.
- Przy 3. próbie przycisk nieaktywny + komunikat „Tę wizytę przekładano już 2 razy z dozwolonych 2. Kolejna zmiana wymaga kontaktu ze specjalistą". Odwołanie nadal możliwe.
- **Zmiana specjalisty niemożliwa** w tym oknie — trzeba odwołać i zarezerwować nową (s.8, s.25).

### 1.4 Umówienie kolejnej wizyty przez specjalistę w gabinecie (PSYCHOLOG, s.10–11)

- Wejście: „Umów pacjenta" u góry listy albo „Kolejny termin" przy wierszu osoby, która właśnie jest na sesji (nazwisko wpisuje się samo). Okno da się otworzyć w trakcie sesji albo później tego samego dnia.
- Dane: imię, nazwisko, e-mail (na ten adres idzie link do płatności). Rodzaj wizyty z ceną i czasem trwania.
- Termin: z własnego grafiku (7 dni) **albo wpisany ręcznie poza grafikiem** — z ostrzeżeniem „nikt inny go nie zarezerwuje, ale też nikt nie przypomni, że ma wtedy pracować". Termin ręczny trafia do kalendarza i do rozliczenia tak samo jak każdy inny.
- „Zarezerwuj i wyślij link do płatności" → **System w tle:** mail „Do opłacenia: wizyta [data] o [godzina]" z nazwiskiem, terminem, kwotą i linkiem. W mailu data, do której termin jest trzymany — **2 dni od wysłania**. **Po otwarciu linku: 10 minut** na dokończenie płatności. Brak płatności w 2 dni → termin wraca do puli, specjalistka widzi to na liście. Po zapłaceniu — zwykłe potwierdzenie mailem i SMS-em.
- **Wariant: „Poproś koordynatora o zwolnienie z opłaty"** + krótkie uzasadnienie **wyłącznie finansowe** (pod polem stoi wprost, żeby nie wpisywać informacji o zdrowiu). Przycisk zmienia się na „Wyślij wniosek i zablokuj termin". **System:** mail do koordynatora (nazwisko pacjenta, termin, kwota, uzasadnienie, link do decyzji), **termin zablokowany do czasu decyzji**, pacjent nie dostaje w tym momencie żadnego linku ani informacji.
- Status „**czeka na płatność**" widoczny u specjalisty i w kaflu koordynatora.
- **Zasada naczelna:** „Specjalista nigdy nie pobiera pieniędzy" — rozmowa o pieniądzach nie wchodzi do gabinetu (s.11, s.30).

### 1.5 Odwołanie przez specjalistę + lista rezerwowa (s.12–13)

- Przy wizytach <24 h w kolumnie statusu dopisek „**pacjent nie może już odwołać**" — specjalista wie, że po drugiej stronie nie ma wyjścia awaryjnego.
- Okno mówi wprost: pacjent dostanie **pełny zwrot niezależnie od tego, ile zostało** do wizyty; ta godzina **nie wejdzie do rozliczenia**. **Brak jakiegokolwiek progu czasowego.**
- Powód z listy: choroba / sytuacja losowa / nakładający się obowiązek zawodowy / inny. **Powód widzi koordynator, nie pacjent.**
- Domyślnie zaznaczone: „Zaproponuj pacjentowi trzy najbliższe wolne terminy z mojego kalendarza" (można odznaczyć).
- **System w tle:** pacjent dostaje **jednocześnie mail** „Wizyta [data] niestety się nie odbędzie" (kwota zwrotu, informacja o **3 dniach roboczych**, link do wyboru nowego terminu, zdanie że może odpisać jeśli woli, żeby fundacja zaproponowała termin sama) **oraz SMS** z tą samą informacją w skrócie i linkiem. Zwrot 100% → lista zwrotów u koordynatora. Godzina wypada z rozliczenia. **Odwołanie dolicza się do licznika odwołań z ostatnich 30 dni.**
- **Alert po >10 odwołaniach w 30 dni** — widzi **wyłącznie koordynator**; specjalista nie dostaje żadnej wiadomości ani ostrzeżenia. „Nie jest to kara ani wpis do akt".

**Lista rezerwowa (mechanika, s.13, s.42, s.50):**
- Zwolniona godzina nie wraca „po prostu do kalendarza" — system proponuje ją osobom zapisanym w kolejce **u tej osoby i na ten rodzaj wizyty**, po kolei.
- Propozycja mailem **i** SMS-em jednocześnie. **Pierwsza osoba ma 4 godziny na odpowiedź**, **zegar zatrzymuje się między 21:00 a 8:00**. Po tym czasie propozycja przechodzi do następnej **automatycznie, bez udziału koordynatora**.
- Kolejka ułożona **według czasu oczekiwania** (najdłużej czekający pierwszy). Widać: ile razy ktoś dostał propozycję i jej nie przyjął (po dwóch — warto zapytać, czy nadal chce czekać) oraz **kto zgadza się na termin w ciągu 24 h**.
- Koordynator widzi: ile osób czeka, które terminy się zwolniły, ile godzin przed wizytą odwołano, wynik (obsadzony / propozycja wysłana / przepadł), ile pieniędzy odzyskano i ile przepadło. Przycisk „**Przekaż dalej**" — natychmiastowe przeniesienie propozycji do następnej osoby.

### 1.6 Zamknięcie miesiąca: oznaczanie → rozliczenie → faktura → akceptacja (s.14–15)

1. **Zakładka „Do oznaczenia"**: odbyła się / pacjent nie przyszedł. **Po 48 h system sam uznaje nieoznaczoną wizytę za odbytą** i wlicza do rozliczenia. **Nieobecność musi zgłosić specjalista osobiście** — bo to ona odbiera pacjentowi zwrot. Opcja „**odpuść tym razem**" → zamienia w odwołanie z pełnym zwrotem trafiającym na listę do wykonania.
2. **Ekran rozliczeń — 4 liczby**: kwota do wypłaty, godziny w gabinecie, godziny opłacone mimo braku pacjenta, data najbliższej wypłaty (**10 dnia miesiąca**). Dwie osobne listy: wizyty odbyte / godziny opłacone mimo braku pacjenta (późne odwołania i nieobecności).
3. **Ściągawka „kiedy godzina jest płatna"**: wizyta się odbyła — tak; pacjent odwołał późno — tak; pacjent nie przyszedł — tak; pacjent odwołał w terminie — nie; specjalista odwołał — nie. Rozbieżności zgłaszać **PRZED** wystawieniem faktury.
4. **„Prześlij fakturę"**: własny numer + plik **PDF do 10 MB**; bez pliku i numeru przycisk nie działa. Potwierdzenie przyjęcia mailem; faktura u koordynatora ze statusem „oczekuje".
5. **Koordynator**: przy każdej pozycji kwota z faktury, wyliczenie systemu i **różnica**. 4 kafle: czeka / zaakceptowano / do poprawy / rozjazd.
6. **Wariant zgodny**: zielone potwierdzenie → „Akceptuj do wypłaty" → przelew w najbliższej paczce, do 10 dnia, na konto z profilu; potwierdzenie księgowania mailem.
7. **Wariant z różnicą**: system podpowiada przyczynę (faktura niższa → pominięte godziny opłacone mimo nieobecności; wyższa → sprawdzić wizyty umówione telefonicznie lub poza grafikiem). „Poproś o korektę" + konkretny opis (bez opisu przycisk nie działa, s.57). Mail „Faktura [numer] — prośba o korektę" z treścią koordynatora, wyliczeniem systemu, kwotą z faktury i linkiem do przesłania poprawionego dokumentu. Status „**do poprawy**", nie „odrzucona". Wypłata przesuwa się na najbliższą paczkę po akceptacji.

### 1.7 Zgłoszenie pierwszego kontaktu i kwalifikacja (KOORDYNATOR, s.16–17, s.40)

- **Ankieta: 6 pytań, wszystkie z zamkniętą listą, zero pól opisowych.** Pyta o: obszar wsparcia (**najwyżej 2 z listy 12**, np. lęk, depresja, trauma, żałoba, wypalenie zawodowe), wcześniejsze doświadczenie terapii, formę spotkania i miasto, pilność, czy potrzebuje wizyty dofinansowanej, kanał kontaktu. **Nie ma pytania „opisz swój problem", nie ma pytań o diagnozy, leki ani hospitalizacje.**
- Zgłoszenie dostaje numer, trafia do kolejki. **Nic nie idzie automatycznie do żadnego specjalisty.**
- Kolejka sortowana **pilnością + czasem czekania**. 4 liczby: ile czeka, ile >24 h, ile „pilne", mediana oczekiwania. Nazwiska skrócone do inicjału, telefon zasłonięty kropkami.
- Po otwarciu: wszystkie 6 odpowiedzi + **3 propozycje specjalistów** z uzasadnieniem (wspólne obszary, miasto, data pierwszego wolnego terminu; jeśli >tydzień — wypisane przy nazwisku). **Żadna nie jest zaznaczona domyślnie.**
- Telefon zasłonięty do kliknięcia „Pokaż" — **odsłonięcie zapisuje się w logu dostępu** („ekran kwalifikacji bywa otwarty w biurze, przy którym przechodzą inni").
- Rodzaj wizyty startuje od tego, co osoba zaznaczyła. Zmiana niskopłatnej na pełnopłatną wbrew ankiecie → **ostrzeżenie z konkretną ceną**.
- Wybór **jednej godziny** spośród 4 na dzień, z 3 najbliższych dni. Podgląd dokładnie tego, co zobaczy osoba. **Jedna propozycja, nie lista.**
- **System w tle:** osoba dostaje propozycję z terminem i linkiem do opłacenia. **Termin zablokowany na 2 dni**, potem wraca do puli, a zgłoszenie wraca do kolejki. Zgłoszenie znika z listy i zamienia się w rezerwację. **Odpowiedzi z ankiety nie wędrują do kartoteki pacjenta.**
- Warianty: (a) brak dopasowania w zespole → „sytuacja do decyzji człowieka, nie do filtra": dopytać o formę, zaproponować grupę wsparcia albo skierować **poza fundację**; (b) nikt nie przyjmuje w tym mieście → ostrzeżenie, propozycje z innych miast, trzeba najpierw zapytać o online; (c) „**Poproś o rozmowę**" zamiast propozycji — zgłoszenie wraca do kolejki z adnotacją.

### 1.8 Grupy i warsztaty (s.20–21, s.25, s.31–32, s.43)

- Karty spotkań: data, nazwa, rodzaj, opis, prowadzący, kiedy/gdzie, cena, rytm (np. „8 spotkań, co tydzień"), pasek zajętości, liczba osób w kolejce.
- Zapis: okno z danymi, harmonogramem, kosztem i **zgodą na stały skład grupy**.
- Dwa ekrany potwierdzenia: „**Masz miejsce**" albo „**Jesteś na liście rezerwowej**" z pozycją w kolejce. Osoba z miejscem dostaje link/adres od razu; przy cyklu — wszystkie terminy naraz do pobrania do kalendarza.
- **Osoba na liście rezerwowej nie płaci nic z góry** — dopiero po zwolnieniu miejsca i potwierdzeniu.
- **Rezygnacja zawsze bezpłatna**, także z warsztatu płatnego, **najpóźniej 2 h przed** — wtedy też zamykają się zapisy. Wpłata wraca w całości.
- Zwolnione miejsce → **pierwsza osoba z listy dostaje maila i ma 24 h na potwierdzenie**, potem następna. Prowadzący nie musi nic robić (napisane wprost w oknie uczestników, s.32).
- Koordynator: tworzy spotkania (rodzaj, forma, nazwa, opis, data, godzina, czas trwania, liczba spotkań, rytm, limit, miejsce, cena), przypisuje prowadzących, przenosi z listy rezerwowej, **podnosi limit miejsc → osoby z listy awansują natychmiast i dostają maila**.
- **Specjalista nie zakłada grup** — zgłasza chęć („to prośba, nie rezerwacja", decyduje koordynator, odpowiedź mailem). Widzi imiona i nazwiska uczestników, **nie widzi kto i ile zapłacił**.
- Wynagrodzenie: **180 zł za 90-minutowe spotkanie, niezależnie od frekwencji** (4 czy 12 osób) — ryzyko pustej sali bierze fundacja (s.37).

### 1.9 Interwencje koordynatora w grafiku zespołu (s.39)

Koordynator może: odwołać wizytę (**pacjent dostaje pełny zwrot niezależnie od czasu**), przenieść wizytę na inny termin, **przypisać innego specjalistę**, **„zwróć mimo reguły" 24 h**, umówić wizytę za osobę dzwoniącą — z wyborem: link do płatności / opłacona przelewem / **bezpłatna**. Wizyta umówiona telefonicznie podlega tej samej regule 24 h; odstępstwo wymaga świadomego kliknięcia.

### 1.10 Wdrożenie nowego specjalisty (s.43, s.51)

**7 kroków**: zaproszenie → konto → dane i umowa → konto bankowe → uprawnienia do usług → pierwszy grafik → pierwsza wizyta.
- Najważniejsza kolumna to **nie postęp, tylko „przyjmuje pacjentów"** (6/7 kroków może nie wystarczyć, 5/7 może wystarczyć).
- **5 z 7 kroków wstrzymuje przyjmowanie pacjentów; brak numeru konta wstrzymuje wyłącznie wypłatę; pierwsza wizyta nie blokuje niczego.**
- Brak podpisanej umowy zatrzymuje wszystko („fundacja nie ma podstawy do powierzenia komuś danych osoby w kryzysie").
- Uprawnienie do diagnozy ADHD idzie osobno (wymaga sprawdzenia dyplomu) i nie wstrzymuje pozostałych usług.
- **Sprawa bez ruchu >14 dni → lista do interwencji.** Jedno przypomnienie zbiera wszystkie braki naraz. **Jedyną akcją wobec specjalisty jest przypomnienie — nigdy automatyczne zamknięcie sprawy.**
- Konto specjalisty powstaje **po ustawieniu przez niego hasła** (s.42).

---

## 2. Model biznesowy

### Cennik (s.3, s.36, s.58) — 4 usługi w katalogu
| Usługa | Cena dla pacjenta | Prowizja fundacji |
|---|---|---|
| Konsultacja **niskopłatna** | **55 zł** | **0%** (fundacja dopłaca z dotacji) |
| Konsultacja **pełnopłatna** | **115 / 125 / 135 / 145 zł** — „Innych kwot w systemie nie ma" (s.36) | **20%** |
| **Asystent zdrowienia** | **bezpłatnie** | 0% |
| **Diagnoza ADHD u dorosłych** | **350 zł** | (niewymieniona wprost) |
| Spotkanie grupowe 90 min | cena od osoby ustalana przez koordynatora | prowadzący dostaje **180 zł**, niezależnie od frekwencji |

- **Cennik ustala fundacja**, jest ten sam dla wszystkich. Jedyna cena, o której decyduje specjalista, to **stawka pełnopłatna wybrana z 4 widełek** (s.30, s.36).
- **2 z 4 usług — diagnoza ADHD i asystent zdrowienia — wymagają uprawnienia nadanego przez koordynatora**; specjalista nie włączy ich sam, może poprosić jednym kliknięciem (s.36).
- Katalog usług (s.46): koordynator dodaje usługi, zmienia nazwę, czas trwania, model ceny, dozwolone stawki, decyduje czy usługa wymaga uprawnienia, czy fundacja pobiera prowizję i czy jest widoczna w wyszukiwarce.

### Zasady odwołań — okna i zwroty
| Sytuacja | Zwrot | Termin wraca do puli | Wynagrodzenie specjalisty |
|---|---|---|---|
| Pacjent odwołuje **>24 h** przed | **100%** | tak, natychmiast | **nie** |
| Pacjent odwołuje **<24 h** przed | **0%** | **tak** (mimo braku zwrotu) | **tak** |
| Pacjent nie przychodzi | **0%** | **nie** (termin minął) | **tak** |
| **Specjalista odwołuje** (o dowolnej porze, także godzinę przed) | **100%** | tak | **nie** |
| Koordynator odwołuje | **100%** niezależnie od czasu | tak | — |
| „Odpuść tym razem" przy nieobecności | 100% | — | — |
| Rezygnacja z grupy/warsztatu **>2 h** przed | **100%**, zawsze, także warsztat płatny | miejsce → 1. osoba z listy | — |

- **Brak progów pośrednich** i opłat częściowych: „każdy dodatkowy próg to kolejne pytanie do koordynatora" (s.7).
- **Kredyt za odsprzedany termin**: jeśli po późnym odwołaniu ktoś inny wykupi zwolnioną godzinę, pierwsza osoba dostaje równowartość jako **kredyt na kolejną wizytę** (s.7, s.24, s.49, s.58). Różnicę pokrywa fundacja. Reguła **włączalna/wyłączalna** w ekranie reguł (s.48, s.54).
- Dokument wskazuje na **8-sytuacyjną tabelę polityki odwołań** jako jedyne miejsce decydujące o zwrocie (s.48, s.54). Wyjątki robi człowiek przyciskiem „**zwróć mimo reguły**" i zostawia ślad w dzienniku decyzji.

### Limity
- **10 wizyt niskopłatnych na osobę** (s.5, s.24, s.36, s.49). Stan puli pokazywany **PRZED płatnością**, nie po (s.5, s.19, s.54).
- **Koordynator podnosi limit skokiem o 4 wizyty**; pacjent dostaje maila; decyzja zostaje w historii pacjenta z nazwiskiem osoby i uzasadnieniem (s.41, s.49). Bez zaświadczeń, odpowiedź zwykle tego samego dnia (s.5, s.60).
- **Kontynuacja kategorii**: kto zaczął niskopłatnie, kontynuuje niskopłatnie u tego samego albo innego specjalisty, dopóki ma pulę. Pacjent może wybrać wizytę pełnopłatną i wtedy pula zostaje nienaruszona (s.5, s.25, s.50).
- **4 terminy niskopłatne tygodniowo na specjalistę**, pula **odnawia się w każdy poniedziałek**, godziny pełnopłatne bez ograniczeń. **Blokada działa przy układaniu grafiku, nie przy rezerwacji** (s.25, s.35, s.50, s.60).
- **Liczymy wizyty, nie minuty**: „3 z 10 wizyt", nie „pozostało 1 godzina 40 minut" (s.24, s.36).
- **10 minut przerwy między wizytami** — twarda reguła systemowa (s.25, s.35, s.50).
- **Brak pakietów i serii wizyt** — każda wizyta to osobna rezerwacja i osobna płatność (s.5, s.20, s.55).

### Zamrażanie cen/reguł (kluczowe architektonicznie)
- „**Zmiana reguły nigdy nie działa wstecz** — wizyty już opłacone zachowują zasady z dnia zakupu, bo pacjent zgodził się na konkretne warunki" (s.48).
- „**Zmiana ceny nigdy nie dotyka wizyt już opłaconych: rezerwacja pamięta kwotę z dnia zakupu**, więc zwrot zawsze równa się temu, co pacjent naprawdę zapłacił. Bez tego podniesienie cennika o 10 zł sprawiłoby, że stare zwroty przestałyby się zgadzać z operatorem płatności" (s.46).
- „Zmiana reguł — na przykład **przesunięcie okna z 24 na 48 godzin** — obowiązuje wyłącznie nowe rezerwacje" (s.51, s.54, s.59).
- Zapis reguł: „obowiązują od nowych rezerwacji" (s.48).

### Model finansowy fundacji
- Wypłaty: **zestawienie do 5 dnia, uwagi do 8 dnia, przelew do 10 dnia**; faktura do 10 dnia za miesiąc poprzedni (s.33, s.36).
- Specjalista **nie widzi prowizji ani kwoty zapłaconej przez pacjenta** — tylko własną stawkę (s.15, s.32, s.36, s.42, s.56, s.59). Pełne rozliczenie (wpływ, prowizja, marża) widzi wyłącznie koordynator — jedyne miejsce: ekran „Psycholodzy" (s.42).
- „Płatność z góry": „Opłacona wizyta jest wizytą, która się odbywa — przy płatności na miejscu odsetek nieobecności rośnie kilkukrotnie" (s.58).

---

## 3. Role i perspektywy

### PACJENT (s.18–26)
**Ekrany:** strona startowa (najbliższy wolny termin w całej fundacji **nad** cennikiem) · wyszukiwarka terminów (wszyscy specjaliści, do 5 konkretnych godzin na karcie, filtry, pasek „kto mnie prowadzi") · profil specjalisty (3 zakładki: O mnie / Obszary pomocy / Podejście terapeutyczne; 7 dni; opinie dostępne dopiero po odbytej wizycie) · rezerwacja (dane+zgody+płatność) · potwierdzenie · warsztaty i grupy (lista + spotkanie + potwierdzenie zapisu) · logowanie · **pulpit** · moje wizyty (Nadchodzące / Historia) · grupy w koncie · wiadomości · moje dane.

**Pulpit pacjenta** (s.21): 4 liczby (za ile najbliższa wizyta, ile czeka, ile się odbyło, **ile wizyt niskopłatnych zostało z puli + pasek**), ramka z psychologiem prowadzącym, karta najbliższej wizyty z linkiem/adresem i datą graniczną, grupy, zestawienie „**Zasady, które Cię dotyczą**" z pięcioma konkretnymi liczbami.

**Czego pacjent NIE widzi:** żadnych sum wydanych na wizyty ani historii finansowej — tylko cenę pojedynczej wizyty przed zapłatą (s.21, s.26, s.60: „Zestawienie »wydałaś dotąd 1400 zł na terapię« nie pomaga nikomu w leczeniu").

**Psycholog prowadzący**: przypisuje się **automatycznie po pierwszej odbytej wizycie**, nikt nie ustawia ręcznie, **nie ma ekranu „przypisz psychologa"** (s.41). Wizyta u kogoś innego dozwolona i nie zmienia przypisania — komunikat o tym stoi na profilu, „ostatni ekran przed płatnością" (s.19). Trwałą zmianę potwierdza koordynator.

### PSYCHOLOG / SPECJALISTA (s.27–37)
**Menu w 3 grupach** (bo „specjalista wchodzi z jednym z trzech pytań: co mam dzisiaj, kiedy przyjmuję, ile zarobiłam"):
- **Praca**: pulpit, wizyty, grupy i warsztaty, wiadomości
- **Kalendarz**: moje kalendarze, dostępność
- **Pieniądze**: rozliczenia, dokumenty i wypłaty

**Pulpit** (s.27): 4 liczby (wizyty dziś / ile jeszcze przed nią, wolne godziny w tygodniu, % obłożenia, wynagrodzenie z 30 dni), lista dzisiejszych wizyt, kolumna „Wymaga uwagi", słupki na 7 dni (ciemny = umówione, jasny = wystawione niezajęte). Przycisk dołączenia do spotkania online **pojawia się godzinę przed wizytą**. Pokazuje **tylko imię pacjenta, rodzaj wizyty i formę — żadnych notatek ani historii** (ekran bywa otwarty przy kolejnej osobie w gabinecie).

**Wizyty** — 3 zakładki: Nadchodzące (3 tygodnie) / **Do oznaczenia** (z licznikiem) / Historia.

**Okna:** szczegóły wizyty (można **zmienić gabinet lub wpisać własny link**; przycisk „**Zapisz i powiadom pacjenta**", zmiana idzie mailem i SMS-em) · odwołuję wizytę · pacjent się nie pojawił (z „odpuść tym razem") · umów pacjenta na kolejny termin.

**Moje kalendarze** (s.30): usługi w 3 grupach — przyjmowane / do włączenia / **wymagające zgody fundacji**. Wyłączenie usługi **zatrzymuje tylko nowe rezerwacje** — umówione wizyty zostają.

**Dostępność** (s.31): **osobne zakładki dla pełnopłatnych i niskopłatnych** (przy niskopłatnych pasek „Twoja pula na ten tydzień — 3 z 4"). **Dwa poziomy**: (1) tygodniowy rytm — 7 dni z przełącznikami i zakresami, licznik terminów tygodniowo; (2) siatka 7 dni na godziny w **4 kolorach**: z rytmu / dodane ręcznie / wyłączone / zajęte przez pacjenta. Poprawki na siatce są **jednorazowe** (dotyczą jednego dnia). **Godziny zajętej przez pacjenta nie da się kliknąć.** Tabela wolnego i urlopów + spis reguł fundacji + miejsce na połączenie z prywatnym kalendarzem.

**Urlop**: „Wpisane wolne wyłącza terminy **we wszystkich usługach naraz** i ma pierwszeństwo zarówno przed tygodniowym rytmem, jak i przed pojedynczymi poprawkami w siatce" + system pokazuje, ile umówionych wizyt trzeba przełożyć (s.36).

**Dokumenty i wypłaty** (s.33): umowa o współpracy z datą, formą i aneksami; tabela stawek (pełnopłatna / niskopłatna / grupa) z adnotacją że to kwoty do wypłaty; numer konta „zweryfikowany"; **zmiana numeru konta wymaga kliknięcia w link z maila — do tego czasu przelew idzie na stary rachunek** (zabezpieczenie przed przejęciem konta przed wypłatą, s.33, s.36–37).

**Czego specjalista NIE widzi:** grafiku całego zespołu, finansów fundacji, prowizji, kwoty zapłaconej przez pacjenta, kto ile zapłacił za grupę.

### KOORDYNATOR (s.38–52)
**Belka rodzaju wizyt** nad każdym ekranem — pasek na całą szerokość, wielkimi literami: „łącznie / tylko niskopłatne / tylko pełnopłatne". Domyślnie „łącznie". Zawężenie **działa jako pierwsze, przed pozostałymi filtrami**. Zmienia kolor i zostaje na ekranie. **Jeden ekran świadomie ignoruje belkę — raport dla grantodawcy** (sprawozdanie musi obejmować całą działalność).

**Ekrany:** Pulpit · Grafik zespołu · Zgłoszenia · Rezerwacje · Pacjenci · Lista rezerwowa · Psycholodzy · Wdrożenia · Grupy i warsztaty · Wiadomości · Faktury specjalistów · **Historia decyzji** · Raport dla grantodawcy · Katalog usług · Powiadomienia SMS · Szablony maili · Zakres wdrożenia · **Reguły systemu**.

- **Pulpit** (s.38): 6 liczb z 4 tygodni (rezerwacje, przychód, obłożenie, % odwołań, % nieobecności, kwota zwrotów). **Zakres dat osobno w każdej sekcji** (4/12/26 tyg. albo rok), z ostrzeżeniem gdy sekcje raportu mają różne okresy. **Wizyty i złotówki zawsze na dwóch osobnych wykresach** (inna skala, sugerowałyby zależność której nie ma). Najważniejsza pojedyncza liczba: **udział odwołań po terminie w ogóle odwołań** — jeśli rośnie, problem jest w komunikacji, nie w regulaminie.
- **Grafik zespołu** (s.39): 3 ujęcia (dzień całego zespołu — mieści 8 z **111 osób**; tydzień jednej osoby; miesiąc całego zespołu **bez nazwisk**). Dzień z **>85% wykupionych godzin zapala się na żółto**. 4 liczby na dole **zawsze liczą cały zespół, nie widoczną stronę**.
- **Rezerwacje** (s.41, s.56): pod każdą kwotą dopisek „**zwrot do wykonania**" / „**bez zwrotu**" / „**nieopłacone**". Pełna historia rezerwacji: kto założył, kiedy przyszła płatność i z jakiej karty, kiedy poszło potwierdzenie i przypomnienie, kto i kiedy odwołał, ile trzeba zwrócić. **Historia pokazuje wyłącznie to, co się już wydarzyło** — nie ma przyszłych przypomnień ani płatności przy nieopłaconej rezerwacji. Można **wysłać potwierdzenie jeszcze raz**. Tabela pokazuje **60 pozycji** i wprost pisze ile zostało.
- **Pacjenci** (s.41): kartoteka osób z wizytami w **ostatnich 120 dniach**; pasek żółknie po wyczerpaniu limitu; osobna kolumna **wizyt bezpłatnych**. Lista pokazuje **25 pozycji**.
- **Psycholodzy** (s.42): jedyne miejsce z **pełnym rozliczeniem** (wpływy + prowizja). Ostrzeżenie przy >10 odwołaniach w 30 dni; można napisać do osoby albo **wyciszyć sygnał na 30 dni**. Listy: kto ma kalendarz otwarty **krócej niż 21 dni**, które usługi wymagają zgody. Akcje: zaproszenie do zespołu, włączanie/wyłączanie usług, **nadpisanie stawki pełnopłatnej**, zawieszenie konta, **prośba** (nie polecenie) o zwolnienie terminów. „Odebranie usługi nie kasuje już umówionych wizyt".
- **Historia decyzji** (s.45, s.51): dziennik decyzji **poza regułami systemu**: wizyta bezpłatna, podniesienie limitu, zwrot mimo 24 h, ręczna zmiana prowadzącego, zdjęcie blokady. Przy każdym wpisie: data, kogo dotyczy, przy której rezerwacji, uzasadnienie, nazwisko decydenta, skutek dla budżetu. **Dziennik ma dokładnie jedną operację: dopisanie. Wpisu nie da się zmienić ani usunąć — nie ma przycisku „edytuj" ani „usuń", także w oknie szczegółów.** Poprawka = nowy wpis z dzisiejszą datą. **Dwie osobne kwoty, nigdy sumowane: pieniądze już wydane i zobowiązania** (podniesione limity kosztują dopiero przy wykorzystaniu).
- **Raport dla grantodawcy** (s.45, s.51): **główną liczbą jest osoba, a nie wizyta**. Wyłącznie **zamknięty kwartał albo rok** (bieżący kwartał niedostępny). „**Osób z 4 kwartałów nie wolno dodawać do roku — wizyty wolno dodawać, osób nie**". „Objęta pomocą" = **co najmniej jedna odbyta wizyta**. Bez nazwisk, bez powodów zgłoszenia, **bez informacji o efektach terapii** („system wie, ile spotkań się odbyło, i nie wie, co dały"). Tabela wskaźników z kolumną „jak dokładnie jest liczony".
- **Reguły systemu** (s.48, s.54): centralne miejsce konfiguracji — czasy (okno bezpłatnego odwołania, limit przełożeń, najbliższy możliwy termin, dni otwarcia kalendarza, przerwa między wizytami, czas trzymania terminu), **tabela 8 sytuacji** (zwrot / termin / wynagrodzenie), pieniądze (prowizja pełnopłatna i niskopłatna, częstotliwość wypłat, **kredyt za odsprzedany termin on/off**), lista **7 powiadomień z dwoma przełącznikami każde: mail i SMS**.

**Czego koordynator NIE widzi:** treści rozmów i notatek z sesji (s.17, s.41: „Koordynator widzi organizację wizyty i pieniądze, nie to, o czym ona była").

---

## 4. Płatności

- **Operator: Stripe** (s.4 — jedyne wystąpienie nazwy w dokumencie). Metody: **karta, BLIK, Google Pay, Apple Pay** (s.4, s.54). „Fundacja nie przechowuje danych karty" (s.4, s.23, s.54); w „Moje dane" tylko **4 ostatnie cyfry i data ważności** (s.23), na potwierdzeniu — 4 ostatnie cyfry (s.55).
- **Kiedy pieniądze zmieniają właściciela:** płatność z góry, pełna kwota, **przed wizytą**. Konto pacjenta powstaje dopiero **po zaksięgowaniu płatności** (s.3). Mail i SMS potwierdzające idą **po zaksięgowaniu** (s.4).
- **Blokady terminu podczas płatności:**
  - rezerwacja na stronie: **10 minut**
  - termin umówiony przez specjalistę: **2 dni** na opłacenie z linku mailowego, **+ 10 minut od otwarcia linku** na dokończenie transakcji
  - propozycja koordynatora po kwalifikacji: **2 dni**
  - wniosek o zwolnienie z opłaty: **termin zablokowany do czasu decyzji koordynatora**
- **Brak płatności:** termin wraca do puli wolnych; specjalista widzi to na liście (rezerwacja znika z kalendarza jako zajęta); przy propozycji koordynatora **zgłoszenie wraca do kolejki kwalifikacji**; status „**czeka na płatność**"/„nieopłacone" widoczny u specjalisty i koordynatora.
- **Zwroty — ręczne, świadoma decyzja architektoniczna** (s.7, s.26, s.50, s.58):
  - system **generuje wyłącznie listę „zwrot do wykonania"**, nigdy nie pisze „zwrot wykonany"
  - klika je człowiek w panelu operatora płatności
  - uzasadnienie: „automatyczne zwroty wymagałyby ciągłego uzgadniania stanu między systemem a operatorem płatności oraz obsługi zwrotów nieudanych i częściowych — czyli **najdroższego kawałka całej integracji**. Przy kilkudziesięciu zwrotach miesięcznie ręczne kliknięcie jest tańsze" (s.50)
  - pieniądze wracają **na tę samą kartę**, zwykle **3–5 dni roboczych**
- **Przełożenie terminu: płatność przechodzi na nowy termin — bez zwrotu i bez ponownego pobrania** (s.8, s.9, s.55). Uzasadnienie: zwrot + nowa płatność = kilka dni oczekiwania i sztucznie napompowana lista zwrotów.
- **Kredyt** zamiast zwrotu — gdy późno odwołany termin zostanie odsprzedany (s.7, s.24, s.49, s.58).
- **Wypłaty dla specjalistów**: paczki przelewów, **do 10 dnia miesiąca**, na konto z profilu; potwierdzenie księgowania mailem. Faktura z własnej księgowości specjalisty (system tylko podpowiada kwotę). **Dokument sam sygnalizuje problem: „faktura złożona ostatniego dnia nie ma kiedy zostać sprawdzona — warto rozsunąć te dwie daty"** (s.59–60).
- **Uwaga:** wizyty umówione przez koordynatora mogą być **opłacone przelewem albo bezpłatne** — poza Stripe (s.39).

---

## 5. Powiadomienia

**Skala:** **7 szablonów SMS** i **7 szablonów maili** (s.53).

### Maile (wyzwalacze)
| Mail | Wyzwalacz | Odbiorca |
|---|---|---|
| „Potwierdzenie wizyty" (termin, specjalista, forma, miejsce/link, nr rezerwacji, data graniczna odwołania) + **dane do logowania w tym samym mailu** | zaksięgowanie płatności | pacjent |
| „Jutro o [godzina] — przypomnienie o wizycie" | **24 h przed wizytą** | pacjent |
| Propozycja kolejnego terminu | po wizycie | pacjent |
| „Do opłacenia: wizyta [data] o [godzina]" + link do płatności + data ważności (2 dni) | specjalista umówił w gabinecie | pacjent |
| Wniosek o zwolnienie z opłaty (nazwisko, termin, kwota, uzasadnienie, link do decyzji) | specjalista złożył wniosek | koordynator |
| „Wizyta [data] niestety się nie odbędzie" (kwota zwrotu, **3 dni robocze**, link do wyboru terminu) | odwołanie przez specjalistę | pacjent |
| Zaktualizowane potwierdzenie z nową datą | przełożenie terminu | pacjent |
| Propozycja z listy rezerwowej (mail + SMS jednocześnie) | zwolnienie terminu | pacjent z kolejki |
| Propozycja terminu po kwalifikacji + link do opłacenia | koordynator wysłał propozycję | osoba zgłaszająca |
| Potwierdzenie przyjęcia faktury | wysłanie faktury | specjalista |
| „Faktura [numer] — prośba o korektę" (treść koordynatora, wyliczenie systemu, kwota z faktury, link) | koordynator prosi o korektę | specjalista |
| Potwierdzenie księgowania przelewu | akceptacja + przelew | specjalista |
| Informacja o podniesieniu limitu wizyt niskopłatnych | decyzja koordynatora | pacjent |
| Zwolnienie miejsca w grupie — **24 h na potwierdzenie** | rezygnacja / podniesienie limitu miejsc | 1. osoba z listy rezerwowej |
| Powiadomienie o nowej wiadomości w module Wiadomości | wysłanie wiadomości | odbiorca |
| Link potwierdzający zmianę numeru konta | zmiana rachunku przez specjalistę | specjalista |
| Przypomnienie o brakach we wdrożeniu (zbiorcze) | koordynator wysyła | specjalista |
| Odpowiedź na zgłoszenie chęci prowadzenia grupy | decyzja koordynatora | specjalista |
| Link do ustawienia nowego hasła / kod logowania | żądanie użytkownika | wszyscy |

### SMS-y
| SMS | Wyzwalacz |
|---|---|
| „termin zarezerwowany, szczegóły wysłaliśmy mailem, nr rezerwacji" | zaksięgowanie płatności (s.4) |
| Przypomnienie z terminem + data graniczna bezpłatnego odwołania | 24 h przed (s.4) |
| **SMS z samym linkiem do rozmowy** | **2 h przed wizytą online** (s.4) |
| Skrócona informacja o odwołaniu przez specjalistę + link | odwołanie przez specjalistę (s.12) |
| Propozycja zwolnionego terminu (razem z mailem) | lista rezerwowa (s.7, s.13) |
| Zmiana miejsca spotkania / linku | „Zapisz i powiadom pacjenta" (s.28) |

**Kategorie SMS w panelu** (s.53): przypomnienia, potwierdzenia, zmiany i odwołania, obsługa konta.
**Kategorie maili** (s.53): rezerwacja i płatność, przypomnienia, zmiany i odwołania, konto i limity, rozliczenia.

### Zasady prywatności i redakcji treści
- **W SMS-ach nie ma ani słowa o zdrowiu, nazwie usługi ani specjalizacji. Nadawcą jest „Niepodzielni", nie nazwa poradni** (s.5, s.46, s.59). Uzasadnienie: „wiadomość wyświetla się na zablokowanym ekranie, często przy innych ludziach. Dla części pacjentów sam fakt korzystania z pomocy jest informacją, której nie chcą pokazywać rodzinie ani współpracownikom".
- **Numery telefonów w logu wysyłek przycięte do 3 ostatnich cyfr** (s.53).
- **2 powiadomienia nieusuwalne — bez przełącznika**: **potwierdzenie rezerwacji** i **potwierdzenie odwołania ze zwrotem**. „To jedyny dowód, jaki pacjent ma na to, że wizyta istnieje i że pieniądze wracają" (s.54, s.59).
- **Kto może edytować:** **maile zmienia koordynator sam i od razu; treści SMS zmienia wykonawca** (s.47, s.53, s.59). Powód jest kosztowy, nie ostrożnościowy.
- **Walidacja placeholderów**: edytor sprawdza wstawiane wartości i **nie pozwala zapisać treści z nazwą, której system nie umie podstawić** (ryzyko wysłania 300 osobom maila z „{imie}") (s.47, s.53).
- **Świadomie brak edytora WYSIWYG** (kolory, obrazki, pogrubienia): maile transakcyjne mają dotrzeć i dać się przeczytać na starym telefonie; grafika ląduje w spamie albo się rozjeżdża (s.47, s.53).
- **Ekonomia SMS** (s.46, s.59): 160 znaków w alfabecie łacińskim vs **70 znaków z polskimi ogonkami**; każda część to **osobna opłata ok. 8 groszy**; ogonki = **136 zł miesięcznie, czyli 46% rachunku**. **3 szablony przekraczają limit o kilkanaście znaków.** Rekomendacja: **nie pisać bez ogonków** (wygląda jak spam od fundacji pomocowej), tylko skracać treść.
- **Zaplanowana synchronizacja przypomnienia z oknem odwołania**: „Przypomnienie 24 h przed wizytą jest nieprzypadkowe: wypada dokładnie w momencie zamykania okna bezpłatnej rezygnacji... Bez tego maila reguła 24 godzin generuje same skargi" (s.48, s.59).
- **Data graniczna bezpłatnego odwołania jest wypisana w 3 miejscach**: potwierdzenie rezerwacji, przypomnienie dzień wcześniej, karta wizyty w panelu (s.7).
- **Link do spotkania w 3 miejscach**: potwierdzenie na ekranie, mail, karta wizyty w panelu (s.25) — plus SMS 2 h przed.
- Możliwość **wysłania wiadomości testowej** (SMS na wskazany numer, mail na własny adres) i **przywrócenia treści domyślnej** maila.

---

## 6. Konta pacjentów

- **Konto nie jest wymagane do rezerwacji.** Domyślnie rezerwacja **jako gość**, nic nie trzeba wybierać (s.3, s.25).
- Opcja przy rezerwacji: „Załóż mi konto przy tej rezerwacji" + ustawienie hasła → wtedy pacjentka sama zmienia terminy, zapisuje się na grupy i widzi historię (s.3).
- **Konto powstaje w tle także bez tej opcji — dopiero po zaksięgowaniu płatności.** Dostęp linkiem z maila (s.3, s.25).
- **Dane do logowania idą w tym samym mailu co potwierdzenie, nie osobno** (s.3).
- **Logowanie linkiem/kodem bez hasła**: „W gotowym systemie prawie nikt tego ekranu [logowania] nie zobaczy. Pacjentka wchodzi do swojego panelu **linkiem z maila, bez hasła**" (s.21). **Kod do logowania bez hasła jest ważny 10 minut** (s.59).
- Ekran logowania służy: osobom wracającym po miesiącach, które nie mają już tamtej wiadomości, oraz **specjalistom i koordynatorom, gdzie hasło jest konieczne** (s.21).
- Uzasadnienie: „Osoba w kryzysie nie powinna wymyślać hasła, zanim umówi wizytę"; „Wymuszanie rejestracji przed pierwszą wizytą odsiewa część osób na progu, a fundacji nie daje nic, czego nie da adres e-mail" (s.5, s.25).
- **Konto specjalisty** powstaje **po ustawieniu przez niego hasła** z zaproszenia koordynatora (s.42).
- **Moje dane** (s.23): imię, e-mail (z wyjaśnieniem po co), telefon, **strefa czasowa**; sposób płatności (4 ostatnie cyfry karty + data ważności); **3 zgody, jedna zablokowana**; **prawo do pobrania wszystkich danych i do usunięcia konta**.

---

## 7. Odwołania do istniejących systemów fundacji

**To najuboższy obszar dokumentu — nie ma w nim ANI JEDNEJ wzmianki o WordPressie, Niepodzielni-dev, Bookero, SSO/Kontach ani aplikacji fundacji.** Sprawdziłem to wyszukiwaniem po całym pliku. Istnieją dokładnie **trzy** odniesienia do stanu obecnego, wszystkie pośrednie:

1. **Dwa konta specjalisty w obecnym systemie** (s.30, w bloku „Moje kalendarze"):
   > „W dzisiejszym systemie „niskopłatne" i „pełnopłatne" to dwa oddzielne konta tej samej osoby; tutaj to jedno konto z dwoma kalendarzami — rozdzielone są godziny, nie tożsamość specjalisty."

2. **Migracja danych świadomie poza zakresem** (s.47, blok „Zakres wdrożenia"):
   > „Nie ma tu też przenoszenia danych z obecnych narzędzi fundacji — zakres tej pracy zależy od tego, co dokładnie trzeba przenieść, a tego jeszcze nie ustaliliśmy."

3. **Prywatny kalendarz Google — poza makietą** (s.31, „Dostępność"):
   > „Podłączyć prywatny kalendarz Google (poza makietą, do decyzji)"

Jedyne nazwane systemy zewnętrzne: **Stripe** (s.4), **Google Pay / Apple Pay / BLIK** (s.4, s.54), nienazwany **operator SMS** („umowa z operatorem", s.46), nienazwany **panel operatora płatności** do zwrotów (s.50, s.56).

**Konsekwencja dla decyzji architektonicznej:** dokument opisuje system jako **samodzielny, zamknięty produkt** (własne konta, własne logowanie hasłem/linkiem, własne szablony maili i SMS, własny katalog usług, własny cennik, własny dziennik decyzji). Nie zakłada nigdzie SSO, integracji z WordPressem, ani współistnienia z Bookero. Zespół to **111 specjalistów** (s.16, s.39, s.40) — to jedyna liczba mówiąca o skali istniejącej organizacji.

---

## 8. Reguły twarde

### Czas i blokady
| Reguła | Wartość | Strona |
|---|---|---|
| Najbliższy możliwy termin | **2 h od teraz** | 5, 24, 35, 49 |
| Kalendarz dla pacjenta otwarty | **30 dni w przód** | 5, 25, 35, 49 |
| Specjalista wystawia godziny najwyżej | **7 dni w przód** (panel blokuje dalsze daty) | 5, 11, 25, 35, 49 |
| Blokada terminu przy rezerwacji na stronie | **10 minut** | 3, 25, 50, 58 |
| Blokada terminu przy linku od specjalisty/koordynatora | **2 dni** (+10 min od otwarcia linku) | 10, 11, 16, 36, 50 |
| Bezpłatne odwołanie i zmiana | **do 24 h przed wizytą** | wszędzie |
| Limit przełożeń jednej rezerwacji | **2 razy** | 8, 9, 24, 35, 49, 58 |
| Przerwa między wizytami | **10 minut**, zawsze | 25, 35, 50 |
| Oznaczenie wizyty przez specjalistę | **48 h**, potem auto-„odbyła się" | 14, 15, 35 |
| Ważność propozycji z listy rezerwowej | **4 h, zegar stoi 21:00–8:00** | 13, 42, 50 |
| Ważność propozycji miejsca w grupie | **24 h** | 25 |
| Rezygnacja z grupy / zamknięcie zapisów | **2 h przed spotkaniem** | 22, 25, 43, 50 |
| Ważność kodu logowania bez hasła | **10 minut** | 59 |
| Obietnica odpowiedzi na zgłoszenie | **24 h** (zobowiązanie organizacyjne, **nie blokada**) | 17, 51 |
| „Bez pośpiechu" wyprzedza świeże „pilne" | po **48 h** w kolejce | 17, 51 |
| Sprawa wdrożeniowa bez ruchu | **>14 dni** → lista do interwencji | 43, 51 |
| Kalendarz specjalisty otwarty krócej niż | **21 dni** → lista do przypomnienia | 42, 51 |
| Dzień zespołu zapala się na żółto | **>85%** wykupionych godzin | 39, 51 |
| Alert odwołań specjalisty | **>10 w oknie 30 dni** | 12, 13, 36, 42, 50 |
| Kartoteka pacjentów obejmuje | ostatnie **120 dni** | 41 |
| Nieobecności na pulpicie koordynatora | ostatnie **14 dni** | 41, 56 |
| Limit uzasadnienia decyzji uznaniowej | **<40 znaków → „do uzupełnienia"** | 51 |
| Paginacja | rezerwacje **60**, pacjenci **25**, obie piszą ile ukryły | 51 |
| Plik faktury | **PDF do 10 MB**, wymagany | 57 |

### Blokady interfejsu i przepływu
- **Przyciski odwołania i zmiany ZNIKAJĄ, nie są wyszarzane** (powtórzone 5×: s.6, 7, 22, 49, 55, 58).
- **Żadna propozycja specjalisty w kwalifikacji nie jest zaznaczona domyślnie** (s.16, 17, 40).
- **Godziny zajętej przez pacjenta nie da się kliknąć** w siatce dostępności (s.31).
- **Bez pliku PDF i numeru faktura nie wysyła się**; bez wpisanego powodu prośba o korektę nie działa (s.14, 57).
- **Wpisu w dzienniku decyzji nie da się zmienić ani usunąć** — brak przycisków „edytuj"/„usuń" także w oknie szczegółów (s.45, 51).
- **Zmiana specjalisty w istniejącej rezerwacji niemożliwa** (s.8, 25).
- **Urlop ma pierwszeństwo przed rytmem tygodniowym i przed poprawkami w siatce**, wyłącza wszystkie usługi naraz (s.36).
- **Limit 4 terminów niskopłatnych/tydzień działa przy układaniu grafiku, nie przy rezerwacji** (s.25, 35, 50, 60).
- **Zmiana numeru konta wymaga potwierdzenia linkiem z maila** (s.33, 36).
- **Blokada wsteczna reguł i cen** (s.46, 48, 51, 54, 59).

### RODO / zgody / dane szczególnej kategorii
- **2 zgody wymagane** (regulamin+polityka prywatności; **przetwarzanie danych o zdrowiu**), 1 nieobowiązkowa (warsztaty i grupy wsparcia) (s.3).
- **Zgoda na dane o zdrowiu jest w „Moje dane" zablokowana** — „bez niej nie da się prowadzić wizyt; odznaczenie oznaczałoby przerwanie leczenia, a to nie powinno dziać się jednym kliknięciem" (s.23).
- **Prawo do pobrania wszystkich danych i usunięcia konta** (s.23).
- **W systemie NIE MA notatek z sesji ani dokumentacji terapeutycznej** — „to dane szczególnej kategorii i wymagają **osobnej decyzji fundacji**: kto ma do nich dostęp, jak długo są przechowywane i czy pacjent widzi je u siebie" (s.23). To otwarta luka architektoniczna.
- **Ankieta: 6 pytań, zamknięte listy, zero pól opisowych** — „każde dodatkowe pole trzeba zabezpieczyć, przechować i po czasie usunąć" (s.17, 40).
- **Odpowiedzi ankiety nie trafiają do kartoteki pacjenta** (s.16, 17).
- **Odpowiedzi bez form „byłam/byłem"** — „rodzaj gramatyczny wymuszałby deklarację płci już w pierwszym kontakcie" (s.17).
- **Log dostępu przy odsłonięciu numeru telefonu** w kwalifikacji (s.16, 40).
- **Uzasadnienie wniosku o zwolnienie z opłaty wyłącznie finansowe** — pod polem stoi wprost, żeby nie wpisywać informacji o zdrowiu (s.10, 11, 30).
- **Powód odwołania specjalisty widzi koordynator, nie pacjent** (s.12, 13).
- **Specjalista widzi imiona uczestników grupy, ale nie kto ile zapłacił** (s.31).
- **Koordynator nie widzi treści rozmów ani notatek** (s.17, 41).
- **Raport dla grantodawcy bez nazwisk, powodów zgłoszenia i efektów terapii** (s.45).
- **Przycięte numery telefonów** w logu wysyłek SMS (s.53) i **inicjały zamiast nazwisk** w kolejce zgłoszeń (s.40).
- **Uzasadnienie decyzji uznaniowej wymagane dla kontroli dotacji** — „bez zapisanego uzasadnienia ten wydatek może nie zostać uznany" (s.49).

---

## 9. Pozostałe kwestie architektonicznie istotne

### 9.1 Otwarte pytania i sprzeczności wskazane WPROST w dokumencie
1. **„24 godziny" — zegarowo czy w dniach roboczych?** (s.48): „Otwarte pozostaje jedno: czy te 24 godziny liczyć zegarowo, także przez weekend (**tak jest dzisiaj**), czy w dniach roboczych — wtedy wizytę w poniedziałek rano trzeba by odwołać do piątku. **To decyzja regulaminowa, nie techniczna**."
2. **Rozjazd terminu zwrotu**: mail o odwołaniu przez specjalistę mówi o **3 dniach roboczych**, okno odwołania po stronie pacjentki o **3–5 dniach roboczych** — „Jedna rzecz do ujednolicenia przed wdrożeniem" (s.53, s.58–59).
3. **Kolizja dat rozliczeniowych**: termin przesłania faktury i termin przelewu **oba wypadają 10 dnia miesiąca** — „faktura złożona ostatniego dnia nie ma kiedy zostać sprawdzona. **Warto rozsunąć te dwie daty**" (s.59–60).
4. **Prywatny kalendarz Google** — „poza makietą, do decyzji" (s.31).
5. **Notatki z sesji / dokumentacja terapeutyczna** — wymagają osobnej decyzji fundacji (s.23).
6. **Migracja danych z obecnych narzędzi** — nieustalona (s.47).

### 9.2 Sprzeczność, której dokument NIE nazywa (istotna przy implementacji)
Na s.59 stoi twarda reguła: „**Wiadomości tekstowe wysyłamy tylko przy dwóch zdarzeniach: przypomnieniu 2 godziny przed i odwołaniu wizyty przez specjalistę. Reszta idzie mailem.**"
Ale scenariusze opisują SMS-y także przy: potwierdzeniu rezerwacji (s.4), przypomnieniu 24 h przed (s.4), propozycji z listy rezerwowej (s.7, s.13), zmianie miejsca spotkania (s.28) — a panel SMS wymienia **7 szablonów** w **4 kategoriach** (przypomnienia, potwierdzenia, zmiany i odwołania, **obsługa konta**, s.53) i **koszt 136 zł/mies. przy 46% udziale ogonków**, co byłoby niemożliwe przy 2 zdarzeniach. Ekran „Reguły systemu" daje **7 powiadomień × 2 przełączniki (mail/SMS)** (s.54) — więc rzeczywistą listę SMS-ów ustala konfiguracja, a zdanie ze s.59 opisuje prawdopodobnie **domyślne ustawienie**, nie ograniczenie systemu. **Do rozstrzygnięcia przed implementacją.**

### 9.3 Wzorce projektowe powtarzające się przez cały dokument (do przeniesienia do architektury)
- **„Informacja przed decyzją, nie po niej"**: stan puli niskopłatnej przed płatnością; licznik „zmiana 1 z 2" przed kliknięciem; kwota zwrotu przed potwierdzeniem odwołania; liczba pozycji przy każdym przycisku filtra; podgląd tego, co zobaczy pacjent, przed wysłaniem propozycji.
- **„Znikanie zamiast wyszarzania"** — wszędzie tam, gdzie akcja jest niedostępna z powodu reguły.
- **„Człowiek decyduje o wyjątkach, system o regułach"**: brak reguły „choroba zwalnia z opłaty"; „zwróć mimo reguły"; „odpuść tym razem"; wniosek o zwolnienie z opłaty; podniesienie limitu. **Każdy wyjątek zostawia ślad w dzienniku decyzji.**
- **„System nigdy nie obiecuje czegoś, czego sam nie robi"** — stąd „zwrot do wykonania", nie „zwrot wykonany".
- **Asymetrie celowe**: specjalista odwołuje → zawsze 100% zwrotu; pacjent → 24 h. Grupy → 2 h; wizyty 1:1 → 24 h. Rezerwacja na stronie → 10 min; link mailem → 2 dni.
- **Domyślne założenie idzie na korzyść pacjenta**: nieoznaczona wizyta po 48 h = odbyta (nie nieobecność), bo „brak reakcji nie może skutkować utratą jego pieniędzy" (s.15).
- **Ekrany świadomie nie pokazują**: pacjent — sum wydanych; specjalista — prowizji i kwot pacjenta; koordynator — treści rozmów i notatek.

### 9.4 Moduł Wiadomości (s.22, s.33, s.44)
- Trzy kierunki: **pacjent↔specjalista, specjalista↔koordynacja, pacjent↔koordynacja**.
- **Każdy wątek ma obowiązkowy kontekst: numer wizyty albo nazwa grupy** — inaczej „moduł zamienia się w drugą skrzynkę mailową, z której nikt nie korzysta".
- Wysłanie wiadomości → **odbiorca dostaje powiadomienie mailem**.
- **To nie jest kanał kryzysowy** — ostrzeżenie widoczne **dla specjalisty i koordynatora, nie dla pacjenta** („to instrukcja dla osoby, która ma zareagować", s.33). Numery kryzysowe w stopce każdej strony.

### 9.5 Eksporty i raporty
Do arkusza: lista wizyt specjalisty, lista obecności grupy, lista uczestników, rezerwacje, zestawienie dla księgowości specjalisty, pełny raport o zespole, pełny raport o grupach.
Do PDF: raport z pulpitu koordynatora (wybrane sekcje), raport dla grantodawcy, umowa i aneksy specjalisty, dokument „Zakres wdrożenia".

### 9.6 Ekran „Zakres wdrożenia" (s.47) — meta-dokument w makiecie
Rozpiska modułów: cel, lista ekranów, zadania rozbite na czynności, reguły modułu, ustalenia projektowe, zależności i ryzyka. 4 liczby na górze: moduły, ekrany, zadania, podzadania. Wyszukiwanie po treści (np. „płatności", „RODO"), zawężanie do jednej warstwy prac, eksport PDF. **Świadomie bez godzin i kwot** — „zakres jest stały, a wycena zależy od kolejności wdrażania, od tego ile fundacja robi sama, i od terminu".
