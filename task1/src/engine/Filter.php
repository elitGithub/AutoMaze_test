<?php

declare(strict_types = 1);

/**
 * Wrapper class for various filter methods
 */
class Filter
{

    /**
     * Static wrapper method for filter_input().
     *
     * @param  int         $type  Filter type
     * @param  string      $variableName  Variable name
     * @param  int         $filter  Filter
     * @param  mixed|null  $default  Default value
     *
     * @return mixed
     */
    public static function filterInput(int $type, string $variableName, int $filter, $default = null)
    {
        $options = 0;
        if ($filter === FILTER_SANITIZE_NUMBER_FLOAT) {
            $options = FILTER_FLAG_ALLOW_FRACTION;
        }
        $return = filter_input($type, $variableName, $filter, $options);

        if ($filter === FILTER_SANITIZE_SPECIAL_CHARS) {
            $return = filter_input(
                $type,
                $variableName,
                FILTER_CALLBACK,
                ['options' => [new static(), 'filterSanitizeString']]
            );
        }

        return (is_null($return) || $return === false) ? $default : $return;
    }

    /**
     * Static wrapper method for filter_input_array.
     *
     * @param  int    $type  Filter type
     * @param  array  $definition  Definition
     *
     * @return array | bool | null
     */
    public static function filterInputArray(int $type, array $definition)
    {
        return filter_input_array($type, $definition);
    }

    /**
     * Static wrapper method for filter_var().
     *
     * @param  mixed       $variable  Variable
     * @param  int         $filter  Filter
     * @param  mixed|null  $default  Default value
     */
    public static function filterVar($variable, int $filter, $default = null)
    {
        $return = filter_var($variable, $filter);

        if ($filter === FILTER_SANITIZE_SPECIAL_CHARS) {
            $return = filter_var(
                $variable,
                FILTER_CALLBACK,
                ['options' => [new Filter(), 'filterSanitizeString']]
            );
        }

        return ($return === false) ? $default : $return;
    }

    /**
     * Static wrapper method for filter_var_array().
     */
    public static function filterArray(
        array $array,
              $options = FILTER_DEFAULT,
        bool  $addEmpty = true
    ) {
        return filter_var_array($array, $options, $addEmpty);
    }

    /**
     * Filters a query string.
     */
    public static function getFilteredQueryString(): string
    {
        $urlData = [];
        $cleanUrlData = [];

        if (!isset($_SERVER['QUERY_STRING'])) {
            return '';
        }

        parse_str((string) $_SERVER['QUERY_STRING'], $urlData);

        foreach ($urlData as $key => $urlPart) {
            $cleanUrlData[strip_tags($key)] = strip_tags($urlPart);
        }

        return http_build_query($cleanUrlData);
    }

    /**
     * This method is a polyfill for FILTER_SANITIZE_STRING, deprecated since PHP 8.1.
     */
    public function filterSanitizeString(string $string): string
    {
        $string = htmlspecialchars($string);
        $string = preg_replace('/\x00|<[^>]*>?/', '', $string);
        return str_replace(["'", '"'], ['&#39;', '&#34;'], $string);
    }

    /**
     * Removes a lot of HTML attributes.
     */
    public static function removeAttributes(string $html = ''): string
    {
        $keep = [
            'href',
            'src',
            'title',
            'alt',
            'class',
            'style',
            'id',
            'name',
            'size',
            'dir',
            'rel',
            'rev',
            'target',
            'width',
            'height',
            'controls',
        ];

        // remove broken stuff
        $html = str_replace('&#13;', '', $html);

        preg_match_all('/[a-z]+=".+"/iU', $html, $attributes);

        foreach ($attributes[0] as $attribute) {
            $attributeName = stristr((string) $attribute, '=', true);
            if (self::isAttribute($attributeName) && !in_array($attributeName, $keep)) {
                $html = str_replace(' ' . $attribute, '', $html);
            }
        }

        return $html;
    }

    /**
     * @param  string  $attribute
     *
     * @return bool
     */
    private static function isAttribute(string $attribute): bool
    {
        $globalAttributes = [
            'autocomplete', 'autofocus', 'disabled', 'list', 'name', 'readonly', 'required', 'tabindex', 'type',
            'value', 'accesskey', 'class', 'contenteditable', 'contextmenu', 'dir', 'draggable', 'dropzone', 'id',
            'lang', 'style', 'tabindex', 'title', 'inputmode', 'is', 'itemid', 'itemprop', 'itemref', 'itemscope',
            'itemtype', 'lang', 'slot', 'spellcheck', 'translate', 'autofocus', 'disabled', 'form', 'multiple', 'name',
            'required', 'size', 'autocapitalize', 'autocomplete', 'autofocus', 'cols', 'disabled', 'form', 'maxlength',
            'minlength', 'name', 'placeholder', 'readonly', 'required', 'rows', 'spellcheck', 'wrap', 'onmouseenter',
            'onmouseleave', 'onafterprint', 'onbeforeprint', 'onbeforeunload', 'onhashchange', 'onmessage', 'onoffline',
            'ononline', 'onpopstate', 'onpagehide', 'onpageshow', 'onresize', 'onunload', 'ondevicemotion', 'preload',
            'ondeviceorientation', 'onabort', 'onblur', 'oncanplay', 'oncanplaythrough', 'onchange', 'onclick',
            'oncontextmenu', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover',
            'ondragstart', 'ondrop', 'ondurationchange', 'onemptied', 'onended', 'onerror', 'onfocus', 'oninput',
            'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onload', 'onloadeddata', 'onloadedmetadata',
            'onloadstart', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'controls',
            'onmozfullscreenchange', 'onmozfullscreenerror', 'onpause', 'onplay', 'onplaying', 'onprogress',
            'onratechange', 'onreset', 'onscroll', 'onseeked', 'onseeking', 'onselect', 'onshow', 'onstalled',
            'onsubmit', 'onsuspend', 'ontimeupdate', 'onvolumechange', 'onwaiting', 'oncopy', 'oncut', 'onpaste',
            'onbeforescriptexecute', 'onafterscriptexecute',
        ];

        return in_array($attribute, $globalAttributes);
    }

}
