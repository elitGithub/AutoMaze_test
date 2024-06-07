<?php

declare(strict_types=1);

namespace Libraries\Inflector;

interface WordInflector
{
    public function inflect(string $word): string;
}
