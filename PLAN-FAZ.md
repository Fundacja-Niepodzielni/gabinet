# Plan faz — Gabinet

Fazy wykonywane po kolei; bramka fazy musi być zielona i **niezależnie zweryfikowana** przed wejściem w następną. Mapowanie na moduły specyfikacji w nawiasach. Rozmiary: S (dni), M (1–2 tyg.), L (kilka tyg.).

## CURRENT WORK

> (aktualizuj na koniec każdej sesji: bieżąca faza, zadania w toku, blokery, następny krok)

- **Faza: F0 — w toku** (sesja 1, 2026-08-07)
- Blokery zewnętrzne: źródła makiety (dostarczy właściciel); wartość startowa limitu niskopłatnych (zarząd); potwierdzenie „przelewy miesięczne" po rozmowie fundacji; wybór dostawcy wideo (decyzja właściciela po dokumencie z F0.7)

### Rozpiska zadań F0

| # | Zadanie | Kryterium „zrobione" | Stan |
|---|---|---|---|
| F0.1 | Szkielet Laravel 13 (PHP 8.4) w `backend/`, wersje przypięte, lockfile w repo | `composer install` z lockfile'a przechodzi; `php artisan --version` = Laravel 13.x | ⬜ |
| F0.2 | Docker Compose (dev): app (PHP-FPM 8.4), nginx, PostgreSQL, Redis, Horizon, scheduler | `docker compose up -d` → wszystkie kontenery `healthy`; `GET /up` = 200; Horizon widzi Redis | ⬜ |
| F0.3 | Pest + Larastan (max) + Pint; test pozytywny i negatywny na szkielecie | `pest`, `larastan`, `pint --test` zielone lokalnie i w kontenerze | ⬜ |
| F0.4 | CI GitHub Actions: Pint, Larastan, Pest (usługi PG+Redis), gitleaks (`GITLEAKS_LICENSE` z sekretów org) | workflow zielony na pustym szkielecie | ⬜ |
| F0.5 | `.env.example` — komplet nazw zmiennych bez wartości (Keycloak, 2× Stripe, SMSAPI, poczta, wideo) | brak wartości sekretów; `.env` w `.gitignore`; gitleaks czysty | ⬜ |
| F0.6 | `docs/DECYZJE.md` — założony rejestr, przeniesione decyzje zapadłe + decyzje tej sesji | plik istnieje, format: data / decyzja / uzasadnienie / skutek | ⬜ |
| F0.7 | Porównanie **Jitsi self-host vs Whereby** — koszty, ryzyka, rekomendacja | dokument w `docs/analizy/`; rekomendacja jednoznaczna; decyzja właściciela przed F3/F4 | ⬜ |
| F0.8 | Lokalny Keycloak (stos z repo `konta`) + rejestracja klienta `gabinet` **w repo `konta`** + logowanie testowym kontem wg wzorca `ref-laravel` | test E2E: `test-psycholog`/`test-pacjent` loguje się do Gabinetu, role z access tokena, `aud` walidowane, test negatywny cudzego tokena = 401 | ⬜ |
| F0.9 | Bramka F0 zielona + **niezależna weryfikacja** (osobny agent, czysty checkout) | raport weryfikatora bez czerwonych | ⬜ |
| F0.10 | Przypomnienie właścicielowi: wniosek o nadawcę SMS „Niepodzielni" w SMSAPI | wpis w raporcie sesji + w `docs/DECYZJE.md` jako zadanie człowieka | ⬜ |

- Następny krok: F0.1 → F0.7 (backend-first), potem F0.8 (Keycloak) i bramka F0.9.

## F0 — Fundament (S)

Szkielet Laravel 13 + PostgreSQL + Redis + Horizon w Docker Compose (dev) · Pest/Larastan/Pint + CI (GitHub Actions: testy, statyka, gitleaks z `GITLEAKS_LICENSE` z sekretów org) · lokalny Keycloak do developmentu: klon repo `Fundacja-Niepodzielni/konta`, uruchomienie wg jego README, import realm · `.env.example` · `docs/DECYZJE.md` założony · **porównanie Jitsi self-host vs Whereby** (dokument z rekomendacją i kosztami — decyzja właściciela przed F4) · przypomnienie właścicielowi: wniosek o nadawcę SMS „Niepodzielni" w SMSAPI.
**Bramka:** `docker compose up` stawia całość; CI zielone na pustym szkielecie; logowanie testowym kontem z lokalnego Keycloaka działa (ref-laravel wzorzec); rekomendacja wideo zapisana.

