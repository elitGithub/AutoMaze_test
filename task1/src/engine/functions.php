<?php

declare(strict_types = 1);

use Session\JWTHelper;

/**
 * @param $string
 *
 * @return array|mixed|string|string[]|null
 */
function to_html($string)
{
    if (is_string($string)) {
        $string = preg_replace(['/</', '/>/', '/"/'], ['&lt;', '&gt;', '&quot;'], $string);
    }

    return $string;
}

function diff($a = 0, $b = 0)
{
    return ($a - $b);
}


/**
 * @return void
 * @throws \Throwable
 */
function destroyUserSession()
{
    if (session_status() !== PHP_SESSION_NONE) {
        session_unset();
        session_destroy();
    }
    JWTHelper::removeToken();
}


/**
 * @param        $string
 * @param  bool  $urldecode
 *
 * @return string
 */
function normalizeName($string, bool $urldecode = true): string
{
    // Is email? No need to decode + signs.
    if ((strpos($string, '@') && strpos($string, '.')) || filter_var($string, FILTER_VALIDATE_EMAIL)) {
        return strip_tags(htmlspecialchars_decode(html_entity_decode(rawurldecode($string)), ENT_QUOTES));
    }
    // Separated for readability
    $normalizedString = replacePercent($string);
    if ($urldecode) {
        $normalizedString = rawurldecode($normalizedString);
        $normalizedString = urldecode(replacePercent($normalizedString));
    }
    $normalizedString = html_entity_decode($normalizedString);
    $normalizedString = htmlspecialchars_decode($normalizedString, ENT_QUOTES);
    return strip_tags($normalizedString);
}

/**
 * @param $source
 *
 * @return string
 */
function replacePercent($source): string
{
    $pattern = '/[0-9A-Fa-f]/';
    $decodedStr = '';
    $pos = 0;
    $len = strlen($source);
    while ($pos < $len) {
        $charAt = substr($source, $pos, 1);
        if ($charAt === '%') {
            $pos++;
            $hexVal = substr($source, $pos, 2);
            $decodedStr .= preg_match($pattern, $hexVal) ? "{$charAt}{$hexVal}" : "{$charAt}25";
            $pos += preg_match($pattern, $hexVal) ? 2 : 0;
        } else {
            $decodedStr .= $charAt;
            $pos++;
        }
    }
    return $decodedStr;
}

/**
 * Determines if the current version of PHP is equal to or greater than the supplied value
 *
 * @param  string
 *
 * @return    bool    TRUE if the current version is $version or higher
 */
function is_php($version): bool
{
    static $_is_php;
    $version = (string)$version;

    if (!isset($_is_php[$version])) {
        $_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
    }

    return $_is_php[$version];
}

/**
 * Remove Invisible Characters
 *
 * This prevents sandwiching null characters
 * between ascii characters, like Java\0script.
 *
 * @param  string
 * @param  bool
 *
 * @return    string
 */
function remove_invisible_characters($str, $url_encoded = true): string
{
    $non_displayables = [];

    // every control character except newline (dec 10),
    // carriage return (dec 13) and horizontal tab (dec 09)
    if ($url_encoded) {
        $non_displayables[] = '/%0[0-8bcef]/i';    // url encoded 00-08, 11, 12, 14, 15
        $non_displayables[] = '/%1[0-9a-f]/i';    // url encoded 16-31
        $non_displayables[] = '/%7f/i';    // url encoded 127
    }

    $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';    // 00-08, 11, 12, 14-31, 127

    do {
        $str = preg_replace($non_displayables, '', $str, -1, $count);
    } while ($count);

    return $str;
}

/**
 * Function usable
 *
 * Executes a function_exists() check, and if the Suhosin PHP
 * extension is loaded - checks whether the function that is
 * checked might be disabled in there as well.
 *
 * This is useful as function_exists() will return FALSE for
 * functions disabled via the *disable_functions* php.ini
 * setting, but not for *suhosin.executor.func.blacklist* and
 * *suhosin.executor.disable_eval*. These settings will just
 * terminate script execution if a disabled function is executed.
 *
 * The above described behavior turned out to be a bug in Suhosin,
 * but even though a fix was committed for 0.9.34 on 2012-02-12,
 * that version is yet to be released. This function will therefore
 * be just temporary, but would probably be kept for a few years.
 *
 * @link    http://www.hardened-php.net/suhosin/
 *
 * @param  string  $function_name  Function to check for
 *
 * @return    bool    TRUE if the function exists and is safe to call,
 *            FALSE otherwise.
 */
