<?php

namespace Exceptions;

use engine\HttpResponseCodes;
use Exception;

class ForbiddenException extends Exception
{
    protected $code = HttpResponseCodes::HTTP_FORBIDDEN;
    protected $message = "You don't have permission to access this page";
}
