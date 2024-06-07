<?php

declare(strict_types = 1);

const PERFORMANCE_CONFIG = [
    // Should the caller information be captured in SQL Logging?
    // It adds little overhead for performance but will be useful to debug
    'SQL_LOG_INCLUDE_CALLER'  => false,

    // If database default charset is UTF-8, set this to true
    // This avoids executing the SET NAMES SQL for each query!
    'DB_DEFAULT_CHARSET_UTF8' => true,

    'HOME_PAGE_WIDGET_GROUP_SIZE'     => 12,
    //take backup legacy style, whenever an admin user logs out.
    'LOGOUT_BACKUP'                   => true,

    //If TRUE - cache number of rows in the lists of entities
    'CACHE_LISTS_COUNT_ALLOWED'       => false,

    //Time (number of seconds) of valid rows count cache (or false, if time validation isn't needed)
    'CACHE_LISTS_COUNT_VALIDATE_TIME' => 600,
];
