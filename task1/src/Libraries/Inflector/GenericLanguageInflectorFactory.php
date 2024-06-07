<?php

declare(strict_types=1);

namespace Libraries\Inflector;

use Libraries\Inflector\Rules\Ruleset;

use Libraries\Inflector\CachedWordInflector;
use Libraries\Inflector\Inflector;
use Libraries\Inflector\LanguageInflectorFactory;
use Libraries\Inflector\RulesetInflector;

use function array_unshift;

abstract class GenericLanguageInflectorFactory implements LanguageInflectorFactory
{
    /** @var \Libraries\Inflector\Rules\Ruleset[] */
    private $singularRulesets = [];

    /** @var \Libraries\Inflector\Rules\Ruleset[] */
    private $pluralRulesets = [];

    final public function __construct()
    {
        $this->singularRulesets[] = $this->getSingularRuleset();
        $this->pluralRulesets[]   = $this->getPluralRuleset();
    }

    final public function build(): Inflector
    {
        return new Inflector(
            new CachedWordInflector(new RulesetInflector(
                ...$this->singularRulesets
            )),
            new CachedWordInflector(new RulesetInflector(
                ...$this->pluralRulesets
            ))
        );
    }

    final public function withSingularRules(?Ruleset $singularRules, bool $reset = false): LanguageInflectorFactory
    {
        if ($reset) {
            $this->singularRulesets = [];
        }

        if ($singularRules instanceof Ruleset) {
            array_unshift($this->singularRulesets, $singularRules);
        }

        return $this;
    }

    final public function withPluralRules(?Ruleset $pluralRules, bool $reset = false): LanguageInflectorFactory
    {
        if ($reset) {
            $this->pluralRulesets = [];
        }

        if ($pluralRules instanceof Ruleset) {
            array_unshift($this->pluralRulesets, $pluralRules);
        }

        return $this;
    }

    abstract protected function getSingularRuleset(): Ruleset;

    abstract protected function getPluralRuleset(): Ruleset;
}
