<?php

declare(strict_types=1);

namespace Libraries\Inflector\Rules\NorwegianBokmal;

use Libraries\Inflector\Rules\Pattern;

final class Uninflected
{
    /** @return \Libraries\Inflector\Rules\Pattern[] */
    public static function getSingular(): iterable
    {
        yield from self::getDefault();
    }

    /** @return \Libraries\Inflector\Rules\Pattern[] */
    public static function getPlural(): iterable
    {
        yield from self::getDefault();
    }

    /** @return \Libraries\Inflector\Rules\Pattern[] */
    private static function getDefault(): iterable
    {
        yield new Pattern('barn');
        yield new Pattern('fjell');
        yield new Pattern('hus');
    }
}
