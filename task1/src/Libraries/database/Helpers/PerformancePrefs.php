<?php

declare(strict_types = 1);

namespace Libraries\database\Helpers;

/**
 * System performance preferences
 */
class PerformancePrefs
{
    /**
     * Get performance parameter configured value or default one
     *
     * @param      $key
     * @param  bool  $default
     *
     * @return false|mixed
     */
    public static function get($key, bool $default = false)
    {
        return PERFORMANCE_CONFIG[$key] ?? $default;
    }

    /** Get boolean value
     *
     * @param      $key
     * @param  bool  $default
     *
     * @return bool|mixed
     */
    public static function getBoolean($key, bool $default = false)
    {
        return self::get($key, $default);
    }

    /** Get Integer value
     *
     * @param      $key
     * @param  bool  $default
     *
     * @return int
     */
    public static function getInteger($key, bool $default = false): int
    {
        return intval(self::get($key, $default));
    }
}
