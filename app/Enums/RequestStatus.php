<?php

namespace App\Enums;

enum RequestStatus: string
{
    case New = 'new';
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case WaitingGuest = 'waiting_guest';
    case Ready = 'ready';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новые',
            self::Accepted => 'Приняты',
            self::InProgress => 'В работе',
            self::WaitingGuest => 'Ожидают гостя',
            self::Ready => 'Готово',
            self::Completed => 'Завершены',
            self::Cancelled => 'Отменены',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'primary',
            self::Accepted => 'info',
            self::InProgress => 'warning',
            self::WaitingGuest => 'secondary',
            self::Ready => 'success',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    public static function kanban(): array
    {
        return [self::New, self::Accepted, self::InProgress, self::WaitingGuest, self::Ready, self::Completed];
    }
}
