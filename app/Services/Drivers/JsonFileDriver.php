<?php

namespace LaravelSettings\Settings\Services\Drivers;

use LaravelSettings\Settings\Services\Drivers\SettingsDriver;
use Illuminate\Support\Facades\File;

class JsonFileDriver implements SettingsDriver
{
    protected string $path;

    public function __construct()
    {
        $this->path = config('settings.file.path');
		
        if (!File::exists($this->path)) {
            File::put($this->path, json_encode([]));
        }
    }

    protected function read(): array
    {
        return json_decode(File::get($this->path), true);
    }

    protected function write(array $data): void
    {
        File::put($this->path, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function get(string $key): mixed
    {
        return $this->read()[$key] ?? null;
    }

    public function all(): array
    {
        return $this->read();
    }

    public function save(string $key, mixed $value): void
    {
        $data = $this->read();
        $data[$key] = $value;
        $this->write($data);
    }

    public function delete(string $key): void
    {
        $data = $this->read();
        unset($data[$key]);
        $this->write($data);
    }

    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->read());
    }
}