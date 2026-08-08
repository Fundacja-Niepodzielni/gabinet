<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Znaczniki unieważnionych sesji SSO — TRWALE, nie w cache'u.
 *
 * Znacznik unieważnienia jest **negatywną asercją bezpieczeństwa**: mówi „ten
 * `sid` jest martwy". Każdy sposób, w jaki może zniknąć, jest po cichu
 * FAIL-OPEN — zablokowany użytkownik wraca, a objawem jest BRAK OBJAWU.
 *
 * Pierwsza wersja trzymała go w cache'u i to był błąd tej samej klasy co
 * BLK-22: obietnica działa, dopóki nikt jej nie zmierzy. Wyzwalacze są
 * całkowicie prozaiczne — `cache:clear`, deploy, restart Redisa, eksmisja LRU.
 *
 * Cztery wymagania, które ta tabela spełnia:
 *
 *  1. TRWAŁOŚĆ — przeżywa restart, deploy i `cache:clear`.
 *  2. WSPÓŁDZIELENIE — wszystkie instancje widzą ten sam znacznik.
 *  3. CZAS ŻYCIA ≥ najdłuższemu bytowi, który unieważnia. To NIE jest 600 s
 *     życia access tokenu, tylko **SSO Session Max** realmu: refresh token
 *     żyje dłużej niż access token, więc znacznik wygasający przed nim
 *     wpuszczałby z powrotem. `wygasa_at` liczone od tego progu, a sprzątanie
 *     używa TEGO SAMEGO progu — inaczej sprzątaczka sama odblokowuje.
 *  4. EKSMISJA NIE MOŻE BYĆ CICHA — w magazynie z LRU „brak znacznika" jest
 *     nieodróżnialny od „nigdy nie blokowany". Tabela nie eksmituje nic sama;
 *     usuwa wyłącznie zadanie czyszczące, po jawnym progu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uniewaznione_sesje', function (Blueprint $table): void {
            // Skrót `sid`, nie sam `sid`: identyfikator sesji SSO nie ma po co
            // leżeć w bazie w postaci czytelnej (ta sama zasada co
            // `pacjenci.email_skrot`).
            $table->string('sid_skrot', 64)->primary();

            $table->timestampTz('uniewazniona_at');

            // Do KIEDY znacznik obowiązuje. Sprzątanie kasuje wyłącznie wpisy
            // po tej dacie — próg jest zapisany w wierszu, nie domyślany
            // z konfiguracji w chwili sprzątania.
            $table->timestampTz('wygasa_at')->index();

            // Po co unieważniono — do diagnozy i do odróżnienia „wylogowany"
            // od „konto zablokowane", gdy dojdą kolejne powody.
            $table->string('powod', 40)->default('backchannel-logout');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uniewaznione_sesje');
    }
};
