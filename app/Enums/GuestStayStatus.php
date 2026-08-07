<?php

namespace App\Enums;

enum GuestStayStatus: string
{
    case Upcoming = 'upcoming';
    case CheckedIn = 'checked_in';
    case CheckedOut = 'checked_out';
    case Cancelled = 'cancelled';

    public function color(): string
    {
        return match ($this) {
            self::Upcoming => 'primary',
            self::CheckedIn => 'success',
            self::CheckedOut => 'secondary',
            self::Cancelled => 'danger',
        };
    }
}
