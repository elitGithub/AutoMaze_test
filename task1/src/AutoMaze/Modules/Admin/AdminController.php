<?php

declare(strict_types = 1);

namespace AutoMaze\Modules\Admin;

use Core\Controller;
use Core\Request;
use Core\Storm;
use engine\HttpResponseCodes;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class AdminController extends Controller
{

    public function index()
    {
        $this->setLayout('main');
        return $this->render('admin_dashboard', $this->params);
    }

    public function githubAuth()
    {
        if (!Storm::isGuest()) {
            return $this->index();
        }
        $client = new Client();
        $res = $client->request(
            'POST',
            'https://github.com/login/oauth/access_token',
            [
                RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                ],
                RequestOptions::JSON    => [
                    'client_secret' => $this->vars['client_secret'],
                    'client_id'     => $this->vars['client_id'],
                    'code'          => $this->vars['code'],
                ],
            ]
        );
        $response = json_decode($res->getBody()->getContents(), true);
        if (isset($response['error'])) {
            return $this->login(Storm::getStorm()->request);
        }
        Storm::getStorm()->session->addValue('loginToken', $response['access_token']);
        Storm::getStorm()->session->addValue('token_type', $response['token_type']);
        Storm::getStorm()->session->addValue('github_scope', $response['scope']);
        Storm::getStorm()->session->addValue('loggedin', true);
        return $this->index();
    }

    public function getBugs()
    {
        $bugsModel = Storm::getStorm()->getModuleInstance('BugReportModule');
        $this->vars['bugs'] = $bugsModel->getModel()->getBugs();
        Storm::getStorm()
            ->response
            ->setSuccess(true)
            ->setMessage('')
            ->setData(['bugs' => $bugsModel->getModel()->getBugs()])
            ->setCode(HttpResponseCodes::HTTP_OK)
            ->sendResponse();
    }

    public function updateBug(Request $request)
    {
        if (!isset($request['id']) || !is_numeric($request['id']) || !isset($request['status'])) {
            Storm::getStorm()
                ->response
                ->setSuccess(false)
                ->setMessage('Error in updating bug')
                ->setData([])
                ->setCode(HttpResponseCodes::HTTP_BAD_REQUEST)
                ->sendResponse();
        }
        $bugReports = Storm::getStorm()->getModuleInstance('bugreports');
        $result = $bugReports->getModel()->updateBug((int)$request['id'], (string)$request['status']);
        if (!$result) {
            Storm::getStorm()
                ->response
                ->setSuccess(false)
                ->setMessage('Error in updating bug')
                ->setData([])
                ->setCode(HttpResponseCodes::HTTP_BAD_REQUEST)
                ->sendResponse();
        }
        $bugsModel = Storm::getStorm()->getModuleInstance('BugReportModule');
        Storm::getStorm()
            ->response
            ->setSuccess(true)
            ->setMessage('bug updated successfully')
            ->setData(['bugs' => $bugsModel->getModel()->getBugs()])
            ->setCode(HttpResponseCodes::HTTP_OK)
            ->sendResponse();
    }


    public function login($request)
    {
        $this->setLayout('main');
        [$module, $action, $vars] = Storm::getStorm()->router->routes->resolveRequestedPath($request);
        return $this->render('login', $this->params);
    }

}