# ZLECENIE-088 · 19.08.2026 · OD architekta DO WSZYSTKICH SESJI — **F1 ZAMKNIĘTA**

## Wykonane przez architekta (O-9), po zgodzie właściciela

```
merge faza-1-retencja → main         FAST-FORWARD, bez konfliktów
main lokalnie / origin:              75b84e2 / 75b84e2   (zgodne)
znacznik:                            f1-zamkniete-19-08 (7c1cbaf), wypchnięty
gałąź faza-1-retencja na origin:     75b84e2
kopia bezpieczeństwa:                kopia-przed-merge-19-08 = b60c53a (ZOSTAJE)
przebieg kontrolny architekta na main: 326 passed, 2 skipped, 2282 asercji
```

**Faza F1 jest zamknięta decyzją właściciela z 19.08.2026 (wariant C).** Podstawa:
13 rund niezależnej weryfikacji, granica R13-1 opisana jawnie w `docs/GRANICA-R13-1.md`
z terminem i warunkiem znoszącym. Reguła zbieżności `D-2026-08-07-16` została **świadomie
nadpisana decyzją właściciela**, co jest zapisane w `D-2026-08-19-04` — to nie jest
pominięcie reguły i nikomu nie wolno tak tego czytać.

## Co pozostaje OTWARTE po scaleniu — jawnie

| pozycja | właściciel sprawy | termin |
|---|---|---|
| **R13-1** — granica ósmego piętra (kontrola skutku) | KOD-SILNIK | **zadanie zerowe etapu B** |
| **D-3** — `TwierdzeniaKomentarzyTest` poza bramką | sesja kodująca | etap B |
| **D-4 + D-5** — wyjątki gitleaks (strażnik wąskości działa) | — | pierwsze przepisanie historii, **oba razem albo żaden** |
| **Q-16** — kto akceptuje zgody, gdy psycholog umawia osobę bez konta | **Fundacja** | spotkanie (pozycja G7 briefu) |
| **BLK-01** — klient `gabinet` w realmie Keycloaka | repo `konta` | przy produkcji SSO |
| **F0** | — | ma własne otwarte pozycje; **NIE zamknięta przy F1** |

## Etap B — start

Prompty gotowe: `_architektura/gabinet-orkiestracja/PROMPT-KOD-SILNIK.md` (zadanie zerowe:
kontrola skutku; potem kontrakt operacji API uzgadniany trójstronnie i przestawienie
kształtu zrzutu reguł **przed pierwszą rezerwacją**) oraz `PROMPT-KOD-PLATNOSCI.md`.
Sesje TESTY i SPEC-UMOWA wznawiane wg swoich promptów — **pierwsza czynność każdej:
wersjonowana deklaracja zakresu** (`skrypty/zakresy/<STRUMIEN>.zakres`), inaczej strażnik
odmówi pierwszego commita.

Materiał czekający na etap B: plan 75 przypadków + 68 szkieletów + wymagania kontraktowe
W-01…W-14 (gałąź `testy-plan-f2`), specyfikacja umowna z rejestrem rozjazdów (SPEC-UMOWA).

## Podziękowanie i jedna liczba

Sesji KOD-F1: 13 rund, ~40 znalezisk zamkniętych z dowodem, osiem pięter jednej klasy,
z czego **cztery ostatnie znalazłeś sam, zanim znalazła je runda**. Zbieżność
29 → 9 → 2 → 5 → 1 → 3 → 1 → 1 to nie jest wykres słabnącej jakości, tylko rosnącej
trudności znajdowania.
