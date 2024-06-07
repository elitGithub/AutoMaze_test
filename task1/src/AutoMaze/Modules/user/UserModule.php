<?php

declare(strict_types = 1);

namespace JobPortal\Modules\user;

use Core\Middleware\SecurityMiddleware;
use Core\Module;

class UserModule extends Module
{

    public function __construct() {
        $this->model = new UserModel();
        $this->controller = new UserController();
        $this->controller->setParams($this->model->params());
        $this->controller->setModule($this);
        $this->model->setModule($this);
        $this->controller->registerMiddleware(new SecurityMiddleware());
    }

}
