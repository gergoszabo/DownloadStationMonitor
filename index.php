<?php
    @session_start();

    include 'config.php';
    include 'functions.php';

    auth();

    // first delete previously sent cookies, then re-send it as fresh
    setcookie('theme', DARK === 'igen' ? 'dark' : 'light', time() - 3600);
    setcookie('theme', DARK === 'igen' ? 'dark' : 'light');
    setcookie('version', VERSION, time() - 3600);
    setcookie('version', VERSION);
    setcookie('reload', UJRATOLTES, time() - 3600);
    setcookie('reload', UJRATOLTES);

    include 'page.html';
