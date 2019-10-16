# DownloadStationMonitor

#### Synology beállítások
Borisz76 [bejegyzését](https://logout.hu/bejegyzes/borisz76/synology_dsm_6_unregistered_torrent_ds_ncore.html) annyiban egészíteném ki, hogy a PHP beállításoknál a Kiterjesztéseknél a **_curl_**-hoz is tegyük be a pipát!

#### Egyéni beállítások
A config.php-ban a NAS IP címét, a használt portot, a felhasználónevet és hozzá tartozó jelszót be kell állítani ahhoz, hogy megjelenítse a futtatott torrenteket az oldal.
```php
define('IP', '192.168.0.0');
define('PORT', 5000);
define('USER', 'user');
define('PASS', 'pass');
```
Lehetőség van két téma között váltani, egy sötét és világos között
```php
// sötét mód használata: igen -> true, nem -> false
define('DARK', true);
```
Egyszerű mód: csak a tracker állapota érdekel -> true
Minden infó: false
```php
// egyszerüsített mód
define('MOD_SIMPLE', false);
```
Az oldal automatikusan újratöltődik bizonyos időközönként, ezt lehet megadni a következő beállítással:
```php
// oldal újratöltésének időköze
// 5 perc => 5 * 60
define('UJRATOLTES', 10);
```
Kétlépcsős azonosítás használatára is van lehetőség, ekkor az oldal bekéri az aktuális kódot, és azután jelennek meg a torrentek
```php
// kétlépcső azonosítás: igen-> true, nem -> false
define('TWOFACTOR', false);

```
Megadhatjuk, hogy http vagy https protokolt szeretnénk használni
```php
// https vagy http használata a nas felé
define('PROTOCOL', 'http');
```

Lehetőség van a Download Stationben felvett RSS csatornák megjelenítésére is, alapértelmezetten nem látszik. Megadható, hogy hány eleme jelenjen meg, illetve az rss oldal mennyi időközönként töltődjön újra
```php
// rss megjelenítése: igen -> true, nem -> false
define('RSS', true);
// rss megjelenítendő elemek száma
define('RSS_LIMIT', 500);
// rss oldal újratöltésének időköze
define('RSS_UJRATOLTES', 5 * 60);
```
