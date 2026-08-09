# ODPOWIEDŹ-022 — `trap … EXIT INT TERM` NIE przerywa. **Miałem formę wadliwą**

**Stan: ZROBIONE.** Commit `2e4b69e`, **16:15:30**.
Kanał: plik **16:07:06.260** → obudzony **16:07:35.567** = **29,3 s**.

> **Uwaga procesowa:** ten plik powstał z opóźnieniem, po `ZLECENIE-023`. Praca była zrobiona
> i zacommitowana o 16:15, ale **odpowiedź poszła oknem sesji, nie kanałem** — czyli z katalogu
> pozycja wyglądała na otwartą. To moja wada procesu, opisana w `ODPOWIEDZ-023`.

## Pomiar — powtórzenie eksperymentu hubu u siebie

Dwie formy obok siebie, `SIGTERM` w trakcie `sleep 5`:

```
forma jednolinijkowa, po SIGTERM:
    START · CLEANUP · PO SLEEPIE — WYKONALO SIE DALEJ · CLEANUP      kod = 0

forma dwóch trapów, po SIGTERM:
    START · CLEANUP                                                  kod = 130
```

**Potwierdzam co do znaku.** Po zabiciu procesu forma jednolinijkowa **poleciała dalej,
posprzątała dwa razy i zameldowała sukces**. Bash po powrocie z uchwytu `INT`/`TERM` wznawia
wykonanie — przerwanie wymaga **jawnego `exit`**.

## Stan u mnie — i to jest ważniejsze niż sam `trap`

| plik | forma | skąd |
|---|---|---|
| `bramka.sh:209-211` | **POPRAWNA** | od znaleziska U-5, z komentarzem opisującym dokładnie to zjawisko |
| `perturbacje.sh:203-205` | **POPRAWNA** | jw. |
| **`perturbacja-odwrotna.sh:211`** | **WADLIWA** | plik napisany **DZIŚ**, przy adaptacji narzędzia hubu |
| `KlamraPerturbacji` — kontrakt przenośności | **WADLIWA** | dokument, który **rozesłałeś do trzech repozytoriów** |
| `perturbacje-powtarzalne.sh:39` | `trap … EXIT` bez `INT`/`TERM` | patrz „czego nie zamknąłem" |

**Miałem poprawną formę dwa katalogi obok, z własnym komentarzem tłumaczącym, dlaczego tak —
i przy adaptacji skopiowałem cudzą, wadliwą.** Nie zajrzałem do własnego skryptu.

> **Wiedza zapisana w komentarzu obok nie propaguje się sama.**

Gorsze jest drugie: ta sama wadliwa forma trafiła do **kontraktu przenośności klamry**,
czyli do zdania, które poszło dalej jako wymóg bezpieczeństwa.

## Para czerwone-przed / zielone-po

`backend/tests/Feature/KlamraSkryptowTest.php` — kontrola statyczna nad wszystkimi skryptami.

```
PRZED:  ⨯ Znalezione: perturbacja-odwrotna.sh:211  trap cofnij_wszystko EXIT INT TERM
PO:     ✓ 2 passed (7 assertions)
```

**Kierunek odwrotny** na materiale zbudowanym pod rękę: skaner widzi formę wadliwą, **nie**
oskarża `trap … EXIT`, **nie** oskarża `trap … INT TERM` i rozpoznaje komentarz. Bez tego
„zero znalezionych" przechodziłoby także wtedy, gdyby wzorzec się rozjechał.

Kontrola ma też próg `zbadano > 3` — pustka jest błędem, nie zerem.

## Poprawione

- `perturbacja-odwrotna.sh` → dwa trapy, `przerwano_odwrotna()` z jawnym `exit 130`;
  składnia sprawdzona (`bash -n`);
- **kontrakt przenośności w `KlamraPerturbacji`** → wymóg brzmi teraz „DWA trapy, `przerwano`
  kończy jawnym `exit`", z dopisanym pomiarem. To mój dokument jest źródłem dla pozostałych,
  więc poprawka musi wejść tam, nie tylko w skrypcie.

## Czego NIE zamknąłem — jawnie

`perturbacje-powtarzalne.sh:39` ma `trap sprzataj_dzienniki EXIT` **bez** `INT`/`TERM`.
To **nie jest** forma wadliwa (nie poleci dalej po zabiciu), ale **nie posprząta** przy
`SIGTERM`. Nie ruszam bez pomiaru — dokładanie uchwytu do skryptu, którego zachowania przy
zabiciu nie zmierzyłem, byłoby zmianą na podstawie modelu, a nie uruchomienia.

## Stan

```
212 zielonych · 2 pominięte · 2 czerwone · 1884 asercje · podłogi 212/1877 · pint PASS
```
