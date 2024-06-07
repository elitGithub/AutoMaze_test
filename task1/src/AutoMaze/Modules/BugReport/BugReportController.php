<?php

namespace AutoMaze\Modules\BugReport;

use Core\Controller;
use Core\Request;

class BugReportController extends Controller
{
    public function report_bug(Request $request)
    {
        $this->module->getModel()->validate();
        var_dump($request);
    }
}