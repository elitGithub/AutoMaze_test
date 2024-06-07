<?php

namespace Exceptions;

use engine\HttpResponseCodes;
use Exception;

class NotFoundException extends Exception
{
    protected $code = HttpResponseCodes::HTTP_NOT_FOUND;
    protected $message = 'Page not found';

}
