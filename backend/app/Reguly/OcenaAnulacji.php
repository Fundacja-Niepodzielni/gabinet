<?php

declare(strict_types=1);

namespace App\Reguly;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * JEDNA FUNKCJA ROZSTRZYGAJĄCA — zwrot, możliwość przełożenia, płatność godziny.
 *
 * `CLAUDE.md` §1: „Jedna funkcja rozstrzygająca na regułę, wołana przez
 * wszystkie moduły." Specyfikacja mówi to samo trzy razy, w trzech miejscach:
 * „żaden ekran nie decyduje samodzielnie" (s. 47), „cała polityka odwołań żyje
 * w jednej funkcji `ocenaAnulacji()`" (s. 64), a przy wdrożeniu ta sama zasada
 * ma obowiązywać **po stronie serwera**.
 *
 * Klasa jest CZYSTA: nie dotyka bazy, nie czyta konfiguracji, nie zna „teraz".
 * Wszystko dostaje w argumentach. Dzięki temu test granicy 23:59/24:00/24:01
 * jest zwykłym wywołaniem funkcji, a nie inscenizacją z bazą i zegarem.
 *
 * Reguły przychodzą z ZAMROŻONEGO zrzutu rezerwacji, nie z bieżącej
 * konfiguracji. To nie jest szczegół: przesunięcie okna z 24 na 48 godzin
 * obowiązuje wyłącznie nowe rezerwacje (spec s. 51, 54, 59).
 */
