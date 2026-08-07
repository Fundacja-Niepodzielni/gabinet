<?php

declare(strict_types=1);

namespace App\Wsparcie;

/**
 * Jedna funkcja rozstrzygająca, czy wiadomość może opuścić to środowisko.
 *
 * Wołają ją wszystkie kanały (poczta w F6, SMS w F6) — żaden moduł nie
 * podejmuje tej decyzji sam. Wzorzec wymagany przez CLAUDE.md §1
 * („jedna funkcja rozstrzygająca na regułę, wołana przez wszystkie moduły").
 */
final class BlokadaWysylki
{
    /**
     * Sterowniki poczty, które NICZEGO nie wysyłają na zewnątrz.
     *
     * @var list<string>
     */
    public const array STEROWNIKI_NIEWYSYLAJACE = ['log', 'array'];

    /**
     * Zwraca sterownik poczty, którego wolno użyć w tym środowisku.
     *
     * Przy włączonej blokadzie każdy sterownik realnie wysyłający jest
     * zamieniany na `log`. Sterowniki, które i tak nic nie wysyłają,
     * zostają bez zmian — inaczej `Mail::fake()` w testach przestałby
     * cokolwiek znaczyć.
     */
    public static function sterownikPoczty(string $skonfigurowany, bool $blokadaWlaczona): string
    {
        if (! $blokadaWlaczona) {
            return $skonfigurowany;
        }

        if (in_array($skonfigurowany, self::STEROWNIKI_NIEWYSYLAJACE, true)) {
            return $skonfigurowany;
        }

        return 'log';
    }

    /**
     * Czy wolno wysłać SMS-a? Bramka SMSAPI (F6) pyta o to przed każdą wysyłką.
     */
    public static function wolnoWyslacSms(bool $blokadaWlaczona): bool
    {
        return ! $blokadaWlaczona;
    }
}
