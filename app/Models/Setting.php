<?php

namespace LaravelSettings\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Setting extends Model
{
    protected $guarded = [];

    public function getTable()
    {
        return config('settings.table', 'settings');
    }

    public function value(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => json_validate($value) ? json_decode($value, true) : $value,
            set: fn ($value) => is_array($value) ? json_encode($value) : $value
        );

    }//end of get value

}//end of model