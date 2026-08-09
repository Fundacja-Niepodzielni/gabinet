# ZLECENIE-043 — ⛔ WYCOFUJĘ POLECENIE Z `039`. „2 dni" NIE JEST odwołane. Nie kasuj tej wartości.

**Od:** architekt · **09.08.2026, noc** · potwierdź zwyczajnie · **to jest SPROSTOWANIE, pilne**

---

## 1 · Co Ci kazałem i dlaczego to było błędne

W `ZLECENIE-039` §1 napisałem:

> *„⚠ Wartość «2 dni» dla linku płatności JEST ODWOŁANA. Zastępuje ją jedna liczba na ścieżkę:
> 10 min / 10 min przy samodzielnym, 48 h / 48 h przy psychologu. Nie zostawiaj starej wartości
> obok nowej."*

**To polecenie jest błędne i je wycofuję.** Przeczytałem specyfikację — **rzeczy, której nie
zrobiłem przed napisaniem tamtego zlecenia.**

## 2 · Co naprawdę mówi specyfikacja — cytat, nie parafraza

> **„Na opłacenie terminu umówionego przez specjalistę są 2 dni, a nie 10 minut jak przy
> rezerwacji własnej. […] Po otwarciu linku do płatności termin jest trzymany JESZCZE 10 MINUT
> na samo dokończenie transakcji, tak jak przy każdej innej rezerwacji."**
> — `01-jak-dziala-system`, linie 303–307

**To jest konstrukcja DWUSTOPNIOWA i lepsza od mojej:**

| etap | ile | po co |
|---|---|---|
| termin zarezerwowany dla pacjenta | **2 dni** | „nie siedzi przy komputerze z kartą, tylko wyszedł z gabinetu" |
| od OTWARCIA linku do dokończenia płatności | **10 minut** | tyle samo co przy każdej innej rezerwacji |

**I ona rozwiązuje dokładnie ten problem, który sam postawiłem** („płatność dociera po wygaśnięciu
blokady"): **okno 10 minut startuje w chwili otwarcia linku, a nie w chwili wysłania maila.**
Moje „jedna liczba na ścieżkę" **zlikwidowałoby ten mechanizm i zostawiło problem otwarty.**

## 3 · Co robisz

**NIC nie kasujesz.** `waznoscLinkuPlatnosciDni: 2` w `ZestawRegul` **zostaje**.
**Jeśli zdążyłeś usunąć albo zmienić tę wartość — przywróć ją i powiedz.**

**Jeśli zapisałeś moje polecenie do `docs/DECYZJE.md`** — **nie kasuj wpisu, dopisz sprostowanie
z dzisiejszą datą.** Cicha podmiana nie dociera do tego, kto zdążył przeczytać wersję poprzednią;
to Wasza własna zasada z dziennika decyzji i obowiązuje też mnie.

## 4 · Sprawdziłem WSZYSTKIE Twoje liczby wobec specyfikacji — i to jest dobra wiadomość

| reguła | `ZestawRegul` | specyfikacja | |
|---|---|---|---|
| okno bezpłatnego odwołania | 24 h | 24 h | ✅ |
| limit przełożeń | 2 | 2 | ✅ |
| najbliższy termin | 2 h | 2 h | ✅ |
| kalendarz pacjenta | 30 dni | 30 dni | ✅ |
| horyzont wystawiania | 7 dni | 7 dni | ✅ |
| przerwa między wizytami | 10 min | 10 min | ✅ |
| blokada koszyka | 10 min | 10 min | ✅ |
| link płatności | 2 dni | 2 dni | ✅ |
| limit niskopłatnych | 10 wizyt | 10 | ✅ |
| limit podażowy | 4/tydzień na specjalistę | 4, **przy układaniu grafiku** | ✅ |
| kredyt za odsprzedany termin | włączony | jest w regułach | ✅ |
| auto-domknięcie wizyty | 48 h | 48 h | ✅ |

> **DWANAŚCIE NA DWANAŚCIE. Twoje wartości zgadzają się ze specyfikacją co do jednej.**
> **Jedyną rzeczą, która groziła rozjazdem, było MOJE wczorajsze polecenie.**

**Komentarz w `wersjaZerowa()` podaje źródło każdej liczby** — i dlatego dało się to sprawdzić
w pięć minut zamiast w godzinę. **To jest ta sama praktyka, której wymagam od Was przy pomiarach,
zastosowana do konfiguracji. Odnotowuję, bo działa.**

## 5 · Jedna rzecz do sprawdzenia przy okazji, NIE dziś

Specyfikacja mówi o **limicie podażowym 4/tydzień**, że **„blokada działa przy układaniu grafiku,
a NIE przy rezerwacji"**, i podaje powód: *„pacjent nigdy nie powinien zobaczyć wolnego terminu
i dostać odmowy przy płatności — w najgorszym możliwym momencie, po podjęciu decyzji i wyjęciu
karty"*.

**Wartość masz. Miejsce egzekwowania — do sprawdzenia, gdy dojdziesz do grafiku.**
**Do zaległościnika, nie na dziś.**

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · realmu nie dotykasz · ścieżki bezwzględne · nic poza fundację ·
**S-3 obowiązuje. S-2 zawieszone — domykasz dzień.**
