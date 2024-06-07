<?php

declare(strict_types = 1);

namespace Permissions;

use Libraries\database\PearDatabase;
use engine\User;
use Exception;
use Memcached;
use Redis;

global $permissionsConfig, $redisConfig, $memcachedConfig;
require_once 'system/user/permissions.php';

/**
 * Wrapper around the cache system
 */
class CacheSystemManager
{
    protected static ?Memcached $memcached = null;
    protected static ?Redis     $redis     = null;
    protected const CACHE_WRITE_PREFIX  = 'job_portal_permissions_data';
    protected const PERMISSION_HASH_KEY = '_permissions';

    /**
     * @param $userId
     * @param $data
     *
     * @return void
     * @throws \Throwable
     */
    public static function writeUser($userId, $data)
    {
        global $permissionsConfig;
        $key = self::CACHE_WRITE_PREFIX . '_' . $permissionsConfig['writing_key'] . '_' . $userId;
        self::hashWrite($key, (string) $userId, $data);
    }

    /**
     * @param            $key
     * @param            $hashKey
     * @param            $data
     * @param  int|null  $expiration
     *
     * @return void
     * @throws \Throwable
     */
    private static function hashWrite($key, $hashKey, $data, ?int $expiration = null): void
    {
        global $permissionsConfig;
        switch ($permissionsConfig['backend']) {
            case 'redis':
                $redis = self::getRedisConnection();
                $redis->hSet($key, $hashKey, serialize($data));
                if ($expiration !== null) {
                    $redis->expire($key, $expiration);
                }
                return;

            case 'memcached':
                $memcached = self::getMemcachedConnection();
                $memcached->set($key, serialize($data), $expiration ?? 0);
                return;

            case 'apcu':
                if (!self::isAPCUEnabled()) {
                    throw new Exception('APCu is not enabled or available.');
                }
                apcu_store($key, serialize($data), $expiration ?? 0);
                return;

            case 'default':
                self::writeFile($key, $data, $expiration);
                return;
            default:
                throw new Exception('Unsupported backend specified.');
        }
    }

    /**
     * @param         $key
     * @param  array  $data
     * @param  null   $expiration
     *
     * @return void
     */
    private static function writeFile($key, array $data, $expiration = null)
    {
        $fileName = ROOT_DIR . '/system/data/' . $key . '.txt';
        $res = file_put_contents($fileName, serialize($data));
        if (is_int($expiration)) {
            $expiryFile = ROOT_DIR . '/system/data/' . 'expirations.php';

            $ttl = time() + $expiration;
            $data[$key] = $ttl;
            $data['expiryFileName'] = $fileName;
            if (is_file($expiryFile)) {
                $data = unserialize(file_get_contents($expiryFile));
                $data['expiryFileName'] = $fileName;
                $data[$key] = $ttl;
            }

            file_put_contents($expiryFile, serialize($data), FILE_APPEND);
        }
    }


    /**
     * Write data to the configured backend.
     *
     * @param            $key
     * @param  mixed     $data  The data to write.
     * @param  int|null  $expiration
     *
     * @return bool
     * @throws \Throwable
     */
    public static function write($key, $data, ?int $expiration = null): bool
    {
        global $permissionsConfig;

        switch ($permissionsConfig['backend']) {
            case 'redis':
                $redis = self::getRedisConnection();
                if ($expiration !== null) {
                    return $redis->set($key, serialize($data), ['ex' => $expiration]);
                } else {
                    return $redis->set($key, serialize($data));
                }

            case 'memcached':
                $memcached = self::getMemcachedConnection();
                if ($expiration !== null) {
                    // Memcached treats expiration values greater than 30 days as a Unix timestamp of an absolute expiration time
                    $expirationTime = $expiration > 2592000 ? $expiration : time() + $expiration;
                    return $memcached->set($key, $data, $expirationTime);
                } else {
                    return $memcached->set($key, $data);
                }

            case 'apcu':
                if (!self::isAPCUEnabled()) {
                    throw new Exception('APCu is not enabled or available.');
                }
                if ($expiration !== null) {
                    return apcu_store($key, $data, $expiration);
                } else {
                    return apcu_store($key, $data);
                }

            case 'default':
                self::writeFile($key, $data, $expiration);
                return true;

            default:
                throw new Exception('Unsupported backend specified.');
        }
    }


    /**
     * @param          $key
     * @param  string  $hashKey
     *
     * @return false|mixed|null
     * @throws \Throwable
     */
    private static function hashRead($key, string $hashKey)
    {
        global $permissionsConfig;
        switch ($permissionsConfig['backend']) {
            case 'redis':
                $redis = self::getRedisConnection();
                $data = $redis->hGet($key, $hashKey);
                return $data !== false ? unserialize($data) : null;
            case 'memcached':
                $memcached = self::getMemcachedConnection();
                return $memcached->get($key) ?: null;

            case 'apcu':
                if (!self::isAPCUEnabled()) {
                    throw new Exception('APCu is not enabled or available.');
                }
                $success = false;
                $data = apcu_fetch($key, $success);
                return $success ? unserialize($data) : null;
            case 'default':
                $data = file_get_contents(ROOT_DIR . '/system/data/' . $key . '.txt');
                if ($data !== false) {
                    return unserialize($data);
                }
                return false;
            default:
                throw new Exception('Unsupported backend specified.');
        }
    }

