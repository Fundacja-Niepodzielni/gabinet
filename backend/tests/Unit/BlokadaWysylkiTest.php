<?php

declare(strict_types=1);

use App\Wsparcie\BlokadaWysylki;

// POZYTYW: przy włączonej blokadzie żaden realnie wysyłający sterownik nie przechodzi.
it('podmienia wysyłający sterownik poczty na log, gdy blokada jest włączona', function (string $sterownik): void {
    expect(BlokadaWysylki::sterownikPoczty($sterownik, blokadaWlaczona: true))->toBe('log');
})->with(['smtp', 'ses', 'postmark', 'resend', 'sendmail', 'failover', 'roundrobin']);

// POZYTYW: sterowniki, które i tak nic nie wysyłają, zostają nietknięte —
// inaczej `Mail::fake()` i asercje na treści maili przestałyby cokolwiek znaczyć.
it('zostawia sterowniki niewysyłające bez zmian', function (string $sterownik): void {
    expect(BlokadaWysylki::sterownikPoczty($sterownik, blokadaWlaczona: true))->toBe($sterownik);
})->with(BlokadaWysylki::STEROWNIKI_NIEWYSYLAJACE);

// NEGATYW: po zdjęciu blokady (produkcja) reguła nie może niczego podmieniać.
it('nie rusza konfiguracji, gdy blokada jest zdjęta', function (): void {
    expect(BlokadaWysylki::sterownikPoczty('smtp', blokadaWlaczona: false))->toBe('smtp');
});

// SMS nie ma odpowiednika „log" — jedyną odpowiedzią jest tak/nie.
it('zabrania wysyłki SMS przy włączonej blokadzie i pozwala po jej zdjęciu', function (): void {
    expect(BlokadaWysylki::wolnoWyslacSms(blokadaWlaczona: true))->toBeFalse()
        ->and(BlokadaWysylki::wolnoWyslacSms(blokadaWlaczona: false))->toBeTrue();
});
