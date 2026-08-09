# ZLECENIE-039 — trzy decyzje zamknięte. I propozycja właściciela, która USUWA cały problem scalania.

**Od:** architekt · **09.08.2026, noc** · potwierdź zwyczajnie · **kolejki NIE zmieniam** ·
**zapis wymagań, nie pozycja do wykonania**

---

## 1 · ZAMKNIĘTE — trzy wartości, wpisz do `docs/DECYZJE.md`

| co | wartość | uwaga |
|---|---|---|
| blokada slotu, **umawianie samodzielne** | **10 min** | *„skoro mamy politykę, to super, trzymajmy się jej"* — wartość zapisana zostaje |
| blokada slotu, **umawia psycholog** | **48 h** | |
| **ważność linku płatności** | **= ŻYWOTNOŚCI BLOKADY** | *„zgadzam się, żeby link żył tyle, ile blokada, to sensowne"* |

> **⚠ Wartość „2 dni" dla linku płatności JEST ODWOŁANA.** Zastępuje ją jedna liczba na ścieżkę:
> **10 min / 10 min** przy samodzielnym, **48 h / 48 h** przy psychologu. **Nie zostawiaj starej
> wartości obok nowej** — to byłaby ta sama rzecz opisana dwa razy, czyli `P3`.

**Zostaje w mocy przypadek brzegowy z `ZLECENIE-038`:** okno = **min(okno_ścieżki,
czas_do_wizyty − margines)**. Przy 48 h to nie jest teoria: wizyta jutro rano, umówiona dziś
wieczorem, dałaby termin płatności **po wizycie**.

## 2 · ⚠ KIERUNEK ZMIENIONY: weryfikacja kodem RAZ, nie przy każdej rezerwacji

Właściciel cofa własną decyzję sprzed godziny, z powodu kosztu, **i ma rację merytoryczną,
nie tylko finansową:**

> **Weryfikacja dowodzi WŁADANIA NUMEREM. Raz udowodnione — udowodnione.** Powtarzanie kodu
> przy każdej rezerwacji **nie mówi nic nowego o tożsamości**; mówi tylko, że ta sama osoba
> ma ten sam telefon.

**ALE weryfikacja pełniła u nas DWIE funkcje i druga zostaje bez obrony:**
w `ZLECENIE-038` §4 napisałem, że kod przy każdej rezerwacji **znosi zamrażanie grafiku**
(znalezisko `D5`). **Przy weryfikacji jednorazowej ta obrona znika i wraca stary problem.**

**Nie proponuję wracać do kodu za każdym razem — proponuję TAŃSZE obrony, których nie
rozważyliśmy, bo SMS wydawał się załatwiać wszystko:**

1. **Limit równoczesnych nieopłaconych blokad na pacjenta** (rząd 1–2). Zamrożenie grafiku
   wymaga wielu blokad naraz — **to jest najtańsza i najskuteczniejsza z tych obron**.
2. **Ograniczenie tempa** na numer/adres/sesję — zwykłe, nudne, wystarczające.
3. **Kod wymagany PONOWNIE tylko w trzech sytuacjach:** numer nieznany · pacjent ma już aktywną
   nieopłaconą blokadę · **dane nie zgadzają się z tymi przy numerze**.
4. **⚠ POWIADOMIENIE JEST MECHANIZMEM WYKRYWANIA, nie uprzejmością.** Potwierdzenie rezerwacji
   i tak idzie SMS-em na zweryfikowany numer — więc **jeśli ktoś umówi wizytę na cudze dane,
   właściciel numeru dowie się natychmiast.** Nie musimy blokować wszystkiego, co da się wykryć.
   **I to nie kosztuje ani jednego SMS-a więcej**, bo tę wiadomość i tak wysyłamy.

**Do właściciela idzie ode mnie wniosek kosztowy:** kod jednorazowy + potwierdzenie rezerwacji
to **mniej więcej tyle SMS-ów, ile i tak planowaliśmy**, a nie dwa razy tyle.

## 3 · ⚠⚠ PROPOZYCJA WŁAŚCICIELA, KTÓRA DOTYKA ZASADY TWARDEJ 2 — NIE WYKONUJ, ZAPISZ

Właściciel zaproponował dwie rzeczy, które **są tym samym rozwiązaniem opisanym dwa razy**
(sam tego nie zauważył, ja to składam):

> *„czy nie można zakładać konta automatycznie, w sensie usunąć opcję gościa? […] pacjenci
> zamiast haseł mieliby jednorazowe kody na maila lub telefon"*
> *„ktoś bez konta może podać sam numer i go potwierdzić, a system uzupełni resztę danych —
> to w sumie tak, jakby ktoś miał już konto, tylko bez hasła"*

**To jest logowanie bezhasłowe kodem jednorazowym, czyli konto pacjenta bez hasła.**

**Dlaczego to jest najmocniejsze uproszczenie, jakie dziś padło:**

- **cała konstrukcja pseudo-rekordów, scalania po telefonie, okna 24 miesięcy i dowodu władania
  identyfikatorem — PRZESTAJE BYĆ POTRZEBNA.** Licznik wisi na `sub` z Kont Niepodzielni,
  dokładnie tak, jak każe `D-EKO-002`. **Znika wyjątek, a nie powstaje nowy;**
- **liczba kroków dla pacjenta się NIE ZMIENIA** — i tak miał potwierdzać numer kodem. Zamiast
  „potwierdź numer" będzie „potwierdź numer i tym samym masz konto";
- **żadnych haseł** — zgodne z zasadą twardą 2 i z `D-EKO-001` bez naciągania;
- **zakładanie konta w tle jest już zdecydowane** (`CLAUDE.md` zasada 2: *„tworzenie w tle przez
  Admin API + link aktywacyjny"*). **To nie jest nowy pomysł — to dokończenie istniejącego.**

**Co tracimy i trzeba powiedzieć głośno:** **guest checkout jako zasada twarda przestaje
obowiązywać.** Dziś ktoś może zarezerwować, nie potwierdzając niczego. Po zmianie **bez
potwierdzenia kodem nie ma rezerwacji.** To jest **decyzja właściciela o zmianie zasady twardej**,
nie moja i nie Twoja.

**Zostaje jednorazowy dług:** rezerwacje gościa **już istniejące** trzeba będzie raz skojarzyć.
Zbiór skończony i niewielki — inaczej niż problem, który znika.

**Twoje zadanie: ŻADNE.** Nie zmieniaj modelu, nie usuwaj ścieżki gościa, nie zakładaj kont.
**Zapisz to jako propozycję ze statusem „czeka na decyzję właściciela"** i idź dalej swoją kolejką.

## 4 · Co mierzą inni, żebyś nie mierzył tego sam

**Konta dostają pytanie, czy nasz Keycloak umie logowanie kodem jednorazowym bez hasła
i czy wymaga to rozszerzenia.** Bez tej odpowiedzi propozycja z §3 jest życzeniem.
**Nie sprawdzaj tego u siebie — to nie Twój przedmiot.**

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · **modelu danych nie zmieniasz** · realmu nie dotykasz ·
ścieżki bezwzględne · nic poza fundację · **S-2 i S-3 obowiązują.**
