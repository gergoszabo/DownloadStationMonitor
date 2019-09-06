<?php

define('TRACKER_STATUS_ERROR', 10);
define('TRACKER_STATUS_OTHER', 5);
define('TRACKER_STATUS_OK', 1);
define('UNREG_TORRENT', 'unregistered torrent');
define('UNREG_TORRENT_SHORT', 'unreg.torr.');

function getBaseUrl()
{
    // output: /myproject/index.php
    $currentPath = $_SERVER['PHP_SELF'];

    // output: Array ( [dirname] => /myproject [basename] => index.php [extension] => php [filename] => index )
    $pathInfo = pathinfo($currentPath);

    // output: localhost
    $hostName = $_SERVER['HTTP_HOST'];

    // output: http://
    $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"], 0, 5)) == 'https://' ? 'https://' : 'http://';

    // return: http://localhost/myproject/
    return $protocol . $hostName . $pathInfo['dirname'];
}

function displayErrorAndDie($error)
{
    $template = file_get_contents('template/error.html');

    $template = str_replace('##ERROR##', print_r($error, true), $template);
    $template = str_replace('##BASEURL##', getBaseUrl(), $template);

    die($template);
}

function post($url, $data)
{
    $ch = curl_init();

    $defaults = array(
        CURLOPT_URL => $url,
        CURLOPT_HEADER => 0,
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POSTFIELDS => $data
    );
    curl_setopt_array($ch, $defaults);

    $result = curl_exec($ch);

    curl_close($ch);

    return $result;
}

function get($url)
{
    $ch = curl_init();

    $defaults = array(
        CURLOPT_URL => $url,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
    );
    curl_setopt_array($ch, $defaults);

    $result = curl_exec($ch);

    curl_close($ch);

    return $result;
}

function newTaskFromUrl($url)
{
    $createUrl = PROTOCOL . '://' . IP . ':' . PORT . '/webapi/DownloadStation/task.cgi';
    $data = 'api=SYNO.DownloadStation.Task&version=3&method=create&_sid=' . $_SESSION['sid'] . '&uri=' . urlencode($url);
    $decodedRequest = json_decode(post($createUrl, $data), true);

    if (isset($decodedRequest['error'])) {
        displayErrorAndDie(print_r(array($decodedRequest, $createUrl), true));
    }

    header('Location: ' . getBaseUrl());
    exit();
}

function startTask()
{
    $resumeUrl = PROTOCOL . '://' . IP . ':' . PORT . '/webapi/DownloadStation/task.cgi?api=SYNO.DownloadStation.Task&version=1&method=resume&id=' . $_POST['start'] . '&_sid=' . $_SESSION['sid'];
    $decodedRequest = json_decode(get($resumeUrl), true);

    if (isset($decodedRequest['error'])) {
        displayErrorAndDie(print_r(array($decodedRequest, $resumeUrl), true));
    }

    header('Location: ' . getBaseUrl());
    exit();
}

function pauseTask()
{
    $pauseUrl = PROTOCOL . '://' . IP . ':' . PORT . '/webapi/DownloadStation/task.cgi?api=SYNO.DownloadStation.Task&version=1&method=pause&id=' . $_POST['pause'] . '&_sid=' . $_SESSION['sid'];
    $decodedRequest = json_decode(get($pauseUrl), true);

    if (isset($decodedRequest['error'])) {
        displayErrorAndDie(print_r(array($decodedRequest, $pauseUrl), true));
    }

    header('Location: ' . getBaseUrl());
    exit();
}

function removeTask()
{
    $removeUrl = PROTOCOL . '://' . IP . ':' . PORT . '/webapi/DownloadStation/task.cgi?api=SYNO.DownloadStation.Task&version=1&method=delete&id=' . $_POST['remove'] . '&_sid=' . $_SESSION['sid'];
    $decodedRequest = json_decode(get($removeUrl), true);

    if (isset($decodedRequest['error'])) {
        displayErrorAndDie(print_r(array($decodedRequest, $removeUrl), true));
    }

    header('Location: ' . getBaseUrl());
    exit();
}

function friendlySize($size)
{
    if ($size > GB)
        return sprintf('%.2f GB', round($size / GB, 2));

    return sprintf('%.1f MB', round($size / MB, 2));
}

function friendlySpeed($speed)
{
    if ($speed > MB)
        return sprintf('%.1f MB/s', round($speed / MB, 2));

    return sprintf('%.1f KB/s', round($speed / KB, 2));
}

function getStatusPriority($status)
{
    switch ($status) {
        case 'error':
            return STATUS_PRIOR_ERROR;

        case 'waiting':
        case 'finishing':
        case 'filehosting_waiting':
        case 'hash_checking':
        case 'extracting':
            return STATUS_PRIOR_INTERMEDIATE;

        case 'paused':
        case 'downloading':
        case 'finished':
            return STATUS_PRIOR_ACTIONABLE;

        case 'seeding':
            return STATUS_PRIOR_OK;

        default:
            return STATUS_PRIOR_OTHER;
    }
}

function getTrackerStatusPriority($trackerStatus)
{
    if ($trackerStatus === 'Success' || $trackerStatus === '')
        return TRACKER_STATUS_OK;

    if ($trackerStatus === UNREG_TORRENT || $trackerStatus === 'passkey not found')
        return TRACKER_STATUS_ERROR;

    return TRACKER_STATUS_OTHER;
}

