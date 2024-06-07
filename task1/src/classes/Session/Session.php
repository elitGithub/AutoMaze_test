<?php

declare(strict_types = 1);

namespace Session;

/**
 * In case we want to use a different session manager like Redis or Memcached and so on.
 */
class Session
{
    protected const FLASH_KEY = 'flash_messages';

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
        $flashMessages = $_SESSION[static::FLASH_KEY] ?? [];
        foreach ($flashMessages as $key => &$flashMessage) {
            $flashMessage['remove'] = true;
        }

        $_SESSION[static::FLASH_KEY] = $flashMessages;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return void
     */
    public function addValue($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function pushToErrors($value)
    {
        $_SESSION['errors'][] = $value;
    }

    /**
     * @param $key
     *
     * @return mixed|null
     */
    public function readKeyValue($key)
    {
        return $_SESSION[$key] ?? null;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function hasKey($key): bool
    {
        return isset($_SESSION[$key]) || array_key_exists($key, $_SESSION);
    }

    public function setFlash($key, $message)
    {
        $_SESSION[static::FLASH_KEY][$key] = [
            'value' => $message,
            'remove' => false,
        ];
    }

    public function __set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function __get($key)
    {
        return $_SESSION[$key] ?? false;
    }

    public function destroyUserSession()
    {
        $keys = [
            'authenticated_user_language',
            'password',
            'authenticated_user_id',
            'username',
            'authenticated_user_name',
            'loggedin',
            'last_login',
            'is_logged_in',
            'authenticated_user_data',
        ];
        foreach ($keys as $key) {
            if ($this->hasKey($key)) {
                $this->remove($key);
            }
        }
    }


    public function remove($key)
    {
        unset($_SESSION[$key]);
    }

    public function getFlash($key)
    {
        return $_SESSION[static::FLASH_KEY][$key]['value'] ?? false;
    }

    public function __destruct()
    {
        $flashMessages = $_SESSION[static::FLASH_KEY] ?? [];
        foreach ($flashMessages as $key => &$flashMessage) {
            if ($flashMessage['remove'] === true) {
                unset($flashMessages[$key]);
            }
        }

        $_SESSION[static::FLASH_KEY] = $flashMessages;
    }


}
