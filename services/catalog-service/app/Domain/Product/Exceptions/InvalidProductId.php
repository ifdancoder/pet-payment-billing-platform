<?php

namespace App\Domain\Product\Exceptions;

use InvalidArgumentException;

final class InvalidProductId extends InvalidArgumentException
{
    public static function forValue(string $value): self
    {
        return new self("\"{$value}\" is not a valid product id.");
    }
}
