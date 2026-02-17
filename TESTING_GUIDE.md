# دليل الاختبارات - Laravel Settings Package

## نظرة عامة على الاختبارات

هناك ملفا اختبار رئيسيان:
1. **CrudOperationsTest.php** - الاختبارات الأساسية (CRUD operations)
2. **SettingsServicesTest.php** - اختبارات الخدمات المتقدمة

---

## 1. CRUD Operations Tests

### test_create() 
```php
test('settings create() creates new record', function () {
    $items = ['name' => 'name value'];
    setting('items_create')->create($items);
    
    expect(setting('items_create')->get())->toBeArray();
    expect(setting('items_create')->name)->toBe('name value');
});
```
**يختبر**: إنشاء إعداد جديد مع عدم السماح بالتكرار

---

### test_update()
```php
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
});
```
**يختبر**: تحديث القيم المتداخلة باستخدام dot notation (roles.owner.title)

**الميزات**:
- الوصول للقيم المتداخلة باستخدام `->` (SafeObject)
- تحديث القيم العميقة بدون فقدان باقي البيانات
- دعم Arrays والكائنات

---

### test_sort()
```php
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
});
```
**يختبر**: ترتيب Arrays حسب حقل معين (تصاعدي أو تنازلي)

---

### test_groupBy()
```php
test('can group items by key', function () {
    $items = [
        ['room_id' => 1, 'day' => 'Monday', 'time' => '09:00'],
        ['room_id' => 2, 'day' => 'Monday', 'time' => '09:00'],
        ['room_id' => 1, 'day' => 'Tuesday', 'time' => '10:00'],
    ];

    setting('group_items')->save($items);
    $groups = setting('group_items')->groupBy('room_id');
    
    expect($groups)->toHaveKeys([1, 2]);
    expect($groups[1])->toHaveCount(2);
});
```
**يختبر**: تجميع العناصر حسب قيمة معينة

---

### test_delete()
```php
test('settings delete() works correctly', function () {
    setting('items_delete')->save($items);
    expect(setting('items_delete')->get())->toBeArray();
    
    setting('items_delete')->delete();
    expect(setting('items_delete')->get())->toBe([]);
});
```
**يختبر**: حذف الإعدادات وإرجاع array فارغ

---

### test_search()
```php
test('can search nested value and return full array', function () {
    $items = [
        'roles' => [
            'owner_one' => ['title' => 'Title One'],
            'owner_two' => ['title' => 'Title Tow'],
            'owner_three' => ['title' => 'Title three'],
        ],
    ];

    setting('search_items')->save($items);
    $result = setting('search_items')->search('ow');
    
    expect($result)->toHaveKeys(['owner_two', 'owner_three']);
});
```
**يختبر**: البحث عن كلمة مفتاحية ضمن البيانات المتداخلة

---

### test_where()
```php
test('can where nested value and return full array', function () {
    $items = [ 
        'roles_one' => [ 
            'owner_one' => ['admin' => 'Ahemd', 'title' => 'Title One'],
            'owner_two' => ['admin' => 'Ali', 'title' => 'Title Tow'],
        ],
    ];

    setting('where_items')->save($items);
    $result = setting('where_items')->where('admin', 'Ahemd');
    
    expect($result)->toHaveKeys(['owner_one']);
});
```
**يختبر**: البحث عن سجلات بقيمة معينة في حقل معين

---

## 2. Settings Services Tests

### test_can_save_and_retrieve_logo_file()

```php
public function test_can_save_and_retrieve_logo_file()
{
    Storage::fake('public');
    
    $oldFile = UploadedFile::fake()->image('old_logo.png');
    $firstPath = $oldFile->store('website', 'public');
    
    setting('website')->save(['logo_path' => $firstPath]);
    
    Storage::disk('public')->assertExists(setting('website')->logo_path);
    $this->assertEquals($firstPath, setting('website')->logo_path);
}
```
**يختبر**: تخزين ومسترجعة مسارات الملفات

---

### test_can_save_and_retrieve_single_setting()

```php
public function test_can_save_and_retrieve_single_setting()
{
    $items = [
        'name' => 'name value new',
        'email' => 'email value',
    ];

    setting('item_single')->save($items);
    
    $this->assertEquals('name value new', setting('item_single')->name);
    $this->assertEquals('email value', setting('item_single')->email);

    // Blade Usage
    $blade = "@setting('item_single', 'name')";
    $rendered = Blade::render($blade);
    
    $this->assertStringContainsString('name value new', $rendered);
}
```

**يختبر**: 
- حفظ واسترجاع إعدادات بسيطة
- الوصول للخصائص مباشرة (`->name`)

---

#### Blade Directive شرح:
```blade
@setting('item_single', 'name')
```
يُترجم إلى:
```php
setting('item_single')->getValue('name')
```
**النتيجة**: يطبع قيمة الخاصية على الشاشة

---

### test_can_save_and_retrieve_single_language_setting()

