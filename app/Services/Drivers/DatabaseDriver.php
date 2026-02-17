<?php

namespace LaravelSettings\Settings\Services\Drivers;

use LaravelSettings\Settings\Services\Drivers\SettingsDriver;
use LaravelSettings\Settings\Models\Setting;

class DatabaseDriver implements SettingsDriver
{
    public function get(string $key): mixed
    {
        return Setting::where('key', $key)->value('value');
    }

    public function all(): array
    {
        return Setting::pluck('value', 'key')->toArray();
    }

    public function save(string $key, mixed $value): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => json_encode($value)]
        );
    }

    public function delete(string $key): void
    {
        Setting::where('key', $key)->delete();
    }

    public function exists(string $key): bool
    {
        return Setting::where('key', $key)->exists();
    }
}