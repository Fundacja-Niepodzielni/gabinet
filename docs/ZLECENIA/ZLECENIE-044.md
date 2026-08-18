# ZLECENIE-044 · 12.08.2026 · OD sesji SPEC-UMOWA DO architekta

**Kierunek odwrotny niż zwykle:** to meldunek ukończenia + pozycje wymagające Twojego
rozstrzygnięcia (zgodnie z promptem sesji: „rozjazd, którego nie umiem rozstrzygnąć
z dokumentów → ZLECENIE do architekta z propozycją brzmienia"). Plik zamyka moją pozycję
(S-3); nie czekam (S-2) — wszystko, co mogłem domknąć, jest domknięte.

---

## 1. Co jest gotowe

| Produkt | Plik | Stan |
|---|---|---|
| Rejestr rozjazdów | `gabinet/docs/specyfikacja/REJESTR-ROZJAZDOW.md` | kompletny; kontrola własna: **każda pozycja §0.2–0.3 DO-OMOWIENIA ma wiersz** (tabela „Kontrola kompletności" na końcu pliku) |
| Specyfikacja umowna v1 | `gabinet/docs/specyfikacja/SPECYFIKACJA-UMOWNA.md` | gotowa do przeglądu właściciela; struktura wg oferty (W1 = I1–I11, W2 = I12–I16, W3 = I17–I21); per iteracja: co powstaje / sprawdzalne kryteria / czego nie obejmuje; wartości reguł w tabelach; 14 pozycji warunkowych zebranych w rozdz. 9 |
| PDF w brandingu | `gabinet/docs/specyfikacja/Specyfikacja-umowna-System-rezerwacji-Gabinet.pdf` (13 stron) | wygenerowany kopią generatora: `_architektura/narzedzia/gen-pdf-spec-umowna.py` (oryginał `gen-pdf-brand.py` nietknięty — obsługuje nadal ofertę) |
| Brief spotkania | `_spotkanie/00-BRIEF-SPOTKANIE.md` §7.5 | **dopisane pkt 12–14**: pełna lista stawek pełnopłatnych · rozsunięcie dat faktura/przelew · lista powiadomień SMS (koszt) |

**Weryfikacja PDF (wg wzorca z generatora):**
- kontrola pozytywna (28 fraz: kwoty z oferty, kryterium „sto jednoczesnych żądań…",
  wartości reguł, granice zakresu) — **zielona**;
- kontrola negatywna (żargon i narzędzia: nazwy technologii, słownictwo warsztatowe,
  wzmianki o sposobie pracy) — **zielona, zero trafień**;
- obie z **dowodem czerwieni**: ta sama kontrola na PDF oferty pada z 14 brakami
  (kod wyjścia 1), więc przyrząd rozróżnia;
- sieroty nagłówków: żadna strona nie kończy się nagłówkiem (kontrola tekstowa
  na 41 nagłówkach źródła; detekcja udowodniona podstawieniem) — **zielona**.

**Zasady języka dotrzymane:** dokument kliencki bez żargonu, bez wzmianek o sposobie pracy
i narzędziach; jedyne kwoty = kwoty z oferty (100 zł/h netto, widełki wdrożeń, cennik usług
ze specyfikacji); zakres opisany funkcjami, **bez liczby ekranów** (61/47/39 — rozjazd A17).

## 2. Pozycje do rozstrzygnięcia — z propozycją brzmienia (nie zdecydowałem sam)

1. **Termin zwrotu w treściach (B4 rejestru).** Spec mówi raz „3 dni robocze", raz „3–5".
   **Propozycja:** wszędzie „**zwykle 3–5 dni roboczych**" — termin zależy od operatora
   karty, więc „3 dni" to obietnica poza naszą kontrolą. W dokumencie klienckim stoi
   „kilka dni roboczych" + znacznik [DO POTWIERDZENIA].
2. **Daty rozliczeniowe (C8).** Faktura i przelew oba do 10. dnia.
   **Propozycja:** „faktura do **7. dnia**, przelew do **10.**" — zachowuje rytm
   zestawienie-do-5 / uwagi-do-8 i daje 3 dni na sprawdzenie faktury. Dopisane do briefu
   §7.5 pkt 13 jako pytanie, nie decyzja.
3. **Lista startowa powiadomień SMS (C10).** Spec wewnętrznie sprzeczna („dwa zdarzenia"
   vs 7 szablonów). **Propozycja listy startowej SMS:** potwierdzenie rezerwacji ·
   przypomnienie przed wizytą · link 2 h przed wizytą online · odwołanie przez specjalistę ·
   propozycja z listy rezerwowej (W2). Reszta mailem; kanały per zdarzenie i tak są
   przełącznikiem. Dopisane do briefu §7.5 pkt 14.
4. **Przypomnienie 24 vs 48 h (B2).** W dokumencie klienckim wpisałem **48 h**
   (D-2026-08-09-06) z jawnym znacznikiem warunkowym i argumentem specyfikacji za 24 h.
   Brief §7.5 pkt 5 już to niesie — po spotkaniu trzeba w specyfikacji umownej zostawić
   jedną wartość.
5. **Krok tożsamości przy rezerwacji (B1).** Opisany jako pozycja warunkowa z dwoma
   wariantami (gość + link z maila / konto z kodem). Napisałem, że „oba warianty mieszczą
   się w wycenie iteracji" — **przyjąłem, że ewentualny koszt kodu jednorazowego leży po
   stronie Kont, nie w widełkach I3 Gabinetu. Jeśli pomiar Kont (027) pokaże inaczej,
   to zdanie trzeba osłabić przed przekazaniem Fundacji** — dlatego status dokumentu to
   „projekt do uzgodnienia".

## 3. Decyzje redakcyjne, które podjąłem sam (do Twojej kontroli)

- **Kwoty pośrednie wdrożeń** (11 400–18 300 / 9 800–15 700) i godziny per iteracja —
  przepisane 1:1 z oferty.
- Granica okna 24 h w kryterium I3 opisana „sekundę przed / sekundę po"; sama granica
  „rozstrzygnięta jednoznacznie i pokryta testem" — bez przesądzania kierunku w dokumencie
  klienckim (w kodzie jest to test 23:59/24:00/24:01).
- Tabela zwrotów (rozdz. 4.3) = 7 wierszy z 8-sytuacyjnej macierzy specyfikacji;
  wiersz kredytu pominięty świadomie (D-2026-08-09-01), kredyt nazwany w granicach zakresu
  jako „przeniesiony, nie odrzucony".
- Retencje: w dokumencie klienckim tylko „potwierdzenie okresów przechowywania" jako
  pozycja warunkowa — bez tabeli okresów (to zapis wewnętrzny właściciela, nie obietnica
  kliencka).

## 4. Czego NIE zrobiłem

- Nie dotykałem kodu (zgodnie z promptem).
- Nie zmieniałem oferty ani jej PDF.
- Nie rozstrzygnąłem żadnej pozycji 🔶/🔷 z rejestru — wszystkie mają właściciela decyzji
  i (gdzie się dało) propozycję brzmienia.

**Regeneracja PDF po poprawkach:** `python _architektura/narzedzia/gen-pdf-spec-umowna.py`,
potem kontrole: `scratchpad/sprawdz-pdf-spec.py` + `sprawdz-sieroty.py` (sesyjny scratchpad;
wzorzec procedury opisany w tym pliku i w generatorze).
