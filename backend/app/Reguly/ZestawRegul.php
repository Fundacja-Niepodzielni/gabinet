<?php

declare(strict_types=1);

namespace App\Reguly;

use App\Wsparcie\Typy;

/**
 * Niezmienny zrzut reguł systemu — dokładnie to, co trafia do
 * `rezerwacja.regula_anulacji_zamrozona` w chwili zakupu.
 *
 * CLAUDE.md §4: zamrażamy **pełny zrzut, nie referencję**. Referencja do wersji
 * wygląda oszczędniej, ale przy odczycie zwrotu sprzed roku trzeba by mieć
 * pewność, że tamten wiersz konfiguracji nadal istnieje i nadal znaczy to samo.
 * Zrzut jest samowystarczalny — i to jest cała jego wartość.
 *
 * Obiekt jest CELOWO głupi: przechowuje wartości i nic nie rozstrzyga.
 * Rozstrzyga `OcenaAnulacji`, jedna dla całego systemu.
 */
final readonly class ZestawRegul
{
    /**
     * @param  array<string, array{zwrot_procent: int, termin_wraca: bool, godzina_platna: bool}>  $macierzOdwolan
     */
    private function __construct(
        public int $wersja,
        /** Okno bezpłatnego odwołania i zmiany, w godzinach przed wizytą. */
        public int $oknoBezplatnegoOdwolaniaGodzin,
        /** Ile razy wolno przełożyć JEDNĄ rezerwację. */
        public int $limitPrzelozen,
        /** Najbliższy możliwy termin, w godzinach od teraz. */
        public int $najblizszyTerminGodzin,
        /** Ile dni w przód widzi pacjent. */
        public int $kalendarzPacjentaDni,
        /** Ile dni w przód specjalista może wystawiać terminy. */
        public int $horyzontWystawianiaDni,
        /** Przerwa między wizytami, w minutach. */
        public int $przerwaMiedzyWizytamiMinut,
        /** Ile minut trzymamy termin w koszyku. */
        public int $blokadaKoszykaMinut,
        /** Ile dni ważny jest link do płatności wysłany mailem. */
        public int $waznoscLinkuPlatnosciDni,
        /** Limit wizyt niskopłatnych na pacjenta (WIZYT, nie godzin). */
        public int $limitNiskoplatnychWizyt,
        /** Limit podażowy: ile terminów niskopłatnych tygodniowo na specjalistę. */
        public int $limitNiskoplatnychNaTydzien,
        /** Czy działa kredyt za odsprzedany termin. */
        public bool $kredytZaOdsprzedanyTermin,
        /** Po ilu godzinach nieoznaczona wizyta domyka się jako odbyta. */
        public int $autoDomkniecieGodzin,
        /** Macierz odwołań — patrz `Sytuacja`. */
        public array $macierzOdwolan,
    ) {}

    /**
     * Wersja zerowa — wartości z dnia startu systemu.
     *
     * Źródła każdej liczby (żeby nikt nie musiał ich szukać po nowu):
     * okno 24 h, limit przełożeń 2, najbliższy termin 2 h, kalendarz 30 dni,
     * wystawianie 7 dni, przerwa 10 min — „Jak działa system", tabela reguł
     * twardych. Blokada koszyka 10 min i link płatności 2 dni — decyzja
     * właściciela z 07.08 (wartość „24 h" z makiety usunięta jako sprzeczna).
     * Limit niskopłatnych **10 WIZYT** — D-2026-08-07-08; wiersze mówiące
     * „4 h na osobę" to niedoczyszczony ślad sprzed podniesienia limitu.
     * Limit podażowy 4 terminy/tydzień — dziennik makiety, rozdz. 24.
     */
    public static function wersjaZerowa(): self
    {
        return new self(
            wersja: 1,
            oknoBezplatnegoOdwolaniaGodzin: 24,
            limitPrzelozen: 2,
            najblizszyTerminGodzin: 2,
            kalendarzPacjentaDni: 30,
            horyzontWystawianiaDni: 7,
            przerwaMiedzyWizytamiMinut: 10,
            blokadaKoszykaMinut: 10,
            waznoscLinkuPlatnosciDni: 2,
            limitNiskoplatnychWizyt: 10,
            limitNiskoplatnychNaTydzien: 4,
            kredytZaOdsprzedanyTermin: true,
            autoDomkniecieGodzin: 48,
            macierzOdwolan: Sytuacja::macierzDomyslna(),
        );
    }

    /**
     * @param  array<string, mixed>  $dane
     */
    public static function zTablicy(array $dane): self
    {
        $domyslne = self::wersjaZerowa();

        /** @var array<string, array{zwrot_procent: int, termin_wraca: bool, godzina_platna: bool}> $macierz */
        $macierz = [];

        foreach (Typy::mapa($dane['macierz_odwolan'] ?? null) as $sytuacja => $skutek) {
            $skutek = Typy::mapa($skutek);
            $macierz[$sytuacja] = [
                'zwrot_procent' => Typy::liczba($skutek['zwrot_procent'] ?? null),
                'termin_wraca' => Typy::prawda($skutek['termin_wraca'] ?? null),
                'godzina_platna' => Typy::prawda($skutek['godzina_platna'] ?? null),
            ];
        }

        return new self(
            wersja: Typy::liczba($dane['wersja'] ?? null, $domyslne->wersja),
            oknoBezplatnegoOdwolaniaGodzin: Typy::liczba(
                $dane['okno_bezplatnego_odwolania_godzin'] ?? null,
                $domyslne->oknoBezplatnegoOdwolaniaGodzin
            ),
            limitPrzelozen: Typy::liczba($dane['limit_przelozen'] ?? null, $domyslne->limitPrzelozen),
            najblizszyTerminGodzin: Typy::liczba($dane['najblizszy_termin_godzin'] ?? null, $domyslne->najblizszyTerminGodzin),
            kalendarzPacjentaDni: Typy::liczba($dane['kalendarz_pacjenta_dni'] ?? null, $domyslne->kalendarzPacjentaDni),
            horyzontWystawianiaDni: Typy::liczba($dane['horyzont_wystawiania_dni'] ?? null, $domyslne->horyzontWystawianiaDni),
            przerwaMiedzyWizytamiMinut: Typy::liczba($dane['przerwa_miedzy_wizytami_minut'] ?? null, $domyslne->przerwaMiedzyWizytamiMinut),
            blokadaKoszykaMinut: Typy::liczba($dane['blokada_koszyka_minut'] ?? null, $domyslne->blokadaKoszykaMinut),
            waznoscLinkuPlatnosciDni: Typy::liczba($dane['waznosc_linku_platnosci_dni'] ?? null, $domyslne->waznoscLinkuPlatnosciDni),
            limitNiskoplatnychWizyt: Typy::liczba($dane['limit_niskoplatnych_wizyt'] ?? null, $domyslne->limitNiskoplatnychWizyt),
            limitNiskoplatnychNaTydzien: Typy::liczba($dane['limit_niskoplatnych_na_tydzien'] ?? null, $domyslne->limitNiskoplatnychNaTydzien),
            kredytZaOdsprzedanyTermin: Typy::prawda($dane['kredyt_za_odsprzedany_termin'] ?? null, $domyslne->kredytZaOdsprzedanyTermin),
            autoDomkniecieGodzin: Typy::liczba($dane['auto_domkniecie_godzin'] ?? null, $domyslne->autoDomkniecieGodzin),
            macierzOdwolan: $macierz !== [] ? $macierz : $domyslne->macierzOdwolan,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function doTablicy(): array
    {
        return [
            'wersja' => $this->wersja,
            'okno_bezplatnego_odwolania_godzin' => $this->oknoBezplatnegoOdwolaniaGodzin,
            'limit_przelozen' => $this->limitPrzelozen,
            'najblizszy_termin_godzin' => $this->najblizszyTerminGodzin,
            'kalendarz_pacjenta_dni' => $this->kalendarzPacjentaDni,
            'horyzont_wystawiania_dni' => $this->horyzontWystawianiaDni,
            'przerwa_miedzy_wizytami_minut' => $this->przerwaMiedzyWizytamiMinut,
            'blokada_koszyka_minut' => $this->blokadaKoszykaMinut,
            'waznosc_linku_platnosci_dni' => $this->waznoscLinkuPlatnosciDni,
            'limit_niskoplatnych_wizyt' => $this->limitNiskoplatnychWizyt,
            'limit_niskoplatnych_na_tydzien' => $this->limitNiskoplatnychNaTydzien,
            'kredyt_za_odsprzedany_termin' => $this->kredytZaOdsprzedanyTermin,
            'auto_domkniecie_godzin' => $this->autoDomkniecieGodzin,
            'macierz_odwolan' => $this->macierzOdwolan,
        ];
    }
}
