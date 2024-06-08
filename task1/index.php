<?php

declare(strict_types = 1);


use Core\Storm;
use engine\User;
use Session\JWTHelper;
require_once 'main_include.php';


$storm = new Storm(__DIR__);

$storm->unleash();