```php
public function test_can_save_and_retrieve_single_language_setting()
{
    $items = [
        'name' => ['ar' => 'name ar', 'en' => 'name en'],
        'email' => ['ar' => 'email ar', 'en' => 'email en'],
    ];

    setting('single_language')->save($items);
    
    $locale = 'ar';
    
    $this->assertEquals('name ar', setting('single_language')->name->ar);
    $this->assertEquals('email ar', setting('single_language')->email->ar);

    // Blade Usage
    $blade = "@setting('single_language', 'name.ar')";
    $rendered = Blade::render($blade);
    
    $this->assertStringContainsString('name ar', $rendered);
}
```

**يختبر**: 
- القيم متعددة اللغات
- الوصول للقيم حسب اللغة (`->ar`, `->en`)

---

#### Blade Directive:
```blade
@setting('single_language', 'name.ar')
```
يسترجع: `'name ar'`

---

### test_can_save_and_retrieve_single_multiple_items()

```php
public function test_can_save_and_retrieve_single_multiple_items()
{
    $items = [
        ['name' => 'name value 1', 'email' => 'email value 1'],
        ['name' => 'name value 2', 'email' => 'email value 2'],
    ];

    setting('single_multiple_items')->save($items);
    
    $firstItem = setting('single_multiple_items')->first();
    $this->assertEquals('name value 1', $firstItem->name);

    setting('single_multiple_items')->each(function ($item) {
        $this->assertEquals($item->name, $item->name);
    });

    // Blade Usage
    $blade = "@eachSetting('single_multiple_items as \$var')
                <div>{{ \$var->name }} - {{ \$var->email }}</div>
              @endEachSetting";

    $rendered = Blade::render($blade);
    
    $this->assertStringContainsString('name value 1', $rendered);
}
```

**يختبر**: 
- Array من العناصر
- الوصول للعنصر الأول: `->first()`
- التكرار على العناصر: `->each()`

---

#### Blade Directive شرح:

```blade
@eachSetting('single_multiple_items as $var')
    <div>{{ $var->name }} - {{ $var->email }}</div>
@endEachSetting
```

يُترجم إلى:
```php
<?php foreach((array) setting('single_multiple_items')->getValue('') as $var): ?>
    <div><?php echo $var->name; ?> - <?php echo $var->email; ?></div>
<?php endforeach; ?>
```

---

### test_can_save_and_retrieve_single_multiple_language_items()

```php
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
    
    $this->assertEquals('name value 1 ar', $firstItemAr->name);
    $this->assertEquals('name value 1 en', $firstItemEn->name);

    setting('single_multiple_language_items', 'ar')->each(function ($item) {
        $this->assertEquals($item->name, $item->name);
    });

    // Blade Usage
    $blade = "@eachSetting('single_multiple_language_items as \$var')
                <div>{{ \$var->name }} - {{ \$var->email }}</div>
              @endEachSetting";
}
```

**يختبر**: 
- Array متعدد اللغات
- الوصول حسب اللغة: `setting(..., 'ar')`, `setting(..., 'en')`

---

### Blade Directives الموجودة:

#### 1. @setting()
```blade
@setting('key')           <!-- يعرض جميع القيم -->
@setting('key', 'field')  <!-- يعرض قيمة حقل معين -->
```

#### 2. @eachSetting()
```blade
@eachSetting('key as $item')
    {{ $item->field }}
@endEachSetting
```

---

## ملخص الفئات الرئيسية:

### SettingsServices
- `save()` - حفظ البيانات
- `get()` - الحصول على البيانات
- `getValue()` - الحصول على قيمة معينة
- `update()` - تحديث قيم متداخلة
- `delete()` - حذف الإعدادات
- `first()`, `last()` - الوصول لأول/آخر عنصر
- `each()` - التكرار على العناصر
- `sort()` - ترتيب العناصر
- `groupBy()` - تجميع العناصر
- `search()` - البحث
- `where()` - تصفية البيانات

### SafeObject
- كائن آمن للوصول للقيم المتداخلة
- يدعم الوصول عبر الخصائص: `->property`
- يدعم `foreach`: `->each()`
- يدعم `->toArray()`

---

## كيفية تشغيل الاختبارات:

```bash
# تشغيل جميع الاختبارات
vendor/bin/pest

# تشغيل ملف اختبار معين
vendor/bin/pest tests/Feature/Services/Settings/CrudOperationsTest.php

# تشغيل اختبار معين
vendor/bin/pest --filter "test_create"
```

---

## ملاحظات مهمة:

1. **RefreshDatabase** - يُنظف قاعدة البيانات قبل وبعد كل اختبار
2. **Testing مع Blade** - يتم تصيير الـ Blade directives وتحويلها لـ PHP
3. **Multi-language** - اللغة الافتراضية هي `en`، لكن يمكن تحديد لغة أخرى
4. **SafeObject** - التعامل الآمن مع القيم المتداخلة بدون أخطاء null
