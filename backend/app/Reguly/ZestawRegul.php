<?php

declare(strict_types=1);

namespace App\Reguly;

use App\Wsparcie\Typy;
use InvalidArgumentException;

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
     * Pola całkowite zrzutu — nazwa w JSON → nazwa właściwości.
     *
     * Lista istnieje po to, żeby „kompletność" była SPRAWDZALNA, a nie
     * domniemana z liczby argumentów konstruktora.
     */
    private const POLA_CALKOWITE = [
        'wersja' => 'wersja',
        'okno_bezplatnego_odwolania_godzin' => 'oknoBezplatnegoOdwolaniaGodzin',
        'limit_przelozen' => 'limitPrzelozen',
        'najblizszy_termin_godzin' => 'najblizszyTerminGodzin',
        'kalendarz_pacjenta_dni' => 'kalendarzPacjentaDni',
        'horyzont_wystawiania_dni' => 'horyzontWystawianiaDni',
        'przerwa_miedzy_wizytami_minut' => 'przerwaMiedzyWizytamiMinut',
        'blokada_koszyka_minut' => 'blokadaKoszykaMinut',
        'waznosc_linku_platnosci_dni' => 'waznoscLinkuPlatnosciDni',
        'limit_niskoplatnych_wizyt' => 'limitNiskoplatnychWizyt',
        'limit_niskoplatnych_na_tydzien' => 'limitNiskoplatnychNaTydzien',
        'auto_domkniecie_godzin' => 'autoDomkniecieGodzin',
    ];

    /**
     * Liczba całkowita ALBO wyjątek. Bez wartości domyślnej.
     *
     * W-5 z rundy 4: `Typy::liczba()` przyjmuje wyłącznie `int` i napis
     * z samych cyfr, a wszystkiemu innemu podstawia wartość DOMYŚLNĄ. Zapis
     * `"zwrot_procent": 100.0` — całkowicie poprawny JSON — stawał się przez
     * to zerem i przechodził walidację zakresu 0..100 bez protestu.
     * Pacjentowi należnemu 145 zł system wypłacał 0 zł i NIC nie sygnalizował.
     * Zamek `min(zwrot, wpłata)` z definicji tego nie łapie: błąd szedł w dół.
     *
     * Tu żadnej wyrozumiałości: liczba zmiennoprzecinkowa, `null`, napis
     * niebędący liczbą i wartość logiczna to BŁĄD DANYCH, nie zaproszenie
     * do zgadywania.
     */
    private static function calkowita(mixed $wartosc, string $pole): int
    {
        if (is_int($wartosc)) {
            return $wartosc;
        }

        if (is_string($wartosc) && preg_match('/^-?\d+$/', $wartosc) === 1) {
            return (int) $wartosc;
        }

        throw new InvalidArgumentException(sprintf(
            'Pole [%s] musi być liczbą całkowitą, jest %s.',
            $pole,
            get_debug_type($wartosc)
        ));
    }

    private static function logiczna(mixed $wartosc, string $pole): bool
    {
        if (is_bool($wartosc)) {
            return $wartosc;
        }

        throw new InvalidArgumentException(sprintf(
            'Pole [%s] musi być wartością logiczną, jest %s.',
            $pole,
            get_debug_type($wartosc)
        ));
    }

    /**
     * Odtworzenie zestawu reguł z tablicy — ŚCIŚLE, bez wartości domyślnych.
     *
     * W-2 z rundy 4: naprawa U-10 objęła wyłącznie macierz odwołań. Pozostałe
     * TRZYNAŚCIE pól szło przez `Typy::liczba($dane[...] ?? null, $domyslne->...)`,
     * więc brak klucza ALBO zła wartość cicho podstawiały stałą z kodu.
     * Zmierzone przez weryfikatora: ten sam plik zrzutu (identyczne md5) dawał
     * zwrot 14 500 gr albo 0 gr — zależnie wyłącznie od stałej w
     * `wersjaZerowa()`. To wprost naruszenie CLAUDE.md §4: zamrożony zrzut
     * przestawał być samowystarczalny, a zmiana cennika działała WSTECZ.
     *
     * Wartości domyślne mają dokładnie jedno miejsce: `wersjaZerowa()`, czyli
     * wersję pierwszą wstawianą migracją. Nigdzie indziej.
     *
     * @param  array<string, mixed>  $dane
     */
    public static function zTablicy(array $dane): self
    {
        $wymagane = [...array_keys(self::POLA_CALKOWITE), 'kredyt_za_odsprzedany_termin', 'macierz_odwolan'];
        $brakujace = array_values(array_diff($wymagane, array_keys($dane)));

        if ($brakujace !== []) {
            throw new InvalidArgumentException(
                'Zrzut reguł jest niekompletny — brakuje pól: '.implode(', ', $brakujace)
            );
        }

        $liczby = [];

        foreach (array_keys(self::POLA_CALKOWITE) as $klucz) {
            $liczby[$klucz] = self::calkowita($dane[$klucz], $klucz);
        }

        /** @var array<string, array{zwrot_procent: int, termin_wraca: bool, godzina_platna: bool}> $macierz */
        $macierz = [];

        foreach (Typy::mapa($dane['macierz_odwolan']) as $sytuacja => $skutek) {
            $skutek = Typy::mapa($skutek);
            $procent = self::calkowita($skutek['zwrot_procent'] ?? null, "macierz_odwolan.{$sytuacja}.zwrot_procent");

            // U-7 z rundy 3: bez tego warunku zapis `zwrot_procent = 500`
            // dawał ZWROT PIĘĆ RAZY WIĘKSZY niż wpłata (14 500 gr → 72 500 gr).
            if ($procent < 0 || $procent > 100) {
                throw new InvalidArgumentException(
                    "Zwrot dla sytuacji [{$sytuacja}] musi mieścić się w 0..100, podano {$procent}."
                );
            }

            $macierz[$sytuacja] = [
                'zwrot_procent' => $procent,
                'termin_wraca' => self::logiczna($skutek['termin_wraca'] ?? null, "macierz_odwolan.{$sytuacja}.termin_wraca"),
                'godzina_platna' => self::logiczna($skutek['godzina_platna'] ?? null, "macierz_odwolan.{$sytuacja}.godzina_platna"),
            ];
        }

        // U-10: macierz częściowa pozwalała `OcenaAnulacji` sięgnąć po wartości
        // Z KODU dla brakujących sytuacji.
        $brakSytuacji = array_values(array_diff(
            array_map(static fn (Sytuacja $s): string => $s->value, Sytuacja::cases()),
            array_keys($macierz)
        ));

        if ($brakSytuacji !== []) {
            throw new InvalidArgumentException(
                'Macierz odwołań jest niepełna — brakuje: '.implode(', ', $brakSytuacji)
            );
        }

        return new self(
            wersja: $liczby['wersja'],
            oknoBezplatnegoOdwolaniaGodzin: $liczby['okno_bezplatnego_odwolania_godzin'],
            limitPrzelozen: $liczby['limit_przelozen'],
            najblizszyTerminGodzin: $liczby['najblizszy_termin_godzin'],
            kalendarzPacjentaDni: $liczby['kalendarz_pacjenta_dni'],
            horyzontWystawianiaDni: $liczby['horyzont_wystawiania_dni'],
            przerwaMiedzyWizytamiMinut: $liczby['przerwa_miedzy_wizytami_minut'],
            blokadaKoszykaMinut: $liczby['blokada_koszyka_minut'],
            waznoscLinkuPlatnosciDni: $liczby['waznosc_linku_platnosci_dni'],
            limitNiskoplatnychWizyt: $liczby['limit_niskoplatnych_wizyt'],
            limitNiskoplatnychNaTydzien: $liczby['limit_niskoplatnych_na_tydzien'],
            kredytZaOdsprzedanyTermin: self::logiczna($dane['kredyt_za_odsprzedany_termin'], 'kredyt_za_odsprzedany_termin'),
            autoDomkniecieGodzin: $liczby['auto_domkniecie_godzin'],
            macierzOdwolan: $macierz,
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
