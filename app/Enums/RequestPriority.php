<?php

namespace App\Enums;

enum RequestPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Низкий',
            self::Normal => 'Обычный',
            self::High => 'Высокий',
            self::Urgent => 'Срочный',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'secondary',
            self::Normal => 'primary',
            self::High => 'warning',
            self::Urgent => 'danger',
        };
    }
}
