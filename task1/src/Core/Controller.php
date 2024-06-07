<?php

declare(strict_types = 1);

namespace Core;

use Core\Middleware\BaseMiddleware;
use Interfaces\ToString;

abstract class Controller implements ToString
{

    public string $layout = 'main';
    public string $action = '';
    public Module $module;
    protected array $params = [];

    public function setModule(Module $module): void
    {
        $this->module = $module;
    }

    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * @var BaseMiddleware[]
     */
    protected array $middlewares = [];

    public function render($view, $params = [])
    {
        return Storm::getStorm()->view->renderView($view, $params);
    }

    public function renderOnlyView($view, $params = [])
    {
        return Storm::getStorm()->view->renderViewWithoutLayout($view, $params);
    }

    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    public function addComponent(string $name)
    {
        Storm::getStorm()->view->addComponent($name);
    }

    public function registerMiddleware(BaseMiddleware $middleware)
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * @param  BaseMiddleware[]  $middleware
     *
     * @return void
     */
    public function registerMiddlewares(array $middleware = [])
    {
        $this->middlewares = $middleware;
    }

    /**
     * @return BaseMiddleware[]
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function __toString()
    {
        return get_class($this);
    }

    public static function getInstance(): Controller
    {
        return new static();
    }
}
