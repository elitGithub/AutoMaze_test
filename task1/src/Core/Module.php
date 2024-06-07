<?php

declare(strict_types = 1);

namespace Core;

abstract class Module
{
    protected Controller $controller;
    protected Model $model;

    public function getController(): Controller
    {
        return $this->controller;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public static function generateSecureToken(string $tokenKey)
    {
        $formToken = Storm::getStorm()->security->generateFormToken();
        Storm::getStorm()->session->addValue($tokenKey, $formToken);
        return $formToken;

    }

}
