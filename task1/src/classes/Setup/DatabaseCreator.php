<?php

declare(strict_types = 1);

namespace Setup;

use Libraries\database\PearDatabase;
use Exception;

class DatabaseCreator
{
    protected PearDatabase $database;
    protected string       $dbName;
    protected bool         $create;

    /**
     * @param  \Libraries\database\PearDatabase  $database
     * @param  string                       $dbName
     * @param  bool                         $create
     */
    public function __construct(PearDatabase $database, string $dbName, bool $create = false)
    {
        $this->database = $database;
        $this->dbName = $dbName;
        $this->create = $create;
    }

    /**
     * @return bool
     */
    public function createDatabase(): bool
    {
        if (!$this->create) {
            try {
                return $this->checkIfDbExists();
            } catch (\Throwable $exception) {
                $this->database->log->info('Failed to check if db exists', ['exception' => $exception->getMessage(), 'class' => $this]);
                return false;
            }
        }

        $query = "CREATE DATABASE IF NOT EXISTS `$this->dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
        $result = $this->database->preparedQuery($query, [], true);
        return is_object($result);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function checkIfDbExists(): bool
    {
        $dbType = $this->database->dbType;
        $exists = false;

        switch ($dbType) {
            case 'mysqli':
            case 'mysqlt':
            case 'pdo_mysql':
                $sql = 'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?';
                break;
            case 'pgsql':
            case 'pdo_pgsql':
                $sql = 'SELECT 1 FROM `pg_database` WHERE `datname` = ?';
                break;
            case 'sqlite':
            case 'sqlite3':
            case 'pdo_sqlite':
                // SQLite is a file-based database. You might want to check if the file exists instead.
                $exists = file_exists($this->database->dbName);
                break;
            case 'oci8':
            case 'pdo_oci':
                $sql = 'SELECT 1 FROM `all_databases` WHERE `name` = ?';
                break;
            // Add cases for other database types as necessary.
            default:
                throw new Exception("Unsupported database type: {$dbType}");
        }

        if (!$exists && isset($sql)) { // Skip this if already determined (e.g., SQLite)
            $result = $this->database->preparedQuery($sql, [$this->database->dbName], true);
            $exists = $this->database->num_rows($result) > 0; // If not at end of file, the database exists.
        }

        return $exists;
    }

}
