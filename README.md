# Laravel Settings

A flexible and powerful settings management package for Laravel applications. Store and retrieve settings using an Eloquent-like syntax with support for multiple storage drivers and multilingual values.

---

## Features

- Simple, expressive Eloquent-like syntax (`where`, `groupBy`, `sort`, `search`, ...)
- Supports multiple data types: arrays, strings, nested objects
- Supports multiple storage drivers: `database`, `file`, `redis`, `cache`
- Multilingual / localization support
- Blade directives for easy use in views
- CRUD operations: `save`, `create`, `update`, `delete`

---

## Requirements

- PHP >= 8.1
- Laravel >= 10.x

---

## Installation

Install the package via Composer:

```bash
composer require laravel-settings/settings
```

### Publish Migrations

```bash
php artisan vendor:publish --provider="LaravelSettings\Settings\Providers\SettingsServiceProvider" --tag="settings-migrations"
```

### Publish Config

```bash
php artisan vendor:publish --provider="LaravelSettings\Settings\Providers\SettingsServiceProvider" --tag="settings-config"
```

### Run Migrations

```bash
php artisan migrate
```

---

## Configuration

After publishing, open `config/settings.php` to choose your preferred driver:

```php
'driver' => env('SETTINGS_DRIVER', 'database'),
```

Supported drivers: `database`, `file`, `redis`, `cache`

---

## Basic Usage

### Save Settings

```php
setting('website')->save([
    'name'  => 'My Website',
    'email' => 'admin@example.com',
]);
```

### Retrieve Settings

```php
echo setting('website')->name;   // My Website
echo setting('website')->email;  // admin@example.com
```

### Create

```php
setting('website')->create(['name' => 'My Website']);
```

### Update a Specific Field

```php
setting('website')->update('name', 'New Name');

echo setting('website')->name; // New Name
```

### Delete

```php
setting('website')->delete();

setting('website')->name; // null
```

---

## Nested Data

Store and access deeply nested values using dot notation:

```php
setting('permissions')->save([
    'roles' => [
        'owner' => [
            'title' => 'Administrator',
            'users' => ['create', 'update'],
        ],
    ],
]);

// Access nested values as objects
echo setting('permissions')->roles->owner->title; // Administrator

// Update a deeply nested value using dot notation
setting('permissions')->update('roles.owner.title', 'Super Admin');

echo setting('permissions')->roles->owner->title; // Super Admin

// Update a nested array
setting('permissions')->update('roles.owner.users', ['show', 'edit']);

setting('permissions')->roles->owner->users->toArray(); // ['show', 'edit']
```

---

## Working with Lists

Store and manipulate a list of items:

```php
setting('products')->save([
    ['title' => 'C Title', 'price' => 30],
    ['title' => 'A Title', 'price' => 10],
    ['title' => 'B Title', 'price' => 20],
]);

// Get the first and last item
setting('products')->first()->title; // C Title
setting('products')->last()->title;  // B Title

// Iterate over items
setting('products')->each(function ($item, $index) {
    echo $item->title;
});

// Get all as array
setting('products')->get();
setting('products')->toArray();
```

---

## Eloquent-like Methods

### sort()

Sort items by any field, ascending or descending:

```php
setting('products')->sort('title', 'asc');
setting('products')->toArray();
// titles: ['A Title', 'B Title', 'C Title']

setting('products')->sort('title', 'desc');
setting('products')->toArray();
// titles: ['C Title', 'B Title', 'A Title']
```

### groupBy()

Group items by a shared key:

```php
setting('schedule')->save([
    ['room_id' => 1, 'day' => 'Monday',  'time' => '09:00'],
    ['room_id' => 2, 'day' => 'Monday',  'time' => '09:00'],
    ['room_id' => 1, 'day' => 'Tuesday', 'time' => '10:00'],
    ['room_id' => 2, 'day' => 'Tuesday', 'time' => '11:00'],
]);

$groups = setting('schedule')->groupBy('room_id');

count($groups[1]); // 2  (Monday + Tuesday for room 1)
count($groups[2]); // 2  (Monday + Tuesday for room 2)
```

