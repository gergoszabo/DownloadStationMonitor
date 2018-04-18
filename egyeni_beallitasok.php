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
// csatlakozottakat: 	SL_CSAT
// összeset: 			SL_CSAT
// mindkettőt:			SL_MIX
define('SL', SL_MIX);