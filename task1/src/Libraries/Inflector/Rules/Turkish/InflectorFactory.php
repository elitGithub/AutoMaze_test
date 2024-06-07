<?php

declare(strict_types=1);

namespace Libraries\Inflector\Rules\Turkish;

use Libraries\Inflector\GenericLanguageInflectorFactory;
use Libraries\Inflector\Rules\Ruleset;
use Libraries\Inflector\Rules\Turkish\Rules;

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
