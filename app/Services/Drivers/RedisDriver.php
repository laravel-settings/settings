<?php

namespace LaravelSettings\Settings\Services\Drivers;

use Illuminate\Support\Facades\Redis;

class RedisDriver implements SettingsDriver
{
    protected string $prefix;
    protected int $ttl;

    public function __construct()
    {
        $this->prefix = config('settings.redis.prefix', 'settings:');
        $this->ttl = config('settings.redis.ttl', 0);
    }

    public function get(string $key): mixed
    {
        $value = Redis::get($this->prefix . $key);

        return $value !== null
            ? json_decode($value, true)
            : null;
    }

    public function all(): array
    {
        $keys = Redis::keys($this->prefix . '*');
        $data = [];

        foreach ($keys as $key) {
            $cleanKey = str_replace($this->prefix, '', $key);
            $data[$cleanKey] = json_decode(Redis::get($key), true);
        }

        return $data;
    }

    public function save(string $key, mixed $value): void
    {
        $encoded = json_encode($value);

        if ($this->ttl > 0) {
            Redis::setex($this->prefix . $key, $this->ttl, $encoded);
        } else {
            Redis::set($this->prefix . $key, $encoded);
        }
    }

    public function delete(string $key): void
    {
        Redis::del($this->prefix . $key);
    }

    public function exists(string $key): bool
    {
        return (bool) Redis::exists($this->prefix . $key);
    }
}