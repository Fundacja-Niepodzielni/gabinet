# System rezerwacji — decyzje i kwestie otwarte

Dokument towarzyszący makiecie. Zapisuje, co zostało ustalone, co z tego wynika
i czego jeszcze nie wiemy. Makieta jest ilustracją tych decyzji — jeśli któraś się
zmieni, zmieniają się konkretne ekrany wskazane niżej.

Stan na 4 sierpnia 2026 (po rundzie zmian zamawiającego).

---

## 1. Punkt wyjścia — co już istnieje

Makieta nie powstaje w próżni. W `Niepodzielni-dev` jest sporo zaczepów.
**Repozytorium nie zostało w żaden sposób zmienione** — służyło wyłącznie jako źródło wiedzy.

| Co | Gdzie | Znaczenie dla nas |
|---|---|---|
| Rola WP `psycholog` | `mu-plugins/niepodzielni-core/admin/9-psycholog-role.php` | Konto specjalisty już istnieje, linkowane do CPT przez `post_author`, bez dostępu do wp-admin, przekierowanie na `/panel/` |
| CPT `psycholog` + taksonomie | `mu-plugins/niepodzielni-core/cpt/14-cpt-psycholog.php` | Profile, specjalizacje, języki, nurty, obszary pomocy — gotowa baza do filtrów |
| Bookero jako zewnętrzny SaaS | `src/Bookero/`, `docs/reference/bookero.md` | Dwa osobne konta (pełnopłatny `5tu8AC22Akna`, niskopłatny `hxRnUexTsSvc`), sync cronem co 60 s, Circuit Breaker, cache dwupoziomowy |
| Wtyczka Bookero na froncie | `resources/js/bookero-init.js` | Skompilowany Vue bez publicznego API — przełączenie typu wizyty wymaga przeładowania strony |
| Rola pacjenta | — | **Nie istnieje.** Dziś nie ma kont pacjentów ani historii wizyt |
| Stripe | `docs/roadmap/fundraising-stripe.md` | Zaczęty przy wpłatach, niedokończony. Checkout wizyt to nowa ścieżka |

**Wniosek:** największa zmiana to nie kalendarz, tylko pojawienie się pacjenta jako
podmiotu z kontem i historią. Bookero nigdy tego nie miał — znał tylko pojedyncze
rezerwacje bez tożsamości po drugiej stronie.

---

## 2. Decyzje podjęte

| # | Kwestia | Ustalenie |
|---|---|---|
| 1 | Stos technologiczny | React 19 + TypeScript + Vite + Tailwind 4, router po hashu, `zustand`, `lucide-react` — identycznie jak makieta PsychON, żeby oba prototypy utrzymywać tak samo |
| 2 | Wygląd części publicznej | Wpięta w layout niepodzielni.com: nagłówek z menu konsultacji, zielone „Umów się”, stopka z numerami kryzysowymi. Pacjent nie ma czuć, że wchodzi w osobny system |
| 3 | Dane w makiecie | Realne profile specjalistów i katalog usług z produkcji. Rezerwacje, pacjenci i kwoty — zmyślone |
| 4 | Płatność | Pełna kwota przy rezerwacji, Stripe Checkout. Termin blokowany na 10 minut do czasu zaksięgowania |
| 5 | Zmiana terminu po 24 h | Zablokowana w self-service. Przycisk znika, zostaje wyjaśnienie i kontakt do specjalisty. Override ma psycholog i koordynator |
| 6 | Zmiana specjalisty | Niedostępna w istniejącej rezerwacji. Inna osoba = odwołanie + nowa rezerwacja |
| 7 | Konto pacjenta | Rezerwacja jako gość. Konto powstaje w tle po opłaceniu, dostęp magic linkiem z maila |
| 8 | Cykliczność | Pojedyncze wizyty. Po potwierdzeniu system proponuje ten sam termin za tydzień, ale to osobne rezerwacje |
| 9 | Model kalendarzy | Katalog usług globalny, ustalany przez fundację. Specjalista steruje tylko dostępnością. Jedyny wyjątek: stawka pełnopłatna z widełek |
| 10 | Ustawianie dostępności | **Dwa poziomy**: rytm tygodniowy jako baza plus siatka konkretnych godzin do korekty. Można wyłączyć pojedynczą godzinę z rytmu i dodać taką, której w rytmie nie ma |
| 11 | Prowizja | **Niewidoczna dla specjalisty.** W panelu psychologa jest tylko „Twoje wynagrodzenie”. Pełne rozliczenie — wpływ, prowizja, marża — widzi wyłącznie koordynator |
| 12 | Link do spotkania | Dostępny **od razu** po rezerwacji: w potwierdzeniu, w mailu i na karcie wizyty. Nie czeka na przypomnienie 2 h przed |
| 13 | Niskopłatne | Bez weryfikacji dochodu. Limit godzinowy — patrz wiersz 18 |
| 14 | Forma wizyty | Online i stacjonarnie. Przy stacjonarnej karta wizyty pokazuje adres gabinetu zamiast linku |
| 15 | Wspólny kalendarz | Publiczna wyszukiwarka terminów z filtrami, sortowana od najwcześniejszego. Osobno wewnętrzny grafik dla koordynatora |
| 16 | Statystyki koordynatora | Cztery zestawy: rezerwacje i przychód, odwołania i nieobecności, obłożenie specjalistów, ścieżka pacjenta |
| 17 | **Pacjent nie widzi wydatków** | Panel nie pokazuje sum ani historii finansowej. Zniknął ekran płatności i kafel „wydane na wizyty”. Pacjent widzi cenę pojedynczej wizyty przed zapłatą — nic ponad to |
| 18 | Limit wizyt niskopłatnych | **4 h na osobę**, koordynator może podnieść. Kto zaczął leczenie niskopłatnie, rezerwuje dalej w tej samej kategorii — u tego samego albo innego specjalisty. Pełnopłatne bez limitu |
| 19 | Grafik specjalisty | Terminy wystawia **maksymalnie 7 dni do przodu**. Panel blokuje dalsze daty, żeby grafik pokazywał realną dostępność, a nie deklaracje sprzed miesiąca |
| 20 | Rezygnacja z grupy | Do **2 h przed** spotkaniem — bezpłatnie, z powiadomieniem mailem i w systemie. Krócej niż 24 h przy wizytach 1:1, bo miejsce da się jeszcze przekazać z listy rezerwowej |
| 21 | Konto przy rezerwacji | Rezerwacja jako gość zostaje domyślna, ale w checkoucie jest opcja „załóż mi konto” z hasłem — bez wychodzenia z procesu |
| 22 | Umawianie przez specjalistę | Psycholog umawia pacjenta na kolejny termin w trakcie sesji albo później. Nie pobiera pieniędzy — system wysyła maila z linkiem do płatności |
| 23 | Wniosek o wizytę bezpłatną | Specjalista prosi koordynatora o zwolnienie pacjenta z opłaty, z uzasadnieniem finansowym. Termin zostaje zablokowany do decyzji |
| 24 | Wiadomości | Moduł w trzech kierunkach: pacjent ↔ specjalista ↔ koordynator. Każdy wątek ma kontekst (numer wizyty albo nazwa grupy) |
| 25 | Wsparcie techniczne | Przycisk „Zgłoś problem” na każdym ekranie, z numerem telefonu wypisanym wprost w oknie — gdy system nie działa, formularz też może nie zadziałać |

