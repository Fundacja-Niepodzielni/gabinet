# PROJEKT NAPRAWY STRAŻNIKA COMMITA — S-01 i S-02

**Autor:** sesja KOD-F1 · **Data:** 12.08.2026 · **Status: PROJEKT DO ZATWIERDZENIA**
**Podstawa:** `ZLECENIE-059` §2 (pomiar sesji TESTY), `ODPOWIEDZ-059` §2
**Wykonanie:** okno scaleniowe, operacja **O-6b** · **Meldunek:** `ZLECENIE-060`

> **Plik NIE JEST ZACOMMITOWANY** — kod i drzewo stoją na czas rundy 7
> (`ODPOWIEDZ-058` pkt 4: zamrożenie dotyczy kodu i konfiguracji bramki).
> To projekt, nie zmiana.

---

## 0 · Przyjmuję znalezisko w całości. Strażnik nie chronił tych drzew, dla których powstał

TESTY zmierzyły, że mój strażnik **nie zadziałał w worktree**. Sprawdziłem obie
usterki niezależnie, na repozytorium jednorazowym poza projektem — **diagnoza broni
się w całości**, a jedna rzecz jest gorsza, niż napisałem w `ZLECENIE-054`.

Napisałem tam: *„core.hooksPath jest wspólny dla wszystkich worktree, więc pozostałe
trzy strumienie trafią na odmowę przy pierwszym commicie"*. **To zdanie było
nieprawdziwe.** Wspólna jest **wartość konfiguracji**, nie **skuteczność mechanizmu** —
ścieżka względna rozwija się w każdym drzewie osobno i w większości z nich celuje
w pustkę. Ostrzegłem strumienie przed odmową, która nigdy nie miała nadejść.

To jest ta sama klasa, którą ta sesja zamykała u siebie trzy razy: **pisemne
zapewnienie o działaniu mechanizmu, którego nikt nie zmierzył**. Tym razem
autorem zapewnienia byłem ja, a zmierzył je ktoś inny.

### Pomiary własne (repozytorium jednorazowe, poza projektem, posprzątane)

**M-1 · Hook w katalogu WSPÓLNYM odpala się w OBU drzewach:**
```
git rev-parse --git-path hooks   (drzewo główne) → .git/hooks
git rev-parse --git-path hooks   (worktree)      → …/glowne/.git/hooks
commit w drzewie głównym → HOOK ODPALIL SIE
commit w worktree        → HOOK ODPALIL SIE
```

**M-2 · `hooksPath` w pustkę milczy — potwierdzenie S-01:**
```
core.hooksPath = skrypty/git-hooks   (katalog NIE ISTNIEJE)
commit w drzewie głównym → kod 0, ZERO ostrzeżeń
commit w worktree        → kod 0, ZERO ostrzeżeń
```

**M-3 · Tożsamość — potwierdzenie S-02 i rekomendacji TESTY:**
```
w worktree TESTY:
  git rev-parse --path-format=absolute --git-common-dir → D:/KOD/…/gabinet/.git
  basename(dirname(powyższe))                           → gabinet        ✔
  basename(git rev-parse --show-toplevel)               → gabinet-testy-plan-f2   ✘ (dziś)
```

`git 2.53.0` — `--path-format=absolute` dostępne (wymaga ≥ 2.31).

---

## 1 · PATCH — dokładna treść zmian

### P-1 · Strażnik przenosi się do KATALOGU WSPÓLNEGO, `hooksPath` znika

**Dlaczego nie „ścieżka bezwzględna w `core.hooksPath`"** — jeden z trzech wariantów
TESTY. Działałaby, ale wpisuje **ścieżkę tej maszyny** do konfiguracji repozytorium:
psuje się przy przeniesieniu katalogu, przy klonie weryfikatora i u każdego, kto ma
repo gdzie indziej. Katalog wspólny rozwiązuje S-01 **bez** wiązania konfiguracji
z maszyną — potwierdzone M-1.

**Nowy plik `skrypty/zainstaluj-straznika.sh`:**

