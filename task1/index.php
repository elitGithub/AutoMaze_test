<?php

declare(strict_types = 1);


use Core\Storm;
use engine\User;
use Session\JWTHelper;
require_once 'main_include.php';


$app = new Storm(__DIR__);
$app->on(Storm::EVENT_BEFORE_REQUEST, function() use ($app) {
    $app->session->addValue('ua', $_SERVER['HTTP_USER_AGENT']);
    $current_user = new User();
    if (JWTHelper::checkJWT()) {
        $current_user->retrieveUserInfoFromFile();
    }

    if (!JWTHelper::checkJWT()) {
        $app->session->destroyUserSession();
    }
    $app->user = $current_user;
});

$app->unleash();

$app->on(Storm::EVENT_AFTER_REQUEST, function() use ($app) {
    if (!JWTHelper::checkJWT()) {
        $app->session->destroyUserSession();
    }
});
