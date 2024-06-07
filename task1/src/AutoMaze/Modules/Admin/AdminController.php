<?php

declare(strict_types = 1);

namespace JobPortal\Modules\Admin;

use Core\Controller;

class AdminController extends Controller
{

    public function panel()
    {
        $this->setLayout('main');
        $this->addComponent('navbar');
        return $this->render('admin_dashboard', $this->params);
    }

}