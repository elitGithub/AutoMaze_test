<?php

declare(strict_types = 1);

namespace AutoMaze\Modules\api;

use Core\Storm;
use Core\Model;

class ApiModel extends Model
{

    public function params(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function rules(): array
    {
        // TODO: forms filled through ajax will come here, need to make sure we access the correct model and validate through that.
        return $this->rules;
    }

    public function getDisplayName(): string
    {
        return "api";
    }
}

