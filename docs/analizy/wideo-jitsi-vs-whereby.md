# Wideo w Gabinecie: Jitsi self-host vs Whereby

Dokument decyzyjny F0. Odbiorca: właściciel (Jakub). Decyzja potrzebna **przed F3**
(rezerwacja + płatności generuje link do spotkania) — zadanie ze specyfikacji M5/15
ma wpisane wprost: „bez decyzji nie da się tego zadania rzetelnie wycenić".

**Co jest już rozstrzygnięte i czego ten dokument NIE otwiera:** pokoje są
**generowane per wizyta**, nie ma stałego linku w profilu specjalisty
(decyzja właściciela 07.08.2026). Powód jest kliniczny, nie techniczny: przy
stałym linku dwie wizyty pod rząd wpadają do tego samego pokoju i pacjent może
wejść w cudzą sesję. Ten dokument wybiera wyłącznie **dostawcę**.

---

## 1. Skala, pod którą liczymy

| Wielkość | Wartość | Skąd |
|---|---|---|
| Specjaliści | 111 | spec, s. 16/39/40 |
| Wizyty rocznie | 6 500 – 15 000 | 2 758 wizyt na 5 mies. w danych makiety (s. 59) → ~6,6 tys./rok; „kilkanaście tysięcy wizyt rocznie" (s. 50) |
| Udział wizyt online | **nieznany** — do ustalenia z fundacją | spec ma filtr online/stacjonarnie, ale nie podaje proporcji |
| Uczestnicy sesji 1:1 | 2 | pacjent + specjalista |
| Długość wizyty | 50 min albo 90 min | spec: sloty 50+10 i 90+10 |
| Wydarzenia grupowe | 90 min, 4–12 osób + prowadzący | s. 37 |
| Godziny pracy | 08:00–19:00, 12 slotów dziennie | s. 38 |

**Szczyt równoczesności** to wielkość, która realnie decyduje o rozmiarze serwera.
Przy 111 specjalistach i 12 slotach dziennie górne oszacowanie to kilkadziesiąt
równoległych sesji w godzinach popołudniowych, czyli **rzędu 40–80 równoczesnych
uczestników** w wariancie „większość wizyt online".

> ⚠️ **Do ustalenia z fundacją przed podpisaniem czegokolwiek:** jaki procent wizyt
> odbywa się online dzisiaj (Bookero to wie). Od tej jednej liczby zależy, czy
> rachunek za Whereby jest w tysiącach czy dziesiątkach tysięcy złotych rocznie.

---

## 2. Model kosztowy — dlaczego to nie jest porównanie dwóch cenników

Obie opcje mają koszt o **innym kształcie**, i to jest sedno sprawy:

- **Whereby** (i każdy inny SaaS wideo): koszt rośnie **liniowo z liczbą minut**.
  Zero kosztu na starcie, zero pracy utrzymaniowej, rachunek rośnie razem
  z fundacją. Jednostka rozliczeniowa to zwykle **uczestnikominuta**
  (participant-minute) — sesja 50-minutowa we dwoje to **100 jednostek**.
- **Jitsi self-host**: koszt to **stała opłata za serwer** plus **praca człowieka**.
  Nie rośnie z liczbą wizyt aż do momentu, w którym trzeba powiększyć maszynę.

Dlatego zamiast porównywać dwie kwoty, liczymy **próg opłacalności**: przy jakim
wolumenie SaaS zaczyna kosztować więcej niż własny serwer.

### 2.1 Koszt wariantu self-host (policzony)

