<?php

declare(strict_types = 1);

namespace Helpers;


use Core\Request;
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

    public function resolveRequestedPath(Request $request): array
    {
        $requestedPath = $request['requested_path'];

        // Check if the path uses query parameters or path segments
        if (strpos($requestedPath, '?') !== false) {
            // It's a query string URL
            parse_str(parse_url($requestedPath, PHP_URL_QUERY), $params);
            return $this->handleQueryStringParams($params);
        } else {
            // It's a path segment URL
            $segments = explode('/', trim($requestedPath, '/'));
            return $this->handlePathSegments($segments);
        }
    }

    private function handleQueryStringParams(array $params): array
    {
        extract($params);
        return [$module, $action, $vars];
    }

    private function handlePathSegments(array $segments): array
    {
        if (count($segments) < 2) {
            return ['home', $segments[0] ?? 'home', $segments[1] ?? null];
        }
        $module = $segments[0] ?? 'home';
        $action = $segments[1] ?? 'home';
        $vars = array_slice($segments, 2);
        return [$module, $action, $vars];
    }


}
