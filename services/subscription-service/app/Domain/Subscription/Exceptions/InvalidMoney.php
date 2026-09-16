<?php

namespace App\Domain\Subscription\Exceptions;

use InvalidArgumentException;

final class InvalidMoney extends InvalidArgumentException
{
    public static function negativeAmount(int $amountMinorUnits): self
    {
        return new self("{$amountMinorUnits} is not a valid amount: it must not be negative.");
    }
}
