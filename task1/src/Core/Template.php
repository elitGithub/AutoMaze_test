<?php

declare(strict_types = 1);

namespace Core;

class Template
{
    public string $templateContent = '';
    public array  $params          = [
        'title'     => 'AutoMaze',
        'brandName' => 'AutoMaze',
    ];
    public array $translations = [];

    public function __construct(string $templateContent, array $params = [])
    {
        $this->templateContent = $templateContent;
        $this->params = array_merge($this->params, $params);
        $this->translations = $this->loadTranslations();
    }

    public function parseTemplate()
    {
        $parsedTemplate = $this->templateContent;

        $parsedTemplate = preg_replace_callback(
            '/\$\((translation)\):\$(\w+)/',
            function ($matches) {
                return $this->translate($matches[2]);
            },
            $parsedTemplate
        );
        // Parse regular template parameters
        return preg_replace_callback(
            '/\$(\w+|\((\w+) : \(([_\w]+)\) (\w+)\)),?/',
            function ($matches) {
                return $this->matchHandler($matches, $this->params);
            },
            $parsedTemplate
        );
    }

    protected function matchHandler($matches, $paramsArray)
    {
        if (!isset($matches[1]) || is_numeric($matches[1])) {
            return $matches[0];
        }

        $key = $matches[1];
        return $paramsArray[$key] ?? $matches[0];  // Default to the original string if no matching key in params
    }

    protected function translate($key)
    {
        return $this->translations[$key] ?? $key;
    }

    protected function loadTranslations()
    {
        global $default_language;
        // Load the authenticated user language
        $language = Storm::getStorm()->session->readKeyValue('authenticated_user_language');
        $translations = [];

        $defaultTranslationsFile = SRC_DIR . DIRECTORY_SEPARATOR . APP_NAME . DIRECTORY_SEPARATOR . 'translations' . DIRECTORY_SEPARATOR . $default_language . '.php';
        if (file_exists($defaultTranslationsFile)) {
            $translations = include $defaultTranslationsFile;
        }

        // If the user's language is not the default, try load translations for them.
        if ($language !== $default_language) {
            $applicationTranslationsFile = SRC_DIR . DIRECTORY_SEPARATOR . APP_NAME . DIRECTORY_SEPARATOR . 'translations' . DIRECTORY_SEPARATOR . $language . '.php';
            if (file_exists($applicationTranslationsFile)) {
                $applicationTranslations = include $applicationTranslationsFile;
                $translations = array_merge($translations, $applicationTranslations);
            }
        }

        return $translations;
    }
}
