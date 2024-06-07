<?php

declare(strict_types = 1);

namespace Helpers;

class ArrayManipulator
{

    public static function flatten($input, $output = null)
    {
        if (empty($input)) {
            return $input;
        }
        if (empty($output)) {
            $output = [];
        }
        foreach ($input as $value) {
            if (is_array($value)) {
                $output = static::flatten($value, $output);
            } else {
                $output[] = $value;
            }
        }
        return $output;
    }

}
