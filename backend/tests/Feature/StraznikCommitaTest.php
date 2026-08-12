<?php

declare(strict_types=1);

/**
 * STRAŻNIK COMMITA — reguła, która przestała zależeć od pamięci wykonawcy.
 *
 * Wymóg architekta (`ODPOWIEDZ-048`, R-B), po SZEŚCIU złamaniach tej klasy
 * w ciągu jednej sesji 12.08. Żadnego nie złapała kontrola — wszystkie złapał
 * przegląd, czyli przypadek:
 *
 *   1-2. komunikat commita przez `-m` z odwrotnymi apostrofami (dwa razy),
 *   3.   `git add` wciągnął pliki DWÓCH INNYCH SESJI,
 *   4.   `git checkout --` zamiast `cp` przy cofaniu perturbacji,
 *   5.   zestaw zostawił ŻYWĄ MUTACJĘ w drzewie (niepełne zachowanie plików),
 *   6.   `git add` wciągnął artefakt działania (`storage/puls-harmonogramu`).
 *
 * Wcześniej ta sama klasa dała N-10 (żywa perturbacja reguły 24 h w commicie)
 * i N-13 (commit w CUDZYM repozytorium).
 *
 * ⚠ KAŻDY Z TRZECH WARUNKÓW MA WŁASNĄ KONTROLĘ NEGATYWNĄ — bo strażnik, który
 * nie odmawia, jest życzeniem. To zdanie napisałem o cudzych regułach i architekt
 * egzekwuje je teraz wobec mojego strażnika (`ODPOWIEDZ-048`: „egzekwuję je
 * wobec strażnika").
 *
 * Testy wołają hook JAKO PROGRAM, w izolowanym repozytorium tymczasowym.
 * Nie sprawdzają jego treści `grep`-em: kontrola statyczna nie zastępuje
 * uruchomienia — `bash -n` przechodzi, choć wołana podkomenda nie istnieje.
 */

/** Zakłada świeże repozytorium testowe i zwraca jego ścieżkę. */
function repoTestowe(string $nazwa): string
{
    $baza = sys_get_temp_dir().'/straznik-'.$nazwa.'-'.bin2hex(random_bytes(4));

    mkdir($baza.'/'.$nazwa, 0o777, true);

    $repo = $baza.'/'.$nazwa;

    exec('cd '.escapeshellarg($repo).' && git init -q && git config user.email t@t && git config user.name T 2>&1');

    file_put_contents($repo.'/plik.txt', 'tresc');

    return $repo;
}

/**
 * Uruchamia hook w podanym repozytorium i oddaje [kod, wyjście].
 *
 * @return array{0: int, 1: string}
 */
function uruchomStraznika(string $repo): array
{
    $hook = base_path('../skrypty/git-hooks/pre-commit');

    exec(
        'cd '.escapeshellarg($repo).' && bash '.escapeshellarg($hook).' 2>&1',
        $wyjscie,
        $kod
    );

    return [$kod, implode("\n", $wyjscie)];
}

/** Deklaracja zakresu — bez niej strażnik odmawia z definicji. */
function zadeklarujZakres(string $repo, string $nazwaRepo, string ...$prefiksy): void
{
    file_put_contents(
        $repo.'/.zakres-sesji',
        $nazwaRepo."\n".implode("\n", $prefiksy)."\n"
    );
}

it('KONTROLA POZYTYWNA: przy zadeklarowanym zakresie i czystym stanie PRZEPUSZCZA', function (): void {
    // Bez tego trzy kontrole negatywne niżej przechodzą także dla strażnika,
    // który odmawia ZAWSZE — a taki zostałby wyłączony przy pierwszym pośpiechu
    // i nie chroniłby już niczego.
    $repo = repoTestowe('gabinet');
    zadeklarujZakres($repo, 'gabinet', 'plik.txt');

    exec('cd '.escapeshellarg($repo).' && git add plik.txt 2>&1');

    [$kod, $wyjscie] = uruchomStraznika($repo);

    expect($kod)->toBe(
        0,
        "Strażnik odmówił przy poprawnym stanie — czyli odmawia ZAWSZE, a wtedy zostanie ".
        "wyłączony i nie ochroni niczego.\n".$wyjscie
    );
});

it('WARUNEK (a) KONTROLA NEGATYWNA: trwający przebieg pomiarowy ODMAWIA', function (): void {
    // To jest N-10: `git add -A` w trakcie biegnącego zestawu wciągnął do
    // repozytorium ŻYWĄ PERTURBACJĘ reguły 24 h. Pacjent odwołujący dokładnie
    // 24:00:00 przed wizytą tracił prawo do bezpłatnego odwołania.
    $repo = repoTestowe('gabinet');
    zadeklarujZakres($repo, 'gabinet', 'plik.txt');

    mkdir($repo.'/.przebieg-pomiarowy');
    file_put_contents($repo.'/.przebieg-pomiarowy/pid', (string) getmypid());
    file_put_contents($repo.'/.przebieg-pomiarowy/co', 'zestaw perturbacji');
    file_put_contents($repo.'/.przebieg-pomiarowy/od', '2026-08-12 13:00:00');

    [$kod, $wyjscie] = uruchomStraznika($repo);

    expect($kod)->toBe(1, "Strażnik PRZEPUŚCIŁ commit w trakcie przebiegu pomiarowego.\n".$wyjscie);
    expect($wyjscie)->toContain('TRWA PRZEBIEG POMIAROWY');
});

