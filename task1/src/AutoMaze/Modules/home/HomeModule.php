<?php

declare(strict_types = 1);

namespace AutoMaze\Modules\home;

use Core\Middleware\SecurityMiddleware;
use Core\Module;

class HomeModule extends Module
{
    public function __construct() {
        $this->model = new HomeModel();
        $this->controller = new HomeController();
        $this->controller->setParams($this->model->params());
        $this->controller->setModule($this);
        $this->model->setModule($this);
        $this->controller->registerMiddleware(new SecurityMiddleware());
    }

}
