# ZLECENIE-006 — weryfikacja krzyżowa rundy 1 (gabinet)

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-006`, odpowiedz `ODPOWIEDZ-006.md`

---

## ZASADA STAŁA S-1 — SUBAGENCI (nowa, od teraz obowiązuje na stałe)

**Właściciel zezwala każdej sesji na uruchamianie subagentów — do 10 równocześnie.**
To nie jest pozwolenie jednorazowe. Korzystaj, kiedy zadanie się rozgałęzia.

Sześć reguł, bez których subagenci obniżą jakość zamiast podnieść tempo:

1. **Raport subagenta to TWIERDZENIE, nie pomiar.** Liczy się dopiero z surowym dowodem:
   polecenie, kod wyjścia, **pełne wyjście w pliku**. „Subagent napisał, że zielone" to zielone
   od autora, tylko piętro niżej — i groźniejsze, bo brzmi jak cudza weryfikacja.
   **Subagent nigdy nie jest weryfikatorem krzyżowym.**
2. **Równolegle wyłącznie to, co ma ROZŁĄCZNY stan.** Wszystko, co mutuje stan współdzielony —
   drzewo robocze, baza, demon Dockera, porty, **tagi obrazów** — biegnie **szeregowo**.
   To jest dosłownie klasa P3: zasób globalny dla demona wymaga zamka globalnego dla maszyny.
   Dwie równoległe perturbacje dadzą czerwień z cudzej przyczyny, a wygląda to identycznie
   jak działająca kontrola.
3. **Odmowa startu jest ochroną, nie przeszkodą.** Twoja klamra odmawia przy pozostałości.
   Nie obchodź tego, dając każdemu subagentowi własną kopię, zanim sprawdzisz, czy chroniony
   zasób jest naprawdę „per kopia".
4. **Subagent dostaje kulturę w prompcie.** Minimum: „mierz, nie czytaj" · „trafienie grepa to
   nie kod — otwórz kontekst" · „pełne wyjście do pliku, filtruj plik" · „nie znalazłem ≠ nie ma"
   · „pustka to błąd, nie zero wyników". **Zmierzone: wytyczne się same nie propagują.**
5. **Werdyktu nie delegujesz.** Autorem odpowiedzi jesteś Ty i odpowiadasz też za to,
   czego subagent nie sprawdził.
6. **Powiedz, co robili.** Tabela: ilu, co dostali, który zwrócił surowy dowód, a który prozę.
   Wynik bez dowodu → NIEROZSTRZYGNIĘTY.

Opłacają się przy: czytaniu i przeszukiwaniu rozłącznych plików, niezależnych próbach obalenia
tej samej tezy z różnych stron, szkicach adaptacji. Nie opłacają się przy niczym, co uruchamia
suitę albo dotyka bazy.

---

## CZYM JEST WERYFIKACJA KRZYŻOWA — trzy czynności, wszystkie wymagane

- **(A) Próba obalenia na materiale.** Wskaż KONKRETNY scenariusz, w którym cudzy mechanizm daje
  ZIELONE przy żywej wadzie. „Może nie pokryć wszystkiego" nie jest obaleniem.
- **(B) Adaptacja u siebie i URUCHOMIENIE.** Minimalna wersja mechanizmu we własnym repo,
  z pomiarem. Jeśli twierdzisz, że u Ciebie tej klasy nie ma — **udowodnij pomiarem**;
  odczyt kodu nigdy nie dowodzi nieistnienia.
- **(C) Werdykt** z pięciu: `POTWIERDZONE` · `OBALONE` · `ZŁA WAGA` · `ZŁA DIAGNOZA` ·
  `ZALECENIE SZKODLIWE`, plus jawna lista „czego NIE sprawdziłem".

**Adaptacja JEST weryfikacją** — słowami kont: *„kto adaptuje, weryfikuje u siebie; adaptacja
bez weryfikacji to droga, którą błędny opis P2 trafił do helpdesku"*.

**Cudze repozytoria: ODCZYT DOZWOLONY, ZAPIS ZABRONIONY.** Pliki masz na dysku pod
`D:\KOD\Niepodzielni\<repo>\`. Bierz wzorce **z kodu**, nigdy z mojego opisu — przekręciłem
Twój wzorzec P2 dwa razy w ciągu doby.

---

## TWOJE DWA PRZEDMIOTY

### 1 · P1 kont — rejestr pokrycia i zapadka

Materiał: `niepodzielni-konta/docs/ZLECENIA/ODPOWIEDZ-005.md` §3.

Trzy pytania, na które chcę odpowiedzi popartej pomiarem:

- **Czy zapadka daje się obejść?** Rejestr `tests/pokrycie.tsv` + runner liczący dwie liczby.
  Znajdź drogę, którą nowa asercja przechodzi bez kompletu kierunków. Konta same podały
  perturbacje M1–M5 — **to jest projekt, nie wykonanie**; ich nikt nie uruchomił.
- **Czy rejestr sam nie jest kontrolą bez zdolności czerwienienia?** Konta zauważyły to
  i nazwały (§3.6), powołując się na Twoją klasę 3. Sprawdź, czy ich odpowiedź wystarcza.
- **NAJWAŻNIEJSZE — czy warunek, który przyjęły, jest tym, co faktycznie zamknąłeś?**
  Konta warunkują domknięcie P1 zamknięciem Twojej klasy 3. Ty zamknąłeś **mechanizm dwóch
  z dziewięciu członków** (R6B-13, R6B-15) i powiedziałeś to wprost. **Czy P1 wolno domknąć
  przy siedmiu otwartych?** To Twoja ocena, nie ich — i nie moja.

### 2 · Narzędzie perturbacji odwrotnej hubu

Materiał: `hub/scripts/perturbacja-odwrotna.sh`, `hub/scripts/perturbacje-odwrotne/POPRAWKI.txt`,
opis w `hub/docs/ZLECENIA/ODPOWIEDZ-005.md` część B.

- **Dowód mutacji przez `git diff --stat` — czy to wystarcza?** U Ciebie dowód zniknięcia
  wymagał **odczytu bazowego**, bo `! grep -q 'stary tekst'` ma gałąź zdegenerowaną. `git diff
  --stat` mówi „plik się zmienił"; **nie mówi, że zmiana weszła w mierzoną ścieżkę.**
  Masz najmocniejszy przyrząd na martwe mutacje w całym ekosystemie — użyj go tutaj.
- **Zero kandydatów przy pięciu poprawkach.** Hub zapisał to jako NIEROZSTRZYGNIĘTE, bo zero
  pasuje też do „żaden test nie dotyka tych ścieżek", i sam wskazał, że dobrał zestaw
  w najmniej czułą stronę (4 dodające, 1 zaciśnięcie). **Uruchom to narzędzie u siebie
  z zestawem, w którym co najmniej połowa deklaracji ZACIEŚNIA.** Twoja suita ma 183 zielone —
  jeśli u Ciebie też wyjdzie zero, to jest wynik o innej wadze niż u nich.
- Hub sam nazwał brak: **towarzysz mierzący, czy poprawiona ścieżka jest w ogóle wykonywana**.
  Jeśli umiesz go dorobić tanio — dorób i zmierz. Jeśli nie — powiedz dlaczego.

---

## PRZEKAZ, KTÓRY MUSISZ POTWIERDZIĆ

Konta poprosiły o **kształt allowlisty przyczyny czerwieni** — w wersji, która realnie zawęża,
nie o parafrazę. **Wyjąłem im to z Twojego kodu dosłownie**, bez opisywania własnymi słowami.
Wysłałem `skrypty/perturbacje.sh:230-288` (ciało `oczekuj_czerwone`, w tym dopasowanie z `-i`
i komentarz o „ZAMROŻONĄ") oraz cztery wywołania z linii 883–884, 903–904, 1045–1046, 969–970
jako przykłady zawężenia `--przyczyna` + `--filter`.

**Sprawdź, czy zacytowałem właściwe miejsca** i czy któreś z tych czterech nadal nie zawęża.
Jeśli się pomyliłem, powiedz — dwa razy przekręciłem Twój wzorzec i nie zamierzam zrobić tego
trzeci raz.

---

## FORMA ODPOWIEDZI

Nagłówek z pomiarem kanału · tabela subagentów · per przedmiot: (A), (B) z surowym wyjściem,
(C) werdykt · osobna sekcja „czego NIE sprawdziłem" · **własne nieudane próby obalenia**
(raport przemilczający porażki jest nieprawdziwy).

**Nie ruszaj przedmiotu.** To nadal runda przyrządu. Klasa 3 (siedmiu otwartych członków)
i `R6A-11` czekają na rundę 2 — **nie zaczynaj jej sam**.

## ZAKAZY

Zero `main`, merge, deploy · zero zapisu w cudzych repozytoriach · nic poza fundację ·
sekretów nie zapisujesz do plików. Sprzeczność zlecenia z zasadą twardą → **zgłoś, nie wybieraj
po cichu**.
