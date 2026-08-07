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
