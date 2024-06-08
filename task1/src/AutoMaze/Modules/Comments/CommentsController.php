<?php

namespace AutoMaze\Modules\Comments;

use Core\Controller;
use Core\Request;
use Core\Storm;
use engine\HttpResponseCodes;

class CommentsController extends Controller
{

    public function addCommentToBug(Request $request)
    {
        if (!isset($request['id']) || !is_numeric($request['id'])) {
            Storm::getStorm()
                ->response
                ->setSuccess(false)
                ->setCode(HttpResponseCodes::HTTP_NOT_FOUND)
                ->setMessage('Missing bug id')
                ->setData([])
                ->sendResponse();
        }

        $module = $this->module->getModel();
        if ($module->addCommentToBug($request['id'], $request['comment'], Storm::getStorm()->session->readKeyValue('loginToken'))) {
            Storm::getStorm()
                ->response
                ->setSuccess(true)
                ->setMessage('')
                ->setData([])
                ->sendResponse();
        }

        Storm::getStorm()
            ->response
            ->setSuccess(false)
            ->setCode(HttpResponseCodes::HTTP_NOT_FOUND)
            ->setMessage('Missing bug id')
            ->setData([])
            ->sendResponse();
    }

}