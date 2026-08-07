Przeczytałem cały plik (2666 linii, 70 stron). Poniżej pełne, ustrukturyzowane streszczenie.

**Plik źródłowy:** `C:\Users\Jakub\AppData\Local\Temp\claude\D--KOD-Niepodzielni\0ab2c6d0-e8c8-4c6f-9838-4de16e8edbc3\scratchpad\zakres-wdrozenia.txt`

---

# 1. Przegląd 6 modułów

| # | Moduł | Zadania / podzadania | Strony | Ekrany | Zakres w jednym zdaniu |
|---|---|---|---|---|---|
| 1 | **Panel pacjenta i ścieżka rezerwacji** | 29 / 169 | 3–15 | 15 | Od wyszukania najbliższego wolnego terminu, przez płatność i potwierdzenie z linkiem do spotkania, po odwołanie, przełożenie, zapisy na grupy i obsługę własnych danych (s. 3). |
| 2 | **Panel specjalisty (psychologa)** | 24 / 154 | 16–27 | 12 | Psycholog widzi swój dzień i grafik, wystawia terminy, oznacza wizyty, umawia pacjenta bez rozmowy o pieniądzach i rozlicza się z fundacją — widząc wyłącznie własne wynagrodzenie, nigdy prowizji (s. 16). |
| 3 | **Panel koordynatora — operacje bieżące** | 28 / 188 | 28–41 | 8 | „Co dzieje się dzisiaj w fundacji i gdzie muszę wejść ręcznie" — grafik zespołu, interwencje, kartoteka, kolejka pierwszego kontaktu, lista rezerwowa, wydarzenia (s. 28). |
| 4 | **Panel koordynatora — rozliczenia, raporty, konfiguracja** | 24 / 150 | 42–54 | 12 | Komu ile zapłacić, na jakiej podstawie odstąpiono od reguł, jakie liczby wpisać do sprawozdania z dotacji, oraz gdzie ustawia się reguły, cennik i powiadomienia (s. 42). |
| 5 | **Powiadomienia, płatności i sprawy przekrojowe** | 21 / 123 | 55–64 | 14 | Czy właściwa wiadomość idzie w odpowiednim momencie, czy pieniądze zgadzają się ze Stripe i czy każdy widzi wyłącznie to, do czego ma prawo (s. 55). |
| 6 | **Wdrożenie, utrzymanie i zgodność** | 24 / 132 | 65–70 | **0** | Wszystko, czego nie widać na żadnym ekranie: środowiska, backupy, monitoring, przejście ze starych narzędzi, RODO, dostępność, szkolenia, pierwsze tygodnie po starcie (s. 65). |

**Suma: 150 zadań, 916 podzadań, 61 ekranów** (zgodne z okładką, s. 1).

### Listy ekranów

**Moduł 1 (15)** — s. 3: `/`, `/szukaj`, `/psycholog/:slug`, `/rezerwacja`, `/potwierdzenie`, `/logowanie`, `/konto`, `/konto/wizyty` (przekier. z `/moje-wizyty`), `/konto/grupy`, `/konto/wiadomosci`, `/konto/dane` (przekier. z `/moje-dane`), `/wydarzenia`, `/wydarzenie/:slug`, `/zapis/:slug`, komponent `Support` (na każdym ekranie).

**Moduł 2 (12)** — s. 16: `/panel`, `/panel/wizyty`, `/panel/kalendarze`, `/panel/dostepnosc`, `/panel/rozliczenia`, `/panel/dokumenty`, `/panel/wydarzenia`, `/panel/wizyty`, `/panel/rozliczenia`, `/panel/wiadomosci`, `/panel/*`, `/panel/*`.

**Moduł 3 (8)** — s. 28: `/koordynacja`, `/koordynacja/grafik`, `/koordynacja/rezerwacje`, `/koordynacja/pacjenci`, `/koordynacja/psycholodzy`, `/koordynacja/zgloszenia`, `/koordynacja/lista-rezerwowa`, `/koordynacja/wydarzenia`.

**Moduł 4 (12)** — s. 42: `/koordynacja/faktury` (×3), `/panel/rozliczenia`, `/koordynacja/decyzje`, `/koordynacja/raport` (×2), `/koordynacja/wdrozenia`, `/koordynacja/uslugi`, `/koordynacja/reguly`, zbiorczy `/koordynacja, /koordynacja/psycholodzy, /koordynacja/decyzje, /koordynacja/wydarzenia`, `/koordynacja/*` (okno eksportu).

