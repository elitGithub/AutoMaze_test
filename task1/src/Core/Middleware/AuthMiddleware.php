<?php

declare(strict_types = 1);

namespace Core\Middleware;


use Core\Storm;

class AuthMiddleware extends BaseMiddleware
{

    public function execute(&$action = null)
    {
        global $gitClientId, $gitClientSecret;
        if (Storm::getStorm()->request['requested_path'] === '/admin/oAuth/github/callback' && isset(Storm::getStorm()->request['code'])) {
            $this->controller->vars['client_id'] = $gitClientId;
            $this->controller->vars['client_secret'] = $gitClientSecret;
            $this->controller->vars['code'] = Storm::getStorm()->request['code'];
            $action = 'githubAuth';
            return;
        }
        if (Storm::isGuest()) {
            $action = 'login';
        }
    }
}
