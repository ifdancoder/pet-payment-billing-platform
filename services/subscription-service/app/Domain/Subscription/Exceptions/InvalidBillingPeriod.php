<?php

namespace App\Domain\Subscription\Exceptions;

use InvalidArgumentException;

final class InvalidBillingPeriod extends InvalidArgumentException
{
    public static function countMustBeAtLeastOne(int $count): self
    {
        return new self("{$count} is not a valid billing period count: it must be at least 1.");
    }
}
