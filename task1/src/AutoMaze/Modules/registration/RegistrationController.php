<?php

namespace JobPortal\Modules\registration;

use Core\Storm;
use Core\Controller;
use engine\HttpResponseCodes;
use Interfaces\ApiOnlyActions;
use JobPortal\Modules\Company\CompanyModel;

class RegistrationController extends Controller implements ApiOnlyActions
{
    private array  $apiOnlyActions = [];
    protected bool $isApiCall      = false;

    public function __construct()
    {
        $this->setApiOnlyActions(['registerCompany']);
    }

    public function isApiOnlyAction(string $actionName): bool
    {
        return in_array($actionName, $this->apiOnlyActions, true);
    }

    public function setApiOnlyActions(array $actions)
    {
        $this->apiOnlyActions = $actions;
    }

    public function register()
    {
        $this->setLayout('main');
        $this->addComponent('navbar');
        $this->addComponent('registration_forms');
        $this->addComponent('company_registration_form');
        $this->addComponent('applicant_registration_form');
        return $this->render('register', $this->params);
    }

    public function registerCompany()
    {
        if (!$this->isApiCall) {
            throw new \Exception("Can't call 'registerCompany' method without api specification.");
        }

        $model = new CompanyModel();
        $request = Storm::getStorm()->request;
        $result = $model->create($request);
        if ($result) {
            Storm::getStorm()->user->login($request['email'], $request['password']);
            Storm::getStorm()
                ->response
                ->setSuccess(true)
                ->setMessage('Successfully registered.')
                ->setData(['login' => true])
                ->sendResponse();
        }
        Storm::getStorm()
            ->response->setSuccess(false)
                      ->setCode(HttpResponseCodes::HTTP_ENTITY_UNPROCESSABLE)
                      ->setMessage('Error in user creation')
                      ->setData([])
                      ->sendResponse();
    }

    public function setIsApiCall(bool $isApiCall): void
    {
        $this->isApiCall = $isApiCall;
    }

}
