<?php

namespace App\Domain\Price\ValueObjects;

enum PriceStatus: int
{
    case Active = 1;
    case Inactive = 2;

    public function label(): string
    {
        return match ($this) {
            self::Active => 'active',
            self::Inactive => 'inactive',
        };
    }
}
