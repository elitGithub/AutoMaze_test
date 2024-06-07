<?php

declare(strict_types = 1);

namespace Libraries\database;

use ADOConnection;
use adoSchema;
use Libraries\database\Helpers\PearDatabaseCache;
use Libraries\database\Helpers\PerformancePrefs;
use Libraries\database\Helpers\PreparedQMark2SqlValue;
use Exception;
use Log\LogConfig;
use Log\LogWriter;
use Log\StormLogger;
use Throwable;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

include_once('config.performance.php');

require_once 'adodb/adodb-php/adodb.inc.php';
require_once 'adodb/adodb-php/adodb-exceptions.inc.php';

/**
 * Database wrapper classes for ADODB connection handling.
 */
class PearDatabase
{
    public ?ADOConnection $database = null;

    /**
     * @var bool
     */
    public bool $dieOnError = false;
    /**
     * @var mixed
     */
    public         $dbType                 = null;
    /**
     * @var int|string|null
     */
    public         $dbPort                 = null;
    public ?string $dbHostName             = null;
    public         $dbName                 = null;
    public         $dbOptions              = null;
    public         $userName               = null;
    public         $userPassword           = null;
    public         $query_time             = 0;
    public         $lastmysqlrow           = -1;
    public         $enableSQLlog           = false;
    public         $continueInstallOnError = true;

    public LogWriter $log;
    public $logsqltm;
    // If you want to avoid executing PreparedStatement, set this to true
    // PreparedStatement will be converted to normal SQL statement for execution
    public $avoidPreparedSql = false;

    /**
     * Performance tunning parameters (can be configured through performance.prefs.php)
     * See the constructor for initialization
     */
    public $isdb_default_utf8_charset = false;
    public $enableCache               = false;

    /**
     * @var mixed
     */
    public $_cacheinstance = null; // Will be automatically initialized if $enableCache is true


    /**
     * Constructor
     *
     * @param  string  $dbtype
     * @param  string  $host
     * @param  string  $dbname
     * @param  string  $username
     * @param  string  $passwd
     *
     * @throws Exception
     */
    public function __construct($dbtype = '', $host = '', $dbname = '', $username = '', $passwd = '', $dbPort = '')
    {
        global $dbConfig;
        $this->log = StormLogger::getLogger(LogConfig::QUERY_ERRORS_NAME);
        $this->logsqltm = StormLogger::getLogger(LogConfig::SQL_TIME_NAME);
        $this->resetSettings($dbtype, $host, $dbname, $username, $passwd, $dbPort);

        // Initialize performance parameters
        $this->isdb_default_utf8_charset = PerformancePrefs::getBoolean('DB_DEFAULT_CHARSET_UTF8');
        $this->enableCache = PerformancePrefs::getBoolean('CACHE_QUERY_RESULT', false);
        // END

        if (!isset($this->dbType)) {
            $this->log->error('ADODB Connect : DBType not specified');
            return;
        }
        // Initialize the cache object to use.
        if (isset($this->enableCache) && $this->enableCache) {
            $this->setCacheInstance(new PearDatabaseCache($this));
        }
        // END
    }

    /**
     * API's to control cache behavior
     *
     * @param $cacheInstance
     */
    public function setCacheInstance($cacheInstance)
    {
        $this->_cacheinstance = $cacheInstance;
    }

    /** Return the cache instance reference (using &) */
    public function &getCacheInstance()
    {
        return $this->_cacheinstance;
    }

    public function isCacheEnabled(): bool
    {
        return ($this->enableCache && ($this->getCacheInstance() != false));
    }

    public function clearCache()
    {
        if ($this->isCacheEnabled()) {
            $this->getCacheInstance()->resetCache();
        }
    }

    public function toggleCache($newstatus)
    {
        $oldstatus = $this->enableCache;
        $this->enableCache = $newstatus;
        return $oldstatus;
    }
    // END

    /**
     * Manage instance usage of this class
     */
    public static function &getInstance(): PearDatabase
    {
        global $adb;

        if (!isset($adb)) {
            $adb = new self();
        }
        return $adb;
    }
    // END

    /**
     * @return mixed
     */
    public function getTablesConfig()
    {
        global $dbConfig;
        return $dbConfig['tables'];
    }

    /*
     * Reset query result for resuing if cache is enabled.
     */
    public function resetQueryResultToEOF(&$result)
    {
        if ($result) {
            if ($result->MoveLast()) {
                $result->MoveNext();
            }
        }
    }

    // END

    /**
     * @return bool
     */
    public function isMySQL(): bool
    {
        return (stripos($this->dbType, 'mysql') === 0);
    }

