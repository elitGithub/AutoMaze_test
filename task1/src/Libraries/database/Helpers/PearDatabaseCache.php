<?php

namespace Libraries\database\Helpers;

use Exception;
use Libraries\database\Helpers\PerformancePrefs;
use Log\LogConfig;
use Log\StormLogger;

/**
 * Cache Class for PearDatabase
 */
class PearDatabaseCache
{
    public array $_queryResultCache = [];
    public       $_parent;
    public $log;
    // Cache the result if rows are less than this
    public int $_CACHE_RESULT_ROW_LIMIT;

    /**
     * Constructor
     *
     * @param $parent
     *
     * @throws Exception
     */
    public function __construct ($parent)
    {
        $this->_parent = $parent;
        $this->log = StormLogger::getLogger(LogConfig::QUERY_ERRORS_NAME);
        $this->_CACHE_RESULT_ROW_LIMIT = PerformancePrefs::getInteger('CACHE_RESULT_ROW_LIMIT', 100);
    }


    /**
     * Reset the cache contents
     */
    public function resetCache ()
    {
        unset($this->_queryResultCache);
        $this->_queryResultCache = [];
    }

    /**
     * Cache SQL Query Result (perferably only SELECT SQL)
     *
     * @param      $result
     * @param      $sql
     * @param bool $params
     *
     * @return false|void
     */
    public function cacheResult ($result, $sql, $params = false)
    {
        // We don't want to cache NON-SELECT query results now
        if (stripos(trim($sql), 'SELECT ') !== 0) {
            return;
        }
        // If the result is too big, don't cache it
        if ($this->_parent->num_rows($result) > $this->_CACHE_RESULT_ROW_LIMIT) {
            $this->log->critical(
                "[" . get_class($this) . "] Cannot cache result! $sql [Exceeds limit " .
                $this->_CACHE_RESULT_ROW_LIMIT . ", Total Rows " . $this->_parent->num_rows($result) . "]"
            );
            return false;
        }
        $usekey = $sql;
        if (!empty($params)) {
            $usekey = $this->_parent->convert2Sql($sql, $this->_parent->flatten_array($params));
        }
        $this->_queryResultCache[$usekey] = $result;
    }

    /**
     * Get the cached result for re-use
     *
     * @param      $sql
     * @param bool $params
     *
     * @return false|mixed
     */
    public function getCacheResult ($sql, $params = false)
    {
        $usekey = $sql;
        if (!empty($params)) {
            $usekey = $this->_parent->convert2Sql($sql, $this->_parent->flatten_array($params));
        }
        $result = $this->_queryResultCache[$usekey];
        // Rewind the result for re-use
        if ($result) {
            // If result not in use rewind it
            if ($result->EOF) {
                $result->MoveFirst();
            } elseif ($result->CurrentRow() != 0) {
                $this->log->critical(
                    "[" . get_class($this) . "] Cannot reuse result! $usekey [Rows Total " .
                    $this->_parent->num_rows($result) . ", Currently At: " . $result->CurrentRow() . "]"
                );
                // Do no allow result to be re-used if it is in use.
                $result = false;
            }
        }
        return $result;
    }
}
