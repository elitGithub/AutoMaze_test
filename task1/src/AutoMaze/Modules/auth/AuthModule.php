<?php

declare(strict_types = 1);

namespace AutoMaze\Modules\auth;

use Core\Middleware\SecurityMiddleware;
use Core\Module;

class AuthModule extends Module
{
    public function __construct() {
        $this->model = new AuthModel();
        $this->controller = new AuthController();
        $this->controller->setParams($this->model->params());
        $this->controller->setModule($this);
        $this->model->setModule($this);
        $this->controller->registerMiddleware(new SecurityMiddleware());
    }

}
