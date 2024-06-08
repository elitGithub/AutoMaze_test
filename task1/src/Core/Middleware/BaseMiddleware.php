<?php

declare(strict_types = 1);

namespace Core\Middleware;

use Core\Controller;

abstract class BaseMiddleware
{
    protected Controller $controller;
    abstract public function execute(&$action = null);

    public function setController(Controller $controller)
    {
        $this->controller = $controller;
    }
}