function function_usable($function_name)
{
    static $_suhosin_func_blacklist;

    if (function_exists($function_name)) {
        if (!isset($_suhosin_func_blacklist)) {
            $_suhosin_func_blacklist = extension_loaded('suhosin')
                ? explode(',', trim(ini_get('suhosin.executor.func.blacklist')))
                : [];
        }

        return !in_array($function_name, $_suhosin_func_blacklist, true);
    }

    return false;
}

/**
 * Tests for file writability
 *
 * is_writable() returns TRUE on Windows servers when you really can't write to
 * the file, based on the read-only attribute. is_writable() is also unreliable
 * on Unix servers if safe_mode is on.
 *
 * @link    https://bugs.php.net/bug.php?id=54709
 *
 * @param  string
 *
 * @return    bool
 */
function is_really_writable($file)
{
    // If we're on a Unix server with safe_mode off we call is_writable
    if (DIRECTORY_SEPARATOR === '/' && (is_php('5.4') or !ini_get('safe_mode'))) {
        return is_writable($file);
    }

    /* For Windows servers and safe_mode "on" installations we'll actually
     * write a file then read it. Bah...
     */
    if (is_dir($file)) {
        $file = rtrim($file, '/') . '/' . md5((string)mt_rand());
        if (($fp = @fopen($file, 'ab')) === false) {
            return false;
        }

        fclose($fp);
        @chmod($file, 0777);
        @unlink($file);
        return true;
    } elseif (!is_file($file) or ($fp = @fopen($file, 'ab')) === false) {
        return false;
    }

    fclose($fp);
    return true;
}

function &get_mimes()
{
    static $_mimes;

    if (empty($_mimes)) {
        $_mimes = file_exists(ROOT_DIR . '/system/mimes.php')
            ? include(ROOT_DIR . '/system/mimes.php')
            : [];

        if (file_exists(ROOT_DIR . '/config/' . ENVIRONMENT . '/mimes.php')) {
            $_mimes = array_merge($_mimes, include(ROOT_DIR . '/config/' . ENVIRONMENT . '/mimes.php'));
        }
    }

    return $_mimes;
}

/**
 * @param $items_list
 *
 * @return string
 */
function generateQuestionMarks($items_list): string
{
    // array_map will call the function specified in the first parameter for every element of the list in second parameter
    if (is_array($items_list)) {
        return implode(',', array_map(fn($v) => '?', $items_list));
    } else {
        return implode(',', array_map(fn($v) => '?', explode(',', $items_list)));
    }
}

/**
 * @param $directory
 * @param $fileName
 *
 * @return false|string
 */
function fileExists($directory, $fileName)
{
    // Construct the search pattern
    $pattern = $directory . '/' . $fileName . '.*';

    // Use glob to find files matching the pattern
    $files = glob($pattern);

    // Check if any files were found
    if ($files) {
        return basename($files[0]);
    }
    return false;
}

if (!function_exists('getallheaders')) {
    function getallheaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

function decode_html($str): string
{
    global $default_charset;
    return html_entity_decode($str, ENT_QUOTES, $default_charset);
}

function getRealIpAddr()
{
    $ipaddress = '';
    if (getenv('HTTP_X_REAL_IP')) {
        $ipaddress = getenv('HTTP_X_REAL_IP');
    } elseif (getenv('HTTP_CLIENT_IP')) {
        $ipaddress = getenv('HTTP_CLIENT_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('HTTP_X_FORWARDED')) {
        $ipaddress = getenv('HTTP_X_FORWARDED');
    } elseif (getenv('HTTP_FORWARDED_FOR')) {
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    } elseif (getenv('HTTP_FORWARDED')) {
        $ipaddress = getenv('HTTP_FORWARDED');
    } elseif (getenv('REMOTE_ADDR')) {
        $ipaddress = getenv('REMOTE_ADDR');
    } else {
        $ipaddress = 'UNKNOWN';
    }
    return $ipaddress;
}
