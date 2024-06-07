<?php

declare(strict_types = 1);

namespace Setup;

use Libraries\database\PearDatabase;
use Throwable;

/**
 *
 */
class TableFactory
{
    private array $queries        = [];

    /**
     * @var string
     */
    private string $sqlTemplate = '';

    public function __construct()
    {
        $this->sqlTemplate = file_get_contents(ROOT_DIR . '/db_script/table_creation_script.sql');
        $this->generateQueries();
    }

    /**
     * @param  \Libraries\database\PearDatabase  $database
     *
     * @return void
     */
    public function seedWithBaseData(PearDatabase $database): void
    {
        if (is_file(ROOT_DIR . '/db_script/db_base_data_script.sql')) {
            $fileContent = file_get_contents(ROOT_DIR . '/db_script/db_base_data_script.sql');
            $sqlNormalized = str_replace("\r\n", "\n", $fileContent); // Normalize newline characters
            $sqlNormalized = explode(";", $fileContent);
            $queries = array_filter($sqlNormalized, fn($value) => !empty(trim($value)));
            // Display or process your queries
            foreach ($queries as $query) {
                $database->pquery($query, [], true);
            }
        }
    }


    /**
     * @param $prefix
     *
     * @return void
     */
    private function generateQueries()
    {
        $sqlNormalized = str_replace("\r\n", "\n", $this->sqlTemplate); // Normalize newline characters

        $this->queries = explode(";\n", $sqlNormalized);
        $this->queries = array_filter($this->queries, fn($value) => !empty(trim($value)));
    }

    /**
     * @return array
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

}