## F1 — DPIA-checklista, model danych, reguły jako konfiguracja (M) — [spec: M1/1, M4/16, M5/16-17]

NAJPIERW DPIA-checklista (art. 9: co zbieramy, po co, retencje, dostępy) — wynik wpływa na model · migracje: pacjent, specjalista (klucz: `sub` z Keycloak), usługa (flaga fundacja/komercja, prowizja per usługa), rezerwacja (`kwota_zamrozona`, `regula_anulacji_zamrozona` jako pełny zrzut), zgoda (wersjonowana), zdarzenie (append-only), konfiguracja reguł z wersjonowaniem i datą obowiązywania · jedna funkcja rozstrzygająca zwrot/przełożenie/płatność godziny · strefy czasowe (UTC + Europe/Warsaw, testy na dobach 23/25 h).
**Bramka:** testy tabelaryczne reguł na wartościach granicznych (23:59/24:00/24:01); seed o wiarygodnych proporcjach (111 specjalistów, kilkanaście wizyt/pacjenta); migracje w górę i w dół.

## F2 — Silnik dostępności (L) — [spec: M1/2-4, M2/2-5; „najbardziej niedoszacowany element projektu"]

Trzy warstwy (rytm/poprawki/urlopy) · sloty 50+10 i 90+10 (90-min zdejmuje dwa sloty) · horyzonty 2 h / 30 dni / 7 dni · limit 4 niskopłatnych/tydzień (ISO, reset poniedziałek) · jedna funkcja slotów dla panelu (7 d × 1 os.), wyszukiwarki (30 d × 111) i grafiku (35 d × 111) · materializacja/cache z unieważnianiem per specjalista/dzień · API wyszukiwarki z filtrami (bez N+1).
**Bramka:** testy — zmiana czasu, urlop nachodzący na rytm, poprawka spoza rytmu, kolizja 90-min; wydajność < 300 ms na seedzie 111 osób; **test 100 równoczesnych żądań o ten sam termin = dokładnie jedna rezerwacja**.

## F3 — Rezerwacja + płatności (L) — [spec: M1/6-14, M5/1-4]

Blokada terminu 10 min (atomowo, cron zwalniający) · Stripe Checkout ×2 konta (routing po fladze usługi; karta/BLIK/GPay/APay; 0 zł omija Stripe) · webhooki z weryfikacją podpisu, idempotencją, kolejką ponowień · nocna rekoncyliacja per konto + widok rozjazdów · płatność odroczona (`/oplac/:token`, 2 dni, unieważnianie) · zwroty jako lista zadań (domykanie po `charge.refunded`) · kredyt za odsprzedany termin · odwołanie/przełożenie z egzekwowaniem okna 24 h i limitu 2 zmian · konta pacjentów przez Keycloak Admin API + action-token (konto w tle po płatności).
**Bramka:** E2E w trybie testowym Stripe: rezerwacja→płatność→potwierdzenie; odwołanie przed/po granicy; „zapłacone, termin zajęty" jako osobna ścieżka; webhook zwrotu przed webhookiem płatności nie psuje stanu; token `/oplac` po zwolnieniu terminu.

## F4 — Panel specjalisty: API (M) — [spec: M2]

Endpointy `/panel/*` (DTO bez prowizji — test regresyjny) · dostępność (rytm/siatka/urlopy) · oznaczanie wizyt + auto-domknięcie 48 h + „odpuść tym razem" · odwołanie przez specjalistę (zwrot 100%, licznik 30 dni, alert >10 tylko dla koordynatora) · umawianie pacjenta z linkiem płatności / wnioskiem o zwolnienie z opłaty · rozliczenia (naliczanie wg 5 wariantów, zestawienie PDF serwerowo, obieg faktur z antywirusem i plikami poza katalogiem publicznym) · dwuetapowa zmiana rachunku (link na stary adres) · Google Calendar **tylko freeBusy**.
**Bramka:** test braku prowizji w KAŻDEJ odpowiedzi API dla roli psycholog; naliczanie do grosza na przypadkach z macierzy odwołań; PDF z polskimi znakami.

## F5 — Koordynator: operacje + rozliczenia (L) — [spec: M3, M4]