---

## 3. Polityka odwołań — pełna macierz

To jest serce systemu. Wszystko poniżej dzieje się automatycznie, bez udziału człowieka.
Ekran `/koordynacja/reguly` pokazuje tę tabelę koordynatorowi.

| Sytuacja | Zwrot dla pacjenta | Termin wraca do puli | Godzina płatna dla specjalisty |
|---|---|---|---|
| Pacjent odwołuje **wcześniej** niż 24 h przed | 100% | tak | nie |
| Pacjent odwołuje **później** niż 24 h przed | brak | tak | **tak** |
| Pacjent nie przychodzi bez uprzedzenia | brak | nie | **tak** |
| Pacjent zmienia termin wcześniej niż 24 h | płatność przechodzi | tak | za nowy termin |
| Pacjent zmienia termin później niż 24 h | niedostępne | — | — |
| Specjalista odwołuje — kiedykolwiek | 100% | tak | nie |
| Koordynator odwołuje — kiedykolwiek | 100% | tak | decyzja ręczna |
| Płatność nie doszła w 10 minut | — | tak | nie |

Cała ta logika żyje w jednej funkcji: `ocenaAnulacji()` w `src/lib/reguly.ts`.
Żaden ekran nie decyduje o zwrocie samodzielnie.

Dodatkowe reguły:
- **Limit zmian terminu:** 2 na rezerwację.
- **Najbliższy możliwy termin:** 2 godziny od teraz — świadomy wybór na rzecz osób
  w kryzysie, kosztem czasu specjalisty na przygotowanie.
- **Kalendarz pacjenta otwarty na:** 30 dni w przód.
- **Specjalista wystawia terminy na:** 7 dni w przód (blokada w panelu).
- **Limit godzin niskopłatnych:** 4 h na pacjenta, koordynator może podnieść.
- **Rezygnacja z grupy i nieobecność:** do 2 h przed spotkaniem.
- **Przerwa między wizytami:** 10 minut.
- **Kredyt za odsprzedany termin:** jeśli po późnym odwołaniu ktoś inny wykupi ten slot,
  pierwszy pacjent dostaje równowartość jako kredyt. Specjalista i tak ma godzinę opłaconą —
  różnicę pokrywa fundacja. Włączane flagą, domyślnie włączone.

**Dlaczego zmiana terminu po 24 h jest zablokowana:** jeśli zostawić furtkę, nikt nie odwołuje —
wszyscy przekładają. Slot i tak przepada, a okno 24 h przestaje cokolwiek znaczyć.
Tak to rozwiązują SimplePractice, Doctolib i ZnanyLekarz: późne przełożenie = późne odwołanie.

**Dlaczego psycholog dostaje wynagrodzenie za późno odwołaną godzinę:** zablokował na nią czas
i nie mógł przyjąć nikogo innego. Termin mimo to wraca do puli, bo zdarza się, że ktoś go wykupi —
i lepiej, żeby godzina się odbyła, niż stała pusta.

---

## 4. Model danych — szkic

```
Usługa            id, nazwa, minuty, model_ceny (stała|widełki), cena, widełki[],
                  wymaga_uprawnienia, bez_prowizji, widoczna_publicznie

Specjalista       user_id (rola `psycholog`), post_id (CPT), stawka_pelna,
                  uslugi[] (uprawnienia), forma[]

Dostępność        specjalista_id, usluga_id, dzien_tygodnia, od, do      ← rytm tygodniowy
Poprawka          specjalista_id, data, godzina, typ (dodana|wyłączona)  ← siatka godzin
Wyjątek           specjalista_id, od, do, powód          ← urlop, wygrywa ze wszystkim

Rezerwacja        id, pacjent_id, specjalista_id, usluga_id, termin,
                  kwota_zamrozona, status, liczba_przelozen, link_spotkania,
                  stripe_payment_intent, regula_anulacji_zamrozona

Pacjent           user_id, email, telefon, strefa_czasowa, zgody[]
Zdarzenie         rezerwacja_id, czas, aktor, typ, opis   ← ślad audytowy
```

Trzy pola, bez których system się rozjeżdża:

- **`kwota_zamrozona`** — rezerwacja pamięta cenę z momentu zakupu. Bez tego podniesienie
  cennika sprawia, że stare zwroty przestają zgadzać się ze Stripe.
