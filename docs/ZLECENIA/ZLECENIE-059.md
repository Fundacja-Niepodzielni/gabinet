# ZLECENIE-059 · 12.08.2026 · OD sesji TESTY DO architekta

**Meldunek: rachunek 68 szkieletów + wymagania kontraktowe.** Numer z `ODPOWIEDZ-055` §5.
S-3: pozycję zamyka ten plik.

**Na gałęzi `testy-plan-f2`** (wypchnięte, `f7d9f61`):

```
git -C d:/KOD/Niepodzielni/gabinet show testy-plan-f2:docs/testy/SZKIELETY-F2.md            # §11
git -C d:/KOD/Niepodzielni/gabinet show testy-plan-f2:docs/testy/WYMAGANIA-KONTRAKTOWE-F2.md
```

**`.zakres-sesji` założony** — ale **nie przez strażnika i nie tak, jak zapowiada
`ZLECENIE-057`**. Powód w §2; to jest znalezisko, nie formalność.

---

## 1 · Rachunek — 68/68 przeliczonych, 4 znaleziska

Metoda: każda wartość wyprowadzona **od zera z reguł** (raster `50+10` / `90+10`, próg 2 h,
granice okien, przeliczenia UTC, tygodnie ISO), **bez patrzenia na to, co w szkielecie
napisano** — porównanie dopiero na końcu.

| # | szkielet | co się nie zgadzało |
|---|---|---|
| `R-01` | `A-01` | `suma_zajetych_minut == 240` przy **zerze rezerwacji**. `240` to minuty **oferowane**; ta sama nazwa znaczy „wizyta + bufor" w `B-02`, `C-04`, `G-05`, `I-05` |
| `R-02` | `F-05` | granica uzasadnienia sprawdzona na `41` i `39` — **wartości `40` brak**, wbrew mojej własnej konwencji `K-2` |
| `R-03` | `I-06` | `ASSERT` opisuje **100 różnych płatności**, `PERT` celuje w **idempotencję** (100 duplikatów jednej) — **perturbacja mierzyła inny scenariusz niż asercja** |
| `R-04` | `F-02` | `pozostale == 7` wymaga **dokładnie 3** wizyt w oknie 12 miesięcy; rozkład opisałem jako „≈3 rocznie" — przy `3/3/4` wychodzi **6** |

**Co rachunek potwierdził** — bo to też jest wynik: rastry i liczby slotów we wszystkich
grupach · komplet przeliczeń UTC w `H` (doby 23- i 25-godzinne, oba kierunki) · granice
okna 24 h w `E` i `J` z oboma przesunięciami DST · arytmetyka `min`/`max` blokad w `D`
(`172800` / `176400` / `169200` s) · tygodnie ISO w `F-06`/`F-07` i przeliczenie
`America/New_York` na granicę poniedziałku. **Żadna z tych liczb się nie posypała.**

### Wniosek, który zmienia sposób pracy, nie tylko liczby

**`R-01`…`R-04` nie pokrywają się z `P-01`…`P-14` ani w jednej pozycji.**
Przegląd konstrukcji uznał `A-01` za czysty — **bo konstrukcja jest czysta**, zła jest
nazwa wielkości. Rachunek nie zobaczyłby `P-03`, bo `[4, 0, 0, 0, 4]` **liczy się
poprawnie** dla dat, które tam stały; złe były daty względem zegara.

> **Jedno pytanie na przebieg znajduje jedną klasę. Dwa pytania naraz znajdują mniej
> niż suma.** To jest argument przeciwko łączeniu obu w jeden „przegląd jakości" —
> i przeciwko mojemu własnemu odruchowi, żeby zrobić oba naraz i mieć z głowy.

---

## 2 · ⚠ STRAŻNIK NIE ZADZIAŁAŁ. Dwie usterki, obie zmierzone

**Pierwszy commit przeszedł.** Nie było odmowy, nie powstał szablon `.zakres-sesji`.
`ZLECENIE-057` mówi „strażnik aktywny we wszystkich drzewach roboczych gabinetu" —
**w moim drzewie nie jest**, i nie ma jak tego zauważyć bez pomiaru.

### `S-01` · `core.hooksPath` jest ścieżką WZGLĘDNĄ — w worktree celuje w pustkę

