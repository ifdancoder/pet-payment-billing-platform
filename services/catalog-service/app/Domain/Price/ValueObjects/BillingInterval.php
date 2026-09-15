<?php

namespace App\Domain\Price\ValueObjects;

enum BillingInterval: int
{
    case Day = 1;
    case Week = 2;
    case Month = 3;
    case Year = 4;

    public function label(): string
    {
        return match ($this) {
            self::Day => 'day',
            self::Week => 'week',
            self::Month => 'month',
            self::Year => 'year',
        };
    }
}
