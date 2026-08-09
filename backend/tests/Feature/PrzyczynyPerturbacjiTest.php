<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * ZAPADKA POKRYCIA dla allowlist przyczyny czerwieni.
 *
 * Adaptacja projektu P1 kont (`niepodzielni-konta/docs/ZLECENIA/ODPOWIEDZ-005.md` §3.3):
 * rejestr + ZAPADKA, czyli „dług widoczny, tolerowany, malejący", zamiast bramki
 * czerwonej przez miesiące, którą pierwsza osoba wycisza.
 *
 * ================== CO TA KONTROLA MIERZY ==================
 *
 * `oczekuj_czerwone --przyczyna "X"` ma odpowiadać na pytanie „czy czerwień
 * przyszła z BADANEGO powodu". Wzorzec `X` jest dopasowywany do CAŁEGO wyjścia
 * przebiegu (`grep -qiE`). Jeżeli `X` jest NAZWĄ TESTU, to Pest wypisuje go
 * w każdym przebiegu — także zielonym — więc warunek spełnia się przez sam fakt
 * URUCHOMIENIA testu, a nie przez zapalenie badanej asercji. To jest gałąź
 * zdegenerowana: jedna zmierzona wartość zgodna z więcej niż jednym światem.
 *
 * ZMIERZONE 09.08.2026 — wzorzec obecny w wyjściu ZIELONEGO przebiegu:
 *   „odbiera dostęp"    → OBECNY   (nazwa testu, OdebranieRoliTest:167)
 *   „niedostępnym IdP"  → OBECNY   (nazwa testu, OdebranieRoliTest:286)
 *   „ZASZYFROWANY"      → OBECNY   (nazwa testu, OdebranieRoliTest:480)
 *   „granicę okna"      → OBECNY   (nazwa testu, OcenaAnulacjiTest:35)
 *   „ZAMROŻONĄ"         → OBECNY   (nazwa testu, GranicePienidzyTest:148)
 *   „Logout nie trafił…"→ nieobecny (KOMUNIKAT ASERCJI, OdebranieRoliTest:555)
 *
 * ================== DLACZEGO ZAPADKA, A NIE CZERWIEŃ ==================
 *
 * Siedem zdegenerowanych wzorców istnieje DZIŚ; pięć z nich wprowadziła runda 1
 * (commit bcf6fa5), która twierdziła, że tę właśnie klasę zamyka. Naprawa
 * każdego wymaga dopisania komunikatu asercji do konkretnego testu i należy do
 * rundy 2. Zapadka pilnuje jednej rzeczy, której runda 1 nie upilnowała:
 * **liczba nierozróżniających wzorców NIE MOŻE UROSNĄĆ.**
 *
 * ================== CZYM SIĘ RÓŻNI OD PIERWOWZORU ==================
 *
 * W projekcie kont rejestr jest zarazem SPISEM kontrolowanych asercji i ich
 * audytem, więc asercja nieobecna w rejestrze jest dla zapadki niewidzialna
 * (reguła C1: kontrola dzieląca źródło prawdy z przedmiotem nie ma jak go
 * przyłapać). Tutaj spis pochodzi z PARSOWANIA `perturbacje.sh`, czyli z innego
 * źródła niż lista długu — nowe wywołanie `--przyczyna` liczy się samo, bez
 * niczyjego wpisu.
 */
const SUFIT_NIEROZROZNIAJACYCH = 7;

/** @return list<array{wzorzec: string, filtr: ?string, linia: int}> */
function wywolaniaZPrzyczyna(string $skrypt): array
{
    $linie = explode("\n", $skrypt);
    $wynik = [];

    foreach ($linie as $i => $linia) {
        // Linie komentarza opisują SKŁADNIĘ, nie są wywołaniem.
        if (preg_match('/^\s*#/', $linia) === 1) {
            continue;
        }
        if (preg_match('/--przyczyna\s+"([^"]+)"/', $linia, $dopasowanie) !== 1) {
            continue;
        }

        // Polecenie bywa w tej samej linii albo w następnych (kontynuacja `\`).
        $okno = implode("\n", array_slice($linie, $i, 3));
        $filtr = preg_match('/--filter="([^"]+)"/', $okno, $f) === 1 ? $f[1] : null;

        $wynik[] = ['wzorzec' => $dopasowanie[1], 'filtr' => $filtr, 'linia' => $i + 1];
    }

    return $wynik;
}

/** @return list<string> */
function nazwyTestow(string $katalog): array
{
    $nazwy = [];

    foreach (File::allFiles($katalog) as $plik) {
        if ($plik->getExtension() !== 'php') {
            continue;
        }
        preg_match_all(
            '/^\s*(?:it|test)\(\s*([\'"])(.*?)\1/m',
            (string) file_get_contents($plik->getPathname()),
            $trafienia
        );
        foreach ($trafienia[2] as $nazwa) {
            $nazwy[] = $nazwa;
        }
    }

    return $nazwy;
}

