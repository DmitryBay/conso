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

    public static function initials(?string $name): string
    {
        $parts = preg_split('/[\s\p{Z}]+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return '•';
        }

        $initials = count($parts) === 1
            ? mb_substr($parts[0], 0, 2)
            : mb_substr($parts[0], 0, 1).mb_substr($parts[array_key_last($parts)], 0, 1);

        return mb_strtoupper($initials);
    }
}
