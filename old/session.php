<?php

if (!isset($_SESSION['sid'])) {
    $loginUrl = PROTOCOL . '://' . IP . ':' . PORT . '/webapi/auth.cgi?api=SYNO.API.Auth&version=2&method=login' .
        '&account=' . USER . '&passwd=' . PASS . '&session=DownloadStation&format=sid';

    $decodedLogin = json_decode(get($loginUrl), true);

    if (isset($decodedLogin['error'])) {
        displayErrorAndDie(print_r($decodedLogin, true));
    }

    if (isset($decodedLogin['data']['sid']))
        $_SESSION['sid'] = $decodedLogin['data']['sid'];
}
