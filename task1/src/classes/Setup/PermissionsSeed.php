<?php

declare(strict_types = 1);

namespace Setup;

use Libraries\database\PearDatabase;

/**
 * Base permissions
 */
class PermissionsSeed
{
    protected static array $hierarchyTree = [
        'Administrator' => ['Manager', 'Supervisor', 'User'],
        'Manager'       => ['Supervisor', 'User'],
        'Supervisor'    => ['User'],
        'User'          => [],
    ];

    /**
     * @param  \Libraries\database\PearDatabase  $adb
     *
     * @return void
     */
    public static function populateActionsTable(PearDatabase $adb)
    {
        global $actions;
        require_once ROOT_DIR . '/db_script/basePermissions.php';
        $key = 1;
        foreach ($actions as $mainRight) {
            $adb->pquery("INSERT INTO `actions` (`action_label`, `action_key`, `action`) VALUES (?, ?, ?);",
                         [$mainRight['description'], $key, $mainRight['name']]);
            ++$key;
        }
    }

    /**
     * @param  \Libraries\database\PearDatabase  $adb
     *
     * @return void
     */
    public static function populateRolesTable(PearDatabase $adb)
    {
        $roleIds = [];
        $rolePaths = [];

        foreach (static::$hierarchyTree as $role => $children) {
            // Insert the parent role if it hasn't been inserted yet and get its id and path
            if (!array_key_exists($role, $roleIds)) {
                $adb->pquery("INSERT INTO `roles` (`role_name`, `parent_id`, `path`) VALUES (?, NULL, '') ON DUPLICATE KEY UPDATE `role_name` = ?;",
                             [$role, $role]);
                $roleIds[$role] = $adb->getLastInsertID();
                // After inserting, update the path with the new role_id
                $rolePaths[$role] = $roleIds[$role] . '::';
                $adb->pquery("UPDATE `roles` SET `path` = ? WHERE `role_id` = ?;", [$rolePaths[$role], $roleIds[$role]]);
            }

            // Insert children roles
            foreach ($children as $child) {
                if (!array_key_exists($child, $roleIds)) {
                    $adb->pquery("INSERT INTO `roles` (`role_name`, `parent_id`, `path`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `role_name` = ?, `parent_id` = ?, `path` = ?;",
                                 [$child, $roleIds[$role], $rolePaths[$role], $child, $roleIds[$role], $rolePaths[$role]]);
                    $roleIds[$child] = $adb->getLastInsertID();
                    // Update path for the newly inserted child
                    $rolePaths[$child] = $rolePaths[$role] . $roleIds[$child] . '::';
                    $adb->pquery("UPDATE `roles` SET `path` = ? WHERE `role_id` = ?;", [$rolePaths[$child], $roleIds[$child]]);
                } else {
                    // Update the child's parent_id and path if it was inserted before as a parent
                    $newPath = $rolePaths[$role] . $roleIds[$child] . '::';
                    $adb->pquery("UPDATE `roles` SET `parent_id` = ?, `path` = ? WHERE `role_id` = ?;",
                                 [$roleIds[$role], $newPath, $roleIds[$child]]);
                    $rolePaths[$child] = $newPath;
                }
            }
        }
    }

    /**
     * @param  \Libraries\database\PearDatabase  $adb
     *
     * @return void
     * @throws \Exception
     */
    public static function createRolePermissions(PearDatabase $adb)
    {
        global $actions;
        require_once ROOT_DIR . '/db_script/basePermissions.php';
        $getRoleIdQuery = "SELECT `role_id` FROM `roles` WHERE `role_name` = ?";
        $getActionIdQuery = "SELECT `action_id` FROM `actions` WHERE `action` = ?";
        $insertQuery = "INSERT INTO `role_permissions` (`role_id`, `action_id`, `is_enabled`) VALUES (?, ?, ?)";
        foreach (static::$hierarchyTree as $role => $children) {
            $getRoleIsResult = $adb->pquery($getRoleIdQuery, [$role]);
            $roleId = $adb->query_result($getRoleIsResult, 0, 'role_id');
            foreach ($actions as $action) {
                $getActionIdResult = $adb->pquery($getActionIdQuery, [$action['name']]);
                $actionId = $adb->query_result($getActionIdResult, 0, 'action_id');
                $isEnabled = (int) ((int) $roleId !== 4);
                $adb->preparedQuery($insertQuery, [$roleId, $actionId, $isEnabled]);
            }
        }
    }

}
