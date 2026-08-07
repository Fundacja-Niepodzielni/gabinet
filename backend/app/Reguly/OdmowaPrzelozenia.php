<?php

declare(strict_types=1);

namespace App\Reguly;

/**
 * Powód odmowy przełożenia terminu.
 *
 * Powód jest częścią odpowiedzi, nie tylko logu: makieta pokazuje pacjentowi
 * dwa RÓŻNE komunikaty — „minął czas na bezpłatną zmianę" i „tę wizytę
 * przekładano już 2 razy z dozwolonych 2". Enum pilnuje, żeby nie zlały się
 * w jedno „nie można".
 */
enum OdmowaPrzelozenia: string
{
    case PozaOknem = 'poza_oknem';
    case WyczerpanyLimit = 'wyczerpany_limit';

    /**
     * Komunikat dla pacjenta.
     *
     * Przy braku możliwości przycisk ZNIKA z ekranu, a nie jest wyszarzany —
     * „wyszarzony przycisk kusi, żeby go klikać i szukać sposobu obejścia,
     * a potem generuje zgłoszenia »nie działa mi«" (spec, powtórzone 5×).
     * Ten tekst jest wyjaśnieniem obok, nie etykietą martwego przycisku.
     */
    public function komunikat(): string
    {
        return match ($this) {
            self::PozaOknem => 'Minął czas na bezpłatną zmianę terminu.',
            self::WyczerpanyLimit => 'Tę wizytę przekładano już maksymalną liczbę razy. Kolejna zmiana wymaga kontaktu ze specjalistą.',
        };
    }
}
