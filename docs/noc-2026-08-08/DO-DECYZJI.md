# Do rozstrzygnięcia — noc z 8 na 9 sierpnia 2026

Rzeczy, których NIE MOGĘ rozstrzygnąć sam. Zasada nocy: **nigdy nie blokuję** —
zapisuję tutaj, wybieram wariant NAJBARDZIEJ ODWRACALNY (albo pomijam pozycję)
i idę dalej. Odpowiedź stosuję, gdy przyjdzie.

Architekt czuwa na kanale plikowym: odpowiedzi trafiają do `OD-ARCHITEKTA.md`
w tym katalogu. Każdy wpis niżej jest napisany tak, żeby dało się na niego
odpowiedzieć BEZ dopytywania: co blokuje · opcje · co wybrałem tymczasowo
i dlaczego · **co zmieni się w mojej pracy zależnie od odpowiedzi**.

Odpowiedź architekta z nocy jest DORADCZA. Twarde zakazy zlecenia obowiązują
niezależnie od niej.

---

## D-1 — czy wyjątek w skanerze sekretów ma obejmować WSZYSTKIE katalogi raportowe

> **ROZSTRZYGNIĘTE tej samej nocy — odpowiedź architekta w `OD-ARCHITEKTA.md`.**
> Wybrany wariant **3 z elementem 1**, czyli mój wybór docelowy. Wariant 2
> (`docs/**`) odrzucony stanowczo — z mojego własnego argumentu: raporty
> z natury cytują konfiguracje, katalog rośnie, nikt nie przewiduje zawartości.
>
> **Uzasadnienie przesunięte, i to jest istotne:** nie „żeby skaner był ostry",
> tylko dlatego, że raporty **nie potrzebują pełnych identyfikatorów** —
> wartość dowodowa jest w RELACJI między odczytami. Pełny identyfikator sesji
> w dokumencie to sam w sobie drobny wyciek. Skracanie usuwa PRZYCZYNĘ,
> a nie objaw, i obowiązywałoby nawet gdyby żaden skaner nie istniał.
>
> **Historii `83775f4` NIE przepisujemy** — nie przepisujemy wypchniętej
> historii dla samej czystości; wyjątek per katalog zostaje jako zawór na
> historię, której nie da się już zmienić. Dyscyplina skracania obowiązuje
> NAPRZÓD, od następnego raportu. Ta sama figura co przy dyscyplinie gałęzi:
> reguła wchodzi od teraz, a bieżący stan legitymizuje runda, nie przepisanie
> przeszłości.
>
> **Wdrożone tej nocy:** komunikat kroku „sekrety" w `skrypty/bramka.sh` UCZY
> teraz właściwej naprawy. Powód: obie drogi do czerwieni (zapomniany wyjątek,
> niezredagowany cytat) kończą się tak samo, więc kontrola jest fail-closed —
> niebezpieczny jest ODRUCH, bo najtańszą reakcją na „leaks found" jest
> dopisanie wyjątku zamiast usunięcia przyczyny.

**Co blokuje.** Nic nie blokuje pracy — pozycja jest zamknięta wariantem
odwracalnym. Rozstrzygnięcia wymaga **zasada na przyszłość**, bo dotyczy
kontroli bezpieczeństwa, a takich nie rozluźnia się po cichu ani zwyczajowo.

**Sytuacja (zmierzona, `ZNALEZISKA.md` N-8).** Raport z weryfikacji jest tekstem
o wysokiej entropii: identyfikatory sesji, skróty commitów, nazwy plików,
wklejone wydruki. Heurystyka `generic-api-key` w gitleaksie nie odróżnia takiego
tekstu od klucza API — tej nocy uznała za sekret **nazwę pliku**
`ZLECENIE-RUNDA-6.md` i zapaliła bramkę. To nie jest zdarzenie jednorazowe:
każdy kolejny katalog nocny/raportowy będzie się o to ocierał.

**Opcje.**

1. **Wyjątek per katalog, dopisywany świadomie** (co wybrałem tymczasowo).
   Dziś obejmuje wyłącznie `docs/noc-2026-08-08/`.
   *Za:* każde rozluźnienie widoczne w `git diff` i wymaga decyzji człowieka;
   zero precedensu. *Przeciw:* przy każdej nocnej sesji ktoś musi pamiętać
   o dopisaniu wpisu, a zapomnienie objawia się czerwoną bramką z mylącym
   komunikatem („leaks found") — czyli fałszywym alarmem bezpieczeństwa.
2. **Wyjątek na wzorzec `docs/**` albo `docs/noc-*/**`.**
   *Za:* działa raz na zawsze, nikt nie musi pamiętać.
   *Przeciw:* rozluźnia kontrolę na katalogu, który rośnie i którego zawartości
   nikt nie przewiduje. Ktoś kiedyś wklei do raportu prawdziwy sekret —
   a raporty z natury cytują konfiguracje.
3. **Bez wyjątku; zamiast tego dyscyplina redagowania** — w raportach nie wolno
   cytować ciągów o wysokiej entropii bez skrócenia (np. `55wUqZ…` zamiast
   pełnego identyfikatora sesji).
   *Za:* kontrola zostaje nietknięta, a raporty i tak nie potrzebują pełnych
   identyfikatorów — ich wartość dowodowa jest w RELACJI między odczytami, nie
   w konkretnej wartości. *Przeciw:* dyscyplina zależy od człowieka i agenta,
   czyli od tego, co zawodzi najczęściej; nie działa wstecz wobec historii git.

**Co wybrałem tymczasowo i dlaczego:** opcję 1 — najwęższą i najbardziej
odwracalną. Jeden katalog, jedna reguła, pełne uzasadnienie w `.gitleaks.toml`.
Sprawdziłem przy tym pomiarem, że wyjątek nie oślepił skanera (przynęta
w tym samym katalogu → `leaks found: 6`).

**Którą wybrałbym docelowo: 3 z elementem 1.** Skrócone cytaty w raportach
usuwają PRZYCZYNĘ, a nie objaw — i są dobrą praktyką niezależnie od skanera
(pełny identyfikator sesji w dokumencie to sam w sobie drobny wyciek).
Wyjątek per katalog zostawiłbym jako zawór na wypadek historii, której nie da
się już zmienić.

**Co zmieni się w mojej pracy zależnie od odpowiedzi:**
- Przy **2**: dopisuję jeden wzorzec i przestaję o tym myśleć.
- Przy **3**: przechodzę przez raporty rundy 6 i skracam cytowane identyfikatory,
  a wyjątek z `.gitleaks.toml` **usuwam** — wtedy historia commita `83775f4`
  znów zapali skan i trzeba będzie rozstrzygnąć, czy przepisujemy historię
  gałęzi roboczej (czego w nocy świadomie nie zrobiłem).
- Przy **1** (czyli podtrzymaniu mojego wyboru): nie robię nic, ale dopisuję
  do `WYTYCZNE-PRACY.md` przypomnienie, żeby każdy nowy katalog raportowy
  dostawał własny wpis razem z pierwszym commitem.
