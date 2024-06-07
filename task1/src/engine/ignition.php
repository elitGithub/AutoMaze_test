<?php

declare(strict_types = 1);

$rootPath = realpath(dirname(__FILE__, 3)); // Adjust the path as needed
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', $rootPath);
}

define('ROOT_DIR', dirname(__FILE__, 3));

const SRC_DIR = ROOT_DIR . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
const MODULES_DIR = 'AutoMaze' . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR;

const APP_NAME = 'AutoMaze';

require_once ROOT_DIR . '/config.php';
require_once SRC_DIR . '/engine/functions.php';

const USER_AVATARS_UPLOAD_DIR = ROOT_DIR . '/public/uploads/userImages';
const SITE_IMAGES_UPLOAD_DIR = ROOT_DIR . '/public/uploads/';
const SESSION_NAME = 'automaze';

const ENVIRONMENT = 'production';

const ALLOWED_MIME_TYPES = [
    'jpg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
];

// Debug mode:
// - false debug mode disabled
// - true  debug mode enabled

if (!defined('DEBUG')) {
    define('DEBUG', true);
}
if (DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(-1);
} else {
    error_reporting(0);
}
// Fix the PHP include path if the system is running under a "strange" PHP configuration
//
$foundCurrPath = false;
$includePaths = explode(PATH_SEPARATOR, ini_get('include_path'));
$i = 0;
while ((!$foundCurrPath) && ($i < count($includePaths))) {
    if ('.' == $includePaths[$i]) {
        $foundCurrPath = true;
    }
    ++$i;
}
if (!$foundCurrPath) {
    ini_set('include_path', '.' . PATH_SEPARATOR . ini_get('include_path'));
}


// Tweak some PHP configuration values
// Warning: be sure the server has enough memory and stack for PHP
ini_set('pcre.backtrack_limit', '100000000');
ini_set('pcre.recursion_limit', '100000000');

require_once 'autoload.php';
