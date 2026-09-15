<?php

namespace App\Domain\Price\ValueObjects;

enum PriceType: int
{
    case OneTime = 1;
    case Recurring = 2;

    public function label(): string
    {
        return match ($this) {
            self::OneTime => 'one_time',
            self::Recurring => 'recurring',
        };
    }
}
