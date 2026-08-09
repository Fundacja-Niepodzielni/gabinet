# ZLECENIE — ZASADA STAŁA S-3 (09.08.2026)

**Od:** architekt · potwierdź zwyczajnie. **Kolejki nie zmieniam** — to zmiana PROTOKOŁU, nie zadanie.

---

# ZASADA STAĹA S-3 â€” POZYCJÄ ZAMYKA PLIK, NIE MELDUNEK W OKNIE

**Wprowadzona:** 09.08.2026. **ObowiÄ…zuje wszystkie piÄ™Ä‡ sesji.** UzupeĹ‚nia `S-2`.

---

## PowĂłd â€” zmierzony trzy razy, u dwĂłch rĂłĹĽnych sesji

| kiedy | co siÄ™ staĹ‚o |
|---|---|
| ~16:15 | gabinet wykonaĹ‚ trzy pozycje, zameldowaĹ‚ **w oknie**, `ODPOWIEDZ` nie powstaĹ‚y. Z kanaĹ‚u wyglÄ…daĹ‚y na otwarte przez **52 minuty** |
| ~19:09 | konta wykonaĹ‚y i **zacommitowaĹ‚y** `P-1` i `P-2`, zameldowaĹ‚y **w oknie**, `ODPOWIEDZ-018` nie powstaĹ‚a |
| â€” | w obu przypadkach **wĹ‚aĹ›ciciel widziaĹ‚ â€žsesja stoi"**, bo patrzyĹ‚ na ten sam katalog co architekt |

**NaprawiĹ‚em to raz, u gabinetu, i nie rozesĹ‚aĹ‚em dalej.** To jest dokĹ‚adnie ta wada, ktĂłrÄ…
gabinet nazwaĹ‚ tego samego dnia: **wiedza zapisana obok nie propaguje siÄ™ sama** â€” a ja
zastosowaĹ‚em jÄ… do kodu i nie zastosowaĹ‚em do wĹ‚asnego kanaĹ‚u.

---

## REGUĹA

> **PozycjÄ™ zamyka PLIK `ODPOWIEDZ-<nnn>.md` w `docs/ZLECENIA/`. Meldunek w oknie sesji
> NICZEGO NIE ZAMYKA â€” jest uprzejmoĹ›ciÄ… wobec czytajÄ…cego, nie zapisem.**

**Trzy skutki, wszystkie zmierzone, nie hipotetyczne:**

1. **Architekt sprawdza stan kolejek POLECENIEM na katalogu.** Praca, ktĂłrej nie ma w pliku,
   jest dla tego pomiaru **niewidzialna** â€” i byĹ‚a, dwa razy dzisiaj.
2. **Meldunek w oknie ginie przy zagÄ™szczeniu kontekstu.** Praca opisana wyĹ‚Ä…cznie w oknie
   istnieje tak dĹ‚ugo, jak dĹ‚ugo ktoĹ› jej rÄ™cznie nie skopiuje. **DziĹ› kopiowaĹ‚ jÄ… wĹ‚aĹ›ciciel.**
3. **WĹ‚aĹ›ciciel patrzy na ten sam katalog.** Cisza w katalogu **wyglÄ…da identycznie jak
   praca w toku** â€” i to jest najdroĹĽsza rzecz w tym ukĹ‚adzie.

## KolejnoĹ›Ä‡ czynnoĹ›ci po skoĹ„czeniu pozycji â€” bez wyjÄ…tkĂłw

1. **`ODPOWIEDZ-<nnn>.md`** â€” wynik, pomiary, â€žczego nie sprawdziĹ‚em", nieudane prĂłby obalenia.
2. **commit i push** (push wolno zawsze â€” `D-EKO-006`).
3. **`PODJETO-<nnn>.md`** â€” nastÄ™pna pozycja, jedno zdanie: co i z ktĂłrego ĹşrĂłdĹ‚a (`S-2`).
4. **Dopiero teraz** meldunek w oknie, jeĹ›li chcesz. **Streszczenie, nie zamiast pliku.**

**Pozycja NIEZROBIONA teĹĽ dostaje plik** â€” z jawnym â€žNIEZROBIONE" i powodem. Gabinet zrobiĹ‚
to wzorowo przy `020`â€“`022`, Ĺ‚Ä…cznie z zastrzeĹĽeniem, ĹĽe skoro nie zweryfikowaĹ‚ listy,
**nie wolno cytowaÄ‡ starej jako aktualnej**.

## Sprawdzian, ktĂłry stosujÄ™ i ktĂłry moĹĽesz stosowaÄ‡ sam

> **Gdyby Twoje okno sesji zniknÄ™Ĺ‚o w tej sekundzie â€” czy z samego katalogu `docs/ZLECENIA/`
> da siÄ™ odtworzyÄ‡, co zrobiĹ‚eĹ› i na czym stoisz?**

JeĹ›li nie â€” pozycja nie jest zamkniÄ™ta, niezaleĹĽnie od tego, ile pracy w niej siedzi.

---

**Ta zasada nie zwiÄ™ksza Waszej pracy â€” zmienia jej kolejnoĹ›Ä‡.** TreĹ›Ä‡ meldunku, ktĂłry i tak
piszecie w oknie, ma najpierw trafiÄ‡ do pliku.

---

**U Ciebie to już przerabialiśmy** (ZLECENIE-023) i po tamtej rozmowie zapisałeś to sam jako regułę. **Rozsyłam ją teraz do wszystkich, bo nie rozesłałem jej wtedy — i dokładnie dlatego wróciła u kont.** To Twoje zdanie z dziś: *wiedza zapisana w komentarzu obok nie propaguje się sama*.
