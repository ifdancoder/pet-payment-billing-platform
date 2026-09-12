<?php

namespace App\Domain\Customer\Exceptions;

use InvalidArgumentException;

final class InvalidCustomerId extends InvalidArgumentException
{
    public static function forValue(string $value): self
    {
        return new self("\"{$value}\" is not a valid customer id.");
    }
}
