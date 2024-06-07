<?php

declare(strict_types = 1);

namespace Log;


use Libraries\Monolog\Logger;

class LogConfig
{
    public const DEBUG_NAME        = 'debug';
    public const EXCEPTION_NAME    = 'exceptionLog';
    public const SENDMAIL_NAME     = 'sendmail';
    public const QUERY_ERRORS_NAME = 'queryErrors';
    public const SQL_TIME_NAME     = 'sql_time_log';
    public const INSTALL_NAME      = 'install';

    public const LOG_CONFIG = [
        'LOGGING_ENABLED'           => true,
        'LOG_LEVELS'                => [
            'DEBUG'     => true,
            'INFO'      => true,
            'NOTICE'    => true,
            'WARNING'   => true,
            'ERROR'     => true,
            'CRITICAL'  => true,
            'ALERT'     => true,
            'EMERGENCY' => true,
        ],
        'LOG_LEVEL_CODES'           => [
            'debug'     => Logger::DEBUG,
            'info'      => Logger::INFO,
            'notice'    => Logger::NOTICE,
            'warning'   => Logger::WARNING,
            'error'     => Logger::ERROR,
            'critical'  => Logger::CRITICAL,
            'alert'     => Logger::ALERT,
            'emergency' => Logger::EMERGENCY,
        ],
        'LOG_LEVELS_PER_COLLECTION' => [
            self::DEBUG_NAME        => [
//                LogWriter::DEBUG,
//                LogWriter::INFO,
//                LogWriter::NOTICE,
//                LogWriter::WARNING,
//                LogWriter::ERROR,
//                LogWriter::CRITICAL,
//                LogWriter::ALERT,
//                LogWriter::EMERGENCY,
            ],
            self::EXCEPTION_NAME    => [
                Logger::ERROR,
                Logger::CRITICAL,
            ],
            self::SENDMAIL_NAME     => [
                Logger::INFO,
                Logger::NOTICE,
                Logger::WARNING,
                Logger::ERROR,
                Logger::CRITICAL,
                Logger::ALERT,
                Logger::EMERGENCY,
            ],
            self::QUERY_ERRORS_NAME => [
                Logger::ERROR,
                Logger::CRITICAL,
            ],
            self::INSTALL_NAME      => [
                Logger::ERROR,
                Logger::CRITICAL,
            ],
            self::SQL_TIME_NAME     => [
                LOgger::ERROR,
            ],
        ],
    ];

}
