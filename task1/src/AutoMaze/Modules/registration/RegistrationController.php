<?php

namespace AutoMaze\Modules\registration;

use Core\Storm;
use Core\Controller;
use engine\HttpResponseCodes;
use Interfaces\ApiOnlyActions;

class RegistrationController extends Controller implements ApiOnlyActions
{
    private array  $apiOnlyActions = [];
    protected bool $isApiCall      = false;

    public function __construct()
    {
    }

    public function isApiOnlyAction(string $actionName): bool
    {
        return in_array($actionName, $this->apiOnlyActions, true);
    }

    public function setApiOnlyActions(array $actions)
    {
        $this->apiOnlyActions = $actions;
    }

    public function setIsApiCall(bool $isApiCall): void
    {
        $this->isApiCall = $isApiCall;
    }

}