```bash
#!/usr/bin/env bash
# Instaluje strażnika w KATALOGU WSPÓLNYM repozytorium.
#
# S-01: `core.hooksPath` był ścieżką WZGLĘDNĄ i rozwijał się w każdym drzewie
# roboczym osobno. Plik strażnika żyje w plikach śledzonych na `faza-1-retencja`,
# więc w drzewie odbitym od wcześniejszego commita tej ścieżki NIE MA — a git
# przy `hooksPath` wskazującym pustkę NIE OSTRZEGA, tylko nic nie uruchamia
# (zmierzone: M-2). Nieobecność mechanizmu i jego obecność dawały ten sam objaw.
set -euo pipefail

WSPOLNY="$(git rev-parse --path-format=absolute --git-common-dir)"
ZRODLO="$(git rev-parse --show-toplevel)/skrypty/git-hooks/pre-commit"
CEL="$WSPOLNY/hooks/pre-commit"

[ -f "$ZRODLO" ] || { echo "ODMOWA: brak źródła strażnika: $ZRODLO" >&2; exit 1; }

mkdir -p "$WSPOLNY/hooks"
cp "$ZRODLO" "$CEL"
chmod +x "$CEL"

# `hooksPath` MUSI zniknąć — dopóki jest ustawiony, git IGNORUJE katalog wspólny.
git config --unset-all core.hooksPath 2>/dev/null || true

# ODCZYT ZWROTNY. „Skrypt się wykonał" ≠ „plik ma treść" — reguła tego repozytorium,
# złamana u nas dwa razy. Porównujemy sumy, nie fakt wykonania `cp`.
if [ "$(sha256sum < "$ZRODLO")" != "$(sha256sum < "$CEL")" ]; then
	echo "ODMOWA: zainstalowany strażnik RÓŻNI SIĘ od źródła." >&2
	exit 1
fi

[ -x "$CEL" ] || { echo "ODMOWA: strażnik nie jest wykonywalny." >&2; exit 1; }

echo "Strażnik zainstalowany: $CEL"
echo "Aktywny we WSZYSTKICH drzewach roboczych tego repozytorium (zmierzone: M-1)."
```

### P-2 · Tożsamość z `--git-common-dir`, nie z `basename` drzewa (S-02)

**W `skrypty/git-hooks/pre-commit`, sekcja (b).**

BYŁO:
```bash
REPO_BIEZACE="$(basename "$KORZEN")"
```

MA BYĆ:
```bash
# S-02: `basename` KORZENIA to w worktree nazwa DRZEWA, nie repozytorium —
# `gabinet-testy-plan-f2` zamiast `gabinet`. Strażnik odmawiałby tam każdego
# commita, a obejście przez wpisanie nazwy worktree ROZBRAJA warunek (b):
# „czy jestem w cudzym repozytorium" przestaje odróżniać repozytoria, gdy każda
# sesja deklaruje własną nazwę katalogu.
#
# `--git-common-dir` wskazuje repozytorium także z worktree (zmierzone: M-3),
# więc tożsamość jest jedna dla wszystkich drzew.
WSPOLNY="$(git rev-parse --path-format=absolute --git-common-dir 2>/dev/null || printf '')"

if [ -z "$WSPOLNY" ]; then
	odmowa "Nie umiem ustalić repozytorium (`--git-common-dir` puste)." \
		"Bez tożsamości warunek (b) nie ma czego porównać — a cicha zgoda przy" \
		"nieznanej tożsamości byłaby fail-open dokładnie tam, gdzie N-13 zabolało."
fi

REPO_BIEZACE="$(basename "$(dirname "$WSPOLNY")")"
```

**Skutek uboczny do cofnięcia:** TESTY wpisały w swoim `.zakres-sesji`
`gabinet-testy-plan-f2` **jako jawne obejście usterki, z komentarzem**. Po naprawie
ta linia ma wrócić do `gabinet` — jest to część O-6b, nie osobna pozycja.

### P-3 · `.gitignore` — bez zmian

`.zakres-sesji` i `.przebieg-pomiarowy` zostają per drzewo robocze i **tak ma być**:
zakres jest własnością SESJI, nie repozytorium. Znacznik przebiegu też — dwa
równoległe przebiegi w dwóch drzewach to dwa różne pomiary.

---

## 2 · KONTROLA NEGATYWNA W KAŻDYM AKTYWNYM WORKTREE

**Nowy plik `skrypty/straznik-w-worktree.sh`, wołany jako krok bramki.**

### 2.1 · Świadome odstępstwo od dosłownego brzmienia — proszę o rozstrzygnięcie

Dosłowna „kontrola negatywna wykonywana w każdym aktywnym worktree" oznacza
**podłożenie naruszenia w cudzym drzewie roboczym** — utworzenie tam `.zakres-sesji`
albo `.przebieg-pomiarowy`. To łamie regułę **jedna ścieżka, jeden piszący** i mogłoby
nadpisać deklarację pracującej sesji.

