# Uwagi architekta do 05-DECYZJE-makiety.md (07.08.2026)

Dziennik decyzji makiety (stan 04.08.2026) jest źródłem prawdy o REGUŁACH BIZNESOWYCH,
ale trzy jego fragmenty są NADPISANE późniejszymi decyzjami ekosystemu — obowiązuje
CLAUDE.md tego repo:

1. **Konto pacjenta (wiersze 7 i 21 tabeli decyzji: „konto w tle + magic link + opcja
   hasła w checkoucie")** — NADPISANE decyzją „logowanie wszędzie przez SSO" (07.08):
   konta pacjentów żyją w Keycloak (rola `pacjent`), tworzenie w tle przez Admin API,
   „magic link" = link aktywacyjny action-token Keycloaka, ŻADNYCH haseł lokalnych
   w Gabinecie. Zachowanie z perspektywy pacjenta ma pozostać identyczne jak w makiecie.

2. **Limit wizyt niskopłatnych — ROZSTRZYGNIĘTY: 10 WIZYT na pacjenta** (nie godzin).
   Rozdz. 12 i 24 dziennika: „pierwotnie 4, podniesiony na wniosek fundacji". Wiersze
   mówiące „4 h na osobę" (tabela decyzji poz. 18 i lista w rozdz. 3) to NIEDOCZYSZCZONY
   ślad sprzed podniesienia — ignorować. Liczymy WIZYTY („3 z 10"), nie minuty.
   Wartość jako konfiguracja z wersjonowaniem (CLAUDE.md zasada 14) — start: 10.

3. **Technologia backendu** — rozdz. 26 wskazuje jedynie „backend w PHP" (bez frameworka);
   decyzja ekosystemu doprecyzowała: **Laravel 13** (CLAUDE.md). Zgodne.

Poza tym dziennik POTWIERDZA i uszczegóławia rzeczy już obecne w planie — w razie
wątpliwości przy implementacji sięgaj do niego po UZASADNIENIA reguł (150 zasad
z uzasadnieniami do powtórzenia pacjentowi). Szczególnie warte uwagi rozdziały:
3 (pełna macierz odwołań — serce systemu), 13 (prowadzący przypisuje się sam),
20 (faktura = wynagrodzenie specjalisty, bez śladu prowizji w dokumencie),
24 (limit podażowy 4/tydz. egzekwowany przy WYSTAWIANIU grafiku, nie przy rezerwacji),
25 (zwroty wykonuje człowiek — nigdy „zwrot wykonany"), 27 (maile edytuje koordynator,
SMS-y nie), 15 i 23 (klasy błędów niewidocznych na ekranie — wprost pod nasze bramki).

Brakuje nadal: pełnych ŹRÓDEŁ makiety (src/, reguly.ts, skrypty bramek) — właściciel
dośle; do F7 wystarczy specyfikacja + ten dziennik.
