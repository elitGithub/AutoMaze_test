<?php

declare(strict_types = 1);

namespace JobPortal\Modules\registration;

use Core\Middleware\SecurityMiddleware;
use Core\Module;

class RegistrationModule extends Module
{
    public function __construct() {
        $this->model = new RegistrationModel();
        $this->controller = new RegistrationController();
        $this->controller->setParams($this->model->params());
        $this->controller->setModule($this);
        $this->model->setModule($this);
        $this->controller->registerMiddleware(new SecurityMiddleware());
    }
}