**Proponuję rozbicie na trzy części, które razem dają to samo, nie pisząc u nikogo:**

| część | gdzie | co dowodzi |
| --- | --- | --- |
| **K-1** odczyt per worktree | każde aktywne drzewo, **tylko odczyty** | mechanizm jest OSIĄGALNY i nieprzesłonięty |
| **K-2** pełna kontrola negatywna | worktree **jednorazowy**, tworzony i usuwany przez kontrolę | strażnik NAPRAWDĘ odmawia w worktree |
| **K-3** potwierdzenie własne | każda sesja u siebie, raz, wpis w kanale | strażnik odmawia w KONKRETNYM drzewie tej sesji |

K-3 jest dosłownym spełnieniem wymogu, tylko wykonanym **rękami właściciela drzewa** —
tak jak O-7 dla `.zakres-sesji`. Jeżeli architekt woli wariant dosłowny wykonywany
centralnie, wykonam go — ale wtedy potrzebuję zgody każdej sesji na zapis w jej drzewie.

### 2.2 · K-1 — odczyt w każdym aktywnym worktree

```bash
#!/usr/bin/env bash
set -uo pipefail
BLEDY=0
WSPOLNY="$(git rev-parse --path-format=absolute --git-common-dir)"
HOOK="$WSPOLNY/hooks/pre-commit"
ZRODLO="$(git rev-parse --show-toplevel)/skrypty/git-hooks/pre-commit"

# Pustka to BŁĄD, nie zero: brak drzew znaczy, że parser się rozjechał.
LISTA="$(git worktree list --porcelain | awk '/^worktree /{print $2}')"
[ -n "$LISTA" ] || { echo "    ✗ nie znalazłem ŻADNEGO drzewa — parser pusty"; exit 1; }

while IFS= read -r WT; do
	[ -d "$WT" ] || { echo "    · pomijam nieistniejące: $WT"; continue; }

	# 1. czy strażnik jest z tego drzewa OSIĄGALNY
	W="$(git -C "$WT" rev-parse --path-format=absolute --git-common-dir)"
	[ "$W/hooks/pre-commit" = "$HOOK" ] || { echo "    ✗ $WT: inny katalog wspólny"; BLEDY=1; }

	# 2. czy NIE JEST PRZESŁONIĘTY — to jest cała treść S-01
	NADPISANIE="$(git -C "$WT" config --get core.hooksPath || printf '')"
	[ -z "$NADPISANIE" ] || { echo "    ✗ $WT: core.hooksPath='$NADPISANIE' PRZESŁANIA katalog wspólny"; BLEDY=1; }

	# 3. czy tożsamość jest REPOZYTORIUM, nie drzewem (S-02)
	T="$(basename "$(dirname "$W")")"
	[ "$T" = "gabinet" ] || { echo "    ✗ $WT: tożsamość '$T' zamiast 'gabinet'"; BLEDY=1; }
done <<< "$LISTA"

# 4. zainstalowany strażnik ZGADZA SIĘ ze źródłem — inaczej mierzymy cudzy plik
[ -x "$HOOK" ] || { echo "    ✗ brak wykonywalnego strażnika: $HOOK"; BLEDY=1; }
[ "$(sha256sum < "$ZRODLO")" = "$(sha256sum < "$HOOK")" ] || { echo "    ✗ zainstalowany strażnik RÓŻNI SIĘ od źródła"; BLEDY=1; }

exit "$BLEDY"
```

### 2.3 · K-2 — pełna kontrola negatywna na worktree JEDNORAZOWYM

```bash
TYMCZ="$(mktemp -d)/proba-straznika"
git worktree add -q --detach "$TYMCZ"

# Naruszenie warunku (c): plik spoza zadeklarowanego zakresu w indeksie.
printf 'gabinet\nbackend/\n' > "$TYMCZ/.zakres-sesji"
mkdir -p "$TYMCZ/docs"; printf 'cudze\n' > "$TYMCZ/docs/cudze.md"
git -C "$TYMCZ" add docs/cudze.md

WYJSCIE="$(cd "$TYMCZ" && bash "$HOOK" 2>&1)" && KOD=0 || KOD=$?

git worktree remove --force "$TYMCZ"

# Dwa sygnały, nie jeden: sam kod wyjścia nie odróżnia „odmówił" od „padł".
[ "$KOD" -ne 0 ] || { echo "    ✗ strażnik PRZEPUŚCIŁ naruszenie w worktree"; exit 1; }
printf '%s' "$WYJSCIE" | grep -q 'SPOZA ZAKRESU' \
	|| { echo "    ✗ odmowa z INNEJ przyczyny niż badana: $WYJSCIE"; exit 1; }

# KIERUNEK ODWROTNY — bez niego „odmawia" przechodzi dla strażnika odmawiającego ZAWSZE.
# (ta sama konstrukcja co kontrola pozytywna w `StraznikCommitaTest`)
```

