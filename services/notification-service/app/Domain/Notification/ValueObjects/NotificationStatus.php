<?php

namespace App\Domain\Notification\ValueObjects;

enum NotificationStatus: int
{
    case Pending = 1;
    case Processing = 2;
    case Sent = 3;
    case Failed = 4;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'pending',
            self::Processing => 'processing',
            self::Sent => 'sent',
            self::Failed => 'failed',
        };
    }
}
