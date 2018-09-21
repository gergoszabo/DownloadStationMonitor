<?php

if (isset($_POST['otp'])) {
    $_SESSION['otp'] = $_POST['otp'];
}

if (!isset($_SESSION['otp'])) {
    include 'template/twofactor.html';
    die(0);
}

if (!isset($_SESSION['sid'])) {
    $loginUrl = PROTOCOL . '://' . IP . ':' . PORT . '/webapi/auth.cgi?api=SYNO.API.Auth&version=2&method=login' .
        '&account=' . USER . '&passwd=' . PASS . '&session=DownloadStation&format=sid&otp_code=' . $_SESSION['otp'];

    $decodedLogin = json_decode(get($loginUrl), true);

    if (isset($decodedLogin['data']['sid']))
        $_SESSION['sid'] = $decodedLogin['data']['sid'];
    else
        displayErrorAndDie('Sikertelen kétlépcsős bejelentkezés!');
}
