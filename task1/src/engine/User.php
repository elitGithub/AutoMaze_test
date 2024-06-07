<?php

declare(strict_types = 1);

namespace engine;

use Libraries\database\PearDatabase;
use Permissions\CacheSystemManager;
use Permissions\PermissionsManager;
use Permissions\Role;
use Session\Session;
use Throwable;

/**
 *
 */
class User
{
    public ?int      $id          = null;
    public Session   $session;
    protected string $entityTable = 'users';
    protected string $roleTable   = 'user_to_role';
    public bool $is_applicant = false;
    public bool $is_company = false;
    public string $is_admin = 'No';
    /**
     * @var \Libraries\database\PearDatabase
     */
    protected ?PearDatabase $adb         = null;
    protected array         $permissions = [];

    /**
     * @param  int  $id
     */
    public function __construct(int $id = 0)
    {
        if ($id) {
            $this->id = $id;
        }

        $this->session = new Session();
    }

    /**
     * @return \Libraries\database\PearDatabase|null
     */
    public function database(): ?PearDatabase
    {
        if (is_null($this->adb)) {
            $this->adb = PearDatabase::getInstance();
        }

        return $this->adb;
    }

    /**
     * @param $name
     * @param $value
     *
     * @return void
     */
    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * @param $row
     *
     * @return void
     */
    public function initFromRow($row)
    {
        $this->id = (int) $row['user_id'];
        foreach ($row as $key => $userInfo) {
            $this->$key = $userInfo;
        }
    }

    /**
     * @param $userName
     * @param $password
     *
     * @return bool
     * @throws \Throwable
     */
    public function login($userName, $password): bool
    {
        global $default_language, $application_language;
        $query = "SELECT * FROM `$this->entityTable` WHERE CAST(`user_name` AS BINARY) = ?";
        $result = $this->database()->requirePsSingleResult($query, [$userName]);

        if (!$result) {
            return false;
        }

        $row = $this->database()->fetchByAssoc($result);
        if ((bool) $row['is_active'] !== true) {
            return false;
        }

        if (!password_verify($password, $row['password'])) {
            return false;
        }

        $this->initFromRow($row);

        $this->roleid = Role::getRoleByUserId($this->id);
        CacheSystemManager::refreshUserInCache($this);
        PermissionsManager::refreshPermissionsInCache();
        $this->retrieveUserInfoFromFile();

        $this->session->addValue('authenticated_user_language', $default_language);
        if (!empty($application_language) && $application_language !== $default_language) {
            $this->session->addValue('authenticated_user_language', $application_language);
        }
        $this->session->addValue('authenticated_user_id', $this->id);
        $this->session->addValue('username', $userName);
        $this->session->addValue('authenticated_user_name', $userName);
        $this->session->addValue('loggedin', true);
        $this->session->addValue('last_login', date('Y-m-d H:i:s'));
        $this->session->addValue('ua', $_SERVER['HTTP_USER_AGENT']);
        $this->session->addValue('is_logged_in', true);

        $this->refreshUserInSession();

        $this->updateLastLogin();
        return true;
    }

    /**
     * @return void
     */
    public function refreshUserInSession()
    {
        $this->session->addValue('authenticated_user_id', $this->id);
        $this->session->addValue('authenticated_user_data', [
            'userName'   => $this->user_name,
            'user_id'    => $this->id,
            'name'       => $this->first_name . ' ' . $this->last_name,
            'email'      => $this->email,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'active'     => $this->is_active,
            'role'       => $this->roleid,
            'is_admin'   => $this->is_admin ?? 'Off',
        ]);
        $this->session->addValue('username', $this->user_name);
        $this->session->addValue('password', $this->password);
    }

    /**
     * @param  int  $userId
     *
     * @return bool
     */
    public function isLastAdminUser(int $userId): bool
    {
        $query = "SELECT * FROM `$this->entityTable` WHERE `user_id` != ? AND `is_admin` = 'On' AND `is_active` = 1";
        $result = $this->database()->pquery($query, [$userId]);
        if (!$result || !$this->database()->num_rows($result)) {
            return true;
        }

        return false;
    }

    public function isLoggedIn(): bool
    {
        return $this->session->hasKey('loggedin') &&
               $this->session->readKeyValue('loggedin') &&
               $this->session->hasKey('ua') &&
               $this->session->readKeyValue('ua') === $_SERVER['HTTP_USER_AGENT'];
    }



    public function retrieveUserInfoFromFile($ajax = false): ?User
    {
        if (!$this->id && $this->session->hasKey('authenticated_user_id')) {
            $this->id = $this->session->readKeyValue('authenticated_user_id');
        }
        try {
            $userData = PermissionsManager::getUserPrivileges($this->id);
            foreach ($userData['user_data'] as $propertyName => $propertyValue) {
                $this->$propertyName = $propertyValue;
                $this->session->addValue($propertyName, $propertyValue);
            }
            foreach ($userData['permissions'] as $actionId => $isEnabled) {
                $this->permissions[$actionId] = $isEnabled;
            }
            return $this;
        } catch (Throwable $exception) {
            return null;
        }
    }

    public static function primaryKey(): string
    {
        return 'id';
    }

    public function getDisplayName(): string
    {
        return 'Users';
    }


    public function labels(): array
    {
        return [
            'first_name'       => 'First Name',
            'last_name'        => 'Last Name',
            'email'            => 'Email',
            'password'         => 'Password',
            'confirm_password' => 'Confirm Password',
        ];
    }

    /**
     * @param  string  $password
     *
     * @return string|null
     */
    public function encryptPassword(string $password): ?string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * @param  int|null  $id
     *
     * @return \engine\User|null
     * @throws \Exception
     */
    public static function getUserById(?int $id = null): ?User
    {
        if (is_null($id)) {
            return null;
        }

        $instance = new self($id);
        $query = "SELECT * FROM `$instance->entityTable` WHERE user_id = ?";
        $result = $instance->database()->pquery($query, [$id]);
        if (!$result || $instance->database()->num_rows($result) === 0) {
            return null;
        }

        $row = $instance->database()->fetchByAssoc($result);
        $row['roleid'] = Role::getRoleByUserId($id);
        $instance->initFromRow($row);
        return $instance;
    }

    /**
     * @param  string  $password
     * @param  string  $confirmPassword
     *
     * @return bool
     */
    public function changePassword(string $password, string $confirmPassword): bool
    {
        if ($password !== $confirmPassword) {
            $_SESSION['errors'][] = 'Please make sure you typed password and confirm password';
            return false;
        }
        $query = "UPDATE `$this->entityTable` SET `password` = ? WHERE `user_id` = ?";
        $result = $this->database()->pquery($query, [$this->encryptPassword($password), $this->id]);
        if (!$result) {
            return false;
        }

        return $this->database()->getAffectedRowCount($result) > 0;
    }


    /**
     * @param  int  $userId
     *
     * @return array - Data related to last_login
     */
    public static function getLastLoginData(int $userId): array
    {
        $adb = PearDatabase::getInstance();
        if (!$userId || !is_numeric($userId)) {
            return [];
        }
        $sql = "SELECT `last_login`, `user_name`, `email` FROM `users` WHERE `user_id` = ?";
        $res = $adb->pquery($sql, [$userId]);
        return $adb->num_rows($res) ? $adb->fetchByAssoc($res) : [];
    }

    /**
     * @return void
     */
    private function updateLastLogin()
    {
        $this->database()->pquery("UPDATE `{$this->entityTable}` SET `last_login` = CONVERT_TZ(NOW(), 'SYSTEM','+00:00')  WHERE `user_id` = ?;",
                                  [$this->id]);
    }


}
