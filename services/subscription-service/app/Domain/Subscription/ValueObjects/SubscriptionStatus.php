<?php

namespace App\Domain\Subscription\ValueObjects;

enum SubscriptionStatus: int
{
    case Pending = 1;
    case Active = 2;
    case PastDue = 3;
    case Canceled = 4;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'pending',
            self::Active => 'active',
            self::PastDue => 'past_due',
            self::Canceled => 'canceled',
        };
    }
}