| Pozycja | Kwota | Uwaga |
|---|---|---|
| Serwer wideo — Hetzner CX33 (4 vCPU / 8 GB) | ~8,5 €/mies ≈ **37 zł/mies** | cennik z `_architektura/10-infrastruktura-serwerowa.md` |
| …albo CX43 (8 vCPU / 16 GB), jeśli szczyt przekroczy ~50 uczestników | ~16 €/mies ≈ **70 zł/mies** | resize w kilka minut, bez migracji |
| Backup Hetznera (+20%) | ~7–14 zł/mies | |
| Transfer | **0 zł** | 20 TB w cenie; 10 000 sesji 1:1 × 50 min ≈ 7,5 TB/rok |
| **Razem infrastruktura** | **~45–85 zł/mies ≈ 540–1 020 zł/rok** | |
| Wdrożenie (jednorazowo): instalacja, coturn, JWT, monitoring, runbook | 16–24 h × 100 zł = **1 600 – 2 400 zł** | stawka z analizy architekta |
| Utrzymanie: aktualizacje, przegląd logów, reakcja na awarie | ~2 h/mies = **200 zł/mies ≈ 2 400 zł/rok** | to jest realny koszt, nie „darmowy open source" |
| **Rok pierwszy** | **~4 500 – 5 800 zł** | |
| **Każdy kolejny rok** | **~2 900 – 3 400 zł** | |

**Uczciwa uwaga:** największa pozycja w tej tabeli to praca człowieka, nie serwer.
Każde porównanie, które pokazuje Jitsi jako „37 zł miesięcznie", jest nieuczciwe.

### 2.2 Koszt wariantu Whereby (model + próg)

Whereby Embedded rozlicza się w modelu **abonament + uczestnikominuty**, z darmowym
progiem na start.

> ⚠️ **TO VERIFY przed decyzją:** aktualna stawka za uczestnikominutę i wysokość
> darmowego progu na <https://whereby.com/information/embedded/pricing/>. Cenniki
> SaaS zmieniają się co kilka kwartałów i **nie wolno przepisywać ich z pamięci**.
> Poniższa tabela jest policzona parametrycznie właśnie po to, żeby po wpisaniu
> aktualnej stawki dała odpowiedź bez ponownej analizy.

Zużycie przy naszej skali (sesje 1:1, 2 uczestników, bez grup):

| Scenariusz | Wizyty online / rok | Uczestnikominuty / rok |
|---|---|---|
| ostrożny (30% z 6 500 wizyt, po 50 min) | 1 950 | **195 000** |
| środkowy (60% z 10 000 wizyt, po 50 min) | 6 000 | **600 000** |
| wysoki (70% z 15 000 wizyt, po 60 min śr.) | 10 500 | **1 260 000** |

Wydarzenia grupowe dokładają osobno: 90 min × 8 osób = **720 uczestnikominut na
jedno spotkanie**, czyli jedno spotkanie grupowe kosztuje tyle, co siedem wizyt 1:1.

**Próg opłacalności.** Roczny koszt self-hostu (rok drugi i kolejne) to ~3 000 zł.
Whereby kosztuje tyle samo przy stawce:

| Scenariusz | Stawka, przy której Whereby = self-host |
|---|---|
| ostrożny (195 tys. jednostek) | ~0,0154 zł/jedn. ≈ **0,0038 USD/min** |
| środkowy (600 tys. jednostek) | ~0,0050 zł/jedn. ≈ **0,0012 USD/min** |
| wysoki (1,26 mln jednostek) | ~0,0024 zł/jedn. ≈ **0,0006 USD/min** |

Czyta się to tak: **jeśli realna stawka Whereby jest wyższa niż ~0,004 USD za
uczestnikominutę — a rynkowe stawki tego typu usług są tego rzędu — to self-host
wygrywa już w scenariuszu ostrożnym, i wygrywa tym mocniej, im więcej wizyt.**
Fundacja, która rośnie, płaciłaby za wzrost dwa razy: raz prowizją, raz minutami.

---

## 3. RODO — argument, który nie jest kosztowy

To jest system przetwarzający dane o zdrowiu (art. 9 RODO), a DPIA jest **wymagana,
nie opcjonalna** (spec M6/9). Specyfikacja wymienia „dostawcę pokoi wideo" wprost na
liście podprocesorów wymagających **umowy powierzenia**.

