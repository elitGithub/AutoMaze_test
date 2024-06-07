<?php

declare(strict_types = 1);

namespace Core;

use Libraries\database\PearDatabase;
use Exception;

/**
 * System Handler class
 */
class System
{
    /**
     * Major version.
     */
    private const VERSION_MAJOR = 1;

    /**
     * Minor version.
     */
    private const VERSION_MINOR = 0;

    /**
     * Patch level.
     */
    private const VERSION_PATCH_LEVEL = 0;

    /**
     * Pre-release version.
     */
    private const VERSION_PRE_RELEASE = '';

    /**
     * API version.
     */
    private const VERSION_API = '1.0';

    /**
     * Minimum required PHP version.
     */
    public const VERSION_MINIMUM_PHP = '7.4.0';

    /**
     * Array of required PHP extensions.
     *
     * @var array<string>
     */
    private array $requiredExtensions = [
        'curl',
        'fileinfo',
        'filter',
        'gd',
        'json',
        'sodium',
        'xml',
        'openssl',
        'mbstring',
        'zip',
        'ctype',
        'filter',
        'hash',
    ];
    /**
     * "ext-ctype": "*",
     * "ext-filter": "*",
     * "ext-hash": "*"
     */


    /**
     * Supported databases for the system, matching PHP extension names, and ADOdb support.
     *
     * @var array<string, array<int, string>>
     */
    private array $supportedDatabases = [
        'mysqli'  => [
            self::VERSION_MINIMUM_PHP,
            'PHP mysqli extension for MySQL v5.7/ MariaDB v10 / Percona Server v8 / Galera Cluster v4 for MySQL.',
        ],
        'pgsql'   => [
            self::VERSION_MINIMUM_PHP,
            'PHP extension for PostgreSQL v10 or later.',
        ],
        'sqlite3' => [
            self::VERSION_MINIMUM_PHP,
            'PHP extension for SQLite 3.',
        ],
        'sqlsrv'  => [
            self::VERSION_MINIMUM_PHP,
            'PHP extension for Microsoft SQL Server 2016 or later.',
        ],
        'oci8'    => [
            self::VERSION_MINIMUM_PHP,
            'PHP extension for Oracle Database v21c or later.',
        ],
        'ibm_db2' => [
            self::VERSION_MINIMUM_PHP,
            'PHP extension for IBM DB2 v7.1 or later.',
        ],
    ];

    private array $supportedPermissionEngines = [
        'default'   => [
            self::VERSION_MINIMUM_PHP,
            'Use the system default file system',
        ],
        'redis'     => [
            self::VERSION_MINIMUM_PHP,
            'PHP Redis extension for working with Redis, a fast, in-memory data store. Excellent for caching user permissions for quick access.',
        ],
        'memcached' => [
            self::VERSION_MINIMUM_PHP,
            'PHP memcached extension for interfacing with Memcached, an in-memory key-value store. Good for caching frequently accessed data like user permissions.',
        ],
        'apcu'      => [
            self::VERSION_MINIMUM_PHP,
            'PHP APCu extension provides user cache for variables stored in memory. Suitable for caching small datasets like user permissions without distributed caching.',
        ],
    ];

    private UniqueIdsGenerator $uniqueIdsGenerator;
    /**
     * Configuration array.
     */
    protected array $mainConfig = [];
    /**
     * Array of missing PHP extensions.
     *
     * @var array<string>
     */
    private array $missingExtensions = [];

    public function __construct()
    {
        $this->uniqueIdsGenerator = new UniqueIdsGenerator();
        $this->mainConfig = [
            'currentVersion'    => System::getVersion(),
            'appKey'            => $this->getRandomString(36),
            'enableCaptchaCode' => (extension_loaded('gd') ? 'true' : 'false'),
        ];
    }


    /**
     * Returns the current version of phpMyFAQ for installation and
     * version in the database.
     * Releases will be numbered with the follow format:
     * <major>.<minor>.<patch>[-<prerelease>]
     */
    public static function getVersion(): string
    {
        $version = sprintf('%d.%d.%d', self::VERSION_MAJOR, self::VERSION_MINOR, self::VERSION_PATCH_LEVEL);
        return $version . (self::isDevelopmentVersion() ? '-' . self::VERSION_PRE_RELEASE : '');
    }

    /**
     * @return array
     */
    public function getMainConfig(): array
    {
        return $this->mainConfig;
    }


    /**
     * @param  int  $length
     *
     * @return false|string
     */
    public function getRandomString(int $length = 13)
    {
        return $this->uniqueIdsGenerator->generateTrueRandomString($length);
    }


    /**
     * @return bool
     */
    public static function isDevelopmentVersion(): bool
    {
        return strlen(self::VERSION_PRE_RELEASE) > 0;
    }

