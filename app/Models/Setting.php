<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Key/value system settings editable from the admin panel. */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    protected static function booted(): void
    {
        static::saved(fn (Setting $setting) => Cache::forget("setting:{$setting->key}"));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting:{$key}", fn () => static::query()->find($key)?->value) ?? $default;
    }
}