it('WARUNEK (a) ROZRÓŻNIA znacznik OSIEROCONY od żywego', function (): void {
    // Bez tego rozróżnienia strażnik staje się blokadą bez wyjścia: przebieg
    // zabity `kill -9` zostawia znacznik na zawsze, a pierwsza osoba, która
    // się o niego potknie, wyłączy strażnika na stałe.
    $repo = repoTestowe('gabinet');
    zadeklarujZakres($repo, 'gabinet', 'plik.txt');

    mkdir($repo.'/.przebieg-pomiarowy');
    // PID, który na pewno nie żyje — poza zakresem PID-ów systemu.
    file_put_contents($repo.'/.przebieg-pomiarowy/pid', '4194303');
    file_put_contents($repo.'/.przebieg-pomiarowy/co', 'zestaw perturbacji');
    file_put_contents($repo.'/.przebieg-pomiarowy/od', '2026-08-12 13:00:00');

    [$kod, $wyjscie] = uruchomStraznika($repo);

    expect($kod)->toBe(1, 'Osierocony znacznik przepuścił commit — drzewo mogło zostać zmutowane.');
    expect($wyjscie)->toContain('OSIEROCONY');
    expect($wyjscie)->toContain('rm -rf .przebieg-pomiarowy');
});

it('WARUNEK (b) KONTROLA NEGATYWNA: cudze repozytorium ODMAWIA', function (): void {
    // To jest N-13: pisałem i commitowałem w CUDZYM repozytorium, bo został
    // po mnie `cd` z fazy czytania. Przed wypchnięciem uchroniła mnie WYŁĄCZNIE
    // różnica nazw gałęzi — szczęście, nie zabezpieczenie.
    $repo = repoTestowe('helpdesk');
    zadeklarujZakres($repo, 'gabinet', 'plik.txt');

    [$kod, $wyjscie] = uruchomStraznika($repo);

    expect($kod)->toBe(1, "Strażnik PRZEPUŚCIŁ commit w cudzym repozytorium.\n".$wyjscie);
    expect($wyjscie)->toContain('REPOZYTORIUM POZA ZAKRESEM');
});

it('WARUNEK (c) KONTROLA NEGATYWNA: plik spoza zakresu w indeksie ODMAWIA', function (): void {
    // To jest wciągnięcie pracy DWÓCH INNYCH SESJI do mojego commita (12.08)
    // oraz artefaktu działania. `git add` pracuje na STANIE KATALOGU, nie
    // na zamiarze — przy czterech równoległych strumieniach to nie jest
    // teoretyczne ryzyko, tylko zmierzone zdarzenie.
    $repo = repoTestowe('gabinet');
    zadeklarujZakres($repo, 'gabinet', 'backend/');

    mkdir($repo.'/docs', 0o777, true);
    file_put_contents($repo.'/docs/cudze.md', 'praca innej sesji');

    exec('cd '.escapeshellarg($repo).' && git add docs/cudze.md 2>&1');

    [$kod, $wyjscie] = uruchomStraznika($repo);

    expect($kod)->toBe(1, "Strażnik PRZEPUŚCIŁ plik spoza zakresu sesji.\n".$wyjscie);
    expect($wyjscie)->toContain('SPOZA ZAKRESU');
    expect($wyjscie)->toContain('docs/cudze.md');
});

it('BRAK deklaracji zakresu ODMAWIA i zostawia szablon — nieznane = odmowa', function (): void {
    // Deny by default. Bez tego strażnik byłby denylistą: chroniłby tylko przed
    // tym, co ktoś zdążył sobie wyobrazić. Koszt zgodności to jeden plik raz
    // na sesję — dlatego szablon powstaje SAM, żeby koszt wyjątku nie był
    // niższy od kosztu zgodności.
    $repo = repoTestowe('gabinet');

    expect(file_exists($repo.'/.zakres-sesji'))->toBeFalse();

    [$kod, $wyjscie] = uruchomStraznika($repo);

    expect($kod)->toBe(1, 'Brak deklaracji zakresu PRZEPUŚCIŁ commit — to jest fail-open.');
    expect($wyjscie)->toContain('BRAK DEKLARACJI ZAKRESU');
    expect(file_exists($repo.'/.zakres-sesji'))->toBeTrue('Szablon nie powstał — odmowa bez drogi wyjścia.');
});

it('OBEJŚCIE AWARYJNE jest jawne i działa — strażnik bez wyjścia zostanie wyłączony', function (): void {
    // Świadomie ZOSTAWIONE. Strażnik, którego nie da się obejść inaczej niż
    // `--no-verify` (wyłączającym WSZYSTKIE hooki, bez śladu), uczy sięgać
    // po narzędzie szersze i cichsze. `GABINET_STRAZNIK=0` jest wąskie
    // i widoczne w historii powłoki.
    $repo = repoTestowe('helpdesk');
    zadeklarujZakres($repo, 'gabinet', 'plik.txt');

    $hook = base_path('../skrypty/git-hooks/pre-commit');

    exec(
        'cd '.escapeshellarg($repo).' && GABINET_STRAZNIK=0 bash '.escapeshellarg($hook).' 2>&1',
        $wyjscie,
        $kod
    );

    expect($kod)->toBe(0, 'Obejście awaryjne nie działa — strażnik jest blokadą bez wyjścia.');
});
