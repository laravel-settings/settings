<?php

use LaravelSettings\Settings\Services\Settings\SettingsServices;

if (!function_exists('setting')) {

	function setting(string $key = '', $lang = null): SettingsServices
	{
	    return new SettingsServices($key, $lang);

	}//end of fun

}//end of exists