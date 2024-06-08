<?php

declare(strict_types = 1);

namespace Log;

use Libraries\Monolog\Handler\StreamHandler;

class StormLogger
{
    protected static array $loggers     = [];
    protected static array $allowedLogs = [
        LogConfig::EXCEPTION_NAME,
        LogConfig::SENDMAIL_NAME,
        LogConfig::QUERY_ERRORS_NAME,
        LogConfig::INSTALL_NAME,
        LogConfig::DEBUG_NAME,
    ];

    /**
     * @param  string  $loggerName
     *
     * @return \Log\LogWriter|mixed
     */
    public static function getLogger(string $loggerName)
    {
        if (!empty(self::$loggers[$loggerName])) {
            return self::$loggers[$loggerName];
        }

        $instance = new self();
        $logWriter = new LogWriter($loggerName);
        if ($instance->isLogAllowed($loggerName) && isset(LogConfig::LOG_CONFIG['LOGGING_ENABLED']) && LogConfig::LOG_CONFIG['LOGGING_ENABLED']) {
            $logWriter
                ->setLoggingEnabled(LogConfig::LOG_CONFIG['LOGGING_ENABLED'])
                ->configureEnabledLevels(LogConfig::LOG_CONFIG['LOG_LEVELS'])
                ->setLevelsByName(LogConfig::LOG_CONFIG['LOG_LEVEL_CODES']);
            if (isset(LogConfig::LOG_CONFIG['LOG_LEVELS_PER_COLLECTION'][$loggerName])) {
                $logWriter->configureEnabledLevelsForCollection(LogConfig::LOG_CONFIG['LOG_LEVELS_PER_COLLECTION'][$loggerName]);
            }
        }

        $handler = new StreamHandler(ROOT_DIR . '/public/logs/' . $loggerName);
        $logWriter->pushHandler($handler);
        self::$loggers[$loggerName] = $logWriter;

        return self::$loggers[$loggerName];
    }

    protected static function isLogAllowed($loggerName): bool
    {
        return in_array($loggerName, self::$allowedLogs);
    }


}
