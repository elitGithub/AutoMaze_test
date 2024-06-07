<?php

namespace Libraries\League\OAuth2\Google;

if (class_exists('Google_Client', false)) {
    // Prevent error with preloading in PHP 7.4
    // @see https://github.com/googleapis/google-api-php-client/issues/1976
    return;
}

$classMap = [
    'Libraries\\League\\OAuth2\\Google\\Client'                          => 'Google_Client',
    'Libraries\\League\\OAuth2\\Google\\Service'                         => 'Google_Service',
    'Libraries\\League\\OAuth2\\Google\\AccessToken\\Revoke'             => 'Google_AccessToken_Revoke',
    'Libraries\\League\\OAuth2\\Google\\AccessToken\\Verify'             => 'Google_AccessToken_Verify',
    'Libraries\\League\\OAuth2\\Google\\Model'                           => 'Google_Model',
    'Libraries\\League\\OAuth2\\Google\\Utils\\UriTemplate'              => 'Google_Utils_UriTemplate',
    'Libraries\\League\\OAuth2\\Google\\AuthHandler\\Guzzle6AuthHandler' => 'Google_AuthHandler_Guzzle6AuthHandler',
    'Libraries\\League\\OAuth2\\Google\\AuthHandler\\Guzzle7AuthHandler' => 'Google_AuthHandler_Guzzle7AuthHandler',
    'Libraries\\League\\OAuth2\\Google\\AuthHandler\\AuthHandlerFactory' => 'Google_AuthHandler_AuthHandlerFactory',
    'Libraries\\League\\OAuth2\\Google\\Http\\Batch'                     => 'Google_Http_Batch',
    'Libraries\\League\\OAuth2\\Google\\Http\\MediaFileUpload'           => 'Google_Http_MediaFileUpload',
    'Libraries\\League\\OAuth2\\Google\\Http\\REST'                      => 'Google_Http_REST',
    'Libraries\\League\\OAuth2\\Google\\Task\\Retryable'                 => 'Libraries\League\OAuth2\Google\Google_Task_Retryable',
    'Libraries\\League\\OAuth2\\Google\\Task\\Exception'                 => 'Google_Task_Exception',
    'Libraries\\League\\OAuth2\\Google\\Task\\Runner'                    => 'Google_Task_Runner',
    'Libraries\\League\\OAuth2\\Google\\Collection'                      => 'Google_Collection',
    'Libraries\\League\\OAuth2\\Google\\Service\\Exception'              => 'Google_Service_Exception',
    'Libraries\\League\\OAuth2\\Google\\Service\\Resource'               => 'Google_Service_Resource',
    'Libraries\\League\\OAuth2\\Google\\Exception'                       => 'Google_Exception',
];

foreach ($classMap as $class => $alias) {
    class_alias($class, $alias);
}

/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
class Google_Task_Composer extends \Libraries\League\OAuth2\Google\Task\Composer
{
}

/** @phpstan-ignore-next-line */
if (\false) {
    class Google_AccessToken_Revoke extends \Libraries\League\OAuth2\Google\AccessToken\Revoke
    {
    }

    class Google_AccessToken_Verify extends \Libraries\League\OAuth2\Google\AccessToken\Verify
    {
    }

    class Google_AuthHandler_AuthHandlerFactory extends \Libraries\League\OAuth2\Google\AuthHandler\AuthHandlerFactory
    {
    }

    class Google_AuthHandler_Guzzle6AuthHandler extends \Libraries\League\OAuth2\Google\AuthHandler\Guzzle6AuthHandler
    {
    }

    class Google_AuthHandler_Guzzle7AuthHandler extends \Libraries\League\OAuth2\Google\AuthHandler\Guzzle7AuthHandler
    {
    }

    class Google_Client extends \Libraries\League\OAuth2\Google\Client
    {
    }

    class Google_Collection extends \Libraries\League\OAuth2\Google\Collection
    {
    }

    class Google_Exception extends \Libraries\League\OAuth2\Google\Exception
    {
    }

    class Google_Http_Batch extends \Libraries\League\OAuth2\Google\Http\Batch
    {
    }

    class Google_Http_MediaFileUpload extends \Libraries\League\OAuth2\Google\Http\MediaFileUpload
    {
    }

    class Google_Http_REST extends \Libraries\League\OAuth2\Google\Http\REST
    {
    }

    class Google_Model extends \Libraries\League\OAuth2\Google\Model
    {
    }

    class Google_Service extends \Libraries\League\OAuth2\Google\Service
    {
    }

    class Google_Service_Exception extends \Libraries\League\OAuth2\Google\Service\Exception
    {
    }

    class Google_Service_Resource extends \Libraries\League\OAuth2\Google\Service\Resource
    {
    }

    class Google_Task_Exception extends \Libraries\League\OAuth2\Google\Task\Exception
    {
    }

    interface Google_Task_Retryable extends \Libraries\League\OAuth2\Google\Task\Retryable
    {
    }

    class Google_Task_Runner extends \Libraries\League\OAuth2\Google\Task\Runner
    {
    }

    class Google_Utils_UriTemplate extends \Libraries\League\OAuth2\Google\Utils\UriTemplate
    {
    }
}
