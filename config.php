<?php
/// Ezeket módosítsd
define('IP', '192.168.0.0');
define('PORT', 5000);
define('USER', 'user');
define('PASS', 'pass');
// sötét mód használata: igen -> true, nem -> false
define('DARK', true);

// oldal újratöltésének időköze
// 5 perc => 5 * 60
define('UJRATOLTES', 10);

// kétlépcső azonosítás: igen-> true, nem -> false
define('TWOFACTOR', false);

// https vagy http használata a nas felé
define('PROTOCOL', 'http');

// rss megjelenítése: igen -> true, nem -> false
define('RSS', true);
// rss megjelenítendő elemek száma
define('RSS_LIMIT', 500);
// rss oldal újratöltésének időköze
define('RSS_UJRATOLTES', 5 * 60);
