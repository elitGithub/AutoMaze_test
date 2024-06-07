<?php

declare(strict_types = 1);

namespace Core\Middleware;


use Core\Storm;

class AuthMiddleware extends BaseMiddleware
{

    public function execute()
    {
        $user = Storm::getStorm()->user;
        var_dump($user);
    }
}
