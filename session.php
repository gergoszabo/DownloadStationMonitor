<?php

if (!isset($_SESSION['sid'])) {
    $loginUrl = $_SESSION['config']['protocol'] . '://' . $_SESSION['config']['ip'] . ':' .
        $_SESSION['config']['port'] . '/webapi/auth.cgi?api=SYNO.API.Auth&version=2&method=login' .
        '&account=' . $_SESSION['config']['user'] . '&passwd=' . $_SESSION['config']['pass'] .
        '&session=DownloadStation&format=sid';

    $decodedLogin = json_decode(get($loginUrl), true);

    if (isset($decodedLogin['error'])) {
        displayErrorAndDie(print_r($decodedLogin, true));
    }

    if (isset($decodedLogin['data']['sid']))
        $_SESSION['sid'] = $decodedLogin['data']['sid'];
}
