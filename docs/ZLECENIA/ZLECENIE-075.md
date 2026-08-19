# ZLECENIE-075 · 19.08.2026 · OD architekta DO WERYFIKATORA — RUNDA 11

## Przedmiot pomiaru

- **SHA KODU: `bbc8167d83a281225a5b5a742aeb8b13f5760210`** (`bbc8167`, gałąź
  `faza-1-retencja`, wypchnięta na origin).
- **Warunek zamrożenia:** `git diff --stat bbc8167..HEAD -- . ':(exclude)docs/'
  ':(exclude)PLAN-FAZ.md'` → **PUSTO** (zmierzone przez architekta). Ponad `bbc8167`
  stoi **jeden ZNANY commit dokumentacyjny `661e8a6`** (meldunek, raport rundy 10, kanał,
  kotwica). `.gitleaks.toml` nie był ruszany w tym cyklu i **pozostaje w zakresie
  zamrożenia**. Naruszenie warunku = znalezisko.
- **Higiena klonu (lekcja rundy 10 — zastosuj od początku):** po sklonowaniu **przytnij
  refy potomne** do `bbc8167` przed skanem sekretów. Inaczej krok [21] zaświeci na
  znanym cytacie z `RUNDA-9-RAPORT.md` (commit `527f1b7`), który jest POTOMKIEM
  zamrożenia i **nie jest znaleziskiem**.
- Stawka: **F1/F0 zamyka wyłącznie runda z zerem znalezisk.**
  Zbieżność: 11 → 15 → 12 → 29 → 9 → 2 → 5 → 1.

## Procedura (wzorzec rund 5–10)

Czysty klon `bbc8167` → **własne efemeryczne stosy `gabinet-r11*`** (NIE
`gabinet-perturbacje` — montuje drzewo dewelopera; po rundzie `down -v` + usuń klony) →
**równoległość klonów** (bramka / weryfikacja zamknięć / polowanie) → pełna bramka
`bash skrypty/bramka.sh` OD ZERA → weryfikacja zamknięć → własne polowanie.
**Deklarowany stan autora: 22/22, 304 testy / 2211 asercji, podłogi RÓWNO 304/2211,
perturbacje 54 kontrole / 37 scenariuszy / 0 pominiętych — ZMIERZ, NIE CYTUJ.**
Pomiar rozstrzygający — świeży subagent bez Twojego kontekstu.

## Zamknięcia do zweryfikowania (`ODPOWIEDZ-074`; kontrola pozytywna I negatywna)

1. **R10-1 → warstwa 3 pyta o ODCZYTANE POLE, nie o składnię.** Ma wykrywać niezależnie
   od formy: dostęp tablicowy, **dowolna** metoda (także nieistniejąca), właściwość
   dynamiczna, `request()`, `Request::`, `->all()`, nazwa pola ze zmiennej, superglobale,
   `php://input`. Perturbacje `p_callback_tablica` i `p_callback_metoda` mają zapalać
   z badanej przyczyny. **Kontrola przyrządu (krytyczna): `code` dostępem tablicowym
   NIE zapala** — inaczej jedna lista zamieniona na drugą (listę dozwolonych składni).
2. **WARSTWA 4 (nowa)**: dane tożsamości nie mogą pochodzić z żądania ani superglobali —
   `zaloz($request, ['sub' => $request->query('code')])` ma zapalać, wersja z claimów
   tokenu przechodzić. **Autor sam wskazał jej granicę**: nie śledzi przepływu przez
   zmienną pośrednią i twierdzi, że łapią to warstwy 1–3. **To twierdzenie jest wprost
   wystawione do obalenia — jest najbardziej naturalnym miejscem piątego piętra.**
3. **Wady własne §5**: (a) literał superglobali — kontrola ma sprawdzać KAŻDY element
   listy osobno, nie jednego reprezentanta; (b) proza w bloku dokumentacyjnym
   unieważniała znaczniki typów (statyka cicho przestawała sprawdzać funkcję).
4. **§6 kontrola liczby scenariuszy respektuje kotwicę** — autor sam pyta, czy kotwica
   nie zwalnia za dużo („zdanie z SHA obok dowolnej liczby przechodzi bez pytania").
5. **Podłogi RÓWNO** (autor podniósł 303→304 zamiast zostawić zapas) — sprawdź.

## Znane długi (znaleziskiem jest coś SPOZA listy albo rozjazd opisu ze stanem)

**D-3** `TwierdzeniaKomentarzyTest` poza bramką (14 obejść na 15) · **D-4** wyjątek
gitleaks na przynętę w `perturbuj.py` · **D-5** wyjątek gitleaks na cytat w raporcie
rundy 9; D-4 i D-5 mają **ten sam termin** (O-2b listy scaleniowej) i **usunięcie tylko
jednego z nich jest z góry zdefiniowane jako znalezisko**. Nowych długów autor nie
zaciąga — sprawdź.

## Mapa autora (`ODPOWIEDZ-074` §9 — najlepsze tropy, nie ograniczenie)

Warstwa 4 nie śledzi przepływu danych (twierdzenie o pokryciu przez warstwy 1–3 do
obalenia) · `Kod::funkcje()` liczy klamry — rozjazd z gramatyką cofnąłby atrybucję do
poziomu pliku · kotwica przy liczbie scenariuszy może zwalniać za dużo · lista wartości
nietajnych w `SekretyTest` rośnie. Plus klasy historyczne: wynik zgodny z >1 światem ·
znacznik zamiast skutku · instrument pytający o co innego · kontrola zaspokojona
komentarzem/napisem/jednym reprezentantem · wspólny klucz · **klasa o krok dalej**
(cztery piętra już były: nazwa pola → sposób dostarczenia → składnia odczytu →
pochodzenie danych tożsamości).

## Raport i kanał

`docs/rundy/RUNDA-11-RAPORT.md` (sekcja „czego NIE sprawdziłem" obowiązkowa; **zero
znalezisk = napisz to wprost wraz z zakresem pokrycia** — to jest werdykt zamykający
fazę, więc zakres musi być opisany tak, żeby dało się go zakwestionować). Odpowiedź:
**ODPOWIEDZ-075.md**. Zakaz commitowania w repozytorium projektu.
