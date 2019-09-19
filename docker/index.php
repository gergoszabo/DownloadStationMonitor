<?php
try {
    $elotte = microtime(true);
    @session_start();

    $IP = getenv('IP');
    $PORT = getenv('PORT');
    $USER = getenv('USER');
    $PASS = getenv('PASS');
    $RELOAD = getenv('RELOAD');
    $TWOFACTOR = getenv('TWOFACTOR') === 'true';
    $PROTOCOL = getenv('PROTOCOL');

    $version = '0.12.1';

    $kb = 1024;
    $mb = $kb * 1024;
    $gb = $mb * 1024;
    $statusPriorError = 10;
    $statusPriorActionable = 8;
    $statusPriorOther = 6;
    $statusPriorIntermediate = 4;
    $statusPriorOk = 1;

    $trackerStatusError = 10;
    $trackerStatusOther = 5;
    $trackerStatusOk = 1;
    $unregisteredTorrent = 'unregistered torrent';
    $unregisteredTorrentShort = 'unreg.torr.';

    $statusToPriority = array(
        'error' => $statusPriorError,

        'waiting' => $statusPriorIntermediate,
        'finishing' => $statusPriorIntermediate,
        'filehosting_waiting' => $statusPriorIntermediate,
        'hash_checking' => $statusPriorIntermediate,
        'extracting' => $statusPriorIntermediate,

        'paused' => $statusPriorActionable,
        'downloading' => $statusPriorActionable,
        'finished' => $statusPriorActionable,

        'seeding' => $statusPriorOk
    );
    $statusCssClass = array(
        $statusPriorError => 'btn-danger',
        $statusPriorActionable => 'btn-info',
        $statusPriorIntermediate => 'btn-secondary',
        $statusPriorOk => 'btn-success',
        $statusPriorOther => 'btn-warning'
    );
    $synoUrl = "$PROTOCOL://$IP:$PORT/webapi";
    $synoUrlDs = "$synoUrl/DownloadStation";

    function getBaseUrl()
    {
        $dirname = pathinfo($_SERVER['PHP_SELF'])['dirname'];
        $hostName = $_SERVER['HTTP_HOST'];
        $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"], 0, 5)) === 'https' ? 'https://' : 'http://';

        return "$protocol$hostName$dirname";
    }

    function displayErrorAndDie($error)
    {
        $error = print_r($error, true);
        $baseUrl = getBaseUrl();

        die("
        <!DOCTYPE html>
        <html lang='hu'>
        <head>
            <meta charset='UTF-8'>
            <meta http-equiv='content-type' content='text/html; charset=UTF-8'>
            <link type='text/css' rel='stylesheet' href='main.css'/>
            <title>Error - DownloadStationMonitor</title>
        </head>
        <body class='container'>
        <div class='alert-heading'>Error happened!</div>
        <div class='alert-danger'>$error</div>
        <a class='btn btn-primary' href='$baseUrl'>Back</a>
        </body>
        </html>");
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

    function taskAction($url)
    {
        $decodedRequest = json_decode(get($url), true);

        if (isset($decodedRequest['error'])) {
            displayErrorAndDie($decodedRequest['error']);
        }

        return $decodedRequest;
    }

    function startTask()
    {
        global $synoUrlDs;
        $start = $_POST['start'];
        $sid = $_SESSION['sid'];

        $resumeUrl = "$synoUrlDs/task.cgi?api=SYNO.DownloadStation.Task&version=1&method=resume&id=$start&_sid=$sid";
        taskAction($resumeUrl);

        header('Location: ' . getBaseUrl());
        exit();
    }

    function pauseTask()
    {
        global $synoUrlDs;
        $pause = $_POST['pause'];
        $sid = $_SESSION['sid'];

        $pauseUrl = "$synoUrlDs/task.cgi?api=SYNO.DownloadStation.Task&version=1&method=pause&id=$pause&_sid=$sid";
        taskAction($pauseUrl);

        header('Location: ' . getBaseUrl());
        exit();
    }

    function removeTask()
    {
        global $synoUrlDs;
        $remove = $_POST['remove'];
        $sid = $_SESSION['sid'];

        $removeUrl = "$synoUrlDs/task.cgi?api=SYNO.DownloadStation.Task&version=1&method=delete&id=$remove&_sid=$sid";
        taskAction($removeUrl);

        header('Location: ' . getBaseUrl());
        exit();
    }

    function toFriendly($num, $gbsuffix, $mbsuffix, $kbsuffix)
    {
        global $gb, $mb, $kb;

        if ($num > $gb) {
            return sprintf("%.1f $gbsuffix", round($num / $gb, 2));
        }
        if ($num > $mb) {
            return sprintf("%.1f $mbsuffix", round($num / $mb, 2));
        }
        return sprintf("%.1f $kbsuffix", round($num / $kb, 2));
    }

    function friendlySize($size)
    {
        return toFriendly($size, 'GB', 'MB', 'KB');
    }

    function friendlySpeed($speed)
    {
        return toFriendly($speed, 'GB/s', 'MB/s', 'KB/s');
    }

    function getStatusPriority($status)
    {
        global $statusToPriority, $statusPriorOther;

        if (key_exists($status, $statusToPriority)) {
            return $statusToPriority[$status];
        }
        return $statusPriorOther;
    }

    function getTrackerStatusPriority($trackerStatus)
    {
        global $trackerStatusOk, $unregisteredTorrent, $trackerStatusError, $trackerStatusOther;

        if ($trackerStatus === 'Success' || $trackerStatus === '')
            return $trackerStatusOk;

        if ($trackerStatus === $unregisteredTorrent || $trackerStatus === 'passkey not found')
            return $trackerStatusError;

        return $trackerStatusOther;
    }

    function getTrackerStatus($task)
    {
        global $unregisteredTorrent, $unregisteredTorrentShort;

        if (isset($task['combinedTrackerStatus']))
            return $task['combinedTrackerStatus'];

        $trackerStatuses = array();

        if (isset($task['additional']['tracker'])) {
            $tracker = $task['additional']['tracker'];

            if (is_array($tracker)) {
                foreach ($tracker as $t) {
                    if (isset($t['status']) && strlen($t['status']) > 1) {
                        $trackerStatuses[] = $t['status'] === $unregisteredTorrent ? $$unregisteredTorrentShort : $t['status'];
                    }
                }
            } else {
                try {
                    $trackerStatuses[] = $tracker[0]['status'] === $unregisteredTorrent ? $$unregisteredTorrentShort : $tracker[0]['status'];
                } catch (Exception $ex) { }
            }
        } else {
            $trackerStatuses[] = 'Success';
        }

        $task['combinedTrackerStatus'] = implode('<br>', array_unique($trackerStatuses));
        return $task['combinedTrackerStatus'];
    }

    function getStatusHtml($status)
    {
        global $statusPriorOk, $statusPriorOther, $statusPriorIntermediate,
            $statusPriorError, $statusPriorActionable;

        switch (getStatusPriority($status)) {
            case $statusPriorError:
                return '<span class="btn btn-sm btn-danger">' . $status . '</span>';
            case $statusPriorActionable:
                return '<span class="btn btn-sm btn-info">' . $status . '</span>';
            case $statusPriorIntermediate:
                return '<span class="btn btn-sm btn-secondary">' . $status . '</span>';
            case $statusPriorOk:
                return '<span class="btn btn-sm btn-success">' . $status . '</span>';

            case $statusPriorOther:
            default:
                return '<span class="btn btn-sm btn-warning">' . $status . '</span>';
        }
    }

    function getTrackerStatusHtml($trackerStatus)
    {
        global $trackerStatusError, $trackerStatusOk, $trackerStatusOther;

        switch (getTrackerStatusPriority($trackerStatus)) {
            case $trackerStatusOk:
                return '<span class="btn btn-sm btn-success">OK</span>';

            case $trackerStatusError:
                return '<span class="btn btn-sm btn-danger">' . $trackerStatus . '</span>';

            default:
            case $trackerStatusOther:
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
        global $synoUrlDs;
        $sid = $_SESSION['sid'];
        $tasksUrl = "$synoUrlDs/task.cgi?api=SYNO.DownloadStation.Task&version=1&method=list&_sid=$sid&additional=transfer,detail,tracker";

        $decodedRequest = taskAction($tasksUrl);

        if (isset($decodedRequest['data']['tasks']))
            usort($decodedRequest['data']['tasks'], "sortTasks");

        return $decodedRequest;
    }

    function getConfig($elotte)
    {
        global $synoUrlDs, $version;
        $sid = $_SESSION['sid'];
        $configUrl = "$synoUrlDs/info.cgi?api=SYNO.DownloadStation.Info&version=1&method=getconfig&_sid=$sid";

        $config = taskAction($configUrl);

        $baseUrl = getBaseUrl();
        $elapsed = round(microtime(true) - $elotte, 2);
        $btMaxDownloaded = $config['data']['bt_max_download'];
        $btMaxUpload = $config['data']['bt_max_upload'];

        /*
     * [bt_max_download] => 3500
     * [bt_max_upload] => 600
     * */
        die("
        <!DOCTYPE html>
        <html lang='hu'>
        <head>
            <meta charset='UTF-8'>
            <meta http-equiv='content-type' content='text/html; charset=UTF-8'>
            <link type='text/css' rel='stylesheet' href='main.css'/>
            <title>Config - DownloadStationMonitor</title>
            <style type='text/css'>
                html {
                    font-size: 0.9rem;
                }
            </style>
        </head>
        <body class='m-1 p-0'>
        <div class='container-fluid d-flex'>
            <div class='h3 font-italic d-flex flex-grow-1 justify-content-center align-items-center'>Download Station Monitor
            </div>
            <div><a href='$baseUrl' class='btn btn-outline-info'>Főoldal</a></div>
            <div><a href='#' class='btn btn-outline-info'>$elapsed s</a></div>
            <div><a href='#' class='btn btn-outline-info'>v $version</a></div>
        </div>
        <div class='container'>
            <form method='post' class='form-group'>
                <input type='hidden' value='config' name='config'>
                <label for='bt_max_download'>Download limit</label>
                <input type='number' name='bt_max_download' value='$btMaxDownloaded' id='bt_max_download'> <br/>
                <label for='bt_max_upload'>Upload limit</label>
                <input type='number' name='bt_max_upload' value='$btMaxUpload' id='bt_max_upload'> <br/>
        
                <input class='btn' type='submit' value='Save'>
            </form>
        </div>
        </body>
        </html>");
    }

    function setConfig()
    {
        global $synoUrlDs;
        if (!isset($_POST['bt_max_download']) || !is_numeric($_POST['bt_max_download']))
            displayErrorAndDie('Download limit wrong value!');

        if (!isset($_POST['bt_max_upload']) || !is_numeric($_POST['bt_max_upload']))
            displayErrorAndDie('Upload limit wrong value!');

        $btMaxDownloaded = $_POST['bt_max_download'];
        $btMaxUpload = $_POST['bt_max_upload'];
        $sid = $_SESSION['sid'];

        $configUrl = "$synoUrlDs/info.cgi?api=SYNO.DownloadStation.Info&' .
            'version=1&method=setserverconfig&bt_max_download=$btMaxDownloaded&bt_max_upload=$btMaxUpload&_sid=$sid";

        taskAction($configUrl);

        header('Location: ' . getBaseUrl());
        exit();
    }

    function handleSession()
    {
        if (isset($_SESSION['sid'])) return;

        global $synoUrl, $USER, $PASS;

        $loginUrl = "$synoUrl/auth.cgi?api=SYNO.API.Auth&version=2&method=login&account=$USER&passwd=$PASS&session=DownloadStation&format=sid";

        $decodedLogin = taskAction($loginUrl);

        if (isset($decodedLogin['data']['sid']))
            $_SESSION['sid'] = $decodedLogin['data']['sid'];
    }

    function handle2FA()
    {
        if (isset($_POST['otp'])) {
            $_SESSION['otp'] = $_POST['otp'];
        }

        if (!isset($_SESSION['otp'])) {
            die('
            <!DOCTYPE html>
            <html lang="hu">
            <head>
                <meta charset="UTF-8">
                <meta http-equiv="content-type" content="text/html; charset=UTF-8">
                <link type="text/css" rel="stylesheet" href="bootstrap.min.css"/>
                <title>Download Station Monitor</title>
                <style type="text/css">
                    html {
                        font-size: 0.9rem;
                    }
                </style>
            </head>
            <body class="container">
            <h2 class="center"><i>Download Station Monitor</i></h2>
            <form method="POST">
                <div class="form-group">
                    <label for="otp">Two factor authentication</label>
                    <input type="text" class="form-control" id="otp">
                </div>

                <button type="submit" class="btn btn-primary">Send</button>
            </form>
            </body>
            </html>');
        }

        if (!isset($_SESSION['sid'])) {
            global $synoUrl, $USER, $PASS;
            $otp = $_SESSION['otp'];
            $loginUrl = "$synoUrl/auth.cgi?api=SYNO.API.Auth&version=2&method=login&account=$USER&passwd=$PASS&session=DownloadStation&format=sid&otp_code=$otp";

            $decodedLogin = taskAction($loginUrl);

            if (isset($decodedLogin['data']['sid']))
                $_SESSION['sid'] = $decodedLogin['data']['sid'];
            else
                displayErrorAndDie('Unsuccessfuly two factor login!');
        }
    }

    if (isset($_GET['fresh']))
        session_unset();

    if ($TWOFACTOR) {
        handle2FA();
    }

    handleSession();
    // from now on, we are logged in

    if (isset($_POST['config']))
        setConfig();

    if (isset($_GET['config']))
        getConfig($elotte);

    if (isset($_POST['start']))
        startTask();

    if (isset($_POST['pause']))
        pauseTask();

    if (isset($_POST['remove']))
        removeTask();

    $tasks = getTasks();

    $totalDownSpeed = 0;
    $totalUpSpeed = 0;

    $taskNumbers = 0;

    $rows = [];

    foreach ($tasks['data']['tasks'] as $task) {
        $taskNumbers++;

        $id = $task['id'];
        $title = $task['title'];
        $friendlySize = friendlySize((float) $task['size']);

        $sizeDown = (float) (isset($task['additional']['transfer']) ? $task['additional']['transfer']['size_downloaded'] : 0);
        $sizeUp = (float) (isset($task['additional']['transfer']) ? $task['additional']['transfer']['size_uploaded'] : 0);
        $progress = $task['size'] > 0 ? ($sizeDown === $task['size'] ? '100 %' : round($sizeDown / $task['size'] * 100, 2) . ' %') : '0 %';
        if ($progress === '100 %') {
            $progress = '';
        }
        $ratio = round($sizeUp / ($sizeDown > 0 ? $sizeDown : 1), 2);
        $sizeDown = friendlySize($sizeDown);
        $sizeUp = friendlySize($sizeUp);

        $speedDown = (float) (isset($task['additional']['transfer']) ? $task['additional']['transfer']['speed_download'] : 0);
        $totalDownSpeed += $speedDown;
        $speedDown = friendlySpeed($speedDown);

        $speedUp = (float) (isset($task['additional']['transfer']) ? $task['additional']['transfer']['speed_upload'] : 0);
        $totalUpSpeed += $speedUp;
        $speedUp = friendlySpeed($speedUp);
        $trackerStatus = getTrackerStatusHtml(getTrackerStatus($task));

        $taskStatus = getStatusHtml($task['status']);

        $connectedSeeds = (float) (isset($task['additional']['detail']) ? $task['additional']['detail']['connected_seeders'] : 0);
        $connectedLeechers = (float) (isset($task['additional']['detail']) ? $task['additional']['detail']['connected_leechers'] : 0);

        $statusCssClass_ = $statusCssClass[$statusToPriority[$task['status']]];

        /*
         * TODO:
         * + uploaded
         * + seeder/leecher
         * + display current progress if its not 100%
         * - ETA calculation for downloading task
         * - start-stop-pause actions
         * - unregistered torrent displayment improvement
         * - icons on small
         */
        $rows[] = "
        <article id='task$id'>
            <span class='title large medium small'>$title</span>
            <span class='size large medium'>$friendlySize</span>
            <span class='transfer large medium'>$sizeDown / $sizeUp<br>$progress</span>
            <span class='ratio large medium small'>$ratio</span>
            <span class='speed large medium'>$speedDown / $speedUp</span>
            <span class='tracker large medium small'>$trackerStatus</span>
            <span class='connected large medium'>$connectedSeeds / $connectedLeechers</span>
            <span class='status $statusCssClass_ large medium small'>$taskStatus</span>
        </article>";
    }

    $totalDownSpeed = friendlySize($totalDownSpeed);
    $totalUpSpeed = friendlySize($totalUpSpeed);

    $rows[] = "
    <article id='task$id'>
        <span class='title large medium small'>&nbsp;</span>
        <span class='size large medium'>&nbsp;</span>
        <span class='transfer large medium'>&nbsp;</span>
        <span class='ratio large medium small'>&nbsp;</span>
        <span class='speed large medium'>$totalDownSpeed / $totalUpSpeed</span>
        <span class='tracker large medium small'>&nbsp;</span>
        <span class='connected large medium'>&nbsp;</span>
        <span class='status large medium small'>&nbsp;</span>
    </article>";

    $totalTime = round(microtime(true) - $elotte, 2);

    $rows = implode('', $rows);

    echo "<!DOCTYPE html>
<html lang='hu'>

<head>
    <meta charset='UTF-8'>
    <meta http-equiv='content-type' content='text/html; charset=UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <meta http-equiv='refresh' content='$RELOAD'>
    <link type='text/css' rel='stylesheet' href='main.css' />
    <title>DSM</title>
</head>

<body>
    <header>
        <div class='title'>Download Station Monitor </div>
        <div class='settings'><a class='btn btn-outline-info' href='?config'>Settings</a></div>
    </header>
    <main>
        <article class='header'>
            <span class='title large medium small'>Name</span>
            <span class='size large medium'>Size</span>
            <span class='transfer large medium'>Transfer</span>
            <span class='ratio large medium small'>Ratio</span>
            <span class='speed large medium'>Speed</span>
            <span class='tracker large medium small'>Tracker</span>
            <span class='connected large'>Seed/Leech</span>
            <span class='connected medium'>S/L</span>
            <span class='status large medium small'>Status</span>
        </article>
        $rows
    </main>
    <footer>
        <span><a href='javascript:void();' class='btn btn-outline-info'>$totalTime s</a></span>
        <span><a href='javascript:void();' class='btn btn-outline-info'>v " . $version . "</a></span>
    </footer>
</body>

</html>";
} catch (Exception $ex) {
    echo 'Kivétel: ' . $ex->getMessage() . '<br/>' . $ex->getFile() . ' - ' . $ex->getLine();
}
