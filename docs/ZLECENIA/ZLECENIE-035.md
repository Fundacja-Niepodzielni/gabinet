# ZLECENIE-035 — właściciel rozstrzygnął B8. Limit TWARDY, wizyty gościa DOLICZANE. Najpierw POMIAR.

**Od:** architekt · **09.08.2026, wieczór** · potwierdź zwyczajnie · **`PODJETO-032` zostaje bieżąca**

---

## 1 · Co rozstrzygnął właściciel — dosłownie, zanim to zinterpretuję

- **Limit 10 wizyt niskopłatnych jest TWARDY**, *„czasami jednak zdejmowany — ale to należy do
  decyzji fundacji"*. Czyli: **twarda bramka + świadome zdjęcie przez człowieka**, nie próg
  uznaniowy z automatu.
- **Rezerwacja gościa i tak zbiera: imię, nazwisko, telefon, e-mail.** Dane istnieją,
  niezależnie od tego, czy ktoś ma konto.
- **Wizyty gościa MAJĄ się doliczać.** *„Jeśli umówi 3 wizyty bez konta i potem je założy,
  system powinien przypisać mu jego stare wizyty."*
- **Pytanie właściciela:** czy nie robić „pseudo-kont" powiązanych z mailem i telefonem, żeby
  dało się to śledzić i potem przypisać — z uwagą, że **telefon byłby bezpieczniejszy**.
- **⚠ PSYCHOLOG UMAWIA PACJENTA** — *„z reguły na niskopłatnych psycholog umawia kolejną
  wizytę"* — **i to też musi się wliczać w limit.**

## 2 · ROZSTRZYGNIĘCIE ARCHITEKTONICZNE — i granica, której nie wolno przekroczyć

**Kierunek właściciela przyjmuję.** Poniżej rozgraniczenie, żeby nikt nie odczytał tego jako
cofnięcia `D-EKO-001` albo `D-EKO-002`:

> **TOŻSAMOŚĆ DO LOGOWANIA to nie to samo co PODMIOT PACJENTA.**
> `D-EKO-002` („wiąż po `sub`, nigdy po e-mailu") dotyczy **tożsamości uwierzytelnionej** —
> tego, kogo system wpuszcza. **Nie zakazuje istnienia lokalnego rekordu pacjenta**, który
> gromadzi wizyty i nosi dane kontaktowe zebrane przy rezerwacji.

**Trzy twarde granice tego rekordu — łamią całą konstrukcję, jeśli którakolwiek padnie:**

1. **Pseudo-rekord NIE UWIERZYTELNIA NICZEGO.** Nie daje dostępu do żadnego widoku, historii ani
   danych. Jest **licznikiem i kandydatem do skojarzenia**, niczym więcej. Gdyby kiedykolwiek
   zaczął wpuszczać — jest drugim źródłem tożsamości i łamie `D-EKO-001`.
2. **Skojarzenie wymaga DOWODU WŁADANIA identyfikatorem** — kod SMS na ten numer albo link na ten
   adres. **Bez tego dowodu system nie ujawnia, że coś znalazł.**
3. **Skojarzenie jest jednorazową operacją przy zakładaniu konta**, po której **kluczem zostaje
   `sub` z Kont Niepodzielni.** Nie budujemy trwałego wiązania po telefonie.

## 3 · ⚠ TRZY PUŁAPKI, KTÓRYCH W PYTANIU NIE BYŁO — wypisuję, zanim ktokolwiek zacznie pisać kod

**(a) Ujawnienie cudzej historii przez wpisanie cudzego numeru.** Jeśli przy zakładaniu konta
system powie *„znaleźliśmy 3 wcześniejsze wizyty na ten numer"* **zanim** potwierdzimy władanie
numerem, to **wystarczy wpisać czyjś numer, żeby dowiedzieć się, że ta osoba korzystała z pomocy
psychologicznej.** To jest ujawnienie danych o zdrowiu przez formularz rejestracji.
**Kolejność jest odwrotna: najpierw kod, potem informacja.**

**(b) Numery telefonów są PONOWNIE PRZYDZIELANE.** Operatorzy przekazują nieużywane numery
kolejnym abonentom (rząd wielkości: miesiące, nie dekady). Ktoś może **odziedziczyć numer razem
z cudzą historią wizyt**. Obrona: **okno czasowe skojarzenia** (kojarzymy tylko rezerwacje
z ostatnich N miesięcy — proponuję 24, spójnie z helpdeskiem) **plus zawsze kod potwierdzający**.

**(c) Zależność administracyjna, o której właściciel wie: rejestracja nadawcy SMS w SMSAPI
NIE ZOSTAŁA jeszcze złożona.** Bez niej **nie wyślemy kodu**, czyli **punkt 2 tej konstrukcji jest
niewykonalny**. Zapisz to jako warunek zewnętrzny — **nie jako Twój dług**.

## 4 · PSYCHOLOG UMAWIAJĄCY PACJENTA — to jest cięższe niż wygląda i chcę to nazwać teraz

Skoro **na niskopłatnych regułą jest, że psycholog umawia następną wizytę**, to:

- **licznik limitu musi wisieć na PACJENCIE, nie na tym, kto kliknął.** Wizyta umówiona przez
  psychologa liczy się tak samo jak umówiona przez pacjenta. Konstrukcja licząca „rezerwacje
  złożone przez zalogowanego pacjenta" **pominęłaby większość wizyt niskopłatnych** — czyli limit
  nie działałby dokładnie tam, gdzie ma działać;
- **wychodzi z tego pytanie, na które nie mam prawa odpowiedzieć sam:** gdy psycholog wpisuje dane
  osoby, która nie ma konta — **kto akceptuje regulamin i zgodę na przetwarzanie?** Dziś nie
  wiadomo. **Idzie do właściciela ode mnie, nie od Ciebie.**

## 5 · CO MASZ ZROBIĆ — POMIAR, nie budowa

**Nie buduj niczego z tej konstrukcji.** To praca F2, a model danych jest dokładnie tym miejscem,
przed którym słusznie się zatrzymałeś. **Chcę wiedzieć, co jest DZIŚ** — z kontrolą pozytywną
przy każdym wyszukiwaniu:

1. **Czy rezerwacja gościa w ogóle tworzy rekord pacjenta**, czy dane wiszą przy samej rezerwacji?
2. **Które z czterech danych (imię, nazwisko, telefon, e-mail) są przechowywane i w jakim polu** —
   i **czy którekolwiek ma wymuszoną jednoznaczność**?
3. **Jak dziś liczony jest limit** — po czym rozpoznaje „tę samą osobę"?
4. **Czy istnieje ścieżka „psycholog umawia pacjenta"** i czy wpada w ten sam licznik?
5. **Kierunek 0:** dodaj rezerwację w najbardziej naturalny sposób, jaki system dopuszcza, i sprawdź,
   **czy licznik limitu sam się na niej znajdzie.**

**To jest pomiar czterech-pięciu odczytów, nie faza.** Wynik rozstrzyga, czy „pseudo-konto" jest
nowym bytem, czy nazwaniem czegoś, co już masz.

## ZAKAZY — bez zmian

Zero `main`, merge, deploy · **modelu danych nie zmieniasz w tej pozycji** · realmu nie dotykasz ·
ścieżki bezwzględne · nic poza fundację · **S-2 i S-3 obowiązują.**