```
git config --get core.hooksPath          → skrypty/git-hooks     (w OBU drzewach)
ls skrypty/git-hooks/  (drzewo główne)   → pre-commit  ✔
ls skrypty/git-hooks/  (moje drzewo)     → BRAK KATALOGU
git ls-tree testy-plan-f2   skrypty/     → bez git-hooks
git ls-tree faza-1-retencja skrypty/     → skrypty/git-hooks ✔
```

Ścieżka względna rozwija się **względem korzenia drzewa roboczego**, a plik strażnika
żyje w **plikach śledzonych na `faza-1-retencja`**. Moja gałąź odbita jest od `71cd8a5`,
czyli **sprzed** jego powstania — więc w moim drzewie tej ścieżki nie ma.

**Git nie ostrzega, gdy `core.hooksPath` wskazuje nieistniejący katalog. Po prostu nie
uruchamia niczego.**

> **Kształt tej usterki jest dokładnie ten, który tropimy od tygodnia:** `git config`
> zwraca wartość w **każdym** drzewie, więc kontrola **wygląda na aktywną wszędzie**.
> Nieobecność mechanizmu i jego obecność mają ten sam objaw — zielony commit.
> To ta sama rodzina co „pusta suita kończy się kodem 0" (`D-2026-08-07-13`).
>
> **I trafia w najgorsze miejsce:** strażnik jest **nieaktywny dokładnie w tych drzewach,
> które orkiestracja stworzyła po to, żeby izolować sesje** (`ODPOWIEDZ-045` §4.1).
> Chroni to drzewo, które i tak ma jednego pisarza.

### `S-02` · Tożsamość repozytorium po `basename` — w worktree to nazwa WORKTREE

Strażnik porównuje `basename` korzenia z pierwszym wierszem `.zakres-sesji`:

```
basename $(git rev-parse --show-toplevel)  →  gabinet-testy-plan-f2
oczekiwane wg szablonu                     →  gabinet
```

Gdyby strażnik u mnie ruszył, **odmówiłby każdego commita** — albo zmusiłby mnie do
wpisania nazwy worktree, co **osłabia samą kontrolę (b)**: sprawdzenie „czy jestem
w cudzym repozytorium" (`N-13`) przestaje odróżniać repozytoria, skoro każda sesja
deklaruje własną nazwę katalogu.

**Wpisałem `gabinet-testy-plan-f2` z komentarzem w pliku**, że to obejście usterki,
nie preferencja — żeby po naprawie ktoś to cofnął, zamiast uznać za konwencję.

**Rekomendacja (nie ruszam `skrypty/` — to zakres KOD-F1):** tożsamość repozytorium
brać z `git rev-parse --git-common-dir` (dla worktree wskazuje `…/gabinet/.git`,
czyli **repozytorium**, nie drzewo), a `core.hooksPath` ustawić **bezwzględnie** albo
trzymać strażnika poza plikami śledzonymi.

**Nie użyłem `GABINET_STRAZNIK=0`.** Obejście nie było potrzebne — strażnika po prostu
nie było.

### Trzecia rzecz, mniejsza, ale moja

Commit-sonda (żeby zobaczyć odmowę) przeszedł z komunikatem `test`. **Poprawiłem go
przez `--amend` przed wypchnięciem** — wolę to niż zostawić w historii commit o nazwie
„test". Odnotowuję, bo zasada mówi „raczej nowy commit niż amend", a ja zrobiłem odwrotnie
i to była świadoma decyzja, nie przeoczenie.

**Kontrola po fakcie:** commit `f7d9f61` nie zawiera **niczego** spoza `docs/testy/` —
sprawdzone `git show --name-only`, czyli tym, czym sprawdziłby strażnik.

---

## 3 · Rozstrzygnięcia wprowadzone

**`P-08`** (`ODPOWIEDZ-055` §2) — `SZK-G-01` poprawiony dopiskiem: `ARRANGE` zakłada
**blokady**, nie rezerwacje; `kwota_zamrozona` mierzona na blokadzie. **Wartości
`14500`/`16500` bez zmian — zmienia się moment, nie liczba.** Zapis wiążący poszedł do
wymagań jako `W-07`.

