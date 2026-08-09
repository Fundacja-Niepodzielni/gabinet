# ODPOWIEDŹ-001 — klasy i naprawy

## POMIAR KANAŁU — pierwszy prawdziwy, bo plik powstał PO uzbrojeniu

**Obserwator ZADZIAŁAŁ.** Obudził mnie sam, bez trącania przez człowieka.

| pomiar | wartość |
|---|---|
| plik powstał na dysku (`stat`) | **10:59:15.193** |
| obserwator mnie obudził | **10:59:44.120** |
| **różnica** | **28,9 s** |

**Muszę od razu sprostować własną liczbę z `ODPOWIEDZ-000`.** Podałem tam ograniczenie
„0–10 s" i opisałem je jako wartość projektową. **Zmierzone 28,9 s — czyli prawie trzy razy
więcej.** Powód jest taki, że **moja liczba dotyczyła innej wielkości niż Twoje pytanie**:
10 s to okres pętli, czyli opóźnienie **wykrycia** pliku przez `ls`. Ty pytasz o opóźnienie
**obudzenia**, w którym mieści się jeszcze dostarczenie sygnału i wznowienie mojej sesji —
i to ta druga część dominuje (~19 s z 29).

Zapisuję to jako osobną lekcję, bo jest tą samą klasą, którą tropimy w kodzie: **podałem
ograniczenie dla wielkości sąsiedniej i nazwałem je odpowiedzią na pytanie o inną.** Gdybym
napisał „0–10 s" bez słowa „projektowa", oparłbyś na tym harmonogram i pomylił się trzykrotnie.

**Praktyczny wniosek dla kanału:** przy jednym zleceniu naraz opóźnienie jest bez znaczenia.
Zacznie mieć znaczenie, jeśli będziesz liczył na reakcję szybszą niż pół minuty albo wyśle
kilka zleceń w krótkim odstępie — pętla wykryje je **razem**, w jednym przebudzeniu, i podam
je jako listę, nie po kolei.

## Rzecz, którą muszę zgłosić o samym zleceniu

**To zadanie wykonałem wcześniej — z ręcznego wklejenia, zanim kanał się potwierdził.**
`ZLECENIE-000` przewidywało taki przypadek i kazało dokończyć oraz o tym napisać.
Dokument `docs/noc-2026-08-08/KLASY-I-NAPRAWY.md` istniał i był scommitowany, zanim
`ZLECENIE-001` powstało.

**Nie odhaczyłem go jednak jako gotowego.** Po przeczytaniu pliku przeszedłem po Twoich
wymaganiach punkt po punkcie i **znalazłem dwa braki**, oba naprawione przed tą odpowiedzią:

1. **Pola WAGA i OSIĄGALNOŚĆ miałem tylko przy instancjach**, w tabeli — a Twój wymóg mówi
   „każde znalezisko". Klasy ich nie miały. Dopisane do **wszystkich siedmiu**, razem
   z **WARUNKIEM UTRZYMUJĄCYM** tam, gdzie osiągalność jest dziś zerowa dzięki czemuś
   odwracalnemu (jest ich teraz 10 w całym dokumencie).
2. **Brakowało dosłownej frazy „instancja, naprawa punktowa"** — miałem nagłówek
   „Instancje — naprawa punktowa, NIE klasa", co znaczy to samo, ale nie tymi słowami.
   Poprawione, bo o dosłowność prosiłeś wprost.

Najmocniejszy z dopisanych warunków utrzymujących jest przy KLASIE 7 i wolę, żeby był
widoczny także tutaj: **przed wypchnięciem mojego przypadkowego commita do cudzego
repozytorium (N-13) uchroniła mnie wyłącznie różnica nazw gałęzi.** To nie zabezpieczenie,
tylko szczęście — przestanie działać w dniu, w którym dwa repozytoria będą miały gałąź
o tej samej nazwie.

## Wynik — jeden akapit, o który prosiłeś

**Siedem klas i dziesięć osobnych instancji.** Klasy grupowałem po **mechanizmie**, nie po
objawie, a kryterium („jedna zmiana zamyka wszystkich członków") stosowałem ostro — dlatego
sekcja instancji jest długa celowo. Są to: (1) **kontrola dowodzi własności, której jej
środowisko nie ma** → drugi pierścień suity na prawdziwych sterownikach i jako `www-data`,
plus manifest różnic; (2) **dowód oparty na nieobecności napisu** → odczyt bazowy zamiast
negacji; (3) **wynik zgodny z więcej niż jednym światem** → werdyktu bez pre-flightu i gałęzi
bazowej *nie da się wyprodukować*; (4) **zapis w ścieżce bezpieczeństwa bez rozstrzygniętego
losu** → odczyt zwraca „nie wiem", nie liczbę; (5) **kontrola jako denylista** → allowlista
z odmową przy nieznanym; (6) **twierdzenie w dokumencie bez egzekutora** → rozszerzenie
`ObietniceKomentarzyTest` ze znaczników na twierdzenia plus jedno źródło stanu;
(7) **reguła istniejąca wyłącznie jako zdanie** → `pre-commit` sprawdzający znacznik przebiegu
i korzeń repozytorium. Każda ma perturbację falsyfikującą, bo bez niej naprawa jest deklaracją.
**Największa jest klasa 3** — dziewięciu członków — i jest największa nie przypadkiem: to ta
sama wada, którą złapałem na sobie pięć razy w ciągu jednej doby. **O występowanie w innych
repozytoriach podejrzewam wszystkie siedem, najmocniej trzy:** klasa 4 jest najgorszą wspólną
klasą ekosystemu (hubowe **Z-A-1** to ten sam mechanizm po drugiej stronie — u nich zapis
*rzuca* i zabija unieważnienie sesji, u mnie *ostrzega* i zabija diagnostykę; ich **L-3** to
wariant trzeci, helpdeskowe **W-18** czwarty), klasa 3 jest najpowszechniejsza i nazwana
niezależnie u wszystkich czterech, a klasa 1 ma u helpdesku odpowiednik w **D-2026-08-08-27**,
którego zdanie sam obaliłem przy weryfikacji krzyżowej. Osobno: **R6A-11** (retencja
niepodpięta) jest u mnie *instancją*, ale w skali ekosystemu **klasą** — to dosłownie
helpdeskowe **W-17**, z tą różnicą, że u nich mechanizm istnieje i nie jest sprawdzany,
a u mnie nie istnieje wcale; widać to wyłącznie z zestawienia dwóch nocy.

## Zakazy i sprzeczności

Kodu nie tknąłem. Nowych znalezisk nie dopisywałem — dokument wyłącznie **grupuje** istniejące
identyfikatory. `main` nietknięty (`a5a4d8b` lokalnie i zdalnie), zero merge, zero deploy,
nic poza fundację, w cudzych repozytoriach zero zapisów. **Sprzeczności ze zleceniem: brak.**
