<?php

declare(strict_types=1);

namespace Libraries\Inflector;

use Doctrine\Inflector\Rules\English;
use Doctrine\Inflector\Rules\French;
use Doctrine\Inflector\Rules\NorwegianBokmal;
use Doctrine\Inflector\Rules\Portuguese;
use Doctrine\Inflector\Rules\Spanish;
use Doctrine\Inflector\Rules\Turkish;
use InvalidArgumentException;

use Libraries\Inflector\Language;
use Libraries\Inflector\LanguageInflectorFactory;

use function sprintf;

final class InflectorFactory
{
    public static function create(): LanguageInflectorFactory
    {
        return self::createForLanguage(Language::ENGLISH);
    }

    public static function createForLanguage(string $language): LanguageInflectorFactory
    {
        switch ($language) {
            case Language::ENGLISH:
                return new \Libraries\Inflector\Rules\English\InflectorFactory();

            case Language::FRENCH:
                return new \Libraries\Inflector\Rules\French\InflectorFactory();

            case Language::NORWEGIAN_BOKMAL:
                return new \Libraries\Inflector\Rules\NorwegianBokmal\InflectorFactory();

            case Language::PORTUGUESE:
                return new \Libraries\Inflector\Rules\Portuguese\InflectorFactory();

            case Language::SPANISH:
                return new \Libraries\Inflector\Rules\Spanish\InflectorFactory();

            case Language::TURKISH:
                return new \Libraries\Inflector\Rules\Turkish\InflectorFactory();

            default:
                throw new InvalidArgumentException(sprintf(
                    'Language "%s" is not supported.',
                    $language
                ));
        }
    }
}
