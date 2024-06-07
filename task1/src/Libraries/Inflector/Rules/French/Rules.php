<?php

declare(strict_types=1);

namespace Libraries\Inflector\Rules\French;

use Libraries\Inflector\Rules\Patterns;
use Libraries\Inflector\Rules\Ruleset;
use Libraries\Inflector\Rules\Substitutions;
use Libraries\Inflector\Rules\Transformations;
use Libraries\Inflector\Rules\French\Inflectible;
use Libraries\Inflector\Rules\French\Uninflected;

final class Rules
{
    public static function getSingularRuleset(): Ruleset
    {
        return new Ruleset(
            new Transformations(...Inflectible::getSingular()),
            new Patterns(...Uninflected::getSingular()),
            (new Substitutions(...Inflectible::getIrregular()))->getFlippedSubstitutions()
        );
    }

    public static function getPluralRuleset(): Ruleset
    {
        return new Ruleset(
            new Transformations(...Inflectible::getPlural()),
            new Patterns(...Uninflected::getPlural()),
            new Substitutions(...Inflectible::getIrregular())
        );
    }
}
