<?php

namespace LaravelSettings\Settings\Services\Settings;

class SafeObject
{
    public function __construct(
        private mixed $value
    ) {}

    public function __get(string $key): mixed
    {
        if (!is_array($this->value) || !array_key_exists($key, $this->value)) return null;

        return $this->wrap($this->value[$key]);
    }

    public function each(callable $callback): ?self
    {
        if (!is_array($this->value) || !array_is_list($this->value)) return null;

        foreach ($this->value as $key => $item) {
            $callback($item, $key);
        }

        return $this;
    }

    public function map(callable $callback): mixed
    {
        if (!is_array($this->value) || !array_is_list($this->value)) return null;

        return array_map($callback, $this->value);
    }

    public function toArray(): mixed
    {
        return $this->value;
    }

    private function wrap(mixed $value): mixed
    {
        if (!is_array($value)) return $value;

        if (array_is_list($value)) return new self(array_map(fn ($v) => $this->wrap($v), $value));

        return new self(
            array_map(fn ($v) => $this->wrap($v), $value)
        );
    }
}
