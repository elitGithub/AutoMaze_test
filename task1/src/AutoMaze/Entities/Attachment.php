<?php

declare(strict_types = 1);

namespace JobPortal\Entities;

use ArrayAccess;
use Core\Entity;
use IteratorAggregate;
use Traversable;

class Attachment extends Entity implements IteratorAggregate, ArrayAccess
{
    public array  $columnFields = [
        'attachment_id' => 0,
        'name'            => '',
        'type'            => '',
        'path'            => '',
        'subject'         => '',
        'email_id'        => 0,
    ];
    protected string $table        = 'attachments';

    public function save() {}
}
