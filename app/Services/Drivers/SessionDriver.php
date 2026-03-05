<?php

namespace LaravelSettings\Settings\Services\Drivers;

use LaravelSettings\Settings\Services\Drivers\SettingsDriver;

class SessionDriver implements SettingsDriver
{
    public function get(string $key): mixed
    {
        return session($key);
    }

    public function all(): array
    {
        return session()->all();
    }

    public function save(string $key, $value): void
    {
        session([$key => $value]);
    }

    public function delete(string $key): void
    {
        session()->forget($key);
    }

    public function exists(string $key): bool
    {
        return session()->has($key);
    }
}