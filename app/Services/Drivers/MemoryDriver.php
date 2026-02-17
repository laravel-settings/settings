<?php

namespace LaravelSettings\Settings\Services\Drivers;

class MemoryDriver implements SettingsDriver
{
    protected array $data = [];

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function save(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function delete(string $key): void
    {
        unset($this->data[$key]);
    }

    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }
}