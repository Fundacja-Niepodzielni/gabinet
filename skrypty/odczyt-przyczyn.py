# -*- coding: utf-8 -*-
"""DWA ODCZYTY tej samej wlasnosci: czy wzorzec --przyczyna ROZROZNIA przyczyne czerwieni.

ODCZYT STATYCZNY  — wzorzec vs nazwy testow (parsowane z plikow) i vs wartosc --filter.
                    Nie uruchamia przedmiotu. Nie zalezy od srodowiska.
ODCZYT DYNAMICZNY — uruchom polecenie na kodzie NIEZMUTOWANYM (przebieg ZIELONY)
                    i sprawdz, czy wzorzec wystepuje w wyjsciu. Widzi POWODY
                    obecnosci, o ktorych statyczny nie wie (sciezki, nazwy zestawow
                    danych, naglowki runnera), ale mierzy tez SRODOWISKO, ktore
                    to wyjscie formatuje (np. ucinanie nazw przez Pest).

Rozstrzygniecie przyjete od kont (ZLECENIE-009):
  STATYCZNY jest WIAZACY, DYNAMICZNY jest ODKRYWCZY,
  a ROZBIEZNOSC miedzy nimi jest ZNALEZISKIEM, nie szumem.

Dlatego ten skrypt NIE zwraca werdyktu "czerwony/zielony". Zwraca TABELE z czterema
kategoriami, w tym dwiema kategoriami rozbieznosci — osobno, bo znacza co innego.
"""
import io
import os
import re
import subprocess
import sys

KORZEN = 'D:/KOD/Niepodzielni/gabinet'
SKRYPT = KORZEN + '/skrypty/perturbacje.sh'
RAPORT = KORZEN + '/dowody/odczyt-przyczyn.txt'

NL = chr(10)


def wczytaj_nazwy_testow():
    """Nazwy testow z it()/test() — material odczytu STATYCZNEGO."""
    nazwy = []
    wz = re.compile(r"^\s*(?:it|test)\(\s*(['\"])(.*?)\1", re.M)
    for korzen, _, pliki in os.walk(KORZEN + '/backend/tests'):
        for p in pliki:
            if not p.endswith('.php'):
                continue
            tresc = io.open(os.path.join(korzen, p), encoding='utf-8').read()
            for m in wz.finditer(tresc):
                nazwy.append(m.group(2))
    return nazwy


def wywolania():
    """Sklada logiczne wywolania oczekuj_czerwone (kontynuacje backslashem)."""
    linie = io.open(SKRYPT, encoding='utf-8').read().split(NL)
    wynik = []
    i = 0
    while i < len(linie):
        l = linie[i]
        if 'oczekuj_czerwone' not in l or l.lstrip().startswith('#'):
            i += 1
            continue
        nr = i + 1
        czesci = []
        while i < len(linie):
            biezaca = linie[i]
            czesci.append(biezaca.rstrip(chr(92)).strip())
            if not biezaca.rstrip().endswith(chr(92)):
                break
            i += 1
        i += 1
        cale = ' '.join(czesci)

        m = re.search(r'--przyczyna\s+"([^"]+)"', cale)
        if not m:
            continue
        wzorzec = m.group(1)
        mf = re.search(r'--filter="([^"]+)"', cale)
        filtr = mf.group(1) if mf else None

        # polecenie = wszystko po pierwszym "dc " (funkcja z perturbacje.sh)
        mp = re.search(r'\bdc\s+(.*)$', cale)
        polecenie = ('docker compose ' + mp.group(1)) if mp else None

        wynik.append({'nr': nr, 'wzorzec': wzorzec, 'filtr': filtr, 'polecenie': polecenie})
    return wynik


def statyczny(wzorzec, filtr, nazwy):
    w = wzorzec.lower()
    if filtr is not None and filtr.lower() == w:
        return False, 'wzorzec IDENTYCZNY z --filter'
    for n in nazwy:
        if w in n.lower():
            return False, 'wzorzec jest fragmentem NAZWY TESTU'
    return True, 'wzorzec nieobecny w nazwach testow i rozny od --filter'


