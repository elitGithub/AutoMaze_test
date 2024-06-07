<?php

declare(strict_types=1);

namespace Libraries\Inflector\Rules\NorwegianBokmal;

use Libraries\Inflector\Rules\Pattern;
use Libraries\Inflector\Rules\Substitution;
use Libraries\Inflector\Rules\Transformation;
use Libraries\Inflector\Rules\Word;

class Inflectible
{
    /** @return \Libraries\Inflector\Rules\Transformation[] */
    public static function getSingular(): iterable
    {
        yield new Transformation(new Pattern('/re$/i'), 'r');
        yield new Transformation(new Pattern('/er$/i'), '');
    }

    /** @return \Libraries\Inflector\Rules\Transformation[] */
    public static function getPlural(): iterable
    {
        yield new Transformation(new Pattern('/e$/i'), 'er');
        yield new Transformation(new Pattern('/r$/i'), 're');
        yield new Transformation(new Pattern('/$/'), 'er');
    }

    /** @return Substitution[] */
    public static function getIrregular(): iterable
    {
        yield new Substitution(new Word('konto'), new Word('konti'));
    }
}