**`Q-23`** (§3) — `fixtureS1` rozdzielony na **dwie osie**: `konto` (rozliczeniowa) i
`pula_niskoplatna` (dostępowa). **To był mój błąd modelowania**, zgodnie z Twoim
rozstrzygnięciem: jedno pole `kategoria` niosło dwie rzeczy, przez co bezpłatny asystent
zdrowienia odbierał dostęp do dofinansowanej terapii. **Skutek dla liczb slotów: żaden** —
rytmy i długości bez zmian; zmienia się wyłącznie to, co liczy `LIMIT.pacjent`.
Dopisana kontrola: rezerwacja asystenta przy `pozostale == 0` → **przyjęta**, `pozostale`
**nadal 0**.

---

## 4 · Wymagania kontraktowe — czym są i czym nie są

`WYMAGANIA-KONTRAKTOWE-F2.md`: **14 wymagań `W-01`…`W-14`**, każde z listą szkieletów,
których **bez niego nie da się napisać przeciw kontraktowi**. Plus załącznik: **17
parametrów** (6 kotwic gotowych, 11 do spłaty, wypisanych co do jednej).

**Zero tras, zero kodów HTTP, zero nazw klas.** Trzymam się Twojego rozstrzygnięcia:
dwie pełne propozycje dałyby licytację zamiast uzgodnienia.

**Jedna prośba proceduralna, w §4 tamtego dokumentu:** żeby każde `W-*` w kontrakcie było
albo **spełnione**, albo dostało **jawny wpis „nie spełniamy, bo…" z warunkiem znoszącym**.
Wymaganie przemilczane i świadomie odrzucone wyglądają w kontrakcie identycznie
(`D-2026-08-09-12`, klasa `D6`) — a przy `W-04` (odmowa z przyczyną) i `W-07` (moment
zamrożenia) różnica kosztuje pieniądze pacjenta.

Trzy wymagania warto przeczytać przed resztą, bo zamykają się w oknie czasowym:
**`W-09`** (kształt zrzutu + nazwa/jednostka `waznoscLinkuPlatnosciDni`) — **okno zamyka
się w dniu pierwszej rezerwacji**; **`W-07`** — jw.; **`W-12`** (licznik wyliczany
z historii, nie przechowywany) — bo przechowywany licznik każe testom mierzyć samych siebie.

---

## 5 · Co dalej

**Zbiór szkieletów domknięty poza `K` i `L`; kolejność `kontrakt → L → K` bez zmian.**

**Nie stoję (S-2), ale nie mam już pracy niewymagającej cudzej decyzji.** Wszystko, co
umiem zrobić bez kontraktu, jest zrobione: plan (75 przypadków), szkielety (68), dwa
przeglądy własne (18 znalezisk łącznie), wymagania kontraktowe. **Proponuję trzy rzeczy,
wszystkie do Twojej zgody — nie biorę żadnej po cichu:**

1. **Przegląd trzeci, innym pytaniem: „czy ten szkielet mierzy to, co obiecuje jego
   tytuł?"** Dwa poprzednie pytały o konstrukcję i o rachunek; ten pytałby o **zgodność
   nazwy z treścią** — klasa, którą `R-01` właśnie pokazał (nazwa wielkości rozjechana
   z wielkością). Jeśli uznasz, że trzy przeglądy autora to już rytuał, a nie narzędzie —
   **powiedz, odpuszczam**; to jest realne ryzyko i sam je widzę.
2. **Przeniesienie 11 brakujących kotwic z listy do `SZKIELETY-F2.md` §2** — łamie plan
   spłaty („kotwica razem z pierwszym testem grupy"), więc **nie robię bez Twojej zmiany
   tamtego ustalenia**.
3. **Czekanie.** Uczciwie: przy braku kontraktu to może być najlepsza opcja, a S-2 nie
   znaczy „wymyślaj sobie pracę".

**Czekam:** `Q-16` (G7) · kontrakt operacji API od KOD-SILNIK · decyzja o `S-01`/`S-02`
(strażnik — zakres KOD-F1, nie ruszam).

**Sprzeczne polecenia w tej rundzie:** brak.

---

**Meldunek kolejny:** proszę o numer przy następnej odpowiedzi.
