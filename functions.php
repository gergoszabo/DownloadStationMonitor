<?php

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

    $template = str_replace('##ERROR##', $error, $template);
    $template = str_replace('##BASEURL##', getBaseUrl(), $template);

    die($template);
}

function get($url)
{
    $ch = curl_init();

    $defaults = array(
        CURLOPT_URL => $url,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
    );
    curl_setopt_array($ch, $defaults);

    $result = curl_exec($ch);

    curl_close($ch);

    return $result;
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

function toTrackerStatus($trackerStatus)
{
    if ($trackerStatus === 'Success' || $trackerStatus === '')
        return '<span class="btn btn-sm btn-success">OK</span>';


    if ($trackerStatus === 'unregistered torrent' || $trackerStatus === 'passkey not found')
        return '<span class="btn btn-sm btn-danger">' . $trackerStatus . '</span>';

    return '<span class="btn btn-sm btn-warning">' . $trackerStatus . '</span>';
}

function sortTasks($a, $b)
{
    $ap = getStatusPriority($a['status']);
    $bp = getStatusPriority($b['status']);

    if ($ap !== $bp)
        return $ap > $bp ? -1 : 1;


    if ($a['status'] == $b['status']) {
        return strcmp($a['title'], $b['title']);
    }

    return strcmp($a['status'], $b['status']);
}

function getTasks()
{
    $tasksUrl = PROTOCOL . '://' . IP . ':' . PORT . '/webapi/DownloadStation/task.cgi?api=' .
        'SYNO.DownloadStation.Task&version=1&method=list&_sid=' . $_SESSION['sid'] .
        '&additional=transfer,detail,tracker';

    $decodedRequest = json_decode(get($tasksUrl), true);

    if (isset($decodedRequest['error'])) {
        displayErrorAndDie($decodedRequest['error']);
    }

    if (isset($decodedRequest['data']['tasks']))
        usort($decodedRequest['data']['tasks'], "sortTasks");

    return $decodedRequest;
}