Grafik zespołu (3 widoki, agregaty, cel < 300 ms) · interwencje (odwołaj/przenieś/zmień specjalistę/„zwróć mimo reguły") · rezerwacja telefoniczna (3 tryby) · kolejka pierwszego kontaktu (6 pytań zamkniętych, art. 9: szyfrowanie + log odsłonięć telefonu) · silnik dopasowania + propozycja z blokadą 2 dni · lista rezerwowa z automatem (okno 4 h, zegar stoi 21:00–8:00) · wydarzenia grupowe (cykle, lista rezerwowa, awanse, obecności) · **dziennik decyzji append-only** (uprawnienia odebrane na poziomie bazy, łańcuch skrótów) · raport grantowy (osoby!, zamknięte kwartały jako snapshot) · katalog usług i cennik z wersjonowaniem · faktury: obieg akceptacji + paczka przelewów.
**Bramka:** testy negatywne uprawnień (para rola × zasób); próba UPDATE na dzienniku = błąd bazy; **suma osób z 4 kwartałów > osoby w roku**; belka trybu płatności zawęża KAŻDE zapytanie panelu.

## F6 — Powiadomienia (M) — [spec: M1/19-20, M5/5-9]

Silnik: tabela zaplanowanych + worker z ponowieniami; **przeplanowanie przy każdej zmianie terminu** (najdroższy element wg spec) · mail: dostawca transakcyjny + SPF/DKIM/DMARC, 7 szablonów w bazie (wersjonowane, biała lista zmiennych), .ics (stały UID, SEQUENCE, METHOD:CANCEL) · SMS przez SMSAPI (E.164, DLR, liczenie segmentów z twardym odcięciem, limit kosztów, bez treści zdrowotnych) · cisza nocna · macierz 7 zdarzeń × mail/SMS jako konfiguracja.
**Bramka:** E2E: zmiana terminu przeplanowuje przypomnienia (stare anulowane — test!); odwołana wizyta NIE dostaje przypomnienia; staging z twardą blokadą wysyłki na prawdziwe adresy.

## F7 — Frontend: podpięcie makiety (L) — [spec: M1/5,8,10-12,16,18,21,25-26,29; WYMAGA źródeł makiety]

Przepięcie ekranów z danych lokalnych na API (fazami: pacjent → specjalista → koordynator) · routing po ścieżkach · usunięcie paska demo i localStorage-ról · stany ładowania/błędów (404/403/wygasły link/utrata połączenia przy płatności) · WCAG 2.1 AA (siatki z klawiatury, kontrasty walidatorem, aria-live) · responsywność paneli · osadzenie wizualne w niepodzielni.com.
**Bramka:** `sprawdz-ekrany` w CI (Chrome headless); scenariusze E2E pełnej ścieżki pacjenta; audyt dostępności 3 przepływów.

## F8 — Integracje ekosystemu (M)

API dla WordPressa (profile, terminy — WP tylko wyświetla) · te same 3 endpointy rezerwacyjne co `strona/api/24-app-booking-endpoint.php` (aplikacja mobilna bez zmian) · `GET /api/hub-summary` wg kontraktu z repo `hub` · zgłoszenia problemów → skrzynka Zammad · rejestracja klienta Gabinetu w realm (przez repo `konta`, wraz z rolą `pacjent` w praktyce).
**Bramka:** WP renderuje listing z API Gabinetu na dev; aplikacyjny kontrakt 3 endpointów przechodzi testy kontraktowe.

## F9 — Migracja i produkcja (M) — [spec: M6] — **WYŁĄCZNIE za zgodą właściciela**

Import profili CPT+taksonomie z WP · wariant rezerwacji Bookero (decyzja: przełom miesiąca; szczegół do potwierdzenia) · środowiska prod na VPS-3 (plan: `_architektura/10`) · backupy z ćwiczeniem odtworzeniowym · monitoring + alerty · anonimizacja stagingu · pentest zewnętrzny · pilotaż 10 specjalistów 2 tygodnie · szkolenia · cutover Bookero + eksport archiwum + wypowiedzenie.
**Bramka:** pentest bez krytycznych; ćwiczenie odtworzeniowe z pomiarem czasu; pilotaż zakończony poprawkami; zgoda właściciela na każdy krok publiczny.

## Poza zakresem pierwszego wdrożenia (nie buduj)

Notatki z sesji · pakiety/serie wizyt · historia finansowa pacjenta · kody rabatowe (brak decyzji) · dwukierunkowy sync Google Calendar · automatyczne zwroty przez API Stripe · Stripe Connect.
