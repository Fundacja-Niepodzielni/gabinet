# System rezerwacji — nowa specyfikacja (08.2026) i werdykt architekta

Data: 07.08.2026. Podstawa: dwa dokumenty sporządzone 07.08.2026 „na podstawie działającej makiety":
- **„Jak działa system"** (60 s.) — opis funkcjonalny z perspektywy pacjenta/psychologa/koordynatora; streszczenie: [09a](09a-rezerwacje-digest-jak-dziala-system.md)
- **„Zakres wdrożenia"** (70 s.) — 6 modułów, 61 ekranów, 150 zadań, 916 podzadań; streszczenie: [09b](09b-rezerwacje-digest-zakres-wdrozenia.md)

Źródłowe PDF-y: `C:\Users\Jakub\Downloads\jak-dziala-system.pdf`, `zakres-wdrozenia (1).pdf`.

## 1. Co definiuje specyfikacja (esencja)

System rezerwacji wizyt psychologicznych: wyszukiwarka terminów (111 specjalistów), rezerwacja gościa bez konta (konto w tle po płatności, magic link), Stripe Checkout (karta/BLIK/GPay/APay, kwota i reguła anulacji **zamrażane w chwili zakupu**), okno bezpłatnego odwołania 24 h, lista rezerwowa z automatem odzysku terminów, wydarzenia grupowe z listą rezerwową, panel specjalisty (grafik dwuwarstwowy: rytm+poprawki+urlopy, rozliczenia bez widocznej prowizji, faktury), panel koordynatora (grafik zespołu, kwalifikacja zgłoszeń pierwszego kontaktu — 6 pytań zamkniętych, interwencje, **niezmienialny dziennik decyzji uznaniowych**, raport grantowy liczony osobami, katalog usług i reguły z wersjonowaniem), powiadomienia mail+SMS z przeplanowaniem, RODO art. 9 (DPIA wymagana PRZED modelem danych).

Cennik: niskopłatna 55 zł (0% prowizji, limit 10/4 na pacjenta — DO ROZSTRZYGNIĘCIA), pełnopłatna 115–145 zł (20% prowizji), asystent zdrowienia 0 zł, diagnoza ADHD 350 zł, grupa: prowadzący 180 zł/spotkanie.

## 2. Architektura — DOKUMENT JEST DWUZNACZNY (korekta 07.08, po pytaniu użytkownika)

