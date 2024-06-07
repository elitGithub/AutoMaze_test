<?php

declare(strict_types = 1);

namespace AutoMaze\Modules\registration;

use Core\Storm;
use Core\Model;

class RegistrationModel extends Model
{
    public function params(): array
    {
        return [
            'header'      => 'Automaze',
        ];
    }

    public function rules(): array
    {
        return $this->rules;
    }

    public function getDisplayName(): string
    {
        return 'Registration';
    }

}
