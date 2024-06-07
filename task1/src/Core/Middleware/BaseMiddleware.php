<?php

declare(strict_types = 1);

namespace Core\Middleware;

abstract class BaseMiddleware
{
    abstract public function execute();

}
