<?php

namespace AutoMaze\Modules\Admin;

use Core\Model;

class AdminModel extends Model
{

    public function params(): array
    {
        return $this->params;
    }

    /**
     * @inheritDoc
     */
    public function rules(): array
    {
        return $this->rules;
    }

    public function getDisplayName(): string
    {
        return 'Administration';
    }
}