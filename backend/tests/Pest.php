<?php

declare(strict_types=1);

use Tests\TestCase;

/*
|---------------------------------------------------------------------------
| Konfiguracja Pest
|---------------------------------------------------------------------------
| Zasada z WYTYCZNE-PRACY.md §3: dla każdego zachowania test pozytywny
| I negatywny. Testy liczą wartości, nie obecność elementów.
*/

pest()->extend(TestCase::class)->in('Feature');
