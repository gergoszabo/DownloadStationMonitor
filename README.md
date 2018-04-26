# DownloadStationMonitor

#### Synology beállítások
Borisz76 [bejegyzését](https://logout.hu/bejegyzes/borisz76/synology_dsm_6_unregistered_torrent_ds_ncore.html) annyiban egészíteném ki, hogy a PHP beállításkná a Kiterjesztéseknél a **_curl_**-hoz is tegyük be a pipát!

#### Egyéni beállítások
Az egyeni_beallitasok.php-ban a NAS IP címét, a használt portot, a felhasználónevet és hozzá tartozó jelszót be kell állítani ahhoz, hogy megjelenítse a futtatott torrenteket az oldal.
```php
define('IP', '192.168.0.2');
define('PORT', 5000);
define('USER', 'gary');
define('PASS', 'jelszó');
```
Az oldal automatikusan újratöltődik *UJRATOLTES* időközönként, melynek beállítása (például 30 másodpercre):
```php
define('UJRATOLTES', 30);
```
Lehetőség van megjeleníteni az aktuálisan csatlakozott seederek és leecherek számát, az összes seeder és leecher számát, mindkettőt vagy egyiket sem. Erre az *SL* beállítás szolgál négy lehetséges opcióval:
```php
// egyiket sem:       SL_NEM
// csatlakozottakat:  SL_CSAT
// összeset:          SL_OSSZ
// mindkettőt:        SL_MIX
define('SL', SL_MIX);
```
Kétlépcsős azonosítás használatára is van lehetőség, ekkor az oldal bekéri az aktuális kódot, és azután jelennek meg a torrentek
```php
// kétlépcső azonosítás: igen-> true, nem -> false
define('TWOFA', false);
```
Megadhatjuk, hogy http vagy https protokolt szeretnénk használni
```php
// https vagy http használata
define('PROTOCOL', 'http');
```
