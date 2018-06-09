<?php

define('VERSION', '0.9.8');

define('KB', 1024);
define('MB', KB * 1024);
define('GB', MB * 1024);
define('STATUS_PRIOR_ERROR', 10);
define('STATUS_PRIOR_ACTIONABLE', 8);
define('STATUS_PRIOR_OTHER', 6);
define('STATUS_PRIOR_INTERMEDIATE', 4);
define('STATUS_PRIOR_OK', 1);
define('CONFIG_JSON', 'config.json');

if (isset($_POST['wizzard'])) {
    $_SESSION['config']['ip'] = $_POST['wizzard']['ip'];
    $_SESSION['config']['port'] = $_POST['wizzard']['port'];
    $_SESSION['config']['user'] = $_POST['wizzard']['user'];
    $_SESSION['config']['pass'] = $_POST['wizzard']['pass'];
    $_SESSION['config']['dark'] = isset($_POST['wizzard']['dark']);
    $_SESSION['config']['mod_simple'] = isset($_POST['wizzard']['mod_simple']);
    $_SESSION['config']['ujratoltes'] = $_POST['wizzard']['ujratoltes'];
    $_SESSION['config']['twofactor'] = isset($_POST['wizzard']['twofactor']);
    $_SESSION['config']['protocol'] = $_POST['wizzard']['protocol'];
    $_SESSION['config']['rss'] = isset($_POST['wizzard']['rss']);
    $_SESSION['config']['rss_limit'] = $_POST['wizzard']['rss_limit'];
    $_SESSION['config']['rss_ujratoltes'] = $_POST['wizzard']['rss_ujratoltes'];

    saveConfig();

    if (isset($_SESSION['doConfig']))
        unset($_SESSION['doConfig']);
}

if (file_exists(CONFIG_JSON)) {
    loadConfig();
} else {
    $_SESSION['config']['ip'] = '192.168.0.0';
    $_SESSION['config']['port'] = 5000;
    $_SESSION['config']['user'] = 'user';
    $_SESSION['config']['pass'] = 'pass';
    $_SESSION['config']['dark'] = true;
    $_SESSION['config']['mod_simple'] = true;
    $_SESSION['config']['ujratoltes'] = 30;
    $_SESSION['config']['twofactor'] = false;
    $_SESSION['config']['protocol'] = 'http';
    $_SESSION['config']['rss'] = false;
    $_SESSION['config']['rss_limit'] = 500;
    $_SESSION['config']['rss_ujratoltes'] = 300;

    saveConfig();

    unset($_SESSION['sid']);
    $_SESSION['doConfig'] = true;
}
