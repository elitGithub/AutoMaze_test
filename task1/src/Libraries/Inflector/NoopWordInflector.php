<?php

declare(strict_types=1);

namespace Libraries\Inflector;

use Libraries\Inflector\WordInflector;

class NoopWordInflector implements WordInflector
{
    public function inflect(string $word): string
    {
        return $word;
    }
}
