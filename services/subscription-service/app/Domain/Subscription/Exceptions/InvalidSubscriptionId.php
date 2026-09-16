<?php

namespace App\Domain\Subscription\Exceptions;

use InvalidArgumentException;

final class InvalidSubscriptionId extends InvalidArgumentException
{
    public static function forValue(string $value): self
    {
        return new self("\"{$value}\" is not a valid subscription id.");
    }
}
