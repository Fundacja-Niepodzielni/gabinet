# Gabinet — system rezerwacji Fundacji Niepodzielni

Kontekst stały. Przeczytaj w całości przed pierwszą zmianą. Nie relityguj podjętych decyzji.

## Co budujemy

System rezerwacji wizyt psychologicznych: **osobna aplikacja Laravel** będąca **źródłem prawdy** o specjalistach (opisy, specjalizacje, obszary pomocy, terminy), rezerwacjach, rozliczeniach i wydarzeniach grupowych. WordPress (niepodzielni.com) **tylko wyświetla** te dane po API. Zastępuje SaaS Bookero. Frontend: istnieje **makieta React** (`Fundacja-Niepodzielni/gabinet-makieta`) — **zmierzone 39 unikalnych tras**, nie 61; liczba 61 stała tu bezpodstawnie, a commit autora makiety mówi 47 (trzy różne liczby, żadna uzgodniona). **Repozytorium makiety zawiera WYŁĄCZNIE plik zbudowany** (jeden `index.html` ~1,7 MB z wbudowanym Reactem); jego README mówi „Kod źródłowy jest osobno". **Nie jest to więc „gotowa makieta do podpięcia"** — dopóki nie ma źródeł, jest wzorcem wyglądu i zachowania, nie kodem do rozwijania. Ustalenie miejsca źródeł czeka na właściciela.

**Specyfikacja nadrzędna:** `docs/specyfikacja/` — dwa dokumenty („Jak działa system" 60 s. — zachowanie; „Zakres wdrożenia" 70 s. — 6 modułów / 150 zadań / 916 podzadań) + streszczenia. Wszystko, czego nie rozstrzyga CLAUDE.md, rozstrzyga specyfikacja.

## Stack (decyzje zapadłe — nie zmieniać)

- **Laravel 13** (PHP 8.4) + **PostgreSQL** + **Redis** (kolejki: Horizon) — jak chat control-plane i mindscape-backend
- Testy: Pest; statyka: Larastan + Pint; API dokumentowane (Scramble)
- Docker Compose dla dev; sekrety wyłącznie w `.env` (nigdy w repo; `.env.example` bez wartości)
- Frontend: makieta React/Vite (źródła dojdą — do tego czasu backend-first, kontrakty API wg ekranów specyfikacji)

## Twarde zasady (MUST / MUST NOT)

