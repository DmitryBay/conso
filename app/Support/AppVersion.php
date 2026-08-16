<?php

namespace App\Support;

final class AppVersion
{
    public static function current(): string
    {
        $files = [
            base_path('bootstrap/cache/config.php'),
            public_path('build/manifest.json'),
            base_path('composer.lock'),
        ];

        $fingerprint = collect($files)
            ->filter(fn (string $file): bool => is_file($file))
            ->map(fn (string $file): string => $file.':'.filemtime($file).':'.filesize($file))
            ->implode('|');

        return substr(hash('sha256', $fingerprint), 0, 16);
    }
}
