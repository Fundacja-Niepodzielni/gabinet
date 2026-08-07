<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela kont lokalnych — BEZ ŻADNEGO MECHANIZMU HASEŁ.
 *
 * CLAUDE.md §2: „ŻADNYCH własnych haseł w tym systemie". Tożsamość żyje
 * w Kontach Niepodzielni (Keycloak); tutaj trzymamy wyłącznie odwzorowanie
 * konta zdalnego na rekord lokalny, wiązane po `sub` — NIGDY po e-mailu.
 *
 * Domyślny szkielet Laravela dowoził kolumnę `password`, `remember_token`
 * i całą tabelę `password_reset_tokens`. Zostały usunięte świadomie, a nie
 * przeoczone: schemat, który wozi mechanizm własnych haseł, prędzej czy
 * później doczeka się kontrolera, który z niego skorzysta. Pilnuje tego
 * test `tests/Feature/BrakWlasnychHaselTest.php` — sprawdza SCHEMAT bazy,
 * nie deklarację w dokumentacji.
 *
 * Pełny model danych (pacjent, specjalista, rezerwacja, zgoda, zdarzenie)
 * powstaje w F1, PO zamknięciu DPIA-checklisty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();

            // Klucz trwały tożsamości — `sub` z Keycloaka. Zmiana adresu
            // e-mail w IdP nie może przenieść konta na inne (kontrakt §4.3).
            $table->string('keycloak_sub')->unique();

            // E-mail jest ATRYBUTEM OPCJONALNYM: Admin API zakłada konto
            // z samym `username` i claimu `email` w tokenie może nie być
            // (kontrakt §2a). Kolumna nie ma więc ograniczenia NOT NULL.
            $table->string('email')->nullable();
            $table->boolean('email_potwierdzony')->default(false);

            // Imię i nazwisko bywają puste — pseudonimiczność pacjenta.
            $table->string('nazwa_wyswietlana')->nullable();

            $table->timestampTz('ostatnie_logowanie_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
