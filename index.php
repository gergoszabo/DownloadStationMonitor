<?php
$elotte = microtime(true);
@session_start();
define('SL_CSAT', 1);
define('SL_OSSZ', 2);
define('SL_MIX', 4);
define('VERSION', '0.6');

include 'egyeni_beallitasok.php';
include 'session_2fa.php';

?>
<html> 
<head>	 
	<title>Download Station Monitor</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<link type="text/css" rel="stylesheet" href="stylesheet.css"/>
	<meta http-equiv="refresh" content="<?=UJRATOLTES?>">
</head>
<body>
	<div class="right">
		Az oldal automatikusan frissül<br>
		Utolsó oldal betöltése: <?=date('H:i:s')?>
	</div>
	<h2 class="center"><i>Download Station Monitor</i></h2>	
	<table id="maintable" class="center">
		<tr>
			<th><i>Torrent Neve</i></th>
			<th><i>Méret</i></th>
			<th><i>Letöltve</i></th>
			<th><i>Feltöltve</i></th>
			<th><i>Folyamat</i></th>
			<th><i>Átlag</i></th>
			<th><i>Le</i></th>
			<th><i>Fel</i></th>
			<th><i>Tracker</i></th>
			<th><i>S/L</i></th>
			<th><i>Áll.</i></th>
		</tr>
<?
	define('MB', 1024 * 1024);
	define('GB', MB * 1024);
	 
	$tasksUrl='http://'.IP.':'.PORT.'/webapi/DownloadStation/task.cgi?api='.
		'SYNO.DownloadStation.Task&version=1&method=list&_sid='.$_SESSION['sid'].
		'&additional=transfer,detail,tracker';

	$decodedrequest=json_decode(file_get_contents($tasksUrl), true);

	$totaldownloads=$decodedrequest['data']['total']; //get total number of downloads (for statistics)

	if(isset($decodedrequest['data']['tasks']))
	usort($decodedrequest['data']['tasks'], function($a, $b) {
		if($a['status'] == $b['status']) {
			return strcmp($a['title'], $b['title']);
		}

		return strcmp($a['status'], $b['status']);
	});
	
	foreach($decodedrequest['data']['tasks'] as $task) {
		
		$title = $task['title'];
		$size = (float)$task['size'];
		$status = $task['status'];

		$size_downloaded = (float)$task['additional']['transfer']['size_downloaded'];
		$size_uploaded = (float)$task['additional']['transfer']['size_uploaded'];
		$speed_download = (float)$task['additional']['transfer']['speed_download'];
		$speed_upload = (float)$task['additional']['transfer']['speed_upload'];
		$ratio = round($size_uploaded / ($size_downloaded > 0 ? $size_downloaded : 1), 2);

		$size = round($size / GB,2);
		$size_downloaded = round($size_downloaded / GB, 2);
		$size_uploaded = round($size_uploaded / GB, 2);
		$speed_download = round($speed_download / MB, 2);
		$speed_upload = round($speed_upload / MB, 2);

		$progress = round($size_downloaded /($size > 0 ? $size : 1) * 100, 1);

		$trackerstatuses = array();
		$totalSeeds = 0;
		$connectedSeeds = (float)$task['additional']['detail']['connected_seeders'];
		$totalPeers = 0;
		$connectedPeers = (float)$task['additional']['detail']['connected_leechers'];

		if(isset($task['additional']['tracker'])) {
			$tracker = $task['additional']['tracker'];

			if(is_array($tracker)) {
				foreach($tracker as $t) {
					if(isset($t['status']) && strlen($t['status'])) {
						$trackerstatuses[] = $t['status'];

						$s = (float)$t['seeds'];
						if($s > 0)
							$seeds += $s;

						$p = (float)$t['peers'];
						if($p > 0)
							$peers += $p;
					}
				}
			}
			else {
				try {
					$trackerstatuses[] = $tracker[0]['status'];

					$seeds = (float)$tracker[0]['seeds'];
					$peers = (float)$tracker[0]['peers'];
				}
				catch (Exception $ex) {
				}
			}
		}
		
		$trackerstatus = implode(',', $trackerstatuses);
?>
			<tr>
				<td><?=$title?></td>
				<td class="right size"><?=$size?> GB</td>
				<td class="right"><?=$size_downloaded?> GB</td>
				<td class="right"><?=$size_uploaded?> GB</td> 
				<td>
					&nbsp;<?=$progress?>%&nbsp;
					<progress value="<?=$size_downloaded?>" max="<?=$size?>" />
				</td> 
				<td class="right"><?=$ratio?></td> 
				<td class="right size"><?=$speed_download?> MB/s</td>
				<td class="right size"><?=$speed_upload?> MB/s</td>
				<td class="center"><?=$trackerstatus?></td>
				<td class="center">
				<?
					switch(SL) {
						case SL_CSAT:
							echo "$seeds / $peers";
						break;
						case SL_OSSZ:
							echo "$connectedSeeds / $connectedPeers";
						break;
						case SL_MIX:
							echo "$seeds / $peers ($connectedSeeds / $connectedPeers)";
						break;
					}
				?>
				</td>
				<td class="center">
					<img src="images_status/<?=$status?>.png" width=15 height=15 align="center">
				</td>
			</tr>
<?
	}
	
	$speedsUrl = 'http://'.IP.':'.PORT.'/webapi/DownloadStation/statistic.cgi?api='.
		'SYNO.DownloadStation.Statistic&version=1&method=getinfo&_sid='.$_SESSION['sid'];
	$decodedspeeds = json_decode(file_get_contents($speedsUrl),true);
	$totaldownspeed = $decodedspeeds['data']['speed_download'] / MB;
	$totalupspeed = $decodedspeeds['data']['speed_upload'] / MB;
  
?>
	</table>
	<br>
	<table class="center">
		<th colspan="6"><i>Statisztika</i></th>
		<tr>
			<td class="autowidth"><b>Összes torrent:</b></td>
			<td class="autowidth"><?=$totaldownloads?></td>
			<td class="autowidth"><b>Le seb.:</b></td>
			<td class="autowidth"><?=round($totaldownspeed, 2)?> MB/s</td>
			<td class="autowidth"><b>Fel seb.:</b></td>
			<td class="autowidth"><?=round($totalupspeed, 2)?> MB/s</td>
		</tr>
	</table> 
<footer>
Verzió: <?=VERSION?>
<br><small>Generálva: <?=round(microtime(true) - $elotte, 2)?> másodperc</small>
</footer> 
</html>