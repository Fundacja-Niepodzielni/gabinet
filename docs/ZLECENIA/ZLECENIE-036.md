# ZLECENIE-036 — pomiar przyjęty. Jedna poprawka do kanału zastępczego. I co idzie do właściciela.

**Od:** architekt · **09.08.2026** · potwierdź zwyczajnie · **`PODJETO-032` zostaje bieżąca**

---

## 1 · Trzy rzeczy, które ten pomiar rozstrzygnął — i jedna, której nikt nie oczekiwał

**(a) „Pseudo-konto" to nazwanie bytu, który już masz** (`pacjenci` z `keycloak_sub = NULL`,
wymuszone przez `rezerwacje.pacjent_id NOT NULL`). **To zdejmuje z decyzji właściciela cały
ciężar „budujemy nowy rodzaj konta"** — nie budujemy, tylko nazywamy i domykamy.

**(b) `limit_niskoplatnych_indywidualny` już istnieje.** Decyzja właściciela („twardy limit,
czasami zdejmowany") **trafia w model, który ją przewidział**. Rzadki przypadek — odnotowuję.

**(c) ⚠ NAJWAŻNIEJSZE, i nie było o to pytania: LIMIT NIE JEST DZIŚ LICZONY WCALE.**
Pięć trafień, wszystkie w definicji, **zero użyć**. Czyli **limit 10 wizyt dziś nie istnieje
jako reguła — istnieje jako liczba w konfiguracji.** To idzie do właściciela ode mnie **dzisiaj**,
bo mógł sądzić, że limit działa i tylko jest nieszczelny przy gościach. **Jest gorzej: nie ma go.**

**(d) Kierunek 0 zrobiony dosłownie i to on niesie dowód:** te same dane gościa dwa razy → **dwa
rekordy pacjenta**. Przyszły licznik zobaczyłby **dwie osoby po pięć wizyt zamiast jednej po
dziesięć**. **Największą luką nie jest brak pseudo-kont, tylko brak jednoznaczności** — Twoje
sformułowanie, przyjmuję bez zmian.

## 2 · ⚠ POPRAWKA: kanał zastępczy TAK, ale nie w tym kształcie

Napisałeś, że skoro kanał ma być wymienny (`D-2026-08-09-06`), to dowód władania może pójść
**e-mailem**, więc konstrukcja nie musi czekać na SMSAPI. **Zgoda co do kanału — ale jest tu
pułapka, której to zdanie nie zamyka:**

> **Dowód musi dotyczyć TEGO SAMEGO identyfikatora, po którym scalamy.**
> Scalanie po **telefonie** z dowodem na **e-mail** znaczy, że ktoś, kto ma dostęp do skrzynki,
> przejmuje rekordy przypięte do cudzego numeru. **To nie jest ten sam poziom dowodu, tylko
> inny przedmiot dowodu.**

**Dwa dopuszczalne kształty, wybierz świadomie i zapisz który:**

- **PEŁNY (docelowy):** scalamy po telefonie, dowód kodem SMS na ten numer. Wymaga SMSAPI.
- **OGRANICZONY (do czasu SMSAPI):** scalamy **tylko te rekordy, w których zgadza się e-mail
  ORAZ telefon**, a dowód idzie e-mailem. **Węższy zbiór, ale dowód i przedmiot są zgodne.**
  Rekordy pasujące samym telefonem czekają na kanał SMS.

**Nie chcę, żebyś to teraz budował** — chcę, żeby wybór był zapisany, zanim ktoś napisze
pierwszą linię, bo różnica jest w bezpieczeństwie, nie w wygodzie.

## 3 · Co robisz dalej — nic nowego

`PODJETO-032` bez zmian, potem `BEZ_DANYCH_OSOBOWYCH`. **Powyższe to zapis wymagań, nie pozycja.**
Trzy rzeczy z Twojego „nie sprawdziłem" (producent `email_skrot`, ścieżka tworzenia rezerwacji,
zgody) **zostają niesprawdzone i to jest w porządku** — pierwsze dwie wrócą w F2, trzecia jest
u właściciela.

## 4 · Co poszło ode mnie do właściciela na podstawie Twojego pomiaru

- **że limitu dziś nie ma** — jako sprostowanie mojego wcześniejszego opisu, nie jako nowość;
- **że pseudo-konto już istnieje**, więc jego decyzja nie wymaga nowej konstrukcji;
- **że brak jednoznaczności jest prawdziwą luką**, i to on rozstrzyga, po czym scalać;
- **poprawka o SMSAPI**: blokuje przypomnienie 48 h **twardo**, a przy kojarzeniu wizyt istnieje
  droga zastępcza — **ale węższa**, wg punktu 2 wyżej. **Powiedziałem mu wcześniej, że blokuje
  jedno i drugie tak samo. To było zbyt mocne i prostuję.**

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · **modelu danych nie zmieniasz** · realmu nie dotykasz ·
ścieżki bezwzględne · nic poza fundację · **S-2 i S-3 obowiązują.**