    /**
     * @return bool
     */

    public function isOracle(): bool
    {
        return $this->dbType === 'oci8';
    }

    /**
     * @return bool
     */
    public function isPostgres(): bool
    {
        return $this->dbType === 'pgsql';
    }

    /**
     * @return bool
     */
    public function isSqLite(): bool
    {
        return ($this->dbType === 'sqlite3') || $this->dbType === 'sqlite';
    }

    /**
     * @return bool
     */
    public function isIbmDb2(): bool
    {
        return ($this->dbType === 'db2');
    }

    /**
     * @return bool
     */
    public function isSqlSrv(): bool
    {
        return $this->dbType === 'sqlsrv';
    }

    /**
     * @param $value
     *
     * @return void
     */
    public function setDieOnError($value)
    {
        $this->dieOnError = $value;
    }

    /**
     * @param $type
     *
     * @return void
     */
    public function setDatabaseType($type)
    {
        $this->dbType = $type;
    }

    /**
     * @param $name
     *
     * @return void
     */
    public function setUserName($name)
    {
        $this->userName = $name;
    }

    public function setOption($name, $value)
    {
        if (isset($this->dbOptions)) {
            $this->dbOptions[$name] = $value;
        }
        if (isset($this->database)) {
            $this->database->setOption($name, $value);
        }
    }

    public function setUserPassword($pass) { $this->userPassword = $pass; }

    public function setDatabaseName($db) { $this->dbName = $db; }

    public function setDatabaseHost($host) { $this->dbHostName = $host; }

    public function getDataSourceName()
    {
        return $this->dbType . '://' . $this->userName . ':' . $this->userPassword . '@' . $this->dbHostName . '/' . $this->dbName;
    }

    public function startTransaction()
    {
        if ($this->isPostgres()) {
            return;
        }
        $this->checkConnection();
        $this->database->StartTrans();
    }

    public function completeTransaction()
    {
        if ($this->isPostgres()) {
            return;
        }

        $this->database->CompleteTrans();
    }

    public function hasFailedTransaction() { return $this->database->HasFailedTrans(); }

    public function checkError($msg = '', $dieOnError = false, $sql = '')
    {
        $context = [
            'query' => $sql,
        ];
        if ($this->dieOnError || $dieOnError) {
            $bt = debug_backtrace();
            $ut = [];
            foreach ($bt as $t) {
                $ut[] = ['file' => $t['file'], 'line' => $t['line'], 'function' => $t['function']];
            }

            $this->log->error(
                'ADODB error ' . $msg . '->[' . $this->database->ErrorNo() . ']' . $this->database->ErrorMsg() . var_export($ut),
                $context
            );
        } else {
            $this->log->error('ADODB error ' . $msg . '->[' . $this->database->ErrorNo() . ']' . $this->database->ErrorMsg(), $context);
        }
        return false;
    }

    public function change_key_case($arr)
    {
        return is_array($arr) ? array_change_key_case($arr) : $arr;
    }

    public $req_flist;

    public function checkConnection()
    {
        if (!isset($this->database)) {
            $this->connect(false);
        }
    }

    /**
     * Put out the SQL timing information
     *
     * @param        $startat
     * @param        $endat
     * @param        $sql
     * @param  bool  $params
     */
    public function logSqlTiming($startat, $endat, $sql, $params = [])
    {
        // Specifically for timing the SQL execution, you need to enable DEBUG in log4php.properties
        if (PerformancePrefs::getBoolean('SQL_LOG_INCLUDE_CALLER')) {
            $callers = debug_backtrace();
            $callerscount = count($callers);
            $callerfunc = '';
            for ($calleridx = 0; $calleridx < $callerscount; ++$calleridx) {
                if ($calleridx == 0) {
                    // Ignore the first caller information, it will be generally from this file!
                    continue;
                }
                // Caller function will be in next information block
                if ($calleridx < $callerscount) {
                    $callerfunc = $callers[$calleridx + 1]['function'];
                    if (!empty($callerfunc)) {
                        $callerfunc = " ($callerfunc) ";
                    }
                }
                $this->logsqltm->info(
                    'CALLER: (' . $callers[$calleridx]['line'] . ') ' .
                    $callers[$calleridx]['file'] . $callerfunc
                );
                $this->logsqltm->info('SQL: ' . $sql);
                if ($params != null && count($params) > 0) {
                    $this->logsqltm->info('PARAMS: [' . implode(',', $params) . ']');
                }
                $this->logsqltm->info('EXEC: ' . ($endat - $startat) . " micros [START=$startat, END=$endat]");
            }
        }
    }

