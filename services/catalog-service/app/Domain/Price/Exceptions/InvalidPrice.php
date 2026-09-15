<?php

namespace App\Domain\Price\Exceptions;

use InvalidArgumentException;

final class InvalidPrice extends InvalidArgumentException
{
    public static function billingPeriodRequiredForRecurring(): self
    {
        return new self('A recurring price requires a billing period.');
    }

    public static function billingPeriodNotAllowedForOneTime(): self
    {
        return new self('A one-time price must not have a billing period.');
    }
}
