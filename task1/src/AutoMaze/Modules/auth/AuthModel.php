<?php

declare(strict_types = 1);

namespace AutoMaze\Modules\auth;

use Core\Model;

class AuthModel extends Model
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
        return 'Auth';
    }
}
