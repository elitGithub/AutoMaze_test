<?php

namespace Interfaces;

interface ApiOnlyActions
{
    public function isApiOnlyAction(string $actionName): bool;
    public function setApiOnlyActions(array $actions);
    public function setIsApiCall(bool $isApiCall);

}
