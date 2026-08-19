<?php

declare(strict_types=1);

/*
 * ⛔ O-6c — WARTOŚĆ O KSZTAŁCIE SEKRETU W `docs/`.
 *
 * Powód powstania: DWA RAZY w tej serii pełna wartość sekretu weszła do historii
 * przez commit DOKUMENTACYJNY (`527f1b7`, `661e8a6`) — raz w raporcie rundy,
 * raz w pliku opisującym jej usuwanie. Za każdym razem złapał to dopiero krok
 * [21] bramki (gitleaks), czyli PO commicie, bo jedyną kontrolą tej klasy jest
 * skaner HISTORII.
 *
 * Ta kontrola pyta o to samo PRZED commitem — i o coś, czego gitleaks nie widzi
 * wcale: wartość o kształcie tokenu, która NIE MA kształtu przypisania
 * (`NAZWA=wartość`). Tak wyglądały identyfikatory sesji w raporcie rundy 6A:
 * gitleaks je przepuszczał, bo stały w wierszu logu, nie w przypisaniu.
 *
 * ================== DLACZEGO KSZTAŁT, A NIE LISTA ==================
 *
 * Lista znanych cytatów rośnie z każdym incydentem i po roku nikt nie wie,
 * które pozycje wciąż mają powód. Kształt nie wymaga utrzymania: nowa wartość
 * o kształcie sekretu zapala kontrolę, choćby nikt jej wcześniej nie widział.
 * To ta sama zasada, którą `SekretyTest` stosuje do `.env.example` (odwrócony
 * ciężar dowodu) i którą R6A-4 wymusił na kontrolach bezpieczeństwa.
 *
 * ================== KALIBRACJA — ZMIERZONA, NIE ZGADNIĘTA ==================
 *
 * Predykat zawężano POMIAREM na rzeczywistym `docs/`, aż dał ZERO fałszywych
 * alarmów (`ZLECENIE-087` §1 — kontrola nadgorliwa zostanie wyłączona przez
 * pierwszą osobę, której zapali bez powodu, i wtedy mamy zero zamiast czegoś):
 *
 *   bez zawężeń                      → 478 trafień (nazwy klas, ścieżki, kod)
 *   + tylko [A-Za-z0-9_-], bez hex   →   3 trafienia (1 realne + 2 nazwy)
 *   + RÓŻNORODNOŚĆ małe/WIELKIE/cyfry →   0 fałszywych, realne dalej zapalają
 *
 * Trzecie zawężenie jest sednem: nazwa wielbłądzia (`WaskieGardloTozsamosciTest`)
 * ma DWIE klasy znaków (małe, wielkie). Token ma TRZY — bo generatory sypią
 * cyframi. To rozróżnia kształt sekretu od kształtu identyfikatora w kodzie
 * bez żadnej listy nazw.
 */

/**
 * Czy wartość ma KSZTAŁT sekretu (nie: czy jest znana).
 *
 * Zawężenia w kolejności, każde z powodem:
 *  1. długość ≥ 24 — krótsze to skróty, wersje, identyfikatory rund;
 *  2. wyłącznie `[A-Za-z0-9_-]` — ukośnik, `::`, `->`, `(`, `$` znaczą kod
 *     albo ścieżkę, nie token;
 *  3. nie czysty hex — to skróty SHA i sumy kontrolne, których w raportach
 *     jest pełno i które MAJĄ tam stać (kotwice pomiarów);
 *  4. RÓŻNORODNOŚĆ: małe ORAZ wielkie ORAZ cyfry — patrz kalibracja wyżej.
 */
function maKsztaltSekretu(string $wartosc): bool
{
    if (mb_strlen($wartosc) < 24) {
        return false;
    }

    if (preg_match('/^[A-Za-z0-9_-]{24,}$/', $wartosc) !== 1) {
        return false;
    }

    if (preg_match('/^[0-9a-f]+$/i', $wartosc) === 1) {
        return false;
    }

    return preg_match('/[a-z]/', $wartosc) === 1
        && preg_match('/[A-Z]/', $wartosc) === 1
        && preg_match('/[0-9]/', $wartosc) === 1;
}

/**
 * Wartości o kształcie sekretu we wszystkich plikach `.md` katalogu `docs/`.
 *
 * @return list<string>  pozycje w postaci `plik:wartość`
 */
function wartosciOKsztalcieSekretu(): array
{
    $korzen = base_path('../docs');

    if (! is_dir($korzen)) {
        return [];
    }

    $trafienia = [];

    /** @var SplFileInfo $plik */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($korzen, FilesystemIterator::SKIP_DOTS)) as $plik) {
        if ($plik->getExtension() !== 'md') {
            continue;
        }

        $tokeny = preg_split('/\s+/', (string) file_get_contents($plik->getPathname())) ?: [];

        foreach ($tokeny as $token) {
            $czysty = trim($token, '`"\'.,:;()[]{}*|<>#');

            if (maKsztaltSekretu($czysty)) {
                $trafienia[] = $plik->getFilename().': '.$czysty;
            }
        }
    }

    return array_values(array_unique($trafienia));
}

