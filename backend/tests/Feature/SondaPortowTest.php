<?php

declare(strict_types=1);

/**
 * `R6B-11`: SONDA MUSI BYĆ DOPASOWANA DO PROTOKOŁU.
 *
 * Kontrola „nic nie wystawione publicznie" pytała HTTP-em o KAŻDY port, w tym
 * o Postgresa i Redisa. Zmierzone na loopbacku, bez wystawiania czegokolwiek:
 *
 *   Postgres NASŁUCHUJĄCY na 127.0.0.1:55442
 *     sonda HTTP (curl)     → NIE WYKRYŁA
 *     próba połączenia TCP  → WYKRYŁA
 *     kontrola pozytywna: TCP na martwym porcie 59999 → nie połączyła
 *
 * Baza nie mówi po HTTP, więc pytanie HTTP-em o bazę odpowiada na inne pytanie,
 * niż zadajemy. Kontrola była zielona także przy WYSTAWIONEJ BAZIE DANYCH.
 *
 * Ta kontrola pilnuje, żeby sonda nie wróciła do HTTP-a — bo regresja tutaj
 * znaczy wystawioną bazę, a nie brzydki kod.
 */
it('bramka sonduje porty POŁĄCZENIEM TCP, nie zapytaniem HTTP', function (): void {
    $sciezka = base_path('../skrypty/bramka.sh');
    expect(file_exists($sciezka))->toBeTrue('Nie widzę bramki — kontrola mierzyłaby pustkę.');

    // KOMENTARZE ODFILTROWANE — nagłówek sondy CYTUJE starą wersję („sonda HTTP
    // (curl) → NIE WYKRYŁA"), więc kontrola tekstowa bez filtra widziałaby cytat
    // i nie odróżniła go od kodu. Moja własna lekcja z tego samego dnia.
    $tresc = (string) file_get_contents($sciezka);
    $kod = (string) preg_replace('~^\s*#.*$~m', '', $tresc);

    expect(str_contains($kod, '/dev/tcp/'))->toBeTrue(
        'Sonda portów nie używa połączenia TCP. Zapytanie HTTP nie wykryje nasłuchującej '.
        'bazy danych — kontrola „nic nie wystawione" byłaby zielona przy wystawionym Postgresie.'
    );

    // Sonda ma rozróżniać TRZY światy, nie dwa: „nie wiem" nie jest „nie słucha".
    foreach (['OSIAGALNY', 'ODMOWA', 'NIEZNANY'] as $swiat) {
        expect(str_contains($kod, $swiat))->toBeTrue(
            "Sonda nie rozróżnia świata `{$swiat}` — bez niego cisza jest nieodróżnialna od odmowy."
        );
    }
});

it('brak adresu spoza loopbacku NIE JEST po cichu pomijany', function (): void {
    $kod = (string) preg_replace(
        '~^\s*#.*$~m',
        '',
        (string) file_get_contents(base_path('../skrypty/bramka.sh'))
    );

    // Poprzednia wersja wypisywała „pominięte" i szła dalej — brak pomiaru
    // wyglądał identycznie jak pomiar udany. Kontrola bezpieczeństwa, której
    // NIE WYKONANO, nie jest kontrolą zdaną.
    expect(str_contains($kod, 'pominięte: nie ustalono adresu'))->toBeFalse(
        'Bramka nadal po cichu pomija aktywną kontrolę portów, gdy nie zna adresu.'
    );

    expect(str_contains($kod, 'NIEROZSTRZYGNIĘTE: nie ustalono adresu spoza loopbacku'))->toBeTrue(
        'Brak adresu nie jest raportowany jako NIEROZSTRZYGNIĘTE.'
    );
});

it('KIERUNEK ODWROTNY: skaner widzi brak sondy TCP — na materiale zbudowanym pod rękę', function (): void {
    // Bez tego „wszystko w porządku" przechodzi także wtedy, gdy wzorzec się
    // rozjechał i nie łapie niczego.
    $zSonda = "\tif timeout 3 bash -c \"echo > /dev/tcp/\${adres}/\${port}\"; then";
    $bezSondy = "\tif curl -s --max-time 3 \"http://\${ADRES}:\${PORT}/\"; then";
    $tylkoWKomentarzu = '# dawniej: /dev/tcp/ — juz nie';

    expect(str_contains($zSonda, '/dev/tcp/'))->toBeTrue('Skaner nie widzi sondy TCP.');
    expect(str_contains($bezSondy, '/dev/tcp/'))->toBeFalse('Skaner widzi sondę TCP tam, gdzie jej nie ma.');

    $poFiltrze = (string) preg_replace('~^\s*#.*$~m', '', $tylkoWKomentarzu);
    expect(str_contains($poFiltrze, '/dev/tcp/'))->toBeFalse(
        'Filtr komentarzy nie działa — cytat starej sondy uchodziłby za kod.'
    );
});