    /**
     * Execute SET NAMES UTF-8 on the connection based on configuration.
     *
     * @param  bool  $force
     */
    public function executeSetNamesUTF8SQL($force = false)
    {
        global $default_charset;
        // Performance Tuning: If database default charset is UTF-8, we don't need this
        if (strtoupper($default_charset) === 'UTF-8' && ($force || !$this->isdb_default_utf8_charset) && !$this->isSqLite()) {
            $sql_start_time = microtime(true);

            $setnameSql = 'SET NAMES utf8';
            $this->database->Execute($setnameSql);
            $this->logSqlTiming($sql_start_time, microtime(true), $setnameSql);
        }
    }

    /**
     * Execute query in a batch.
     *
     * For example:
     * INSERT INTO TABLE1 VALUES (a,b);
     * INSERT INTO TABLE1 VALUES (c,d);
     *
     * like: INSERT INTO TABLE1 VALUES (a,b), (c,d)
     *
     * @param $prefixsql
     * @param $valuearray
     */
    public function query_batch($prefixsql, $valuearray)
    {
        if (PerformancePrefs::getBoolean('ALLOW_SQL_QUERY_BATCH')) {
            $suffixsql = $valuearray;
            if (!is_array($valuearray)) {
                $suffixsql = implode(',', $valuearray);
            }
            $this->query($prefixsql . $suffixsql);
        } elseif (is_array($valuearray) && !empty($valuearray)) {
            foreach ($valuearray as $suffixsql) {
                $this->query($prefixsql . $suffixsql);
            }
        }
    }

    public function query($sql, $dieOnError = false, $msg = '')
    {
        global $default_charset;
        // Performance Tuning: Have we cached the result earlier?
        if ($this->isCacheEnabled()) {
            $fromcache = $this->getCacheInstance()->getCacheResult($sql);
            if ($fromcache) {
                return $fromcache;
            }
        }
        // END

        $this->checkConnection();

        $this->executeSetNamesUTF8SQL();

        $sql_start_time = microtime(true);
        $recordSet = $this->database->Execute($sql);
        $result = &$recordSet;
        $this->logSqlTiming($sql_start_time, microtime(true), $sql);

        $this->lastmysqlrow = -1;
        if (!$result) {
            $this->checkError($msg . ' Query Failed:' . $sql . '::', $dieOnError, $sql);
        }

        // Performance Tuning: Cache the query result
        if ($this->isCacheEnabled()) {
            $this->getCacheInstance()->cacheResult($result, $sql);
        }
        // END
        return $result;
    }


    /**
     * Convert PreparedStatement to SQL statement
     */
    public function convert2Sql($ps, $vals)
    {
        if (empty($vals)) {
            return $ps;
        }
        // TODO: Checks need to be added array out of bounds situations
        for ($index = 0; $index < count($vals); $index++) {
            // Package import pushes data after XML parsing, so type-cast it
            if (is_a($vals[$index], 'SimpleXMLElement')) {
                $vals[$index] = (string) $vals[$index];
            }
            if (is_string($vals[$index])) {
                if ($vals[$index] == '') {
                    $vals[$index] = $this->database->Quote($vals[$index]);
                } else {
                    $vals[$index] = $this->sql_escape_string($vals[$index]);
                }
            }
            if ($vals[$index] === null) {
                $vals[$index] = 'NULL';
            }
        }
        return preg_replace_callback("/('[^']*')|(\"[^\"]*\")|([?])/", [new PreparedQMark2SqlValue($vals), 'call'], $ps);
    }

    /**
     * @param          $sql
     * @param  array   $params
     * @param  bool    $dieOnError
     * @param  string  $msg
     *
     * @return mixed
     */
    public function preparedQuery($sql, array $params = [], bool $dieOnError = false, string $msg = '')
    {
        return $this->pquery($sql, $params, $dieOnError, $msg);
    }