| | Jitsi self-host | Whereby |
|---|---|---|
| Nowy podprocesor w rejestrze | **nie** — serwer fundacji | **tak** — umowa powierzenia, ocena dostawcy, wpis do rejestru, klauzula informacyjna |
| Gdzie leżą metadane sesji (kto, z kim, kiedy, jak długo) | infrastruktura fundacji (Hetzner, UE) | infrastruktura dostawcy |
| Transfer poza EOG | brak | do sprawdzenia w umowie dostawcy |
| Co widzi dostawca | — | fakt i czas kontaktu konkretnej osoby z poradnią psychologiczną |
| Zakres DPIA | mniejszy | większy o cały rozdział o podprocesorze |

Ostatni wiersz jest ważniejszy, niż wygląda. Dla części pacjentów **sam fakt
korzystania z pomocy** jest informacją, której nie chcą ujawniać — specyfikacja
powtarza to przy SMS-ach (s. 5, 46, 59) i z tego powodu zabrania pisać w SMS-ie
cokolwiek o zdrowiu. Ta sama logika stosuje się do metadanych u dostawcy wideo.

**To nie jest argument rozstrzygający sam z siebie** — umowa powierzenia z
poważnym dostawcą z UE jest normalną praktyką i fundacja i tak podpisuje takie
umowy ze Stripe'em, SMSAPI i dostawcą poczty. Ale jest to jedna rzecz mniej do
zrobienia w DPIA i jedna mniej do pilnowania przy audycie dotacji.

---

## 4. Ryzyko operacyjne — argument w drugą stronę

Tu Whereby wygrywa i trzeba to powiedzieć wprost.

| Ryzyko | Jitsi self-host | Whereby |
|---|---|---|
| Awaria w trakcie sesji terapeutycznej | **nasza** — potrzebny dyżur i runbook | dostawcy, z SLA |
| Klient za restrykcyjną siecią (firmowy firewall, hotel) | wymaga poprawnie ustawionego **coturn/TURN** — najczęstsza przyczyna „nie działa mi kamera" | rozwiązane po stronie dostawcy |
| Zgodność przeglądarek i iOS Safari | testujemy sami przy każdej aktualizacji | testuje dostawca |
| Aktualizacje bezpieczeństwa | nasz obowiązek, w cyklu miesięcznym | dostawcy |
| Skok obciążenia (dzień z 60 sesjami naraz) | resize serwera, ale **z wyprzedzeniem** | elastyczne |
| Czas do pierwszego działającego pokoju | 3–5 dni roboczych | **1–2 dni** |

**Kontekst, który to ryzyko obniża:** fundacja **już** self-hostuje Keycloaka,
WordPressa i (planowo) Zammada na Hetznerze, ma Docker Compose, Ansible/Trellis
i monitoring na VPS-4. Jitsi nie wprowadza nowej klasy obowiązków — wchodzi
w istniejący reżim. Gdyby to była organizacja bez własnej infrastruktury,
rekomendacja byłaby odwrotna.

**Kontekst, który to ryzyko podnosi:** awaria wideo w gabinecie psychologicznym
nie jest awarią techniczną, tylko odwołaną sesją osoby w kryzysie. Dlatego
wariant self-host wchodzi **wyłącznie** z: TURN-em, monitoringiem dostępności
pokoju, alertem i **wariantem awaryjnym** (patrz §6).

---

## 5. Trzecia opcja, której nie wolno pominąć: JaaS (Jitsi as a Service, 8x8)

Zarządzany Jitsi od twórców projektu. Ten sam interfejs i to samo API pokoi co
w self-hoście, ale utrzymuje go dostawca; rozliczenie zwykle per aktywny
użytkownik miesięcznie, nie per minuta.

Dlaczego to jest istotne: **migracja JaaS → self-host (i odwrotnie) jest tania**,
bo to ta sama technologia i to samo API. Migracja Whereby → cokolwiek innego
oznacza przepisanie integracji.

