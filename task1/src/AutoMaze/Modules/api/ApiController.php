<?php

declare(strict_types = 1);

namespace JobPortal\Modules\api;

use Core\Storm;
use Core\Controller;
use engine\HttpResponseCodes;
use Interfaces\ApiOnlyActions;
use Throwable;

class ApiController extends Controller
{

    public function actions()
    {
        $request = Storm::getStorm()->request->getBody();
        $module = $request['module'] ?? '';
        $action = $request['action'] ?? '';
        try {
            $instance = Storm::getStorm()->getModuleInstance($module);
            $controller = $instance->getController();
            if (method_exists($controller, $action)) {
                if (($controller instanceof ApiOnlyActions) && $controller->isApiOnlyAction($action)) {
                    $controller->setIsApiCall(true);
                }
                $instance->getController()->$action();
            }
        } catch (Throwable $e) {
            var_dump($e);
            Storm::getStorm()
                ->response
                ->setSuccess(false)
                ->setCode(HttpResponseCodes::HTTP_TEAPOT)
                ->setMessage('Unknown module')
                ->setData(['exception' => $e->getMessage()])
                ->sendResponse();
        }

        Storm::getStorm()
            ->response
            ->setSuccess(false)
            ->setCode(HttpResponseCodes::HTTP_NOT_FOUND)
            ->setMessage('Unknown action')
            ->setData([])
            ->sendResponse();
    }

    public function citiesList()
    {
        Storm::getStorm()
            ->response
            ->setSuccess(true)
            ->setMessage('')
            ->setData(['cities' => $this->module->getModel()->citiesList()])
            ->sendResponse();
    }

    public function categoriesList()
    {
        Storm::getStorm()
            ->response
            ->setSuccess(true)
            ->setMessage('')
            ->setData(['categories' => $this->module->getModel()->categoriesList()])
            ->sendResponse();
    }

    public function statesList()
    {
        Storm::getStorm()
            ->response
            ->setSuccess(true)
            ->setMessage('')
            ->setData(['states' => $this->module->getModel()->statesList()])
            ->sendResponse();
    }

}
