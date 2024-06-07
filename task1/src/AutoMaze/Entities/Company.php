<?php

declare(strict_types = 1);

namespace JobPortal\Entities;

use Core\Entity;

class Company extends Entity
{
    public const STATE_APPROVED = 1;
    public const STATE_PENDING = 2;
    public const STATE_REJECTED = 3;

    public function save()
    {
        // TODO: Implement save() method.
    }
}
