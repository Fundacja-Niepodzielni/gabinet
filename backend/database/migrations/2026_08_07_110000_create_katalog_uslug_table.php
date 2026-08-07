<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Katalog usług — cztery pozycje z menu fundacji, ale jako DANE, nie kod.
 *
 * Dwie rzeczy, które muszą tu być od pierwszego dnia:
 *
 *  1. **Flaga konta Stripe** (`fundacja` / `komercja`). CLAUDE.md §3: to są dwa
 *     NIEZALEŻNE konta — osobne klucze, osobne webhooki, osobna rekoncyliacja.
 *     Usługa niesie tę flagę, bo od niej zależy, na które konto idzie płatność
 *     i w którym raporcie ląduje przychód.
 *  2. **Prowizja per usługa**, nie per specjalista. Niskopłatna ma 0% (fundacja
 *     dopłaca z dotacji), pełnopłatna 20%. Spec M3/10 ostrzega wprost: prowizja
 *     0% musi być liczona per usługa — przy liczeniu per specjalista psycholog
 *     przyjmujący oba rodzaje wizyt rozliczy się źle.
 *
 * Ceny w GROSZACH. Złotówki w liczbach zmiennoprzecinkowych to gwarantowany
 * rozjazd ze Stripe'em na trzecim miejscu po przecinku.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uslugi', function (Blueprint $table): void {
            $table->id();

            // Stabilny identyfikator tekstowy — nazwa może się zmienić,
            // kod nie. Po nim wołają moduły i po nim idą raporty.
            $table->string('kod')->unique();
            $table->string('nazwa');
            $table->unsignedSmallInteger('minuty');

            // `stala` albo `widelki`. Jedyna cena, o której decyduje specjalista,
            // to stawka pełnopłatna wybrana z zamkniętej listy widełek (spec s. 30).
            $table->string('model_ceny');
            $table->unsignedInteger('cena_gr')->nullable();
            $table->jsonb('widelki_gr')->nullable();

            // CLAUDE.md §3 — na które z DWÓCH kont Stripe idzie płatność.
            $table->string('konto_stripe');

            // Prowizja fundacji w punktach bazowych (2000 = 20%). Punkty bazowe,
            // nie procenty, żeby 12,5% dało się zapisać bez ułamka.
            $table->unsignedSmallInteger('prowizja_bp')->default(0);

            // Usługi wymagające zgody koordynatora: diagnoza ADHD (dyplom)
            // i asystent zdrowienia (spec s. 36).
            $table->boolean('wymaga_uprawnienia')->default(false);
            $table->boolean('widoczna_publicznie')->default(true);

            $table->timestampsTz();
        });

        Schema::create('specjalisci', function (Blueprint $table): void {
            $table->id();

            // CLAUDE.md §2: wiązanie po `sub` z Keycloaka, NIGDY po e-mailu.
            // Zmiana adresu w IdP nie może przenieść profilu na inną osobę.
            $table->string('keycloak_sub')->unique();

            $table->string('imie')->nullable();
            $table->string('nazwisko')->nullable();

            // Stawka pełnopłatna wybrana z widełek usługi, w groszach.
            $table->unsignedInteger('stawka_pelna_gr')->nullable();

            // Wdrożenie ma 7 kroków, ale to TA flaga rozstrzyga, czy ktoś może
            // przyjmować pacjentów — „najważniejsza kolumna to nie postęp,
            // tylko »przyjmuje pacjentów«" (spec s. 43). 5 z 7 kroków ją
            // wstrzymuje; brak numeru konta wstrzymuje wyłącznie wypłatę.
            $table->boolean('przyjmuje_pacjentow')->default(false);

            $table->timestampsTz();
        });

        // Uprawnienia do usług: „odebranie usługi nie kasuje już umówionych
        // wizyt" (spec s. 42) — dlatego to osobna tabela, a nie kolumna.
        Schema::create('specjalista_usluga', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('specjalista_id')->constrained('specjalisci')->cascadeOnDelete();
            $table->foreignId('usluga_id')->constrained('uslugi')->cascadeOnDelete();
            $table->boolean('wlaczona')->default(true);
            $table->timestampsTz();

            $table->unique(['specjalista_id', 'usluga_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specjalista_usluga');
        Schema::dropIfExists('specjalisci');
        Schema::dropIfExists('uslugi');
    }
};
