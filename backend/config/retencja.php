<?php

declare(strict_types=1);

/*
|---------------------------------------------------------------------------
| OKRESY RETENCJI
|---------------------------------------------------------------------------
| Osobno od rejestru (`App\Retencja\RejestrRetencji`), bo to są dwie różne
| rzeczy o dwóch różnych właścicielach:
|
|   · REJESTR mówi, PO CZYM liczymy termin i JAK rekord znika — to decyzja
|     techniczna i mieszka w kodzie;
|   · OKRES mówi, PO ILU DNIACH — to decyzja PRAWNA i podejmuje ją IOD w DPIA,
|     nie programista przy pisaniu sprzątaczki.
|
| `null` znaczy NIEUSTALONY. Nie znaczy „zero" i nie znaczy „bez ograniczeń".
| Zadanie czyszczące ODMAWIA dotknięcia tabeli o nieustalonym okresie i mówi
| o tym głośno — bo „pominięte po cichu" i „pominięte, bo nikt nie ustalił
| okresu" wyglądają w logu identycznie, a znaczą co innego.
|
| Wpisanie tu wartości domyślnej byłoby podjęciem decyzji prawnej przez
| przeoczenie — i to takiej, która KASUJE dane osobowe.
|
| CLAUDE.md zasada 10 (RODO art. 9) i D-2026-08-07-20.
*/

return [

    'okresy_dni' => [
        // Dane o zdrowiu — RODO art. 9. Okres wskaże IOD (P-3 w DPIA).
        'pacjenci' => null,

        // Znacznik unieważnienia sesji SSO. Nie są to dane osobowe (skrót sid),
        // więc okres jest decyzją techniczną, nie prawną — ale zadanie i tak go
        // dziś nie wykona, patrz UWAGA niżej.
        'uniewaznione_sesje' => null,

        // Dowód zgody — okres zależy od terminu przedawnienia roszczeń. IOD.
        'zgody' => null,

        // Dokument księgowy, 5 lat od końca roku podatkowego — ale ta tabela
        // NIE jest kasowana (anonimizacja pacjenta), więc okres jej nie dotyczy.
        'rezerwacje' => null,

        'zdarzenia_rezerwacji' => null,
        'users' => null,
        'specjalisci' => null,
    ],

];

/*
| UWAGA ZMIERZONA 09.08 (runda 2, przy podłączaniu wywołującego):
|
| `uniewaznione_sesje` NIE DA SIĘ dziś posprzątać nawet po ustaleniu okresu.
| `ZadanieRetencji` selekcjonuje i weryfikuje przez `pluck('id')`, a ta tabela
| ma klucz `sid_skrot` (string) i kolumny `id` NIE MA. Zadanie wywróciłoby się
| na nieistniejącej kolumnie.
|
| To jest znalezisko z próby podłączenia, nie z odczytu kodu: dopóki nikt
| zadania nie wołał, założenie „każda tabela ma całkowite `id`" nie miało jak
| się ujawnić. Naprawa (klucz jako parametr) dotyka typów `Wynik` i istniejących
| testów, więc jest osobną pozycją, nie doklejką do R6A-11.
*/
