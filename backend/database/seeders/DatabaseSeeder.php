<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Dane startowe.
 *
 * Domyślny seeder Laravela zakładał użytkownika z hasłem — usunięty razem
 * z całym mechanizmem haseł (CLAUDE.md §2).
 *
 * Właściwy seed powstaje w F1 i ma mieć WIARYGODNE PROPORCJE: 111
 * specjalistów, kilkanaście wizyt na pacjenta. Dziennik makiety, rozdz. 15:
 * czternaście nazwisk na 2758 wizyt dawało ~197 wizyt na osobę, licznik
 * limitu pokazywał „64 z 4" i każdego pacjenta jako wyczerpanego —
 * „ekran działał, tylko nic nie znaczył". Dane bez proporcji nie pokazują
 * reguły, którą mają ilustrować.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        //
    }
}
