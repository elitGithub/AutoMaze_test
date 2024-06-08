<?php

namespace Core;

use engine\HttpResponseCodes;
use Exceptions\NotFoundException;
use Helpers\Routes;

class Router
{
    protected Storm $app;
    public Request  $request;
    public Response $response;
    public Routes   $routes;

    public function __construct(Request $request, Response $response)
    {
        $this->app = Storm::getStorm();
        $this->request = $request;
        $this->response = $response;
        $this->routes = new Routes();
    }

    /**
     * @return mixed
     * @throws \Exceptions\NotFoundException
     */
    public function resolve()
    {
        [$module, $action, $vars] = $this->routes->resolveRequestedPath($this->request);
        $modulePath = $this->routes->resolveModule($module);
        if ($modulePath) {
            return $this->handleModule($modulePath, $action, $vars);
        }
        [$module, $action, $vars] = $this->routes->resolveRequestedPath($this->request);
        $modulePath = $this->routes->resolveModule('home');
        return $this->handleModule($modulePath, $module, $vars);
    }

    /**
     * @param              $modulePath
     * @param              $action
     * @param  array|null  $vars
     *
     * @return mixed
     * @throws \Exceptions\NotFoundException
     */
    private function handleModule($modulePath, $action, ?array $vars = null)
    {
        $moduleName = basename($modulePath);
        $moduleClass = ucfirst($moduleName) . 'Module';
        $moduleClass = "AutoMaze\\Modules\\{$moduleName}\\$moduleClass";
        if (!class_exists($moduleClass)) {
            throw new NotFoundException("Controller '{$moduleClass}' not found", HttpResponseCodes::HTTP_NOT_FOUND);
        }

        /**
         * @var Module $module
         */
        $module = new $moduleClass($this->request, $this->response);
        $controller = $module->getController();

        if (!is_null($vars)) {
            $controller->vars = $vars;
        }
        // in case the controller doesn't implement home, we can try index.
        if (!method_exists($controller, $action)) {
            $action = 'index';
        }

        Storm::getStorm()->setController($controller);
        Storm::getStorm()->getController()->setModule($module);

        foreach (Storm::getStorm()->getController()->getMiddlewares() as $middleware) {
            $middleware->execute($action);
        }

        // Middleware may change the action variable - for example, Auth changes the action to 'login'.
        if (!is_callable([$controller, $action])) {
            throw new NotFoundException("Action '{$action}' not found in controller '{$controller}'", HttpResponseCodes::HTTP_NOT_FOUND);
        }

        return Storm::getStorm()->getController()->$action($this->request);
    }

}
