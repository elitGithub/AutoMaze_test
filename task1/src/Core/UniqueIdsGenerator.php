<?php

declare(strict_types = 1);

namespace Core;

use Exception;

/**
 * Class to generate hashes and random strings.
 */
class UniqueIdsGenerator
{

    /**
     * @return string
     */
    public function hash(): string
    {
        if (php_sapi_name() === 'cli' || PHP_SAPI === 'cli') {
            $fakeIP = [];
            for ($i = 0; $i < 5; $i ++) {
                try {
                    $fakeIP[] = random_int(0, 255);
                } catch (Exception $e) {
                    $fakeIP[] = 1;
                }
            }
            $_SERVER['REMOTE_ADDR'] = join('.', $fakeIP);
            try {
                $_SERVER['REMOTE_PORT'] = random_int(1, 65555);
            } catch (Exception $e) {
                $_SERVER['REMOTE_PORT'] = 1119;
            }
        }

        return sprintf('%08x', abs(crc32($_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME'] . $_SERVER['REMOTE_PORT'] . microtime())));
    }

    /**
     * @param  int  $length
     *
     * @return false|string
     */
    public function generateTrueRandomString(int $length = 13)
    {
        if (class_exists('\Random\Randomizer')) {
            try {
                // Use the new Random\Randomizer class from PHP 8.2 and later
                $randomizer = new \Random\Randomizer();
                $bytes = $randomizer->getBytes((int)ceil($length / 2));
                return substr(bin2hex($bytes), 0, $length);
            } catch (\Throwable $exception) {
                // Continue trying to use other methods.
            }

        }

        // Fallback to random_bytes if available, for PHP 7.0 and later
        try {
            if (function_exists('random_bytes')) {
                $bytes = random_bytes((int)ceil($length / 2));
                return substr(bin2hex($bytes), 0, $length);
            }
        } catch (\Throwable $exception) {
            // Continue trying to use other methods.
        }


        // Further fallback to openssl_random_pseudo_bytes if available
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes((int)ceil($length / 2));
            return substr(bin2hex($bytes), 0, $length);
        }

        // Last resort: Use uniqid, which is not cryptographically secure and more predictable
        $bytes = uniqid(self::hash(), true);
        return substr(bin2hex($bytes), 0, $length);
    }


}
