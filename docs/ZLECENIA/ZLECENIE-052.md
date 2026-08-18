# ZLECENIE-052 · 12.08.2026 · OD sesji TESTY DO architekta

**Meldunek: grupy H, C, D, F wykonane.** Numer przydzielony w `ODPOWIEDZ-047` §5.
S-3: pozycję zamyka ten plik.

**Produkt na gałęzi `testy-plan-f2`** (drzewo `D:\tmp\gabinet-testy-plan-f2`, wypchnięte,
`ddbc05a`):

```
git -C d:/KOD/Niepodzielni/gabinet show testy-plan-f2:docs/testy/SZKIELETY-F2.md
```

---

## 1 · Zrobione

**Szkieletów: 30 → 62. Komplet 62/62 w pełnym formacie**, sprawdzony maszynowo
(`ARRANGE · ACT · ASSERT · NEG · PERT · OBS`, każde dokładnie raz).

| runda | grupy | ile |
|---|---|---|
| pierwsza (`ZLECENIE-047`) | A 10 · B 5 · E 4 · G 5 · I 6 | 30 |
| **ta** | **H 7 · C 5 · D 9 · F 10 · `SZK-J-02`** | **32** |

Kolejność z `ODPOWIEDZ-047` §5 (H → C/D → F) wykonana w całości.
**`SZK-J-02` dopisany na Twój wniosek** — twarde liczby (tożsamość pacjenta, licznik
limitu, zero kont w chwili umówienia) są napisane; **część zgodowa wstrzymana na `Q-16`**
i **nie wchodzi do suity** do rozstrzygnięcia. Nie zgaduję między `0` a `2`.

**Trzy przygotowania dodatkowe** — bez nich przypadków **nie da się zbudować**:

- **`fixtureH` z rytmem pon–nd.** Doby przestawienia zegarów wypadają w **niedzielę**,
  a rytm bazowy `S1` obejmuje pon–pt. Na nim `H-02`…`H-05` mierzyłyby **pustkę**,
  a zera wyglądałyby jak poprawny wynik.
- **`fixtureF` z zadaną HISTORIĄ, nie z zadanym licznikiem.** Licznik ma się wyliczyć;
  wpisany wprost mierzyłby sam siebie (kształt `C1`(c) — stan własnej produkcji).
- **`fixtureNY` bez cyklicznego rytmu niskopłatnego** — inaczej licznik tygodniowy
  przestaje być jednoznaczny, co złapałem dopiero przy `SZK-F-06` (niżej).

---

## 2 · Dwie kolejne moje pomyłki w planie — sprostowane

Obie wyszły przy **wyprowadzaniu** szkieletu z przypadku. To już czwarta i piąta na 75
przypadków; wszystkie pięć znalazło się w ten sam sposób.

| gdzie | co było | co jest | dlaczego to defekt, nie literówka |
|---|---|---|---|
| `H-01` | slot `09:00` dnia **`2026-11-15`** | **`2026-11-17`** | 15 listopada 2026 to **niedziela**, a rytm bazowy obejmuje pon–pt. Przypadku **nie dałoby się zbudować** — slotu tam nie ma, a `0` wyglądałoby jak wynik |
| `F-06` `NEG` | `wystawione(W40)` = **1** | **3** | pominąłem, że **rytm jest cykliczny** i sam z siebie daje 2 terminy niskopłatne w **każdym** tygodniu. Liczba `1` przechodzi tylko przy implementacji, która gubi rytm |

**Wniosek, który zapisuję u siebie:** przekład przypadku na szkielet jest **osobnym
pomiarem planu**. Pięć pomyłek, wszystkie w liczbach, które czytały się sensownie —
i **żadnej** nie znalazłem przez ponowne czytanie planu.

---

## 3 · ⚠ Dług, który wypisuję zamiast przemilczeć: 11 kotwic

Grupy `C`, `D`, `F` **używają jedenastu parametrów, które nie mają jeszcze kotwicy** z §2:

```
min_wyprzedzenie_h (2) · horyzont_pacjenta_dni (30) · horyzont_wystawiania_dni (7) ·
blokada_koszyka_min (10) · blokada_wstepna_min (10) · waznosc_linku_platnosci_h (48) ·
okno_po_otwarciu_linku_min (10) · margines_przed_wizyta_h (2) ·
limit_rownoczesnych_blokad (2) · limit_niskoplatnych_wizyt (10) ·
limit_niskoplatnych_na_tydzien (4)
```

**Szkielety są poprawne** — mają literały w `ASSERT`, zgodnie z brzmieniem, które
przyjąłeś w `ODPOWIEDZ-047` §2. Brakuje **trzeciego elementu**: kotwicy, która nazwie
**przyczynę** czerwieni, gdy parametr rozjedzie się ze specyfikacją.

**Zapisuję to jako dług, a nie jako decyzję**, bo różnica jest ta sama co przy „30
scenariuszy perturbacji" (`N-12`): mając sześć kotwic mógłbym napisać „kotwice są"
i byłaby to prawda, która **nie jest miarą pokrycia** — sześć z siedemnastu parametrów.
Lista jest wypisana co do jednej pozycji w `SZKIELETY-F2.md` §8; dopisuję je w etapie B
razem z pierwszym testem każdej grupy, żeby kotwica nie powstała bez przypadku,
który jej używa.

---

## 4 · Integralność po incydencie z `ZLECENIE-051` §3

SPEC-UMOWA odnotowała, że **nadpisała `SZKIELETY-F2.md` w moim drzewie roboczym** i cofnęła
to `git restore`. **Sprawdziłem, zanim cokolwiek dopisałem** — nie na podstawie jej
meldunku, tylko pomiarem:

```
git hash-object docs/testy/SZKIELETY-F2.md   == 3b560269…
git rev-parse HEAD:docs/testy/SZKIELETY-F2.md == 3b560269…
```

**Plik bajt w bajt zgodny z `bf6a176`.** Zero strat, zero moich commitów do cofania.
Odnotowuję z uznaniem, że zgłosiła to sama i wprost — bez tego meldunku sprawdziłbym
integralność dopiero, gdyby coś się nie zgadzało, czyli **po** zbudowaniu 32 szkieletów
na cudzym pliku.

**Popieram jej wniosek orkiestracyjny z `ZLECENIE-051` §4:** wspólne pliki-rejestry
w drzewie głównym (`DECYZJE.md`) nie mają właściciela zapisu, a oba dzisiejsze zderzenia
mają jeden kształt — **sesja działa na stanie odczytanym przed cudzym równoległym
zapisem**. Twoje zasady z `ODPOWIEDZ-045` §4 domknęły kanał i gałęzie; rejestry zostały
poza nimi. **To Twoja decyzja, nie moja** — sygnalizuję, że mnie też dotyczyła.

---

## 5 · Co dalej

**Biorę bez pytania (S-2):**

1. **`J-03` i `J-04`** — rezygnacja w oknie bezpłatnym w dobie 25- i 23-godzinnej.
   Gotowe do napisania, nic ich nie blokuje, a są to przypadki, które specyfikacja nazywa
   ryzykiem *„wychodzi dwa razy w roku, zawsze na produkcji"*.
2. **`J-05`…`J-08`** — z rekomendacjami `Q-17`, `Q-18` (nieblokujące, przyjęte w planie).
3. **Grupa `K`** — dopiero po domknięciu zbioru szkieletów; mierzy **zbiór**, więc dziś
   rosłaby pod własnymi asercjami.

**Czekam:**

- **`Q-16`** (spotkanie G7) — część zgodowa `SZK-J-02`.
- **Kontrakt operacji API** od KOD-SILNIK. Przyjąłem Twoje ustalenie z `ODPOWIEDZ-047` §5,
  że powstaje **zleceniem trójstronnym** (KOD-SILNIK proponuje → ja kwestionuję →
  Ty rozstrzygasz). Do tego czasu **grupa `L` nie ma szkieletów** — mierzy czas i liczbę
  zapytań, czyli własności implementacji; szkielet bez kontraktu byłby zgadywaniem
  instrumentacji, a nie testem.

**Sprzeczne polecenia w tej rundzie:** brak.

---

**Meldunek kolejny:** proszę o numer przy następnej odpowiedzi.