function getTrackerStatus($task)
{
    if (isset($task['combinedTrackerStatus']))
        return $task['combinedTrackerStatus'];

    $trackerStatuses = array();

    if (isset($task['additional']['tracker'])) {
        $tracker = $task['additional']['tracker'];

        if (is_array($tracker)) {
            foreach ($tracker as $t) {
                if (isset($t['status']) && strlen($t['status']) > 1) {
                    $trackerStatuses[] = $t['status'] === UNREG_TORRENT ? UNREG_TORRENT_SHORT : $t['status'];
                }
            }
        } else {
            try {
                $trackerStatuses[] = $tracker[0]['status'] === UNREG_TORRENT ? UNREG_TORRENT_SHORT : $tracker[0]['status'];
            } catch (Exception $ex) { }
        }
    } else {
        $trackerStatuses[] = 'Success';
    }

    $task['combinedTrackerStatus'] = implode(',', array_unique($trackerStatuses));
    return $task['combinedTrackerStatus'];
}

function getStatusHtml($status)
{
    switch (getStatusPriority($status)) {
        case STATUS_PRIOR_ERROR:
            return '<span class="btn btn-sm btn-danger">' . $status . '</span>';
        case STATUS_PRIOR_ACTIONABLE:
            return '<span class="btn btn-sm btn-info">' . $status . '</span>';
        case STATUS_PRIOR_INTERMEDIATE:
            return '<span class="btn btn-sm btn-secondary">' . $status . '</span>';
        case STATUS_PRIOR_OK:
            return '<span class="btn btn-sm btn-success">' . $status . '</span>';

        case STATUS_PRIOR_OTHER:
        default:
            return '<span class="btn btn-sm btn-warning">' . $status . '</span>';
    }
}

function getTrackerStatusHtml($trackerStatus)
{
    switch (getTrackerStatusPriority($trackerStatus)) {
        case TRACKER_STATUS_OK:
            return '<span class="btn btn-sm btn-success">OK</span>';

        case TRACKER_STATUS_ERROR:
            return '<span class="btn btn-sm btn-danger">' . $trackerStatus . '</span>';

        default:
        case TRACKER_STATUS_OTHER:
            return '<span class="btn btn-sm btn-warning">' . $trackerStatus . '</span>';
    }
}

function sortTasks($a, $b)
{
    $ap = getStatusPriority($a['status']);
    $bp = getStatusPriority($b['status']);

    if ($ap !== $bp)
        return $ap > $bp ? -1 : 1;

    if ($a['status'] == $b['status']) {
        $atrsp = getTrackerStatusPriority(getTrackerStatus($a));
        $btrsp = getTrackerStatusPriority(getTrackerStatus($b));

        if ($atrsp === $btrsp)
            return strcmp($a['title'], $b['title']);
        else
            return $atrsp > $btrsp ? -1 : 1;
    }

    return strcmp($a['status'], $b['status']);
}

function getTasks()
{
    $tasksUrl = PROTOCOL . '://' . IP . ':' . PORT . '/webapi/DownloadStation/task.cgi?api=' .
        'SYNO.DownloadStation.Task&version=1&method=list&_sid=' . $_SESSION['sid'] .
        '&additional=';

    $tasksUrl .= MOD_SIMPLE ? 'tracker' : 'transfer,detail,tracker';

    $decodedRequest = json_decode(get($tasksUrl), true);

    if (isset($decodedRequest['error'])) {
        displayErrorAndDie($decodedRequest['error']);
    }

    if (isset($decodedRequest['data']['tasks']))
        usort($decodedRequest['data']['tasks'], "sortTasks");

    return $decodedRequest;
}

function getConfig($elotte)
{
    $configUrl = PROTOCOL . '://' . IP . ':' . PORT . '/webapi/DownloadStation/info.cgi?api=SYNO.DownloadStation.Info&version=1&method=getconfig&_sid=' . $_SESSION['sid'];

    $config = json_decode(get($configUrl), true);

    if (isset($config['error'])) {
        displayErrorAndDie($config['error']);
    }

    /*
     * [bt_max_download] => 3500
     * [bt_max_upload] => 600
     * */

    $configPage = file_get_contents('template/config.html');
    $configPage = str_replace('##BT_MAX_DOWNLOAD##', $config['data']['bt_max_download'], $configPage);
    $configPage = str_replace('##BT_MAX_UPLOAD##', $config['data']['bt_max_upload'], $configPage);
    $configPage = str_replace('##BODY_THEME##', (DARK ? 'bg-dark text-light' : 'bg-light text-dark'), $configPage);
    $configPage = str_replace('##VERSION##', VERSION, $configPage);
    $configPage = str_replace('##BASEURL##', getBaseUrl(), $configPage);
    $configPage = str_replace('##MS##', round(microtime(true) - $elotte, 2), $configPage);

    echo $configPage;
    exit();
}

function setConfig()
{
    if (!isset($_POST['bt_max_download']) || !is_numeric($_POST['bt_max_download']))
        displayErrorAndDie('Letöltési limit helytelen érték!');

    if (!isset($_POST['bt_max_upload']) || !is_numeric($_POST['bt_max_upload']))
        displayErrorAndDie('Feltöltési limit helytelen érték!');

    $configUrl = PROTOCOL . '://' . IP . ':' . PORT . '/webapi/DownloadStation/info.cgi?api=SYNO.DownloadStation.Info&' .
        'version=1&method=setserverconfig&bt_max_download=' . $_POST['bt_max_download'] . '&bt_max_upload=' . $_POST['bt_max_upload'] . '&_sid='
        . $_SESSION['sid'];

    $config = json_decode(get($configUrl), true);

    if (isset($config['error'])) {
        displayErrorAndDie($config['error']);
    }

    header('Location: ' . getBaseUrl());
    exit();
}
