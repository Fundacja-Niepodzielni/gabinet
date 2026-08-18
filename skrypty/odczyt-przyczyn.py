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
import shutil
import subprocess
import sys

NL = chr(10)

# KORZEN ZE SCIEZKI WLASNEJ, nie z literalu (R7-7).
#
# Do 12.08 stalo tu `D:/KOD/Niepodzielni/gabinet` — sciezka jednego
# konkretnego drzewa. Odpalony z klonu weryfikatora albo z worktree ten
# skrypt czytal i URUCHAMIAL cudzy kod, meldujac wynik jako swoj.
# Ta sama klasa co `core.hooksPath` wskazujacy w pustke poza worktree
# glownym: narzedzie zna JEDNO drzewo, a dziala w wielu.
KORZEN = os.path.dirname(os.path.dirname(os.path.abspath(__file__))).replace(os.sep, chr(47))
SKRYPT = KORZEN + '/skrypty/perturbacje.sh'
RAPORT = KORZEN + '/dowody/odczyt-przyczyn.txt'

# --- IZOLACJA STOSU (R7-7) ------------------------------------------------
#
# Ten skrypt URUCHAMIA polecenia wyjete z `perturbacje.sh`. Sklejal je jako
# gole 'docker compose ' + reszta — czyli gubil `-p`, `--env-file` i caly
# zestaw `GABINET_*`, ktore `dc()` w tamtym skrypcie podstawia. Efekt: pelne
# suity Pest szly na projekt DOMYSLNY, ktory `perturbacje.sh:81` odmawia
# obslugiwac, bo to stos dewelopera.
#
# Odmowa musi byc tutaj wlasna: kod, ktory sklada polecenie, jest tutaj.
PROJEKTY_ZABRONIONE = ('gabinet', 'dev')
PROJEKT = os.environ.get('GABINET_PERTURBACJE_PROJEKT', 'gabinet-perturbacje')

if PROJEKT in PROJEKTY_ZABRONIONE:
    sys.exit(
        'ODMOWA: odczyt dynamiczny nie dziala na projekcie %r — to stos dewelopera.' % PROJEKT
        + NL + 'Uruchamia PELNE suity Pest; jego bazy i portow nie zajmujemy.'
    )

PORT_HTTP = os.environ.get('GABINET_PERTURBACJE_PORT_HTTP', '8097')
PORT_PG = os.environ.get('GABINET_PERTURBACJE_PORT_POSTGRES', '55444')
PORT_REDIS = os.environ.get('GABINET_PERTURBACJE_PORT_REDIS', '56391')


def interpreter_powloki():
    """Bash, ktory WIDZI to repozytorium — wybrany testem, nie po nazwie.

    Zmierzone 12.08: `subprocess.run(['bash', ...])` na tej maszynie trafia
    w bash WSL-a (`C:/Windows/System32/bash.exe`), ktory odpowiada
    „execvpe(/bin/bash) failed" i nie umie otworzyc sciezki `D:/...`.
    `shutil.which()` wskazuje przy tym Git Bash — czyli SAMA NAZWA nie
    rozstrzyga, ktory program dostaniemy.

    Dlatego kandydat jest przyjmowany dopiero wtedy, gdy potrafi ZOBACZYC
    plik bramki pod sciezka, ktorej uzyjemy. To pytanie o zdolnosc, nie
    o tozsamosc — i tylko ono odroznia te dwa interpretery.
    """
    kandydaci = [
        os.environ.get('GABINET_BASH'),
        os.environ.get('SHELL'),
        (os.environ.get('EXEPATH') or '') + '/bash.exe',
        shutil.which('bash'),
        '/bin/bash',
    ]
    probny = KORZEN + '/skrypty/bramka.sh'

    for kandydat in kandydaci:
        if not kandydat:
            continue
        try:
            proc = subprocess.run(
                [kandydat, '-c', 'test -f "$1"', '_', probny],
                stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=30
            )
        except Exception:
            continue
        if proc.returncode == 0:
            return kandydat

    sys.exit(
        'ODMOWA: nie znalazlem powloki bash, ktora widzi %s.' % probny
        + NL + 'Wskaz ja jawnie: GABINET_BASH="C:/Program Files/Git/bin/bash.exe".'
    )


