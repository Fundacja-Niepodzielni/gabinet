<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rezerwacja i ślad audytowy — tu mieszkają trzy najważniejsze pola systemu.
 *
 * Z dziennika makiety, rozdz. 4: „Trzy pola, bez których system się rozjeżdża".
 * Dwa z nich są tutaj:
 *
 *  - **`kwota_zamrozona_gr`** — rezerwacja pamięta cenę z dnia zakupu. Bez tego
 *    podniesienie cennika o 10 zł sprawia, że stare zwroty przestają zgadzać się
 *    z operatorem płatności.
 *  - **`regula_anulacji_zamrozona`** — PEŁNY ZRZUT reguł, nie referencja do
 *    wersji (CLAUDE.md §4). Referencja wygląda oszczędniej, ale przy zwrocie
 *    sprzed roku trzeba by mieć pewność, że tamten wiersz nadal istnieje
 *    i nadal znaczy to samo.
 *
 * Trzecim polem jest `Poprawka` jako osobny byt — wchodzi w F2 razem
 * z silnikiem dostępności.
 *
 * WSPÓŁBIEŻNOŚĆ (CLAUDE.md §6): unikalne ograniczenie `(specjalista_id, termin)`
 * na poziomie BAZY. Warunek w kodzie nie wystarcza — przy PHP-FPM dwa żądania
 * sprawdzają wolny termin jednocześnie i oba widzą, że jest wolny. Test 100
 * równoczesnych żądań o jeden termin wchodzi w F3.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rezerwacje', function (Blueprint $table): void {
            $table->id();

            // W-11 (DPIA): identyfikator NIEODGADYWALNY. Stare `NP-` + kolejny
            // numer pozwalał czytać cudze rezerwacje przez podmianę cyfry
            // (spec M6/5). Numer publiczny jest losowy; `id` zostaje wewnętrzne.
            $table->uuid('numer')->unique();

            $table->foreignId('pacjent_id')->constrained('pacjenci')->restrictOnDelete();
            $table->foreignId('specjalista_id')->constrained('specjalisci')->restrictOnDelete();
            $table->foreignId('usluga_id')->constrained('uslugi')->restrictOnDelete();

            // UTC w bazie, prezentacja Europe/Warsaw (CLAUDE.md §5).
            $table->timestampTz('termin');
            $table->unsignedSmallInteger('czas_trwania_minut');

            $table->string('status');
            $table->string('forma')->default('online');

            // --- ZAMROŻONE W CHWILI ZAKUPU (CLAUDE.md §4) --------------------
            $table->unsignedInteger('kwota_zamrozona_gr');
            $table->jsonb('regula_anulacji_zamrozona');
            $table->unsignedInteger('wersja_regul');
            // W-4: wersja regulaminu, na którą pacjent się zgodził.
            $table->string('wersja_regulaminu');
            // Flaga konta Stripe zamrożona razem z resztą: usługa może zmienić
            // przypisanie, rozliczenie starej rezerwacji nie może.
            $table->string('konto_stripe');
            $table->unsignedSmallInteger('prowizja_bp_zamrozona');
            // -----------------------------------------------------------------

            $table->string('stripe_payment_intent')->nullable();
            $table->unsignedTinyInteger('liczba_przelozen')->default(0);
            $table->text('link_spotkania')->nullable();

            $table->timestampsTz();

            // CLAUDE.md §6 — JEDYNA linia obrony przed podwójną rezerwacją.
            $table->unique(['specjalista_id', 'termin']);

            // Wyszukiwanie „wizyty pacjenta" i „grafik specjalisty na dzień".
            $table->index(['pacjent_id', 'termin']);
            $table->index(['termin', 'status']);
        });

        Schema::create('zdarzenia_rezerwacji', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rezerwacja_id')->constrained('rezerwacje')->cascadeOnDelete();

            // Aktor: człowiek albo system. Spec M5/13 wymaga rozróżnienia —
            // „kto to zrobił" przy automacie brzmi inaczej niż przy koordynatorze.
            $table->string('aktor_rodzaj');
            $table->string('aktor_identyfikator')->nullable();

            $table->string('typ');

            // Kontekst zdarzenia. W-5 i §6 DPIA: BEZ treści zdrowotnych —
            // ślad audytowy mówi, CO się stało, nie o czym była rozmowa.
            $table->jsonb('szczegoly')->nullable();

            // Oś czasu „pokazuje wyłącznie to, co się już wydarzyło" — nie ma
            // zdarzeń z przyszłości (spec s. 41, M5/13).
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['rezerwacja_id', 'created_at']);
        });

        // W-5: ślad audytowy jest TYLKO DO DOPISYWANIA. Reguła jest egzekwowana
        // przez BAZĘ, nie przez pamięć autora kontrolera — dokładnie tak, jak
        // CLAUDE.md §9 wymaga dla dziennika decyzji (ten powstaje w F5).
        // Wyzwalacz odrzuca UPDATE i DELETE niezależnie od tego, kto je wywołał.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION zdarzenia_tylko_insert() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'Slad audytowy jest tylko do dopisywania (proba: %)', TG_OP;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER zdarzenia_bez_zmian
            BEFORE UPDATE OR DELETE ON zdarzenia_rezerwacji
            FOR EACH ROW EXECUTE FUNCTION zdarzenia_tylko_insert();
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS zdarzenia_bez_zmian ON zdarzenia_rezerwacji');
        DB::statement('DROP FUNCTION IF EXISTS zdarzenia_tylko_insert()');

        Schema::dropIfExists('zdarzenia_rezerwacji');
        Schema::dropIfExists('rezerwacje');
    }
};
