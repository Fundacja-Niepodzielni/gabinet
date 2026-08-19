# Decyzja właściciela — ósme piętro kontroli tożsamości (19.08.2026)

**Autor:** architekt · **Stan:** czeka na rozstrzygnięcie · **Sesje:** cisza

## 1. Czego dotyczy — bez żargonu

W gabinecie obowiązuje zasada: **tożsamość zalogowanej osoby może pochodzić wyłącznie
ze zweryfikowanego logowania**. Sama zasada jest w kodzie wymuszona konstrukcyjnie
(obiekt tożsamości powstaje tylko po sprawdzeniu podpisu — rundy 11–12 to potwierdziły).

Osobno mamy **kontrolę pomocniczą**, która pilnuje, żeby nikt nie odtworzył takiego
obiektu „na skróty", z pominięciem konstrukcji. Ta kontrola przez trzy rundy z rzędu
okazywała się omijalna: najpierw pytała o trzy napisy w tekście, potem o nazwę
odczytaną z gramatyki języka, a teraz — o nazwę sklejoną bezpośrednio. Runda 13 pokazała,
że nazwa sklejona **przez zmienne pomocnicze** znów ją omija.

## 2. Rozstrzygnięcie klasy — to nie jest błąd wykonania

Zdanie weryfikatora rundy 13, przyjęte przeze mnie jako rozstrzygające:

> „Kolejne rozszerzanie skanera (`$a.$b`, `.=`, `implode`, `strrev`, stała klasowa…)
> to ta sama denylista, o piętro wyżej — **brzeg będzie zawsze**."

To jest własność analizy kodu w ogóle, nie wada naszej implementacji: **żadna kontrola
czytająca kod nie rozpozna wszystkich sposobów zapisania tej samej czynności.**
Dziewiąte piętro istnieje z definicji.

## 3. Przed czym ta kontrola broni — kluczowe dla oceny ryzyka

**Nie broni przed atakiem z zewnątrz.** Żadna z ośmiu dziur nie jest osiągalna dla
użytkownika przeglądarki, pacjenta ani pracownika — wszystkie wymagają **dopisania kodu
do repozytorium**. Kontrola broni przed **nieuwagą osoby (lub sesji) piszącej kod**.

Wobec tego celu stan jest inny, niż sugeruje liczba rund:

| forma zapisu | czy kontrola łapie | czy powstaje przez nieuwagę |
|---|---|---|
| zwykłe wywołanie, nazwa kwalifikowana, dowolna klasa refleksji | **tak** | tak — to normalny sposób pisania |
| nazwa w zmiennej, sklejenie bezpośrednie | **tak** | rzadko |
| **sklejenie przez zmienne pomocnicze** (`$a='unse'; $b='rialize'`) | nie | **nie — nikt tak nie pisze przypadkiem** |

Innymi słowy: **kontrola pokrywa wszystkie formy naturalne. Omijają ją wyłącznie zapisy,
które trzeba napisać celowo, żeby ją ominąć.**

## 4. Warianty

**A. Zamykamy F1 z jawnie opisaną granicą.**
Zapis w dokumentacji: kontrola łapie każdą naturalną formę; celowe obejście przez
konstruowanie nazwy pozostaje możliwe i jest własnością analizy statycznej, nie luką
do naprawienia. Druga linia obrony istnieje i działa: każda zmiana kodu przechodzi
niezależną rundę weryfikacyjną (13 rund, ta klasa łapana za każdym razem).
**Koszt: 0. Ryzyko: celowe obejście przez osobę mającą dostęp do repozytorium.**

**B. Jeszcze jeden cykl — kontrola na SKUTEK zamiast na kod.**
Kierunek wskazany przez weryfikatora: nie pytać „czy w kodzie jest podejrzane wywołanie",
tylko sprawdzać w działaniu, że **każda tożsamość w sesji ma odpowiadający jej dowód
weryfikacji z tego samego żądania**. Taka kontrola nie zależy od zapisu, więc nie ma
brzegu tej klasy.
**Koszt: ~pół dnia + runda 14. Ryzyko: nowa klasa kontroli może mieć własne granice
(np. wydajność, fałszywe alarmy) — trzeba je zmierzyć.**

**C. Wariant A teraz + B jako pierwsze zadanie etapu B.**
Zamykamy fundament, a kontrolę skutkową budujemy przy silniku rezerwacji, gdzie i tak
powstaje warstwa żądań. **Koszt: 0 teraz, praca przesunięta.**

## 5. Rekomendacja architekta: **C**

Powody, w kolejności wagi:
1. **Realne ryzyko (nieuwaga) jest już pokryte** — formy naturalne kontrola łapie.
   Nie pokryte jest tylko obejście celowe, a przed nim analiza kodu i tak nie obroni.
2. **Malejący zwrot**: trzy ostatnie rundy dały po jednym znalezisku, każde wymagające
   coraz bardziej wymyślnego zapisu. Czwarta runda w tym samym miejscu opóźnia budowę
   systemu, na który Fundacja czeka.
3. **Kontrola skutkowa jest tańsza przy silniku rezerwacji** niż teraz — tam powstaje
   warstwa, w której naturalnie się ją umieszcza.
4. Fundament jest **niezależnie zweryfikowany w 13 rundach**; wszystko poza tą jedną
   pozycją broni się pomiarowo.

**Wariant B pozostaje uczciwym wyborem**, jeśli wolisz mieć tę klasę domkniętą przed
scaleniem — powiedz „B", a zlecenie ruszy natychmiast.

## 6. Co zawiera zapis granicy przy wariancie A/C

Do `docs/DECYZJE.md` i do dokumentu odbioru fazy: opis wektora, dowód, że jest
niedosięgalny z zewnątrz, wskazanie drugiej linii (rundy weryfikacyjne + przegląd kodu),
termin naprawy (etap B, kontrola skutkowa) i **warunek znoszący**: gdyby powstała ścieżka
pozwalająca uruchomić kod z zewnątrz, granica przestaje obowiązywać i wraca jako blokada.
