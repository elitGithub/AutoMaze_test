<?php

namespace AutoMaze\Modules\BugReport;

use Core\Middleware\SecurityMiddleware;
use Core\Module;

class BugReportModule extends Module
{
    public function __construct() {
        $this->controller = new BugReportController();
        $this->model = new BugReportModel();
        $this->controller->registerMiddleware(new SecurityMiddleware());
        $this->controller->setParams($this->model->params());
        $this->controller->setModule($this);
        $this->model->setModule($this);
    }
}