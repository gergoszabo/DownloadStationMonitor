<?
/// Ezeket módosítsd
define('IP', '192.168.0.0');
define('PORT', 5000);
define('USER', 'user');
define('PASS', 'pass');

// milyen időközönként töltődjön újra az oldal másodpercben kifejezve
// 5 perc => 5 * 60
define('UJRATOLTES', 5 * 60);

// melyik seeder/leecher értéket jelenítse meg:
// egyiket sem:			SL_NEM
// csatlakozottakat: 	SL_CSAT
// összeset: 			SL_OSSZ
// mindkettőt:			SL_MIX
define('SL', SL_MIX);

// kétlépcső azonosítás: igen-> true, nem -> false
define('TWOFA', false);

// https vagy http használata
define('PROTOCOL', 'http');

// hibakereséshez true, egyébként false
define('DEBUG', false);

if(DEBUG)
	$_SESSION['debug'] = array();
