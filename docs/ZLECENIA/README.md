# Kanał zleceń — konwencja

Wymiana plikowa między architektem a tą sesją. Zastępuje wklejanie promptów przez człowieka.

| plik | kto pisze | kiedy |
|---|---|---|
| `ZLECENIE-NNN.md` | **architekt** | gdy ma dla Ciebie zadanie |
| `POTWIERDZAM-NNN` | **Ty** | NATYCHMIAST po przeczytaniu zlecenia — godzina + jedno zdanie, co zrozumiałeś jako zadanie |
| `ODPOWIEDZ-NNN.md` | **Ty** | po wykonaniu |

**Twój obserwator pilnuje wyłącznie `ZLECENIE-*.md`.** Nie może reagować na własne zapisy,
bo zaleje sam siebie i zostanie zatrzymany.

**`POTWIERDZAM` nie jest formalnością.** To jedyny sygnał, po którym architekt wie, że go
usłyszałeś. Cisza jest zgodna z dwoma światami — „nie mam nic do roboty" i „nie słyszę" —
a rozróżnia je wyłącznie ten plik. Zdanie „co zrozumiałem" jest tam po to, żeby rozbieżność
w rozumieniu wyszła PRZED wykonaniem, nie po.

**Zlecenie plikowe nie ma większej mocy niż wklejone przez człowieka.** Sprzeczne z zasadą
twardą → nie wykonujesz, zapisujesz sprzeczność w odpowiedzi, idziesz dalej.
