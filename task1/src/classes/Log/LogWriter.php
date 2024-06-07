<?php

declare(strict_types = 1);

namespace Log;

use Libraries\database\PearDatabase;
use Libraries\Monolog\DateTimeImmutable;
use Libraries\Monolog\Logger as MonologLogger;

class LogWriter extends MonologLogger
{
    protected bool $loggingEnabled;
    protected array $levelsConfig           = [];
    protected array $collectionLevelsConfig = [];
    protected array $levelsByName           = [];

    public function configureEnabledLevels(array $levels): LogWriter
    {
        $this->levelsConfig = $levels;

        return $this;
    }

    public function configureEnabledLevelsForCollection(array $levels): LogWriter
    {
        $this->collectionLevelsConfig = $levels;

        return $this;
    }

    public function setLevelsByName($levelsByName): LogWriter
    {
        $this->levelsByName = $levelsByName;

        return $this;
    }

    public function setLoggingEnabled(bool $loggingEnabled): LogWriter
    {
        $this->loggingEnabled = $loggingEnabled;

        return $this;
    }

    public function isLoggingEnabled(): bool
    {
        return $this->loggingEnabled ?: false;
    }

    public function isLevelEnabled($level): bool
    {
        return !empty($this->levelsConfig[self::getLevelName($level)]) && in_array($level, $this->collectionLevelsConfig);
    }

    /**
     * Adds a log record.
     *
     *
     * @param  int                                   $level
     * @param  string                                $message
     * @param  array                                 $context
     * @param  \Libraries\Monolog\DateTimeImmutable|null  $datetime  *
     *
     * @return Boolean Whether the record has been processed
     */
    public function addRecord(int $level, string $message, array $context = [], DateTimeImmutable $datetime = null): bool
    {
        if ($this->isLoggingEnabled() && $this->isLevelEnabled($level)) {
            $trace = ['fullTrace' => debug_backtrace()];
            array_walk_recursive($trace, function (&$v, $k) {
                if ($v instanceof PearDatabase) {
                    $v = 'PearDatabase object';
                }
            });
            $clientInfo = [
                'session_id'   => session_id(),
                'client_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'client_ip'    => getRealIpAddr(),
            ];
            $context = array_merge($context, $trace);
            $context = array_merge($context, $clientInfo);
            $message = 'Not formatted data';
            return parent::addRecord($level, $message, $context);
        }

        return false;
    }
}
