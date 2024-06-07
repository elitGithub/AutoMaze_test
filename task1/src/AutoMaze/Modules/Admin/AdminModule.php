<?php

declare(strict_types = 1);

namespace JobPortal\Modules\Admin;

use Core\Middleware\AuthMiddleware;
use Core\Middleware\SecurityMiddleware;
use Core\Module;

class AdminModule extends Module
{
    public function __construct() {
        $this->model = new AdminModel();
        $this->controller = new AdminController();
        $this->controller->setParams($this->model->params());
        $this->controller->setModule($this);
        $this->model->setModule($this);
        $this->controller->registerMiddlewares([new SecurityMiddleware(), new AuthMiddleware()]);
    }
}