it('ŻADEN dokument w `docs/` nie niesie wartości o KSZTAŁCIE sekretu', function (): void {
    // ⛔ LISTA ZNANYCH CYTATÓW — PUSTA, I TO JEST ZMIERZONE.
    //
    // `ZLECENIE-085` §5 i `ODPOWIEDZ-076` §2: lista ma się KURCZYĆ, nie rosnąć,
    // i ma mieć TERMIN. Dziś jest pusta, bo wszystkie znane cytaty zostały
    // SKRÓCONE u źródła (dwa incydenty cytatu sekretu + identyfikatory sesji
    // w raporcie rundy 6A, skrócone 19.08).
    //
    // TERMIN, gdyby kiedyś urosła: każda pozycja znika przy najbliższym
    // przepisaniu historii tej gałęzi (ten sam termin co D-4/D-5), a do tego
    // czasu każda musi nieść POWÓD. Lista bez terminu jest długiem udającym
    // kontrolę.
    /** @var array<string, string> $znaneCytaty */
    $znaneCytaty = [];

    $trafienia = array_values(array_filter(
        wartosciOKsztalcieSekretu(),
        fn (string $wpis): bool => ! isset($znaneCytaty[$wpis])
    ));

    expect($trafienia)->toBe([], sprintf(
        'WARTOŚĆ O KSZTAŁCIE SEKRETU W DOKUMENTACJI:%s  %s%s%s'.
        'gitleaks tego NIE ZŁAPIE, jeśli wartość nie stoi w przypisaniu `NAZWA=…` — '.
        'a i tak złapałby dopiero PO commicie, bo skanuje historię. Skróć wartość '.
        'u źródła (prefiks + ogon wystarczą do rozpoznania) albo dopisz ją do '.
        '`$znaneCytaty` Z POWODEM i terminem.',
        PHP_EOL,
        implode(PHP_EOL.'  ', $trafienia),
        PHP_EOL,
        PHP_EOL
    ));
});

it('KIERUNEK ODWROTNY: predykat kształtu ZAPALA na wzorcach, MILCZY na nazwach z kodu', function (): void {
    // Bez tej kontroli „zero trafień" znaczyłoby tylko tyle, że predykat nie
    // umie szukać — lekcja z martwego literału `'\$_GET'` (runda 10).
    //
    // Wzorce: obie wartości z incydentów tej serii (skrócone w drzewie, więc
    // tu odtwarzam ich KSZTAŁT, nie treść) plus identyfikator sesji z raportu
    // rundy 6A, który gitleaks przepuszczał.
    $musiZapalic = [
        'przedrostek + hex' => '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5c6d',
        'base64 przynęty' => 'aGVsbG8td29ybGQtdGhpcy1pcy1hLXNlY3JldA',
        'identyfikator sesji' => '55wUqZ3PNcXyRByWbhCvY1qyONlSZzHP75tIWcWQ',
    ];

    foreach ($musiZapalic as $etykieta => $wartosc) {
        expect(maKsztaltSekretu($wartosc))->toBeTrue(
            "Predykat NIE widzi wzorca `$etykieta` — kontrola mierzyłaby pustkę."
        );
    }

    // Kontrola przyrządu w drugą stronę: to, czego w dokumentacji jest pełno
    // i co MA tam stać. Nadgorliwa kontrola zostanie wyłączona przy pierwszym
    // fałszywym alarmie — a wtedy nie chroni już niczego.
    $musiMilczec = [
        'nazwa klasy testowej' => 'WaskieGardloTozsamosciTest',
        'nazwa metody refleksji' => 'newInstanceWithoutConstructor',
        'nazwa kontrolera' => 'BackchannelLogoutController',
        'nazwa gałęzi z datą' => 'kopia-przed-filtrem-12-08',
        'skrót SHA' => 'b60c53a64219b1b81d5be461ffeb23b3622a9749',
        'ścieżka pliku' => 'backend/app/Tozsamosc/OdswiezanieSesji.php',
    ];

    foreach ($musiMilczec as $etykieta => $wartosc) {
        expect(maKsztaltSekretu($wartosc))->toBeFalse(
            "Predykat OSKARŻA `$etykieta` (`$wartosc`) — to fałszywy alarm. Kontrola ".
            'nadgorliwa zostanie wyłączona przez pierwszą osobę, której zapali bez '.
            'powodu, i wtedy mamy zero zamiast czegoś.'
        );
    }
});
