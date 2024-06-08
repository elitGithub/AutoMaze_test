<?php

declare(strict_types = 1);

namespace AutoMaze\Modules\Admin;

use Core\Controller;

class AdminController extends Controller
{

    public function index()
    {
        $this->setLayout('main');
        return $this->render('admin_dashboard', $this->params);
    }


    public function login()
    {
        $this->setLayout('main');
        return $this->render('login', $this->params);
    }

}