<?php

declare(strict_types = 1);

namespace Core;

use ArrayAccess;
use IteratorAggregate;
use Traversable;

abstract class Entity implements IteratorAggregate, ArrayAccess
{

    public int    $id;
    public string $name;
    protected string $table        = '';
    public array  $columnFields = [];

    abstract public function save();

    public function retrieveEntityInfo()
    {
        $query = "SELECT * FROM `$this->table`";
    }

    public function setColumnFields(array $columnFields): void
    {
        $this->columnFields = $columnFields;
    }

    public function getIterator(): \Traversable {
        return new \ArrayIterator($this->columnFields);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->columnFields[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->columnFields[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->columnFields[] = $value;
        } else {
            $this->columnFields[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->columnFields[$offset]);
    }
}
