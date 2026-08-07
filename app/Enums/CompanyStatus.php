<?php

namespace App\Enums;

enum CompanyStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Trial => 'Пробный период',
            self::Active => 'Активна',
            self::Suspended => 'Приостановлена',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Trial => 'warning',
            self::Active => 'success',
            self::Suspended => 'secondary',
        };
    }
}
