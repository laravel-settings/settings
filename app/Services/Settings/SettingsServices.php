<?php

namespace LaravelSettings\Settings\Services\Settings;

use LaravelSettings\Settings\Models\Setting;
use LaravelSettings\Settings\Services\Drivers\SettingsDriver;
use ArrayAccess;
use RuntimeException;

class SettingsServices implements ArrayAccess
{
    private string $key;
    private mixed $value;
    private mixed $lang;
    private SettingsDriver $driver;

    public function __construct(?string $key, ?string $lang)
    {
        $this->driver = app(SettingsDriver::class);
        $this->key = $key;
        $this->lang = $lang ?? app()->getLocale();
        $this->loadValue();
    }

    public function save(array | string $data): void
    {
        $this->driver->save($this->key, $data);
        $this->value = $data;
    }

    public function create(array | string  $value): void
    {
        if (Setting::where('key', $this->key)->exists()) throw new RuntimeException("Setting [{$this->key}] already exists.");

        $this->save($value);
    }

    private function loadValue(): void 
    {
        $this->value = $this->driver->get($this->key);
    }

    public function get(): mixed
	{
        if (!is_array($this->value)) return [];
   
        return array_map(fn($item) => (object) collect($item)->mapWithKeys(
            fn($value, $key) => [$key => is_array($value) ? ($value[$this->lang] ?? reset($value)) : $value]
        )->all(), $this->value ?? []);
	}

    public function each(callable $callback): self
    {
        $items = is_array($this->value) ? $this->get() : [];

        foreach ($items as $key => $item) {
            
            $callback($item, $key);
        }

        return $this;
    }

    public function all(): mixed
    {
        return !empty($this->key) ? Setting::where('key', $this->key)?->pluck('value', 'key')->first() : Setting::pluck('value', 'key')->toArray();
    }
    
    public function delete(): void
    {
        $this->driver->delete($this->key);
    }

    private function hasDot(array $array, string $key): bool
    {
        $keys = explode('.', $key);

        foreach ($keys as $segment) {

            if (!is_array($array) || !array_key_exists($segment, $array)) return false;

            $array = $array[$segment];
        }

        return true;
    }

    public function has(string $key = ''): bool
    {
        if (!is_array($this->value)) return false;

        if (!array_is_list($this->value)) return $this->hasDot($this->value, $key);

        foreach ($this->value as $item) {

            if (is_array($item) && $this->hasDot($item, $key)) return true;
        }

        return false;
    }


    public function dd()
    {
        $item = Setting::where('key', $this->key)?->first()?->pluck('value', 'key')?->toArray();

        dd($item);
    }

    private function toObject(mixed $value): mixed
    {
        if (!is_array($value)) return $value;

        if (array_is_list($value)) return new SafeObject(array_map(fn ($item) => $this->toObject($item), $value));

        return new SafeObject(array_map(fn ($item) => $this->toObject($item), $value));
    }

    public function update(string $key, mixed $value): void
    {
        $data =& $this->value;

        foreach (explode('.', $key) as $segment) {
            $data[$segment] ??= [];
            $data =& $data[$segment];
        }

        $data = $value;

        $this->save($this->value);
    }

    public function sort(string $field, string $direction = 'asc'): void
    {
        if (!is_array($this->value) || !array_is_list($this->value)) return;

        usort($this->value, fn($first, $second) => $direction === 'asc'
            ? ($first[$field] <=> $second[$field])
            : ($second[$field] <=> $first[$field])
        );

        $this->save($this->value);
    }

    public function groupBy(string $key)
    {
        if (!is_array($this->value) || !array_is_list($this->value)) return;

        $group = [];

        foreach ($this->value as $item) {
            if (!array_key_exists($key, $item)) continue;
            $group[$item[$key]][] = $item;
        }

        return $group;
    }


    public function __get($property)
    {
        if (!is_array($this->value) || !array_key_exists($property, $this->value)) return null;

        $value = $this->value[$property];

        if (is_array($value) && array_key_exists($this->lang, $value)) return $value[$this->lang] ?? reset($value);

        return $this->toObject($value);
    }

    public function first(): mixed
    {
        $items = is_array($this->value) ? $this->get() : [];
        
        return empty($items) ? null : reset($items);
    }

    public function last(): ?object
    {
        $items = is_array($this->value) ? $this->get() : [];

        return empty($items) ? null : end($items);
    }

    public function search(string $keyword): array
    {
        if (!is_array($this->value)) return [];

        $data = count($this->value) === 1 && is_array(reset($this->value)) ? reset($this->value) : $this->value;

        return array_filter($data,
            fn ($item) => is_array($item) &&
                array_reduce( $item, fn ($carry, $v) =>
                        $carry || (is_string($v) && stripos($v, $keyword) !== false),
                    false
                )
        );
    }

    public function where(string $field, string $value, array $data = null): array 
    {
        $data ??= $this->value;
        $res = [];

        foreach ($data as $key => $item) {
            if (!is_array($item)) continue;
            if (isset($item[$field]) && stripos((string)$item[$field], $value) !== false) $res[$key] = $item;
            $res += $this->where($field, $value, $item);
        }

        return $res;
    }


    public function getValue(string $key): mixed
    {
        if (empty($key)) return $this->get();

        $parts = explode('.', $key);

        $value = collect($parts)->reduce(
            fn ($carry, $segment) =>
                is_array($carry) && array_key_exists($segment, $carry) ? $carry[$segment] : null,
            $this->value
        );

        if (!is_array($value)) return $value;

        $lang = $this->lang;

        if (array_is_list($value)) {
            return array_map(function ($item) use ($lang) {
                return (object) collect($item)->mapWithKeys(
                    fn ($v, $k) => [
                        $k => is_array($v) ? ($v[$lang] ?? reset($v)) : $v
                    ]
                )->all();
            }, $value);
        }

        if (array_key_exists($lang, $value)) return $value[$lang] ?? reset($value);

        return $value;
    }

    public function toArray(): mixed
    { 
        return $this->value ?? [];
    }

    public function offsetExists($offset): bool
    {
        return isset($this->value[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value): void {}
    
    public function offsetUnset($offset): void {}
}