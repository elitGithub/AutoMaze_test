<?php

declare(strict_types = 1);

namespace JobPortal\Entities;

use Core\Entity;

class JobApplicant extends Entity
{
    public const STATE_ACTIVE = 1;
    public const STATE_INACTIVE = 0;

    public function save()
    {
        // TODO: Implement save() method.
    }
}
