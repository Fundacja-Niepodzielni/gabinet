<?php

declare(strict_types=1);

namespace App\Reguly;

/**
 * Osiem sytuacji z macierzy odwołań — „serce systemu" (dziennik makiety, rozdz. 3).
 *
 * Macierz jest DANYMI, nie kodem: ekran koordynatora `/koordynacja/reguly`
 * czyta ją z tego samego źródła, z którego liczy system (spec s. 47). Ten enum
 * nazywa wyłącznie sytuacje; skutki (zwrot, powrót terminu, płatność godziny)
 * przychodzą z zamrożonego zestawu reguł.
 *
 * Świadomie NIE ma tu progów pośrednich ani opłat częściowych: „każdy dodatkowy
 * próg to kolejne pytanie do koordynatora" (spec s. 7).
 */
enum Sytuacja: string
{
    case PacjentOdwolujeWczesniej = 'pacjent_odwoluje_wczesniej';
    case PacjentOdwolujePozniej = 'pacjent_odwoluje_pozniej';
    case PacjentNiePrzyszedl = 'pacjent_nie_przyszedl';
    case SpecjalistaOdwoluje = 'specjalista_odwoluje';
    case KoordynatorOdwoluje = 'koordynator_odwoluje';
    case OdpuscTymRazem = 'odpusc_tym_razem';
    case BrakPlatnosci = 'brak_platnosci';
    case RezygnacjaZGrupy = 'rezygnacja_z_grupy';

    public function opis(): string
    {
        return match ($this) {
            self::PacjentOdwolujeWczesniej => 'Pacjent odwołuje przed granicą bezpłatnego odwołania',
            self::PacjentOdwolujePozniej => 'Pacjent odwołuje po granicy',
            self::PacjentNiePrzyszedl => 'Pacjent nie przyszedł bez uprzedzenia',
            self::SpecjalistaOdwoluje => 'Specjalista odwołuje — o dowolnej porze',
            self::KoordynatorOdwoluje => 'Koordynator odwołuje — o dowolnej porze',
            self::OdpuscTymRazem => 'Specjalista odpuszcza nieobecność („odpuść tym razem")',
            self::BrakPlatnosci => 'Płatność nie doszła w oknie blokady',
            self::RezygnacjaZGrupy => 'Rezygnacja z grupy lub warsztatu przed zamknięciem zapisów',
        };
    }

    /**
     * Macierz domyślna — wersja zerowa konfiguracji.
     *
     * Dwie asymetrie, które wyglądają na błąd, a są celowe:
     *
     *  1. **Późne odwołanie: brak zwrotu, ale termin WRACA do puli.** Specjalista
     *     i tak ma godzinę opłaconą; termin wraca, bo „lepiej, żeby godzina się
     *     odbyła, niż stała pusta" (dziennik makiety, rozdz. 3). Jeśli ktoś ją
     *     wykupi, pierwszy pacjent dostaje kredyt — różnicę pokrywa fundacja.
     *  2. **Nieobecność: termin NIE wraca.** Bo już minął. To jedyna sytuacja,
     *     w której godzina jest płatna, a termin nie wraca.
     *
     * @return array<string, array{zwrot_procent: int, termin_wraca: bool, godzina_platna: bool}>
     */
    public static function macierzDomyslna(): array
    {
        return [
            self::PacjentOdwolujeWczesniej->value => ['zwrot_procent' => 100, 'termin_wraca' => true, 'godzina_platna' => false],
            self::PacjentOdwolujePozniej->value => ['zwrot_procent' => 0, 'termin_wraca' => true, 'godzina_platna' => true],
            self::PacjentNiePrzyszedl->value => ['zwrot_procent' => 0, 'termin_wraca' => false, 'godzina_platna' => true],
            self::SpecjalistaOdwoluje->value => ['zwrot_procent' => 100, 'termin_wraca' => true, 'godzina_platna' => false],
            self::KoordynatorOdwoluje->value => ['zwrot_procent' => 100, 'termin_wraca' => true, 'godzina_platna' => false],
            self::OdpuscTymRazem->value => ['zwrot_procent' => 100, 'termin_wraca' => false, 'godzina_platna' => false],
            self::BrakPlatnosci->value => ['zwrot_procent' => 0, 'termin_wraca' => true, 'godzina_platna' => false],
            self::RezygnacjaZGrupy->value => ['zwrot_procent' => 100, 'termin_wraca' => true, 'godzina_platna' => false],
        ];
    }
}
