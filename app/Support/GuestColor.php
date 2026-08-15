<?php

namespace App\Support;

use Illuminate\Support\Str;

class GuestColor
{
    public const PALETTE_SIZE = 20;

    public static function index(?string $name): int
    {
        $normalized = Str::lower(trim((string) $name));
        $hashPrefix = substr(hash('sha256', $normalized), 0, 8);

        return (int) (hexdec($hashPrefix) % self::PALETTE_SIZE);
    }
}