    /* ADODB prepared statement Execution
     * @param $sql -- Prepared sql statement
     * @param $params -- Parameters for the prepared statement
     * @param $dieOnError -- Set to true, when query execution fails
     * @param $msg -- Error message on query execution failure
     */
    public function pquery($sql, $params = [], $dieOnError = false, $msg = '')
    {
        global $default_charset;
        if (!defined('ADODB_ERROR_HANDLER')) {
            // Force exception throwing, like PDO::ERRMODE_EXCEPTION
            define('ADODB_ERROR_HANDLER', 'adodb_throw');
        }
        // Performance Tuning: Have we cached the result earlier?
        if ($this->isCacheEnabled()) {
            $fromcache = $this->getCacheInstance()->getCacheResult($sql, $params);
            if ($fromcache) {
                return $fromcache;
            }
        }
        // END
        $this->checkConnection();

        $this->executeSetNamesUTF8SQL();

        $sql_start_time = microtime(true);
        $params = $this->flatten_array($params);

        if ($this->avoidPreparedSql || empty($params)) {
            $sql = $this->convert2Sql($sql, $params);
            $tmpRes = $this->database->Execute($sql);
        } else {
            try {
                $tmpRes = $this->database->Execute($sql, $params);
            } catch (Throwable $e) {
                $this->checkError($e->getMessage() . ' ' . $msg . ' Query Failed:' . $this->convert2Sql($sql, $params) . '::', $dieOnError, $sql);
                return false;
            }
        }
        $result = &$tmpRes;
        $sql_end_time = microtime(true);
        $this->logSqlTiming($sql_start_time, $sql_end_time, $sql, $params);

        $this->lastmysqlrow = -1;
        if (!$result) {
            $this->checkError($msg . ' Query Failed:' . $this->convert2Sql($sql, $params) . '::', $dieOnError, $sql);
        }

        // Performance Tuning: Cache the query result
        if ($this->isCacheEnabled()) {
            $this->getCacheInstance()->cacheResult($result, $sql, $params);
        }
        // END
        return $result;
    }

    /**
     * Flatten the composite array into single value.
     * Example:
     * $input = array(10, 20, array(30, 40), array('key1' => '50', 'key2'=>array(60), 70));
     * returns array(10, 20, 30, 40, 50, 60, 70);
     */
    public function flatten_array($input, $output = null)
    {
        if ($input == null) {
            return null;
        }
        if ($output == null) {
            $output = [];
        }
        foreach ($input as $value) {
            if (is_array($value)) {
                $output = $this->flatten_array($value, $output);
            } else {
                $output[] = $value;
            }
        }
        return $output;
    }

    public function getEmptyBlob($is_string = true)
    {
        if ($is_string) {
            return 'null';
        }
        return null;
    }

    public function updateBlob($tablename, $colname, $id, $data)
    {
        $this->checkConnection();
        $this->executeSetNamesUTF8SQL();

        $sql_start_time = microtime(true);
        $result = $this->database->UpdateBlob($tablename, $colname, $data, $id);
        $this->logSqlTiming($sql_start_time, microtime(true), "Update Blob $tablename, $colname, $id");

        return $result;
    }

    public function updateBlobFile($tablename, $colname, $id, $filename)
    {
        $this->checkConnection();
        $this->executeSetNamesUTF8SQL();

        $sql_start_time = microtime(true);
        $result = $this->database->UpdateBlobFile($tablename, $colname, $filename, $id);
        $this->logSqlTiming($sql_start_time, microtime(true), "Update Blob $tablename, $colname, $id");
        return $result;
    }

    public function limitQuery($sql, $start, $count, $dieOnError = false, $msg = '')
    {
        $this->checkConnection();

        $this->executeSetNamesUTF8SQL();

        $sql_start_time = microtime(true);
        $recordSet = $this->database->SelectLimit($sql, $count, $start);
        $result = &$recordSet;
        $this->logSqlTiming($sql_start_time, microtime(true), "$sql LIMIT $count, $start");

        if (!$result) {
            $this->checkError($msg . ' Limit Query Failed:' . $sql . '::', $dieOnError, $sql);
        }
        return $result;
    }

    public function getOne($sql, $dieOnError = false, $msg = '')
    {
        $this->checkConnection();

        $this->executeSetNamesUTF8SQL();

        $sql_start_time = microtime(true);
        $oneRecord = $this->database->GetOne($sql);
        $result = &$oneRecord;
        $this->logSqlTiming($sql_start_time, microtime(true), "$sql GetONE");

        if (!$result) {
            $this->checkError($msg . ' Get one Query Failed:' . $sql . '::', $dieOnError, $sql);
        }
        return $result;
    }

    public function getFieldsDefinition(&$result)
    {
        $field_array = [];
        if (empty($result)) {
            return 0;
        }

        $i = 0;
        $n = $result->FieldCount();
        while ($i < $n) {
            $meta = $result->FetchField($i);
            if (!$meta) {
                return 0;
            }
            $field_array[] = $meta;
            $i++;
        }

        return $field_array;
    }

    public function getFieldsArray(&$result)
    {
        $field_array = [];
        if (empty($result)) {
            return 0;
        }

        $i = 0;
        $n = $result->FieldCount();
        while ($i < $n) {
            $meta = $result->FetchField($i);
            if (!$meta) {
                return 0;
            }
            $field_array[] = $meta->name;
            $i++;
        }

        return $field_array;
    }