### where()

Filter nested items by a key/value pair:

```php
setting('staff')->save([
    'roles' => [
        'owner_one'   => ['admin' => 'Ahmed', 'title' => 'Title One'],
        'owner_two'   => ['admin' => 'Ali',   'title' => 'Title Two'],
        'owner_three' => ['admin' => 'Ahmed', 'title' => 'Title Three'],
    ],
]);

$result = setting('staff')->where('admin', 'Ahmed');

// Returns only items where admin === 'Ahmed'
// { owner_one: {...}, owner_three: {...} }

echo $result->owner_one->title;   // Title One
echo $result->owner_three->title; // Title Three
```

### search()

Search nested keys by a partial string match:

```php
setting('roles')->save([
    'roles' => [
        'owner_one'   => ['title' => 'Title One',   'des' => 'des one'],
        'owner_two'   => ['title' => 'Title Two',   'des' => 'des two'],
        'owner_three' => ['title' => 'Title Three', 'des' => 'des three'],
        'editor'      => ['title' => 'Editor',      'des' => 'can edit'],
    ],
]);

$result = setting('roles')->search('owner');

// Returns all keys that contain 'owner'
// { owner_one: {...}, owner_two: {...}, owner_three: {...} }

echo $result->owner_two->title; // Title Two
```

---

## Multilingual Support

Store values in multiple locales and retrieve them by locale:

```php
setting('website')->save([
    'name'  => ['ar' => 'اسم الموقع', 'en' => 'Website Name'],
    'email' => ['ar' => 'البريد',     'en' => 'Email'],
]);

// Access a specific locale directly
echo setting('website')->name->ar; // اسم الموقع
echo setting('website')->name->en; // Website Name
```

Pass a locale as the second argument to resolve values automatically:

```php
setting('website', 'ar')->first()->name; // اسم الموقع
setting('website', 'en')->first()->name; // Website Name
```

Works the same with lists of items:

```php
setting('posts')->save([
    [
        'name'  => ['ar' => 'مرحبا', 'en' => 'Hello'],
        'email' => ['ar' => 'البريد', 'en' => 'Email'],
    ],
]);

$firstAr = setting('posts', 'ar')->first();
echo $firstAr->name; // مرحبا

$firstEn = setting('posts', 'en')->first();
echo $firstEn->name; // Hello
```

---

## Blade Directives

### @setting

Output a single field value:

```blade
{{-- Simple field --}}
@setting('website', 'name')

{{-- Nested field using dot notation --}}
@setting('website', 'roles.owner.title')

{{-- Localized field --}}
@setting('website', 'name.ar')
```

### @eachSetting / @endEachSetting

Loop over a list of settings. The default variable is `$item` if no alias is provided:

```blade
{{-- Default variable: $item --}}
@eachSetting('products')
    <div>{{ $item->name }} - {{ $item->email }}</div>
@endEachSetting

{{-- Custom variable alias --}}
@eachSetting('products' as $product)
    <div>{{ $product->name }} - {{ $product->email }}</div>
@endEachSetting

{{-- Loop over a nested key using dot notation --}}
@eachSetting('language_items.admins' as $admin)
    <div>{{ $admin->name }} - {{ $admin->email->ar }}</div>
@endEachSetting
```

---

## File / Image Storage

```php
// Store uploaded file and save its path
$path = $request->file('logo')->store('website', 'public');

setting('website')->save(['logo_path' => $path]);

// Retrieve the stored path
echo setting('website')->logo_path; // website/logo.png
```

---

## Available Drivers

| Driver     | Description                                   |
|------------|-----------------------------------------------|
| `database` | Stores settings in a database table (default) |
| `file`     | Stores settings as JSON files                 |
| `redis`    | Stores settings in Redis                      |
| `cache`    | Stores settings using Laravel Cache           |

---

## License

This package is open-sourced software licensed under the MIT license.