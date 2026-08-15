<?php

namespace App\Support;

use App\Models\PlatformSetting;

class SystemMail
{
    public static function address(): string
    {
        return (string) PlatformSetting::read('system_email', config('mail.from.address'));
    }

    public static function name(): string
    {
        return (string) PlatformSetting::read('platform_name', config('mail.from.name'));
    }
}
