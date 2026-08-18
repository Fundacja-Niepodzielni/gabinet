# ODPOWIEDZ-044 · 12.08.2026 · OD architekta DO sesji SPEC-UMOWA

## 0. Weryfikacja niezależna produktów — ZIELONA

Sprawdziłem własnym przyrządem (inne listy słów niż Twoje kontrole, świeży skrypt):
pliki istnieją, PDF ma 13 stron, frazy nośne obecne („sto jednoczesnych", kwoty oferty,
„czego nie obejmuje"), zero nazw technologii z mojej listy (Laravel/PostgreSQL/Keycloak/
Redis/Pest/Docker/backend/API). Twoje kontrole z dowodem czerwieni przyjmuję.
**Dokument utrzymuje status „projekt do uzgodnienia" do przeglądu właściciela** —
właściciel powiadomiony.

## 1. Rozstrzygnięcia pozycji z §2

| # | Pozycja | Rozstrzygnięcie |
|---|---|---|
| 1 | **B4 zwroty 3 vs 3–5 dni** | **PRZYJĘTE jako brzmienie robocze: „zwykle 3–5 dni roboczych"** wszędzie (termin operatora karty — nie obiecujemy cudzego czasu). W dokumencie klienckim „kilka dni roboczych" [DO POTWIERDZENIA] zostaje do przeglądu treści z Fundacją. **Dopisz wpis do `docs/DECYZJE.md`** (D, 12.08, za architektem, uzasadnienie jw.). |
| 2 | **C8 faktura 7. / przelew 10.** | Rekomendacja **zatwierdzona jako pytanie na spotkanie** (brief §7.5 pkt 13 — dobrze zapisane). Decyzja należy do Fundacji (rytm rozliczeń z ich specjalistami). W specyfikacji umownej znacznik warunkowy zostaje. |
| 3 | **C10 lista startowa SMS** | Architektonicznie bez zastrzeżeń: budujemy WSZYSTKIE szablony, kanał per zdarzenie jest przełącznikiem — lista startowa nie zmienia zakresu budowy, zmienia koszt operacyjny. **Decyzja Fundacji** (brief §7.5 pkt 14 z kosztem — dobrze). Twoja lista pięciu zdarzeń idzie jako nasza rekomendacja. |
| 4 | **B2 przypomnienie 24/48 h** | Podejście zatwierdzone (48 h wg D-2026-08-09-06 + znacznik warunkowy + argument specyfikacji za 24 h). Po spotkaniu zostaje jedna wartość — poprawkę wykona ta sesja albo następca. |
| 5 | **B1 krok tożsamości — zdanie o wycenie** | **DO POPRAWKI TERAZ, nie czekamy na pomiar Kont.** Pomiar `027` jest bezterminowo wstrzymany (cała moc na Gabinet), więc zdanie „oba warianty mieszczą się w wycenie" wisi na niezmierzonym założeniu — a dokument idzie do klienta. Nowe brzmienie w miejscu tego zdania: **„Jeżeli wybrany wariant zwiększy nakład prac, różnicę pokażemy przed decyzją Fundacji — korekta zakresu wyłącznie za jej zgodą."** Zero twierdzeń o tym, gdzie leży koszt kodu jednorazowego. |

## 2. Do wykonania (zamyka Twoją pozycję)

1. Poprawka poz. 5 w `SPECYFIKACJA-UMOWNA.md` + **regeneracja PDF** + ponowne obie kontrole
   (pozytywna/negatywna + sieroty).
2. Wpis D do `docs/DECYZJE.md` za poz. 1.
3. ODPOWIEDZ-045 z meldunkiem (krótkim — dwa punkty wyżej, wynik kontroli).

Po tym Twój zakres jest **domknięty** — dokument czeka na przegląd właściciela;
uwagi z przeglądu wrócą osobnym zleceniem.

## 3. Co przejąłem ja (nie rób)

- Aktualizacja `_spotkanie/LISTA-DECYZJI.md` o pozycje G12–G14 (spójność z briefem §7.5
  pkt 12–14) i regeneracja PDF briefu — mój generator, robię sam.
- Powiadomienie właściciela o gotowości specyfikacji do przeglądu.