**Moduł 5 (14)** — s. 55: `/koordynacja/sms`, `/koordynacja/maile`, przycisk pływający (każdy ekran), zestaw publiczny (`/`, `/szukaj`, `/psycholog/:slug`, `/rezerwacja`, `/potwierdzenie`, `/logowanie`, `/wydarzenia`, `/wydarzenie/:slug`, `/zapis/:slug`), `/konto, /panel, /koordynacja`, `/koordynacja/reguly`, `/rezerwacja`, `/potwierdzenie`, `/konto/dane`, `/logowanie`, ekrany koordynatora, dwa globalne, oraz **`npm run build:publikacja`, `npm run publikuj`, `npm run sprawdz`** (skrypty policzone jako „ekrany").

**Moduł 6 (0)** — s. 65: brak ekranów.

---

# 2. Per moduł: wszystkie 150 zadań

Oznaczenia: **[R]** = zadanie z jawnie wypisanym akapitem „Ryzyko:". **[R!]** = ryzyko określone przez dokument jako *najczęściej / najbardziej niedoszacowane*.

## Moduł 1 — Panel pacjenta i ścieżka rezerwacji (29)

| # | Zadanie | Kat. | Str. | Opis |
|---|---|---|---|---|
| 1 | Model danych pacjenta, rezerwacji i ślad audytowy **[R]** | DANE | 3 | Tabele pacjent/rezerwacja/zgoda/zdarzenie/zapis, pola `kwota_zamrozona` i `regula_anulacji_zamrozona`, rola WP `pacjent`, migracje i seed; ryzyko: brak zamrożenia kwoty ujawnia się przy pierwszej podwyżce cennika. |
| 2 | Silnik dostępności i generator wolnych terminów **[R!]** | BACKEND | 3 | Złożenie rytmu tygodniowego + poprawek + wyjątków urlopowych, bufor 10 min, horyzonty 2 h/30 dni/7 dni; „najczęściej niedoszacowany element" — kombinatoryka kończąca się podwójną rezerwacją. |
| 3 | API wyszukiwarki terminów z filtrami i sortowaniem | BACKEND | 4 | Jeden endpoint zwracający specjalistów z najbliższymi terminami (bez N+1), filtry, sortowanie, cena „od X zł", paginacja. |
| 4 | Wydajność wyszukiwarki przy 111 specjalistach **[R]** | BACKEND | 4 | Materializacja slotów, cache z unieważnianiem per specjalista/dzień, indeksy, test obciążeniowy; cel < 300 ms; naiwna implementacja liczy 3330 dni-osób na żądanie. |
| 5 | Front: wyszukiwarka i profil specjalisty | FRONTEND | 4 | Przepięcie `Szukaj.tsx` i `ProfilPsychologa.tsx` z danych lokalnych na API, filtry w URL, profile całego zespołu z CPT `psycholog`. |
| 6 | Blokada terminu na czas płatności **[R]** | BACKEND | 5 | Rezerwacja wstępna 10 min zapisywana atomowo, obsługa wyścigu, cron zwalniający, wariant 2 dni dla terminów specjalisty; bez transakcji → podwójne rezerwacje. |
| 7 | Integracja Stripe Checkout i uzgadnianie stanu **[R!]** | INTEGRACJA | 5 | Sesja Checkout, webhooki z weryfikacją podpisu, idempotencja, rozjazdy stanów, dobowa rekoncyliacja, portal klienta; „klasyczne miejsce niedoszacowania — happy path to jeden dzień". |
| 8 | Checkout: formularz, zgody i opcjonalne konto | FRONTEND | 5 | Walidacja klient+serwer, wymuszenie zgód (dziś przycisk zawsze aktywny), zapis wersji regulaminu z IP i czasem, termin w sesji serwera zamiast localStorage. |
| 9 | Uwierzytelnianie pacjenta: magic link, hasło, konto w tle **[R]** | BEZPIECZ. | 6 | Konto w tle po płatności, token jednorazowy, kod SMS, CSRF, usunięcie ekranu kont demo; ryzyko: token w `Referer`/logach proxy = incydent RODO. |
| 10 | Potwierdzenie rezerwacji, .ics i propozycje terminów | FRONTEND | 6 | Podpięcie pod realną rezerwację zamiast zaszytego NP-2856, generowanie .ics ze strefą i przypomnieniami, realne sprawdzanie 3 propozycji. |
| 11 | Link do spotkania — generowanie pokoi wideo **[R]** | INTEGRACJA | 6 | Decyzja API (Whereby/Jitsi/Zoom) vs stały link, generowanie przy potwierdzeniu, wariant awaryjny; ryzyko kliniczne — pacjent może wejść w czyjąś sesję. |
| 12 | Panel pacjenta: pulpit, lista wizyt, limit, prowadzący | FRONTEND | 7 | Podpięcie pod API, serwerowe liczenie limitu, automatyczne przypisanie prowadzącego chronologicznie (błąd z DECYZJE.md §15), brak historii finansowej (decyzja 17). |
| 13 | API odwołania i przełożenia z egzekwowaniem polityki **[R]** | BACKEND | 7 | Przeniesienie `ocenaAnulacji()` na serwer, okno 24 h i limit 2 przełożeń w API, atomowe przełożenie, reguła zamrożona; dziś to tylko widoczność przycisków. |
| 14 | Kolejka zwrotów i kredyt za odsprzedany termin | BACKEND | 7 | Zadanie „zwrot do wykonania" (bez API), wykrycie odsprzedania slotu, kredyt w checkoucie, ręczne domknięcie; usunięcie toastu „zwrot zlecony w Stripe". |
| 15 | Wydarzenia grupowe: zapisy, lista rezerwowa, awans **[R]** | BACKEND | 8 | Model cyklu, limit miejsc → lista rezerwowa, awans z oknem 24 h, płatność dopiero po awansie, obecności do raportu grantowego; awaria crona w piątek = puste krzesła. |
| 16 | Front wydarzeń: lista, szczegóły, zapis, moje grupy | FRONTEND | 8 | Cztery ekrany na API, 5 stanów panelu zapisu, płatność przez Stripe, .ics dla całego cyklu, poprawka kierowania „Moje zapisy". |
| 17 | Moduł wiadomości — API, uprawnienia, powiadomienia **[R]** | BACKEND | 8 | Wątek z kontekstem wizyty/grupy, kontrola dostępu przy każdym odczycie, powiadomienie bez cytowania treści, retencja; ryzyko: treści kryzysowe wymagają procedury po stronie fundacji. |
| 18 | Moduł wiadomości — interfejs w trzech rolach | FRONTEND | 9 | Lista wątków i widok rozmowy na API, odświeżanie, blokada podwójnego kliknięcia, baner „to nie zastępuje interwencji kryzysowej". |
| 19 | Poczta transakcyjna: szablony, kolejka, harmonogram **[R]** | INTEGRACJA | 9 | Dostawca + SPF/DKIM/DMARC, 7 szablonów, harmonogram (natychmiast / 24 h / 2 h / po wizycie), .ics, odbicia; ryzyko: anulowanie zaplanowanych wysyłek jest regularnie pomijane. |
| 20 | Powiadomienia SMS | INTEGRACJA | 9 | Bramka z nadawcą „Niepodzielni", 2 scenariusze, respektowanie zgody, licznik segmentów, raport kosztów (polskie znaki +46%). |
| 21 | Moje dane: edycja, zgody, strefa czasowa, karta | FRONTEND | 9 | Zapis danych z potwierdzeniem zmiany e-maila, historia zgód, portal Stripe pod „Zmień kartę", realna obsługa strefy (dziś atrapa). |
| 22 | RODO: rejestr zgód, eksport, usunięcie, retencja **[R]** | BEZPIECZ. | 10 | Wersjonowany rejestr zgód (art. 9), eksport paczki, usunięcie z anonimizacją, retencje, szyfrowanie, umowy powierzenia; błąd = zdarzenie podlegające zgłoszeniu. |
| 23 | Role i uprawnienia — kto co widzi | BEZPIECZ. | 10 | Cztery poziomy (gość/pacjent/psycholog/koordynator) przypisane do każdego endpointu, ukrycie prowadzącego i prowizji, testy każdej pary rola × zasób. |
| 24 | Strefy czasowe i zmiana czasu **[R]** | BACKEND | 10 | UTC w bazie, prezentacja Europe/Warsaw, doba 23/25-godzinna, strefa w .ics; ryzyko: zmiana czasu zamienia bezpłatne odwołanie w płatne, dwa razy w roku. |
| 25 | Zgłoszenie problemu ze zrzutem ekranu | FRONTEND | 11 | Screen Capture API z obsługą odmowy, kontekst techniczny bez treści wizyt, kierowanie wg roli, numer sprawy. |
| 26 | Wpięcie ścieżki pacjenta w layout niepodzielni.com | FRONTEND | 11 | Osadzenie aplikacji w motywie WordPress, ujednolicenie tokenów `--psy-*` → `--np-*`, routing po ścieżkach zamiast po hashu, noindex na panelach. |
| 27 | Migracja z Bookero i okres przejściowy **[R]** | DANE | 11 | Wybór jednego z trzech wariantów, mapowanie dwóch kont Bookero, import rezerwacji, komunikacja do pacjentów; wariant równoległy = pewne podwójne rezerwacje. |
| 28 | Testy automatyczne ścieżki pacjenta **[R]** | TESTY | 12 | Testy jednostkowe funkcji regułowych na wartościach granicznych, E2E, współbieżność, uprawnienia, webhooki, `sprawdz-ekrany.mjs` jako bramka; 3 z 32 błędów wyglądały poprawnie na ekranie. |
| 29 | Dostępność WCAG 2.1 AA i responsywność **[R]** | FRONTEND | 12 | Siatka dni/godzin z klawiatury, pułapka fokusu w modalach, kontrasty (4,42 i 4,58 : 1), `aria-live`, mobile; fundacja pomocowa jest zobowiązana do wyższej dostępności. |

## Moduł 2 — Panel specjalisty (24) — **każde zadanie ma wypisane ryzyko**

| # | Zadanie | Kat. | Str. | Opis + ryzyko |
|---|---|---|---|---|
| 1 | Model danych i API panelu specjalisty **[R]** | DANE | 16 | Tabele specjalisty i dostępności, DTO rozdzielone (bez prowizji), seed 111 osób, endpointy REST `/panel/*`; ryzyko: tożsamość specjalisty żyje w **trzech źródłach** (rola WP + CPT, Bookero `bookero_id_pelny`/`bookero_id_niski`) — uzgodnienie „potrafi zjeść tyle samo, co sam model". |
| 2 | Silnik dostępności: rytm, poprawki, wyjątki **[R!]** | BACKEND | 17 | Rozwinięcie zakresów na sloty 50+10 i 90+10, poprawki jako osobny byt, wyjątki nadpisujące wszystko; **„najbardziej niedoszacowywany element całego projektu"** — ta sama funkcja obsługuje 7 dni × 1 osobę, 30 dni × 111 osób i 35 dni × 111 osób. |
| 3 | Ekran Dostępność **[R]** | FRONTEND | 17 | Rytm i siatka 7×12 na API, zapis optymistyczny, CRUD wyjątków z pokazaniem kolizji; dziś wszystko żyje w stanie komponentu i nic nie waliduje „25:00". |
| 4 | Limit terminów niskopłatnych po stronie podaży **[R]** | BACKEND | 17 | Liczenie w tygodniu ISO, odrzucanie zapisu >4, reset w poniedziałek; ryzyko: pula w makiecie to sztywna liczba 3 — nie wiadomo, czy „wystawiony" = otwarty czy otwarty-i-wolny. |
| 5 | Zajmowanie terminu i konflikty równoległych rezerwacji **[R]** | BACKEND | 18 | Unikalne ograniczenie `(specjalista_id, termin)`, zamknięcie tego samego czasu w drugiej usłudze, test współbieżności; przy PHP-FPM sam warunek w kodzie nie wystarcza. |
| 6 | Ekran Wizyty — trzy zakładki **[R]** | FRONTEND | 18 | Osobne pobieranie danych per zakładka, modal szczegółów, eksport CSV pod polski Excel, 7 kolumn na 13 calach; statusy w makiecie wynikają z pozycji w tablicy. |
| 7 | Oznaczanie wizyt: odbyta, nieobecność, domknięcie 48 h **[R]** | BACKEND | 18 | Walidacja własności i przeszłości terminu, cron domykający, „odpuść tym razem", ślad audytowy; kolizja domknięcia 48 h z zamknięciem okresu rozliczeniowego. |
| 8 | Odwołanie wizyty przez specjalistę **[R]** | BACKEND | 19 | Powód z listy, zadanie zwrotu 100%, wykluczenie z rozliczenia, 3 propozycje terminów, licznik >10 w 30 dniach; ryzyko: nigdzie nie wolno napisać „zwrot wykonany". |
| 9 | Umawianie pacjenta przez specjalistę z linkiem do płatności **[R]** | BACKEND | 19 | Wyszukanie/utworzenie pacjenta, rezerwacja „czeka na płatność", sesja Stripe mailem, termin ręczny spoza grafiku, uzgodnienie sprzeczności 10 min vs 2 dni; potrzebne osobne zadanie rekoncyliacyjne. |
| 10 | Wniosek o zwolnienie pacjenta z opłaty **[R]** | BACKEND | 19 | Wniosek z uzasadnieniem finansowym blokujący termin, kolejka decyzji koordynatora, oznaczenie wizyty pokrytej ze środków fundacji; pole uzasadnienia to „zaproszenie do wpisania danych o zdrowiu". |
| 11 | Ekran Moje kalendarze — usługi, stawka, uprawnienia **[R]** | FRONTEND | 20 | Włączanie usług z ostrzeżeniem, stawka walidowana serwerowo, wniosek o ADHD/asystenta, przepisanie do CPT `psycholog`; brak zamkniętej listy widełek (kwestia 1). |
| 12 | Pulpit specjalisty na realnych danych **[R]** | BACKEND | 20 | Jeden endpoint podsumowania, kafle, sekcja „Wymaga uwagi", okno przycisku „Dołącz", tylko inicjały; ryzyko: kafel wynagrodzenia i ekran rozliczeń rozjadą się o kilkaset zł. |
| 13 | Rozliczenia specjalisty — naliczanie wynagrodzenia **[R]** | BACKEND | 20 | Pozycja przy każdej zmianie statusu z kwotą zamrożoną, reguła płatności godziny (5 wariantów), okresy zamykane, 180 zł za grupę, test braku prowizji w API; ryzyko: nierozstrzygnięty Stripe Connect przebudowuje moduł w całości. |
| 14 | Zestawienie rozliczeniowe w PDF **[R]** | BACKEND | 21 | Serwerowe generowanie (bez okna drukowania), rozbicie odbyte/opłacone mimo nieobecności, font z polskimi znakami, token jednorazowy, wysyłka do 5 dnia; PDF „systematycznie niedoszacowywane". |
| 15 | Faktury: przesyłanie i obieg akceptacji **[R]** | BACKEND | 21 | Wrzutnia 10 MB z walidacją po zawartości i antywirusem, pliki poza katalogiem publicznym, 4 statusy, porównanie kwot; retencja 5 lat to decyzja księgowa, może zablokować odbiór. |
| 16 | Dokumenty i konto do wypłat **[R]** | BEZPIECZ. | 21 | Umowy przez kontroler, dwuetapowa zmiana rachunku, walidacja IBAN z sumą kontrolną, ślad audytowy, powiadomienie na stary adres; **„najbardziej opłacalny atak na ten system"**. |
| 17 | Grupy i warsztaty w panelu specjalisty **[R]** | BACKEND | 22 | Lista wydarzeń, skład grupy bez danych o płatnościach, obecność per spotkanie cyklu, eksport listy; lista uczestników grupy dla osób z depresją = zbiór danych o zdrowiu wychodzący na dysk. |
| 18 | Miejsce spotkania: link wideo i wybór gabinetu **[R]** | INTEGRACJA | 22 | Źródło linku, dostępność od razu (decyzja 12), zmiana per wizyta z zapisem trwałym, katalog gabinetów (Hoża, Wilcza, Kraków) jako dane; bez decyzji nr 4 zadania nie da się zacząć. |
| 19 | Integracja z Kalendarzem Google **[R]** | INTEGRACJA | 22 | OAuth per specjalista, **wyłącznie `freeBusy`** bez treści zdarzeń, odejmowanie zajętości, sync przyrostowy; dwukierunkowość generuje pętle — rekomendacja: sam odczyt jako etap 1. |
| 20 | Powiadomienia dla specjalisty (mail i SMS) **[R]** | INTEGRACJA | 23 | 5 szablonów zdarzeń, kolejka z ponowieniami, preferencje kanałów, dziennik wysyłek, kontrola segmentów; przy 111 specjalistach koszt SMS potrafi przekroczyć budżet. |
| 21 | Role, uprawnienia i izolacja danych specjalisty **[R]** | BEZPIECZ. | 23 | Wykorzystanie istniejącej roli WP `psycholog` bez wp-admin, filtrowanie po właścicielu w warstwie danych, ukrycie prowizji na poziomie API, polityka sesji w gabinecie; makieta trzyma rolę w localStorage (zustand persist). |
| 22 | Wymogi RODO w panelu specjalisty **[R]** | BEZPIECZ. | 23 | Klasyfikacja art. 9, rejestrowanie dostępu do kartoteki, minimalizacja (inicjały), retencja po zakończeniu współpracy, DPIA; **DPIA trzeba zamknąć przed etapem „model danych", nie po**. |
| 23 | Wydajność przy 111 specjalistach **[R]** | BACKEND | 24 | Materializacja slotów, cache obłożenia, test 3 najcięższych widoków, profilowanie po pomiarze; przy 7 osobach wszystko wygląda dobrze. |
| 24 | Testy, przypadki brzegowe i przekazanie **[R]** | TESTY | 24 | Testy reguł, integracyjne całej ścieżki, uprawnień A/B, stref czasowych, współbieżności, akceptacja z jednym psychologiem, instrukcja rytm vs poprawka; „testy muszą liczyć, a nie tylko renderować". |

## Moduł 3 — Koordynator, operacje bieżące (28)

| # | Zadanie | Kat. | Str. | Opis + ryzyko |
|---|---|---|---|---|
| 1 | Model danych i migracje modułu koordynacji **[R]** | DANE | 28 | Rezerwacje z polami zamrożonymi, zdarzenia audytowe, pacjenci z prowadzącym i limitem, zgłoszenia/lista rezerwowa/decyzje, indeksy, dane testowe; przepisanie Bookero to **osobne 16–24 h, których tu nie ma**. |
| 2 | Silnik dostępności i wykrywanie kolizji **[R]** | BACKEND | 28 | Sloty dnia, bufor 10 min, blokada >7 dni w API, kolizje na poziomie transakcji, DST, pula 4/tydzień; sloty 90-min rozjeżdżają siatkę godzinową. |
| 3 | API grafiku zespołu w trzech widokach **[R]** | BACKEND | 29 | Dzień (8 osób), tydzień (1 osoba × 7 dni), miesiąc (agregat 35 × 111), cache z inwalidacją, cel < 300 ms; **3885 kombinacji dzień × specjalista** wymaga tabeli agregatów. |
| 4 | Frontend grafiku — podłączenie i wydajność siatki | FRONTEND | 29 | Zastąpienie lokalnego `slotyDnia` zapytaniami, stan widoku w URL, stany ładowania/błędu, wyrównanie miesiąca do poniedziałku. |
| 5 | Interwencje koordynatora na pojedynczej wizycie **[R]** | BACKEND | 29 | Odwołanie ze zwrotem 100%, przeniesienie bez ograniczeń, zmiana specjalisty jako para operacji, zwrot uznaniowy, dziennik; **nierozstrzygnięte, czy przy przeniesieniu specjalista dostaje wynagrodzenie za pierwotny termin**. |
| 6 | Rezerwacja telefoniczna zakładana przez koordynatora **[R]** | BACKEND | 30 | Trzy tryby (link Stripe / ręczny przelew / wizyta bezpłatna), zakładanie konta razem z rezerwacją; sprzeczność 24 h (Grafik) vs 2 dni (`REGULY.dniNaOplacenie`). |
| 7 | Kartoteka pacjentów i automatyczne przypisanie prowadzącego **[R]** | BACKEND | 30 | Agregaty zdarzeniowe, przypisanie chronologiczne dla całego zespołu, licznik pomijający odwołane, endpoint bez notatek; błąd z §15 DECYZJE.md powtórzy się, jeśli agregacja pójdzie po specjalistach. |
| 8 | Podniesienie limitu niskopłatnych i decyzje uznaniowe **[R]** | BACKEND | 30 | Limit indywidualny na pacjencie, +4 wizyty z uzasadnieniem, wpis jako „zobowiązanie", sprawdzanie limitu w 3 miejscach jednakowo; **zmiana 10 → 4 po wdrożeniu wywróci kartotekę**. |
| 9 | Konta specjalistów, uprawnienia do usług i stawki **[R]** | BACKEND | 31 | Zaproszenie z tokenem tworzące konto w roli `psycholog`, nadanie/odebranie uprawnień, zawieszenie konta, powiązanie z CPT przez `post_author`; warstwy uprawnień per usługa trzeba dobudować od zera. |
| 10 | Wskaźniki jakościowe specjalistów i alert odwołań **[R]** | BACKEND | 31 | Licznik odwołań 30 dni, frekwencja, horyzont dostępności (próg 21 dni), obłożenie z prowizją tylko dla koordynatora, wyciszanie alertu; prowizja 0% musi być liczona **per usługa**, nie per specjalista. |
| 11 | Prośba o zwolnienie terminów wysyłana do specjalisty | BACKEND | 31 | Wybór zakresu, mail z przyciskiem na jednorazowym tokenie, endpoint sprawdzający aktualność, historia współpracy, licznik próśb. |
| 12 | Formularz pierwszego kontaktu i kolejka kwalifikacji **[R]** | BACKEND | 32 | 6 pytań zamkniętych bez pola opisowego, osobna zgoda art. 9, kolejka z wagą, mediana zamiast średniej, odsłonięcie telefonu jako zdarzenie w logu; zmienia reżim całej tabeli (szyfrowanie kolumn). |
| 13 | Silnik dopasowania specjalisty do zgłoszenia **[R]** | BACKEND | 32 | Twardy filtr formy, punktacja obszarów i miasta, bramka dostępności, liczenie terminu tylko dla 10 najlepszych; bez indeksu „pierwszy wolny termin" ekran będzie się zacinał. |
| 14 | Propozycja terminu z blokadą i wygaszaniem | BACKEND | 32 | Miękka rezerwacja na 2 dni, mail z **jednym** terminem i linkiem, cron zwalniający, ścieżka „Poproś o rozmowę", obsługa kolizji. |
| 15 | Lista rezerwowa i automat odzysku terminów **[R]** | BACKEND | 33 | Zapis z preferencjami, wykrycie zwolnionego terminu, propozycja mail+SMS z oknem 4 h, zegar stojący 21:00–8:00, automatyczne przejście dalej; **najbardziej podatne na błędy miejsce w module**. |
| 16 | Wydarzenia grupowe — tworzenie, cykle, publikacja **[R]** | BACKEND | 33 | Generowanie terminów cyklu, wielu prowadzących, publikacja, automatyczne zamknięcie 2 h przed, odwołanie ze zwrotami; zmiana terminu opublikowanego cyklu to osobna ścieżka, której makieta nie pokazuje. |
| 17 | Uczestnicy wydarzeń, awanse i obecności **[R]** | BACKEND | 33 | Lista z pozycją rezerwową, automatyczny awans, hurtowe podniesienie limitu, obecności, płatność przy awansie; nierozstrzygnięte, czy awans jest warunkowy do opłaty. |
| 18 | Pulpit koordynatora — agregaty statystyczne **[R]** | BACKEND | 34 | Szeregi tygodniowe, losy rezerwacji w 5 kategoriach, ranking 111 osób, ścieżka pacjenta, przeliczanie nocne; metryka „wejście na kalendarz → wizyta opłacona" to **osobne 8–12 h na instrumentację**. |
| 19 | Tryb typu płatności jako filtr całego panelu **[R]** | FRONTEND | 34 | Przekazywanie trybu do każdego zapytania, zawężanie puli PRZED filtrami, pasek stanu, tryb w nagłówku eksportów; „liczby wyglądają poprawnie niezależnie od tego, czy są pełne". |
| 20 | Eksport PDF po stronie serwera **[R]** | BACKEND | 34 | Szablon A4, zakres dat przy każdej sekcji, ostrzeżenie o różnych okresach, wykresy renderowane serwerowo, kolejka; render headless wymaga utrzymywania przeglądarki na serwerze. |
| 21 | Eksporty CSV z ekranów operacyjnych | BACKEND | 35 | Eksport z aktywnymi filtrami, ograniczenie kolumn, generowanie strumieniowe, kodowanie pod polski Excel, zapis do logu dostępu. |
| 22 | Role i uprawnienia w całym panelu **[R]** | BEZPIECZ. | 35 | Sprawdzanie przy każdym endpoincie, prowizja niewidoczna dla specjalisty, koordynator bez notatek, osobne uprawnienie na odsłonięcie kontaktu, testy negatywne; makieta ukrywa dane wyłącznie na froncie. |
| 23 | Integracja Stripe i kolejka zwrotów **[R!]** | INTEGRACJA | 35 | Checkout z blokadą, webhooki z idempotencją, zadanie uzgadniające, kolejka zwrotów bez API, płatność odroczona, ręczne księgowanie; **„najczęściej niedoszacowany fragment"**. |
| 24 | Powiadomienia mail i SMS **[R]** | INTEGRACJA | 36 | Poczta transakcyjna z odbiciami, szablony z walidacją zmiennych, bramka SMS z segmentami, log wysyłek; **6 z 7 szablonów przekracza jeden segment**, 136 zł = 46% rachunku. |
| 25 | Kalendarz i link do spotkania **[R]** | INTEGRACJA | 36 | ICS w potwierdzeniach z aktualizacją, jednokierunkowy eksport grafiku, wdrożenie wybranego źródła linku, obsługa awarii; **dwukierunkowy sync z Google świadomie POZA zakresem**. |
| 26 | Wymogi RODO w panelu koordynatora **[R]** | BEZPIECZ. | 36 | Rejestr czynności dla zgłoszeń art. 9, szyfrowanie kolumn, log dostępu, retencja zgłoszeń bez rezerwacji, anonimizacja statystyk, umowy powierzenia; bez tego **nie da się uruchomić produkcyjnie**. |
| 27 | Wydajność przy 111 specjalistach **[R]** | BACKEND | 37 | Profilowanie widoku miesięcznego, agregaty dzienne, indeks pierwszego wolnego terminu, cache zdarzeniowy, twarde limity paginacji, test 111 × 30 × 12. |
| 28 | Testy, przypadki brzegowe i przegląd kodu **[R]** | TESTY | 37 | Testy reguł, integracyjne całej ścieżki koordynatora, negatywne uprawnień, regresji na pułapkach z DECYZJE.md, równoległości, wydajności; 3 najgroźniejsze błędy nie były widoczne gołym okiem. |

## Moduł 4 — Koordynator: rozliczenia, raporty, konfiguracja (24)

| # | Zadanie | Kat. | Str. | Opis + ryzyko |
|---|---|---|---|---|
| 1 | Model danych rozliczeń i zamykanie okresu **[R]** | DANE | 42 | Tabele okresu/pozycji/wynagrodzenia, przeniesienie kwoty i wersji reguły do pozycji, snapshot przy zamknięciu, korekty do okresu bieżącego, migracja „wersji zerowej"; wybór Stripe Connect **zmienia ten model danych, a nie tylko integrację**. |
| 2 | Silnik naliczania wynagrodzenia, prowizji i dopłaty **[R]** | BACKEND | 42 | `ocenaAnulacji()` na serwer, godzina płatna przy późnym odwołaniu i nieobecności, prowizja tylko dla usług bez `bezProwizji`, dopłata = stawka pełna − 55 zł, liczenie w groszach; ryzyko: prowizja per specjalista/usługa, jeśli umowy różnią się. |
| 3 | Obieg faktur specjalistów — backend **[R]** | BACKEND | 43 | Model faktury, maszyna stanów z blokadą niedozwolonych przejść, porównanie kwot w obie strony, paczka wypłat dla banku, status „opłacona"; faktura wyższa = wizyta spoza grafiku, bez ścieżki dopisania koordynator utknie. |
| 4 | Ekran Faktury — podpięcie do API **[R]** | FRONTEND | 43 | Kafle z agregacji serwerowej, filtry z licznikami, sortowanie serwerowe, modal z podglądem pliku w izolowanej ramce, obsługa konfliktu równoległej edycji; podgląd cudzego PDF wymaga CSP i sandboxu. |
| 5 | Przechowywanie i udostępnianie plików faktur **[R]** | BEZPIECZ. | 43 | Zapis poza katalogiem publicznym, walidacja po zawartości, limit 10 MB, antywirus, pobieranie przez kontroler z wygasającymi linkami, retencja 5 lat; faktura zawiera adres, NIP i numer rachunku. |
| 6 | Dziennik decyzji uznaniowych — zapis niezmienialny **[R]** | BACKEND | 44 | **Tabela bez UPDATE i DELETE z odebraniem uprawnień roli bazodanowej**, numeracja DEC-rok-numer, łańcuch skrótów kryptograficznych, sprostowanie jako nowy wpis, 5 źródeł zapisu, minimum 40 znaków uzasadnienia; potrzebny jeden wymuszony punkt zapisu. |
| 7 | Ekran Historia decyzji **[R]** | FRONTEND | 44 | Filtry serwerowe, kafle z zawężonego zbioru, lista bez ucinania (wirtualizacja), modal z łańcuchem sprostowań, brak akcji Edytuj/Usuń; suma za 30 dni potrafi wyjść ujemna. |
| 8 | Silnik raportu grantowego — agregacja liczona osobami **[R]** | BACKEND | 45 | Liczenie przez zbiór identyfikatorów, nowi vs kontynuujący, wskaźnik kontynuacji, dopłata z cennika z dnia wizyty, snapshot zamkniętego kwartału; **trzeba rozstrzygnąć, co jest tożsamością pacjenta przy rezerwacji jako gość**. |
| 9 | Ekran Raport dla grantodawcy | FRONTEND | 45 | Wybór roku/kwartału z blokadą okresów niezamkniętych, kafle i rozbicie finansowania, tabela wskaźników z kolumną „jak jest liczony", modal „Zestawienie do przepisania". |
| 10 | Serwerowe generowanie PDF **[R!]** | BACKEND | 45 | **Pięć różnych układów**, render przez headless Chrome z `@page` A4 z `src/lib/pdf.ts`, wykresy jako SVG, kolejka zadań, archiwum, wysyłka mailem; to **nowy komponent infrastruktury**, jedno z typowych niedoszacowań projektu. |
| 11 | Okno eksportu PDF | FRONTEND | 46 | Wybór sekcji i przedziału z walidacją dat, przekazywanie zakresów z sekcji, wykrywanie różnych zakresów, stan „raport w przygotowaniu" z odpytywaniem. |
| 12 | Wdrożenie specjalistów — backend **[R]** | BACKEND | 46 | Model 7 kroków z 3 klasami blokad, wyliczanie wąskiego gardła, licznik „bez ruchu", zaproszenie z tokenem, flaga „może przyjmować pacjentów" egzekwowana przy rezerwacji, ścieżka dyplomu ADHD; **brak umowy = brak podstawy do powierzenia danych — ryzyko prawne**. |
| 13 | Ekran Wdrożenia — podpięcie do API | FRONTEND | 46 | Lista z 5 filtrami i licznikami serwerowymi, kolumna „przyjmuje pacjentów" z powodem blokady, ranking zatorów, modal z osią 7 kroków, akcje z potwierdzeniem skutku. |
| 14 | Katalog usług i wersjonowanie cennika **[R]** | BACKEND | 47 | CRUD usług, wersjonowanie cen z datą obowiązywania, walidacja widełek, skutki wyłączenia usługi, licznik przyjmujących, powiązanie z krokiem uprawnień; widełki to kwestia blokująca nr 1. |
| 15 | Ekran Katalog usług — formularz z prawdziwym zapisem | FRONTEND | 47 | Karty usług z odznakami, formularz kontrolowany zamiast `defaultValue`, podgląd skutku zmiany przed zapisem, kontrola unikalności identyfikatora. |
| 16 | Reguły systemu — konfiguracja z wersjonowaniem **[R]** | BACKEND | 47 | Przeniesienie stałych z `src/lib/reguly.ts` do tabeli konfiguracji, wersjonowanie z datą wejścia, `regula_anulacji_zamrozona` w rezerwacji, **jedna funkcja rozstrzygająca dla wszystkich modułów**, macierz odwołań jako dane; potrzebna wersja zerowa i migracja. |
| 17 | Ekran Reguły systemu — formularz z prawdziwym zapisem | FRONTEND | 48 | Zamiana pól niekontrolowanych na formularz ze stanem, selecty na wartościach liczbowych zamiast etykiet („24 godziny przed"), macierz z API, przełącznik kredytu z potwierdzeniem skutku. |
| 18 | Macierz powiadomień — konfiguracja kanałów **[R]** | BACKEND | 48 | 7 zdarzeń × mail/SMS, dwa niewyłączalne także przez API, szeregowanie względem terminu w Europe/Warsaw, kolejka z rejestrem doręczeń, licznik segmentów i szacunkowy koszt, limit dzienny i alert kosztowy. |
| 19 | Role i uprawnienia w panelu koordynatora **[R]** | BEZPIECZ. | 48 | Trzy role + pytanie o koordynatora podglądowego, kontrola API dla `/koordynacja/*`, osobne reprezentacje danych per rola, rozdzielenie obowiązków (faktury vs reguły), rejestr dostępu do danych finansowych; **prowizja wycieka najłatwiej przez ogólny endpoint rezerwacji**. |
| 20 | Uzgadnianie stanu ze Stripe i lista zwrotów **[R!]** | INTEGRACJA | 49 | Webhooki (`payment_intent.succeeded`, `charge.refunded`, `charge.dispute.created`) z idempotencją, codzienne porównanie z saldem Stripe, lista zwrotów z odnośnikiem do panelu, oznaczenie wykonania **dopiero po webhooku**, zwroty częściowe/podwójne; uzgadnianie to najdroższy i najczęściej niedoszacowany fragment. |
| 21 | Wymogi RODO w rozliczeniach, dzienniku i raportach **[R]** | BEZPIECZ. | 49 | Dziennik decyzji jako zbiór art. 9, raport bez danych osobowych, rejestr czynności, retencja (faktury 5 lat), pseudonimizacja odniesienia zamiast usunięcia wpisu, umowy powierzenia, klauzule; **rozstrzygnięcie musi być prawne i musi być PRZED budową dziennika**. |
| 22 | Wydajność raportów i list **[R]** | BACKEND | 50 | Indeksy, materializowane podsumowania kwartalne, stronicowanie (222 faktury przy 111 × 2 okresy), pomiar raportu rocznego < 2 s, cache okresów zamkniętych; wskaźnik kontynuacji bez materializacji wchodzi w kilkanaście sekund. |
| 23 | Dane historyczne do sprawozdań sprzed wdrożenia **[R]** | DANE | 50 | Decyzja czy Bookero trafia do bazy, import z oznaczeniem źródła poza rozliczeniami, odtworzenie tożsamości pacjenta z maila/telefonu, oznaczenie okresów niepełnych; **Bookero nie miał tożsamości pacjenta** — liczba osób będzie zawyżona. |
| 24 | Testy modułu rozliczeń, dziennika i raportów **[R]** | TESTY | 50 | Testy tabelaryczne macierzy odwołań (23:59/24:00/24:01), naliczania do grosza, **test kluczowy: suma osób z 4 kwartałów > liczba osób w roku**, obieg faktury, niezmienialność dziennika na poziomie bazy, uprawnienia, E2E, PDF. |

## Moduł 5 — Powiadomienia, płatności, sprawy przekrojowe (21)

| # | Zadanie | Kat. | Str. | Opis + ryzyko |
|---|---|---|---|---|
| 1 | Stripe Checkout dla rezerwacji własnej **[R]** | INTEGRACJA | 55 | Sesja PLN z metadanymi, rezerwacja w jednej transakcji z blokadą wiersza, wygaszanie po 10 min, idempotentne domykanie, karta/BLIK/Google Pay/Apple Pay, usługi 0 zł poza Stripe; ryzyko: „zapłacone, termin zajęty" to osobna ścieżka. |
| 2 | Webhooki Stripe i uzgadnianie stanu **[R]** | INTEGRACJA | 56 | Endpoint z weryfikacją podpisu i zapisem surowego zdarzenia, 5 typów zdarzeń, kolejka ponowień z alertem, nocne porównanie z PaymentIntentami, widok rozjazdów; **kolejność zdarzeń w Stripe nie jest gwarantowana**. |
| 3 | Płatność odroczona (2 dni) **[R]** | BACKEND | 56 | Jednorazowy link i publiczna strona `/oplac/:token` bez logowania, trzymanie terminu, ponowna wysyłka z unieważnieniem, obsługa opłacenia po zwolnieniu, wniosek o wizytę bezpłatną; token to wektor nadużycia. |
| 4 | Zwroty jako lista zadań + kredyt **[R]** | BACKEND | 56 | Wyliczanie wyłącznie z wartości zamrożonych, rejestr zadań ze statusami, domykanie po `charge.refunded`, kredyt za odsprzedany slot, „zwróć mimo reguły" z uzasadnieniem, usunięcie „zwrot zlecony w Stripe" z `/konto/wizyty`. |
| 5 | Silnik powiadomień: harmonogram, kolejka, kanały, zgody **[R!]** | BACKEND | 57 | Tabela zaplanowanych powiadomień + worker z ponowieniami wykładniczymi, planowanie przy rezerwacji, **przeplanowanie i odwoływanie przy zmianach**, zgody, cisza nocna, log dostarczeń; „najdroższy element to nie wysyłka, tylko przeplanowanie" — wpisy z trzech pokoleń. |
| 6 | Integracja bramki SMS **[R]** | INTEGRACJA | 57 | Wybór dostawcy i rejestracja nadawcy „Niepodzielni", E.164, webhook DLR, serwerowe liczenie GSM-7/UCS-2 z twardym odcięciem, miesięczny limit kosztu, raport zasilający `/koordynacja/sms`; **rejestracja pola nadawcy trwa od kilku dni do kilku tygodni**. |
| 7 | Poczta transakcyjna, dostarczalność, .ics **[R]** | INTEGRACJA | 57 | Dostawca + SPF/DKIM/DMARC dla `niepodzielni.com`, zwykły tekst + minimalny HTML, `.ics` (VEVENT, stały UID, alarm 24 h, SEQUENCE, METHOD:CANCEL), odbicia, test na 4 skrzynkach; aktualizacja przez .ics jest kapryśna w Google/Apple/Outlook. |
| 8 | Szablony maili w bazie **[R]** | BACKEND | 58 | Wersjonowanie z autorem, walidacja zmiennych wobec białej listy, eskejpowanie treści koordynatora, wysyłka testowa, uprawnienie „edycja szablonów", migracja 7 treści z `src/dane/maile.ts`; pytanie: render w chwili wysyłki czy planowania. |
| 9 | Ekran powiadomień SMS zasilany prawdziwymi danymi | FRONTEND | 58 | Katalog szablonów, koszt i historia z API, maskowanie numerów, wysyłka testowa tylko na numery personelu, **brak edytora treści dla koordynatora** — zamiast tego zgłoszenie prośby do wykonawcy. |
| 10 | Role, uprawnienia i egzekwowanie widoczności **[R]** | BEZPIECZ. | 58 | **Utworzenie roli pacjenta, której dziś w Niepodzielni-dev nie ma**, macierz rola × zasób × operacja na poziomie API, usunięcie prowizji z odpowiedzi dla `psycholog`, ochrona tras serwerowo, wyłączenie paska demo w produkcji; filtrowanie zderza się z wydajnością. |
| 11 | Logowanie: magic link, kod SMS, hasła personelu **[R]** | BEZPIECZ. | 59 | Token jednorazowy z krótkim życiem, kod 6-cyfrowy ważny 10 min z limitem prób, hasła i reset, **decyzja o drugim składniku dla koordynatora**, konto w tle i scalanie po e-mailu, ochrona przed enumeracją, rate limiting; scalanie kont może pokazać cudzą historię wizyt. |
| 12 | Wymogi RODO: zgody, eksport, usunięcie, retencja **[R]** | BEZPIECZ. | 59 | Rejestr zgód osobno dla art. 9 / marketingu / SMS, wycofanie ze skutkiem natychmiastowym, eksport z listą wyłączeń, usunięcie jako anonimizacja z zachowaniem spójności raportów, retencja treści SMS/zrzutów/IP, szyfrowanie w spoczynku. |
| 13 | Ślad audytowy zdarzeń i oś czasu rezerwacji | DANE | 59 | Tabela tylko do dopisywania (aktor: człowiek albo system), zdarzenia finansowe i komunikacyjne, oś czasu **bez zdarzeń z przyszłości**, eksport wycinka, retencja i archiwizacja. |
| 14 | Zgłoszenia problemów z prawdziwym zrzutem ekranu **[R]** | FRONTEND | 60 | Screen Capture API + ścieżka zapasowa (mobile), kompresja i usunięcie metadanych, kontekst techniczny bez treści wizyt, kierowanie wg roli, rejestr z retencją; **zrzut koordynatora zawiera dane innych pacjentów** — pytanie, czy wykonawca jest podmiotem przetwarzającym. |
| 15 | Link do spotkania wideo — wybór wariantu **[R]** | INTEGRACJA | 60 | Porównanie i decyzja, tworzenie pokoju przy opłaceniu / walidacja stałego linku, zapis w rezerwacji i udostępnienie od razu, unieważnianie przy zmianach, pokoje dla grup; **bez decyzji nie da się tego rzetelnie wycenić**. |
| 16 | Reguły systemu jako konfiguracja w bazie **[R]** | BACKEND | 60 | Przeniesienie z `src/lib/reguly.ts`, zamrażanie kwoty i reguły w chwili zakupu, jedna funkcja serwerowa, walidacja spójności konfiguracji, historia zmian, **ujednolicenie limitu 10 vs 4**; zamrożoną regułę taniej zapisać jako pełny zrzut niż odwołanie do wersji. |
| 17 | Strefy czasowe i zmiana czasu **[R]** | BACKEND | 61 | UTC w bazie, okna 24 h / 2 h liczone w Europe/Warsaw niezależnie od strefy pacjenta, doby 23/25-godzinne w filtrach i raportach, spójne godziny w mailach/SMS/.ics, testy na pacjencie w Ameryka/Nowy Jork; 24 h zegarowo to **założenie makiety, nie decyzja regulaminowa**. |
| 18 | Środowiska, sekrety i monitorowanie integracji | INTEGRACJA | 61 | Rozdzielenie kluczy testowych i produkcyjnych, sekrety poza repozytorium, **środowisko testowe z twardą blokadą wysyłki na prawdziwe numery**, monitoring webhooków/SMS/odbić/pokoi, instrukcja awaryjna dla fundacji, przegląd endpointów publicznych. |
| 19 | Wydajność list i odpowiedzi API **[R]** | BACKEND | 61 | Indeksy i agregaty zamiast pętli po zespole, stronicowanie i twarde limity z informacją o ukrytych pozycjach, buforowanie slotów, budowanie list filtrów z danych, test < 300 ms; **filtrowanie uprawnieniami i agregacja walczą ze sobą**. |
| 20 | Testy end-to-end ścieżek pieniężnych i wysyłek | TESTY | 61 | Pełna rezerwacja w trybie testowym, odwołanie przed i po granicy 24 h, zmiana terminu z kontrolą kolejki przypomnień, wizyta nieopłacona po 2 dniach, atrapy bramek z asercjami na treść/kanał/moment, uruchomienie w CI. |
| 21 | Utrzymanie makiety i przeniesienie bramek do CI | TESTY | 62 | `sprawdz-ekrany.mjs` i `sprawdz-publikacje.mjs` do CI z Chrome headless, kontrola świeżości artefaktu, data publikacji i skrót wersji w stopce, aktualizacja makiety równolegle z budową. |

## Moduł 6 — Wdrożenie, utrzymanie i zgodność (24) — brak akapitów „Ryzyko"

*(szczegóły w sekcji 8)*

1. Infrastruktura produkcyjna i pipeline wdrożeniowy [BACKEND, s. 65]
2. Kopie zapasowe i procedura odtworzenia [BACKEND, s. 65]
3. Monitoring, alerty i dziennik błędów [BACKEND, s. 65]
4. Kolejka zadań okresowych i idempotencja [BACKEND, s. 66]
5. Przegląd bezpieczeństwa i testy penetracyjne [BEZPIECZ., s. 66]
6. Migracja kont i profili z WordPressa [DANE, s. 66]
7. Wyłączenie Bookero i demontaż integracji [DANE, s. 66]
8. Anonimizacja danych w środowiskach nieprodukcyjnych [DANE, s. 67]
9. Dokumentacja RODO poza kodem [BEZPIECZ., s. 67]
10. Regulamin, polityka prywatności i zgody na cookies [BEZPIECZ., s. 67]
11. Notatki z sesji — decyzja i osadzenie w reżimie [BEZPIECZ., s. 67] — **jedyna jawna wycena w dokumencie: 44 h**
12. Audyt WCAG 2.1 AA paneli specjalisty i koordynatora [FRONTEND, s. 68]
13. Responsywność paneli i wersja mobilna [FRONTEND, s. 68]
14. Testy obciążeniowe i budżety wydajnościowe [TESTY, s. 68]
15. Dokumentacja użytkownika i materiały szkoleniowe [TESTY, s. 68]
16. Szkolenia i pilotaż wdrożeniowy zespołu [TESTY, s. 68]
17. Dokumentacja techniczna i przekazanie [TESTY, s. 69]
18. Stabilizacja po starcie — cztery tygodnie [TESTY, s. 69]
19. Opinie o specjaliście [FRONTEND, s. 69]
20. Zapis pacjenta na listę oczekujących [BACKEND, s. 69]
21. Obsługa błędów interfejsu (404, 403, wygasły magic link, utrata połączenia) [FRONTEND, s. 69]
22. System projektowy paneli [FRONTEND, s. 69]
23. „Pomoc w kryzysie" [FRONTEND, s. 70]
24. Poprawki na produkcji niepodzielni.com [FRONTEND, s. 70]

---

# 3. ARCHITEKTURA — rozstrzygnięcie

## Wniosek

**Dokument NIE zakłada budowy osobnej, niezależnej aplikacji.** Zakłada **backend w PHP postawiony od zera, osadzony w ekosystemie WordPressa strony fundacji (repozytorium `Niepodzielni-dev`)**, z zachowaniem WordPressa jako źródła tożsamości użytkowników i profili specjalistów, oraz frontend SPA (React/TypeScript/Vite — po plikach `.tsx`, `VITE_HASH`, `zustand persist`) osadzony w produkcyjnym motywie WordPress.

Jednocześnie: **dokument ani razu nie wymienia `niepodzielni-core`, nie mówi o „wtyczce" do zbudowania, nie wskazuje żadnego frameworka PHP (Laravel/Symfony/inny), i nie mówi wprost „to będzie wtyczka WordPressa" ani „to będzie osobna aplikacja".** Zostawia to nierozstrzygnięte — jedyne twarde wskazanie technologiczne to „PHP wskazany w DECYZJE §26".

## Cytaty — WordPress, role WP, Niepodzielni-dev, CPT

> „Zaimplementować rolę WordPress `pacjent` i powiązanie konta z rekordem pacjenta (dziś rola nie istnieje w Niepodzielni-dev)" — **s. 3**, moduł 1, zadanie 1

> „Zależy od: API wyszukiwarki; CPT `psycholog` i taksonomie z Niepodzielni-dev" — **s. 4**, moduł 1, zadanie 5

> „Dodać strony profilowe dla całego zespołu, nie tylko siedmiu opisanych profili, wraz ze zdjęciami i biogramami z CPT `psycholog`" — **s. 4**

> „**Osadzić aplikację w motywie WordPress** tak, żeby nagłówek, menu konsultacji, stopka z numerami kryzysowymi i pasek misji pochodziły z jednego źródła" — **s. 11**, moduł 1, zadanie 26

> „Zależy od: **Dostęp do motywu produkcyjnego Niepodzielni-dev**" — **s. 11**

> „Zaprojektowanie tabel: specjalista (**user_id roli psycholog + post_id CPT**, stawka, usługi, formy), dostepnosc_rytm, poprawka_slotu, wyjatek_dostepnosci, rezerwacja, zdarzenie_rezerwacji" — **s. 16**, moduł 2, zadanie 1

> „**Endpointy REST /panel/*** z jednolitą obsługą błędów i kodów 403/404" — **s. 16**

> „Ryzyko: Profil specjalisty istnieje już jako **CPT z rolą WP i linkowaniem przez post_author**, a równolegle żyje w Bookero z dwoma osobnymi identyfikatorami (bookero_id_pelny, bookero_id_niski). **Uzgodnienie tożsamości specjalisty między trzema źródłami potrafi zjeść tyle samo, co sam model.**" — **s. 16**

> „Ryzyko: (…) Przy kilku **procesach PHP-FPM** sprawdzenie 'czy wolne' i zapis muszą być w jednej transakcji z blokadą wiersza" — **s. 18**, moduł 2, zadanie 5

> „Przepisanie zmian formy i usług do **profilu publicznego CPT psycholog**, żeby wyszukiwarka pacjenta nie kłamała" — **s. 20**, moduł 2, zadanie 11

> „**Wykorzystanie istniejącej roli WP 'psycholog' bez dostępu do wp-admin, z przekierowaniem na /panel**" — **s. 23**, moduł 2, zadanie 21

> „Powiązanie konta użytkownika z wpisem CPT psycholog przez post_author, **zgodnie z istniejącym rozwiązaniem w repozytorium**" — **s. 31**, moduł 3, zadanie 9

> „Ryzyko: **Rola psycholog istnieje w repozytorium, ale nie ma warstwy uprawnień per usługa — trzeba ją dobudować od zera.**" — **s. 31**

### Cytat kluczowy — rozstrzyga sprawę tożsamości

> „**Rola WP `psycholog` już istnieje w repozytorium Niepodzielni-dev, jest linkowana z wpisem CPT przez post_author, nie ma dostępu do wp-admin i przekierowuje na /panel/. Nie trzeba budować osobnego systemu użytkowników — trzeba dobudować warstwę uprawnień per usługa. Rola pacjenta NIE istnieje i to jest największa zmiana w całym projekcie.**" — **s. 41**, moduł 3, USTALENIA PROJEKTOWE

> „utworzenie **roli pacjenta, której dziś w Niepodzielni-dev nie ma**, i pogodzenie jej z istniejącą rolą psycholog powiązaną z CPT przez post_author" — **s. 58**, moduł 5, zadanie 10

> „**Rola pacjenta nie istnieje w Niepodzielni-dev** — dziś nie ma kont pacjentów ani historii wizyt. **Rola psycholog już jest (WP, bez dostępu do wp-admin, przekierowanie na /panel/, powiązanie z CPT przez post_author).** Największą zmianą nie jest kalendarz, tylko pojawienie się pacjenta jako podmiotu z kontem i historią." — **s. 64**, moduł 5, USTALENIA PROJEKTOWE

### Cytat kluczowy — technologia backendu

> „Konfiguracja serwera aplikacji, bazy i procesu roboczego **pod PHP wskazany w DECYZJE §26** (**obecna makieta jest frontendem, backend trzeba postawić od zera**)." — **s. 65**, moduł 6, zadanie 1

To jedyne w całym dokumencie jawne wskazanie języka/platformy backendu. Dokument odsyła po nie do **DECYZJE.md §26** — czyli sama decyzja technologiczna żyje w innym dokumencie.

### Cytat kluczowy — migracja z WordPressa (napięcie architektoniczne)

> „**6. Migracja kont i profili z WordPressa** [DANE]
> — Zmapowanie roli WP psycholog i post_author **na konta w nowym systemie**, z zachowaniem możliwości logowania tymi samymi danymi.
> — Przeniesienie CPT psycholog i czterech taksonomii (specjalizacje, języki, nurty, obszary pomocy) **na model filtrów wyszukiwarki**, z ujednoliceniem wartości.
> — Normalizacja stawek z atrybutów opisanych w **migracja-psychologow-woo.md** („155" → „155 zł") i przypisanie każdej osoby do widełek fundacji.
> — **Ustalenie kierunku synchronizacji profilu: czy wp-admin pozostaje źródłem prawdy, czy przejmuje to nowy panel.**
> — Uruchomienie próbne migracji na kopii produkcji, raport rozbieżności, poprawki, powtórka.
> — Skrypt wycofania migracji i procedura na wypadek przerwania w połowie." — **s. 66**

To jest **jedyne miejsce, gdzie dokument dopuszcza wyjście poza WordPressa** („konta w nowym systemie", „nowy panel przejmuje źródło prawdy") — i **jawnie zostawia to jako decyzję do podjęcia**. Stoi to w napięciu z cytatem ze s. 41 („nie trzeba budować osobnego systemu użytkowników").

### Pozostałe ślady WordPressowe

> „Usunięcie **wtyczki bookero-init.js** z frontu i podmiana kalendarza na własną siatkę terminów na profilach." — **s. 66** (to jedyna „wtyczka" w dokumencie — do usunięcia, nie do zbudowania)

> „Zachować wybór rodzaju konsultacji bez przeładowania strony — to główna przewaga nad **dzisiejszą wtyczką Bookero**" — **s. 4**

> „**Renderowanie pola złożonego Carbon Fields** („Tryb konsultacji: field_complex")." — **s. 70**, moduł 6, zadanie 24 (Carbon Fields = biblioteka pól WordPressa)

> „Wdrożyć **routing po ścieżkach zamiast po hashu**, wraz z regułami przepisywania na serwerze" — **s. 11**

> „Router po hashu jest decyzją wyłącznie publikacyjną (**VITE_HASH** podstawiane przy budowaniu) — serwer statyczny i dysk nie wiedzą nic o adresie /panel. W docelowym systemie zostają normalne ścieżki" — **s. 64**

### Czego w dokumencie NIE MA (wyszukane, zero trafień)

- **`niepodzielni-core`** — 0 wystąpień
- **„wtyczka" jako artefakt do zbudowania** — 0 (tylko Bookero i bookero-init.js, do usunięcia)
- **Laravel, Symfony, Slim, Node.js, Next.js, Express** — 0
- **„osobna aplikacja", „headless", „monorepo"** — 0
- **„osobny backend/API" jako świadoma alternatywa architektoniczna** — 0; jest tylko „backend trzeba postawić od zera" (s. 65) i „Endpointy REST /panel/*" (s. 16)

---

# 4. Odwołania do istniejącego kodu, makiety i repozytoriów

### DECYZJE.md — cytowany kilkadziesiąt razy

| Odwołanie | Str. | Treść |
|---|---|---|
| „kod ma 10, DECYZJE.md w dwóch miejscach mówi o 4" | 3 | „Uzgodnić z zamawiającym rozbieżność limitu wizyt niskopłatnych: **kod ma 10, DECYZJE.md w dwóch miejscach mówi o 4**" |
| DECYZJE.md §15 | 7 | „(błąd opisany w DECYZJE.md §15 polegał na sortowaniu po specjaliście)" |
| DECYZJE.md §15 | 10 | „Ukryć prowadzącego specjalistę przed gościem i przed koordynatorem oglądającym ekran pacjenta (błąd opisany w DECYZJE.md §15)" |
| DECYZJE.md §23 | 12 | „Dodać test przeglądarkowy weryfikujący, że aplikacja startuje i renderuje treść — **kontrola statyczna nie zastąpi uruchomienia (DECYZJE.md §23)**" |
| Kwestia otwarta nr 2 | 16 | „Zależy od: Rozstrzygnięcie kwestii otwartej nr 2 z DECYZJE.md — co dzieje się z rezerwacjami umówionymi w Bookero w dniu przełączenia" |
| Decyzja 9 | 25 | „Decyzja 9 z DECYZJE.md — katalog usług i cennik są globalne i ustala je fundacja." |
| Decyzja 10 | 25 | „dostępność ma dwa poziomy: rytm tygodniowy jako baza plus siatka konkretnych godzin. **Uzasadnienie zapisane w kodzie**" |
| Decyzja 11 / rozdz. 20 | 26 | „prowizja jest niewidoczna dla specjalisty" |
| Kwestie otwarte 1, 3, 4, 11 | 26–27 | Pełne omówienie |
| §14, §23 | 64 | „Publikacja jest skryptem, bo ręczne kopiowanie dało biały ekran (DECYZJE §14), a drugi biały ekran wynikał z wyjątku w czasie wykonania i przeszedł przez typy, smoke test i wszystkie kontrole strukturalne (§23)" |
| §4 | 63–64 | „**Trzy pola, bez których system się rozjeżdża (DECYZJE §4): kwota_zamrozona, regula_anulacji_zamrozona i Poprawka jako osobny byt.**" |
| §26 | 65 | „pod PHP wskazany w DECYZJE §26" |
| Rozdz. 15 | 67 | „(rozdział 15 DECYZJE: **32 błędy, z czego 3 wynikały ze złych proporcji danych**)" |
| Rozdz. 23 | 65 | „Przeniesienie bramek z skrypty/ do CI: typy, sprawdz-ekrany.mjs, uruchomienie w Chrome headless, skan surowych bajtów sterujących (rozdział 23 DECYZJE)" |
| §21, §22, §25, §27 | 63 | Symulator SMS, wymuszony zrzut, zwroty ręczne, podział mail/SMS |

### Konkretne pliki i symbole makiety wymienione z nazwy

| Plik / symbol | Str. | Kontekst |
|---|---|---|
| `Szukaj.tsx`, `ProfilPsychologa.tsx` | 4 | „Przepiąć (…) z danych lokalnych na API" |
| `MojeWizyty.tsx` | 14 | „pokazuje toast »zwrot zlecony w Stripe«, co tę zasadę łamie i wymaga poprawki" |
| `sprawdz-ekrany.mjs` | 12, 62, 65 | Bramka wydania, 4 kontrole danych, do CI |
| `sprawdz-publikacje.mjs` | 62 | Do CI |
| `src/lib/reguly.ts` | 30, 42, 47, 60, 62, 64 | Źródło stałych `REGULY`, `ocenaAnulacji()`, do przeniesienia do bazy |
| `src/lib/pdf.ts` | 45, 53 | Arkusz `@page` A4; `tabelaPrzykladowa` generuje pseudolosowe tabele |
| `src/dane/maile.ts` | 58 | 7 treści do migracji do bazy jako wersje wyjściowe |
| `lib/czas.ts` | 64 | „jedno źródło »teraz«" jako punkt wstrzykiwania w testach |
| `terminy.ts:141` | 66 | „identyfikatory rezerwacji w makiecie są przewidywalne (NP- plus numer arytmetyczny)" |
| `Rezerwacja.tsx:166` | 67 | Zgoda na dane o zdrowiu (art. 9) |
| `Pulpit.tsx:179` | 67 | „Zdjęcie obietnicy (…) jeśli notatek nie będzie" |
| `LayoutPanelu.tsx:146` | 68 | Boczne menu o stałej szerokości do przerobienia |
| `Modal.tsx` | 68 | Pułapka ogniska, Escape |
| `HistoriaDecyzji.tsx` | 52 | „Dziennik decyzji jest niezmienialny (komentarz w HistoriaDecyzji.tsx)" |
| `RaportGrantowy.tsx` | 53 | „Główną metryką raportu grantowego jest osoba, nie wizyta (komentarz w RaportGrantowy.tsx)" |
| `Grafik.tsx` | 38 | „modal rezerwacji telefonicznej w Grafik.tsx mówi »termin trzymany 24 h« — sprzeczność" |
| `ListaRezerwowa.tsx` | 69 | „`krotkiTermin` w ListaRezerwowa.tsx już tego oczekuje" |
| `LayoutAdmina` | 53 | „jest zwykłą trasą bez żadnej bramki" |
| `tokens.css`, `app.css` | 69 | „Przeniesienie (…) do produkcji z zachowaniem aliasów `--psy-*`" |
| `migracja-psychologow-woo.md` | 66 | Osobny istniejący dokument o migracji stawek („155" → „155 zł") |
| `bookero-init.js` | 66 | Wtyczka do usunięcia z frontu |
| `npm run build:publikacja`, `npm run publikuj`, `npm run sprawdz` | 55 | Wymienione w liście „ekranów" modułu 5 |
| `skrypty/` | 65 | Katalog bramek do przeniesienia do CI |
| `ETYKIETY_STATUSU` | 28 | Słownik statusów do migracji |
| `zustand persist` / localStorage | 23 | „Makieta trzyma rolę i identyfikator specjalisty w localStorage przez zustand persist. To wyłącznie mechanizm demonstracyjny" |
| `VITE_HASH` | 64 | Router po hashu jako decyzja publikacyjna |

### „Co już działa / czego nie ma" — jawne bilanse stanu makiety

> „Stan makiety w tym obszarze: **wszystkie ekrany są kompletne wizualnie i logicznie, ale żaden nie ma trwałości.** Rytm, poprawki, wyjątki, nadpisane linki do spotkań i wybrane gabinety żyją w stanie komponentów i znikają po odświeżeniu. Statusy w historii wizyt (…) wynikają z pozycji w tablicy, a nie z danych. (…) Eksport listy wizyt, eksport listy obecności, pobranie zestawienia PDF, pobranie umowy i aneksu oraz 'Połącz z Google' to na dziś **wyłącznie powiadomienia na ekranie**." — **s. 27**

> „**CZEGO NIE MA I TRZEBA DOBUDOWAĆ W CAŁOŚCI: jakiegokolwiek zapisu** — wszystkie akcje na tych sześciu ekranach kończą się toastem. Formularze na ekranach Reguły i Katalog usług są niekontrolowane (defaultValue), a selecty reguł operują na etykietach tekstowych typu »24 godziny przed«, więc nie mają czego zapisać. Tabele w generowanym PDF są pseudolosowe (funkcja tabelaPrzykladowa w lib/pdf.ts) i nie zawierają ani jednej prawdziwej liczby. Podgląd i przesyłanie pliku faktury to podmiana nazwy w stanie komponentu. **Nie ma uwierzytelniania ani kontroli ról — rolę przełącza pasek demo, a LayoutAdmina jest zwykłą trasą bez żadnej bramki.**" — **s. 53**

> „ANONIMIZACJA (rozdz. 16): siedem prawdziwych profili z niepodzielni.com zastąpiono wymyślonymi, bo makieta krąży dalej niż strona (…). Zespół w makiecie liczy 111 osób (7 opisanych + 104 wygenerowane)" — **s. 14, 41, 53**

---

# 5. Integracje

## Stripe (najszerzej opisana integracja)

**Model płatności:** pełna kwota przy rezerwacji przez Stripe Checkout, waluta PLN, kwota zamrożona, metadane rezerwacji (id, specjalista, usługa, termin) — s. 14, 55.

**Metody płatności:** karta, BLIK, Google Pay, Apple Pay. „BLIK ma własny przepływ potwierdzenia i osobne stany błędu" (s. 55). Usługi 0 zł (asystent zdrowienia) **omijają Stripe** (s. 55).

**Webhooki (pełna lista z dokumentu):** `checkout.session.completed`, `payment_intent.succeeded`, `payment_intent.payment_failed`, `charge.refunded`, `charge.dispute.created` (s. 5, 49, 56). Weryfikacja podpisu, zapis surowego zdarzenia, idempotencja po identyfikatorze zdarzenia, kolejka ponowień z alertem.

**Uzgadnianie stanu (rekoncyliacja):** zadanie dobowe/nocne porównujące płatności w bazie z listą PaymentIntentów i saldem Stripe, raport rozbieżności dla koordynatora, widok z ręcznym domknięciem pozycji (s. 5, 35, 49, 56). Dokument nazywa to **najdroższym i najczęściej niedoszacowanym fragmentem integracji płatniczej**.

**Zwroty — kluczowa decyzja:** `REGULY.zwrotyAutomatyczne = false`. Fundacja klika zwrot **ręcznie w panelu Stripe**; system tylko generuje listę zadań. Oznaczenie „wykonany" **dopiero po webhooku `charge.refunded`**, nie po kliknięciu (s. 49). Obsługa zwrotu częściowego, podwójnego i wykonanego w Stripe bez wpisu w systemie. Język interfejsu: zawsze „zwrot do wykonania", nigdy „zwrot wykonany" (s. 13, 26, 51, 62).

**Portal klienta Stripe:** podpięty pod przycisk „Zmień kartę" w Moich danych; pobieranie 4 ostatnich cyfr i daty ważności z API (s. 5, 9).

**Płatność odroczona:** jednorazowy wygasający link płatności + publiczna strona **`/oplac/:token` bez logowania**, 2 dni na opłacenie (`REGULY.dniNaOplacenie`), unieważnianie poprzedniego tokenu przy ponownej wysyłce (s. 56).

**Środowiska:** tryb testowy, klucze w zmiennych środowiskowych, **osobne webhooki dla stagingu** (s. 5), rozdzielenie kluczy testowych i produkcyjnych (s. 61), osobne klucze dla dev/staging/produkcji (s. 65).

**KWESTIA OTWARTA nr 3 (blokująca):** przelewy miesięczne vs **Stripe Connect** z podziałem płatności przy zakupie. Connect: zdejmuje ręczną pracę, ale **wymaga weryfikacji tożsamości każdego ze 111 specjalistów w Stripe**, przebudowuje ekran Rozliczenia i cały obieg faktur (s. 20, 26–27), oraz **zmienia model danych rozliczeń, a nie tylko integrację** (s. 42).

**Kredyt za odsprzedany termin:** `REGULY.kredytZaOdsprzedany = true` — po późnym odwołaniu, gdy slot kupi ktoś inny, pierwszy pacjent dostaje równowartość jako kredyt, różnicę pokrywa fundacja (s. 13, 51, 62).

## SMS

**Dostawca: NIEWYBRANY.** „wybór dostawcy i rejestracja alfanumerycznego pola nadawcy »Niepodzielni« u operatorów krajowych" (s. 57). Ryzyko: **rejestracja pola nadawcy u polskich operatorów trwa od kilku dni do kilku tygodni i wymaga dokumentów fundacji**; do tego czasu wiadomości wychodzą z numeru losowego (s. 57).

**Techniczne:** normalizacja do E.164, odrzucanie numerów spoza dozwolonych krajów, webhook DLR z mapowaniem statusów, ponowienia dla błędów przejściowych, serwerowe liczenie segmentów GSM-7/UCS-2 z twardym odcięciem powyżej progu (s. 57).

**Ekonomia (policzona w makiecie):** 7 szablonów, **1815 wysyłek na 30 dni, 8 gr za segment = 292 zł miesięcznie**. GSM-7 = 160 znaków, UCS-2 (polskie znaki) = 70 znaków. **Sześć z siedmiu szablonów nie mieści się w jednym segmencie.** Usunięcie diakrytyków obniżyłoby rachunek do 157 zł, czyli o **136 zł i 46%** (s. 62). Rekomendacja dokumentu: **nie pisać bez ogonków** (wiadomość od fundacji pomocowej wygląda wtedy jak spam) — tańsza droga to skrócenie treści; trzy szablony przekraczają limit o kilkanaście znaków (s. 63).

**Zasady treści:** „ani słowa o zdrowiu, nazwie usługi ani specjalizacji; nadawca »Niepodzielni«; jedna informacja na wiadomość" — bo wiadomość wyświetla się na zablokowanym ekranie (s. 12, 41, 62).

**Zakres domyślny:** SMS tylko przy przypomnieniu 2 h przed i przy odwołaniu przez specjalistę (s. 9, 51, 62).

**Uprawnienia:** **koordynator NIE edytuje SMS-ów** (tylko maile); zmiana treści SMS to konfiguracja bramki i ponowne testy — zamiast edytora jest formularz zgłoszenia prośby do wykonawcy (s. 41, 53, 58, 63).

## E-mail

**Dostawca: NIENAZWANY** — „dostawca poczty transakcyjnej" (s. 9, 36, 57). Konfiguracja **SPF, DKIM, DMARC dla domeny niepodzielni.com** (s. 9, 57).

**Format:** zwykły tekst + minimalna wersja HTML dla klientów, które jej wymagają. **Świadomie bez edytora z formatowaniem** — „zwykły tekst dociera zawsze, rozbudowany HTML potrafi wylądować w spamie" (s. 63).

**Szablony:** 7 istniejących treści w `src/dane/maile.ts` → migracja do bazy jako wersje wyjściowe; wersjonowanie z autorem i datą, powrót do treści domyślnej, **walidacja zmiennych wobec białej listy przypisanej do konkretnego szablonu** (edytor nie pozwoli zapisać `{imie}` spoza listy), eskejpowanie treści koordynatora, wysyłka testowa do siebie (s. 58).

**Harmonogram:** mail natychmiast po rezerwacji (z linkiem do spotkania i .ics), przypomnienie 24 h przed, przypomnienie 2 h przed, propozycja kolejnego terminu po wizycie (s. 9, 12).

**Dostarczalność:** obsługa odbić i skarg, lista wykluczeń, wyłączenie wysyłki po twardym odbiciu, **test dostarczalności do Gmaila, Outlooka, WP i Onetu — „cztery skrzynki, cztery różne zachowania"** (s. 57).

**Prywatność:** „treść maili nie zdradza rodzaju usługi osobom postronnym w podglądzie skrzynki" (s. 9); powiadomienie o nowej wiadomości bez cytowania treści (s. 8).

**Staging:** osobne konto SMTP i osobny nadawca, „żeby test przypomnień nie poszedł do pacjentów" (s. 65); twarda blokada wysyłki na prawdziwe numery i adresy (s. 61, 67).

## Kalendarz / .ics

**.ics:** VEVENT ze **stałym UID**, strefa Europe/Warsaw, alarm 24 h; aktualizacje i odwołania przez `SEQUENCE` i `METHOD:CANCEL`; .ics dla całego cyklu wydarzenia (jeden wpis na spotkanie) (s. 8, 57).

**Ryzyko (s. 57):** „Aktualizacja terminu przez plik .ics jest kapryśna: Google Calendar, Apple Calendar i Outlook różnie reagują na zmianę SEQUENCE przy tym samym UID. Realnie kończy się to serią prób na trzech klientach i decyzją, czy przy zmianie terminu wysyłać odwołanie plus nowe zaproszenie zamiast aktualizacji."

## Google Calendar

- **OAuth per specjalista** z bezpiecznym przechowywaniem tokenów odświeżających (s. 22)
- **Wyłącznie `freeBusy`** — bez treści zdarzeń; obietnica z ekranu („nikt nie zobaczy ich treści, tylko fakt, że jesteś zajęta") musi być prawdziwa technicznie
- Odejmowanie zajętości od slotów w momencie generowania oferty; kierunek drugi (zapis wizyt do kalendarza specjalisty) z opisem bez danych o zdrowiu
- Synchronizacja przyrostowa zamiast odpytywania przy każdym wyświetleniu
- **Ryzyko:** dwukierunkowość generuje pętle i duplikaty (wizyta zapisana do Google wraca jako zajętość i sama siebie blokuje). „Sam odczyt free/busy jest **o połowę tańszy** i rozwiązuje 80% problemu — warto zaproponować fundacji ten wariant jako pierwszy etap." (s. 22)
- **KWESTIA OTWARTA nr 11** (s. 27). W module 3 zapisano wprost: „**Dwukierunkowy sync z Kalendarzem Google jest tu świadomie POZA zakresem**" (s. 36)
- Dziś „Połącz z Google" to wyłącznie powiadomienie na ekranie (s. 27)

## Wideo

**Wariant NIEWYBRANY — kwestia otwarta nr 4, blokująca.** Opcje: pokoje generowane przez API (**Whereby / Jitsi / Zoom**) vs stały link w profilu specjalisty (s. 6, 22, 36, 60).

- Wariant API: tworzenie pokoju przy opłaceniu, czas ważności, sprzątanie po zakończeniu; kosztuje abonament i dokłada integrację
- Wariant stały link: darmowy, ale **„dwie wizyty pod rząd wpadają do tego samego pokoju — pacjent może wejść w czyjąś sesję. To ryzyko kliniczne, nie techniczne"** (s. 6)
- Niezależnie od wariantu: link dostępny **od razu po rezerwacji** (decyzja 12), unieważnianie przy odwołaniu, przenoszenie przy zmianie terminu, wariant zapasowy na awarię dostawcy, pokoje dla wydarzeń grupowych (jeden link, wielu uczestników, cykl spotkań)
- „**Bez decyzji nie da się tego zadania rzetelnie wycenić**" (s. 60)
- Specjalista może nadpisać link per wizyta („używa własnego pokoju w Zoomie") — s. 26

## WordPress

Patrz sekcja 3. Rola integracyjna: źródło tożsamości (rola `psycholog`), źródło profili (CPT + 4 taksonomie: specjalizacje, języki, nurty, obszary pomocy), źródło layoutu (motyw produkcyjny), Carbon Fields na produkcji.

## SSO / „Konta Niepodzielni"

**NIE WSPOMNIANE ANI RAZU.** Zero wystąpień „SSO", „single sign-on", „jednolite logowanie", „Konta Niepodzielni". Dokument opisuje **własny system logowania** (magic link, kod SMS, hasła personelu, konto w tle po płatności, scalanie po e-mailu) — s. 6, 59 — oraz zachowanie logowania WP dla specjalistów („z zachowaniem możliwości logowania tymi samymi danymi", s. 66).

## Hub / podsumowania międzyproduktowe

**NIE WSPOMNIANE.** Słowo „podsumowanie" występuje wyłącznie w znaczeniu wewnętrznym (kafle pulpitu, podsumowanie dnia w grafiku, podsumowania kwartalne, zestawienie rozliczeniowe). Brak jakiejkolwiek wzmianki o hubie, agregatorze wielu systemów fundacji, wspólnym dashboardzie produktów, newsletterze czy CRM.

## Inne integracje / zależności zewnętrzne

- **Bookero** (do wygaszenia) — dwa konta SaaS `5tu8AC22Akna` i `hxRnUexTsSvc`, cron synchronizujący co 60 s, Circuit Breaker, dwie warstwy cache, wtyczka `bookero-init.js`, identyfikatory `bookero_id_pelny` / `bookero_id_niski` (s. 11, 16, 66)
- **Skan antywirusowy** plików faktur (s. 21, 43)
- **Headless Chrome** na serwerze do generowania PDF (s. 45) i do bramek CI (s. 62, 65)
- **Magazyn kopii zapasowych** poza serwerem aplikacji, jako osobny podprocesor (s. 65, 67)
- **Zewnętrzny wykonawca testu penetracyjnego** (s. 66)
- **Screen Capture API** przeglądarki (s. 11, 60)
- **Clipboard API** (s. 6, 18, 45)

---

# 6. Kwestie otwarte / decyzje do podjęcia — pełna lista

## A. Kwestie numerowane z DECYZJE.md

| Nr | Kwestia | Status | Str. |
|---|---|---|---|
| **1** | **Pełna lista dozwolonych widełek pełnopłatnych.** Makieta używa 115/125/135/145 zł, **produkcja pokazuje 135 i 145**. „Bez zamkniętej listy nie da się zbudować walidacji serwerowej wyboru stawki." Dodatkowo: co ze stawkami specjalistów spoza nowej listy. | **BLOKUJĄCA** | 3, 20, 27, 31, 47, 53 |
| **2** | **Co z rezerwacjami już umówionymi w Bookero w dniu przełączenia.** Trzy warianty: przepisanie rezerwacji / dopalenie Bookero do wyczerpania terminów / równoległa praca dwóch systemów. „Przepisanie ich do nowej bazy to **osobne 16–24 h, których tu nie ma**." | **BLOKUJĄCA** | 11, 16, 28, 53 |
| **3** | **Rozliczenie z psychologami: przelewy raz w miesiącu czy Stripe Connect** z podziałem przy zakupie. Connect wymaga weryfikacji tożsamości 111 osób i **przebudowuje model danych rozliczeń**, nie tylko integrację. | **BLOKUJĄCA** | 5, 20, 26–27, 35, 42, 49, 53, 64 |
| **4** | **Skąd bierze się link do spotkania** — pokoje generowane przez API vs stały link w profilu. Bez tego zadania nie da się zacząć ani wycenić. | **BLOKUJĄCA** | 6, 22, 26–27, 36, 60, 64 |
| **6** | **Czy okno 24 h liczy się zegarowo, czy w dniach roboczych.** Wybór dni roboczych oznacza przerobienie całej arytmetyki okna i wszystkich treści przypomnień. | nieblokująca | 10, 54, 61, 64 |
| **9** | **Pusty stan wyszukiwarki** — zapis na listę oczekujących zamiast dzisiejszego komunikatu bez wyjścia. | nieblokująca | 4 |
| **11** | **Dwukierunkowa synchronizacja z Kalendarzem Google.** Sam odczyt free/busy realizuje obietnicę ekranu i kosztuje o połowę mniej. | nieblokująca | 27 |
| **12** | **Reżim notatek z sesji i dokumentacji terapeutycznej** — kto ma dostęp, jak długo przechowywane, czy pacjent widzi je w panelu. Dziś świadomie poza zakresem panelu. **Jeśli w zakresie: osobne 44 h.** | nieblokująca | 10, 15, 67 |
| **13** | **Czy opinię może wystawić wyłącznie osoba z zakończoną rezerwacją** — założenie makiety do formalnego potwierdzenia. | nieblokująca | 15 |

## B. Rozbieżności kod ↔ dokumentacja (do rozstrzygnięcia przed wdrożeniem)

| Rozbieżność | Opis | Str. |
|---|---|---|
| **Limit wizyt niskopłatnych: 10 vs 4** | `REGULY.limitNiskoplatnychWizyt = 10` w `src/lib/reguly.ts`; DECYZJE.md w **wierszu 18 tabeli decyzji i w rozdziale 12** mówi o **4 wizytach (i 4 godzinach)**. Ekrany Pacjenci, Raport i Historia decyzji liczą z 10. Wartość wpływa na: treść maila „Wyczerpany limit", licznik w checkoucie, budżet dopłat, wskaźnik „wyczerpały limit" w sprawozdaniu. **„Zmiana z 10 na 4 po wdrożeniu wywróci kartotekę — nagle większość pacjentów będzie miała wyczerpany limit."** | 3, 13, 26, 30, 37, 53, 60, 62, 64 |
| **Okno blokady terminu przy umawianiu przez specjalistę** | Okno „Umów pacjenta" mówi „termin jest zablokowany przez **10 minut** od otwarcia linku", `REGULY.dniNaOplacenie` daje **2 dni**, a modal rezerwacji telefonicznej w `Grafik.tsx` mówi „termin trzymany **24 h**". **Trzy wartości, jedna musi zostać** — od niej zależy, kiedy termin wraca do puli. | 19, 26, 30, 38 |
| **„Środki własne w okresie" = ?** | Pozycja podsumowująca pokazuje **dokładnie tę samą wartość** co wiersz „Prowizja od wizyt pełnopłatnych". Trzeba ustalić, czy środki własne to sama prowizja, czy prowizja **pomniejszona o dopłatę** — dziś ta suma nie mówi nic ponad wiersz wyżej. | 53 |
| **Toast „zwrot zlecony w Stripe"** | `/konto/wizyty` (`MojeWizyty.tsx`) pokazuje „Wizyta odwołana · zwrot zlecony w Stripe", co **wprost przeczy decyzji nr 25**. Do usunięcia. | 7, 14, 56, 64 |

## C. Kwestie nieblokujące wymienione zbiorczo (s. 54, 64)

- Czy potrzebne są **faktury z NIP-em dla pacjentów**, czy wystarczy paragon Stripe
- **Kody rabatowe i vouchery** — nie ma ich w checkoucie, bo nie wiadomo, czy fundacja ich używa (gdyby tak: pole nad podsumowaniem, nie w formularzu danych)
- Czy 24 h liczy się zegarowo, czy w dniach roboczych (= kwestia 6)

## D. Decyzje operacyjne i modelowe rozsiane po zadaniach

| Decyzja | Str. |
|---|---|
| Czy przy **przeniesieniu wizyty przez koordynatora** specjalista dostaje wynagrodzenie za pierwotny termin — „inaczej rozliczenia zaczną się rozjeżdżać po pierwszym miesiącu" | 29 |
| Do którego **okresu rozliczeniowego** wchodzi wizyta domknięta automatycznie po 48 h (wizyta z 30. dnia domknie się 2. następnego) | 18 |
| Co się dzieje, gdy **koordynator obniży limit niskopłatnych poniżej liczby już wystawionych terminów** | 17 |
| Definicja „**wystawiony termin niskopłatny**": slot otwarty, czy slot otwarty i jeszcze wolny — „Ta definicja musi zapaść przed kodowaniem" | 17 |
| Czy **awans na płatny warsztat jest warunkowy** do czasu opłaty, czy miejsce jest trzymane | 33 |
| **Rezygnacja z cyklu wieloetapowego** — czy zwalnia miejsce we wszystkich spotkaniach, czy tylko w najbliższym | 8 |
| Domyślna ścieżka **przy odmowie wniosku o wizytę bezpłatną**: zwolnienie blokady czy wysłanie standardowego linku do płatności | 19 |
| Czy **eksport listy obecności grupy do CSV** w ogóle, w jakim zakresie i z jakim pouczeniem | 22 |
| Czy fundacja potrzebuje **koordynatora podglądowego** bez prawa akceptacji faktur | 48 |
| Czy **rozdzielić koordynatora od administratora technicznego** | 35 |
| Czy **zaplanowane powiadomienie renderuje się w chwili wysyłki** (bierze nową treść szablonu) czy w chwili planowania | 58 |
| Czy potrzebna **stawka prowizji per specjalista / per usługa** (jeśli umowy mówią inaczej niż jednolite 20%) — „podnosi koszt o kilkanaście godzin" | 42 |
| **Kierunek synchronizacji profilu**: czy wp-admin pozostaje źródłem prawdy, czy przejmuje to nowy panel | 66 |
| Czy **rezerwacje z Bookero trafiają do bazy**, czy raport grantowy zaczyna się od dnia uruchomienia | 50 |
| **Co jest tożsamością pacjenta przy rezerwacji jako gość** — bez rozstrzygnięcia ta sama osoba policzy się kilka razy i liczba objętych pomocą będzie zawyżona | 45 |
| **Drugi składnik uwierzytelniania dla koordynatora** — decyzja i wdrożenie | 59 |
| **Wpływ oceny na sortowanie w wyszukiwarce** (dziś sortuje się po najwcześniejszym terminie) | 69 |
| **Retencja i archiwizacja faktur po zakończeniu współpracy** — „decyzja księgowa, nie programistyczna, i potrafi zablokować odbiór" | 21 |
| **Prawo do usunięcia vs niezmienialny dziennik decyzji vs 5-letni obowiązek przechowywania** — „Rozstrzygnięcie musi być prawne, a nie techniczne, i trzeba je mieć PRZED budową dziennika" | 49, 59 |
| **Kto ma dostęp do zrzutów ekranu ze zgłoszeń** koordynatora (zawierają dane innych pacjentów) i czy wykonawca jest podmiotem przetwarzającym | 60 |
| Czy **notatki z sesji** wchodzą w zakres pierwszego wdrożenia | 67 |

---

# 7. Ryzyka wypisane w dokumencie — pełna lista z przypisaniem

## Ryzyka nazwane wprost jako „najczęściej / najbardziej niedoszacowane"

| Ryzyko | Zadanie | Str. |
|---|---|---|
| **„Najbardziej niedoszacowywany element całego projektu"** — silnik dostępności; ta sama funkcja obsługuje panel (7 dni × 1 osoba), wyszukiwarkę (30 dni × 111) i grafik miesięczny (35 dni × 111) | M2/2 | 17 |
| **„Najczęściej niedoszacowany element"** — silnik dostępności: 3 warstwy × 2 długości usług × strefa czasowa | M1/2 | 3 |
| **„Klasyczne miejsce niedoszacowania"** — Stripe: „Sam happy path to jeden dzień pracy; koszt siedzi w scenariuszach rozjazdu, idempotencji i w tym, że część z nich da się odtworzyć tylko ręcznie" | M1/7 | 5 |
| **„Najczęściej niedoszacowany fragment"** — Stripe i kolejka zwrotów u koordynatora | M3/23 | 35 |
| **„Uzgadnianie jest najdroższym i najczęściej niedoszacowanym fragmentem integracji płatniczej"** | M4/20 | 49 |
| **„Generowanie PDF jest w tym projekcie systematycznie niedoszacowywane"** — polskie znaki i podział na strony | M2/14 | 21 |
| **„Jedno z typowych niedoszacowań tego projektu"** — serwerowe PDF to nowy komponent infrastruktury, nie przepisanie kodu | M4/10 | 45 |
| **„Klasyczne miejsce niedoszacowania"** — sloty 90-min i blokada bazodanowa | M3/2 | 28 |
| **„Najdroższy element to nie wysyłka, tylko przeplanowanie"** — powiadomienia | M5/5 | 57 |

## Ryzyka techniczne — podwójne rezerwacje i współbieżność

| Ryzyko | Zadanie | Str. |
|---|---|---|
| Blokada bez transakcji i bez testu współbieżności → podwójne rezerwacje ujawniające się „gdy dwoje pacjentów przyjdzie na tę samą godzinę" | M1/6 | 5 |
| Przy PHP-FPM sam warunek w kodzie nie wystarcza — sprawdzenie i zapis muszą być w jednej transakcji z blokadą wiersza | M2/5 | 18 |
| Dwa równoległe żądania na ten sam termin bez blokady bazodanowej — „podwójna rezerwacja, której nikt nie zauważy do dnia wizyty" | M3/2 | 28 |
| Lista rezerwowa: zegar stojący na noc + zmiana czasu + dwie osoby przyjmujące jednocześnie = **„najbardziej podatne na błędy miejsce w całym module"** | M3/15 | 33 |
| Wyścig wygaśnięcia blokady vs zaksięgowanie płatności — potrzebna osobna ścieżka „zapłacone, termin zajęty" | M5/1 | 55 |
| Kolejność zdarzeń w Stripe nie jest gwarantowana — webhook o zwrocie może przyjść przed webhookiem o płatności | M5/2 | 56 |

## Ryzyka wydajnościowe

| Ryzyko | Zadanie | Str. |
|---|---|---|
| Naiwna wyszukiwarka liczy **3330 dni-osób na każde żądanie**; działa na 7 profilach, przewraca się na produkcji | M1/4 | 4 |
| Widok miesięczny = **3885 kombinacji dzień × specjalista**; liczenie na żywo idzie w sekundy | M3/3 | 29 |
| Silnik dopasowania: kilkadziesiąt tysięcy slotów przy każdym otwarciu modala bez indeksu „pierwszy wolny termin" | M3/13 | 32 |
| Wskaźnik kontynuacji sięga 4 kwartały wstecz — bez materializacji raport roczny wchodzi w kilkanaście sekund | M4/22 | 50 |
| Przy 7 osobach każdy układ działa — problem ujawnia się na pełnym zespole i najczęściej **po wdrożeniu** | M2/23, M3/27 | 24, 37 |
| Filtrowanie uprawnieniami i agregacja walczą ze sobą — potrzebne osobne zapytania dla liczb i dla listy | M5/19 | 61 |

## Ryzyka bezpieczeństwa i RODO

| Ryzyko | Zadanie | Str. |
|---|---|---|
| Magic link daje dostęp do danych o zdrowiu — token w `Referer` albo w logach proxy to **incydent RODO, nie usterka** | M1/9 | 6 |
| Dane o zdrowiu psychicznym = szczególna kategoria; błąd w retencji lub zakresie eksportu to **zdarzenie podlegające zgłoszeniu** | M1/22 | 10 |
| Reguła egzekwowana wyłącznie w interfejsie jest do obejścia zapytaniem do API — „przy polityce, która decyduje o pieniądzach, to otwarta furtka" | M1/13 | 7 |
| **Podmiana numeru konta to najbardziej opłacalny atak na ten system** — jedno przejęte konto przekierowuje miesięczną wypłatę; samo potwierdzenie mailem nie wystarcza | M2/16 | 21 |
| Makieta trzyma rolę w localStorage (zustand persist) — uprawnienie nie może pochodzić z danych z przeglądarki | M2/21 | 23 |
| **DPIA zrobiona po zbudowaniu modelu danych potrafi wymusić zmiany w samym modelu** — warto ją zamknąć przed etapem „model danych" | M2/22 | 23 |
| Pole uzasadnienia wniosku o zwolnienie z opłaty to „zaproszenie do wpisania danych o zdrowiu mimo ostrzeżenia" | M2/10 | 19 |
| Lista uczestników grupy dla osób z depresją = zbiór danych o zdrowiu z imionami i mailami; eksport do CSV wyprowadza go na dysk specjalisty | M2/17 | 22 |
| Obszar wsparcia w zgłoszeniu = dana art. 9 → zmienia reżim całej tabeli; **„zrobienie tego po wdrożeniu jest dwa razy droższe"** | M3/12 | 32 |
| **„Bez tego kroku systemu nie da się uruchomić produkcyjnie"** — RODO u koordynatora; dołożenie szyfrowania po wdrożeniu **podwaja koszt** | M3/26 | 36 |
| Makieta ukrywa dane wyłącznie na froncie — „to zadanie łatwo uznać za zrobione po dodaniu warstwy w menu, a wtedy dane wyciekają przez samo API" | M3/22 | 35 |
| **Prowizja wycieka najłatwiej przez ogólny endpoint rezerwacji** zwracający pełny obiekt | M4/19 | 48 |
| Faktura zawiera adres, NIP i numer rachunku — wyciek pliku to **incydent ochrony danych** | M4/5 | 43 |
| Podgląd cudzego PDF wymaga CSP i sandboxu — plik z zewnątrz nie może wykonać skryptu w kontekście panelu | M4/4 | 43 |
| **Prawo do usunięcia zderza się z niezmienialnym dziennikiem i z obowiązkiem księgowym** — rozstrzygnięcie prawne PRZED budową dziennika | M4/21, M5/12 | 49, 59 |
| Scalanie konta gościa z istniejącym = miejsce, gdzie można pokazać komuś cudzą historię wizyt | M5/11 | 59 |
| **Zrzut ekranu koordynatora zawiera dane innych pacjentów** — wymuszony zrzut z makiety staje się problemem prawnym po wdrożeniu | M5/14 | 60 |
| Token w mailu bez logowania (`/oplac/:token`) — wygoda i wektor nadużycia jednocześnie | M5/3 | 56 |
| Identyfikatory rezerwacji są przewidywalne (`NP-` + numer arytmetyczny, `terminy.ts:141`) | M6/5 | 66 |
| **Brak podpisanej umowy = brak podstawy do powierzenia danych pacjenta** — jeśli flaga blokująca nie jest sprawdzana przy rezerwacji, to **ryzyko prawne, nie usterka interfejsu** | M4/12 | 46 |

## Ryzyka procesowe, danych i biznesowe

| Ryzyko | Zadanie | Str. |
|---|---|---|
| Pominięcie kwoty i reguły zamrożonej wychodzi przy pierwszej podwyżce cennika — stare zwroty przestają zgadzać się ze Stripe | M1/1 | 3 |
| **Stały link wideo: dwie wizyty pod rząd w tym samym pokoju — „ryzyko kliniczne, nie techniczne"** | M1/11 | 6 |
| Awans z listy rezerwowej to cron działający „na pieniądzach i miejscach jednocześnie — awaria crona w piątek wieczorem oznacza puste krzesła w poniedziałek" | M1/15 | 8 |
| Wiadomości od pacjenta w kryzysie mogą wymagać natychmiastowej reakcji — potrzebna procedura po stronie fundacji, nie tylko baner | M1/17 | 8 |
| Anulowanie zaplanowanych wysyłek jest regularnie pomijane → przypomnienia o odwołanych wizytach i telefony do koordynatora | M1/19 | 9 |
| Zmiana czasu przesuwa okno 24 h o godzinę i potrafi zamienić bezpłatne odwołanie w płatne — „wychodzi dwa razy w roku, zawsze na produkcji" | M1/24 | 10 |
| Dwa systemy równolegle = dwie prawdy o dostępności tego samego specjalisty; „podwójne rezerwacje są wtedy pewne, pytanie tylko ile" | M1/27 | 11 |
| Tożsamość specjalisty w trzech źródłach (WP + CPT + dwa ID Bookero) — „potrafi zjeść tyle samo, co sam model" | M2/1 | 16 |
| Statusy w Historii wizyt wyprowadzane z pozycji w tablicy — po podpięciu okaże się, że brakuje „oczekuje na płatność" i „anulowana przez koordynatora" | M2/6 | 18 |
| Automatyczne domknięcie po 48 h koliduje z zamknięciem okresu rozliczeniowego | M2/7 | 18 |
| Kafel wynagrodzenia na pulpicie i suma na ekranie rozliczeń **rozjadą się o kilkaset złotych**; pierwszym, kto to zgłosi, będzie psycholog | M2/12 | 20 |
| Stripe Connect zmienia moduł rozliczeń w całości + weryfikacja tożsamości 111 osób | M2/13, M4/1 | 20, 42 |
| Google Calendar dwukierunkowo: wizyta zapisana do Google wraca jako zajętość i **sama siebie blokuje** | M2/19 | 22 |
| Koszt SMS przy 111 specjalistach „potrafi przekroczyć budżet, zanim ktokolwiek to zauważy" | M2/20 | 23 |
| Błąd z §15 DECYZJE.md powtórzy się, jeśli agregacja pójdzie po specjalistach zamiast po czasie — **„wszyscy pacjenci dostaną tego samego prowadzącego, a ekran będzie wyglądał poprawnie"** | M3/7 | 30 |
| Prowizja 0% liczona per specjalista zamiast per usługa (dziś makieta zeruje dopiero gdy WSZYSTKIE usługi są bezprowizyjne) | M3/10 | 31 |
| Metryka „wejście na kalendarz → wizyta opłacona" wymaga zdarzeń analitycznych, których nikt nie zbiera — **osobne 8–12 h na instrumentację** | M3/18 | 34 |
| „Liczby wyglądają poprawnie niezależnie od tego, czy są pełne. **To najgorszy rodzaj pomyłki, bo nic nie sygnalizuje błędu**" | M3/19 | 34 |
| Wykresy w PDF: render headless wymaga utrzymywania przeglądarki na serwerze | M3/20 | 34 |
| Zmiana terminu opublikowanego, opłaconego cyklu — osobna ścieżka, której makieta nie pokazuje | M3/16 | 33 |
| Faktura wyższa niż rozliczenie = wizyta spoza grafiku; bez ścieżki dopisania koordynator utknie | M4/3 | 43 |
| Jeśli którykolwiek moduł zapisze decyzję z pominięciem dziennika, przy rozliczeniu dotacji **nie ma czym uzasadnić wydatku** | M4/6 | 44 |
| Suma decyzji za 30 dni potrafi wyjść ujemna (samo sprostowanie bez wpisu) — zachowanie zamierzone, ale wymaga podpisu na ekranie | M4/7 | 44 |
| Tożsamość pacjenta przy rezerwacji jako gość — bez rozstrzygnięcia liczba objętych pomocą będzie zawyżona | M4/8 | 45 |
| **Bookero nie miał tożsamości pacjenta** — liczba osób za okresy sprzed wdrożenia nieporównywalna, a to główna liczba w sprawozdaniu z dotacji | M4/23 | 50 |
| Rezerwacje sprzed wersjonowania nie mają wskazania wersji reguł — potrzebna wersja zerowa i migracja | M4/16 | 47 |
| Zamrożona reguła musi być czytelna po latach — taniej zapisać pełny zrzut niż odwołanie do wersji | M5/16 | 60 |
| Włączenie SMS przy codziennym przypomnieniu **wielokrotnie zwiększa rachunek**; koszt ujawni się dopiero na fakturze operatora | M4/18 | 48 |
| Kredyt za odsprzedany slot: wykrycie „ten sam slot, inny pacjent" komplikuje się przy innej usłudze/długości wizyty | M5/4 | 56 |
| Rejestracja pola nadawcy SMS trwa kilka dni do kilku tygodni | M5/6 | 57 |
| Aktualizacja .ics kapryśna w Google/Apple/Outlook | M5/7 | 57 |
| Podmiana szablonu, z którego korzystają już zaplanowane wysyłki | M5/8 | 58 |
| Makieta obiecuje płynniejszy zrzut ekranu, niż da się dowieźć — **„to jedyne miejsce, w którym makieta obiecuje płynniej, niż da się dowieźć"** | M1/25, M5 ust. | 11, 63–64 |
| **Bez decyzji o wideo nie da się tego zadania rzetelnie wycenić** | M5/15 | 60 |
| Reguła 24 h zegarowo to założenie makiety, nie decyzja regulaminowa | M5/17 | 61 |

## Ryzyko powtarzane w każdym module testowym (4×)

> „Trzy z 32 błędów znalezionych w przeglądzie regresji **wyglądały na ekranie zupełnie poprawnie** i ujawniło je dopiero policzenie — **testy muszą sprawdzać wartości, nie obecność elementów**." — M1/28 (s. 12), M2/24 (s. 24), M3/28 (s. 37), M4/24 (s. 50)

## Ryzyko dostępności

> „Fundacja pomocowa jest w praktyce **zobowiązana do dostępności wyższej niż przeciętny serwis komercyjny**; poprawianie tego po wdrożeniu kosztuje **wielokrotnie więcej** niż zrobienie od razu." — M1/29, s. 12

---

# 8. Moduł 6 — Wdrożenie, utrzymanie i zgodność (szczegółowo, s. 65–70)

**Zakres:** „Obejmuje wszystko, czego nie widać na żadnym ekranie, a bez czego system nie ruszy" (s. 65). **0 ekranów. 24 zadania, 132 podzadania. Ani jednego akapitu „Ryzyko"** — moduł jest w całości listą pominiętych obowiązków.

**Geneza modułu (s. 70):**
> „Ten moduł powstał z kontroli kompletności: **pięciu agentów opisało obszary widoczne na ekranach, szósty sprawdził, czego w tych opisach brakuje. Wszystkie zadania poniżej zostały pominięte przez każdego z nich.** Bez tego modułu rozpiska wyglądałaby kompletnie i miałaby dziury dokładnie tam, gdzie najdroższe — w rzeczach, których nie widać w interfejsie."

## Hosting / infrastruktura (zadanie 1, s. 65)

- **Trzy środowiska: dev, staging, produkcja** — osobne bazy, osobne klucze Stripe (test + prod)
- **„Konfiguracja serwera aplikacji, bazy i procesu roboczego pod PHP wskazany w DECYZJE §26 (obecna makieta jest frontendem, backend trzeba postawić od zera)"**
- Wdrożenie automatyczne z gałęzi: budowanie → migracje bazy → **przełączenie symlinkiem** → wycofanie jedną komendą
- Certyfikaty TLS, domeny, **przekierowania ze starych adresów Bookero**
- Przeniesienie bramek z `skrypty/` do CI: typy, `sprawdz-ekrany.mjs`, uruchomienie w **Chrome headless**, skan surowych bajtów sterujących (rozdz. 23 DECYZJE)
- **Osobne konto SMTP i osobny nadawca dla staging**, „żeby test przypomnień nie poszedł do pacjentów"
- Próba wdrożenia na staging z odtworzeniem produkcyjnego wolumenu danych

## Backupy (zadanie 2, s. 65)

- **Kopia bazy co godzinę, odtwarzanie do punktu w czasie, retencja 30 dni**
- Kopia plików użytkowników (faktury, zrzuty ekranu) **do magazynu poza serwerem aplikacji**
- **Szyfrowanie kopii, klucz przechowywany poza tym samym dostawcą**
- **Ćwiczenie odtworzeniowe** — pełne postawienie systemu z kopii na czystym środowisku, z pomiarem czasu: „**bez tego kopia jest deklaracją**"
- Runbook z podziałem ról (kto decyduje o odtworzeniu, kto informuje pacjentów)
- **Alert, gdy kopia nie powstanie — „cisza jest tu najczęstszym trybem awarii"**

## Monitoring (zadanie 3, s. 65)

- Zbieranie wyjątków z frontendu i backendu z kontekstem roli i trasy, **bez treści wizyt**
- Kontrola dostępności ścieżek: strona główna, `/szukaj`, checkout, webhook Stripe
- **Alerty progowe:** nieudane webhooki Stripe, kolejka maili rosnąca ponad N, nieudane SMS-y, **zadania okresowe opóźnione ponad 15 minut**
- Panel wskaźników: czasy odpowiedzi API, liczba rezerwacji na godzinę, **saldo blokad koszyka**
- Kanał alertów (mail + SMS do wykonawcy) i zasada, kto reaguje poza godzinami pracy
- Dziennik zdarzeń z retencją i **bez danych o zdrowiu**

## Kolejka zadań okresowych (zadanie 4, s. 66)

Pełna lista cronów: przypomnienia 24 h i 2 h, awanse z listy rezerwowej, wygaszanie blokad koszyka, domykanie wizyt po 48 h, wygaszanie linków płatności po 2 dniach, zamykanie okresu rozliczeniowego.
- **Klucze idempotencji** — ponowione zadanie nie może wysłać drugiego SMS-a ani awansować dwóch osób na jedno miejsce
- Blokada współbieżna na slocie i na miejscu w grupie
- Polityka ponowień z narastającym odstępem + **kolejka martwych zadań** do przeglądu
- **Zegar zatrzymywany 21:00–8:00 dla okna przyjęcia z listy rezerwowej — „reguła istnieje w komentarzu, nie w kodzie"**
- Testy zadań na przesuniętym zegarze, łącznie ze zmianą czasu

## Bezpieczeństwo i pentest (zadanie 5, s. 66)

- Przegląd autoryzacji API pod kątem odwołań do cudzych obiektów; **identyfikatory `NP-` + numer arytmetyczny (`terminy.ts:141`) muszą stać się nieodgadywalne albo chronione sprawdzeniem właściciela przy każdym zapytaniu**
- Rate limiting na magic link, kod SMS i formularz zgłoszenia — „inaczej link do logowania jest **darmową bramką do wysyłki maili**"
- Nagłówki bezpieczeństwa, CSRF w formularzach personelu, **polityka treści dla osadzonego pokoju wideo**
- Kontrola plików (faktury PDF, zrzuty): typ, rozmiar, składowanie poza katalogiem publicznym
- Skan zależności i tryb aktualizacji krytycznych łatek
- **Zewnętrzny test penetracyjny przed startem** + „godziny na poprawki, nie na sam test"
- Procedura zgłaszania podatności i kontakt

## Migracja (zadania 6, 7, 8, s. 66–67)

**6. Migracja kont i profili z WordPressa** — patrz sekcja 3 (kluczowe architektonicznie).

**7. Wyłączenie Bookero i demontaż integracji:**
- Wyłączenie **crona synchronizującego co 60 s**, usunięcie **Circuit Breakera i obu warstw cache**
- Usunięcie wtyczki `bookero-init.js` z frontu, podmiana kalendarza na własną siatkę
- Przekierowania ze starego widżetu i z linków w mailach Bookero
- **Eksport pełnej historii z obu kont SaaS (`5tu8AC22Akna`, `hxRnUexTsSvc`) do archiwum przed wygaszeniem abonamentów**
- Okres pracy równoległej z jasną zasadą, który system przyjmuje nowe rezerwacje, i **monitorowaniem podwójnych zapisów**
- Wypowiedzenie abonamentów i **potwierdzenie usunięcia danych po stronie dostawcy**

**8. Anonimizacja danych w środowiskach nieprodukcyjnych:**
- Skrypt maskujący imiona, nazwiska, e-maile, telefony i treści wiadomości przy kopiowaniu produkcji na staging/dev
- **Zachowanie proporcji i rozkładów** (rozdz. 15 DECYZJE: 32 błędy, z czego 3 wynikały ze złych proporcji danych)
- Zestaw danych demonstracyjnych do pokazów i szkoleń z tego samego generatora
- **Zakaz techniczny na wysyłkę maili i SMS-ów ze środowisk nieprodukcyjnych** (przechwytywanie wysyłki)
- Wpisanie procedury do runbooka i sprawdzenie na pierwszej kopii

## RODO i zgodność (zadania 9, 10, 11, s. 67)

**9. Dokumentacja RODO poza kodem:**
- Rejestr czynności przetwarzania dla trzech ról i dla danych art. 9 (zgoda z `Rezerwacja.tsx:166`)
- **DPIA — „przy danych o zdrowiu i profilowaniu dopasowania specjalisty jest wymagana, nie opcjonalna"**
- **Umowy powierzenia z każdym podprocesorem:** Stripe, bramka SMS, dostawca poczty transakcyjnej, dostawca pokoi wideo, hosting, magazyn kopii zapasowych (w innych modułach dochodzi Google Calendar — s. 49)
- Polityka retencji z konkretnymi okresami (rezerwacje, zgody, ślad audytowy, faktury, zgłoszenia ze zrzutami) **i zapisanie tych okresów w kodzie jako zadania czyszczące**
- **Upoważnienia do przetwarzania dla 111 specjalistów i koordynatorów**, z obiegiem przy dołączaniu i odejściu
- **Procedura zgłoszenia naruszenia w 72 h**, z listą kontaktów i szablonem zawiadomienia
- Klauzule informacyjne w checkoucie, w koncie pacjenta i w formularzu pierwszego kontaktu

**10. Regulamin, polityka prywatności, cookies:**
- Podstrony regulaminu i polityki — **„dziś to zdanie bez odnośnika"**
- **Wersjonowanie regulaminu i zapisywanie w rezerwacji zaakceptowanej wersji — analogicznie do `regula_anulacji_zamrozona`**
- Baner cookies z rozdzieleniem niezbędnych i analitycznych
- Obieg zmiany regulaminu: powiadomienie, ponowna akceptacja, obsługa osób, które nie zaakceptowały
- **Sprawdzenie treści przez prawnika fundacji**

**11. Notatki z sesji:** decyzja o zakresie, krąg dostępu, okres przechowywania, zdjęcie obietnicy z `Pulpit.tsx:179` jeśli poza zakresem. **„Jeśli w zakresie: osobne szacowanie 44 h (szyfrowanie na poziomie rekordu, dziennik dostępu, eksport na żądanie pacjenta, wyłączenie z eksportu ogólnego)."** — jedyna liczba godzin w całym dokumencie oprócz wzmianek 16–24 h (Bookero) i 8–12 h (instrumentacja analityczna).

## Dostępność i responsywność (zadania 12, 13, s. 68)

**12. Audyt WCAG 2.1 AA paneli:** siatka grafiku „godziny × ludzie" z obsługą klawiatury i rolami tabeli; tabele z sortowaniem (powiązanie nagłówków, komunikaty o liczbie wyników); modale (`Modal.tsx`) — pułapka ogniska, Escape, powrót ogniska; wykresy — alternatywa tekstowa i tabela; **kontrasty w hover/focus, w tym zieleń marki `#01be4a` o kontraście 2,42 : 1, która „nie może nieść znaczenia sama"**; test z czytnikiem ekranu na trzech przepływach.

**13. Responsywność:** boczne menu o stałej szerokości (`LayoutPanelu.tsx:146`) → wysuwane poniżej progu tabletu; tabele → układ kartowy zamiast poziomego przewijania; **grafik zespołu na telefonie („koordynator odbiera telefony w drodze")**; panel specjalisty na telefonie („oznaczanie wizyty to czynność między sesjami, nie przy biurku"); **testy na realnych urządzeniach, nie w trybie responsywnym przeglądarki**.

## Testy (zadanie 14, s. 68)

- **Profil ruchu ustalony z fundacją**: liczba rezerwacji dziennie, szczyt godzinowy, liczba równoczesnych koordynatorów
- Scenariusze: wyszukiwarka, widok miesięczny (35 dni × 111 osób), raport grantowy za rok
- **Budżety czasowe i bramka w CI, która nie przepuszcza regresji**
- Przegląd indeksów bazy
- **Test 100 równoczesnych żądań na jeden termin**
- Raport z listą wąskich gardeł do naprawy **przed startem**

## Dokumentacja, szkolenia, przekazanie (zadania 15–18, s. 68–69)

**15. Materiały:** instrukcja specjalisty (dwa poziomy dostępności, oznaczanie, rozliczenia, faktura), instrukcja koordynatora, krótkie odpowiedzi dla pacjentów, **nagrania ekranu 5 najczęstszych czynności („111 osób nie przeczyta instrukcji tekstowej")**, karta „co robić, gdy system nie działa".

**16. Pilotaż i szkolenia:** **pilotaż na 10 specjalistach przez dwa tygodnie** przed otwarciem dla całego zespołu; dwie sesje szkoleniowe online z nagraniem + sesja pytań tydzień później; **osobne szkolenie koordynatorów — „mają dwadzieścia razy więcej funkcji niż specjalista"**; zebranie uwag i poprawki przed otwarciem.

**17. Przekazanie:** schemat bazy z opisem pól zamrażanych; opis integracji z listą kluczy i miejscem przechowywania; runbook (wdrożenie, wycofanie, odtworzenie, typowe awarie); **„Przekazanie dostępów do wszystkich kont zewnętrznych na fundację, nie na wykonawcę"**; sesja przekazania z osobą techniczną fundacji.

**18. Stabilizacja — cztery tygodnie:** dyżur z podwyższoną gotowością; codzienny przegląd nieudanych płatności, wysyłek i zawieszonych cronów; poprawki na realnym ruchu („zawsze są, i zawsze nie te przewidziane"); **cotygodniowy raport dla fundacji: co się zepsuło, co naprawiono, czego brakuje**; domknięcie i przejście na zwykłe utrzymanie.

## Funkcje dobudowywane w module 6 (zadania 19–24, s. 69–70)

**19. Opinie o specjaliście:** model powiązany z konkretną odbytą rezerwacją („bez tego »zweryfikowana rezerwacja« pod cytatem jest nieprawdą"), jednorazowo na rezerwację, **moderacja koordynatora przed publikacją** („opinia bez moderacji to ryzyko, którego fundacja nie uniesie"), średnia i stan pusty, zgłaszanie i usuwanie ze śladem, **wpływ oceny na sortowanie — do rozstrzygnięcia**.

**20. Zapis na listę oczekujących:** wejście z pustego stanu `/szukaj` i z profilu bez terminów; formularz (specjalista/dowolny, usługa, zgoda na termin w 24 h — `krotkiTermin` w `ListaRezerwowa.tsx` już tego oczekuje); podłączenie do kolejki koordynatora, **która dziś jest wypełniana wyłącznie ręcznie**; widok „czekam na termin" i wypisanie się; potwierdzenie mailem z informacją o oknie 4 h.

**21. Obsługa błędów interfejsu:** 404 (jest w makiecie, nie było w zakresie), 403 przy wejściu w cudzą rezerwację, wygasły magic link z wysyłką nowego jednym kliknięciem, **utrata połączenia w trakcie płatności — „najgorszy moment na komunikat techniczny"**, jednolity ekran awarii z numerem sprawy.

**22. System projektowy paneli:** przeniesienie `tokens.css` i `app.css` do produkcji z zachowaniem aliasów `--psy-*`; komponenty wielokrotnego użytku; kroki koloru typu płatności jako osobny kanał; **sprawdzenie kontrastów walidatorem, nie okiem (jak przy `#0f8f43` i `#3a45cf`)**; arkusz druku raportów (klasa `bez-druku` już jest).

**23. „Pomoc w kryzysie":** podpięcie odnośnika z nagłówka pod realną treść zamiast `preventDefault()`; numery **112, 116 111, 800 70 22 22, 800 12 12 12** dostępne z każdego ekranu, także w panelach; klikalne numery na telefonie; **„Sprawdzenie, że treść wyświetli się nawet przy awarii aplikacji"**.

**24. Poprawki na produkcji niepodzielni.com:** renderowanie pola złożonego **Carbon Fields** („Tryb konsultacji: `field_complex`"); normalizacja cen do postaci z „zł" na dwóch profilach; sprawdzenie pozostałych profili pod kątem tej samej niespójności.

---

# 9. Pozostałe kwestie architektonicznie istotne

## Skala i dane

- **111 specjalistów** (7 profili opisanych + 104 wygenerowane deterministycznie, identyfikatory ≥ 100). Pacjent widzi w wyszukiwarce **tylko opisane profile** — tylko one mają biogramy (s. 14, 38)
- **Ekrany Psycholodzy, Pacjenci i ranking obłożenia liczą dziś tylko 7 profili — w prawdziwym systemie muszą obsłużyć cały zespół** (s. 41)
- Skala danych: ~2700 wizyt na pięć miesięcy (s. 59), kilkanaście tysięcy wizyt rocznie (s. 50), 222 faktury przy 111 osobach × 2 okresy (s. 50)
- Godziny pracy 08:00–19:00, **12 slotów dziennie**; niedziela wolna dla wszystkich, sobota wolna dla specjalistów o parzystym identyfikatorze (s. 38)
- „Dane makiety muszą mieć wiarygodne proporcje (…). Czternaście nazwisk na 2758 wizyt dawało ~197 wizyt na osobę i licznik pokazywał »64 z 4« — **ekran działał, tylko nic nie znaczył**" (s. 41)

## Jedno źródło prawdy — powtarzany wymóg architektoniczny

Dokument wielokrotnie żąda **jednej funkcji obsługującej wiele ekranów**:
- „Wystawienie **jednej funkcji slotów** obsługującej panel, wyszukiwarkę pacjenta i grafik koordynatora" (s. 17)
- „**Jedna funkcja rozstrzygająca zwrot, możliwość przełożenia i płatność godziny, wołana przez wszystkie moduły — żaden ekran nie decyduje samodzielnie**" (s. 47)
- „Cała polityka odwołań żyje w jednej funkcji `ocenaAnulacji()` (…). Przy wdrożeniu ta sama zasada musi obowiązywać **po stronie serwera**" (s. 64)
- „Przeniesienie licznika terminów tygodniowo (…) na wynik z serwera, **żeby dwa ekrany nie liczyły tego samego dwiema metodami**" (s. 17)
- „Udostępnienie **macierzy odwołań jako danych**, tak żeby ekran koordynatora czytał ją z tego samego źródła, z którego liczy system" (s. 47)
- „Wspólna infrastruktura powiadomień, dzielona z modułem pacjenta i koordynatora — **godziny liczyć raz**" (s. 23)

## Pola, bez których system się rozjeżdża (DECYZJE §4, s. 63–64)

1. **`kwota_zamrozona`** — bez niej podniesienie cennika sprawia, że stare zwroty przestają zgadzać się ze Stripe
2. **`regula_anulacji_zamrozona`** — bez niej zmiana okna z 24 na 48 h działałaby wstecz
3. **`Poprawka` jako osobny byt** — żeby wyklikanie jednej godziny nie rozbijało rytmu na setki rekordów

Dodatkowe pola z modelu specjalisty (s. 16): `stripe_payment_intent`, `liczba_przelozen`.

## Rozdział DTO / serializacji per rola

> „Rozdzielenie serializacji na **DTO specjalisty (bez prowizji i kwoty pacjenta) i DTO koordynatora**" (s. 16)
> „Osobne reprezentacje danych dla ról: odpowiedź kierowana do psychologa **nie zawiera prowizji ani kwoty zapłaconej przez pacjenta w żadnym polu**" (s. 48)
> „Test regresyjny sprawdzający, że odpowiedź API dla roli psycholog nie zawiera pól prowizji" (s. 20)

To wymóg architektoniczny, nie kosmetyczny — ukrycie kolumny w interfejsie jest jawnie odrzucone jako niewystarczające.

## Warstwa repozytorium filtrująca po właścicielu

> „Zbudowanie warstwy repozytorium filtrującej po zalogowanym specjaliście, **żeby ograniczenie nie zależało od pamięci autora kontrolera**" (s. 16)

## Architektura raportowania i niezmienialności

- **Dziennik decyzji:** tabela bez UPDATE i DELETE, **z odebraniem tych uprawnień roli bazodanowej aplikacji** (nie tylko brakiem przycisku), numeracja `DEC-rok-numer` odporna na równoległe zapisy, **łańcuch skrótów kryptograficznych** (s. 44). Test: „próba UPDATE i DELETE kończy się błędem **na poziomie bazy, nie tylko aplikacji**" (s. 50)
- **Snapshoty okresów:** zamknięcie okresu rozliczeniowego i zamrożenie raportu kwartalnego jako snapshot niezmienny przy późniejszych korektach (s. 42, 45)
- **Kluczowy test poprawności raportu:** „suma osób z czterech kwartałów jest **WIĘKSZA** niż liczba osób w roku, a nie jej równa" (s. 50)

## Anonimizacja i obieg makiety

> „Profile siedmiu prawdziwych specjalistów zostały zanonimizowane, bo **makieta krąży dalej niż strona** — trafia na prezentacje i na dysk każdego, kto ją otworzy. Osoba, która zgodziła się na profil na stronie fundacji, nie zgodziła się na występowanie w makiecie systemu, który jeszcze nie istnieje." (s. 14, 41, 53)

## Adresy i kontakty w systemie

- Zgłoszenia koordynatora → **info@mixturemarketing.pl** (wykonawca); pacjenta i specjalisty → **kontakt@niepodzielni.com** (fundacja) — s. 63
- Telefon wsparcia **+48 22 123 45 67**, pon.–pt. 8:00–18:00 (s. 12)
- Gabinety: **Hoża, Wilcza, Kraków** — jako dane z obłożeniem sal, nie stała w kodzie (s. 22)

## Ograniczenia prezentacji zapisane jako reguły (s. 38)

60 wierszy w tabeli rezerwacji (z komunikatem ile ukryto), 25 pacjentów w kartotece, 12 pozycji kolejki rezerwowej, 8 specjalistów na stronę w widoku dziennym, 3 propozycje dopasowania, 4 godziny na dzień w propozycji terminu, 5 wolnych godzin na karcie specjalisty w wyszukiwarce.

## Kolorystyka jako decyzja systemowa (s. 39, 64, 69)

Typ płatności ma **własny kanał kolorystyczny** (`#0f8f43` niskopłatne, `#3a45cf` pełnopłatne), bo zieleń marki znaczy „akcja/sukces", a granat „nagłówek/tożsamość". Kroki dobrane **walidatorem kontrastu, nie okiem**. Kolor nigdy nie występuje sam. Przełącznik trójstanowy, nie dwustanowy.

## Rzeczy świadomie poza zakresem

- Historia finansowa i sumy wydatków w panelu pacjenta (decyzja 17) — „zdejmuje z zakresu **cały moduł historii płatności po stronie pacjenta**" (s. 64)
- Pakiety i serie wizyt (decyzja 8)
- Kody rabatowe i vouchery (nie zapadła decyzja)
- Notatki z sesji i dokumentacja terapeutyczna (kwestia 12)
- Dwukierunkowy sync z Google Calendar (s. 36)
- Generowanie faktury za specjalistę (dokument musi wyjść z jego księgowości)
- Automatyczne zwroty przez API Stripe
- **Migracja danych z obecnych narzędzi fundacji nie jest ujęta w tym dokumencie** — „Zakres tej pracy zależy od tego, co i w jakiej postaci trzeba przenieść — ustalimy to osobno" (s. 2). Uwaga: mimo tego zastrzeżenia migracja pojawia się jako zadania M1/27, M4/23, M6/6, M6/7 i M6/8
- **Dokument nie zawiera wyceny ani harmonogramu** — „te powstają osobno, po ustaleniu kolejności wdrażania modułów" (s. 2)

---

## Podsumowanie dla decyzji architektonicznej

1. **Dokument zakłada backend PHP budowany od zera** (jedyne wskazanie: „PHP wskazany w DECYZJE §26", s. 65), z endpointami REST (`/panel/*`, `/koordynacja/*`), procesem roboczym i kolejką zadań.
2. **Tożsamość i profile pozostają w WordPressie** — jednoznaczne zdanie: „**Nie trzeba budować osobnego systemu użytkowników**" (s. 41). Rola `psycholog` + CPT + `post_author` + 4 taksonomie są traktowane jako istniejący fundament w repozytorium `Niepodzielni-dev`.
3. **Największa zmiana to rola pacjenta**, której w WordPressie fundacji nie ma (s. 41, 58, 64).
4. **Frontend to SPA osadzone w motywie WordPress** (s. 11), z routingiem po ścieżkach i regułami przepisywania na serwerze.
5. **Nie ma ani jednego zdania o wtyczce `niepodzielni-core`, o osobnej aplikacji, o frameworku ani o SSO/hubie.** Jedyne miejsce dopuszczające wyjście poza WP to zadanie „Migracja kont i profili z WordPressa" (s. 66) — i ono samo stawia to jako **otwarte pytanie**: „Ustalenie kierunku synchronizacji profilu: czy wp-admin pozostaje źródłem prawdy, czy przejmuje to nowy panel."
6. **Do rzetelnej wyceny brakuje czterech decyzji blokujących** (widełki, Bookero w dniu przełączenia, Stripe Connect, źródło linku wideo) oraz rozstrzygnięcia limitu 10 vs 4 i okna 10 min / 2 dni / 24 h.
