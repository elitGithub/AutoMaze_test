<?php

declare(strict_types = 1);

namespace Core;

class View
{
    public string $title      = '';
    public array  $components = [];
    public array  $scripts    = [];
    public array  $styles     = [];

    public function addComponent($componentName)
    {
        $componentBasePath = Storm::$ROOT_DIR . '/public/views/components/' . $componentName;
        $this->styles[] = "/public/views/components/$componentName/$componentName.css";
        $this->scripts[] = "/public/views/components/$componentName/$componentName.js";

        ob_start();
        include $componentBasePath . "/$componentName.php";
        $this->components[$componentName] = ob_get_clean();
    }

    /**
     * @param  string  $view
     * @param  array   $params
     *
     * @return array|string|string[]|null
     * Render this by loading the View from public/views, replace its contents with components, then load the
     * Layout from public/views/layouts/, then replace its content with the result of the view content.
     */
    public function renderView(string $view, array $params = [])
    {
        $viewContent = $this->renderOnlyView($view);
        foreach ($this->components as $componentName => $componentHtml) {
            $viewContent = str_replace('{{' . $componentName . '}}', $componentHtml, $viewContent);
        }

        $layoutContent = $this->layoutContent();

        $layoutContent = str_replace('{{styles}}', $this->renderAssets('css'), $layoutContent);
        $layoutContent = str_replace('{{scripts}}', $this->renderAssets('js'), $layoutContent);
        $layoutContent = str_replace('{{content}}', $viewContent, $layoutContent);

        // Inject view-specific JS and CSS files as <script> and <link> tags
        $viewJsTag = $this->generateFileTag($view, 'js');
        $viewCssTag = $this->generateFileTag($view, 'css');

        $layoutContent = str_replace('{{viewScripts}}', $viewJsTag, $layoutContent);
        $layoutContent = str_replace('{{viewStyles}}', $viewCssTag, $layoutContent);

        // Remove placeholders if no content
        $layoutContent = str_replace(['{{viewScripts}}', '{{viewStyles}}'], '', $layoutContent);

        $template = new Template($layoutContent, $params);
        return $template->parseTemplate();
    }

    private function generateFileTag(string $view, string $extension): string
    {
        $filePath = Storm::$ROOT_DIR . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $view . '.' . $extension;
        $fileUrl = '/public/views/' . $view . '.' . $extension;

        if (file_exists($filePath)) {
            if ($extension === 'js') {
                return '<script src="' . $fileUrl . '"></script>';
            } elseif ($extension === 'css') {
                return '<link rel="stylesheet" href="' . $fileUrl . '">';
            }
        }
        return '';
    }


    /**
     * @param $type
     *
     * @return string
     * Loads assets from the components.
     */
    private function renderAssets($type): string
    {
        $html = '';
        if ($type === 'css') {
            foreach ($this->styles as $style) {
                $html .= "<link rel='stylesheet' type='text/css' href='$style'>" . PHP_EOL;
            }
        } elseif ($type === 'js') {
            foreach ($this->scripts as $script) {
                $html .= "<script src='$script'></script>" . PHP_EOL;
            }
        }
        return $html;
    }

    /**
     * @return bool|string
     */
    private function layoutContent()
    {
        $layout = Storm::getStorm()->defaultAppLayout;

        if (Storm::getStorm()->getController()) {
            $layout = Storm::getStorm()->getController()->layout;
        }
        ob_start();
        include_once Storm::$ROOT_DIR . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $layout . '.php';
        return ob_get_clean();
    }

    /**
     * @param $view
     *
     * @return bool|string
     */
    private function renderOnlyView($view)
    {
        ob_start();
        include_once Storm::$ROOT_DIR . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $view . '.php';
        return ob_get_clean();
    }
}
