<?php
    define('VERSION', '1.0.0');

function getBaseUrl()
{
    $currentPath = $_SERVER['PHP_SELF']; // /myproject/index.php
    $pathInfo = pathinfo($currentPath); // Array ( [dirname] => /myproject [basename] => index.php [extension] => php [filename] => index )
    $hostName = $_SERVER['HTTP_HOST']; // localhost
    $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"], 0, 5)) == 'https://' ? 'https://' : 'http://';
    return $protocol . $hostName . $pathInfo['dirname']; // http://localhost/myproject/
}

function displayErrorAndDie($error)
{
    die('<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hiba - DownloadStationMonitor</title>
</head>

<body>
    <pre>Hiba történt!</pre>
    <pre>' . print_r($error, true) . '</pre>
</body>

</html>');
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

function getTasks()
{
    $synoUrl = PROTOCOL . '://' . IP . ':' . PORT;
    $tasksUrl = $synoUrl . '/webapi/DownloadStation/task.cgi?api=' .
        'SYNO.DownloadStation.Task&version=1&method=list&_sid=' . $_SESSION['sid'] .
        '&additional=transfer,detail,tracker';

    $response = get($tasksUrl);
    $decodedRequest = json_decode($response, true);

    if (isset($decodedRequest['error'])) {
        displayErrorAndDie($decodedRequest['error']);
    }

    return $response;
}

function auth()
{
    $synoUrl = PROTOCOL . '://' . IP . ':' . PORT;

    // -----    MFA start   -----
    if (TWOFACTOR === 'igen') {
        if (isset($_POST['otp'])) {
            $_SESSION['otp'] = $_POST['otp'];
        }

        if (!isset($_SESSION['otp'])) {
            include 'mfa.html';
            die(0);
        }

        if (!isset($_SESSION['sid'])) {
            $authUrl = $synoUrl . '/webapi/query.cgi?api=SYNO.API.Info&version=1&method=query&query=ALL';

            $result = json_decode(get($authUrl), true);

            $authApi = $result['data']['SYNO.API.Auth'];

            if ($authApi['maxVersion'] >= 6) {
                $path = $authApi['path'];
                $loginUrl = "$synoUrl/webapi/$path.cgi?api=SYNO.API.Auth&version=6&method=login";
            } else {
                $loginUrl = "$synoUrl/webapi/auth.cgi?api=SYNO.API.Auth&version=2&method=login&session=DownloadStation&format=sid";
            }
            $loginUrl = $loginUrl . '&account=' . USER . '&passwd=' . PASS;
            $loginUrl = $loginUrl . '&otp_code='.$_SESSION['otp'];

            // $loginUrl = $synoUrl.'/webapi/auth.cgi?api=SYNO.API.Auth&version=2&method=login' .
            //     '&account=' . USER . '&passwd=' . PASS . '&session=DownloadStation&format=sid&otp_code=' . $_SESSION['otp'];

            $decodedLogin = json_decode(get($loginUrl), true);

            if (isset($decodedLogin['data']['sid']))
                $_SESSION['sid'] = $decodedLogin['data']['sid'];
            else
                displayErrorAndDie('Sikertelen kétlépcsős bejelentkezés!');
        }
    }
    // -----    MFA end     -----

    // -----  AUTH start    ------
    if (!isset($_SESSION['sid'])) {
        $authUrl = $synoUrl . '/webapi/query.cgi?api=SYNO.API.Info&version=1&method=query&query=ALL';

        $result = json_decode(get($authUrl), true);

        $authApi = $result['data']['SYNO.API.Auth'];

        if ($authApi['maxVersion'] >= 6) {
            $path = $authApi['path'];
            $loginUrl = "$synoUrl/webapi/$path.cgi?api=SYNO.API.Auth&version=6&method=login";
        } else {
            $loginUrl = "$synoUrl/webapi/auth.cgi?api=SYNO.API.Auth&version=2&method=login&session=DownloadStation&format=sid";
        }

        if (isset($_POST['otp'])) {
            $_SESSION['otp'] = $_POST['otp'];
        }
        
        isset($_SESSION['otp']) && ($loginUrl = $loginUrl . '&otp_code='.$_SESSION['otp']);
        $url = $loginUrl;
        $loginUrl = $loginUrl . '&account=' . USER . '&passwd=' . PASS;
        
        $decodedLogin = json_decode(get($loginUrl), true);

        if (isset($decodedLogin['error'])) {
            displayErrorAndDie(print_r([$url, $decodedLogin], true));
        }

        if (isset($decodedLogin['data']['sid'])) {
            $_SESSION['sid'] = $decodedLogin['data']['sid'];
            if (isset($_SESSION['otp']))
                unset($_SESSION['otp']);
        }
    }
    // -----  AUTH end  ------
}
