<?php

namespace App\Domain\Invoice\Exceptions;

use InvalidArgumentException;

final class InvalidBillingPeriod extends InvalidArgumentException
{
    public static function endMustBeAfterStart(): self
    {
        return new self('A billing period must end after it starts.');
    }
}
