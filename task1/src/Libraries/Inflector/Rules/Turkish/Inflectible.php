<?php

declare(strict_types=1);

namespace Libraries\Inflector\Rules\Turkish;

use Libraries\Inflector\Rules\Pattern;
use Libraries\Inflector\Rules\Substitution;
use Libraries\Inflector\Rules\Transformation;
use Libraries\Inflector\Rules\Word;

class Inflectible
{
    /** @return Transformation[] */
    public static function getSingular(): iterable
    {
        yield new Transformation(new Pattern('/l[ae]r$/i'), '');
    }

    /** @return \Libraries\Inflector\Rules\Transformation[] */
    public static function getPlural(): iterable
    {
        yield new Transformation(new Pattern('/([eöiü][^aoıueöiü]{0,6})$/u'), '\1ler');
        yield new Transformation(new Pattern('/([aoıu][^aoıueöiü]{0,6})$/u'), '\1lar');
    }

    /** @return \Libraries\Inflector\Rules\Substitution[] */
    public static function getIrregular(): iterable
    {
        yield new Substitution(new Word('ben'), new Word('biz'));
        yield new Substitution(new Word('sen'), new Word('siz'));
        yield new Substitution(new Word('o'), new Word('onlar'));
    }
}
