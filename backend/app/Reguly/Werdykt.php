<?php

declare(strict_types=1);

namespace App\Reguly;

/**
 * Wynik oceny anulacji — jedyne źródło prawdy dla wszystkich modułów.
 *
 * Zawiera także `sekundDoWizyty` i `wOknieBezplatnym`, bo test negatywny musi
 * móc udowodnić, że werdykt zapadł Z WŁAŚCIWEGO POWODU. Ta sama zasada, co
 * w mapie kontroli walidatora tokenu: „odrzucone" bez wskazania powodu
 * przechodzi także wtedy, gdy logika jest zepsuta w całości.
 */
final readonly class Werdykt
{
    public function __construct(
        public Sytuacja $sytuacja,
        public bool $wOknieBezplatnym,
        public int $kwotaZwrotuGr,
        public bool $terminWracaDoPuli,
        public bool $godzinaPlatnaDlaSpecjalisty,
        public int $sekundDoWizyty,
    ) {}

    /**
     * Czy powstaje zadanie zwrotu dla koordynatora.
     *
     * CLAUDE.md §7: system NIGDY nie pisze „zwrot wykonany" — generuje zadanie.
     * „Wykonany" pojawia się dopiero po webhooku `charge.refunded`.
     */
    public function czyPowstajeZadanieZwrotu(): bool
    {
        return $this->kwotaZwrotuGr > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function doTablicy(): array
    {
        return [
            'sytuacja' => $this->sytuacja->value,
            'w_oknie_bezplatnym' => $this->wOknieBezplatnym,
            'kwota_zwrotu_gr' => $this->kwotaZwrotuGr,
            'termin_wraca_do_puli' => $this->terminWracaDoPuli,
            'godzina_platna_dla_specjalisty' => $this->godzinaPlatnaDlaSpecjalisty,
            'zadanie_zwrotu' => $this->czyPowstajeZadanieZwrotu(),
        ];
    }
}
