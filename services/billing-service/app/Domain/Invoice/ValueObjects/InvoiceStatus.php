<?php

namespace App\Domain\Invoice\ValueObjects;

enum InvoiceStatus: int
{
    case Open = 1;
    case Paid = 2;
    case Void = 3;

    public function label(): string
    {
        return match ($this) {
            self::Open => 'open',
            self::Paid => 'paid',
            self::Void => 'void',
        };
    }
}
