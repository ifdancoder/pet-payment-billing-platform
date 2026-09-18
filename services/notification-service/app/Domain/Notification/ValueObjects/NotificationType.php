<?php

namespace App\Domain\Notification\ValueObjects;

enum NotificationType: int
{
    case PaymentReceipt = 1;

    public function label(): string
    {
        return match ($this) {
            self::PaymentReceipt => 'payment_receipt',
        };
    }
}
