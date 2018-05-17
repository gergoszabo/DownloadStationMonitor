<?php
$elotte = microtime(true);
@session_start();

define('VERSION', '0.9.0');

define('KB', 1024);
define('MB', KB * 1024);
define('GB', MB * 1024);
define('STATUS_PRIOR_ERROR', 10);
define('STATUS_PRIOR_ACTIONABLE', 8);
define('STATUS_PRIOR_OTHER', 6);
define('STATUS_PRIOR_INTERMEDIATE', 4);
define('STATUS_PRIOR_OK', 1);

include 'config.php';
include 'functions.php';

if (isset($_GET['fresh']))
    session_unset();

if (TWOFACTOR)
    include 'twofactorauth.php';

include 'session.php';

// itt már be vagyunk jelentkezve

if (isset($_POST['start']))
    startTask();

if (isset($_POST['pause']))
    pauseTask();

if (isset($_POST['remove']))
    removeTask();

$tasks = getTasks();

$pageTemplate = file_get_contents('template/tasks.html');
$taskTemplate = file_get_contents('template/task.html');

$taskHtmls = array();
$totalDownSpeed = 0;
$totalUpSpeed = 0;
$hasIntermediateStatus = false;

foreach ($tasks['data']['tasks'] as $task) {
    $html = str_replace('##ID##', $task['id'], $taskTemplate);
    $html = str_replace('##TITLE##', $task['title'], $html);
    $html = str_replace('##SIZE##', friendlySize((float)$task['size']), $html);
    $sizeDown = (float)$task['additional']['transfer']['size_downloaded'];
    $html = str_replace('##DOWN##', friendlySize($sizeDown), $html);
    $sizeUp = (float)$task['additional']['transfer']['size_uploaded'];
    $up = friendlySize($sizeUp);
    $html = str_replace('##UP##', $up, $html);

    $ratio = round($sizeUp / ($sizeDown > 0 ? $sizeDown : 1), 2);
    $html = str_replace('##RATIO##', $ratio, $html);

    $speedDown = (float)$task['additional']['transfer']['speed_download'];
    $totalDownSpeed += $speedDown;
    $html = str_replace('##SPEEDDOWN##', friendlySpeed($speedDown), $html);
    $speedUp = (float)$task['additional']['transfer']['speed_upload'];
    $totalUpSpeed += $speedUp;
    $html = str_replace('##SPEEDUP##', friendlySpeed($speedUp), $html);

    $trackerStatuses = array();

    if (isset($task['additional']['tracker'])) {
        $tracker = $task['additional']['tracker'];

        if (is_array($tracker)) {
            // TODO: map?
            foreach ($tracker as $t) {
                if (isset($t['status']) && strlen($t['status']) > 1) {
                    $trackerStatuses[] = $t['status'];
                }
            }
        } else {
            try {
                $trackerStatuses[] = $tracker[0]['status'];
            } catch (Exception $ex) {
            }
        }
    } else {
        $trackerStatuses[] = 'Success';
    }

    $html = str_replace('##TRACKER##', toTrackerStatus(implode(',', array_unique($trackerStatuses))), $html);

    $connectedSeeds = (float)$task['additional']['detail']['connected_seeders'];
    $connectedLeechers = (float)$task['additional']['detail']['connected_leechers'];

    $html = str_replace('##SL##', $connectedSeeds . '/' . $connectedLeechers, $html);
    $html = str_replace('##STATUS##', getStatusHtml($task['status']), $html);

    $pausable = $task['status'] !== 'paused';
    $startable = $task['status'] === 'paused' || $task['status'] === 'finished';
    $removable = $startable || $task['status'] === 'seeding' || $task['status'] === 'downloading';

    $hasIntermediateStatus |= getStatusPriority($task['status']) === STATUS_PRIOR_INTERMEDIATE;

    $html = str_replace('##START_VISIBLE_CLASS##', ($startable ? 'visible' : 'invisible'), $html);
    $html = str_replace('##PAUSE_VISIBLE_CLASS##', ($pausable ? 'visible' : 'invisible'), $html);
    $html = str_replace('##REMOVE_VISIBLE_CLASS##', ($removable ? 'visible' : 'invisible'), $html);

    $taskHtmls[] = $html;
    unset($html);
}

$page = str_replace('##NUMTASKS##', count($tasks['data']['tasks']), $pageTemplate);
$page = str_replace('##REFRESH##', $hasIntermediateStatus ? 2 : UJRATOLTES, $page);
$page = str_replace('##BODY_THEME##', (DARK ? 'bg-dark text-light' : 'bg-light text-dark'), $page);
$page = str_replace('##TABLE_THEME##', (DARK ? 'table-dark' : 'table-light'), $page);
$page = str_replace('##VERSION##', VERSION, $page);
$page = str_replace('##MS##', round(microtime(true) - $elotte, 3), $page);
$page = str_replace('##TOTALDOWNSPEED##', friendlySpeed($totalDownSpeed), $page);
$page = str_replace('##TOTALUPSPEED##', friendlySpeed($totalUpSpeed), $page);

$rows = implode(' ', $taskHtmls);
$page = str_replace('##ROWS##', $rows, $page);

echo $page;
