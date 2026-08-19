# ODPOWIEDZ-084 — okno scaleniowe F1: część wykonana, blok przepisania historii ESKALOWANY

**Od:** sesja KOD-F1 · **Do:** architekt · **Data:** 19.08.2026
**W odpowiedzi na:** `ZLECENIE-084` (wariant C — domykamy F1)
**SHA bieżące:** `0807fcf` (nad `b60c53a`, kopia bezpieczeństwa `kopia-przed-merge-19-08`)

> **To NIE jest meldunek zamknięcia okna.** To meldunek po §1 i operacjach
> bezpiecznych, z **eskalacją PRZED ROBOTĄ** jedynej operacji, której zakres
> okazał się większy niż zakładała lista scaleniowa — dokładnie tak, jak
> wielokrotnie prosiłeś. Reszta czeka na Twoje rozstrzygnięcie o force-push.

---

## 1. Warunki wejścia W1–W4

| # | warunek | wynik |
|---|---|---|
| **W1** | runda z zerem znalezisk | **ZASTĄPIONE decyzją właściciela z 19.08** (wariant C, `ZLECENIE-084` §2). Zapisane wprost w `D-2026-08-19-04` jako świadome nadpisanie reguły zbieżności, nie pominięcie. |
| **W2** | cisza w drzewie | **SPEŁNIONE.** `git status` poza `docs/` PUSTY; niezacommitowane tylko pliki kanału i `docs/specyfikacja/` (sesja SPEC-UMOWA, nieśledzone — nie blokują `filter-branch`). Żadnej niezacommitowanej pracy w kodzie. |
| **W3** | kopia bezpieczeństwa | **SPEŁNIONE (O-1).** `kopia-przed-merge-19-08` = `b60c53a` = stan wejściowy. |
| **W4** | bramka zielona od zera | **SPEŁNIONE.** `BRAMKA OK — 22/22`, 320/2261 RÓWNO, `no leaks`, kod 0 (po commicie `0807fcf`). |

---

## 2. Wykonane

**§1 — zapis granicy (najwyższa waga zlecenia):**
- `docs/GRANICA-R13-1.md` — osiem elementów z Twojego §1, bez łagodzenia:
  wektor dosłownie, skutek, twierdzenie SPRAWDZALNE o niedosięgalności z zewnątrz
  (z tym, co trzeba zmierzyć, żeby je obalić), dlaczego nie naprawiamy dalej,
  tabela pokryte/niepokryte, druga linia obrony, termin (etap B — kontrola
  skutku), warunek znoszący.
- `D-2026-08-19-04` w `docs/DECYZJE.md`.

**O-1 — kopia:** `kopia-przed-merge-19-08` wskazuje `b60c53a`.

Commit `0807fcf`, bramka po nim zielona (reguła: bramka po każdym commicie).

### Rozjazd numeracji — zgłaszam, nie ukrywam

`ZLECENIE-084` §1 prosiło o `D-2026-08-19-03`. Ten numer jest **już zajęty** —
wpisem o naprawie R12-1 (commit `b60c53a`, z tego samego dnia). Nie duplikuję
numeru; granica dostała `-04`. Powód rozjazdu: pisałeś zlecenie, nie wiedząc,
że numer 03 zużył się w poprzednim cyklu tego samego dnia.

---

## 3. ⛔ ESKALACJA PRZED ROBOTĄ — O-2/O-2b mają WIĘKSZY zakres niż zakładała lista

Lista scaleniowa (12.08) zakładała przepisanie `origin/faza-1-retencja..HEAD`,
bo wtedy zamrożony SHA **nie był wypchnięty**. **Zmierzyłem stan na dziś i jest
inny:**

```
base64 hello-world (D-4):  4 commity — 69c9e38, 31727fb, f24dfec, 4ad5728
GOCSPX pełna wartość (D-5): 4 commity — 527f1b7, 11da17e, 661e8a6, f8f64c0
origin/faza-1-retencja = b60c53a  (WSZYSTKIE osiem commitów są w jego historii)
najstarszy commit z sekretem: 69c9e38
```

Konsekwencje, których lista nie przewidywała:

1. **Zakres przepisania to NIE `origin..HEAD`** — sekrety są GŁĘBOKO w historii,
   od `69c9e38`. Przepisanie obejmie ~całą historię gałęzi.
2. **Origin JUŻ zawiera sekrety** (`b60c53a` wypchnięty). Po przepisaniu lokalna
   i zdalna gałąź rozjadą się od `69c9e38` — scalenie wymaga **force-push
   przepisanej, wypchniętej gałęzi**. To operacja nieodwracalna i outward-facing.
3. **`git filter-repo` niedostępny** w tym środowisku; zostaje `filter-branch`
   (sam git ostrzega: „glut of gotchas generating mangled history").

**Dlaczego zgłaszam, zamiast wykonać:** force-push przepisujący wypchniętą
historię to dokładnie ta klasa operacji, którą wielokrotnie prosiłeś zgłaszać
przed robotą, i której sam wcześniej odmawiałem bez wyraźnej zgody (`ZLECENIE-072`,
`ZLECENIE-076` — potwierdziłeś to jako właściwe). Autoryzacja z `ZLECENIE-084`
pokrywa przepisanie w zakresie z listy (`origin..HEAD`, bez ruszania wypchniętego),
nie force-push całej wypchniętej historii.

**Do rozstrzygnięcia (Twojego + właściciela):**

- **(a)** Czy force-push przepisanej `faza-1-retencja` jest zatwierdzony?
  Jeśli tak — czy wykonuję go ja, czy Ty w ramach O-9?
- **(b)** Czy wobec większego zakresu wolisz **wariant B** (zostaw wąski wyjątek
  gitleaks, z kontrolą pilnującą jego wąskości)? Lista sama nazywa B „decyzją,
  nie zaniechaniem": wartości są zmyślone, wyjątki wąskie (jedna reguła + jedna
  wartość + pełne SHA, `condition=AND`). To eliminuje force-push wypchniętej
  historii kosztem czterech linii konfiguracji do zniesienia w O-1 przyszłego etapu.
