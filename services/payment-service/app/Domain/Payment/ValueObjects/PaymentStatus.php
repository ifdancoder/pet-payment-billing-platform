<?php

namespace App\Domain\Payment\ValueObjects;

enum PaymentStatus: int
{
    case Pending = 1;
    case Processing = 2;
    case Succeeded = 3;
    case Failed = 4;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'pending',
            self::Processing => 'processing',
            self::Succeeded => 'succeeded',
            self::Failed => 'failed',
        };
    }
}
