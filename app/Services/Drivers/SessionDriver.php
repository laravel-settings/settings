<?php

namespace LaravelSettings\Settings\Services\Drivers;

use LaravelSettings\Settings\Services\Drivers\SettingsDriver;

class SessionDriver implements SettingsDriver 
{
    protected function key(string $key): string
    {
        $prefix = config('settings.session.prefix', '');
        return $prefix . $key;
    }

    public function get(string $key): mixed 
    { 
        return session($this->key($key)); 
    } 

    public function all(): array 
    { 
        $prefix = config('settings.session.prefix', '');

        return collect(session()->all())
            ->filter(fn ($value, $k) => str_starts_with($k, $prefix))
            ->mapWithKeys(fn ($value, $k) => [
                str_replace($prefix, '', $k) => $value
            ])->toArray();
    } 

    public function save(string $key, $value): void 
    { 
        session([$this->key($key) => $value]); 
    } 

    public function delete(string $key): void 
    { 
        session()->forget($this->key($key)); 
    } 

    public function exists(string $key): bool 
    { 
        return session()->has($this->key($key)); 
    } 
}