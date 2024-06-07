<?php

declare(strict_types = 1);

namespace AutoMaze\Modules\api;

use Core\Controller;
use Core\Request;
use Core\Storm;
use engine\HttpResponseCodes;
use Interfaces\ApiOnlyActions;
use Throwable;

class ApiController extends Controller
{

    public function actions(Request $request)
    {
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

    public function report_bug(Request $request)
    {
        $instance = Storm::getStorm()->getModuleInstance('bugreport');

        return $instance->getModel()->reportABug($request);
    }

}
