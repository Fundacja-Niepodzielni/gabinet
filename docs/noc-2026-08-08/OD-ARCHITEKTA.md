# Od architekta — noc 8/9 sierpnia 2026

Wpisy DORADCZE, powstające bez bieżącego nadzoru właściciela. Twarde zakazy zlecenia obowiązują
niezależnie od nich. Sprzeczność z zakazem → nie wykonuj, zapisz w `DZIENNIK.md`, idź dalej.

---

## INFORMACJA NA RANO — źródła makiety są dostępne

**⛔ TO NIE JEST ZADANIE NA DZIŚ W NOCY. NIE ZACZYNAJ FRONTENDU.**

Właściciel przekazał źródła makiety frontendu:
`https://github.com/Fundacja-Niepodzielni/gabinet-makieta`

To zdejmuje blokadę zapisaną w `CLAUDE.md` („Frontend: makieta React/Vite — źródła dojdą, do tego
czasu backend-first"). Zapisuję to teraz wyłącznie po to, żeby informacja nie zginęła między
sesjami — **podpięcie frontendu to nowa budowa**, a noc jest przeznaczona na weryfikację. Gdybyś
zaczął to teraz, rano miałbym dużą zmianę bez żadnej rundy niezależnej, czyli dokładnie ten dług,
który tej nocy spłacamy.

### Co zrobić z tą informacją TERAZ (i tylko to)

Jedno zdanie w `PODSUMOWANIE.md`, w sekcji „co pierwsze rano", obok rzeczy już tam zapisanych:
„źródła makiety dostępne pod `Fundacja-Niepodzielni/gabinet-makieta` — do rozpoznania przed
planowaniem fazy frontendowej".
Nie klonuj, nie analizuj, nie planuj. Kolejka nocna bez zmian.

### Uwaga metodyczna dla porannej sesji — zapisz ją razem z informacją

Makieta to **61 ekranów** i jest ŹRÓDŁEM WYGLĄDU, a nie źródłem prawdy o zachowaniu. Reguła 1
z `CLAUDE.md` obowiązuje bez zmian: **serwer jest jedynym rozstrzygającym**, frontend tylko chowa
przyciski. Przy podpinaniu ekranów istnieje realne ryzyko, że reguła biznesowa widoczna w makiecie
(limit, okno 24 h, warunek zwrotu) zostanie zaimplementowana po stronie klienta, bo tam ją widać —
i wtedy powstanie druga, niezależna implementacja reguły, której serwer nie zna. To jest ten sam
kształt co „dwóch pisarzy tożsamości", który naprawiałeś dziś wieczorem, tylko przeniesiony na
reguły biznesowe.
Do rozpoznania w fazie frontendowej: dla każdej reguły widocznej w makiecie wskazać funkcję
serwera, która ją rozstrzyga — i ZERO reguł istniejących wyłącznie w makiecie.

Dodatkowo, z dzisiejszej lekcji o odbiorcy-człowieku (helpdesk, język interfejsu): makieta niesie
formaty dat, godzin i strefę czasową. Kontrola ma mierzyć **to, co widzi pacjent na ekranie**,
a nie ustawienie, które ma to wyprodukować. Godzina wizyty pokazana w złej strefie to człowiek,
który przychodzi o złej porze.
