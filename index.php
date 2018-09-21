<?php
@session_start();
/*$_SESSION['protocol'] = 'https';
$_SESSION['host'] = 'gary89.synology.me';
$_SESSION['port'] = '5001';*/

if(isset($_POST['username'])) $_SESSION['username'] = $_POST['username'];
if(isset($_POST['password'])) $_SESSION['password'] = $_POST['password'];
if(isset($_POST['protocol'])) $_SESSION['protocol'] = $_POST['protocol'];
if(isset($_POST['host'])) $_SESSION['host'] = $_POST['host'];
if(isset($_POST['port'])) $_SESSION['port'] = $_POST['port'];

if(count($_POST) > 0) {
    header('Location: /');
    die('');
}

if(isset($_GET['reset'])) {
    unset($_SESSION['sid']);
    unset($_SESSION['protocol']);
    unset($_SESSION['host']);
    unset($_SESSION['port']);
    unset($_SESSION['username']);
    unset($_SESSION['password']);
}

if (!isset($_SESSION['sid']) && !isset($_GET['reset'])) {
    $loginUrl = $_SESSION['protocol'].'://'.$_SESSION['host'].':'.$_SESSION['port'].'/webapi/auth.cgi?api=SYNO.API.Auth&version=2&method=login' .
        '&account='.$_SESSION['username'].'&passwd='.$_SESSION['password'].'&session=DownloadStation&format=sid';

    $decodedLogin = json_decode(get($loginUrl), true);

    if (isset($decodedLogin['data']['sid']))
        $_SESSION['sid'] = $decodedLogin['data']['sid'];
}

function get($url) {
    $ch = curl_init();
    $defaults = array(
        CURLOPT_URL => $url,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
    );
    curl_setopt_array($ch, $defaults);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

if (isset($_GET['tasks'])) {
    $url = $_SESSION['protocol'].'://'.$_SESSION['host'].':'.$_SESSION['port'].'/webapi/DownloadStation/task.cgi?api='.
        'SYNO.DownloadStation.Task&version=1&method=list&_sid='.$_SESSION['sid'].'&additional=transfer,detail,tracker';
    die(get($url));
}

?>

<link href="style.css" rel="stylesheet">
<script src="angular.js"></script>
<script src="app.js"></script>
<body ng-app="app">
    <?=isset($_SESSION['username'])?'<tasks></tasks>':'<config></config>'?>
</body>