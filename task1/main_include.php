<?php

declare(strict_types = 1);
global $dbConfig, $default_language;
require_once 'src/engine/ignition.php';


use Libraries\database\PearDatabase;


if (DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
session_name(SESSION_NAME);
session_start();
ob_start();

if (empty($adb)) {
    $adb = new PearDatabase();
    $adb->connect();
}
