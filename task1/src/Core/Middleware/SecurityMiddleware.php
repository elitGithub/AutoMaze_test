<?php

declare(strict_types = 1);

namespace Core\Middleware;

use Core\Storm;
use engine\HttpResponseCodes;
use Helpers\Routes;

class SecurityMiddleware extends BaseMiddleware
{
    public function execute(&$action = null)
    {
        // GET requests will get the token.
        if (Storm::getStorm()->request->isGet()) {
            return true;
        }
        $headers = array_change_key_case(Storm::getStorm()->request->getHeaders());
        $csrfToken = $headers['x-csrf-token'] ?? null;

        if (!$this->isValidCSRFToken($csrfToken)) {
            Storm::getStorm()
                ->response
                ->setSuccess(false)
                ->setMessage('Invalid request - wrong csrf token')
                ->setData(['request' => Storm::getStorm()->request['form-token'], 'headers' => $csrfToken, 'sess' => $_SESSION])
                ->setCode(HttpResponseCodes::HTTP_FORBIDDEN)
                ->sendResponse();
        }
    }

    private function isValidCSRFToken($csrfToken): bool
    {
        $sessionToken = Storm::getStorm()->session->readKeyValue('csrf_token');
        if ($sessionToken === $csrfToken) {
            return true;
        }
        if (isset(Storm::getStorm()->request['form-token'])) {
            return $sessionToken === Storm::getStorm()->request['form-token'];
        }

        return false;
    }

}
