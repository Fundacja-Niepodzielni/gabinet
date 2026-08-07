<?php

declare(strict_types=1);

namespace App\Tozsamosc;

use App\Wsparcie\Typy;

/**
 * Mapowanie ról z IdP na uprawnienia Gabinetu.
 *
 * Realizuje zasadę kontraktu: „IdP mówi kim jesteś, aplikacja decyduje co
 * możesz". Trzy reguły, które łatwo złamać i których pilnują testy:
 *
 *   1. Rola przychodzi WYŁĄCZNIE z `realm_access.roles` w ACCESS TOKENIE.
 *      Ani ID token, ani `userinfo` nie zawierają tego claimu (kontrakt §2b).
 *   2. PUSTA lista ról to POPRAWNY stan konta, nie błąd logowania.
 *   3. **Autoryzuje wyłącznie rola z BIAŁEJ LISTY.** Kompozyty rozwijają się
 *      w tokenie: access token koordynatora niesie `koordynator` ORAZ marker
 *      `wymaga-2fa`, obok ról wbudowanych Keycloaka. Logika oparta na
 *      „wszystkich rolach z tokenu" wpuściłaby markery techniczne do uprawnień.
 *      Zmierzone na żywym realmie, nie wydedukowane.
 */
final class Bramki
{
    /**
     * SUROWA lista ról z access tokenu — wszystko, co przyszło.
     *
     * Do inspekcji i do logów. Do decyzji o dostępie służy `roleAutoryzujace()`.
     *
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
     * Role, które w ogóle MOGĄ cokolwiek otworzyć.
     *
     * Filtr przez białą listę z konfiguracji. Wszystko poza nią — markery
     * (`wymaga-2fa`), role wbudowane Keycloaka (`offline_access`,
     * `uma_authorization`, `default-roles-niepodzielni`) i cokolwiek, co
     * pojawi się w realmie w przyszłości — odpada tutaj, zanim dotknie
     * jakiejkolwiek bramki.
     *
     * @param  list<string>  $roleZTokenu
     * @return list<string>
     */
    public static function roleAutoryzujace(array $roleZTokenu): array
    {
        $biala = Typy::listaNapisow(config('konta.role_autoryzujace'));

        return array_values(array_intersect($roleZTokenu, $biala));
    }

    /**
     * Markery techniczne obecne w tokenie. Informacja o koncie, NIE uprawnienie.
     *
     * @param  list<string>  $roleZTokenu
     * @return list<string>
     */
    public static function markery(array $roleZTokenu): array
    {
        $znane = Typy::listaNapisow(config('konta.markery'));

        return array_values(array_intersect($roleZTokenu, $znane));
    }

    /**
     * Czy IdP wymaga od tego konta drugiego składnika.
     *
     * UWAGA na dwie pułapki, obie zmierzone:
     *  - **Direct grant OMIJA 2FA.** Token pobrany `grant_type=password` może
     *    nie mieć markera albo mieć go bez faktycznego przejścia TOTP. Wolno
     *    go używać wyłącznie do inspekcji zawartości tokenu na dev.
     *  - **Z Admin API nie widać obowiązku TOTP** (`requiredActions` bywa puste).
     *    Nie wnioskuj o stanie 2FA konta z Admin API.
     *
     * @param  list<string>  $roleZTokenu
     */
    public static function wymaga2fa(array $roleZTokenu): bool
    {
        return in_array('wymaga-2fa', $roleZTokenu, true);
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
     * Pełna mapa bramek dla ról z tokenu.
     *
     * @param  list<string>  $roleZTokenu
     * @return array<string, bool>
     */
    public static function dlaRol(array $roleZTokenu): array
    {
        $autoryzujace = self::roleAutoryzujace($roleZTokenu);
        $wynik = [];

        foreach (self::mapa() as $bramka => $dozwolone) {
            $wynik[$bramka] = array_intersect($dozwolone, $autoryzujace) !== [];
        }

        return $wynik;
    }

    /**
     * @param  list<string>  $roleZTokenu
     */
    public static function pozwala(array $roleZTokenu, string $bramka): bool
    {
        $mapa = self::mapa();

        // Nieznana bramka to ZAWSZE odmowa. Literówka w nazwie nie może
        // przypadkiem otwierać zasobu.
        if (! array_key_exists($bramka, $mapa)) {
            return false;
        }

        // Filtr przez białą listę wykonuje się TUTAJ, a nie u wywołującego.
        // Gdyby ktoś wpisał marker do mapy bramek (literówka, kopiuj-wklej),
        // i tak nic nie otworzy — bo marker nie przejdzie przez ten filtr.
        return array_intersect($mapa[$bramka], self::roleAutoryzujace($roleZTokenu)) !== [];
    }
}
