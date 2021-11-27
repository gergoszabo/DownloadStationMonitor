# DownloadStationMonitor

#### Synology beállítások
Borisz76 [bejegyzését](https://logout.hu/bejegyzes/borisz76/synology_dsm_6_unregistered_torrent_ds_ncore.html) annyiban egészíteném ki, hogy a PHP beállításoknál a Kiterjesztéseknél a **_curl_**-hoz is tegyük be a pipát!

#### Egyéni beállítások
A config.php-ban a NAS IP címét, a használt portot, a felhasználónevet és hozzá tartozó jelszót be kell állítani ahhoz, hogy megjelenítse a futtatott torrenteket az oldal.
```php
define('IP', '192.168.0.1');
define('PORT', 5000);
define('USER', 'username');
define('PASS', 'password');
```
Lehetőség van sötét és világos téma között váltani, sötéthez 'igen', világoshoz 'nem' értéket adjunk meg
```php
// sötét mód használata: 'igen' vagy 'nem'
define('DARK', 'igen');
```
Az oldal automatikusan frissül néhány másodpercenként, itt tudod változtatni az időtartamot
```php
// oldal újratöltésének időköze
// 5 perc => 5 * 60
define('UJRATOLTES', 30);
```
Kétlépcsős azonosítás használatára is van lehetőség, ekkor az oldal bekéri az aktuális kódot, és azután jelennek meg a taskok
```php
// kétlépcső azonosítás: 'igen' vagy 'nem'
define('TWOFACTOR', 'nem');

```
Megadhatjuk, hogy http vagy https protokolt szeretnénk használni
```php
// 'https' vagy 'http' használata a nas felé
define('PROTOCOL', 'http');
```