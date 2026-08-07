<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pacjent i zgody — pierwsze tabele z danymi art. 9 RODO.
 *
 * Wymagania z `docs/rodo/DPIA-checklista.md`, które ta migracja realizuje:
 *  - **W-1**: kolumny 🔒 szyfrowane na poziomie aplikacji (`encrypted` cast),
 *    nie tylko szyfrowanie dysku. Dlatego `text`, nie `string(255)` —
 *    szyfrogram jest znacznie dłuższy od tekstu jawnego.
 *  - **W-2**: zgoda WERSJONOWANA, wiersze niezmienne. Wycofanie zgody to
 *    nowy wiersz, nie UPDATE — inaczej znika dowód, że zgoda kiedykolwiek była.
 *  - **W-8**: wszystkie znaczniki czasu jako `timestamptz`.
 *  - **W-10**: usunięcie konta = anonimizacja, nie `DELETE` (stąd
 *    `zanonimizowany_at`, a nie miękkie kasowanie).
 *
 * Pacjent-GOŚĆ nie ma konta w Keycloaku — `keycloak_sub` jest nullable.
 * To nie jest wyjątek, tylko domyślna ścieżka rezerwacji (spec s. 3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacjenci', function (Blueprint $table): void {
            $table->id();

            // NULL dla gościa. Konto w Keycloaku powstaje dopiero po
            // zaksięgowaniu płatności (CLAUDE.md §2).
            $table->string('keycloak_sub')->nullable()->unique();

            // 🔒 W-1. `text`, bo szyfrogram nie mieści się w 255 znakach.
            $table->text('imie')->nullable();
            $table->text('nazwisko')->nullable();
            $table->text('email')->nullable();
            $table->text('telefon')->nullable();

            // Skrót e-maila do WYSZUKIWANIA bez odszyfrowywania całej tabeli.
            // Sam skrót nie jest odwracalny, ale pozwala dopasować gościa
            // wracającego pod tym samym adresem — a to jest dokładnie problem
            // „co jest tożsamością pacjenta-gościa" z raportu grantowego
            // (spec M4/8, pytanie P-7 w DPIA — do rozstrzygnięcia przez człowieka).
            $table->string('email_skrot', 64)->nullable()->index();

            $table->string('strefa_czasowa')->default('Europe/Warsaw');

            // Psycholog prowadzący przypisuje się AUTOMATYCZNIE po pierwszej
            // odbytej wizycie — nie ma ekranu „przypisz psychologa" (spec s. 41).
            $table->foreignId('prowadzacy_specjalista_id')->nullable()
                ->constrained('specjalisci')->nullOnDelete();

            // Limit indywidualny: koordynator podnosi go skokiem, decyzja idzie
            // do dziennika (spec s. 41). NULL = obowiązuje limit z konfiguracji.
            $table->unsignedSmallInteger('limit_niskoplatnych_indywidualny')->nullable();

            // W-10: usunięcie konta to anonimizacja z zachowaniem spójności
            // raportów, nie kasowanie wiersza.
            $table->timestampTz('zanonimizowany_at')->nullable();

            $table->timestampsTz();
        });

        Schema::create('zgody', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pacjent_id')->constrained('pacjenci')->cascadeOnDelete();

            // `regulamin` · `dane_zdrowie` (art. 9!) · `marketing`
            $table->string('rodzaj');

            // WERSJA dokumentu, na który pacjent się zgodził. Bez niej nie da
            // się odpowiedzieć na pytanie „na co dokładnie się zgodził"
            // po pierwszej zmianie regulaminu (spec M6/10).
            $table->string('wersja_dokumentu');

            $table->boolean('udzielona');
            $table->timestampTz('rozstrzygnieta_at');

            // Dowód rozliczalności. IP to dana osobowa — ma własną retencję.
            $table->string('ip', 45)->nullable();

            // W-2: BEZ `updated_at`. Wiersz jest niezmienny; zmiana decyzji
            // to nowy wiersz. Brak kolumny to najtańsze przypomnienie o tym.
            $table->timestampTz('created_at')->useCurrent();

            // Historia zgód czytana jest zawsze „po pacjencie i rodzaju,
            // najnowsza pierwsza".
            $table->index(['pacjent_id', 'rodzaj', 'rozstrzygnieta_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zgody');
        Schema::dropIfExists('pacjenci');
    }
};
