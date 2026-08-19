# ZLECENIE-079 · 19.08.2026 · OD architekta DO sesji KOD-F1 — rozstrzygnięcia + §7

**Cykl przyjęty w całości.** Ściana typu zbudowana tak, jak zlecałem — z dwoma ruchami
lepszymi niż mój plan: usunięcie `zPodmienionymi` zamiast jego pilnowania (metoda, której
nie ma, nie potrzebuje kontroli) i przeniesienie mapowania roszczeń do fasady (tablicę
w kontrolerze wolno wypełnić czymkolwiek). Odnotowuję też trzy rzeczy, które wykonałeś
bez polecenia, a które chcę mieć w standardzie: pomiar powierzchni **przed** robotą,
odrzucenie własnej pierwszej wersji, bo osłabiała inną kontrolę („naprawa, która psuje
inną kontrolę, nie jest naprawą"), i ręczne sprawdzenie plików kanału przed commitem.

## 1. §7 — SZÓSTE PIĘTRO: NAPRAWIAMY. Zakres rozstrzygnięty

Twoje pytanie było właściwe i odpowiedź brzmi **tak, w pełnym zakresie**. Powód: to jest
ta sama klasa, tylko przeniesiona z „skąd pochodzi WARTOŚĆ" na **„skąd pochodzi PODSTAWA
ZAUFANIA"** — a ściana typu, która przyjmuje materiał klucza od wołającego, chroni
kształt, nie prawdę. Obiekt nazywa się „zweryfikowane"; ma być zweryfikowane wobec
**naszego** IdP, nie dowolnego.

**Zakres zatwierdzony:** `zTokenu` przestaje przyjmować wymagania od wołającego i bierze
je z konfiguracji (`KontaOidc`); zmiana obejmuje kontrakt walidatora (albo nowe wejście
obok), `LogowanieController::powrot` (2 miejsca), `OdswiezanieSesji::przelicz` oraz
pomocniki testowe. **Utrudnienie testów przyjmuję świadomie** — jeżeli obiekt roszczeń
da się w teście zdobyć wyłącznie pełną ścieżką z podstawionym IdP, to jest cecha, nie
koszt: dokładnie to znaczy „niepodrabialny".

Kontrole odbioru:
1. **negatywna**: mechanizm podający własny materiał klucza **nie kompiluje się /
   nie przechodzi statyki / rzuca** — nie „zapala kontrolę";
2. **pozytywna**: legalna ścieżka logowania i odświeżania działa (bez tego §1 spełnia
   też system, w którym nikt się nie zaloguje);
3. **przyrządu**: podstawiony IdP w teście musi dawać tożsamość — inaczej „nie da się
   zalogować" udawałoby bezpieczeństwo;
4. **krok dalej**: po zamknięciu źródła wymagań wskaż, co jeszcze rozstrzyga o zaufaniu
   (adres wystawcy? zegar? pamięć podręczna kluczy?) — pokryj albo nazwij jako zmierzoną
   granicę.

## 2. `docs/DECYZJE.md` — rozszerzam Twój zakres (to była MOJA niespójność)

Strażnik zadziałał poprawnie, a wada jest moja: `ODPOWIEDZ-051` §2 uczyniła Cię
właścicielem zapisu tego pliku, ale nikt nie dopisał go do Twojego `.zakres-sesji`
(wytknął to również audyt spójności). **Dopisz `docs/DECYZJE.md` do swojego zakresu**
i zacommituj oba wpisy: swój `D-2026-08-19-01` oraz oczekujący `D-2026-08-12-04`
sesji SPEC-UMOWA — w komunikacie commita zaznacz, że drugi wpis jest jej autorstwa
i przenosisz go, nie tworzysz. Nie obchodź strażnika; zmień deklarację, bo to
deklaracja była nieprawdziwa.

## 3. Twoje trzy twierdzenia z §10 — wystawione do obalenia, przekazuję rundzie

Bez zmian, z moim komentarzem dla weryfikatora: `?string $refreshToken` (twierdzisz, że
nie rozstrzyga o tożsamości), `wszystkie()` wydające tablicę roszczeń (twierdzisz, że to
wynik weryfikacji, nie droga jej ominięcia), oraz kontrola „jedyne `new`" liczona
w jednym pliku (klasa `final`, ale niemierzone osobno). **Zmierz to trzecie sam przy
okazji naprawy §7** — `final` bez pomiaru jest deklaracją.

## 4. Wady własne §6 — przyjęte, jedna do standardu

„Kontrola rozpoznawała JEDEN KSZTAŁT przedmiotu zamiast pytać o jego istotę" to najlepsze
sformułowanie tej klasy, jakie w tej serii padło — wchodzi do lekcji F1 dosłownie.
Wada (3) — **potok maskujący kod wyjścia bramki** — jest groźniejsza, niż wygląda:
przyrząd raportujący sukces cudzego procesu. Twoja zmiana (wyjście do pliku, kod odczytany
wprost) obowiązuje odtąd wszystkich; dopisuję do standardów.

## 5. Procedura

Naprawa §7 + §2 → bramka OD ZERA **bez potoku** + pełny zestaw perturbacji → commit →
bramka po commicie → nowe zamrożone SHA → **meldunek ODPOWIEDZ-079**. Po nim runda 12.

**Jedno uprzedzenie, żebyś znał moje kryterium:** jeżeli runda 12 znajdzie SIÓDME piętro
w tym samym obszarze, nie zlecam ósmej naprawy w tym trybie — przedstawiam właścicielowi
wybór między dalszym pogłębianiem a zamknięciem F1 z jawnie opisaną granicą. Twoja praca
jest bez zarzutu; to kwestia tego, gdzie kończy się rozsądny zakres fazy fundamentowej.
