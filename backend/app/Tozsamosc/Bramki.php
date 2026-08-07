<?php

declare(strict_types=1);

namespace App\Tozsamosc;

use App\Wsparcie\Typy;

/**
 * Mapowanie ról z IdP na uprawnienia Gabinetu.
 *
 * Realizuje zasadę kontraktu: „IdP mówi kim jesteś, aplikacja decyduje co
 * możesz". Dwie reguły, które łatwo złamać i których pilnują testy:
 *
 *   1. Rola przychodzi WYŁĄCZNIE z `realm_access.roles` w ACCESS TOKENIE.
 *      Ani ID token, ani `userinfo` nie zawierają tego claimu (kontrakt §2b) —
 *      biblioteka budująca sesję z ID tokenu nie zobaczy żadnej roli.
 *   2. PUSTA lista ról to POPRAWNY stan konta, nie błąd logowania.
 */
final class Bramki
{
    /**
     * @param  array<string, mixed>  $claims
     * @return list<string>
     */
    public static function roleZAccessTokenu(array $claims): array
    {
        $realmAccess = $claims['realm_access'] ?? [];

        if (! is_array($realmAccess)) {
            return [];
        }

        return Typy::listaNapisow($realmAccess['roles'] ?? null);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function mapa(): array
    {
        $wynik = [];

        foreach (Typy::mapa(config('konta.bramki')) as $bramka => $role) {
            $wynik[$bramka] = Typy::listaNapisow($role);
        }

        return $wynik;
    }

    /**
     * Pełna mapa bramek dla danego zestawu ról.
     *
     * @param  list<string>  $role
     * @return array<string, bool>
     */
    public static function dlaRol(array $role): array
    {
        $wynik = [];

        foreach (self::mapa() as $bramka => $dozwolone) {
            $wynik[$bramka] = array_intersect($dozwolone, $role) !== [];
        }

        return $wynik;
    }

    /**
     * @param  list<string>  $role
     */
    public static function pozwala(array $role, string $bramka): bool
    {
        $mapa = self::mapa();

        // Nieznana bramka to ZAWSZE odmowa. Literówka w nazwie nie może
        // przypadkiem otwierać zasobu.
        if (! array_key_exists($bramka, $mapa)) {
            return false;
        }

        return array_intersect($mapa[$bramka], $role) !== [];
    }
}