    public function getRowCount(&$result)
    {
        $rows = 0;
        if (!empty($result)) {
            $rows = $result->RecordCount();
        }
        return max($rows, 0);
    }

    /* ADODB newly added. replacement for mysql_num_rows */
    public function num_rows(&$result)
    {
        return $this->getRowCount($result);
    }

    /* ADODB newly added. replacement form mysql_num_fields */
    public function num_fields(&$result)
    {
        return $result->FieldCount();
    }

    /* ADODB newly added. replacement for mysql_fetch_array() */
    public function fetch_array(&$result)
    {
        if (is_object($result)) {
            if ($result->EOF) {
                return null;
            }
            $arr = $result->FetchRow();
            if (is_array($arr)) {
                $arr = array_map('to_html', $arr);
            }
            return $this->change_key_case($arr);
        }
    }

    ## adds new functions to the PearDatabase class to come around the whole
    ## broken query_result() idea
    ## Code-Contribution given by weigelt@metux.de - Starts
    public function run_query_record_html($query)
    {
        if (!is_array($rec = $this->run_query_record($query))) {
            return $rec;
        }
        foreach ($rec as $walk => $cur) {
            $r[$walk] = to_html($cur);
        }
        return $r;
    }

    public function sql_quote($data)
    {
        if (is_array($data)) {
            switch ($data['type']) {
                case 'text':
                case 'numeric':
                case 'integer':
                case 'oid':
                    return $this->quote($data['value']);
                case 'timestamp':
                    return $this->formatDate($data['value']);
                default:
                    throw new Exception('unhandled type: ' . serialize($data));
            }
        } else {
            return $this->quote($data);
        }
    }

    public function sql_insert_data($table, $data)
    {
        if (!$table) {
            throw new Exception('missing table name');
        }
        if (!is_array($data)) {
            throw new Exception('data must be an array');
        }
        if (!count($table)) {
            throw new Exception('no data given');
        }
        $sql_fields = '';
        $sql_data = '';
        foreach ($data as $walk => $cur) {
            $sql_fields .= ($sql_fields ? ',' : '') . $walk;
            $sql_data .= ($sql_data ? ',' : '') . $this->sql_quote($cur);
        }
        return 'INSERT INTO ' . $table . ' (' . $sql_fields . ') VALUES (' . $sql_data . ')';
    }

    /**
     * @param $table
     * @param $data
     *
     * @return void
     * @throws \Exception
     */
    public function run_insert_data($table, $data)
    {
        $query = $this->sql_insert_data($table, $data);
        $this->query($query);
        $this->query('commit;');
    }

    /**
     * @param $query
     *
     * @return array|mixed|void
     * @throws \Exception
     */
    public function run_query_record($query)
    {
        $result = $this->query($query);
        if (!$result) {
            return;
        }
        if (!is_object($result)) {
            throw new Exception("query \"$query\" failed: " . serialize($result));
        }
        $res = $result->FetchRow();
        return $this->change_key_case($res);
    }

    /**
     * @param $query
     *
     * @return array
     */
    public function run_query_allrecords($query): array
    {
        $result = $this->query($query);
        $records = [];
        $sz = $this->num_rows($result);
        for ($i = 0; $i < $sz; $i++) {
            $records[$i] = $this->change_key_case($result->FetchRow());
        }
        return $records;
    }

    /**
     * @param $query
     * @param $field
     *
     * @return mixed|null
     * @throws \Exception
     */
    public function run_query_field($query, $field = '')
    {
        $rowdata = $this->run_query_record($query);
        if (isset($field) && $field != '') {
            return $rowdata[$field];
        } else {
            return array_shift($rowdata);
        }
    }

    public function run_query_list($query, $field)
    {
        $list = [];
        $records = $this->run_query_allrecords($query);
        foreach ($records as $walk => $cur) {
            $list[] = $cur[$field];
        }

        return $list;
    }

    public function run_query_field_html($query, $field)
    {
        return to_html($this->run_query_field($query, $field));
    }

    public function result_get_next_record($result)
    {
        return $this->change_key_case($result->FetchRow());
    }

    // create an IN expression from an array/list
    public function sql_expr_datalist($a)
    {
        if (!is_array($a)) {
            throw new Exception('not an array');
        }
        if (!count($a)) {
            throw new Exception('empty arrays not allowed');
        }

        foreach ($a as $walk => $cur) {
            $l .= ($l ? ',' : '') . $this->quote($cur);
        }
        return ' ( ' . $l . ' ) ';
    }

