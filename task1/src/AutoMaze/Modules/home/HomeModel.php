<?php

declare(strict_types = 1);

namespace AutoMaze\Modules\home;

use Core\Model;

class HomeModel extends Model
{

    public function params(): array
    {
        return [
            'header'      => 'Automaze',
        ];
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
        return 'Home';
    }

}