def plik_srodowiska():
    """Sciezke pliku srodowiska podaje SAMA BRAMKA — bez drugiego wzoru na nazwe.

    Drugi opis jednej rzeczy rozjezdza sie po cichu (lekcja `licz-testy.sh`).
    Pliku tu NIE BUDUJEMY: ten skrypt jest ODCZYTEM i nie ma prawa stawiac
    ani przygotowywac srodowiska. Gdy pliku nie ma, stos perturbacji nie stoi
    i odczyt dynamiczny jest nierozstrzygniety — co jest uczciwym wynikiem,
    a nie awaria do obejscia.
    """
    try:
        proc = subprocess.run(
            [interpreter_powloki(), KORZEN + '/skrypty/bramka.sh',
             '--projekt', PROJEKT, '--pokaz-srodowisko'],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=120
        )
    except Exception as e:
        sys.exit('ODMOWA: nie umiem zapytac bramki o plik srodowiska: %s' % e)

    sciezka = proc.stdout.decode('utf-8', errors='replace').strip()

    # Bramka mowi sciezka POSIX (`/d/KOD/...`), bo sama biegnie w MSYS.
    # Python na Windows jej nie otworzy, a `docker compose --env-file`
    # potrzebuje postaci natywnej — to ta sama klasa co MSYS_NO_PATHCONV
    # w naglowku `perturbacja-odwrotna.sh`: przyrzad psuje pomiar, zanim
    # pomiar sie zacznie. Konwertujemy TYM SAMYM interpreterem, ktory
    # sciezke podal — inaczej mielibysmy dwa zdania o jednym pliku.
    if sciezka.startswith(chr(47)):
        try:
            konw = subprocess.run(
                [interpreter_powloki(), '-c', 'cygpath -w "$1"', '_', sciezka],
                stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=30
            )
            if konw.returncode == 0:
                nowa = konw.stdout.decode('utf-8', errors='replace').strip()
                if nowa:
                    sciezka = nowa.replace(chr(92), chr(47))
        except Exception:
            pass

    if not sciezka:
        sys.exit('ODMOWA: bramka nie podala sciezki pliku srodowiska dla projektu %r' % PROJEKT)
    if not os.path.isfile(sciezka) or os.path.getsize(sciezka) == 0:
        sys.exit(
            'ODMOWA: brak pliku srodowiska %s.' % sciezka
            + NL + 'Bez niego compose zamontowalby ./.env DEWELOPERA z prawdziwymi sekretami (R6B-16).'
            + NL + 'Postaw stos perturbacji: bash skrypty/perturbacje.sh --lista, potem dowolny scenariusz.'
        )
    return sciezka


PLIK_ENV = plik_srodowiska()

# Prefiks POWTARZA to, co robi `dc()` w `perturbacje.sh` — i to jest
# swiadome powtorzenie, nie kopia przez niedopatrzenie: tamto `dc()` jest
# funkcja powloki wewnatrz skryptu z wlasna klamra, wiec nie da sie go
# zaimportowac bez uruchomienia calego przebiegu. Zgodnosci obu miejsc
# pilnuje kontrola w `KlamraSkryptowTest`.
PREFIKS_DC = (
    'docker compose --env-file "' + PLIK_ENV + '"'
    + ' -p "' + PROJEKT + '"'
    + ' -f "' + KORZEN + '/docker-compose.yml"'
)

SRODOWISKO = dict(os.environ)
SRODOWISKO.update({
    'GABINET_PREFIX': PROJEKT,
    'GABINET_PORT_HTTP': PORT_HTTP,
    'GABINET_PORT_POSTGRES': PORT_PG,
    'GABINET_PORT_REDIS': PORT_REDIS,
    'GABINET_PLIK_ENV': PLIK_ENV,
    'MSYS_NO_PATHCONV': '1',
    'MSYS2_ARG_CONV_EXCL': '*',
})