    /**
     * @return bool
     */
    public function checkDatabase(): bool
    {
        foreach (array_keys($this->supportedDatabases) as $extension) {
            if (extension_loaded($extension)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function checkRequiredExtensions(): bool
    {
        foreach ($this->requiredExtensions as $requiredExtension) {
            if (!extension_loaded($requiredExtension)) {
                $this->missingExtensions[] = $requiredExtension;
            }
        }
        return count($this->missingExtensions) <= 0;
    }

    /**
     * Returns all missing extensions.
     *
     * @return array<string>
     */
    public function getMissingExtensions(): array
    {
        return $this->missingExtensions;
    }

    /**
     * Checks for an installed phpMyFAQ version
     */
    public function checkInstallation(): bool
    {
        return !is_file(ROOT_DIR . '/system/system_settings.php');
    }

    /**
     * Returns the locally supported databases.
     *
     * @param  bool  $returnAsHtml
     *
     * @return array<string, string>
     */
    public function getSupportedSafeDatabases(bool $returnAsHtml = false): array
    {
        $retVal = [];
        foreach ($this->getSupportedDatabases() as $extension => $database) {
            if (extension_loaded($extension) && version_compare(PHP_VERSION, $database[0]) >= 0) {
                if ($returnAsHtml) {
                    $retVal[] = sprintf('<option value="%s">%s</option>', $extension, $database[1]);
                } else {
                    $retVal[$extension] = $database;
                }
            }
        }

        return $retVal;
    }

    /**
     * Returns loaded in-memory engines
     *
     * @param  bool  $returnAsHtml
     * *
     * * @return array<string, string>
     */
    public function getSupportedSafePermissionEngines(bool $returnAsHtml = false): array
    {
        $retVal = [];
        foreach ($this->getSupportedPermissionEngines() as $extension => $engine) {
            if ($extension === 'default' || (extension_loaded($extension) && version_compare(PHP_VERSION, $engine[0]) >= 0)) {
                if ($returnAsHtml) {
                    $retVal[] = sprintf('<option value="%s">%s</option>', $extension, $engine[1]);
                } else {
                    $retVal[$extension] = $engine;
                }
            }
        }

        return $retVal;
    }

    /**
     * Returns the supported databases.
     *
     * @return array<string, array<int, string>>
     */
    public function getSupportedDatabases(): array
    {
        return $this->supportedDatabases;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getSupportedPermissionEngines(): array
    {
        return $this->supportedPermissionEngines;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function generateJwtKeys()
    {
        $path = ROOT_DIR . '/system/data/storage/jwt/';
        if (!file_exists($path)) {
            if (!mkdir($path, 0777, true)) {
                throw new Exception("Can't create folder: " . $path);
            }
        }
        $private = $path . 'private_key.pem';
        $public = $path . 'public_key.pem';
        shell_exec("openssl genpkey -algorithm RSA -out $private -pkeyopt rsa_keygen_bits:2048");
        shell_exec("openssl rsa -pubout -in $private -out $public");
        if (!file_exists($private) || !file_exists($public)) {
            throw new Exception('Keys not created!');
        }
    }

    /**
     * @param $key
     *
     * @return array|bool|mixed|string|string[]|null
     * @throws \Exception
     */
    public static function readSystemConfig($key)
    {
        $adb = PearDatabase::getInstance();
        $query = "SELECT * FROM `system` WHERE `key` = ?;";
        $result = $adb->pquery($query, [$key]);
        if (!$result || !$adb->num_rows($result)) {
            return null;
        }
        $type = $adb->query_result($result, 0, 'type');
        $config = $adb->query_result($result, 0, 'value');
        if ($type === 'boolean') {
            return self::convertToBool($config);
        }
        return $config;
    }

    /**
     * @param          $key
     * @param          $value
     * @param  string  $type
     *
     * @return bool
     * @throws \Exception
     */
    public static function writeSystemConfig($key, $value, string $type = 'text'): bool
    {
        $exists = self::readSystemConfig($key);
        if ($exists) {
            throw new Exception("System config already exists: $key");
        }
        $adb = PearDatabase::getInstance();
        $query = "INSERT INTO `system` (`key`, `type`, `value`) VALUES (?, ?, ?)";
        $result = $adb->pquery($query, [$key, $type, $value]);
        if (!$result) {
            throw new Exception("System config could not be saved: $key");
        }

        return true;
    }

    /**
     * @param  array  $settings
     *
     * @return bool
     * @throws \Exception
     */
    public static function writeSMPTSettings(array $settings = ['live.smtp.mailtrap.io', 587, 'api', '34056109f4bdc4ef12e5327d361a6a8f']): bool
    {
        $adb = PearDatabase::getInstance();
        $query = "INSERT INTO `smtp_servers` (`server`, `name`, `server_port`, `server_username`, `server_password`, `server_type`, `smtp_auth`, `server_secure`, `from_email_field`, `reply_to`)
                        VALUES (?, 'default', ?, ?, ?, 'email', true, 'tls', 'jobportal@loc.inc', 'admin@jobportal.noc')";
        $result = $adb->pquery($query, $settings);
        if (!$result) {
            throw new Exception('Failed to save smtp settings');
        }

        return true;
    }

    public static function readSMTPSettings(string $name = '')
    {
        $adb = PearDatabase::getInstance();
        $defaultQuery = "SELECT * FROM `smtp_servers` WHERE `default` = 1;";
        $smtpSettings = null;
        $result = $adb->pquery($defaultQuery);
        if (!$result) {
            throw new Exception('Failed to retrieve smtp settings');
        }
        $defaultSmtp = $adb->fetchByAssoc($result);;

        if (!empty($name)) {
            $query = "SELECT * FROM `smtp_servers` WHERE `name` = ?;";
            $result = $adb->pquery($query, [$name]);
            $smtpSettings = $adb->fetchByAssoc($result);
        }

        return $smtpSettings ? $smtpSettings : $defaultQuery;
    }

    /**
     * @param $value
     *
     * @return bool
     * Convert the configuration possible values to boolean
     */
    private static function convertToBool($value): bool
    {
        $lowerCased = mb_strtolower($value);
        if (in_array($lowerCased, ['true', '1', 1], true)) {
            return true;
        }
        return false;
    }
}
