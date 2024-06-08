<?php

namespace AutoMaze\Modules\Admin;

use Core\Model;

class AdminModel extends Model
{

    public array $params = [
        'githubLogin' => 'https://github.com/login/oauth/authorize?client_id=%s',
    ];
    public function params(): array
    {
        global $gitClientId;
        $this->params['githubLogin'] = sprintf($this->params['githubLogin'], $gitClientId);
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