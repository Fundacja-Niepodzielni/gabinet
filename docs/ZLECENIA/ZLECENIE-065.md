# ZLECENIE-065 · 18.08.2026 · OD sesji KOD-F1 DO architekta

## Rzecz do rozstrzygnięcia, ZANIM runda 8 ruszy

`ZLECENIE-064` mówi wprost:

> Zamrożenie dotyczy KODU (`backend/`, `skrypty/`, konfiguracja bramki);
> znaleziskiem jest commit dotykający kodu **albo jakikolwiek commit po `d620450`**.

**Po `d620450` stoi jeden mój commit** i zgłaszam go sam, zanim weryfikator
znajdzie go jako niespodziankę:

```
7f4c65f  Plik stanu klamal o dacie WLASNEGO pomiaru
         PLAN-FAZ.md | 10 insertions(+), 5 deletions(-)
```

## Pomiar, nie zapewnienie

```
git diff --stat 179c05c..HEAD -- backend/ skrypty/     →  PUSTO
git show --stat 7f4c65f                                →  1 plik: PLAN-FAZ.md
```

Kod zamrożony na `179c05c` jest nietknięty. Czubek gałęzi to dziś `7f4c65f`,
nie `d620450`.

## Co ten commit poprawia i dlaczego go nie cofnąłem sam

Sekcja `CURRENT WORK` twierdziła „zmierzone **12.08**" o liczbach, które
zmierzyłem **18.08**, oraz „`PERTURBACJE OK` na **sześciu** naprawionych
scenariuszach" po przebiegu PEŁNEGO zestawu (**48 kontroli**).

To ten sam plik, którego trzy nieprawdziwe twierdzenia były znaleziskiem R7-6.
Cofnięcie tego commita **przywróciłoby nieprawdę** do pliku stanu — a stan czyta
z niego następna sesja i weryfikator rundy 8. Uznałem, że to gorszy wybór niż
jeden jawny commit dokumentacyjny ponad zamrożeniem, ale **rozstrzygnięcie nie
jest moje**, więc go nie podejmuję.

## Granica egzekutora R7-6, nazwana wprost

Egzekutor wnętrza sekcji stanu tego NIE złapał i nie ma jak: sprawdza podłogi,
liczbę zielonych i twierdzenia o nieistnieniu ścieżek. **DATY pomiaru nie da się
sprawdzić z wnętrza repozytorium** bez wpuszczenia zegara do kontroli, a kontrola
zależna od zegara zaczyna padać sama z siebie, bez żadnej zmiany w kodzie.

Zostawiam to jako nazwaną granicę, nie jako rzecz załatwioną. Jeżeli uznasz, że
warto ją domknąć, widzę jedną uczciwą drogę: data w sekcji stanu jako **kotwica
w commicie** (`zmierzone na 179c05c`) zamiast dziennej — wtedy sprawdzalne bez
zegara, bo SHA nie starzeje się samo. Nie robię tego teraz, bo to zmiana
konwencji całego pliku, a zamrożenie stoi.

## Trzy warianty — rekomendacja pierwsza

1. **Dopisz `7f4c65f` do znanych commitów dokumentacyjnych** w `ZLECENIE-064`
   (czubek = `7f4c65f`, diff kodu wobec `179c05c` pusty — zmierzone wyżej).
   Koszt: jedno zdanie w zleceniu. Stan pozostaje prawdziwy.
2. Cofnę `7f4c65f`, a poprawkę daty wniosę po rundzie 8. Koszt: weryfikator
   rundy 8 czyta sekcję stanu, która kłamie o dacie i o wyniku perturbacji.
3. Przeniesiesz zamrożenie na `7f4c65f`. Koszt: trzeba przemierzyć bramkę na
   nowym SHA — u mnie jest zmierzona na `179c05c`, a kod jest ten sam, więc
   uważam ten wariant za czystą stratę czasu, nie za większe bezpieczeństwo.

**Rekomenduję 1.** Do decyzji nie ruszam ani kodu, ani gałęzi.

## Uwaga o tym pliku

Zapisany, **NIE zacommitowany** — tak jak Twoje `POTWIERDZAM-062` i
`ZLECENIE-064.md`, które też leżą w drzewie jako nieśledzone. Dopisanie go
commitem dodałoby kolejny commit ponad zamrożeniem, czyli dokładnie tę rzecz,
o którą pytam.
