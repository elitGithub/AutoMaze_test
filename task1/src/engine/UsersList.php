<?php

declare(strict_types = 1);

namespace engine;

use Libraries\database\PearDatabase;
use Permissions\PermissionsManager;
use Permissions\Role;

class UsersList
{
    protected PearDatabase $adb;
    /**
     * @var User[]
     */
    protected array $usersCollection = [];
    /**
     * @var mixed
     */
    protected $tables;

    public function __construct()
    {
        $this->adb = PearDatabase::getInstance();
    }

    /**
     * @param  \engine\User  $user
     *
     * @return array|\engine\User[]
     * @throws \Exception
     */
    public function loadUserList(User $user): array
    {
        $query = "SELECT
                      `users`.*,
                      `roles`.`role_name` AS role_name,
                      `roles`.`role_id`,
                      IFNULL(CONCAT(`creators`.`first_name`, ' ', `creators`.`last_name`), 'System') AS `creator`
                  FROM `users`
                  JOIN `user_to_role` AS `user2Role` ON `user2Role`.user_id = `users`.`user_id`
                  JOIN `roles` ON `roles`.`role_id` = `user2Role`.`role_id`
                LEFT JOIN  `users` AS `creators` ON `users`.`created_by` = `creators`.`user_id`
                          WHERE `users`.`deleted_at` IS NULL ";
        $where = '';
        $params = [];
        if (!PermissionsManager::isAdmin($user)) {
            $roles = $roleSubordinates = Role::getChildRoles($user);
            $roleIds = array_column($roles, 'role_id');
            $where = ' AND `roles`.`role_id` IN (' . generateQuestionMarks($roleIds) . ') AND `users`.`is_admin` != "On" ';
            $params = $roleIds;
        }

        $query .= $where . ' GROUP BY  users.user_id, roles.role_name, roles.role_id, creators.first_name, creators.last_name ORDER BY users.user_id DESC ';
        $result = $this->adb->pquery($query, $params);
        if (!$result || $this->adb->num_rows($result) === 0) {
            return [];
        }
        while ($row = $this->adb->fetchByAssoc($result)) {
            $user = new User();
            $user->initFromRow($row);
            $this->usersCollection[] = $user;
        }

        return $this->usersCollection;
    }
}