- **(c)** Jeśli mimo to wariant C: potwierdź `filter-branch` (bo `filter-repo`
  brak) i przyjmij, że mapa SHA obejmie ~całą historię gałęzi, nie kilka commitów.

**Moja rekomendacja: (b) — wariant B z kontrolą wąskości.** Powód: zakres C
urósł z „kilku commitów nad origin" do „przepisania wypchniętej historii +
force-push", a zysk (czystość konfiguracji skanera) jest ten sam. Wartości są
udowodnienie zmyślone; ryzyko wąskiego wyjątku jest niższe niż ryzyko force-push
wypchniętej gałęzi w oknie, w którym inne strumienie mają na niej pracę.
Jeśli jednak odtwarzalność-bez-wyjątków przeważa — C jest wykonalny, tylko
większym nakładem i z force-push do rozstrzygnięcia.

---

## 4. Operacje ZALEŻNE od rozstrzygnięcia §3 — czekają, nie pominięte

| operacja | zależność |
|---|---|
| **O-3** (usunięcie wpisów `.gitleaks.toml`) | zależy od O-2: bez przepisania sekret zostaje w historii, więc skan zapali bez wyjątku. Przy wariancie B — wpisy ZOSTAJĄ (to jest wariant B). |
| **O-4** (sprostowanie odwołań do SHA) | zależy od MAPY z O-2. Bez przepisania SHA się nie zmieniają — sprostowań nie ma. |
| **O-5** (konsolidacja wpisów D) | cytuje SHA rund; przy wariancie C czekałaby na mapę (O-4), żeby nie prostować dwa razy. Część bez cytatów SHA mogę zrobić wcześniej. |
| **O-6c** (kontrola kształtu w `docs/`) | lista znanych cytatów „kurczy się po O-2b" — jej ostateczny kształt zależy od tego, co zostanie w `docs/` po przepisaniu. Przy wariancie B — buduję z obecną listą. |

---

## 5. Operacje NIEZALEŻNE — stan i uwagi

**O-6 (automatyzacja podłóg):** obecny mechanizm (`podlogi.sh`, ręczne twarde
podłogi równe zmierzonemu) **już jest zapadką** — usunięcie testu spuszcza liczbę
poniżej podłogi i bramka zapala (kontrola negatywna spełniona konstrukcyjnie).
Lista sama ostrzega: „nie ustawiać podłóg »na ile akurat jest« — automat zamienia
zapadkę w licznik". **Pytanie o zakres O-6:** czy chcesz skrypt aktualizujący
podłogi (z ryzykiem licznika, wymagający własnej kontroli negatywnej), czy
uznajesz obecną zapadkę za wystarczającą? Domyślnie **nie ruszam działającej
zapadki** bez potrzeby.

**O-7 (`.zakres-sesji` wersjonowane):** dotyka WSPÓŁDZIELONEGO strażnika
(`skrypty/git-hooks/pre-commit`) i wymaga, by KAŻDY strumień (TESTY, SPEC-UMOWA)
miał śledzoną deklarację — czyli koordynacji, której z mojego drzewa nie
wykonam. Projekt gotów do opisania (śledzone deklaracje per strumień zamiast
jednego ignorowanego pliku; strażnik wybiera wg tożsamości strumienia); wdrożenie
i potwierdzenia od innych strumieni to `ZLECENIE-057`-podobna operacja architekta.
**Zgłaszam jako pozycję wymagającą koordynacji, nie wykonuję jednostronnie na
wspólnym strażniku.**

---

## 6. Stan długów

| dług | stan | uwaga |
|---|---|---|
| **D-3** | `TwierdzeniaKomentarzyTest` poza bramką | zostaje z terminem do etapu B (`ZLECENIE-084` §3) |
| **D-4** | wyjątek gitleaks na przynętę | **znika w O-2/O-3 — LUB zostaje przy wariancie B.** Do rozstrzygnięcia §3 |
| **D-5** | wyjątek gitleaks na cytat sekretu | **ten sam termin co D-4** — usunięcie tylko jednego jest znaleziskiem. Do rozstrzygnięcia §3 |
| **O-6c** | kontrola kształtu w `docs/` | przyjęta, czeka na §3 |

---

## 7. Czego NIE zrobiłem — jawnie

- **Przepisania historii (O-2/O-2b)** — eskalowane w §3 przed robotą; zakres
  większy niż lista, force-push wypchniętej gałęzi do rozstrzygnięcia.
- **O-3/O-4** — zależne od O-2.
- **O-5** — czeka na mapę SHA (wariant C) albo rusza bez cytatów (wariant B).
- **O-6** — nie ruszam działającej zapadki bez potwierdzenia zakresu.
- **O-6c** — czeka na ostateczny kształt `docs/` po §3.
- **O-7** — wymaga koordynacji strumieni; nie wykonuję jednostronnie na wspólnym
  strażniku.
- **O-8** — bramka finalna dopiero po komplecie operacji.
- **O-9 (merge do main)** — Twój, nie mój (`ZLECENIE-084` §2).

Kopia `kopia-przed-merge-19-08` = `b60c53a`; cała lista wycofywalna
`git reset --hard kopia-przed-merge-19-08` do czasu O-8. Czekam na Twoje
rozstrzygnięcie §3 (wariant B czy C + force-push), potem dokańczam blok zależny.
