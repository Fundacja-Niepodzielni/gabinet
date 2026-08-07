#!/usr/bin/env python3
"""Mutacje plików dla `skrypty/perturbacje.sh`.

Po co osobny plik zamiast `sed` w skrypcie: perturbacje podkładają całe
fragmenty kodu PHP — z ukośnikami wstecznymi w przestrzeniach nazw, znakami
dolara i cudzysłowami. W zagnieżdżonych heredokach basha każdy z tych znaków
wymaga innego poziomu ucieczki i pomyłka jest cicha: `sed` nic nie znajduje,
perturbacja „przechodzi", a my dowiadujemy się, że kontrola nic nie sprawdza.
Zdarzyło się to dwa razy, zanim ten plik powstał.

Użycie:
    python3 skrypty/perturbuj.py hasla-podloz
    python3 skrypty/perturbuj.py nonce-fail-open
"""

from __future__ import annotations

import io
import sys
from pathlib import Path

KORZEN = Path(__file__).resolve().parent.parent

MIGRACJA = KORZEN / "backend/database/migrations/0001_01_01_000000_create_users_table.php"
TRASY = KORZEN / "backend/routes/web.php"
MODEL = KORZEN / "backend/app/Models/Personel.php"
WALIDATOR = KORZEN / "backend/app/Tozsamosc/WalidatorTokenu.php"


def czytaj(sciezka: Path) -> str:
    return io.open(sciezka, encoding="utf-8").read()


def pisz(sciezka: Path, tresc: str) -> None:
    io.open(sciezka, "w", encoding="utf-8", newline="\n").write(tresc)


def podmien(sciezka: Path, stare: str, nowe: str) -> None:
    """Podmiana, która KRZYCZY, gdy wzorca nie ma.

    Cicha podmiana bez trafienia to najgorszy możliwy wynik: perturbacja
    zgłasza sukces, nie zmieniwszy niczego.
    """
    tresc = czytaj(sciezka)

    if stare not in tresc:
        raise SystemExit(f"PERTURBACJA NIEUDANA: nie znaleziono wzorca w {sciezka.name}:\n{stare[:120]}")

    pisz(sciezka, tresc.replace(stare, nowe, 1))


def hasla_podloz() -> None:
    """Odtworzenie ataku niezależnego weryfikatora: pełny mechanizm haseł.

    Pierwsza wersja `BrakWlasnychHaselTest` sprawdzała literalne nazwy
    (`password`, `remember_token`, `password_reset_tokens`, model `User`)
    i przepuściła KOMPLET poniżej — cała bramka była wtedy zielona.
    """
    # 1. kolumny hasła i „zapamiętaj mnie" pod polskimi nazwami
    podmien(
        MIGRACJA,
        "$table->string('email')->nullable();",
        "$table->string('email')->nullable();\n"
        "            $table->string('haslo_hash');\n"
        "            $table->string('token_zapamietania')->nullable();",
    )

    # 2. osobna tabela kont lokalnych i tabela resetu
    podmien(
        MIGRACJA,
        "        Schema::create('sessions', function (Blueprint $table): void {",
        "        Schema::create('konta_lokalne', function (Blueprint $table): void {\n"
        "            $table->id();\n"
        "            $table->string('hash_hasla');\n"
        "        });\n\n"
        "        Schema::create('zetony_resetu', function (Blueprint $table): void {\n"
        "            $table->string('email')->primary();\n"
        "            $table->string('zeton');\n"
        "        });\n\n"
        "        Schema::create('sessions', function (Blueprint $table): void {",
    )

    # 3. drugi model, zdolny do uwierzytelniania hasłem
    pisz(
        MODEL,
        "<?php\n\ndeclare(strict_types=1);\n\n"
        "namespace App" + chr(92) + "Models;\n\n"
        "use Illuminate" + chr(92) + "Foundation" + chr(92) + "Auth" + chr(92) + "User as Authenticatable;\n\n"
        "class Personel extends Authenticatable\n{\n"
        "    protected $table = 'konta_lokalne';\n}\n",
    )

    # 4. trasy w polskiej odmianie — `haslo` ich nie łapie, `hasl` tak
    pisz(
        TRASY,
        czytaj(TRASY)
        + "\nRoute::get('/reset-hasla', fn () => 'perturbacja');\n"
        + "Route::post('/zaloguj-haslem', fn () => 'perturbacja');\n",
    )


def hasla_sprzataj() -> None:
    MODEL.unlink(missing_ok=True)


def nonce_fail_open() -> None:
    """Przywraca zachowanie sprzed naprawy: kontrola MILCZY zamiast paść."""
    podmien(
        WALIDATOR,
        "if (array_key_exists('nonce', $opcje)) {",
        "if (array_key_exists('nonce', $opcje) && $opcje['nonce'] !== null) {",
    )


POLECENIA = {
    "hasla-podloz": hasla_podloz,
    "hasla-sprzataj": hasla_sprzataj,
    "nonce-fail-open": nonce_fail_open,
}


if __name__ == "__main__":
    if len(sys.argv) != 2 or sys.argv[1] not in POLECENIA:
        raise SystemExit(f"użycie: perturbuj.py [{' | '.join(POLECENIA)}]")

    POLECENIA[sys.argv[1]]()
