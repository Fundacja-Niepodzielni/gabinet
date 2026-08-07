<?php

declare(strict_types=1);

return [

    /*
    |-----------------------------------------------------------------------
    | Strefa prezentacji
    |-----------------------------------------------------------------------
    | W bazie ZAWSZE UTC. Ta strefa dotyczy wyłącznie tego, co widzi człowiek:
    | panel, mail, SMS, plik .ics. Okna regulaminowe (24 h przed wizytą, cisza
    | nocna 21:00–8:00) liczymy w tej strefie niezależnie od strefy pacjenta
    | (CLAUDE.md §5, spec M5/17).
    */
    'strefa_prezentacji' => env('GABINET_STREFA_PREZENTACJI', 'Europe/Warsaw'),

    /*
    |-----------------------------------------------------------------------
    | Twarda blokada wysyłki mail/SMS
    |-----------------------------------------------------------------------
    | CLAUDE.md §10 i spec M5/18: ze środowisk nieprodukcyjnych nie wychodzi
    | ani jeden mail i ani jeden SMS. To nie jest ustawienie wygody — test
    | przypomnień wysłany na prawdziwe adresy pacjentów jest incydentem RODO.
    */
    'blokada_wysylki' => filter_var(env('GABINET_BLOKADA_WYSYLKI', true), FILTER_VALIDATE_BOOLEAN),

];
