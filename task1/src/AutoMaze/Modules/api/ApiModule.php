<?php

declare(strict_types = 1);

namespace JobPortal\Modules\api;

use Core\Middleware\SecurityMiddleware;
use Core\Module;

class ApiModule extends Module
{

    public function __construct() {
        $this->controller = new ApiController();
        $this->model = new ApiModel();
        $this->controller->registerMiddleware(new SecurityMiddleware());
        $this->controller->setParams($this->model->params());
        $this->controller->setModule($this);
        $this->model->setModule($this);
    }

}