1. **Serwer jest jedynym rozstrzygającym.** Każda reguła (okno 24 h, limity, zwroty) egzekwowana w API; frontend tylko chowa przyciski. Jedna funkcja rozstrzygająca na regułę, wołana przez wszystkie moduły.
2. **Logowanie wyłącznie przez Konta Niepodzielni (Keycloak).** Personel: OIDC wg wzorca `konta/tests/ref-laravel` + `konta/docs/INTEGRACJA-KONTRAKT.md`; wiązanie po `sub`, nigdy po e-mailu; role z access tokena. Pacjenci: konta przez Keycloak (rola `pacjent`; tworzenie w tle przez Admin API + link aktywacyjny action-token). Guest checkout bez konta. **ŻADNYCH własnych haseł w tym systemie.**
3. **Dwa konta Stripe:** fundacyjne (niskopłatna 55 zł, asystent zdrowienia 0 zł) i komercyjne (pełnopłatna 115/125/135/145 zł, diagnoza ADHD 350 zł). Każda usługa ma flagę `fundacja/komercja`; osobne webhooki i rekoncyliacja per konto. Prowizje konfigurowalne per usługa.
4. **Zamrażanie w chwili zakupu:** `kwota_zamrozona`, `regula_anulacji_zamrozona` (pełny zrzut, nie referencja), wersja regulaminu. Zmiana cennika/reguł NIGDY nie działa wstecz.
5. **`Poprawka` slotu to osobny byt** (nie modyfikacja rytmu). Trzy warstwy dostępności: rytm tygodniowy → poprawki → urlopy (wygrywają ze wszystkim). Bufor 10 min. UTC w bazie, prezentacja Europe/Warsaw.
6. **Współbieżność przez bazę:** unikalne ograniczenie `(specjalista_id, termin)` + transakcje z blokadą wiersza. Test 100 równoczesnych żądań na jeden termin — obowiązkowy.
7. **Zwroty: NIGDY „zwrot wykonany" przed webhookiem `charge.refunded`.** Zwroty to lista zadań dla koordynatora (bez API Stripe do zwrotów). Idempotencja webhooków po ID zdarzenia; kolejność zdarzeń Stripe niegwarantowana.
8. **Psycholog nie widzi prowizji ani kwoty pacjenta** — osobne DTO per rola, egzekwowane testem regresyjnym na poziomie API (nie ukrywaniem w UI). Warstwa repozytorium filtruje po właścicielu.
9. **Dziennik decyzji uznaniowych: tylko INSERT** — odebranie UPDATE/DELETE roli bazodanowej aplikacji, łańcuch skrótów, sprostowanie = nowy wpis. Test na poziomie bazy.
10. **RODO art. 9:** DPIA-checklista PRZED modelem danych (Faza 1 planu). Szyfrowanie kolumn wrażliwych, log dostępu do kartotek, retencje jako zadania czyszczące w kodzie, anonimizacja danych na dev/staging, twarda blokada wysyłki mail/SMS ze środowisk nieprodukcyjnych.
11. **Raport grantowy liczy OSOBY, nie wizyty** — suma osób z 4 kwartałów MUSI być większa niż liczba osób w roku (test kluczowy). Zamknięte okresy = niezmienny snapshot.
12. **Endpoint `GET /api/hub-summary`** — dane operacyjne bez danych osobowych. Kontrakt `hub/docs/KONTRAKT-HUB-SUMMARY.md` **na 09.08.2026 NIE ISTNIEJE** (zmierzone; wcześniej ta zasada twierdziła, że istnieje). Do czasu jego powstania **nie implementuj endpointu wg domysłu** — kształt pola i zakres danych rozstrzyga hub, nie my.
13. **Zgłoszenia problemów → Zammad** (repo `helpdesk`), nie własny rejestr.
14. Limity i okna czasowe = **konfiguracja w bazie z wersjonowaniem** (limit niskopłatnych — wartość startową wskaże zarząd; okno linku płatności 2 dni + 10 min trzymania slotu po otwarciu płatności).
15. Testy **liczą wartości, nie obecność elementów** (3 z 32 błędów makiety wyglądały poprawnie na ekranie).

## Decyzje zapadłe (nie otwierać ponownie)

Wideo: **pokoje generowane per wizyta** (dostawca: porównaj Jitsi self-host vs Whereby w F0, rekomendacja przed F3). SMS: **SMSAPI** (rejestracja nadawcy „Niepodzielni" — wniosek na starcie prac). Rozliczenia ze specjalistami: **przelewy miesięczne** (Stripe Connect odrzucony na start; decyzja fundacji w toku — buduj pod przelewy). Cutover Bookero: przełom miesiąca. Wydarzenia: Gabinet źródłem prawdy. Brak pakietów wizyt, brak historii finansowej pacjenta, notatki z sesji POZA zakresem pierwszego wdrożenia.

## Czego NIE wolno

- Wdrażać produkcyjnie ani wystawiać publicznie **bez wyraźnej zgody użytkownika** (dev — pełna swoboda).
- Zapisywać sekretów do plików. Commitować `.env`.
- Dotykać starego repo `System-rezerwacji` (zarchiwizowane; NIE wzorzec).
- Zmieniać realm Keycloak lokalnie „na skróty" — zmiany realm przechodzą przez repo `konta` (klient Gabinetu do zarejestrowania tam).

## Jak pracujemy

Patrz `WYTYCZNE-PRACY.md` (kultura, weryfikacja, agenci) i `PLAN-FAZ.md` (fazy, bramki, kryteria akceptacji). Stan bieżący między sesjami: sekcja `CURRENT WORK` w `PLAN-FAZ.md`. Decyzje projektowe: dopisuj do `docs/DECYZJE.md` (data, treść, uzasadnienie) — raz podjętych nie relityguj.