    // create an IN expression from a record list, take $field within each record
    public function sql_expr_datalist_from_records($a, $field)
    {
        if (!is_array($a)) {
            throw new Exception('not an array');
        }
        if (!$field) {
            throw new Exception('missing field');
        }
        if (!count($a)) {
            throw new Exception('empty arrays not allowed');
        }

        foreach ($a as $walk => $cur) {
            $l .= ($l ? ',' : '') . $this->quote($cur[$field]);
        }

        return ' ( ' . $l . ' ) ';
    }

    public function sql_concat($list)
    {
        switch ($this->dbType) {
            case 'mysql':
            case 'mysqli':
                return 'concat(' . implode(',', $list) . ')';
            case 'pgsql':
                return '(' . implode('||', $list) . ')';
            default:
                throw new Exception("unsupported dbtype \"" . $this->dbType . "\"");
        }
    }
    ## Code-Contribution given by weigelt@metux.de - Ends

    /**
     * @param $result
     * @param int|string $row
     * @param int|string $col
     *
     * @return array|mixed|string|string[]|null
     * @throws \Exception
     */
    public function query_result(&$result, $row, $col = 0)
    {
        if (!is_object($result)) {
            $exception = new Exception('result is not an object');
            $this->log->error($exception->getMessage());
            throw $exception;
        }

        $result->Move($row);
        $rowdata = $this->change_key_case($result->FetchRow());
        //Commented strip_selected_tags and added to_html function for HTML tags vulnerability
        return is_array($rowdata) ? to_html($rowdata[$col]) : $rowdata;
    }

    // Function to get particular row from the query result
    public function query_result_rowdata(&$result, $row = 0)
    {
        if (!is_object($result)) {
            throw new Exception('result is not an object');
        }
        $result->Move($row);
        $rowdata = $this->change_key_case($result->FetchRow());

        foreach ($rowdata as $col => $coldata) {
            if ($col != 'fieldlabel') {
                $rowdata[$col] = to_html($coldata);
            }
        }
        return $rowdata;
    }

    /**
     * Get an array representing a row in the result set
     * Unlike it's non raw siblings this method will not escape
     * html entities in return strings.
     *
     * The case of all the field names is converted to lower case.
     * as with the other methods.
     *
     * @param  mixed &$result  The query result to fetch from.
     * @param  int    $row  The row number to fetch. It's default value is 0
     *
     * @return array|mixed
     * @throws Exception
     */
    public function raw_query_result_rowdata(&$result, $row = 0)
    {
        if (!is_object($result)) {
            throw new Exception('result is not an object');
        }
        $result->Move($row);
        return $this->change_key_case($result->FetchRow());
    }


    public function getAffectedRowCount(&$result)
    {
        $rows = $this->database->Affected_Rows();
        $this->log->debug('getAffectedRowCount rows = ' . $rows);
        return $rows;
    }

    public function requireSingleResult($sql, $dieOnError = false, $msg = '', $encode = true)
    {
        $result = $this->query($sql, $dieOnError, $msg);

        if ($this->getRowCount($result) == 1) {
            return $result;
        }
        $this->log->error('Rows Returned:' . $this->getRowCount($result) . ' More than 1 row returned for ' . $sql);
        return '';
    }



    /**
     * @param $sql
     * @param $params
     * @param $dieOnError
     * @param $msg
     * @param $encode
     *
     * @return \ADORecordSet|\ADORecordSet_array|\Libraries\database\adodb\ADORecordSet_empty|bool|string
     */
    public function requirePsSingleResult($sql, $params, $dieOnError = false, $msg = '', $encode = true)
    {
        $result = $this->pquery($sql, $params, $dieOnError, $msg);
        $cnt = $this->getRowCount($result);
        if ($cnt != 1) {
            $result = '';
            if ($cnt > 1) {
                $this->log->error('Returned rows count: ' . $this->getRowCount($result) . ' More than 1 row returned for ' . $sql);
            }
        }
        return $result;
    }

    /**
     * @param $result
     * @param $rowNum
     * @param $encode
     *
     * @return array|void|null
     */
    public function fetchByAssoc(&$result, $rowNum = -1, $encode = true)
    {
        if (is_object($result)) {
            if ($result->EOF) {
                return null;
            }
            if ($rowNum < 0) {
                $row = $this->change_key_case($result->GetRowAssoc(false));
                $result->MoveNext();
                if ($encode && is_array($row)) {
                    return array_map('to_html', $row);
                }
                return $row;
            }

            if ($this->getRowCount($result) > $rowNum) {
                $result->Move($rowNum);
            }
            $this->lastmysqlrow = $rowNum;
            $row = $this->change_key_case($result->GetRowAssoc(false));
            $result->MoveNext();

            if ($encode && is_array($row)) {
                return array_map('to_html', $row);
            }
            return $row;
        }
    }

