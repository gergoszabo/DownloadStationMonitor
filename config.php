<?php
/// Ezeket módosítsd
define('IP', '192.168.0.0');
define('PORT', 5000);
define('USER', 'user');
define('PASS', 'pass');
define('BASEURL', 'http://localhost/');
define('DARK', true);

// milyen időközönként töltődjön újra az oldal másodpercben kifejezve
// 5 perc => 5 * 60
define('UJRATOLTES', 10);

// kétlépcső azonosítás: igen-> true, nem -> false
define('TWOFACTOR', false);

// https vagy http használata
define('PROTOCOL', 'http');
