<?php

declare(strict_types = 1);

namespace JobPortal\Entities;

use ArrayAccess;
use Core\Entity;
use IteratorAggregate;
use Traversable;

class Email extends Entity implements IteratorAggregate, ArrayAccess
{
    protected const STATUS_SENT   = 'SENT';
    protected const STATUS_SAVED  = 'SAVED';
    protected const STATUS_FAILED = 'FAILED';
    protected string $table        = 'emails';
    public array  $columnFields = [
        'email_id'    => 0,
        'from_email'  => '',
        'to_emails'   => '',
        'cc_emails'   => '',
        'bcc_emails'  => '',
        'email_flag'  => self::STATUS_SAVED,
        'template_id' => null,
        'email_body'  => '',
    ];

    public function save() {

    }


}
