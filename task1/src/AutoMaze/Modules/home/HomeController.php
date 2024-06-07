<?php

declare(strict_types = 1);

namespace AutoMaze\Modules\home;

use Core\Controller;

class HomeController extends Controller
{

    public function home()
    {
        $this->setLayout('main');
        $this->addComponent('hello-world');
        return $this->render('home', $this->params);
    }

}
