<?php

namespace LaravelSettings\Settings\Tests\Feature\Services\Settings;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Blade;
use LaravelSettings\Settings\Tests\TestCase;

class SettingsServicesTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_save_and_retrieve_logo_file()
    {
        Storage::fake('public');

        $oldFile = UploadedFile::fake()->image('old_logo.png');
        $firstPath = $oldFile->store('website', 'public');

        setting('website')->save(['logo_path' => $firstPath]);

        Storage::disk('public')->assertExists(setting('website')->logo_path);
        $this->assertEquals($firstPath, setting('website')->logo_path);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_save_and_retrieve_single_setting()
    {
        $items = [
            'name' => 'name value new',
            'email' => 'email value',
        ];

        setting('item_single')->save($items);

        $this->assertEquals('name value new', setting('item_single')->name);
        $this->assertEquals('email value', setting('item_single')->email);

        //blade
        $blade = "@setting('item_single', 'name')";
        $rendered = Blade::render($blade);

        $this->assertStringContainsString('name value new', $rendered);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_save_and_retrieve_single_language_setting()
    {
        $items = [
            'name' => ['ar' => 'name ar', 'en' => 'name en'],
            'email' => ['ar' => 'email ar', 'en' => 'email en'],
        ];

        setting('single_language')->save($items);

        $locale = app()->getLocale();

        $this->assertEquals($items['name'][$locale], setting('single_language')->name);
		$this->assertEquals($items['email'][$locale], setting('single_language')->email);

        //blade
        $blade = "@setting('single_language', 'name.ar')";
        $rendered = Blade::render($blade);

        $this->assertStringContainsString('name ar', $rendered);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_save_and_retrieve_single_multiple_items()
    {
        $items = [
            ['name' => 'name value 1', 'email' => 'email value 1'],
            ['name' => 'name value 2', 'email' => 'email value 2'],
        ];

        setting('single_multiple_items')->save($items);

        $firstItem = setting('single_multiple_items')->first();

        $this->assertEquals('name value 1', $firstItem->name);
        $this->assertEquals('email value 1', $firstItem->email);

        setting('single_multiple_items')->each(function ($item) {
            $this->assertEquals($item->name, $item->name);
        });

        $result = setting('single_multiple_items')->get();

        foreach ($result as $index => $value) {
            $this->assertEquals($items[$index]['name'], $value->name);
            $this->assertEquals($items[$index]['email'], $value->email);
        }

        //blade
        $blade = "@eachSetting('single_multiple_items as \$var')
                    <div>{{ \$var->name }} - {{ \$var->email }}</div>
                  @endEachSetting";

        $rendered = Blade::render($blade);

        $this->assertStringContainsString('name value 1', $rendered);
        $this->assertStringContainsString('email value 1', $rendered);

    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_save_and_retrieve_single_multiple_language_items()
    {
        $items = [
            [
                'name' => ['ar' => 'name value 1 ar', 'en' => 'name value 1 en'],
                'email' => ['ar' => 'email value 1 ar', 'en' => 'email value 1 en'],
            ],
            [
                'name' => ['ar' => 'name value 2 ar', 'en' => 'name value 2 en'],
                'email' => ['ar' => 'email value 2 ar', 'en' => 'email value 2 en'],
            ],
        ];

        setting('single_multiple_language_items')->save($items);

        $firstItemAr = setting('single_multiple_language_items', 'ar')->first();
        $firstItemEn = setting('single_multiple_language_items', 'en')->first();
        $firstItem = setting('single_multiple_language_items')->first();

        $this->assertEquals('name value 1 ar', $firstItemAr->name);
        $this->assertEquals('name value 1 en', $firstItemEn->name);
        $this->assertEquals('name value 1 en', $firstItem->name); // defult

        $result = setting('single_multiple_language_items')->get();

        setting('single_multiple_language_items', 'ar')->each(function ($item) {
            $this->assertEquals($item->name, $item->name); //name value //defult lang
        });

        setting('single_multiple_language_items', 'en')->each(function ($item) {
            $this->assertEquals($item->name, $item->name); //name value //defult lang
        });

        $locale = app()->getLocale();

        foreach ($result as $index => $value) {
            $this->assertEquals($items[$index]['name'][$locale], $value->name);
            $this->assertEquals($items[$index]['email'][$locale], $value->email);
        }

        //blade
        $blade = "@eachSetting('single_multiple_language_items as \$var')
                    <div>{{ \$var->name }} - {{ \$var->email }}</div>
                  @endEachSetting";

        $rendered = Blade::render($blade);

        $this->assertStringContainsString('name value 1 en', $rendered);
        $this->assertStringContainsString('email value 1 en', $rendered);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_save_and_retrieve_multiple_items()
    {
        $items = [
            ['name' => 'name 1 multiples', 'email' => 'email 1 multiples'],
            ['name' => 'name 2 multiples', 'email' => 'email 2 multiples'],
        ];

        setting('multiple_items')->save($items);

        $result = setting('multiple_items')->get();

        $firstItemFirst = setting('multiple_items')->first();
        $firstItemLast = setting('multiple_items')->last();

        $this->assertEquals('name 1 multiples', $firstItemFirst->name);
        $this->assertEquals('email 2 multiples', $firstItemLast->email);

        foreach ($result as $index => $value) {
            $this->assertEquals($items[$index]['name'], $value->name);
            $this->assertEquals($items[$index]['email'], $value->email);
        }

        setting('multiple_items')->each(function ($item, $index) use($items) {
            $this->assertEquals($items[$index]['name'], $item->name);
        });

        //blade
        $blade = "@eachSetting('multiple_items as \$var')
                    <div>{{ \$var->name }} - {{ \$var->email }}</div>
                  @endEachSetting";

        $rendered = Blade::render($blade);

        $this->assertStringContainsString('name 1 multiples', $rendered);
        $this->assertStringContainsString('email 1 multiples', $rendered);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_save_and_retrieve_multiple_language_items()
    {
        $items = [
            [
                'name' => ['ar' => 'name value 1 ar', 'en' => 'name value 1 en'],
                'email' => ['ar' => 'email value 1 ar', 'en' => 'email value 1 en'],
            ],
            [
                'name' => ['ar' => 'name value 2 ar', 'en' => 'name value 2 en'],
                'email' => ['ar' => 'email value 2 ar', 'en' => 'email value 2 en'],
            ],
        ];

        setting('multiple_language_items')->save($items);
        
        $result = setting('multiple_language_items')->get();
        $locale = app()->getLocale();
        
        $firstItemFirst = setting('multiple_language_items', $locale)->first();
        $firstItemLast = setting('multiple_language_items', $locale)->last();

        $this->assertEquals('name value 1 ' . $locale, $firstItemFirst->name);
        $this->assertEquals('email value 2 ' . $locale, $firstItemLast->email);

        foreach ($result as $index => $value) {
            $this->assertEquals($items[$index]['name'][$locale], $value->name);
            $this->assertEquals($items[$index]['email'][$locale], $value->email);
        }

        setting('multiple_language_items', $locale)->each(function ($item, $index) use($items, $locale) {
            $this->assertEquals($items[$index]['name'][$locale], $item->name);
        });
    }
}