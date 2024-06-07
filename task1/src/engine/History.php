<?php

declare(strict_types = 1);

namespace engine;

use Libraries\database\PearDatabase;

/**
 *
 */
class History
{
    /**
     * @param  string  $entityType
     * @param  int     $entityId
     * @param          $action
     * @param          $who
     * @param          $changeData
     *
     * @return void
     */
    public static function logTrack(string $entityType, int $entityId, $action, $who, $changeData)
    {
        $adb = PearDatabase::getInstance();
        $query = "INSERT INTO `history`
                              (`entity_type`, `entity_id`, `action`, `who`, `change_data`)
                              VALUES (?, ?, ?, ?, ?)";
        $adb->pquery($query, [$entityType, $entityId, $action, $who, $changeData]);

    }

}
