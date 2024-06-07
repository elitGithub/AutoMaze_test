<?php

namespace Core;

use engine\HttpResponseCodes;
use Exceptions\NotFoundException;
use Helpers\Routes;

class Router
{
    protected Storm $app;
    public Request  $request;
    public Response       $response;
    public Routes         $routes;

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
        $path = $this->request->getPath();
        $path = trim($path, '/');
        $segments = explode('/', $path);
        $module = $segments[0] ?: 'home';
        $action = $segments[1] ?? 'home';
        $modulePath = $this->routes->resolveModule($module);
        if ($modulePath) {
            return $this->handleModule($modulePath, $action);
        }
        throw new NotFoundException("Module not found", HttpResponseCodes::HTTP_NOT_FOUND);
    }

    /**
     * @param $modulePath
     * @param $action
     *
     * @return mixed
     * @throws \Exceptions\NotFoundException
     */
    private function handleModule($modulePath, $action)
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
        if (!method_exists($controller, $action)) {
            throw new NotFoundException("Action '{$action}' not found in controller '{$controller}'", HttpResponseCodes::HTTP_NOT_FOUND);
        }
        Storm::getStorm()->setController($controller);
        Storm::getStorm()->getController()->setModule($module);

        foreach (Storm::getStorm()->getController()->getMiddlewares() as $middleware) {
            $middleware->execute();
        }
        return Storm::getStorm()->getController()->$action();
    }

}
