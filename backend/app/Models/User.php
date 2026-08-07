<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Konto lokalne — odwzorowanie konta z Kont Niepodzielni.
 *
 * NIE dziedziczy po `Authenticatable` i nie zna pojęcia hasła: uwierzytelnia
 * IdP, a Gabinet trzyma tylko wynik (CLAUDE.md §2). Wiązanie po `keycloak_sub`,
 * nigdy po e-mailu — zmiana adresu w IdP nie może przejąć cudzego rekordu.
 */
#[Fillable(['keycloak_sub', 'email', 'email_potwierdzony', 'nazwa_wyswietlana'])]
class User extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_potwierdzony' => 'boolean',
            'ostatnie_logowanie_at' => 'datetime',
        ];
    }
}
