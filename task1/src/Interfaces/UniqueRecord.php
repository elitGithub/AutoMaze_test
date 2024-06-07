<?php

declare(strict_types = 1);

namespace Interfaces;

interface UniqueRecord
{
    public function uniqueRecordExists($uniqueAttributeName, $uniqueAttributeValue): bool;
}