**Uwaga wykonawcza:** `git worktree add` w drzewie, w którym trwa przebieg pomiarowy,
sam jest zmianą stanu — dlatego K-2 należy do **bramki**, a nie do zestawu perturbacji,
i biegnie **przed** krokiem perturbacyjnym.

---

## 3 · MIEJSCE W LIŚCIE SCALENIOWEJ: **O-6b**

Między **O-6** (automatyzacja podłóg) a **O-7** (weryfikacja `.zakres-sesji` per strumień).

**Dlaczego dokładnie tu:**

- **po O-6**, bo obie pozycje są zmianami kodu i dzielą jedną zieloną bramkę —
  rozdzielanie ich na dwa przebiegi kosztuje bez zysku;
- **przed O-7 i to jest warunek konieczny**, bo O-7 weryfikuje deklaracje
  `.zakres-sesji` w każdym strumieniu. **Weryfikacja deklaracji dla strażnika,
  który w tym drzewie nie działa, jest pustym rytuałem** — dokładnie tym, co
  TESTY zmierzyły: deklaracja była, mechanizmu nie było.

**Dowód ukończenia O-6b — dwustronny:**

1. `K-1` zielone dla **każdego** aktywnego drzewa (odczyty),
2. `K-2` zielone: strażnik **odmawia** w worktree jednorazowym, z **właściwej przyczyny**,
3. `StraznikCommitaTest` nadal zielony (7 kontroli, w tym pozytywna),
4. `.zakres-sesji` sesji TESTY wraca z `gabinet-testy-plan-f2` na `gabinet`.

**Warunek, bez którego O-6b nie jest zamknięte:** instalator musi być **wykonany
w drzewie głównym**, a `core.hooksPath` **usunięty** — inaczej katalog wspólny
pozostaje przesłonięty i cała naprawa jest niewidoczna. Pilnuje tego `K-1` pkt 2.

---

## 4 · CO ROBIĆ TERAZ, PRZED OKNEM SCALENIOWYM

**Naprawy nie wykonuję** — kod stoi na czas rundy. Ale jedna rzecz **nie może czekać**:

⚠ **`ZLECENIE-057` niesie zdanie nieprawdziwe** — „strażnik aktywny we wszystkich
drzewach roboczych gabinetu". Dopóki stoi bez sprostowania, każda sesja może uznać,
że jest chroniona, i przestać uważać na `git add`. **Fałszywe zapewnienie jest gorsze
niż znana dziura** — to precedens R6A-4, gdzie zdanie „lista jest ZAMKNIĘTA" uczyło
czytelnika przestać szukać.

**Proszę o sprostowanie w kanale** (jedno zdanie): strażnik działa dziś **wyłącznie
w drzewie głównym**; w worktree jest nieaktywny do wykonania O-6b.

**Ryzyko w międzyczasie jest równe stanowi sprzed strażnika** — nie ma regresji,
jest tylko brak ochrony, o którym trzeba wiedzieć.

---

## 5 · Czego ten projekt NIE rozstrzyga

- **Nie mierzyłem** zachowania przy trzecim i czwartym drzewie roboczym — dziś są dwa.
  K-1 iteruje po `git worktree list`, więc obejmie kolejne, ale **nie jest to zmierzone**.
- **Nie mierzyłem** przypadku, w którym sesja pracuje w klonie, a nie w worktree
  (weryfikator rundy 7). Tam strażnik nie działa **i to jest poprawne**: `ZLECENIE-056`
  daje mu zakaz commitowania, więc mechanizm jest zbędny.
- **Nie rozstrzygam**, czy `.zakres-sesji` powinien być plikiem śledzonym per gałąź
  zamiast lokalnym per drzewo. Dziś lokalny; zmiana byłaby osobną decyzją.
