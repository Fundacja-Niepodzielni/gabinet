<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

/*
|---------------------------------------------------------------------------
| Zadania okresowe
|---------------------------------------------------------------------------
| Pełna lista zadań (przypomnienia 24 h i 2 h, awanse z listy rezerwowej,
| wygaszanie blokad koszyka, domykanie wizyt po 48 h, wygaszanie linków
| płatności, zamykanie okresu rozliczeniowego) dochodzi w F3–F6 — spec M6/4.
|
| Dziś jest tu jedno zadanie: puls. Bez niego nie da się odróżnić „harmonogram
| pracuje" od „proces stoi, ale pętla umarła" — a to jest najczęstszy, cichy
| tryb awarii zadań okresowych.
*/

Schedule::command('gabinet:puls')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

/*
| RETENCJA — dopisana 09.08 w rundzie 2, pozycja R6A-11.
|
| Do tego dnia `ZadanieRetencji` NIE MIAŁO ANI JEDNEGO WYWOŁUJĄCEGO: klasa
| istniała, testy kasowania świeciły zielono, a na żywym systemie nic się nie
| kasowało. Kontrole badały KASOWANIE, żadna nie badała URUCHAMIANIA.
|
| Codziennie o 3:10 — poza oknem wizyt, żeby kasowanie nie konkurowało
| o wiersze z ruchem pacjentów. `withoutOverlapping`, bo przebieg dłuższy niż
| doba nakładałby się sam na siebie i liczył te same rekordy dwa razy.
*/
Schedule::command('gabinet:retencja')
    ->dailyAt('03:10')
    ->withoutOverlapping()
    ->runInBackground();
