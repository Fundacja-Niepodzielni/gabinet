<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|---------------------------------------------------------------------------
| Konfiguracja Pest
|---------------------------------------------------------------------------
| Zasada z WYTYCZNE-PRACY.md §3: dla każdego zachowania test pozytywny
| I negatywny. Testy liczą WARTOŚCI i pytają o STAN, nie o obecność elementu
| ani o zawartość konfiguracji (CLAUDE.md §15, dziennik makiety rozdz. 15).
|
| `RefreshDatabase` w suicie Feature jest tu ŚWIADOMY, mimo kosztu: bez niego
| testy schematu (np. „w bazie nie ma kolumny hasła") sprawdzałyby bazę
| w nieznanym stanie, a to znaczy: nie sprawdzałyby niczego.
*/

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
