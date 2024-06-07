<?php

declare(strict_types = 1);

namespace Core;

use Libraries\database\PearDatabase;
use Libraries\Inflector\Inflector;
use Libraries\Inflector\InflectorFactory;
use engine\User;
use Session\Session;
use Throwable;

class Storm
{
    public const EVENT_BEFORE_REQUEST = 'beforeRequest';
    public const EVENT_AFTER_REQUEST  = 'afterRequest';

    protected array $eventListeners = [];

    public static string $ROOT_DIR;
    /**
     * @var Module[]
     */
    protected static array $moduleInstances  = [];
    public string          $defaultAppLayout = 'main';

    public ?Controller   $controller = null;
    public PearDatabase  $db;
    public Router        $router;
    public Request       $request;
    public Response      $response;
    public Session       $session;
    public ?User         $user       = null;
    public View          $view;
    public Security      $security;
    public Mailer        $mailer;
    public Inflector     $inflector;
    private static Storm $storm;

    public function __construct(string $rootPath)
    {
        static::$ROOT_DIR = $rootPath;
        $this->setStorm($this);
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        $this->session = new Session();
        $this->db = PearDatabase::getInstance();
        $this->view = new View();
        $this->user = new User();
        $this->security = new Security();
        $this->mailer = new Mailer();
        $this->inflector = InflectorFactory::create()->build();
    }

    /**
     * Gets an instance of a module with caching and dynamic loading.
     *
     * @param  string  $moduleName  The base name of the module (without 'Module' suffix).
     *
     * @return object|null Returns the module instance or null if not found.
     */
    public function getModuleInstance(string $moduleName)
    {
        $moduleClassName = (stripos($moduleName, 'Module') === false) ? $moduleName . 'Module' : $moduleName;
        $directoryName = strtolower(preg_replace('/Module$/', '', $moduleClassName));
        $moduleClass = "AutoMaze\\Modules\\$directoryName\\$moduleClassName";
        if (class_exists($moduleClass)) {
            if (!isset(self::$moduleInstances[$moduleClassName])) {
                self::$moduleInstances[$moduleClassName] = new $moduleClass();
            }
            return self::$moduleInstances[$moduleClassName];
        }
        return null;
    }

    public function unleash()
    {
        $this->triggerEvent(static::EVENT_BEFORE_REQUEST);
        try {
            echo $this->router->resolve();
        } catch (Throwable $e) {
            var_dump($e);
            $this->response->setStatusCode($e->getCode());
            echo $this->view->renderView('_error', ['code' => $e->getCode(), 'message' => $e->getMessage()]);
        }
        $this->triggerEvent(static::EVENT_AFTER_REQUEST);
    }

    public static function getStorm(): Storm
    {
        return static::$storm;
    }

    public static function isGuest(): bool
    {
        return static::$storm->user->isLoggedIn();
    }

    /**
     * @return Controller|null
     */
    public function getController(): ?Controller
    {
        return $this->controller;
    }

    /**
     * @param  Controller  $controller
     */
    public function setController(Controller $controller): void
    {
        $this->controller = $controller;
    }

    /**
     * @param  Storm  $storm
     */
    public static function setStorm(Storm $storm): void
    {
        static::$storm = $storm;
    }

    public function on($eventName, $callback)
    {
        $this->eventListeners[$eventName][] = $callback;
    }

    private function triggerEvent(string $eventName)
    {
        $callbacks = $this->eventListeners[$eventName] ?? [];
        foreach ($callbacks as $callback) {
            call_user_func($callback);
        }
    }
}
