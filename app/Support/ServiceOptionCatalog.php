<?php

namespace App\Support;

class ServiceOptionCatalog
{
    public const OPTIONS = [
        'table_setting' => 'bi-flower1',
        'in_room_service' => 'bi-door-open',
        'contactless_delivery' => 'bi-box-seam',
        'preferred_time' => 'bi-clock',
        'eco_friendly' => 'bi-leaf',
        'allergy_friendly' => 'bi-shield-check',
        'child_friendly' => 'bi-emoji-smile',
        'express_service' => 'bi-lightning-charge',
        'delicate_care' => 'bi-feather',
        'child_seat' => 'bi-person-arms-up',
        'meet_and_greet' => 'bi-person-check',
        'extra_luggage' => 'bi-luggage',
    ];

    public static function keys(): array
    {
        return array_keys(self::OPTIONS);
    }

    public static function icon(string $key): string
    {
        return self::OPTIONS[$key] ?? 'bi-check2-square';
    }

    public static function normalize(array $keys): array
    {
        return collect($keys)
            ->map(fn (mixed $key) => (string) $key)
            ->filter(fn (string $key) => array_key_exists($key, self::OPTIONS))
            ->unique()
            ->values()
            ->all();
    }

    public static function defaultsFor(string $name, ?string $background = null): array
    {
        $name = mb_strtolower($name);

        return match (true) {
            str_contains($name, 'cleaning'), str_contains($name, 'уборк') => ['preferred_time', 'eco_friendly'],
            str_contains($name, 'laundry'), str_contains($name, 'стирк'), str_contains($name, 'прачеч') => ['express_service', 'delicate_care'],
            str_contains($name, 'transfer'), str_contains($name, 'трансфер'), str_contains($name, 'airport'), str_contains($name, 'аэропорт') => ['child_seat', 'meet_and_greet', 'extra_luggage'],
            $background === 'food', str_contains($name, 'breakfast'), str_contains($name, 'завтрак'), str_contains($name, 'room service') => ['table_setting', 'in_room_service', 'contactless_delivery', 'allergy_friendly', 'child_friendly'],
            default => [],
        };
    }
}
