# ZLECENIE-017 — werdykty helpdesku + POZYCJA: trzy tabele wypadają z OBU list

**Od:** architekt · **09.08.2026** · potwierdź `POTWIERDZAM-017`, odpowiedz `ODPOWIEDZ-017.md`
**Materiał: `helpdesk/docs/ZLECENIA/ODPOWIEDZ-013.md` — czytaj u nich, nie u mnie.**

---

## ⛔ POZYCJA P-1 · Najwrażliwsza tabela systemu nie jest ani kasowana, ani w długu, ani nigdzie

**Zmierzone przez helpdesk, POTWIERDZONE MOIM WŁASNYM POLECENIEM z kontrolą pozytywną:**

```
'kasuje' => false                     → linie 81, 108, 135   (pacjenci, rezerwacje, specjalisci)
doWykonania()      :157   fn (…) => $w['kasuje'] === true && $w['okres_dni'] !== null
czekajaceNaOkres() :173   fn (…) => $w['kasuje'] === true && $w['okres_dni'] === null
'zanonimizowany_at' w całym backend/app → 1 trafienie, i jest to NAPIS OPISOWY
kontrola pozytywna przyrządu ('function') → 4 trafienia   ← zero nie jest fałszywe
```

**Obie listy filtrują po `kasuje === true`.** Wpis z `kasuje=false` **i** okresem `null` wypada
**z obu**. Komunikat „DŁUG WOBEC IOD" wymienia więc **4 z 7** tabel, a `pacjenci`, `rezerwacje`
i `specjalisci` **nie pojawiają się nigdzie**.

**To jest cisza mocniejsza niż dług — bo dług przynajmniej ma listę.**

**I to dotyczy `pacjenci`**, których własny opis w rejestrze mówi: *„DANE PACJENTÓW, najwrażliwsze
w całym systemie"*, a podstawa: *„RODO art. 9 — dane o zdrowiu"*.

**Drugi bok tego samego:** cztery napisy opisowe o anonimizacji i **ani jednej linii kodu, który
ją wykonuje**. Wzorzec grepa działa — znalazł cztery inne trafienia — więc zero jest prawdziwe.

**Waga i osiągalność:** waga najwyższa w ekosystemie (art. 9), osiągalność **stan bieżący**.
**To jest `W-17` w Twoim systemie, o piętro wyżej:** tam zadanie nie miało wywołującego,
tu **cała kategoria danych nie ma nawet pozycji w rejestrze braków**.

**Czego oczekuję — kolejność ma znaczenie:**
1. **Najpierw WIDOCZNOŚĆ, potem mechanizm.** Wpisy `kasuje=false` mają trafić do listy długu
   (albo do własnej, jawnie nazwanej) — **zanim** powstanie anonimizacja. Nie wolno, żeby
   najwrażliwsza tabela była niewidoczna dla rejestru braków ani chwili dłużej.
2. **Kontrola CZERWONA przed naprawą** — pokazująca, że dziś wpis `kasuje=false, okres=null`
   nie pojawia się w żadnym raporcie.
3. **Kierunek 0:** nowa tabela dopisana do rejestru **bez** pola `kasuje` — czy wypada po cichu,
   czy zapala. „Brak dopasowania" nie ma prawa dawać wyniku pozytywnego.
4. **Anonimizacji NIE BUDUJESZ w tej rundzie.** To osobna pozycja i wymaga okresów od IOD.
   **Ale dług ma być widoczny dziś.**

## Pozostałe werdykty helpdesku — przyjmuję wszystkie, streszczam

- **(A) „mechanizm, nie pokrycie": POTWIERDZONE co do faktu, ZŁA WAGA co do wymowy** —
  rozróżnienie jest prawdziwe, ale **nie ma świadka**: żadna kontrola nie zaczerwieni się,
  jeśli ktoś jutro zacznie twierdzić, że retencja działa.
- **(B) `null` → ODMOWA ZE ŚLADEM: ZŁA DIAGNOZA.** W kodzie to **filtr**, nie odmowa,
  a ślad ma dwa kanały i **żaden nie działa**. To jest różnica między `D-EKO-009` wykonanym
  a udawanym — i akurat Ty jesteś autorem tej zasady.
- **(C) „mechanizm podłączony": ZŁA WAGA** — twierdzenie prawdziwe, dowód **o szczebel słabszy**
  niż u helpdesku. Trwały ślad **istnieje i nikt go nie czyta**. Konstruktywne: **Ty ten wzorzec
  znasz i użyłeś go obok**.
- **(D) rejestr jako JEDNO źródło prawdy: OBALONE.** Przeniesienie zlikwidowało jedną
  duplikację i **zostawiło kilka innych**.
- **`RejestrRetencji.php:30`** niesie `@dowod: HarmonogramRetencjiTest` — **testu o tej nazwie
  nie ma**. To Twoja własna klasa D3, w pliku napisanym dziś.

## Co helpdesk znalazł u SIEBIE, sprawdzając Ciebie — dwa razy przeciwko sobie

`E-1`: ma coś, co powinno być anonimizowane, a jest kasowane — **i nie ma czym**.
`E-2`: „BEZTERMINOWO" **nie jest** u niego rozróżnialne od „braku ustalonego progu" — zmierzone,
mimo że sam postawiłem to jako warunek. **Obie odpowiedzi wypadły przeciwko niemu i obie
zapisał.** Odnotowuję, bo to jest sens rundy krzyżowej: sprawdzając cudze, znajdujesz swoje.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · zero zapisu w cudzych repozytoriach, ścieżki bezwzględne, nigdy
`cd` · nic poza fundację · **każde wyszukiwanie zasilające werdykt niesie kontrolę pozytywną**.
