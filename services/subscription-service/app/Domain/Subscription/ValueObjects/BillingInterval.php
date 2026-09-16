<?php

namespace App\Domain\Subscription\ValueObjects;

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

    public static function fromLabel(string $label): self
    {
        return match ($label) {
            'day' => self::Day,
            'week' => self::Week,
            'month' => self::Month,
            'year' => self::Year,
            default => throw new \ValueError("\"{$label}\" is not a valid billing interval label."),
        };
    }
}