> ⚠️ **TO VERIFY:** aktualny cennik JaaS i limity darmowego progu
> (<https://jaas.8x8.vc/>). Jeśli okaże się porównywalny z self-hostem, jest to
> najlepszy wariant „na start" — zero pracy utrzymaniowej przy zachowaniu drogi
> ucieczki do self-hostu bez zmiany kodu.

---

## 6. Rekomendacja

**Rekomendujemy Jitsi — z kolejnością wdrażania, która nie zamyka drogi odwrotu:**

1. **Kod nie zna dostawcy.** Interfejs `DostawcaPokojuWideo` z trzema operacjami:
   `utworzPokoj(rezerwacja)`, `uniewaznijPokoj(rezerwacja)`, `linkDlaUczestnika(rola)`.
   Reszta systemu (potwierdzenie mailem, SMS 2 h przed, karta wizyty w panelu,
   przeniesienie linku przy zmianie terminu) rozmawia **wyłącznie** z tym
   interfejsem. Koszt: kilka godzin. Zysk: zmiana dostawcy to jedna klasa, a nie
   przepisanie F3 i F6.
2. **Start na Jitsi** — self-host na osobnej maszynie albo JaaS, zależnie od
   wyniku weryfikacji cenników z §5. Pokój generowany przy potwierdzeniu
   płatności, dostęp przez **token JWT ważny w oknie wizyty** (od 15 min przed
   do 15 min po), nazwa pokoju losowa i nieodgadywalna — nigdy `NP-2857`.
3. **Wariant awaryjny wpisany w kod od początku**: gdy dostawca nie odpowiada
   przy tworzeniu pokoju, rezerwacja **i tak** się kończy sukcesem, a link
   dostaje status „w przygotowaniu"; zadanie w kolejce dokłada go i wysyła
   aktualizację. Płatność pacjenta nigdy nie czeka na dostawcę wideo.
4. **Bramka przed F3:** test, w którym dwie kolejne wizyty tego samego
   specjalisty dostają **różne** pokoje, a token z pierwszej wizyty **nie**
   wpuszcza do drugiej. To jest ta reguła kliniczna z §wstępu, wyrażona jako test.

**Dlaczego Jitsi, a nie Whereby — w jednym zdaniu:** przy tej skali koszt SaaS
rośnie z liczbą wizyt, a fundacja ma rosnąć; jednocześnie fundacja ma już
infrastrukturę i kompetencje, żeby unieść self-host, a przy danych o zdrowiu
jeden podprocesor mniej realnie skraca DPIA.

**Kiedy ta rekomendacja się odwraca (warto to znać teraz, żeby nie relitygować
później):**

- jeśli okaże się, że **online to margines** (poniżej ~20% wizyt) — wtedy
  wolumen nie uzasadnia własnego serwera i wygrywa SaaS;
- jeśli fundacja nie zapewni **dyżuru** reagującego na awarię wideo w godzinach
  przyjęć — wtedy ryzyko kliniczne przeważa nad kosztem;
- jeśli weryfikacja cennika JaaS pokaże koszt niższy od pracy utrzymaniowej —
  wtedy JaaS zamiast self-hostu (to nie jest zmiana dostawcy, tylko trybu hostingu).

---

## 7. Czego potrzebujemy od właściciela

| # | Pytanie | Bez odpowiedzi nie da się |
|---|---|---|
| 1 | **Jaki procent wizyt jest dziś online?** (Bookero to wie) | policzyć rachunku SaaS ani dobrać rozmiaru serwera |
| 2 | **Zatwierdzenie kierunku: Jitsi czy Whereby** | zacząć zadania M5/15 w F3 |
| 3 | Jeśli Jitsi: **self-host czy JaaS** — po sprawdzeniu cennika z §5 | zamówić serwer / konto |
| 4 | Kto reaguje na awarię wideo w godzinach przyjęć | domknąć runbook (spec M6/3) |

Do czasu odpowiedzi `WIDEO_DOSTAWCA` w `.env.example` zostaje puste, a kod nie
zakłada żadnego dostawcy. Wpis w rejestrze decyzji: `docs/DECYZJE.md`,
D-2026-08-07-06 (status: OTWARTE) i zadanie Z-02.
