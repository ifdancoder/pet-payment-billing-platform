<?php

namespace App\Domain\Merchant\ValueObjects;

enum MerchantStatus: int
{
    case Active = 1;
    case Disabled = 2;

    public function label(): string
    {
        return match ($this) {
            self::Active => 'active',
            self::Disabled => 'disabled',
        };
    }
}
