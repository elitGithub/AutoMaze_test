<?php

declare(strict_types = 1);

namespace JobPortal\Modules\user;

use Core\Storm;
use Core\Model;
use Libraries\database\PearDatabase;
use engine\User;
use Interfaces\UniqueRecord;
use Permissions\CacheSystemManager;
use Permissions\PermissionsManager;

class UserModel extends Model implements UniqueRecord
{
    protected string        $tableName       = 'users';
    protected string        $userToRoleTable = 'user_to_role';
    protected ?PearDatabase $adb             = null;


    public function __construct()
    {
        $this->adb = PearDatabase::getInstance();
    }

    /**
     * @param  string  $email
     * @param  string  $userName
     * @param  string  $password
     * @param  string  $firstName
     * @param  string  $lastName
     * @param  int     $roleId
     * @param  string  $isAdmin
     *
     * @return bool|int
     * @throws \Throwable
     */
    public function create(string $email, string $userName, string $password, string $firstName, string $lastName, int $roleId, string $isAdmin)
    {
        if ($this->checkUniqueEmail($email) && $this->checkUniqueUserName($userName)) {
            $user = new User();
            $query = "INSERT INTO `$this->tableName` (
                     `first_name`,
                     `last_name`,
                     `email`,
                     `user_name`,
                     `password`,
                     `is_admin`,
                     `is_active`)
                                 VALUES (?, ?, ?, ?, ?, ?, 1);";
            $this->adb->pquery($query, [$firstName, $lastName, $email, $userName, $user->encryptPassword($password), ucfirst($isAdmin)]);
            $id = $this->adb->getLastInsertID();
            $this->adb->pquery("INSERT INTO `$this->userToRoleTable` (`user_id`, `role_id`) VALUES (?, ?)", [$id, $roleId]);
            if ($id) {
                CacheSystemManager::writeUser(
                    $id,
                    [
                        'userName'   => $userName,
                        'user_id'    => $id,
                        'name'       => $firstName . ' ' . $lastName,
                        'email'      => $email,
                        'first_name' => $firstName,
                        'last_name'  => $lastName,
                        'is_active'  => 1,
                        'role'       => $roleId,
                        'is_admin'   => $isAdmin,
                    ]
                );
            }
            return $id;
        }

        Storm::getStorm()->session->pushToErrors('User already exists.');
        return false;
    }


    public function findOne(string $column, $value)
    {
        $query = "SELECT * FROM `$this->tableName` WHERE `$column` = ?;";
        $result = Storm::getStorm()->db->pquery($query, [$value]);
        if ($result && Storm::getStorm()->db->num_rows($result) > 0) {
            return Storm::getStorm()->db->fetchByAssoc($result);
        }

        return false;
    }

    /**
     * @param  string  $email
     * @param  string  $userName
     *
     * @return array|null
     */
    public function getByEmailAndUserName(string $email, string $userName): ?array
    {
        $query = "SELECT *
                     FROM `$this->tableName`
                     WHERE `email` = ? AND `user_name` = ? AND `is_active` = 1;";
        $res = $this->adb->preparedQuery($query, [$email, $userName]);
        return $this->adb->fetchByAssoc($res);
    }

    /**
     * @param  string  $email
     *
     * @return bool
     * @throws \Exception
     */
    public function checkUniqueEmail(string $email): bool
    {
        $result = $this->adb->pquery(
            "SELECT COUNT(*) AS `total` FROM `$this->tableName` WHERE `email` = CAST(? AS BINARY) AND `deleted_at` IS NULL;",
            [$email]
        );
        return ($this->adb->query_result($result, 0, 'total') < 1);
    }

    /**
     * @param  string  $userName
     *
     * @return bool
     * @throws \Exception
     */
    public function checkUniqueUserName(string $userName): bool
    {
        $result = $this->adb->pquery(
            "SELECT COUNT(*) AS `total` FROM `$this->tableName` WHERE `user_name` = CAST(? AS BINARY) AND `deleted_at` IS NULL;",
            [$userName]
        );
        return ($this->adb->query_result($result, 0, 'total') < 1);
    }

    /**
     * @param  int  $userId
     *
     * @return bool
     */
    public function deleteUser(int $userId): bool
    {
        $query = "UPDATE
                      `$this->tableName`
                    SET
                        `is_active` = '0',
                        `deleted_at` = CURRENT_TIMESTAMP(),
                        `email` = CONCAT(`email`, '_', `user_id`, 'DELETED_USER'),
                        `user_name` = CONCAT(`user_name`, '_', `user_id`, 'DELETED_USER')
                    WHERE `user_id` = ?;";
        $result = $this->adb->preparedQuery($query, [$userId]);
        if ($result && $this->adb->getAffectedRowCount($result)) {
            $this->adb->pquery("DELETE FROM `$this->userToRoleTable` WHERE `user_id` = ?", [$userId]);
            return true;
        }

        return false;
    }

    /**
     * @param  \engine\User  $user
     * @param  int           $roleId
     * @param  string        $email
     * @param  string        $firstName
     * @param  string        $lastName
     * @param                $active
     * @param  string        $isAdmin
     *
     * @return bool
     * @throws \Throwable
     */
    public function updateUser(User $user, int $roleId, string $email, string $firstName, string $lastName, $active, string $isAdmin): bool
    {
        $query = "UPDATE
                      `$this->tableName`
                  SET
                      `email` = ?,
                      `first_name` = ?,
                      `last_name` = ?,
                      `is_active` = ?,
                      `is_admin` = ?,
                      `last_update_at` = CURRENT_TIMESTAMP()
                  WHERE `user_id` = ?; ";

        $result = $this->adb->pquery($query, [$email, $firstName, $lastName, $active, $isAdmin, $user->id]);
        $userChanges = $this->adb->getAffectedRowCount($result);
        if (!$result) {
            return false;
        }

        $roleQuery = "UPDATE `$this->userToRoleTable` SET `role_id` = ? WHERE `user_id` = ?;";
        $result = $this->adb->pquery($roleQuery, [$roleId, $user->id]);
        if (!$result) {
            return false;
        }


        CacheSystemManager::refreshUserInCache($user);
        PermissionsManager::refreshPermissionsInCache();
        return $userChanges > 0;
    }

    public function rules(): array
    {
        return $this->rules;
    }

    public function getDisplayName(): string
    {
        return 'Users';
    }

    public function uniqueRecordExists($uniqueAttributeName, $uniqueAttributeValue): bool
    {
        return false;
    }

    public function params(): array
    {
        return [];
    }
}
