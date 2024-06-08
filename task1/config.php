<?php

global $dbConfig, $dbConfigOption;
$dbConfigOption = [
    'persistent'     => true,
    'autofree'       => false,
    'debug'          => 0,
    'seqname_format' => '%s_seq',
    'portability'    => 0,
    'ssl'            => false,
];

$dbConfig          = [
    'db_user' => 'root',
    'db_pass' => 'root',
    'db_host' => 'localhost',
    'db_port' => 0,
    'db_name' => SRC_DIR . '/Libraries/database/automaze.db',
    'db_type' => 'sqlite3',
    'log_sql' => false,
];

$default_charset = 'utf-8';
$default_language = 'en_us';
$application_language = 'en_us';
ini_set('memory_limit', '1024M');  // Increase memory limit
const MAX_ALLOWED_FILE_SIZE = 3000000;
$gitClientSecret = 'a2ac03d150e033e2fe69bde4f3550c59b2f900e9';
$gitClientId = 'Ov23limwerrf227MKAtR';
