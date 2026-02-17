<?php

namespace LaravelSettings\Settings\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use LaravelSettings\Settings\Services\Drivers\SettingsDriver;
use LaravelSettings\Settings\Services\Drivers\DatabaseDriver;
use LaravelSettings\Settings\Services\Drivers\JsonFileDriver;
use LaravelSettings\Settings\Services\Drivers\RedisDriver;
use LaravelSettings\Settings\Services\Drivers\CacheDriver;

class SettingsServiceProvider extends ServiceProvider
{
    public function register()
    {
        require_once __DIR__ . '/../Helpers/SettingHelper.php';
        $this->settingsDriver();
    }

    public function boot()
    {
        $this->publishSettings();
        $this->bladeDirectives();
    }

    public function settingsDriver()
    {
        $this->app->bind(SettingsDriver::class, function () {

            return match (config('settings.driver')) {
                'file' => new JsonFileDriver(),
                'redis' => new RedisDriver(),
                'cache' => new CacheDriver(),
                default => new DatabaseDriver(),
            };

        });
    } 

    public function bladeDirectives() 
    {
        Blade::directive('eachSetting', function ($expression) {
            $expression = trim($expression, '()');
            if (str_contains($expression, ' as ')) {
                [$path, $var] = array_map('trim', explode(' as ', $expression, 2));
                $var = preg_replace("/['\"\)\(\s]+/", '', $var);
                if ($var[0] !== '$') $var = '$' . $var;
            } else {
                $path = $expression;
                $var = '$item';
            }
            $path = trim($path, "'\"");
            [$settingKey, $fieldPath] = array_pad(explode('.', $path, 2), 2, '');
            $fieldPath = $fieldPath !== '' ? $fieldPath : '';
            return "<?php foreach((array) setting('{$settingKey}')->getValue('{$fieldPath}') as {$var}): ?>";
        });

        Blade::directive('endEachSetting', fn () => "<?php endforeach; ?>");

        Blade::directive('setting', function ($expression) {
            [$settingKey, $fieldPath] = array_map('trim', explode(',', $expression . ','));

            $getValue = $fieldPath
                ? "->getValue({$fieldPath})"
                : "->getValue()";

            return "<?php echo setting({$settingKey}){$getValue}; ?>";
        });
    }

    public function publishSettings()
    {
        $timestamp = now()->format('Y_m_d_His'); 
        $this->publishes([ 
            __DIR__.'/../../database/migrations/0000_create_settings_table.php' => database_path("migrations/{$timestamp}_create_settings_table.php")
        ], 'settings-migrations');

        $this->publishes([
            __DIR__.'/../../config/settings.php' => config_path('settings.php'),
        ], 'settings-config');
    }
}