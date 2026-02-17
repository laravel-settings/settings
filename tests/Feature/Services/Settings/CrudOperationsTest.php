<?php

use LaravelSettings\Settings\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

//uses(TestCase::class, RefreshDatabase::class);

test('settings create() creates new record', function () {

    $items = [
        'name' => 'name value',
    ];

    setting('items_create')->create($items);

    expect(setting('items_create')->get())->toBeArray();
    expect(setting('items_create')->name)->toBe('name value');
});

test('can update nested value using safe object update', function () {
    
    $items = [
        'roles' => [
            'owner' => [
                'title' => 'Title One',
                'users' => ['create', 'update'],
            ],
        ],
    ];

    setting('update_items')->save($items);
    
    setting('update_items')->update('roles.owner.title', 'New title'); 

    expect(setting('update_items')->roles->owner->title)->toBe('New title');

    setting('update_items')->update('roles.owner.users', ['show', 'edit']);
    
    expect(setting('update_items')->roles->owner->users->toArray())->toBe(['show', 'edit']);

});

test('can sort a list of items by a field', function () {

    $items = [
        ['title' => 'C Title', 'users' => 'user3'],
        ['title' => 'A Title', 'users' => 'user1'],
        ['title' => 'B Title', 'users' => 'user2'],
    ];

    setting('sort_items')->save($items);

    setting('sort_items')->sort('title', 'asc');

    $sortedAsc = setting('sort_items')->toArray();
    expect(array_column($sortedAsc, 'title'))->toBe(['A Title', 'B Title', 'C Title']);

    setting('sort_items')->sort('title', 'desc');

    $sortedDesc = setting('sort_items')->toArray();
    expect(array_column($sortedDesc, 'title'))->toBe(['C Title', 'B Title', 'A Title']);

});

test('can group items by key', function () {

    $items = [
        ['room_id' => 1, 'day' => 'Monday', 'time' => '09:00'],
        ['room_id' => 2, 'day' => 'Monday', 'time' => '09:00'],
        ['room_id' => 1, 'day' => 'Tuesday', 'time' => '10:00'],
        ['room_id' => 2, 'day' => 'Tuesday', 'time' => '11:00'],
    ];

    setting('group_items')->save($items);

    $groups = setting('group_items')->groupBy('room_id');

    expect($groups)->toHaveKeys([1, 2]);

    expect($groups[1])->toHaveCount(2);
    expect($groups[2])->toHaveCount(2);

});

test('settings delete() works correctly', function () {

    $items = [
        'name'  => 'name value',
        'email' => 'email value',
    ];

    setting('items_delete')->save($items);

    expect(setting('items_delete')->get())->toBeArray();
    expect(setting('items_delete')->name)->toBe('name value');

    setting('items_delete')->delete();

    expect(setting('items_delete')->get())->toBe([]);
    expect(setting('items_delete')->name ?? null)->toBeNull();

});

test('can search nested value and return full array', function () {

    $items = [
        'roles' => [
            'owner_one' => [
                'title' => 'Title One',
                'des'   => 'des one',
            ],
            'owner_two' => [
                'title' => 'Title Tow',
                'des'   => 'des Tow',
            ],
            'owner_three' => [
                'title' => 'Title three',
                'des' => 'des Tow',
            ],
        ],
    ];

    setting('search_items')->save($items);
    $result = setting('search_items')->search('ow');

    expect($result)->toHaveKeys(['owner_two', 'owner_three']);
    expect($result['owner_two']['title'])->toBe('Title Tow');
});

test('can where nested value and return full array', function () {

    $items = [ 
        'roles_one' => [ 
            'owner_one' => ['admin' => 'Ahemd', 'title' => 'Title One'],
            'owner_two' => ['admin' => 'Ali', 'title' => 'Title Tow'],
            'owner_three' => ['admin' => 'Ahemd', 'title' => 'Title Three'],
        ],

        'roles_tow' => [ 
            'owner_one' => ['owner' => 'Ahemd', 'title' => 'Title One'],
            'owner_three' => ['owner' => 'Ahemd', 'title' => 'Title Three'],
            'owner_two' => ['owner' => 'Ali', 'title' => 'Title Tow'],
        ],
    ];

    setting('where_items')->save($items);

    $result = setting('where_items')->where('admin', 'Ahemd');

    expect($result)->toHaveKeys(['owner_one', 'owner_three']);
    expect($result['owner_one']['title'])->toBe('Title One');
});