- **`regula_anulacji_zamrozona`** — pacjent zgodził się na konkretne warunki.
  Zmiana okna z 24 na 48 h nie może działać wstecz.
- **`Poprawka`** jako osobny byt — bez niej wyklikanie pojedynczej godziny musiałoby
  rozbijać rytm tygodniowy na setki rekordów.

---

## 5. Kwestie otwarte

### Rozstrzygnięte na podstawie produkcji ✓

- ~~**Stawka niskopłatna**~~ → **55 zł / 50 min, stała cena.** Potwierdzone na
  [profilu Pauliny Siwek](https://niepodzielni.com/psycholog/paulina-siwek/).
  Wartość 37 zł z migracji WooCommerce jest nieaktualna.
- ~~**Katalog usług**~~ → cztery pozycje z menu niepodzielni.com: konsultacje niskopłatne,
  konsultacje pełnopłatne, asystent zdrowienia, diagnoza ADHD u dorosłych.

### Blokujące budowę

1. **Widełki pełnopłatne.** Makieta używa 115 / 125 / 135 / 145 zł. Na profilach widać dziś
   realne stawki 135 zł (Filip Dudzicz, Marcin Błażejowski) i 145 zł (Marzena Dobies),
   a strona opisuje je jako „standardową cenę rynkową”. Trzeba podać pełną listę dozwolonych kwot.

2. **Co z rezerwacjami umówionymi w Bookero?**
   W dniu przełączenia część pacjentów ma już umówione wizyty w starym systemie.
   Trzy opcje: przepisać je do nowej bazy, dopalić Bookero do wyczerpania terminów,
   albo przełączyć tylko nowe rezerwacje i przez kilka tygodni żyć z dwoma systemami.

3. **Rozliczenie z psychologami — przelewy czy Stripe Connect?**
   Makieta zakłada, że fundacja przyjmuje całą płatność i raz w miesiącu przelewa
   specjalistom. Connect dzieli płatność automatycznie przy zakupie i zdejmuje ręczną pracę,
   ale wymaga weryfikacji tożsamości każdego psychologa w Stripe.

4. **Skąd bierze się link do spotkania.** Makieta pokazuje go od razu po rezerwacji.
   Do decyzji, czy generujemy pokoje w Whereby/Jitsi/Zoom przez API, czy każdy specjalista
   wkleja swój stały link w profilu. Pierwsze daje osobny pokój na wizytę,
   drugie jest darmowe, ale dwie wizyty pod rząd wpadną do tego samego pokoju.

### Ważne, ale nie blokujące

5. **Prowizja fundacji.** Makieta zakłada 20% od pełnopłatnych i 0% od niskopłatnych.
   Specjalista jej nie widzi, ale musi się zgadzać z umowami.

6. **24 godziny — zegarowe czy robocze?** Makieta liczy zegarowo, także przez weekend.

7. **Faktury.** Dziś tylko paragon Stripe. Czy potrzebne faktury z NIP-em?

8. **Kody rabatowe i vouchery.** Nie ma ich, bo nie wiadomo, czy fundacja ich używa.

9. **Lista oczekujących.** Gdy pacjent nie znajduje terminu, dostaje pusty ekran.

10. **Powiadomienia SMS.** Makieta proponuje SMS tylko przy przypomnieniu 2 h przed
    i odwołaniu przez specjalistę.

### Do rozstrzygnięcia później

11. **Dwukierunkowy sync z Kalendarzem Google.** Ekran ma przygotowane miejsce, integracji nie ma.
12. **Notatki z sesji i dokumentacja.** Dane szczególnej kategorii, osobny reżim RODO.
13. **Opinie.** Makieta zakłada, że opinię wystawia tylko osoba z zakończoną rezerwacją.

---

## 6. Znalezione przy okazji — do poprawienia na produkcji

Przeglądając profile do makiety, natknąłem się na dwie rzeczy na `niepodzielni.com`.
Nie dotykałem ich — to zgłoszenie, nie zmiana.

1. **Surowa nazwa pola Carbon Fields.** Na profilu
   [Marcina Błażejowskiego](https://niepodzielni.com/psycholog/marcin-blazejowski/)
   wyświetla się `Tryb konsultacji: field_complex` — niezrenderowana wartość pola złożonego.
2. **Brakujące „zł” przy cenie.** U Marcina Błażejowskiego i
   [Aleksandra Wiślińskiego](https://niepodzielni.com/psycholog/aleksander-wislinski/)
   cena to `135 / 50 min` i `55 / 50 min`, podczas gdy u pozostałych `135 zł / 50 min`.
   Prawdopodobnie efekt niespójnych wartości atrybutu opisany w
   `docs/roadmap/migracja-psychologow-woo.md` („wartość `155` → normalizować do `155 zł`”).

---

## 7. Mapa ekranów

| Adres | Rola | Co pokazuje |
|---|---|---|
| `/` | pacjent | Start: rodzaje konsultacji, najbliższy wolny termin, wejścia do trzech ról |
| `/szukaj` | pacjent | Wspólny kalendarz — wszyscy specjaliści, filtry, sortowanie po najwcześniejszym |
| `/psycholog/:slug` | pacjent | Profil w układzie niepodzielni.com + kalendarz rezerwacji |
| `/rezerwacja` | pacjent | Checkout: dane, zgody, podsumowanie, licznik blokady terminu |
| `/potwierdzenie` | pacjent | Link do spotkania od razu, harmonogram przypomnień, propozycja kolejnej wizyty |
| `/moje-wizyty` | pacjent | **Sedno polityki** — wizyta w oknie 24 h i poza nim, pełne info o spotkaniu |
| `/moje-dane` | pacjent | Kontakt, karta, zgody, eksport i usunięcie konta |
| `/panel` | psycholog | Dzisiejsze wizyty, obłożenie, sprawy wymagające uwagi |
| `/panel/wizyty` | psycholog | Lista wizyt, oznaczanie nieobecności, odwołanie po swojej stronie |
| `/panel/kalendarze` | psycholog | Co przyjmuję, co zablokowane, gdzie wybieram stawkę |
| `/panel/dostepnosc` | psycholog | **Rytm tygodniowy + siatka pojedynczych godzin**, wyjątki, kalendarz Google |
| `/panel/rozliczenia` | psycholog | Wynagrodzenie bez rozbicia na prowizję |
| `/koordynacja` | admin | Cztery zestawy statystyk |
| `/koordynacja/grafik` | admin | Cały zespół w jednym dniu, interwencje, rezerwacja telefoniczna |
| `/koordynacja/rezerwacje` | admin | Wyszukiwanie, pełna oś czasu każdej rezerwacji |
| `/koordynacja/psycholodzy` | admin | Konta, uprawnienia, obłożenie, **pełne rozliczenie z prowizją** |
| `/koordynacja/uslugi` | admin | Katalog usług i cennik |
| `/koordynacja/reguly` | admin | **Macierz odwołań**, prowizje, powiadomienia |

---

## 8. Uwagi techniczne

### PsychON i niepodzielni.com mają różne palety

To okazało się przy pobieraniu produkcyjnego CSS-u i warto to zapisać, bo łatwo się pomylić.

Arkusz `styl.css`, od którego zaczynaliśmy, jest wyciągnięty z **PsychONa** — platformy
szkoleniowej. Jego kolorem wiodącym jest fiolet `#594ef9`. Tymczasem **na niepodzielni.com
ten fiolet nie występuje ani razu** (sprawdzone w `app-C7XSQriQ.css` i
`single-psycholog-DDaLQaqL.css`).

| Rola | PsychON | niepodzielni.com |
|---|---|---|
| Kolor wiodący | `#594ef9` fiolet | **`#01be4a` zieleń** (`--mix-color-brand-primary`) |
| Kolor tożsamości / nagłówki | `#1500bb` | **`#1500bb` granat** (`--mix-color-brand-secondary`) |
| Nagłówek profilu | — | `#1c2e4a` |
| Tekst przygaszony | `#555555` | `#707070` |
| Promień karty | 28 px | **40 px** (profil specjalisty: 60 px) |
| Waga nagłówków | 900 | **700** |

Makieta używa palety **niepodzielni.com**. Nazwy tokenów zaczynają się od `--np-*`,
a stare nazwy `--psy-*` zostały w `tokens.css` jako aliasy — dzięki temu całość przemalowuje
się z jednego pliku, a różnica między platformami jest udokumentowana w jednym miejscu.

### Reszta

- Komponenty siedzą w `@layer components`, bo bez tego `.btn-primary` wygrywa z `hidden`
  i wariantami `sm:` Tailwinda.
- Karty mają przezroczysty obrys 2 px, który na hover robi się zielony razem z podniesieniem
  o 2 px i zieloną poświatą `0 0 15px #01be4ab3` — dokładnie jak `.card` na produkcji.
  Efekt dostają tylko karty, które naprawdę są klikalne.
- Kolory serii na wykresach to `--np-secondary` (#1500bb) i `--np-info` (#4a90e2) — oba
  z palety produkcyjnej. Kolejność jest stała: pełnopłatne zawsze granat, niskopłatne
  zawsze niebieski, żeby zmiana filtra nie przemalowała wykresu.
- Rezerwacje i przychód są na dwóch osobnych wykresach, nie na jednym z dwiema osiami:
  liczba wizyt i złotówki mają inną skalę i zestawione razem sugerowałyby zależność,
  której nie ma.
- `npm run sprawdz` renderuje wszystkie 23 ekrany pod jsdom i wywala się, jeśli
  którykolwiek rzuci błędem albo wyrenderuje pusto.

## 9. Zespół liczy 111 osób, nie 7

Siedem prawdziwych profili z niepodzielni.com plus 104 wygenerowane deterministycznie.
Bez tego nie widać, że siatka „wszyscy × godziny" przestaje działać — przy siedmiu
osobach każdy układ wygląda dobrze. Generowani mają `id >= 100`, rozróżnia je
`czyPrawdziwy()`; pacjent w wyszukiwarce widzi tylko prawdziwych, bo tylko oni mają
zdjęcia i biogramy.

## 10. Grafik ma trzy widoki, bo jeden nie obsłuży 100+ osób

- **Dzień** — siatka godzin × ludzie, ale tylko 8 osób naraz, z wyszukiwarką
  i stronicowaniem. Osiem to tyle, ile mieści się na ekranie bez poziomego przewijania.
- **Tydzień** — jedna osoba × 7 dni. Siatka „111 osób × 7 dni" miałaby 777 kolumn,
  a grafik i tak układa się dla konkretnego terapeuty.
- **Miesiąc** — bez nazwisk, tylko obłożenie zespołu dzień po dniu. Służy do wyłapywania
  dziur, nie do sprawdzania kto kiedy pracuje. Kliknięcie w dzień otwiera go w widoku dziennym.

Kafle podsumowania liczą **cały zespół**, nie widoczną stronę — inaczej zmieniałyby się
przy przewijaniu listy i nie znaczyłyby nic. Sloty są memoizowane: widok miesięczny to
35 dni × 111 osób = 3885 wywołań, mierzone 6 ms.

## 11. Filtry rezerwacji zawężają, a nie ukrywają

Okres, specjalista, usługa, forma, status i wyszukiwarka. Dwie decyzje warte zapisania:

- Lista specjalistów w filtrze bierze się **z danych**, nie z całego zespołu — rozwijalna
  ze 111 pozycjami, z których większość nic nie pokazuje, jest gorsza niż brak filtru.
- Widok pokazuje 60 pozycji i **mówi, ile ukrył**. Ciche ucięcie listy czyta się jak
  „to wszystko, co jest".

Próbka rozkłada się co n-tą pozycję zamiast brać pierwsze n. Branie początku listy
ściągało wszystko do jednego dnia i filtry okresu świeciły pustką.

## 12. „Godziny dofinansowane" to teraz „wizyty niskopłatne"

Nazwa nie mówiła, o co chodzi — pytanie klienta „co to są godziny finansowane" jest
tu dowodem. Liczymy spotkania, nie minuty: „3 z 10 wizyt" nie wymaga tłumaczenia,
a „pozostało 1 h 40 min" generuje telefon do koordynatora. Limit to `limitNiskoplatnychWizyt: 10`
(pierwotnie 4, podniesiony na wniosek fundacji — patrz rozdział 24),
koordynator może go podnieść pojedynczemu pacjentowi.

## 13. Każdy pacjent ma jednego psychologa prowadzącego

Przypisuje się **sam**, przy pierwszej odbytej wizycie — nikt tego nie ustawia ręcznie
i nie ma ekranu „przypisz psychologa". Prowadzący jest widoczny w panelu pacjenta,
na czele wyszukiwarki terminów i w kolumnie kartoteki koordynatora.

Umówienie się u kogoś innego jest **dozwolone i nie zmienia przypisania** — w podsumowaniu
rezerwacji pojawia się wtedy zdanie o tym, kto prowadzi. Stała zmiana prowadzącego wymaga
potwierdzenia koordynatora. Alternatywa (blokada rezerwacji poza prowadzącym) odpadła:
przy terapii kryzysowej liczy się najbliższy wolny termin, nie ciągłość opieki.

## 14. Publikacja jest skryptem, bo ręczne kopiowanie dało biały ekran

Do repozytorium trafiła zawartość `dist/` z absolutnymi ścieżkami `/assets/…`.
Strona stoi pod `/rezerwacje-makieta/`, więc przeglądarka szukała skryptu
o poziom wyżej, dostawała 404 i pokazywała **biały ekran — bez błędu, bez śladu**.
Nic w projekcie nie mogło tego złapać: typy przechodziły, `sprawdz-ekrany.mjs`
testuje źródła przez esbuild, nie plik wynikowy, a `npm run build` nie ma pojęcia,
pod jakim adresem plik wyląduje.

Wnioski wpisane w kod, nie w nawyk:

- **`skrypty/opublikuj.mjs`** — do repozytorium idzie dokładnie jeden plik.
  Skrypt sam sprząta pozostałości po nieudanych publikacjach (zostawione `assets/`
  przykryłoby nowy plik) i odmawia wypchnięcia, gdy plik odwołuje się do czegokolwiek obok.
- **Asercja samowystarczalności** w `zbuduj-jeden-plik.mjs` — plik nie wychodzi
  z fabryki z odwołaniem do zasobu zewnętrznego. Sprawdza też ścieżki ukryte
  w `url()` i w stringach JS, których nie widać jako `src`/`href`.
- **Kontrola świeżości** — publikacja odmawia, gdy źródła są nowsze niż zbudowany plik.
  Wypchnięcie makiety sprzed trzech rund wygląda identycznie jak wypchnięcie aktualnej;
  różnicę widać dopiero, gdy klient mówi „przecież to poprawiałeś".

Oba zabezpieczenia sprawdzone testem negatywnym — sztucznie wstrzyknięte
`<script src="/assets/…">` zatrzymuje publikację z nazwą winnego pliku.

Osobno warto zapamiętać: **jsdom nie wykonuje `<script type="module">`**.
Test jsdom na pliku publikacyjnym pokazuje zero znaków także dla wersji,
która działa poprawnie — nie nadaje się do weryfikacji artefaktu i nie wolno
z jego wyniku wnioskować o awarii.

## 15. Przegląd regresji: 32 błędy, z czego trzy zmieniały odpowiedzi na ekranie

Po rundzie zmian puściliśmy przegląd czterech wymiarów (grafik, filtry rezerwacji,
limit i prowadzący, proces publikacji), każde znalezisko przechodziło przez
weryfikatora, którego zadaniem było je **obalić**. Przeżyły 32. Trzy krytyczne
warto zapamiętać, bo wszystkie wyglądały na ekranie zupełnie zwyczajnie:

**Doba ruchoma zamiast dnia kalendarzowego.** Filtr okresu liczył
`godzinDo(termin) / 24`, czyli okno od „teraz”. O 14:30 „Dzisiaj” ukrywało
50 dzisiejszych wizyt i pokazywało 42 jutrzejsze — z datą jutrzejszą, którą
koordynator brał za dzisiejszą. Stąd `dniDo()` w `format.ts`: liczy pełne dni
od północy i przetrwa zmianę czasu.

**Wszyscy pacjenci z tym samym prowadzącym.** `wizytyPsychologa` sortuje wizyty
w obrębie jednego specjalisty, więc `flatMap` po zespole daje listę pogrupowaną
ludźmi, nie czasem. „Pierwsza odbyta wizyta” oznaczała pierwszą napotkaną w pętli,
czyli zawsze wizytę specjalisty o najniższym id. Kartoteka pokazywała 14 razy
to samo nazwisko i wyglądała poprawnie. Sortowanie chronologiczne jest tu
warunkiem poprawności, nie kosmetyką.

**Za mała pula pacjentów.** Czternaście nazwisk na 2758 wizyt dawało ~197 wizyt
na osobę w pięć miesięcy. Licznik limitu pokazywał „64 z 4” (limit wynosił wtedy 4) i każdego pacjenta
jako wyczerpanego — ekran działał, tylko nic nie znaczył. Dane makiety muszą mieć
wiarygodne proporcje, inaczej nie da się na nich zobaczyć reguły, którą ilustrują.

Wspólny wniosek: **żaden z tych błędów nie był widoczny gołym okiem**. Ekran
pokazywał nazwiska, liczby i wizyty — wszystko na swoim miejscu. Dopiero
policzenie pokazało, że odpowiedzi są nieprawdziwe. Dlatego cztery z tych
sprawdzeń są teraz stałą częścią `sprawdz-ekrany.mjs`: prowadzący muszą być
rozłożeni na wielu specjalistów, wizyt na pacjenta poniżej czterdziestu, limit
musi różnicować pacjentów, a filtr „dzisiaj” nie może wpuścić ani jednej wizyty
z innego dnia.

Reszta znalezisk to spójność: liczniki na chipach liczyły z całej puli zamiast
z przefiltrowanej listy (chip obiecywał 12, klik dawał zero), oś czasu historii
wypisywała zdarzenia z przyszłości i księgowała płatność przy statusie
„czeka na płatność”, siatka miesiąca nie była wyrównana do kolumn dni tygodnia,
a wyszukiwarka mówiła „Twój psycholog prowadzący” gościowi i koordynatorowi.

## 16. Anonimizacja: makieta krąży dalej niż strona

Siedem prawdziwych profili z niepodzielni.com zostało zastąpionych wymyślonymi.
Powód nie jest formalny: makieta trafia na prezentacje, do plików wysyłanych
mailem i na dysk każdego, kto ją otworzy. Osoba, która zgodziła się na profil
na stronie fundacji, nie zgodziła się tym samym na występowanie w makiecie
systemu, który jeszcze nie istnieje.

Zachowana została **struktura**, bo to ona buduje ekran: rozkład nurtów, obszarów
pomocy, form pracy i stawek odpowiada rzeczywistości — w tym jedna osoba
przyjmująca wyłącznie stacjonarnie i jedna z uprawnieniem do diagnozy ADHD.
Bez tego filtry pokazywałyby fikcyjne proporcje.

Wyjątek: `DECYZJE.md` nadal wymienia prawdziwe nazwiska w rozdziale o błędach
znalezionych na produkcji. Raport o cudzej stronie bez wskazania konkretnego
profilu jest bezużyteczny, a ten dokument nie jest makietą.

## 17. Typ płatności dostał własny kanał, a nie kolory marki

Specyfikacja żądała zieleni dla wizyt niskopłatnych i błękitu dla pełnopłatnych.
Problem: `--np-primary` (zieleń) znaczy w systemie „akcja / sukces”, a
`--np-secondary` „nagłówek / tożsamość”. Kolor o dwóch znaczeniach na jednym
ekranie przestaje cokolwiek znaczyć — zielony przycisk „Zarezerwuj” i zielony
badge „Potwierdzona” zaczęłyby kłamać.

Rozwiązanie: typ płatności nosi **własne kroki**, używane wyłącznie przez kropki,
paski i segmenty wykresów. Nigdy przyciski, nigdy statusy.

Kroki dobrane walidatorem, nie okiem:

| slot | kolor | kontrast | deuteranopia | tritanopia |
|---|---|---|---|---|
| niskopłatne | `#0f8f43` | 4,42 : 1 | ΔE 28,4 | ΔE 11,2 |
| pełnopłatne | `#3a45cf` | 4,58 : 1 | — | — |

Zieleń marki `#01be4a` odpadła (kontrast 2,42 : 1 przy progu 3 : 1), granat
`#1500bb` wypadł poza pasmo jasności. Kolor nigdzie nie jest jedynym nośnikiem:
każda kropka ma podpis, każdy segment wartość, legenda jest zawsze obecna.

**Przełącznik jest trójstanowy**, nie dwustanowy. „Łącznie” jest domyślne, bo
pierwsze pytanie brzmi „ile tego jest”, a dopiero drugie „ile dokłada fundacja”.
Sam dwustan zmuszałby do przeklikiwania i zapamiętywania liczby z poprzedniego
widoku za każdym razem, gdy ktoś chce poznać proporcję.

## 18. Filtr w każdej sekcji ma cenę i trzeba ją nazwać

„Ile mieliśmy wizyt w tym roku” i „jakie jest obłożenie w tym miesiącu” to dwa
niezależne pytania — wspólny filtr każe porzucić jedno z nich. Stąd niezależny
zakres w każdej sekcji pulpitu.

Cena: raport, w którym sekcje mówią o różnych okresach. Dlatego zakres jest
wypisany w nagłówku sekcji, powtórzony przy każdej pozycji w oknie eksportu,
a gdy wybrane sekcje mają różne okresy — pojawia się ostrzeżenie. Bez tego ktoś
zestawiłby marzec z rokiem i wyciągnął wniosek z porównania, które nie istnieje.

## 19. Alert odwołań liczy sztuki w oknie, nie procent od zawsze

Dwie decyzje, obie zmieniające to, kogo alert wskaże:

- **Okno 30 dni, nie „od zawsze”.** Licznik od początku współpracy przekroczyłby
  dziesiątkę u każdej aktywnej osoby w ciągu roku i alert przestałby cokolwiek
  znaczyć. Sygnał ma dotyczyć tego, co dzieje się teraz.
- **Sztuki, nie procent.** „12% odwołań” u osoby z trzema wizytami i u osoby ze
  stu to zupełnie inna sprawa, a rozmowę trzeba odbyć z tą drugą.

Na danych makiety alert wyzwala się u 2 z 7 osób — próg realnie różnicuje.
Treść jest sformułowana jako zaproszenie do rozmowy z przyciskiem „Napisz do…”,
nie jako kara: dziesięć odwołań w miesiącu prawie zawsze znaczy, że po drugiej
stronie dzieje się coś, czego nie widać w grafiku.

## 20. Faktura specjalisty a niewidoczna prowizja

Fundacja pobiera 20%, ale specjalista tego nie widzi — tak ustaliliśmy wcześniej.
Faktura musi mieć kwotę, więc albo trzeba prowizję ujawnić, albo fakturować
kwotę już po niej.

Wybrane: system podpowiada **wynagrodzenie specjalisty**. Z jego perspektywy to
stawka za wykonane wizyty i nic w dokumencie nie sugeruje, że pacjent zapłacił
inaczej. Wariant „system generuje fakturę sam” odpadł, bo specjaliści rozliczają
się na różnych zasadach — działalność, zlecenie, ryczałt — i dokument musi
wychodzić z ich własnej księgowości.

## 21. Symulator SMS ujawnił koszt, o którym nikt nie pomyślał

Ekran powstał po to, żeby fundacja przeczytała treści przed wdrożeniem. Przy
okazji licznik segmentów pokazał rzecz niewidoczną w kodzie: **polskie znaki
kosztują 136 zł miesięcznie, czyli 46% rachunku za SMS-y**.

Diakrytyki wypychają wiadomość z alfabetu GSM-7 (160 znaków na segment) do UCS-2
(70 znaków). Sześć z siedmiu szablonów przekracza limit, przypomnienie dzień
wcześniej zajmuje trzy segmenty zamiast jednego.

Nie rekomendujemy pisania bez ogonków — wiadomość od fundacji pomocowej wygląda
wtedy jak spam i to może kosztować więcej niż te złotówki. Tańsza droga to
skrócenie treści; trzy szablony przekraczają limit o kilkanaście znaków.

Osobna zasada dla tych treści: **ani słowa o zdrowiu**. SMS wyświetla się na
zablokowanym ekranie, często przy innych ludziach, a dla części pacjentów sam
fakt korzystania z pomocy jest informacją wrażliwą.

## 22. Wymuszony zrzut ekranu i czego przeglądarka nie potrafi

Zgłoszenie błędu nie da się wysłać bez zrzutu — blokada, nie ostrzeżenie.
Opis „nie działa rezerwacja” bez obrazka kosztuje dwie wymiany maili, zanim
w ogóle wiadomo, o który ekran chodzi.

Ograniczenie do zakomunikowania fundacji przed wdrożeniem: **przeglądarka nie
zrobi zrzutu po cichu**. Screen Capture API wymaga zgody i pokazuje systemowy
wybór okna, więc w działającym systemie użytkownik zobaczy jeszcze jedno okno.
W makiecie przechwycenie jest natychmiastowe — i to jedyne miejsce, gdzie
makieta obiecuje płynniej, niż da się dowieźć.

## 23. Drugi biały ekran: wyjątek w czasie wykonania

W `sms.ts` wyrażenie regularne z zakresem `\x00–\x7f` zapisało się do pliku jako
**surowe bajty NUL i DEL**, a nie jako sekwencje ucieczki. Przy składaniu jednego
pliku HTML zamieniły się w znak zastępczy, wyrażenie stało się niepoprawne
(„Range out of order in character class”), aplikacja rzuciła wyjątek przy starcie
i `#root` został pusty.

Co przepuściło ten błąd: **wszystko**. Typy przeszły, smoke test przeszedł,
kontrola struktury artefaktu przeszła — bo błąd ujawniał się dopiero przy
uruchomieniu kodu, a żadna z tych kontroli kodu nie uruchamiała.

Naprawa dwustopniowa:

1. Regex zastąpiony porównaniem `charCodeAt` — kod bez znaków spoza ASCII
   nie ma jak zepsuć się przy zmianie kodowania.
2. **Publikacja uruchamia plik w Chrome headless** i sprawdza, czy `#root`
   ma treść. Plus skan źródeł na surowe bajty sterujące. Obie bramki sprawdzone
   na zepsutym pliku — zatrzymują publikację.

Wniosek ogólniejszy niż ten błąd: kontrola statyczna nie zastąpi uruchomienia.
Pierwszy biały ekran (ścieżki `/assets/…`) dało się złapać analizą tekstu, drugi
nie — i dopiero po nim proces ma bramkę, która łapie obie klasy.

## 24. Limit niskopłatnych działa po stronie podaży

Dwa limity o różnym celu:

- **10 wizyt na pacjenta** — ile fundacja dofinansowuje jednej osobie.
- **4 terminy tygodniowo na specjalistę** — ile godzin niskopłatnych w ogóle
  trafia do puli.

Drugi jest ważniejszy i działa przy **wystawianiu** grafiku, nie przy rezerwacji.
Gdyby blokada siedziała na końcu, pacjent widziałby wolny termin i dostawał
odmowę przy płatności — w najgorszym możliwym momencie, po podjęciu decyzji
i wyjęciu karty.

## 25. Zwroty wykonuje człowiek, nie system

Fundacja klika zwrot w panelu Stripe; system tylko pokazuje, co i komu się należy.
Automatyczny zwrot przez API wymaga uzgadniania stanu między systemem a Stripe,
obsługi zwrotów nieudanych i częściowych — czyli najdroższego kawałka integracji
płatniczej. Przy kilkudziesięciu zwrotach miesięcznie ręczne kliknięcie jest tańsze
niż utrzymywanie tej automatyki.

Konsekwencja dla języka interfejsu: **nigdzie nie wolno napisać „zwrot wykonany”**.
Wszędzie jest „zwrot do wykonania”, a koordynator dostaje listę zadań.

## 26. Eksport PDF: prawdziwy dokument, nie powiadomienie

Makieta otwiera gotowy dokument HTML z arkuszem druku i oknem drukowania —
użytkownik wybiera „Zapisz jako PDF” i dostaje plik. Układ, nagłówki, stopki
i podział na strony są takie, jakie mają być docelowo, więc fundacja ogląda
realny dokument, a nie jego opis.

Czego ta droga nie potrafi i co dokłada backend w PHP: wygenerowania pliku bez
udziału człowieka oraz wysłania go mailem albo zarchiwizowania automatycznie.

Każda sekcja drukuje **swój** zakres dat, bo filtry są niezależne. Bez tego ktoś
zestawiłby w raporcie marzec z rokiem.

## 27. Maile edytuje koordynator, SMS-y nie

Rozdział uprawnień wynika z kosztu, nie z ostrożności. SMS ma sztywny limit
znaków i płaci się za segment — dopisanie jednego zdania potrafi podwoić rachunek
za wysyłkę idącą codziennie do wszystkich pacjentów. Mail nie ma tego ograniczenia,
a to właśnie w mailach fundacja najczęściej chce coś dopisać.

Cena swobody: da się wysłać maila z napisem „{imie}” do trzystu osób. Dlatego
edytor sprawdza zmienne i nie pozwala zapisać szablonu z nazwą, której system
nie umie podstawić.

Świadomie bez edytora z formatowaniem. Maile transakcyjne mają dotrzeć i być
przeczytane także w kliencie sprzed dekady — zwykły tekst dociera zawsze,
rozbudowany HTML potrafi wylądować w spamie.

## 28. Faktura przechodzi przez akceptację, i to chroni specjalistę

Obieg: specjalista przesyła → koordynator sprawdza → przelew. Środkowy krok
istnieje, bo kwota z faktury bywa **niższa** od rozliczenia systemu: specjalista
liczy z własnego kalendarza i pomija godziny opłacone mimo nieobecności pacjenta.

Automatyczna akceptacja oznaczałaby, że fundacja płaci mniej, niż powinna,
a specjalista dowiaduje się o tym po miesiącach albo nigdy.

Status nazywa się **„do poprawy”, nie „odrzucona”**. Odrzucenie brzmi jak zarzut,
a w dziewięciu przypadkach na dziesięć chodzi o pomyłkę w arytmetyce — i o człowieka,
z którym fundacja chce dalej pracować.

## 29. Przełącznik trybu potrzebował paska, nie tylko przycisku

Sam przełącznik w rogu nie wystarczył: przy zawężonym widoku koordynator patrzył
na listę i nie wiedział, że część danych jest ukryta. Liczby wyglądają poprawnie
niezależnie od tego, czy są pełne — a to najgorszy rodzaj pomyłki, bo nic
nie sygnalizuje błędu.

Stąd pasek pod nagłówkiem: pełna szerokość, kolor trybu, wersaliki. Ma być trudny
do przeoczenia, nawet gdy ktoś patrzy wyłącznie na tabelę.

Tryb zawęża pulę **przed** pozostałymi filtrami, więc kafle, chipy statusów
i tabela mówią o tym samym zbiorze.

## 30. Dwa dokumenty, bo dwóch czytelników

Fundacja i wykonawca potrzebują opisu tego samego systemu, ale odpowiedzi na inne
pytania. Dokument napisany dla obu naraz nie trafia do żadnego: fundacja grzęźnie
w indeksach bazy, wykonawca w opisie, co pacjentka widzi na ekranie.

**Zakres wdrożenia** — dla wykonawcy. Sześć modułów, 150 zadań, 916 podzadań.
Przy zadaniach zależności i ryzyka, czyli to, co może podnieść koszt. Podzadanie
jest czynnością, nie kategorią: „wprowadzić pola `kwota_zamrozona`
i `regula_anulacji_zamrozona` zapisywane w chwili zakupu", a nie „model danych".

**Jak działa system** — dla fundacji. Siedem ścieżek krok po kroku, przy 37 z 49
kroków osobna kolumna „co robi system w tle": kiedy wychodzi mail, kiedy SMS,
co się blokuje. To pytanie wraca w telefonach od pacjentów częściej niż
jakiekolwiek inne, więc dostało własne miejsce w układzie strony.

Do tego 150 zasad, każda z uzasadnieniem, które koordynator może powtórzyć
pacjentowi. Nie „zgodnie z regulaminem", tylko: *„Wybrany termin jest trzymany
10 minut. Krócej — nie zdąży ktoś, kto płaci przelewem albo szuka karty. Dłużej —
jedna niedokończona rezerwacja blokuje godzinę na pół dnia."*

### Bez godzin i kwot — na wyraźne życzenie, ale też z powodu

Zakres jest stały: te funkcje system ma mieć. Wycena zależy od kolejności wdrażania
i od tego, ile fundacja robi sama. Liczba wpisana obok zadania zaczyna żyć własnym
życiem i wraca w rozmowie jako zobowiązanie, także wtedy, gdy zakres tego zadania
w międzyczasie urósł.

### Jak powstały

Z odczytu kodu, nie z pamięci. Pięć niezależnych przebiegów po obszarach makiety,
plus szósty, którego jedynym zadaniem było **znaleźć braki w poprzednich pięciu**.
Wynik tego szóstego to cały moduł „Wdrożenie, utrzymanie i zgodność" — 24 zadania,
których nie widać na żadnym ekranie i które przeoczyli wszyscy opisujący ekrany:
środowiska, kopie zapasowe z ćwiczeniem odtworzeniowym, monitoring, wyłączenie
Bookero, dokumentacja RODO poza kodem, audyt WCAG, szkolenie zespołu.

Kontrola wyłapała też realną niespójność: limit wizyt niskopłatnych wynosił w kodzie
10, a w tym dzienniku w dwóch miejscach 4 — ślad po podniesieniu limitu, którego
nie doprowadzono do dokumentacji. Poprawione.

### Generowane skryptem, nie klikaniem

`npm run dokumenty` składa oba pliki bez udziału człowieka i odmawia zapisania
dokumentu, który nie zaczyna się od `%PDF-` albo ma mniej niż pięć stron. Chrome
potrafi zapisać pustą skorupę, gdy arkusz druku nie zdąży się zastosować — a pusty
PDF wygląda dokładnie tak samo jak pełny, dopóki się go nie otworzy.

Ręczne generowanie kończy się wysłaniem klientowi wersji sprzed trzech rund poprawek.
