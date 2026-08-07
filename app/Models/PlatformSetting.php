<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class PlatformSetting extends Model
{
    public static function read(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function enabled(string $key, bool $default = true): bool
    {
        return filter_var(static::read($key, $default ? '1' : '0'), FILTER_VALIDATE_BOOL);
    }

    public static function write(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }
}