Specyfikacja PDF zawiera przesłanki za DWOMA odczytaniami i sama zostawia rozstrzygnięcie otwarte. Pewne jest tylko: **backend PHP od zera** („PHP wskazany w DECYZJE §26" — treść §26 do sprawdzenia w źródłach makiety) i **frontend = SPA (makieta) osadzona wizualnie w motywie niepodzielni.com** (działa z oboma wariantami).

**Przesłanki za wariantem A — „w ekosystemie WordPressa":**
- „Zaimplementować rolę WordPress `pacjent`" (s. 3); „Wykorzystanie istniejącej roli WP `psycholog` bez dostępu do wp-admin" (s. 23)
- „Nie trzeba budować osobnego systemu użytkowników" (s. 41, USTALENIA PROJEKTOWE)
- tabela `specjalista (user_id roli psycholog + post_id CPT …)` (s. 16)

**Przesłanki za wariantem B — „osobna aplikacja, migracja Z WordPressa":**
- M6/6: „Zmapowanie roli WP psycholog i post_author **na konta w nowym systemie**, z zachowaniem możliwości logowania tymi samymi danymi" (s. 66)
- tamże, jawnie otwarte pytanie: „**Ustalenie kierunku synchronizacji profilu: czy wp-admin pozostaje źródłem prawdy, czy przejmuje to nowy panel**"

**ROZSTRZYGNIĘTE przez użytkownika 07.08.2026 (doprecyzowanie ustne):** obowiązuje **wariant B, w wersji mocnej** — system rezerwacji („Gabinet") jest osobną aplikacją i **przejmuje rolę źródła prawdy**: opisy specjalistów, specjalizacje, obszary pomocy i terminy żyją POZA WordPressem, a **WordPress tylko pobiera te dane po API** i renderuje na niepodzielni.com (listingi typu `/konsultacje-psychologiczne-pelnoplatne/`, profile, ścieżka rezerwacji osadzona na stronie). To odwrócenie dzisiejszego kierunku (dziś: dane psychologów w WP + rezerwacje w Bookero). Zadanie M6/6 czyta się wtedy naturalnie: jednorazowa migracja CPT/taksonomii do Gabinetu, potem WP przechodzi na odczyt z API. Zgodne z EKOSYSTEM.md (Gabinet = jedyne źródło prawdy o specjalistach, publikowane do WP i aplikacji) i z CLAUDE.md starego repo. Treść DECYZJE.md §26 (źródła makiety — lokalnie u wykonawcy, repo GitHub zawiera tylko zbudowany index.html) nadal do wglądu dla potwierdzenia szczegółów technologicznych.

**Rekomendacja architekta: wariant B — osobna aplikacja PHP (Laravel).** Argumenty:
1. **RODO/art. 9 — najważniejszy.** Konta tysięcy pacjentów i dane o zdrowiu w tej samej bazie co marketingowy WordPress = wspólna powierzchnia ataku i trudniejsza DPIA. Osobna aplikacja daje czystą granicę danych (osobna baza, osobne kopie, osobna retencja).
2. Charakter systemu (blokady współbieżne, kolejki, workery, przeplanowanie powiadomień, webhooki Stripe, generowanie PDF) to naturalny teren Laravela (kolejki/Horizon, scheduler, Cashier) — w WP wszystko to trzeba ręcznie dorabiać.
3. SSO personelu: gotowy, przetestowany wzorzec `niepodzielni-konta/tests/ref-laravel` (INTEGRACJA-KONTRAKT) — niezależny od terminu przejścia WP na OIDC; chat już tak działa.
4. Zgodność z EKOSYSTEM.md (Gabinet jako osobny system, etap 3) i z zasadą „integracje przez API-umowy, nie wspólną bazę".
5. WordPress zostaje **źródłem profili publicznych** (CPT + taksonomie, odczyt przez REST — wzorzec app-booking-endpoint już istnieje); kierunek edycji profilu = otwarte pytanie z M6/6 do decyzji.

Konsekwencja wariantu B: zadania mówiące o „roli WordPress pacjent" wymagają korekty w specyfikacji (pacjent = rekord w nowym systemie + konto z magic linkiem, nie rola WP).

**Technologia — DECYZJA użytkownika 07.08.2026: Laravel.** Po analizie Laravel vs NestJS: technicznie oba udźwigną system; przesądza spójność ekosystemu (3 istniejące backendy Laravel) i gotowe, zweryfikowane klocki — SSO `ref-laravel`, Stripe/Cashier (PsychON), kolejki/Horizon, harmonogram, podpisane linki jednorazowe (mechanizm magic linków). Argument za NestJS (współdzielenie reguł TS z makietą) uznany za mniejszy niż wygląda: serwer i tak jest jedynym rozstrzygającym, a reguły to kilkadziesiąt linii przepisywanych raz, pod wymagane tabelaryczne testy graniczne.

## 3. Werdykt: istniejące repo `System-rezerwacji` → ARCHIWUM (kosz z pożegnaniem)

Nowa specyfikacja nie odwołuje się do repo NestJS ani razu. Porównanie po rozstrzygnięciu architektury (07.08):

| | Stare repo (NestJS/Prisma/React, dossier [05](05-system-rezerwacji.md)) | Specyfikacja 08.2026 + decyzja 07.08 |
|---|---|---|
| Kształt architektury | osobna aplikacja z API do WP | osobna aplikacja z API do WP — **ten sam kształt** |
| Źródło prawdy o specjalistach | ambicja ta sama (CLAUDE.md repo: „jedyne źródło prawdy, publikowane do WP i aplikacji") | Gabinet źródłem prawdy; WP tylko czyta po API |
| Technologia backendu | NestJS/TypeScript — **jedyny taki stack w ekosystemie** (reszta: PHP/Laravel) | **PHP** (DECYZJE §26); rekomendacja: Laravel — jak chat control-plane, mindscape-backend, PsychON |
| Tożsamość | własna tabela User + bcrypt + JWT | personel przez Konta Niepodzielni (wzorzec ref-laravel); pacjenci: konto w Gabinecie, magic link |
| Frontend | własny panel React + widget Shadow DOM | makieta React/Vite (61 ekranów) do podpięcia pod API |
| Model pacjenta | guest checkout | gość + konto w tle po płatności + magic link |
| Zakres funkcjonalny | ułamek: rezerwacja + płatność + prosty panel | 6 modułów: + kwalifikacja zgłoszeń, lista rezerwowa z automatem, wydarzenia, rozliczenia/faktury, dziennik decyzji, raport grantowy, powiadomienia z przeplanowaniem |
| WooCommerce sync | jest | zbędny (Woo usunięte ze strony) |
| Google Calendar | atrapa | realny freeBusy (etap 1 tylko odczyt) |
| Stan bezpieczeństwa | 4 krytyczne luki (audyt 31.07) | wymagania bezpieczeństwa wpisane w zadania + pentest przed startem |

**Uczciwa uwaga:** po rozstrzygnięciu „osobna aplikacja" stare repo jest architektonicznie bliższe celowi, niż wynikało z pierwszej wersji tej analizy (kształt się zgadza). Werdykt ARCHIWUM mimo to się utrzymuje, z czterech powodów: (1) **technologia** — spec wskazuje PHP, a NestJS to jedyny obcy stack w ekosystemie, którego poza tym repo nikt tu nie używa; kompetencje zespołu to Laravel; (2) **jakość** — 4 krytyczne luki i status „hobbystycznego szkicu" (ocena właściciela); (3) **zakres** — repo pokrywa ułamek 150 zadań specyfikacji, a jego frontend w całości zastępuje makieta; (4) **model tożsamości** — sprzeczny z zasadą SSO-first.

**Rekomendacje:**
1. Repo **zamrozić jako archiwum** — nie rozwijać, nie wdrażać, NIE wykonywać planu naprawy 4 krytycznych luk (naprawianie systemu idącego do kosza to wyrzucone godziny). Jedyny obowiązek: **nigdy nie wystawić go publicznie**.
2. Niezacommitowaną fazę naprawczą (~40 plików w working tree) zacommitować na gałęzi `archiwum-faza-naprawcza` wyłącznie dla porządku historii, bez weryfikacji.
3. **Co przeżywa (idee, nie kod):** lekcje z audytu 31.07 jako twarde wymagania nowego systemu (brak eskalacji uprawnień przez self-update, żadnych publicznych endpointów z hashami, potwierdzenie rezerwacji wyłącznie po webhooku płatności, zero sekretów w kodzie); wzorzec szyfrowania PII (AES-256-GCM) i anonimizacji RODO; test idempotencji webhooków; koncepcja widgetu w Shadow DOM (gdyby osadzanie SPA w motywie wymagało izolacji stylów). Specyfikacja i tak pokrywa to wszystko własnymi zadaniami.

## 4. Braki ekosystemowe specyfikacji (do wstrzyknięcia PRZED wyceną)

Specyfikacja jest bardzo dobra funkcjonalnie, ale powstała w oderwaniu od planu ekosystemu — 4 rzeczy trzeba dopisać:

1. **SSO personelu.** Dokument opisuje własne logowanie (magic link/kod SMS dla pacjentów — OK; hasła personelu — KONFLIKT z zasadą „nowe systemy bez własnych haseł"). Rozwiązanie po decyzji 07.08 (Gabinet = osobna aplikacja): personel (psycholog, koordynator) loguje się do Gabinetu **bezpośrednio przez Konta Niepodzielni** — gotowy wzorzec `niepodzielni-konta/tests/ref-laravel`, w Keycloak trzeba zarejestrować klienta Gabinetu; 2FA koordynatora domyka rola w Keycloak. Pacjenci: konta lokalne w Gabinecie z magic linkiem (rekomendacja; w realm istnieje rezerwowa rola `pacjent`, gdyby fundacja kiedyś chciała konta wspólne z aplikacją — DECYZJA do zapisania).
2. **Dwa konta Stripe (fundacja/komercja) — decyzja 0.1 planu, w spec nieobecna.** Mapowanie naturalne: niskopłatna (0% prowizji, dotacja) + asystent zdrowienia → konto fundacyjne; pełnopłatna (20%) + ADHD 350 zł → komercyjne. Konsekwencje niebanalne: dwa zestawy kluczy i webhooków, rekoncyliacja per konto, raport grantowy i przychodowy per konto. Wchodzi w interakcję z kwestią blokującą nr 3 (Stripe Connect).
3. **Endpoint „podsumowanie dla hubu"** (decyzja 0.4): dzisiejsze wizyty, obłożenie, kolejka zgłoszeń, zwroty do wykonania.
4. **Zgłoszenia problemów → Zammad.** Zadania M1/25 i M5/14 (zgłoszenie ze zrzutem ekranu, kierowanie wg roli) powinny tworzyć sprawy w Zammadzie (decyzja 07.08), nie budować osobnego rejestru zgłoszeń.

## 5. Decyzje — stan po odpowiedziach użytkownika z 07.08.2026 (wieczór)

**PODJĘTE:**
1. ✅ **Widełki pełnopłatne: 115/125/135/145 zł** — z zastrzeżeniem, że mogą się zmieniać → cennik wersjonowany z datą obowiązywania (spec i tak to przewiduje), lista widełek jako ustawienie systemu.
2. ✅ **Cutover Bookero: na przełomie miesiąca** (przełączenie z nowym miesiącem). Wariant obsługi już umówionych rezerwacji — do domknięcia; rekomendacja: przepisanie przyszłych rezerwacji do Gabinetu (+16–24 h), NIE praca równoległa.
3. ✅ **Logowanie wszędzie przez SSO — STAŁY WYMÓG ekosystemu** (rozszerzenie decyzji 0.2): także pacjenci Gabinetu logują się przez Konta Niepodzielni (rola `pacjent` w realm). Guest checkout pozostaje bez konta. Konsekwencja projektowa: flow „konto w tle po płatności + magic link" realizować mechanizmami Keycloaka (utworzenie konta przez Admin API + link aktywacyjny action-token zamiast własnych magic linków) — do przeprojektowania w spec.
4. ✅ **Wydarzenia: Gabinet źródłem prawdy, WP tylko wyświetla po API** (spójnie z profilami specjalistów).
5. ✅ **Prowizje (w tym ADHD): konfigurowalne per usługa w ustawieniach systemu** (katalog usług spec już to przewiduje — pole „czy fundacja pobiera prowizję" + stawka; wartość startowa dla ADHD do wpisania przy konfiguracji).
6. ✅ **Specjalista wiązany po `sub` z Keycloak** (nie po e-mailu) — wspólna tożsamość personelu z PsychON/chatem.
7. ✅ **Kolejność wdrożeń bez zmian** (Gabinet po PsychON i chacie).
8. ✅ **SMS: dostawca SMSAPI**, wdrożenie później. Uwaga terminowa: rejestracja nadawcy „Niepodzielni" u operatorów trwa do kilku tygodni — wniosek złożyć na starcie budowy Gabinetu, nie przy wysyłce pierwszego SMS-a.
9. ✅ **Notatki z sesji: poza pierwszym wdrożeniem** (interpretacja odpowiedzi „okej" — do potwierdzenia); zdjąć obietnicę z pulpitu makiety (`Pulpit.tsx:179`).
10. ✅ **Infrastruktura: plan w [10-infrastruktura-serwerowa.md](10-infrastruktura-serwerowa.md)** (osobne serwery per domena: strona / konta / aplikacje / narzędzia).

**Aktualizacja 07.08 (noc):**
11. ✅ **Wideo: pokoje generowane per wizyta** (decyzja użytkownika); dostawca do wyboru przy wycenie (Jitsi self-host vs Whereby).
12. ✅ **Limit niskopłatnych i okno blokady: jako ustawienia systemu** (decyzja użytkownika). Wartości startowe: limit — do wskazania przez zarząd przy konfiguracji (steruje budżetem dopłat); okno blokady — domyślne wg rekomendacji: link płatności ważny 2 dni + 10 min trzymania slotu po otwarciu płatności, wartość „24 h" usunięta.

**OTWARTE:**
- **Stripe Connect vs przelewy miesięczne** — użytkownik ustala z fundacją; rekomendacja architekta: przelewy miesięczne na start (odwracalne), Connect ewentualnie później.
- Wariant przepisania rezerwacji Bookero (pkt 2 wyżej); „środki własne w okresie"; toast „zwrot zlecony w Stripe" (do usunięcia — bezsporne); historia Bookero w raportach grantowych (pyt. 15 — bez odpowiedzi).

**Korekta specyfikacji (niezmienna):** zadania mówiące o „roli WordPress pacjent"/„roli WP psycholog" przepisać na konta przez SSO; zadanie M6/6 = migracja profili CPT→Gabinet + przełączenie WP na odczyt z API.

**Operacyjne:** pełna lista w [09b](09b-rezerwacje-digest-zakres-wdrozenia.md) §6 (m.in. tożsamość pacjenta-gościa w raporcie grantowym, wynagrodzenie przy przeniesieniu przez koordynatora, notatki z sesji — osobne 44 h).

## 6. Braki warsztatowe

- **Zbudowana makieta sklonowana** do `D:\KOD\Niepodzielni\rezerwacje-makieta` (GitHub `MixtureMarketing/rezerwacje-makieta` — wyłącznie `index.html` 1,7 MB + README; podgląd: https://mixturemarketing.github.io/rezerwacje-makieta/). **ŹRÓDŁA makiety wciąż brakujące** — dokumenty cytują `src/lib/reguly.ts`, `DECYZJE.md` §4/§14/§15/§23/§26, `src/dane/maile.ts`, `sprawdz-ekrany.mjs`; README: „kod źródłowy jest osobno". Do pozyskania od użytkownika (lokalnie u wykonawcy) i objęcia dossier — w szczególności DECYZJE.md §26 do potwierdzenia szczegółów technologicznych.
- Dokument świadomie nie zawiera wyceny/harmonogramu; wycena wg zasad: stawka 100 zł/h, tempo z pracy z AI, bez wzmianek o AI w dokumentach klienckich.
