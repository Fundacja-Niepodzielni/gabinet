<?php

declare(strict_types=1);

use App\Reguly\ZestawRegul;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reguły systemu jako KONFIGURACJA Z WERSJONOWANIEM (CLAUDE.md §14, spec M4/16).
 *
 * Dlaczego tabela, a nie stałe w kodzie: koordynator ma ekran „Reguły systemu",
 * na którym zmienia okno bezpłatnego odwołania, limity i macierz odwołań.
 * Zmiana wchodzi w życie od konkretnej daty i **NIGDY nie działa wstecz** —
 * rezerwacja opłacona wczoraj zachowuje zasady z dnia zakupu, bo pacjent zgodził
 * się na konkretne warunki (spec s. 48).
 *
 * Dlatego wiersze są NIEZMIENNE. Zmiana reguły = nowy wiersz z nową datą
 * obowiązywania, nie UPDATE poprzedniego. Historia zmian jest wtedy darmowa,
 * a „jaka reguła obowiązywała 3 marca" to jedno zapytanie, nie śledztwo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konfiguracja_regul', function (Blueprint $table): void {
            $table->id();

            // Numer wersji rośnie monotonicznie. Wersja 1 to „wersja zerowa"
            // z migracji — spec M4/16 wymaga jej wprost, żeby rezerwacje sprzed
            // pierwszej zmiany też miały co zamrozić.
            $table->unsignedInteger('wersja')->unique();

            // Od kiedy obowiązuje. W UTC; okna regulaminowe liczone są potem
            // w Europe/Warsaw (CLAUDE.md §5).
            $table->timestampTz('obowiazuje_od');

            // Pełna treść reguł. JSON, a nie kolumny, z jednego powodu: to jest
            // zrzut, który trafia do `rezerwacja.regula_anulacji_zamrozona`
            // w całości. Kolumny wymuszałyby przepisywanie schematu przy każdym
            // dołożeniu reguły, a zamrożony zrzut i tak musi być samowystarczalny.
            $table->jsonb('reguly');

            // Kto i dlaczego. Zmiana reguł to decyzja, nie zdarzenie techniczne.
            $table->string('autor')->nullable();
            $table->text('uzasadnienie')->nullable();

            $table->timestampTz('created_at')->useCurrent();
        });

        // Wyszukiwanie „która wersja obowiązywała w chwili T" to najczęstsze
        // zapytanie tej tabeli — i jedyne na ścieżce zakupu.
        Schema::table('konfiguracja_regul', function (Blueprint $table): void {
            $table->index('obowiazuje_od');
        });

        // WERSJA ZEROWA — wymagana wprost przez spec M4/16 („potrzebna wersja
        // zerowa i migracja"). Bez niej pierwsza rezerwacja nie miałaby czego
        // zamrozić, a `RejestrRegul` musiałby zgadywać.
        //
        // Data obowiązywania w przeszłości, żeby objąć także dane migrowane
        // z Bookero (F9) — one też muszą mieć zamrożoną regułę, choćby po to,
        // by dało się je uczciwie rozliczyć.
        DB::table('konfiguracja_regul')->insert([
            'wersja' => 1,
            'obowiazuje_od' => '2020-01-01 00:00:00+00',
            'reguly' => json_encode(ZestawRegul::wersjaZerowa()->doTablicy(), JSON_UNESCAPED_UNICODE),
            'autor' => 'migracja',
            'uzasadnienie' => 'Wersja zerowa: wartości z dnia startu systemu, wg specyfikacji i decyzji D-2026-08-07-08.',
            'created_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('konfiguracja_regul');
    }
};
