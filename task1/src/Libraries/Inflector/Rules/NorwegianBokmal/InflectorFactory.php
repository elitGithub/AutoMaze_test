<?php

declare(strict_types=1);

namespace Libraries\Inflector\Rules\NorwegianBokmal;

use Libraries\Inflector\GenericLanguageInflectorFactory;
use Libraries\Inflector\Rules\NorwegianBokmal\Rules;
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