def dynamiczny(wpis):
    """Uruchamia polecenie na kodzie NIEZMUTOWANYM i szuka wzorca w wyjsciu."""
    if not wpis['polecenie']:
        return None, 'nie umiem wydobyc polecenia — NIEROZSTRZYGNIETE'
    try:
        proc = subprocess.run(
            wpis['polecenie'], shell=True, cwd=KORZEN,
            stdout=subprocess.PIPE, stderr=subprocess.STDOUT, timeout=600
        )
    except Exception as e:
        return None, 'polecenie nie dalo sie uruchomic: %s' % e

    wyjscie = proc.stdout.decode('utf-8', errors='replace')
    if not wyjscie.strip():
        return None, 'PUSTE WYJSCIE (pustka to blad, nie zero) — NIEROZSTRZYGNIETE'

    # Ten sam mechanizm co w oczekuj_czerwone: grep -qiE
    try:
        obecny = re.search(wpis['wzorzec'], wyjscie, re.I) is not None
    except re.error:
        obecny = wpis['wzorzec'].lower() in wyjscie.lower()

    # kod wyjscia mowi, czy przebieg BYL zielony — bez tego nie wiadomo, co zmierzylismy
    zielony = (proc.returncode == 0)
    opis = 'przebieg %s (kod %d), wzorzec %s w wyjsciu' % (
        'ZIELONY' if zielony else 'CZERWONY', proc.returncode,
        'OBECNY' if obecny else 'nieobecny'
    )
    if not zielony:
        return None, 'przebieg NIE BYL zielony (kod %d) — odczyt dynamiczny NIEROZSTRZYGNIETY: %s' % (proc.returncode, opis)

    # rozroznia <=> wzorzec NIEOBECNY w zielonym
    return (not obecny), opis


def main():
    nazwy = wczytaj_nazwy_testow()
    wpisy = wywolania()
    if not wpisy:
        sys.exit('ZERO wywolan — parser sie rozjechal ze skryptem')
    if len(nazwy) < 50:
        sys.exit('ZERO/za malo nazw testow (%d) — parser sie rozjechal' % len(nazwy))

    buf = []
    def w(s=''):
        buf.append(s)

    w('=' * 100)
    w('DWA ODCZYTY WZORCOW --przyczyna  ·  statyczny WIAZACY, dynamiczny ODKRYWCZY')
    w('=' * 100)
    w('nazw testow w materiale statycznym: %d   ·   wywolan do zbadania: %d' % (len(nazwy), len(wpisy)))
    w('')

    kategorie = {'ZGODNE-NIE-ROZROZNIA': [], 'ZGODNE-ROZROZNIA': [],
                 'ROZBIEZNOSC-A': [], 'ROZBIEZNOSC-B': [], 'NIEROZSTRZYGNIETE': []}

    for wpis in wpisy:
        st, st_op = statyczny(wpis['wzorzec'], wpis['filtr'], nazwy)
        dy, dy_op = dynamiczny(wpis)

        if dy is None:
            kat = 'NIEROZSTRZYGNIETE'
        elif st == dy:
            kat = 'ZGODNE-ROZROZNIA' if st else 'ZGODNE-NIE-ROZROZNIA'
        elif st is False and dy is True:
            kat = 'ROZBIEZNOSC-A'   # statyczny odrzuca, dynamiczny przyjmuje
        else:
            kat = 'ROZBIEZNOSC-B'   # statyczny przyjmuje, dynamiczny odrzuca  <-- NAJGROZNIEJSZY
        kategorie[kat].append(wpis['nr'])

        w('-' * 100)
        w('linia %-5d  [%s]' % (wpis['nr'], kat))
        w('   przyczyna : %s' % wpis['wzorzec'])
        w('   filter    : %s' % wpis['filtr'])
        w('   STATYCZNY : %-10s  %s' % ('rozroznia' if st else 'NIE rozroznia', st_op))
        w('   DYNAMICZNY: %-10s  %s' % (
            ('rozroznia' if dy else 'NIE rozroznia') if dy is not None else 'nieznany', dy_op))
        print('linia %d -> %s' % (wpis['nr'], kat))

    w('')
    w('=' * 100)
    w('PODSUMOWANIE')
    w('=' * 100)
    for k in ('ZGODNE-NIE-ROZROZNIA', 'ZGODNE-ROZROZNIA', 'ROZBIEZNOSC-A', 'ROZBIEZNOSC-B', 'NIEROZSTRZYGNIETE'):
        w('%-22s : %2d   linie: %s' % (k, len(kategorie[k]), kategorie[k]))
    w('')
    w('ROZBIEZNOSC-A = statyczny NIE rozroznia, dynamiczny rozroznia.')
    w('   Wzorzec JEST nazwa testu, ale do wyjscia nie dociera — u nas przez UCINANIE')
    w('   nazw przez Pest. Rozroznialnosc zalezna od szerokosci wyjscia nie jest wlasnoscia')
    w('   kontroli, tylko srodowiska. Wiazacy jest STATYCZNY: traktujemy jako NIE rozroznia.')
    w('')
    w('ROZBIEZNOSC-B = statyczny rozroznia, dynamiczny NIE.  <-- NAJGROZNIEJSZY')
    w('   Wzorzec wyglada dobrze (nie jest nazwa testu, rozny od --filter), a MIMO TO')
    w('   jest w wyjsciu zielonego przebiegu — z powodu, o ktorym statyczny nie wie:')
    w('   sciezka pliku, nazwa zestawu danych, naglowek runnera. Tego statyczny NIE ZLAPIE')
    w('   NIGDY. Kazda pozycja tutaj to ZNALEZISKO.')

    io.open(RAPORT, 'w', encoding='utf-8', newline=NL).write(NL.join(buf) + NL)
    print('RAPORT: ' + RAPORT)


main()
