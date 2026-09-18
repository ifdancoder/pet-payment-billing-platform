<?php

namespace App\Domain\Notification\ValueObjects;

enum DeliveryAttemptStatus: int
{
    case Pending = 1;
    case Succeeded = 2;
    case Failed = 3;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'pending',
            self::Succeeded => 'succeeded',
            self::Failed => 'failed',
        };
    }
}