def wczytaj_nazwy_testow():
    """SLOWNIK ZIELONEGO PRZEBIEGU — material odczytu STATYCZNEGO (R7-8).

    Nie tylko nazwy `it()/test()`, ale wszystko, co raporter Pest wypisuje
    NIEZALEZNIE od tego, czy badana asercja zapalila: nazwy klas w naglowku
    (`PASS  Tests\\Feature\\BramkiTest`) i wlasne stale raportera.

    Zmierzone 12.08 na przebiegu ZIELONYM — nie zdedukowane z formatu.
    """
    slownik = []
    wz = re.compile(r"^\\s*(?:it|test|describe)\\(\\s*(['\\\"])(.*?)\\1", re.M)
    katalog = KORZEN + '/backend/tests'

    for korzen, _, pliki in os.walk(katalog):
        for p in pliki:
            if not p.endswith('.php'):
                continue
            pelna = os.path.join(korzen, p)
            tresc = io.open(pelna, encoding='utf-8').read()
            for m in wz.finditer(tresc):
                slownik.append(m.group(2))

            wzgledna = os.path.relpath(pelna, katalog).replace(os.sep, chr(47))
            slownik.append('Tests' + chr(92) + wzgledna[:-4].replace(chr(47), chr(92)))

    slownik.extend(['PASS', 'FAIL', 'Tests:', 'Duration:', 'assertions',
                    'passed', 'failed', 'WARN', 'SKIPPED'])

    return slownik


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
        polecenie = (PREFIKS_DC + ' ' + mp.group(1)) if mp else None

        wynik.append({'nr': nr, 'wzorzec': wzorzec, 'filtr': filtr, 'polecenie': polecenie})
    return wynik


def galezie_alternatywy(wzorzec):
    """Galezie ERE — `grep -qiE` spelnia sie, gdy pasuje DOWOLNA (R7-8).

    Dzielimy na kazdym `|` poza klasa znakow i poza ucieczka, takze wewnatrz
    nawiasow grupujacych. Nadmiarowy podzial czyni odczyt SUROWSZYM;
    pominiecie galezi czyni go slepym.
    """
    galezie, biezaca, w_klasie, ucieczka = [], '', False, False

    for znak in wzorzec:
        if ucieczka:
            biezaca += znak
            ucieczka = False
        elif znak == chr(92):
            biezaca += znak
            ucieczka = True
        elif w_klasie:
            biezaca += znak
            w_klasie = znak != ']'
        elif znak == '[':
            w_klasie = True
            biezaca += znak
        elif znak == '|':
            galezie.append(biezaca)
            biezaca = ''
        else:
            biezaca += znak

    galezie.append(biezaca)

    return galezie


def statyczny(wzorzec, filtr, slownik):
    """Wzorzec ROZROZNIA, gdy zadna galaz nie stoi w slowniku zielonego przebiegu.

    R7-8: ten odczyt mial DOKLADNIE te sama slepote co zapadka
    `PrzyczynyPerturbacjiTest` — i nie przypadkiem, bo powstal z niej przez
    przepisanie. Naprawa jednej strony bez drugiej zostawilaby dwa przyrzady
    mierzace TE SAMA wlasnosc i podajace rozne wyniki, a rozbieznosc miedzy
    nimi jest tu z definicji ZNALEZISKIEM — wiec produkowalaby falszywe.
    """
    if filtr is not None and filtr.lower() == wzorzec.lower():
        return False, 'wzorzec IDENTYCZNY z --filter'

    for galaz in galezie_alternatywy(wzorzec):
        if re.match(r'^[.*+?^$()\\s]*$', galaz):
            return False, 'galaz %r pasuje do KAZDEGO wyjscia' % galaz

        g = galaz.strip().replace('(', '').replace(')', '').lower()
        if not g:
            continue

        for napis in slownik:
            if g in napis.lower():
                return False, 'galaz %r jest fragmentem napisu stalego: %r' % (galaz, napis)

    return True, 'zadna galaz nie wystepuje w slowniku zielonego przebiegu'


