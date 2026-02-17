<?php

namespace LaravelSettings\Settings\Services\Drivers;

interface SettingsDriver
{
    public function get(string $key): mixed;

    public function all(): array;

    public function save(string $key, mixed $value): void;

    public function delete(string $key): void;

    public function exists(string $key): bool;
}