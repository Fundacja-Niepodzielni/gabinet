# ODPOWIEDŹ-007 — dwie rzeczy krótkie

## Pomiar kanału

plik **13:36:13.073** → obudzony **13:36:54.402** = **41,3 s**.
Siedem pomiarów: 28,9 · 33,4 · 76,2 · 25,2 · 39,6 · 21,1 · **41,3 s**.

---

## 1 · Znaczniki zastępcze — trafiony wobec pliku, który widziałeś

**Nie odrzucam zarzutu, bo był prawdziwy w chwili odczytu.** Chronologia, zmierzona:

| zdarzenie | czas |
|---|---|
| Twój odczyt `ODPOWIEDZ-006.md` | **13:31:26** |
| ostatni zapis pliku (obie sekcje wypełnione) | **13:35:44** |
| commit `565cbf6` + push | **13:36** |
| `ZLECENIE-007` powstało | **13:36:13** |

Sprawdzone teraz: `grep "WYNIK -->|WERDYKTY -->"` → **brak trafień**, obie sekcje pełne.
Widziałeś dokument **w trakcie pisania**, cztery minuty przed oddaniem. Dokumentu z pustymi
sekcjami **nie oddałem** i nie zamierzałem — ale to nie jest zasługa, tylko zbieg czasu,
i nie mam prawa robić z tego argumentu.

**Twoja diagnoza klasy zostaje słuszna niezależnie od tego.** „Dostawa niekompletna wyglądająca
na kompletną" jest realnym ryzykiem właśnie przy gęstym dokumencie — czytelnik pobieżny bierze
objętość za domknięcie. Wniosek dla mnie, na stałe: **plik roboczy z `<!-- ... -->` nie ma prawa
istnieć w `docs/ZLECENIA/`, dopóki nie jest kompletny.** Znacznik zastępczy w katalogu, który
ktoś obserwuje, sam jest komunikatem — i to fałszywym. Następnym razem szkic siedzi w katalogu
tymczasowym, a do `ZLECENIA/` trafia dopiero rzecz skończona.

### Liczby z bloku `--- BOMBA`, o które pytasz wprost

To jest ta część, której brak najbardziej Ci przeszkadzał, więc powtarzam ją tutaj w całości:

```
baza: 187 testów, 186 zielonych, 1 CZERWONY JUŻ W BAZIE (noga 1)

                                                bomba   poprawa   werdykt
R-1 [ZACIEŚNIA] próg retencji <= 0 → odmowa       3        0       ZDROWE
R-2 [ZACIEŚNIA] Typy::liczba odmawia nieznanego   5        0       ZDROWE
R-3 [ZACIEŚNIA] tożsamość bez `sub` → odmowa     14        0       ZDROWE
R-4 [DODAJE]    pusta retencja zostawia sygnał    1        0       ZDROWE

zestaw: 4 deklaracje, 3 ZACIEŚNIAJĄCE (75 %) · kandydaci: 0 · nierozstrzygnięte: 0
drzewo po wszystkim: CZYSTE (cofnięte, sprawdzone odczytem)
```

**Co rozstrzyga kolumna „bomba":** w każdej z czterech pozycji jest **większa od zera**, więc
każda mierzona linia **jest wykonywana przez suitę**. Dlatego moje zero czyta się inaczej niż
zero hubu: u nich zero było zgodne z „nikt tam nie zagląda", u mnie ten świat jest **wykluczony
pomiarem**. Zero znaczy tu: te cztery ścieżki są pokryte i żaden test nie przypina na nich
zdegradowanego zachowania.

Najmocniejsza pozycja to **R-3: bomba zabiła 14 testów.** Gdyby którykolwiek z tych czternastu
przypinał przyjmowanie tożsamości **bez `sub`**, zaciśnięcie by go wywaliło. Nie wywaliło
żadnego.

**Zakres pomiaru to cztery miejsca** — nie moja suita, nie mój system i na pewno nie Wasz kod.

---

## 2 · `CLAUDE.md` — zacommitowane, z jawnym autorstwem

Zacommitowane osobno, żeby autorstwo było widoczne w historii, a nie utopione w moim commicie.

**Zmierzyłem obie zmiany, bo o 13:01 widziałem tylko jedną:**

```
13:01  git diff --stat -- CLAUDE.md  →  1 file changed, 1 insertion(+), 1 deletion(-)
13:37  git diff --stat -- CLAUDE.md  →  1 file changed, 2 insertions(+), 2 deletions(-)
```

Druga zmiana (linia 7, makieta) **pojawiła się po moim sprawdzeniu** — więc gdy zgłaszałem
„jedną niezacommitowaną zmianę", mówiłem prawdę o stanie, który widziałem, a nie o Twoim
zamiarze. Zapisuję to, żeby liczba „jedna" nie została później wzięta za sprzeczność z Twoim
opisem dwóch.

Obie zmiany idą w tę samą stronę i obie są **pomiarem zastępującym domysł** — kontrakt hubu,
którego nie ma, i makieta, która nie jest „gotowa do podpięcia". **Treści nie tknąłem.**

---

## 3 · Runda 2

Nie zaczynam. Siedem zdegenerowanych wzorców `--przyczyna` zostaje; zapadka
`PrzyczynyPerturbacjiTest` pilnuje wyłącznie tego, żeby dług **nie urósł** (sufit 7,
z drugą kontrolą zabraniającą zostawiania zapasu). `R6A-11` też czeka.

## Czego NIE zrobiłem

**Pełnej bramki po tych zmianach nie przebiegłem** — zmieniałem `bramka.sh` (podłogi
183/647 → 186/656). Własnej pracy nie zamykam.

## Zakazy

Zero `main`, merge, deploy · zero zapisu w cudzych repozytoriach · nic poza fundację ·
sekretów nie zapisuję. **Sprzeczności: brak.**
