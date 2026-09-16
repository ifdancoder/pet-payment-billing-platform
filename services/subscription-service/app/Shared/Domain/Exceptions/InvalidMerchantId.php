<?php

namespace App\Shared\Domain\Exceptions;

use InvalidArgumentException;

final class InvalidMerchantId extends InvalidArgumentException
{
    public static function forValue(string $value): self
    {
        return new self("\"{$value}\" is not a valid merchant id.");
    }
}
