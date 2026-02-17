<?php

namespace LaravelSettings\Settings\Services\Drivers;

use Illuminate\Support\Facades\Cache;

class CacheDriver implements SettingsDriver
{
    protected string $prefix;
    protected int $ttl;

    public function __construct()
    {
        $this->prefix = config('settings.cache.prefix', 'settings.');
        $this->ttl = config('settings.cache.ttl', 3600);
    }

    public function get(string $key): mixed
    {
        return Cache::get($this->prefix . $key);
    }

    public function all(): array
    {
        return []; 
    }

    public function save(string $key, mixed $value): void
    {
        if ($this->ttl > 0) {
            Cache::put($this->prefix . $key, $value, $this->ttl);
        } else {
            Cache::forever($this->prefix . $key, $value);
        }
    }

    public function delete(string $key): void
    {
        Cache::forget($this->prefix . $key);
    }

    public function exists(string $key): bool
    {
        return Cache::has($this->prefix . $key);
    }
}