    /**
     * @param $key
     *
     * @return false|mixed|null
     * @throws \Throwable
     */
    private function read($key)
    {
        global $permissionsConfig;
        switch ($permissionsConfig['backend']) {
            case 'redis':
                $redis = self::getRedisConnection();
                $data = $redis->get($key);
                return $data !== false ? unserialize($data) : null;
            case 'memcached':
                $memcached = self::getMemcachedConnection();
                return $memcached->get($key) ?: null;

            case 'apcu':
                if (!self::isAPCUEnabled()) {
                    throw new Exception('APCu is not enabled or available.');
                }
                $success = false;
                $data = apcu_fetch($key, $success);
                return $success ? $data : null;
            case 'default':
                $data = file_get_contents(ROOT_DIR . '/system/data/' . $key . '.php');
                if ($data !== false) {
                    return unserialize($data);
                }
                return false;
            default:
                throw new Exception('Unsupported backend specified.');
        }
    }

    /**
     * Read data from the configured backend.
     *
     * @param  mixed  $userId  The user ID to use as part of the key.
     *
     * @return mixed The data read from the storage, or null if not found.
     * @throws \Throwable
     */
    public static function readUser($userId)
    {
        global $permissionsConfig;

        return self::hashRead(self::CACHE_WRITE_PREFIX . '_' . $permissionsConfig['writing_key'] . '_' . $userId, (string) $userId);
    }

    /**
     * @param  \engine\User  $user
     *
     * @return void
     * @throws \Throwable
     */
    public static function refreshUserInCache(User $user)
    {
        self::writeUser($user->id, [
            'userName'   => $user->user_name,
            'user_id'    => $user->id,
            'name'       => $user->first_name . ' ' . $user->last_name,
            'email'      => $user->email,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'is_active'  => $user->is_active,
            'role'       => $user->roleid,
            'last_login' => $user->last_login,
            'is_admin'   => $user->is_admin ?? 'Off',
        ]);
        $adb = PearDatabase::getInstance();
        $rolePermRes = $adb->query("SELECT * FROM `role_permissions`;");
        $rolePermissionsArray = [];
        while ($row = $adb->fetchByAssoc($rolePermRes)) {
            $rolePermissionsArray[] = $row;
        }
        self::refreshPermissionsInCache($rolePermissionsArray);
    }

    /**
     * @param  \Libraries\database\PearDatabase  $adb
     * @param  string                       $rolePermissionsTable
     *
     * @return void
     * @throws \Throwable
     */
    public static function createPermissionsFile(PearDatabase $adb)
    {
        $rolePermRes = $adb->query("SELECT * FROM `role_permissions`;");
        $rolePermissionsArray = [];
        while ($row = $adb->fetchByAssoc($rolePermRes)) {
            $rolePermissionsArray[] = $row;
        }
        self::refreshPermissionsInCache($rolePermissionsArray);
        $result = file_put_contents(ROOT_DIR . '/system/user/default_permissions.php',
                                    '<?php $rolePermissionsArray=' . var_export($rolePermissionsArray, true) . ';');
        if ($result === false) {
            throw new Exception('Unable to write permissions file.');
        }
    }

    /**
     * @param  array  $rolePermissionsArray
     *
     * @return void
     * @throws \Throwable
     */
    public static function refreshPermissionsInCache(array $rolePermissionsArray)
    {
        global $permissionsConfig;
        $key = self::CACHE_WRITE_PREFIX . '_' . $permissionsConfig['writing_key'] . self::PERMISSION_HASH_KEY;
        self::hashWrite($key, self::PERMISSION_HASH_KEY, $rolePermissionsArray);
    }

    /**
     * @return false|mixed|null
     * @throws \Throwable
     */
    public static function readPermissions()
    {
        global $permissionsConfig;
        return self::hashRead(self::CACHE_WRITE_PREFIX . '_' . $permissionsConfig['writing_key'] . self::PERMISSION_HASH_KEY,
                              self::PERMISSION_HASH_KEY);
    }

    /**
     * @return \Redis
     * @throws \RedisException
     */
    private static function getRedisConnection(): Redis
    {
        global $redisConfig;

        if (null === self::$redis) {
            self::$redis = new Redis();
            self::$redis->connect($redisConfig['host'], $redisConfig['port']);
            if (!empty($redisConfig['auth'])) {
                self::$redis->auth($redisConfig['auth']);
            }
        }

        return self::$redis;
    }


    /**
     * @return bool
     */
    private static function isAPCUEnabled(): bool
    {
        return extension_loaded('apcu');
    }

    /**
     * Attempts to get a Memcached connection with server availability check.
     * Utilizes Memcached's built-in health check mechanisms to avoid cache pollution.
     *
     * @return Memcached
     * @throws Exception If unable to establish a Memcached connection after several attempts.
     */
    private static function getMemcachedConnection(): Memcached
    {
        global $memcachedConfig;

        if (null === self::$memcached) {
            self::$memcached = new Memcached($memcachedConfig['persist_name']);
            self::$memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);

            // Check if the server list is already populated to avoid re-adding servers.
            if (!count(self::$memcached->getServerList())) {
                self::$memcached->addServer($memcachedConfig['host'], $memcachedConfig['port']);
            }

            // Verify connection health without introducing dummy values.
            if (!self::verifyMemcachedConnection()) {
                throw new Exception('Unable to establish a Memcached connection.');
            }
        }

        return self::$memcached;
    }

    /**
     * Verifies the Memcached connection health without polluting the cache.
     * Uses Memcached's getStats method to check server responsiveness.
     *
     * @return bool True if the server is responsive and connection is deemed healthy, false otherwise.
     */
    private static function verifyMemcachedConnection(): bool
    {
        $stats = self::$memcached->getStats();
        return !empty($stats) && array_reduce($stats, function ($carry, $server) {
                return $carry && $server['pid'] > 0;
            },                                true);
    }


}
