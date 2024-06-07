<?php

namespace AutoMaze\Modules\Comments;

use Core\Middleware\SecurityMiddleware;
use Core\Module;

class CommentsModule extends Module
{
    public function __construct() {
        $this->controller = new CommentsController();
        $this->model = new CommentsModel();
        $this->controller->registerMiddleware(new SecurityMiddleware());
        $this->controller->setParams($this->model->params());
        $this->controller->setModule($this);
        $this->model->setModule($this);
    }
}