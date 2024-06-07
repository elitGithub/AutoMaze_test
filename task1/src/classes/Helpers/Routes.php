<?php

declare(strict_types = 1);

namespace Helpers;


use Core\Storm;

class Routes
{
    public function resolveModule(string $uri): ?string
    {
        $modulePath = SRC_DIR . MODULES_DIR . $uri;
        if (is_dir($modulePath)) {
            return $modulePath;
        }

        // Let's check - maybe it was passed with wrong capitalization?
        $uri = Storm::getStorm()->inflector->classify($uri);
        $modulePath = SRC_DIR . MODULES_DIR . $uri;
        if (is_dir($modulePath)) {
            return $modulePath;
        }

        // Maybe its plural?
        $uri = Storm::getStorm()->inflector->classify($uri);
        $uri = Storm::getStorm()->inflector->pluralize($uri);
        $modulePath = SRC_DIR . MODULES_DIR . $uri;
        if (is_dir($modulePath)) {
            return $modulePath;
        }
        return null;
    }

}