    public function getNextRow(&$result, $encode = true)
    {
        $this->log->debug('getNextRow');
        if (isset($result)) {
            $row = $this->change_key_case($result->FetchRow());
            if ($row && $encode && is_array($row)) {
                return array_map('to_html', $row);
            }
            return $row;
        }
        return null;
    }

    public function fetch_row(&$result, $encode = true)
    {
        return $this->getNextRow($result);
    }

    public function field_name(&$result, $col)
    {
        return $result->FetchField($col);
    }

    public function getQueryTime()
    {
        return $this->query_time;
    }

    /**
     * @param  bool  $dieOnError
     *
     * @return void
     */
    public function connect(bool $dieOnError = false)
    {
        global $dbConfigOption, $dbConfig;
        if (!isset($this->dbType)) {
            return;
        }

        if ($dieOnError) {
            $this->setDieOnError($dieOnError);
        }

        $this->database = NewADOConnection($this->dbType);
        $this->dbHostName = (int)$this->dbPort > 0 ? $this->dbHostName . ':' . $this->dbPort : $this->dbHostName;
        $this->database->PConnect($this->dbHostName, $this->userName, $this->userPassword, $this->dbName);
        $this->database->LogSQL($this->enableSQLlog);

        // 'SET NAMES UTF8' needs to be executed even if a database has default CHARSET UTF8
        // as mysql server might be running with different charset!
        // We will notice problem reading UTF8 characters otherwise.
        if ($this->isdb_default_utf8_charset) {
            $this->executeSetNamesUTF8SQL(true);
        }
    }

    public function resetSettings($dbtype, $host, $dbname, $username, $passw, $dbPort)
    {
        global $dbConfig, $dbConfigOption;

        $this->disconnect();
        if ($host == '') {
            $this->setDatabaseType($dbConfig['db_type']);
            $this->setUserName($dbConfig['db_user']);
            $this->setUserPassword($dbConfig['db_pass']);
            $this->setDatabaseHost($dbConfig['db_host']);
            $this->setDatabaseName($dbConfig['db_name']);
            $this->setDbPort($dbConfig['db_port']);
            $this->dbOptions = $dbConfigOption;
            if ($dbConfig['log_sql']) {
                $this->enableSQLlog = ($dbConfig['log_sql'] == true);
            }
        } else {
            $this->setDatabaseType($dbtype);
            $this->setDatabaseName($dbname);
            $this->setUserName($username);
            $this->setUserPassword($passw);
            $this->setDatabaseHost($host);
            $this->setDbPort($dbPort);
        }
    }

    public function quote($string)
    {
        return $this->database->qstr($string);
    }

    public function real_escape($string)
    {
        return mysqli_real_escape_string($this->database->_connectionID, $string);
    }

    public function free_result($result)
    {
        mysqli_free_result($result);
    }

    public function error()
    {
        return mysqli_error($this->database->_connectionID);
    }

    public function disconnect()
    {
        if (isset($this->database)) {
            $this->database->disconnect();
            unset($this->database);
        }
    }

    public function setDebug($value)
    {
        $this->database->debug = $value;
    }

    // ADODB newly added methods
    public function createTables($schemaFile, $dbHostName = false, $userName = false, $userPassword = false, $dbName = false, $dbType = false)
    {
        if ($dbHostName != false) {
            $this->dbHostName = $dbHostName;
        }
        if ($userName != false) {
            $this->userName = $userPassword;
        }
        if ($userPassword != false) {
            $this->userPassword = $userPassword;
        }
        if ($dbName != false) {
            $this->dbName = $dbName;
        }
        if ($dbType != false) {
            $this->dbType = $dbType;
        }

        $this->checkConnection();
        $db = $this->database;
        $schema = new adoSchema($db);
        //Debug Adodb XML Schema
        $schema->XMLS_DEBUG = true;
        //Debug Adodb
        $schema->debug = true;
        $sql = $schema->ParseSchema($schemaFile);

        $result = $schema->ExecuteSchema($sql, $this->continueInstallOnError);
        if ($result) {
            print $db->errorMsg();
        }
        // needs to return in a decent way
        return $result;
    }

    public function createTable($tablename, $flds)
    {
        $this->checkConnection();
        $dict = NewDataDictionary($this->database);
        $sqlarray = $dict->CreateTableSQL($tablename, $flds);
        return $dict->ExecuteSQLArray($sqlarray);
    }

