<?php

declare(strict_types=1);

namespace Libraries\Inflector\Rules\Portuguese;

use Libraries\Inflector\GenericLanguageInflectorFactory;
use Libraries\Inflector\Rules\Portuguese\Rules;
use Libraries\Inflector\Rules\Ruleset;

final class InflectorFactory extends GenericLanguageInflectorFactory
{
    protected function getSingularRuleset(): Ruleset
    {
        return Rules::getSingularRuleset();
    }

    protected function getPluralRuleset(): Ruleset
    {
        return Rules::getPluralRuleset();
    }
}
