<?php

declare(strict_types = 1);

namespace Session;

use Core\System;
use engine\User;
use Libraries\Namshi\JOSE\SimpleJWS;
use Permissions\CacheSystemManager;
use Throwable;

/**
 * Wrapper around Namshi\JOSE for custom JWT usage
 */
class JWTHelper
{
    public const MODE_GENERATE = 'generate';

    public const MODE_LOGIN = 'login';
    public const LAST_LOGIN = 'last_login';

    protected static array $actionsToSkip = [
        'Authenticate',
        'Login',
        'Logout',
    ];

    protected static string $encryptMethod = 'AES-256-CBC';

    /**
     * @var int|string
     */
    private static $userIdFromToken = 0;

    private static string $userLangFromToken = '';

    private static int $sessionTimeout = 3600;

    private static array $tokenUpdateIgnoreActions = [
        'Logout',
    ];

    /**
     * Cookie default lifetime in seconds - 1 day
     */
    public const DEFAULT_COOKIE_LIFETIME = 86400;

    /**
     * JWT cookie lifetime is 60 seconds longer than session timeout (and JWT expiration time)
     */
    public const JWT_COOKIE_LIFETIME_EXTRA = 60;

    private static array $jwt_configuration = [
        'algorithm'               => 'RS256',
        'certificate_path'        => 'system/data/storage/jwt',
        'certificate_public_key'  => 'public_key.pem',
        'certificate_private_key' => 'private_key.pem',
        'cookie_name'             => 'expenses-tracker-token',
        'parent_cookie_name'      => 'parent-expenses-tracker',
        'expiration_minutes'      => 60 * 24,
        'expired_token_storage'   => 'tokens',
        'refresh_token_name'      => 'rfrsh',
        'expired_token_prefix'    => '__EXPIRED__',
    ];


    /**
     * @return mixed
     */
    private static function getParentTokenFromStorage()
    {
        $key = self::$jwt_configuration['parent_cookie_name'];
        $jwt = $_COOKIE[$key] ?? false;
        if (!$jwt) {
            $headers = getallheaders();
            $jwt = $headers[self::translateCookieNameToHeader($key)] ?? null;
        }
        return $jwt;
    }

    /**
     * @return mixed
     */
    private static function getTokenFromStorage()
    {
        $key = self::$jwt_configuration['cookie_name'];
        $jwt = $_COOKIE[$key] ?? false;
        if (!$jwt) {
            $headers = getallheaders();
            $jwt = $headers[self::translateCookieNameToHeader($key)] ?? null;
        }
        return $jwt;
    }

    /**
     * @param $name
     *
     * @return string
     */
    private static function translateCookieNameToHeader($name): string
    {
        $delimiter = '-';
        $words = explode($delimiter, $name);
        return implode($delimiter, array_map(function ($s) {
            return ucfirst($s);
        }, $words));
    }

