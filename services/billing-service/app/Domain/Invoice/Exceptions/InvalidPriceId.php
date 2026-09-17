<?php

namespace App\Domain\Invoice\Exceptions;

use InvalidArgumentException;

final class InvalidPriceId extends InvalidArgumentException
{
    public static function forValue(string $value): self
    {
        return new self("\"{$value}\" is not a valid price id.");
    }
}
