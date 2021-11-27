<?php
    @session_start();
    include 'config.php';
    include 'functions.php';

    auth();

    echo getTasks();
