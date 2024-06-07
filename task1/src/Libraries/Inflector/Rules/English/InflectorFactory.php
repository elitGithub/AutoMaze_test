<?php

declare(strict_types=1);

namespace Libraries\Inflector\Rules\English;

use Libraries\Inflector\GenericLanguageInflectorFactory;
use Libraries\Inflector\Rules\English\Rules;
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