def bez_mutacji_w_poleceniu(polecenie):
    """Usuwa `-e VAR=...`, czyli MUTACJE WNIESIONA PRZEZ SAMO POLECENIE.

    ZNALEZISKO WLASNE 12.08, przy pierwszym przebiegu po naprawie R7-7.
    Ten odczyt zakladal, ze mutacja mieszka zawsze w DRZEWIE, a polecenie jest
    neutralnym obserwatorem. Dla perturbacji `p_sesja` nieprawda: mutacja to
    `-e SESSION_ENCRYPT=false` wpisane w samo wywolanie. Czytnik uruchamial
    wiec swiat JUZ ZMUTOWANY, dostawal czerwien i meldowal ja jako „GALAZ
    BAZOWA (R6B-13) — ta perturbacja nie moze niczego dowiesc".

    To bylo FALSZYWE OSKARZENIE sprawnej perturbacji, i to oskarzenie o wade
    powazna. Przyrzad mierzyl nie ten swiat, o ktory pytal.

    Baze odzyskujemy, zdejmujac z polecenia jego wlasna mutacje.
    """
    return re.sub(r'\s-e\s+[A-Za-z_][A-Za-z0-9_]*=\S*', '', polecenie)


def dynamiczny(wpis):
    """Uruchamia polecenie na swiecie NIEZMUTOWANYM i szuka wzorca w wyjsciu."""
    if not wpis['polecenie']:
        return None, 'nie umiem wydobyc polecenia — NIEROZSTRZYGNIETE', None
    try:
        proc = subprocess.run(
            bez_mutacji_w_poleceniu(wpis['polecenie']), shell=True, cwd=KORZEN, env=SRODOWISKO,
            stdout=subprocess.PIPE, stderr=subprocess.STDOUT, timeout=600
        )
    except Exception as e:
        return None, 'polecenie nie dalo sie uruchomic: %s' % e, None

    wyjscie = proc.stdout.decode('utf-8', errors='replace')
    if not wyjscie.strip():
        return None, 'PUSTE WYJSCIE (pustka to blad, nie zero) — NIEROZSTRZYGNIETE', None

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
        return None, 'przebieg NIE BYL zielony (kod %d) — odczyt dynamiczny NIEROZSTRZYGNIETY: %s' % (proc.returncode, opis), False

    # rozroznia <=> wzorzec NIEOBECNY w zielonym
    return (not obecny), opis, zielony


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
    # GALAZ BAZOWA (R6B-13): perturbacja celujaca w polecenie, ktore JUZ JEST
    # czerwone na kodzie NIEZMUTOWANYM, nie moze niczego dowiesc — jej czerwien
    # przyjdzie zawsze, niezaleznie od mutacji.
    baza_czerwona = []

    for wpis in wpisy:
        st, st_op = statyczny(wpis['wzorzec'], wpis['filtr'], nazwy)
        dy, dy_op, baza_zielona = dynamiczny(wpis)

        if dy is None:
            kat = 'NIEROZSTRZYGNIETE'
        elif st == dy:
            kat = 'ZGODNE-ROZROZNIA' if st else 'ZGODNE-NIE-ROZROZNIA'
        elif st is False and dy is True:
            kat = 'ROZBIEZNOSC-A'   # statyczny odrzuca, dynamiczny przyjmuje
        else:
            kat = 'ROZBIEZNOSC-B'   # statyczny przyjmuje, dynamiczny odrzuca  <-- NAJGROZNIEJSZY
        kategorie[kat].append(wpis['nr'])
        if baza_zielona is False:
            baza_czerwona.append(wpis['nr'])

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
    w('GALAZ BAZOWA (R6B-13) — polecenia JUZ CZERWONE na kodzie niezmutowanym: %d   linie: %s'
      % (len(baza_czerwona), baza_czerwona))
    if baza_czerwona:
        w('   Te perturbacje NIE MOGA NICZEGO DOWIESC: ich czerwien przychodzi zawsze,')
        w('   niezaleznie od mutacji. Zawez je --filter albo napraw przyczyne czerwieni.')
    else:
        w('   Kazde badane polecenie jest ZIELONE na kodzie niezmutowanym, wiec czerwien')
        w('   po mutacji da sie przypisac mutacji. To jest galaz bazowa, ktorej brakowalo.')
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