    public function alterTable($tablename, $flds, $oper)
    {
        $this->checkConnection();
        $dict = NewDataDictionary($this->database);

        if ($oper === 'Add_Column') {
            $sqlarray = $dict->AddColumnSQL($tablename, $flds);
        } elseif ($oper === 'Delete_Column') {
            $sqlarray = $dict->DropColumnSQL($tablename, $flds);
        }

        return $dict->ExecuteSQLArray($sqlarray);
    }

    public function getColumnNames($tablename)
    {
        $this->checkConnection();
        $adoflds = $this->database->MetaColumns($tablename);
        $i = 0;
        foreach ($adoflds as $fld) {
            $colNames[$i] = $fld->name;
            $i++;
        }
        return $colNames;
    }

    public function formatString($tablename, $fldname, $str)
    {
        $this->checkConnection();
        $adoflds = $this->database->MetaColumns($tablename);

        foreach ($adoflds as $fld) {
            if (strcasecmp($fld->name, $fldname) == 0) {
                $fldtype = strtoupper($fld->type);
                if (strcmp($fldtype, 'CHAR') === 0 ||
                    strcmp($fldtype, 'VARCHAR') === 0 ||
                    strcmp($fldtype, 'VARCHAR2') === 0 ||
                    strcmp($fldtype, 'LONGTEXT') == 0 ||
                    strcmp($fldtype, 'TEXT') == 0) {
                    return $this->database->Quote($str);
                } elseif (strcmp($fldtype, 'DATE') == 0 || strcmp($fldtype, 'TIMESTAMP') == 0) {
                    return $this->formatDate($str);
                } else {
                    return $str;
                }
            }
        }
        $this->log->error('format String Illegal field name ' . $fldname);
        return $str;
    }

    public function formatDate($datetime, $strip_quotes = false)
    {
        $this->checkConnection();
        $db = &$this->database;
        $date = $db->DBTimeStamp($datetime);
        /* Asha: Stripping single quotes to use the date as parameter for Prepared statement */
        if ($strip_quotes == true) {
            return trim($date, "'");
        }
        return $date;
    }

    public function getDBDateString($datecolname)
    {
        $this->checkConnection();
        $db = &$this->database;
        return $db->SQLDate('Y-m-d, H:i:s', $datecolname);
    }

    public function getUniqueID($seqname)
    {
        $this->checkConnection();
        return $this->database->GenID($seqname . '_seq', 1);
    }

    public function get_tables()
    {
        $this->checkConnection();
        $metaTables = $this->database->MetaTables('TABLES');
        $result = &$metaTables;
        return $result;
    }

    //To get a function name with respect to the database type which escapes strings in given text
    public function sql_escape_string($str)
    {
        global $adb;
        if ($this->isMySql()) {
            $result_data = $adb->real_escape($str);
        } elseif ($this->isPostgres()) {
            $result_data = pg_escape_string($str);
        }
        return $result_data;
    }

    // Function to get the last insert id based on the type of database
    public function getLastInsertID($seqname = '')
    {
        if ($this->isPostgres()) {
            $result = pg_query("SELECT currval('" . $seqname . "_seq')");
            if ($result) {
                $row = pg_fetch_row($result);
                $last_insert_id = $row[0];
            }
        } else {
            $last_insert_id = $this->database->Insert_ID();
        }
        return $last_insert_id;
    }

    // Function to escape the special characters in database name based on database type.
    public function escapeDbName($dbName = '')
    {
        if ($dbName === '') {
            $dbName = $this->dbName;
        }
        if ($this->isMySql()) {
            $dbName = "`{$dbName}`";
        }
        return $dbName;
    }

    public static function tableExists($tableName): bool
    {
        global $adb;
        $res = $adb->pquery(
            "SELECT count(table_name) as cnt
                                FROM information_schema.tables
                                WHERE table_name = '$tableName'"
        );
        return $adb->query_result($res, 0, 'cnt') > 0;
    }

    public function getLastError()
    {
        return $this->database->errorMsg();
    }

    /**
     * @param  int|string|null  $dbPort
     */
    public function setDbPort($dbPort): void
    {
        $this->dbPort = $dbPort;
    }
} /* End of class */


if (!function_exists('adodb_throw')) {
    function adodb_throw($dbms, $fn, $errno, $errmsg, $p1, $p2, $thisConnection)
    {
        global $ADODB_EXCEPTION;

        if (error_reporting() == 0) {
            return;
        } // obey @ protocol
        if (is_string($ADODB_EXCEPTION)) {
            $errfn = $ADODB_EXCEPTION;
        } else {
            $errfn = 'Libraries\database\adodb\ADODB_Exception';
        }
        throw new $errfn($dbms, $fn, $errno, $errmsg, $p1, $p2, $thisConnection);
    }
}