/**
 * Wzorzec jest NIEROZRÓŻNIAJĄCY, gdy spełnia się bez zapalenia badanej asercji:
 * gdy jest fragmentem nazwy testu (Pest wypisuje nazwy zawsze) albo gdy jest
 * dosłownie tym samym napisem co `--filter`, czyli nie dokłada nic ponad to,
 * co filtr już zagwarantował.
 *
 * @param  list<string>  $nazwy
 */
function wzorzecNieRozroznia(string $wzorzec, ?string $filtr, array $nazwy): bool
{
    $w = mb_strtolower($wzorzec);

    if ($filtr !== null && mb_strtolower($filtr) === $w) {
        return true;
    }

    foreach ($nazwy as $nazwa) {
        if (str_contains(mb_strtolower($nazwa), $w)) {
            return true;
        }
    }

    return false;
}

it('ZAPADKA: liczba nierozróżniających wzorców --przyczyna nie rośnie', function (): void {
    $sciezka = base_path('../skrypty/perturbacje.sh');
    expect(file_exists($sciezka))->toBeTrue('Nie widzę skryptu perturbacji — kontrola mierzyłaby pustkę.');

    $wywolania = wywolaniaZPrzyczyna((string) file_get_contents($sciezka));
    expect(count($wywolania))->toBeGreaterThan(0, 'Zero wywołań --przyczyna — parser się rozjechał ze skryptem.');

    $nazwy = nazwyTestow(base_path('tests'));
    expect(count($nazwy))->toBeGreaterThan(50, 'Zero nazw testów — parser się rozjechał, kontrola mierzyłaby pustkę.');

    $zdegenerowane = [];
    foreach ($wywolania as $w) {
        if (wzorzecNieRozroznia($w['wzorzec'], $w['filtr'], $nazwy)) {
            $zdegenerowane[] = sprintf(
                'linia %d: --przyczyna "%s"%s',
                $w['linia'],
                $w['wzorzec'],
                $w['filtr'] === $w['wzorzec'] ? ' (IDENTYCZNY z --filter)' : ''
            );
        }
    }

    expect(count($zdegenerowane))->toBeLessThanOrEqual(
        SUFIT_NIEROZROZNIAJACYCH,
        sprintf(
            "PRZYBYŁO nierozróżniających wzorców przyczyny (%d > %d).\n".
            "Wzorzec równy NAZWIE TESTU spełnia się w każdym przebiegu, także zielonym,\n".
            "więc nie odróżnia czerwieni z badanego powodu od czerwieni z dowolnego innego.\n".
            "Użyj KOMUNIKATU ASERCJI, nie nazwy testu i nie wartości --filter.\n%s",
            count($zdegenerowane),
            SUFIT_NIEROZROZNIAJACYCH,
            implode("\n", $zdegenerowane)
        )
    );
});

it('ZAPADKA: sufit jest CIASNY — nie wolno go zostawić z zapasem', function (): void {
    $wywolania = wywolaniaZPrzyczyna((string) file_get_contents(base_path('../skrypty/perturbacje.sh')));
    $nazwy = nazwyTestow(base_path('tests'));

    $ile = 0;
    foreach ($wywolania as $w) {
        if (wzorzecNieRozroznia($w['wzorzec'], $w['filtr'], $nazwy)) {
            $ile++;
        }
    }

    // Bez tego zapadka jest atrapą: sufit z zapasem pozwala dołożyć wzorzec
    // zdegenerowany i nadal świecić zielono. Sufit ma ZJEŻDŻAĆ za długiem.
    expect($ile)->toBe(
        SUFIT_NIEROZROZNIAJACYCH,
        sprintf(
            'Dług spadł do %d, a sufit stoi na %d. OBNIŻ SUFIT do %d — inaczej zapadka '.
            'przepuści ponowne wprowadzenie tej samej wady.',
            $ile,
            SUFIT_NIEROZROZNIAJACYCH,
            $ile
        )
    );
});

it('KIERUNEK ODWROTNY: skaner naprawdę widzi — na materiale zbudowanym pod rękę', function (): void {
    $nazwy = ['odbiera dostęp, gdy Keycloak odbierze rolę', 'POZYTYWNY: żądanie po wylogowaniu'];

    // wzorzec = fragment nazwy testu → MA zostać uznany za nierozróżniający
    expect(wzorzecNieRozroznia('odbiera dostęp', null, $nazwy))->toBeTrue(
        'Skaner NIE widzi wzorca będącego nazwą testu — cała kontrola mierzyłaby pustkę.'
    );

    // wzorzec = komunikat asercji, nieobecny w nazwach → rozróżnia
    expect(wzorzecNieRozroznia('Logout nie trafił w sesję tego użytkownika', 'POZYTYWNY', $nazwy))->toBeFalse(
        'Skaner uznaje komunikat asercji za nierozróżniający — fałszywie oskarżałby poprawne wywołania.'
    );

    // wzorzec identyczny z filtrem → nierozróżniający, nawet gdy nazw nie ma
    expect(wzorzecNieRozroznia('ZASZYFROWANY', 'ZASZYFROWANY', []))->toBeTrue(
        'Skaner nie wykrywa wzorca będącego kopią --filter.'
    );
});
