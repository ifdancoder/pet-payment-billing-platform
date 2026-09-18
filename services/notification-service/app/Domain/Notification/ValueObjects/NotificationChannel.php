<?php

namespace App\Domain\Notification\ValueObjects;

enum NotificationChannel: int
{
    case Email = 1;

    public function label(): string
    {
        return match ($this) {
            self::Email => 'email',
        };
    }
}