final class OcenaAnulacji
{
    /**
     * Rozstrzyga skutki odwołania/nieobecności.
     *
     * @param  ZestawRegul  $reguly  ZAMROŻONY zrzut z chwili zakupu
     * @param  CarbonImmutable  $terminWizyty  termin wizyty (dowolna strefa — porównujemy chwile)
     * @param  CarbonImmutable  $chwilaDecyzji  kiedy pada decyzja
     * @param  int  $kwotaZamrozonaGr  kwota zapłacona, w groszach
     */
    public static function oceń(
        ZestawRegul $reguly,
        Sytuacja $sytuacja,
        CarbonImmutable $terminWizyty,
        CarbonImmutable $chwilaDecyzji,
        int $kwotaZamrozonaGr,
    ): Werdykt {
        // U-7: kwota ujemna dawała ujemny zwrot, a `PHP_INT_MAX` wywracał
        // `intdiv()`. Rozstrzyganie o pieniądzach zaczyna się od sprawdzenia,
        // czy pieniądze mają sens.
        if ($kwotaZamrozonaGr < 0) {
            throw new InvalidArgumentException("Kwota zamrożona nie może być ujemna: {$kwotaZamrozonaGr}.");
        }

        // W-5 z rundy 4: komentarz w tym miejscu TWIERDZIŁ, że `PHP_INT_MAX`
        // jest obsłużony. Nie był — `$kwota * $procent` przepełniało typ
        // całkowity, wynik stawał się liczbą zmiennoprzecinkową i `intdiv()`
        // rzucało `TypeError` z komunikatem, który nie mówi nic o pieniądzach.
        // Komentarz opisujący naprawę nie jest naprawą.
        //
        // Górna granica wynika wprost z arytmetyki: mnożymy przez najwyżej 100.
        $granica = intdiv(PHP_INT_MAX, 100);

        if ($kwotaZamrozonaGr > $granica) {
            throw new InvalidArgumentException(
                "Kwota zamrożona {$kwotaZamrozonaGr} gr przekracza granicę bezpiecznego przeliczenia ({$granica} gr)."
            );
        }

        // Godziny do wizyty liczone w SEKUNDACH i dopiero potem porównywane
        // z oknem. Liczenie w „pełnych godzinach" gubi 59 minut i przesuwa
        // granicę o godzinę — a granica jest tu całą regułą.
        $sekundDoWizyty = $terminWizyty->getTimestamp() - $chwilaDecyzji->getTimestamp();
        $sekundOkna = $reguly->oknoBezplatnegoOdwolaniaGodzin * 3600;

        // „Wcześniej niż 24 h" znaczy: zostało WIĘCEJ niż 24 h. Dokładnie
        // 24:00:00 to jeszcze bezpłatne odwołanie — pacjent widzi datę graniczną
        // co do minuty i musi móc trafić w nią bez ryzyka.
        $wOknieBezplatnym = $sekundDoWizyty >= $sekundOkna;

        // Sytuacja pacjenta rozstrzyga się DOPIERO tutaj: wywołujący mówi
        // „pacjent odwołuje", a nie „pacjent odwołuje późno". Inaczej każdy
        // moduł liczyłby granicę po swojemu — czyli dokładnie to, przed czym
        // przestrzega §1 CLAUDE.md.
        $rozstrzygnieta = match ($sytuacja) {
            Sytuacja::PacjentOdwolujeWczesniej, Sytuacja::PacjentOdwolujePozniej => $wOknieBezplatnym
                ? Sytuacja::PacjentOdwolujeWczesniej
                : Sytuacja::PacjentOdwolujePozniej,
            default => $sytuacja,
        };

        // U-10: BEZ cichego sięgania po wartości domyślne z kodu. Zamrożony
        // zrzut ma być samowystarczalny; brak klucza to błąd danych, nie
        // zaproszenie do zgadywania.
        if (! array_key_exists($rozstrzygnieta->value, $reguly->macierzOdwolan)) {
            throw new InvalidArgumentException(
                "Zamrożona macierz nie zna sytuacji [{$rozstrzygnieta->value}]."
            );
        }

        $skutek = $reguly->macierzOdwolan[$rozstrzygnieta->value];

        $zwrotProcent = $skutek['zwrot_procent'];

        // Liczymy w GROSZACH i dzielimy na końcu — inaczej 55 zł przy 100%
        // potrafi wyjść 54,99 zł, a zwrot musi zgadzać się ze Stripe co do grosza.
        $kwotaZwrotuGr = intdiv($kwotaZamrozonaGr * $zwrotProcent, 100);

        // Trzeci zamek: choćby oba poprzednie zawiodły, zwrot NIGDY nie
        // przekroczy tego, co pacjent naprawdę zapłacił.
        //
        // @dowod: GranicePienidzyTest — „nigdy nie zwraca więcej, niż pacjent
        //         zapłacił — nawet gdyby oba wcześniejsze zamki zawiodły".
        //         Twierdzenie wskazane przez helpdesk (ZLECENIE-008) jako
        //         orzekające bez świadka; świadek ISTNIAŁ, brakowało wskazania.
        $kwotaZwrotuGr = min($kwotaZwrotuGr, $kwotaZamrozonaGr);

        return new Werdykt(
            sytuacja: $rozstrzygnieta,
            wOknieBezplatnym: $wOknieBezplatnym,
            kwotaZwrotuGr: $kwotaZwrotuGr,
            terminWracaDoPuli: $skutek['termin_wraca'],
            godzinaPlatnaDlaSpecjalisty: $skutek['godzina_platna'],
            sekundDoWizyty: $sekundDoWizyty,
        );
    }

    /**
     * Czy wolno przełożyć rezerwację?
     *
     * Dwa warunki naraz, oba twarde: okno bezpłatnej zmiany **i** limit zmian.
     * Późne przełożenie jest zablokowane świadomie — „jeśli zostawić furtkę,
     * nikt nie odwołuje, wszyscy przekładają. Slot i tak przepada, a okno 24 h
     * przestaje cokolwiek znaczyć" (dziennik makiety, rozdz. 3).
     */
    public static function czyMoznaPrzelozyc(
        ZestawRegul $reguly,
        CarbonImmutable $terminWizyty,
        CarbonImmutable $chwilaDecyzji,
        int $liczbaPrzelozen,
    ): OdmowaPrzelozenia|true {
        $sekundDoWizyty = $terminWizyty->getTimestamp() - $chwilaDecyzji->getTimestamp();

        if ($sekundDoWizyty < $reguly->oknoBezplatnegoOdwolaniaGodzin * 3600) {
            return OdmowaPrzelozenia::PozaOknem;
        }

        if ($liczbaPrzelozen >= $reguly->limitPrzelozen) {
            return OdmowaPrzelozenia::WyczerpanyLimit;
        }

        return true;
    }
}