    /**
     * @param        $token
     * @param  bool  $verifyOnly
     *
     * @return bool
     */
    public static function isJWTValid($token, bool $verifyOnly = false): bool
    {
        try {
            $token = SimpleJWS::load($token);
            $publicPath = self::$jwt_configuration['certificate_path'] . '/' . self::$jwt_configuration['certificate_public_key'];
            $publicKey = openssl_pkey_get_public("file://$publicPath");
            if ($verifyOnly) {
                return $token->verify($publicKey, self::$jwt_configuration['algorithm']);
            }
            return $token->isValid($publicKey, self::$jwt_configuration['algorithm']);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @param $jwt
     *
     * @return bool
     */
    public static function isTokenFromOldSession($jwt): bool
    {
        $payload = self::getTokenPayload($jwt);

        if (!is_array($payload) ||
            empty($payload['iat']) ||
            empty($payload['user']) ||
            empty($payload['user']['id']) ||
            (empty($payload['user']['last_login']))) {
            return false;
        }

        $lastLoginData = User::getLastLoginData($payload['user']['id']);

        $lastLoginTimeDb = strtotime($lastLoginData['last_login']);

        $lastLoginTimeToken = strtotime($payload['user']['last_login']);

        if ($lastLoginTimeToken) {
            return ($lastLoginTimeToken !== $lastLoginTimeDb);
        }

        return false;
    }

    /**
     * @return bool
     * @throws \Throwable
     */
    public static function checkJWT(): bool
    {
        $jwt = self::getTokenFromStorage();
        if (!self::isJWTValid($jwt, true)) {
            return false;
        }
        if (self::isTokenFromOldSession($jwt)) {
            self::removeToken();
            return false;
        }

        $action = $_REQUEST['action'] ?? null;
        $isExpired = self::isJWTExpired($jwt);
        if ($isExpired && !in_array($action, self::$actionsToSkip)) {
            if ($userId = self::canRefreshTokens($jwt, self::getRefreshTokenFromStorage())) {
                $lang = self::getUserLanguageFromToken();
                self::$userIdFromToken = $userId;
                self::generateJwtDataCookie($userId, $lang);
                return true;
            }

            self::clearCookies();
            session_unset();
            session_destroy();
            return false;
        }
        self::$userIdFromToken = self::extractUserIdFromToken($jwt);
        self::$userLangFromToken = self::extractUserLangFromToken($jwt);
        self::generateJwtDataCookie(self::$userIdFromToken, self::$userLangFromToken);
        return true;
    }

    /**
     * @return mixed
     */
    private static function getRefreshTokenFromStorage()
    {
        $key = self::$jwt_configuration['refresh_token_name'];
        $jwt = $_COOKIE[$key];
        if (!$jwt) {
            $headers = getallheaders();
            $jwt = $headers[self::translateCookieNameToHeader($key)];
        }
        return $jwt;
    }

    /**
     * @return mixed|string
     */
    public static function getUserLanguageFromToken()
    {
        global $default_language;

        if (self::$userLangFromToken) {
            return self::$userLangFromToken;
        }
        self::$userLangFromToken = self::extractUserLangFromToken(self::getTokenFromStorage());
        return self::$userLangFromToken ?? $default_language;
    }


    /**
     * @param $token
     *
     * @return mixed|null
     */
    private static function extractUserIdFromToken($token)
    {
        $payload = self::getTokenPayload($token);
        return !empty($payload['user']) ? $payload['user']['id'] : null;
    }

    /**
     * @param $token
     *
     * @return mixed|string
     */
    private static function extractUserLangFromToken($token)
    {
        global $default_language;
        $payload = self::getTokenPayload($token);
        return !empty($payload['user']) ? $payload['user']['lang'] : $default_language;
    }

    /**
     * @param $token
     *
     * @return array|null
     */
    private static function getTokenPayload($token): ?array
    {
        if (!$token) {
            return null;
        }
        $token = SimpleJWS::load($token);
        return $token->getPayload();
    }

    /**
     * @return int
     * @throws \Exception
     */
    private static function getSessionTimeout(): int
    {
        $sessionTimeOut = System::readSystemConfig('session_time');
        return (int)$sessionTimeOut ?? self::$sessionTimeout;
    }

    /**
     * @return void
     */
    private static function clearCookies()
    {
        if (!isset($_SERVER['HTTP_COOKIE'])) {
            return;
        }
        $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
        foreach ($cookies as $cookie) {
            $parts = explode('=', $cookie);
            $name = trim($parts[0]);
            setcookie($name, '', time() - 1000);
            setcookie($name, '', time() - 1000, '/');
        }
    }

    /**
     * Remove JWT token from cookie
     *
     * @throws \Throwable
     */
    public static function removeToken()
    {
        $cookieName = self::$jwt_configuration['cookie_name'];
        $refreshCookieName = self::$jwt_configuration['refresh_token_name'];
        $parentName = self::$jwt_configuration['parent_cookie_name'];
        $parentToken = self::getParentTokenFromStorage();
        if (!$parentToken) {
            self::removeCookie($cookieName);
            self::removeCookie($refreshCookieName);
            return;
        }
        // LoginAs logic
        self::setTokenCookie($cookieName, $parentToken);
        self::setRefreshTokenCookie($parentToken);
        self::removeCookie($parentName);
    }

    /**
     * @param $jwt
     *
     * @return void
     * @throws \Exception
     */
    private static function setRefreshTokenCookie($jwt)
    {
        self::setTokenCookie(self::$jwt_configuration['refresh_token_name'], self::generateRefreshToken($jwt));
    }

    /**
     * @param $token
     *
     * @return false
     */
    public static function isJWTExpired($token): bool
    {
        try {
            $token = SimpleJWS::load($token);
            return $token->isExpired();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @param $jwt
     *
     * @return string
     */
    private static function generateRefreshToken($jwt): string
    {
        global $dbConfig;
        $token = SimpleJWS::load($jwt);
        $payload = $token->getPayload();
        $secretData = [
            'id'    => $payload['user']['id'],
            'ip'    => $payload['ip'],
            'token' => $jwt,
        ];

        $secretData = json_encode($secretData);
        $secretKey = sha1($dbConfig['db_name'] . $_SERVER['REMOTE_ADDR']);
        $secretIv = md5($dbConfig['db_name'] . $_SERVER['REMOTE_ADDR']);

        return base64_encode(
            openssl_encrypt(
                $secretData,
                self::$encryptMethod,
                hash('sha256', $secretKey),
                0,
                substr(hash('sha256', $secretIv), 0, 16)
            )
        );
    }

    /**
     * @param $cookieName
     *
     * @return void
     * @throws \Throwable
     */
    private static function removeCookie($cookieName)
    {
        if (isset($_COOKIE[$cookieName])) {
            self::addToBlacklist($_COOKIE[$cookieName]);
            unset($_COOKIE[$cookieName]);
        }
        self::setTokenCookie($cookieName, '', -60); // empty value and old timestamp
    }

    /**
     * @param $cookieName
     * @param $token
     * @param $seconds
     *
     * @return void
     * @throws \Exception
     */
    public static function setTokenCookie($cookieName, $token, $seconds = null)
    {
        if (is_null($seconds)) {
            $seconds = self::getSessionTimeout() + self::JWT_COOKIE_LIFETIME_EXTRA;
            if (!$seconds && self::$jwt_configuration['expiration_minutes']) {
                $seconds = self::$jwt_configuration['expiration_minutes'] * 60;
            }
            if (!$seconds) {
                $seconds = self::DEFAULT_COOKIE_LIFETIME;
            }
        }
        $expiration = time() + $seconds;
        setcookie($cookieName, $token, $expiration, '/', '.' . $_SERVER['SERVER_NAME']);
        if ($cookieName === self::$jwt_configuration['cookie_name']) {
            setcookie($cookieName . '-exp', "$expiration", $expiration + 60, '/', '.' . $_SERVER['SERVER_NAME']);
        }
    }

    /**
     * @param          $userId
     * @param          $userLang
     * @param  string  $mode
     *
     * @return void
     * @throws \Exception
     */
    public static function generateJwtDataCookie($userId, $userLang = null, string $mode = self::MODE_GENERATE)
    {
        global $default_language;
        $name = self::$jwt_configuration['cookie_name'];
        $userData = [];
        switch ($mode) {
            case self::MODE_LOGIN:
                $lastLoginData = User::getLastLoginData($userId);
                $userData[self::LAST_LOGIN] = $lastLoginData[self::LAST_LOGIN];
                break;
            default:
                $oldPayload = self::getTokenPayload(self::getTokenFromStorage());
                if ($oldPayload && $oldPayload['user']) {
                    $userData = $oldPayload['user'];
                }
        }
        $token = self::getJwtToken($userId, $userLang ?? $default_language, $userData);
        self::setTokenCookie($name, $token);
        self::setRefreshTokenCookie($token);
    }

    /**
     * @param         $userId
     * @param         $userLang
     * @param  array  $userData
     *
     * @return string
     * @throws \Exception
     */
    public static function getJwtToken($userId, $userLang = null, array $userData = []): string
    {
        global $default_language;

        if (is_null($userLang)) {
            $userLang = $default_language;
        }
        $seconds = self::getSessionTimeout();
        if (!$seconds) {
            $expirationMinutes = self::$jwt_configuration['expiration_minutes'] ?? 60 * 24;
            $seconds = $expirationMinutes * 60;
        }
        $data = [
            'user' => array_merge($userData, ['id' => $userId, 'lang' => $userLang,]),
            'ip'   => $_SERVER['REMOTE_ADDR'],
        ];
        return JWTHelper::createJwtToken($data, $seconds);
    }

    /**
     * @param $data
     * @param $ttlSeconds
     *
     * @return string
     */
    public static function createJwtToken($data, $ttlSeconds): string
    {
        $currentTime = time();
        $data = array_merge($data, [
            'exp' => $currentTime + $ttlSeconds,
        ]);
        $jws = new SimpleJWS(['alg' => self::$jwt_configuration['algorithm'],]);
        $jws->setPayload($data);
        $privatePath = self::$jwt_configuration['certificate_path'] . '/' . self::$jwt_configuration['certificate_private_key'];
        $privateKey = openssl_pkey_get_private("file://$privatePath");
        $jws->sign($privateKey);
        return $jws->getTokenString();
    }

    /**
     * @param $token
     *
     * @return void
     * @throws \Throwable
     */
    private static function addToBlacklist($token)
    {
        $storedValue = self::$jwt_configuration['expired_token_prefix'] . $token;
// $key, $data, ?int $expiration = null
        $storedValue = mb_substr($storedValue, 0, 70);
        $data = [
            'token' => $token,
        ];
        CacheSystemManager::write($storedValue, $data, 86400);
    }

    /**
     * @param $jwt
     * @param $refreshToken
     *
     * @return false|mixed
     */
    public static function canRefreshTokens($jwt, $refreshToken)
    {
        $secretData = self::getSecretDataFromRefreshToken($refreshToken);

        if (!$secretData) {
            return false;
        }

        $isIpTheSame = !empty($secretData['ip']) && $secretData['ip'] === $_SERVER['REMOTE_ADDR'];
        $isTokenTheSame = !empty($secretData['token']) && $secretData['token'] === $jwt;

        if ($isIpTheSame && $isTokenTheSame) {
            return $secretData['id'] ?? false;
        }

        return false;
    }

    /**
     * @param $token
     *
     * @return mixed
     */
    private static function getSecretDataFromRefreshToken($token)
    {
        global $dbConfig;

        $secretKey = sha1($dbConfig['db_name'] . $_SERVER['REMOTE_ADDR']);
        $secretIv = md5($dbConfig['db_name'] . $_SERVER['REMOTE_ADDR']);
        $secretData = openssl_decrypt(
            base64_decode($token),
            self::$encryptMethod,
            hash('sha256', $secretKey),
            0,
            substr(hash('sha256', $secretIv), 0, 16)
        );

        return json_decode($secretData, true);
    }
}
