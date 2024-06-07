<?php

declare(strict_types = 1);

namespace AutoMaze\Modules\auth;

use Core\Storm;
use Core\Controller;

class AuthController extends Controller
{

    public function login() {
        $this->setLayout('main');
        $this->addComponent('navbar');
        return $this->render('login', $this->params);
    }
    public function logout() {
        destroyUserSession();
        Storm::getStorm()->response->redirect('/');
    }

}
