<?php

declare(strict_types = 1);

namespace Core;


use Interfaces\UniqueRecord;

abstract class Model
{

    public const RULE_REQUIRED   = 'required';
    public const RULE_EMAIL      = 'email';
    public const RULE_MIN_LENGTH = 'min';
    public const RULE_MAX_LENGTH = 'max';
    public const RULE_MATCH      = 'match';
    public const RULE_UNIQUE     = 'unique';
    public const RULE_NUMBER     = 'number';

    public array    $errors     = [];
    public array    $params     = [];
    public array    $rules      = [];
    protected array $attributes = [];
    protected string $tableName = '';
    public Module $module;

    abstract public function params(): array;

    /**
     * @return array
     */
    abstract public function rules(): array;

    public function labels(): array
    {
        return [];
    }

    public function getLabel($attribute)
    {
        return $this->labels()[$attribute] ?? $attribute;
    }

    public function validateFormTokenForSubmission($formToken, string $tokenName): bool
    {
        if (empty($formToken)) {
            return false;
        }

        if ($formToken !== Storm::getStorm()->session->readKeyValue($tokenName)) {
            return false;
        }

        return true;
    }

    public function validate(): bool
    {
        $uniqueAttributes = [];
        foreach ($this->rules() as $attribute => $rules) {
            $value = $this->attributes[$attribute];
            foreach ($rules as $rule) {
                $ruleName = $rule;
                if (!is_string($ruleName)) {
                    $ruleName = $rule[0];
                }

                if ($ruleName === static::RULE_REQUIRED && !$value) {
                    $this->addErrorForRule($attribute, static::RULE_REQUIRED, $rule);
                }

                if ($ruleName === static::RULE_EMAIL && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addErrorForRule($attribute, static::RULE_EMAIL, $rule);
                }

                if ($ruleName === static::RULE_MIN_LENGTH && (mb_strlen($value) < $rule[static::RULE_MIN_LENGTH])) {
                    $this->addErrorForRule($attribute, static::RULE_MIN_LENGTH, $rule);
                }

                if ($ruleName === static::RULE_MAX_LENGTH && (mb_strlen($value) > $rule[static::RULE_MAX_LENGTH])) {
                    $this->addErrorForRule($attribute, static::RULE_MAX_LENGTH, $rule);
                }

                if ($ruleName === static::RULE_NUMBER && (!is_numeric($value))) {
                    $this->addErrorForRule($attribute, static::RULE_NUMBER, $rule);
                }

                if ($ruleName === static::RULE_MATCH) {
                    if (is_callable($rule['match'])) {
                        $isValid = call_user_func($rule['match'], $value, $this->attributes[$attribute]);
                        if (!$isValid) {
                            $this->addErrorForRule($attribute, static::RULE_MATCH, $rule);
                        }
                    } elseif ($value !== $this->attributes[$rule['match']]) {
                        $rule['match'] = $this->getLabel($rule['match']);
                        $this->addErrorForRule($attribute, static::RULE_MATCH, $rule);
                    }
                }

                if ($ruleName === static::RULE_UNIQUE && ($this instanceof UniqueRecord)) {
                    // Collect unique attributes and their values
                    $uniqueAttrs = $rule['attributes'] ?? [$attribute];
                    foreach ($uniqueAttrs as $uniqueAttributeName) {
                        if ($this->uniqueRecordExists($uniqueAttributeName, $value)) {
                            $this->addErrorForRule(
                                $uniqueAttributeName,
                                static::RULE_UNIQUE,
                                ['field' => $this->getLabel($uniqueAttributeName)]
                            );
                        }
                    }
                }
            }
        }

        return empty($this->errors);
    }

    public function setModule(Module $module): void
    {
        $this->module = $module;
    }


    /**
     * @param  string        $attribute
     * @param  string        $rule
     * @param  array|string  $params
     */
    public function addErrorForRule(string $attribute, string $rule, $params = [])
    {
        $message = $this->errorMessages()[$rule] ?? '';
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $message = str_replace('{' . $key . '}', $value, $message);
            }
        }
        $this->errors[$attribute][] = $message;
    }


    /**
     * @return string[]
     */
    public function errorMessages(): array
    {
        return [
            static::RULE_REQUIRED   => 'This field is required.',
            static::RULE_EMAIL      => 'This field must be a valid email address.',
            static::RULE_MIN_LENGTH => 'This field must be at least {min} characters long.',
            static::RULE_MAX_LENGTH => 'This field must be at most {max} characters long.',
            static::RULE_MATCH      => 'This field must be the same as {match}.',
            static::RULE_UNIQUE     => 'Record with this {field} already exists.',
            static::RULE_NUMBER     => 'This field must be a number.',
        ];
    }

    public function loadAttributes()
    {
        $result = Storm::getStorm()->db->query("DESCRIBE `$this->tableName`");
        if (!$result) {
            throw new \Exception('Error in query');
        }

        while ($row = Storm::getStorm()->db->fetchByAssoc($result)) {
            $this->attributes[$row['field']] = $row['type'];
        }
    }

    abstract public function getDisplayName(): string;
}
