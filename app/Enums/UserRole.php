<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case CompanyOwner = 'company_owner';
    case Manager = 'manager';
    case Guest = 'guest';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Администратор платформы',
            self::CompanyOwner => 'Владелец компании',
            self::Manager => 'Менеджер',
            self::Guest => 'Гость',
        };
    }
}
