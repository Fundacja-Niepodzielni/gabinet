<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dwa magazyny, które NIOSĄ ASERCJE BEZPIECZEŃSTWA — wyprowadzone z cache'u.
 *
 * Cztery wymagania trwałości zastosowaliśmy 09.08 do ZNACZNIKA unieważnienia
 * (`uniewaznione_sesje`) i NIE zastosowaliśmy ich do dwóch rzeczy obok.
 * Weryfikator rundy 6 zmierzył jedno i drugie.
 *
 * ── `sesje_sso` (R6B-9, waga PODNIESIONA przez weryfikację krzyżową Kont) ──
 *
 * Mapa `sid → identyfikatory sesji lokalnych`. Bez niej back-channel logout
 * NIE ZNAJDZIE ŻADNEJ SESJI: żądanie od IdP przychodzi bez ciasteczka, więc
 * `sid` jest jedynym kluczem. Mieszkała w cache'u z TTL 86 400 s.
 *
 * Utrata rejestru daje `skasowane_sesje = 0` — **po cichu**. To jest FAIL-OPEN
 * w najgorszym możliwym miejscu: wylogowany zostaje zalogowany, a objawem jest
 * BRAK OBJAWU. Wyzwalacze są prozaiczne: `cache:clear`, deploy, restart Redisa.
 *
 * Część przyczyny leży po stronie kontraktu Kont — ich §4.5 mówi „skasuj sesje
 * o tym `sid`" i milczy o tym, że trzeba je najpierw UMIEĆ ZNALEŹĆ. Ich wzorzec
 * `ref-laravel` mapy nie potrzebuje (skanuje pliki rekordów); konsument na
 * Redisie skanować nie może i musi ją zbudować — mechanizm, którego kontrakt
 * nie nazywa, więc i nie obejmuje wymaganiami. Zapisali to jako znalezisko
 * przeciwko sobie.
 *
 * `sygnaly_zdrowia` NIE POWSTAJE — i to jest wynik pomiaru, nie zmiana zdania.
 *
 * Puls harmonogramu (R6B-10) mial tu zamieszkac. Wlasna bramka to OBALILA:
 * krok [5] meldowal `scheduler=starting`, bo sonda kontenera wola
 * `gabinet:puls --sprawdz`, a migracje ida dopiero w kroku [13] — sonda
 * pytala o tabele, ktorej jeszcze nie ma. SYGNAL ZDROWIA NIE MOZE ZALEZEC
 * OD SCHEMATU, ktorego powstanie sam poprzedza. Puls mieszka w PLIKU
 * (patrz `App\Console\Commands\Puls`), co spelnia caly model zagrozen
 * R6B-10 i nie wprowadza tej zaleznosci.
 *
 * ── Cztery wymagania, które obie tabele spełniają ──
 *
 *  1. TRWAŁOŚĆ — przeżywają restart, deploy i `cache:clear`.
 *  2. WSPÓŁDZIELENIE — wszystkie instancje widzą to samo (php-fpm, Horizon,
 *     harmonogram; cache Redisa też, ale jego kasowanie jest jednym poleceniem).
 *  3. CZAS ŻYCIA ≥ najdłuższemu bytowi, który opisują. Dla mapy to SSO Session
 *     Max realmu (`konta.sso_session_max_s`), NIE czas życia access tokenu.
 *     Próg zapisany W WIERSZU, żeby sprzątanie używało tego samego progu,
 *     co zapis.
 *  4. EKSMISJA NIE MOŻE BYĆ CICHA — tabela nie eksmituje nic sama; usuwa
 *     wyłącznie zadanie czyszczące, po jawnym progu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesje_sso', function (Blueprint $table): void {
            $table->id();

            // Skrót `sid`, nie sam `sid` — ta sama zasada co w `uniewaznione_sesje`.
            $table->string('sid_skrot', 64)->index();

            // Identyfikator sesji frameworka. ROTUJE przy ruchu użytkownika,
            // więc jeden `sid` może mieć ich wiele — stąd tabela, nie kolumna.
            $table->string('id_sesji', 191);

            $table->timestampTz('zapamietana_at');
            $table->timestampTz('wygasa_at')->index();

            // Zapamiętanie tej samej sesji dwa razy nie ma tworzyć duplikatu.
            $table->unique(['sid_skrot', 'id_sesji']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesje_sso');
    }
};
