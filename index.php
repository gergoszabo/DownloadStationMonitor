<?php
try {
    $elotte = microtime(true);
    @session_start();

    define('VERSION', '0.9.10');

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

    if (isset($_POST['config']))
        setConfig();

    if (isset($_GET['config']))
        getConfig($elotte);

    // itt már be vagyunk jelentkezve
    if (isset($_POST['create']))
        newTaskFromUrl($_POST['create']);

    if (isset($_GET['rss']))
        include 'rss.php';

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

    $taskNumbers = 0;

    foreach ($tasks['data']['tasks'] as $task) {
        if (MOD_SIMPLE && getTrackerStatusPriority(getTrackerStatus($task)) == TRACKER_STATUS_OK) {
            continue;
        }

        $taskNumbers++;

        $html = str_replace('##ID##', $task['id'], $taskTemplate);
        $html = str_replace('##TITLE##', $task['title'], $html);
        $html = str_replace('##SIZE##', friendlySize((float)$task['size']), $html);
        $sizeDown = (float)(isset($task['additional']['transfer']) ? $task['additional']['transfer']['size_downloaded'] : 0);
        $html = str_replace('##DOWN##', friendlySize($sizeDown), $html);
        $sizeUp = (float)(isset($task['additional']['transfer']) ? $task['additional']['transfer']['size_uploaded'] : 0);
        $up = friendlySize($sizeUp);
        $html = str_replace('##UP##', $up, $html);

        $progress = $task['size'] > 0 ? ($sizeDown === $task['size'] ? '100 %' : round($sizeDown / $task['size'] * 100, 2) . ' %') : '0 %';
        $html = str_replace('##PROGRESS##', $progress, $html);

        $ratio = round($sizeUp / ($sizeDown > 0 ? $sizeDown : 1), 2);
        $html = str_replace('##RATIO##', $ratio, $html);

        $speedDown = (float)(isset($task['additional']['transfer']) ? $task['additional']['transfer']['speed_download'] : 0);
        $totalDownSpeed += $speedDown;
        $html = str_replace('##SPEEDDOWN##', friendlySpeed($speedDown), $html);
        $speedUp = (float)(isset($task['additional']['transfer']) ? $task['additional']['transfer']['speed_upload'] : 0);
        $totalUpSpeed += $speedUp;
        $html = str_replace('##SPEEDUP##', friendlySpeed($speedUp), $html);

        $html = str_replace('##TRACKER##', getTrackerStatusHtml(getTrackerStatus($task)), $html);

        $connectedSeeds = (float)(isset($task['additional']['detail']) ? $task['additional']['detail']['connected_seeders'] : 0);
        $connectedLeechers = (float)(isset($task['additional']['detail']) ? $task['additional']['detail']['connected_leechers'] : 0);

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

    $page = $pageTemplate;
    $extraCss = '.complex { visibility: visible; display: table-cell; } .complexTr { display: table-row; }';

    if (MOD_SIMPLE) {
        $page = str_replace('##NUMTASKS##', '##NUMTASKS##<i>/' . count($tasks['data']['tasks']) . '</i>', $page);
        $extraCss = '.complex { visibility: hidden !important; display: none !important; } .complexTr { display: none !important; }';
    }

    $page = str_replace('##EXTRACSS##', $extraCss, $page);

    $page = str_replace('##NUMTASKS##', $taskNumbers, $page);
    $page = str_replace('##REFRESH##', $hasIntermediateStatus ? 2 : UJRATOLTES, $page);
    $page = str_replace('##BODY_THEME##', (DARK ? 'bg-dark text-light' : 'bg-light text-dark'), $page);
    $page = str_replace('##TABLE_THEME##', (DARK ? 'table-dark' : 'table-light'), $page);
    $page = str_replace('##VERSION##', VERSION, $page);
    $page = str_replace('##TOTALDOWNSPEED##', friendlySpeed($totalDownSpeed), $page);
    $page = str_replace('##TOTALUPSPEED##', friendlySpeed($totalUpSpeed), $page);
    $page = str_replace("##SIMPLE##", MOD_SIMPLE ? 'Simple ' : '', $page);
    $page = str_replace('##RSS##', RSS ? ' <a class="btn btn-outline-info" href="?rss">RSS</a>' :'', $page);

    $rows = implode(' ', $taskHtmls);
    $page = str_replace('##ROWS##', $rows, $page);
    $page = str_replace('##MS##', round(microtime(true) - $elotte, 2), $page);

    echo $page;

} catch (Exception $ex) {
    echo 'Kivétel: ' . $ex->getMessage() . '<br/>' . $ex->getFile() . ' - ' . $ex->getLine();
}
