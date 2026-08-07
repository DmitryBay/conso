<?php

namespace App\Enums;

enum ServiceNodeType: string
{
    case Category = 'category';
    case Service = 'service';
    case Guide = 'guide';

    public function label(): string
    {
        return match ($this) {
            self::Category => 'Категория',
            self::Service => 'Услуга',
            self::Guide => 'Гайд',
        };
    }
}
